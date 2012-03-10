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
if(isset($HTTP_GET_VARS['accid'])){
	$HTTP_GET_VARS['prd'] = PRD_DB;
	$HTTP_GET_VARS['details'] = "";
	$OUTPUT = viewtran($HTTP_GET_VARS);
}elseif (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "viewtran":
			$OUTPUT = viewtran($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slctAcc($HTTP_POST_VARS);
	}
} else {
	$OUTPUT = slctAcc($HTTP_POST_VARS);
}

# Get templete
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
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Name</td><td><select name=accid>";

	core_connect();
 	$sql = "SELECT * FROM accounts ORDER BY accname ASC";
	$accRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);

	if(pg_numrows($accRslt) < 1){
		return "<li class=err> There are no Accounts in Cubit.";
	}

	while($acc = pg_fetch_array($accRslt)){
		$branname = branname($acc['div']);
		$slctAcc .= "<option value='$acc[accid]'>$acc[accname] - $branname</option>";
	}

	$slctAcc .="</select></td><td><input type=submit name=details value='View Transactions'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=3><br></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Number</td><td><input type=text name=topacc size=3 maxlength=3> / <input type=text name=accnum size=3 maxlength=3></td><td><input type=submit value='View Transactions'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Period</td><td valign=center colspan=3>
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
	<tr><td align=center><input type=button value='&laquo Back' onClick='javascript:history.back()'></td></tr>
	</form>
	</table>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $slctAcc;
}

# View per account number and cat
function viewtran($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd, "string", 1, 14, "Invalid Period number.");
	if(isset($details)){
		$v->isOk ($accid, "string", 1, 20, "Invalid Account number.");
		$hide = "
		<input type=hidden name=prd value='$prd'>
		<input type=hidden name=details value='$details'>
		<input type=hidden name=accid value='$accid'>";
	}else{
		$v->isOk ($topacc, "num", 1, 20, "Invalid Account number.");
		$v->isOk ($accnum, "num", 0, 20, "Invalid Account number.");
		$hide = "
		<input type=hidden name=prd value='$prd'>
		<input type=hidden name=topacc value='$topacc'>
		<input type=hidden name=accnum value='$accnum'>";
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

	if(isset($details)){
		$accRs = undget("core", "accname, accid, topacc, accnum", "accounts", "accid", $accid);
		$acc = pg_fetch_array($accRs);
	}else{
		if(strlen($accnum) < 2){
			// account numbers
			$accRs = undget("core","accname, accid, topacc, accnum","accounts","topacc","$topacc' AND accnum = '000");
			if(pg_numrows($accRs) < 1){
				return "<li> Accounts number : $accnum does not exist";
			}
			$acc  = pg_fetch_array($accRs);
			}else{
			// account numbers
			$accRs = undget("core","accname, accid, topacc, accnum","accounts","topacc","$topacc' AND accnum = '$accnum");
			if(pg_numrows($accRs) < 1){
				return "<li> Accounts number : $topacc/$accnum does not exist";
			}
			$acc  = pg_fetch_array($accRs);
		}
	}

	db_conn($prd);

	// Set up table to display in
	$OUTPUT = "<center>
	<h3>Journal Entries for Account : $acc[topacc]/$acc[accnum] - $acc[accname]</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='80%'>
	<tr><th>Date</th><th>Reference</th><th>Contra Acc</th><th>Description</th><th>Debit</th><th>Credit</th><th>User</th></tr>";

	# Get Transactions
	$sql = "SELECT * FROM transect WHERE debit = '$acc[accid]' OR credit = '$acc[accid]'";
	$tranRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
	if (pg_numrows ($tranRslt) < 1) {
		$OUTPUT .= "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=10>No Transactions found</td></tr>";
		# counts
		$credtot = 0;
		$debtot = 0;
	}else{
		# counts
		$credtot = 0;
		$debtot = 0;

		# display all transactions
		while ($tran = pg_fetch_array ($tranRslt)){
			#get vars from tran as the are in db
			foreach ($tran as $key => $value) {
				$$key = $value;
			}

			if($debit == $acc['accid']){
				$cacc = $credit;
				$debitamt = "R ".$amount;
				$debtot += $amount;
				$creditamt = "";
			}else{
				$debitamt = "";
				$creditamt = "R ".$amount;
				$credtot += $amount;
				$cacc = $debit;
			}

			# format date
	        $date = explode("-", $date);
            $date = $date[2]."-".$date[1]."-".$date[0];

			# get contra account name
			$caccRs = undget("core","div, accname,topacc,accnum","accounts","accid",$cacc);
			$cacc = pg_fetch_array($caccRs);
			$branname = branname($cacc['div']);
			$OUTPUT .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$date</td><td>$refnum</td><td>$cacc[topacc]/$cacc[accnum] - $cacc[accname] - $branname</td><td>$details</td><td>$debitamt</td><td>$creditamt</td><td>$author</td></tr>";
		}
	}

	$OUTPUT .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=4><b>Total</b></td><td><b>".CUR." $debtot</b></td><td><b>".CUR." $credtot</b></td><td></td></tr>
	<tr><td><br></td></tr>

	<!--
	<tr><td align=center colspan=10>
			<form action='../xls/acc-trans-xls.php' method=post name=form>
			<input type=hidden name=key value=viewtran>
			$hide
			<input type=submit name=xls value='Export to spreadsheet'>
			</form>
	</td></tr>
	-->

	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $OUTPUT;
}
?>
