=== HXMD — Markdown Log Manager ===
Contributors:      youheiokubo
Tags:              markdown, log, inquiry, ai-ready, htmx
Requires at least: 6.4
Tested up to:      7.0
Requires PHP:      8.1
Stable tag:        1.0.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Collect inquiries and memos, export as AI-ready Markdown. No API required.

== Description ==

**HXMD — Markdown Log Manager** collects phone call notes, emails, meeting logs, and memos in the WordPress admin, then exports them as structured Markdown optimized for AI agents.

**Features**

* No AI API required. Copy the MD output and paste it into any AI tool you choose.
* Universal Markdown output compatible with NotebookLM, Claude, ChatGPT, and more.
* Customizable log types (phone, email, meeting, memo — and your own).
* List view with sort, filter, and keyword search.
* Bulk MD copy for multiple logs (ideal for NotebookLM ingestion).
* Simple UI powered by Alpine.js + htmx.
* Part of the HX Series (HXFE, HXSE, HXRV).

**MD Export Example**

    ## [LOG-003] Phone / 2026-07-09

    - Contact: Yamada-san
    - Priority: High
    - Status: Open
    - Subject: Contact form not submitting
    - Detail: Confirmed on iPhone. Requested fix by next week.
    - Instruction: Fix mobile form submission bug

**WAHX Stack**

HXMD is part of the WAHX Stack (WordPress + Alpine.js + htmx + HX Series).

== Installation ==

1. Upload the plugin to `/wp-content/plugins/hxmd-markdown-log-manager/`
2. Activate the plugin in the WordPress admin
3. Open "HXMD" in the admin menu to start logging

== Frequently Asked Questions ==

= Do I need an AI API key? =

No. HXMD does not include any AI. Copy the exported Markdown and paste it into any AI tool you prefer.

= Can HXMD integrate with HXFE (HX Form Engine)? =

Integration is planned for v1.1.0. Form submissions will be automatically saved as HXMD logs.

= Can I add custom log types? =

Yes. Go to HXMD > Settings to add, edit, or delete custom types. You can also use the `hxmd_log_types` PHP filter.

== Screenshots ==

1. ログ一覧（ソート・フィルター・MDコピー）
2. ログ入力画面
3. MDプレビュー

== Changelog ==

= 1.0.0 =
* 初回リリース

== Upgrade Notice ==

= 1.0.0 =
初回リリースです。
