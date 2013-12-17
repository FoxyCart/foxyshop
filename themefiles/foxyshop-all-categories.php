<?php /*
------------ ATTENTION ------------
If you need to edit this template, do not edit the version in the plugin directory. Place a copy in your template folder and edit it there.
This will allow you to upgrade FoxyShop without breaking your customizations. More details here: http://www.foxy-shop.com/documentation/theme-customization/
-----------------------------------
*/ ?>

<?php get_header(); ?>

<?php foxyshop_include('header'); ?>
<div id="foxyshop_container">
	<?php

	//echo '<h1 id="foxyshop_category_title">Products</h1>';

	//Show all children that have a parent of 0 (top level ones)
	//Options: (Parent ID) (Show Product Count in Parentheses) <- Shows all child products (including sub categories)
	foxyshop_category_children(0, false);

	?>
</div>
<?php foxyshop_include('footer'); ?>

<script type="text/javascript">
jQuery(document).ready(function($){
	//This is set up for a three-column display. For a two column you need to do: nth-child(odd)
	$(".foxyshop_categories>li:nth-child(3n+1)").css("clear","left");
});
</script>

<?php get_footer(); ?>