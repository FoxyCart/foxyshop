=== FoxyShop ===
Contributors: sparkweb
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2AHG2QMABF8SG
Tags: foxycart, shopping, cart, inventory, management, ecommerce, selling, subscription, foxy
Requires at least: 3.1
Tested up to: 4.1
Stable tag: 4.5.1
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

= 4.2.1 =
* Added support for multiple dkeys split by a comma: {dkey:key1,key2}
* Bugfix for radio/checkbox elements whose values were hidden after being hidden by dkey
* Updated variations for better multiship support on repeat purchases
* Updated to jQuery 1.8
* Updated to jQuery UI 1.8.23
* Added security option if you'd like to disable user uploading: define('FOXYSHOP_DISABLE_USER_UPLOAD', 1);
* Order snapshot on admin dashboard now shows up to 300 orders instead of 50 (more accurate numbers)
* Added a few more product variation container filters
* Fixed missing discounts in order_total for CSV export

= 4.2 =
* Updated variation processor to allow multiple levels of dkeys
* Updated variation processor to add x: to hidden text inputs and textarea elements
* Updated prettyPhoto gallery to have product ID in rel so multiple galleries on category page won't overlap
* Updated out of stock messages to be js arrays for multiple values on one page
* Updated Google Product Feed feature to add CDATA to content field and allow for customizable target country and currency via filters
* Allow saved variations to have custom names
* Added settings option for disabling cart validation
* Added a filter for the post type registration argument array
* Added filter for disabling auto-login when new user account created
* Updated hidden ikey images to be written out but hidden so cloudzoom will work properly.
* Improved inventory update matching to only update published products, not those in trash
* Fix for double UTF8 encoding on foxyshop_currency()
* Fix for error in customer email variable in subscription datafeed
* Fix to accept custom field criteria in CSV export feature
* Fix for CSV export not showing proper amount for quantities greater than one
* Fix for the FOXYSHOP_PRODUCT_TAGS feature

= 4.1.6 =
* Correction for subscription datafeed processing. Emails could be sent to wrong customer.

= 4.1.5 =
* Added FoxyShop version 1.0 support
* Added 'foxyshop-current-category class' to the category list function
* Added '$foxyshop_skip_cart_image' variable so the cart image can be easily skipped (globally set this in functions.php)
* Allow subscription post data to be passed through to external datafeeds
* Added setting to enable email reminders to expiring credit card customers
* Improved is_foxyshop()
* Fixed the "required file upload" feature to trigger an error if no file is uploaded
* Fixed Google Analytics script so that it won't be inserted on skipped template pages (Checkout, Receipt)
* See [Release Notes](http://www.foxy-shop.com/2012/06/version-4-1-5-catching-up-on-updates/) for more details

= 4.1.4 =
* Added setting for automatic FoxyTools Order Desk integration
* Added feature to add ikey images that don't show up in slideshow
* Introduced is_foxyshop() function as a conditional tag which returns true for all FoxyShop pages
* Tested with WordPress 3.4 and found no issues
* Added action to SSO endpoint to allow interception
* Upgraded to prettyPhoto 3.1.4
* License changed to GPLv2 or later as recommended by WordPress
* Changed to get_user_by in datafeed functions because of deprecated function in WordPress 3.3
* Filter variation names for invalid characters on CSV import upgrade
* Fixed incorrect query match in the inventory update function when all numbers are used for code

= 4.1.3 =
* Updated jQuery to version 1.7.2
* Internationalization updates. Added Norwegian translation thanks to Kenneth from KKtrend.
* Added 'foxyshop_breadcrumbs_base_link' filter
* Upgraded template redirect function to check for post names as well as page names
* Removed unneeded "is foxyshop installed" checks from template files
* Added settings page sniffers for Thesis and Headway users
* Fixed bug where subsequent cloud zoom displays were "large" not "full" size images
* Fixed potential warning in templateredirect.php for foxyshop_body_class() with missing array
* Fixed notice appearing on some installs when no orders returned
* Fixed missing default dropdown on first unsaved variation (tools page)

= 4.1.2 =
* Fixed broken receipt link on order management page

= 4.1.1 =
* New option on settings page to show "Add to Cart" links on product entry page
* Fixed variation help key on initial admin product page load
* Setup page now defaults to simple FoxyCart domain entry type
* Display line breaks on custom fields in order display
* Redundant shipping address hidden from multiship stores
* Fixed rogue quote in form tag, helperfunctions.php
* Fixed variable error in customized foxyshop quantity wrapper

= 4.1 =
* Added Order Export in CSV format (single ship stores only)
* Added native FoxyCart ColorBox slideshow functionality option. Works best in 0.7.2+
* Sped up admin product page load by skipping javascript and loading images natively on first load and by caching and condensing a lot of jQuery
* Removed UPS export option in settings - it will always show up on the View Orders page in the new dropdown
* Added an argument to the foxyshop_setup_product() function to speed up the inventory page load
* Added 'foxyshop_main_menu_name' filter so you can customize the main admin menu title
* Moved the jQuery insert from 'init' to 'wp_enqueue_scripts' so it initializes later
* The foxyshop_setup_product() function was upgraded to accept product ID's and slugs as well as $post objects
* Added optional append argument to the foxyshop_save_attribute() helper function
* Added filters for the order/customer/subscription search defaults
* Bugfix: Related products were matching an add-on product, not the main parent
* Bugfix: Search page template was loading the foxyshop header instead of footer at end of file
* See [Release Notes](http://www.foxy-shop.com/2012/02/version-4-1-export-csv-transactions/) for more details

= 4.0 =
* Transaction, Customer, and Subscription attributes can now be managed within FoxyShop (0.7.2+)
* The discount entry interface has been rebuilt with a more natural entry option
* Dropdown variations can now click the Required checkbox if they want the first option in the dropdown to be an invalid choice
* Instead of one tax total, all taxes are now displayed by name on admin order page and default receipt
* Adjusted item price and weight variations on admin order page now show accurately
* Cloud-Zoom updated for better mobile device support and removed slideshow if just one image returned
* Improved Google Product Feed available product matching method
* Added hook for updating inventory alert email destination
* Subscriptions now show all products in the subscription, not just the first one
* Fixed settings link on plugins page
* Menu name changed from "Products" to "FoxyShop" for easier recognition
* Removed jQuery from the enqueue if FoxyShop includes skipped
* Subscription management urls now have empty=true in them to ensure an easier update experience
* Updated fallback FoxyCart Colorbox CSS to version 3.1.18 (for 0.7.2 users)
* Added two recommended plugins to tools page for user logins
* Made the FoxyCart domain entry box on the settings and setup pages a little more self-explanatory
* WordPress 3.0 support has been removed. Do not upgrade if you are still using WordPress 3.0!
* See [Release Notes](http://www.foxy-shop.com/2012/01/version-4-0-something-special/) for more details

= 3.9 =
* Added cloud-zoom image slideshow support
* Added feature to skip FoxyCart includes on some or all pages
* Added filters for all role-based permission pages: you can now set custom roles for plugin access
* Added action hooks to fire after transactions have been archived
* Added filter for product slug within product setup (aids dynamic rewrite strings)
* Added filter for description field variation
* Changed default theme files so that FoxyShop header and footer files are outside of the foxyshop_container element
* Shipping category renamed "FoxyCart Category" to avoid confusion. Allow default category to load so that the category type can be set.
* Images uploaded while product is untitled will now be called "Image" instead of "Auto Draft"
* Added warnings so that the & and " characters can't be saved in the product code
* Bugfix: Minimum quantities that were entered without a maximum quantity weren't being saved
* Bugfix: Expired Google Products authentication now correctly prompts for renewal
* See [Release Notes](http://www.foxy-shop.com/2012/01/version-3-9-image-zooming-and-more/) for more details

= 3.8 =
* Updating price in bulk now lets you update dynamically with +, -, or by percentage.
* Added a helper function for updating inventory levels
* Added ability to show more than 50 orders per page for 0.7.1+ users with FOXYSHOP_API_ENTRIES_PER_PAGE constant
* Improved paging navigation for API processes (transactions, customers, subscriptions)
* Added an "Archive All" option for the Manage Orders screen
* Inventory codes can now be forced from the inventory import system even if they haven't been added before
* Inventory connectors available for QuickBooks (through ConsoliBYTE) and SmartTurn
* curl connection error now displays actual error
* Transaction receipts outside the default date filter now viewable
* Added MinFraud score (0.7.2+) to transaction details
* Many datafeed template improvements
* "No Weight" can now be set as a system default if your products don't use weight
* Added a filter so that the date format can be adjusted on order page
* Admin nag bars are now limited to admins only
* Fixed jQuery error (variations) which was appearing in iOS 4
* Fixed some errors in the subscription datafeed process
* See [Release Notes](http://www.foxy-shop.com/2011/12/version-3-8-inventory-and-api-updates/) for more details

= 3.7.1 =
* Fixed a PHP error in the default theme file causing the datafeed to fail

= 3.7 =
* New: UPS WorldShip Integration
* New: Manage your Google Products directly from the FoxyShop admin
* Added built-in Google Product Feed fields
* Manage Google Product Feed with the Customer Field Bulk Editor
* Added 0.7.2 feature: sync with FoxyCart's list of downloadables for easier product entry
* Updated to jQuery 1.7.1
* FoxyShop Import/Export tool now includes saved variations
* Fix: reset post data after related product WP_Query loop
* Fix: uninstall issue and potential missing datafeed key on new installs
* See [Release Notes](http://www.foxy-shop.com/2011/11/version-3-7-google-products-integration-ups-worldship/) for more details

= 3.6.1 =
* Product sitemap is now a dynamic url + fixed namespace issue
* Added 0.7.2 feature to automatically pull your shipping category list from FoxyCart
* Added 0.7.2 receipt template caching functionality
* Updated to jQuery 1.7.0
* Made some admin styling tweaks
* See [Release Notes](http://www.foxy-shop.com/2011/11/version-3-6-1-dynamic-sitemaps/) for more details

= 3.6 =
* Important: Double serialization corrected!
* Updated datafeed so that FoxyShop processes are now protected within functions
* Added filters for the related products areas
* Added "Minimize" button to product variations to help manage when there are many
* Added 0.7.2 custom field search support to orders, subscriptions, and customers
* Added transaction template change support for subscriptions
* Subscription detail view now updated immediately after saving in admin
* Bugfix: Bundled text link wasn't adding url with numeric prefix
* Ampersand not allowed in product variation name. Doesn't work with validation.
* See [Release Notes](http://www.foxy-shop.com/2011/11/version-3-6-no-more-double-serialization/) for more details

[View Archived Changelog](http://www.foxy-shop.com/changelog-archives/)


== Upgrade Notice ==

= 4.5.2 =
Updating Multiship JS for FoxyCart 2.0
