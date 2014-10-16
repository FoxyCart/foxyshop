<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//Get Correct Template File
add_action('template_redirect', 'foxyshop_theme_redirect', 1);

function foxyshop_theme_redirect() {
	global $wp, $wp_query, $foxyshop_settings, $foxyshop_body_class_name, $currentPageName;

	$currentName = (isset($wp->query_vars["name"]) ? $wp->query_vars["name"] : "");
	$currentPageName = (isset($wp->query_vars["pagename"]) ? $wp->query_vars["pagename"] : "");
	$currentPostType = (isset($wp->query_vars["post_type"]) ? $wp->query_vars["post_type"] : "");
	$currentCategory = (isset($wp->query_vars["foxyshop_categories"]) ? $wp->query_vars["foxyshop_categories"] : "");
	$currentProduct = (isset($wp->query_vars["foxyshop_product"]) ? $wp->query_vars["foxyshop_product"] : "");

	//Troubleshooting (set this variable to 1)
	$show_to_admin = 0;
	if ($show_to_admin && is_user_logged_in()) {
		echo "<h1>Rewrite Rules</h1><pre>";print_r(get_option('rewrite_rules'));echo "</pre>"; //View Rewrite Rules
		echo "<h1>\$wp Results</h1><pre>";print_r($wp);echo "</pre>";
		echo "<h1>\$wp_query Results</h1><pre>";print_r($wp_query);echo "</pre>";
	}

	//Backup Parsing If Not Month/Day or Month/Name
	$permalink_structure = get_option('permalink_structure');
	if ($permalink_structure != "/%year%/%monthnum%/%postname%/" && $permalink_structure != "/%year%/%monthnum%/%day%/%postname%/") {
		$request_arr = explode("/",$wp->request);
		$request_start = $request_arr[0];
		$request_end = end($request_arr);
		$foxyshop_indicators = array(FOXYSHOP_PRODUCTS_SLUG, FOXYSHOP_PRODUCT_CATEGORY_SLUG, 'product-search', 'foxycart-datafeed-'.$foxyshop_settings['datafeed_url_key'], 'foxycart-sso-'.$foxyshop_settings['datafeed_url_key'], 'upload-'.$foxyshop_settings['datafeed_url_key']);
		if (array_intersect($request_arr, $foxyshop_indicators)) {
			if (in_array(FOXYSHOP_PRODUCTS_SLUG, $request_arr) && $request_end != FOXYSHOP_PRODUCTS_SLUG) {
				$currentProduct = $request_end;
				$currentPostType = "foxyshop_product";
				$wp_query->set("post_type", "foxyshop_product");
				$wp_query->set("foxyshop_product=", $currentProduct);

			} elseif (in_array(FOXYSHOP_PRODUCT_CATEGORY_SLUG, $request_arr) && $request_end != FOXYSHOP_PRODUCT_CATEGORY_SLUG) {
				$currentCategory = $request_end;
				$paged = 1;

				//Check For Paging
				if (is_numeric($request_end) && $request_arr[count($request_arr) - 2] == "page") {
					$currentCategory = $request_arr[count($request_arr) - 3];
					$paged = $request_end;
				}
				$wp_query->set("post_type", "foxyshop_product");
				$wp_query->set("foxyshop_categories=", $currentCategory);
				$wp_query->set("paged=", $paged);

			} elseif ($request_start == "foxycart-checkout-template") {
				$currentPageName = 'foxycart-checkout-template';
			} elseif ($request_start == "foxycart-receipt-template") {
				$currentPageName = 'foxycart-receipt-template';
			} elseif ($request_start == "product-search") {
				$currentPageName = 'product-search';
			} elseif ($request_start == 'foxycart-datafeed-'.$foxyshop_settings['datafeed_url_key']) {
				$currentPageName = 'foxycart-datafeed-'.$foxyshop_settings['datafeed_url_key'];
			} elseif ($request_start == 'foxycart-sso-'.$foxyshop_settings['datafeed_url_key']) {
				$currentPageName = 'foxycart-sso-'.$foxyshop_settings['datafeed_url_key'];
			} elseif ($request_start == 'upload-'.$foxyshop_settings['datafeed_url_key']) {
				$currentPageName = 'upload-'.$foxyshop_settings['datafeed_url_key'];
			}
		}
	}

	//Single Product Page
	if ($currentPostType == "foxyshop_product" && $currentProduct != "" && $currentProduct != 'page') {
		if (have_posts()) {
			$foxyshop_body_class_name = "foxyshop-single-product";
			global $product, $foxyshop_settings;
			if (!defined("FOXYSHOP_DISABLE_SOCIAL_MEDIA_META")) add_action('wp_head', 'foxyshop_social_media_header_meta');
			if ($foxyshop_settings['browser_title_4']) add_filter('wp_title', 'title_filter_single_product', 9, 3);
			add_filter('body_class', 'foxyshop_body_class', 10, 2 );
			if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
			status_header(200);
			add_filter('template_include', 'foxyshop_template_include');
		} else {
			$wp_query->is_404 = true;
			if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
		}

	//All Categories Page
	} elseif ($currentPageName == FOXYSHOP_PRODUCT_CATEGORY_SLUG || $currentName == FOXYSHOP_PRODUCT_CATEGORY_SLUG) {
		$foxyshop_body_class_name = "foxyshop-all-categories";
		$return_template = foxyshop_get_template_file('foxyshop-all-categories.php');
		if ($foxyshop_settings['browser_title_2']) add_filter('wp_title', 'title_filter_all_categories', 9, 3);
		add_filter('body_class', 'foxyshop_body_class', 10, 2);
		$wp_query->is_404 = false;
		if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
		status_header(200);
		add_filter('template_include', 'foxyshop_template_include');

	//Single Category Page
	} elseif ($currentCategory != '') {
		global $post, $foxyshop_title_filter_term;
		$foxyshop_title_filter_term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
		if ($foxyshop_title_filter_term) {

			$foxyshop_body_class_name = "foxyshop-single-category";
			$foxyshop_single_category_name = $currentCategory;
			if ($foxyshop_settings['browser_title_3']) add_filter('wp_title', 'title_filter_single_categories', 9, 3);
			add_filter('body_class', 'foxyshop_body_class', 10, 2 );
			status_header(200);
			if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
			add_filter('template_include', 'foxyshop_template_include');
		} else {
			$wp_query->is_404 = true;
			if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
		}



	//All Products Page
	} elseif ($currentPageName == apply_filters('foxyshop_template_redirect_product_slug', FOXYSHOP_PRODUCTS_SLUG) || $currentName == apply_filters('foxyshop_template_redirect_product_slug', FOXYSHOP_PRODUCTS_SLUG) || $currentPostType == 'foxyshop_product') {
		$foxyshop_body_class_name = "foxyshop-all-products";
		if ($foxyshop_settings['browser_title_1']) add_filter('wp_title', 'title_filter_all_products', 9, 3);
		add_filter('body_class', 'foxyshop_body_class', 10, 2 );
		status_header(200);
		if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
		$wp_query->is_404 = false;
		add_filter('template_include', 'foxyshop_template_include');

	//Search Product Page
	} elseif ($currentPageName == 'product-search' || $currentName == 'product-search') {
		$foxyshop_body_class_name = "foxyshop-search";
		if ($foxyshop_settings['browser_title_5']) add_filter('wp_title', 'title_filter_product_search', 9, 3);
		add_filter('body_class', 'foxyshop_body_class', 10, 2 );
		status_header(200);
		if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
		$wp_query->is_404 = false;
		add_filter('template_include', 'foxyshop_template_include');

	//FoxyCart Datafeed Endpoint
	} elseif ($currentPageName == 'foxycart-datafeed-'.$foxyshop_settings['datafeed_url_key'] || $currentName == 'foxycart-datafeed-'.$foxyshop_settings['datafeed_url_key']) {
		add_filter('body_class', 'foxyshop_body_class', 10, 2 );
		status_header(200);
		if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
		$wp_query->is_404 = false;
		include foxyshop_get_template_file('foxyshop-datafeed-endpoint.php');
		die;

	//FoxyCart SSO Endpoint
	} elseif ($currentPageName == 'foxycart-sso-'.$foxyshop_settings['datafeed_url_key'] || $currentName == 'foxycart-sso-'.$foxyshop_settings['datafeed_url_key']) {
		status_header(200);
		$wp_query->is_404 = false;
		if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
		include FOXYSHOP_PATH . '/ssoendpoint.php';
		die;

	//FoxyCart Checkout Template
	} elseif ($currentPageName == 'foxycart-checkout-template' || $currentName == 'foxycart-checkout-template') {
		if ($foxyshop_settings['browser_title_6']) add_filter('wp_title', 'title_filter_checkout_template', 9, 3);
		add_filter('body_class', 'foxyshop_body_class', 10, 2 );
		status_header(200);
		$wp_query->is_404 = false;
		if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
		if (version_compare($foxyshop_settings['version'], '2.0', ">=")) {
			include foxyshop_get_template_file('foxyshop-checkout-template-2.php');
		} else {
			include foxyshop_get_template_file('foxyshop-checkout-template.php');
		}
		die;

	//FoxyCart Receipt Template
	} elseif ($currentPageName == 'foxycart-receipt-template' || $currentName == 'foxycart-receipt-template') {
		if ($foxyshop_settings['browser_title_7']) add_filter('wp_title', 'title_filter_receipt_template', 9, 3);
		add_filter('body_class', 'foxyshop_body_class', 10, 2 );
		status_header(200);
		$wp_query->is_404 = false;
		if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
		if (version_compare($foxyshop_settings['version'], '2.0', ">=")) {
			include foxyshop_get_template_file('foxyshop-receipt-template-2.php');
		} else {
			include foxyshop_get_template_file('foxyshop-receipt-template.php');
		}
		die;

	//File Upload
	} elseif ($currentPageName == 'upload-'.$foxyshop_settings['datafeed_url_key'] || $currentName == 'upload-'.$foxyshop_settings['datafeed_url_key']) {
		status_header(200);
		$wp_query->is_404 = false;
		if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
		include FOXYSHOP_PATH . '/uploadprocessor.php';
		die;

	//Dynamic Product Sitemap
	} elseif (($currentPageName == FOXYSHOP_PRODUCT_SITEMAP_SLUG || $currentName == FOXYSHOP_PRODUCT_SITEMAP_SLUG) && $foxyshop_settings['generate_product_sitemap']) {
		status_header(200);
		$wp_query->is_404 = false;
		if (!defined("IS_FOXYSHOP")) define("IS_FOXYSHOP", 1);
		include FOXYSHOP_PATH . '/generatesitemap.php';
		die;
	}
}

function title_filter_all_products() {
	global $foxyshop_settings;
	return $foxyshop_settings['browser_title_1'];
}
function title_filter_single_categories() {
	global $wp, $foxyshop_title_filter_term, $foxyshop_settings, $foxyshop_single_category_name;
	$term = get_term_by('slug', $foxyshop_single_category_name, "foxyshop_categories");
	return str_replace("%c", $foxyshop_title_filter_term->name, $foxyshop_settings['browser_title_3']);
}
function title_filter_all_categories() {
	global $foxyshop_settings;
	return $foxyshop_settings['browser_title_2'];
}
function title_filter_single_product() {
	global $foxyshop_settings, $post;
	return str_replace("%p", $post->post_title, $foxyshop_settings['browser_title_4']);
}
function title_filter_product_search() {
	global $foxyshop_settings;
	return $foxyshop_settings['browser_title_5'];
}
function title_filter_checkout_template() {
	global $foxyshop_settings;
	return $foxyshop_settings['browser_title_6'];
}
function title_filter_receipt_template() {
	global $foxyshop_settings;
	return $foxyshop_settings['browser_title_7'];
}

function foxyshop_body_class($wp_classes, $extra_classes = "") {
	global $foxyshop_body_class_name;
	if (!is_array($extra_classes)) $extra_classes = array();
	$wp_classes[] = "foxyshop";
	if ($foxyshop_body_class_name) $wp_classes[] = $foxyshop_body_class_name;
	return array_merge($wp_classes, (array)$extra_classes);
}

function foxyshop_template_include() {
	global $foxyshop_body_class_name;
	return foxyshop_get_template_file($foxyshop_body_class_name . ".php");
}
?>
