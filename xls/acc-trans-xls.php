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
				<tr bgcolor='".bgcolorg()."'>
					<td colspan='3'><br></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Account Number</td>
					<td><input type='text' name='topacc' size='3' maxlength='3'> / <input type='text' name='accnum' size='3' maxlength='3'></td>
					<td><input type='submit' value='View Transactions'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Select Period</td>
					<td valign='center' colspan='3'>
						<select name='prd'>";

	db_conn(YR_DB);
	$sql = "SELECT * FROM info WHERE prdname !=''";
	$prdRslt = db_exec($sql);
	if(pg_numrows($prdRslt) < 1){
		return "<li class='err'>ERROR : There are no periods set for the current year</li>";
	}

	while($prd = pg_fetch_array($prdRslt)){
		if($prd['prddb'] == PRD_DB){
			$sel = "selected";
		}else{
			$sel= "";
		}
		$slctAcc .="<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
	}

	$slctAcc .= "
						</select>
					</td>
				</tr>
				<tr>
					<td align='center'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				</tr>
		</form>
		</table>
		<table ".TMPL_tblDflts." width='100'>
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

	return $slctAcc;
}

# View per account number and cat
function viewtran($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd, "string", 1, 14, "Invalid Period number.");
	if(isset($details)){
		$v->isOk ($accid, "string", 1, 20, "Invalid Account number.");
	}else{
		$v->isOk ($topacc, "num", 1, 20, "Invalid Account number.");
		$v->isOk ($accnum, "num", 0, 20, "Invalid Account number.");
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

	if(isset($details)){
		$accRs = get("core", "accname, accid, topacc, accnum", "accounts", "accid", $accid);
		$acc = pg_fetch_array($accRs);
	}else{
		if(strlen($accnum) < 2){
			// account numbers
			$accRs = get("core","accname, accid, topacc, accnum","accounts","topacc","$topacc' AND accnum = '000");
			if(pg_numrows($accRs) < 1){
				return "<li> Accounts number : $accnum does not exist";
			}
			$acc  = pg_fetch_array($accRs);
			}else{
			// account numbers
			$accRs = get("core","accname, accid, topacc, accnum","accounts","topacc","$topacc' AND accnum = '$accnum");
			if(pg_numrows($accRs) < 1){
				return "<li> Accounts number : $topacc/$accnum does not exist";
			}
			$acc  = pg_fetch_array($accRs);
		}
	}

	db_conn($prd);

	// Set up table to display in
	$OUTPUT = "
			<table ".TMPL_tblDflts." width='80%'>
				<tr>
					<th colspan='7'><h3>Journal Entries for Account : $acc[topacc]/$acc[accnum] - $acc[accname]</h3></th>
				</tr>
				<tr><th colspan='7'></th></tr>
				<tr>
					<td colspan='3' align='left'>".COMP_NAME."</td>
					<td colspan='4' align='right'>".date("Y-m-d")."</td>
				</tr>
				<tr>
					<th><u>Date</u></th>
					<th><u>Reference</u></th>
					<th><u>Contra Acc</u></th>
					<th><u>Description</u></th>
					<th><u>Debit</u></th>
					<th><u>Credit</u></th>
					<th><u>User</u></th>
				</tr>";

	# Get Transactions
	$sql = "SELECT * FROM transect WHERE debit = '$acc[accid]' AND div = '".USER_DIV."' OR credit = '$acc[accid]' AND div = '".USER_DIV."'";
	$tranRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
	if (pg_numrows ($tranRslt) < 1) {
		$OUTPUT .= "<tr><td colspan='10'>No Transactions found</td></tr>";
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
			foreach ($tran as $key => $value) {
				$$key = $value;
			}

			if($debit == $acc['accid']){
				$cacc = $credit;
				$debitamt = "R ".$amount;
				$debtot += $amount;
				$creditamt = "";
			}else{
				$debitamt = "";
				$creditamt = "R ".$amount;
				$credtot += $amount;
				$cacc = $debit;
			}

			# format date
			$date = explode("-", $date);
			$date = $date[2]."-".$date[1]."-".$date[0];

			# get contra account name
			$caccRs = get("core","accname,topacc,accnum","accounts","accid",$cacc);
			$cacc = pg_fetch_array($caccRs);

			$OUTPUT .= "
					<tr>
						<td>$date</td>
						<td>$refnum</td>
						<td>$cacc[topacc]/$cacc[accnum] - $cacc[accname]</td>
						<td>$details</td>
						<td>$debitamt</td>
						<td>$creditamt</td>
						<td>$author</td>
					</tr>";
		}
	}

	$OUTPUT .= "
			<tr>
				<td colspan='4'><b>Total</b></td>
				<td><b>".CUR." $debtot</b></td>
				<td><b>".CUR." $credtot</b></td>
				<td></td>
			</tr>
		</table>";

	$acc['accname'] = str_replace(" ","",$acc['accname']);
	# Send the stream
	include("temp.xls.php");
	Stream("AllTrans-$acc[accname]", $OUTPUT);

	return $OUTPUT;

}
?>
