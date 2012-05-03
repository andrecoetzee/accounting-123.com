<?

	require ("settings.php");

	if (isset($_POST["key"])){
		$OUTPUT = write_details ($_POST);
	}else {
		$OUTPUT = get_details ($_POST);
	}

	$OUTPUT .= "
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='branches-add.php'>Add Branch</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='branches-view.php'>View Branches</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";

	require ("template.php");



function get_details ($_POST,$err="")
{

	extract ($_POST);

	if (!isset($branch_name))
		$branch_name = "";
	if (!isset($branch_desc))
		$branch_desc = "";
	if (!isset($branch_contact))
		$branch_contact = "";
	if (!isset($branch_ip))
		$branch_ip = "";
	if (!isset($branch_username))
		$branch_username = "";
	if (!isset($branch_password))
		$branch_password = "";
	if (!isset($branch_passwordconfirm))
		$branch_passwordconfirm = "";
	if (!isset($branch_company))
		$branch_company = "";

	#get list of users
	$get_users = "SELECT * FROM users ORDER BY username";
	$run_users = db_exec($get_users) or errDie ("Unable to get user information.");
	if (pg_numrows($run_users) < 1){
		$users_drop = "";
	}else {
		$users_drop = "<select name='branch_localuser'>";
		while ($uarr = pg_fetch_array($run_users)){
			$users_drop .= "<option value='$uarr[userid]'>$uarr[username]</option>";
		}
		$users_drop .= "</select>";
	}

	$display = "
					<h3>Add A New Branch</h3>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						$err
						<input type='hidden' name='key' value='confirm'>
						<tr>
							<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th>Branch Name</th>
									</tr>
									<tr class='".bg_class()."'>
										<td><input type='text' size='35' name='branch_name' value='$branch_name'></td>
									</tr>
									<tr>
										<th>Branch Description</th>
									</tr>
									<tr class='".bg_class()."'>
										<td><textarea cols='30' rows='4' name='branch_desc'>$branch_desc</textarea></td>
									</tr>
									<tr>
										<th>Branch Contact</th>
									</tr>
									<tr class='".bg_class()."'>
										<td><input type='text' size='35' name='branch_contact' value='$branch_contact'></td>
									</tr>
									<tr>
										<th>Branch Company Code (Eg aaaa,aaab,etc)</th>
									</tr>
									<tr class='".bg_class()."'>
										<td><input type='text' size='4' maxlength='4' name='branch_company' value='$branch_company'></td>
									</tr>
								</table>
							</td>
							<td valign='top'>
								<table ".TMPL_tblDflts.">
									<tr>
										<th>Branch IP</th>
									</tr>
									<tr class='".bg_class()."'>
										<td><input type='text' size='35' name='branch_ip' value='$branch_ip'></td>
									</tr>
									<tr>
										<th>Branch Username</th>
									</tr>
									<tr class='".bg_class()."'>
										<td><input type='text' size='35' name='branch_username' value='$branch_username'></td>
									</tr>
									<tr>
										<th>Branch Password</th>
									</tr>
									<tr class='".bg_class()."'>
										<td><input type='password' size='35' name='branch_password' value='$branch_password'></td>
									</tr>
									<tr>
										<th>Branch Password (Confirm)</th>
									</tr>
									<tr class='".bg_class()."'>
										<td><input type='password' size='35' name='branch_passwordconfirm' value='$branch_passwordconfirm'></td>
									</tr>
									<tr>
										<th>Local User</th>
									</tr>
									<tr class='".bg_class()."'>
										<td>$users_drop</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align='right' colspan='2'><input type='submit' value='Add'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



function write_details ($_POST)
{

	extract ($_POST);



	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($branch_ip, "url", 1, 50, "Invalid Branch IP.");
	$v->isOk ($branch_company, "url", 4, 4, "Invalid Branch Company Code.");
	$v->isOk ($branch_username, "string", 1, 50, "Invalid Branch Username.");
//	$v->isOk ($branch_password, "url", 1, 50, "Invalid Branch Password.");
//	$v->isOk ($branch_passwordconfirm, "url", 1, 50, "Invalid Branch Password.");
//	$v->isOk ($branch_ip, "url", 1, 50, "Invalid Branch IP.");

	if ($branch_password != $branch_passwordconfirm){
		$v->addError($branch_password,"Passwords do not match.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		return get_details ($_POST,$confirmCust);
	}


	db_connect ();

	$add_sql = "
		INSERT INTO branches_data (
			branch_name, branch_desc, branch_contact, branch_ip, 
			date_added, last_online, branch_username, branch_password, 
			last_login_from, branch_localuser, branch_company
		) VALUES (
			'$branch_name', '$branch_desc', '$branch_contact', '$branch_ip', 
			'now', '1990-01-01', '$branch_username', md5('$branch_password'), 
			'1990-01-01', '$branch_localuser', '$branch_company'
		)";
	$run_add = pg_exec($add_sql) or errDie ("Unable to add branch information.");

	$display = "
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Branch Added</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch Has Been Added.</td>
						</tr>
					</table>
				";
	return $display;

}


?>