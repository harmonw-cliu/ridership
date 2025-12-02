<?php
require_once "include/az.php";
require_once "include/header.php";

require_once "include/data_month_required.php";
require_once "include/data_dir_required.php";

?>
<h2>Files in that directory:</h2>
<table border="1">
<?php
$files = scandir($data_dir, SCANDIR_SORT_ASCENDING);
foreach ($files as $file) {
	if (! preg_match('/^[.]/', $file)) {
		?>
	<tr>
		<td>
		<!-- <a href="view.php?data_month=<?=$file?>"><?=$file?></a> -->
		<?=$file?>
		</td>
		<?php
	}
	</tr>
	<?php
}
?>
</table>

</body>
</html>