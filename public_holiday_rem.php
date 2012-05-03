<?

	require ("settings.php");

	if(isset($_POST["key"])){
		switch($_POST["key"]){
			case "write":
				$OUTPUT = write_holiday ($_POST);
				break;
			default:
				$OUTPUT = confirm_holiday ($_POST);
		}
	}else {
		$OUTPUT = confirm_holiday ($_GET);
	}

	require ("template.php");


function confirm_holiday ($_POST)
{

	global $_GET;

	if(!isset($_GET["id"]) OR (strlen($_GET["id"]) < 1)){
		return "Invalid Use Of Module.";
	}

	extract ($_POST);

	db_connect ();

	$get_details = "SELECT * FROM public_holidays WHERE id = '$_GET[id]' LIMIT 1";
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
				<tr class='".bg_class()."'>
					<td>$holiday_name</td>
				</tr>
				<tr>
					<th>Holiday Type</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>$holiday_type</td>
				</tr>
				<tr>
					<th>Holiday Date</th>
				</tr>
				<tr class='".bg_class()."'>
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


function write_holiday ($_POST)
{

	extract ($_POST);

	db_connect ();

	$insert_sql = "DELETE FROM public_holidays WHERE id = '$id'";
	$run_insert = db_exec($insert_sql) or errDie("Unable to remove public holiday information");

	return "
			<table ".TMPL_Dflts.">
				<tr>
					<th>Information Updated</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Public Holiday Has Been Removed.</td>
				</tr>";


}

?>