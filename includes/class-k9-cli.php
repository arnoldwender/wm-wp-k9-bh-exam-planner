<?php
/**
 * WM K9 BH Exam Planner — WP-CLI Command Interface
 *
 * @package WM_K9_BH_Exam_Planner
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_K9_CLI {

	/**
	 * Display the operational status of the K9 exam engine.
	 *
	 * ## EXAMPLES
	 *
	 *     wp k9 status
	 */
	public function status( array $args, array $assoc_args ): void {
		WP_CLI::line( '==================================================' );
		WP_CLI::line( 'WM K9 BH EXAM PLANNER' );
		WP_CLI::line( '==================================================' );
		WP_CLI::line( 'Version:          ' . ( defined( 'WM_K9_PLANNER_VERSION' ) ? WM_K9_PLANNER_VERSION : '?' ) );
		WP_CLI::line( sprintf( 'Zulassung:        Mindestalter %d Monate, %d-stelliger Transpondercode, SKN', WM_K9_Admission_Validator::MIN_AGE_MONTHS, WM_K9_Admission_Validator::CHIP_DIGITS ) );
		WP_CLI::line( sprintf( 'Bewertung Teil A: max. %s Punkte, bestanden ab %s (Aufteilung %s)', WM_K9_Scoring_Engine::format_points( WM_K9_Scoring_Engine::MAX_POINTS_TEIL_A ), WM_K9_Scoring_Engine::format_points( WM_K9_Scoring_Engine::PASSING_POINTS_TEIL_A ), WM_K9_Scoring_Engine::RULE_EDITION ) );
		WP_CLI::line( 'Gespeicherte Daten: keine' );
		WP_CLI::line( '==================================================' );
	}

	/**
	 * Validate a dog candidate's eligibility for Begleithundeprüfung admission.
	 *
	 * ## OPTIONS
	 *
	 * [--dob=<dob>]
	 * : Birthdate of the dog (YYYY-MM-DD).
	 *
	 * [--exam=<exam>]
	 * : Date of the scheduled examination (YYYY-MM-DD); today in the site's time zone when omitted.
	 *
	 * [--chip=<chip>]
	 * : Transponder code; 15 digits after removing everything else (format only).
	 *
	 * [--skn=<skn>]
	 * : Handler Sachkundenachweis present (true|false).
	 *
	 * ## EXAMPLES
	 *
	 *     wp k9 check-eligibility --dob=2024-05-01 --chip=276098100123456 --skn=true
	 */
	public function check_eligibility( array $args, array $assoc_args ): void {
		$res = self::eligibility( $assoc_args );

		if ( $res['eligible'] ) {
			WP_CLI::success( self::success_message( $res ) );
		} else {
			WP_CLI::error( sprintf( "Vorabprüfung nicht bestanden (%d Monate):\n  - %s", $res['age_months'], implode( "\n  - ", $res['errors'] ) ) );
		}
	}

	/**
	 * The check behind `wp k9 check-eligibility`, without WP_CLI, so the standalone suite can run it.
	 * The exam date defaults to today in the site's time zone (until 2026-09-15 the UTC date).
	 *
	 * @param array<string, string> $assoc_args
	 * @return array{eligible: bool, age_months: int, errors: list<string>}
	 */
	public static function eligibility( array $assoc_args ): array {
		$dob       = (string) ( $assoc_args['dob'] ?? '' );
		$exam_date = (string) ( $assoc_args['exam'] ?? WM_K9_Shortcodes::site_today() );
		$chip      = (string) ( $assoc_args['chip'] ?? '' );
		$skn       = isset( $assoc_args['skn'] ) && in_array( strtolower( (string) $assoc_args['skn'] ), [ '1', 'true', 'yes' ], true );

		return WM_K9_Admission_Validator::check_eligibility( $dob, $exam_date, $chip, $skn );
	}

	/**
	 * Until 2026-09-15: "Zulassung ERTEILT! … erfüllt alle Kriterien" — three criteria are checked, and the club admits.
	 *
	 * @param array{age_months: int} $res
	 */
	public static function success_message( array $res ): string {
		return sprintf( 'Vorabprüfung bestanden: Hund ist %d Monate alt und erfüllt die drei geprüften Kriterien. Über die Zulassung entscheidet der ausrichtende Verein.', $res['age_months'] );
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'k9', 'WM_K9_CLI' );
}
