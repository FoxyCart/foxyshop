<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

$args = array(
	'post_type' => array('foxyshop_product'),
	'post_status' => 'publish',
	'numberposts' => -1
);
$products = get_posts($args);
$write = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
$write .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
foreach ($products as $product) {
	$write .= '<url>'."\n";
	$write .= '<loc>' . get_bloginfo('wpurl') . '/' . FOXYSHOP_PRODUCTS_SLUG . '/' . htmlspecialchars($product->post_name) . '/</loc>'."\n";
	$write .= '<lastmod>' . date('Y-m-d\TH:i:s+00:00',strtotime($product->post_modified)) . '</lastmod>'."\n";
	$write .= '<changefreq>weekly</changefreq>'."\n";
	$write .= '<priority>1.0</priority>'."\n";
	$write .= '</url>'."\n";
}

$termchildren = get_terms('foxyshop_categories', 'hide_empty=0&hierarchical=0&orderby=name&order=ASC');
if ($termchildren) {
	$write .= '<url>'."\n";
	$write .= '<loc>' . get_bloginfo('wpurl') . '/' . FOXYSHOP_PRODUCT_CATEGORY_SLUG . '/</loc>'."\n";
	$write .= '<lastmod>' . date('Y-m-d\TH:i:s+00:00',time()) . '</lastmod>'."\n";
	$write .= '<changefreq>weekly</changefreq>'."\n";
	$write .= '<priority>1.0</priority>'."\n";
	$write .= '</url>'."\n";
	foreach ($termchildren as $child) {
		$write .= '<url>'."\n";
		$write .= '<loc>' . get_term_link((int)$child->term_id, "foxyshop_categories") . '</loc>'."\n";
		$write .= '<lastmod>' . date('Y-m-d\TH:i:s+00:00',time()) . '</lastmod>'."\n";
		$write .= '<changefreq>weekly</changefreq>'."\n";
		$write .= '<priority>1.0</priority>'."\n";
		$write .= '</url>'."\n";
	}
} else {
	$write .= '<url>'."\n";
	$write .= '<loc>' . get_bloginfo('wpurl') . '/' . FOXYSHOP_PRODUCTS_SLUG . '/</loc>'."\n";
	$write .= '<lastmod>' . date('Y-m-d\TH:i:s+00:00',time()) . '</lastmod>'."\n";
	$write .= '<changefreq>weekly</changefreq>'."\n";
	$write .= '<priority>1.0</priority>'."\n";
	$write .= '</url>'."\n";
}
$write .= '</urlset>';

header ("Content-Type:text/xml");
echo $write;