<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

/*
This file is setup to provide you with the ability to create a product feed that can be sent out to various aggregators. This is designed specifically for Google.
*/

if (isset($_GET['create_google_product_feed'])) add_action('admin_init', 'foxyshop_save_feed_file');
function foxyshop_save_feed_file() {
	// Define the path to file
	$filename = 'Google-Product-Feed.txt';

	// Set headers
	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
	header("Content-Type: text/plain");
	header("Content-Transfer-Encoding: binary");

	foxyshop_create_feed();
	die;
}

function foxyshop_create_feed() {
	global $product, $google_product_field_names;

	//Field Names
	$fieldnames = array(
		'id',
		'item_group_id',
		'product_type',
		'title',
		'link',
		'price',
		'sale_price',
		'sale_price_effective_date',
		'availability',
		'description',
		'image_link',
		'additional_image_link'
	);
	$fieldnames = array_merge($fieldnames, $google_product_field_names);
	$lastfieldname = end($fieldnames);

	$write = "";
	foreach($fieldnames as $field) {
		if ($field != $fieldnames[0]) $write .= "\t";
		$write .= '"' . $field . '"';
	}
	$write .= "\n";

	$products = get_posts(array('post_type' => 'foxyshop_product', 'post_status' => "publish", 'numberposts' => -1));
	foreach($products as $singleproduct) {
		$product = foxyshop_setup_product($singleproduct);

		foreach($fieldnames as $fieldname) {

			$write .= '"';
			switch ($fieldname) {
				case "id":
					$write .= foxyshop_dblquotes($product['id']); break;
				case "title":
					$write .= foxyshop_dblquotes($product['name']); break;
				case "link":
					$write .= foxyshop_dblquotes($product['url']); break;
				case "availability":
					$availability = get_post_meta($product['id'],'_availability',1);
					if (!$availability) $availability = "in stock";
					$write .= foxyshop_dblquotes($availability); break;
				case "price":
					$write .= foxyshop_dblquotes($product['originalprice']); break;
				case "sale_price":
					$write .= foxyshop_dblquotes(($product['originalprice'] != $product['price'] ? $product['price'] : ''));
					break;
				case "sale_price_effective_date":
					if ($product['originalprice'] != $product['price']) {
						$salestartdate = get_post_meta($product['id'],'_salestartdate',1);
						$saleenddate = get_post_meta($product['id'],'_saleenddate',1);
						if ($salestartdate == '999999999999999999') $salestartdate = 0;
						if ($saleenddate == '999999999999999999') $saleenddate = 0;
						$salestartdate = ($salestartdate == 0 ? Date("Y-m-d", strtotime("-1 day")) : Date("Y-m-d", $salestartdate));
						$saleenddate = ($saleenddate == 0 ? Date("Y-m-d", strtotime("+1 year")) : Date("Y-m-d", $saleenddate));
						$write .= foxyshop_dblquotes($salestartdate."/".$saleenddate);
					} else {
						$write .= foxyshop_dblquotes('');
					}
					break;
				case "description":
					$write .= foxyshop_dblquotes(strip_tags($product['description'])); break;

				case "product_type":
					$product_type_write = "";
					$categories = wp_get_post_terms($product['id'], 'foxyshop_categories');
					foreach ($categories as $cat) {
						if ($product_type_write) $product_type_write .= "\n";
						$breadcrumbarray = array_reverse(get_ancestors($cat->term_id, 'foxyshop_categories'));
						foreach ($breadcrumbarray as $crumb) {
							$term = get_term_by('id', $crumb, 'foxyshop_categories');
							$product_type_write .= $term->name . ' > ';
						}
						$product_type_write .= $cat->name;
					}

					$write .= foxyshop_dblquotes($product_type_write); break;

				case "condition":
					$condition = get_post_meta($product['id'],'_condition',1);
					if (!$condition) $condition = "new";
					$write .= foxyshop_dblquotes($condition); break;

				case "image_link":
					$write .= foxyshop_dblquotes(foxyshop_get_main_image()); break;
				case "additional_image_link":
					$additional_images = array();
					$number_of_additional_images = 0;
					foreach($product['images'] AS $product_image) {
						$number_of_additional_images++;
						if ($product_image['featured'] == 0 && $number_of_additional_images <= 10) {
							$additional_images[] = $product_image['thumbnail'];
						}
					}
					$write .= foxyshop_dblquotes(implode(",", $additional_images)); break;

				default:
					$write .= foxyshop_dblquotes(get_post_meta($product['id'], "_" . $fieldname, 1)); break;
			}
			$write .= '"';

			if ($fieldname != $lastfieldname) $write .= "\t";
		}
		$write .= "\n";


	}
	echo $write;
}

function foxyshop_google_product_xml($id, $batch_process = "") {
	global $google_product_field_names, $product, $foxyshop_settings;
	$xml = "";

	if ($batch_process == "DELETE") {
		$xml = "<entry>\n";
		$xml .= "<batch:operation type='DELETE'/>\n";
		$xml .= "<id>https://content.googleapis.com/content/v1/" . $foxyshop_settings['google_product_merchant_id'] . "/items/products/schema/" . $id . "</id>\n";
		$xml .= "</entry>\n";
		return $xml;
	}


	$products = get_posts(array('post_type' => 'foxyshop_product', 'post_status' => "publish", 'p' => $id));
	foreach($products as $singleproduct) {

		//Initialize $product
		$product = foxyshop_setup_product($singleproduct);

		//Setup a few things
		$condition = get_post_meta($product['id'],'_condition',1);
		if (!$condition) $condition = "new";

		$availability = get_post_meta($product['id'],'_availability',1);
		if (!$availability) $availability = "in stock";

		$product_type_write = "";
		$categories = wp_get_post_terms($product['id'], 'foxyshop_categories');
		foreach ($categories as $cat) {
			if ($product_type_write == "") {
				$breadcrumbarray = array_reverse(get_ancestors($cat->term_id, 'foxyshop_categories'));
				foreach ($breadcrumbarray as $crumb) {
					$term = get_term_by('id', $crumb, 'foxyshop_categories');
					$product_type_write .= $term->name . ' > ';
				}
				$product_type_write .= $cat->name;
			}
		}

		if ($product['originalprice'] != $product['price']) {
			$salestartdate = get_post_meta($product['id'],'_salestartdate',1);
			$saleenddate = get_post_meta($product['id'],'_saleenddate',1);
			if ($salestartdate == '999999999999999999') $salestartdate = 0;
			if ($saleenddate == '999999999999999999') $saleenddate = 0;
			$salestartdate = ($salestartdate == 0 ? Date("Y-m-d", strtotime("-1 day")) : Date("Y-m-d", $salestartdate));
			$saleenddate = ($saleenddate == 0 ? Date("Y-m-d", strtotime("+1 year")) : Date("Y-m-d", $saleenddate));
			$sale_price_effective_date = $salestartdate."/".$saleenddate;
		}


		$xml .= '<entry xmlns="http://www.w3.org/2005/Atom"';
		$xml .= " xmlns:app='http://www.w3.org/2007/app'";
		$xml .= ' xmlns:gd="http://schemas.google.com/g/2005"';
		$xml .= ' xmlns:sc="http://schemas.google.com/structuredcontent/2009"';
		$xml .= ' xmlns:scp="http://schemas.google.com/structuredcontent/2009/products" >'."\n";
		$xml .= '<app:control>'."\n";
		$xml .= '<sc:required_destination dest="ProductSearch"/>'."\n";
		$xml .= '</app:control>'."\n";

		if ($batch_process == "UPDATE") {
			$xml .= "<id>https://content.googleapis.com/content/v1/" . $foxyshop_settings['google_product_merchant_id'] . "/items/products/schema/" . $id . "</id>\n";
		}
		if ($batch_process) $xml .= "<batch:operation type='$batch_process'/>\n";
		$xml .= '<title>' . esc_attr(trim($product['name'])) . '</title>'."\n";
		$xml .= '<content type="text">' . esc_attr($product['description']) . '</content>'."\n";
		$xml .= '<sc:id>' . esc_attr($product['id']) . '</sc:id>'."\n";
		$xml .= '<sc:availability>' . esc_attr($availability) . '</sc:availability>'."\n";
		$xml .= '<link rel="alternate" type="text/html" href="' . esc_attr($product['url']) . '"/>'."\n";
		$xml .= '<sc:image_link>' . foxyshop_get_main_image('large') . '</sc:image_link>'."\n";

		//Additional Images
		$number_of_additional_images = 0;
		foreach($product['images'] AS $product_image) {
			$number_of_additional_images++;
			if ($product_image['featured'] == 0 && $number_of_additional_images <= 10) {
				'<sc:additional_image_link>' . $product_image['large'] . '</sc:additional_image_link>'."\n";
			}
		}

		$xml .= '<sc:target_country>US</sc:target_country>'."\n";
		$xml .= '<sc:content_language>en</sc:content_language>'."\n";
		foreach($google_product_field_names as $field) {
			$val = get_post_meta($product['id'],'_'.$field,1);
			if ($field == 'condition') $val = $condition;
			if ($field == 'gtin' && !$val) $val = $product['code'];
			if ($val) $xml .= '<scp:'.$field.'>' . esc_attr($val) . '</scp:'.$field.'>'."\n";
		}
		$xml .= '<scp:availability>in stock</scp:availability>'."\n";
		$xml .= '<scp:price unit="usd">' . $product['originalprice'] . '</scp:price>'."\n";
		if ($product['originalprice'] != $product['price']) {
			$xml .= '<scp:sale_price unit="usd">' . $product['originalprice'] . '</scp:sale_price>'."\n";
			//$xml .= '<scp:sale_price_effective_date">' . $sale_price_effective_date . '</scp:sale_price_effective_date>'."\n";
		}
		if ($product_type_write) $xml .= '<scp:product_type>' . esc_attr($product_type_write) . '</scp:product_type>'."\n";
		$xml .= '</entry>'."\n";
	}
	return $xml;
}

if ($foxyshop_settings['google_product_merchant_id']) {
	add_action('admin_menu', 'foxyshop_google_product_menu');
	add_action('admin_init', 'foxyshop_google_products_act');
}

function foxyshop_google_products_act() {
	if (!isset($_REQUEST['foxyshop_google_products_update'])) return;
	if (!check_admin_referer('gp1')) return;
	global $foxyshop_settings;

	//Logout
	if (isset($_GET['googleprodlogout'])) {
		$foxyshop_settings['google_product_auth'] = "";
		update_option("foxyshop_settings", $foxyshop_settings);
		wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_google_products_page');
		die;
	}

}


function foxyshop_google_product_menu() {
	add_submenu_page('edit.php?post_type=foxyshop_product', __('Google Products', 'foxyshop'), __('Google Products', 'foxyshop'), apply_filters('foxyshop_google_product_perm', 'manage_options'), 'foxyshop_google_products_page', 'foxyshop_google_products_page');
}
function foxyshop_google_products_page() {
	global $foxyshop_settings, $product;
	$local_products = array();
	$google_icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEgAACxIB0t1+/AAAABh0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzT7MfTgAAABZ0RVh0Q3JlYXRpb24gVGltZQAwNy8xNS8xMMjfMS0AAAQRdEVYdFhNTDpjb20uYWRvYmUueG1wADw/eHBhY2tldCBiZWdpbj0iICAgIiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+Cjx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDQuMS1jMDM0IDQ2LjI3Mjk3NiwgU2F0IEphbiAyNyAyMDA3IDIyOjM3OjM3ICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4YXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iPgogICAgICAgICA8eGFwOkNyZWF0b3JUb29sPkFkb2JlIEZpcmV3b3JrcyBDUzM8L3hhcDpDcmVhdG9yVG9vbD4KICAgICAgICAgPHhhcDpDcmVhdGVEYXRlPjIwMTAtMDctMTVUMTk6MDU6MDFaPC94YXA6Q3JlYXRlRGF0ZT4KICAgICAgICAgPHhhcDpNb2RpZnlEYXRlPjIwMTAtMDctMTVUMTk6MTg6MDBaPC94YXA6TW9kaWZ5RGF0ZT4KICAgICAgPC9yZGY6RGVzY3JpcHRpb24+CiAgICAgIDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiCiAgICAgICAgICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyI+CiAgICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2UvcG5nPC9kYzpmb3JtYXQ+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDBS8igAAAJlSURBVDiNfY9PSFRhFMV/33uvcRzfzKijmQr5jyzFciGFldlGiRCCoAIhqFy0CdpE0Sao3Ei1CtdBuAjDhdBSQiw3SaM5IBpkJTlRaprydJznvO+2sNRJ68CBy733nHuuisfjLVNf4k+fReciI1+TWCa0HLA5VhIAwHOcBR48sMyVlSC/IUohhhHLTCTOWfPz80+GZndFuqdCaAxYg/GYoqd2L1UFGQA50tCwRGfnHz3aMHBs+9DP7OxuKxAI7P64Ksx5gqDWNzx49CZJ75Wi9Yv19SF97x5bEwQcB0+pWsu2bUIBF9GarXg37WwKEgm0UmlzXzJJ1vKyYZimSWNFELSXRncttRl5eJj56hKGT1cxfLqKlXAmIoLyPCyA5v1hmitt+t4vbogayrI36ktHRni+bxpPvPXGBQiuMna7z3LNoqard+srcjlTk8v3pSSLiRSNFUEenysn5DfpmuyifeIhIpL2gmuxe7TMX6jybg7KxeOlXD+RT1mub3NBu7S/a6cj1rF5+S9E1iKovBsD8kNnbTT7r1VysiJIa38r3Z+6dxRuGKQiGOgUWzk05eBql5qcGsJWGDT/pSq79VLOHy3nVHUO4UyT4rCPPaH1V+LLcdoG2uib7vtnAhWb/CoHywsBEOcDOB/AXUAVnwXTj6tdml40MfhtcGeDufiE5Cz1IrOv06f+AozqO6isUqKzUQ73HN5u4EUw9HgHMvMKRNKZ+IYeuw+Soi6/DtuyQdhGQ7tLo6Jlu8FvE3E+A+BpL02oRKFEvTWUyOVkyoztaGL4Uf4CojNREmuJNHGGlzHk077WXxkiNLGzmnadAAAAAElFTkSuQmCC";
	$debug_querystring = isset($_GET['debug']) ? "&amp;debug=1" : "";

	?>
	<?php
	if (!$foxyshop_settings['google_product_auth']) {
		?>
		<div class="wrap">
		<div class="icon32" id="icon-tools"><br></div>
		<h2>Google Products Management - Authentication Required</h2>

		<table class="widefat" style="margin-top: 14px;">
			<thead>
				<tr>
					<th><img src="<?php echo $google_icon; ?>" alt="" /><?php _e("Google Authentication Required", 'foxyshop'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<p>To view and manage your Google Products you need to log in to your Google Account. Your username and password are not saved in our system, but are passed directly to Google for authentication. If authenticated, Google will issue an auth key that will be stored with your store settings. This auth key will expire after two weeks at which point you will need to authenticate again.</p>

						<form onsubmit="return false;" autocomplete="off" style="display: block; margin-bottom: 10px;">

						<div class="foxyshop_field_control">
							<label for="Email"><?php _e('Email', 'foxyshop'); ?></label>
							<input type="text" id="Email" name="Email" value="" />
						</div>

						<div class="foxyshop_field_control">
							<label for="Passwd"><?php _e('Password', 'foxyshop'); ?></label>
							<input type="password" id="Passwd" name="Passwd" value="" />
						</div>

						<div style="clear: both; height: 4px;"></div>

						<button class="button-primary" id="authnow"><?php _e('Authenticate Now', 'foxyshop'); ?></button>
						</form>

						<div id="error" style="color: red; margin-top: 10px; font-weight: bold; display: none;"></div>


						<script type="text/javascript">
						jQuery(document).ready(function($){

							$("#authnow").click(function() {
								var data = {
									action: 'foxyshop_set_google_auth',
									security: '<?php echo wp_create_nonce("foxyshop-ajax-set-google-auth"); ?>',
									Email: $("#Email").val(),
									Passwd: $("#Passwd").val()
								};
								$("#error").hide();
								$.post(ajaxurl, data, function(response) {
									if (!response) {
										$("#error").text("Error: No Response").show();
									} else if (response == "Error") {
										$("#error").text("Error: Login Failed. Please try again.").show();
									} else if (response == "Success") {
										$("#error").hide();
										location.reload();
									} else {
										$("#error").text("Error: " + response).show();
									}
								});
							});
						});
						</script>


					</td>
				</tr>
			</tbody>
		</table>


	<?php
	} else {


		?>
		<div class="wrap">
		<div class="icon32" id="icon-tools"><br></div>
		<h2>Google Products Management <a class="<?php if (version_compare(get_bloginfo('version'), '3.2', "<")) echo "button "; ?>add-new-h2" href="edit.php?post_type=foxyshop_product&amp;page=foxyshop_google_products_page&amp;foxyshop_google_products_update=1&amp;googleprodlogout=1&amp;_wpnonce=<?php echo wp_create_nonce('gp1'); ?>">De-Authenticate</a></h2>
		<?php

		//Display Confirmations and Errors
		if (isset($_GET['error'])) {
			echo '<div class="error"><p><strong>Error!</strong><br /><ul style="margin: 0 10px;">';
			$error_list = explode("||", $_GET['error']);
			foreach($error_list as $the_error) {
				if ($the_error) echo "<li style=\"list-style: disc inside none;\">$the_error</li>\n";
			}
			echo '</ul></p></div>';
		} elseif (isset($_GET['success'])) {
			echo '<div class="updated"><p>' . __('Operation completed successfully.', 'foxyshop') . '</p></div>';
		}


		//Get All Local Product ID's
		$args = array('post_type' => 'foxyshop_product', 'post_status' => 'publish', 'numberposts' => "-1", "orderby" => "id", "order" => "ASC", "meta_key" => "_google_product_category", "meta_compare" => "!=", "meta_value" => "");
		$product_list = get_posts($args);
		foreach ($product_list as $single_product) {
			$local_products[] = $single_product->ID;
		}

		//Get Feed
		$header_array = array(
			"Authorization: GoogleLogin auth=" . $foxyshop_settings['google_product_auth']
		);

		$url = "https://content.googleapis.com/content/v1/" . $foxyshop_settings['google_product_merchant_id'] . "/items/products/schema?performance.start=" . date("Y-m-d", strtotime("-30 days")) . "&max-results=250&performance.end=" . date("Y-m-d", strtotime("now"));
		if (isset($_GET['nextlink'])) $url = $_GET['nextlink'];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$response = trim(curl_exec($ch));
		$xml = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response);
		$xml = simplexml_load_string($xml, NULL, LIBXML_NOCDATA);

		//Check For Next Link
		$nextlink = "";
		foreach($xml->link as $link) {
			$thisone = 0;
			foreach($link->attributes() as $a => $b) {
				if ($a == "rel" && $b == "next") $thisone = 1;
				if ($thisone && $a == "href") $nextlink = $b;
			}
		}

		//Token Has Expired, Remove and Restart Authentication
		if ((string)$xml->BODY->H1 == "Token invalid" || (string)$xml->BODY->H1 == "Token expired") {
			$foxyshop_settings['google_product_auth'] = "";
			update_option("foxyshop_settings", $foxyshop_settings);
			$local_products = array();

			echo '<div class="updated"><p>' . __('Authentication Failed. It appears that your authentication has expired. This happens every two weeks. Please login again.', 'foxyshop') . '</p></div>';
			echo '<p><a href="edit.php?post_type=foxyshop_product&amp;page=foxyshop_google_products_page" class="button">' . __('Login Now', 'foxyshop') . '</a></p>';


		} else {
		?>

		<form action="edit.php?post_type=foxyshop_product&page=foxyshop_google_products_page&foxyshop_manage_google_feed=1" method="post">

		<table cellpadding="0" cellspacing="0" border="0" class="wp-list-table widefat foxyshop-list-table" id="product_feed_view" style="margin-top: 14px;">
			<thead>
				<tr>
					<th id="cb" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<th class="column-id"><span><?php _e('ID', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Name', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Image', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Expiration', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Performance', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column column-cb check-column" style="" scope="col"><input type="checkbox"></th>
					<th><?php _e('ID', 'foxyshop'); ?></th>
					<th><?php _e('Name', 'foxyshop'); ?></th>
					<th><?php _e('Image', 'foxyshop'); ?></th>
					<th><?php _e('Expiration', 'foxyshop'); ?></th>
					<th><?php _e('Performance', 'foxyshop'); ?></th>
				</tr>
			</tfoot>
			<tbody>
		<?php

		if (count($xml->entry) == 0) {
			echo '<tr class="no-items"><td colspan="6" class="colspanchange">No posts found.</td></tr>';
		}
		foreach($xml->entry as $entry) {
			$expiration_date = (string)$entry->scexpiration_date;
			$expiration_date = substr($expiration_date,0,strpos($expiration_date,"T"));
			$clicks = 0;
			foreach($entry->scperformance->scdatapoint as $scdatapoint) {
				$clicks += $scdatapoint->attributes()->clicks;
			}
			$google_product_id = (string)$entry->scid;


			if (in_array($google_product_id, $local_products)) {
				$local_products = array_diff($local_products, array($google_product_id));
				$unmatched_text = "";
			} else {
				$unmatched_text = "<br /><em>" . __('Unmatched!', 'foxyshop') . "</em>";
			}

			echo '<tr>'."\n";
			echo '<th class="check-column" scope="row"><input type="checkbox" value="' . $google_product_id . '" name="post[]"></th>'."\n";
			echo '<td><strong>' . $google_product_id . '</strong>' . $unmatched_text . '</td>'."\n";
			if ($unmatched_text == "") {
				echo '<td><strong><a href="post.php?post=' . $google_product_id . '&action=edit">' . (string)$entry->title . '</a></strong>';
				echo '<div class="row-actions">';
				echo '<span><a href="edit.php?foxyshop_manage_google_feed=1&amp;editid=' . $google_product_id . $debug_querystring . '&amp;_wpnonce=' . wp_create_nonce("manage-the-google-feed-settings") . '" class="update_google_product" rel="' . $google_product_id . '">Renew/Update</a> | </span>';
				echo '<span class="delete"><a href="edit.php?foxyshop_manage_google_feed=1&amp;deleteid=' . $google_product_id . $debug_querystring . '&amp;_wpnonce=' . wp_create_nonce("manage-the-google-feed-settings") . '" class="delete_google_product" rel="' . $google_product_id . '">Delete</a></span>';
				echo '</div>';
				echo '</td>'."\n";
			} else {
				echo '<td><strong><a href="#" onclick="return false;">' . (string)$entry->title . '</a></strong>';
				echo '<div class="row-actions">';
				echo '<span class="delete"><a href="edit.php?foxyshop_manage_google_feed=1&amp;deleteid=' . $google_product_id . $debug_querystring . '&amp;_wpnonce=' . wp_create_nonce("manage-the-google-feed-settings") . '" class="delete_google_product" rel="' . $google_product_id . '">Delete</a></span>';
				echo '</div>';
				echo '</td>'."\n";
			}

			if ((string)$entry->scimage_link) {
				echo '<td><img src="' . (string)$entry->scimage_link . '" class="productfeedimage" /></td>'."\n";
			} else {
				echo '<td>&nbsp;</td>'."\n";
			}
			echo '<td>' . $expiration_date . '</td>'."\n";
			echo '<td>' . $clicks . ' Click' . ($clicks != 1 ? 's' : '') . '</td>'."\n";
			echo '</tr>'."\n";



		}
			?>
			</tbody>
		</table>
		<div style="padding-top: 10px;">
			<button type="submit" class="button" name="update_checked_google_products" value="1" id="update_checked_google_products">Update/Renew Checked <?php echo FOXYSHOP_PRODUCT_NAME_PLURAL; ?></button>
			&nbsp;&nbsp;&nbsp;
			<button type="submit" class="button" name="delete_checked_google_products" value="1" id="delete_checked_google_products">Delete Checked <?php echo FOXYSHOP_PRODUCT_NAME_PLURAL; ?></button>

			<?php if ($nextlink) {
				echo '<a href="edit.php?post_type=foxyshop_product&page=foxyshop_google_products_page&nextlink=' . urlencode($nextlink) . '" class="button" style="float: right;">Next Page</a>';
			} ?>

		</div>
		<input type="hidden" name="foxyshop_run_the_xml" value="1" />
		<?php wp_nonce_field('manage-the-google-feed-settings'); ?>
		<?php if (isset($_GET['debug'])) echo '<input type="hidden" name="debug" value="1" />'; ?>
		</form>
		<?php
		}
	}
	?>


	<?php
	$meta_query = array(
		'relation' => 'OR',
		array(
			'key' => '_google_product_category',
			'value' => "",
			'compare' => '!='
		)
	);
	$args = array('post_type' => 'foxyshop_product', 'post_status' => 'publish', 'numberposts' => "-1", "orderby" => "id", "order" => "ASC", "meta_query" => $meta_query);
	$product_list = get_posts($args);
	if ($product_list) {

	?>
		<h2 style="padding: 100px 0 0 0;">Available, Unmatched <?php echo FOXYSHOP_PRODUCT_NAME_PLURAL; ?> to Add</h2>
		<p style="margin: 0;">In order to appear in this list, <?php echo strtolower(FOXYSHOP_PRODUCT_NAME_PLURAL); ?> must have a "Google Product Category" attribute.</p>

		<form action="edit.php?post_type=foxyshop_product&page=foxyshop_google_products_page&foxyshop_manage_google_feed=1" method="post">

		<table cellpadding="0" cellspacing="0" border="0" class="wp-list-table widefat foxyshop-list-table" id="available_product_view" style="margin-top: 14px;">
			<thead>
				<tr>
					<th id="cb" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<th class="column-id"><span><?php _e('ID', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Name', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Code', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Image', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Price', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Date', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column column-cb check-column" style="" scope="col"><input type="checkbox"></th>
					<th><?php _e('ID', 'foxyshop'); ?></th>
					<th><?php _e('Name', 'foxyshop'); ?></th>
					<th><?php _e('Code', 'foxyshop'); ?></th>
					<th><?php _e('Image', 'foxyshop'); ?></th>
					<th><?php _e('Price', 'foxyshop'); ?></th>
					<th><?php _e('Data', 'foxyshop'); ?></th>
				</tr>
			</tfoot>
			<tbody>
		<?php

		$none_available = 1;
		foreach ($product_list as $single_product) {
			$product = foxyshop_setup_product($single_product);
			$google_product_listed = (int)get_post_meta($product['id'],'_google_product_listed',TRUE);
			if ($google_product_listed > strtotime("now")) continue;
			$none_available = 0;

			$salestartdate = get_post_meta($product['id'],'_salestartdate',TRUE);
			$saleenddate = get_post_meta($product['id'],'_saleenddate',TRUE);
			if ($salestartdate == '999999999999999999') $salestartdate = 0;
			if ($saleenddate == '999999999999999999') $saleenddate = 0;
			$originalprice = $product['originalprice'];
			$saleprice = get_post_meta($product['id'],'_saleprice', true);

			if ($saleprice > 0) {
				$beginningOK = (strtotime("now") > $salestartdate);
				$endingOK = (strtotime("now") < ($saleenddate + 86400) || $saleenddate == 0);
				if ($beginningOK && $endingOK || ($salestartdate == 0 && $saleenddate == 0)) {
					$pricewrite = '<span style="text-decoration: line-through; margin-right: 10px;">' . foxyshop_currency($originalprice) . '</span><span style="color: red;">' . foxyshop_currency($saleprice) . '</span>';
				} else {
					$pricewrite = foxyshop_currency($originalprice);
				}
			} else {
				$pricewrite = foxyshop_currency($originalprice);
			}


			echo '<tr>'."\n";
			echo '<th class="check-column" scope="row"><input type="checkbox" value="' .$product['id'] . '" name="post[]"></th>'."\n";
			echo '<td><strong>' . $product['id'] . '</strong></td>'."\n";

			echo '<td><strong><a href="post.php?post=' . $product['id'] . '&action=edit">' . $product['name'] . '</a></strong>';
			echo '<div class="row-actions">';
			echo '<span><a href="edit.php?foxyshop_manage_google_feed=1&amp;addid=' . $product['id'] . $debug_querystring . '&amp;_wpnonce=' . wp_create_nonce("manage-the-google-feed-settings") . '" class="insert_google_product" rel="' . $google_product_id . '">Add To Google Products Feed</a></span>';
			echo '</div>';
			echo '</td>'."\n";

			echo '<td>' . $product['code'] . '</td>'."\n";
			echo '<td><img src="' . foxyshop_get_main_image() . '" class="productfeedimage" /></td>'."\n";
			echo '<td>' . $pricewrite . '</td>'."\n";
			echo '<td>' . Date("Y-m-d", strtotime($single_product->post_date)) . '</td>'."\n";
			echo '</tr>'."\n";



		}
		if ($none_available) {
			echo '<tr><td colspan="7"><em>No ' . FOXYSHOP_PRODUCT_NAME_PLURAL . ' Available.</em></td></tr>'."\n";
		}
			?>
			</tbody>
		</table>
		<input type="hidden" name="foxyshop_run_the_xml" value="1" />
		<?php wp_nonce_field('manage-the-google-feed-settings'); ?>
		<?php if (isset($_GET['debug'])) echo '<input type="hidden" name="debug" value="1" />'; ?>
		<div style="padding-top: 10px;">
			<button type="submit" class="button" name="add_checked_google_products" value="1" id="add_checked_google_products">Add Checked <?php echo FOXYSHOP_PRODUCT_NAME_PLURAL; ?> to Google</button>
		</div>
		</form>




	<?php }
	?>

	<br /><br /><br /><br /><br /><br />

	<table class="widefat">
		<thead>
			<tr>
				<th><img src="<?php echo $google_icon; ?>" alt="" /><?php _e("Create Manual Export File", 'foxyshop'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<p>If you would like to <a href="http://www.google.com/merchants" target="_blank">submit your products to Google</a>, you may do so by creating a product feed using this tool. Make sure that you check the option that <a href="http://www.google.com/support/merchants/bin/answer.py?answer=160037" target="_blank">enables double quotes.</a> You also need to make sure that the '_google_product_category' custom field is filled out for each product.</p>
					<p><a href="edit.php?post_type=foxyshop_product&amp;create_google_product_feed=1" class="button-primary">Create Google Product Feed</a></p>
				</td>
			</tr>
		</tbody>
	</table>




	</div>



<script type="text/javascript" src="<?php echo FOXYSHOP_DIR; ?>/js/jquery.tablesorter.js"></script>
<script type="text/javascript">
jQuery(document).ready(function($){
	$("#product_feed_view").tablesorter({
		'cssDesc': 'asc sorted',
		'cssAsc': 'desc sorted',
		'headers': { 0: { sorter: false} }
	});
});
</script>

	<?php

}


if (isset($_GET['foxyshop_manage_google_feed'])) add_action('admin_init', 'foxyshop_manage_google_feed');
function foxyshop_manage_google_feed() {
	if (!check_admin_referer('manage-the-google-feed-settings')) return;
	global $foxyshop_settings;
	$url = 'https://content.googleapis.com/content/v1/' . $foxyshop_settings['google_product_merchant_id'] . '/items/products/schema/batch?dry-run';
	$url = 'https://content.googleapis.com/content/v1/' . $foxyshop_settings['google_product_merchant_id'] . '/items/products/schema/batch?';
	$header_array = array(
		"Authorization: GoogleLogin auth=" . $foxyshop_settings['google_product_auth'],
		"Content-Type: application/atom+xml"
	);
	$error = "";
	$xml = "";

	//Add Items in Bulk
	if (isset($_POST['add_checked_google_products']) && isset($_POST['post'])) {
		$posts = $_POST['post'];
		if (!is_array($posts)) $posts = array();
		foreach ($posts as $post) {
			$xml .= foxyshop_google_product_xml($post, "INSERT");
			update_post_meta($post, '_google_product_listed', strtotime('+30 days'));
		}


	//Update Items in Bulk
	} elseif (isset($_POST['update_checked_google_products']) && isset($_POST['post'])) {
		$posts = $_POST['post'];
		if (!is_array($posts)) $posts = array();
		foreach ($posts as $post) {
			$xml .= foxyshop_google_product_xml($post, "UPDATE");
			update_post_meta($post, '_google_product_listed', strtotime('+30 days'));
		}

	//Delete Items in Bulk
	} elseif (isset($_POST['delete_checked_google_products']) && isset($_POST['post'])) {
		$posts = $_POST['post'];
		if (!is_array($posts)) $posts = array();
		foreach ($posts as $post) {
			$xml .= foxyshop_google_product_xml($post, "DELETE");
			delete_post_meta($post, '_google_product_listed');
		}



	//Add Single Item
	} elseif (isset($_GET['addid'])) {

		$xml = foxyshop_google_product_xml((int)$_GET['addid'], "INSERT");
		update_post_meta((int)$_GET['addid'], '_google_product_listed', strtotime('+30 days'));


	//Update Single Item
	} elseif (isset($_GET['editid'])) {

		$xml = foxyshop_google_product_xml((int)$_GET['editid'], "UPDATE");
		update_post_meta((int)$_GET['editid'], '_google_product_listed', strtotime('+30 days'));


	//Delete Single Item
	} elseif (isset($_GET['deleteid'])) {

		$xml = foxyshop_google_product_xml((int)$_GET['deleteid'], "DELETE");
		delete_post_meta((int)$_GET['deleteid'], '_google_product_listed');
	}


	//Run Batch
	if ($xml) {

		$writexml = "<?xml version='1.0' encoding='UTF-8'?>\n";
		$writexml .= "<feed xmlns='http://www.w3.org/2005/Atom' xmlns:batch='http://schemas.google.com/gdata/batch'>\n";
		$writexml .= $xml;
		$writexml .= '</feed>';
		if (isset($_REQUEST['debug'])) {
			echo "<form><button type=\"button\" onclick=\"location.href = 'edit.php?post_type=foxyshop_product&page=foxyshop_google_products_page&success=1&debug=1$error';\">Continue</button></form>";
			echo "<b>Submitted XML:</b><br /><br /><form><textarea style=\"width: 80%; height: 350px;\">$writexml</textarea></form><br /><br />";
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $writexml);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$response = trim(curl_exec($ch));
		$xml = $response;
		//$xml = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $xml);
		$xml = simplexml_load_string($xml, NULL, LIBXML_NOCDATA);

		if (strpos('Error', $xml->entry->title) !== false) {
			foreach($xml->entry as $entry) {
				foreach($entry->content->gderrors->gderror as $gderror) {
					$error .= (string)$gderror->gdinternalReason;
					if ((string)$gderror->gdlocation) $error .= " (" . (string)$gderror->id . ', ' . (string)$gderror->gdlocation  . ")";
					$error .= "||";
				}
			}
			$error = "&error=" . urlencode($error);
		}

		if (isset($_REQUEST['debug'])) {
			if ($error) echo "<b>Error: </b> $error<br /><br />";
			echo "<b>Returned Data:</b><br /><br />";

			echo "<form><textarea style=\"width: 80%; height: 350px;\">";
			print_r($xml);
			echo "</textarea></form><br /><br />";

			echo "<b>Returned Data (raw XML):</b><br /><br />";
			echo "<form><textarea style=\"width: 80%; height: 350px;\">" . $response;
			echo "</textarea></form>";

			echo "<br /><form><button type=\"button\" onclick=\"location.href = 'edit.php?post_type=foxyshop_product&page=foxyshop_google_products_page&success=1&debug=1$error';\">Continue</button></form>";
			die;
		}

		wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_google_products_page&success=1'.$error);
		die;

	}

	wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_google_products_page&error='. urlencode("Nothing to do. Unmatched products cannot be updated or renewed."));
	die;
}
