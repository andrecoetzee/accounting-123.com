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

##
# trans-new.php :: Multiple debit-credit Transactions
##

# get settings
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "slct":
			$OUTPUT = slctacc($HTTP_POST_VARS);
			break;
		case "confirm":
			if (isset ($_REQUEST["another"])){
				$OUTPUT = slctacc($HTTP_POST_VARS);
			}else {
				$OUTPUT = confirm($HTTP_POST_VARS);
			}
			break;
		case "cconfirm":
			$OUTPUT = cconfirm($HTTP_POST_VARS);
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
			$OUTPUT = number();
	}
} else {
	# Display default output
	$OUTPUT = number();
}

# Get templete
require("template.php");




function number()
{

	global $HTTP_POST_VARS;
	extract($HTTP_POST_VARS);

	if(!isset($vby)) {
		$vby = "";
	}

	if(!isset($tnum)) {
		$tnum = 1;
	}

	if($vby == "topacc,accnum") {
		$c1 = "checked=yes";
		$c2 = "";
	} else {
		$c1 = "";
		$c2 = "checked=yes";
	}

	# layout
	$number = "
		<h3>Journal transactions</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='slct'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Accounts by</td>
				<td align='right'><input type='radio' name='vby' value='topacc,accnum' $c1>Account No. | <input type='radio' name='vby' value='accname' $c2>Account Name</td>
			</tr>
			<tr>
				<td></td>
				<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $number;

}



# Select Accounts
function slctacc($HTTP_POST_VARS, $err="")
{

	# Get vars
	extract ($HTTP_POST_VARS);

	if (!isset ($tnum))
		$tnum = 1;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($tnum, "num", 1, 3, "Invalid Number of transactions.");
	if($tnum < 1){
		$v->isOk ("#error#", "num", 1, 1, " - Number of transactions must be at least one.");
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


	$jump_bot = "";
	if (isset ($another)) {
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
		$tnum++;
	}


	if (!isset ($refnum))
		$refnum = getrefnum();
		/*refnum*/

	// Accounts (debit)
	$view = "
		<center>
		<h3> Journal transactions </h3>
		$err
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='vby' value='$vby'>
			<input type='hidden' name='tnum' value='$tnum'>
		<table ".TMPL_tblDflts." align='center'>
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit <input align='right' type='button' onClick=\"window.open('acc-new2.php?update_parent=yes&set_key=slct','accounts','width=700, height=400');\" value='New Account'></th>
				<th>Credit <input align='right' type='button' onClick=\"window.open('acc-new2.php?update_parent=yes&set_key=slct','accounts','width=700, height=400');\" value='New Account'></th>
				<th>Amount</th>
				<th>Description</th>
			</tr>";

	for($i=0; $i < $tnum; $i++){

		if (!isset ($date_day[$i])){
			$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
			if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
				$trans_date_value = getCSetting ("TRANSACTION_DATE");
				$date_arr = explode ("-", $trans_date_value);
				$date_year[$i] = $date_arr[0];
				$date_month[$i] = $date_arr[1];
				$date_day[$i] = $date_arr[2];
			}else {
				$date_year[$i] = date("Y");
				$date_month[$i] = date("m");
				$date_day[$i] = date("d");
			}
		}

		$view .= "
			<tr bgcolor=".bgcolorg().">
				<td>".mkDateSelecta("date",$i,$date_year[$i],$date_month[$i],$date_day[$i])."</td>
				<td><input type='text' size='5' name='refnum[$i]' value='$refnum[$i]'></td>
				<td valign='center'>";

		core_connect();

		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY $vby ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.</li>";
		}

		$dtaccid[$i] += 0;

		$view .= mkAccSelect ("dtaccid[$i]", $dtaccid[$i]);

		$view .= "
			</td>
			<td valign='center'>";

		$ctaccid[$i] += 0;

		$view .= mkAccSelect ("ctaccid[]", $ctaccid[$i]);

		if(isset($amount[$i])) {
			$a_val = $amount[$i];
		} else {
			$a_val = "";
		}

		if(isset($descript[$i])) {
			$d_val = $descript[$i];
		} else {
			$d_val = "";
		}

		$view .= "
				</td>
				<td><input type='text' size='7' name='amount[]' value='$a_val'></td>
				<td><input type='text' size='20' name='descript[]' value='$d_val'></td>
			</tr>";

	}

	$view .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4' align='right'><b>Total:</b></td>
			<td>".CUR." ".sprint(array_sum ($amount))."</td>
			<td></td>
		</tr>";

	$view .= "
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td><input type='submit' name='another' value='Add Another'></td>
				<td valign='center' colspan='4' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>
		<a name='bottom'>
		$jump_bot
		<table border='0' cellpadding='2' cellspacing='1' width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
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

	if(isset($back)) {
		return number($HTTP_POST_VARS);
	}

	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($amount as $key => $value){
		if($amount[$key] > 0){
			if(isDisabled($ctaccid[$key]))
				return custconfirm($HTTP_POST_VARS);
			if(isDisabled($dtaccid[$key]))
				return custconfirm($HTTP_POST_VARS);

			$v->isOk ($ctaccid[$key], "num", 1, 50, "Invalid Account to be Credited.[$key]");
			$v->isOk ($dtaccid[$key], "num", 1, 50, "Invalid Account to be Debited.[$key]");
			$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
			$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[$key]");
			$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
			$date[$key] = $date_day[$key]."-".$date_month[$key]."-".$date_year[$key];
			if(!checkdate($date_month[$key], $date_day[$key], $date_year[$key])){
				$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
			}

// 			if ($amount[$key] <= 0){
// 				return slctacc($HTTP_POST_VARS,"<li class='err'>Invalid Amount To Process.</li>");
// 			}

			if (strtotime($date[$key]) >= strtotime($blocked_date_from) AND strtotime($date[$key]) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
				return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
			}

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
		return slctacc($HTTP_POST_VARS, $confirm);
	}



	# accnums
	foreach($amount as $key => $value){
		if($amount[$key] > 0){
			# get account to be debited
			$dtaccRs = get("core","*","accounts","accid",$dtaccid[$key]);
			if(pg_numrows($dtaccRs) < 1){
				return "<li> Accounts to be debited does not exist.</li>";
			}
			$dtacc[$key]  = pg_fetch_array($dtaccRs);

			# get account to be credited
			$ctaccRs = get("core","*","accounts","accid",$ctaccid[$key]);
			if(pg_numrows($ctaccRs) < 1){
				return "<li> Accounts to be credited does not exist.</li>";
			}
			$ctacc[$key]  = pg_fetch_array($ctaccRs);
		}
	}

	$confirm = "
		<center>
		<h3>Multiple Journal transactions</h3>
		<h4>Confirm entry</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='vby' value='$vby'>
			<input type='hidden' name='tnum' value='$tnum'>
		<table ".TMPL_tblDflts." width='700'>
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Amount</th>
				<th>Description</th>
			</tr>";

	$trans = "";
	foreach($amount as $key => $value){
		if($amount[$key] > 0){
			$trans .= "
				<tr bgcolor=".bgcolorg().">
					<input type='hidden' name='date_day[]' value='$date_day[$key]'>
					<input type='hidden' name='date_month[]' value='$date_month[$key]'>
					<input type='hidden' name='date_year[]' value='$date_year[$key]'>
					<td><input type='hidden' size='10' name='date[]' value='$date[$key]'>$date[$key]</td>
					<td><input type='hidden' size='10' name='refnum[]' value='$refnum[$key]'>$refnum[$key]</td>
					<td valign='center'><input type='hidden' name='dtaccid[]' value='".$dtacc[$key]['accid']."'>".$dtacc[$key]['accname']."</td>
					<td valign='center'><input type='hidden' name='ctaccid[]' value='".$ctacc[$key]['accid']."'>".$ctacc[$key]['accname']."</td>
					<td><input type='hidden' name='amount[]' value='$amount[$key]'>".CUR." $amount[$key]</td>
					<td><input type='hidden' name='descript[]' value ='$descript[$key]'>$descript[$key]</td>
				</tr>";
		}
	}
	if(strlen($trans) < 5){
		return slctacc ($HTTP_POST_VARS, "<li class='err'>Please enter full transaction details.</li><br>");
	}

	$confirm .= "
			$trans
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right' colspan='4'><input type='submit' value='Write &raquo'></td>
			</tr>
		</form>
		</table>
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
	return $confirm;

}



# Customer Confirm
function custconfirm($HTTP_POST_VARS)
{

    # Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($amount as $key => $value){
		if($amount[$key] > 0){
			$v->isOk ($ctaccid[$key], "num", 1, 50, "Invalid Account to be Credited.[$key]");
			$v->isOk ($dtaccid[$key], "num", 1, 50, "Invalid Account to be Debited.[$key]");
			$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
			$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[$key]");
			$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
			if(!isset($day[$key])) {
				$tds = explode("-",$date[$key]);
				$day[$key]=$tds[0];
				$mon[$key]=$tds[1];
				$year[$key]=$tds[2];
			}
			$date[$key] = $day[$key]."-".$mon[$key]."-".$year[$key];
			if(!checkdate($mon[$key], $day[$key], $year[$key])){
				$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
			}
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



	# Customer drop downs
	db_connect();

	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' ORDER BY cusnum ASC";
	$cusRslt = db_exec($sql) or errDie("Could not retrieve Customers Information from the Database.",SELF);

	if(pg_numrows($cusRslt) < 1){
		return "<li class='err'> There are no Customers in Cubit.</li>";
	}
	$ccusts = "<select name='ccusnum[]'>";
	while($cus = pg_fetch_array($cusRslt)){
		$ccusts .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
	}
	$ccusts .= "</select>";

	$sql = "SELECT * FROM customers WHERE div = '".USER_DIV."' ORDER BY cusnum ASC";
	$cusRslt = db_exec($sql) or errDie("Could not retrieve Customers Information from the Database.",SELF);

	if(pg_numrows($cusRslt) < 1){
		return "<li class='err'> There are no Customers in Cubit.</li>";
	}
	$dcusts = "<select name='dcusnum[]'>";
	while($cus = pg_fetch_array($cusRslt)){
		$dcusts .= "<option value='$cus[cusnum]'>$cus[cusname] $cus[surname]</option>";
	}
	$dcusts .= "</select>";

	# Supplier drop downs
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' ORDER BY supno ASC";
	$supRslt = db_exec($sql) or errDie("Could not retrieve Suppliers Information from the Database.",SELF);

	if(pg_numrows($supRslt) < 1){
		return "<li class='err'> There are no Suppliers in Cubit.</li>";
	}
	$dsups = "<select name='dsupid[]'>";
	while($dsup = pg_fetch_array($supRslt)){
		$dsups .= "<option value='$dsup[supid]'>$dsup[supname]</option>";
	}
	$dsups .= "</select>";
	#--
	$sql = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' ORDER BY supno ASC";
	$supRslt = db_exec($sql) or errDie("Could not retrieve Suppliers Information from the Database.",SELF);

	if(pg_numrows($supRslt) < 1){
		return "<li class='err'> There are no Suppliers in Cubit.</li>";
	}
	$csups = "<select name='csupid[]'>";
	while($csup = pg_fetch_array($supRslt)){
		$csups .= "<option value='$csup[supid]'>$csup[supname]</option>";
	}
	$csups .= "</select>";

	# Stock drop downs
	$sql = "SELECT * FROM stock WHERE div = '".USER_DIV."' ORDER BY stkcod ASC";
	$stkRslt = db_exec($sql) or errDie("Could not retrieve Stock Information from the Database.",SELF);

	if(pg_numrows($stkRslt) < 1){
		return "<li class='err'> There are no Stock Items in Cubit.</li>";
	}
	$dstks = "<select name='dstkids[]'>";
	while($dstk = pg_fetch_array($stkRslt)){
		$dstks .= "<option value='$dstk[stkid]'>($dstk[stkcod]) $dstk[stkdes]</option>";
	}
	$dstks .= "</select>";
	#--
	$sql = "SELECT * FROM stock WHERE div = '".USER_DIV."' ORDER BY stkcod ASC";
	$stkRslt = db_exec($sql) or errDie("Could not retrieve Stock Information from the Database.",SELF);

	if(pg_numrows($stkRslt) < 1){
		return "<li class='err'> There are no Stock Items in Cubit.</li>";
	}
	$cstks = "<select name='cstkids[]'>";
	while($cstk = pg_fetch_array($stkRslt)){
		$cstks .= "<option value='$cstk[stkid]'>($cstk[stkcod]) $cstk[stkdes]</option>";
	}
	$cstks .= "</select>";


	# accnums
	foreach($amount as $key => $value){
		if($amount[$key] > 0){
			# get account to be debited
			$dtaccRs = get("core","*","accounts","accid",$dtaccid[$key]);
			if(pg_numrows($dtaccRs) < 1){
				return "<li> Accounts to be debited does not exist.</li>";
			}
			$dtacc[$key]  = pg_fetch_array($dtaccRs);

			# get account to be credited
			$ctaccRs = get("core","*","accounts","accid",$ctaccid[$key]);
			if(pg_numrows($ctaccRs) < 1){
				return "<li> Accounts to be credited does not exist.</li>";
			}
			$ctacc[$key]  = pg_fetch_array($ctaccRs);
		}
	}

	$confirm = "
		<center>
		<h3>Multiple Journal transactions</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='cconfirm'>
			<input type='hidden' name='vby' value='$vby'>
			<input type='hidden' name='tnum' value='$tnum'>
		<table ".TMPL_tblDflts." width='700'>
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Amount</th>
				<th>Description</th>
			</tr>";

	$trans = "";
	foreach($amount as $key => $value){
			if($amount[$key] > 0){
				if(isDebtors($dtaccid[$key])){
					$dt = "
						<td valign='center'>
							<input type='hidden' name='dtaccid[]' value='".$dtacc[$key]['accid']."'>
							<input type='hidden' name='dsupid[]' value='0'>
							<input type='hidden' name='dstkids[]' value='0'>$dcusts
						</td>";
				}elseif(isCreditors($dtaccid[$key])){
					$dt = "
						<td valign='center'>
							<input type='hidden' name='dtaccid[]' value='".$dtacc[$key]['accid']."'>
							<input type='hidden' name='dcusnum[]' value='0'>
							<input type='hidden' name='dstkids[]' value='0'>$dsups
						</td>";
				}elseif(isStock($dtaccid[$key])){
					$dt = "
						<td valign='center'>
							<input type='hidden' name='dtaccid[]' value='".$dtacc[$key]['accid']."'>
							<input type='hidden' name='dcusnum[]' value='0'>
							<input type='hidden' name='dsupid[]' value='0'>$dstks
						</td>";
				}else{
					$dt = "
						<td valign='center'>
							<input type='hidden' name='dtaccid[]' value='".$dtacc[$key]['accid']."'>
							<input type='hidden' name='dcusnum[]' value='0'>
							<input type='hidden' name='dsupid[]' value='0'>
							<input type='hidden' name='dstkids[]' value='0'>".$dtacc[$key]['accname']."
						</td>";
				}
				if(isDebtors($ctaccid[$key])){
					$ct = "
						<td valign='center'>
							<input type='hidden' name='ctaccid[]' value='".$ctacc[$key]['accid']."'>
							<input type='hidden' name='csupid[]' value='0'>
							<input type='hidden' name='cstkids[]' value='0'>$ccusts
						</td>";
				}elseif(isCreditors($ctaccid[$key])){
					$ct = "
						<td valign='center'>
							<input type='hidden' name='ctaccid[]' value='".$ctacc[$key]['accid']."'>
							<input type='hidden' name='ccusnum[]' value='0'>
							<input type='hidden' name='cstkids[]' value='0'>$csups
						</td>";
				}elseif(isStock($ctaccid[$key])){
					$ct = "
						<td valign='center'>
							<input type='hidden' name='ctaccid[]' value='".$ctacc[$key]['accid']."'>
							<input type='hidden' name='ccusnum[]' value='0'>
							<input type='hidden' name='csupid[]' value='0'>$cstks
						</td>";
				}else{
					$ct = "
						<td valign='center'>
							<input type='hidden' name='ctaccid[]' value='".$ctacc[$key]['accid']."'>
							<input type='hidden' name='ccusnum[]' value='0'>
							<input type='hidden' name='csupid[]' value='0'>
							<input type='hidden' name='cstkids[]' value='0'>".$ctacc[$key]['accname']."
						</td>";
				}

				$trans .= "
					<tr bgcolor=".bgcolorg().">
						<td><input type='hidden' size='10' name='date[]' value='$date[$key]'>$date[$key]</td>
						<td><input type='hidden' size='10' name='refnum[]' value='$refnum[$key]'>$refnum[$key]</td>
						$dt
						$ct
						<td><input type='hidden' name='amount[]' value='$amount[$key]'>".CUR." $amount[$key]</td>
						<td><input type='hidden' name='descript[]' value ='$descript[$key]'>$descript[$key]</td>
					</tr>";
			}
	}

	if(strlen($trans) < 5){
		return "
			<li> - Please enter full transaction details</li><p>
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
	}

	$confirm .= "
			$trans
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right' colspan='4'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</form>
		</table>
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
	return $confirm;

}



# Customer Confirm
function cconfirm($HTTP_POST_VARS)
{

    # Get vars
	extract ($HTTP_POST_VARS);

	if(isset($back)) {
		unset($HTTP_POST_VARS["back"]);
		return slctacc($HTTP_POST_VARS);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($amount as $key => $value){
		if($amount[$key] > 0){
			$v->isOk ($ctaccid[$key], "num", 1, 50, "Invalid Account to be Credited.[$key]");
			$v->isOk ($dtaccid[$key], "num", 1, 50, "Invalid Account to be Debited.[$key]");
			$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
			$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[$key]");
			$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
			$v->isOk ($date[$key], "date", 1, 14, "Invalid date.");
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



	# Accnums
	foreach($amount as $key => $value){
		if($amount[$key] > 0){
			# get account to be debited
			$dtaccRs = get("core","*","accounts","accid",$dtaccid[$key]);
			if(pg_numrows($dtaccRs) < 1){
					return "<li> Accounts to be debited does not exist.</li>";
			}
			$dtacc[$key]  = pg_fetch_array($dtaccRs);

			# get account to be credited
			$ctaccRs = get("core","*","accounts","accid",$ctaccid[$key]);
			if(pg_numrows($ctaccRs) < 1){
					return "<li> Accounts to be credited does not exist.</li>";
			}
			$ctacc[$key]  = pg_fetch_array($ctaccRs);
		}
	}

	$confirm = "
		<center>
		<h3>Multiple Journal transactions</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='vby' value='$vby'>
			<input type='hidden' name='tnum' value='$tnum'>
		<table ".TMPL_tblDflts." width='700'>
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Amount</th>
				<th>Description</th>
			</tr>";

	$trans = "";
	foreach($amount as $key => $value){
		if($amount[$key] > 0){
			if(isDebtors($dtaccid[$key])){
				$dcusRs = get("cubit", "*", "customers","cusnum",$dcusnum[$key]);
				$dcus = pg_fetch_array($dcusRs);
				$dt = "
					<td valign='center'>
						<input type='hidden' name='dtaccid[]' value='".$dtacc[$key]['accid']."'>
						<input type='hidden' name='dcusnum[]' value='$dcusnum[$key]'>
						<input type='hidden' name='dsupid[]' value='0'>
						<input type='hidden' name='dstkids[]' value='0'>$dcus[accno] - $dcus[cusname] $dcus[surname]
					</td>";
			}elseif(isCreditors($dtaccid[$key])){
				$dsupRs = get("cubit", "*", "suppliers","supid",$dsupid[$key]);
				$dsup = pg_fetch_array($dsupRs);
				$dt = "
					<td valign='center'>
						<input type='hidden' name='dtaccid[]' value='".$dtacc[$key]['accid']."'>
						<input type='hidden' name='dcusnum[]' value='0'>
						<input type='hidden' name='dsupid[]' value='$dsupid[$key]'>
						<input type='hidden' name='dstkids[]' value='0'>$dsup[supno] - $dsup[supname]
					</td>";
			}elseif(isStock($dtaccid[$key])){
				$dstkRs = get("cubit", "*", "stock", "stkid", $dstkids[$key]);
				$dstk = pg_fetch_array($dstkRs);
				$dt = "
					<td valign='center'>
						<input type='hidden' name='dtaccid[]' value='".$dtacc[$key]['accid']."'>
						<input type='hidden' name='dcusnum[]' value='0'>
						<input type='hidden' name='dsupid[]' value='0'>
						<input type='hidden' name='dstkids[]' value='$dstkids[$key]'>$dstk[stkcod] - $dstk[stkdes]
					</td>";
			}else{
				$dt = "
					<td valign='center'>
						<input type='hidden' name='dtaccid[]' value='".$dtacc[$key]['accid']."'>
						<input type='hidden' name='dcusnum[]' value='0'>
						<input type='hidden' name='dsupid[]' value='0'>
						<input type='hidden' name='dstkids[]' value='0'>".$dtacc[$key]['accname']."
					</td>";
			}

			if(isDebtors($ctaccid[$key])){
				$ccusRs = get("cubit", "*", "customers","cusnum",$ccusnum[$key]);
				$ccus = pg_fetch_array($ccusRs);
				$ct = "
					<td valign='center'>
						<input type='hidden' name='ctaccid[]' value='".$ctacc[$key]['accid']."'>
						<input type='hidden' name='ccusnum[]' value='$ccusnum[$key]'>
						<input type='hidden' name='csupid[]' value='0'>
						<input type='hidden' name='cstkids[]' value='0'>$ccus[accno] - $ccus[cusname] $ccus[surname]
					</td>";
			}elseif(isCreditors($ctaccid[$key])){
				$csupRs = get("cubit", "*", "suppliers","supid",$csupid[$key]);
				$csup = pg_fetch_array($csupRs);
				$ct = "
					<td valign='center'>
						<input type='hidden' name='ctaccid[]' value='".$ctacc[$key]['accid']."'>
						<input type='hidden' name='ccusnum[]' value='0'>
						<input type='hidden' name='csupid[]' value='$csupid[$key]'>
						<input type='hidden' name='cstkids[]' value='0'>$csup[supno] - $csup[supname]
					</td>";
			}elseif(isStock($ctaccid[$key])){
				$cstkRs = get("cubit", "*", "stock", "stkid", $cstkids[$key]);
				$cstk = pg_fetch_array($cstkRs);
				$ct = "
					<td valign='center'>
						<input type='hidden' name='ctaccid[]' value='".$ctacc[$key]['accid']."'>
						<input type='hidden' name='ccusnum[]' value='0'>
						<input type='hidden' name='csupid[]' value='0'>
						<input type='hidden' name='cstkids[]' value='$cstkids[$key]'>$cstk[stkcod] - $cstk[stkdes]
					</td>";
			}else{
				$ct = "
					<td valign='center'>
						<input type='hidden' name='ctaccid[]' value='".$ctacc[$key]['accid']."'>
						<input type='hidden' name='ccusnum[]' value='0'>
						<input type='hidden' name='csupid[]' value='0'>
						<input type='hidden' name='cstkids[]' value='0'>".$ctacc[$key]['accname']."
					</td>";
			}

			$trans .= "
				<tr bgcolor=".bgcolorg().">
					<td><input type='hidden' size='10' name='date[]' value='$date[$key]'>$date[$key]</td>
					<td><input type='hidden' size='10' name='refnum[]' value='$refnum[$key]'>$refnum[$key]</td>
					$dt
					$ct
					<td><input type='hidden' name='amount[]' value='$amount[$key]'>".CUR." $amount[$key]</td>
					<td><input type='hidden' name='descript[]' value ='$descript[$key]'>$descript[$key]</td>
				</tr>";
		}
	}
	if(strlen($trans) < 5){
		return "
			<li> - Please enter full transaction details</li><p>
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
	}

	$confirm .= "
			$trans
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='cback' value='&laquo; Correction'></td>
			<td align=right colspan=4><input type=submit value='Write &raquo'></td></tr>
		</form></table>
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
	return $confirm;

}



# Write
function write($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	if(isset($back)) {
		unset($HTTP_POST_VARS["back"]);
		return slctacc($HTTP_POST_VARS);
	}

	if(isset($cback)) {
		return custconfirm($HTTP_POST_VARS);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($amount as $key => $value){
		if ($value > 0) 
			continue;

		$v->isOk ($ctaccid[$key], "num", 1, 50, "Invalid Account to be Credited.[$key]");
		$v->isOk ($dtaccid[$key], "num", 1, 50, "Invalid Account to be Debited.[$key]");
		$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
		$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[$key]");
		$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
		$datea = explode("-", $date[$key]);
		if(count($datea) == 3){
			if(!checkdate($datea[1], $datea[0], $datea[2])){
				$v->isOk ("dadasdas", "num", 1, 1, "Invalid date.");
			}
		}else{
			$v->isOk ("asdasd", "num", 1, 1, "Invalid date.");
		}
		$date[$key] = $datea[2]."-".$datea[1]."-".$datea[0];
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



	foreach($amount as $key => $value){

		if ($value <= 0) 
			continue;

		// Accounts details
		$dtaccRs = get("core","accname, topacc, accnum","accounts","accid",$dtaccid[$key]);
		$dtacc[$key]  = pg_fetch_array($dtaccRs);
		$ctaccRs = get("core","accname, topacc, accnum","accounts","accid",$ctaccid[$key]);
		$ctacc[$key]  = pg_fetch_array($ctaccRs);

		$td = $date[$key];

		if(isDebtors($dtaccid[$key])){

			# Select customer
			db_connect();
			$sql = "SELECT * FROM customers WHERE cusnum = '$dcusnum[$key]' AND div = '".USER_DIV."'";
			$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
			if(pg_numrows($custRslt) < 1){
				return "<li> Invalid Customer ID.</li>";
			}else{
				$cust = pg_fetch_array($custRslt);
			}

			# Get department
			db_conn("exten");
			$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]' AND div = '".USER_DIV."'";
			$deptRslt = db_exec($sql);
			if(pg_numrows($deptRslt) < 1){
				return "<i class='err'>Department Not Found</i>";
			}else{
				$dept = pg_fetch_array($deptRslt);
			}

			db_connect();
			# Begin updates
			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

				$sdate = date("Y-m-d");
				# record the payment on the statement
				$sql = "
					INSERT INTO stmnt (
						cusnum, invid, amount, date, type, st, div, allocation_date
					) VALUES (
						'$cust[cusnum]', '0', '$amount[$key]', '$td', '$descript[$key]', 'n', '".USER_DIV."', '$td'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

				$sql = "
					INSERT INTO open_stmnt (
						cusnum, invid, amount, balance, date, 
						type, st, div
					) VALUES (
						'$cust[cusnum]', '0', '$amount[$key]', '$amount[$key]', '$td', 
						'$descript[$key]', 'n', '".USER_DIV."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

				# update the customer (make balance more)
				$sql = "UPDATE customers SET balance = (balance + '$amount[$key]') WHERE cusnum = '$cust[cusnum]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update customer in Cubit.",SELF);

			# Commit updates
			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

			# Make ledge record
			custledger($cust['cusnum'], $ctaccid[$key], $td, $refnum[$key], $descript[$key], $amount[$key], "d");
			custDT($amount[$key], $cust['cusnum'],$td);
			$dtaccid[$key] = $dept['debtacc'];
			$descript[$key] = $descript[$key]." - Customer $cust[surname]";
		}elseif(isCreditors($dtaccid[$key])){
			# Select supplier
			db_connect();
			$sql = "SELECT * FROM suppliers WHERE supid = '$dsupid[$key]' AND div = '".USER_DIV."'";
			$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
			if(pg_numrows($suppRslt) < 1){
				return "<li> Invalid Supplier ID.</li>";
			}else{
				$supp = pg_fetch_array($suppRslt);
			}

			# Get department
			db_conn("exten");
			$sql = "SELECT * FROM departments WHERE deptid = '$supp[deptid]' AND div = '".USER_DIV."'";
			$deptRslt = db_exec($sql);
			if(pg_numrows($deptRslt) < 1){
				return "<i class='err'>Department Not Found</i>";
			}else{
				$dept = pg_fetch_array($deptRslt);
			}

			db_connect();
			# Begin updates
			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

				$edate = date("Y-m-d");
				# record the payment on the statement
				$sql = "
					INSERT INTO sup_stmnt (
						supid, edate, ref, cacc, descript, amount, div
					) VALUES (
						'$supp[supid]', '$td', '0', '$ctaccid[$key]', '$descript[$key]', '-$amount[$key]', '".USER_DIV."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

				# update the supplier (make balance more)
				$sql = "UPDATE suppliers SET balance = (balance - '$amount[$key]') WHERE supid = '$supp[supid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update supplier in Cubit.",SELF);

			# Commit updates
			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

			# Ledger Records
			suppledger($supp['supid'], $ctaccid[$key], $td, $refnum[$key], $descript[$key], $amount[$key], 'd');
			suppDT($amount[$key], $supp['supid'],$td);
			$dtaccid[$key] = $dept['credacc'];
			$descript[$key] = $descript[$key]." - Supplier $supp[supname]";
		}elseif(isStock($dtaccid[$key])){
			# Select Stock
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$dstkids[$key]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
			if(pg_numrows($stkRslt) < 1){
				return "<li> Invalid Stock ID.</li>";
			}else{
				$stk = pg_fetch_array($stkRslt);
			}

			# Get warehouse name
			db_conn("exten");
			$sql = "SELECT * FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# Update Stock
			db_connect();
			$sql = "UPDATE stock SET csamt = (csamt + '$amount[$key]') WHERE stkid = '$stk[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

			$sdate = date("Y-m-d");
			# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
			stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'dt', $td, 0, $amount[$key], "Stock Debit Transaction");
			db_connect();
			$dtaccid[$key] = $wh['stkacc'];
		}

		if(isDebtors($ctaccid[$key])){

			# Select customer
			db_connect();
			$sql = "SELECT * FROM customers WHERE cusnum = '$ccusnum[$key]' AND div = '".USER_DIV."'";
			$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
			if(pg_numrows($custRslt) < 1){
				return "<li> Invalid Customer ID.</li>";
			}else{
				$cust = pg_fetch_array($custRslt);
			}

			# Get department
			db_conn("exten");
			$sql = "SELECT * FROM departments WHERE deptid = '$cust[deptid]' AND div = '".USER_DIV."'";
			$deptRslt = db_exec($sql);
			if(pg_numrows($deptRslt) < 1){
				return "<i class='err'>Department Not Found</i>";
			}else{
				$dept = pg_fetch_array($deptRslt);
			}

			db_connect();
			# Begin updates
			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

				$sdate = date("Y-m-d");
				# record the payment on the statement
				$sql = "
					INSERT INTO stmnt (
						cusnum, invid, amount, date, type, st, div, allocation_date
					) VALUES (
						'$cust[cusnum]', '0', '-$amount[$key]', '$td', '$descript[$key]', 'n', '".USER_DIV."', '$td'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

				$sql = "
					INSERT INTO open_stmnt (
						cusnum, invid, amount, balance, date, 
						type, st, div
					) VALUES (
						'$cust[cusnum]', '0', '-$amount[$key]', '-$amount[$key]', '$td', 
						'$descript[$key]', 'n', '".USER_DIV."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

				# update the customer (make balance more)
				$sql = "UPDATE customers SET balance = (balance - '$amount[$key]') WHERE cusnum = '$cust[cusnum]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update customer in Cubit.",SELF);

			# Commit updates
			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

			# Make ledge record
			custledger($cust['cusnum'], $dtaccid[$key], $sdate, $refnum[$key], $descript[$key], $amount[$key], "c");
			custCT($amount[$key], $cust['cusnum'],$td);
			$ctaccid[$key] = $dept['debtacc'];
			$descript[$key] = $descript[$key]." - Customer $cust[surname]";

		}elseif(isCreditors($ctaccid[$key])){
			# Select supplier
			db_connect();
			$sql = "SELECT * FROM suppliers WHERE supid = '$csupid[$key]' AND div = '".USER_DIV."'";
			$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
			if(pg_numrows($suppRslt) < 1){
				return "<li> Invalid Supplier ID.</li>";
			}else{
				$supp = pg_fetch_array($suppRslt);
			}

			# Get department
			db_conn("exten");
			$sql = "SELECT * FROM departments WHERE deptid = '$supp[deptid]' AND div = '".USER_DIV."'";
			$deptRslt = db_exec($sql);
			if(pg_numrows($deptRslt) < 1){
				return "<i class='err'>Department Not Found</i>";
			}else{
				$dept = pg_fetch_array($deptRslt);
			}

			db_connect();
			# Begin updates
			pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

				$edate = date("Y-m-d");
				# record the payment on the statement
				$sql = "
					INSERT INTO sup_stmnt (
						supid, edate, ref, cacc, descript, amount, div
					) VALUES (
						'$supp[supid]', '$td', '0', '$dtaccid[$key]', '$descript[$key]', '$amount[$key]', '".USER_DIV."'
					)";
				$stmntRslt = db_exec($sql) or errDie("Unable to Insert statement record in Cubit.",SELF);

				# update the supplier (make balance more)
				$sql = "UPDATE suppliers SET balance = (balance + '$amount[$key]') WHERE supid = '$supp[supid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update supplier in Cubit.",SELF);

			# Commit updates
			pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

			# Ledger Records
			suppledger($supp['supid'], $dtaccid[$key], $edate, $refnum[$key], $descript[$key], $amount[$key], 'c');
			suppCT($amount[$key], $supp['supid'],$td);
			$ctaccid[$key] = $dept['credacc'];
			$descript[$key] = $descript[$key]." - Supplier $supp[supname]";
		}elseif(isStock($ctaccid[$key])){
			# Select Stock
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$cstkids[$key]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
			if(pg_numrows($stkRslt) < 1){
				return "<li> Invalid Stock ID.</li>";
			}else{
				$stk = pg_fetch_array($stkRslt);
			}

			# Get warehouse name
			db_conn("exten");
			$sql = "SELECT * FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# Update Stock
			db_connect();
			$sql = "UPDATE stock SET csamt = (csamt + '$amount[$key]') WHERE stkid = '$stk[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

			$sdate = date("Y-m-d");
			# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
			stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $td, 0, $amount[$key], "Stock Credit Transaction");
			db_connect();
			$ctaccid[$key] = $wh['stkacc'];
		}

		# write transaction
		writetrans($dtaccid[$key],$ctaccid[$key], $date[$key], $refnum[$key], $amount[$key], $descript[$key]);

	}

	// Layout
	$write = "
		<center>
		<h3>Journal transactions have been recorded</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Amount</th>
				<th>Description</th>
			</tr>";

		foreach($amount as $key => $value){

			if ($value <= 0) 
				continue;

			if(isDebtors($dtaccid[$key])){
				$dcusRs = get("cubit", "*", "customers","cusnum",$dcusnum[$key]);
				$dcus = pg_fetch_array($dcusRs);
				$dt = "<td valign='center'>$dcus[accno] - $dcus[cusname] $dcus[surname]</td>";
			}elseif(isCreditors($dtaccid[$key])){
				$dsupRs = get("cubit", "*", "suppliers","supid",$dsupid[$key]);
				$dsup = pg_fetch_array($dsupRs);
				$dt = "<td valign='center'>$dsup[supno] - $dsup[supname]</td>";
			}elseif(isStock($dtaccid[$key])){
				$dstkRs = get("cubit", "*", "stock", "stkid", $dstkids[$key]);
				$dstk = pg_fetch_array($dstkRs);
				$dt = "<td valign='center'>$dstk[stkcod] - $dstk[stkdes]</td>";
			}else{
				$dt = "<td valign='center'>".$dtacc[$key]['accname']."</td>";
			}
			if(isDebtors($ctaccid[$key])){
				$ccusRs = get("cubit", "*", "customers","cusnum",$ccusnum[$key]);
				$ccus = pg_fetch_array($ccusRs);
				$ct = "<td valign='center'>$ccus[accno] - $ccus[cusname] $ccus[surname]</td>";
			}elseif(isCreditors($ctaccid[$key])){
				$csupRs = get("cubit", "*", "suppliers","supid",$csupid[$key]);
				$csup = pg_fetch_array($csupRs);
				$ct = "<td valign='center'>$csup[supno] - $csup[supname]</td>";
			}elseif(isStock($ctaccid[$key])){
				$cstkRs = get("cubit", "*", "stock", "stkid", $cstkids[$key]);
				$cstk = pg_fetch_array($cstkRs);
				$ct = "<td valign='center'>$cstk[stkcod] - $cstk[stkdes]</td>";
			}else{
				$ct = "<td valign='center'>".$ctacc[$key]['accname']."</td>";
			}

			$write .= "
				<tr bgcolor=".bgcolorg().">
					<td>$date[$key]</td>
					<td>$refnum[$key]</td>
					$dt
					$ct
					<td>".CUR." $amount[$key]</td>
					<td>$descript[$key]</td>
				</tr>";
		}

	$write .= "
		</table>
		<p>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='multi-trans.php'>Journal Transactions</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}


?>
