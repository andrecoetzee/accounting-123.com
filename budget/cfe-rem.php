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
require ("../settings.php");

# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = con_data ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = view_data ($HTTP_GET_VARS);
	}
} else {
	$OUTPUT = view_data ($HTTP_GET_VARS);
}
# check department-level access

# display output
require ("../template.php");
# enter new data
function view_data ($HTTP_GET_VARS)
{
  foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($id,"num", 1,100, "Invalid num.");

        # display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	db_conn('cubit');
	$user =USER_NAME;
	$Sql = "SELECT * FROM cf WHERE (id='$id' AND div = '".USER_DIV."')";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	if(pg_numrows($Rslt)<1){return "entry not Found";}
	$Data = pg_fetch_array($Rslt);

	foreach ($Data as $key => $value) {
		$$key = $value;
	}

	$view_data =
	"<h3>Confirm cash flow budget entry</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=id value='$id'>
	<table cellpadding=0 cellspacing=0>
	<tr valign=top><td>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th colspan=2>Asset Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td>$description</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Date Bought</td><td>$date</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Amount</td><td>$amount</td></tr>
	</table>
	</td></tr>
	<tr><td valign=bottom><input type=submit value='Remove &raquo;'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='cfe-view.php'>View Assets</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

        return $view_data;
}

# confirm new data
function con_data ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

        $v->isOk ($id,"num",0 ,100, "Invalid number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

        db_conn('cubit');
        $Sql = "DELETE FROM cf WHERE id='$id' AND div = '".USER_DIV."'";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Cash flow budget entry Removed</th></tr>
	<tr class=datacell><td>Entry has been deleted from the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='cfe-add.php'>New cash flow budget entry</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='cfe-view.php'>View cash flow budget entries</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>
