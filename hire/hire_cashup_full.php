<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");

function display()
{
	extract($_REQUEST);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = date("d");
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");

	extract($fields, EXTR_SKIP);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	$sql = "
	SELECT invnum, cash, cheque, credit, sdate, cusname
	FROM cubit.nons_invoices
	WHERE sdate BETWEEN '$from_date' AND '$to_date' AND hire_invnum!='0'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

	$total_cash = 0;
	$total_cheque = 0;
	$total_credit = 0;

	$inv_out = "";
	while ($inv_data = pg_fetch_array($inv_rslt)) {
		$inv_out .= "
		<tr class='".bg_class()."'>
			<td>$inv_data[sdate]</td>
			<td align='center'>$inv_data[invnum]</td>
			<td>$inv_data[cusname]</td>
			<td align='right'>".sprint($inv_data["cash"])."</td>
			<td align='right'>".sprint($inv_data["cheque"])."</td>
			<td align='right'>".sprint($inv_data["credit"])."</td>
		</tr>";
		
		$total_cash += $inv_data["cash"];
		$total_cheque += $inv_data["cheque"];
		$total_credit += $inv_data["credit"];
	}

	if (empty($inv_out)) {
		$inv_out = "
		<tr class='".bg_class()."'>
			<td colspan='6'><li>No results found.</li></td>
		</tr>";
	}

	$OUTPUT = "
	<center>
	<h3>Daily Hire Cashup Report</h3>
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
			<th>Date</th>
			<th>Invoice No</th>
			<th>Customer</th>
			<th>Cash</th>
			<th>Cheque</th>
			<th>Credit Card</th>
		</tr>
		$inv_out
		<tr class='".bg_class()."'>
			<td colspan='3'><b>Total</b></td>
			<td align='right'><b>".sprint($total_cash)."</b></td>
			<td align='right'><b>".sprint($total_cheque)."</b></td>
			<td align='right'><b>".sprint($total_credit)."</b></td>
	</table>
	</center>";

	return $OUTPUT;
}
