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
			$OUTPUT ="Invalid.";
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
		<h3>Employee Ledger</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='slctacc'>
			<tr>
				<th>Select Year</th>
			</tr>
			<tr class='".bg_class()."'>
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

	# from period
	$prds = finMonList("prd", PRD_DB, false, $yd["yrname"]);

	db_connect();

	$sql = "SELECT * FROM employees WHERE div = '".USER_DIV."' ORDER BY sname,fnames";
	$supRslt = db_exec($sql) or errDie("Could not retrieve suppliers Information from the Database.",SELF);

	if(pg_numrows($supRslt) < 1){
		return "<li class='err'> There are no employees in Cubit.";
	}
	$supts = "<select name='emps[]' multiple size='10'>";
	while($sup = pg_fetch_array($supRslt)){
		$supts .= "<option value='$sup[empnum]'>$sup[sname], $sup[fnames]</option>";
	}
	$supts .= "</select>";

	$slctacc = "
		<p>
		<h3>Employee Ledger</h3>
		<h4>Select Options</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='viewtran'>
			<input type='hidden' name='year' value='$year'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='top'>Employees</td>
				<td><input type='radio' name='accnt' value='slct' checked='yes'>Selected Accounts | <input type='radio' name='accnt' value='all'>All Accounts</td>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='top'>Select Employees(s)</td>
				<td>$supts</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select period</td>
				<td>$prds</td>
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

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd, "string", 1, 14, "Invalid Period number.");
	$v->isOk ($year, "string", 1, 10, "Invalid year.");

	if(isset($accnt)){
		if($accnt == 'slct'){
			if(isset($emps)){
				foreach($emps as $key => $emp){
					$v->isOk ($emp, "num", 1, 20, "Invalid employee number.");
				}
			}else{
				return "<li class='err'>Please select at least one Emloyee.</li>".slctacc($_POST);
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
		$supids = array();
		db_connect();
		$sql = "SELECT empnum FROM employees WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);
		if(pg_num_rows($rs) > 0){
			while($ac = pg_fetch_array($rs)){
				$emps[] = $ac['empnum'];
			}
		}else{
			return "<li calss='err'> There are no employees yet in Cubit.";
		}
	}

	# Period name
	$prdname = prdname($prd);
	$hide = "";

	db_conn('core');

	$Sl = "SELECT * FROM year WHERE yrdb='$year'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$yd = pg_fetch_array($Ri);

	$trans = "";
	foreach($emps as $key => $emp){
		$supRs = get("cubit", "sname,fnames, balance", "employees", "empnum", $emp);
		$sup = pg_fetch_array($supRs);

		$idRs = get($yd['yrname']."_audit", "min(id)", $prdname."_empledger", "empid", $emp);
		$id = pg_fetch_array($idRs);

		if($id['min'] <> 0){
			$balRs = get($yd['yrname']."_audit", "(cbalance-credit) AS cbalance,(dbalance-debit) AS dbalance", $prdname."_empledger", "id", $id['min']);
			$bal = pg_fetch_array($balRs);
			$bal['cbalance'] += 0;
			$bal['dbalance'] += 0;
		}else{
			$balRs = get("cubit", "balance", "employees", "empnum", $emp);
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

		$balance = sprint($bal['cbalance']-$bal['dbalance']);
		$hide .= "<input type='hidden' name='emps[]' value='$emp'>";

		$trans .= "
			<tr class='".bg_class()."'>
				<td colspan='8'><b>$sup[sname], $sup[fnames] </b></td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='2'><br></td>
				<td>Br/Forwd</td>
				<td>Brought Forward</td>
				<td align='right'>$bal[debit]</td>
				<td align='right'>$bal[credit]</td>
				<td align='right'>$balance</td>
				<td> </td>
			</tr>";

		# --> Transaction reading comes here <--- #
		$dbal['debit'] = 0;
		$dbal['credit'] = 0;

		$tranRs = get($yd['yrname']."_audit", "*", $prdname."_empledger", "empid", $emp,"ORDER BY id");
		while($tran = pg_fetch_array($tranRs)){
			$caccRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $tran['contra']);
			$cacc = pg_fetch_array($caccRs);

			$dbal['debit'] += $tran['debit'];
			$dbal['credit'] += $tran['credit'];

			$cbalance = sprint($tran['cbalance'] - $tran['dbalance']);

			# Format date
			$tran['edate'] = explode("-", $tran['edate']);
			$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];

			$trans .= "
				<tr class='".bg_class()."'>
					<td><br></td>
					<td>$tran[edate]</td>
					<td>$tran[ref]</td>
					<td>$tran[descript]</td>
					<td align='right'>$tran[debit]</td>
					<td align='right'>$tran[credit]</td>
					<td align='right'>$cbalance</td>
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
			<tr class='".bg_class()."'>
				<td colspan='2'><br></td>
				<td>A/C Total</td>
				<td>Total for period $prdname to Date :</td>
				<td align='right'>$dbal[debit]</td>
				<td align='right'>$dbal[credit]</td>
				<td align='right'></td>
				<td> </td>
			</tr>
			<tr><td colspan='8'><br></td></tr>";
	}

	//<tr><td colspan=8 align=center><input type=submit value='Export to Spreadsheet'></td></tr>
	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$view = "
		<center>
		<form action='../xls/supp-ledger-audit-xls.php' method='POST'>
			<input type='hidden' name='key' value='viewtran'>
			<input type='hidden' name='accnt' value='$accnt'>
			<input type='hidden' name='year' value='$year'>
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='accnt' value='$accnt'>
			$hide
		<h3>Employee Ledger</h3>
		<table ".TMPL_tblDflts." width='75%'>
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
		</form>
		</table>";
	return $view;

}


?>