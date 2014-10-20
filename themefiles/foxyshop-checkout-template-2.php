<?php /*
------------ ATTENTION ------------
If you need to edit this template, do not edit the version in the plugin directory. Place a copy in your template folder and edit it there.
This will allow you to upgrade FoxyShop without breaking your customizations. More details here: http://www.foxy-shop.com/documentation/theme-customization/

This file is for FoxyCart version 2+. You may run into javascript problems so it might be better to avoid wp_head() if you can
and to put the header contents in manually. Avoid unnecesary libraries and styles. Put id="fc" in your <html> tag.
-----------------------------------

------------ FOXYCART TEMPLATE INSTRUCTIONS ------------
You can find these templates at:
http://yoursite.com/foxycart-checkout-template/
http://yoursite.com/foxycart-receipt-template/

*/
global $foxyshop_settings;

//Remove jQuery and FoxyCart Includes
add_action('wp_enqueue_scripts', 'foxyshop_remove_jquery', 99);
wp_deregister_script('jquery');
remove_action('wp_footer', 'foxyshop_insert_google_analytics', 100);
remove_action('wp_footer', 'foxyshop_insert_foxycart_loader');
remove_action('init', 'foxyshop_insert_jquery');

//Wrap RAW tags
add_action('wp_head', 'foxycart_template_start_raw', 2);
add_action('wp_head', 'foxycart_template_end_raw', 999);
function foxycart_template_start_raw() { echo "{% raw %}"; }
function foxycart_template_end_raw() { echo "{% endraw %}<style></style>"; }

//Put Special CSS in Head
add_action('wp_head', 'foxycart_template_header_includes', 1);
function foxycart_template_header_includes() {
	?>
<!--<link rel="shortcut icon" href="<?php bloginfo("url");?>/favicon.ico" />-->
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1, minimal-ui">
<meta name="ROBOTS" content="NOINDEX">
<link rel="stylesheet" href="{{ config.css_file }}" media="screen">
<!-- FC script insertion -->{{ fc_header_content|raw }}<!-- /FC script insertion -->
<!--[if lt IE 9 ]>
    <script type="text/javascript">
        var IEElms = ['article', 'aside', 'header', 'main', 'nav', 'section'];
        for (var i = 0; i < IEElms.length; i++) {
            document.createElement(IEElms[i]);
        };
    </script>
<![endif]-->
<style type="text/css">


</style>
	<?php
}

get_header(); ?>
</head>
<body>
<!-- has to be here because of chrome bug -->
{% include 'svg.inc.twig' %}

<?php foxyshop_include('header'); ?>
<div id="foxyshop_container">


{% import "utils.inc.twig" as utils %}
{% use 'checkout.inc.twig' %}
{{ block('checkout') }}


<!-- FC footer script insertion -->{% include template_from_string(fc_footer_content) %}<!-- /FC footer scripts -->

</body>
</html>

</div>
<?php foxyshop_include('footer'); ?>

<script type="text/javascript">
jQuery(document).ready(function($){
	$("html").attr("id", "fc");
});
</script>

<?php get_footer(); ?>
