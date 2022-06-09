<div class="accordion-container">
    <?php if (!empty($nav_menus)) { ?>
        <?php foreach ($nav_menus as $nav_menu) { ?>
            <div class="accordion-section">
                <h3 class="accordion-section-title"><span class="wp-quick-menu-name-title">Menu Name: </span> <?php print $nav_menu->name; ?></h3>
                <div class="accordion-section-content">
                    <p class="wp_quick_nav_menu_<?php print $this->wp_quick_menu_get_menu_entry_default_checked($nav_menu); ?>">
                        <input class="wp_quick_nav_menu" type="checkbox" name="wp_quick_nav_menu[]" value="<?php print $nav_menu->term_id ?>"  <?php print $this->wp_quick_menu_get_menu_entry_default_checked($nav_menu); ?>/>
                        Add this <?php print $post->post_type; ?> to <?php print $this->wp_quick_menu_get_menu_entry_default_checked($nav_menu); ?>
                        <strong><?php print $nav_menu->name; ?></strong>      
                    </p>

                    <?php if ($this->wp_quick_menu_get_menu_entry_default_checked($nav_menu) != '') { ?>
                        <input class="wp_quick_nav_menu_remove" type="checkbox" name="wp_quick_nav_menu_remove[]" value="<?php print $nav_menu->term_id ?>"  />
                        <span class="redcolor">Remove this <?php print $post->post_type; ?> menu entry </span>
                    <?php } ?>

                    <div class="<?php print $this->wp_quick_menu_get_menu_entry_class($nav_menu); ?> wp-quick-menu-details-for-<?php print $nav_menu->term_id ?>">

                        <p>
                            <label for="wp_quick_menu_item_parent_id">
                                <?php _e('Select parent', 'wp_quick_menu'); ?>
                            </label>

                            <select name="wp_quick_menu_item_parent_id[<?php print $nav_menu->term_id; ?>]" menu_id="<?php print $nav_menu->term_id; ?>" id="wp_quick_menu_item_parent_id_<?php print $nav_menu->term_id ?>" class="wp_quick_menu_item_parent_id">
                                <?php print $this->wp_quick_menu_parent_child_relation($nav_menu); ?>
                            </select>
                        </p> 
                        <p>
                            <label for="wp_quick_menu_item_title">
                                <?php _e('Navigation Label', 'wp_quick_menu'); ?>
                            </label>
                        </p>    
                        <input type="text" id="wp_quick_menu_item_title" name="wp_quick_menu_item_title[<?php print $nav_menu->term_id ?>]" value="<?php print $this->wp_quick_menu_get_menu_item_title($nav_menu); ?>" size="25" />

                        <p>
                            <label for="wp_quick_menu_item_position">
                                <?php _e('Position', 'wp_quick_menu'); ?>
                            </label>
                        </p>   
                        <select name="wp_quick_menu_item_position[<?php print $nav_menu->term_id ?>]" id="wp_quick_menu_item_position_<?php print $nav_menu->term_id ?>" class="wp_quick_menu_item_position">
                            <?php print $this->wp_quick_menu_get_menu_order($nav_menu); ?>
                        </select>
                        <div class="possible-position-<?php print $nav_menu->term_id; ?>"></div>

                        <input type="hidden" name="wp_quick_menu_item_object_id[<?php print $nav_menu->term_id ?>]" value="<?php print $post->ID; ?>">
                        <input type="hidden" name="wp_quick_menu_item_db_id[<?php print $nav_menu->term_id ?>]" value="<?php print $this->wp_quick_menu_get_menu_item_db_id($nav_menu); ?>">
                        <input type="hidden" name="wp_quick_menu_item_object[<?php print $nav_menu->term_id ?>]" value="page">
                        <input type="hidden" name="wp_quick_menu_item_type[<?php print $nav_menu->term_id ?>]" value="post_type">
                        <p class="wp_quick_menu_advance_settings" navmenu_id = "<?php print $nav_menu->term_id ?>"> <span></span> Show/Hide advance Settings </p> 
                        <div id="advance_settings_<?php print $nav_menu->term_id ?>" class="advance-settings">
                            <p>
                                <label for="wp_quick_menu_item_attr_title">
                                    <?php _e('Title Attribute', 'wp_quick_menu'); ?>
                                </label>
                            </p>    
                            <input type="text" id="wp_quick_menu_item_attr_title" name="wp_quick_menu_item_attr_title[<?php print $nav_menu->term_id ?>]" value="<?php print $this->wp_quick_menu_get_menu_item_attr_title($nav_menu); ?>" size="25" />

                            <p>
                                <label for="wp_quick_menu_item_desc">
                                    <?php _e('Description', 'wp_quick_menu'); ?>
                                </label>
                            </p>    
                            <textarea id="wp_quick_menu_item_desc" name="wp_quick_menu_item_desc[<?php print $nav_menu->term_id ?>]" cols="23" /><?php print $this->wp_quick_menu_get_menu_item_desc($nav_menu); ?></textarea>

                            <p>
                                <label for="qpquickmenu_item_class">
                                    <?php _e('CSS Classes (Optional)', 'wp_quick_menu'); ?>
                                </label>
                            </p>    
                            <input type="text" id="wp_quick_menu_item_classes" name="wp_quick_menu_item_classes[<?php print $nav_menu->term_id ?>]" value="<?php print $this->wp_quick_menu_get_menu_item_classes($nav_menu); ?>" size="25" />

                            <p>
                                <label for="wp_quick_menu_item_xfn">
                                    <?php _e('Link Relationship (XFN)', 'wp_quick_menu'); ?>
                                </label>
                            </p>    
                            <input type="text" id="wp_quick_menu_item_xfn" name="wp_quick_menu_item_xfn[<?php print $nav_menu->term_id ?>]" value="<?php print $this->wp_quick_menu_get_menu_item_xfn($nav_menu); ?>" size="25" />


                            <p>
                                <label for="wp_quick_menu_item_target">
                                    <?php _e('Select Menu item Target', 'wp_quick_menu'); ?>
                                </label>
                            </p>
                            <select name="wp_quick_menu_item_target[<?php print $nav_menu->term_id ?>]" id="wp_quick_menu_item_target">
                                <option value="" selected="selected">open link in same tab</option>
                                <option value="_blank">open link in new tab</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    <?php } ?>
</div>
