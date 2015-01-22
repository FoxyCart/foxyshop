<?php /*
------------ ATTENTION ------------
If you need to edit this template, do not edit the version in the plugin directory. Place a copy in your template folder and edit it there.
This will allow you to upgrade FoxyShop without breaking your customizations. More details here: http://www.foxy-shop.com/documentation/theme-customization/
-----------------------------------
*/


//Set Globals and Get Settings
global $wpdb, $foxyshop_settings;
require(FOXYSHOP_PATH.'/datafeedfunctions.php');



//EXTERNAL DATAFEEDS
//-----------------------------------------------------
//If you need to use more than one datafeed with FoxyCart, you can enter as many as you like in the $external_datafeeds array
//If you are using more than one integration and one of them fails, the entire process will fail and that failure will be sent to FoxyCart.
//If you have more than one additional datafeed and one is more unreliable than another, put the most unreliable one first so that any failures will result in a full retry from FoxyCart.
//This function will send the WordPress admin (this is filterable) an email if any datafeeds fail.
//There are reports that this won't work on GoDaddy's hosting, which begs the question: why are you hosting on GoDaddy?
$external_datafeeds = array();
foxyshop_run_external_datafeeds($external_datafeeds);



//-----------------------------------------------------
// TRANSACTION DATAFEED
//-----------------------------------------------------
if (isset($_POST["FoxyData"])) {
	if ($_POST['FoxyData'] == "") die('No Content Received From Datafeed');


	//DECRYPT (required)
	//-----------------------------------------------------
	$FoxyData_decrypted = foxyshop_decrypt($_POST["FoxyData"]);
	$xml = simplexml_load_string($FoxyData_decrypted, NULL, LIBXML_NOCDATA);


	//TROUBLESHOOTING
	//-----------------------------------------------------
	//For testing, write datafeed to file in theme folder
	//$file = STYLESHEETPATH.'/datafeed.xml';
	//$fh = fopen($file, 'a') or die("Couldn't open $file for writing!");
	//fwrite($fh, $FoxyData_decrypted);
	//fclose($fh);

	//Uncomment These If You Need Help Troubleshooting
	//error_reporting(E_ALL);
	//ini_set('display_errors','On');

	//SKIP IF HOSTED GATEWAY AND NOT YET APPROVED
	//-----------------------------------------------------
	$status = isset($xml->transactions->transaction->status) ? (string)$xml->transactions->transaction->status : '';
	if ($status == "pending") {
		die("foxy");
	}



	//BUILT-IN FEATURES
	//-----------------------------------------------------

	//Update Inventory
	//Comment Out if Using QB or other External Inventory Management
	if ($foxyshop_settings['manage_inventory_levels']) foxyshop_datafeed_inventory_update($xml);


	//Set Subscription Features If Using SSO
	if ($foxyshop_settings['enable_subscriptions'] && $foxyshop_settings['enable_sso']) foxyshop_datafeed_sso_update($xml);


	//Add/Update WordPress User
	if ($foxyshop_settings['checkout_customer_create']) foxyshop_datafeed_user_update($xml);




	//MANUAL PROCESSES GO HERE
	//-----------------------------------------------------

	//For Each Transaction
	foreach($xml->transactions->transaction as $transaction) {

		//This variable will tell us whether this is a multi-ship store or not
		$is_multiship = 0;

		//Get FoxyCart Transaction Information
		//Simply setting lots of helpful data to PHP variables so you can access it easily
		//If you need to access more variables, you can see some sample XML here: http://wiki.foxycart.com/v/0.7.2/transaction_xml_datafeed
		$transaction_id =		(string)$transaction->id;
		$transaction_date =		(string)$transaction->transaction_date;
		$customer_ip =			(string)$transaction->customer_ip;
		$customer_id =			(string)$transaction->customer_id;
		$customer_first_name =		(string)$transaction->customer_first_name;
		$customer_last_name =		(string)$transaction->customer_last_name;
		$customer_company =		(string)$transaction->customer_company;
		$customer_email =		(string)$transaction->customer_email;
		$customer_password =		(string)$transaction->customer_password;
		$customer_address1 =		(string)$transaction->customer_address1;
		$customer_address2 =		(string)$transaction->customer_address2;
		$customer_city =		(string)$transaction->customer_city;
		$customer_state =		(string)$transaction->customer_state;
		$customer_postal_code =		(string)$transaction->customer_postal_code;
		$customer_country =		(string)$transaction->customer_country;
		$customer_phone =		(string)$transaction->customer_phone;


		//This is for a multi-ship store. The shipping addresses will go in a $shipto array with the address name as the key
		$shipto = array();
		foreach($transaction->shipto_addresses->shipto_address as $shipto_address) {
			$is_multiship = 1;
			$shipto_name = (string)$shipto_address->address_name;
			$shipto[$shipto_name] = array(
				'first_name' => (string)$shipto_address->shipto_first_name,
				'last_name' => (string)$shipto_address->shipto_last_name,
				'company' => (string)$shipto_address->shipto_company,
				'address1' => (string)$shipto_address->shipto_address1,
				'address2' => (string)$shipto_address->shipto_address2,
				'city' => (string)$shipto_address->shipto_city,
				'state' => (string)$shipto_address->shipto_state,
				'postal_code' => (string)$shipto_address->shipto_postal_code,
				'country' => (string)$shipto_address->shipto_country,
				'shipping_service_description' => (string)$shipto_address->shipto_shipping_service_description,
				'subtotal' => (string)$shipto_address->shipto_subtotal,
				'tax_total' => (string)$shipto_address->shipto_tax_total,
				'shipping_total' => (string)$shipto_address->shipto_shipping_total,
				'total' => (string)$shipto_address->shipto_,
				'custom_fields' => array()
			);

			//Putting the Custom Fields in an array if they are there
			if (!empty($shipto_address->custom_fields)) {
				foreach($shipto_address->custom_fields->custom_field as $custom_field) {
					$shipto[$shipto_name]['custom_fields'][(string)$custom_field->custom_field_name] = (string)$custom_field->custom_field_value;
				}
			}
		}

		//This is setup for a single ship store
		if (!$is_multiship) {
			$shipping_first_name =	(string)$transaction->shipping_first_name ? (string)$transaction->shipping_first_name : $customer_first_name;
			$shipping_last_name =	(string)$transaction->shipping_last_name ? (string)$transaction->shipping_last_name : $customer_last_name;
			$shipping_company =	(string)$transaction->shipping_company ? (string)$transaction->shipping_company : $customer_company;
			$shipping_address1 =	(string)$transaction->shipping_address1 ? (string)$transaction->shipping_address1 : $customer_address1;
			$shipping_address2 =	(string)$transaction->shipping_address1 ? (string)$transaction->shipping_address2 : $customer_address2; //shipping_address1 is intended here
			$shipping_city =	(string)$transaction->shipping_city ? (string)$transaction->shipping_city : $customer_city;
			$shipping_state =	(string)$transaction->shipping_state ? (string)$transaction->shipping_state : $customer_state;
			$shipping_postal_code =	(string)$transaction->shipping_postal_code ? (string)$transaction->shipping_postal_code : $customer_postal_code;
			$shipping_country =	(string)$transaction->shipping_country ? (string)$transaction->shipping_country : $customer_country;
			$shipping_phone =	(string)$transaction->shipping_phone ? (string)$transaction->shipping_phone : $customer_phone;
			$shipto_shipping_service_description = (string)$transaction->shipto_shipping_service_description;
		}

		//Putting the Custom Fields in an array if they are there. These are on the top level and could be there for both single ship and multiship stores
		$custom_fields = array();
		if (!empty($transaction->custom_fields)) {
			foreach($transaction->custom_fields->custom_field as $custom_field) {
				$custom_fields[(string)$custom_field->custom_field_name] = (string)$custom_field->custom_field_value;
			}
		}






		//For Each Transaction Detail
		foreach($transaction->transaction_details->transaction_detail as $transaction_detail) {
			$product_name = (string)$transaction_detail->product_name;
			$product_code = (string)$transaction_detail->product_code;
			$product_quantity = (int)$transaction_detail->product_quantity;
			$product_price = (double)$transaction_detail->product_price;
			$product_shipto = (string)$transaction_detail->shipto;
			$category_code = (string)$transaction_detail->category_code;
			$product_delivery_type = (string)$transaction_detail->product_delivery_type;
			$sub_token_url = (string)$transaction_detail->sub_token_url;
			$subscription_frequency = (string)$transaction_detail->subscription_frequency;
			$subscription_startdate = (string)$transaction_detail->subscription_startdate;
			$subscription_nextdate = (string)$transaction_detail->subscription_nextdate;
			$subscription_enddate = (string)$transaction_detail->subscription_enddate;

			//These are the options for the product
			$transaction_detail_options = array();
			foreach($transaction_detail->transaction_detail_options->transaction_detail_option as $transaction_detail_option) {
				$product_option_name = (string)$transaction_detail_option->product_option_name;
				$product_option_value = (string)$transaction_detail_option->product_option_value;
				$price_mod = (double)$transaction_detail_option->price_mod;
				$weight_mod = (double)$transaction_detail_option->weight_mod;
				$transaction_detail_options[$product_name] = $product_option_value;


			}





			//If you have custom code to run for each product, put it here:












			//Runs a WordPress action for each transaction detail if you prefer to customize that way
			do_action('foxyshop_datafeed_transaction_detail', $transaction, $transaction_detail);

		}

		//If you have custom code to run for each order, put it here:










		//Runs a WordPress action at the end if you prefer to customize that way
		do_action('foxyshop_datafeed_order', $transaction);
	}

	//All Done!
	die("foxy");








//-----------------------------------------------------
// SUBSCRIPTION DATAFEED
//-----------------------------------------------------
} elseif (isset($_POST["FoxySubscriptionData"])) {

	if ($_POST['FoxySubscriptionData'] == "") die('No Content Received From Subscription Datafeed');

	//If you don't want to use any of the subscription datafeed reminder features, uncomment the next line
	//die("Not In Use");

	//Setup Defaults
	$failed_days_before_cancel = 7;
	$billing_reminder_frequency_in_days = 3;
	$update_payment_method_reminder_days_of_month = array(1, 15, 28);

	//Decrypt
	$FoxyData_decrypted = foxyshop_decrypt($_POST["FoxySubscriptionData"]);
	$xml = simplexml_load_string($FoxyData_decrypted, NULL, LIBXML_NOCDATA);


	//For Each Subscription
	foreach($xml->subscriptions->subscription AS $subscription) {

		//Get Variables
		$customer_id = (string)$subscription->customer_id;
		$customer_first_name = (string)$subscription->customer_first_name;
		$customer_last_name = (string)$subscription->customer_last_name;
		$customer_email = (string)$subscription->customer_email;
		$transaction_date = (string)$subscription->transaction_date;
		$sub_token_url = (string)$subscription->sub_token_url;
		$past_due_amount = (double)$subscription->past_due_amount;
		$end_date = (string)$subscription->end_date;

		//Get Product Code
		foreach($subscription->transaction_details->transaction_detail AS $transaction_detail) {
			$product_code = (string)$transaction_detail->product_code;
		}

		$canceled = 0;
		$sendReminder = 0;

		//This Entry Was Canceled Today
		if (date("Y-m-d",strtotime("now")) == date("Y-m-d", strtotime($end_date))) {
			$canceled = 1;
		}
		if (!$canceled && $past_due_amount > 0) {
			$failedDays = floor((strtotime("now") - strtotime($transaction_date)) / (60 * 60 * 24));
			if ($failedDays > $failed_days_before_cancel) {
				$canceled = 1;
			} else {
				if (($failedDays % $billing_reminder_frequency_in_days) == 0) {
					$sendReminder = 1;
				}
			}
		}

		//Set Subscription Inactive
		if ($canceled) {

			//Get WordPress User ID
			$user_id = $wpdb->get_var("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'foxycart_customer_id' AND meta_value = '$customer_id'");
			if ($user_id) {

				//Get User's Subscription Array
				$foxyshop_subscription = get_user_meta($user_id, 'foxyshop_subscription', true);
				if (!is_array($foxyshop_subscription)) $foxyshop_subscription = array();

				//Set To NON-ACTIVE
				$foxyshop_subscription[$product_code] = array(
					"is_active" => 0,
					"sub_token_url" => $sub_token_url
				);

				//Write Serialized Array Back to DB
				update_user_meta($user_id, 'foxyshop_subscription', $foxyshop_subscription);
			}


		}

		//Send reminder email
		if ($sendReminder && $foxyshop_settings['expiring_cards_reminder']) {
			$subject_line = "Please Update Payment Information";
			$to_email = $customer_email;
			$message = "Dear $customer_first_name,\n\n";
			$message .= "This is a reminder that your recent subscription payment failed. Please click the link below to update the credit card you have on file with us. Thank you!\n\n";
			$message .= $sub_token_url . "&empty=true&cart=checkout\n\n";
			$message .= "";
			$headers = 'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>' . "\r\n";
			//$headers .= 'Bcc: ' . get_bloginfo('admin_email') . "\r\n";
			wp_mail($to_email, $subject_line, $message, $headers);
		}
	}

	// send emails to customers with soon to expire credit cards. Ignore already expired cards, since they should have already been
	// sent an email when their payment failed.
	if (in_array(date("j"),$update_payment_method_reminder_days_of_month) && $foxyshop_settings['expiring_cards_reminder']) {
		foreach($xml->payment_methods_soon_to_expire->customer AS $customer) {

			$customer_id = (string)$customer->customer_id;
			$customer_first_name = (string)$customer->customer_first_name;
			$customer_last_name = (string)$customer->customer_last_name;
			$customer_email = (string)$customer->customer_email;
			$cc_exp_month = (int)$customer->cc_exp_month;
			$cc_exp_year = (int)$customer->cc_exp_year;
			if (strtolower($customer_first_name) === $customer_first_name) $customer_first_name = ucwords($customer_first_name);
			if (strtolower($customer_last_name) === $customer_last_name) $customer_last_name = ucwords($customer_last_name);


			if (mktime(0,0,0,$cc_exp_month+1, 1, $cc_exp_year) > strtotime("now")) {
				$subject_line = "Reminder to Update Your Credit Card";
				$to_email = $customer_email;
				$message = "Dear $customer_first_name,\n\n";
				$message .= "This is a reminder that the credit card you have on file with us is about to expire. Please login to your account by clicking the link below to update your card. Thank you!\n\n";
				$message .= "https://" . $foxyshop_settings['domain'] . "/cart?empty=true&cart=updateinfo&customer_email=" . urlencode($customer_email) . "\n\n";
				$message .= "";
				$headers = 'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>' . "\r\n";
				//$headers .= 'Bcc: ' . get_bloginfo('admin_email') . "\r\n";
				wp_mail($to_email, $subject_line, $message, $headers);
			}
		}
	}

	//All Done!
	die("foxysub");





//-----------------------------------------------------
// CONSOLIBYTE QUICKBOOKS UPDATE
//-----------------------------------------------------
} elseif (isset($_POST["FoxyInventory"])) {

	foxyshop_consolibyte_inventory_process();




//-----------------------------------------------------
// NO POST CONTENT SENT
//-----------------------------------------------------
} else {
	die('No Content Received From Datafeed');
}
