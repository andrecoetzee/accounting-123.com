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

	# from period
	$fprd = "<select name=fprd>";
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
		$fprd .="<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
	}
	$fprd .= "</select>";

	# from period
	$tprd = "<select name=tprd>";
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
		$tprd .="<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
	}
	$tprd .= "</select>";

	// Layout
	$slctAcc = "<h3>Select Options</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=viewtran>
	<tr><th colspan=2>Period Range</th></tr>
	<tr class='bg-odd'><td>From : $fprd</td><td>To : $tprd</td></tr>
	<tr><td><br></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td><input type=submit value='View All'></td></tr>
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
	$v->isOk ($fprd, "string", 1, 14, "Invalid Starting Period number.");
	$v->isOk ($tprd, "string", 1, 14, "Invalid Ending Period number.");

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

	# dates drop downs
	$months = array("1"=>"January","2"=>"February", "3"=>"March", "4"=>"April", "5"=>"May", "6"=>"June", "7"=>"July", "8"=>"August", "9"=>"September", "10"=>"October", "11"=>"November", "12"=>"December");

//	if($tprd < $fprd){
//		$OUTPUT = "<li class=err> Invalid Period range : $months[$fprd] to $months[$tprd]";
//		require("../template.php");
//	}

	// Set up table to display in
	$OUTPUT = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th colspan=7><h3>Journal Entries : $months[$fprd] - $months[$tprd]</h3></th></tr>
	<tr><th colspan=7></th></tr>
	<tr><th>Date</th><th>Debit</th><th>Credit</th><th>Ref No</th><th>Amount</th><th>Details</th><th>User</th></tr>";

	

	$prds = array();
	if ($tprd < $fprd) {
		for($i=$fprd; $i <= 12; $i++){
			$prds[] = $i;
		}
		for($i= 1; $i <= $tprd; $i++){
			$prds[] = $i;
		}
	} else {
		for($i= $fprd; $i <= $tprd; $i++){
			$prds[] = $i;
		}
	}

	# counts
	$credtot = 0;
	$debtot = 0;
	# Get Transactions
//	for($i= $fprd; $i <= $tprd; $i++){
	foreach ($prds as $i) {
		db_conn($i);
		$sql = "SELECT * FROM transect WHERE div = '".USER_DIV."'";
		$tranRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
		if (pg_numrows ($tranRslt) < 1) {
			continue;
		}else{
			# display all transactions
			while ($tran = pg_fetch_array ($tranRslt)){
				#get vars from tran as the are in db
                foreach ($tran as $key => $value) {
		        	$$key = $value;
	        	}

				# format date
                $date = explode("-", $date);
                $date = $date[2]."-".$date[1]."-".$date[0];

				// get account names
                $deb = get("core","accname, topacc, accnum","accounts","accid",$debit);
                $debacc = pg_fetch_array($deb);
                $ct = get("core","accname, topacc,accnum","accounts","accid",$credit);
                $ctacc = pg_fetch_array($ct);

                $OUTPUT .= "<tr><td>$date</td><td>$debit - $debacc[topacc]/$debacc[accnum] - $debacc[accname]</td><td>$credit - $ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td><td>$refnum</td><td>".CUR." $amount</td><td>$details</td><td>$author</td></tr>";
			}
		}
	}

	$OUTPUT .= "</table>";

	# Send the stream
	include("temp.xls.php");
	Stream("AllTransactionsPrd", $OUTPUT);

}
?>
