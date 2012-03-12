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
			$OUTPUT = "Invalid";
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
		return "There are no closed years.";
	}

	$years = "<select name='year'>";
	while($data = pg_fetch_array($Ri)) {
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

	# from period
	$prds = "<select name='prd'>";
	db_conn(YR_DB);
	$sql = "SELECT * FROM info WHERE prdname !=''";
	$prdRslt = db_exec($sql);
	if(pg_numrows($prdRslt) < 1){
		return "<li class='err'>ERROR : There are no periods set for the current year.</li>";
	}
	while($prd = pg_fetch_array($prdRslt)){
		if($prd['prddb'] == PRD_DB){
			$sel = "selected";
		}else{
			$sel= "";
		}
		$prds .= "<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
	}
	$prds .= "</select>";

	db_connect();
	$sql = "SELECT * FROM stock WHERE div = '".USER_DIV."' ORDER BY stkdes ASC";
	$stkRslt = db_exec($sql) or errDie("Could not retrieve Stock Information from the Database.",SELF);

	if(pg_numrows($stkRslt) < 1){
		return "<li class='err'> There are no Stock Items in Cubit.</li>";
	}
	$stks = "<select name='stkids[]' multiple size='10'>";
	while($stk = pg_fetch_array($stkRslt)){
		$stks .= "<option value='$stk[stkid]'>$stk[stkcod] $stk[stkdes]</option>";
	}
	$stks .= "</select>";

	$slctacc = "
		<p>
		<h3>Inventory Ledger</h3>
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
				<td valign='top'>Stock Items</td>
				<td><input type='radio' name='accnt' value='slct' checked='yes'>Selected Items | <input type='radio' name='accnt' value='all'>All Items</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Select Stock Item(s)</td>
				<td>$stks</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select period</td>
				<td>$prds</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='center'></td>
				<td align='right'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $slctacc;

}



# View all transaction for the ledger
function viewtran($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd, "string", 1, 14, "Invalid Period number.");
	$v->isOk ($accnt, "string", 1, 5, "Invalid Accounts Selection.");
	$v->isOk ($year, "string", 1, 10, "Invalid year.");

	if($accnt == 'slct'){
		if(isset($stkids)){
			foreach($stkids as $key => $stkid){
				$v->isOk ($stkid, "num", 1, 20, "Invalid Stock code.");
			}
		}else{
			return "<li class='err'>ERROR : Please select at least one Stock Item.</li>".slctacc();
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
		$stkids = array();
		db_connect();
		$sql = "SELECT stkid FROM stock WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);
		if(pg_num_rows($rs) > 0){
			while($ac = pg_fetch_array($rs)){
				$stkids[] = $ac['stkid'];
			}
		}else{
			return "<li calss='err'> There are no Stock Items yet in Cubit.</li>";
		}
	}

	$hide = "";

	# Period name
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

	$trans = "";
	foreach($stkids as $key => $stkid){
		$stkRs = get("cubit", "*", "stock", "stkid", $stkid);
		$stk = pg_fetch_array($stkRs);

		$trans .= "
			<tr>
				<td colspan='8' align='center'><h3>$stk[stkcod] - $stk[stkdes]</h3></td>
			</tr>";
		$hide .= "<input type='hidden' name='stkids[]' value='$stkid'>";

		foreach ($prds as $prd) {

			# Period name
			$prdname = prdname($prd);

			# Get balances
			$idRs = get($prd, "max(id), min(id)", "stkledger", "yrdb='$year' AND stkid", $stkid);
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

			$get_yr = "SELECT yrname FROM core.year WHERE yrdb = '$year' LIMIT 1";
			$run_yr = db_exec ($get_yr) or errDie ("Unable to get year information.");
			if (pg_numrows ($run_yr) > 0){
				$yarr = pg_fetch_array ($run_yr);
				$tmpyear = substr ($yarr['yrname'],1);
			}else {
				$tmpyear = date ("Y") - 1;
			}

			$get_last_prdmap = "SELECT period FROM core.prdmap WHERE month = '12'";
			$run_last_prdmap = db_exec ($get_last_prdmap) or errDie ("Unable to get last month period");
			if (pg_numrows ($run_last_prdmap) > 0){
				$lastperiod = pg_fetch_result ($run_last_prdmap,0,0);
			}

			$get_current_prd = "SELECT period FROM core.prdmap WHERE month = '$prd' LIMIT 1";
			$run_current_prd = db_exec ($get_current_prd) or errDie ("Unable to get period information.");
			if (pg_fetch_array ($run_current_prd) > 0){
				$currentperiod = pg_fetch_result($run_current_prd,0,0);
				if ((int)$lastperiod >= (int)$currentperiod){
					$show_year = $tmpyear-1;
				}else {
					$show_year = $tmpyear;
				}
			}

			$trans .= "
				<tr>
					<td colspan='5'><b>($stk[stkcod]) $stk[stkdes]</b></td>
				</tr>";
			$trans .= "
				<tr>
					<td>$show_year-$prd-01<br></td>
					<td>Balance Brought Forward</td>
					<td align='right'>$bal[bqty]</td>
					<td align='right'>$balance </td>
					<td align='right'>$balance </td>
				</tr>";

			# --> transactio reding comes here <--- #
			$dbal['balance'] = 0;
			$dbal['bqty'] = 0;


			$tranRs = nget($prd, "*", "stkledger", "yrdb='$year' AND stkid", $stkid." ORDER BY id ASC");
			while($tran = pg_fetch_array($tranRs)){
				$dbal['balance'] += $tran['csamt'];
				$dbal['bqty'] += $tran['qty'];

				# sprinting
				$tran['csamt'] = sprint($tran['csamt']);
				$tran['balance'] = sprint($tran['balance']);

				# Format date
				$tran['edate'] = explode("-", $tran['edate']);
				$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];

				$trans .= "
					<tr>
						<td>$tran[edate]</td>
						<td>$tran[details]</td>
						<td>$tran[qty]</td>
						<td align='right'>$tran[csamt]</td>
						<td align='right'>$tran[balance]</td>
					</tr>";
			}
			$dbal['balance'] = sprint($dbal['balance']);

			$trans .= "
				<tr>
					<td><br></td>
					<td>Total for period $prdname to Date :</td>
					<td align='right'>$dbal[bqty]</td>
					<td align='right'>$dbal[balance] </td>
					<td align='right'>$dbal[balance] </td>
				</tr>";
			$trans .= "<tr><td colspan='5'><br></td></tr>";
		}
	}

	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$view = "
		<center>
		<h3>Inventory Ledger</h3>
		<table ".TMPL_tblDflts." width='75%'>
			<tr>
				<th>DATE</th>
				<th>DETAILS</th>
				<th>QTY</th>
				<th>COST AMOUNT</th>
				<th>BALANCE</th>
			</tr>
			$trans
		</table>";
	include("temp.xls.php");
	Stream("Ledger", $view);

}


?>