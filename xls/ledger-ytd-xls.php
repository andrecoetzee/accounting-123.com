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
	core_connect();
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);

	if(pg_numrows($accRslt) < 1){
		return "<li class=err> There are no Accounts in Cubit.";
	}
	$accs = "<select name=accids[] multiple size=10>";
	while($acc = pg_fetch_array($accRslt)){
		$accs .= "<option value='$acc[accid]'>$acc[accname]</option>";
	}
	$accs .= "</select>";

	$slctacc = "
	<p>
	<h3>Year Review General Ledger</h3>
	<h4>Select Options</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=viewtran>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>Accounts</td><td><input type=radio name=accnt value=slct checked=yes>Selected Accounts | <input type=radio name=accnt value=all>All Accounts</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td valign=top>Select account(s)</td><td>$accs</td></tr>
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
function viewtran($HTTP_POST_VARS)
{
	global $MONPRD, $PRDMON;

	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accnt, "string", 1, 5, "Invalid Accounts Selection.");

	if($accnt == 'slct'){
		if(isset($accids)){
			foreach($accids as $key => $accid){
				$v->isOk ($accid, "num", 1, 20, "Invalid Account number.");
			}
		}else{
			return "<li class=err>Please select at least one account.</li>".slctacc();
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
		$accids = array();
		core_connect();
		$sql = "SELECT accid FROM accounts WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);
		if(pg_num_rows($rs) > 0){
			while($ac = pg_fetch_array($rs)){
				$accids[] = $ac['accid'];
			}
		}else{
			return "<li calss=err> There are no accounts yet in Cubit.";
		}
	}

	$hide="";

	# Get all previous Periods
	db_conn("audit");
	$sql = "SELECT prd.*, map.period FROM audit.closedprd prd, core.prdmap map
			WHERE prd.prdnum=map.month AND map.period::integer<'".$MONPRD[PRD_DB]."'
			ORDER BY map.period::integer";
	$clsRs = db_exec($sql) or errDie("Could not get closed periods from audit DB",SELF);
	$trans = "";
	if(pg_numrows($clsRs) > 0){
	while($cls = pg_fetch_array($clsRs)){
		$prd = $cls['prdnum'];

		# Period name
		$prdname = prdname($prd);

		$trans .= "<tr><td colspan=8 align=center><h3>$prdname</h3></td></tr>";
		$hide = "";
		if(isset($t)) unset($t);
		foreach($accids as $key => $accid){
			$accRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $accid);
			$acc = pg_fetch_array($accRs);

			# Get balances
			$idRs = get($prd, "max(id), min(id)", "ledger", "acc", $accid);
			$id = pg_fetch_array($idRs);
			if($id['min'] <> 0){
				$balRs = get($prd, "(cbalance-credit) as cbalance,(dbalance-debit) as dbalance", "ledger", "id", $id['min']);
				$bal = pg_fetch_array($balRs);
				$cbalRs = get($prd, "cbalance,dbalance", "ledger", "id", $id['max']);
				$cbal = pg_fetch_array($cbalRs);
			}else{
				continue;

				$balRs = get("core", "credit as cbalance, debit as dbalance", "trial_bal", "accid", $accid);
				$bal = pg_fetch_array($balRs);
				$cbal['cbalance'] = 0;
				$cbal['dbalance'] = 0;
			}

			$t = "lemme ci";

			if($bal['dbalance'] > $bal['cbalance']){
				$bal['dbalance'] = sprint($bal['dbalance'] - $bal['cbalance']);
				$bal['cbalance'] = "";
				$balance = $bal['dbalance'];
				$fl = "DT";
			}elseif($bal['cbalance'] > $bal['dbalance']){
				$bal['cbalance'] = sprint($bal['cbalance'] - $bal['dbalance']);
				$bal['dbalance'] = "";
				$balance = $bal['cbalance'];
				$fl = "CT";
			}else{
				$bal['cbalance'] = "";
				$bal['dbalance'] = "";
				$balance  = "0.00";
				$fl = "";
			}

			$balance = sprint($balance);
			$bal['cbalance'] = sprint($bal['cbalance']);
			$bal['dbalance'] = sprint($bal['dbalance']);

			// calculate which year the current period is in
			$prd_y = getFinYear() - 1;
			if ($prd < $PRDMON[1]) {
				++$prd_y;
			}

			// make the date of the last day of the previous prd
			$bbf_date = date("t-M-Y", mktime(0, 0, 0, $prd - 1, 1, $prd_y));

			$hide .= "<input type=hidden name=accids[] value='$acc[accid]'>";
			$trans .= "
			<tr>
				<td colspan='8'><b>$acc[topacc]/$acc[accnum] - $acc[accname]</b></td>
			</tr>
			<tr>
				<td colspan='2' align='right'>$bbf_date</td>
				<td>Br/Forwd</td>
				<td>Brought Forward</td>
				<td align='right'>$bal[dbalance]</td>
				<td align='right'>$bal[cbalance]</td>
				<td align='right'>$balance $fl</td>
				<td>&nbsp;</td>
			</tr>";

			# --> Transaction reding comes here <--- #
			$dbal['debit'] = 0;
			$dbal['credit'] = 0;

			$tranRs = get($prd, "*", "ledger", "acc", $accid);
			while($tran = pg_fetch_array($tranRs)){
				$dbal['debit'] += $tran['debit'];
				$dbal['credit'] += $tran['credit'];

				# Current(Running) balance
				if($tran['dbalance'] > $tran['cbalance']){
					$tran['dbalance'] = sprint($tran['dbalance'] - $tran['cbalance']);
					$tran['cbalance'] = "";
					$cbalance = $tran['dbalance'];
					$cfl = "DT";
				}elseif($tran['cbalance'] > $tran['dbalance']){
					$tran['cbalance'] = sprint($tran['cbalance'] - $tran['dbalance']);
					$tran['dbalance'] = "";
					$cbalance = $tran['cbalance'];
					$cfl = "CT";
				}else{
					$tran['cbalance'] = "";
					$tran['dbalance'] = "";
					$cbalance  = "0.00";
					$cfl = "";
				}

				# Format date
				$tran['edate'] = explode("-", $tran['edate']);
				$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];

				$tran['debit'] = sprint($tran['debit']);
				$tran['credit'] = sprint($tran['credit']);

				$trans .= "<tr><td><br></td><td>$tran[edate]</td><td>$tran[eref]</td><td>$tran[descript]</td><td align=right>$tran[debit]</td><td align=right>$tran[credit]</td><td align=right>$cbalance $cfl</td><td>$tran[ctopacc]/$tran[caccnum] - $tran[caccname]</td></tr>";
			}

			# Total balance changes
			if($dbal['debit'] > $dbal['credit']){
				$dbal['debit'] = sprint($dbal['debit'] - $dbal['credit']);
				$dbal['credit'] = "";
			}elseif($dbal['credit'] > $dbal['debit']){
				$dbal['credit'] = sprint($dbal['credit'] - $dbal['debit']);
				$dbal['debit'] = "";
			}else{
				$dbal['credit'] = "0.00";
				$dbal['debit'] = "0.00";
			}

			$trans .= "<tr><td colspan=2><br></td><td>A/C Total</td><td>Total for period $prdname to Date :</td><td align=right>$dbal[debit]</td><td align=right>$dbal[credit]</td><td align=right></td><td> </td></tr>";
			$trans .= "<tr><td colspan=8><br></td></tr>";
		}
		if(!isset($t)){
			$trans .= "<tr><td colspan=8 align=center><li> There are no transactions in this period.</td></tr>";
		}
	}
	}else{
		$trans .= "<tr><td colspan=8 align=center><li> There are no closed periods this year.</td></tr>";
	}

	# Period name
	$prdname = prdname(PRD_DB);
	$prd = PRD_DB;
	$trans .= "<tr><td><br></td></tr>";
	$trans .= "<tr><td colspan=8 align=center><h3>$prdname</h3></td></tr>";
	$hide = "";
	if(isset($t)) unset($t);
	foreach($accids as $key => $accid){
		$accRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $accid);
		$acc = pg_fetch_array($accRs);

		# Get balances
		$idRs = get($prd, "max(id), min(id)", "ledger", "acc", $accid);
		$id = pg_fetch_array($idRs);
		if($id['min'] <> 0){
			$balRs = get($prd, "(cbalance-credit) as cbalance,(dbalance-debit) as dbalance", "ledger", "id", $id['min']);
			$bal = pg_fetch_array($balRs);
			$cbalRs = get($prd, "cbalance,dbalance", "ledger", "id", $id['max']);
			$cbal = pg_fetch_array($cbalRs);
		}else{
			continue;

			$balRs = get("core", "credit as cbalance, debit as dbalance", "trial_bal", "accid", $accid);
			$bal = pg_fetch_array($balRs);
			$cbal['cbalance'] = 0;
			$cbal['dbalance'] = 0;
		}

		$t = "lemme ci";

		if($bal['dbalance'] > $bal['cbalance']){
			$bal['dbalance'] = sprint($bal['dbalance'] - $bal['cbalance']);
			$bal['cbalance'] = "";
			$balance = $bal['dbalance'];
			$fl = "DT";
		}elseif($bal['cbalance'] > $bal['dbalance']){
			$bal['cbalance'] = sprint($bal['cbalance'] - $bal['dbalance']);
			$bal['dbalance'] = "";
			$balance = $bal['cbalance'];
			$fl = "CT";
		}else{
			$bal['cbalance'] = "";
			$bal['dbalance'] = "";
			$balance  = "0.00";
			$fl = "";
		}

		$balance = sprint($balance);
		$bal['cbalance'] = sprint($bal['cbalance']);
		$bal['dbalance'] = sprint($bal['dbalance']);

		// calculate which year the current period is in
		$prd_y = getFinYear() - 1;
		if ($prd < $PRDMON[1]) {
			++$prd_y;
		}

		// make the date of the last day of the previous prd
		$bbf_date = date("t-M-Y", mktime(0, 0, 0, $prd - 1, 1, $prd_y));

		$hide .= "<input type=hidden name=accids[] value='$acc[accid]'>";
		$trans .= "
		<tr>
			<td colspan='8'><b>$acc[topacc]/$acc[accnum] - $acc[accname]</b></td>
		</tr>
		<tr>
			<td colspan='2'>$bbf_date</td>
			<td>Br/Forwd</td>
			<td>Brought Forward</td>
			<td align='right'>$bal[dbalance]</td>
			<td align='right'>$bal[cbalance]</td>
			<td align='right'>$balance $fl</td>
			<td>&nbsp;</td>
		</tr>";

		# --> transactio reding comes here <--- #
		$dbal['debit'] = 0;
		$dbal['credit'] = 0;

		$tranRs = get($prd, "*", "ledger", "acc", $accid);
		while($tran = pg_fetch_array($tranRs)){
			$dbal['debit'] += $tran['debit'];
			$dbal['credit'] += $tran['credit'];

			# Current(Running) balance
			if($tran['dbalance'] > $tran['cbalance']){
				$tran['dbalance'] = sprint($tran['dbalance'] - $tran['cbalance']);
				$tran['cbalance'] = "";
				$cbalance = $tran['dbalance'];
				$cfl = "DT";
			}elseif($tran['cbalance'] > $tran['dbalance']){
				$tran['cbalance'] = sprint($tran['cbalance'] - $tran['dbalance']);
				$tran['dbalance'] = "";
				$cbalance = $tran['cbalance'];
				$cfl = "CT";
			}else{
				$tran['cbalance'] = "";
				$tran['dbalance'] = "";
				$cbalance  = "0.00";
				$cfl = "";
			}

			# Format date
			$tran['edate'] = explode("-", $tran['edate']);
			$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];

			$tran['debit'] = sprint($tran['debit']);
			$tran['credit'] = sprint($tran['credit']);

			$trans .= "<tr><td><br></td><td>$tran[edate]</td><td>$tran[eref]</td><td>$tran[descript]</td><td align=right>$tran[debit]</td><td align=right>$tran[credit]</td><td align=right>$cbalance $cfl</td><td>$tran[ctopacc]/$tran[caccnum] - $tran[caccname]</td></tr>";
		}

		# Total balance changes
		if($dbal['debit'] > $dbal['credit']){
			$dbal['debit'] = sprint($dbal['debit'] - $dbal['credit']);
			$dbal['credit'] = "";
		}elseif($dbal['credit'] > $dbal['debit']){
			$dbal['credit'] = sprint($dbal['credit'] - $dbal['debit']);
			$dbal['debit'] = "";
		}else{
			$dbal['credit'] = "0.00";
			$dbal['debit'] = "0.00";
		}

		$trans .= "<tr><td colspan=2><br></td><td>A/C Total</td><td>Total for period $prdname to Date :</td><td align=right>$dbal[debit]</td><td align=right>$dbal[credit]</td><td align=right></td><td> </td></tr>";
		$trans .= "<tr><td colspan=8><br></td></tr>";
	}
	if(!isset($t)){
		$trans .= "<tr><td colspan=8 align=center><li> There are no transactions in this period.</td></tr>";
	}

	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$view = "
	<center>
	<h3>Year Review General Ledger</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=90%>
	<tr><td colspan=8><br></td></tr>
	<tr><th>$sp</th><th>Date</th><th>Reference</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th><th>Contra Acc</th></tr>
	$trans
	<tr><td colspan=8><br></td></tr>
	</table>";

	include("temp.xls.php");
	Stream("Ledger", $view);

	return $view;
}
?>
