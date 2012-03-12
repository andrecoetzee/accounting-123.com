<?

require ("../settings.php");

if (isset ($_POST["key"])){
	switch ($_POST["key"]){
		case "confirm":
			$OUTPUT = write_cubit_setting($_POST);
			break;
		default:
			$OUTPUT = get_cubit_setting($_POST);
	}
}else {
	$OUTPUT = get_cubit_setting ();
}

$OUTPUT .= "<p>".mkQuickLinks(
	ql ("../stock_take_pre.php","Pre Stock Take")
);

require ("../template.php");


function get_cubit_setting ($err="")
{

	$cur_setting = getCsetting ("OPTIONAL_STOCK_FILTERS");

	$yes_setting = "";
	$no_setting = "";

	if (!isset ($cur_setting) OR strlen ($cur_setting) < 1){
		$yes_setting = "checked='yes'";
	}else {
		if ($cur_setting == "yes") 
			$yes_setting = "checked";
		else 
			$no_setting = "checked";
	}

	$display = "
		<h4>Display Additional Stock Filters On Reports</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
		<table ".TMPL_tblDflts.">
			$err
			<tr>
				<th>Display Filters</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
					<input type='radio' name='setting' value='yes' $yes_setting> Yes
					<input type='radio' name='setting' value='no' $no_setting> No
				</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' value='Save'></td>
			</tr>
		</table>
		</form>";
	return $display;

}


function write_cubit_setting ($_POST)
{

	extract ($_POST);

	if (!isset ($setting) or strlen ($setting) < 1){
		$setting = "no";
	}

	db_connect ();

	$check_sql = "SELECT value FROM settings WHERE constant = 'OPTIONAL_STOCK_FILTERS' LIMIT 1";
	$run_check = db_exec ($check_sql) or errDie ("Unable to get setting information.");
	if (pg_numrows ($run_check) < 1){
		#nothing found ... insert
		$write_sql = "
			INSERT INTO settings (
				constant, label, value, type, datatype, minlen, maxlen, div, readonly
			) VALUES (
				'OPTIONAL_STOCK_FILTERS', 'Display Additional Stock Filters', '$setting', 'general', 'string', '2', '3', '0', 'f'
			)";
		$run_write = db_exec ($write_sql) or errDie ("Unable to record pre stock take display limit setting.");
	}else {
		#found setting ... update
		$upd_sql = "UPDATE settings SET value = '$setting' WHERE constant = 'OPTIONAL_STOCK_FILTERS'";
		$run_upd = db_exec ($upd_sql) or errDie ("Unable to update pre stock take display setting.");
	}

	return get_cubit_setting("<li class='yay'>Setting Has Been Saved.</li><br>");

}


?>