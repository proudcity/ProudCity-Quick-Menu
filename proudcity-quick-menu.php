<?php

/*
  Plugin Name: ProudCity Quick Menu
  Description: Add page/post to menu on create or edit screen
  Version: 1.2
  Tested up to: 6.0.0
  Author: proudcity, curtismchale
  Author URI: https://proudcity.com
  License: GPLv2 or later
 */

/*
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

if (!class_exists('PC_Quick_Menu')) {

    class PC_Quick_Menu {

        /**
         * class constructor
         */
        function __construct() {
            add_action('admin_enqueue_scripts', array($this, 'wp_quick_menu_add_css_js'));
            add_action('add_meta_boxes', array($this, 'wp_quick_menu_add_meta_box'));
//            add_action('save_post', array($this, 'wp_quick_menu_save_meta_box_data'));

            // ajax actions
            add_action( 'wp_ajax_pc_quick_get_menu_items', array( $this, 'get_menu_items' ) );
            add_action( 'wp_ajax_pcq_update_menu', array( $this, 'update_menu_items' ) );
            add_action( 'wp_ajax_pcq_delete_menu_item', array( $this, 'delete_menu_item' ) );
            add_action( 'wp_ajax_pcq_edit_menu_item', array( $this, 'edit_menu_item' ) );

        }

        public static function edit_menu_item(){

            check_ajax_referer( 'pc_quick_menu_nonce', 'security' );

            $success = false;
            $message = 'The menu item was NOT edited. Please contact a site administrator.';

            $update_args = array(
                'ID' => absint( $_POST['post_id'] ),
                'post_title' => esc_attr( $_POST['item_title'] ),
            );

            $updated = wp_update_post( $update_args );


            if ( ! is_wp_error( $updated ) ){
                $success = true;
                $message = 'Menu Item was updated';
            }

            $data = array(
                'success' => (bool) $success,
                'message' => $message,
            );

            wp_send_json_success( $data );


        } // edit_menu_item

        /**
         * Deletes a menu item when the delete butten is pressed
         *
         * @since 1.2
         *
         * @uses    check_ajax_referer()                        ajax security
         * @uses    wp_delete_post()                            removes a post and all the metadata that goes with it
         * @uses    absint()                                    no negative numbers
         * @uses    wp_send_json_success()                      returns the php to our json thing for ajax
         */
        public static function delete_menu_item(){

            check_ajax_referer( 'pc_quick_menu_nonce', 'security' );

            $post_id = $_POST['post_id'];

            $success = false;
            $message = 'The menu item was NOT deleted. Please contact a site administrator.';

            $deleted = wp_delete_post( absint( $post_id ), true );

            if ( false !== $deleted || null !== $deleted ){
                $success = true;
                $message = 'Menu item was removed.';
            }

            $data = array(
                'success' => (bool) $success,
                'message' => $message,
            );

            wp_send_json_success( $data );

        }

        /**
         * Updates the menu items when we change their order
         *
         * @since 1.2
         *
         * @param   int         $menu_to_update         optional            The ID of the menu to update
         * @param   array       $updated_items          optional            Array of menu items with their data
         * @uses    check_ajax_referer()                                    Ajax security stuff
         * @uses    absint()                                                no negative numbers
         * @uses    intval()                                                make sure it's a number
         * @uses    wp_update_post()                                        Updates a post given args with post_id
         * @uses    wp_send_json_success()                                  sends our response back to the jquery request
         */
        public static function update_menu_items( $menu_to_update = '', $updated_items = '' ){

            check_ajax_referer( 'pc_quick_menu_nonce', 'security' );

            $updated = array();

            foreach( $_POST['menu-items'] as $item ){
                $update_args = array(
                    'ID' => absint( $item['menu-item-db-id'] ),
                    'menu_order' => intval( $item['menu-item-position'] ),
                    'post_excerpt' => 'nope',
                );

                $updated[] = wp_update_post( $update_args );

                if ( ! in_array( WP_ERROR, $updated ) ){
                    $saved = true;
                }

            } // foreach

            if ( ! empty( $saved ) ){
                $success = true;
                $message = 'Menu Updated';
            } else {
                $success = false;
                $message = 'Menu NOT Updated. Please contact a site administrator.';
            }

            $data = array(
                'success' => (bool) $success,
                'message' => $message,
            );

            wp_send_json_success( $data );
        }

        /**
         * Returns the items in a menu given the Menu ID
         *
         * @since 1.2
         *
         * @todo this needs to loop through building our menus so that we can add an item to multiple menus
         *
         * @uses    wp_get_nav_menu_items()                         Returns the items in a menu
         */
        public function get_menu_items(){
            check_ajax_referer( 'pc_quick_menu_nonce', 'security' );

            $html = '';
            $count = 0;
            $menu_items = wp_get_nav_menu_items(
                absint( $_POST['selected_menu'] )
            );

            $in_menu = false;

            // if we changed the menu the item was assigned to then this will delete the old item entry in the menu
            self::maybe_remove_old_menu_entry( $_POST['old_menu_item'] );

            $html .= '<div class="pc-sortable-menu">';
                $html .= '<ol class="pc_quick_menu_item_position">';
                    foreach( $menu_items as $item ){
                        $current_item = absint( $item->object_id ) == absint( $_POST['current_post_id'] ) ? 'current-menu-item' : '';
                        $in_menu = self::is_item_already_in_menu( $in_menu, absint( $item->object_id ), absint( $_POST['current_post_id'] ) );

                        // sets the id of the nav menu item so we can use it to delete the item if we change menus
                        $nav_menu_item = ( true === $in_menu ) ? absint( $item->db_id ) : '';

                        // if this is a child item it gets caught in the get_single_item call
                        if ( ! self::is_item_a_child_item( $item ) ){
                            $html .= self::get_single_item( $item, $current_item, $count );
                        }

                        $count++;

                    }

                    // false because we are adding our current item to a newly selected menu
                    if ( ! $in_menu ){

                        $nav_menu_item = self::add_new_item_to_menu( absint( $_POST['current_post_id'] ), $_POST['selected_menu'], $count );

                        $html .= self::get_current_item( absint( $_POST['current_post_id'] ), absint( $count ), absint( $nav_menu_item ) );
                    }
                $html .= '</ol>';
            $html .= '</div><!-- /.pc-sortable-menu -->';

            // used to tell what the db-id of the item is in case we change the menu so we can delete it from the old one
            $html .= '<div id="old-menu" data-old-menu="'. absint( $nav_menu_item ) .'"></div>';

            $html .= '<p class="pcq-edit-menu-link">For more detailed editing of your menu see - <a target="_blank" href="' . site_url() .'/wp-admin/nav-menus.php?action=edit&menu='. absint( $_POST['selected_menu'] ) .'">Advanced Menu Editing</a></p>';

            $success = true;
            $value = $html;

            $data = array(
                'success' => (bool) $success,
                'value' => $value,
            );

            wp_send_json_success( $data );

        } // get_menu_items

        /**
         * Builds a sub menu. Assumes you've already checked there are menu item children
         *
         * @since 2022-09-22
         * @author Curtis
         * @access private
         *
         * @param   int         $parent_id      required            ID of the parent menu item
         * @param   int         $count          required            The menu position of the item
         * @uses    self::get_child_menu_items()                    Returns objects for child items
         * @return  string      $html                               Built out child menu items
         */
        private static function get_child_menu( $parent_id, $count ){

            $html = '';

            $child_items = self::get_child_menu_items( absint( $parent_id ) );

            $html .= '<ol class="dd-list pc_quick_menu_item_position">';
                foreach( $child_items as $item ){

                    $current_item = null;

                    $html .=  self::get_single_item( $item, $current_item, $count );

                }
            $html .= '</ol>';

            return $html;

        }

        /**
         * Sets up the data format for the item depending on the data type of the item.
         *
         * @since 2022-10-02
         * @author Curtis McHale
         * @access private
         *
         * @param   object      $item           required            The object we want to get data out of
         * @param   ??          $count                              Not even sure we need this
         * @uses    absint()                                        No negative numbers
         * @uses    self::set_object_id()                           Returns the proper object_id after dealing with our 2 data types
         * @uses    self::get_item_title()                          Determines the title of the item
         * @return  array       $updated_items                      The array of items that are updated depending on the data recieved
         */
        private static function setup_item_data( $item, $count ){

            $updated_item = array();

            $object_id = self::set_object_id( absint( $item->object_id ), absint( $item->ID ) );

            $updated_item['db_id'] = absint( $item->ID );
            $updated_item['object_id'] = absint( $object_id );
            $updated_item['page'] = ''; // @todo figure this out
            $updated_item['menu_item_parent'] = absint( $item->menu_item_parent );
            $updated_item['type'] = esc_attr( get_post_meta( absint( $item->ID ), '_menu_item_type', true ) );
            $updated_item['title'] = self::get_item_title( $item->title, $object_id );
            $updated_item['url'] = esc_url( $item->url );
            $updated_item['description'] = esc_attr( $item->description );
            $updated_item['attr_title'] = esc_attr( $item->attr_title );
            $updated_item['target'] = esc_attr( get_post_meta( absint( $item->ID ), '_menu_item_target', true ) );
            $updated_item['classes'] = self::get_menu_item_classes( absint( $item->ID ) );
            $updated_item['xfn'] = get_post_meta( absint( $item->ID ), '_menu_item_xfn', true );
            $updated_item['count'] = absint( $count );

            return (array) $updated_item;

        }

        /**
         * Sets the ID of the object.
         *
         * Nav Menu objects have this set but if we're getting wp_nav_menu results
         * we need to work this out from the post_meta field for the post_type entry.
         *
         * In a default nav menu object the `object_id` corresponds to the post that the nav
         * menu entry is referring to.
         *
         * @since 2022-09-22
         * @author Curtis
         * @access private
         *
         * @param   int         $object_id              required            Object id if we have it
         * @param   int         $item_id                required            ID of the item
         * @uses    get_post_meta()                                         Returns post meta given post_id and param
         * @uses    absint()                                                no negative numbers
         * @return  int         $object_id                                  The worked out object id
         */
        private static function set_object_id( $object_id, $item_id ){

            if ( ! isset( $object_id ) || 0 == $object_id ){
                $object_id = get_post_meta( absint( $item_id ), '_menu_item_object_id', true );
            }

            return absint( $object_id );
        }

        /**
         * Figures out the title.
         *
         * Menu objects have their title site, post objects mean I need to work out the title
         * from the root post time that the navigation item is linking to.
         *
         * @since 2022-09-22
         * @author Curtis
         * @access private
         *
         * @param   string      $item_title         required            Default title setting which may be empty
         * @param   int         $item_id            required            ID of the item we want to get a title for
         * @uses    get_the_title()                                     Returns post title given post_id
         * @return  string      $title                                  The title we have worked out
         */
        private static function get_item_title( $item_title, $item_id ){

            if ( isset( $item_title ) && ! empty( $item_title ) ){
                $title = $item_title;
            } else {
                $title = get_the_title( absint( $item_id ) );
            }

            return esc_attr( $title );
        }

        /**
         * Returns child menu items
         *
         * @since 2022-09-22
         * @author Curtis
         * @access private
         *
         * @param   int         $parent_id          required            ID of the menu item we want children for
         * @uses    absint()                                            No negative numbers
         * @uses    get_posts()                                         Returns posts given args
         * @return  object      $children                               Array of post objects
         */
        private static function get_child_menu_items( $parent_id ){

            $args = array(
                'post_type' => 'nav_menu_item',
                'meta_query' => array(
                    array(
                        'key' => '_menu_item_menu_item_parent',
                        'value' => absint( $parent_id ),
                        'compare' => '=',
                    ),
                ),
            );

            $children = get_posts( $args );

            return $children;
        }

        /**
         * Returns true if menu item has children
         *
         * Checks for post_meta with a menu_item_parent key set to the current
         * post_id but NOT 0 which means it has no parent
         *
         * @since 2022-09-21
         * @author Curtis
         * @access private
         *
         * @param   int         $item_id           required         ID for the item we are checking
         * @uses    absint()                                        No negative numbers
         * @uses    get_posts()                                     Returns posts given args
         * @return  bool        $has_children                       True if there are children
         */
        private static function menu_item_has_children( $item_id ){

            $has_children = false;

            $args = array(
                'post_type' => 'nav_menu_item',
                'meta_query' => array(
                    array(
                        'key' => '_menu_item_menu_item_parent',
                        'value' => absint( $item_id ),
                        'compare' => '=',
                    ),
                    array(
                        'key' => '_menu_item_menu_item_parent',
                        'value' => 0,
                        'compare' => '!='
                    )
                ),
            );

            $children = get_posts( $args );

            if ( ! empty( $children ) ){
                $has_children = true;
            }

            return (bool) $has_children;

        }

        /**
         * Adds our new item to a menu because we just selected that menu
         *
         * @since 1.2
         * @author Curtis McHale
         * @access private
         *
         * @param   int         $post_id        required            ID of the CPT item we're adding to the menu
         * @param   int         $menu_id        required            ID of the menu we're adding to
         * @param   int         $count          required            Corresponds to the order of the item in the menu
         * @uses    get_the_title()                                 Returns the title of a post given $post_id
         * @uses    absint()                                        no negative numbers
         * @uses    wp_insert_post()                                Adds a post to the WP database
         * @uses    wp_set_object_terms()                           Adds a term to the post_type
         * @uses    update_post_meta()                              Adds post metadata given post_id, key, value
         * @uses    esc_url()                                       Makes our URL safe
         * @uses    get_the_permalink()                             Returns permalink given post_id
         * @uses    get_post_type()                                 Returns the post type of the object provide
         * @return  int         $nav_menu_item                      ID of the newly create nav_menu item
         */
        private static function add_new_item_to_menu( $post_id, $menu_id, $count ){

                $post_args = array(
                    'ID' => 0,
                    'post_title' => get_the_title( absint( $post_id ) ),
                    'post_type' => 'nav_menu_item',
                    'post_status' => 'publish',
                    'menu_order' => absint( $count ),
                    'post_content' => '',
                );

                $nav_menu_item = wp_insert_post( $post_args );

                wp_set_object_terms( absint( $nav_menu_item ), absint( $menu_id ), 'nav_menu', false );

                update_post_meta( absint( $nav_menu_item ), '_menu_item_url', esc_url( get_the_permalink( absint( $post_id ) ) ) );
                update_post_meta( absint( $nav_menu_item ), '_menu_item_target', '_blank' );
                update_post_meta( absint( $nav_menu_item ), '_menu_item_classes', array() );
                update_post_meta( absint( $nav_menu_item ), '_menu_item_xfn', '' );
                update_post_meta( absint( $nav_menu_item ), '_menu_item_object_id', absint( $post_id ) );
                update_post_meta( absint( $nav_menu_item ), '_menu_item_object', get_post_type( absint( $post_id ) ) );
                update_post_meta( absint( $nav_menu_item ), '_menu_item_type', 'post_type' );

                return absint( $nav_menu_item );

        } // add_new_item_to_menu

        /**
         * Displays our form to edit the item
         *
         * @since 1.2
         * @author Curtis McHale
         * @access private
         *
         * @param   int     $post_id        required            ID of the item we are editing
         * @param   object  $item_object    required            Object containing item properties
         * @return  string  $html                               built out HTML for the form
         */
        private static function edit_item_form( $post_id, $item_object ){

            $html = '';

            $html .= '<div class=pcq-edit-item-form>';
                $html .= '<label for="pcq-menu-item-title">Display title for ';
                    $html .= '<span class="pcq-original"><a href="'. get_permalink( absint( $item_object['object_id'] ) ) .'">'. get_post_field( 'post_title', absint( $item_object['object_id'] ) ) .'</a></span>';
                $html .= '</label>';
                $html .= '<input class="pcq-menu-item-title" name="pcq-menu-item-title" value="'. esc_attr( $item_object['title'] ).'" />';
                $html .= '<button class="pcq-edit-item-button button" data-menu-item-object-id="'. absint( $post_id ) .'">Update</button>';
                $html .= '<span class="spinner pcq-edit-spinner"></span>';
            $html .= '</div>';

            return $html;

        } // edit_item_form

        /**
         * Removes the menu entry because we changed which menu the page was in
         *
         * @since 1.2
         *
         * @param   int         $old_menu_item_id           required                post_id of the old menu entry
         * @uses    wp_delete_post()                                                deletes post given id or object
         */
        private static function maybe_remove_old_menu_entry( $old_menu_item_id ){

            if ( null != $old_menu_item_id ){
                wp_delete_post( $old_menu_item_id );
            }

        } // maybe_remove_old_menu_entry

        /**
         * Loops through and sanitizes the array of CSS classes on the item
         *
         * @since 1.2
         *
         * @param   array           $class_array        required                Array of CSS classes to sanitize
         * @uses    sanitize_html_class()                                       sanitizes our class
         * @return  string          $sanitized_classes()                        clean classes
         */
        private static function sanitize_array_of_css_classes( $class_array ){


            $sanitized_classes = '';

            foreach( $class_array as $key => $value ){
                $sanitized_classes .= sanitize_html_class( $value ) . ' ';
            }


            return (string) $sanitized_classes;
        }

        /**
         * Returns a sanitized string of classes for a given menu item
         *
         * @since 2022-10-04
         * @author Curtis McHale
         * @access private
         *
         * @param   int         $menu_item_id       required            ID for the nav_menu_item we want classes
         * @uses    get_post_meta()                                     Returns post metadata given post_id and key
         * @uses    self::sanitize_array_of_css_classes()               Takes array returns string of sanitized classes
         * @return  string      $classes                                Sanitized string of HTML classes
         */
        private static function get_menu_item_classes( $menu_item_id ){

            $classes = get_post_meta( absint( $menu_item_id ), '_menu_item_classes', true );

            $classes = self::sanitize_array_of_css_classes( $classes );

            return (string) $classes;

        }

        /**
         * Returns true if this is a child item
         *
         * @since 1.2
         *
         * @param   object      $item           required            Menu item object
         * @return  bool        $is_child                           true if this is a child item
         */
        private static function is_item_a_child_item( $item ){

            $is_child = false;
            $parent_id = get_post_meta( absint( $item->db_id ), '_menu_item_menu_item_parent', true );
            if ( 0 != absint( $parent_id ) ){
                $is_child = true;
            }

            return (bool) $is_child;
        }

        /**
         * Returns true if the item is already in the menu
         *
         * @since 1.2
         *
         * @param   bool        $in_menu            optional                True if the item is in the menu
         * @param   int         $menu_object_id     required                object id of the menu item
         * @param   int         $current_post_id    required                ID for the current post
         * @return  bool                                                    true if the item is already in the menu
         */
        private static function is_item_already_in_menu( $in_menu = false, $menu_object_id, $current_post_id ){

            if ( true == $in_menu ) return (bool) $in_menu;

            if ( absint( $menu_object_id ) == absint( $current_post_id ) ){
                $in_menu = true;
            }

            return (bool) $in_menu;
        }

        /**
         * Returns a single item for the menu
         *
         * @since 1.2
         * @author Curtis McHale
         *
         * @param   object          $item           required            Menu item object
         * @param   string          $current_item   required            adds current-menu-item if we're on the page represented by the menu item
         * @param   string          $child_depth    required            Adds a class if item is a nested menu
         * @param   int             $count          required            Menu order
         */
        private static function get_single_item( $item, $current_item, $count ){

            $html = '';

            $setup_item = self::setup_item_data( $item, $count );

            $html .= '<li ';
                $html .= 'class="pc_quick_menu_item dd-item' . sanitize_html_class( $current_item ) .'"';
                $html .= 'data-menu-item-db-id="'. absint( $setup_item['db_id'] ) .'" ';
                $html .= 'data-menu-item-object-id="'. absint( $setup_item['object_id'] ) .'" ';
                $html .= 'data-menu-item-object="'. esc_attr( $setup_item['page'] ) .'" ';
                $html .= 'data-menu-item-parent-id="'. absint( $setup_item['menu_item_parent'] ) .'" ';
                $html .= 'data-menu-item-type="'. esc_attr( $setup_item['type'] ) .'" ';
                $html .= 'data-menu-item-title="'. esc_attr( $setup_item['title'] ) .'" ';
                $html .= 'data-menu-item-url="'. esc_url( $setup_item['url'] ) .'" ';
                $html .= 'data-menu-item-description="'. esc_attr( $setup_item['description'] ) .'" ';
                $html .= 'data-menu-item-attr-title="' . esc_attr( $setup_item['attr_title'] ) .'" ';
                $html .= 'data-menu-item-target="'. esc_attr( $setup_item['target'] ) .'" ';
                $html .= 'data-menu-item-classes="'. esc_attr( $setup_item['classes'] ) .'" ';
                $html .= 'data-menu-item-xfn="'. esc_attr( $setup_item['xfn'] ) .'" ';
                $html .= 'data-menu-item-position="'. absint( $setup_item['count'] ) .'">';
                $html .= '<div class="pcq-item-title-wrap">';
                    $html .= '<span class="pcq-title-wrap">'. esc_attr( $setup_item['title'] ) .'</span>';
                    $html .= '<div class="pcq-action-wrapper">';
                        $html .= '<span title="Delete Item" class="pcq_delete_item dashicons dashicons-trash"></span>';
                        $html .= '<span title="Edit Item" class="pcq_edit_item dashicons dashicons-admin-tools"></span>';
                    $html .= '</div>';
                $html .= '</div><!-- /.pcq-item-title-wrap -->';
                $html .= self::edit_item_form( absint( $setup_item['db_id'] ), $setup_item );

                if ( self::menu_item_has_children( absint( $setup_item['db_id'] ) ) ){
                    // I may need to return an array with $html and $count values updated
                    $html .= self::get_child_menu( absint( $setup_item['db_id'] ), absint( $count ) );
                }

            $html .= '</li>';

            return $html;

        } // get_single_item

        /**
         * Retrieves the HTML for the current item
         *
         * @since 1.2
         *
         * @todo expand this so it has the same params as other items
         *
         * @uses    int         $post_id        required                    The id of the current item that our nav menu item links to
         * @uses    int         $order          required                    The menu order of the item currently
         * @uses    int         $db_id          required                    ID of the time in the database (because nav menu items are their own posts)
         */
        public static function get_current_item( $post_id, $order, $db_id ){

            $html = '';

            $title = empty( get_post_field( 'title', absint( $post_id ) ) ) ? get_post_field( 'post_title', absint( $post_id ) ) : get_post_field( 'title', absint( $post_id ) );

            $html .= '<li class="pc_quick_menu_item current-menu-item" ';
                $html .= 'data-menu-item-object-id="'. absint( $post_id ) .'" ';
                $html .= 'data-menu-item-db-id="' . absint( $db_id ) .'" ';
                $html .= 'data-menu-item-position="'. absint( $order ) .'">';
                $html .= '<div class="pcq-item-title-wrap">';
                    $html .= '<span class="pcq-title-wrap">'. esc_attr( $title ) .'</span>';
                    $html .= '<div class="pcq-action-wrapper">';
                        $html .= '<span title="Delete Item" class="pcq_delete_item dashicons dashicons-trash"></span>';
                        $html .= '<span title="Edit Item" class="pcq_edit_item dashicons dashicons-admin-tools"></span>';
                    $html .= '</div>';
                $html .= '</div><!-- /.pcq-item-title-wrap -->';
            $html .= '</li>';

            return $html;

        } // get_current_item

        /**
         * add required JS and CSS
         */
        function wp_quick_menu_add_css_js() {

            $allowed_post_types = apply_filters( 'pcq_allowed_post_types', array( 'post', 'page' ) );
            $screen = get_current_screen();

            // only adding our scripts on specific types that are allowed
            if ( in_array( $screen->id, $allowed_post_types ) ){
                wp_enqueue_style('wp_quick_menu_style', plugins_url('css/style.css', __FILE__));

                // scripts plugin
                wp_enqueue_script('pc_quick_menu_scripts', plugins_url( '/proudcity-quick-menu/js/script.js' ), array('jquery'), '1.2', true);
                wp_localize_script( 'pc_quick_menu_scripts', 'PCQuickMenuScripts', array(
                    'ajaxurl'           => admin_url( 'admin-ajax.php' ),
                    'pc_quick_menu_nonce' => wp_create_nonce( 'pc_quick_menu_nonce' ),
                ) );
            }
        }

        /**
         * Adds the metabox to our post types
         *
         * @since 1.2
         * @author Curtis McHale
         * @access public
         *
         * @uses add_meta_box                   Adds the metabox to our post types
         */
        function wp_quick_menu_add_meta_box() {

            /**
             * Allows end users to add their own post types, or remove the default post types
             *
             * @since 1.2
             *
             * @param   array       The existing array of allowed post types
             */
			$in_which_screens = apply_filters( 'pcq_allowed_post_types', array( 'post', 'page' ) );

            foreach ($in_which_screens as $screen) {
                add_meta_box(
                    'wp_quick_menu_meta_box', __('Add to menu', 'wp_quick_menu'), array($this, 'wp_quick_menu_meta_box_call_back'), esc_attr( $screen ), 'side', 'core'
                );
            }

        } // wp_quick_menu_add_meta_box

        /**
         * Our metabox to choose the menu our post entry should attach to
         *
         * @param   object          $post               required                The post object for the current post
         * @uses    wp_nonce_field()                                            Creates a nonce to verify the form submission
         * @uses    wp_get_nav_menus()                                          Returns all registered navigation menu objects
         * @uses    $this->wp_quick_menu_check_menu_entry()
         */
        function wp_quick_menu_meta_box_call_back($post) {

            wp_nonce_field('wp_quick_menu_meta_box', 'wp_quick_menu_meta_box_nonce');

            $nav_menus = wp_get_nav_menus(array('orderby' => 'name'));

            // @todo this needs a wrapping loop if we have an item in multiple menus

            self::pc_quick_build_menu_interface( $nav_menus );

        } // wp_quick_menu_meta_box_call_back

        /**
         * Builds out our menu interface for using menus on post types
         */
        private static function pc_quick_build_menu_interface( $nav_menus ){ ?>

            <div class="inside pc_quick_menu_wrapper" style="margin:6px;">
                <p>
                    <div>
                        <label for="wp_quick_nav_menu"><?php _e( 'Menu', 'wp_quick_menu' ); ?></label>
                    </div>

                    <select id="wp_quick_nav_menu" name="wp_quick_nav_menu">
                        <option value=""><?php echo __( '- No Menu - ', 'wp_quick_menu' ); ?></option>
                        <?php echo self::pc_quick_menu_return_menu_options( $nav_menus ); ?>
                    </select>
                </p>

                <div class="pc_quick_menu_position">
                </div>

                <div id="pcq_user_feedback">
                    <p id="pcq_feedback_message"></p>
                    <span class="spinner"></span>
                </div>

            </div><!-- /.inside -->

        <?php
        } // pc_quick_build_menu_interface

        /**
         * Builds the options for our nav menus
         *
         * @since 1.2
         *
         * @param   array       $nav_menus      required                Array of nav menu locations
         * @uses    self::is_item_selected()                            Returns selected text if current page is in the menu
         */
        private static function pc_quick_menu_return_menu_options( $nav_menus ){

            $html = '';

            foreach( $nav_menus as $nav_menu ){
                $selected = self::is_item_selected( absint( $nav_menu->term_id ), absint( $_GET['post'] ) );
                $html .= '<option value="'. absint( $nav_menu->term_id ) .'" '. $selected .'>';
                    $html .= esc_attr( $nav_menu->name );
                $html .= '</option>';
            }

            return $html;

        } // pc_quick_return_menu_options

        /**
         * Returns selected text if the item is selected
         *
         * @since 1.2
         *
         * @param   $menu_id        int         required            wp nav menu id
         * @param   $post_id        int         required            the id for the post we're checking
         * @uses    wp_get_nav_menu_items()                         Returns the items in the nav menu
         * @return  string                                          empty if no match selected if a match is found
         */
        private static function is_item_selected( $menu_id, $post_id ){

            $selected = '';

            $menu_items = wp_get_nav_menu_items( absint( $menu_id ) );

            foreach( $menu_items as $item ){
                if ( $post_id == $item->object_id ){
                    return 'selected';
                }
            } // foreach

            return $selected;

        } // is_item_selected

    }

}

$pc_quick_menu = new PC_Quick_Menu();