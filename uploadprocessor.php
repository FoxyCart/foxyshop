<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

 if (!function_exists('media_handle_upload')){
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
}

/*
This is the uploadify uploader. It is being processed through WordPress so has access to all the WordPress security functions.
To add an allowed upload extension, put this in your wp-config.php file with new extensions separated by a comma:
define('FOXYSHOP_ALLOWED_EXTENSIONS',"newext1,newext2");
*/

//If Empty, Die!
if (empty($_FILES)) die('1');


//Setup Vars
$upload_dir = wp_upload_dir();

$unsupported_file_type_text = __('unsupported file type', 'foxyshop');
$upload_runtime_error = __('An inner error has happened during file upload.', 'foxyshop');

//Allowed Extensions
$allowed_extensions = array("jpg","gif","jpeg","png","doc","docx","odt","xmls","xlsx","txt","tif","psd","pdf","mp3");
if (defined('FOXYSHOP_ALLOWED_EXTENSIONS')) $allowed_extensions = array_merge($allowed_extensions, explode(",",str_replace(' ','',FOXYSHOP_ALLOWED_EXTENSIONS)));

//Admin Upload
if (isset($_GET['foxyshop_product_id'])) {

	$product_id = (isset($_GET['foxyshop_product_id']) ? sanitize_text_field($_GET['foxyshop_product_id']) : 0);

	$images = get_children(array('post_parent' => $product_id, 'post_type' => 'attachment', "post_mime_type" => "image"));
	if (empty($images)) {
		$product_count = 0;
	} else {
		$product_count = count($images);
	}

	//$tempFile = $_FILES['file']['tmp_name'];

	$filename = urldecode($_FILES['file']['name']);
	$filename = str_replace(array('[1]','[2]','[3]','[4]','[5]','[6]','[7]','[8]','[9]','[10]'),'',$filename);
	$filename = sanitize_file_name($filename);

	$ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
	if (!in_array($ext, $allowed_extensions)) {
		die($unsupported_file_type_text);
	}

	$results = wp_handle_upload($_FILES['file'], ['test_form'=>FALSE]);

	if(!is_array($results) || isset($results['error'])){
		die($upload_runtime_error);
	}

	$targetFile = $results['file'];

	$targetFile = apply_filters("foxyshop_image_upload_file", $targetFile);
	if (is_array($targetFile)) {
		die("error: " . $targetFile['error']);
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
	require_once(ABSPATH . "wp-admin/includes/image.php");
	$attach_id = wp_insert_attachment($attachment, $targetFile, $product_id);
	$attach_data = wp_generate_attachment_metadata($attach_id, $targetFile);
	wp_update_attachment_metadata($attach_id, $attach_data);

	if ($product_count == 0) {
		update_post_meta($product_id,"_thumbnail_id",$attach_id);
	}

	echo ('success');


//User Upload
} elseif (isset($_POST['newfilename']) && !defined('FOXYSHOP_DISABLE_USER_UPLOAD')) {

	$ext = strtolower(substr($_FILES['Filedata']['name'], strrpos($_FILES['Filedata']['name'], '.') + 1));
	if (!in_array($ext, $allowed_extensions)) die($unsupported_file_type_text);


	$newfilename = str_replace(array('.','/','\\',' '),'',sanitize_text_field($_POST['newfilename'])).'.'.$ext;
	$_FILES['Filedata']['name'] = $newfilename;
	$results = wp_handle_upload($_FILES['Filedata'], ['test_form'=>FALSE]);

	if(!is_array($results) || isset($results['error'])){
		die($upload_runtime_error);
	}

	$targetFile = $results['file'];

	echo esc_html($newfilename);

//Nothing Requested
} else {
	echo esc_html(__('invalid request', 'foxyshop'));
}


exit;
?>
