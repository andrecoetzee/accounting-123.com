<?php

require ("settings.php");
require ("core-settings.php");

if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);
	
	switch ($button) {
		case "complete":
			$OUTPUT = complete();
			break;
	}
} elseif (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "adjust_display":
			$OUTPUT = adjust_display();
			break;
		case "adjust_update":
			$OUTPUT = adjust_update();
			break;
	}
} else {
	$OUTPUT = adjust_display();
}

$OUTPUT .= mkQuicklinks (
	ql("stock-add.php", "Add Stock"),
	ql("stock-view.php", "View Stock")
);

require ("template.php");

function adjust_display()
{
	extract ($_REQUEST);
	
	$fields = array();
	$fields["page"] = 1;
	
	extract ($fields, EXTR_SKIP);
	
	$sql = "SELECT stock.stkid, bar, stkcod, stkdes, (qty-units) AS adjust_qty,
				csprice
			FROM cubit.stock_take
				LEFT JOIN cubit.stock ON stock.stkid=stock_take.stkid
			WHERE adjusted='0' AND page='$page'
			ORDER BY stkcod ASC";
	$adjust_rslt = db_exec($sql) or errDie("Unable to retrieve adjustments.");
	
	$adjust_out = "";
	while ($adjust_data = pg_fetch_array($adjust_rslt)) {
		if ($adjust_data["adjust_qty"] == 0) continue;
	
		$value = $adjust_data["csprice"] * $adjust_data["adjust_qty"];
	
		$adjust_out .= "
		<tr class='".bg_class()."'>
			<td>$adjust_data[bar]</td>
			<td>$adjust_data[stkcod]</td>
			<td>$adjust_data[stkdes]</td>
			<td align='center'>$adjust_data[adjust_qty]</td>
			<td align='right'>".sprint($value)."</th>
			<td>
				<input type='hidden' name='adjust_qty[$adjust_data[stkid]]'
				value='$adjust_data[adjust_qty]' />
				<input type='submit' name='update[$adjust_data[stkid]]'
				value='OK' />
			</td>
		</tr>";
	}
	
	if (empty($adjust_out)) {
		$adjust_out = "
		<tr class='".bg_class()."'>
			<td colspan='6'>
				<li>No results found for this page.</li>
			</td>
		</tr>";
	}
	
	$OUTPUT = "
	<center>
	<h3>Stock Take - Adjustments</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Page</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>
				<input type='text' name='page' value='$page' size='3'
				style='font-weight: bold; text-align: center' />
			</td>
			<td><input type='submit' value='OK' /></td>
		</tr>
	</table>
	</form>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='adjust_update' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Barcode</th>
			<th>Stock Code</th>
			<th>Stock Description</th>
			<th>Adjustment</th>
			<th>Value</th>
			<th>&nbsp;</th>
		</tr>
		$adjust_out
	</table>
	<input type='submit' name='button[complete]' value='Complete' />
	</center>";
	
	return $OUTPUT;
}

function adjust_update()
{
	extract ($_REQUEST);
	
	pglib_transaction("BEGIN");
	
	foreach ($update as $stkid=>$value) {
		$sql = "UPDATE cubit.stock_take SET adjusted='1' WHERE stkid='$stkid'";
		db_exec($sql) or errDie("Unable to update stock take.");
		
		if ($adjust_qty[$stkid] > 0) {
			increase_stock($stkid, $adjust_qty[$stkid]);
		} elseif ($adjust_qty[$stkid] < 0) {
			decrease_stock($stkid, abs($adjust_qty[$stkid]));
		}
		
		unblock_stock($stkid);
	}
	
	pglib_transaction("COMMIT");
	
	return adjust_display();
}

function complete()
{
	extract ($_REQUEST);

	pglib_transaction("BEGIN");

	$datetime = date("Y-m-d G:i:s");

	$sql = "SELECT stkid, qty FROM cubit.stock_take";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	
	while (list($stkid, $qty) = pg_fetch_array($stock_rslt)) {
		$sql = "INSERT INTO cubit.stock_take_report (timestamp, stkid, qty)
				VALUES ('$datetime', '$stkid', '$qty')";
		db_exec($sql) or errDie("Unable to add to report.");
	}
	
	$sql = "DELETE FROM cubit.stock_take";
	db_exec($sql) or errDie("Unable to remove from stock take.");
	
	pglib_transaction("COMMIT");
	
	$OUTPUT = "
	<h3>Stock Take</h3>
	<table ".TMPL_tblDflts.">
		<tr><th>Complete</th></tr>
		<tr class='".bg_class()."'>
			<td><li>Stock Take has successfully been completed.</td></li>
		</tr>
	</table>";
	
	return $OUTPUT;
}

function increase_stock($stkid, $qty)
{
	$sysdate = date("Y-m-d");
	
	$sql = "SELECT stkcod, stkdes, csprice FROM cubit.stock WHERE stkid='$stkid'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	list($stkcod, $stkdes, $csprice) = pg_fetch_array($stock_rslt);
	
	$price = $csprice * $qty;
	
	$sql = "UPDATE cubit.stock SET units=(units+'$qty'), csamt=(csamt+'$price')
			WHERE stkid='$stkid'";
	db_exec($sql) or errDie("Unable to increase stock.");

	stockrec($stkid, $stkcod, $stkdes, "dt", $sysdate, $qty, $price,
			 "Stock Take Adjustment");
	
	return;
}

function decrease_stock($stkid, $qty)
{
	$sysdate = date("Y-m-d");
	
	$sql = "SELECT stkcod, stkdes, csprice FROM cubit.stock WHERE stkid='$stkid'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	list($stkcod, $stkdes, $csprice) = pg_fetch_array($stock_rslt);
	
	$price = $csprice * $qty;
	
	$sql = "UPDATE cubit.stock SET units=(units-'$qty'), csamt=(csamt-'$price')
			WHERE stkid='$stkid'";
	db_exec($sql) or errDie("Unable to decrease stock.");

	stockrec($stkid, $stkcod, $stkdes, "ct", $sysdate, $qty, $price,
			 "Stock Take Adjustment");
	
	return;
}

function block_stock($stkid)
{
	$sql = "UPDATE cubit.stock SET blocked='y' WHERE stkid='$stkid'";
	db_exec($sql) or errDie("Unable to block stock.");
	
	return true;
}

function unblock_stock($stkid)
{
	$sql = "UPDATE cubit.stock SET blocked='n' WHERE stkid='$stkid'";
	db_exec($sql) or errDie("Unable to unblock stock.");
	
	return true;
}

?>