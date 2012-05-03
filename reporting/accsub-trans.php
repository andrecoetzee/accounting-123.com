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

# get settings
require("../settings.php");
require("../core-settings.php");

# decide what to do
if(isset($_GET['accid'])){
	$_GET['prd'] = PRD_DB;
	$_GET['details'] = "";
	$OUTPUT = viewtran($_GET);
}elseif (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "viewtran":
			$OUTPUT = viewtran($_POST);
			break;
		default:
			$OUTPUT = slctAcc($_POST);
	}
} else {
	$OUTPUT = slctAcc($_POST);
}

# get templete
require("../template.php");


# Select Category
function slctAcc()
{

	// Layout
	$slctAcc = "
		<h3>Select Account</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='viewtran'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account Name</td>
				<td>
					<select name='accid'>";

	core_connect();
	$sql = "SELECT * FROM accounts WHERE accnum = '000' AND div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);

	if(pg_numrows($accRslt) < 1){
		return "<li class='err'> There are no Accounts in Cubit.</li>";
	}

	while($acc = pg_fetch_array($accRslt)){
		$slctAcc .= "<option value='$acc[accid]'>$acc[accname]</option>";
	}

	$slctAcc .= "
					</select>
				</td>
				<td><input type='submit' name='details' value='Enter Details'></td>
			</tr>
			<tr class='".bg_class()."'><td colspan=3><br></td></tr>
			<tr class='".bg_class()."'>
				<td>Account Number</td>
				<td><input type='text' name='topacc' size='4' maxlength='4'> / <input type='text' size='3' maxlength='3' disabled='yes' value='000'></td>
				<td><input type='submit' value='View'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select Period</td>
				<td valign='center' colspan='3'>".finMonList("prd", PRD_DB)."</td>
			</tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' value='View Transactions &raquo'></td>
			</tr>
		</form>
		</table>
		<table ".TMPL_tblDflts." width='100'>
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
				<td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $slctAcc;

}



# View per account number and cat
function viewtran($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd, "string", 1, 14, "Invalid Period number.");
	if(isset($details)){
		$v->isOk ($accid, "string", 1, 20, "Invalid Account number.");
		$hide = "
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='details' value='$details'>
			<input type='hidden' name='accid' value='$accid'>";
	}else{
		$v->isOk ($topacc, "num", 1, 4, "Invalid Account number.");
		$hide = "
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='topacc' value='$topacc'>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirm.slctAcc();
	}

	if(isset($details)){
		$acc = qryAccounts($accid);
	}else{
		if(strlen($topacc) > 2){
			$acc = qryAccountsNum($topacc, "000");
		}else{
			return "<li> Accounts number : $topacc/000 does not exist.</li>";
		}
	}

	db_conn($prd);

	// Set up table to display in
	$OUTPUT = "
		<center>
		<h3>Journal Entries for Account : $acc[topacc]/$acc[accnum] - $acc[accname] and Sub Accounts</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Reference</th>
				<th>Contra Acc</th>
				<th>Description</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>User</th>
			</tr>";

	# get all sub accounts
	core_connect();
	$sql = "SELECT * FROM accounts WHERE topacc = '$acc[topacc]' AND div = '".USER_DIV."' ORDER BY accnum ASC";
	$subRs = db_exec($sql);

	# all totals
	$allcredtot = 0;
	$alldebtot = 0;

	while($subacc = pg_fetch_array($subRs)){
		$OUTPUT .= "
			<tr><td><br></td></tr>
			<tr class='".bg_class()."'>
				<td colspan='10'><h2>Account : $subacc[topacc]/$subacc[accnum] - $subacc[accname]</h2></td>
			</tr>";

		# get Transactions
		db_conn($prd);
		$sql = "SELECT * FROM transect WHERE debit = '$subacc[accid]' AND div = '".USER_DIV."' OR credit = '$subacc[accid]' AND div = '".USER_DIV."'";
		$tranRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
		if (pg_numrows ($tranRslt) < 1) {
			# counts
			$credtot = 0;
			$debtot = 0;
			$OUTPUT .= "
				<tr class='".bg_class()."'>
					<td colspan='10'>No Transactions found</td>
				</tr>";
			$debtot = sprint ($debtot);
			$credtot = sprint ($credtot);
			$OUTPUT .= "
				<tr class='".bg_class()."'>
					<td colspan='4'><b>Total</b></td>
					<td><b>".CUR." $debtot</b></td>
					<td><b>".CUR." $credtot</b></td>
					<td></td>
				</tr>";
		}else{
			# counts
			$credtot = 0;
			$debtot = 0;

			# display all transactions
			while ($tran = pg_fetch_array ($tranRslt)){
				extract($tran);

				$amount = sprint($amount);

				if($debit == $subacc['accid']){
					$cacc['accname'] = $caccname;
					$cacc['accnum'] = $caccnum;
					$cacc['topacc'] = $ctopacc;
					$debitamt = "R ".$amount;
					$debtot += $amount;
					$alldebtot += $amount;
					$creditamt = "";
				}else{
					$debitamt = "";
					$creditamt = "R ".$amount;
					$credtot += $amount;
					$allcredtot += $amount;
					$cacc['accname'] = $daccname;
					$cacc['accnum'] = $daccnum;
					$cacc['topacc'] = $dtopacc;
				}

				/*
				# get contra account name
				$caccRs = get("core","accname,topacc,accnum","accounts","accid",$cacc);
				$cacc = pg_fetch_array($caccRs);
				*/

				$OUTPUT .= "
					<tr class='".bg_class()."'>
						<td>$date</td>
						<td>$custom_refnum</td>
						<td>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td>
						<td>$details</td>
						<td align='right'>$debitamt</td>
						<td align='right'>$creditamt</td>
						<td>$author</td>
					</tr>";
			}

			if($acc["acctype"] == "I"){
				$accbal = ($credtot - $debtot);
			} else if ($acc["acctype"] == "E") {
				$accbal = ($debtot - $credtot);
			} else if ($acc["acctype"] == "B"){
				$accbal = ($debtot - $credtot);
			} else {
				return "<li class='err'>Account number is beyond limits.</li>";
			}

			$debtot = sprint ($debtot);
			$credtot = sprint ($credtot);
			$accbal = sprint ($accbal);
			
			$OUTPUT .= "
				<tr class='".bg_class()."'>
					<td colspan='4'><b>Total</b></td>
					<td><b>".CUR." $debtot</b></td>
					<td><b>".CUR." $credtot</b></td>
					<td></td>
				</tr>
				<tr class='".bg_class()."'>
					<td colspan='4'><b>Balance</b></td>
					<td colspan='2'><b>".CUR." $accbal</b></td>
					<td></td>
				</tr>";
		}
	}

	$alldebtot = sprint ($alldebtot);
	$allcredtot = sprint ($allcredtot);

	$OUTPUT .= "
			<tr><td><br></td></tr>
			<tr class='".bg_class()."'>
				<td colspan='4'><b>Total</b></td>
				<td><b>".CUR." $alldebtot</b></td>
				<td><b>".CUR." $allcredtot</b></td>
				<td></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='center' colspan='10'>
					<form action='../xls/accsub-trans-xls.php' method='POST' name='form'>
						<input type='hidden' name='key' value='viewtran'>
						$hide
						<input type='submit' name='xls' value='Export to spreadsheet'>
					</form>
				</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='25%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'><td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
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
	return $OUTPUT;

}


?>