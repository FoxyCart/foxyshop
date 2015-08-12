=== FoxyShop ===
Contributors: sparkweb
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2AHG2QMABF8SG
Tags: foxycart, shopping, cart, inventory, management, ecommerce, selling, subscription, foxy
Requires at least: 3.1
Tested up to: 4.3
Stable tag: 4.6.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
FoxyShop provides a robust shopping cart and inventory management tool for use with FoxyCart's hosted e-commerce solution.

== Description ==

FoxyShop is a complete shopping cart solution for WordPress. This plugin connects to [FoxyCart's]((http://affiliate.foxycart.com/idevaffiliate.php?id=211)) hosted shopping cart service and will allow you to manage your inventory from a WordPress backend. FoxyShop was built to make the integration of FoxyCart and WordPress a breeze. Products are easy to manage and the flexible templates make it easy for developers to quickly build their storefront. The FoxyShop plugin is exhaustively documented, actively maintained, and completely free. And it's foxy, too.

Visit [foxy-shop.com](http://www.foxy-shop.com/) for full documentation and instructions.

[youtube http://www.youtube.com/watch?v=HkS-J3XTGIk]

= Just a Few of the Many FoxyShop Features: =

* Fully customizable theme files and CSS
* Unlimited images per product with popup slideshow or zooming
* Widget support for featured categories
* Manage product inventory within the WordPress admin
* Set up product categories and subcategories
* Drag-and-drop product sorting
* Complete flexibility for product variations and pricing
* Sale pricing with optional date controls
* Digital products and subscriptions
* Allow WordPress users to checkout with their account
* Flexible product and category discounts
* Multiple shipping recipients
* Inventory management
* Internationalization support
* Field validation to prevent form tampering
* Lots more... [See Complete Feature List!](http://www.foxy-shop.com/foxyshop-features/)

= Translations Available =
* Norwegian (Kenneth from [KKTrends](http://kktrend.no/))
* German (Andrei from [PixelDarkroom](http://www.pixeldarkroom.com/))


== Installation ==

Copy the folder to your WordPress
'*/wp-content/plugins/*' folder.

1. Activate the '*FoxyShop*' plugin in your WordPress admin '*Plugins*'
1. Go to '*Products / Manage Settings*' in your WordPress admin.
1. Enter your FoxyCart domain.
1. Copy and paste the supplied API key into your FoxyCart admin area (Advanced) and check the "enable verification" checkbox.
1. All other settings are optional. See [Docs](http://www.foxy-shop.com/documentation/installation-instructions/) for more details and a setup video.

== Frequently Asked Questions ==

There's a thorough FAQ section located at [http://www.foxy-shop.com/faq/](http://www.foxy-shop.com/faq/).


== Screenshots ==

1. Admin Settings
2. Product Management
3. Custom Product Order
4. Order Management
5. Inventory Levels


== Changelog ==

= 4.6.2 =
* WP 4.3 compatibility changes with widget constructor
* SSO endpoint now listens for update/cancellation and doesn't query cart in those cases

= 4.6.1 =
* Update PrettyPhoto to 3.1.6
* Update Order Desk Link on Orders page
* Removing unneeded queries when setting up images

= 4.6 =
* Adding "expired" feature - use a number (minutes) or a date (Y-m-d in Pacific time)
* Adding Reverse SSO functionality - log your customers into WordPress after checkout
* Connecting featured images that weren't uploaded to the product
* The dashboard widget now shows all orders for the month instead of just the first 300
* A custom image size called "featured" won't take over the featured image functionality any more
* Fix to make sure that hidden required checkboxes aren't required
* Adding filters to disable custom category sorting and provide your own custom sorting

= 4.5.3 =
* Adding Magnific as the new default lightbox
* Removing loader.js from wp_footer if requested by settings
* Adding filter to allow file processing images
* Upgrading to jQuery 1.11.2
* Fixing numbers with commas when setting inventory
* SSO endpoint now listens for proper JSON on FC version 2

= 4.5.2 =
* Updating the multiship javascript for FoxyCart 2.0
* Custom sorting honors hidden category children preferences
* Improving product add-on functionality

= 4.5.1 =
* Making FoxyCart 2.0 the Default
* Updating checkout/receipt templates for 2.0
* Added demographics option for Analytics
* SSO - WordPress password resets now get saved back to FoxyCart
* Ensure only is_anonymous=0 transactions update WordPress users
* Switch to FoxyCart's loader.js
* Added filter for category terms
* Removed a console.log statement
* Adding 'public static' to the encryption fuction for better compatibility
* Display PO# if order is a Purchase Order

= 4.5 =
* Added FoxyCart 2.0 support
* Added true bundled product support
* Google Analytics Universal functionality
* Custom product sorting by category
* Adding full and medium size image type filters
* Updating max_quantity calculation, removing hashes
* Updating most fields to remove tags to solve validation issues
* Open validation for bundled product images
* Adding filters for adjusting all prices and variations
* Adding filter for default wp user role
* Adding filter for Google Analytics ga.src
* Adding filter for custom bundled product fields
* Fixed the SSO password problem when adding new WordPress accounts
* Changing mysql_real_escape_string() to esc_sql()
* Allowing price changes to still be enforced with v: variation modifier
* Allowing id attribute when specifying 'showproduct' shortcode
* Updated to jQuery 1.11.1
* See [Release Notes](http://www.foxy-shop.com/2014/08/version-4-5-foxycart-2-0-support/) for more details

= 4.4.4 =
* Changing the Manage Inventory page to use ajax-based saving
* Trim individual variation lines
* Updating styles for WordPress 3.8
* Adding GTIN, MPN field matching for Google Products (thanks to Scott Daniels)

= 4.4.3 =
* Updating inventory now covers multiple products with same code
* Add-on product images are now properly skipping validation
* Add-on products are not displayed if they are not in stock
* Changed multiship script to use "Me" instead of "me" to avoid potentially different shipto names
* Allow alternate named sub_startdate and sub_enddate to set a dynamic strtotime date

= 4.4.2 =
* Fixing a missing " that was keeping the image field from working properly

= 4.4.1 =
* Improved WP_Error handling with FoxyCart API
* Start using wp_redirect()
* Fix for missing validation on variation values that are only "0"
* Added 'foxyshop_social_media_header' handle for custom social media headers
* Made the JavaScript UTF-8 checker more flexible
* Updates SSO account updating to properly update WordPress passwords
* Updated external jQuery reference to 1.10.2

= 4.4 =
* Added a default checkout and receipt template
* Allow the fr:X variation modifier to adjust the sub_frequency
* Checkbox variations can now be set as required
* Added FOXYSHOP_DECIMAL_PLACES definition to allow setups that need three decimal places
* Google feeds can now be exported in multiple pages/batches and the feed page is limited to 100 unmatched products at a time
* Updated admin js calls to use .on() instead of the deprecated .live()
* Image field is now set to --OPEN-- validation so that W3 Cache doesn't break it
* Future line item products shouldn't get processed by inventory
* Set description variation field to process shortcodes
* Added datafeed loop detection to the Order Desk redirect
* Added filters for the no-stock inventory message
* max_quantity now honored when dealing with low inventory
* Changed cURL to wp_remote_post()
* Check for is_ssl() when building FoxyShop admin links
* Fixed the jQuery dequeue feature
* Upgraded to jQuery 1.10.1 for stores <= FoxyCart 1.0
* Upgraded to prettyPhoto 3.1.5
* Upgraded to jQuery 1.10.3 (used for Date Picker)
* Default FoxyCart version is now 1.1
* Added German translation

= 4.3.2 =
* Added some extra variation features to allow custom values and field names
* Added missing radio title dkey class
* Added FoxyCart 1.1 option
* Added 'foxyshop_inventory_update' action

= 4.3.1 =
* Added easier category syncing for FoxyCart 0.7.2+
* Fixed spacing issue with WP 3.5
* Fix to remove the `quantity_max` field when backordering is allowed
* Show warning if curl is not installed
* Moved the template cache functionality up so that scrolling isn't required as often on the tools page

= 4.3 =
* Added native support for cart, empty, and coupon settings at the product level
* Added support for hidden field product variations
* Set FoxyCart version 1.0 as default
* Updated to jQuery 1.8.2
* Updated to jQuery UI 1.9.1
* Reverted jQuery UI theme to Smoothness (from Lightness in FoxyShop 4.2.1)
* Fixed double-encoding in foreign currency in JavaScript context
* Fix for apostrophes in saved variation titles
* Include and require functions now use absolute paths
* Security: added checks to make sure that any FoxyShop php pages can't be run directly
* Fix for missing alert values on imported inventory records
* Fix for missing quantity_min and quantity_max values on the add to cart link
* Fix for inventory error generated when there's no product code
* Fix to make sure that add to cart form can't be submitted if submit button is disabled

[View Archived Changelog](http://www.foxy-shop.com/changelog-archives/)


== Upgrade Notice ==

= 4.6.2 =
WP_Widget Fix For WordPress 4.3 Compatibility
