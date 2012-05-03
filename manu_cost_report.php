<?php

require ("settings.php");

$OUTPUT = display();

require ("template.php");




function display()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");

	extract ($fields, EXTR_SKIP);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	$sql = "
	SELECT id, stock.stkcod, stock.stkdes, qty, cost_per_unit, (qty*cost_per_unit) AS total,
		extract('epoch' FROM timestamp) AS e_time
	FROM cubit.manu_hist_main
		LEFT JOIN cubit.stock ON manu_hist_main.stkid=stock.stkid
	WHERE timestamp BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59'
	ORDER BY timestamp DESC";
	$main_rslt = db_exec($sql) or errDie("Unable to retrieve main items.");

	$hist_out = "";
	while ($main_data = pg_fetch_array($main_rslt)) {
		$hist_out .= "
			<tr class='".bg_class()."'>
				<td><b>".date("d-m-Y", $main_data["e_time"])."</b></td>
				<td><b>($main_data[stkcod]) $main_data[stkdes]</b></td>
				<td align='center'><b>$main_data[qty]</b></td>
				<td align='right'><b>".sprint($main_data["cost_per_unit"])."</b></td>
				<td align='right'><b>".sprint($main_data["total"])."</b></td>
			</tr>";

		$sql = "
		SELECT stkcod, stkdes, qty, cost_per_unit, (qty*cost_per_unit) AS total
		FROM cubit.manu_hist_sub
			LEFT JOIN cubit.stock ON manu_hist_sub.stkid=stock.stkid
		WHERE main_id='$main_data[id]'
		ORDER BY timestamp ASC";
		$sub_rslt = db_exec($sql) or errDie("Unable to retrieve sub items.");

		while ($sub_data = pg_fetch_array($sub_rslt)) {
			$hist_out .= "
				<tr class='".bg_class()."'>
					<td>&nbsp;</td>
					<td>($sub_data[stkcod]) $sub_data[stkdes]</td>
					<td align='center'>$sub_data[qty]</td>
					<td align='right'>".sprint($sub_data["cost_per_unit"])."</td>
					<td align='right'>".sprint($sub_data["total"])."</td>
				</tr>";
		}
	}

	if (empty($hist_out)) {
		$hist_out = "
			<tr class='".bg_class()."'>
				<td colspan='5'><li>No results found.</li></td>
			</tr>";
	}

	$OUTPUT = "
		<center>
		<h3>Manufacturing Cost Report</h3>
		<form method='post' action='".SELF."'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='4'>Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
				<td>&nbsp; <b>To</b> &nbsp;</td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
				<td><input type='submit' value='Select' style='font-weight:bold' /></td>
			</tr>
		</table>
		</form>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Stock</th>
				<th>Qty</th>
				<th>Cost per Unit</th>
				<th>Total</th>
			</tr>
			$hist_out
		</table>
		</center>";
	return $OUTPUT;

}


?>