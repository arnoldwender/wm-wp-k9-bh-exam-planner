<?php
/**
 * Plugin Name:       WM K9 BH Exam Planner
 * Plugin URI:        https://arnoldwender.com/wordpress-plugins/wm-k9-bh-exam-planner/
 * Description:       Zulassungs-Vorabprüfung (Alter, 15-stelliger Transpondercode, Sachkundenachweis) und Punkterechner für die Begleithundeprüfung (BH/VT), als Shortcode, REST-Route und WP-CLI-Befehl.
 * Version:           1.2.2
 * Requires at least: 6.4
 * Tested up to:      7.1
 * Requires PHP:      8.1
 * Author:            Arnold Wender
 * Author URI:        https://arnoldwender.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wm-k9-bh-exam-planner
 * Domain Path:       /languages
 *
 * @package WM_K9_BH_Exam_Planner
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WM_K9_PLANNER_VERSION', '1.2.2' );
define( 'WM_K9_PLANNER_FILE', __FILE__ );
define( 'WM_K9_PLANNER_PATH', plugin_dir_path( __FILE__ ) );

// Load core subsystems
require_once WM_K9_PLANNER_PATH . 'includes/class-k9-admission-validator.php';
require_once WM_K9_PLANNER_PATH . 'includes/class-k9-scoring-engine.php';
require_once WM_K9_PLANNER_PATH . 'includes/class-k9-shortcodes.php';
require_once WM_K9_PLANNER_PATH . 'includes/class-k9-rest-controller.php';
require_once WM_K9_PLANNER_PATH . 'includes/class-k9-ecosystem-bridges.php';
require_once WM_K9_PLANNER_PATH . 'includes/class-k9-admin-dashboard.php';

WM_K9_Shortcodes::init();
WM_K9_REST_Controller::init();
WM_K9_Ecosystem_Bridges::init();

/**
 * Load translations and fire readiness action.
 */
function wm_k9_bootstrap_i18n(): void {
	load_plugin_textdomain(
		'wm-k9-bh-exam-planner',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
	do_action( 'wm_k9_ready', WM_K9_PLANNER_VERSION );
}
add_action( 'init', 'wm_k9_bootstrap_i18n', 5 );

if ( is_admin() ) {
	WM_K9_Admin_Dashboard::init();
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once WM_K9_PLANNER_PATH . 'includes/class-k9-cli.php';
}

/**
 * Register with WM Suite Hub if active.
 */
function wm_k9_register_spoke_module(): void {
	if ( interface_exists( 'ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface' ) ) {
		require_once WM_K9_PLANNER_PATH . 'includes/class-k9-spoke-adapter.php';
		if ( class_exists( 'WM_K9_Spoke_Adapter' ) ) {
			add_filter( 'wm_register_suite_module', static function ( array $modules ): array {
				$modules[] = new WM_K9_Spoke_Adapter();
				return $modules;
			} );
		}
	}
}
add_action( 'plugins_loaded', 'wm_k9_register_spoke_module', 20 );
