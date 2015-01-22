<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();


//Save Inventory Values - AJAX
add_action('wp_ajax_save_inventory_values', 'foxyshop_save_inventory_values_ajax');
function foxyshop_save_inventory_values_ajax() {
	if (!check_admin_referer('update-foxyshop-inventory')) return;
	foxyshop_inventory_count_update($_POST['code'], $_POST['new_count'], $_POST['product_id'], true);
	die;
}



add_action('admin_init', 'foxyshop_inventory_update');
function foxyshop_inventory_update() {

	//Saving Values From Uploaded Data
	if (isset($_POST['foxyshop_inventory_updates'])) {
		if (!check_admin_referer('import-foxyshop-inventory-updates')) return;

		$lines = preg_split("/(\r\n|\n|\r)/", $_POST['foxyshop_inventory_updates']);
		$save_count = 0;
		foreach ($lines as $line) {
			$line = explode("\t", $line);
			if (count($line) < 5) continue;
			if ($line[0] == "ID") continue;

			$productid = (int)$line[0];
			$productcode = $line[2];
			$newcount = (int)$line[4];

			//Update
			foxyshop_inventory_count_update($productcode, $newcount, $productid);
			$save_count++;
		}
		wp_redirect('edit.php?post_type=foxyshop_product&page=foxyshop_inventory_management_page&importcompleted='.$save_count);
		die;
	}
}


add_action('admin_menu', 'foxyshop_inventory_menu');
function foxyshop_inventory_menu() {
	add_submenu_page('edit.php?post_type=foxyshop_product', __('Inventory Management'), __('Inventory'), apply_filters('foxyshop_inventory_perm', 'manage_options'), 'foxyshop_inventory_management_page', 'foxyshop_inventory_management_page');
}

function foxyshop_inventory_management_page() {
	global $foxyshop_settings, $wp_version;
	?>
	<div class="wrap">
		<div class="icon32" id="icon-tools"><br></div>
		<h2>Manage Inventory Levels</h2>

		<?php
		//Confirmation Saved
		if (isset($_GET['saved'])) echo '<div class="updated"><p>' . __('Your New Inventory Levels Have Been Saved.') . '</p></div>';

		//Import Completed
		if (isset($_GET['importcompleted'])) echo '<div class="updated"><p>' . sprintf(__('Import completed: %s records updated.'), (int)$_GET['importcompleted']) . '</p></div>';
		?>

		<table cellpadding="0" cellspacing="0" border="0" class="wp-list-table widefat foxyshop-list-table" id="inventory_level" style="margin-top: 14px;">
			<thead>
				<tr>
					<th><span><?php _e('ID'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Name'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Code'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Variation'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Update'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Stock Lvl'); ?></span><span class="sorting-indicator"></span></th>
					<th><span><?php _e('Alert Lvl'); ?></span><span class="sorting-indicator"></span></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th><?php _e('ID'); ?></th>
					<th><?php _e('Name'); ?></th>
					<th><?php _e('Code'); ?></th>
					<th><?php _e('Variation'); ?></th>
					<th><?php _e('Update'); ?></th>
					<th><?php _e('Stock Lvl'); ?></th>
					<th><?php _e('Alert Lvl'); ?></th>
				</tr>
			</tfoot>
			<tbody>
			<?php
			$args = array('post_type' => 'foxyshop_product', 'post_status' => 'publish', 'numberposts' => "-1", "orderby" => "id", "order" => "ASC", "meta_key" => "_inventory_levels", "meta_compare" => "!=", "meta_value" => "");
			$product_list = get_posts($args);
			$exported = "ID\tName\tCode\tVariation\tInventory";
			$i = 0;
			$alternate = "";
			foreach ($product_list as $single_product) {
				$product = foxyshop_setup_product($single_product, true);
				$inventory_levels = get_post_meta($single_product->ID,'_inventory_levels',TRUE);
				if (!is_array($inventory_levels)) $inventory_levels = array();
				foreach ($inventory_levels as $ivcode => $iv) {

					$i++;
					if (!isset($iv['alert'])) $iv['alert'] = $foxyshop_settings['inventory_alert_level'];
					$inventory_alert = (int)($iv['alert'] == '' ? $foxyshop_settings['inventory_alert_level'] : $iv['alert']);
					$inventory_count = str_replace(",", "", $iv['count']);

					$variation = "&nbsp;";
					foreach ($product['variations'] as $product_variation) {
						$product_variation1 = preg_split("/(\r\n|\n)/", $product_variation['value']);
						foreach ($product_variation1 as $product_variation2) {
							if (strpos($product_variation2, "c:" . $ivcode) !== false) {
								$variation = str_replace("*", "", substr($product_variation2,0,strpos($product_variation2,"{")));
							}
						}
					}

					$exported .= "\n";
					$exported .= $product['id'] . "\t";
					$exported .= str_replace("\t", "", $product['name']) . "\t";
					$exported .= str_replace("\t", "", $ivcode) . "\t";
					$exported .= str_replace("\t", "", $variation) . "\t";
					$exported .= $inventory_count;

					$grade = "A";
					if ($inventory_count <= $inventory_alert) $grade = "X";
					if ($inventory_count <= 0) $grade = "U";
					echo '<tr>'."\n";
					echo '<td><strong>' . $product['id'] . '</strong></td>'."\n";
					echo '<td><strong><a href="post.php?post=' . $product['id'] . '&action=edit" tabindex="1">' . $product['name'] . '</a></strong></td>'."\n";
					echo '<td>' . $ivcode . '</td>'."\n";
					echo '<td>' . $variation . '</td>'."\n";

					//The Form
					echo '<td>';
					echo '<form>';
					echo '<input type="hidden" name="original_count_' . $i . '" id="original_count_' . $i . '" value="' . $inventory_count . '" />';
					echo '<input type="hidden" name="productid_' . $i . '" id="productid_' . $i . '" value="' . $single_product->ID . '" />';
					echo '<input type="hidden" name="code_' . $i . '" id="code_' . $i . '" value="' . $ivcode . '" />';
					echo '<input type="text" name="new_count_' . $i . '" id="new_count_' . $i . '" value="' . $inventory_count . '" data-id="' . $i . '" class="inventory_update_width" autocomplete="off" />';
					echo '<div class="foxyshop_wait" id="wait_' . $i . '"></div>';
					echo "</form>\n";
					echo "</td>\n";

					echo '<td id="current_inventory_' . $i . '" class="inventory' . $grade . '">' . $inventory_count . '</td>'."\n";
					echo '<td id="current_inventory_alert_' . $i . '">' . $inventory_alert . '</td>'."\n";
					echo '</tr>'."\n";
				}
			}
			?>
			</tbody>
		</table>

		<br /><br />

		<?php
		$export_icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAACH0lEQVR4nKWSTUhUYRSGnzv33snRdEY0M0dJaSDTzRBkiZhZkNCiTZC1CIQQahFEq9q3aBFRqzZFkUQRuayFEKlZhv0ZGsUw6VD4N5rj5KjT3O9+p4VphCVKZ3PO5nl4Obzwn2M0tLSdBE6vk7vedfvETQDjQOs9qT1Suy66t72XEn8y2Hb51KglIiwk59YlEJHl29paMMPEm4/4sn2rQspOkAi2Exg9TGizQ372/ByAVZSX5tihbYTD4X/C8fkxWp80URZSjA1/JnesGiZ2LSZYS+Svs0MEcjZSWDTNpiKDl11vSXmmioHkqoKekQ4exe4ykorxLT1Dmcpg2x727PXR3Rn90PigouSvAldcLjxrITbXjzdvGl+xy3Y/OMrGMAy8tkFDY8Ds6ZqN/iEQEdJqnrOdzUwuRCktzKK8oA7LtDFNzZDqxDAMDMNDqRlGpC/LAtBa47ouIsKtwatMJL+wu6Se0fQkjwfekevNI60WCFULhqEoZAcPu185rnZLPUsCpRRKKSKJQcr9lbye6qcpeJz7+19wo66D6vydJGc8+N0Kevri5KS21Dw9Ohy3AJRSOI6D1hqfmY3X3sD58DVMLLQStHZIOd+pyjrIwKcogalKiuM1w9DNCsG5qkuICNrVKFForRERzoQucid2hXBuPbOJIBmc3z2IRCJkMhlEZBH+BS1Vdmnvoxl+wHMdWX78omA8SWT8/Vo6tWJ+AquVAo19QSjUAAAAAElFTkSuQmCC";
		?>
		<form method="post" name="foxyshop_inventory_import_form" action="edit.php?post_type=foxyshop_product&amp;page=foxyshop_inventory_management_page">
		<table class="widefat">
			<thead>
				<tr>
					<th><img src="<?php echo $export_icon; ?>" alt="" /><?php _e('Import New Inventory Values'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<p>
							Copy and paste these values into Excel. Make your changes, then copy and paste back in and click update.<br />
							You can also add new inventory levels by using the template to add new rows with code and inventory fields.
						</p>
						<textarea id="name="foxyshop_inventory_updates" name="foxyshop_inventory_updates" wrap="auto" style="float: left; width:650px;height: 200px;"><?php echo $exported; ?></textarea>
						<div style="clear: both;"></div>
						<p><input type="submit" class="button-primary" value="<?php _e('Update Inventory Values'); ?>" /></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php wp_nonce_field('import-foxyshop-inventory-updates'); ?>
		</form>


	</div>

<script type="text/javascript" src="<?php echo FOXYSHOP_DIR; ?>/js/jquery.tablesorter.js"></script>
<script type="text/javascript">
jQuery(document).ready(function($){
	$(".inventory_update_width").blur(function() {
		current_field_id = $(this).attr("id");
		current_id = $("#" + current_field_id).attr("data-id");
		new_count = $("#" + current_field_id).val();

		$("#" + current_field_id).val(new_count);
		$("#" + current_field_id).parents("tr").removeClass("inventory_update_width_highlight");

		if (new_count != $("#original_count_" + current_id).val()) {

			var data = {
				action: 'save_inventory_values',
				"_wpnonce": "<?php echo wp_create_nonce('update-foxyshop-inventory'); ?>",
				"code": $("#code_" + current_id).val(),
				"product_id": $("#productid_" + current_id).val(),
				"new_count": new_count
			};

			$("#wait_" + current_id).addClass("waiting");
			$.post(ajaxurl, data, function() {
				$("#wait_" + current_id).removeClass("waiting");
				$("#original_count_" + current_id).val(new_count);
				$("#current_inventory_" + current_id).text(new_count);
				if (new_count <= 0) {
					$("#current_inventory_" + current_id).removeClass().addClass("inventoryU");
				} else if (new_count <= parseInt($("#current_inventory_alert_" + current_id).text())) {
					$("#current_inventory_" + current_id).removeClass().addClass("inventoryX");
				} else {
					$("#current_inventory_" + current_id).removeClass().addClass("inventoryA");
				}
			});
		}
  	});
	$(".inventory_update_width").keypress(function(e) {
		if (e.which == 13) {
			$(this).trigger("blur");
			return false;
		}
	});
	$(".inventory_update_width").focus(function() {
		$(this).parents("tr").addClass("inventory_update_width_highlight");
	});
	$("#inventory_level").tablesorter({
		'cssDesc': 'asc sorted',
		'cssAsc': 'desc sorted'
	});


});
function foxyshop_format_number_single(num) { num = num.toString().replace(/\$|\,/g,''); if(isNaN(num)) num = "0"; sign = (num == (num = Math.abs(num))); num = Math.floor(num*100+0.50000000001); cents = num%100; num = Math.floor(num/100).toString(); if(cents<10) cents = "0" + cents; for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++) num = num.substring(0,num.length-(4*i+3))+','+ num.substring(num.length-(4*i+3)); return (((sign)?'':'-') + num); }
</script>


<?php
}
?>
