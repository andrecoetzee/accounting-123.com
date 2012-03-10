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
# Get settings
require ("../settings.php");

# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirmLeave ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = writeLeave ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enterLeave ();
	}
} else {
	$OUTPUT = enterLeave ();
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



# enter new data
function enterLeave ()
{

	db_connect ();

	$sql = "SELECT empnum, sname, fnames, enum FROM employees WHERE div = '".USER_DIV."' ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "No employees found in database.";
	}

	# select employees
	$employees = "<select size=1 name='empnum'>\n";
	while ($myEmp = pg_fetch_array ($empRslt)) {
		$employees .= "<option value='$myEmp[empnum]'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</option>\n";
	}
	$employees .= "</select>\n";

// 	# get days
// 	$days = "<select size=1 name=days[]>\n";
// 	for ($i=1; $i <= 31; $i++) {
// 		$selected = ($i == date ("d")) ? "selected" : "";
// 		$days .= "<option value='$i' $selected>$i</option>\n";
// 	}
// 	$days .= "</select>\n";
// 	# get months
// 	$months = "<select size=1 name=months[]>\n";
// 	for ($i=1; $i <= 12; $i++) {
// 		$selected = ($i == date ("m")) ? "selected" : "";
// 		$months .= "<option value='$i' $selected>".getMonth ($i)."</option>\n";
// 	}
// 	$months .= "</select>\n";
// 	# get years
// 	$thisYear = date ("Y");
// 	$years = "<select size=1 name=years[]>\n";
// 	for ($i=$thisYear; $i <= ($thisYear+1); $i++) {
// 		$selected = ($i == date ("Y")) ? "selected" : "";
// 		$years .= "<option value='$i' $selected>$i</option>\n";
// 	}
// 	$years .= "</select>\n";

	# today
	$today = date ("Y-m-d");

/*
						<td align='center'>
							<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
								<tr>
									<td>$days</td>
									<td>$months</td>
									<td>$years</td>
								</tr>
							</table>
						</td>
*/

/*
						<td align='center'>
							<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
								<tr>
									<td>$days</td>
									<td>$months</td>
									<td>$years</td>
								</tr>
							</table>
						</td>
*/
	$enterLeave = "
		<h3>Employee Leave Application</h3>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='date' value='$today'>
			<input type='hidden' name='approvedby' value='".USER_NAME."'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td align='center'>$employees</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date of Application</td>
				<td align='center'>$today</td>
			</tr>
			<tr bgcolor='".bgcolorg()."' ".ass("This is the first date the employee will be on leave.").">
				<td>Leave start date</td>
				<td align='center'>".mkDateSelect("start")."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."' ".ass("This is the last date the employee will be on leave.").">
				<td>Leave end date</td>
				<td align='center'>".mkDateSelect("fin")."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Approved by</td>
				<td align='center'>".USER_NAME."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Type of leave</td>
				<td align='center'>
					<select size='1' name='type'>
						<option value='Paid vacation-leave'>Paid vacation-leave</option>
						<option value='Paid sick-leave'>Paid sick-leave</option>
						<option value='Paid study-leave'>Paid study-leave</option>
						<option value='Special paid-leave'>Special paid-leave</option>
						<option value='Unpaid leave'>Unpaid leave</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type=submit value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $enterLeave;

}



# confirm new data
function confirmLeave ($HTTP_POST_VARS)
{

	# check dates
// 	$start_day = $HTTP_POST_VARS["days"][0];
// 	$start_month = $HTTP_POST_VARS["months"][0];
// 	$start_year = $HTTP_POST_VARS["years"][0];
// 	$fin_day = $HTTP_POST_VARS["days"][1];
// 	$fin_month = $HTTP_POST_VARS["months"][1];
// 	$fin_year = $HTTP_POST_VARS["years"][1];

//	if (!checkdate ($start_month, $start_day, $start_year)) {
//		return "<li class=err>Invalid start date.";
//	} elseif (!checkdate ($fin_month, $fin_day, $fin_year)) {
//		return "<li class=err>Invalid end date.";
//	}


	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($date, "date", 1, 10, "Invalid approval date.");
	$v->isOk ($approvedby, "string", 1, 20, "Invalid value for 'approved by'.");
	$v->isOk ($type, "string", 1, 20, "Invalid leave type.");

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

	# get unix timestamps (seconds since unix epoch)
	$unixStart = mktime (0, 0, 0, $start_month, $start_day, $start_year);
	$unixEnd = mktime (0, 0, 0, $fin_month, $fin_day, $fin_year);
	$unixDay = (60 * 60 * 24);
	# interval
	$days_between = (((($unixEnd - $unixStart) / 60) / 60) / 24);

	$days_between++;
	# if mourn
	if($days_between < 1){
		return "<li class='err'> Invalid leave duration.
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
	}

	# don't count Saturdays & Sundays
	$workDays = 0;
	$checkDate = $unixStart;
	for ($i = 1; $i <= $days_between; $i++) {
		// $checkDate += $unixDay;
		// Must check the start date first then onwards
		$day = date ("l", $checkDate);
		if ($day == "Saturday" || $day == "Sunday") {
			$checkDate += $unixDay;
			continue;
		}
		$checkDate += $unixDay;
		$workDays++;
	}

	# get employee details
	db_connect ();

	$sql = "SELECT empnum, sname, fnames, enum FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	$confirmLeave = "
		<h3>Confirm Employee leave</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='empnum' value='$myEmp[empnum]'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='startdate' value='".$start_year."-".$start_month."-".$start_day."'>
			<input type='hidden' name='enddate' value='".$fin_year."-".$fin_month."-".$fin_day."'>
			<input type='hidden' name='approvedby' value='$approvedby'>
			<input type='hidden' name='type' value='$type'>
			<input type='hidden' name='workingdays' value='$workDays'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td align='center'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date of Application</td>
				<td align='center'>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Leave start date</td>
				<td align='center'>".$start_year."-".$start_month."-".$start_day."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Leave end date</td>
				<td align='center'>".$fin_year."-".$fin_month."-".$fin_day."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Total days inbetween</td>
				<td align='center'>$days_between</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Total working days inbetween</td>
				<td align='center'>$workDays</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Approved by</td>
				<td align='center'>$approvedby</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Type of leave</td>
				<td align='center'>$type</td>
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
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		errDie ("Invalid employee type: $type.");
	}
	$myEmp = pg_fetch_array ($empRslt);
	$initial_days = $myEmp['value'];
	*/

	# get sum of days taken
	$sql = "SELECT SUM (workingdays) AS taken FROM empleave WHERE empnum='$empnum' AND type='$type' AND div = '".USER_DIV."'";
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
function writeLeave ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($date, "date", 1, 10, "Invalid date.");
	$v->isOk ($startdate, "date", 1, 10, "Invalid leave start date.");
	$v->isOk ($enddate, "date", 1, 10, "Invalid leave end date.");
	$v->isOk ($approvedby, "string", 1, 20, "Invalid value for 'approved by'.");
	$v->isOk ($type, "string", 1, 20, "Invalid leave type.");
	$v->isOk ($workingdays, "num", 1, 3, "Invalid value for working days off.");

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

	switch ($type) {
		case "Paid vacation-leave":
			$type = "leave_vac";
			# $type = "vaclea";
			if (!checkLeave ($empnum, $type, $workingdays)) {
				return "<li>ERROR : Leave period selected exceeds allowed amount for paid vacation-leave.";
			}
			break;
		case "Paid sick-leave":
			$type = "leave_sick";
			# $type = "siclea";
			if (!checkLeave ($empnum, $type, $workingdays)) {
				return "<li>ERROR : Leave period selected exceeds allowed amount for paid sick-leave.";
			}
			break;
		case "Paid study-leave":
			$type = "leave_study";
			# $type = "stdlea";
			if (!checkLeave ($empnum, $type, $workingdays)) {
				return "<li>ERROR : Leave period selected exceeds allowed amount for paid study-leave.";
			}
			break;
		case "Special paid-leave":
			# $type = "leave_special";
			$type = "leave_special";
			break;
		default:
			# $type = "leave_unpaid";
			$type = "leave_unpaid";
			break;
	}

	# Connect to db
	db_connect ();

	# write to db
	$sql = "
		INSERT INTO empleave (
			empnum, date, startdate, enddate, approvedby, type, workingdays, nonworking, approved, div
		) VALUES (
			'$empnum', '$date', '$startdate', '$enddate', '$approvedby', '$type', '$workingdays', '0', 'n', '".USER_DIV."'
		)";
	$leaveRslt = db_exec ($sql) or errDie ("Unable to write approved leave to database.");
	if (pg_cmdtuples ($leaveRslt) < 1) {
		return "Unable to write approved leave to database.";
	}

	$writeLeave = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Employee leave requested</th>
			</tr>
			<tr class='datacell'>
				<td>Employee leave from $startdate until $enddate been requested.</td>
			</tr>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $writeLeave;

}


?>