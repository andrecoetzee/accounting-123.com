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

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "slctacc":
			$OUTPUT = slctacc($HTTP_POST_VARS);
			break;
		case "viewtran":
			$OUTPUT = viewtran($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT ="Invalid.";
	}
} else {
	$OUTPUT =select_year();
}

$OUTPUT .= "
	<p>
	<table ".TMPL_tblDflts.">
		<tr><td><br></td></tr>
		<tr>
			<th>Quick Links</th>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

# Get templete
require("../template.php");



function select_year()
{

	db_conn('core');

	$Sl = "SELECT * FROM year WHERE closed='y' ORDER BY yrname";
	$Ri = db_exec($Sl) or errDie("Unable to get data");

	if(pg_num_rows($Ri) < 1) {
		return "There are no closed years.";
	}

	$years = "<select name='year'>";
	while($data = pg_fetch_array($Ri)) {
		$years .= "<option value='$data[yrdb]'>$data[yrname]</option>";
	}
	$years .= "</select>";

	$out = "
		<h3>Creditor Ledger</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='slctacc'>
			<tr>
				<th>Select Year</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$years</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='submit' value='Next &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $out;

}



function slctacc($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($year, "string", 1, 10, "Invalid year.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	db_conn('core');
	$Sl="SELECT * FROM year WHERE yrdb='$year'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	$yd=pg_fetch_array($Ri);

	# from period
	$prds = "<select name=prd>";
	db_conn($yd['yrname']."_audit");
	$sql = "SELECT * FROM closedprd ORDER BY prdnum";
	$prdRslt = db_exec($sql);
	if(pg_numrows($prdRslt) < 1){
		return "<li class=err>ERROR : There are no periods set for the current year";
	}
	while($prd = pg_fetch_array($prdRslt)){
		if($prd['prdnum'] == PRD_DB){
			$sel = "selected";
		}else{
			$sel= "";
		}
		$prds .="<option value='$prd[prdnum]' $sel>$prd[prdname]</option>";
	}
	$prds .= "</select>";

	db_connect();
	$sql = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' ORDER BY supid ASC";
	$supRslt = db_exec($sql) or errDie("Could not retrieve suppliers Information from the Database.",SELF);

	if(pg_numrows($supRslt) < 1){
		return "<li class=err> There are no suppliers in Cubit.";
	}
	$supts = "<select name=supids[] multiple size=10>";
	while($sup = pg_fetch_array($supRslt)){
		$supts .= "<option value='$sup[supid]'>$sup[supname]</option>";
	}
	$supts .= "</select>";

	$slctacc = "
	<p>
	<h3>Creditors Ledger</h3>
	<h4>Select Options</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=viewtran>
	<input type=hidden name=year value='$year'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>suppliers</td><td><input type=radio name=accnt value=slct checked=yes>Selected Accounts | <input type=radio name=accnt value=all>All Accounts</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Select Customer(s)</td><td>$supts</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Select period</td><td>$prds</td></tr>
	<tr><td><br></td></tr>
	<tr><td></td><td align=right><input type=submit value='Continue &raquo;'></td></tr>
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
	$v->isOk ($year, "string", 1, 10, "Invalid year.");

	if(isset($accnt)){
		if($accnt == 'slct'){
			if(isset($supids)){
				foreach($supids as $key => $supid){
					$v->isOk ($supid, "num", 1, 20, "Invalid Supplier number.");
				}
			}else{
				return "<li class=err>Please select at least one Creditor.</li>".slctacc();
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
		$supids = array();
		db_connect();
		$sql = "SELECT supid FROM suppliers WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);
		if(pg_num_rows($rs) > 0){
			while($ac = pg_fetch_array($rs)){
				$supids[] = $ac['supid'];
			}
		}else{
			return "<li calss=err> There are no suppliers yet in Cubit.";
		}
	}

	# Period name
	$prdname = prdname($prd);

	db_conn('core');
	$Sl="SELECT * FROM year WHERE yrdb='$year'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	$yd=pg_fetch_array($Ri);

	$trans = "";
	foreach($supids as $key => $supid){
		$supRs = get("cubit", "supname, supno, balance", "suppliers", "supid", $supid);
		$sup = pg_fetch_array($supRs);

		$idRs = get($yd['yrname']."_audit", "min(id)", $prdname."_suppledger", "supid", $supid);
		$id = pg_fetch_array($idRs);

		if($id['min'] <> 0){
			$balRs = get($yd['yrname']."_audit", "(cbalance-credit) AS cbalance,(dbalance-debit) AS dbalance", $prdname."_suppledger", "id", $id['min']);
			$bal = pg_fetch_array($balRs);
			$bal['cbalance'] += 0;
			$bal['dbalance'] += 0;
		}else{
			$balRs = get("cubit", "balance", "suppliers", "supid", $supid);
			$bal = pg_fetch_array($balRs);
			$bal['balance']+=0;

			if($bal['balance']<0) {
				$bal['dbalance'] = ($bal['balance']*-1);
				$bal['cbalance'] = 0;
			} else {
				$bal['cbalance'] = $bal['balance'];
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

		$bal['credit'] = $bal['cbalance'];
		$bal['debit'] = $bal['dbalance'];

		$balance=sprint($bal['cbalance']-$bal['dbalance']);

		$trans .= "<tr><td colspan=8><b>$sup[supno] - $sup[supname] </b></td></tr>";
		$trans .= "<tr><td colspan=2><br></td><td>Br/Forwd</td><td>Brought Forward</td><td align=right>$bal[debit]</td><td align=right>$bal[credit]</td><td align=right>$balance</td><td> </td></tr>";

		# --> Transaction reading comes here <--- #
		$dbal['debit'] = 0;
		$dbal['credit'] = 0;

		$tranRs = get($yd['yrname']."_audit", "*", $prdname."_suppledger", "supid", $supid,"ORDER BY id");
		while($tran = pg_fetch_array($tranRs)){
			$caccRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $tran['contra']);
			$cacc = pg_fetch_array($caccRs);

			$dbal['debit'] += $tran['debit'];
			$dbal['credit'] += $tran['credit'];

			$cbalance = sprint($tran['cbalance'] - $tran['dbalance']);

			# Format date
			$tran['edate'] = explode("-", $tran['edate']);
			$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];

			$trans .= "<tr><td><br></td><td>$tran[edate]</td><td>$tran[eref]</td><td>$tran[descript]</td><td align=right>$tran[debit]</td><td align=right>$tran[credit]</td><td align=right>$cbalance</td><td>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td></tr>";
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
	<h3>Creditors Ledger</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=75%>
	<tr><td>$sp</td><th>Date</th><th>Reference</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th><th>Contra Acc</th></tr>
	$trans
	<table>";

	include("temp.xls.php");
	Stream("Ledger", $view);

}
?>
