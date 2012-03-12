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
if(isset($_POST["confirm"])) {
	$OUTPUT = confirm($_POST);
} elseif(isset($_POST["write"])) {
	$OUTPUT = write($_POST);
} elseif (isset($_POST["key"])) {
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

	// main layout
	$view = "
				<h3>View Cash Book</h3>
				<h4>Select Period</h4>
				<table ".TMPL_tblDflts." width='350'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='viewcash'>
					<input type='hidden' name='order' value=''>
					<tr>
						<th>Field</th>
						<th>Value</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Bank Account</td>
						<td valign='center'>
							<select name='bankid'>";

	db_connect();
	$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."' ORDER BY accname,bankname";
	$banks = db_exec($sql);
	$numrows = pg_numrows($banks);

	if(empty($numrows)){
		return "<li class='err'> There are no accounts held at the selected Bank.
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	while($acc = pg_fetch_array($banks)){
		$view .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}

	$view .= "
				</select>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>From :</td>
			<td valign='center'>".mkDateSelect("from",date("Y"),date("m"),"01")."</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>To :</td>
			<td valign='center'>".mkDateSelect("to")."</td>
		</tr>
		<tr>
			<td align='right'></td>
			<td align='right'><input type='submit' value='View &raquo'></td>
		</tr>
	</table>"
	.mkQuickLinks(
		ql("../core/acc-new2.php", "Add New Account")
	);
	return $view;

}



# view cash book
function viewcash($_POST)
{
	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($from_day, "num", 1, 2, "Invalid Day for the 'From' date.");
	$v->isOk ($from_month, "num", 1, 2, "Invalid month for the 'From' date..");
	$v->isOk ($from_year, "num", 1, 4, "Invalid year for the 'From' date..");
	$v->isOk ($to_day, "num", 1, 2, "Invalid Day for the 'To' date.");
	$v->isOk ($to_month, "num", 1, 2, "Invalid month for the 'To' date..");
	$v->isOk ($to_year, "num", 1, 4, "Invalid year for the 'To' date..");

	# lets mix the date
	$from = $from_day."-".$from_month."-".$from_year;
	$to = $to_day."-".$to_month."-".$to_year;

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



	# Get bank details
	$bankRslt = get("cubit", "accname,bankname,fcid", "bankacct", "bankid", $bankid);
	$bank = pg_fetch_array($bankRslt);

	$Sl="SELECT * FROM currency WHERE fcid='$bank[fcid]'";
	$Ry=db_exec($Sl) or errDie("Unable to get currency");

	if(pg_numrows($Ry)>0) {
		$curdata=pg_fetch_array($Ry);
		$fc=$curdata['symbol'];
	}

	$s1="";
	$s2="";
	$s3="";
	$s4="";
	$s5="";

	if(isset($order)) {
		if($order=="order by date desc, cheqnum asc") {
			$s2="selected";
 		} elseif($order=="order by date desc, cheqnum desc") {
			$s3="selected";
 		} elseif($order=="order by cheqnum asc") {
			$s4="selected";
 		} elseif($order=="order by cheqnum desc") {
			$s5="selected";
 		}  else {
			$s1="selected";
		}
	} else {
		$s1="selected";
	}
	// Set up table to display in

	# Receipts
	$OUTPUT = "
	<center>
	<h3>Batch Cash Book Entries<br><br>Account : $bank[accname] - $bank[bankname]<br>Period : $from to $to</h3>
	<table ".TMPL_tblDflts.">
	<form action='".SELF."' method='POST' name='form'>
		<input type='hidden' name='key' value='viewcash'>
		<input type='hidden' name='bankid' value='$bankid'>
		<input type='hidden' name='from_day' value='$from_day'>
		<input type='hidden' name='from_month' value='$from_month'>
		<input type='hidden' name='from_year' value='$from_year'>
		<input type='hidden' name='to_day' value='$to_day'>
		<input type='hidden' name='to_month' value='$to_month'>
		<input type='hidden' name='to_year' value='$to_year'>
		<tr>
			<th>Order By</th>
		</tr>
		<tr bgcolor='".bgcolorg() ."'>
			<td>
				<select name='order' onChange='javascript:document.form.submit();'>
					<option value='' disabled $s1 >Select</option>
					<option value='order by date desc, cheqnum asc' $s2>Date, Cheque No. Ascending</option>
					<option value='order by date desc, cheqnum desc' $s3>Date, Cheque No. Descending</option>
					<option value='order by cheqnum asc' $s4>Cheque No. Ascending</option>
					<option value='order by cheqnum desc' $s5>Cheque No. Descending</option>
				</select>
			</td>
		</tr>
	</table>
	<p>
	<table ".TMPL_tblDflts." width='95%'>
		<tr>
			<td colspan='7'><h4>Receipts</h4></td>
		</tr>
		<tr>
			<th> Date</th>
			<th>Bank Account Name</th>
			<th>Cheque Number</th>
			<th>Received From : </th>
			<th>Description</th>
			<th>Ledger Account</th>
			<th>Amount</th>
			<th>VAT</th>
			<th>VAT Code</th>
			<th colspan='2'>Options</th>
			<th>Process</th>
		</tr>";

	$rtotal = 0; // Received total amount

	// Connect to database
	db_Connect ();

	# date format
	$from = explode("-", $from);
	$from = $from[2]."-".$from[1]."-".$from[0];

	$to = explode("-", $to);
	$to = $to[2]."-".$to[1]."-".$to[0];

	if(!isset($order)) {
		$order="";
	}

	$sql = "SELECT * FROM batch_cashbook WHERE  date >= '$from' AND date <= '$to' AND trantype='deposit' AND bankid='$bankid' AND div = '".USER_DIV."' $order";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

	if ($numrows < 1) {
		$OUTPUT .= "
			<tr>
				<td colspan='12' align='center'><li class='err'>There are no batch Payments/cheques received for the selected period.</td></tr>";
	}else{
		# display all bank Deposits
		for ($i=0; $i < $numrows; $i++) {
			$accnt = pg_fetch_array ($accntRslt, $i);

			if(strlen($accnt['accids']) > 0){
				$acc['accname'] = "<a href=# onClick=openSmallWindow('multi-acc-popup.php?cashid=$accnt[cashid]')>Multiple Accounts</a>";
				//$acc['accname'] = "";
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

			if($accnt['chrgvat'] == "inc"){
				$showvat = "Incl";
			}elseif ($accnt['chrgvat'] == "exc"){
				$showvat = "Excl";
			}else {
				$showvat = "None";
			}

			#get vat code
			$get_vcod = "SELECT * FROM vatcodes WHERE id = '$accnt[vatcode]' LIMIT 1";
			$run_vcod = db_exec($get_vcod);
			if(pg_numrows($run_vcod) < 1){
				$vatc = "";
			}else {
				$varr = pg_fetch_array($run_vcod);
				$vatc = $varr['code'];
			}

			$OUTPUT .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$accnt[date]</td>
							<td align='center'>$bname[accname]</td>
							<td align='center'>$accnt[cheqnum]</td>
							<td align='center'>$accnt[name]</td>
							<td>$accnt[descript]</td>
							<td>$acc[accno]  $acc[accname]</td>
							<td>".CUR." $accnt[amount] $ex</td>
							<td>$showvat</td>
							<td>$vatc</td>";
			if($accnt['banked'] == "no" && $accnt['opt'] != 'n'){
				if($accnt['bt']=="receipt") {
					$OUTPUT .= "
							<td><a href='bank-recpt-edit.php?id=$accnt[cashid]'>Edit</td>";
				}elseif($accnt['bt'] == "" AND (strlen($accnt['accids']) > 0)){
					$OUTPUT .= "
							<td><a href='multi-bank-recpt-edit.php?id=$accnt[cashid]'>Edit</td>";
				}elseif($accnt['bt'] == ""){
					$OUTPUT .= "
							<td><a href='bank-recpt-edit.php?id=$accnt[cashid]'>Edit</td>";
				} else {
					$OUTPUT .= "
							<td></td>";
				}

				$OUTPUT .= "
							<td><a href='batch-entry-delete.php?id=$accnt[cashid]'>Delete</td>";
				if(isset($select)) {
					$ch="checked";
				} else {
					$ch="";
				}
				$OUTPUT .= "
							<td><input type='checkbox' name='pro[".$accnt['cashid']."]' $ch></td>";
				// $OUTPUT .= "<td><a href='../bank/cheq-cancel.php?cashid=$accnt[cashid]'>Cancel</td>";
			}
			$OUTPUT .= "
						</tr>";
		}
		# print the total
		$OUTPUT .= "
						<tr bgcolor='".bgcolorg() ."'>
							<td colspan='6'><b>Total Receipts</b></td>
							<td><b>".CUR." ".sprintf("%01.2f",$rtotal)."</b></td>
						</tr>";
	}

	# Seperate the tables with two rows
	$OUTPUT .= "
						<tr>
							<td colspan='7'><br></td>
						</tr>
						<tr>
							<td colspan='7'><br></td>
						</tr>";

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
			<th>Ledger Account</th>
			<th>Amount</th>
			<th>VAT</th>
			<th>VAT Code</th>
			<th colspan='2'>Options</th>
			<th>Process</th>
		</tr>";

	$ptotal = 0; // payments total

	// Connect to database
	db_Connect ();
	$sql = "SELECT * FROM batch_cashbook WHERE date >= '$from' AND date <= '$to' AND trantype='withdrawal' AND bankid='$bankid' AND div = '".USER_DIV."' $order";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);

	if (pg_numrows ($accntRslt) < 1) {
		$OUTPUT .= "<tr><td colspan='12' align='center'><li class='err'>There are batch no Payments made for the selected period.</td></tr>";
	}else{
		# Display all bank Deposits
		for ($i = 0; $accnt = pg_fetch_array ($accntRslt); $i++) {


			if(strlen($accnt['accids']) > 0){
				$acc['accname'] = "<a href=# onClick=openSmallWindow('multi-acc-popup.php?cashid=$accnt[cashid]')>Multiple Accounts</a>";
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

			if(isset($select)) {
				$ch="checked";
			} else {
				$ch="";
			}

			if($accnt['chrgvat'] == "inc"){
				$showvat = "Incl";
			}elseif ($accnt['chrgvat'] == "exc"){
				$showvat = "Excl";
			}else {
				$showvat = "None";
			}

			#get vat code
			$get_vcod = "SELECT * FROM vatcodes WHERE id = '$accnt[vatcode]' LIMIT 1";
			$run_vcod = db_exec($get_vcod);
			if(pg_numrows($run_vcod) < 1){
				$vatc = "";
			}else {
				$varr = pg_fetch_array($run_vcod);
				$vatc = $varr['code'];
			}

			$OUTPUT .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$accnt[date]</td>
							<td align='center'>$bname[accname]</td>
							<td align='center'>$accnt[cheqnum]</td>
							<td align='center'>$accnt[name]</td>
							<td>$accnt[descript]</td>
							<td>$acc[accno]  $acc[accname]</td>
							<td>".CUR." $accnt[amount] $ex</td>
							<td>$showvat</td>
							<td>$vatc</td>";
			if($accnt['banked'] == "no" && $accnt['opt'] != 'n'){
				if($accnt['bt']=="payment") {
					$OUTPUT .= "
							<td><a href='bank-pay-edit.php?id=$accnt[cashid]'>Edit</td>";
				} elseif($accnt['bt']=="transfer") {
					$OUTPUT .= "
							<td><a href='bank-trans-edit.php?id=$accnt[cashid]'>Edit</td>";
				}elseif($accnt['bt']=="") {
					if(strlen($accnt['accids']) > 0){
						$OUTPUT .= "
							<td><a href='multi-bank-pay-edit.php?id=$accnt[cashid]'>Edit</td>";
					}else {
						$OUTPUT .= "
							<td><a href='bank-pay-edit.php?id=$accnt[cashid]'>Edit</td>";
					}
				} else {
					$OUTPUT .= "
							<td></td>";
				}


				$OUTPUT .= "
							<td><a href='batch-entry-delete.php?id=$accnt[cashid]'>Delete</td>";
				$OUTPUT .= "
							<td><input type='checkbox' name=pro[".$accnt['cashid']."] $ch></td>";
				//$OUTPUT .= "<td><a href='../bank/cheq-return.php?cashid=$accnt[cashid]'>Returned/Unpaid</td>";
				// $OUTPUT .= "<td><a href='../bank/cheq-cancel.php?cashid=$accnt[cashid]'>Cancel</td>";
			}
			$OUTPUT .= "</tr>";
		}
		# print the total
		$OUTPUT .= "
				<tr bgcolor='".bgcolorg() ."'>
					<td colspan='6'><b>Total Payments</b></td><td><b>".CUR." ".sprintf("%01.2f",$ptotal)."</b></td>
				</tr>";
	}

	$OUTPUT .= "
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' name='select' value='Select All'></td>
				<td colspan='3' align='right'><input type='submit' name='confirm' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>"
	.mkQuickLinks(
		ql("../core/acc-new2.php", "Add New Account")
	);

	return $OUTPUT;
}

# view cash book
function confirm($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($from_day, "num", 1, 2, "Invalid Day for the 'From' date.");
	$v->isOk ($from_month, "num", 1, 2, "Invalid month for the 'From' date..");
	$v->isOk ($from_year, "num", 1, 4, "Invalid year for the 'From' date..");
	$v->isOk ($to_day, "num", 1, 2, "Invalid Day for the 'To' date.");
	$v->isOk ($to_month, "num", 1, 2, "Invalid month for the 'To' date..");
	$v->isOk ($to_year, "num", 1, 4, "Invalid year for the 'To' date..");

	# lets mix the date
	$from = $from_day."-".$from_month."-".$from_year;
	$to = $to_day."-".$to_month."-".$to_year;

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

	# Get bank details
	$bankRslt = get("cubit", "accname,bankname,fcid", "bankacct", "bankid", $bankid);
	$bank = pg_fetch_array($bankRslt);

	$Sl="SELECT * FROM currency WHERE fcid='$bank[fcid]'";
	$Ry=db_exec($Sl) or errDie("Unable to get currency");

	if(pg_numrows($Ry)>0) {
		$curdata=pg_fetch_array($Ry);
		$fc=$curdata['symbol'];
	}

	$s1="";
	$s2="";
	$s3="";
	$s4="";
	$s5="";

	$order="order by date desc, cheqnum asc";

	if(isset($order)) {
		if($order=="order by date desc, cheqnum asc") {
			$s2="selected";
 		} elseif($order=="order by date desc, cheqnum desc") {
			$s3="selected";
 		} elseif($order=="order by cheqnum asc") {
			$s4="selected";
 		} elseif($order=="order by cheqnum desc") {
			$s5="selected";
 		}  else {
			$s1="selected";
		}
	} else {
		$s1="selected";
	}
	// Set up table to display in
	# Receipts
	$OUTPUT = "
		<center>
		<h3>Batch Cash Book Entries<br><br>Account : $bank[accname] - $bank[bankname]<br>Period : $from to $to</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='viewcash'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='from_day' value='$from_day'>
			<input type='hidden' name='from_month' value='$from_month'>
			<input type='hidden' name='from_year' value='$from_year'>
			<input type='hidden' name='to_day' value='$to_day'>
			<input type='hidden' name='to_month' value='$to_month'>
			<input type='hidden' name='to_year' value='$to_year'>
		</table>
	<p>
	<table ".TMPL_tblDflts." width='95%'>
		<tr>
			<td colspan='7'><h4>Receipts</h4></td>
		</tr>
		<tr>
			<th> Date</th>
			<th>Bank Account Name</th>
			<th>Cheque Number</th>
			<th>Received From : </th>
			<th>Description</th>
			<th>Ledger Account</th>
			<th>Amount</th>
		</tr>";

	$rtotal = 0; // Received total amount

	// Connect to database
	db_Connect ();

	# date format
	$from = explode("-", $from);
	$from = $from[2]."-".$from[1]."-".$from[0];

	$to = explode("-", $to);
	$to = $to[2]."-".$to[1]."-".$to[0];

	$sql = "SELECT * FROM batch_cashbook WHERE  date >= '$from' AND date <= '$to' AND trantype='deposit' AND bankid='$bankid' AND div = '".USER_DIV."' $order";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

	if ($numrows < 1) {
		//$OUTPUT .= "<tr><td colspan=7 align=center><li class=err>There are no batch Payments/cheques received for the selected period.</td></tr>";
	}else{
		# display all bank Deposits
		for ($i=0; $i < $numrows; $i++) {
			$accnt = pg_fetch_array ($accntRslt, $i);

			if(!isset($pro[$accnt['cashid']])) {
				continue;
			}

			if(strlen($accnt['accids']) > 0){
				$acc['accname'] = "<a href=# onClick=openSmallWindow('multi-acc-popup.php?cashid=$accnt[cashid]')>Multiple Accounts</a>";
				//$acc['accname'] = "";
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

			$OUTPUT .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$accnt[date]</td>
						<td align='center'>$bname[accname]</td>
						<td align='center'>$accnt[cheqnum]</td>
						<td align='center'>$accnt[name]</td>
						<td>$accnt[descript]</td>
						<td>$acc[accno]  $acc[accname]</td>
						<td>".CUR." $accnt[amount] $ex</td>";
			if($accnt['banked'] == "no" && $accnt['opt'] != 'n'){
				//$OUTPUT .= "<td><a href='batch-enytry-delete.php?id=$accnt[cashid]'>Delete</td>";
				$OUTPUT .= "<input type=hidden name=pro[".$accnt['cashid']."] value='1'>";
				// $OUTPUT .= "<td><a href='../bank/cheq-cancel.php?cashid=$accnt[cashid]'>Cancel</td>";
			}
			$OUTPUT .= "</tr>";
		}
		# print the total
		$OUTPUT .= "
				<tr bgcolor='".bgcolorg() ."'>
					<td colspan='6'><b>Total Receipts</b></td>
					<td><b>".CUR." ".sprintf("%01.2f",$rtotal)."</b></td>
				</tr>";
	}

	# Seperate the tables with two rows
	$OUTPUT .= "
			<tr>
				<td colspan='7'><br></td>
			</tr>
			<tr>
				<td colspan='7'><br></td>
			</tr>";

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
			<th>Ledger Account</th>
			<th>Amount</th>
		</tr>";

	$ptotal = 0; // payments total

	// Connect to database
	db_Connect ();
	$sql = "SELECT * FROM batch_cashbook WHERE date >= '$from' AND date <= '$to' AND trantype='withdrawal' AND bankid='$bankid' AND div = '".USER_DIV."' $order";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);

	if (pg_numrows ($accntRslt) < 1) {
		//$OUTPUT .= "<tr><td colspan=7 align=center><li class=err>There are batch no Payments made for the selected period.</td></tr>";
	}else{
		# Display all bank Deposits
		for ($i = 0; $accnt = pg_fetch_array ($accntRslt); $i++) {

			if(!isset($pro[$accnt['cashid']])) {
				continue;
			}

			if(strlen($accnt['accids']) > 0){
				$acc['accname'] = "<a href=# onClick=openSmallWindow('multi-acc-popup.php?cashid=$accnt[cashid]')>Multiple Accounts</a>";
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
						<td>$acc[accno]  $acc[accname]</td>
						<td>".CUR." $accnt[amount] $ex</td>";
			if($accnt['banked'] == "no" && $accnt['opt'] != 'n'){
				//$OUTPUT .= "<td><a href='batch-enytry-delete.php?id=$accnt[cashid]'>Delete</td>";
				$OUTPUT .= "<input type=hidden name=pro[".$accnt['cashid']."] value=1>";
				//$OUTPUT .= "<td><a href='../bank/cheq-return.php?cashid=$accnt[cashid]'>Returned/Unpaid</td>";
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

	$OUTPUT .= "
			<tr><td><br></td></tr>
			<tr><td colspan='5' align='right'><input type='submit' name='write' value='Write &raquo;'></td></tr>
		</table>"
		.mkQuickLinks(
			ql("../core/acc-new2.php", "Add New Account")
		);

	return $OUTPUT;
}


# view cash book
function write($_POST)
{

	# get vars
	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($from_day, "num", 1, 2, "Invalid Day for the 'From' date.");
	$v->isOk ($from_month, "num", 1, 2, "Invalid month for the 'From' date..");
	$v->isOk ($from_year, "num", 1, 4, "Invalid year for the 'From' date..");
	$v->isOk ($to_day, "num", 1, 2, "Invalid Day for the 'To' date.");
	$v->isOk ($to_month, "num", 1, 2, "Invalid month for the 'To' date..");
	$v->isOk ($to_year, "num", 1, 4, "Invalid year for the 'To' date..");

	# lets mix the date
	$from = $from_day."-".$from_month."-".$from_year;
	$to = $to_day."-".$to_month."-".$to_year;

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



	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	# Get bank details
	$bankRslt = get("cubit", "accname,bankname,fcid", "bankacct", "bankid", $bankid);
	$bank = pg_fetch_array($bankRslt);

	$Sl="SELECT * FROM currency WHERE fcid='$bank[fcid]'";
	$Ry=db_exec($Sl) or errDie("Unable to get currency");

	if(pg_numrows($Ry)>0) {
		$curdata=pg_fetch_array($Ry);
		$fc=$curdata['symbol'];
	}

	$s1="";
	$s2="";
	$s3="";
	$s4="";
	$s5="";

	$order="order by date desc, cheqnum asc";

	if(isset($order)) {
		if($order=="order by date desc, cheqnum asc") {
			$s2="selected";
 		} elseif($order=="order by date desc, cheqnum desc") {
			$s3="selected";
 		} elseif($order=="order by cheqnum asc") {
			$s4="selected";
 		} elseif($order=="order by cheqnum desc") {
			$s5="selected";
 		}  else {
			$s1="selected";
		}
	} else {
		$s1="selected";
	}
	// Set up table to display in
	# Receipts
	$OUTPUT = "
	<center>
	<h3>Batch Cash Book Entries<br><br>Account : $bank[accname] - $bank[bankname]<br>Period : $from to $to</h3>
	<table ".TMPL_tblDflts.">
	<form action='".SELF."' method='POST' name='form'>
		<input type='hidden' name='key' value='viewcash'>
		<input type='hidden' name='bankid' value='$bankid'>
		<input type='hidden' name='from_day' value='$from_day'>
		<input type='hidden' name='from_month' value='$from_month'>
		<input type='hidden' name='from_year' value='$from_year'>
		<input type='hidden' name='to_day' value='$to_day'>
		<input type='hidden' name='to_month' value='$to_month'>
		<input type='hidden' name='to_year' value='$to_year'>
	</table>
	<p>
	<table ".TMPL_tblDflts." width='95%'>
		<tr>
			<td colspan='7'><h4>Receipts</h4></td>
		</tr>
		<tr>
			<th> Date</th>
			<th>Bank Account Name</th>
			<th>Cheque Number</th>
			<th>Received From : </th>
			<th>Description</th>
			<th>Ledger Account</th>
			<th>Amount</th>
		</tr>";

	$rtotal = 0; // Received total amount


	// Connect to database
	db_Connect ();

	# date format
	$from = explode("-", $from);
	$from = $from[2]."-".$from[1]."-".$from[0];

	$to = explode("-", $to);
	$to = $to[2]."-".$to[1]."-".$to[0];

	$vatacc = gethook("accnum", "salesacc", "name", "VAT");

	# Get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
	}
	$banklnk = pg_fetch_array($rslt);

	db_Connect ();
	$sql = "SELECT * FROM batch_cashbook WHERE  date >= '$from' AND date <= '$to' AND trantype='deposit' AND bankid='$bankid' AND div = '".USER_DIV."' $order";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

	if ($numrows < 1) {
		//$OUTPUT .= "<tr><td colspan='7' align='center'><li class='err'>There are no batch Payments/cheques received for the selected period.</td></tr>";
	}else{
		for ($i=0; $i < $numrows; $i++) {
			$accnt = pg_fetch_array ($accntRslt, $i);
			if(!isset($pro[$accnt['cashid']])) {
				continue;
			}

		//	if($accnt['bt']=="receipt") {

				$refnum = getrefnum();

				if (strlen($accnt['accids']) > 0) {
					$accids = explode("|", $accnt['accids']);
					$vatcodes = explode("|", $accnt['vatcodes']);
					$amounts = explode("|", $accnt['amounts']);
					$vats = explode("|", $accnt['vats']);
					$chrgvats = explode("|", $accnt['chrgvats']);
					$refnum = getrefnum();
					$descript = $accnt['descript'];
					//$date = date("Y-m-d");
					$date = $accnt['date'];

					foreach($amounts as $key => $amount){
						# SQL Array Rule: Thou shalt skip Zero Reference
						if($key < 1)
							continue;

						$accid = $accids[$key];
						$vat = $vats[$key];
						$chrgvat = $chrgvats[$key];
						$amount -= $vat;
						$vatcode = $vatcodes[$key];

						db_conn('cubit');

						$Sl="SELECT * FROM vatcodes WHERE id='$vatcode'";
						$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

						$vd=pg_fetch_array($Ri);

						if($accnt['trantype'] != "deposit"){
							$vatacc = gethook("accnum", "salesacc", "name", "VAT","a");
							# DT(account involved), CT(bank)
//							writetrans($accid, $banklnk['accnum'], $date, $refnum, ($amount-$vat), $descript);
							writetrans($accid, $banklnk['accnum'], $date, $refnum, $amount, $descript);

							if($vat <> 0){
								# DT(Vat), CT(Bank)
								writetrans($vatacc, $banklnk['accnum'], $date, $refnum, $vat, $descript);
								vatr($vd['id'],$accnt['date'],"INPUT",$vd['code'],$refnum,$accnt['descript'],-$amount,-$vat);
							}
						}else{
							$vatacc = gethook("accnum", "salesacc", "name", "VAT");
							# DT(bank), CT(account invoilved)
//							writetrans($banklnk['accnum'], $accid, $date, $refnum, ($amount-$vat), $descript);
							writetrans($banklnk['accnum'], $accid, $date, $refnum, $amount, $descript);

							if($vat <> 0){
								# DT(Vat), CT(Bank)
								vatr($vd['id'], $accnt['date'], "OUTPUT", $vd['code'], $refnum, $accnt['descript'], $amount, $vat);
								writetrans($banklnk['accnum'], $vatacc, $date, $refnum, $vat, $descript);
							}
						}
					}

					db_connect();

					$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accids, amounts, chrgvats, vats, reference, div) VALUES ('$accnt[bankid]', 'deposit', '$accnt[date]', '$accnt[name]', '$accnt[descript]', '$accnt[cheqnum]', '$accnt[amount]', 'no', '$accnt[accids]', '$accnt[amounts]', '$accnt[chrgvats]', '$accnt[vats]', '$accnt[reference]', '".USER_DIV."')";
					$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

					db_connect();
					$Sl="DELETE FROM batch_cashbook WHERE cashid='$accnt[cashid]'";
					$Ri=db_exec($Sl);
					
				} else {

					db_conn('cubit');
					$Sl="SELECT * FROM vatcodes WHERE id='$accnt[vatcode]'";
					$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

					$vd=pg_fetch_array($Ri);


					# record the payment record
					db_connect();
					$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, vat, chrgvat, banked, accinv, div, vatcode, reference) VALUES ('$bankid', 'deposit', '$accnt[date]', '$accnt[name]', '$accnt[descript]', '$accnt[cheqnum]', '$accnt[amount]', '$accnt[vat]', '$accnt[chrgvat]', 'no', '$accnt[accinv]', '".USER_DIV."', '$accnt[vatcode]', '$accnt[reference]')";
					$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

					vatr($vd['id'],$accnt['date'],"OUTPUT",$vd['code'],$refnum,$accnt['descript'],($accnt['amount']),$accnt['vat']);

					# DT(account involved), CT(bank)
					writetrans($banklnk['accnum'], $accnt['accinv'], $accnt['date'], $refnum, ($accnt['amount']-$accnt['vat']), $accnt['descript']);

					if($accnt['vat'] <> 0){
						# DT(Vat), CT(Bank)
						writetrans($banklnk['accnum'], $vatacc, $accnt['date'], $refnum, $accnt['vat'], $accnt['descript']);
					}
		//		}


					db_connect();
					$Sl="DELETE FROM batch_cashbook WHERE cashid='$accnt[cashid]'";
					$Ri=db_exec($Sl);

					continue;


					if(strlen($accnt['accids']) > 0){
						$acc['accname'] = "<a href=# onClick=openSmallWindow('multi-acc-popup.php?cashid=$accnt[cashid]')>Multiple Accounts</a>";
						$acc['accname'] = "";
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

					$OUTPUT .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$accnt[date]</td>
								<td align='center'>$bname[accname]</td>
								<td align='center'>$accnt[cheqnum]</td>
								<td align='center'>$accnt[name]</td>
								<td>$accnt[descript]</td>
								<td>$acc[accno]  $acc[accname]</td>
								<td>".CUR." $accnt[amount] $ex</td>";
					if($accnt['banked'] == "no" && $accnt['opt'] != 'n'){
						//$OUTPUT .= "<td><a href='batch-enytry-delete.php?id=$accnt[cashid]'>Delete</td>";
						$OUTPUT .= "<input type='hidden' name='pro[".$accnt['cashid']."]' value='1'>";
						// $OUTPUT .= "<td><a href='../bank/cheq-cancel.php?cashid=$accnt[cashid]'>Cancel</td>";
					}
					$OUTPUT .= "</tr>";
				}
		}
		# print the total
		$OUTPUT .= "
			<tr bgcolor='".bgcolorg() ."'>
				<td colspan='6'><b>Total Receipts</b></td>
				<td><b>".CUR." ".sprintf("%01.2f",$rtotal)."</b></td>
			</tr>";
	}

	# Seperate the tables with two rows
	$OUTPUT .= "
					<tr>
						<td colspan='7'><br></td>
					</tr>
					<tr>
						<td colspan='7'><br></td>
					</tr>";


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
			<th>Ledger Account</th>
			<th>Amount</th>
		</tr>";

	$vatacc = gethook("accnum", "salesacc", "name", "VAT");

	# Get hook account number
	core_connect();
	$sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# Check if link exists
	if(pg_numrows($rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
	}
	$banklnk = pg_fetch_array($rslt);

	$ptotal = 0; // payments total



	// Connect to database
	db_Connect ();
	$sql = "SELECT * FROM batch_cashbook WHERE date >= '$from' AND date <= '$to' AND trantype='withdrawal' AND bankid='$bankid' AND div = '".USER_DIV."' $order";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank deposits details from database.", SELF);

	if (pg_numrows ($accntRslt) < 1) {
		//$OUTPUT .= "<tr><td colspan=7 align=center><li class=err>There are batch no Payments made for the selected period.</td></tr>";
	}else{
		# Display all bank payments
		for ($i = 0; $accnt = pg_fetch_array ($accntRslt); $i++) {
			if(!isset($pro[$accnt['cashid']])) {
				continue;
			}
			
			if ($accnt['bt'] == "payment") {
				$refnum = getrefnum();

				if(strlen($accnt['accids']) > 0) {
					$accids = explode("|", $accnt['accids']);
					$vatcodes = explode("|", $accnt['vatcodes']);
					$amounts = explode("|", $accnt['amounts']);
					$vats = explode("|", $accnt['vats']);
					$chrgvats = explode("|", $accnt['chrgvats']);
					$refnum = getrefnum();
					$descript = $accnt['descript'];
					//$date = date("Y-m-d");
					$date = $accnt['date'];

					foreach($amounts as $key => $amount){

						# SQL Array Rule: Thou shalt skip Zero Reference
						if($key < 1)
							continue;

						$accid = $accids[$key];
						$vat = $vats[$key];
						$chrgvat = $chrgvats[$key];
						$amount -= $vat;
						$vatcode = $vatcodes[$key];

						db_conn('cubit');
						$Sl="SELECT * FROM vatcodes WHERE id='$vatcode'";
						$Ri=db_exec($Sl) or errDie("Unable to get vat codes");
		
						$vd=pg_fetch_array($Ri);

						if($accnt['trantype'] != "deposit"){
							$vatacc = gethook("accnum", "salesacc", "name", "VAT","a");
							# DT(account involved), CT(bank)
//							writetrans($accid, $banklnk['accnum'], $date, $refnum, $amount-$vat, $descript);
							writetrans($accid, $banklnk['accnum'], $date, $refnum, $amount, $descript);

							if($vat <> 0){
								# DT(Vat), CT(Bank)
								writetrans($vatacc, $banklnk['accnum'], $date, $refnum, $vat, $descript);
								vatr($vd['id'],$accnt['date'],"INPUT",$vd['code'],$refnum,$accnt['descript'],-$amount,-$vat);
							}
						}else{
							$vatacc = gethook("accnum", "salesacc", "name", "VAT");
							# DT(bank), CT(account invoilved)
							writetrans($banklnk['accnum'], $accid, $date, $refnum, $amount, $descript);

							if($vat <> 0){
								# DT(Vat), CT(Bank)
								writetrans($banklnk['accnum'], $vatacc, $date, $refnum, $vat, $descript);
								vatr($vd['id'], $accnt['date'], "OUTPUT", $vd['code'], $refnum, $accnt['descript'], $amount, $vat);
							}
						}
					}

					db_connect();
					$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accids, amounts, chrgvats, vats, div, vatcode, reference) VALUES ('$accnt[bankid]', 'withdrawal', '$accnt[date]', '$accnt[name]', '$accnt[descript]', '$accnt[cheqnum]', '$accnt[amount]', 'no', '$accnt[accids]', '$accnt[amounts]', '$accnt[chrgvats]', '$accnt[vats]', '".USER_DIV."','$accnt[vatcode]', '$accnt[reference]')";
					$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

				} else {
					db_conn('cubit');
					$Sl="SELECT * FROM vatcodes WHERE id='$accnt[vatcode]'";
					$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

					$vd=pg_fetch_array($Ri);


					# Record the payment record
					db_connect();
					$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, vat, chrgvat, banked, accinv, div, vatcode, reference) VALUES ('$bankid', 'withdrawal', '$accnt[date]', '$accnt[name]', '$accnt[descript]', '$accnt[cheqnum]', '$accnt[amount]', '$accnt[vat]', '$accnt[chrgvat]', 'no', '$accnt[accinv]', '".USER_DIV."','$accnt[vatcode]', '$accnt[reference]')";
					$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

					vatr($vd['id'],$accnt['date'],"INPUT",$vd['code'],$refnum,$accnt['descript'],-$accnt['amount'],-$accnt['vat']);

					# DT(account involved), CT(bank)
					writetrans($accnt['accinv'], $banklnk['accnum'], $accnt['date'], $refnum, ($accnt['amount']-$accnt['vat']), $accnt['descript']);

					if($accnt['vat'] <> 0){
						# DT(Vat), CT(Bank)
						writetrans($vatacc, $banklnk['accnum'], $accnt['date'], $refnum, $accnt['vat'], $accnt['descript']);
					}
				}

				db_connect();
				$Sl="DELETE FROM batch_cashbook WHERE cashid='$accnt[cashid]'";
				$Ri=db_exec($Sl);

				continue;

				if(strlen($accnt['accids']) > 0){
					$acc['accname'] = "<a href=# onClick=openSmallWindow('multi-acc-popup.php?cashid=$accnt[cashid]')>Multiple Accounts</a>";
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
							<td>$acc[accno]  $acc[accname]</td>
							<td>".CUR." $accnt[amount] $ex</td>";
				if($accnt['banked'] == "no" && $accnt['opt'] != 'n'){
					//$OUTPUT .= "<td><a href='batch-enytry-delete.php?id=$accnt[cashid]'>Delete</td>";
					$OUTPUT .= "<input type='hidden' name='pro[".$accnt['cashid']."]' value='1'>";
					//$OUTPUT .= "<td><a href='../bank/cheq-return.php?cashid=$accnt[cashid]'>Returned/Unpaid</td>";
					// $OUTPUT .= "<td><a href='../bank/cheq-cancel.php?cashid=$accnt[cashid]'>Cancel</td>";
				}
				$OUTPUT .= "</tr>";
			} elseif($accnt['bt']=="transfer") {

				$refnum = getrefnum();

				extract($accnt);

				db_connect();
				$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
				$fbankRslt = db_exec($sql);
				$fbank = pg_fetch_array($fbankRslt);

				$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$rid' AND div = '".USER_DIV."'";
				$tbankRslt = db_exec($sql);
				$tbank = pg_fetch_array($tbankRslt);

				$faccid = getbankaccid($bankid);
				$taccid = getbankaccid($rid);

				# write trans
				writetrans($taccid, $faccid, $date, $refnum, $amount, $descript);

				# Record the payment record
				db_connect();
				$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, div, reference) VALUES ('$bankid', 'withdrawal', '$date', '$tbank[accname] - $tbank[bankname]', '$descript', '$cheqnum', '$amount', 'no', '$taccid', '".USER_DIV."', '$reference')";
				$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

				$lcashid = pglib_lastid("cashbook", "cashid");

				$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, div, reference) VALUES ('$rid', 'deposit', '$date', '$fbank[accname] - $fbank[bankname]', '$descript', '$cheqnum', '$amount', 'no', '$faccid', '".USER_DIV."', '$reference')";
				$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

				$lcashid2 = pglib_lastid("cashbook", "cashid");

				# restore link
				$sql = "UPDATE cashbook SET lcashid = '$lcashid2' WHERE cashid = '$lcashid'";
				$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

				$sql = "UPDATE cashbook SET lcashid = '$lcashid' WHERE cashid = '$lcashid2'";
				$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

				db_connect();
				$Sl="DELETE FROM batch_cashbook WHERE cashid='$accnt[cashid]'";
				$Ri=db_exec($Sl);
			} else {
				$refnum = getrefnum();

				if(strlen($accnt['accids']) > 0) {
					$accids = explode("|", $accnt['accids']);
					$vatcodes = explode("|", $accnt['vatcodes']);
					$amounts = explode("|", $accnt['amounts']);
					$vats = explode("|", $accnt['vats']);
					$chrgvats = explode("|", $accnt['chrgvats']);
					$refnum = getrefnum();
					$descript = $accnt['descript'];
					$date = $accnt["date"];

					foreach($amounts as $key => $amount){
						# SQL Array Rule: Thou shalt skip Zero Reference
						if($key < 1)
							continue;

						$accid = $accids[$key];
						$vat = $vats[$key];
						$chrgvat = $chrgvats[$key];
						//$amount -= $vat;
						$vatcode = $vatcodes[$key];
						
						db_conn('cubit');
						$Sl="SELECT * FROM vatcodes WHERE id='$vatcode'";
						$Ri=db_exec($Sl) or errDie("Unable to get vat codes");
		
						$vd=pg_fetch_array($Ri);

						if($accnt['trantype'] != "deposit"){
							$vatacc = gethook("accnum", "salesacc", "name", "VAT","a");
							# DT(account involved), CT(bank)
							writetrans($accid, $banklnk['accnum'], $date, $refnum, $amount-$vat, $descript);

							if($vat <> 0){
								# DT(Vat), CT(Bank)
								writetrans($vatacc, $banklnk['accnum'], $date, $refnum, $vat, "$descript");
								vatr($vd['id'],$accnt['date'],"INPUT",$vd['code'],$refnum,$accnt['descript'],-$amount,-$vat);
							}
						}else{
							$vatacc = gethook("accnum", "salesacc", "name", "VAT");
							# DT(bank), CT(account invoilved)
							writetrans($banklnk['accnum'], $accid, $date, $refnum, $amount-$vat, $descript);

							if($vat <> 0){
								# DT(Vat), CT(Bank)
								writetrans($banklnk['accnum'], $vatacc, $date, $refnum, $vat, "$descript");
								vatr($vd['id'], $accnt['date'], "OUTPUT", $vd['code'], $refnum, $accnt['descript'], $amount, $vat);
							}
						}
					}

					db_connect();
					$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accids, amounts, chrgvats, vats, div, vatcode, reference) VALUES ('$accnt[bankid]', 'withdrawal', '$accnt[date]', '$accnt[name]', '$accnt[descript]', '$accnt[cheqnum]', '$accnt[amount]', 'no', '$accnt[accids]', '$accnt[amounts]', '$accnt[chrgvats]', '$accnt[vats]', '".USER_DIV."','$accnt[vatcode]', '$accnt[reference]')";
					$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

				} else {
					db_conn('cubit');
					$Sl="SELECT * FROM vatcodes WHERE id='$accnt[vatcode]'";
					$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

					$vd=pg_fetch_array($Ri);


					# Record the payment record
					db_connect();
					$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, vat, chrgvat, banked, accinv, div, vatcode, reference) VALUES ('$bankid', 'withdrawal', '$accnt[date]', '$accnt[name]', '$accnt[descript]', '$accnt[cheqnum]', '$accnt[amount]', '$accnt[vat]', '$accnt[chrgvat]', 'no', '$accnt[accinv]', '".USER_DIV."','$accnt[vatcode]', '$accnt[reference]')";
					$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

					vatr($vd['id'],$accnt['date'],"INPUT",$vd['code'],$refnum,$accnt['descript'],-$accnt['amount'],-$accnt['vat']);

					# DT(account involved), CT(bank)
					writetrans($accnt['accinv'], $banklnk['accnum'], $accnt['date'], $refnum, ($accnt['amount']-$accnt['vat']), $accnt['descript']);

					if($accnt['vat'] <> 0){
						# DT(Vat), CT(Bank)
						writetrans($vatacc, $banklnk['accnum'], $accnt['date'], $refnum, $accnt['vat'], $accnt['descript']);
					}
				}

				db_connect();
				$Sl="DELETE FROM batch_cashbook WHERE cashid='$accnt[cashid]'";
				$Ri=db_exec($Sl);

				continue;

				if(strlen($accnt['accids']) > 0){
					$acc['accname'] = "<a href=# onClick=openSmallWindow('multi-acc-popup.php?cashid=$accnt[cashid]')>Multiple Accounts</a>";
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

				if ($bname['btype']!="loc") {
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
							<td>$acc[accno]  $acc[accname]</td>
							<td>".CUR." $accnt[amount] $ex</td>";
				if($accnt['banked'] == "no" && $accnt['opt'] != 'n'){
					//$OUTPUT .= "<td><a href='batch-enytry-delete.php?id=$accnt[cashid]'>Delete</td>";
					$OUTPUT .= "<input type='hidden' name='pro[".$accnt['cashid']."]' value='1'>";
					//$OUTPUT .= "<td><a href='../bank/cheq-return.php?cashid=$accnt[cashid]'>Returned/Unpaid</td>";
					// $OUTPUT .= "<td><a href='../bank/cheq-cancel.php?cashid=$accnt[cashid]'>Cancel</td>";
				}
				$OUTPUT .= "</tr>";
			}
		}
		# print the total
		$OUTPUT .= "
			<tr bgcolor='".bgcolorg() ."'>
				<td colspan='6'><b>Total Payments</b></td>
				<td><b>".CUR." ".sprintf("%01.2f",$ptotal)."</b></td>
			</tr>";
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	$OUTPUT= "
				<table ".TMPL_tblDflts." width='25%'>
					<tr>
						<th>Done</th>
					</tr>
					<tr class='datacell'>
						<td>Batch entries have been processed.</td>
					</tr>
				</table><br>"
				.mkQuickLinks(
					ql("../core/acc-new2.php", "Add New Account")
				);
	return $OUTPUT;

}


?>