<?php
require_once WC_WHMCS_PATH . 'includes/class-woo-whmcs-map-table.php';
/* $api_client = new Woo_WHMCS_Api_Client();
$subcription = new Woo_WHMCS_Subscription($api_client);
$data = [
	'first_name' => 'arul',
	'last_name' => 'dev',
	'email' => 'arul@aruldev.net',
	'password' => 'kutukupret'
];

$result = $subcription->test_add_subscription($data);
*/
?>
<div class="wrap">
	<h1 class="wp-heading-inline">Product & Service Mapping</h1>
	<a href="<?=admin_url('admin.php?page=woo_whmcs_add_product_service_mapping')?>" class="page-title-action">Add New</a>
	<hr class="wp-header-end"/>
	
<!-- <div id="import-status" class="notice notice-error" style="display: none">
	<p><strong id="import-status-text"></strong></p>
</div>  -->
	<form id="form-product" method="post">
	<?php
	$woo_whmcs_map_table = new Woo_WHMCS_Map_Table();
	$woo_whmcs_map_table->prepare_items();
	$woo_whmcs_map_table->search_box('Search', 'search');
	$woo_whmcs_map_table->display();
	?>
	</form>
</div>
<!-- if($result==true){
	echo 'Create success:';
}else{
	echo 'Create failed:';
}

echo '<pre>';
echo  json_encode($data, JSON_PRETTY_PRINT);
echo '</pre>'; 

echo '</div>'; -->