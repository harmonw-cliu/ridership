<?php
function is_export_path($full_path) {
	$basename = basename($full_path);
	return preg_match('/^test_excel_output_(EI|SA)_[0-9]+.xlsx$/', $basename);
}

function export_file_path($data_dir, $grade, $i) {
	return "{$data_dir}/test_excel_output_{$grade}_{$i}.xlsx";
}
