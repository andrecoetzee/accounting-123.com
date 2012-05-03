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
		SELECT invnum, cusname, odate, timestamp
		FROM cubit.invoices
		LEFT JOIN cubit.pslip_dispatched
			ON invoices.invid=pslip_dispatched.invid
		WHERE signed='0' AND dispatched='1' AND (timestamp BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59')
		ORDER BY invnum DESC";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

	$inv_out = "";
	while ($inv_data = pg_fetch_array($inv_rslt)) {
		$inv_out .= "
			<tr class='".bg_class()."'>
				<td>$inv_data[timestamp]</td>
				<td>$inv_data[invnum]</td>
				<td>$inv_data[cusname]</td>
				<td>$inv_data[odate]</td>
			</tr>";
	}

	if (empty($inv_out)) {
		$inv_out = "
			<tr class='".bg_class()."'>
				<td colspan='4'><li>No results found</li></td>
			</tr>";
	}

	$OUTPUT = "
		<center>
		<h3>Unsigned Invoices Report</h3>
		<form method='POST' action='".SELF."'>
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
				<th>Time</th>
				<th>Invoice No</th>
				<th>Customer</th>
				<th>Invoice Date</th>
			</tr>
			$inv_out
		</table>
		</center>";
	return $OUTPUT;

}


?>