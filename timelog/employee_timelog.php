<?php

require ("../settings.php");

$OUTPUT = display();

$OUTPUT .= mkQuickLinks(
	ql("employee_timelog_report.php?key=logs", "Time and Attendance Log"),
	ql("employee_timelog_report.php?key=times", "Time And Attendance Total Times")
);

require ("../template.php");



function display($OUTPUT="", $user_id=0)
{

	$flashes = array("user_id"=>"Scan Employee");
	list($user_id) = array_values(flashRed($flashes, $OUTPUT));

	if (!is_numeric($user_id)) {
		header("Location: ".SELF);
	} else {

		//new
		$get_emp = "SELECT empnum FROM employees WHERE enum = '$user_id' LIMIT 1";
		$run_emp = db_exec ($get_emp) or errDie ("Unable to get employee information.");
		if (pg_numrows ($run_emp) > 0){
			$empnum = pg_fetch_result ($run_emp,0,0);

			$get_userid = "SELECT userid FROM users WHERE empnum = '$empnum' LIMIT 1";
			$run_userid = db_exec ($get_userid) or errDie ("Unable to get user information.");
			if (pg_numrows ($run_userid) > 0){
				$user_id = pg_fetch_result ($run_userid,0,0);
			}else {
				header("Location: ".SELF);
			}
		}else {
			header("Location: ".SELF);
		}

		$sql = "SELECT userid,empnum FROM cubit.users WHERE userid='$user_id'";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve user.");

		if (!pg_num_rows($user_rslt)) {
			header("Location: ".SELF);
		}

		$user_arr = pg_fetch_array ($user_rslt);

		$sql = "SELECT empnum, enum, sname, fnames FROM cubit.employees WHERE empnum = '$user_arr[empnum]' LIMIT 1";
		$emp_rslt = db_exec($sql) or errDie("Unable to retrieve employees."); 
		$emp_data = pg_fetch_array($emp_rslt);
	}

	$attendance = do_attendance($user_id);
	if ($attendance) {
		list($book_type, $time) = array_values($attendance);
	} else {
		unset ($_POST);
		display();
	}

	define("TIMEOUT", 10);

	$OUTPUT = "
		<center>
		<script>
			setTimeout('Redirect()', ".(TIMEOUT * 1000).");
			function Redirect()
			{
				location.href = '".SELF."';
			}
		</script>
		<h3>Employee Time and Attendance</h3>
		<table ".TMPL_tblDflts.">
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><li class='err'>Next scan in ".TIMEOUT." seconds...</li></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td style='font-size: 1.2em'>$book_type: $time</td>
			</tr>
		</table>
		<br>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='5'>Employee</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><img src='../employee-view-image.php?id=$emp_data[empnum]' width='60' height='75' /></td>
				<td>$emp_data[sname]</td>
				<td>$emp_data[fnames]</td>
				<td>$emp_data[username]</td>
			</tr>
		</table>
		</center>";
	return $OUTPUT;

}



function do_attendance($user_id)
{

	$check_sql = "SELECT max(in_time) AS in_time, max(out_time) AS out_time FROM cubit.emp_times WHERE user_id = '$user_id'";
	$run_check = db_exec ($check_sql) or errDie ("Unable to check employee log times");
	if (pg_numrows ($run_check) > 0){
		$charr = pg_fetch_array ($run_check);
		#found times
		if (
			strtotime($charr['in_time']) >= mktime (date("G"), date("i")-5, date("s"), date("m"), date("d"), date("Y")) 
			OR 
			strtotime($charr['out_time']) >= mktime (date("G"), date("i")-5, date("s"), date("m"), date("d"), date("Y"))
		){
			return FALSE;
		}
	}

	$sql = "SELECT in_out FROM cubit.emp_attendance WHERE user_id='$user_id'";
	$attendance_rslt = db_exec($sql) or errDie("Unable to retrieve attendance.");
	$attendance = pg_fetch_result($attendance_rslt, 0);

	if (!pg_num_rows($attendance_rslt)) {

		$sql = "INSERT INTO cubit.emp_attendance (user_id, in_out) VALUES ('$user_id', 1)";
		db_exec($sql) or errDie("Unable to record attendance.");

		$sql = "INSERT INTO cubit.emp_times (user_id, in_time) VALUES ('$user_id', current_timestamp)";
		db_exec($sql) or errDie("Unable to record attendance time.");

		$book_type = "Booked <b>IN</b>";

	} elseif ($attendance == 1) {

		# check if this employee is currently running a job
		/* MANUFACTURING ONLY
		$get_check = "SELECT * FROM manufact.wip WHERE user_id = '$user_id' AND (length (end_time) < 1 OR end_time IS NULL)";
		$run_check = db_exec ($get_check) or errDie ("Unable to get user information.");
		if (pg_numrows ($run_check) > 0){
			return array("User Has A Job Currently Running.", date("D d M Y G:i:s"));
		}
		*/
		$sql = "UPDATE cubit.emp_attendance SET in_out=0 WHERE user_id='$user_id'";
		db_exec($sql) or errDie("Unable to record attendance.");

		$sql = "SELECT max(id) FROM cubit.emp_times WHERE user_id='$user_id'";
		$id_rslt = db_exec($sql) or errDie("Unable to retrieve attendance id.");
		$id = pg_fetch_result($id_rslt, 0);

		$sql = "UPDATE cubit.emp_times SET out_time=current_timestamp WHERE user_id='$user_id' AND id='$id'";
		db_exec($sql) or errDie("Unable to record attendance time.");

		$book_type = "Booked <b>OUT</b>";
	} elseif ($attendance == 0) {
		$sql = "UPDATE cubit.emp_attendance SET in_out=1 WHERE user_id='$user_id'";
		db_exec($sql) or errDie("Unable to record attendance.");

		$sql = "INSERT INTO cubit.emp_times (user_id, in_time) VALUES ('$user_id', current_timestamp)";
		db_exec($sql) or errDie("Unable to record attendance time.");

		$book_type = "Booked <b>IN</b>";
	}

	$time = date("D d M Y G:i:s");
	$return = array($book_type, $time);
	return $return;

}


?>
