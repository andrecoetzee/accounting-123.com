<?

	require ("settings.php");

	if(isset($_POST["key"])){
		switch($_POST["key"]){

			case "write":
				$OUTPUT = write_loan ($_POST);
				break;
			default:
				$OUTPUT = confirm_loan ($_POST);
		}
	}else {
		$OUTPUT = confirm_loan ($_POST);
	}

	require ("template.php");



function confirm_loan ($_POST)
{

	global $_GET;
	extract ($_POST);

	if(!isset($_GET["id"]) OR (strlen($_GET["id"]) < 1)){
		return "Invalid Use Of Module. Invalid ID.";
	}

	db_connect ();

	$get_info = "SELECT * FROM loan_types WHERE id = '$_GET[id]' LIMIT 1";
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
				<input type='hidden' name='id' value='$_GET[id]'>
				<input type='hidden' name='loan_type' value='$loan_type'>
				<tr>
					<th>Loan Type</th>
				</tr>
				<tr class='".bg_class()."'>
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


function write_loan ($_POST)
{

	extract ($_POST);

	db_connect ();

	$insert_sql = "DELETE FROM loan_types WHERE id = '$id' AND loan_type = '$loan_type'";
	$run_insert = db_exec($insert_sql) or errDie("Unable to store loan type information");

	return "
			<table ".TMPL_Dflts.">
				<tr>
					<th>Information Updated</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Loan Type Has Been Removed</td>
				</tr>
			</table><br>"
			.mkQuickLinks(
				ql("loan_type_add.php", "Add Loan Type"),
				ql("loan_type_view.php", "View Loan Types")
			);


}

?>
