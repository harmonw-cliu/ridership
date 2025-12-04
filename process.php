<?php
// NOTE: we're doing a student ID translation for one student,
// (3153276528 -> 5623149936   # Changed per Robin Miller's instruction; PAID changed)
// and it's unclear whether this needs to happen *prior* to
// looking each student up in the Medicare files or *afterwards*.

require_once "include/az.php";
require_once "include/header.php";

require_once "include/data_month_required.php";
require_once "include/user_name_required.php";
require_once "include/data_dir_required.php";

require_once "include/file_paths_import.php";
require_once "include/file_paths_export.php";

require_once "include/array_data_processing.php";
require_once "include/show_array.php";

require_once "include/time_conversion.php";
require_once "include/zpass_data_conversion.php";

require_once "include/excel_read.php";
require_once "include/excel_write.php";

// strftime is deprecated as of PHP 8.1: use date() or DateTime::format() instead
// python format: "%m/%d/%Y %I:%M:%S %p"
// php format:    "m/d/Y h:i:s A"
// month, day, hour, minute, and second have leading zeros
// year has four digits
// time is in a 12-hour clock with AM/PM at the end
date_default_timezone_set('EST');
$current_timestamp = date("m/d/Y h:i:s A");

$constants = [
	'global' => [
		'uploaded_by' => 'DRIGHI',
		'timestamp' => $current_timestamp,
	],
	'EI' => [
		'district_code' => '1072E',
		'service_type' => 'ETR',
		'diagnosis_code' => 'R6250',
	],
	'SA' => [
		'district_code' => '1072',
		'service_type' => 'TR',
		'diagnosis_code' => 'R6889',
	],
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

// echo "az_user_data:<pre>"; print_r($_SESSION["az_user_data"]); echo "</pre>\n";
// echo "_GET:"; echo "<pre>"; print_r($_GET); echo "</pre>";
// echo "_POST:"; echo "<pre>"; print_r($_POST); echo "</pre>";
// echo "_FILES:"; echo "<pre>"; print_r($_FILES); echo "</pre>";

$file_paths = [];
foreach ($file_labels_import as $file_id => $file_label) {
	$file_name = "$file_id.xlsx";
	$real_name = "$data_dir/$file_name";
	$file_paths[$file_id] = $real_name;
}
$files_missing = [];
foreach ($file_labels_import as $file_id => $file_label) {
	$file_path = $file_paths[$file_id] ?? "";
	if ($file_path && file_exists($file_path)) {
		continue;
	} else {
		$files_missing[$file_id] = $file_path;
	}
}
if ($files_missing) {
	?>
<table>
	<?php
	foreach ($files_missing as $file_id => $file_path) {
		$file_label = $file_labels_import[$file_id];
		?>
	<tr>
		<td align="right">
			<?php echo $file_label?>:
		</td>
		<td>
			<span style='font-weight:bold; color:red'><?=$file_path?> MISSING</span>
		</td>
	</tr>
		<?php
	}
	?>
	<tr>
		<td align="right">
			<span style="font-weight: bold; color: red;">Files missing: <?=count($files_missing)?></span>
		</td>
		<td>
			<form action="upload.php" method="post" enctype="multipart/form-data">
				<input type="hidden" name="data_month" value="<?=$data_month?>">
				<input type="submit" name="submit" value="Upload More Files">
			</form>
		</td>
	</tr>
	<?php
	exit();
} else {
	?>
<table>
	<?php
	foreach ($file_labels_import as $file_id => $file_label) {
		?>
	<tr>
		<td align="right">
			<?php echo $file_label?>
		</td>
		<td>
			<span style='font-weight:bold'><?=$file_name?></span>
		</td>
	</tr>
		<?php
	}
	?>
</table>
	<?php
}
?>
<h2>Processing ...</h2>
<?php
echo "<hr />\n";

$zpass = load_xls($file_paths['ZPASS']);
// echo 'zpass_data: ' . count($zpass) . "<br/>\n";

$zpass = extract_relevant_columns($zpass);
show_array_hidden($zpass, 'zpass', 'zpass ALL RECORDS');
$zpass_split = split_zonar_by_grade($zpass);
show_array_hidden($zpass_split['error'], 'zpass_err', 'zpass ERROR no Grade (EI/SA)');

# Changed per Robin Miller's instruction; PAID changed
$student_id_replacements = [
	3153276528 => 5623149936,
];

foreach (['EI', 'SA'] as $grade) {
	echo "<hr />\n";
	echo "<h2>grade = '$grade':</h2>\n";

	$zpass_students_data = load_xls($file_paths["{$grade}_IPE"]);
	$zpass_students = extract_studentIDs($zpass_students_data);
	show_array_hidden($zpass_students, "students_{$grade}", "students $grade");

	show_array_hidden($zpass_split[$grade], "zpass_{$grade}", "zpass $grade (all)");
	$zpass_filtered = filter_data_by_has_id($zpass_split[$grade]);

	show_array_hidden($zpass_filtered['error'], "zpass_{$grade}_err", "zpass $grade ERROR no ID");
	show_array_hidden($zpass_filtered['ok'], "zpass_{$grade}_ok", "zpass $grade OK");

	$zpass_with_id_found = split_data_by_id_found($zpass_filtered['ok'], $zpass_students);

	show_array_hidden($zpass_with_id_found['error'], "zpass_{$grade}_not_found", "zpass $grade ID not found in student list");
	show_array_hidden($zpass_with_id_found['ok'], "zpass_{$grade}_found", "zpass $grade with ID in student list");

	$zpass_with_date = data_add_columns_day_time($zpass_with_id_found['ok']);
	$zpass_with_date = data_remove_columns_date_time_etc($zpass_with_date);
	show_array_hidden($zpass_with_date, "zpass_{$grade}_date", "zpass $grade with date");

	$zpass_split_id_day = time_spread_per_ID_and_day($zpass_with_date);
	show_array_hidden($zpass_split_id_day, "zpass_{$grade}_split", "zpass $grade split by ID and day");

	$zpass_clean = zpass_clean($zpass_split_id_day, $student_id_replacements);
	show_array_hidden($zpass_clean, "zpass_{$grade}_clean", "zpass $grade cleaned up columns");

	$constants_local = array_merge($constants['global'], $constants[$grade]);
	$zpass_output_all = zpass_output($zpass_clean, $constants_local);[$grade];
	show_array_hidden($zpass_output_all, "zpass_{$grade}_output", "zpass $grade for output");

	$max_rows = 1000;
	$zpass_output_split = split_data_at_row_count($zpass_output_all, $max_rows);
	foreach ($zpass_output_split as $i => $batch) {
		show_array_hidden($batch, "zpass_{$grade}_output_{$i}", "zpass $grade for output #$i");
	}

	foreach ($zpass_output_split as $i => $batch) {
		$filename = export_file_path($data_dir, $grade, $i);
		export_data_as_excel($batch, $filename, 'Sheet Name Goes Here');
	}
}

echo "<hr />\n";

?>
</body>
</html>