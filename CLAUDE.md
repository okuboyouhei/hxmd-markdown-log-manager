# CLAUDE.md — HXMD Development Reference

## プロジェクト概要

**HXMD — Markdown Log Manager**
問い合わせ・メモ・会議ログをAIエージェントが読みやすい構造化MDで管理するWordPressプラグイン。

- API不要・AI非組み込み
- 入力は荒くていい、出力は綺麗なMD
- HXシリーズ第4弾（HXFE・HXSE・HXRVに続く）
- WAHXスタック（WordPress + Alpine.js + htmx + HX Series）の一部

## ディレクトリ構成

```
hxmd-markdown-log-manager/
├── hxmd-markdown-log-manager.php   # メインファイル・定数・フック登録
├── uninstall.php                    # 削除時にテーブル・全オプションを削除
├── CLAUDE.md                        # このファイル
├── README.md                        # GitHub用
├── readme.txt                       # WordPress.org用
├── docs/
│   ├── ai-reference.md              # AI向け機能サマリー
│   └── llms.txt                     # LLM向け最小概要
├── includes/
│   ├── class-hxmd-db.php            # カスタムテーブル操作・スキーマ管理
│   ├── class-hxmd-log-types.php     # 種別管理（どうやって来たか軸）
│   ├── class-hxmd-categories.php    # カテゴリ管理（何の案件か軸）
│   ├── class-hxmd-markdown.php      # MDエクスポート生成
│   ├── class-hxmd-admin.php         # 管理画面・ハンドラ・Ajax・enqueue
│   └── class-hxmd-hxfe-bridge.php   # HXFEフォーム送信の自動取り込み
├── admin/
│   ├── views/
│   │   ├── list.php                 # ログ一覧（HTMLのみ、JSなし）
│   │   ├── edit.php                 # ログ入力・編集・MDプレビュー（HTMLのみ）
│   │   └── settings.php             # 種別・カテゴリ・HXFE連携設定（HTMLのみ）
│   └── assets/
│       ├── hxmd-admin.css
│       └── hxmd-admin.js            # 全JS（Alpineコンポーネント・ツールバー・ペースト変換）
├── assets/
│   ├── alpine.min.js                # Alpine.js 3.15.12 バンドル
│   └── htmx.min.js                  # htmx 2.0.10 バンドル
└── languages/
```

## 重要な設計ルール（WordPress.orgレビュー対応）

- **viewファイルに `<script>` / `<style>` タグを書かない。** 全JSは `admin/assets/hxmd-admin.js` に集約し `wp_enqueue_script` で読み込む。PHP→JSのデータ受け渡しは `wp_localize_script`（`hxmdData` オブジェクト）。
- **インラインイベントハンドラ（onclick等）を使わない。** `data-*` 属性 + `addEventListener` のデリゲーション方式。
- **スクリプト読み込み順:** `hxmd-admin.js` → `alpine.min.js`（依存指定済み）。Alpineの `x-data` が参照する関数（`hxmdList` / `hxmdPreview` / `hxmdSettings`）はAlpine初期化前にグローバル定義されている必要がある。
- Plugin Check ERROR 0 を維持。phpcs抑制は `class-hxmd-db.php` 冒頭のファイル単位 `phpcs:disable`（理由コメント付き）を踏襲。

## データベース

### テーブル: `{$wpdb->prefix}hxmd_logs`

| カラム | 型 | 備考 |
|---|---|---|
| id | INT AUTO_INCREMENT | |
| log_type | VARCHAR(50) | 種別。デフォルト 'memo' |
| category | VARCHAR(100) | カテゴリ。空可 |
| log_date | DATE | 発生日・受信日 |
| start_date | DATE NULL | 対応開始日（任意） |
| due_date | DATE NULL | 期限日（任意）。期限切れ+未完了は一覧で赤表示 |
| contact_name | VARCHAR(255) | 連絡者 |
| subject | VARCHAR(255) | 件名 |
| body | TEXT | 詳細（MD可） |
| priority | VARCHAR(20) | high / medium / low |
| instruction | TEXT | AI向け対応指示（MD可） |
| links | TEXT | 関連URL。1行1URL、URL後にスペース区切りメモ可 |
| status | VARCHAR(20) | open / in_progress / done |
| source | VARCHAR(50) | manual / hxfe |
| created_at / updated_at | DATETIME | |

### スキーマ管理

- 作成: `register_activation_hook` → `HXMD_DB::create_table()`（dbDelta）
- アップグレード: `admin_init` → `HXMD_DB::maybe_upgrade()`。`hxmd_db_version` オプションと `HXMD_VERSION` を比較し、差異があれば dbDelta 再実行。**カラム追加時はスキーマSQLを書き換えるだけでよい。**

## 分類の2軸

| 軸 | クラス | オプション | フィルター | デフォルト |
|---|---|---|---|---|
| 種別（どうやって来たか） | HXMD_Log_Types | hxmd_custom_types | hxmd_log_types | 電話・メール・会議・メモ |
| カテゴリ（何の案件か） | HXMD_Categories | hxmd_categories | hxmd_categories | なし（空でも運用可） |

## MDエクスポート仕様

```markdown
## [LOG-003] 電話 / 2026-07-09

- 連絡者: 山田さん
- カテゴリ: AGUサイト
- 優先度: 高
- ステータス: 未対応
- 開始日: 2026-07-13
- 期限日: 2026-07-17
- 件名: LPフォームが送信できない
- 最終更新: 2026-07-10 15:02
- 詳細: iPhoneで確認。**来週中に対応希望**。

### 対応指示

1. モバイルのフォーム送信バグを修正
2. ~~PC側の調査~~（不要と判明）

### 関連URL

- [対応チケット](https://example.backlog.jp/view/PROJ-123)
```

ルール:
- 空フィールドは出力しない
- 詳細・対応指示が**複数行なら `### セクション` 形式**、1行なら `- ラベル: 値`（`render_text_field()`）
- 関連URLは `render_links()` でMDリンクリスト化（`URL メモ` → `[メモ](URL)`）
- 優先度・ステータスは日本語ラベル変換
- 複数件は `# HXMD Log Export` + `Generated:` + `Total:` ヘッダー付き

## フロントJS（hxmd-admin.js）の構成

| 関数 | 役割 |
|---|---|
| hxmdCopyText(text) | クリップボードコピー。非HTTPS環境はexecCommandフォールバック |
| hxmdToggleWrap(id, marker) | 選択範囲を `**` / `~~` で囲む・外す（トグル） |
| hxmdToggleList(id, type) | 選択行を ul/ol リスト化・解除（トグル、行単位拡張） |
| hxmdTsvToMdTable(text) | タブ区切り→MDテーブル。全行タブありのみ表と判定 |
| hxmdHtmlToMd(html) | Googleドキュメント等のHTML→MD。h1-h3/太字/取り消し線/ネストリスト/表対応。font-weight:700のspan太字も検出 |
| hxmdEnableTablePaste(id) | ペースト監視。HTML構造→表→通常の優先順で確認ダイアログ付き変換 |
| hxmdList() | Alpine: 一覧（選択・一括コピー） |
| hxmdPreview(id) | Alpine: MDプレビュー（Ajax取得・コピー） |
| hxmdSettings() | Alpine: 設定画面（hxmdData.customTypes / categories から初期化） |

## HXFE連携（class-hxmd-hxfe-bridge.php）

- HXFE v1.4.5以降の `hxfe_after_submit` アクション（`$form_id, $values, $schema`）を購読
- `plugins_loaded` で `HXFE_VERSION` 定義チェック後に登録（HXFE無しでも安全）
- 設定: `hxmd_hxfe_enabled`（'0'/'1'）、`hxmd_hxfe_forms`（カンマ区切り、空=全フォーム）、`hxmd_hxfe_log_type`
- フィールドマッピングは候補キー方式（name/your-name/onamae等）、`hxmd_hxfe_field_mapping` フィルターで上書き可
- マッピング外のフィールドは「その他のフィールド」として本文に追記（情報を欠落させない）
- 取り込みログは `source = 'hxfe'`、一覧で紫バッジ表示

## Ajax エンドポイント

- Action: `hxmd_get_md`（POST）
- 認証: nonce `hxmd_nonce` + `manage_options`
- Params: `ids[]`、1件なら render_single、複数なら render_bulk を返す

## フロントエンドライブラリ

| ライブラリ | バージョン | 備考 |
|---|---|---|
| Alpine.js | 3.15.12 | defer、hxmd-admin.jsに依存 |
| htmx | 2.0.10 | defer |

バンドル済み・CDN不使用・HXMD管理画面のみ読み込み（`enqueue_assets` のフックチェック）。

## やってはいけないこと

- AI APIの組み込み
- viewファイルへの `<script>` / `<style>` / インラインイベントハンドラの記述
- フロントエンド（非管理画面）へのアセット出力
- wp_posts / カスタム投稿タイプの使用（カスタムテーブルのみ）
- Plugin Check ERROR の発生

## HXシリーズ関連プラグイン

| プラグイン | 役割 | スラッグ |
|---|---|---|
| HXFE | フォーム収集 | hxfe-code-first-forms |
| HXSE | 情報検索 | hxse-code-first-search |
| HXRV | フィードバック収集 | hxrv-ai-ready-visual-review |
| HXMD | ログ構造化保存 | hxmd-markdown-log-manager |

## HXRV連携（class-hxmd-hxrv-bridge.php）v1.1.0〜

- HXRV v1.0.1以降の `hxrv_after_comment_created` アクション（`$id, $comment`）を購読
- `plugins_loaded` で `HXRV_VERSION` 定義チェック後に登録（HXRV無しでも安全）
- **ピン本体のみ取り込む**。スレッド返信（`parent_id` あり）は除外
- 設定: `hxmd_hxrv_enabled`（'0'/'1'）、`hxmd_hxrv_log_type`（デフォルト 'memo'）
- マッピング:
  - content → body（＋対象要素セレクタを追記）**および instruction**（ピンは修正指示そのものなので対応指示にもコピー）
  - content先頭行（40字まで）→ subject
  - page_url → links（「対象ページ」ラベル付き）
  - author_name → contact_name
  - source = 'hxrv'（一覧で緑バッジ #0F6E56）

## HX連携の共通パターン

HXFE / HXRV とも同じBridge設計を踏襲している。新しいHXプラグインと連携する場合もこのパターンに従うこと:

1. 相手プラグイン側に `{prefix}_after_{event}` アクションフックを追加（存在しなければ）
2. HXMDに `class-hxmd-{plugin}-bridge.php` を作成
3. `plugins_loaded` で相手のVERSION定数チェック → フック購読
4. 設定画面に有効化チェックボックス + 種別選択を追加
5. `source` カラムに識別子、一覧にブランド色バッジ
6. uninstall.php にオプション削除を追加

## 投稿エクスポート（class-hxmd-post-export.php）v1.2.0〜

### 概要

投稿・固定ページ・カスタム投稿タイプを、AI可読な構造化MDに変換してコピーできる機能。「この記事をリライトして」「サイトの全お知らせをNotebookLMに」という用途。hxmd_logsテーブルとは無関係の独立機能（投稿をログとして取り込まない設計判断。データモデルを濁さない）。

### 入口は2つ

1. **HXMDメニュー「投稿エクスポート」**（admin.php?page=hxmd-post-export）— 投稿タイプセレクト + 検索 + 一覧から選択してMDコピー（1件 / 複数一括）
2. **行アクション「HXMD: MD」** — 全公開投稿タイプの一覧画面（edit.php）に表示。リンク先はエクスポート画面（該当投稿タイプ + タイトル検索済み状態）。post_row_actions / page_row_actions フィルターで追加

### 対象投稿タイプ

get_post_types( [ 'public' => true ] ) で動的取得（attachmentを除く）。カスタム投稿タイプは登録されていれば自動で対象になる。追加コード不要。

### MDフォーマット（render_post）

    # 投稿タイトル

    - URL: {permalink}
    - 投稿タイプ: {ラベル表示。内部名ではない}
    - 公開日: Y-m-d
    - 更新日: Y-m-d H:i
    - {タクソノミーラベル}: {ターム名, ...}   ← 全公開タクソノミーを自動列挙
    - ステータス: {日本語ラベル}

    ---

    本文MD

複数件は render_bulk が「# HXMD Post Export」+ Generated: + Total: ヘッダー付きで結合。

### HTML→MD変換（サーバーサイド）

- apply_filters( 'the_content', ... ) でレンダリング後HTMLを取得 → DOMDocumentで変換。**Gutenbergブロックはレンダリング後を変換するのでブロック種別に依存しない**
- この行はPlugin Checkの NonPrefixedHooknameFound に誤検知される（コアフィルターの適用であって新規フック定義ではない）。理由コメント付きphpcs:ignoreで抑制済み。**削除しないこと**
- DOMDocumentのUTF-8対策: <?xml encoding="UTF-8"?> プリアンブル + ルートdivラップ方式
- 対応タグ: h1-h6 / p / strong / b / em / i / s / del / a / img / code / pre / ul / ol（ネスト対応）/ table / blockquote / hr / br / figure / figcaption
- v1.1.0のJS版変換（hxmdHtmlToMd: Googleドキュメント貼り付け用）とは**別実装**。JS版はクリップボードHTML、PHP版は投稿レンダリングHTMLが対象。仕様変更時は両方の同期を検討すること

### Ajax

- Action: hxmd_get_post_md（POST、nonce hxmd_nonce + manage_options）
- Params: ids[]。1件なら render_post、複数なら render_bulk

## 管理画面URLの注意（v1.2.1の教訓）

admin.php?page=... のURLに `post_type` や `s` などのWP予約クエリパラメータを含めてはいけない。`post_type` は `$typenow` に影響して親メニュー解決が edit.php 側に化け、「Cannot load {page}」エラーになる。プラグイン独自のパラメータ名（`hxmd_pt` / `hxmd_s`）を使うこと。なお WP_Query の引数の 'post_type' / 's' はURLと無関係なのでそのままで正しい。
