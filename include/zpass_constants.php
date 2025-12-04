<?php

require_once "include/user_name_required.php";

// strftime is deprecated as of PHP 8.1: use date() or DateTime::format() instead
// python format: "%m/%d/%Y %I:%M:%S %p"
// php format:    "m/d/Y h:i:s A"
// month, day, hour, minute, and second have leading zeros
// year has four digits
// time is in a 12-hour clock with AM/PM at the end
date_default_timezone_set('EST');
$current_timestamp = date("m/d/Y h:i:s A");

$zpass_constants = [
	'global' => [
		'uploaded_by' => $user_name,
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

