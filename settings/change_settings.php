<?

	require ("../settings.php");

	if(isset($_POST["key"])){
		switch ($_POST["key"]){
			case "confirm":
				$OUTPUT = change_settings ($_POST);
				break;
			default:
				$OUTPUT = show_settings ();
		}
	}else {
		$OUTPUT = show_settings ();
	}

	require ("../template.php");



function show_settings ($err = "")
{

	db_connect ();

	#assign teh vars ...
	$sel1 = "";
	$sel2 = "";


	#get trad disc setting
	$traddisc = getCSetting("SET_INV_TRADDISC");

	if($traddisc == "include")
		$sel1 = "checked='yes'";
	else 
		$sel2 = "checked='yes'";


	$display = "
					<h2>Change Cubit Settings</h2>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='confirm'>
						$err
						<tr>
							<th colspan='2'>Invoice Trade Discount Setting</th>
						</tr>
						<tr class='".bg_class()."'>
							<td><input type='radio' name='traddisc' value='include' $sel1> Include Delivery Charge In Trade Discount</td>
							<td><input type='radio' name='traddisc' value='exclude' $sel2> Exclude Delivery Charge In Trade Discount</td>
						</tr>
						".TBL_BR."
						<tr>
							<td colspan='2' align='right'><input type='submit' value='Change Settings'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}



function change_settings ($_POST)
{

	extract ($_POST);

	$traddisc_check = getCSetting("SET_INV_TRADDISC");

	db_connect ();

	if(!isset($traddisc_check) OR strlen($traddisc_check) < 1){
		#setting does not yet exist ... ad it :/
		$add_sql = "INSERT INTO cubit.settings (constant,label,value,type,datatype,minlen,maxlen,div,readonly) VALUES ('SET_INV_TRADDISC','Include/Exclude  Delivery Charge In Trade Discount','exclude','general','string','7','7','0','f');";
		$run_add = db_exec ($add_sql) or errDie ("Unable to get settings information.");
	}else {
		#update the setting ...
		$upd_sql = "UPDATE settings SET value = '$traddisc' WHERE constant = 'SET_INV_TRADDISC'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update setting information.");
	}

	return show_settings ("<li class='err'>Settings Have Been Saved.</li><br>");

}



?>