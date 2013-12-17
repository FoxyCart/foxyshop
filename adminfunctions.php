<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//Insert jQuery
function foxyshop_insert_jquery() {

	$jquery_url = "http" . ($_SERVER['SERVER_PORT'] == 443 ? 's' : '') . "://ajax.googleapis.com/ajax/libs/jquery/" . foxyshop_get_jquery_version() . "/jquery.min.js";
	wp_deregister_script('jquery');
	wp_register_script('jquery', apply_filters('foxyshop_jquery_url', $jquery_url), array(), NULL, false);
	wp_enqueue_script('jquery');
}

function foxyshop_get_jquery_version() {
	global $foxyshop_settings;

	$jquery_version = FOXYSHOP_JQUERY_VERSION;
	if (version_compare($foxyshop_settings['version'], '1.0', "<=") && version_compare($jquery_version, '1.8.3', ">")) {
		$jquery_version = "1.8.3";
	}
	return $jquery_version;
}

//Remove jQuery
function foxyshop_remove_jquery() {
	wp_dequeue_script('jquery');
}

//Loading in Admin Scripts
function foxyshop_load_admin_scripts($hook) {
	global $foxyshop_settings;

	$page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : '');

	//Style - Always Do This
	wp_enqueue_style('foxyshop_admin_css', FOXYSHOP_DIR . '/css/foxyshop-admin.css');

	//Date Picker
	if ($page == "foxyshop_order_management" || $page == "foxyshop_subscription_management") foxyshop_date_picker();

	//Custom Sorter
	if ($page == "foxyshop_custom_sort" || $page == "foxyshop_category_sort" ||  $page == "foxyshop_tools") {
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
	}

	//Product
	if ($hook != 'post.php' && $hook != 'post-new.php' && $page != 'cfbe_editor-foxyshop_product' && $page != 'foxyshop_setup') return;
	wp_enqueue_script('swfobject');
	if ($foxyshop_settings['related_products_custom'] || $foxyshop_settings['related_products_tags'] || $foxyshop_settings['enable_addon_products']) {
		wp_enqueue_script('chosenScript', FOXYSHOP_DIR . '/js/chosen.jquery.min.js', array('jquery'));
		wp_enqueue_style('chosenStyle', FOXYSHOP_DIR . '/css/chosen.css');
	}
	foxyshop_date_picker();
}

//Loading in Public Style
function foxyshop_load_site_scripts() {
	wp_enqueue_style('foxyshop_css', FOXYSHOP_DIR . '/css/foxyshop.css', array(), FOXYSHOP_VERSION);
}

//Checking For Includes To Be Removed
function foxyshop_check_include_status() {
	global $foxyshop_settings;
	$skip = 0;
	if (defined('FOXYSHOP_SKIP_FOXYCART_INCLUDES')) $foxyshop_settings['include_exception_list'] = "*";
	if ($foxyshop_settings['include_exception_list']) {
		if ($foxyshop_settings['include_exception_list'] == "*") {
			$skip = 1;
		} else {
			$include_exception_list = explode(",", str_replace(" ", "", $foxyshop_settings['include_exception_list']));
			if (is_page($include_exception_list) || is_single($include_exception_list)) $skip = 1;
		}
	}
	if ($skip) {
		remove_action('wp_head', 'foxyshop_insert_foxycart_files');
		remove_action('init', 'foxyshop_insert_jquery');
		if ($foxyshop_settings['include_exception_list'] != "*") {
			add_action('wp_enqueue_scripts', 'foxyshop_remove_jquery', 99);
			remove_action('wp_footer', 'foxyshop_insert_google_analytics', 100);
		}
	}
}


function foxyshop_date_picker() {
	wp_enqueue_style('datepickerStyle', FOXYSHOP_DIR . '/css/ui-smoothness/jquery-ui.custom.css');
	wp_enqueue_script('datepickerScript', FOXYSHOP_DIR . '/js/jquery-ui.datepicker.min.js', array('jquery','jquery-ui-core'));
}

//Check Permalinks on all admin pages and warn if incorrect
add_action('admin_notices', 'foxyshop_check_permalinks');
function foxyshop_check_permalinks() {
	$permalink_structure = (isset($_POST['permalink_structure']) ? $_POST['permalink_structure'] : get_option('permalink_structure'));
	if ($permalink_structure == '' && current_user_can('manage_options')) {
		echo '<div class="error"><p><strong>Warning:</strong> Your <a href="options-permalink.php">permalink structure</a> is set to default. Your product links will not work correctly until you have turned on Permalink support. It is recommend that you set to "Month and Name".</p></div>';
	}
}



//Insert Google Analytics
function foxyshop_insert_google_analytics() {
	global $foxyshop_settings;

	//Advanced
	if ($foxyshop_settings['ga_advanced']) {
		?><script type="text/javascript" charset="utf-8">
	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', '<?php echo htmlspecialchars($foxyshop_settings['ga']); ?>']);
	_gaq.push(['_setDomainName', '<?php echo $_SERVER['SERVER_NAME']; ?>']);
	_gaq.push(['_setAllowHash', 'false']);
	<?php if (strpos($foxyshop_settings['domain'], '.foxycart.com') !== false) echo "_gaq.push(['_setAllowLinker', true]);\n"; ?>
	_gaq.push(['_trackPageview']);
	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();
	fcc.events.cart.preprocess.add(function(e, arr) {
		if (arr['cart'] == 'checkout' || arr['cart'] == 'updateinfo' || arr['output'] == 'json') {
			return true;
		}
		if (arr['cart'] == 'checkout_paypal_express') {
			_gaq.push(['_trackPageview', '/paypal_checkout']);
			return true;
		}
		_gaq.push(['_trackPageview', '/cart']);
		return true;
	});
	fcc.events.cart.process.add_pre(function(e, arr) {
		var pageTracker = _gat._getTrackerByName();
		jQuery.getJSON('https://' + storedomain + '/cart?' + fcc.session_get() + '&h:ga=' + escape(pageTracker._getLinkerUrl('', true)) + '&output=json&callback=?', function(data){});
		return true;
	});
</script><?php

	} else {
		if (!is_user_logged_in()) {
		?><script type="text/javascript">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', '<?php echo htmlspecialchars($foxyshop_settings['ga']); ?>']);
_gaq.push(['_trackPageview']);
(function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
</script><?php
		} else {
			echo "\n<!-- Google Analytics Not Loaded Because This is a Logged-In User -->\n";
		}
	}
}

//Google Analytics For Checkout
function foxyshop_insert_google_analytics_checkout() {
	global $foxyshop_settings;
	if (!$foxyshop_settings['ga_advanced']) return;
	?>
	<script type="text/javascript" charset="utf-8">
		if (window.location.hash.search(/utma/) == -1 && typeof(fc_json.custom_fields['ga']) != "undefined") {
			if (fc_json.custom_fields['ga'].length > 0) {
				window.location.hash = fc_json.custom_fields['ga'].replace( /\&amp;/g, '&' );
			}
		}
	</script>

	<script type="text/javascript">

	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', '<?php echo htmlspecialchars($foxyshop_settings['ga']); ?>']);
	  _gaq.push(['_setDomainName', 'none']);
	  _gaq.push(['_setAllowLinker', true]);
	  _gaq.push(['_setAllowAnchor', true]);
	  _gaq.push(['_trackPageview', '/checkout']);

	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	</script>

	<script type="text/javascript" charset="utf-8">
		function ga_tracker() {
			if (typeof(fc_json.custom_fields['ga']) != "undefined" && jQuery('#fc_payment_method_paypal').attr("checked") == true) {
				_gaq.push(['_trackPageview', '/paypal_payment']);
				// setTimeout('return true;', 250); // TODO
			}
		}
		FC.checkout.overload('validateAndSubmit', 'ga_tracker', null);
	</script>
	<?php
}


//Google Analytics For Receipt
function foxyshop_insert_google_analytics_receipt() {
	global $foxyshop_settings;
	if (!$foxyshop_settings['ga_advanced']) return;
	?>
	<script type="text/javascript">

	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', '<?php echo htmlspecialchars($foxyshop_settings['ga']); ?>']);
	  _gaq.push(['_setDomainName', 'none']);
	  _gaq.push(['_setAllowLinker', true]);
	  _gaq.push(['_setAllowAnchor', true]);
	  _gaq.push(['_trackPageview', '/receipt']);

	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	</script>

	^^receipt_only_begin^^
	^^analytics_google_ga_async^^
	^^receipt_only_end^^
	<?php
}

//Product Category Comparison
function foxyshop_comparison($a, $b) {
	if ($a->sort_key == $b->sort_key) { return 0; }
	return ($a->sort_key < $b->sort_key) ? -1 : 1;
}

//Sort Categories
function foxyshop_sort_categories($obj, $categoryID) {
	global $foxyshop_category_sort;
	if (array_key_exists($categoryID,$foxyshop_category_sort)) {
		$sort_array = $foxyshop_category_sort[$categoryID];
		foreach($obj as $cat) {
			$cat->sort_key = 999;
			for ($i=0;$i<count($sort_array);$i++) {
				if ($sort_array[$i] == $cat->term_id) $cat->sort_key = $i;
			}
		}
		usort($obj,'foxyshop_comparison');
	}
	return $obj;
}

//Generate Products Per Page
function foxyshop_products_per_page() {
	global $foxyshop_settings;
	return $foxyshop_settings['products_per_page'];
}

//Hide Children Array
function foxyshop_hide_children_array($currentCategoryID) {
	global $foxyshop_settings;
	if ($foxyshop_settings['hide_subcat_children']) {
		$temp = get_objects_in_term($currentCategoryID, "foxyshop_categories");
		return array("post__not_in" => get_objects_in_term(get_term_children($currentCategoryID, "foxyshop_categories"), "foxyshop_categories"));
	} else {
		return array();
	}
}

//Place Plugin Activation Links
function foxyshop_plugin_action_links($links, $file) {
	static $this_plugin;
	if (!$this_plugin) $this_plugin = "foxyshop/foxyshop.php";
	if ($file == $this_plugin) {
		$settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/edit.php?post_type=foxyshop_product&page=foxyshop_settings_page">Settings</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}

function foxyshop_dblquotes($str) {
	return str_replace('"','""',$str);
}


//Plugin Activation Function
function foxyshop_activation() {
	global $wpdb, $google_product_field_names;

	//Get Locale
	$current_locale = get_locale();
	if (!$current_locale) $current_locale = "en_US";

	//Defaults For Settings
	$default_foxyshop_settings = array(
		"domain" => "",
		"version" => "1.1",
		"foxyshop_version" => FOXYSHOP_VERSION,
		"ship_categories" => "",
		"enable_ship_to" => "",
		"use_cart_validation" => "1",
		"enable_subscriptions" => "",
		"expiring_cards_reminder" => "",
		"enable_bundled_products" => "",
		"enable_addon_products" => "",
		"enable_dashboard_stats" => "",
		"related_products_custom" => "on",
		"related_products_tags" => "",
		"browser_title_1" => FOXYSHOP_PRODUCT_NAME_SINGULAR . " | " . get_bloginfo("name"),
		"browser_title_2" => FOXYSHOP_PRODUCT_NAME_SINGULAR . " Categories | " . get_bloginfo("name"),
		"browser_title_3" => "%c | " . get_bloginfo("name"),
		"browser_title_4" => "%p | " . get_bloginfo("name"),
		"browser_title_5" => FOXYSHOP_PRODUCT_NAME_SINGULAR . " Search | " . get_bloginfo("name"),
		"browser_title_6" => get_bloginfo("name") . " Checkout",
		"browser_title_7" => get_bloginfo("name") . " Receipt",
		"weight_type" => "english",
		"default_weight" => "1 0.0",
		"use_jquery" => "on",
		"hide_subcat_children" => "on",
		"generate_product_sitemap" => "on",
		"sort_key" => "menu_order",
		"enable_sso" => "",
		"orderdesk_url" => "",
		"sso_account_required" => "0",
		"ga" => "",
		"ga_advanced" => "",
		"locale_code" => $current_locale,
		"manage_inventory_levels" => "",
		"inventory_alert_level" => 3,
		"inventory_alert_email" => "",
		"checkout_customer_create" => "",
		"datafeed_url_key" => substr(MD5(rand(1000, 99999)."{urlkey}" . date("H:i:s")),1,12),
		"default_image" => "",
		"foxycart_include_cache" => "",
		"template_url_cart" => "",
		"template_url_checkout" => get_bloginfo("url") . "/foxycart-checkout-template/",
		"template_url_receipt" => get_bloginfo("url") . "/foxycart-receipt-template/",
		"products_per_page" => -1,
		"downloadables_sync" => "",
		"google_product_support" => "",
		"google_product_merchant_id" => "",
		"google_product_auth" => "",
		"include_exception_list" => "",
		"show_add_to_cart_link" => "",
		"api_key" => "spfx" . hash_hmac('sha256', rand(2165,64898), "dkw81" . time()),
	);

	//Set For the First Time
	if (!get_option("foxyshop_settings")) {
		update_option("foxyshop_settings", $default_foxyshop_settings);
		add_option("foxyshop_setup_required", 1);
		return $default_foxyshop_settings;

	//Upgrade Tasks
	} else {

		$foxyshop_settings = maybe_unserialize(get_option("foxyshop_settings")); //Double Serialization Repair 3.6

		//Double Serialization Repair 3.6
		$foxyshop_category_sort = get_option("foxyshop_category_sort");
		if (is_serialized($foxyshop_category_sort)) {
			update_option('foxyshop_category_sort', unserialize($foxyshop_category_sort));
		}
		$foxyshop_saved_variations = get_option("foxyshop_saved_variations");
		if (is_serialized($foxyshop_saved_variations)) {
			update_option('foxyshop_saved_variations', unserialize($foxyshop_saved_variations));
		}

		//Run Some Upgrades
		if (!array_key_exists('version',$foxyshop_settings)) $foxyshop_settings['version'] = "0";
		if ($foxyshop_settings['version'] == "0.70") $foxyshop_settings['version'] = "0.7.0";
		if (!array_key_exists('locale_code',$foxyshop_settings)) $foxyshop_settings['locale_code'] = $current_locale;
		if (!array_key_exists('inventory_alert_email',$foxyshop_settings)) $foxyshop_settings['inventory_alert_email'] = "on";
		if (array_key_exists('inventory_url_key',$foxyshop_settings)) { $foxyshop_settings['datafeed_url_key'] = $foxyshop_settings['inventory_url_key']; unset($foxyshop_settings['inventory_url_key']); }
		if ($foxyshop_settings['sso_account_required'] == "") $foxyshop_settings['sso_account_required'] = 0;
		if ($foxyshop_settings['sso_account_required'] == "on") $foxyshop_settings['sso_account_required'] = 1;
		if (!array_key_exists('enable_dashboard_stats',$foxyshop_settings)) $foxyshop_settings['enable_dashboard_stats'] = ""; //3.0
		if (!array_key_exists('checkout_customer_create',$foxyshop_settings)) $foxyshop_settings['checkout_customer_create'] = ""; //3.2?
		if ($foxyshop_settings['default_image'] == WP_PLUGIN_URL."/foxyshop/images/no-photo.png") $foxyshop_settings['default_image'] = ""; //3.3
		if (!$foxyshop_settings['domain']) add_option("foxyshop_setup_required", 1); //3.3
		if (!array_key_exists('foxycart_include_cache',$foxyshop_settings)) $foxyshop_settings['foxycart_include_cache'] = ""; //3.3
		if (!array_key_exists('related_products_custom',$foxyshop_settings)) $foxyshop_settings['related_products_custom'] = "on"; //3.3
		if (!array_key_exists('related_products_tags',$foxyshop_settings)) $foxyshop_settings['related_products_tags'] = ""; //3.3
		if (!array_key_exists('enable_addon_products',$foxyshop_settings)) $foxyshop_settings['enable_addon_products'] = ""; //3.4
		if (!array_key_exists('template_url_cart',$foxyshop_settings)) $foxyshop_settings['template_url_cart'] = ""; //3.5.1
		if (!array_key_exists('template_url_checkout',$foxyshop_settings)) $foxyshop_settings['template_url_checkout'] = ""; //3.5.1
		if (!array_key_exists('template_url_receipt',$foxyshop_settings)) $foxyshop_settings['template_url_receipt'] = ""; //3.6.1
		if (!array_key_exists('ups_worldship_export',$foxyshop_settings)) $foxyshop_settings['ups_worldship_export'] = ""; //3.7
		if (!array_key_exists('downloadables_sync',$foxyshop_settings)) $foxyshop_settings['downloadables_sync'] = ""; //3.7
		if (!array_key_exists('google_product_support',$foxyshop_settings)) $foxyshop_settings['google_product_support'] = ""; //3.7
		if (!array_key_exists('google_product_merchant_id',$foxyshop_settings)) $foxyshop_settings['google_product_merchant_id'] = ""; //3.7
		if (!array_key_exists('google_product_auth',$foxyshop_settings)) $foxyshop_settings['google_product_auth'] = ""; //3.7
		if (!array_key_exists('include_exception_list',$foxyshop_settings)) $foxyshop_settings['include_exception_list'] = ""; //3.9
		if (array_key_exists('ups_worldship_export',$foxyshop_settings)) unset($foxyshop_settings['ups_worldship_export']); //4.1
		if (!array_key_exists('show_add_to_cart_link',$foxyshop_settings)) $foxyshop_settings['show_add_to_cart_link'] = ""; //4.1.1
		if (!array_key_exists('orderdesk_url',$foxyshop_settings)) $foxyshop_settings['orderdesk_url'] = ""; //4.1.4
		if (!array_key_exists('expiring_cards_reminder',$foxyshop_settings)) $foxyshop_settings['expiring_cards_reminder'] = $foxyshop_settings['enable_subscriptions']; //4.1.5
		if (!array_key_exists('use_cart_validation',$foxyshop_settings)) $foxyshop_settings['use_cart_validation'] = (defined('FOXYSHOP_SKIP_VERIFICATION') ? 0 : 1); //4.2
		if (!array_key_exists('browser_title_6', $foxyshop_settings)) $foxyshop_settings['browser_title_6'] = get_bloginfo("name") . " Checkout"; //4.4
		if (!array_key_exists('browser_title_7', $foxyshop_settings)) $foxyshop_settings['browser_title_7'] = get_bloginfo("name") . " Receipt"; //4.4



		//Upgrade Variations in 3.0
		if (version_compare($foxyshop_settings['foxyshop_version'], '3.0', "<")) {
			$temp_max_variations = (array_key_exists('max_variations',$foxyshop_settings) ? $foxyshop_settings['max_variations'] : 10);
			$products = get_posts(array('post_type' => 'foxyshop_product', 'numberposts' => -1, 'post_status' => null));
			foreach ($products as $product) {
				$variations = array();
				for ($i=1; $i<=$temp_max_variations; $i++) {
					$_variationName = get_post_meta($product->ID, '_variation_name_'.$i, 1);
					$_variationType = get_post_meta($product->ID, '_variation_type_'.$i, 1);
					$_variationValue = get_post_meta($product->ID, '_variation_value_'.$i, 1);
					$_variationDisplayKey = get_post_meta($product->ID, '_variation_dkey_'.$i, 1);
					$_variationRequired = get_post_meta($product->ID, '_variation_required_'.$i, 1);
					if ($_variationName) {
						$variations[$i] = array(
							"name" => str_replace(array('"', '&', '.'), array('', 'and', ''), $_variationName),
							"type" => $_variationType,
							"value" => $_variationValue,
							"displayKey" => $_variationDisplayKey,
							"required" => $_variationRequired
						);
					}
				}
				if (count($variations) > 0) {
					if (update_post_meta($product->ID, '_variations', $variations)) {
						for ($i=1; $i<=$temp_max_variations; $i++) {
							delete_post_meta($product->ID,'_variation_name_'.$i);
							delete_post_meta($product->ID,'_variation_type_'.$i);
							delete_post_meta($product->ID,'_variation_value_'.$i);
							delete_post_meta($product->ID,'_variation_dkey_'.$i);
							delete_post_meta($product->ID,'_variation_required_'.$i);
						}
					}
				}
			}
			if (array_key_exists('max_variations', $foxyshop_settings)) unset($foxyshop_settings['max_variations']);
		}

		//Remove Double Serialization in 3.6
		if (version_compare($foxyshop_settings['foxyshop_version'], '3.6', "<")) {

			//Product Variations and Inventory Levels
			$products = get_posts(array('post_type' => 'foxyshop_product', 'numberposts' => -1, 'post_status' => null));
			foreach ($products as $product) {
				$variations = get_post_meta($product->ID,'_variations',1);
				$inventory_levels = get_post_meta($product->ID,'_inventory_levels',1);
				if (is_serialized($variations)) update_post_meta($product->ID, '_variations', unserialize($variations));
				if (is_serialized($inventory_levels)) update_post_meta($product->ID, '_inventory_levels', unserialize($inventory_levels));
			}

			//User Subscriptions
			$user_list = $wpdb->get_results("SELECT user_id, meta_value from $wpdb->usermeta WHERE meta_key = 'foxyshop_subscription' AND meta_value != ''");
			foreach ((array)$user_list as $user) {
				$meta_value = $user->meta_value;
				$meta_value = maybe_unserialize($meta_value);
				if (is_serialized($meta_value)) update_user_meta($user->user_id, 'foxyshop_subscription', unserialize($meta_value));
			}
		}

		//Upgrade Google Product Fields in 3.7
		if (version_compare($foxyshop_settings['foxyshop_version'], '3.7', "<")) {
			$products = get_posts(array('post_type' => 'foxyshop_product', 'numberposts' => -1, 'post_status' => null));
			foreach ($products as $product) {
				foreach($google_product_field_names as $field) {
					$google_product_field_value = get_post_meta($product->ID, $field, 1);
					if ($google_product_field_value) {
						add_post_meta($product->ID, "_" . $field, $google_product_field_value);
						delete_post_meta($product->ID, $field);
					}
				}
			}
		}

		//Load in New Defaults and Version Number
		$foxyshop_settings = wp_parse_args($foxyshop_settings,$default_foxyshop_settings);
		$foxyshop_settings['foxyshop_version'] = FOXYSHOP_VERSION;
		if (!$foxyshop_settings['datafeed_url_key']) $foxyshop_settings['datafeed_url_key'] = substr(MD5(rand(1000, 99999)."{urlkey}" . date("H:i:s")),1,12);

		//Save Settings
		update_option("foxyshop_settings", $foxyshop_settings);
		return $foxyshop_settings;
	}
}

//Plugin Deactivation Function
function foxyshop_deactivation() {
	global $wp_post_types;
	if (isset($wp_post_types['foxyshop_product'])) unset($wp_post_types['foxyshop_product']);
	delete_option('foxyshop_rewrite_rules');
	flush_rewrite_rules();
}

//Flushes Rewrite Rules if Structure Has Changed
function foxyshop_check_rewrite_rules() {
	if (get_option('foxyshop_rewrite_rules') != FOXYSHOP_PRODUCTS_SLUG."|".FOXYSHOP_PRODUCT_CATEGORY_SLUG || isset($_GET["foxyshop_flush_rewrite_rules"])) {
		flush_rewrite_rules(false);
		update_option('foxyshop_rewrite_rules', FOXYSHOP_PRODUCTS_SLUG."|".FOXYSHOP_PRODUCT_CATEGORY_SLUG);
	}
}


//Inventory Update Helper
function foxyshop_inventory_count_update($code, $new_count, $product_id = 0, $force = true) {
	global $wpdb;

	$search_code = mysql_real_escape_string($code);

	//Setup Search Query
	$sql = "SELECT $wpdb->postmeta.`post_id`, $wpdb->postmeta.`meta_value`,  $wpdb->postmeta.`meta_key` ";
	$sql .= "FROM  $wpdb->posts INNER JOIN $wpdb->postmeta ON $wpdb->posts.`ID` =  $wpdb->postmeta.`post_id` ";
	$sql .= "WHERE $wpdb->posts.`post_status` = 'publish' AND (";

	//Search Inventory Values
	$sql .= "($wpdb->postmeta.`meta_key` = '_inventory_levels' AND ";
	$sql .= "(";
	$sql .= "$wpdb->postmeta.`meta_value` LIKE '%\"" . $search_code . "\";%' OR ";
	$sql .= "$wpdb->postmeta.`meta_value` LIKE '%:" . $search_code . ";%'";
	$sql .= ")";
	$sql .= ") ";

	//Only Search These Extra Fields If $force = 1
	if ($force) {

		//Search Variation Values
		$sql .= "OR ($wpdb->postmeta.`meta_key` = '_variations' AND $wpdb->postmeta.`meta_value` LIKE '%c:" . $search_code . "%')";

		//Search Code Values
		$sql .= "OR ($wpdb->postmeta.`meta_key` = '_code' AND $wpdb->postmeta.`meta_value` = '" . $search_code . "')";
	}
	$sql .= ")";

	//Search
	$result = $wpdb->get_results($sql);

	foreach ($result as $row) {
		$product_id = $row->post_id;
		$meta_key = $row->meta_key;
		$meta_value = $row->meta_value;

		//No Inventory Already, Create Inventory Record
		if (($meta_key == "_code" || $meta_key == "_variations") && $force) {
			$inventory = get_post_meta($product_id, "_inventory_levels", 1);
			if (!is_array($inventory)) $inventory = array();
			$inventory[$code]['count'] = $new_count;
			$inventory[$code]['alert'] = "";
			$original_count = "";
			do_action("foxyshop_inventory_update", $code, $original_count, $new_count);
			update_post_meta($product_id, '_inventory_levels', $inventory);


		//Inventory Already Exists
		} elseif ($meta_key == "_inventory_levels") {
			$inventory = maybe_unserialize($meta_value);
			$original_count = isset($inventory[$code]['count']) ? $inventory[$code]['count'] : "";
			$inventory[$code]['count'] = $new_count;
			do_action("foxyshop_inventory_update", $code, $original_count, $new_count);
			update_post_meta($product_id, '_inventory_levels', $inventory);
		}
	}
}


//Get Category List from FoxyCart API
function foxyshop_get_category_list($output_type = "") {
	global $foxyshop_settings;
	if (version_compare($foxyshop_settings['version'], '0.7.2', "<") || !$foxyshop_settings['domain']) return "";
	$foxy_data = array("api_action" => "category_list");
	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
	if ($xml->result == "ERROR") return "";
	$output = "";
	$save = "";
	foreach($xml->categories->category as $category) {
		$code = (string)$category->code;
		$description = (string)$category->description;
		$product_delivery_type = (string)$category->product_delivery_type;
		$save .= "$code|$description|$product_delivery_type\n";

		if ($output_type == "") {
			$output .= "$code|$description|$product_delivery_type\n";
		} elseif ($output_type == "select") {
			$output .= '<option value="' . htmlspecialchars($code) . '">' . htmlspecialchars($description) . '</option>' . "\n";
		}
	}

	//Save
	$foxyshop_settings['ship_categories'] = trim($save);
	update_option("foxyshop_settings", $foxyshop_settings);

	return trim($output);
}

//Get Downloadable List from FoxyCart API
function foxyshop_get_downloadable_list() {
	global $foxyshop_settings;
	if (version_compare($foxyshop_settings['version'], '0.7.2', "<") || !$foxyshop_settings['domain']) return "";
	$foxy_data = array("api_action" => "downloadable_list");
	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
	if ($xml->result == "ERROR") return "";
	$output = array();
	foreach($xml->downloadables->downloadable as $downloadable) {
		$output[] = array(
			'category_code' => (string)$downloadable->category_code,
			'product_name' => (string)$downloadable->product_name,
			'product_code' => (string)$downloadable->product_code,
			'product_price' => (string)$downloadable->product_price
		);
	}
	update_option("foxyshop_downloadables", $output);
	return $output;
}

//Access the FoxyCart API
function foxyshop_get_foxycart_data($foxyData, $silent_fail = true) {
	global $foxyshop_settings;
	$foxyData = array_merge(array("api_token" => $foxyshop_settings['api_key']), $foxyData);
	$args = array(
		"redirection" => !defined('FOXYSHOP_CURL_CONNECTTIMEOUT') ? 10 : FOXYSHOP_CURL_CONNECTTIMEOUT,
		"timeout" => !defined('FOXYSHOP_CURL_TIMEOUT') ? 15 : FOXYSHOP_CURL_TIMEOUT,
		"method" => "POST",
		"sslverify" => defined('FOXYSHOP_CURL_SSL_VERIFYPEER') ? FOXYSHOP_CURL_SSL_VERIFYPEER : 1,
		"body" => $foxyData,
	);
	$response = wp_remote_post("https://" . $foxyshop_settings['domain'] . "/api", $args);

	//WP Error
	if (is_wp_error($response)) {
		if ($silent_fail) {
			return "<?xml version='1.0' encoding='UTF-8'?><foxydata><result>ERROR</result><messages><message>" . __('Connection Error', 'foxyshop') . ": " . nl2br($response->get_error_message()) . "</message></messages></foxydata>";
		} else {
			die("Connection Error: " . nl2br($response->get_error_message()));
		}
	}

	$response_body = $response['body'];
	if (!$response_body || $response['response']['code'] != 200) {
		if ($silent_fail) {
			return "<?xml version='1.0' encoding='UTF-8'?><foxydata><result>ERROR</result><messages><message>" . __('Connection Error', 'foxyshop') . ": " . $message . "</message></messages></foxydata>";
		} else {
			die("Connection Error: " . $message);
		}
	}
	return $response_body;
}



//Paging for Orders, Customers, Subscriptions
function foxyshop_api_paging_nav($type, $position, $xml, $querystring) {
	global $foxyshop_settings, $wp_version;

	//Pagination
	$p = (int)(version_compare($foxyshop_settings['version'], '0.7.1', "<") ? 50 : FOXYSHOP_API_ENTRIES_PER_PAGE);
	$start_offset = (int)(version_compare($foxyshop_settings['version'], '0.7.1', "<=") ? -1 : 0);
	$filtered_total = (int)$xml->statistics->filtered_total;
	$pagination_start = (int)$xml->statistics->pagination_start;
	$pagination_end = (int)$xml->statistics->pagination_end;
	$current_page = $pagination_start >= 1 ? ceil($pagination_start / $p) : 1;
	$total_pages = $filtered_total > 0 ? ceil($filtered_total / $p) : 0;

	echo '<div class="tablenav ' . $position . '">';

	//All Transaction
	if ($type == "transactions") {
		echo '<div class="alignleft actions">'."\n";
		echo '<select name="action-' . $position . '">';
		echo '<option selected="selected" value="-1">Bulk Actions</option>';
		echo '<option value="archive">Archive</option>';
		echo '<option value="unarchive">Unarchive</option>';
		echo '</select>'."\n";
		echo '<input type="submit" value="Apply" class="button-secondary action" id="doaction" name="">'."\n";
		echo '</div>'."\n";
	}

	echo '<input type="hidden" name="paged-' . $position . '-original" value="' . $current_page . '" />'."\n";
	echo '<div class="tablenav-pages"><span class="displaying-num">' . $filtered_total . ' item' . ($filtered_total == 1 ? '' : 's') . '</span>'."\n";

	if ($pagination_start > 1 || $filtered_total > $pagination_end) {
		//First
		echo '<span class="pagination-links"><a href="edit.php' . $querystring . '&amp;pagination_start=' . (1 + $start_offset) . '" title="Go to the first page" class="first-page' . ($current_page == 1 ? ' disabled' : '') . '">&laquo;</a>'."\n";

		//Previous
		echo '<a href="edit.php' . $querystring . '&amp;pagination_start=' . ($pagination_start - $p + $start_offset) . '" title="Go to the previous page" class="prev-page' . ($current_page == 1 ? ' disabled' : '') . '">&lsaquo;</a>'."\n";

		//Enter Page
		echo '<span class="paging-input"><input type="text" size="1" class="foxyshop_paged_number" value="' . $current_page . '" name="paged-' . $position . '" title="Current page" class="current-page"> of <span class="total-pages">' . $total_pages . '</span></span>'."\n";

		//Next
		echo '<a href="edit.php' . $querystring . '&amp;pagination_start=' . ($pagination_end + 1 + $start_offset) . '" title="Go to the next page" class="next-page' . ($filtered_total <= $pagination_end ? ' disabled' : '') . '">&rsaquo;</a>'."\n";

		//Last
		echo '<a href="edit.php' . $querystring . '&amp;pagination_start=' . ((($total_pages - 1) * $p) + 1 + $start_offset) . '" title="Go to the last page" class="last-page' . ($filtered_total <= $pagination_end ? ' disabled' : '') . '">&raquo;</a></span>'."\n";
	}

	echo '</div>'."\n";

	echo '</div>'."\n";
}


//Save Meta Data
function foxyshop_save_meta_data($fieldname,$input) {
	global $post_id;
	$current_data = get_post_meta($post_id, $fieldname, TRUE);
	$new_data = $input;
	if (!$new_data) $new_data = NULL;
	if ($current_data != "" && is_null($new_data)) delete_post_meta($post_id,$fieldname);
	if (!is_null($new_data)) update_post_meta($post_id,$fieldname,$new_data);
}


//Set FoxyCart Attributes
function foxyshop_save_attribute($att_type, $id, $att_name, $att_value, $append = 0) {
	$foxy_data = array(
		"api_action" => "attribute_save",
		"name" => $att_name,
		"value" => $att_value,
		"type" => $att_type,
		"identifier" => $id,
		"append" => $append
	);
	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
	return (string)$xml->result . ": " . (string)$xml->messages->message;
}


//Delete FoxyCart Attributes
function foxyshop_delete_attribute($att_type, $id, $att_name) {
	$foxy_data = array(
		"api_action" => "attribute_delete",
		"name" => $att_name,
		"type" => $att_type,
		"identifier" => $id
	);
	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
	return (string)$xml->result . ": " . (string)$xml->messages->message;
}



//Attributes for Orders, Customers, Subscriptions
function foxyshop_manage_attributes($xml, $id, $att_type) {
	global $foxyshop_settings;
	if (version_compare($foxyshop_settings['version'], '0.7.2', "<")) return "";

	$holder = '<div style="clear: both; height: 20px;"></div>';
	$holder .= '<table class="foxyshop_attribute_list" rel="' . $id . '"><tbody>'."\n";

	foreach($xml->attribute as $attribute) {
		$attribute_name = (string)$attribute->name;
		$attribute_value = (string)$attribute->value;

		$holder .= '<tr class="viewing">';
		$holder .= '<td class="col1">' . htmlspecialchars($attribute_name) . '</td>';
		$holder .= '<td class="col2"><div>' . str_replace("\n", "<br />\n", $attribute_value) . '</div><a href="#" class="foxyshop_attribute_delete" attname="' . htmlspecialchars($attribute_name) . '" rel="' . $id . '" title="Delete">' . __('Delete', 'foxyshop') . '</a><a href="#" class="foxyshop_attribute_edit" rel="' . $id . '" title="Edit">' . __('Edit', 'foxyshop') . '</a></td>'."\n";
		$holder .= '</tr>';
	}
	$holder .= "</tbody></table>\n";
	$holder .= '<input type="button" value="' . __('Add Attribute', 'foxyshop') . '" class="button foxyshop_add_attribute" rel="' . $id . '" />'."\n";
	return $holder;
}

//Attributes for jQuery
function foxyshop_manage_attributes_jquery($att_type) {
	global $foxyshop_settings;
	if (version_compare($foxyshop_settings['version'], '0.7.2', "<")) return "";
	?>

	//Show New Form
	$(".foxyshop_add_attribute").click(function(e) {
		var id = $(this).attr("rel");
		var new_html = '<tr id="new_attribute_container_' + id + '"><td><input type="text" class="new_attribute_name" name="new_attribute_name" placeholder="Name" rel="' + id + '" /></td><td><textarea placeholder="Value" class="new_attribute_value" name="new_attribute_value" rel="' + id + '"></textarea> <input type="button" value="Add Attribute" class="button-primary foxyshop_add_new_attribute" rel="' + id + '" /> <br /> <input type="button" value="Cancel" class="button foxyshop_cancel_new_attribute" rel="' + id + '" /> </td></tr>';
		$(".foxyshop_attribute_list[rel='" + id + "']").append(new_html);
		$(this).hide();
		e.preventDefault();
		return false;
	});

	//Cancel New Form
	$(".foxyshop_cancel_new_attribute").live("click", function(e) {
		var id = $(this).attr("rel");
		$("#new_attribute_container_" + id).remove();
		$(".foxyshop_add_attribute[rel='" + id + "']").show();
		e.preventDefault();
		return false;
	});

	//Cancel Edit Form
	$(".foxyshop_cancel_save_attribute").live("click", function(e) {
		var id = $(this).attr("rel");
		var original_text = $(this).attr("original_text").replace("\n", "<br />\n");
		var parent_tr = $(this).parents(".foxyshop_attribute_list tr");
		parent_tr.addClass("viewing");
		parent_tr.find(".col2 div").html(original_text);
		e.preventDefault();
		return false;
	});

	//Add New
	$(".foxyshop_add_new_attribute").live("click", function(e) {
		var id = $(this).attr("rel");
		var att_name = $(".new_attribute_name[rel='" + id + "']").val();
		var att_value = $(".new_attribute_value[rel='" + id + "']").val();
		var manage_buttons = '<a href="#" class="foxyshop_attribute_delete" attname="' + att_name + '" title="Delete" rel="' + id + '">Delete</a><a href="#" class="foxyshop_attribute_edit" rel="' + id + '" title="Edit">Edit</a>';

		if (att_name && att_value) {
			$.post(ajaxurl, {action: "foxyshop_attribute_manage", foxyshop_action: "save_attribute", security: "<?php echo wp_create_nonce("foxyshop-save-attribute"); ?>", att_type: "<?php echo $att_type; ?>", id: id, att_name: att_name, att_value: att_value }, function(response) {
				$(".foxyshop_add_attribute[rel='" + id + "']").show();
				$("#new_attribute_container_" + id).remove();
				$(".foxyshop_attribute_list[rel='" + id + "']").append('<tr class="viewing"><td class="col1">' + att_name + '</td><td class="col2"><div>' + att_value.replace("\n", "<br />\n") + '</div> ' + manage_buttons + '</td></tr>');
			});
		} else {
			alert('Please enter a name and value before submitting.');
		}
		e.preventDefault();
		return false;
	});

	//Save Attribute
	$(".foxyshop_save_attribute").live("click", function(e) {
		var id = $(this).attr("rel");
		var parent_tr = $(this).parents(".foxyshop_attribute_list tr");
		var att_name = parent_tr.children(".col1").text();
		var att_value = parent_tr.find(".col2 div").children("textarea").val();

		if (att_value) {
			$.post(ajaxurl, {action: "foxyshop_attribute_manage", foxyshop_action: "save_attribute", security: "<?php echo wp_create_nonce("foxyshop-save-attribute"); ?>", att_type: "<?php echo $att_type; ?>", id: id, att_name: att_name, att_value: att_value }, function(response) {
				parent_tr.addClass("viewing");
				parent_tr.find(".col2 div").html(att_value.replace(/\n/g, "<br />\n"));
			});
		} else {
			alert('Please enter a value before submitting.');
		}
		e.preventDefault();
		return false;
	});

	//Start Editing
	$(".foxyshop_attribute_edit").live("click", function(e) {
		var id = $(this).attr("rel");
		var parent_tr = $(this).parents(".foxyshop_attribute_list tr");
		var att_value = parent_tr.find(".col2 div").text();

		parent_tr.removeClass("viewing");
		parent_tr.find(".col2 div").html('<textarea placeholder="Value" class="edit_attribute_value" name="new_attribute_value" rel="' + id + '">' + att_value + '</textarea> <input type="button" value="Save Changes" class="button-primary foxyshop_save_attribute" rel="' + id + '" /> <br /> <input type="button" value="Cancel" class="button foxyshop_cancel_save_attribute" rel="' + id + '" original_text="' + att_value + '" />');

		e.preventDefault();
		return false;
	});

	//Delete
	$(".foxyshop_attribute_delete").live("click", function(e) {
		var id = $(this).attr("rel");
		var att_name = $(this).attr("attname");
		var parent_tr = $(this).parents(".foxyshop_attribute_list tr");

		$.post(ajaxurl, {action: "foxyshop_attribute_manage", foxyshop_action: "delete_attribute", security: "<?php echo wp_create_nonce("foxyshop-save-attribute"); ?>", att_type: "<?php echo $att_type; ?>", id: id, att_name: att_name }, function(response) {
			parent_tr.remove();
		});

		e.preventDefault();
		return false;
	});

<?php

}
