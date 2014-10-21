/*
	FoxyCart Multiship Javascript
	2014-09-25

	INSTRUCTIONS:
	http://wiki.foxycart.com/integration/foxycart/multiship_javascript_2

	IMPORTANT:
	If you're having problems with this script, MAKE SURE YOUR PAGE IS VALID.
	Seriously, if your page is invalid (particularly with regard to forms
	spanning beyond where they should, like starting in one <td> and going into
	another) this code might have issues.
*/

function shipto_initialize() {
	jQuery('div.shipto_select').show();
	jQuery('div.shipto_name').hide();
	jQuery('div.shipto_name input').val("");
}

// shipto_check checks for the existence of the shipto cookie
// returns an array of values, or false if no cookie found
function shipto_array() {
	if (jQuery.cookie('shipto_names')) {
		// Define the global shipto array
		var shipto_array = jQuery.cookie('shipto_names').split('||');
		shipto_array = unique(shipto_array);
		shipto_array.sort();
		return shipto_array;
	} else {
		return false;
	}
}

function shipto_select() {
	// Clear the shipto select boxes first
	jQuery('div.shipto_select select').html('');
	var shipto_options = '';
	var shipto = shipto_array();
	// alert('shipto: ' + shipto);
	if (shipto) {
		// alert('shipto is true');
		jQuery.each(shipto, function(i, val){
			// alert('starting the .each loop with: ' + i + ' = ' + val);
			if (val != 'undefined' && val != 'null' && val != '' && val != 'Me') {
				shipto_options += '<option value="' + val + '">' + val + '<\/option>';
				// alert('and it worked: ' + val);
			}
		});
		if (shipto_options != '') shipto_options += '<option value="">- - - - - - - - -<\/option>';
	}

	// Add the defaults...
	// This doesn't seem to work perfectly, as the selected="selected" gets ignored for some reason
	shipto_options += '<option value="add_new">Add a new recipient...<\/option>';
	shipto_options += '<option value="">- - - - - - - - -<\/option>';
	shipto_options += '<option selected="selected" value="Me">Yourself<\/option>';

	// Set the select boxes with the proper values
	jQuery('div.shipto_select select').html(shipto_options);

	// Now add the onchange event
	jQuery('div.shipto_select select').change(function(){
		if (jQuery(this).val() == 'add_new') {
			jQuery(this).parents('form').find('div.shipto_name').show();
			// Set any shipto values. Done this way in case you're adding more than one product at a time.
			jQuery(this).parents('form').find('input[name*="shipto"]').val('');
			// alert(jQuery('input[name^="shipto"]').attr('name') + jQuery('input[name^="shipto"]').val())
		} else {
			jQuery(this).parents('form').find('div.shipto_name').hide();
			// Set any shipto values. Done this way in case you're adding more than one product at a time.
			jQuery(this).parents('form').find('input[name*="shipto"]').val(jQuery(this).val());
		}
		// console.info(jQuery('input[name^="shipto"]').val());
		// console.info(jQuery('input[name^="2:shipto"]').val());
	});

	// Finally, select the last used shipto
	if (jQuery.cookie('shipto_name_recent') != '') {
		jQuery('div.shipto_select select').val(jQuery.cookie('shipto_name_recent')).change();
	} else {
		jQuery('div.shipto_select select').val('Me').change();
	}

	// console.info(jQuery('input[name*="shipto"]').val())
}

// Tie any additional product shipto's to the primary shipto
function shipto_multiples() {
	jQuery('input[name*="shipto"]').change(function(){
		jQuery(this).parents('form').find('input[name*="shipto"]').val(jQuery(this).val());
	});
}

// Set the events
jQuery(document).ready(function(){
	shipto_initialize();
	shipto_select();
	shipto_multiples();
});


// Set the cookie on cart add
setCookie = function(e) {
	// Run any custom scripts you may have before dealing with,
	// or just insert your code here
	if (typeof fc_PreProcess_custom=="function") {
		fc_PreProcess_custom();
	}
	// Set the cookie
	var error = false;
	var shipto_cookie = jQuery.cookie('shipto_names');
	var shipto_new = jQuery(e).find('input[name^="shipto"]').eq(0).val();

	jQuery.cookie('shipto_name_recent', '', {expires: -1, path: '/', domain: location.host.match('[^.]+.[^.]+$')});
	jQuery.cookie('shipto_name_recent', shipto_new, {expires: 300, path: '/', domain: location.host.match('[^.]+.[^.]+$')});

	if ((shipto_new != 'undefined') && (shipto_new != 'null') && (shipto_new != 'Me')) {
		jQuery.cookie('shipto_names', '', {expires: -1, path: '/', domain: location.host.match('[^.]+.[^.]+$')});
		jQuery.cookie('shipto_names', shipto_cookie + '||' + shipto_new, {expires: 300, path: '/', domain: location.host.match('[^.]+.[^.]+$')});
	}
	return true;
}

constructShipTo = function(e) {
	shipto_initialize();
	shipto_select();
}

var FC = FC || {};
FC.onLoad = function () {
	FC.client.on('cart-submit', setCookie);
	FC.client.on('cart-submit.done', constructShipTo);
};




// ============================================================================
/**
 * Removes duplicates in the array 'a'
 * @author Johan KÃ¤nngÃ¥rd, http://dev.kanngard.net
 */
function unique(a) {
	tmp = new Array(0);
	for(i=0;i<a.length;i++){
		if(!contains(tmp, a[i])){
			tmp.length+=1;
			tmp[tmp.length-1]=a[i];
		}
	}
	return tmp;
}
/**
 * Returns true if 's' is contained in the array 'a'
 * @author Johan KÃ¤nngÃ¥rd, http://dev.kanngard.net
 */
function contains(a, e) {
	for(j=0;j<a.length;j++)if(a[j]==e)return true;
	return false;
}
// ============================================================================



// ============================================================================
/*!
 * jQuery Cookie Plugin v1.3.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function(d){"function"===typeof define&&define.amd?define(["jquery"],d):d(jQuery)})(function(d){function m(a){return a}function n(a){return decodeURIComponent(a.replace(j," "))}function k(a){0===a.indexOf('"')&&(a=a.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\"));try{return e.json?JSON.parse(a):a}catch(c){}}var j=/\+/g,e=d.cookie=function(a,c,b){if(void 0!==c){b=d.extend({},e.defaults,b);if("number"===typeof b.expires){var g=b.expires,f=b.expires=new Date;f.setDate(f.getDate()+g)}c=e.json?
JSON.stringify(c):String(c);return document.cookie=[e.raw?a:encodeURIComponent(a),"=",e.raw?c:encodeURIComponent(c),b.expires?"; expires="+b.expires.toUTCString():"",b.path?"; path="+b.path:"",b.domain?"; domain="+b.domain:"",b.secure?"; secure":""].join("")}c=e.raw?m:n;b=document.cookie.split("; ");for(var g=a?void 0:{},f=0,j=b.length;f<j;f++){var h=b[f].split("="),l=c(h.shift()),h=c(h.join("="));if(a&&a===l){g=k(h);break}a||(g[l]=k(h))}return g};e.defaults={};d.removeCookie=function(a,c){return void 0!==
d.cookie(a)?(d.cookie(a,"",d.extend({},c,{expires:-1})),!0):!1}});
