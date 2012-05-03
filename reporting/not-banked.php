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

require ("../settings.php");	// Get global variables & functions

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "out":
			$OUTPUT = cashbook($_POST['bankid']);
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
	// main layout
	$view = "
	<h3>Outstanding Bank Account Entries</h3>
	<table ".TMPL_tblDflts." width='350'>
	<form action='".SELF."' method='POST' name='form'>
		<input type='hidden' name='key' value='out'>
		<tr>
			<th>Field</th>
			<th>Value</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Bank Account</td>
			<td valign='center'>
				<select name=bankid>";
	db_connect();
	$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."' ORDER BY bankname,branchname";
	$banks = db_exec($sql);

	if(pg_numrows($banks) < 1){
			return "<li class=err> There are no bank accounts yet in Cubit.";
	}

	while($acc = pg_fetch_array($banks)){
			$view .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}

	$view .= "
				</select>
			</td>
		</tr>
		<tr>
			<td></td>
			<td align='right'><input type='submit' value='View &raquo'></td>
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

	return $view;
}

function cashbook($bankid)
{

	$OUTPUT = printdep($bankid);
	$OUTPUT .= "<br>";
	$OUTPUT .= printrep($bankid);

	$OUTPUT .= "
		<p>
		<form action='../xls/not-banked-xls.php' method='POST' name='form'>
			<input type='hidden' name='key' value='out'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='submit' name='xls' value='Export to spreadsheet'>
		</form>
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

# print all not banked cheques
function printrep($bankid)
{
	// Set up table to display in

        $OUTPUT = "
			<h3>Outstanding Receipts</h3>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<form action='../bank/bank-bankall.php' method='POST'>
			<tr>
				<th>Bank Name</th>
				<th>Account Name</th>
				<th>Date</th>
				<th>Received from</th>
				<th>Description</th>
				<th>Transaction Type</th>
				<th>Amount</th>
				<th>Account</th>
			</tr>";

		// Connect to database
		db_Connect ();
        $sql = "SELECT * FROM cashbook WHERE bankid = '$bankid' AND trantype = 'deposit' AND banked = 'no' AND div = '".USER_DIV."' ORDER BY date DESC";
        $accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank cheque transaction details from database.", SELF);
		$numrows = pg_numrows ($accntRslt);

        if ($numrows < 1) {
			$OUTPUT = "<li class='err'> There are no outstanding Bank Receipts entries.</li>";
			return $OUTPUT;
		}

		# display all bank cheques
		$tot = 0;
		for ($i=0; $i < $numrows; $i++) {
			$accnt = pg_fetch_array ($accntRslt, $i);

			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

			if(strlen($accnt['accids']) > 0){
				$acc['accname'] = "Multiple Accounts";
				$acc['accnum'] = "";
				$acc['topacc'] = "";
			}else{
				# get account name for the account involved
				$AccRslt = get("core","accname","accounts", "accid", $accnt['accinv']);
				$acc = pg_fetch_array($AccRslt);
			}

			/*
			# get account name for account involved
			$accRslt = get("core", "accname", "accounts", "accid", $accnt['accinv']);
			$acc = pg_fetch_array($accRslt);
			*/

			# get account name for bank account
			db_connect();
			$sql = "SELECT accname, bankname FROM bankacct WHERE bankid= '$accnt[bankid]' AND div = '".USER_DIV."'";
			$bankRslt = db_exec($sql);
			$bank = pg_fetch_array($bankRslt);

			$accnt['amount'] = sprint ($accnt['amount']);

			# $OUTPUT .= "<tr bgcolor='$bgColor'><td>$accnt[bankname]</td><td align=center>$bname[accname]</td><td align=center>$accnt[date]</td><td>$accnt[descript]</td><td align=center>$accnt[ref]</td><td align=center>$accnt[trantype]</td><td align=center>".CUR." $accnt[amount]<td align=center>$acc[accname]</td></td>";
			$OUTPUT .= "
					<tr class='".bg_class()."'>
						<td>$bank[bankname]</td>
						<td align='center'>$bank[accname]</td>
						<td align='center'>$accnt[date]</td>
						<td align='center'>$accnt[name]</td>
						<td>$accnt[descript]</td>
						<td align='center'>$accnt[trantype]</td>
						<td align='center'>".CUR." $accnt[amount]</td>
						<td align='center'>$acc[accname]</td>";
			if($accnt['banked'] == "no" && $accnt['opt'] != 'n'){
				$OUTPUT .= "
						<td><a href='../bank/cheq-return.php?cashid=$accnt[cashid]'>Returned/Unpaid</td>";
				// $OUTPUT .= "<td><a href='../bank/cheq-cancel.php?cashid=$accnt[cashid]'>Cancel</td>";
			}
			$OUTPUT .= "</tr>";

			$tot += $accnt['amount'];
		}

		$tot = sprint ($tot);

		$OUTPUT .= "
				<tr class='".bg_class()."'>
					<td colspan='6'><b>Total Outstanding</b></td>
					<td colspan='3'><b>".CUR." $tot</b></td>
				</tr>
			</form>
			</table>";

        return $OUTPUT;
}


# print all not banked deposits
function printdep($bankid)
{

        // Set up table to display in

        $OUTPUT = "
			<h3>Outstanding Payments</h3>
			<table ".TMPL_tblDflts.">
			<form action='../bank/bank-bankall.php' method='POST'>
				<tr>
					<th>Bank Name</th>
					<th>Account Name</th>
					<th>Date</th>
					<th>Paid to</th>
					<th>Description</th>
					<th>Transaction Type</th>
					<th>Amount</th>
					<th>Account</th>
				</tr>";

		// Connect to database
		db_Connect();
        $sql = "SELECT * FROM cashbook WHERE bankid = '$bankid' AND trantype = 'withdrawal' AND banked='no' AND div = '".USER_DIV."' ORDER BY date DESC";
        $accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);
		$numrows = pg_numrows ($accntRslt);

        if ($numrows < 1) {
                $OUTPUT = "<li class='err'> There are no outstanding Bank Payment entries.</li>";
                return $OUTPUT;
		}

		# display all bank Deposits
		$tot = 0;
		for ($i=0; $i < $numrows; $i++) {
			$accnt = pg_fetch_array ($accntRslt, $i);

			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

			if(strlen($accnt['accids']) > 0){
				$acc['accname'] = "Multiple Accounts";
				$acc['accnum'] = "";
				$acc['topacc'] = "";
			}else{
				# get account name for the account involved
				$AccRslt = get("core","accname","accounts", "accid", $accnt['accinv']);
				$acc = pg_fetch_array($AccRslt);
			}

			/*
			# get account name for account involved
			$accRslt = get("core", "accname", "accounts", "accid", $accnt['accinv']);
			$acc = pg_fetch_array($accRslt);
			*/

			# get account name for bank account
			db_connect();
			$sql = "SELECT accname,bankname FROM bankacct WHERE bankid= '$accnt[bankid]' AND div = '".USER_DIV."'";
			$bankRslt = db_exec($sql);
			$bank = pg_fetch_array($bankRslt);

			$accnt['amount'] = sprint ($accnt['amount']);

			$OUTPUT .= "
					<tr class='".bg_class()."'>
						<td>$bank[bankname]</td>
						<td align='center'>$bank[accname]</td>
						<td align='center'>$accnt[date]</td>
						<td align='center'>$accnt[name]</td>
						<td align='center'>$accnt[descript]</td>
						<td align='center'>$accnt[trantype]</td>
						<td align='right'>".CUR." $accnt[amount]</td>
						<td align='center'>$acc[accname]</td>";
			if($accnt['banked'] == "no" && $accnt['opt'] != 'n'){
				$OUTPUT .= "
						<td><a href='../bank/cheq-return.php?cashid=$accnt[cashid]'>Returned/Unpaid</td>";
				// $OUTPUT .= "<td><a href='../bank/cheq-cancel.php?cashid=$accnt[cashid]'>Cancel</td>";
			}
			$OUTPUT .= "</tr>";

			$tot += $accnt['amount'];
		}

		$tot = sprint ($tot);

		$OUTPUT .= "
				<tr class='".bg_class()."'>
					<td colspan='6'><b>Total Outstanding</b></td>
					<td colspan='3'><b>".CUR." $tot</b></td>
				</tr>
			</form>
			</table>";

	// return OUTPUT
	return $OUTPUT;
}


?>