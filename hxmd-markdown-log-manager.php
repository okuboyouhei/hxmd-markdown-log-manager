<?php
/**
 * Plugin Name: HXMD — Markdown Log Manager
 * Plugin URI:  https://github.com/okuboyouhei/hxmd-markdown-log-manager
 * Description: Collect inquiries and memos, export as AI-ready Markdown. No API required.
 * Version:     1.3.0
 * Author:      youheiokubo
 * Author URI:  https://zenn.dev/youheiokubo
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hxmd-markdown-log-manager
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HXMD_VERSION',    '1.3.0' );
define( 'HXMD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HXMD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once HXMD_PLUGIN_DIR . 'includes/class-hxmd-db.php';
require_once HXMD_PLUGIN_DIR . 'includes/class-hxmd-log-types.php';
require_once HXMD_PLUGIN_DIR . 'includes/class-hxmd-categories.php';
require_once HXMD_PLUGIN_DIR . 'includes/class-hxmd-markdown.php';
require_once HXMD_PLUGIN_DIR . 'includes/class-hxmd-admin.php';
require_once HXMD_PLUGIN_DIR . 'includes/class-hxmd-hxfe-bridge.php';
require_once HXMD_PLUGIN_DIR . 'includes/class-hxmd-hxrv-bridge.php';
require_once HXMD_PLUGIN_DIR . 'includes/class-hxmd-post-export.php';

register_activation_hook( __FILE__, [ 'HXMD_DB', 'create_table' ] );

add_action( 'init', [ 'HXMD_Admin', 'init' ] );
add_action( 'admin_init', [ 'HXMD_DB', 'maybe_upgrade' ] );
HXMD_HXFE_Bridge::init();
HXMD_HXRV_Bridge::init();
HXMD_Post_Export::init();
