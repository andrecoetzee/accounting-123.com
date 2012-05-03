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
# pos-quotes-view.php :: Module to view & print pos quotes
##

require ("settings.php");

if (!isset($_REQUEST["key"])) {
	$_REQUEST["key"] = "view";
}

switch ($_REQUEST["key"]) {
	case "Send Emails":
		if(!isset($_REQUEST["evs"]) OR !is_array($_REQUEST["evs"])){
			$OUTPUT = printInv ();
		}else {
			$evss = implode (",",$_REQUEST["evs"]);
			header ("Location: pos-quote-email.php?evs=$evss");
		}
		break;
	case "view":
	default:
		$OUTPUT = printInv ();
		break;
}

require ("template.php");



##
# Functions
##

# show quotes
function printInv ()
{
	# Set up table to display in
	$printQuo = "
					<h3>View previous POS Quotes</h3>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<tr>
							<th>Department</th>
							<th>Sales Person</th>
							<th>Quote No.</th>
							<th>Quote Date</th>
							<th>Customer Name</th>
							<th>Order No</th>
							<th>Grand Total</th>
							<th colspan='6'>Options</th>
							<th>Email</th>
						</tr>";

		# connect to database
		db_connect ();

		# Query server
		$i = 0;
        $sql = "SELECT * FROM pos_quotes WHERE accepted != 'c' AND done = 'y' AND div = '".USER_DIV."' ORDER BY quoid DESC";
        $quoRslt = db_exec ($sql) or errDie ("Unable to retrieve quotes from database.");
		if (pg_numrows ($quoRslt) < 1) {
			$printQuo = "<li>No previous quotes.</li>";
		}else{
			while ($quo = pg_fetch_array ($quoRslt)) {

				# format date
				$quo['odate'] = explode("-", $quo['odate']);
				$quo['odate'] = $quo['odate'][2]."-".$quo['odate'][1]."-".$quo['odate'][0];

				$printQuo .= "
								<tr class='".bg_class()."'>
									<td>$quo[deptname]</td>
									<td>$quo[salespn]</td>
									<td>$quo[quoid]</td>
									<td align='center'>$quo[odate]</td>
									<td>$quo[cusname] $quo[surname]</td>
									<td align=right>$quo[ordno]</td>
									<td>".CUR." $quo[total]</td>
									<td><a href='pos-quote-details.php?quoid=$quo[quoid]'>Details</a></td>";
				if ($quo['accepted'] == 'n') {
					$printQuo .= "
									<td><a href='pos-quote-new.php?quoid=$quo[quoid]&cont=true&done='>Edit</a></td>
									<td><a href='pos-quote-cancel.php?quoid=$quo[quoid]'>Cancel</a></td>
									<td><a href='pos-quote-accept.php?quoid=$quo[quoid]'>Accept</a></td>
									<td><a href='pos-quote-print.php?quoid=$quo[quoid]' target='_blank'>Print</a></td>
									<td><a href='pdf/pos-quote-pdf-print.php?quoid=$quo[quoid]' target='_blank'>Print in PDF</a></td>
									<td><input type='checkbox' name='evs[]' value='$quo[quoid]'></td>
								</tr>";
				} else {
					$printQuo .= "
									<td colspan='3'>Accepted</td>
									<td><a href='pos-quote-print.php?quoid=$quo[quoid]' target='_blank'>Print</a></td>
									<td><a href='pdf/pos-quote-pdf-print.php?quoid=$quo[quoid]' target='_blank'>Print in PDF</a></td>
									<td><input type='checkbox' name='evs[]' value='$quo[quoid]'></td>
								</tr>";
				}
				$i++;
			}
			$printQuo .= "
							<tr>
								<td colspan='14' align='right'><input type='submit' name='key' value='Send Emails'></td>
							</tr>
						";
		}

		// Layout
		$printQuo .= "
							</form>
							</table>
							<p>
							<table ".TMPL_tblDflts.">
								<tr><td><br></td></tr>
								<tr>
									<th>Quick Links</th>
								</tr>
								<tr class='datacell'>
									<td align='center'><a href='pos-quote-new.php'>New POS Quote</td>
								</tr>
								<tr class='datacell'>
									<td align='center'><a href='main.php'>Main Menu</td>
								</tr>
							</table>";
	return $printQuo;

}


?>