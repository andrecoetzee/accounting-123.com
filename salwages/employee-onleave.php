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

# Display default output
$OUTPUT = printLea();

require ("../template.php");

# show invoices
function printLea ()
{


	# Set up table to display in
	$printLea = "
		<h3>Employees on Leave</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Employee</th>
				<th>Type Of Leave</th>
				<th>Start Date</th>
				<th>End Date</th>
				<th>Approved By</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
	$today = date("Y-m-d");
	$sql = "SELECT * FROM empleave WHERE enddate >= '$today' AND approved = 'y' AND div = '".USER_DIV."' ORDER BY id DESC";
	$leaRslt = db_exec ($sql) or errDie ("Unable to retrieve employee leave from database.");
	if (pg_numrows ($leaRslt) < 1) {
		$printLea = "<li class='err'>There are no Employees on Leave.</li><br>";
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
					<td>$typedef</td>
					<td>$lea[startdate]</td>
					<td>$lea[enddate]</td>
					<td>$lea[approvedby]</td>
				</tr>";
			$i++;
		}
	}
	$printLea .= "
		</table>"
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