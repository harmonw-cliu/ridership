<?php
// NOTE: there must be NOTHING PRINTED prior to this include file loading,
// or the header() call will silently fail.

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
session_start();

if ($_SERVER["SERVER_NAME"] == "localhost") {
	# bypass signon requirement
	return;
}

if (!$_SESSION["az_user_data"]["userPrincipalName"]) {
	header("Location: https://ridership.cliu.org/az_auth.php");
	die();
}

// Force HTTPS for security

if ($_SERVER["HTTPS"] != "on") {
	$pageURL = "Location: https://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
	}

	header($pageURL);
}
?>