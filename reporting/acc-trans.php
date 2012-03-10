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
if(isset($HTTP_GET_VARS['accid'])){
	$HTTP_GET_VARS['prd'] = PRD_DB;
	$HTTP_GET_VARS['details'] = "";
	$OUTPUT = viewtran($HTTP_GET_VARS);
}elseif (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "viewtran":
			$OUTPUT = viewtran($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slctAcc($HTTP_POST_VARS);
	}
} else {
	$OUTPUT = slctAcc($HTTP_POST_VARS);
}

# Get templete
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
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Name</td>
				<td>
					<select name='accid'>";

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
				<td>Select Period</td>
				<td valign='center' colspan='3'>".finMonList("prd", PRD_DB)."</td>
			</tr>
			<tr><td></td></tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
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
function viewtran($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	if (!isset($from_year))
		$from_year = date ("Y");
	if (!isset($from_month)) 
		$from_month = date ("m");
	if (!isset($from_day)) 
		$from_day = "01";

	if (!isset($to_year)) 
		$to_year = date ("Y");
	if (!isset($to_month)) 
		$to_month = date ("m");
	if (!isset($to_day)) 
		$to_day = date ("d");

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";



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
		$v->isOk ($accnum, "num", 0, 3, "Invalid Account number.");
		$hide = "
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='topacc' value='$topacc'>
			<input type='hidden' name='accnum' value='$accnum'>";
	}
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	if(!checkdate($from_month, $from_day, $from_year)){
		$v->isOk ($from_date, "num", 1, 1, "Invalid from date.");
		$HTTP_POST_VARS["from_year"] = date ("Y");
		$HTTP_POST_VARS["from_month"] = date ("m");
		$HTTP_POST_VARS["from_day"] = date ("d");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
		$v->isOk ($to_date, "num", 1, 1, "Invalid to date.");
		$HTTP_POST_VARS["to_year"] = date ("Y");
		$HTTP_POST_VARS["to_month"] = date ("m");
		$HTTP_POST_VARS["to_day"] = date ("d");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirm.viewtran($HTTP_POST_VARS);
	}

	if(isset($details)){
		$accRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $accid);
		$acc = pg_fetch_array($accRs);
	}else{
		if(strlen($accnum) < 2){
			// account numbers
			$accRs = get("core","accname, accid, topacc, accnum","accounts","topacc","$topacc' AND accnum = '000");
			if(pg_numrows($accRs) < 1){
				return "<li> Accounts number : $accnum does not exist.</li>";
			}
			$acc  = pg_fetch_array($accRs);
		}else{
			// account numbers
			$accRs = get("core","accname, accid, topacc, accnum","accounts","topacc","$topacc' AND accnum = '$accnum");
			if(pg_numrows($accRs) < 1){
				return "<li> Accounts number : $topacc/$accnum does not exist.</li>";
			}
			$acc  = pg_fetch_array($accRs);
		}
	}

	// Set up table to display in
	$OUTPUT = "
		<center>
		<h3>Journal Entries for Account : $acc[topacc]/$acc[accnum] - $acc[accname]</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts." width='80%'>
			<tr>
				<td width='15%' align='center'>
					<table ".TMPL_tblDflts." width='55%'>
						<tr>
							<th colspan='2' align='center'>Date Range</th>
						</tr>
						<tr>
							<th>From Date:</th>
							<th>To Date:</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td nowrap>".mkDateSelect ("from",$from_year,$from_month,$from_day)."</td>
							<td nowrap>".mkDateSelect ("to",$to_year,$to_month,$to_day)."</td>
							<td><input type='submit' value='View'></td>
						</tr>
					</table>
				</tr>
			</tr>
		</table>
		<br>
		<table ".TMPL_tblDflts." width='80%'>
			<tr>
				<th>Date</th>
				<th>Reference</th>
				<th>Contra Acc</th>
				<th>Description</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>User</th>
			</tr>";

	db_connect ();

	# Get Transactions
	$sql = "
		SELECT * FROM \"1\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"2\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"3\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"4\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"5\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"6\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"7\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"8\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"9\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"10\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"11\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"12\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"13\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date') UNION 
		SELECT * FROM \"14\".transect WHERE ((debit = '$acc[accid]' AND div = '".USER_DIV."') OR (credit = '$acc[accid]' AND div = '".USER_DIV."')) AND (date >= '$from_date' AND date <= '$to_date')";
	$tranRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
	if (pg_numrows ($tranRslt) < 1) {
		$OUTPUT .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='10'>No Transactions found</td>
			</tr>";
		# counts
		$credtot = 0;
		$debtot = 0;
	}else{
		# counts
		$credtot = 0;
		$debtot = 0;

		# display all transactions
		while ($tran = pg_fetch_array ($tranRslt)){
			#get vars from tran as the are in db
			extract ($tran);

			$amount = sprint($amount);

			if($debit == $acc['accid']){
				$cacc['accname'] = $caccname;
				$cacc['accnum'] = $caccnum;
				$cacc['topacc'] = $ctopacc;
				$debitamt = "R ".$amount;
				$debtot += $amount;
				$creditamt = "";
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
			get contra account name
			$caccRs = get("core","accname,topacc,accnum","accounts","accid",$cacc);
			$cacc = pg_fetch_array($caccRs);
			*/

			$OUTPUT .= "
				<tr bgcolor='".bgcolorg()."'>
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

	$debtot = sprint($debtot);
	$credtot = sprint($credtot);

	$OUTPUT .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4'><b>Total</b></td>
			<td nowrap><b>".CUR." $debtot</b></td>
			<td nowrap><b>".CUR." $credtot</b></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align='center' colspan='10'>
				<form action='../xls/acc-trans-xls.php' method='POST' name='form'>
					<input type='hidden' name='key' value='viewtran'>
					$hide
					<input type='submit' name='xls' value='Export to spreadsheet'>
				</form>
			</td>
		</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a target='_blank' href='../core/acc-new2.php'>Add account (New Window)</a></td>
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
	return $OUTPUT;

}


?>