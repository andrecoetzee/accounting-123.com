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

# decide what to do
if (isset ($_GET["empnum"])) {
	//$OUTPUT = confirmLoan ($_GET["empnum"]);
} elseif (isset ($_POST["key"])) {
	//$OUTPUT = ($_POST["key"] == "rem") ? remLoan ($_POST["empnum"]) : "Invalid use of module.";
} else {
	//$OUTPUT = "Invalid use of module.";
}

$OUTPUT = "<li class=err>Module no longer used.</li>";

# display output
require ("../template.php");

# confirm deletion
function confirmLoan ($empnum)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");

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

	# get loan info
	db_connect ();
	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$loanRslt = db_exec ($sql) or errDie ("Unable to select loan info from database.");
	if (pg_numrows ($loanRslt) < 1) {
		return "Invalid employee number.";
	}
	$myLoan = pg_fetch_array ($loanRslt);

	$confirmLoan =
"
<h3>Confirm removal of loan</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=rem>
<input type=hidden name=empnum value='$empnum'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Loan amount</td><td align=center>".CUR." $myLoan[loanamt]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Monthly installment</td><td align=center>".CUR." $myLoan[loaninstall]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Loan interest</td><td align=center>$myLoan[loanint] %</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Loan period (months)</td><td align=center>$myLoan[loanperiod]</td></tr>
<tr><td colspan=2 align=right><input type=submit value='Delete &raquo;'><input type=button value='Back' onclick='javascript:history.back();'</td><td valign=left></td></tr>
</form>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $confirmLoan;
}

# delete loan details from employee
function remLoan ($empnum)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");

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

	# update gotloan field with NULL value
	$sql = "UPDATE employees SET gotloan='f'::bool WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$loanRslt = db_exec ($sql) or errDie ("Unable to remove loan details from database.");
	if (pg_cmdtuples ($loanRslt) < 1) {
		return "Unable to remove loan details from database.";
	}

	$sql = "SELECT loanid FROM employees WHERE empnum='$empnum'";
	$rslt = db_exec($sql) or errDie("Error fetching loan pre-archive id.");

	if ( pg_num_rows($rslt) > 0 ) {
		$loanid = pg_fetch_result($rslt, 0, 0);
	} else {
		$loanid = 0;
	}

	$sql = "DELETE FROM emp_loanarchive WHERE id='$loanid'";
	$rslt = db_exec($sql) or errDie("Error deleting loan from pre-archive.");

	$remLoan =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Loan details removed</th></tr>
<tr class=datacell><td>Loan details have been successfully removed.</td></tr>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $remLoan;
}

?>
