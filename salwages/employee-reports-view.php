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

if (isset ($_POST["key"])) {
	if ($_POST["key"] == "show") {
		$OUTPUT = showReports ($_POST);
	} else {
		errDie ("Invalid use of module.");
	}
} elseif (isset ($_GET["key"])) {
	$OUTPUT = printReport ($_GET);
} else {
	$OUTPUT = slctReports ();
}

require ("../template.php");



# select reports to view
function slctReports ($err="")
{

	# connect to db
	db_connect ();

	# get employees
	$employees = "
		<select size='1' name='empnum'>
			<option value='ALL' style='font-align: center'>- ALL -</option>";
	$sql = "SELECT empnum, sname, fnames, enum FROM employees WHERE div = '".USER_DIV."' ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "No employees found in database.<p>"
			.mkQuickLinks(
				ql("../admin-employee-add.php", "Add Employee"),
				ql("../admin-employee-view.php", "View Employees")
			);
	}
	while ($myEmp = pg_fetch_array ($empRslt)) {
		$employees .= "<option value='$myEmp[empnum]'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</option>\n";
	}
	$employees .= "</select>";

	# get report types
	$report_types = "
		<select size='1' name='type'>
			<option value='ALL' style='font-align: center'>- ALL -</option>";
	$sql = "SELECT type FROM report_types WHERE div = '".USER_DIV."' ORDER BY type";
	$typeRslt = db_exec ($sql) or errDie ("Unable to select report types from database.");
	if (pg_numrows ($typeRslt) < 1) {
		return "No report types found in database.";
	}
	while ($myType = pg_fetch_array ($typeRslt)) {
		$report_types .= "<option value='$myType[type]'>$myType[type]</option>\n";
	}
	$report_types .= "</select>";

	$slctReports = "
		<h3>View employee reports</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			$err
			<input type='hidden' name='key' value='show'>
			<tr bgcolor='".bgcolorg()."'>
				<th>Filter reports by:</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>Employee:<br>$employees</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>Report type:<br>$report_types</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' value='Show reports &raquo;'></td>
				<td valign='left'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $slctReports;

}



function showReports ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();
	$v->isOk ($type, "string", 1, 80, "Invalid report type.");
	$v->isOk ($empnum, "string", 1, 20, "Invalid employee number.");

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


	# connect to db
	db_connect ();

	# get employee info
	if ($empnum != "ALL") {
		$sql = "SELECT sname, fnames, enum FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
		$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
		if (pg_numrows ($empRslt) < 1) {
			return "Invalid employee number: $empnum.";
		}
		$myEmp = pg_fetch_array ($empRslt);
		$employee = "$myEmp[sname], $myEmp[fnames] ($empnum)";
	} else {
		$employee = "ALL";
	}

	# construct sql query
	$sql = "SELECT * FROM empreports";
	if ($type != "ALL" && $empnum != "ALL") {
		$sql .= " WHERE type='$type' AND empnum='$empnum'";
	} elseif ($type != "ALL") {
		$sql .= " WHERE type='$type'";
	} elseif ($empnum != "ALL") {
		$sql .= " WHERE empnum='$empnum'";
	}
	$sql .= " ORDER BY empnum";

	# get reports from db
	$reports = "";
	$i = 0;
	$repRslt = db_exec ($sql) or errDie ("Unable to select reports from database.");
	if (pg_numrows ($repRslt) < 1) {
		return slctReports("<li class='err'>No Report of the selected type has been filed for the selected employee(s).</li><br>");
	}

	while ($myRep = pg_fetch_array ($repRslt)) {
		# get employee info if ALL selected
		if ($employee == "ALL") {
			$sql = "SELECT sname, fnames FROM employees WHERE empnum='$myRep[empnum]' AND div = '".USER_DIV."'";
			$empRslt = db_exec ($sql) or errDie ("Unable to select employee information from database.");
			if (pg_numrows ($empRslt) < 1) {
				return "Invalid employee number in reports: $empnum.";
			}
			$myEmp = pg_fetch_array ($empRslt);
			$this_employee = "$myEmp[sname], $myEmp[fnames] ($empnum)";
		} else {
			$this_employee = $employee;
		}

		$reports .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$myRep[id]</td>
				<td>$myRep[date]</td>
				<td>$this_employee</td>
				<td>$myRep[type]</td>
				<td>$myRep[submitter]</td>
				<td><a target='_blank' href='".SELF."?key=print&id=$myRep[id]'>Print</a></td>
			</tr>\n";
		$i++;
	}

	$showReports = "
		<h3>Viewing reports</h3>
		<h4>Employee: $employee<br>Type: $type</h4>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Report ID</th>
				<th>Date</th>
				<th>Employee</th>
				<th>Type</th>
				<th>Submitter</th>
				<th>Options</th>
			</tr>
			$reports
			<tr>
				<td></td>
				<td valign='left'></td>
			</tr>
		</table>"
		.mkQuickLinks(
			ql("employee-reports-add.php","Add Employee Report"),
			ql("employee-reports-view.php","View Employee Report"),
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $showReports;

}




function printReport ($_GET)
{

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new validate ();
	#$v->isOk ($id, "num", 1, 20, "Invalid report ID.");

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


	# connect to db
	db_connect ();

	# get report info
	$sql = "SELECT * FROM empreports WHERE id='$id' AND div = '".USER_DIV."'";
	$repRslt = db_exec ($sql) or errDie ("Unable to select employee report from database.");
	if (pg_numrows ($repRslt) < 1) {
		return "Invalid employee report ID: $id";
	}
	$myRep = pg_fetch_array ($repRslt);

	# get employee info
	$sql = "SELECT sname, fnames, enum FROM employees WHERE empnum='$myRep[empnum]' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee information from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee number in reports table: $empnum.";
	}
	$myEmp = pg_fetch_array ($empRslt);
	$employee = "$myEmp[sname], $myEmp[fnames] ($myEmp[enum])";

	$OUTPUT = "
		<table border='0' cellpadding='5' cellspacing='0' width='600'>
			<tr>
				<td width='50%' align='center'>
					<img src='../".COMP_LOGO."' width='230' height='47' alt='".COMP_NAME."'>
				</td>
				<td align='right'>
					".COMP_ADDRESS."
					<br>".COMP_TEL."
					<br>".COMP_FAX."
				</td>
			<tr>
		</table>

		<table ".TMPL_tblDflts.">
			<tr>
				<td width='20%'>Employee: $employee</td>
				<td width='20%'>Report type: $myRep[type]</td>
				<td width='20%'></td>
			</tr>
		</table>

		<h3>Employee Report, Date: $myRep[date]</h3>
		".nl2br ($myRep["report"])."
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'><hr size='1' noshade></td>
			</tr>
			<tr>
				<td>Submitter of report:</td>
				<td>$myRep[submitter]</td>
			</tr>
			<tr>
				<td>Other persons responsible:</td>
				<td>$myRep[submitter2]</td>
			</tr>
			<tr>
				<td><br></td>
				<td>$myRep[submitter3]</td>
			</tr>
			<tr>
				<td><br></td>
				<td>$myRep[submitter4]</td>
			</tr>
		</table>";
	require ("../tmpl-print.php");

}



?>