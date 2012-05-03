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

# get templete
require("../template.php");


# Select Category
function slctAcc()
{

	// Layout
	$slctAcc = "<h3>Select Account</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=viewtran>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-even'><td>Account Name</td><td><select name=accid>";

	core_connect();
	$sql = "SELECT * FROM accounts WHERE accnum = '000' ORDER BY accname ASC";
	$accRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);

	if(pg_numrows($accRslt) < 1){
		return "<li class=err> There are no Accounts in Cubit.";
	}

	while($acc = pg_fetch_array($accRslt)){
		$slctAcc .= "<option value='$acc[accid]'>$acc[accname]</option>";
	}

	$slctAcc .="</select></td><td><input type=submit name=details value='Enter Details'></td></tr>
	<tr class='bg-odd'><td colspan=3><br></td></tr>
	<tr class='bg-even'><td>Account Number</td><td><input type=text name=topacc size=3 maxlength=3> / <input type=text name=accnum size=3 maxlength=3></td><td><input type=submit value='Enter Details'></td></tr>
	<tr class='bg-odd'><td>Select Period</td><td valign=center colspan=3>
	<select name=prd>";

	db_conn(YR_DB);
	$sql = "SELECT * FROM info WHERE prdname !=''";
	$prdRslt = db_exec($sql);
	if(pg_numrows($prdRslt) < 1){
		return "<li class=err>ERROR : There are no periods set for the current year";
	}
	while($prd = pg_fetch_array($prdRslt)){
		if($prd['prddb'] == PRD_DB){
			$sel = "selected";
		}else{
			$sel= "";
		}
		$slctAcc .="<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
	}

	$slctAcc .= "
	</select></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='View Transactions &raquo'></td></tr>
	</form>
	</table>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $slctAcc;
}

# View per account number and cat
function viewtran($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd, "string", 1, 14, "Invalid Period number.");
	if(isset($details)){
		$v->isOk ($accid, "string", 1, 20, "Invalid Account number.");
	}else{
		$v->isOk ($topacc, "num", 1, 20, "Invalid Account number.");
		$v->isOk ($accnum, "num", 0, 20, "Invalid Account number.");
	}

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

	if (isset($details)) {
		$acc = qryAccounts($accid);
	} else {
		if(strlen($accnum) < 2){
			$acc = qryAccountsNum($topacc, "000");
			if ($acc === false) {
				return "<li> Accounts number $topacc/000 does not exist";
			}
		} else {
			$acc = qryAccountsNum($topacc, $accnum);
			if ($acc === false) {
				return "<li> Accounts number $topacc/$accnum does not exist";
			}
			$acc  = pg_fetch_array($accRs);
		}
	}

	db_conn($prd);

	// Set up table to display in
	$OUTPUT = "<center>
	<h3>Journal Entries for Account : $acc[topacc]/$acc[accnum] - $acc[accname]</h3>
	<table ".TMPL_tblDflts.">
	<tr>
		<th>Date</th>
		<th>Reference</th>
		<th>Contra Acc</th>
		<th>Description</th>
		<th>Debit</th>
		<th>Credit</th>
		<th>User</th>
	</tr>";

	$sql = "SELECT * FROM transect WHERE debit = '$acc[accid]' OR credit = '$acc[accid]'";
	$tranRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
	if (pg_numrows ($tranRslt) < 1) {
		$OUTPUT .= "<tr class='bg-odd'><td colspan=10>No Transactions found</td></tr>";
	} else {
		$credtot = 0;
		$debtot = 0;

		# display all transactions
		while ($tran = pg_fetch_array ($tranRslt)){
			extract($tran);

			if($debit == $accid){
				$cacc = $credit;
				$debitamt = CUR ." $amount";
				$debtot += $amount;
				$creditamt = "";
			} else {
				$debitamt = "";
				$creditamt = CUR ." $amount";
				$credtot += $amount;
				$cacc = $debit;
			}

			# get contra account name
			$caccRs = get("core","accname,topacc,accnum","accounts","accid",$cacc);
			$cacc = pg_fetch_array($caccRs);

			$OUTPUT .= "
			<tr class='".bg_class()."'>
				<td>$date</td>
				<td>$refnum</td>
				<td>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td>
				<td>$details</td>
				<td>$debitamt</td>
				<td>$creditamt</td>
				<td>$author</td>
			</tr>";
		}
	}

	if($acc["acctype"] == "I"){
		$accbal = ($credtot - $debtot);
	} else if ($acc["acctype"] == "E") {
		$accbal = ($debtot - $credtot);
	} else if ($acc["acctype"] == "B"){
		$accbal = ($debtot - $credtot);
	} else {
		return "<li class=err>Account number is beyond limits.";
	}

	$OUTPUT .= "
	<tr class='".bg_class()."'>
		<td colspan='4'><b>Total</b></td>
		<td><b>".CUR." $debtot</b></td>
		<td><b>".CUR." $credtot</b></td>
		<td>&nbsp;</td>
	</tr>
	<tr class='".bg_class()."'>
		<td colspan='4'><b>Balance</b></td>
		<td colspan='2'><b>".CUR." $accbal</b></td>
		<td>&nbsp;</td>
	</tr>
	</table>"
	.mkQuickLinks();

	return $OUTPUT;
}
?>
