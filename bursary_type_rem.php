<?

	require ("settings.php");

	if(isset($HTTP_POST_VARS["key"])){
		switch ($HTTP_POST_VARS["key"]){
			case "write":
				$OUTPUT = write_burs ($HTTP_POST_VARS);
				break;
			default:
				$OUTPUT = confirm_burs ($HTTP_POST_VARS);
		}
	}else {
		$OUTPUT = confirm_burs ($HTTP_POST_VARS);
	}

	require ("template.php");


function confirm_burs ($HTTP_POST_VARS)
{

	global $HTTP_GET_VARS;
	extract ($HTTP_POST_VARS);

	if(!isset ($HTTP_GET_VARS["id"]) OR (strlen($HTTP_GET_VARS["id"]) < 1)){
		return "Invalid Use Of Module. Invalid ID.";
	}

	db_connect ();

	$get_burs = "SELECT * FROM bursaries WHERE id = '$HTTP_GET_VARS[id]' LIMIT 1";
	$run_burs = db_exec($get_burs) or errDie("Unable to get bursaries information.");
	if(pg_numrows($run_burs) < 1){
		return "Invalid use of module. Invalid bursary.";
	}else {
		$barr = pg_fetch_array($run_burs);
		$bursary_name = $barr['bursary_name'];
		$bursary_details = $barr['bursary_details'];
	}

	$display = "
			<h2>Confirm Bursary Removal</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='id' value='$HTTP_GET_VARS[id]'>
				<input type='hidden' name='bursary_name' value='$bursary_name'>
				<input type='hidden' name='bursary_details' value='$bursary_details'>
				<tr>
					<th colspan='2'>Bursary Information</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bursary Name</td>
					<td>$bursary_name</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bursary Details</td>
					<td>".nl2br($bursary_details)."</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' value='Remove'></td>
				</tr>
			</form>
			</table><br>"
			.mkQuickLinks(
				ql("bursary_type_add.php", "Add Bursary"),
				ql("bursary_type_view.php", "View Bursaries")
			);
	return $display;

}


function write_burs ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	db_connect ();

	$write_sql = "DELETE FROM bursaries WHERE id = '$id'";
	$runwrite = db_exec($write_sql) or errDie ("Unable to remove bursary information.");

	return "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Information Updated.</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bursary Has Been Removed</td>
				</tr>
			</table><br>"
			.mkQuickLinks(
				ql("bursary_type_add.php", "Add Bursary"),
				ql("bursary_type_view.php", "View Bursaries")
			);

}


?>