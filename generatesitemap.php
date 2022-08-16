<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

$args = array(
	'post_type' => array('foxyshop_product'),
	'post_status' => 'publish',
	'numberposts' => -1
);
$products = get_posts($args);

header ("Content-Type:text/xml");

echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
foreach ($products as $product) {
	echo '<url>'."\n";
	echo '<loc>' . esc_url(get_bloginfo('wpurl') . '/' . FOXYSHOP_PRODUCTS_SLUG . '/' . htmlspecialchars($product->post_name)) . '/</loc>'."\n";
	echo '<lastmod>' . esc_html(date('Y-m-d\TH:i:s+00:00',strtotime($product->post_modified))) . '</lastmod>'."\n";
	echo '<changefreq>weekly</changefreq>'."\n";
	echo '<priority>1.0</priority>'."\n";
	echo '</url>'."\n";
}

$termchildren = get_terms('foxyshop_categories', 'hide_empty=0&hierarchical=0&orderby=name&order=ASC');
if ($termchildren) {
	echo '<url>'."\n";
	echo '<loc>' . esc_url(get_bloginfo('wpurl') . '/' . FOXYSHOP_PRODUCT_CATEGORY_SLUG) . '/</loc>'."\n";
	echo '<lastmod>' . esc_html(date('Y-m-d\TH:i:s+00:00',time())) . '</lastmod>'."\n";
	echo '<changefreq>weekly</changefreq>'."\n";
	echo '<priority>1.0</priority>'."\n";
	echo '</url>'."\n";
	foreach ($termchildren as $child) {
		echo '<url>'."\n";
		echo '<loc>' . esc_url(get_term_link((int)$child->term_id, "foxyshop_categories")) . '</loc>'."\n";
		echo '<lastmod>' . esc_html(date('Y-m-d\TH:i:s+00:00',time())) . '</lastmod>'."\n";
		echo '<changefreq>weekly</changefreq>'."\n";
		echo '<priority>1.0</priority>'."\n";
		echo '</url>'."\n";
	}
} else {
	echo '<url>'."\n";
	echo '<loc>' . esc_url(get_bloginfo('wpurl') . '/' . FOXYSHOP_PRODUCTS_SLUG) . '/</loc>'."\n";
	echo '<lastmod>' . esc_html(date('Y-m-d\TH:i:s+00:00',time())) . '</lastmod>'."\n";
	echo '<changefreq>weekly</changefreq>'."\n";
	echo '<priority>1.0</priority>'."\n";
	echo '</url>'."\n";
}
echo '</urlset>';