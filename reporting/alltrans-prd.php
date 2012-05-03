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

# Get settings
require("../settings.php");
require("../core-settings.php");

# decide what to do
if(isset($_GET['accid'])){
	$_GET['prd'] = PRD_DB;
	$_GET['details'] = "";
	$OUTPUT = viewtran($_GET);
}elseif (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "viewtran":
			$OUTPUT = viewtran($_POST);
			break;
		default:
			$OUTPUT = slctAcc($_POST);
	}
} else {
	$OUTPUT = slctAcc($_POST);
}

# Get templete
require("../template.php");


# Select Category
function slctAcc()
{

	global $PRDMON;
	$fprd = finMonList("fprd", $PRDMON[1]);
	$tprd = finMonList("tprd", PRD_DB);

	// Layout
	$slctAcc = "
		<h3>Select Options</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='viewtran'>
			<tr>
				<th colspan='2'>Period Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>From : $fprd</td>
				<td>To : $tprd</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' value='View All &raquo;'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".bgcolorg()." width='100'>
			<tr><th>Quick Links</th></tr>
			<tr class='datacell'>
				<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $slctAcc;

}



# View per account number and cat
function viewtran($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fprd, "string", 1, 14, "Invalid Starting Period number.");
	$v->isOk ($tprd, "string", 1, 14, "Invalid Ending Period number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# dates drop downs
	$months = array(
		"1" => "January", 
		"2" => "February", 
		"3" => "March", 
		"4" => "April", 
		"5" => "May", 
		"6" => "June", 
		"7" => "July", 
		"8" => "August", 
		"9" => "September", 
		"10" => "October", 
		"11" => "November", 
		"12" => "December"
	);

	// Set up table to display in
	$OUTPUT = "
		<center>
		<h3>Journal Entries : $months[$fprd] - $months[$tprd]</h3>
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
			<th>Date</th>
			<th>Debit</th>
			<th>Credit</th>
			<th>Ref No</th>
			<th>Amount</th>
			<th>Details</th>
			<th>User</th>
		</tr>";

	# counts
	$credtot = 0;
	$debtot = 0;
	$prds = array();
	if ($tprd < $fprd) {
		for($i = $fprd; $i <= 12; $i++){
			$prds[] = $i;
		}
		for($i = 1; $i <= $tprd; $i++){
			$prds[] = $i;
		}
	} else {
		for($i = $fprd; $i <= $tprd; $i++){
			$prds[] = $i;
		}
	}

	# Get Transactions
	foreach ($prds as $i) {

		db_conn($i);
		$sql = "SELECT * FROM transect WHERE div = '".USER_DIV."'";
		$tranRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
		if (pg_numrows ($tranRslt) < 1) {
			continue;
		}else{
			# display all transactions
			while ($tran = pg_fetch_array ($tranRslt)){

				# Get vars from tran as the are in db
				foreach ($tran as $key => $value) {
					$$key = $value;
				}

				# format date
				$date = explode("-", $date);
				$date = $date[2]."-".$date[1]."-".$date[0];

				/*
					get account names
					$deb = get("core","accname, topacc, accnum","accounts","accid",$debit);
					$debacc = pg_fetch_array($deb);
					$ct = get("core","accname, topacc,accnum","accounts","accid",$credit);
					$ctacc = pg_fetch_array($ct);

					$debit -
					$credit -
				*/

				$amount = sprint($amount);

				$OUTPUT .= "
					<tr class='".bg_class()."'>
						<td>$date</td>
						<td>$dtopacc/$daccnum - $daccname</td>
						<td>$ctopacc/$caccnum - $caccname</td>
						<td align='right'>$custom_refnum</td>
						<td align='right'>".CUR." $amount</td>
						<td>$details</td>
						<td>$author</td>
					</tr>";
			}
		}
	}

	$OUTPUT .= "
			<tr>
				<td align='center' colspan='10'>
					<form action='../xls/alltrans-prd-xls.php' method='POST' name='form'>
						<input type='hidden' name='key' value='viewtran'>
						<input type='hidden' name='fprd' value='$fprd'>
						<input type='hidden' name='tprd' value='$tprd'>
						<input type='submit' name='xls' value='Export to spreadsheet'>
					</form>
				</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='25%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	return $OUTPUT;

}

?>