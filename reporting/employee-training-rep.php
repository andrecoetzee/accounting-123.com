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
# admin-employee-rem.php :: Remove employees from db
##

require ("../settings.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "confirm":
			$OUTPUT = show_training();
			break;
		default:
			$OUTPUT = "Invalid use.";
	}
} else {
	$OUTPUT = get_emps ();
}

require ("../template.php");



##
# Functions
##

function get_emps ()
{

	db_connect ();

	$get_emplist = "SELECT * FROM employees WHERE div = '".USER_DIV."'";
	$run_emplist = db_exec($get_emplist);

	if(pg_numrows($run_emplist) < 1){
		return "No employees have been added yet";
	}else {
		$listing = "<select multiple name='emps[]'>";
		$listing .= "<option value='all'>All</option>";
		while ($arr = pg_fetch_array($run_emplist)){
			$listing .= "<option value='$arr[empnum]'>$arr[fnames] $arr[sname]</option>";
		}
		$listing .= "</select>";
	}

	$display = "
		<h2>Select Employee(s)</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>Employees</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$listing</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' value='Next'></td>
			</tr>
		</form>
		</table>";
	return $display;

}


function show_training ()
{

	global $_POST;
	extract ($_POST);

	if(!isset($emps) OR (sizeof($emps) < 1)){
		return get_emps ();
	}

	$listing = "";
	db_connect ();

	foreach ($emps as $each){
		if ($each == "all"){
			$get_emp = "SELECT * FROM employees WHERE div = '".USER_DIV."'";
			$run_emp = db_exec($get_emp);
			if(pg_numrows($run_emp) < 1){
				$listing = "<tr><td>No employees found.</td></tr>";
			}else {
				$listing = "";
				while ($earr = pg_fetch_array($run_emp)){

					$listing .= "<tr><td><font size='4' style='color:white'>$earr[sname], $earr[fnames]</font></tr></tr>";

					#get training for this employee
					$get_train = "SELECT * FROM training WHERE empnum = '$earr[empnum]'";
					$run_train = db_exec($get_train);
					if(pg_numrows($run_train) > 0){
						$i = 0;
						$listing .= "
							<tr>
								<th>Course</th>
								<th>Star Date</th>
								<th>End Date</th>
								<th>Assessor Name</th>
								<th>Training Cost</th>
								<th>Other Details</th>
							</tr>";
						while ($tarr = pg_fetch_array($run_train)){
							$listing .= "
								<tr class='".bg_class()."'>
									<td>$tarr[course_name]</td>
									<td>$tarr[commence_date]</td>
									<td>$tarr[completed_date]</td>
									<td>$tarr[assessor_name]</td>
									<td>$tarr[training_cost]</td>
									<td>".nl2br($tarr['other_details'])."</td>
								</tr>";
							$i++;
						}
						$listing .= "<tr><td><br></td></tr>";
					}else {
						$listing .= "
							<tr class='".bg_class()."'>
								<td>This employee has no training courses completed</td>
							</tr>";
					}

					$listing .= "<tr><td><br></td></td>";

				}
			}
			break;
		}else {
			$get_emp = "SELECT * FROM employees WHERE empnum = '$each' LIMIT 1";
			$run_emp = db_exec($get_emp);

			if(pg_numrows($run_emp) < 1){
				$listing .= "<tr><td>Employee not found</td></tr>";
			}else {
				$earr = pg_fetch_array($run_emp);
				$listing .= "<tr><td><font size='4' style='color:white'>$earr[sname], $earr[fnames]</font></td></tr>";

				#get training
				$get_train = "SELECT * FROM training WHERE empnum = '$each'";
				$run_train = db_exec($get_train);
				if(pg_numrows($run_train) > 0){
					$i = 0;
					$listing .= "
						<tr>
							<th>Course</th>
							<th>Star Date</th>
							<th>End Date</th>
							<th>Assessor Name</th>
							<th>Training Cost</th>
							<th>Other Details</th>
						</tr>";
					while ($tarr = pg_fetch_array($run_train)){
						$listing .= "
								<tr class='".bg_class()."'>
									<td>$tarr[course_name]</td>
									<td>$tarr[commence_date]</td>
									<td>$tarr[completed_date]</td>
									<td>$tarr[assessor_name]</td>
									<td>$tarr[training_cost]</td>
									<td>".nl2br($tarr['other_details'])."</td>
								</tr>
							";
						$i++;
					}
					$listing .= "<tr><td><br></td></tr>";
				}else {
					$listing .= "
						<tr class='".bg_class()."'>
							<td>This employee has no training courses completed</td>
						</tr>";
				}
				$listing .= "<tr><td><br></td></td>";
			}
		}
	}


	$display = "
		<h2>Staff Training Report</h2>
		<table ".TMPL_tblDflts.">
			$listing
		</table>";
	return $display;

}



?>