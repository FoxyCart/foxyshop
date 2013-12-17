<?php /*
------------ ATTENTION ------------
If you need to edit this template, do not edit the version in the plugin directory. Place a copy in your template folder and edit it there.
This will allow you to upgrade FoxyShop without breaking your customizations. More details here: http://www.foxy-shop.com/documentation/theme-customization/
-----------------------------------
*/

if (!$writeUploadInclude) {
	$writeUploadInclude = 1;
	echo '<link rel="stylesheet" href="' . FOXYSHOP_DIR . '/js/uploadify/uploadify.css" type="text/css" media="screen" />'."\n";
	echo '<script type="text/javascript" src="' . FOXYSHOP_DIR . '/js/uploadify/jquery.uploadify.v2.1.4.min.js"></script>'."\n";
	echo '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>'."\n";
	$imagefilename = "file-" . substr(MD5(rand(1000, 99999)."{img}" . date("H:i:s")),1,8);
	$upload_dir = wp_upload_dir();

	//Get Max Upload Limit
	$max_upload = (int)(ini_get('upload_max_filesize'));
	$max_post = (int)(ini_get('post_max_size'));
	$memory_limit = (int)(ini_get('memory_limit'));
	$upload_mb = min($max_upload, $max_post, $memory_limit);
	$foxyshop_max_upload = $upload_mb * 1048576;
	if ($foxyshop_max_upload == 0) $foxyshop_max_upload = "8000000";

	?>
	<script type="text/javascript">
	jQuery(document).ready(function($){
		$('.foxyshop_file_upload').each(function() {
			var variationID = $(this).attr("rel");
			$(this).uploadify({
				uploader  : '<?php echo FOXYSHOP_DIR; ?>/js/uploadify/uploadify.swf',
				script    : '<?php echo get_bloginfo("url") . FOXYSHOP_URL_BASE; ?>/upload-<?php echo $foxyshop_settings['datafeed_url_key']; ?>/',
				cancelImg : '<?php echo FOXYSHOP_DIR; ?>/js/uploadify/cancel.png',
				auto      : true,
				width     : '120',
				height    : '29',
				sizeLimit : '<?php echo $foxyshop_max_upload; ?>',
				scriptData: { 'newfilename': '<?php echo $imagefilename; ?>_' + $(this).attr("rel") },
				onComplete: function(event,queueID,fileObj,response,data) {
						if (response == "unsupported file type") {
							$("#uploadedFile_" + variationID).html('<span style="color: red;"><?php _e('Invalid File Type'); ?></span>').show();
						} else {
							if (response.indexOf("move_uploaded_file") >= 0) {
								$("#uploadedFile_" + variationID).html('There was an error uploading your image: ' + response);
							} else if (response.indexOf("jpg") >= 0 || response.indexOf("gif") >= 0 || response.indexOf("png") >= 0 || response.indexOf("jpeg") >= 0) {
								$("#uploadedFile_" + variationID).html('<img src="<?php echo $upload_dir['baseurl']; ?>/customuploads/' + response + '?rand=' + Math.floor(Math.random()*1000) + '" alt="" />').show();
								$("#FileNameHolder_"+variationID).val(response);

								//Use this instead if you want to use timthumb to set the result as the product image in the cart
								//new_filename = "<?php bloginfo("url") ?>/timthumb.php?src=<?php echo $upload_dir['baseurl']; ?>/customuploads/" + response + "&w=290";
								//$("#foxyshop_main_product_image").attr("src", new_filename + '&rand=' + Math.floor(Math.random()*1000));
								//$("#foxyshop_cart_product_image_<?php echo $product['id']; ?>").attr("name", "image<?php echo foxyshop_get_verification("image", "--OPEN--"); ?>").val(new_filename);

							} else {
								$("#uploadedFile_" + variationID).html('<?php _e('File Uploaded Successfuly.'); ?> <a href="<?php echo $upload_dir['baseurl']; ?>/customuploads/' + response + '"><?php _e('Click here to view.'); ?></a>').show();
								$("#FileNameHolder_"+variationID).val(response);
							}
						}
					}
			});
		});
	});
	</script>
	<?php
}

$write .= '<div class="foxyshop_custom_upload_container' . $dkeyclass . '"'. $dkey . '>';

$write .= '<label for="' . esc_attr($product['code']) . '_' . $i . '">' . esc_attr(str_replace('_',' ',$variationName)) . '</label>'."\n";

$uploadRequiredClassName = ($variationRequired ? ' foxyshop_required' : '');

$write .= '<input type="file" class="foxyshop_file_upload" rel="' . $i . '" id="' . esc_attr($product['code']) . '_' . $i . '">'."\n";
if ($variationValue) $write .= '<p>' . $variationValue . '</p>'."\n";
$write .= '<div id="uploadedFile_' . $i . '" class="foxyshop_uploaded_file" style="display: none;"></div>'."\n";
$write .= '<input type="hidden" name="' . esc_attr(foxyshop_add_spaces($variationName)) . foxyshop_get_verification(foxyshop_add_spaces($variationName),'--OPEN--') . '" id="FileNameHolder_' . $i . '" value="" class="hiddenimageholder ' . $uploadRequiredClassName . $dkeyclass . '"'. $dkey . ' />'."\n";
$write .= '</div>';

?>