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

	extract ($fields, EXTR_SKIP);

	$OUTPUT = "<center>
	<h3>Equipment to be Returned</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			<td><b> To </b></td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td>
				<input type='submit' value='Continue' style='text-weight: bold' />
			</td>
		</tr>
	</table>";

	// Retrieve equipment to be returned
	$sql = "SELECT *,
				(SELECT des FROM cubit.assets WHERE id=asset_id) AS des,
				(SELECT serial FROM cubit.assets WHERE id=asset_id) AS serial
			FROM hire.hire_invitems
			ORDER BY des ASC";
	$items_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

	$items_out = "";
	while ($items_data = pg_fetch_array($items_rslt)) {
		$sql = "SELECT * FROM hire.hire_invoices WHERE invid='$items_data[invid]'";
		$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
		$inv_data = pg_fetch_array($inv_rslt);

		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$inv_data[cusnum]'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
		$cust_data = pg_fetch_array($cust_rslt);

		if (!hiredDate($items_data["id"])) {
			continue;
		}

		$items_out .= "<tr class='".bg_class()."'>
			<td>".hiredDate($items_data["id"])."</td>
			<td>".returnDate($items_data["id"])."</td>
			<td>$items_data[des]</td>
			<td>".getSerial($items_data["asset_id"])."</td>
			<td>$items_data[qty]</td>
			<td>$cust_data[surname] $cust_data[cusname]</td>
			<td>
				<a href='hire-invoice-new.php?invid=$items_data[invid]'>
					View Hire
				</a>
			</td>
		</tr>";
	}

	$OUTPUT .= "<p></p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Hired</th>
			<th>To Be Returned</th>
			<th>Plant</th>
			<th>Serial</th>
			<th>Qty</th>
			<th>Customer</th>
			<th>Options</th>
		</tr>
		$items_out
	</table>
	</center>";

	return $OUTPUT;
}