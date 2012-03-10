<?php

require ("../settings.php");

if (!isset($_REQUEST["id"])) {
	$OUTPUT = "<li class='err'>Invalid use of module</li>";
	require ("../template.php");
}

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		default:
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
	}
} else {
	$OUTPUT = confirm();
}

require ("../template.php");

function confirm($errors="&nbsp;")
{
	extract($_REQUEST);

	// Retrieve the booking
	$sql = "SELECT *,
				extract('epoch' FROM from_date) AS e_from,
				extract('epoch' FROM to_date) AS e_to
			FROM hire.bookings
			WHERE id='$id'";
	$booking_rslt = db_exec($sql) or errDie("Unable to retrieve bookings.");
	$booking_data = pg_fetch_array($booking_rslt);

	// Booking Date
	$from_date = date("d-m-Y", $booking_data["e_from"]);
	$to_date = date("d-m-Y", $booking_data["e_to"]);

	if (empty($booking_data["asset_id"])) {
		$OUTPUT = "<li class='err'>Selected booking does no longer exist.</li>";
		return $OUTPUT;
	}

	// Retrieve asset
	$sql = "SELECT * FROM cubit.assets WHERE id='$booking_data[asset_id]'";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
	$asset_data = pg_fetch_array($asset_rslt);

	// Retrieve customer
	$sql = "SELECT * FROM cubit.customers WHERE cusnum='$booking_data[cust_id]'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");
	$cust_data = pg_fetch_array($cust_rslt);

	$OUTPUT = "<h3>Remove Booking</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='id' value='$id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Confirm</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Small Plant</td>
			<td>$asset_data[des]</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Customer</td>
			<td>$cust_data[surname]</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Booking Date - From</td>
			<td>$from_date</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Booking Date - To</td>
			<td>$to_date</td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Remove &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function write()
{
	extract($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($id, "num", 1, 9, "Invalid booking selection.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	$sql = "DELETE FROM hire.bookings WHERE id='$id'";
	db_exec($sql) or errDie("Unable to retrieve bookings.");

	$OUTPUT = "<h3>Remove Booking</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Remove</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><li>Booking successfully removed</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}