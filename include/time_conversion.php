<?php

function convert_time_to_hours($time) {
	list($hours, $minutes) = explode(':', $time);
	$hours = (int)$hours;
	$minutes = (int)$minutes;
	return $hours + ($minutes / 24);
}
