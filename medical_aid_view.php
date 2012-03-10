<?

require ("settings.php");

$OUTPUT = view_medical_aid ();

$OUTPUT .= "
	<br>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'><a href='medical_aid_add.php'>Add Medical Aid</a></td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'><a href='medical_aid_view.php'>View Medical Aid Options</a></td>
		</tr>
	</table>";

require ("template.php");



function view_medical_aid ()
{

	db_connect ();

	$get_meds = "SELECT * FROM medical_aid ORDER BY medical_aid_name";
	$run_meds = db_exec ($get_meds) or errDie ("Unable to get medical aid information.");
	if (pg_numrows ($run_meds) < 1){
		$listing = "<tr><td>No Medical Aid Options Found.</td></tr>";
	}else {
		$listing = "";
		while ($marr = pg_fetch_array ($run_meds)){
			$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$marr[medical_aid_name]</td>
					<td>$marr[medical_aid_contact_person]</td>
					<td>$marr[medical_aid_contact_number]</td>
					<td>$marr[medical_aid_bank_name]</td>
					<td><a href='medical_aid_edit.php?mid=$marr[id]'>Edit</a></td>
					<td><a href='medical_aid_rem.php?mid=$marr[id]'>Remove</a></td>
				</tr>";
		}
	}

	$display = "
		<h4>View All Medical Aid Options</h4>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Medical Aid Name</th>
				<th>Medical Aid Contact</th>
				<th>Medical Aid Contact Number</th>
				<th>Medical Aid Bank Name</th>
				<th colspan='2'>Options</th>
			</tr>
			$listing
		</table>";
	return $display;

}


?>