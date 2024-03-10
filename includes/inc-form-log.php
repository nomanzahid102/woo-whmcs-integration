<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$file = WC_WHMCS_LOG_PATH . '/' . WC_WHMCS_LOG_FILE;
	if(file_exists($file)){
		$handle = fopen($file, 'w');
		fwrite($handle, "");
		fclose($handle);
	}
}

?>
<div class="wrap">
<h2>Show Log</h2>
<hr class="wp-header-end"/>
<br/>
<textarea readonly style="width: 100%; height: 650px">
<?php
	$message = WhmcsAPIUtil::get_log();
	if(!empty($message)){
		echo $message;
	}else{
		echo 'Log is empty.';
	}
?>
</textarea>
<form method="post" action="">
	<p class="submit">
		<input type="submit" class="button-primary" value="Clear Log" />
	</p>
</form>
<br/>