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
#
# admin-employee-view.php :: View employees in db
##

require ("settings.php");

$OUTPUT = viewEmp ();

require ("template.php");



# view employees in db
function viewEmp ()
{

	db_connect ();

	# Get employees from db
	$employees = "";
	$i = 0;
	$sql = "SELECT * FROM lemployees WHERE div = '".USER_DIV."' ORDER BY sname,fnames";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "No Past Employees Found.<p>"
			.mkQuickLinks(
				ql("admin-employee-add.php", "Add Employee")
			);
	}
	while ($myEmp = pg_fetch_array ($empRslt)) {
		$employees .= "
			<tr class='".bg_class()."'>
				<td>$myEmp[empnum]</td>
				<td>$myEmp[fnames]</td>
				<td>$myEmp[sname]</td>
				<td>$myEmp[leavereason]</td>
				<td>$myEmp[leavedate]</td>
				<td><a href='admin-lemployee-detail.php?empnum=$myEmp[empnum]'>Details</a></td>
				<td><a target=_blank href='salwages/irp5-data.php?empnum=$myEmp[empnum]'>Year to Date</a></td>
				<td><a href=# onClick=openwindowbg('docman/doc-view-type.php?xin=$myEmp[enum]&type=empl');>View Documents</a></td>
			</tr>";
		$i++;
	}

	# Set up table & form
	$enterEmp = "
		<h3>Employees</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Nr.</th>
				<th>First names</th>
				<th>Last name</th>
				<th>Reason for Leaving</th>
				<th>Date</th>
				<th colspan='3'>Options</th>
			</tr>
			$employees
			<tr class='".bg_class()."'>
				<td colspan='8'>Total: $i</td>
			</tr>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee")
		);
	return $enterEmp;

}


?>