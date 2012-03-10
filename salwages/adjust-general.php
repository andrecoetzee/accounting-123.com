<?

require ("../settings.php");
require ("emp-functions.php");

if ( isset($HTTP_GET_VARS) ) {
	foreach ( $HTTP_GET_VARS as $k => $v ) {
		$HTTP_POST_VARS[$k] = $v;
	}
}

if (isset($HTTP_POST_VARS["key"])){
	switch ($HTTP_POST_VARS["key"]){
		case "salary":
			$OUTPUT = multiple_salary ($HTTP_GET_VARS['counter'],$HTTP_GET_VARS['empnum']);
			break;
		case "salary2":
			$OUTPUT = save_sal ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = show_emp_listing($HTTP_POST_VARS);
	}
}else {
	$OUTPUT = show_emp_listing ($HTTP_POST_VARS);
}

require ("../template.php");




function show_emp_listing ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	$limit = 50;

	if(!isset($offset))
		$offset = 0;

	if (isset($next))
		$offset = $offset + $limit;
	if (isset($back)){
		$offset = $offset - $limit;
		if ($offset < 0)
			$offset = 0;
	}


	db_connect ();

	if (isset($emp_group) AND $emp_group != 0){
		$egsearch = "AND emp_group = '$emp_group'";
	}else {
		#check for which groups we have perm
		$get_check = "SELECT payroll_groups FROM users WHERE username = '$_SESSION[USER_NAME]' LIMIT 1";
		$run_check = db_exec ($get_check) or errDie ("Unable to get employees group permissions.");
		if (pg_numrows ($run_check) > 0){
			$earr = pg_fetch_array ($run_check);
			if (strlen ($earr['payroll_groups']) > 0){
			    $eperms = explode (",",$earr['payroll_groups']);
			    $egsearch = " AND (emp_group = '".implode ("' OR emp_group = '",$eperms)."')";
			}
		}else {
			$egsearch = "";
		}
	}

	$sel1 = "";
	$sel2 = "";
	$sel3 = "";
	if (!isset($sort)){
		$sort = "enum";
		$sel1 = "checked='yes'";
	}elseif ($sort == "enum") {
		$sel1 = "checked='yes'";
	}elseif ($sort == "fnames") {
		$sel2 = "checked='yes'";
	}else {
		$sel3 = "checked='yes'";
	}

	$listing = "";
	$get_emps = "SELECT * FROM employees WHERE div = '".USER_DIV."' $egsearch ORDER BY $sort ASC OFFSET $offset LIMIT $limit";
	$run_emps = db_exec($get_emps) or errDie ("Unable to get employees information.");
	if (pg_numrows($run_emps) < 1){
		$listing = "
			<tr>
				<td colspan='4'><li class='err'>No Employees Found.</li></td>
			</tr>";
	}

	$rows = pg_numrows ($run_emps);

	$next_button = "";
	$back_button = "";
	if ($rows == $limit){
		$next_button = "<input type='submit' name='next' value='Next Screen'>";
	}
	if ($offset != 0){
		$back_button = "<input type='submit' name='back' value='Previous Screen'>";
	}


	$functions = "";
	$counter = 0;
	while ($emp = pg_fetch_array ($run_emps)){

		extract ($emp);

		// create the allowances and deductions storage fields
		$allowances = "";
		$allowances_ids = Array();
		$sql = "SELECT * FROM allowances WHERE div = '".USER_DIV."' ORDER BY allowance";
		$allowRslt = db_exec ($sql) or errDie ("Unable to select allowances from database.");
		if (pg_numrows ($allowRslt) > 0) {
			while ( $myAllow = pg_fetch_array ($allowRslt) ) {
				$aid = $myAllow["id"];
	
				$empsql = "SELECT * FROM cubit.empallow WHERE allowid='$aid' AND empnum='$empnum'";
				$emprslt = db_exec($empsql) or errDie("Error reading allowance information.");
	
				if ( pg_num_rows($emprslt) < 1 ) {
					$empAllow["amount"] = "0.00";
					$empAllow["accid"] = "";
				} else {
					$empAllow = pg_fetch_array($emprslt);
				}
	
				if ( empty($empAllow["accid"]) ) $empAllow["accid"] = $myAllow["accid"];
	
				$allowances .= "
					<input type='hidden' name='allowid[$aid]' value='$aid'>
					<input type='hidden' name='allowname[$aid]' value='$myAllow[allowance]'>
					<input type='hidden' name='allowtax[$aid]' value='$myAllow[add]'>
					<input type='hidden' name='allowances[$aid]' value='$empAllow[amount]'>
					<input type='hidden' name='allowaccid[$aid]' value='$empAllow[accid]'>
					<input type='hidden' name='allowtype[$aid]' value='$myAllow[type]'>";
	
				$allowances_ids[] = $aid;
			}
		}

		$subsistence = "";
		$subsistence_ids = array();
		$sql = "SELECT * FROM subsistence WHERE div='".USER_DIV."' ORDER BY name";
		$rslt = db_exec($sql) or errDie("Error reading subsistence allowances.");
		if (pg_num_rows($rslt) > 0) {
			while ($subs = pg_fetch_array($rslt)) {
				$sid = $subs["id"];
				$subsistence_ids[] = $sid;
	
				$sql = "SELECT * FROM emp_subsistence WHERE empnum='$empnum' AND subid='$sid'";
				$rslt2 = db_exec($sql) or errDie("Error reading employee subsistence.");
	
				if (pg_num_rows($rslt2) <= 0) {
					$si["amount"] = "0.00";
					$si["days"] = "0";
					$si["accid"] = $subs["accid"];
				} else {
					$si = pg_fetch_array($rslt2);
				}
	
				$subsistence .= "
					<input type='hidden' name='subsname[$sid]' value='$subs[name]'>
					<input type='hidden' name='subsamt[$sid]' value='$si[amount]'>
					<input type='hidden' name='subsacc[$sid]' value='$si[accid]'>
					<input type='hidden' name='subsdays[$sid]' value='$si[days]'>";		
			}
		}

		$deductions = "";
		$deductions_ids = Array();
		$sql = "SELECT * FROM salded WHERE div = '".USER_DIV."' ORDER BY deduction";
		$deductRslt = db_exec ($sql) or errDie("Unable to select deductions from database.");
		if (pg_numrows ($deductRslt) > 0) {
			while ( $myDeduct = pg_fetch_array ($deductRslt) ) {
				$did = $myDeduct["id"];
	
				$empsql = "SELECT * FROM empdeduct WHERE dedid='$did' AND empnum='$empnum'";
				$emprslt = db_exec($empsql) or errDie("Error reading employee deduction info.");
	
				if ( pg_num_rows($emprslt) < 1 ) {
					$empDeduct["amount"] = "0.00";
					$empDeduct["employer_amount"] = "0.00";
					if ( $myDeduct["creditor"] == "In House" ) {
						$empDeduct["accid"] = "$myDeduct[expaccid]";
					} else {
						$empDeduct["accid"] = "$myDeduct[accid]";
					}
				} else {
					$empDeduct = pg_fetch_array($emprslt);
				}
	
				$deductions .= "
					<input type='hidden' name='deductid[$did]' value='$did'>
					<input type='hidden' name='deductname[$did]' value='$myDeduct[deduction]'>
					<input type='hidden' name='deducttax[$did]' value='$myDeduct[add]'>
					<input type='hidden' name='deductions[$did]' value='$empDeduct[amount]'>
					<input type='hidden' name='comp_deductions[$did]' value='$empDeduct[employer_amount]'>
					<input type='hidden' name='deducttype[$did]' value='$myDeduct[type]'>
					<input type='hidden' name='deductaccid[$did]' value='$empDeduct[accid]'>";
				$deductions_ids[] = $did;
			}
		}

		$fringebens = "";
		$fringebens_ids = Array();
		$sql = "SELECT * FROM fringebens WHERE div = '".USER_DIV."' ORDER BY fringeben";
		$rslt = db_exec($sql) or errDie("Error to read fringe benefits.");
		if ( pg_num_rows($rslt) > 0 ) {
			while ( $myFringe = pg_fetch_array($rslt) ) {
				$fid = $myFringe["id"];
	
				$empsql = "SELECT * FROM empfringe WHERE fringeid='$fid' AND empnum='$empnum'";
				$emprslt = db_exec($empsql) or errDie("Error reading employee fringe info.");
	
				if ( pg_num_rows($emprslt) < 1 ) {
					$empFringe["amount"] = "0.00";
				} else {
					$empFringe = pg_fetch_array($emprslt);
				}
	
				$fringebens .= "
					<input type='hidden' name='fringeid[$fid]' value='$fid'>
					<input type='hidden' name='fringename[$fid]' value='$myFringe[fringeben]'>
					<input type='hidden' name='fringebens[$fid]' value='$empFringe[amount]'>
					<input type='hidden' name='fringetype[$fid]' value='$myFringe[type]'>
					<input type='hidden' name='fringeexpacc[$fid]' value='$myFringe[accid]'>";
	
				$fringebens_ids[] = $fid;
			}
		}

		$functions .= "
			function calcsalary$counter() {
				frm = document.emplfrm;
				passon = '?key=salary'
				passon += '&saltyp=' + document.getElementById('emplfrm$counter').saltyp.value;
				passon += '&all=".implode("|", $allowances_ids)."';
				passon += '&ded=".implode("|", $deductions_ids)."';
				passon += '&frin=".implode("|", $fringebens_ids)."';
				passon += '&subs=".implode("|", $subsistence_ids)."';
				passon += '&counter=$counter';
				passon += '&empnum=$empnum';
				popupSized('".SELF."' + passon, 'salpopup$counter', 400, 550, '');
			}";

		$hdate = explode("-", $hiredate);

		$listing .= "
			<form id='emplfrm$counter' action='".SELF."' method='POST'>
				<input type='hidden' name='empnum' value='$empnum' />
				<input type='hidden' name='basic_sal_annum' value='$basic_sal_annum' />
				<input type='hidden' name='sal_bonus' value='$sal_bonus' />
				<input type='hidden' name='sal_bonus_month' value='$sal_bonus_month' />
				<input type='hidden' name='all_travel' value='$all_travel' />
				<input type='hidden' name='comp_uif' value='$comp_uif' />
				<input type='hidden' name='comp_sdl' value='$comp_sdl' />
				<input type='hidden' name='comp_other' value='$comp_other' />
				<input type='hidden' name='comp_provident' value='$comp_provident' >
				<input type='hidden' name='comp_medical' value='$comp_medical' />
				<input type='hidden' name='comp_ret' value='$comp_ret' />
				<input type='hidden' name='comp_pension' value='$comp_pension' />
				<input type='hidden' name='emp_uif' value='$emp_uif' />
				<input type='hidden' name='emp_other' value='$emp_other' />
				<input type='hidden' name='emp_provident' value='$emp_provident' />
				<input type='hidden' name='emp_medical' value='$emp_medical' />
				<input type='hidden' name='emp_meddeps' value='$emp_meddeps' />
				<input type='hidden' name='emp_ret' value='$emp_ret' />
				<input type='hidden' name='emp_pension' value='$emp_pension' />
				<input type='hidden' name='saltyp' value='$saltyp' />
				<input type='hidden' name='fringe_car1' value='$fringe_car1' />
				<input type='hidden' name='fringe_car1_contrib' value='$fringe_car1_contrib' />
				<input type='hidden' name='fringe_car1_fuel' value='$fringe_car1_fuel' />
				<input type='hidden' name='fringe_car1_service' value='$fringe_car1_service' />
				<input type='hidden' name='fringe_car2' value='$fringe_car2' />
				<input type='hidden' name='fringe_car2_contrib' value='$fringe_car2_contrib' />
				<input type='hidden' name='fringe_car2_fuel' value='$fringe_car2_fuel' />
				<input type='hidden' name='fringe_car2_service' value='$fringe_car2_service' />
				<input type='hidden' name='year' value='$hdate[0]' />
				<input type='hidden' name='month' value='$hdate[1]' />
				<input type='hidden' name='day' value='$hdate[2]' />
				<input type='hidden' name='payprd' value='$payprd' />
				<input type='hidden' name='payprd_day' value='$payprd_day' />
				<input type='hidden' name='paytype' value='$paytype' />
				$allowances
				$subsistence
				$deductions
				$fringebens
				<tr bgcolor='".bgcolorg()."'>
					<td>$emp[enum]</td>
					<td>$emp[fnames] $emp[sname]</td>
					<td>$emp[hiredate]</td>
					<td><input type='button' onClick='javascript: calcsalary$counter();' value='Adjust'></td>
				</tr>
			</form>";
		$counter++;
	}

	$get_empgroups = "SELECT * FROM emp_groups ORDER BY emp_group";
	$run_empgroups = db_exec ($get_empgroups) or errDie ("Unable to get employee groups information.");
	if (pg_numrows ($run_empgroups) > 0){
		$emp_group_drop = "<select name='emp_group' onChange='document.form1.submit();'>";
		$emp_group_drop .= "<option value='0'>Select A Employee Group</option>";
		while ($earr = pg_fetch_array ($run_empgroups)){
			if (isset ($emp_group) AND $emp_group == "$earr[id]"){
				$emp_group_drop .= "<option value='$earr[id]' selected>$earr[emp_group]</option>";
			}else {
				$emp_group_drop .= "<option value='$earr[id]'>$earr[emp_group]</option>";
			}
		}
		$emp_group_drop .= "</select>";
	}

	$get_pays = "SELECT payroll_groups FROM users WHERE username = '$_SESSION[USER_NAME]' LIMIT 1";
	$run_pays = db_exec ($get_pays) or errDie ("Unable to get user payroll group information.");
	if (pg_numrows ($run_pays) > 0){
		$arr = pg_fetch_array ($run_pays);
		#check if the current user has pems to view the current payroll group
		$perms = explode (",",$arr['payroll_groups']);
		if (isset ($emp_group) AND is_array($perms) AND $emp_group != "0"){
			if (!in_array($emp_group,$perms)){
				$listing = "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='4'><li class='err'>You Do Not Have Permission To View This Payroll Group.</td>
					</tr>";
			}
		}elseif (strlen ($arr['payroll_groups']) < 1) {
			$listing = "<li class='err'>You Have Insufficient Permissions To Access The Cubit Payroll. You May Add The Permission <a href='../admin-usredit.php?username=$_SESSION[USER_NAME]'>Here</a></li></td></tr>";
		}
	}

	$display = "
		<script>
			$functions
		</script>
		<h2>Adjust Employees Salaries</h2>
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='offset' value='$offset'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Employee Group</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>$emp_group_drop</td>
			</tr>
			".TBL_BR."
			<tr>
				<th colspan='3'>Sort By</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='radio' onChange='document.form1.submit();' name='sort' $sel1 value='enum'> Employee Number</td>
				<td><input type='radio' onChange='document.form1.submit();' name='sort' $sel2 value='fnames'> Employee Name</td>
				<td><input type='radio' onChange='document.form1.submit();' name='sort' $sel3 value='sname'> Employee Surname</td>
			</tr>
		</table>
		<table ".TMPL_tblDflts." width='40%'>
			<tr>
				<td width='50'>$back_button</td>
				<td width='50'>$next_button</td>
			</tr>
		</table>
		</form>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Emp Num</th>
				<th>Employee</th>
				<th>Hire Date</th>
				<th>Adjust</th>
			</tr>
			$listing
		</table>";
	return $display;

}



function save_sal ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new validate ();
	# Limit field lengths as per database settings
	$v->isOk ($empnum,"string", 0, 20, "Invalid empnum.");
	$v->isOk ($saltyp, "string", 1, 2, "Invalid salary type.");
	$v->isOk ($paytype, "string", 1, 15, "Invalid pay type.");

	if(isset($allowances)){
		foreach($allowances as $key => $value){
			$v->isOk ($allowances[$key], "float", 0, 11, "Invalid allowance amount ".($key+1).".");
		}
	}
	if(isset($deductid)){
		foreach($deductid as $key => $value){
			$v->isOk ($deductid[$key], "num", 1, 9, "Invalid deductions ID.");
		}
	}
	if(isset($deductions)){
		foreach($deductions as $key => $value){
			$v->isOk ($deductions[$key], "float", 0, 11, "Invalid deduction amount".($key+1).".");
		}
	}
	if(isset($allowid)){
		foreach($allowid as $key => $value){
			$v->isOk ($allowid[$key], "num", 1, 9, "Invalid allowance ID.");
		}
	}
	if(isset($allowtax)){
		foreach($allowtax as $key => $value){
			$v->isOk ($allowtax[$key], "string", 1, 13, "Invalid allowance tax option".($key+1).".");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>$e[msg]</li>";
		}
		return "<li class='err'>$confirmCust</li>";
	}

	switch ( $saltyp ) {
		case "m":
			$sal_divisor = 12;
			break;
		case "f":
			$sal_divisor = 26;
			break;
		case "w":
			$sal_divisor = 52;
			break;
		case "h":
			$sal_divisor = 52 * $hpweek;
			break;
	}

	$basic_sal = sprint($basic_sal_annum / $sal_divisor);

	db_connect ();
	
	$sql = "
		UPDATE employees 
		SET basic_sal='$basic_sal', paytype='$paytype', payprd_day='$payprd_day', basic_sal_annum='$basic_sal_annum', 
			sal_bonus='$sal_bonus', sal_bonus_month='$sal_bonus_month', all_travel='$all_travel', comp_uif='$comp_uif', 
			comp_sdl='$comp_sdl', emp_uif='$emp_uif', comp_pension='$comp_pension', emp_pension='$emp_pension', 
			comp_ret='$comp_ret', emp_ret='$emp_ret', comp_medical='$comp_medical', emp_medical='$emp_medical', 
			emp_meddeps='$emp_meddeps', comp_provident='$comp_provident', emp_provident='$emp_provident', 
			comp_other='$comp_other', emp_other='$emp_other', payprd='$payprd', saltyp='$saltyp', 
			fringe_car1='$fringe_car1', fringe_car1_contrib='$fringe_car1_contrib', fringe_car1_fuel='$fringe_car1_fuel', 
			fringe_car1_service='$fringe_car1_service', fringe_car2='$fringe_car2', 
			fringe_car2_contrib='$fringe_car2_contrib', fringe_car2_fuel='$fringe_car2_fuel', 
			fringe_car2_service='$fringe_car2_service', flag=NULL 
		WHERE empnum = '$empnum' AND div = '".USER_DIV."'";
    $nwEmpRslt = db_exec ($sql) or errDie ("Unable to update employee information.");

	if (isset($allowid)){

		# Remove old details
		$sql = "DELETE FROM empallow WHERE empnum = '$empnum' AND div = '".USER_DIV."'";
		$allowRslt = db_exec($sql);

		# write Allowances to db
		foreach($allowid as $i => $id){
			if ( empty($allowances[$i]) || $allowances[$i] == 0 ) continue;
			# Insert new records
			$allowances[$i] += 0;
			$allowances[$i] = sprint ($allowances[$i]);
			$sql = "
				INSERT INTO empallow (
					allowid, empnum, type, amount, accid, div
				) VALUES (
					'$id', '$empnum', '$allowtype[$i]', '$allowances[$i]', '$allowaccid[$i]', '".USER_DIV."'
				)";
			$allowRslt = db_exec ($sql) or errDie ("Unable to process Employee allowances in database.");
		}
	}

	if (isset($subsname)) {

		$inssub = new dbUpdate("emp_subsistence", "cubit");

		foreach ($subsname as $sid => $sn) {
			$cols = grp(
				m("subid", $sid),
				m("empnum", $empnum),
				m("amount", $subsamt[$sid]),
				m("days", $subsdays[$sid]),
				m("accid", $subsacc[$sid])
			);

			$inssub->setOpt($cols, wgrp(m("subid", $sid), m("empnum", $empnum)));
			$inssub->run(DB_REPLACE);
		}
	}

	if(isset($deductid)){

		# Remove old records
		$sql = "DELETE FROM empdeduct WHERE empnum = '$empnum' AND div = '".USER_DIV."'";
		$deductRslt = db_exec($sql);

		# write Deductions to db
		foreach($deductid as $i => $id){
			if (isset($ltsal_checked[$i]))
				$ltsal = "y";
			else
				$ltsal = "n";

			# Insert new records
			if ( empty($deductions[$i]) || $deductions[$i] == 0 ) continue;
			if ( empty($comp_deductions[$i]) ) $comp_deductions[$i] = 0;

			$deductions[$i]+=0;
			$deductions[$i] = sprint ($deductions[$i]);
			$comp_deductions[$i]+=0;
			$sql = "
				INSERT INTO empdeduct (
					dedid, empnum, amount, employer_amount, div, type, 
					employer_type, grosdeduct, accid
				) VALUES (
					'$id', '$empnum', '$deductions[$i]', '$comp_deductions[$i]', '".USER_DIV."','$deducttype[$i]', 
					'$deducttype[$i]', '$ltsal', '$deductaccid[$i]'
				)";
			$deductRslt = db_exec ($sql) or errDie ("Unable to process Employee deductions in database.");
		}
	}

	if ( isset($fringebens) ) {

		$sql = "DELETE FROM empfringe WHERE empnum='$empnum' AND div='".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Error updating fringe benefits (DEL).");

		foreach ( $fringeid as $i => $id ) {
			if ( empty($fringebens[$i]) || $fringebens[$i] == 0 ) continue;

			$fringebens[$i] += 0;

			$sql = "
				INSERT INTO empfringe (
					fringeid, empnum, amount, type, accid, div
				) VALUES (
					'$id', '$empnum', '$fringebens[$i]', '$fringetype[$i]', '$fringeexpacc[$i]', '".USER_DIV."'
				)";
			$rslt = db_exec($sql) or errDie("Error updating fringe benefits (INS#$id).");
		}
	}
//				<script>
//					parent.opener.location.reload();
//				</script>
	$display = "
		<script>
			parent.opener.document.form1.submit();
			window.close();
		</script>";
	return $display;

}



function multiple_salary($counter=0,$empnum=0)
{

	global $HTTP_GET_VARS;
	extract($HTTP_GET_VARS);

	if ( empty($all) ) {
		$all = Array();
	} else {
		$all = explode("|", $all);
	}

	if ( empty($ded) ) {
		$ded = Array();
	} else {
		$ded = explode("|", $ded);
	}

	if ( empty($frin) ) {
		$frin = Array();
	} else {
		$frin = explode("|", $frin);
	}

	if ( empty($subs) ) {
		$subs = array();
	} else {
		$subs = explode("|", $subs);
	}

	$salarr = array(
		"m" => "Per Month",
		"w" => "Per Week",
		"f" => "Fortnightly",
		"h" => "Per Hour"
	);

	$saltyp = extlib_cpsel("saltyp", $salarr, "m");

	$payprd_arr = array(
		"d" => "Daily",
		"w" => "Weekly",
		"f" => "Fortnightly",
		"m" => "Monthly"
	);

	$payprd_day_arr = array(
		"mon"=>"Monday",
		"tue"=>"Tuesday",
		"wed"=>"Wendesday",
		"thu"=>"Thursday",
		"fri"=>"Friday"
	);

	if(!isset($payprd))
		$payprd = "";
	
	$payprd = extlib_cpsel("payprd", $payprd_arr, $payprd, "onChange='payprd_change(this);'");
	$payprd_day = extlib_cpsel("payprd_day", $payprd_day_arr, "m");

	$paytarr = array(
		"EFT" => "EFT",
		"Cheque" => "Cheque",
		"Cash" => "Cash",
		"Ledger Account" => "Ledger Account"
	);
	$paytypes = extlib_cpsel("paytype", $paytarr, "Cash");

	// bonus month selection
	$bonus_month = "<select name='sal_bonus_month'>";
	global $ePRDMON;
	for ($i = 1; $i <= 12; $i++) {
		$mon = $ePRDMON[$i];
		$bonus_month .= "<option value='$mon'>".getMonthName($mon)."</option>";
	}
	$bonus_month .= "</select>";

	$OUTPUT = "
		<table ".TMPL_tblDflts.">
		<form id='emplfrm$counter'>
			<input type='hidden' name='key' value='salary2'>
			<input type='hidden' name='empnum' value='$empnum'>";

//	<tr>
//		<td colspan=2 align=right>
//			<input type=button value='Save' onClick='savesalary();'>
//		</td>
//	</tr>

	$OUTPUT .= "
		<tr>
			<td colspan='2' align='right'><input type='submit' value='Save'></td>
		</tr>
		<tr>
			<th colspan='2'>General Salary and Allowances</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><b>Remuneration per Annum</b></td>
			<td>
				<table><tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='basic_sal_annum' value='0.00' class='right'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Salary Calculation</td>
			<td>$saltyp</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Pay Period</td>
			<td valign='top'>
				$payprd
				<div id='div_payprd_day'>$payprd_day</div>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Pay Type</td>
			<td valign='center'>$paytypes</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Annual Bonus</td>
			<td>
				<table><tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='sal_bonus' value='0.00' class='right'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Bonus - Month</td>
			<td>$bonus_month</td>
		</tr>
		<tr>
			<td colspan='2'>
				<li class='err'>All the amounts here after are monthly amounts.
					Please note that in the case of weekly/fortnightly employees the
					weekly/fortnightly amount needs to be converted to the monthly equivalent
					using the following calculation:<br>
					Weekly: amount x 52 / 12<br>
					Fortnightly: amount x 26 / 12</li>
			</td>
		</tr>";

	// fringe benefits
	$OUTPUT .= "
		<tr>
			<th colspan='2'>Fringe Benefits</th>
		</tr>
	  	<tr bgcolor='".bgcolorg()."'>
	  		<td>Medical Contribution</td>
	  		<td align='right'><div id='div_fringe_medaid'>".CUR."0.00</div></td>
	  	</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Motorcar 1 Determined Value</td>
			<td>
				<table><tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='fringe_car1' value='0.00' class='right'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>- Contributions for Use</td>
			<td>
				<table><tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='fringe_car1_contrib' value='0.00' class='right'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>- Pays for own Fuel</td>
			<td>
				<table><tr>
					<td>&nbsp;</td>
					<td>
						<select name='fringe_car1_fuel'>
							<option value='0'>No</option>
							<option value='1'>Yes</option>
						</select>
					</td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>- Pays for Servicing</td>
			<td>
				<table>
					<tr>
						<td>&nbsp;</td>
						<td>
							<select name='fringe_car1_service'>
								<option value='0'>No</option>
								<option value='1'>Yes</option>
							</select>
						</td>
						<td>&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
	  		<td colspan='2'><li class='err'>In case of 2 motorcars it is accepted that the second
	  			vehicle is not used for business purposes. In other cases PAYE has to be
	  			manually adjusted when processing salary.</li>
	  		</td>
	  	</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Motorcar 2 Determined Value</td>
			<td>
				<table>
					<tr>
						<td>".CUR."</td>
						<td><input type='text' size='10' name='fringe_car2' value='0.00' class='right'></td>
						<td>&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>- Contributions for Use</td>
			<td>
				<table>
					<tr>
						<td>".CUR."</td>
						<td><input type='text' size='10' name='fringe_car2_contrib' value='0.00' class='right'></td>
						<td>&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>- Pays for own Fuel</td>
			<td>
				<table>
					<tr>
						<td>&nbsp;</td>
						<td>
							<select name='fringe_car2_fuel'>
								<option value='0'>No</option>
								<option value='1'>Yes</option>
							</select>
						</td>
						<td>&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>- Pays for Servicing</td>
			<td>
				<table><tr>
					<td>&nbsp;</td>
					<td>
						<select name='fringe_car2_service'>
							<option value='0'>No</option>
							<option value='1'>Yes</option>
						</select>
					</td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>";

  	$i = 0;
  	foreach ( $frin as $fid ) {

  		$OUTPUT .= "
	  		<input type='hidden' name='fringeid[$fid]' value=''>
	  		<tr bgcolor='".bgcolorg()."'>
	  			<td><div id='divfrin[$fid]'></div></td>
	  			<td>
	  				<table>
	  					<tr>
							<td><div id='divfrinamt[$fid]'>&nbsp;</div></td>
							<td><input type='text' size='10' name='fringebens[$fid]' value='' class='right'></td>
							<td><div id='divfrinperc[$fid]'>&nbsp;</div></td>
		  				</tr>
		  			</table>
				</td>
	  		</tr>";
  	}

	// allowances
	$OUTPUT .= "
		<tr>
			<th colspan='2'>Allowances</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Travel Allowance</td>
			<td>
				<table>
					<tr>
						<td>".CUR."</td>
						<td><input type='text' size='10' name='all_travel' value='0.00' class='right'></td>
						<td>&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>";

	$i = 1;
	foreach ( $all as $aid ) {

		$aid += 0;

		#get allowaccid for this allowance
		$get_allaccid = "SELECT accid FROM allowances WHERE id = '$aid' LIMIT 1";
		$run_allaccid = db_exec ($get_allaccid) or errDie ("Unable to get allowance information.");
		if (pg_numrows ($run_allaccid) > 0){
			#found!
			$aidaccid = pg_fetch_result ($run_allaccid,0,0);
		}else {
			$aidaccid = 0;
		}

		$OUTPUT .= "
			<input type='hidden' name='allowid[$aid]' value=''>
			<input type='hidden' name='allowaccid[$aid]' value='$aidaccid'>
			<tr bgcolor='".bgcolorg()."'>
				<td><div id='divall[$aid]'></div></td>
				<td>
					<table>
						<tr>
							<td><div id='divallamt[$aid]'>&nbsp;</div></td>
							<td><input type='text' size='10' name='allowances[$aid]' value='' class='right'></td>
							<td><div id='divallperc[$aid]'>&nbsp;</div></td>
						</tr>
					</table>
				</td>
			</tr>";
	}

	if (count($subs) > 0) {
		$OUTPUT .= "
			<tr>
				<th colspan='2'>Subsistence Allowances</th>
			</tr>";
	}

	$i = 1;
	foreach ( $subs as $sid ) {

		$OUTPUT .= "
			<input type='hidden' name='subsname[$sid]' value=''>
			<input type='hidden' name='subsacc[$sid]' value=''>
			<tr bgcolor='".bgcolorg()."'>
				<td><div id='subsname[$sid]'></div></td>
				<td>
					<table>
						<tr>
							<td>Amount:</td><td>".CUR." <input type='text' name='subsamt[$sid]' value=''></td>
						</tr>
						<tr>
							<td>Days:</td><td><input type='text' name='subsdays[$sid]' value=''></td>
						</tr>
					</table>
				</td>
			</tr>";
	}

	$OUTPUT .= "
		<tr>
			<th colspan='2'>Deductions: Company Contributions</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>SDL</td>
			<td>
				<table>
					<tr>
						<td>&nbsp;</td>
						<td><input type='hidden' size='10' name='comp_sdl' value='0' class='right'>1</td>
						<td>%</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>UIF</td>
			<td>
				<table>
					<tr>
						<td>&nbsp;</td>
						<td><input type='hidden' size='10' name='comp_uif' value='0' class='right'>1</td>
						<td>%</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Pension Fund</td>
			<td>
				<table>
					<tr>
						<td>&nbsp;</td>
						<td><input type='text' size='10' name='comp_pension' value='0' class='right'></td>
						<td>%</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Retirement Annuity Fund</td>
			<td>
				<table>
					<tr>
						<td>".CUR."</td>
						<td><input type='text' size='10' name='comp_ret' value='0.00' class='right'></td>
						<td>&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Medical Contribution</td>
			<td>
				<table>
					<tr>
						<td>".CUR."</td>
						<td><input type='text' size='10' name='comp_medical' value='0.00' class='right' onChange='updateMedFringe();'></td>
						<td>&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Provident Fund</td>
		<td>
			<table>
				<tr>
					<td>&nbsp;</td>
					<td><input type='text' size='10' name='comp_provident' value='0' class='right'></td>
					<td>%</td>
				</tr>
			</table>
		</td>
	</tr>";

	$i = 0;
	foreach ( $ded as $did ) {

/*		$OUTPUT .= "
		<tr bgcolor='$bgColor'>
			<td><div id='divcomp_ded[$did]'></div></td>
			<td>
				<table><tr>
					<td><div id='divcomp_dedamt[$did]'>&nbsp;</div></td>
					<td><input type=text size=10 name='comp_deductions[$did]' value='' class=right></td>
					<td><div id='divcomp_dedperc[$did]'>&nbsp;</div></td>
				</tr></table>
			</td>
		</tr>";*/
		$OUTPUT .= "<input type='hidden' size='10' name='comp_deductions[$did]' value=''>";
	}

	/*
	$OUTPUT .= "
	<tr bgcolor='".TMPL_tblDataColor2."'>
		<td>Other</td>
		<td>
			<table></tr>
				<td>".CUR."</td>
				<td><input type=text size=10 name=comp_other value='0' class=right></td>
				<td>&nbsp;</td>
			</tr></table>
		</td>
	</tr>";*/
	$OUTPUT .= "<input type='hidden' name='comp_other' value='0'>";

	// deductions
	$OUTPUT .= "
		<tr>
			<th colspan='2'>Deductions: Employee Contributions</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>UIF</td>
			<td>
				<table>
					<tr>
						<td>&nbsp;</td>
						<td><input type='hidden' size='10' name='emp_uif' value='0' class='right'>1</td>
						<td>%</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Pension Fund</td>
			<td>
				<table>
					<tr>
						<td>&nbsp;</td>
						<td><input type='text' size='10' name='emp_pension' value='0' class='right'></td>
						<td>%</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Retirement Annuity Fund</td>
			<td>
				<table>
					<tr>
						<td>".CUR."</td>
						<td><input type='text' size='10' name='emp_ret' value='0.00' class='right'></td>
						<td><li class='err'>To be paid to RA fund by employer</li></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Medical Contribution</td>
			<td>
				<table>
					<tr>
						<td>".CUR."</td>
						<td><input type='text' size='10' name='emp_medical' value='0.00' class='right' onChange='updateMedFringe();'></td>
						<td>&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td> - Total Benificiaries<br>(Including Member)</td>
			<td>
				<table>
					<tr>
						<td>&nbsp;</td>
						<td><input type='text' size='2' name='emp_meddeps' value='0' class='right'></td>
						<td>&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Provident Fund</td>
			<td>
				<table>
					<tr>
						<td>&nbsp;</td>
						<td><input type='text' size='10' name='emp_provident' value='0' class='right'></td>
						<td>%</td>
					</tr>
				</table>
			</td>
		</tr>";

	$i = 1;
	foreach ( $ded as $did ) {

		$OUTPUT .= "
			<input type='hidden' name='deductid[$did]' value=''>
			<input type='hidden' name='deducttype[$did]' value=''>
			<input type='hidden' name='deductaccid[$did]' value=''>
			<tr bgcolor='".bgcolorg()."'>
				<td><div id='divded[$did]'></div></td>
				<td>
					<table>
						<tr>
							<td><div id='divdedamt[$did]'>&nbsp;</div></td>
							<td><input type='text' size='10' name='deductions[$did]' value='' class='right'></td>
							<td><div id='divdedperc[$did]'>&nbsp;</div></td>
						</tr>
					</table>
				</td>
			</tr>";
	}

/*	$OUTPUT .= "
	<tr bgcolor='".TMPL_tblDataColor2."'>
		<td>Other</td>
		<td>
			<table><tr>
				<td>".CUR."</td>
				<td><input type=text size=10 name=emp_other value='0' class=right></td>
				<td>&nbsp;</td>
			</tr></table>
		</td>
	</tr>";*/
	$OUTPUT .= "<input type='hidden' name='emp_other' value='0'>";

//	<tr>
//		<td colspan=2 align=right>
//			<input type=button value='Save' onClick='savesalary();'>
//		</td>
//	</tr>

	#old java script method
	$OUTPUT .= "
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Save'></td>
			</tr>
		</form>
		</table>";

//	$OUTPUT .= "
//	<tr>
//		<td colspan=2 align=right>
//			<input type='submit' value='Save'>
//		</td>
//	</tr>
//	</form>
//	</table>";

	// javascript
	$OUTPUT .= "
		<script>
			function payprd_change(p) {
				if ( p.value == \"f\" || p.value == \"w\" ) {
					document.getElementById('div_payprd_day').style.visibility = 'visible';
					document.getElementById('div_payprd_day').style.height = document.getElementById('emplfrm$counter').payprd_day.style.height;
				} else {
					document.getElementById('div_payprd_day').style.visibility = 'hidden';
					document.getElementById('div_payprd_day').style.height = '0';
				}
			}

			// get all the different objects to access
			if ( window.opener.parent.mainframe ) {
				opdoc = window.opener.parent.mainframe.document;
			} else {
				opdoc = window.opener.document;
			}

			opfrm = opdoc.getElementById('emplfrm$counter');
			efrm = document.getElementById('emplfrm$counter');

			// gets the salary info from the opener
			function getsalary() {
				efrm.saltyp.value = opfrm.saltyp.value;
				efrm.payprd.value = opfrm.payprd.value;
				efrm.payprd_day.value = opfrm.payprd_day.value;
				payprd_change(efrm.payprd);
				efrm.paytype.value = opfrm.paytype.value;
				efrm.basic_sal_annum.value = opfrm.basic_sal_annum.value;
				efrm.sal_bonus.value = opfrm.sal_bonus.value;
				efrm.sal_bonus_month.value = opfrm.sal_bonus_month.value;
				efrm.all_travel.value = opfrm.all_travel.value;
				efrm.comp_sdl.value = opfrm.comp_sdl.value;
				efrm.comp_uif.value = opfrm.comp_uif.value;
				efrm.comp_other.value = opfrm.comp_other.value;
				efrm.comp_provident.value = opfrm.comp_provident.value;
				efrm.comp_medical.value = opfrm.comp_medical.value;
				efrm.comp_ret.value = opfrm.comp_ret.value;
				efrm.comp_pension.value = opfrm.comp_pension.value;
				efrm.emp_uif.value = opfrm.emp_uif.value;
				efrm.emp_other.value = opfrm.emp_other.value;
				efrm.emp_provident.value = opfrm.emp_provident.value;
				efrm.emp_medical.value = opfrm.emp_medical.value;
				efrm.emp_meddeps.value = opfrm.emp_meddeps.value;
				efrm.emp_ret.value = opfrm.emp_ret.value;
				efrm.emp_pension.value = opfrm.emp_pension.value;
				efrm.fringe_car1.value = opfrm.fringe_car1.value;
				efrm.fringe_car1_contrib.value = opfrm.fringe_car1_contrib.value;
				efrm.fringe_car1_fuel.options[opfrm.fringe_car1_fuel.value].selected = true;
				efrm.fringe_car1_service.options[opfrm.fringe_car1_service.value].selected = true;
				efrm.fringe_car2.value = opfrm.fringe_car2.value;
				efrm.fringe_car2_contrib.value = opfrm.fringe_car2_contrib.value;
				efrm.fringe_car2_fuel.options[opfrm.fringe_car2_fuel.value].selected = true;
				efrm.fringe_car2_service.options[opfrm.fringe_car2_service.value].selected = true;";

	foreach ( $frin as $fid ) {
		$OUTPUT .= "
			// set the fringe benefit symbol
			frintype = opfrm.elements['fringetype[$fid]'].value;

			if ( frintype == 'Amount' ) {
				document.getElementById('divfrinamt[$fid]').innerHTML = '".CUR."';
			} else {
				document.getElementById('divfrinperc[$fid]').innerHTML = '%';
			}

			// fringeben name
			document.getElementById('divfrin[$fid]').innerHTML = opfrm.elements['fringename[$fid]'].value;

			// fringeben fields
			efrm.elements['fringebens[$fid]'].value = opfrm.elements['fringebens[$fid]'].value;
			efrm.elements['fringeid[$fid]'].value = opfrm.elements['fringeid[$fid]'].value";
	}

	foreach ( $all as $aid ) {
		$OUTPUT .= "
			// set the allowance symbol
			alltype = opfrm.elements['allowtype[$aid]'].value;

			if ( alltype == 'Amount' ) {
				document.getElementById('divallamt[$aid]').innerHTML = '".CUR."';
			} else {
				document.getElementById('divallperc[$aid]').innerHTML = '%';
			}

			// allowance name
			document.getElementById('divall[$aid]').innerHTML = opfrm.elements['allowname[$aid]'].value;

			// allowance fields
			efrm.elements['allowances[$aid]'].value = opfrm.elements['allowances[$aid]'].value;
			efrm.elements['allowid[$aid]'].value = opfrm.elements['allowid[$aid]'].value;";
		
	}

	foreach ( $subs as $sid ) {
		$OUTPUT .= "
			// set the allowance name
			document.getElementById('subsname[$sid]').innerHTML = opfrm.elements['subsname[$sid]'].value;

			// set the allowance amount
			efrm.elements['subsamt[$sid]'].value = opfrm.elements['subsamt[$sid]'].value;

			// allowance days
			efrm.elements['subsdays[$sid]'].value = opfrm.elements['subsdays[$sid]'].value;
			efrm.elements['subsname[$sid]'].value = opfrm.elements['subsname[$sid]'].value;
			efrm.elements['subsacc[$sid]'].value = opfrm.elements['subsacc[$sid]'].value;";
	}

	foreach ( $ded as $did ) {
		$OUTPUT .= "
			// set the deduction symbol
			dedtype = opfrm.elements['deducttype[$did]'].value;

			if ( dedtype == 'Amount' ) {
				document.getElementById('divdedamt[$did]').innerHTML = '".CUR."';
				//document.getElementById('divcomp_dedamt[$did]').innerHTML = '".CUR."';
			} else {
				document.getElementById('divdedperc[$did]').innerHTML = '%';
				//document.getElementById('divcomp_dedperc[$did]').innerHTML = '%';
			}

			// set the deduction name
			document.getElementById('divded[$did]').innerHTML = opfrm.elements['deductname[$did]'].value;
			//document.getElementById('divcomp_ded[$did]').innerHTML = opfrm.elements['deductname[$did]'].value;

			// deduction fields
			efrm.elements['deductions[$did]'].value = opfrm.elements['deductions[$did]'].value;
			efrm.elements['deductid[$did]'].value = opfrm.elements['deductid[$did]'].value;
			efrm.elements['deducttype[$did]'].value = opfrm.elements['deducttype[$did]'].value;
			efrm.elements['deductaccid[$did]'].value = opfrm.elements['deductaccid[$did]'].value;
			//efrm.elements['comp_deductions[$did]'].value = opfrm.elements['comp_deductions[$did]'].value;";
	}

	$OUTPUT .= "
		} // end get salary

		// saves the salary to the opener
		function savesalary() {
			// determine what to display about the salary
			switch ( efrm.saltyp.value ) {
			case 'w':
				salperiod = 'per Week';
				saldivisor = 52;
				break;
			case 'h':
				salperiod = 'per Hour';
				saldivisor = 52 * opfrm.hpweek.value;
				break;
			case 'f':
				salperiod = 'Fortnightly';
				saldivisor = 26;
				break;
			case 'm':
			default:
				salperiod = 'per Month';
				saldivisor = 12;
				break;
			}

			salamount = parseFloat(efrm.basic_sal_annum.value) / saldivisor;
			salamount = salamount.toFixed(2);

			salvalue = '".CUR."' + salamount + ' ' + salperiod;

			// set the display salary
			opdoc.getElementById('div_basic_sal').innerHTML = salvalue;

			// set the add employee form elements
			opfrm.saltyp.value = efrm.saltyp.value;
			opfrm.payprd.value = efrm.payprd.value;
			opfrm.payprd_day.value = efrm.payprd_day.value;
			opfrm.paytype.value = efrm.paytype.value;
			opfrm.basic_sal_annum.value = efrm.basic_sal_annum.value;
			opfrm.sal_bonus.value = efrm.sal_bonus.value;
			opfrm.sal_bonus_month.value = efrm.sal_bonus_month.value; // each month's number is one more than it's index obviously
			opfrm.all_travel.value = efrm.all_travel.value;
		
			opfrm.comp_sdl.value = efrm.comp_sdl.value;
			opfrm.comp_uif.value = efrm.comp_uif.value;
			opfrm.comp_other.value = efrm.comp_other.value;
			opfrm.comp_provident.value = efrm.comp_provident.value;
			opfrm.comp_medical.value = efrm.comp_medical.value;
			opfrm.comp_ret.value = efrm.comp_ret.value;
			opfrm.comp_pension.value = efrm.comp_pension.value;
		
			opfrm.emp_uif.value = efrm.emp_uif.value;
			opfrm.emp_other.value = efrm.emp_other.value;
			opfrm.emp_provident.value = efrm.emp_provident.value;
			opfrm.emp_medical.value = efrm.emp_medical.value;
			opfrm.emp_meddeps.value = efrm.emp_meddeps.value;
			opfrm.emp_ret.value = efrm.emp_ret.value;
			opfrm.emp_pension.value = efrm.emp_pension.value;
		
			opfrm.fringe_car1.value = efrm.fringe_car1.value;
			opfrm.fringe_car1_contrib.value = efrm.fringe_car1_contrib.value;
			opfrm.fringe_car1_fuel.value = efrm.fringe_car1_fuel.value;
			opfrm.fringe_car1_service.value = efrm.fringe_car1_service.value;
			opfrm.fringe_car2.value = efrm.fringe_car2.value;
			opfrm.fringe_car2_contrib.value = efrm.fringe_car2_contrib.value;
			opfrm.fringe_car2_fuel.value = efrm.fringe_car2_fuel.value;
			opfrm.fringe_car2_service.value = efrm.fringe_car2_service.value;";

	foreach ( $frin as $fid ) {
		$OUTPUT .= "
			opfrm.elements['fringebens[$fid]'].value = efrm.elements['fringebens[$fid]'].value;";
	}

	foreach ( $all as $aid ) {
		$OUTPUT .= "
			opfrm.elements['allowid[$aid]'].value = efrm.elements['allowid[$aid]'].value;
			opfrm.elements['allowances[$aid]'].value = efrm.elements['allowances[$aid]'].value;";
	}

	foreach ( $subs as $sid ) {
		$OUTPUT .= "
			opfrm.elements['subsamt[$sid]'].value = efrm.elements['subsamt[$sid]'].value;
			opfrm.elements['subsdays[$sid]'].value = efrm.elements['subsdays[$sid]'].value;";
	}

	foreach ( $ded as $did ) {
		$OUTPUT .= "
			opfrm.elements['deductions[$did]'].value = efrm.elements['deductions[$did]'].value;
			opfrm.elements['comp_deductions[$did]'].value = efrm.elements['comp_deductions[$did]'].value;";
	}

	$OUTPUT .= "
			alert = 'test';
				window.close();
			} // end save salary

			function updateMedFringe() {
				memp = parseFloat(efrm.emp_medical.value);
				mcomp = parseFloat(efrm.comp_medical.value);
				thrd = (memp + mcomp) / 3 * 2;
				if ( (fben = mcomp - thrd) < 0 ) {
					fben = 0;
				}
				fben = fben.toFixed(2);
				document.getElementById('div_fringe_medaid').innerHTML = '".CUR." ' + fben;
			}
			document.setOnLoad=getsalary();
		</script>";
	return $OUTPUT;

}



?>