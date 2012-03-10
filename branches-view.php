<?

	require ("settings.php");

	if (isset($HTTP_POST_VARS["key"])){
		$OUTPUT = show_branches ($active_search=TRUE);
	}else {
		$OUTPUT = show_branches ();
	}

	$OUTPUT .= "
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='branches-add.php'>Add Branch</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='branches-view.php'>View Branches</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";

	require ("template.php");



function show_branches ($active_search=FALSE)
{

	db_connect ();

	$get_branches = "SELECT * FROM branches_data ORDER BY branch_name";
	$run_branches = db_exec($get_branches) or errDie ("Unable to get branch information.");
	if (pg_numrows($run_branches) < 1){
		$listing = "
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='7'>No Branches Found.</td>
						</tr>
					";
	}else {
		$listing = "";
		while ($barr = pg_fetch_array ($run_branches)){

			if ($active_search == FALSE){
				$status = "Unknown";
			}else {

				if ($connect_test = @fsockopen("$barr[branch_ip]", 80, $errno, $errstr, 4)){
					#online ...
					$status = "Online";
				}else {
					$status = "Offline";
				}


			}


			$get_username = "SELECT username FROM users WHERE userid = '$barr[branch_localuser]' LIMIT 1";
			$run_username = db_exec($get_username) or errDie ("Unable to get user information.");
			if (pg_numrows($run_username) < 1){
				
			}

			$listing .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$barr[branch_name]</td>
								<td>".nl2br($barr['branch_desc'])."</td>
								<td>$barr[branch_username]</td>
								<td>$barr[branch_contact]</td>
								<td></td>
								<td>$barr[branch_ip]</td>
								<td>$status</td>
								<td><a href='branches-rem.php?bid=$barr[id]'>Remove</a></td>
							</tr>
						";
		}
	}


	$display = "
					<h3>Current Branches on Cubit</h3>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirm'>
						<tr>
							<th>Branch Name</th>
							<th>Branch Description</th>
							<th>Branch Username</th>
							<th>Branch Contact</th>
							<th>Local Username</th>
							<th>Branch IP</th>
							<th>Status</th>
							<th>Remove</th>
						</tr>
						$listing
						<tr><td><br></td></tr>
						<tr>
							<td><input type='submit' value='Update Status'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}


?>