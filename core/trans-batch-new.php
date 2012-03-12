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

# trans-new.php :: debit-credit Transaction
#
##

# get settings
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		case "details":
			if(isset($_POST['details'])){
				$OUTPUT = details($_POST);
			}else{
				$OUTPUT = details2($_POST);
			}
			break;
		default:
			$OUTPUT = slctacc();
	}
} else {
	# Display default output
	$OUTPUT = slctacc();
}

# get templete
require("template.php");




# Select Accounts
function slctacc()
{

	extract ($_POST);

	// connect
	db_conn(PRD_DB);

	# get last ref number
	if (!isset($refnum))
		$refnum = getrefnum();
		/*refnum*/

// 	if (!isset($date_year)){
// 		$date_year = date ("Y");
// 		$date_month = date ("m");
// 		$date_day = date ("d");
// 	}

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

	// Accounts (debit)
	$view = "
		<center>
		<h3>Add Journal transaction to batch</h3>
		<br><br>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='details'>
		<table ".TMPL_tblDflts." align='center'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference Number</td>
				<td><input type='text' size='10' name='refnum' value='".($refnum++)."'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='center'>
					<table ".TMPL_tblDflts."'>
						<tr>
							<td><h4>Debit</h4></td>
						</tr>
						<tr>
							<th>Select Account <input align='right' type='button' onClick=\"window.open('acc-new2.php?update_parent=yes&set_key=begin','accounts','width=700, height=400');\" value='New Account'></th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='center'>";

	core_connect();

	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li>There are No accounts in Cubit.</li>";
	}

	$view .= mkAccSelect ("dtaccid",$dtaccid);

// 	$view .= "<select name='dtaccid'>";
// 	while($acc = pg_fetch_array($accRslt)){
// 		# Check Disable
// 		if(isDisabled($acc['accid']))
// 			continue;
// 		if (isset($dtaccid) AND $dtaccid == $acc['accid']){
// 			$view .= "<option value='$acc[accid]' selected>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
// 		}else {
// 			$view .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
// 		}
// 	}
// 	$view .= "</select>";

	$view .= "
					</td>
				</tr>
			</table>
		</td>
		<td align='center'>
			<table ".TMPL_tblDflts.">
				<tr>
					<td><h4>Credit</h4></td>
				</tr>
				<tr>
					<th>Select Account <input align='right' type='button' onClick=\"window.open('acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td valign='center'>";

	$view .= mkAccSelect ("ctaccid",$ctaccid);

// 	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
// 	$accRslt = db_exec($sql);
// 	if(pg_numrows($accRslt) < 1){
// 		return "<li>There are No accounts in Cubit.</li>";
// 	}

// 	$view .= "<select name='ctaccid'>";
// 	while($acc = pg_fetch_array($accRslt)){
// 		# Check Disable
// 		if(isDisabled($acc['accid']))
// 			continue;
// 		if (isset($ctaccid) AND $ctaccid == $acc['accid']){
// 			$view .= "<option value='$acc[accid]' selected>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
// 		}else {
// 			$view .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
// 		}
// 	}
// 	$view .= "</select>";

	$view .= "
							</td>
							<td><input name='details' type='submit' value='Enter Details >'></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<br><br><br>
		<table ".TMPL_tblDflts." align='center'>
			<tr>
				<td align='center'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td><h4>Debit</h4></td>
						</tr>
						<tr>
							<th>Account number</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='center'><input type='text' name='dtaccnum' size='20'></td>
						</tr>
					</table>
				</td>
				<td align='center'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td><h4>Credit</h4></td>
						</tr>
						<tr>
							<th>Account number</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign='center'><input type='text' name='ctaccnum' size='20'></td>
							<td><input type='submit' value='Enter Details >'></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<br>
		<input type='button' value='< Go Back' onClick='javascript:history.back();'>
		</form>
		<table border='0' cellpadding='2' cellspacing='1' width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='trans-new.php'>Journal Transactions</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
		</center>";
	return $view;

}



# Enter Details of Transaction
function details($_POST)
{

// Sanity Checking
	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($date_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid to Date Year.");
	$date = $date_day."-".$date_month."-".$date_year;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");

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

	// account numbers
	$dtaccRs = get("core","*","accounts","accid",$dtaccid);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$ctaccid);
	$ctacc  = pg_fetch_array($ctaccRs);

	// Deatils
	$details = "
		<h3>Add Journal transaction to batch</h3>
		<h4>Enter Details</h4>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='ctaccid' value='$ctaccid'>
			<input type='hidden' name='dtaccid' value='$dtaccid'>
		<table ".TMPL_tblDflts." width='500'>
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference No.</td>
				<td valign='center'><input type='text' size='20' name='refnum' value='$refnum'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR."<input type='text' size='20' name='amount'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Charge VAT </td>
				<td><input type='radio' name='chrgvat' value='yes'>Yes &nbsp;&nbsp; <input type='radio' name='chrgvat' value='no' checked='yes'>No</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Transaction Details</td>
				<td valign='center'><textarea cols='20' rows='5' name='details'></textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Person Authorising</td>
				<td valign='center'><input type='hidden' size='20' name='author' value=".USER_NAME.">".USER_NAME."</td>
			</tr>
			<tr>
				<td><input type='button' value='Back' OnClick='javascript:history.back()'></td>
				<td valign='center'><input type='submit' value='Record Transaction'></td>
			</tr>
		</table>
		</form>
		<table border='0' cellpadding='2' cellspacing='1' width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='trans-new.php'>Journal Transactions</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $details;

}



# Enter Details of Transaction
function details2($_POST)
{

	// Sanity Checking
	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($date_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid to Date Year.");
	$date = $date_day."-".$date_month."-".$date_year;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	$v->isOk ($dtaccnum, "string", 1, 50, "Invalid Account number  to be Debited.");
	$v->isOk ($ctaccnum, "string", 1, 50, "Invalid Account number to be Credited.");

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

	$dtaccnum = explode("/", rtrim($dtaccnum));
	$ctaccnum = explode("/", rtrim($ctaccnum));

	if(count($dtaccnum) < 2){
		// account numbers
		$dtaccRs = get("core","*","accounts","topacc",$dtaccnum[0]."' AND accnum = '000");
		if(pg_numrows($dtaccRs) < 1){
			return "<li> Accounts number : $dtaccnum[0] does not exist";
		}
		$dtacc  = pg_fetch_array($dtaccRs);
	}else{
		// account numbers
		$dtaccRs = get("core","*","accounts","topacc","$dtaccnum[0]' AND accnum = '$dtaccnum[1]");
		if(pg_numrows($dtaccRs) < 1){
			return "<li> Accounts number : $dtaccnum[0]/$dtaccnum[1] does not exist";
		}
		$dtacc  = pg_fetch_array($dtaccRs);
	}

	if(count($ctaccnum) < 2){
		# get top level account
		$ctaccRs = get("core","*","accounts","topacc",$ctaccnum[0]."' AND accnum = '000");
		if(pg_numrows($ctaccRs) < 1){
			return "<li> Accounts number : $ctaccnum[0] does not exist";
		}
		$ctacc  = pg_fetch_array($ctaccRs);
	}else{
		# get low level account
		$ctaccRs = get("core","*","accounts","topacc","$ctaccnum[0]' AND accnum = '$ctaccnum[1]");
		if(pg_numrows($ctaccRs) < 1){
			return "<li> Accounts number : $ctaccnum[0]/$ctaccnum[1] does not exist";
		}
		$ctacc  = pg_fetch_array($ctaccRs);
	}

	// Details
	$details = "
		<h3>Add Journal transaction to batch</h3>
		<h4>Enter Details</h4>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='ctaccid' value='$ctacc[accid]'>
			<input type='hidden' name='dtaccid' value='$dtacc[accid]'>
		<table ".TMPL_tblDflts." width='500'>
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference No.</td>
				<td valign='center'><input type='text' size='20' name='refnum' value='$refnum'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR."<input type='text' size='20' name='amount'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Charge VAT </td>
				<td><input type='radio' name='chrgvat' value='yes'>Yes &nbsp;&nbsp; <input type='radio' name='chrgvat' value='no' checked='yes'>No</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Transaction Details</td>
				<td valign='center'><textarea cols='20' rows='5' name='details'></textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Person Authorising</td>
				<td valign='center'><input type='hidden' size='20' name='author' value=".USER_NAME.">".USER_NAME."</td>
			</tr>
			<tr>
				<td><input type='button' value='Back' OnClick='javascript:history.back()'></td>
				<td valign='center'><input type='submit' value='Record Transaction'></td>
			</tr>
		</table>
		</form>
		<table border='0' cellpadding='2' cellspacing='1' width=15%>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='trans-new.php'>Journal Transactions</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $details;

}



# Select vat accounts
function slctVatAcc($_POST)
{

	# Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
	$v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");

	$datea = explode("-", $date);

	if(count($datea) == 3){
		if(!checkdate($datea[1], $datea[0], $datea[2])){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
		}
	}else{
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



	# account numbers
	$dtaccRs = get("core","*","accounts","accid",$dtaccid);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$ctaccid);
	$ctacc  = pg_fetch_array($ctaccRs);

	db_conn('core');
	$vatacc = "<select name='vataccid'>";
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li>There are No accounts in Cubit.";
	}
	while($acc = pg_fetch_array($accRslt)){
		# Check Disable
		if(isDisabled($acc['accid']))
			continue;
		$vatacc .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
	}
	$vatacc .= "</select>";

	db_conn('cubit');

	if(!isset($vatcode)) {
		$Sl = "SELECT * FROM vatcodes WHERE del='Yes'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		$vatcode = $vd['id'];
	}

	if(!isset($vatcode)) {
		$vatcode = 0;
	}

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes = "<select name='vatcode'>";
	while($vd = pg_fetch_array($Ri)) {
		if($vd['id'] == $vatcode) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
	}
	$Vatcodes .= "</select>";

	// Details
	$slctacc = "
		<center>
		<h3> Record Journal transaction to batch </h3>
		<h2>Select VAT Accounts</h2>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='dtaccid' value='$dtaccid'>
			<input type='hidden' name='ctaccid' value='$ctaccid'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='refnum' value='$refnum'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='chrgvat' value='$chrgvat'>
			<input type='hidden' name='details' value='$details'>
			<input type='hidden' name='author' value='$author'>
		<table ".TMPL_tblDflts." align='center'>
			<tr>
				<th>Option</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>VAT Deductable Account</td>
				<td><input type='radio' name='vatdedacc' value='$dtaccid' checked='yes'>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]<br><input type='radio' name='vatdedacc' value='$ctaccid'>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Account</td>
				<td>$vatacc</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Inclusive </td>
				<td><input type='radio' size='20' name='vatinc' value='yes' checked='yes'>Yes(Amount Includes VAT) &nbsp;&nbsp;<input type='radio' size='20' name='vatinc' value='no'>No(Add VAT to Amount)</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Code</td>
				<td>$Vatcodes</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Continue &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='trans-new.php'>Journal Transactions</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slctacc;

}



# Confirm
function confirm($_POST)
{

	// Sanity Checking
	# Get vars
	extract ($_POST);

	# Redirect if must chrgvat
	if($chrgvat == 'yes' && !isset($vataccid)){
		return slctVatAcc($_POST);
	}

	if(isb($dtaccid)) {
		return "<li class='err'>You selected a main account.</li>".slctacc($_POST);
	}

	if(isb($ctaccid)) {
		return "<li class='err'>You selected a main account.</li>".slctacc($_POST);
	}

	if(isset($vatcode)) {
		$vatcode += 0;
	} else {
		$vatcode = 0;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
	$v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");

	$datea = explode("-", $date);

	if(count($datea) == 3){
		if(!checkdate($datea[1], $datea[0], $datea[2])){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
		}
	}else{
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}
	if($chrgvat == 'yes'){
		$v->isOk ($vataccid, "num", 1, 50, "Invalid VAT Account number.");
		$v->isOk ($vatdedacc, "num", 1, 50, "Invalid VAT Deductable Account number.");
		$v->isOk ($vatinc, "string", 1, 3, "Invalid vat inclusive selection.");
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

	$dtaccRs = get("core","*","accounts","accid",$dtaccid);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$ctaccid);
	$ctacc  = pg_fetch_array($ctaccRs);

	# show vat account if vat is charged
	if($chrgvat == 'yes'){
		$vataccRs = get("core","*","accounts","accid",$vataccid);
		$vatacc  = pg_fetch_array($vataccRs);
		$vatin = ucwords($vatinc);
		$vataccnum = "
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Account</td>
				<td><input type='hidden' name='vataccid' value='$vataccid'><input type='hidden' name='vatdedacc' value='$vatdedacc'>$vatacc[topacc]/$vatacc[accnum] - $vatacc[accname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Inclusive</td>
				<td><input type='hidden' name='vatinc' value='$vatinc'>$vatin</td>
			</tr>";
	}else{
		$vataccnum = "";
	}

	$vat = ucwords($chrgvat);

	if($vatcode > 0) {
		db_conn('cubit');
		$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode'";
		$Ri = db_exec($Sl) or errDie("unable to get data.");

		$va = pg_fetch_array($Ri);

		$vd = "
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Code</td>
				<td>$va[code]</td>
			</tr>";
	} else {
		$vd = "";
	}


	# Processes
	$confirm = "
		<h3>Record Journal transaction to batch</h3>
		<h4>Confirm entry</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='vatcode' value='$vatcode'>
			<input type='hidden' name='dtaccid' value='$dtaccid'>
			<input type='hidden' name='ctaccid' value='$ctaccid'>
			<input type='hidden' name='dtaccname' value='$dtacc[accname]'>
			<input type='hidden' name='ctaccname' value='$ctacc[accname]'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='refnum' value='$refnum'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='chrgvat' value='$chrgvat'>
			<input type='hidden' name='details' value='$details'>
			<input type='hidden' name='author' value='$author'>
		<table ".TMPL_tblDflts." width='500'>
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Reference number</td>
				<td>$refnum</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td>".CUR." $amount</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Charge VAT </td>
				<td>$vat</td>
			</tr>
			$vataccnum
			$vd
			<tr bgcolor='".bgcolorg()."'>
				<td>Details</td><td>$details</td></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Authorising Person</td><td>$author</td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Confirm Transaction &raquo'></td>
			</tr>
		</table>
		</form>
		<table border='0' cellpadding='2' cellspacing='1' width=15%>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='trans-new.php'>Journal Transactions</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}




# Write
function write($_POST)
{

// Sanity Checking and get vars(Respectively)
        # Get vars
	extract ($_POST);

	$vatcode += 0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
	$v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");
	if($chrgvat == 'yes'){
		$v->isOk ($vataccid, "num", 1, 50, "Invalid VAT Account number.");
		$v->isOk ($vatdedacc, "num", 1, 50, "Invalid VAT Deductable Account number.");
		$v->isOk ($vatinc, "string", 1, 3, "Invalid vat inclusive selection.");
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

	// Accounts details
	$dtaccRs = get("core","*","accounts","accid",$dtaccid);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$ctaccid);
	$ctacc  = pg_fetch_array($ctaccRs);

	# Format date
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	# Check/Insert the records into the batch table
	core_connect();
	if($chrgvat == 'yes'){
		$sql = "
			INSERT INTO batch (
				date, debit, credit, refnum, amount, author, 
				details, proc, chrgvat, vatinc, vataccid, vatdedacc, 
				div, vatcode
			) VALUES (
				'$date', '$dtaccid', '$ctaccid', '$refnum', '$amount', '$author', 
				'$details', 'no', '$chrgvat', '$vatinc', '$vataccid', '$vatdedacc', 
				'".USER_DIV."', '$vatcode'
			)";
		$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);
	}else{
		$sql = "
			INSERT INTO batch (
				date, debit, credit, refnum, amount, author, 
				details, proc, chrgvat, div, vatcode
			) VALUES (
				'$date', '$dtaccid', '$ctaccid', '$refnum', '$amount', '$author', 
				'$details', 'no', '$chrgvat', '".USER_DIV."','$vatcode'
			)";
		$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);
	}

	# Start layout
	$write = "
		<center>
		<h3>Journal transaction have been recorded to a batch file</h3>
		<table ".TMPL_tblDflts." width='500'>
			<tr>
				<td width='50%'><h3>Debit</h3></td>
				<td width='50%'><h3>Credit</h3></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td>
				<td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td>
			</tr>
			<tr><td><br></td></tr>
			<tr colspan='2'>
				<td><h4>Amount</h4></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><b>".CUR." $amount</b></td>
			</tr>
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
				<td align='center'><a href='trans-batch-new.php'>Add Journal Transaction to batch</td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='batch-view.php'>View batch file</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



?>
