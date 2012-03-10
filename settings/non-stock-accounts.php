<?

require ("../settings.php");
require ("../core-settings.php");

if (isset ($_REQUEST["key"])){
	switch ($_REQUEST["key"]){
		case "confirm":
			if (isset ($_REQUEST["remove"])){
				$OUTPUT = remove_entry ();
			}else {
				$OUTPUT = save_new_setting ();
			}
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

	$get_list = "SELECT * FROM non_stock_account_list";
	$run_list = db_exec ($get_list) or errDie ("Unable to get account lisit information.");
	if (pg_numrows ($run_list) > 0){
		$account_list = "
			<tr>
				<th>Account</th>
				<th>Options</th>
			</tr>";
		while ($aarr = pg_fetch_array ($run_list)){
			$account_list .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$aarr[accname]</td>
					<td><input type='submit' name='remove[$aarr[accid]]' value='Remove'></td>
				</tr>";
		}
	}

	$cur_setting = getCsetting ("USE_NON_STOCK_ACCOUNTS");

	$yes_setting = "";
	$no_setting = "";

	if (!isset ($cur_setting) OR strlen ($cur_setting) < 1){
		$no_setting = "checked='yes'";
	}else {
		if ($cur_setting == "yes") 
			$yes_setting = "checked";
		else 
			$no_setting = "checked";
	}

	$display = "
		<h2>Accounts to Display for Non Stock Sales</h2>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Use These Accounts Only</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
					<input type='radio' name='setting' value='yes' $yes_setting> Yes
					<input type='radio' name='setting' value='no' $no_setting> No
				</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' name='save' value='Save'></td>
			</tr>
		</table>
		<br>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Add Account</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>".mkAccSelect ("newaccount", $newaccount)." <input type='submit' name='add' value='Add Account'></td>
			</tr>
			<tr><td><br></td></tr>
			$account_list
		</table>
		</form>";
	return $display;

}


function save_new_setting ()
{

	extract ($_REQUEST);

	if (!isset ($setting) or strlen ($setting) < 1){
		$setting = "no";
	}

	db_connect ();

	$check_sql = "SELECT value FROM settings WHERE constant = 'USE_NON_STOCK_ACCOUNTS' LIMIT 1";
	$run_check = db_exec ($check_sql) or errDie ("Unable to get setting information.");
	if (pg_numrows ($run_check) < 1){
		#nothing found ... insert
		$write_sql = "
			INSERT INTO settings (
				constant, label, value, type, datatype, minlen, maxlen, div, readonly
			) VALUES (
				'USE_NON_STOCK_ACCOUNTS', 'Use Only Specified Accounts for non stock', '$setting', 'general', 'string', '2', '3', '0', 'f'
			)";
		$run_write = db_exec ($write_sql) or errDie ("Unable to record pre stock take display limit setting.");
	}else {
		#found setting ... update
		$upd_sql = "UPDATE settings SET value = '$setting' WHERE constant = 'USE_NON_STOCK_ACCOUNTS'";
		$run_upd = db_exec ($upd_sql) or errDie ("Unable to update pre stock take display setting.");
	}

	if (isset ($save)) 
		return get_current_setting ();

	#check duplicate
	$check_sql = "SELECT id FROM non_stock_account_list WHERE accid = '$newaccount'";
	$run_check = db_exec ($check_sql) or errDie ("Unable to check for new account.");
	if (pg_numrows ($run_check) < 1){
		$ins_sql = "
			INSERT INTO non_stock_account_list (
				accid, accname
			) VALUES (
				'$newaccount', (SELECT accname FROM core.accounts where accid = '$newaccount' LIMIT 1)
			)";
		$run_ins = db_exec ($ins_sql) or errDie ("Unable to record new account information.");
	}


	return get_current_setting ();

}


function remove_entry ()
{

	extract ($_REQUEST);

	$accid_arr = array_keys($remove);
	$accid = $accid_arr[0];

	db_connect ();

	$rem_sql = "DELETE FROM non_stock_account_list WHERE accid = '$accid'";
	$run_rem = db_exec ($rem_sql) or errDie ("Unable to remove account information.");

	unset ($_REQUEST);

	return get_current_setting ();

}


?>