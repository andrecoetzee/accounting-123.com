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
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirmReport ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = writeReport ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = enterReport ();
	}
}elseif(isset($HTTP_GET_VARS["err"])){
        # get vars from HTTP_GET_VARS
        extract ($HTTP_GET_VARS);
        $OUTPUT = enterReport ($submitter, $submitter2, $submitter3, $submitter4, $report, $err);
} else {
	$OUTPUT = enterReport ();
}

$OUTPUT .= mkQuickLinks(
	ql("employee-reports-add.php","Add Employee Report"),
	ql("employee-reports-view.php","View Employee Report"),
	ql("../admin-employee-add.php", "Add Employee"),
	ql("../admin-employee-view.php", "View Employees")
);

# display output
require ("../template.php");




# enter new data
function enterReport ($submitter="", $submitter2="", $submitter3="", $submitter4="", $report="", $err="")
{

	db_connect ();

	# select employees
	$sql = "SELECT empnum, sname, fnames, enum FROM employees WHERE div = '".USER_DIV."' ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "No employees found in database.";
	}
	$employees = "<select size='1' name='empnum'>\n";
	while ($myEmp = pg_fetch_array ($empRslt)) {
		$employees .= "<option value='$myEmp[empnum]'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</option>\n";
	}
	$employees .= "</select>\n";

	# get report types from db
	$report_types = "<select size='1' name='type'>\n";
	$sql = "SELECT type FROM report_types WHERE div = '".USER_DIV."' ORDER BY type";
	$typeRslt = db_exec ($sql) or errDie ("Unable to select report types from database.");
	if (pg_numrows ($typeRslt) > 0) {
		while ($myType = pg_fetch_array ($typeRslt)) {
			$report_types .= "<option value='$myType[type]'>$myType[type]</option>\n";
		}
	}else{
		return "<li>ERROR : There are no report types found in Cubit.</li>";
	}
	$report_types .= "</select>\n";

	$enterReport = "
		<h3>Add employee report to system</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='date' value='".DATE_STD."'>
			$err
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td align='center'>$employees</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td align='center'>".DATE_STD."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Type of report</td>
				<td align='center'>$report_types</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Person submitting report</td>
				<td align='center'><input type='text' size='20' name='submitter' value='$submitter'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Other person responsible for report</td>
				<td align='center'><input type='text' size='20' name='submitter2' value='$submitter2'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Other person responsible for report</td>
				<td align='center'><input type='text' size='20' name='submitter3' value='$submitter3'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Other person responsible for report</td>
				<td align='center'><input type='text' size='20' name='submitter4' value='$submitter4'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Details of report</td>
				<td align='center'><textarea name='report' cols='40' rows='20'>$report</textarea></td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
				<td valign='left'></td>
			</tr>
		</form>
		</table>";
	return $enterReport;

}



# confirm new data
function confirmReport ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($date, "date", 1, 10, "Invalid date.");
	$v->isOk ($type, "string", 1, 50, "Invalid report type.");
	$v->isOk ($submitter, "string", 1, 100, "Invalid submitter.");
	$v->isOk ($submitter2, "string", 0, 100, "Invalid submitter.(2)");
	$v->isOk ($submitter3, "string", 0, 100, "Invalid submitter.(3)");
	$v->isOk ($submitter4, "string", 0, 100, "Invalid submitter.(4)");
	$v->isOk ($report, "allstring", 1, 5000, "Invalid report.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "-".$e["msg"]."<br>";
		}
        $Errors = "<tr><td class='err' colspan='2'>$confirmCust</td></tr>
        <tr><td colspan='2'><br></td></tr>";
		return enterReport ($submitter, $submitter2, $submitter3, $submitter4, $report, $Errors);
	}



	# get employee details
	db_connect ();

	$sql = "SELECT empnum, sname, fnames, enum FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	$confirmReport = "
		<h3>Confirm new report</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='empnum' value='$myEmp[empnum]'>
			<input type='hidden' name='enum' value='$myEmp[enum]'>
			<input type='hidden' name='date' value='$date'>
			<input type='hidden' name='type' value='$type'>
			<input type='hidden' name='submitter' value='$submitter'>
			<input type='hidden' name='submitter2' value='$submitter2'>
			<input type='hidden' name='submitter3' value='$submitter3'>
			<input type='hidden' name='submitter4' value='$submitter4'>
			<input type='hidden' name='report' value='$report'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td align='center'>$myEmp[enum]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td align='center'>$date</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Type of report</td>
				<td align='center'>$type</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Person submitting report</td>
				<td align='center'>$submitter</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Other person responsible for report</td>
				<td align='center'>$submitter2</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Other person responsible for report</td>
				<td align='center'>$submitter3</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Other person responsible for report</td>
				<td align='center'>$submitter4</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td valign='top'>Details of report</td>
				<td align='center'>".nl2br ($report)."</td>
			</tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $confirmReport;

}




# write new data
function writeReport ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	if(isset($back)) {
		return enterReport ($submitter, $submitter2, $submitter3, $submitter4, $report, "");
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($date, "date", 1, 10, "Invalid date.");
	$v->isOk ($type, "string", 1, 50, "Invalid report type.");
	$v->isOk ($submitter, "string", 1, 100, "Invalid submitter.");
	$v->isOk ($submitter2, "string", 0, 100, "Invalid submitter.(2)");
	$v->isOk ($submitter3, "string", 0, 100, "Invalid submitter.(3)");
	$v->isOk ($submitter4, "string", 0, 100, "Invalid submitter.(4)");
	$v->isOk ($report, "allstring", 1, 5000, "Invalid report.");

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

	$Sl = "SELECT max(id) FROM empreports";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$data = pg_fetch_array($Ri);

	$nextid = $data['max'];
	$nextid++;

	# write to db
	$sql = "
		INSERT INTO empreports (
			id, empnum, date, type, submitter, submitter2, 
			submitter3, submitter4, report, div
		) VALUES (
			'$nextid', '$empnum', '$date', '$type', '$submitter', '$submitter2', 
			'$submitter3', '$submitter4', '$report', '".USER_DIV."'
		)";
	$reportRslt = db_exec ($sql) or errDie ("Unable to add report to database.");
	if (pg_cmdtuples ($reportRslt) < 1) {
		return "Unable to add report to database.";
	}

	$writeReport = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Employee report added to system</th>
			</tr>
			<tr class='datacell'>
				<td>New employee report has been successfully added to Cubit.</td>
			</tr>
		</table>";
	return $writeReport;

}



?>