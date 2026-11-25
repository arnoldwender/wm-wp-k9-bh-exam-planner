<?php
/**
 * Read-only runtime check of wm-k9-bh-exam-planner inside a real WordPress: REST schemas answer 400 through
 * core's own validation, the calculators return the corrected values, the shortcodes render without
 * pre-filled marks and with the site date, the hub adapter reports health, and the Spanish catalog decodes
 * through WordPress' MO parser. Writes nothing (REST requests are dispatched in-process; no option, row or
 * transient is touched), so it is safe on a shared install and on the demo Box.
 *
 *   wp eval-file wp-content/plugins/wm-k9-bh-exam-planner/tests/wp-runtime-k9.php --user=1
 *   ssh <box> 'wp --path=<www> eval-file - --user=1' < tests/wp-runtime-k9.php
 *
 * Exit 1 when a check fails. `wp eval-file` runs the file inside a method: state lives in $GLOBALS.
 *
 * @package WM_K9_BH_Exam_Planner
 */

$GLOBALS['k9rt'] = [ 'pass' => 0, 'fail' => 0 ];
$GLOBALS['k9rt_check'] = static function ( bool $ok, string $label ): void {
	$GLOBALS['k9rt'][ $ok ? 'pass' : 'fail' ]++;
	echo ( $ok ? '  [PASS] ' : '  [FAIL] ' ) . $label . "\n";
};
$GLOBALS['k9rt_post'] = static function ( string $route, array $params ): WP_REST_Response {
	$request = new WP_REST_Request( 'POST', $route );
	$request->set_body_params( $params );
	return rest_ensure_response( rest_do_request( $request ) );
};
$check = $GLOBALS['k9rt_check'];
$post  = $GLOBALS['k9rt_post'];

echo "\n=== WM K9 BH EXAM PLANNER · RUNTIME ===\n";
$check( defined( 'WM_K9_PLANNER_VERSION' ) && class_exists( 'WM_K9_REST_Controller' ), 'plugin loaded (version ' . ( defined( 'WM_K9_PLANNER_VERSION' ) ? WM_K9_PLANNER_VERSION : '-' ) . ')' );

// --- REST: core validation against the route schemas --------------------------------------------------
$chip = '276098100123456';
foreach ( [ 'nein', 'off', ' false' ] as $wrong ) {
	$r = $post( '/wm-k9/v1/zulassung', [ 'birth_date' => '2023-01-01', 'exam_date' => '2025-10-01', 'microchip' => $chip, 'has_skn' => $wrong ] );
	$check( 400 === $r->get_status() && 'rest_invalid_param' === ( $r->get_data()['code'] ?? '' ), "zulassung has_skn='{$wrong}' -> 400 rest_invalid_param (was 200 eligible)" );
}
$r = $post( '/wm-k9/v1/zulassung', [ 'birth_date' => '2023-01-01', 'exam_date' => '2025-10-01', 'microchip' => $chip, 'has_skn' => 'false' ] );
$check( 200 === $r->get_status() && false === ( $r->get_data()['data']['eligible'] ?? null ), 'zulassung has_skn=false -> 200, not eligible' );
$r = $post( '/wm-k9/v1/zulassung', [ 'exam_date' => '2025-10-01', 'microchip' => $chip, 'has_skn' => 'true' ] );
$check( 400 === $r->get_status(), 'zulassung without birth_date -> 400' );
$r = $post( '/wm-k9/v1/zulassung', [ 'birth_date' => '2023-11-30', 'exam_date' => '2025-03-01', 'microchip' => '276 098 100 123 456', 'has_skn' => '1' ] );
$check( true === ( $r->get_data()['data']['eligible'] ?? null ) && 15 === ( $r->get_data()['data']['age_months'] ?? null ), 'born 2023-11-30, trial 2025-03-01: 15 months, eligible (was 14)' );
$r = $post( '/wm-k9/v1/zulassung', [ 'birth_date' => '2024-00-15', 'exam_date' => '2025-03-15', 'microchip' => $chip, 'has_skn' => '1' ] );
$check( false === ( $r->get_data()['data']['eligible'] ?? null ), 'birth_date 2024-00-15 refused (rolled over to 2023-12-15 and eligible)' );

$sheet = [ 'leinenfuehrigkeit' => '14.5', 'freifolge' => '14.5', 'sitz_bewegung' => '4.5', 'ablegen_herankommen' => '4.5', 'ablegen_ablenkung' => '4.5', 'teil_b_passed' => 'true' ];
$r     = $post( '/wm-k9/v1/bewertung', $sheet );
$check( 'BESTANDEN' === ( $r->get_data()['data']['verdict'] ?? '' ) && 42.5 === (float) ( $r->get_data()['data']['teil_a_points'] ?? 0 ), 'bewertung 42.5 points with dots -> BESTANDEN' );
$r = $post( '/wm-k9/v1/bewertung', [ 'leinenfuehrigkeit' => '14,5' ] + $sheet );
$check( 400 === $r->get_status(), 'bewertung "14,5" -> 400 (was truncated to 14.0 and failed)' );
$r = $post( '/wm-k9/v1/bewertung', [ 'leinenfuehrigkeit' => '16' ] + $sheet );
$check( 400 === $r->get_status(), 'bewertung Leinenführigkeit 16 -> 400 (was clamped silently)' );
$r = $post( '/wm-k9/v1/bewertung', [ 'teil_b_passed' => 'nein' ] + $sheet );
$check( 400 === $r->get_status(), 'bewertung teil_b_passed=nein -> 400 (was BESTANDEN)' );

// --- Shortcodes -------------------------------------------------------------------------------------------
$saved_post = $_POST;
$_POST      = [];
$score      = do_shortcode( '[wm_k9_richterbogen]' );
$admission  = do_shortcode( '[wm_k9_zulassungscheck]' );
$_POST      = $saved_post;
$check( str_contains( $score, 'wm-k9-scorecard-box' ) && 0 === preg_match( '/name="k9_[lfsha]" value="[0-9]/', $score ), 'score form renders without pre-filled marks' );
$check( 0 === preg_match( '/name="k9_b" value="1" checked/', $score ), 'Teil B not pre-ticked' );
$check( str_contains( $score, 'FCI-PO 2019' ), 'score form names the edition of its layout' );
$check( str_contains( $admission, 'name="wm_k9_exam" value="' . current_datetime()->format( 'Y-m-d' ) . '"' ), 'admission form pre-fills the site date ' . current_datetime()->format( 'Y-m-d' ) . ' (' . wp_timezone_string() . ')' );

// --- Hub adapter --------------------------------------------------------------------------------------------
if ( class_exists( 'WM_K9_Spoke_Adapter' ) ) {
	$adapter = new WM_K9_Spoke_Adapter();
	$check( 'HEALTHY' === $adapter->get_health_status() && 'HEALTHY' === ( $adapter->get_telemetry_metrics()['status'] ?? '' ), 'hub adapter: health and telemetry from the self-test (HEALTHY)' );
} else {
	echo "  [SKIP] hub adapter not loaded (WM Suite Hub inactive)\n";
}

// --- Catalog through WordPress' MO parser ---------------------------------------------------------------------
$mo = new MO();
$mo->import_from_file( WM_K9_PLANNER_PATH . 'languages/wm-k9-bh-exam-planner-es_ES.mo' );
$check( 'Admisión' === $mo->translate( 'Zulassung' ), 'es_ES catalog: "Zulassung" -> "' . $mo->translate( 'Zulassung' ) . '" (was mojibake)' );
$registry_path = isset( $GLOBALS['wp_textdomain_registry'] ) ? (string) $GLOBALS['wp_textdomain_registry']->get( 'wm-k9-bh-exam-planner', 'es_ES' ) : '';
echo '  [INFO] textdomain registry path for es_ES: ' . ( '' !== $registry_path ? $registry_path : '(none)' ) . "\n";

echo "\n  RUNTIME RESULTS: {$GLOBALS['k9rt']['pass']} PASSED | {$GLOBALS['k9rt']['fail']} FAILED\n";
exit( $GLOBALS['k9rt']['fail'] > 0 ? 1 : 0 );
