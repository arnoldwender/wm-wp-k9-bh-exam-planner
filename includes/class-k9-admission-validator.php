<?php
/**
 * WM K9 BH Exam Planner — Admission pre-check for the companion dog trial (BH/VT)
 *
 * Three hardcoded checks:
 * - minimum age of 15 months on the exam date: the VDH sets 15 months for the BH (vdh.de, Begleithundprüfung);
 *   the FCI-PO leaves the age to the national organisation, not below 12 months ("Am Tag der Prüfung muss der
 *   Hund das erforderliche Mindestalter erreicht haben")
 * - transponder code of exactly 15 digits (format only; ISO 11784 has no checksum to verify)
 * - handler holds a Sachkundenachweis (SKN), as declared by the caller; the FCI-PO admits handlers who prove the
 *   Sachkundeprüfung or an official proof of Sachkunde
 *
 * @package WM_K9_BH_Exam_Planner
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_K9_Admission_Validator {

	public const MIN_AGE_MONTHS = 15;

	public const CHIP_DIGITS = 15;

	/**
	 * A calendar date in exactly the form YYYY-MM-DD, or null. `!` zeroes the time of day, and the round trip
	 * rejects dates PHP would roll over: until 2026-09-15 "2024-00-15" became 2023-12-15 and "2025-10-32"
	 * became 2025-11-01, and the dog came back eligible.
	 */
	public static function parse_date( string $value ): ?DateTimeImmutable {
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
		if ( false === $date || $date->format( 'Y-m-d' ) !== $value ) {
			return null;
		}
		return $date;
	}

	/**
	 * Full calendar months from the date of birth to the exam date.
	 *
	 * A month is complete on the day with the same number as the birth day; when the target month is shorter
	 * (born on the 30th, target February), on the first day of the following month. This is the reading of
	 * § 187 Abs. 2 and § 188 Abs. 2, 3 BGB for ages; the Prüfungsordnung gives no counting rule. Until
	 * 2026-09-15 PHP's DateTime::diff() decided, which rejected a dog born on 30 November on 1 March.
	 */
	public static function age_in_months( DateTimeImmutable $birth, DateTimeImmutable $exam ): int {
		$months = ( (int) $exam->format( 'Y' ) - (int) $birth->format( 'Y' ) ) * 12
			+ ( (int) $exam->format( 'n' ) - (int) $birth->format( 'n' ) );
		if ( (int) $birth->format( 'j' ) > (int) $exam->format( 'j' ) ) {
			--$months;
		}
		return $months;
	}

	/**
	 * Validate if a dog is eligible for the Begleithundeprüfung (BH/VT) on exam date.
	 *
	 * Every check runs and every reason is returned: until 2026-09-15 an unreadable date returned one error and
	 * skipped the chip and SKN checks.
	 *
	 * @param string $birth_date ISO date YYYY-MM-DD.
	 * @param string $exam_date ISO date YYYY-MM-DD.
	 * @param string $microchip_code Transponder code; everything but digits is ignored.
	 * @param bool   $has_skn Whether the handler has a Sachkundenachweis.
	 * @return array{eligible: bool, age_months: int, errors: list<string>}
	 */
	public static function check_eligibility(
		string $birth_date,
		string $exam_date,
		string $microchip_code,
		bool $has_skn
	): array {
		$errors     = [];
		$age_months = 0;

		// 1. Age on the exam day
		$dob  = self::parse_date( $birth_date );
		$exam = self::parse_date( $exam_date );

		if ( null === $dob || null === $exam ) {
			$errors[] = 'Ungültiges Datum für Geburtsdatum oder Prüfungsdatum (ein existierendes Datum im Format JJJJ-MM-TT erforderlich).';
		} elseif ( $exam < $dob ) {
			// Until 2026-09-15 this read "Mindestalter nicht erreicht: 21 Monate".
			$errors[] = 'Das Prüfungsdatum liegt vor dem Geburtsdatum.';
		} else {
			$age_months = self::age_in_months( $dob, $exam );
			if ( $age_months < self::MIN_AGE_MONTHS ) {
				$errors[] = sprintf(
					'Mindestalter nicht erreicht: Hund ist am Prüfungstag %d Monate alt (geprüft werden mindestens %d Monate).',
					$age_months,
					self::MIN_AGE_MONTHS
				);
			}
		}

		// 2. Transponder code: digit count only
		$clean_chip = (string) preg_replace( '/[^0-9]/', '', $microchip_code );
		if ( self::CHIP_DIGITS !== strlen( $clean_chip ) ) {
			$errors[] = sprintf( 'Transponder-Code hat nicht exakt %d Ziffern.', self::CHIP_DIGITS );
		}

		// 3. Handler SKN as declared
		if ( ! $has_skn ) {
			$errors[] = 'Sachkundenachweis (SKN) des Hundeführers fehlt oder ist ungültig.';
		}

		$result = [
			'eligible'   => empty( $errors ),
			'age_months' => $age_months,
			'errors'     => $errors,
		];

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters(
				'wm_k9_zulassung_result',
				$result,
				[
					'birth_date' => $birth_date,
					'exam_date'  => $exam_date,
					'microchip'  => $microchip_code,
					'has_skn'    => $has_skn,
				]
			);
			if (
				is_array( $filtered )
				&& isset( $filtered['eligible'], $filtered['age_months'], $filtered['errors'] )
				&& is_array( $filtered['errors'] )
			) {
				$result = [
					'eligible'   => (bool) $filtered['eligible'],
					'age_months' => (int) $filtered['age_months'],
					'errors'     => array_values( array_map( 'strval', $filtered['errors'] ) ),
				];
			}
		}

		if ( function_exists( 'do_action' ) ) {
			do_action( 'wm_k9_zulassung_checked', $result, $birth_date, $exam_date );
		}

		return $result;
	}
}
