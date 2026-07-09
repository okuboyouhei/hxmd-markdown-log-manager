<?php
/**
 * HXMD_Log_Types — 種別管理
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class HXMD_Log_Types {

	private static array $defaults = [
		'phone'   => '電話',
		'email'   => 'メール',
		'meeting' => '会議',
		'memo'    => 'メモ',
	];

	public static function get_types(): array {
		$custom = get_option( 'hxmd_custom_types', [] );
		$merged = array_merge( self::$defaults, is_array( $custom ) ? $custom : [] );
		return apply_filters( 'hxmd_log_types', $merged );
	}

	public static function get_label( string $key ): string {
		$types = self::get_types();
		return $types[ $key ] ?? $key;
	}

	public static function save_custom_types( array $types ): void {
		$sanitized = [];
		foreach ( $types as $key => $label ) {
			$key   = sanitize_key( $key );
			$label = sanitize_text_field( $label );
			if ( $key && $label ) {
				$sanitized[ $key ] = $label;
			}
		}
		update_option( 'hxmd_custom_types', $sanitized );
	}

	public static function is_default( string $key ): bool {
		return array_key_exists( $key, self::$defaults );
	}
}
