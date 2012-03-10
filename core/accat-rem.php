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
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;

                case "write":
                        $OUTPUT = write($HTTP_POST_VARS);
			break;

                default:
			$OUTPUT = remcat($HTTP_GET_VARS['catid'], $HTTP_GET_VARS['type']);
	}
} else {
        # Display default output
        if(!empty($HTTP_GET_VARS['catid'])){
                $OUTPUT = remcat($HTTP_GET_VARS['catid'], $HTTP_GET_VARS['type']);
        }else{
                $OUTPUT = remcat('none','none');
        }
}

# get template
require("template.php");

function remcat($catid,$type)
{
        // Limit field lengths as per database settings ( Regex method doesn't work :-/ )
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($catid, "string", 1, 20, "Invalid category ID.");
        $v->isOk ($type, "string", 1, 2, "Invalid category Type .");

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

        # Switch type
        switch($type){
                case "I":
                        $tab = "income";
                        break;
                case "E":
                        $tab = "expenditure";
                        break;
                case "B":
                        $tab = "balance";
                        break;
                default:
                        return "<li>Invalid Category type";
        }

        // Connect to database
	core_Connect ();
        $sql = "SELECT * FROM $tab WHERE catid = '$catid' AND div = '".USER_DIV."'";
        $catRslt = db_exec ($sql) or errDie ("ERROR: Unable to Retrive Payable Account Categories details from database.", SELF);
	$numrows = pg_numrows ($catRslt);

        if ($numrows > 1) {
		$OUTPUT = "There are more than one categories with the same Category ID number.";
		require ("template.php");
	}

       if ($numrows < 1) {
		$OUTPUT = "Category with number, <b>$catid</b> was not found in Cubit.";
		require ("template.php");
	}
$cat = pg_fetch_array($catRslt);

$rem =
"<h3>Delete Account Category</h3>
<h4>Confirm entry</h4>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=write>
<input type=hidden name=catid value='$cat[catid]'>
<input type=hidden name=catname value='$cat[catname]'>
<input type=hidden name=tab value='$tab'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Category ID</td><td>$cat[catid]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Category Name</td><td>$cat[catname]</td></tr>
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Delete Category&raquo'></td></tr>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
</tr>
</table>


</form>
</table>
";
	return $rem;
}

function write($HTTP_POST_VARS)
{

//processes
core_connect();
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
        require_lib("validate");
	$v = new  validate ();
        $v->isOk ($catid, "string", 1, 20, "Invalid Category ID.");
        $v->isOk ($catname, "string", 1, 70, "Invalid Category name.");
        $v->isOk ($tab, "string", 1, 14, "Invalid Category name.");

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
        $sql = "DELETE FROM $tab WHERE catid='$catid' AND div = '".USER_DIV."'";
        $editRslt = db_exec ($sql) or errDie ("Unable to Delete catagory.",SELF);

	# status report
	$write =
        "
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>Account category Deleted from database</th></tr>
        <tr class=datacell><td>Accounts Category number, <b> $catname</b>, was successfully Deleted.</td></tr>
</table>
<p>
<table border=0 cellpadding='2' cellspacing='1'>
<tr><th>Quick Links</th></tr>
<tr bgcolor='#88BBFF'><td><a href='accat-view.php'>View Accounts</a></td></tr>
<script>document.write(getQuicklinkSpecial());</script>
</tr>
";
	return $write;
}
?>
