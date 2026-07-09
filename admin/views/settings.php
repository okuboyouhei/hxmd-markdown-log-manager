<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GETパラメータの表示フラグのためnonce不要
$hxmd_settings_saved = ! empty( $_GET['saved'] );
// phpcs:enable
?>

<div class="wrap" x-data="hxmdSettings()">
  <h1>HXMD 設定</h1>

  <?php if ( $hxmd_settings_saved ) : ?>
    <div class="notice notice-success is-dismissible"><p>設定を保存しました。</p></div>
  <?php endif; ?>

  <h2>種別管理</h2>
  <p class="description">電話・メール・会議・メモはデフォルト種別です。カスタム種別を追加・編集できます。</p>

  <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <?php wp_nonce_field( 'hxmd_save_settings' ); ?>
    <input type="hidden" name="action" value="hxmd_save_settings">

    <table class="wp-list-table widefat fixed hxmd-settings-table">
      <thead>
        <tr>
          <th>キー（英数字）</th>
          <th>ラベル（表示名）</th>
          <th>種別</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $hxmd_defaults = [ 'phone' => '電話', 'email' => 'メール', 'meeting' => '会議', 'memo' => 'メモ' ];
        foreach ( $hxmd_defaults as $hxmd_def_key => $hxmd_def_label ) : ?>
        <tr class="hxmd-row-default">
          <td><code><?php echo esc_html( $hxmd_def_key ); ?></code></td>
          <td><?php echo esc_html( $hxmd_def_label ); ?></td>
          <td><span class="hxmd-badge">デフォルト</span></td>
          <td><span class="description">変更不可</span></td>
        </tr>
        <?php endforeach; ?>

        <template x-for="(item, index) in customTypes" :key="index">
          <tr>
            <td><input type="text" :name="'custom_types[' + item.key + ']'"
                       x-model="item.key" placeholder="visit" class="regular-text"
                       pattern="[a-z0-9_]+" title="英小文字・数字・アンダースコアのみ"></td>
            <td><input type="text" :name="'custom_types[' + item.key + ']'"
                       x-model="item.label" placeholder="訪問" class="regular-text"></td>
            <td><span class="hxmd-badge hxmd-badge-custom">カスタム</span></td>
            <td><button type="button" class="button button-link-delete" @click="removeType(index)">削除</button></td>
          </tr>
        </template>

        <tr>
          <td colspan="4">
            <button type="button" class="button" @click="addType()">+ 種別を追加</button>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="submit">
      <button type="submit" class="button button-primary">設定を保存</button>
    </p>
  </form>

  <hr>
  <h2>PHPフィルターでの拡張</h2>
  <p class="description">functions.php でも種別を追加できます。</p>
  <pre class="hxmd-pre" style="background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:6px;">add_filter( 'hxmd_log_types', function( $types ) {
    $types['slack'] = 'Slack';
    $types['visit'] = '訪問';
    return $types;
} );</pre>
</div>

<script>
function hxmdSettings() {
  const saved = <?php
    $hxmd_custom = get_option( 'hxmd_custom_types', [] );
    $hxmd_items  = [];
    if ( is_array( $hxmd_custom ) ) {
      foreach ( $hxmd_custom as $hxmd_k => $hxmd_v ) {
        $hxmd_items[] = [ 'key' => $hxmd_k, 'label' => $hxmd_v ];
      }
    }
    echo wp_json_encode( $hxmd_items );
  ?>;
  return {
    customTypes: saved,
    addType()     { this.customTypes.push({ key: '', label: '' }); },
    removeType(i) { this.customTypes.splice(i, 1); },
  };
}
</script>
