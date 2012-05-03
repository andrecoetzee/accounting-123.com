<?

require ("settings.php");

if (isset($_REQUEST["key"])){
	switch ($_REQUEST["key"]){
		case "confirm":
			$OUTPUT = export_file ();
			break;
		default:
			$OUTPUT = get_employer ($_POST);
	}
}else {
	$OUTPUT = get_employer ($_POST);
}

require ("template.php");



function get_employer ($_POST,$err="")
{

	global $_SESSION;
	extract ($_POST);

	db_connect();

	$sql ="SELECT * FROM compinfo WHERE div = '".$_SESSION["USER_DIV"]."'";
	$Rslt = db_exec($sql);
	$com = pg_fetch_array($Rslt);

	if (!isset($employer_company)) 
		$employer_company = COMP_NAME;
	if (!isset($employer_paye)) 
		$employer_paye = COMP_PAYE;
	if (!isset($employer_sdl)) 
		$employer_sdl = $com['sdl'];
	if (!isset($employer_uif)) 
		$employer_uif = $com['uif'];
	if (!isset($employer_tel)) 
		$employer_tel = $com['tel'];
	if (!isset($employer_alttel)) 
		$employer_alttel = $com['tel'];
	if (!isset($employer_addr1)) 
		$employer_addr1 = $com['addr1'];
	if (!isset($employer_addr2)) 
		$employer_addr2 = $com['addr2'];
	if (!isset($employer_addr3)) 
		$employer_addr3 = $com['addr3'];
	if (!isset($employer_postalcode)) 
		$employer_postalcode = $com['postcode'];
	if (!isset($employer_processyear)) 
		$employer_processyear = date ("Y");

	#company info for easyfile system ...

	$display = "
		<h3>Generate CSV File For SARS efiling</h3>
		<form action='".SELF."' method='POST'>
			$err
			<input type='hidden' name='key' value='confirm'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td><li class='err'>Please Ensure These Values Are Correct And Contain No Spaces</li></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<th>Company Name</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='40' name='employer_company' value='$employer_company'></td>
			</tr>
			<tr>
				<th>Address</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='40' name='employer_addr1' value='$employer_addr1'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='40' name='employer_addr2' value='$employer_addr2'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='40' name='employer_addr3' value='$employer_addr3'></td>
			</tr>
			<tr>
				<th>Postal Code</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='5' maxlength='4' name='employer_postalcode' value='$employer_postalcode'></td>
			</tr>
			<tr>
				<th>PAYE Ref No</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='40' name='employer_paye' value='$employer_paye'></td>
			</tr>
			<tr>
				<th>SDL No</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='40' name='employer_sdl' value='$employer_sdl'></td>
			</tr>
			<tr>
				<th>UIF No</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='40' name='employer_uif' value='$employer_uif'></td>
			</tr>
			<tr>
				<th>Tel</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='40' name='employer_tel' value='$employer_tel'></td>
			</tr>
			<tr>
				<th>Alternative Tel</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='40' name='employer_alttel' value='$employer_alttel'></td>
			</tr>
			<tr>
				<th>Process</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>
					<select name='employer_runstatus'>
						<option value='TEST'>Test</option>
						<option value='LIVE'>Live</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>Process Year</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'><input type='text' size='5' maxlength='4' name='employer_processyear' value='$employer_processyear'></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' value='Proceed'></td>
			</tr>
		</table>
		</form>";
	return $display;

}

function export_file ()
{

	extract ($_POST);


	require_lib("validate");

	$v = new validate ();
	$v->isOk ($employer_company, "string", 1, 70, "Invalid company name or company name too long (70)");
	$v->isOk ($employer_tel, "string", 1, 16, "Invalid telephone number.");
	$v->isOk ($employer_alttel, "string", 0, 16, "Invalid alternative telephone number.");
	$v->isOk ($employer_paye, "string", 8, 11, "Invalid paye number.");
	$v->isOk ($employer_sdl, "string", 0, 30, "Invalid sdl number.");
	$v->isOk ($employer_uif, "string", 0, 30, "Invalid uif number.");
	$v->isOk ($employer_addr1, string, 1, 35, "Invalid Employer Address (1).");
	$v->isOk ($employer_addr2, string, 0, 35, "Invalid Employer Address (2).");
	$v->isOk ($employer_addr3, string, 0, 35, "Invalid Employer Address (3).");
	$v->isOk ($employer_postalcode, string, 4, 4, "Invalid Employer Address Postal Code.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return get_employer($_POST,$confirm);
	}

	$gendate = date ("Ymd");

	$file = "";
	$code_count = 0;
	$amount_count = 0;
	$record_count = 0;

	$file .= "1010,\"$employer_company\",1020,$employer_paye,1030,\"".substr($employer_company,0,30)."\",1040,\"$employer_tel\",1050,\"$employer_alttel\",1060,\"$employer_addr1\",1070,\"$employer_addr2\",1080,\"$employer_addr3\",1100,$employer_postalcode,1110,$gendate,1120,0002,1130,\"$employer_runstatus\",9999\n";
	$record_count++;

	$file .= "2010,\"$employer_company\",2020,$employer_paye,2030,$employer_processyear,2040,\"$employer_addr1\",2050,\"$employer_addr2\",2080,$employer_postalcode,9999\n";
	$record_count++;

	$code_count += 2010;
	$code_count += 2020;
	$code_count += 2030;
	$code_count += 2040;
	$code_count += 2050;
	$code_count += 2080;
	$code_count += 9999;


	db_connect ();

	$get_emp = "SELECT * FROM employees";
	$run_emp = db_exec ($get_emp) or errDie ("Unable to get employee information.");
	if (pg_numrows ($run_emp) < 1){
		#no employees ????? what are we doing then ??
		return get_employer ();
	}else {
		$counter = 0;


		while ($emp = pg_fetch_array ($run_emp)){

			#get initials ...
			$initials = "";
			$init_arr = explode (" ", $emp['fnames']);
			foreach ($init_arr AS $each){
				$initials .= ucwords(substr($each,0,1));
			}

			#we only have 1 payroll ...
			$run_number = str_pad ($counter,8,"0",'STR_PAD_LEFT');

			$file .= "3010,\"$run_number\",3020,\"$emp[person_nature]\",3030,\"".substr($emp['sname'],0,120)."\",3040,\"".substr($emp['fnames'],0,90)."\",3050,\"$initials\",";
			$code_count += 3010;
			$code_count += 3020;
			$code_count += 3030;
			$code_count += 3040;
			$code_count += 3050;

			if ($emp['person_nature'] == "A" OR $emp['person_nature'] == "C" OR $emp['person_nature'] == "M"){
				$file .= "3060,$emp[idnum],";
				$code_count += 3060;
			}else {
				$file .= "3070,$emp[passport_number],";
				$code_count += 3070;
			}

//3080,
//19671011,

			if (!isset($emp['pos1']) OR strlen ($emp['pos1']) < 1)
				$emp['pos1'] = "0000";

			$file .= "3110,\"$emp[res1]\",3120,\"$emp[res2]\",3150,\"$emp[pos1]\",";
			$code_count += 3110;
			$code_count += 3120;
			$code_count += 3150;

			#employed from date (hiredate)
			$file .= "3170,".str_replace ("-","",$emp['hiredate']).",";
			$code_count += 3170;

			#employed to date (firedate)
			if (strlen ($emp['firedate']) > 0){
				$emptodate = str_replace ("-","",$emp['firedate']);
			}else {
				#last day of tax year
				$emptodate = date ("Ymd");
			}
			$file .= "3180,$emptodate,";
			$code_count += 3180;

			#pay periods
			switch ($emp['payprd']){
				case "d":
					$payprd = "365.0000";
					break;
				case "f":
					$payprd = "26.0000";
					break;
				case "m":
					$payprd = "12.0000";
					break;
				case "w":
					$payprd = "52.0000";
					break;
				default:
					$payprd = "12.0000";
			}
			$file .= "3200,$payprd,";
			$code_count += 3200;

			#total processed sals ...
			$get_paid = "SELECT count(id) FROM salpaid WHERE empnum = '$emp[empnum]' AND cyear = '$emp[cyear]'";
			$run_paid = db_exec ($get_paid) or errDie ("Unable to get paid salaries");
			$salpaid = pg_fetch_result ($run_paid,0,0);
			$get_rev = "SELECT count(id) FROM salr WHERE empnum = '$emp[empnum]' AND cyear = '$emp[cyear]'";
			$run_rev = db_exec ($get_rev) or errDie ("Unable to get reversed salaries.");
			$salrev = pg_fetch_result ($run_rev,0,0);
			$salprocs = $salpaid - $salrev;
			$file .= "3210,$salprocs.0000,";
			$code_count += 3210;

			$get_ptot = "SELECT sum(salary) FROM salpaid WHERE empnum = '$emp[empnum]' AND cyear = '$emp[cyear]'";
			$run_ptot = db_exec ($get_ptot) or errDie ("Unable to get employee payment information.");
			$ptot = pg_fetch_array ($run_ptot,0,0);
			$get_rtot = "SELECT sum (salary) FROM salr WHERE empnum = '$emp[empnum]' AND cyear = '$emp[cyear]'";
			$run_rtot = db_exec ($get_rtot) or errDie ("Unable to get reversed salary information.");
			$rtot = pg_fetch_result ($run_rtot,0,0);
			$total_sal = sprint ($ptot-$rtot);
			$file .= "3601,\"Y\",$total_sal,";
			$code_count += 3601;
			$amount_count += $total_sal;

			$get_pbon = "SELECT sum(bonus) FROM salpaid WHERE empnum = '$emp[empnum]' AND cyear = '$emp[cyear]'";
			$run_pbon = db_exec ($get_pbon) or errDie ("Unable to get employee bonus information.");
			$pbon = pg_fetch_array ($run_pbon,0,0);
			$get_rbon = "SELECT sum (bonus) FROM salr WHERE empnum = '$emp[empnum]' AND cyear = '$emp[cyear]'";
			$run_rbon = db_exec ($get_rbon) or errDie ("Unable to get reversed bonus information.");
			$rbon = pg_fetch_result ($run_rbon,0,0);
			$total_bon = sprint ($pbon-$rbon);
			$file .= "3605,,$total_bon,";
			$code_count += 3605;
			$amount_count += $total_bon;

			$get_trav = "SELECT sum (amount) FROM emp_inc WHERE emp = '$emp[empnum]' AND year = '$emp[cyear]' AND type = 'INCT' AND description = 'Travel Allowance'";
			$run_trav = db_exec ($get_trav) or errDie ("Unable to get employee travel allowance information.");
			$trav_allow = sprint (pg_fetch_result ($run_trav,0,0));
			$trav_allow = substr(sprint ($trav_allow),0,strpos(sprint($trav_allow),"."));
			$file .= "3701,,$trav_allow,";
			$code_count += 3701;
			$amount_count += $trav_allow;

			$get_allow = "SELECT sum (amount) FROM emp_inc WHERE emp = '$emp[empnum]' AND year = '$emp[cyear]' AND type != 'Basic Salary' AND type != 'Travel Allowance'";
			$run_allow = db_exec ($get_allow) or errDie ("Unable to get allowances amount");
			$tot_allow = sprint (pg_fetch_result ($run_allow,0,0));
			$tot_allow = substr(sprint ($tot_allow),0,strpos(sprint($tot_allow),"."));
			$file .= "3712,,$tot_allow,";
			$code_count += 3712;
			$amount_count += $tot_allow;

//ONLY FOR FOREIGN SERVICE INCOME
//			$get_med = "SELECT sum(amount) FROM emp_com WHERE emp = '$emp[empnum]' AND year = '$emp[cyear]' AND type = 'COMM' AND description = 'Medical Contribution'";
//			$run_med = db_exec ($get_med) or errDie ("Unable to get medical aid information.");
//			$med_aid = sprint (pg_fetch_result ($run_med,0,0));
//			$med_aid = substr(sprint ($med_aid),0,strpos(sprint($med_aid),"."));
//			$file .= "3810,,$med_aid,";
//			$code_count += 3810;
//			$amount_count += $med_aid;

			$file .= "3695,".($emp['prevemp_remun'] + $salprocs).",";
			$code_count += 3695;
			$amount_count += ($emp['prevemp_remun'] + $salprocs);

//			$get_emp_ret = "SELECT sum(amount) FROM emp_ded WHERE emp = '$emp[empnum]' AND year = '$emp[cyear]' AND type = 'DEDR' AND description = 'Retirement Annuity Fund'";
//			$run_emp_ret = db_exec ($get_emp_ret) or errDie ("Unable to get employee retirement information.");
//			$emp_ret = sprint (pg_fetch_result ($run_emp_ret,0,0));
//			$emp_ret = substr(sprint ($emp_ret),0,strpos(sprint($emp_ret),"."));
			$emp_ret = 0;
			$file .= "3697,$emp_ret,";
			$code_count += 3697;
			$amount_count += $emp_ret;

			$tot_gross = $total_sal + $total_bon + $trav_allow + $tot_allow + ($emp['prevemp_remun'] + $salprocs);
			$file .= "3698,$tot_gross,";
			$code_count += 3698;
			$amount_count += $tot_gross;

			$file .= "3699,$tot_gross,";
			$code_count += 3699;
			$amount_count += $emp_ret;
//3696,
//8426,


//3697,
//145833,

//3698,
//92494,

//3699,
//238327,

			$get_emp_pen = "SELECT sum(amount) FROM emp_ded WHERE emp = '$emp[empnum]' AND year = '$emp[cyear]' AND type = 'DEDP' AND description = 'Pension'";
			$run_emp_pen = db_exec ($get_emp_pen) or errDie ("Unable to get employee pension information.");
			$emp_pension = sprint (pg_fetch_result ($run_emp_pen,0,0));
			$emp_pension = substr(sprint ($emp_pension),0,strpos(sprint($emp_pension),"."));
			$file .= "4001,,$emp_pension,";
			$code_count += 4001;
			$amount_count += $emp_pension;

			$get_emp_prov = "SELECT sum(amount) FROM emp_ded WHERE emp = '$emp[empnum]' AND year = '$emp[cyear]' AND type = 'DEDV' AND description = 'Provident'";
			$run_emp_prov = db_exec ($get_emp_pen) or errDie ("Unable to get employee provident information.");
			$emp_provident = sprint (pg_fetch_result ($run_emp_prov,0,0));
			$emp_provident = substr(sprint ($emp_provident),0,strpos(sprint($emp_provident),"."));
			$file .= "4003,,$emp_provident,";
			$code_count += 4003;
			$amount_count += $emp_provident;

			$get_emp_med = "SELECT sum(amount) FROM emp_ded WHERE emp = '$emp[empnum]' AND year = '$emp[cyear]' AND type = 'DEDM' AND description = 'Medical Contribution'";
			$run_emp_med = db_exec ($get_emp_med) or errDie ("Unable to get employee medical information.");
			$emp_med = sprint (pg_fetch_result ($run_emp_med,0,0));
			$emp_med = substr(sprint ($emp_med),0,strpos(sprint($emp_med),"."));
			$file .= "4005,$emp_med,";
			$code_count += 4005;
			$amount_count += $emp_med;

			$get_emp_ret = "SELECT sum(amount) FROM emp_ded WHERE emp = '$emp[empnum]' AND year = '$emp[cyear]' AND type = 'DEDR' AND description = 'Retirement Annuity Fund'";
			$run_emp_ret = db_exec ($get_emp_ret) or errDie ("Unable to get employee retirement information.");
			$emp_ret = sprint (pg_fetch_result ($run_emp_ret,0,0));
			$emp_ret = substr(sprint ($emp_ret),0,strpos(sprint($emp_ret),"."));
			$file .= "4006,,$emp_ret,";
			$code_count += 4006;
			$amount_count += $emp_ret;

			$get_comp_pen = "SELECT sum(amount) FROM emp_com WHERE emp = '$emp[empnum]' AND year = '$emp[cyear]' AND type = 'COMP' AND description = 'Pension'";
			$run_comp_pen = db_exec ($get_comp_pen) or errDie ("Unable to get company pension information.");
			$comp_pension = sprint (pg_fetch_result ($run_comp_pen,0,0));
			$comp_pension = substr(sprint ($comp_pension),0,strpos(sprint($comp_pension),"."));
			$file .= "4472,,$comp_pension,";
			$code_count += 4472;
			$amount_count += $comp_pension;

			$get_comp_prov = "SELECT sum(amount) FROM emp_com WHERE emp = '$emp[empnum]' AND year = '$emp[cyear]' AND type = 'COMV' AND description = 'Provident'";
			$run_comp_prov = db_exec ($get_comp_prov) or errDie ("Unable to get company provident information.");
			$comp_provident = sprint (pg_fetch_result ($run_comp_prov,0,0));
			$comp_provident = substr(sprint ($comp_provident),0,strpos(sprint($comp_provident),"."));
			$file .= "4473,,$comp_provident,";
			$code_count += 4473;
			$amount_count += $comp_provident;

			$get_comp_med = "SELECT sum(amount) FROM emp_com WHERE emp = '$emp[empnum]' AND year = '$emp[cyear]' AND type = 'COMM' AND description = 'Medical Contribution'";
			$run_comp_med = db_exec ($get_comp_med) or errDie ("Unable to get company provident information.");
			$comp_med = sprint (pg_fetch_result ($run_comp_med,0,0));
			$comp_med = substr(sprint ($comp_med),0,strpos(sprint($comp_med),"."));
			$file .= "4474,,$comp_med,";
			$code_count += 4474;
			$amount_count += $comp_med;

//4486,
//,
//7980,

//4101,
//2625.00,

			$get_ppaye = "SELECT sum(paye) FROM salpaid WHERE empnum = '$emp[empnum]' AND cyear = '$emp[cyear]'";
			$run_ppaye = db_exec ($get_ppaye) or errDie ("Unable to get paye information.");
			$ppaye = sprint (pg_fetch_result ($run_ppaye,0,0));
			$get_rpaye = "SELECT sum (paye) FROM salr WHERE empnum = '$emp[empnum]' AND cyear = '$emp[cyear]'";
			$run_rpaye = db_exec ($get_rpaye) or errDie ("Unable to get reversed paye information.");
			$rpaye = sprint (pg_fetch_result ($run_rpaye,0,0));
			$tot_paye = sprint ($ppaye - $rpaye);
			$file .= "4102,$tot_paye,";
			$code_count += 4102;
			$amount_count += $tot_paye;

			$file .= "4103,$tot_paye,";
			$code_count += 4103;
			$amount_count += $tot_paye;
	
			$file .= "9999\n";
			$code_count += 9999;

			$counter++;
			$record_count++;
		}
	}





/*


3010,
"01000001",

3020,
"A",

3030,
"Queenie",

3040,
"Elizabeth",

3050,
"E",

3060,
6805180197083,

3080,
19680518,

3110,
"PO Box 12",

3120,
"Johannesburg",

3150,
"2000",

3170,
20070301,

3180,
20080228,

3200,
12.0000,

3210,
12.0000,

3601,
,
80000,

3605,
,
4000,

3695,
4000,

3698
,
84000,

3699,
84000,
,

4101,
4500.00,

4102,
1025.16,

4103,
5525.16,

9999










*/




$counter++;

$file .= "6010,$counter,6020,$code_count,6030,$amount_count,9999\n";
$record_count++;

$file .= "7010,$record_count,9999";

header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-disposition: attachment; filename=sars_efiling_$gendate.csv");
header("Content-Type: text/plain");
header("Content-Transfer-Encoding: binary");
header("Content-Length: ". strlen($file));
echo $file;

}


?>