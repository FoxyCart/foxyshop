<?php 

/*
------------ ATTENTION ------------
If you need to edit this template, do not edit the version in the plugin directory. Place a copy in your template folder and edit it there.
This will allow you to upgrade FoxyShop without breaking your customizations. More details here: http://www.foxy-shop.com/documentation/theme-customization/
-----------------------------------
*/


//Set Globals and Get Settings
global $wpdb;
require(FOXYSHOP_PATH.'/prepaymentwebhookfunctions.php');

//TODO? EXTERNAL WEBHOOKS
//-----------------------------------------------------

//BUILT-IN FEATURES
//-----------------------------------------------------

$rawPost = file_get_contents('php://input');
$cart_details = json_decode($rawPost, true);
$local_test = false;

//TROUBLESHOOTING
//-----------------------------------------------------
// For testing, uncomment the following to write datafeed to file in theme folder so you can examine
// $file = STYLESHEETPATH.'/prepaymentwebhook.json';
// $fh = fopen($file, 'a') or die("Couldn't open $file for writing!");
// fwrite($fh, json_encode($cart_details));
// fclose($fh);

//-----------------------------------------------------

/* 
To test locally:
  1. Navigate to ../Tests folder within the plugin
  2. Copy the prepaymentwebhook_test.json file to your theme folder
  3. Modify the file to fit your test case
  4. Uncomment the following which will load the cart details from sample file
*/
// $path = STYLESHEETPATH.'/prepaymentwebhook_test.json';
// $jsonString = file_get_contents($path);
// $cart_details = json_decode($jsonString, true);
// var_dump($cart_details);
// $local_test = true;

//Uncomment These If You Need Help Troubleshooting
// error_reporting(E_ALL);
// ini_set('display_errors','On');

if ($cart_details) {
    // inventory check
    print json_encode(check_inventory($cart_details, $local_test, $wpdb));
    // add custom checks here
} else {
    echo("No payload found");
}
?>
