<?

	require ("settings.php");

	if(isset($HTTP_POST_VARS["key"])){
		switch($HTTP_POST_VARS["key"]){
			case "write":
				$OUTPUT = write_holiday ($HTTP_POST_VARS);
				break;
			default:
				$OUTPUT = confirm_holiday ($HTTP_POST_VARS);
		}
	}else {
		$OUTPUT = confirm_holiday ($HTTP_GET_VARS);
	}

	require ("template.php");


function confirm_holiday ($HTTP_POST_VARS)
{

	global $HTTP_GET_VARS;

	if(!isset($HTTP_GET_VARS["id"]) OR (strlen($HTTP_GET_VARS["id"]) < 1)){
		return "Invalid Use Of Module.";
	}

	extract ($HTTP_POST_VARS);

	db_connect ();

	$get_details = "SELECT * FROM public_holidays WHERE id = '$HTTP_GET_VARS[id]' LIMIT 1";
	$run_details = db_exec($get_details) or errDie("Unable to get public holiday information.");
	if(pg_numrows($run_details) < 1){
		return "Invalid Use Of Module. Can't find holiday information.";
	}else {
		$darr = pg_fetch_array($run_details);
		extract ($darr);
	}

	$display = "
			<h2>Confirm Removal Of Public Holiday</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='id' value='$id'>
				<tr>
					<th>Holiday Name</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>$holiday_name</td>
				</tr>
				<tr>
					<th>Holiday Type</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>$holiday_type</td>
				</tr>
				<tr>
					<th>Holiday Date</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>$holiday_date</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' value='Confirm'></td>
				</tr>
			</table>
		";
	return $display;

}


function write_holiday ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	db_connect ();

	$insert_sql = "DELETE FROM public_holidays WHERE id = '$id'";
	$run_insert = db_exec($insert_sql) or errDie("Unable to remove public holiday information");

	return "
			<table ".TMPL_Dflts.">
				<tr>
					<th>Information Updated</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Public Holiday Has Been Removed.</td>
				</tr>";


}

?>