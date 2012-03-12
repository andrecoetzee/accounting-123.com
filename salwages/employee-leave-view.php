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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printLea ($_POST);
			break;
		default:
			$OUTPUT = slct();
			break;
	}
} else {
        # Display default output
        $OUTPUT = slct();
}

require ("../template.php");



# Default view
function slct()
{

    //layout
	$slct = "
		<h3>View Employee Leave Applications<h3>
		<table ".TMPL_tblDflts." width='60%'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='view'>
			<tr>
				<th colspan='2'>By Application Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>
					".mkDateSelect("from")."
					&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
					".mkDateSelect("to")."
				</td>
				<td valign='bottom'><input type='submit' value='Search'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $slct;

}



# show invoices
function printLea ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

	# mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	if(!checkdate($from_month, $from_day, $from_year)){
		$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
		$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
        return $confirm;
	}


		# Set up table to display in
		$printLea = "
			<h3>View Employee Leave Applications</h3>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Employee</th>
					<th>Application Date</th>
					<th>Start Date</th>
					<th>End Date</th>
					<th>Working Days</th>
					<th>Type Of Leave</th>
					<th colspan='2'>Options</th>
				</tr>";

		# connect to database
		db_connect ();

		# Query server
		$i = 0;
		$sql = "SELECT * FROM empleave WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' ORDER BY id DESC";
		$leaRslt = db_exec ($sql) or errDie ("Unable to retrieve employee leave applications from database.");
		if (pg_numrows ($leaRslt) < 1) {
			$printLea = "<li class='err'>No Outstanding Employee Leave applications found.</li><br>";
		}else{
			while ($lea = pg_fetch_array ($leaRslt)) {

				$typedef = typedef($lea['type']);

				# format date
				$lea['date'] = explode("-", $lea['date']);
				$lea['date'] = $lea['date'][2]."-".$lea['date'][1]."-".$lea['date'][0];
				$lea['startdate'] = explode("-", $lea['startdate']);
				$lea['startdate'] = $lea['startdate'][2]."-".$lea['startdate'][1]."-".$lea['startdate'][0];
				$lea['enddate'] = explode("-", $lea['enddate']);
				$lea['enddate'] = $lea['enddate'][2]."-".$lea['enddate'][1]."-".$lea['enddate'][0];

				# get employee details
				db_connect ();
				$sql = "SELECT empnum, sname, fnames, enum FROM employees WHERE empnum='$lea[empnum]' AND div = '".USER_DIV."'";
				$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
				if (pg_numrows ($empRslt) < 1) {
					return "Invalid employee ID.";
				}
				$myEmp = pg_fetch_array ($empRslt);

				$printLea .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</td>
						<td>$lea[date]</td>
						<td>$lea[startdate]</td>
						<td>$lea[enddate]</td>
						<td>$lea[workingdays]</td>
						<td>$typedef</td>";

				# If approve
				if($lea['approved'] == 'n'){
					$printLea .= "
							<td><a href='employee-leave-approve.php?id=$lea[id]'>Approve</td>
							<td><a href='employee-leave-cancel.php?id=$lea[id]'>Cancel</td>
						</tr>";
				}else{
					$printLea .= "<td colspan='2'><br></td></tr>";
				}
				$i++;
			}
		}
		$printLea .= "
			</table>
			<br>
			<input type='button' onClick=\"javascript:window.open('../public_holiday_list.php','window1','width=400,height=360,scrollbars=yes')\" value='Public Holiday List'>
			<br><br>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $printLea;

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



?>