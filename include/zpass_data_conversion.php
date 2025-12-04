<?php
define('SSG_STUDENT_INDEX', 1);

function extract_studentIDs($data) {
	return extract_one_column($data, SSG_STUDENT_INDEX);
}

define('ZPASS_LAST_NAME_INDEX', 0);
define('ZPASS_FIRST_NAME_INDEX', 1);
define('ZPASS_CARD_INDEX', 2);
define('ZPASS_DATE_INDEX', 8);
define('ZPASS_STUDENT_INDEX', 17);
define('ZPASS_DISTRICT_INDEX', 19);
define('ZPASS_GRADE_INDEX', 20);

define('ZPASS_TIME_INDEX', 'time'); 
define('ZPASS_DAY_INDEX', 'day'); 
define('ZPASS_HOURS_INDEX', 'hours'); 
define('ZPASS_MIN_INDEX', 'min'); 
define('ZPASS_MAX_INDEX', 'max'); 
define('ZPASS_ELAPSED_INDEX', 'elapsed'); 
define('ZPASS_COUNT_INDEX', 'count');
define('ZPASS_SERVICE_NAME_INDEX', 'service_name'); 
define('ZPASS_SERVICE_CODE_INDEX', 'service_code'); 

function extract_relevant_columns($data) {
	$column_list = [
		// ZPASS_LAST_NAME_INDEX,
		// ZPASS_FIRST_NAME_INDEX,
		ZPASS_CARD_INDEX,
		ZPASS_DATE_INDEX,
		ZPASS_STUDENT_INDEX,
		ZPASS_DISTRICT_INDEX,
		ZPASS_GRADE_INDEX,
	];

	return keep_columns_by_indexes($column_list, $data);
}

function split_data_by_id_found($data, $students_ids) {
	return split_rows_by_column_data_present($data, ZPASS_STUDENT_INDEX, $students_ids);
}

function split_zonar_by_grade($data) {
	$split_by_presence = split_rows_by_column_data_present($data, ZPASS_GRADE_INDEX, ['EI', 'SP']);

	$split_by_data = split_rows_by_column_data_value($split_by_presence['ok'], ZPASS_GRADE_INDEX);

	$answer = [
		'EI' => $split_by_data['EI'],
		'SA' => $split_by_data['SP'],				// SP -> SA column rename is by design
		'error' => $split_by_presence['error'],
	];

	return $answer;
}

function filter_data_by_has_id($data) {
	return filter_data_by_column_not_blank(ZPASS_STUDENT_INDEX, $data);
}

function data_add_columns_day_time($data) {
	$header = $data[0];				// first row only
	$body = array_slice($data, 1);	// all rows except first

	$header[ZPASS_DAY_INDEX] = "Day";
	$header[ZPASS_TIME_INDEX] = "Time";
	$header[ZPASS_HOURS_INDEX] = "Hours";

	$answer = [$header];

	foreach ($body as $row) {
		$date = $row[ZPASS_DATE_INDEX];
		list($day, $time) = explode(' ', $date);
		$row[ZPASS_DAY_INDEX] = $day; 
		$row[ZPASS_TIME_INDEX] = $time; 
		$row[ZPASS_HOURS_INDEX] = convert_time_to_hours($time); 
		array_push($answer, $row);
	}

	return $answer;
}

function data_remove_columns_date_time_etc($data) {
	$index_list = [
		ZPASS_CARD_INDEX,
		ZPASS_DATE_INDEX,
		ZPASS_DISTRICT_INDEX,
		ZPASS_TIME_INDEX,
	];
	$output = remove_columns_by_indexes($index_list, $data);
	return $output;
}

function time_spread_per_ID_and_day($data) {
	$header = $data[0];				// first row only
	$body = array_slice($data, 1);	// all rows except first

	# fix header labels ...
	$header[ZPASS_STUDENT_INDEX] = 'StudentCode';
	$header[ZPASS_GRADE_INDEX] = 'Service';
	$header[ZPASS_DAY_INDEX] = 'ServiceDate';

	# ... and add some new ones
	$header[ZPASS_MIN_INDEX] = "Min";
	$header[ZPASS_MAX_INDEX] = "Max";
	$header[ZPASS_ELAPSED_INDEX] = "Elapsed";
	$header[ZPASS_COUNT_INDEX] = "Count";
	$header[ZPASS_SERVICE_NAME_INDEX] = "Service Description";
	$header[ZPASS_SERVICE_CODE_INDEX] = "ServiceType";

	$answer = [$header];
	foreach ($body as $row) {
		$id = $row[ZPASS_STUDENT_INDEX];
		$day = $row[ZPASS_DAY_INDEX];
		$hours = $row[ZPASS_HOURS_INDEX];
		$index = "$id:$day";
		if (! isset($answer[$index])) {
			$answer[$index] = $row;
			$answer[$index][ZPASS_HOURS_INDEX] = '---';
			$answer[$index][ZPASS_MIN_INDEX] = $hours;
			$answer[$index][ZPASS_MAX_INDEX] = $hours;
			$answer[$index][ZPASS_ELAPSED_INDEX] = 0;
			$answer[$index][ZPASS_COUNT_INDEX] = 1;
		} else {
			$min = $answer[$index][ZPASS_MIN_INDEX];
			$max = $answer[$index][ZPASS_MAX_INDEX];
			$min = min($hours, $min);
			$max = max($hours, $max);
			$answer[$index][ZPASS_MIN_INDEX] = $min;
			$answer[$index][ZPASS_MAX_INDEX] = $max;
			$answer[$index][ZPASS_ELAPSED_INDEX] = $max - $min;
			$answer[$index][ZPASS_COUNT_INDEX]++;
		}
		$elapsed = $answer[$index][ZPASS_ELAPSED_INDEX];
		$round_trip = ($elapsed > 2.5);
		$answer[$index][ZPASS_SERVICE_NAME_INDEX] = (
			$round_trip
			? 'RoundTrip'
			: 'OneWay'
		);
		$answer[$index][ZPASS_SERVICE_CODE_INDEX] = (
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
		$value = $row[ZPASS_STUDENT_INDEX];
		if (isset($student_id_replacements[$value])) {
			$value = $student_id_replacements[$value];
			$row[ZPASS_STUDENT_INDEX] = $value;
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
		ZPASS_GRADE_INDEX,
		ZPASS_HOURS_INDEX,
		ZPASS_MIN_INDEX,
		ZPASS_MAX_INDEX,
		ZPASS_ELAPSED_INDEX,
		ZPASS_COUNT_INDEX,
		ZPASS_SERVICE_NAME_INDEX,
		// ZPASS_SERVICE_CODE_INDEX,
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
            $row[ZPASS_STUDENT_INDEX],			// student ID
            $constants['uploaded_by'],		// Provider ID
            $row[ZPASS_DAY_INDEX],				// Service Date
            '',								// Make-Up Date
            '',								// Start Time
            '',								// End Time
            $constants['service_type'],		// Service Type
            $row[ZPASS_SERVICE_CODE_INDEX],		// Service Code
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
