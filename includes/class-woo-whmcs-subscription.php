<?php

class Woo_WHMCS_Subscription {
	private $whmcs_api_client;

	public function __construct($whmcs_api_client) {
		$this->whmcs_api_client = $whmcs_api_client;
	}

	public function create_subscription($user_data) {
		// Add logic to create a subscription in WHMCS using the provided user data
		// You can use the $this->whmcs_api_client to interact with the WHMCS API
		// Example code:
		$result = $this->whmcs_api_client->add_user($user_data);

		print_r($result);

		if ($result && $result['result'] == 'success') {
			// Subscription created successfully
			// Add any additional logic here
			return true;
		} else {
			// Failed to create subscription
			// Handle the error and return false
			return false;
		}
	}

	public function test_add_subscription($data){
		$user_data = array(
			'firstname' => $data['first_name'],
			'lastname' => $data['last_name'],
			'email' => $data['email'],
			'password2' => $data['password'], // Set the password for the user
			'language' => 'english' // Set the default language
		);

		$subscription_created = $this->create_subscription($user_data);

		return $subscription_created;
	}

	public function subscription_activation($subscription_id) {
		// Get the subscription details from WooCommerce
		$subscription = wc_get_subscription($subscription_id);

		// Get the customer details from the subscription
		$customer = $subscription->get_customer();

		// Prepare the user data for WHMCS API
		$user_data = array(
			'firstname' => $customer->get_first_name(),
			'lastname' => $customer->get_last_name(),
			'email' => $customer->get_email(),
			'password2' => 'password', // Set the password for the user
			'language' => 'english' // Set the default language
		);

		// Create a subscription in WHMCS
		$subscription_created = $this->create_subscription($user_data);

		if ($subscription_created) {
			// Subscription created successfully
			// You can perform further actions or log the result if needed
			// For example, update the subscription status or send a confirmation email
			$subscription->update_status('active');
			$subscription->add_order_note('WHMCS subscription created successfully.');
		} else {
			// Failed to create subscription
			// Handle the error or log the result if needed
			// For example, update the subscription status or send an error notification
			$subscription->update_status('failed');
			$subscription->add_order_note('Failed to create WHMCS subscription.');
		}
	}

    // Add more methods here to handle other subscription-related operations

}
