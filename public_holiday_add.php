<?

	require ("settings.php");

	if(isset($HTTP_POST_VARS["key"])){
		switch($HTTP_POST_VARS["key"]){
			case "confirm":
				$OUTPUT = confirm_holiday ($HTTP_POST_VARS);
				break;
			case "write":
				$OUTPUT = write_holiday ($HTTP_POST_VARS);
				break;
			default:
				$OUTPUT = get_holiday ();
		}
	}else {
		$OUTPUT = get_holiday ();
	}

	require ("template.php");


function get_holiday ()
{

	$holiday_types = array (
					"Religious",
					"State",
					"General"
				);

	$holiday_type_drop = "<select name='holiday_type'>";
	foreach ($holiday_types as $each){
		$holiday_type_drop .= "<option value='$each'>$each</option>";
	}
	$holiday_type_drop .= "</select>";

	$display = "
			<h2>Add New Public Holiday</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<tr>
					<th>Holiday Name</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='text' name='holiday_name'></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<th>Holiday Type</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>$holiday_type_drop</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<th>Holiday Date</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>".mkDateSelect("holiday")."</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' value='Next'></td>
				</tr>
			</table><br>"
			.mkQuickLinks(
				ql("public_holiday_add.php", "Add Public Holiday"),
				ql("public_holiday_list.php", "View Public Holidays")
			);
	return $display;

}


function confirm_holiday ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	$display = "
			<h2>Confirm New Public Holiday</h2>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='holiday_name' value='$holiday_name'>
				<input type='hidden' name='holiday_type' value='$holiday_type'>
				<input type='hidden' name='holiday_year' value='$holiday_year'>
				<input type='hidden' name='holiday_month' value='$holiday_month'>
				<input type='hidden' name='holiday_day' value='$holiday_day'>
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
					<td>$holiday_year-$holiday_month-$holiday_day</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' value='Confirm'></td>
				</tr>
			</table><br>"
			.mkQuickLinks(
				ql("public_holiday_add.php", "Add Public Holiday"),
				ql("public_holiday_list.php", "View Public Holidays")
			);
	return $display;

}


function write_holiday ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	$holiday_date = "$holiday_year-$holiday_month-$holiday_day";

	db_connect ();

	$insert_sql = "INSERT INTO public_holidays (holiday_name,holiday_type,holiday_date) VALUES ('$holiday_name','$holiday_type','$holiday_date')";
	$run_insert = db_exec($insert_sql) or errDie("Unable to store public holiday information");

	return "
			<table ".TMPL_Dflts.">
				<tr>
					<th>Information Saved</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Public Holiday Has Been Added</td>
				</tr>
			</table><br>"
			.mkQuickLinks(
				ql("public_holiday_add.php", "Add Public Holiday"),
				ql("public_holiday_list.php", "View Public Holidays")
			);


}

?>