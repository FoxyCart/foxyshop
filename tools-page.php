<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

if (isset($_REQUEST['foxyshop_save_tools'])) add_action('admin_init', 'foxyshop_save_tools');
function foxyshop_save_tools() {
	global $foxyshop_settings;

	//Import Settings
	if (isset($_POST['foxyshop_import_settings'])) {
		if (!check_admin_referer('import-foxyshop-settings')) return;

		$encrypt_key = "foxyshop_encryption_key_16";
		$foxyshop_import_settings = str_replace("\n","",$_POST['foxyshop_import_settings']);
		$decrypted = explode("|-|", rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($encrypt_key), base64_decode($foxyshop_import_settings), MCRYPT_MODE_CBC, md5(md5($encrypt_key))), "\0"));
		if (count($decrypted) != 3) {
			wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_tools&importerror=1');
			exit;
		} else {
			update_option("foxyshop_settings", unserialize($decrypted[0]));
			update_option("foxyshop_category_sort", unserialize($decrypted[1]));
			update_option("foxyshop_saved_variations", unserialize($decrypted[2]));
			delete_option("foxyshop_setup_required");
			wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_tools&import=1');
			exit;
		}

	//Scan For Old Variations
	} elseif (isset($_GET['foxyshop_old_variations_scan'])) {
		if (!check_admin_referer('foxyshop_old_variations_scan')) return;
		$foxyshop_settings['foxyshop_version'] = "2.9";
		update_option("foxyshop_settings", $foxyshop_settings);
		wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_tools&oldvars=1');
		exit;

	//Update FoxyCart Template
	} elseif (isset($_POST['foxycart_cart_update_save']) || isset($_POST['foxycart_checkout_update_save']) || isset($_POST['foxycart_receipt_update_save'])) {
		if (!check_admin_referer('update-foxycart-template')) return;
		$foxyshop_settings['template_url_cart'] = $_POST['foxycart_cart_update'];
		$foxyshop_settings['template_url_checkout'] = $_POST['foxycart_checkout_update'];
		$foxyshop_settings['template_url_receipt'] = $_POST['foxycart_receipt_update'];
		update_option("foxyshop_settings", $foxyshop_settings);

		//If just clearing the urls, return now
		if (empty($_POST['foxycart_cart_update']) && empty($_POST['foxycart_checkout_update'])) {
			wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_tools&updatetemplate=clear');
			exit;
		}

		//Cart
		if (isset($_POST['foxycart_cart_update_save'])) {

			$foxy_data = array("api_action" => "store_template_cache", "template_type" => "cart", "template_url" => $_POST['foxycart_cart_update']);
			$foxy_response = foxyshop_get_foxycart_data($foxy_data);
			$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
			if ($xml->result != "ERROR") {
				wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_tools&updatetemplate=cart');
			} else {
				wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_tools&updatetemplate=error&error='.urlencode((string)$xml->messages->message));
			}
			exit;


		//Checkout
		} elseif (isset($_POST['foxycart_checkout_update_save'])) {
			$foxy_data = array("api_action" => "store_template_cache", "template_type" => "checkout", "template_url" => $_POST['foxycart_checkout_update']);
			$foxy_response = foxyshop_get_foxycart_data($foxy_data);
			$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
			if ($xml->result != "ERROR") {
				wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_tools&updatetemplate=checkout');
			} else {
				wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_tools&updatetemplate=error&error='.urlencode((string)$xml->messages->message));
			}
			exit;

		//Receipt
		} elseif (isset($_POST['foxycart_receipt_update_save'])) {
			$foxy_data = array("api_action" => "store_template_cache", "template_type" => "receipt", "template_url" => $_POST['foxycart_receipt_update']);
			$foxy_response = foxyshop_get_foxycart_data($foxy_data);
			$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
			if ($xml->result != "ERROR") {
				wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_tools&updatetemplate=receipt');
			} else {
				wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_tools&updatetemplate=error&error='.urlencode((string)$xml->messages->message));
			}
			exit;
		}

	//Process Saved Variations
	} elseif (isset($_POST['foxyshop_process_saved_variations'])) {
		if (!check_admin_referer('wp-foxyshop-process-saved-variations')) return;

		$currentID = 1;
		$variations = array();
		for ($i=1;$i<=(int)$_POST['max_variations'];$i++) {

			//Get Target From Sort Numbers
			$target_id = 0;
			for ($k=1;$k<=(int)$_POST['max_variations'];$k++) {
				$tempid = (isset($_POST['sort'.$k]) ? $_POST['sort'.$k] : 0);
				if ($tempid == $i) $target_id = $k;
			}

			//Set Values, Skip if Not There or Empty Name
			if ($target_id == 0) continue;
			$_variationRefName = trim(str_replace(".","",str_replace('"','',$_POST['_variation_ref_name_'.$target_id])));
			$_variationName = trim(str_replace(".","",str_replace('"','',$_POST['_variation_name_'.$target_id])));
			$_variationType = $_POST['_variation_type_'.$target_id];
			$_variationDisplayKey = $_POST['_variation_dkey_'.$target_id];
			$_variationRequired = (isset($_POST['_variation_required_'.$target_id]) ? $_POST['_variation_required_'.$target_id] : '');
			if ($_POST['_variation_name_'.$target_id] == "") continue;

			//Get Values
			if ($_variationType == 'text') {
				$_variationValue = $_POST['_variation_textsize1_'.$target_id]."|".$_POST['_variation_textsize2_'.$target_id];
			} elseif ($_variationType == 'textarea') {
				$_variationValue = (int)$_POST['_variation_textareasize_'.$target_id];
				if ($_variationValue == 0) $_variationValue = 3;
			} elseif ($_variationType == 'upload') {
				$_variationValue = $_POST['_variation_uploadinstructions_'.$target_id];
			} elseif ($_variationType == 'descriptionfield') {
				$_variationValue = $_POST['_variation_description_'.$target_id];
			} elseif ($_variationType == 'dropdown') {
				$_variationValue = $_POST['_variation_value_'.$target_id];
			} elseif ($_variationType == 'checkbox') {
				$_variationValue = $_POST['_variation_checkbox_'.$target_id];
			} elseif ($_variationType == 'hiddenfield') {
				$_variationValue = $_POST['_variation_hiddenfield_'.$target_id];
			} elseif ($_variationType == 'radio') {
				$_variationValue = $_POST['_variation_radio_'.$target_id];
			}

			$variations[$currentID] = array(
				"refname" => stripslashes($_variationRefName),
				"name" => stripslashes($_variationName),
				"type" => stripslashes($_variationType),
				"value" => stripslashes($_variationValue),
				"displayKey" => stripslashes($_variationDisplayKey),
				"required" => stripslashes($_variationRequired)
			);
			$currentID++;
		}
		if (count($variations) > 0) {
			update_option('foxyshop_saved_variations', $variations);
		} else {
			delete_option('foxyshop_saved_variations');
		}

		wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_tools&processedvars=1');
		exit;

	//Reset API Key
	} elseif (isset($_GET['foxyshop_api_key_reset'])) {
		if (!check_admin_referer('reset-foxyshop-api-key')) return;
		$foxyshop_settings['api_key'] = "sp92fx".hash_hmac('sha256',rand(21654,6489798),"dkjw82j1".time());
		update_option("foxyshop_settings", $foxyshop_settings);
		wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_tools&key=1');
		exit;
	}
}

add_action('admin_menu', 'foxyshop_tools_menu');
function foxyshop_tools_menu() {
	add_submenu_page('edit.php?post_type=foxyshop_product', __('Tools', 'foxyshop'), __('Tools', 'foxyshop'), apply_filters('foxyshop_tools_perm', 'manage_options'), 'foxyshop_tools', 'foxyshop_tools');
}

function foxyshop_check_plugin_status($plugin) {
	$plugin_path = str_replace('/foxyshop', "", FOXYSHOP_PATH);
	$plugin_path_short = $plugin . '/' . $plugin . ".php";
	if ($plugin == "csv-importer") $plugin_path_short = $plugin . '/csv_importer.php';
	if (!file_exists($plugin_path . "/" . $plugin_path_short)) {
		echo '<a href="plugin-install.php?tab=search&type=term&s=' . $plugin . '" class="button">Install</a>';
	} elseif (!is_plugin_active($plugin_path_short)) {
		echo '<a href="plugins.php?action=activate&amp;plugin=' . $plugin_path_short . '&amp;plugin_status=all&amp;paged=1&amp;s&amp;_wpnonce=' . wp_create_nonce("activate-plugin_".$plugin_path_short) . '" style="color: #21759B;" class="button">Activate Now</a>';
	} else {
		echo '<a href="#" class="button" disabled="disabled" onclick="return false;">Installed &amp; Activated</a>';
	}
}

function foxyshop_tools() {
	global $foxyshop_settings;
?>
<div id="foxyshop_settings_wrap" class="wrap">

	<div class="icon32" id="icon-tools"><br></div>
	<h2>FoxyShop Tools</h2>


	<?php
	//Confirmation Import
	if (isset($_GET['import'])) echo '<div class="updated"><p>' . __('Your Settings Have Been Imported', 'foxyshop') . '.</p></div>';

	//Import Error
	if (isset($_GET['importerror'])) echo '<div class="error"><p>' . __('There was an error with your import settings and they could not be imported. The decrypted value was invalid.', 'foxyshop') . '</p></div>';

	//Confirmation Key Reset
	if (isset($_GET['key'])) echo '<div class="updated"><p>' . sprintf(__('Your API Key Has Been Reset: "%s". Please Update FoxyCart With Your New Key.', 'foxyshop'), $foxyshop_settings['api_key']) . '</p></div>';

	//Confirmation Old Vars
	if (isset($_GET['oldvars'])) echo '<div class="updated"><p>' . __('Scan for old variations has been successfully completed.', 'foxyshop') . '</p></div>';

	//Flush Rewrite Rules
	if (isset($_GET['foxyshop_flush_rewrite_rules'])) echo '<div class="updated"><p>' . __('WordPress rewrite rules have been flushed.', 'foxyshop') . '</p></div>';

	//Process Saved Variations
	if (isset($_GET['processedvars'])) echo '<div class="updated"><p>' . __('Saved variations have been successfully updated.', 'foxyshop') . '</p></div>';

	//Update Template
	if (isset($_GET['updatetemplate'])) {
		if ($_GET['updatetemplate'] == "error") {
			echo '<div class="updated"><p>' .  $_GET['error'] . '</p></div>';
		} elseif ($_GET['updatetemplate'] == "clear") {
			echo '<div class="updated"><p>' . __('Your saved URLs have been cleared.', 'foxyshop') . '</p></div>';
		} else {
			echo '<div class="updated"><p>' . sprintf(__('The %s template has been successfully updated.', 'foxyshop'), esc_attr($_GET['updatetemplate'])) . '</p></div>';
		}
	}


	//Get Export Settings
	if (function_exists('mcrypt_encrypt')) {
		$encrypt_key = "foxyshop_encryption_key_16";
		$foxyshop_export_settings = serialize(get_option('foxyshop_settings')) . "|-|";
		$foxyshop_export_settings .= serialize(get_option('foxyshop_category_sort')) . "|-|";
		$foxyshop_export_settings .= serialize(get_option('foxyshop_saved_variations'));
		$foxyshop_export_settings = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($encrypt_key), $foxyshop_export_settings, MCRYPT_MODE_CBC, md5(md5($encrypt_key))));
		$foxyshop_export_settings = wordwrap($foxyshop_export_settings, 58, "\n", true);
	}

	$recommend_icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAApRJREFUeNqEU01IVFEUPu9n3nszvjuvGadGBpswLZIJzR8mslXQIiiICJxKpVwULiKI2oWb0iBo06YgKWih6KJFbUrDwlxYDISRkQtNKcbNkGY6+pr37rud+3xOo/Rz4Hvv3HvP951z7o8Am+z2EQlqokLxVIPDIEUZiNRhA+in1xeO99ogt+0T3QGntNSs+YwVyGF0U4eu3utYys4tjT64rrAiAW5ya63okkX8CAgJYdqF9dbyhsP1jArEH4wItg2S44nr6lqVMvzdLkmanqg4cCzJB9Pp4YmfFMa5QFQXQPWY8r/ITRe6TwsgE9sylyfHhifzlA3uDIsbAv8k0EGCpLK+vfuUICpk8cvEx2d9D6eIkLMMTejE9e9e3AvEULFADHFC1Eh13bmuNsakIKMUSnc3Js7ebEpszvKy64y0LtDIdxpLJtHqZOX25NH91AYCsLaTq4uLUBIKQW5hoUD+MTeVsR2gbgsWZS0NzVea9bKKWN40YSVnAsXtppjdQaiaBhoh7lz/rcuP10XwDJ66AjYFUrI1HsthJtuywMrnN4AYBoRw3q/r0H6j5+TyfHZ5tOfao6U8jLoCeQd8FAMcDsziAjMzx3FvFPf5+uynDwuDfXfTXvZCJSK2oPAAm5ddRGZI5neG+1w8XrUnlLrYeXCbLmT8ivAqoHgXybSAZb9OfwtGYqU2D/ZQqAD/PMHI8yeZ929HMkjrn8GDnME9Pc8FchbrGRy4r5fFd5UbkWhQUQOqviWi+9SAH0TRJ6LxtvbWJUs/j79+d+cNG5r5fSDuM5Bqy6DCUCHhk6BqhwHhqA4hTYawJIKBATJCx3ZWR2ahd2gaxpCTRcxjm9R9Rwg/IoDwwf/NQqzwK4ICzi8BBgBdLjNedsdOVgAAAABJRU5ErkJggg==";
	$export_icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAACH0lEQVR4nKWSTUhUYRSGnzv33snRdEY0M0dJaSDTzRBkiZhZkNCiTZC1CIQQahFEq9q3aBFRqzZFkUQRuayFEKlZhv0ZGsUw6VD4N5rj5KjT3O9+p4VphCVKZ3PO5nl4Obzwn2M0tLSdBE6vk7vedfvETQDjQOs9qT1Suy66t72XEn8y2Hb51KglIiwk59YlEJHl29paMMPEm4/4sn2rQspOkAi2Exg9TGizQ372/ByAVZSX5tihbYTD4X/C8fkxWp80URZSjA1/JnesGiZ2LSZYS+Svs0MEcjZSWDTNpiKDl11vSXmmioHkqoKekQ4exe4ykorxLT1Dmcpg2x727PXR3Rn90PigouSvAldcLjxrITbXjzdvGl+xy3Y/OMrGMAy8tkFDY8Ds6ZqN/iEQEdJqnrOdzUwuRCktzKK8oA7LtDFNzZDqxDAMDMNDqRlGpC/LAtBa47ouIsKtwatMJL+wu6Se0fQkjwfekevNI60WCFULhqEoZAcPu185rnZLPUsCpRRKKSKJQcr9lbye6qcpeJz7+19wo66D6vydJGc8+N0Kevri5KS21Dw9Ohy3AJRSOI6D1hqfmY3X3sD58DVMLLQStHZIOd+pyjrIwKcogalKiuM1w9DNCsG5qkuICNrVKFForRERzoQucid2hXBuPbOJIBmc3z2IRCJkMhlEZBH+BS1Vdmnvoxl+wHMdWX78omA8SWT8/Vo6tWJ+AquVAo19QSjUAAAAAElFTkSuQmCC";
	$key_icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAhtJREFUeNqEUktrE1EU/iaZtDVILRMSpw9FRUipdKGWVNSFCr6gBBHERxFK2oX4D7oUpA9dunFTQVcWkaILXQhiXVlRi1JtSgO1vttUS6FmJp2Zezx3Uoc8JnrgY7j3nu9xOKMQEf7WxGAdyqqZcZWRZGiMX4wxRj8jKxtUVK8TihK8u3lvalMk3oWw3o7c4gft5/SD3oXXt5JEzjnueaoUJyhKojP5ffzMHS3cYEDkXkHk5xGo2YJAuAPGygak7/VkWWRXtQSDekevVlf7EvZy2r34MTWLxenHiLU+QePuI2js7Il+ezFyJeBDrpHxIzu3QZhM5oREAkuz89hzaRRLmU9wjAwammQbkn4Caww9GDBAwnEBRmRHM97cPAttexPIsaFYM7I3Wm2ELIl8VBKlu0wRa92KWLzFPUsBx867fb4CoY161I0u7IKAoHUh4QkaKzIoHlaM8PzaQWo7dRn57CPIDUk3ElZBTEK6Wza+Ts6scvt1tZycOJ+E+X0UtVqLeyfWeI2WyUQLgpMYyzl8mcyskqDT/DznCTwb2k/7urs8shpJIT024InnFt6625SxGUOd/eac/HNdgfFhdr5wksn3PbJaf5hfBtCemlDWNeS34q9TxocPUOJiH8zPN3ydiwT8SlERCoKsj97Mav2hcueSBTGsojOpihqCEj7KzsdZ6Dembh/7h2EJuRDh3UiiZK7/RK6oPwIMANlx/pat4bMTAAAAAElFTkSuQmCC";
	$misc_icon = "data:image/png;base64,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";
	$remove_icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAH5SURBVDiNfZJPTxNBGMZ/U7aUbaTVg/VQQyKCaEhIlAOEpAaVi0TwZEI9c/DsmS/AV5Cjxj8H5YD1I5CQtOgFTtoDGoM0oaHtbtmZ6YyHte22Jb7Je3n29zzz7jsjnr14aZRWgn91JZWyANVaraMBPHpw3964dbNHA3CUVkLrVkeQKgyLagCe54nK8Um/HwfbK9gBJKym7/Pn9/EFAf1lL47wPZ9q9Www4PHDnLbWdoISw8MABFL2gKnRS9Qb3kCAKP/8dQ4kACiXESfhf9qZGUgmQ0prRKkE1mIzGRgf704gVfckIWBk6xVojZ6fR62vAxDf3maoUEDE4zQ3NrARjxNI1Z0ncw35ZIX4p4+wu4u6ew+bTkPhC8oY9OpTWlczEPE4UkUCABYXkd++Ir7/gDevwXXRWmPv3MbkctDHO4HqXRYA+TxicxNOT8N9JJOwtgZaDaCOlIMioynM9DS2WAx3MzlJzE32jP7fCezREapYRLTfxP4+Q7OzxCYmBtiYlIqebng03r7jvNUimJoiyF6naQyN9x8IanX6+VigFNGuFz7jVSo0hxOY5WVaqyv4IoZfrVLf2aGfj0kpabd/eMjZ3h6+NdilJVRiBJ2+jFlYwLeGWqmId3BA1OMEkWtpuS5O/jlWCMzYGO1vdm4OJ5sFQLkuJuL5C0rrI1wGe+BQAAAAAElFTkSuQmCC";
	$vars_icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAABGdBTUEAANbY1E9YMgAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAFlUExURf///01NTdnZ2W5ubmlpadLS0q2trWpqamdnZ3FxcW9vb2hoaN/w93R0dNXV1XV1dWuFko++bo68bJycnHmtqiSQELPR03i4xXewsI+9a3aMlt/w9oy5rOTx94KZaZC8aG234GZ3T2K14GdzWUWuMnGHkqOjo2Cz35exbYmndWqFkrbU1Hd3d05lM4+6ZtLcx4S+1iihEnaFhYO8yYmneHq+4o3Bp4+1Z3OJULLR0UyxOpC+rI+uZ3qRXJHBq2y34Nzu95C0ZzioJD1tNJLCrHGHUnGGkZG1ZXCKl9/u9crKyt3v9yabEURyPNDfv+Hv9nK64GGQQsnJybXT01+y3uDt7YS0sHChuanHtHWqaGe24W2Gkne3yXy/4o2ncnuKkV9+WXy+33a84nqsp4aeaGy/XY7DsIe2rCedEoSfXiWXEW+tr9zt9n691oWFhezs7DxnNGa24ZG+bm2GkWq34XmtdX9/fyhM8JoAAAB3dFJOU/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////8AqZ+kZQAAANhJREFUeNpi4GVngQN23rIyBnYGJMAOFOBmiLJXCEgqKVRSD2HgBgqwMIR7uBWrRgsIaDEyMnLqAAWkPP1zHLx5eKQZUxmZOIEC8bKmsYkx4oyMCSq+jIxAAaPQXAPjbAlGKyfDjCxGoKHK+mHpMskijIzOPgVAFewMin7Wdi5pZqWRjKKMTFwMHAzCDJbawWIRJppAW8ACeflxcimZGhautja6YAE1BndHcz15SaEiwUCgtZwcHKxwwMfEzFBWxszExubFxsbGx8YWBBVAAiABfmYkwA8QYACmrCI1vQnmgQAAAABJRU5ErkJggg==";
	?>

	<div style="clear: both; margin-top: 14px;"></div>

	<table class="widefat" id="recommended_plugins_container" style="margin-bottom: 30px;">
		<thead>
			<tr>
				<th><img src="<?php echo $recommend_icon; ?>" alt="" /><?php _e("Recommended Companion Plugins", 'foxyshop'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<ul id="foxyshoprecommendedplugins">
						<li><h3><a href="plugin-install.php?tab=search&type=term&s=taxonomy+images">Taxonomy Images</a></h3>
						(set category images)
						<?php
						foxyshop_check_plugin_status("taxonomy-images");
						?>
						</li>

						<li><h3><a href="plugin-install.php?tab=search&type=term&s=custom+field+bulk+editor">Custom Field Bulk Editor</a></h3>
						(change products in bulk)
						<?php
						foxyshop_check_plugin_status("custom-field-bulk-editor");
						?>
						</li>

						<li><h3><a href="plugin-install.php?tab=search&type=term&s=duplicate+post">Duplicate Post</a></h3>
						(quickly copy products)
						<?php
						foxyshop_check_plugin_status("duplicate-post");
						?>
						</li>

						<li><h3><a href="plugin-install.php?tab=search&type=term&s=csv+importer">CSV Importer</a></h3>
						(import products - <a href="http://www.foxy-shop.com/2011/03/importing-products/" target="_blank">guide here</a>)
						<?php
						foxyshop_check_plugin_status("csv-importer");
						?>
						</li>

						<li><h3><a href="plugin-install.php?tab=search&type=term&s=cimy+user+extra+fields">Cimy User Extra Fields</a></h3>
						(manage registration form)
						<?php
						foxyshop_check_plugin_status("cimy-user-extra-fields");
						?>
						</li>

						<li><h3><a href="plugin-install.php?tab=search&type=term&s=capsman">Capability Manager</a></h3>
						(change roles and user abilities)
						<?php
						foxyshop_check_plugin_status("capsman");
						?>
						</li>

						<li><h3><a href="plugin-install.php?tab=search&type=term&s=sidebar+login">Sidebar Login</a></h3>
						(adds a login widget to your sidebar)
						<?php
						foxyshop_check_plugin_status("sidebar-login");
						?>
						</li>

						<li><h3><a href="plugin-install.php?tab=search&type=term&s=login-logo">Login Logo</a></h3>
						(easily add logo to your login pages)
						<?php
						foxyshop_check_plugin_status("login-logo");
						?>
						</li>

					</ul>

				</td>
			</tr>
		</tbody>
	</table>

	<table class="widefat" id="misc_tools_container" style="margin-bottom: 30px;">
		<thead>
			<tr>
				<th><img src="<?php echo $misc_icon; ?>" alt="" /><?php _e('Misc Tools', 'foxyshop'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (version_compare($foxyshop_settings['version'], '0.7.2', ">=")) : ?>

			<tr>
				<td>
					<form method="post" name="foxyshop_cache_form_1" action="">
					<h3 style="margin-top: 0;">Update FoxyCart Template <span> <a href="http://wiki.foxycart.com/v/1.0/templates" target="_blank"><?php _e('Instructions', 'foxyshop'); ?></a></span></h3>

					<label for="foxycart_cart_update" style="width: 150px;">Cart Template URL</label>
					<input type="text" name="foxycart_cart_update" id="foxycart_cart_update" style="width: 450px;" value="<?php echo htmlspecialchars($foxyshop_settings['template_url_cart']); ?>" />
					<input type="submit" name="foxycart_cart_update_save" value="<?php _e('Update Cart Cache', 'foxyshop'); ?>" class="button" />

					<div style="clear: both;"></div>

					<label for="foxycart_checkout_update" style="width: 150px;">Checkout Template URL</label>
					<input type="text" name="foxycart_checkout_update" id="foxycart_checkout_update" style="width: 450px;" value="<?php echo htmlspecialchars($foxyshop_settings['template_url_checkout']); ?>" />
					<input type="submit" name="foxycart_checkout_update_save" value="<?php _e('Update Checkout Cache', 'foxyshop'); ?>" class="button" />

					<div style="clear: both;"></div>

					<label for="foxycart_receipt_update" style="width: 150px;">Receipt Template URL</label>
					<input type="text" name="foxycart_receipt_update" id="foxycart_receipt_update" style="width: 450px;" value="<?php echo htmlspecialchars($foxyshop_settings['template_url_receipt']); ?>" />
					<input type="submit" name="foxycart_receipt_update_save" value="<?php _e('Update Receipt Cache', 'foxyshop'); ?>" class="button" />

					<?php wp_nonce_field('update-foxycart-template'); ?>
					<input type="hidden" name="foxyshop_save_tools" value="1" />
					</form>
				</td>
			</tr>



			<?php endif; ?>

			<tr>
				<td>
					<span>Product pages not showing up?</span> <a href="edit.php?post_type=foxyshop_product&amp;page=foxyshop_tools&amp;foxyshop_flush_rewrite_rules=1" class="button"><?php _e('Flush Rewrite Rules', 'foxyshop'); ?></a>
				</td>
			</tr>
			<tr>
				<td>
					<span>Recently imported products with old variation method?</span> <a href="edit.php?foxyshop_old_variations_scan=1&amp;foxyshop_save_tools=1&amp;_wpnonce=<?php echo wp_create_nonce('foxyshop_old_variations_scan'); ?>" class="button"><?php _e('Scan For Old Variations', 'foxyshop'); ?></a>
				</td>
			</tr>
			<tr>
				<td>
					<span>Need a new API key?</span> <a href="edit.php?foxyshop_api_key_reset=1&amp;foxyshop_save_tools=1&amp;_wpnonce=<?php echo wp_create_nonce('reset-foxyshop-api-key'); ?>" onclick="return apiresetcheck();" class="button"><?php _e('Reset API Key', 'foxyshop'); ?></a>
				</td>
			</tr>
		</tbody>
	</table>

	<form method="post" name="foxyshop_saved_vars_form" action="">
	<table class="widefat">
		<thead>
			<tr>
				<th><img src="<?php echo $vars_icon; ?>" alt="" /><?php _e('Saved Variations', 'foxyshop'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>

<?php
$var_type_array = array(
	'dropdown' => __("Dropdown List", 'foxyshop'),
	'radio' => __("Radio Buttons", 'foxyshop'),
	'checkbox' => __("Checkbox", 'foxyshop'),
	'text' => __("Single Line of Text", 'foxyshop'),
	'textarea' => __("Multiple Lines of Text", 'foxyshop'),
	'upload' => __("Custom File Upload", 'foxyshop'),
	'hiddenfield' => __("Hidden Field", 'foxyshop'),
	'descriptionfield' => __("Description Field", 'foxyshop')
);
$variation_key = __('Name{p+1.50|w-1|c:product_code|y:foxycart_category|dkey:display_key|ikey:image_id}', 'foxyshop');

//Setup Variations
$variations = get_option('foxyshop_saved_variations');
if (!is_array($variations)) $variations = array();

echo '<input type="hidden" id="variation_order_value" name="variation_order_value" />'."\n";

echo '<div id="variation_sortable">'."\n";
$max_variations = count($variations);
if ($max_variations == 0) $max_variations = 1;
for ($i=1;$i<=$max_variations;$i++) {
	$dkeyhide = "";
	$_variationRefName = '';
	$_variationName = '';
	$_variation_type = 'dropdown';
	$_variationValue = '';
	$_variationDisplayKey = '';
	$_variationRequired = '';
	if (isset($variations[$i])) {
		$_variationName = isset($variations[$i]['name']) ? $variations[$i]['name'] : '';
		$_variationRefName = isset($variations[$i]['refname']) ? $variations[$i]['refname'] : '';
		$_variation_type = isset($variations[$i]['type']) ? $variations[$i]['type'] : 'dropdown';
		$_variationValue = isset($variations[$i]['value']) ? $variations[$i]['value'] : '';
		$_variationDisplayKey = isset($variations[$i]['displayKey']) ? $variations[$i]['displayKey'] : '';
		$_variationRequired = isset($variations[$i]['required']) ? $variations[$i]['required'] : '';
	}
	?>
	<div class="product_variation" rel="<?php echo $i; ?>" id="variation<?php echo $i; ?>">
		<input type="hidden" name="sort<?php echo $i; ?>" id="sort<?php echo $i; ?>" class="variationsort" value="<?php echo $i; ?>" />
		<input type="hidden" name="dropdownradio_value_<?php echo $i; ?>" id="dropdownradio_value_<?php echo $i; ?>" value="" />
		<input type="hidden" name="text1_value_<?php echo $i; ?>" id="text1_value_<?php echo $i; ?>" value="" />
		<input type="hidden" name="text2_value_<?php echo $i; ?>" id="text2_value_<?php echo $i; ?>" value="" />
		<input type="hidden" name="textarea_value_<?php echo $i; ?>" id="textarea_value_<?php echo $i; ?>" value="" />
		<input type="hidden" name="descriptionfield_value_<?php echo $i; ?>" id="descriptionfield_value_<?php echo $i; ?>" value="" />
		<input type="hidden" name="checkbox_value_<?php echo $i; ?>" id="checkbox_value_<?php echo $i; ?>" value="" />
		<input type="hidden" name="upload_value_<?php echo $i; ?>" id="upload_value_<?php echo $i; ?>" value="" />
		<input type="hidden" name="hiddenfield_value_<?php echo $i; ?>" id="hiddenfield_value_<?php echo $i; ?>" value="" />

		<!-- //// VARIATION HEADER //// -->
		<div class="foxyshop_field_control">
			<a href="#" class="button deleteVariation" rel="<?php echo $i; ?>">Delete</a>
			<label for="_variation_ref_name_<?php echo $i; ?>"><?php _e('Reference Name', 'foxyshop'); ?></label>
			<input type="text" name="_variation_ref_name_<?php echo $i; ?>" class="variation_ref_name" id="_variation_ref_name_<?php echo $i; ?>" value="<?php echo esc_attr($_variationRefName); ?>" />
			<span><?php _e('Displayed in Dropdown Menu', 'foxyshop'); ?></span>
		</div>
		<div class="foxyshop_field_control">
			<label for="_variation_name_<?php echo $i; ?>"><?php _e('Variation Name', 'foxyshop'); ?></label>
			<input type="text" name="_variation_name_<?php echo $i; ?>" class="variation_name" id="_variation_name_<?php echo $i; ?>" value="<?php echo esc_attr($_variationName); ?>" />

			<label for="_variation_type_<?php echo $i; ?>" class="variationtypelabel"><?php _e('Variation Type', 'foxyshop'); ?></label>
			<select name="_variation_type_<?php echo $i; ?>" id="_variation_type_<?php echo $i; ?>" class="variationtype">
			<?php
			foreach ($var_type_array as $var_name => $var_val) {
				echo '<option value="' . $var_name . '"' . ($_variation_type == $var_name ? ' selected="selected"' : '') . '>' . $var_val . '  </option>'."\n";
			} ?>
			</select>
		</div>


		<div class="variation_holder" id="variation_holder_<?php echo $i; ?>">

			<?php if ($_variation_type == "dropdown") : ?>
				<!-- Dropdown -->
				<div class="foxyshop_field_control dropdown variationoptions">
					<label for="_variation_value_<?php echo $i; ?>"><?php _e('Items in Dropdown', 'foxyshop'); ?></label>
					<textarea name="_variation_value_<?php echo $i; ?>" id="_variation_value_<?php echo $i; ?>"><?php echo $_variationValue; ?></textarea>
					<div class="variationkey"><?php echo $variation_key; ?></div>
				</div>

			<?php elseif($_variation_type == "radio") : ?>
				<!-- Radio Buttons -->
				<div class="foxyshop_field_control radio variationoptions">
					<label for="_variation_radio_<?php echo $i; ?>"><?php _e('Radio Button Options', 'foxyshop'); ?></label>
					<textarea name="_variation_radio_<?php echo $i; ?>" id="_variation_radio_<?php echo $i; ?>"><?php echo $_variationValue; ?></textarea>
					<div class="variationkey"><?php echo $variation_key; ?></div>
				</div>

			<?php elseif($_variation_type == "text") : ?>
				<!-- Text Box -->
				<?php $arrVariationTextSize = explode("|",esc_attr($_variationValue)); ?>
				<div class="foxyshop_field_control text variationoptions">
					<div class="foxyshop_field_control">
						<label for="_variation_textsize1_<?php echo $i; ?>"><?php _e('Text Box Size', 'foxyshop'); ?></label>
						<input type="text" name="_variation_textsize1_<?php echo $i; ?>" id="_variation_textsize1_<?php echo $i; ?>" value="<?php if (isset($arrVariationTextSize)) echo $arrVariationTextSize[0]; ?>" /> <span><?php _e('characters', 'foxyshop'); ?></span>
					</div>
					<div class="foxyshop_field_control">
						<label for="_variation_textsize2_<?php echo $i; ?>"><?php _e('Maximum Chars', 'foxyshop'); ?></label>
						<input type="text" name="_variation_textsize2_<?php echo $i; ?>" id="_variation_textsize2_<?php echo $i; ?>" value="<?php if (isset($arrVariationTextSize)) echo $arrVariationTextSize[1]; ?>" /> <span><?php _e('characters', 'foxyshop'); ?></span>
					</div>
					<div style="clear: both;"></div>
				</div>

			<?php elseif($_variation_type == "textarea") : ?>
				<!-- Textarea -->
				<div class="foxyshop_field_control textarea variationoptions">
					<label for="_variation_textareasize_<?php echo $i; ?>"><?php _e('Lines of Text', 'foxyshop'); ?></label>
					<input type="text" name="_variation_textareasize_<?php echo $i; ?>" id="_variation_textareasize_<?php echo $i; ?>" value="<?php echo esc_attr($_variationValue); ?>" /> <span>(<?php _e('default is', 'foxyshop'); ?> 3)</span>
				</div>

			<?php elseif($_variation_type == "descriptionfield") : ?>
				<!-- Description Field -->
				<div class="foxyshop_field_control descriptionfield variationoptions">
					<label for="_variation_description_<?php echo $i; ?>"><?php _e('Descriptive Text', 'foxyshop'); ?></label>
					<textarea name="_variation_description_<?php echo $i; ?>" id="_variation_description_<?php echo $i; ?>"><?php echo $_variationValue; ?></textarea>
				</div>

			<?php elseif($_variation_type == "hiddenfield") : ?>
				<!-- Hidden Field -->
				<div class="foxyshop_field_control hiddenfield variationoptions">
					<div class="foxyshop_field_control">
						<label for="_variation_hiddenfield_<?php echo $i; ?>"><?php _e('Value', 'foxyshop'); ?></label>
						<input type="text" name="_variation_hiddenfield_<?php echo $i; ?>" id="_variation_hiddenfield_<?php echo $i; ?>" value="<?php echo $_variationValue; ?>" />
					</div>
				</div>
				<?php $dkeyhide = ' style="display: none;"'; ?>

			<?php elseif($_variation_type == "checkbox") : ?>
				<!-- Checkbox -->
				<div class="foxyshop_field_control checkbox variationoptions" style="background-color: transparent;">
					<label for="_variation_checkbox_<?php echo $i; ?>"><?php _e('Value', 'foxyshop'); ?></label>
					<input type="text" name="_variation_checkbox_<?php echo $i; ?>" id="_variation_checkbox_<?php echo $i; ?>" value="<?php echo $_variationValue; ?>" class="variation_checkbox_text" />
					<div class="variationkey"><?php echo $variation_key; ?></div>
				</div>

			<?php elseif($_variation_type == "upload") : ?>
				<!-- Custom File Upload -->
				<div class="foxyshop_field_control upload variationoptions">
					<label for="_variation_uploadinstructions_<?php echo $i; ?>"><?php _e('Instructions', 'foxyshop'); ?></label>
					<textarea name="_variation_uploadinstructions_<?php echo $i; ?>" id="_variation_uploadinstructions_<?php echo $i; ?>"><?php echo $_variationValue; ?></textarea>
				</div>

			<?php endif; ?>
		</div>

		<!-- //// DISPLAY KEY //// -->
		<div class="foxyshop_field_control dkeycontainer"<?php echo $dkeyhide; ?>>
			<label class="dkeylabel" title="Enter a value here if you want your variation to be invisible until called by another variation."><?php _e('Display Key'); ?></label>
			<input type="text" name="_variation_dkey_<?php echo $i; ?>" id="_variation_dkey_<?php echo $i; ?>" value="<?php echo esc_attr($_variationDisplayKey); ?>" class="dkeynamefield" />

			<!-- Required -->
			<div class="variation_required_container" rel="<?php echo $i; ?>"<?php echo ($_variation_type == 'dropdown' || $_variation_type == 'text' || $_variation_type == 'textarea' || $_variation_type == 'upload' ? '' : ' style="display: none;"'); ?>>
				<input type="checkbox" name="_variation_required_<?php echo $i; ?>" id="_variation_required_<?php echo $i; ?>"<?php echo checked($_variationRequired,"on"); ?> />
				<label for="_variation_required_<?php echo $i; ?>"><?php _e('Make Field Required', 'foxyshop'); ?></label>
			</div>
		</div>

		<div class="variationsortnum"><?php echo $i; ?></div>
		<div style="clear: both;"></div>
	</div>
	<?php
}
echo "</div>";
?>
<button type="button" id="AddVariation" class="button"><?php _e('Add Another Variation', 'foxyshop'); ?></button>
<input type="hidden" name="max_variations" id="max_variations" value="<?php echo $max_variations; ?>" />




				</td>
			</tr>
		</tbody>
	</table>

	<div style="clear: both;"></div>
	<p><input type="submit" class="button-primary" value="<?php _e('Save These Variations', 'foxyshop'); ?>" /></p>
	<input type="hidden" name="foxyshop_save_tools" value="1" />
	<input type="hidden" name="foxyshop_process_saved_variations" value="1" />
	<?php wp_nonce_field('wp-foxyshop-process-saved-variations'); ?>
	</form>

	<br /><br />

	<form method="post" name="foxyshop_tools_form" action="">
	<table class="widefat" id="import_export_settings_container">
		<thead>
			<tr>
				<th><img src="<?php echo $export_icon; ?>" alt="" /><?php _e('Import/Export FoxyShop Settings', 'foxyshop'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (function_exists('mcrypt_encrypt')) : ?>
			<tr>
				<td>
					<label for="foxyshop_export_settings"><?php echo __('Copy String To Your Clipboard to Export FoxyShop Settings', 'foxyshop'); ?>:</label>
					<div style="clear: both;"></div>
					<textarea id="foxyshop_export_settings" name="foxyshop_export_settings" wrap="auto" readonly="readonly" onclick="this.select();" style="font-size: 13px; float: left; width:500px; line-height: 110%; resize: none; height: 80px; font-family: courier;"><?php echo $foxyshop_export_settings; ?></textarea>
				</td>
			</tr>
			<tr>
				<td>
					<label for="foxyshop_import_settings"><?php echo __('Paste Settings String to Import', 'foxyshop'); ?>:</label>
					<div style="clear: both;"></div>
					<textarea id="foxyshop_import_settings" name="foxyshop_import_settings" wrap="auto" style="float: left; width:500px;height: 80px; font-size: 13px; font-family: courier; line-height: 110%; resize: none;"></textarea>
					<div style="clear: both;"></div>
					<p><input type="submit" class="button-primary" value="<?php _e('Import Settings', 'foxyshop'); ?>" /></p>
				</td>
			</tr>
			<?php else : ?>
			<tr>
				<td>
					<p><em>In order to use this feature you need to enable the mcrypt library in your php.ini. More info <a href="http://stackoverflow.com/questions/2604435/what-causes-this-error-fatal-error-call-to-undefined-function-mcrypt-encrypt" target="_blank">here</a>.</em></p>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
	<input type="hidden" name="foxyshop_save_tools" value="1" />
	<?php wp_nonce_field('import-foxyshop-settings'); ?>
	</form>

	<br /><br />

	<form name="foxyshop_uninstall_form" action="" onsubmit="return false;">
	<table class="widefat" id="uninstall_plugin_container" style="margin-bottom: 14px;">
		<thead>
			<tr>
				<th><img src="<?php echo $remove_icon; ?>" alt="" /><?php _e('Uninstall FoxyShop', 'foxyshop'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<p>To uninstall FoxyShop, simply go to the Plugins page and deactivate the FoxyShop plugin. You can re-activate at any time but if you want to permanently delete FoxyShop and remove all settings, products, and product categories, you can delete the plugin via the WordPress delete link and all settings will be permanently removed. All products will be moved to the trash and subsequently deleted in 30 days.</p>
				</td>
			</tr>
		</tbody>
	</table>
	</form>





<script type="text/javascript">
function apiresetcheck() {
	if (confirm ("Are you sure you want to reset your API Key?\nYou will not be able to recover your old key.")) {
		return true;
	} else {
		return false;
	}
}

jQuery(document).ready(function($){
	$('.deleteVariation').live("click", function() {
		variationID = $(this).attr("rel");
		$("#variation" + variationID).slideUp(function() {
			$(this).remove();
			var counter = 1;
			$("div.product_variation").each(function() {
				$(this).find('.variationsort').val(counter);
				$(this).find('.variationsortnum').html(counter);
				counter++;
			});
		});
		return false;
	});


	function foxyshop_variation_order_load_event() {
		$("#variation_sortable").sortable({
			placeholder: "sortable-variation-placeholder",
			revert: false,
			items: "div.product_variation",
			tolerance: "pointer",
			distance: 30,
			update: function() {
				var counter = 1;
				$("div.product_variation").each(function() {
					$(this).find('.variationsort').val(counter);
					$(this).find('.variationsortnum').html(counter);
					counter++;
				});
			}
		});
	};
	addLoadEvent(foxyshop_variation_order_load_event);

	//Check For Illegal Titles
	$("input.variation_name").live("blur", function() {
		var thisval = $(this).val().toLowerCase();
		if (thisval == "code" || thisval == "codes" || thisval == "price" || thisval == "name" || thisval == "category" || thisval == "weight" || thisval == "shipto") {
			alert("Sorry! The title '" + thisval + "' cannot be used as a variation name.");
			return false;
		}
	});

	//Check For Illegal Titles
	$("input.variation_name").live("keypress", function(e) {
		if (e.which !== 0 && (e.charCode == 46 || e.charCode == 34)) {
			alert("Sorry! You can't use this character in a variation name: " + String.fromCharCode(e.keyCode|e.charCode));
			return false;
		}
	});

	//On Change Listener
	$(".variationtype").live("change", function() {
		new_type = $(this).val();
		this_id = $(this).parents(".product_variation").attr("rel");

		//Set Temp Values
		temp_dropdown = $("#_variation_value_"+this_id).val();
		temp_radio = $("#_variation_radio_"+this_id).val();
		temp_text1 = $("#_variation_textsize1_"+this_id).val();
		temp_text2 = $("#_variation_textsize2_"+this_id).val();
		temp_textarea = $("#_variation_textareasize_"+this_id).val();
		temp_descriptionfield = $("#_variation_description_"+this_id).val();
		temp_checkbox = $("#_variation_checkbox_"+this_id).val();
		temp_upload = $("#_variation_uploadinstructions_"+this_id).val();
		temp_hiddenfield = $("#_variation_hiddenfield_"+this_id).val();
		if (temp_dropdown) $("#dropdownradio_value_"+this_id).val(temp_dropdown);
		if (temp_radio) $("#dropdownradio_value_"+this_id).val(temp_radio);
		if (temp_text1) $("#text1_value_"+this_id).val(temp_text1);
		if (temp_text2) $("#text2_value_"+this_id).val(temp_text2);
		if (temp_textarea) $("#textarea_value_"+this_id).val(temp_textarea);
		if (temp_descriptionfield) $("#descriptionfield_value_"+this_id).val(temp_descriptionfield);
		if (temp_checkbox) $("#checkbox_value_"+this_id).val(temp_checkbox);
		if (temp_upload) $("#upload_value_"+this_id).val(temp_upload);
		if (temp_hiddenfield) $("#hiddenfield_value_"+this_id).val(temp_upload);

		//Set Contents in Container
		$("#variation_holder_"+this_id).html(getVariationContents(new_type, this_id));

		//Hide or Show Required Checkbox Option
		if (new_type == 'dropdown' || new_type == 'text' || new_type == 'textarea' || new_type == 'upload') {
			$(this).parents(".product_variation").find(".variation_required_container").show();
		} else {
			$(this).parents(".product_variation").find(".variation_required_container").hide();
			$(this).parents(".product_variation").find(".variation_required_container").find('input[type="checkbox"]').not(':checked');
		}


	});


	//New Variation
	$("#AddVariation").click(function() {
		var this_id = parseInt($("#max_variations").val()) + 1;


		new_content = '<div class="product_variation" rel="' + this_id + '" id="variation' + this_id + '">';
		new_content += '<input type="hidden" name="sort' + this_id + '" id="sort' + this_id + '" value="' + this_id + '" class="variationsort" />';
		new_content += '<input type="hidden" name="dropdownradio_value_' + this_id + '" id="dropdownradio_value_' + this_id + '" value="" />';
		new_content += '<input type="hidden" name="text1_value_' + this_id + '" id="text1_value_' + this_id + '" value="" />';
		new_content += '<input type="hidden" name="text2_value_' + this_id + '" id="text2_value_' + this_id + '" value="" />';
		new_content += '<input type="hidden" name="textarea_value_' + this_id + '" id="textarea_value_' + this_id + '" value="" />';
		new_content += '<input type="hidden" name="descriptionfield_value_' + this_id + '" id="descriptionfield_value_' + this_id + '" value="" />';
		new_content += '<input type="hidden" name="checkbox_value_' + this_id + '" id="checkbox_value_' + this_id + '" value="" />';
		new_content += '<input type="hidden" name="upload_value_' + this_id + '" id="upload_value_' + this_id + '" value="" />';
		new_content += '<input type="hidden" name="hiddenfield_value_' + this_id + '" id="hiddenfield_value_' + this_id + '" value="" />';
		new_content += '<!-- //// VARIATION HEADER //// -->';
		new_content += '<div class="foxyshop_field_control">';
		new_content += '<a href="#" class="button deleteVariation" rel="' + this_id + '">Delete</a>';
		new_content += '<label for="_variation_ref_name_' + this_id + '"><?php _e('Reference Name', 'foxyshop'); ?></label>';
		new_content += '<input type="text" name="_variation_ref_name_' + this_id + '" class="variation_ref_name" id="_variation_ref_name_' + this_id + '" value="" />';
		new_content += '<span>Displayed in Dropdown Menu</span>';
		new_content += '</div>';
		new_content += '<div class="foxyshop_field_control">';
		new_content += '<label for="_variation_name_' + this_id + '"><?php _e('Variation Name', 'foxyshop'); ?></label>';
		new_content += '<input type="text" name="_variation_name_' + this_id + '" class="variation_name" id="_variation_name_' + this_id + '" value="" />';
		new_content += '<label for="_variation_type_' + this_id + '" class="variationtypelabel"><?php _e('Variation Type', 'foxyshop'); ?></label> ';
		new_content += '<select name="_variation_type_' + this_id + '" id="_variation_type_' + this_id + '" class="variationtype">';
		<?php
		foreach ($var_type_array as $var_name => $var_val) {
			echo "\t\tnew_content += '<option value=\"" . $var_name . '">' . $var_val . "  </option>';\n";
		} ?>
		new_content += '</select>';
		new_content += '</div>';
		new_content += '<div class="variation_holder" id="variation_holder_' + this_id + '"></div>';
		new_content += '<!-- //// DISPLAY KEY //// -->';
		new_content += '<div class="foxyshop_field_control dkeycontainer">';
		new_content += '<label class="dkeylabel" title="Enter a value here if you want your variation to be invisible until called by another variation."><?php _e('Display Key', 'foxyshop'); ?></label>';
		new_content += '<input type="text" name="_variation_dkey_' + this_id + '" id="_variation_dkey_' + this_id + '" value="" class="dkeynamefield" />';
		new_content += '<!-- Required -->';
		new_content += '<div class="variation_required_container" rel="' + this_id + '">';
		new_content += '<input type="checkbox" name="_variation_required_' + this_id + '" id="_variation_required_' + this_id + '" />';
		new_content += '<label for="_variation_required_' + this_id + '"><?php _e('Make Field Required', 'foxyshop'); ?></label>';
		new_content += '</div>';
		new_content += '</div>';
		new_content += '<div class="variationsortnum">' + this_id + '</div>';
		new_content += '<div style="clear: both;"></div>';
		new_content += '</div>';

		$("#variation_sortable").append(new_content);
		$("#variation_holder_"+this_id).html(getVariationContents("dropdown", this_id));

		$("#max_variations").val(this_id);
		$("#variation_sortable").sortable("refresh");
		return false;
	});





	function getVariationContents(new_type, this_id) {
		new_contents = "";
		variationkeyhtml = '<div class="variationkey"><?php echo $variation_key; ?></div>';

		//Dropdown
		if (new_type == "dropdown") {
			new_contents = '<div class="foxyshop_field_control dropdown variationoptions">';
			new_contents += '<label id="_variation_value_' + this_id + '"><?php _e('Items in Dropdown', 'foxyshop'); ?></label>';
			new_contents += '<textarea name="_variation_value_' + this_id + '" id="_variation_value_' + this_id + '">' + $("#dropdownradio_value_"+this_id).val() + '</textarea>';
			new_contents += variationkeyhtml;
			new_contents += '</div>';

		//Radio Buttons
		} else if (new_type == "radio") {
			new_contents = '<div class="foxyshop_field_control radio variationoptions">';
			new_contents += '<label for="_variation_radio_' + this_id + '"><?php _e('Radio Button Options', 'foxyshop'); ?></label>';
			new_contents += '<textarea name="_variation_radio_' + this_id + '" id="_variation_radio_' + this_id + '">' + $("#dropdownradio_value_"+this_id).val() + '</textarea>';
			new_contents += variationkeyhtml;
			new_contents += '</div>';

		//Text
		} else if (new_type == "text") {
			new_contents = '<div class="foxyshop_field_control text variationoptions">';
			new_contents += '<div class="foxyshop_field_control">';
			new_contents += '<label for="_variation_textsize1_' + this_id + '"><?php _e('Text Box Size', 'foxyshop'); ?></label>';
			new_contents += '<input type="text" name="_variation_textsize1_' + this_id + '" id="_variation_textsize1_' + this_id + '" value="' + $("#text1_value_"+this_id).val() + '" /> <span><?php _e('characters'); ?></span>';
			new_contents += '</div>';
			new_contents += '<div class="foxyshop_field_control">';
			new_contents += '<label for="_variation_textsize2_' + this_id + '"><?php _e('Maximum Chars', 'foxyshop'); ?></label>';
			new_contents += '<input type="text" name="_variation_textsize2_' + this_id + '" id="_variation_textsize2_' + this_id + '" value="' + $("#text2_value_"+this_id).val() + '" /> <span><?php _e('characters'); ?></span>';
			new_contents += '</div>';
			new_contents += '<div style="clear: both;"></div>';
			new_contents += '</div>';

		//Textarea
		} else if (new_type == "textarea") {
			new_contents = '<div class="foxyshop_field_control textarea variationoptions">';
			new_contents += '<label for="_variation_textareasize_' + this_id + '"><?php _e('Lines of Text', 'foxyshop'); ?></label>';
			new_contents += '<input type="text" name="_variation_textareasize_' + this_id + '" id="_variation_textareasize_' + this_id + '" value="' + $("#textarea_value_"+this_id).val() + '" /> <span>(<?php _e('default is', 'foxyshop'); ?> 3)</span>';
			new_contents += '</div>';


		//Description
		} else if (new_type == "descriptionfield") {
			new_contents = '<div class="foxyshop_field_control descriptionfield variationoptions">';
			new_contents += '<label for="_variation_description_' + this_id + '"><?php _e('Descriptive Text', 'foxyshop'); ?></label>';
			new_contents += '<textarea name="_variation_description_' + this_id + '" id="_variation_description_' + this_id + '">' + $("#descriptionfield_value_"+this_id).val() + '</textarea>';
			new_contents += '</div>';

		//Checkbox
		} else if (new_type == "checkbox") {
			new_contents = '<div class="foxyshop_field_control checkbox variationoptions">';
			new_contents += '<label for="_variation_description_' + this_id + '"><?php _e('Value', 'foxyshop'); ?></label>';
			new_contents += '<input type="text" name="_variation_checkbox_' + this_id + '" id="_variation_checkbox_' + this_id + '" value="' + $("#checkbox_value_"+this_id).val() + '" class="variation_checkbox_text" />';
			new_contents += variationkeyhtml;
			new_contents += '</div>';

		//Hidden Field
		} else if (new_type == "hiddenfield") {
			new_contents = '<div class="foxyshop_field_control hiddenfield variationoptions">';
			new_contents += '<label for="_variation_hiddenfield_' + this_id + '">Value</label>';
			new_contents += '<input type="text" name="_variation_hiddenfield_' + this_id + '" id="_variation_hiddenfield_' + this_id + '" value="' + $("#hiddenfield_value_"+this_id).val() + '" />';
			new_contents += '</div>';

		//Custom File Upload
		} else if (new_type == "upload") {
			new_contents = '<div class="foxyshop_field_control upload variationoptions">';
			new_contents += '<label for="_variation_uploadinstructions_' + this_id + '"><?php _e('Instructions', 'foxyshop'); ?></label>';
			new_contents += '<textarea name="_variation_uploadinstructions_' + this_id + '" id="_variation_uploadinstructions_' + this_id + '">' + $("#upload_value_"+this_id).val() + '</textarea>';
			new_contents += '</div>';
		}

		return new_contents;
	}
});

</script>
<?php }
?>
