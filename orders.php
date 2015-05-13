<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

if (isset($_GET['action-top']) && isset($_GET['action-bottom'])) add_action('admin_init', 'foxyshop_multi_api_edit');
function foxyshop_multi_api_edit() {
	if (!isset($_GET['post'])) return;
	if ($_GET['action-top'] == -1) $act = $_GET['action-bottom'];
	if ($_GET['action-bottom'] == -1) $act = $_GET['action-top'];
	if ($act == -1) return;
	$posts = $_GET['post'];
	if (!is_array($posts)) $posts = array($_POST['post']);

	if ($act == "archive" || $act == "unarchive") {
		$hide_transaction = $act == "archive" ? 1 : 0;
		foreach ($posts as $postid) {
			$foxy_data = array("api_action" => "transaction_modify", "transaction_id" => $postid, "hide_transaction" => $hide_transaction);
			foxyshop_get_foxycart_data($foxy_data); //Run the API Update Call
		}
	}
}

if (isset($_GET['foxyshop_print_invoice'])) add_action('admin_init', 'foxyshop_print_invoice');

if (isset($_GET['transaction_search_type'])) {
	$transaction_search_type = $_GET['transaction_search_type'];
	if ($transaction_search_type == "print_recipts") {
		add_action('admin_init', 'foxyshop_print_invoice');
	} elseif ($transaction_search_type == "export_csv" || $transaction_search_type == "export_tab") {
		add_action('admin_init', 'foxyshop_transaction_export');
	} elseif ($transaction_search_type == "export_ups") {
		add_action('admin_init', 'foxyshop_ups_export');
	}
}

function foxyshop_print_invoice() {
	global $foxyshop_settings;

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
		if (version_compare($foxyshop_settings['version'], '0.7.0', ">")) $foxy_data['entries_per_page'] = FOXYSHOP_API_ENTRIES_PER_PAGE;
	}

	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);

	if ($xml->result == "ERROR") {
		echo '<h3>' . $xml->messages->message . '</h3>';
		die;
	}

	include foxyshop_get_template_file('/foxyshop-receipt.php');
	die;
}


//Shipping Integrations
include_once FOXYSHOP_PATH . '/shippingintegration.php';


//Order Management
add_action('admin_menu', 'foxyshop_order_management_menu');
function foxyshop_order_management_menu() {
	add_submenu_page('edit.php?post_type=foxyshop_product', __('Order Management', 'foxyshop'), __('Orders', 'foxyshop'), apply_filters('foxyshop_order_perm', 'manage_options'), 'foxyshop_order_management', 'foxyshop_order_management');
}

function foxyshop_order_management() {
	global $foxyshop_settings, $wp_version;

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
	$foxy_data = wp_parse_args(array("api_action" => "transaction_list"), apply_filters('foxyshop_transaction_filter_defaults',$foxy_data_defaults));
	$foxyshop_querystring = "?post_type=foxyshop_product&amp;page=foxyshop_order_management&amp;foxyshop_search=1";
	$foxyshop_hidden_input = "";


	if (isset($_GET['foxyshop_search']) || !defined('FOXYSHOP_AUTO_API_DISABLED')) {
		$fields = array("is_test_filter", "hide_transaction_filter", "data_is_fed_filter", "id_filter", "order_total_filter", "coupon_code_filter", "transaction_date_filter_begin", "transaction_date_filter_end", "customer_id_filter", "customer_email_filter", "customer_first_name_filter", "customer_last_name_filter","customer_state_filter", "shipping_state_filter", "customer_ip_filter", "product_code_filter", "product_name_filter", "product_option_name_filter", "product_option_value_filter", "custom_field_name_filter", "custom_field_value_filter");
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

	$transaction_search_type = isset($_GET['transaction_search_type']) ? $_GET['transaction_search_type'] : '';

	if ($foxyshop_settings["orderdesk_url"]) {
		$orderdesk_link = ' <a class="' . (version_compare(get_bloginfo('version'), '3.2', "<") ? "button " : '') . 'add-new-h2" href="https://app.orderdesk.me/" target="_blank">' . __('Launch Order Desk', 'foxyshop') . '</a>';
	} else {
		$orderdesk_link = "";
	}
	?>

	<div class="wrap">
		<div class="icon32 icon32-posts-page" id="icon-edit-pages"><br></div>
		<h2><?php _e('Manage Orders', 'foxyshop'); echo $orderdesk_link; ?></h2>


		<form action="edit.php" method="get" id="foxyshop_searchform" name="foxyshop_searchform" style="display: block; margin: 14px 0 20px 0;">
		<input type="hidden" name="foxyshop_search" value="1" />
		<input type="hidden" name="post_type" value="foxyshop_product" />
		<input type="hidden" name="page" value="foxyshop_order_management" />

		<table class="widefat">
		<thead><tr><th colspan="2"><img src="<?php echo FOXYSHOP_DIR; ?>/images/search-icon.png" alt="" /><?php _e('Search Options', 'foxyshop'); ?></th></tr></thead>
		<tbody><tr><td>

			<div class="foxyshop_field_control foxyshop_radio_label_container">
				<label><?php _e('Transaction Status', 'foxyshop'); ?></label>

				<input type="radio" id="hide_transaction_filter0" name="hide_transaction_filter" value="0"<?php echo $foxy_data['hide_transaction_filter'] == 0 ? ' checked="checked"' : ''; ?> />
				<label for="hide_transaction_filter0"><?php _e('Unfilled', 'foxyshop'); ?></label>

				<input type="radio" id="hide_transaction_filter1" name="hide_transaction_filter" value="1"<?php echo $foxy_data['hide_transaction_filter'] == 1 ? ' checked="checked"' : ''; ?> />
				<label for="hide_transaction_filter1"><?php _e('Filled', 'foxyshop'); ?></label>

				<input type="radio" id="hide_transaction_filter" name="hide_transaction_filter" value=""<?php echo $foxy_data['hide_transaction_filter'] == '' ? ' checked="checked"' : ''; ?> />
				<label for="hide_transaction_filter"><?php _e('Both', 'foxyshop'); ?></label>
			</div>

			<div class="foxyshop_field_control foxyshop_radio_label_container">
				<label><?php _e('Datafeed Status', 'foxyshop'); ?></label>

				<input type="radio" id="data_is_fed_filter0" name="data_is_fed_filter" value="0"<?php echo $foxy_data['data_is_fed_filter'] == 0 ? ' checked="checked"' : ''; ?> />
				<label for="data_is_fed_filter0"><?php _e('Fed', 'foxyshop'); ?></label>

				<input type="radio" id="data_is_fed_filter1" name="data_is_fed_filter" value="1"<?php echo $foxy_data['data_is_fed_filter'] == 1 ? ' checked="checked"' : ''; ?> />
				<label for="data_is_fed_filter1"><?php _e('Unfed', 'foxyshop'); ?></label>

				<input type="radio" id="data_is_fed_filter" name="data_is_fed_filter" value=""<?php echo $foxy_data['data_is_fed_filter'] == '' ? ' checked="checked"' : ''; ?> />
				<label for="data_is_fed_filter"><?php _e('Both', 'foxyshop'); ?></label>
			</div>

			<div class="foxyshop_field_control foxyshop_radio_label_container">
				<label><?php _e('Test Transactions', 'foxyshop'); ?></label>

				<input type="radio" id="is_test_filter0" name="is_test_filter" value="0"<?php echo $foxy_data['is_test_filter'] == 0 ? ' checked="checked"' : ''; ?> />
				<label for="is_test_filter0"><?php _e('Live', 'foxyshop'); ?></label>

				<input type="radio" id="is_test_filter1" name="is_test_filter" value="1"<?php echo $foxy_data['is_test_filter'] == 1 ? ' checked="checked"' : ''; ?> />
				<label for="is_test_filter1"><?php _e('Test', 'foxyshop'); ?></label>

				<input type="radio" id="is_test_filter" name="is_test_filter" value=""<?php echo $foxy_data['is_test_filter'] == '' ? ' checked="checked"' : ''; ?> />
				<label for="is_test_filter"><?php _e('Both', 'foxyshop'); ?></label>
			</div>

			<div class="foxyshop_field_control">
				<label for="order_id_filter"><?php _e('Order ID', 'foxyshop'); ?></label><input type="text" name="id_filter" id="id_filter" value="<?php echo $foxy_data['id_filter']; ?>" />
			</div>

			<div class="foxyshop_field_control">
				<label for="order_total_filter"><?php _e('Order Total', 'foxyshop'); ?></label><input type="text" name="order_total_filter" id="order_total_filter" value="<?php echo $foxy_data['order_total_filter']; ?>" />
			</div>

			<div class="foxyshop_field_control">
				<label for="coupon_code_filter"><?php _e('Coupon Code', 'foxyshop'); ?></label><input type="text" name="coupon_code_filter" id="coupon_code_filter" value="<?php echo $foxy_data['coupon_code_filter']; ?>" />
			</div>

			<div class="foxyshop_field_control">
				<label for="product_code_filter"><?php echo FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('Code', 'foxyshop'); ?></label><input type="text" name="product_code_filter" id="product_code_filter" value="<?php echo $foxy_data['product_code_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="product_name_filter"><?php echo FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('Name', 'foxyshop'); ?></label><input type="text" name="product_name_filter" id="product_name_filter" value="<?php echo $foxy_data['product_name_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="product_option_name_filter"><?php echo FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('Option Name', 'foxyshop'); ?></label><input type="text" name="product_option_name_filter" id="product_option_name_filter" value="<?php echo $foxy_data['product_option_name_filter']; ?>" />
				<label for="product_option_value_filter" style="margin-left: 15px; margin-top: 4px; width: 34px;"><?php _e('Value', 'foxyshop'); ?></label><input type="text" name="product_option_value_filter" id="product_option_value_filter" value="<?php echo $foxy_data['product_option_value_filter']; ?>" />
			</div>

			<?php if (version_compare($foxyshop_settings['version'], '0.7.2', ">=")) { ?>
			<div class="foxyshop_field_control">
				<label for="custom_field_name_filter"><?php _e('Custom Field Name', 'foxyshop'); ?></label><input type="text" name="custom_field_name_filter" id="custom_field_name_filter" value="<?php echo $foxy_data['custom_field_name_filter']; ?>" />
				<label for="custom_field_value_filter" style="margin-left: 15px; margin-top: 4px; width: 34px;"><?php _e('Value', 'foxyshop'); ?></label><input type="text" name="custom_field_value_filter" id="custom_field_value_filter" value="<?php echo $foxy_data['custom_field_value_filter']; ?>" />
			</div>
			<?php } ?>

		</td><td>

			<div class="foxyshop_field_control">
				<label for="transaction_date_filter_begin"><?php _e('Date Range', 'foxyshop'); ?></label><input type="text" name="transaction_date_filter_begin" id="transaction_date_filter_begin" value="<?php echo $foxy_data['transaction_date_filter_begin']; ?>" class="foxyshop_date_field" />
				<span><?php _e('to', 'foxyshop'); ?></span><input type="text" name="transaction_date_filter_end" id="transaction_date_filter_end" value="<?php echo $foxy_data['transaction_date_filter_end']; ?>" class="foxyshop_date_field" />
			</div>



			<div class="foxyshop_field_control">
				<label for="customer_id_filter"><?php _e('Customer ID', 'foxyshop'); ?></label><input type="text" name="customer_id_filter" id="customer_id_filter" value="<?php echo $foxy_data['customer_id_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="customer_email_filter"><?php _e('Customer Email', 'foxyshop'); ?></label><input type="text" name="customer_email_filter" id="customer_email_filter" value="<?php echo $foxy_data['customer_email_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="customer_first_name_filter"><?php _e('Customer First Name', 'foxyshop'); ?></label><input type="text" name="customer_first_name_filter" id="customer_first_name_filter" value="<?php echo $foxy_data['customer_first_name_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="customer_last_name_filter"><?php _e('Customer Last Name', 'foxyshop'); ?></label><input type="text" name="customer_last_name_filter" id="customer_last_name_filter" value="<?php echo $foxy_data['customer_last_name_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="customer_state_filter"><?php _e('Customer State', 'foxyshop'); ?></label><input type="text" name="customer_state_filter" id="customer_state_filter" value="<?php echo $foxy_data['customer_state_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="shipping_state_filter"><?php _e('Shipping State', 'foxyshop'); ?></label><input type="text" name="shipping_state_filter" id="shipping_state_filter" value="<?php echo $foxy_data['shipping_state_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="customer_ip_filter"><?php _e('Customer IP', 'foxyshop'); ?></label><input type="text" name="customer_ip_filter" id="customer_ip_filter" value="<?php echo $foxy_data['customer_ip_filter']; ?>" />
			</div>

			<div style="clear: both;"></div>
			<select name="transaction_search_type" id="transaction_search_type">
				<option value="show_orders"<?php echo ($transaction_search_type == "show_orders" ? ' selected="selected"' : ''); ?>><?php _e('Show Orders', 'foxyshop'); ?></option>
				<option value="print_recipts" target="_blank"><?php _e('Print Receipts', 'foxyshop'); ?></option>
				<?php if (!$foxyshop_settings['enable_ship_to']) { ?>
				<option value="export_csv"><?php _e('Export CSV', 'foxyshop'); ?></option>
				<option value="export_tab"><?php _e('Export Tab Delimeted', 'foxyshop'); ?></option>
				<option value="export_ups"><?php _e('Export to UPS', 'foxyshop'); ?></option>
				<?php } ?>
				<?php do_action("foxyshop_order_search_list"); ?>
			</select>
			<button type="submit" id="foxyshop_search_submit" name="foxyshop_search_submit" class="button-primary" style="clear: left; margin-top: 10px;"><?php _e('Submit', 'foxyshop'); ?></button>
			<button type="button" class="button submitcancel" style="margin-top: 10px;" onclick="document.location.href = 'edit.php?post_type=foxyshop_product&page=foxyshop_order_management';"><?php _e('Reset', 'foxyshop'); ?></button>

			<div style="clear: both;"></div>
			<?php
			if (has_action('foxyshop_order_search_buttons')) {
				echo '<div id="foxyshop_order_search_buttons">';
				do_action("foxyshop_order_search_buttons", $foxy_data);
				echo '</div>';
			}
			?>
		</td></tr></tbody></table>

		</form>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#foxyshop_searchform button").live("click", function() {
				if ($("#transaction_search_type option:selected").attr("target") == "_blank") {
					$("#foxyshop_searchform").attr("target","_blank");
				} else {
					$("#foxyshop_searchform").attr("target","_self");
				}
			});
			$(".tablenav a.disabled").click(function() {
				return false;
			});

			$(".foxyshop_date_field").datepicker({ dateFormat: 'yy-mm-dd' });
		});
		</script>

	<?php
	if (!isset($_GET['foxyshop_search']) && defined('FOXYSHOP_AUTO_API_DISABLED')) return;

	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
	//var_dump($xml);

	if ((string)$xml->result == __('ERROR', 'foxyshop')) {
		echo '<h3>' . (string)$xml->messages->message . '</h3>';
		return;
	} else {

		?>

		<form action="edit.php" method="get">
		<input type="hidden" name="foxyshop_search" value="1" />
		<input type="hidden" name="post_type" value="foxyshop_product" />
		<input type="hidden" name="page" value="foxyshop_order_management" />

		<?php
		echo $foxyshop_hidden_input;
		foxyshop_api_paging_nav('transactions', 'top', $xml, $foxyshop_querystring);
		?>

		<table cellpadding="0" cellspacing="0" border="0" class="wp-list-table widefat foxyshop-list-table" id="transaction_table">
			<thead>
				<tr>
					<th id="cb" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
					<th><span><?php _e('Transaction ID', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Order Date', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Customer', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Total', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<?php do_action("foxyshop_order_table_head"); ?>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th class="manage-column column-cb check-column" style="" scope="col"><input type="checkbox"></th>
					<th><?php _e('Transaction ID', 'foxyshop'); ?></th>
					<th><?php _e('OrderDate', 'foxyshop'); ?></th>
					<th><?php _e('Customer', 'foxyshop'); ?></th>
					<th><?php _e('Total', 'foxyshop'); ?></th>
					<?php do_action("foxyshop_order_table_foot"); ?>
				</tr>
			</tfoot>
			<tbody id="the-list">

		<?php
		$holder = "";
		$hide_transaction_filter = isset($_REQUEST['hide_transaction_filter']) ? $_REQUEST['hide_transaction_filter'] : 0;
		foreach($xml->transactions->transaction as $transaction) {
			$transaction_id = (string)$transaction->id;
			$customer_first_name = (string)$transaction->customer_first_name;
			$customer_last_name = (string)$transaction->customer_last_name;
			$is_anonymous = (int)$transaction->is_anonymous;
			$customer_id = (string)$transaction->customer_id;
			$minfraud_score = (int)$transaction->minfraud_score;

			$transaction_date = (string)$transaction->transaction_date;
			$transaction_date = date(apply_filters("foxyshop_date_time_format", "Y-m-d H:i"), strtotime($transaction_date));

			$customer_name = $customer_last_name . ', ' . $customer_first_name;
			if ($is_anonymous != 1 && $customer_id) $customer_name = '<a href="edit.php?post_type=foxyshop_product&page=foxyshop_customer_management&customer_id_filter=' . $customer_id . '&foxyshop_search=1" title="Customer ' . $customer_id . '">' . $customer_name . '</a>';

			$print_receipt_link = "edit.php?foxyshop_search=1&amp;post_type=foxyshop_product&amp;page=foxyshop_order_management&amp;id_filter=" . $transaction_id . "&amp;foxyshop_print_invoice=1&amp;is_test_filter=&amp;skip_print=1&amp;transaction_date_filter_begin=" . $foxy_data['transaction_date_filter_begin'] . "&amp;transaction_date_filter_end=" . $foxy_data['transaction_date_filter_end'];


			foreach($transaction->transaction_details->transaction_detail as $transaction_detail) {

				$pickup_day = "";
				$pickup_location = "";

				foreach($transaction_detail->transaction_detail_options->transaction_detail_option as $transaction_detail_option) {
					if ((string)$transaction_detail_option->product_option_name == "Pickup_Day") {
						$pickup_day = (string)$transaction_detail_option->product_option_value;
					} elseif ((string)$transaction_detail_option->product_option_name == "Pickup_Location") {
						$pickup_location = (string)$transaction_detail_option->product_option_value;
					}


					$holder .= '<li>';
					$holder .= str_replace("_", " ", (string)$transaction_detail_option->product_option_name) . ': ';
					if (substr((string)$transaction_detail_option->product_option_value,0,5) == "file-") {
						$upload_dir = wp_upload_dir();
						$holder .= '<a href="' . $upload_dir['baseurl'] . '/customuploads/' . (string)$transaction_detail_option->product_option_value . '" target="_blank">' . (string)$transaction_detail_option->product_option_value . '</a>';
					} else {
						$holder .= $transaction_detail_option->product_option_value;
					}
					if ((string)$transaction_detail_option->price_mod != '0.000') $holder .= ' (' . (strpos("-",$transaction_detail_option->price_mod) !== false ? '-' : '+') . foxyshop_currency((double)$transaction_detail_option->price_mod) . ')';
					$holder .= '</li>';
				}

			}


			echo '<tr rel="' . $transaction_id . '">';
			echo '<th class="check-column" scope="row"><input type="checkbox" value="' . $transaction_id . '" name="post[]"></th>'."\n";
			echo '<td>';
			echo '<a href="' . (string)$transaction->receipt_url . '" title="' . __('FoxyCart Receipt', 'foxyshop') . '" target="_blank" style="float: left;"><img src="' . FOXYSHOP_DIR . '/images/foxycart-icon.png" alt="" align="top" /></a>';
			echo '<strong><a href="#" class="view_detail" style="float: left; line-height: 18px; margin: 0 0 0 5px;">' . $transaction_id . '</a></strong>';
			echo '<div class="row-actions">';
				echo '<span><a href="#" class="view_detail">' . __('View Order', 'foxyshop') . '</a> | </span>';
				echo '<span><a href="' . $print_receipt_link . '" title="' . __('Printable Receipt', 'foxyshop') . '" target="_blank">' . __('Receipt', 'foxyshop') . '</a></span>';

				if (!isset($transaction->is_hidden)) {
					$is_hidden = $hide_transaction_filter;
				} else {
					$is_hidden = (string)$transaction->is_hidden;
				}
				if ($is_hidden == 1) {
					echo '<span> | <a href="#" class="set_order_hidden_status" rel="0">' . __('Un-Archive', 'foxyshop') . '</a></span>';
				} else {
					echo '<span> | <a href="#" class="set_order_hidden_status" rel="1">' . __('Archive', 'foxyshop') . '</a></span>';
				}
				do_action("foxyshop_order_line_item", $transaction);
			echo '</div>';
			echo '</td>';
			echo '<td>' . $transaction_date . '</td>';
			echo '<td>' . $customer_name . '</td>';
			echo '<td>' . foxyshop_currency((double)$transaction->order_total) . '</td>';
			do_action("foxyshop_order_line_end", $transaction);
			echo '</tr>'."\n";

			//Write Out Order Details Holder
			$holder .= '<div class="detail_holder" id="holder_' . $transaction_id. '">'."\n";

			$holder .= '<div class="foxyshop_list_col">';
			$holder .= '<h4>' . __('Transaction Details', 'foxyshop') . '</h4>';
			$holder .= '<ul>';
			$holder .= '<li>' . __('Order ID', 'foxyshop') . ': ' . (string)$transaction->id . '</li>';
			$holder .= '<li>' . __('Date', 'foxyshop') . ': ' . $transaction_date. '</li>';
			$holder .= '<li>' . (string)$transaction->processor_response. '</li>';
			if ((string)$transaction->cc_number_masked != "") $holder .= '<li>' . __('Card', 'foxyshop') . ': ' . (string)$transaction->cc_number_masked. ' (' . (string)$transaction->cc_type . ')</li>';
			if ((string)$transaction->cc_exp_month != "") $holder .= '<li>' . __('Exp', 'foxyshop') . ': ' . (string)$transaction->cc_exp_month . '-' . (string)$transaction->cc_exp_year . '</li>';
			if ($minfraud_score > 0) $holder .= '<li>' . __('MinFraud Score', 'foxyshop') . ': ' . $minfraud_score . '</li>';
			if ((string)$transaction->shipto_shipping_service_description != "") $holder .= '<li>' . __('Shipping Type', 'foxyshop') . ': ' . (string)$transaction->shipto_shipping_service_description . '</li>';
			if ((string)$transaction->processor_response == "Purchase Order") $holder .= '<li>PO #: ' . (string)$transaction->purchase_order . '</li>';
			$holder .= '</ul>';
			$holder .= '</div>';

			$holder .= '<div class="foxyshop_list_col">';
			$holder .= '<h4>' . __('Order Details', 'foxyshop') . '</h4>';
			$holder .= '<ul>';
			$holder .= '<li>' . __('Subtotal', 'foxyshop') . ': ' . foxyshop_currency((double)$transaction->product_total) . '</li>';

			//Discounts
			foreach($transaction->discounts->discount as $discount) {
				$holder .= '<li>' . (string)$discount->name . ': ' . foxyshop_currency((double)$discount->amount) . '</li>';
			}

			//Taxes
			foreach($transaction->taxes->tax as $tax) {
				$holder .= '<li>' . (string)$tax->tax_name . ': ' . foxyshop_currency((double)$tax->tax_amount) . '</li>';
			}

			$holder .= '<li>' . __('Shipping', 'foxyshop') . ': ' . foxyshop_currency((double)$transaction->shipping_total) . '</li>';
			$holder .= '<li><strong>' . __('Order Total', 'foxyshop') . ': ' . foxyshop_currency((double)$transaction->order_total) . '</strong></li>';

			$holder .= '</ul>';
			$holder .= '</div>';

			$holder .= '<div class="foxyshop_list_col">';
			$holder .= '<h4>' . __('Customer Address', 'foxyshop') . '</h4>';
			$holder .= '<ul>';
			$holder .= '<li>' . (string)$transaction->customer_first_name . ' ' . (string)$transaction->customer_last_name . '</li>';
			if ((string)$transaction->customer_company != "") $holder .= '<li>' . (string)$transaction->customer_company . '</li>';
			$holder .= '<li>' . (string)$transaction->customer_address1 . '</li>';
			if ((string)$transaction->customer_address2 != "") $holder .= '<li>' . (string)$transaction->customer_address2 . '</li>';
			$holder .= '<li>' . (string)$transaction->customer_city . ', ' . (string)$transaction->customer_state . ' ' . (string)$transaction->customer_postal_code . '</li>';
			$holder .= '<li>' . (string)$transaction->customer_country . '</li>';
			$holder .= '</ul>';
			$holder .= '</div>';

			//Shipping Addresses (if entered)
			if ((string)$transaction->shipping_first_name != "" && !isset($transaction->shipto_addresses->shipto_address)) {
				$holder .= '<div class="foxyshop_list_col">';
				$holder .= '<h4>' . __('Shipping Details', 'foxyshop') . '</h4>';
				$holder .= '<ul>';
				$holder .= '<li>' . (string)$transaction->shipping_first_name . ' ' . (string)$transaction->shipping_last_name . '</li>';
				if ((string)$transaction->shipping_company != "") $holder .= '<li>' . (string)$transaction->shipping_company . '</li>';
				$holder .= '<li>' . (string)$transaction->shipping_address1 . '</li>';
				if ((string)$transaction->shipping_address2 != "") $holder .= '<li>' . (string)$transaction->shipping_address2 . '</li>';
				$holder .= '<li>' . (string)$transaction->shipping_city . ', ' . (string)$transaction->shipping_state . ' ' . (string)$transaction->shipping_postal_code . '</li>';
				$holder .= '<li>' . (string)$transaction->shipping_country . '</li>';
				if ((string)$transaction->shipping_phone != "") $holder .= '<li>' . (string)$transaction->shipping_phone . '</li>';
				$holder .= '</ul>';
				$holder .= '</div>';
			}

			//Multi-ship Addresses
			foreach($transaction->shipto_addresses->shipto_address as $shipto_address) {
				$holder .= '<div class="foxyshop_list_col">';
				$holder .= '<h4>' . __('Shipping Details', 'foxyshop') . ': ' . $shipto_address->address_name . '</h4>';
				$holder .= '<ul>';
				$holder .= '<li>' . $shipto_address->shipto_first_name . ' ' . $shipto_address->shipto_last_name . '</li>';
				if ((string)$shipto_address->shipto_company != "") $holder .= '<li>' . $shipto_address->shipto_company . '</li>';
				$holder .= '<li>' . $shipto_address->shipto_address1 . '</li>';
				if ((string)$shipto_address->shipto_address2 != "") $holder .= '<li>' . $shipto_address->shipto_address2 . '</li>';
				$holder .= '<li>' . $shipto_address->shipto_city . ', ' . $shipto_address->shipto_state . ' ' . $shipto_address->shipto_postal_code . '</li>';
				$holder .= '<li>' . $shipto_address->shipto_country . '</li>';
				if ((string)$shipto_address->shipto_phone != "") $holder .= '<li>' . $shipto_address->shipto_phone . '</li>';
				$holder .= '<li><br />' . __('Method', 'foxyshop') . ': ' . $shipto_address->shipto_shipping_service_description . '</li>';
				$holder .= '<li>' . __('Shipping', 'foxyshop') . ': ' .  foxyshop_currency((double)$shipto_address->shipto_shipping_total) . '</li>';
				$holder .= '</ul>';
				$holder .= '</div>';
			}

			//Customer Details
			$holder .= '<div class="foxyshop_list_col">';
			$holder .= '<h4>' . __('Customer Details', 'foxyshop') . '</h4>';
			$holder .= '<ul>';
			if ((string)$transaction->customer_phone != "") $holder .= '<li>' . (string)$transaction->customer_phone . '</li>';
			$holder .= '<li><a href="mailto:' . (string)$transaction->customer_email . '">' . (string)$transaction->customer_email . '</a></li>';
			$holder .= '<li>' . apply_filters('foxyshop_order_ip', '<a href="http://whatismyipaddress.com/ip/' . (string)$transaction->customer_ip . '" target="_blank">' . (string)$transaction->customer_ip . '</a>', (string)$transaction->customer_ip) . '</li>';
			$holder .= '<li>&nbsp;</li>';

			//Custom Fields
			foreach($transaction->custom_fields->custom_field as $custom_field) {
				if ($custom_field->custom_field_name != 'ga') {
					$holder .= '<li><strong>' . str_replace("_"," ",(string)$custom_field->custom_field_name) . ':</strong> ' . nl2br((string)$custom_field->custom_field_value) . '</li>';
				}
			}

			$holder .= '</ul>';
			$holder .= '</div>';



			//Custom Attributes
			$holder .= foxyshop_manage_attributes($transaction->attributes, $transaction_id, "transaction");



			$holder .= '<div style="clear: both; height: 20px;"></div>';

			foreach($transaction->transaction_details->transaction_detail as $transaction_detail) {
				$holder .= '<div class="product_listing">';
				if ($transaction_detail->image != "") {
					$holder .= '<div class="image_div">';
					if ($transaction_detail->url != "") $holder .= '<a href="' . $transaction_detail->url . '" target="_blank">';
					$holder .= '<img src="' . $transaction_detail->image . '" />';
					if ($transaction_detail->url != "") $holder .= '</a>';
					$holder .= '</div>';
				}

				$product_discount = 0;
				$weight_discount = 0;
				foreach($transaction_detail->transaction_detail_options->transaction_detail_option as $transaction_detail_option) {
					$product_discount += (double)$transaction_detail_option->price_mod;
					$weight_discount += (double)$transaction_detail_option->weight_mod;
				}


				$holder .= '<div class="details_div">';
				$holder .= '<h4>' . $transaction_detail->product_name . '</h4>';
				$holder .= '<ul>';
				if ((string)$transaction_detail->shipto != "") $holder .= '<li>Ship To: ' . (string)$transaction_detail->shipto . '</li>';
				$holder .= '<li>' . __('Code', 'foxyshop') . ': ' . (string)$transaction_detail->product_code . '</li>';
				$holder .= '<li>' . __('Price', 'foxyshop') . ': ' . foxyshop_currency((double)$transaction_detail->product_price). '</li>';
				if ($product_discount != 0) $holder .= '<li>Adjusted Price: ' . foxyshop_currency((double)$transaction_detail->product_price + $product_discount). '</li>';
				$holder .= '<li>' . __('Qty', 'foxyshop') . ': ' . $transaction_detail->product_quantity . '</li>';
				if ((string)$transaction_detail->product_weight != "0.000") $holder .= '<li>Weight: ' . (string)$transaction_detail->product_weight . '</li>';
				if ($weight_discount != 0) $holder .= '<li>Adjusted Weight: ' . ((double)$transaction_detail->product_weight + $weight_discount). '</li>';
				if ((string)$transaction_detail->category_code != "DEFAULT") $holder .= '<li>Category: ' . (string)$transaction_detail->category_description . '</li>';
				if ((string)$transaction_detail->product_delivery_type != "shipped") $holder .= '<li>Delivery Type: ' . (string)$transaction_detail->product_delivery_type . '</li>';
				if ((string)$transaction_detail->downloadable_url != "") $holder .= '<li>Downloadable URL: <a href="' . (string)$transaction_detail->downloadable_url . '" target="_blank">Click Here</a></li>';
				if ($transaction_detail->subscription_frequency != "") {
					$holder .= '<li>' . __('Subscription Frequency', 'foxyshop') . ': ' . (string)$transaction_detail->subscription_frequency . '</li>';
					$holder .= '<li>' . __('Subscription Start Date', 'foxyshop') . ': ' . (string)$transaction_detail->subscription_startdate . '</li>';
					$holder .= '<li>' . __('Subscription Next Date', 'foxyshop') . ': ' . (string)$transaction_detail->subscription_nextdate . '</li>';
					if ((string)$transaction_detail->subscription_enddate != "0000-00-00") $holder .= '<li>Subscription End Date: ' . (string)$transaction_detail->subscription_enddate . '</li>';
				}
				foreach($transaction_detail->transaction_detail_options->transaction_detail_option as $transaction_detail_option) {
					$holder .= '<li>';
					$holder .= str_replace("_", " ", (string)$transaction_detail_option->product_option_name) . ': ';
					if (substr((string)$transaction_detail_option->product_option_value,0,5) == "file-") {
						$upload_dir = wp_upload_dir();
						$holder .= '<a href="' . $upload_dir['baseurl'] . '/customuploads/' . (string)$transaction_detail_option->product_option_value . '" target="_blank">' . (string)$transaction_detail_option->product_option_value . '</a>';
					} else {
						$holder .= $transaction_detail_option->product_option_value;
					}
					if ((string)$transaction_detail_option->price_mod != '0.000') $holder .= ' (' . (strpos("-",$transaction_detail_option->price_mod) !== false ? '-' : '+') . foxyshop_currency((double)$transaction_detail_option->price_mod) . ')';
					$holder .= '</li>';
				}

				$holder .= '</ul>';
				$holder .= '</div>';
				$holder .= '<div style="clear: both;"></div>';
				$holder .= '</div>';
			}
			$holder .= '<div style="clear: both; height: 10px;"></div>';
			$holder .= '</div>';


		}

		echo '</tbody></table>';

		foxyshop_api_paging_nav('transactions', 'bottom', $xml, $foxyshop_querystring);
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
			'cssAsc': 'desc sorted',
			'headers': { 0: { sorter: false} }
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



		$(".set_order_hidden_status").click( function() {
			var hide_transaction = $(this).attr("rel");
			var transaction_id = $(this).parents("tr").attr("rel");
			var data = {
				action: 'foxyshop_display_list_ajax_action',
				security: '<?php echo wp_create_nonce("foxyshop-display-list-function"); ?>',
				hide_transaction: hide_transaction,
				foxyshop_action: 'hide_transaction',
				id: transaction_id
			};
			$.post(ajaxurl, data, function(response) {
			<?php if ($hide_transaction_filter == "0") { ?>
				$("tr[rel="+transaction_id+"]").remove();
				$("#foxyshop-list-inline #holder_" + transaction_id).remove();
			<?php } else { ?>
				alert(response);
			<?php } ?>
			});

			return false;
		});

		<?php foxyshop_manage_attributes_jquery('transaction'); ?>

	});
	</script>


	<?php

}
