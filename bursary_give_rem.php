<?

	require ("settings.php");

	if(isset($_POST["key"])){
		switch ($_POST["key"]){
			case "write":
				$OUTPUT = write_details ($_POST);
				break;
			default:
				$OUTPUT = confirm_details ($_POST);
		}
	}else {
		$OUTPUT = confirm_details ($_POST);
	}

	require ("template.php");



function confirm_details ($_POST)
{

	global $_GET;
	extract ($_POST);

	if(!isset($_GET["id"])){
		return "Invalid use of module. Invalid ID.";
	}

	db_connect ();

	$get_burs = "SELECT * FROM active_bursaries WHERE id = '$_GET[id]' LIMIT 1";
	$run_burs = db_exec($get_burs) or errDie("Unable to get bursaries information.");
	if(pg_numrows($run_burs) < 1){
		return "<li class='err'>Invalid Use Of Module. Invalid Bursary Recipient.</li>";
	}

	$barr = pg_fetch_array($run_burs);
	extract ($barr);

	$get_bur = "SELECT * FROM bursaries WHERE id = '$bursary' LIMIT 1";
	$run_bur = db_exec($get_bur) or errDie("Unable to get bursary information.");
	if(pg_numrows($run_bur) < 1){
		return "<li class='err'>Invalid Use Of Module. Invalid Bursary.</li>";
	}
	$burarr = pg_fetch_array($run_bur);
	$showburs = $burarr['bursary_name'];

	$display = "
			<h2>Grant Bursary</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='id' value='$_GET[id]'>
				<input type='hidden' name='bursary' value='$bursary'>
				<tr>
					<th colspan='2'>Recipient Information</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bursary</td>
					<td>$showbursary</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Name</td>
					<td>$rec_name</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Address</td>
					<td>$rec_add1</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td></td>
					<td>$rec_add2</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td></td>
					<td>$rec_add3</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td></td>
					<td>$rec_add4</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>ID Number</td>
					<td>$rec_idnum</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Telephone</td>
					<td>$rec_telephone</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Date From</td>
					<td>$from_date</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Date To</td>
					<td>$to_date</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Notes</td>
					<td>".nl2br($notes)."</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td colspan='2' align='right'><input type='submit' value='Remove'></td>
				</tr>
			</form>
			</table><br>"
			.mkQuickLinks(
				ql("bursary_type_add.php", "Add Bursary"),
				ql("bursary_type_view.php", "View Bursaries")
			);
	return $display;
}


function write_details ($_POST)
{

	extract ($_POST);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	db_connect ();

	$insert_sql = "DELETE FROM active_bursaries WHERE id = '$id'";
	$run_insert = db_exec($insert_sql) or errDie("Unable to save bursary information.");

	$update_sql = "UPDATE bursaries SET used = 'no' WHERE id = '$bursary'";
	$run_update = db_exec($update_sql) or errDie("Unable to update bursary information.");

	return "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Bursary Information Updated.</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bursary Information Removed</td>
				</tr>
			</table>";


}


?>