=== HXMD — Markdown Log Manager ===
Contributors:      youheiokubo
Tags:              markdown, log, inquiry, ai-ready, alpine
Requires at least: 6.4
Tested up to:      7.0
Requires PHP:      8.1
Stable tag:        1.4.2
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
* Simple UI powered by Alpine.js.
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

HXMD is part of the WAHX Stack (WordPress + Alpine.js + htmx + HX Series). HXMD itself uses Alpine.js only — htmx is not needed for its admin UI.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/hxmd-markdown-log-manager/`
2. Activate the plugin in the WordPress admin
3. Open "HXMD" in the admin menu to start logging

== Frequently Asked Questions ==

= Do I need an AI API key? =

No. HXMD does not include any AI. Copy the exported Markdown and paste it into any AI tool you prefer.

= Can HXMD integrate with HXFE (HX Form Engine)? =

Yes. With HXFE v1.4.5+, enable auto-capture in HXMD > Settings. Form submissions are automatically saved as HXMD logs.

= Can I add custom log types? =

Yes. Go to HXMD > Settings to add, edit, or delete custom types. You can also use the `hxmd_log_types` PHP filter.

== Screenshots ==

1. ログ一覧（ソート・フィルター・MDコピー）
2. ログ入力画面
3. MDプレビュー

== Changelog ==

= 1.4.2 =
* Added: HXSR — Smart Redirecter integration bridge. When HXSR is active, saving a short link can be captured into HXMD as a Markdown log (opt-in via Settings). Editing the same link updates the existing log instead of creating duplicates. The log records the short URL, redirect type, current destination, access count, and any scheduled redirects. Works standalone — nothing happens if HXSR is not installed
* Added: "HXSR" source badge in the log list

= 1.4.1 =
* Improved: the HXRV bridge now captures the Before / After fields added in HXRV 1.2.0. When a pinned comment includes a current/expected pair, it is folded into the log's 詳細 (body) and 対応指示 (instruction) as a "Before（現状）→ After（あるべき姿）" block. Backward compatible — older HXRV without these fields is unaffected

= 1.4.0 =
* Removed: bundled htmx — HXMD's admin UI is Alpine.js + fetch() only and never used htmx attributes. Removing the unused library reduces the plugin size and load. No functional change

= 1.3.0 =
* Added: Bulk delete — select multiple logs and delete them at once (confirmation dialog, nonce-protected)

= 1.2.1 =
* Fixed: Post Export page failed to load ("Cannot load hxmd-post-export") when opened from the row action — the reserved `post_type` query parameter broke WP admin menu resolution. Replaced with plugin-specific parameter names

= 1.2.0 =
* Added: Post Export — select posts/pages and copy them as structured Markdown (title, URL, dates, categories, tags + full body converted from HTML to MD). Ideal for NotebookLM ingestion or AI-assisted rewriting
* Added: Server-side HTML-to-Markdown converter (headings, bold, strikethrough, italic, nested lists, tables, links, images, blockquotes, code blocks)

= 1.1.0 =
* Added: HXRV integration — automatically capture visual review pin comments as logs (requires HXRV v1.0.1+)
* Added: HXRV integration settings (enable/disable, log type) and HXRV source badge in log list

= 1.0.0 =
* Initial release
* Log management with types, categories, priorities, statuses, start/due dates
* Sortable, filterable log list with overdue highlighting
* MD export (single & bulk copy) optimized for AI agents
* MD toolbar: bold, strikethrough, bullet/numbered lists
* Smart paste: Excel/Sheets tables and Google Docs rich text convert to Markdown
* Related URLs field for Backlog/GitHub issue linking
* HXFE integration: auto-capture form submissions (requires HXFE v1.4.5+)
* DB schema auto-upgrade routine

== Upgrade Notice ==

= 1.0.0 =
Initial release.
