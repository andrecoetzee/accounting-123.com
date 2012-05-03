<?php

require ("../settings.php");
error_reporting(E_ALL);
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

	$from_date = dateFmt($from_year, $from_month, $from_day);
	$to_date = dateFmt($to_year, $to_month, $to_day);

	// Retrieve hires
	$sql = "
	SELECT *,
		(SELECT username FROM cubit.users WHERE userid=user_id) AS username,
		(SELECT surname FROM cubit.customers WHERE cusnum=cust_id) AS surname,
		(SELECT cusname FROM cubit.customers WHERE cusnum=cust_id) AS cusname,
		(SELECT EXTRACT('EPOCH' FROM from_time)) AS e_from,
		(SELECT EXTRACT('EPOCH' FROM to_time)) AS e_to
	FROM hire.hires
	WHERE from_time BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59' OR
		to_time BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'
	ORDER BY e_from ASC";
	$hire_rslt = db_exec($sql) or errDie("Unable to retrieve hires.");

	$hire_out = "";
	while ($hire_data = pg_fetch_array($hire_rslt)) {
		$time = date("d-m-Y G:i:s", $hire_data["e_from"]);

		$hire_out .= "<tr class='".bg_class()."'>
			<td>$time</td>
			<td>$hire_data[inv_id]</td>
			<td>$hire_data[username]</td>
			<td>
				<a href='../cust-det.php?cusnum=$hire_data[cust_id]'>
					$hire_data[cusname] $hire_data[surname]
				</a>
			</td>
		</tr>";
	}

	if (empty($hire_out)) {
		$hire_out = "<tr class='".bg_class()."'>
			<td colspan='4'><li>No items found for this date range</li></td>
		</tr>";
	}

	if (empty($invoice_out)) {
		$invoice_out = "<tr class='".bg_class()."'>
			<td colspan='4'><li>No items found for this date range</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Daily Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td><b> To </b></td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Select' /></td>
		</tr>
	</table>
	</form>
	<p></p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4' style='font-size: 16px'>HIRES</th>
		</tr>
		<tr>
			<th>Time</th>
			<th>Invoice No</th>
			<th>Sales Person</th>
			<th>Customer</th>
		</tr>
		$hire_out
		<tr>
			<th colspan='4' style='font-size: 16px'>INVOICES</th>
		</tr>
		<tr>
			<th>Time</th>
			<th>Invoice No</th>
			<th>Sales Person</th>
			<th>Customer</th>
		</tr>
		$invoice_out
	</table>
	</center>";

	return $OUTPUT;
}