<?php
/**
 * Plugin Name:  WooCommerce WHMCS Integration
 * Plugin URI:   https://aruldev.net
 * Description:  This plugin integrates WooCommerce with WHMCS.
 * Version:      1.0.0
 * Author:       aruldev
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  aruldev.net
 * Domain Path:  /languages
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
 * Main plugin class
 */

final class WC_WHMCS_Integration {

	const version = '1.0.0';

	public static function init() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	private function __construct() {
		$this->define_constants();
		//register_activation_hook( __FILE__, [ $this, 'activate' ] );
		add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );
	}

	public function define_constants() {
		define( 'WC_WHMCS_VERSION', self::version );
		define( 'WC_WHMCS_FILE', __FILE__ );
		define( 'WC_WHMCS_PATH', plugin_dir_path( __FILE__ ) );
		define( 'WC_WHMCS_URL', plugins_url( '', WC_WHMCS_FILE ) );
		define( 'WC_WHMCS_ASSETS', WC_WHMCS_URL . '/assets' );
		define( 'WC_WHMCS_LOG_PATH', wp_upload_dir( null, false )['basedir'] . '/woo-whmcs-log' );
		define( 'WC_WHMCS_LOG_FILE', 'woo_whmcs.log' );
		define( 'WC_WHMCS_LOG_LIMIT', 1000 );
	}

	public function init_plugin() {
		require_once WC_WHMCS_PATH . 'includes/class-woo-whmcs-util.php';
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', 'WhmcsAPIUtil::admin_notice_error' );

			return;
		}
		require_once WC_WHMCS_PATH . 'includes/class-woo-whmcs-api-client.php';
		require_once WC_WHMCS_PATH . 'includes/class-woo-whmcs-subscription.php';
		require_once WC_WHMCS_PATH . 'includes/class-woo-whmcs-admin.php';

		//set hook
		//add_action('woocommerce_order_status_changed', array($this, 'order_status_changed'), 10, 1);
		add_action( 'woocommerce_subscription_payment_complete', array(
			$this,
			'subscription_payment_complete'
		), 10, 1 );
		add_action( 'woocommerce_subscription_status_updated', array( $this, 'subscription_status_updated' ), 10, 3 );
		add_action( 'woocommerce_subscription_renewal_payment_complete', array(
			$this,
			'subscription_renewwal_payment_complete'
		), 10, 2 );
		//add_action('woocommerce_before_delete_order_item', array($this, 'before_delete_order_item'), 10, 1);
		add_action( 'woocommerce_delete_order_item', array( $this, 'delete_order_item' ), 10, 1 );
		add_action( 'template_redirect', array( $this, 'custom_redirects' ), 10 );


		//add_action('woocommerce_order_status_changed', array($this, 'subscription_payment_complete'), 10, 1);

		//echo "<div class='notice notice-warning is-dismissible'><ul>test string<ul></div>";
		$woo_whmcs_admin = new Woo_WHMCS_Admin();
		$woo_whmcs_admin->init();
	}


	public function subscription_renewwal_payment_complete( $subscription, $order ) {

//		alog( "subscription_renewwal_payment_complete", $subscription, __FILE__, __LINE__ );

		$subscription_id = $subscription->get_id();
		$order_id        = $order->get_id();
		$billing_email   = $subscription->get_billing_email();
		$next_payment    = $subscription->get_date( 'next_payment', 'site' );

	}

	public function custom_redirects() {

		if ( is_page( 'my-account' ) ) {

			//$subscription_id = $_GET['subscription_id'];
			//$subscription_id = $wp_query->query_vars['subscription_id'];
			//$remove_item = $wp_query->query_vars['remove_item'];
			//$remove_item = $_GET['remove_item'];
			//WhmcsAPIUtil::set_log("params: subscription id: $subscription_id, remove item: $remove_item");

			//WhmcsAPIUtil::set_log("test redirection");
			if ( is_user_logged_in() ) {
				//WhmcsAPIUtil::set_log(str_repeat('-', 200));
				$current_user = wp_get_current_user();
				$user_id      = $current_user->ID;
				//get transient
				$transient_var = 'whmcs_subs_' . $user_id;
				$transient_val = get_transient( $transient_var );
				/*if(!empty($transient_val)){
					WhmcsAPIUtil::set_log("transient value: \r\n" . json_encode($transient_val, JSON_PRETTY_PRINT));	
				}else{
					WhmcsAPIUtil::set_log("transient value: (empty)");
				}*/

				// Get user subscriptions
				$subscriptions  = wcs_get_subscriptions( [ 'customer_id' => $user_id ] );
				$data_transient = array();
				foreach ( $subscriptions as $subscription_id => $subscription ) {
					// Get items from current subscription
					$items = $subscription->get_items();
					//WhmcsAPIUtil::set_log("subcription id: $subscription_id");
					foreach ( $items as $item_id => $item ) {
						$product_id                                     = $item->get_product_id();
						$product_name                                   = $item->get_name();
						$data_transient[ $subscription_id ][ $item_id ] = $product_id;

						set_transient( $transient_var, $data_transient, 86400 );
						//WhmcsAPIUtil::set_log("subscription id: $subscription_id, item id: $item_id, product id: $product_id, product name: $product_name");
					}
				}

				/*if(!empty($data_transient)){
					WhmcsAPIUtil::set_log("new transient data: \r\n" . json_encode($data_transient, JSON_PRETTY_PRINT));	
				}*/

				//check differenst
				if ( ! empty( $transient_val ) && ! empty( $data_transient ) ) {
					//ini_set('display_errors', 1);
					//error_reporting(E_ALL);
					$data_diff = WhmcsAPIUtil::check_diff_multi( $transient_val, $data_transient );

					//WhmcsAPIUtil::set_log("check for deletion?");
					if ( ! empty( $data_diff ) ) {
						WhmcsAPIUtil::set_log( str_repeat( '-', 200 ) );
						WhmcsAPIUtil::set_log( "item delete triggered" );
						//WhmcsAPIUtil::set_log("data to delete: \r\n" . json_encode($data_diff, JSON_PRETTY_PRINT));	
						foreach ( $data_diff as $subscription_id => $subscription ) {
							foreach ( $subscription as $item_id => $product_id ) {
								$service_id = wc_get_order_item_meta( $item_id, '_whmcs_service_id', true );
								if ( ! empty( $service_id ) ) {
									//WhmcsAPIUtil::set_log("Suspend subscription, service id: $service_id");
									//$message = "Suspend subscription.";
									//$this->do_suspend($service_id, $message);
									$message = "Terminate subscription, from delete item.";
									$this->do_terminate( $service_id );
								}
							}
						}
						//}else{
						//	WhmcsAPIUtil::set_log("no deletion");
					}
				}
			}
			/*$subscription_id = get_query_var( 'subscription_id', false );
			$remove_item = get_query_var( 'remove_item', false );
			WhmcsAPIUtil::set_log("this is page my-account");
			WhmcsAPIUtil::set_log("item with item id: $remove_item, subscription id: $subscription_id");*/

			//wp_redirect( home_url( '/new-contact/' ) );
			//die;
		}
	}

	public function delete_order_item( $item_id ) {
		WhmcsAPIUtil::set_log( str_repeat( '-', 200 ) );
		WhmcsAPIUtil::set_log( "do delete order item log, item id: $item_id" );
		$service_id = wc_get_order_item_meta( $item_id, '_whmcs_service_id', true );
		if ( ! empty( $service_id ) ) {
			//$message = "Suspend subscription.";
			//$this->do_suspend($service_id, $message);
			$message = "Terminate subscription.";
			$this->do_terminate( $service_id );
			//WhmcsAPIUtil::set_log("delete for whmcs service id: $service_id");
		} else {
			WhmcsAPIUtil::set_log( "no service id found" );
		}

	}

	/*public function before_delete_order_item($item_id){
		WhmcsAPIUtil::set_log(str_repeat('-', 200));
		WhmcsAPIUtil::set_log("before delete order item log, item id: $item_id");
		$service_id = wc_get_order_item_meta($item_id, '_whmcs_service_id',  true);
		if(!empty($service_id)){
			WhmcsAPIUtil::set_log("delete for whmcs service id: $service_id");
		}else{
			WhmcsAPIUtil::set_log("no service id found");
		}
		
	}*/

	public function subscription_payment_complete( $subscription ) {

//		alog( 'pyment complete', $subscription, __FILE__, __LINE__ );


//		alog( "Woocommere Subscription Reniew", $subscription, __FILE__, __LINE__ );
//		alog( "Woocommere Subscription Reniew ID", $subscription->get_meta( '_whmcs_service_id' ), __FILE__, __LINE__ );
//		alog( "Woocommere Subscription Reniew Subscription id", $subscription->get_meta( '_subscription_resubscribe' ), __FILE__, __LINE__ );

		WhmcsAPIUtil::set_log( str_repeat( '-', 200 ) );
		$first_name = $subscription->get_billing_first_name();
		$last_name  = $subscription->get_billing_last_name();
		WhmcsAPIUtil::set_log( "payment completete for: $first_name $last_name" );
		$last_order = $subscription->get_last_order( 'all', 'any' );
//		alog( "Woocommere  Reniew Last Order", $last_order, __FILE__, __LINE__ );
//		if ( wcs_order_contains_renewal( $last_order ) ) {
//			alog( "Woocommere Contain Reniew", $subscription, __FILE__, __LINE__ );
//			WhmcsAPIUtil::set_log( 'renewal order' );
//			WhmcsAPIUtil::set_log( 'order id: ' . $subscription->get_id() );
//			$types = [ 'line_item' ];
//			foreach ( $subscription->get_items( $types ) as $item_id => $item ) {
//				$item_id      = $item->get_id();
//				$product_id   = $item->get_product_id();
//				$product_name = $item->get_name();
//
//				WhmcsAPIUtil::set_log( str_repeat( '-', 200 ) );
//				WhmcsAPIUtil::set_log( "product id: $product_id, product name: $product_name" );
//				$map_opt = get_option( 'whmcs_map_list', array() );
//				if ( array_key_exists( $product_id, $map_opt ) ) {
//					$pid = $map_opt[ $product_id ];
//					//wc_add_order_item_meta( $item_id, '_whmcs_service_id', $pid, true );
//					WhmcsAPIUtil::set_log( 'maps to whmcs product id: ' . $pid );
//					$this->do_renewal( $subscription, $pid, $item_id );
//				} else {
//					WhmcsAPIUtil::set_log( 'no whmcs product id mapping for product: ' . $product_name );
//				}
//			}
//		}

		if ( ! empty( $subscription->get_meta( '_subscription_resubscribe' ) ) ) {
			$subscription_id = $subscription->get_meta( '_subscription_resubscribe' );
			$resubscribe     = wcs_get_subscription( $subscription_id );
			$service_Id      = $resubscribe->get_meta( '_whmcs_service_id' );
//			alog( 'subscription_id', $subscription_id, __FILE__, __LINE__ );
//			alog( 'resubscribe Object', $resubscribe, __FILE__, __LINE__ );
//			alog( "Service ID", $service_Id, __FILE__, __LINE__ );
			$this->resubscribe_subscription( $subscription, $service_Id, 'active' );

		} else {//add new
//			alog( "Woocommere not Contain Reniew", $subscription, __FILE__, __LINE__ );
			WhmcsAPIUtil::set_log( 'add new order' );
			WhmcsAPIUtil::set_log( 'order id: ' . $subscription->get_id() );
			$types = [ 'line_item' ];
			foreach ( $subscription->get_items( $types ) as $item_id => $item ) {
				$item_id      = $item->get_id();
				$product_id   = $item->get_product_id();
				$product_name = $item->get_name();

				WhmcsAPIUtil::set_log( str_repeat( '-', 200 ) );
				WhmcsAPIUtil::set_log( "product id: $product_id, product name: $product_name" );
				$map_opt = get_option( 'whmcs_map_list', array() );
				if ( array_key_exists( $product_id, $map_opt ) ) {
					$pid = $map_opt[ $product_id ];

					//wc_add_order_item_meta( $item_id, '_whmcs_service_id', $pid, true );

					WhmcsAPIUtil::set_log( 'maps to whmcs product id: ' . $pid );
					$this->do_create_service( $subscription, $pid, $item_id );
				} else {
					WhmcsAPIUtil::set_log( 'no whmcs product id mapping for product: ' . $product_name );
				}
			}
		}
		WhmcsAPIUtil::set_log( str_repeat( '-', 200 ) );
		//WhmcsAPIUtil::set_log(str_repeat('-', 200));
		//WhmcsAPIUtil::set_log('subscription id: ' . $subscription->ID);
	}

	public function subscription_status_updated( $subscription, $new_status, $old_status ) {
//		alog( "subscription change", $subscription, __FILE__, __LINE__ );
		$serviceid = $subscription->get_meta( '_whmcs_service_id' );
		if ( ! empty( $serviceid ) ) {
			if ( ( $old_status == 'active' && $new_status == 'on-hold' ) || $new_status == 'cancelled' ) {//suspend
				//pending-cancel
				if ( $new_status == 'on-hold' ) {
					$message = 'Suspend subscription.';
				} elseif ( $new_status == 'cancelled' ) {
					$message = 'Cancel subscription.';
				}

				$subscription_id = $subscription->get_id();
				$this->do_suspend( $subscription_id, $serviceid, $message );
			} elseif ( ( $old_status == 'on-hold' || $old_status == 'pending-cancel' ) && $new_status == 'active' ) {//unsuspend
				//$this->do_unsuspend($serviceid);
				$this->do_unsuspend( $subscription, $serviceid );
			}
		}
	}

	/*public function order_status_changed($order_id, $old_status, $new_status, $order){
		if($new_status == 'completed'){
			foreach ( $order->get_items() as $item_id => $item ) {
				$item_type = $item->get_type();
				WhmcsAPIUtil::set_log(str_repeat('-', 200));
				WhmcsAPIUtil::set_log('woocommerce order id: ' . $order->get_id());
				if($item_type == 'line_item'){
					$product = $item->get_product();
					if($product->get_type()=='subscription'){
						WhmcsAPIUtil::set_log('product type : subcription');
						$map_opt = get_option('whmcs_map_list', array());
						$product_id = $product->get_id();
						if(array_key_exists($product_id, $map_opt)){
							$pid = $map_opt[$product_id];
							WhmcsAPIUtil::set_log('whmcs product id: ' . $pid);
							//$this->do_api_call($order, $pid);
						}
					}
				}
			}

		}
	}*/

	private function within_grace_period( $suspended_date ) {
		$grace_period_days = get_option( 'whmcs_grace_periode', 60 );
		$suspended_time    = strtotime( $suspended_date );
		$current_time      = time();

		return ( $current_time - $suspended_time ) <= ( $grace_period_days * 24 * 60 * 60 );
	}

	private function resubscribe_subscription( $subscription, $service_id, $status ) {
		$api_client = new Woo_WHMCS_Api_Client();
		$response   = $api_client->update_client_product_status( $service_id, 'active' );

		if ( $response['result'] == 'success' ) {
			$subscription->update_meta_data( '_whmcs_service_id', $service_id );
			$api_client->unsuspend_order( $service_id );
		}
//		alog( 'response', $response, __FILE__, __LINE__ );
	}

	private function do_renewal( $subscription, $pid, $item_id ) {
		$random_passwd = WhmcsAPIUtil::randomString();
		$data          = [
			'firstname'   => $subscription->get_billing_first_name(),
			'lastname'    => $subscription->get_billing_last_name(),
			'email'       => $subscription->get_billing_email(),
			'address1'    => $subscription->get_billing_address_1(),
			'city'        => $subscription->get_billing_city(),
			'state'       => '-',
			'postcode'    => $subscription->get_billing_postcode(),
			'country'     => $subscription->get_billing_country(),
			'phonenumber' => $subscription->get_billing_phone(),
			'password2'   => $random_passwd
		];

		$paymentmethod = 'paypal';

		$iserror = false;
		//check user exists based on email
		$api_client = new Woo_WHMCS_Api_Client();
		WhmcsAPIUtil::set_log( 'client check if exists: ' . $data['email'] );
		$result_api = $api_client->get_client( $data['email'] );
		if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
			$is_user_exists = $result_api['totalresults'] == 1;
			$client_id      = $result_api['clients']['client'][0]['id'];
		} else {
			$is_user_exists = false;
			$client_id      = null;
			$iserror        = true;
			WhmcsAPIUtil::set_log( "error: " . $result_api['message'] );
		}

		//if exists then get client_id variable
		if ( $is_user_exists == true ) {
			WhmcsAPIUtil::set_log( 'client exists clientid: ' . $client_id );
		} elseif ( ! $iserror ) {
			WhmcsAPIUtil::set_log( 'client not exists: create client...' );
			$result_api = $api_client->add_client( $data );
			//WhmcsAPIUtil::set_log("response:\r\n" . json_encode($result_api, JSON_PRETTY_PRINT));
			if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
				$client_id = $result_api['clientid'];
				WhmcsAPIUtil::set_log( 'client created clientid: ' . $client_id );
			} else {
				$client_id = null;
				$iserror   = true;
				WhmcsAPIUtil::set_log( "create client failed, response message: " . $result_api['message'] );
			}
		}

		//grace periode

		$suspended_date = $subscription->get_meta( '_whmcs_suspended_date' );
		if ( $suspended_date ) {
			$is_within_grace_periode = $this->within_grace_period( $suspended_date );
		} else {
			$is_within_grace_periode = false;
		}

		//update_client_product_status

		//check client product exists
		if ( ! empty( $client_id ) && ! $iserror ) {
			WhmcsAPIUtil::set_log( 'Client product/service check if exists for: ' . $data['email'] );
			//get client domain
			$product_api = $api_client->get_client_products( $client_id );
			if ( isset( $product_api['result'] ) && $product_api['result'] == 'success' ) {
				//$is_client_product_exists = $product_api['totalresults'] > 0;

				$is_unsuspended = false;
				foreach ( $product_api['products']['product'] as $product ) {
					$serviceid = $product['id'];

					$is_service_terminate = false;
					if ( $product['status'] == 'Suspended' ) {
						if ( $is_within_grace_periode && ! $is_unsuspended ) {
							WhmcsAPIUtil::set_log( "Unsuspend Client product/service..." );
							$suspend_api = $api_client->unsuspend_order( $serviceid );
							if ( isset( $suspend_api['result'] ) && $suspend_api['result'] == 'success' ) {
								$is_unsuspended = true;
								WhmcsAPIUtil::set_log( "Client product/service unsuspended" );
							} else {
								$iserror = true;
								WhmcsAPIUtil::set_log( "Client product/service unsuspension failed, response message: " . $suspend_api['message'] );
							}
						} else {
							$is_service_terminate = true;
						}
					}
					//service termination for Suspended more than grace periode
					if ( $is_service_terminate ) {
						$terminate_api = $api_client->module_terminate( $serviceid );
						if ( isset( $terminate_api['result'] ) && $terminate_api['result'] == 'success' ) {
							WhmcsAPIUtil::set_log( "Client product/service terminated" );
						} else {
							//$iserror = true; if  error this can continue next process
							WhmcsAPIUtil::set_log( "Client product/service termination failed, response message: " . $terminate_api['message'] );
						}
					}
				}
			} else {
				//$is_client_product_exists = false;
				$iserror = true;
				WhmcsAPIUtil::set_log( "checking client product error: " . $product_api['message'] );
			}


			//client product not exist or no suspended, so create new one
			if ( ! $iserror && ! $is_unsuspended ) {
				WhmcsAPIUtil::set_log( "Client product/service does not exists, do create..." );
				$order_data = [
					'clientid'      => $client_id,
					'pid'           => $pid,
					'paymentmethod' => $paymentmethod
					//'domain' => $domain
				];
				WhmcsAPIUtil::set_log( "add order for clientid: " . $client_id );
				$result_api = $api_client->add_order( $order_data );
				if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
					$order_id   = $result_api['orderid'];
					$service_id = $result_api['serviceids'];
					$subscription->update_meta_data( '_whmcs_service_id', $service_id );

					wc_add_order_item_meta( $item_id, '_whmcs_service_id', $service_id, true );

					WhmcsAPIUtil::set_log( 'order created orderid: ' . $order_id . ', serviceid: ' . $service_id );

					//set domain
					$domain = WhmcsAPIUtil::get_domain_from_email( $order_id, $data['email'] );

					WhmcsAPIUtil::set_log( "set domain: " . $domain );
					$result_api = $api_client->set_domain( $service_id, $domain );
					if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
						WhmcsAPIUtil::set_log( 'set domain success' );
					} else {
						$iserror = true;
						WhmcsAPIUtil::set_log( "set domain failed: " . $result_api['message'] );
					}

					//accept order
					$serviceusername = WhmcsAPIUtil::get_user_from_email( $order_id, $data['email'] );
					$order_data      = [
						'orderid'         => $order_id,
						'sendemail'       => true,
						'autosetup'       => true,
						'serviceusername' => $serviceusername,
						'servicepassword' => $random_passwd
					];
					WhmcsAPIUtil::set_log( "accept order, orderid: " . $order_data['orderid'] );
					$result_api = $api_client->accept_order( $order_data );
					if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
						WhmcsAPIUtil::set_log( 'order accepted orderid: ' . $order_id );
					} else {
						$iserror = true;
						WhmcsAPIUtil::set_log( "order acceptation failed: " . $result_api['message'] );
					}
				} else {
					//$order_id = null;
					//$service_id = null;
					$iserror = true;
					WhmcsAPIUtil::set_log( "create order failed: " . $result_api['message'] );
				}


				/* $serviceid = $result_api['products']['product'][0]['id'];
				$status = $result_api['products']['product'][0]['status'];

				WhmcsAPIUtil::set_log("Client product/service exists, status: " . $status);

				if($status=='Termninated'){
					WhmcsAPIUtil::set_log("Create Client product/service...");
					$result_api = $api_client->module_create($serviceid);
					if(isset($result_api['result']) && $result_api['result'] == 'success'){
						WhmcsAPIUtil::set_log("Client product/service created");
					}else{
						WhmcsAPIUtil::set_log("Client product/service creation failed, response message: " . $result_api['message']);
					}
				}else{
					WhmcsAPIUtil::set_log("Unsuspend Client product/service...");
					$result_api = $api_client->unsuspend_order($serviceid);
					if(isset($result_api['result']) && $result_api['result'] == 'success'){
						WhmcsAPIUtil::set_log("Client product/service unsuspended");
					}else{
						WhmcsAPIUtil::set_log("Client product/service unsuspension failed, response message: " . $result_api['message']);
					}
				} */

				//}elseif(!$is_client_product_exists && !$iserror){

				/* }else{
					$order_id = null;
					$service_id = null; */
			}
		}
	}

	//private function do_suspend($serviceid, $suspendreason){
	private function do_suspend( $subscription_id, $serviceid, $suspendreason ) {
		WhmcsAPIUtil::set_log( str_repeat( '-', 200 ) );
		WhmcsAPIUtil::set_log( "Suspend subscription, serviceid: $serviceid" );

		$suspended_date = date( "Y-m-d H:i:s" );

		$api_client = new Woo_WHMCS_Api_Client();
		$result_api = $api_client->suspend_order( $serviceid, $suspendreason );
		if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
			$subscription = new WC_Subscription( $subscription_id );
			$subscription->update_meta_data( '_whmcs_suspended_date', $suspended_date );

			WhmcsAPIUtil::set_log( "Subscription suspended, reason: $suspendreason" );
		} else {
			WhmcsAPIUtil::set_log( "Suspend failed, response message: " . $result_api['message'] );
		}
	}

	private function do_unsuspend( $subscription, $serviceid ) {
		WhmcsAPIUtil::set_log( str_repeat( '-', 200 ) );
		WhmcsAPIUtil::set_log( "Unsuspend subscription, serviceid: $serviceid" );

		$api_client = new Woo_WHMCS_Api_Client();
		$result_api = $api_client->unsuspend_order( $serviceid );
		if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
			$subscription->update_meta_data( '_whmcs_suspended_date', null );
			WhmcsAPIUtil::set_log( "Subscription activated" );
		} else {
			WhmcsAPIUtil::set_log( "Subscription activation failed, response message: " . $result_api['message'] );
		}
	}

	private function do_create_service( $subscription, $pid, $item_id ) {
		$random_passwd = WhmcsAPIUtil::randomString();
		$data          = [
			'firstname'   => $subscription->get_billing_first_name(),
			'lastname'    => $subscription->get_billing_last_name(),
			'email'       => $subscription->get_billing_email(),
			'address1'    => $subscription->get_billing_address_1(),
			'city'        => $subscription->get_billing_city(),
			'state'       => '-',
			'postcode'    => $subscription->get_billing_postcode(),
			'country'     => $subscription->get_billing_country(),
			'phonenumber' => $subscription->get_billing_phone(),
			'password2'   => $random_passwd
		];

		$paymentmethod = 'paypal';

		$iserror = false;
		//check user exists based on email
		$api_client = new Woo_WHMCS_Api_Client();
		WhmcsAPIUtil::set_log( 'client check if exists: ' . $data['email'] );
		$result_api = $api_client->get_client( $data['email'] );
		if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
			$is_user_exists = $result_api['totalresults'] == 1;
			$client_id      = $result_api['clients']['client'][0]['id'];
		} else {
			$is_user_exists = false;
			$client_id      = null;
			$iserror        = true;
			WhmcsAPIUtil::set_log( "error: " . $result_api['message'] );
		}

		//if exists then get client_id variable
		if ( $is_user_exists == true ) {
			WhmcsAPIUtil::set_log( 'client exists clientid: ' . $client_id );
		} elseif ( ! $iserror ) {
			WhmcsAPIUtil::set_log( 'client not exists: create client...' );
			$result_api = $api_client->add_client( $data );
			//WhmcsAPIUtil::set_log("response:\r\n" . json_encode($result_api, JSON_PRETTY_PRINT));
			if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
				$client_id = $result_api['clientid'];
				WhmcsAPIUtil::set_log( 'client created clientid: ' . $client_id );
			} else {
				$client_id = null;
				$iserror   = true;
				WhmcsAPIUtil::set_log( "create client failed, response message: " . $result_api['message'] );
			}
		}

		//check client product exists
		if ( ! empty( $client_id ) && ! $iserror ) {
			WhmcsAPIUtil::set_log( 'Client product/service check if exists for: ' . $data['email'] );
			//get client domain
			$result_api = $api_client->get_client_products( $client_id );
			if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
				//$is_client_product_exists = $result_api['totalresults'] > 0;
				$is_unsuspended = false;
				foreach ( $product_api['products']['product'] as $product ) {
					$serviceid = $product['id'];

					$is_service_terminate = false;
					if ( $product['status'] == 'Suspended' ) {
						if ( ! $is_unsuspended ) {
							WhmcsAPIUtil::set_log( "Unsuspend Client product/service..." );
							$suspend_api = $api_client->unsuspend_order( $serviceid );
							if ( isset( $suspend_api['result'] ) && $suspend_api['result'] == 'success' ) {
								$is_unsuspended = true;
								WhmcsAPIUtil::set_log( "Client product/service unsuspended" );
							} else {
								$iserror = true;
								WhmcsAPIUtil::set_log( "Client product/service unsuspension failed, response message: " . $suspend_api['message'] );
							}
						} else {
							$is_service_terminate = true;
						}
					}
					//service termination for Suspended more than grace periode
					if ( $is_service_terminate ) {
						$terminate_api = $api_client->module_terminate( $serviceid );
						if ( isset( $terminate_api['result'] ) && $terminate_api['result'] == 'success' ) {
							WhmcsAPIUtil::set_log( "Client product/service terminated" );
						} else {
							//$iserror = true; if  error this can continue next process
							WhmcsAPIUtil::set_log( "Client product/service termination failed, response message: " . $terminate_api['message'] );
						}
					}
				}
			} else {
				$is_client_product_exists = false;
				$iserror                  = true;
				WhmcsAPIUtil::set_log( "checking client product error: " . $result_api['message'] );
			}

			if ( ! $iserror && ! $is_unsuspended ) {
				WhmcsAPIUtil::set_log( "Client product/service does not exists, do create..." );
				$order_data = [
					'clientid'      => $client_id,
					'pid'           => $pid,
					'paymentmethod' => $paymentmethod
					//'domain' => $domain
				];
				WhmcsAPIUtil::set_log( "add order for clientid: " . $client_id );
				//$this->printTest($order_data, 'add order');
				$result_api = $api_client->add_order( $order_data );
				//$this->printTest($result_api, 'response');
				if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
					$order_id   = $result_api['orderid'];
					$service_id = $result_api['serviceids'];
					$subscription->update_meta_data( '_whmcs_service_id', $service_id );

					wc_add_order_item_meta( $item_id, '_whmcs_service_id', $service_id, true );

					WhmcsAPIUtil::set_log( 'order created orderid: ' . $order_id . ', serviceid: ' . $service_id );

					//set domain
					$domain = WhmcsAPIUtil::get_domain_from_email( $order_id, $data['email'] );

					WhmcsAPIUtil::set_log( "set domain: " . $domain );
					$result_api = $api_client->set_domain( $service_id, $domain );
					if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
						WhmcsAPIUtil::set_log( 'set domain success' );
					} else {
						$iserror = true;
						WhmcsAPIUtil::set_log( "set domain failed: " . $result_api['message'] );
					}

					//accept order
					$serviceusername = WhmcsAPIUtil::get_user_from_email( $order_id, $data['email'] );
					$order_data      = [
						'orderid'         => $order_id,
						'sendemail'       => true,
						'autosetup'       => true,
						'serviceusername' => $serviceusername,
						'servicepassword' => $random_passwd
					];
					WhmcsAPIUtil::set_log( "accept order, orderid: " . $order_data['orderid'] );
					$result_api = $api_client->accept_order( $order_data );
					if ( isset( $result_api['result'] ) && $result_api['result'] == 'success' ) {
						WhmcsAPIUtil::set_log( 'order accepted orderid: ' . $order_id );
					} else {
						WhmcsAPIUtil::set_log( "create order failed: " . $result_api['message'] );
					}
				} else {
					//$order_id = null;
					//$service_id = null;
					$iserror = true;
					WhmcsAPIUtil::set_log( "create order failed: " . $result_api['message'] );
				}
			}
		}
	}
}

function wc_whmcs_integration() {
	return WC_WHMCS_Integration::init();
}

// kick-off the plugin
wc_whmcs_integration();


