<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET絞り込みフォームのためnonce不要
$hxmd_pe_type   = sanitize_key( wp_unslash( $_GET['hxmd_pt'] ?? 'post' ) );
$hxmd_pe_search = sanitize_text_field( wp_unslash( $_GET['hxmd_s'] ?? '' ) );
// phpcs:enable

$hxmd_pe_types = get_post_types( [ 'public' => true ], 'objects' );
unset( $hxmd_pe_types['attachment'] );

$hxmd_pe_query = new WP_Query( [
	'post_type'      => array_key_exists( $hxmd_pe_type, $hxmd_pe_types ) ? $hxmd_pe_type : 'post',
	'post_status'    => [ 'publish', 'draft', 'private' ],
	'posts_per_page' => 50,
	's'              => $hxmd_pe_search,
	'orderby'        => 'modified',
	'order'          => 'DESC',
] );
?>

<div class="wrap" x-data="hxmdPostExport()">
  <h1>投稿エクスポート</h1>
  <p class="description">投稿・固定ページを構造化Markdownとしてコピーします。NotebookLMへの投入や、AIへのリライト依頼にどうぞ。</p>

  <!-- 絞り込み -->
  <form method="get" action="" class="hxmd-filter-form">
    <input type="hidden" name="page" value="hxmd-post-export">
    <div class="hxmd-filter-row">
      <select name="hxmd_pt">
        <?php foreach ( $hxmd_pe_types as $hxmd_pt_key => $hxmd_pt_obj ) : ?>
          <option value="<?php echo esc_attr( $hxmd_pt_key ); ?>" <?php selected( $hxmd_pe_type, $hxmd_pt_key ); ?>>
            <?php echo esc_html( $hxmd_pt_obj->labels->name ); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="hxmd_s" value="<?php echo esc_attr( $hxmd_pe_search ); ?>" placeholder="タイトル・本文を検索">
      <button type="submit" class="button">絞り込む</button>
    </div>
  </form>

  <!-- まとめてMDコピー -->
  <div class="hxmd-bulk-actions" x-show="selected.length > 0">
    <span x-text="selected.length + ' 件選択中'"></span>
    <button class="button button-primary" @click="bulkCopy()" x-text="copied ? 'コピーしました！' : 'まとめてMDコピー'"></button>
  </div>

  <table class="wp-list-table widefat fixed striped hxmd-table">
    <thead>
      <tr>
        <th class="hxmd-col-check"><input type="checkbox" @change="toggleAll($event)"></th>
        <th>タイトル</th>
        <th style="width:100px;">ステータス</th>
        <th style="width:110px;">更新日</th>
        <th style="width:140px;">操作</th>
      </tr>
    </thead>
    <tbody>
      <?php if ( ! $hxmd_pe_query->have_posts() ) : ?>
        <tr><td colspan="5" class="hxmd-empty">投稿がありません。</td></tr>
      <?php else : ?>
        <?php while ( $hxmd_pe_query->have_posts() ) : $hxmd_pe_query->the_post();
          $hxmd_pe_id = get_the_ID();
        ?>
        <tr>
          <td><input type="checkbox" :value="<?php echo (int) $hxmd_pe_id; ?>" x-model="selected"></td>
          <td>
            <a href="<?php echo esc_url( get_edit_post_link( $hxmd_pe_id ) ); ?>"><?php echo esc_html( get_the_title() ?: '（無題）' ); ?></a>
          </td>
          <td><?php echo esc_html( get_post_status() ); ?></td>
          <td><?php echo esc_html( get_the_modified_date( 'Y-m-d' ) ); ?></td>
          <td>
            <button class="button button-small" @click="copyOne(<?php echo (int) $hxmd_pe_id; ?>, $event)">MDコピー</button>
          </td>
        </tr>
        <?php endwhile; wp_reset_postdata(); ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
