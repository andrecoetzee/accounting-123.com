<?

require ("../settings.php");

if (isset ($_REQUEST["key"])){
	switch ($_REQUEST["key"]){
		case "confirm":
			$OUTPUT = save_new_setting ();
			break;
		default:
			$OUTPUT = get_current_setting ();
	}
}else {
	$OUTPUT = get_current_setting ();
}

require ("../template.php");



function get_current_setting ()
{

	db_connect ();

	$date_setting = getCSetting ("TRANSACTION_DATE");
	$use_date_setting = getCSetting ("USE_TRANSACTION_DATE");

	if (!isset ($date_setting) OR strlen ($date_setting) < 1){
		$date_setting = date ("Y-m-d");
	}
	if (!isset ($use_date_setting) OR strlen ($use_date_setting) < 1){
		$use_date_setting = "no";
	}

	$date_arr = explode ("-", $date_setting);

	if ($use_date_setting == "yes"){
		$checked1 = "";
		$checked2 = "checked='yes'";
	}else {
		$checked1 = "checked='yes'";
		$checked2 = "";
	}

	$display = "
		<h2>Transaction Date To Use</h2>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Transaction Date Setting</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
					<input type='radio' name='use_date_setting' value='no' $checked1> Use System Date 
					<input type='radio' name='use_date_setting' value='yes' $checked2> Use This Date 
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th>Transaction Date To Use</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".mkDateSelect("transaction", "$date_arr[0]", "$date_arr[1]", "$date_arr[2]")."</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='submit' value='Save Setting'></td>
			</tr>
		</table>
		</form>";
	return $display;

}


function save_new_setting ()
{

	extract ($_REQUEST);

	db_connect ();

	$check_sql = "SELECT value FROM settings WHERE constant = 'TRANSACTION_DATE' LIMIT 1";
	$run_check = db_exec ($check_sql) or errDie ("Unable to get current setting information.");
	if (pg_numrows ($run_check) < 1){
		$ins_sql = "
			INSERT INTO settings (
				constant, label, value, type, datatype, minlen, maxlen, div, readonly
			) VALUES (
				'TRANSACTION_DATE', 'Transaction Date', '$transaction_year-$transaction_month-$transaction_day', 'company', 'allstring', '1', '250', '0', 'f'
			)";
		$run_ins = db_exec ($ins_sql) or errDie ("Unable to record transaction date setting information.");
	}else {
		$upd_sql = "UPDATE settings SET value = '$transaction_year-$transaction_month-$transaction_day' WHERE constant = 'TRANSACTION_DATE'";
		$run_upd = db_exec ($upd_sql) or errDie ("Unable to update setting information.");
	}

	$check_sql = "SELECT value FROM settings WHERE constant = 'USE_TRANSACTION_DATE' LIMIT 1";
	$run_check = db_exec ($check_sql) or errDie ("Unable to get current setting information.");
	if (pg_numrows ($run_check) < 1){
		$ins_sql = "
			INSERT INTO settings (
				constant, label, value, type, datatype, minlen, maxlen, div, readonly
			) VALUES (
				'USE_TRANSACTION_DATE', 'Use Transaction Date', '$use_date_setting', 'company', 'allstring', '1', '250', '0', 'f'
			)";
		$run_ins = db_exec ($ins_sql) or errDie ("Unable to record transaction date setting information.");
	}else {
		$upd_sql = "UPDATE settings SET value = '$use_date_setting' WHERE constant = 'USE_TRANSACTION_DATE'";
		$run_upd = db_exec ($upd_sql) or errDie ("Unable to update setting information.");
	}

	return get_current_setting ();

}


?>