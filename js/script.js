jQuery(document).ready(function($) {

    var menuWrapper = $('.inside.pc_quick_menu_wrapper');
    var menuSelect = $(menuWrapper).find('select#wp_quick_nav_menu');
    var menuPositionSelect = $(menuWrapper).find('.pc_quick_menu_position');
    var userFeedback = $('#pcq_user_feedback');
    var spinner = $(userFeedback).find('.spinner');
    var userMessage = $(userFeedback).find('#pcq_feedback_message');

    // if there is already a selected item i need to get the menu items
    var selectedMenu = $(menuSelect).find('option:selected').val()
    if ( selectedMenu.length ){
        var currentPostID = $('#post_ID').val();
        pcq_process_select( selectedMenu, currentPostID, spinner );
    }

    // detecting menu select change when we have a new item for a menu
    $(menuSelect).on('change', function(){

        var oldMenu = $(menuWrapper).find('#old-menu').data('old-menu');
        var selectedMenu = $(this).val();
        var currentPostID = $('#post_ID').val();

        pcq_process_select( selectedMenu, currentPostID, spinner, oldMenu );
    });

    /**
     * Abstracts the processing of the menu items showing in the metabox so that we have them
     *
     * @since 1.2
     *
     * @param {int} selectedMenu ID of the selected menu
     * @param {int} currentPostID ID of the current post
     * @param {*} spinner
     */
    function pcq_process_select( selectedMenu, currentPostID, spinner, oldMenu = null ){
        // showing spinner
        $(spinner).css('visibility', 'visible' );

        // compile the data for our ajax call
        var data = {
            'action': 'pc_quick_get_menu_items',
            'selected_menu': selectedMenu,
            'old_menu_item': oldMenu,
            'current_post_id': currentPostID,
            'security': PCQuickMenuScripts.pc_quick_menu_nonce
        }

        $.post( PCQuickMenuScripts.ajaxurl, data, function( response ) {

                // hiding spinner
                $(spinner).css('visibility', 'hidden' );

                if ( true === response.data.success ){
                    // adding our menu items
                    $(menuPositionSelect).empty().append( response.data.value );

                    // making our menu items sortable
                    $('.pc_quick_menu_item_position').sortable({
                        placeholder: "pc-quick-drop-target",
                        start: function( event, ui){
                            ui.item.data('item_order', ui.item.index());
                        },
                        stop: function( event, ui ){
                            var start = ui.item.data('item_order');
                            if ( start != ui.item.index()){
                                //console.log('moved');
                            } else {
                                // the update function runs before stop so it may log as
                                // not moved once the elements are updated
                                //console.log('not moved');
                            }

                        },
                        update: function( event, ui ){
                            // get menu being update
                            var menuToUpdate = $(menuSelect).find(':selected').val();

                            // build array of menu items for saving
                            var updatedItems = [];
                            $(this).children('li').each(function(index){

                                //first we need to update the position index
                               $(this).attr('data-menu-item-position', index);

                               var menuItemDbId = $(this).data('menu-item-db-id');
                               var menuItemObjectId = $(this).data('menu-item-object-id');
                               var menuItemObject = $(this).data('menu-item-object');
                               var menuItemParentId = $(this).data('menu-item-parent-id');
                               var menuItemType = $(this).data('menu-item-type');
                               var menuItemTitle = $(this).data('menu-item-title');
                               var menuItemUrl = $(this).data('menu-item-url');
                               var menuItemDescription = $(this).data('menu-item-description');
                               var menuItemAttrTitle = $(this).data('menu-item-attr-title');
                               var menuItemTarget = $(this).data('menu-item-target');
                               var menuItemClasses = $(this).data('menu-item-classes');
                               var menuItemDepth = $(this).data('menu-item-depth');
                               var menuItemXfn = $(this).data('menu-item-xfn');
                               var menuItemPosition = $(this).data('menu-item-position');

                               updatedItems.push({
                                'menu-item-db-id': menuItemDbId,
                                'menu-item-object-id': menuItemObjectId,
                                'menu-item-object': menuItemObject,
                                'menu-item-parent-id': menuItemParentId,
                                'menu-item-type': menuItemType,
                                'menu-item-title': menuItemTitle,
                                'menu-item-url': menuItemUrl,
                                'menu-item-description': menuItemDescription,
                                'menu-item-attr-title': menuItemAttrTitle,
                                'menu-item-target': menuItemTarget,
                                'menu-item-classes': menuItemClasses,
                                'menu-item-xfn': menuItemXfn,
                                'menu-item-position': menuItemPosition
                               });

                            });

                            // after we've looped through the items we need to do the ajax thing to update them
                            pcq_save_updated_menu_items( menuToUpdate, updatedItems, spinner );

                        }
                    });
                    $('.pc_quick_menu_item_position').disableSelection();

                    // making sure we default the current menu item to open
                    var currentItem = $('.pc_quick_menu_wrapper').find('.current-menu-item');
                    $(currentItem).find('.pcq-edit-item-form').slideDown();

                } // yup

                if ( false === response.data.success ){
                    console.log( 'fail' );
                }

            }); // end ajax post


    }

    /**
     * Sends our updated menu items to PHP for saving the attributes
     *
     * @param {int}     menuToUpdate    required            ID of the menu we need to update
     * @param {array}   updatedItems    required            nested array of the menu items we need to update via `wp_save_nav_menu`
     */
    function pcq_save_updated_menu_items( menuToUpdate, updatedItems, spinner ){

        $(spinner).css('visibility', 'visible');

        var data = {
            'action': 'pcq_update_menu',
            'menu-to-update': menuToUpdate,
            'menu-items': updatedItems,
            'security': PCQuickMenuScripts.pc_quick_menu_nonce
        }

        $.post( PCQuickMenuScripts.ajaxurl, data, function( response ) {

                $(spinner).css('visibility', 'hidden' );
                if ( true === response.data.success ){
                    $('#pcq_feedback_message').empty().show().append(response.data.message).delay(1500).fadeOut();
                } // yup

                if ( false === response.data.success ){
                    $('#pcq_feedback_message').empty().show().append(response.data.message).delay(1500).fadeOut();
                }

            }); // end ajax post

    } // pcq_save_updated_menu_items

    /**
     * Deletes a menu item when you click the trash
     */
    $(menuWrapper).on('click touchstart', '.pcq_delete_item', function(){

        // show spinner
        $(spinner).css('visibility', 'visible' );

        var parentItem = $(this).parent('.pc_quick_menu_item');
        var postId = $(parentItem).data('menu-item-db-id');
        var deletePostId = $(parentItem).data('menu-item-object-id');
        var currentPostID = $('#post_ID').val();

        var data = {
            'action': 'pcq_delete_menu_item',
            'post_id': postId,
            'security': PCQuickMenuScripts.pc_quick_menu_nonce
        }

        $.post( PCQuickMenuScripts.ajaxurl, data, function( response ) {

                // hide spinner
                $(spinner).css( 'visibility', 'hidden' );

                if ( true === response.data.success ){
                    $('#pcq_feedback_message').empty().show().append(response.data.message).delay(1500).fadeOut();

                    // if we are removing this item from the menu then we need to reset the menu
                    if ( currentPostID == deletePostId ){
                        $('.pc_quick_menu_position').fadeOut(500).delay(500).empty();
                        $(menuSelect).prop('selectedIndex', 0 );
                    } else {
                        $(parentItem).fadeOut(500).delay(500).remove();
                    }
                    // reset the main menu select
                } // yup

                if ( false === response.data.success ){
                    $('#pcq_feedback_message').empty().show().append(response.data.message).delay(1500).fadeOut();
                }

            }); // end ajax post
    });

    /**
     * Toggles the display of the label edit form
     */
    $(menuWrapper).on('click touchstart', '.pcq_edit_item', function(){

        var parentItem = $(this).parents('.pc_quick_menu_item');
        var editForm = $(parentItem).find('.pcq-edit-item-form');

        $(editForm).slideToggle();

    });

    /**
     * Handles saving when you edit a menu item
     */
    $(menuWrapper).on('click touchstart', '.pcq-edit-item-button', function(e){

        e.preventDefault();

        var menuParentWrap = $(this).parents('.pc_quick_menu_item');
        var parentItem = $(this).parent('.pcq-edit-item-form');
        var editSpinner = $(parentItem).find('.spinner');
        var editPostId = $(parentItem).find('.pcq-edit-item-button').data('menu-item-object-id');
        var itemTitle = $(parentItem).find('.pcq-menu-item-title').val();
        var editButton = $(this);

        $(editSpinner).css('visibility', 'visible');

        var data = {
            'action': 'pcq_edit_menu_item',
            'post_id': editPostId,
            'item_title': itemTitle,
            'security': PCQuickMenuScripts.pc_quick_menu_nonce
        }

        $.post( PCQuickMenuScripts.ajaxurl, data, function( response ) {

                // hide spinner
                $(editSpinner).css( 'visibility', 'hidden' );

                if ( true === response.data.success ){
                    $(menuParentWrap).find('.pcq-title-wrap').html(itemTitle);
                    $(editButton).html('Item Updated');
                    $.wait( function(){
                        $(editButton).html('Update');
                    }, 3 );
                } // yup

                if ( false === response.data.success ){
                    // false feedback
                }

            }); // end ajax post
    });

    // gives me a more jQuery way to wait because that's what I feel comfortable with
    $.wait = function( callback, seconds ){
        return window.setTimeout( callback, seconds * 1000 );
    }
});