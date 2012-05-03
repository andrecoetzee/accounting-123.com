<?

	require ("settings.php");

	if (isset($_POST["key"])){
		switch ($_POST["key"]){
			case "confirm":
				$OUTPUT = run_rem ($_POST);
				break;
			default:
				$OUTPUT = confirm_rem($_POST);
		}
	}else {
		$OUTPUT = confirm_rem ($_GET);
	}

	require ("template.php");



function confirm_rem ($_POST)
{

	extract ($_POST);

	if (!isset($bid) or strlen($bid) < 1){
		return "Invalid Use Of Module. Invalid Branch.";
	}

	db_connect ();


	$get_branch = "SELECT * FROM branches_data WHERE id = '$bid' LIMIT 1";
	$run_branch = db_exec($get_branch) or errDie ("Unable to get branch information.");
	if (pg_numrows($run_branch) < 1){
		#branch not found ...
		$barr = array ();
	}else {
		$barr = pg_fetch_array ($run_branch);
	}

	$display = "
					<h2>Confirm Branch To Remove</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirm'>
						<input type='hidden' name='bid' value='$bid'>
						<tr>
							<th>Branch Name</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>$barr[branch_name]</td>
						</tr>
						<tr>
							<th>Branch Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>".nl2br($barr['branch_desc'])."</td>
						</tr>
						<tr>
							<th>Branch Contact</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>$barr[branch_contact]</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td align='right'><input type='submit' value='Remove'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



function run_rem ($_POST)
{

	extract ($_POST);

	if (!isset($bid) or strlen($bid) < 1){
		return "Invalid Use Of Module. Invalid Branch.";
	}

	db_connect ();

	$rem_sql = "DELETE FROM branches_data WHERE id = '$bid'";
	$run_rem = db_exec($rem_sql) or errDie ("Unable to get branches information.");


	$display = "
	
				";
	return $display;
}

?>