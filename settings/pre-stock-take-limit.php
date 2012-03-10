<?

require ("../settings.php");

if (isset ($HTTP_POST_VARS["key"])){
	switch ($HTTP_POST_VARS["key"]){
		case "confirm":
			$OUTPUT = write_limit_setting($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = get_limit_setting($HTTP_POST_VARS);
	}
}else {
	$OUTPUT = get_limit_setting ();
}

$OUTPUT .= "<p>".mkQuickLinks(
	ql ("../stock_take_pre.php","Pre Stock Take")
);

require ("../template.php");


function get_limit_setting ($err="")
{

	$cur_setting = getCsetting ("PRE_STOCK_TAKE_LIMIT");

	if (!isset ($cur_setting) OR strlen ($cur_setting) < 1){
		$cur_setting = 0;
	}

	$cur_setting += 0;

	$display = "
		<h4>Change How Many Stock Items Appear On Pre Stock Take Pages</h4>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'
		<table ".TMPL_tblDflts.">
			$err
			<tr>
				<th>Display Limit</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='text' size='5' name='setting' value='$cur_setting'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' value='Save'></td>
			</tr>
		</table>
		</form>";
	return $display;

}


function write_limit_setting ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	$setting += 0;

	db_connect ();

	$check_sql = "SELECT value FROM settings WHERE constant = 'PRE_STOCK_TAKE_LIMIT' LIMIT 1";
	$run_check = db_exec ($check_sql) or errDie ("Unable to get ");
	if (pg_numrows ($run_check) < 1){
		#nothing found ... insert
		$write_sql = "
			INSERT INTO settings (
				constant, label, value, type, datatype, minlen, maxlen, div, readonly
			) VALUES (
				'PRE_STOCK_TAKE_LIMIT', 'Stock Items On Displayed On Pre Stock Take', '$setting', 'general', 'num', '1', '6', '0', 'f'
			)";
		$run_write = db_exec ($write_sql) or errDie ("Unable to record pre stock take display limit setting.");
	}else {
		#found setting ... update
		$upd_sql = "UPDATE settings SET value = '$setting' WHERE constant = 'PRE_STOCK_TAKE_LIMIT'";
		$run_upd = db_exec ($upd_sql) or errDie ("Unable to update pre stock take display setting.");
	}

	return get_limit_setting("<li class='yay'>Setting Has Been Saved.</li><br>");

}


?>