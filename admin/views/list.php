<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GETフィルターフォームのためnonce不要
$hxmd_saved   = ! empty( $_GET['saved'] );
$hxmd_deleted = ! empty( $_GET['deleted'] );
$hxmd_filter  = [
	'log_type'  => sanitize_text_field( wp_unslash( $_GET['log_type']  ?? '' ) ),
	'category'  => sanitize_text_field( wp_unslash( $_GET['category']  ?? '' ) ),
	'priority'  => sanitize_text_field( wp_unslash( $_GET['priority']  ?? '' ) ),
	'status'    => sanitize_text_field( wp_unslash( $_GET['status']    ?? '' ) ),
	'date_from' => sanitize_text_field( wp_unslash( $_GET['date_from'] ?? '' ) ),
	'date_to'   => sanitize_text_field( wp_unslash( $_GET['date_to']   ?? '' ) ),
	'search'    => sanitize_text_field( wp_unslash( $_GET['search']    ?? '' ) ),
	'orderby'   => sanitize_text_field( wp_unslash( $_GET['orderby']   ?? 'log_date' ) ),
	'order'     => strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ?? 'DESC' ) ) ),
];
// phpcs:enable
?>

<div class="wrap" x-data="hxmdList()">
  <h1 class="wp-heading-inline">HXMD ログ一覧</h1>
  <a href="<?php echo esc_url( admin_url( 'admin.php?page=hxmd-new' ) ); ?>" class="page-title-action">新規ログ</a>

  <?php if ( $hxmd_saved ) : ?>
    <div class="notice notice-success is-dismissible"><p>保存しました。</p></div>
  <?php endif; ?>
  <?php if ( $hxmd_deleted ) : ?>
    <div class="notice notice-success is-dismissible"><p>削除しました。</p></div>
  <?php endif; ?>

  <!-- フィルターフォーム -->
  <form method="get" action="" class="hxmd-filter-form">
    <input type="hidden" name="page" value="hxmd">
    <div class="hxmd-filter-row">
      <select name="log_type">
        <option value="">すべての種別</option>
        <?php foreach ( $types as $hxmd_type_key => $hxmd_type_label ) : ?>
          <option value="<?php echo esc_attr( $hxmd_type_key ); ?>" <?php selected( $hxmd_filter['log_type'], $hxmd_type_key ); ?>>
            <?php echo esc_html( $hxmd_type_label ); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php $hxmd_categories = HXMD_Categories::get_categories(); ?>
      <?php if ( ! empty( $hxmd_categories ) ) : ?>
      <select name="category">
        <option value="">すべてのカテゴリ</option>
        <?php foreach ( $hxmd_categories as $hxmd_cat_key => $hxmd_cat_label ) : ?>
          <option value="<?php echo esc_attr( $hxmd_cat_key ); ?>" <?php selected( $hxmd_filter['category'], $hxmd_cat_key ); ?>>
            <?php echo esc_html( $hxmd_cat_label ); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php endif; ?>
      <select name="priority">
        <option value="">すべての優先度</option>
        <option value="high"   <?php selected( $hxmd_filter['priority'], 'high' ); ?>>高</option>
        <option value="medium" <?php selected( $hxmd_filter['priority'], 'medium' ); ?>>中</option>
        <option value="low"    <?php selected( $hxmd_filter['priority'], 'low' ); ?>>低</option>
      </select>
      <select name="status">
        <option value="">すべてのステータス</option>
        <option value="open"        <?php selected( $hxmd_filter['status'], 'open' ); ?>>未対応</option>
        <option value="in_progress" <?php selected( $hxmd_filter['status'], 'in_progress' ); ?>>対応中</option>
        <option value="done"        <?php selected( $hxmd_filter['status'], 'done' ); ?>>完了</option>
      </select>
      <input type="date" name="date_from" value="<?php echo esc_attr( $hxmd_filter['date_from'] ); ?>" placeholder="開始日">
      <input type="date" name="date_to"   value="<?php echo esc_attr( $hxmd_filter['date_to'] ); ?>"   placeholder="終了日">
      <input type="search" name="search"  value="<?php echo esc_attr( $hxmd_filter['search'] ); ?>"    placeholder="キーワード検索">
      <button type="submit" class="button">絞り込む</button>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=hxmd' ) ); ?>" class="button">リセット</a>
    </div>
  </form>

  <!-- まとめてMDコピー -->
  <div class="hxmd-bulk-actions" x-show="selected.length > 0">
    <span x-text="selected.length + ' 件選択中'"></span>
    <button class="button button-primary" @click="bulkCopyMd()" x-text="copied ? 'コピーしました！' : 'まとめてMDコピー'"></button>
  </div>

  <!-- 一覧テーブル -->
  <table class="wp-list-table widefat fixed striped hxmd-table">
    <thead>
      <tr>
        <th class="hxmd-col-check"><input type="checkbox" @change="toggleAll($event)"></th>
        <?php
        $hxmd_cols = [
          'id'         => '#',
          'log_type'   => '種別',
          'category'   => 'カテゴリ',
          'log_date'   => '日付',
          'subject'    => '件名',
          'due_date'   => '期限',
          'priority'   => '優先度',
          'status'     => 'ステータス',
          'updated_at' => '更新日時',
        ];
        foreach ( $hxmd_cols as $hxmd_col => $hxmd_col_label ) :
          $hxmd_next_order = ( $hxmd_filter['orderby'] === $hxmd_col && $hxmd_filter['order'] === 'DESC' ) ? 'ASC' : 'DESC';
          $hxmd_sort_url   = esc_url( add_query_arg( [ 'orderby' => $hxmd_col, 'order' => $hxmd_next_order ] ) );
          $hxmd_arrow      = $hxmd_filter['orderby'] === $hxmd_col ? ( $hxmd_filter['order'] === 'ASC' ? ' ▲' : ' ▼' ) : '';
        ?>
        <th><a href="<?php echo esc_url( $hxmd_sort_url ); ?>"><?php echo esc_html( $hxmd_col_label . $hxmd_arrow ); ?></a></th>
        <?php endforeach; ?>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if ( empty( $logs ) ) : ?>
        <tr><td colspan="11" class="hxmd-empty">ログがありません。</td></tr>
      <?php else : ?>
        <?php foreach ( $logs as $hxmd_log ) :
          $hxmd_type_label     = HXMD_Log_Types::get_label( $hxmd_log['log_type'] );
          $hxmd_priority_label = [ 'high' => '高', 'medium' => '中', 'low' => '低' ][ $hxmd_log['priority'] ] ?? $hxmd_log['priority'];
          $hxmd_status_label   = [ 'open' => '未対応', 'in_progress' => '対応中', 'done' => '完了' ][ $hxmd_log['status'] ] ?? $hxmd_log['status'];
          $hxmd_edit_url       = esc_url( admin_url( 'admin.php?page=hxmd-new&id=' . $hxmd_log['id'] ) );
        ?>
        <tr>
          <td><input type="checkbox" :value="<?php echo (int) $hxmd_log['id']; ?>" x-model="selected"></td>
          <td><?php echo (int) $hxmd_log['id']; ?></td>
          <td>
            <span class="hxmd-badge hxmd-type-<?php echo esc_attr( $hxmd_log['log_type'] ); ?>"><?php echo esc_html( $hxmd_type_label ); ?></span>
            <?php if ( 'hxfe' === ( $hxmd_log['source'] ?? 'manual' ) ) : ?>
              <span class="hxmd-badge hxmd-source-hxfe">HXFE</span>
            <?php elseif ( 'hxrv' === ( $hxmd_log['source'] ?? 'manual' ) ) : ?>
              <span class="hxmd-badge hxmd-source-hxrv">HXRV</span>
            <?php endif; ?>
          </td>
          <td><?php echo esc_html( HXMD_Categories::get_label( $hxmd_log['category'] ?? '' ) ); ?></td>
          <td><?php echo esc_html( $hxmd_log['log_date'] ); ?></td>
          <td><a href="<?php echo esc_url( $hxmd_edit_url ); ?>"><?php echo esc_html( $hxmd_log['subject'] ); ?></a></td>
          <td>
            <?php
            $hxmd_due = $hxmd_log['due_date'] ?? '';
            if ( $hxmd_due ) :
              $hxmd_is_overdue = ( $hxmd_due < current_time( 'Y-m-d' ) && 'done' !== $hxmd_log['status'] );
            ?>
              <span class="<?php echo $hxmd_is_overdue ? 'hxmd-overdue' : ''; ?>"><?php echo esc_html( $hxmd_due ); ?></span>
            <?php endif; ?>
          </td>
          <td><span class="hxmd-priority hxmd-priority-<?php echo esc_attr( $hxmd_log['priority'] ); ?>"><?php echo esc_html( $hxmd_priority_label ); ?></span></td>
          <td><span class="hxmd-status hxmd-status-<?php echo esc_attr( $hxmd_log['status'] ); ?>"><?php echo esc_html( $hxmd_status_label ); ?></span></td>
          <td class="hxmd-updated"><?php echo esc_html( mysql2date( 'Y-m-d H:i', $hxmd_log['updated_at'] ) ); ?></td>
          <td>
            <button class="button button-small" @click="copyOneMd(<?php echo (int) $hxmd_log['id']; ?>, $event)">MDコピー</button>
            <a href="<?php echo esc_url( $hxmd_edit_url ); ?>" class="button button-small">編集</a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

