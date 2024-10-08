<?php
//Exit if not called in proper context
if (!defined('ABSPATH')) exit();

function check_inventory($cart_details, $local_test, $wpdb) {
    $response = array(
        'ok' => true,
        'details' => ''
    );

    $log_file = './log.txt';
    $date = new DateTime();
    $date_string = $date->format('Y-m-d H:i:s');
     
    $log_line = $date_string . ': ' . $cart_details['customer_ip'] . ' - '. $cart_details['_embedded']['fx:customer']['email'] . ' -- ';

    $no_stock_products = array();
    $limited_stock_products = array();

    foreach($cart_details['_embedded']['fx:items'] as $item) {
        $product_id = -1;
        $code = $item['code'];

        $inv_code = "%" . $wpdb->esc_like($code) . "%";
        $sql = $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value LIKE %s", '_inventory_levels', $inv_code );
        $meta_list = $wpdb->get_results( $sql );

        foreach ($meta_list as $meta) {
            $product_id = $meta->post_id;
            $val = unserialize($meta->meta_value);
            if (!is_array($val)) $val = array();
            foreach ($val as $ivcode => $iv) {
                if ($ivcode == $code) {
                    $inventory = $iv['count'];
                    if ($inventory === 0) {
                        array_push($no_stock_products, $item['name']);
                    }
                    else if ($item['quantity'] > $inventory) {
                        array_push($limited_stock_products, $item['name']);
                    }
                    break;
                }
            }
        }
    }

    $response_text = "";

    if (count($no_stock_products) > 0) {
        $response_text = "Sorry, we are currently out of stock of the following " . ((count($no_stock_products) > 1) ? "items" : "item") . ": " . join(", ", $no_stock_products) . ". Please remove " . ((count($no_stock_products) > 1) ? "them" : "it") . " from your cart and try again.";
    }

    if (count($limited_stock_products) > 0) {
        if ($response_text) {
            $response_text .= "/n";
        }
        $response_text .= "Sorry, we currently have only limited stock of the following " . ((count($limited_stock_products) > 1) ? "items" : "item") . ": " . join(", ", $limited_stock_products) . ". Please reduce the quantity of " . ((count($limited_stock_products) > 1) ? "them" : "it") . " in your cart and try again.";
    }

    if ($response_text) {
        $response['ok'] = false;
        $response['details'] = $response_text; 
    }

	$log_line .= (string) $response['ok'] . "\n";
	$fp = fopen($log_file, 'a');
	fwrite($fp, $log_line);

	if ($local_test === false) {
	header('Content-Type: application/json');
	}
	return $response;
}
?>
