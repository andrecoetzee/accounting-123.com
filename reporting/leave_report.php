<?

	require ("../settings.php");

	if(isset($HTTP_POST_VARS["key"])){
		switch($HTTP_POST_VARS["key"]){
			case "confirm":
				$OUTPUT = show_report($HTTP_POST_VARS);
				break;
			case "xls":
				$OUTPUT = show_xls ($HTTP_POST_VARS);
				break;
			default:
				$OUTPUT = get_employee ();
		}
	}else {
		$OUTPUT = get_employee ();
	}

//	require ("../tmpl-print.php");
	require ("../template.php");



function get_employee ()
{


	db_connect ();

	$get_employees = "SELECT * FROM employees ORDER BY sname";
	$run_employees = db_exec($get_employees) or errDie("Unable to get employees information.");
	if(pg_numrows($run_employees) < 1){
		return "<li class='err'>No Employees Found.</li>";
	}else {
		$emp_drop = "<select name='employee'>";
		while ($earr = pg_fetch_array($run_employees)){
			$emp_drop .= "<option value='$earr[empnum]'>$earr[fnames] $earr[sname] ($earr[empnum])</option>";
		}
		$emp_drop .= "</select>";
	}

	$display = "
			<h2>Select Employee For Report</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<tr>
					<td>$emp_drop</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td align='right'><input type='submit' value='Next'></td>
				</tr>
			</form>
			</table>";
	return $display;

}


function show_report ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if(!isset($employee) OR (strlen($employee) < 1)){
		return "Invalid use of module.";
	}

	db_connect ();

	$get_employee = "SELECT * FROM employees WHERE empnum = '$employee' LIMIT 1";
	$run_employee = db_exec($get_employee) or errDie("Unable to get employees information.");
	if(pg_numrows($run_employee) < 1){
		return "Invalid Employee Selected.";
	}else {
		$earr = pg_fetch_array($run_employee);
		//extract($earr);
	}

	$finstartdate = mkdate(getYearOfFinPrd(1)-1,$PRDMON[1],1);
	$finenddate = mkldate(getYearOfFinPrd(12)-1,$PRDMON[12]);


	db_connect ();

	#get all leave for this employee
	$get_leave = "SELECT * FROM empleave WHERE empnum = '$earr[empnum]' AND startdate > '$finstartdate' AND enddate < '$finenddate'";
	$run_leave = db_exec($get_leave) or errDie("Unable to get employee leave information.");
	if(pg_numrows($run_leave) < 1){
		$listing = "Employee did not apply for leave during the previous financial year.";
	}else {
		$total_leave_days = 0;
		$listing = "
				<tr>
					<th>Leave Start Date</th>
					<th>Leave End Date</th>
					<th>Approved By</th>
					<th>Working Days</th>
					<th>Non Working Days</th>
					<th>Leave Type</th>
				</tr>
			";
		while ($larr = pg_fetch_array($run_leave)){
			$total_leave_days = $total_leave_days + $larr['workingdays'];
			$listing .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$larr[startdate]</td>
						<td>$larr[enddate]</td>
						<td>$larr[approvedby]</td>
						<td>$larr[workingdays]</td>
						<td>$larr[nonworking]</td>
						<td>$larr[type]</td>
					</tr>
				";
		}

		$total_cost = ($earr['basic_sal_annum'] / (52*5)) * $total_leave_days;
	}


	$display = "
			<h2>Calculate Value Of Employee Leave</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='xls'>
				<input type='hidden' name='employee' value='$employee'>
				<tr>
					<th colspan='2'>Details</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Employee:</td>
					<td>$earr[fnames] $earr[sname]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Amount Of Annual Leave Days:</td>
					<td>$earr[stdlea]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Total Days Leave Taken</td>
					<td>$total_leave_days</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Total Cost Of Employee Leave</td>
					<td>".CUR." $total_cost</td>
				</tr>
				<tr><td><br></td></tr>
				$listing
				<tr><td><br></td></tr>
				<tr>
					<td colspan='4'><input type='submit' name='xls' value='Export to spreadsheet'></td>
				</tr>
			</form>
			</table>
		";
	return $display;

}

function show_xls ($HTTP_POST_VARS) {
    $OUT = show_report ($HTTP_POST_VARS);
    $OUT = clean_html($OUT);

    require_lib("xls");

    StreamXLS("Leave", $OUT);
}


?>