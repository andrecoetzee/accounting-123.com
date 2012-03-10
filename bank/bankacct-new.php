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
require("../libs/ext.lib.php");

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
function view($acctype="", $bankname="", $loc = "", $branchname="", $branchcode="", $accname="", $accnum="", $details="", $err="")
{

	db_connect();

	# Locations drop down
	$locs = array("loc"=>"Local", "int"=>"International");
	$locsel = extlib_cpsel("loc", $locs, $loc);

	# currency drop down
	$currsel = ext_unddbsel("fcid", "currency", "fcid", "descrip", "There are is no currency found in Cubit, please add currency first.", "");

	//layout
	$view = "
		<h3>Add New Bank Account</h3>
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			$err
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Type of Account</td>
				<td valign='center'><input type='text' size='20' name='acctype' value='$acctype'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Bank Name</td>
				<td valign='center'><input type='text' size='20' name='bankname' value='$bankname'></td>
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
				<td valign='center'><input type='text' size='20' name='branchname' value='$branchname'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Branch Code</td>
				<td valign='center'><input type='text' size='20' name='branchcode' value='$branchcode'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Account Name</td>
				<td valign='center'><input type='text' size='20' name='accname' maxlength='50' value='$accname'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Account Number</td>
				<td valign='center'><input type='text' size='20' name='accnum' value='$accnum'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Account Category</td>
				<td>
					<select name='catid'>";

	core_connect();

	$sql = "SELECT * FROM balance WHERE div = '".USER_DIV."' ORDER BY catname";
	$catRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);
	$rows = pg_numrows($catRslt);

	if($rows < 1){
		return "There are no Account Categories under Balance";
	}

	while($cat = pg_fetch_array($catRslt)){
		$view .= "<option value='$cat[catid]'>$cat[catname]</option>";
	}

	$view .= "
					</select>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Details</td>
				<td valign='center'><textarea cols='20' rows='5' name='details'>$details</textarea></td></tr>
			<tr>
				<td></td>
				<td valign='center' align='right'><input type='submit' value='Confirm &raquo;'></td>
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
	$v->isOk ($acctype, "string", 1, 30, "Invalid Account Type.");
	$v->isOk ($bankname, "string", 1, 50, "Invalid Bank name.");
	$v->isOk ($loc, "string", 1, 3, "Invalid Type.");
	$v->isOk ($fcid, "num", 1, 30, "Invalid Currency.");
	$v->isOk ($branchname, "string", 1, 50, "Invalid Branch Name.");
	$v->isOk ($branchcode, "string", 1, 15, "Invalid Branch Code.");
	$v->isOk ($accname, "string", 1, 50, "Invalid Account Name.");
	$v->isOk ($accnum, "num", 1, 30, "Invalid Account Number.");
	$v->isOk ($catid, "string", 1, 4, "Invalid Category.");
	$v->isOk ($details, "string", 1, 255, "Invalid Details.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "-".$e["msg"]."<br>";
		}
		$err = "<tr><td class='err' colspan='2'>$confirm</td></tr>
		<tr><td colspan='2'><br></td></tr>";
		return view($acctype, $bankname, $loc, $branchname, $branchcode, $accname, $accnum, $details, $err);
	}

	core_connect();

	# income accounts ($inc)
	$glacc = "<select name='glacc'>";
	$sql = "SELECT * FROM accounts WHERE catid ='$catid' AND div = '".USER_DIV."' ORDER BY accname";
	$accRslt = db_exec($sql);
	$numrows = pg_numrows($accRslt);
	if(empty($numrows)){
		return "<li> - There are no accounts yet in Cubit. Please set up accounts first.</li>";
	}
	while($acc = pg_fetch_array($accRslt)){
		if(isbank($acc['accid']))
			continue;
			if(isb($acc['accid'])) {
				continue;
			}
			$glacc .= "<option value='$acc[accid]'>$acc[accname]</option>";
	}
	$glacc .= "</select>";

	//processes
	db_connect();

	# check if account name doesn't exist
	$sql = "SELECT bankname FROM bankacct WHERE accname ='$accname' AND type != 'cr' AND type != 'ptrl' AND div = '".USER_DIV."'";
	$checkRslt = db_exec ($sql) or errDie ("Unable to select bank details from database.",SELF);
		$check = pg_numrows ($checkRslt);
		if (!empty($check)) {
		return "<li class='err'>The Account : $accname Already Exits, please choose another account name.<p>
				<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
	}

	# check accnum and bankname
	$sql = "SELECT bankname FROM bankacct WHERE bankname ='$bankname' AND accnum = '$accnum' AND type != 'cr' AND type != 'ptrl' AND div = '".USER_DIV."'";
	$checkRslt = db_exec ($sql) or errDie ("Unable to select bank details from database.",SELF);
	$check = pg_numrows ($checkRslt);
	if (!empty($check)) {
		return "<li class='err'>The Account with account number : $accnum, held at $bankname already exits.<p>
			<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
	}

	# Locations drop down
	$locs = array("loc"=>"Local", "int"=>"International");
	$curr = getSymbol($fcid);

	// layout
	$confirm = "
		<h3>Add New Account to database</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='acctype' value='$acctype'>
			<input type='hidden' name='bankname' value='$bankname'>
			<input type='hidden' name='loc' value='$loc'>
			<input type='hidden' name='fcid' value='$fcid'>
			<input type='hidden' name='branchname' value='$branchname'>
			<input type='hidden' name='branchcode' value='$branchcode'>
			<input type='hidden' name='accname' value='$accname'>
			<input type='hidden' name='accnum' value='$accnum'>
			<input type='hidden' name='catid' value='$catid'>
			<input type='hidden' name='details' value='$details'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Type</td>
				<td>$acctype</td>
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
				<td>Legder account</td>
				<td>$glacc</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Details</td>
				<td>$details</td>
			</tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
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

	if(isset($back)) {
		return view($acctype, $bankname, $loc, $branchname, $branchcode, $accname, $accnum, $details);
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($acctype, "string", 1, 30, "Invalid Account Type.");
	$v->isOk ($bankname, "string", 1, 50, "Invalid Bank name.");
	$v->isOk ($fcid, "num", 1, 30, "Invalid Currency.");
	$v->isOk ($branchname, "string", 1, 50, "Invalid Branch Name.");
	$v->isOk ($branchcode, "string", 1, 15, "Invalid Branch Code.");
	$v->isOk ($accname, "string", 1, 50, "Invalid Account Name.");
	$v->isOk ($accnum, "num", 1, 20, "Invalid Account Number.");
	$v->isOk ($glacc, "num", 1, 20, "Invalid Ledger account.");
	$v->isOk ($details, "string", 1, 255, "Invalid Details.");

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


	$curr = getSymbol($fcid);

	# processes
	db_connect();

	# begin sql transaction
	# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	# Register bank account
	$sql = "
		INSERT INTO bankacct (
			acctype, bankname, btype, fcid, currency, branchname, 
			branchcode, accname, accnum, details, div
		) VALUES (
			'$acctype', '$bankname', '$loc', '$fcid', '$curr[name]', '$branchname', 
			'$branchcode', '$accname', '$accnum', '$details', '".USER_DIV."'
		)";
	$bankAccRslt = db_exec ($sql) or errDie ("Unable to add bank account to database.");

	# Get last id from bank accounts
	$accid = pglib_lastid("cubit.bankacct","bankid");

	# Create hook
	core_connect();

	$hook = "INSERT INTO bankacc(accid, accnum, div) VALUES('$accid', '$glacc', '".USER_DIV."')";
	$Rlst = db_exec($hook) or errDie("Unable to add hiik for for new bank account", SELF);

	# Commit sql transaction
	# pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);

	# status report
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>New Bank Account added to database</th>
			</tr>
			<tr class='datacell'>
				<td>New Bank Account , $accname, was successfully added to Cubit.</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='bankacct-new.php'>Add New Bank Account</a></td>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</tr>";
	return $write;

}


?>