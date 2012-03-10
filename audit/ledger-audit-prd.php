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
		case "slctacc":
			$OUTPUT = slctacc($HTTP_POST_VARS);
			break;
		case "viewtran":
			$OUTPUT = viewtran($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = "Invalid.";
	}
} else {
	$OUTPUT = select_year();
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
		return "<li class='err'>There are no closed years.</li>";
	}

	$years = "<select name='year'>";
	while($data = pg_fetch_array($Ri)) {
		$years .= "<option value='$data[yrdb]'>$data[yrname]</option>";
	}
	$years .= "</select>";

	$out = "
		<h3>General Ledger by period range</h3>
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
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	db_conn('core');

	$Sl = "SELECT * FROM year WHERE yrdb='$year'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$yd = pg_fetch_array($Ri);

	# from period
	global $PRDMON;
	$fprd = finMonList("fprd", $PRDMON[1], false, $yd["yrname"]);
	$tprd = finMonList("tprd", $PRDMON[12], false, $yd["yrname"]);

	db_conn($year);

	$sql = "SELECT * FROM year_balance WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);

	if(pg_numrows($accRslt) < 1){
		return "<li class='err'> There are no Accounts in Cubit.</li>";
	}
	$accs = "<select name='accids[]' multiple size='10'>";
	while($acc = pg_fetch_array($accRslt)){
		$accs .= "<option value='$acc[accid]'>$acc[accname]</option>";
	}
	$accs .= "</select>";


	$slctacc = "
		<p>
		<h3>Previous Year Period Range General Ledger</h3>
		<h4>Select Options</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='viewtran'>
			<input type='hidden' name='year' value='$year'>
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
				<td>Period Range :</td>
				<td valign='center' colspan='3'>$fprd To : $tprd</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='center'></td>
				<td align='center'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		</table>";
	return $slctacc;

}



# View all transaction for the ledger
function viewtran($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accnt, "string", 1, 5, "Invalid Accounts Selection.");
	$v->isOk ($year, "string", 1, 10, "Invalid year.");

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
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get the ids
	if($accnt == 'all'){
		$accids = array();
		db_conn($year);
		$sql = "SELECT accid FROM year_balance WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);
		if(pg_num_rows($rs) > 0){
			while($ac = pg_fetch_array($rs)){
				$accids[] = $ac['accid'];
			}
		} else{
			return "<li class='err'> There are no accounts yet in Cubit.</li>";
		}
	}

	$cp = $fprd;
	$fs = 0;

	if($cp == ($tprd + 1)) {
		$f = true;
	} else {
		$f = false;
	}


	db_conn('core');

	$Sl = "SELECT * FROM year WHERE yrdb='$year'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$yd = pg_fetch_array($Ri);

	# Get all Closed Periods
	db_conn("audit");

	// $sql = "SELECT * FROM closedprd";
	// $clsRs = db_exec($sql) or errDie("Could not get closed periods from audit DB",SELF);
	$trans = "";
	//while($cls = pg_fetch_array($clsRs)){
	while($cp!=($tprd+1)||$f) {
		$prd = $cp;
		$cp++;

		if($cp == 13) {
			$cp = 1;
		}
		$fs++;
		if($fs > 13) {
			break;
		}
		$f = false;

		# Period name
		$prdname = prdname($prd);

		$trans .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='8' align='center'><h3>$prdname</h3></td>
			</tr>";
		$hide = "";
		if(isset($t)) unset($t);
		foreach($accids as $key => $accid){
			$accRs = get($year, "accname, accid, topacc, accnum", "year_balance", "accid", $accid);
			$acc = pg_fetch_array($accRs);

			$test = $prdname."_ledger";

			db_conn($yd['yrname']."_audit");

			$Sl = "SELECT * FROM $test";
			if(!($Ri = @db_exec($Sl))) {
				continue;
			}

			# Get balances
			$idRs = get($yd['yrname']."_audit", "max(id), min(id)", $prdname."_ledger", "acc", $accid);
			$id = pg_fetch_array($idRs);
			if($id['min'] <> 0){
				$balRs = get($yd['yrname']."_audit", "(cbalance-credit) as cbalance,(dbalance-debit) as dbalance", $prdname."_ledger", "id", $id['min']);
				$bal = pg_fetch_array($balRs);
				$cbalRs = get($yd['yrname']."_audit", "cbalance,dbalance", $prdname."_ledger", "id", $id['max']);
				$cbal = pg_fetch_array($cbalRs);
			}else{
				$balRs = get($year, "credit as cbalance, debit as dbalance", $prdname, "accid", $accid);
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

			$hide .= "<input type='hidden' name='accids[]' value='$acc[accid]'>";
			$trans .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='8'><b>$acc[topacc]/$acc[accnum] - $acc[accname]</b></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'><br></td>
					<td>Br/Forwd</td>
					<td>Brought Forward</td>
					<td align='right'>$bal[dbalance]</td>
					<td align='right'>$bal[cbalance]</td>
					<td align='right'>$balance $fl</td>
					<td> </td>
				</tr>";

			# --> transactio reding comes here <--- #
			$dbal['debit'] = 0;
			$dbal['credit'] = 0;

			$tranRs = get($yd['yrname']."_audit", "*", $prdname."_ledger", "acc", $accid);
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

				$trans .= "
					<tr bgcolor='".bgcolorg()."'>
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
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2'>&nbsp;</td>
					<td>A/C Total</td>
					<td>Total for period $prdname to Date :</td>
					<td align='right'>$dbal[debit]</td>
					<td align='right'>$dbal[credit]</td>
					<td align='right'></td>
					<td>&nbsp;</td>
				</tr>
				<tr><td colspan='8'><br></td></tr>";
		}
		if(!isset($t)){
			$trans .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='8' align='center'><li> There are no transactions in this period.</td>
				</tr>";
		}
	}
	$fprdname = prdname($fprd);
	$tprdname = prdname($tprd);

	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$view = "
		<center>
		<form action='../xls/ledger-audit-prd-xls.php' method='POST'>
			<input type='hidden' name='key' value='viewtran'>
			<input type='hidden' name='fprd' value='$fprd'>
			<input type='hidden' name='tprd' value='$tprd'>
			<input type='hidden' name='accnt' value='$accnt'>
			<input type='hidden' name='year' value='$year'>
			$hide
			<h3>Period Range General Ledger</h3>
			<h4>$fprdname - $tprdname</h4>
		<table ".TMPL_tblDflts." width='90%'>
			<tr><td colspan='8'><br></td></tr>
			<tr>
				<th>$sp</th>
				<th>Date</th>
				<th>Reference</th>
				<th>Description</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Balance</th>
				<th>Contra Acc</th>
			</tr>
			$trans
			<tr><td colspan='8'><br></td></tr>
			<tr>
				<td colspan='8' align='center'><input type='submit' value='Export to Spreadsheet'></td>
			</tr>
		<table>
		</form>";
	return $view;

}


?>