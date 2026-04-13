# Changelog

## 2026-04-13

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
