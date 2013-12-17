<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//Put in Sidebar
add_action('admin_menu', 'foxyshop_category_sorting_menu');
function foxyshop_category_sorting_menu() {
	add_submenu_page('edit.php?post_type=foxyshop_product', __('Category Sorting', 'foxyshop'), __('Set Category Order', 'foxyshop'), apply_filters('foxyshop_category_sort_perm', 'edit_others_pages'), 'foxyshop_category_sort', 'foxyshop_category_sort');
}

//Update Order
function foxyshop_category_update_order() {
	global $foxyshop_category_sort;

	$categoryID = $_POST['categoryID'];
	$returned_ids = explode(",", $_POST['foxyshop_category_order_value']);

	//Clean Sort Fields
	foreach ($foxyshop_category_sort as $key=>$val) {
		if (!get_term((int)$key, "foxyshop_category_sort")) unset($foxyshop_category_sort[$key]);
	}

	//Add or Replace With New Fields
	$foxyshop_category_sort[$categoryID] = $returned_ids;
	update_option('foxyshop_category_sort', $foxyshop_category_sort);

	return '<div id="message" class="updated fade"><p>'. __('Category order updated successfully.', 'foxyshop').'</p></div>';
}

//Reset Order to Alpha
function foxyshop_category_revert_order() {
	global $foxyshop_category_sort;

	if (isset($_POST['categoryID'])) {
		$categoryID = $_POST['categoryID'];
		unset($foxyshop_category_sort[$categoryID]);
	}
	update_option('foxyshop_category_sort', $foxyshop_category_sort);
	return '<div id="message" class="updated fade"><p>'. __('Category order reset to alphabetical.', 'foxyshop').'</p></div>';
}

//The Main Function
function foxyshop_category_sort() {
	global $wpdb, $product, $foxyshop_category_sort;
	$parentID = 0;
	$success = "";

	if (isset($_POST['submit_new_category_order'])) {
		//if (check_admin_referer('update-foxyshop-sorting-options')) $success = foxyshop_category_update_order();
		$success = foxyshop_category_update_order();
	} elseif (isset($_POST['revert_category_order'])) {
		if (check_admin_referer('update-foxyshop-sorting-options')) $success = foxyshop_category_revert_order();
	}


	echo '<div class="wrap">';

	echo '<div class="icon32" id="icon-tools"><br></div>';
	echo '<h2>' . sprintf(__('Set %s Category Order', 'foxyshop'), FOXYSHOP_PRODUCT_NAME_SINGULAR) . '</h2>';
	if ($success) echo $success;

	$categoryID = (isset($_POST['categoryID']) ? $_POST['categoryID'] : 0);
	$product_categories = get_terms('foxyshop_categories', 'hide_empty=0&hierarchical=0&orderby=name&order=ASC&parent='.$categoryID);

	if ($product_categories) {
		$subcats = array();
		foreach($product_categories as $cat) {
			$subcat = get_term_children($cat->term_id, 'foxyshop_categories');
			if (count($subcat) > 0) $subcats[] = '<option value="' . $cat->term_id . '">' . $cat->name . '</option>'."\n";
		}

		if (count($subcats) > 0 || $categoryID > 0) {
			echo '<form name="form_product_category_order" method="post" action="">';
			echo '<select name="categoryID" id="categoryID">'."\n";
			if ($categoryID > 0) echo '<option value="0">Top Level</option>'."\n";
			echo implode("\n",$subcats);
			echo '</select>'."\n";
			echo '<input type="submit" name="btnSubPages" class="button" id="btnSubPages" value="' . __('Select Sub-Category', 'foxyshop') . '" /></form>';
		}
	}

	if ($categoryID > 0) {
		$term = get_term_by('id', $categoryID, "foxyshop_categories");
		$current_category_name = $term->name;
		$current_category_slug = $term->slug;
		$unwanted_children = get_term_children($categoryID, "foxyshop_categories");
		$unwanted_post_ids = get_objects_in_term($unwanted_children, "foxyshop_categories");
	} else {
		$current_category_name = __("Top Level Categories", 'foxyshop');
	}

	if ($product_categories) {

		//Sort Categories
		$product_categories = foxyshop_sort_categories($product_categories, $categoryID);

		echo '<h3>' . $current_category_name . '</h3>'."\n";
		echo '<p>' . __('Drag categories to the preferred order and then click the Save button at the bottom of the page.', 'foxyshop') . '</p>';
		echo '<p><strong>' . __('Current Sorting') . ': ' . (array_key_exists($categoryID,$foxyshop_category_sort) ? __('Custom', 'foxyshop') : __('Alphabetical', 'foxyshop')) . '</strong></p>';
		echo '<form name="form_category_order" method="post" action="">'."\n";
		echo '<ul id="foxyshop_category_order_list" class="foxyshop_sort_list">'."\n";

		$counter = 1;
		foreach($product_categories as $cat) {
			if (substr($cat->name,0,1) == "_") continue;
			echo '<li id="' . $cat->term_id . '" class="lineitem">';
			echo '<h4>' . $cat->name . '</h4>'."\n";
			echo '<div class="counter">' . $counter . '</div>';
			echo '<div style="clear: both; height: 1px;"></div>'."\n";
			echo '</li>'."\n";
			$counter++;
		}
		echo '</ul>'."\n";

		?>
		<div style="height: 100px;">
			<input type="submit" name="submit_new_category_order" id="revert_category_order" class="button-primary" value="<?php _e('Save Custom Order', 'foxyshop'); ?>" onclick="javascript:orderPages(); return true;" />&nbsp;&nbsp;<strong id="updateText"></strong>
			<input type="submit" name="revert_category_order" id="revert_category_order" class="button" style="float: right;" value="<?php _e('Reset to Alphabetical', 'foxyshop'); ?>" onclick="javascript:orderPages(); return true;" />
		</div>
		<input type="hidden" id="foxyshop_category_order_value" name="foxyshop_category_order_value" />
		<input type="hidden" id="categoryID" name="categoryID" value="<?php echo $categoryID; ?>" />
		<?php wp_nonce_field('update-foxyshop-sorting-options'); ?>
		</form>
		<?php

	} else {
		echo '<p><em>' . __('No Sub-Categories Found.', 'foxyshop') . '</em></p>';
	}
	?>
	</div>
<script type="text/javascript">
function foxyshop_custom_order_load_event(){
	jQuery("#foxyshop_category_order_list").sortable({
		placeholder: "sortable-placeholder-category",
		revert: false,
		tolerance: "pointer",
		update: function() {
			var counter = 1;
			jQuery("#foxyshop_category_order_list li").each(function() {
				jQuery(this).find('.counter').html(counter);
				counter++;
			});
		}
	});
};
addLoadEvent(foxyshop_custom_order_load_event);
function orderPages() {
	jQuery("#updateText").html("<?php _e('Updating Category Order...', 'foxyshop') ?>");
	jQuery("#foxyshop_category_order_value").val(jQuery("#foxyshop_category_order_list").sortable("toArray"));
}
</script>
<?php
}
