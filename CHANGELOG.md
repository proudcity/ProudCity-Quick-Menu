# Changelog

## 2026-04-13

### Security: Restrict Metabox to Capable Users

The quick menu metabox was registered for all users who could edit posts, exposing the admin UI to users who lack menu editing capability. Added a `current_user_can('manage_categories')` check at the top of `wp_quick_menu_add_meta_box()` to match the capability check already in place on all AJAX handlers.

**Files changed**:
- `proudcity-quick-menu.php`

**Changes**:
- `wp_quick_menu_add_meta_box()`: returns early if the current user cannot `manage_categories`

References: https://github.com/proudcity/wp-proudcity/issues/2784

---

### Security: AJAX Rate Limiting

Added transient-based rate limiting to all four AJAX handlers to prevent DoS abuse by logged-in users.

**Files changed**:
- `proudcity-quick-menu.php`

**Changes**:
- Added `const RATE_LIMIT_WINDOW = 10` class constant for the throttle window (seconds)
- Added `check_rate_limit( $action, $limit )` private static helper using a per-user, per-action transient counter
- Applied rate limiting to all four AJAX handlers after nonce and capability checks:
  - `edit_menu_item()` — 2 per 10s
  - `delete_menu_item()` — 2 per 10s
  - `get_menu_items()` — 5 per 10s
  - `update_menu_items()` — 10 per 10s
- Added docblock to `edit_menu_item()`

References: https://github.com/proudcity/wp-proudcity/issues/2784

---

### Security: JavaScript Input Validation

Added client-side validation before AJAX submission to prevent malformed or manipulated DOM data from reaching the server.

**Files changed**:
- `js/pc-quick-menu-script.js`

**Changes**:
- Added `pcq_valid_id()` helper to validate positive integer IDs from data attributes
- `pcq_setup_menu_data()`: skips menu items with missing/invalid `menu-item-db-id`; validates `menu-item-menu-order` with fallback to DOM index
- Delete handler: validates `menu-item-db-id` before firing AJAX; shows user feedback and bails if invalid
- Edit handler: validates `menu-item-object-id` and rejects empty title before firing AJAX; shows user feedback for both failure cases

References: https://github.com/proudcity/wp-proudcity/issues/2784
