jQuery(document).ready(function($) {

    var menuWrapper = $('.inside.pc_quick_menu_wrapper');
    var menuSelect = $(menuWrapper).find('select#wp_quick_nav_menu');
    var menuPositionSelect = $(menuWrapper).find('.pc_quick_menu_position');
    var spinner = $(menuPositionSelect).find('.spinner');

    // detecting menu select
    $(menuSelect).on('change', function(){
        var selectedMenu = $(this).val();
        var currentPostID = $('#post_ID').val();

        console.log( currentPostID );

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
                        placeholder: "pc-quick-drop-target"
                    });
                    $('.pc_quick_menu_item_position').disableSelection();
                } // yup

                if ( false === response.data.success ){
                    console.log( 'fail' );
                }

            }); // end ajax post

        // return the menu items to the page
        //  - make sure you clear the area so that you can put in new menu items
        // make sure we append the current page to the menu (should probably save the menu right away as well)
    });

    // @todo detect drag/drop change in menu order and save


});