<?php
/**
 * WM K9 BH Exam Planner — Uninstaller
 *
 * Does nothing unless the site administrator explicitly opted into data removal via
 * `wm_k9_delete_data_on_uninstall`; then deletes that flag, the only option the plugin stores. The
 * rate-limit transients expire after 60 seconds on their own.
 *
 * @package WM_K9_BH_Exam_Planner
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! in_array( get_option( 'wm_k9_delete_data_on_uninstall', '0' ), [ 1, '1', true ], true ) ) {
	return;
}

// Until 1.2.1 this also deleted `wm_k9_planner_settings` and the transient `wm_k9_planner_cache`, which no
// version of the plugin ever wrote (git log -S).
delete_option( 'wm_k9_delete_data_on_uninstall' );
