<?

	require ("settings.php");

	$OUTPUT = forward_search ();

	require ("template.php");


function forward_search ()
{

	extract ($_REQUEST);

	if (!isset($bid) OR strlen($bid) < 1){
		return "Branch information not found.";
	}

	db_connect ();

	#get branch info
	$get_branch = "SELECT * FROM branches_data WHERE id = '$bid' LIMIT 1";
	$run_branch = db_exec($get_branch) or errDie ("Unable to get branch information.");
	if (pg_numrows($run_branch) < 1){
		#no branch found ?
		return "Invalid Use Of Module.";
	}else {
		$barr = pg_fetch_array ($run_branch);
		$server = "http://$barr[branch_ip]/cubit290/branches-stock-search.php";
		$login_send = "
						<input type='hidden' name='username' value='$barr[branch_username]'>
						<input type='hidden' name='password' value='$barr[branch_password]'>
						<input type='hidden' name='company' value='$barr[branch_company]'>
					";

	}


	$display = "
					<table ".TMPL_tblDflts.">
					<form action='$server' method='POST'>
						$login_send
						<tr>
							<td>Loading Branch Search Result ...</td>
						</tr>
					</form>
					</table>
					<script>
						document.form1.submit();
					</script>
				";
	return $display;

}

?>