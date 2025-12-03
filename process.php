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

require_once "vendor/autoload.php";

use Shuchkin\SimpleXLSX;
use \avadim\FastExcelWriter\Excel;

define('LAST_NAME_INDEX', 0);
define('FIRST_NAME_INDEX', 1);
define('CARD_INDEX', 2);
define('DATE_INDEX', 8);
define('STUDENT_INDEX', 17);
define('DISTRICT_INDEX', 19);
define('GRADE_INDEX', 20);

define('TIME_INDEX', 'time'); 
define('DAY_INDEX', 'day'); 
define('HOURS_INDEX', 'hours'); 
define('MIN_INDEX', 'min'); 
define('MAX_INDEX', 'max'); 
define('ELAPSED_INDEX', 'elapsed'); 
define('COUNT_INDEX', 'count');
define('SERVICE_NAME_INDEX', 'service_name'); 
define('SERVICE_CODE_INDEX', 'service_code'); 

function dump_data($data, $label='dump_data()') {
	echo '<table border="1">';
	$row_num = 0;
    foreach ($data as $row) {
    	echo '<tr>';
    	echo "<td>$row_num</td>";
    	if (is_array($row)) {
	    	if ($row_num == 0) {
		    	foreach ($row as $column_num => $cell) {
			    	echo '<td style="text-align: center">';
			    	echo $column_num;
			    	echo '<br />';
			    	if (is_array($cell)) {
			    		print_r($cell);
			    	} else {
			    		echo $cell;
			    	}
			    	echo '</td>';
		    	}
	    	} else {
		    	foreach ($row as $cell) {
			    	echo '<td>';
			    	if (is_array($cell)) {
			    		print_r($cell);
			    	} else {
			    		echo $cell;
			    	}
			    	echo '</td>';
		    	}
	    	}
    	} else {
	    	if ($row_num == 0) {
		    	echo '<td style="text-align: center">';
		    	echo '(strings)';
		    	echo '<br />';
		    	echo $row;
		    	echo '</td>';
	    	} else {
		    	echo '<td>';
		    	echo $row;
		    	echo '</td>';
	    	}
    	}
    	$row_num++;
    	echo '</tr>';
    }
    echo '</table>';
}

function dump_data_hidden($data, $span_id, $label='dump_data_hidden()') {
	// requires toggle_visibility() from ridership.js
	$records = count($data) - 1;
	?>
	<div id="<?=$span_id?>_master">
		<div id="<?=$span_id?>_label">
			<h2>
				<?=$label?>:
				(<?=$records?> records)
				<button onclick="toggle_visibility('<?=$span_id?>')">Hide/Show</button>
			</h2>
		</div>
		<div id="<?=$span_id?>" style="display:none">
	<?php
	dump_data($data, $label);
	?>
		</div>
	</div>
	<?php
}

function load_xls($filename) {
	// echo "load_xls($filename)<br/>\n";
	if ( $xlsx = SimpleXLSX::parse($filename) ) {
		return $xlsx->rows();
	} else {
	    echo SimpleXLSX::parseError();
	    return [[]];
	}
}

function dump_xls($filename) {
	$data = load_xls($filename);
	dump_data($data);
}

function extract_studentIDs($filename) {
	$data = load_xls($filename);
	$ids_column = array_map(
		fn($row) => $row[1],
		$data,
	);
	// $header = ;
	return $ids_column;
}

function remove_columns_by_indexes($column_list, $data) {
	$filter_column_remove = function ($value, $key) use ($column_list) {
		return (! in_array($key, $column_list));
	};
	$filter_rows = fn($row) => array_filter($row, $filter_column_remove, ARRAY_FILTER_USE_BOTH);
	return array_map($filter_rows, $data);
}

function keep_columns_by_indexes($column_list, $data) {
	$filter_columns_keep = function ($value, $key) use ($column_list) {
		return in_array($key, $column_list);
	};
	$filter_rows = fn($row) => array_filter($row, $filter_columns_keep, ARRAY_FILTER_USE_BOTH);
	return array_map($filter_rows, $data);
}

function extract_relevant_columns($data) {
	$column_list = [
		// LAST_NAME_INDEX,
		// FIRST_NAME_INDEX,
		CARD_INDEX,
		DATE_INDEX,
		STUDENT_INDEX,
		DISTRICT_INDEX,
		GRADE_INDEX,
	];

	return keep_columns_by_indexes($column_list, $data);
}

function split_rows_by_column_data_present($data, $column_index, $allow_list) {
	# returns 'ok' and 'error' sections for column $column_index value being present or absent in allow_list
	$header = $data[0];				// first row only
	$body = array_slice($data, 1);	// all rows except first
	$answer = [];

	foreach (['ok', 'error'] as $bucket) {
		$answer[$bucket] = [$header];
	}

	foreach ($data as $row) {
		$value = $row[$column_index];
		$found = in_array($value, $allow_list);
		$bucket = ($found ? 'ok' : 'error');
		array_push($answer[$bucket], $row);
	}

	return $answer;
}

function split_rows_by_column_data_value($data, $column_index) {
	# returns separate sections for each value in column $column_index
	$header = $data[0];				// first row only
	$body = array_slice($data, 1);	// all rows except first
	$answer = [];
	foreach ($data as $row) {
		$value = $row[$column_index];
		if (! isset($answer[$value])) {
			$answer[$value] = [$header];
		}
		array_push($answer[$value], $row);
	}

	return $answer;
}

function split_data_by_id_found($data, $students_ids) {
	return split_rows_by_column_data_present($data, STUDENT_INDEX, $students_ids);
}

function split_zonar_by_grade($data) {
	$split_by_presence = split_rows_by_column_data_present($data, GRADE_INDEX, ['EI', 'SP']);

	$split_by_data = split_rows_by_column_data_value($split_by_presence['ok'], GRADE_INDEX);

	$answer = [
		'EI' => $split_by_data['EI'],
		'SA' => $split_by_data['SP'],				// SP -> SA column rename is by design
		'error' => $split_by_presence['error'],
	];

	return $answer;
}

function filter_data_by_column_not_blank($column_id, $data) {
	$header = $data[0];				// first row only
	$body = array_slice($data, 1);	// all rows except first
	$answer = [
		'ok' => [$header],
		'error' => [$header],
	];
	foreach ($body as $row) {
		$value = $row[$column_id];
		$group = ($value !== "") ? 'ok' : 'error';
		array_push($answer[$group], $row);
	}
	return $answer;
}

function filter_data_by_has_id($data) {
	return filter_data_by_column_not_blank(STUDENT_INDEX, $data);
}

function convert_time_to_hours($time) {
	list($hours, $minutes) = explode(':', $time);
	$hours = (int)$hours;
	$minutes = (int)$minutes;
	return $hours + ($minutes / 24);
}

function data_add_columns_day_time($data) {
	$header = $data[0];				// first row only
	$body = array_slice($data, 1);	// all rows except first

	$header[DAY_INDEX] = "Day";
	$header[TIME_INDEX] = "Time";
	$header[HOURS_INDEX] = "Hours";

	$answer = [$header];

	foreach ($body as $row) {
		$date = $row[DATE_INDEX];
		list($day, $time) = explode(' ', $date);
		$row[DAY_INDEX] = $day; 
		$row[TIME_INDEX] = $time; 
		$row[HOURS_INDEX] = convert_time_to_hours($time); 
		array_push($answer, $row);
	}

	return $answer;
}

function time_spread_per_ID_and_day($data) {
	$header = $data[0];				// first row only
	$body = array_slice($data, 1);	// all rows except first

	# fix header labels ...
	$header[STUDENT_INDEX] = 'StudentCode';
	$header[GRADE_INDEX] = 'Service';
	$header[DAY_INDEX] = 'ServiceDate';

	# ... and add some new ones
	$header[MIN_INDEX] = "Min";
	$header[MAX_INDEX] = "Max";
	$header[ELAPSED_INDEX] = "Elapsed";
	$header[COUNT_INDEX] = "Count";
	$header[SERVICE_NAME_INDEX] = "Service Description";
	$header[SERVICE_CODE_INDEX] = "ServiceType";

	$answer = [$header];
	foreach ($body as $row) {
		$id = $row[STUDENT_INDEX];
		$day = $row[DAY_INDEX];
		$hours = $row[HOURS_INDEX];
		$index = "$id:$day";
		if (! isset($answer[$index])) {
			$answer[$index] = $row;
			$answer[$index][HOURS_INDEX] = '---';
			$answer[$index][MIN_INDEX] = $hours;
			$answer[$index][MAX_INDEX] = $hours;
			$answer[$index][ELAPSED_INDEX] = 0;
			$answer[$index][COUNT_INDEX] = 1;
		} else {
			$min = $answer[$index][MIN_INDEX];
			$max = $answer[$index][MAX_INDEX];
			$min = min($hours, $min);
			$max = max($hours, $max);
			$answer[$index][MIN_INDEX] = $min;
			$answer[$index][MAX_INDEX] = $max;
			$answer[$index][ELAPSED_INDEX] = $max - $min;
			$answer[$index][COUNT_INDEX]++;
		}
		$elapsed = $answer[$index][ELAPSED_INDEX];
		$round_trip = ($elapsed > 2.5);
		$answer[$index][SERVICE_NAME_INDEX] = (
			$round_trip
			? 'RoundTrip'
			: 'OneWay'
		);
		$answer[$index][SERVICE_CODE_INDEX] = (
			$round_trip
			? 'T2'
			: 'T1'
		);
	}
	ksort($answer);		// sort by key == by student ID, then by day
	return $answer;
}

function replace_student_ids($data, $student_id_replacements) {
	$fix_row = function($row) use ($student_id_replacements) {
		$value = $row[STUDENT_INDEX];
		if (isset($student_id_replacements[$value])) {
			$value = $student_id_replacements[$value];
			$row[STUDENT_INDEX] = $value;
		}
		return $row;
	};
	return array_map(
		$fix_row,
		$data,
	);
}

function zpass_clean($data, $student_id_replacements) {
	# delete the columns we don't care about anymore
	$column_list = [
		GRADE_INDEX,
		HOURS_INDEX,
		MIN_INDEX,
		MAX_INDEX,
		ELAPSED_INDEX,
		COUNT_INDEX,
		SERVICE_NAME_INDEX,
		// SERVICE_CODE_INDEX,
	];
	$data = remove_columns_by_indexes($column_list, $data);

	$data = replace_student_ids($data, $student_id_replacements);

	return $data;
}

function zpass_output($data, $constants) {
	$header_discard = $data[0];				// first row only
	$body = array_slice($data, 1);	// all rows except first
	$header = [
		"District CD",
		"Student ID",
		"Provider ID",
		"Service Date",
		"Make-Up Date",
		"Start Time",
		"End Time",
		"Service Type",
		"Service Code",
		"Group Size",
		"Therapy Method",
		"Therapy Method2",
		"Diagnosis Code",
		"Place of Service CD",
		"Place of Service Description",
		"School CD",
		"Progress",
		"Therapy Notes",
		"Entered by ID",
		"Entered Date",
		"Approved?",
		"Approver ID",
		"Approved Date",
		"Reference Number",
	];
	$answer = [$header];

	echo "<pre>DEBUG: Constants = "; print_r($constants); echo "</pre>\n";

	foreach ($body as $row) {
		$answer_row = [
            $constants['district_code'],
            $row[STUDENT_INDEX],			// student ID
            $constants['uploaded_by'],		// Provider ID
            $row[DAY_INDEX],				// Service Date
            '',								// Make-Up Date
            '',								// Start Time
            '',								// End Time
            $constants['service_type'],		// Service Type
            $row[SERVICE_CODE_INDEX],		// Service Code
            '',								// Group Size
            '',								// Therapy Method
            '',								// Therapy Method2
            $constants['diagnosis_code'],	// Diagnosis Code
            '',								// Place of Service CD
            '',								// Place of Service Description
            '',								// School CD
            '',								// Progress
            '',								// Therapy Notes
            $constants['uploaded_by'],		// Entered by ID
            $constants['timestamp'],		// Entered Date
            '',								// Approved?
            '',								// Approver ID
            '',								// Approved Date
            '',								// Reference Number
		];
		array_push($answer, $answer_row);
	}

	return $answer;
}

function split_data_at_row_count($data, $max_rows) {
	echo "DEBUG: split_data_at_row_count(" . count($data) . ", " . $max_rows . ")<br />\n";

	$output = [];
	if (count($data) <= $max_rows) {
		array_push($output, $data);
	} else {
		$header = $data[0];				// first row only
		$body = array_slice($data, 1);	// all rows except first
		$chunks = array_chunk($body, $max_rows);
		foreach ($chunks as $batch) {
			array_unshift($batch, $header);
			array_push($output, $batch);
		}
	}
	return $output;
}

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

// strftime is deprecated as of PHP 8.1: use date() or DateTime::format() instead
// python format: "%m/%d/%Y %I:%M:%S %p"
// php format:    "m/d/Y h:i:s A"
// month, day, hour, minute, second have leading zeros
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

// echo "az_user_data:<pre>"; print_r($_SESSION["az_user_data"]); echo "</pre>\n";
// echo "_GET:"; echo "<pre>"; print_r($_GET); echo "</pre>";
// echo "_POST:"; echo "<pre>"; print_r($_POST); echo "</pre>";
// echo "_FILES:"; echo "<pre>"; print_r($_FILES); echo "</pre>";

$file_paths = [];
foreach ($file_labels as $file_id => $file_label) {
	$file_name = "$file_id.xlsx";
	$real_name = "$data_dir/$file_name";
	$file_paths[$file_id] = $real_name;
}
$files_needed = 0;
?>
<table>
	<tr>
		<td align="right">Month for data (YYYY-MM)</td>
		<td><span style='font-weight:bold'><?=$data_month?></span></td>
	</tr>
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
			<span style='font-weight:bold'><?=$file_name?></span>
			<?php
		} else {
			?>
			<span style='font-weight:bold; color:red'><?=$file_name?> MISSING</span>
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
		<td colspan="2" align="right">
			<form action="upload.php" method="post" enctype="multipart/form-data">
				<input type="hidden" name="data_month" value="<?=$data_month?>">
				<input type="submit" name="submit" value="Upload More Files">
			</form>
		</td>
	</tr>
		<?php
		exit();
	}
	// $process = $_GET['process'] ?? "";
	// if (! $process) { throw_an_error(); }	// we don't actually care about verifying this
?>
</table>
<h2>Processing ...</h2>
<?php
echo "<hr />\n";

$zpass = load_xls($file_paths['ZPASS']);
// echo 'zpass_data: ' . count($zpass) . "<br/>\n";

$zpass = extract_relevant_columns($zpass);
dump_data_hidden($zpass, 'zpass', 'zpass ALL RECORDS');
$zpass_split = split_zonar_by_grade($zpass);
dump_data_hidden($zpass_split['error'], 'zpass_err', 'zpass ERROR no Grade (EI/SA)');

$index_list = [
	CARD_INDEX,
	DATE_INDEX,
	DISTRICT_INDEX,
	TIME_INDEX,
];

# Changed per Robin Miller's instruction; PAID changed
$student_id_replacements = [
	3153276528 => 5623149936,
];

foreach (['EI', 'SA'] as $grade) {
	echo "<hr />\n";
	echo "<h2>grade = '$grade':</h2>\n";

	$zpass_students = extract_studentIDs($file_paths["{$grade}_IPE"]);
	dump_data_hidden($zpass_students, "students_{$grade}", "students $grade");

	dump_data_hidden($zpass_split[$grade], "zpass_{$grade}", "zpass $grade (all)");
	$zpass_filtered = filter_data_by_has_id($zpass_split[$grade]);

	dump_data_hidden($zpass_filtered['error'], "zpass_{$grade}_err", "zpass $grade ERROR no ID");
	dump_data_hidden($zpass_filtered['ok'], "zpass_{$grade}_ok", "zpass $grade OK");

	$zpass_with_id_found = split_data_by_id_found($zpass_filtered['ok'], $zpass_students);

	dump_data_hidden($zpass_with_id_found['error'], "zpass_{$grade}_not_found", "zpass $grade ID not found in student list");
	dump_data_hidden($zpass_with_id_found['ok'], "zpass_{$grade}_found", "zpass $grade with ID in student list");

	$zpass_with_date = data_add_columns_day_time($zpass_with_id_found['ok']);
	$zpass_with_date = remove_columns_by_indexes($index_list, $zpass_with_date);
	dump_data_hidden($zpass_with_date, "zpass_{$grade}_date", "zpass $grade with date");

	$zpass_split_id_day = time_spread_per_ID_and_day($zpass_with_date);
	dump_data_hidden($zpass_split_id_day, "zpass_{$grade}_split", "zpass $grade split by ID and day");

	$zpass_clean = zpass_clean($zpass_split_id_day, $student_id_replacements);
	dump_data_hidden($zpass_clean, "zpass_{$grade}_clean", "zpass $grade cleaned up columns");

	$constants_local = array_merge($constants['global'], $constants[$grade]);
	$zpass_output_all = zpass_output($zpass_clean, $constants_local);[$grade];
	dump_data_hidden($zpass_output_all, "zpass_{$grade}_output", "zpass $grade for output");

	$max_rows = 1000;
	$zpass_output_split = split_data_at_row_count($zpass_output_all, $max_rows);
	foreach ($zpass_output_split as $i => $batch) {
		dump_data_hidden($batch, "zpass_{$grade}_output_{$i}", "zpass $grade for output #$i");
	}

	foreach ($zpass_output_split as $i => $batch) {
		$filename = export_file_path($data_dir, $grade, $i);
		export_data_as_excel($batch, $filename, 'Sheet Name Goes Here');
	}
}

function export_file_path($data_dir, $grade, $i) {
	return "upload/2025-10/test_excel_output_{$grade}_{$i}.xlsx";
}

echo "<hr />\n";

?>
</body>
</html>