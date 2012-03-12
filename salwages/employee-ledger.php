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
require("emp-functions.php");

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

function slctacc() {
	extract($_REQUEST);

	if (!isset($prd_f)) {
		$prd_f = $prd_t = PRD_DB;
	}

	$prds_f = finMonList("prd_f", $prd_f);
	$prds_t = finMonList("prd_t", $prd_t);

	db_connect();
	$sql = "SELECT * FROM employees WHERE div = '".USER_DIV."' ORDER BY sname,fnames ASC";
	$supRslt = db_exec($sql) or errDie("Could not retrieve employees Information from the Database.",SELF);

	if(pg_numrows($supRslt) < 1){
		return "<li class=err> There are no employees in Cubit.";
	}
	$supts = "<select name='supids[]' multiple size=10>";
	while($sup = pg_fetch_array($supRslt)){
		$supts .= "<option value='$sup[empnum]'>$sup[sname],$sup[fnames]</option>";
	}
	$supts .= "</select>";

	$slctacc = "
	<p>
	<h3>Employee Ledger</h3>
	<h4>Select Options</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method='post'>
	<input type='hidden' name='key' value='viewtran' />
	<tr>
		<th>Field</th>
		<th>Value</th>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td valign='top'></td>
		<td><input type='radio' name='accnt' value='slct' checked='yes'>Selected Accounts | <input type=radio name=accnt value=all>All Accounts</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td valign='top'>Select Employees(s)</td>
		<td>$supts</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Select period</td>
		<td>$prds_f to $prds_t</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Order By</td>
		<td>Transaction Date<input type=radio name=t checked value='t'>System Date<input type=radio name=t value='s'></td>
	</tr>
	".TBL_BR."
	<tr>
		<td></td>
		<td align='right'><input type=submit value='Continue &raquo;'></td>
	</tr>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $slctacc;
}

# View all transaction for the ledger
function viewtran($_POST) {
	extract($_POST);
	
	global $PRDMON, $MONPRD;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd_f, "num", 1, 2, "Invalid From Period.");
	$v->isOk ($prd_t, "num", 1, 2, "Invalid To Period.");

	if(isset($accnt)){
		if($accnt == 'slct'){
			if(isset($supids)){
				foreach($supids as $key => $supid){
					$v->isOk ($supid, "num", 1, 20, "Invalid Supplier number.");
				}
			}else{
				return "<li class=err>Please select at least one Employee.</li>".slctacc();
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
		$sql = "SELECT empnum FROM employees WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);
		if(pg_num_rows($rs) > 0){
			while($ac = pg_fetch_array($rs)){
				$supids[] = $ac['empnum'];
			}
		}else{
			return "<li calss=err> There are no employees yet in Cubit.";
		}
	}

	$prds = array();
	if ($prd_f > $prd_t) {
		for ($i = $prd_f; $i <= 12; ++$i) {
			$prds[] = $i;
		}

		for ($i = 1; $i <= $prd_t; ++$i) {
			$prds[] = $i;
		}
	} else {
		for ($i = $prd_f; $i <= $prd_t; ++$i) {
			$prds[] = $i;
		}
	}
	
	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

	# Period name
	$hide="";

	$trans = "";
	foreach($supids as $key => $supid){
		$supRs = get("cubit", "empnum,sname,fnames, balance", "employees", "empnum", $supid);
		$sup = pg_fetch_array($supRs);

		$trans .= "
		<tr>
			<td align='center' colspan='8'><h2>$sup[sname], $sup[fnames]</h2></td>
		</tr>";
		$hide .= "<input type=hidden name=supids[] value='$supid'>";

		foreach ($prds as $prd) {
			$idRs = get($prd, "min(id)", "empledger", "empid", $supid);
			$id = pg_fetch_array($idRs);

			if($id['min'] <> 0){
				$balRs = get($prd, "(cbalance-credit) AS cbalance,(dbalance-debit) AS dbalance", "empledger", "id", $id['min']);
				$bal = pg_fetch_array($balRs);
				$bal['cbalance'] += 0;
				$bal['dbalance'] += 0;
			}else{
				$sql = array();
				for ($i = $MONPRD[$prd] - 1; $i >= 1; --$i) {
					$pprdname = getMonthName($PRDMON[$i]);

					$sql[] = "SELECT id,cbalance,dbalance
							FROM \"$i\".empledger
							WHERE empid='$supid'";
				}

				if (count($sql) > 0) {
					$sql = "SELECT * FROM (".implode(" UNION ", $sql).") AS sl
							ORDER BY id DESC
							LIMIT 1";
					$balRs = db_exec($sql);
					$bal = pg_fetch_array($balRs);
				}
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

			$balance=sprint($bal['cbalance']-$bal['dbalance']);

			// make the date of the last day of the previous prd
			$bbf_date = date("t-M-Y", mktime(0, 0, 0, $prd - 1, 1, getYearOfEmpMon($prd - 1)));

			$c = 0;
			$trans .= "
			<tr bgcolor='".bgcolor($c)."'>
				<th colspan='8'>".getMonthName($prd)." ".getYearOfEmpMon($prd)."</th>
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
			</tr>
			<tr bgcolor='".bgcolor($c)."'>
				<td colspan='2' align='right'>$bbf_date</td>
				<td>Br/Forwd</td>
				<td>Brought Forward</td>
				<td align=right>$bal[debit]</td>
				<td align=right>$bal[credit]</td>
				<td align=right>$balance</td>
				<td> </td>
			</tr>";

			# --> Transaction reading comes here <--- #
			$dbal['debit'] = 0;
			$dbal['credit'] = 0;

			if($t=="s") {
				$tranRs = get($prd, "*", "empledger", "empid", $supid,"ORDER BY id");
			} else  {
				$tranRs = get($prd, "*", "empledger", "empid", $supid,"ORDER BY edate,id");
			}
			while($tran = pg_fetch_array($tranRs)){
				$caccRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $tran['contra']);
				$cacc = pg_fetch_array($caccRs);

				$tran['debit']=sprint($tran['debit']);
				$tran['credit']=sprint($tran['credit']);

				$dbal['debit'] += $tran['debit'];
				$dbal['credit'] += $tran['credit'];

				if($t=="s") {

					$cbalance = sprint($tran['cbalance'] - $tran['dbalance']);

				} else {

					$cbalance = sprint(($dbal['credit']+$bal['credit']) - ($dbal['debit']+$bal['debit']));

				}

				if($t=="s") {
					$tran['edate']=$tran['sdate'];
				}

				# Format date
				$tran['edate'] = explode("-", $tran['edate']);
				$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];

				$trans .= "
				<tr bgcolor='".bgcolor($c)."'>
					<td>&nbsp;</td>
					<td>$tran[edate]</td>
					<td>$tran[ref]</td>
					<td>$tran[des]</td>
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
			<tr bgcolor='".bgcolor($c)."'>
				<td colspan='2'>&nbsp;</td>
				<td>A/C Total</td>
				<td>Total for period ".prdname($prd).":</td>
				<td align='right'>$dbal[debit]</td>
				<td align='right'>$dbal[credit]</td>
				<td align='right'>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
			".TBL_BR;
		}
	}

	$view = "
	<center>
	<form action='../xls/emp-ledger-xls.php' method='POST'>
	<input type='hidden' name='key' value='viewtran'>
	<input type='hidden' name='accnt' value='$accnt'>
	<input type='hidden' name='prd' value='$prd'>
	<input type='hidden' name='prd_f' value='$prd_f'>
	<input type='hidden' name='prd_t' value='$prd_t'>
	<input type='hidden' name='accnt' value='$accnt'>
	<input type='hidden' name='t' value='$t'>
	$hide
	<h3>Employee Ledger</h3>
	<table ".TMPL_tblDflts.">
	$trans
	<tr><td colspan=8 align=center><input type=submit value='Export to Spreadsheet'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $view;
}
?>
