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

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "slctCat":
			$OUTPUT = slctCat($HTTP_POST_VARS);
			break;
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "subacc":
			$OUTPUT = subacc($HTTP_POST_VARS);
			break;
		case "confirmsub":
			$OUTPUT = confirmsub($HTTP_POST_VARS);
			break;
		case "writesub":
			$OUTPUT = writesub($HTTP_POST_VARS);
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
require("template.php");




# Default View
function view(){

	if(!(USER_NAME == 'Root' OR USER_NAME == "admin")){
		# check permission
		$chk = "SELECT * FROM userscripts WHERE username = '".USER_NAME."' AND script = 'acc-new2.php'";
		$chkRslt = db_exec($chk) or errDie("Unable to check user access permissions",SELF);
		if(pg_numrows($chkRslt) < 1){
			$OUTPUT = "<li class='err'>You <b>don't have sufficient permissions</b> to use this command.$HTTP_SESSION_VARS[USER_NAME] => ".getenv ("SCRIPT_NAME");
			require("template.php");
		}
	}

	$view = "
		<h3>Add New Account</h3>
		<table ".TMPL_tblDflts." width='350'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='slctCat'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Account type</td>
				<td valign='center'>
					<select name='type'>
						<option value='I'>Income</option>
						<option value='B'>Balance</option>
						<option value='E'>Expenditure</option>
					</select>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Account Level</td>
				<td valign=center>
					<select name='level'>
						<option value='top'>Main Account</option>
						<option value='sub'>Sub Account</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><input type='button' value='< Cancel' onClick='javascript:history.back();'></td>
				<td valign='center'><input type='submit' value='Add Account >'></td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='acc-view.php'>View Accounts</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $view;

}




# confirm
function slctCat($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($type, "string", 1, 2, "Invalid account type.");
	$v->isOk ($level, "string", 1, 3, "Invalid account level.");

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

	# Change to sub accounts selection
	# if the account is a sub.
	if($level == 'sub'){
		return slctSubCat($type);
	}


	# Check Category name on selected type
	core_connect();
	switch($type){
        case "I":
			$tab = "Income";
			break;
        case "E":
			$tab = "Expenditure";
			break;
        case "B":
			$tab = "Balance";
			break;
        default:
			return "<li>Invalid Category type";
	}

	$slctCat = "
		<h3>Add New Account</h3>
		<h4>Select Category</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='type' value='$type'>
			<input type='hidden' name='tab' value='$tab'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Type</td>
				<td>$tab</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category Name</td>
				<td>
					<select name='catid'>";

	core_connect();

	$sql = "SELECT * FROM $tab WHERE div = '".USER_DIV."' ORDER BY catid";
	$catRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);
	$rows = pg_numrows($catRslt);

	if($rows < 1){
		return "There are no Account Categories under $tab";
	}

	while($cat = pg_fetch_array($catRslt)){
		$slctCat .= "<option value='$cat[catid]'>$cat[catname]</option>";
	}

	$slctCat .= "
					</select>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Name</td>
				<td valign='center'><input type='text' name='accname' maxlength='40'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Add Account &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='acc-view.php'>View Accounts</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slctCat;

}




# confirm
function slctSubCat($type)
{

	# Check Category name on selected type
	core_connect();
	switch($type){
		case "I":
			$tab = "Income";
			break;
		case "E":
			$tab = "Expenditure";
			break;
		case "B":
			$tab = "Balance";
			break;
		default:
			return "<li>Invalid Category type.</li>";
	}

	$slctCat = "
	<h3>Add New Sub Account</h3>
	<h4>Select Category</h4>
	<table ".TMPL_tblDflts.">
	<form action='".SELF."' method='POST'>
		<input type='hidden' name='key' value='subacc'>
		<input type='hidden' name='type' value='$type'>
		<input type='hidden' name='tab' value='$tab'>
		<tr>
			<th>Field</th>
			<th>Value</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Account Type</td>
			<td>$tab</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Category Name</td>
			<td>
				<select name='catid'>";

	core_connect();

	$sql = "SELECT * FROM $tab WHERE div = '".USER_DIV."' ORDER BY catid";
	$catRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);
	$rows = pg_numrows($catRslt);

	if($rows < 1){
		return "There are no Account Categories under $tab";
	}

	while($cat = pg_fetch_array($catRslt)){
		$slctCat .= "<option value='$cat[catid]'>$cat[catname]</option>";
	}

	$slctCat .= "
					</select>
				</td>
			</tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Add Account &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='acc-view.php'>View Accounts</td>
			/tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $slctCat;

}




# confirm
function subacc($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
    $v->isOk ($type, "string", 1, 3, "Invalid category type.");
    $v->isOk ($tab, "string", 1, 14, "Invalid category type.");
    $v->isOk ($catid, "string", 1, 50, "Invalid category ID.");

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



    # list of top accounts available
    core_connect();

    $sql = "SELECT * FROM accounts WHERE accnum = '000' AND catid = '$catid' AND div = '".USER_DIV."'";
    $Rslt = db_exec($sql) or errDie("Unable to retrieve top account from Cubit.", SELF);
    if(pg_numrows($Rslt) < 1){
            return "<li> There are not main accounts in Cubit.</li>";
    }
    $acc = "<select name='topacc'>";
    while($accnt = pg_fetch_array($Rslt)){
            $acc .= "<option value='$accnt[topacc]'>$accnt[accname]</option>";
    }
    $acc .= "</select>";

	// Layout
	$subacc = "
		<h3>Add New Account</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirmsub'>
			<input type='hidden' name='type' value='$type'>
			<input type='hidden' name='catid' value='$catid'>
			<input type='hidden' name='tab' value='$tab'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Type</td>
				<td>$tab</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category ID</td>
				<td>$catid</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Main Account</td>
				<td>$acc</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Sub Account Name</td>
				<td><Input type='text' name='accname' size='18'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Add Account &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='acc-view.php'>View Accounts</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $subacc;

}




# confirm
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($type, "string", 1, 3, "Invalid category type.");
	$v->isOk ($tab, "string", 1, 14, "Invalid category type.");
	$v->isOk ($accname, "string", 1, 50, "Invalid account name.");
	$v->isOk ($catid, "string", 1, 50, "Invalid category ID.");

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

    # Check Account name on selected type and category
    core_connect();
    $sql = "SELECT * FROM accounts WHERE accname = '$accname' AND div = '".USER_DIV."'";
	$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
		$check = pg_numrows ($checkRslt);
		if (!empty($check)) {
		return "<center>The Account name that you enter already exits.<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'></center>";
	}

	$confirm = "
		<h3>Add New Account</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='type' value='$type'>
			<input type='hidden' name='catid' value='$catid'>
			<input type='hidden' name='tab' value='$tab'>
			<input type='hidden' name='accname' value='$accname'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Type</td>
				<td>$tab</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category ID</td>
				<td>$catid</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Name</td>
				<td>$accname</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Add Account &raquo'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='acc-view.php'>View Accounts</td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $confirm;

}




# confirm
function confirmsub($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($type, "string", 1, 3, "Invalid category type.");
	$v->isOk ($tab, "string", 1, 14, "Invalid category type.");
	$v->isOk ($topacc, "string", 1, 20, "Invalid main account number.");
	$v->isOk ($accname, "string", 1, 50, "Invalid account name.");
	$v->isOk ($catid, "string", 1, 50, "Invalid category ID.");

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

        # Check Account name on selected type and category
        core_connect();
        $sql = "SELECT * FROM accounts WHERE accname = '$accname' AND div = '".USER_DIV."'";
		$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
			$check = pg_numrows ($checkRslt);
			if (!empty($check)) {
			return "<center>The Account name that you enter already exits.<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'></center>";
		}

        # get account name for top account
        $topRslt = get("core", "accname", "accounts", "topacc", $topacc);
        if(pg_numrows($topRslt) < 1){
            return "<li> Invalid Main account number.</li>";
        }else{
            $top = pg_fetch_array($topRslt);
            $topaccname = $top['accname'];
        }

	// LAYOUT
	$confirm = "
	<h3>Add New Sub Account</h3>
	<h4>Confirm entry</h4>
	<table ".TMPL_tblDflts." width='20%'>
	<form action='".SELF."' method='POST'>
		<input type='hidden' name='key' value='writesub'>
		<input type='hidden' name='type' value='$type'>
		<input type='hidden' name='catid' value='$catid'>
		<input type='hidden' name='tab' value='$tab'>
		<input type='hidden' name='topacc' value='$topacc'>
		<input type='hidden' name='accname' value='$accname'>
		<tr>
			<th>Field</th>
			<th>Value</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Account Type</td>
			<td>$tab</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Category ID</td>
			<td>$catid</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Main Account</td>
			<td>$topaccname</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Sub Account</td>
			<td>$accname</td>
		</tr>
		<tr><td><br></td></tr>
		<tr>
			<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
			<td align='right'><input type='submit' value='Add Account &raquo'></td>
		</tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr bgcolor='#88BBFF'>
			<td><a href='acc-view.php'>View Accounts</td>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";
	return $confirm;

}




# write
function writesub($HTTP_POST_VARS)
{

	//processes
	core_connect();

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($type, "string", 1, 2, "Invalid category type.");
	$v->isOk ($tab, "string", 1, 14, "Invalid category type.");
	$v->isOk ($topacc, "num", 1, 50, "Invalid main account number.");
	$v->isOk ($accname, "string", 1, 50, "Invalid account name.");
	$v->isOk ($catid, "string", 1, 50, "Invalid category Id/name.");
	$v->isOk ($vat, "string", 1, 3, "Invalid vat selection.");

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



    # get last account name
    $sql = "SELECT accnum FROM accounts WHERE catid = '$catid' AND topacc = '$topacc' AND div = '".USER_DIV."' ORDER BY accnum DESC";
    $Rslt = db_exec($sql) or errDie("Unable to select next value for account number.", SELF);
    $acc = pg_fetch_array($Rslt,0);
    $accnum = $acc['accnum'] + 1;

    # format the top accnum
    $accnum = sprintf("%03.d", $accnum);

    # print "<b>$topacc/$accnum"; exit;

    # begin sql transaction
    pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, div) VALUES ('$topacc', '$accnum', '$accname', '$type', '$catid', '".USER_DIV."')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, div) VALUES('$accid', '$topacc', '$accnum', '$accname', '".USER_DIV."')";
		$trialRslt = db_exec($query);

	# commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);

	# status report
	$write = "
	<table ".TMPL_tblDflts." width='50%'>
		<tr>
			<th>New Account</th>
		</tr>
		<tr class='datacell'>
			<td>New Account, <b>$accname</b> was successfully added to Cubit.</td>
		</tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr bgcolor='#88BBFF'>
			<td><a href='acc-view.php'>View Accounts</td>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";
	return $write;

}




# write
function write($HTTP_POST_VARS)
{

	//processes
	core_connect();

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($type, "string", 1, 2, "Invalid category type.");
	$v->isOk ($tab, "string", 1, 14, "Invalid category type.");
	$v->isOk ($accname, "string", 1, 50, "Invalid account name.");
	$v->isOk ($catid, "string", 1, 50, "Invalid category Id/name.");

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

	# begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		$sql = "SELECT nextval('accounts_topacc_seq')";
		$Rslt = db_exec($sql) or errDie("Unable to select next value for account number.", SELF);
		$topacc = pg_fetch_array($Rslt);
		$topacc = $topacc['nextval'];

		if($topacc < 100){
			# format the top accnum to 3 digits if < 100 coz > 101  is ok
			$topacc = sprintf("%03.d",$topacc);
		}

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accname, acctype, catid, div) VALUES ('$topacc', '$accname', '$type', '$catid', '".USER_DIV."')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accname, div) VALUES('$accid', '$topacc', '$accname', '".USER_DIV."')";
		$trialRslt = db_exec($query);

	# commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to start a database transaction.",SELF);

	# status report
	$write = "
	<table ".TMPL_tblDflts." width='50%'>
		<tr>
			<th>New Account</th>
		</tr>
		<tr class='datacell'>
			<td>New Account, <b>$accname</b> was successfully added to Cubit.</td>
		</tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr bgcolor='#88BBFF'>
			<td><a href='acc-view.php'>View Accounts</td>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";
	return $write;

}



?>