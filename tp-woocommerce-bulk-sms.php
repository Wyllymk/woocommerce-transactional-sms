<?php
defined('ABSPATH') or die("No access please!");

/* Plugin Name: Tp WooCommerce MOBILESASA SMS
* Plugin URI: https://codetribe.co.ke/
* Description: MOBILE SASA Bulk SMS for WooCommerce.
* Version: 1.0
* Author: CodeTribe Kenya
* Author URI: https://codetribe.co.ke
* Licence: GPL2
* WC requires at least: 2.2
* WC tested up to: 8.3.1
*/


// Include the file containing the custom order status function
require_once plugin_dir_path(__FILE__) . 'include/tp-woocommerce-custom-order-status.php';

// Call each function to register the custom order statuses
add_action('init', 'register_shipment_departure_order_status');
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
			// add_filter('woocommerce_get_sections_advanced', array($this,"wc_bulk_sms"));
			add_filter('woocommerce_get_settings_advanced', array($this,"wc_bulk_sms_settings"),10,2);
			add_action('woocommerce_order_status_changed', array($this,"tp_order_status"),10,3);
		}

		//Wc sections
		function wc_bulk_sms($sections){
			$sections['wctpbulksms'] = __('MOBILE SASA Bulk SMS','wordpress');
			return $sections;
		}
		
		function wc_bulk_sms_settings($settings, $current_section){
			if ($current_section == 'wctpbulksms'){
				$tp_sms_settings = array();
				$tp_sms_settings[] = array('name'=>__('MOBILE SASA Bulk SMS Settings','wordpress'),'type'=>'title','id'=>'wctpbulksms');
				$tp_sms_settings[] = array('name'=>__('Enable/Disable','wordpress'),'id'=>'wctpbulksms_enable','type'=>'checkbox','desc'=>__('Enable','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Sender ID','wordpress'),'id'=>'wctpbulksms_senderid','type'=>'text','placeholder'=>'MOBILESASA');
				$tp_sms_settings[] = array('name'=>__('Api Token','wordpress'),'id'=>'wctpbulksms_apitoken','type'=>'text');
				$tp_sms_settings[] = array('name'=>__('Order Placed','wordpress'),'id'=>'wctpbulksms_ordernew','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Placed SMS','wordpress'),'id'=>'wctpbulksms_ordernewsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, we have received your order {orderid}','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Completed','wordpress'),'id'=>'wctpbulksms_ordercomplete','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Completed SMS','wordpress'),'id'=>'wctpbulksms_ordercompletesms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, we have shipped your order {orderid}','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Cancelled','wordpress'),'id'=>'wctpbulksms_ordercancelled','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Cancelled SMS','wordpress'),'id'=>'wctpbulksms_ordercancelledsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, we have cancelled your order {orderid}','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Refunded','wordpress'),'id'=>'wctpbulksms_orderrefunded','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Refunded SMS','wordpress'),'id'=>'wctpbulksms_orderrefundedsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, we have refunded your order {orderid}','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Failed','wordpress'),'id'=>'wctpbulksms_orderfailed','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Failed SMS','wordpress'),'id'=>'wctpbulksms_orderfailedsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, your order {orderid} has failed','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Shipped','wordpress'),'id'=>'wctpbulksms_ordershipped','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Shipped SMS','wordpress'),'id'=>'wctpbulksms_ordershippedsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, your order {orderid} has been shipped','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Ready for Pickup','wordpress'),'id'=>'wctpbulksms_orderreadypickup','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Ready for Pickup SMS','wordpress'),'id'=>'wctpbulksms_orderreadypickupsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, your order {orderid} is ready for pickup','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Failed Delivery','wordpress'),'id'=>'wctpbulksms_orderfaileddelivery','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Failed Delivery SMS','wordpress'),'id'=>'wctpbulksms_orderfaileddeliverysms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, your order {orderid} has failed delivery','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Returned','wordpress'),'id'=>'wctpbulksms_orderreturned','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
				$tp_sms_settings[] = array('name'=>__('Order Returned SMS','wordpress'),'id'=>'wctpbulksms_orderreturnedsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, your order {orderid} has been returned','wordpress'));
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

				if ($new_status == 'processing') {
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
			}
		}
		
		//Send SMS
		function tp_sendExpressPostSMS($phone, $message){
			$return = 0;
			$curl = curl_init();
			curl_setopt_array($curl,[
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => 'https://api.mobilesasa.com/v1/send/message',
				CURLOPT_POST => 1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_TIMEOUT => 400, //timeout in seconds
				CURLOPT_POSTFIELDS => [
					"senderID" => $this->tp_senderid,
					"message" => $message,
					"phone" => $phone,
					"api_token" => $this->tp_apitoken
				]
			]);
			// Send the request
			$response = curl_exec($curl);
			curl_close($curl);
			//echo $response;
			$status = 0;
			$responseVals = json_decode($response,true);
			if($responseVals['responseCode'] == '0200') $status = 1;
			return $status;
		}
		
		//Tp clean phone
		function tp_clean_phone($phone){
			$tel = str_replace(array(' ','<','>','&','{','}','*',"+",'!','@','#',"$",'%','^','&'),"",str_replace("-","",$phone));
			return "254".substr($tel,-9);
		}
	}

	/*Class*/
	if(class_exists('tp_bulksms_plugin')){
		$tp_sms_pl = new tp_bulksms_plugin();
	}
	
}
?>