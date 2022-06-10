<?php if (!empty($nav_menus)) { ?>

    <div class="inside" style="margin:6px;">

    <p>
        <div><label for="wp_quick_menu_selected_menu">
            <?php _e('Menu', 'wp_quick_menu'); ?>
        </label></div>

        <select id="wp_quick_nav_menu" name="wp_quick_nav_menu">
            <option value=""><?php echo __( '- Select menu -', 'wp_quick_menu' ); ?></option>
            <?php foreach ($nav_menus as $nav_menu) { ?>
                <option
                    value="<?php echo absint( $nav_menu->term_id ); ?>"
                    <?php if ($this->wp_quick_menu_get_menu_entry_default_checked($nav_menu)) { echo 'selected="selected"'; } ?>
                >
                <?php echo esc_attr( $nav_menu->name ); ?>
                </option>
            <?php } ?>
        </select>
    </p>



    <script type="text/javascript">
    (function ($) {

        $('#wp_quick_nav_menu').bind('change', function() {
            var selectedMenu = $(this).val();
            $('.wp_quick_menu_section .wp-quick-menu-details-hide').hide();
            $('#wp_quick_menu_section_' + selectedMenu + ' .wp-quick-menu-details-hide').show();
        })

    })(jQuery);
    </script>



        <?php foreach ($nav_menus as $nav_menu) { ?>
            <div id="wp_quick_menu_section_<?php echo absint( $nav_menu->term_id ); ?>" class="wp_quick_menu_section">

                <div class="<?php /* @TODO sanitize_html_class? */ print $this->wp_quick_menu_get_menu_entry_class($nav_menu); ?> wp-quick-menu-details-for-<?php print absint( $nav_menu->term_id ); ?>">
                    <?php if ($num_menus > 1) { ?>
                        <p style="border-top:1px solid #eee;padding-top:5px"><strong><?php echo $nav_menu->name; ?></strong></p>
                    <?php } ?>

                    <p>
                        <div>
                            <label for="wp_quick_menu_item_title">
                                <?php _e('Label', 'wp_quick_menu'); ?>
                            </label>
                        </div>
                        <input type="text" id="wp_quick_menu_item_title" name="wp_quick_menu_item_title[<?php print absint( $nav_menu->term_id ); ?>]" value="<?php print $this->wp_quick_menu_get_menu_item_title($nav_menu); ?>" size="25" />
                    </p>

                    <?php if ($this->wp_quick_menu_get_menu_entry_default_checked($nav_menu) != '') { ?><p>
                        <input class="wp_quick_nav_menu_remove" type="checkbox" name="wp_quick_nav_menu_remove[]" value="<?php print $nav_menu->term_id ?>"  />
                        <span class="redcolor">Remove this <?php print $post->post_type; ?> menu entry </span>
                    </p><?php } ?>

                    <input type="hidden" name="wp_quick_menu_item_object_id[<?php print absint( $nav_menu->term_id ); ?>]" value="<?php print absint( $post->ID ); ?>">
                    <input type="hidden" name="wp_quick_menu_item_db_id[<?php print absint( $nav_menu->term_id ); ?>]" value="<?php print $this->wp_quick_menu_get_menu_item_db_id($nav_menu); ?>">
                    <input type="hidden" name="wp_quick_menu_item_object[<?php print absint( $nav_menu->term_id ); ?>]" value="page">
                    <input type="hidden" name="wp_quick_menu_item_type[<?php print absint( $nav_menu->term_id ); ?>]" value="post_type">
                    <p class="wp_quick_menu_advance_settings" navmenu_id = "<?php print absint( $nav_menu->term_id ); ?>"> <span></span> <?php echo _e('Advanced Settings', 'wp_quick_menu'); ?> </p>
                    <div id="advance_settings_<?php print absint( $nav_menu->term_id ); ?>" class="advance-settings">
                        <p>
                            <div><label for="wp_quick_menu_item_parent_id">
                                <?php _e('Parent', 'wp_quick_menu'); ?>
                            </label></div>

                            <select name="wp_quick_menu_item_parent_id[<?php print absint( $nav_menu->term_id ); ?>]" menu_id="<?php print absint( $nav_menu->term_id ); ?>" id="wp_quick_menu_item_parent_id_<?php print absint( $nav_menu->term_id ); ?>" class="wp_quick_menu_item_parent_id">
                                <?php print $this->wp_quick_menu_parent_child_relation($nav_menu); ?>
                            </select>
                        </p>
                        <div>
                            <label for="wp_quick_menu_item_position">
                                <?php _e('Position', 'wp_quick_menu'); ?>
                            </label>
                        </div>
                        <select name="wp_quick_menu_item_position[<?php print absint( $nav_menu->term_id ); ?>]" id="wp_quick_menu_item_position_<?php print absint( $nav_menu->term_id ); ?>" class="wp_quick_menu_item_position">
                            <?php print $this->wp_quick_menu_get_menu_order($nav_menu); ?>
                        </select>
                        <div class="possible-position-<?php print absint( $nav_menu->term_id ); ?>" style="font-size:.8em;"></div>

                        <div class="advance_settings_extra">

                            <p>
                                <label for="wp_quick_menu_item_attr_title">
                                    <?php _e('Title Attribute', 'wp_quick_menu'); ?>
                                </label>
                            </p>
                            <input type="text" id="wp_quick_menu_item_attr_title" name="wp_quick_menu_item_attr_title[<?php print absint( $nav_menu->term_id ); ?>]" value="<?php print $this->wp_quick_menu_get_menu_item_attr_title($nav_menu); ?>" size="25" />

                            <p>
                                <label for="wp_quick_menu_item_desc">
                                    <?php _e('Description', 'wp_quick_menu'); ?>
                                </label>
                            </p>
                            <textarea id="wp_quick_menu_item_desc" name="wp_quick_menu_item_desc[<?php print absint( $nav_menu->term_id ); ?>]" cols="23" /><?php print $this->wp_quick_menu_get_menu_item_desc($nav_menu); ?></textarea>

                            <p>
                                <label for="qpquickmenu_item_class">
                                    <?php _e('CSS Classes (Optional)', 'wp_quick_menu'); ?>
                                </label>
                            </p>
                            <input type="text" id="wp_quick_menu_item_classes" name="wp_quick_menu_item_classes[<?php print absint( $nav_menu->term_id ); ?>]" value="<?php print $this->wp_quick_menu_get_menu_item_classes($nav_menu); ?>" size="25" />

                            <p>
                                <label for="wp_quick_menu_item_xfn">
                                    <?php _e('Link Relationship (XFN)', 'wp_quick_menu'); ?>
                                </label>
                            </p>
                            <input type="text" id="wp_quick_menu_item_xfn" name="wp_quick_menu_item_xfn[<?php print absint( $nav_menu->term_id ); ?>]" value="<?php print $this->wp_quick_menu_get_menu_item_xfn($nav_menu); ?>" size="25" />


                            <p>
                                <label for="wp_quick_menu_item_target">
                                    <?php _e('Select Menu item Target', 'wp_quick_menu'); ?>
                                </label>
                            </p>
                            <select name="wp_quick_menu_item_target[<?php print absint( $nav_menu->term_id ); ?>]" id="wp_quick_menu_item_target">
                                <option value="" selected="selected">open link in same tab</option>
                                <option value="_blank">open link in new tab</option>
                            </select>

                        </div>

                    </div>
                </div>
            </div>
        <?php } ?>

    </div><!-- inside -->

<?php } ?>
