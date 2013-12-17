<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();


/*
Examples:

[productcategory name="category-slug"]
Shows all products in a given category with full markup

[showproduct name="product-slug"]
Shows all product content and markup

[product name="product-slug"]Add XYZ Product To Cart[/product]
<a href="http://yoursite.com/products/product-slug/" class="foxyshop_sc_product_link">Add XYZ Product To Cart</a>

[productlink name="product-slug"]
http://yoursite.com/products/product-slug/

*/


//Show Products in Category
add_shortcode('productcategory', 'foxyshop_productcategory_shortcode');
function foxyshop_productcategory_shortcode($atts, $content = null) {
	global $foxyshop_category_slug;
	extract(shortcode_atts(array(
		"name" => ''
	), $atts));


	$foxyshop_category_slug = $name;

	ob_start();
	foxyshop_include('single-category-shortcode');
	$write = ob_get_contents();
	ob_end_clean();
	return $write;
}


//Show Full Product
add_shortcode('showproduct', 'foxyshop_showproduct_shortcode');
function foxyshop_showproduct_shortcode($atts, $content = null) {
	global $product, $prod;
	$original_product = $product;
	extract(shortcode_atts(array(
		"name" => ''
	), $atts));

	$prod = foxyshop_get_product_by_name($name);
	if (!$prod || !$name) return "";

	ob_start();
	foxyshop_include('single-product-shortcode');
	$write = ob_get_contents();
	ob_end_clean();
	return $write;
}



//Show Product Name with Add To Cart Link
add_shortcode('product', 'foxyshop_product_shortcode');
function foxyshop_product_shortcode($atts, $content = null) {
	global $product;
	$original_product = $product;
	extract(shortcode_atts(array(
		"name" => '',
		"variations" => ''
	), $atts));


	$prod = foxyshop_get_product_by_name($name);
	if (!$prod || !$name) return;
	if ($content == "") $content = "Add To Cart";
	$product = foxyshop_setup_product($prod);
	$write = '<a href="' . foxyshop_product_link("", true, $variations) . '" class="foxyshop_sc_product_link">' . $content . '</a>';
	$product = $original_product;
	return $write;
}


//Show Add To Cart Link For Any Product
add_shortcode('productlink', 'foxyshop_productlink_shortcode');
function foxyshop_productlink_shortcode($atts, $content = null) {
	global $product;
	$original_product = $product;
	extract(shortcode_atts(array(
		"name" => '',
		"variations" => ''
	), $atts));

	$prod = foxyshop_get_product_by_name($name);
	if (!$prod || !$name) return "";
	$product = foxyshop_setup_product($prod);
	$write = foxyshop_product_link("", true, $variations);
	$product = $original_product;
	return $write;
}


//Function To Get the Product Object From SLUG
function foxyshop_get_product_by_name($post_name, $output = OBJECT) {
    global $wpdb;
    $post = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type='foxyshop_product'", $post_name ));
    if ($post) return get_post($post, $output);
    return null;
}
