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
#
##

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

	db_connect();
	$sql = "SELECT * FROM stock WHERE div = '".USER_DIV."' ORDER BY stkdes ASC";
	$stkRslt = db_exec($sql) or errDie("Could not retrieve Stock Information from the Database.",SELF);

	if(pg_numrows($stkRslt) < 1){
		return "<li class=err> There are no Stock Items in Cubit.";
	}
	$stks = "<select name=stkids[] multiple size=10>";
	while($stk = pg_fetch_array($stkRslt)){
		$stks .= "<option value='$stk[stkid]'>$stk[stkcod] $stk[stkdes]</option>";
	}
	$stks .= "</select>";

	$slctacc = "
	<p>
	<h3>Inventory Ledger</h3>
	<h4>Select Options</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=viewtran>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>Stock Items</td><td><input type=radio name=accnt value=slct checked=yes>Selected Items | <input type=radio name=accnt value=all>All Items</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Select Stock Item(s)</td><td>$stks</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Select period</td><td>$prds</td></tr>
	<tr><td><br></td></tr>
	<tr><td align=center></td><td align=right><input type=submit value='Continue &raquo;'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
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
	$v->isOk ($prd, "string", 1, 14, "Invalid Period number.");
	$v->isOk ($accnt, "string", 1, 5, "Invalid Accounts Selection.");

	if($accnt == 'slct'){
		if(isset($stkids)){
			foreach($stkids as $key => $stkid){
				$v->isOk ($stkid, "num", 1, 20, "Invalid Stock code.");
			}
		}else{
			return "<li class=err>ERROR : Please select at least one Stock Item.</li>".slctacc();
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
		$stkids = array();
		db_connect();
		$sql = "SELECT stkid FROM stock WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);
		if(pg_num_rows($rs) > 0){
			while($ac = pg_fetch_array($rs)){
				$stkids[] = $ac['stkid'];
			}
		}else{
			return "<li calss=err> There are no Stock Items yet in Cubit.";
		}
	}

	# Period name
	$prdname = prdname($prd);
	$hide="";

	$trans = "";
	foreach($stkids as $key => $stkid){
		$stkRs = get("cubit", "*", "stock", "stkid", $stkid);
		$stk = pg_fetch_array($stkRs);

		# Get balances
		$idRs = get($prd, "max(id), min(id)", "stkledger", "yrdb='".YR_DB."' AND stkid", $stkid);
		$id = pg_fetch_array($idRs);
		if($id['min'] <> 0){
			$balRs = get($prd, "qty, (bqty - qty) as bqty, trantype, (balance - csamt) as balance", "stkledger", "id", $id['min']);
			$bal = pg_fetch_array($balRs);
			$cbalRs = get($prd, "balance", "stkledger", "id", $id['max']);
			$cbal = pg_fetch_array($cbalRs);

			/*
			if($bal['trantype'] == 'dt'){
				$bal['bqty'] =  ($bal['bqty'] + $bal['qty']);
			}else{
				$bal['bqty'] =  ($bal['bqty'] - $bal['qty']);
			}
			*/

		}else{
			if($prd != PRD_DB){
				continue;
			}
			$balRs = get("cubit", "csamt as balance, units as bqty", "stock", "stkid", $stkid);
			$bal = pg_fetch_array($balRs);
			$cbal['balance'] = 0;
			$cbal['bqty'] = 0;
		}

		$balance = sprint($bal['balance']);

		$hide .= "<input type=hidden name=stkids[] value='$stkid'>";
		$trans .= "<tr><td colspan=5><b>($stk[stkcod]) $stk[stkdes]</b></td></tr>";
		$trans .= "<tr><td><br></td><td>Balance Brought Forward</td><td align=right>$bal[bqty]</td><td align=right>$balance </td><td align=right>$balance </td></tr>";

		# --> transactio reding comes here <--- #
		$dbal['balance'] = 0;
		$dbal['bqty'] = 0;


		$tranRs = nget($prd, "*", "stkledger", "yrdb='".YR_DB."' AND stkid", $stkid." ORDER BY id ASC");
		while($tran = pg_fetch_array($tranRs)){
   			$dbal['balance'] += $tran['csamt'];
			$dbal['bqty'] += $tran['qty'];

			# sprinting
			$tran['csamt'] = sprint($tran['csamt']);
			$tran['balance'] = sprint($tran['balance']);

			# Format date
			$tran['edate'] = explode("-", $tran['edate']);
			$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];

			$trans .= "<tr><td>$tran[edate]</td><td>$tran[details]</td><td>$tran[qty]</td><td align=right>$tran[csamt]</td><td align=right>$tran[balance]</td></tr>";
		}
		$dbal['balance'] = sprint($dbal['balance']);

		$trans .= "<tr><td><br></td><td>Total for period $prdname to Date :</td><td align=right>$dbal[bqty]</td><td align=right>$dbal[balance] </td><td align=right>$dbal[balance] </td></tr>";
		$trans .= "<tr><td colspan=5><br></td></tr>";
	}

	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$view = "
	<center>
	<h3>Inventory Ledger</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=75%>
	<tr><th>DATE</th><th>DETAILS</th><th>QTY</th><th>COST AMOUNT</th><th>BALANCE</th></tr>
	$trans
	</table>
	<p>";

	include("temp.xls.php");
	Stream("Ledger", $view);
}
?>
