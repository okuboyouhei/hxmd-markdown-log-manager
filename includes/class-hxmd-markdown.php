<?php
/**
 * HXMD_Markdown — MDエクスポート生成
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class HXMD_Markdown {

	private static array $priority_labels = [
		'high'   => '高',
		'medium' => '中',
		'low'    => '低',
	];

	private static array $status_labels = [
		'open'        => '未対応',
		'in_progress' => '対応中',
		'done'        => '完了',
	];

	public static function render_single( array $log ): string {
		$type_label     = HXMD_Log_Types::get_label( $log['log_type'] );
		$priority_label = self::$priority_labels[ $log['priority'] ] ?? $log['priority'];
		$status_label   = self::$status_labels[ $log['status'] ]     ?? $log['status'];
		$id_str         = str_pad( (string) $log['id'], 3, '0', STR_PAD_LEFT );

		$lines   = [];
		$lines[] = "## [LOG-{$id_str}] {$type_label} / {$log['log_date']}";
		$lines[] = '';

		if ( $log['contact_name'] ) {
			$lines[] = "- 連絡者: {$log['contact_name']}";
		}
		if ( ! empty( $log['category'] ) ) {
			$lines[] = '- カテゴリ: ' . HXMD_Categories::get_label( $log['category'] );
		}
		$lines[] = "- 優先度: {$priority_label}";
		$lines[] = "- ステータス: {$status_label}";
		if ( ! empty( $log['start_date'] ) ) {
			$lines[] = "- 開始日: {$log['start_date']}";
		}
		if ( ! empty( $log['due_date'] ) ) {
			$lines[] = "- 期限日: {$log['due_date']}";
		}
		$lines[] = "- 件名: {$log['subject']}";
		if ( ! empty( $log['updated_at'] ) ) {
			$lines[] = '- 最終更新: ' . mysql2date( 'Y-m-d H:i', $log['updated_at'] );
		}

		if ( $log['body'] ) {
			$lines[] = self::render_text_field( '詳細', $log['body'] );
		}
		if ( $log['instruction'] ) {
			$lines[] = self::render_text_field( '対応指示', $log['instruction'] );
		}
		if ( ! empty( $log['links'] ) ) {
			$lines[] = self::render_links( $log['links'] );
		}

		return implode( "\n", $lines );
	}

	/**
	 * 関連URL（1行1URL、URLの後ろにメモ可）をMDリンクリストに変換する。
	 *
	 * @param string $links 生テキスト
	 */
	private static function render_links( string $links ): string {
		$out = [ '', '### 関連URL', '' ];
		foreach ( preg_split( '/\r\n|\r|\n/', $links ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			// URL + スペース + メモ の形式を分解
			if ( preg_match( '/^(https?:\/\/\S+)\s+(.+)$/', $line, $m ) ) {
				$out[] = "- [{$m[2]}]({$m[1]})";
			} elseif ( preg_match( '/^https?:\/\/\S+$/', $line ) ) {
				$out[] = "- {$line}";
			} else {
				$out[] = "- {$line}";
			}
		}
		return implode( "\n", $out );
	}

	/**
	 * 複数行対応のフィールド出力。
	 * 1行なら `- ラベル: 値`、複数行ならセクション形式にする。
	 *
	 * @param string $label ラベル
	 * @param string $value 値
	 */
	private static function render_text_field( string $label, string $value ): string {
		if ( false === strpos( $value, "\n" ) ) {
			return "- {$label}: {$value}";
		}
		// 複数行はセクション化してMD構造を保持する
		return "\n### {$label}\n\n{$value}\n";
	}

	public static function render_bulk( array $logs ): string {
		$date  = date_i18n( 'Y-m-d' );
		$total = count( $logs );

		$lines   = [];
		$lines[] = '# HXMD Log Export';
		$lines[] = "Generated: {$date}";
		$lines[] = "Total: {$total} 件";
		$lines[] = '';
		$lines[] = '---';

		foreach ( $logs as $log ) {
			$lines[] = '';
			$lines[] = self::render_single( $log );
			$lines[] = '';
			$lines[] = '---';
		}

		return implode( "\n", $lines );
	}
}
