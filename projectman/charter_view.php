<?php

require ("../settings.php");

if (!isset($_REQUEST["project_id"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	require ("../template.php");
}

extract($_REQUEST);

$sql = "SELECT * FROM project.charters WHERE project_id='$project_id'";
$ch_rslt = db_exec($sql) or errDie("Unable to retrieve charter.");
$ch_data = pg_fetch_array($ch_rslt);

print $ch_data["body"];

?>