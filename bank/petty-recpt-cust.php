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

# Decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = add();
	}
} else {
	# Display default output
	$OUTPUT = add();
}

# Get templete
require("../template.php");




# Insert details
function add()
{

	# Suppliers Drop down selections
	db_connect();

	$sql = "SELECT cusnum,cusname,surname FROM customers WHERE location != 'int' AND div = '".USER_DIV."' ORDER BY surname,cusname";
	$cusRslt = db_exec($sql);
	$numrows = pg_numrows($cusRslt);
	if(empty($numrows)){
		return "<li> There are no Customers in Cubit.</li>";
	}

	$cust = "<select name='cusid'>";
	while($cus = pg_fetch_array($cusRslt)){
		$cust .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
	}
	$cust .= "</select>";

	core_connect();

	# Get Petty cash account
	$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");

	# Get account name for thy lame User's Sake
	$accRslt = get("core", "*", "accounts", "accid", $cashacc);
	if(pg_numrows($accRslt) < 1){
		return "<li class='err'> Petty Cash Account not found.</li>";
	}
	$acc = pg_fetch_array($accRslt);

	# Check available funds
	$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");

	core_connect();

	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE month='".PRD_DB."' AND accid = '$cashacc' AND div = '".USER_DIV."'";
	$accbRslt = db_exec($sql);
	if(pg_numrows($accbRslt) < 1){
		return "<li class='err'> Petty Cash Account not found.</li>";
	}
	$accb = pg_fetch_array($accbRslt);
	$accb['bal'] = sprint($accb['bal']);

	if (!isset ($date_day)){
		$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
		if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
			$trans_date_value = getCSetting ("TRANSACTION_DATE");
			$date_arr = explode ("-", $trans_date_value);
			$date_year = $date_arr[0];
			$date_month = $date_arr[1];
			$date_day = $date_arr[2];
		}else {
			$date_year = date("Y");
			$date_month = date("m");
			$date_day = date("d");
		}
	}

	// layout
	$add = "
		<h3>New Petty Cash Receipt</h3>
		<table ".TMPL_tblDflts." width='100%'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='cashacc' value='$cashacc'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Petty Cash Account</td>
				<td valign='center'>$acc[topacc]/$acc[accnum] $acc[accname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Available Funds</td>
				<td>".CUR." $accb[bal]<input type='hidden' name='max' value='$accb[bal]'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Customer received from</td>
				<td valign='center'>$cust</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td valign='center'><textarea col='20' rows='5' name='descript'></textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." <input type='text' size='10' name='amount'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='&laquo; Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Add &raquo;'></td>
			</tr>
		</form>
		</table>";

	# main table (layout with menu)
	$OUTPUT = "
		<center>
		<table width='100%'>
			<tr>
				<td width='65%' align='left'>$add</td>
				<td valign='top' align='center'>
					<table ".TMPL_tblDflts." width='65%'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
			</tr>
		</table>";
	return $OUTPUT;

}



# confirm
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashacc, "num", 1, 30, "Invalid Petty Cash Account.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	if(strlen($date_year) <> 4){
		$v->isOk ($bankname, "num", 1, 1, "Invalid Date year.");
	}
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($cusid, "num", 1, 20, "Invalid customer account.");
	$date = $date_day."-".$date_month."-".$date_year;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
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


	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	# Get account name
	$supRslt = get("cubit", "*", "customers", "cusnum", $cusid);
	$cus = pg_fetch_array($supRslt);

	# Get account name for thy lame User's Sake
	$accRslt = get("core", "*", "accounts", "accid", $cashacc);
	if(pg_numrows($accRslt) < 1){
		return "<li class='err'> Petty Cash Account not found.</li>";
	}
	$acc = pg_fetch_array($accRslt);

	# Check available funds
	core_connect();
	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE month='".PRD_DB."' AND accid = '$cashacc' AND div = '".USER_DIV."'";
	$accbRslt = db_exec($sql);
	if(pg_numrows($accbRslt) < 1){
		return "<li class='err'> Petty Cash Account not found.</li>";
	}
	$accb = pg_fetch_array($accbRslt);
	$accb['bal'] = sprint($accb['bal']);

	$confirm = "
					<center>
					<h3>New Petty Cash Receipt</h3>
					<h4>Confirm entry (Please check the details)</h4>
					<table ".TMPL_tblDflts." width='60%'>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='cashacc' value='$cashacc'>
						<input type='hidden' name='date' value='$date'>
						<input type='hidden' name='descript' value='$descript'>
						<input type='hidden' name='amount' value='$amount'>
						<input type='hidden' name='cusid' value='$cusid'>
						<tr>
							<th>Field</th>
							<th>Value</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Petty Cash Account</td>
							<td valign='center'>$acc[topacc]/$acc[accnum] $acc[accname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Available Funds</td>
							<td>".CUR." $accb[bal]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Date</td>
							<td valign='center'>$date</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Received from</td>
							<td valign='center'>$cus[accno] - $cus[surname]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Description</td>
							<td valign='center'>$descript</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Amount</td>
							<td valign='center'>".CUR." $amount</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
							<td align='right'><input type='submit' value='Confirm &raquo'></td>
						</tr>
					</form>
					</table>
					<p>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Quick Links</th>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
	return $confirm;

}



# write
function write($HTTP_POST_VARS)
{

	# processes
	db_connect();

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashacc, "num", 1, 30, "Invalid Petty Cash Account.");
	$v->isOk ($date, "date", 1,10, "Invalid Date Entry.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($cusid, "num", 1, 20, "Invalid customer account.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	# Get account name
	$cusRslt = get("cubit", "*", "customers", "cusnum", $cusid);
	$cus = pg_fetch_array($cusRslt);

	db_conn("exten");
	# get debtors control account
	$sql = "SELECT debtacc FROM departments WHERE deptid ='$cus[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec ($sql);
	$dept = pg_fetch_array($deptRslt);

	pglib_transaction("BEGIN");

	custledger($cusid, $cashacc, $date, '0', "Customer Cash Receipt", $amount, "c");

	db_connect();
	$Sl = "
		INSERT INTO stmnt 
			(cusnum, invid, amount, date, type, div, allocation_date) 
		VALUES 
			('$cusid', '0', '-$amount', '$date', 'Cash Receipt', '".USER_DIV."', '$date')";
	$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

	$sql = "UPDATE customers SET balance = (balance - '$amount'::numeric(13,2)) WHERE cusnum = '$cus[cusnum]' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	custCT($amount, $cus['cusnum']);

	# Record tranfer for patty cash report
	$sql = "INSERT INTO pettyrec(date, type, det, amount, name, div) VALUES ('$date', 'Transfer', '$descript', '$amount', 'Received from customer: $cus[cusname] $cus[surname]', '".USER_DIV."')";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

	$refnum = getrefnum();
	writetrans($cashacc, $dept['debtacc'], $date, $refnum, $amount, $descript);

	pglib_transaction("COMMIT");

	# status report
	$write = "
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<th>Petty Cash Receipt</th>
					</tr>
					<tr class='datacell'>
						<td>Petty Cash Receipt from customer : $cus[surname] added to petty cash book.</td>
					</tr>
				</table>";

	# main table (layout with menu)
	$OUTPUT = "
					<center>
					<table width='90%'>
						<tr valign=top>
							<td width='50%'>$write</td>
							<td align='center'>
								<table ".TMPL_tblDflts." width='80%'>
									<tr>
										<th>Quick Links</th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='bank-pay-add.php'>Add Petty Cash Payment</a></td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='bank-recpt-add.php'>Add Petty Cash Receipt</a></td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='cashbook-view.php'>View Cash Book</a></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>";
	return $OUTPUT;

}


?>
