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
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;

                case "write":
                        $OUTPUT = write($_POST);
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
$view = "
<h3>Add New Category</h3>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
<form action='".SELF."' method=post name=form>
<input type=hidden name=key value=confirm>
<tr><th>Field</th><th>Value</th></tr>
<tr class='bg-odd'><td>".REQ."Select Category type</td><td valign=center>
<select name='type'>
<option value='I'>Income</option>
<option value='B'>Balance</option>
<option value='E'>Expenditure</option>
</select></td></tr>
<tr class='bg-even'><td>".REQ."Category Name</td><td valign=center><input type=text name=catname maxlength=40></td></tr>
<tr><td><input type=button value='&laquo; Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Add Category &raquo;'></td></tr>
<p>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
</tr>
</table>

";
return $view;
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
        $v->isOk ($type, "string", 1, 2, "Invalid category type.");
        $v->isOk ($catname, "string", 1, 50, "Invalid category name.");


        # display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
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
        $sql = "SELECT * FROM $tab WHERE catname = '$catname' AND div = '".USER_DIV."'";
	$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve payable account Category details from database.");
        $check = pg_numrows ($checkRslt);
        if (!empty($check)) {
		return "<center>The Account Category name that you enter already exits in $tab accounts Categories.<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'></center>";
	}

$confirm =
"<h3>Add New Accounts Category</h3>
<h4>Confirm entry</h4>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=write>
<input type=hidden name='type' value='$type'>
<input type=hidden name=catname value='$catname'>
<input type=hidden name=tab value='$tab'>
<tr><th>Field</th><th>Value</th></tr>
<tr class='bg-odd'><td>".REQ."Category Type</td><td>$tab</td></tr>
<tr class='bg-even'><td>".REQ."Category Name</td><td>$catname</td></tr>
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Add Category &raquo'></td></tr>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
</tr>
</table>


</form>
</table>
";
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
        $v->isOk ($catname, "string", 1, 50, "Invalid category name.");
	$v->isOk ($tab, "string", 1, 14, "Invalid Category type.");

        # display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

        # Set the sequence
        $seq = $tab."_seq";

        # Write to db
        $sql = "INSERT INTO $tab (catid, catname, div) VALUES ('$type' || nextval('$seq'),'$catname', '".USER_DIV."')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add Category to Database.");

		# status report
		$write =
        "
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>New Accounts Category</th></tr>
        <tr class=datacell><td>New Accounts Category, <b>$catname</b> was successfully added to Cubit.</td></tr>
</table>
<p>
<table border=0 cellpadding='2' cellspacing='1'>
<tr><th>Quick Links</th></tr>
<tr bgcolor='#88BBFF'><td><a href='accat-new.php'>Add Category</a></td></tr>
<script>document.write(getQuicklinkSpecial());</script>
</tr>
";
	return $write;
}
?>
