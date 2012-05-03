<?php

require ("../settings.php");
require_lib("ext");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		default:
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
	}
} else {
	$OUTPUT = enter();
}

$OUTPUT .=
mkQuickLinks(
	ql("booking_save.php", "Add New Booking"),
	ql("booking_view.php", "View Bookings")
);

require ("../template.php");

function enter($errors="&nbsp;")
{
	extract($_REQUEST);

	// Default values
	$fields = array();
	$fields["page_option"] = "add";
	$fields["id"] = 0;
	$fields["asset_id"] = 0;
	$fields["cust_id"] = 0;
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = date("d");
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");

	extract($fields, EXTR_SKIP);

	if ($page_option == "edit") {
		if ($id) {
			$sql = "SELECT *,
						extract('epoch' FROM from_date) AS e_from,
						extract('epoch' FROM to_date) AS e_to
					FROM hire.bookings WHERE id='$id'";
			$booking_rslt = db_exec($sql) or errDie("Unable to retrieve booking.");
			$booking_data = pg_fetch_array($booking_rslt);

			extract($booking_data);

			$from_year = date("Y", $booking_data["e_from"]);
			$from_month = date("m", $booking_data["e_from"]);
			$from_day = date("d", $booking_data["e_from"]);

			$to_year = date("Y", $booking_data["e_to"]);
			$to_month = date("m", $booking_data["e_to"]);
			$to_day = date("d", $booking_data["e_to"]);
		} else {
			$page_option = "edit";
		}
	}

	// Retrieve assets
	$sql = "SELECT id, des FROM cubit.assets ORDER BY des ASC";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");

	// Assets dropdown
	$asset_sel = "<select name='asset_id' style='width: 100%'>";
	$asset_sel.= "<option value='0'>[None]</option>";
	while ($asset_data = pg_fetch_array($asset_rslt)) {
		if (!isOurs($asset_data["id"])) {
			continue;
		}

		if ($asset_id == $asset_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$asset_sel .= "<option value='$asset_data[id]' $sel>
			".getSerial($asset_data["id"], 1)." $asset_data[des]
		</option>";
	}
	$asset_sel .= "</select>";

	// Retrieve customers
	$sql = "SELECT * FROM cubit.customers ORDER BY surname ASC";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");

	// Customers dropdown
	$cust_sel = "<select name='cust_id' style='width: 100%'>";
	$cust_sel.= "<option value='0'>[None]</option>";
	while ($cust_data = pg_fetch_array($cust_rslt)) {

		if ($cust_id == $cust_data["cusnum"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$cust_sel .= "<option value='$cust_data[cusnum]' $sel>
			$cust_data[surname]
		</option>";
	}

	$OUTPUT = "<h3>".ucfirst($page_option)." Booking</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<input type='hidden' name='page_option' value='$page_option' />
	<input type='hidden' name='id' value='$id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='2'>$errors</td>
		</tr>
		<tr>
			<th colspan='2'>Booking Details</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>".REQ."Small Plant</td>
			<td>$asset_sel</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>".REQ."Customer</td>
			<td>$cust_sel</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>".REQ."Booking Date</td>
			<td align='center'>
				".mkDateSelect("from", $from_year, $from_month, $from_day)."
				&nbsp; <b>To &nbsp;
				".mkDateSelect("to", $to_year, $to_month, $to_day)."
			</td>
		<tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Confirm &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($asset_id, "num", 1, 9, "Invalid asset selection.");
	$v->isOk($cust_id, "num", 1, 9, "Invalid customer selection.");
	$v->isOk($from_year, "num", 4, 4, "Invalid from date (year).");
	$v->isOk($from_month, "num", 1, 2, "Invalid from date (month).");
	$v->isOk($from_day, "num", 1, 2, "Invalid from date (day).");
	$v->isOk($to_year, "num", 4, 4, "Invalid to date (year).");
	$v->isOk($to_month, "num", 1, 2, "Invalid to date (month).");
	$v->isOk($to_day, "num", 1, 2, "Invalid to date (day).");

	$e_from = getDTEpoch("$from_year-$from_month-$from_day 0:00:00");
	$e_to = getDTEpoch("$to_year-$to_month-$to_day 23:59:59");

	for ($i = $e_from; $i < $e_to; $i += DAYS) {
		if (isHired($asset_id, date("Y-m-d", $i))) {
			$v->addError(0, "Asset is hired out on ".date("d-m-Y", $i).".");
		}
	}

	if (!$asset_id) {
		$v->addError(0, "Please select an asset first.");
	}

	if (!$cust_id) {
		$v->addError(0, "Please select a customer first.");
	}

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	// Booking Date
	$from_date = "$from_day-$from_month-$from_year";
	$from_date_db = dateFmt($from_year, $from_month, $from_day);
	$to_date = "$to_day-$to_month-$to_year";
	$to_date_db = dateFmt($to_year, $to_month, $to_day);

	if (!isset($units)) $units = 1;

	$sql = "SELECT * FROM hire.bookings
			WHERE asset_id='$asset_id' AND
				('$from_date_db' BETWEEN from_date AND to_date OR
				 '$to_date_db' BETWEEN from_date AND to_date)";
	$bk_rslt = db_exec($sql) or errDie("Unable to retrieve bookings.");

	if (pg_num_rows($bk_rslt) && isSerialized($asset_id)) {
		return enter("<li class='err'>Item has already been booked in the
			specified date range</li>");
	}

	// Retrieve the asset description
	$sql = "SELECT * FROM cubit.assets WHERE id='$asset_id'";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
	$asset_data = pg_fetch_array($asset_rslt);
	$asset_name = getSerial($asset_data["id"], 1) ." ". $asset_data["des"];

	// Retrieve the customer name
	$sql = "SELECT surname FROM cubit.customers WHERE cusnum='$cust_id'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
	$cust_name = pg_fetch_result($cust_rslt, 0);

	if (!isSerialized($asset_id)) {
		$units_input = "<input type='text' name='units' value='$units' size='2' />";
	} else {
		$units_input = "1";
	}

	$OUTPUT = "<h3>".ucfirst($page_option)." Booking</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='page_option' value='$page_option' />
	<input type='hidden' name='id' value='$id' />
	<input type='hidden' name='asset_id' value='$asset_id' />
	<input type='hidden' name='cust_id' value='$cust_id' />
	<input type='hidden' name='from_year' value='$from_year' />
	<input type='hidden' name='from_month' value='$from_month' />
	<input type='hidden' name='from_day' value='$from_day' />
	<input type='hidden' name='to_year' value='$to_year' />
	<input type='hidden' name='to_month' value='$to_month' />
	<input type='hidden' name='to_day' value='$to_day' />
	<input type='hidden' name='units' value='1' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Confirm</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Small Plant</td>
			<td>$asset_name</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Customer</td>
			<td>$cust_name</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Units</td>
			<td>$units_input</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Booking Date</td>
			<td>$from_date &nbsp; <b>To</b> &nbsp; $to_date</td>
		</tr>
		<tr>
			<td><input type='submit' name='key' value='&laquo Correction' /></td>
			<td align='right'><input type='submit' value='Write &raquo ' /></td>
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
	$v->isOk($asset_id, "num", 1, 9, "Invalid asset selection.");
	$v->isOk($cust_id, "num", 1, 9, "Invalid customer selection.");
	$v->isOk($from_year, "num", 4, 4, "Invalid from date (year).");
	$v->isOk($from_month, "num", 1, 2, "Invalid from date (month).");
	$v->isOk($from_day, "num", 1, 2, "Invalid from date (day).");
	$v->isOk($to_year, "num", 4, 4, "Invalid to date (year).");
	$v->isOk($to_month, "num", 1, 2, "Invalid to date (month).");
	$v->isOk($to_day, "num", 1, 2, "Invalid to date (day).");
	$v->isOk($units, "num", 1, 80, "Invalid units.");

	// Booking date
	$from_date = dateFmt($from_year, $from_month, $from_day);
	$to_date = dateFmt($to_year, $to_month, $to_day);

	$e_from = getDTEpoch("$from_date 0:00:00");
	$e_to = getDTEpoch("$to_date 23:59:59");

	for ($i = $e_from; $i < $e_to; $i += DAYS) {
		$date = date("Y-m-d", $i);

		if ((unitsAvailable($asset_id, $date) - $units) < 0) {
			$available = unitsAvailable($asset_id, $date);
			$v->addError(0, "Only $available units available on $date.");
		}
	}

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	if ($page_option == "edit") {
		$sql = "
		UPDATE hire.bookings SET asset_id='$asset_id', cust_id='$cust_id',
			from_date='$from_date', to_date='$to_date', units='$units'
		WHERE id='$id'";
	} else {
		$sql = "
		INSERT INTO hire.bookings (asset_id, cust_id, from_date, to_date, units)
		VALUES ('$asset_id', '$cust_id', '$from_date', '$to_date', '$units')";
	}
	db_exec($sql) or errDie("Unable to save booking.");

	$OUTPUT = "<h3>".ucfirst($page_option)." Booking</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><li>Successfully saved booking.</li></td>
		</tr>
	</table>";

	return $OUTPUT;
}