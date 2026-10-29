<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//SSO ENDPOINT TEMPLATE
global $foxyshop_settings;
if (empty($foxyshop_settings['enable_sso'])) {
	wp_redirect(get_home_url());
	die;
}
if (isset($_GET['fcsid']) && isset($_GET['timestamp'])) {
	global $foxyshop_settings;
	global $current_user;

	//Run an action here in case you want to to intercept (only if there's no special checkout type)
	if (!isset($_GET['checkout_type'])) {
		do_action("foxyshop_sso_endpoint");
	}

	$login_url = get_bloginfo('wpurl') . '/wp-login.php';

	//If you don't want to redirect to the wp-login screen for your login/create account page, define this constant in your wp-config.php file.
	if (defined('FOXYSHOP_SSO_REDIRECT_URL')) $login_url = FOXYSHOP_SSO_REDIRECT_URL;

	if(!is_user_logged_in()) {

		//Force a Straight Redirect
		if ($foxyshop_settings['sso_account_required'] == 1) {
			$redirect_to = get_bloginfo('url') . '/foxycart-sso-' . $foxyshop_settings['datafeed_url_key'] . '/?timestamp=' . sanitize_text_field($_GET['timestamp']) . '&fcsid=' . sanitize_text_field($_GET['fcsid']);
			header('Location: ' . $login_url . '?redirect_to=' . urlencode($redirect_to) . '&foxycart_checkout=1&reauth=1');
			die;

		//Check Cart Contents to Decide on Redirect
		} elseif ($foxyshop_settings['sso_account_required'] == 2 && !isset($_GET['checkout_type'])) {

			if (!defined('FOXYSHOP_CURL_CONNECTTIMEOUT')) define('FOXYSHOP_CURL_CONNECTTIMEOUT', 10);
			if (!defined('FOXYSHOP_CURL_TIMEOUT')) define('FOXYSHOP_CURL_TIMEOUT', 15);

			$args = array(
				"timeout" => !defined('FOXYSHOP_CURL_TIMEOUT') ? 15 : FOXYSHOP_CURL_TIMEOUT,
				"method" => "POST",
				"sslverify" => defined('FOXYSHOP_CURL_SSL_VERIFYPEER') ? FOXYSHOP_CURL_SSL_VERIFYPEER : 1,
				"body" => $foxyData,
			);
			$response = wp_remote_get("https://" . $foxyshop_settings['domain'] . "/cart?fcsid=" . sanitize_text_field($_GET['fcsid']) . "&output=json", $args);

			//WP Error
			if (is_wp_error($response)) {
				die();
			}

			$curlout = trim($response['body']);
			$sso_required = 0;
			if ($curlout) {
				$response = json_decode($curlout, true);
				$item_list = isset($response['products']) ? $response['products'] : $response['items'];
				foreach($item_list as $product){
					$code = $product['code'];
					$product_name = $product['name'];
					$product_id = 0;

					//Skip This if Login Already True
					if (!$sso_required) {
						//Lookup Product Code
						$product_check = get_posts('post_type=foxyshop_product&meta_key=_code&meta_value=' . $code);
						if ($product_check) {
							foreach($product_check as $check1) {
								$product_id = $check1->ID;
							}
						//If Not Found, Lookup ID
						} else {
							$product_check = get_posts('post_type=foxyshop_product&page_id=' . (int)$code);
							if ($product_check) {
								foreach($product_check as $check1) {
									if ($check1->ID == (int)$code) $product_id = $check1->ID;
								}
							}
						}

						if ($product_id > 0) {
							if (get_post_meta($product_id,'_require_sso', true) == "on") $sso_required = 1;
						}
					}
				}
			}

			//Do the Signup Redirect
			if ($sso_required) {
				$redirect_to = get_bloginfo('url') . '/foxycart-sso-' . $foxyshop_settings['datafeed_url_key'] . '/?timestamp=' . sanitize_text_field($_GET['timestamp']) . '&fcsid=' . sanitize_text_field($_GET['fcsid']);
				header('Location: ' . $login_url . '?redirect_to=' . urlencode($redirect_to) . '&foxycart_checkout=1&reauth=1');
				die;

			//No Redirect Required
			} else {
				$customer_id = 0;
			}

		//No Redirect Required
		} else {
			$customer_id = 0;
		}

	//Already Logged In, Get Account Info.
	} else {
		wp_get_current_user();

		//Staff Accounts Check Out as Guests; They're Never Linked to FoxyCart Customers
		if (foxyshop_is_protected_user($current_user->ID)) {
			$customer_id = 0;
		} else {
			$customer_id = get_user_meta($current_user->ID, "foxycart_customer_id", TRUE);
			$customer_email = $current_user->user_email;

			//Get FoxyCart Customer ID
			if (!$customer_id) {
				$found_customer_id = foxyshop_find_customer_id_by_email($customer_email);
				if ($found_customer_id) {

					//WordPress Email Addresses Aren't Verified, So an Existing FoxyCart Customer Isn't Linked Automatically (Check Out as a Guest)
					if (apply_filters('foxyshop_sso_claim_existing_customer', false, $current_user, $found_customer_id)) {
						$customer_id = foxyshop_check_for_customer_id($customer_email, $found_customer_id);
					}

				//Customer ID Doesn't Exist, Create New FoxyCart Customer
				} elseif ($found_customer_id === '') {
					$customer_id = foxyshop_add_new_customer_id($customer_email, $current_user->user_pass, $current_user->user_firstname, $current_user->user_lastname);
				}
			}
			if (!$customer_id) $customer_id = 0;
		}
	}


	//Redirect to FoxyCart
	$fcsid = sanitize_text_field($_GET['fcsid']);
	$timestamp = sanitize_text_field($_GET['timestamp']);
	$newtimestamp = time() + 3600;
	$auth_token = sha1($customer_id . '|' . $newtimestamp . '|' . $foxyshop_settings['api_key']);
	$redirect_complete = 'https://' . $foxyshop_settings['domain'] . '/checkout?fc_auth_token=' . $auth_token . '&fc_customer_id=' . $customer_id . '&timestamp=' . $newtimestamp . '&fcsid=' . $fcsid;

	wp_redirect($redirect_complete, 302);
	die;
}

//Link the Current User to the FoxyCart Customer With Their Email and Sync Their Password
function foxyshop_check_for_customer_id($email, $foxycart_customer_id = null) {
	global $current_user;
	wp_get_current_user();
	if (foxyshop_is_protected_user($current_user->ID)) return false;
	if ($foxycart_customer_id === null) $foxycart_customer_id = foxyshop_find_customer_id_by_email($email);
	if (!$foxycart_customer_id || foxyshop_customer_id_is_linked($foxycart_customer_id, $current_user->ID)) return false;
	$foxy_response = foxyshop_get_foxycart_data(array("api_action" => "customer_save", "customer_id" => $foxycart_customer_id, "customer_password_hash" => $current_user->user_pass));
	$xml = foxyshop_load_xml($foxy_response);
	if (!$xml || (string)$xml->result != "SUCCESS") return false;
	add_user_meta($current_user->ID, 'foxycart_customer_id', $foxycart_customer_id, true);
	return $foxycart_customer_id;
}

function foxyshop_add_new_customer_id($email, $pass, $first_name, $last_name) {
	global $current_user;
	wp_get_current_user();
	$foxy_data = array("api_action" => "customer_save", "customer_email" => $email, "customer_password_hash" => $pass);
	if ($first_name != '') $foxy_data['customer_first_name'] = $first_name;
	if ($last_name != '') $foxy_data['customer_last_name'] = $last_name;
	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml = foxyshop_load_xml($foxy_response);
	$foxycart_customer_id = $xml && (string)$xml->result == "SUCCESS" ? (string)$xml->customer_id : "";
	if (!$foxycart_customer_id || foxyshop_customer_id_is_linked($foxycart_customer_id, $current_user->ID)) return "";
	add_user_meta($current_user->ID, 'foxycart_customer_id', $foxycart_customer_id, true);
	return $foxycart_customer_id;
}
