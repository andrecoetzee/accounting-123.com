<?php

require ("../settings.php");

if (!isset($_REQUEST["sordid"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
} else {
	$sql = "SELECT stkid, qty FROM cubit.sorders_items WHERE sordid='$_REQUEST[sordid]'";
	$sorder_rslt = db_exec($sql) or errDie("Unable to retrieve order.");

	while (list($stkid, $qty) = pg_fetch_array($sorder_rslt)) {
		$sql = "UPDATE cubit.stock SET alloc=(alloc-'$qty') WHERE stkid='$stkid'";
		db_exec($sql) or errDie("Unable to update stock allocation.");
	}

	$sql = "DELETE FROM cubit.sorders WHERE sordid='$_REQUEST[sordid]'";
	db_exec($sql) or errDie("Unable to update sales order.");
	
	$OUTPUT = "
	<script>
		move(\"sorder-view.php\");
	</script>";
	
	require ("../template.php");
}
