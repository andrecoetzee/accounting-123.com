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

	$sort_order_setting = getCSetting ("ACCOUNT_SORT_ORDER");

	if (!isset ($sort_order_setting) OR strlen ($sort_order_setting) < 1){
		$sort_order_setting = "number";
	}

	if ($sort_order_setting == "number"){
		$checked1 = "checked='yes'";
		$checked2 = "";
	}else {
		$checked1 = "";
		$checked2 = "checked='yes'";
	}

	$display = "
		<h2>Accounts Selected Sort Order</h2>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Accounts Sort Order</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
					<input type='radio' name='sort_order' value='number' $checked1> Account Number - Account Name 
					<input type='radio' name='sort_order' value='name' $checked2> Account Name - Account Number 
				</td>
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

	$check_sql = "SELECT value FROM settings WHERE constant = 'ACCOUNT_SORT_ORDER' LIMIT 1";
	$run_check = db_exec ($check_sql) or errDie ("Unable to get current setting information.");
	if (pg_numrows ($run_check) < 1){
		$ins_sql = "
			INSERT INTO settings (
				constant, label, value, type, datatype, minlen, maxlen, div, readonly
			) VALUES (
				'ACCOUNT_SORT_ORDER', 'Accounts Sort Order', '$sort_order', 'company', 'allstring', '1', '250', '0', 'f'
			)";
		$run_ins = db_exec ($ins_sql) or errDie ("Unable to record accounts sort order information.");
	}else {
		$upd_sql = "UPDATE settings SET value = '$sort_order' WHERE constant = 'ACCOUNT_SORT_ORDER'";
		$run_upd = db_exec ($upd_sql) or errDie ("Unable to update setting information.");
	}

	return get_current_setting ();

}


?>