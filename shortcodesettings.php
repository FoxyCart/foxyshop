<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();


/*
Examples:

[productcategory name="category-slug"]
Shows all products in a given category with full markup

[showproduct id="product-id"]
Shows all product content and markup

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


//Shortcode-Supplied Values Are Only Signed When They Match the Product's Own Options, Unless a Site Opts In to Signing Them As-Is
function foxyshop_shortcode_can_sign() {
	global $post;
	return (bool)apply_filters('foxyshop_shortcode_allow_unrestricted_signing', false, $post);
}

//Option Values the Product Itself Defines for Each Dropdown/Radio/Checkbox Field, Keyed by Form Field Name
function foxyshop_product_variation_options($product) {
	$options = array();
	if (empty($product['variations']) || !is_array($product['variations'])) return $options;
	$saved_variations = get_option('foxyshop_saved_variations');
	if (!is_array($saved_variations)) $saved_variations = array();
	foreach ($product['variations'] as $product_variation) {
		$variation_name = $product_variation['name'];
		$variation_type = $product_variation['type'];
		$variation_value = isset($product_variation['value']) ? $product_variation['value'] : '';
		foreach ($saved_variations as $saved_var) {
			if (sanitize_title($saved_var['refname']) == $variation_type) {
				$variation_type = $saved_var['type'];
				$variation_value = isset($saved_var['value']) ? $saved_var['value'] : '';
			}
		}
		if (!in_array($variation_type, array('dropdown', 'radio', 'checkbox'))) continue;
		if (strpos($variation_name, "{") !== false) $variation_name = substr($variation_name, strpos($variation_name, "{") + 1, strpos($variation_name, "}") - (strpos($variation_name, "{") + 1));
		foreach (preg_split("[\r\n|\r|\n]", $variation_value) as $val) {
			$val = str_replace("*", "", apply_filters("foxyshop_variation_adjustment", trim($val)));
			if ($val !== '') $options[foxyshop_add_spaces($variation_name)][] = $val;
		}
	}
	return $options;
}

//Only Keep Variations That Are One of the Product's Own Dropdown/Radio/Checkbox Fields Set to Exactly One of Its Options
function foxyshop_shortcode_safe_variations($variations, $product) {
	if ($variations == "") return "";
	$options = foxyshop_product_variation_options($product);
	$safe = array();
	foreach (wp_parse_args(html_entity_decode($variations)) as $key => $val) {
		if (!is_string($key) || !is_string($val)) continue;
		$field = foxyshop_add_spaces($key);
		if (preg_match('/[|{}:]/', $field) || !isset($options[$field]) || !in_array($val, $options[$field], true)) continue;
		$safe[$field] = $val;
	}
	return http_build_query($safe, '', '&');
}


//Show Full Product
add_shortcode('showproduct', 'foxyshop_showproduct_shortcode');
function foxyshop_showproduct_shortcode($atts, $content = null) {
	global $product, $prod;
	$original_product = $product;
	extract(shortcode_atts(array(
		"id" => '',
		"name" => '',
	), $atts));

	$prod = "";
	if ($id) {
		$prod = get_post($id, OBJECT);
	} elseif ($name) {
		$prod = foxyshop_get_product_by_name($name);
	}

	if (!$prod || $prod->post_type != 'foxyshop_product') return "";
	if ($prod->post_status != 'publish' && !current_user_can('read_post', $prod->ID)) return "";
	if (post_password_required($prod)) return get_the_password_form($prod);

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
		"sub_frequency" => '',
		"variations" => '',
	), $atts));


	$can_sign = foxyshop_shortcode_can_sign();
	$prod = foxyshop_get_product_by_name($name);
	if (!$prod || !$name) return;
	if ($prod->post_status != 'publish' && !current_user_can('read_post', $prod->ID)) return;
	if ($content == "") $content = "Add To Cart";
	$product = foxyshop_setup_product($prod);
	if (!$can_sign) $variations = foxyshop_shortcode_safe_variations($variations, $product);
	$url_extra = "";
	if ($sub_frequency && preg_match('/^(\.5m|[1-9][0-9]{0,2}[dwmy])$/', $sub_frequency) && ($can_sign || $sub_frequency === str_replace("-", "", $product['sub_frequency']))) {
		$url_extra .= "&amp;sub_frequency=" . urlencode($sub_frequency) . foxyshop_get_verification("sub_frequency", $sub_frequency);
	}
	$write = '<a href="' . esc_attr(foxyshop_product_link("", true, $variations) . $url_extra) . '" class="foxyshop_sc_product_link">' . $content . '</a>';
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
		"variations" => '',
		"quantity" => '1',
	), $atts));

	$can_sign = foxyshop_shortcode_can_sign();
	$prod = foxyshop_get_product_by_name($name);
	if (!$prod || !$name) return "";
	if ($prod->post_status != 'publish' && !current_user_can('read_post', $prod->ID)) return "";
	$quantity = max(1, (int)$quantity);
	$product = foxyshop_setup_product($prod);
	if (!$can_sign) $variations = foxyshop_shortcode_safe_variations($variations, $product);
	$write = esc_attr(foxyshop_product_link("", true, $variations, $quantity));
	$product = $original_product;
	return $write;
}


//Function To Get the Product Object From SLUG
function foxyshop_get_product_by_name($post_name, $output = OBJECT) {
	global $wpdb;
	$post = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE ( post_name = %s OR post_title = %s ) AND post_type='foxyshop_product'", $post_name, $post_name ));
	if ($post) {
		return get_post($post, $output);
	}
	return null;
}
