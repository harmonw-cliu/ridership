<?php

require_once "user_name.php";

if (! $user_name) {
	?>
	<h2 style='color:red'>Error: no SSG Username passed</h2>
	Please <a href="index.php?data_month=<?=$data_month?>">enter your SSG username for the report</a>
	<?php
	exit();
} elseif (! preg_match('/^[A-Z]*$/', $user_name)) {
	?>
	<h2 style='color:red'>Error: invalid SSG Username passed</h2>
	Please <a href="index.php?data_month=<?=$data_month?>">enter your SSG username for the report</a>
	<?php
	exit();
} else {
	?>
	<h2 style='color:green'>Your SSG Username: <?=$user_name?></h2>
	<?php
}
