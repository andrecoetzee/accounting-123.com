<?php

require ("../settings.php");

$OUTPUT = display();

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["search"] = "";

	extract ($fields, EXTR_SKIP);

	$sql = "SELECT accno, customers.surname, bustel, invnum, des,
				hire_invitems.id, hire_invitems.invid
			FROM hire.hire_invitems
				LEFT JOIN hire.hire_invoices
					ON hire_invitems.invid=hire_invoices.invid
				LEFT JOIN cubit.customers
					ON hire_invoices.cusnum=customers.cusnum
				LEFT JOIN cubit.assets
					ON hire_invitems.asset_id=assets.id
			WHERE customers.surname ILIKE '%$search%' OR
				bustel ILIKE '%$search%' OR	des ILIKE '%$search%'";
	$overdue_rslt = db_exec($sql) or errDie("Unable to retrieve hires.");

	$overdue_out = "";
	$i = 0;
	while ($overdue_data = pg_fetch_array($overdue_rslt)) {
		if (!isOverdue($overdue_data["id"])) continue;

		$overdue_out .= "
		<tr class='".bg_class()."'>
			<td>$overdue_data[accno]</td>
			<td>$overdue_data[surname]</td>
			<td>$overdue_data[bustel]</td>
			<td>H$overdue_data[invnum]".rev($overdue_data["invid"])."</td>
			<td>$overdue_data[des]</td>
			<td>".returnDate($overdue_data["id"])."</td>
		</tr>";
	}

	if (empty($overdue_out)) {
		$overdue_out = "<tr class='".bg_class()."'>
			<td colspan='6'><li>No results found.</li></td>
		</tr>";
	}

	$OUTPUT = "<center>
	<h3>Overdue Hires Report</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr><th colspan='2'>Search</th></tr>
		<tr class='".bg_class()."'>
			<td><input type='text' name='search' value='$search' /></td>
			<td>
				<input type='submit' value='Search' style='font-weight: bold' />
			</td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Acc No</th>
			<th>Company / Name</th>
			<th>Telephone</th>
			<th>Hire No</th>
			<th>Product Code</th>
			<th>Return Date</th>
		</tr>
		$overdue_out
	</table>";

	return $OUTPUT;
}