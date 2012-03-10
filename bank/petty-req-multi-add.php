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
		case "add":
			$OUTPUT = add($HTTP_POST_VARS);
			break;
		case "confirm":
			if (isset ($_REQUEST["another"])){
				$OUTPUT = add ($HTTP_POST_VARS);
			}else {
				$OUTPUT = confirm($HTTP_POST_VARS);
			}
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		case "":
			$OUTPUT = add ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = add ($HTTP_POST_VARS);
	}
} else {
	# Display default output
	$OUTPUT = add ($HTTP_POST_VARS);
}

# Get templete
require("../template.php");



# Insert details
function add($HTTP_POST_VARS, $err = "")
{

	# Get vars
	extract ($HTTP_POST_VARS);

	if (!isset($rnum)) 
		$rnum = 1;

	if($rnum < 1) return "<li class='err'> - Invalid number of requisitions</li>";

	core_connect();

	# Check available funds
	$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");

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

	$jump_bot = "";
	if (isset ($another)) {
		$jump_bot = "
			<script>
				window.location.hash='bottom';
			</script>";
		$rnum++;
	}

	$reqs = "";
	for($i = 0; $i < $rnum; $i++){

		core_connect();

		$check1 = "";
		$check2 = "";
		$check3 = "";
		if (isset ($chrgvat[$i]) AND $chrgvat[$i] == "nov")
			$check3 = "checked='yes'";
		elseif (isset($chrgvat[$i]) AND $chrgvat[$i] == "exc")
			$check2 = "checked='yes'";
		else 
			$check1 = "checked='yes'";

		if (!isset ($o_day[$i])){
			$trans_date_setting = getCSetting ("USE_TRANSACTION_DATE");
			if (isset ($trans_date_setting) AND $trans_date_setting == "yes"){
				$trans_date_value = getCSetting ("TRANSACTION_DATE");
				$date_arr = explode ("-", $trans_date_value);
				$o_year[$i] = $date_arr[0];
				$o_month[$i] = $date_arr[1];
				$o_day[$i] = $date_arr[2];
			}else {
				$o_year[$i] = date("Y");
				$o_month[$i] = date("m");
				$o_day[$i] = date("d");
			}
		}

		$reqs .= "
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap>".mkDateSelectA("o", $i, $o_year[$i], $o_month[$i], $o_day[$i])."</td>
				<td><input type='text' size='20' name='name[]' value='$name[$i]'></td>
				<td nowrap valign='center'>".CUR." <input type='text' size='6' name='amount[]' value='$amount[$i]'></td>
				<td><input type='text' size='26' name='details[]' value='$details[$i]'></td>
				<td valign='center'>
					<input type='radio' size='7' name='chrgvat[$i]' value='inc' $check1> Yes<br>
					<input type='radio' size='7' name='chrgvat[$i]' value='exc' $check2> No<br>
					<input type='radio' size='7' name='chrgvat[$i]' value='nov' $check3> No VAT
				</td>
				<td>".mkAccSelect ("accid[$i]", $accid[$i])."</td>
			</tr>";
	}

	$reqs .= "
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2' align='right'><b>Total:</b></td>
			<td>".CUR." ".sprint(array_sum ($amount))."</td>
			<td colspan='3'></td>
		</tr>";

	// Layout
	$add = "
		<h3>Add Petty Cash Requisistions</h3>
		<form action='".SELF."' method='POST' name='form'>
		<table ".TMPL_tblDflts." width='300'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' size='3' name='rnum' value='$rnum'>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Available Funds</td>
				<td><input type='hidden' name='bal' value='$accb[bal]'>".CUR." $accb[bal]</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Paid to</th>
				<th>Amount</th>
				<th>Details</th>
				<th>VAT Inclusive</th>
				<th>Account Paid to <input align='right' type='button' onClick=\"window.open('../core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
			</tr>
			$reqs
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='another' value='Add Another'></td>
				<td valign='center'><input type='submit' value='Confirm >'></td>
			</tr>
		</table>
		<a name='bottom'>
		$jump_bot
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
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	foreach($amount as $key => $value){
		if (empty($value))
			continue;
		$v->isOk ($o_day[$key], "num", 1, 2, "Invalid Date day.");
		$v->isOk ($o_month[$key], "num", 1, 2, "Invalid Date month.");
		$v->isOk ($o_year[$key], "num", 1, 4, "Invalid Date Year.");
		if(strlen($o_year[$key]) <> 4){
			$v->isOk ("sasas", "num", 1, 1, "Invalid Date year.");
		}
		$v->isOk ($name[$key], "string", 1, 255, "Invalid Name Paid To.");
		$v->isOk ($details[$key], "string", 0, 255, "Invalid Details.");
		$v->isOk ($amount[$key], "float", 1, 10, "Invalid amount.");
		$v->isOk ($chrgvat[$key], "string", 1, 3, "Invalid VAT Option.");
		$v->isOk ($accid[$key], "num", 1, 20, "Invalid Account Paid to.");
		$date[$key] = $o_year[$key]."-".$o_month[$key]."-".$o_day[$key];
		$o_month[$key] += 0;
		$o_day[$key] += 0;
		$o_year[$key] += 0;
		if(!checkdate($o_month[$key], $o_day[$key], $o_year[$key])){
			$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
		}
	}

	# Check availability of funds
	if(array_sum($amount) > $bal){
		$v->isOk ('#$@!', "num", 1, 20, "Error : Total Amount is more than the avaliable funds.");
	}

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return add($HTTP_POST_VARS, $confirm);

		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	$reqs = "";
	foreach($amount as $key => $value){
		if (empty($value))
			continue;
		# Keep the charge vat option stable
		if($chrgvat[$key] == "inc"){
			$vchrgvat = "Yes";

			db_conn('cubit');

			$Sl = "SELECT * FROM vatcodes ORDER BY code";
			$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "<select name='vatcode[$key]'>";
			while($vd = pg_fetch_array($Ri)) {
				$Vatcodes .= "<option value='$vd[id]'>$vd[code]</option>";
			}
			$Vatcodes .= "</select>";

		}elseif($chrgvat[$key] == "exc"){
			$vchrgvat = "No";

			db_conn('cubit');
			$Sl = "SELECT * FROM vatcodes ORDER BY code";
			$Ri = db_exec($Sl) or errDie("Unable to get vat codes");

			$Vatcodes = "<select name='vatcode[$key]'>";
			while($vd = pg_fetch_array($Ri)) {
				$Vatcodes .= "<option value='$vd[id]'>$vd[code]</option>";
			}
			$Vatcodes .= "</select>";
		}else{
			$vchrgvat = "Non VAT";

			$Vatcodes = "<input type='hidden' name='vatcode[$key]' value='0'>0";
		}

		# Get account name
		$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $accid[$key]);
		$accnt = pg_fetch_array($accRslt);

		$reqs .= "
			<input type='hidden' name='o_day[]' value='$o_day[$key]'>
			<input type='hidden' name='o_month[]' value='$o_month[$key]'>
			<input type='hidden' name='o_year[]' value='$o_year[$key]'>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='date[]' value='$date[$key]'>$date[$key]</td>
				<td><input type='hidden' size='20' name='name[]' value='$name[$key]'>$name[$key]</td>
				<td valign='center'><input type='hidden' type='text' size='10' name='amount[]' value='$amount[$key]'>".CUR." $amount[$key]</td>
				<td><input type='hidden' name='details[]' value='$details[$key]'>$details[$key]</td>
				<td valign='center'><input type='hidden' name='chrgvat[]' value='$chrgvat[$key]'>$vchrgvat</td>
				<td>$Vatcodes</td>
				<td><input type='hidden' name='accid[]' value='$accid[$key]'>$accnt[topacc]/$accnt[accnum] - $accnt[accname]</td>
			</tr>";
	}

	// Layout
	$confirm = "
		<h3>Confirm Petty Cash Requisistions</h3>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='rnum' value='$rnum'>
		<table ".TMPL_tblDflts." width='300'>
			<input type='hidden' name='key' value='write'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Available Funds</td>
				<td><input type='hidden' name='bal' value='$bal'>".CUR." $bal</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Paid to</th>
				<th>Amount</th>
				<th>Details</th>
				<th>VAT Inclusive</th>
				<th>Vat Code</th>
				<th>Account Paid to</th>
			</tr>
			$reqs
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='< Cancel'></td>
				<td valign='center'><input type='submit' value='Confirm >'></td>
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
	return $confirm;

}



# write
function write($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	if (isset ($back)) 
		return add($HTTP_POST_VARS);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($amount as $key => $value){
		$v->isOk ($date[$key], "date", 1, 14, "Invalid Date.");
		$v->isOk ($name[$key], "string", 1, 255, "Invalid Name Paid To.");
		$v->isOk ($details[$key], "string", 0, 255, "Invalid Details.");
		$v->isOk ($amount[$key], "float", 1, 10, "Invalid amount.");
		$v->isOk ($chrgvat[$key], "string", 1, 3, "Invalid VAT Option.");
		$v->isOk ($accid[$key], "num", 1, 20, "Invalid Account Paid to.");
	}

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return add($HTTP_POST_VARS, $confirm);
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	pglib_transaction("BEGIN");

	foreach($amount as $key => $val){
		# Date format
		$date[$key] = explode("-", $date[$key]);
		$date[$key] = $date[$key][0]."-".$date[$key][1]."-".$date[$key][2];

		$vat[$key]  = strtolower($chrgvat[$key]);

		# Record the payment record
		db_connect();
		$sql = "
			INSERT INTO pettycashbook (
				date, name, det, amount, accid, approved, chrgvat, 
				div, vatcode
			) VALUES (
				'$date[$key]', '$name[$key]', '$details[$key]', '$amount[$key]', '$accid[$key]', 'n', '$vat[$key]', 
				'".USER_DIV."', '$vatcode[$key]'
			)";
		$Rslt = db_exec ($sql) or errDie ("Unable to add Petty Cash requisition to database.",SELF);
	}

	pglib_transaction("COMMIT");

    # Status report
	$write = "
		<table ".TMPL_tblDflts." width='30%'>
			<tr>
				<th>Petty Cash Requisistions</th>
			</tr>
			<tr class='datacell'>
				<td>Petty Cash Requisistions have been added to the Petty Cash book.</td>
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