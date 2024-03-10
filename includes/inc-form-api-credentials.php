

<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	update_option('whmcs_api_identifier', filter_input(INPUT_POST, 'whmcs_api_identifier'));
	update_option('whmcs_api_secret', filter_input(INPUT_POST, 'whmcs_api_secret'));
	update_option('whmcs_api_url', filter_input(INPUT_POST, 'whmcs_api_url'));
	update_option('whmcs_api_accesskey', filter_input(INPUT_POST, 'whmcs_api_accesskey'));
	update_option('whmcs_grace_periode', filter_input(INPUT_POST, 'whmcs_grace_periode'));

	$api_client = new Woo_WHMCS_Api_Client();

	$result = $api_client->validate_api_credentials();
	if ($result) {
		echo '<div class="notice notice-success is-dismissible"><p>API Connection Successful.</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>API Connection Failed.</p></div>';
	}
}

$api_identifier = get_option('whmcs_api_identifier');
$api_secret = get_option('whmcs_api_secret');
$api_url = get_option('whmcs_api_url');
$api_accesskey = get_option('whmcs_api_accesskey');
$grace_periode = get_option('whmcs_grace_periode');
?>
<div class="wrap">
	<h1>API Credentials</h1>
	<form method="post" action="">
		<table class="form-table">
			<tr valign="top">
				<th scope="row">API Identifier</th>
				<td><input type="password" name="whmcs_api_identifier" value="<?=esc_attr($api_identifier)?>" size="100" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">API Secret</th>
				<td><input type="password" name="whmcs_api_secret" value="<?=esc_attr($api_secret)?>" size="100" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">API URL</th>
				<td><input type="text" name="whmcs_api_url" value="<?=esc_attr($api_url)?>" size="100" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">API Access Key</th>
				<td><input type="password" name="whmcs_api_accesskey" value="<?=esc_attr($api_accesskey)?>" size="100" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Grace Periode (Day)</th>
				<td><input type="number" name="whmcs_grace_periode" value="<?=esc_attr($grace_periode)?>" size="100" /></td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" value="Save Changes" />
		</p>
	</form>
</div>
