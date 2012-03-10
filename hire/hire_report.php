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

	// Retrieve invoices
	$sql = "SELECT * FROM hire.hire_invoices WHERE done='y'
				AND odate BETWEEN '$from_date' AND '$to_date'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

	$hires_out = "";
	while ($inv_data = pg_fetch_array($inv_rslt)) {
		// Retrieve customer
		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$inv_data[cusnum]'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");
		$cust_data = pg_fetch_array($cust_rslt);

		$hires_out .= "<tr bgcolor='".bgcolorg()."'>
			<td>$inv_data[odate]</td>
			<td align='center'>
				<a href='hire-invoice-new.php?invid=$inv_data[invid]'>
					H".getHirenum($inv["invid"], 1)."
				</a>
			</td>
			<td>$cust_data[surname]</td>
			<td>".CUR."$inv_data[total]</td>
			<!--<td><a href='hires_return.php?invid=$inv_data[invid]'>Return</a></td>-->
			<!--<td><a href='hires_swap.php?invid=$inv_data[invid]'>Swap</a></td>-->
		</tr>";
	}
	if (empty($hires_out)) {
		$hires_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='5'>No hire notes found for this date range.</td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Hire Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td><b> To </b></td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select' style='font-weight: bold' /></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='5'>$msg</td>
		</tr>
		<tr>
			<th>Date</th>
			<th>Hire No</td>
			<th>Customer</th>
			<th>Total</th>
			<!--<th colspan='2'>Options</th>-->
		</tr>
		$hires_out
	</table>
	</center>";

	return $OUTPUT;
}
