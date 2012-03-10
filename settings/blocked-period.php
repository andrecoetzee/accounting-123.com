<?

require ("../settings.php");

if (isset ($_POST["key"])){
	switch ($_POST["key"]){
		case "confirm":
			$OUTPUT = write_setting ();
			break;
		default:
			$OUTPUT = get_range();
	}
}else {
	$OUTPUT = get_range ();
}

require ("../template.php");



function get_range ($err="")
{

	$blocked_range_from = getCSetting ("BLOCKED_FROM");
	$blocked_range_to = getCSetting ("BLOCKED_TO");

	if (!isset ($blocked_range_from) OR strlen ($blocked_range_from) < 1){
		$blocked_range_from = date ("Y-m-d");
	}

	if (!isset ($blocked_range_to) OR strlen ($blocked_range_to) < 1){
		$blocked_range_to = date ("Y-m-d");
	}

	$from_arr = explode ("-", $blocked_range_from);
	$to_arr = explode ("-", $blocked_range_to);

	$from_year = $from_arr[0];
	$from_month = $from_arr[1];
	$from_day = $from_arr[2];

	$to_year = $to_arr[0];
	$to_month = $to_arr[1];
	$to_day = $to_arr[2];

// 	$from_year = substr ($blocked_range_from,0,4);
// 	$from_month = substr ($blocked_range_from,5,2);
// 	$from_day = substr ($blocked_range_from,8,2);
// 
// 	$to_year = substr ($blocked_range_to,0,4);
// 	$to_month = substr ($blocked_range_to,5,2);
// 	$to_day = substr ($blocked_range_to,8,2);

	$display = "
		<h4>Set Date Range to Block Transactions In</h4>
		$err
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th colspan='2'>Date Range</th>
			</tr>
			<tr>
				<th>From Date</th>
				<th>To Date</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".mkDateSelect ("from",$from_year,$from_month,$from_day)."</td>
				<td>".mkDateSelect ("to",$to_year,$to_month,$to_day)."</td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='right' colspan='2'><input type='submit' value='Save Setting'></td>
			</tr>
		</form>
		</table>";
	return $display;

}



function write_setting ()
{

	extract ($_POST);

	db_connect ();

	$check1 = getCSetting ("BLOCKED_FROM");
	$check2 = getCSetting ("BLOCKED_TO");

	if (!isset ($check1) OR strlen ($check1) < 1){
		$ins_sql = "
			INSERT INTO settings (
				constant, label, value, type, 
				datatype, minlen, maxlen, div, readonly
			) VALUES (
				'BLOCKED_FROM', 'Blocked Period Date Range From', '$from_year-$from_month-$from_day', 'accounting', 
				'allstring', '10', '10', '0','f'
			)";
		$run_ins = db_exec ($ins_sql) or errDie ("Unable to record blocked period information.");
	}else {
		$upd1_sql = "UPDATE settings SET value = '$from_year-$from_month-$from_day' WHERE constant = 'BLOCKED_FROM'";
		$run_upd1 = db_exec ($upd1_sql) or errDie ("Unable to update blocked period information.");
	}

	if (!isset ($check2) OR strlen ($check2) < 1){
		$ins_sql = "
			INSERT INTO settings (
				constant, label, value, type, 
				datatype, minlen, maxlen, div, readonly
			) VALUES (
				'BLOCKED_TO', 'Blocked Period Date Range To', '$to_year-$to_month-$to_day', 'accounting', 
				'allstring', '10', '10', '0','f'
			)";
		$run_ins = db_exec ($ins_sql) or errDie ("Unable to record blocked period information.");
	}else {
		$upd2_sql = "UPDATE settings SET value = '$to_year-$to_month-$to_day' WHERE constant = 'BLOCKED_TO'";
		$run_upd2 = db_exec ($upd2_sql) or errDie ("Unable to update blocked period information.");
	}

	return get_range("<li class='yay'>Setting has been saved.</li><br>");

}


?>