<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$hxmd_is_edit = ! empty( $log );
$hxmd_title   = $hxmd_is_edit ? 'ログを編集' : '新規ログ';
$hxmd_today   = date_i18n( 'Y-m-d' );
?>

<div class="wrap">
  <h1><?php echo esc_html( $hxmd_title ); ?></h1>

  <?php if ( $hxmd_is_edit ) : ?>
  <div class="hxmd-md-preview" x-data="hxmdPreview(<?php echo (int) $log['id']; ?>)">
    <div class="hxmd-md-header">
      <strong>MDプレビュー</strong>
      <button class="button" @click="copy()" x-text="copied ? 'コピーしました！' : 'MDをコピー'"></button>
    </div>
    <pre class="hxmd-pre" x-text="md"></pre>
  </div>
  <?php endif; ?>

  <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hxmd-edit-form">
    <?php wp_nonce_field( 'hxmd_save_log' ); ?>
    <input type="hidden" name="action" value="hxmd_save_log">
    <input type="hidden" name="id"     value="<?php echo (int) ( $log['id'] ?? 0 ); ?>">

    <table class="form-table">
      <tr>
        <th><label for="log_date">日付</label></th>
        <td><input type="date" id="log_date" name="log_date" class="regular-text"
              value="<?php echo esc_attr( $log['log_date'] ?? $hxmd_today ); ?>" required></td>
      </tr>
      <tr>
        <th><label for="log_type">種別</label></th>
        <td>
          <select id="log_type" name="log_type">
            <?php foreach ( $types as $hxmd_type_key => $hxmd_type_label ) : ?>
              <option value="<?php echo esc_attr( $hxmd_type_key ); ?>" <?php selected( $log['log_type'] ?? 'memo', $hxmd_type_key ); ?>>
                <?php echo esc_html( $hxmd_type_label ); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <th><label for="contact_name">連絡者</label></th>
        <td><input type="text" id="contact_name" name="contact_name" class="regular-text"
              value="<?php echo esc_attr( $log['contact_name'] ?? '' ); ?>"
              placeholder="山田さん、株式会社〇〇 など"></td>
      </tr>
      <tr>
        <th><label for="subject">件名 <span class="required">*</span></label></th>
        <td><input type="text" id="subject" name="subject" class="large-text"
              value="<?php echo esc_attr( $log['subject'] ?? '' ); ?>"
              placeholder="短い要約を入力" required></td>
      </tr>
      <tr>
        <th><label for="body">詳細（ドラフト）</label></th>
        <td><textarea id="body" name="body" class="large-text" rows="6"
              placeholder="荒書きでOK。聞いた内容・状況をそのまま入力"><?php echo esc_textarea( $log['body'] ?? '' ); ?></textarea></td>
      </tr>
      <tr>
        <th><label for="instruction">対応指示</label></th>
        <td>
          <textarea id="instruction" name="instruction" class="large-text" rows="3"
              placeholder="AIに渡す実行指示。例: モバイルのフォーム送信バグを修正する"><?php echo esc_textarea( $log['instruction'] ?? '' ); ?></textarea>
          <p class="description">AIエージェントに渡す際の指示文。MDエクスポートに含まれます。</p>
        </td>
      </tr>
      <tr>
        <th><label for="priority">優先度</label></th>
        <td>
          <select id="priority" name="priority">
            <option value="high"   <?php selected( $log['priority'] ?? 'medium', 'high' ); ?>>高</option>
            <option value="medium" <?php selected( $log['priority'] ?? 'medium', 'medium' ); ?>>中</option>
            <option value="low"    <?php selected( $log['priority'] ?? 'medium', 'low' ); ?>>低</option>
          </select>
        </td>
      </tr>
      <tr>
        <th><label for="status">ステータス</label></th>
        <td>
          <select id="status" name="status">
            <option value="open"        <?php selected( $log['status'] ?? 'open', 'open' ); ?>>未対応</option>
            <option value="in_progress" <?php selected( $log['status'] ?? 'open', 'in_progress' ); ?>>対応中</option>
            <option value="done"        <?php selected( $log['status'] ?? 'open', 'done' ); ?>>完了</option>
          </select>
        </td>
      </tr>
    </table>

    <p class="submit">
      <button type="submit" class="button button-primary">保存する</button>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=hxmd' ) ); ?>" class="button">← 一覧に戻る</a>
    </p>
  </form>

  <?php if ( $hxmd_is_edit ) : ?>
  <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
        onsubmit="return confirm('このログを削除しますか？')"
        style="margin-top:24px;">
    <?php wp_nonce_field( 'hxmd_delete_log' ); ?>
    <input type="hidden" name="action" value="hxmd_delete_log">
    <input type="hidden" name="id"     value="<?php echo (int) $log['id']; ?>">
    <button type="submit" class="button button-link-delete">このログを削除する</button>
  </form>
  <?php endif; ?>
</div>

<?php if ( $hxmd_is_edit ) : ?>
<script>
function hxmdPreview(id) {
  return {
    md: '読み込み中...',
    copied: false,
    async init() {
      this.md = await this.fetchMd([id]);
    },
    async copy() {
      await hxmdCopyText(this.md);
      this.copied = true;
      setTimeout(() => this.copied = false, 2000);
    },
    async fetchMd(ids) {
      const body = new FormData();
      body.append('action', 'hxmd_get_md');
      body.append('_ajax_nonce', hxmdData.nonce);
      ids.forEach(id => body.append('ids[]', id));
      const res  = await fetch(hxmdData.ajaxUrl, { method: 'POST', body });
      const json = await res.json();
      return json.success ? json.data.md : '取得に失敗しました';
    },
  };
}
</script>
<?php endif; ?>
