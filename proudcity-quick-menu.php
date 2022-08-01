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

if (!defined('WPINC')) {
    die;
}

require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

if (!class_exists('WP_Quick_Menu')) {

    class WP_Quick_Menu {

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

        }

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

            $html .= '<ul class="pc_quick_menu_item_position">';
                foreach( $menu_items as $item ){
                    $current_item = absint( $item->object_id ) == absint( $_POST['current_post_id'] ) ? 'current-menu-item' : '';
                    $child_depth = self::is_item_a_child_item( $item ) ? 'item-depth-1' : '';
                    $in_menu = self::is_item_already_in_menu( $in_menu, absint( $item->object_id ), absint( $_POST['current_post_id'] ) );
                    $html .= '<li ';
                        $html .= 'class="pc_quick_menu_item ' . sanitize_html_class( $current_item ) .' '. sanitize_html_class( $child_depth ) .'" ';
                        $html .= 'data-menu-item-db-id="'. absint( $item->db_id ) .'" ';
                        $html .= 'data-menu-item-object-id="'. absint( $item->object_id ) .'" ';
                        $html .= 'data-menu-item-object="'. esc_attr( $item->page ) .'" ';
                        $html .= 'data-menu-item-parent-id="'. absint( $item->menu_item_parent ) .'" ';
                        $html .= 'data-menu-item-type="'. esc_attr( $item->type ) .'" ';
                        $html .= 'data-menu-item-title="'. esc_attr( $item->title ) .'" ';
                        $html .= 'data-menu-item-url="'. esc_url( $item->url ) .'" ';
                        $html .= 'data-menu-item-description="'. esc_attr( $item->description ) .'" ';
                        $html .= 'data-menu-item-attr-title="' . esc_attr( $item->attr_title ) .'" ';
                        $html .= 'data-menu-item-target="'. esc_attr( $item->target ) .'" ';
                        $html .= 'data-menu-item-classes="'. self::sanitize_array_of_css_classes( $item->classes ) .'" ';
                        $html .= 'data-menu-item-xfn="'. esc_attr( $item->xfn ) .'" ';
                        $html .= 'data-menu-item-position="'. $count .'">';
                        $html .= esc_attr( $item->title );
                        $html .= '<span title="Delete Item" class="pcq_delete_item">X</span>';
                    $html .= '</li>';
                    $count++;
                }

                // false because we are adding our current item to a newly selected menu
                if ( ! $in_menu ){

                    // @todo abstract this to it's own function
                    $post_id = $_POST['current_post_id'];

                    $post_args = array(
                        'ID' => 0,
                        'post_title' => get_the_title( absint( $post_id ) ),
                        'post_type' => 'nav_menu_item',
                        'post_status' => 'publish',
                        'menu_order' => absint( $count ),
                        'post_content' => '',
                    );

                    $nav_menu_item = wp_insert_post( $post_args );

                    update_option( 'sfn_test', 'nav_id ' . $nav_menu_item );

                    wp_set_object_terms( absint( $nav_menu_item ), absint( $_POST['selected_menu'] ), 'nav_menu', false );

                    update_post_meta( absint( $nav_menu_item ), '_menu_item_url', esc_url( get_the_permalink( absint( $post_id ) ) ) );
                    update_post_meta( absint( $nav_menu_item ), '_menu_item_target', '_blank' );
                    update_post_meta( absint( $nav_menu_item ), '_menu_item_classes', array() );
                    update_post_meta( absint( $nav_menu_item ), '_menu_item_xfn', '' );
                    update_post_meta( absint( $nav_menu_item ), '_menu_item_object_id', absint( $post_id ) );
                    update_post_meta( absint( $nav_menu_item ), '_menu_item_object', get_post_type( absint( $post_id ) ) );
                    update_post_meta( absint( $nav_menu_item ), '_menu_item_type', 'post_type' );


                    $html .= self::get_current_item( absint( $_POST['current_post_id'] ), absint( $count ), absint( $nav_menu_item ) );
                }
            $html .= '</ul>';

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
         * Returns true if this is a child item
         *
         * @since 1.2
         *
         * @param   object      $item           required            Menu item object
         * @return  bool        $is_child                           true if this is a child item
         */
        private static function is_item_a_child_item( $item ){

            $is_child = false;
            if ( isset( $item->menu_item_parent ) && 0 != absint( $item->menu_item_parent ) ){
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
                $html .= 'data-item_order="'. absint( $order ) .'">';
                $html .= esc_attr( $title );
                $html .= '<span title="Delete Item" class="pcq_delete_item">X</span>';
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
                    'wp_quick_menu_meta_box', __('Add to menu', 'wp_quick_menu'), array($this, 'wp_quick_menu_meta_box_call_back'), esc_attr( $screen ), 'side', 'high'
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

$wp_quick_menu = new WP_Quick_Menu();
