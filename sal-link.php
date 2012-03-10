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
##
# sal-link.php :: Add link for a salaries payment
##

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
			$OUTPUT = slctCat($type, $payname);
	}
} else {
        # Display default output
        $OUTPUT = slctCat($type, $payname);
}

# get templete
require("template.php");

# Default View (Selected catagory)
function slctCat($type, $payname)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($type, "string", 1, 3, "Invalid catagory type.");
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
                case "inc":
                        $tab = "income";
                        break;
                case "exp":
                        $tab = "expenditure";
                        break;
                case "bal":
                        $tab = "balance";
                        break;
                default:
                        return "<li>Invalid Category type";
        }

$slctCat =
"<h3>Select Account Category to Link to : $payname</h3>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=slctacc>
<input type=hidden name=type value='$type'>
<input type=hidden name=tab value='$tab'>
<input type=hidden name=payname value='$payname'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Type</td><td>$tab</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Category Name</td><td><select name=catid>";
core_connect();
$sql = "SELECT * FROM $tab ORDER BY catid";
$catRslt = db_exec($sql) or errDie("Could not retrieve Categories Infomation from the Database.",SELF);
$rows = pg_numrows($catRslt);

if($rows < 1){
        return "There are no Account Categories under $tab";
}

while($cat = pg_fetch_array($catRslt)){
        $slctCat .= "<option value='$cat[catid]'>$cat[catname]</option>";
}

$slctCat .="</select></td></tr>
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Link &raquo'></td></tr>
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
        $v->isOk ($type, "string", 1, 3, "Invalid catagory type.");
        $v->isOk ($tab, "string", 1, 14, "Invalid catagory type.");
        $v->isOk ($catid, "string", 1, 50, "Invalid catagory Id/name.");
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
<input type=hidden name=type value='$type'>
<input type=hidden name=tab value='$tab'>
<input type=hidden name=catid value='$catid'>
<input type=hidden name=payname value='$payname'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Type</td><td>$tab</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Name</td><td><select name=accnum>";
        $type = strtoupper($type);
        core_connect();
        $sql = "SELECT * FROM accounts WHERE catid='$catid' AND acctype ='$type'";
        $accRslt = db_exec($sql);
        $numrows = pg_numrows($accRslt);
        if(empty($numrows)){
                return "ERROR : There are no account in the catgory selected for debit";
        }
        while($acc = pg_fetch_array($accRslt)){
                $slctAcc .= "<option value='$acc[accnum]'>$acc[accname]</option>";
        }
$slctAcc .="</select></td></tr>
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Link &raquo'></td></tr>
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
        $v->isOk ($accnum, "string", 1, 70, "Invalid Account Number.");
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
        $sql = "SELECT * FROM salacc WHERE name = '$payname'";
	$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Salaries Account Link details from database.");
        $check = pg_numrows ($checkRslt);
        $sallink = pg_fetch_array($checkRslt);
        if (!empty($check)) {
		$note = "<font color=#ffffff>Warning: The <b>$payname</b> has already been linked to account number <b>$sallink[accnum]</b>.<br> Re-linking it will overwrite the previos link.<font>";
	}else{
        $note = "";
        }

$type = strtoupper($type);
$confirm =
"<h3>Select Account to link to</h3>
<h4>Confirm entry</h4>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=write>
<input type=hidden name=type value='$type'>
<input type=hidden name=tab value='$tab'>
<input type=hidden name=accnum value='$accnum'>
<input type=hidden name=note value='$note'>
<input type=hidden name=payname value='$payname'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Type</td><td>$tab</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Category ID</td><td>$type$catid</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Number</td><td>$accnum</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2>$note</td></tr>
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Link &raquo'></td></tr>
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
        $v->isOk ($accnum, "string", 1, 70, "Invalid Account Number.");
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
        $link = "INSERT INTO salacc(name, accnum) VALUES('$payname', '$accnum')";
        }else{
        $link = "UPDATE salacc SET name = '$payname', accnum='$accnum' WHERE name = '$payname'";
        }
        $linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

        # status report
	$write =
        "
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>Link Created</th></tr>
        <tr class=datacell><td>Link For, <b>$payname</b> was successfully added to Cubit.</td></tr>
";
	return $write;
}
?>
