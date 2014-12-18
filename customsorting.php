<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//Only run this if sort key is set to custom
if ($foxyshop_settings['sort_key'] == "menu_order") add_action('admin_menu', 'foxyshop_custom_sorting_menu');
function foxyshop_custom_sorting_menu() {
	add_submenu_page('edit.php?post_type=foxyshop_product', sprintf(__('Custom %s Sorting', 'foxyshop'), FOXYSHOP_PRODUCT_NAME_SINGULAR), sprintf(__('Set %s Order', 'foxyshop'), FOXYSHOP_PRODUCT_NAME_SINGULAR), apply_filters('foxyshop_product_sort_perm', 'edit_others_pages'), 'foxyshop_custom_sort', 'foxyshop_custom_sort');
}

add_action("save_post", "foxyshop_init_menu_order");
function foxyshop_init_menu_order($post_id) {
	$cats = wp_get_post_terms($post_id, "foxyshop_categories", array("fields" => "ids"));
	foreach ($cats as $cat_id) {
		add_post_meta($post_id, "_foxyshop_menu_order_" . $cat_id, 0, true);
	}
	return;
}

function foxyshop_upgrade_menu_order() {
	$products = get_posts(array('post_type' => 'foxyshop_product', 'posts_per_page' => -1, 'post_status' => null));
	foreach ($products as $product) {
		$cats = wp_get_post_terms($product->ID, "foxyshop_categories", array("fields" => "ids"));
		foreach ($cats as $cat_id) {
			add_post_meta($product->ID, "_foxyshop_menu_order_" . $cat_id, $product->menu_order, 1);
		}
	}
}



//Update Order
function foxyshop_update_order() {
	if ($_POST['foxyshop_product_order_value'] != "") {
		global $wpdb;

		$foxyshop_product_order_value = $_POST['foxyshop_product_order_value'];
		$IDs = explode(",", $foxyshop_product_order_value);
		$result = count($IDs);
		for($i = 0; $i < $result; $i++) {
			$post_id = (int)str_replace(array("'", "id_"), "", $IDs[$i]);

			if ((int)$_POST['categoryID'] == 0) {
				$wpdb->query("UPDATE $wpdb->posts SET menu_order = '$i' WHERE id = $post_id");
			} else {
				update_post_meta($post_id, "_foxyshop_menu_order_" . (int)$_POST['categoryID'], $i);
			}
		}
		return '<div id="message" class="updated fade"><p>'. FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('order updated successfully', 'foxyshop').'.</p></div>';
	} else {
		return '<div id="message" class="updated fade"><p>'. __('An error occured, order has not been saved', 'foxyshop').'.</p></div>';
	}
}

//Revert Order to Original (set all to 0)
function foxyshop_revert_order() {
	global $wpdb;

	$foxyshop_product_order_value = $_POST['foxyshop_product_order_value'];
	$IDs = explode(",", $foxyshop_product_order_value);
	$result = count($IDs);
	for($i = 0; $i < $result; $i++) {
		$post_id = (int)str_replace(array("'", "id_"), "", $IDs[$i]);

		if ((int)$_POST['categoryID'] == 0) {
			$wpdb->query("UPDATE $wpdb->posts SET menu_order = '0' WHERE id = $post_id");
		} else {
			update_post_meta($post_id, "_foxyshop_menu_order_" . (int)$_POST['categoryID'], 0);
		}
	}
	return '<div id="message" class="updated fade"><p>'. FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('order reverted to original', 'foxyshop').'.</p></div>';
}

//The Main Function
function foxyshop_custom_sort() {
	global $wpdb, $product;
	$parentID = 0;
	$success = "";

	//Reset Order
	if (isset($_GET['upgrade_menu_order'])) {
		foxyshop_upgrade_menu_order();
	}

	if (isset($_POST['submit_new_product_order'])) {
		if (check_admin_referer('update-foxyshop-sorting-options')) $success = foxyshop_update_order();
	} elseif (isset($_POST['revert_product_order'])) {
		if (check_admin_referer('update-foxyshop-sorting-options')) $success = foxyshop_revert_order();
	}
	?>

	<div class="wrap">
	<div class="icon32" id="icon-tools"><br></div>
	<h2><?php echo sprintf(__('Custom %s Order'), FOXYSHOP_PRODUCT_NAME_SINGULAR); ?></h2>
	<?php if ($success) echo $success; ?>

	<?php
	$product_categories = get_terms('foxyshop_categories', 'hide_empty=0&hierarchical=0&orderby=name&order=ASC');
	$categoryID = 0;
	if (isset($_REQUEST['categoryID'])) {
		$categoryID = $_REQUEST['categoryID'];
	}
	if ($product_categories) {
		echo '<p>' . sprintf(__("Select a category from the drop down to order the %s in that category.", 'foxyshop'), strtolower(FOXYSHOP_PRODUCT_NAME_PLURAL)) . "</p>\n";
		echo '<form name="form_product_category_order" method="post" action="edit.php?post_type=foxyshop_product&page=foxyshop_custom_sort">';
		echo '<select name="categoryID" id="categoryID">'."\n";
		echo '<option value="0"' . ($categoryID == 0 ? ' selected="selected"' : '') . '>' . __('All', 'foxyshop') . ' ' . FOXYSHOP_PRODUCT_NAME_PLURAL . '</option>'."\n";
		foreach($product_categories as $cat) {
			echo '<option value="' . esc_attr($cat->term_id) . '"' . ($categoryID == $cat->term_id ? ' selected="selected"' : '') . '>' . esc_html($cat->name) . ' (' . $cat->count . ')' . '</option>'."\n";
		}
		echo '</select>'."\n";
		echo '<input type="submit" name="btnSubPages" class="button" id="btnSubPages" value="' . __('Select Category', 'foxyshop') . '" /></form>';
	} else {
		$categoryID = 0;
	}

	if ($categoryID >= 0) {

		if ($categoryID > 0) {
			$term = get_term_by('id', $categoryID, "foxyshop_categories");
			$current_category_name = $term->name;
			$args = array(
				'post_type' => 'foxyshop_product',
				'foxyshop_categories' => $term->slug,
				'posts_per_page' => -1,
				'orderby' => "meta_value_num",
				"meta_key" => "_foxyshop_menu_order_" . $categoryID,
				'order' => "ASC",
				'post__not_in' => foxyshop_hide_children_array($categoryID),
			);

		} else {
			$current_category_name = __("All", 'foxyshop') . ' ' . FOXYSHOP_PRODUCT_NAME_PLURAL;
			$args = array(
				'post_type' => 'foxyshop_product',
				'numberposts' => -1,
				'orderby' => "menu_order",
				'order' => "ASC",
			);
		}


		$product_list = get_posts($args);
		if ($product_list) {

			echo '<h3>' . $current_category_name . '</h3>'."\n";
			echo '<p>' . sprintf(__('Drag %s to the preferred order and then click the Save button at the bottom of the page.', 'foxyshop'), strtolower(FOXYSHOP_PRODUCT_NAME_PLURAL)) . '</p>';
			echo '<form name="form_product_order" method="post" action="">'."\n";
			echo '<ul id="foxyshop_product_order_list" class="foxyshop_sort_list">'."\n";

			foreach ($product_list as $prod) {
				$product = foxyshop_setup_product($prod);
				if ($categoryID == 0) {
					$current_count = $prod->menu_order;
				} else {
					$current_count = (int)get_post_meta($prod->ID, "_foxyshop_menu_order_" . $categoryID, true);
				}
				echo '<li id="id_' . $prod->ID . '" class="lineitem">';
				echo '<img src="' . foxyshop_get_main_image() . '" />';
				echo '<h4>' . $prod->post_title . '</h4>'."\n";
				echo foxyshop_price();
				echo '<div class="counter">' . ($current_count + 1) . '</div>';
				echo '<div style="clear: both; height: 1px;"></div>'."\n";
				echo '</li>'."\n";
			}
			echo '</ul>'."\n";
			?>
			<div style="width: 90%; height: 100px;">
				<input type="submit" name="submit_new_product_order" id="submit_new_product_order" class="button-primary" value="<?php _e('Save Custom Order', 'foxyshop'); ?>" onclick="javascript:orderPages(); return true;" />&nbsp;&nbsp;<strong id="updateText"></strong>
				<input type="submit" name="revert_product_order" id="revert_product_order" class="button" style="float: right;" value="<?php _e('Revert To Original', 'foxyshop'); ?>" onclick="javascript:orderPages(); return true;" />
			</div>
			<input type="hidden" id="foxyshop_product_order_value" name="foxyshop_product_order_value" />
			<input type="hidden" id="hdnParentID" name="hdnParentID" value="<?php echo $parentID; ?>" />
			<input type="hidden" id="categoryID" name="categoryID" value="<?php echo $categoryID; ?>" />
			<?php wp_nonce_field('update-foxyshop-sorting-options'); ?>
			</form>
			<?php

		} else {
			echo '<p><em>' . sprintf(__('No %s Found For This Category.', 'foxyshop'), FOXYSHOP_PRODUCT_NAME_PLURAL) . '</em></p>';
			echo '<p><a href="edit.php?post_type=foxyshop_product&amp;page=foxyshop_custom_sort&amp;upgrade_menu_order=1&amp;categoryID=' . $categoryID . '">Missing products? Click here.</a></p>';
		}

	}
	?>

</div>

<script type="text/javascript">
function foxyshop_custom_order_load_event(){
	jQuery("#foxyshop_product_order_list").sortable({
		placeholder: "sortable-placeholder",
		revert: false,
		tolerance: "pointer",
		update: function() {
			var counter = 1;
			jQuery("#foxyshop_product_order_list li").each(function() {
				jQuery(this).find('.counter').html(counter);
				counter++;
			});
		}
	});
};
addLoadEvent(foxyshop_custom_order_load_event);
function orderPages() {
	jQuery("#updateText").html("<?php echo sprintf(__('Updating %s Order...', 'foxyshop'), FOXYSHOP_PRODUCT_NAME_SINGULAR); ?>");
	jQuery("#foxyshop_product_order_value").val(jQuery("#foxyshop_product_order_list").sortable("toArray"));
}
</script>
<?php
}
