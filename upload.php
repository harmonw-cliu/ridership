<?php
require_once "include/az.php";
require_once "include/header.php";

require_once "include/data_month_required.php";
require_once "include/user_name_required.php";

require_once "include/data_dir_create.php";

$file_labels = [
	'ZPASS' => 'Zpass File',
	'EI_IPE' => 'Early Intervention IPE data',
	'SA_IPE' => 'School-Age IPE data',
];

$error_msg = [
	1 => 'UPLOAD_ERR_INI_SIZE',		// The uploaded file exceeds the upload_max_filesize directive in php.ini
	2 => 'UPLOAD_ERR_FORM_SIZE',	// The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
	3 => 'UPLOAD_ERR_PARTIAL',		// The uploaded file was only partially uploaded.
	4 => 'UPLOAD_ERR_NO_FILE',		// No file was uploaded.
	6 => 'UPLOAD_ERR_NO_TMP_DIR',	// Missing a temporary folder. Introduced in PHP 5.0.3.
	7 => 'UPLOAD_ERR_CANT_WRITE',	// Failed to write file to disk. Introduced in PHP 5.1.0.
	8 => 'UPLOAD_ERR_EXTENSION',	// A PHP extension stopped the file upload.
	// PHP does not provide a way to ascertain which extension caused the file upload to stop;
	// examining the list of loaded extensions with phpinfo() may help.
];

echo "_GET:"; echo "<pre>"; print_r($_GET); echo "</pre>";
echo "_POST:"; echo "<pre>"; print_r($_POST); echo "</pre>";
echo "_FILES:"; echo "<pre>"; print_r($_FILES); echo "</pre>";
$file_paths = [];
foreach ($file_labels as $file_id => $file_label) {
	$real_name = "$data_dir/$file_id.xlsx";
	$file_paths[$file_id] = $real_name;
}

foreach ($file_labels as $file_id => $file_label) {
	$File = $_FILES[$file_id] ?? ['error' => 4];
	$error = $File['error'];
	if ($error == 4) {
		// echo "File '$file_id':<br />\n";
		// echo "... File not passed";
		// echo "<br />\n";
	}
	elseif ($error) {
		echo "File '$file_id':<br />\n";
		echo "... Error #$error: ";
		echo $error_msg[$error];
		echo "<br />\n";
	} else {
		$tmp_name = $File['tmp_name'];
		$orig_name = $File['name'];
		$real_name = $file_paths[$file_id];
		echo "File '$file_id':<br />\n";
		echo "... temp " . "'" . $tmp_name . "'" . "<br />\n";
		echo "... orig " . "'" . $orig_name . "'" . "<br />\n";
		echo "... filepath " . "'" . $real_name . "'" . "<br />\n";
		if (move_uploaded_file($tmp_name, $real_name)) {
			echo "==> MOVED<br />";
		} else {
			echo "==> ERROR!<br />";
		}
		echo "<br />\n";
	}
}
$files_needed = 0;
?>
<table>
	<form action="./" method="post" enctype="multipart/form-data">
		<input type="hidden" name="data_month" value="<?=$data_month?>">
		<input type="hidden" name="user_name" value="<?=$user_name?>">
		<?php
		foreach ($file_labels as $file_id => $file_label) {
			?>
		<tr>
			<td align="right">
				<?php echo $file_label?>
			</td>
			<td>
				<?php
				$file_path = $file_paths[$file_id] ?? "";
				if ($file_path && file_exists($file_path)) {
					?>
				<span style='font-weight:bold'><?=$file_path?></span>
					<?php
				} else {
					?>
				<input type="file" name="<?php echo $file_id?>" value="">
					<?php
					$files_needed++;
				}
				?>
			</td>
		</tr>
			<?php
		}
		if ($files_needed) {
			?>
		<tr>
			<td align="right" style="font-weight: bold; color: red">Files needed: <?=$files_needed?></td>
			<td align="left"><input type="submit" name="submit" value="Upload"></td>
		</tr>
			<?php
		}
		?>
	</form>
	<?php
	if (! $files_needed) {
		$process = $_GET['process'] ?? "";
		if (! $process) {
			?>
	<form action="process.php" method="get">
		<input type="hidden" name="data_month" value="<?=$data_month?>">
		<input type="hidden" name="user_name" value="<?=$user_name?>">
		<input type="hidden" name="process" value="process">
		<tr>
			<td align="right" style="font-weight: bold; color: blue">All files ready.</td>
			<td align="left"><input type="submit" name="submit" value="Process"></td>
		</tr>
	</form>
			<?php
		}
	}
	?>
</table>

</body>
</html>