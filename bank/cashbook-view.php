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
require("../libs/ext.lib.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "viewcash":
			$OUTPUT = viewcash($_POST);
			break;
		default:
			$OUTPUT = view();
	}
} else {
	# Display default output
	$OUTPUT = view();
}

# get template
require("../template.php");



# Default view
function view()
{

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

	$fmonth = extlib_cpsel("fmonth", $months, date("m"));
	$lmonth = extlib_cpsel("lmonth", $months, date("m"));

	// main layout
	$view = "
		<h3>View Cash Book</h3>
		<h4>Select Period</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='viewcash' />
			<input type='hidden' name='order' value='' />
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank Account</td>
				<td valign='center'><select name='bankid'>";

	db_connect();

	$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."' ORDER BY accname,bankname";
	$banks = db_exec($sql);
	$numrows = pg_numrows($banks);

	if(empty($numrows)){
		return "<li class='err'> There are no accounts held at the selected Bank.
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	while($acc = pg_fetch_array($banks)){
		$view .= "<option value='$acc[bankid]'>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}

	$view .= "
				</select>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>From:</td>
			<td valign='center'>".mkDateSelect("f", DATE_YEAR, DATE_MONTH, 1)."</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>To:</td>
			<td valign='center'>".mkDateSelect("l", DATE_YEAR, DATE_MONTH, DATE_DAY)."</td></tr>
		<tr>
			<td align='right'></td>
			<td align='right'><input type='submit' value='View &raquo' /></td>
		</tr>
		</table>"
		.mkQuickLinks(
			ql("../core/acc-new2.php", "Add New Account")
		);
	return $view;

}




function viewcash($_POST)
{

	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk($f_day, "num", 1, 2, "Invalid Day for the 'From' date.");
	$v->isOk($f_month, "num", 1, 2, "Invalid month for the 'From' date..");
	$v->isOk($f_year, "num", 1, 4, "Invalid year for the 'From' date..");
	$v->isOk($l_day, "num", 1, 2, "Invalid Day for the 'To' date.");
	$v->isOk($l_month, "num", 1, 2, "Invalid month for the 'To' date..");
	$v->isOk($l_year, "num", 1, 4, "Invalid year for the 'To' date..");

	# lets mix the date
	$from = mkdate($f_year, $f_month, $f_day);
	$to = mkdate($l_year, $l_month, $l_day);

	if ($v->isError()) {
		$err = $v->genErrors();
		return $err;
	}

	if (isset($export)) {
		$pure = true;
	} else {
		$pure = false;
	}

	$bank = qryBankAcct($bankid);
	$curdata = qryCurrency($bank["fcid"]);
	$fc = $curdata['symbol'];

	$s1="";
	$s2="";
	$s3="";
	$s4="";
	$s5="";

	if(isset($order)) {
		if($order == "ORDER BY date ASC, cheqnum ASC") {
			$s2 = "selected";
 		} elseif($order == "ORDER BY date DESC, cheqnum DESC") {
			$s3 = "selected";
 		} elseif($order == "ORDER BY cheqnum ASC") {
			$s4 = "selected";
 		} elseif($order == "ORDER BY cheqnum DESC") {
			$s5 = "selected";
 		}  else {
			$s1 = "selected";
		}
	} else {
		$order = "ORDER BY date DESC, cheqnum ASC";
		$s1 = "selected";
	}

	// Set up table to display in
	# Receipts
	$OUTPUT = "
		<center>
		<table ".TMPL_tblDflts." width='95%'>
			<tr>
				<td colspan='8' align='center'><h3>Cash Book<br><br>Account : $bank[accname] - $bank[bankname]<br>Period : $from to $to</h3></td>
			</tr>";

	if (!$pure) {
		$OUTPUT .= "
			<tr>
				<td colspan='8' align='center'>
					<form action='".SELF."' method='POST' name='form'>
					<table ".TMPL_tblDflts.">
						<input type='hidden' name='key' value='viewcash'>
						<input type='hidden' name='bankid' value='$bankid'>
						<input type='hidden' name='f_day' value='$f_day'>
						<input type='hidden' name='f_month' value='$f_month'>
						<input type='hidden' name='f_year' value='$f_year'>
						<input type='hidden' name='l_day' value='$l_day'>
						<input type='hidden' name='l_month' value='$l_month'>
						<input type='hidden' name='l_year' value='$l_year'>
						<tr>
							<th>Order By</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>
								<select name='order' onChange='javascript:document.form.submit();'>
									<option value='' disabled $s1 >Select</option>
									<option value='ORDER BY date ASC, cheqnum ASC' $s2>Date, Cheque No. Ascending</option>
									<option value='ORDER BY date DESC, cheqnum DESC' $s3>Date, Cheque No. Descending</option>
									<option value='ORDER BY cheqnum ASC' $s4>Cheque No. Ascending</option>
									<option value='ORDER BY cheqnum DESC' $s5>Cheque No. Descending</option>
								</select>
							</td>
						</tr>
						<tr>
							<td align='center'><input type='submit' name='export' value='Export to Spreadsheet'></td>
						</tr>
					</form>
					</table>
				</td>
			</tr>";
	}

	$OUTPUT .= "
		<tr>
			<td colspan='7'><h4>Receipts</h4></td>
		</tr>
		<tr>
			<th>Date</th>
			<th width='20%'>Bank Account Name</th>
			<th width='5%'>Cheque Number</th>
			<th width='15%'>Received From : </th>
			<th width='20%'>Description</th>
			<th>Reference</th>
			<th width='21%'>Ledger Account</th>
			<th width='23%'>Amount</th>
		</tr>";

	$rtotal = 0; // Received total amount

	// Connect to database
	db_Connect ();

	$sql = "SELECT * FROM cashbook WHERE date >= '$from' AND date <= '$to' AND trantype='deposit' AND bankid='$bankid' AND div = '".USER_DIV."' $order";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

	if ($numrows < 1) {
		$OUTPUT .= "<tr><td colspan='7' align='center'><li class='err'>There are no Payments/cheques received on the selected period.</td></tr>";
	}else{
		# display all bank Deposits
		for ($i=0; $i < $numrows; $i++) {
			$accnt = pg_fetch_array ($accntRslt, $i);

			if(strlen($accnt['accids']) > 0){
				$acc['accname'] = "<a href=\"javascript: openSmallWindow('multi-acc-popup.php?cashid=$accnt[cashid]&type=cash')\">Multiple Accounts</a>";
				$acc['accno'] = "";
			}else{
				# Get account name for the account involved
				$AccRslt = get("core","accname, topacc, accnum","accounts", "accid", $accnt['accinv']);
				$acc = pg_fetch_array($AccRslt);
				$acc['accno'] = "$acc[topacc]/$acc[accnum]";
			}

			# Get account name for bank account
			db_connect();
			$sql = "SELECT accname,btype FROM bankacct WHERE bankid= '$accnt[bankid]' AND div = '".USER_DIV."'";
			$bnameRslt = db_exec($sql);
			$bname = pg_fetch_array($bnameRslt);

			$rtotal += $accnt['amount']; // add to rtotal
			$accnt['amount'] = sprint($accnt['amount']);
			$accnt['date'] = ext_rdate($accnt['date']);

			if($bname['btype']!="loc") {
				$ex = "/ $fc $accnt[famount]";
			} else {
				$ex = "";
			}

			if (empty($accnt["multicusnum"])) {
				$from_disp = "$accnt[name]";
			} else {
				$from_disp = "<a href=\"javascript: openSmallWindow('multi-debtor-popup.php?cashid=$accnt[cashid]&type=cash')\">Multiple Debtors</a>";
			}

			$OUTPUT .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$accnt[date]</td>
					<td align='center'>$bname[accname]</td>
					<td align='center'>$accnt[cheqnum]</td>
					<td align='center'>$from_disp</td>
					<td>$accnt[descript]</td>
					<td>$accnt[reference]</td>
					<td>$acc[accno]  $acc[accname]</td>
					<td>".CUR." $accnt[amount] $ex</td>
					<td><a href='#' onClick=\"printer ('bank/bank-recpt-inv-print.php?recid=$accnt[cashid]');\">Print</a></td>";

			if(!$pure && $accnt['banked'] == "no" && $accnt['opt'] != 'n'){
				$OUTPUT .= "<td><a href='../bank/cheq-return.php?cashid=$accnt[cashid]'>Returned/Unpaid</td>";
				// $OUTPUT .= "<td><a href='../bank/cheq-cancel.php?cashid=$accnt[cashid]'>Cancel</td>";
			}
			$OUTPUT .= "</tr>";
		}
		# print the total
		$OUTPUT .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='6'><b>Total Receipts</b></td>
				<td><b>".CUR." ".sprintf("%01.2f",$rtotal)."</b></td>
			</tr>";
	}

	# Seperate the tables with two rows
	$OUTPUT .= "<tr><td colspan='7'><br></td></tr><tr><td colspan='7'><br></td></tr>";

	# Payments
	$OUTPUT .= "
		<tr>
			<td colspan='7'><h4>Payments</h4></td>
		</tr>
		<tr>
			<th>Date</th>
			<th>Bank Account Name</th>
			<th>Cheque Number</th>
			<th>Paid to: </th>
			<th>Description</th>
			<th>Reference</th>
			<th>Ledger Account</th>
			<th>Amount</th>
		</tr>";

	$ptotal = 0; // payments total

	// Connect to database
	db_Connect ();
	$sql = "SELECT * FROM cashbook WHERE date >= '$from' AND date <= '$to' AND trantype='withdrawal' AND bankid='$bankid' AND div = '".USER_DIV."' $order";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);

	if (pg_numrows ($accntRslt) < 1) {
		$OUTPUT .= "<tr><td colspan='7' align='center'><li class='err'>There are no Payments made on the selected period.</td></tr>";
	}else{
		# Display all bank Deposits
		for ($i = 0; $accnt = pg_fetch_array ($accntRslt); $i++) {

			# alternate bgcolor
			$bgColor = bgcolorc($i);

			if(strlen($accnt['accids']) > 0){
				$acc['accname'] = "<a href=\"javascript: openSmallWindow('multi-acc-popup.php?cashid=$accnt[cashid]&type=cash');\">Multiple Accounts</a>";
				$acc['accno'] = "";
			}else{
				# get account name for the account involved
				$AccRslt = get("core","accname, topacc, accnum","accounts", "accid", $accnt['accinv']);
				$acc = pg_fetch_array($AccRslt);
				$acc['accno'] = "$acc[topacc]/$acc[accnum]";
			}

			# get account name for bank account
			db_connect();
			$sql = "SELECT accname,btype FROM bankacct WHERE bankid= '$accnt[bankid]' AND div = '".USER_DIV."'";
			$bnameRslt = db_exec($sql);
			$bname = pg_fetch_array($bnameRslt);

			$ptotal += $accnt['amount']; //add to total
			$accnt['amount'] = sprint($accnt['amount']);
			$accnt['date'] = ext_rdate($accnt['date']);


			if($bname['btype']!="loc") {
				$ex = "/ $fc $accnt[famount]";
			} else {
				$ex = "";
			}

			$OUTPUT .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$accnt[date]</td>
				<td align='center'>$bname[accname]</td>
				<td align='center'>$accnt[cheqnum]</td>
				<td align='center'>$accnt[name]</td>
				<td>$accnt[descript]</td>
				<td>$accnt[reference]</td>
				<td>$acc[accno]  $acc[accname]</td>
				<td>".CUR." $accnt[amount] $ex</td>";

			if (!$pure && $accnt['banked'] == "no" && $accnt['opt'] != 'n'){
				$OUTPUT .= "<td><a href='../bank/cheq-return.php?cashid=$accnt[cashid]'>Returned/Unpaid</td>";
				// $OUTPUT .= "<td><a href='../bank/cheq-cancel.php?cashid=$accnt[cashid]'>Cancel</td>";
			}
			$OUTPUT .= "</tr>";
		}
		# print the total
		$OUTPUT .= "
		<tr bgcolor='".bgcolorg() ."'>
			<td colspan='6'><b>Total Payments</b></td>
			<td><b>".CUR." ".sprintf("%01.2f",$ptotal)."</b></td>
		</tr>";
	}

	if (!$pure) {
		$OUTPUT .= mkQuickLinks(
			ql("../core/acc-new2.php", "Add New Account"),
			ql("../core/acc-new2.php", "Add New Account (New Window)", true)
		);
	}

	if (isset($export)) {
		$OUTPUT = clean_html($OUTPUT);
		require_lib("xls");
		StreamXLS("Cashbook", $OUTPUT);
	}
	return $OUTPUT;

}


?>
