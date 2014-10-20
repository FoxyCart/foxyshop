<?php /*
------------ ATTENTION ------------
If you need to edit this template, do not edit the version in the plugin directory. Place a copy in your template folder and edit it there.
This will allow you to upgrade FoxyShop without breaking your customizations. More details here: http://www.foxy-shop.com/documentation/theme-customization/

This file is for FoxyCart version 1.1. Do not use for version 2+
-----------------------------------

------------ FOXYCART TEMPLATE INSTRUCTIONS ------------
You can find these templates at:
http://yoursite.com/foxycart-checkout-template/
http://yoursite.com/foxycart-receipt-template/

*/
global $foxyshop_settings;

//Remove jQuery and FoxyCart Includes
add_action('wp_enqueue_scripts', 'foxyshop_remove_jquery', 99);
remove_action('wp_footer', 'foxyshop_insert_google_analytics', 100);
remove_action('wp_head', 'foxyshop_insert_foxycart_files');
remove_action('init', 'foxyshop_insert_jquery');

//Do Special Google Analytics If Required
add_action('wp_footer', 'foxyshop_insert_google_analytics_receipt');

//Wrap RAW tags
add_action('wp_head', 'foxycart_template_start_raw', 1);
add_action('wp_head', 'foxycart_template_end_raw', 999);
function foxycart_template_start_raw() { echo "{% raw %}"; }
function foxycart_template_end_raw() { echo "{% endraw %}<style></style>"; }

//Put Special CSS in Head
add_action('wp_head', 'foxycart_template_header_includes');
function foxycart_template_header_includes() {
	?>
<!--<link rel="shortcut icon" href="<?php bloginfo("url");?>/favicon.ico" />-->
<meta name="ROBOTS" content="NOINDEX">
<link rel="stylesheet" href="https://^^store_domain^^/themes/standard/styles.css" type="text/css" media="screen" charset="utf-8" />
<style type="text/css">
body {
	padding: 0;
	margin: 0;
}
.fc_cart_item_code, .fc_cart_category_code, .fc_cart_item_weight, .fc_minicart, #fc_minicart {
	display: none !important;
}
</style>

	<?php
}
?>


<?php get_header(); ?>
<?php foxyshop_include('header'); ?>
<div id="foxyshop_container">



<?php if (version_compare($foxyshop_settings['version'], '1.1', "<=")) { ?>
^^cart^^
^^receipt^^
<?php } else { ?>
{% include 'cart.inc.twig' %}
{% include 'email_order_details.inc.twig' %}
<?php } ?>



</div>
<?php foxyshop_include('footer'); ?>
<?php get_footer(); ?>
