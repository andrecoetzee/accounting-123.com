<?

require ("settings.php");

$OUTPUT = get_filters ($_POST);

require ("template.php");



function get_filters ($_POST)
{

	extract ($_POST);

	if (isset ($search) AND strlen ($search) > 0){

		$from_date = "$from_year-$from_month-$from_day";
		$to_date = "$to_year-$to_month-$to_day";

		$view_report = "
			<tr>
				<th>Employee Name</th>
				<th>Hire Date</th>
				<th>Position</th>
				<th>Options</th>
			</tr>";

		$get_emps = "SELECT * FROM employees WHERE hiredate >= '$from_date' AND hiredate <= '$to_date' ORDER BY enum, sname";
		$run_emps = db_exec ($get_emps) or errDie ("Unable to get employee number.");
		if (pg_numrows ($run_emps) < 1){
			$view_report = "
				<tr class='".bg_class()."'>
					<td colspan='4'>No Employees Hired Within Selected Dates.</td>
				</tr>";
		}else {
			while ($arr = pg_fetch_array ($run_emps)){
				$view_report .= "
					<tr class='".bg_class()."'>
						<td>$arr[sname], $arr[fnames]</td>
						<td>$arr[hiredate]</td>
						<td>$arr[designation]</td>
						<td><a target='_blank' href='admin-employee-edit.php?empnum=$arr[empnum]'>Edit</a></td>
					</tr>";
			}
		}
	}else {
		$view_report = "";
	}

	$display = "
		<h4>Employee Appointment Report</h4>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Date Range</th>
			</tr>
			<tr>
				<th>From</th>
				<th>To</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>".mkDateSelect("from",date("Y"),date("m"),"01")."</td>
				<td>".mkDateSelect("to",date("Y"),date("m"),date("d"))."</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' name='search' value='View'></td>
			</tr>
			<tr><td><br></td></tr>
			$view_report
		</table>
		</form>";
	return $display;

}


?>