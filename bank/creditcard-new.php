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
			$OUTPUT = view();
        }
} else {
	# Display default output
	$OUTPUT = view();
}

# get template
require("../template.php");




# Default view
function view($VARS = array(), $err="")
{

	extract ($VARS);

	if(!isset($accid))
		$accid = "";

	$vars = array(
			"bankname", 
			"branchname", 
			"loc", 
			"fcid", 
			"branchcode", 
			"accname", 
			"accnum", 
			"cardnum", 
			"mon", 
			"year", 
			"lastdigits", 
			"cardname", 
			"cardtyp", 
			"details"
		);

	$vard = array(
			"cardtyp" => "Visa", 
			"mon" => date("m"), 
			"year" => date("Y")
		);

	foreach($vars as $key => $val){
		if(!isset($$val)){
			$$val = (isset($vard[$val])) ? $vard[$val] : "";
		}
	}

	if(strlen($lastdigits) < 1){
		$lastdigits = "000";
	}

	db_connect();
	# Locations drop down
	$locs = array("loc"=>"Local", "int"=>"International");
	$locsel = extlib_cpsel("loc", $locs, $loc);

	# currency drop down
	$currsel = ext_unddbsel("fcid", "currency", "fcid", "descrip", "There are is no currency found in Cubit, please add currency first.", $fcid);

	$chm = ""; $chv = ""; $cho = "";
	if($cardtyp == 'Visa'){
		$chv = "checked=yes";
	}elseif($cardtyp == 'Mastercard'){
		$chm = "checked=yes";
	}else{
		$cho = "checked=yes";
	}

	core_connect();
	$sql = "SELECT * FROM accounts WHERE acctype = 'B' AND div = '".USER_DIV."' ORDER BY accname";
	$accRslt = db_exec($sql) or errDie("Could not retrieve accounts from Cubit.",SELF);
	if(pg_numrows($accRslt) < 1){
		return "<li> There are no balance accouts in Cubit.";
	}
	$accs = "<select name='accid'>";
	while($acc = pg_fetch_array($accRslt)){
		if(isbank($acc['accid']))
			continue;
		if(isb($acc['accid'])) {
			continue;
		}
		if($accid == $acc['accid']){
			$accs .= "<option value='$acc[accid]' selected>$acc[accname]</option>";
		}else {
			$accs .= "<option value='$acc[accid]'>$acc[accname]</option>";
		}
	}
	$accs .= "</select>";


	//layout
	$view = "
		<h3>Add New Credit Card Account</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Bank Name</td>
				<td><input type='text' size='20' name='bankname' value='$bankname'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Type</td>
				<td>$locsel</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Currency</td>
				<td>$currsel</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Branch Name</td>
				<td><input type='text' size='20' name='branchname' value='$branchname'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Branch Code</td>
				<td><input type='text' size='20' name='branchcode' value='$branchcode'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Account Name</td>
				<td><input type='text' size='20' name='accname' maxlength='50' value='$accname'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Account Number</td>
				<td><input type='text' size='20' name='accnum' value='$accnum'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Ledger Account</td>
				<td>$accs</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Card Number</td>
				<td><input type='text' size='25' name='cardnum' maxlength='16' value='$cardnum'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Expiry Date</td>
				<td>
					<input type='text' size='2' name='mon' maxlength='2' value='$mon'>-
					<input type='text' size='4' name='year' maxlength='4' value='$year'>MM-YYYY
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Last 3 Digits at back of Card</td>
				<td><input type='text' size='3' maxlength='3' name='lastdigits' value='$lastdigits'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Card Type</td>
				<td><input type='radio' name='cardtyp' value='Visa' $chv>Visa &nbsp;&nbsp; <input type='radio' name='cardtyp' value='Mastercard' $chm> Mastercard &nbsp;&nbsp;&nbsp;<input type='radio' name='cardtyp' value='other' $cho>Other: <input type='text' name='cardname' value='$cardname'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Details</td>
				<td><textarea cols='20' rows='3' name='details'>$details</textarea></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='button' value='&laquo Cancel' onClick='javascript:history.back();'></td>
				<td><input type='submit' value='Confirm &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../core/acc-new2.php'>Add Ledger Account</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $view;

}


# confirm
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankname, "string", 1, 50, "Invalid Bank name.");
	$v->isOk ($loc, "string", 1, 3, "Invalid Type.");
	$v->isOk ($fcid, "num", 1, 30, "Invalid Currency.");
	$v->isOk ($branchname, "string", 1, 50, "Invalid Branch Name.");
	$v->isOk ($branchcode, "string", 1, 15, "Invalid Branch Code.");
	$v->isOk ($accname, "string", 1, 50, "Invalid Account Name.");
	$v->isOk ($accnum, "num", 1, 30, "Invalid Account Number.");
	$v->isOk ($accid, "num", 1, 20, "Invalid Ledger Account.");
	$v->isOk ($cardnum, "num", 16, 16, "Invalid Card Number.");
	$v->isOk ($mon, "num", 1, 2, "Invalid Expiry date.");
	$v->isOk ($year, "num", 4, 4, "Invalid Expiry date.");
	$v->isOk ($lastdigits, "num", 3, 3, "Invalid Last Digits.");
	if(isset($cardtyp)){
		$v->isOk ($cardtyp, "string", 1, 255, "Invalid Card Type.");
	}else{
		$v->isOk ("#error#", "num", 1, 255, "Invalid Card Type.");
	}
	$v->isOk ($details, "string", 1, 255, "Invalid Details.");
	if($cardtyp == 'other'){
		$v->isOk ($cardname, "string", 1, 255, "Invalid Card Type.");
		$cardtyp = $cardname;
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err> - ".$e["msg"]."<br>";
		}
		return view($HTTP_POST_VARS, $confirm);
	}


	# get ledger account name
	core_connect();
	$sql = "SELECT accname,topacc,accnum FROM accounts WHERE accid = '$accid' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$acc = pg_fetch_array($accRslt);

	# Locations drop down
	$locs = array("loc"=>"Local", "int"=>"International");
	$curr = getSymbol($fcid);

	// layout
	$confirm = "
		<h3>Add New Credit Card Account</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='bankname' value='$bankname'>
			<input type='hidden' name='branchname' value='$branchname'>
			<input type='hidden' name='loc' value='$loc'>
			<input type='hidden' name='fcid' value='$fcid'>
			<input type='hidden' name='branchcode' value='$branchcode'>
			<input type='hidden' name='accname' value='$accname'>
			<input type='hidden' name='accnum' value='$accnum'>
			<input type='hidden' name='accid' value='$accid'>
			<input type='hidden' name='cardnum' value='$cardnum'>
			<input type='hidden' name='mon' value='$mon'>
			<input type='hidden' name='year' value='$year'>
			<input type='hidden' name='lastdigits' value='$lastdigits'>
			<input type='hidden' name='cardtyp' value='$cardtyp'>
			<input type='hidden' name='details' value='$details'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank Name</td>
				<td>$bankname</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Type</td>
				<td>$locs[$loc]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Currency</td>
				<td>$curr[symbol] - $curr[name]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Branch Name</td>
				<td>$branchname</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Branch Code</td>
				<td>$branchcode</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Name</td>
				<td>$accname</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Number</td>
				<td>$accnum</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Ledger Account</td>
				<td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Card Number</td>
				<td>$cardnum</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Expiry Date</td>
				<td>$mon-$year</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Last 3 Digits at back of Card</td>
				<td>$lastdigits</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Card Type</td>
				<td>$cardtyp</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Details</td>
				<td>$details</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='submit' name='btn_back' value='&laquo Correction'></td>
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
				<td><a href='../core/acc-new2.php'>Add Ledger Account</a></td>
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

	# get vars
	extract ($HTTP_POST_VARS);

	if (isset($btn_back))
		return view ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankname, "string", 1, 50, "Invalid Bank name.");
	$v->isOk ($fcid, "num", 1, 30, "Invalid Currency.");
	$v->isOk ($branchname, "string", 1, 50, "Invalid Branch Name.");
	$v->isOk ($branchcode, "string", 1, 15, "Invalid Branch Code.");
	$v->isOk ($accname, "string", 1, 50, "Invalid Account Name.");
	$v->isOk ($accnum, "num", 1, 30, "Invalid Account Number.");
	$v->isOk ($accid, "num", 1, 20, "Invalid Ledger Account.");
	$v->isOk ($cardnum, "num", 1, 16, "Invalid Card Number.");
	$v->isOk ($mon, "num", 1, 2, "Invalid Expiry date.");
	$v->isOk ($year, "num", 1, 4, "Invalid Expiry date.");
	$v->isOk ($lastdigits, "num", 1, 3, "Invalid Last Digits.");
	if(isset($cardtyp)){
		$v->isOk ($cardtyp, "string", 1, 255, "Invalid Card Type.");
	}else{
		$v->isOk ("#error#", "num", 1, 255, "Invalid Card Type.");
	}
	$v->isOk ($details, "string", 1, 255, "Invalid Details.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return view($HTTP_POST_VARS, $confirm);
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	$curr = getSymbol($fcid);

	db_connect();
	# Register bank account
	$sql = "INSERT INTO cubit.bankacct (type, acctype, bankname, btype, fcid, currency,
				branchname, branchcode, accname, accnum, cardnum, mon, year, digits,
				cardtype ,details, div)
			VALUES ('cr', 'Credit Card', '$bankname', '$loc', '$fcid', '$curr[name]',
				'$branchname', '$branchcode', '$accname', '$accnum', '$cardnum', '$mon',
				'$year', '$lastdigits', '$cardtyp', '$details', '".USER_DIV."')";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank account to database.");

	# Get last id from bank accounts
	$bankid = pglib_lastid("cubit.bankacct","bankid");

	# Create hook
	core_connect();
	$hook = "INSERT INTO bankacc(accid, accnum, div) VALUES('$bankid', '$accid', '".USER_DIV."')";
	$Rlst = db_exec($hook) or errDie("Unable to add hook for for new bank account", SELF);

	# Status report
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>New Credit Card Account added to database</th>
			</tr>
			<tr class='datacell'>
				<td>New Credit Card Account $accname, bas been successfully added to Cubit.</td>
			</tr>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='bankacct-view.php'>View Bank Accounts</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}


?>