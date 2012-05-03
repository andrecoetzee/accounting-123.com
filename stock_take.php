<?php

require ("settings.php");
require ("core-settings.php");

// Navigation logic
if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);

	switch ($button) {
		case "adjustments":
			$OUTPUT = adjust_display();
			break;
		case "complete":
			$OUTPUT = complete();
			break;
	}
} elseif (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "take_display":
			$OUTPUT = take_display();
			break;
		case "take_update":
			$OUTPUT = take_update();
			break;
		case "adjust_display":
			$OUTPUT = adjust_display();
			break;
		case "adjust_update":
			$OUTPUT = adjust_update();
			break;
	}
} else {
	$OUTPUT = take_display();
}

require ("template.php");

function take_display()
{
	extract ($_REQUEST);
	
	$sql = "SELECT stock.stkid, stkcod, stkdes, bar, adjusted
			FROM cubit.stock
				LEFT JOIN cubit.stock_take ON stock.stkid=stock_take.stkid
			WHERE adjusted='0' OR adjusted IS NULL
			ORDER BY stkcod ASC";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	
	$stock_out = "";
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		$sql = "SELECT stkid, qty FROM cubit.stock_take
				WHERE stkid='$stock_data[stkid]'";
		$stktake_rslt = db_exec($sql) or errDie("Unable to retrieve stock take.");
		$stktake_data = pg_fetch_array($stktake_rslt);
		
		$stock_out .= "
		<tr class='".bg_class()."'>
			<td>$stock_data[bar]</td>
			<td>$stock_data[stkcod]</td>
			<td>$stock_data[stkdes]</td>
			<td align='center'>
				<input type='text' name='qty[$stock_data[stkid]]'
				value='$stktake_data[qty]' size='3'
				style='text-align: center' />
			</td>
			<td>
				<input type='submit' name='update[$stock_data[stkid]]'
				value='OK' />
			</td>
		</tr>";
	}
	
	if (empty($stock_out)) {
		$stock_out = "
		<tr class='".bg_class()."'>
			<td colspan='5'>No results found. All stock <em>adjusted</em>?</td>
		</tr>";
	}
	
	$OUTPUT = "
	<center>
	<h3>Stock Take</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='take_update' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Barcode</th>
			<th>Stock Code</th>
			<th>Stock Description</th>
			<th>Quantity</th>
			<th>&nbsp;</th>
		</tr>
		$stock_out
	</table>
	<input type='submit' name='button[adjustments]' value='Adjustments' />
	</form>
	</center>";
	
	return $OUTPUT;
}

function take_update()
{
	extract ($_REQUEST);
	
	pglib_transaction("BEGIN");
	
	foreach ($qty as $stkid=>$value) {
		$sql = "SELECT stkid FROM cubit.stock_take WHERE stkid='$stkid'";
		$stktake_rslt = db_exec($sql) or errDie("Unable to retrieve stock take.");
		
		if (is_numeric($qty[$stkid])) {
			if (pg_num_rows($stktake_rslt)) {
				$sql = "UPDATE cubit.stock_take SET qty='$qty[$stkid]'
						WHERE stkid='$stkid'";
			} else {
				$sql = "INSERT INTO cubit.stock_take (stkid, qty)
						VALUES ('$stkid', '$qty[$stkid]')";
			}
		}
		db_exec($sql) or errDie("Unable to add to stock take.");
	}
	
	pglib_transaction("COMMIT");
	
	return take_display();
}

function adjust_display()
{
	extract ($_REQUEST);
	
	$sql = "SELECT stock.stkid, bar, stkcod, stkdes, (qty-units) AS adjust_qty,
				csprice
			FROM cubit.stock_take
				LEFT JOIN cubit.stock ON stock.stkid=stock_take.stkid
			WHERE adjusted='0'
			ORDER BY stkcod ASC";
	$adjust_rslt = db_exec($sql) or errDie("Unable to retrieve adjustments.");
	
	$total_value = 0;
	$adjust_out = "";
	while ($adjust_data = pg_fetch_array($adjust_rslt)) {
		$value = $adjust_data["csprice"] * $adjust_data["adjust_qty"];
		$total_value += $value;
	
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
				<li>No results found, stock take <em>completed?</em></li>
			</td>
		</tr>";
	}
	
	$OUTPUT = "
	<center>
	<h3>Stock Take - Adjustments</h3>
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
		<tr class='".bg_class()."'>
			<td colspan='4'>Total</td>
			<td align='right'>".sprint($total_value)."</td>
			<td>&nbsp;</td>
		</tr>
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

function