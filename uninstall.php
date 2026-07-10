<?php
/**
 * HXMD Uninstall
 * プラグイン削除時にテーブルとオプションを削除する
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-hxmd-db.php';

HXMD_DB::drop_table();
delete_option( 'hxmd_custom_types' );
delete_option( 'hxmd_categories' );
delete_option( 'hxmd_db_version' );
delete_option( 'hxmd_hxfe_enabled' );
delete_option( 'hxmd_hxfe_forms' );
delete_option( 'hxmd_hxfe_log_type' );
delete_option( 'hxmd_hxrv_enabled' );
delete_option( 'hxmd_hxrv_log_type' );
