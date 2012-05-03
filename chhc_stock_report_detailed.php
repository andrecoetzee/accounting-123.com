<?php

require ("settings.php");

$OUTPUT = report();

require ("template.php");

function report()
{
	extract ($_REQUEST);

	$sql = "
	SELECT whname, stkcod, stkdes, classname, catname, units, selamt, alloc,
		ordered
	FROM cubit.stock
		LEFT JOIN exten.warehouses ON stock.whid=warehouses.whid
	ORDER BY stkcod ASC";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

	$stock_out = "";
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		$stock_out .= "
		<tr class='".bg_class()."'>
			<td>$stock_data[whname]</td>
			<td>$stock_data[stkcod]</td>
			<td>$stock_data[stkdes]</td>
			<td>$stock_data[classname]</td>
			<td>$stock_data[catname]</td>
			<td align='center'>$stock_data[units]</td>
			<td align='right'>$stock_data[selamt]</td>
			<td align='center'>$stock_data[alloc]</td>
			<td align='center'>$stock_data[ordered]</td>
		</tr>";
	}

	$OUTPUT = "
	<h3>CHHC Detailed Stock Report</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Store</th>
			<th>Code</th>
			<th>Description</th>
			<th>Class</th>
			<th>Category</th>
			<th>On Hand</th>
			<th>Retail Price</th>
			<th>Allocated</th>
			<th>Ordered</th>
		</tr>
		$stock_out
	</table>";

	return $OUTPUT;
}
