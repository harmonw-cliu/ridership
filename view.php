<?php
require_once "include/az.php";
require_once "include/header.php";

require_once "include/data_month_required.php";
require_once "include/data_dir_required.php";

require_once "include/file_paths_import.php";

?>
<h2>Files in that directory:</h2>
<table border="1">
<?php

echo "DEBUG: file_paths_import =<pre>"; print_r($file_paths_import); echo "</pre>\n";

$files = scandir($data_dir, SCANDIR_SORT_ASCENDING);
foreach ($files as $file) {
	if (preg_match('/^[.]/', $file)) {
		continue;
	}
	$full_path = $data_dir . "/" . $file;
	?>
	<tr>
		<td>
			<!-- <a href="view.php?data_month=<?=$file?>"><?=$file?></a> -->
			<?=$file?>
		</td>
		<td>
			(<?=$full_path?>)
		</td>
		<td>
			<?php
			if (in_array($full_path, $file_paths_import)) {
				echo "Import (uploaded) file";
			} elseif (preg_match('/^test_excel_output_(EI|SA)_[0-9]+.xlsx$/', $file)) {
				# php note: "1"==yes, "0"==no, "false"==failure
				echo "Export (produced) file";
			} else {
				echo "Other";
			}
			?>
		</td>
	</tr>
	<?php
}
?>
</table>

</body>
</html>