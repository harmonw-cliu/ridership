<?php

require_once "include/secrets.php";

echo "<br/>Connecting to MySQL ...\n";

$mysqli = mysqli_connect($mysql_hostname, $mysql_username, $mysql_password, $mysql_database);

// Check connection
if (mysqli_connect_errno()) {
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
	exit();
} else {
	echo "Success.<br/><br/>\n";
}

// $result = mysqli_query($mysqli, "SELECT * FROM contacts ORDER BY id DESC");

// $stmt = $mysqli->prepare("INSERT INTO contacts (name,age,email) VALUES(?, ?, ?)");
// $stmt->bind_param("sis", $name, $age, $email);
// $stmt->execute();

// $stmt = $mysqli->prepare("UPDATE contacts SET name=?, age=?, email=? WHERE id=?");
// $stmt->bind_param("sisi", $name, $age, $email, $id);
// $stmt->execute();

// $stmt = $mysqli->prepare("DELETE FROM contacts WHERE id=?");
// $stmt->bind_param("i", $id);
// $stmt->execute();
