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
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "write":
			$OUTPUT = writeLeave ($_POST);
			break;
		default:
			if(isset($_GET['id'])){
				$OUTPUT = confirmLeave ($_GET);
			}else{
				$OUTPUT = "<li class=err>Invalid Use of module.";
			}
	}
} else {
	if(isset($_GET['id'])){
		$OUTPUT = confirmLeave ($_GET);
	}else{
		$OUTPUT = "<li class=err>Invalid Use of module.";
	}
}

# display output
require ("../template.php");

# return name of month for $monthNo
function getMonth ($monthNo)
{
	if ($monthNo > 12 || $monthNo < 1 || !is_int ($monthNo)) {
		errDie ("Invalid month: $monthNo.");
	}
	$arrMonths = array (1 => "January",
		2 => "February",
		3 => "March",
		4 => "April",
		5 => "May",
		6 => "June",
		7 => "July",
		8 => "August",
		9 => "September",
		10 => "October",
		11 => "November",
		12 => "December"
	);
	return $arrMonths[$monthNo];
}

function typedef($type)
{
	switch ($type) {
		case "leave_vac":
			$def = "Paid vacation-leave";
			break;
		case "leave_sick":
			$def = "Paid sick-leave";
			break;
		case "leave_study":
			$def = "Paid study-leave";
			break;
		case "leave_special":
			$def = "Special paid-leave";
			break;
		default:
			$def = "Unpaid leave";
			break;
	}
	return $def;
}

# confirm new data
function confirmLeave ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid leave number.");

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

	db_connect();
	$sql = "SELECT * FROM empleave WHERE id = '$id' AND div = '".USER_DIV."'";
	$leaRslt = db_exec ($sql) or errDie ("Unable to retrieve employee leave application from database.");
	if(pg_numrows($leaRslt) > 0){
		$lea = pg_fetch_array ($leaRslt);
	}else{
		return "<li class=err> Invalid leave number";
	}

	# format the dates
	$lea['startdate'] = explode("-", $lea['startdate']);
	$lea['startdate'] = $lea['startdate'][2]."-".$lea['startdate'][1]."-".$lea['startdate'][0];
	$lea['enddate'] = explode("-", $lea['enddate']);
	$lea['enddate'] = $lea['enddate'][2]."-".$lea['enddate'][1]."-".$lea['enddate'][0];

	$typedef = typedef($lea['type']);
	$today = date("d-m-Y");

	# get employee details
	db_connect ();
	$sql = "SELECT empnum, sname, fnames FROM employees WHERE empnum='$lea[empnum]' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	$typedef = typedef($lea['type']);

	list($start_day, $start_month, $start_year) = explode("-", $lea['startdate']);
	list($fin_day, $fin_month, $fin_year) = explode("-", $lea['enddate']);

	# get unix timestamps (seconds since unix epoch)
	$unixStart = mktime (0, 0, 0, $start_month, $start_day, $start_year);
	$unixEnd = mktime (0, 0, 0, $fin_month, $fin_day, $fin_year);
	$unixDay = (60 * 60 * 24);
	# interval
	$days_between = (((($unixEnd - $unixStart) / 60) / 60) / 24);

	$confirmLeave = "<h3>Confirm Cancel Employee Leave Application</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=id value='$id'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Employee</td><td align=center>$myEmp[sname], $myEmp[fnames] ($myEmp[empnum])</td></tr>
	<tr class='bg-even'><td>Date of approval</td><td align=center>$today</td></tr>
	<tr class='bg-odd'><td>Leave start date</td><td align=center>$lea[startdate]</td></tr>
	<tr class='bg-even'><td>Leave end date</td><td align=center>$lea[enddate]</td></tr>
	<tr class='bg-odd'><td>Total days inbetween</td><td align=center>$days_between</td></tr>
	<tr class='bg-even'><td>Total working days inbetween</td><td align=center>$lea[workingdays]</td></tr>
	<tr class='bg-odd'><td>Type of leave</td><td align=center>$typedef</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $confirmLeave;
}

# write new data
function writeLeave ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid leave number.");

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

	db_connect();
	$sql = "SELECT * FROM empleave WHERE id = '$id' AND div = '".USER_DIV."'";
	$leaRslt = db_exec ($sql) or errDie ("Unable to retrieve employee leave application from database.");
	if(pg_numrows($leaRslt) > 0){
		$lea = pg_fetch_array ($leaRslt);
	}else{
		return "<li class=err> Invalid leave number";
	}

	# get employee details
	$sql = "SELECT empnum, sname, fnames FROM employees WHERE empnum = '$lea[empnum]' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	# write to db
	$sql = "DELETE FROM empleave WHERE id = '$id' AND div = '".USER_DIV."'";
	$leaveRslt = db_exec ($sql) or errDie ("Unable to update leave in database.");
	if (pg_cmdtuples ($leaveRslt) < 1) {
		return "Unable to cancel leave in database.";
	}

	# format the dates
	$lea['startdate'] = explode("-", $lea['startdate']);
	$lea['startdate'] = $lea['startdate'][2]."-".$lea['startdate'][1]."-".$lea['startdate'][0];
	$lea['enddate'] = explode("-", $lea['enddate']);
	$lea['enddate'] = $lea['enddate'][2]."-".$lea['enddate'][1]."-".$lea['enddate'][0];

	// Layout
	$writeLeave =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Employee leave cancelled </th></tr>
	<tr class=datacell><td>Leave application for Employee <b>$myEmp[sname], $myEmp[fnames] ($myEmp[empnum])</b> from $lea[startdate] until $lea[enddate] been cancelled.</td></tr>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $writeLeave;
}
?>
