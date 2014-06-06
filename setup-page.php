<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

if (get_option("foxyshop_setup_required")) {
	add_action('admin_notices', 'foxyshop_show_setup_prompt');
}
function foxyshop_show_setup_prompt() {
	if (isset($_GET['hide_setup_prompt']) || isset($_GET['setup']) || !current_user_can('manage_options')) return;
	$page = (isset($_GET['page']) ? $_GET['page'] : "");
	if ($page != "foxyshop_setup") echo '<div class="updated"><p style="height: 16px;"><img src="' . FOXYSHOP_DIR . '/images/icon.png" alt="" style="float: left; margin: 0 4px 0 0;" /><strong style="float: left; margin-top: 1px;">Your FoxyShop store needs to be synced with your FoxyCart account: <a href="admin.php?page=foxyshop_setup">Setup Now</a></strong><small style="float: right;"><a href="edit.php?post_type=foxyshop_product&page=foxyshop_settings_page&hide_setup_prompt=1">I&rsquo;ll Do It Later</a></small></p></div>';
}


add_action('admin_menu', 'foxyshop_setup_menu');
add_action('admin_init', 'save_foxyshop_setup');

function foxyshop_setup_menu() {
	add_submenu_page(NULL, __('FoxyShop Setup Wizard', 'foxyshop'), NULL, 'manage_options', 'foxyshop_setup', 'foxyshop_setup_legacy');
}

function save_foxyshop_setup() {
	$foxyshop_settings_update_key = (isset($_POST['action']) ? $_POST['action'] : "");
	if ($foxyshop_settings_update_key != "foxyshop_setup_save") return;
	if (!check_admin_referer('save-foxyshop-setup')) return;

	global $foxyshop_settings;

	$domain = $_POST['foxyshop_domain'];
	if ($domain) delete_option("foxyshop_setup_required"); //Delete the setup prompt if domain entered
	if ($domain && strpos($domain, ".") === false) $domain .= ".foxycart.com";
	$foxyshop_settings["domain"] = trim(stripslashes(str_replace("http://","",$domain)));

	$foxyshop_settings['version'] = $_POST['foxyshop_version'];

	//Get Category List if >= 0.7.2
	if ($cached_shipping_categories = foxyshop_get_category_list()) $foxyshop_settings['ship_categories'] = $cached_shipping_categories;

	update_option("foxyshop_settings", $foxyshop_settings);
	delete_option("foxyshop_setup_required");
	wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_settings_page&setup=1');
	die;
}

function foxyshop_setup_legacy() {
	global $foxyshop_settings, $foxycart_version_array;
?>
<div class="wrap">
<div class="icon32" id="icon-options-general"><br></div>
<h2><?php _e('FoxyShop Setup Wizard', 'foxyshop'); ?></h2>

<a href="http://www.foxy-shop.com/?utm_source=plugin&utm_medium=app&utm_campaign=pluginlink_<?php echo FOXYSHOP_VERSION ?>" target="_blank"><img src="<?php echo FOXYSHOP_DIR; ?>/images/logo.png" alt="FoxyShop" style="float: right; margin-left: 20px;" /></a>
<h3>Cool! You've got your new FoxyShop store installed and you are ready to get started.</h3>

<p>The first thing you'll need to do is open up your FoxyCart account in another window so we can copy some information over there. If you don't have a FoxyCart account yet, that's no problem. Here's a short video overview that may help.</p>

<iframe width="640" height="390" src="http://www.youtube.com/embed/TaW1yLbURfc?rel=0" frameborder="0" allowfullscreen></iframe>

<table width="640" style="margin:10px 0 15px 0;">
<tr>
<td align="center" width="50%" style="border-right: 1px solid lightgray;">
<h3 style="margin-top: .5em;">I haven't created an account yet</h3>
<p><a href="http://affiliate.foxycart.com/idevaffiliate.php?id=211_2_3_3" target="_blank" class="button"><?php _e('Create New FoxyCart Account'); ?></a></p>
</td>
<td align="center" width="50%">
<h3 style="margin-top: .5em;">I already have an account</h3>
<p><a href="http://affiliate.foxycart.com/idevaffiliate.php?id=211&url=http://admin.foxycart.com/" target="_blank" class="button"><?php _e('Login To FoxyCart Account'); ?></a></p>
</td>
</tr>
</table>


<form method="post" name="foxycart_settings_form" action="admin.php" onsubmit="return foxyshop_check_settings_form();">
<input type="hidden" name="action" value="foxyshop_setup_save" />

<table class="widefat foxyshopsetup">
	<thead>
		<tr>
			<th colspan="2"><h2 style="margin: 0; padding: 0;";><?php _e('Step 1: Click on Store / Settings', 'foxyshop'); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><h3>1A</h3></td>

				<?php
				if (substr($foxyshop_settings['domain'], -13) == ".foxycart.com" || !$foxyshop_settings['domain']) {
					$foxycart_domain_class = "simple";
					$foxycart_domain = str_replace(".foxycart.com", "", $foxyshop_settings['domain']);
				} else {
					$foxycart_domain_class = "advanced";
					$foxycart_domain = $foxyshop_settings['domain'];
				}
				?>
				<td class="foxycartdomain <?php echo $foxycart_domain_class; ?>">
					<label for="foxyshop_domain"><?php _e('Enter Your FoxyCart Domain'); ?>:</label> <input type="text" name="foxyshop_domain" id="foxyshop_domain" value="<?php echo htmlspecialchars($foxycart_domain); ?>" size="50" />
					<label id="foxydomainsimplelabel">.foxycart.com</label>
					<div id="foxydomain_simple">Have a customized FoxyCart domain like store.yoursite.com? <a href="#" class="foxydomainpicker" rel="advanced">Click here.</a></div>
					<div id="foxydomain_advanced">Have a regular FoxyCart domain like yourstore.foxycart.com? <a href="#" class="foxydomainpicker" rel="simple">Click here.</a></div>
				</td>
		</tr>
		<tr>
			<td><h3>1B</h3></td>
			<td>
				<label for="foxyshop_version">What FoxyCart version are you using?</label>
				<select name="foxyshop_version" id="foxyshop_version" style="min-width: 100px;">
				<?php
				foreach ($foxycart_version_array as $key => $val) {
					echo '<option value="' . $key . '"' . ($foxyshop_settings['version'] == $key ? ' selected="selected"' : '') . '>' . $val . '  </option>'."\n";
				} ?>
				</select>
				<small>Version 1.1 is recommended.</small>
			</td>
		</tr>
	</tbody>
</table>

<br /><br />

<table class="widefat foxyshopsetup infoonly">
	<thead>
		<tr>
			<th colspan="2"><h2 style="margin: 0; padding: 0;";><?php _e('Step 2: Click on Store / Advanced', 'foxyshop'); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><h3>2A</h3></td>
			<td>
				<div>Check the Box to Enable Cart Validation (REQUIRED). Then replace the existing API key with this one:</div>
				<input type="text" id="foxyshop_key" name="key" value="<?php echo $foxyshop_settings['api_key']; ?>" readonly="readonly" onclick="this.select();" />
			</td>
		</tr>
		<tr>
			<td><h3>2B</h3></td>
			<td>
				<div>Check the Box to Enable Store Datafeed (RECOMMENDED). Then enter this datafeed URL:</div>
				<input type="text" id="foxyshop_datafeed_url" name="foxyshop_datafeed_url" value="<?php echo get_bloginfo('url') . '/foxycart-datafeed-' . $foxyshop_settings['datafeed_url_key']; ?>/" readonly="readonly" onclick="this.select();" />
			</td>
		</tr>
		<tr>
			<td><h3>2C</h3></td>
			<td>
				<div>Set Customer Password Hash Type to "phpass" (STRONGLY RECOMMENDED).</div>
			</td>
		</tr>

	</tbody>
</table>



<p><input type="submit" class="button-primary" value="<?php _e('Save and Get Started!', 'foxyshop'); ?>" /></p>

<?php wp_nonce_field('save-foxyshop-setup'); ?>
</form>

<script type="text/javascript">
jQuery(document).ready(function($){
	$(".foxydomainpicker").click(function(e) {
		$(".foxycartdomain").removeClass("simple advanced");
		$(".foxycartdomain").addClass($(this).attr("rel"));
		$("#foxyshop_domain").focus().select();
		e.preventDefault();
		return false;
	});
});

function foxyshop_check_settings_form() {
	return true;
}
</script>

</div>
<?php } ?>
