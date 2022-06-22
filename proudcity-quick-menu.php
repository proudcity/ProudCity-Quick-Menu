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
        }

        /**
         * add required JS and CSS
         */
        function wp_quick_menu_add_css_js() {
            wp_enqueue_style('wp_quick_menu_style', plugins_url('css/style.css', __FILE__));
            wp_register_script('wp_quick_menu_javascript', plugins_url('js/script.js', __FILE__));

            //$translation_array = array('some_string' => __('Some string to translate'), 'a_value' => '10');
            wp_localize_script('wp_quick_menu_javascript', 'possible_values', $this->wp_quick_menu_get_possible_order_for_all_parent());
            wp_enqueue_script('wp_quick_menu_javascript');
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
         * quick menu meta box call back
         * @param type $post
         */
        function wp_quick_menu_meta_box_call_back($post) {

            wp_nonce_field('wp_quick_menu_meta_box', 'wp_quick_menu_meta_box_nonce');

            $nav_menus = wp_get_nav_menus(array('orderby' => 'name'));

            if (!empty($nav_menus)) {
                foreach ($nav_menus as $key => $value) {
                    $nav_menus[$key]->menu_items = $this->wp_quick_menu_check_menu_entry( absint( $value->term_id ), absint( $post->ID ) );
                }
            } // if ! empty $nav_menus

            $locations = get_registered_nav_menus();
            $menu_locations = get_nav_menu_locations();

            if (get_option('wp_quick_menu_format', 'accordion') === 'select') {
              $num_menus = 0;
              foreach ($nav_menus as $key => $value) {
                $num_menus += !empty($value->menu_items->ID) ? 1 : 0;
              }
              require_once dirname(__FILE__) . "/templates/menu_form_select_template.php";
            }
            else {
              wp_enqueue_script('accordion');
              require_once dirname(__FILE__) . "/templates/menu_form_template.php";
            }

        }

        /**
         *
         */
        private function wp_quick_menu_get_possible_order_for_all_parent() {
            $possible_values = array();

            $nav_menus = wp_get_nav_menus(array('orderby' => 'name'));

            foreach ($nav_menus as $nav_menu) {

                $possible_values[ absint( $nav_menu->term_id ) ] = array();
                $menu_items = $this->wp_quick_menu_nav_menu_items( absint( $nav_menu->term_id ) );
                $possible_values[$nav_menu->term_id][0] = $this->wp_quick_menu_get_possible_menu_order_for_default_parent($menu_items);

                if (!empty($menu_items)) {
                    foreach ($menu_items as $menu_item) {
                        $possible_values[ absint( $nav_menu->term_id ) ][ absint( $menu_item->ID ) ] = $this->wp_quick_menu_get_possible_menu_order_for_specific_parent($menu_items, $menu_item->ID, $menu_item->menu_order);
                    }
                }
            } // foreach

            return $possible_values;
        }

        private function wp_quick_menu_get_possible_menu_order_for_default_parent($menu_items) {
            if (empty($menu_items)) {
                return '<span class="wp-quick-menu-strong">' . __('Possible Positions: ', 'wp_quick_menu') . '</span> 1';
            }
            $possible_values = array();
            foreach ($menu_items as $menu_item) {
                if ($menu_item->menu_item_parent == 0) {
                    $possible_values[] = $menu_item->menu_order;
                }
            }

            if (!isset($_GET['action'])) {
                $possible_values[] = count($menu_items) + 1;
            }


            return '<span class="wp-quick-menu-strong">' . __('Possible Positions: ', 'wp_quick_menu') . '</span>' . implode(", ", $possible_values);
        }

        private function wp_quick_menu_get_possible_menu_order_for_specific_parent($menu_items, $parent, $current_order) {
            $possible_position = array();
            $current_order += 1;
            $child = 0;

            foreach ($menu_items as $menu_item) {
                if ($menu_item->menu_item_parent == $parent && $parent > 0) {
                    $child += 1;
                }
            }


            return '<span class="wp-quick-menu-strong">' . __('Possible Positions: ', 'wp_quick_menu') . '</span>' . implode(", ", range($current_order, $current_order + $child));
        }

        /**
         * save the meta box data
         * @param int $post_ID
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
            if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {

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
                    $this->wp_quick_menu_remove_menu_entry($values['menu-item-db-id']);
                    continue;
                }

                if (!isset($values['menu-item-db-id']) || $values['menu-item-db-id'] <= 0) {
                    $nav_menu_items = $this->wp_quick_menu_nav_menu_items($menuID);
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
         *
         * @param type $menuID
         * @param type $values
         * @return int
         */
        private function wp_quick_menu_update_menu_item_postion($menuID, $values) {

            $nav_menu_items = $this->wp_quick_menu_nav_menu_items( absint( $menuID ) );
            $exisint_menu_prop = $this->wp_quick_menu_get_specific_menu_entry($nav_menu_items, $values['menu-item-db-id']);

            // well no change in menu Item Position
            if ($values['menu-item-position'] == $exisint_menu_prop->menu_order && $values['menu-item-parent-id'] == $exisint_menu_prop->menu_item_parent) {
                return $exisint_menu_prop->menu_order;
            }

            $nav_menu_items_logical = $this->wp_quick_menu_remove_specific_menu_entry_logically($nav_menu_items, $values['menu-item-db-id']);

            $calculated_position = $this->wp_quick_menu_calculate_accurate_menu_position($nav_menu_items_logical, $values);
            $my_post = array('ID' => $exisint_menu_prop->ID, 'menu_order' => $calculated_position);
            wp_update_post($my_post);

            return $calculated_position;

        }

        /**
         * calculate accurate menu position
		 *
         * @param int $menuID
         * @param array $values
         * @return boolean
         */
        private function wp_quick_menu_calculate_accurate_menu_position($nav_menu_items, $values) {

            // no parent selected and menu will be in last position
            if ($values['menu-item-parent-id'] <= 0 && $values['menu-item-position'] >= count($nav_menu_items) + 1) {
                return count($nav_menu_items) + 1;
            }

            if ($values['menu-item-parent-id'] <= 0 && $values['menu-item-position'] <= count($nav_menu_items)) {
                $calculatedpos = $this->wp_quick_menu_check_to_replace_exisitng_menu_item($nav_menu_items, $values['menu-item-position']);
                $this->wp_quick_menu_update_existing_menu_order($calculatedpos, $nav_menu_items);
                return $calculatedpos;
            }

            // parent selected
            if ($values['menu-item-parent-id'] >= 0) {
                $calculated_position = $this->wp_quick_menu_get_parent_position_with_child_count($nav_menu_items, $values['menu-item-parent-id'], $values['menu-item-position']);
                if ($this->wp_quick_menu_update_existing_menu_order($calculated_position, $nav_menu_items)) {
                    return $calculated_position;
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
                if ($nav_menu_item->menu_order >= $current_position) {
                    if ((int) $nav_menu_item->menu_item_parent <= 0) {
                        return $nav_menu_item->menu_order;
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

            if (!empty($nav_menu_items)) {

                foreach ($nav_menu_items as $nav_menu_item) {

                    if ($nav_menu_item->menu_order >= $where_nod_to_update) {

                        $my_post = array();
                        $my_post = array('ID' => absint( $nav_menu_item->ID ) , 'menu_order' => $nav_menu_item->menu_order + 1);
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
                    return $min_position;
                }

                if (($current_position >= $min_position) && ($current_position <= $max_position)) {
                    return $current_position;
                }

                if ($current_position > $max_position) {
                    return $max_position;
                }

                // return the max number of child
                return $max_postion;
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
                $menus =  $_POST['wp_quick_nav_menu'];
                if (is_array($menus)) {
                    foreach ($menus as $value) {
                        $menu_id = (int) $value;
                        if ($menu_id > 0) {
                            $menu_ids[] = $menu_id;
                        }
                    }
                }
                elseif ($menus > 0) {
                    $menu_ids = array($menus);
                }

            }

            unset($_POST['wp_quick_nav_menu']);

            return $menu_ids;
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
                if ($i == $nav_menu->menu_items->menu_order) {
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
         * get nav menu items
         * @param type $nav_menu_id
         * @return array
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

            return $items;
        }

        /**
         * get specific menu entry
         * @param array $menu_items
         * @param int $db_id
         * @return object
         */
        private function wp_quick_menu_get_specific_menu_entry($menu_items, $db_id) {

            foreach ($menu_items as $key => $menu_item) {
                if ( absint( $menu_item->ID )  == absint( $db_id ) ) {
                    return $menu_items[$key];
                }
            }

            return stdClass;
        }

        /**
         *
         * @param array $menu_items
         * @param int $db_id
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
                    wp_update_post($menu_items[$key]);
                }
            }
            return $menu_items;
        }

        /**
         * get the MENU entry
         * @param type $nav_menu_id
         * @param type $post_ID
         * @return stdClass
         */
        private function wp_quick_menu_check_menu_entry($nav_menu_id, $post_id) {

            $menu_items = $this->wp_quick_menu_nav_menu_items( absint( $nav_menu_id ) );

            foreach ($menu_items as $menu_item) {
                if ( absint( $menu_item->object_id ) == absint( $post_id ) ) {
                    return $menu_item;
                }
            } // foreach

            return new stdClass();
        }

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
         * get Menu parent
         * @param type $nav_menu
         * @return int
         */
        private function wp_quick_menu_get_menu_item_parent($nav_menu) {
            return (int) $nav_menu->menu_items->menu_item_parent;
        }

    }

}

$wp_quick_menu = new WP_Quick_Menu();
