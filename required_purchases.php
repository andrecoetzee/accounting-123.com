<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "display":
			$OUTPUT = display();
			break;
		case "purchaseitem":
			$OUTPUT = purchaseItem();
			break;
		case "purchaseall":
			$OUTPUT = purchaseAll();
			break;
	}
} else {
	$OUTPUT = display();
}

require ("template.php");




function display()
{

	$sql = "SELECT * FROM cubit.stock ORDER BY stkcod ASC";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

	$stock_out = "";
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		// Retrieve units on order
		$sql = "SELECT sum(qty) FROM cubit.sorders_items WHERE stkid='$stock_data[stkid]'";
		$order_rslt = db_exec($sql) or errDie("Unable to retrieve orders.");
		$order_qty = pg_fetch_result($order_rslt, 0);

		if (empty($stock_data["minlvl"])) {
			continue;
		}

		if (empty($order_qty)) {
			$order_qty = 0;
		}

		$units = $stock_data["units"] - $order_qty;
		$required = abs($units - $stock_data["minlvl"]);

		// We're not required to purchase this item
		if ($units > $stock_data["minlvl"]) {
			continue;
		}

		$stock_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$stock_data[stkcod]</td>
				<td>$stock_data[stkdes]</td>
				<td align='center'>".sprint3($stock_data['units'])."</td>
				<td align='center'>".sprint3($order_qty)."</td>
				<td align='center'>".sprint3($stock_data['minlvl'])."</td>
				<td align='center'><b>".sprint3($required)."</b></td>
				<td><a href='purchase-new.php'>Purchase</a></td>
			</tr>";
	}

	if (empty($stock_out)) {
		$stock_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='7'>No items required for purchase.</td>
			</tr>";
	}

	$OUTPUT = "
		<center>
		<h3>Required Purchases</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Stock Code</th>
				<th>Stock Description</th>
				<th>Current Units</th>
				<th>Units on Order</th>
				<th>Minimum Level</th>
				<th>Minimum Required</th>
				<th>Options</th>
			</tr>
			$stock_out
		</table>
		</center>";
	return $OUTPUT;

}

?>