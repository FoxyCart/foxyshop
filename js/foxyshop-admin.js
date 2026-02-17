jQuery(document).ready(function ($) {
    $("#product_feed_view").tablesorter({
        'cssDesc': 'asc sorted',
        'cssAsc': 'desc sorted',
        'headers': { 0: { sorter: false } }
    });

    if ($.datepicker) {
        $(".foxyshop_date_field").datepicker({ dateFormat: 'yy-mm-dd' });
    }

    $(".foxyshop_search_toggle").on("click", function () {
        var $toggle = $(this);
        var $tbody = $toggle.closest("table").find("tbody");
        var page = $toggle.data("search-page");
        $toggle.find(".dashicons").toggleClass("dashicons-arrow-right dashicons-arrow-down");
        $tbody.slideToggle(200, function () {
            var isOpen = $(this).is(":visible") ? "1" : "0";
            document.cookie = "foxyshop_search_open_" + page + "=" + isOpen + ";path=/;max-age=31536000";
        });
    });
});