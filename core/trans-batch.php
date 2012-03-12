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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		case "details":
			$OUTPUT = details($_POST);
			break;
		case "details2":
			$OUTPUT = details2($_POST);
			break;
		default:
			$OUTPUT = slctacc($_POST);
	}
} else {
    # Display default output
    $OUTPUT = slctacc($_POST);
}

# get templete
require("template.php");




# Select Accounts
function slctacc($_POST, $err="")
{

	extract($_POST);

	if (!isset($refnum))
		$refnum = getrefnum();
		/*refnum*/

	// Accounts (debit)
	$view = "
		<center>
		<h3>Add Journal transactions to batch </h3>
		$err
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
		<table ".TMPL_tblDflts." align='center'>
			<tr>
				<th>Date</th>
				<th>Ref num</th>
				<th>Debit <input align='right' type='button' onClick=\"window.open('acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
				<th>Credit <input align='right' type='button' onClick=\"window.open('acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
				<th>Amount</th>
				<th>Description</th>
			</tr>";

	for($i=0; $i != 20; $i++){

// 		if (!isset($date_year[$i])){
// 			$date_year[$i] = date("Y");
// 			$date_month[$i] = date("m");
// 			$date_day[$i] = date ("d");
// 		}

		if (!isset($date_year[$i])){
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
				<td>".mkDateSelecta("date", $i, $date_year[$i], $date_month[$i], $date_day[$i])."</td>
				<td><input type='text' size='5' name='refnum[]' value='$refnum[$i]'></td>
				<td valign='center'>";

		core_connect();

		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
		$accRslt = db_exec($sql);
		if(pg_numrows($accRslt) < 1){
			return "<li>There are No accounts in Cubit.</li>";
		}

		if(isset($dtaccid[$i])) {
			$aval = $dtaccid[$i];
		} else {
			$aval = 0;
		}

		$view .= mkAccSelect ("dtaccid[]",$aval);

// 		$view .= "<select name='dtaccid[]' style='width: 230'>";
// 		while($acc = pg_fetch_array($accRslt)){
// 			# Check Disable
// 			if(isDisabled($acc['accid']))
// 				continue;
// 			if($acc['accid'] == $aval) {
// 				$sel = "selected";
// 			} else {
// 				$sel = "";
// 			}
// 			$view .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
// 		}
// 		$view .= "</select>";

		$view .= "
			</td>
			<td valign='center'>";

// 		$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
// 		$accRslt = db_exec($sql);
// 		if(pg_numrows($accRslt) < 1){
// 			return "<li>There are No accounts in Cubit.</li>";
// 		}

		if(isset($ctaccid[$i])) {
			$aval = $ctaccid[$i];
		} else {
			$aval = 0;
		}

		$view .= mkAccSelect ("ctaccid[]", $aval);

// 		$view .= "<select name='ctaccid[]' style='width: 230'>";
// 		while($acc = pg_fetch_array($accRslt)){
// 			# Check Disable
// 			if(isDisabled($acc['accid']))
// 				continue;
// 			if($aval == $acc['accid']) {
// 				$sel = "selected";
// 			} else {
// 				$sel = "";
// 			}
// 			$view .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
// 		}
// 		$view .= "</select>";

		if(isset($amount[$i])) {
			$aval = $amount[$i];
		} else {
			$aval = "";
		}

		if(isset($descript[$i])) {
			$dval = $descript[$i];
		} else {
			$dval = "";
		}

		$view .= "
				</td>
				<td><input type='text' size='7' name='amount[]' value='$aval'></td>
				<td><input type='text' size='20' name='descript[]' value='$dval'></td>
			</tr>";
	}

	$view .= "
			<tr>
				<td></td>
				<td valign='center' colspan='4' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>
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
function confirm($_POST)
{

    # Get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	foreach($amount as $key => $value){
		if($amount[$key] > 0){
			$v->isOk ($ctaccid[$key], "num", 1, 50, "Invalid Account to be Credited.[$key]");
			$v->isOk ($dtaccid[$key], "num", 1, 50, "Invalid Account to be Debited.[$key]");
			$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
			$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[$key]");
			$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
			$date[$key] = $date_day[$key]."-".$date_month[$key]."-".$date_year[$key];
			if(!checkdate($date_month[$key], $date_day[$key], $date_year[$key])){
				$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
			}

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
		return slctacc($_POST, $confirm);
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
		<h3>Add Multiple Journal transactions to batch</h3>
		<h4>Confirm entry</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
		<table ".TMPL_tblDflts." width='590'>
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
					<td><input type='hidden' size='10' name='date[]' value='$date[$key]'>$date[$key]</td>
					<td><input type='hidden' size='10' name='refnum[]' value='$refnum[$key]'>$refnum[$key]</td>
					<td valign='center'><input type='hidden' name='dtaccid[]' value='".$dtacc[$key]['accid']."'>".$dtacc[$key]['accname']."</td>
					<td valign='center'><input type='hidden' name='ctaccid[]' value='".$ctacc[$key]['accid']."'>".$ctacc[$key]['accname']."</td>
					<td nowrap><input type='hidden' name='amount[]' value='$amount[$key]'>".CUR." $amount[$key]</td>
					<td><input type='hidden' name='descript[]' value ='$descript[$key]'>$descript[$key]</td>
				</tr>";
		}
	}

	if(strlen($trans) < 5){
		return slctacc($_POST,"<li class='err'>Please enter full transaction details</li><br>");
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
function write($_POST)
{

	# Get vars
	extract ($_POST);

	if(isset($back)) {
		return slctacc($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($amount as $key => $value){
		$v->isOk ($ctaccid[$key], "num", 1, 50, "Invalid Account to be Credited.[$key]");
		$v->isOk ($dtaccid[$key], "num", 1, 50, "Invalid Account to be Debited.[$key]");
		$v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
		$v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[$key]");
		$v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
		$datea = explode("-", $date[$key]);
		if(count($datea) == 3){
			if(!checkdate($datea[1], $datea[0], $datea[2])){
				$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
			}
		}else{
		    $v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
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


	
	foreach($amount as $key => $value){
		// Accounts details
		$dtaccRs = get("core","accname, topacc, accnum","accounts","accid",$dtaccid[$key]);
		$dtacc[$key]  = pg_fetch_array($dtaccRs);
		$ctaccRs = get("core","accname, topacc, accnum","accounts","accid",$ctaccid[$key]);
		$ctacc[$key]  = pg_fetch_array($ctaccRs);

		# begin sql transaction
		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		// Insert the records into the transaction table
		core_connect();

		# format date of loss
		$date[$key] = explode("-", $date[$key]);
		$date[$key] = $date[$key][2]."-".$date[$key][1]."-".$date[$key][0];

		# Insert into batch
		$sql = "
			INSERT INTO batch (
				date, debit, credit, refnum, amount, 
				author, details, proc, div
			) VALUES (
				'$date[$key]', '$dtaccid[$key]', '$ctaccid[$key]', '$refnum[$key]', '$amount[$key]', 
				'".USER_NAME."', '$descript[$key]', 'no', '".USER_DIV."'
			)";
		$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

		# commit sql transaction
		pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);
	}

	// Start layout
	$write = "
		<center>
		<h3>Journal transactions have been recorded to a batch file</h3>
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
		$write .= "
			<tr bgcolor=".bgcolorg().">
				<td>$date[$key]</td>
				<td>$refnum[$key]</td>
				<td valign='center'>".$dtacc[$key]['topacc']."/".$dtacc[$key]['accnum']." ".$dtacc[$key]['accname']."</td>
				<td valign='center'>".$ctacc[$key]['topacc']."/".$ctacc[$key]['accnum']." ".$ctacc[$key]['accname']."</td>
				<td nowrap>".CUR." $amount[$key]</td>
				<td>$descript[$key]</td>
			</tr>";
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
