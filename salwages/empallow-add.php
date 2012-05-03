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

if (isset ($_POST["key"])) {
	if ($_POST["key"] == "confirm") {
		$OUTPUT = confirmEmpAllow ($_POST);
	} elseif ($_POST["key"] == "write") {
		$OUTPUT = writeEmpAllow ($_POST);
	} else {
		errDie ("Invalid use of module.");
	}
} elseif (isset ($_GET["id"]) && isset ($_GET["empnum"])) {
	$OUTPUT = enterEmpAllow ($_GET);
} else {
	errDie ("Invalid use of module.");
}


# display output
require ("../template.php");

# enter new info
function enterEmpAllow ($_GET)
{
	# get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid allowance ID.");
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

	# select allowance info
	$sql = "SELECT allowance FROM allowances WHERE id='$id'";
	$allowRslt = db_exec ($sql) or errDie ("Unable to select allowance from database.");
	if (pg_numrows ($allowRslt) < 1) {
		return "Invalid allowance ID: $id.";
	}
	$myAllow = pg_fetch_array ($allowRslt);

	$enterEmpAllow =
"
<h3>Add allowance to employee</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=confirm>
<input type=hidden name=empnum value='$empnum'>
<input type=hidden name=id value='$id'>
<tr><th>Field</th><th>Value</th></tr>
<tr class='bg-odd'><td>Employee</td><td align=center>$myEmp[sname], $myEmp[fnames] ($myEmp[empnum])</td></tr>
<tr class='bg-even'><td>Allowance</td><td align=center>$myAllow[allowance]</td></tr>
<tr class='bg-odd'><td>Amount</td><td align=center>".CUR."<br><input type=text size=10 name=amount></td></tr>
<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
</form>
</table>
";
	return $enterEmpAllow;
}

# confirm new data
function confirmEmpAllow ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($id, "num", 1, 20, "Invalid allowance ID.");
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

	# select allowance info
	$sql = "SELECT allowance FROM allowances WHERE id='$id'";
	$allowRslt = db_exec ($sql) or errDie ("Unable to select allowance from database.");
	if (pg_numrows ($allowRslt) < 1) {
		return "Invalid allowance ID: $id.";
	}
	$myAllow = pg_fetch_array ($allowRslt);

	$confirmEmpAllow =
"
<h3>Confirm allowance</h3>

<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=write>
<input type=hidden name=empnum value='$empnum'>
<input type=hidden name=id value='$id'>
<input type=hidden name=amount value='$amount'>
<tr><th>Field</th><th>Value</th></tr>
<tr class='bg-odd'><td>Employee</td><td align=center>$myEmp[sname], $myEmp[fnames] ($myEmp[empnum])</td></tr>
<tr class='bg-even'><td>Allowance</td><td align=center>$myAllow[allowance]</td></tr>
<tr class='bg-odd'><td>Amount</td><td align=center>".CUR." $amount</td></tr>
<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
</form>
</table>
";
	return $confirmEmpAllow;
}

# write new data
function writeEmpAllow ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($id, "num", 1, 20, "Invalid allowance ID.");
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

	# select allowance benefit info
	$sql = "SELECT allowance FROM allowances WHERE id='$id'";
	$allowRslt = db_exec ($sql) or errDie ("Unable to select allowance from database.");
	if (pg_numrows ($allowRslt) < 1) {
		return "Invalid allowance ID: $id.";
	}
	$myAllow = pg_fetch_array ($allowRslt);

	# write entry
	$sql = "INSERT INTO empallow (allowid, empnum, amount) VALUES ('$id', '$empnum', '$amount')";
	$empAllowRslt = db_exec ($sql) or errDie ("Unable to add allowance to employee.");
	if (pg_cmdtuples ($empAllowRslt) < 1) {
		return "Unable to add allowance to employee.";
	}

	$writeEmpAllow =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Allowance added to employee</th></tr>
<tr class=datacell><td>Allowance added to employee.
<a href='salaries-staff.php?empnum=$empnum'>Click here to continue processing</a>.</td></tr>
</table>
";
	return $writeEmpAllow;
}

?>
