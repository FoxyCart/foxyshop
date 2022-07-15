jQuery(document).ready(function($){
	if($(".foxyshop_product_list>li:nth-child(odd)").length) 
		$(".foxyshop_product_list>li:nth-child(odd)").css("clear","left");
	if($(".foxyshop_categories>li:nth-child(3n+1)").length)
		$(".foxyshop_categories>li:nth-child(3n+1)").css("clear","left");
});

	function foxyshop_sort_dropdown(el) {
		var current_url = document.location.href;
		var current_sort_key = el.options[el.selectedIndex].value;
		foxyshop_set_cookie('sort_key',current_sort_key,1);
		document.location.href = current_url.split('?')[0] + '?sort_key=' + current_sort_key;
	}
	function foxyshop_set_cookie(c_name,value,exdays) { var exdate=new Date();exdate.setDate(exdate.getDate() + exdays);var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString()) + '; path=/';document.cookie=c_name + "=" + c_value; }