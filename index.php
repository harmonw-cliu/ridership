<?php
require_once "include/az.php";
require_once "include/header.php";

require_once "include/mysql_connect.php";	// not required here, but we want to fail early if DB is broken

$last_month = strtotime('-1 month');
$default_data_month = date('Y-m', $last_month);

$data_month = $_POST['data_month'] ?? $_GET['data_month'] ?? $default_data_month;

require_once "include/data_dir.php";	# must be after definition of $data_month

?>
<table>
<form action="upload.php" method="get">
	<tr><td align="right">Month for data (YYYY-MM):</td><td><input type="text" name="data_month" value="<?=$data_month?>"></td></tr>
	<tr><td align="right">Your SSG Username:</td><td><input type="text" name="user_name" value=""></td></tr>
	<tr><td colspan="2" align="right"><input type="submit" name="submit" value="Submit"></td></tr>
</form>
</table>

<h2>Or, choose a past report to revisit:</h2>
<ul>
<?php
$directories = scandir($data_root, SCANDIR_SORT_DESCENDING);
foreach ($directories as $dir) {
	if (! preg_match('/^[.]/', $dir)) {
		?>
	<li>
		<a href="view.php?data_month=<?=$dir?>"><?=$dir?></a>
	</li>
		<?php
	}
}
?>
</ul>
</body>
</html>