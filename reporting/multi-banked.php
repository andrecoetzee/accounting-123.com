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
	$banksel = "<select name=bankid>";
	db_connect();
	$sql = "SELECT * FROM bankacct";
	$banks = db_exec($sql);

	if(pg_numrows($banks) < 1){
		return "<li class=err> There are no bank accounts yet in Cubit.";
	}

	while($acc = pg_fetch_array($banks)){
		$branname = branname($acc['div']);
		$banksel .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname] - $branname</option>";
	}

	$banksel .= "</select>";

	// main layout
	$view = "<h3>View Cash Book Analysis</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=viewcash>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Bank Account</td><td valign=center>$banksel</td></tr>
	<tr class='bg-even'><td>From :</td><td align=center><input type=text size=2 name=fday maxlength=2 value='1'>-<input type=text size=2 name=fmonth maxlength=2  value='".date("m")."'>-<input type=text size=4 name=fyear maxlength=4 value='".date("Y")."'></td></tr>
	<tr class='bg-odd'><td>To :</td><td align=center><input type=text size=2 name=lday maxlength=2 value='".date("d")."'>-<input type=text size=2 name=lmonth maxlength=2 value='".date("m")."'>-<input type=text size=4 name=lyear maxlength=4 value='".date("Y")."'></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='View &raquo'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}

# view cash book
function viewcash($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 20, "Invalid Bank Account Number.");
	$v->isOk ($fday, "num", 1, 2, "Invalid Day for the 'From' date.");
	$v->isOk ($fmonth, "num", 1, 2, "Invalid month for the 'From' date..");
	$v->isOk ($fyear, "num", 1, 4, "Invalid year for the 'From' date..");
	$v->isOk ($lday, "num", 1, 2, "Invalid Day for the 'To' date.");
	$v->isOk ($lmonth, "num", 1, 2, "Invalid month for the 'To' date..");
	$v->isOk ($lyear, "num", 1, 4, "Invalid year for the 'To' date..");

	# lets mix the date
	$from = sprintf("%02.2d",$fday)."-".sprintf("%02.2d",$fmonth)."-".$fyear;
	$to = sprintf("%02.2d",$lday)."-".sprintf("%02.2d",$lmonth)."-".$lyear;

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get account name for bank account
	db_connect();
	$sql = "SELECT accname,bankname FROM bankacct WHERE bankid= '$bankid'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	// Receipts
	$OUTPUT = "<center><h3>Cash Book : $bank[accname]<br><br>$from to $to</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><td colspan=7><h4>Analysis of Receipts</h4></td></tr>
	<tr><th>Date</th><th>Bank Account Name</th><th>Cheque Number</th><th>Received From : </th><th>Description</th><th>Ledger Account</th><th>Amount</th></tr>";

	# date format
	$from = explode("-", $from);
	$from = $from[2]."-".$from[1]."-".$from[0];

	$to = explode("-", $to);
	$to = $to[2]."-".$to[1]."-".$to[0];

	$rtotal = 0; # Received total amount

	# Connect to database
	db_Connect ();

	$sql = "SELECT * FROM cashbook WHERE bankid = '$bankid' AND date >= '$from' AND date <= '$to' AND trantype='deposit' AND banked='yes' ORDER BY date DESC";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

	if ($numrows < 1) {
			$OUTPUT .= "<tr><td colspan=7 align=center><li class=err>There are no Payments/cheques received on the selected period.</td></tr>";
	}else{
		# display all bank Deposits
		for ($i=0; $i < $numrows; $i++) {
			$accnt = pg_fetch_array ($accntRslt, $i);

			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

			if(strlen($accnt['accids']) > 0){
				$acc['accname'] = "Multiple Accounts";
				$acc['accnum'] = "000";
				$acc['topacc'] = "000";
			}else{
				# get account name for the account involved
				$AccRslt = undget("core","accname","accounts", "accid", $accnt['accinv']);
				$acc = pg_fetch_array($AccRslt);
			}

			/*
			# get account name for account involved
			$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accnt['accinv']);
			$acc = pg_fetch_array($accRslt);
			*/

			# get account name for bank account
			db_connect();
			$sql = "SELECT accname FROM bankacct WHERE bankid= '$accnt[bankid]'";
			$bnameRslt = db_exec($sql);
			$bname = pg_fetch_array($bnameRslt);

			# format date
			$accnt['date'] = explode("-", $accnt['date']);
			$accnt['date'] = $accnt['date'][2]."-".$accnt['date'][1]."-".$accnt['date'][0];

			$rtotal += $accnt['amount']; // add to rtotal
			$OUTPUT .= "<tr bgcolor='$bgColor'><td>$accnt[date]</td><td>$bname[accname]</td><td align=center>$accnt[cheqnum]</td><td align=center>$accnt[name]</td><td>$accnt[descript]</td><td>$acc[topacc]/$acc[accnum]  $acc[accname]</td><td>".CUR." $accnt[amount]</td></tr>";
		}

		# print the total
		$OUTPUT .= "<tr class='bg-even''><td colspan=6><b>Total Receipts</b></td><td><b>".CUR." ".sprintf("%01.2f",$rtotal)."</b></td></tr>";
	}


	# Seperate the tables with two rows
	$OUTPUT .= "<tr><td colspan=7><br></td></tr><tr><td colspan=7><br></td></tr>";

	# Payments
	$OUTPUT .= "<tr><td colspan=7><h4>Analysis of Payments</h4></td></tr>
	<tr><th>Date</th><th>Bank Account Name</th><th>Cheque Number</th><th>Paid to: </th><th>Description</th><th>Ledger Account</th><th>Amount</th></tr>";

	$ptotal = 0; # payments total

	# Connect to database
	db_Connect ();
	$sql = "SELECT * FROM cashbook WHERE bankid = '$bankid' AND date >= '$from' AND date <= '$to' AND trantype='withdrawal' AND banked='yes' ORDER BY date DESC";

	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

	if ($numrows < 1) {
		$OUTPUT .= "<tr><td colspan=7 align=center><li class=err>There are no Payments made on the selected period.</td></tr>";
	}else{
			# Display all bank Deposits
			for ($i=0; $i < $numrows; $i++) {
			$accnt = pg_fetch_array ($accntRslt, $i);

			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

					if(strlen($accnt['accids']) > 0){
						$acc['accname'] = "Multiple Accounts";
						$acc['accnum'] = "000";
						$acc['topacc'] = "000";
					}else{
						# get account name for the account involved
						$AccRslt = undget("core","accname","accounts", "accid", $accnt['accinv']);
						$acc = pg_fetch_array($AccRslt);
					}

					/*
					# get account name for account involved
					$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accnt['accinv']);
					$acc = pg_fetch_array($accRslt);
					*/

					# get account name for bank account
					db_connect();
					$sql = "SELECT accname FROM bankacct WHERE bankid= '$accnt[bankid]'";
					$bnameRslt = db_exec($sql);
					$bname = pg_fetch_array($bnameRslt);

					# format date
					$accnt['date'] = explode("-", $accnt['date']);
					$accnt['date'] = $accnt['date'][2]."-".$accnt['date'][1]."-".$accnt['date'][0];

					$ptotal += $accnt['amount']; # add to total
					$OUTPUT .= "<tr bgcolor='$bgColor'><td>$accnt[date]</td><td>$bname[accname]</td><td align=center>$accnt[cheqnum]</td><td align=center>$accnt[name]</td><td>$accnt[descript]</td><td>$acc[topacc]/$acc[accnum]  $acc[accname]</td><td>".CUR." $accnt[amount]</td></tr>";
			}
			# print the total
			$OUTPUT .= "<tr class='bg-even''><td colspan=6><b>Total Payments</b></td><td><b>".CUR." ".sprintf("%01.2f",$ptotal)."</b></td></tr>";
	}

	$OUTPUT .= "<tr><td colspan=7><br></td></tr>

	<!--
	<tr><td align=center colspan=10>
		<form action='../xls/banked-xls.php' method=post name=form>
		<input type=hidden name=key value=viewcash>
		<input type=hidden name=bankid value='$bankid'>
		<input type=hidden name=fday value='$fday'>
		<input type=hidden name=fmonth value='$fmonth'>
		<input type=hidden name=fyear value='$fyear'>
		<input type=hidden name=lday value='$lday'>
		<input type=hidden name=lmonth value='$lmonth'>
		<input type=hidden name=lyear value='$lyear'>
		<input type=submit name=xls value='Export to spreadsheet'>
		</form>
	</td></tr>
	-->

	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $OUTPUT;
}
?>
