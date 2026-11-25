<?php
/**
 * WM K9 BH Exam Planner — Frontend Accessible Shortcodes
 *
 * Implements accessible, Zero-CDN frontend tools:
 * - [wm_k9_zulassungscheck]: Dog admission pre-check for the BH/VT exam.
 * - [wm_k9_richterbogen]: score calculator for Teil A with the Teil B result.
 *
 * @package WM_K9_BH_Exam_Planner
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WM_K9_Shortcodes {

	/*
	 * Inline colours, each pair at or above 4.5:1 (WCAG AA for normal text). Until 2026-09-15 the error text
	 * #f87171 on #7f1d1d was 3.62:1 and the white buttons on #059669 and #0284c7 3.77:1 and 4.10:1.
	 */
	private const COLOR_ERROR_TEXT = '#fecaca';
	private const COLOR_ERROR_BG   = '#7f1d1d';
	private const COLOR_OK_TEXT    = '#6ee7b7';
	private const COLOR_OK_BG      = '#064e3b';
	private const COLOR_BUTTON_ADM = '#0369a1';
	private const COLOR_BUTTON_SC  = '#047857';

	public static function init(): void {
		add_shortcode( 'wm_k9_zulassungscheck', [ self::class, 'render_admission_check' ] );
		add_shortcode( 'wm_k9_richterbogen', [ self::class, 'render_judge_scorecard' ] );
	}

	/**
	 * Decorative inline SVG (monochrome, currentColor, hidden from assistive technology).
	 * Replaced the emoji on 2026-09-14; the result messages keep their words, which carry the meaning.
	 */
	private static function icon( string $name ): string {
		$paths = [
			'check'   => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
			'cross'   => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/>',
			'paw'     => '<circle cx="7" cy="9" r="1.6"/><circle cx="11" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="18" cy="9.5" r="1.6"/><path d="M12 11c-3 0-5 3.2-5 5.4 0 1.7 1.3 2.6 2.8 2.6 1 0 1.5-.5 2.2-.5s1.2.5 2.2.5c1.5 0 2.8-.9 2.8-2.6C17 14.2 15 11 12 11Z"/>',
			'sheet'   => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 3v3h6V3M9 11h6M9 15h4"/>',
			'warning' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
		];
		return '<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="vertical-align:-0.125em;">' . ( $paths[ $name ] ?? '' ) . '</svg>';
	}

	/** A posted field as text, unslashed and sanitised; '' when absent or not a string. */
	private static function posted( string $key ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- stateless calculator: nothing is stored or changed.
		if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) ) {
			return '';
		}
		return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/** Today in the site's time zone. Until 2026-09-15 the UTC date, which is yesterday after midnight in Berlin. */
	public static function site_today(): string {
		return current_datetime()->format( 'Y-m-d' );
	}

	/**
	 * Render accessible admission checker shortcode.
	 *
	 * @param array|string $atts WordPress before 6.5 passes '' when the shortcode has no attributes; an `array`
	 *                           type made the page fatal there (Requires at least: 6.4).
	 */
	public static function render_admission_check( $atts = [] ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- stateless calculator.
		$submitted = isset( $_POST['wm_k9_check'] );
		$dob       = $submitted ? self::posted( 'wm_k9_dob' ) : '';
		$exam      = $submitted ? self::posted( 'wm_k9_exam' ) : self::site_today();
		$chip      = $submitted ? self::posted( 'wm_k9_chip' ) : '';
		$skn       = $submitted && '' !== self::posted( 'wm_k9_skn' );

		$output = '<div class="wm-k9-admission-box" style="background:#0f172a;color:#f8fafc;padding:24px;border-radius:12px;font-family:sans-serif;max-width:600px;margin:20px auto;border:1px solid #334155;">';
		$output .= '<h3 style="color:#38bdf8;margin-top:0;font-size:1.25rem;">' . self::icon( 'paw' ) . ' Zulassungs-Vorabprüfung (BH/VT)</h3>';
		// Until 2026-09-14: "gemäß FCI PO 2025 & ISO 11784/11785" — the code checks three fixed values.
		$output .= '<p style="color:#94a3b8;font-size:0.9rem;">' . esc_html( sprintf( 'Prüft vorab: Mindestalter %d Monate am Prüfungstag, %d-stelliger Transpondercode (nur das Format) und Sachkundenachweis des Hundeführers. Über die Zulassung entscheidet der ausrichtende Verein nach seiner Prüfungsordnung.', WM_K9_Admission_Validator::MIN_AGE_MONTHS, WM_K9_Admission_Validator::CHIP_DIGITS ) ) . '</p>';
		$output .= '<form method="post" style="display:grid;gap:12px;" aria-label="Zulassungsprüfung">';
		$output .= '<label style="display:grid;gap:4px;font-size:0.875rem;">Geburtsdatum des Hundes: <input type="date" name="wm_k9_dob" value="' . esc_attr( $dob ) . '" required style="padding:8px;border-radius:6px;border:1px solid #94a3b8;background:#1e293b;color:#fff;"></label>';
		$output .= '<label style="display:grid;gap:4px;font-size:0.875rem;">Prüfungsdatum: <input type="date" name="wm_k9_exam" value="' . esc_attr( $exam ) . '" required style="padding:8px;border-radius:6px;border:1px solid #94a3b8;background:#1e293b;color:#fff;"></label>';
		// maxlength 32: the validator ignores spaces and dashes; until 2026-09-15 maxlength 15 blocked "276 098 100 123 456".
		$output .= '<label style="display:grid;gap:4px;font-size:0.875rem;">' . esc_html( sprintf( 'Transponder / Mikrochip (%d-stellig):', WM_K9_Admission_Validator::CHIP_DIGITS ) ) . ' <input type="text" name="wm_k9_chip" value="' . esc_attr( $chip ) . '" maxlength="32" placeholder="276098101234567" required style="padding:8px;border-radius:6px;border:1px solid #94a3b8;background:#1e293b;color:#fff;"></label>';
		$output .= '<label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;"><input type="checkbox" name="wm_k9_skn" value="1"' . ( $skn ? ' checked' : '' ) . '> Sachkundenachweis (SKN) des Hundeführers liegt vor</label>';
		$output .= '<button type="submit" name="wm_k9_check" style="background:' . self::COLOR_BUTTON_ADM . ';color:#fff;padding:10px 16px;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Zulassung vorab prüfen</button>';
		$output .= '</form>';

		if ( $submitted ) {
			$result = WM_K9_Admission_Validator::check_eligibility( $dob, $exam, $chip, $skn );

			if ( $result['eligible'] ) {
				// Until 2026-09-15 "ZULASSUNG ERTEILT": the plugin grants nothing, the organising club admits.
				$output .= '<div style="margin-top:16px;padding:12px;background:' . self::COLOR_OK_BG . ';color:' . self::COLOR_OK_TEXT . ';border-radius:8px;font-weight:600;" role="alert">' . self::icon( 'check' ) . ' VORABPRÜFUNG BESTANDEN: Der Hund erfüllt die drei geprüften Kriterien (Alter: ' . (int) $result['age_months'] . ' Monate). Über die Zulassung entscheidet der ausrichtende Verein.</div>';
			} else {
				$output .= '<div style="margin-top:16px;padding:12px;background:' . self::COLOR_ERROR_BG . ';color:' . self::COLOR_ERROR_TEXT . ';border-radius:8px;" role="alert"><p style="margin:0 0 8px 0;font-weight:600;">' . self::icon( 'cross' ) . ' VORABPRÜFUNG NICHT BESTANDEN:</p><ul style="margin:0;padding-left:20px;">';
				foreach ( $result['errors'] as $err ) {
					$output .= '<li>' . esc_html( $err ) . '</li>';
				}
				$output .= '</ul></div>';
			}
		}

		$output .= '</div>';
		return $output;
	}

	/**
	 * Render score calculator shortcode.
	 *
	 * @param array|string $atts See render_admission_check().
	 */
	public static function render_judge_scorecard( $atts = [] ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- stateless calculator.
		$submitted = isset( $_POST['wm_k9_score_calc'] );
		$fields    = [
			'leinenfuehrigkeit'   => 'k9_l',
			'freifolge'           => 'k9_f',
			'sitz_bewegung'       => 'k9_s',
			'ablegen_herankommen' => 'k9_h',
			'ablegen_ablenkung'   => 'k9_a',
		];
		// Until 2026-09-15 the fields came pre-filled with full marks and Teil B ticked, so an untouched submit
		// printed "BESTANDEN 60/60", and after a submit the form showed those defaults instead of the entries.
		$values = [];
		foreach ( $fields as $key => $name ) {
			$raw            = $submitted ? self::posted( $name ) : '';
			$values[ $key ] = is_numeric( $raw ) ? $raw : '';
		}
		$teil_b = $submitted && '' !== self::posted( 'k9_b' );

		$output = '<div class="wm-k9-scorecard-box" style="background:#0f172a;color:#f8fafc;padding:24px;border-radius:12px;font-family:sans-serif;max-width:650px;margin:20px auto;border:1px solid #334155;">';
		$output .= '<h3 style="color:#10b981;margin-top:0;font-size:1.25rem;">' . self::icon( 'sheet' ) . ' Punkterechner — BH/VT</h3>';
		$output .= '<p style="color:#94a3b8;font-size:0.9rem;">' . esc_html( sprintf(
			'Berechnet Teil A (Unterordnung, max. %1$s Punkte, bestanden ab %2$s) und verbindet ihn mit dem Ergebnis von Teil B (Verkehrsteil). Übungen und Höchstpunkte nach der %3$s; seit dem 01.01.2025 gilt die FCI-PO 2025 mit anderer Aufteilung (Leinenführigkeit 30 Punkte, keine Freifolge). Kein amtlicher Richterbogen: das Ergebnis gibt der Leistungsrichter bekannt.',
			WM_K9_Scoring_Engine::format_points( WM_K9_Scoring_Engine::MAX_POINTS_TEIL_A ),
			WM_K9_Scoring_Engine::format_points( WM_K9_Scoring_Engine::PASSING_POINTS_TEIL_A ),
			WM_K9_Scoring_Engine::RULE_EDITION
		) ) . '</p>';
		$output .= '<form method="post" style="display:grid;gap:12px;" aria-label="Richterbewertung">';
		$n = 0;
		foreach ( $fields as $key => $name ) {
			$exercise = WM_K9_Scoring_Engine::EXERCISES[ $key ];
			$max      = (string) (int) $exercise['max'];
			++$n;
			$output .= '<label style="display:grid;gap:4px;font-size:0.875rem;">' . esc_html( sprintf( '%d. %s (max. %s p):', $n, $exercise['label'], $max ) ) . ' <input type="number" step="0.5" min="0" max="' . esc_attr( $max ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $values[ $key ] ) . '" required style="padding:6px;border-radius:6px;background:#1e293b;color:#fff;border:1px solid #94a3b8;"></label>';
		}
		$output .= '<label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;"><input type="checkbox" name="k9_b" value="1"' . ( $teil_b ? ' checked' : '' ) . '> Teil B: Verkehrssicherheit bestanden</label>';
		$output .= '<button type="submit" name="wm_k9_score_calc" style="background:' . self::COLOR_BUTTON_SC . ';color:#fff;padding:10px 16px;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Ergebnis berechnen</button>';
		$output .= '</form>';

		if ( $submitted ) {
			$scores = [];
			foreach ( $fields as $key => $name ) {
				$scores[ $key ] = '' === $values[ $key ] ? 0.0 : (float) $values[ $key ];
			}

			$eval   = WM_K9_Scoring_Engine::evaluate( $scores, $teil_b );
			$points = WM_K9_Scoring_Engine::format_points( $eval['teil_a_points'] );
			$max    = WM_K9_Scoring_Engine::format_points( WM_K9_Scoring_Engine::MAX_POINTS_TEIL_A );

			if ( 'BESTANDEN' === $eval['verdict'] ) {
				$output .= '<div style="margin-top:16px;padding:12px;background:' . self::COLOR_OK_BG . ';color:' . self::COLOR_OK_TEXT . ';border-radius:8px;font-weight:600;" role="alert">' . self::icon( 'check' ) . ' RECHNERISCH BESTANDEN: ' . esc_html( $points . ' / ' . $max ) . ' Punkte in Teil A, Teil B als bestanden angegeben.</div>';
			} else {
				$output .= '<div style="margin-top:16px;padding:12px;background:' . self::COLOR_ERROR_BG . ';color:' . self::COLOR_ERROR_TEXT . ';border-radius:8px;" role="alert"><p style="margin:0 0 8px 0;font-weight:600;">' . self::icon( 'warning' ) . ' RECHNERISCH NICHT BESTANDEN (' . esc_html( $points ) . ' Punkte):</p><ul style="margin:0;padding-left:20px;">';
				foreach ( $eval['errors'] as $err ) {
					$output .= '<li>' . esc_html( $err ) . '</li>';
				}
				$output .= '</ul></div>';
			}
		}

		$output .= '</div>';
		return $output;
	}
}
