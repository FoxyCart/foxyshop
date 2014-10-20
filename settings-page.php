<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//Save Settings
add_action('admin_init', 'foxyshop_save_settings');
function foxyshop_save_settings() {
	if (!isset($_POST['foxyshop_settings_update'])) return;
	if (!check_admin_referer('update-foxyshop-options')) return;
	global $foxyshop_settings;

	//Check for downloadables sync first and perform if setting is new
	if (!$foxyshop_settings['downloadables_sync'] && isset($_POST['foxyshop_downloadables_sync'])) {
		foxyshop_get_downloadable_list();
	}

	//Loop Through Most Fields
	$fields = array(
		"version",
		"ship_categories",
		"weight_type",
		"enable_ship_to",
		"enable_dashboard_stats",
		"enable_subscriptions",
		"expiring_cards_reminder",
		"enable_bundled_products",
		"enable_addon_products",
		"related_products_custom",
		"related_products_tags",
		"sort_key",
		"use_jquery",
		"ga",
		"ga_advanced",
		"ga_type",
		"ga_demographics",
		"hide_subcat_children",
		"generate_product_sitemap",
		"manage_inventory_levels",
		"inventory_alert_level",
		"inventory_alert_email",
		"enable_sso",
		"sso_account_required",
		"checkout_customer_create",
		"downloadables_sync",
		"google_product_support",
		"google_product_merchant_id",
		"include_exception_list",
		"show_add_to_cart_link",
		"use_cart_validation",
		"locale_code"
	);
	foreach ($fields as $field1) {
		$foxyshop_settings[$field1] = isset($_POST['foxyshop_'.$field1]) ? trim(stripslashes($_POST['foxyshop_'.$field1])) : '';
	}
	//Loop Through No Trim Fields
	$fields = array(
		"browser_title_1",
		"browser_title_2",
		"browser_title_3",
		"browser_title_4",
		"browser_title_5",
		"browser_title_6",
		"browser_title_7",
	);
	foreach ($fields as $field1) {
		$foxyshop_settings[$field1] = isset($_POST['foxyshop_'.$field1]) ? stripslashes($_POST['foxyshop_'.$field1]) : '';
	}

	//Default Image
	if ($_POST['foxyshop_default_image'] == 2) {
		$foxyshop_settings["default_image"] = "none";
	} elseif ($_POST['foxyshop_default_image'] == 1 && $_POST['foxyshop_default_image_custom'] != "") {
		$foxyshop_settings["default_image"] = trim(stripslashes($_POST['foxyshop_default_image_custom']));
	} else {
		$foxyshop_settings["default_image"] = "";
	}

	//Order Desk URL
	if (isset($_POST['foxyshop_set_orderdesk_url']) && !empty($_POST['foxyshop_orderdesk_url'])) {
		$foxyshop_settings["orderdesk_url"] = $_POST['foxyshop_orderdesk_url'];
	} else {
		$foxyshop_settings["orderdesk_url"] = "";
	}


	//Set FoxyCart Domain Name
	$domain = $_POST['foxyshop_domain'];
	if ($domain && get_option("foxyshop_setup_required")) delete_option("foxyshop_setup_required"); //Delete the setup prompt if domain entered
	if ($domain && strpos($domain, ".") === false) $domain .= ".foxycart.com";
	$foxyshop_settings["domain"] = trim(stripslashes(str_replace("http://","",$domain)));

	//Set Setup Prompt If FoxyCart API Version Available
	//if ($domain && version_compare($foxyshop_settings['version'], '1.1', ">=") && !$foxyshop_settings['api']['store_access_token']) add_option("foxyshop_setup_required", 1);

	//Other Settings Treated Specially
	$foxyshop_settings["default_weight"] = (int)$_POST['foxyshop_default_weight1'] . ' ' . (double)$_POST['foxyshop_default_weight2'];
	$foxyshop_settings["products_per_page"] = ((int)$_POST['foxyshop_products_per_page'] == 0 ? -1 : (int)$_POST['foxyshop_products_per_page']);

	//Cache the FoxyCart Includes
	if (version_compare($foxyshop_settings['version'], '0.7.2', ">=") && version_compare($foxyshop_settings['version'], '2.0', "<") && $foxyshop_settings['domain']) {
		if (version_compare($foxyshop_settings['version'], '0.7.2', "<=")) {
			$cart_type = "colorbox";
		} else {
			$cart_type = "sidecart";
		}
		$foxy_data = array("api_action" => "store_includes_get", "javascript_library" => "none", "cart_type" => $cart_type);
		$foxy_data = apply_filters('foxyshop_store_includes_get', $foxy_data);
		$foxy_response = foxyshop_get_foxycart_data($foxy_data);
		$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
		if ($xml->result != "ERROR") {
			if ($xml->code_block) $foxyshop_settings['foxycart_include_cache'] = (string)$xml->code_block;
		} else {
			$foxyshop_settings['foxycart_include_cache'] = "";
		}
	} else {
		$foxyshop_settings['foxycart_include_cache'] = "";
	}

	//Save
	update_option("foxyshop_settings", $foxyshop_settings);
	wp_redirect("edit.php?post_type=foxyshop_product&page=foxyshop_settings_page&saved=1");
	exit;
}

add_action('admin_menu', 'foxyshop_settings_menu');
function foxyshop_settings_menu() {
	add_submenu_page('edit.php?post_type=foxyshop_product', __('Settings', 'foxyshop'), __('Settings', 'foxyshop'), apply_filters('foxyshop_settings_perm', 'manage_options'), 'foxyshop_settings_page', 'foxyshop_settings_page');
}
function foxyshop_settings_page() {
	global $foxyshop_settings, $foxycart_version_array;
	if (!defined('FOXYSHOP_TEMPLATE_PATH')) define('FOXYSHOP_TEMPLATE_PATH',STYLESHEETPATH);
?>
<div id="foxyshop_settings_wrap" class="wrap">


	<div class="icon32" id="icon-options-general"><br></div>
	<h2>FoxyShop Settings <a class="<?php if (version_compare(get_bloginfo('version'), '3.2', "<")) echo "button "; ?>add-new-h2" href="admin.php?page=foxyshop_setup">Setup Wizard</a></h2>


	<?php
	//Headway Sniffer
	if (class_exists("Headway")) {
		if (is_plugin_active("foxyshop-headway/foxyshop-block.php")) echo '<div class="updated"><p><strong>Hello Headway User!</strong> Be sure to install the <a href="http://www.foxy-shop.com/2012/04/installing-foxyshop-on-headway/" target="_blank">FoxyShop Headway plugin</a> for full Headway compatability.</p></div>';

	//Thesis Sniffer
	} elseif (defined("THESIS_ADMIN")) {
		if (!defined('FOXYSHOP_TEMPLATE_PATH')) define('FOXYSHOP_TEMPLATE_PATH',STYLESHEETPATH);
		if (!file_exists(FOXYSHOP_TEMPLATE_PATH . '/foxyshop-single-product.php') && !file_exists(TEMPLATEPATH . '/foxyshop-single-product.php')) {
			echo '<div class="updated"><p><strong>Hello Thesis User!</strong> Please be sure to install <a href="http://www.foxy-shop.com/wp-content/uploads/2012/04/foxyshop-template-files-for-thesis.zip">these files</a> in your theme folder (or preferably your child theme folder) for Thesis compatibility.</p></div>';
		} else {
			$skip_header_warning = 1;
		}
	}


	//Confirmation Saved
	if (isset($_GET['saved'])) echo '<div class="updated"><p>' . __('Your Settings Have Been Saved.', 'foxyshop') . '</p></div>';

	//Setup Prompt Hidden
	if (isset($_GET['hide_setup_prompt'])) {
		delete_option("foxyshop_setup_required");
		echo '<div class="updated"><p>' . __('The setup prompt has been hidden. You can always use the setup wizard by clicking the link above or just configure the settings on this page.', 'foxyshop') . '</p></div>';
	}

	//Inital Setup
	if (isset($_GET['setup'])) echo '<div class="updated"><p>' . __('<strong>Congratulations!</strong> You are all set up and ready to go. You may now review all the settings on this page and start entering products.', 'foxyshop') . '</p></div>';

	//Warning PHP Version
	if (version_compare(PHP_VERSION, '5.1.2', "<")) echo '<div class="error"><p>' . sprintf(__('<strong>Warning:</strong> You are using PHP version %s. FoxyShop requires PHP version 5.1.2 or higher to utilize the required hmac_has() functions. Without upgrading you will experience problems adding items to the cart and completing other tasks. After upgrading, make sure that you reset your API key (on the FoxyShop Tools page) to ensure that you have a fully secure key.', 'foxyshop'), PHP_VERSION) . '</p></div>';

	//Warning Header/Footer Missing
	if ((!file_exists(TEMPLATEPATH.'/header.php') || !file_exists(TEMPLATEPATH.'/footer.php')) && !isset($skip_header_warning)) echo '<div class="error"><p>' . __('<strong>Warning:</strong> Your theme does not appear to be using header.php or footer.php. Without these files FoxyShop pages will show up unstyled. This error can often show up if you are using a WordPress framework that is bypassing the get_header() and get_footer() functions.', 'foxyshop') . '</p></div>';

	//Warning About cURL Installation
	if (!in_array('curl', get_loaded_extensions())) echo '<div class="error"><p>' . __('<strong>Warning:</strong> Your web server does not have cURL installed. Without cURL, FoxyShop will not be able to sync settings with FoxyCart and you will experience errors while saving.', 'foxyshop') . '</p></div>';

	//Warning Upload Folders
	$upload_dir = wp_upload_dir();
	if ($upload_dir['error'] != '') {
		echo '<div class="error"><p><strong>Warning:</strong> ' . $upload_dir['error'].'</p></div>';
	} elseif (!file_exists($upload_dir['basedir'] . '/customuploads')) {
		if (!is_writeable($upload_dir['basedir'])) echo '<div class="error"><p><strong>Warning:</strong> ' . $upload_dir['basedir'].' is not writeable. You may encounter problems uploading images or allowing the custom upload of files by customers. (To hide this notice, add a folder under <em>wp-content/uploads</em> called <em>customupload</em>.)</p></div>';
	}

	$info_icon = "data:image/png;base64,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";
	$settings_icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAANkE3LLaAgAAAs1JREFUeJx9U11Ik2EYPTp1ueXU5dLpMvMnsEjnJq7yB5WyMEgIlTQVRU1YA/MiVpgEilPvugjUK2mKdhFNNiy0lQOxtilp7sJkKeq+YWvCN/Sz+bP2dpNh/p3L85xznsMDDwBAIpHkRkdHX8DRCKipqbmv1+vHNBrNRwDB/01LSko6JiYmfmVlZV05zN3Z2dlLCCEURf3OzMxU/KX9AZzY1bBbW1t14+PjVgC8vea0tLQcmqaJ2+0m1dXV7bt8V1fXy6Kiogd7tb6FhYUvVCrVWwABu2R+fn4lTdPE4XAQk8m0KJVKZUqlstnj8ZC8vLzaA3UrKip6GhsbNQBYABATEyObn58nNpuNrKysEIqitmmaJmaz2R0SEpLkuz9ArVbX0jRN6uvrewAENDQ0yLlcLgDA6/WCzWb72+12tLS0dLtcrhmfw44GwK+urq6/oKAgQya7LGTWXFCp2nTLFLUYFhYmNBqNI1arVQ1gy++IAHZKcgo3NjZOSK/+wLuxyYXuvj453G5qv5C1n+DxePzBwUHtpaSk69ubGzDZdvCZnRoqPHuO/31Mpz02QCDgRKjVAzpxcko6L+gkDIZR+6Nei3PdL5wfJ04Vh0dGnl4wDg8d2lkoFEaPDI9MLy0tE6fTSSwWy6ZIFHETQCInt8kmaZ4k5W9+kozapva9PhYAiESieK1W+z4+ISERIPB6vSgtLX04Ozs3AGB1x/bFvOYbcdsbKOAkXL2WEciGxz7zaQwAWDwej6/RaPT9/a+mGGZtXSwWn6mqquoYHR1t+7fGu23bsU9/XWOJChB4in0+PS/XZ8tFO+amTFAoFI83NjaIQqF4ZjAYvsnl8teHHRcAECi4w7313C1tNpPyQZok5BbfRVlZ2ROGYQjDMESpVA5h3y8cACeqIrhYTVKfflgPjbl4AwCCsrOz5RKJpBIA91jzLkLi77G4UTkA8AemGSncz8QQ2gAAAABJRU5ErkJggg==";
	$display_icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAN1wAADdcBQiibeAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAGWSURBVDiNlZHNahRBFIW/0307isZItpmHUARB48Y/0LyEe3EjggOarQqOooJ7wbfQ6JDZuTLRp1BcxMX89qSqroueOJPJdCBnV12nvnvuab1++/J9DPG+u+ecQJJinufPLOyHB3c37qgoCtAhS3Vc9A0oyzL//GnriQFYYdx78e3olLrpmfj4dB0gM0lJZLm7c/vmZVDds0qeEu3ODqp8MsAlSDHR64/oDgNeNxlYPp2TYiRTBoBB5Y8p0RuM6A728ToCIC+IMaJskkBSAuHJ6fdGdPvjYwG5R1JMCOHuVQIBKU1W6Jb17QFLWSQmP+hgsoIEAndn9VwxaUtIqvZzB025gpkSRQI4e8r48+s3aPqv8cpd8aew5TNL0wRCPhj0+bB5rT73AvUHvSpB8jT+svX1RI9nJZ+r/M27V89DCE1JdnX9CuW45Mf3n8QUg5m1Hj18vDnrt3liCKF549Z129v7y87uLjni4qULrJxfse12pwkcDzhQo7FGo7H2/zwcDhf6jgDMrLXd7jTd/dCdpGBmrXn/PyMSrHB+wxAhAAAAAElFTkSuQmCC";
	$pagetitle_icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAhdJREFUeNqEk8tqVEEQhv/q7swMOmMwJqgQByRERBCEASGiiLgVd6ILfYBZCgHdKAFXrly4CCF5AEMewJWIKII3gihmo3iBWYkg4txO36zqmWMmJ14aDqe7q+qvr7qrKcaI0XFx4dFt/jX5q2D7eMLf4urC6bV8g4oCF248/LF07VQNxAs2iVk8Ak/aHYubK6/Q67tLE+OV1aX5OZhiirVbZ2rfOw5vvnRhQ4RzEdZHaE1oHNyJzAWUDd37+u1njd1XzJ+QtaKUdf9kFYdnJlHfN44x3mv3fLLbSCgZWj4/f3+PCT40/4d8slHHbH03iAgz9QlUKxr9zOHF+uem8c6VlMI/kR8//8gBludTmDt2AJ7FT8xWcfnZ+2nlrGMggjEEzRkUB0oJReSnLz/h9UYLY2wvaZWAXeasYQFGAxsUYwfeZQsfrTgUkdffttA4Og1xE7vNMhjHaCJgOGNgEa4+iUg5G63uFuQr1z8kMq9iimECEXBJzTBaSC2xKZJfsk5F8uF6n0pVfkAnyY1giFlzdiNs2C6SI0tA2ShYCoO1dTQgGJYQ8xIKIjmy5/M6tLeUrtn5IAKddAYiZ7T0AG0GjojkyJ12D7t2GBw5e6fLhp68C7kFSmFydYYzeS6IFBQNAhVtQe5Lx757cPV364tAx/tQnqqqYSfGhBijRpoNOzNHLr4d6cTF4+fu/u35jo6EXNz8JcAAvqMmox2nmLYAAAAASUVORK5CYII=";
	$advanced_icon = "data:image/png;base64,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";
	?>

	<table class="widefat infoonly" id="foxyshop_settings_header" style="margin-top: 14px;">
		<thead>
			<tr>
				<th>
					<div id="settings_title">FoxyShop <?php echo $foxyshop_settings['foxyshop_version']; ?></div>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td style="border-bottom: 0 none;">
					<a href="http://www.foxy-shop.com/?utm_source=plugin&amp;utm_medium=app&amp;utm_campaign=pluginlink_<?php echo FOXYSHOP_VERSION ?>" target="_blank"><img src="<?php echo FOXYSHOP_DIR; ?>/images/logo.png" alt="FoxyShop" style="float: right; margin-left: 20px;" /></a>

					<p>Stay up to date with the latest updates from FoxyShop by following on Twitter and Facebook.</p>
					<a href="http://twitter.com/FoxyShopWP" class="twitter-follow-button">Follow @FoxyShopWP</a>
					<script src="http://platform.twitter.com/widgets.js" type="text/javascript"></script>
					<iframe src="http://www.facebook.com/plugins/like.php?href=<?php echo urlencode('https://www.facebook.com/pages/FoxyShop/188079417920111'); ?>&amp;layout=button_count&amp;show_faces=false&amp;width=190&amp;action=like&amp;colorscheme=light&amp;font=arial" scrolling="no" frameborder="0" allowTransparency="true" style="border:none; overflow:hidden; width:190px; height:26px;"></iframe>

					<p>
					<a href="http://www.foxy-shop.com/documentation/?utm_source=plugin&amp;utm_medium=app&amp;utm_campaign=pluginlink_<?php echo FOXYSHOP_VERSION ?>" target="_blank" class="button"><?php _e('FoxyShop Documentation', 'foxyshop'); ?></a>
					<a href="http://affiliate.foxycart.com/idevaffiliate.php?id=211&amp;url=http://www.foxycart.com/" target="_blank" class="button"><?php _e('FoxyCart Information', 'foxyshop'); ?></a>
					<a href="http://affiliate.foxycart.com/idevaffiliate.php?id=211&amp;url=http://wiki.foxycart.com/" target="_blank" class="button"><?php _e('FoxyCart Wiki', 'foxyshop'); ?></a>
					<a href="http://affiliate.foxycart.com/idevaffiliate.php?id=211&amp;url=http://admin.foxycart.com/" target="_blank" class="button"><?php _e('FoxyCart Admin Panel', 'foxyshop'); ?></a>

					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<br /><br />
	<form>

	<table class="widefat infoonly">
		<thead>
			<tr>
				<th><img src="<?php echo $info_icon; ?>" alt="" /><?php _e('Setup Information', 'foxyshop'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td style="border-bottom: 0 none;">
					<label for="foxyshop_key"><?php _e('API Key', 'foxyshop'); ?>:</label>
					<input type="text" id="foxyshop_key" name="key" value="<?php echo esc_attr($foxyshop_settings['api_key']); ?>" readonly="readonly" onclick="this.select();" />
					<a href="#" class="foxyshophelp">The API key is saved here and stored on your FoxyCart account so that your cart information can be encrypted to avoid link tampering. The API key is also used to communicate with FoxyCart and retrieve your order information.<br /><br />This API key is generated automatically and cannot be edited. Go to the tools page if you need to reset this key.</a>
					<div style="clear: both; padding: 5px 0; font-style: italic;"><strong style="color: #BB1E1E;">Required Setup:</strong> Enter this API key on the advanced menu of your <a href="http://affiliate.foxycart.com/idevaffiliate.php?id=211&url=http://admin.foxycart.com/" target="_blank">FoxyCart admin</a> and check the box to enable cart validation.</div>

					<div style="clear: both;"></div>

					<label for="foxyshop_datafeed_url"><?php _e('Datafeed URL', 'foxyshop'); ?>:</label>
					<input type="text" id="foxyshop_datafeed_url" name="foxyshop_datafeed_url" value="<?php echo get_bloginfo('url') . '/foxycart-datafeed-' . $foxyshop_settings['datafeed_url_key']; ?>/" readonly="readonly" onclick="this.select();" />
					<a href="#" class="foxyshophelp">FoxyCart can be configured to send order information to a url on your website. If you want to use FoxyShop's datafeed and take advantage of inventory, user management and more, copy this url and enable the datafeed in your FoxyCart admin panel.</a>

					<div style="clear: both;margin-bottom: 5px;"></div>

					<label for="foxyshop_sso_url"><?php _e('SSO Endpoint', 'foxyshop'); ?>:</label>
					<input type="text" id="foxyshop_sso_url" name="foxyshop_sso_url" value="<?php echo get_bloginfo('url') . '/foxycart-sso-' . $foxyshop_settings['datafeed_url_key']; ?>/" readonly="readonly" onclick="this.select();" />
					<a href="#" class="foxyshophelp">FoxyShop can automatically sync your WordPress and FoxyCart users. If you want to take advantage of this feature, copy this url and enable the Single Sign On feature in the FoxyCart admin panel. Also, be sure to set the customer password hash to phpass.</a>

					<div style="clear: both;margin-bottom: 5px;"></div>

					<label for="foxyshop_theme_dir"><?php _e('Template Path', 'foxyshop'); ?>:</label>
					<input type="text" id="foxyshop_theme_dir" name="foxyshop_theme_dir" value="<?php echo esc_attr(FOXYSHOP_TEMPLATE_PATH); ?>/" readonly="readonly" />
					<a href="#" class="foxyshophelp">FoxyShop will look in this folder for customized theme files.</a>

					<?php if ($foxyshop_settings['generate_product_sitemap']) { ?>
						<div style="clear: both;margin-bottom: 5px;"></div>
						<label for="foxyshop_sitemap"><?php _e('Sitemap', 'foxyshop'); ?>:</label>
						<input type="text" id="foxyshop_sitemap" name="foxyshop_sitemap" value="http://<?php echo esc_attr($_SERVER['SERVER_NAME']) . '/' . FOXYSHOP_PRODUCT_SITEMAP_SLUG . '/'; ?>" readonly="readonly" onclick="this.select();" />
						<a href="#" class="foxyshophelp">This is the url where you can find your sitemap for submitting to search engines.</a>
					<?php } ?>

				</td>
			</tr>
		</tbody>
	</table>
	</form>

	<br /><br />

	<form method="post" name="foxycart_settings_form" action="options.php" onsubmit="return foxyshop_check_settings_form();">

	<table class="widefat">
		<thead>
			<tr>
				<th><img src="<?php echo $settings_icon; ?>" alt="" /><?php _e('FoxyCart Settings', 'foxyshop'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<?php
				if (substr($foxyshop_settings['domain'], -13) == ".foxycart.com" || !$foxyshop_settings['domain']) {
					$foxycart_domain_class = "simple";
					$foxycart_domain = str_replace(".foxycart.com", "", $foxyshop_settings['domain']);
				} else {
					$foxycart_domain_class = "advanced";
					$foxycart_domain = $foxyshop_settings['domain'];
				}
				?>
				<td class="foxycartdomain <?php echo $foxycart_domain_class; ?>">
					<label for="foxyshop_domain"><?php _e('Your FoxyCart Domain', 'foxyshop'); ?>:</label> <input type="text" name="foxyshop_domain" id="foxyshop_domain" value="<?php echo esc_attr($foxycart_domain); ?>" size="50" />
					<label id="foxydomainsimplelabel">.foxycart.com</label>
					<a href="#" class="foxyshophelp">If you have your own custom domain, you may enter that as well (store.yoursite.com). Do not include the "http://". The FoxyCart include files will be inserted automatically so you won't need to add anything to the header of your site.</a>
					<div id="foxydomain_simple">Have a customized FoxyCart domain like store.yoursite.com? <a href="#" class="foxydomainpicker" rel="advanced">Click here.</a></div>
					<div id="foxydomain_advanced">Have a regular FoxyCart domain like yourstore.foxycart.com? <a href="#" class="foxydomainpicker" rel="simple">Click here.</a></div>
				</td>
			</tr>
			<tr>
				<td>
					<label for="foxyshop_version"><?php _e('FoxyCart Version', 'foxyshop'); ?>:</label>
					<select name="foxyshop_version" id="foxyshop_version">
					<?php
					foreach ($foxycart_version_array as $key => $val) {
						echo '<option value="' . $key . '"' . ($foxyshop_settings['version'] == $key ? ' selected="selected"' : '') . '>' . $val . '  </option>'."\n";
					} ?>
					</select>
					<a href="#" class="foxyshophelp">Version 0.7.0 was a big step up from 0.6.0 and used the new ColorBox overlay. Version 0.7.1 added images to the cart checkout. Version 0.7.2 added new API options. Version 1.0 added live tax rates and a new country selector. Version 2.0 completely rebuilt the checkout templates.<br /><br />If you are upgrading to 0.7.2 or higher, change your version at FoxyCart and save, then update here.</a>
				</td>
			</tr>
			<tr>
				<td>
					<label for="foxyshop_include_exception_list"><?php _e("Skip FoxyCart Includes on These Pages", 'foxyshop'); ?>:</label>
					<input type="text" name="foxyshop_include_exception_list" id="foxyshop_include_exception_list" value="<?php echo esc_attr($foxyshop_settings['include_exception_list']); ?>" size="50" />
					<a href="#" class="foxyshophelp">Enter page slugs or ID's, separated by comma and the FoxyCart includes will not be included on these pages. This is helpful if you are setting up a template for checkout caching.<br /><br />Enter * to keep includes from showing on any page. This is useful if you want to enter the includes manually.</a>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" id="foxyshop_use_jquery" name="foxyshop_use_jquery"<?php checked($foxyshop_settings['use_jquery'], "on"); ?> />
					<label for="foxyshop_use_jquery"><?php echo sprintf(__('Automatically Insert jQuery %s from Google CDN', 'foxyshop'), foxyshop_get_jquery_version()); ?></label>
					<a href="#" class="foxyshophelp">If you are already manually inserting jQuery you should uncheck this option.</a>
				</td>
			</tr>
		</tbody>
	</table>
	<p><input type="submit" class="button-primary" value="<?php _e('Save All Settings', 'foxyshop'); ?>" /></p>

	<br /><br />


	<table class="widefat">
		<thead>
			<tr>
				<th><img src="<?php echo $display_icon; ?>" alt="" /><?php _e('Display Settings', 'foxyshop'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<label for="sort_key"><?php echo FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('Sorting', 'foxyshop'); ?>:</label>
					<select name="foxyshop_sort_key" id="sort_key">
					<?php
					$sortArray = array("menu_order" => "Custom Order", "name" => FOXYSHOP_PRODUCT_NAME_SINGULAR . " Name", "price_asc" => "Price (Lowest to Highest)", "price_desc" => "Price (Highest to Lowest)", "date_asc" => "Date (Oldest to Newest)", "date_desc" => "Date (Newest to Oldest)");
					foreach ($sortArray as $key=>$val) {
						echo '<option value="' . $key . '"' . ($foxyshop_settings['sort_key'] == $key ? ' selected="selected"' : '') . '>' . $val . '  </option>'."\n";
					} ?>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<label for="foxyshop_products_per_page"><?php echo FOXYSHOP_PRODUCT_NAME_PLURAL . ' ' . __('Per Page', 'foxyshop'); ?>:</label> <input type="text" id="foxyshop_products_per_page" name="foxyshop_products_per_page" value="<?php echo ($foxyshop_settings['products_per_page'] < 0 ? 0 : $foxyshop_settings['products_per_page']); ?>" style="width: 50px;" />
					<small>Enter 0 to show all products (no paging)</small>
				</td>
			</tr>
			<tr>
				<td>
					<strong><?php _e("What To Show if No Image Is Loaded", 'foxyshop'); ?>:</strong>
					<div style="clear: both;"></div>
					<input type="radio" name="foxyshop_default_image" id="foxyshop_default_image_0" value="0"<?php if ($foxyshop_settings['default_image'] == "") echo ' checked="checked"'; ?> /><label for="foxyshop_default_image_0" style="width: 95px;"><?php _e('Default Image'); ?></label> <input type="text" id="foxyshop_default_image_custom" name="foxyshop_default_image_standard" value="<?php echo WP_PLUGIN_URL."/foxyshop/images/no-photo.png";?>" readonly="readonly" style="width:544px;" onclick="jQuery('#foxyshop_default_image_0').prop('checked', true);" />
					<div style="clear: both;"></div>
					<input type="radio" name="foxyshop_default_image" id="foxyshop_default_image_1" value="1"<?php if ($foxyshop_settings['default_image'] && $foxyshop_settings['default_image'] != "") echo ' checked="checked"'; ?> /><label for="foxyshop_default_image_1" style="width: 95px;"><?php _e('Custom Image'); ?></label> <input type="text" id="foxyshop_default_image_custom" name="foxyshop_default_image_custom" value="<?php if ($foxyshop_settings['default_image'] != "none") echo esc_attr($foxyshop_settings['default_image']); ?>" style="width:544px;" onclick="jQuery('#foxyshop_default_image_1').prop('checked', true);" />
					<div style="clear: both;"></div>
					<input type="radio" name="foxyshop_default_image" id="foxyshop_default_image_2" value="2"<?php if ($foxyshop_settings['default_image'] == "none") echo ' checked="checked"'; ?> /><label for="foxyshop_default_image_2"><?php _e("Don't Show a Default Image"); ?></label>
					<div class="small" style="line-height: 13px;"><strong>Note:</strong> If you are loading a custom image, it is not recommended to load a full url in your dev environment. Changing the url later via<br />a mass mysql update can invalidate your settings and erase them. Use a relative path starting with /wp-content/</div>
				</td>
			</tr>



		</tbody>
	</table>
	<p><input type="submit" class="button-primary" value="<?php _e('Save All Settings', 'foxyshop'); ?>" /></p>
	<br /><br />


	<table class="widefat">
		<thead>
			<tr>
				<th><img src="<?php echo $pagetitle_icon; ?>" alt="" /><?php _e('Browser Title Bar Settings', 'foxyshop'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<label for="foxyshop_browser_title_1" style="width: 123px;"><?php echo __('All', 'foxyshop') . ' ' . FOXYSHOP_PRODUCT_NAME_PLURAL; ?>:</label> <input type="text" name="foxyshop_browser_title_1" value="<?php echo esc_attr($foxyshop_settings['browser_title_1']); ?>" size="50" />
					<div style="clear: both;"></div>
					<label for="foxyshop_browser_title_2" style="width: 123px;"><?php _e('All Categories', 'foxyshop'); ?>:</label> <input type="text" name="foxyshop_browser_title_2" value="<?php echo esc_attr($foxyshop_settings['browser_title_2']); ?>" size="50" />
					<div style="clear: both;"></div>
					<label for="foxyshop_browser_title_3" style="width: 123px;"><?php _e('Single Category', 'foxyshop'); ?>:</label> <input type="text" name="foxyshop_browser_title_3" value="<?php echo esc_attr($foxyshop_settings['browser_title_3']); ?>" size="50" /> <small>Use %c for Category Name</small>
					<div style="clear: both;"></div>
					<label for="foxyshop_browser_title_4" style="width: 123px;"><?php echo __('Single', 'foxyshop') . ' ' . FOXYSHOP_PRODUCT_NAME_SINGULAR; ?>:</label> <input type="text" name="foxyshop_browser_title_4" value="<?php echo esc_attr($foxyshop_settings['browser_title_4']); ?>" size="50" /> <small>Use %p for <?php echo esc_html(FOXYSHOP_PRODUCT_NAME_SINGULAR); ?> Name</small>
					<div style="clear: both;"></div>
					<label for="foxyshop_browser_title_5" style="width: 123px;"><?php _e('Search Results', 'foxyshop'); ?>:</label> <input type="text" name="foxyshop_browser_title_5" value="<?php echo esc_attr($foxyshop_settings['browser_title_5']); ?>" size="50" />
					<div style="clear: both;"></div>
					<label for="foxyshop_browser_title_6" style="width: 123px;"><?php _e('Checkout Template', 'foxyshop'); ?>:</label> <input type="text" name="foxyshop_browser_title_6" value="<?php echo esc_attr($foxyshop_settings['browser_title_6']); ?>" size="50" />
					<div style="clear: both;"></div>
					<label for="foxyshop_browser_title_7" style="width: 123px;"><?php _e('Receipt Template', 'foxyshop'); ?>:</label> <input type="text" name="foxyshop_browser_title_7" value="<?php echo esc_attr($foxyshop_settings['browser_title_7']); ?>" size="50" />

				</td>
			</tr>
		</tbody>
	</table>
	<p><input type="submit" class="button-primary" value="<?php _e('Save All Settings', 'foxyshop'); ?>" /></p>

	<br /><br />

	<table class="widefat">
		<thead>
			<tr>
				<th><img src="<?php echo $advanced_icon; ?>" alt="" /><?php _e('Advanced Settings', 'foxyshop'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<div id="foxyshop_ship_category_label">
						<label for="foxyshop_ship_categories" style="vertical-align: top;"><?php _e('FoxyCart Categories', 'foxyshop'); ?>:</label>
						<a href="#" class="foxyshophelp">These categories should correspond to the category codes you set up in your FoxyCart admin and will be available in a drop-down on your <?php echo strtolower(FOXYSHOP_PRODUCT_NAME_SINGULAR); ?> setup page. Separate each category with a line break. If you would like to also display a nice name in the dropdown menu, use a pipe sign "|" like this: free_shipping|Free Shipping. There's also an optional third entry you can put with the product delivery type (shipped, downloaded, not_shipped, flat_rate).</a>
						<?php if (version_compare($foxyshop_settings['version'], '0.7.2', ">=") && $foxyshop_settings['domain']) echo '<button type="button" class="button" id="ajax_get_category_list">Pull Category List From FoxyCart</button><div id="foxyshop_category_list_waiter"></div>'; ?>
					</div>
					<textarea id="foxyshop_ship_categories" name="foxyshop_ship_categories" wrap="auto" style="float: left; width:640px;height: <?php echo strlen($foxyshop_settings['ship_categories']) > 110 ? "160px" : "80px" ?>;"><?php echo $foxyshop_settings['ship_categories']; ?></textarea>
					<span style="display:block; clear: both; padding-top: 3px;"><strong>Syntax:</strong> category_code<strong>|</strong>category_description<strong>|</strong>product_delivery_type</span>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" id="foxyshop_use_cart_validation" value="1" name="foxyshop_use_cart_validation"<?php checked($foxyshop_settings['use_cart_validation']); ?> />
					<label for="foxyshop_use_cart_validation"><?php _e('Use Cart Validation (strongly recommended)', 'foxyshop'); ?></label>
					<a href="#" class="foxyshophelp">Encrypts your forms and add-to-cart links to prevent tampering</a>

					<div style="clear: both;"></div>

					<input type="checkbox" id="foxyshop_ship_to" name="foxyshop_enable_ship_to"<?php checked($foxyshop_settings['enable_ship_to'], "on"); ?> />
					<label for="foxyshop_ship_to"><?php _e('Enable Multi-Ship', 'foxyshop'); ?></label>
					<a href="#" class="foxyshophelp">Remember that FoxyCart charges an extra fee for this service. You must enable it on your FoxyCart account or it will not work. NOTE: At this time, this feature is not available for multi-ship stores.</a>
				</td>
			</tr>
			<tr>
				<td>
					<h3 style="margin: 0;"><?php echo FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('Relation', 'foxyshop'); ?></h3>
					<input type="checkbox" id="foxyshop_related_products_custom" name="foxyshop_related_products_custom"<?php checked($foxyshop_settings['related_products_custom'], "on"); ?> />
					<label for="foxyshop_related_products_custom"><?php echo sprintf(__('Enable Related %s (Custom)', 'foxyshop'),FOXYSHOP_PRODUCT_NAME_PLURAL); ?></label>
					<a href="#" class="foxyshophelp">Allow multiple, specific items to be shown with each product.</a>
					<?php if (version_compare(get_bloginfo("version"), "3.1", ">=")) : ?>
					<div style="clear: both;"></div>
					<input type="checkbox" id="foxyshop_related_products_tags" name="foxyshop_related_products_tags"<?php checked($foxyshop_settings['related_products_tags'], "on"); ?> />
					<label for="foxyshop_related_products_tags"><?php echo sprintf(__('Enable Related %s (Tags)', 'foxyshop'),FOXYSHOP_PRODUCT_NAME_PLURAL); ?></label>
					<a href="#" class="foxyshophelp">Set tags on your products and related products will be automatically determined based on those tags.</a>
					<?php endif; ?>

					<div style="clear: both;"></div>
					<input type="checkbox" id="foxyshop_enable_bundled_products" name="foxyshop_enable_bundled_products"<?php checked($foxyshop_settings['enable_bundled_products'], "on"); ?> />
					<label for="foxyshop_enable_bundled_products"><?php echo __('Enable Bundled', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_PLURAL; ?></label>
					<a href="#" class="foxyshophelp">Allow multiple items to be added to the cart at once (extra items will be added with a price of $0.00, though this can be configured.)</a>

					<div style="clear: both;"></div>
					<input type="checkbox" id="foxyshop_enable_addon_products" name="foxyshop_enable_addon_products"<?php checked($foxyshop_settings['enable_addon_products'], "on"); ?> />
					<label for="foxyshop_enable_addon_products"><?php echo __('Enable Add-On', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_PLURAL; ?></label>
					<a href="#" class="foxyshophelp">Allow other products to appear on a product page with checkbox options.</a>

				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" id="foxyshop_enable_subscriptions" name="foxyshop_enable_subscriptions"<?php checked($foxyshop_settings['enable_subscriptions'], "on"); ?> />
					<label for="foxyshop_enable_subscriptions"><?php _e('Enable Subscriptions', 'foxyshop'); ?></label>
					<a href="#" class="foxyshophelp">Show fields to allow the creation of subscription <?php echo strtolower(FOXYSHOP_PRODUCT_NAME_PLURAL); ?>.</a>
					<div class="settings_indent">
						<input type="checkbox" id="foxyshop_expiring_cards_reminder" name="foxyshop_expiring_cards_reminder"<?php checked($foxyshop_settings['expiring_cards_reminder'], "on"); ?> />
						<label for="foxyshop_expiring_cards_reminder"><?php _e('Send Reminders to Subscription Customers with Expiring Credit Cards', 'foxyshop'); ?></label>
						<a href="#" class="foxyshophelp"><?php _e('This can be configured in your datafeed template file', 'foxyshop'); ?></a>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" id="foxyshop_enable_sso" name="foxyshop_enable_sso"<?php checked($foxyshop_settings['enable_sso'], "on"); ?> />
					<label for="foxyshop_enable_sso"><?php _e('Enable WordPress Single-Sign-On', 'foxyshop'); ?></label>
					<a href="#" class="foxyshophelp">If enabled, your WordPress users will not have to login again to complete a FoxyCart checkout. WordPress accounts and FoxyCart accounts are kept in sync. You must be using FoxyCart 0.7.1 or above and in the FoxyCart admin you must set the "customer password hash type" to "phpass, portable mode" and the hash config to 8. Check the "enable single sign on" option and put the SSO Endpoint url in the appropriate box.</a>
					<div class="settings_indent">

						<label for="foxyshop_sso_account_required"><?php _e('SSO Type', 'foxyshop'); ?>:</label>
						<select name="foxyshop_sso_account_required" id="sort_key">
						<?php
						$sortArray = array(__('WordPress account optional', 'foxyshop'), __('Require WordPress account to check out', 'foxyshop'), __('Account required on product-by-product basis', 'foxyshop'));
						foreach ($sortArray as $key=>$val) {
							echo '<option value="' . $key . '"' . ($foxyshop_settings['sso_account_required'] == $key ? ' selected="selected"' : '') . '>' . $val . '  </option>'."\n";
						} ?>
						</select>
						<div style="clear: both;"></div>
						<input type="checkbox" id="foxyshop_checkout_customer_create" name="foxyshop_checkout_customer_create"<?php checked($foxyshop_settings['checkout_customer_create'], "on"); ?> />
						<label for="foxyshop_checkout_customer_create"><?php _e('Create/Update WordPress User After Checkout', 'foxyshop'); ?></label>
						<a href="#" class="foxyshophelp"><?php _e('The datafeed must be enabled at FoxyCart for this feature to work properly.', 'foxyshop'); ?></a>
					</div>
				</td>
			</tr>


			<tr>
				<td>
					<input type="checkbox" id="foxyshop_manage_inventory_levels" name="foxyshop_manage_inventory_levels"<?php checked($foxyshop_settings['manage_inventory_levels'], "on"); ?> />
					<label for="foxyshop_manage_inventory_levels"><?php _e('Manage Inventory Levels', 'foxyshop'); ?></label>
					<a href="#" class="foxyshophelp">If enabled, you will be able to set inventory levels per <?php echo strtolower(FOXYSHOP_PRODUCT_NAME_PLURAL); ?> code. In the FoxyCart admin, you need to check the box to enable your datafeed and enter the datafeed url from the top of this page in the "datafeed url" box.</a>
					<div class="settings_indent">
						<label for="foxyshop_inventory_alert_level"><?php _e('Default Inventory Alert Level', 'foxyshop'); ?>:</label> <input type="text" id="foxyshop_inventory_alert_level" name="foxyshop_inventory_alert_level" value="<?php echo $foxyshop_settings['inventory_alert_level']; ?>" style="width: 50px;" />
						<input type="checkbox" id="foxyshop_inventory_alert_email" name="foxyshop_inventory_alert_email"<?php checked($foxyshop_settings['inventory_alert_email'], "on"); ?> style="clear: left;" /><label for="foxyshop_inventory_alert_email"><?php _e('Send Email to Admin When Alert Level Reached'); ?></label>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<span style="float: left;font-weight: bold; margin: 2px 10px 0 0;"><?php _e('Default Weight Type', 'foxyshop'); ?>:</span>
					<input type="radio" id="foxyshop_weight_type_english" name="foxyshop_weight_type" value="english"<?php if ($foxyshop_settings['weight_type'] == "english" || $foxyshop_settings['weight_type'] == "") echo ' checked="checked"'; ?> />
					<label for="foxyshop_weight_type_english" style="font-weight: normal;"><?php _e('English', 'foxyshop'); ?></label>
					<input type="radio" id="foxyshop_weight_type_metric" name="foxyshop_weight_type" value="metric" style="margin-left: 20px;"<?php checked($foxyshop_settings['weight_type'], "metric"); ?> />
					<label for="foxyshop_weight_type_metric" style="font-weight: normal;"><?php _e('Metric', 'foxyshop'); ?></label>
				</td>
			</tr>
			<tr>
				<td>
					<label for="foxyshop_default_weight"><?php _e('Default Weight', 'foxyshop'); ?>:</label>
					<?php
					$arrweight = explode(" ",$foxyshop_settings['default_weight']);
					$weight1 = (int)$arrweight[0];
					$weight2 = (count($arrweight) > 1 ? (double)$arrweight[1] : "0.0");
					?>
					<input type="text" id="foxyshop_default_weight1" name="foxyshop_default_weight1" value="<?php echo esc_attr($weight1); ?>" style="width: 46px;" /><small id="weight_title1" style="width: 28px;">lbs</small>
					<input type="text" id="foxyshop_default_weight2" name="foxyshop_default_weight2" value="<?php echo esc_attr($weight2); ?>" style="width: 46px;" /><small id="weight_title2">oz</small>
				</td>
			</tr>
			<tr>
				<td>
					<label for="foxyshop_locale_code"><?php _e('Currency Locale Code', 'foxyshop'); ?>:</label> <input type="text" id="foxyshop_locale_code" name="foxyshop_locale_code" value="<?php echo esc_attr($foxyshop_settings['locale_code']); ?>" style="width: 150px;" />
					<a href="#" class="foxyshophelp"><?php _e('If you would like to use something other than $ for your currency, enter your locale code here. For the British Pound, enter "en_GB".', 'foxyshop'); ?></a>
					<small><a href="http://www.roseindia.net/tutorials/I18N/locales-list.shtml" target="_blank" tabindex="99999">full list of locale codes</a></small>
					<?php if (!function_exists('money_format')) echo '<div style="clear: both; padding-top: 5px;"><em>' . __('Attention: you are using Windows which does not support internationalization. You will be limited to $ (en_US) or &pound; (en_GB).', 'foxyshop') . '</em></div>'; ?>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" id="foxyshop_hide_subcat_children" name="foxyshop_hide_subcat_children"<?php checked($foxyshop_settings['hide_subcat_children'], "on"); ?> />
					<label for="foxyshop_hide_subcat_children"><?php echo sprintf(__('Hide Child %s From Parent Categories (recommended)', 'foxyshop'), esc_html(FOXYSHOP_PRODUCT_NAME_PLURAL)); ?></label>
					<a href="#" class="foxyshophelp"><?php echo sprintf(__('By default, WordPress treats children a little differently than you would expect in that products in child categories also show up in parent categories. FoxyShop removes these products, but if you would like to have all %s from sub-categories show up in parent categories, uncheck this box.', 'foxyshop'), strtolower(FOXYSHOP_PRODUCT_NAME_PLURAL)); ?></a>
				</td>
			</tr>
			<tr>
				<td>
					<label for="foxyshop_ga"><?php _e('Google Analytics Code'); ?>:</label> <input type="text" id="foxyshop_ga" name="foxyshop_ga" value="<?php echo esc_attr($foxyshop_settings['ga']); ?>" size="20" /> <small>(UA-XXXXXXXX-X)</small>
					<a href="#" class="foxyshophelp"><?php _e('Enter your UA code here and Google Analytics tracking will be installed in the footer. Tracking will only be initiated if the visitor is not a logged-in WordPress user so that admin usage won\'t be tracked.', 'foxyshop'); ?></a>
					<div class="settings_indent">
						<input type="checkbox" id="foxyshop_ga_advanced" name="foxyshop_ga_advanced"<?php checked($foxyshop_settings['ga_advanced'], "on"); ?> />
						<label for="foxyshop_ga_advanced"><?php _e('Advanced Google Analytics Code', 'foxyshop'); ?></label>
						<a href="#" class="foxyshophelp"><?php _e('Check this box if you are using the advanced FoxyCart Google Analytics Sync. We will put the appropriate code in your footer.', 'foxyshop'); ?></a>
						<small>Advanced Instructions: <a href="https://wiki.foxycart.com/integration/googleanalytics_async" target="_blank" tabindex="99998">legacy</a> or <a href="https://wiki.foxycart.com/integration/googleanalytics_universal" target="_blank" tabindex="99999">universal</a></small>
						<div style="clear: both;">
						<select name="foxyshop_ga_type" id="ga_type">
							<option value="legacy"<?php if ($foxyshop_settings['ga_type'] == "legacy") echo ' selected="selected"'; ?>>Legacy Analytics</option>
							<option value="universal"<?php if ($foxyshop_settings['ga_type'] == "universal") echo ' selected="selected"'; ?>>Universal Analytics</option>
						</select>

						<label for="foxyshop_ga_demographics" style="margin: 2px 3px 0 9px;"><input type="checkbox" name="foxyshop_ga_demographics" id="foxyshop_ga_demographics" <?php checked($foxyshop_settings['ga_demographics'], "on"); ?>>Include Demographics</label>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" id="foxyshop_google_product_support" name="foxyshop_google_product_support"<?php checked($foxyshop_settings['google_product_support'], "on"); ?> />
					<label for="foxyshop_google_product_support"><?php _e('Enable Google Product Feed Connection', 'foxyshop'); ?></label>
					<a href="#" class="foxyshophelp"><?php _e('If checked, you will be able to create a feed suitable for submitting to Google Product Search.', 'foxyshop'); ?></a>
					<div id="google_merchant_id_holder"<?php if (!$foxyshop_settings['google_product_support']) echo ' style="display:none;"'; ?>>
						<label for="foxyshop_google_product_merchant_id"><?php echo __('Google Merchant Account ID', 'foxyshop'); ?>:</label>
						<input type="text" id="foxyshop_google_product_merchant_id" name="foxyshop_google_product_merchant_id" value="<?php echo esc_attr($foxyshop_settings['google_product_merchant_id']); ?>" />
						<a href="#" class="foxyshophelp"><?php _e('Enter your Google Merchant Account ID found in the Google Merchant Center.', 'foxyshop'); ?></a>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" id="foxyshop_set_orderdesk_url" name="foxyshop_set_orderdesk_url"<?php if ($foxyshop_settings['orderdesk_url']) echo ' checked="checked"'; ?> />
					<label for="foxyshop_set_orderdesk_url"><?php _e('Use FoxyTools Order Desk', 'foxyshop'); ?></label>
					<small>(<a href="http://www.orderdesk.me/" target="_blank"><?php _e("more info", "foxyshop"); ?></a>)</small>
					<div id="orderdesk_url_holder"<?php if (!$foxyshop_settings['orderdesk_url']) echo ' style="display:none;"'; ?>>
						<label for="foxyshop_orderdesk_url"><?php echo __('Your Order Desk Datafeed URL', 'foxyshop'); ?>:</label>
						<input type="text" id="foxyshop_orderdesk_url" name="foxyshop_orderdesk_url" value="<?php echo esc_attr($foxyshop_settings['orderdesk_url']); ?>" style="width: 400px;" />
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" id="foxyshop_enable_dashboard_stats" name="foxyshop_enable_dashboard_stats"<?php checked($foxyshop_settings['enable_dashboard_stats'], "on"); ?> />
					<label for="foxyshop_enable_dashboard_stats"><?php echo __('Show FoxyShop Stats on Dashboard', 'foxyshop'); ?></label>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" id="foxyshop_generate_product_sitemap" name="foxyshop_generate_product_sitemap"<?php checked($foxyshop_settings['generate_product_sitemap'], "on"); ?> />
					<label for="foxyshop_generate_product_sitemap"><?php echo sprintf(__('Generate %s Sitemap', 'foxyshop'), esc_html(FOXYSHOP_PRODUCT_NAME_SINGULAR)); ?></label>
					<a href="#" class="foxyshophelp"><?php echo sprintf(__('If checked, a sitemap file with all of your %s will be created in your root folder.', 'foxyshop'), strtolower(esc_html(FOXYSHOP_PRODUCT_NAME_PLURAL))); ?></a>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" id="foxyshop_show_add_to_cart_link" name="foxyshop_show_add_to_cart_link"<?php checked($foxyshop_settings['show_add_to_cart_link'], "on"); ?> />
					<label for="foxyshop_show_add_to_cart_link"><?php echo __('Show Add to Cart Link on the Product Entry Page.', 'foxyshop'); ?></label>
				</td>
			</tr>
			<?php if (version_compare($foxyshop_settings['version'], '0.7.2', ">=")) : ?>
			<tr>
				<td>
					<input type="checkbox" id="foxyshop_downloadables_sync" name="foxyshop_downloadables_sync"<?php checked($foxyshop_settings['downloadables_sync'], "on"); ?> />
					<label for="foxyshop_downloadables_sync"><?php echo __('Enable FoxyCart Downloadables Sync', 'foxyshop'); ?></label>
					<a href="#" class="foxyshophelp"><?php echo sprintf(__('If checked, you will be able to select from a list of the downloadables loaded at FoxyCart to help set up your new downloadable %s.', 'foxyshop'), strtolower(FOXYSHOP_PRODUCT_NAME_PLURAL)); ?></a>
				</td>
			</tr>
			<?php endif; ?>


		</tbody>
	</table>

	<p><input type="submit" class="button-primary" value="<?php _e('Save All Settings', 'foxyshop'); ?>" /></p>

	<input type="hidden" name="foxyshop_settings_update" value="1" />
	<?php wp_nonce_field('update-foxyshop-options'); ?>
	</form>

<script type="text/javascript">
jQuery(document).ready(function($){
	$("input[name='foxyshop_weight_type']").change(function() {
		if ($("#foxyshop_weight_type_english").is(":checked")) {
			$("#weight_title1").text("lbs");
			$("#weight_title2").text("oz");
		} else {
			$("#weight_title1").text("kg");
			$("#weight_title2").text("gm");
		}
	});

	if ($("#foxyshop_weight_type_english").is(":checked")) {
		$("#weight_title1").text("lbs");
		$("#weight_title2").text("oz");
	} else {
		$("#weight_title1").text("kg");
		$("#weight_title2").text("gm");
	}

	$("#resetimage").click(function() {
		$("#foxyshop_default_image").val("<?php echo FOXYSHOP_DIR."/images/no-photo.png"; ?>");
		return false;
	});
	$("#foxyshop_google_product_support").click(function() {
		if ($(this).is(":checked")) {
			$("#google_merchant_id_holder").show();
		} else {
			$("#google_merchant_id_holder").hide();
		}
	});
	$("#foxyshop_set_orderdesk_url").click(function() {
		if ($(this).is(":checked")) {
			$("#orderdesk_url_holder").show();
			$("#foxyshop_orderdesk_url").select();
		} else {
			$("#orderdesk_url_holder").hide();
		}
	});

	//Tooltip
	xOffset = -10;
	yOffset = 10;
	$("a.foxyshophelp").hover(function(e) {
		var tooltip_text = $(this).html();
		$("body").append("<p id='tooltip'>"+ tooltip_text +"</p>");
		$("#tooltip")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px")
			.fadeIn("fast");
	}, function(){
		$("#tooltip").remove();
	}).mousemove(function(e){
		$("#tooltip")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px");
	}).click(function(e) {
		e.preventDefault();
		return false;
	}).attr("tabindex", "99999");

	$(".foxydomainpicker").click(function(e) {
		$(".foxycartdomain").removeClass("simple advanced");
		$(".foxycartdomain").addClass($(this).attr("rel"));
		$("#foxyshop_domain").focus().select();
		e.preventDefault();
		return false;
	});

	<?php if (version_compare($foxyshop_settings['version'], '0.7.2', ">=") && $foxyshop_settings['domain']) { ?>
	$("#ajax_get_category_list").click(function() {
		var data = {
			action: 'foxyshop_ajax_get_category_list',
			security: '<?php echo wp_create_nonce("foxyshop-ajax-get-category-list"); ?>'
		};
		$("#foxyshop_category_list_waiter").show();
		$.post(ajaxurl, data, function(response) {
			if (response) {
				$("#foxyshop_ship_categories").val(response);
			}
			$("#foxyshop_category_list_waiter").hide();
		});

	});
	<?php } ?>

});
function foxyshop_check_settings_form() {
	return true;
}
</script>
<?php } ?>
