<?

require ("settings.php");

$OUTPUT = get_report_parms ($_POST);

require ("template.php");



function get_report_parms ($_POST, $err="")
{

	extract ($_POST);

	db_connect ();

	$view_report = "";

	if (isset ($search) AND strlen ($search) > 0){
		if (!isset ($medical_aid) OR !is_array ($medical_aid)){
			unset ($_POST["search"]);
			return get_report_parms($_POST,"<li class='err'>Please Select At Least One Medical Aid.</li>");
		}

		foreach ($medical_aid AS $mid){
			$med_total = 0;
			$get_med = "SELECT * FROM medical_aid WHERE id = '$mid' LIMIT 1";
			$run_med = db_exec ($get_med) or errDie ("Unable to get medical aid information.");
			$marr = pg_fetch_array ($run_med);

			$view_report .= "
				<tr>
					<th colspan='3'>$marr[medical_aid_name]</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Contact</td>
					<td colspan='2'>$marr[medical_aid_contact_number]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bank Name</td>
					<td colspan='2'>$marr[medical_aid_bank_name]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bank Branch</td>
					<td colspan='2'>$marr[medical_aid_bank_branch]</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bank Account</td>
					<td colspan='2'>$marr[medical_aid_bank_account]</td>
				</tr>
				<tr>
					<th>Employee Name</th>
					<th>Medical Aid Number</th>
					<th>Contribution Amount</th>
				</tr>";

			#get all emps using this medical aid
			$get_emps = "SELECT * FROM employees WHERE medical_aid = '$mid' ORDER BY sname, fnames";
			$run_emps = db_exec ($get_emps) or errDie ("Unable to get employee information.");
			if (pg_numrows ($run_emps) > 0){
				while ($earr = pg_fetch_array ($run_emps)){
					$earr['comp_medical'] += 0;
					$view_report .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$earr[sname], $earr[fnames]</td>
							<td>$earr[medical_aid_number]</td>
							<td align='right'>".CUR." ".sprint ($earr['comp_medical'])."</td>
						</tr>";
					$med_total += $earr['comp_medical'];
				}
				$view_report .= "
					<tr bgcolor='".bgcolorg()."'>
						<th colspan='2'>Total</th>
						<td align='right'>".CUR." ".sprint ($med_total)."</td>
					</tr>";
			}else {
				$view_report .= "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='3'>No Employees Using This Medical Aid Option.</td>
					</tr>";
			}
			$view_report .= "<tr><td><br></td></tr>";
		}
	}

	$get_meds = "SELECT * FROM medical_aid ORDER BY medical_aid_name";
	$run_meds = db_exec ($get_meds) or errDie ("Unable to get medical aid information.");
	if (pg_numrows ($run_meds) > 0){
		$medical_aid_drop = "<select name='medical_aid[]' multiple size='5'>";
		while ($marr = pg_fetch_array ($run_meds)){
			$medical_aid_drop .= "<option value='$marr[id]'>$marr[medical_aid_name]</option>";
		}
		$medical_aid_drop .= "</select>";
	}else {
		$medical_aid_drop = "No Medical Aid Options Found.";
	}

	$display = "
		<h3>Medical Aid Report</h3>
		$err
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Medical Aid Options</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$medical_aid_drop</td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='right'><input type='submit' name='search' value='Search'></td>
			</tr>
			".TBL_BR."
			$view_report
		</table>
		</form>";
	return $display;

}



?>