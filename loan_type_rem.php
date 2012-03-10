<?

	require ("settings.php");

	if(isset($HTTP_POST_VARS["key"])){
		switch($HTTP_POST_VARS["key"]){

			case "write":
				$OUTPUT = write_loan ($HTTP_POST_VARS);
				break;
			default:
				$OUTPUT = confirm_loan ($HTTP_POST_VARS);
		}
	}else {
		$OUTPUT = confirm_loan ($HTTP_POST_VARS);
	}

	require ("template.php");



function confirm_loan ($HTTP_POST_VARS)
{

	global $HTTP_GET_VARS;
	extract ($HTTP_POST_VARS);

	if(!isset($HTTP_GET_VARS["id"]) OR (strlen($HTTP_GET_VARS["id"]) < 1)){
		return "Invalid Use Of Module. Invalid ID.";
	}

	db_connect ();

	$get_info = "SELECT * FROM loan_types WHERE id = '$HTTP_GET_VARS[id]' LIMIT 1";
	$run_info = db_exec($get_info) or errDie("Unable to get loan type information.");
	if(pg_numrows($run_info) < 1){
		return "Could Not Retrieve Loan Type Information. Invalid Loan Type.";
	}else {
		$larr = pg_fetch_array($run_info);
		$loan_type = $larr['loan_type'];
	}

	$display = "
			<h2>Confirm Removal Of Loan Type</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='id' value='$HTTP_GET_VARS[id]'>
				<input type='hidden' name='loan_type' value='$loan_type'>
				<tr>
					<th>Loan Type</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>$loan_type</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' value='Remove'></td>
				</tr>
			</table><br>"
			.mkQuickLinks(
				ql("loan_type_add.php", "Add Loan Type"),
				ql("loan_type_view.php", "View Loan Types")
			);
	return $display;

}


function write_loan ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	db_connect ();

	$insert_sql = "DELETE FROM loan_types WHERE id = '$id' AND loan_type = '$loan_type'";
	$run_insert = db_exec($insert_sql) or errDie("Unable to store loan type information");

	return "
			<table ".TMPL_Dflts.">
				<tr>
					<th>Information Updated</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Loan Type Has Been Removed</td>
				</tr>
			</table><br>"
			.mkQuickLinks(
				ql("loan_type_add.php", "Add Loan Type"),
				ql("loan_type_view.php", "View Loan Types")
			);


}

?>
