<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//When Saving Profile, These Actions Sync Data to FoxyCart
add_action('profile_update', 'foxyshop_profile_update', 5);
add_action('user_register', 'foxyshop_profile_add', 5);

//Runs When WP Profile is Updated
function foxyshop_profile_update($user_id) {
	global $foxyshop_new_password_hash;

	//Get User Info
	$foxycart_customer_id = get_user_meta($user_id, 'foxycart_customer_id', true);

	//Get User Data
	$wp_user = get_userdata($user_id);

	//Set The New Password
	$new_password = $wp_user->user_pass;
	if (isset($foxyshop_new_password_hash)) $new_password = $foxyshop_new_password_hash;

	//Send Updated Info to FoxyCart
	$foxy_data = array("api_action" => "customer_save");
	if ($foxycart_customer_id) $foxy_data["customer_id"] = $foxycart_customer_id;
	$foxy_data["customer_email"] = $wp_user->user_email;
	$foxy_data["customer_password_hash"] = $new_password;
	if ($wp_user->user_firstname) $foxy_data["customer_first_name"] = $wp_user->user_firstname;
	if ($wp_user->user_lastname) $foxy_data["customer_last_name"] = $wp_user->user_lastname;

	//Hook To Add Your Own Function to Update the $foxy_data array with your own data
	if (has_filter('foxyshop_save_sso_to_foxycart')) $foxy_data = apply_filters('foxyshop_save_sso_to_foxycart', $foxy_data, $user_id, "update");

	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
	$foxycart_customer_id = (string)$xml->result != "ERROR" ? (string)$xml->customer_id : "";

	//If FoxyCart Customer ID Returned, Add FoxyCart Customer ID To User Meta
	if ($foxycart_customer_id) {
		add_user_meta($user_id, 'foxycart_customer_id', $foxycart_customer_id, true);
	}
}


//Runs When WP User is Added
function foxyshop_profile_add($user_id) {

	//Get User Data
	$wp_user = get_userdata($user_id);

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
	$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
	$foxycart_customer_id = (string)$xml->result != "ERROR" ? (string)$xml->customer_id : "";

	//If FoxyCart Customer ID Returned, Add FoxyCart Customer ID To User Meta
	if ($foxycart_customer_id) {
		add_user_meta($user_id, 'foxycart_customer_id', $foxycart_customer_id, true);
	}

	//Auto-login if user wasn't logged in before
	//Note that if you don't have the querystring "redirect_to" set on the registration page the page will not redirect anywhere and won't appear logged in at first
	$auto_login = apply_filters("foxyshop_new_user_auto_login", true);
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
	add_action('show_user_profile', 'action_show_user_profile');
	add_action('edit_user_profile', 'action_show_user_profile');
	add_action('personal_options_update', 'action_process_option_update');
	add_action('edit_user_profile_update', 'action_process_option_update');
}

function action_show_user_profile($user) {
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
            <th class="manage-column column-columnname" scope="col"><?php echo FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('Code', 'foxyshop'); ?></th>
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
            <td class="column-columnname"><?php echo $key; ?></td>
            <td class="column-columnname"><?php echo ($val['is_active'] == 1 ? __('Yes', 'foxyshop') : __('No', 'foxyshop')); ?></td>
            <td class="column-columnname"><a href="<?php echo $val['sub_token_url']; ?>&amp;cart=checkout" target="_blank"><?php _e('Update Info', 'foxyshop');?></a> | <a href="<?php echo $val['sub_token_url']; ?>&amp;sub_cancel=true&amp;cart=checkout" target="_blank"><?php _e('Cancel', 'foxyshop');?></a></td>
        </tr>
     <?php
     }
     ?>
    </tbody>
</table>
	<?php
	} //End Subscription View
}

function action_process_option_update($user_id) {
	if (!current_user_can('administrator')) return;
	if (isset($_POST['foxycart_customer_id'])) update_user_meta($user_id, 'foxycart_customer_id', $_POST['foxycart_customer_id']);
}

//Keep redirect_to in URL
add_filter('site_url', 'foxyshop_add_registration_redirect', 5);
function foxyshop_add_registration_redirect($path) {
	if ((strpos($path, "action=register") !== false || strpos($path, "action=lostpassword") !== false) && isset($_REQUEST['redirect_to'])) return $path . '&amp;redirect_to='.urlencode($_REQUEST['redirect_to']);
	if (substr($path, strlen($path)-12) == "wp-login.php" && isset($_REQUEST['redirect_to'])) return $path . '?redirect_to='.urlencode($_REQUEST['redirect_to']);
	return $path;
}
