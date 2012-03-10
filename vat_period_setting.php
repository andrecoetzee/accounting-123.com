<?

require ("settings.php");

if(isset($HTTP_POST_VARS["key"])){
	switch ($HTTP_POST_VARS["key"]){
		case "confirm":
			$OUTPUT = confirm_settings ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write_settings ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = get_setting ();
	}
}else {
	$OUTPUT = get_setting ();
}

require ("template.php");


function get_setting ()
{

	#get current setting
	$get_period = "SELECT * FROM settings WHERE label = 'VAT Period' LIMIT 1";
	$run_period = db_exec($get_period) or errDie("Unable to get vat period information.");
	if(pg_numrows($run_period) < 1){
		$period = "2";
	}else {
		$parr = pg_fetch_array($run_period);
		$period = $parr['value'];
	}

	$options = array (
		"1" => "1 Month",
		"2" => "2 Months",
		"3" => "3 Months",
		"6" => "6 Months",
		"12" => "1 Year"
	);

	$period_drop = "<select name='period'>";
	foreach ($options as $each => $own){
		if($period == $each){
			$period_drop .= "<option value='$each' selected>$own</option>";
		}else {
			$period_drop .= "<option value='$each'>$own</option>";
		}
	}
	$period_drop .= "</select>";


	$display = "
		<h2>Change VAT Period Setting
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr><td><br></td></tr>
			<tr>
				<th>Setting</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$period_drop</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' value='Change'></td>
			</tr>
		</form>
		</table>";
	return $display;

}


function confirm_settings ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if(!isset($period) OR (strlen($period) < 1)){
		return "Invalid Period Selected.";
	}

	switch ($period){
		case "1":
			$showperiod = "1 Month";
			break;
		case "2":
			$showperiod = "2 Months";
			break;
		case "3":
			$showperiod = "3 Months";
			break;
		case "6":
			$showperiod = "6 Months";
			break;
		case "12":
			$showperiod = "1 Year";
			break;
		default:
			$showperiod = "";
	}

	$display = "
		<h2>Confirm Change VAT Period Setting</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='period' value='$period'>
			<tr>
				<th>Setting</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$showperiod</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' value='Change'></td>
			</tr>
		</form>
		</table>";
	return $display;

}


function write_settings ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	db_connect ();

	#check if setting is set ...
	$get_check = "SELECT * FROM settings WHERE label = 'VAT Period' LIMIT 1";
	$run_check = db_exec($get_check) or errDie("Unable to get vat period setting.");
	if(pg_numrows($run_check) < 1){
		#no entry .... insert
		$insert_sql = "
			INSERT INTO settings (
				constant, label, value, type, datatype, minlen, maxlen, div, readonly
			) VALUES (
				'VAT_PERIOD', 'VAT Period', '$period', 'accounting', 'num', '1', '2', '".USER_DIV."', 't'
			)";
		$run_insert = db_exec($insert_sql) or errDie("Unable to get vat period setting information.");
	}else {
		#entry ... update
		$sarr = pg_fetch_array($run_check);
		$update_sql = "UPDATE settings SET value = '$period' WHERE label = 'VAT Period'";
		$run_update = db_exec($update_sql) or errDie("Unable to update vat period setting.");
	}

	$display = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Setting Changed.</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>VAT Period Setting has Been Updated.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='reporting/vat_return_report.php'>Process VAT Return Report</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $display;

}


?>