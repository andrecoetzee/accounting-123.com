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

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "viewtran":
			$OUTPUT = viewtran($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slctacc();
	}
} else {
	$OUTPUT = slctacc();
}

# Get templete
require("../template.php");

function slctacc()
{
	/*
	# from period
	$prds = "<select name=prd>";
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
		$prds .="<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
	}
	$prds .= "</select>";
	*/

	db_connect();
	$sql = "SELECT * FROM assets WHERE div = '".USER_DIV."' ORDER BY supid ASC";
	$ledRslt = db_exec($sql) or errDie("Could not retrieve assets Information from the Database.",SELF);

	if(pg_numrows($ledRslt) < 1){
		return "<li class=err> There are no assets in Cubit.";
	}
	$assets = "<select name=ledids[] multiple size=10>";
	while($led = pg_fetch_array($ledRslt)){
		$assets .= "<option value='$led[supid]'>$led[supname]</option>";
	}
	$assets .= "</select>";

	$slctacc = "
	<p>
	<h3>Assets Ledger</h3>
	<h4>Select Options</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=viewtran>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>Assets</td><td><input type=radio name=accnt value=slct checked=yes>Selected Assets | <input type=radio name=accnt value=all>All Assets</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Select Asset(s)</td><td>$assets</td></tr>
	<tr><td><br></td></tr>
	<tr><td align=center><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=center><input type=submit value='Continue &raquo;'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $slctacc;
}

# View all transaction for the ledger
function viewtran($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	// $v->isOk ($prd, "string", 1, 14, "Invalid Period number.");
	$v->isOk ($accnt, "string", 1, 5, "Invalid Assets Selection.");

	if($accnt == 'slct'){
		if(isset($ledids)){
			foreach($ledids as $key => $ledid){
				$v->isOk ($ledid, "num", 1, 20, "Invalid Asset number.");
			}
		}else{
			$v->isOk ("###", "num", 0, 0, "ERROR : Please select at least one Asset.");
		}
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

	# Get the ids
	if($accnt == 'all'){
		$ledids = array();
		db_connect();
		$sql = "SELECT id FROM assets WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);
		if(pg_num_rows($rs) > 0){
			while($ac = pg_fetch_array($rs)){
				$ledids[] = $ac['id'];
			}
		}else{
			return "<li calss=err> There are no assets yet in Cubit.";
		}
	}

	# Period name
	// $prdname = prdname($prd);

	$trans = "";
	$prd = "cubit";
	foreach($ledids as $key => $ledid){
		$ledRs = get("cubit", "*", "assets", "id", $ledid);
		$led = pg_fetch_array($ledRs);

		$netval = sprint($led['amount'] - $bal['accdep']);

		$trans .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=8><b>$led[des]</b></td></tr>";
		$trans .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><br></td><td>CST/AMT</td><td>Cost Amount</td><td align=right>$led[amount]</td><td align=right>$netval</td><td><br></td></tr>";

		# --> Transaction reading comes here <--- #
		$tranRs = get($prd, "*", "assetledger", "id", $ledid);
		while($tran = pg_fetch_array($tranRs)){
			# Format date
			$tran['date'] = explode("-", $tran['date']);
			$tran['date'] = $tran['date'][2]."-".$tran['date'][1]."-".$tran['date'][0];

			$trans .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><br></td><td>$tran[date]</td><td>DEP</td><td>Accumulated Depriciation</td><td align=right>$tran[accdep]</td><td align=right>$tran[netval]</td><td><br></td></tr>";
		}

		// $trans .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><br></td><td>A/C Total</td><td>Total for period $prdname to Date :</td><td align=right>$dbal[debit]</td><td align=right></td><td> </td></tr>";
		$trans .= "<tr><td colspan=8><br></td></tr>";
	}

	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$view = "
	<center>
	<h3>Assets Ledger</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=75%>
	<tr><td>$sp</td><th>Date</th><th>Reference</th><th>Description</th><th>Amount</th><th>Net Value</th></tr>
	$trans
	<table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}
?>
