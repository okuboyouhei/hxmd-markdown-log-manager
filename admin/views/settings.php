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

  <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="hxmd-settings-form">
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

        <tr class="hxmd-add-row">
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
  <h2>カテゴリ管理</h2>
  <p class="description">プロジェクト・クライアント軸の分類です。空欄でも運用できます。</p>

  <table class="wp-list-table widefat fixed hxmd-settings-table">
    <thead>
      <tr>
        <th>キー（英数字）</th>
        <th>ラベル（表示名）</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <template x-for="(item, index) in categories" :key="'cat' + index">
        <tr>
          <td><input type="text" :name="'categories[' + item.key + ']'"
                     x-model="item.key" placeholder="agu" class="regular-text" form="hxmd-settings-form"
                     pattern="[a-z0-9_]+" title="英小文字・数字・アンダースコアのみ"></td>
          <td><input type="text" :name="'categories[' + item.key + ']'"
                     x-model="item.label" placeholder="AGUサイト" class="regular-text" form="hxmd-settings-form"></td>
          <td><button type="button" class="button button-link-delete" @click="removeCategory(index)">削除</button></td>
        </tr>
      </template>
      <tr class="hxmd-add-row">
        <td colspan="3">
          <button type="button" class="button" @click="addCategory()">+ カテゴリを追加</button>
        </td>
      </tr>
    </tbody>
  </table>

  <hr>
  <h2>HXFE連携</h2>
  <?php if ( defined( 'HXFE_VERSION' ) ) : ?>
    <p class="description">HXFE v<?php echo esc_html( HXFE_VERSION ); ?> を検出しました。フォーム送信を自動的にログとして取り込めます（HXFE v1.4.5以降が必要）。</p>
    <table class="form-table">
      <tr>
        <th>自動取り込み</th>
        <td>
          <label>
            <input type="checkbox" name="hxfe_enabled" value="1" form="hxmd-settings-form"
              <?php checked( get_option( 'hxmd_hxfe_enabled', '0' ), '1' ); ?>>
            HXFEフォームの送信をHXMDログとして自動保存する
          </label>
        </td>
      </tr>
      <tr>
        <th><label for="hxfe_forms">対象フォームID</label></th>
        <td>
          <input type="text" id="hxfe_forms" name="hxfe_forms" class="regular-text" form="hxmd-settings-form"
            value="<?php echo esc_attr( get_option( 'hxmd_hxfe_forms', '' ) ); ?>"
            placeholder="contact, inquiry（カンマ区切り・空欄で全フォーム）">
          <p class="description">空欄の場合、すべてのHXFEフォームが対象になります。</p>
        </td>
      </tr>
      <tr>
        <th><label for="hxfe_log_type">取り込み時の種別</label></th>
        <td>
          <select id="hxfe_log_type" name="hxfe_log_type" form="hxmd-settings-form">
            <?php
            $hxmd_hxfe_current = get_option( 'hxmd_hxfe_log_type', 'email' );
            foreach ( $types as $hxmd_t_key => $hxmd_t_label ) : ?>
              <option value="<?php echo esc_attr( $hxmd_t_key ); ?>" <?php selected( $hxmd_hxfe_current, $hxmd_t_key ); ?>>
                <?php echo esc_html( $hxmd_t_label ); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
    </table>
  <?php else : ?>
    <p class="description">HXFE（HX Form Engine）がインストールされていません。HXFE v1.4.5以降を有効化するとフォーム送信の自動取り込みが利用できます。</p>
  <?php endif; ?>

  <hr>
  <h2>HXRV連携</h2>
  <?php if ( defined( 'HXRV_VERSION' ) ) : ?>
    <p class="description">HXRV v<?php echo esc_html( HXRV_VERSION ); ?> を検出しました。ビジュアルレビューのピンコメントを自動的にログとして取り込めます（HXRV v1.0.1以降が必要）。</p>
    <table class="form-table">
      <tr>
        <th>自動取り込み</th>
        <td>
          <label>
            <input type="checkbox" name="hxrv_enabled" value="1" form="hxmd-settings-form"
              <?php checked( get_option( 'hxmd_hxrv_enabled', '0' ), '1' ); ?>>
            HXRVのピンコメント作成をHXMDログとして自動保存する
          </label>
          <p class="description">ピン本体のみ取り込みます（スレッドへの返信は対象外）。コメント内容は対応指示にも反映されます。</p>
        </td>
      </tr>
      <tr>
        <th><label for="hxrv_log_type">取り込み時の種別</label></th>
        <td>
          <select id="hxrv_log_type" name="hxrv_log_type" form="hxmd-settings-form">
            <?php
            $hxmd_hxrv_current = get_option( 'hxmd_hxrv_log_type', 'memo' );
            foreach ( $types as $hxmd_t2_key => $hxmd_t2_label ) : ?>
              <option value="<?php echo esc_attr( $hxmd_t2_key ); ?>" <?php selected( $hxmd_hxrv_current, $hxmd_t2_key ); ?>>
                <?php echo esc_html( $hxmd_t2_label ); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
    </table>
  <?php else : ?>
    <p class="description">HXRV（AI-Ready Visual Review）がインストールされていません。HXRV v1.0.1以降を有効化するとピンコメントの自動取り込みが利用できます。</p>
  <?php endif; ?>

  <h2>HXSR連携</h2>
  <?php if ( defined( 'HXSR_VERSION' ) ) : ?>
    <p class="description">HXSR v<?php echo esc_html( HXSR_VERSION ); ?> を検出しました。ショートリンクの保存内容を自動的にログとして取り込めます（HXSR v0.1.0以降が必要）。</p>
    <table class="form-table">
      <tr>
        <th>自動取り込み</th>
        <td>
          <label>
            <input type="checkbox" name="hxsr_enabled" value="1" form="hxmd-settings-form"
              <?php checked( get_option( 'hxmd_hxsr_enabled', '0' ), '1' ); ?>>
            HXSRのリンク保存をHXMDログとして自動保存する
          </label>
          <p class="description">同じリンクを編集保存しても重複作成せず、対応する既存ログを更新します。</p>
        </td>
      </tr>
      <tr>
        <th><label for="hxsr_log_type">取り込み時の種別</label></th>
        <td>
          <select id="hxsr_log_type" name="hxsr_log_type" form="hxmd-settings-form">
            <?php
            $hxmd_hxsr_current = get_option( 'hxmd_hxsr_log_type', 'memo' );
            foreach ( $types as $hxmd_t3_key => $hxmd_t3_label ) : ?>
              <option value="<?php echo esc_attr( $hxmd_t3_key ); ?>" <?php selected( $hxmd_hxsr_current, $hxmd_t3_key ); ?>>
                <?php echo esc_html( $hxmd_t3_label ); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
    </table>
  <?php else : ?>
    <p class="description">HXSR（Smart Redirecter）がインストールされていません。HXSR v0.1.0以降を有効化するとショートリンクの自動取り込みが利用できます。</p>
  <?php endif; ?>

  <hr>
  <h2>PHPフィルターでの拡張</h2>
  <p class="description">functions.php でも種別を追加できます。</p>
  <pre class="hxmd-pre" style="background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:6px;">add_filter( 'hxmd_log_types', function( $types ) {
    $types['slack'] = 'Slack';
    $types['visit'] = '訪問';
    return $types;
} );</pre>
</div>

