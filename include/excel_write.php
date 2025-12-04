<?php
require_once "vendor/autoload.php";

use \avadim\FastExcelWriter\Excel;

function export_data_as_excel($data, $filename, $sheetname='Sheet1') {
	$excel = Excel::create([$sheetname]);
	$sheet = $excel->sheet();
	foreach ($data as $row) {
		$rowOptions = [
			'height' => 20,
		];
		$sheet->writeRow($row, $rowOptions);
	}
	$excel->save($filename);
	echo "<h2 style='color: green'>DEBUG: saved excel data to '$filename'</h2>\n";
}
