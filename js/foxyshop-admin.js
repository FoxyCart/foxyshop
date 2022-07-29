jQuery(document).ready(function ($) {
    $("#product_feed_view").tablesorter({
        'cssDesc': 'asc sorted',
        'cssAsc': 'desc sorted',
        'headers': { 0: { sorter: false } }
    });

    if ($.datepicker) {
        $(".foxyshop_date_field").datepicker({ dateFormat: 'yy-mm-dd' });
    }
});