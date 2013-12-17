<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

add_action('widgets_init', 'foxyshop_load_widgets');

function foxyshop_load_widgets() {
	register_widget('FoxyShop_Category');
	register_widget('FoxyShop_Cart_Link');
	register_widget('FoxyShop_Category_List');
}


class FoxyShop_Category extends WP_Widget {

	//Widget Setup
	function FoxyShop_Category() {
		$widget_ops = array('classname' => 'foxyshop_category', 'description' => sprintf(__('Show the contents of a FoxyShop %s category.', 'foxyshop'), strtolower(FOXYSHOP_PRODUCT_NAME_SINGULAR)));
		$control_ops = array('width' => 300, 'height' => 350, 'id_base' => 'foxyshop-category-widget');
		$this->WP_Widget('foxyshop-category-widget', __('FoxyShop Category', 'foxyshop'), $widget_ops, $control_ops);
	}

	//Widget Display
	function widget($args, $instance) {
		extract($args);

		//Our variables from the widget settings
		$title = apply_filters('widget_title', $instance['title']);
		$categoryName = $instance['categoryName'];
		$showMoreDetails = isset($instance['showMoreDetails']) ? $instance['showMoreDetails'] : false;
		$showAddToCart = isset($instance['showAddToCart']) ? $instance['showAddToCart'] : false;
		$showMax = $instance['showMax'] > 0 ? $instance['showMax'] : -1;

		echo $before_widget;
		if ($title) echo $before_title . $title . $after_title;

		if ($instance['simpleView']) {
			echo '<div class="foxyshop_category_simple_widget">';
			foxyshop_featured_category($categoryName, $showAddToCart, $showMoreDetails, $showMax, True);
			echo '</div>';
		} else {
			echo '<div class="foxyshop_category_widget">';
			foxyshop_featured_category($categoryName, $showAddToCart, $showMoreDetails, $showMax);
			echo '</div>';
		}
		echo $after_widget;
	}

	//Update Widget Settings
	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['categoryName'] = strip_tags($new_instance['categoryName']);
		$instance['showMax'] = (int)strip_tags($new_instance['showMax']);

		/* No need to strip tags */
		$instance['simpleView'] = $new_instance['simpleView'];
		$instance['showAddToCart'] = $new_instance['showAddToCart'];
		$instance['showMoreDetails'] = $new_instance['showMoreDetails'];

		return $instance;
	}

	//Widget Control Panel
	function form($instance) {

		//Defaults
		$defaults = array(
			'title' => "",
			'categoryName' => "",
			'showAddToCart' => "",
			'showMoreDetails' => "on",
			'simpleView' => "",
			'showMax' => -1
		);
		$instance = wp_parse_args((array)$instance, $defaults);
		?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'foxyshop'); ?>:</label>
			<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" style="width:260px;" />
		</p>

		<!-- Select Category -->
		<p>
			<label for="<?php echo $this->get_field_id('categoryName'); ?>"><?php _e('Category', 'foxyshop'); ?>:</label>
			<select id="<?php echo $this->get_field_id('categoryName'); ?>" name="<?php echo $this->get_field_name('categoryName'); ?>" class="widefat" style="width:100%;">
				<option value="">- - <?php _e('Select Category', 'foxyshop'); ?> - -</option>
				<?php
				$toplevelterms = get_terms('foxyshop_categories', 'hide_empty=0&hierarchical=0');
				$arrCategory = array();
				foreach ($toplevelterms as $toplevelterm) {
					echo '<option value="' . $toplevelterm->slug .'"';
					if ($instance['categoryName'] == $toplevelterm->slug) echo ' selected="selected"';
					echo '>' . str_replace("_","",$toplevelterm->name) . '</option>';
				}
				?>
			</select>
		</p>

		<!-- Max Entries -->
		<p>
			<label for="<?php echo $this->get_field_id('showMax'); ?>"><?php echo sprintf(__('Max %s to Show', 'foxyshop'), FOXYSHOP_PRODUCT_NAME_PLURAL); ?>:</label>
			<input id="<?php echo $this->get_field_id('showMax'); ?>" name="<?php echo $this->get_field_name('showMax'); ?>" value="<?php echo ($instance['showMax'] != 0 ? $instance['showMax'] : ''); ?>" style="width:50px;" /> <span class="small">(<?php _e('optional', 'foxyshop'); ?>)</span>
		</p>


		<p>
			<input class="checkbox" type="checkbox" <?php checked($instance['simpleView'], 'on'); ?> id="<?php echo $this->get_field_id('simpleView'); ?>" name="<?php echo $this->get_field_name('simpleView'); ?>" />
			<label for="<?php echo $this->get_field_id('simpleView'); ?>"><?php _e('Show Simple View', 'foxyshop'); ?></label>
		</p>

		<!-- Show Checkboxes for Button Selection -->
		<p>
			<input class="checkbox" type="checkbox" <?php checked($instance['showAddToCart'], 'on'); ?> id="<?php echo $this->get_field_id('showAddToCart'); ?>" name="<?php echo $this->get_field_name('showAddToCart'); ?>" />
			<label for="<?php echo $this->get_field_id('showAddToCart'); ?>"><?php _e('Show Add To Cart Button', 'foxyshop'); ?></label>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked($instance['showMoreDetails'], 'on'); ?> id="<?php echo $this->get_field_id('showMoreDetails'); ?>" name="<?php echo $this->get_field_name('showMoreDetails'); ?>" />
			<label for="<?php echo $this->get_field_id('showMoreDetails'); ?>"><?php _e('Show More Details Button', 'foxyshop'); ?></label>
		</p>


	<?php
	}
}




class FoxyShop_Cart_Link extends WP_Widget {

	//Widget Setup
	function FoxyShop_Cart_Link() {
		$widget_ops = array('classname' => 'foxyshop_cart_link', 'description' => __('Show a link to view shopping cart.', 'foxyshop'));
		$control_ops = array('width' => 300, 'height' => 350, 'id_base' => 'foxyshop-cart-link-widget');
		$this->WP_Widget('foxyshop-cart-link-widget', __('FoxyShop Cart Link', 'foxyshop'), $widget_ops, $control_ops);
	}

	//Widget Display
	function widget($args, $instance) {
		extract($args);

		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title']);
		$linkText = $instance['linkText'];
		$hideEmpty = isset($instance['hideEmpty']) ? $instance['hideEmpty'] : false;

		echo $before_widget;
		if ($title) echo $before_title . $title . $after_title;

		echo '<ul class="foxyshop_cart_link_widget"><li>';
		foxyshop_cart_link($linkText, $hideEmpty);
		echo '</li></ul>';
		echo $after_widget;
	}

	//Update Widget Settings
	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags($new_instance['title']);

		/* No need to strip tags */
		$instance['linkText'] = $new_instance['linkText'];
		$instance['hideEmpty'] = $new_instance['hideEmpty'];

		return $instance;
	}

	//Widget Control Panel
	function form($instance) {

		//Defaults
		$defaults = array(
			'title' => __('Your Shopping Cart', 'foxyshop'),
			'linkText' => __('View Cart', 'foxyshop'),
			'hideEmpty' => ""
		);
		$instance = wp_parse_args((array)$instance, $defaults); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'foxyshop'); ?>:</label>
			<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" style="width:260px;" />
		</p>

		<!-- Max Entries -->
		<p>
			<div><?php _e('Link Text:'); ?></div>
			<textarea id="<?php echo $this->get_field_id('linkText'); ?>" name="<?php echo $this->get_field_name('linkText'); ?>" style="width: 100%;"><?php echo $instance['linkText']; ?></textarea>
			<span class="small"><?php _e('Example', 'foxyshop'); ?>: View Cart (%q Items) ($%p)</span>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked($instance['hideEmpty'], 'on'); ?> id="<?php echo $this->get_field_id('hideEmpty'); ?>" name="<?php echo $this->get_field_name('hideEmpty'); ?>" />
			<label for="<?php echo $this->get_field_id('hideEmpty'); ?>"><?php _e('Hide Link if Cart is Empty', 'foxyshop'); ?></label>
		</p>

	<?php
	}
}

class FoxyShop_Category_List extends WP_Widget {

	//Widget setup.
	function FoxyShop_Category_List() {
		$widget_ops = array('classname' => 'foxyshop_category_list', 'description' => __('Show the FoxyShop category list.', 'foxyshop'));
		$control_ops = array('width' => 300, 'height' => 350, 'id_base' => 'foxyshop-category-list-widget');
		$this->WP_Widget('foxyshop-category-list-widget', __('FoxyShop Category List', 'foxyshop'), $widget_ops, $control_ops);
	}

	//Widget Display
	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$categoryID = $instance['categoryID'];
		$depth = $instance['depth'];
		if ($depth == "") $depth = 1;

		echo $before_widget;
		if ($title) echo $before_title . $title . $after_title;

		echo '<ul>';
		foxyshop_simple_category_children($categoryID, $depth);
		echo '</ul>';

		echo $after_widget;
	}

	//Update Widget Settings
	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		// Strip tags for title and name to remove HTML (important for text inputs)
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['categoryID'] = (int)strip_tags($new_instance['categoryID']);
		$instance['depth'] = strip_tags($new_instance['depth']);

		return $instance;
	}

	//Widget Control Panel
	function form($instance) {

		//Defaults
		$defaults = array(
			'title' => "",
			'categoryID' => 0,
			'depth' => 1
		);
		$instance = wp_parse_args((array)$instance, $defaults); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'foxyshop'); ?>:</label>
			<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" style="width:260px;" />
		</p>

		<!-- Select Category -->
		<p>
			<label for="<?php echo $this->get_field_id('categoryID'); ?>"><?php _e('Parent Category', 'foxyshop'); ?>:</label>
			<select id="<?php echo $this->get_field_id('categoryID'); ?>" name="<?php echo $this->get_field_name('categoryID'); ?>" class="widefat" style="width:100%;">
				<option value="0"><?php _e('Top Level Categories', 'foxyshop'); ?></option>
				<?php
				$toplevelterms = get_terms('foxyshop_categories', 'hide_empty=0&hierarchical=0');
				$arrCategory = array();
				foreach ($toplevelterms as $toplevelterm) {
					echo '<option value="' . $toplevelterm->term_id .'"';
					if ($instance['categoryID'] == $toplevelterm->term_id) echo ' selected="selected"';
					echo '>' . str_replace("_","",$toplevelterm->name) . '</option>';
				}
				?>
			</select>
		</p>

		<!-- Depth -->
		<p>
			<label for="<?php echo $this->get_field_id('depth'); ?>"><?php _e('Depth', 'foxyshop'); ?>:</label>
			<input id="<?php echo $this->get_field_id('depth'); ?>" name="<?php echo $this->get_field_name('depth'); ?>" value="<?php echo $instance['depth']; ?>" style="width:50px;" /> <span class="small"><?php _e('(default: 1)', 'foxyshop'); ?></span>
		</p>

	<?php
	}
}



//Dashboard Stats
if (is_array($foxyshop_settings)) {
	if ($foxyshop_settings['enable_dashboard_stats']) {
		add_action('wp_dashboard_setup', 'add_foxyshop_dashboard_stats');
		add_action('wp_ajax_foxyshop_order_history_dashboard_action', 'foxyshop_order_history_dashboard_ajax');
	}
}

function add_foxyshop_dashboard_stats() {
	if (!current_user_can(apply_filters('foxyshop_dashboard_stats_perm', 'manage_options'))) return;
	wp_add_dashboard_widget('foxyshop_dashboard_widget', __('FoxyShop Statistics', 'foxyshop'), 'foxyshop_dashboard_stats');

	//Move Widget to Right Column
	global $wp_meta_boxes;
	$my_widget = $wp_meta_boxes['dashboard']['normal']['core']['foxyshop_dashboard_widget'];
	unset($wp_meta_boxes['dashboard']['normal']['core']['foxyshop_dashboard_widget']);
	$wp_meta_boxes['dashboard']['side']['core']['foxyshop_dashboard_widget'] = $my_widget;
}

function foxyshop_dashboard_stats() {
	echo '<div id="foxyshop_statsright">'."\n";
	echo '<img src="' . FOXYSHOP_DIR . '/images/logo.png" alt="FoxyShop" style="width: 100%; max-width: 230px; float: right;" />'."\n";
	echo '</div>';

	echo '<div id="foxyshop_statsleft">'."\n";

	echo '<h4>' . __('Order History', 'foxyshop') . '</h4>'."\n";
	echo '<ul id="foxyshop_dashboard_order_history">'."\n";
	echo '<li>' . __('Loading', 'foxyshop') . '...</li>';
	echo '</ul>'."\n";

	$count_posts = wp_count_posts('foxyshop_product');
	$tax = get_terms('foxyshop_categories', array("hide_empty" => 0));

	echo '<h4 style="margin-top: 24px;">' . FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . __('Summary', 'foxyshop') . '</h4>';
	echo '<ul>';
	echo '<li><a href="edit.php?post_type=foxyshop_product">' . $count_posts->publish . ' ' . ($count_posts->publish == 1 ? FOXYSHOP_PRODUCT_NAME_SINGULAR : FOXYSHOP_PRODUCT_NAME_PLURAL) . '</a></li>';
	echo '<li><a href="edit-tags.php?taxonomy=foxyshop_categories&post_type=foxyshop_product">' . count($tax) . ' ' . FOXYSHOP_PRODUCT_NAME_SINGULAR . ' ' . _n('Category', 'Categories', count($tax), 'foxyshop') . '</a></li>';
	echo '</ul>';

	echo '</div>'."\n";
	?>

	<script type="text/javascript">
	jQuery(document).ready(function($){
		$.post(ajaxurl, { action: 'foxyshop_order_history_dashboard_action', security: '<?php echo wp_create_nonce("foxyshop-order-info-dashboard"); ?>' }, function(response) {
			$("#foxyshop_dashboard_order_history").html(response)
		});
	});
	</script>
	<?php
	echo '<div style="clear: both;"></div>'."\n";
}

//AJAX Order History - this is in an AJAX call to avoid a delay in rendering the dashboard
function foxyshop_order_history_dashboard_ajax() {
	global $foxyshop_settings;
	check_ajax_referer('foxyshop-order-info-dashboard', 'security');

	//Get Order Info
	$foxy_data = array(
		"api_action" => "transaction_list",
		"transaction_date_filter_begin" => date("Y-m-d", strtotime("-30 days")),
		"transaction_date_filter_end" => date("Y-m-d"),
		"is_test_filter" => "0",
		"hide_transaction_filter" => ""
	);
	if ($foxyshop_settings['version'] >= "0.7.1") $foxy_data['entries_per_page'] = 300;
	$foxy_response = foxyshop_get_foxycart_data($foxy_data);
	$xml = simplexml_load_string($foxy_response, NULL, LIBXML_NOCDATA);
	$orderstats = array(1 => array(0, 0), 7 => array(0, 0), 30 => array(0, 0));
	if ($xml->result != "ERROR") {
		foreach($xml->transactions->transaction as $transaction) {
			$transaction_date = (string)$transaction->transaction_date;
			$transaction_total = (double)$transaction->order_total;

			if (strtotime($transaction_date) >= strtotime("-24 hours")) {
				$orderstats[1][0]++;
				$orderstats[1][1] += $transaction_total;
			}

			if (strtotime($transaction_date) >= strtotime("-7 days")) {
				$orderstats[7][0]++;
				$orderstats[7][1] += $transaction_total;
			}

			$orderstats[30][0]++;
			$orderstats[30][1] += $transaction_total;
		}
	}
	echo '<li>' . __('One Day', 'foxyshop') . ': <a href="edit.php?foxyshop_search=1&amp;is_test_filter=&amp;post_type=foxyshop_product&amp;page=foxyshop_order_management&amp;transaction_date_filter_begin=' . date("Y-m-d", strtotime("-1 day")) . '&amp;transaction_date_filter_end='.date("Y-m-d") . '">' . $orderstats[1][0] . ' ' . _n('order', 'orders', $orderstats[1][0], 'foxyshop') . ', ' . foxyshop_currency($orderstats[1][1]) . '</a></li>'."\n";
	echo '<li>' . __('Seven Days', 'foxyshop') . ': <a href="edit.php?foxyshop_search=1&amp;is_test_filter=&amp;post_type=foxyshop_product&amp;page=foxyshop_order_management&amp;transaction_date_filter_begin=' . date("Y-m-d", strtotime("-7 days")) . '&amp;transaction_date_filter_end='.date("Y-m-d") . '">' . $orderstats[7][0] . ' ' . _n('order', 'orders', $orderstats[7][0], 'foxyshop') . ', ' . foxyshop_currency($orderstats[7][1]) . '</a></li>'."\n";
	echo '<li>' . __('30 Days', 'foxyshop') . ': <a href="edit.php?foxyshop_search=1&amp;is_test_filter=&amp;post_type=foxyshop_product&amp;page=foxyshop_order_management&amp;transaction_date_filter_begin=' . date("Y-m-d", strtotime("-30 days")) . '&amp;transaction_date_filter_end='.date("Y-m-d") . '">' . $orderstats[30][0] . ' ' . _n('order', 'orders', $orderstats[30][0], 'foxyshop') . ', ' . foxyshop_currency($orderstats[30][1]) . '</a></li>'."\n";
	die;
}
