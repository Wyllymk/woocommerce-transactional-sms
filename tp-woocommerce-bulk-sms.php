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
	}

	//Wc sections
	function wc_bulk_sms($sections){
		$sections['wctpbulksms'] = __('MOBILE SASA Bulk SMS','wordpress');
		return $sections;
	}
	function wc_bulk_sms_settings($settings,$current_section){
		if ($current_section == 'wctpbulksms'){
			$tp_sms_settings = array();
			$tp_sms_settings[] = array('name'=>__('MOBILE SASA Bulk SMS Settings','wordpress'),'type'=>'title','id'=>'wctpbulksms');
			$tp_sms_settings[] = array('name'=>__('Enable/Disable','wordpress'),'id'=>'wctpbulksms_enable','type'=>'checkbox','desc'=>__('Enable','wordpress'));
			$tp_sms_settings[] = array('name'=>__('Sender ID','wordpress'),'id'=>'wctpbulksms_senderid','type'=>'text','placeholder'=>'MOBILESASA');
			$tp_sms_settings[] = array('name'=>__('Api Token','wordpress'),'id'=>'wctpbulksms_apitoken','type'=>'text');
			$tp_sms_settings[] = array('name'=>__('Order Placed','wordpress'),'id'=>'wctpbulksms_ordernew','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
			$tp_sms_settings[] = array('name'=>__('Order Placed SMS','wordpress'),'id'=>'wctpbulksms_ordernewsms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, we have received your order {orderid}','wordpress'));
			$tp_sms_settings[] = array('name'=>__('Order Completed','wordpress'),'id'=>'wctpbulksms_ordercomplete','type'=>'checkbox','desc'=>__('Send SMS','wordpress'));
			$tp_sms_settings[] = array('name'=>__('Order Completed SMS','wordpress'),'id'=>'wctpbulksms_ordercompletesms','type'=>'textarea','desc'=>__('Order shortcodes: {name} {orderid} {total} {phone}','wordpress'),'placeholder'=>__('e.g Hello {name}, we have delivered your order {orderid}','wordpress'));
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
			//Order details
			global $woocommerce;
			$order = new WC_Order($orderID);
			$msg = $new_status=='processing' ? $this->tp_sms_ordernew : $this->tp_sms_ordercomplete;
			if(($new_status=='processing' && $this->tp_on_ordernew && $this->tp_on_ordernew=='yes' && !empty($this->tp_sms_ordernew))||($new_status=='completed' && $this->tp_on_ordercomplete && $this->tp_on_ordercomplete=='yes' && !empty($this->tp_sms_ordercomplete))){
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