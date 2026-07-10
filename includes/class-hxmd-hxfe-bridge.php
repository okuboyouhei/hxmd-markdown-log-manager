<?php
/**
 * HXMD_HXFE_Bridge — HXFE フォーム送信の自動取り込み
 *
 * HXFE v1.4.5 以降の `hxfe_after_submit` フックを購読し、
 * フォーム送信を HXMD のログとして自動保存する。
 * HXFE が無効・未インストールの場合は何もしない。
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class HXMD_HXFE_Bridge {

	public static function init(): void {
		// HXFE 本体が有効な場合のみフックを登録する。
		// plugins_loaded 以降であれば HXFE_VERSION が定義済み。
		add_action( 'plugins_loaded', [ __CLASS__, 'maybe_register' ] );
	}

	public static function maybe_register(): void {
		if ( ! defined( 'HXFE_VERSION' ) ) {
			return;
		}
		add_action( 'hxfe_after_submit', [ __CLASS__, 'capture' ], 10, 3 );
	}

	/**
	 * HXFE 送信をログとして取り込む。
	 *
	 * @param string $form_id フォームID
	 * @param array  $values  送信された値（HXFE側でサニタイズ済み）
	 * @param array  $schema  フォームスキーマ
	 */
	public static function capture( string $form_id, array $values, array $schema ): void {
		// 連携が有効か
		if ( '1' !== get_option( 'hxmd_hxfe_enabled', '0' ) ) {
			return;
		}

		// 対象フォームか（空 = 全フォーム対象）
		$target_forms = get_option( 'hxmd_hxfe_forms', '' );
		if ( '' !== $target_forms ) {
			$allowed = array_filter( array_map( 'trim', explode( ',', $target_forms ) ) );
			if ( ! in_array( $form_id, $allowed, true ) ) {
				return;
			}
		}

		// フィールドマッピング（フィルターで上書き可能）
		$mapping = apply_filters( 'hxmd_hxfe_field_mapping', [
			'contact_name' => [ 'name', 'your-name', 'onamae', 'fullname' ],
			'subject'      => [ 'subject', 'title', 'kenmei' ],
			'body'         => [ 'message', 'body', 'content', 'inquiry', 'honbun' ],
		], $form_id, $schema );

		$contact = self::pick( $values, $mapping['contact_name'] );
		$subject = self::pick( $values, $mapping['subject'] );
		$body    = self::pick( $values, $mapping['body'] );

		// subjectが取れなければフォーム名 or フォームIDで補完
		if ( '' === $subject ) {
			$form_title = $schema['title'] ?? $form_id;
			$subject    = sprintf( '（%s からの送信）', $form_title );
		}

		// マッピングされなかった残りの値も本文に含める（AIに渡す情報を欠落させない）
		$mapped_keys = array_merge( $mapping['contact_name'], $mapping['subject'], $mapping['body'] );
		$extras      = [];
		foreach ( $values as $key => $value ) {
			if ( in_array( $key, $mapped_keys, true ) ) {
				continue;
			}
			if ( is_scalar( $value ) && '' !== (string) $value && 0 !== strpos( $key, '__' ) ) {
				$extras[] = "{$key}: {$value}";
			}
		}
		if ( ! empty( $extras ) ) {
			$body .= ( '' !== $body ? "\n\n" : '' ) . "--- その他のフィールド ---\n" . implode( "\n", $extras );
		}

		HXMD_DB::save_log( [
			'log_type'     => get_option( 'hxmd_hxfe_log_type', 'email' ),
			'log_date'     => current_time( 'Y-m-d' ),
			'contact_name' => $contact,
			'subject'      => $subject,
			'body'         => $body,
			'priority'     => 'medium',
			'instruction'  => '',
			'status'       => 'open',
			'source'       => 'hxfe',
		] );
	}

	/**
	 * 候補キーのリストから最初に見つかった値を返す。
	 *
	 * @param array $values 送信値
	 * @param array $keys   候補キー（優先順）
	 */
	private static function pick( array $values, array $keys ): string {
		foreach ( $keys as $key ) {
			if ( isset( $values[ $key ] ) && is_scalar( $values[ $key ] ) && '' !== (string) $values[ $key ] ) {
				return (string) $values[ $key ];
			}
		}
		return '';
	}
}
