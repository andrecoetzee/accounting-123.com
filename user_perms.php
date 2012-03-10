<?php

$pg_link = pg_connect("user=postgres password=i56kfm dbname=cubit_aaab");

$users = array("david", "alwyn");

$sql = "SELECT script FROM cubit.userscripts WHERE username='david'";
$script_rslt = pg_exec($sql) or die("Unable to retrieve scripts.");

while (list($script) = pg_fetch_array($script_rslt)) {
	foreach ($users as $username) {
			$sql = "INSERT INTO cubit.userscripts (username, script, div) VALUES ('$username', '$script', '2');";
			print $sql."\n";
	}
}

?>
