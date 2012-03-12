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
			# Display default output
			if(isset($_GET['cashid'])){
				$OUTPUT = edit($_GET['cashid']);
			}else{
				$OUTPUT = "<li class='err'> Invalid use of module</li>";
			}
	}
} else {
	# Display default output
	if(isset($_GET['cashid'])){
		$OUTPUT = edit($_GET['cashid']);
	}else{
		$OUTPUT = "<li class='err'> Invalid use of module</li>";
	}
}

# Get templete
require("../template.php");



# Insert details
function edit($cashid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashid, "num", 1, 4, "Invalid Petty Cash Requisition ID.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}
	


	# Connect to database
	db_Connect ();

	$sql = "SELECT * FROM pettycashbook WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
	if (pg_numrows ($cashRslt) < 1) {
		$OUTPUT = "<li class='err'>Requisistion not found in Cubit.</li>";
		return $OUTPUT;
	}
	$cash = pg_fetch_array($cashRslt);

	$accnts = mkAccSelect ("accid",$cash['accid']);

// 	core_connect();
// 	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
// 	$accRslt = db_exec($sql);
// 	if(pg_numrows($accRslt) < 1){
// 		return "<li> ERROR : There are no accounts in the category selected.</li>";
// 	}
// 	$accnts = "<select name='accid'>";
// 	while($acc = pg_fetch_array($accRslt)){
// 		# Check Disable
// 		if(isDisabled($acc['accid']))
// 			continue;
// 
// 		$sel = "";
// 		if($acc['accid'] == $cash['accid'])
// 			$sel = "selected";
// 
// 		$accnts .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
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
		return "<li> There are no Petty Cash funds available.</li>";
	}

	list($date_year, $date_month, $date_day) = explode("-", $cash['date']);

	# Keep the charge vat option stable
	if($cash['chrgvat'] == "inc"){
		$chin = "checked=yes";
		$chex = "";
		$chno = "";
	}elseif($cash['chrgvat'] == "exc"){
		$chin = "";
		$chex = "checked=yes";
		$chno = "";
	}else{
		$chin = "";
		$chex = "";
		$chno = "checked=yes";
	}

	
	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes ORDER BY code";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes="<select name='vatcode'>
	<option value='0'>Select</option>";

	while($vd=pg_fetch_array($Ri)) {
		if($vd['del']=="Yes" || $cash["vatcode"] == $vd["id"]) {
			$sel="selected";
		} else {
			$sel="";
		}
		$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
	}
	
	$Vatcodes.="</select>";

	// Layout
	$add = "
				<h3>Edit Petty Cash Requisistion</h3>
				<table ".TMPL_tblDflts." width='300'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='confirm'>
					<input type='hidden' name='cashid' value='$cashid'>
					<tr>
						<th>Field</th>
						<th>Value</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Date</td>
						<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Paid to</td>
						<td valign='center'><input size='20' name='name' value='$cash[name]'></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Details</td>
						<td valign='center'><textarea cols='18' rows='2' name='det'>$cash[det]</textarea></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Available Funds</td>
						<td><input type='hidden' name='bal' value='$accb[bal]'>".CUR." $accb[bal]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Amount</td>
						<td valign='center'>".CUR." <input type='text' size='10' name='amount' value='$cash[amount]'></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>VAT Inclusive</td>
						<td valign='center'>
							Yes <input type='radio' size='7' name='chrgvat' value='inc' $chin>
							No<input type='radio' size='7' name='chrgvat' value='exc' $chex>
							No VAT<input type='radio' size='7' name='chrgvat' value='nov' $chno>
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>VAT Code</td>
						<td>$Vatcodes</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Account Paid to</td>
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


# Insert details
function error($_POST, $err = "")
{

	# get vars
	extract ($_POST);

	core_connect();
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li> ERROR : There are no accounts in the category selected.</li>";
	}
	$accnts = "<select name='accid'>";
	while($acc = pg_fetch_array($accRslt)){
		# Check Disable
		if(isDisabled($acc['accid']))
			continue;
			
		$sel = "";
		if($acc['accid'] == $accid)
			$sel = "selected";

		$accnts .= "<option value='$acc[accid]' $sel>$acc[accname]</option>";
	}
	$accnts .= "</select>";

	# check available funds
	$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");
	core_connect();
	$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE accid = '$cashacc' AND div = '".USER_DIV."'";
	$accbRslt = db_exec($sql);
	if(pg_numrows($accbRslt) < 1){
		return "<li class='err'> Petty Cash Account not found.</li>";
	}
	$accb = pg_fetch_array($accbRslt);
	$accb['bal'] = sprint($accb['bal']);

	# mourn if the is no money
	if($accb['bal'] < 1){
		return "<li> There are no Petty Cash funds available.</li>";
	}

	# Keep the charge vat option stable
	if($chrgvat == "inc"){
		$chin = "checked=yes";
		$chex = "";
		$chno = "";
	}elseif($chrgvat == "exc"){
		$chin = "";
		$chex = "checked=yes";
		$chno = "";
	}else{
		$chin = "";
		$chex = "";
		$chno = "checked=yes";
	}
	
	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes ORDER BY code";
	$Ri=db_exec($Sl) or errDie("Unable to get vat codes");

	$Vatcodes="<select name='vatcode'>
	<option value='0'>Select</option>";

	while($vd=pg_fetch_array($Ri)) {
		if($vd['del']=="Yes" || $vatcode == $vd["id"]) {
			$sel="selected";
		} else {
			$sel="";
		}
		$Vatcodes.="<option value='$vd[id]' $sel>$vd[code]</option>";
	}
	
	$Vatcodes.="</select>";

	// Layout
	$add = "
				<h3>Edit Petty Cash Requisistion</h3>
				<table ".TMPL_tblDflts." width='300'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='confirm'>
					<input type='hidden' name='cashid' value='$cashid'>
					<tr>
						<td colspan='2'>$err</td>
					</tr>
					<tr>
						<th>Field</th>
						<th>Value</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Date</td>
						<td>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
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
							Yes <input type='radio' size='7' name='chrgvat' value='inc' $chin>
							No <input type='radio' size='7' name='chrgvat' value='exc' $chex>
							No VAT <input type='radio' size='7' name='chrgvat' value='nov' $chno>
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>VAT Code</td>
						<td>$Vatcodes</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Account Paid to</td>
						<td>$accnts</td>
					</tr>
					<tr>
						<td><br></td>
					</tr>
					<tr>
						<td></td>
						<td valign='center' colspan='2' align='right'><input type='submit' value='Confirm &raquo'></td>
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
	$v->isOk ($cashid, "num", 1, 4, "Invalid Petty Cash Requisition ID.");
	$v->isOk ($date_day, "num", 1, 2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1, 2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1, 4, "Invalid Date Year.");
	if(strlen($date_year) <> 4){
			$v->isOk ($bankname, "num", 1, 1, "Invalid Date year.");
	}
	$v->isOk ($name, "string", 1, 255, "Invalid Name Paid To.");
	$v->isOk ($det, "string", 0, 255, "Invalid Details.");
	$v->isOk ($bal, "float", 1, 10, "Invalid balance amount.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");
	$v->isOk ($chrgvat, "string", 1, 3, "Invalid VAT Option.");
	$v->isOk ($accid, "num", 1, 20, "Invalid Account Paid to.");
	$v->isOk ($vatcode, "num", 1, 9, "Invalid vatcode selection.");
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
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return error($_POST, $confirm);

		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get account name
	$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accid);
	$accnt = pg_fetch_array($accRslt);

	db_conn("cubit");
	$sql = "SELECT code FROM vatcodes WHERE id='$vatcode'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve vatcodes from Cubit.");
	$code = pg_fetch_result($rslt, 0);

	// Layout
	$confirm = "
					<h3>Edit Petty Cash Requisistion</h3>
					<h4>Confirm entry</h4>
					<table ".TMPL_tblDflts." width='300'>
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='write'>
						<input type='hidden' name='cashid' value='$cashid'>
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
							<td valign='center'>$chrgvat</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Code</td>
							<td>$code</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account Paid to</td>
							<td valign='center'>$accnt[topacc]/$accnt[accnum] - $accnt[accname]</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
							<td align='right'><input type='submit' value='Edit &raquo'></td>
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
	$v->isOk ($chrgvat, "string", 1, 3, "Invalid VAT Option.");
	$v->isOk ($accid, "string", 1, 255, "Invalid Account Paid to.");
	$v->isOk ($vatcode, "num", 1, 9, "Invalid vatcode selection.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return error($_POST, $confirm);
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	$vat  = strtolower($chrgvat);

	# Record the payment record
	db_connect();
	// $sql = "INSERT INTO pettycashbook(date, name, det, amount, accid, approved, chrgvat, div) VALUES ('$date', '$name', '$det', '$amount', '$accid', 'n', '$vat', '".USER_DIV."')";
	$sql = "UPDATE pettycashbook SET date = '$date', name='$name', det = '$det', amount = '$amount', accid = '$accid', chrgvat = '$vat', vatcode = '$vatcode' WHERE cashid = '$cashid'";
	$Rslt = db_exec ($sql) or errDie ("Unable to edit Petty Cash requisition in database.",SELF);

    # Status report
	$write = "
				<table ".TMPL_tblDflts." width='30%'>
					<tr>
						<th>Petty Cash Requisistion</th>
					</tr>
					<tr class='datacell'>
						<td>Petty Cash Requisistion has been edited on the Petty Cash book.</td>
					</tr>
				</table>
				<p>
				<table ".TMPL_tblDflts." width='15%'>
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