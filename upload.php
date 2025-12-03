<?php
require_once "include/az.php";
require_once "include/header.php";

require_once "include/data_month_required.php";
require_once "include/user_name_required.php";

require_once "include/data_dir_create.php";

require_once "include/file_paths_import.php";
require_once "include/error_msg_file_upload.php";

echo "_GET:"; echo "<pre>"; print_r($_GET); echo "</pre>";
echo "_POST:"; echo "<pre>"; print_r($_POST); echo "</pre>";
echo "_FILES:"; echo "<pre>"; print_r($_FILES); echo "</pre>";

foreach ($file_labels_import as $file_id => $file_label) {
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
		echo $error_msg_file_upload[$error];
		echo "<br />\n";
	} else {
		$tmp_name = $File['tmp_name'];
		$orig_name = $File['name'];
		$real_name = $file_paths_import[$file_id];
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
	<form action="upload.php" method="post" enctype="multipart/form-data">
		<input type="hidden" name="data_month" value="<?=$data_month?>">
		<input type="hidden" name="user_name" value="<?=$user_name?>">
		<?php
		foreach ($file_labels_import as $file_id => $file_label) {
			?>
		<tr>
			<td align="right">
				<?php echo $file_label?>
			</td>
			<td>
				<?php
				$file_path = $file_paths_import[$file_id] ?? "";
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