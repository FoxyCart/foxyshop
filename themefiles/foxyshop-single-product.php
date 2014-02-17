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
while (have_posts()) : the_post();

	//Initialize Product
	global $product;
	$product = foxyshop_setup_product();


	//This is for testing to see what is included in the $product array
	//print_r($product);

	//Initialize Form
	foxyshop_start_form();

	//Write Breadcrumbs
	foxyshop_breadcrumbs(" &raquo; ", "&laquo; Back to Products");


	//Shows Main Image and Optional Slideshow
	//Available Built-in Options: prettyPhoto (lightbox), cloud-zoom (inline zooming), or colorbox (native FoxyCart lightbox)
	//Second arg writes css and js includes on page
	//If you want to make more customizations, you can grab the code from helperfunctions.php line ~650 and paste here
	//-------------------------------------------------------------------------------------------------------------------------
	foxyshop_build_image_slideshow("prettyPhoto", true);
	//foxyshop_build_image_slideshow("cloud-zoom", true); //Note, make sure to use jQuery 1.7.2 as 1.8+ seems to be incompatible for now
	//foxyshop_build_image_slideshow("colorbox", true); //only recommended for 0.7.2+


	//Main Product Information Area
	echo '<div class="foxyshop_product_info">';
	//edit_post_link('<img src="' . FOXYSHOP_DIR . '/images/editicon.png" alt="Edit Product" width="16" height="16" />','<span class="foxyshop_edit_product">','</span>');
	echo '<h2>' . apply_filters('the_title', $product['name']) . '</h2>';

	//Show a sale tag if the product is on sale
	//if (foxyshop_is_on_sale()) echo '<p class="sale-product">SALE!</p>';

	//Product Is New Tag (number of days since added)
	//if (foxyshop_is_product_new(14)) echo '<p class="new-product">NEW!</p>';

	//Main Product Description
	echo $product['description'];


	//Show Variations (showQuantity: 0 = Do Not Show Qty, 1 = Show Before Variations, 2 = Show Below Variations)
	//If Qty is turned off on product, Qty box will not be shown at all
	foxyshop_product_variations(2);

	//(style) clear floats before the submit button
	echo '<div class="clr"></div>';

	//Check Inventory Levels and Display Status (last variable allows backordering of out of stock items)
	foxyshop_inventory_management("There are only %c item%s left in stock.", "Item is not in stock.", false);

	//Add On Products ($qty [1 or 0], $before_entry, $after_entry)
	foxyshop_addon_products();

	//Add To Cart Button
	echo '<button type="submit" name="x:productsubmit" id="productsubmit" class="foxyshop_button">Add To Cart</button>';

	//Shows the Price (includes sale price if applicable)
	echo '<div id="foxyshop_main_price">';
	foxyshop_price();
	echo '</div>';

	//Shows any related products
	foxyshop_related_products("Related Products");


	//Custom Code Can Go Here








	//Ends the form
	echo '</div>';
	echo '</form>';


endwhile;
?>

	<div class="clr"></div>
</div>

<?php foxyshop_include('footer'); ?>

<?php get_footer(); ?>
