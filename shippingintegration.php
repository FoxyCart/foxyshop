<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//UPS WorldShip XML Integration
function foxyshop_ups_export() {
	global $foxyshop_settings;

	//Setup Defaults
	$description_of_goods = "Retail Goods";
	$billing_option = "PP";
	$package_type = "CP"; //CP = Customer Packaging
	$default_service_type = "GND";
	$default_residential_indicator = ""; //1 = res, 0 = biz. Leave Blank to key off company field
	if (defined('FOXYSHOP_UPS_RESIDENTIAL_INDICATOR')) $default_residential_indicator = FOXYSHOP_UPS_RESIDENTIAL_INDICATOR;

	$services_types = array(
		"UPS Express Plus or Worldwide Express Plus" => "EP",
		"UPS Express or Worldwide Express" => "ES",
		"UPS Express Saver or Worldwide Saver" => "1DP",
		"UPS Expedited or Worldwide Expedited" => "EX",
		"UPS Standard" => "ST",
		"UPS 3 Day Select to the United States" => "3DS",
		"UPS Express (NA1)" => "ND",
		"UPS Next Day Air Early AM" => "1DM",
		"UPS Next Day Air" => "1DA",
		"UPS Next Day Air Saver" => "1DP",
		"UPS 2nd Day Air AM" => "2DM",
		"UPS 2nd Day Air" => "2DA",
		"UPS 3 Day Select" => "3DS",
		"UPS Ground" => "GND",
		"UPS Worldwide Express Plus" => "ES",
		"UPS Worldwide Express" => "SV",
		"UPS Worldwide Saver (Express)" => "SV",
		"UPS Worldwide Expedited" => "EX"
	);

	//Setup Fields and Defaults
	$foxy_data_defaults = array(
		"is_test_filter" => "0",
		"hide_transaction_filter" => "0",
		"data_is_fed_filter" => "",
		"id_filter" => "",
		"order_total_filter" => "",
		"coupon_code_filter" => "",
		"transaction_date_filter_begin" => date("Y-m-d", strtotime("-10 days")),
		"transaction_date_filter_end" => date("Y-m-d"),
		"customer_id_filter" => "",
		"customer_email_filter" => "",
		"customer_first_name_filter" => "",
		"customer_last_name_filter" => "",
		"customer_state_filter" => "",
		"shipping_state_filter" => "",
		"customer_ip_filter" => "",
		"product_code_filter" => "",
		"product_name_filter" => "",
		"product_option_name_filter" => "",
		"product_option_value_filter" => ""
	);
	$foxy_data = wp_parse_args(array("api_action" => "transaction_list"), $foxy_data_defaults);

	if (isset($_GET['foxyshop_search'])) {
		$fields = array("is_test_filter", "hide_transaction_filter", "data_is_fed_filter", "id_filter", "order_total_filter", "coupon_code_filter", "transaction_date_filter_begin", "transaction_date_filter_end", "customer_id_filter", "customer_email_filter", "customer_first_name_filter", "customer_last_name_filter","customer_state_filter", "shipping_state_filter", "customer_ip_filter", "product_code_filter", "product_name_filter", "product_option_name_filter", "product_option_value_filter");
		foreach ($fields as $field) {
			if (isset($_GET[$field])) {
				$foxy_data[$field] = $_GET[$field];
			}
		}
		$foxy_data['pagination_start'] = (isset($_GET['pagination_start']) ? $_GET['pagination_start'] : 0);
		if ($foxyshop_settings['version'] != "0.7.0") $foxy_data['entries_per_page'] = FOXYSHOP_API_ENTRIES_PER_PAGE;
	}

	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml_return = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
	if ((string)$xml_return->result == "ERROR") {
		echo '<h3>' . $xml_return->messages->message . '</h3>';
		die;
	}

	// Define the path to file
	$filename = 'ups-worldship-'.Date('d-m-Y').'.xml';

	// Set headers
	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
	header("Content-Type: text/xml");
	header("Content-Transfer-Encoding: binary");

	$xml = '<?xml version="1.0" encoding="windows-1252"?>'."\n";
	$xml .= '<OpenShipments xmlns="x-schema:OpenShipments.xdr">'."\n";

	foreach($xml_return->transactions->transaction as $transaction) {

		$shipping_first_name = ((string)$transaction->shipping_first_name != "" ? (string)$transaction->shipping_first_name : (string)$transaction->customer_first_name);
		$shipping_last_name = ((string)$transaction->shipping_last_name != "" ? (string)$transaction->shipping_last_name : (string)$transaction->customer_last_name);
		$shipping_company = ((string)$transaction->shipping_company != "" ? (string)$transaction->shipping_company : (string)$transaction->customer_company);
		$shipping_address1 = ((string)$transaction->shipping_address1 != "" ? (string)$transaction->shipping_address1 : (string)$transaction->customer_address1);
		$shipping_address2 = ((string)$transaction->shipping_address2 != "" ? (string)$transaction->shipping_address2 : (string)$transaction->customer_address2);
		$shipping_city = ((string)$transaction->shipping_city != "" ? (string)$transaction->shipping_city : (string)$transaction->customer_city);
		$shipping_state = ((string)$transaction->shipping_state != "" ? (string)$transaction->shipping_state : (string)$transaction->customer_state);
		$shipping_postal_code = ((string)$transaction->shipping_postal_code != "" ? (string)$transaction->shipping_postal_code : (string)$transaction->customer_postal_code);
		$shipping_country = ((string)$transaction->shipping_country != "" ? (string)$transaction->shipping_country : (string)$transaction->customer_country);
		$shipping_phone = ((string)$transaction->shipping_phone != "" ? (string)$transaction->shipping_phone : (string)$transaction->customer_phone);
		$shipping_phone = preg_replace("/[^0-9]/","", $shipping_phone); //Strip Non-Numberic Characters
		$shipping_phone = apply_filters('foxyshop_ups_phone', $shipping_phone, $transaction);
		$customer_email = (string)$transaction->customer_email;
		$customer_id = (string)$transaction->customer_id;
		$transaction_id = (string)$transaction->id;
		$shipto_shipping_service_description = (string)$transaction->shipto_shipping_service_description;
		$shipping_name = $shipping_company;
		if ($shipping_company) {
			$shipping_attn = $shipping_first_name . ' ' . $shipping_last_name;
		} else {
			$shipping_company = $shipping_first_name . ' ' . $shipping_last_name;
			$shipping_attn = "";
		}

		//Get Service Type
		$service_type = $default_service_type;
		if (isset($services_types[$shipto_shipping_service_description])) $service_type = $services_types[$shipto_shipping_service_description];

		//Get Weight
		$product_weight = 0;
		foreach($transaction->transaction_details->transaction_detail as $transaction_detail) {
			$product_weight += (double)$transaction_detail->product_weight;
			foreach($transaction_detail->transaction_detail_options->transaction_detail_option as $transaction_detail_option) {
				$product_weight += (double)$transaction_detail_option->weight_mod;
			}
		}

		//Residential Indicator
		if ($default_residential_indicator != "") {
			$residential_indicator = $default_residential_indicator;
		} else {
			$residential_indicator = $shipping_attn != "" ? 0 : 1;
		}

		$xml .= "\t".'<OpenShipment ShipmentOption="" ProcessStatus="">'."\n";
		$xml .= "\t\t".'<ShipTo>'."\n";
		$xml .= "\t\t\t".'<CustomerID>' . $customer_id . '</CustomerID>'."\n";
		$xml .= "\t\t\t".'<CompanyOrName>' . $shipping_company . '</CompanyOrName>'."\n";
		$xml .= "\t\t\t".'<Attention>' . $shipping_attn . '</Attention>'."\n";
		$xml .= "\t\t\t".'<Address1>' . $shipping_address1 . '</Address1>'."\n";
		if ($shipping_address2) $xml .= "\t\t\t".'<Address2>' . $shipping_address2 . '</Address2>'."\n";
		$xml .= "\t\t\t".'<CityOrTown>' . $shipping_city . '</CityOrTown>'."\n";
		$xml .= "\t\t\t".'<CountryTerritory>' . $shipping_country . '</CountryTerritory>'."\n";
		$xml .= "\t\t\t".'<PostalCode>' . apply_filters('foxyshop_ups_postal_code', $shipping_postal_code, $transaction) . '</PostalCode>'."\n";
		$xml .= "\t\t\t".'<StateProvinceCounty>' . $shipping_state . '</StateProvinceCounty>'."\n";
		if ($shipping_phone) $xml .= "\t\t\t".'<Telephone>' . $shipping_phone . '</Telephone>'."\n";
		$xml .= "\t\t\t".'<EmailAddress>' . $customer_email . '</EmailAddress>'."\n";
		$xml .= "\t\t\t".'<ResidentialIndicator>' . apply_filters('foxyshop_ups_residential', $residential_indicator, $transaction) . '</ResidentialIndicator>'."\n";
		if (has_filter('foxyshop_ups_shipto_node')) $xml .= apply_filters('foxyshop_ups_shipto_node', '', $transaction);
		$xml .= "\t\t".'</ShipTo>'."\n";
		$xml .= "\t\t".'<ShipmentInformation>'."\n";
		$xml .= "\t\t\t".'<ServiceType>' . $service_type . '</ServiceType>'."\n";
		$xml .= "\t\t\t".'<NumberOfPackages>' . apply_filters('foxyshop_ups_package_number', 1, $transaction) . '</NumberOfPackages>'."\n";
		$xml .= "\t\t\t".'<ShipmentActualWeight>' . $product_weight . '</ShipmentActualWeight>'."\n";
		$xml .= "\t\t\t".'<DescriptionOfGoods>' . apply_filters('foxyshop_ups_goods_descript', $description_of_goods, $transaction) . '</DescriptionOfGoods>'."\n";
		$xml .= "\t\t\t".'<BillingOption>' . apply_filters('foxyshop_ups_billing_option', $billing_option, $transaction) . '</BillingOption>'."\n";
		if (has_filter('foxyshop_ups_shipment_info_node')) $xml .= apply_filters('foxyshop_ups_shipment_info_node', '', $transaction);
		$xml .= "\t\t".'</ShipmentInformation>'."\n";
		$xml .= "\t\t".'<Package>'."\n";
		$xml .= "\t\t\t".'<PackageType>' . apply_filters('foxyshop_ups_package_type', $package_type, $transaction) . '</PackageType>'."\n";
		$xml .= "\t\t\t".'<Reference1>' . $transaction_id . '</Reference1>'."\n";
		if (has_filter('foxyshop_ups_package_node')) $xml .= apply_filters('foxyshop_ups_package_node', '', $transaction);
		$xml .= "\t\t".'</Package>'."\n";
		$xml .= "\t".'</OpenShipment>'."\n";
	}
	$xml .= '</OpenShipments>'."\n";
	echo $xml;
	die;
}



function foxyshop_transaction_export() {
	global $foxyshop_settings;

	if ($_GET['transaction_search_type'] == "export_csv") {
		$field_delimiter = ",";
	} elseif ($_GET['transaction_search_type'] == "export_tab") {
		$field_delimiter = "\t";
	}

	//Setup Fields and Defaults
	$foxy_data_defaults = array(
		"is_test_filter" => "0",
		"hide_transaction_filter" => "0",
		"data_is_fed_filter" => "",
		"id_filter" => "",
		"order_total_filter" => "",
		"coupon_code_filter" => "",
		"transaction_date_filter_begin" => date("Y-m-d", strtotime("-10 days")),
		"transaction_date_filter_end" => date("Y-m-d"),
		"customer_id_filter" => "",
		"customer_email_filter" => "",
		"customer_first_name_filter" => "",
		"customer_last_name_filter" => "",
		"customer_state_filter" => "",
		"shipping_state_filter" => "",
		"customer_ip_filter" => "",
		"product_code_filter" => "",
		"product_name_filter" => "",
		"product_option_name_filter" => "",
		"product_option_value_filter" => ""
	);
	if (version_compare($foxyshop_settings['version'], '0.7.2', ">=")) {
		$foxy_data_defaults["custom_field_name_filter"] = "";
		$foxy_data_defaults["custom_field_value_filter"] = "";
	}
	$foxy_data = wp_parse_args(array("api_action" => "transaction_list"), $foxy_data_defaults);

	if (isset($_GET['foxyshop_search'])) {
		$fields = array("is_test_filter", "hide_transaction_filter", "data_is_fed_filter", "id_filter", "order_total_filter", "coupon_code_filter", "transaction_date_filter_begin", "transaction_date_filter_end", "customer_id_filter", "customer_email_filter", "customer_first_name_filter", "customer_last_name_filter","customer_state_filter", "shipping_state_filter", "customer_ip_filter", "product_code_filter", "product_name_filter", "product_option_name_filter", "product_option_value_filter", "custom_field_name_filter", "custom_field_value_filter");
		foreach ($fields as $field) {
			if (isset($_GET[$field])) {
				$foxy_data[$field] = $_GET[$field];
			}
		}
		$foxy_data['pagination_start'] = (isset($_GET['pagination_start']) ? $_GET['pagination_start'] : 0);
		if ($foxyshop_settings['version'] != "0.7.0") $foxy_data['entries_per_page'] = 10000;
	}

	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml_return = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
	if ((string)$xml_return->result == "ERROR") {
		echo '<h3>' . $xml_return->messages->message . '</h3>';
		die;
	}

	// Define the path to file
	$filename = 'foxycart-export-'.Date('d-m-Y').'.' . ($field_delimiter == "," ? "csv" : "txt");

	// Set headers
	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
	header("Content-Type: text/csv");


	$fields = array('transaction_id',
		'store_id',
		'transaction_date',
		'product_total',
		'tax_total',
		'shipping_total',
		'discount_total',
		'order_total',
		'purchase_order',
		'cc_type',
		'cc_number_masked',
		'cc_exp_month',
		'cc_exp_year',
		'processor_response',
		'customer_id',
		'is_anonymous',
		'minfraud_score',
		'customer_first_name',
		'customer_last_name',
		'customer_company',
		'customer_address1',
		'customer_address2',
		'customer_city',
		'customer_state',
		'customer_postal_code',
		'customer_country',
		'customer_phone',
		'customer_email',
		'customer_ip',
		'custom_fields',
		'attributes',
		'coupons_used',
		'shipping_service_description',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_company',
		'shipping_address1',
		'shipping_address2',
		'shipping_city',
		'shipping_state',
		'shipping_postal_code',
		'shipping_country',
		'shipping_phone',
		'sub_token_url',
		'category_code',
		'product_name',
		'product_code',
		'product_price',
		'product_quantity',
		'product_weight',
		'product_options'
	);

	echo implode($field_delimiter, $fields) . "\n";

	foreach($xml_return->transactions->transaction as $transaction) {


		$custom_fields = "";
		if (!empty($transaction->custom_fields)) {
			foreach($transaction->custom_fields->custom_field as $custom_field) {
				if ($custom_fields) $custom_fields .= " - ";
				$custom_fields .= (string)$custom_field->custom_field_name . ":" . (string)$custom_field->custom_field_value;
			}
		}

		$attributes1 = "";
		if (!empty($transaction->attributes)) {
			foreach($transaction->attributes->attribute as $attribute) {
				if ($attributes1) $attributes1 .= " - ";
				$attributes1 .= (string)$attribute->name . ":" . (string)$attribute->value;
			}
		}

		$discounts = "";
		$discount_total = 0;
		if (!empty($transaction->discounts)) {
			foreach($transaction->discounts->discount as $discount) {
				if ($discounts) $discounts .= " - ";
				$discounts .= (string)$discount->code . ":" . (string)$discount->amount;
				$discount_total += (double)$discount->amount;
			}
		}

		$tax_total = 0;
		if (!empty($transaction->taxes)) {
			foreach($transaction->taxes->tax as $tax) {
				$tax_total += (double)$tax->tax_amount;
			}
		}

		$product_total = 0;
		foreach($transaction->transaction_details->transaction_detail as $transaction_detail) {
			$product_price = (double)$transaction_detail->product_price;
			foreach($transaction_detail->transaction_detail_options->transaction_detail_option as $transaction_detail_option) {
				$product_price += (double)$transaction_detail_option->price_mod;
			}
			$product_total += $product_price * (int)$transaction_detail->product_quantity;
		}

		$shipping_total = (double)$transaction->shipping_total;
		$order_total = $product_total + $shipping_total + $discount_total + $tax_total;



		//Start Writing
		echo (string)$transaction->id;
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->store_id) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->transaction_date) . '"';
		echo $field_delimiter . $product_total;
		echo $field_delimiter . $tax_total;
		echo $field_delimiter . $shipping_total;
		echo $field_delimiter . $discount_total;
		echo $field_delimiter . $order_total;
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->purchase_order) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->cc_type) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->cc_number_masked) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->cc_exp_month) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->cc_exp_year) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->processor_response) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_id) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->is_anonymous) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->minfraud_score) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_first_name) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_last_name) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_company) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_address1) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_address2) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_city) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_state) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_postal_code) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_country) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_phone) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_email) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->customer_ip) . '"';

		echo $field_delimiter . '"' . foxyshop_dblquotes($custom_fields) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes($attributes1) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes($discounts) . '"';

		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->shipto_shipping_service_description) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->shipping_first_name) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->shipping_last_name) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->shipping_company) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->shipping_address1) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->shipping_address2) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->shipping_city) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->shipping_state) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->shipping_postal_code) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->shipping_country) . '"';
		echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction->shipping_phone) . '"';

		//Products
		$product_count = 1;
		foreach($transaction->transaction_details->transaction_detail as $transaction_detail) {

			//New Line for Second Product
			if ($product_count > 1) {
				echo (string)$transaction->id;
				for ($i=1;$i<=42;$i++) {
					echo $field_delimiter . '""';
				}
			}

			//Options
			$product_options = "";
			foreach($transaction_detail->transaction_detail_options->transaction_detail_option as $transaction_detail_option) {
				if ($product_options) $product_options .= " - ";
				$product_options .= (string)$transaction_detail_option->product_option_name . ":" . (string)$transaction_detail_option->product_option_value;
			}


			echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction_detail->sub_token_url) . '"';
			echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction_detail->category_code) . '"';
			echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction_detail->product_name) . '"';
			echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction_detail->product_code) . '"';
			echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction_detail->product_price) . '"';
			echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction_detail->product_quantity) . '"';
			echo $field_delimiter . '"' . foxyshop_dblquotes((string)$transaction_detail->product_weight) . '"';
			echo $field_delimiter . '"' . foxyshop_dblquotes($product_options) . '"';

			echo "\n";
			$product_count++;
		}

	}
	die;
}
