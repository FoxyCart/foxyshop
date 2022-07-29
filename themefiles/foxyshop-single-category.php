<?php /*
------------ ATTENTION ------------
If you need to edit this template, do not edit the version in the plugin directory. Place a copy in your template folder and edit it there.
This will allow you to upgrade FoxyShop without breaking your customizations. More details here: http://www.foxy-shop.com/documentation/theme-customization/
-----------------------------------
*/ ?>

<?php get_header(); ?>

<?php foxyshop_include('header'); ?>
<div class="foxyshop_container">
	<?php
	global $product;

	//Write Breadcrumbs
	foxyshop_breadcrumbs(" &raquo; ");

	//Get Current Page Info
	$term = get_term_by('slug', get_query_var('term'), "foxyshop_categories");
	$currentCategoryName = $term->name;
	$currentCategoryDescription = $term->description;
	$currentCategorySlug = $term->slug;
	$currentCategoryID = $term->term_id;

	//Write Category Title
	echo '<h1 id="foxyshop_category_title">' . str_replace("_","",$currentCategoryName) . '</h1>'."\n";

	//Write Product Sort Dropdown
	//foxyshop_sort_dropdown();

	//If there's a category description, write it here
	if ($currentCategoryDescription) echo '<p>' . $currentCategoryDescription . '</p>'."\n";

	//Show Children Categories
	foxyshop_category_children($currentCategoryID);

	//Run the query for all products in this category
	$args = array('post_type' => 'foxyshop_product', "foxyshop_categories" => $currentCategorySlug, 'post_status' => 'publish', 'posts_per_page' => foxyshop_products_per_page(), 'paged' => get_query_var('paged'));
	$args = array_merge($args,foxyshop_sort_order_array($currentCategoryID));
	$args = array_merge($args,foxyshop_hide_children_array($currentCategoryID));
	query_posts($args);
	echo '<ul class="foxyshop_product_list">';
	while (have_posts()) :
		the_post();

		//Product Display
		foxyshop_include('product-loop');

	endwhile;
	echo '</ul>';

	//Pagination
	foxyshop_get_pagination();
	?>
</div>
<?php foxyshop_include('footer'); ?>
 

<?php get_footer(); ?>
