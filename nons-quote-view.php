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
}elseif (
	isset($_GET["from_year"]) && isset($_GET["to_year"]) && isset($_GET["from_month"]) && 
	isset($_GET["to_month"]) && isset($_GET["from_day"]) && isset($_GET["to_day"])
) {
	$OUTPUT = printInvoice ($HTTP_GET_VARS);
}else {
	# Display default output
	$OUTPUT = slct ();
}

require ("template.php");




# Default view
function slct()
{

	extract ($_REQUEST);
	
	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	
	extract ($fields, EXTR_SKIP);
	
	//layout
	$slct = "
		<h3>View Non-Stock Quotes</h3>
		<table ".TMPL_tblDflts." width='550'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center' nowrap>
					".mkDateSelect("from", $from_year, $from_month, $from_day)."
					&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
					".mkDateSelect("to", $to_year, $to_month, $to_day)."
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
				<td><a href='nons-quote-new.php'>New Non Stock Quote</a></td>
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
	extract ($HTTP_POST_VARS);

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
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}


	# Set up table to display in
	$printOrd = "
		<center>
		<h3>View Non-Stock Quotes</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quote Num</th>
				<th>Quote Date</th>
				<th>Next Contact Date</th>
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

	$sql = "
		SELECT invnum, invid, sdate, odate, cusname, subtot, total, done, multiline, ncdate 
		FROM nons_invoices 
		WHERE typ = 'quo' AND odate >= '$fromdate' AND odate <= '$todate' AND div = '".USER_DIV."' 
		ORDER BY invnum";
	$nonstksRslt = db_exec ($sql) or errDie ("Unable to retrieve quotes from database.");
	if (pg_numrows ($nonstksRslt) < 1) {
		return "
			<li class='err'> No non stock quotes found.</li>
			<p>
			<table border='0' cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='nons-quote-new.php'>New Non Stock Quote</a></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}

	while ($nonstks = pg_fetch_array ($nonstksRslt)) {
		# date format
		$date = explode("-", $nonstks['odate']);
		$date = $date[2]."-".$date[1]."-".$date[0];

		// compute the totals
		$tot_subtot += $nonstks["subtot"];
		$tot_total += $nonstks["total"];

		# calculate the Sub-Total
		if($nonstks['invnum'] == 0) {
			$nonstks['invnum'] = $nonstks['invid'];
		}
		
		$nonstks['subtot'] = sprint ($nonstks['subtot']);
		$nonstks['total'] = sprint ($nonstks['total']);

		if (isset($nonstks['multiline']) AND $nonstks['multiline'] == "yes"){
			$editscript = "nons-multiline-quote-new.php";
		}else {
			$editscript = "nons-quote-new.php";
		}

		$printOrd .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$nonstks[invnum]</td>
				<td>$date</td>
				<td align='center'>$nonstks[ncdate]</td>
				<td>$nonstks[cusname]</td>
				<td align='right'>".CUR." $nonstks[subtot]</td>
				<td align='right'>".CUR." $nonstks[total]</td>
				<td><a href='nons-quote-det.php?invid=$nonstks[invid]'>Details</a></td>";

		if ( $nonstks['done'] != "y" && $nonstks["subtot"] == 0 ) {
			$printOrd .= "
					<td><a href='$editscript?invid=$nonstks[invid]&cont=1'>Edit</a></td>
				</tr>";
		} elseif($nonstks['done'] != "y") {
			$printOrd .= "
					<td><a href='$editscript?invid=$nonstks[invid]&cont=1'>Edit</a></td>
					<td><a href='nons-quote-print.php?invid=$nonstks[invid]' target='_blank'>Print</a></td>
					<td><a href='pdf/nons-quote-pdf-print.php?invid=$nonstks[invid]' target='_blank'>Print in PDF</a></td>
					<td><a href='nons-quote-acc.php?invid=$nonstks[invid]'>Accept</a></td>
				</tr>";
		} else {
			$printOrd .= "
					<td colspan='4'><a href='nons-quote-print.php?invid=$nonstks[invid]' target='_blank'>Reprint</a></td>
				</tr>";
		}
		$i++;
	}

	$tot_subtot = sprint ($tot_subtot);
	$tot_total = sprint ($tot_total);

	$printOrd .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'>Totals</td>
			<td align='right'>".CUR." $tot_subtot</td>
			<td align='right'>".CUR." $tot_total</td>
		</tr>
	</table>
	<p>
	<table ".TMPL_tblDflts.">
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='nons-quote-new.php'>New Non-Stock Quotes</a></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='main.php'>Main Menu</a></td>
		</tr>
	</table>";
	return $printOrd;

}



?>