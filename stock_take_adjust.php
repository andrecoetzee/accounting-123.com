<?php

require ("settings.php");
require ("core-settings.php");

error_reporting(E_ALL);

// Navigation logic
if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);
	
	switch ($button) {
		case "complete":
			$OUTPUT = complete();
			break;
		case "page_complete":
			$OUTPUT = page_complete();
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

// Quick links
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
	
	$sql = "SELECT stock.stkid, bar, stkcod, stkdes, catname, csprice,
				(qty-units) AS adjust_qty
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
		<tr bgcolor='".bgcolorg()."'>
			<td>$adjust_data[bar]</td>
			<td>$adjust_data[catname]</td>
			<td>$adjust_data[stkcod]</td>
			<td>$adjust_data[stkdes]</td>
			<td align='center'>".sprint3($adjust_data['adjust_qty'])."</td>
			<td align='right'>".sprint($value)."</th>
			<td>
				<input type='hidden' name='adjust_qty[$adjust_data[stkid]]'
				value='$adjust_data[adjust_qty]' />
				<input type='submit' name='update[$adjust_data[stkid]]'
				value='Adjust' />
			</td>
		</tr>";
	}
	
	if (empty($adjust_out)) {
		$adjust_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='7'>
				<li>No results found for this page.</li>
			</td>
		</tr>";
	}
	
	$sql = "SELECT max(page) FROM cubit.stock_take";
	$mp_rslt = db_exec($sql) or errDie("Unable to retrieve total pages.");
	$max_page = pg_fetch_result($mp_rslt, 0);
	if ($page < $max_page) {
		$adjust_btn = "
			<a href='".SELF."?key=adjust_display&page=".($page + 1)."'>
				Next &raquo
			</a>";
	} else {
		$adjust_btn = "";
	}
	
	$OUTPUT = "
	<center>
	<h3>Stock Take - Adjustments</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Page</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
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
	<input type='hidden' name='page' value='$page' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Barcode</th>
			<th>Category</th>
			<th>Stock Code</th>
			<th>Stock Description</th>
			<th>Adjustment</th>
			<th>Value</th>
			<th>&nbsp;</th>
		</tr>
		$adjust_out
	</table>
	<input type='submit' name='button[page_complete]' value='Adjust Page' />
	<br />
	$adjust_btn
	</center>";
	
	return $OUTPUT;
}

function adjust_update()
{
	extract ($_REQUEST);
	
	pglib_transaction("BEGIN");
	
	foreach ($update as $stkid=>$value) {
		$sql = "UPDATE cubit.stock_take SET adjusted='1'
				WHERE stkid='$stkid'";
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

function page_complete()
{
	extract ($_REQUEST);
	
	$sql = "
	SELECT stock.stkid, csprice FROM cubit.stock_take
		LEFT JOIN cubit.stock ON stock_take.stkid=stock.stkid
	WHERE page='$page'";
	$st_rslt = db_exec($sql) or errDie("Unable to retrieve stock take");
	while (list($stkid, $csprice) = pg_fetch_array($st_rslt)) {
		if (!isset($adjust_qty[$stkid])) continue;
		
		$price = $adjust_qty[$stkid] * $csprice;
		
		$sql = "UPDATE cubit.stock_take SET adjusted='1', adjust_val='$price'
				WHERE stkid='$stkid'";
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

	foreach ($adjust_qty as $stkid=>$value) {
		$sql = "UPDATE cubit.stock_take SET adjusted='1'
				WHERE stkid='$stkid'";
		db_exec($sql) or errDie("Unable to update stock take.");
		
		if ($adjust_qty[$stkid] > 0) {
			increase_stock($stkid, $adjust_qty[$stkid]);
		} elseif ($adjust_qty[$stkid] < 0) {
			decrease_stock($stkid, abs($adjust_qty[$stkid]));
		}
		
		unblock_stock($stkid);
	}

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
		<tr bgcolor='".bgcolorg()."'>
			<td><li>Page has successfully been completed.</td></li>
		</tr>
	</table>";
	
	return adjust_display();
	
	return $OUTPUT;
}

function increase_stock($stkid, $qty)
{
	$sysdate = date("Y-m-d");
	
	$sql = "
		SELECT stkcod, stkdes, csprice FROM cubit.stock
			WHERE stkid='$stkid'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	list($stkcod, $stkdes, $csprice) = pg_fetch_array($stock_rslt);
	
	$price = $csprice * $qty;
	
	$sql = "
		UPDATE cubit.stock SET units=(units+'$qty'), csamt=(csamt+'$price')
			WHERE stkid='$stkid'";
	db_exec($sql) or errDie("Unable to increase stock.");

	$refnum = getRefnum();

	$inventory_acc = qryAccountsName("Inventory");
	$inventory_acc = $inventory_acc["accid"];
	
	$inventory_suspense_acc = qryAccountsName("Stock Take Suspense Account");
	$inventory_suspense_acc = $inventory_suspense_acc["accid"];

	stockrec($stkid, $stkcod, $stkdes, "dt", $sysdate, $qty, $price,
			 "Stock Take Adjustment");
	writetrans($inventory_acc, $inventory_suspense_acc, $sysdate, $refnum,
		$price, "Stock Take Adjustment for ($stkcod) $stkdes - $qty Units");
	
	return;
}

function decrease_stock($stkid, $qty)
{
	$sysdate = date("Y-m-d");
	
	$sql = "
		SELECT stkcod, stkdes, csprice FROM cubit.stock
			WHERE stkid='$stkid'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	list($stkcod, $stkdes, $csprice) = pg_fetch_array($stock_rslt);
	
	$price = $csprice * $qty;
	
	$sql = "
		UPDATE cubit.stock SET units=(units-'$qty'), csamt=(csamt-'$price')
			WHERE stkid='$stkid'";
	db_exec($sql) or errDie("Unable to decrease stock.");

	$inventory_acc = qryAccountsName("Inventory");
	$inventory_acc = $inventory_acc["accid"];
	
	// Stock take suspense
	$inventory_suspense_acc = qryAccountsName("Stock Take Suspense Account");
	$inventory_suspense_acc = $inventory_suspense_acc["accid"];

	$refnum = getRefnum();

	stockrec($stkid, $stkcod, $stkdes, "ct", $sysdate, $qty, $price,
			 "Stock Take Adjustment");
	writetrans($inventory_suspense_acc, $inventory_acc, $sysdate, $refnum,
		$price, "Stock Take Adjustment for ($stkcod) $stkdes - $qty Units");
	
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
