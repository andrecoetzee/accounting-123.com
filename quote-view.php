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

##
# quotes-view.php :: Module to view & print quotes
##

require ("settings.php");

if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);
	switch ($button) {
		case "nonsquote":
			extract ($_REQUEST);
			header("Location: nons-quote-view.php?"
				."from_year=$from_year&from_month=$from_month"
				."&from_day=$from_day&to_year=$to_year"
				."&to_month=$to_month&to_day=$to_day");
		break;
	}
} elseif (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "Send Emails":
			if(!isset($_REQUEST["evs"]) OR !is_array($_REQUEST["evs"])){
				$OUTPUT = printInv ();
			}else {
				$evss = implode (",",$_REQUEST["evs"]);
				header ("Location: quote-email.php?evs=$evss");
			}
			break;
		case "view":
		default:
			$OUTPUT = printInv ();
			break;
	}
} else {
	$OUTPUT = printInv();
}

$OUTPUT .= mkQuickLinks(
	ql("quote-new.php", "New Quote"),
	ql("customers-new.php", "New Customer")
);

require ("template.php");

##
# Functions
##

# show quotes
function printInv ()
{

	extract($_REQUEST);

	if (isset($key)) {
		$key = strtolower($key);
		switch ($key) {
			case "export to spreadsheet":
			case "print":
			case "save":
				$pure = true;
				break;
			case "view":
			default:
				$pure = false;
		}
	} else {
		$pure = false;
	}

	if (!isset($from_year)) {
		explodeDate(false, $from_year, $from_month, $from_day);
		explodeDate(false, $to_year, $to_month, $to_day);
		explodeDate(date ("Y-m-")."01", $ncdate_from_year, $ncdate_from_month, $ncdate_from_day);
		explodeDate(false, $ncdate_to_year, $ncdate_to_month, $ncdate_to_day);
	}

	if (!isset($type)) {
		$type = "all";
	}
	if(!isset($cust))
		$cust = "";
	
	if(!isset($ordnosearch))
		$ordnosearch = "";

	$sel_accepted = ($type == "accepted") ? "checked='t'" : "";
	$sel_notaccepted = ($type == "notaccepted") ? "checked='t'" : "";
	$sel_all = ($type != "accepted" && $type != "notaccepted") ? "checked='t'" : "";

	$printQuo = "";

	if (isset ($check_followon) AND strlen ($check_followon) > 0){
		$checkfollowonbox = "checked='yes'";
	}

	if (!$pure) {
		$printQuo .= "
		<form method='post' action='".SELF."'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>View Options</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Begin Date:</td>
				<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>End Date:</td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Follow On Date From:</td>
				<td>".mkDateSelect("ncdate_from", $ncdate_from_year, $ncdate_from_month, $ncdate_from_day)."
				Check Followon <input type='checkbox' name='check_followon' value='yes' $checkfollowonbox></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Follow On Date To:</td>
				<td>".mkDateSelect("ncdate_to", $ncdate_to_year, $ncdate_to_month, $ncdate_to_day)."</td>
			</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Type:</td>
			<td>
				<input type='radio' name='type' $sel_accepted value='accepted' /> Accepted
				<input type='radio' name='type' $sel_notaccepted value='notaccepted' /> Not Yet Accepted
				<input type='radio' name='type' $sel_all value='all' /> All
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Customer:</td>
			<td><input type='text' size='20' name='cust' value='$cust'></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Quote No:</td>
			<td><input type='text' size='6' name='ordnosearch' value='$ordnosearch'></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>View Other Quotes</td>
			<td>
				<input type='button' value='POS (Non Customers) Quotes' onClick=\"document.location='pos-quote-view.php'\">
				<input type='submit' name='button[nonsquote]' value='Non Stock Quotes'>
			</td>
		</tr>
		<tr>
			<td colspan='2' align='right'><input type='submit' name='key' value='Filter' /></td>
		</tr>
		</table>";
	}

	if (!isset($key)) {
		$printQuo .= "</form>";
		return $printQuo;
	}

	$filt = "odate >= '$from_year-$from_month-$from_day' AND odate <= '$to_year-$to_month-$to_day'";

	if (isset ($check_followon) AND strlen ($check_followon) > 0){
		$filt .= " AND (ncdate >= '$ncdate_from_year-$ncdate_from_month-$ncdate_from_day' AND ncdate <= '$ncdate_to_year-$ncdate_to_month-$ncdate_to_day')";
	}

	switch ($type) {
		case "accepted":
			$filt .= " AND accepted='y'";
			break;
		case "notaccepted":
			$filt .= " AND accepted='n'";
			break;
		case "all":
		default:
	}
	
	$filt .= " AND lower(surname) LIKE lower('%$cust%')";
	$filt .= " AND ordno LIKE '%$ordnosearch%'";

	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='invoices'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	if ($template == "invoice-print.php") {
		$pdf = "pdf/quote-pdf-print.php?quoid=";
	} else {
		$pdf = "pdf/pdf-quote.php?quoid=";
	}

	# Set up table to display in
	$printQuo .= "
		<h3>View previous Quotes</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Department</th>
				<th>Sales Person</th>
				<th>Quote No.</th>
				<th>Quote Date</th>
				<th>Next Contact Date</th>
				<th>Customer Name</th>
				<th>Order No</th>
				<th>Grand Total</th>
				".($pure?"":"<th colspan='6'>Options</th><th>Email</th>")."
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;

	$sql = "SELECT * FROM quotes WHERE accepted != 'c' AND done = 'y' AND div = '".USER_DIV."' AND ($filt) ORDER BY quoid DESC";
	$quoRslt = db_exec ($sql) or errDie ("Unable to retrieve quotes from database.");
	if (pg_numrows ($quoRslt) < 1) {
		$printQuo .= "
			<tr bgcolor='".bgcolorc(0)."'>
				<td colspan='14'>No Quotes matching criteria.</td>
			</tr>
			".TBL_BR;
	}else{
		while ($quo = pg_fetch_array ($quoRslt)) {

			# format date
			$quo['odate'] = explode("-", $quo['odate']);
			$quo['odate'] = $quo['odate'][2]."-".$quo['odate'][1]."-".$quo['odate'][0];

			$printQuo .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$quo[deptname]</td>
					<td>$quo[salespn]</td>
					<td>$quo[quoid]</td>
					<td align='center'>$quo[odate]</td>
					<td align='center'>$quo[ncdate]</td>
					<td>$quo[cusname] $quo[surname]</td>
					<td align='right'>$quo[ordno]</td>
					<td>".CUR." $quo[total]</td>";
//<td><a href='quote-email.php?evs=$quo[quoid]'>Email</a></td>

			if (!$pure) {
				$printQuo .= "
					<td><a href='quote-details.php?quoid=$quo[quoid]'>Details</a></td>
					<td><a href='quote-new.php?quoid=$quo[quoid]&cont=true&letters=&done='>Edit</a></td>";

				if ($quo['accepted'] == 'n') {
				$printQuo .= "
						<td><a href='quote-cancel.php?quoid=$quo[quoid]'>Cancel</a></td>
						<td><a href='quote-accept.php?quoid=$quo[quoid]'>Accept</a></td>
						<td><a href='quote-print.php?quoid=$quo[quoid]' target='_blank'>Print</a></td>
						<td><a href='$pdf$quo[quoid]' target='_blank'>Print in PDF</a></td>
						<td><input type='checkbox' name='evs[]' value='$quo[quoid]'></td>
					</tr>";
				} else {
				$printQuo .="
						<td colspan='2'>Accepted</td>
						<td><a href='quote-print.php?quoid=$quo[quoid]' target='_blank'>Print</a></td>
						<td><a href='$pdf$quo[quoid]' target='_blank'>Print in PDF</a></td>
						<td><input type='checkbox' name='evs[]' value='$quo[quoid]'></td>
					</tr>";
				}
			}
		}
	}

	if (!$pure) {
		$printQuo .= "
				<tr>
					<td colspan='13'>
						<input type='submit' name='key' value='Print'>
						| <input type='submit' name='key' value='Export to Spreadsheet'>
					</td>
					<td colspan='2' align='right'><input type='submit' name='key' value='Send Emails'></td>
				</tr>
			</table>
			</form>";
	} else {
		$printQuo .= "
			</table>";

		$OUTPUT = clean_html($printQuo);

		switch ($key) {
			case "export to spreadsheet":
				require_lib("xls");
				StreamXLS("quotes", $OUTPUT);
				break;
			case "print":
				$OUTPUT = "<h3>Quotes</h3>$OUTPUT";
				require("tmpl-print.php");
				break;
			case "save":
				$pure = true;
				break;
		}
	}

	// Layout
// 	$printQuo .= "</table>"
// 		.mkQuickLinks(
// 			ql("quote-new.php", "New Quote"),
// 			ql("customers-new.php", "New Customer")
// 		);
	return $printQuo;

}


?>
