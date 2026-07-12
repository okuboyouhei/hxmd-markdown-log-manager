# HXMD — Markdown Log Manager

**問い合わせ・メモ・会議ログをAIエージェントが読みやすい構造化MDで管理するWordPressプラグイン。**

AI API不要。AI非組み込み。入力は荒くていい、出力は綺麗なMD。

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue?logo=wordpress)](https://wordpress.org/plugins/hxmd-markdown-log-manager/)
[![Version](https://img.shields.io/badge/version-1.4.0-green)](https://github.com/okuboyouhei/hxmd-markdown-log-manager/releases)
[![License](https://img.shields.io/badge/license-GPL--2.0-orange)](https://www.gnu.org/licenses/gpl-2.0.html)
[![HX Series](https://img.shields.io/badge/HX%20Series-4th-0F6E56)](https://zenn.dev/youheiokubo)

---

## 概要

HXMD は電話・メール・会議・メモなどのログを WordPress 管理画面で受け取り、AI エージェントが即座に読めるフォーマットの Markdown として保存・出力します。

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

### 関連URL

- [対応チケット](https://example.backlog.jp/view/PROJ-123)
```

このMDをコピーして Claude・ChatGPT・NotebookLM など任意のAIツールに貼り付けるだけ。**APIキー不要。AI非組み込み。**

---

## 特徴

- **AI API不要** — MDをコピーして好きなAIに渡すだけ（無料プランのAIでも使える）
- **汎用MD出力** — NotebookLM・Claude・ChatGPT どれにでも持ち込める
- **2軸の分類** — 種別（電話・メール・会議・メモ＋カスタム）× カテゴリ（プロジェクト・クライアント軸）
- **開始日・期限日** — 期限切れの未対応ログは一覧で赤表示。簡易タスクボードとして機能
- **関連URL** — Backlog課題・GitHub Issue・議事録を1ログに複数紐付け。MDではリンクリスト化
- **スマートペースト** — Excel/スプレッドシートの表 → MDテーブル、Googleドキュメント → 見出し・太字・リスト・表を保持したままMD化
- **MDツールバー** — 強調・取り消し線・リスト（トグル対応）
- **HXFE連携** — フォーム送信を自動でログ化（HXFE v1.4.5+）
- **HXRV連携** — ビジュアルレビューのピンを自動でログ化（HXRV v1.0.1+）
- **投稿エクスポート** — 投稿・固定ページ・カスタム投稿タイプを構造化MDに変換（v1.2.0）
- **一括操作** — 複数選択してまとめてMDコピー / まとめて削除（v1.3.0）
- **更新日時ソート** — 「AIに投げた後に更新されたログ」を見つけて再エクスポート
- **削除時にデータ削除** — `uninstall.php` でテーブルとオプションをクリーン削除
- **ビルドステップなし** — Alpine.js バンドル済み（htmxは不使用のためv1.4.0で削除）

---

## WAHXスタックでの位置づけ

```
HXFE（フォーム送信）──┐
                      ├→ HXMD（構造化ログ）→ MDコピー → AI（あなたが選ぶ）
HXRV（レビューピン）──┘
投稿・固定ページ・CPT ─→ HXMD 投稿エクスポート ─┘
```

| プラグイン | 役割 | WordPress.org |
|---|---|---|
| [HXFE](https://github.com/okuboyouhei/hxfe-code-first-forms) | フォーム収集 | [hxfe-code-first-forms](https://wordpress.org/plugins/hxfe-code-first-forms/) |
| [HXSE](https://github.com/okuboyouhei/hxse-code-first-search) | 情報検索 | [hxse-code-first-search](https://wordpress.org/plugins/hxse-code-first-search/) |
| [HXRV](https://github.com/okuboyouhei/hxrv-ai-ready-visual-review) | フィードバック収集 | [hxrv-ai-ready-visual-review](https://wordpress.org/plugins/hxrv-ai-ready-visual-review/) |
| **HXMD** | ログ構造化保存 | [hxmd-markdown-log-manager](https://wordpress.org/plugins/hxmd-markdown-log-manager/) |

---

## インストール

### WordPress.org から（推奨）

WordPress管理画面 → プラグイン → 新規追加 → `HXMD` で検索

### 手動インストール

```bash
# リリースページからzipをダウンロードして
# /wp-content/plugins/ に展開して有効化
```

---

## 使い方

### 1. ログを入力する

管理画面 → **HXMD → 新規ログ** から入力。荒書きでOK。

| フィールド | 説明 |
|---|---|
| 日付 | 発生日・受信日 |
| 種別 | 電話・メール・会議・メモ（カスタマイズ可） |
| カテゴリ | プロジェクト・クライアント軸の分類（任意） |
| 開始日 / 期限日 | 対応期間（任意。期限切れは一覧で赤表示） |
| 連絡者 | 相手の名前・会社名 |
| 件名 | 短い要約 |
| 詳細 | 荒書きでOK。ツールバーで強調・リスト、表・Googleドキュメントの貼り付けは自動MD変換 |
| 対応指示 | AIエージェントへの実行指示 |
| 関連URL | 1行1URL。Backlog・GitHub Issue・議事録など（URLの後にスペース区切りでメモ可） |
| 優先度 | 高・中・低 |
| ステータス | 未対応・対応中・完了 |

### 2. MDをコピーする

ログ詳細画面の **「MDをコピー」** ボタン、または一覧画面でチェックボックスを選んで **「まとめてMDコピー」**。

### 3. AIに渡す

コピーしたMDをClaudeやChatGPTのチャット欄に貼り付けるだけ。NotebookLMのソースに登録すれば「期限が近い未対応タスクは？」と横断質問もできます。

---

## HXFE / HXRV との自動連携

HXMD → 設定 から連携をONにできます。

- **HXFE**（v1.4.5以降）: フォーム送信が自動でログ化。名前・件名・本文は自動マッピングされ、それ以外のフィールドも本文に保全されます。対象フォームの絞り込み可
- **HXRV**（v1.0.1以降）: レビューのピンコメントが自動でログ化。コメントは対応指示にも反映され、対象要素のセレクタ・ページURLが付きます

取り込まれたログには一覧でソースバッジ（HXFE / HXRV）が表示されます。

---

## 投稿エクスポート（v1.2.0〜）

HXMD → 投稿エクスポート、または各投稿一覧の行アクション **「HXMD: MD」** から。

- 投稿・固定ページ・**カスタム投稿タイプ**（公開設定されている全タイプ）に対応
- タイトル・URL・日付・全タクソノミー・ステータス + 本文を構造化MD化
- 本文は見出し・リスト・表・画像・リンクを保持（Gutenbergブロック非依存）
- 「この記事をAIにリライトさせたい」「サイトの全お知らせをNotebookLMに」という用途に

---

## 種別・カテゴリのカスタマイズ

### 管理画面から

HXMD → 設定 → 種別 / カテゴリを追加・編集

### PHPフィルターから

```php
add_filter( 'hxmd_log_types', function( $types ) {
    $types['slack']  = 'Slack';
    $types['visit']  = '訪問';
    return $types;
} );

add_filter( 'hxmd_categories', function( $cats ) {
    $cats['agu'] = 'AGUサイト';
    return $cats;
} );
```

---

## 技術スタック

| 項目 | 内容 |
|---|---|
| PHP | 8.1+ |
| WordPress | 6.4+ |
| Alpine.js | 3.15.12（バンドル済み） |
| データ保存 | カスタムテーブル `{prefix}hxmd_logs`（スキーマ自動アップグレード対応） |
| ビルドステップ | なし |
| AI API | 使用しない |

---

## 更新履歴

- **v1.4.0** — 未使用だったhtmxバンドルを削除（管理画面はAlpine + fetchのみ。機能変更なし）
- **v1.3.0** — まとめて削除、投稿エクスポート画面の不具合修正
- **v1.2.0** — 投稿エクスポート（投稿・固定ページ・カスタム投稿タイプ → 構造化MD）
- **v1.1.0** — HXRV連携（レビューピンの自動ログ化）
- **v1.0.0** — 初回リリース（ログ管理・MDエクスポート・HXFE連携・カテゴリ・期限日・関連URL・スマートペースト）

---

## ライセンス

GPL-2.0-or-later — [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 作者

**youheiokubo** — Nagoya, Japan  
Engineer & Director at CAMP inc.  
[Zenn](https://zenn.dev/youheiokubo) · [WordPress.org](https://profiles.wordpress.org/youheiokubo/)
