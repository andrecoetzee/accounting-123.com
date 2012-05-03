<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");

function display($msg="")
{
	extract ($_REQUEST);

	$fields = array();
	$fields["search"] = "~:BLANK:~";

	extract ($fields, EXTR_SKIP);

	removeDead();

	$sql = "SELECT invid FROM hire.hire_invoices WHERE done='y' AND printed='y'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve hire notes.");

	while ($inv_data = pg_fetch_array($inv_rslt)) {
		updateTotals($inv_data["invid"]);
	}

	// Retrieve invoices
	if ($search == "~:BLANK:~") {
		// We don't want any results at first
		$sql = "SELECT * FROM hire.hire_invoices WHERE cusnum='-1.23'";
		$search = "";
	} else if (!empty($search)) {
		$sql = "SELECT * FROM hire.hire_invoices WHERE done='y'
					AND invnum ILIKE '%$search%'
				UNION
				SELECT * FROM hire.hire_invoices WHERE done='y'
					AND cusname ILIKE '%$search%'
				ORDER BY invnum DESC";
	} else {
		$sql = "SELECT * FROM hire.hire_invoices
					WHERE done='y'
					ORDER BY invnum DESC";
	}
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

	$hires_out = "";
	while ($inv_data = pg_fetch_array($inv_rslt)) {
		// Retrieve customer
		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$inv_data[cusnum]'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");
		$cust_data = pg_fetch_array($cust_rslt);

		$hires_out .= "<tr class='".bg_class()."'>
			<td>
				<a href='javascript:printer(\"hire/hire-invoice-new.php?invid=$inv_data[invid]&reprint=1\")'>
					H$inv_data[invnum]".rev($inv_data["invid"])."
				</a>
			</td>
			<td>$cust_data[surname]</td>
			<td>".CUR."$inv_data[total]</td>
			<td>
				<a href='hire-invoice-new.php?invid=$inv_data[invid]&reprint=1'>
					View Hire
				</a>
			</td>
			<td>
			<a href='javascript:printer(\"hire/hire_note_reprint.php?invid=$inv_data[invid]\")'>Reprint</a>
			</td>
			<!--<td><a href='hires_return.php?invid=$inv_data[invid]'>Return</a></td>-->
			<!--<td><a href='hires_swap.php?invid=$inv_data[invid]'>Swap</a></td>-->
		</tr>";
	}
	if (empty($hires_out)) {
		$hires_out = "<tr class='".bg_class()."'>
			<td colspan='5'>Please enter a hire no or customer name</td>
		</tr>";
	}

	if (isset($added)) {
		$msg = "<li class='err'>Item/s successfully hired out</li>";
	}

	$OUTPUT = "<h3>View Hire</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Enter Hire No or Client Name</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td><input type='submit' value='Filter' /></td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='5'>$msg</td>
		</tr>
		<tr>
			<th>Hire No</td>
			<th>Customer</th>
			<th>Total</th>
			<th colspan='2'>Options</th>
		</tr>
		$hires_out
	</table>";

	return $OUTPUT;
}

function removeDead()
{
	$sql = "SELECT * FROM hire.hire_invoices WHERE done='y'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

	while ($inv_data = pg_fetch_array($inv_rslt)) {
		$sql = "SELECT * FROM hire.hire_invitems WHERE invid='$inv_data[invid]'";
		$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

		if (!pg_num_rows($item_rslt)) {
			$sql = "DELETE FROM hire.hire_invoices WHERE invid='$inv_data[invid]'";
			db_exec($sql) or errDie("Unable to remove invoices.");
		}
	}

	return;
}
