<?php /*
------------ ATTENTION ------------
If you need to edit this template, do not edit the version in the plugin directory. Place a copy in your template folder and edit it there.
This will allow you to upgrade FoxyShop without breaking your customizations. More details here: http://www.foxy-shop.com/documentation/theme-customization/

Please Note: This is a placeholder. If you want to use a variation that includes an upload you need to install
the foxyshop-uploadify plugin found here: http://www.foxy-shop.com/foxyshop-uploadify.zip

-----------------------------------
*/

//Plugin Exists, Grab File From Here
if (file_exists(ABSPATH . "wp-content/plugins/foxyshop-uploadify/foxyshop-custom-upload.php")) {
	include ABSPATH . "wp-content/plugins/foxyshop-uploadify/foxyshop-custom-upload.php";

//Plugin Not Installed, Write Directions
} else {
	echo "In Order To Use FoxyShop's Variation Upload Functionality, Please Install the <a href=\"http://www.foxy-shop.com/foxyshop-uploadify.zip\">Upload Plugin</a>.";
}
