<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//When Saving Profile, These Actions Sync Data to FoxyCart
add_action('profile_update', 'foxyshop_profile_update', 5, 2);
add_action('user_register', 'foxyshop_profile_add', 5);
add_action('password_reset', 'foxyshop_password_reset_at_foxycart', 5, 2);

//Reset the Password at FoxyCart on a WordPress Password Reset
function foxyshop_password_reset_at_foxycart($wp_user, $new_password) {

	//Only Sync Customer Accounts Already Linked to a FoxyCart Customer
	if (foxyshop_is_protected_user($wp_user->ID)) return;
	$foxycart_customer_id = get_user_meta($wp_user->ID, 'foxycart_customer_id', true);
	if (!$foxycart_customer_id) return;

	//Send Updated Info to FoxyCart
	$foxy_data = array("api_action" => "customer_save");
	$foxy_data["customer_id"] = $foxycart_customer_id;
	$foxy_data["customer_email"] = $wp_user->user_email;
	$foxy_data["customer_password"] = $new_password;
	if ($wp_user->user_firstname) $foxy_data["customer_first_name"] = $wp_user->user_firstname;
	if ($wp_user->user_lastname) $foxy_data["customer_last_name"] = $wp_user->user_lastname;
	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
}

//Runs When WP Profile is Updated
function foxyshop_profile_update($user_id, $old_user_data = null) {
	global $foxyshop_new_password_hash;

	//Only Sync Customer Accounts Already Linked to a FoxyCart Customer
	if (foxyshop_is_protected_user($user_id)) return;
	$foxycart_customer_id = get_user_meta($user_id, 'foxycart_customer_id', true);
	if (!$foxycart_customer_id) return;

	//Get User Data
	$wp_user = get_userdata($user_id);

	//Send Updated Info to FoxyCart, Including the Password Hash Only When It Changed
	$foxy_data = array("api_action" => "customer_save");
	$foxy_data["customer_id"] = $foxycart_customer_id;
	$foxy_data["customer_email"] = $wp_user->user_email;
	if (isset($foxyshop_new_password_hash)) {
		$foxy_data["customer_password_hash"] = $foxyshop_new_password_hash;
	} elseif (!is_object($old_user_data) || $old_user_data->user_pass !== $wp_user->user_pass) {
		$foxy_data["customer_password_hash"] = $wp_user->user_pass;
	}
	if ($wp_user->user_firstname) $foxy_data["customer_first_name"] = $wp_user->user_firstname;
	if ($wp_user->user_lastname) $foxy_data["customer_last_name"] = $wp_user->user_lastname;

	//Hook To Add Your Own Function to Update the $foxy_data array with your own data
	if (has_filter('foxyshop_save_sso_to_foxycart')) $foxy_data = apply_filters('foxyshop_save_sso_to_foxycart', $foxy_data, $user_id, "update");

	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
}


//Runs When WP User is Added
function foxyshop_profile_add($user_id) {

	//Staff Accounts Are Never Linked to FoxyCart Customers
	if (!foxyshop_is_protected_user($user_id)) {

		//Get User Data
		$wp_user = get_userdata($user_id);

		//Only Create a FoxyCart Customer When None Exists for This Email; Existing Customers Are Linked at Checkout
		if (foxyshop_find_customer_id_by_email($wp_user->user_email) === '') {

			//Set Foxy Data
			$foxy_data = array("api_action" => "customer_save");
			$foxy_data["customer_email"] = $wp_user->user_email;
			$foxy_data["customer_password_hash"] = $wp_user->user_pass;
			if ($wp_user->user_firstname) $foxy_data["customer_first_name"] = $wp_user->user_firstname;
			if ($wp_user->user_lastname) $foxy_data["customer_last_name"] = $wp_user->user_lastname;

			//Hook To Add Your Own Function to Update the $foxy_data array with your own data
			if (has_filter('foxyshop_save_sso_to_foxycart')) $foxy_data = apply_filters('foxyshop_save_sso_to_foxycart', $foxy_data, $user_id, "add");

			//Send To FoxyCart
			$foxy_response = foxyshop_get_foxycart_data($foxy_data);
			$xml = foxyshop_load_xml($foxy_response);
			$foxycart_customer_id = $xml && (string)$xml->result != "ERROR" ? (string)$xml->customer_id : "";

			//If FoxyCart Customer ID Returned, Add FoxyCart Customer ID To User Meta
			if ($foxycart_customer_id && !foxyshop_customer_id_is_linked($foxycart_customer_id, $user_id)) {
				add_user_meta($user_id, 'foxycart_customer_id', $foxycart_customer_id, true);
			}
		}
	}

	//Auto-login if user wasn't logged in before (off by default)
	//Note that if you don't have the querystring "redirect_to" set on the registration page the page will not redirect anywhere and won't appear logged in at first
	$auto_login = apply_filters("foxyshop_new_user_auto_login", false);
	if (!is_user_logged_in() && $auto_login) wp_set_auth_cookie($user_id, false, is_ssl());
}


//Adds a Login Message When Prompting Users to Login Before Checking Out
if (isset($_GET['foxycart_checkout'])) {
	add_filter('login_message', 'foxyshop_login_message', 2);
	add_action('login_head','foxyshop_login_head', 2);
}
function foxyshop_login_head() { ?>
	<style type="text/css">
	#login_error, .message { display:none; }
	.custom-message {
		-moz-border-radius:3px 3px 3px 3px;
		border-style:solid;
		border-width:1px;
		margin:0 0 16px 8px;
		padding:12px;
	}
	.login .custom-message {
		background-color:#FFFFE0;
		border-color:#E6DB55;
	}
	</style><?php
}
function foxyshop_login_message() {
	$message = '<p class="custom-message">' . __('Please login before checking out.', 'foxyshop') . ' <a href="' . get_bloginfo("wpurl") . '/wp-login.php?action=register">' . __('Click here to register.', 'foxyshop') . '</a></p><br />';
	return $message;
}


//Setup Actions
add_action('admin_init', 'foxyshop_user_init');
function foxyshop_user_init() {
	add_action('show_user_profile', 'foxyshop_action_show_user_profile');
	add_action('edit_user_profile', 'foxyshop_action_show_user_profile');
	add_action('personal_options_update', 'foxyshop_action_process_option_update');
	add_action('edit_user_profile_update', 'foxyshop_action_process_option_update');
}

function foxyshop_action_show_user_profile($user) {
	global $foxyshop_settings;
	if (!current_user_can('administrator')) return;
	?>
	<h3><?php _e('FoxyCart User Data') ?></h3>
	<table class="form-table">
	<tr>
	<th><label for="foxycart_customer_id"><?php _e('FoxyCart Customer ID', 'foxyshop'); ?></label></th>
	<td><input type="text" name="foxycart_customer_id" id="foxycart_customer_id" value="<?php echo esc_attr(get_user_meta($user->ID, 'foxycart_customer_id', 1) ); ?>" /> <span class="description"><?php _e('Editing is not recommended', 'foxyshop'); ?></span></td>
	</tr>
	<?php
	//Custom Hook To Allow Customization of the Content that Goes Here (add your own fields). Passes in one argument: the current user ID
	//Also note that is before the </table> so anything you add should be wrapped in <tr>
	do_action("foxyshop_show_user_profile_data", $user->ID);
	?>
	</table>


	<?php
	//Get User's Subscription Array
	$foxyshop_subscription = get_user_meta($user->ID, 'foxyshop_subscription', true);
	if (!is_array($foxyshop_subscription)) $foxyshop_subscription = array();

	if (count($foxyshop_subscription) > 0) {
	?>
<h3><?php _e('FoxyCart Subscriptions', 'foxyshop') ?></h3>
<table class="widefat" cellspacing="0">
    <thead>
    <tr>
        <tr>
            <th class="manage-column column-columnname" scope="col"><?php echo esc_html(FOXYSHOP_PRODUCT_NAME_SINGULAR) . ' ' . __('Code', 'foxyshop'); ?></th>
            <th class="manage-column column-columnname" scope="col"><?php _e('Active', 'foxyshop'); ?></th>
            <th class="manage-column column-columnname" scope="col"><?php _e('Actions', 'foxyshop'); ?></th>
        </tr>
    </tr>
    </thead>
    <tbody>
	<?php
	foreach ($foxyshop_subscription as $key => $val) {
		$sub_token = str_replace('https://'.$foxyshop_settings['domain'].'/cart?sub_token=', "", $val['sub_token_url']);
	?>
        <tr class="alternate">
            <td class="column-columnname"><?php echo esc_html($key); ?></td>
            <td class="column-columnname"><?php echo ($val['is_active'] == 1 ? __('Yes', 'foxyshop') : __('No', 'foxyshop')); ?></td>
            <td class="column-columnname"><a href="<?php echo esc_url($val['sub_token_url']); ?>&amp;cart=checkout" target="_blank"><?php _e('Update Info', 'foxyshop');?></a> | <a href="<?php echo esc_url($val['sub_token_url']); ?>&amp;sub_cancel=true&amp;cart=checkout" target="_blank"><?php _e('Cancel', 'foxyshop');?></a></td>
        </tr>
     <?php
     }
     ?>
    </tbody>
</table>
	<?php
	} //End Subscription View
}

function foxyshop_action_process_option_update($user_id) {
	if (!current_user_can('administrator')) return;
	if (isset($_POST['foxycart_customer_id'])) update_user_meta($user_id, 'foxycart_customer_id', sanitize_text_field($_POST['foxycart_customer_id']));
}

//Keep redirect_to in URL
add_filter('site_url', 'foxyshop_add_registration_redirect', 5);
function foxyshop_add_registration_redirect($path) {
	if ((strpos($path, "action=register") !== false || strpos($path, "action=lostpassword") !== false) && isset($_REQUEST['redirect_to'])) return $path . '&amp;redirect_to='.urlencode(sanitize_text_field($_REQUEST['redirect_to']));
	if (substr($path, strlen($path)-12) == "wp-login.php" && isset($_REQUEST['redirect_to'])) return $path . '?redirect_to='.urlencode(sanitize_text_field($_REQUEST['redirect_to']));
	return $path;
}

//Process Reverse SSO Login
function foxyshop_reverse_sso_login() {

	$redirect_url = apply_filters("foxyshop_reverse_sso_login_failed_destination", get_home_url());
	$result = "failed";

	if (!isset($_GET['foxycart_customer_id']) || !isset($_GET['timestamp']) || !isset($_GET['fc_auth_token'])) {
		wp_redirect($redirect_url);
		die;
	}

	//Build Token
	global $foxyshop_settings;
	$customer_id = sanitize_text_field($_GET['foxycart_customer_id']);
	$timestamp = sanitize_text_field($_GET['timestamp']);
	$auth_token = strtolower(sanitize_text_field($_GET['fc_auth_token']));
	$current_timestamp = time();
	$calculated_auth_token = sha1($customer_id . '|' . $timestamp . '|' . $foxyshop_settings['api_key']);

	//Tokens must expire within the hour and can only be used once; logins also require a current API key
	$max_lifetime = (int)apply_filters('foxyshop_reverse_sso_max_lifetime', 3900);
	$token_ok = !foxyshop_api_key_is_legacy($foxyshop_settings['api_key']) && ctype_digit($customer_id) && $customer_id !== '0'
		&& ctype_digit($timestamp) && $timestamp >= $current_timestamp && $timestamp <= $current_timestamp + $max_lifetime
		&& hash_equals($calculated_auth_token, $auth_token);
	$used_token_key = 'foxyshop_sso_used_' . substr(sha1($auth_token), 0, 32);
	if ($token_ok && get_transient($used_token_key)) $token_ok = false;

	//Token Matches, Do Login
	if ($token_ok) {

		//Lookup ID By FoxyCart Customer ID (must match exactly one user)
		$user_ids = get_users(array('meta_key' => 'foxycart_customer_id', 'meta_value' => $customer_id, 'fields' => 'ID', 'number' => 2));
		$wp_user_id = count($user_ids) == 1 ? (int)$user_ids[0] : 0;

		//Customer Accounts Only
		if ($wp_user_id && foxyshop_is_protected_user($wp_user_id)) {
			$wp_user_id = 0;
		}

		//Login
		if ($wp_user_id) {
			set_transient($used_token_key, 1, $max_lifetime + 300);
			$redirect_url = apply_filters("foxyshop_reverse_sso_login_destination", get_home_url());
			wp_set_auth_cookie($wp_user_id);
			$result = "ok";
		}
	}

	//Is This a JSONP Request?
	if (isset($_GET['callback'])) {
		$callback = is_string($_GET['callback']) && preg_match('/^[A-Za-z_$][A-Za-z0-9_$.]{0,63}$/', $_GET['callback']) ? $_GET['callback'] : 'callback';
		header('Content-Type: application/javascript; charset=utf-8');
		header('X-Content-Type-Options: nosniff');
		echo $callback . '({ "result": "' . esc_js($result) . '"})';
		die;
	}

	wp_redirect($redirect_url);
	die;
}
