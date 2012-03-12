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

# decide what to do
if(isset($_GET['accid'])){
	$_GET['prd'] = PRD_DB;
	$_GET['details'] = "";
	$OUTPUT = viewtran($_GET);
}elseif (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "spreadsheet":
			$OUTPUT = clean_html(viewtran($_POST));
			require_lib("xls");
			StreamXLS("journalacc", $OUTPUT);
			break;
		case "viewtran":
			$OUTPUT = viewtran($_POST);
			break;
		default:
			$OUTPUT = slctAcc($_POST);
	}
} else {
	$OUTPUT = slctAcc($_POST);
}

# Get templete
require("../template.php");



# Select Category
function slctAcc()
{

	global $PRDMON;
	$fprd = finMonList("fprd", $PRDMON[1]);
	$tprd = finMonList("tprd", PRD_DB);

	// Layout
	$slctAcc = "
		<h3>Select Options</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='viewtran'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Name</td>
				<td><select name='accid'>";

	core_connect();
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
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
				<td><input type='submit' name='details' value='View Transactions'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'><td colspan='3'><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Number</td>
				<td><input type='text' name='topacc' size='4' maxlength='4'> / <input type='text' name='accnum' size='3' maxlength='3'></td>
				<td><input type='submit' value='View Transactions'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>From :</td>
				<td valign='center' colspan='3'>$fprd To : $tprd</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='center'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
			</tr>
		</form>
		</table>
		<table ".TMPL_tblDflts." width='100'>
			<tr><th>Quick Links</th></tr>
			<tr class='datacell'><td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
			<tr class='datacell'><td align='center'><a href='index-reports.php'>Financials</a></td></tr>
			<tr class='datacell'><td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td></tr>
			<tr class='datacell'><td align='center'><a href='../main.php'>Main Menu</a></td></tr>
		</table>";
	return $slctAcc;

}



# View per account number and cat
function viewtran($_POST)
{

	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fprd, "string", 1, 14, "Invalid Starting Period number.");
	$v->isOk ($tprd, "string", 1, 14, "Invalid Ending Period number.");
	if(isset($details)){
		$v->isOk ($accid, "string", 1, 20, "Invalid Account number.");
		$hide = "
			<input type='hidden' name='fprd' value='$fprd'>
			<input type='hidden' name='tprd' value='$tprd'>
			<input type='hidden' name='details' value='$details'>
			<input type='hidden' name='accid' value='$accid'>";
	}else{
		$v->isOk ($topacc, "num", 1, 4, "Invalid Account number.");
		$v->isOk ($accnum, "num", 0, 3, "Invalid Account number.");
		$hide = "
			<input type='hidden' name='fprd' value='$fprd'>
			<input type='hidden' name='tprd' value='$tprd'>
			<input type='hidden' name='topacc' value='$topacc'>
			<input type='hidden' name='accnum' value='$accnum'>";
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

	if ($key == "spreadsheet") {
		$pure = true;
	} else {
		$pure = false;
	}

	# dates drop downs
	$months = array(
		"1" => "January", 
		"2" => "February", 
		"3" => "March", 
		"4" => "April", 
		"5" => "May", 
		"6" => "June", 
		"7" => "July", 
		"8" => "August", 
		"9" => "September", 
		"10" => "October", 
		"11" => "November", 
		"12" => "December"
	);

	if(isset($details)){
		$accRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $accid);
		$acc = pg_fetch_array($accRs);
	}else{
		if(strlen($accnum) < 2){
			// account numbers
			$accRs = get("core","accname, accid, topacc, accnum","accounts","topacc","$topacc' AND accnum = '000");
			if(pg_numrows($accRs) < 1){
				return "<li> Accounts number : $accnum does not exist"."</li>".slctAcc();
			}
			$acc  = pg_fetch_array($accRs);
			}else{
			// account numbers
			$accRs = get("core","accname, accid, topacc, accnum","accounts","topacc","$topacc' AND accnum = '$accnum");
			if(pg_numrows($accRs) < 1){
				return "<li> Accounts number : $topacc/$accnum does not exist"."</li>".slctAcc();
			}
			$acc  = pg_fetch_array($accRs);
		}
	}

	// Set up table to display in
	$OUTPUT = "";
	
	if (!$pure) {
		$OUTPUT .= "
			<center>
			<h3>Journal Entries for Account : $acc[topacc]/$acc[accnum] - $acc[accname]</h3>";
	}
	
	$OUTPUT .= "
		<table ".TMPL_tblDflts." width='80%'>
			<tr>
				<th>Period</th>
				<th>Date</th>
				<th>Reference</th>
				<th>Contra Acc</th>
				<th>Description</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>User</th>
			</tr>";

	$prds = array();
	if ($tprd < $fprd) {
		for($i=$fprd; $i <= 12; $i++){
			$prds[] = $i;
		}
		for($i= 1; $i <= $tprd; $i++){
			$prds[] = $i;
		}
	} else {
		for($i= $fprd; $i <= $tprd; $i++){
			$prds[] = $i;
		}
	}

	# counts
	$credtot = 0;
	$debtot = 0;
	# Get Transactions
	foreach ($prds as $i) {
		db_conn($i);
		$sql = "SELECT * FROM transect WHERE debit = '$acc[accid]' AND div = '".USER_DIV."' OR credit = '$acc[accid]' AND div = '".USER_DIV."'";
		$tranRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
		if (pg_numrows ($tranRslt) < 1) {
			continue;
		}else{
			# display all transactions
			while ($tran = pg_fetch_array ($tranRslt)){
				#get vars from tran as the are in db
				foreach ($tran as $key => $value) {
					$$key = $value;
				}

				$amount = sprint($amount);

				if($debit == $acc['accid']){
					$creditamt = "";
					$debitamt = "R ".$amount;
					$debtot += $amount;
					$cacc['accname'] = $caccname;
					$cacc['accnum'] = $caccnum;
					$cacc['topacc'] = $ctopacc;
				}else{
					$debitamt = "";
					$creditamt = "R ".$amount;
					$credtot += $amount;
					$cacc['accname'] = $daccname;
					$cacc['accnum'] = $daccnum;
					$cacc['topacc'] = $dtopacc;
				}

				# format date
				$date = explode("-", $date);
				$date = $date[2]."-".$date[1]."-".$date[0];

				/*
				# get contra account name
				$caccRs = get("core","accname,topacc,accnum","accounts","accid",$cacc);
				$cacc = pg_fetch_array($caccRs);
				*/

				$OUTPUT .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$months[$i]</td>
						<td>$date</td>
						<td>$custom_refnum</td>
						<td>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td>
						<td>$details</td>
						<td align='right'>$debitamt</td>
						<td align='right'>$creditamt</td>
						<td>$author</td>
					</tr>";
			}
		}
	}
	
	vsprint($debtot);
	vsprint($credtot);

	$OUTPUT .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='5'><b>Total</b></td>
			<td nowrap='t'><b>".CUR." $debtot</b></td>
			<td nowrap='t'><b>".CUR." $credtot</b></td>
			<td></td>
		</tr>";

	if (!$pure) { 
		$OUTPUT .= "
				<tr><td><br></td></tr>
				<tr><td align='center' colspan='10'>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='spreadsheet'>
						$hide
						<input type='submit' name='xls' value='Export to spreadsheet'>
					</form>
				</td></tr>
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
					<td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='../main.php'>Main Menu</td>
				</tr>
			</table>";
	}
	return $OUTPUT;

}


?>