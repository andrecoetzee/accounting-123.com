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
	<tr bgcolor='".TMPL_tblDataColor1."'><td>From : $fprd</td><td>To : $tprd</td></tr>
	<tr><td><br></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td><input type=submit value='View All'></td></tr>
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

	if($tprd < $fprd){
		return "<li class=err> Invalid Period range : $months[$fprd] to $months[$tprd]";
	}

	// Set up table to display in
	$OUTPUT = "<center>
	<h3>Journal Entries : $months[$fprd] - $months[$tprd]</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
	<tr><th>Date</th><th>Debit</th><th>Credit</th><th>Ref No</th><th>Amount</th><th>Details</th><th>User</th></tr>";

	# counts
	$credtot = 0;
	$debtot = 0;
	# Get Transactions
	for($i= $fprd; $i <= $tprd; $i++){
		db_conn($i);
		$sql = "SELECT * FROM transect";
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
                $deb = undget("core", "div, accname, topacc, accnum","accounts","accid",$debit);
                $debacc = pg_fetch_array($deb);
                $ct = undget("core", "div, accname, topacc,accnum","accounts","accid",$credit);
                $ctacc = pg_fetch_array($ct);
				$dtbranname = branname($debacc['div']);
				$ctbranname = branname($ctacc['div']);
                $OUTPUT .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$date</td><td>$debacc[topacc]/$debacc[accnum] - $debacc[accname] - $dtbranname</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname] - $ctbranname</td><td>$refnum</td><td>".CUR." $amount</td><td>$details</td><td>$author</td></tr>";
			}
		}
	}

	$OUTPUT .= "
	<tr><td><br></td></tr>

	<!--
	<tr><td align=center colspan=10>
		<form action='../xls/alltrans-prd-xls.php' method=post name=form>
		<input type=hidden name=key value=viewtran>
		<input type=hidden name=fprd value='$fprd'>
		<input type=hidden name=tprd value='$tprd'>
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
