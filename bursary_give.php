<?

	require ("settings.php");

	if(isset($HTTP_POST_VARS["key"])){
		switch ($HTTP_POST_VARS["key"]){
			case "confirm":
				$OUTPUT = confirm_details ($HTTP_POST_VARS);
				break;
			case "write":
				$OUTPUT = write_details ($HTTP_POST_VARS);
				break;
			default:
				$OUTPUT = get_details ($HTTP_POST_VARS);
		}
	}else {
		$OUTPUT = get_details ($HTTP_POST_VARS);
	}

	require ("template.php");


function get_details ($HTTP_POST_VARS,$err = "")
{

	extract ($HTTP_POST_VARS);

	#handle the vars
	if(!isset($bursary))
		$bursary = "";
	if(!isset($rec_name))
		$rec_name = "";
	if(!isset($rec_add1))
		$rec_add1 = "";
	if(!isset($rec_add2))
		$rec_add2 = "";
	if(!isset($rec_add3))
		$rec_add3 = "";
	if(!isset($rec_add4))
		$rec_add4 = "";
	if(!isset($rec_idnum))
		$rec_idnum = "";
	if(!isset($rec_telephone))
		$rec_telephone = "";
	if(!isset($from_year))
		$from_year = "";
	if(!isset($from_month))
		$from_month = "";
	if(!isset($from_day))
		$from_day = "";
	if(!isset($to_year))
		$to_year = "";
	if(!isset($to_month))
		$to_month = "";
	if(!isset($to_day))
		$to_day = "";
	if(!isset($notes))
		$notes = "";


	db_connect ();

	$get_burs = "SELECT * FROM bursaries WHERE used = 'no' ORDER BY bursary_name";
	$run_burs = db_exec($get_burs) or errDie("Unable to get bursary information.");
	if(pg_numrows($run_burs) < 1){
		return "<li class='err'>No Bursaries Found. Please Add At Least One.</li>";
	}else {
		$bursary_drop = "<select name='bursary'>";
		$bursary_drop .= "<option value='0'>Select Bursary</option>";
		while ($barr = pg_fetch_array($run_burs)){
			if($barr['id'] == $bursary){
				$bursary_drop .= "<option selected value='$barr[id]'>$barr[bursary_name]</option>";
			}else {
				$bursary_drop .= "<option value='$barr[id]'>$barr[bursary_name]</option>";
			}
		}
		$bursary_drop .= "</select>";
	}

	$display = "
			<h2>Grant Bursary</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				$err
				<input type='hidden' name='key' value='confirm'>
				<tr>
					<th colspan='2'>Recipient Information</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bursary</td>
					<td>$bursary_drop</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Name</td>
					<td><input type='text' name='rec_name' value='$rec_name'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Address</td>
					<td><input type='text' name='rec_add1' value='$rec_add1'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td></td>
					<td><input type='text' name='rec_add2' value='$rec_add2'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td></td>
					<td><input type='text' name='rec_add3' value='$rec_add3'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td></td>
					<td><input type='text' name='rec_add4' value='$rec_add4'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>ID Number</td>
					<td><input type='text' name='rec_idnum' value='$rec_idnum'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Telephone</td>
					<td><input type='text' name='rec_telephone' value='$rec_telephone'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Date From</td>
					<td>".mkDateSelect("from")."</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Date To</td>
					<td>".mkDateSelect("to")."</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Notes</td>
					<td><textarea name='notes' cols='40' rows='4'>$notes</textarea></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td colspan='2' align='right'><input type='submit' value='Next'></td>
				</tr>
			</form>
			</table><br>"
			.mkQuickLinks(
				ql("bursary_type_add.php", "Add Bursary"),
				ql("bursary_type_view.php", "View Bursaries"),
				ql("bursary_give_view.php", "View Given Bursaries")
			);
	return $display;

}


function confirm_details ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if($bursary == "0")
		return get_details ($HTTP_POST_VARS,"<li class='err'>Please Select a Bursary To grant</li>");
	if((strlen($from_year) < 4) OR (strlen($from_month) < 2) OR (strlen($from_day) < 2))
		return get_details ($HTTP_POST_VARS,"<li class='err'>Please Select a Valid From Date</li>");
	if((strlen($to_year) < 4) OR (strlen($to_month) < 2) OR (strlen($to_day) < 2))
		return get_details ($HTTP_POST_VARS,"<li class='err'>Please Select a Valid To Date</li>");

	db_connect ();

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

				<input type='hidden' name='bursary' value='$bursary'>
				<input type='hidden' name='rec_name' value='$rec_name'>
				<input type='hidden' name='rec_add1' value='$rec_add1'>
				<input type='hidden' name='rec_add2' value='$rec_add2'>
				<input type='hidden' name='rec_add3' value='$rec_add3'>
				<input type='hidden' name='rec_add4' value='$rec_add4'>
				<input type='hidden' name='rec_idnum' value='$rec_idnum'>
				<input type='hidden' name='rec_telephone' value='$rec_telephone'>
				<input type='hidden' name='from_year' value='$from_year'>
				<input type='hidden' name='from_month' value='$from_month'>
				<input type='hidden' name='from_day' value='$from_day'>
				<input type='hidden' name='to_year' value='$to_year'>
				<input type='hidden' name='to_month' value='$to_month'>
				<input type='hidden' name='to_day' value='$to_day'>
				<input type='hidden' name='notes' value='$notes'>

				<tr>
					<th colspan='2'>Recipient Information</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bursary</td>
					<td>$showburs</td>
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
					<td>$from_year-$from_month-$from_day</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Date To</td>
					<td>$to_year-$to_month-$to_day</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Notes</td>
					<td>".nl2br($notes)."</td>
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


function write_details ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	db_connect ();

	$insert_sql = "INSERT INTO active_bursaries (bursary,rec_name,rec_add1,rec_add2,rec_add3,rec_add4,rec_idnum,rec_telephone,from_date,to_date,notes) VALUES ('$bursary','$rec_name','$rec_add1','$rec_add2','$rec_add3','$rec_add4','$rec_idnum','$rec_telephone','$from_date','$to_date','$notes')";
	$run_insert = db_exec($insert_sql) or errDie("Unable to save bursary information.");

	$update_sql = "UPDATE bursaries SET used = 'yes' WHERE id = '$bursary'";
	$run_update = db_exec($update_sql) or errDie("Unable to update bursary information.");

	return "
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Bursary Information Updated.</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Bursary Information Saved</td>
				</tr>
			</table>";


}


?>