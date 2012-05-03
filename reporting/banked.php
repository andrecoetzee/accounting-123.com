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

# get settings
require("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
                case "viewcash":
			$OUTPUT = viewcash($_POST);
			break;
                default:
			$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# get template
require("../template.php");


# Default view
function view()
{
	$banksel = "<select name='bankid'>";
	db_connect();
	$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."'";
	$banks = db_exec($sql);

	if(pg_numrows($banks) < 1){
		return "<li class='err'> There are no bank accounts yet in Cubit.";
	}

	while($acc = pg_fetch_array($banks)){
		$banksel .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}

	$banksel .= "</select>";

	// main layout
	$view = "
			<h3>View Cash Book Analysis</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='viewcash'>
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Bank Account</td>
					<td valign=center>$banksel</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>From :</td>
					<td align='center'>".mkDateSelect("from")."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>To :</td>
					<td align='center'>".mkDateSelect("to")."</td>
				</tr>
				<tr>
					<td></td>
					<td align='right'><input type='submit' value='View &raquo'></td>
				</tr>
			</form>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='index-reports.php'>Financials</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='index-reports-banking.php'>Banking Reports</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='../main.php'>Main Menu</a></td>
				</tr>
			</table>";

	return $view;
}

# view cash book
function viewcash($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 20, "Invalid Bank Account Number.");
	$v->isOk ($from_day, "num", 1, 2, "Invalid Day for the 'From' date.");
	$v->isOk ($from_month, "num", 1, 2, "Invalid month for the 'From' date..");
	$v->isOk ($from_year, "num", 1, 4, "Invalid year for the 'From' date..");
	$v->isOk ($to_day, "num", 1, 2, "Invalid Day for the 'To' date.");
	$v->isOk ($to_month, "num", 1, 2, "Invalid month for the 'To' date..");
	$v->isOk ($to_year, "num", 1, 4, "Invalid year for the 'To' date..");

	# lets mix the date
	$from = sprintf("%02.2d",$from_day)."-".sprintf("%02.2d",$from_month)."-".$from_year;
	$to = sprintf("%02.2d",$to_day)."-".sprintf("%02.2d",$to_month)."-".$to_year;

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

	# Get account name for bank account
	db_connect();
	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid= '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	// Receipts
	$OUTPUT = "
			<center>
			<h3>Cash Book : $bank[accname]<br><br>$from to $to</h3>
			<table ".TMPL_tblDflts.">
				<tr>
					<td colspan='7'><h4>Analysis of Receipts</h4></td>
				</tr>
				<tr>
					<th>Date</th>
					<th>Bank Account Name</th>
					<th>Cheque Number</th>
					<th>Received From : </th>
					<th>Description</th>
					<th>Ledger Account</th>
					<th>Amount</th>
				</tr>";

	# date format
	$from = explode("-", $from);
	$from = $from[2]."-".$from[1]."-".$from[0];

	$to = explode("-", $to);
	$to = $to[2]."-".$to[1]."-".$to[0];

	$rtotal = 0; # Received total amount

	# Connect to database
	db_Connect ();

	$sql = "SELECT * FROM cashbook WHERE bankid = '$bankid' AND date >= '$from' AND date <= '$to' AND trantype='deposit' AND banked='yes' AND div = '".USER_DIV."' ORDER BY date DESC";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

	if ($numrows < 1) {
			$OUTPUT .= "
					<tr>
						<td colspan='7' align='center'><li class='err'>There are no Payments/cheques received on the selected period.</td>
					</tr>";
	}else{

			# display all bank Deposits
			for ($i=0; $i < $numrows; $i++) {
				$accnt = pg_fetch_array ($accntRslt, $i);

				if(strlen($accnt['accids']) > 0){
					$acc['accname'] = "Multiple Accounts";
					$acc['accnum'] = "";
					$acc['topacc'] = "";
				}else{
					# get account name for the account involved
					$AccRslt = get("core","accname,topacc,accnum","accounts", "accid", $accnt['accinv']);
					$acc = pg_fetch_array($AccRslt);
				}

				/*
				# get account name for account involved
				$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accnt['accinv']);
				$acc = pg_fetch_array($accRslt);
				*/

				# get account name for bank account
				db_connect();
				$sql = "SELECT accname FROM bankacct WHERE bankid= '$accnt[bankid]' AND div = '".USER_DIV."'";
				$bnameRslt = db_exec($sql);
				$bname = pg_fetch_array($bnameRslt);

				# format date
				$accnt['date'] = explode("-", $accnt['date']);
				$accnt['date'] = $accnt['date'][2]."-".$accnt['date'][1]."-".$accnt['date'][0];

				$rtotal += $accnt['amount']; // add to rtotal
				$accnt['amount'] = sprint ($accnt['amount']);

				$OUTPUT .= "
						<tr class='".bg_class()."'>
							<td>$accnt[date]</td>
							<td>$bname[accname]</td>
							<td align='center'>$accnt[cheqnum]</td>
							<td align='center'>$accnt[name]</td>
							<td>$accnt[descript]</td>
							<td>$acc[topacc]/$acc[accnum]  $acc[accname]</td>
							<td>".CUR." $accnt[amount]</td>
						</tr>";
			}

			# print the total
			$OUTPUT .= "
					<tr class='".bg_class()."'>
						<td colspan='6'><b>Total Receipts</b></td>
						<td><b>".CUR." ".sprintf("%01.2f",$rtotal)."</b></td>
					</tr>";
	}


	# Seperate the tables with two rows
	$OUTPUT .= "
			<tr>
				<td colspan='7'><br></td>
			</tr>
			<tr>
				<td colspan='7'><br></td>
			</tr>";

	# Payments
	$OUTPUT .= "
			<tr>
				<td colspan='7'><h4>Analysis of Payments</h4></td>
			</tr>
			<tr>
				<th>Date</th>
				<th>Bank Account Name</th>
				<th>Cheque Number</th>
				<th>Paid to: </th>
				<th>Description</th>
				<th>Ledger Account</th>
				<th>Amount</th>
			</tr>";

	$ptotal = 0; # payments total

	# Connect to database
	db_Connect ();

	$sql = "SELECT * FROM cashbook WHERE date >= '$from' AND date <= '$to' AND trantype='withdrawal' AND banked='yes' AND div = '".USER_DIV."' ORDER BY date DESC";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

	if ($numrows < 1) {
		$OUTPUT .= "
				<tr>
					<td colspan='7' align='center'><li class='err'>There are no Payments made on the selected period.</td>
				</tr>";
	}else{
			# Display all bank Deposits
			for ($i=0; $i < $numrows; $i++) {
				$accnt = pg_fetch_array ($accntRslt, $i);

				if(strlen($accnt['accids']) > 0){
					$acc['accname'] = "Multiple Accounts";
					$acc['accnum'] = "";
					$acc['topacc'] = "";
				}else{
					# get account name for the account involved
					$AccRslt = get("core","accname,topacc,accnum","accounts", "accid", $accnt['accinv']);
					$acc = pg_fetch_array($AccRslt);
				}

				/*
				# get account name for account involved
				$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accnt['accinv']);
				$acc = pg_fetch_array($accRslt);
				*/

				# get account name for bank account
				db_connect();
				$sql = "SELECT accname FROM bankacct WHERE bankid= '$accnt[bankid]' AND div = '".USER_DIV."'";
				$bnameRslt = db_exec($sql);
				$bname = pg_fetch_array($bnameRslt);

				# format date
				$accnt['date'] = explode("-", $accnt['date']);
				$accnt['date'] = $accnt['date'][2]."-".$accnt['date'][1]."-".$accnt['date'][0];

				$ptotal += $accnt['amount']; # add to total
				$accnt['amount'] = sprint ($accnt['amount']);

				$OUTPUT .= "
						<tr class='".bg_class()."'>
							<td>$accnt[date]</td>
							<td>$bname[accname]</td>
							<td align='center'>$accnt[cheqnum]</td>
							<td align='center'>$accnt[name]</td>
							<td>$accnt[descript]</td>
							<td>$acc[topacc]/$acc[accnum]  $acc[accname]</td>
							<td>".CUR." $accnt[amount]</td>
						</tr>";
			}
			# print the total
			$OUTPUT .= "
					<tr class='".bg_class()."'>
						<td colspan='6'><b>Total Payments</b></td>
						<td><b>".CUR." ".sprintf("%01.2f",$ptotal)."</b></td>
					</tr>";
	}

	$OUTPUT .= "
				<tr>
					<td colspan='7'><br></td>
				</tr>
				<tr>
					<td align='center' colspan='10'>
						<form action='../xls/banked-xls.php' method='POST' name='form'>
							<input type='hidden' name='key' value='viewcash'>
							<input type='hidden' name='bankid' value='$bankid'>
							<input type='hidden' name='fday' value='$from_day'>
							<input type='hidden' name='fmonth' value='$from_month'>
							<input type='hidden' name='fyear' value='$from_year'>
							<input type='hidden' name='lday' value='$to_day'>
							<input type='hidden' name='lmonth' value='$to_month'>
							<input type='hidden' name='lyear' value='$to_year'>
							<input type='submit' name='xls' value='Export to spreadsheet'>
						</form>
					</td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='index-reports.php'>Financials</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='index-reports-banking.php'>Banking Reports</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='../main.php'>Main Menu</a></td>
				</tr>
			</table>";
	return $OUTPUT;

}

?>