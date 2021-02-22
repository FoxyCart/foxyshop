<?php /*
------------ ATTENTION ------------
If you need to edit this template, do not edit the version in the plugin directory. Place a copy in your template folder and edit it there.
This will allow you to upgrade FoxyShop without breaking your customizations. More details here: http://www.foxy-shop.com/documentation/theme-customization/
-----------------------------------
*/ ?>

<?php get_header(); ?>

<?php foxyshop_include('header'); ?>
<div class="foxyshop_container">
	<h1 id="foxyshop_category_title">Product Search</h1>

	<form class="searchform" action="<?php bloginfo("url"); ?>/product-search/" method="get">
		<input type="text" name="search" value="" />
		<button type="submit" name="submitsearch" id="submitsearch">Search Products</button>
	</form>


	<?php
	global $product;
	$search = (isset($_REQUEST['search']) ? urlencode($_REQUEST['search']) : "sdafasdfasdfasdfasdfasdf");
	$args = array('post_type' => 'foxyshop_product', 'post_status' => 'publish', 'posts_per_page' => foxyshop_products_per_page(), 's' => $search, 'paged' => get_query_var('paged'));
	query_posts($args);
	if (!have_posts() & isset($_REQUEST['search'])) {
		echo '<p style="margin-top: 20px;">No products found.</p>';
	}
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

<script type="text/javascript">
jQuery(document).ready(function($){
	$(".foxyshop_product_list>li:nth-child(odd)").css("clear","left");
});
</script>

<?php get_footer(); ?>