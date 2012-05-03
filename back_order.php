<?php

require("settings.php");

$OUTPUT = display();

require("template.php");

function display()
{
	$sql = "SELECT stkid, stkcod, stkdes, units, alloc
			FROM cubit.stock
			ORDER BY stkcod ASC";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

	$stock_out = "";
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		$avail = $stock_data["units"] - $stock_data["alloc"];
		if ($avail > 0) continue;

		$union = array();
		for ($i = 1; $i <= 12; $i++) {
			$union[] = "SELECT max(purid) \"$i\".pur_items

		$sql = "SELECT purid, rqty FROM

		$stock_out .= "
		<tr class='".bg_class()."'>
			<td>$stock_data[stkcod]</td>
			<td>$stock_data[stkdes]</td>
			<td>$pur_rslt</td>
			<td>&nbsp;</td>
		</tr>";
	}

	$OUTPUT = "<h3>No Stock - Back Order</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Stock Code</th>
			<th>Stock Description</th>
			<th>Units Last Ordered</th>
			<th>Order</th>
		</tr>
		$stock_out
	</table>";

	return $OUTPUT;
}