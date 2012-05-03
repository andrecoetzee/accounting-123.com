<?

require ("settings.php");

if (isset ($_POST["key"])){
	switch ($_POST["key"]){
		case "confirm":
			$OUTPUT = confirm_medical_aid_details ($_POST);
			break;
		case "write":
			$OUTPUT = write_medical_aid_details ($_POST);
			break;
		default:
			$OUTPUT = get_medical_aid_details ();
	}
}else {
	$OUTPUT = get_medical_aid_details ($_GET);
}

$OUTPUT .= "
	<br>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr class='".bg_class()."'>
			<td align='center'><a href='medical_aid_add.php'>Add Medical Aid</a></td>
		</tr>
		<tr class='".bg_class()."'>
			<td align='center'><a href='medical_aid_view.php'>View Medical Aid Options</a></td>
		</tr>
	</table>";

require ("template.php");



function get_medical_aid_details ($_GET,$err="")
{

	extract ($_GET);

	if (!isset ($mid) OR strlen ($mid) < 1){
		return "Invalid Use Of Module";
	}

	db_connect ();

	$get_med = "SELECT * FROM medical_aid WHERE id = '$mid' LIMIT 1";
	$run_med = db_exec ($get_med) or errDie ("Unable to get medical aid information.");
	if (pg_numrows ($run_med) > 0){
		$marr = pg_fetch_array ($run_med);
		extract ($marr);
	}


	$display = "
		<h4>Enter Medical Aid Information</h4>
		<form action='".SELF."' method='POST'>
			$err
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='mid' value='$mid'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Medical Aid Information</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Name</td>
				<td><input type='text' size='20' name='medical_aid_name' value='$medical_aid_name'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Contact Person</td>
				<td><input type='text' size='20' name='medical_aid_contact_person' value='$medical_aid_contact_person'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Contact Number</td>
				<td><input type='text' size='20' name='medical_aid_contact_number' value='$medical_aid_contact_number'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Bank Name</td>
				<td><input type='text' size='20' name='medical_aid_bank_name' value='$medical_aid_bank_name'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Bank Account Number</td>
				<td><input type='text' size='20' name='medical_aid_bank_account' value='$medical_aid_bank_account'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Bank Account Branch</td>
				<td><input type='text' size='20' name='medical_aid_bank_branch' value='$medical_aid_bank_branch'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' value='Confirm'></td>
			</tr>
		</table>
		</form>";
	return $display;

}



function confirm_medical_aid_details ($_POST)
{

	extract ($_POST);

	$display = "
		<h4>Confirm Medical Aid Information</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='mid' value='$mid'>
			<input type='hidden' name='medical_aid_name' value='$medical_aid_name'>
			<input type='hidden' name='medical_aid_contact_person' value='$medical_aid_contact_person'>
			<input type='hidden' name='medical_aid_contact_number' value='$medical_aid_contact_number'>
			<input type='hidden' name='medical_aid_bank_name' value='$medical_aid_bank_name'>
			<input type='hidden' name='medical_aid_bank_account' value='$medical_aid_bank_account'>
			<input type='hidden' name='medical_aid_bank_branch' value='$medical_aid_bank_branch'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Medical Aid Information</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Name</td>
				<td>$medical_aid_name</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Contact Person</td>
				<td>$medical_aid_contact_person</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Contact Number</td>
				<td>$medical_aid_contact_number</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Bank Name</td>
				<td>$medical_aid_bank_name</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Bank Account Number</td>
				<td>$medical_aid_bank_account</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Bank Account Branch</td>
				<td>$medical_aid_bank_branch</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' value='Save'></td>
			</tr>
		</table>
		</form>";
	return $display;

}



function write_medical_aid_details ($_POST)
{

	extract ($_POST);

	db_connect ();

	$ins_sql = "
		UPDATE medical_aid 
		SET 
			medical_aid_name = '$medical_aid_name', medical_aid_contact_person = '$medical_aid_contact_person', 
			medical_aid_contact_number = '$medical_aid_contact_number', medical_aid_bank_name = '$medical_aid_bank_name', 
			medical_aid_bank_account = '$medical_aid_bank_account', medical_aid_bank_branch = '$medical_aid_bank_branch' 
		WHERE id = '$mid'";
	$run_sql = db_exec ($ins_sql) or errDie ("Unable to record medical aid information.");

	return "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Medical Aid Updated</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Aid Has Been Updated.</td>
			</tr>
		</table>";

}

?>