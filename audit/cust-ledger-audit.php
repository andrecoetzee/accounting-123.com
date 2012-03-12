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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "slctacc":
			$OUTPUT = slctacc($_POST);
			break;
		case "viewtran":
			$OUTPUT = viewtran($_POST);
			break;
		default:
			$OUTPUT ="Invalid year";
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

	if(pg_num_rows($Ri)<1) {
		return "<li class='err'>There are no closed years.</li>";
	}

	$years = "<select name=year>";
	while($data=pg_fetch_array($Ri)) {
		$years .= "<option value='$data[yrdb]'>$data[yrname]</option>";
	}
	$years .= "</select>";

	$out = "
		<h3>Debtor Ledger</h3>
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



function slctacc($_POST)
{

	extract($_POST);

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

	global $PRDMON;
	$fprds = finMonList("fprd", $PRDMON[1], false, $yd["yrname"]);
	$tprds = finMonList("tprd", PRD_DB, false, $yd["yrname"]);

	db_connect();

	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' ORDER BY cusnum ASC";
	$cusRslt = db_exec($sql) or errDie("Could not retrieve Customers Information from the Database.",SELF);

	if(pg_numrows($cusRslt) < 1){
		return "<li class='err'> There are no Customers in Cubit.</li>";
	}
	$custs = "<select name='cusnums[]' multiple size='10'>";
	while($cus = pg_fetch_array($cusRslt)){
		$custs .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
	}
	$custs .= "</select>";

	$slctacc = "
		<p>
		<h3>Debtors Ledger</h3>
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
				<td valign='top'>Customers</td>
				<td><input type='radio' name='accnt' value='slct' checked='yes'>Selected Accounts | <input type='radio' name='accnt' value='all'>All Accounts</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Select Customer(s)</td>
				<td>$custs</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select period</td>
				<td>$fprds to $tprds</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		</table>";
	return $slctacc;

}



# View all transaction for the ledger
function viewtran($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fprd, "string", 1, 14, "Invalid from period number.");
	$v->isOk ($tprd, "string", 1, 14, "Invalid to period number.");
	$v->isOk ($year, "string", 1, 10, "Invalid year.");

	if(isset($accnt)){
		if($accnt == 'slct'){
			if(isset($cusnums)){
				foreach($cusnums as $key => $cusnum){
					$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer number.");
				}
			}else{
				return "<li class='err'>Please select at least one Debtor.</li>".slctacc($_POST);
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
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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
			return "<li calss='err'> There are no customers yet in Cubit.</li>";
		}
	}

	$prds = array();
	if ($tprd < $fprd) {
		for ($i = $fprd; $i <= 12; ++$i) {
			$prds[] = $i;
		}
		
		for ($i = 1; $i <= $tprd; ++$i) {
			$prds[] = $i;
		}
	} else {
		for ($i = $fprd; $i <= $tprd; ++$i) {
			$prds[] = $i;
		}
	}
	
	db_conn('core');

	$Sl = "SELECT * FROM year WHERE yrdb='$year'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$yd = pg_fetch_array($Ri);
	$hide = "";
	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$trans = "";
	foreach($cusnums as $key => $cusnum){
		$cusRs = get("cubit", "cusname, surname, accno, balance", "customers", "cusnum", $cusnum);
		$cus = pg_fetch_array($cusRs);

		$trans .= "
			<tr>
				<td colspan='8' align='center'><h3>$cus[surname]</h3></td>
			</tr>";
		$hide .= "<input type='hidden' name='cusnums[]' value='$cusnum'>";

		foreach ($prds as $prd) {
			$prdname = prdname($prd);
			$trans .= "
				<tr>
					<th colspan='8'>$prdname</th>
				</tr>
				<tr>
					<th>$sp</th>
					<th>Date</th>
					<th>Reference</th>
					<th>Description</th>
					<th>Debit</th>
					<th>Credit</th>
					<th>Balance</th>
					<th>Contra Acc</th>
				</tr>";

			$idRs = get($yd['yrname']."_audit", "min(id)", $prdname."_custledger", "cusnum", $cusnum);
			$id = pg_fetch_array($idRs);
	
			if($id['min'] <> 0){
				$balRs = get($yd['yrname']."_audit", "(cbalance-credit) AS cbalance,(dbalance-debit) AS dbalance", $prdname."_custledger", "id", $id['min']);
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
	
			$balance = sprint($bal['dbalance']-$bal['cbalance']);

			$trans .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan=8><b>$cus[accno] - $cus[cusname] $cus[surname]</b></td>
				</tr>";
			$trans .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan=2>&nbsp;</td>
					<td>Br/Forwd</td>
					<td>Brought Forward</td>
					<td align='right'>".sprint($bal["debit"])."</td>
					<td align='right'>".sprint($bal["credit"])."</td>
					<td align='right'>".sprint($balance)."</td>
					<td>&nbsp;</td>
				</tr>";
	
			# --> Transaction reading comes here <--- #
			$dbal['debit'] = 0;
			$dbal['credit'] = 0;
	
			$tranRs = get($yd['yrname']."_audit", "*", $prdname."_custledger", "cusnum", $cusnum,"ORDER BY id");
			while($tran = pg_fetch_array($tranRs)){
				$caccRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $tran['contra']);
				$cacc = pg_fetch_array($caccRs);
	
				$dbal['debit'] += $tran['debit'];
				$dbal['credit'] += $tran['credit'];
	
				$balance = sprint($tran['dbalance'] - $tran['cbalance']);
	
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
						<td align='right'>$balance</td>
						<td>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td>
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
					<td colspan='2'><br></td>
					<td>A/C Total</td>
					<td>Total for period $prdname to Date :</td>
					<td align='right'>$dbal[debit]</td>
					<td align='right'>$dbal[credit]</td>
					<td align='right'></td>
					<td> </td>
				</tr>";
			$trans .= "<tr><td colspan='8'><br></td></tr>";
		}
	}

	$view = "
		<center>
		<h3>Debtors Ledger</h3>
		<form action='../xls/cust-ledger-audit-xls.php' method='POST'>
			<input type='hidden' name='key' value='viewtran'>
			<input type='hidden' name='accnt' value='$accnt'>
			<input type='hidden' name='year' value='$year'>
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='accnt' value='$accnt'>
			<input type='hidden' name='fprd' value='$fprd'>
			<input type='hidden' name='tprd' value='$tprd'>
			$hide
		<table ".TMPL_tblDflts." width='75%'>
			$trans
			<tr>
				<td colspan='8' align='center'><input type='submit' value='Export to Spreadsheet'></td>
			</tr>
		</form>
		</table>";
	return $view;

}


?>