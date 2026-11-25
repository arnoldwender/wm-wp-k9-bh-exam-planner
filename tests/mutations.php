<?php
/**
 * Literal mutations for tests/run-mutations.sh: each entry undoes one correction of 2026-09-15; the
 * standalone suite must report a failure for every one. Search strings must occur exactly once.
 */

return [
	[
		'D3 impossible dates roll over again',
		[
			[ 'includes/class-k9-admission-validator.php', 'if ( false === $date || $date->format( \'Y-m-d\' ) !== $value ) {', 'if ( false === $date ) {' ],
		],
	],
	[
		'D4 parsed dates carry the time of day again',
		[
			[ 'includes/class-k9-admission-validator.php', 'createFromFormat( \'!Y-m-d\', $value )', 'createFromFormat( \'Y-m-d\', $value )' ],
		],
	],
	[
		'D4 age from DateTime::diff() again',
		[
			[ 'includes/class-k9-admission-validator.php', "\t\tif ( (int) \$birth->format( 'j' ) > (int) \$exam->format( 'j' ) ) {\n\t\t\t--\$months;\n\t\t}\n\t\treturn \$months;", "\t\t\$diff = \$birth->diff( \$exam );\n\t\treturn ( \$diff->y * 12 ) + \$diff->m;" ],
		],
	],
	[
		'D5 exam before birth reported as too young again',
		[
			[ 'includes/class-k9-admission-validator.php', '} elseif ( $exam < $dob ) {', '} elseif ( false ) {' ],
		],
	],
	[
		'D6 unreadable date hides the other reasons again',
		[
			[ 'includes/class-k9-admission-validator.php', "\t\t\t\$errors[] = 'Ungültiges Datum für Geburtsdatum oder Prüfungsdatum (ein existierendes Datum im Format JJJJ-MM-TT erforderlich).';", "\t\t\treturn [ 'eligible' => false, 'age_months' => 0, 'errors' => [ 'Ungültiges Datum für Geburtsdatum oder Prüfungsdatum (ein existierendes Datum im Format JJJJ-MM-TT erforderlich).' ] ];" ],
		],
	],
	[
		'D1 routes without args again',
		[
			[ 'includes/class-k9-rest-controller.php', "\t\t\t'args'                => \$args['zulassung'],\n", '' ],
			[ 'includes/class-k9-rest-controller.php', "\t\t\t'args'                => \$args['bewertung'],\n", '' ],
		],
	],
	[
		'D1 booleans typed as string',
		[
			[ 'includes/class-k9-rest-controller.php', "\t\t\t'type'        => 'boolean',", "\t\t\t'type'        => 'string'," ],
		],
	],
	[
		'D2 points typed as string (decimal comma passes)',
		[
			[ 'includes/class-k9-rest-controller.php', "\t\t\t\t'type'        => 'number',", "\t\t\t\t'type'        => 'string'," ],
		],
	],
	[
		'D2 points without maximum (silently clamped)',
		[
			[ 'includes/class-k9-rest-controller.php', "\t\t\t\t'maximum'     => \$exercise['max'],\n", '' ],
		],
	],
	[
		'D1 birth_date not required',
		[
			[ 'includes/class-k9-rest-controller.php', '\'birth_date\' => [ \'type\' => \'string\', \'required\' => true,', '\'birth_date\' => [ \'type\' => \'string\',' ],
		],
	],
	[
		'D1 array parameters cast to string again',
		[
			[ 'includes/class-k9-rest-controller.php', 'return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : \'\';', 'return sanitize_text_field( (string) $value );' ],
		],
	],
	[
		'D2 array points cast to float again',
		[
			[ 'includes/class-k9-rest-controller.php', '$scores[ $key ] = is_numeric( $value ) ? (float) $value : 0.0;', '$scores[ $key ] = (float) $value;' ],
		],
	],
	[
		'D18 shortcode attributes typed array again (fatal before WP 6.5)',
		[
			[ 'includes/class-k9-shortcodes.php', 'public static function render_admission_check( $atts = [] ): string {', 'public static function render_admission_check( array $atts = [] ): string {' ],
		],
	],
	[
		'D7 score fields pre-filled with full marks again',
		[
			[ 'includes/class-k9-shortcodes.php', '$raw            = $submitted ? self::posted( $name ) : \'\';', '$raw            = $submitted ? self::posted( $name ) : (string) (int) WM_K9_Scoring_Engine::EXERCISES[ $key ][\'max\'];' ],
		],
	],
	[
		'D7 Teil B pre-ticked again',
		[
			[ 'includes/class-k9-shortcodes.php', '$teil_b = $submitted && \'\' !== self::posted( \'k9_b\' );', '$teil_b = ! $submitted || \'\' !== self::posted( \'k9_b\' );' ],
		],
	],
	[
		'D7 exercise fields not required',
		[
			[ 'includes/class-k9-shortcodes.php', ' required style="padding:6px;', ' style="padding:6px;' ],
		],
	],
	[
		'D13 form maxima hardcoded',
		[
			[ 'includes/class-k9-shortcodes.php', 'max="\' . esc_attr( $max ) . \'"', 'max="60"' ],
		],
	],
	[
		'D7 admission form forgets the date of birth',
		[
			[ 'includes/class-k9-shortcodes.php', 'name="wm_k9_dob" value="\' . esc_attr( $dob ) . \'"', 'name="wm_k9_dob" value=""' ],
		],
	],
	[
		'D8 default date in UTC again',
		[
			[ 'includes/class-k9-shortcodes.php', 'return current_datetime()->format( \'Y-m-d\' );', 'return gmdate( \'Y-m-d\' );' ],
		],
	],
	[
		'D15 chip field maxlength 15 again',
		[
			[ 'includes/class-k9-shortcodes.php', 'maxlength="32"', 'maxlength="15"' ],
		],
	],
	[
		'Posted values not unslashed',
		[
			[ 'includes/class-k9-shortcodes.php', 'return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );', 'return sanitize_text_field( $_POST[ $key ] );' ],
		],
	],
	[
		'D8b pre-check result grants admission again',
		[
			[ 'includes/class-k9-shortcodes.php', 'VORABPRÜFUNG BESTANDEN: Der Hund erfüllt', 'ZULASSUNG ERTEILT: Der Hund erfüllt' ],
		],
	],
	[
		'D11 error text contrast 3.62:1 again',
		[
			[ 'includes/class-k9-shortcodes.php', 'private const COLOR_ERROR_TEXT = \'#fecaca\';', 'private const COLOR_ERROR_TEXT = \'#f87171\';' ],
		],
	],
	[
		'D11 check button contrast 4.10:1 again',
		[
			[ 'includes/class-k9-shortcodes.php', 'private const COLOR_BUTTON_ADM = \'#0369a1\';', 'private const COLOR_BUTTON_ADM = \'#0284c7\';' ],
		],
	],
	[
		'D11 score button contrast 3.77:1 again',
		[
			[ 'includes/class-k9-shortcodes.php', 'private const COLOR_BUTTON_SC  = \'#047857\';', 'private const COLOR_BUTTON_SC  = \'#059669\';' ],
		],
	],
	[
		'D13 age literal in the form intro',
		[
			[ 'includes/class-k9-shortcodes.php', '\'Prüft vorab: Mindestalter %d Monate am Prüfungstag,', '\'Prüft vorab: Mindestalter 15 Monate am Prüfungstag,' ],
		],
	],
	[
		'D7b CLI success grants admission again',
		[
			[ 'includes/class-k9-cli.php', '\'Vorabprüfung bestanden: Hund ist %d Monate alt und erfüllt die drei geprüften Kriterien. Über die Zulassung entscheidet der ausrichtende Verein.\'', '\'Zulassung ERTEILT! Hund ist %d Monate alt und erfüllt alle Kriterien.\'' ],
		],
	],
	[
		'D9 health literal HEALTHY again',
		[
			[ 'includes/class-k9-spoke-adapter.php', 'return $this->self_test_passes() ? \'HEALTHY\' : \'ERROR\';', 'return \'HEALTHY\';' ],
		],
	],
	[
		'D9 telemetry status literal ACTIVE again',
		[
			[ 'includes/class-k9-spoke-adapter.php', '\'status\'      => $this->get_health_status(),', '\'status\'      => \'ACTIVE\',' ],
		],
	],
	[
		'D16 action message HTML-escaped again',
		[
			[ 'includes/class-k9-spoke-adapter.php', 'sprintf( \'Unbekannte Spoke-Aktion "%s".\', $action )', 'sprintf( \'Unbekannte Spoke-Aktion "%s".\', esc_html( $action ) )' ],
		],
	],
	[
		'D14 sum rounded before the comparison again',
		[
			[ 'includes/class-k9-scoring-engine.php', '$teil_a_passed = $sum >= self::PASSING_POINTS_TEIL_A;', '$teil_a_passed = round( $sum, 1 ) >= self::PASSING_POINTS_TEIL_A;' ],
		],
	],
	[
		'D14 float noise decides the pass',
		[
			[ 'includes/class-k9-scoring-engine.php', "\t\t\$sum           = round( \$sum, 6 );\n", '' ],
		],
	],
	[
		'D14 hundredths hidden in the display',
		[
			[ 'includes/class-k9-scoring-engine.php', '$decimals = 0.0 === fmod( round( $points * 10, 6 ), 1.0 ) ? 1 : 2;', '$decimals = 1;' ],
		],
	],
	[
		'D12 reason with a decimal point again',
		[
			[ 'includes/class-k9-scoring-engine.php', "\t\t\t\tself::format_points( \$total_a ),\n", "\t\t\t\tnumber_format( \$total_a, 1, '.', '' ),\n" ],
		],
	],
	[
		'Disqualification without reason asserts a PO violation again',
		[
			[ 'includes/class-k9-scoring-engine.php', '\'kein Grund angegeben\'', '\'Verstoß gegen Prüfungsordnung\'' ],
		],
	],
	[
		'D6 admin says both calculators run on WP-CLI again',
		[
			[ 'includes/class-k9-admin-dashboard.php', 'Beide laufen als Shortcode auf einer Seite und als REST-Route; die Zulassungsprüfung zusätzlich als WP-CLI-Befehl.', 'Beide laufen als Shortcode auf einer Seite, als REST-Route und als WP-CLI-Befehl.' ],
		],
	],
	[
		'D10 es_ES catalog double-encoded again',
		[
			[ 'languages/wm-k9-bh-exam-planner-es_ES.po', 'msgstr "Admisión"', 'msgstr "AdmisiÃ³n"' ],
		],
	],
	[
		'D-UNINSTALL deletes data unconditionally without opt-in again',
		[
			[ 'uninstall.php', 'if ( ! in_array( get_option( \'wm_k9_delete_data_on_uninstall\', \'0\' ), [ 1, \'1\', true ], true ) ) {', 'if ( false ) {' ],
		],
	],
	[
		'REST rate-limiting bypassed on threshold reach',
		[
			[ 'includes/class-k9-rest-controller.php', 'if ( $count >= $limit ) {', 'if ( false ) {' ],
		],
	],
	[
		'REST rate-limiting leaks raw client IP instead of HMAC digest',
		[
			[ 'includes/class-k9-rest-controller.php', 'substr( hash_hmac( \'sha256\', $raw_ip, $salt ), 0, 24 )', '$raw_ip' ],
		],
	],
	[
		'i18n load_plugin_textdomain skipped on init',
		[
			[ 'wm-k9-bh-exam-planner.php', "\tload_plugin_textdomain(", "\tif ( false ) load_plugin_textdomain(" ],
		],
	],
	[
		'wm_k9_zulassung_result filter dropped',
		[
			[ 'includes/class-k9-admission-validator.php', '\'wm_k9_zulassung_result\',', '\'wm_k9_zulassung_disabled\',' ],
		],
	],
	[
		'wm_k9_bewertung_result filter dropped',
		[
			[ 'includes/class-k9-scoring-engine.php', '\'wm_k9_bewertung_result\'', '\'wm_k9_bewertung_disabled\'' ],
		],
	],
	[
		'WP 6.5+ Block Bindings source wm-k9/exam-info unregistered',
		[
			[ 'includes/class-k9-ecosystem-bridges.php', "\t\tregister_block_bindings_source(", "\t\tif ( false ) register_block_bindings_source(" ],
		],
	],
	[
		'GenerateBlocks 2.x Dynamic Tag wm_k9_info unregistered',
		[
			[ 'includes/class-k9-ecosystem-bridges.php', "\t\tnew \\GenerateBlocks_Register_Dynamic_Tag(", "\t\tif ( false ) new \\GenerateBlocks_Register_Dynamic_Tag(" ],
		],
	],
	[
		'uninstall.php deletes an option no version of the plugin writes again',
		[
			[ 'uninstall.php', "delete_option( 'wm_k9_delete_data_on_uninstall' );", "delete_option( 'wm_k9_planner_settings' );\ndelete_option( 'wm_k9_delete_data_on_uninstall' );" ],
		],
	],
];
