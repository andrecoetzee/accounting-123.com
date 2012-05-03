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
##

# Get settings
require("../settings.php");
require("../core-settings.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "viewtran":
			$OUTPUT = viewtran($_POST);
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
	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' ORDER BY cusnum ASC";
	$cusRslt = db_exec($sql) or errDie("Could not retrieve Customers Information from the Database.",SELF);

	if(pg_numrows($cusRslt) < 1){
		return "<li class=err> There are no Customers in Cubit.";
	}
	$custs = "<select name=cusnums[] multiple size=10>";
	while($cus = pg_fetch_array($cusRslt)){
		$custs .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
	}
	$custs .= "</select>";

	$slctacc = "
	<p>
	<h3>Debtors Ledger</h3>
	<h4>Select Options</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=viewtran>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-even'><td valign=top>Customers</td><td><input type=radio name=accnt value=slct checked=yes>Selected Accounts | <input type=radio name=accnt value=all>All Accounts</td></tr>
	<tr class='bg-odd'><td valign=top>Select Customer(s)</td><td>$custs</td></tr>
	<tr class='bg-even'><td>Select period</td><td>$prds</td></tr>
	<tr class='bg-odd'><td>Order By</td><td>Transaction Date<input type=radio name=t checked value='t'>System Date<input type=radio name=t value='s'></td></tr>
	<tr><td><br></td></tr>
	<tr><td></td><td align=right><input type=submit value='Continue &raquo;'></td></tr>
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
function viewtran($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd, "string", 1, 14, "Invalid Period number.");

	if(isset($accnt)){
		if($accnt == 'slct'){
			if(isset($cusnums)){
				foreach($cusnums as $key => $cusnum){
					$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer number.");
				}
			}else{
				return "<li class=err>Please select at least one Debtor.</li>".slctacc();
			}
		}
	}else{
		$v->isOk ("###", "num", 0, 0, "ERROR : Invalid Accounts Selection.");
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
		$cusnums = array();
		db_connect();
		$sql = "SELECT cusnum FROM customers WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);
		if(pg_num_rows($rs) > 0){
			while($ac = pg_fetch_array($rs)){
				$cusnums[] = $ac['cusnum'];
			}
		}else{
			return "<li calss=err> There are no customers yet in Cubit.";
		}
	}

	# Period name
	$prdname = prdname($prd);
	$hide="";

	$trans = "";
	foreach($cusnums as $key => $cusnum){
		$cusRs = get("cubit", "cusname, surname, accno, balance", "customers", "cusnum", $cusnum);
		$cus = pg_fetch_array($cusRs);

		$idRs = get($prd, "min(id)", "custledger", "cusnum", $cusnum);
		$id = pg_fetch_array($idRs);

		if($id['min'] <> 0){
			$balRs = get($prd, "(cbalance-credit) AS cbalance,(dbalance-debit) AS dbalance", "custledger", "id", $id['min']);
			$bal = pg_fetch_array($balRs);
			$bal['cbalance'] += 0;
			$bal['dbalance'] += 0;
		}else{
			$balRs = get("cubit", "balance", "customers", "cusnum", $cusnum);
			$bal = pg_fetch_array($balRs);
			$bal['balance']+=0;

			if($bal['balance']>0) {
				$bal['dbalance'] = $bal['balance'];
				$bal['cbalance'] = 0;
			} else {
				$bal['cbalance'] = ($bal['balance']*-1);
				$bal['dbalance'] = 0;
			}
			//$bal['dbalance'] += $amount;
		}

		# Total balance changes
		if($bal['dbalance'] > $bal['cbalance']){
			$bal['dbalance'] = ($bal['dbalance'] - $bal['cbalance']);
			$bal['cbalance'] = 0;
		}elseif($bal['cbalance'] > $bal['dbalance']){
			$bal['cbalance'] = ($bal['cbalance'] - $bal['dbalance']);
			$bal['dbalance'] = 0;
		}else{
			$bal['cbalance'] = 0;
			$bal['dbalance'] = 0;
		}

		$bal['credit'] = sprint($bal['cbalance']);
		$bal['debit'] = sprint($bal['dbalance']);

		$balance=sprint($bal['dbalance']-$bal['cbalance']);

		$hide .= "<input type=hidden name=cusnums[] value='$cusnum'>";
		$trans .= "<tr><td colspan=8><b>$cus[accno] - $cus[cusname] $cus[surname]</b></td></tr>";
		$trans .= "<tr><td colspan=2><br></td><td>Br/Forwd</td><td>Brought Forward</td><td align=right>$bal[debit]</td><td align=right>$bal[credit]</td><td align=right>$balance</td><td> </td></tr>";

		# --> Transaction reading comes here <--- #
		$dbal['debit'] = 0;
		$dbal['credit'] = 0;

		if($t=="s") {

			$tranRs = get($prd, "*", "custledger", "cusnum", $cusnum,"ORDER BY id");

		} else {
			$tranRs = get($prd, "*", "custledger", "cusnum", $cusnum,"ORDER BY edate,id");
		}
		while($tran = pg_fetch_array($tranRs)){
			$caccRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $tran['contra']);
			$cacc = pg_fetch_array($caccRs);

			$dbal['debit'] += $tran['debit'];
			$dbal['credit'] += $tran['credit'];

			if($t=="s") {

				$balance = sprint($tran['dbalance'] - $tran['cbalance']);

			} else {

				$balance = sprint(($dbal['debit']+$bal['debit']) - ($dbal['credit']+$bal['credit']));

			}

			if($t=="t") {
				$tran['sdate']=$tran['edate'];
			}

			# Format date
			$tran['sdate'] = explode("-", $tran['sdate']);
			$tran['sdate'] = $tran['sdate'][2]."-".$tran['sdate'][1]."-".$tran['sdate'][0];

			$trans .= "<tr><td><br></td><td>$tran[sdate]</td><td>$tran[eref]</td><td>$tran[descript]</td><td align=right>".sprint($tran['debit'])."</td><td align=right>".sprint($tran['credit'])."</td><td align=right>$balance</td><td>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td></tr>";
		}

		# Total balance changes
		if($dbal['debit'] > $dbal['credit']){
			$dbal['debit'] = sprint($dbal['debit'] - $dbal['credit']);
			$dbal['credit'] = "";
		}elseif($dbal['credit'] > $dbal['debit']){
			$dbal['credit'] = sprint($dbal['credit'] - $dbal['debit']);
			$dbal['debit'] = "";
		}else{
			$dbal['credit'] = "";
			$dbal['debit'] = "0.00";
		}

		$trans .= "<tr><td colspan=2><br></td><td>A/C Total</td><td>Total for period $prdname to Date :</td><td align=right>$dbal[debit]</td><td align=right>$dbal[credit]</td><td align=right></td><td> </td></tr>";
		$trans .= "<tr><td colspan=8><br></td></tr>";
	}

	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$view = "
	<center>
	<h3>Debtors Ledger</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=75%>
	<tr><td>$sp</td><th>Date</th><th>Reference</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th><th>Contra Acc</th></tr>
	$trans
	</table>";

	include("temp.xls.php");
	Stream("Ledger", $view);
}
?>
