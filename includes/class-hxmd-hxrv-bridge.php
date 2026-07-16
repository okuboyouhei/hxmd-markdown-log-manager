<?php
/**
 * HXMD_HXRV_Bridge — HXRV ピンコメントの自動取り込み
 *
 * HXRV v1.0.1 以降の `hxrv_after_comment_created` フックを購読し、
 * ビジュアルレビューのピンコメントを HXMD のログとして自動保存する。
 * HXRV が無効・未インストールの場合は何もしない。
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class HXMD_HXRV_Bridge {

	public static function init(): void {
		add_action( 'plugins_loaded', [ __CLASS__, 'maybe_register' ] );
	}

	public static function maybe_register(): void {
		if ( ! defined( 'HXRV_VERSION' ) ) {
			return;
		}
		add_action( 'hxrv_after_comment_created', [ __CLASS__, 'capture' ], 10, 2 );
	}

	/**
	 * HXRV ピンコメントをログとして取り込む。
	 *
	 * @param int    $id      コメントID
	 * @param object $comment コメントオブジェクト
	 */
	public static function capture( int $id, object $comment ): void {
		// 連携が有効か
		if ( '1' !== get_option( 'hxmd_hxrv_enabled', '0' ) ) {
			return;
		}

		// 返信（parent_idあり）は取り込まない。ピン本体のみ
		if ( ! empty( $comment->parent_id ) ) {
			return;
		}

		$content  = (string) ( $comment->content ?? '' );
		$page_url = (string) ( $comment->page_url ?? '' );
		$selector = (string) ( $comment->selector ?? '' );
		// HXRV v1.2.0+ の任意項目。旧版では未定義なので ?? で無害に受ける。
		$before   = trim( (string) ( $comment->before_text ?? '' ) );
		$after    = trim( (string) ( $comment->after_text ?? '' ) );

		// 件名はコメントの先頭行（40字まで）
		$first_line = strtok( $content, "\n" );
		$subject    = mb_strlen( $first_line ) > 40
			? mb_substr( $first_line, 0, 40 ) . '…'
			: $first_line;
		if ( '' === $subject ) {
			$subject = sprintf( '（ビジュアルレビュー #%d）', $id );
		}

		// Before/After（現状 → あるべき姿）ブロック。片方だけでも出力する。
		$ba_lines = [];
		if ( '' !== $before ) {
			$ba_lines[] = "Before（現状）:\n{$before}";
		}
		if ( '' !== $after ) {
			$ba_lines[] = "After（あるべき姿）:\n{$after}";
		}
		$ba_block = implode( "\n\n", $ba_lines );

		// 本文: コメント + 対象要素の情報 + Before/After
		$body = $content;
		if ( $selector ) {
			$body .= "\n\n対象要素: `{$selector}`";
		}
		if ( '' !== $ba_block ) {
			$body .= "\n\n{$ba_block}";
		}

		// 対応指示: コメント + Before/After（修正の差分をエージェントに渡す）
		$instruction = $content;
		if ( '' !== $ba_block ) {
			$instruction = '' !== $instruction ? "{$instruction}\n\n{$ba_block}" : $ba_block;
		}

		// 関連URL: 対象ページ
		$links = '';
		if ( $page_url ) {
			$links = "{$page_url} 対象ページ";
		}

		HXMD_DB::save_log( [
			'log_type'     => get_option( 'hxmd_hxrv_log_type', 'memo' ),
			'log_date'     => current_time( 'Y-m-d' ),
			'contact_name' => (string) ( $comment->author_name ?? '' ),
			'subject'      => $subject,
			'body'         => $body,
			'priority'     => 'medium',
			'instruction'  => $instruction,
			'links'        => $links,
			'status'       => 'open',
			'source'       => 'hxrv',
		] );
	}
}
