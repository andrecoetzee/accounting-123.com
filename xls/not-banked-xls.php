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


require ("../settings.php");          // Get global variables & functions

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
            case "out":
				$OUTPUT = cashbook($HTTP_POST_VARS['bankid']);
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
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=out>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Bank Account</td>
	<td valign=center><select name=bankid>";
	db_connect();
	$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."'";
	$banks = db_exec($sql);

	if(pg_numrows($banks) < 1){
		return "<li class=err> There are no bank accounts yet in Cubit.";
	}

	while($acc = pg_fetch_array($banks)){
		$view .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname]</option>";
	}

	$view .= "</select></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='View &raquo'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}

function cashbook($bankid)
{

	$OUTPUT = printdep($bankid);
	$OUTPUT .= "<br>";
	$OUTPUT .= printrep($bankid);

	# Send the stream
	include("temp.xls.php");
	Stream("Oustanding", $OUTPUT);
}

# print all not banked cheques
function printrep($bankid)
{
	// Set up table to display in
	$OUTPUT = "
	<tr><td><br></td></tr>
	<tr><th colspan=3><h3>Outstanding Receipts</h3></th></tr>
	<tr><th>Bank Name</th><th>Account Name</th><th>Date</th><th>Received from</th><th>Description</th><th>Transaction Type</th><th>Amount</th><th>Account</th></tr>";

	// Connect to database
	db_Connect ();
	$sql = "SELECT * FROM cashbook WHERE bankid = '$bankid' AND trantype = 'deposit' AND banked = 'no' AND div = '".USER_DIV."' ORDER BY date DESC";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank cheque transaction details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

	if ($numrows < 1) {
		$OUTPUT = "<tr><td></td></tr>
		<tr><th colspan=5><li class=err> There are no outstanding Bank Receipts entries.</li></th></tr>";
		return $OUTPUT;
	}

	# display all bank cheques
	$tot = 0;
	for ($i=0; $i < $numrows; $i++) {
		$accnt = pg_fetch_array ($accntRslt, $i);

		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

		# get account name for account involved
		$accRslt = get("core", "accname", "accounts", "accid", $accnt['accinv']);
		$acc = pg_fetch_array($accRslt);

		# get account name for bank account
		db_connect();
		$sql = "SELECT accname, bankname FROM bankacct WHERE bankid= '$accnt[bankid]' AND div = '".USER_DIV."'";
		$bankRslt = db_exec($sql);
		$bank = pg_fetch_array($bankRslt);

		$OUTPUT .= "<tr><td>$bank[bankname]</td><td align=center>$bank[accname]</td><td align=center>$accnt[date]</td><td align=center>$accnt[name]</td><td>$accnt[descript]</td><td align=center>$accnt[trantype]</td><td align=center>".CUR." $accnt[amount]<td align=center>$acc[accname]</td></td></tr>";
		$tot += $accnt['amount'];
	}
	$OUTPUT .= "<tr><td colspan=6><b>Total Outstanding</b></td><td colspan=3><b>".CUR." $tot</b></td></tr>
	</table>";

	return $OUTPUT;
}


# print all not banked deposits
function printdep($bankid)
{

	// Set up table to display in
	$OUTPUT = "<table>
	<tr><th colspan=3><h3>Outstanding Payments</h3></th></tr>
	<tr><th>Bank Name</th><th>Account Name</th><th>Date</th><th>Paid to</th><th>Description</th><th>Transaction Type</th><th>Amount</th><th>Account</th></tr>";

	// Connect to database
	db_Connect();
	$sql = "SELECT * FROM cashbook WHERE bankid = '$bankid' AND trantype = 'withdrawal' AND banked='no' AND div = '".USER_DIV."' ORDER BY date DESC";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

	if ($numrows < 1) {
		$OUTPUT = "<tr><td></td></tr>
		<tr><th colspan=5><li class=err> There are no outstanding Bank Payment entries.</li></th></tr>";
		return $OUTPUT;
	}

	# display all bank Deposits
	$tot = 0;
	for ($i=0; $i < $numrows; $i++) {
		$accnt = pg_fetch_array ($accntRslt, $i);

		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

		# get account name for account involved
		$accRslt = get("core", "accname", "accounts", "accid", $accnt['accinv']);
		$acc = pg_fetch_array($accRslt);

		# get account name for bank account
		db_connect();
		$sql = "SELECT accname,bankname FROM bankacct WHERE bankid= '$accnt[bankid]' AND div = '".USER_DIV."'";
		$bankRslt = db_exec($sql);
		$bank = pg_fetch_array($bankRslt);

		$OUTPUT .= "<tr><td>$bank[bankname]</td><td align=center>$bank[accname]</td><td align=center>$accnt[date]</td><td align=center>$accnt[name]</td><td align=center>$accnt[descript]</td><td align=center>$accnt[trantype]</td><td align=right>".CUR." $accnt[amount]</td><td align=center>$acc[accname]</td></tr>";

		$tot += $accnt['amount'];
	}
	$OUTPUT .= "<tr><td colspan=6><b>Total Outstanding</b></td><td colspan=3><b>".CUR." $tot</b></td></tr>";

	# return OUTPUT
	return $OUTPUT;
}
?>
