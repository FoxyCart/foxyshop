<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//Display List AJAX Functions
add_action('wp_ajax_foxyshop_display_list_ajax_action', 'foxyshop_display_ajax');
function foxyshop_display_ajax() {
	global $wpdb, $foxyshop_settings;
	check_ajax_referer('foxyshop-display-list-function', 'security');
	if (!isset($_POST['foxyshop_action']) || !is_string($_POST['foxyshop_action'])) die;
	$_POST['foxyshop_action'] = sanitize_text_field($_POST['foxyshop_action']);

	//Each Action Needs the Permission for Its Own Area
	$display_action_perms = array('subscription_modify' => 'foxyshop_subscription_perm', 'hide_transaction' => 'foxyshop_order_perm');
	if (!isset($display_action_perms[$_POST['foxyshop_action']])) die;
	foxyshop_require_capability($display_action_perms[$_POST['foxyshop_action']]);
	$id = isset($_POST['id']) ? (int)sanitize_text_field($_POST['id']) : 0;
	$transaction_template_id = isset($_POST['transaction_template_id']) ? (int)sanitize_text_field($_POST['transaction_template_id']) : 0;

	//Change Subscription
	if ($_POST['foxyshop_action'] == 'subscription_modify') {
		$sub_token = sanitize_text_field($_POST['sub_token']);
		$start_date = sanitize_text_field($_POST['start_date']);
		$frequency = sanitize_text_field($_POST['frequency']);
		$past_due_amount = sanitize_text_field($_POST['past_due_amount']);
		$is_active = sanitize_text_field($_POST['is_active']);
		$end_date = sanitize_text_field($_POST['end_date']);
		$next_transaction_date = sanitize_text_field($_POST['next_transaction_date']);

		$foxy_data = array(
			"api_action" => "subscription_modify",
			"sub_token" => ($sub_token),
			"start_date" => ($start_date),
			"frequency" => ($frequency),
			"past_due_amount" => ($past_due_amount),
			"is_active" => ($is_active)
		);
		if ($end_date == "0000-00-00" || strtotime($end_date) > strtotime("now")) $foxy_data['end_date'] = $end_date;
		if (strtotime($next_transaction_date) > strtotime("now")) $foxy_data['next_transaction_date'] = $next_transaction_date;
		if ($transaction_template_id) $foxy_data['transaction_template'] = foxyshop_subscription_template($transaction_template_id);
		$foxy_response = foxyshop_get_foxycart_data($foxy_data);
		$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
		do_action("foxyshop_after_subscription_modify", $xml);
		echo esc_html((string)$xml->result . ": " . (string)$xml->messages->message);
		die;

	//Hide/Unhide Transaction
	} elseif ($_POST['foxyshop_action'] == 'hide_transaction') {
		$foxy_data = array("api_action" => "transaction_modify", "transaction_id" => $id, "hide_transaction" => (int)sanitize_text_field($_POST['hide_transaction']));
		$foxy_response = foxyshop_get_foxycart_data($foxy_data);
		$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
		do_action("foxyshop_after_transaction_archive", $xml);
		echo esc_html((string)$xml->result . ": " . (string)$xml->messages->message);
		die;
	}
	die;
}


//Attribute AJAX Functions
add_action('wp_ajax_foxyshop_attribute_manage', 'foxyshop_manage_attribute_ajax');
function foxyshop_manage_attribute_ajax() {
	global $wpdb, $foxyshop_settings;
	$foxyshop_action = sanitize_text_field($_POST['foxyshop_action']);

	check_ajax_referer('foxyshop-save-attribute', 'security');
	//Attributes Need the Permission for the Area They Belong To
	$attribute_perms = array('transaction' => 'foxyshop_order_perm', 'customer' => 'foxyshop_customer_perm', 'subscription' => 'foxyshop_subscription_perm');
	$attribute_type = isset($_POST['att_type']) ? sanitize_text_field($_POST['att_type']) : '';
	if (!isset($attribute_perms[$attribute_type])) wp_die('', '', array('response' => 403));
	foxyshop_require_capability($attribute_perms[$attribute_type]);
	if (!isset($foxyshop_action)) die;
	if (!isset($_POST['att_type'])) die;
	if (!isset($_POST['id'])) die;

	$id = sanitize_text_field($_POST['id']);
	$att_type = sanitize_text_field($_POST['att_type']);
	$att_name = sanitize_text_field($_POST['att_name']);

	//Save
	if ($foxyshop_action == 'save_attribute') {
		$att_value = str_replace('\"', '"', sanitize_text_field($_POST['att_value']));
		echo esc_attr(foxyshop_save_attribute($att_type, $id, $att_name, $att_value));
		die;

	//Delete
	} elseif ($foxyshop_action == 'delete_attribute') {
		echo esc_attr(foxyshop_delete_attribute($att_type, $id, $att_name));
		die;
	}
	die;
}


//Get New Category List AJAX
add_action('wp_ajax_foxyshop_ajax_get_category_list', 'foxyshop_ajax_get_category_list');
function foxyshop_ajax_get_category_list() {
	check_ajax_referer('foxyshop-ajax-get-category-list', 'security');
	foxyshop_require_capability('foxyshop_settings_perm');
	echo esc_html(foxyshop_get_category_list());
	die;
}


//Get New Category List From Product Page AJAX
add_action('wp_ajax_foxyshop_ajax_get_category_list_select', 'foxyshop_ajax_get_category_list_select');
function foxyshop_ajax_get_category_list_select() {
	check_ajax_referer('foxyshop-ajax-get-downloadable-list', 'security');
	if (!current_user_can('edit_pages')) wp_die('', '', array('response' => 403));
	echo foxy_wp_kses_html(foxyshop_get_category_list('select'), ['option']);
	die;
}


//Get New Downloadable List AJAX
add_action('wp_ajax_foxyshop_ajax_get_downloadable_list', 'foxyshop_ajax_get_downloadable_list');
function foxyshop_ajax_get_downloadable_list() {
	check_ajax_referer('foxyshop-ajax-get-downloadable-list', 'security');
	if (!current_user_can('edit_pages')) wp_die('', '', array('response' => 403));
	$output = foxyshop_get_downloadable_list();
	foreach ($output as $downloadable) {
		echo ('<option value="' . esc_attr($downloadable['product_code']) . '"'.
		 ' category_code="' . esc_attr($downloadable['category_code']) . '"'.
		 ' product_price="' . esc_attr($downloadable['product_price']) . '"'.
		 '>' . esc_attr($downloadable['product_name']) . '</option>'.
		 "\n");
	}
	die;
}



//Set Google Auth Code
add_action('wp_ajax_foxyshop_set_google_auth', 'foxyshop_ajax_set_google_auth');
function foxyshop_ajax_set_google_auth() {
	global $foxyshop_settings;
	check_ajax_referer('foxyshop-ajax-set-google-auth', 'security');
	foxyshop_require_capability('foxyshop_google_product_perm');

	$response = wp_remote_post("https://www.google.com/accounts/ClientLogin",
		[
			'headers' =>  ["Content-Type" => "application/x-www-form-urlencoded"],
			'body' => ['Email' =>urlencode(sanitize_email($_POST['Email'])) ,
						'Passwd' => urlencode(sanitize_text_field($_POST['Passwd'])),
						'service' => 'structuredcontent',
						'source' => 'FoxyShop' ]
		]);

	if ( is_wp_error( $response ) ) {
		    die('Error');
	}

	$ans = trim($response['body']);
	$response_line = preg_split("[\r\n|\r|\n]", $ans);
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




//Extensions Allowed for Product Image and Customer Uploads
function foxyshop_allowed_upload_extensions() {
	$allowed_extensions = array("jpg","gif","jpeg","png","doc","docx","odt","xmls","xlsx","txt","tif","psd","pdf","mp3");
	if (defined('FOXYSHOP_ALLOWED_EXTENSIONS')) $allowed_extensions = array_merge($allowed_extensions, explode(",",str_replace(' ','',FOXYSHOP_ALLOWED_EXTENSIONS)));
	return $allowed_extensions;
}

//Upload a Product Image AJAX
add_action('wp_ajax_foxyshop_product_image_upload', 'foxyshop_product_image_upload_ajax');
function foxyshop_product_image_upload_ajax() {
	$product_id = (isset($_POST['foxyshop_product_id']) ? (int)$_POST['foxyshop_product_id'] : 0);
	check_ajax_referer('foxyshop-product-image-functions-'.$product_id, 'security');
	if (!$product_id || get_post_type($product_id) != 'foxyshop_product' || !current_user_can('edit_post', $product_id) || !current_user_can('upload_files')) wp_die('', '', array('response' => 403));
	if (empty($_FILES['file'])) die('1');

	require_once(ABSPATH . 'wp-admin/includes/image.php');
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/media.php');

	$images = get_children(array('post_parent' => $product_id, 'post_type' => 'attachment', "post_mime_type" => "image"));
	$product_count = empty($images) ? 0 : count($images);

	$filename = urldecode($_FILES['file']['name']);
	$filename = str_replace(array('[1]','[2]','[3]','[4]','[5]','[6]','[7]','[8]','[9]','[10]'),'',$filename);
	$filename = sanitize_file_name($filename);

	$ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
	if (!in_array($ext, foxyshop_allowed_upload_extensions())) {
		die(esc_html__('unsupported file type', 'foxyshop'));
	}

	$results = wp_handle_upload($_FILES['file'], ['test_form'=>FALSE]);
	if(!is_array($results) || isset($results['error'])){
		die(esc_html__('An inner error has happened during file upload.', 'foxyshop'));
	}

	$targetFile = $results['file'];
	$targetFile = apply_filters("foxyshop_image_upload_file", $targetFile);
	if (is_array($targetFile)) {
		die("error: " . esc_html($targetFile['error']));
	}

	//Setup New Image
	$wp_filetype = wp_check_filetype(basename($targetFile), null);
	$product_title = get_the_title($product_id);
	if ($product_title == "Auto Draft") $product_title = "Image";
	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title' => $product_title,
		'guid' => $results['url'],
		'menu_order' => $product_count + 1,
		'post_content' => '',
		'post_status' => 'inherit'
	);
	$attach_id = wp_insert_attachment($attachment, $targetFile, $product_id);
	$attach_data = wp_generate_attachment_metadata($attach_id, $targetFile);
	wp_update_attachment_metadata($attach_id, $attach_data);

	if ($product_count == 0) {
		update_post_meta($product_id,"_thumbnail_id",$attach_id);
	}

	echo ('success');
	die;
}


//FoxyShop Product AJAX Functions
add_action('wp_ajax_foxyshop_product_ajax_action', 'foxyshop_product_ajax');
function foxyshop_product_ajax() {
	global $wpdb;

	$productID = (isset($_POST['foxyshop_product_id']) ? (int)sanitize_text_field($_POST['foxyshop_product_id']) : 0);
	$imageID = (isset($_POST['foxyshop_image_id']) ? (int)sanitize_text_field($_POST['foxyshop_image_id']) : 0);
	check_ajax_referer('foxyshop-product-image-functions-'.$productID, 'security');
	if (!isset($_POST['foxyshop_action'])) die;
	if (!$productID || !current_user_can('edit_post', $productID)) wp_die('', '', array('response' => 403));

	//Image Actions May Only Touch Attachments That Belong to This Product
	if ($imageID && !foxyshop_is_product_attachment($imageID, $productID)) wp_die('', '', array('response' => 403));


	$foxyshop_action = sanitize_text_field($_POST['foxyshop_action']);

	if ($foxyshop_action == "add_new_image") {

		echo foxy_wp_kses_html(foxyshop_redraw_images($productID));

	} elseif ($foxyshop_action == "delete_image") {
		wp_delete_attachment($imageID);
		echo foxy_wp_kses_html(foxyshop_redraw_images($productID));

	} elseif ($foxyshop_action == "featured_image") {
		delete_post_meta($productID,"_thumbnail_id");
		update_post_meta($productID,"_thumbnail_id",$imageID);
		echo foxy_wp_kses_html(foxyshop_redraw_images($productID));

	} elseif ($foxyshop_action == "toggle_visible") {
		if (get_post_meta($imageID, "_foxyshop_hide_image", 1)) {
			delete_post_meta($imageID, "_foxyshop_hide_image");
		} else {
			add_post_meta($imageID,"_foxyshop_hide_image", 1);
		}
		echo foxy_wp_kses_html(foxyshop_redraw_images($productID));

	} elseif ($foxyshop_action == "rename_image") {
		$update_post = array();
		$update_post['ID'] = $imageID;
		$update_post['post_title'] = sanitize_text_field($_POST['foxyshop_new_name']);
		wp_update_post($update_post);

	} elseif ($foxyshop_action == "update_image_order") {

		$foxyshop_order_array = sanitize_text_field($_POST['foxyshop_order_array']);
		$IDs = explode(",", $foxyshop_order_array);
		$result = count($IDs);
		for($i = 0; $i < $result; $i++) {
			$attachment_id = (int)str_replace("att_", "", $IDs[$i]);
			if (!foxyshop_is_product_attachment($attachment_id, $productID)) continue;
			$update_post = array();
			$update_post['ID'] = $attachment_id;
			$update_post['menu_order'] = $i+1;
			wp_update_post($update_post);
		}

		echo foxy_wp_kses_html(foxyshop_redraw_images($productID));

	} elseif ($foxyshop_action == "refresh_images") {
		echo foxy_wp_kses_html(foxyshop_redraw_images($productID));
	}
	die;
}

//Check That an Attachment Belongs to a Product
function foxyshop_is_product_attachment($attachment_id, $product_id) {
	return get_post_type($attachment_id) == 'attachment' && (int)get_post_field('post_parent', $attachment_id) == (int)$product_id;
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
				$write .= '<div class="foxyshop_image_holder"><img src="' . esc_url($thumbnailSRC[0]) . '" alt="' . esc_attr($attachment->post_title) . ' (' . $attachment->ID . ')" title="' . esc_attr($attachment->post_title) . ' (' . $attachment->ID . ')" /></div>';
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