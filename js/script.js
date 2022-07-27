jQuery(document).ready(function($) {

    var menuWrapper = $('.inside.pc_quick_menu_wrapper');
    var menuSelect = $(menuWrapper).find('select#wp_quick_nav_menu');
    var menuPositionSelect = $(menuWrapper).find('.pc_quick_menu_position');
    var spinner = $(menuPositionSelect).find('.spinner');

    // if there is already a selected item i need to get the menu items
    $(menuSelect).children('option').each( function() {
        if (this.selected){
            var selectedMenu = $(this).val();
            var currentPostID = $('#post_ID').val();

            pcq_process_select( selectedMenu, currentPostID, spinner );
        }
    });

    // detecting menu select change when we have a new item for a menu
    $(menuSelect).on('change', function(){
        var selectedMenu = $(this).val();
        var currentPostID = $('#post_ID').val();

        pcq_process_select( selectedMenu, currentPostID, spinner );
        // @todo handle nested item
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
    function pcq_process_select( selectedMenu, currentPostID, spinner ){
        // showing spinner
        $(spinner).css('visibility', 'visible' );

        var data = {
            'action': 'pc_quick_get_menu_items',
            'selected_menu': selectedMenu,
            'current_post_id': currentPostID,
            'security': PCQuickMenuScripts.pc_quick_menu_nonce
        }

        $.post( PCQuickMenuScripts.ajaxurl, data, function( response ) {

                // hiding spinner
                $(spinner).css('visibility', 'hidden' );

                if ( true === response.data.success ){
                    $(menuPositionSelect).empty().append( response.data.value );
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
                            $(this).children('li').each(function(index){
                               $(this).attr('data-item_order', index);
                               // need to save the new order of items
                               // need the menu_id
                               // need to have an array of data for each item similar to the `$values` array where data-item_order is `menu-item-position` in the array
                            });
                        }
                    });
                    $('.pc_quick_menu_item_position').disableSelection();
                } // yup

                if ( false === response.data.success ){
                    console.log( 'fail' );
                }

            }); // end ajax post


    }

    // @todo detect drag/drop change in menu order and save


});