function apiresetcheck() {
	if (confirm ("Are you sure you want to reset your API Key?\nYou will not be able to recover your old key.")) {
		return true;
	} else {
		return false;
	}
}
	function foxyshop_format_number(num) { num = num.toString().replace(/\$|\,/g,''); if(isNaN(num)) num = "0"; sign = (num == (num = Math.abs(num))); num = Math.floor(num*100+0.50000000001); cents = num%100; num = Math.floor(num/100).toString(); if(cents<10) cents = "0" + cents; for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++) num = num.substring(0,num.length-(4*i+3))+','+ num.substring(num.length-(4*i+3)); return (((sign)?'':'-') + num + '.' + cents); }
	function foxyshop_check_number(el) { el.value = foxyshop_format_number(el.value); }
	function foxyshop_format_number_single(num) { num = num.toString().replace(/\$|\,/g,''); if(isNaN(num)) num = "0"; sign = (num == (num = Math.abs(num))); num = Math.floor(num*100+0.50000000001); cents = num%100; num = Math.floor(num/100).toString(); if(cents<10) cents = "0" + cents; for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++) num = num.substring(0,num.length-(4*i+3))+','+ num.substring(num.length-(4*i+3)); return (((sign)?'':'-') + num); }

jQuery(document).ready(function($){
		if($("#product_variations_meta").length)
		$("#product_variations_meta").on("click", ".deleteVariation", function() {
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
		if($("#product_variations_meta").length)
	$("#product_variations_meta").on("blur", "input.variation_name", function() {
		var thisval = $(this).val().toLowerCase();
		if (thisval == "code" || thisval == "codes" || thisval == "price" || thisval == "name" || thisval == "category" || thisval == "weight" || thisval == "shipto") {
			alert("Sorry! The title '" + thisval + "' cannot be used as a variation name.");
			return false;
		}
	});

	//Check For Illegal Titles
		if($("#product_variations_meta").length)
	$("#product_variations_meta").on("keypress", "input.variation_name", function(e) {
		if (e.which !== 0 && (e.charCode == 46 || e.charCode == 34)) {
			alert("Sorry! You can't use this character in a variation name: " + String.fromCharCode(e.keyCode|e.charCode));
			return false;
		}
	});

	//On Change Listener
		if($("#product_variations_meta").length)
	$("#product_variations_meta").on("click", ".variationtype", function() {
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
		temp_hiddenfield = $("#_variation_hiddenfield_"+this_id).val();
		if (temp_dropdown) $("#dropdownradio_value_"+this_id).val(temp_dropdown);
		if (temp_radio) $("#dropdownradio_value_"+this_id).val(temp_radio);
		if (temp_text1) $("#text1_value_"+this_id).val(temp_text1);
		if (temp_text2) $("#text2_value_"+this_id).val(temp_text2);
		if (temp_textarea) $("#textarea_value_"+this_id).val(temp_textarea);
		if (temp_descriptionfield) $("#descriptionfield_value_"+this_id).val(temp_descriptionfield);
		if (temp_checkbox) $("#checkbox_value_"+this_id).val(temp_checkbox);
		if (temp_upload) $("#upload_value_"+this_id).val(temp_upload);
		if (temp_hiddenfield) $("#hiddenfield_value_"+this_id).val(temp_upload);

		//Set Contents in Container
		$("#variation_holder_"+this_id).html(getVariationContents(new_type, this_id));

		//Hide or Show Required Checkbox Option
		if (new_type == 'dropdown' || new_type == 'text' || new_type == 'textarea' || new_type == 'upload') {
			$(this).parents(".product_variation").find(".variation_required_container").show();
		} else {
			$(this).parents(".product_variation").find(".variation_required_container").hide();
			$(this).parents(".product_variation").find(".variation_required_container").find('input[type="checkbox"]').not(':checked');
		}


	});


	//New Variation
	if($("#AddVariation").length)
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
		new_content += '<label for="_variation_ref_name_' + this_id + '">'+jQuery('#referencename').text()+'</label>';
		new_content += '<input type="text" name="_variation_ref_name_' + this_id + '" class="variation_ref_name" id="_variation_ref_name_' + this_id + '" value="" />';
		new_content += '<span>Displayed in Dropdown Menu</span>';
		new_content += '</div>';
		new_content += '<div class="foxyshop_field_control">';
		new_content += '<label for="_variation_name_' + this_id + '">'+jQuery('#variationname').text()+'</label>';
		new_content += '<input type="text" name="_variation_name_' + this_id + '" class="variation_name" id="_variation_name_' + this_id + '" value="" />';
		new_content += '<label for="_variation_type_' + this_id + '" class="variationtypelabel">'+jQuery('#variationtype').text()+'</label> ';
		new_content += '<select name="_variation_type_' + this_id + '" id="_variation_type_' + this_id + '" class="variationtype">';
		new_content += jQuery('#hiddenoptions').html();
		new_content += '</select>';
		new_content += '</div>';
		new_content += '<div class="variation_holder" id="variation_holder_' + this_id + '"></div>';
		new_content += '<!-- //// DISPLAY KEY //// -->';
		new_content += '<div class="foxyshop_field_control dkeycontainer">';
		new_content += '<label class="dkeylabel" title="Enter a value here if you want your variation to be invisible until called by another variation.">'+jQuery('#displaykey').text()+'</label>';
		new_content += '<input type="text" name="_variation_dkey_' + this_id + '" id="_variation_dkey_' + this_id + '" value="" class="dkeynamefield" />';
		new_content += '<!-- Required -->';
		new_content += '<div class="variation_required_container" rel="' + this_id + '">';
		new_content += '<input type="checkbox" name="_variation_required_' + this_id + '" id="_variation_required_' + this_id + '" />';
		new_content += '<label for="_variation_required_' + this_id + '">'+jQuery('#makefieldrequired').text()+'</label>';
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
		variationkeyhtml = $('#variationkeytemplate').html();

		//Dropdown
		if (new_type == "dropdown") {
			new_contents = '<div class="foxyshop_field_control dropdown variationoptions">';
			new_contents += '<label id="_variation_value_' + this_id + '">'+jQuery('#itemsindropdown').text()+'</label>';
			new_contents += '<textarea name="_variation_value_' + this_id + '" id="_variation_value_' + this_id + '">' + $("#dropdownradio_value_"+this_id).val() + '</textarea>';
			new_contents += variationkeyhtml;
			new_contents += '</div>';

		//Radio Buttons
		} else if (new_type == "radio") {
			new_contents = '<div class="foxyshop_field_control radio variationoptions">';
			new_contents += '<label for="_variation_radio_' + this_id + '">'+jQuery('#radiobuttonoptions').text()+'</label>';
			new_contents += '<textarea name="_variation_radio_' + this_id + '" id="_variation_radio_' + this_id + '">' + $("#dropdownradio_value_"+this_id).val() + '</textarea>';
			new_contents += variationkeyhtml;
			new_contents += '</div>';

		//Text
		} else if (new_type == "text") {
			new_contents = '<div class="foxyshop_field_control text variationoptions">';
			new_contents += '<div class="foxyshop_field_control">';
			new_contents += '<label for="_variation_textsize1_' + this_id + '">'+jQuery('#textboxsize').text()+'</label>';
			new_contents += '<input type="text" name="_variation_textsize1_' + this_id + '" id="_variation_textsize1_' + this_id + '" value="' + $("#text1_value_"+this_id).val() + '" /> <span>'+jQuery('#characters').text()+'</span>';
			new_contents += '</div>';
			new_contents += '<div class="foxyshop_field_control">';
			new_contents += '<label for="_variation_textsize2_' + this_id + '">'+jQuery('#maximumchars').text()+'</label>';
			new_contents += '<input type="text" name="_variation_textsize2_' + this_id + '" id="_variation_textsize2_' + this_id + '" value="' + $("#text2_value_"+this_id).val() + '" /> <span>'+jQuery('#characters').text()+'</span>';
			new_contents += '</div>';
			new_contents += '<div style="clear: both;"></div>';
			new_contents += '</div>';

		//Textarea
		} else if (new_type == "textarea") {
			new_contents = '<div class="foxyshop_field_control textarea variationoptions">';
			new_contents += '<label for="_variation_textareasize_' + this_id + '">'+jQuery('#linesoftext').text()+'</label>';
			new_contents += '<input type="text" name="_variation_textareasize_' + this_id + '" id="_variation_textareasize_' + this_id + '" value="' + $("#textarea_value_"+this_id).val() + '" /> <span>('+jQuery('#defaultis').text()+' 3)</span>';
			new_contents += '</div>';


		//Description
		} else if (new_type == "descriptionfield") {
			new_contents = '<div class="foxyshop_field_control descriptionfield variationoptions">';
			new_contents += '<label for="_variation_description_' + this_id + '">'+jQuery('#descriptivetext').text()+'</label>';
			new_contents += '<textarea name="_variation_description_' + this_id + '" id="_variation_description_' + this_id + '">' + $("#descriptionfield_value_"+this_id).val() + '</textarea>';
			new_contents += '</div>';

		//Checkbox
		} else if (new_type == "checkbox") {
			new_contents = '<div class="foxyshop_field_control checkbox variationoptions">';
			new_contents += '<label for="_variation_description_' + this_id + '">'+jQuery('#value').text()+'</label>';
			new_contents += '<input type="text" name="_variation_checkbox_' + this_id + '" id="_variation_checkbox_' + this_id + '" value="' + $("#checkbox_value_"+this_id).val() + '" class="variation_checkbox_text" />';
			new_contents += variationkeyhtml;
			new_contents += '</div>';

		//Hidden Field
		} else if (new_type == "hiddenfield") {
			new_contents = '<div class="foxyshop_field_control hiddenfield variationoptions">';
			new_contents += '<label for="_variation_hiddenfield_' + this_id + '">Value</label>';
			new_contents += '<input type="text" name="_variation_hiddenfield_' + this_id + '" id="_variation_hiddenfield_' + this_id + '" value="' + $("#hiddenfield_value_"+this_id).val() + '" />';
			new_contents += '</div>';

		//Custom File Upload
		} else if (new_type == "upload") {
			new_contents = '<div class="foxyshop_field_control upload variationoptions">';
			new_contents += '<label for="_variation_uploadinstructions_' + this_id + '">'+jQuery('#instructions').text()+'</label>';
			new_contents += '<textarea name="_variation_uploadinstructions_' + this_id + '" id="_variation_uploadinstructions_' + this_id + '">' + $("#upload_value_"+this_id).val() + '</textarea>';
			new_contents += '</div>';
		}

		return new_contents;
	}



		if($("input[name='foxyshop_weight_type']").length)
	$("input[name='foxyshop_weight_type']").change(function() {
		if ($("#foxyshop_weight_type_english").is(":checked")) {
			$("#weight_title1").text("lbs");
			$("#weight_title2").text("oz");
		} else {
			$("#weight_title1").text("kg");
			$("#weight_title2").text("gm");
		}
	});

		if($("#foxyshop_weight_type_english").length)
	if ($("#foxyshop_weight_type_english").is(":checked")) {
		$("#weight_title1").text("lbs");
		$("#weight_title2").text("oz");
	} else {
		$("#weight_title1").text("kg");
		$("#weight_title2").text("gm");
	}

		if($("#foxyshop_key").length)
	$("#foxyshop_key").click(function(e) {
		if ($(this).prop("readonly")) {
			$(this).select();
		}
	});

		if($(".customise-api-key").length)
	$(".customise-api-key").click(function(e) {
		e.preventDefault();
		$("#foxyshop_key").prop("readonly", false);
		$(this).hide();
		$(".customise-api-key-save").show();
	});

		if($("#resetimage").length)
	$("#resetimage").click(function() {
		$("#foxyshop_default_image").val("<?php echo FOXYSHOP_DIR."/images/no-photo.png"; ?>");
		return false;
	});
		if($("#foxyshop_google_product_support").length)
	$("#foxyshop_google_product_support").click(function() {
		if ($(this).is(":checked")) {
			$("#google_merchant_id_holder").show();
		} else {
			$("#google_merchant_id_holder").hide();
		}
	});
		if($("#foxyshop_set_orderdesk_url").length)
	$("#foxyshop_set_orderdesk_url").click(function() {
		if ($(this).is(":checked")) {
			$("#orderdesk_url_holder").show();
			$("#foxyshop_orderdesk_url").select();
		} else {
			$("#orderdesk_url_holder").hide();
		}
	});

	//Tooltip
	xOffset = -10;
	yOffset = 10;
		if($("a.foxyshophelp").length)
	$("a.foxyshophelp").hover(function(e) {
		var tooltip_text = $(this).html();
		$("body").append("<p id='tooltip'>"+ tooltip_text +"</p>");
		$("#tooltip")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px")
			.fadeIn("fast");
	}, function(){
		$("#tooltip").remove();
	}).mousemove(function(e){
		$("#tooltip")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px");
	}).click(function(e) {
		e.preventDefault();
		return false;
	}).attr("tabindex", "99999");


		if($(".foxydomainpicker").length)
	$(".foxydomainpicker").click(function(e) {
		$(".foxycartdomain").removeClass("simple advanced");
		$(".foxycartdomain").addClass($(this).attr("rel"));
		$("#foxyshop_domain").focus().select();
		e.preventDefault();
		return false;
	});


		if($(".inventory_update_width").length)
	$(".inventory_update_width").blur(function() {
		current_field_id = $(this).attr("id");
		current_id = $("#" + current_field_id).attr("data-id");
		new_count = $("#" + current_field_id).val();

		$("#" + current_field_id).val(new_count);
		$("#" + current_field_id).parents("tr").removeClass("inventory_update_width_highlight");

		if (new_count != $("#original_count_" + current_id).val()) {

			var data = {
				action: 'save_inventory_values',
				"_wpnonce": thenonce,
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

		if($(".inventory_update_width").length)
	$(".inventory_update_width").keypress(function(e) {
		if (e.which == 13) {
			$(this).trigger("blur");
			return false;
		}
	});

		if($(".inventory_update_width").length)
	$(".inventory_update_width").focus(function() {
		$(this).parents("tr").addClass("inventory_update_width_highlight");
	});
	$("#inventory_level").tablesorter({
		'cssDesc': 'asc sorted',
		'cssAsc': 'desc sorted'
	});



		if($("#product_feed_view").length)
	$("#product_feed_view").tablesorter({
		'cssDesc': 'asc sorted',
		'cssAsc': 'desc sorted',
		'headers': { 0: { sorter: false} }
	});



		if($(".foxyshop_date_field").length)
			$(".foxyshop_date_field").datepicker({ dateFormat: 'yy-mm-dd' });

			
		if($("#foxyshop_searchform").length)
$("#foxyshop_searchform").on("click", "button", function() {
				if ($("#transaction_search_type option:selected").attr("target") == "_blank") {
					$("#foxyshop_searchform").attr("target","_blank");
				} else {
					$("#foxyshop_searchform").attr("target","_self");
				}
			});
			$(".tablenav a.disabled").click(function() {
				return false;
			});

if($("#foxyshop_dashboard_order_history")[0]){
$.post(ajaxurl, { action: 'foxyshop_order_history_dashboard_action', security: '<?php echo wp_create_nonce("foxyshop-order-info-dashboard"); ?>' }, function(response) {
			$("#foxyshop_dashboard_order_history").html(response)
		});
}

		if($(".foxyshop-list-table").length){
	$(".foxyshop-list-table").tablesorter({
			'cssDesc': 'asc sorted',
			'cssAsc': 'desc sorted'
		});

		$(".foxyshop-list-table thead th").click(function() {
			$("#foxyshop-list-inline .detail_holder").appendTo("#details_holder");
			$("#foxyshop-list-inline").remove();
		});
	}
	
		if($(".view_detail").length)
		$(".view_detail").click(function() {
			var id = $(this).parents("tr").attr("rel");

			if ($("#foxyshop-list-inline #holder_" + id).length > 0) {
				$("#foxyshop-list-inline .detail_holder").appendTo("#details_holder");
				$("#foxyshop-list-inline").remove();
			} else {
				$("#details_holder select").prop('selectedIndex', 0);
				$("#foxyshop-list-inline .detail_holder").appendTo("#details_holder");
				$("#foxyshop-list-inline").remove();

				$(this).parents("tr").after('<tr id="foxyshop-list-inline"><td colspan="7"></td></tr>');
				$("#holder_"+id).appendTo("#foxyshop-list-inline td");
			}

			return false;
		});
		if($(".detail_close").length)
		$(".detail_close").click(function() {
			$("#foxyshop-list-inline .detail_holder").appendTo("#details_holder");
			$("#foxyshop-list-inline").remove();
			return false;
		});

		if($(".subscription_save").length)
		$(".subscription_save").click(function() {
			var id = $(this).parents("form").children("input[name='sub_token']").val();
			$.post(ajaxurl, $(this).parents("form").serialize(), function(response) {

				$("#foxyshop-list-inline .detail_holder").appendTo("#details_holder");
				$("#foxyshop-list-inline").remove();

				if (response.indexOf("ERROR") < 0) {
					$("tr[rel='" + id + "']").css("background-color", "#FFFFE0").delay(500).animate({ backgroundColor: 'transparent' }, 500);
					if ($("#is_active_0_" + id).is(":checked")) {
						$("tr[rel='" + id + "'] td.customer_name strong").addClass("strikethrough");
					} else {
						$("tr[rel='" + id + "'] td.customer_name strong").removeClass("strikethrough");
					}
					$("tr[rel='" + id + "'] td.start_date").text($("#start_date" + id).val());
					$("tr[rel='" + id + "'] td.next_transaction_date").text($("#next_transaction_date_" + id).val());
					$("tr[rel='" + id + "'] td.end_date").text($("#end_date_" + id).val());
					$("tr[rel='" + id + "'] td.past_due_amount").text($("#past_due_amount_" + id).val());
					$("tr[rel='" + id + "'] td.frequency").text($("#frequency_" + id).val());
					if ($("#transaction_template_id_" + id).prop("selectedIndex") > 0) {
						$("tr[rel='" + id + "'] td.product_description").text($("#transaction_template_id_" + id + " option:selected").text());
					}
				} else {
					alert(response);
				}
			});
			return false;
		});


		if($(".foxydomainpicker").length)
$(".foxydomainpicker").click(function(e) {
		$(".foxycartdomain").removeClass("simple advanced");
		$(".foxycartdomain").addClass($(this).attr("rel"));
		$("#foxyshop_domain").focus().select();
		e.preventDefault();
		return false;
	});


		if($("#authnow").length)
							$("#authnow").click(function() {
								var data = {
									action: 'foxyshop_set_google_auth',
									security: '<?php echo wp_create_nonce("foxyshop-ajax-set-google-auth"); ?>',
									Email: $("#Email").val(),
									Passwd: $("#Passwd").val()
								};
								$("#error").hide();
								$.post(ajaxurl, data, function(response) {
									if (!response) {
										$("#error").text("Error: No Response").show();
									} else if (response == "Error") {
										$("#error").text("Error: Login Failed. Please try again.").show();
									} else if (response == "Success") {
										$("#error").hide();
										location.reload();
									} else {
										$("#error").text("Error: " + response).show();
									}
								});
							});
});
 