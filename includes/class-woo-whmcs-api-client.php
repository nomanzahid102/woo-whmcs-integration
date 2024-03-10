<?php

class Woo_WHMCS_Api_Client {
	private $api_url;
	private $api_identifier;
	private $api_secret;
	private $api_accesskey;

	public function __construct() {
		$this->api_url = get_option('whmcs_api_url');
		$this->api_identifier = get_option('whmcs_api_identifier');
		$this->api_secret = get_option('whmcs_api_secret');
		$this->api_accesskey = get_option('whmcs_api_accesskey');
	}

	private function api_request($params) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->api_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		$response = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($response, true);

		return $result;
		//return ($result && $result['result'] == 'success');
	}

	public function get_product_service(){
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => 'GetProducts',
			'responsetype' => 'json',
			'accesskey' => $this->api_accesskey
		);

		$result = $this->api_request($postfields);

		if($result && $result['result'] == 'success'){
			return $result['products']['product'];
		}else{
			return false;
		}
	}

	public function validate_api_credentials() {
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => 'GetHealthStatus',
			'responsetype' => 'json',
			'accesskey' => $this->api_accesskey
		);

		$result = $this->api_request($postfields);
		return ($result && $result['result'] == 'success');
	}

	public function add_user($user_data) {
		$postfields = array_merge(
			array(
					'identifier' => $this->api_identifier,
					'secret' => $this->api_secret,
					'action' => 'AddUser',
					'responsetype' => 'json',
					'accesskey' => $this->api_accesskey
			),
			$user_data
		);
		return $this->api_request($postfields);
	}

	public function get_client($email){
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => 'GetClients',
			'responsetype' => 'json',
			'accesskey' => $this->api_accesskey,
			'search' => $email
			
		);
		return $this->api_request($postfields);
	}
	
	/*public function get_client_domain($clientid){
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => 'GetClientsDomains',
			'responsetype' => 'json',
			'accesskey' => $this->api_accesskey,
			'clientid' => $clientid
		);
		return $this->api_request($postfields);
	}*/
	
	public function get_client_products($clientid){
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => 'GetClientsProducts',
			'responsetype' => 'json',
			'accesskey' => $this->api_accesskey,
			'clientid' => $clientid
			
		);
		return $this->api_request($postfields);
	}
	
	public function update_client_product_status($serviceid, $status){
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => 'UpdateClientProduct',
			'responsetype' => 'json',
			'accesskey' => $this->api_accesskey,
			'serviceid' => $serviceid,
			'status' => $status
		);

		return $this->api_request($postfields);
	}

	public function add_client($client_data){
		$postfields = array_merge(
			array(
				'identifier' => $this->api_identifier,
				'secret' => $this->api_secret,
				'action' => 'AddClient',
				'responsetype' => 'json',
				'accesskey' => $this->api_accesskey
			),
			$client_data
		);
		return $this->api_request($postfields);
	}

	public function add_order($order_data){
		$postfields = array_merge(
			array(
				'identifier' => $this->api_identifier,
				'secret' => $this->api_secret,
				'action' => 'AddOrder',
				'responsetype' => 'json',
				'accesskey' => $this->api_accesskey
			),
			$order_data
		);
		return $this->api_request($postfields);
	}

	public function set_domain($serviceid, $domain){
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => 'UpdateClientProduct',
			'responsetype' => 'json',
			'accesskey' => $this->api_accesskey,
			'serviceid' => $serviceid,
			'domain' => $domain
		);
		return $this->api_request($postfields);
	}

	public function accept_order($order_data){
		$postfields = array_merge(
			array(
				'identifier' => $this->api_identifier,
				'secret' => $this->api_secret,
				'action' => 'AcceptOrder',
				'responsetype' => 'json',
				'accesskey' => $this->api_accesskey
			),
			$order_data
		);
		return $this->api_request($postfields);
	}

	public function get_service_by_id($service_id){
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => 'GetClientsProducts',
			'responsetype' => 'json',
			'accesskey' => $this->api_accesskey,
			'serviceid' => $service_id
		);
		return $this->api_request($postfields);
	}

	public function suspend_order($serviceid, $suspendreason){
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => 'ModuleSuspend',
			'responsetype' => 'json',
			'accesskey' => $this->api_accesskey,
			'serviceid' => $serviceid,
			'suspendreason' => $suspendreason
		);
		return $this->api_request($postfields);
	}

	public function unsuspend_order($serviceid){
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => 'ModuleUnsuspend',
			'responsetype' => 'json',
			'accesskey' => $this->api_accesskey,
			'serviceid' => $serviceid
		);
		return $this->api_request($postfields);
	}
	
	public function module_create($serviceid){
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => 'ModuleCreate',
			'responsetype' => 'json',
			'accesskey' => $this->api_accesskey,
			'serviceid' => $serviceid
		);
		return $this->api_request($postfields);
	}

	public function module_terminate($serviceid){
		$postfields = array(
			'identifier' => $this->api_identifier,
			'secret' => $this->api_secret,
			'action' => 'ModuleTerminate',
			'responsetype' => 'json',
			'accesskey' => $this->api_accesskey,
			'serviceid' => $serviceid
		);
		return $this->api_request($postfields);
	}
}