<?

require ("../settings.php");
require ("../core-settings.php");
require ("../libs/ext.lib.php");

if(isset($HTTP_POST_VARS["key"])){
	switch ($HTTP_POST_VARS["key"]){
		case "confirm":
			$OUTPUT = confirm_setting_info ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = get_setting_info ($HTTP_POST_VARS);
	}
}else {
	$OUTPUT = get_setting_info ($HTTP_POST_VARS);
}

require ("../template.php");




function get_setting_info ($HTTP_POST_VARS,$err="")
{

	extract ($HTTP_POST_VARS);

	db_connect ();

	$get_settings = "SELECT * FROM credit_note_accounts ORDER BY accid";
	$run_settings = db_exec($get_settings) or errDie ("Unable to get credit note information.");
	if (pg_numrows($run_settings) < 1){
		$accs = array ();
	}else {
		$accs = array ();
		while ($arr = pg_fetch_array ($run_settings)){
			$accs[] = $arr['accid'];
		}
	}

	if(!isset($sortmethod))
		$sortmethod = "nameacc";
	if (!isset($vatacc))
		$vatacc = getCSetting("CRED_NOTE_VAT_ACC");
	if (!isset($vatacc) OR strlen($vatacc) < 1)
		$vatacc = 0;

	$sel1 = "";
	$sel2 = "";

	if ($sortmethod == "accname"){
		$sel2 = "checked='yes'";
		$sqlfilter = "ORDER BY topacc,accnum";
	}else {
		$sel1 = "checked='yes'";
		$sqlfilter = "ORDER BY accname,topacc";
	}



	db_conn ("core");

	$get_accs = "SELECT * FROM accounts $sqlfilter";
	$run_accs = db_exec($get_accs) or errDie ("Unable to get accounts information");
	if(pg_numrows($run_accs) < 1){
		return "<li class='err'>No Accounts Found. Please Add At Least One Account First.</li>";
	}

	$account_listing = "";
	while ($acc_arr = pg_fetch_array ($run_accs)){
		$show = "";
		if (in_array ($acc_arr['accid'],$accs))
			$show = "checked='yes'";
		if($sortmethod == "accname"){
			$show_acc = "$acc_arr[topacc]/$acc_arr[accnum] - $acc_arr[accname]";
		}else {
			$show_acc = "$acc_arr[accname] - $acc_arr[topacc]/$acc_arr[accnum]";
		}

		$account_listing .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$show_acc</td>
				<td><input type='checkbox' name='show_account[$acc_arr[accid]]' value='yes' $show></td>
			</tr>";
	}

	$get_vaccs = "SELECT accid,accname,topacc,accnum FROM accounts ORDER BY accname";
	$run_vaccs = db_exec($get_vaccs) or errDie ("Unable to get vat account information.");
	if (pg_numrows($run_vaccs) < 1){
		$vataccdrop = "<input type='hidden' name='' value=''>";
	}else {
		$vataccdrop = "<select name='vatacc'>";
		while ($aarr = pg_fetch_array($run_vaccs)){
			if(isDisabled($aarr['accid']))
				continue;

			if ($vatacc == $aarr['accid'])
				$vataccdrop .= "<option value='$aarr[accid]' selected>$aarr[topacc]/$aarr[accnum] - $aarr[accname]</option>";
			else 
				$vataccdrop .= "<option value='$aarr[accid]'>$aarr[topacc]/$aarr[accnum] - $aarr[accname]</option>";
		}
		$vataccdrop .= "</select>";
	}

	$display = "
		<h2>Select Accounts To Display On General Credit Note</h2>
		<table ".TMPL_tblDflts.">
		$err
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>Select Account To Use As VAT Account</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$vataccdrop</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input align='right' type='button' onClick=\"window.open('../core/acc-new2.php?update_parent=yes','accounts','width=700, height=400');\" value='New Account'></td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Sort By</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='sortmethod' value='nameacc' $sel1 onChange='document.form.submit();'> Account Name - Account Number</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='sortmethod' value='accname' $sel2 onChange='document.form.submit();'> Account Number - Account Name</td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Accounts</th>
				<th>Show</th>
			</tr>
			$account_listing
			".TBL_BR."
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Accept'></td>
			</tr>
		</form>
		</table>";
	return $display;

}



function confirm_setting_info ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if(!isset($show_account) OR !is_array ($show_account))
		return get_setting_info($HTTP_POST_VARS,"<li class='err'>Please Select At Least 1 Account To Display.</li><br>");

	if(!isset($vatacc))
		$vatacc = 0;

	db_connect ();

	#clear old entries
	$rem_sql = "DELETE FROM credit_note_accounts";
	$run_rem = db_exec($rem_sql) or errDie ("Unable to get credit note accounts information.");

	foreach ($show_account AS $each => $own){
		$add_sql = "INSERT INTO credit_note_accounts (accid) VALUES ($each)";
		$run_add = db_exec($add_sql) or errDie ("Unable to get credit note account information.");
	}

	#update vat account to use
	$check_vatacc = getCSetting("CRED_NOTE_VAT_ACC");

	if (!isset($check_vatacc) OR strlen($check_vatacc) < 1){
		#no previous setting ... insert
		$ins_sql = "
			INSERT INTO cubit.settings (
				constant, label, value, type, datatype, 
				minlen, maxlen, div, readonly
			) VALUES (
				'CRED_NOTE_VAT_ACC', 'Credit Note VAT Account', '$vatacc', 'general', 'allstring', 
				'1', '10', '0', 'f'
			)";
		$run_ins = db_exec($ins_sql) or errDie ("Unable to record vat account setting.");
	}else{
		#exists ... update
		$upd_sql = "UPDATE settings SET value = '$vatacc' WHERE constant = 'CRED_NOTE_VAT_ACC'";
		$run_upd = db_exec($upd_sql) or errDie ("Unable to update vat account setting.");
	}

//	return get_setting_info($HTTP_POST_VARS, "<li class='err'>Account Settings Updated.</li><br>");
	header ("Location: ../general-creditnote.php");

}


?>