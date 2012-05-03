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
			$OUTPUT = editAccnt($_GET['bankid']);
	}
} else {
	# Display default output
	if(isset($_GET['bankid'])){
		$OUTPUT = editAccnt($_GET['bankid']);
	}else{
		$OUTPUT = editAccnt('none');
	}
}

# get templete
require("../template.php");




function editAccnt ($bankid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
    $v->isOk ($bankid, "num", 1, 4, "Invalid Bank Account ID.");

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

	// Connect to database
	Db_Connect ();

	$sql = "SELECT * FROM bankacct WHERE bankid='$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank account details from database.", SELF);
	$numrows = pg_numrows ($bankRslt);
	if ($numrows < 1) {
		$OUTPUT = "<li> - Invalid Bank account ID.";
		return $OUTPUT;
	}

	global $_POST;

	extract($_POST);

	$accnt = pg_fetch_array($bankRslt);

	if(isset($accname)) {
		$accnt['accname'] = $accname;
		$accnt['acctype'] = $acctype;
		$accnt['bankname'] = $bankname;
		$accnt['branchname'] = $branchname;
		$accnt['branchcode'] = $branchcode;
		$accnt['accnum'] = $accnum;
		$accnt['details'] = $details;
		$accnt['btype'] = $loc;
	}

	if(strlen($accnt['accname']) < 20){
		$size = 20;
	}else{
		$size = strlen($accnt['accname']);
	}

	// Get Bank account [the traditional way re: hook of hook]
	core_connect();

    $sql = "SELECT * FROM bankacc WHERE accid = '$bankid' AND div = '".USER_DIV."'";
    $Rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
    # check if link exists
    if(pg_numrows($Rslt) <1){
        return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
    }

    $bank = pg_fetch_array($Rslt);
    $bankaccid = $bank["accnum"];

	# Check account balance
	$sql = "SELECT * FROM trial_bal WHERE period='".PRD_DB."' AND accid = '$bankaccid' AND debit > 0 OR accid = '$bankaccid' AND credit > 0";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) > 0){
		$acc = pg_fetch_array($accRslt);
		$account = "<input type='hidden' name='glacc' value='$acc[accid]'>$acc[accname]";
	}else{
		core_connect();
		# income accounts ($inc)
		$account = "<select name='glacc'>";
		$sql = "SELECT * FROM accounts WHERE acctype ='B' ORDER BY accname";
		$accRslt = db_exec($sql);
		$numrows = pg_numrows($accRslt);
		if(empty($numrows)){
		return "<li> - There are no accounts yet in Cubit. Please set up accounts first.</li>";
		}
		while($acc = pg_fetch_array($accRslt)){
			if(isb($acc['accid'])) {
				continue;
			}

			if($acc['accid'] == $bankaccid){
				$sal = "selected";
			}else{
				$sal = "";
			}
			$account .= "<option value='$acc[accid]' $sal>$acc[accname]</option>";
		}
		$account .="</select>";
	}

	db_connect();

	# Locations drop down
	$locs = array("loc"=>"Local", "int"=>"International");
	$locsel = extlib_cpsel("loc", $locs, $accnt['btype']);

	# currency drop down
	$currsel = ext_unddbsel("fcid", "currency", "fcid", "descrip", "There are is no currency found in Cubit, please add currency first.", $accnt['fcid']);

	// Set up table to display in
	$OUTPUT = "
		<h3>Edit Bank Account</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='bankid' value='$bankid'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Type of Account</td>
				<td valign='center'><input type='text' size='20' name='acctype' value='$accnt[acctype]'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Bank Name</td>
				<td valign='center'><input type='text' size='20' name='bankname' value='$accnt[bankname]'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Type</td>
				<td>$locsel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Currency</td>
				<td>$currsel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Branch Name</td>
				<td valign='center'><input type='text' size='20' name='branchname' value='$accnt[branchname]'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Branch Code</td>
				<td valign='center'><input type='text' size='20' name='branchcode' value='$accnt[branchcode]'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account Name</td>
				<td valign='center'><input type='text' size='$size' name='accname'  value='$accnt[accname]'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account Number</td>
				<td valign='center'><input type='text' name='accnum'  value='$accnt[accnum]'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Ledger Account</td>
				<td valign='center'>$account</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Details</td>
				<td valign='center'><textarea cols='20' rows='5' name='details'>$accnt[details]</textarea></td></tr>
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
			<tr class='".bg_class()."'>
				<td><a href='bankacct-view.php'>View Bank Accounts</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $OUTPUT;

}




function confirm($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 4, "Invalid Bank Account ID.");
	$v->isOk ($acctype, "string", 1, 30, "Invalid Account Type.");
	$v->isOk ($bankname, "string", 1, 50, "Invalid Bank name.");
	$v->isOk ($loc, "string", 1, 3, "Invalid Type.");
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
		return $confirm.editAccnt($bankid);
	}

	# get ledger account name
	core_connect();

	$sql = "SELECT accname FROM accounts WHERE accid = '$glacc' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$acc = pg_fetch_array($accRslt);
	$glaccname = $acc['accname'];

	# Locations drop down
	$locs = array("loc"=>"Local", "int"=>"International");
	$curr = getSymbol($fcid);

	// Layout
	$confirm = "
		<h3>Confirm Account Edit</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='bankid' value='$bankid'>
			<input type='hidden' name='acctype' value='$acctype'>
			<input type='hidden' name='bankname' value='$bankname'>
			<input type='hidden' name='loc' value='$loc'>
			<input type='hidden' name='fcid' value='$fcid'>
			<input type='hidden' name='branchname' value='$branchname'>
			<input type='hidden' name='branchcode' value='$branchcode'>
			<input type='hidden' name='accname' value='$accname'>
			<input type='hidden' name='accnum' value='$accnum'>
			<input type='hidden' name='glacc' value='$glacc'>
			<input type='hidden' name='details' value='$details'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account Type</td>
				<td>$acctype</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Bank Name</td>
				<td>$bankname</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Type</td>
				<td>$locs[$loc]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Currency</td>
				<td>$curr[symbol] - $curr[name]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Branch Name</td>
				<td>$branchname</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Branch Code</td>
				<td>$branchcode</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account Name</td>
				<td>$accname</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Account Number</td>
				<td>$accnum</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Legder account</td>
				<td>$glaccname</td>
			</tr>
			<tr class='".bg_class()."'>
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
			<tr class='".bg_class()."'>
				<td><a href='bankacct-view.php'>View Bank Accounts</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $confirm;

}




# write
function write($_POST)
{

	# Connect to cubit
	db_connect();

	# Get vars
	extract ($_POST);

	if(isset($back)) {
		return editAccnt($bankid);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 4, "Invalid Bank Account ID.");
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

	db_connect();

	$sql = "UPDATE bankacct SET acctype = '$acctype', bankname = '$bankname', btype = '$loc', fcid = '$fcid', currency = '$curr[name]', branchname='$branchname', branchcode='$branchcode', accname='$accname', accnum='$accnum', details='$details' WHERE bankid='$bankid' AND div = '".USER_DIV."'";
	$nwUsrRslt = db_exec ($sql) or errDie ("Unable to edit bank account.");

	# ReCreate hook
	core_connect();
	$hook = "UPDATE bankacc SET accnum = '$glacc' WHERE accid = '$bankid'";
	$Rlst = db_exec($hook) or errDie("Unable to add hook for for bank account", SELF);

	# Status report
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Bank Account Edited</th>
			</tr>
			<tr class='datacell'>
				<td>Bank Account <b>$accname</b>, was successfully edited.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='bankacct-view.php'>View Bank Accounts</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}



?>