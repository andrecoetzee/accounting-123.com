<?php

require ("settings.php");

$OUTPUT = display();

require ("template.php");




function display()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["from_day"] = "01";
	$fields["from_month"] = date("m");
	$fields["from_year"] = date("Y");
	$fields["to_day"] = date("d");
	$fields["to_month"] = date("m");
	$fields["to_year"] = date("Y");

	extract($fields, EXTR_SKIP);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	// Stock Costs -----------------------------------------------------------
	$total_stock = 0.00;

	$sql = "SELECT * FROM cubit.stock WHERE div='".USER_DIV."'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

	$stock_out = "";

	while ($stock_data = pg_fetch_array($stock_rslt)) {
		// Retrieve purchases
		$periods = 14;
		$pi_ar = array();
		for ($i = 1; $i <= $periods; $i++) {
			$pi_ar[] = "SELECT * FROM \"$i\".pur_items WHERE stkid='$stock_data[stkid]'";
		}
		$sql = implode(" UNION ", $pi_ar);
		$pi_rslt = db_exec($sql) or errDie("Unable to retrieve purchases.");

		$total_qty = 0;
		$total_cost = 0;
		while ($pi_data = pg_fetch_array($pi_rslt)) {
			$total_qty += $pi_data["qty"];
			$total_cost += $pi_data["amt"];
		}
		$total_stock += $total_cost;

		$stock_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$stock_data[stkcod]</td>
			<td>$stock_data[stkdes]</td>
			<td align='right'>".CUR.sprint($stock_data["selamt"])."</td>
			<td align='center'>$total_qty</td>
			<td align='right'>".CUR.sprint($total_cost)."</td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Stock Purchase and Cost History</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td><b>To</b></td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select'></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='6'><h3>Stock Costs</h3></th>
		</tr>
		<tr>
			<th>Stock Code</th>
			<th>Stock Description</th>
			<th>Cost per Unit</th>
			<th>Qty Purchased</th>
			<th>Total Cost</th>
		</tr>
		$stock_out
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'>&nbsp;</td>
			<td align='right'><b>".CUR.sprint($total_stock)."</b></td>
		</tr>

		<tr>
	</table>";
	return $OUTPUT;

}


?>