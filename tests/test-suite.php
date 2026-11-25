<?php
/**
 * WM K9 BH Exam Planner — Zero-Dependency Standalone Test Suite
 *
 * Covers, without WordPress:
 * - the admission pre-check: age in calendar months (month ends, time of day), impossible dates, exam before
 *   birth, every reason listed, transponder digit count, SKN as declared
 * - the score calculator: clamps, comparison before rounding, German number format, disqualification
 * - the REST routes: boolean and number schemas validated the way core validates them, form strings
 * - the shortcodes: WordPress < 6.5 attributes, empty defaults, submitted values kept, site-local date, contrast
 * - the WP-CLI check, the hub adapter (health from the self-test), the gettext catalogs
 *
 * Values are the ones fixed in the code (FCI-PO 2019 layout); no conformity with a rule edition is tested.
 *
 * @package WM_K9_BH_Exam_Planner
 */

declare(strict_types=1);

namespace ArnoldWender\SuiteHub\Contracts {
	if ( ! interface_exists( 'ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface' ) ) {
		interface WM_Plugin_Module_Interface {
			public function get_module_slug(): string;
			public function get_name(): string;
			public function get_version(): string;
			public function get_description(): string;
			public function get_health_status(): string;
			public function get_sbom_data(): array;
		}
	}
}

namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/../' );
	}

	$GLOBALS['k9_filters']       = [];
	$GLOBALS['k9_actions_fired'] = [];
	$GLOBALS['k9_options']       = [];
	$GLOBALS['k9_transients']    = [];
	$GLOBALS['k9_settings']      = [];
	$GLOBALS['k9_textdomains']   = [];
	$GLOBALS['k9_bindings']      = [];

	function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		$GLOBALS['k9_filters'][ $hook ][ $priority ][] = [ $callback, $accepted_args ];
		return true;
	}
	function remove_all_filters( string $hook ): bool {
		unset( $GLOBALS['k9_filters'][ $hook ] );
		return true;
	}
	function apply_filters( string $hook, mixed $value, mixed ...$args ): mixed {
		if ( empty( $GLOBALS['k9_filters'][ $hook ] ) ) {
			return $value;
		}
		ksort( $GLOBALS['k9_filters'][ $hook ] );
		$all = array_merge( [ $value ], $args );
		foreach ( $GLOBALS['k9_filters'][ $hook ] as $callbacks ) {
			foreach ( $callbacks as [ $cb, $accepted ] ) {
				$all[0] = $cb( ...array_slice( $all, 0, max( 1, $accepted ) ) );
			}
		}
		return $all[0];
	}
	function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		return add_filter( $hook, $callback, $priority, $accepted_args );
	}
	function do_action( string $hook, mixed ...$args ): void {
		$GLOBALS['k9_actions_fired'][ $hook ][] = $args;
		if ( empty( $GLOBALS['k9_filters'][ $hook ] ) ) {
			return;
		}
		ksort( $GLOBALS['k9_filters'][ $hook ] );
		foreach ( $GLOBALS['k9_filters'][ $hook ] as $callbacks ) {
			foreach ( $callbacks as [ $cb, $accepted ] ) {
				$cb( ...array_slice( $args, 0, $accepted ) );
			}
		}
	}
	function add_shortcode( ...$args ) {}
	function is_admin(): bool { return true; }
	function current_user_can( string $cap ): bool { return true; }
	function plugin_dir_path( string $file ): string { return rtrim( dirname( $file ), '/\\' ) . '/'; }
	function plugin_basename( string $file ): string { return basename( dirname( $file ) ) . '/' . basename( $file ); }
	function load_plugin_textdomain( string $domain, $deprecated = false, string $plugin_rel_path = '' ): bool {
		$GLOBALS['k9_textdomains'][ $domain ] = $plugin_rel_path;
		return true;
	}
	function __( string $text, string $domain = 'default' ): string { return $text; }
	function esc_html__( string $text, string $domain = 'default' ): string { return esc_html( $text ); }
	function esc_html_e( string $text, string $domain = 'default' ): void { echo esc_html( $text ); }
	function esc_attr_e( string $text, string $domain = 'default' ): void { echo esc_attr( $text ); }
	function sanitize_key( string $key ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ) ?? ''; }
	function wp_salt( string $scheme = 'auth' ): string { return 'k9-unit-salt-' . $scheme; }
	function get_option( string $option, mixed $default = false ): mixed {
		return array_key_exists( $option, $GLOBALS['k9_options'] ) ? $GLOBALS['k9_options'][ $option ] : $default;
	}
	function update_option( string $option, mixed $value, $autoload = null ): bool {
		$GLOBALS['k9_options'][ $option ] = $value;
		return true;
	}
	function delete_option( string $option ): bool {
		unset( $GLOBALS['k9_options'][ $option ] );
		return true;
	}
	function get_transient( string $transient ): mixed {
		return $GLOBALS['k9_transients'][ $transient ] ?? false;
	}
	function set_transient( string $transient, mixed $value, int $expiration = 0 ): bool {
		$GLOBALS['k9_transients'][ $transient ] = $value;
		return true;
	}
	function delete_transient( string $transient ): bool {
		unset( $GLOBALS['k9_transients'][ $transient ] );
		return true;
	}
	function register_setting( string $option_group, string $option_name, array $args = [] ): void {
		$GLOBALS['k9_settings'][ $option_name ] = [ 'group' => $option_group, 'args' => $args ];
	}
	function settings_fields( string $option_group ): void {}
	function register_block_bindings_source( string $source_name, array $source_properties ): bool {
		$GLOBALS['k9_bindings'][ $source_name ] = $source_properties;
		return true;
	}
	function get_block_bindings_source( string $source_name ): ?array {
		return $GLOBALS['k9_bindings'][ $source_name ] ?? null;
	}

	class WP_Error {
		public function __construct(
			public string $code = '',
			public string $message = '',
			public mixed $data = null
		) {}
	}
	class GenerateBlocks_Register_Dynamic_Tag {
		/** @var array<string, array<string, mixed>> */
		public static array $tags = [];
		public function __construct( array $args ) {
			if ( isset( $args['tag'] ) && is_string( $args['tag'] ) ) {
				self::$tags[ $args['tag'] ] = $args;
			}
		}
	}

	function esc_attr( $str ) { return htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' ); }
	function esc_html( $str ) { return htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' ); }
	// Core strips tags and line breaks and trims; a non-string is a TypeError there too (strict callers).
	function sanitize_text_field( $str ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $str ) ) ); }
	function wp_unslash( $value ) { return is_string( $value ) ? stripslashes( $value ) : $value; }

	// current_datetime(): "now" in the site's time zone. The suite fixes the instant and the zone.
	$GLOBALS['k9_now'] = time();
	$GLOBALS['k9_tz']  = 'Europe/Berlin';
	function current_datetime(): DateTimeImmutable {
		return ( new DateTimeImmutable( '@' . $GLOBALS['k9_now'] ) )->setTimezone( new DateTimeZone( $GLOBALS['k9_tz'] ) );
	}

	// Copied from core (wp-includes/rest-api.php, WP 7.1): rest_sanitize_boolean() and rest_is_boolean().
	function rest_sanitize_boolean( $value ) {
		if ( is_string( $value ) ) {
			$value = strtolower( $value );
			if ( in_array( $value, [ 'false', '0' ], true ) ) {
				$value = false;
			}
		}
		return (bool) $value;
	}
	function rest_is_boolean( $maybe_bool ) {
		if ( is_bool( $maybe_bool ) ) {
			return true;
		}
		if ( is_string( $maybe_bool ) ) {
			return in_array( strtolower( $maybe_bool ), [ 'false', 'true', '0', '1' ], true );
		}
		if ( is_int( $maybe_bool ) ) {
			return in_array( $maybe_bool, [ 0, 1 ], true );
		}
		return false;
	}
	/**
	 * The part of core's rest_validate_value_from_schema() these routes use (types boolean, number, string;
	 * inclusive minimum and maximum), same checks in the same order. Returns true or an error code.
	 */
	function k9_validate_like_core( $value, array $schema ) {
		switch ( $schema['type'] ?? '' ) {
			case 'boolean':
				return rest_is_boolean( $value ) ? true : 'rest_invalid_type';
			case 'number':
				if ( ! is_numeric( $value ) ) {
					return 'rest_invalid_type';
				}
				if ( isset( $schema['minimum'] ) && $value < $schema['minimum'] ) {
					return 'rest_out_of_bounds';
				}
				if ( isset( $schema['maximum'] ) && $value > $schema['maximum'] ) {
					return 'rest_out_of_bounds';
				}
				return true;
			case 'string':
				return is_string( $value ) ? true : 'rest_invalid_type';
		}
		return 'no_type';
	}

	$GLOBALS['k9_routes'] = [];
	function register_rest_route( string $namespace, string $route, array $args ) {
		$GLOBALS['k9_routes'][ $namespace . $route ] = $args;
		return true;
	}

	class WP_REST_Request {
		public function __construct( private array $params = [] ) {}
		public function get_param( string $key ) { return $this->params[ $key ] ?? null; }
	}
	class WP_REST_Response {
		public function __construct( public $data = null, public int $status = 200 ) {}
		public function get_data() { return $this->data; }
	}

	require_once __DIR__ . '/../wm-k9-bh-exam-planner.php';
	require_once __DIR__ . '/../includes/class-k9-spoke-adapter.php';
	require_once __DIR__ . '/../includes/class-k9-cli.php';

	// ---------------------------------------------------------------------------
	// Test Execution Harness
	// ---------------------------------------------------------------------------

	$total_tests  = 0;
	$passed_tests = 0;
	$failed_tests = 0;

	function assert_true( bool $condition, string $description ): void {
		global $total_tests, $passed_tests, $failed_tests;
		++$total_tests;
		if ( $condition ) {
			++$passed_tests;
			echo "  [PASS] {$description}\n";
		} else {
			++$failed_tests;
			echo "  [FAIL] {$description}\n";
		}
	}

	function assert_equals( mixed $expected, mixed $actual, string $description ): void {
		global $total_tests, $passed_tests, $failed_tests;
		++$total_tests;
		if ( $expected === $actual ) {
			++$passed_tests;
			echo "  [PASS] {$description}\n";
		} else {
			++$failed_tests;
			echo "  [FAIL] {$description} — Expected: " . var_export( $expected, true ) . ', Got: ' . var_export( $actual, true ) . "\n";
		}
	}

	/** WCAG 2.x contrast ratio of two #rrggbb colours. */
	function k9_contrast( string $a, string $b ): float {
		$lum = static function ( string $hex ): float {
			$c = array_map( static fn( string $h ): float => hexdec( $h ) / 255, str_split( ltrim( $hex, '#' ), 2 ) );
			$c = array_map( static fn( float $x ): float => $x <= 0.03928 ? $x / 12.92 : ( ( $x + 0.055 ) / 1.055 ) ** 2.4, $c );
			return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
		};
		$l = [ $lum( $a ), $lum( $b ) ];
		rsort( $l );
		return ( $l[0] + 0.05 ) / ( $l[1] + 0.05 );
	}

	/** msgid => msgstr of a compiled .mo file (little endian, as msgfmt writes it). */
	function k9_read_mo( string $path ): array {
		$d = (string) file_get_contents( $path );
		[ , , $n, $o, $t ] = array_values( unpack( 'V5', substr( $d, 0, 20 ) ) );
		$out = [];
		for ( $i = 0; $i < $n; $i++ ) {
			[ $l1, $a1 ] = array_values( unpack( 'V2', substr( $d, $o + 8 * $i, 8 ) ) );
			[ $l2, $a2 ] = array_values( unpack( 'V2', substr( $d, $t + 8 * $i, 8 ) ) );
			$out[ substr( $d, $a1, $l1 ) ] = substr( $d, $a2, $l2 );
		}
		return $out;
	}

	$chip = '276098100123456';

	echo "==============================================================================\n";
	echo "  WM K9 BH EXAM PLANNER — STANDALONE TEST SUITE\n";
	echo "==============================================================================\n\n";

	// --- Test Group 1: age in calendar months ---
	echo "[TEST GROUP 1] Age on the exam day, 15-month floor, month ends\n";

	$res_eligible_age = WM_K9_Admission_Validator::check_eligibility( '2024-05-01', '2025-10-01', $chip, true );
	assert_true( $res_eligible_age['eligible'], 'Admits dog aged 17 months' );
	assert_equals( 17, $res_eligible_age['age_months'], 'Computes 17 months' );
	assert_true( ! WM_K9_Admission_Validator::check_eligibility( '2025-01-01', '2025-10-01', $chip, true )['eligible'], 'Rejects a 9-month-old dog' );
	assert_equals( 15, WM_K9_Admission_Validator::check_eligibility( '2024-07-01', '2025-10-01', $chip, true )['age_months'], 'Born 1 July: 15 months on 1 October' );
	assert_equals( 14, WM_K9_Admission_Validator::check_eligibility( '2024-07-01', '2025-09-30', $chip, true )['age_months'], 'Born 1 July: 14 months on 30 September' );
	$nov30 = WM_K9_Admission_Validator::check_eligibility( '2023-11-30', '2025-03-01', $chip, true );
	assert_true( $nov30['eligible'] && 15 === $nov30['age_months'], 'Born 30 November 2023: 15 months on 1 March 2025 (DateTime::diff said 14)' );
	assert_equals( 14, WM_K9_Admission_Validator::check_eligibility( '2023-11-30', '2025-02-28', $chip, true )['age_months'], 'Born 30 November 2023: 14 months on 28 February 2025' );
	assert_equals( 14, WM_K9_Admission_Validator::check_eligibility( '2024-01-31', '2025-04-30', $chip, true )['age_months'], 'Born 31 January 2024: 14 months on 30 April 2025' );
	assert_equals( 15, WM_K9_Admission_Validator::check_eligibility( '2024-01-31', '2025-05-01', $chip, true )['age_months'], 'Born 31 January 2024: 15 months on 1 May 2025' );
	assert_equals( 15, WM_K9_Admission_Validator::check_eligibility( '2024-02-29', '2025-05-29', $chip, true )['age_months'], 'Born 29 February 2024: 15 months on 29 May 2025' );
	$zero_time = WM_K9_Admission_Validator::parse_date( '2025-10-01' );
	assert_true( null !== $zero_time && '00:00:00' === $zero_time->format( 'H:i:s' ), 'Parsed dates carry no time of day (they used to copy the current time)' );

	// --- Test Group 2: transponder code ---
	echo "\n[TEST GROUP 2] Transponder code: 15 digits, format only\n";

	assert_true( WM_K9_Admission_Validator::check_eligibility( '2024-01-01', '2025-10-01', $chip, true )['eligible'], 'Accepts a 15-digit code' );
	assert_true( WM_K9_Admission_Validator::check_eligibility( '2024-01-01', '2025-10-01', '276 098 100 123 456', true )['eligible'], 'Accepts a 15-digit code written with spaces' );
	assert_true( ! WM_K9_Admission_Validator::check_eligibility( '2024-01-01', '2025-10-01', '123456', true )['eligible'], 'Rejects a 6-digit code' );

	// --- Test Group 3: handler Sachkundenachweis ---
	echo "\n[TEST GROUP 3] Handler Sachkundenachweis (SKN) as declared\n";

	assert_true( ! WM_K9_Admission_Validator::check_eligibility( '2024-01-01', '2025-10-01', $chip, false )['eligible'], 'Rejects a handler without SKN' );

	// --- Test Group 4: score calculator ---
	echo "\n[TEST GROUP 4] Teil A sum, pass threshold, Teil B\n";

	$scores_pass = [ 'leinenfuehrigkeit' => 14.0, 'freifolge' => 13.5, 'sitz_bewegung' => 9.0, 'ablegen_herankommen' => 8.5, 'ablegen_ablenkung' => 9.0 ];
	$eval_pass   = WM_K9_Scoring_Engine::evaluate( $scores_pass, true );
	assert_equals( 'BESTANDEN', $eval_pass['verdict'], 'A 54-point sheet with Teil B passed is BESTANDEN' );
	assert_equals( 54.0, $eval_pass['teil_a_points'], 'Sums Teil A to 54.0' );
	assert_true( $eval_pass['teil_a_passed'], 'Teil A passing flag is true' );

	$eval_fail = WM_K9_Scoring_Engine::evaluate( [ 'leinenfuehrigkeit' => 8.0, 'freifolge' => 7.0, 'sitz_bewegung' => 5.0, 'ablegen_herankommen' => 5.0, 'ablegen_ablenkung' => 5.0 ], true );
	assert_equals( 'NICHT_BESTANDEN', $eval_fail['verdict'], 'A 30-point sheet is NICHT_BESTANDEN' );
	assert_true( ! $eval_fail['teil_a_passed'], 'Teil A passing flag is false' );

	$just_under = WM_K9_Scoring_Engine::evaluate( [ 'leinenfuehrigkeit' => 14.98, 'freifolge' => 14.98, 'sitz_bewegung' => 4, 'ablegen_herankommen' => 4, 'ablegen_ablenkung' => 4 ], true );
	assert_true( ! $just_under['teil_a_passed'] && 41.96 === $just_under['teil_a_points'], '41.96 points fail and are reported as 41.96 (the sum was rounded to 42.0 before the comparison)' );
	assert_true( str_contains( $just_under['errors'][0] ?? '', '41,96 von 60,0' ), 'The reason shows 41,96, not a rounded 42,0' );
	$exactly = WM_K9_Scoring_Engine::evaluate( [ 'leinenfuehrigkeit' => 15, 'freifolge' => 15, 'sitz_bewegung' => 4, 'ablegen_herankommen' => 4, 'ablegen_ablenkung' => 4 ], true );
	assert_true( $exactly['teil_a_passed'], 'Exactly 42 points pass' );
	// 14.1 + 13.9 + 4.3 + 4.4 + 5.3 is 41.999999999999993 in floating point.
	$noise = WM_K9_Scoring_Engine::evaluate( [ 'leinenfuehrigkeit' => 14.1, 'freifolge' => 13.9, 'sitz_bewegung' => 4.3, 'ablegen_herankommen' => 4.4, 'ablegen_ablenkung' => 5.3 ], true );
	assert_true( $noise['teil_a_passed'] && 42.0 === $noise['teil_a_points'], 'A sum of 42 built from decimals passes despite float noise' );
	$b_failed = WM_K9_Scoring_Engine::evaluate( $scores_pass, false );
	assert_equals( [ 'Teil B (Verkehrsteil) nicht bestanden.' ], $b_failed['errors'], 'Teil B failed after Teil A passed is the only reason' );

	// --- Test Group 5: disqualification ---
	echo "\n[TEST GROUP 5] Disqualification\n";

	$eval_disq = WM_K9_Scoring_Engine::evaluate( $scores_pass, true, true, 'Aggressionsverhalten im Verkehrsteil' );
	assert_equals( 'DISQUALIFIZIERT', $eval_disq['verdict'], 'A disqualification gives DISQUALIFIZIERT' );
	assert_true( ! $eval_disq['teil_a_passed'], 'A disqualification resets the pass status' );
	$no_reason = WM_K9_Scoring_Engine::evaluate( $scores_pass, true, true, '' );
	assert_equals( [ 'Disqualifikation: kein Grund angegeben' ], $no_reason['errors'], 'Without a reason no violation of the Prüfungsordnung is asserted' );

	// --- Test Group 6: hub adapter ---
	echo "\n[TEST GROUP 6] Hub adapter: health from the self-test\n";

	$adapter = new WM_K9_Spoke_Adapter();
	assert_equals( 'wm-k9-bh-exam-planner', $adapter->get_module_slug(), 'Module slug' );
	assert_equals( 'WM K9 BH Exam Planner', $adapter->get_name(), 'Module name' );
	assert_equals( 'HEALTHY', $adapter->get_health_status(), 'HEALTHY while both calculators return their known values' );
	$broken = new WM_K9_Spoke_Adapter( static fn(): bool => false );
	assert_equals( 'ERROR', $broken->get_health_status(), 'ERROR when the self-test fails (the health was the literal HEALTHY)' );
	assert_equals( 'ERROR', $broken->get_telemetry_metrics()['status'], 'Telemetry status follows the self-test (it was the literal ACTIVE)' );
	assert_true( false === $broken->execute_action( 'self_test' )['success'], 'The self_test action reports the failure' );

	$sbom = $adapter->get_sbom_data();
	assert_equals( 'application', $sbom['type'], 'SBOM component type' );
	assert_equals( 'GPL-2.0-or-later', $sbom['licenses'][0]['license']['id'], 'SBOM licence id matches the header' );

	// --- Test Group 7: dates ---
	echo "\n[TEST GROUP 7] Impossible dates, exam before birth, every reason\n";

	foreach ( [ '2024-00-15', '2024-13-01', '2024-02-30', '24-5-1', '' ] as $bad_dob ) {
		$r = WM_K9_Admission_Validator::check_eligibility( $bad_dob, '2025-10-01', $chip, true );
		assert_true( ! $r['eligible'] && str_contains( $r['errors'][0] ?? '', 'Ungültiges Datum' ), "Birth date '{$bad_dob}' is refused (a roll-over used to make it a real date)" );
	}
	$bad_exam = WM_K9_Admission_Validator::check_eligibility( '2024-05-01', '2025-10-32', $chip, true );
	assert_true( ! $bad_exam['eligible'], 'Exam date 2025-10-32 is refused (it became 2025-11-01 and eligible)' );
	$before = WM_K9_Admission_Validator::check_eligibility( '2025-10-01', '2024-01-01', $chip, true );
	assert_true( [ 'Das Prüfungsdatum liegt vor dem Geburtsdatum.' ] === $before['errors'] && 0 === $before['age_months'], 'An exam before birth says so, with no age (it read "21 Monate alt")' );
	$all = WM_K9_Admission_Validator::check_eligibility( '', '2025-10-01', '12', false );
	assert_equals( 3, count( $all['errors'] ), 'An unreadable date still lists the chip and SKN reasons' );

	// --- Test Group 8: shortcodes on WordPress before 6.5 ---
	echo "\n[TEST GROUP 8] Shortcodes render, also with the empty-string attributes of WordPress < 6.5\n";

	foreach ( [ 'render_admission_check' => 'wm-k9-admission-box', 'render_judge_scorecard' => 'wm-k9-scorecard-box' ] as $method => $box ) {
		try {
			$html = WM_K9_Shortcodes::$method( '' );
			assert_true( str_contains( $html, $box ), "{$method}() renders with \$atts = '' (an array type was a fatal TypeError there)" );
		} catch ( Throwable $e ) {
			assert_true( false, "{$method}() renders with \$atts = '': " . get_class( $e ) );
		}
	}

	// --- Test Group 9: REST booleans and numbers ---
	echo "\n[TEST GROUP 9] REST: schemas reject what rest_sanitize_boolean() and (float) would misread\n";

	$adm = WM_K9_REST_Controller::handle_admission( new WP_REST_Request( [ 'birth_date' => '2023-01-01', 'exam_date' => '2025-10-01', 'microchip' => $chip, 'has_skn' => 'false' ] ) )->get_data();
	assert_true( false === $adm['data']['eligible'], 'has_skn=false (form string) makes the dog ineligible' );
	$full     = [ 'leinenfuehrigkeit' => '15', 'freifolge' => '15', 'sitz_bewegung' => '10', 'ablegen_herankommen' => '10', 'ablegen_ablenkung' => '10' ];
	$b_failed = WM_K9_REST_Controller::handle_scoring( new WP_REST_Request( $full + [ 'teil_b_passed' => 'false' ] ) )->get_data();
	assert_equals( 'NICHT_BESTANDEN', $b_failed['data']['verdict'], 'teil_b_passed=false with 60 points is NICHT_BESTANDEN' );
	$not_disq = WM_K9_REST_Controller::handle_scoring( new WP_REST_Request( $full + [ 'teil_b_passed' => 'true', 'disqualified' => 'false' ] ) )->get_data();
	assert_equals( 'BESTANDEN', $not_disq['data']['verdict'], 'disqualified=false does not disqualify' );
	$disq = WM_K9_REST_Controller::handle_scoring( new WP_REST_Request( $full + [ 'teil_b_passed' => '1', 'disqualified' => '1' ] ) )->get_data();
	assert_equals( 'DISQUALIFIZIERT', $disq['data']['verdict'], 'disqualified=1 still disqualifies' );

	WM_K9_REST_Controller::register_routes();
	$z = $GLOBALS['k9_routes']['wm-k9/v1/zulassung']['args'] ?? [];
	$b = $GLOBALS['k9_routes']['wm-k9/v1/bewertung']['args'] ?? [];
	foreach ( [ 'no', 'nein', 'off', ' false', 'n', [ 'false' ] ] as $wrong ) {
		$shown = is_array( $wrong ) ? '[false]' : "'{$wrong}'";
		assert_true( isset( $z['has_skn'] ) && true !== k9_validate_like_core( $wrong, $z['has_skn'] ), "has_skn={$shown} is rejected (rest_sanitize_boolean read it as true)" );
		assert_true( isset( $b['teil_b_passed'], $b['disqualified'] ) && true !== k9_validate_like_core( $wrong, $b['teil_b_passed'] ) && true !== k9_validate_like_core( $wrong, $b['disqualified'] ), "teil_b_passed / disqualified={$shown} are rejected" );
	}
	foreach ( [ 'true', 'false', '1', '0', true ] as $right ) {
		assert_true( isset( $z['has_skn'] ) && true === k9_validate_like_core( $right, $z['has_skn'] ), 'has_skn=' . var_export( $right, true ) . ' is accepted' );
	}
	assert_true( isset( $b['leinenfuehrigkeit'] ) && true !== k9_validate_like_core( '14,5', $b['leinenfuehrigkeit'] ), 'Points "14,5" are rejected (they became 14.0)' );
	assert_true( isset( $b['leinenfuehrigkeit'] ) && true === k9_validate_like_core( '14.5', $b['leinenfuehrigkeit'] ), 'Points "14.5" are accepted' );
	assert_true( isset( $b['leinenfuehrigkeit'] ) && true !== k9_validate_like_core( '16', $b['leinenfuehrigkeit'] ) && true !== k9_validate_like_core( '-1', $b['leinenfuehrigkeit'] ), 'Points outside 0-15 are rejected, not clamped silently' );
	assert_true( isset( $b['sitz_bewegung'] ) && true !== k9_validate_like_core( '10.5', $b['sitz_bewegung'] ), 'Maxima come from the exercise table (10 for Sitz)' );
	assert_true( isset( $z['birth_date'] ) && true !== k9_validate_like_core( [ '2024-01-01' ], $z['birth_date'] ) && ! empty( $z['birth_date']['required'] ), 'birth_date must be a string and is required (an array raised "Array to string conversion")' );
	$php_warnings = [];
	set_error_handler( static function ( int $no, string $msg ) use ( &$php_warnings ): bool {
		$php_warnings[] = $msg;
		return true;
	} );
	$arr = WM_K9_REST_Controller::handle_admission( new WP_REST_Request( [ 'birth_date' => [ 'x' ], 'exam_date' => '2025-10-01', 'microchip' => $chip, 'has_skn' => '1' ] ) )->get_data();
	$arr_points = WM_K9_REST_Controller::handle_scoring( new WP_REST_Request( [ 'leinenfuehrigkeit' => [ '15' ] ] + $full + [ 'teil_b_passed' => '1' ] ) )->get_data();
	restore_error_handler();
	assert_true( false === $arr['data']['eligible'] && [] === $php_warnings, 'An array that gets past the schema is treated as empty, without "Array to string conversion"' . ( $php_warnings ? ': ' . $php_warnings[0] : '' ) );
	assert_equals( 45.0, $arr_points['data']['teil_a_points'], 'Points sent as an array count as 0, not as 1.0' );

	// --- Test Group 10: hub adapter answers honestly ---
	echo "\n[TEST GROUP 10] Hub adapter: no blanket success, plain-text messages\n";

	$unknown = $adapter->execute_action( 'x"<b>' );
	assert_true( false === $unknown['success'], 'An unknown spoke action reports failure' );
	assert_equals( 'Unbekannte Spoke-Aktion "x"<b>".', $unknown['message'], 'The message is plain text; the hub escapes where it renders (it printed &quot;)' );
	assert_true( true === $adapter->execute_action( 'self_test' )['success'], 'self_test passes with the shipped calculators' );
	assert_true( ! array_key_exists( 'exams', $adapter->get_telemetry_metrics() ), 'Telemetry reports no exam count the plugin cannot know' );

	// --- Test Group 11: wording ---
	echo "\n[TEST GROUP 11] Admin screen, frontend and CLI wording\n";

	$emoji        = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{25CF}]/u';
	$admin_source = (string) file_get_contents( __DIR__ . '/../includes/class-k9-admin-dashboard.php' );
	assert_true( 0 === preg_match( '/wm-stat-value[^>]*>\s*0\s*</', $admin_source ), 'Admin screen shows no KPI tile fixed at 0' );
	assert_true( ! str_contains( $admin_source, 'here -->' ), 'Admin screen has no "Add … here" placeholder tabs' );
	assert_true( 0 === preg_match( $emoji, $admin_source ), 'Admin screen uses dashicons, not emoji' );
	assert_true( ! str_contains( $admin_source, 'als REST-Route und als WP-CLI-Befehl' ), 'Admin overview no longer says both calculators run as a WP-CLI command' );
	assert_true( str_contains( $admin_source, 'FCI-Prüfungsordnung 2019' ) && str_contains( $admin_source, 'Leinenführigkeit 30 Punkte' ), 'Admin screen names the rule edition of the layout and the 2025 difference' );

	$_POST    = [ 'wm_k9_check' => '1', 'wm_k9_dob' => '2025-01-01', 'wm_k9_exam' => '2025-10-01', 'wm_k9_chip' => '12', 'wm_k9_skn' => '' ];
	$adm_html = WM_K9_Shortcodes::render_admission_check();
	$_POST    = [ 'wm_k9_check' => '1', 'wm_k9_dob' => '2024-01-01', 'wm_k9_exam' => '2025-10-01', 'wm_k9_chip' => $chip, 'wm_k9_skn' => '1' ];
	$ok_html  = WM_K9_Shortcodes::render_admission_check();
	$_POST      = [ 'wm_k9_score_calc' => '1', 'k9_l' => '5', 'k9_f' => '5', 'k9_s' => '5', 'k9_h' => '5', 'k9_a' => '5', 'k9_b' => '1' ];
	$score_html = WM_K9_Shortcodes::render_judge_scorecard();
	$_POST      = [];
	$front      = $adm_html . $ok_html . $score_html;
	assert_true( 0 === preg_match( $emoji, $front ), 'Shortcode output, result messages included, carries no emoji' );
	assert_true( ! str_contains( $front, 'gemäß FCI' ) && ! str_contains( $front, 'FCI 2025' ) && ! str_contains( $front, 'ISO 11784/11785-Standard' ) && ! str_contains( $front, 'konform' ), 'No claim of conformity with a rule edition or ISO checksum' );
	assert_true( str_contains( $score_html, 'nach der FCI-PO 2019; seit dem 01.01.2025 gilt die FCI-PO 2025 mit anderer Aufteilung' ), 'The score form says which edition its layout follows and that the 2025 edition differs' );
	assert_true( str_contains( $adm_html, 'VORABPRÜFUNG NICHT BESTANDEN' ) && str_contains( $score_html, 'RECHNERISCH NICHT BESTANDEN' ) && substr_count( $front, '<svg' ) >= 5, 'Result messages keep their words and SVG icons' );
	assert_true( str_contains( $ok_html, 'VORABPRÜFUNG BESTANDEN' ) && ! str_contains( $front, 'ZULASSUNG ERTEILT' ), 'A passed pre-check grants no admission (it said "ZULASSUNG ERTEILT")' );
	$cli_ok = WM_K9_CLI::success_message( [ 'age_months' => 17 ] );
	assert_true( str_contains( $cli_ok, 'drei geprüften Kriterien' ) && ! str_contains( $cli_ok, 'alle Kriterien' ) && ! str_contains( $cli_ok, 'ERTEILT' ), 'The CLI success names the three checks and grants nothing' );

	// --- Test Group 12: score form defaults and submitted values ---
	echo "\n[TEST GROUP 12] Score form: empty by default, keeps what was submitted\n";

	$fresh = WM_K9_Shortcodes::render_judge_scorecard();
	assert_true( 0 === preg_match( '/name="k9_[lfsha]" value="[0-9]/', $fresh ), 'No exercise field is pre-filled (they came with 15/15/10/10/10)' );
	assert_true( 0 === preg_match( '/name="k9_b" value="1" checked/', $fresh ), 'Teil B is not pre-ticked' );
	$_POST     = [ 'wm_k9_score_calc' => '1', 'k9_l' => '12.5', 'k9_f' => '5', 'k9_s' => '5', 'k9_h' => '5', 'k9_a' => '5' ];
	$resubmit  = WM_K9_Shortcodes::render_judge_scorecard();
	$_POST     = [ 'wm_k9_score_calc' => '1' ];
	$untouched = WM_K9_Shortcodes::render_judge_scorecard();
	$_POST     = [];
	assert_true( str_contains( $resubmit, 'name="k9_l" value="12.5"' ) && 0 === preg_match( '/name="k9_b" value="1" checked/', $resubmit ), 'After a submit the form shows the entered values and Teil B as entered' );
	assert_true( str_contains( $untouched, 'RECHNERISCH NICHT BESTANDEN' ) && ! str_contains( $untouched, 'RECHNERISCH BESTANDEN:' ), 'An untouched submit is not a pass (it printed BESTANDEN 60/60)' );
	assert_true( 5 === substr_count( $fresh, ' required ' ), 'Every exercise field is required' );
	assert_true( str_contains( $fresh, 'max="15" name="k9_l"' ) && str_contains( $fresh, 'max="10" name="k9_s"' ), 'Form maxima come from the exercise table' );

	// --- Test Group 13: admission form ---
	echo "\n[TEST GROUP 13] Admission form: site-local date, submitted values kept, formatted chip codes fit\n";

	$GLOBALS['k9_now'] = ( new DateTimeImmutable( '2026-03-15 23:30:00', new DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$GLOBALS['k9_tz']  = 'Europe/Berlin';
	$night             = WM_K9_Shortcodes::render_admission_check();
	assert_true( str_contains( $night, 'name="wm_k9_exam" value="2026-03-16"' ), 'At 23:30 UTC on 15 March the Berlin date 2026-03-16 is pre-filled (it was the UTC date)' );
	assert_true( 1 === preg_match( '/name="wm_k9_chip" value="" maxlength="(\d+)"/', $night, $m ) && (int) $m[1] >= strlen( '276 098 100 123 456' ), 'The chip field takes a code written with spaces (maxlength was 15)' );
	$_POST = [ 'wm_k9_check' => '1', 'wm_k9_dob' => '2024-01-01', 'wm_k9_exam' => '2025-10-01', 'wm_k9_chip' => '276 098 100 123 456', 'wm_k9_skn' => '1' ];
	$kept  = WM_K9_Shortcodes::render_admission_check();
	$_POST = [ 'wm_k9_check' => '1', 'wm_k9_dob' => "2024-01-01\\'", 'wm_k9_exam' => '2025-10-01', 'wm_k9_chip' => $chip ];
	$slash = WM_K9_Shortcodes::render_admission_check();
	$_POST = [];
	assert_true( str_contains( $kept, 'name="wm_k9_dob" value="2024-01-01"' ) && str_contains( $kept, 'value="276 098 100 123 456"' ) && str_contains( $kept, 'name="wm_k9_skn" value="1" checked' ), 'After a submit the form keeps date of birth, chip and SKN' );
	assert_true( str_contains( $kept, 'VORABPRÜFUNG BESTANDEN' ), 'A formatted chip code passes through the form' );
	assert_true( str_contains( $slash, 'value="2024-01-01&#039;"' ), 'Posted values are unslashed before use' );

	// --- Test Group 14: WP-CLI check ---
	echo "\n[TEST GROUP 14] wp k9 check-eligibility defaults\n";

	$GLOBALS['k9_now'] = ( new DateTimeImmutable( '2025-09-30 22:30:00', new DateTimeZone( 'UTC' ) ) )->getTimestamp();
	$cli = WM_K9_CLI::eligibility( [ 'dob' => '2024-07-01', 'chip' => $chip, 'skn' => 'true' ] );
	assert_true( $cli['eligible'] && 15 === $cli['age_months'], 'Without --exam the site date (1 October in Berlin, 30 September in UTC) decides' );
	assert_true( ! WM_K9_CLI::eligibility( [ 'dob' => '2024-07-01', 'chip' => $chip, 'skn' => 'nein' ] )['eligible'], '--skn=nein counts as no certificate' );
	$GLOBALS['k9_now'] = time();

	// --- Test Group 15: one number format ---
	echo "\n[TEST GROUP 15] German number format in every result\n";

	$_POST = [ 'wm_k9_score_calc' => '1', 'k9_l' => '5', 'k9_f' => '5', 'k9_s' => '5', 'k9_h' => '5', 'k9_a' => '5' ];
	$fmt   = WM_K9_Shortcodes::render_judge_scorecard();
	$_POST = [];
	assert_true( str_contains( $fmt, '(25,0 Punkte)' ) && str_contains( $fmt, '25,0 von 60,0 Punkten' ) && ! str_contains( $fmt, '25.0' ), 'Heading and reason both use the decimal comma (the reason said 25.0 … 42.0)' );

	// --- Test Group 16: contrast ---
	echo "\n[TEST GROUP 16] Frontend colour pairs at 4.5:1 or more\n";

	$colors = ( new ReflectionClass( 'WM_K9_Shortcodes' ) )->getConstants();
	$pairs  = [
		'error text'   => [ $colors['COLOR_ERROR_TEXT'], $colors['COLOR_ERROR_BG'] ],
		'success text' => [ $colors['COLOR_OK_TEXT'], $colors['COLOR_OK_BG'] ],
		'check button' => [ '#ffffff', $colors['COLOR_BUTTON_ADM'] ],
		'score button' => [ '#ffffff', $colors['COLOR_BUTTON_SC'] ],
	];
	foreach ( $pairs as $what => [ $fg, $bg ] ) {
		$ratio = k9_contrast( $fg, $bg );
		assert_true( $ratio >= 4.5, sprintf( '%s %s on %s: %.2f:1', $what, $fg, $bg, $ratio ) );
	}
	$_POST = [ 'wm_k9_score_calc' => '1', 'k9_l' => '1' ];
	$page  = WM_K9_Shortcodes::render_judge_scorecard() . WM_K9_Shortcodes::render_admission_check();
	$_POST = [];
	assert_true( str_contains( $page, 'background:' . $colors['COLOR_ERROR_BG'] . ';color:' . $colors['COLOR_ERROR_TEXT'] ) && str_contains( $page, 'background:' . $colors['COLOR_BUTTON_ADM'] . ';color:#fff' ) && str_contains( $page, 'background:' . $colors['COLOR_BUTTON_SC'] . ';color:#fff' ), 'The rendered markup uses those colour constants' );

	// --- Test Group 17: gettext catalogs ---
	echo "\n[TEST GROUP 17] Catalogs: no double-encoded UTF-8, .mo matches .po\n";

	$po_files = glob( __DIR__ . '/../languages/*.po' ) ?: [];
	$mojibake = [];
	$mismatch = [];
	foreach ( $po_files as $po ) {
		$src = (string) file_get_contents( $po );
		if ( preg_match( '/Ã[\x{0080}-\x{00BF}]|Ð[\x{0080}-\x{00BF}]|Ä[\x{0080}-\x{00BF}]|Å[\x{0080}-\x{00BF}]/u', $src ) ) {
			$mojibake[] = basename( $po );
		}
		preg_match_all( '/^msgid "(.+)"\nmsgstr "(.*)"$/mu', $src, $pairs_po, PREG_SET_ORDER );
		$mo = k9_read_mo( substr( $po, 0, -3 ) . '.mo' );
		foreach ( $pairs_po as [ , $id, $str ] ) {
			if ( '' !== $str && ( $mo[ $id ] ?? null ) !== $str ) {
				$mismatch[] = basename( $po ) . ':' . $id;
			}
		}
	}
	assert_true( count( $po_files ) > 0 && [] === $mojibake, 'No catalog carries double-encoded UTF-8 (' . count( $po_files ) . ' checked; es_ES read "AdmisiÃ³n")' . ( $mojibake ? ': ' . implode( ', ', $mojibake ) : '' ) );
	assert_true( [] === $mismatch, 'Every compiled .mo holds the strings of its .po' . ( $mismatch ? ': ' . implode( ', ', array_slice( $mismatch, 0, 5 ) ) : '' ) );
	$es = k9_read_mo( __DIR__ . '/../languages/wm-k9-bh-exam-planner-es_ES.mo' );
	assert_equals( 'Admisión', $es['Zulassung'] ?? null, 'es_ES translates the admin tab "Zulassung" as "Admisión"' );

	// --- Test Group 18: values from one place ---
	echo "\n[TEST GROUP 18] Thresholds and maxima are not repeated as literals\n";

	$sources = [];
	foreach ( [ 'class-k9-shortcodes.php', 'class-k9-cli.php', 'class-k9-admin-dashboard.php', 'class-k9-spoke-adapter.php', 'class-k9-ecosystem-bridges.php' ] as $file ) {
		$sources[ $file ] = (string) file_get_contents( __DIR__ . '/../includes/' . $file );
	}
	$literal = [];
	foreach ( $sources as $file => $src ) {
		foreach ( [ '15 Monate', '15-stellig', 'max. 60', 'ab 42', '/ 60', 'max="15"', 'max="10"' ] as $needle ) {
			if ( str_contains( $src, $needle ) ) {
				$literal[] = "{$file}: {$needle}";
			}
		}
	}
	assert_true( [] === $literal, 'No UI or CLI text hardcodes the age, digits, maxima or threshold' . ( $literal ? ': ' . implode( '; ', $literal ) : '' ) );
	assert_equals( 60.0, array_sum( array_column( WM_K9_Scoring_Engine::EXERCISES, 'max' ) ), 'The exercise maxima add up to MAX_POINTS_TEIL_A' );
	assert_equals( WM_K9_Scoring_Engine::MAX_POINTS_TEIL_A * 0.7, WM_K9_Scoring_Engine::PASSING_POINTS_TEIL_A, 'The pass threshold is 70 % of the maximum (FCI-PO 2019 and 2025)' );

	// --- Test Group 19: opt-in uninstall (D-UNINSTALL) and admin setting ---
	echo "\n[TEST GROUP 19] Opt-in uninstall (D-UNINSTALL) preserves options unless explicitly enabled\n";

	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		define( 'WP_UNINSTALL_PLUGIN', true );
	}
	$GLOBALS['k9_options']    = [ 'wm_k9_delete_data_on_uninstall' => '0' ];
	$GLOBALS['k9_transients'] = [];
	include __DIR__ . '/../uninstall.php';
	assert_true(
		'0' === ( $GLOBALS['k9_options']['wm_k9_delete_data_on_uninstall'] ?? null ),
		'uninstall.php leaves the stored flag alone by default when wm_k9_delete_data_on_uninstall is off'
	);

	WM_K9_Admin_Dashboard::update_uninstall_flag( '1' );
	assert_equals( '1', get_option( 'wm_k9_delete_data_on_uninstall' ), 'update_uninstall_flag stores explicit opt-in' );
	include __DIR__ . '/../uninstall.php';
	assert_true(
		! isset( $GLOBALS['k9_options']['wm_k9_delete_data_on_uninstall'] ),
		'uninstall.php deletes the stored flag when wm_k9_delete_data_on_uninstall is enabled'
	);

	// Until 1.2.1 uninstall.php also deleted `wm_k9_planner_settings` and the transient `wm_k9_planner_cache`, which
	// no version of the plugin ever wrote (git log -S): every name it deletes must be one the plugin writes.
	preg_match_all( "/delete_(option|transient)\(\s*'([a-z0-9_]+)'/", (string) file_get_contents( __DIR__ . '/../uninstall.php' ), $k9_deleted, PREG_SET_ORDER );
	$k9_written_src = '';
	foreach ( array_merge( glob( __DIR__ . '/../includes/*.php' ) ?: [], [ __DIR__ . '/../wm-k9-bh-exam-planner.php' ] ) as $k9_src_file ) {
		$k9_written_src .= (string) file_get_contents( $k9_src_file );
	}
	$k9_never_written = [];
	foreach ( $k9_deleted as $k9_del ) {
		$k9_writer = 'option' === $k9_del[1] ? '(update_option|add_option|register_setting)' : 'set_transient';
		if ( ! preg_match( '/' . $k9_writer . "\([^;]*'" . preg_quote( $k9_del[2], '/' ) . "'/", $k9_written_src ) ) {
			$k9_never_written[] = $k9_del[1] . ' ' . $k9_del[2];
		}
	}
	assert_true(
		[] !== $k9_deleted && [] === $k9_never_written,
		'uninstall.php deletes only options and transients the plugin writes' . ( $k9_never_written ? ': ' . implode( ', ', $k9_never_written ) : '' )
	);

	do_action( 'admin_init' );
	assert_true(
		isset( $GLOBALS['k9_settings']['wm_k9_delete_data_on_uninstall'] )
		&& '0' === ( $GLOBALS['k9_settings']['wm_k9_delete_data_on_uninstall']['args']['default'] ?? null ),
		'Admin dashboard registers wm_k9_delete_data_on_uninstall with default 0'
	);

	// --- Test Group 20: REST rate-limiting and anonymised client digest ---
	echo "\n[TEST GROUP 20] REST rate-limiting uses HMAC digest and enforces HTTP 429\n";

	$GLOBALS['k9_transients'] = [];
	$_SERVER['REMOTE_ADDR']   = '203.0.113.77';
	$rl_key                   = WM_K9_REST_Controller::rate_limit_key( 'zulassung' );
	assert_true(
		str_starts_with( $rl_key, 'wm_k9_rl_zulassung_' ) && ! str_contains( $rl_key, '203.0.113.77' ),
		'Rate-limit transient key hashes the client IP with HMAC instead of storing plain IP'
	);
	$GLOBALS['k9_transients'][ $rl_key ] = WM_K9_REST_Controller::RATE_LIMIT_MAX;
	$perm_denied                         = WM_K9_REST_Controller::check_zulassung_access( new WP_REST_Request() );
	assert_true(
		$perm_denied instanceof WP_Error && 'wm_k9_rate_limited' === $perm_denied->code,
		'check_zulassung_access returns WP_Error wm_k9_rate_limited when threshold is reached'
	);
	$resp_429 = WM_K9_REST_Controller::handle_admission(
		new WP_REST_Request( [ 'birth_date' => '2023-01-01', 'exam_date' => '2025-10-01', 'microchip' => $chip, 'has_skn' => '1' ] )
	);
	assert_equals( 429, $resp_429->status, 'handle_admission returns HTTP 429 when rate limit is exceeded' );
	$other_bucket_ok = WM_K9_REST_Controller::handle_scoring( new WP_REST_Request( $full + [ 'teil_b_passed' => '1' ] ) );
	assert_equals( 200, $other_bucket_ok->status, 'bewertung uses an independent rate-limit bucket' );
	$GLOBALS['k9_transients'] = [];
	unset( $_SERVER['REMOTE_ADDR'] );

	// --- Test Group 21: i18n bootstrap, developer hooks, Block Bindings & GenerateBlocks ---
	echo "\n[TEST GROUP 21] Textdomain bootstrap, developer filters/actions, Block Bindings & GenerateBlocks\n";

	$GLOBALS['k9_textdomains']   = [];
	$GLOBALS['k9_actions_fired'] = [];
	wm_k9_bootstrap_i18n();
	assert_true(
		isset( $GLOBALS['k9_textdomains']['wm-k9-bh-exam-planner'] )
		&& str_ends_with( $GLOBALS['k9_textdomains']['wm-k9-bh-exam-planner'], '/languages' ),
		'wm_k9_bootstrap_i18n loads wm-k9-bh-exam-planner textdomain from /languages'
	);
	// The plugin's own constant, loaded from the main file above: a literal had to be edited on every release.
	assert_equals(
		[ [ WM_K9_PLANNER_VERSION ] ],
		$GLOBALS['k9_actions_fired']['wm_k9_ready'] ?? [],
		'wm_k9_ready action fires with the plugin version'
	);

	add_filter(
		'wm_k9_zulassung_result',
		static function ( array $res ): array {
			$res['errors'][] = 'Vereinsregel Zusatzprüfung';
			$res['eligible'] = false;
			return $res;
		},
		10,
		2
	);
	$filtered_adm = WM_K9_Admission_Validator::check_eligibility( '2024-05-01', '2025-10-01', $chip, true );
	remove_all_filters( 'wm_k9_zulassung_result' );
	assert_true(
		! $filtered_adm['eligible'] && in_array( 'Vereinsregel Zusatzprüfung', $filtered_adm['errors'], true ),
		'wm_k9_zulassung_result filter can customize admission pre-check results'
	);

	add_filter(
		'wm_k9_bewertung_result',
		static function ( array $res ): array {
			$res['verdict'] = 'SONDERWERTUNG';
			return $res;
		},
		10,
		2
	);
	$filtered_score = WM_K9_Scoring_Engine::evaluate( $scores_pass, true );
	remove_all_filters( 'wm_k9_bewertung_result' );
	assert_equals( 'SONDERWERTUNG', $filtered_score['verdict'], 'wm_k9_bewertung_result filter can customize scoring results' );

	$GLOBALS['k9_bindings']                  = [];
	GenerateBlocks_Register_Dynamic_Tag::$tags = [];
	WM_K9_Ecosystem_Bridges::init();
	WM_K9_Ecosystem_Bridges::register_block_bindings();
	WM_K9_Ecosystem_Bridges::register_generateblocks_tag();
	assert_true( isset( $GLOBALS['k9_bindings']['wm-k9/exam-info'] ), 'Registers WP 6.5+ Block Bindings source wm-k9/exam-info' );
	assert_equals( '15', WM_K9_Ecosystem_Bridges::get_block_binding_value( [ 'key' => 'min_age_months' ] ), 'Block Bindings resolves min_age_months from validator constant' );
	assert_equals( '42,0', WM_K9_Ecosystem_Bridges::get_block_binding_value( [ 'key' => 'passing_points_teil_a' ] ), 'Block Bindings resolves passing_points_teil_a in German format' );
	assert_true( isset( GenerateBlocks_Register_Dynamic_Tag::$tags['wm_k9_info'] ), 'Registers GenerateBlocks 2.x Dynamic Tag wm_k9_info' );
	assert_equals( 'FCI-PO 2019', WM_K9_Ecosystem_Bridges::render_generateblocks_tag( [ 'key' => 'rule_edition' ] ), 'GenerateBlocks Dynamic Tag renders rule_edition' );
	assert_equals( 'FCI-PO 2019', apply_filters( 'generateblocks_dynamic_tag_replacement', '', [ 'tag' => 'wm_k9_info', 'options' => [ 'key' => 'rule_edition' ] ] ), 'generateblocks_dynamic_tag_replacement resolves wm_k9_info' );
	assert_equals( 'Post Title', apply_filters( 'generateblocks_dynamic_tag_output', 'Post Title', [ 'source' => 'post' ], 'Post Title' ), 'generateblocks_dynamic_tag_output passes native scalar raw_output untouched' );
	assert_true( is_link( __DIR__ . '/../AGENTS.md' ), 'AGENTS.md is a symlink to CLAUDE.md' );
	assert_true( is_file( __DIR__ . '/../bom.json' ), 'CycloneDX 1.6 bom.json exists' );
	assert_true( is_file( __DIR__ . '/../.wordpress-org/blueprints/blueprint.json' ), 'WordPress Playground blueprint.json exists' );

	echo "\n==============================================================================\n";
	echo "  TEST SUITE RESULTS: {$passed_tests} PASSED | {$failed_tests} FAILED\n";
	echo "==============================================================================\n\n";

	exit( $failed_tests > 0 ? 1 : 0 );
}
