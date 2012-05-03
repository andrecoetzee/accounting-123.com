<?

	require ("settings.php");

	if(isset($_POST["key"])){
		switch ($_POST["key"]){
			case "write":
				$OUTPUT = write_burs ($_POST);
				break;
			default:
				$OUTPUT = confirm_burs ($_POST);
		}
	}else {
		$OUTPUT = confirm_burs ($_POST);
	}

	require ("template.php");


function confirm_burs ($_POST)
{

	global $_GET;
	extract ($_POST);

	if(!isset ($_GET["id"]) OR (strlen($_GET["id"]) < 1)){
		return "Invalid Use Of Module. Invalid ID.";
	}

	db_connect ();

	$get_burs = "SELECT * FROM bursaries WHERE id = '$_GET[id]' LIMIT 1";
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
				<input type='hidden' name='id' value='$_GET[id]'>
				<input type='hidden' name='bursary_name' value='$bursary_name'>
				<input type='hidden' name='bursary_details' value='$bursary_details'>
				<tr>
					<th colspan='2'>Bursary Information</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Bursary Name</td>
					<td>$bursary_name</td>
				</tr>
				<tr class='".bg_class()."'>
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


function write_burs ($_POST)
{

	extract ($_POST);

	db_connect ();

	$write_sql = "DELETE FROM bursaries WHERE id = '$id'";
	$runwrite = db_exec($write_sql) or errDie ("Unable to remove bursary information.");

	return "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Information Updated.</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Bursary Has Been Removed</td>
				</tr>
			</table><br>"
			.mkQuickLinks(
				ql("bursary_type_add.php", "Add Bursary"),
				ql("bursary_type_view.php", "View Bursaries")
			);

}


?>