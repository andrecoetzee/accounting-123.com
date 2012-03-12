<?

	require ("settings.php");

	if(isset($_POST["key"])){
		switch ($_POST["key"]){
			case "confirm":
				$OUTPUT = confirm_burs ($_POST);
				break;
			case "write":
				$OUTPUT = write_burs ($_POST);
				break;
			default:
				$OUTPUT = get_burs ();
		}
	}else {
		$OUTPUT = get_burs ();
	}

	require ("template.php");


function get_burs ()
{

	$display = "
			<h2>Add New Bursary</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<tr>
					<th colspan='2'>Bursary Information</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bursary Name</td>
					<td><input type='text' size='40' name='bursary_name'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bursary Details</td>
					<td><textarea name='bursary_details' rows='6' cols='50'></textarea></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td colspan='2' align='right'><input type='submit' value='Next'></td>
				</tr>
			</form>
			</table><br>"
			.mkQuickLinks(
				ql("bursary_type_add.php", "Add Bursary"),
				ql("bursary_type_view.php", "View Bursaries")
			);
	return $display;

}


function confirm_burs ($_POST)
{

	extract ($_POST);

	$display = "
			<h2>Confirm New Bursary</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
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
					<td colspan='2' align='right'><input type='submit' value='Confirm'></td>
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

	$write_sql = "INSERT INTO bursaries (bursary_name,bursary_details,date_added,used) VALUES ('$bursary_name','$bursary_details','now','no')";
	$runwrite = db_exec($write_sql) or errDie ("Unable to add bursary information.");

	return "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Information Updated.</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bursary Has Been Added</td>
				</tr>
			</table><br>"
			.mkQuickLinks(
				ql("bursary_type_add.php", "Add Bursary"),
				ql("bursary_type_view.php", "View Bursaries")
			);

}


?>