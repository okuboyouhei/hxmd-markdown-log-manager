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
		$lines[] = "- 優先度: {$priority_label}";
		$lines[] = "- ステータス: {$status_label}";
		$lines[] = "- 件名: {$log['subject']}";

		if ( $log['body'] ) {
			$lines[] = "- 詳細: {$log['body']}";
		}
		if ( $log['instruction'] ) {
			$lines[] = "- 対応指示: {$log['instruction']}";
		}

		return implode( "\n", $lines );
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
