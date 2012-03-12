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
		case "spreadsheet":
			$OUTPUT = clean_html(viewtran($_POST));
			require_lib("xls");
			StreamXLS("ledgerprd", $OUTPUT);
			break;
		case "viewtran":
			if(isset($_POST['continue']))
				$OUTPUT = viewtran($_POST);
			else 
				$OUTPUT = slctacc();
			break;
		default:
			$OUTPUT = slctacc();
	}
} else {
	$OUTPUT = slctacc();
}

# Get templete
require("../template.php");




function slctacc($err = "")
{

	global $PRDMON, $_POST;
	extract($_POST);

	$fprd = finMonList("fprd", $PRDMON[1]);
	$tprd = finMonList("tprd", PRD_DB);

	$fields["acc_first"] = "topacc";
	extract($fields, EXTR_SKIP);

	if($acc_first == "topacc")
		$sortacc_first = "topacc, accnum";
	else 
		$sortacc_first = "accname";


	core_connect();
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY $sortacc_first ASC";
	$accRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);

	if(pg_numrows($accRslt) < 1){
		return "<li class='err'> There are no Accounts in Cubit.</li>";
	}
	$accs = "<select name='accids[]' multiple size='10'>";
	while($acc = pg_fetch_array($accRslt)){
		if($acc_first == "topacc"){
			$accs .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
		}else {
			$accs .= "<option value='$acc[accid]'>$acc[accname] - $acc[topacc]/$acc[accnum]</option>";
		}
	}
	$accs .= "</select>";

	if ($acc_first == "topacc") {
		$topacc = "checked";
		$accname = "";
	} else {
		$topacc = "";
		$accname = "checked";
	}

	$slctacc = "
		<script>
		acclist_height = -1;
		function updateList(obj) {
			a = getObject('acclist');
	
			if (obj.value != 'slct') {
				if (acclist_height == -1) {
					acclist_height = a.offsetHeight;
					a.style.height = 0;
					a.style.visibility = 'hidden';
				}
			} else if (acclist_height >= 0) {
				a.style.height = acclist_height;
				acclist_height = -1;
				a.style.visibility = 'visible';
			}
		}
		</script>
		<h3>Period Range General Ledger</h3>
		<h4>Select Options</h4>
		$err
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='viewtran' />
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Accounts</td>
				<td nowrap>
					<input type='radio' onClick='updateList(this);' name='accnt' value='slct' checked='yes' />Selected Accounts<b> | </b>
					<input type='radio' onClick='updateList(this);' name='accnt' value='allactive' />All Active Accounts<b> | </b>
					<input type='radio' onClick='updateList(this);' name='accnt' value='all' />All Accounts
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Sort By</td>
				<td>
					<input type='radio' name='acc_first' onClick='document.form1.submit();' value='topacc' $topacc />Account Number - Account Name<br />
					<input type='radio' name='acc_first' onClick='document.form1.submit();' value='accname' $accname />Account Name - Account Number<br/>
				</td>
			</tr>
			<tr>
				<td colspan='2' style='margin: 0px; padding: 0px;'>
					<div id='acclist'>
					<table ".TMPL_tblDflts." width='100%' height='100%'>
					<tr bgcolor='".bgcolorg()."'>
						<td valign='top'>Select account(s)</td>
						<td>$accs</td>
					</tr>
					</table>
					</div>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Period Range :</td>
				<td valign='center' colspan='3'>$fprd To : $tprd</td>
			</tr>
			".TBL_BR."
			<tr>
				<td>&nbsp;</td>
				<td align='right'><input type='submit' name='continue' value='Continue &raquo;'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("index-reports.php", "Financials"),
			ql("index-reports-journal.php", "Current Year Details General Ledger Reports"),
			ql("../core/acc-new2.php", "Add New Account")
		);
	return $slctacc;

}


function viewtran($_POST)
{

	extract($_POST);

	global $MONPRD, $PRDMON;

	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accnt, "string", 1, 10, "Invalid Accounts Selection.");

	if($accnt == 'slct'){
		if(isset($accids)){
			foreach($accids as $accid){
				$v->isOk ($accid, "num", 1, 20, "Invalid Account number.");
			}
		}else{
			return "<li class='err'>Please select at least one account.</li>".slctacc();
		}
	}

	# display errors, if any
	if ($v->isError()) {
		$err = $v->genErrors();
		return slct($err);
	}





	if($accnt == 'all'){
		$accids = array();
		core_connect();
		$sql = "SELECT accid FROM accounts WHERE div = '".USER_DIV."'";
		$rs = db_exec($sql);

		while($ac = pg_fetch_array($rs)){
			$accids[] = $ac['accid'];
		}
	} else if ($accnt == "allactive") {
		$accids = array();
		$sql = "SELECT accid FROM core.trial_bal
				WHERE (debit!=0 OR credit!=0) AND div='".USER_DIV."'
					AND period>='".$MONPRD[$fprd]."' AND period<='".$MONPRD[$tprd]."'
				GROUP BY accid";
		$qry = new dbSql($sql);
		$qry->run();

		while ($macc_data = $qry->fetch_array()) {
			$accids[] = $macc_data["accid"];
		}
	}
	
	if ($key == "spreadsheet") {
		$pure = true;
	} else {
		$pure = false;
	}

	# Get all Closed Periods
	db_conn("audit");
	// $sql = "SELECT * FROM closedprd";
	// $clsRs = db_exec($sql) or errDie("Could not get closed periods from audit DB",SELF);
	$trans = "";
	$hide = "";
	//while($cls = pg_fetch_array($clsRs)){
	foreach($accids as $key => $accid){
		$accRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $accid);
		$acc = pg_fetch_array($accRs);

		$sql = "SELECT debit,credit FROM core.trial_bal WHERE accid='$accid' AND month='$tprd'";
		$qry = new dbSql($sql);
		$qry->run();
		$tb = $qry->fetch_array();

		$tbbal = $tb["debit"] - $tb["credit"];

		$hide .= "<input type='hidden' name='accids[]' value='$acc[accid]'>";

		$trans .= "
			<tr>
				<th>&nbsp;</th>
				<th>Date</th>
				<th>Reference</th>
				<th>Description</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Balance</th>
				<th>Contra Acc</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='8'><b>$acc[topacc]/$acc[accnum] - $acc[accname]</b></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4' align='right'><b>Balance at end of ".getMonthName($tprd)."</b></td>
				<td align='right'><b>".money($tb["debit"])."</b></td>
				<td align='right'><b>".money($tb["credit"])."</b></td>
				<td align='right' nowrap='t'><b>".($tbbal > 0 ? money($tbbal)." DT" : money(-$tbbal)." CT")."</b></td>
				<td>&nbsp;</td>
			</tr>";

		$cp = $fprd;
		$fs = 0;
		
		if($fprd == ($tprd+1)) {
			$f = true;
		} else {
			$f = false;
		}

		while($cp != ($tprd+1) || $f) {
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

			if(isset($t)) unset($t);

			# Get balances
			$idRs = get($prd, "max(id), min(id)", "ledger", "acc", $accid);
			$id = pg_fetch_array($idRs);
			if($id['min'] <> 0){
				$balRs = get($prd, "(cbalance-credit) as cbalance,(dbalance-debit) as dbalance", "ledger", "id", $id['min']);
				$bal = pg_fetch_array($balRs);
				$cbalRs = get($prd, "cbalance,dbalance", "ledger", "id", $id['max']);
				$cbal = pg_fetch_array($cbalRs);
			}else{
				if (!isset($t)) {
					$trans .= "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='8' align='center'><li> There are no transactions in this period.</td>
						</tr>";
				}

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
				$balance = "0.00";
				$fl = "";
			}

			$balance = sprint($balance);

			// calculate which year the current period is in
			$prd_y = getFinYear() - 1;
			if ($prd < $PRDMON[1]) {
				++$prd_y;
			}

			// make the date of the last day of the previous prd
			$bbf_date = date("t-M-Y", mktime(0, 0, 0, $prd - 1, 1, $prd_y));

			$trans .= "
				<tr bgcolor='".bgcolorg()."'>
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

				$trans .= "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='2'>$tran[edate]</td>
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
					<td colspan='2'><br></td>
					<td>A/C Total</td>
					<td>Total for period $prdname:</td>
					<td align='right'>$dbal[debit]</td>
					<td align='right'>$dbal[credit]</td>
					<td align='right'></td>
					<td></td>
				</tr>";
		}

		$trans .= "<tr><td colspan='8'><br></td></tr>";
	}

	$fprdname = prdname($fprd);
	$tprdname = prdname($tprd);

	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$view = "";
	
	if (!$pure) {
		$view .= "
			<center>
			<h3>Period Range General Ledger</h3>
			<h4>$fprdname - $tprdname</h4>";
	}
	
	$view .= "<table ".TMPL_tblDflts." width='90%'>";
	
	if (!$pure) {
		$view .= "
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='spreadsheet'>
				<input type='hidden' name='fprd' value='$fprd'>
				<input type='hidden' name='tprd' value='$tprd'>
				<input type='hidden' name='prd' value='$prd'>
				<input type='hidden' name='accnt' value='$accnt'>
				$hide
				<tr>
					<td colspan='8' align='center'><input type='submit' value='Export to Spreadsheet'></td>
				</tr>
				".TBL_BR;
	}

	$view .= $trans;

	if (!$pure) {
		$view .= "
				<tr>
					<td colspan='8'>&nbsp;</td>
				</tr>
				<tr>
					<td colspan='8' align='center'><input type='submit' value='Export to Spreadsheet'></td>
				</tr>
			<table>
			</form>"
			.mkQuickLinks(
				ql("index-reports.php", "Financials"),
				ql("index-reports-journal.php", "Current Year Details General Ledger Reports"),
				ql("../core/acc-new2.php", "Add New Account")
			);
	}
	return $view;

}


?>