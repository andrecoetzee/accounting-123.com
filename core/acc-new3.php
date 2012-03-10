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
			case "slctcat":
				$OUTPUT = slctCat($HTTP_POST_VARS);
				break;

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
require("template.php");

# Select cat
function view()
{
	$view =
	"<h3>Add New Account</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=slctcat>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Number</td><td valign=center><input type=text name=topacc size=3 maxlength=3> / <input type=text name=accnum size=3 maxlength=3></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Name</td><td valign=center><input type=text name=accname maxlength=40></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Vat Deductable</td><td><input type=radio name=vat value=yes>yes | <input type=radio name=vat value=no checked=yes>no</td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Account &raquo'></td></tr>
	</form>
	</table>";

	return $view;
}

function viewerr($HTTP_POST_VARS, $err=""){

	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	$viewerr =
	"<h3>Add New Account</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=slctcat>
	$err
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Number</td><td valign=center><input type=text name=topacc size=3 maxlength=3 value='$topacc'> / <input type=text name=accnum size=3 maxlength=3 value='$accnum'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Name</td><td valign=center><input type=text name=accname maxlength=40 value='$accname'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Vat Deductable</td><td><input type=radio name=vat value=yes>yes | <input type=radio name=vat value=no checked=yes>no</td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Account &raquo'></td></tr>
	</form>
	</table>";

	return $viewerr;
}

// select cat
function slctCat($HTTP_POST_VARS)
{
		# get vars
		foreach ($HTTP_POST_VARS as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($topacc, "num", 1, 3, "Invalid account number prefix.");
		$v->isOk ($accnum, "string", 1, 3, "Invalid account number suffix.");
		$v->isOk ($accname, "string", 1, 50, "Invalid account name.");
		$v->isOk ($vat, "string", 1, 3, "Invalid vat selection.");

        # display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>".$e["msg"];
			}
			$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}

		// Check type of account
		if($topacc >= MIN_INC && $topacc <= MAX_INC){
			$tab = "Income";
		}elseif($topacc >= MIN_EXP && $topacc <= MAX_EXP){
			$tab = "Expenditure";
		}elseif($topacc >= MIN_BAL && $topacc <= MAX_BAL){
			$tab = "Balance";
		}

		# upper case account number
		$accnum = strtoupper($accnum);

		# Check Account name on selected type and category
		core_connect();
		$sql = "SELECT * FROM accounts WHERE accname = '$accname'";
		$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
		if (pg_numrows($checkRslt) > 0) {
			$err = "<tr><td colspan=2 class=err>Account name already exist.</td></tr>";
			# return error function
			return viewerr($HTTP_POST_VARS, $err);
			exit;
		}

		# Check Account name on selected type and category
		core_connect();
		$sql = "SELECT * FROM accounts WHERE topacc = '$topacc' AND accnum = '$accnum'";
		$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
		if (pg_numrows($cRslt) > 0) {
			$err = "<tr><td colspan=2 class=err>The Account number is already in use.</td></tr>";
			# return error function
			return viewerr($HTTP_POST_VARS, $err);
			exit;
		}

		// layout
		$slctCat =
		"<h3>Add New Account</h3>
		<h4>Select Category</h4>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post>
		<input type=hidden name=key value=confirm>
		<input type=hidden name=tab value='$tab'>
		<input type=hidden name=topacc value='$topacc'>
		<input type=hidden name=accnum value='$accnum'>
		<input type=hidden name=accname value='$accname'>
		<input type=hidden name=vat value='$vat'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Number</td><td valign=center>$topacc/$accnum</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Name</td><td valign=center>$accname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Type</td><td>$tab</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Category Name</td><td><select name=catid>";
		core_connect();
		$sql = "SELECT * FROM $tab ORDER BY catid";
		$catRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);
		$rows = pg_numrows($catRslt);

		if($rows < 1){
				return "There are no Account Categories under $tab";
		}

		while($cat = pg_fetch_array($catRslt)){
				$slctCat .= "<option value='$cat[catid]'>$cat[catname]</option>";
		}

		$slctCat .="</select></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Vat Deductable</td><td>$vat</td></tr>
		<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Account &raquo'></td></tr>
		</form>
		</table>";

		return $slctCat;
}

# confirm
function confirm($HTTP_POST_VARS)
{
		# get vars
		foreach ($HTTP_POST_VARS as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($tab, "string", 1, 14, "Invalid category type.");
		$v->isOk ($accname, "string", 1, 50, "Invalid account name.");
		$v->isOk ($catid, "string", 1, 50, "Invalid category ID.");
		$v->isOk ($vat, "string", 1, 3, "Invalid vat selection.");
		$v->isOk ($topacc, "num", 1, 3, "Invalid account number prefix.");
		$v->isOk ($accnum, "string", 1, 3, "Invalid account number suffix.");

		# display errors, if any
		if ($v->isError ()) {
			$confirm = "<div class=err>";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li>".$e["msg"];
			}
			$confirm .= "</div>";

			# Return error function
			return $confirm;
		}

		$confirm =
		"<h3>Add New Account</h3>
		<h4>Confirm entry</h4>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post>
		<input type=hidden name=key value=write>
		<input type=hidden name=catid value='$catid'>
		<input type=hidden name=tab value='$tab'>
		<input type=hidden name=accname value='$accname'>
		<input type=hidden name=vat value='$vat'>
		<input type=hidden name=topacc value='$topacc'>
		<input type=hidden name=accnum value='$accnum'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Number</td><td>$topacc/$accnum</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Name</td><td>$accname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Type</td><td>$tab</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Vat Deductable</td><td>$vat</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Category ID</td><td>$catid</td></tr>
		<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Account &raquo'></td></tr>
		</form>
		</table>";

		return $confirm;
}

# write
function write($HTTP_POST_VARS)
{
	# Processes
	core_connect();
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($tab, "string", 1, 14, "Invalid category type.");
	$v->isOk ($accname, "string", 1, 50, "Invalid account name.");
	$v->isOk ($catid, "string", 1, 50, "Invalid category Id/name.");
	$v->isOk ($vat, "string", 1, 3, "Invalid vat selection.");
	$v->isOk ($topacc, "num", 1, 3, "Invalid account number.");
	$v->isOk ($accnum, "string", 1, 3, "Invalid account number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Vat
	if($vat == 'yes'){
			$vat = "t";
	}else{
			$vat = "f";
	}
	$type = substr($tab, 0, 1);

		# upper case account number
		$accnum = strtoupper($accnum);


		# Check Account name on selected type and category
		core_connect();
		$sql = "SELECT * FROM accounts WHERE accname = '$accname'";
		$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
		if (pg_numrows($checkRslt) > 0) {
			$err = "<tr><td colspan=2 class=err>Account name already exist.</td></tr>";
			# return error function
			return viewerr($HTTP_POST_VARS, $err);
			exit;
		}

		# Check Account name on selected type and category
		core_connect();
		$sql = "SELECT * FROM accounts WHERE topacc = '$topacc' AND accnum = '$accnum'";
		$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
		if (pg_numrows($cRslt) > 0) {
			$err = "<tr><td colspan=2 class=err>The Account number is already in use.</td></tr>";
			# return error function
			return viewerr($HTTP_POST_VARS, $err);
			exit;
		}

		# Begin sql transaction
		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

			# write to DB
			$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat) VALUES ('$topacc', '$accnum', '$accname','$type', '$catid', '$vat')";
			$catRslt = db_exec ($sql) or errDie ("Unable to add Account to Database.", SELF);

			# get last inserted id for new acc
			$accid = pglib_lastid ("accounts", "accid");

			# insert account into trial Balance
			$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat) VALUES('$accid', '$topacc', '$accnum', '$accname', '$vat')";
			$trialRslt = db_exec($query);

		# Commit sql transaction
		pglib_transaction ("COMMIT") or errDie("Unable to start a database transaction.",SELF);

		# status report
		$write =
		"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
			<tr><th>New Account</th></tr>
			<tr class=datacell><td>New Account, <b>($topacc/$accnum) - $accname</b> was successfully added to Cubit.</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='#88BBFF'><td><a href='acc-new3.php'>Add New Account</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";

		return $write;
}
?>
