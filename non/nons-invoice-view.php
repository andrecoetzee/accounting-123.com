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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printInvoice ($_POST);
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
		<tr class='bg-odd'><td align=center>
		<input type=text size=2 name=fday maxlength=2 value='1'>-<input type=text size=2 name=fmon maxlength=2  value='".date("m")."'>-<input type=text size=4 name=fyear maxlength=4 value='".date("Y")."'>
		&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
		<input type=text size=2 name=today maxlength=2 value='".date("d")."'>-<input type=text size=2 name=tomon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=toyear maxlength=4 value='".date("Y")."'>
		</td><td valign=bottom><input type=submit value='Search'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='nons-invoices-new.php'>New Non Stock Invoice</a></td></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
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
	<tr>
		<th>Invoice Date</th>
		<th>Customer</th>
		<th>Sub Total</th>
		<th>Total Cost Amount</th>
		<th colspan=4>Options</th>
	</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot_subtot = 0;
	$tot_total = 0;

	$sql = "SELECT invid,sdate,cusname,subtot,total,done FROM nons_invoices WHERE sdate >= '$fromdate' AND sdate <= '$todate' AND div = '".USER_DIV."' ORDER BY sdate DESC";
	$nonstksRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices from database.");
	if (pg_numrows ($nonstksRslt) < 1) {
		return "<li> There are no non stock invoices found.";
	}
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

		$printOrd .= "<tr bgcolor='$bgColor'>
			<td>$date</td>
			<td>$nonstks[cusname]</td>
			<td align=right>".CUR." $nonstks[subtot]</td>
			<td align=right>".CUR." $nonstks[total]</td>
			<td><a href='nons-invoice-det.php?invid=$nonstks[invid]'>Details</a></td>";

		if ( $nonstks['done'] != "y" && $nonstks["subtot"] == 0 ) {
			$printOrd .= "<td><a href='nons-invoice-new.php?invid=$nonstks[invid]&cont=1'>Edit</a></td>
				</tr>";
		} elseif($nonstks['done'] != "y") {
			$printOrd .= "<td><a href='nons-invoice-new.php?invid=$nonstks[invid]&cont=1'>Edit</a></td>
				<td><a target='_blank' href='nons-invoice-print.php?invid=$nonstks[invid]'>Print</a></td>
			</tr>";
		} else {
			$printOrd .= "<td colspan=3><a target='_blank' href='nons-invoice-print.php?invid=$nonstks[invid]'>Reprint</a></td>
			</tr>";
		}
		$i++;
	}

	$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
	$printOrd .= "<tr bgcolor='$bgColor'><td colspan=2>Totals</td><td align=right>".CUR." $tot_subtot</td><td align=right>".CUR." $tot_total</td></tr>
	</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='nons-invoice-new.php'>New Non-Stock Invoices</a></td></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $printOrd;
}
?>
