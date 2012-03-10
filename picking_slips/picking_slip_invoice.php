<?php

require ("../settings.php");

$OUTPUT = pos_invoice();
if (!isset($_REQUEST["sordid"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
} elseif (isset($_REQUEST["pos"]) && $_REQUEST["pos"]) {
	$OUTPUT = pos_invoice();
} else {
	$OUTPUT = invoice();
}

require ("../template.php");

function pos_invoice()
{
	extract ($_REQUEST);
	
	pglib_transaction("BEGIN");
	
	$invnum = divlastid("inv");
	
	$sql = "INSERT INTO cubit.pinvoices(deptid, chrgvat, odate, printed, done, 
				username, prd, invnum, div, systime, pslip_sordid, cusnum, ordno)
			VALUES ('".USER_DIV."', 'inc', current_date, 'n', 'n',
				'".USER_NAME."', '".PRD_DB."', '$invnum', '".USER_DIV."',
				current_date, '$sordid', '$cusnum', '$sordid')";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
	$invid = lastinvid();
	
	$sql = "SELECT stock.stkid, stock.whid, qty, sorders_items.vatcode, amt, unitcost
			FROM cubit.sorders_items
				LEFT JOIN cubit.stock ON sorders_items.stkid=stock.stkid
			WHERE sordid='$sordid'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	
	while (list($stkid, $whid, $qty, $vatcode, $amt, $unitcost) = pg_fetch_array($stock_rslt)) {
	
		$sql = "INSERT INTO cubit.pinv_items (invid, whid, stkid, qty, div,
					vatcode, amt, unitcost)
				VALUES ('$invid', '$whid', '$stkid', '$qty', '".USER_DIV."',
					'$vatcode', '$amt', '$unitcost')";
		db_exec($sql) or errDie("Unable to add inventory items.");
	}

	$OUTPUT = "
	<script>
		move(\"../pos-invoice-new-no-neg.php?invid=$invid&cont=1\");
	</script>";
	
	pglib_transaction("COMMIT");
	
	return $OUTPUT;
}			

function invoice()
{
	extract ($_REQUEST);
	
	pglib_transaction("BEGIN");
	
	$invnum = divlastid("inv");

	$sql = "INSERT INTO cubit.invoices(deptid, chrgvat, odate, printed, done, 
				username, prd, invnum, div, systime, pslip_sordid, cusnum, ordno)
			VALUES ('".USER_DIV."', 'inc', current_date, 'n', 'n',
				'".USER_NAME."', '".PRD_DB."', '$invnum', '".USER_DIV."',
				current_date, '$sordid', '$cusnum', '$sordid')";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
	$invid = lastinvid();
	
	$sql = "SELECT stock.stkid, stock.whid, qty, sorders_items.vatcode, amt, unitcost
			FROM cubit.sorders_items
				LEFT JOIN cubit.stock ON sorders_items.stkid=stock.stkid
			WHERE sordid='$sordid'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	
	while (list($stkid, $whid, $qty, $vatcode, $amt, $unitcost) = pg_fetch_array($stock_rslt)) {
	
		$sql = "INSERT INTO cubit.inv_items (invid, whid, stkid, qty, div,
					vatcode, amt, unitcost)
				VALUES ('$invid', '$whid', '$stkid', '$qty', '".USER_DIV."',
					'$vatcode', '$amt', '$unitcost')";
		db_exec($sql) or errDie("Unable to add inventory items.");
	}

	$OUTPUT = "
	<script>
		move(\"../cust-credit-stockinv-no-neg.php?invid=$invid&cont=true\");
	</script>";
	
	pglib_transaction("COMMIT");
	
	return $OUTPUT;
}
