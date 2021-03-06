=== ProudCity Quick Menu ===

Contributors: proudcity, curtismchale
Tags: Menu, Page, Post, Admin, Quick Menu
Requires at least: 6.0.0
Tested up to: 6.0.0
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

ProudCity Quick Menu plugin allow you to add or remove page / post to multiple or single menu on page create or edit screen.

Your theme MUST support Nav Menus for this plugin to work on your site.

== Description ==
- Easily add Page/Post to  menu On edit or create.
- Also possible to remove page from menu items during content edit screen.
- Add page/post to multiple menu items together.
- You can easily set the Menu positions including parent child relations.
- There is automatic calculation for menu position, so don't afraid to provide a wrong position.
- On deactivate of this plugin will not affect already added items to menu.
- Easy to use user interface.

=== Filters ===

`pcq_allowed_post_types`: Used to add your own post types to the array of allowed post types.

```
function pcq_add_post_types( $post_types ){
    $post_types[] = 'your_custom_post_type';
    return (array) $post_types;
}
add_filter( 'pcq_allowed_post_types', 'pcq_add_post_types' );
```

== Installation ==
1. Upload plugin to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. No more settings available, its plug n play


== Screenshots ==
1. Select Menu
2. Add post/page to Menu
3. Menu item properties
4. Advance settings
5. Remove post/page from menu


== Changelog ==

= 1.2 =

- resolve a issues with variables that were named improperly
- forked original plugin to resolve errors: https://wordpress.org/plugins/wp-quick-menu/

= 1.0 =

* Intial Commit of this plugin.

