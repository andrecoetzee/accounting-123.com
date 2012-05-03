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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
                case "slctacc":
			$OUTPUT = slctAcc($_POST);
			break;

                case "confirm":
			$OUTPUT = confirm($_POST);
			break;

                case "write":
                        $OUTPUT = write($_POST);
			break;

                default:
                        if(isset($_GET["type"]) && isset($_GET["payname"])){
                               # Display default output
                                $OUTPUT = slctCat($_GET["type"], $_GET["payname"]);
                        }else{
                                $OUTPUT = "<li>ERROR : Invalid use of module";
                        }
        }
} else {
        if(isset($_GET["type"]) && isset($_GET["payname"])){
                # Display default output
                $OUTPUT = slctCat($_GET["type"], $_GET["payname"]);
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
        $v->isOk ($type, "string", 1, 3, "Invalid category type.");
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
	<tr class='bg-odd'><td>Account Type</td><td>$tab</td></tr>
	<tr class='bg-even'><td>Category Name</td><td><select name=catid>";
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

	$slctCat .="</select></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Link &raquo'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='#88BBFF'><td><a href='../salwages/sal-settings.php'>Settings</a></td></tr>
	<tr bgcolor='#88BBFF'><td><a href='../salwages/employee-resources.php'>Employee Resources</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $slctCat;
}

# Select Account
function slctAcc($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
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

	db_conn("core");
	$sql = "SELECT accnum FROM salacc WHERE name='$payname' AND div='".USER_DIV."'
			UNION
			SELECT '0' AS accnum ORDER BY accnum DESC";
	$rslt = db_exec($sql) or errDie("Error reading current account link.");

	$curacc_link = pg_fetch_result($rslt, 0, 0);

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
	<tr class='bg-odd'><td>Account Type</td><td>$tab</td></tr>
	<tr class='bg-even'><td>Account Name</td><td><select name=accnum>";
        $type = strtoupper($type);
        core_connect();
        $sql = "SELECT * FROM accounts WHERE catid='$catid' AND acctype ='$type' AND div = '".USER_DIV."'";
        $accRslt = db_exec($sql);
        $numrows = pg_numrows($accRslt);
        if(empty($numrows)){
                return "<li>ERROR : There are no accounts in the category selected.";
        }
        while ( $acc = pg_fetch_array($accRslt) ){
        	if ( $acc["accid"] == $curacc_link ) {
        		$sel = "selected";
        	} else {
        		$sel = "";
        	}

			$slctAcc .= "<option $sel value='$acc[accid]'>$acc[accname]</option>";
        }
	$slctAcc .="</select></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Link &raquo'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='#88BBFF'><td><a href='../salwages/sal-settings.php'>Settings</a></td></tr>
	<tr bgcolor='#88BBFF'><td><a href='../salwages/employee-resources.php'>Employee Resources</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $slctAcc;
}

# confirm
function confirm($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
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
        $sql = "SELECT * FROM salacc WHERE name = '$payname' AND div = '".USER_DIV."'";
		$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Salaries Account Link details from database.");
        $check = pg_numrows ($checkRslt);
        $sallink = pg_fetch_array($checkRslt);
        if (!empty($check)) {
			#Get account name for they lame User's Sake
	        $accRslt = get("core", "accname", "accounts", "accid", $sallink['accnum']);
    	    $acc = pg_fetch_array($accRslt);
			$note = "<font color=#ffffff>Warning: The <b>$payname</b> has already been linked to account <b>$acc[accname]</b>.<br> Re-linking it will overwrite the previous link.<font>";
		}else{
        	$note = "";
        }

        #Get account name for they lame User's Sake
        $accRslt = get("core", "accname", "accounts", "accid", $accnum);
        $acc = pg_fetch_array($accRslt);

$type = strtoupper($type);
$confirm =
"<h3>Select Account to link to</h3>
<h4>Confirm entry</h4>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name='key' value=write>
<input type=hidden name='type' value='$type'>
<input type=hidden name='tab' value='$tab'>
<input type=hidden name='accnum' value='$accnum'>
<input type=hidden name='note' value='$note'>
<input type=hidden name=payname value='$payname'>
<tr><th>Field</th><th>Value</th></tr>
<tr class='bg-odd'><td>Account Type</td><td>$tab</td></tr>
<tr class='bg-even'><td>Category ID</td><td>$catid</td></tr>
<tr class='bg-odd'><td>Account Number</td><td>$acc[accname]</td></tr>
<tr class='bg-even'><td colspan=2>$note</td></tr>
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Link &raquo'></td></tr>
</form>
</table>
<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='#88BBFF'><td><a href='../salwages/sal-settings.php'>Settings</a></td></tr>
	<tr bgcolor='#88BBFF'><td><a href='../salwages/employee-resources.php'>Employee Resources</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $confirm;
}

# write
function write($_POST)
{

//processes
core_connect();
	# get vars
	foreach ($_POST as $key => $value) {
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
        $link = "INSERT INTO salacc(name, accnum, div) VALUES('$payname', '$accnum', '".USER_DIV."')";
        }else{
        $link = "UPDATE salacc SET name = '$payname', accnum='$accnum' WHERE name = '$payname' AND div = '".USER_DIV."'";
        }
        $linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

        # status report
		$write =
        "<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>Link Created</th></tr>
        <tr class=datacell><td>Link For, <b>$payname</b> was successfully added to Cubit.</td></tr>
		<table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='../salwages/sal-settings.php'>Settings</a></td></tr>
		<tr bgcolor='#88BBFF'><td><a href='../salwages/employee-resources.php'>Employee Resources</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		</table>";

	return $write;
}
?>
