<?php
$product_id = null;
$service_id = null;
$action = (empty($_REQUEST['action'])?'add':$_REQUEST['action']);
if(isset($_REQUEST['product_id']) && isset($_REQUEST['service_id'])){
	$product_id = $_REQUEST['product_id'];
	$service_id = $_REQUEST['service_id'];
}	

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$message = [];
	if(empty($_POST['product_id'])){
		$message[] = "<li>- Product Subcription is required.</li>";
		//echo '<div class="notice notice-warning is-dismissible"><p>Product Subcription is required.</p></div>';
	} 
	
	if(empty($_POST['service_id'])){
		$message[] = "<li>- WHMCS Service is required.</li>";
	} 

	$message = implode("\r\n", $message);

	if(!empty($message)){
		echo "<div class='notice notice-warning is-dismissible'><ul>$message<ul></div>";
	}else{
		$map_data = get_option('whmcs_map_list', array());
		$map_data[$_POST['product_id']] = $_POST['service_id'];
		update_option('whmcs_map_list', $map_data);	
		$url = admin_url('admin.php?page=woo_whmcs_settings');
		header("Location: $url");
	}
	
	/* foreach ($map_data as $product_id => $service_id) {

	}  */
/* }else{
	if(isset($_REQUEST['product_id']) && isset($_REQUEST['service_id'])){
		$product_id = $_GET['product_id'];
		$service_id = $_GET['service_id'];
	}	 */
}





$args = array(
	'limit'  => -1, // All products
	'status' => 'publish',
);
$products = wc_get_products($args);
$subscription = [];
$product_option = '';
foreach ($products as $product) {
	if($product->get_type()=='subscription'){
		/* $subscription[$product->get_id()] = [
			'id' => $product->get_id(),
			'name' => $product->get_name(),
			'description' => $product->get_description()
		]; */

		$selected = '';
		if($product_id == $product->get_id()){
			$selected = ' selected';
		}
		$product_name = esc_attr($product->get_name());
		$product_option .= "<option value='{$product->get_id()}' $selected>{$product_name}</option>\r\n";
	}
}


$api_client = new Woo_WHMCS_Api_Client();
$result_api = $api_client->get_product_service();
$service = [];
$service_option = '';

foreach ($result_api as $items) {
	/* $service[$items['pid']] = [
		'id' => $items['pid'],
		'name' => $items['name'],
		'description' => $items['description']
	]; */
	$selected = '';
	if($service_id == $items['pid']){
		$selected = ' selected';
	}
	
	$service_name = esc_attr($items['name']);
	$service_option .= "<option value='{$items['pid']}'$selected>{$service_name}</option>\r\n";
}
?>
<div class="wrap">
	<h1><?=($action=='edit'?'Edit':'Add')?> Product & Service Mapping</h1>
	<hr class="wp-header-end"/>
	<form method="post" action="">
		<input type="hidden" id="action" name="action" value="<?=$action?>">
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Product Subcription</th>
				<td>
					<select id="product_id" name="product_id">
						<option value=0>– Choose a product subcription –</option>
						<?=$product_option?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">WHMCS Service</th>
				<td>
					<select id="service_id" name="service_id">
						<option value=0>– Choose a service –</option>
						<?=$service_option?>
					</select>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" value="Save Changes" />
		</p>
	</form>
</div>';