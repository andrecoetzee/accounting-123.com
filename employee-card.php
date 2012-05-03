<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "select":
			$OUTPUT = select();
			break;
		case "display":
			$OUTPUT = display();
			break;
	}
} else {
	$OUTPUT = select();
}

require ("template.php");

function select()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["search"] = "";

	extract ($fields, EXTR_SKIP);

	//if (empty($search)) $search = "(--EMPTY SEARCH FIELD--)";

	$sql = "
		SELECT employees.empnum, enum, sname, fnames, username 
		FROM cubit.users
			LEFT JOIN cubit.employees ON users.empnum=employees.empnum
		WHERE users.empnum!='0' AND (enum ILIKE '$search%' OR sname ILIKE '$search%' OR fnames ILIKE '$search%')";
	$emp_rslt = db_exec($sql) or errDie("Unable to retrieve employees."); 

	$emp_out = "";
	while ($emp_data = pg_fetch_array($emp_rslt)) {
		$emp_out .= "
			<tr class='".bg_class()."'>
				<td><img src='employee-view-image.php?id=$emp_data[empnum]' width='60' height='75' /></td>
				<td><img src='".getBarcode($emp_data["enum"])."' /></td>
				<td>$emp_data[sname]</td>
				<td>$emp_data[fnames]</td>
				<td>$emp_data[username]</td>
				<td><a href='".SELF."?key=display&empnum=$emp_data[empnum]'>Print</a></td>
			</tr>";
	}
	
	$OUTPUT = "
		<h3>Employee Cards</h3>
		<form method='post' action='".SELF."'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Search</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='text' name='search' value='$search' /></td>
				<td><input type='submit' value='Search' /></td>
			</tr>
		</table>
		</form>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Photo</th>
				<th>Barcode</th>
				<th>Surname</th>
				<th>First Names</th>
				<th>Username</th>
				<th>Print</th>
			</tr>
			$emp_out
		</table>";
	return $OUTPUT;

}



function display()
{

	extract ($_REQUEST);

	$sql = "SELECT empnum, sname, fnames, enum FROM cubit.employees WHERE empnum='$empnum'";
	$emp_rslt = db_exec($sql) or errDie("Unable to retrieve employee image.");
	
	$emp_out = "";
	while ($emp_data = pg_fetch_array($emp_rslt)) {

		$init_arr = explode (" ", $emp_data['fnames']);
		$initials = "";
		foreach ($init_arr AS $each) {
			$initials .= substr($each,0,1);
		}

		$emp_out = "
			<table style='border: 1px solid #000; width: 90mm; background: #ffffff;'>
				<tr>
					<td rowspan='3'><img src='employee-view-image.php?id=$emp_data[empnum]' width='120' height='150' /></td>
					<td valign='top'><img src='".getBarcode($emp_data["enum"])."' /></td>
				</tr>
				<tr>
					<td align='center'><b>$initials $emp_data[sname] $emp_data[fnames]</b></td>
				</tr>
				<tr><td></td></tr>
			</table>";
	}
	
	$OUTPUT = "$emp_out";
	
	require ("tmpl-print.php");

}


?>
