jQuery(document).ready(function($) {

    var menuWrapper = $('.inside.pc_quick_menu_wrapper');
    var menuSelect = $(menuWrapper).find('select#wp_quick_nav_menu');

    // detecting menu select
    $(menuSelect).on('change', function(){
        var selectedMenu = $(this).val();

        // need to build out the HTML for the menu now
    });


});