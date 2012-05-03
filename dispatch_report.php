<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");

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
	SELECT ordno, cordno, total FROM cubit.dispatch_scans
		LEFT JOIN cubit.sorders ON dispatch_scans.sordid=sorders.sordid
	WHERE timestamp BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59'
		AND duplicate='0'";
	$dispatch_rslt = db_exec($sql) or errDie("Unable to retrieve scans.");

	$report_out = "";
	while ($dispatch_data = pg_fetch_array($dispatch_rslt)) {
		$report_out .= "
		<tr class='".bg_class()."'>
			<td>$dispatch_data[ordno]</td>
			<td>&nbsp;</td>
			<td>$dispatch_data[cordno]</td>
			<td>$dispatch_data[total]</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>";
	}

	if (empty($report_out)) {
		$report_out .= "
		<tr class='".bg_class()."'>
			<td colspan='8'><li>No results found</li></td>
		</tr>";
	}

	$OUTPUT = "
	<h3>Dispatch Report</h3>
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
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Order No.</th>
			<th>Invoice No.</th>
			<th>Customer Order No.</th>
			<th>Amount</th>
			<th>Date Dispatch</th>
			<th>How Dispatched</th>
			<th>Other Dispatch Information</th>
		</tr>
		$report_out
	</table>";

	return $OUTPUT;
}
