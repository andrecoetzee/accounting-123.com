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
                        if(isset($HTTP_GET_VARS["type"]) && isset($HTTP_GET_VARS["payname"])){
                               # Display default output
                                $OUTPUT = slctCat($HTTP_GET_VARS["type"], $HTTP_GET_VARS["payname"]);
                        }else{
                                $OUTPUT = "<li>ERROR : Invalid use of module";
                        }
        }
} else {
        if(isset($HTTP_GET_VARS["type"]) && isset($HTTP_GET_VARS["payname"])){
                # Display default output
                $OUTPUT = slctCat($HTTP_GET_VARS["type"], $HTTP_GET_VARS["payname"]);
        }else{
                $OUTPUT = "<li>ERROR : Invalid use of module";
        }
}
# get template
require("template.php");

# Default View (Selected category)
function slctCat($type, $payname)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($type, "string", 1, 2, "Invalid category type.");
        $v->isOk ($payname, "string", 1, 255, "Invalid Salary payment name to be linked.");

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

$slctCat =
"<h3>Select Account Category to Link to : $payname</h3>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=slctacc>
<input type=hidden name='type' value='$type'>
<input type=hidden name='tab' value='$tab'>
<input type=hidden name='payname' value='$payname'>
<tr><th>Field</th><th>Value</th></tr>
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
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Link &raquo'></td></tr>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
</tr>
</table>

</form>
</table>
";
	return $slctCat;
}

# Select Account
function slctAcc($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($type, "string", 1, 3, "Invalid category type.");
        $v->isOk ($tab, "string", 1, 14, "Invalid category type.");
        $v->isOk ($catid, "string", 1, 50, "Invalid category Id/name.");
        $v->isOk ($payname, "string", 1, 255, "Invalid Salary payment name to be linked.");

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

 $slctAcc =
"<h3>Select Account to Link to : $payname</h3>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<input type=hidden name='type' value='$type'>
<input type=hidden name='tab' value='$tab'>
<input type=hidden name='catid' value='$catid'>
<input type=hidden name='payname' value='$payname'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Type</td><td>$tab</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Name</td><td><select name=accnum>";
        $type = strtoupper($type);
        core_connect();
        $sql = "SELECT * FROM accounts WHERE catid='$catid' AND acctype ='$type'";
        $accRslt = db_exec($sql);
        $numrows = pg_numrows($accRslt);
        if(empty($numrows)){
                return "<li>ERROR : There are no accounts in the category selected.";
        }
        while($acc = pg_fetch_array($accRslt)){
                $slctAcc .= "<option value='$acc[accid]'>$acc[accname]</option>";
        }
$slctAcc .="</select></td></tr>
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Link &raquo'></td></tr>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
</tr>
</table>

</form>
</table>
";
	return $slctAcc;
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
        $v->isOk ($type, "string", 1, 3, "Invalid Category type.");
        $v->isOk ($tab, "string", 1, 14, "Invalid Category type.");
        $v->isOk ($accnum, "num", 1, 70, "Invalid Account Number.");
        $v->isOk ($payname, "string", 1, 255, "Invalid Salary payment name to be linked.");


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

        # Check if Payname has not been linked yet
        core_connect();
        $sql = "SELECT * FROM salesacc WHERE name = '$payname'";
		$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Salaries Account Link details from database.");
        $check = pg_numrows ($checkRslt);
        $saleslink = pg_fetch_array($checkRslt);
        if (!empty($check)) {
                #Get account name for thy lame User's Sake
                $accRslt = get("core", "*", "accounts", "accid", $saleslink['accnum']);
                $acc = pg_fetch_array($accRslt);
                $note = "<font color=#ffffff>Warning: <b>$payname</b> has already been linked to account <b>$acc[accname]</b>.<br>Re-linking it will overwrite the existing link.</font>";
		}else{
			$note = "";
		}
		$type = strtoupper($type);

		# Get account name for thy lame User's Sake
		$acccRslt = get("core", "*", "accounts", "accid", $accnum);
		$accc = pg_fetch_array($acccRslt);

$confirm =
"<h3>Select Account to link to : $payname</h3>
<h4>Confirm entry</h4>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=write>
<input type=hidden name='type' value='$type'>
<input type=hidden name='tab' value='$tab'>
<input type=hidden name='accnum' value='$accnum'>
<input type=hidden name='note' value='$note'>
<input type=hidden name='payname' value='$payname'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Type</td><td>$tab</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Category ID</td><td>$catid</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Account</td><td>$accc[accname]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2>$note</td></tr>
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Link &raquo'></td></tr>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
</tr>
</table>

</form>
</table>
";
	return $confirm;
}

# write
function write($HTTP_POST_VARS)
{

//processes
core_connect();
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($type, "string", 1, 3, "Invalid Category type.");
        $v->isOk ($tab, "string", 1, 14, "Invalid Category type.");
        $v->isOk ($accnum, "num", 1, 70, "Invalid Account Number.");
        $v->isOk ($payname, "string", 1, 255, "Invalid Salary payment name to be linked.");

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

         # write the link
        if(strlen($note) < 5){
        $link = "INSERT INTO salesacc(name, accnum) VALUES('$payname', '$accnum')";
        }else{
        $link = "UPDATE salesacc SET name = '$payname', accnum='$accnum' WHERE name = '$payname'";
        }
        $linkRslt = db_exec ($link) or errDie ("Unable to add Sales Account link to Database.", SELF);

        # status report
	$write =
        "
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>Link Created</th></tr>
        <tr class=datacell><td>Link For, <b>$payname</b> was successfully added to Cubit.</td></tr>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
</tr>
</table>

";
	return $write;
}
?>
