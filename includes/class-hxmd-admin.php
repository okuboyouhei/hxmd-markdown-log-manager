<?php
/**
 * HXMD_Admin — 管理画面全般
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class HXMD_Admin {

	public static function init(): void {
		if ( ! is_admin() ) { return; }
		add_action( 'admin_menu',                    [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts',         [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'admin_post_hxmd_save_log',      [ __CLASS__, 'handle_save' ] );
		add_action( 'admin_post_hxmd_delete_log',    [ __CLASS__, 'handle_delete' ] );
		add_action( 'admin_post_hxmd_bulk_delete',   [ __CLASS__, 'handle_bulk_delete' ] );
		add_action( 'admin_post_hxmd_save_settings', [ __CLASS__, 'handle_save_settings' ] );
		add_action( 'wp_ajax_hxmd_get_md',           [ __CLASS__, 'ajax_get_md' ] );
		add_action( 'wp_ajax_hxmd_get_post_md',      [ __CLASS__, 'ajax_get_post_md' ] );
	}

	public static function register_menu(): void {
		add_menu_page(
			'HXMD',
			'HXMD',
			'manage_options',
			'hxmd',
			[ __CLASS__, 'page_list' ],
			'dashicons-text-page',
			30
		);
		add_submenu_page( 'hxmd', 'ログ一覧', 'ログ一覧', 'manage_options', 'hxmd',          [ __CLASS__, 'page_list' ] );
		add_submenu_page( 'hxmd', '新規ログ', '新規ログ', 'manage_options', 'hxmd-new',      [ __CLASS__, 'page_edit' ] );
		add_submenu_page( 'hxmd', '投稿エクスポート', '投稿エクスポート', 'manage_options', 'hxmd-post-export', [ __CLASS__, 'page_post_export' ] );
		add_submenu_page( 'hxmd', '設定',     '設定',     'manage_options', 'hxmd-settings', [ __CLASS__, 'page_settings' ] );
	}

	public static function enqueue_assets( string $hook ): void {
		$hxmd_hooks = [ 'toplevel_page_hxmd', 'hxmd_page_hxmd-new', 'hxmd_page_hxmd-settings', 'hxmd_page_hxmd-post-export' ];
		if ( ! in_array( $hook, $hxmd_hooks, true ) ) {
			return;
		}

		wp_enqueue_script(
			'hxmd-admin',
			HXMD_PLUGIN_URL . 'admin/assets/hxmd-admin.js',
			[],
			HXMD_VERSION,
			[ 'strategy' => 'defer' ]
		);
		wp_enqueue_script(
			'hxmd-alpine',
			HXMD_PLUGIN_URL . 'assets/alpine.min.js',
			[ 'hxmd-admin' ],
			'3.15.12',
			[ 'strategy' => 'defer' ]
		);
		wp_enqueue_style(
			'hxmd-admin',
			HXMD_PLUGIN_URL . 'admin/assets/hxmd-admin.css',
			[],
			HXMD_VERSION
		);
		// 設定画面用の保存済みデータ
		$custom_types = [];
		$saved_types  = get_option( 'hxmd_custom_types', [] );
		if ( is_array( $saved_types ) ) {
			foreach ( $saved_types as $k => $v ) {
				$custom_types[] = [ 'key' => $k, 'label' => $v ];
			}
		}
		$categories       = [];
		$saved_categories = get_option( 'hxmd_categories', [] );
		if ( is_array( $saved_categories ) ) {
			foreach ( $saved_categories as $k => $v ) {
				$categories[] = [ 'key' => $k, 'label' => $v ];
			}
		}

		wp_localize_script( 'hxmd-admin', 'hxmdData', [
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'hxmd_nonce' ),
			'customTypes' => $custom_types,
			'categories'  => $categories,
		] );
	}

	public static function page_list(): void {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GETフィルターフォームのためnonce不要
		$args = [
			'log_type'  => sanitize_text_field( wp_unslash( $_GET['log_type']  ?? '' ) ),
			'category'  => sanitize_text_field( wp_unslash( $_GET['category']  ?? '' ) ),
			'priority'  => sanitize_text_field( wp_unslash( $_GET['priority']  ?? '' ) ),
			'status'    => sanitize_text_field( wp_unslash( $_GET['status']    ?? '' ) ),
			'date_from' => sanitize_text_field( wp_unslash( $_GET['date_from'] ?? '' ) ),
			'date_to'   => sanitize_text_field( wp_unslash( $_GET['date_to']   ?? '' ) ),
			'search'    => sanitize_text_field( wp_unslash( $_GET['search']    ?? '' ) ),
			'orderby'   => sanitize_text_field( wp_unslash( $_GET['orderby']   ?? 'log_date' ) ),
			'order'     => sanitize_text_field( wp_unslash( $_GET['order']     ?? 'DESC' ) ),
		];
		// phpcs:enable
		$logs  = HXMD_DB::get_logs( $args );
		$types = HXMD_Log_Types::get_types();
		include HXMD_PLUGIN_DIR . 'admin/views/list.php';
	}

	public static function page_edit(): void {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 表示用GETパラメータのためnonce不要
		$id  = intval( $_GET['id'] ?? 0 );
		$log = $id ? HXMD_DB::get_log( $id ) : null;
		$types = HXMD_Log_Types::get_types();
		include HXMD_PLUGIN_DIR . 'admin/views/edit.php';
	}

	public static function page_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$types = HXMD_Log_Types::get_types();
		include HXMD_PLUGIN_DIR . 'admin/views/settings.php';
	}

	public static function page_post_export(): void {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		include HXMD_PLUGIN_DIR . 'admin/views/post-export.php';
	}

	// Ajax: 投稿MD取得（コピー用）
	public static function ajax_get_post_md(): void {
		check_ajax_referer( 'hxmd_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$ids = array_map( 'intval', (array) ( $_POST['ids'] ?? [] ) );
		if ( empty( $ids ) ) {
			wp_send_json_error( 'No IDs', 400 );
		}

		$posts = [];
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			if ( $post instanceof WP_Post ) {
				$posts[] = $post;
			}
		}
		if ( empty( $posts ) ) {
			wp_send_json_error( 'Not found', 404 );
		}

		$md = count( $posts ) === 1
			? HXMD_Post_Export::render_post( $posts[0] )
			: HXMD_Post_Export::render_bulk( $posts );

		wp_send_json_success( [ 'md' => $md ] );
	}

	public static function handle_save(): void {
		check_admin_referer( 'hxmd_save_log' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Unauthorized' ); }

		$id = intval( $_POST['id'] ?? 0 );
		HXMD_DB::save_log( $_POST, $id );
		wp_safe_redirect( admin_url( 'admin.php?page=hxmd&saved=1' ) );
		exit;
	}

	public static function handle_delete(): void {
		check_admin_referer( 'hxmd_delete_log' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Unauthorized' ); }

		$id = intval( $_POST['id'] ?? 0 );
		HXMD_DB::delete_log( $id );
		wp_safe_redirect( admin_url( 'admin.php?page=hxmd&deleted=1' ) );
		exit;
	}

	public static function handle_bulk_delete(): void {
		check_admin_referer( 'hxmd_bulk_delete' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Unauthorized' ); }

		$ids = array_filter( array_map( 'intval', (array) ( $_POST['ids'] ?? [] ) ) );
		$deleted = HXMD_DB::delete_logs( $ids );
		wp_safe_redirect( admin_url( 'admin.php?page=hxmd&deleted=' . $deleted ) );
		exit;
	}

	public static function handle_save_settings(): void {
		check_admin_referer( 'hxmd_save_settings' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Unauthorized' ); }

		$types = isset( $_POST['custom_types'] ) ? wp_unslash( $_POST['custom_types'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_custom_types() 内でサニタイズ済み
		HXMD_Log_Types::save_custom_types( is_array( $types ) ? $types : [] );

		$categories = isset( $_POST['categories'] ) ? wp_unslash( $_POST['categories'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- save_categories() 内でサニタイズ済み
		HXMD_Categories::save_categories( is_array( $categories ) ? $categories : [] );

		// HXFE連携設定
		update_option( 'hxmd_hxfe_enabled', isset( $_POST['hxfe_enabled'] ) ? '1' : '0' );
		update_option( 'hxmd_hxfe_forms', sanitize_text_field( wp_unslash( $_POST['hxfe_forms'] ?? '' ) ) );
		update_option( 'hxmd_hxfe_log_type', sanitize_key( wp_unslash( $_POST['hxfe_log_type'] ?? 'email' ) ) );

		// HXRV連携設定
		update_option( 'hxmd_hxrv_enabled', isset( $_POST['hxrv_enabled'] ) ? '1' : '0' );
		update_option( 'hxmd_hxrv_log_type', sanitize_key( wp_unslash( $_POST['hxrv_log_type'] ?? 'memo' ) ) );

		// HXSR連携設定
		update_option( 'hxmd_hxsr_enabled', isset( $_POST['hxsr_enabled'] ) ? '1' : '0' );
		update_option( 'hxmd_hxsr_log_type', sanitize_key( wp_unslash( $_POST['hxsr_log_type'] ?? 'memo' ) ) );

		wp_safe_redirect( admin_url( 'admin.php?page=hxmd-settings&saved=1' ) );
		exit;
	}

	public static function ajax_get_md(): void {
		check_ajax_referer( 'hxmd_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$ids = array_map( 'intval', (array) ( $_POST['ids'] ?? [] ) );
		if ( empty( $ids ) ) {
			wp_send_json_error( 'No IDs', 400 );
		}

		$logs = HXMD_DB::get_logs_by_ids( $ids );
		$md   = count( $logs ) === 1
			? HXMD_Markdown::render_single( $logs[0] )
			: HXMD_Markdown::render_bulk( $logs );

		wp_send_json_success( [ 'md' => $md ] );
	}
}
