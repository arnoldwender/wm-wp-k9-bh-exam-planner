<?php
/**
 * WM K9 BH Exam Planner — Score calculator for the companion dog trial (BH/VT)
 *
 * Sums the five Teil A exercises (15 + 15 + 10 + 10 + 10 = 60 points, 42 to pass), takes the
 * Teil B result as given and applies a disqualification. Hardcoded values; not an official sheet.
 *
 * Source of the layout: the five exercises and their maxima are those of the FCI-Prüfungsordnung 2019
 * (valid from 01.01.2019, "Prüfungsbeschreibung: Begleithundeprüfung mit Verhaltenstest"). The FCI-PO 2025,
 * valid from 01.01.2025, scores Leinenführigkeit 30, Sitz 10, Ablegen in Verbindung mit Herankommen 10 and
 * Ablegen unter Ablenkung 10 and has no Freifolge. Both editions pass Teil A at 70 % of the points and admit
 * to Teil B only after Teil A passed. Moving to the 2025 layout changes form fields and REST keys and waits
 * for a decision.
 *
 * @package WM_K9_BH_Exam_Planner
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_K9_Scoring_Engine {

	public const RULE_EDITION = 'FCI-PO 2019';

	/** Exercise key => German label and maximum points (FCI-PO 2019 layout). */
	public const EXERCISES = [
		'leinenfuehrigkeit'   => [ 'label' => 'Leinenführigkeit', 'max' => 15.0 ],
		'freifolge'           => [ 'label' => 'Freifolge', 'max' => 15.0 ],
		'sitz_bewegung'       => [ 'label' => 'Sitz aus der Bewegung', 'max' => 10.0 ],
		'ablegen_herankommen' => [ 'label' => 'Ablegen mit Herankommen', 'max' => 10.0 ],
		'ablegen_ablenkung'   => [ 'label' => 'Ablegen unter Ablenkung', 'max' => 10.0 ],
	];

	public const MAX_POINTS_TEIL_A     = 60.0;
	public const PASSING_POINTS_TEIL_A = 42.0; // 70 % of 60

	/**
	 * Points in German notation: one decimal, two when the value has hundredths ("41,96", not a rounded "42,0").
	 */
	public static function format_points( float $points ): string {
		$decimals = 0.0 === fmod( round( $points * 10, 6 ), 1.0 ) ? 1 : 2;
		return number_format( $points, $decimals, ',', '.' );
	}

	/**
	 * Evaluate exam scorecard.
	 *
	 * @param array<string, float|int|string> $teil_a_scores Exercise key => points.
	 * @param bool   $teil_b_passed
	 * @param bool   $disqualified
	 * @param string $disqualification_reason
	 * @return array{
	 *     verdict: string,
	 *     teil_a_points: float,
	 *     teil_a_passed: bool,
	 *     teil_b_passed: bool,
	 *     errors: list<string>
	 * }
	 */
	public static function evaluate(
		array $teil_a_scores,
		bool $teil_b_passed,
		bool $disqualified = false,
		string $disqualification_reason = ''
	): array {
		$context = [
			'teil_a_scores'           => $teil_a_scores,
			'teil_b_passed'           => $teil_b_passed,
			'disqualified'            => $disqualified,
			'disqualification_reason' => $disqualification_reason,
		];

		if ( $disqualified ) {
			return self::finalize_result(
				[
					'verdict'        => 'DISQUALIFIZIERT',
					'teil_a_points'  => 0.0,
					'teil_a_passed'  => false,
					'teil_b_passed'  => false,
					// Until 2026-09-15 a missing reason read "Verstoß gegen Prüfungsordnung", a violation nobody stated.
					'errors'         => [ 'Disqualifikation: ' . ( '' !== trim( $disqualification_reason ) ? $disqualification_reason : 'kein Grund angegeben' ) ],
				],
				$context
			);
		}

		// Clamp each exercise to its range and sum. The comparison uses the unrounded sum (to six decimals, which
		// only removes float noise): until 2026-09-15 the sum was rounded to one decimal first, so 41.96 passed.
		$sum = 0.0;
		foreach ( self::EXERCISES as $key => $exercise ) {
			$sum += max( 0.0, min( $exercise['max'], (float) ( $teil_a_scores[ $key ] ?? 0.0 ) ) );
		}
		$sum           = round( $sum, 6 );
		$teil_a_passed = $sum >= self::PASSING_POINTS_TEIL_A;
		$total_a       = round( $sum, 2 );

		$errors = [];
		if ( ! $teil_a_passed ) {
			$errors[] = sprintf(
				'Teil A (Unterordnung) nicht bestanden: %s von %s Punkten erreicht (mindestens %s Punkte erforderlich).',
				self::format_points( $total_a ),
				self::format_points( self::MAX_POINTS_TEIL_A ),
				self::format_points( self::PASSING_POINTS_TEIL_A )
			);
		}

		// Both editions admit to Teil B only after Teil A passed, so a failed Teil A reports Teil A only.
		if ( $teil_a_passed && ! $teil_b_passed ) {
			$errors[] = 'Teil B (Verkehrsteil) nicht bestanden.';
		}

		$overall_passed = $teil_a_passed && $teil_b_passed;

		return self::finalize_result(
			[
				'verdict'        => $overall_passed ? 'BESTANDEN' : 'NICHT_BESTANDEN',
				'teil_a_points'  => $total_a,
				'teil_a_passed'  => $teil_a_passed,
				'teil_b_passed'  => $teil_b_passed,
				'errors'         => $errors,
			],
			$context
		);
	}

	/**
	 * Pass calculated scorecard result through developer filter and action hooks.
	 *
	 * @param array{verdict: string, teil_a_points: float, teil_a_passed: bool, teil_b_passed: bool, errors: list<string>} $result
	 * @param array<string, mixed> $context
	 * @return array{verdict: string, teil_a_points: float, teil_a_passed: bool, teil_b_passed: bool, errors: list<string>}
	 */
	private static function finalize_result( array $result, array $context ): array {
		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'wm_k9_bewertung_result', $result, $context );
			if (
				is_array( $filtered )
				&& isset( $filtered['verdict'], $filtered['teil_a_points'], $filtered['teil_a_passed'], $filtered['teil_b_passed'], $filtered['errors'] )
				&& is_array( $filtered['errors'] )
			) {
				$result = [
					'verdict'       => (string) $filtered['verdict'],
					'teil_a_points' => (float) $filtered['teil_a_points'],
					'teil_a_passed' => (bool) $filtered['teil_a_passed'],
					'teil_b_passed' => (bool) $filtered['teil_b_passed'],
					'errors'        => array_values( array_map( 'strval', $filtered['errors'] ) ),
				];
			}
		}

		if ( function_exists( 'do_action' ) ) {
			do_action( 'wm_k9_bewertung_evaluated', $result, $context );
		}

		return $result;
	}
}
