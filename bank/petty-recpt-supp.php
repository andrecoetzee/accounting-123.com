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

# get templete
require("../template.php");




# Insert details
function add()
{

	# Suppliers Drop down selections
	db_connect();

	$supp = "<select name='supid'>";
	$sql = "SELECT supid,supno,supname FROM suppliers WHERE location != 'int' AND div = '".USER_DIV."' ORDER BY supname,supno";
	$supRslt = db_exec($sql);
	if(pg_numrows($supRslt) < 1){
		return "<li> There are no Creditors in Cubit.</li>";
	}
	while($sup = pg_fetch_array($supRslt)){
		$supp .= "<option value='$sup[supid]'>$sup[supname] ($sup[supno])</option>";
	}
	$supp .= "</select>";

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
				<td>".CUR." $accb[bal]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Supplier Received from</td>
				<td valign='center'>$supp</td>
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




# Confirm
function confirm($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashacc, "num", 1, 30, "Invalid Petty Cash Account.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	if(strlen($date_year) <> 4){
		$v->isOk ($day, "num", 10, 1, "Invalid Date year.");
	}
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($supid, "num", 1, 20, "Invalid Supplier account.");
	$date = $date_day."-".$date_month."-".$date_year;

	$date_year += 0;
	$date_month += 0;
	$date_day += 0;

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
	$supRslt = get("cubit", "*", "suppliers", "supid", $supid);
	$sup = pg_fetch_array($supRslt);

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
			<input type='hidden' name='supid' value='$supid'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Petty Cash Account</td>
				<td>$acc[topacc]/$acc[accnum] $acc[accname]</td>
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
				<td valign='center'>$sup[supno] - $sup[supname]</td>
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

	# Processes
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
	$v->isOk ($supid, "num", 1, 20, "Invalid Supplier account.");

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



	# Date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	# Get account name
	$supRslt = get("cubit", "*", "suppliers", "supid", $supid);
	$sup = pg_fetch_array($supRslt);

	db_conn("exten");
	# Get debtors control account
	$sql = "SELECT credacc FROM departments WHERE deptid ='$sup[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec ($sql);
	$dept = pg_fetch_array($deptRslt);

	pglib_transaction("BEGIN");

	db_connect();
	$Sl = "
		INSERT INTO sup_stmnt (
			supid, amount, edate, descript,ref,cacc, div
		) VALUES (
			'$supid', '$amount', '$date', 'Receipt', '0', '$cashacc', '".USER_DIV."'
		)";
	$Rs = db_exec($Sl) or errDie("Unable to insert statement record in Cubit.",SELF);

	# Update the supplier (make balance less)
	$sql = "UPDATE suppliers SET balance = (balance + '$amount') WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	suppledger($supid, $cashacc, $date, 0, "Cash Receipt", $amount, "c");

	suppCT($amount, $supid);

	# Record tranfer for patty cash report
	$sql = "
		INSERT INTO pettyrec (
			date, type, det, amount, 
			name, div
		) VALUES (
			'$date', 'Transfer', '$descript', '$amount', 
			'Received from supplier: $sup[supname]', '".USER_DIV."'
		)";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

	$refnum = getrefnum();
	writetrans($cashacc, $dept['credacc'], $date, $refnum, $amount, $descript);

	pglib_transaction("COMMIT");

	# Status report
	$write = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>Petty Cash Receipt</th>
			</tr>
			<tr class='datacell'>
				<td>Petty Cash Receipt from supplier : $sup[supname] added to petty cash book.</td>
			</tr>
		</table>";

	# main table (layout with menu)
	$OUTPUT = "
		<center>
		<table width='90%'>
			<tr valign='top'>
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
		</table>
		</center>";
	return $OUTPUT;

}



?>
