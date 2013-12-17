<?php /*
------------ ATTENTION ------------
If you need to edit this template, do not edit the version in the plugin directory. Place a copy in your template folder and edit it there.
This will allow you to upgrade FoxyShop without breaking your customizations. More details here: http://www.foxy-shop.com/documentation/theme-customization/
-----------------------------------
*/

global $product, $foxyshop_category_slug, $post;
?>
<?php foxyshop_include('header'); ?>
<div id="foxyshop_container">
	<?php
	//-------------------------------------------------------------------------------------------------
	// Remember that the products on these category pages link to the generated page links (permalinks)
	//-------------------------------------------------------------------------------------------------


	//Write Breadcrumbs (Probably can't use these from widget very effectively - better to build your own)
	//foxyshop_breadcrumbs(" &raquo; ");

	//Get Current Page Info
	$term = get_term_by('slug', $foxyshop_category_slug, "foxyshop_categories");
	$currentCategoryName = $term->name;
	$currentCategoryDescription = $term->description;
	$currentCategorySlug = $term->slug;
	$currentCategoryID = $term->term_id;

	//Write Category Title (if you want the title in, just uncomment line below)
	//echo '<h1 id="foxyshop_category_title">' . str_replace("_","",$currentCategoryName) . '</h1>'."\n";

	//If there's a category description, write it here
	if ($currentCategoryDescription) echo '<p>' . $currentCategoryDescription . '</p>'."\n";


	//Run the query for all products in this category
	//Note that the widget displays ALL products since pagination isn't possible

	$args = array('post_type' => 'foxyshop_product', "foxyshop_categories" => $currentCategorySlug, 'post_status' => 'publish', 'posts_per_page' => -1);
	$args = array_merge($args,foxyshop_sort_order_array());
	$args = array_merge($args,foxyshop_hide_children_array($currentCategoryID));
	$category_contents = get_posts($args);
	echo '<ul class="foxyshop_product_list">';
	foreach($category_contents as $post) {
		setup_postdata($post);

		//Product Display
		foxyshop_include('product-loop');

	}
	echo '</ul>';
	?>
</div>
<?php foxyshop_include('footer'); ?>

<script type="text/javascript">
jQuery(document).ready(function($){
	//Products
	//This is set up for a two-column display. For a three column you need to do: nth-child(3n+1)
	$(".foxyshop_product_list>li:nth-child(odd)").css("clear","left");

	//Subcategories
	//This is set up for a three-column display. For a two column you need to do: nth-child(odd)
	$(".foxyshop_categories>li:nth-child(3n+1)").css("clear","left");

});
</script>