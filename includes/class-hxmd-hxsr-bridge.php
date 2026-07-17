<?php
/**
 * HXMD_HXSR_Bridge — HXSR ショートリンクの自動取り込み
 *
 * HXSR v0.1.0 以降の `hxsr_after_save` フックを購読し、
 * ショートリンクの情報を HXMD のログとして自動保存する。
 * HXSR が無効・未インストールの場合は何もしない。
 *
 * 【HXFE/HXRVブリッジとの設計上の違い】
 * HXFEの送信・HXRVのピンコメントは「1回きりのイベント」なので毎回新規insertでよいが、
 * HXSRの `hxsr_after_save` は「同じリンクを編集保存するたび」に発火する。
 * そのため本ブリッジは link_id ⇔ hxmd_log_id の対応表をオプションとして持ち、
 * 既存ログがあれば更新、なければ新規作成する（重複ログの増殖を防ぐ）。
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class HXMD_HXSR_Bridge {

	const MAP_OPTION = 'hxmd_hxsr_link_map';

	public static function init(): void {
		add_action( 'plugins_loaded', [ __CLASS__, 'maybe_register' ] );
	}

	public static function maybe_register(): void {
		if ( ! defined( 'HXSR_VERSION' ) ) {
			return;
		}
		add_action( 'hxsr_after_save', [ __CLASS__, 'capture' ], 10, 3 );
	}

	/**
	 * HXSR リンク保存をログとして取り込む（新規 or 更新）。
	 *
	 * @param object   $link      hxsr_links 行オブジェクト（id, slug, redirect_url, memo, access_count, ...）
	 * @param object[] $schedules hxsr_schedules の配列
	 * @param string   $markdown  HXSR が生成したMarkdown本文（本ブリッジは独自整形するため未使用。フック契約として受け取る）
	 */
	public static function capture( object $link, array $schedules, string $markdown ): void {
		// 連携が有効か
		if ( '1' !== get_option( 'hxmd_hxsr_enabled', '0' ) ) {
			return;
		}

		$link_id = (int) ( $link->id ?? 0 );
		if ( ! $link_id ) {
			return;
		}

		$slug      = (string) ( $link->slug ?? '' );
		$short_url = function_exists( 'hxsr_get_short_url' ) ? hxsr_get_short_url( $slug ) : '';
		$memo      = (string) ( $link->memo ?? '' );

		$subject = $slug ? "/{$slug}" : '（HXSR リンク）';

		// リダイレクト種別の日本語ラベル
		$type_labels = [
			'url'   => 'URL 直接指定',
			'post'  => '投稿・固定ページ',
			'media' => 'メディア（ファイル）',
		];
		$redirect_type  = (string) ( $link->redirect_type ?? 'url' );
		$type_label     = $type_labels[ $redirect_type ] ?? $redirect_type;
		$access_count   = (int) ( $link->access_count ?? 0 );

		// body: メモを主文にしつつ、HXSR固有の状態を構造化ブロックとして畳み込む
		// （HXRVブリッジが Before/After を畳み込む流儀を踏襲）
		$body_parts = [];
		if ( '' !== $memo ) {
			$body_parts[] = $memo;
		}

		$status_lines   = [];
		$status_lines[] = '## HXSR リンク情報';
		$status_lines[] = "- ショートURL: {$short_url}";
		$status_lines[] = "- リダイレクト種別: {$type_label}";
		if ( ! empty( $link->redirect_url ) ) {
			$status_lines[] = "- 現在のリダイレクト先: {$link->redirect_url}";
		}
		$status_lines[] = "- アクセス数: {$access_count}";

		// スケジュール（予約リダイレクト）
		if ( ! empty( $schedules ) ) {
			$status_lines[] = '';
			$status_lines[] = '### 予約リダイレクト';
			foreach ( $schedules as $sched ) {
				$sched_when = (string) ( $sched->scheduled_at ?? '' );
				$sched_to   = (string) ( $sched->redirect_url ?? '' );
				$sched_done = ! empty( $sched->applied ) ? '（適用済み）' : '（予約中）';
				$status_lines[] = "- {$sched_when} → {$sched_to} {$sched_done}";
			}
		}

		$body_parts[] = implode( "\n", $status_lines );
		$body         = implode( "\n\n", $body_parts );

		$links_field = '';
		if ( $short_url ) {
			$links_field .= "{$short_url} ショートURL\n";
		}
		if ( ! empty( $link->redirect_url ) ) {
			$links_field .= "{$link->redirect_url} リダイレクト先";
		}

		$data = [
			'log_type'     => get_option( 'hxmd_hxsr_log_type', 'memo' ),
			'log_date'     => current_time( 'Y-m-d' ),
			'contact_name' => '',
			'subject'      => $subject,
			'body'         => $body,
			'priority'     => 'low',
			'instruction'  => '',
			'links'        => trim( $links_field ),
			'status'       => 'open',
			'source'       => 'hxsr',
		];

		// 既存ログがあれば更新、なければ新規作成（編集のたびに増殖させない）
		$existing_log_id = self::find_existing_log_id( $link_id );

		if ( $existing_log_id ) {
			$result = HXMD_DB::save_log( $data, $existing_log_id );
			// 対応先が手動削除されていた場合は新規作成にフォールバック
			if ( false === $result ) {
				$existing_log_id = 0;
			}
		}

		if ( ! $existing_log_id ) {
			$new_id = HXMD_DB::save_log( $data );
			if ( $new_id ) {
				self::remember_log_id( $link_id, (int) $new_id );
			}
		}
	}

	/**
	 * link_id に対応する既存の hxmd_log_id を取得する。
	 * ログが手動削除されている場合は null 相当（0）を返す。
	 */
	private static function find_existing_log_id( int $link_id ): int {
		$map = get_option( self::MAP_OPTION, [] );
		if ( ! is_array( $map ) || empty( $map[ $link_id ] ) ) {
			return 0;
		}

		$log_id = (int) $map[ $link_id ];

		// ログが実在するか確認（手動削除されていたら対応表から除去）
		if ( null === HXMD_DB::get_log( $log_id ) ) {
			unset( $map[ $link_id ] );
			update_option( self::MAP_OPTION, $map, false ); // 保存時のみ参照するためautoloadしない
			return 0;
		}

		return $log_id;
	}

	/**
	 * link_id ⇔ hxmd_log_id の対応を記録する。
	 */
	private static function remember_log_id( int $link_id, int $log_id ): void {
		$map             = get_option( self::MAP_OPTION, [] );
		$map             = is_array( $map ) ? $map : [];
		$map[ $link_id ] = $log_id;
		// リンク数に比例して成長する配列のため、autoload対象にしない
		// （必要なのはリンク保存時のみ。全ページロードで読み込む必要がない）
		update_option( self::MAP_OPTION, $map, false );
	}
}
