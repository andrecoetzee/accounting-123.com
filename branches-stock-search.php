<?

	require ("settings.php");

	if (isset($_POST["key"])){
		switch ($_POST["key"]){
			case "search":
				$OUTPUT = search_branches ($_POST);
				break;
			default:
				$OUTPUT = get_search_details ();
		}
	}else {
		$OUTPUT = get_search_details ();
	}

	require ("template.php");



function get_search_details ($err="")
{

	$display = "
					<h3>Search Branches For Stock</h3>
					<table ".TMPL_tblDflts.">
					$err
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='search'>
						<tr>
							<th>Search Term</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='text' size='35' name='search_term'></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td><input type='submit' value='Search'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



function search_branches ($_POST)
{

	extract ($_POST);

	if (!isset($search_term) OR (strlen($search_term) < 1))
		return get_search_details("<li class='err'>Invalid Search Term</li>");

	db_connect ();

	$get_branches = "SELECT * FROM branches_data ORDER BY branch_name";
	$run_branches = db_exec($get_branches) or errDie ("Unable to get branch information.");
	if (pg_numrows($run_branches) < 1){
		#no branches found ...
		return get_search_details("<li class='err'>No Branches Found.</li>");
	}else {
		$branch_entries = "";
		while ($barr = pg_fetch_array ($run_branches)){

			#check if this branch is online first ...
			if (!($conn_test = @fsockopen($barr['branch_ip'],80,$errno,$errstr,"4"))){
				#connection failed !!
				continue;
			}else {
				#branch online ... search
				$branch_entries .= "
									<tr>
										<td colspan='2'><iframe name='iframe1' width='800' height='200' scrolling='false' marginwidth='0' marginheight='0' frameborder='1' src='branches-stock-control.php?bid=$barr[id]'></iframe></td>
									</tr>
									";
			}
		}
	}

	$display = "
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						$branch_entries
					</form>
					</table>
				";
	return $display;

}



function query_branch_stock ($branch_id="",$branch_ip="",$search_term)
{

	require_once("http://$branch_ip/cubit290/branches-stock-return.php");
print "--http://$branch_ip/cubit290/branches-stock-return.php--<br>";
	return get_stock_please ($branch_ip,$search_term);


//	$var = header ("Location: http://$branch_ip/cubit290/branches-stock-return.php");
//	return $var;

}


?>