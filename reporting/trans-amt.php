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
	$prds = finMonList("prd", PRD_DB);


	core_connect();
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);

	if(pg_numrows($accRslt) < 1){
		return "<li class='err'> There are no Accounts in Cubit.";
	}
	$accs = "<select name='accids[]' multiple size='10'>";
	while($acc = pg_fetch_array($accRslt)){
		$accs .= "<option value='$acc[accid]'>$acc[accname]</option>";
	}
	$accs .= "</select>";

	$slctacc = "
			<p>
			<h3>Journal Entries By Ref no.</h3>
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
					<td></td>
					<td align='right'><input type='submit' value='Continue &raquo;'></td>
				</tr>
			</table>
			<p>
			<table ".TMPL_tblDflts." width='25%'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='index-reports.php'>Financials</a></td>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='../main.php'>Main Menu</td>
				</tr>
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
	$v->isOk ($accnt, "string", 1, 5, "Invalid Accounts Selection.");

	if($accnt == 'slct'){
		if(isset($accids)){
			foreach($accids as $key => $accid){
				$v->isOk ($accid, "num", 1, 20, "Invalid Account number.");
			}
		}else{
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
			return "<li calss='err'> There are no accounts yet in Cubit.</li>";
		}
	}

	# Period name
	$prdname = prdname($prd);
	$hide="";

	$trans = "";
	foreach($accids as $key => $accid){
  		$accRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $accid);
		$acc = pg_fetch_array($accRs);

		$hide .= "<input type=hidden name=accids[] value='$accid'>";

		db_conn($prd);
		$sql = "SELECT DISTINCT eref FROM ledger WHERE acc = '$accid' AND debit > 0";
		$disRs = db_exec($sql);
		if(pg_numrows($disRs) > 0){
			while($dis = pg_fetch_array($disRs)){
				$sql = "SELECT sum(debit) as debit,edate FROM ledger WHERE acc = '$accid' AND eref = '$dis[eref]' GROUP BY edate";
				$sumRs = db_exec($sql);
				$sum = pg_fetch_array($sumRs);

				$trans .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$sum[edate]</td>
							<td><b>$acc[topacc]/$acc[accnum] - $acc[accname]</b></td>
							<td>$dis[eref]</td>
							<td><br descript><td align='right'>$sum[debit]</td>
							<td><br credit></td>
							<td><br cacc></td>
						</tr>";

				$sql = "SELECT * FROM ledger WHERE acc = '$accid' AND eref = '$dis[eref]'";
				$tranRs = db_exec($sql);
				while($tran = pg_fetch_array($tranRs)){
					$trans .= "
							<tr bgcolor='".bgcolorg()."'>
								<td><br date></td>
								<td><br account></td>
								<td><br ref></td>
								<td>$tran[descript]</td>
								<td><br debit></td>
								<td align='right'>$tran[debit]</td>
								<td>$tran[ctopacc]/$tran[caccnum] - $tran[caccname]</td>
							</tr>";
				}
				$trans .= "<tr><td><br></td></tr>";
			}
		}

		db_conn($prd);
		$sql = "SELECT DISTINCT eref FROM ledger WHERE acc = '$accid' AND credit > 0";
		$disRs = db_exec($sql);
		if(pg_numrows($disRs) > 0){
			while($dis = pg_fetch_array($disRs)){
				$sql = "SELECT sum(credit) as credit,edate FROM ledger WHERE acc = '$accid' AND eref = '$dis[eref]' GROUP BY edate";
				$sumRs = db_exec($sql);
				$sum = pg_fetch_array($sumRs);

				$trans .= "
						<tr bgcolor='".TMPL_tblDataColor2."'>
							<td>$sum[edate]</td>
								<td><b>$acc[topacc]/$acc[accnum] - $acc[accname]</b></td>
								<td>$dis[eref]</td>
								<td><br descript></td>
								<td><br debit></td>
								<td>$sum[credit]</td>
								<td><br></td>
							</tr>";

				$sql = "SELECT * FROM ledger WHERE acc = '$accid' AND eref = '$dis[eref]'";
				$tranRs = db_exec($sql);
				while($tran = pg_fetch_array($tranRs)){
					$trans .= "
							<tr bgcolor='".TMPL_tblDataColor1."'>
								<td><br date></td>
								<td><br account></td>
								<td><br ref></td>
								<td>$tran[descript]</td>
								<td align='right'>$tran[credit]</td>
								<td><br credit></td>
								<td>$tran[ctopacc]/$tran[caccnum] - $tran[caccname]</td>
							</tr>";
				}
				$trans .= "<tr><td><br></td></tr>";
			}
		}
	}

	$sp = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$view = "
			<center>
			<form action='../xls/trans-amt-xls.php' method='POST'>
				<input type='hidden' name='key' value='viewtran'>
				<input type='hidden' name='prd' value='$prd'>
				<input type='hidden' name='accnt' value='$accnt'>
				$hide
				<h3>Journal Entries By Ref no.</h3>
			<table ".TMPL_tblDflts." width='95%'>
				<tr>
					<th>Date</th>
					<th>Account</th>
					<th>Ref No.</th>
					<th>Description</th>
					<th>Debit</th>
					<th>Credit</th>
					<th>Contra Acc</th>
				</tr>
				$trans
				<tr>
					<td colspan='8' align='center'><input type='submit' value='Export to Spreadsheet'></td></tr>
			</from>
			</table>
			<p>
			<table ".TMPL_tblDflts." width='25%'>
				<tr><td><br></td></tr>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='index-reports.php'>Financials</a></td>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='../main.php'>Main Menu</td>
				</tr>
			</table>";

	return $view;
}
?>
