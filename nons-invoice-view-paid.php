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
foreach ($_GET as $key => $val) {
	$_POST[$key] = $val;
}

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printInvoice ($_POST);
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

# Default view
function slct()
{
	//layout
	$slct = "
	<h3>View Non-Stock Invoices</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=view>
		<tr><th>By Date Range</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center>
		<input type=text size=2 name=fday maxlength=2 value='1'>-<input type=text size=2 name=fmon maxlength=2  value='".date("m")."'>-<input type=text size=4 name=fyear maxlength=4 value='".date("Y")."'>
		&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
		<input type=text size=2 name=today maxlength=2 value='".date("d")."'>-<input type=text size=2 name=tomon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=toyear maxlength=4 value='".date("Y")."'>
		</td><td valign=bottom><input type=submit value='Search'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='nons-invoice-new.php'>New Non Stock Invoice</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $slct;
}

# show
function printInvoice ($_POST)
{

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
	# mix dates
	$fromdate = $fyear."-".$fmon."-".$fday;
	$todate = $toyear."-".$tomon."-".$today;

	if(!checkdate($fmon, $fday, $fyear)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($tomon, $today, $toyear)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printOrd = "<center>
	<h3>View Non-Stock Invoices</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='invoice-proc.php' method=get>
	<input type=hidden name=t value=i>
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

	$sql = "SELECT * FROM nons_invoices WHERE typ = 'inv' AND sdate >= '$fromdate' AND sdate <= '$todate' AND div = '".USER_DIV."' AND balance='0' ORDER BY invnum";
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
		# date format
		$date = explode("-", $nonstks['sdate']);
		$date = $date[2]."-".$date[1]."-".$date[0];

		// compute the totals
		$tot_subtot += $nonstks["subtot"];
		$tot_total += $nonstks["total"];

		# calculate the Sub-Total
		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

		if($nonstks['invnum']==0) {
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
		$docs = doclib_getdocs("ninv", $nonstks['invnum']);
		
		if($nonstks['accepted']==" " &&$nonstks['done'] != "y") {
			$chbox = "<input type=checkbox name='evs[$nonstks[invid]]' value='$nonstks[invid]' checked=yes>";
		} else {
			$chbox="";
		}
		
		$printOrd .= "<tr bgcolor='$bgColor'>
			<td>$nonstks[invnum]</td>
			<td>$nonstks[docref]</td>
			<td>$date</td>
			<td>$nonstks[cusname]</td>
			<td align=right>$cur $nonstks[total]</td>
			<td>$docs</td>
			<td>$chbox</td>
			<td><a href='$det?invid=$nonstks[invid]'>Details</a></td>";

		if ( $nonstks['done'] != "y" && $nonstks["subtot"] == 0 ) {
			$printOrd .= "<td><a href='$edit?invid=$nonstks[invid]&cont=1'>Edit</a></td>
				<td><a href='?key=delete_confirm&invid=$nonstks[invid]'>Delete</a></td>
				</tr>";
		} elseif($nonstks['done'] != "y") {
			$printOrd .= "<td><a href='$edit?invid=$nonstks[invid]&cont=1'>Edit</a></td>
				<td><a href='?key=delete_confirm&invid=$nonstks[invid]'>Delete</a></td>
				<td><a href=# onClick=printer('$print?invid=$nonstks[invid]&type=nons')>Process</a></td>
			</tr>";
		} else {
			$cn = "";
			if($nonstks['accepted'] !="note")
				$cn = "<a href=# onClick=printer('$note?invid=$nonstks[invid]&type=nonsnote')>Credit Note</a>";

			$printOrd .= "<td>$cn</td><td><a target='_blank' href='$reprint?invid=$nonstks[invid]&type=nons'>Reprint</a></td><td><a href='pdf/$reprpdf?invid=$nonstks[invid]&type=nons' target=_blank>Reprint in PDF</a></td><td><input type=checkbox name='evs[$nonstks[invid]]'></td>
			</tr>";
		}
		$i++;
	}
	$tot_total=sprint($tot_total);
	

	$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
	$printOrd .= "<tr bgcolor='$bgColor'><td colspan=4>Totals</td><td align=right>".CUR." $tot_total</td><td colspan=3 align=right><input type=submit value='Process Selected' name=print></td><td colspan=3 align=right><input type=submit value='Email Selected' name=email></td></tr>
	</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

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

	$OUTPUT = "<h3>Delete Unprocessed Non Stock Invoice</h3>
	<form method=post action='".SELF."'>
	<input type=hidden name=key value='delete_write'>
	<input type=hidden name=invid value='$invid'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	  <tr>
	    <th colspan=2>Confirm</th>
	  </tr>
	  <tr bgcolor='".TMPL_tblDataColor1."'>
	    <td>Invoice Num</td>
	    <td>$ninvData[invid]</td>
	  </tr>
	  <tr bgcolor='".TMPL_tblDataColor2."'>
	    <td>Proforma Inv Num</td>
	    <td>$ninvData[docref]</td>
	  </tr>
	  <tr bgcolor='".TMPL_tblDataColor1."'>
	    <td>Invoice Date</td>
	    <td>$date</td>
	  </tr>
	  <tr bgcolor='".TMPL_tblDataColor2."'>
	    <td>Customer</td>
	    <td>$ninvData[cusname]</td>
	  </tr>
	  <tr bgcolor='".TMPL_tblDataColor1."'>
	    <td>Total</td>
	    <td>".CUR."$ninvData[total]</td>
	  </tr>
	  <tr>
	    <td colspan=2 align=right><input type=submit value='Write &raquo'></td>
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