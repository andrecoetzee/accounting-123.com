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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
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

	# mourn if the is no money
	if($accb['bal'] < 1){
		return "
			<li class='err'> There are no Petty Cash funds available.</li>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='petty-trans.php'>Transfer funds to petty cash account</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
			</table>";
	}

    // Layout
    $add = "
		<h3>Funds transfer to Bank</h3>
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank Account</td>
				<td valign='center'>
					<select name='bankid'>";

	db_connect();

    $sql = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."'";
    $banks = db_exec($sql);
    if(pg_numrows($banks) < 1){
		return "<li class='err'> There are no accounts held at the selected Bank.</li>
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
    }
    while($bacc = pg_fetch_array($banks)){
		$add .= "<option value='$bacc[bankid]'>($bacc[acctype]) $bacc[accname] - $bacc[bankname]</option>";
    }

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

    $add .= "
					</select>
				</td>
			</tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Date</td>
	        	<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Received from</td>
	        	<td valign='center'><input size='20' name='name' value='Petty Cash'></td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Description</td>
	        	<td valign='center'><textarea cols='18' rows='2' name='descript'></textarea></td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Cheque Number</td>
	        	<td valign='center'><input size='10' name='cheqnum'></td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Amount</td>
	        	<td valign='center'>".CUR." <input type='text' size='10' name='amount'>&nbsp;&nbsp;Max : ".CUR." $accb[bal]<input type='hidden' name='max' value='$accb[bal]'></td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Petty Cash Account</td>
	        	<td><input type='hidden' name='accinv' value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
	        </tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='< Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Confirm >'></td>
			</tr>
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
function confirm($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	if(strlen($date_year) <> 4){
		$v->isOk ("##", "num", 1, 1, "Invalid Date year.");
	}
	if($amount > $max){
		$v->isOk ("##", "num", 1, 1, "ERROR : amount is more than available petty cash funds.");
	}
	$v->isOk ($name, "string", 1, 255, "Invalid Name Received from.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($accinv, "num", 1, 20, "Invalid Account type (account involved).");
	$date = $date_day."-".$date_month."-".$date_year;

	$date_day += 0;
	$date_month += 0;
	$date_year += 0;

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


	# Get bank account name
	db_connect();

	$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	# get account name
	$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accinv);
	$accnt = pg_fetch_array($accRslt);

	// Layout
	$confirm = "
		<h3>Funds transfer to Bank</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='name' value='$name'>
			<input type='hidden' name='descript' value='$descript'>
			<input type='hidden' name='cheqnum' value='$cheqnum'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='accinv' value='$accinv'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank Account</td>
				<td>$bank[accname] - $bank[bankname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Received from</td>
				<td valign='center'>$name</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Description</td>
				<td valign='center'>$descript</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cheque Number</td>
				<td valign='center'>$cheqnum</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." $amount</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Petty Cash Account</td>
				<td valign='center'>$accnt[topacc]/$accnt[accnum] - $accnt[accname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Write &raquo'></td>
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
function write($_POST)
{

	# Get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($date, "date", 1,10, "Invalid Date Entry.");
	$v->isOk ($name, "string", 1, 255, "Invalid Person/Business Received from/received from.");
	$v->isOk ($descript, "string", 0, 255, "Invalid Description.");
	$v->isOk ($cheqnum, "num", 0, 30, "Invalid Cheque number.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($accinv, "string", 1, 255, "Invalid account number (account involved).");

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

	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	# nasty zero
	$cheqnum += 0;

	db_connect();

	$sql = "SELECT accname, bankname FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);

	# Some info
	$bankacc = getbankaccid($bankid);

	pglib_transaction("BEGIN");
	$refnum = getrefnum();

	# write trans
	writetrans($bankacc, $accinv, $date, $refnum, $amount, $descript);

	# Record the payment record
	db_connect();

	$sql = "
		INSERT INTO cashbook (
			bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, div
		) VALUES (
			'$bankid', 'deposit', '$date', '$name', '$descript', '$cheqnum', '$amount', 'no', '$accinv', '".USER_DIV."'
		)";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

	# Record tranfer for patty cash report
	$sql = "
		INSERT INTO pettyrec (
			date, type, det, amount, name, div
		) VALUES (
			'$date', 'Req', '$descript', '-$amount', 'Transfer To Bank Account : $bank[accname] - $bank[bankname]', '".USER_DIV."'
		)";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

	pglib_transaction("COMMIT");

	# Status report
	$write = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th>Funds transfer to Bank</th>
			</tr>
			<tr class='datacell'>
				<td>Petty Cash Funds transfer to Bank has been added to the petty cash book.</td>
			</tr>
		</table>";

	# Main table (layout with menu)
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
							<td><a href='cashbook-view.php'>View Cash Book</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>
				</td>
			</tr>
		</table>";
	return $OUTPUT;

}


?>
