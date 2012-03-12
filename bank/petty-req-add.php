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

# Get templete
require("../template.php");




# Insert details
function add($errors="")
{

	global $_POST;
	extract ($_POST);

	$fields["name"] = "";
	$fields["det"] = "";
	$fields["amount"] = "";
	$fields["chrgvat"] = "inc";
	$fields["vatcode"] = "";
	$fields["accid"] = "";

	foreach ($fields as $var_name=>$value) {
		if (!isset($$var_name)) {
			$$var_name = $value;
		}
	}

	core_connect();

	$accnts = mkAccSelect ("accid",$accid);

// 	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
// 	$accRslt = db_exec($sql);
// 
// 	if(pg_numrows($accRslt) < 1){
// 		return "<li class='err'> ERROR : There are no accounts in the category selected.</li>";
// 	}
// 
// 	$accnts = "<select name='accid'>";
// 	while($acc = pg_fetch_array($accRslt)){
// 		# Check Disable
// 		if(isDisabled($acc['accid']))
// 			continue;
// 
// 		if ($accid == $acc["accid"]) {
// 			$selected = "selected";
// 		} else {
// 			$selected = "";
// 		}
// 
// 		$accnts .= "<option value='$acc[accid]' $selected>$acc[accname]</option>";
// 	}
// 	$accnts .= "</select>";

	# check available funds
	$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");

	core_connect();

	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE accid = '$cashacc' AND month='".(int)date("m")."' AND div = '".USER_DIV."'";
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

	// Vat Inclusive ?
	if ($chrgvat == "exc") {
		$vat_exc = "checked";
		$vat_inc = "";
	} else {
		$vat_exc = "";
		$vat_inc = "checked";
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes ORDER BY code";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes = "
		<select name='vatcode'>
			<option value='0'>Select</option>";
	while($vd = pg_fetch_array($Ri)) {
		if($vd['del'] == "Yes" || $vatcode == $vd["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$Vatcodes .= "<option value='$vd[id]' $sel>$vd[code]</option>";
	}
	$Vatcodes .= "</select>";

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

	// Layout
	$add = "
		<h3>Add Petty Cash Requisistion</h3>
		<table ".TMPL_tblDflts." width='600'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<td colspan='2'>$errors</td>
			</tr>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Paid to</td>
				<td valign='center'><input size='20' name='name' value='$name'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Details</td>
				<td valign='center'><textarea cols='18' rows='2' name='det'>$det</textarea></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Available Funds</td>
				<td><input type='hidden' name='bal' value='$accb[bal]'>".CUR." $accb[bal]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." <input type='text' size='10' name='amount' value='$amount'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Inclusive</td>
				<td valign='center'>
					Yes <input type='radio' size='7' name='chrgvat' value='inc' $vat_inc>
					No <input type='radio' size='7' name='chrgvat' value='exc' $vat_exc>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Code</td>
				<td>$Vatcodes</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Paid to <input align='right' type='button' onClick=\"window.open('../core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></td>
				<td>$accnts</td>
			</tr>
			<tr>
				<td><br></td>
			</tr>
			<tr>
				<td valign='center' align='right' colspan='2'><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pettycashbook-view.php'>View Petty Cash Requisitions</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $add;

}



# confirm
function confirm($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$vatcode += 0;
	$v->isOk ($date_day, "num", 1, 2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1, 2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1, 4, "Invalid Date Year.");
	if(strlen($date_year) <> 4){
		$v->isOk ($bankname, "num", 1, 1, "Invalid Date year.");
	}
	$v->isOk ($name, "string", 1, 255, "Invalid Name Paid To.");
	$v->isOk ($det, "string", 0, 255, "Invalid Details.");
	$v->isOk ($bal, "float", 1, 16, "Invalid balance amount.");
	$v->isOk ($amount, "float", 1, 16, "Invalid amount.");
	$v->isOk ($chrgvat, "string", 1, 3, "Invalid Vat Option.");
	$v->isOk ($accid, "num", 1, 20, "Invalid Account Paid to.");
	$date = $date_day."-".$date_month."-".$date_year;
	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# Check availability of funds
	if($amount > $bal){
		$v->isOk ('#$@!', "num", 1, 20, "Error : Amount is more than the avaliable funds.");
	}

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return add($confirm);
	}


	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$vatcode' AND zero='Yes'";
	$Ri = db_exec($Sl) or errDie("Unable to get vat codes");
	$vd = pg_fetch_array($Ri);

	if(pg_num_rows($Ri) > 0) {
		$chrgvat = "no";
	}

	# Keep the charge vat option stable
	if($chrgvat == "inc"){
		$vchrgvat = "Yes";
	}elseif($chrgvat == "exc"){
		$vchrgvat = "No";
	}else{
		$vchrgvat = "Non Vat";
	}

	# Get account name
	$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accid);
	$accnt = pg_fetch_array($accRslt);

	// Layout
	$confirm = "
		<h3>Add Petty Cash Requisistion</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='date_day' value='$date_day'>
			<input type='hidden' name='date_month' value='$date_month'>
			<input type='hidden' name='date_year' value='$date_year'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='name' value='$name'>
			<input type='hidden' name='det' value='$det'>
			<input type='hidden' name='amount' value='$amount'>
			<input type='hidden' name='chrgvat' value='$chrgvat'>
			<input type='hidden' name='accid' value='$accid'>
			<input type='hidden' name='vatcode' value='$vatcode'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td valign='center'>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Paid to</td>
				<td valign='center'>$name</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Details</td>
				<td valign='center'>$det</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Amount</td>
				<td valign='center'>".CUR." $amount</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Inclusive</td>
				<td valign='center'>$vchrgvat</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Code</td>
				<td>$vd[code]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Paid to</td>
				<td valign='center'>$accnt[topacc]/$accnt[accnum] - $accnt[accname]</td>
			</tr>
			<tr>
				<td><br></td>
			</tr>
			<tr>
				<td align='right'><input type='submit' name='key' value='&laquo Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='petty-req-add.php'>Add Petty Cash Requisition</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pettycashbook-view.php'>View Petty Cash Requisitions</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
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
	$v->isOk ($date, "date", 1, 10, "Invalid Date Entry.");
	$v->isOk ($name, "string", 1, 255, "Invalid Name Paid to.");
	$v->isOk ($det, "string", 0, 255, "Invalid Details.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($chrgvat, "string", 1, 3, "Invalid Vat Option.");
	$v->isOk ($accid, "string", 1, 255, "Invalid Account Paid to.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return add($confirm);
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	$vat  = strtolower($chrgvat);

	$vatcode += 0;

	pglib_transaction("BEGIN");

	# Record the payment record
	db_connect();

	$sql = "
		INSERT INTO pettycashbook (
			date, name, det, amount, accid, approved, chrgvat, div, vatcode
		) VALUES (
			'$date', '$name', '$det', '$amount', '$accid', 'n', '$vat', '".USER_DIV."', '$vatcode'
		)";
	$Rslt = db_exec ($sql) or errDie ("Unable to add Petty Cash requisition to database.",SELF);

	pglib_transaction("COMMIT");

	# Status report
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Petty Cash Requisistion</th>
			</tr>
			<tr class='datacell'>
				<td>Petty Cash Requisistion has been added to the Petty Cash book.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='20%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='petty-req-add.php'>Add Petty Cash Requisition</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='pettycashbook-view.php'>View Petty Cash Requisitions</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}


?>
