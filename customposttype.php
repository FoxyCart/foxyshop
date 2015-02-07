<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

//----------------------------------------------------------------------
//Custom Post Type Initialization (Initialisation for you British Types)
//----------------------------------------------------------------------
add_action('init', 'foxyshop_create_post_type', 1);
function foxyshop_create_post_type() {
	global $foxyshop_settings, $wp_version;

	//Custom Taxonomy: Product Categories
	$labels = array(
		'name' => FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Categories', 'foxyshop'),
		'singular_name' => FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Category', 'foxyshop'),
		'parent_item' => __('Parent Category'),
		'all_items' => __('All').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Categories', 'foxyshop'),
		'edit_item' => __('Edit').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Category', 'foxyshop'),
		'update_item' => __('Update').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Category', 'foxyshop'),
		'add_new_item' => __('Add New').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Category', 'foxyshop'),
		'new_item_name' => __('New').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Category Name', 'foxyshop'),
		'menu_name' => FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Categories', 'foxyshop')
	);
	$foxyshop_product_cat_tax_args = array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array('slug' => FOXYSHOP_PRODUCT_CATEGORY_SLUG, 'hierarchical' => true)
	);
	register_taxonomy('foxyshop_categories', 'foxyshop_product', apply_filters("foxyshop_product_cat_taxonomy_setup", $foxyshop_product_cat_tax_args));


	//Custom Taxonomy: Product Tags
	if ($foxyshop_settings['related_products_tags']) {
		$labels = array(
			'name' => FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Tags', 'foxyshop'),
			'singular_name' => FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Tag', 'foxyshop'),
			'parent_item' => __('Parent Category'),
			'all_items' => __('All').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Tags', 'foxyshop'),
			'edit_item' => __('Edit').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Tag', 'foxyshop'),
			'update_item' => __('Update').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Tag', 'foxyshop'),
			'add_new_item' => __('Add New').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Tag', 'foxyshop'),
			'new_item_name' => __('New').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Tag Name', 'foxyshop'),
			'menu_name' => FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Tags', 'foxyshop')
		);
		$foxyshop_product_tag_tax_args = array(
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array('slug' => FOXYSHOP_PRODUCTS_SLUG."-tag")
		);
		register_taxonomy('foxyshop_tags', 'foxyshop_product', apply_filters("foxyshop_product_tag_taxonomy_setup", $foxyshop_product_tag_tax_args));
	}

	//FoxyShop Product
	$labels = array(
		'name' => FOXYSHOP_PRODUCT_NAME_PLURAL,
		'singular_name' => FOXYSHOP_PRODUCT_NAME_SINGULAR,
		'add_new' => __('Add New', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR,
		'add_new_item' => __('Add New', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR,
		'edit_item' => __('Edit', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR,
		'new_item' => __('New', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR,
		'view_item' => __('View', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_SINGULAR,
		'all_items' => __('Manage', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_PLURAL, //Since WP 3.2
		'menu_name' => (version_compare($wp_version, '3.2', '>=') ? apply_filters('foxyshop_main_menu_name', "FoxyShop") : FOXYSHOP_PRODUCT_NAME_PLURAL),
		'not_found' =>  __('No', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_PLURAL.' '.__('Found'),
		'not_found_in_trash' => __('No', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_PLURAL.' '.__('Found in Trash'),
		'search_items' => __('Search', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_PLURAL,
		'parent_item_colon' => ''
	);
	$post_type_support = array('title','editor','thumbnail', 'custom-fields', 'excerpt');
	$post_type_taxonomies = array('foxyshop_categories');
	if (defined('FOXYSHOP_PRODUCT_COMMENTS')) array_push($post_type_support, "comments");
	if (defined('FOXYSHOP_PRODUCT_TAGS')) $post_type_taxonomies[] = "post_tag";
	if ($foxyshop_settings['related_products_tags']) $post_type_taxonomies[] = 'foxyshop_tags';
	$register_post_type_args = array(
		'labels' => $labels,
		'description' => "FoxyShop " . FOXYSHOP_PRODUCT_NAME_PLURAL,
		'public' => true,
		'show_ui' => true,
		'capability_type' => 'page',
		'hierarchical' => false,
		'supports' => $post_type_support,
		'menu_icon' => apply_filters('foxyshop_main_menu_icon', FOXYSHOP_DIR . '/images/icon.png'),
		'rewrite' => array("slug" => FOXYSHOP_PRODUCTS_SLUG),
		'taxonomies' => $post_type_taxonomies
	);
	register_post_type('foxyshop_product', apply_filters('foxyshop_register_post_type', $register_post_type_args));
}



//-------------------------------------------
//Setup Thumbnail Support
//-------------------------------------------
add_action('after_setup_theme','foxyshop_setup_post_thumbnails', 999);
function foxyshop_setup_post_thumbnails(){
	add_theme_support('post-thumbnails');
}





//-------------------------------------------
//Custom Columns
//-------------------------------------------
add_filter('manage_edit-foxyshop_product_columns', 'add_new_foxyshop_product_columns');
function add_new_foxyshop_product_columns($cols) {
	$new_columns['cb'] = '<input type="checkbox" />';
	$new_columns['id'] = __('ID', 'foxyshop');
	$new_columns['title'] = FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Title', 'foxyshop');
	$new_columns['productimage'] = __('Image', 'foxyshop');
	$new_columns['productcode'] = __('Code', 'foxyshop');
	$new_columns['price'] = __('Price', 'foxyshop');
	$new_columns['productcategory'] = FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Category', 'foxyshop');
	return $new_columns;
}



//-------------------------------------------
//Rewrite Columns
//-------------------------------------------
add_action('manage_posts_custom_column', 'foxyshop_manage_custom_columns', 10, 2);
function foxyshop_manage_custom_columns($column_name, $id) {
	global $wpdb, $foxyshop_settings;
	switch ($column_name) {
	case 'id':
		echo $id;
		break;
	case 'productcategory':
		$_taxonomy = 'foxyshop_categories';
		$terms = get_the_terms($id, $_taxonomy);
		if ( !empty( $terms ) ) {
			$out = array();
			foreach ( $terms as $c )
				$out[] = "<a href='edit-tags.php?action=edit&taxonomy=$_taxonomy&post_type=book&tag_ID={$c->term_id}'> " . esc_html(sanitize_term_field('name', $c->name, $c->term_id, 'category', 'display')) . "</a>";
			echo join( ', ', $out );
		}
		else {
			_e('Uncategorized', 'foxyshop');
		}
		break;
	case 'productimage':
		$featuredImageID = (has_post_thumbnail($id) ? get_post_thumbnail_id($id) : 0);
		$imageNumber = 0;
		$src = "";
		$attachments = get_posts(array('numberposts' => -1, 'post_type' => 'attachment','post_status' => null,'post_parent' => $id, 'order' => 'ASC','orderby' => 'menu_order'));
		if (!$attachments && $featuredImageID) {
			$attachments = get_posts(array("p" => $featuredImageID, 'post_type' => 'attachment', "post_mime_type" => "image"));
		}
		foreach ($attachments as $attachment) {
			$thumbnailSRC = wp_get_attachment_image_src($attachment->ID, "thumbnail");
			if ($featuredImageID == $attachment->ID || ($featuredImageID == 0 && $imageNumber == 0)) $src = $thumbnailSRC[0];
			$imageNumber++;
		}
		if (!$src && $featuredImageID) {
			$arr = wp_get_attachment_image_src($featuredImageID, "thumbnail");
			$src = $arr[0];
		}
		if (!$src) $src = $foxyshop_settings['default_image'];
		if (!$src) $src = WP_PLUGIN_URL."/foxyshop/images/no-photo.png";
		echo $src != 'none' ? '<a href="post.php?post=' . $id . '&amp;action=edit"><img src="' . $src . '" alt="" /></a>' : '&nbsp;';
		break;
	case 'productcode':
		$productcode = get_post_meta($id, "_code", true);
		echo ($productcode ? $productcode : '(' . $id . ')');
		break;
	case 'price':

		$salestartdate = get_post_meta($id,'_salestartdate',TRUE);
		$saleenddate = get_post_meta($id,'_saleenddate',TRUE);
		if ($salestartdate == '999999999999999999') $salestartdate = 0;
		if ($saleenddate == '999999999999999999') $saleenddate = 0;
		$originalprice = get_post_meta($id,'_price', true);
		$saleprice = get_post_meta($id,'_saleprice', true);

		if ($saleprice > 0) {
			$beginningOK = (strtotime("now") > $salestartdate);
			$endingOK = (strtotime("now") < ($saleenddate + 86400) || $saleenddate == 0);
			if ($beginningOK && $endingOK || ($salestartdate == 0 && $saleenddate == 0)) {
				echo '<span style="text-decoration: line-through; margin-right: 10px;">' . foxyshop_currency($originalprice) . '</span><span style="color: red;">' . foxyshop_currency($saleprice) . '</span>';
			} else {
				echo foxyshop_currency($originalprice);
			}
		} else {
			echo foxyshop_currency($originalprice);
		}
		break;
	default:
	}
}





//-------------------------------------------
//Add Filter Box to Top of Product List
//-------------------------------------------
add_action('restrict_manage_posts', 'foxyshop_restrict_manage_posts');
function foxyshop_restrict_manage_posts() {

    // only display these taxonomy filters on desired custom post_type listings
    global $typenow;
    if ($typenow == 'foxyshop_product') {

        // create an array of taxonomy slugs you want to filter by - if you want to retrieve all taxonomies, could use get_taxonomies() to build the list
        $filters = array('foxyshop_categories');

        foreach ($filters as $tax_slug) {
            // retrieve the taxonomy object
            $tax_obj = get_taxonomy($tax_slug);
            $tax_name = $tax_obj->labels->name;
            // retrieve array of term objects per taxonomy
            $terms = get_terms($tax_slug);

            // output html for taxonomy dropdown filter
            echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
            echo '<option value="">' . __('Show All', 'foxyshop') . ' ' . $tax_name . '</option>'."\n";
            foreach ($terms as $term) {
                // output each select option line, check against the last $_GET to show the current option selected
                echo '<option value='. $term->slug, $tax_slug == $term->slug ? ' selected="selected"' : '','>' . $term->name .' (' . $term->count .')</option>';
            }
            echo "</select>";
        }
    }
}




//-------------------------------------------
//Add Filter For Language
//-------------------------------------------
add_filter('post_updated_messages', 'foxyshop_updated_messages');
function foxyshop_updated_messages($messages) {
	global $post, $post_ID;

	$messages['foxyshop_product'] = array(
		1 => FOXYSHOP_PRODUCT_NAME_SINGULAR.' updated. <a href="'.esc_url(get_permalink($post_ID)).'">View '.strtolower(FOXYSHOP_PRODUCT_NAME_SINGULAR).'</a>',
		2 => __('Custom field updated.', 'foxyshop'),
		3 => __('Custom field deleted.', 'foxyshop'),
		4 => FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('updated', 'foxyshop').'.',
		6 => FOXYSHOP_PRODUCT_NAME_SINGULAR.' published. <a href="' . esc_url(get_permalink($post_ID)) . '">View '.strtolower(FOXYSHOP_PRODUCT_NAME_SINGULAR).'</a>',
		7 => FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__(' saved', 'foxyshop').'.',
		8 => FOXYSHOP_PRODUCT_NAME_SINGULAR.' submitted. <a target="_blank" href="'.esc_url(add_query_arg('preview', 'true', get_permalink($post_ID))).'">Preview '.strtolower(FOXYSHOP_PRODUCT_NAME_SINGULAR).'</a>',
		9 => FOXYSHOP_PRODUCT_NAME_SINGULAR.' scheduled for: <strong>'.date_i18n( __('M j, Y @ G:i'), strtotime($post->post_date)).'</strong>. <a target="_blank" href="'.esc_url(get_permalink($post_ID)).'">Preview '.strtolower(FOXYSHOP_PRODUCT_NAME_SINGULAR).'</a>',
		10 => FOXYSHOP_PRODUCT_NAME_SINGULAR.' draft updated. <a target="_blank" href="'.esc_url(add_query_arg( 'preview', 'true', get_permalink($post_ID))).'">Preview '.strtolower(FOXYSHOP_PRODUCT_NAME_SINGULAR).'</a>'
	);
	return $messages;
}




//-------------------------------------------
//Meta Box for Product Info
//-------------------------------------------
add_action('admin_init','foxyshop_product_meta_init');
function foxyshop_product_meta_init() {
	global $foxyshop_settings;
	add_meta_box('product_details_meta', FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Details', 'foxyshop'), 'foxyshop_product_details_setup', 'foxyshop_product', 'side', 'high');
	add_meta_box('product_pricing_meta', __('Pricing Details', 'foxyshop'), 'foxyshop_product_pricing_setup', 'foxyshop_product', 'side', 'low');
	add_meta_box('product_images_meta', FOXYSHOP_PRODUCT_NAME_SINGULAR.' ' . __('Images', 'foxyshop'), 'foxyshop_product_images_setup', 'foxyshop_product', 'normal', 'high');
	add_meta_box('product_variations_meta', FOXYSHOP_PRODUCT_NAME_SINGULAR.' '.__('Variations', 'foxyshop'), 'foxyshop_product_variations_setup', 'foxyshop_product', 'normal', 'high');
	if ($foxyshop_settings['google_product_support']) add_meta_box('google_products_data', __('Google Product Feed Data', 'foxyshop'), 'foxyshop_google_products_data', 'foxyshop_product', 'normal', 'low');
	if ($foxyshop_settings['related_products_custom']) add_meta_box('product_related_meta', __('Related', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_PLURAL, 'foxyshop_related_products_setup', 'foxyshop_product', 'normal', 'low');
	if ($foxyshop_settings['enable_bundled_products']) add_meta_box('product_bundled_meta', __('Bundled', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_PLURAL, 'foxyshop_bundled_products_setup', 'foxyshop_product', 'normal', 'low');
	if ($foxyshop_settings['enable_addon_products']) add_meta_box('product_addon_meta', __('Add-On', 'foxyshop').' '.FOXYSHOP_PRODUCT_NAME_PLURAL, 'foxyshop_addon_products_setup', 'foxyshop_product', 'normal', 'low');
	add_action('save_post','foxyshop_product_meta_save');
}




//-------------------------------------------
//Main Product Details
//-------------------------------------------
function foxyshop_product_details_setup() {
	global $post, $foxyshop_settings;
	$_price = number_format((double)get_post_meta($post->ID,'_price',TRUE),FOXYSHOP_DECIMAL_PLACES,".",",");
	$_code = get_post_meta($post->ID,'_code',TRUE);
	$_category = get_post_meta($post->ID,'_category',TRUE);
	$_quantity_min = get_post_meta($post->ID,'_quantity_min',TRUE);
	$_quantity_max = get_post_meta($post->ID,'_quantity_max',TRUE);
	$_quantity_hide = get_post_meta($post->ID,'_quantity_hide',TRUE);

	$defaultweight = explode(" ",$foxyshop_settings['default_weight']);
	$defaultweight1 = (int)$defaultweight[0];
	$defaultweight2 = (count($defaultweight) > 1 ? number_format($defaultweight[1],1) : "0.0");
	$original_weight = get_post_meta($post->ID,'_weight',1);
	if (!$original_weight && strpos($_SERVER['SCRIPT_FILENAME'], "post-new.php") === false) {
		$disable_weight_checked = ' checked="checked"';
		$_weight = array("", "");
	} else {
		$_weight = explode(" ", $original_weight);
		if (!$original_weight) $_weight = array($defaultweight1, $defaultweight2);
		$disable_weight_checked = "";
		if ((int)$_weight[0] == 0 && (double)$_weight[1] == 0) {
			if ($defaultweight1 == 0 && $defaultweight2 == 0.0) {
				$disable_weight_checked = ' checked="checked"';
				$_weight[0] = "";
				$_weight[1] = "";
			} else {
				$_weight[0] = $defaultweight1;
				$_weight[1] = $defaultweight2;
			}
		} else {
			$_weight[0] = (int)$_weight[0];
			if (!isset($_weight[1])) $_weight[1] = 0;
			$_weight[1] = number_format($_weight[1],1);
		}
	}

	if ($foxyshop_settings['downloadables_sync'] && version_compare($foxyshop_settings['version'], '0.7.2', ">=") && $foxyshop_settings['domain']) {
		$show_downloadables = 1;
	} else {
		$show_downloadables = 0;
	}


	$_hide_product = get_post_meta($post->ID,'_hide_product',TRUE);
	?>
	<?php if ($show_downloadables) { ?>
	<a href="#" id="show_downloadable_list" title="<?php _e('Show Available Downloadables', 'foxyshop'); ?>">"<?php _e('Show Available Downloadables', 'foxyshop'); ?></a>
	<div class="foxyshop_field_control" id="downloadable_list_parent">
		<a href="#" id="hide_downloadable_list" title="<?php _e('Hide Available Downloadables', 'foxyshop'); ?>"><?php _e('Hide Available Downloadables', 'foxyshop'); ?></a>
		<label for="downloadable_list" style="width:100%;"><?php _e('Select Downloadable To Prefill Data', 'foxyshop'); ?></label>
		<select name="downloadable_list" id="downloadable_list">
			<?php
			foxyshop_get_downloadable_list();
			$downloadable_list = get_option("foxyshop_downloadables");
			if (!is_array($downloadable_list)) {
				$downloadable_list = array();
				echo '<option value="">' . __('None Found') . '</option>'."\n";
			} else {
				echo '<option value="">- - ' . __('Select Below', 'foxyshop') . ' - -</option>'."\n";

			}
			foreach ($downloadable_list as $downloadable) {
				echo '<option value="' . esc_attr($downloadable['product_code']) . '"';
				echo ' category_code="' . esc_attr($downloadable['category_code']) . '"';
				echo ' product_price="' . esc_attr($downloadable['product_price']) . '"';
				echo '>' . esc_attr($downloadable['product_name']) . '</option>';
				echo "\n";
			}
			?>
		</select>
		<a href="#" id="ajax_get_downloadable_list" title="<?php _e('Refresh List', 'foxyshop'); ?>"><?php _e('Refresh List', 'foxyshop'); ?></a>
		<div style="clear:both"></div>
	</div>
	<?php } ?>
	<div id="foxyshop_price" class="foxyshop_field_control">
		<label for="_price"><?php _e('Base Price', 'foxyshop'); ?></label>
		<input type="text" name="_price" id="_price" value="<?php echo $_price; ?>" onblur="foxyshop_check_number(this);" style="width: 90px; float: left;" />
		<span style="float: left; margin: 9px 0 0 5px;">0.00</span>
	</div>
	<div id="foxyshop_item_code" class="foxyshop_field_control">
		<label for="_code"><?php _e('Item Code', 'foxyshop'); ?></label>
		<input type="text" name="_code" id="_code" value="<?php echo $_code; ?>" />
	</div>
	<div id="foxyshop_weight" class="foxyshop_field_control">
		<label for="_weight1"><?php _e('Weight', 'foxyshop'); ?></label>
		<input type="text" name="_weight1" id="_weight1" value="<?php echo $_weight[0]; ?>"<?php if ($disable_weight_checked) echo ' disabled="disabled"'; ?> />
		<span style="float: left; margin: 9px 0 0 5px; width: 21px;"><?php echo ($foxyshop_settings['weight_type'] == "metric" ? 'kg' : 'lbs'); ?></span>
		<input type="text" name="_weight2" id="_weight2" value="<?php echo $_weight[1]; ?>"<?php if ($disable_weight_checked) echo ' disabled="disabled"'; ?> />
		<span style="float: left; margin: 9px 0 0 5px; width: 23px;"><?php echo ($foxyshop_settings['weight_type'] == "metric" ? 'gm' : 'oz'); ?></span>
		<input type="checkbox" name="weight_disable" id="weight_disable" title="<?php _e('Disable Weight', 'foxyshop'); ?>" style="float: left; margin-top: 7px;"<?php echo $disable_weight_checked; ?> />
		<label id="weight_disable_label" for="weight_disable" style="float: left; margin: 6px 0 0 2px; width: 16px;" title="<?php _e('Disable Weight', 'foxyshop'); ?>" class="iconsprite <?php echo $disable_weight_checked ? "hide_color" : "hide_gray"; ?>"></label>
	</div>
	<div id="foxyshop_quantity" class="foxyshop_field_control">
		<label for="_quantity_min"><?php _e('Qty Settings', 'foxyshop'); ?></label>
		<input type="text" name="_quantity_min" id="_quantity_min" value="<?php echo $_quantity_min; ?>" title="<?php _e('Minimum Quantity', 'foxyshop'); ?>" style="width: 33px; float: left;" onblur="foxyshop_check_number_single(this);"<?php if ($_quantity_hide) echo ' disabled="disabled"'; ?> />
		<span id="quantity_min_label" style="float: left; margin: 6px 0 0 1px; width: 26px;" class="iconsprite <?php echo $_quantity_min ? "down_color" : "down_gray"; ?>"></span>
		<input type="text" name="_quantity_max" id="_quantity_max" value="<?php echo $_quantity_max; ?>" title="<?php _e('Maximum Quantity', 'foxyshop'); ?>" style="width: 33px; float: left;" onblur="foxyshop_check_number_single(this);"<?php if ($_quantity_hide) echo ' disabled="disabled"'; ?> />
		<span id="quantity_max_label" style="float: left; margin: 6px 0 0 1px; width: 26px;" class="iconsprite <?php echo $_quantity_max ? "up_color" : "up_gray"; ?>"></span>
		<input type="checkbox" name="_quantity_hide" id="_quantity_hide" title="<?php _e('Hide Quantity Box', 'foxyshop'); ?>" style="float: left; margin-top: 7px;"<?php echo checked($_quantity_hide,"on"); ?> />
		<label id="quantity_hide_label" for="_quantity_hide" style="float: left; margin: 6px 0 0 2px; width: 16px;" title="<?php _e('Hide Quantity Box', 'foxyshop'); ?>" class="iconsprite <?php echo $_quantity_hide ? "hide_color" : "hide_gray"; ?>"></label>
		<div style="clear:both"></div>
	</div>

	<?php if (version_compare($foxyshop_settings['version'], '0.7.2', ">=") && $foxyshop_settings['domain']) { ?>
	<div id="foxyshop_category" class="foxyshop_field_control">
		<label for="_category" style="width: 100%;">
			<?php _e('FoxyCart Category'); ?>
			<small>(<a href="#" id="ajax_get_category_list_select" title="<?php _e('Refresh List', 'foxyshop'); ?>"><?php _e('Refresh List', 'foxyshop'); ?></a>)</small>
		</label>
		<select name="_category" id="_category">
			<?php
			if (strpos($foxyshop_settings['ship_categories'], "DEFAULT") === false) $foxyshop_settings['ship_categories'] = "DEFAULT|" . __('Default for all products', 'foxyshop') . "\n" . $foxyshop_settings['ship_categories'];
			$arrShipCategories = preg_split("/(\r\n|\n|\r)/", $foxyshop_settings['ship_categories']);
			for ($i = 0; $i < count($arrShipCategories); $i++) {
				if ($arrShipCategories[$i] == "") continue;
				$shipping_category = explode("|", $arrShipCategories[$i]);
				$shipping_category_code = trim($shipping_category[0]);
				if ($shipping_category_code == "DEFAULT") $shipping_category_code = "";
				$shipping_category_name = $shipping_category_code;
				$shipping_category_type = '';
				if (isset($shipping_category[1])) $shipping_category_name = trim($shipping_category[1]);
				if (isset($shipping_category[2])) $shipping_category_type = trim($shipping_category[2]);
				echo '<option value="' . esc_attr($shipping_category_code) . '"';
				if ($shipping_category_type) echo ' rel="' . esc_attr($shipping_category_type) . '"';
				if (esc_attr($shipping_category_code == $_category)) echo ' selected="selected"';
				echo '>' . esc_attr($shipping_category_name) . '</option>';
				echo "\n";
			}
			?>
		</select>
	</div>
	<?php } else { ?>
	<div id="foxyshop_category" class="foxyshop_field_control">
		<label for="_category" style="width:76px; margin-right: 4px;"><?php _e('FoxyCart Cat'); ?></label>
		<select name="_category" id="_category">
			<?php
			if (strpos($foxyshop_settings['ship_categories'], "DEFAULT") === false) $foxyshop_settings['ship_categories'] = "DEFAULT|" . __('Default for all products', 'foxyshop') . "\n" . $foxyshop_settings['ship_categories'];
			$arrShipCategories = preg_split("/(\r\n|\n|\r)/", $foxyshop_settings['ship_categories']);
			for ($i = 0; $i < count($arrShipCategories); $i++) {
				if ($arrShipCategories[$i] == "") continue;
				$shipping_category = explode("|", $arrShipCategories[$i]);
				$shipping_category_code = trim($shipping_category[0]);
				if ($shipping_category_code == "DEFAULT") $shipping_category_code = "";
				$shipping_category_name = $shipping_category_code;
				$shipping_category_type = '';
				if (isset($shipping_category[1])) $shipping_category_name = trim($shipping_category[1]);
				if (isset($shipping_category[2])) $shipping_category_type = trim($shipping_category[2]);
				echo '<option value="' . esc_attr($shipping_category_code) . '"';
				if ($shipping_category_type) echo ' rel="' . esc_attr($shipping_category_type) . '"';
				if (esc_attr($shipping_category_code == $_category)) echo ' selected="selected"';
				echo '>' . esc_attr($shipping_category_name) . '</option>';
				echo "\n";
			}
			?>
		</select>
	</div>
	<?php } ?>

	<?php
	if ($foxyshop_settings['show_add_to_cart_link'] && isset($_REQUEST['post'])) {
		global $product;
		$product = foxyshop_setup_product();
	?>
	<div id="foxyshop_add_to_cart_link" class="foxyshop_field_control">
		<label for="add_to_cart_link"><?php _e('Add to Cart', 'foxyshop'); ?></label>
		<input type="text" name="add_to_cart_link" id="add_to_cart_link" value="<?php echo foxyshop_product_link("", 1); ?>" onclick="this.select();" readonly="readonly" />
	</div>
	<?php } ?>

	<?php if ($foxyshop_settings['enable_sso'] && $foxyshop_settings['sso_account_required'] == 2) { ?>
	<div id="foxyshop_require_sso" class="foxyshop_field_control">
		<input type="checkbox" name="_require_sso" id="_require_sso" style="float: left; margin: 5px 0 0 10px;"<?php echo checked(get_post_meta($post->ID,'_require_sso',TRUE),"on"); ?> />
		<label style="width: 210px;" for="_require_sso"><?php _e('Require Account For Checkout', 'foxyshop'); ?></label>
	</div>
	<?php } ?>
	<div id="foxyshop_hide_product" class="foxyshop_field_control">
		<input type="checkbox" name="_hide_product" id="_hide_product"<?php echo checked($_hide_product,"on"); ?> />
		<label style="width: 210px;" for="_hide_product"><?php echo sprintf(__('Hide This %s From List View', 'foxyshop'), FOXYSHOP_PRODUCT_NAME_SINGULAR); ?></label>
	</div>
	<div style="clear:both"></div>

	<script type="text/javascript">

	//Setup Vars For Use Later
	var FOXYSHOP_PRODUCT_NAME_SINGULAR = '<?php echo strtolower(str_replace("'", "\'", FOXYSHOP_PRODUCT_NAME_SINGULAR)); ?>';
	var show_downloadables = <?php echo ($show_downloadables ? 1 : 0); ?>;
	var nonce_downloadable_list = '<?php echo wp_create_nonce("foxyshop-ajax-get-downloadable-list"); ?>';
	var defaultweight1 = '<?php echo $defaultweight1; ?>';
	var defaultweight2 = '<?php echo $defaultweight2; ?>';
	var weight_dividend = <?php echo ($foxyshop_settings['weight_type'] == 'metric' ? 1000 : 16); ?>;
	var use_chozen = <?php echo ($foxyshop_settings['related_products_custom'] || $foxyshop_settings['related_products_tags'] || $foxyshop_settings['enable_addon_products'] ? 1 : 0); ?>;

	var renameLive = false;
	var post_id = <?php echo $post->ID; ?>;
	var nonce_images = '<?php echo wp_create_nonce("foxyshop-product-image-functions-".$post->ID); ?>';

	<?php
	//Get Max Upload Limit
	$max_upload = (int)(ini_get('upload_max_filesize'));
	$max_post = (int)(ini_get('post_max_size'));
	$memory_limit = (int)(ini_get('memory_limit'));
	$upload_mb = min($max_upload, $max_post, $memory_limit);
	$foxyshop_max_upload = $upload_mb * 1048576;
	if ($foxyshop_max_upload == 0) $foxyshop_max_upload = "8000000"; ?>
	var foxyshop_max_upload = '<?php echo $foxyshop_max_upload;
	?>';

	var FOXYSHOP_DIR = '<?php echo FOXYSHOP_DIR; ?>';
	var FOXYSHOP_URL_BASE = '<?php echo FOXYSHOP_URL_BASE; ?>';
	var bloginfo_url = '<?php bloginfo("url"); ?>';
	var datafeed_url_key = '<?php echo $foxyshop_settings['datafeed_url_key']; ?>';

	</script>

	<?php
	//Add Action For Product Details (For Other Integrations)
	do_action("foxyshop_admin_product_details", $post->ID);

	//Setup Hidden Admin Fields
	echo '<input type="hidden" name="products_meta_noncename" value="' . wp_create_nonce(__FILE__) . '" />';
	echo '<input type="hidden" name="menu_order" value="' . ($post->menu_order == 0 && $post->post_status == "auto-draft" ? $post->ID : $post->menu_order) . '" />';
}




//-------------------------------------------
//Product Pricing Details
//-------------------------------------------
function foxyshop_product_pricing_setup() {
	global $post, $foxyshop_settings;
	$_saleprice = number_format((double)get_post_meta($post->ID, '_saleprice', 1),FOXYSHOP_DECIMAL_PLACES,".",",");
	$_salestartdate = get_post_meta($post->ID, '_salestartdate', 1);
	$_saleenddate = get_post_meta($post->ID, '_saleenddate', 1);

	$_coupon = get_post_meta($post->ID, '_coupon', 1);
	$_expires = get_post_meta($post->ID, '_expires', 1);
	$_cart = get_post_meta($post->ID, '_cart', 1) == 'checkout' ? ' checked="checked"' : '';
	$_empty = get_post_meta($post->ID, '_empty', 1) == 'true' ? ' checked="checked"' : '';

	//Format Sale Date
	if ($_salestartdate == '999999999999999999') $_salestartdate = "";
	if ($_salestartdate) $_salestartdate = date('n/j/Y', $_salestartdate);
	if ($_saleenddate == '999999999999999999') $_saleenddate = "";
	if ($_saleenddate) $_saleenddate = date('n/j/Y', $_saleenddate);

	$_sub_frequency = get_post_meta($post->ID,'_sub_frequency',TRUE);
	$_sub_startdate = get_post_meta($post->ID,'_sub_startdate',TRUE);
	$_sub_enddate = get_post_meta($post->ID,'_sub_enddate',TRUE);

	$discount_methods = array(
		"none" => "No Discounts",
		"discount_quantity_amount" => __("Amount Based on Quantity", 'foxyshop'),
		"discount_quantity_percentage" => __("Percentage Based on Quantity", 'foxyshop'),
		"discount_price_amount" => __("Amount Based on Price", 'foxyshop'),
		"discount_price_percentage" => __("Percentage Based on Price", 'foxyshop')
	);
	$discount_types = array(
		"allunits" => __("All Units", 'foxyshop'),
		"incremental" => __("Incremental", 'foxyshop'),
		"repeat" => _x("Repeat", "Recurring", 'foxyshop'),
		"single" => _x("Single", "One-time", 'foxyshop')
	);
	$current_discount_method = "none";
	$computed_discount = "";
	foreach ($discount_methods as $key => $val) {
		if ($key == "none") continue;
		if (get_post_meta($post->ID, '_' . $key, 1) != "") {
			$current_discount_method = $key;
			$computed_discount = get_post_meta($post->ID,'_' . $key, 1);
		}
	}

	?>
	<h4><?php echo _x('Sale', 'Noun, Special Offer', 'foxyshop'); ?></h4>
	<div class="foxyshop_field_control">
		<label for="_saleprice"><?php _e('Sale Price', 'foxyshop'); ?></label>
		<input type="text" name="_saleprice" id="_saleprice" value="<?php echo $_saleprice; ?>" style="width: 87px; float: left;" />
		<span style="float: left; margin: 9px 0 0 5px;">0.00</span>
	</div>
	<div class="foxyshop_field_control">
		<label for="_salestartdate"><?php _e('Start Date', 'foxyshop'); ?></label>
		<input type="text" id="_salestartdate" name="_salestartdate" value="<?php echo $_salestartdate; ?>" style="width: 87px; float: left;" />
		<span style="float: left; margin: 9px 0 0 5px;">mm/dd/yyy</span>
	</div>
	<div class="foxyshop_field_control">
		<label for="_saleenddate"><?php _e('End Date', 'foxyshop'); ?></label>
		<input type="text" id="_saleenddate" name="_saleenddate" value="<?php echo $_saleenddate; ?>" style="width: 87px; float: left;" />
		<span style="float: left; margin: 9px 0 0 5px;">mm/dd/yyy</span>
	</div>
	<div style="clear: both;"></div>


	<h4><?php echo _x('Discounts', 'Discounted Prices Applied to Products', 'foxyshop'); ?> <a href="http://wiki.foxycart.com/v/<?php echo $foxyshop_settings['version']; ?>/coupons_and_discounts" target="_blank">(<?php _e('reference', 'foxyshop'); ?>)</a></h4>
	<div class="foxyshop_field_control">
		<select name="discount_method" id="discount_method">
		<?php
		foreach ($discount_methods as $key => $val) {
			echo '<option value="' . $key . '"';
			if ($current_discount_method == $key) echo ' selected="selected"';
			echo ">$val</option>\n";
		}
		?>
		</select>
	</div>

	<div id="discount_container">

		<div class="foxyshop_field_control">
			<label for="discount_type"><?php echo _x('Disc. Type', 'Type of Discount', 'foxyshop'); ?></label>
			<select name="discount_type" id="discount_type">
			<?php
			foreach ($discount_types as $key => $val) {
				echo '<option value="' . $key . '"';
				if ($current_discount_type == $key) echo ' selected="selected"';
				echo ">$val</option>\n";
			}
			?>
			</select>
		</div>

		<div class="foxyshop_field_control">
			<label for="discount_name"><?php echo _x('Disc. Name', 'Name of Discount', 'foxyshop'); ?></label>
			<input type="text" id="discount_name" name="discount_name" value="" />
		</div>

		<ul id="discount_levels"></ul>

		<label for="computed_discount"><?php _e('This is Your Computed Discount', 'foxyshop'); ?>:</label>
		<input type="text" name="computed_discount" id="computed_discount" value="<?php echo $computed_discount; ?>" />
		<div style="clear:both;"></div>

	</div>

	<?php if ($foxyshop_settings['manage_inventory_levels']) { ?>
	<h4><?php _e('Set Inventory Levels', 'foxyshop'); ?></a></h4>
	<div style="float: left; width: 152px; margin-bottom: 5px; font-size: 11px;"><? echo FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('Code', 'foxyshop');?></div>
	<div style="float: left; width: 51px; margin-bottom: 5px; font-size: 11px;"><?php _e('Count', 'foxyshop'); ?></div>
	<div style="float: left; width: 50px; margin-bottom: 5px; font-size: 11px;" title="<?php echo sprintf(__('If not set, default value will be used (%s)', 'foxyshop'), $foxyshop_settings['inventory_alert_level']); ?>"><?php _e('Alert Lvl', 'foxyshop'); ?></div>
	<ul id="inventory_levels">
		<?php
		$inventory_levels = get_post_meta($post->ID,'_inventory_levels',TRUE);
		if (!is_array($inventory_levels)) $inventory_levels = array();
		$i = 1;
		foreach ($inventory_levels as $ivcode => $iv) {
			if ($ivcode) {
				echo '<li>';
				echo '<input type="text" id="inventory_code_' . $i . '" name="inventory_code_' . $i . '" value="' . $ivcode . '" class="inventory_code" rel="' . $i . '" style="width: 142px;" />';
				echo '<input type="text" id="inventory_count_' . $i . '" name="inventory_count_' . $i . '" value="' . $iv['count'] . '" class="inventory_count" rel="' . $i . '" />';
				echo '<input type="text" id="inventory_alert_' . $i . '" name="inventory_alert_' . $i . '" value="' . $iv['alert'] . '" class="inventory_count" rel="' . $i . '" />';
				echo "</li>\n";
				$i++;
			}
		}
		?>
		<li><input type="text" id="inventory_code_<?php echo $i; ?>" name="inventory_code_<?php echo $i; ?>" value="" class="inventory_code" rel="<?php echo $i; ?>" style="width: 142px;" /><input type="text" id="inventory_count_<?php echo $i; ?>" name="inventory_count_<?php echo $i; ?>" value="" class="inventory_count" rel="<?php echo $i; ?>" /><input type="text" id="inventory_alert_<?php echo $i; ?>" name="inventory_alert_<?php echo $i; ?>" value="" class="inventory_count" rel="<?php echo $i; ?>" /></li>
	</ul>
	<input type="hidden" name="max_inventory_count" id="max_inventory_count" value="<?php echo $i; ?>" />
	<div style="clear:both;"></div>
	<?php } ?>


	<?php if ($foxyshop_settings['enable_subscriptions']) { ?>
	<h4 style="margin-bottom: 3px;"><?php _e('Subscription Attributes', 'foxyshop'); ?> <a href="http://wiki.foxycart.com/v/<?php echo $foxyshop_settings['version']; ?>/cheat_sheet#subscription_product_options" target="_blank">(<?php _e('reference'); ?>)</a></h4>
	<span style="color: #999999; display: block; line-height: 15px; margin-bottom: 5px;"><?php _e('You may also enter a', 'foxyshop'); ?> <a href="http://php.net/manual/en/function.strtotime.php" target="_blank" title="PHP Docs" style="color: #999">strtotime</a> <?php _e('argument for start or end (like +3 months)', 'foxyshop'); ?></span>
	<div id="foxyshop_subscription_attributes">
		<div class="foxyshop_field_control">
			<label for="_sub_frequency"><?php echo _x('Frequency', 'Frequency of Subscription Charge', 'foxyshop'); ?></label>
			<input type="text" name="_sub_frequency" id="_sub_frequency" value="<?php echo $_sub_frequency; ?>" />
			<span>60d, 2w, 1m, 1y, .5m</span>
		</div>
		<div class="foxyshop_field_control">
			<label for="_sub_startdate"><?php _e('Start Date', 'foxyshop'); ?></label>
			<input type="text" id="_sub_startdate" name="_sub_startdate" value="<?php echo $_sub_startdate; ?>" />
			<span>YYYYMMDD or D</span>
		</div>
		<div class="foxyshop_field_control">
			<label for="_sub_enddate"><?php _e('End Date', 'foxyshop'); ?></label>
			<input type="text" id="_sub_enddate" name="_sub_enddate" value="<?php echo $_sub_enddate; ?>" />
			<span>YYYYMMDD or D</span>
		</div>
		<div style="clear: both;"></div>
	</div>
	<?php
	}
	?>

	<h4 style="margin-bottom: 3px;"><?php _e('Other Product Features', 'foxyshop'); ?></h4>
	<div class="foxyshop_field_control">
		<input type="checkbox" style="float: left; margin: 5px 0 0 10px;" id="_cart" name="_cart" value="checkout"<?php echo $_cart; ?>>
		<label for="_cart" style="width: 210px;"><?php _e('Force Immediate Checkout', 'foxyshop'); ?></label>
	</div>
	<div class="foxyshop_field_control">
		<input type="checkbox" style="float: left; margin: 5px 0 0 10px;" id="_empty" name="_empty" value="true"<?php echo $_empty; ?>>
		<label for="_empty" style="width: 210px;"><?php _e('Empty Cart Before Adding Product', 'foxyshop'); ?></label>
	</div>
	<div class="foxyshop_field_control">
		<input type="checkbox" style="float: left; margin: 5px 0 0 10px;" id="do_coupon" name="do_coupon"<?php if ($_coupon) echo ' checked="checked"'; ?>>
		<label for="do_coupon" style="width: 210px;"><?php _e('Add a Coupon', 'foxyshop'); ?></label>
	</div>
	<div class="foxyshop_field_control" id="product_coupon_entry_field"<?php if (!$_coupon) echo ' style="display: none;"'; ?>>
		<label for="_coupon"><?php _e('Code', 'foxyshop'); ?></label>
		<input type="text" name="_coupon" id="_coupon" value="<?php echo $_coupon; ?>" />
	</div>
	<?php if (version_compare($foxyshop_settings['version'], '2.0', ">=")) { ?>
	<div class="foxyshop_field_control">
		<label for="_expires"><?php _e('Expires', 'foxyshop'); ?></label>
		<input type="text" name="_expires" id="_expires" value="<?php echo $_expires; ?>" />
	</div>
	<?php } ?>
	<div style="clear: both;"></div>
	<?php
}




//-------------------------------------------
//Related Products Setup
//-------------------------------------------
function foxyshop_related_products_setup() {
	global $post, $foxyshop_settings, $bundledList, $addonList;
	$arr_related_products = explode(",",get_post_meta($post->ID,'_related_products',TRUE));
	if ($foxyshop_settings['enable_bundled_products']) $arr_bundled_products = explode(",",get_post_meta($post->ID,'_bundled_products',TRUE));
	if ($foxyshop_settings['enable_addon_products']) $arr_addon_products = explode(",",get_post_meta($post->ID,'_addon_products',TRUE));

	$relatedList = "";
	$bundledList = "";
	$addonList = "";
	$args = array('post_type' => 'foxyshop_product', "post__not_in" => array($post->ID), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC');
	$all_products = get_posts($args);
	foreach ($all_products as $product) {
		$relatedList .= '<option value="' . $product->ID . '"'. (in_array($product->ID, $arr_related_products) ? ' selected="selected"' : '') . '>' . $product->post_title . ' (' . $product->ID . ')</option>'."\n";
		if ($foxyshop_settings['enable_bundled_products']) $bundledList .= '<option value="' . $product->ID . '"'. (in_array($product->ID, $arr_bundled_products) ? ' selected="selected"' : '') . '>' . $product->post_title . '</option>'."\n";
		if ($foxyshop_settings['enable_addon_products']) $addonList .= '<option value="' . $product->ID . '"'. (in_array($product->ID, $arr_addon_products) ? ' selected="selected"' : '') . '>' . $product->post_title . ' (' . $product->ID . ')</option>'."\n";
	} ?>
	<select name="_related_products_list[]" id="_related_products_list" data-placeholder="Search for <?php echo FOXYSHOP_PRODUCT_NAME_PLURAL; ?>" style="width: 100%;" class="chzn-select" multiple="multiple">
		<?php echo $relatedList; ?>
	</select>
	<p style="color: #999999; margin-bottom: 2px;"><?php echo sprintf(__("Click the box above for a drop-down menu showing all %s. Type to search and click or press enter to select.", 'foxyshop'), strtolower(FOXYSHOP_PRODUCT_NAME_PLURAL)); ?></p>
	<div class="foxyshop_field_control">
		<label for="_related_order" style="width: 226px; margin-left: 0;"><?php _e('Set Custom Order For Related Products', 'foxyshop'); ?></label> <input type="text" style="width: 220px; float: left;" name="_related_order" id="_related_order" value="<?php echo get_post_meta($post->ID, "_related_order", 1) ?>" /> <span>ID's separated by comma</span>
	</div>
	<div style="clear: both;"></div>
	<?php
}




//-------------------------------------------
//Bundled Products Setup
//-------------------------------------------
function foxyshop_bundled_products_setup() {
	global $post, $foxyshop_settings, $bundledList;
	if (!isset($bundledList)) {
		$arr_bundled_products = explode(",",get_post_meta($post->ID,'_bundled_products',TRUE));
		$args = array('post_type' => 'foxyshop_product', "post__not_in" => array($post->ID), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC');
		$all_products = get_posts($args);
		foreach ($all_products as $product) {
			$bundledList .= '<option value="' . $product->ID . '"'. (in_array($product->ID, $arr_bundled_products) ? ' selected="selected"' : '') . '>' . $product->post_title . '</option>'."\n";
		}
	}
	?>
	<select name="_bundled_products_list[]" id="_bundled_products_list" data-placeholder="Search for <?php echo FOXYSHOP_PRODUCT_NAME_PLURAL; ?>" style="width: 100%;" class="chzn-select" multiple="multiple">
		<?php echo $bundledList; ?>
	</select>
	<p style="color: #999999;"><?php echo sprintf(__('Click the box above for a drop-down menu showing all %s. Type to search and click or press enter to select.', 'foxyshop'), strtolower(FOXYSHOP_PRODUCT_NAME_PLURAL)); ?></p>
	<?php
}


//-------------------------------------------
//Add-on Products Setup
//-------------------------------------------
function foxyshop_addon_products_setup() {
	global $post, $foxyshop_settings, $addonList;
	if (!isset($addonList)) {
		$arr_addon_products = explode(",",get_post_meta($post->ID,'_addon_products',TRUE));
		$args = array('post_type' => 'foxyshop_product', "post__not_in" => array($post->ID), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC');
		$all_products = get_posts($args);
		foreach ($all_products as $product) {
			$addonList .= '<option value="' . $product->ID . '"'. (in_array($product->ID, $arr_addon_products) ? ' selected="selected"' : '') . '>' . $product->post_title . ' (' . $product->ID . ')</option>'."\n";
		}
	}
	?>
	<select name="_addon_products_list[]" id="_addon_products_list" data-placeholder="Search for <?php echo FOXYSHOP_PRODUCT_NAME_PLURAL; ?>" style="width: 100%;" class="chzn-select" multiple="multiple">
		<?php echo $addonList; ?>
	</select>
	<p style="color: #999999;"><?php echo sprintf(__('Click the box above for a drop-down menu showing all %s. Type to search and click or press enter to select.', 'foxyshop'), strtolower(FOXYSHOP_PRODUCT_NAME_PLURAL)); ?></p>
	<div class="foxyshop_field_control">
		<label for="_addon_order" style="width: 220px; margin-left: 0;">Set Custom Order For Add-on Products</label> <input type="text" style="width: 220px; float: left;" name="_addon_order" id="_addon_order" value="<?php echo get_post_meta($post->ID, "_addon_order", 1) ?>" /> <span>ID's separated by comma</span>
	</div>
	<div style="clear: both;"></div>
	<?php
}




//-------------------------------------------
//Google Products Data
//-------------------------------------------
function foxyshop_google_products_data() {
	global $post, $google_product_field_names;

	echo '<p>The following data is used by the Google Product Search tool. Read the <a href="http://www.google.com/support/merchants/bin/answer.py?hl=en&answer=188494#US" target="_blank">feed specification</a> for specific field.<br /><em>Google Product Category</em> is required.</p>';

	foreach($google_product_field_names as $field) {
		$display_title = ucwords(str_replace("_", " ", $field));
		if (strlen($display_title) <= 4 && $display_title != "Size") $display_title = strtoupper($display_title);
		echo '<div class="foxyshop_field_control">'."\n";
		echo '<label for="_' . $field . '">' . $display_title . '</label>'."\n";
		echo '<input type="text" id="_' . $field . '" name="_' . $field . '" value="' . esc_attr(get_post_meta($post->ID, "_" . $field, 1)) . '" />'."\n";
		switch ($field) {
			case "google_product_category": echo '<span>(<a href="http://www.google.com/basepages/producttype/taxonomy.en-US.txt" target="_blank">options</a>)</span>'; break;
		}
		echo '</div>'."\n";
	}


	?>
	<div style="clear: both;"></div>
	<?php
}




//-------------------------------------------
//Product Images
//-------------------------------------------
function foxyshop_product_images_setup() {
	global $post, $foxyshop_settings;
	$upload_dir = wp_upload_dir();

	if (array_key_exists('error', $upload_dir)) {
		if ($upload_dir['error'] != '') {
			echo '<p style="color: red;"><strong>Warning:</strong> Images cannot be uploaded at this time. The error given is below.<br />Please attempt to correct the error and reload this page.</p>';
			echo '<p>' . $upload_dir['error'] . '</p>';
			return;
		}
	}

	echo '<link rel="stylesheet" href="' . FOXYSHOP_DIR . '/js/uploadify/uploadify.css" type="text/css" media="screen" />'."\n";
	echo '<script type="text/javascript" src="' . FOXYSHOP_DIR . '/js/uploadify/jquery.uploadify.v2.1.4.min.js"></script>'."\n";
	//echo '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>'."\n";

	echo '<div id="foxyshop_new_product_image_container">'."\n";
	echo '<input type="file" id="foxyshop_new_product_image">'."\n";
	echo '<div id="foxyshop_image_waiter"></div>';
	echo '</div>'."\n";
	echo '<input type="hidden" id="foxyshop_sortable_value" name="foxyshop_sortable_value" />'."\n";
	echo '<ul id="foxyshop_product_image_list">' . foxyshop_redraw_images($post->ID) . '</ul>'."\n";
	echo '<div style="clear: both;"></div>';

}




//-------------------------------------------
//Product Variations
//-------------------------------------------
function foxyshop_product_variations_setup() {
	global $post, $foxyshop_settings, $wp_version;

	//-------------------------------------------------------------------------------------------
	//Note: if making any changes, mirror these changes in foxyshoptools.php for saved variations
	//-------------------------------------------------------------------------------------------
	$var_type_array = array('dropdown' => __("Dropdown List", 'foxyshop'), 'radio' => __("Radio Buttons", 'foxyshop'), 'checkbox' => __("Checkbox", 'foxyshop'), 'text' => __("Single Line of Text", 'foxyshop'), 'textarea' => __("Multiple Lines of Text", 'foxyshop'), 'upload' => __("Custom File Upload", 'foxyshop'), 'hiddenfield' => __("Hidden Field", 'foxyshop'), 'descriptionfield' => __("Description Field", 'foxyshop'));
	$variation_key = __("Name{p+1.50|w-1|c:product_code|y:foxycart_category|dkey:display_key|ikey:image_id|v:actual_value|fr:sub_frequency}", 'foxyshop');

	//Setup Variations
	$variations = get_post_meta($post->ID, '_variations', 1);
	if (!is_array($variations)) $variations = array();

	$saved_variations = get_option('foxyshop_saved_variations');
	if (!is_array($saved_variations)) $saved_variations = array();

	echo '<input type="hidden" id="variation_order_value" name="variation_order_value" />'."\n";

	echo '<div id="variation_sortable">'."\n";
	$max_variations = count($variations);
	if ($max_variations == 0) $max_variations = 1;
	for ($i=1;$i<=$max_variations;$i++) {
		$dkeyhide = '';
		$_variationName = '';
		$_variation_type = 'dropdown';
		$_variationValue = '';
		$_variationDisplayKey = '';
		$_variationRequired = '';
		if (isset($variations[$i])) {
			$_variationName = isset($variations[$i]['name']) ? $variations[$i]['name'] : '';
			$_variation_type = isset($variations[$i]['type']) ? $variations[$i]['type'] : 'dropdown';
			$_variationValue = isset($variations[$i]['value']) ? $variations[$i]['value'] : '';
			$_variationDisplayKey = isset($variations[$i]['displayKey']) ? $variations[$i]['displayKey'] : '';
			$_variationRequired = isset($variations[$i]['required']) ? $variations[$i]['required'] : '';
		}
		if (!array_key_exists($_variation_type, $var_type_array) && $_variationName != "") {
			foreach($saved_variations as $saved_var) {
				if (sanitize_title($saved_var['refname']) == $_variation_type) {
					//$_variationName = $saved_var['name'];
					$dkeyhide = ' style="display:none;"';
				}
			}
			if (!$dkeyhide) continue;
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
				<label for="_variation_name_<?php echo $i; ?>"><?php _e('Variation Name', 'foxyshop'); ?></label>
				<input type="text" name="_variation_name_<?php echo $i; ?>" class="variation_name" id="_variation_name_<?php echo $i; ?>" value="<?php echo esc_attr($_variationName); ?>" />

				<label for="_variation_type_<?php echo $i; ?>" class="variationtypelabel"><?php _e('Variation Type', 'foxyshop'); ?></label>
				<select name="_variation_type_<?php echo $i; ?>" id="_variation_type_<?php echo $i; ?>" class="variationtype">
				<?php
				foreach ($var_type_array as $var_name => $var_val) {
					echo '<option value="' . $var_name . '"' . ($_variation_type == $var_name ? ' selected="selected"' : '') . '>' . $var_val . '  </option>'."\n";
				}
				if (is_array($saved_variations) && count($saved_variations) > 0) {
					echo '<optgroup label="' . __('Saved Variations', 'foxyshop') . '">'."\n";
					foreach($saved_variations as $saved_var) {
						$saved_ref = $saved_var['refname'];
						echo '<option value="' . sanitize_title($saved_ref) . '" rel="' . esc_attr($saved_var['name']) . '"' . (sanitize_title($saved_ref) == $_variation_type ? ' selected="selected"' : '') . '>' . $saved_ref . '  </option>'."\n";
					}
					echo '</optgroup>'."\n";
				}
				?>
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
							<input type="text" name="_variation_textsize1_<?php echo $i; ?>" id="_variation_textsize1_<?php echo $i; ?>" value="<?php if (isset($arrVariationTextSize)) echo $arrVariationTextSize[0]; ?>" /> <?php _e('characters'); ?>
						</div>
						<div class="foxyshop_field_control">
							<label for="_variation_textsize2_<?php echo $i; ?>"><?php _e('Maximum Chars', 'foxyshop'); ?></label>
							<input type="text" name="_variation_textsize2_<?php echo $i; ?>" id="_variation_textsize2_<?php echo $i; ?>" value="<?php if (isset($arrVariationTextSize)) echo $arrVariationTextSize[1]; ?>" /> <?php _e('characters'); ?>
						</div>
						<div style="clear: both;"></div>
					</div>

				<?php elseif($_variation_type == "textarea") : ?>
					<!-- Textarea -->
					<div class="foxyshop_field_control textarea variationoptions">
						<label for="_variation_textareasize_<?php echo $i; ?>"><?php _e('Lines of Text', 'foxyshop'); ?></label>
						<input type="text" name="_variation_textareasize_<?php echo $i; ?>" id="_variation_textareasize_<?php echo $i; ?>" value="<?php echo esc_attr($_variationValue); ?>" /> (default is 3)
					</div>

				<?php elseif($_variation_type == "descriptionfield") : ?>
					<!-- Description Field -->
					<div class="foxyshop_field_control descriptionfield variationoptions">
						<label for="_variation_description_<?php echo $i; ?>"><?php _e('Descriptive Text', 'foxyshop'); ?></label>
						<textarea name="_variation_description_<?php echo $i; ?>" id="_variation_description_<?php echo $i; ?>"><?php echo $_variationValue; ?></textarea>
					</div>

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

				<?php elseif($_variation_type == "hiddenfield") : ?>
					<!-- Hidden Field -->
					<div class="foxyshop_field_control hiddenfield variationoptions">
						<div class="foxyshop_field_control">
							<label for="_variation_hiddenfield_<?php echo $i; ?>"><?php _e('Value', 'foxyshop'); ?></label>
							<input type="text" name="_variation_hiddenfield_<?php echo $i; ?>" id="_variation_hiddenfield_<?php echo $i; ?>" value="<?php echo $_variationValue; ?>" />
						</div>
					</div>
					<?php $dkeyhide = ' style="display: none;"'; ?>

				<?php else : ?>
					<!-- Saved Variation -->
					<p class="foxyshop_saved_variation"><em><?php _e('This varation will use saved settings.', 'foxyshop'); ?></em></p>
				<?php endif; ?>
			</div>

			<!-- //// DISPLAY KEY //// -->
			<div class="foxyshop_field_control dkeycontainer"<?php echo $dkeyhide; ?>>
				<label class="dkeylabel" title="Enter a value here if you want your variation to be invisible until called by another variation."><?php _e('Display Key', 'foxyshop'); ?></label>
				<input type="text" name="_variation_dkey_<?php echo $i; ?>" id="_variation_dkey_<?php echo $i; ?>" value="<?php echo esc_attr($_variationDisplayKey); ?>" class="dkeynamefield" />

				<!-- Required -->
				<div class="variation_required_container" rel="<?php echo $i; ?>"<?php echo ($_variation_type == 'dropdown' || $_variation_type == 'text' || $_variation_type == 'textarea' || $_variation_type == 'upload' || $_variation_type == 'checkbox' ? '' : ' style="display: none;"'); ?>>
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
	<button type="button" id="VariationMinimizeAll" class="button" style="float: right;"><?php _e('Minimize All', 'foxyshop'); ?></button>
	<button type="button" id="VariationMaximizeAll" class="button" style="display:none; float: right;"><?php _e('Maximize All', 'foxyshop'); ?></button>
	<input type="hidden" name="max_variations" id="max_variations" value="<?php echo $max_variations; ?>" />

<script type="text/javascript">

var variation_key = '<?php echo $variation_key; ?>';
var variation_select_options = "";
<?php
foreach ($var_type_array as $var_name => $var_val) {
	echo "variation_select_options += '<option value=\"" . $var_name . '">' . $var_val . "  </option>';\n";
}
if (is_array($saved_variations)) {
	echo "\t\tvariation_select_options += '<optgroup label=\"" . __('Saved Variations', 'foxyshop') . "\">';\n";
	foreach($saved_variations as $saved_var) {
		$saved_ref = $saved_var['refname'];
		echo "\t\tvariation_select_options += '<option value=\"" . sanitize_title($saved_ref) . "\" rel=\"" . esc_attr($saved_var['name']) . "\">" . esc_attr($saved_var['name']) . "  </option>';\n";
	}
	echo "\t\tvariation_select_options += '</optgroup>';\n";
}

?>
</script>
<script type="text/javascript" src="<?php echo FOXYSHOP_DIR . '/js/products-admin.js'; ?>"></script>

<?php
}





//-------------------------------------------
//Save All Product Info
//-------------------------------------------
function foxyshop_product_meta_save($post_id) {
	global $foxyshop_settings, $google_product_field_names;
	if (!wp_verify_nonce((isset($_POST['products_meta_noncename']) ? $_POST['products_meta_noncename'] : ""),__FILE__)) return $post_id;
	if (!current_user_can('edit_'.($_POST['post_type'] == 'page' ? 'page' : 'post'), $post_id)) return $post_id;

	if (!isset($_POST['_weight1']) && !isset($_POST['_weight2'])) {
		$_weight = "";
	} else {
		$_weight = (int)$_POST['_weight1'] . ' ' . (double)$_POST['_weight2'];
	}

	//Remove Illegal Characters From Code
	$_code = trim($_POST['_code']);
	$_code = str_replace('"', '', $_code);
	$_code = str_replace('&', '', $_code);

	//Save Product Detail Data
	foxyshop_save_meta_data('_weight',$_weight);
	foxyshop_save_meta_data('_price',number_format((double)str_replace(",","",$_POST['_price']),FOXYSHOP_DECIMAL_PLACES,".",""));
	foxyshop_save_meta_data('_code',$_code);
	if (isset($_POST['_category'])) foxyshop_save_meta_data('_category',$_POST['_category']);
	foxyshop_save_meta_data('_hide_product',(isset($_POST['_hide_product']) ? $_POST['_hide_product'] : ""));

	//Quantity Settings
	$_quantity_min = (int)$_POST['_quantity_min'];
	$_quantity_max = (int)$_POST['_quantity_max'];
	if ($_quantity_min > $_quantity_max && $_quantity_max > 0) $_quantity_min = "";
	if ($_quantity_min <= 0) $_quantity_min = "";
	if ($_quantity_max <= 0) $_quantity_max = "";
	foxyshop_save_meta_data('_quantity_min',$_quantity_min);
	foxyshop_save_meta_data('_quantity_max',$_quantity_max);
	foxyshop_save_meta_data('_quantity_hide',(isset($_POST['_quantity_hide']) ? $_POST['_quantity_hide'] : ""));

	//Require SSO
	if ($foxyshop_settings['enable_sso'] && $foxyshop_settings['sso_account_required'] == 2) {
		foxyshop_save_meta_data('_require_sso',(isset($_POST['_require_sso']) ? $_POST['_require_sso'] : ""));
	}

	//Save Sale Pricing Data
	$saleprice = number_format((double)str_replace(",","",$_POST['_saleprice']),FOXYSHOP_DECIMAL_PLACES,".","");
	if ($saleprice == 0) $saleprice = "";
	foxyshop_save_meta_data('_saleprice',$saleprice);
	if (($_salestartdate = strtotime($_POST['_salestartdate'])) === false) foxyshop_save_meta_data('_salestartdate',"999999999999999999");
	else foxyshop_save_meta_data('_salestartdate',$_salestartdate);
	if (($_saleenddate = strtotime($_POST['_saleenddate'])) === false) foxyshop_save_meta_data('_saleenddate',"999999999999999999");
	else foxyshop_save_meta_data('_saleenddate',$_saleenddate);

	//Discounts
	$discount_array = array("discount_quantity_amount", "discount_quantity_percentage", "discount_price_amount", "discount_price_percentage");
	foreach ($discount_array as $val) {
		if ($_POST['discount_method'] == $val) {
			foxyshop_save_meta_data('_' . $val, $_POST['computed_discount']);
		} else {
			foxyshop_save_meta_data('_' . $val, '');
		}
	}



	//Subscriptions
	if (isset($_POST['_sub_frequency'])) {
		if ($_POST['_sub_frequency'] == "") {
			foxyshop_save_meta_data('_sub_frequency', "");
		} else {
			foxyshop_save_meta_data('_sub_frequency', $_POST['_sub_frequency']);
		}
		if ($_POST['_sub_startdate'] == "") {
			foxyshop_save_meta_data('_sub_startdate', "");
		} else {
			foxyshop_save_meta_data('_sub_startdate', $_POST['_sub_startdate']);
		}
		if ($_POST['_sub_enddate'] == "") {
			foxyshop_save_meta_data('_sub_enddate', "");
		} else {
			foxyshop_save_meta_data('_sub_enddate', $_POST['_sub_enddate']);
		}
	}

	//Extra Options
	if (isset($_POST['do_coupon'])) {
		foxyshop_save_meta_data('_coupon', $_POST['_coupon']);
	} else {
		foxyshop_save_meta_data('_coupon', '');
	}
	if (isset($_POST['_empty'])) {
		foxyshop_save_meta_data('_empty', $_POST['_empty']);
	} else {
		foxyshop_save_meta_data('_empty', '');
	}

	if (isset($_POST['_cart'])) {
		foxyshop_save_meta_data('_cart', $_POST['_cart']);
	} else {
		foxyshop_save_meta_data('_cart', '');
	}

	if (isset($_POST['_expires'])) {
		foxyshop_save_meta_data('_expires', $_POST['_expires']);
	} else {
		foxyshop_save_meta_data('_expires', '');
	}




	//Save Related Product Data
	if (isset($_POST['_related_products_list'])) {
		foxyshop_save_meta_data('_related_products', implode(",",$_POST['_related_products_list']));
	} else {
		foxyshop_save_meta_data('_related_products', "");
	}
	if (isset($_POST['_related_order'])) foxyshop_save_meta_data('_related_order', $_POST['_related_order']);
	if (isset($_POST['_addon_order'])) foxyshop_save_meta_data('_addon_order', $_POST['_addon_order']);

	//Save Bundled Product Data
	if (isset($_POST['_bundled_products_list'])) {
		foxyshop_save_meta_data('_bundled_products', implode(",",$_POST['_bundled_products_list']));
	} else {
		foxyshop_save_meta_data('_bundled_products', "");
	}

	//Save Add-On Product Data
	if (isset($_POST['_addon_products_list'])) {
		foxyshop_save_meta_data('_addon_products', implode(",",$_POST['_addon_products_list']));
	} else {
		foxyshop_save_meta_data('_addon_products', "");
	}

	//Inventory Levels
	if ($foxyshop_settings['manage_inventory_levels']) {
		$inventory_array = array();
		for ($i=1; $i<=$_POST['max_inventory_count']; $i++) {
			if ($_POST['inventory_code_'.$i] && $_POST['inventory_count_'.$i] != '') {
				$alert_level = $_POST['inventory_alert_'.$i];
				if ($alert_level != '') $alert_level = (int)$alert_level;
				$inventory_array[stripslashes(str_replace("'","",$_POST['inventory_code_'.$i]))] = array("count" => (int)$_POST['inventory_count_'.$i], "alert" => $alert_level);
			}
		}
		if (count($inventory_array) == 0) $inventory_array = "";
		foxyshop_save_meta_data('_inventory_levels', $inventory_array);
	}

	//Save Product Variations
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
		$_variationName = trim(str_replace("&","and",str_replace(".","",str_replace('"','',$_POST['_variation_name_'.$target_id]))));
		$_variationType = $_POST['_variation_type_'.$target_id];
		$_variationDisplayKey = $_POST['_variation_dkey_'.$target_id];
		$_variationRequired = (isset($_POST['_variation_required_'.$target_id]) ? $_POST['_variation_required_'.$target_id] : '');
		$_variationValue = "";
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
		} elseif ($_variationType == 'radio') {
			$_variationValue = $_POST['_variation_radio_'.$target_id];
		} elseif ($_variationType == 'hiddenfield') {
			$_variationValue = $_POST['_variation_hiddenfield_'.$target_id];
		}

		$variations[$currentID] = array(
			"name" => stripslashes($_variationName),
			"type" => stripslashes($_variationType),
			"value" => stripslashes($_variationValue),
			"displayKey" => stripslashes($_variationDisplayKey),
			"required" => stripslashes($_variationRequired)
		);
		$currentID++;
	}

	if (count($variations) == 0) $variations = "";
	foxyshop_save_meta_data('_variations', $variations);


	//Google Products Fields
	if ($foxyshop_settings['google_product_support']) {
		foreach($google_product_field_names as $field) {
			foxyshop_save_meta_data("_" . $field, $_POST["_" . $field]);
		}
	}

	//Save Action (For Other Integrations)
	do_action("foxyshop_save_product", $post_id);

	return;
}
