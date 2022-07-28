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
            add_action('save_post', array($this, 'wp_quick_menu_save_meta_box_data'));

            add_action( 'wp_ajax_pc_quick_get_menu_items', array( $this, 'get_menu_items' ) );
        }

        /**
         * Returns the items in a menu given the Menu ID
         */
        public function get_menu_items(){
            check_ajax_referer( 'pc_quick_menu_nonce', 'security' );

            $html = '';
            $count = 0;
            $menu_items = wp_get_nav_menu_items(
                absint( $_POST['selected_menu'] )
            );

            //print_r( $menu_items );

            // @todo if empty handling
            // @todo deal with child items in a menu as well

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
                    $html .= '</li>';
                    $count++;
                }

                // false because we are adding our current item to a newly selected menu
                if ( ! $in_menu ){
                    $html .= self::get_current_item( absint( $_POST['current_post_id'] ), $count );
                }
            $html .= '</ul>';

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
         * @uses    int         $post_id        required                    The id of the current item
         */
        public static function get_current_item( $post_id, $order ){

            $html = '';

            $title = empty( get_post_field( 'title', absint( $post_id ) ) ) ? get_post_field( 'post_title', absint( $post_id ) ) : get_post_field( 'title', absint( $post_id ) );

            $html .= '<li class="pc_quick_menu_item current-menu-item" data-post_id="'. absint( $post_id ) .'" data-item_order="'. absint( $order ) .'">'. esc_attr( $title ) .'</li>';

            return $html;

        } // get_current_item

        /**
         * add required JS and CSS
         */
        function wp_quick_menu_add_css_js() {
            wp_enqueue_style('wp_quick_menu_style', plugins_url('css/style.css', __FILE__));

            // scripts plugin
            wp_enqueue_script('pc_quick_menu_scripts', plugins_url( '/proudcity-quick-menu/js/script.js' ), array('jquery'), '1.2', true);
            wp_localize_script( 'pc_quick_menu_scripts', 'PCQuickMenuScripts', array(
                'ajaxurl'           => admin_url( 'admin-ajax.php' ),
                'pc_quick_menu_nonce' => wp_create_nonce( 'pc_quick_menu_nonce' ),
            ) );
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

            if (!empty($nav_menus)) {
                foreach ($nav_menus as $key => $value) {
                    $nav_menus[$key]->menu_items = $this->wp_quick_menu_check_menu_entry( absint( $value->term_id ), absint( $post->ID ) );
                }
            } // if ! empty $nav_menus

            self::pc_quick_build_menu_interface( $nav_menus );
            // @todo see about removing the locations and menu_locations vars as they do not seem to be used
            $locations = get_registered_nav_menus();
            $menu_locations = get_nav_menu_locations();

            /**
             * @todo there are no settings to change the format of the menu
             *  - implement settings
             *  - provide feedback on which item is actually selected if there is something in the menu
             * @todo add settings to the plugin listing screen
             *  - https://neliosoftware.com/blog/how-to-add-a-link-to-your-settings-in-the-wordpress-plugin-list/
             */
            $menu_format = get_option( 'wp_quick_menu_format', 'accordian' );

            /*
            if ( esc_attr( $menu_format ) === 'select') {
              $num_menus = 0;
              foreach ($nav_menus as $key => $value) {
                $num_menus += !empty($value->menu_items->ID) ? 1 : 0;
              }
              require_once dirname(__FILE__) . "/templates/menu_form_select_template.php";
            } else {
                // users never see this template unless they manually set get_option( 'wp_quick_menu_format' ) as there is no settings page currently
              wp_enqueue_script('accordion');
              require_once dirname(__FILE__) . "/templates/menu_form_template.php";
            }
            */

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

        /**
         * Returns the possible menu order positions for a menu.
         *
         * This is used with wp_localize_script so that each menu item in a post/page/cpt
         * shows the possible menu positions available for any menu item as you add
         * the current page/post/cpt to the menu.
         *
         * @uses    wp_get_nav_menu_items()                                                 Returns all the menu objects
         * @uses    $this->wp_quick_menu_nav_menu_items()                                   Returns the items in a menu given menu_id
         * @uses    $this->wp_quick_menu_get_possible_menu_order_for_default_parent()       Returns the possible menu positions for the default parent item in the menu
         * @uses    $this->wp_quick_menu_get_possible_menu_order_for_specific_parent()      Returns the possible menu positions for a specific parent item in a menu
         */
        private function wp_quick_menu_get_possible_order_for_all_parent() {
            $possible_values = array();

            $nav_menus = wp_get_nav_menus(array('orderby' => 'name'));

            foreach ($nav_menus as $nav_menu) {

                $possible_values[ absint( $nav_menu->term_id ) ] = array();
                $menu_items = $this->wp_quick_menu_nav_menu_items( absint( $nav_menu->term_id ) );
                $possible_values[ absint( $nav_menu->term_id ) ][0] = wp_kses_post( $this->wp_quick_menu_get_possible_menu_order_for_default_parent( (object) $menu_items ) );

                if (!empty($menu_items)) {
                    foreach ($menu_items as $menu_item) {
                        $possible_values[ absint( $nav_menu->term_id ) ][ absint( $menu_item->ID ) ] = wp_kses_post( $this->wp_quick_menu_get_possible_menu_order_for_specific_parent( (object) $menu_items, (int) $menu_item->ID, (int) $menu_item->menu_order) );
                    }
                }
            } // foreach

            return (array) $possible_values;
        }

        /**
         * Returns the possible order for menu items
         *
         * @return  string          Text telling the user what the possible order values are for an item
         */
        private function wp_quick_menu_get_possible_menu_order_for_default_parent($menu_items) {
            if (empty($menu_items)) {
                return '<span class="wp-quick-menu-strong">' . __('Possible Positions: ', 'proudcity_quick_menu') . '</span> 1';
            }

            $possible_values = array();

            foreach ($menu_items as $menu_item) {

                if ( absint( $menu_item->menu_item_parent ) === 0) {
                    $possible_values[] = absint( $menu_item->menu_order );
                }

            } // foreach

            if (!isset($_GET['action']) && is_countable( $menu_items ) ) {
                $possible_values[] = count( $menu_items ) + 1;
            }


            return '<span class="wp-quick-menu-strong">' . __('Possible Positions: ', 'proudcity_quick_menu') . '</span>' . implode(", ", (array) $possible_values );

        } // wp_quick_menu_get_possible_menu_order_for_default_parent

        /**
         * Returns possible menu order values for a specific menu item parent
         *
         * @access private
         *
         * @param   object      $menu_items             required                the menu items object we're parsing
         * @param   int         $parent                 required                ID of the parent item we're looking at
         * @param   int         $current_order          required                Current order int value
         */
        private function wp_quick_menu_get_possible_menu_order_for_specific_parent($menu_items, $parent, $current_order) {
            $possible_position = array();
            $current_order += 1;
            $child = 0;

            foreach ($menu_items as $menu_item) {
                if ($menu_item->menu_item_parent == $parent && $parent > 0) {
                    $child += 1;
                }
            }


            return '<span class="wp-quick-menu-strong">' . __('Possible Positions: ', 'proudcity_quick_menu') . '</span>' . implode(", ", range($current_order, $current_order + $child));
        }

        /**
         * Saving the data in the metabox
         *
         * @param   int         $post_ID            required                post_id for the post we're currently saving
         * @uses    wp_verify_nonce()                                       makes sure we have a valid nonce
         * @uses    current_user_can()                                      TRUE if the current user has the listed capability
         * @uses    $this->wp_quick_menu_get_menu_id()
         * @uses    $this->wp_quick_menu_get_menu_remove()
         * @return boolean
         */
        function wp_quick_menu_save_meta_box_data($post_id) {

            if (
                    !isset($_POST['wp_quick_menu_meta_box_nonce']) ||
                    !wp_verify_nonce($_POST['wp_quick_menu_meta_box_nonce'], 'wp_quick_menu_meta_box')
            ) {
                return;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            // Check the user's permissions.
            if (isset($_POST['post_type']) && 'page' == esc_attr( $_POST['post_type'] ) ) {

                if (!current_user_can('edit_page', absint( $post_id ) )) {
                    return;
                }
            } else {

                if (!current_user_can('edit_post', absint( $post_id ) )) {
                    return;
                }
            }

            $menu_ids = $this->wp_quick_menu_get_menu_id();
            $remove_menu_ids = $this->wp_quick_menu_get_menu_remove();

            if (empty($menu_ids)) {
                return true;
            }

            $menu_values = $this->wp_quick_menu_get_menu_data_from_post_vars($post_id, $menu_ids);
            // unset the psot variables
            $this->wp_quick_menu_unset_post_variables();
            $this->wp_quick_menu_insert_menu_items($menu_values, $remove_menu_ids);

            return true;
        }

        /**
         *
         * @param array $menu_values
         * @param array $remove_menu_ids
         * @return boolean
         */
        private function wp_quick_menu_insert_menu_items($menu_values, $remove_menu_ids) {
            if (empty($menu_values)) {
                return true;
            }

            foreach ($menu_values as $menuID => $values) {
                if (!empty($remove_menu_ids) && in_array($menuID, $remove_menu_ids) && isset($values['menu-item-db-id']) && $values['menu-item-db-id'] > 0) {
                    $this->wp_quick_menu_remove_menu_entry( absint( $values['menu-item-db-id'] ) );
                    continue;
                }

                if (!isset($values['menu-item-db-id']) || $values['menu-item-db-id'] <= 0) {
                    $nav_menu_items = $this->wp_quick_menu_nav_menu_items( absint( $menuID ) );

                    error_log( print_r( $nav_menu_items, true ) );

                    $calculated_postition = $this->wp_quick_menu_calculate_accurate_menu_position($nav_menu_items, $values);
                    $values['menu-item-position'] = $calculated_postition;
                    $saved_items[] = $values;
                    $item_ids = wp_save_nav_menu_items(0, $saved_items);
                    $values['menu-item-db-id'] = $item_ids[0];
                } else {
                    $position = $this->wp_quick_menu_update_menu_item_postion($menuID, $values);
                    $values['menu-item-position'] = $position;
                }
                wp_update_nav_menu_item($menuID, $values['menu-item-db-id'], $values);
                $post_details = get_post($values['menu-item-object-id']);
                $my_post = array('ID' => $values['menu-item-db-id'], 'post_status' => $post_details->post_status);
                wp_update_post($my_post);
            }
            return true;
        }

        /**
         * remove menu entry
         * @param int $postid
         * @return boolean
         */
        private function wp_quick_menu_remove_menu_entry($post_id) {
            return wp_delete_post( absint( $post_id ) );
        }

        /**
         * Saves the updated position of a menu item
         *
         * @param   int         $menuID         required                            ID of the menu we're saving
         * @param   array       $values         required                            array of metadata with values
         * @uses    $this->wp_quick_menu_nav_menu_items()                           given id it returns a nav menu object
         * @uses    $this->wp_quick_menu_get_specific_menu_entry()                  Returns a menu object for a specific menu item
         * @uses    $this->wp_quick_menu_remove_specific_menu_entry_logically()
         * @return int
         */
        private function wp_quick_menu_update_menu_item_postion($menuID, $values) {

            $nav_menu_items = $this->wp_quick_menu_nav_menu_items( absint( $menuID ) );
            $exisint_menu_prop = $this->wp_quick_menu_get_specific_menu_entry( (array) $nav_menu_items, absint( $values['menu-item-db-id'] ) );

            // well no change in menu Item Position
            if ( absint( $values['menu-item-position'] ) === absint( $exisint_menu_prop->menu_order ) && absint( $values['menu-item-parent-id'] ) == absint( $exisint_menu_prop->menu_item_parent ) ) {
                error_log( 'no change' );
                return absint( $exisint_menu_prop->menu_order );
            }

            $nav_menu_items_logical = $this->wp_quick_menu_remove_specific_menu_entry_logically( (array) $nav_menu_items, absint( $values['menu-item-db-id'] ) );

            $calculated_position = $this->wp_quick_menu_calculate_accurate_menu_position($nav_menu_items_logical, $values);

            //error_log( 'calculated psition '. print_r( $calculated_position, true ) );
            //error_log( 'exisint id ' . $exisint_menu_prop->ID );

            $my_post = array('ID' => absint( $exisint_menu_prop->ID ), 'menu_order' => absint( $calculated_position ) );
            wp_update_post($my_post);

            return absint( $calculated_position );

        }

        /**
         * calculate accurate menu position
		 *
         * @param   array       $nav_menu_items             required            Nav menu objects
         * @param   array       $values                     required            Array of extra menu item metadata
         *
         * @return boolean
         */
        private function wp_quick_menu_calculate_accurate_menu_position($nav_menu_items, $values) {

            error_log( 'values ' . print_r( $values, TRUE ) );

            // get item order when it current item is NOT a sub-item

            // no parent selected and menu will be in last position
            if ( 0 == absint( $values['menu-item-parent-id'] ) ) {
                // so this saves the position of the menu if the current item is NOT a child item
                // but then it always assumes that the item is the last item no matter what you set
                // in theory it should put the item last if there has been no definition because the item has just been added to the menu
                // heck, even better would be to show all the the menu items and have a draggable interface to reorder items
                error_log( 'no parent' );
                return 8; //count( get_object_vars( $nav_menu_items ) ) + 1;
            }

            if ( is_countable( $nav_menu_items) ){
                if ( absint( $values['menu-item-parent-id'] ) <= 0 && absint( $values['menu-item-position'] ) <= count($nav_menu_items)) {
                    error_log( 'option 2' );
                    $calculatedpos = $this->wp_quick_menu_check_to_replace_exisitng_menu_item( (array) $nav_menu_items, absint( $values['menu-item-position'] ) );
                    $this->wp_quick_menu_update_existing_menu_order( absint( $calculatedpos ), (array) $nav_menu_items);

                    return absint( $calculatedpos );
                }
            } // is_countable

            // get menu order when item IS a child of another item
            // parent selected
            if ( absint( $values['menu-item-parent-id'] ) >= 0) {
                $calculated_position = $this->wp_quick_menu_get_parent_position_with_child_count( (array) $nav_menu_items, absint( $values['menu-item-parent-id'] ), absint( $values['menu-item-position'] ) );
                error_log( 'has parrent' );
                if ($this->wp_quick_menu_update_existing_menu_order( absint( $calculated_position ), (array) $nav_menu_items )) {
                    return absint( $calculated_position );
                }
            }

            return (bool) true;
        }

        /**
         * replace an existing menu order
         * @param array $nav_menu_items
         * @param int $current_position
         * @return int
         */
        private function wp_quick_menu_check_to_replace_exisitng_menu_item($nav_menu_items, $current_position) {
            if (empty($nav_menu_items)) {
                return 1;
            }

            foreach ($nav_menu_items as $nav_menu_item) {
                if ( absint( $nav_menu_item->menu_order ) >= absint( $current_position ) ) {
                    if ((int) $nav_menu_item->menu_item_parent <= 0) {
                        return absint( $nav_menu_item->menu_order );
                    }
                }
            }
        }

        /**
         * update menu order
         * @param type $where_nod_to_update
         * @param type $nav_menu_items
         */
        private function wp_quick_menu_update_existing_menu_order($where_nod_to_update, $nav_menu_items) {

            error_log( 'nod '. print_r( $where_nod_to_update, true ) );

            if (!empty($nav_menu_items)) {

                foreach ($nav_menu_items as $nav_menu_item) {

                    if ( absint( $nav_menu_item->menu_order ) >= $where_nod_to_update) {

                        $my_post = array();
                        $my_post = array('ID' => absint( $nav_menu_item->ID ) , 'menu_order' => absint( $nav_menu_item->menu_order + 1 ) );
                        wp_update_post($my_post);

					} // if $nave_menu_item->menu_order

                } // foreach

            } // if

            return (bool) true;
        }

        /**
         * calculate position with child
         * @param array $nav_menu_items
         * @param int $parent_id
         * @param int $current_position
         * @return int
         */
        private function wp_quick_menu_get_parent_position_with_child_count($nav_menu_items, $parent_id, $current_position) {

            if (!empty($nav_menu_items)) {

                // @todo just defined these to kill errors need to really figure them out and make sure the code works
                $min_position = 0;
                $max_position = 0;

                $child = 0;
                $parent_found = false;

                foreach ($nav_menu_items as $pos => $nav_menu_item) {
                    // get parent current position
                    if ( absint( $nav_menu_item->ID ) === absint( $parent_id ) ) {
                        $parent_found = true;
                        $min_position = $max_position = $nav_menu_item->menu_order + 1;
                    }
                    // count total child
                    if ($nav_menu_item->menu_item_parent == $parent_id && $parent_found === true) {
                        $max_position += 1;
                    }
                }

                // if parent has no childs so position will be just after the parent
                if ($min_position === $max_position) {
                    return absint( $min_position );
                }

                if (($current_position >= $min_position) && ($current_position <= $max_position)) {
                    return absint( $current_position );
                }

                if ($current_position > $max_position) {
                    return absint( $max_position );
                }

                // return the max number of child
                return absint( $max_position );
            }


            // default position
            return $current_position;
        }

        /**
         * get menu ID
         * @return array
         */
        private function wp_quick_menu_get_menu_id() {

            $menu_ids = array();

            if (isset($_POST['wp_quick_nav_menu'])) {

                $menus = absint( $_POST['wp_quick_nav_menu'] );
                if (is_array($menus)) {
                    foreach ($menus as $value) {
                        $menu_id = (int) $value;
                        if ($menu_id > 0) {
                            $menu_ids[] = absint( $menu_id );
                        }
                    }
                }
                elseif ($menus > 0) {
                    $menu_ids = array($menus);
                }

            }

            unset($_POST['wp_quick_nav_menu']);

            return (array) $menu_ids;
        }

        /**
         * get menu IDs which need to remove
         * @return array
         */
        private function wp_quick_menu_get_menu_remove() {
            $menu_ids = array();
            if (isset($_POST['wp_quick_nav_menu_remove'])) {
                foreach ($_POST['wp_quick_nav_menu_remove'] as $value) {
                    $menu_id = (int) $value;
                    if ($menu_id > 0) {
                        $menu_ids[] = $menu_id;
                    }
                }
            }
            unset($_POST['wp_quick_nav_menu_remove']);
            return $menu_ids;
        }

        /**
         * get menu submitted menu data
         * @param int $post_ID
         * @param array $menu_ids
         * @return array
         */
        private function wp_quick_menu_get_menu_data_from_post_vars($post_ID, $menu_ids) {

            $menu_item_data = array();
            $_object = get_post( absint( $post_ID ) );
            $_menu_items = array_map('wp_setup_nav_menu_item', array($_object));
            $_menu_item = array_shift($_menu_items);

            foreach ($menu_ids as $menuid) {

                $menu_item_data[$menuid]['menu-item-description'] = (isset($_POST['wp_quick_menu_item_desc'][ absint($menuid) ]) && trim($_POST['wp_quick_menu_item_desc'][ absint( $menuid ) ]) != '') ? trim(esc_html($_POST['wp_quick_menu_item_desc'][ absint( $menuid ) ])) : $_menu_item->description;
                $menu_item_data[$menuid]['menu-item-title'] = (isset($_POST['wp_quick_menu_item_title'][ absint( $menuid ) ]) && trim($_POST['wp_quick_menu_item_title'][ absint( $menuid ) ]) != '') ? trim(esc_html($_POST['wp_quick_menu_item_title'][ absint( $menuid ) ] )) : $_menu_item->title;
                $menu_item_data[$menuid]['menu-item-url'] = esc_url( $_menu_item->url );
                $menu_item_data[$menuid]['menu-item-object-id'] = $_POST['wp_quick_menu_item_object_id'][ absint( $menuid ) ];
                $menu_item_data[$menuid]['menu-item-db-id'] = (int) $_POST['wp_quick_menu_item_db_id'][ absint( $menuid ) ];
                $menu_item_data[$menuid]['menu-item-object'] = $_POST['wp_quick_menu_item_object'][ absint( $menuid ) ];
                $menu_item_data[$menuid]['menu-item-parent-id'] = (int) $_POST['wp_quick_menu_item_parent_id'][ absint( $menuid ) ];
                $menu_item_data[$menuid]['menu-item-type'] = $_POST['wp_quick_menu_item_type'][ absint( $menuid ) ];
                $menu_item_data[$menuid]['menu-item-xfn'] = $_POST['wp_quick_menu_item_xfn'][ absint( $menuid ) ];
                $menu_item_data[$menuid]['menu-item-target'] = $_POST['wp_quick_menu_item_target'][ absint( $menuid ) ];
                $menu_item_data[$menuid]['menu-item-classes'] = $_POST['wp_quick_menu_item_classes'][ absint( $menuid ) ];
                $menu_item_data[$menuid]['menu-item-attr-title'] = $_POST['wp_quick_menu_item_attr_title'][ absint( $menuid ) ];

                if (isset($_POST['wp_quick_menu_item_position'][ absint( $menuid ) ]) && $_POST['wp_quick_menu_item_position'][ absint( $menuid ) ] > 0) {
                    $menu_item_data[ absint( $menuid ) ]['menu-item-position'] = $_POST['wp_quick_menu_item_position'][ absint( $menuid ) ];
                } // if

            } // foreach

            return $menu_item_data;
        }

        /**
         * unset post variables
         * @return boolean
         */
        private function wp_quick_menu_unset_post_variables() {
            unset($_POST['wp_quick_menu_item_db_id']);
            unset($_POST['wp_quick_menu_item_object']);
            unset($_POST['wp_quick_menu_item_parent_id']);
            unset($_POST['wp_quick_menu_item_type']);
            unset($_POST['wp_quick_menu_item_xfn']);
            unset($_POST['wp_quick_menu_item_target']);
            unset($_POST['wp_quick_menu_item_classes']);
            unset($_POST['wp_quick_menu_item_title']);
            unset($_POST['wp_quick_menu_item_attr_title']);
            unset($_POST['wp_quick_menu_item_desc']);
            return true;
        }

        /**
         * get parent child relations for object
         * @param obj $nav_menu
         * @return string
         */
        private function wp_quick_menu_parent_child_relation($nav_menu) {

            $parents = array(0 => '');
            $options = '<option value="0"> Select parent</option>';
            $nav_menu_items = $this->wp_quick_menu_nav_menu_items( absint( $nav_menu->term_id ) );

            if (!empty($nav_menu_items)) {
                foreach ($nav_menu_items as $nav_menu_item) {
                    if ($nav_menu_item->menu_item_parent > 0) {
                        if (!isset($parents[$nav_menu_item->menu_item_parent])) {
                            $parents[$nav_menu_item->menu_item_parent] = '-';
                        }
                        $parents[ absint( $nav_menu_item->ID ) ] = $parents[$nav_menu_item->menu_item_parent] . '-';
                    }

                    $selected = '';
                    if ($this->wp_quick_menu_get_menu_item_parent($nav_menu) > 0 && $nav_menu_item->ID == $this->wp_quick_menu_get_menu_item_parent($nav_menu)) {
                        $selected = 'selected';
                    }

                    $options .= '<option value="' . absint( $nav_menu_item->ID )  . '" ' . $selected . '>' . $this->wp_quick_menu_extra_spaces($parents[$nav_menu_item->menu_item_parent]) . ' ' . esc_attr( $nav_menu_item->title ) . '   (position:' . $nav_menu_item->menu_order . ')' . '</option>';
                }
            }
            return $options;
        }

        /**
         * get menu item order
         * @param object $nav_menu
         * @return string
         */
        private function wp_quick_menu_get_menu_order($nav_menu) {
            $toal_menu_count = $nav_menu->count + 1;
            $options = '';
            $status = false;

            for ($i = 1; $i <= $toal_menu_count; $i++) {
                $selected = '';
                if ( isset( $nav_menu->menu_items ) && ! empty( $nav_menu->menu_items ) && ( ! empty( $nav_menu->menut_items->menu_order ) && $i == $nav_menu->menu_items->menu_order) ) {
                    $status = true;
                    $selected = "selected";
                }

                if ($status == false && $i == $toal_menu_count) {
                    $selected = "selected";
                }

                $options .= '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
            }
            return $options;
        }

        /**
         * get parent child relation with dashes
         * @param string $dashes
         * @return string
         */
        private function wp_quick_menu_extra_spaces($dashes) {
            $spaces = '';
            if ($dashes != '') {
                $mdash = str_replace('-', '&mdash;', $dashes);
                $nbsp = str_replace('-', '&nbsp;&nbsp;', $dashes);
                $spaces = $nbsp . $mdash;
            }
            return $spaces;
        }

        /**
         * Retrieves the items in a nav menu
         *
         * @param   int         $nav_menu_id            required                ID of the nav menu we want items for
         * @uses    wp_get_nav_menu_items()                                     Returns nav menu items given nav_id and args
         * @return  object      $items                                          The list of nav_menu objects for the menu
         */
        private function wp_quick_menu_nav_menu_items($nav_menu_id) {

            $args = array(
                'order' => 'ASC',
                'orderby' => 'menu_order',
                'post_type' => 'nav_menu_item',
                'post_status' => 'publish | draft',
                'output' => ARRAY_A,
                'output_key' => 'menu_order',
                'nopaging' => true,
                'update_post_term_cache' => true);

            $items = wp_get_nav_menu_items( absint( $nav_menu_id ), $args);

            return (object) $items;
        }

        /**
         * Get the data for a specific menu item
         *
         * @param   array           $menu_items             required                Menu objects
         * @param   int             $db_id                  required                The database id for the menu item
         * @return object
         */
        private function wp_quick_menu_get_specific_menu_entry($menu_items, $db_id) {

            foreach ($menu_items as $key => $menu_item) {
                if ( absint( $menu_item->ID )  == absint( $db_id ) ) {
                    return (object) $menu_items[ absint( $key ) ];
                }
            }

            return stdClass;
        }

        /**
         * Removes a menu item
         *
         * @param   array           $menu_items                 required                    menu objects
         * @param   int             $db_id                      required                    The database id for the menu item
         * @return object
         */
        private function wp_quick_menu_remove_specific_menu_entry_logically($menu_items, $db_id) {

            $minus = 0;

            foreach ($menu_items as $key => $menu_item) {
                if ( absint( $menu_item->ID ) == absint( $db_id ) ) {
                    $minus = 1;
                    unset($menu_items[$key]);
                    continue;
                }

                if ($minus > 0) {
                    $menu_items[$key]->menu_order = $menu_items[$key]->menu_order - 1;
                    wp_update_post($menu_items[ absint( $key ) ]);
                }
            } // foreach

            return (object) $menu_items;

        } // wp_quick_menu_remove_specific_menu_entry_logically

        /**
         * Returns the object for the nav_menu entry
         *
         * If we get a match between the $menu_item->object_id and $post_id
         * that means our current page is in a nav menu and should be returned.
         * If there is no match we return an empty class.
         *
         * @TODO should the return actually be null or something else
         *
         * @param   int             $nav_menu_id            required            ID of the nav menu we're checking for matches
         * @param   int             $post_ID                required            the ID of the post we're matching to a nav menu entry
         * @uses    $this->wp_quick_menu_nave_menu_items()                      returns all the items (entries) in a nav menu
         * @return stdClass
         */
        private function wp_quick_menu_check_menu_entry($nav_menu_id, $post_id) {

            $menu_items = $this->wp_quick_menu_nav_menu_items( absint( $nav_menu_id ) );

            foreach ($menu_items as $menu_item) {
                if ( absint( $menu_item->object_id ) == absint( $post_id ) ) {
                    return (object) $menu_item;
                }
            } // foreach

            return new stdClass();

        } // wp_quick_menu_check_menu_entry

        /**
         * get class for menu entry
         * @param type $nav_menus
         * @return string
         */
        private function wp_quick_menu_get_menu_entry_class($nav_menu) {
            return (!isset($nav_menu->menu_items->ID)) ? 'wp-quick-menu-details-hide' : 'wp-quick-menu-details';
        }

        /**
         * get class for menu entry
         * @param type $nav_menus
         * @return string
         */
        private function wp_quick_menu_get_menu_entry_default_checked($nav_menu) {
            return (!isset($nav_menu->menu_items->ID)) ? '' : 'checked';
        }

        /**
         * get menu item title
         * @param type $nav_menu
         * @return string
         */
        private function wp_quick_menu_get_menu_item_title($nav_menu) {
            return (isset($nav_menu->menu_items->title)) ? $nav_menu->menu_items->title : '';
        }

        /**
         * get menu Item Position
         * @param type $nav_menu
         * @return int
         */
        private function wp_quick_menu_get_menu_item_position($nav_menu) {
            return (isset($nav_menu->menu_items->menu_order)) ? (int) $nav_menu->menu_items->menu_order : 0;
        }

        /**
         * get menu its DB ID
         * @param type $nav_menu
         * @return int
         */
        private function wp_quick_menu_get_menu_item_db_id($nav_menu) {
            return (!isset($nav_menu->menu_items->ID)) ? 0 : $nav_menu->menu_items->ID;
        }

        /**
         * get menu item ttitle
         * @param type $nav_menu
         * @return string
         */
        private function wp_quick_menu_get_menu_item_attr_title($nav_menu) {
            return (!isset($nav_menu->menu_items->attr_title)) ? '' : $nav_menu->menu_items->attr_title;
        }

        /**
         * get menu item description
         * @param type $nav_menu
         * @return string
         */
        private function wp_quick_menu_get_menu_item_desc($nav_menu) {
            return (!isset($nav_menu->menu_items->description)) ? '' : $nav_menu->menu_items->description;
        }

        /**
         * get menu item classes
         * @param type $nav_menu
         * @return string
         */
        private function wp_quick_menu_get_menu_item_classes($nav_menu) {
            return (!isset($nav_menu->menu_items->description)) ? '' : implode(' ', $nav_menu->menu_items->classes);
        }

        private function wp_quick_menu_get_menu_item_xfn($nav_menu) {

            return (!isset($nav_menu->menu_items->xfn)) ? '' : $nav_menu->menu_items->xfn;
        }

        /**
         * Returns the parent item for a menu item if it exists
         *
         * @param   object          $nav_menu           required                    The object we should be checking
         * @return  int             $parent                                         ID of the parent item
         */
        private function wp_quick_menu_get_menu_item_parent($nav_menu) {

            if ( isset( $nav_menu->menu_items ) && ! empty( $nav_menu->menu_items->menu_item_parent ) ){

            error_log( print_r( $nav_menu, true ) );
                $parent = (int) $nav_menu->menu_items->menu_item_parent;
            } else {
                $parent = 0;
            }

            return absint( $parent );

        } // wp_quick_menu_get_menu_item_parent

    }

}

$wp_quick_menu = new WP_Quick_Menu();
