<div class="wrap">
<h2>Test Suspend Service</h2>
<hr class="wp-header-end"/>
<form method="post" action="">
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Service ID</th>
			<td><input type="text" name="serviceid" value="<?=$_POST['serviceid']?>" size="100" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Reason</th>
			<td><input type="text" name="suspendreason" value="<?=$_POST['suspendreason']?>" size="100" /></td>
		</tr>
	</table>
	<p class="submit">
		<input type="submit" class="button-primary" value="OK" />
	</p>
</form>
<?php
if($_POST){
	$service_id = $_POST['serviceid'];
	$reason = $_POST['suspendreason'];
	$service_data = [];
	$api_client = new Woo_WHMCS_Api_Client();
	$result_api = $api_client->get_service_by_id($service_id);
	if(isset($result_api['result']) && $result_api['result'] == 'success'){
		if($result_api['numreturned']==1){
			$service_data = $result_api['products']['product'][0];
		}
	}
	$this->printTest($service_data, 'suspend service');
	$data = [
		'serviceid' => $service_id,
		'suspendreason' => $reason
	];
	$result_api = $api_client->suspend_order($data);
	$this->printTest($result_api, 'response');
}
?>
</div>