<?php
$file_labels_import = [
	'ZPASS' => 'Zpass File',
	'EI_IPE' => 'Early Intervention IPE data',
	'SA_IPE' => 'School-Age IPE data',
];

$file_paths_import = [];
foreach ($file_labels_import as $file_id => $file_label) {
	$real_name = "$data_dir/$file_id.xlsx";
	$file_paths_import[$file_id] = $real_name;
}

function is_hidden_file($basename) {
	return preg_match('/^[.]/', $basename);
}

function is_import_path($full_path) {
	global $file_paths_import;
	return in_array($full_path, $file_paths_import);
}
