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
		case "confirm":
			$OUTPUT = confirmLeave ($_POST);
			break;
		case "write":
			$OUTPUT = writeLeave ($_POST);
			break;
		default:
			if(isset($_GET['id'])){
				$OUTPUT = enterLeave ($_GET);
			}else{
				$OUTPUT = "<li class='err'>Invalid Use of module.</li>";
			}
	}
} else {
	if(isset($_GET['id'])){
		$OUTPUT = enterLeave ($_GET);
	}else{
		$OUTPUT = "<li class='err'>Invalid Use of module.</li>";
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

	$arrMonths = array (
		1 => "January",
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



# enter new data
function enterLeave ($_GET)
{

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid Leave number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	db_connect();

	$sql = "SELECT * FROM empleave WHERE id = '$id' AND div = '".USER_DIV."'";
	$leaRslt = db_exec ($sql) or errDie ("Unable to retrieve employee leave application from database.");
	if(pg_numrows($leaRslt) > 0){
		$lea = pg_fetch_array ($leaRslt);
	}else{
		return "<li class='err'> Invalid leave number.</li>";
	}

	# get employee details
	db_connect ();

	$sql = "SELECT empnum, sname, fnames, enum FROM employees WHERE empnum='$lea[empnum]' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	# format the dates
	$lea['startdate'] = explode("-", $lea['startdate']);
	$lea['startdate'] = $lea['startdate'][2]."-".$lea['startdate'][1]."-".$lea['startdate'][0];
	$lea['enddate'] = explode("-", $lea['enddate']);
	$lea['enddate'] = $lea['enddate'][2]."-".$lea['enddate'][1]."-".$lea['enddate'][0];

	$typedef = typedef($lea['type']);
	$today = date("d-m-Y");

	$enterLeave = "
		<h3>Approve employee leave</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='id' value='$id'>
			<input type='hidden' name='approvedby' value='".USER_NAME."'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td align='center'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date of approval</td>
				<td align='center'>$today</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Leave start date</td>
				<td align='center'>$lea[startdate]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Leave end date</td>
				<td align='center'>$lea[enddate]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Approved by</td>
				<td align='center'>".USER_NAME."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Type of leave</td>
				<td align='center'>$typedef</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Non-working days<br>(Excluding weekends)</td>
				<td align='center'><input type='text' size='5' name='nonworking' value='0'></td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $enterLeave;

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
	$v->isOk ($nonworking, "num", 1, 2, "Invalid value for non-working days.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	db_connect();

	$sql = "SELECT * FROM empleave WHERE id = '$id' AND div = '".USER_DIV."'";
	$leaRslt = db_exec ($sql) or errDie ("Unable to retrieve employee leave application from database.");
	if(pg_numrows($leaRslt) > 0){
		$lea = pg_fetch_array ($leaRslt);
	}else{
		return "<li class='err'> Invalid leave number.</li>";
	}

	# format the dates
	$lea['startdate'] = explode("-", $lea['startdate']);
	$lea['startdate'] = $lea['startdate'][2]."-".$lea['startdate'][1]."-".$lea['startdate'][0];
	$lea['enddate'] = explode("-", $lea['enddate']);
	$lea['enddate'] = $lea['enddate'][2]."-".$lea['enddate'][1]."-".$lea['enddate'][0];

	$typedef = typedef($lea['type']);
	$today = date("d-m-Y");

	$lea['workingdays'] -= $nonworking;
	if ($lea['workingdays'] < 1) {
		return "<li class='err'>The number of non working days is above or the same as the number of working days,<br>this invalidates the leave. Please check your entry.\n";
	}

	# get employee details
	db_connect ();

	$sql = "SELECT empnum, sname, fnames, enum FROM employees WHERE empnum='$lea[empnum]' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	$typedef = typedef($lea['type']);
	$today = date("d-m-Y");

	list($start_day, $start_month, $start_year) = explode("-", $lea['startdate']);
	list($fin_day, $fin_month, $fin_year) = explode("-", $lea['enddate']);

	# get unix timestamps (seconds since unix epoch)
	$unixStart = mktime (0, 0, 0, $start_month, $start_day, $start_year);
	$unixEnd = mktime (0, 0, 0, $fin_month, $fin_day, $fin_year);
	$unixDay = (60 * 60 * 24);
	# interval
	$days_between = (((($unixEnd - $unixStart) / 60) / 60) / 24);

	$days_between++;

	$confirmLeave = "
		<h3>Confirm employee leave</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='id' value='$id'>
			<input type='hidden' name='nonworking' value='$nonworking'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td align='center'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date of approval</td>
				<td align='center'>$today</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Leave start date</td>
				<td align='center'>$lea[startdate]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Leave end date</td>
				<td align='center'>$lea[enddate]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Total days inbetween</td>
				<td align='center'>$days_between</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Total working days inbetween</td>
				<td align='center'>$lea[workingdays]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Approved by</td>
				<td align='center'>$approvedby</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Type of leave</td>
				<td align='center'>$typedef</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Non-working days</td>
				<td align='center'>$nonworking</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $confirmLeave;

}




# Check if ok to give leave
function checkLeave ($empnum, $type, $workingdays)
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
	$empRslt = db_exec ($sql) or errDie ("Unable to select leave type from database.");
	if (pg_numrows ($empRslt) < 1) {
		errDie ("Invalid leave type : $type.");
	}
	$myEmp = pg_fetch_array ($empRslt);
	$initial_days = $myEmp['value'];
	*/

	# get sum of days taken
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

	if ($allowed < $workingdays) {
		return 0;
	} else {
		return 1;
	}

}




# write new data
function writeLeave ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid leave number.");
	$v->isOk ($nonworking, "num", 1, 2, "Invalid value for non-working days.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	db_connect();

	$sql = "SELECT * FROM empleave WHERE id = '$id' AND div = '".USER_DIV."'";
	$leaRslt = db_exec ($sql) or errDie ("Unable to retrieve employee leave application from database.");
	if(pg_numrows($leaRslt) > 0){
		$lea = pg_fetch_array ($leaRslt);
	}else{
		return "<li class='err'> Invalid leave number.</li>";
	}

	# check if leave can be granted
	$leav = array("leave_sick", "leave_study", "leave_vac");
	if(in_array($lea['type'], $leav)){
		if (!checkLeave ($lea['empnum'], $lea['type'], ($lea['workingdays'] - $nonworking))) {
			return "<li>ERROR : Leave period selected exceeds allowed amount for ".typedef($lea['type']).".";
		}
	}

	db_connect ();

	# write to db
	$sql = "UPDATE empleave SET workingdays = (workingdays - '$nonworking'), nonworking = '$nonworking', approved = 'y' WHERE id = '$id' AND div = '".USER_DIV."'";
	$leaveRslt = db_exec ($sql) or errDie ("Unable to update approved leave to database.");
	if (pg_cmdtuples ($leaveRslt) < 1) {
		return "Unable to write approved leave to database.";
	}

	# format the dates
	$lea['startdate'] = explode("-", $lea['startdate']);
	$lea['startdate'] = $lea['startdate'][2]."-".$lea['startdate'][1]."-".$lea['startdate'][0];
	$lea['enddate'] = explode("-", $lea['enddate']);
	$lea['enddate'] = $lea['enddate'][2]."-".$lea['enddate'][1]."-".$lea['enddate'][0];

	$writeLeave = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Employee leave approved</th>
			</tr>
			<tr class='datacell'>
				<td>Employee leave from $lea[startdate] until $lea[enddate] has been approved.</td>
			</tr>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $writeLeave;

}



?>