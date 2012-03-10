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

require ("settings.php");
require ("core-settings.php");
require_lib("docman");

// Merge get vars and post vars
foreach ($HTTP_GET_VARS as $key => $val) {
	$HTTP_POST_VARS[$key] = $val;
}

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
        case "view":
			$OUTPUT = printInvoice ($HTTP_POST_VARS);
			break;
        case "bdel":
        	$OUTPUT = bdel_confirm($HTTP_POST_VARS);
        	break;
        case "bdelwrite":
        	$OUTPUT = bdel_write($HTTP_POST_VARS);
        	break;
		case "delete_confirm":
			$OUTPUT = delete_confirm ($HTTP_POST_VARS);
			break;
		case "delete_write":
			$OUTPUT = delete_write ($HTTP_POST_VARS);
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




function slct()
{

	extract($_GET);

	db_connect ();

	$cust_arr = array ();
	$ncust_arr = array ();

	#get list of all customers
	$get_cust = "SELECT surname FROM customers ORDER by surname";
	$run_cust = db_exec($get_cust) or errDie("Unable to get customers information.");
	if(pg_numrows($run_cust) > 0)
		while ($temp = pg_fetch_array($run_cust))
			$cust_arr[] = $temp['surname'];
	
	#now get the non stock invoices customers
	$get_ncust = "SELECT distinct(cusname) FROM nons_invoices ORDER BY cusname";
	$run_ncust = db_exec($get_ncust) or errDie("Unable to get customers information.");
	if(pg_numrows($run_ncust) > 0)
		while ($temp = pg_fetch_array($run_ncust))
			$ncust_arr[] = $temp['cusname'];

	$allcust_arr = array_merge($cust_arr,$ncust_arr);
	$allcust_arr = array_unique($allcust_arr);
	ksort($allcust_arr);
	
	#make customer drop ...
	$cust_drop = "<select name='customer'>";
	$cust_drop .= "<option value='0'>All</option>";
	foreach ($ncust_arr as $each){
		$cust_drop .= "<option value='$each'>$each</option>";
	}
	$cust_drop .= "</select>";

	$slct = "
		<h3>View Non-Stock Invoices</h3>
		<table ".TMPL_tblDflts." width='580'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			".(isset($mode)?"<input type='hidden' name='mode' value='$mode'>":"")."
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center' nowrap>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
			</td>
				<td valign='bottom'><input type='submit' value='Search'></td>
			</tr>
			<tr>
				<th>Select Customer</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$cust_drop</td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='nons-invoice-new.php'>New Non Stock Invoice</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $slct;

}



function printInvoice ($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

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
		<h3>View Non-Stock Invoices</h3>
		<table ".TMPL_tblDflts.">
		<form action='invoice-proc.php' method='POST'>
			<input type='hidden' name='t' value='i'>
			<tr>
				<th>Invoice Num</th>
				<th>Proforma Inv No.</th>
				<th>Invoice Date</th>
				<th>Customer</th>
				<th>Total</th>
				<th>Documents</th>
				<th colspan=6>Options</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot_subtot = 0;
	$tot_total = 0;
	
	$cust_search = "";
	if(isset ($customer) AND ($customer != "0")){
		$cust_search = "AND cusname = '$customer'";
	}

	$sql = "SELECT * FROM nons_invoices WHERE typ = 'inv' AND sdate >= '$fromdate' 	AND sdate <= '$todate' AND div = '".USER_DIV."' AND balance > 0 $cust_search ORDER BY invnum";
	$nonstksRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($nonstksRslt) < 1) {
		return "
			<li class='err'> No Non Stock Invoices Could Be Found.</li>
			<p>
			<table border='0' cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-invoice-new.php'>New Non Stock Invoice</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-invoice-view.php'>View Non Stock Invoices</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
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
		if($nonstks['invnum'] == 0) {
			$nonstks['invnum'] = $nonstks['invid'];
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
		$docs = doclib_getdocs("ninv", $nonstks['invnum']);

		if($nonstks['accepted']==" " &&$nonstks['done'] != "y") {
			$chbox = "<input type=checkbox name='evs[$nonstks[invid]]' value='$nonstks[invid]' checked=yes>";
		} else {
			$chbox="";
		}

		$printOrd .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$nonstks[invnum]</td>
				<td>$nonstks[docref]</td>
				<td>$nonstks[odate]</td>
				<td>$nonstks[cusname]</td>
				<td align='right'>$cur $nonstks[total]</td>
				<td>$docs</td>
				<td>$chbox</td>
				<td><a href='$det?invid=$nonstks[invid]'>Details</a></td>";

		if ( $nonstks['done'] != "y" && $nonstks["subtot"] == 0 ) {
			$printOrd .= "
					<td><a href='$edit?invid=$nonstks[invid]&cont=1'>Edit</a></td>
					<td><a href='?key=delete_confirm&invid=$nonstks[invid]'>Delete</a></td>
				</tr>";
		} elseif($nonstks['done'] != "y") {
			$printOrd .= "
					<td><a href='$edit?invid=$nonstks[invid]&cont=1'>Edit</a></td>
					<td><a href='?key=delete_confirm&invid=$nonstks[invid]'>Delete</a></td>
					<td><a href=# onClick=printer('$print?invid=$nonstks[invid]&type=nons')>Process</a></td>
				</tr>";
		} else {
			$cn = "";
			if($nonstks['accepted'] != "note")
				if (isset($mode) && $mode == "creditnote") {
					$cn = "<input type='button' onClick=\"printer('$note?invid=$nonstks[invid]&type=nonsnote');\" value='Credit Note'>";
				} else {
					$cn = "<a href='#' onClick=printer('$note?invid=$nonstks[invid]&type=nonsnote')>Credit Note</a>";
				}

			$printOrd .= "
					<td>$cn</td>
					<td><a href='#' onClick=printer('$reprint?invid=$nonstks[invid]&type=nonsreprint')>Reprint</a></td>
					<td><a href='pdf/$reprpdf?invid=$nonstks[invid]&type=nonsreprint' target='_blank'>Reprint in PDF</a></td>
					<td><input type='checkbox' name='evs[$nonstks[invid]]'></td>
				</tr>";
		}
	}

	$tot_total = sprint($tot_total);

	$printOrd .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4'>Totals</td>
				<td align='right'>".CUR." $tot_total</td>
				<td colspan='4' align='right'>
					<input type='submit' value='Delete Batch' name='btndelete' />
					<input type='submit' value='Process Selected' name='print' />
				</td>
				<td colspan='3' align='right'><input type='submit' value='Email Selected' name='email'></td>
			</tr>
		</table>
		</form><p>"
	.mkQuickLinks(
		ql("nons-invoice-new.php", "New Non-Stock Invoice")
	);
	return $printOrd;

}



function delete_confirm($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	require_lib("validate");
	$v = new validate;

	$v->isOk($invid, "num", 1, 9, "Invalid invoice number.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
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
		<table ".TMPL_tblDflts.">
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
		</form>
		<p>
			<table border='0' cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-invoice-new.php'>New Non Stock Invoice</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-invoice-view.php'>View Non Stock Invoices</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='rec-nons-invoice-view.php'>View Recurring Non Stock Invoices</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='rec-invoice-view.php'>View Recurring Invoices</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	return $OUTPUT;

}




function delete_write($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	require_lib("validate");
	$v = new validate;

	$v->isOk($invid, "num", 1, 9, "Invalid invoice number.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return printInvoice($confirm);
	}

	db_conn("cubit");

	$sql = "DELETE FROM nons_invoices WHERE invid='$invid'";
	$ninvRslt = db_exec($sql) or errDie("Unable to retrieve non stock invoice information from Cubit.");

	if (pg_affected_rows($ninvRslt) > 0) {
		$OUTPUT = "
			<li class='err'>Invoice has been successfully removed.</li>
			<p>
			<table border='0' cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-invoice-new.php'>New Non Stock Invoice</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-invoice-view.php'>View Non Stock Invoices</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='rec-nons-invoice-view.php'>View Recurring Non Stock Invoices</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='rec-invoice-view.php'>View Recurring Invoices</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	} else {
		$OUTPUT = "<li class='err'>Invoice was not found.</li>";
	}

	return $OUTPUT;

}




function bdel_confirm($GET)
{

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
		$err .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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
		$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."' AND done!='y'";
		$invRslt = db_exec ($sql) or errDie ("Unable to get recuring invoice information");
		if (pg_num_rows($invRslt) < 1) {
			return "<i class='err'>Invoice Not Found, Please make sure you have selected a unprinted invoice.</i>";
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




function bdel_write($GET)
{

	extract($GET);

	require_lib("validate");
	$v = new  validate ();
	foreach($ids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
	}

	if ($v->isError ()) {
		$err = $v->genErrors();
		$err .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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
