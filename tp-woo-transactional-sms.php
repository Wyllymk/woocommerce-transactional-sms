<?php
defined('ABSPATH') or die("No access please!");

/* Plugin Name: Tp WooCommerce MOBILESASA SMS
* Plugin URI: https://github.com/Wyllymk/Woocommerce-Bulk-SMS
* Description: MOBILE SASA Transactional SMS for WooCommerce.
* Version: 2.0
* Author: Wilson Devops
* Author URI: https://wilsondevops.com
* Licence: GPLv2
* WC requires at least: 7.4
* WC tested up to: 8.3.1
*/


// Include the file containing the custom order status function
require_once plugin_dir_path(__FILE__) . 'include/tp-woocommerce-custom-order-status.php';

// Call each function to register the custom order statuses
add_action('init', 'add_custom_order_status_shipped');
add_action('init', 'add_custom_order_status_ready_for_pickup');
add_action('init', 'add_custom_order_status_failed_delivery');
add_action('init', 'add_custom_order_status_returned');

// Add an action hook to add custom order status to dropdown
add_action('wc_order_statuses', 'add_awaiting_shipment_to_order_statuses');

//Initialize the plugin
add_action('plugins_loaded', 'woo_bulk_sms_init', 0);

function woo_bulk_sms_init(){

	if(!class_exists('woocommerce')) return;
	class tp_bulksms_plugin {
		function __construct(){
			$this->tp_init_hooks();
		}
		
		//Init
		function tp_init_hooks(){
			add_action('before_woocommerce_init',function(){ //Declaring compatibility
				if(class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class)){
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables',__FILE__,true);
				}
			});
			add_filter('woocommerce_get_sections_advanced', array($this,"wc_bulk_sms"));
			add_filter('woocommerce_get_settings_advanced', array($this,"wc_bulk_sms_settings"),10,2);
			add_action('woocommerce_order_status_changed', array($this,"tp_order_status"),10,3);
			// Hook into order status changes
			add_action( 'woocommerce_store_api_checkout_update_order_meta', array($this, 'wc_track_order_draft_duration') );
			// Hook into the cron event to send the SMS
			add_action( 'send_draft_order_sms', array($this,'send_draft_order_sms_callback'), 10, 1 );
			// Hook the function to run when WordPress initializes
			add_action('init', array($this, 'schedule_delete_custom_post_meta'));
			// Hook the function to the scheduled event
			add_action('delete_custom_post_meta_event', array($this, 'delete_custom_post_meta'));
			
		}

		//Wc sections
		function wc_bulk_sms($sections){
			$sections['wctpbulksms'] = __('MOBILE SASA Transactional SMS','wordpress');
			return $sections;
		}
		
		function wc_bulk_sms_settings($settings, $current_section){
			if ($current_section == 'wctpbulksms'){
				$tp_sms_settings = array();
				$tp_sms_settings[] = array('name'=>__('MOBILE SASA Bulk SMS Settings','wordpress'),'type'=>'title','id'=>'wctpbulksms');
				$tp_sms_settings[] = array('name'=>__('Enable/Disable','wordpress'),'id'=>'wctpbulksms_enable','type'=>'checkbox','desc'=>__('Enable','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Sender ID','wordpress'),'id'=>'wctpbulksms_senderid','type'=>'text','placeholder'=>'e.g MOBILESASA');
				$tp_sms_settings[] = array('name'=>__('Api Token','wordpress'),'id'=>'wctpbulksms_apitoken','type'=>'text');
				$tp_sms_settings[] = array('name'=>__('Admin Number','wordpress'),'id'=>'wctpbulksms_adminnumber','type'=>'text','desc'=>__('Admin Number will receive a text on every order placed','wordpress'), 'placeholder'=>'e.g 0729123456, 0729123456');
				$tp_sms_settings[] = array('name'=>__('Receive Admin SMS','wordpress'),'id'=>'wctpbulksms_ordernewadmin','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Admin Placed Order SMS','wordpress'),'id'=>'wctpbulksms_ordernewadminsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello Admin, {name} has placed an order #{orderid}','wordpress'));

				$tp_sms_settings[] = array('name'=>__('Order Draft','wordpress'),'id'=>'wctpbulksms_orderdraft','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Draft SMS','wordpress'),'id'=>'wctpbulksms_orderdraftsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, please continue with your order #{orderid}','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Pending Payment','wordpress'),'id'=>'wctpbulksms_orderpending','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Pending Payment SMS','wordpress'),'id'=>'wctpbulksms_orderpendingsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, we have received your order #{orderid} please finish payment','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order On-Hold','wordpress'),'id'=>'wctpbulksms_orderhold','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order On Hold SMS','wordpress'),'id'=>'wctpbulksms_orderholdsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, your order #{orderid} is on hold pending payment confirmation','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Processing','wordpress'),'id'=>'wctpbulksms_ordernew','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Processing SMS','wordpress'),'id'=>'wctpbulksms_ordernewsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, we have received your order #{orderid}','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Completed','wordpress'),'id'=>'wctpbulksms_ordercomplete','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Completed SMS','wordpress'),'id'=>'wctpbulksms_ordercompletesms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, we have shipped your order #{orderid}','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Cancelled','wordpress'),'id'=>'wctpbulksms_ordercancelled','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Cancelled SMS','wordpress'),'id'=>'wctpbulksms_ordercancelledsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, we have cancelled your order #{orderid}','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Failed','wordpress'),'id'=>'wctpbulksms_orderfailed','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Failed SMS','wordpress'),'id'=>'wctpbulksms_orderfailedsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, your order #{orderid} has failed payment','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Shipped','wordpress'),'id'=>'wctpbulksms_ordershipped','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Shipped SMS','wordpress'),'id'=>'wctpbulksms_ordershippedsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, your order #{orderid} has been shipped','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Ready for Pickup','wordpress'),'id'=>'wctpbulksms_orderreadypickup','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Ready for Pickup SMS','wordpress'),'id'=>'wctpbulksms_orderreadypickupsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, your order #{orderid} is ready for pickup','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Failed Delivery','wordpress'),'id'=>'wctpbulksms_orderfaileddelivery','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Failed Delivery SMS','wordpress'),'id'=>'wctpbulksms_orderfaileddeliverysms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, your order #{orderid} has failed delivery','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Returned','wordpress'),'id'=>'wctpbulksms_orderreturned','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Returned SMS','wordpress'),'id'=>'wctpbulksms_orderreturnedsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, your order #{orderid} has been returned','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Refunded','wordpress'),'id'=>'wctpbulksms_orderrefunded','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Refunded SMS','wordpress'),'id'=>'wctpbulksms_orderrefundedsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, your order #{orderid} has been refunded','wordpress'));
				$tp_sms_settings[] = array('type'=>'sectionend','id'=>'wctpbulksms');
				return $tp_sms_settings;
			} else return $settings;
		}
		
		//Order status
		function tp_order_status($orderID, $old_status, $new_status){
			$this->tp_bulksms = get_option('wctpbulksms_enable');
			if($this->tp_bulksms && $this->tp_bulksms=='yes'){			
				$this->tp_senderid = get_option('wctpbulksms_senderid');
				$this->tp_apitoken = get_option('wctpbulksms_apitoken');
				$this->tp_adminnumber = get_option('wctpbulksms_adminnumber');
				$this->tp_on_adminreceive = get_option('wctpbulksms_ordernewadmin');
				$this->tp_sms_adminreceive = get_option('wctpbulksms_ordernewadminsms');

				$this->tp_on_orderpending = get_option('wctpbulksms_orderpending');
				$this->tp_sms_orderpending = get_option('wctpbulksms_orderpendingsms');
				$this->tp_on_orderhold = get_option('wctpbulksms_orderhold');
				$this->tp_sms_orderhold = get_option('wctpbulksms_orderholdsms');
				$this->tp_on_ordernew = get_option('wctpbulksms_ordernew');
				$this->tp_sms_ordernew = get_option('wctpbulksms_ordernewsms');
				$this->tp_on_ordercomplete = get_option('wctpbulksms_ordercomplete');
				$this->tp_sms_ordercomplete = get_option('wctpbulksms_ordercompletesms');
				$this->tp_on_ordercancelled = get_option('wctpbulksms_ordercancelled');
				$this->tp_sms_ordercancelled = get_option('wctpbulksms_ordercancelledsms');
				$this->tp_on_orderrefunded = get_option('wctpbulksms_orderrefunded');
				$this->tp_sms_orderrefunded = get_option('wctpbulksms_orderrefundedsms');
				$this->tp_on_orderfailed = get_option('wctpbulksms_orderfailed');
				$this->tp_sms_orderfailed = get_option('wctpbulksms_orderfailedsms');
				$this->tp_on_ordershipped = get_option('wctpbulksms_ordershipped');
				$this->tp_sms_ordershipped = get_option('wctpbulksms_ordershippedsms');
				$this->tp_on_orderreadypickup = get_option('wctpbulksms_orderreadypickup');
				$this->tp_sms_orderreadypickup = get_option('wctpbulksms_orderreadypickupsms');
				$this->tp_on_orderfaileddelivery = get_option('wctpbulksms_orderfaileddelivery');
				$this->tp_sms_orderfaileddelivery = get_option('wctpbulksms_orderfaileddeliverysms');
				$this->tp_on_orderreturned = get_option('wctpbulksms_orderreturned');
				$this->tp_sms_orderreturned = get_option('wctpbulksms_orderreturnedsms');
				
				//Order details
				global $woocommerce;
				$order = new WC_Order($orderID);

				if ($this->tp_on_adminreceive=='yes'){
					$msgAdmin = $this->tp_sms_adminreceive;
				}

				if ($new_status == 'pending') {
					$msg = $this->tp_sms_orderpending;
				} elseif ($new_status == 'on-hold') {
					$msg = $this->tp_sms_orderhold;
				} elseif ($new_status == 'processing') {
					$msg = $this->tp_sms_ordernew;
				} elseif ($new_status == 'completed') {
					$msg = $this->tp_sms_ordercomplete;
				} elseif ($new_status == 'cancelled') {
					$msg = $this->tp_sms_ordercancelled;
				} elseif ($new_status == 'refunded') {
					$msg = $this->tp_sms_orderrefunded;
				} elseif ($new_status == 'failed') {
					$msg = $this->tp_sms_orderfailed;
				} elseif ($new_status == 'shipped') {
					$msg = $this->tp_sms_ordershipped;
				} elseif ($new_status == 'ready-for-pickup') {
					$msg = $this->tp_sms_orderreadypickup;
				} elseif ($new_status == 'failed-delivery') {
					$msg = $this->tp_sms_orderfaileddelivery;
				} elseif ($new_status == 'returned') {
					$msg = $this->tp_sms_orderreturned;
				} else {
					// Handle the case if $new_order doesn't match any of the above values
				}

				if(($new_status=='processing' && $this->tp_on_ordernew && $this->tp_on_ordernew=='yes' && !empty($this->tp_sms_ordernew))
				||($new_status=='pending' && $this->tp_on_orderpending && $this->tp_on_orderpending=='yes' && !empty($this->tp_sms_orderpending))
				||($new_status=='on-hold' && $this->tp_on_orderhold && $this->tp_on_orderhold=='yes' && !empty($this->tp_sms_orderhold))
				||($new_status=='completed' && $this->tp_on_ordercomplete && $this->tp_on_ordercomplete=='yes' && !empty($this->tp_sms_ordercomplete))
				||($new_status=='cancelled' && $this->tp_on_ordercancelled && $this->tp_on_ordercancelled=='yes' && !empty($this->tp_sms_ordercancelled))
				||($new_status=='refunded' && $this->tp_on_orderrefunded && $this->tp_on_orderrefunded=='yes' && !empty($this->tp_sms_orderrefunded))
				||($new_status=='failed' && $this->tp_on_orderfailed && $this->tp_on_orderfailed=='yes' && !empty($this->tp_sms_orderfailed))
				||($new_status=='shipped' && $this->tp_on_ordershipped && $this->tp_on_ordershipped=='yes' && !empty($this->tp_sms_ordershipped))
				||($new_status=='ready-for-pickup' && $this->tp_on_orderreadypickup && $this->tp_on_orderreadypickup=='yes' && !empty($this->tp_sms_orderreadypickup))
				||($new_status=='failed-delivery' && $this->tp_on_orderfaileddelivery && $this->tp_on_orderfaileddelivery=='yes' && !empty($this->tp_sms_orderfaileddelivery))
				||($new_status=='returned' && $this->tp_on_orderreturned && $this->tp_on_orderreturned=='yes' && !empty($this->tp_sms_orderreturned)))
				{
					$msg = str_replace("{name}", $order->get_billing_first_name(), $msg);
					$msg = str_replace("{orderid}", $orderID, $msg);
					$msg = str_replace("{total}", $order->get_total(), $msg);
					$msg = str_replace("{phone}", $order->get_billing_phone(), $msg);
					$this->tp_sendExpressPostSMS($this->tp_clean_phone($order->get_billing_phone()), $msg);
				}
				
				// Check for flag already set or not.
				$has_admin_logged = get_post_meta( $order->get_id(), '_admin_sms_sent', true );

				if(! $has_admin_logged){
					if(($new_status=='processing' && $this->tp_on_adminreceive && $this->tp_on_adminreceive=='yes' && !empty($this->tp_sms_adminreceive))
					|| ($new_status=='pending' && $this->tp_on_adminreceive && $this->tp_on_adminreceive=='yes' && !empty($this->tp_sms_adminreceive))
					|| ($new_status=='on-hold' && $this->tp_on_adminreceive && $this->tp_on_adminreceive=='yes' && !empty($this->tp_sms_adminreceive)))
					{
						$msgAdmin = str_replace("{name}", $order->get_billing_first_name(), $msgAdmin);
						$msgAdmin = str_replace("{orderid}", $orderID, $msgAdmin);
						$msgAdmin = str_replace("{total}", $order->get_total(), $msgAdmin);
						$msgAdmin = str_replace("{phone}", $order->get_billing_phone(), $msgAdmin);
						$this->tp_sendExpressPostSMS($this->tp_clean_phone($this->tp_adminnumber), $msgAdmin);
						// Set flag to prevent duplicate logging
						update_post_meta( $order->get_id(), '_admin_sms_sent', true );
						// Delete the meta entry
						delete_post_meta( $order->get_id(), '_draft_duration_logged' );
						// Delete the meta entry
						delete_post_meta( $order->get_id(), '_sms_sent_logged' );
					}
				}
				
			}
		}


		function wc_track_order_draft_duration( $order ) {
			// Check for flag already set or not.
			$has_draft_logged = get_post_meta( $order->get_id(), '_draft_duration_logged', true );
			
			if ( $order->has_status( 'checkout-draft' ) && ! $has_draft_logged ) {
				// Schedule a cron job to send the SMS after 10 minutes
				$timestamp = time() + 300; //300 seconds
				wp_schedule_single_event( $timestamp  , 'send_draft_order_sms', array( $order->get_id() ) );
		
				// Set flag to prevent duplicate logging
				update_post_meta( $order->get_id(), '_draft_duration_logged', true );
			}
		}
		
		
		function send_draft_order_sms_callback( $order_id ) {

			$has_sms_logged = get_post_meta( $order_id, '_sms_sent_logged', true );
			
			$order = wc_get_order( $order_id );
			
			if($order->has_status( 'checkout-draft' ) && ! $has_sms_logged ){
			
				// Perform SMS sending logic here
				$this->tp_bulksms = get_option('wctpbulksms_enable');
				if($this->tp_bulksms && $this->tp_bulksms=='yes'){
					$this->tp_senderid = get_option('wctpbulksms_senderid');
					$this->tp_apitoken = get_option('wctpbulksms_apitoken');
					
					$this->tp_on_orderdraft = get_option('wctpbulksms_orderdraft');
					$this->tp_sms_orderdraft = get_option('wctpbulksms_orderdraftsms');
	
					$msg = $this->tp_sms_orderdraft;
	
					if(($this->tp_on_orderdraft && $this->tp_on_orderdraft=='yes' && !empty($this->tp_sms_orderdraft)))
					{
						$msg = str_replace("{name}", $order->get_billing_first_name(), $msg);
						$msg = str_replace("{orderid}", $order->get_id(), $msg);
						$msg = str_replace("{total}", $order->get_total(), $msg);
						$msg = str_replace("{phone}", $order->get_billing_phone(), $msg);
						$this->tp_sendExpressPostSMS($this->tp_clean_phone($order->get_billing_phone()), $msg);
					}

					// Set flag to prevent duplicate logging
					update_post_meta( $order->get_id(), '_sms_sent_logged', true );
					// Delete the meta entry
					delete_post_meta( $order->get_id(), '_draft_duration_logged' );
	
				}

			}

		}
		
		
		//Send SMS
		function tp_sendExpressPostSMS($phones, $message){
			$status = 0;
		
			// Check if $phones contains multiple phone numbers
			$multiple_numbers = strpos($phones, ',');
		
			// Set the appropriate URL and phone number parameter based on whether $phones contains multiple phone numbers
			if ($multiple_numbers !== false) {
				$url = 'https://api.mobilesasa.com/v1/send/bulk';
				$phone_param = "phones";
			} else {
				$url = 'https://api.mobilesasa.com/v1/send/message';
				$phone_param = "phone";
			}           
		
			$postData = [
				"senderID" => $this->tp_senderid,
				"message" => $message,
				$phone_param => $phones,
				"api_token" => $this->tp_apitoken
			];
		
			$curl = curl_init();
			curl_setopt_array($curl,[
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $url,
				CURLOPT_POST => 1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_TIMEOUT => 400, //timeout in seconds
				CURLOPT_POSTFIELDS => $postData
			]);
		
			// Send the request
			$response = curl_exec($curl);
			curl_close($curl);
		
			// Check the response
			$responseVals = json_decode($response,true);
			if($responseVals['responseCode'] == '0200') {
				$status = 1; // Message was sent successfully
			} else {
				$status = 0; // Message failed to send
			}
			return $status;
		}
		
		//Tp clean phone
		function tp_clean_phone($phones){
			$cleaned_phones = array();
			
			// Split the input string based on commas
			$phones_array = explode(",", $phones);
		
			foreach ($phones_array as $phone) {
				// Remove any non-numeric characters from the phone number
				$tel = str_replace(array(' ','<','>','&','{','}','*',"+",'!','@','#',"$",'%','^','&'),"",str_replace("-","",$phone));
				
				// Prepend the country code "254" and extract the last 9 digits
				$cleaned_phone = "254" . substr($tel, -9);
				
				// Add the cleaned phone number to the result array
				$cleaned_phones[] = $cleaned_phone;
			}
		
			return implode(",", $cleaned_phones);
		}

		// Function to delete specific post meta
		function delete_custom_post_meta() {
			// Define the post meta keys created by your plugin
			$meta_keys = array(
				'_admin_sms_sent',
				'_draft_duration_logged',
				'_sms_sent_logged'
				// Add other meta keys as needed
			);

			// Loop through each meta key and delete the post meta for all posts
			foreach ($meta_keys as $meta_key) {
				// Delete the post meta for all posts
				delete_metadata('post', 0, $meta_key, '', true);
			}
		}

		// Schedule the function to run once every 2 hours
		function schedule_delete_custom_post_meta() {
			// Check if the scheduled event already exists
			if (!wp_next_scheduled('delete_custom_post_meta_event')) {
				// Schedule the event to run once every 2 hours
				wp_schedule_event(time(), 'daily', 'delete_custom_post_meta_event');
			}
		}	
		
	}

	/*Class*/
	if(class_exists('tp_bulksms_plugin')){
		$tp_sms_pl = new tp_bulksms_plugin();
	}
	
}
// Register uninstall hook
register_uninstall_hook( __FILE__, 'tp_bulk_sms_uninstall' );

// Uninstall hook callback function
function tp_bulk_sms_uninstall() {
    // Load the external file for uninstallation tasks
    include_once plugin_dir_path( __FILE__ ) . 'tp_uninstall.php';
}
?>