# CLAUDE.md — HXMD Development Reference

## プロジェクト概要

**HXMD — Markdown Log Manager**
問い合わせ・メモ・会議ログをAIエージェントが読みやすい構造化MDで管理するWordPressプラグイン。

- API不要・AI非組み込み
- 入力は荒くていい、出力は綺麗なMD
- HXシリーズ第4弾（HXFE・HXSE・HXRVに続く）
- WAHXスタック（WordPress + Alpine.js + htmx + HX Series）の一部
- **htmxは非同梱（v1.4.0で削除）**: HXMDの管理画面はAlpine + fetch() のみで、hx-*属性を使う箇所がなかったため。安易に再追加しないこと。一覧のフィルター等をサーバー駆動スワップ化する場合のみ再検討

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
