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
	$setting = getCSetting("JOBCARD_OPERATION_STOCK_CHECKBOX");

	$sel1 = "";
	$sel2 = "";
	if(isset($setting) AND $setting == "checked")
		$sel1 = "checked='yes'";
	else 
		$sel2 = "checked='yes'";


	$display = "
		<h2>Change Jobcard Operation Add Stock Setting</h2>
		<table ".TMPL_tblDflts.">
		$err
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>Change Setting</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' name='operation_setting' value='checked' $sel1>Checked By Default</td>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' name='operation_setting' value='not_checked' $sel2>Unchecked By Default</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' value='Save'></td>
			</tr>
		</form>
		</table>";
	return $display;

}



function save_setting_val ($_POST)
{

	extract ($_POST);

	db_connect ();

	#check for setting
	$check = getCSetting("JOBCARD_OPERATION_STOCK_CHECKBOX");

	if (!isset($check) OR strlen ($check) < 1){
		#no setting ... insert
		$sql = "
			INSERT INTO cubit.settings (
				constant, label, value, type, datatype, minlen, maxlen, div, readonly
			) VALUES (
				'JOBCARD_OPERATION_STOCK_CHECKBOX', 'Jobcard Operation Add Stock Checkbox Default Setting', '$operation_setting', 'general', 'allstring', '6', '14', '0', 'f'
			)";
		$run_sql = db_exec($sql) or errDie ("Unable to record setting information.");
	}else {
		$upd_sql = "UPDATE settings SET value = '$operation_setting' WHERE constant = 'JOBCARD_OPERATION_STOCK_CHECKBOX'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update customer statement setting.");
	}

	return get_setting_val("<li class='err'>Jobcard Operation Add Stock Setting Updated.</li><br>");

}



?>