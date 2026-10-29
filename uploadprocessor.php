<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

 if (!function_exists('media_handle_upload')){
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
}

/*
This is the public customer file upload endpoint (used by custom "upload" variation templates).
It is disabled unless you put this in your wp-config.php file:
define('FOXYSHOP_ENABLE_USER_UPLOAD', true);
To add an allowed upload extension, put this in your wp-config.php file with new extensions separated by a comma:
define('FOXYSHOP_ALLOWED_EXTENSIONS',"newext1,newext2");
Admin product image uploads are handled by the foxyshop_product_image_upload AJAX action.
*/

//If Empty, Die!
if (empty($_FILES)) die('1');

$unsupported_file_type_text = __('unsupported file type', 'foxyshop');
$upload_runtime_error = __('An inner error has happened during file upload.', 'foxyshop');

//User Upload
if (isset($_POST['newfilename']) && isset($_FILES['Filedata']) && defined('FOXYSHOP_ENABLE_USER_UPLOAD') && FOXYSHOP_ENABLE_USER_UPLOAD && !defined('FOXYSHOP_DISABLE_USER_UPLOAD')) {

	$ext = strtolower(substr($_FILES['Filedata']['name'], strrpos($_FILES['Filedata']['name'], '.') + 1));
	if (!in_array($ext, foxyshop_allowed_upload_extensions())) die($unsupported_file_type_text);

	$newfilename = str_replace(array('.','/','\\',' '),'',sanitize_text_field($_POST['newfilename'])).'.'.$ext;
	$_FILES['Filedata']['name'] = $newfilename;
	$results = wp_handle_upload($_FILES['Filedata'], ['test_form'=>FALSE]);

	if(!is_array($results) || isset($results['error'])){
		die($upload_runtime_error);
	}

	echo esc_html($newfilename);

//Nothing Requested
} else {
	echo esc_html(__('invalid request', 'foxyshop'));
}


exit;
?>
