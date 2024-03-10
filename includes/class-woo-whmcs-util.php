<?php

class WhmcsAPIUtil {
	public static function admin_notice_error(){
		echo '<div class="error"><p><strong>WooCommerce WHMCS plugin</strong> requires WooCommerce to be installed and active.</p></div>';
	}

	public static function set_log($message){
		// The error line itself
		$strlog = date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;

		// check if the tikkie dir is present
		if(wp_mkdir_p( WC_WHMCS_LOG_PATH )){
			// check if htaccess is present, if not, create it.
			$htaccess = WC_WHMCS_LOG_PATH . '/.htaccess';
			if(!file_exists($htaccess)){
				$handle = fopen($htaccess,'a+');
				fwrite($handle, 'order deny,allow'.PHP_EOL.'deny from all');
				fclose($handle);
			}

			// Set log file path
			$path = WC_WHMCS_LOG_PATH . '/' . WC_WHMCS_LOG_FILE;
			
			// Create/Open write and start at the beginning
			$handle = fopen($path, 'a+');
			// read the file
			$filesize = filesize($path);
			if($filesize > 0){
				$content = @fread($handle, $filesize);
			}else{
				$content = "";
			}
			
			// insert in array
			$lines = explode(PHP_EOL, $content);
			$count = count($lines);
			//$limit = $LOG_LIMIT;
			$limit = WC_WHMCS_LOG_LIMIT;

			// if more lines than the limit, remove all but the last x lines.
			if($count > $limit){
				// truncate the file
				ftruncate($handle, 0);
				// cut the unwanted lines off the log
				array_splice($lines, 0, 0 - $limit);
				// write back all the lines we want to keep
				fwrite($handle, implode(PHP_EOL, $lines));
			}
			// add the latest error and close
			fwrite($handle, $strlog);
			fclose($handle);
		}
	}

	public static function get_log() {
		$file = WC_WHMCS_LOG_PATH . '/' . WC_WHMCS_LOG_FILE;
		if(file_exists($file)){
			return @file_get_contents($file);
		} else {
			return 'No log file present.';
		}
	}

	public static function randomString($length = 12, $case_sensitive = TRUE){
		if ($case_sensitive) {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+=-';
		} else {
			$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+=-';
		}

		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	public static function get_domain_from_email($order_id, $email){
		$email_part = explode('@', $email);
		//$email_name = str_replace('.', '_', $email_part[0]);
		$email_name = str_replace(str_split('._'), '', $email_part[0]);
		$domain_name = 'betracloud.com';
		$sub_domain = "$email_name$order_id.$domain_name";
		return $sub_domain;
	}

	public static function get_user_from_email($order_id, $email){
		$email_part = explode('@', $email);
		$email_name = str_replace(str_split('._-'), '', $email_part[0]);
		$user_name = $email_name . $order_id;
		return $user_name;
	}

	public static function check_diff_multi($array1, $array2){
		$result = array();
		foreach($array1 as $key => $val) {
			if(isset($array2[$key])){
				if(is_array($val) && $array2[$key]){
					$arr_dif = self::check_diff_multi($val, $array2[$key]);
					if(!empty($arr_dif)){
						$result[$key] = $arr_dif;
					}
				}
			} else {
				$result[$key] = $val;
			}
		}

		return $result;
	}
}

