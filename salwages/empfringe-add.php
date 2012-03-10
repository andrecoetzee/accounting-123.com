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

if (isset ($HTTP_POST_VARS["key"])) {
	if ($HTTP_POST_VARS["key"] == "confirm") {
		$OUTPUT = confirmEmpFringe ($HTTP_POST_VARS);
	} elseif ($HTTP_POST_VARS["key"] == "write") {
		$OUTPUT = writeEmpFringe ($HTTP_POST_VARS);
	} else {
		errDie ("Invalid use of module.");
	}
} elseif (isset ($HTTP_GET_VARS["id"]) && isset ($HTTP_GET_VARS["empnum"])) {
	$OUTPUT = enterEmpFringe ($HTTP_GET_VARS);
} else {
	errDie ("Invalid use of module.");
}

# display output
require ("../template.php");

# enter new info
function enterEmpFringe ($HTTP_GET_VARS)
{
	# get vars
	foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid fringe benefit ID.");
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

	# select employee info
	$sql = "SELECT empnum, sname, fnames FROM employees WHERE empnum='$empnum'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee number: $empnum.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	# select fringe benefit info
	$sql = "SELECT fringeben FROM fringebens WHERE id='$id'";
	$fringeRslt = db_exec ($sql) or errDie ("Unable to select fringe benefit from database.");
	if (pg_numrows ($fringeRslt) < 1) {
		return "Invalid fringe benefit ID: $id.";
	}
	$myFringe = pg_fetch_array ($fringeRslt);

	$enterEmpFringe =
"
<h3>Add fringe benefit to employee</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<input type=hidden name=empnum value='$empnum'>
<input type=hidden name=id value='$id'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Employee</td><td align=center>$myEmp[sname], $myEmp[fnames] ($myEmp[empnum])</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Fringe benefit</td><td align=center>$myFringe[fringeben]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td align=center>".CUR."<br><input type=text size=10 name=amount></td></tr>
<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
</form>
</table>
";
	return $enterEmpFringe;
}

# confirm new data
function confirmEmpFringe ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($id, "num", 1, 20, "Invalid fringe benefit ID.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");

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

	# select employee info
	$sql = "SELECT empnum, sname, fnames FROM employees WHERE empnum='$empnum'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee number: $empnum.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	# select fringe benefit info
	$sql = "SELECT fringeben FROM fringebens WHERE id='$id'";
	$fringeRslt = db_exec ($sql) or errDie ("Unable to select fringe benefit from database.");
	if (pg_numrows ($fringeRslt) < 1) {
		return "Invalid fringe benefit ID: $id.";
	}
	$myFringe = pg_fetch_array ($fringeRslt);

	$confirmEmpFringe =
"
<h3>Confirm fringe benefit</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=write>
<input type=hidden name=empnum value='$empnum'>
<input type=hidden name=id value='$id'>
<input type=hidden name=amount value='$amount'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Employee</td><td align=center>$myEmp[sname], $myEmp[fnames] ($myEmp[empnum])</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Fringe benefit</td><td align=center>$myFringe[fringeben]</td></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td align=center>".CUR." $amount</td></tr>
<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
</form>
</table>
";
	return $confirmEmpFringe;
}

# write new data
function writeEmpFringe ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($id, "num", 1, 20, "Invalid fringe benefit ID.");
	$v->isOk ($amount, "float", 1, 10, "Invalid amount.");

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

	# select employee info
	$sql = "SELECT empnum, sname, fnames FROM employees WHERE empnum='$empnum'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee number: $empnum.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	# select fringe benefit info
	$sql = "SELECT fringeben FROM fringebens WHERE id='$id'";
	$fringeRslt = db_exec ($sql) or errDie ("Unable to select fringe benefit from database.");
	if (pg_numrows ($fringeRslt) < 1) {
		return "Invalid fringe benefit ID: $id.";
	}
	$myFringe = pg_fetch_array ($fringeRslt);

	# write entry
	$sql = "INSERT INTO empfringe (fringebenid, empnum, amount) VALUES ('$id', '$empnum', '$amount')";
	$empFringeRslt = db_exec ($sql) or errDie ("Unable to add fringe benefit to employee.");
	if (pg_cmdtuples ($empFringeRslt) < 1) {
		return "Unable to add fringe benefit to employee.";
	}

	$writeEmpFringe =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Fringe benefit added to employee</th></tr>
<tr class=datacell><td>Fringe benefit added to employee.
<a href='salaries-staff.php?empnum=$empnum'>Click here to continue processing</a>.</td></tr>
</table>
";
	return $writeEmpFringe;
}

?>
