<?php
require_once "include/data_dir.php";
require_once "include/user_name.php";

function verify_dir($path) {
	global $data_month;
	global $user_name;

	if (! is_dir($path)) {
		?>
	<h1>Error: directory '<?=$data_month?>' does not exist!</h1>
	Please <a href="upload.php?data_month=<?=$data_month?>&user_name=<?=$user_name?>">upload the files for the report</a>
		<?php
		exit();
	}
}

verify_dir($data_dir);
