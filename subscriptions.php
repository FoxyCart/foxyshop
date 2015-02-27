<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

add_action('admin_menu', 'foxyshop_subscription_management_menu');

function foxyshop_subscription_management_menu() {
	add_submenu_page('edit.php?post_type=foxyshop_product', __('Subscription Management', 'foxyshop'), __('Subscriptions', 'foxyshop'), apply_filters('foxyshop_subscription_perm', 'manage_options'), 'foxyshop_subscription_management', 'foxyshop_subscription_management');
}

function foxyshop_subscription_management() {
	global $foxyshop_settings, $wp_version, $product;

	//Setup Fields and Defaults
	$foxy_data_defaults = array(
		"is_active_filter" => "",
		"frequency_filter" => "",
		"past_due_amount_filter" => "",
		"start_date_filter_begin" => date("Y-m-d", strtotime("-10 days")),
		"start_date_filter_end" =>  date("Y-m-d"),
		"next_transaction_date_filter_begin" => "",
		"next_transaction_date_filter_end" => "",
		"end_date_filter_begin" => "",
		"end_date_filter_end" => "",
		"third_party_id_filter" => "",
		"last_transaction_id_filter" => "",
		"customer_id_filter" => "",
		"customer_email_filter" => "",
		"customer_first_name_filter" => "",
		"customer_last_name_filter" => "",
		"product_code_filter" => "",
		"product_name_filter" => "",
		"product_option_name_filter" => "",
		"product_option_value_filter" => "",
	);
	if (version_compare($foxyshop_settings['version'], '0.7.2', ">=")) {
		$foxy_data_defaults["custom_field_name_filter"] = "";
		$foxy_data_defaults["custom_field_value_filter"] = "";
	}

	$foxy_data = wp_parse_args(array("api_action" => "subscription_list"), apply_filters('foxyshop_subscription_filter_defaults',$foxy_data_defaults));
	$foxyshop_querystring = "?post_type=foxyshop_product&amp;page=foxyshop_subscription_management&amp;foxyshop_search=1";
	$foxyshop_hidden_input = "";

	if (isset($_GET['foxyshop_search']) || !defined('FOXYSHOP_AUTO_API_DISABLED')) {
		$fields = array("is_active_filter", "frequency_filter", "past_due_amount_filter","start_date_filter_begin", "start_date_filter_end", "next_transaction_date_filter_begin", "next_transaction_date_filter_end", "end_date_filter_begin", "end_date_filter_end", "third_party_id_filter", "last_transaction_id_filter", "customer_id_filter", "customer_email_filter", "customer_first_name_filter", "customer_last_name_filter", "product_code_filter", "product_name_filter", "product_option_name_filter", "product_option_value_filter", "custom_field_name_filter", "custom_field_value_filter");
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

	$subscription_products = get_posts(array('post_type' => 'foxyshop_product', "meta_key" => "_sub_frequency", "meta_value" => "", 'meta_compare' => '!=', "_sub_frequency", 'numberposts' => -1));
	$subscription_product_array = array();
	foreach($subscription_products as $subscription_product) {
		$product = foxyshop_setup_product($subscription_product);
		$subscription_product_array[] = array(
			"id" => $product['id'],
			"name" => $product['name'],
			"price" => $product['price']
		);
	}
	?>

	<div class="wrap">
		<div class="icon32 icon32-posts-page" id="icon-edit-pages"><br></div>
		<h2><?php _e('Manage Subscriptions', 'foxyshop'); ?></h2>

		<form action="edit.php" method="get" id="foxyshop_searchform" name="foxyshop_searchform" style="display: block; margin: 14px 0 20px 0;">
		<input type="hidden" name="foxyshop_search" value="1" />
		<input type="hidden" name="post_type" value="foxyshop_product" />
		<input type="hidden" name="page" value="foxyshop_subscription_management" />

		<table class="widefat">
		<thead><tr><th colspan="2"><img src="<?php echo FOXYSHOP_DIR; ?>/images/search-icon.png" alt="" /><?php _e('Search Options', 'foxyshop'); ?></th></tr></thead>
		<tbody><tr><td>
			<div class="foxyshop_field_control">
				<label for="is_active_filter"><?php _e('Subscription Type', 'foxyshop'); ?></label>
				<select name="is_active_filter" id="is_active_filter">
				<?php
				$selectArray = array("0" => __("Disabled", 'foxyshop'), "1" => __("Active", 'foxyshop'), "" => __("Both", 'foxyshop'));
				foreach ($selectArray as $selectKey=>$selectOption) {
					echo '<option value="' . $selectKey . '"' . ($foxy_data['is_active_filter'] == $selectKey ? ' selected="selected"' : '') . '>' . $selectOption . '</option>'."\n";
				} ?>
				</select>
			</div>
			<div class="foxyshop_field_control">
				<label for="past_due_amount_filter"><?php _e('Past Due Status', 'foxyshop'); ?></label>
				<select name="past_due_amount_filter" id="past_due_amount_filter">
				<?php
				$selectArray = array("" => __('Show All', 'foxyshop'), "1" => __('Show Past Due Only', 'foxyshop'));
				foreach ($selectArray as $selectKey=>$selectOption) {
					echo '<option value="' . $selectKey . '"' . ($foxy_data['past_due_amount_filter'] == $selectKey ? ' selected="selected"' : '') . '>' . $selectOption . '</option>'."\n";
				} ?>
				</select>
			</div>
			<div class="foxyshop_field_control">
				<label for="frequency_filter"><?php _e('Frequency', 'foxyshop'); ?></label><input type="text" name="frequency_filter" id="frequency_filter" value="<?php echo $foxy_data['frequency_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="third_party_id_filter"><?php _e('Third Party ID', 'foxyshop'); ?></label><input type="text" name="third_party_id_filter" id="third_party_id_filter" value="<?php echo $foxy_data['third_party_id_filter']; ?>" />
				<span>PayPal</span>
			</div>
			<div class="foxyshop_field_control">
				<label for="last_transaction_id_filter"><?php _e('Last Transaction ID', 'foxyshop'); ?></label><input type="text" name="last_transaction_id_filter" id="last_transaction_id_filter" value="<?php echo $foxy_data['last_transaction_id_filter']; ?>" />
			</div>

			<div class="foxyshop_field_control">
				<label for="product_code_filter"><?php echo FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('Code', 'foxyshop'); ?></label><input type="text" name="product_code_filter" id="product_code_filter" value="<?php echo $foxy_data['product_code_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="product_name_filter"><?php echo FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('Name', 'foxyshop'); ?></label><input type="text" name="product_name_filter" id="product_name_filter" value="<?php echo $foxy_data['product_name_filter']; ?>" />
			</div>
			<div class="foxyshop_field_control">
				<label for="product_option_name_filter"><?php echo FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('Option Name', 'foxyshop'); ?></label><input type="text" name="product_option_name_filter" id="product_option_name_filter" value="<?php echo $foxy_data['product_option_name_filter']; ?>" />
				<label for="product_option_value_filter" style="margin-left: 15px; margin-top: 4px; width: 38px;"><?php _e('Value', 'foxyshop'); ?></label><input type="text" name="product_option_value_filter" id="product_option_value_filter" value="<?php echo $foxy_data['product_option_value_filter']; ?>" />
			</div>
			<?php if (version_compare($foxyshop_settings['version'], '0.7.2', ">=")) { ?>
			<div class="foxyshop_field_control">
				<label for="custom_field_name_filter"><?php _e('Custom Field Name', 'foxyshop'); ?></label><input type="text" name="custom_field_name_filter" id="custom_field_name_filter" value="<?php echo $foxy_data['custom_field_name_filter']; ?>" />
				<label for="custom_field_value_filter" style="margin-left: 15px; margin-top: 4px; width: 38px;"><?php _e('Value', 'foxyshop'); ?></label><input type="text" name="custom_field_value_filter" id="custom_field_value_filter" value="<?php echo $foxy_data['custom_field_value_filter']; ?>" />
			</div>
			<?php } ?>

		</td><td>

			<div class="foxyshop_field_control">
				<label for="start_date_filter_begin"><?php _e('Start Date', 'foxyshop'); ?></label>
				<input type="text" name="start_date_filter_begin" id="start_date_filter_begin" value="<?php echo $foxy_data['start_date_filter_begin']; ?>" class="foxyshop_date_field" />
				<span><?php _e('to', 'foxyshop'); ?></span>
				<input type="text" name="start_date_filter_end" id="start_date_filter_end" value="<?php echo $foxy_data['start_date_filter_end']; ?>" class="foxyshop_date_field" />
			</div>
			<div class="foxyshop_field_control">
				<label for="next_transaction_date_filter_begin"><?php _e('Next Transaction Date', 'foxyshop'); ?></label>
				<input type="text" name="next_transaction_date_filter_begin" id="next_transaction_date_filter_begin" value="<?php echo $foxy_data['next_transaction_date_filter_begin']; ?>" class="foxyshop_date_field" />
				<span><?php _e('to', 'foxyshop'); ?></span>
				<input type="text" name="next_transaction_date_filter_end" id="next_transaction_date_filter_end" value="<?php echo $foxy_data['next_transaction_date_filter_end']; ?>" class="foxyshop_date_field" />
			</div>
			<div class="foxyshop_field_control">
				<label for="end_date_filter_begin"><?php _e('End Date', 'foxyshop'); ?></label>
				<input type="text" name="end_date_filter_begin" id="end_date_filter_begin" value="<?php echo $foxy_data['end_date_filter_begin']; ?>" class="foxyshop_date_field" />
				<span><?php _e('to', 'foxyshop'); ?></span>
				<input type="text" name="end_date_filter_end" id="end_date_filter_end" value="<?php echo $foxy_data['end_date_filter_end']; ?>" class="foxyshop_date_field" />
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

			<div style="clear: both;"></div>
			<button type="submit" id="foxyshop_search_submit" name="foxyshop_search_submit" class="button-primary" style="clear: both; margin-top: 10px;"><?php _e('Search Records Now', 'foxyshop'); ?></button>
			<button type="button" class="button" style="margin-left: 15px; margin-top: 10px;" onclick="document.location.href = 'edit.php?post_type=foxyshop_product&page=foxyshop_subscription_management';"><?php _e('Reset Form', 'foxyshop'); ?></button>

		</td></tr></tbody></table>


		</form>
		<script type="text/javascript" charset="utf-8">
		jQuery(document).ready(function($) {
			$(".foxyshop_date_field").datepicker({ dateFormat: 'yy-mm-dd' });
		});
		</script>

	<?php
	if (!isset($_GET['foxyshop_search']) && defined('FOXYSHOP_AUTO_API_DISABLED')) return;

	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);

	if ((string)$xml->result == __('ERROR', 'foxyshop')) {
		echo '<h3>' . (string)$xml->messages->message . '</h3>';
		return;
	} else {
		?>



		<form action="edit.php" method="get">
		<input type="hidden" name="foxyshop_search" value="1" />
		<input type="hidden" name="post_type" value="foxyshop_product" />
		<input type="hidden" name="page" value="foxyshop_subscription_management" />

		<?php
		echo $foxyshop_hidden_input;
		foxyshop_api_paging_nav('subscriptions', 'top', $xml, $foxyshop_querystring);
		?>

		<table cellpadding="0" cellspacing="0" border="0" class="wp-list-table widefat foxyshop-list-table" id="subscription_table">
			<thead>
				<tr>
					<th><span><?php _e('Customer', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Start Date', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Next Date', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('End Date', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Past Due', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Details', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Frequency', 'foxyshop'); ?></span><span class="sorting-indicator"></span></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th><?php _e('Customer', 'foxyshop'); ?></th>
					<th><?php _e('Start Date', 'foxyshop'); ?></th>
					<th><?php _e('Next Date', 'foxyshop'); ?></th>
					<th><?php _e('End Date', 'foxyshop'); ?></th>
					<th><?php _e('Past Due', 'foxyshop'); ?></th>
					<th><?php _e('Details', 'foxyshop'); ?></th>
					<th><?php _e('Frequency', 'foxyshop'); ?></th>
				</tr>
			</tfoot>
			<tbody id="the-list">

		<?php
		$holder = "";
		foreach($xml->subscriptions->subscription as $subscription) {
			$sub_token = (string)$subscription->sub_token;
			$customer_id = (string)$subscription->customer_id;
			$customer_first_name = (string)$subscription->customer_first_name;
			$customer_last_name = (string)$subscription->customer_last_name;
			$start_date = (string)$subscription->start_date;
			$next_transaction_date = (string)$subscription->next_transaction_date;
			$end_date = (string)$subscription->end_date;
			$frequency = (string)$subscription->frequency;
			$past_due_amount = (string)$subscription->past_due_amount;
			$is_active = (string)$subscription->is_active;
			$product_name = "";
			if (version_compare($foxyshop_settings['version'], '0.7.0', ">")) {
				foreach($subscription->transaction_template->transaction_details->transaction_detail as $transaction_detail) {
					if ($product_name) $product_name .= "<br />";
					$product_price = (double)$transaction_detail->product_price;
					foreach($transaction_detail->transaction_detail_options->transaction_detail_option as $transaction_detail_option) {
						$product_price += (double)$transaction_detail_option->price_mod;
					}
					$product_name .= (string)$transaction_detail->product_name . ' ' . foxyshop_currency($product_price);
				}
			} else { // The 0.7.0 code had an extra transaction_template node which was removed in subsequent versions
				foreach($subscription->transaction_template->transaction_template->transaction_details->transaction_detail as $transaction_detail) {
					if ($product_name) $product_name .= "<br />";
					$product_price = (double)$transaction_detail->product_price;
					foreach($transaction_detail->transaction_detail_options->transaction_detail_option as $transaction_detail_option) {
						$product_price += (double)$transaction_detail_option->price_mod;
					}
					$product_name .= (string)$transaction_detail->product_name . ' ' . foxyshop_currency($product_price);
				}
			}

			if ($customer_first_name != "") {
				$customer_name = $customer_last_name . ', ' . $customer_first_name;
			} else {
				$customer_name = $customer_id;
			}

			echo '<tr rel="' . $sub_token . '">';
			echo '<td class="customer_name">';
			echo '<strong' . ($is_active == "0" ? ' class="strikethrough"' : '') . '><a href="#" class="view_detail">' . $customer_name . '</a></strong>';
			echo '<div class="row-actions">';
				echo '<span class="edit"><a title="' . __('Edit') . '" href="#" class="view_detail">' . __('Edit') . '</a> | </span>';
				echo '<span class="view_customer"><a href="edit.php?post_type=foxyshop_product&page=foxyshop_customer_management&customer_id_filter=' . $customer_id . '&foxyshop_search=1" title="' . __('Customer') . '">' . __('Customer') . '</a></span>';
				do_action('foxyshop_subscription_action_line', $subscription);
			echo '</div>';
			echo '</td>';
			echo '<td class="start_date">' . $start_date . '</td>';
			echo '<td class="next_transaction_date">' . $next_transaction_date . '</td>';
			echo '<td class="end_date">' . $end_date . '</td>';
			echo '<td class="past_due_amount">' . $past_due_amount . '</td>';
			echo '<td class="product_description">' . $product_name . '</td>';
			echo '<td class="frequency">' . $frequency . '</td>';
			echo "</tr>\n";

			$holder .= '<div class="detail_holder" id="holder_' . $sub_token. '">'."\n";
			$holder .= '<form class="subscription_update_form" name="subscription_update_form_' . $sub_token. '" id="subscription_update_form_' . $sub_token. '" onsubmit="return false;">'."\n";
			$holder .= '<div class="foxyshop_field_control">'."\n";
			$holder .= '<label>' . __('Subscription Status', 'foxyshop') . '</label>'."\n";
			$holder .= '<input type="radio" name="is_active" id="is_active_1_' . $sub_token. '" value="1"' . ($is_active == "1" ? ' checked="checked"' : '') . ' style="float: left; margin-top: 7px;" />'."\n";
			$holder .= '<label for="is_active_1_' . $sub_token. '" style="width: 55px;">' . __('Active', 'foxyshop') . '</label>'."\n";
			$holder .= '<input type="radio" name="is_active" id="is_active_0_' . $sub_token. '" value="0"' . ($is_active == "0" ? ' checked="checked"' : '') . ' style="float: left; margin-top: 7px;" />'."\n";
			$holder .= '<label for="is_active_0_' . $sub_token. '">' . __('In-active', 'foxyshop') . '</label>'."\n";
			$holder .= '</div>'."\n";
			$holder .= '<div class="foxyshop_field_control">'."\n";
			$holder .= '<label for="start_date_' . $sub_token. '">' . __('Start Date', 'foxyshop') . '</label>'."\n";
			$holder .= '<input type="text" name="start_date" id="start_date_' . $sub_token. '" class="foxyshop_date_field" value="' . (string)$subscription->start_date. '" /><span>(YYYY-MM-DD)</span>'."\n";
			$holder .= '</div>'."\n";
			$holder .= '<div class="foxyshop_field_control">'."\n";
			$holder .= '<label for="next_transaction_date_' . $sub_token. '">' . __('Next Transaction Date', 'foxyshop') . '</label>'."\n";
			$holder .= '<input type="text" name="next_transaction_date" id="next_transaction_date_' . $sub_token. '" value="' . (string)$subscription->next_transaction_date. '" class="foxyshop_date_field" /><span>(YYYY-MM-DD)</span>'."\n";
			$holder .= '</div>'."\n";
			$holder .= '<div class="foxyshop_field_control">'."\n";
			$holder .= '<label for="end_date_' . $sub_token. '">' . __('End Date', 'foxyshop') . '</label>'."\n";
			$holder .= '<input type="text" name="end_date" id="end_date_' . $sub_token. '" value="' . $end_date. '" class="foxyshop_date_field" /><span>(YYYY-MM-DD)</span> <a href="#" onclick="jQuery(\'#end_date_' . $sub_token. '\').val(\'0000-00-00\'); this.blur(); return false;" class="button" style="margin: 5px 0 0 5px; float: left;">Never</a> <a href="#" onclick="jQuery(\'#end_date_' . $sub_token. '\').val(\'' . date("Y-m-d", strtotime("+1 day")) . '\'); this.blur(); return false;" class="button" style="margin: 5px 0 0 5px; float: left;">Tomorrow</a>'."\n";
			$holder .= '</div>'."\n";
			$holder .= '<div class="foxyshop_field_control">'."\n";
			$holder .= '<label for="frequency_' . $sub_token. '">' . __('Frequency', 'foxyshop') . '</label>'."\n";
			$holder .= '<input type="text" name="frequency" id="frequency_' . $sub_token. '" value="' . $frequency. '" /><span>(60d, 2w, 1m, 1y, .5m)</span>'."\n";
			$holder .= '</div>'."\n";
			$holder .= '<div class="foxyshop_field_control">'."\n";
			$holder .= '<label for="past_due_amount_' . $sub_token. '">' . __('Past Due Amount', 'foxyshop') . '</label>'."\n";
			$holder .= '<input type="text" name="past_due_amount" id="past_due_amount_' . $sub_token. '" value="' . $past_due_amount. '" onblur="foxyshop_check_number(this);" /><span>(0.00)</span>'."\n";
			$holder .= '</div>'."\n";
			$holder .= '<div class="foxyshop_field_control">'."\n";
			$holder .= '<label for="update_url_' . $sub_token. '">' . __('Update URL', 'foxyshop') . '</label>'."\n";
			$holder .= '<input type="text" name="update_url" id="update_url_' . $sub_token. '" value="https://' . $foxyshop_settings['domain']. '/cart?sub_token=' . $sub_token . '&amp;empty=true&amp;cart=checkout" style="width: 390px;" onclick="this.select();" />'."\n";
			$holder .= '</div>'."\n";
			$holder .= '<div class="foxyshop_field_control">'."\n";
			$holder .= '<label for="cancel_url_' . $sub_token. '">' . __('Cancellation URL', 'foxyshop') . '</label>'."\n";
			$holder .= '<input type="text" name="cancel_url" id="cancel_url_' . $sub_token. '" value="https://' . $foxyshop_settings['domain']. '/cart?sub_token=' . $sub_token . '&amp;empty=true&amp;cart=checkout&amp;sub_cancel=true" style="width: 390px;" onclick="this.select();" />'."\n";
			$holder .= '</div>'."\n";
			$holder .= '<div class="foxyshop_field_control">'."\n";
			$holder .= '<label for="transaction_template_id_' . $sub_token. '">' . __('Transaction Template') . '</label>'."\n";
			$holder .= '<select name="transaction_template_id" id="transaction_template_id_' . $sub_token. '">'."\n";
			$holder .= '<option value="0">- - ' . __('Select Option Below', 'foxyshop') . ' - -</option>'."\n";
			foreach ($subscription_product_array as $key=>$val) {
				$holder .= '<option value="' . $val['id'] . '">' . $val['name'] . ' ' . foxyshop_currency($val['price']) . '</option>'."\n";
			}
			$holder .= '</select>'."\n";
			$holder .= '</div>'."\n";

			$holder .= '<p style="padding-top: 5px; clear: both"><a href="#" class="subscription_save button-primary">' . __('Save Changes', 'foxyshop') . '</a> <a href="#" class="detail_close button">' . __('Cancel', 'foxyshop') . '</a></p>'."\n";
			$holder .= '<input type="hidden" name="sub_token" value="' . $sub_token. '" />'."\n";
			$holder .= '<input type="hidden" name="action" value="foxyshop_display_list_ajax_action" />'."\n";
			$holder .= '<input type="hidden" name="foxyshop_action" value="subscription_modify" />'."\n";
			$holder .= '<input type="hidden" name="security" value="' . wp_create_nonce("foxyshop-display-list-function") . '" />'."\n";


			//Custom Attributes
			$holder .= foxyshop_manage_attributes($subscription->attributes, $sub_token, "subscription");

			$holder .= '</form>'."\n";
			$holder .= '</div>'."\n";


		} ?>
		</tbody>
		</table>

		<?php
		foxyshop_api_paging_nav('subscriptions', 'bottom', $xml, $foxyshop_querystring);
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
				$("#details_holder select").prop('selectedIndex', 0);
				$("#foxyshop-list-inline .detail_holder").appendTo("#details_holder");
				$("#foxyshop-list-inline").remove();

				$(this).parents("tr").after('<tr id="foxyshop-list-inline"><td colspan="7"></td></tr>');
				$("#holder_"+id).appendTo("#foxyshop-list-inline td");
			}

			return false;
		});
		$(".detail_close").click(function() {
			$("#foxyshop-list-inline .detail_holder").appendTo("#details_holder");
			$("#foxyshop-list-inline").remove();
			return false;
		});
		$(".subscription_save").click(function() {
			var id = $(this).parents("form").children("input[name='sub_token']").val();
			$.post(ajaxurl, $(this).parents("form").serialize(), function(response) {

				$("#foxyshop-list-inline .detail_holder").appendTo("#details_holder");
				$("#foxyshop-list-inline").remove();

				if (response.indexOf("ERROR") < 0) {
					$("tr[rel='" + id + "']").css("background-color", "#FFFFE0").delay(500).animate({ backgroundColor: 'transparent' }, 500);
					if ($("#is_active_0_" + id).is(":checked")) {
						$("tr[rel='" + id + "'] td.customer_name strong").addClass("strikethrough");
					} else {
						$("tr[rel='" + id + "'] td.customer_name strong").removeClass("strikethrough");
					}
					$("tr[rel='" + id + "'] td.start_date").text($("#start_date" + id).val());
					$("tr[rel='" + id + "'] td.next_transaction_date").text($("#next_transaction_date_" + id).val());
					$("tr[rel='" + id + "'] td.end_date").text($("#end_date_" + id).val());
					$("tr[rel='" + id + "'] td.past_due_amount").text($("#past_due_amount_" + id).val());
					$("tr[rel='" + id + "'] td.frequency").text($("#frequency_" + id).val());
					if ($("#transaction_template_id_" + id).prop("selectedIndex") > 0) {
						$("tr[rel='" + id + "'] td.product_description").text($("#transaction_template_id_" + id + " option:selected").text());
					}
				} else {
					alert(response);
				}
			});
			return false;
		});

		<?php foxyshop_manage_attributes_jquery('subscription'); ?>

	});

	function foxyshop_format_number(num) { num = num.toString().replace(/\$|\,/g,''); if(isNaN(num)) num = "0"; sign = (num == (num = Math.abs(num))); num = Math.floor(num*100+0.50000000001); cents = num%100; num = Math.floor(num/100).toString(); if(cents<10) cents = "0" + cents; for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++) num = num.substring(0,num.length-(4*i+3))+','+ num.substring(num.length-(4*i+3)); return (((sign)?'':'-') + num + '.' + cents); }
	function foxyshop_check_number(el) { el.value = foxyshop_format_number(el.value); }

	</script>
	<?php

	echo '</div>';
}
