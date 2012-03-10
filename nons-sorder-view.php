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

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
        case "view":
			$OUTPUT = printInvoice ($HTTP_POST_VARS);
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
			<h3>View Non-Stock Sales Orders</h3>
			<table ".TMPL_tblDflts." width='580'>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='view'>
				<tr>
					<th colspan='2'>By Date Range</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td align='center'>
						".mkDateSelect("from",date("Y"),date("m"),"01")."
						&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
						".mkDateSelect("to")."
					</td>
					<td valign='bottom'><input type='submit' value='Search'></td>
				</tr>
			</form>
			</table>
			<p>
			<table border='0' cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-sorder-new.php'>New Non Stock Sales Order</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";

	return $slct;
}

# show
function printInvoice ($HTTP_POST_VARS)
{

	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	# mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."<br>";
		}
        return $confirm;
	}

	# Set up table to display in
	$printOrd = "
			<center>
			<h3>View Non-Stock Sales Orders</h3>
			<table ".TMPL_tblDflts.">
			<tr>
				<th>Sales Order Num</th>
				<th>Sales Order Date</th>
				<th>Customer</th>
				<th>Sub Total</th>
				<th>Total Cost Amount</th>
				<th colspan='5'>Options</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$tot_subtot = 0;
	$tot_total = 0;

	$sql = "SELECT invnum,invid,sdate,cusname,subtot,total,done,odate FROM nons_invoices WHERE typ = 'sord' AND odate >= '$fromdate' AND odate <= '$todate' AND div = '".USER_DIV."' ORDER BY invnum";
	$nonstksRslt = db_exec ($sql) or errDie ("Unable to retrieve sales orders from database.");
	if (pg_numrows ($nonstksRslt) < 1) {
		return "
			<li class='err'> No Non Stock Sales Orders Found.</li><br><br>"
			.mkQuickLinks(
				ql("nons-sorder-new.php", "New Non Stock Sales Order"),
				ql("nons-sorder-view.php", "View Non Stock Sales Orders")
			);
	}
	while ($nonstks = pg_fetch_array ($nonstksRslt)) {
		# date format
		$date = explode("-", $nonstks['odate']);
	//	$date = $date[2]."-".$date[1]."-".$date[0];

		// compute the totals
		$tot_subtot += $nonstks["subtot"];
		$tot_total += $nonstks["total"];

		# calculate the Sub-Total


		if($nonstks['invnum']==0) {
			$nonstks['invnum']=$nonstks['invid'];
		}
		$printOrd .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$nonstks[invnum]</td>
					<td>$nonstks[odate]</td>
					<td>$nonstks[cusname]</td>
					<td align=right>".CUR." $nonstks[subtot]</td>
					<td align=right>".CUR." $nonstks[total]</td>
					<td><a href='nons-sorder-det.php?invid=$nonstks[invid]'>Details</a></td>";

		if ( $nonstks['done'] != "y" && $nonstks["subtot"] == 0 ) {
			$printOrd .= "
					<td><a href='nons-sorder-new.php?invid=$nonstks[invid]&cont=1'>Edit</a></td>
				</tr>";
		} elseif($nonstks['done'] != "y") {
			$printOrd .= "
					<td><a href='nons-sorder-new.php?invid=$nonstks[invid]&cont=1'>Edit</a></td>
					<td><a href='nons-sorder-print.php?invid=$nonstks[invid]' target='_blank'>Print</a></td>
					<td><a href='pdf/nons-sorder-pdf-print.php?invid=$nonstks[invid]' target='_blank'>Print in PDF</a></td>
					<td><a href='nons-sorder-acc.php?invid=$nonstks[invid]'>Accept</a></td>
				</tr>";
		} else {
			$printOrd .= "
					<td colspan='4'><a href='nons-sorder-print.php?invid=$nonstks[invid]' target='_blank'>Reprint</a></td>
				</tr>";
		}
		$i++;
	}

	$printOrd .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='3'>Totals</td>
			<td align='right'>".CUR." ".sprint($tot_subtot)."</td>
			<td align='right'>".CUR." ".sprint($tot_total)."</td>
		</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr><td><br></td></tr>
			<tr>
		<th>Quick Links</th></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='nons-sorder-new.php'>New Non-Stock Sales Orders</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='nons-sorder-view.php'>View Non Stock Sales Orders</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";

	return $printOrd;
}
?>
