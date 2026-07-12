# HXMD — Markdown Log Manager

**問い合わせ・メモ・会議ログをAIエージェントが読みやすい構造化MDで管理するWordPressプラグイン。**

AI API不要。AI非組み込み。入力は荒くていい、出力は綺麗なMD。

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue?logo=wordpress)](https://wordpress.org/plugins/hxmd-markdown-log-manager/)
[![Version](https://img.shields.io/badge/version-1.0.0-green)](https://github.com/okuboyouhei/hxmd-markdown-log-manager/releases)
[![License](https://img.shields.io/badge/license-GPL--2.0-orange)](https://www.gnu.org/licenses/gpl-2.0.html)
[![HX Series](https://img.shields.io/badge/HX%20Series-4th-0F6E56)](https://zenn.dev/youheiokubo)

---

## 概要

HXMD は電話・メール・会議・メモなどのログを WordPress 管理画面で受け取り、AI エージェントが即座に読めるフォーマットの Markdown として保存・出力します。

```markdown
## [LOG-003] 電話 / 2026-07-09

- 連絡者: 山田さん
- 優先度: 高
- ステータス: 未対応
- 件名: LPフォームが送信できない
- 詳細: iPhoneで確認。来週中に対応希望。
- 対応指示: モバイルのフォーム送信バグを修正する
```

このMDをコピーして Claude・ChatGPT・NotebookLM など任意のAIツールに貼り付けるだけ。**APIキー不要。AI非組み込み。**

---

## 特徴

- **AI API不要** — MDをコピーして好きなAIに渡すだけ
- **汎用MD出力** — NotebookLM・Claude・ChatGPT どれにでも持ち込める
- **種別カスタマイズ** — 電話・メール・会議・メモ＋独自種別を追加可能
- **ソート・フィルター・検索** — 一覧画面で素早く目的のログを探せる
- **複数件まとめコピー** — チェックボックスで選択して一括MDエクスポート
- **削除時にデータ削除** — `uninstall.php` でテーブルとオプションをクリーン削除
- **ビルドステップなし** — Alpine.js バンドル済み（htmxは不使用のためv1.4.0で削除）

---

## WAHXスタックでの位置づけ

```
HXFE（フォーム収集） → HXMD（構造化ログ保存） → AI（あなたが選ぶ）
HXRV（現場フィードバック） → HXMD（ログ化）  ※将来連携予定
```

| プラグイン | 役割 | WordPress.org |
|---|---|---|
| [HXFE](https://github.com/okuboyouhei/hxfe-code-first-forms) | フォーム収集 | [hxfe-code-first-forms](https://wordpress.org/plugins/hxfe-code-first-forms/) |
| [HXSE](https://github.com/okuboyouhei/hxse-code-first-search) | 情報検索 | [hxse-code-first-search](https://wordpress.org/plugins/hxse-code-first-search/) |
| [HXRV](https://github.com/okuboyouhei/hxrv-ai-ready-visual-review) | フィードバック収集 | [hxrv-ai-ready-visual-review](https://wordpress.org/plugins/hxrv-ai-ready-visual-review/) |
| **HXMD** | ログ構造化保存 | 審査中 |

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
| 連絡者 | 相手の名前・会社名 |
| 件名 | 短い要約 |
| 詳細 | 荒書きでOK |
| 対応指示 | AIエージェントへの実行指示 |
| 優先度 | 高・中・低 |
| ステータス | 未対応・対応中・完了 |

### 2. MDをコピーする

ログ詳細画面の **「MDをコピー」** ボタン、または一覧画面でチェックボックスを選んで **「まとめてMDコピー」**。

### 3. AIに渡す

コピーしたMDをClaudeやChatGPTのチャット欄に貼り付けるだけ。

---

## 種別のカスタマイズ

### 管理画面から

HXMD → 設定 → 種別を追加・編集

### PHPフィルターから

```php
add_filter( 'hxmd_log_types', function( $types ) {
    $types['slack']  = 'Slack';
    $types['visit']  = '訪問';
    $types['report'] = '報告';
    return $types;
} );
```

---

## 技術スタック

| 項目 | 内容 |
|---|---|
| PHP | 8.1+ |
| WordPress | 6.4+ |
| Alpine.js | 3.15.12（バンドル済み） |
| データ保存 | カスタムテーブル `{prefix}hxmd_logs` |
| ビルドステップ | なし |
| AI API | 使用しない |

---

## ロードマップ

- [x] v1.0.0 — 管理画面でのログ管理・MDエクスポート
- [x] v1.1.0 — HXFEフォームとの自動連携、カテゴリ、期限日、関連URL、スマートペースト
- [ ] v1.2.0 — HXRVとの連携（フィードバックログ化）

---

## ライセンス

GPL-2.0-or-later — [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 作者

**youheiokubo** — Nagoya, Japan  
Engineer & Director at CAMP inc.  
[Zenn](https://zenn.dev/youheiokubo) · [WordPress.org](https://profiles.wordpress.org/youheiokubo/)
