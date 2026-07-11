<?php
/**
 * HXMD_DB — カスタムテーブル操作
 *
 * カスタムテーブルを直接操作するため、以下のルールをファイル単位で無効化する。
 * - WordPress.DB.DirectDatabaseQuery.DirectQuery  : カスタムテーブルはWP_Queryで扱えないため直接クエリが必要
 * - WordPress.DB.DirectDatabaseQuery.NoCaching    : ログ一覧は常に最新データが必要なためキャッシュ不使用
 * - WordPress.DB.PreparedSQL.InterpolatedNotPrepared : テーブル名・カラム名は self::table() / allowlist で安全に制御済み
 * - WordPress.DB.PreparedSQL.NotPrepared          : $wpdb->prepare() 済みの変数を渡している箇所
 * - PluginCheck.Security.DirectDB.UnescapedDBParameter : テーブル名は内部生成のみ、SQL文は prepare() 済み
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

if ( ! defined( 'ABSPATH' ) ) { exit; }

class HXMD_DB {

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'hxmd_logs';
	}

	public static function create_table(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$table   = self::table();

		$sql = "CREATE TABLE {$table} (
			id           INT          NOT NULL AUTO_INCREMENT,
			log_type     VARCHAR(50)  NOT NULL DEFAULT 'memo',
			category     VARCHAR(100) NOT NULL DEFAULT '',
			log_date     DATE         NOT NULL,
			start_date   DATE         DEFAULT NULL,
			due_date     DATE         DEFAULT NULL,
			contact_name VARCHAR(255) NOT NULL DEFAULT '',
			subject      VARCHAR(255) NOT NULL DEFAULT '',
			body         TEXT         NOT NULL DEFAULT '',
			priority     VARCHAR(20)  NOT NULL DEFAULT 'medium',
			instruction  TEXT         NOT NULL DEFAULT '',
			links        TEXT         NOT NULL DEFAULT '',
			status       VARCHAR(20)  NOT NULL DEFAULT 'open',
			source       VARCHAR(50)  NOT NULL DEFAULT 'manual',
			created_at   DATETIME     NOT NULL,
			updated_at   DATETIME     NOT NULL,
			PRIMARY KEY (id),
			KEY log_type (log_type),
			KEY category (category),
			KEY log_date (log_date),
			KEY due_date (due_date),
			KEY priority (priority),
			KEY status (status)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( 'hxmd_db_version', HXMD_VERSION );
	}

	/**
	 * DBスキーマのアップグレード。
	 * バージョンが変わっていたら dbDelta を再実行してカラム追加を反映する。
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( 'hxmd_db_version' ) !== HXMD_VERSION ) {
			self::create_table();
		}
	}

	public static function drop_table(): void {
		global $wpdb;
		$table = self::table();
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	public static function get_logs( array $args = [] ): array {
		global $wpdb;
		$table = self::table();

		$defaults = [
			'log_type'  => '',
			'category'  => '',
			'priority'  => '',
			'status'    => '',
			'date_from' => '',
			'date_to'   => '',
			'search'    => '',
			'orderby'   => 'log_date',
			'order'     => 'DESC',
		];
		$args = wp_parse_args( $args, $defaults );

		$where  = [ '1=1' ];
		$params = [];

		if ( $args['log_type'] ) {
			$where[]  = 'log_type = %s';
			$params[] = $args['log_type'];
		}
		if ( $args['category'] ) {
			$where[]  = 'category = %s';
			$params[] = $args['category'];
		}
		if ( $args['priority'] ) {
			$where[]  = 'priority = %s';
			$params[] = $args['priority'];
		}
		if ( $args['status'] ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}
		if ( $args['date_from'] ) {
			$where[]  = 'log_date >= %s';
			$params[] = $args['date_from'];
		}
		if ( $args['date_to'] ) {
			$where[]  = 'log_date <= %s';
			$params[] = $args['date_to'];
		}
		if ( $args['search'] ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(subject LIKE %s OR body LIKE %s OR contact_name LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$allowed_orderby = [ 'log_date', 'start_date', 'due_date', 'priority', 'status', 'log_type', 'category', 'id', 'updated_at' ];
		$orderby = in_array( $args['orderby'], $allowed_orderby, true )
			? $args['orderby'] : 'log_date';
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$where_sql = implode( ' AND ', $where );

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order}",
				...$params
			);
		} else {
			$sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order}";
		}

		return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
	}

	public static function get_log( int $id ): ?array {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function save_log( array $data, int $id = 0 ): int|false {
		global $wpdb;
		$table = self::table();
		$now   = current_time( 'mysql' );

		$start_date = sanitize_text_field( $data['start_date'] ?? '' );
		$due_date   = sanitize_text_field( $data['due_date']   ?? '' );

		$row = [
			'log_type'     => sanitize_text_field( $data['log_type']        ?? 'memo' ),
			'category'     => sanitize_text_field( $data['category']        ?? '' ),
			'log_date'     => sanitize_text_field( $data['log_date']        ?? $now ),
			'start_date'   => '' !== $start_date ? $start_date : null,
			'due_date'     => '' !== $due_date ? $due_date : null,
			'contact_name' => sanitize_text_field( $data['contact_name']    ?? '' ),
			'subject'      => sanitize_text_field( $data['subject']         ?? '' ),
			'body'         => sanitize_textarea_field( $data['body']        ?? '' ),
			'priority'     => sanitize_text_field( $data['priority']        ?? 'medium' ),
			'instruction'  => sanitize_textarea_field( $data['instruction'] ?? '' ),
			'links'        => sanitize_textarea_field( $data['links']       ?? '' ),
			'status'       => sanitize_text_field( $data['status']          ?? 'open' ),
			'source'       => sanitize_text_field( $data['source']          ?? 'manual' ),
			'updated_at'   => $now,
		];

		if ( $id > 0 ) {
			$wpdb->update( $table, $row, [ 'id' => $id ], null, [ '%d' ] );
			return $id;
		}

		$row['created_at'] = $now;
		$wpdb->insert( $table, $row );
		return $wpdb->insert_id ?: false;
	}

	public static function delete_log( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( self::table(), [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * 複数ログの一括削除。
	 *
	 * @param int[] $ids ログIDの配列
	 * @return int 削除件数
	 */
	public static function delete_logs( array $ids ): int {
		global $wpdb;
		$ids = array_filter( array_map( 'intval', $ids ) );
		if ( empty( $ids ) ) {
			return 0;
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		return (int) $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'DELETE FROM ' . self::table() . " WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- テーブル名はprefix由来、プレースホルダは%dのみで構築
				...$ids
			)
		);
	}

	public static function get_logs_by_ids( array $ids ): array {
		if ( empty( $ids ) ) { return []; }
		global $wpdb;
		$table        = self::table();
		$ids          = array_map( 'intval', $ids );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id IN ({$placeholders}) ORDER BY log_date DESC",
			...$ids
		);
		return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
	}
}

// phpcs:enable
