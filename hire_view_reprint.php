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

	$sql = "SELECT invid, invnum, customers.surname, hire_invid,
				extract('epoch' FROM reprint_invoices.odate) AS e_date
				FROM hire.reprint_invoices
					LEFT JOIN cubit.customers
						ON reprint_invoices.cusnum=customers.cusnum
			WHERE reprint_invoices.odate BETWEEN '$from_date' AND '$to_date'
			ORDER BY invnum DESC";
	$reprint_rslt = db_exec($sql) or errDie("Unable to retrieve reprints.");

	$reprint_out = "";
	while ($reprint_data = pg_fetch_array($reprint_rslt)) {
		if (!$reprint_data["invnum"]) continue;

		$reprint_out .= "<tr bgcolor='".bgcolorg()."'>
			<td>H$reprint_data[invnum]".rrev($reprint_data["invid"])."</td>
			<td>$reprint_data[surname]</td>
			<td>".date("d-m-Y", $reprint_data["e_date"])."</td>
			<td>
				<a href='javascript:printer".
				"(\"hire/hire_note_reprint.php?invid=$reprint_data[invid]\")'>
					Reprint
				</a>
			</td>
		</tr>";
	}

	if (empty($reprint_out)) {
		$reprint_out = "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'><li>No results found.</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>View Hire Note Reprints</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr><th colspan='4'>Date Range</th></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td>&nbsp; <b>To</b> &nbsp;</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td>
				<input type='submit' value='Select' style='font-weight: bold' />
			</td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Hire No</th>
			<th>Customer</th>
			<th>Date</th>
			<th>Reprint</th>
		</tr>
		$reprint_out
	</table>";

	return $OUTPUT;
}