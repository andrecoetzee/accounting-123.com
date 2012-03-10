<?

require ("../settings.php");

if (isset($HTTP_POST_VARS["key"])){
	switch ($HTTP_POST_VARS["key"]){
		case "confirm":
			$OUTPUT = confirm_info_setting ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = get_info_setting ($HTTP_POST_VARS);
	}
}else {
	$OUTPUT = get_info_setting ($HTTP_POST_VARS);
}

require ("../template.php");



function get_info_setting ($HTTP_POST_VARS, $err="")
{

	extract ($HTTP_POST_VARS);

	#handle unset vars
	if (!isset($pay_type))
		$pay_type = getCSetting("SUPP_PAY_TYPE");
	if (!isset($pay_type))
		$pay_type = "";

	if (!isset($process_type))
		$process_type = getCSetting("PAY_TYPE");
	if (!isset($process_type))
		$process_type = "";


	#handle preset vars
	$psel1 = "";
	$psel2 = "";
	$psel3 = "";
	if(isset($pay_type) AND ($pay_type == "cheq_man")){
		$psel1 = "checked='yes'";
	}elseif (isset($pay_type) AND ($pay_type == "cheq_aut")){
		$psel2 = "checked='yes'";
	}elseif (isset($pay_type) AND ($pay_type == "export")) {
		$psel3 = "checked='yes'";
	}else {
		$psel1 = "checked='yes'";
	}

	$prsel1 = "";
	$prsel2 = "";
	if ($process_type == "batch")
		$prsel2 = "checked='yes'";
	else 
		$prsel1 = "checked='yes'";

	$display = "
		<h2>Set Supplier Payment Output Type</h2>
		<table ".TMPL_tblDflts.">
			$err
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>Payment Type</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
					<input type='radio' name='pay_type' $psel1 value='cheq_man'> Manual Cheque Printing<br>
					<input type='radio' name='pay_type' $psel2 value='cheq_aut'> System Cheque Printing<br>
					<input type='radio' name='pay_type' $psel3 value='export'> Export To Payment File
				</td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Payment Process Type</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='process_type' value='now' $prsel1>Pay creditor immediately and add to cashbook</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' name='process_type' value='batch' $prsel2>Add to creditor payment batch</td>
			</tr>
			<tr>
				<td align='right'><input type='submit' value='Save'></td>
			</tr>
		</form>
		</table>";
	return $display;

}



function confirm_info_setting ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($pay_type, "string", 1, 15, "Invalid Payment Method");
	$v->isOk ($process_type, "string", 1, 15, "Invalid Payment Process Method");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirmCust;
	}


	db_connect ();

	pglib_transaction("BEGIN") or errDie ("Unable to start transaction.");

		$check = getCSetting("SUPP_PAY_TYPE");
		if (!isset($check) OR strlen ($check) < 1){
			#no setting ... insert
			$ins_sql = "INSERT INTO settings (constant,label,value,type,datatype,minlen,maxlen,div,readonly) VALUES ('SUPP_PAY_TYPE','Supplier Payment Type','$pay_type','general','allstring','1','20','0','f')";
			$run_ins = db_exec($ins_sql) or errDie ("Unable to record supplier payment setting.");
		}else {
			#settings ... update
			$upd_sql = "UPDATE settings SET value = '$pay_type' WHERE constant = 'SUPP_PAY_TYPE'";
			$run_upd = db_exec($upd_sql) or errDie ("Unable to update supplier pay type setting.");
		}

		$check2 = getCSetting("SUPP_PROCESS_TYPE");
		if (!isset($check2) OR strlen($check2) < 1){
			#no setting ... insert
			$ins_sql = "INSERT INTO settings (constant,label,value,type,datatype,minlen,maxlen,div,readonly) VALUES ('SUPP_PROCESS_TYPE','Supplier Payment Process Type','$process_type','general','allstring','1','20','0','f')";
			$run_ins = db_exec($ins_sql) or errDie ("Unable to record supplier payment setting.");
		}else {
			#settings ... update
			$upd_sql = "UPDATE settings SET value = '$process_type' WHERE constant = 'SUPP_PROCESS_TYPE'";
			$run_upd = db_exec($upd_sql) or errDie ("Unable to update supplier pay type setting.");
		}

	pglib_transaction("COMMIT") or errDie ("Unable to complete transaction.");

	return get_info_setting($HTTP_POST_VARS,"<li class='err'>Supplier Settings Updated</li><br>");

}


?>