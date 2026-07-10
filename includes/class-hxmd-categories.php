<?php
/**
 * HXMD_Categories — カテゴリ（プロジェクト・クライアント軸）管理
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class HXMD_Categories {

	/**
	 * 全カテゴリを返す（設定 + フィルター）。
	 * キー => ラベル の連想配列。デフォルトは空。
	 */
	public static function get_categories(): array {
		$saved = get_option( 'hxmd_categories', [] );
		$saved = is_array( $saved ) ? $saved : [];
		return apply_filters( 'hxmd_categories', $saved );
	}

	public static function get_label( string $key ): string {
		if ( '' === $key ) {
			return '';
		}
		$categories = self::get_categories();
		return $categories[ $key ] ?? $key;
	}

	public static function save_categories( array $categories ): void {
		$sanitized = [];
		foreach ( $categories as $key => $label ) {
			$key   = sanitize_key( $key );
			$label = sanitize_text_field( $label );
			if ( $key && $label ) {
				$sanitized[ $key ] = $label;
			}
		}
		update_option( 'hxmd_categories', $sanitized );
	}
}
