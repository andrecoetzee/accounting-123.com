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

require("../template.php");




function slctacc()
{

	global $_POST;
	extract($_POST);

	$fields["acc_first"] = "topacc";

	extract($fields, EXTR_SKIP);

	$prds = finMonList("prd", PRD_DB);

	if($acc_first == "topacc")
		$sortacc_first = "topacc, accnum";
	else 
		$sortacc_first = "accname";

	// Retrieve main accounts from Cubit.
	db_conn("core");
	$sql = "SELECT * FROM accounts WHERE div='".USER_DIV."' ORDER BY $sortacc_first";

	$macc_rslt = db_exec($sql) or errDie("Unable to retrieve main accounts from Cubit.");

	$accs = "<select id='accids' name='accids[]' multiple size='10'>";
	$i = 0;

	while ($macc_data = pg_fetch_array($macc_rslt)) {

		++$i;
		if ($acc_first == "accname") {
			$accs .= "<option value='$macc_data[accid]'>$macc_data[accname] - $macc_data[topacc]/$macc_data[accnum]</option>";
		} else {
			$accs .= "<option value='$macc_data[accid]'>$macc_data[topacc]/$macc_data[accnum] - $macc_data[accname]</option>";
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
	<p>
	<h3>General Ledger</h3>
	<table ".TMPL_tblDflts.">
	<form action='".SELF."' method='POST' name='form1'>
	<input type='hidden' name='key' value='viewtran' />
	<tr>
		<th colspan='2'>Options</th>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Select period</td>
		<td>$prds</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Order By</td>
		<td>
			Transaction Date<input type='radio' name='t' checked value='t'>
			System Date<input type='radio' name='t' value='s'>
		</td>
	</tr>
	".TBL_BR."
	<tr>
		<th colspan='2'>Account Options</th>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td valign='top'>Accounts</td>
		<td nowrap>
			<input type='radio' onClick='updateList(this);' name='accnt' value='slct' checked='yes' />Selected Accounts<b> | </b>
			<input type='radio' onClick='updateList(this);' name='accnt' value='allactive' />All Active Accounts<b> | </b>
			<input type='radio' onClick='updateList(this);' name='accnt' value='all' />All Accounts
		</td>
	</tr>
	<tr>
		<td colspan='2' style='margin: 0px; padding: 0px;'>
			<div id='acclist'>
			<table ".TMPL_tblDflts." width='100%' height='100%'>
				<tr id='acclist1' bgcolor='".bgcolorg()."'>
					<td>Account List Options</td>
					<td colspan='2'>
						<input type='radio' name='acc_first' onClick='document.form1.submit();' value='topacc' $topacc />Account Number - Account Name<br />
						<input type='radio' name='acc_first' onClick='document.form1.submit();' value='accname' $accname />Account Name - Account Number<br/>
					</td>
				</tr>
				<tr id='acclist2' bgcolor='".bgcolorg()."'>
					<td valign='top'>Select account(s)</td>
					<td>$accs</td>
				</tr>
			</table>
			</div>
		</td>
	</tr>
	".TBL_BR."
	<tr>
		<td></td>
		<td align='right'><input id='sdf' type='submit' name='continue' value='Continue &raquo;'></td>
	</tr>
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

	require_lib("validate");
	$v = new validate();
	$v->isOk($prd, "string", 1, 14, "Invalid Period number.");
	$v->isOk($accnt, "string", 1, 10, "Invalid Accounts Selection.");
	$v->isOk($acc_first, "string", 1, 20, "Invalid accounts display selection");

	if ($accnt == 'slct') {
		if (isset($accids)){
			foreach($accids as $key => $accid){
				$v->isOk ($accid, "num", 1, 20, "Invalid Account number.");
			}
		} else {
			return "<li class='err'>Please select at least one account.</li>".slctacc();
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

	// Was the 'All Accounts' option selected?
	if ($accnt == 'all') {
		$accids = array();

		// Retrieve the main accounts
		db_conn("core");
		$sql = "SELECT * FROM accounts WHERE div='".USER_DIV."' AND accnum='000' ORDER BY topacc ASC";
		$macc_rslt = db_exec($sql) or errDie("Unable to retrieve main accounts from Cubit.");

		while ($macc_data = pg_fetch_array($macc_rslt)) {
			// Retrieve sub accounts from Cubit
			$sql = "SELECT * FROM accounts WHERE div='".USER_DIV."' AND topacc='$macc_data[topacc]' AND accnum!='000' ORDER BY topacc ASC";
			$sacc_rslt = db_exec($sql) or errDie("Unable to retrieve sub accounts from Cubit.");

			// List the main accounts without any sub accounts
			if (!pg_num_rows($sacc_rslt)) {
				$accids[] = $macc_data["accid"];

			// List the sub accounts
			} else {
				while ($sacc_data = pg_fetch_array($sacc_rslt)) {
					$accids[] = $sacc_data["accid"];
				}
			}
		}
	} else if ($accnt == "allactive") {
		$accids = array();
		$sql = "SELECT accid FROM core.trial_bal
				WHERE (debit!=0 OR credit!=0) AND div='".USER_DIV."' AND month='$prd'";
		$rslt = db_exec($sql) or errDie("Error fetching active account list.");

		while ($macc_data = pg_fetch_array($rslt)) {
			$accids[] = $macc_data["accid"];
		}
	}

	# Period name
	$prdname = prdname($prd);

	$trans = "";
	$hide = "";
	$i = 0;
	$curr_topacc = "";
	foreach($accids as $key => $accid) {
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
			db_conn("core");
			$balSql = "SELECT credit as cbalance, debit as dbalance FROM trial_bal WHERE accid='$acc[accid]' AND period='$prd'";
			$balRs = db_exec($balSql) or errDie("Error reading trial balance.");

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

		if ($acc_first == "accnum") {
			$account_name = "$acc[topacc]/$acc[accnum] - $acc[accname]";
		} else {
			$account_name = "$acc[accname] - $acc[topacc]/$acc[accnum]";
		}

		$hide .= "<input type='hidden' name='accids[]' value='$acc[accid]'>";

		$heading = "";
		if ($acc["accnum"] != "000") {
			if (!$i && $acc["topacc"] != $curr_topacc) {
				db_conn("core");
				$sql = "SELECT * FROM accounts WHERE div='".USER_DIV."' AND topacc='$acc[topacc]' AND accnum='000'";
				$hacc_rslt = db_exec($sql) or errDie("Unable to retrieve main account from Cubit.");
				$hacc_data = pg_fetch_array($hacc_rslt);

				if ($acc_first == "accnum") {
					$heading_name = "$hacc_data[topacc]/$hacc_data[accnum] - $hacc_data[accname]";
				} else {
					$heading_name = "$hacc_data[accname] - $hacc_data[topacc]/$hacc_data[accnum]";
				}

				$heading = "<tr><th colspan='8' align='left'>$heading_name</th></tr>";

				$curr_topacc = $acc["topacc"];
			}
		} elseif ($acc["topacc"] == $curr_topacc) {
			++$i;
		}


		$trans .= "$heading
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='8'><b>$account_name</b></td>
		</tr>";

		// make the date of the last day of the previous prd
		$bbf_date = date("t-M-Y", mktime(0, 0, 0, $prd - 1, 1, getYearOfFinMon($prd)));

		$trans .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2' align='right'>$bbf_date</td>
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

		if ( $t == "s" ) {
			$tranRs = get($prd, "*", "ledger", "acc", $accid);
		} else {
			$tranRs = get($prd, "*", "ledger", "acc", $accid,"ORDER BY edate,id");
		}

		while ($tran = pg_fetch_array($tranRs)) {
   			$dbal['debit'] += $tran['debit'];
			$dbal['credit'] += $tran['credit'];

			if ($t == "t") {
				$tran['dbalance'] = $dbal['debit'] + $bal['dbalance'];
				$tran['cbalance'] = $dbal['credit'] + $bal['cbalance'];
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

			if ($t == "s") {
				$tran['edate'] = $tran['sdate'];
			}

			# Format date
			$tran['edate'] = explode("-", $tran['edate']);
			$tran['edate'] = $tran['edate'][2]."-".$tran['edate'][1]."-".$tran['edate'][0];

			$tran['debit'] = sprint($tran['debit']);
			$tran['credit'] = sprint($tran['credit']);

			if($tran["debit"] != 0) {
				$trans .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2' nowrap>$tran[edate]</td>
					<td>$tran[eref]</td>
					<td>$tran[descript]</td>
					<td align='right' nowrap>$tran[debit]</td>
					<td align='right'></td>
					<td align='right' nowrap>$cbalance $cfl</td>
					<td align='right'>$tran[ctopacc]/$tran[caccnum] - $tran[caccname]</td>
				</tr>";
			} elseif ($tran["credit"] != 0) {
				$trans .= "
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='2' nowrap>$tran[edate]</td>
					<td>$tran[eref]</td>
					<td>$tran[descript]</td>
					<td align='right'></td>
					<td align='right' nowrap>$tran[credit]</td>
					<td align='right' nowrap>$cbalance $cfl</td>
					<td align='right'>$tran[ctopacc]/$tran[caccnum] - $tran[caccname]</td>
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
			$dbal['credit'] = "";
			$dbal['debit'] = "0.00";
		}

		$trans .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2'><br></td>
			<td>A/C Total</td>
			<td>Total for period $prdname to Date :</td>
			<td align='right' nowrap>$dbal[debit]</td>
			<td align='right' nowrap>$dbal[credit]</td>
			<td align='right' nowrap></td>
			<td> </td>
		</tr>";

		$trans .= "<tr><td colspan='8'><br></td></tr>";
	}

	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$view = "
				<center>
				<h3>General Ledger</h3>
				<form action='../xls/ledger-xls.php' method='POST'>
					<input type='hidden' name='key' value='viewtran'>
					<input type='hidden' name='prd' value='$prd'>
					<input type='hidden' name='t' value='$t'>
					<input type='hidden' name=accnt value='$accnt'>
					$hide
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<td colspan='8' align='center'><input type='submit' value='Export to Spreadsheet'></td>
					</tr>
					<tr><td colspan='8'><br></td></tr>
					<tr>
						<th colspan='2'>Date</th>
						<th>Reference</th>
						<th>Description</th>
						<th width='10%'>Debit</th>
						<th width='10%'>Credit</th>
						<th width='10%'>Balance</th>
						<th>Contra Acc</th>
					</tr>
					$trans
					<tr><td colspan='8'><br></td></tr>
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
	return $view;

}



?>