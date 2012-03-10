<?php

require ("../settings.php");

error_reporting(E_ALL);

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "display":
			$OUTPUT = display();
			break;
		case "hire":
			$OUTPUT = hire();
			break;
	}
} else {
	$OUTPUT = display();
}

$OUTPUT .=
	mkQuickLinks(
		ql("booking_save.php", "Add Booking")
	);

require ("../template.php");

function display()
{
	extract($_REQUEST);

	$fields = array();
	$fields["frm_year"] = date("Y");
	$fields["frm_month"] = date("m");
	$fields["frm_day"] = date("d");
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("t");

	extract($fields, EXTR_SKIP);

	// Dates from date range
	$frm_date = dateFmt($frm_year, $frm_month, $frm_day);
	$to_date = dateFmt($to_year, $to_month, $to_day);

	$OUTPUT = "<center>
	<h3>View Bookings</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("frm", $frm_year, $frm_month, $frm_day)."</td>
			<td><b>&nbsp; To &nbsp;</b></td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			<td><input type='submit' value='Continue &raquo' /></td>
		</tr>
	</table>
	</form>";

	// Retrieve bookings
	$sql = "SELECT *,
				extract('epoch' FROM from_date) AS e_from,
				extract('epoch' FROM to_date) AS e_to
				FROM hire.bookings
				WHERE from_date BETWEEN '$frm_date' AND '$to_date' OR
					to_date BETWEEN '$frm_date' AND '$to_date'";
	$booking_rslt = db_exec($sql) or errDie("Unable to retrieve bookings.");

	$booking_out = "";
	while ($booking_data = pg_fetch_array($booking_rslt)) {
		// Retrieve asset
		$sql = "SELECT * FROM cubit.assets WHERE id='$booking_data[asset_id]'";
		$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
		$asset_data = pg_fetch_array($asset_rslt);

		if (!isOurs($asset_data["id"])) {
			continue;
		}

		// Retrieve customer
		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$booking_data[cust_id]'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
		$cust_data = pg_fetch_array($cust_rslt);

		// Create the output
		$booking_out .= "<tr bgcolor='".bgcolorg()."'>
			<td align='center'>$booking_data[id]</td>
			<td>".date("d-m-Y", $booking_data["e_from"])."</td>
			<td>".date("d-m-Y", $booking_data["e_to"])."</td>
			<td>$asset_data[des]</td>
			<td>".getSerial($asset_data["id"])."</td>
			<td>
				<a href='../cust-det.php?cusnum=$cust_data[cusnum]'>
					$cust_data[surname]
				</a>
			</td>
			<td>
				<a href='booking_save.php?id=$booking_data[id]&page_option=edit'>
					Edit Booking
				</a>
			</td>
			<td>
				<a href='booking_remove.php?id=$booking_data[id]'>
					Remove Booking
				</a>
			</td>
			<td>
				<a href='".SELF."?key=hire&id=$booking_data[id]'
				style='font-size: 1.2em; padding: 0 1em'>
					<b>Hire</b></a>
			</td>
		</tr>";
	}

	if (empty($booking_out)) {
		$booking_out = "<tr bgcolor='".bgcolorg()."'>
			<td colspan='7'><li>No bookings found.</li></td>
		</tr>";
	}

	$OUTPUT .= "<table ".TMPL_tblDflts.">
		<tr>
			<th>Booking No.</th>
			<th>From Date</th>
			<th>To Date</th>
			<th>Small Plant</th>
			<th>Serial No.</th>
			<th>Customer</th>
			<th colspan='3'>Options</th>
		</tr>
		$booking_out
	</table>";

	return $OUTPUT;
}

function hire()
{
	extract ($_REQUEST);

	// Invoice ----------------------------------------------------------------
	$deptid = "2";
	$salespn = "";
	$comm = "";
	$salespn = "";
	$chrgvat = getSetting("SELAMT_VAT");
	$odate = date("Y-m-d");
	$ordno = "";
	$delchrg = "0.00";
	$cordno = "";
	$terms = 0;
	$traddisc = 0;
	$SUBTOT = 0;
	$vat = 0;
	$total = 0;
	$vatnum = "";
	$cusacc = "";
	$telno = "";
	$collection = "";
	$custom_txt = "";

	// Retrieve the booking
	$sql = "SELECT * FROM hire.bookings WHERE id='$id'";
	$booking_rslt = db_exec($sql) or errDie("Unable to retrieve booking.");
	$booking_data = pg_fetch_array($booking_rslt);

	$cusnum = $booking_data["cust_id"];

	# insert invoice to DB
	$sql = "INSERT INTO hire.hire_invoices(deptid, cusnum, cordno, ordno,
		chrgvat, terms, traddisc, salespn, odate, delchrg, subtot, vat, total,
		balance, comm, username, printed, done, prd, vatnum, cusacc, telno, div,
		collection, custom_txt)
	VALUES('$deptid', '$cusnum',  '$cordno', '$ordno', '$chrgvat', '$terms',
		'$traddisc', '$salespn', '$odate', '$delchrg', '$SUBTOT', '$vat' ,
		'$total', '$total', '$comm', '".USER_NAME."', 'n', 'n', '".PRD_DB."',
		'$vatnum', '$cusacc', '$telno', '".USER_DIV."', '$collection', '$custom_txt')";
	$rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

	# get next ordnum
	db_conn("hire");
	$invid = pglib_lastid("hire_invoices", "invid");

	header("Location:hire-invoice-new.php?invid=$invid&bk_asset=$booking_data[asset_id]&bk_id=$booking_data[id]&bk_from=$booking_data[from_date]&bk_to=$booking_data[to_date]");
}