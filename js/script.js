jQuery(document).ready(function($) {

    var menuWrapper = $('.inside.pc_quick_menu_wrapper');
    var menuSelect = $(menuWrapper).find('select#wp_quick_nav_menu');
    var menuPositionSelect = $(menuWrapper).find('.pc_quick_menu_position');
    var spinner = $(menuPositionSelect).find('.spinner');

    // detecting menu select
    $(menuSelect).on('change', function(){
        var selectedMenu = $(this).val();
        var currentPostID = $('#post_ID').val();

        var data = {
            'action': '$thing',
            'security': AllEmail.nonce
        }

        $.post( AllEmail.ajaxurl, data, function( response ) {

                // @todo hide spinner

                if ( true === response.data.success ){
                    console.log( 'succes' );
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