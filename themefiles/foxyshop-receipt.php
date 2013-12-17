<?php /*
------------ ATTENTION ------------
If you need to edit this template, do not edit the version in the plugin directory. Place a copy in your template folder and edit it there.
This will allow you to upgrade FoxyShop without breaking your customizations. More details here: http://www.foxy-shop.com/documentation/theme-customization/
-----------------------------------
*/


//----------------------------------------------
//Easy Config Section	
//----------------------------------------------
$show_email = true;
$show_phone = true;
$show_country = true;
$show_custom_fields = true;
$receipt_title = get_bloginfo("name");
//$receipt_title = '<img src="' . get_bloginfo("stylesheet_directory") . '/images/your_logo.png" alt="' . esc_attr(get_bloginfo("name")) . '" style="width: 190px;" />';
$date_style = "n/j/Y"; // Default Style 6/20/2012

//----------------------------------------------


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>"  />
<title><?php bloginfo('name'); ?> Invoice</title>
<style type="text/css">
body{
	background: #FFFFFF;
	font-size: 12pt;
}
.bold {
	font-weight:bold;
}
.clr {
	clear: both;
}
.invoice-title {
	font-size: 14pt;
	color:#734B3A;
	margin:25px 0 15px 0;
	font-family: Arial, Helvetica, sans-serif;
	text-transform: uppercase;
}
table {
	width: 100%;
}
th {
	text-align: left;
	border-bottom:1px solid #EEE;
}
.variation {
	font-size: 11px;
}
#main_container {
	width:100%;
	margin:0 0 30px 0;
}
#main {
	width:720px;
	margin:auto;
}
#main .header_logo {
	text-align: right;
	margin:15px 0 15px 0;
	font-size: 24px;
	font-weight: bold;
	color:#000000;
	font-family: Arial, Helvetica, sans-serif;
}
#main .wrapper {
	border:1px solid #EFECDC;
	background-color:#FFFFFF;
}
#main .wrapper-table {
	margin:0 0 0 0;
	padding:25px 25px 25px 25px;
	font-family:Arial, Helvetica, sans-serif;
}
.short_cell {
	width: 90px;
}

@media all {
	.page-break  { display:none; }
}

@media print {
	.page-break  { display:block; page-break-before:always; }
	.noprint { display: none; }
}
</style>

</head>
<body>

<?php

//Loop For Each Receipt Starts Here
foreach($xml->transactions->transaction as $transaction) {
	
	//Just Grab Some Values to Show Later
	$transaction_id = (string)$transaction->id;
	$transaction_date = (string)$transaction->transaction_date;
	$customer_first_name = (string)$transaction->customer_first_name;
	$customer_last_name = (string)$transaction->customer_last_name;
	$shipping_first_name = (string)$transaction->shipping_first_name;
	$shipping_last_name = (string)$transaction->shipping_last_name;
	$is_anonymous = (string)$transaction->is_anonymous;
	$customer_id = (string)$transaction->customer_id;
	$order_total = (double)$transaction->order_total;
	
?>
<div style="main_container">
<div id="main">

	<div class="header_logo"><?php echo $receipt_title; ?></div>

	<div class="clr"></div>

	<div class="wrapper">
	<div class="wrapper-table">
		<table cellpadding="0" cellspacing="5" border="0" style="margin-left:5px;">
			<tr>
				<td class="invoice-title">Receipt</td>
			</tr>

			<tr>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td><strong>Date:</strong> <?php echo Date($date_style, strtotime($transaction_date)); ?></td>
			</tr>
			<tr>
				<td><strong>Order Number:</strong> <?php echo $transaction_id; ?></td>
			</tr>
			<?php
			//Show Email or Phone if enabled in the easy config section
			if ($show_email) echo '<tr><td><strong>Email:</strong> ' . (string)$transaction->customer_email . '</td></tr>';
			if ($show_phone && (string)$transaction->customer_phone) echo '<tr><td><strong>Phone:</strong> ' . (string)$transaction->customer_phone . '</td></tr>';
			
			//Custom Fields (per order)
			if ($show_custom_fields) {
				foreach($transaction->custom_fields->custom_field as $custom_field) {
					if ($custom_field->custom_field_name != 'ga' && (int)$custom_field->custom_field_is_hidden == 0) {
						echo '<tr><td><strong>' . str_replace("_"," ",$custom_field->custom_field_name) . ':</strong> ' . nl2br((string)$custom_field->custom_field_value) . '</td></tr>';
					}
				}
			}
			?>
			<tr>
				<td>

				<table cellpadding="0" cellspacing="0" border="0"><tr>

				<td valign="top" style="padding-right: 20px;"><strong>Invoice Address:</strong><br />
				<?php
				//Show Invoice Address Section
				echo $customer_first_name . " " . $customer_last_name . "<br />";
				if ((string)$transaction->customer_company) echo $transaction->customer_company . "<br />";
				echo (string)$transaction->customer_address1 . "<br />";
				if ((string)$transaction->customer_address2) echo $transaction->customer_address2 . "<br />";
				echo (string)$transaction->customer_city . ', ' . (string)$transaction->customer_state . ' ' . (string)$transaction->customer_postal_code . '<br />';
				if ($show_country) echo (string)$transaction->customer_country . '<br />';
				echo "&nbsp;</td>\n\n";
				
				//Show Shipping Address If Entered
				if ((string)$transaction->shipping_address1 && !isset($transaction->shipto_addresses->shipto_address)) {
					echo '<td valign="top"><strong>Shipping Address:</strong><br />';
					echo $shipping_first_name . " " . $shipping_last_name . "<br />";
					if ((string)$transaction->shipping_company) echo $transaction->shipping_company . "<br />";
					echo (string)$transaction->shipping_address1 . "<br />";
					if ((string)$transaction->shipping_address2) echo $transaction->shipping_address2 . "<br />";
					echo (string)$transaction->shipping_city . ', ' . (string)$transaction->shipping_state . ' ' . (string)$transaction->shipping_postal_code . '<br />';
					if ($show_country) echo $transaction->shipping_country."<br />";
					if ($show_phone && (string)$transaction->shipping_phone) echo 'Phone: ' . $transaction->shipping_phone . "<br />";
					echo '&nbsp;</td>';
				}
				
				//Show Each Multi-ship Address If Entered
				foreach($transaction->shipto_addresses->shipto_address as $shipto_address) {
					echo '<td valign="top"><strong>Shipping Address (' . $shipto_address->address_name . '):</strong><br />';
					echo (string)$shipto_address->shipto_first_name . " " . (string)$shipto_address->shipto_last_name . "<br />";
					if ((string)$shipto_address->shipto_company) echo $shipto_address->shipto_company . "<br />";
					echo (string)$shipto_address->shipto_address1 . "<br />";
					if ((string)$shipto_address->shipto_address2) echo $shipto_address->shipto_address2 . "<br />";
					echo (string)$shipto_address->shipto_city . ', ' . (string)$shipto_address->shipto_state . ' ' . (string)$shipto_address->shipto_postal_code . '<br />';
					if ($show_country) echo $shipto_address->shipto_country."<br />";
					if ($show_phone && (string)$shipto_address->shipto_phone) echo $shipto_address->shipto_phone . "<br />";
					echo 'Method: ' . $shipto_address->shipto_shipping_service_description . "<br />";
					echo 'Shipping: ' . foxyshop_currency((double)$shipto_address->shipto_shipping_total) . "<br />";
					echo '&nbsp;</td>';
				}
				?>
				</tr></table>

				</td>
			</tr>
		</table>

		<table cellpadding="5" cellspacing="5" border="0" style="margin-top:30px;">
			<tr>
				<th>Product</td>
				<th class="short_cell">Price</td>
				<th class="short_cell">Qty</td>
				<th class="short_cell">Subtotal</td>
			</tr>

		<?php
		//Each Product In Order
		foreach($transaction->transaction_details->transaction_detail as $transaction_detail) {
			$product_code = (string)$transaction_detail->product_code;
			$product_name = (string)$transaction_detail->product_name;
			$product_price = (double)$transaction_detail->product_price;
			$product_quantity = (int)$transaction_detail->product_quantity;

			$product_discount = 0;
			foreach($transaction_detail->transaction_detail_options->transaction_detail_option as $transaction_detail_option) {
				$product_discount += (double)$transaction_detail_option->price_mod;
			}
			
			$product_price += $product_discount;

			?>
			<tr>
				<td><?php
				echo '<div>' . $product_name . '</div>';
				//If a subscription, show the subscription details
				if ((string)$transaction_detail->shipto != "") echo '<div class="variation">Ship To: ' . (string)$transaction_detail->shipto . '</div>';
				if ((string)$transaction_detail->subscription_frequency != "") {
					echo '<div class="variation">Subscription Frequency: ' . $transaction_detail->subscription_frequency . '</div>';
					echo '<div class="variation">Subscription Start Date: ' . $transaction_detail->subscription_startdate . '</div>';
					echo '<div class="variation">Subscription Next Date: ' . $transaction_detail->subscription_nextdate . '</div>';
					if ((string)$transaction_detail->subscription_enddate != "0000-00-00") echo '<div class="variation">Subscription End Date: ' . $transaction_detail->subscription_enddate . '</div>';
				}
				//These are the product Variations
				foreach($transaction_detail->transaction_detail_options->transaction_detail_option as $transaction_detail_option) {
					echo '<div class="variation">';
					echo str_replace("_", " ", (string)$transaction_detail_option->product_option_name) . ': ';
					echo (string)$transaction_detail_option->product_option_value;
					if ((string)$transaction_detail_option->price_mod != '0.000') {
						echo ' (' . (strpos("-",$transaction_detail_option->price_mod) !== false ? '-' : '+') . foxyshop_currency((double)$transaction_detail_option->price_mod) . ')';
					}
					echo '</div>';
				}
				?></td>
				<td class="short_cell"><?php echo foxyshop_currency($product_price); ?></td>
				<td class="short_cell"><?php echo $product_quantity; ?></td>
				<td class="short_cell"><?php echo foxyshop_currency($product_quantity * $product_price); ?></td>
			</tr>
			<?php
		}
			//Show the Totals
			?>
			<tr style="margin-top:30px;">
				<td>&nbsp;</td>
				<td colspan="2" align="right">Shipping:</td>
				<td class="short_cell bold"><?php echo foxyshop_currency((double)$transaction->shipping_total); ?></td>

			</tr>
			
			<?php
			//Taxes
			foreach($transaction->taxes->tax as $tax) {
				?>
				<tr>
					<td>&nbsp;</td>
					<td colspan="2" align="right"><?php echo (string)$tax->tax_name; ?>:</td>
					<td class="short_cell bold"><?php echo foxyshop_currency((double)$tax->tax_amount); ?></td>
				</tr>
				<?php
			}

			//Show Each Discount (if applicable)
			foreach($transaction->discounts->discount as $discount) {
				?>
				<tr>
					<td>&nbsp;</td>
					<td colspan="2" align="right"><?php echo (string)$discount->name; ?>:</td>
					<td class="short_cell bold"><?php echo foxyshop_currency((double)$discount->amount); ?></td>
				</tr>
				<?php
			}
			?>
			<tr>
				<td>&nbsp;</td>
				<td colspan="2" align="right">Total:</td>
				<td class="short_cell bold"><?php echo foxyshop_currency((double)$transaction->order_total); ?></td>
			</tr>
		</table>


	</div>
	</div>
	<?php
	//Show a Nice Shadow Image at the Bottom of The Table
	?>
	<div style="text-align:center;"><img src="<?php echo FOXYSHOP_DIR; ?>/images/paper-shadow.png" width="505" height="8" alt="shadow" /></div>

</div>
</div>
<div class="page-break"></div>
<?php
}
//End Loop
?>

<?php if (!isset($_GET['skip_print'])) : ?>
<script type="text/javascript">
//Comment Next Line If You Don't Want The Auto-Print Pop-Up
window.print();
</script>
<?php endif; ?>

</body>
</html>