<?php
defined('ABSPATH') or die("No access please!");

// Add custom order status to WooCommerce


function register_shipment_departure_order_status() {
	register_post_status( 'wc-shipped', array(
		'label'                     => 'Shipped',
		'public'                    => true,
		'show_in_admin_status_list' => true,
		'show_in_admin_all_list'    => true,
		'exclude_from_search'       => false,
		'label_count'               => _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>' )
	) );
 }


// Function to add custom order status "Ready for Pickup"
function add_custom_order_status_ready_for_pickup() {
    register_post_status('wc-ready-for-pickup', array(
        'label'                     => 'Ready for Pickup',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Ready for Pickup <span class="count">(%s)</span>', 'Ready for Pickup <span class="count">(%s)</span>')
    ));
}

// Function to add custom order status "Failed Delivery"
function add_custom_order_status_failed_delivery() {
    register_post_status('wc-failed-delivery', array(
        'label'                     => 'Failed Delivery',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Failed Delivery <span class="count">(%s)</span>', 'Failed Delivery <span class="count">(%s)</span>')
    ));
}

// Function to add custom order status "Returned"
function add_custom_order_status_returned() {
    register_post_status('wc-returned', array(
        'label'                     => 'Returned',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Returned <span class="count">(%s)</span>', 'Returned <span class="count">(%s)</span>')
    ));
}



// Function to add custom order statuses to dropdown

function add_awaiting_shipment_to_order_statuses( $order_statuses ) {
	$new_order_statuses = array();
	foreach ( $order_statuses as $key => $status ) {
		$new_order_statuses[ $key ] = $status;
		if ( 'wc-processing' === $key ) {
			$new_order_statuses['wc-shipped'] = 'Shipped';
			$new_order_statuses['wc-ready-for-pickup'] = 'Ready for Pickup';
			$new_order_statuses['wc-failed-delivery'] = 'Failed Delivery';
			$new_order_statuses['wc-returned'] = 'Returned';
		}
	}
	return $new_order_statuses;
}