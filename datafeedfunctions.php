<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//Decrypt Data From Source
function foxyshop_decrypt($src) {
	global $foxyshop_settings;
	return rc4crypt::decrypt($foxyshop_settings['api_key'],urldecode($src));
}


//Push Feed to External Datafeeds
function foxyshop_run_external_datafeeds($external_datafeeds) {
	global $foxyshop_settings;
	if ($foxyshop_settings["orderdesk_url"]) {
		//Check Referer to make sure we aren't coming from Order Desk and thus creating a loop
		if ($_SERVER['REMOTE_ADDR'] == "216.70.96.51") {
			die("It looks like you have a potential datafeed loop with FoxyShop and Order Desk. You can't send the datafeed to Order Desk and then have Order Desk send it back to FoxyShop or you'll have a never-ending loop. It's recommended that you uncheck the 'Send to Order Desk' feature in your FoxyShop Settings.");
		}
		$external_datafeeds[] = $foxyshop_settings["orderdesk_url"];
	}
	if (!defined('FOXYSHOP_CURL_CONNECTTIMEOUT')) define('FOXYSHOP_CURL_CONNECTTIMEOUT', 10); //10
	if (!defined('FOXYSHOP_CURL_TIMEOUT')) define('FOXYSHOP_CURL_TIMEOUT', 15); //15
	if (!isset($_POST["FoxyData"]) && !isset($_POST["FoxySubscriptionData"])) return;

	foreach($external_datafeeds as $feedurl) {
		if ($feedurl) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $feedurl);
			if (isset($_POST["FoxyData"])) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, array("FoxyData" => $_POST["FoxyData"]));
			} elseif (isset($_POST["FoxySubscriptionData"])) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, array("FoxySubscriptionData" => $_POST["FoxySubscriptionData"]));
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, FOXYSHOP_CURL_CONNECTTIMEOUT);
			curl_setopt($ch, CURLOPT_TIMEOUT, FOXYSHOP_CURL_TIMEOUT);
			if (defined('FOXYSHOP_CURL_SSL_VERIFYPEER')) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FOXYSHOP_CURL_SSL_VERIFYPEER);
			$response = trim(curl_exec($ch));

			//If Error, Send Email and Kill Process
			if ($response != 'foxy' && $response != 'foxysub') {
				$error_msg = (!$response ? "Datafeed Processing Error: " . curl_error($ch) : $response);
				$to_email = get_bloginfo('admin_email');
				$message = "A FoxyCart datafeed error was encountered at " . date("F j, Y, g:i a") . ".\n\n";
				$message .= "The feed that failed was $feedurl\n\n";
				$message .= "The error is listed below:\n\n";
				$message .= $error_msg;
				//$message .= "\n\n" . print_r($_POST, 1);
				//$message .= "\n\n" . print_r($_SERVER, 1);
				$message .= "\n\n" . foxyshop_decrypt($_POST["FoxyData"]);
				$headers = 'From: ' . get_bloginfo('name') . ' Server Admin <' . $to_email . '>' . "\r\n";
				mail($to_email, 'Data Feed Error on ' . get_bloginfo('name'), $message, $headers);
				curl_close($ch);
				die($error_msg);
			} else {
				curl_close($ch);
			}
		}
	}
}



//Update the FoxyShop Inventory
function foxyshop_datafeed_inventory_update($xml) {
	global $wpdb, $foxyshop_settings;

	//For Each Transaction
	foreach($xml->transactions->transaction as $transaction) {

		//For Each Transaction Detail
		foreach($transaction->transaction_details->transaction_detail as $transactiondetails) {
			if ((int)$transactiondetails->is_future_line_item == 1) continue;
			$product_name = (string)$transactiondetails->product_name;
			$product_code = (string)$transactiondetails->product_code;
			$product_quantity = (int)$transactiondetails->product_quantity;

			//Skip if there's no product code
			if (!$product_code) continue;

			//Get List of Target ID's for Inventory Update
			$meta_list = $wpdb->get_results("SELECT post_id, meta_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_inventory_levels' AND meta_value LIKE '%" . esc_sql($product_code) . "%'");
			foreach ($meta_list as $meta) {
				$productID = $meta->post_id;
				$val = unserialize($meta->meta_value);
				if (!is_array($val)) $val = array();
				foreach ($val as $ivcode => $iv) {
					if ($ivcode == $product_code) {
						$original_count = $iv['count'];
						$new_count = $original_count - $product_quantity;
						$alert_level = ($iv['alert'] == '' ? $foxyshop_settings['inventory_alert_level'] : $iv['alert']);
						$val[$ivcode]['count'] = $new_count;

						//Send Email Alert Email
						if ($foxyshop_settings['inventory_alert_email'] && $new_count <= $alert_level) {
							$subject_line = "Inventory Alert: " . $product_name;
							$to_email = apply_filters('foxyshop_inventory_alert_email', get_bloginfo('admin_email'));
							$message = "The inventory for one of your products is getting low:\n\n";
							$message .= "Product Name: $product_name\n";
							$message .= "Product Code: $product_code\n";
							$message .= "Current Inventory Level: $new_count\n";
							$message .= "Inventory Alert Level: $alert_level\n";
							$message .= "\n". get_bloginfo('wpurl') . "/wp-admin/edit.php?post_type=foxyshop_product\n";
							$headers = 'From: ' . get_bloginfo('name') . ' <' . $to_email . '>' . "\r\n";
							wp_mail($to_email, $subject_line, $message, $headers);
						}

					}
				}
				//Run the Update
				foxyshop_inventory_count_update($product_code, $new_count, $productID, 0);
			}
		}
	}
}


//Update the WordPress Customer's Subscription List
function foxyshop_datafeed_sso_update($xml) {
	global $wpdb;

	//For Each Transaction
	foreach($xml->transactions->transaction as $transaction) {

		//Get FoxyCart Transaction Information
		$transaction_id = (string)$transaction->id;
		$customer_id = (string)$transaction->customer_id;

		//For Each Transaction Detail
		foreach($transaction->transaction_details->transaction_detail as $transactiondetails) {
			$product_code = (string)$transactiondetails->product_code;
			$sub_token_url = (string)$transactiondetails->sub_token_url;

			//Set Subscription Features if using SSO
			if ($sub_token_url != "") {

				//Get WordPress User ID
				$select_user = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'foxycart_customer_id' AND meta_value = '$customer_id'";
				$user_id = $wpdb->get_var($select_user);
				if ($user_id) {

					//Get User's Subscription Array
					$foxyshop_subscription = get_user_meta($user_id, 'foxyshop_subscription', true);
					if (!is_array($foxyshop_subscription)) $foxyshop_subscription = array();

					//Add On To Array
					$foxyshop_subscription[$product_code] = array(
						"is_active" => 1,
						"sub_token_url" => $sub_token_url
					);

					//Write Array Back to DB
					update_user_meta($user_id, 'foxyshop_subscription', $foxyshop_subscription);
				}


			}
		}
	}
}

//Update or Create a WordPress User After Checkout
function foxyshop_datafeed_user_update($xml) {
	global $wpdb, $foxyshop_new_password_hash;

	//For Each Transaction
	foreach($xml->transactions->transaction as $transaction) {

		//Get FoxyCart Transaction Information
		$customer_id = (string)$transaction->customer_id;
		$customer_first_name = (string)$transaction->customer_first_name;
		$customer_last_name = (string)$transaction->customer_last_name;
		$customer_email = (string)$transaction->customer_email;
		$customer_password = (string)$transaction->customer_password;

		//Add or Update WordPress User If Not Guest Checkout
		if ($customer_id != '0') {

			//Check To See if WordPress User Already Exists
			$current_user = get_user_by("email", $customer_email);
			$foxyshop_new_password_hash = $customer_password;

			//No Return, Add New User, Username will be email address
			if (!$current_user) {
				remove_action('user_register', 'foxyshop_profile_add', 5);
				$new_user_id = wp_insert_user(array(
					'user_login' => $customer_email,
					'user_email' => $customer_email,
					'first_name' => $customer_first_name,
					'last_name' => $customer_last_name,
					'user_email' => $customer_email,
					'user_pass' => wp_generate_password(),
					'user_nicename' => $customer_first_name . ' ' . $customer_last_name,
					'display_name' => $customer_first_name . ' ' . $customer_last_name,
					'nickname' => $customer_first_name . ' ' . $customer_last_name,
					'role' => apply_filters('foxyshop_default_user_role', 'subscriber'),
				));
				add_user_meta($new_user_id, 'foxycart_customer_id', $customer_id, true);

				//Set Password In WordPress Database
				$wpdb->query("UPDATE $wpdb->users SET user_pass = '" . esc_sql($customer_password) . "' WHERE ID = $new_user_id");

				//Set Original Password at FoxyCart
				//foxyshop_get_foxycart_data(array("api_action" => "customer_save", "customer_id" => $customer_id, "customer_password_hash" => $customer_password));

				//Run Your Custom Actions Here with add_action()
				do_action("foxyshop_datafeed_add_wp_user", $xml, $new_user_id);

			//Update User
			} else {

				//Set Password
				$wpdb->query("UPDATE $wpdb->users SET user_pass = '" . esc_sql($customer_password) . "' WHERE ID = " . $current_user->ID);

				//Update First Name and Last Name
				$updated_user_id = wp_update_user(array(
					'ID' => $current_user->ID,
					'first_name' => $customer_first_name,
					'last_name' => $customer_last_name
				));

				//Reset Password Again
				$wpdb->query("UPDATE $wpdb->users SET user_pass = '" . esc_sql($customer_password) . "' WHERE ID = " . $current_user->ID);

				//Add FoxyCart User ID if not added before
				add_user_meta($current_user->ID, 'foxycart_customer_id', $customer_id, true);

				//Run Your Custom Actions Here with add_action()
				do_action("foxyshop_datafeed_update_wp_user", $xml, $current_user->ID);
			}
		}
	}
}



//ConsoliBYTE Inventory Processor
function foxyshop_consolibyte_inventory_process() {

	//DECRYPT (required)
	//-----------------------------------------------------
	$FoxyData_decrypted = foxyshop_decrypt($_POST["FoxyInventory"]);
	$xml = simplexml_load_string($FoxyData_decrypted, NULL, LIBXML_NOCDATA);

	//For Each Item
	foreach($xml->foxyinventory->item as $item) {

		//Set Variables
		$product_code = (string)$item->product_code;
		$quantity_on_hand = (int)$item->quantity_on_hand;

		//Update Inventory
		foxyshop_inventory_count_update($product_code, $quantity_on_hand, 0);
	}


	//All Done!
	die("foxyinventory");
}



// ======================================================================================
// RC4 ENCRYPTION CLASS
// Do not modify.
// ======================================================================================
/**
 * RC4Crypt 3.2
 *
 * RC4Crypt is a petite library that allows you to use RC4
 * encryption easily in PHP. It's OO and can produce outputs
 * in binary and hex.
 *
 * (C) Copyright 2006 Mukul Sabharwal [http://mjsabby.com]
 *     All Rights Reserved
 *
 * @link http://rc4crypt.devhome.org
 * @author Mukul Sabharwal <mjsabby@gmail.com>
 * @version $Id: class.rc4crypt.php,v 3.2 2006/03/10 05:47:24 mukul Exp $
 * @copyright Copyright &copy; 2006 Mukul Sabharwal
 * @license http://www.gnu.org/copyleft/gpl.html
 * @package RC4Crypt
 */
class rc4crypt {
	/**
	 * The symmetric encryption function
	 *
	 * @param string $pwd Key to encrypt with (can be binary of hex)
	 * @param string $data Content to be encrypted
	 * @param bool $ispwdHex Key passed is in hexadecimal or not
	 * @access public
	 * @return string
	 */
	function encrypt ($pwd, $data, $ispwdHex = 0) {
		if ($ispwdHex) $pwd = @pack('H*', $pwd); // valid input, please!
 		$key[] = '';
		$box[] = '';
		$cipher = '';
		$pwd_length = strlen($pwd);
		$data_length = strlen($data);
		for ($i = 0; $i < 256; $i++) {
			$key[$i] = ord($pwd[$i % $pwd_length]);
			$box[$i] = $i;
		}
		for ($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $key[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
		for ($a = $j = $i = 0; $i < $data_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$k = $box[(($box[$a] + $box[$j]) % 256)];
			$cipher .= chr(ord($data[$i]) ^ $k);
		}
		return $cipher;
	}
	/**
	 * Decryption, recall encryption
	 *
	 * @param string $pwd Key to decrypt with (can be binary of hex)
	 * @param string $data Content to be decrypted
	 * @param bool $ispwdHex Key passed is in hexadecimal or not
	 * @access public
	 * @return string
	 */
	function decrypt ($pwd, $data, $ispwdHex = 0) {
		return rc4crypt::encrypt($pwd, $data, $ispwdHex);
	}
}
