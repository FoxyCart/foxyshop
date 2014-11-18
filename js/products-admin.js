jQuery(document).ready(function($){

	//Check For Illegal Code
	$(document).on("blur", "#_code", function() {
		var thisval = $(this).val();
		if (thisval.indexOf("&") > -1 || thisval.indexOf('"') > -1) {
			alert("Sorry! You can't use & or \" in the " + FOXYSHOP_PRODUCT_NAME_SINGULAR + " code.");
			return false;
		}
	});

	//Run When FoxyCart Category Changes
	$("#_category").change(function() {
		var current_shipping_type = $("#_category option:selected").attr("rel");
		if (current_shipping_type == "notshipped" || current_shipping_type == "downloaded") {
			$("#weight_disable").prop("checked", true).triggerHandler("click");
		}
		if (show_downloadables) {
			if (current_shipping_type == "downloaded" && !$("#_code").val()) {
				$("#downloadable_list_parent").show();
				$("#show_downloadable_list").hide();
			} else {
				$("#downloadable_list_parent").hide();
				$("#show_downloadable_list").show();
			}
		}
	});

	//Do the Show Downloadable List
	if (show_downloadables) {
		$("#ajax_get_downloadable_list").click(function() {
			var data = {
				action: 'foxyshop_ajax_get_downloadable_list',
				security: nonce_downloadable_list
			};
			$("#downloadable_list option").remove();
			$("#downloadable_list").append('<option value="">Refreshing Now...</option>' + "\n");
			$(this).addClass("waiting");
			$.post(ajaxurl, data, function(response) {
				if (response) {
					$("#downloadable_list option").remove();
					if (response) {
						$("#downloadable_list").append('<option value="">- - Select Below - -</option>' + "\n" + response);
					} else {
						$("#downloadable_list").append('<option value="">None Found</option>' + "\n" + response);
					}
				}
				$("#ajax_get_downloadable_list").removeClass("waiting");
			});

		});
		$("#show_downloadable_list").click(function(){
			$("#downloadable_list_parent").show();
			$(this).hide();
			return false;
		});
		$("#hide_downloadable_list").click(function(){
			$("#downloadable_list_parent").hide();
			$("#show_downloadable_list").show();
			return false;
		});
		$("#downloadable_list").change(function(){
			var sel = $("#downloadable_list option:selected");
			if (!sel.val()) return;
			$("#title").val(sel.text()).focus().blur();
			$("#_code").val(sel.val());
			$("#_price").val(sel.attr("product_price"));
			$("#_category option[value='" + sel.attr("category_code") + "']").prop("selected", true);
		});

	}


	//Refresh FoxyCart Categories
	$("#ajax_get_category_list_select").click(function() {
		var data = {
			action: 'foxyshop_ajax_get_category_list_select',
			security: nonce_downloadable_list
		};
		$("#_category option").remove();
		$("#_category").append('<option value="">Refreshing Now...</option>' + "\n");
		$(this).addClass("waiting");
		$.post(ajaxurl, data, function(response) {
			$("#_category option").remove();
			if (response) {
				$("#_category").append(response);
			} else {
				$("#_category").append('<option value="">None Found</option>' + "\n" + response);
			}
		});

	});

	//When Weight is Disabled
	$("#weight_disable").click(function() {
		if ($(this).is(":checked")) {
			$("#weight_disable_label").addClass("hide_color").removeClass("hide_gray");
			$("#_weight1").prop("disabled", true).val("");
			$("#_weight2").prop("disabled", true).val("");
		} else {
			$("#weight_disable_label").removeClass("hide_color").addClass("hide_gray");
			$("#_weight1").prop("disabled", false).val(defaultweight1);
			$("#_weight2").prop("disabled", false).val(defaultweight2);
		}
	});

	//When the bluring Weight 1
	$("#_weight1").blur(function() {
		var weight = $(this).val();
		if (weight.indexOf(".") >= 0) {
			secondstring = parseFloat(weight.substr(weight.indexOf("."))) * 100;
			result = secondstring * weight_dividend / 100;
			result = result.toFixed(1)
			$("#_weight2").val(result);
			foxyshop_check_number_single(this);
		}
		foxyshop_check_number_single(this);
	});

	//When the bluring Weight 2
	$("#_weight2").blur(function() {
		var weight = parseFloat($(this).val()).toFixed(1);
		if (weight == 'NaN') weight = "0.0";
		if (weight >= weight_dividend) {
			$("#_weight1").val(parseFloat(jQuery("#_weight1").val())+1);
			$("#_weight2").val("0.0");
		} else {
			$(this).val(weight);
		}
	});


	//Quantity Min/Max
	$("#_quantity_min, #_quantity_max").blur(function() {
		tempval = foxyshop_format_number_single($(this).val());
		if (tempval == "0") {
			$(this).val("");
		} else {
			$(this).val(tempval);
		}

	});
	$("#_quantity_min").bind("blur keyup", function() {
		if ($(this).val()) {
			$("#quantity_min_label").addClass("down_color").removeClass("down_gray");
		} else {
			$("#quantity_min_label").removeClass("down_color").addClass("down_gray");
		}
	});
	$("#_quantity_max").bind("blur keyup", function() {
		if ($(this).val()) {
			$("#quantity_max_label").addClass("up_color").removeClass("up_gray");
		} else {
			$("#quantity_max_label").removeClass("up_color").addClass("up_gray");
		}
	});

	$("#_quantity_hide").change(function() {
		if ($(this).is(":checked")) {
			$("#quantity_hide_label").addClass("hide_color").removeClass("hide_gray");
			$("#_quantity_min, #_quantity_max").prop("disabled", true);
		} else {
			$("#quantity_hide_label").removeClass("hide_color").addClass("hide_gray");
			$("#_quantity_min, #_quantity_max").prop("disabled", false);
		}
	});

	//Chosen Dropdown
	if (use_chozen) $(".chzn-select").chosen();



	//SALES
	//--------------------------------

	$("#_salestartdate, #_saleenddate").datepicker({ dateFormat: 'm/d/yy' });


	$("#_saleprice").blur(function() {
		saleprice = foxyshop_format_number($(this).val());
		if (saleprice == "0.00") {
			$(this).val("");
		} else {
			$(this).val(saleprice);
		}
	});


	$("#_code").blur(function() {
		if ($("#max_inventory_count").val() == 1 && !$("#inventory_code_1").val()) {
			$("#inventory_code_1").val($("#_code").val());
			addField(2);
		}
	});

	$(document).on("keyup", ".inventory_code, .inventory_count", function() {
		thisID = parseFloat($(this).attr("rel"));
		nextID = thisID + 1;
		if (parseFloat($("#max_inventory_count").val()) == thisID && $("#inventory_code_"+nextID).length == 0 && $("#inventory_code_"+thisID).val()) {
			addField(nextID);
		}
	});

	function addField(nextID) {
		$("#inventory_levels").append('<li><input type="text" id="inventory_code_' + nextID + '" name="inventory_code_' + nextID + '" value="" class="inventory_code" rel="' + nextID + '" style="width: 142px;" /><input type="text" id="inventory_count_' + nextID + '" name="inventory_count_' + nextID + '" value="" class="inventory_count" rel="' + nextID + '" /><input type="text" id="inventory_alert_' + nextID + '" name="inventory_alert_' + nextID + '" value="" class="inventory_count" rel="' + nextID + '" /></li>');
		$("#max_inventory_count").val(nextID);
	}


	$("#do_coupon").click(function() {
		if ($(this).is(":checked")) {
			$("#product_coupon_entry_field").show();
		} else {
			$("#product_coupon_entry_field").hide();
		}
	});




	//DISCOUNT STUFF
	//--------------------------------

	//On Load
	write_discount_type();
	rebuild_discount();

	//When Discount Type is Changed
	$("#discount_method").change(function() {
		var discount_method = $("#discount_method").val();

		$("#discount_levels input").each(function() {
			format_discount_values($(this));
		});

		write_discount_type();
	});

	//When levels are adjusted
	$(document).on("blur", "#discount_levels input", function() {
		var discount_method = $("#discount_method").val();
		format_discount_values($(this));
		compute_discount();
		check_for_new_discount_line();
	});
	$(document).on("keypress", "#discount_levels input", function(e) {
		if (e.keyCode == 13) {
			compute_discount();
			return true;
		}
	});

	//When Discount Name, Type are Changed
	$("#discount_name, #discount_type").change(function() {
		compute_discount();
	});

	//When Discount String is Manually Change
	$("#computed_discount").blur(function() {
		rebuild_discount();
	});



	function write_discount_type() {
		var discount_method = $("#discount_method").val();
		if (discount_method == "none") {
			$("#discount_container").hide();
		} else {
			if (discount_method == "discount_quantity_amount" || discount_method == "discount_quantity_percentage") {
				$(".prediscountlevel").text("");
			} else {
				$(".prediscountlevel").text("$");
			}
			if (discount_method == "discount_quantity_amount" || discount_method == "discount_price_amount") {
				$(".prediscountamount").text("$");
				$(".postdiscountamount").text("");
			} else {
				$(".prediscountamount").text("");
				$(".postdiscountamount").text("%");
			}
			$("#discount_container").show();
		}
	}



	function rebuild_discount() {
		var computed_discount = $("#computed_discount").val();
		var discount_method = $("#discount_method").val();

		//Discount Name
		var discount_name = computed_discount.split("{")[0];
		var discount_type = "";
		if (!discount_name) discount_name = "Discount";
		$("#discount_name").val(discount_name);

		//Discount Values
		$("#discount_levels li").remove();
		var discount_vals = computed_discount.substr(computed_discount.indexOf("{") + 1).replace("}","").split("|");

		//Makes Sure that first element of array is discount type
		if (discount_vals[0] != "allunits" && discount_vals[0] != "incremental" && discount_vals[0] != "repeat" && discount_vals[0] != "single") {
			discount_vals.unshift("allunits");
		}


		for (var i = 0; i < discount_vals.length; i++) {

			//Set Discount Type On First Array (if there)
			if (i == 0) {
				$("#discount_type").val(discount_vals[i]);
				continue;
			}


			if (discount_vals[i].indexOf("-") >= 0) {
				discountlevel1 = discount_vals[i].split('-')[0];
				discountamount = "-" + discount_vals[i].split("-")[1];
			} else {
				discountlevel1 = discount_vals[i].substr(0,discount_vals[i].indexOf("+"));
				discountamount = "+" + discount_vals[i].substr(discount_vals[i].indexOf("+") + 1);
			}

			//Write New Lines
			$("#discount_levels").append(write_discount_line(i, discountlevel1, discountamount, discount_method));
		}

		//Fill in second number
		$("#discount_levels li").each(function() {
			current_number = parseInt($(this).attr("rel"));
			next_number = current_number + 1;

			if ($('#discount' + next_number + 'a').length > 0) {
				next_val = $('#discount' + next_number + 'a').val();
				if (discount_method == "discount_quantity_amount" || discount_method == "discount_quantity_percentage") {
					discountlevel2 = next_val - 1;
				} else {
					discountlevel2 = next_val - .01;
				}
				$('#discount' + current_number + 'b').val(discountlevel2);
			} else {
				$('#discount' + current_number + 'b').val("x");
			}
		});

		write_discount_type();
	}

	function check_for_new_discount_line() {
		var discount_method = $("#discount_method").val();
		total_lines = $("#discount_levels li").length;
		total_last_boundary = parseFloat($("#discount" + total_lines + "b").val());
		if (total_last_boundary > 0) {
			if (discount_method == "discount_quantity_amount" || discount_method == "discount_quantity_percentage") {
				new_start_value = total_last_boundary + 1;
			} else {
				new_start_value = total_last_boundary + .01;
			}
			$("#discount_levels").append(write_discount_line(total_lines + 1, new_start_value, '', discount_method));
		}

	}

	function write_discount_line(num, discountlevel1, discountamount, discount_method) {
		discountamount = foxyshop_format_number(discountamount);
		if (discountamount >= 0) discountamount = "+" + discountamount;
		if (discount_method == "discount_quantity_amount" || discount_method == "discount_quantity_percentage") {
			discountlevel1 = foxyshop_format_number_single(discountlevel1);
		} else {
			discountlevel1 = foxyshop_format_number(discountlevel1);
		}

		var line = "<li rel=\"" + num + "\">\n";
		line += '<div class="prediscountlevel"></div>';
		line += '<input type="text" name="discount' + num + 'a" id="discount' + num + 'a" class="discountlevel1" value="' + discountlevel1 + '" />';
		line += '<div class="discountto">to</div>';
		line += '<div class="prediscountlevel"></div>';
		line += '<input type="text" name="discount' + num + 'b" id="discount' + num + 'b" class="discountlevel2" value="" />';
		line += '<div class="prediscountamount"></div>';
		line += '<input type="text" name="discount' + num + 'c" id="discount' + num + 'c" class="discountamount" value="' + discountamount+ '" />';
		line += '<div class="postdiscountamount"></div>';
		line += '<div style="clear:both;"></div>';
		line += "</li>\n";
		return line;
	}

	function compute_discount() {
		var computed_discount = "";

		computed_discount += $("#discount_name").val();
		computed_discount += "{";
		computed_discount += $("#discount_type").val();

		//Put All Vals Here
		$("#discount_levels li").each(function() {
			current_number = parseInt($(this).attr("rel"));
			computed_discount += "|" + $('#discount' + current_number + 'a').val() + $('#discount' + current_number + 'c').val();;

		});

		computed_discount += "}";

		$("#computed_discount").val(computed_discount);

	}

	function format_discount_values(el) {
		var currentnumber = el.val();
		var discount_method = $("#discount_method").val();

		//Amount
		if (el.hasClass("discountamount")) {
			newval = foxyshop_format_number(currentnumber);
			if (newval >= 0) newval = "+" + newval;
			el.val(newval);
		//Level
		} else if (currentnumber != "x" && currentnumber != "") {
			if (discount_method == "discount_quantity_amount" || discount_method == "discount_quantity_percentage") {
				el.val(foxyshop_format_number_single(currentnumber));
			} else {
				el.val(foxyshop_format_number(currentnumber));
			}
		}
	}


	//IMAGES
	//--------------------------------
	$(document).on("click", "#foxyshop_product_image_list .foxyshop_image_rename", function() {
		var thisID = $(this).attr("rel");
		$(".renamediv").removeClass('rename_active');
		$("#renamediv_" + thisID).addClass('rename_active');
		document.getElementById('rename_' + thisID).select();
		renameLive = true;
		return false;
	});

	$("form").bind("keypress", function(e) {
		if (e.keyCode == 13 && renameLive) {
			return false;
		}
	});

	$(document).on("keyup blur", "#foxyshop_product_image_list input", function(e) {
		var thisID = $(this).attr("rel");
		var newTitle = $(this).val();
		if (e.keyCode == 27) {
			$("#renamediv_" + thisID).removeClass('rename_active');
			renameLive = false;
		} else if (e.keyCode == 13) {
			var data = {
				action: 'foxyshop_product_ajax_action',
				security: nonce_images,
				foxyshop_action: 'rename_image',
				foxyshop_new_name: newTitle,
				foxyshop_image_id: thisID,
				foxyshop_product_id: post_id
			};
			$.post(ajaxurl, data, function() {
				$("#renamediv_" + thisID).removeClass('rename_active');
				$("#att_" + thisID + " img").attr("alt",newTitle + ' (' + thisID + ')').attr("title",newTitle + ' (' + thisID + ')');
				renameLive = false;
			});
		}
		return false;
	});


	$(document).on("click", "#foxyshop_product_image_list .foxyshop_image_delete", function() {
		var data = {
			action: 'foxyshop_product_ajax_action',
			security: nonce_images,
			foxyshop_action: 'delete_image',
			foxyshop_image_id: $(this).attr("rel"),
			foxyshop_product_id: post_id
		};
		$("#foxyshop_image_waiter").show();
		$.post(ajaxurl, data, function(response) {
			$("#foxyshop_product_image_list").html(response);
			$("#foxyshop_image_waiter").hide();
		});
		return false;
	});

	$(document).on("click", "#foxyshop_product_image_list .foxyshop_image_featured", function() {
		var data = {
			action: 'foxyshop_product_ajax_action',
			security: nonce_images,
			foxyshop_action: 'featured_image',
			foxyshop_image_id: $(this).attr("rel"),
			foxyshop_product_id: post_id
		};
		$("#foxyshop_image_waiter").show();
		$.post(ajaxurl, data, function(response) {
			$("#foxyshop_product_image_list").html(response);
			$("#foxyshop_image_waiter").hide();
		});
		return false;
	});

	$(document).on("click", "#foxyshop_product_image_list .foxyshop_visible", function() {
		var data = {
			action: 'foxyshop_product_ajax_action',
			security: nonce_images,
			foxyshop_action: 'toggle_visible',
			foxyshop_image_id: $(this).attr("rel"),
			foxyshop_product_id: post_id
		};
		$("#foxyshop_image_waiter").show();
		$.post(ajaxurl, data, function(response) {
			$("#foxyshop_product_image_list").html(response);
			$("#foxyshop_image_waiter").hide();
		});
		return false;
	});

	$('#foxyshop_new_product_image').show().each(function() {
		var variationID = $(this).attr("rel");
		$(this).uploadify({
			uploader  : FOXYSHOP_DIR + '/js/uploadify/uploadify.swf',
			script    : bloginfo_url + FOXYSHOP_URL_BASE + '/upload-' + datafeed_url_key + '/',
			cancelImg : FOXYSHOP_DIR + '/js/uploadify/cancel.png',
			auto      : true,
			buttonImg	: FOXYSHOP_DIR + '/images/add-new-image.png',
			width     : '132',
			height    : '23',
			scriptData: {
				'foxyshop_image_uploader':'1',
				'foxyshop_product_id': post_id
			},
			sizeLimit : foxyshop_max_upload,
			onComplete: function(event,queueID,fileObj,response,data) {
					var data = {
						'action': 'foxyshop_product_ajax_action',
						'security': nonce_images,
						'foxyshop_product_id': post_id,
						'foxyshop_action': 'add_new_image'
					};

					$("#foxyshop_image_waiter").show();
					$.post(ajaxurl, data, function(response) {
						$("#foxyshop_product_image_list").html(response)
						$("#foxyshop_image_waiter").hide();
					});
			}
		});
	});

	$("#foxyshop_product_image_list").sortable({
		placeholder: "sortable-placeholder",
		revert: false,
		tolerance: "pointer",
		update: function() {
			$("#foxyshop_sortable_value").val($("#foxyshop_product_image_list").sortable("toArray"));
			var data = {
				action: 'foxyshop_product_ajax_action',
				security: nonce_images,
				foxyshop_action: 'update_image_order',
				foxyshop_order_array: $("#foxyshop_sortable_value").val(),
				foxyshop_product_id: post_id
			};
			$("#foxyshop_image_waiter").show();
			$.post(ajaxurl, data, function(response) {
				$("#foxyshop_product_image_list").html(response)
				$("#foxyshop_image_waiter").hide();
			});
		}
	});



	//VARIATIONS
	//--------------------------------

	$("#product_variations_meta").before('<a name="product_variations_meta"></a>');
	$("#VariationMinimizeAll").click(function() {
		$("#product_variations_meta").addClass("variation_minimized");
		$("#VariationMaximizeAll").show();
		$(this).hide();
		$('html,body').animate({scrollTop: $("#product_variations_meta").offset().top - 30},'fast');
	});
	$("#VariationMaximizeAll").click(function() {
		$("#product_variations_meta").removeClass("variation_minimized");
		$(".product_variation").css("cursor", "cursor");
		$("#VariationMinimizeAll").show();
		$(this).hide();
	});

	$(document).on("click", ".deleteVariation", function() {
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
	$(document).on("blur", "input.variation_name", function() {
		var thisval = $(this).val().toLowerCase();
		if (thisval == "code" || thisval == "codes" || thisval == "price" || thisval == "name" || thisval == "category" || thisval == "weight" || thisval == "shipto") {
			alert("Sorry! The title '" + thisval + "' cannot be used as a variation name.");
			return false;
		}
	});

	//Check For Illegal Titles
	$(document).on("keypress", "input.variation_name", function(e) {
		if (e.which !== 0 && (e.charCode == 46 || e.charCode == 34)) {
			alert("Sorry! You can't use this character in a variation name: " + String.fromCharCode(e.keyCode|e.charCode));
			return false;
		}
	});

	//On Change Listener
	$(document).on("change", ".variationtype", function() {
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
		temp_hidden = $("#_variation_hiddenfield_"+this_id).val();
		if (temp_dropdown) $("#dropdownradio_value_"+this_id).val(temp_dropdown);
		if (temp_radio) $("#dropdownradio_value_"+this_id).val(temp_radio);
		if (temp_text1) $("#text1_value_"+this_id).val(temp_text1);
		if (temp_text2) $("#text2_value_"+this_id).val(temp_text2);
		if (temp_textarea) $("#textarea_value_"+this_id).val(temp_textarea);
		if (temp_descriptionfield) $("#descriptionfield_value_"+this_id).val(temp_descriptionfield);
		if (temp_checkbox) $("#checkbox_value_"+this_id).val(temp_checkbox);
		if (temp_upload) $("#upload_value_"+this_id).val(temp_upload);
		if (temp_hidden) $("#hiddenfield_value_"+this_id).val(temp_upload);

		//Set Contents in Container
		$("#variation_holder_"+this_id).html(getVariationContents(new_type, this_id));

		//Hide or Show Required Checkbox Option
		if (new_type == 'dropdown' || new_type == 'text' || new_type == 'textarea' || new_type == 'upload' || new_type == 'checkbox') {
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
		new_content += '<label for="_variation_name_' + this_id + '">Variation Name</label>';
		new_content += '<input type="text" name="_variation_name_' + this_id + '" class="variation_name" id="_variation_name_' + this_id + '" value="" />';
		new_content += '<label for="_variation_type_' + this_id + '" class="variationtypelabel">Variation Type</label> ';
		new_content += '<select name="_variation_type_' + this_id + '" id="_variation_type_' + this_id + '" class="variationtype">';
		new_content += variation_select_options;
		new_content += '</select>';
		new_content += '</div>';
		new_content += '<div class="variation_holder" id="variation_holder_' + this_id + '"></div>';
		new_content += '<!-- //// DISPLAY KEY //// -->';
		new_content += '<div class="foxyshop_field_control dkeycontainer">';
		new_content += '<label class="dkeylabel" title="Enter a value here if you want your variation to be invisible until called by another variation.">Display Key</label>';
		new_content += '<input type="text" name="_variation_dkey_' + this_id + '" id="_variation_dkey_' + this_id + '" value="" class="dkeynamefield" />';
		new_content += '<!-- Required -->';
		new_content += '<div class="variation_required_container" rel="' + this_id + '">';
		new_content += '<input type="checkbox" name="_variation_required_' + this_id + '" id="_variation_required_' + this_id + '" />';
		new_content += '<label for="_variation_required_' + this_id + '">Make Field Required</label>';
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
		variationkeyhtml = '<div class="variationkey">' + variation_key + '</div>';
		$(".product_variation[rel='" + this_id + "'] .dkeycontainer").show();

		//Dropdown
		if (new_type == "dropdown") {
			new_contents = '<div class="foxyshop_field_control dropdown variationoptions">';
			new_contents += '<label id="_variation_value_' + this_id + '">Items in Dropdown</label>';
			new_contents += '<textarea name="_variation_value_' + this_id + '" id="_variation_value_' + this_id + '">' + $("#dropdownradio_value_"+this_id).val() + '</textarea>';
			new_contents += variationkeyhtml;
			new_contents += '</div>';

		//Radio Buttons
		} else if (new_type == "radio") {
			new_contents = '<div class="foxyshop_field_control radio variationoptions">';
			new_contents += '<label for="_variation_radio_' + this_id + '">Radio Button Options</label>';
			new_contents += '<textarea name="_variation_radio_' + this_id + '" id="_variation_radio_' + this_id + '">' + $("#dropdownradio_value_"+this_id).val() + '</textarea>';
			new_contents += variationkeyhtml;
			new_contents += '</div>';

		//Text
		} else if (new_type == "text") {
			new_contents = '<div class="foxyshop_field_control text variationoptions">';
			new_contents += '<div class="foxyshop_field_control">';
			new_contents += '<label for="_variation_textsize1_' + this_id + '">Text Box Size</label>';
			new_contents += '<input type="text" name="_variation_textsize1_' + this_id + '" id="_variation_textsize1_' + this_id + '" value="' + $("#text1_value_"+this_id).val() + '" /> characters';
			new_contents += '</div>';
			new_contents += '<div class="foxyshop_field_control">';
			new_contents += '<label for="_variation_textsize2_' + this_id + '">Maximum Chars</label>';
			new_contents += '<input type="text" name="_variation_textsize2_' + this_id + '" id="_variation_textsize2_' + this_id + '" value="' + $("#text2_value_"+this_id).val() + '" /> characters';
			new_contents += '</div>';
			new_contents += '<div style="clear: both;"></div>';
			new_contents += '</div>';

		//Textarea
		} else if (new_type == "textarea") {
			new_contents = '<div class="foxyshop_field_control textarea variationoptions">';
			new_contents += '<label for="_variation_textareasize_' + this_id + '">Lines of Text</label>';
			new_contents += '<input type="text" name="_variation_textareasize_' + this_id + '" id="_variation_textareasize_' + this_id + '" value="' + $("#textarea_value_"+this_id).val() + '" /> (default is 3)';
			new_contents += '</div>';


		//Description
		} else if (new_type == "descriptionfield") {
			new_contents = '<div class="foxyshop_field_control descriptionfield variationoptions">';
			new_contents += '<label for="_variation_description_' + this_id + '">Descriptive Text</label>';
			new_contents += '<textarea name="_variation_description_' + this_id + '" id="_variation_description_' + this_id + '">' + $("#descriptionfield_value_"+this_id).val() + '</textarea>';
			new_contents += '</div>';

		//Checkbox
		} else if (new_type == "checkbox") {
			new_contents = '<div class="foxyshop_field_control checkbox variationoptions">';
			new_contents += '<label for="_variation_description_' + this_id + '">Value</label>';
			new_contents += '<input type="text" name="_variation_checkbox_' + this_id + '" id="_variation_checkbox_' + this_id + '" value="' + $("#checkbox_value_"+this_id).val() + '" class="variation_checkbox_text" />';
			new_contents += variationkeyhtml;
			new_contents += '</div>';

		//Custom File Upload
		} else if (new_type == "upload") {
			new_contents = '<div class="foxyshop_field_control upload variationoptions">';
			new_contents += '<label for="_variation_uploadinstructions_' + this_id + '">Instructions</label>';
			new_contents += '<textarea name="_variation_uploadinstructions_' + this_id + '" id="_variation_uploadinstructions_' + this_id + '">' + $("#upload_value_"+this_id).val() + '</textarea>';
			new_contents += '</div>';

		//Hidden Field
		} else if (new_type == "hiddenfield") {
			new_contents = '<div class="foxyshop_field_control hiddenfield variationoptions">';
			new_contents += '<label for="_variation_hiddenfield_' + this_id + '">Value</label>';
			new_contents += '<input type="text" name="_variation_hiddenfield_' + this_id + '" id="_variation_hiddenfield_' + this_id + '" value="' + $("#hiddenfield_value_"+this_id).val() + '" />';
			new_contents += '</div>';

		//Saved Variation
		} else {
			new_contents = '<p class="foxyshop_saved_variation"><em>This varation will use saved settings.</em></p>';
			if (!$("#_variation_name_" + this_id).val()) $("#_variation_name_" + this_id).val($('#_variation_type_' + this_id + ' option:selected').attr("rel"));
			$(".product_variation[rel='" + this_id + "'] .dkeycontainer").hide();
		}


		return new_contents;
	}


});
function foxyshop_format_number_single(num) { num = num.toString().replace(/\$|\,/g,''); if(isNaN(num)) num = "0"; sign = (num == (num = Math.abs(num))); num = Math.floor(num*100+0.50000000001); cents = num%100; num = Math.floor(num/100).toString(); if(cents<10) cents = "0" + cents; for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++) num = num.substring(0,num.length-(4*i+3))+','+ num.substring(num.length-(4*i+3)); return (((sign)?'':'-') + num); }
function foxyshop_format_number(num) { num = num.toString().replace(/\$|\,/g,''); if(isNaN(num)) num = "0"; sign = (num == (num = Math.abs(num))); num = Math.floor(num*100+0.50000000001); cents = num%100; num = Math.floor(num/100).toString(); if(cents<10) cents = "0" + cents; for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++) num = num.substring(0,num.length-(4*i+3))+','+ num.substring(num.length-(4*i+3)); return (((sign)?'':'-') + num + '.' + cents); }
function foxyshop_check_number_single(el) { el.value = foxyshop_format_number_single(el.value); }
function foxyshop_check_number(el) { el.value = foxyshop_format_number(el.value); }
