<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "logs":
			$OUTPUT = display_logs();
			break;
		case "times":
			$OUTPUT = display_totals();
			break;
		case "confirm":
			if (isset ($_REQUEST["update"])){
				$OUTPUT = update_logs ();
			}else {
				$OUTPUT = display_logs ();
			}
			break;
	}
} else {
	$OUTPUT = display_logs();
}

require ("../template.php");



function display_logs($err="")
{

	extract ($_REQUEST);

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["employee"] = "";
	
	extract ($fields, EXTR_SKIP);
	
	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

/*
	// ORIGINAL
	$sql = "
		SELECT id, username AS fnames, '',  extract('epoch' FROM in_time) AS e_in, extract('epoch' FROM out_time) AS e_out
		FROM cubit.emp_times
			LEFT JOIN cubit.users ON emp_times.user_id=users.userid
		WHERE (in_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59'
			OR out_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59')
			AND (user_id ILIKE '%$employee%' OR username ILIKE '%$employee%')
		ORDER BY out_time DESC";
*/
	// CUBIT ?
	$sql = "
		SELECT id, empnum, '', extract('epoch' FROM in_time) AS e_in, extract('epoch' FROM out_time) AS e_out
		FROM cubit.emp_times 
			LEFT JOIN cubit.users ON emp_times.user_id=users.userid
		WHERE (in_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59'
			OR out_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59')
			AND (user_id ILIKE '%$employee%' OR username ILIKE '%$employee%')
		ORDER BY out_time DESC";


/*
	// MANUFACTURING ? (cubit3.4 released too)
	$sql = "
		SELECT id, fnames, sname, extract('epoch' FROM in_time) AS e_in, extract('epoch' FROM out_time) AS e_out
		FROM cubit.emp_times
			LEFT JOIN cubit.employees ON emp_times.user_id=employees.enum
		WHERE (in_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59'
			OR out_time BETWEEN '$from_date 0:00:00' AND '$to_date 23:59:59')
			AND (user_id ILIKE '%$employee%' OR fnames ILIKE '%$employee%' OR sname ILIKE '%$employee%')
		ORDER BY out_time DESC";
*/
	$times_rslt = db_exec($sql) or errDie("Unable to retrieve attendance times.");

	$times_out = "";
	while (list($id, $fnames, $sname, $e_in, $e_out) = pg_fetch_array($times_rslt)) {

		// CUBIT ?
		$fnames += 0;
		$get_emp = "SELECT fnames,sname FROM employees WHERE empnum = '$fnames' LIMIT 1";
		$run_emp = db_exec ($get_emp) or errDie ("Unable to get employee information.");
		$fnames = pg_fetch_result ($run_emp,0,0);
		$sname = pg_fetch_result ($run_emp,0,1);

		if (empty($e_out)) {
			$out_time = "";
			$total_time = "";
		} else {
			$out_time = date("Y-m-d G:i:s", $e_out);
			
			$total_minutes = round(($e_out - $e_in) / 60);
			if ($total_minutes < 60) {
				$total_time = $total_minutes . " Minutes";
			} else {
				$total_hours = floor($total_minutes / 60);
				$total_minutes = $total_minutes - (60 * $total_hours);
				$total_time = "$total_hours Hours $total_minutes Minutes";
			}
		}

		if (user_is_admin(USER_ID)){
			if (!isset ($e_out) OR strlen ($e_out) < 1){
				$outtime = "";
			}else {
				$outtime = date("Y-m-d G:i:s", $e_out);
			}
			$display_in_date = "<input type='text' name='indate[$id]' value='".date("Y-m-d G:i:s", $e_in)."'>";
			$display_out_date = "<input type='text' name='outdate[$id]' value='$outtime'>";
		}else {
			$display_in_date = date("Y-m-d G:i:s", $e_in);
			$display_out_date = date("Y-m-d G:i:s", $e_out);
		}

		$times_out .= "
			<tr class='".bg_class()."'>
				<td>$fnames $sname</td>
				<td>$display_in_date</td>
				<td>$display_out_date</td>
				<td>$total_time</td>
			</tr>";
	}

	if (empty($items_out)) {
		$items_out = "
			<tr class='".bg_class()."'>
				<td colspan='4'><li>No results found</li></td>
			</tr>";
	}

	$OUTPUT = "
		<center>
		<h3>Employee Time and Attendance Log Report</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='confirm'>
			$err
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='4'>Employee</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='4' align='center'>
					<input type='text' name='employee' value='$employee' />
					<input type='submit' value='Select' />
				</td>
			</tr>
			<tr><th colspan='4'>Date Range</th></tr>
			<tr class='".bg_class()."'>
				<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
				<td>&nbsp; <b>To</b> &nbsp;</td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
				<td><input type='submit' value='Select' style='font-weight: bold' /></td>
			</tr>
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Employee</th>
				<th>Booked In</th>
				<th>Booked Out</th>
				<th>Total Time</th>
			</tr>
			$times_out
			".TBL_BR."
			<tr>
				<td colspan='4' align='center'><input type='submit' name='update' value='Update'></td>
			</tr>
		</table>
		</form>
		</center>";
	return $OUTPUT;

}



function display_totals()
{
	extract ($_REQUEST);
	
	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	
	extract ($fields, EXTR_SKIP);

	$sql = "SELECT userid, username FROM cubit.users";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve users.");

	$times_out = "";
	while (list($user_id, $username) = pg_fetch_array($user_rslt)) {
		$total_secs = 0;
		$sql = "
			SELECT extract('epoch' FROM in_time) AS e_in,
				extract('epoch' FROM out_time) AS e_out
			FROM cubit.emp_times
			WHERE user_id='$user_id'";
		$times_rslt = db_exec($sql) or errDie("Unable to retrieve times.");
		
		while (list($e_in, $e_out) = pg_fetch_array($times_rslt)) {
			if (empty($e_out)) $e_out = time();
			$total_secs += $e_out - $e_in;
		}
		$total_time = round($total_secs / 60 / 60);

		$times_out .= "
			<tr class='".bg_class()."'>
				<td>$username</td>
				<td align='center'>$total_time Hours</td>
			</tr>";
	}
	
	$OUTPUT = "
		<center>
		<h3>Employee Time and Attendance Total Time Report</h3>
		<form method='POST' action='".SELF."'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='4'>Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
				<td>&nbsp; <b>To</b> &nbsp;</td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
				<td><input type='submit' value='Select' style='font-weight: bold' /></td>
			</tr>
		</table>
		</form>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Employee</th>
				<th>Total Hours Worked</th>
			</tr>
			$times_out
		</table>";
	return $OUTPUT;

}


function update_logs ()
{

	extract ($_REQUEST);

	db_connect ();

	foreach ($indate AS $id => $invalue){

		if (strlen ($invalue) != 18 AND strlen ($invalue) != 19){
			return display_logs("<li class='err'>Please Ensure Correct Format is Used For Book In ($invalue)</li><br>");
		}

		if (strlen ($outdate[$id]) != 18 AND strlen ($outdate[$id]) != 19){
			return display_logs("<li class='err'>Please Ensure Correct Format is Used For Book Out ($outdate[$id])</li><br>");
		}

		$upd_sql = "UPDATE emp_times SET in_time = '$invalue', out_time = '$outdate[$id]' WHERE id = '$id'";
		$run_sql = db_exec ($upd_sql) or errDie ("Unable to update employee attendance information");

	}

	return display_logs ();

}


?>
