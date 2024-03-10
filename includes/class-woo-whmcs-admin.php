

<?php

class Woo_WHMCS_Admin{
	public function init() {
		add_action('admin_menu', array($this, 'add_menu_page'));
		add_filter('submenu_file', array($this, 'submenu_filter')); //filter to hide sub menu and set selected menu to  another menu
		add_action('admin_enqueue_scripts', array($this, 'register_admin_script'));
		add_action('wp_ajax_woo_whmcs_delete_selected_product_service_mapping_action', array($this, 'product_service_delete_selected_action'));
	}

	public function product_service_delete_selected_action(){
		$map_data = get_option('whmcs_map_list', array());

		$product_ids = $_POST['product_ids'];		
		foreach ($map_data as $product_id => $item) {
			if(in_array($product_id, $product_ids)){
				unset($map_data[$product_id]);
			}
		}

		update_option('whmcs_map_list', $map_data);

		echo json_encode([
			'success' => true,
			'message' => 'Map deleted', 
		]);

		wp_die();
	}

	public function submenu_filter(){
		global $plugin_page;

		$hidden_submenus = array(
			'woo_whmcs_add_product_service_mapping' => true,
		);

		// Select another submenu item to highlight (optional).
		$submenu_file = '';
		if ( $plugin_page && isset( $hidden_submenus[ $plugin_page ] ) ) {
			$submenu_file = 'woo_whmcs_settings';
		}

		// Hide the submenu.
		foreach ( $hidden_submenus as $submenu => $unused ) {
			remove_submenu_page( 'woo_whmcs', $submenu );
		}

		return $submenu_file;
	}

	public function add_menu_page() {
		add_menu_page(
			'WooCommerce WHMCS',
			'WooWHMCS',
			'manage_options',
			'woo_whmcs',
			[$this, 'render_api_credentials_form']
		);
	
		add_submenu_page(
			'woo_whmcs',
			'API Credentials',
			'API Credentials',
			'manage_options',
			'woo_whmcs',
			[$this, 'render_api_credentials_form']
		);

		add_submenu_page(
			'woo_whmcs',
			'Settings',
			'Settings',
			'manage_options',
			'woo_whmcs_settings',
			[$this, 'render_settings_form']
		);

		add_submenu_page(
			'woo_whmcs',
			'Logs',
			'Logs',
			'manage_options',
			'woo_whmcs_logs',
			array($this, 'render_logs_form')
		);

		add_submenu_page(
			'woo_whmcs',
			'Add product service mapping',
			'Add product service mapping',
			'manage_options',
			'woo_whmcs_add_product_service_mapping',
			array($this, 'render_add_product_service_mapping_form')
		);

		/* add_submenu_page(
			'woo_whmcs',
			'Test Create Client',
			'Test Create Client',
			'manage_options',
			'woo_whmcs_test_create',
			array($this, 'render_test_create')
		);

		add_submenu_page(
			'woo_whmcs',
			'Test Suspend Account',
			'Test Suspend Account',
			'manage_options',
			'woo_whmcs_test_suspend',
			array($this, 'render_test_suspend')
		);

		add_submenu_page(
			'woo_whmcs',
			'Test Unuspend Account',
			'Test Unuspend Account',
			'manage_options',
			'woo_whmcs_test_unsuspend',
			array($this, 'render_test_unsuspend')
		); */
		/*add_submenu_page(
			'woo_whmcs',
			'Test Get Client Services',
			'Test Get Client Services',
			'manage_options',
			'woo_whmcs_test_get_client',
			array($this, 'render_test_get_client')
		);*/

		
		
	}
	
	public function render_test_get_client(){
	    //include_once WC_WHMCS_PATH . 'includes/inc-form-test-get-client-services.php';
	    $api_client = new Woo_WHMCS_Api_Client();
	    $ret = $api_client->get_client_products(112);
	    
	    echo '<pre>';
	    print_r($ret);
	    echo '</pre>';
	}

	public function render_add_product_service_mapping_form(){
		

		include_once WC_WHMCS_PATH . 'includes/inc-form-service-mapping.php';
	}

	public function render_api_credentials_form(){
		include_once WC_WHMCS_PATH . 'includes/inc-form-api-credentials.php';
	}

	public function render_settings_form() {
		wp_enqueue_script('woo-whmcs-admin-product-service-mapping');
		wp_localize_script('woo-whmcs-admin-product-service-mapping', 'woo_whmcs_app', [
			'settingPageUrl' => admin_url('admin.php?page=woo_whmcs_settings'),
			'formSettingPageUrl' => admin_url('admin.php?page=woo_whmcs_add_product_service_mapping'),
			'ajaxUrl' => admin_url('admin-ajax.php'),
		]);
		
		include_once WC_WHMCS_PATH . 'includes/inc-table-map-product.php';
		
	}

	public function register_admin_script(){
		wp_register_script('woo-whmcs-admin-product-service-mapping', WC_WHMCS_URL .'/assets/js/admin_product_service.js', null, '1.0.0', false);
	}

	private function printTest($str_obj, $title=""){
		echo '<pre>';
		$color = 'green';
		if($title=='response') $color = 'red';
		if($title!="") echo "<strong style='color: $color'>$title:\r\n</strong>";
		echo  json_encode($str_obj, JSON_PRETTY_PRINT);
		if($title=='response') echo "\r\n" . str_repeat('-', 200);
		echo '</pre>';
	}

	public function render_logs_form() {
		include_once WC_WHMCS_PATH . 'includes/inc-form-log.php';
	}

	/* public function render_test_create() {
		include_once WC_WHMCS_PATH . 'includes/inc-form-test-create.php';
		
	}

	public function render_test_suspend(){
		include_once WC_WHMCS_PATH . 'includes/inc-form-test-suspend.php';
	}

	public function render_test_unsuspend(){
		include_once WC_WHMCS_PATH . 'includes/inc-form-test-unsuspend.php';
	} */
}