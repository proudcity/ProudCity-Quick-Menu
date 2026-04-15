# Changelog

## 2026-04-15

### Fix: menu_order collision when adding new items; code cleanup

`add_new_item_to_menu` used `count($menu_items)` for the new item's `menu_order`. When existing items did not have perfectly sequential orders, this produced a value already taken by another item, causing MySQL's tie-breaking to return items out of depth-first sequence and breaking `get_nested_menu` nesting. Changed to `max(menu_order) + 1` so the new item always lands after every existing item.

**Files changed:**
- `proudcity-quick-menu.php`

**Changes:**
- `add_new_item_to_menu()`: `menu_order` now uses `max( wp_list_pluck( $menu_items, 'menu_order' ) ) + 1`
- `update_menu_items()`: removed unused `$menu_to_update` and `$updated_items` parameters (AJAX handler reads from `$_POST` directly; parameters were never passed by the hook)
- `sanitize_array_of_css_classes()`: removed unused `$key =>` from foreach
- `wp_quick_menu_meta_box_call_back()`: renamed `$post` → `$_post` (required by hook, intentionally unused)

References: https://github.com/proudcity/wp-proudcity/issues/2776

---

### Fix: Menu layout broken after adding item via quick menu

Identified and fixed three bugs that could cause the menu display to break after using the quick-menu metabox, matching the symptom where opening the WP menu editor and saving without changes would restore the layout.

**Bug 1 (primary cause):** `$nav_menu_item` was overwritten on every loop iteration after `$in_menu` became true, ending up as the last menu item's db_id instead of the current post's. When changing menus, the wrong nav_menu_item was passed to `maybe_remove_old_menu_entry` and deleted, leaving its children with stale `_menu_item_menu_item_parent` values pointing to a nonexistent post. Those orphaned children became invisible in the menu display.

**Bug 2:** `add_new_item_to_menu` never set `_menu_item_menu_item_parent` on newly created nav_menu_items. WordPress expects this meta to be present (set to `0` for top-level items) for correct nesting queries.

**Bug 3:** `get_current_item` rendered the newly added item's `<li>` without a `data-menu-item-parent-id` attribute, unlike all other items rendered by `get_single_item`. Added `data-menu-item-parent-id="0"` to match.

References: https://github.com/proudcity/wp-proudcity/issues/2776

**Files changed**:
- `proudcity-quick-menu.php`

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
