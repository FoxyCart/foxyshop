/*
	FoxyCart Multiship Javascript
	v2.2
	2011-06-20

	INSTRUCTIONS:
	http://wiki.foxycart.com/integration/foxycart/multiship_javascript_070

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
	//if (jQuery.cookie('shipto_name_recent') != '') {
	//	jQuery('div.shipto_select select').selectOptions(jQuery.cookie('shipto_name_recent'));
	//} else {
		jQuery('div.shipto_select select').selectOptions('Me');
	//}

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
// NOTE: This does not yet work with multiple forms on the same page.
// If you need that, chime in on the FoxyCart forums: http://forum.foxycart.com
setCookie = function() {
	// Run any custom scripts you may have before dealing with,
	// or just insert your code here
	if (typeof fc_PreProcess_custom=="function") {
		fc_PreProcess_custom();
	}
	// Set the cookie
	var error = false;
	var shipto_cookie = jQuery.cookie('shipto_names');
	var shipto_new = jQuery('input[name^="shipto"]').eq(0).val();

	jQuery.cookie('shipto_name_recent', '', {expires: -1, path: '/', domain: location.host.match('[^.]+.[^.]+$')});
	jQuery.cookie('shipto_name_recent', shipto_new, {expires: 300, path: '/', domain: location.host.match('[^.]+.[^.]+$')});

	if ((shipto_new != 'undefined') && (shipto_new != 'null') && (shipto_new != 'me')) {
		jQuery.cookie('shipto_names', '', {expires: -1, path: '/', domain: location.host.match('[^.]+.[^.]+$')});
		jQuery.cookie('shipto_names', shipto_cookie + '||' + shipto_new, {expires: 300, path: '/', domain: location.host.match('[^.]+.[^.]+$')});
	}
	return true;
}

constructShipTo = function() {
	shipto_initialize();
	shipto_select();
}

fcc.events.cart.preprocess.add(setCookie);
fcc.events.cart.postprocess.add(constructShipTo);



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
/**
 * Cookie plugin
 *
 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */

/**
 * Create a cookie with the given name and value and other optional parameters.
 *
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Set the value of a cookie.
 * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
 * @desc Create a cookie with all available options.
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Create a session cookie.
 * @example $.cookie('the_cookie', null);
 * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
 *   used when the cookie was set.
 *
 * @param String name The name of the cookie.
 * @param String value The value of the cookie.
 * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
 * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
 * If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
 * If set to null or omitted, the cookie will be a session cookie and will not be retained
 * when the the browser exits.
 * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
 * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
 * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
 *require a secure protocol (like HTTPS).
 * @type undefined
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */

/**
 * Get the value of a cookie with the given name.
 *
 * @example $.cookie('the_cookie');
 * @desc Get the value of a cookie.
 *
 * @param String name The name of the cookie.
 * @return The value of the cookie.
 * @type String
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */
jQuery.cookie = function(name, value, options) {
if (typeof value != 'undefined') { // name and value given, set cookie
options = options || {};
if (value === null) {
value = '';
options.expires = -1;
}
var expires = '';
if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
var date;
if (typeof options.expires == 'number') {
date = new Date();
date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
} else {
date = options.expires;
}
expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
}
// CAUTION: Needed to parenthesize options.path and options.domain
// in the following expressions, otherwise they evaluate to undefined
// in the packed version for some reason...
var path = options.path ? '; path=' + (options.path) : '';
var domain = options.domain ? '; domain=' + (options.domain) : '';
var secure = options.secure ? '; secure' : '';
document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
} else { // only name given, get cookie
var cookieValue = null;
if (document.cookie && document.cookie != '') {
var cookies = document.cookie.split(';');
for (var i = 0; i < cookies.length; i++) {
var cookie = jQuery.trim(cookies[i]);
// Does this cookie string begin with the name we want?
if (cookie.substring(0, name.length + 1) == (name + '=')) {
cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
break;
}
}
}
return cookieValue;
}
};
// ============================================================================



// ============================================================================
// http://www.mathias-bank.de/2006/11/01/jquery-plugin-select-box-manipulation/
/*
 *
 * Copyright (c) 2006 Sam Collett (http://www.texotela.co.uk)
 * Licensed under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Addepted to select an option by Mathias Bank (http://www.mathias-bank.de)
 */

/*
 * Selects an option by value
 *
 * @name selectOptions
 * @author   Mathias Bank (http://www.mathias-bank.de)
 * @paramvalue specifies, which options should be selected
 * @example  jQuery("#myselect").selectOptions("val1");
 *
 */
jQuery.fn.selectOptions = function(value) {
	this.each(
		function()	{
			if(this.nodeName.toLowerCase() != "select") return;

			// get number of options
			var optionsLength = this.options.length;


			for(var i = 0; i<optionsLength; i++) {
				if (this.options[i].value == value) {
					this.options[i].selected = true;
				};
			}
		}
	)
	return this;
}
