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
		case "slctacc":
			$OUTPUT = slctAcc($HTTP_POST_VARS);
			break;
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slctAcc();
	}
} else {
	$OUTPUT = slctAcc();
}

# get template
require("template.php");



# Select Account
function slctAcc()
{

	core_connect();

	$accnts = mkAccSelect ("accid", 1, ACCTYPE_B);

// 	$sql = "SELECT * FROM accounts WHERE acctype ='B' AND div = '".USER_DIV."'";
// 	$accRslt = db_exec($sql);
// 	if(pg_numrows($accRslt) < 1){
// 		return "<li> ERROR : There are no accounts in the category selected.";
// 	}
// 	$accnts = "<select name='accid'>";
// 	while($acc = pg_fetch_array($accRslt)){
// 		# Check Disable
// 		if(isDisabled($acc['accid']))
// 			continue;
// 		$accnts .= "<option value='$acc[accid]'>$acc[accname]</option>";
// 	}
// 	$accnts .= "</select>";

	// Layout
	$slctAcc = "
		<h3>Set Petty Cash Account</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th colspan='2'>Select Account <input align='right' type='button' onClick=\"window.open('../core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>$accnts</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Set Account &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slctAcc;

}

# confirm
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accid, "num", 1, 70, "Invalid Account Number.");

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

	# Check if Payname has not been linked yet
	core_connect();

	$sql = "SELECT * FROM bankacc WHERE name = 'Petty Cash' AND div = '".USER_DIV."'";
	$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Petty Cash Account details from database.");
	$check = pg_numrows ($checkRslt);
	if (pg_numrows ($checkRslt) > 0){
		$cashlink = pg_fetch_array($checkRslt);

		# Get account name for thy lame User's Sake
		$accRslt = get("core", "*", "accounts", "accid", $cashlink['accnum']);
		$acc = pg_fetch_array($accRslt);
		$note = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'><font color=#ffffff>Warning: Petty Cash Account link already exists as : <b>$acc[accname]</b>.<br>Re-linking it will overwrite the existing link.</font></td>
			</tr>";
	}else{
		$note = "";
	}

	# Get account name for thy lame User's Sake
	$acccRslt = get("core", "*", "accounts", "accid", $accid);
	$accc = pg_fetch_array($acccRslt);

	// Layout
	$confirm = "
		<h3>Set Petty Cash Account</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='accid' value='$accid'>
			<tr>
				<th colspan='2'>Petty Cash Account</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>$accc[topacc]/$accc[accnum] - $accc[accname]</td>
			</tr>
			$note
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Write &raquo'></td>
			</tr>
		</table>
		</form>
		<p>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
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

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accid, "num", 1, 70, "Invalid Account Number.");

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


	# Write the link
	core_connect();

	$sql = "SELECT * FROM bankacc WHERE name = 'Petty Cash' AND div = '".USER_DIV."'";
	$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Petty Cash Account details from database.");
	if (pg_numrows ($checkRslt) > 0){
		$link = "UPDATE bankacc SET accnum='$accid' WHERE name = 'Petty Cash' AND div = '".USER_DIV."'";
	}else{
		$link = "INSERT INTO bankacc(name, accnum, div) VALUES ('Petty Cash', '$accid', '".USER_DIV."')";
	}
	$linkRslt = db_exec ($link) or errDie ("Unable to add Petty Cash Account link to Database.", SELF);


	# status report
	$write = "
		<table ".TMPL_tblDflts." width='30%'>
			<tr>
				<th>Petty Cash Account</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Petty Cash Account link has been created.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}



?>