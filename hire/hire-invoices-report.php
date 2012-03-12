<?

#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#

require ("../settings.php");
require ("../core-settings.php");

// Merge get vars and post vars
foreach ($_GET as $key => $val) {
	$_POST[$key] = $val;
}

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printInvoice ($_POST);
			break;
        case "bdel":
        	$OUTPUT = bdel_confirm($_POST);
        	break;
        case "bdelwrite":
        	$OUTPUT = bdel_write($_POST);
        	break;
		case "delete_confirm":
			$OUTPUT = delete_confirm ($_POST);
			break;
		case "delete_write":
			$OUTPUT = delete_write ($_POST);
			break;
		default:
			$OUTPUT = slct ();
			break;
	}
} else {
	# Display default output
	$OUTPUT = slct ();
}

require ("template.php");

function slct() {
	extract($_GET);

	$slct = "
			<h3>Hire Invoices Report</h3>
			<table ".TMPL_tblDflts." width='580'>
			<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			".(isset($mode)?"<input type='hidden' name='mode' value='$mode'>":"")."
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
			</td>
			</tr>
			<tr>
				<th colspan='2'>Display</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>
					<input type='radio' name='show' value='all' checked='t'> All
					<input type='radio' name='show' value='paid'> Paid
					<input type='radio' name='show' value='unpaid'> Unpaid
			</tr>
			<tr>
				<td colspan='2' align='right'>
					<input type='submit' value='Display &raquo;' />
				</td>
			</tr>
			</form>
			</table>";

	return $slct;
}

function printInvoice ($_POST)
{

	extract($_POST);

	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	$fromdate = mkdate($from_year, $from_month, $from_day);
	$todate = mkdate($to_year, $to_month, $to_day);

	$v->isOk ($fromdate, "date", 1, 1, "Invalid from date.");
	$v->isOk ($todate, "date", 1, 1, "Invalid to date.");

	if ($v->isError ()) {
		$err = $v->genErrors();
		return $err;
	}

	# Set up table to display in
	$printOrd = "
	<center>
	<h3>Hire Invoices Report</h3>
	<table ".TMPL_tblDflts.">
	<tr>
		<th>Invoice Num</th>
		<th>Invoice Date</th>
		<th>Customer</th>
		<th>Invoice Total</th>
		<th>Outstanding</th>
		<th>Amount Paid</td>
		<th>Paid/Unpaid</th>
	</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot_subtot = 0;
	$tot_total = 0;
	$tot_balance = 0;
	$tot_paid = 0;

	if ($show == "unpaid") {
		$filter = "AND balance>0";
	} else if ($show == "paid") {
		$filter = "AND balance<=0";
	} else {
		$filter = "";
	}

	$sql = "SELECT * FROM cubit.nons_invoices WHERE sdate >= '$fromdate' AND sdate <= '$todate' $filter AND div='".USER_DIV."' AND hire_invid > 0 ORDER BY invnum";
	$nonstksRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($nonstksRslt) < 1) {
		return "<li> There are no non stock invoices found.";
	}

	// Retrieve the PDF reprints
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='reprints' AND div='".USER_DIV."'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	while ($nonstks = pg_fetch_array ($nonstksRslt)) {
		// compute the totals
		if ($nonstks["xrate"] == 0.00) {
			$tot_subtot += $nonstks["subtot"];
			$tot_total += $nonstks["total"];
		} else {
			$tot_subtot += $nonstks["subtot"] * $nonstks["xrate"];
			$tot_total += $nonstks["total"] * $nonstks["xrate"];
		}

		# calculate the Sub-Total
		if ($nonstks['invnum'] == 0) {
			$nonstks['invnum']=$nonstks['invid'];
		}

		$det = "nons-invoice-det.php";
		$edit = "nons-invoice-new.php";
		$print = "nons-invoice-print.php";
		$reprint = "nons-invoice-reprint.php";
		$note = "nons-invoice-note.php";

		if ($template == "default") {
			$template = "nons-invoice-pdf-reprint.php";
		} elseif ($template == "new") {
			$template = "pdf-tax-invoice.php";
		}
		$reprpdf = $template;
		$cur = CUR;
		if($nonstks['location'] == 'int'){
			$det = "nons-intinvoice-det.php";
			$edit = "nons-intinvoice-new.php";
			$print = "nons-intinvoice-print.php";
			$note = "nons-intinvoice-note.php";
			if ($template == "default") {
				$template = "nons-intinvoice-pdf-reprint.php";
			} elseif ($template == "new") {
				$template = "pdf-tax-invoice.php";
			}
			$reprpdf = $template;
			$note = "nons-intinvoice-note.php";
			$cur = $nonstks['currency'];
		}

		# Get documents
		if ($nonstks["balance"] > 0) {
			$disp = "Unpaid";
		} else {
			$disp = "Paid";
		}

		$amt_paid = $nonstks["total"] - $nonstks["balance"];
		$tot_balance += $nonstks["balance"];
		$tot_paid += $amt_paid;

		$printOrd .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$nonstks[invnum]</td>
			<td>$nonstks[odate]</td>
			<td>$nonstks[cusname]</td>
			<td align=right>$cur ".sprint($nonstks["total"])."</td>
			<td align='right'>$cur ".sprint($nonstks["balance"])."</td>
			<td align='right'>$cur ".sprint($amt_paid)."</td>
			<td align='center'>$disp</td>
		</tr>";
	}

	$tot_total=sprint($tot_total);


	$printOrd .= "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='3'>Totals</td>
						<td align='right'>".CUR." ".sprint($tot_total)."</td>
						<td align='right'>".CUR." ".sprint($tot_balance)."</td>
						<td align='right'>".CUR." ".sprint($tot_paid)."</td>
						<td>&nbsp;</td>
					</tr>
				</table>"
	.mkQuickLinks(
		ql("hire-invoice-new.php", "New Hire")
	);

	return $printOrd;
}

function delete_confirm($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;

	$v->isOk($invid, "num", 1, 9, "Invalid invoice number.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return printInvoice($confirm);
	}

	// Retrieve information from Cubit.
	db_conn("cubit");
	$sql = "SELECT * FROM nons_invoices WHERE invid='$invid'";
	$ninvRslt = db_exec($sql) or errDie("Unable to retrieve non stock invoice information from Cubit.");
	$ninvData = pg_fetch_array($ninvRslt);

	// date format
	$date = explode("-", $ninvData["sdate"]);
	$date = $date[2]."-".$date[1]."-".$date[0];

	$OUTPUT = "
				<h3>Delete Unprocessed Non Stock Invoice</h3>
				<form method='POST' action='".SELF."'>
					<input type='hidden' name='key' value='delete_write'>
					<input type='hidden' name='invid' value='$invid'>
				<table ".TMPL_tblDftls.">
					<tr>
						<th colspan='2'>Confirm</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Invoice Num</td>
						<td>$ninvData[invid]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Proforma Inv Num</td>
						<td>$ninvData[docref]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Invoice Date</td>
						<td>$date</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Customer</td>
						<td>$ninvData[cusname]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Total</td>
						<td>".CUR."$ninvData[total]</td>
					</tr>
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Write &raquo'></td>
					</tr>
				</table>
				</form>";

	return $OUTPUT;
}

function delete_write($_POST)
{
	extract ($_POST);

	require_lib("validate");
	$v = new validate;

	$v->isOk($invid, "num", 1, 9, "Invalid invoice number.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return printInvoice($confirm);
	}

	db_conn("cubit");
	$sql = "DELETE FROM nons_invoices WHERE invid='$invid'";
	$ninvRslt = db_exec($sql) or errDie("Unable to retrieve non stock invoice information from Cubit.");

	if (pg_affected_rows($ninvRslt) > 0) {
		$OUTPUT = "<li>Invoice has been successfully removed.</li>";
	} else {
		$OUTPUT = "<li class=err>Invoice was not found.</li>";
	}

	return $OUTPUT;
}

function bdel_confirm($GET) {
	extract($GET);

	if (!is_array($ids)) {
		$ids = explode(",", $ids);
	}

	require_lib("validate");
	$v = new  validate ();
	foreach($ids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
	}

	if ($v->isError ()) {
		$err = $v->genErrors();
		$err .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $err;
	}

	$OUT = "
			<h3>Batch Delete Non-Stock Invoices</h3>
			Are you sure you wish to delete the following invoices?
			<form method='POST' action='".SELF."'>
				<input type='hidden' name='key' value='bdelwrite' />
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Invoice No.</th>
					<th>Invoice Date</th>
					<th>Customer Name</th>
					<th>Grand Total</th>
				</tr>";

	foreach ($ids as $invid) {
		db_connect();
		$sql = "SELECT * FROM nons_invoices
				WHERE invid = '$invid' AND div = '".USER_DIV."' AND done!='y'";
		$invRslt = db_exec ($sql) or errDie ("Unable to get recuring invoice information");
		if (pg_num_rows($invRslt) < 1) {
			return "<i class=err>Invoice Not Found, Please make sure you have selected a unprinted invoice.</i>";
		}
		$inv = pg_fetch_array($invRslt);

		$inv['total'] = sprint($inv['total']);
		$inv['balance'] = sprint($inv['balance']);

		# Format date
		list($oyear, $omon, $oday) = explode("-", $inv['odate']);

		$OUT .= "
					<input type='hidden' name='ids[]' value='$inv[invid]' />
					<tr bgcolor='".bgcolorg()."'>
						<td>T $inv[invid]</td>
						<td valign='center'>$oday-$omon-$oyear</td>
						<td>$inv[cusname]</td>
						<td align=right>".CUR." $inv[total]</td>
					</tr>";
	}

	$OUT .= "
	<tr>
		<td colspan='4' align='right'><input type='submit' value='Process &gt;' /></td>
	</tr>
	</table>
	</form>"
	.mkQuickLinks(
		ql("nons-invoice-view.php", "View Non-Stock Invoices")
	);

	return $OUT;
}

function bdel_write($GET) {
	extract($GET);

	require_lib("validate");
	$v = new  validate ();
	foreach($ids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
	}

	if ($v->isError ()) {
		$err = $v->genErrors();
		$err .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $err;
	}

	$del = new dbDelete("nons_invoices", "cubit");

	foreach($ids as $key => $invid){
		$del->setOpt(wgrp(
			m("invid", $invid)
		));
		$del->run();
	}

	$OUT = "
			<h3>Batch Delete Non-Stock Invoices</h3>
			Successfully deleted non-stock invoices."
			.mkQuickLinks(
				ql("nons-invoice-view.php", "View Non-Stock Invoices")
			);

	return $OUT;
}

?>