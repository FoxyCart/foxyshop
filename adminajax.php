<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//Display List AJAX Functions
add_action('wp_ajax_foxyshop_display_list_ajax_action', 'foxyshop_display_ajax');
function foxyshop_display_ajax() {
	global $wpdb, $foxyshop_settings;
	check_ajax_referer('foxyshop-display-list-function', 'security');
	if (!isset($_POST['foxyshop_action'])) die;
	$id = isset($_POST['id']) ? $_POST['id'] : 0;
	$transaction_template_id = isset($_POST['transaction_template_id']) ? (int)$_POST['transaction_template_id'] : 0;

	//Change Subscription
	if ($_POST['foxyshop_action'] == 'subscription_modify') {
		$foxy_data = array(
			"api_action" => "subscription_modify",
			"sub_token" => $_POST['sub_token'],
			"start_date" => $_POST['start_date'],
			"frequency" => $_POST['frequency'],
			"past_due_amount" => $_POST['past_due_amount'],
			"is_active" => $_POST['is_active']
		);
		if ($_POST['end_date'] == "0000-00-00" || strtotime($_POST['end_date']) > strtotime("now")) $foxy_data['end_date'] = $_POST['end_date'];
		if (strtotime($_POST['next_transaction_date']) > strtotime("now")) $foxy_data['next_transaction_date'] = $_POST['next_transaction_date'];
		if ($transaction_template_id) $foxy_data['transaction_template'] = foxyshop_subscription_template($transaction_template_id);
		$foxy_response = foxyshop_get_foxycart_data($foxy_data);
		$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
		do_action("foxyshop_after_subscription_modify", $xml);
		echo (string)$xml->result . ": " . (string)$xml->messages->message;
		die;

	//Hide/Unhide Transaction
	} elseif ($_POST['foxyshop_action'] == 'hide_transaction') {
		$foxy_data = array("api_action" => "transaction_modify", "transaction_id" => $id, "hide_transaction" => (int)$_POST['hide_transaction']);
		$foxy_response = foxyshop_get_foxycart_data($foxy_data);
		$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
		do_action("foxyshop_after_transaction_archive", $xml);
		echo (string)$xml->result . ": " . (string)$xml->messages->message;
		die;
	}
	die;
}


//Attribute AJAX Functions
add_action('wp_ajax_foxyshop_attribute_manage', 'foxyshop_manage_attribute_ajax');
function foxyshop_manage_attribute_ajax() {
	global $wpdb, $foxyshop_settings;
	check_ajax_referer('foxyshop-save-attribute', 'security');
	if (!isset($_POST['foxyshop_action'])) die;
	if (!isset($_POST['att_type'])) die;
	if (!isset($_POST['id'])) die;

	$id = $_POST['id'];
	$att_type = $_POST['att_type'];
	$att_name = $_POST['att_name'];

	//Save
	if ($_POST['foxyshop_action'] == 'save_attribute') {
		$att_value = str_replace('\"', '"', $_POST['att_value']);
		echo foxyshop_save_attribute($att_type, $id, $att_name, $att_value);
		die;

	//Delete
	} elseif ($_POST['foxyshop_action'] == 'delete_attribute') {
		echo foxyshop_delete_attribute($att_type, $id, $att_name);
		die;
	}
	die;
}


//Get New Category List AJAX
add_action('wp_ajax_foxyshop_ajax_get_category_list', 'foxyshop_ajax_get_category_list');
function foxyshop_ajax_get_category_list() {
	check_ajax_referer('foxyshop-ajax-get-category-list', 'security');
	echo foxyshop_get_category_list();
	die;
}


//Get New Category List From Product Page AJAX
add_action('wp_ajax_foxyshop_ajax_get_category_list_select', 'foxyshop_ajax_get_category_list_select');
function foxyshop_ajax_get_category_list_select() {
	check_ajax_referer('foxyshop-ajax-get-downloadable-list', 'security');
	echo foxyshop_get_category_list('select');
	die;
}


//Get New Downloadable List AJAX
add_action('wp_ajax_foxyshop_ajax_get_downloadable_list', 'foxyshop_ajax_get_downloadable_list');
function foxyshop_ajax_get_downloadable_list() {
	check_ajax_referer('foxyshop-ajax-get-downloadable-list', 'security');
	$output = foxyshop_get_downloadable_list();
	foreach ($output as $downloadable) {
		echo '<option value="' . esc_attr($downloadable['product_code']) . '"';
		echo ' category_code="' . esc_attr($downloadable['category_code']) . '"';
		echo ' product_price="' . esc_attr($downloadable['product_price']) . '"';
		echo '>' . esc_attr($downloadable['product_name']) . '</option>';
		echo "\n";
	}
	die;
}



//Set Google Auth Code
add_action('wp_ajax_foxyshop_set_google_auth', 'foxyshop_ajax_set_google_auth');
function foxyshop_ajax_set_google_auth() {
	global $foxyshop_settings;
	check_ajax_referer('foxyshop-ajax-set-google-auth', 'security');
	$header_array = array("Content-Type: application/x-www-form-urlencoded");
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "Email=" . urlencode($_POST['Email']) . "&Passwd=" . urlencode($_POST['Passwd']) . "&service=structuredcontent&source=FoxyShop");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$response_line = preg_split("[\r\n|\r|\n]", trim(curl_exec($ch)));
	foreach($response_line as $response) {
		$r = explode("=", $response);
		if ($r[0] == "Error") {
			die("Error");
		} elseif ($r[0] == "Auth") {
			$foxyshop_settings['google_product_auth'] = strip_tags($r[1]);
			update_option("foxyshop_settings", $foxyshop_settings);
			die("Success");
		}
	}
	die;
}




//FoxyShop Product AJAX Functions
add_action('wp_ajax_foxyshop_product_ajax_action', 'foxyshop_product_ajax');
function foxyshop_product_ajax() {
	global $wpdb;
	$productID = (isset($_POST['foxyshop_product_id']) ? (int)$_POST['foxyshop_product_id'] : 0);
	$imageID = (isset($_POST['foxyshop_image_id']) ? (int)$_POST['foxyshop_image_id'] : 0);
	check_ajax_referer('foxyshop-product-image-functions-'.$productID, 'security');
	if (!isset($_POST['foxyshop_action'])) die;

	if ($_POST['foxyshop_action'] == "add_new_image") {

		echo foxyshop_redraw_images($productID);

	} elseif ($_POST['foxyshop_action'] == "delete_image") {
		wp_delete_attachment($imageID);
		echo foxyshop_redraw_images($productID);

	} elseif ($_POST['foxyshop_action'] == "featured_image") {
		delete_post_meta($productID,"_thumbnail_id");
		update_post_meta($productID,"_thumbnail_id",$imageID);
		echo foxyshop_redraw_images($productID);

	} elseif ($_POST['foxyshop_action'] == "toggle_visible") {
		if (get_post_meta($imageID, "_foxyshop_hide_image", 1)) {
			delete_post_meta($imageID, "_foxyshop_hide_image");
		} else {
			add_post_meta($imageID,"_foxyshop_hide_image", 1);
		}
		echo foxyshop_redraw_images($productID);

	} elseif ($_POST['foxyshop_action'] == "rename_image") {
		$update_post = array();
		$update_post['ID'] = $imageID;
		$update_post['post_title'] = $_POST['foxyshop_new_name'];
		wp_update_post($update_post);

	} elseif ($_POST['foxyshop_action'] == "update_image_order") {

		$foxyshop_order_array = $_POST['foxyshop_order_array'];
		$IDs = explode(",", $foxyshop_order_array);
		$result = count($IDs);
		for($i = 0; $i < $result; $i++) {
			$update_post = array();
			$update_post['ID'] = str_replace("att_", "", $IDs[$i]);
			$update_post['menu_order'] = $i+1;
			wp_update_post($update_post);
		}

		echo foxyshop_redraw_images($productID);

	} elseif ($_POST['foxyshop_action'] == "refresh_images") {
		echo foxyshop_redraw_images($productID);
	}
	die;
}

//Function to redraw images
function foxyshop_redraw_images($id) {
	global $wpdb;
	$write = "";
	$featuredImageID = (has_post_thumbnail($id) ? get_post_thumbnail_id($id) : 0);
	$attachments = get_posts(array('numberposts' => -1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $id, 'order' => 'ASC','orderby' => 'menu_order'));
	if ($attachments) {
		$i = 0;
		foreach ($attachments as $attachment) {
			if (wp_attachment_is_image($attachment->ID)) {

				$thumbnailSRC = wp_get_attachment_image_src($attachment->ID, "thumbnail");
				$hide_from_slideshow = get_post_meta($attachment->ID, "_foxyshop_hide_image", 1);
				$featured_class = $featuredImageID == $attachment->ID || ($featuredImageID == 0 && $i == 0) ? 'foxyshop_featured_image ' : '';
				$hide_from_slideshow_class = $hide_from_slideshow ? 'foxyshop_hide_from_slideshow ' : '';

				$write .= '<li id="att_' . $attachment->ID . '" class="'. $featured_class . $hide_from_slideshow_class . '">';
				$write .= '<div class="foxyshop_image_holder"><img src="' . $thumbnailSRC[0] . '" alt="' . esc_attr($attachment->post_title) . ' (' . $attachment->ID . ')" title="' . esc_attr($attachment->post_title) . ' (' . $attachment->ID . ')" /></div>';
				$write .= '<div style="clear: both;"></div>';
				$write .= '<a href="#" class="foxyshop_image_delete" rel="' . $attachment->ID . '" alt="Delete" title="Delete">Delete</a>';
				$write .= '<a href="#" class="foxyshop_image_rename" rel="' . $attachment->ID . '" alt="Rename" title="Rename">Rename</a>';
				$write .= '<a href="#" class="foxyshop_image_featured" rel="' . $attachment->ID . '" alt="Make Featured Image" title="Make Featured Image">Make Featured Image</a>';
				$write .= '<a href="#" class="foxyshop_visible" rel="' . $attachment->ID . '" alt="Toggle Slideshow View" title="Toggle Slideshow View">Toggle Slideshow View</a>';
				$write .= '<div class="renamediv" id="renamediv_' . $attachment->ID . '">';
				$write .= '<input type="text" name="rename_' . $attachment->ID . '" id="rename_' . $attachment->ID . '" rel="' . $attachment->ID . '" value="' . esc_attr($attachment->post_title) . '" />';
				$write .= '</div>';
				$write .= '<div style="clear: both;"></div>';
				$write .= '</li>';
				$write .= "\n";
				$i++;
			}
		}
	}
	return $write;
}