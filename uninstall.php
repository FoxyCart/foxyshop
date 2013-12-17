<?php
//Exit if not called in proper context
if(!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) exit();


//Delete Settings
delete_option('foxyshop_settings');
delete_option('foxyshop_category_sort');
delete_option('foxyshop_categories_children');
delete_option('foxyshop_rewrite_rules');
delete_option('foxyshop_setup_required');
delete_option('foxyshop_saved_variations');
delete_option('foxyshop_downloadables');
delete_option('widget_foxyshop-cart-link-widget');
delete_option('widget_foxyshop-category-list-widget');
delete_option('widget_foxyshop-category-widget');


//Put All Products in Trash
$products = get_posts(array('post_type' => 'foxyshop_product', 'numberposts' => -1, 'post_status' => null));
foreach ($products as $product) {
	wp_trash_post($product->ID);
}


//Deletes All Prodcut Categories
global $wp_taxonomies;
register_taxonomy('foxyshop_categories', 'foxyshop_product');
$terms = get_terms("foxyshop_categories");
$count = count($terms);
if ($count > 0) {
	foreach ( $terms as $term ) {
		print_r($term);
		wp_delete_term($term->term_id, 'foxyshop_categories');
	}
}
unset($wp_taxonomies['foxyshop_categories']);


//Deletes All Product Tags
register_taxonomy('foxyshop_tags', 'foxyshop_product');
$terms = get_terms("foxyshop_tags");
$count = count($terms);
if ($count > 0) {
	foreach ( $terms as $term ) {
		wp_delete_term($term->term_id, 'foxyshop_tags');
	}
}
unset($wp_taxonomies['foxyshop_tags']);



//Flush Rewrute Rules
flush_rewrite_rules();