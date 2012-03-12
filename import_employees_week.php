<?

	require ("settings.php");

	if(isset($_POST["key"])){
		$OUTPUT = process_emps ($_POST);
	}else {
		$OUTPUT = get_file_location ();
	}

	require ("template.php");



function get_file_location ($err="")
{

	$display = "
					<h2>Import Weekly Employees</h2>
					$err
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST' enctype='multipart/form-data'>
						<input type='hidden' name='key' value='confirm'>
						<tr>
							<th>Select File To Import</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><input type='file' name='file_upload'></td>
						</tr>
						".TBL_BR."
						<tr>
							<th>File Must Be In This Format</th>
						</tr>
						<tr>
							<td><li class='err'>Employee Number|Surname|First Name|ID Number|Birthdate (DD/MM/YYYY)|Sex|Race|Status|Resident (Y/N)|Bank Name|Branch|Bank Account Type|Bank Account Number|Residential 1|Resesidential 2|Resesidential 3|Postal 1|Postal 2|Postal 3|Postal Code|Annual Remuneration|Salary Type|Pay Method|Hours Per Week|Starting Date|Job Title|Department|Occupation Category|Occupation Level|This Position Filled|Recruitment From|Reason For Employment|Union Name|Union Member Number|Union Position|Leave Days</li></td>
						</tr>
						".TBL_BR."
						<tr>
							<td><input type='submit' value='Import Employees'></td>
						</tr>
					</form>
					</table>
				";
	return $display;

}


function process_emps ($_POST)
{

	global $_FILES;
	extract ($_POST);

	define("EMP_YEAR", getCSetting("EMP_TAXYEAR"));

	if(!isset($_FILES['file_upload']['name']) or (strlen($_FILES['file_upload']['name']) < 1))
		return get_file_location ("<li class='err'>Please Select A File To Import.</li><br>");

	if($_FILES['file_upload']['size'] == 0)
		return get_file_location ("<li class='err'>Uploaded File Is Empty.</li><br>");

	$file_data = file ($_FILES['file_upload']['tmp_name']);

	foreach ($file_data AS $line){

		$clean_line = trim($line);
		$clean_line = str_replace("\"","",$clean_line);
		$clean_line = str_replace("'","",$clean_line);
		$clean_line = str_replace(",","",$clean_line);
		$clean_line = str_replace("&","",$clean_line);
		$clean_line = str_replace("$","",$clean_line);

		$line_arr = explode ("|", $clean_line);

//print "<pre>";
//var_dump ($line_arr);
//print "</pre>";
//die ();

		#do some reprocessing ...
		if($line_arr[5] == "Male")
			$line_arr[5] = "M";
		else 
			$line_arr[5] = "F";

		$hiredate_arr = explode("/","$line_arr[24]");
		if(strlen($hiredate_arr[2]) == "2")
			if($hiredate_arr[2] > 9)
				$hiredate_arr[2] = "19".$hiredate_arr[2];
			else 
				$hiredate_arr[2] = "20".$hiredate_arr[2];
		$hiredate = "$hiredate_arr[2]-$hiredate_arr[1]-$hiredate_arr[0]";

		if($line_arr[8] == "Y")
			$line_arr[8] = "Yes";
		else 
			$line_arr[8] = "No";

		$pay_type = $line_arr[22];

		if($line_arr[11] != "Savings")
			$line_arr[11] = "Current or Cheque";

		if(strlen($line_arr[3]) < 8){
			print "ID Number Not Set For This Data: $clean_line\n";
			continue;
		}

		$line_arr[20] = sprint ($line_arr[20]);
		$basic_sal = sprint ($line_arr[20] / 12);

		$term_date = date("Y-m-d");

		if($line_arr[27] == "machine operator"){
			$line_arr[27] = "8";
		}elseif ($line_arr[27] == "elementary"){
			$line_arr[27] = "9";
		}elseif ($line_arr[27] == "clerk"){
			$line_arr[27] = "5";
		}elseif ($line_arr[27] == "Qualified tradesman"){
			$line_arr[27] = "7";
		}else {
			$line_arr[27] = "1";
		}

		if($line_arr[28] == "semi skilled"){
			$line_arr[28] = "5";
		}elseif ($line_arr[28] == "unskilled"){
			$line_arr[28] = "6";
		}elseif ($line_arr[28] == "skilled technical"){
			$line_arr[28] = "4";
		}else {
			$line_arr[28] = "1";
		}



		$get_union = "SELECT id FROM unions WHERE union_name = '$line_arr[32]' LIMIT 1";
		$run_union = db_exec($get_union) or errDie ("Unable to get union information");
		if(pg_numrows($run_union) < 1){
			#no union .. add
			$union_add = "INSERT INTO cubit.unions (union_name,date_added,req_perc) VALUES ('$line_arr[32]','now','14.29')";
			$run_union2 = db_exec($union_add) or errDie ("Unable to add union information.");
			$union_id = pglib_lastid ("unions","id");
		}else {
			$union_id = pg_fetch_result ($run_union,0,0);
		}

		if(strtolower($line_arr[6]) == "african"){
			$line_arr[6] = "african";
		}elseif(strtolower($line_arr[6]) == "asian"){
			$line_arr[6] = "indian";
		}elseif(strtolower($line_arr[6]) == "white"){
			$line_arr[6] = "white";
		}elseif(strtolower($line_arr[6]) == "coloured"){
			$line_arr[6] = "coloured";
		}else {
			$line_arr[6] = "african";
		}






		if(strlen($line_arr[35]) < 1)
			$line_arr[35] = "0";


		#process the vars
		$empnum = "$line_arr[0]";
		$sname = "$line_arr[1]";
		$designation = "";
		$fnames = "$line_arr[2]";
		$sex = "$line_arr[5]";
		$marital = "$line_arr[7]";
		$resident = "$line_arr[8]";
		$hiredate = "$hiredate";
		$telno = "";
		$email = "";
		$hpweek = "$line_arr[23]";
		$novert = "1.5";
		$hovert = "2";
		$payprd = "m";
		$payprd_day = "fri";
		$paytype = "$pay_type";
		$bankname = "$line_arr[9]";
		$bankcode = "$line_arr[10]";
		$bankacctype = "$line_arr[11]";
		$bankaccno = "$line_arr[12]";
		$vaclea = "$line_arr[35]";
		$siclea = "$line_arr[35]";
		$stdlea = "$line_arr[35]";
		$res1 = "$line_arr[13]";
		$res2 = "$line_arr[14]";
		$res3 = "$line_arr[15]";
		$res4 = "";
		$pos1 = "$line_arr[16]";
		$pos2 = "$line_arr[17]";
		$pcode = "$line_arr[19]";
		$contsname = "";
		$contfnames = "";
		$contres1 = "";
		$contres2 = "";
		$contres3 = "";
		$contres4 = "";
		$conttelno = "";
		$idnum = "$line_arr[3]";
		$passportnum = "";
		$changelogo = "no";
		$taxref = "";
		$basic_sal = "$basic_sal";
		$saltyp = "w";
		$basic_sal_annum = "$line_arr[20]";
		$sal_bonus = "0.00";
		$all_travel = "0.00";
		$comp_uif = "1.00";
		$comp_other = "0.00";
		$comp_provident = "0.00";
		$comp_medical = "0.00";
		$comp_ret = "0.00";
		$comp_pension = "0.00";
		$emp_uif = "1.00";
		$emp_other = "0.00";
		$emp_provident = "0.00";
		$emp_medical = "0.00";
		$emp_meddeps = "0";
		$emp_ret = "0.00";
		$emp_pension = "0.00";
		$comp_sdl = "1.00";
		$sal_bonus_month = "12";
		$fringe_car1 = "0.00";
		$fringe_car1_contrib = "0.00";
		$fringe_car1_fuel = "0";
		$fringe_car1_service = "0";
		$fringe_car2_contrib = "0.00";
		$fringe_car2_fuel = "0";
		$fringe_car2_service = "0";
		$fringe_car2 = "0.00";
		$department = "1";
		$occ_cat = "$line_arr[27]";
		$occ_level = "$line_arr[28]";
		$pos_filled = "2";
		$temporary = "no";
		$termination_date = "$term_date";
		$recruitment_from = "$line_arr[30]";
		$employment_reason = "$line_arr[31]";
		$union_name = "$union_id";
		$union_mem_num = "None";
		$union_pos = "None";
		$race = "$line_arr[6]";
		$disabled_stat = "No";
		$prevemp_remun = "0";
		$prevemp_tax = "0";

		$get_ccs = "SELECT * FROM costcenters";
		$run_ccs = db_exec($get_ccs) or errDie ("Unable to get costcenter information.");
		if(pg_numrows($run_ccs) < 1){
			$ct_arr = array ();
		}else {
			$ct_arr = array ();
			while ($ccarr = pg_fetch_array ($run_ccs)){
				$ct_arr[$ccarr['ccid']] = "";
			}
		}

		if(strlen($res1) < 1){
			#res 1 not set ...
			$res1 = "Not Set";
		}
		writeEmp(
			array (
				"key" => "write",
				"empnum" => "$empnum",
				"sname" => "$sname",
				"designation" => "$designation",
				"fnames" => "$fnames",
				"sex" => "$sex",
				"marital" => "$marital",
				"resident" => "$resident",
				"hiredate" => "$hiredate",
				"telno" => "$telno",
				"email" => "$email",
				"hpweek" => "$hpweek",
				"novert" => "$novert",
				"hovert" => "$hovert",
				"payprd" => "$payprd",
				"payprd_day" => "$payprd_day",
				"paytype" => "$paytype",
				"bankname" => "$bankname",
				"bankcode" => "$bankcode",
				"bankacctype" => "$bankacctype",
				"bankaccno" => "$bankaccno",
				"vaclea" => "$vaclea",
				"siclea" => "$siclea",
				"stdlea" => "$stdlea",
				"res1" => "$res1",
				"res2" => "$res2",
				"res3" => "$res3",
				"res4" => "$res4",
				"pos1" => "$pos1",
				"pos2" => "$pos2",
				"pcode" => "$pcode",
				"contsname" => "$contsname",
				"contfnames" => "$contfnames",
				"contres1" => "$contres1",
				"contres2" => "$contres2",
				"contres3" => "$contres3",
				"contres4" => "$contres4",
				"conttelno" => "$conttelno",
				"idnum" => "$idnum",
				"passportnum" => "$passportnum",
				"changelogo" => "$changelogo",
				"taxref" => "$taxref",
				"basic_sal" => "$basic_sal",
				"saltyp" => "$saltyp",
				"basic_sal_annum" => "$basic_sal_annum",
				"sal_bonus" => "$sal_bonus",
				"all_travel" => "$all_travel",
				"comp_uif" => "$comp_uif",
				"comp_other" => "$comp_other",
				"comp_provident" => "$comp_provident",
				"comp_medical" => "$comp_medical",
				"comp_ret" => "$comp_ret",
				"comp_pension" => "$comp_pension",
				"emp_uif" => "$emp_uif",
				"emp_other" => "$emp_other",
				"emp_provident" => "$emp_provident",
				"emp_medical" => "$emp_medical",
				"emp_meddeps" => "$emp_meddeps",
				"emp_ret" => "$emp_ret",
				"emp_pension" => "$emp_pension",
				"comp_sdl" => "$comp_sdl",
				"sal_bonus_month" => "$sal_bonus_month",
				"fringe_car1" => "$fringe_car1",
				"fringe_car1_contrib" => "$fringe_car1_contrib",
				"fringe_car1_fuel" => "$fringe_car1_fuel",
				"fringe_car1_service" => "$fringe_car1_service",
				"fringe_car2_contrib" => "$fringe_car2_contrib",
				"fringe_car2_fuel" => "$fringe_car2_fuel",
				"fringe_car2_service" => "$fringe_car2_service",
				"fringe_car2" => "$fringe_car2",
				"department" => "$department",
				"occ_cat" => "$occ_cat",
				"occ_level" => "$occ_level",
				"pos_filled" => "$pos_filled",
				"temporary" => "$temporary",
				"termination_date" => "$termination_date",
				"recruitment_from" => "$recruitment_from",
				"employment_reason" => "$employment_reason",
				"union_name" => "$union_name",
				"union_mem_num" => "$union_mem_num",
				"union_pos" => "$union_pos",
				"race" => "$race",
				"disabled_stat" => "$disabled_stat",
				"prevemp_remun" => "$prevemp_remun",
				"prevemp_tax" => "$prevemp_tax",
				"ct" => "$ct_arr"
			)
		);

	
	}

	return "<br><li class='err'>Import Complete.</li>";
}


# write to database
function writeEmp ($_POST)
{

	$_POST = var_makesafe($_POST);
	global $_FILES;

	extract ($_POST);

	$comp_pension+=0;
	$emp_pension+=0;
	$comp_ret+=0;
	$emp_ret+=0;
	$comp_medical+=0;
	$emp_medical+=0;

	# validate input
	require_lib("validate");
	$v = new validate ();
	# Limit field lengths as per database settings
	$v->isOk ($empnum,"string",0,20,"Invalid emp num");
	$v->isOk ($sname, "string", 1, 50, "Invalid surname.");
	$v->isOk ($fnames, "string", 1, 50, "Invalid first names.");
	$v->isOk ($sex, "string", 1, 1, "Invalid sex.");
	$v->isOk ($marital, "string", 0, 10, "Invalid marital status.");
	$v->isOk ($designation, "string", 0, 100, "Invalid designation.");
	$v->isOk ($changelogo, "string", 1, 3, "Invalid image selection.");
	$v->isOk ($resident, "string", 1, 5, "Invalid residential status.");
	$v->isOk ($hiredate, "date", 1, 10, "Invalid hire date.");
	$v->isOk ($telno, "string", 0, 30, "Invalid telephone no.");
	$v->isOk ($email, "email", 0, 255, "Invalid email address.");
	$v->isOk ($basic_sal, "float", 1, 9, "Invalid basic salary.");
	$v->isOk ($hpweek, "float", 1, 5, "Invalid hours per week.");
	$v->isOk ($saltyp, "string", 1, 2, "Invalid payment period.");
	$v->isOk ($novert, "float", 1, 9, "Invalid normal overtime.");
	$v->isOk ($hovert, "float", 1, 9, "Invalid holiday overtime.");
	$v->isOk ($paytype, "string", 1, 15, "Invalid pay type.");
	$v->isOk ($bankname, "string", 0, 50, "Invalid bank name.");
	$v->isOk ($bankcode, "string", 0, 8, "Invalid branch code.");
	$v->isOk ($bankacctype, "string", 0, 50, "Invalid bank account type.");
	$v->isOk ($bankaccno, "num", 0, 50, "Invalid bank account no.");
	$v->isOk ($vaclea, "num", 1, 5, "Invalid vacation leave days.");
	$v->isOk ($siclea, "num", 1, 5, "Invalid sick leave days.");
	$v->isOk ($stdlea, "num", 1, 5, "Invalid study leave days.");
	$v->isOk ($res1, "string", 1, 50, "Invalid residential address. (line 1)");
	$v->isOk ($res2, "string", 0, 50, "Invalid residential address. (line 2)");
	$v->isOk ($res3, "string", 0, 50, "Invalid residential address. (line 3)");
	$v->isOk ($res4, "string", 0, 50, "Invalid residential address. (line 4)");
	$v->isOk ($pos1, "string", 0, 50, "Invalid postal address. (line 1)");
	$v->isOk ($pos2, "string", 0, 50, "Invalid postal address. (line 2)");
	$v->isOk ($pcode, "strin", 0, 16, "Invalid postal code.");
	$v->isOk ($contsname, "string", 0, 50, "Invalid contact surname.");
	$v->isOk ($contfnames, "string", 0, 50, "Invalid first names.");
	$v->isOk ($contres1, "string", 0, 50, "Invalid contact address. (line 1)");
	$v->isOk ($contres2, "string", 0, 50, "Invalid contact address. (line 2)");
	$v->isOk ($contres3, "string", 0, 50, "Invalid contact address. (line 3)");
	$v->isOk ($contres4, "string", 0, 50, "Invalid contact address. (line 4)");
	$v->isOk ($conttelno, "string", 0, 30, "Invalid contact telephone no.");
	$v->isOk ($idnum.$passportnum, "string", 1, 30, "Invalid id/passport num (VAL).");
	if (!empty($idnum)) {
		$v->isOk ($idnum, "string", 6, 30, "Invalid id number.");
	}
	$v->isOk ($taxref, "string", 0, 30, "Invalid tax ref no.");

	$v->isOk ($department, "string", 0, 50, "Invalid department");
	$v->isOk ($occ_cat, "string", 0, 50, "Invalid Occupational Category");
	$v->isOk ($occ_level, "string", 0, 50, "Invalid Occupational Level");
	$v->isOk ($pos_filled, "string", 0, 50, "Invalid Position Files");
	$v->isOk ($temporary, "string", 0, 50, "Invalid Temporary Data");
	$v->isOk ($termination_date, "date", 1, 10, "$termination_date Invalid termination date.");
	$v->isOk ($recruitment_from, "string", 0, 50, "Invalid Recruitment From");
	$v->isOk ($employment_reason, "string", 0, 50, "Invalid Employment Reason");
	$v->isOk ($union_name, "string", 0, 50, "Invalid Union Name");
	$v->isOk ($union_mem_num, "string", 0, 50, "Invalid Union Member Name");
	$v->isOk ($union_pos, "string", 0, 50, "Invalid Union Position");
	$v->isOk ($race, "string", 0, 50, "Invalid Race");
	$v->isOk ($disabled_stat, "string", 0, 50, "Invalid Disabled Status");

	if ( strlen($idnum) >= 6 ) {
		$bd_year = substr($idnum, 0, 2);
		$bd_month = substr($idnum, 2, 2);
		$bd_day = substr($idnum, 4, 2);

		if ( ! (is_numeric($bd_year) && is_numeric($bd_month) && is_numeric($bd_day) &&
					checkdate($bd_month, $bd_day, $bd_year)) ) {
			$v->addError("", "Invalid id num (BD).");
		}
	}

	if(isset($allowances)){
			foreach($allowances as $key => $value){
					$v->isOk ($allowances[$key], "float", 0, 20, "Invalid allowance amount ".($key+1).".");
			}
	}
	if(isset($deductid)){
			foreach($deductid as $key => $value){
					$v->isOk ($deductid[$key], "num", 1, 20, "Invalid deductions ID.");
			}
	}
	if(isset($deductions)){
			foreach($deductions as $key => $value){
					$v->isOk ($deductions[$key], "float", 0, 20, "Invalid deduction amount".($key+1).".");
			}
	}
	if(isset($allowid)){
			foreach($allowid as $key => $value){
					$v->isOk ($allowid[$key], "num", 1, 20, "Invalid allowance ID.");
			}
	}
	if(isset($allowtax)){
			foreach($allowtax as $key => $value){
					$v->isOk ($allowtax[$key], "string", 2, 20, "Invalid allowance tax ".($key+1).".");
			}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>$e[msg]</li>";
		}

		print $confirmCust;
		print "<br>ERROR";
		die;
	}

	$basic_sal = sprint($basic_sal);

	$expacc_provident = gethook("accnum", "salacc", "name", "providentexpense");
	$expacc_ret = gethook("accnum", "salacc", "name", "retireexpense");
	$expacc_pension = gethook("accnum", "salacc", "name", "pensionexpense");
	$expacc_uif = gethook("accnum", "salacc", "name", "uifexp");
	$expacc_medical = gethook("accnum", "salacc", "name", "medicalexpense");
	$expacc_salwages = gethook("accnum", "salacc", "name", "salaries");
	$expacc_sdl = gethook("accnum", "salacc", "name", "sdlexp");
	$expacc_reimburs = 0;//gethook("accnum", "salacc", "name", "allreimburs");

	/* FOR AUDITING PURPOSES THESE VALUES HAVE BEEN HARDCODED */
	$comp_sdl = 1;
	$comp_uif = 1;
	$emp_uif = 1;
	/* DONE */

	if ($resident=="Yes") {$resident="TRUE";} else {$resident="FALSE";}
	db_conn("cubit");
	$sql = "INSERT INTO cubit.employees (sname, fnames, sex, marital, resident, hiredate, telno, email, basic_sal, saltyp, hpweek, novert, hovert, payprd,payprd_day,
				paytype, bankname, bankcode, bankacctype, bankaccno, vaclea, siclea, stdlea, res1, res2, res3, res4, pos1, pos2, pcode, contsname, contfnames, contres1,
				contres2, contres3, conttelno, div,idnum, passportnum, taxref,enum,designation,balance,comp_pension,emp_pension,comp_ret,emp_ret,comp_medical,emp_medical,
				emp_meddeps,sal_bonus, sal_bonus_month, basic_sal_annum, all_travel, comp_uif, comp_sdl, comp_other, comp_provident, emp_uif, emp_other, emp_provident,
				expacc_provident, expacc_ret, expacc_pension, expacc_uif, expacc_medical, expacc_other, expacc_salwages, expacc_sdl, expacc_reimburs, department, occ_cat, occ_level,
				pos_filled, temporary, termination_date, recruitment_from, employment_reason, union_name, union_mem_num, union_pos, race, disabled_stat,
				fringe_car1, fringe_car1_contrib, fringe_car1_fuel, fringe_car1_service,
				fringe_car2, fringe_car2_contrib, fringe_car2_fuel, fringe_car2_service,
				prevemp_remun, prevemp_tax, cyear)
			VALUES ('$sname', '$fnames', '$sex', '$marital', '$resident', '$hiredate', '$telno',
				'$email', '$basic_sal', '$saltyp', '$hpweek', '$novert', '$hovert', '$payprd','$payprd_day', '$paytype', '$bankname', '$bankcode', '$bankacctype', '$bankaccno', '$vaclea',
				'$siclea', '$stdlea', '$res1', '$res2', '$res3', '$res4', '$pos1', '$pos2', '$pcode', '$contsname', '$contfnames', '$contres1', '$contres2', '$contres3', '$conttelno',
				'".USER_DIV."','$idnum', '$passportnum', '$taxref','$empnum','$designation',0,'$comp_pension','$emp_pension','$comp_ret','$emp_ret','$comp_medical','$emp_medical','$emp_meddeps',
				'$sal_bonus', '$sal_bonus_month', '$basic_sal_annum', '$all_travel', '$comp_uif', '$comp_sdl', '$comp_other', '$comp_provident', '$emp_uif', '$emp_other', '$emp_provident',
				'$expacc_provident', '$expacc_ret', '$expacc_pension', '$expacc_uif', '$expacc_medical', '0', '$expacc_salwages', '$expacc_sdl', '$expacc_reimburs', '$department', '$occ_cat', '$occ_level',
				'$pos_filled', '$temporary', '$termination_date', '$recruitment_from', '$employment_reason', '$union_name', '$union_mem_num', '$union_pos', '$race', '$disabled_stat',
				'$fringe_car1', '$fringe_car1_contrib', '$fringe_car1_fuel', '$fringe_car1_service',
				'$fringe_car2', '$fringe_car2_contrib', '$fringe_car2_fuel', '$fringe_car2_service',
				'$prevemp_remun', '$prevemp_tax', '".EMP_YEAR."')";

	$nwEmpRslt = db_exec ($sql) or errDie ("Unable to add new employee.");

	if($empnum=="") {
		$not="Yes";
	}else {
		$not="No";
	}

	$empnum = pglib_lastid ("employees", "empnum");

	if ( isset($allowid) ) {
		# Remove old details
		$sql = "DELETE FROM empallow WHERE empnum = '$empnum' AND div = '".USER_DIV."'";
		$allowRslt = db_exec($sql);

		# write Allowances to db
		foreach($allowid as $i => $id){
			if ( empty($allowances[$i]) || $allowances[$i] == 0 ) continue;
			# Insert new records
			$sql = "INSERT INTO empallow (allowid, empnum, type, amount, accid, div) VALUES ('$id', '$empnum','$allowtype[$i]', '$allowances[$i]', '$allowaccid[$i]', '".USER_DIV."')";
			$allowRslt = db_exec ($sql) or errDie ("Unable to process Employee allowances in database.");
		}

		# delete empallow with zeros on the amount
		$sql = "DELETE FROM empallow WHERE amount=0 AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql);
	}

	if (isset($subsname)) {
		$inssub = new dbUpdate("emp_subsistence", "cubit");

		foreach ($subsname as $sid => $sn) {
			if ($subsamt[$sid] == 0) {
				continue;
			}

			$cols = grp(
				m("subid", $sid),
				m("empnum", $empnum),
				m("amount", $subsamt[$sid]),
				m("days", $subsdays[$sid]),
				m("accid", $subsacc[$sid])
			);

			$inssub->setOpt($cols);
			$inssub->run(DB_INSERT);
		}
	}

	if( isset($deductid) ){
		# write Deductions to db
		foreach( $deductid as $i => $id ) {
			$sql = "SELECT * FROM empdeduct WHERE dedid='$id' AND empnum='$empnum'";
			$rslt = db_exec($sql) or errDie("Error writing deduction.");

			if ( empty($deductions[$i]) || $deductions[$i] == 0 ) continue; //$deductions[$i] = 0;
			if ( empty($comp_deductions[$i]) ) $comp_deductions[$i] = 0;

			if ( pg_num_rows($rslt) > 0 ) {
				$sql = "UPDATE empdeduct SET amount='$deductions[$i]'";
			} else {
				$sql = "INSERT INTO empdeduct (dedid, empnum, amount,
							employer_amount, employer_type, div, type, accid)
						VALUES ('$id', '$empnum', '$deductions[$i]', '$comp_deductions[$i]',
							'$deducttype[$i]', '".USER_DIV."', '$deducttype[$i]', '$deductaccid[$i]')";
			}
			$rslt = db_exec ($sql) or errDie ("Unable to process Employee deductions in database.");
		}
	}

	if ( isset($fringebens) ) {
		foreach ( $fringeid as $i => $id ) {
			if ( empty($fringebens[$i]) || $fringebens[$i] == 0 ) continue;

			$sql = "SELECT * FROM empfringe WHERE fringeid='$id' AND empnum='$empnum'";
			$rslt = db_exec($sql) or errDie("Error writing fringe benefit.");

			if ( pg_num_rows($rslt) > 0 ) {
				$sql = "UPDATE empfringe SET amount='$fringebens[$i]'";
			} else {
				$sql = "INSERT INTO empfringe (fringeid, empnum, amount, type, accid, div)
						VALUES('$id', '$empnum', '$fringebens[$i]', '$fringetype[$i]', '$fringeexpacc[$i]', '".USER_DIV."')";
			}

			$rslt = db_exec($sql) or errDie("Error writing fringe benefit.");
		}
	}

	# deal with logo image
        if ($changelogo == "yes") {
		if (empty ($_FILES["logo"])) {
			return "<li class=err> Please select an image to upload from your hard drive.";
		}
		if (is_uploaded_file ($_FILES["logo"]["tmp_name"])) {
			# Check file ext
			if (preg_match ("/(image\/jpeg|image\/png|image\/gif)/", $_FILES["logo"]["type"], $extension)) {
				$type = $_FILES["logo"]["type"];

				// open file in "read, binary" mode
				$img = "";
				$file = fopen ($_FILES['logo']['tmp_name'], "rb");
				while (!feof ($file)) {
					// fread is binary safe
					$img .= fread ($file, 1024);
				}
				fclose ($file);
				# base 64 encoding
				$img = base64_encode($img);

				db_connect();
				$Sl="INSERT INTO eimgs (emp,image, imagetype) VALUES('$empnum','$img','$type')";
				$Ry=db_exec($Sl) or errDie("Unable to upload company logo Image to DB.",SELF);

				# to show IMG
				//$logoimg = "<br><img src='compinfo/getimg.php' width=230 height=47><br><br>";
				//$logo = "compinfo/getimg.php";
			}else {
				return "<li class='err'>Please note that we only accept images of the types PNG,GIF and JPEG.";
			}
		} else {
			return "Unable to upload file, Please check file permissions.";
		}
	}

	if($not=="Yes") {
		$Sl="UPDATE employees SET enum='$empnum' WHERE empnum='$empnum'";
		$Ry=db_exec($Sl) or errDie("unable to update employees.");

	}

	db_conn('cubit');

	$Sl="SELECT * FROM costcenters";
	$Ri=db_exec($Sl);

	$Sl="DELETE FROM empc WHERE emp='$empnum'";
	$Rl=db_exec($Sl);

	while($data=pg_fetch_array($Ri)) {
		if($ct[$data['ccid']]>0) {
			$Sl="INSERT INTO empc(cid,emp,amount) VALUES ('$data[ccid]','$empnum','".$ct[$data['ccid']]."')";
			$Rl=db_exec($Sl);
		}
	}

	print ".";

}


?>