<div class="wrap">
<h2>Test Create Client</h2>
<hr class="wp-header-end"/>
<form method="post" action="">
	<table class="form-table">
		<tr valign="top">
			<th scope="row">First Name</th>
			<td><input type="text" name="firstname" value="<?=$_POST['firstname']?>" size="100" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Last Name</th>
			<td><input type="text" name="lastname" value="<?=$_POST['lastname']?>" size="100" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Email</th>
			<td><input type="text" name="email" value="<?=$_POST['email']?>" size="100" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Address</th>
			<td><input type="text" name="address1" value="<?=$_POST['address1']?>" size="100" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">City</th>
			<td><input type="text" name="city" value="<?=$_POST['city']?>" size="100" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">State</th>
			<td><input type="text" name="state" value="<?=$_POST['state']?>" size="100" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Postcode</th>
			<td><input type="text" name="postcode" value="<?=$_POST['postcode']?>" size="100" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Country</th>
			<td><input type="text" name="country" value="<?=$_POST['country']?>" size="100" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Phonenumber</th>
			<td><input type="text" name="phonenumber" value="<?=$_POST['phonenumber']?>" size="100" /></td>
		</tr>
	</table>
	<p class="submit">
		<input type="submit" class="button-primary" value="Save Changes" />
	</p>
</form>
<?php
if($_POST){
	//set email to test
	$data = [
		'firstname' => $_POST['firstname'],
		'lastname' => $_POST['lastname'],
		'email' => $_POST['email'],
		'address1' => $_POST['address1'],
		'city' => $_POST['city'],
		'state' => $_POST['state'],
		'postcode' => $_POST['postcode'],
		'country' => $_POST['country'],
		'phonenumber' => $_POST['phonenumber'],
		'password2' => WhmcsAPIUtil::randomString()
	];

	$pid = 1;
	$paymentmethod = 'paypal';
	$domain = WhmcsAPIUtil::get_domain_from_email($data['email']);

	//check user exists based on email
	$api_client = new Woo_WHMCS_Api_Client();
	$this->printTest($data['email'], 'account check if exists');
	$result_api = $api_client->get_client($data['email']);
	$this->printTest($result_api, 'response');
	if(isset($result_api['result']) && $result_api['result'] == 'success'){
		$is_user_exists = $result_api['totalresults'] == 1;
		$client_id = $result_api['clients']['client'][0]['id'];
	}else{
		$is_user_exists = false;
		$client_id = null;
	}

	//if exists then get client_id variable
	if($is_user_exists == true){
		$this->printTest(['client_id' => $client_id], 'data');
	}else{
		$this->printTest($data, 'add client');
		$result_api = $api_client->add_client($data);
		$this->printTest($result_api, 'response');

		if(isset($result_api['result']) && $result_api['result'] == 'success'){
			$client_id = $result_api['clientid'];
		}else{
			$client_id = null;
		}
	}

	//add order
	if(!empty($client_id)){
		$order_data = [
			'clientid' => $client_id,
			'pid' => $pid,
			'paymentmethod' => $paymentmethod,
			'domain' => $domain
		];
		$this->printTest($order_data, 'add order');
		$result_api = $api_client->add_order($order_data);
		$this->printTest($result_api, 'response');
		if(isset($result_api['result']) && $result_api['result'] == 'success'){
			$order_id = $result_api['orderid'];
		}else{
			$order_id = null;
		}
	}else{
		$order_id = null;
	}

	//accept order
	if(!empty($order_id)){
		$order_data = [
			'orderid' => $order_id,
			'sendemail' => true,
			'autosetup' => true
		];
		$this->printTest($order_data, 'accept order');
		$result_api = $api_client->accept_order($order_data);
		$this->printTest($result_api, 'response');
	}
}
?>
</div>
