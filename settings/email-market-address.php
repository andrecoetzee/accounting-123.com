<?

	require ("../settings.php");
	
	if (isset($HTTP_POST_VARS["key"])){
		$OUTPUT = write_setting ($HTTP_POST_VARS);
	}else {
		$OUTPUT = get_show_setting ();
	}

	$OUTPUT .= "<p>".
				mkQuickLinks(
					ql("../email-queue-manage.php","Manage Email Queue"),
					ql("../email-groups.php", "Send Email To Group"),
					ql("../email-group-new.php", "Add Email Group"),
					ql("../email-group-view.php", "View Email Groups")
				);

	require ("../template.php");


function get_show_setting ($err="")
{

	db_connect ();

	$setting = getCSetting ("MARKET_MAIL_FROM");
	if (!isset($setting) OR strlen($setting) < 1)
		$setting = "";

	$display = "
					<h2>Change Email Marketing From Address</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						$err
						<input type='hidden' name='key' value='confirm'>
						<tr>
							<th>Email Marketing From Address</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='text' size='40' name='setting' value='$setting'></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td align='right'><input type='submit' value='Confirm'></td>
						</tr>
					</form>
					</table>
				";
	return $display;
}



function write_setting ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if (!isset($setting) OR strlen($setting) < 1)
		$setting = "";

	db_connect ();

	#update setting
	$check = getCSetting ("MARKET_MAIL_FROM");

	if (!isset($check) OR strlen($check) < 1){
		#no setting ... insert
		$ins_sql = "
			INSERT INTO settings (
				constant, label, value, type, 
				datatype, minlen, maxlen, div, readonly
			) VALUES (
				'MARKET_MAIL_FROM', 'Marketing Email From Address', '$setting', 'general',
				'allstring', '1', '250', '0', 'f'
			);
				";
		$run_ins = db_exec($ins_sql) or errDie ("Unable to remove marketing from email information.");
	}else {
		#setting ... update
		$upd_sql = "UPDATE settings SET value = '$setting' WHERE constant = 'MARKET_MAIL_FROM'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update marketing email information.");
	}
	return get_show_setting("<li class='err'>Email Setting Updated</li>");

}


?>