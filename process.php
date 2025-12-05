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

require_once "include/zpass_constants.php";

require_once "include/show_missing_files_and_exit.php";

require_once "include/mysql_connect.php";

?>
<h2>Processing ...</h2>
<?php
echo "<hr />\n";

$zpass = load_xls($file_paths_import['ZPASS']);
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

	$zpass_students_data = load_xls($file_paths_import["{$grade}_IPE"]);
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

	$constants_local = array_merge($zpass_constants['global'], $zpass_constants[$grade]);
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