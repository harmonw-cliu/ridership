<?php
require_once "include/az.php";
require_once "include/header.php";

require_once "include/data_month_required.php";
require_once "include/data_dir_required.php";

require_once "include/file_paths_import.php";
require_once "include/file_paths_export.php";

function is_visible_file($basename) {
	return (! is_hidden_file($basename));
}

$files = scandir($data_dir, SCANDIR_SORT_ASCENDING);
$files = array_filter($files, 'is_visible_file');

function is_other_path($full_path) {
	if (is_import_path($full_path)) {
		return false;
	} elseif (is_export_path($full_path)) {
		return false;
	} else {
		return true;
	}
}

function full_path($basename) {
	global $data_dir;
	return $data_dir . "/" . $basename;
}

$file_paths = array_map('full_path', $files);

$import_files = array_filter($file_paths, 'is_import_path');
$export_files = array_filter($file_paths, 'is_export_path');
$other_files = array_filter($file_paths, 'is_other_path');
?>

<h2>Files uploaded by user, from ZPass and SSG:</h2>
<?php
?>
<table border="1">
<?php
foreach ($import_files as $full_path) {
	$basename = basename($full_path);
	?>
	<tr>
		<td>
			<!-- <a href="view.php?data_month=<?=$basename?>"><?=$basename?></a> -->
			<?=$basename?>
		</td>
		<td>
			(<?=$full_path?>)
		</td>
	</tr>
	<?php
}
?>
</table>
<?php
?>

<h2>Files produced by Ridership system:</h2>
<?php
?>
<table border="1">
<?php

foreach ($export_files as $full_path) {
	$basename = basename($full_path);
	?>
	<tr>
		<td>
			<!-- <a href="view.php?data_month=<?=$basename?>"><?=$basename?></a> -->
			<?=$basename?>
		</td>
		<td>
			(<?=$full_path?>)
		</td>
		<td>
			export
		</td>
	</tr>
	<?php
}
?>
</table>
<?php
?>

<?php
if ($other_files) {
	?>
<h2>Other (unknown) files:</h2>
<table border="1">
	<?php

	foreach ($other_files as $full_path) {
		$basename = basename($full_path);
		?>
	<tr>
		<td>
			<!-- <a href="view.php?data_month=<?=$basename?>"><?=$basename?></a> -->
			<?=$basename?>
		</td>
		<td>
			(<?=$full_path?>)
		</td>
		<td>
			other
		</td>
	</tr>
		<?php
	}
	?>
</table>
	<?php
}
# else don't show this section
?>

</body>
</html>