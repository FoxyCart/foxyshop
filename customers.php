<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

add_action('admin_menu', 'foxyshop_customer_management_menu');
function foxyshop_customer_management_menu() {
	add_submenu_page('edit.php?post_type=foxyshop_product', __('Customer Management', 'foxyshop'), __('Customers', 'foxyshop'), apply_filters('foxyshop_customer_perm', 'manage_options'), 'foxyshop_customer_management', 'foxyshop_customer_management');
}

function foxyshop_customer_management() {
	global $foxyshop_settings, $wp_version;

	//Setup Fields and Defaults
	$foxy_data_defaults = array(
		"customer_id_filter" => "",
		"customer_email_filter" => "",
		"customer_first_name_filter" => "",
		"customer_last_name_filter" => "",
		"customer_state_filter" => ""
	);
	if (version_compare($foxyshop_settings['version'], '0.7.2', ">=")) {
		$foxy_data_defaults["custom_field_name_filter"] = "";
		$foxy_data_defaults["custom_field_value_filter"] = "";
	}
	$foxy_data = wp_parse_args(array("api_action" => "customer_list"), apply_filters('foxyshop_customer_filter_defaults',$foxy_data_defaults));
	$foxyshop_querystring = "?post_type=foxyshop_product&amp;page=foxyshop_customer_management&amp;foxyshop_search=1";
	$foxyshop_hidden_input = "";

	if (isset($_GET['foxyshop_search'])) {
		$fields = array("customer_id_filter", "customer_email_filter", "customer_first_name_filter", "customer_last_name_filter", "customer_state_filter", "custom_field_name_filter", "custom_field_value_filter");
		foreach ($fields as $field) {
			if (isset($_GET[$field])) {
				$foxy_data[$field] = $_GET[$field];
				$foxyshop_querystring .= "&amp;$field=" . urlencode($_GET[$field]);
				$foxyshop_hidden_input .= '<input type="hidden" name="' . $field . '" value="' . htmlspecialchars($_GET[$field]) . '" />' . "\n";
			}
		}
		$foxy_data['pagination_start'] = (isset($_GET['pagination_start']) ? $_GET['pagination_start'] : 0);
		$p = (int)(version_compare($foxyshop_settings['version'], '0.7.1', "<") ? 50 : FOXYSHOP_API_ENTRIES_PER_PAGE);
		if (version_compare($foxyshop_settings['version'], '0.7.0', ">")) $foxy_data['entries_per_page'] = $p;
		$start_offset = (int)(version_compare($foxyshop_settings['version'], '0.7.1', "<=") ? -1 : 0);
		if (isset($_GET['paged-top']) || isset($_GET['paged-bottom'])) {
			if ($_GET['paged-top'] != $_GET['paged-top-original']) $foxy_data['pagination_start'] = $p * ((int)$_GET['paged-top'] - 1) + 1 + $start_offset;
			if ($_GET['paged-bottom'] != $_GET['paged-bottom-original']) $foxy_data['pagination_start'] = $p * ((int)$_GET['paged-bottom'] - 1) + 1 + $start_offset;
		}
	}

	?>
	<div class="wrap">
		<div class="icon32" id="icon-users"><br></div>
		<h2><?php _e('Manage Customers', 'foxyshop'); ?></h2>

		<form action="edit.php" method="get" id="foxyshop_searchform" name="foxyshop_searchform" style="display: block; margin: 14px 0 20px 0;">
		<input type="hidden" name="foxyshop_search" value="1" />
		<input type="hidden" name="post_type" value="foxyshop_product" />
		<input type="hidden" name="page" value="foxyshop_customer_management" />

		<table class="widefat">
		<thead><tr><th colspan="2"><img src="<?php echo FOXYSHOP_DIR; ?>/images/search-icon.png" alt="" /><?php _e('Search Options', 'foxyshop'); ?></th></tr></thead>
		<tbody><tr><td>

			<div class="foxyshop_field_control">
				<label for="customer_id_filter"><?php _e('Customer ID', 'foxyshop'); ?></label><input type="text" name="customer_id_filter" id="customer_id_filter" value="<?php echo $foxy_data['customer_id_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="customer_first_name_filter"><?php _e('Customer First Name', 'foxyshop'); ?></label><input type="text" name="customer_first_name_filter" id="customer_first_name_filter" value="<?php echo $foxy_data['customer_first_name_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="customer_last_name_filter"><?php _e('Customer Last Name', 'foxyshop'); ?></label><input type="text" name="customer_last_name_filter" id="customer_last_name_filter" value="<?php echo $foxy_data['customer_last_name_filter']; ?>" />
			</div>
			<?php if (version_compare($foxyshop_settings['version'], '0.7.2', ">=")) { ?>
			<div class="foxyshop_field_control">
				<label for="custom_field_name_filter"><?php _e('Custom Field Name', 'foxyshop'); ?></label><input type="text" name="custom_field_name_filter" id="custom_field_name_filter" value="<?php echo $foxy_data['custom_field_name_filter']; ?>" />
				<label for="custom_field_value_filter" style="margin-left: 15px; margin-top: 4px; width: 34px;"><?php _e('Value', 'foxyshop'); ?></label><input type="text" name="custom_field_value_filter" id="custom_field_value_filter" value="<?php echo $foxy_data['custom_field_value_filter']; ?>" />
			</div>
			<?php } ?>
		</td><td>
			<div class="foxyshop_field_control">
				<label for="customer_email_filter"><?php _e('Customer Email', 'foxyshop'); ?></label><input type="text" name="customer_email_filter" id="customer_email_filter" value="<?php echo $foxy_data['customer_email_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="customer_state_filter"><?php _e('Customer State', 'foxyshop'); ?></label><input type="text" name="customer_state_filter" id="customer_state_filter" value="<?php echo $foxy_data['customer_state_filter']; ?>" />
			</div>

			<div style="clear: both;"></div>
			<button type="submit" id="foxyshop_search_submit" name="foxyshop_search_submit" class="button-primary" style="clear: left; margin: 10px 0 6px 0;"><?php _e('Search Records Now', 'foxyshop'); ?></button>
			<button type="button" class="button" style="margin-left: 15px; margin-top: 10px;" onclick="document.location.href = 'edit.php?post_type=foxyshop_product&page=foxyshop_customer_management';"><?php _e('Reset Form', 'foxyshop'); ?></button>

		</td></tr></tbody></table>
		</form>

	<?php
	if (!isset($_GET['foxyshop_search'])) return;
	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
	//print_r($foxy_data);
	//echo "<pre>" . substr($foxy_response,1,2000) . "</pre>";

	if ((string)$xml->result == "ERROR") {
		echo '<h3>' . (string)$xml->messages->message . '</h3>';
		return;
	} else {
		?>

		<form action="edit.php" method="get">
		<input type="hidden" name="foxyshop_search" value="1" />
		<input type="hidden" name="post_type" value="foxyshop_product" />
		<input type="hidden" name="page" value="foxyshop_customer_management" />

		<?php
		echo $foxyshop_hidden_input;
		foxyshop_api_paging_nav('customers', 'top', $xml, $foxyshop_querystring);
		?>


		<table cellpadding="0" cellspacing="0" border="0" class="wp-list-table widefat foxyshop-list-table" id="customer_table">
			<thead>
				<tr>
					<th><span><?php _e('Customer ID', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Last Name', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('First Name', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Email', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Orders', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<?php if ($foxyshop_settings['enable_subscriptions']) echo "<th><span>" . __('Subscriptions', 'foxyshop') . "</span><span class=\"sorting-indicator\"></span></th>\n"; ?>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th><?php _e('Customer ID', 'foxyshop'); ?></th>
					<th><?php _e('Last Name', 'foxyshop'); ?></th>
					<th><?php _e('First Name', 'foxyshop'); ?></th>
					<th><?php _e('Email', 'foxyshop'); ?></th>
					<th><?php _e('Orders', 'foxyshop'); ?></th>
					<?php if ($foxyshop_settings['enable_subscriptions']) echo "<th>" . __('Subscriptions', 'foxyshop') . "</th>\n"; ?>
				</tr>
			</tfoot>
			<tbody>

		<?php
		$holder = "";
		foreach($xml->customers->customer as $customer) {
			$customer_id = (string)$customer->customer_id;
			$customer_first_name = (string)$customer->customer_first_name;
			$customer_last_name = (string)$customer->customer_last_name;
			$customer_email = (string)$customer->customer_email;

			$last_modified_date = (string)$customer->last_modified_date;
			$last_modified_date = date(apply_filters("foxyshop_date_time_format", "Y-m-d H:i"), strtotime($last_modified_date));

			echo '<tr rel="' . $customer_id . '">';
			echo '<td><strong><a href="#" class="view_detail">' . (string)$customer_id . '</a></strong></td>';
			echo '<td>' . (string)$customer_last_name . '</td>';
			echo '<td>' . (string)$customer_first_name . '</td>';
			echo '<td>' .(string) $customer_email . '</td>';
			echo '<td><a href="edit.php?post_type=foxyshop_product&page=foxyshop_order_management&customer_id_filter=' . (string)$customer->customer_id . '&transaction_date_filter_begin=&transaction_date_filter_end=&hide_transaction_filter=&foxyshop_search=1">' . __('Orders', 'foxyshop') . '</a></td>';
			if ($foxyshop_settings['enable_subscriptions']) echo '<td><a href="edit.php?post_type=foxyshop_product&page=foxyshop_subscription_management&customer_id_filter=' . (string)$customer->customer_id . '&start_date_filter_begin=&start_date_filter_end=&&foxyshop_search=1">' . __('Subscriptions', 'foxyshop') . '</a></td>';
			echo '</tr>'."\n";


			$holder .= '<div class="detail_holder" id="holder_' . $customer_id. '">'."\n";

			//Customer Details
			$holder .= '<div class="foxyshop_list_col">';
			$holder .= '<h4>' . __('Customer Details', 'foxyshop') . '</h4>';
			$holder .= '<ul>';
			if ((string)$customer->customer_phone != "") $holder .= '<li>' . (string)$customer->customer_phone . '</li>';
			$holder .= '<li><a href="mailto:' . $customer->customer_email . '">' . (string)$customer->customer_email . '</a></li>';
			if ((string)$customer->cc_number != "") $holder .= '<li>' . __('Card', 'foxyshop') . ': ' . (string)$customer->cc_number . '</li>'; // 0.7.1 and lower
			if ((string)$customer->cc_number_masked != "") $holder .= '<li>' . __('Card', 'foxyshop') . ': ' . (string)$customer->cc_number_masked . '</li>'; //0.7.2+
			if ((string)$customer->cc_exp_month != "") $holder .= '<li>' . __('Exp', 'foxyshop') . ': ' . (string)$customer->cc_exp_month . '-' . (string)$customer->cc_exp_year . '</li>';
			$holder .= '<li>' . __('Last Modified', 'foxyshop') . ': ' . $last_modified_date . '</li>';
			$holder .= '<li>&nbsp;</li>';

			$holder .= '</ul>';
			$holder .= '</div>';

			//Customer Address
			$holder .= '<div class="foxyshop_list_col">';
			$holder .= '<h4>' . __('Customer Address', 'foxyshop') . '</h4>';
			$holder .= '<ul>';
			$holder .= '<li>' . (string)$customer->customer_first_name . ' ' . (string)$customer->customer_last_name . '</li>';
			if ((string)$customer->customer_company != "") $holder .= '<li>' . (string)$customer->customer_company . '</li>';
			if ((string)$customer->customer_address1 != "") $holder .= '<li>' . (string)$customer->customer_address1 . '</li>';
			if ((string)$customer->customer_address2 != "") $holder .= '<li>' . (string)$customer->customer_address2 . '</li>';
			if ((string)$customer->customer_city != "") $holder .= '<li>' . (string)$customer->customer_city . ', ' . (string)$customer->customer_state . ' ' . (string)$customer->customer_postal_code . '</li>';
			if ((string)$customer->customer_country != "") $holder .= '<li>' . (string)$customer->customer_country . '</li>';
			$holder .= '</ul>';
			$holder .= '</div>';

			//Shipping Addresses (if entered)
			if ((string)$customer->shipping_first_name != "") {
				$holder .= '<div class="foxyshop_list_col">';
				$holder .= '<h4>' . __('Shipping Details', 'foxyshop') . '</h4>';
				$holder .= '<ul>';
				$holder .= '<li>' . (string)$customer->shipping_first_name . ' ' . (string)$customer->shipping_last_name . '</li>';
				if ((string)$customer->shipping_company != "") $holder .= '<li>' . (string)$customer->shipping_company . '</li>';
				if ((string)$customer->shipping_address1 != "")$holder .= '<li>' . $customer->shipping_address1 . '</li>';
				if ((string)$customer->shipping_address2 != "") $holder .= '<li>' . (string)$customer->shipping_address2 . '</li>';
				if ((string)$customer->shipping_city != "")$holder .= '<li>' . (string)$customer->shipping_city . ', ' . (string)$customer->shipping_state . ' ' . (string)$customer->shipping_postal_code . '</li>';
				if ((string)$customer->shipping_country != "")$holder .= '<li>' . (string)$customer->shipping_country . '</li>';
				if ((string)$customer->shipping_phone != "") $holder .= '<li>' . (string)$customer->shipping_phone . '</li>';
				$holder .= '</ul>';
				$holder .= '</div>';
			}

			//Multi-ship Addresses
			foreach($customer->shipto_addresses->shipto_address as $shipto_address) {
				$holder .= '<div class="foxyshop_list_col">';
				$holder .= '<h4>' . __('Shipping Details', 'foxyshop') . ': ' . $shipto_address->address_name . '</h4>';
				$holder .= '<ul>';
				$holder .= '<li>' . (string)$shipto_address->shipto_first_name . ' ' . (string)$shipto_address->shipto_last_name . '</li>';
				if ((string)$shipto_address->shipto_company != "") $holder .= '<li>' . (string)$shipto_address->shipto_company . '</li>';
				$holder .= '<li>' . (string)$shipto_address->shipto_address1 . '</li>';
				if ((string)$shipto_address->shipto_address2 != "") $holder .= '<li>' . (string)$shipto_address->shipto_address2 . '</li>';
				$holder .= '<li>' . (string)$shipto_address->shipto_city . ', ' . (string)$shipto_address->shipto_state . ' ' . (string)$shipto_address->shipto_postal_code . '</li>';
				$holder .= '<li>' . (string)$shipto_address->shipto_country . '</li>';
				if ((string)$shipto_address->shipto_phone != "") $holder .= '<li>' . (string)$shipto_address->shipto_phone . '</li>';
				$holder .= '</ul>';
				$holder .= '</div>';
			}


			//Custom Attributes
			$holder .= foxyshop_manage_attributes($customer->attributes, $customer_id, "customer");

			$holder .= '<div style="clear: both; height: 20px;"></div>';
			$holder .= "</div>\n";


		}

		echo '</tbody></table>';

		foxyshop_api_paging_nav('customers', 'bottom', $xml, $foxyshop_querystring);
		?>
		</form>
	<?php } ?>

	<div id="details_holder"><?php echo $holder; ?></div>

	<script type="text/javascript" src="<?php echo FOXYSHOP_DIR; ?>/js/jquery.tablesorter.js"></script>
	<script type="text/javascript">
	jQuery(document).ready(function($){
		$(".foxyshop-list-table thead th").click(function() {
			$("#foxyshop-list-inline .detail_holder").appendTo("#details_holder");
			$("#foxyshop-list-inline").remove();
		});
		$(".foxyshop-list-table").tablesorter({
			'cssDesc': 'asc sorted',
			'cssAsc': 'desc sorted'
		});
		$(".view_detail").click(function() {
			var id = $(this).parents("tr").attr("rel");

			if ($("#foxyshop-list-inline #holder_" + id).length > 0) {
				$("#foxyshop-list-inline .detail_holder").appendTo("#details_holder");
				$("#foxyshop-list-inline").remove();
			} else {
				$("#foxyshop-list-inline .detail_holder").appendTo("#details_holder");
				$("#foxyshop-list-inline").remove();

				$(this).parents("tr").after('<tr id="foxyshop-list-inline"><td colspan="7"></td></tr>');
				$("#holder_"+id).appendTo("#foxyshop-list-inline td");
			}

			return false;
		});


		<?php foxyshop_manage_attributes_jquery('customer'); ?>

	});
	</script>


	<?php
	echo '</div>';
}
