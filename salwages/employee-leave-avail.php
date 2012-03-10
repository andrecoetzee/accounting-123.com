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
require ("../libs/ext.lib.php");

if (isset($HTTP_GET_VARS['empnum'])){
	$OUTPUT = Emplea ($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class='err'> Invalid use of module.</li>";
}

require ("../template.php");




# View leave
function Emplea ($HTTP_GET_VARS)
{

	# Get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "-".$e["msg"]."<br>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# Connect to db
	db_connect ();

	# Get employee info to edit
	$sql = "SELECT * FROM employees WHERE empnum='$empnum'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee number.";
	}
	$emp = pg_fetch_array($empRslt);

	# Get the arrays
	$lvac = getLeave ($empnum, "leave_vac");
	$lsick = getLeave ($empnum, "leave_sick");
	$lstudy = getLeave ($empnum, "leave_study");

	$lea = "
		<h3>Employee Leave Available</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td align='center'>$emp[sname], $emp[fnames] ($emp[enum])</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Paid Vacation Leave</td>
				<td align='center'>$lvac[1] days</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Paid Sick Leave</td>
				<td align='center'>$lsick[1] days</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Paid Study Leave</td>
				<td align='center'>$lstudy[1] days</td>
			</tr>
		<table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $lea;

}




# Check if ok to give leave
function getLeave ($empnum, $type)
{

	switch ($type) {
		case "leave_vac":
			$ttype = "vaclea";
			break;
		case "leave_sick":
			$ttype = "siclea";
			break;
		case "leave_study":
			$ttype = "stdlea";
			break;
	}

	# Connect to db
	db_connect ();

	# Get employee info to edit
	$sql = "SELECT $ttype FROM employees WHERE empnum = '$empnum'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee number.";
	}
	$emp = pg_fetch_array($empRslt);
	$initial_days = $emp[$ttype];

	/*
	# Get allowed days
	$sql = "SELECT value FROM settings WHERE lower(constant) = lower('$type')";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		errDie ("Invalid employee number: $empnum.");
	}
	$myEmp = pg_fetch_array ($empRslt);
	$initial_days = $myEmp['value'];
	*/

	# Get sum of days taken
	$sql = "SELECT SUM (workingdays) AS taken FROM empleave WHERE empnum='$empnum' AND type='$type' AND approved = 'y'";
	$leaveRslt = db_exec ($sql) or errDie ("Unable to select employee leave from database.");
	if(pg_numrows($leaveRslt) > 0){
		$myLeave = pg_fetch_array ($leaveRslt);
		$taken_days = $myLeave["taken"];
	}else{
		$taken_days = 0;
	}

	# get currently allowed
	$allowed = $initial_days - $taken_days;

	$arr[0] = $type;
	$arr[1] = $allowed;

	return $arr;

}


?>