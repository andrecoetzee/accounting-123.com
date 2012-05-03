<?php

require ("settings.php");

$OUTPUT = display();

require ("template.php");

function display()
{
	extract ($_REQUEST);
	
	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = "01";
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	
	extract ($fields, EXTR_SKIP);
	
	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";
	
	$sql = "SELECT timestamp, stkcod, stkdes, qty
			FROM cubit.stock_take_report
				LEFT JOIN cubit.stock ON stock_take_report.stkid=stock.stkid
			WHERE timestamp BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59'
			ORDER BY timestamp, stkcod DESC";
	$stktake_rslt = db_exec($sql) or errDie("Unable to retrieve report.");
	
	$stock_take_ar = array();
	while ($stktake_data = pg_fetch_array($stktake_rslt)) {
		$stock_take_ar[$stktake_data["timestamp"]][] = "
			<tr class='".bg_class()."'>
				<td>$stktake_data[stkcod]</td>
				<td>$stktake_data[stkdes]</td>
				<td>$stktake_data[qty]</td>
			</tr>";
	}
	
	$stock_take_out = "";
	foreach ($stock_take_ar as $timestamp=>$lv2) {
		$stock_take_out = "
		<h3>$timestamp</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Stock Code</th>
				<th>Stock Description</th>
				<th>Qty</th>
			</tr>";
		foreach ($lv2 as $out) {
			$stock_take_out .= $out;
		}
		$stock_take_out .= "
		</table>";
	}
	
	$OUTPUT = "
	<center>
	<h3>Stock Take Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select' /></td>
		</tr>
	</table>
	</form>
	$stock_take_out
	</center>";
	
	return $OUTPUT;
}