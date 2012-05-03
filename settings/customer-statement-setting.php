<?

	require ("../settings.php");
	
	if(isset($_POST["key"])){
		switch ($_POST["key"]){
			case "confirm":
				$OUTPUT = save_setting_val ($_POST);
				break;
			default:
				$OUTPUT = get_setting_val ();
		}
	}else {
		$OUTPUT = get_setting_val ();
	}
	
	require ("../template.php");



function get_setting_val ($err="")
{

	db_connect ();

	#get current setting ...
	$setting = getCSetting("STATEMENT_AGE");

	$sel1 = "";
	$sel2 = "";
	if(isset($setting) AND $setting == "statement")
		$sel1 = "checked='yes'";
	else 
		$sel2 = "checked='yes'";


	$display = "
					<h2>Change Customer Statement Setting</h2>
					<table ".TMPL_tblDflts.">
					$err
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirm'>
						<tr>
							<th>Change Setting</th>
						</tr>
						<tr class='".bg_class()."'>
							<td><input type='radio' name='state_setting' value='statement' $sel1>Age From Statement Date</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><input type='radio' name='state_setting' value='invoice' $sel2>Age From Invoice Date</td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' value='Save'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



function save_setting_val ($_POST)
{

	extract ($_POST);

	db_connect ();

	#check for setting
	$check = getCSetting("STATEMENT_AGE");

	if (!isset($check) OR strlen ($check) < 1){
		#no setting ... insert
		$sql = "
				INSERT INTO cubit.settings 
					(constant,label,value,type,datatype,minlen,maxlen,div,readonly) 
				VALUES 
					('STATEMENT_AGE','Customer Statement','$state_setting','general','allstring','6','9','0','f')";
		$run_sql = db_exec($sql) or errDie ("Unable to record setting information.");
	}else {
		$upd_sql = "UPDATE settings SET value = '$state_setting' WHERE constant = 'STATEMENT_AGE'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update customer statement setting.");
	}

	return get_setting_val("<li class='err'>Customer Statement Setting Updated.</li><br>");

}



?>