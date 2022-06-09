(function ($) {
    function wp_quick_menu_possible_postion(menu_parent, menu_ID) {
        return (typeof possible_values[menu_ID][menu_parent] != 'undefined') ? possible_values[menu_ID][menu_parent] : '';
    }

    $(document).ready(function () {
        $(".accordion-section-title").click(function () {
            var selected = $(this);
            setTimeout(function () {
                $('html, body').animate({
                    scrollTop: selected.offset().top - 40
                }, 1000)
            }, 300);
        });

        $(".wp_quick_menu_item_parent_id").each(function () {
            var menu_parent = $(this).val();
            var menu_ID = $(this).attr('menu_id');
            $('div.possible-position-' + menu_ID).html(wp_quick_menu_possible_postion(menu_parent, menu_ID));
        })

        $(".wp_quick_menu_item_parent_id").change(function () {
            var menu_parent = $(this).val();
            var menu_ID = $(this).attr('menu_id');
            $('div.possible-position-' + menu_ID).html(wp_quick_menu_possible_postion(menu_parent, menu_ID));
        })

        $("input.wp_quick_nav_menu").each(function () {
            if ($(this).is(":checked")) {
                $("div.wp-quick-menu-details-for-" + $(this).val()).show();
            }
        })

        $("input.wp_quick_nav_menu_remove").each(function () {
            if ($(this).is(":checked")) {
                $("div.wp-quick-menu-details-for-" + $(this).val()).hide();
            }
        })

        $("input.wp_quick_nav_menu").click(function () {
            var value = $(this).val();
            $("div.wp-quick-menu-details-for-" + value).slideToggle("slow");
        });


        $("input.wp_quick_nav_menu_remove").click(function () {
            var value = $(this).val();
            $("div.wp-quick-menu-details-for-" + value).slideToggle("slow");
        });

        $("p.wp_quick_menu_advance_settings").click(function () {
            $("div#advance_settings_" + $(this).attr('navmenu_id')).slideToggle("slow");
            return false;
        });


    });
})(jQuery);