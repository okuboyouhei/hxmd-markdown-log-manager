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
