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
			<h3>General Ledger</h3>
			<h4>Select Options</h4>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='viewtran'>
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td valign='top'>Accounts</td>
					<td><input type='radio' name='accnt' value='slct' checked='yes'>Selected Accounts | <input type='radio' name='accnt' value='all'>All Accounts</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td valign='top'>Select account(s)</td>
					<td>$accs</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Select period</td>
					<td>$prds</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td align='center'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
					<td align='center'><input type='submit' value='Continue &raquo;'></td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts." width='25%'>
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
	$v->isOk ($accnt, "string", 1, 9, "Invalid Accounts Selection.");

	if($accnt == 'slct'){
		if(isset($accids)){
			foreach($accids as $key => $accid){
				$v->isOk ($accid, "num", 1, 20, "Invalid Account number.");
			}
		}else{
			$v->isOk ("###", "num", 0, 0, "ERROR : Please select at least one account.");
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

	# Period name
	$prdname = prdname($prd);

	$trans = "";
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
			if($prd != PRD_DB){
				continue;
			}
			$balRs = get("core", "credit as cbalance, debit as dbalance", "trial_bal", "accid", $accid);
			$bal = pg_fetch_array($balRs);
			$cbal['cbalance'] = 0;
			$cbal['dbalance'] = 0;
		}

		if($bal['dbalance'] > $bal['cbalance']){
			$bal['dbalance'] = sprint($bal['dbalance'] - $bal['cbalance']);
			$bal['cbalance'] = "";
			$balance = $bal['dbalance'];
			$fl = "DR";
		}elseif($bal['cbalance'] > $bal['dbalance']){
			$bal['cbalance'] = sprint($bal['cbalance'] - $bal['dbalance']);
			$bal['dbalance'] = "";
			$balance = $bal['cbalance'];
			$fl = "CR";
		}else{
			$bal['cbalance'] = "";
			$bal['dbalance'] = "";
			$balance  = "0.00";
			$fl = "";
		}

		$balance = sprint($balance);
		$bal['cbalance'] = sprint($bal['cbalance']);
		$bal['dbalance'] = sprint($bal['dbalance']);

		global $MONPRD, $PRDMON;

		// calculate which year the current period is in
		$prd_y = getFinYear() - 1;
		if ($prd < $PRDMON[1]) {
			++$prd_y;
		}

		// make the date of the last day of the previous prd
		$bbf_date = date("t-M-Y", mktime(0, 0, 0, $prd - 1, 1, $prd_y));

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

		# --> transactio reding comes here <--- #
		$dbal['debit'] = 0;
		$dbal['credit'] = 0;

		if($t=="s") {
			$tranRs = get($prd, "*", "ledger", "acc", $accid);
		} else {
			$tranRs = get($prd, "*", "ledger", "acc", $accid,"ORDER BY edate,id");
		}
		while($tran = pg_fetch_array($tranRs)){
   			$dbal['debit'] += $tran['debit'];
			$dbal['credit'] += $tran['credit'];

			if($t=="t") {
				$tran['dbalance']=$dbal['debit']+$bal['dbalance'];
				$tran['cbalance']=$dbal['credit']+$bal['cbalance'];
			}

			# Current(Running) balance
			if($tran['dbalance'] > $tran['cbalance']){
				$tran['dbalance'] = sprint($tran['dbalance'] - $tran['cbalance']);
				$tran['cbalance'] = "";
				$cbalance = $tran['dbalance'];
				$cfl = "DR";
			}elseif($tran['cbalance'] > $tran['dbalance']){
				$tran['cbalance'] = sprint($tran['cbalance'] - $tran['dbalance']);
				$tran['dbalance'] = "";
				$cbalance = $tran['cbalance'];
				$cfl = "CR";
			}else{
				$tran['cbalance'] = "";
				$tran['dbalance'] = "";
				$cbalance  = "0.00";
				$cfl = "";
			}

			if($t=="s") {
				$tran['edate']=$tran['sdate'];
			}

			# Format date
			$tran['edate'] = explode("-", $tran['edate']);
			$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];

			$tran['debit'] = sprint($tran['debit']);
			$tran['credit'] = sprint($tran['credit']);

			$trans .= "
					<tr>
						<td><br></td>
						<td>$tran[edate]</td>
						<td>$tran[eref]</td>
						<td>$tran[descript]</td>
						<td align='right'>$tran[debit]</td>
						<td align='right'>$tran[credit]</td>
						<td align='right'>$cbalance $cfl</td>
						<td>$tran[ctopacc]/$tran[caccnum] - $tran[caccname]</td>
					</tr>";
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

		$trans .= "
				<tr>
					<td colspan='2'><br></td>
					<td>A/C Total</td>
					<td>Total for period $prdname to Date :</td>
					<td align='right'>$dbal[debit]</td>
					<td align='right'>$dbal[credit]</td>
					<td align='right'></td>
					<td> </td>
				</tr>";
		$trans .= "<tr><td colspan=8><br></td></tr>";
	}

	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$OUTPUT = "
			<center>
			<h3>General Ledger</h3>
			<table ".TMPL_tblDflts." width='90%'>
				<tr>
					<td width='50%' align='left' colspan='4'>".COMP_NAME."</td>
					<td width='50%' align='right' colspan='4'>".date("Y-m-d")."</td>
				</tr>
				<tr><td colspan='8'><br></td></tr>
				<tr>
					<td>$sp</td>
					<th>Date</th>
					<th>Reference</th>
					<th>Description</th>
					<th>Debit</th>
					<th>Credit</th>
					<th>Balance</th>
					<th>Contra Acc</th>
				</tr>
				$trans
			<table>";

	require_lib("xls");
	StreamXLS("GeneralLedger".date("dmY"), $OUTPUT);
	# return $view;
}
?>
