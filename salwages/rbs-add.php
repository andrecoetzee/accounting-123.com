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
require ("../settings.php");
require ("../libs/ext.lib.php");


# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirmAllow ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = writeAllow ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enterAllow ();
	}
} else {
	$OUTPUT = enterAllow ();
}

# display output
require ("../template.php");

# enter new data
function enterAllow ()
{

	# connect to db
	core_connect ();
	$allcat= "<select name='catid'>";
		$sql = "SELECT * FROM expenditure WHERE div = '".USER_DIV."'";
		$catRslt = db_exec($sql);
		if(pg_numrows($catRslt) < 1){
				return "<li> There are no Expenditure Accounts categories yet in Cubit.";
		}else{
				while($cat = pg_fetch_array($catRslt)){
						$allcat .= "<option value='$cat[catid]'>$cat[catname]</option>";
				}
		}
	$allcat .="</select>";

	$Tp = array("No"=>"No","Yes"=>"Yes");
	$taxables = extlib_cpsel("taxable", $Tp,"Yes");

	$enterAllow =
	"<h3>Add reimbursement to system</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<tr><th colspan=2>Reimbursement Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Name</td><td align=center><input type=text size=20 name=name></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Category</td><td align=center>$allcat</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $enterAllow;
}

# confirm new data
function confirmAllow ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($name, "string", 1, 100, "Invalid reimbursement name.");
	$v->isOk ($catid, "string", 1, 20, "Invalid Category number.");

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

	# connect to db
	core_connect ();
	$allacc= "<select name='accid'>";
		$sql = "SELECT * FROM accounts WHERE catid = '$catid' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$numrows = pg_numrows($accRslt);
		if(empty($numrows)){
				return "<li> There are no accounts under selected category.
				<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		}else{
				while($acc = pg_fetch_array($accRslt)){
						$allacc .= "<option value='$acc[accid]'>$acc[accname]</option>";
				}
		}
	$allacc .="</select>";


	$confirmAllow =
	"<h3>Confirm new reimbursement</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=name value='$name'>
	<tr><th colspan=2>Allowance Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Name of reimbursement</td><td align=center>$name</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Reimbursement Account</td><td align=center>$allacc</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $confirmAllow;
}

# write new data
function writeAllow ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($name, "string", 1, 100, "Invalid reimbursement name.");
	$v->isOk ($accid, "num", 1, 20, "Invalid Account number.");

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

	# connect to db
	db_connect ();

	# write to db
	$sql = "INSERT INTO rbs (name, account, div) VALUES ('$name', '$accid', '".USER_DIV."')";
	$allowRslt = db_exec ($sql) or errDie ("Unable to add reimbursement to database.", SELF);

	$writeAllow =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Reimbursement added to system</th></tr>
	<tr class=datacell><td>New reimbursement, $name, has been successfully added to Cubit.</td></tr>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $writeAllow;
}
?>
