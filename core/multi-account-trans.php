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
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			if (!isset ($_POST["confirm"]))
				$OUTPUT = slctacc($HTTP_POST_VARS);
			else 
				$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		case "details":
			$OUTPUT = details($HTTP_POST_VARS);
			break;
		case "details2":
			$OUTPUT = details2($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slctacc($HTTP_POST_VARS);
	}
} else {
    # Display default output
    $OUTPUT = slctacc($HTTP_POST_VARS);
}

# get templete
require("template.php");




# Select Accounts
function slctacc($HTTP_POST_VARS, $err="")
{

	extract($HTTP_POST_VARS);

	$translist = "";

	if (!isset ($total)) 
		$total = 1;
	if (!isset($refnum))
		$refnum = getrefnum();

	$jump_bot = "";
	if (isset ($more)) {
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
		$total++;
	}

	if (!isset($date_year)){
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

	for($i=0; $i != $total; $i++){

		core_connect();

		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.</li>";
		}

		$dtaccid[$i] += 0;
		$dtamount[$i] += 0;
		$ctamount[$i] += 0;

		$translist .= "
			<tr bgcolor=".bgcolorg().">
				<td valign='center'>".mkAccSelect ("accid[]", $accid[$i])."</td>
				<td><input type='text' size='20' name='descript[]' value='$descript[$i]'></td>
				<td><input type='text' size='7' name='dtamount[]' value='$dtamount[$i]'></td>
				<td><input type='text' size='7' name='ctamount[]' value='$ctamount[$i]'></td>
			</tr>";
	}

	$translist .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2' align='right'><b>Total:</b></td>
			<td>".CUR." ".sprint(array_sum ($dtamount))."</td>
			<td>".CUR." ".sprint(array_sum ($ctamount))."</td>
		</tr>";

	// Accounts (debit)
	$view = "
		<center>
		<h3>Process Multiple Account Journal Transactions</h3>
		$err
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='total' value='$total'>
		<table ".TMPL_tblDflts." align='center'>
			<tr>
				<th>Transaction Date</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>".mkDateSelect ("date", $date_year, $date_month, $date_day)."</td>
			</tr>
			<tr>
				<th>Select Contra Account</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".mkAccSelect("account", $account)."</td>
			</tr>
			<tr>
				<th>Reference Number</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><input type='text' size='5' name='refnum' value='$refnum'></td>
			</tr>
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Account <input align='right' type='button' onClick=\"window.open('acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
				<th>Description</th>
				<th>Debit</th>
				<th>Credit</th>
			</tr>
			$translist
			<tr>
				<td colspan='3'><input type='submit' name='more' value='Add Another'></td>
			</tr>
			<tr>
				<td valign='center' colspan='4' align='right'><input type='submit' name='confirm' value='Confirm &raquo;'></td>
			</tr>
		</table>
		</form>
		<a name='bottom'>
		$jump_bot
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='batch-view.php'>View batch Entries</td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='trans-new.php'>Journal Transaction</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $view;

}


# Confirm
function confirm($HTTP_POST_VARS)
{

    # Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$date = $date_year."-".$date_month."-".$date_day;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($account, "num", 1, 50, "Invalid Account to be used as contra.[$key]");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($date) >= strtotime($blocked_date_from) AND strtotime($date) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	foreach($accid as $key => $value){

		$dtamount[$key] += 0;
		$ctamount[$key] += 0;

		if($dtamount[$key] > 0 || $ctamount[$key] > 0){
			$v->isOk ($accid[$key], "num", 1, 50, "Invalid Account to be Debited.[$key]");
			$v->isOk ($dtamount[$key], "float", 1, 20, "Invalid Debit Amount.[$key]");
			$v->isOk ($ctamount[$key], "float", 1, 20, "Invalid Credit Amount.[$key]");
			$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
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

	# get contra account
	$accRs = get("core", "*", "accounts", "accid", $account);
	if(pg_numrows($accRs) < 1){
		return "<li> Accounts to be debited does not exist.</li>";
	}
	$account_info = pg_fetch_array($accRs);

	# accnums
	foreach($accid as $key => $value){
		if($dtamount[$key] > 0 || $ctamount[$key] > 0){
			# get account to be debited
			$accRss = get("core", "*", "accounts", "accid", $accid[$key]);
			if(pg_numrows($accRs) < 1){
				return "<li> Accounts to be debited does not exist.</li>";
			}
			$accs[$key] = pg_fetch_array($accRss);
		}
	}

	$confirm = "
		<center>
		<h3>Add Multiple Journal transactions to batch</h3>
		<h4>Confirm entry</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='date_year' value='$date_year'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='refnum' value='$refnum'>
			<input type='hidden' name='account' value='$account'>
			<input type='hidden' name='total' value='$total'>
			<input type='hidden' name='key' value='write'>
		<table ".TMPL_tblDflts." width='590'>
			<tr>
				<th>Transaction Date</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$date_year-$date_month-$date_day</td>
			</tr>
			<tr>
				<th>Select Contra Account</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$account_info[accname]</td>
			</tr>
			<tr>
				<th>Reference Number</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$refnum</td>
			</tr>
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts." width='590'>
			<tr>
				<th>Account</th>
				<th>Description</th>
				<th>Debit</th>
				<th>Credit</th>
			</tr>";

	$trans = "";
	foreach($accid as $key => $value){
		if($dtamount[$key] > 0 || $ctamount[$key] > 0){

			$accRss = get("core", "*", "accounts", "accid", $accid[$key]);
			if(pg_numrows($accRs) < 1){
				return "<li> Accounts to be debited does not exist.</li>";
			}
			$acc_each[$key] = pg_fetch_array($accRss);

			$trans .= "
				<input type='hidden' name='accid[]' value='$accid[$key]'>
				<input type='hidden' name='descript[]' value ='$descript[$key]'>
				<input type='hidden' name='dtamount[]' value='$dtamount[$key]'>
				<input type='hidden' name='ctamount[]' value='$ctamount[$key]'>
				<tr bgcolor=".bgcolorg().">
					<td valign='center'>".$acc_each[$key]['accname']."</td>
					<td>$descript[$key]</td>
					<td nowrap>".CUR." ".sprint($dtamount[$key])."</td>
					<td nowrap>".CUR." ".sprint($ctamount[$key])."</td>
				</tr>";
		}
	}

	if(strlen($trans) < 5){
		return slctacc($HTTP_POST_VARS,"<li class='err'>Please enter full transaction details</li><br>");
	}

	$confirm .= "
			$trans
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right' colspan='3'><input type='submit' value='Write &raquo'></td>
			</tr>
		</form>
		</table>
		<table border='0' cellpadding='2' cellspacing='1' width=15%>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='batch-view.php'>View batch file</td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='trans-new.php'>Journal Transaction</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}


# Write
function write($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	if(isset($back)) {
		return slctacc($HTTP_POST_VARS);
	}


	# validate input
	require_lib("validate");
	$v = new  validate ();

	$date = $date_year."-".$date_month."-".$date_day;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	foreach($accid as $key => $value){

		$dtamount[$key] += 0;
		$ctamount[$key] += 0;

		if($dtamount[$key] > 0 || $ctamount[$key] > 0){
			$v->isOk ($accid[$key], "num", 1, 50, "Invalid Account to be Debited.[$key]");
			$v->isOk ($dtamount[$key], "float", 1, 20, "Invalid Debit Amount.[$key]");
			$v->isOk ($ctamount[$key], "float", 1, 20, "Invalid Credit Amount.[$key]");
			$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class='err'>".$e["msg"]."</li>";
		}
		$write .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}


	foreach($accid as $key => $value){

		# begin sql transaction
		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		if ($dtamount[$key] > 0){
			writetrans($accid[$key], $account, $date, $refnum, $dtamount[$key], $descript[$key]);
		}elseif ($ctamount[$key] > 0) {
			writetrans($account, $accid[$key], $date, $refnum, $ctamount[$key], $descript[$key]);
		}

		pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);

	}

	// Start layout
	$write = "
		<center>
		<h3>Journal transactions have been recorded.</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Account</th>
				<th>Description</th>
				<th>Debit</th>
				<th>Credit</th>
			</tr>";

	foreach($accid as $key => $value){
		if($dtamount[$key] > 0 || $ctamount[$key] > 0){

			core_connect ();

			$accRss = get("core", "*", "accounts", "accid", $accid[$key]);
			if(pg_numrows($accRss) < 1){
				return "<li> Accounts to be debited does not exist.</li>";
			}
			$acc_each[$key] = pg_fetch_array($accRss);

			$write .= "
				<tr bgcolor=".bgcolorg().">
					<td valign='center'>".$acc_each[$key]['accname']."</td>
					<td>$descript[$key]</td>
					<td nowrap>".CUR." ".sprint($dtamount[$key])."</td>
					<td nowrap>".CUR." ".sprint($ctamount[$key])."</td>
				</tr>";
		}
	}

	$write .= "
		</table>
		<br>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='trans-new.php'>Journal Transaction</td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='batch-view.php'>View batch file</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


?>
