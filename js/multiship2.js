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
	var shipto_new = jQuery(e.element).find('input[name^="shipto"]').eq(0).val();

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
 * jQuery Cookie Plugin v1.4.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2006, 2014 Klaus Hartl
 * Released under the MIT license
 */
(function(c){"function"===typeof define&&define.amd?define(["jquery"],c):"object"===typeof exports?c(require("jquery")):c(jQuery)})(function(c){function p(a){a=e.json?JSON.stringify(a):String(a);return e.raw?a:encodeURIComponent(a)}function n(a,g){var b;if(e.raw)b=a;else a:{var d=a;0===d.indexOf('"')&&(d=d.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\"));try{d=decodeURIComponent(d.replace(l," "));b=e.json?JSON.parse(d):d;break a}catch(h){}b=void 0}return c.isFunction(g)?g(b):b}var l=/\+/g,e=
c.cookie=function(a,g,b){if(1<arguments.length&&!c.isFunction(g)){b=c.extend({},e.defaults,b);if("number"===typeof b.expires){var d=b.expires,h=b.expires=new Date;h.setTime(+h+864E5*d)}return document.cookie=[e.raw?a:encodeURIComponent(a),"=",p(g),b.expires?"; expires="+b.expires.toUTCString():"",b.path?"; path="+b.path:"",b.domain?"; domain="+b.domain:"",b.secure?"; secure":""].join("")}for(var d=a?void 0:{},h=document.cookie?document.cookie.split("; "):[],m=0,l=h.length;m<l;m++){var f=h[m].split("="),
k;k=f.shift();k=e.raw?k:decodeURIComponent(k);f=f.join("=");if(a&&a===k){d=n(f,g);break}a||void 0===(f=n(f))||(d[k]=f)}return d};e.defaults={};c.removeCookie=function(a,e){if(void 0===c.cookie(a))return!1;c.cookie(a,"",c.extend({},e,{expires:-1}));return!c.cookie(a)}});
