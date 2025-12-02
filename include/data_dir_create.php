<?php
function safe_mkdir($path) {
	if (! is_dir($path)) {
		echo "safe_mkdir(): Create directory '$path'<br />";
		$success = mkdir($path, 0755, true);
		if ($success) {
			echo "-> success<br/>\n";
		} else {
			$error =  error_get_last();
			echo "-> FAIL! error: <b>{$error['message']}</b><br />\n";
		}
	}
}

include "include/data_dir.php";
safe_mkdir($data_dir);
