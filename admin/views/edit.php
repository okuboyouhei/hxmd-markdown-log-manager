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
              value="<?php echo esc_attr( $log['log_date'] ?? $hxmd_today ); ?>" required>
          <p class="description">発生日・受信日</p>
        </td>
      </tr>
      <tr>
        <th><label for="start_date">開始日 / 期限日</label></th>
        <td>
          <input type="date" id="start_date" name="start_date"
            value="<?php echo esc_attr( $log['start_date'] ?? '' ); ?>">
          <span class="hxmd-date-sep">〜</span>
          <input type="date" id="due_date" name="due_date"
            value="<?php echo esc_attr( $log['due_date'] ?? '' ); ?>">
          <p class="description">対応の開始日と期限。どちらも任意です。</p>
        </td>
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
        <th><label for="category">カテゴリ</label></th>
        <td>
          <?php $hxmd_categories = HXMD_Categories::get_categories(); ?>
          <?php if ( empty( $hxmd_categories ) ) : ?>
            <span class="description">カテゴリが未登録です。<a href="<?php echo esc_url( admin_url( 'admin.php?page=hxmd-settings' ) ); ?>">設定</a>から追加できます。</span>
          <?php else : ?>
            <select id="category" name="category">
              <option value="">（未分類）</option>
              <?php foreach ( $hxmd_categories as $hxmd_cat_key => $hxmd_cat_label ) : ?>
                <option value="<?php echo esc_attr( $hxmd_cat_key ); ?>" <?php selected( $log['category'] ?? '', $hxmd_cat_key ); ?>>
                  <?php echo esc_html( $hxmd_cat_label ); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p class="description">プロジェクト・クライアント軸の分類</p>
          <?php endif; ?>
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
        <td>
          <div class="hxmd-textarea-toolbar">
            <button type="button" class="button button-small hxmd-md-btn" data-target="body" data-md="wrap" data-marker="**" title="選択範囲を強調"><strong>B</strong></button>
            <button type="button" class="button button-small hxmd-md-btn" data-target="body" data-md="wrap" data-marker="~~" title="選択範囲に取り消し線"><s>S</s></button>
            <button type="button" class="button button-small hxmd-md-btn" data-target="body" data-md="list" data-list="ul" title="箇条書きリスト">• リスト</button>
            <button type="button" class="button button-small hxmd-md-btn" data-target="body" data-md="list" data-list="ol" title="番号付きリスト">1. リスト</button>
            <span class="description">選択してボタンでMD装飾（エクスポートに反映）</span>
          </div>
          <textarea id="body" name="body" class="large-text" rows="6"
              placeholder="荒書きでOK。聞いた内容・状況をそのまま入力"><?php echo esc_textarea( $log['body'] ?? '' ); ?></textarea>
        </td>
      </tr>
      <tr>
        <th><label for="instruction">対応指示</label></th>
        <td>
          <div class="hxmd-textarea-toolbar">
            <button type="button" class="button button-small hxmd-md-btn" data-target="instruction" data-md="wrap" data-marker="**" title="選択範囲を強調"><strong>B</strong></button>
            <button type="button" class="button button-small hxmd-md-btn" data-target="instruction" data-md="wrap" data-marker="~~" title="選択範囲に取り消し線"><s>S</s></button>
            <button type="button" class="button button-small hxmd-md-btn" data-target="instruction" data-md="list" data-list="ul" title="箇条書きリスト">• リスト</button>
            <button type="button" class="button button-small hxmd-md-btn" data-target="instruction" data-md="list" data-list="ol" title="番号付きリスト">1. リスト</button>
          </div>
          <textarea id="instruction" name="instruction" class="large-text" rows="3"
              placeholder="AIに渡す実行指示。例: モバイルのフォーム送信バグを修正する"><?php echo esc_textarea( $log['instruction'] ?? '' ); ?></textarea>
          <p class="description">AIエージェントに渡す際の指示文。MDエクスポートに含まれます。</p>
        </td>
      </tr>
      <tr>
        <th><label for="links">関連URL</label></th>
        <td>
          <textarea id="links" name="links" class="large-text" rows="3"
              placeholder="https://example.backlog.jp/view/PROJ-123 対応チケット&#10;https://docs.google.com/... 議事録"><?php echo esc_textarea( $log['links'] ?? '' ); ?></textarea>
          <p class="description">1行に1URL。URLの後にスペース区切りでメモを付けられます（Backlog課題・GitHub Issue・関連資料など）。</p>
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
        class="hxmd-delete-form" style="margin-top:24px;">
    <?php wp_nonce_field( 'hxmd_delete_log' ); ?>
    <input type="hidden" name="action" value="hxmd_delete_log">
    <input type="hidden" name="id"     value="<?php echo (int) $log['id']; ?>">
    <button type="submit" class="button button-link-delete">このログを削除する</button>
  </form>
  <?php endif; ?>
</div>

