<?php

require ("settings.php");

if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);
	switch ($button) {
		case "adjustments":
			header("Location: stock_take_adjust.php");
			break;
	}
} else if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "take_display":
			$OUTPUT = posttake_display();
			break;
		case "take_update":
			$OUTPUT = posttake_update();
			break;
	}
} else {
	$OUTPUT = posttake_display();
}

$OUTPUT .= mkQuicklinks (
	ql("stock-add.php", "Add Stock"),
	ql("stock-view.php", "View Stock")
);

require ("template.php");

function posttake_display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["page"] = 1;
	
	extract ($fields, EXTR_SKIP);
	
	$sql = "SELECT stock.stkid, stkcod, stkdes, bar, adjusted, qty
			FROM cubit.stock_take
				LEFT JOIN cubit.stock ON stock_take.stkid=stock.stkid
			WHERE page='$page' AND (adjusted='0' OR adjusted IS NULL)
			ORDER BY stkcod ASC";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	
	$stock_out = "";
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		$stock_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$stock_data[bar]</td>
			<td>$stock_data[stkcod]</td>
			<td>$stock_data[stkdes]</td>
			<td align='center'>
				<input type='text' name='qty[$stock_data[stkid]]'
				value='$stock_data[qty]' size='3'
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
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='5'>No results found.</td>
		</tr>";
	}
	
	$OUTPUT = "
	<center>
	<h3>Stock Take</h3>
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

function posttake_update()
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
			}
		}
		db_exec($sql) or errDie("Unable to add to stock take.");
	}
	
	pglib_transaction("COMMIT");
	
	return posttake_display();
}