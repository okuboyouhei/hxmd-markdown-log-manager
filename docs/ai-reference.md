# HXMD — AI Reference

## What is HXMD?

HXMD is a WordPress plugin that collects inquiries, memos, and meeting logs,
and exports them as structured Markdown optimized for AI agents.
No AI API is embedded. Users copy the MD output and paste it into their AI tool of choice.

## Plugin slug
hxmd-markdown-log-manager

## Version
1.0.0

## Key files

| File | Role |
|---|---|
| hxmd-markdown-log-manager.php | Entry point, constants, hooks |
| includes/class-hxmd-db.php | Custom table CRUD |
| includes/class-hxmd-log-types.php | Log type management |
| includes/class-hxmd-markdown.php | MD export rendering |
| includes/class-hxmd-admin.php | Admin menu, form handlers, Ajax |
| admin/views/list.php | Log list with sort/filter/copy |
| admin/views/edit.php | Log input/edit + MD preview |
| admin/views/settings.php | Custom type management |

## Database

Table: `{prefix}hxmd_logs`

Columns: id, log_type, log_date, contact_name, subject, body,
         priority, instruction, status, source, created_at, updated_at

Created via dbDelta() on plugin activation.

## Log types

Default (non-editable): phone, email, meeting, memo
Custom: stored in wp_options key `hxmd_custom_types` (array)
PHP filter: `hxmd_log_types`

## MD output format

### Single log

    ## [LOG-{id}] {type_label} / {log_date}

    - 連絡者: {contact_name}
    - 優先度: {priority_label}
    - ステータス: {status_label}
    - 件名: {subject}
    - 詳細: {body}
    - 対応指示: {instruction}

### Bulk export

    # HXMD Log Export
    Generated: {date}
    Total: {n} 件

    ---

    ## [LOG-001] ...

    ---

## Priority values

| Internal | Label |
|---|---|
| high | 高 |
| medium | 中 |
| low | 低 |

## Status values

| Internal | Label |
|---|---|
| open | 未対応 |
| in_progress | 対応中 |
| done | 完了 |

## Ajax endpoint

Action: `hxmd_get_md`
Method: POST
Auth: nonce (`hxmd_nonce`) + `manage_options` capability
Params: `ids[]` (array of int)
Returns: `{ success: true, data: { md: "..." } }`

## Security

- All inputs sanitized via sanitize_text_field / sanitize_textarea_field
- All DB queries use $wpdb->prepare()
- Nonce verified on all form submissions and Ajax requests
- Capability check: manage_options on all admin pages and handlers

## Frontend libraries

| Library | Version | Loading |
|---|---|---|
| Alpine.js | 3.15.12 | defer |

Both bundled locally. Loaded on HXMD admin pages only.

## What NOT to do

- Do not add AI API calls
- Do not load assets on front-end pages
- Do not use wp_posts or custom post types — use the custom table only
- Do not break Plugin Check (target: ERROR 0)
