<?

	require ("settings.php");

	if (isset($_POST["key"])){
		$OUTPUT = write_remove ($_POST);
	}else {
		$OUTPUT = confirm_email ($_GET);
	}

	require ("template.php");



function confirm_email ($_GET)
{

	extract ($_GET);

	if (!isset($email) OR strlen($email) < 1){
		return "Invalid Use Of Module. Invalid Email Address.";
	}


	db_connect ();

	#verify if this is a valid email adress
	$get_check = "SELECT * FROM cubit.email_groups WHERE emailaddress = '$email' LIMIT 1";
	$run_check = db_exec($get_check) or errDie ("Unable to get email address information.");
	if (pg_numrows($run_check) < 1){
		#email address not found ??
		return "Email Address Not Found In List.";
	}

	$display = "
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirm'>
						<input type='hidden' name='email' value='$email'>
						<tr>
							<th>Confirm Removal Of This Email Address From Email Groups</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>$email</td>
						</tr>
						".TBL_BR."
						<tr>
							<td align='right'><input type='submit' value='Confirm Removal'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



function write_remove ($_POST)
{

	extract ($_POST);

	if (!isset($email))
		return "Invalid Use Of Email.";

	#remove the address

	db_connect ();

	$rem_sql = "DELETE FROM cubit.email_groups WHERE emailaddress = '$email'";
	$run_rem = db_exec($rem_sql) or errDie ("Unable to remove email address.");

	#add this to removed list
	$ins_sql = "INSERT INTO cubit.removed_list (emailaddress, date_removed) VALUES ('$email','now')";
	$run_ins = db_exec ($ins_sql) or errDie ("Unable to remove address");

	$display = "
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Address Removed</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Email Address Has Beeen Removed</td>
						</tr>
					</table>
			";
	return $display;

}



?>