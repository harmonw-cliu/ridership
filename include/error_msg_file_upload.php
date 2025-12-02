<?php
$error_msg_file_upload = [
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
