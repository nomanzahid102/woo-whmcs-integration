<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

//delete option
delete_option('whmcs_api_identifier');
delete_option('whmcs_api_secret');
delete_option('whmcs_api_url');
delete_option('whmcs_api_accesskey');
delete_option('whmcs_map_list');

function woo_whmcs_removelog($dir) {
	$files = array_diff(scandir($dir), array('.', '..'));
	foreach ($files as $file) {
		(is_dir("$dir/$file")) ? rmrf("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}

$log_base = wp_upload_dir(null, false);
$logfile_dir = $log_base['basedir'] . '/woo-whmcs-log/';

// Clean up by removing dir
woo_whmcs_removelog($logfile_dir);
