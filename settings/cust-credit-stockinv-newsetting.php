<?

require ("../settings.php");

if(isset($_POST["key"])){
	switch ($_POST["key"]){
		case "confirm":
			$OUTPUT = change_setting_confirm ($_POST);
			break;
		default:
			$OUTPUT = do_setting ();
	}
}elseif (isset($_GET["change"])){
	$OUTPUT = change_setting ();
}else {
	$OUTPUT = do_setting ();
}

require ("../template.php");
	
	
function change_setting ($err="")
{

	db_connect ();

	$inv_set = getCSetting("NEWINV_SETTING");

	$sel1 = "";
	$sel2 = "";

	if ($inv_set == "yes")
		$sel1 = "checked='yes'";
	else 
		$sel2 = "checked='yes'";

	$display = "
		<h2>Change Navigation Setting</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			$err
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th colspan='2'>Change Setting</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='setting' value='yes' $sel1>Automatically Create New Invoice After Processing One.</td>
				<td><input type='radio' name='setting' value='no' $sel2>Go To Complete Screen. (Less Incomplete Invoices)</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' value='Save Setting'></td>
			</tr>
		</form>
		</table>";
	return $display;


}



function change_setting_confirm ($_POST)
{

	extract ($_POST);

	if(!isset($setting) OR strlen($setting) < 1){
		return change_setting("<li class='err'>Please Select A Valid Option.</li>");
	}

	$inv_set = getCSetting("NEWINV_SETTING");

	db_connect ();

	if(isset($inv_set) AND strlen($inv_set) > 0){
		#update
		$upd_sql = "UPDATE settings SET value = '$setting' WHERE constant = 'NEWINV_SETTING'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update setting information.");
	}else {
		#insert
		$ins_sql = "INSERT INTO cubit.settings (constant,label,value,type,datatype,minlen,maxlen,div,readonly) VALUES ('NEWINV_SETTING','Create A New Invoice After Processing The Last One','$setting','general','string','2','3','0','f');";
		$run_ins = db_exec($ins_sql) or errDie ("Unable to record setting.");
	}

	$display = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Setting Updated</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Invoice Setting Has Been Updated.</td>
			</tr>
			".TBL_BR."
		</table>
		".mkQuickLinks(
			ql ("../cust-credit-stockinv.php", "Make Another Invoice"),
			ql ("cust-credit-stockinv-newsetting.php?change=yes", "Change Setting To Create A New Invoice After Processing One."),
			ql ("../invoice-view.php", "View Invoices")
		);
	return $display;

}



function do_setting ()
{

	extract ($_GET);

	$inv_set = getCSetting("NEWINV_SETTING");

	if (isset($vol) && $vol == "yes"){
		$script = "calc-cust-credit-stockinv.php";
	}else {
		$script = "cust-credit-stockinv.php";
	}

	if($inv_set == "no"){
		return mkQuickLinks(
			ql ("../$script", "Make Another Invoice"),
			ql ("cust-credit-stockinv-newsetting.php?change=yes", "Change Setting To Create A New Invoice After Processing One."),
			ql ("../invoice-view.php", "View Invoices")
		);
	}else {
		header ("Location: ../$script");
	}

}



?>