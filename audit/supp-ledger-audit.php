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
		case "viewtran":
			$OUTPUT = viewtran($_POST);
			break;
		default:
			$OUTPUT = slctacc();
	}
} else {
	$OUTPUT = select_year();
}

# Get templete
require("../template.php");



function select_year()
{

	db_conn('core');

	$Sl = "SELECT * FROM year WHERE closed='y' ORDER BY yrname";
	$Ri = db_exec($Sl) or errDie("Unable to get data");

	if(pg_num_rows($Ri)<1) {
		return "
			<li class='err'>There are no closed years.</li>
				<p>
				<table ".TMPL_tblDflts." width='25%'>
					<tr><td><br></td></tr>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='datacell'>
						<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
					</tr>
					<tr class='datacell'>
						<td align='center'><a href='index-reports.php'>Financials</a></td>
					</tr>
					<tr class='datacell'>
						<td align='center'><a href='index-reports-debtcred.php'>Debtors & Creditors Reports</a></td>
					</tr>
					<tr class='datacell'>
						<td align='center'><a href='../main.php'>Main Menu</td>
					</tr>
				</table>";
	}

	$years = "<select name='year'>";
	while($data=pg_fetch_array($Ri)) {
	    $years .= "<option value='$data[yrdb]'>$data[yrname]</option>";
	}
	$years .= "</select>";

	$out = "
		<h3>Creditor Ledger</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='slctacc' />
			<tr>
				<th>Select Year</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$years</td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='right'><input type='submit' value='Next &raquo;' /></td>
			</tr>
		</table>
		</form>";
	return $out;

}



function slctacc()
{

	extract($_REQUEST);

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

	$sql = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' ORDER BY supid ASC";
	$supRslt = db_exec($sql) or errDie("Could not retrieve suppliers Information from the Database.",SELF);

	if(pg_numrows($supRslt) < 1){
		return "<li class='err'> There are no suppliers in Cubit.</li>";
	}
	$supts = "<select name='supids[]' multiple size='10'>";
	while($sup = pg_fetch_array($supRslt)){
		$supts .= "<option value='$sup[supid]'>$sup[supname]</option>";
	}
	$supts .= "</select>";


	$slctacc = "
		<p>
		<h3>Creditors Ledger</h3>
		<h4>Select Options</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='viewtran'>
			<input type='hidden' name='year' value='$year' />
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='top'></td>
				<td><input type='radio' name='accnt' value='slct' checked='yes'>Selected Accounts | <input type='radio' name='accnt' value='all'>All Accounts</td>
			</tr>
			<tr class='".bg_class()."'>
				<td valign='top'>Select Supplier(s)</td>
				<td>$supts</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select period</td>
				<td>$fprds to $tprds</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Order By</td>
				<td>Transaction Date<input type='radio' name='t' checked value='t'>System Date<input type='radio' name='t' value='s'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='25%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports.php'>Financials</a></td></tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports-debtcred.php'>Debtors & Creditors Reports</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../main.php'>Main Menu</td>
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
	$v->isOk ($year, "string", 3, 4, "Invalid year.");

	if(isset($accnt)){
		if($accnt == 'slct'){
			if(isset($supids)){
				foreach($supids as $key => $supid){
					$v->isOk ($supid, "num", 1, 20, "Invalid Supplier number.");
				}
			}else{
				return "<li class='err'>Please select at least one Creditor.</li>".slctacc();
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
		$sql = "SELECT supid FROM suppliers WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);
		if(pg_num_rows($rs) > 0){
			while($ac = pg_fetch_array($rs)){
				$supids[] = $ac['supid'];
			}
		}else{
			return "<li calss='err'> There are no suppliers yet in Cubit.</li>";
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

	$Sl = "SELECT yrname FROM year WHERE yrdb='$year'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$yrname = pg_fetch_result($Ri, 0);
	$auditdb = "${yrname}_audit";

	# Period name
	$hide = "";
	global $MONPRD, $PRDMON;
	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$trans = "";
	foreach($supids as $key => $supid){
		$supRs = get("cubit", "supname, supno, balance", "suppliers", "supid", $supid);
		$sup = pg_fetch_array($supRs);
		
		$trans .= "
			<tr>
				<td colspan='8' align='center'><h3>$sup[supname]</h3></td>
			</tr>";
		$hide .= "<input type='hidden' name='supids[]' value='$supid'>";

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

			$idRs = get($auditdb, "min(id)", "${prdname}_suppledger", "supid", $supid);
			$id = pg_fetch_array($idRs);

			if($id['min'] <> 0){
				$balRs = get($auditdb, "(cbalance-credit) AS cbalance,(dbalance-debit) AS dbalance", "${prdname}_suppledger", "id", $id['min']);
				$bal = pg_fetch_array($balRs);
				$bal['cbalance'] += 0;
				$bal['dbalance'] += 0;
			} else {
				$sql = array();
				for ($i = $MONPRD[$prd] - 1; $i >= 1; --$i) {
					$pprdname = getMonthName($PRDMON[$i]);
	
					$sql[] = "SELECT id,cbalance,dbalance FROM $auditdb.${pprdname}_suppledger WHERE supid='$supid'";
				}

				if (count($sql) > 0) {
					$sql = "SELECT * FROM (".implode(" UNION ", $sql).") AS sl ORDER BY id DESC LIMIT 1";
					$balRs = db_exec($sql);
					$bal = pg_fetch_array($balRs);
				}
			}

			if(!isset($bal['dbalance']))
				$bal['dbalance'] = "";

			if(!isset($bal['cbalance']))
				$bal['cbalance'] = "";

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
	
			$balance = sprint($bal['cbalance']-$bal['dbalance']);
	
			$trans .= "
				<tr class='".bg_class()."'>
					<td colspan='8'><b>$sup[supno] - $sup[supname] </b></td>
				</tr>";
	
// // 			$bbf_date = date("t-M-Y", mktime(0, 0, 0, $prd - 1, 1, getYearOfFinMon($prd)));
// $bbf_date	
			$trans .= "
				<tr class='".bg_class()."'>
					<td colspan='2' align='right'>&nbsp;</td>
					<td>Br/Forwd</td>
					<td>Brought Forward</td>
					<td align='right'>".sprint($bal['debit'])."</td>
					<td align='right'>".sprint($bal['credit'])."</td>
					<td align='right'>".sprint($balance)."</td>
					<td>&nbsp;</td>
				</tr>";
	
			# --> Transaction reading comes here <--- #
			$dbal['debit'] = 0;
			$dbal['credit'] = 0;
	
			if($t=="s") {
				$tranRs = get($auditdb, "*", "${prdname}_suppledger", "supid", $supid,"ORDER BY id");
			} else  {
				$tranRs = get($auditdb, "*", "${prdname}_suppledger", "supid", $supid,"ORDER BY edate,id");
			}
			while ($tran = pg_fetch_array($tranRs)) {
				$caccRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $tran['contra']);
				$cacc = pg_fetch_array($caccRs);
	
				$tran['debit']=sprint($tran['debit']);
				$tran['credit']=sprint($tran['credit']);
	
				$dbal['debit'] += $tran['debit'];
				$dbal['credit'] += $tran['credit'];
	
				if ($t=="s") {
					$cbalance = sprint($tran['cbalance'] - $tran['dbalance']);
				} else {
					$cbalance = sprint(($dbal['credit']+$bal['credit']) - ($dbal['debit']+$bal['debit']));
				}
	
				if ($t == "s") {
					$tran['edate']=$tran['sdate'];
				}
	
				# Format date
				$tran['edate'] = explode("-", $tran['edate']);
				$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];
	
				$trans .= "
					<tr class='".bg_class()."'>
						<td>&nbsp;</td>
						<td>$tran[edate]</td>
						<td>$tran[eref]</td>
						<td>$tran[descript]</td>
						<td align='right'>".sprint($tran['debit'])."</td>
						<td align='right'>".sprint($tran['credit'])."</td>
						<td align='right'>".sprint($cbalance)."</td>
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
					<td colspan='2'>&nbsp;</td>
					<td>A/C Total</td>
					<td>Total for period $prdname to Date :</td>
					<td align='right'>$dbal[debit]</td>
					<td align='right'>$dbal[credit]</td>
					<td align='right'>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td colspan='8'>&nbsp;</td>
				</tr>";
		}
	}

	$view = "
		<center>
		<form action='../xls/supp-ledger-xls.php' method='POST'>
			<input type='hidden' name='key' value='viewtran'>
			<input type='hidden' name='year' value='$year'>
			<input type='hidden' name='accnt' value='$accnt'>
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='accnt' value='$accnt'>
			<input type='hidden' name='t' value='$t'>
			<input type='hidden' name='fprd' value='$fprd'>
			<input type='hidden' name='tprd' value='$tprd'>
		$hide
		<h3>Creditors Ledger</h3>
		<table ".TMPL_tblDflts." width='75%'>
			$trans
			<tr>
				<td colspan='8' align='center'><input type='submit' value='Export to Spreadsheet'></td>
			</tr>
		</from>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='25%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports-debtcred.php'>Debtors & Creditors Reports</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	return $view;

}


?>