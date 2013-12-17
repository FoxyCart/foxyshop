<?php /*
------------ ATTENTION ------------
If you need to edit this template, do not edit the version in the plugin directory. Place a copy in your template folder and edit it there.
This will allow you to upgrade FoxyShop without breaking your customizations. More details here: http://www.foxy-shop.com/documentation/theme-customization/
-----------------------------------
*/

/***************************
Product display details
referenced in:
- foxyshop-all-products.php
- foxyshop-search.php
- foxyshop-single-category.php
- foxyshop-single-category-shortcode.php
- foxyshop-single-category-widget.php
****************************/

//Setup Product
global $product, $post;
$product = foxyshop_setup_product();
if (!$product['hide_product']) {

	echo '<li class="foxyshop_product_box">';

	//Show Image on Left
	echo '<div class="foxyshop_product_image">';
	if ($thumbnailSRC = foxyshop_get_main_image("thumbnail")) echo '<a href="' . $product['url'] . '"><img src="' . $thumbnailSRC . '" alt="' . htmlspecialchars($product['name']) . '" class="foxyshop_main_image" /></a>';
	echo "</div>\n";

	//Show Main Product Info
	echo '<div class="foxyshop_product_info">';
	echo '<h2><a href="' . $product['url'] . '">' . apply_filters('the_title', $product['name']) . '</a></h2>';

	//Show a sale tag if the product is on sale
	//if (foxyshop_is_on_sale()) echo '<p>SALE!</p>';

	//Product Is New Tag
	//if (foxyshop_is_product_new(14)) echo '<p>NEW!</p>';

	if ($product['short_description']) echo "<p>" . $product['short_description'] . "</p>";

	//More Details Button
	echo '<a href="' . $product['url'] . '" class="foxyshop_button">More Details</a>';

	//Add To Cart Button (options)
	//foxyshop_product_link("Add To Cart", false);
	//foxyshop_product_link("Add %name% To Cart", false);
	//echo '<a href="'.foxyshop_product_link("", true).'" class="foxyshop_button">Add To Cart</a>';

	//Show Price (and sale if applicable)
	foxyshop_price();

	echo "</div>\n";

	//Clear Floats and End Item
	echo '<div class="clr"></div>';
	echo "</li>\n";

}
?>