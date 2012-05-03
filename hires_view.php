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

	$sql = "SELECT *,extract('epoch' FROM timestamp) as e_time FROM hire.hires
	ORDER BY timestamp DESC";
	$hire_rslt = db_exec($sql) or errDie("Unable to retrieve hires.");

	$hire_out = "";
	while ($hire_data = pg_fetch_array($hire_rslt)) {
		// Retrieve customer
		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$hire_data[cust_id]'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
		$cust_data = pg_fetch_array($cust_rslt);

		// Retrieve stock
		$sql = "SELECT * FROM cubit.stock WHERE stkid='$hire_data[old_id]'";
		$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		$stock_data = pg_fetch_array($stock_rslt);

		$time = date("d-m-Y G:i:s", $hire_data["e_time"]);

		$hires_out .= "<tr class='".bg_class()."'>
			<td>$time</td>
			<td>$cust_data[surname]</td>
			<td>$stock_data[stkcod]</td>
			<td>$hire_data[units]</td>
			<td>
				<a href='hire-invoice-new.php?invid=$hire_data[inv_id]&cont=1&edit=1'>
					Edit
				</a>
			</td>
		</tr>";
	}

	if (empty($hires_out)) {
		$hires_out .= "<tr class='".bg_class()."'>
			<td colspan='7'>No items on hire.</td>
		</tr>";
	}

	if (isset($added)) {
		$message = "<li class='err'>Item/s successfully hired out.</li>";
	} else {
		$message = "";
	}

	$OUTPUT = "<h3>View Hire</h3>
	<table ".TMPL_tblDflts.">
	</table>
	<p></p>
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='7'>$message</td>
		</tr>
		<tr>
			<th>Time</th>
			<th>Customer</th>
			<th>Stock</th>
			<th>Units</th>
			<th colspan='3'>Options</th>
		</tr>
		$hires_out
	</table>";

	return $OUTPUT;
}