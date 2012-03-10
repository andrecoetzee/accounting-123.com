<?

require ("../settings.php");

if (isset ($HTTP_POST_VARS["key"])){
	switch ($HTTP_POST_VARS["key"]){
		case "confirm":
			$OUTPUT = run_import_file ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = get_import_file();
	}
}else {
	$OUTPUT = get_import_file ();
}

$OUTPUT .= "
	<br>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'><a href='import-commission.php'>Import Employees Commission</a></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'><a href='../salwages/salaries-staff.php'>Process Salaries</a></td>
		</tr>
	</table>";

require ("../template.php");



function get_import_file ($err="")
{

	$display = "
		<h4>Import Employee Commissions</h4>
		<form action='".SELF."' method='POST' enctype='multipart/form-data'>
			<input type='hidden' name='key' value='confirm'>
			$err
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Import File</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='file' name='import_file'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><li class='err'>Import File Needs To Be In The Following Format. (Seperated By Commas)</li></td>
			</tr>
			<tr>
				<td><li class='err'>Employee Number,Commission Amount</li></td>
			</tr>
			<tr>
				<td><li class='err'>Eg. <br>
					1,100<br>
					2,120<br>
					3,80
				</li></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='submit' value='Import'></td>
			</tr>
		</table>
		</form>";
	return $display;

}


function run_import_file ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if (!isset ($_FILES["import_file"])) {
		return get_import_file ("<li class='err'>Please Choose A Valid File To Import</li>");
	}

	db_connect ();

	$file_data = file ($_FILES["import_file"]["tmp_name"]);

	foreach ($file_data AS $line){
		$cleanline = trim ($line);
		$line_arr = explode (",",$cleanline);
		$enum = $line_arr[0] + 0;
		$comm = $line_arr[1] + 0;

		$upd_emp = "UPDATE employees set commission = '$comm' WHERE enum = '$enum'";
		$run_emp = db_exec ($upd_emp) or errDie ("Unable to update employee commission information.");

	}

	return "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Commissions Imported</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Commissions Have Been Imported</td>
			</tr>
		</table>";

}

?>
