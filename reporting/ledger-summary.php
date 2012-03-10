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
		case "export":
			$OUTPUT = export();
			break;
		default:
			$OUTPUT = slctacc();
	}
} else {
	$OUTPUT = slctacc();
}

require("../template.php");




function slctacc($err = "")
{

	global $PRDMON;

	core_connect();

	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);

	if(pg_numrows($accRslt) < 1){
		return "<li class='err'> There are no Accounts in Cubit.</li>";
	}
	$accs = "<select name='accids[]' multiple size='10'>";
	while($acc = pg_fetch_array($accRslt)){
		$accs .= "<option value='$acc[accid]'>$acc[accname]</option>";
	}
	$accs .= "</select>";

	#make the from/to period dropdowns ...
	$fprd = finMonList("fprd", $PRDMON[1]);
	$tprd = finMonList("tprd", PRD_DB);


	db_conn ('core');

	#get list of previous years ...
	$fin_year_drop = "<select name='fin_year'>";
	$fin_year_drop .= "<option value='0'>Current</option>";
	$get_years = "SELECT * FROM year WHERE closed = 'y'";
	$run_years = db_exec($get_years) or errDie ("Unable to get previous year information");
	if (pg_numrows($run_years) < 1){
		#no previous years ...
	}else {
		while ($parr = pg_fetch_array ($run_years)){
			$fin_year_drop .= "<option value='$parr[yrname]'>$parr[yrname]</option>";
		}
	}
	$fin_year_drop .= "</select>";


//			<tr bgcolor='".bgcolorg()."'>
//				<td>Financial Year</td>
//				<td>$fin_year_drop</td>
//			</tr>


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

		<h3>General Ledger Report</h3>
		<h4>Select Options</h4>
		$err
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='viewtran' />
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>

			<tr bgcolor='".bgcolorg()."'>
				<td>Date Range</td>
				<td>$fprd TO $tprd</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Opening Balances</td>
				<td>
					<input type='radio' name='openbal' value='show' checked='yes' />Include Opening Balances<b> | </b>
					<input type='radio' name='openbal' value='none' />No Opening Balances
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Accounts</td>
				<td nowrap>
					<input type='radio' onClick='updateList(this);' name='accnt' value='slct' checked='yes' />Selected Accounts<b> | </b>
					<input type='radio' onClick='updateList(this);' name='accnt' value='allactive' />All Accounts with transactions<b> | </b>
					<input type='radio' onClick='updateList(this);' name='accnt' value='all' />All Accounts
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
			".TBL_BR."
			<tr>
				<td>&nbsp;</td>
				<td align='right'><input type='submit' value='Continue &raquo;' /></td>
			</tr>
		</table>"
		.mkQuickLinks(
			ql("index-reports.php", "Financials"),
			ql("index-reports-journal.php", "Current Year Details General Ledger Reports"),
			ql("../core/acc-new2.php", "Add New Account")
		);
	return $slctacc;

}




function viewtran($HTTP_POST_VARS)
{

	global $MONPRD, $PRDMON;
	extract($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accnt, "string", 1, 10, "Invalid Accounts Selection.");

	if($accnt == 'slct'){
		if (isset($accids)){
			foreach($accids as $key => $accid){
				$v->isOk ($accid, "num", 1, 20, "Invalid Account number.");
			}
		}else{
			return "<li class='err'>Please select at least one account.</li>".slctacc();
		}
	}

	if ($v->isError()) {
		$err = $v->genErrors();
		return $confirm;
	}





	if ($_POST["key"] == "export") {
		$pure = true;
	} else {
		$pure = false;
	}

	#get list of which accounts to show
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
			return "<li calss='err'> There are no accounts yet in Cubit.</li>";
		}
	} else if ($accnt == "allactive") {
		$accids = array();

//print "->$fin_year<-";
//		if ($fin_year != "0"){
//			$accsql = array ();
//			for ($x=1;$x<13;$x++){
//				$month = date ("F",mktime (0,0,0,$x,1,substr($fin_year,1)));
//				$accsql[] = "SELECT debit,credit FROM $month";
//			}
//			$sql = implode (" UNION ",$accsql);
//			db_conn($fin_year."_audit");
//			$run_sql = db_exec($sql) or errDie ("Unable to get previous year information.");
//			while ($rarr = pg_fetch_array ($run_sql)){
//				$accids[] = $rarr['debit'];
//				$accids[] = $rarr['credit'];
//				$accids = array_unique($accids);
//			}
//
//		}else {
			$sql = "SELECT accid FROM core.trial_bal
					WHERE (debit!=0 OR credit!=0) AND div='".USER_DIV."'
						AND period<='".$MONPRD[PRD_DB]."'
					GROUP BY accid";
			$qry = new dbSql($sql);
			$qry->run();

			while ($macc_data = $qry->fetch_array()) {
				$accids[] = $macc_data["accid"];
			}
//		}
	}

	$hide = "";

	$trans = "";
	foreach($accids as $key => $accid) {
		$accRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $accid);
		$acc = pg_fetch_array($accRs);

		$tran_flag = FALSE;
		$the_trans = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='8'><b>$acc[topacc]/$acc[accnum] - $acc[accname]</b></td>
			</tr>";

		db_conn("audit");


		#go through SELECTED periods ...
		$cp = $fprd;
		$fs = 0;
		if($fprd == ($tprd+1)) {
			$f = true;
		} else {
			$f = false;
		}

		$balRs = get("core", "credit as cbalance, debit as dbalance", "trial_bal", "accid", $accid);
		$bal = pg_fetch_array($balRs);

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

		// calculate which year the current period is in
		$prd_y = getFinYear() - 1;
//		if ($prd < $PRDMON[1]) {
//			++$prd_y;
//		}

//"t-M-Y"
		// make the date of the last day of the previous prd
		$bbf_date = date("t F Y", mktime(0, 0, 0, $PRDMON[1]- 1, 1, $prd_y));

			if ($openbal == "show" AND (($bal['dbalance'] > 0) OR ($bal['cbalance'] > 0))){
				$tran_flag = TRUE;
				$the_trans .= "
					<tr bgcolor='".bgcolorg()."'>
						<td nowrap colspan='2' align='right'>$bbf_date</td>
						<td>Br/Forwd</td><td>Brought Forward</td>
						<td align='right'>$bal[dbalance]</td>
						<td align='right'>$bal[cbalance]</td>
						<td align='right'>$balance $fl</td>
						<td>&nbsp;</td>
					</tr>";
			}

		while($cp != ($tprd+1) || $f) {

			$prd = $cp;
			$cp++;
			$fs++;
			if($cp == 13) 
				$cp = 1;
			if($fs > 13) 
				break;
			$f = false;

			# Period name
			$prdname = prdname($prd);

			$hide = "";
			if(isset($t)) unset($t);

			# Get balances
			$idRs = get($prd, "max(id), min(id)", "ledger", "acc", $accid);
			$id = pg_fetch_array($idRs);

			if($id['min'] <> 0){
				#at least 1 entry found for this period ...
				$balRs = get($prd, "(cbalance-credit) as cbalance,(dbalance-debit) as dbalance", "ledger", "id", $id['min']);
				$bal = pg_fetch_array($balRs);

				$cbalRs = get($prd, "cbalance,dbalance", "ledger", "id", $id['max']);
				$cbal = pg_fetch_array($cbalRs);
			}else{
				if(!isset($t)){
//					$the_trans .= "
//						<tr bgcolor='".bgcolorg()."'>
//							<td colspan='8' align='center'><li> There are no transactions in this period.</td>
//						</tr>";
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

			# --> Transaction reding comes here <--- #
			$dbal['debit'] = 0;
			$dbal['credit'] = 0;

			#go through all the transactions
			$tranRs = get($prd, "*", "ledger", "acc", $accid, "ORDER BY id");
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

				#only show the transaction if it actually DOES something ...
				if ($tran['debit'] != 0 OR $tran['credit'] != 0){

					$tran_flag = TRUE;

					if (strlen($tran['edate']) == 10){
						$darr = explode ("-", $tran['edate']);
						$tran['edate'] = date ("t F Y",mktime (0,0,0,$darr[1],$darr[0],$darr[2]));
					}

					$the_trans .= "
						<tr bgcolor='".bgcolorg()."'>
							<td><br></td>
							<td nowrap>$tran[edate]</td>
							<td>$tran[eref]</td>
							<td>$tran[descript]</td>
							<td nowrap align='right'>$tran[debit]</td>
							<td nowrap align='right'>$tran[credit]</td>
							<td nowrap align='right'>$cbalance $cfl</td>
							<td nowrap>$tran[ctopacc]/$tran[caccnum] - $tran[caccname]</td>
						</tr>";
				}
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

//			$trans .= "
//				<tr bgcolor='".bgcolorg()."'>
//					<td colspan='2'><br></td>
//					<td>A/C Total</td>
//					<td>Total for period $prdname to Date :</td>
//					<td align='right'>$dbal[debit]</td>
//					<td align='right'>$dbal[credit]</td>
//					<td align='right'></td>
//					<td> </td>
//				</tr>";
			if ($tran_flag){
				$trans .= $the_trans;
				$the_trans = "";
			}
		}
		if ($tran_flag)
			$trans .= "<tr><td colspan='8'><br></td></tr>";
	}

	$OUT = "";

	if (!$pure) {
		$OUT .= "<center>";
	}

	$OUT .= "
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='8' align='center'><h3>General Ledger Report</h3></td>
			</tr>";

	if (!$pure) {
		$OUT .= "
		<tr>
			<form action='".SELF."' method='post'>
				<input type='hidden' name='key' value='export' />
				<input type='hidden' name='prd' value='$prd' />
				<input type='hidden' name='accnt' value='$accnt' />
				<input type='hidden' name='fprd' value='$fprd' />
				<input type='hidden' name='tprd' value='$tprd' />
				<input type='hidden' name='openbal' value='$openbal' />
				".array2form($accids, "accids")."
				<td colspan='8' align='center'>
					<input type='submit' value='Export to Spreadsheet'>
				</td>
			</form>
		</tr>
		".TBL_BR;
	}

	$OUT .= "
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
	$trans
	</table>";

	if (!$pure) {
		$OUT .= mkQuickLinks(
			ql("index-reports.php", "Financials"),
			ql("index-reports-journal.php", "Current Year Details General Ledger Reports"),
			ql("../core/acc-new2.php", "Add New Account")
		);

		$OUT .= "
		</center>";
	}
	return $OUT;

}




function export()
{

	$OUT = clean_html(viewtran($_POST));

	require_lib("xls");
	StreamXLS("year_review_ledger", $OUT);

}



?>
