<?

#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#
#
# admin-employee-add.php :: Add employees to database
##

require ("settings.php");
require ("libs/ext.lib.php");
require ("salwages/emp-functions.php");

if ( isset($_GET) ) {
	foreach ( $_GET as $k => $v ) {
		$_POST[$k] = $v;
	}
}

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "salary":
			$OUTPUT = salary();
			break;
		case  "confirm":
			if (!isset ($_POST["confirmed"])){
				$OUTPUT = editEmp ();
			}else {
				$OUTPUT = confirmEmp ($_POST);
			}
			break;
		case "write":
			$OUTPUT = writeEmp ($_POST);
			break;
		default:
			$OUTPUT = editEmp ();
	}
} else {
	# print form for data entry
	$OUTPUT = editEmp ();
}

require ("template.php");




# editEmp
function editEmp ($err="")
{

	global $_POST;
	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "-".$e["msg"]."<br>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# connect to db
	db_connect ();

	# get employee info to edit
	$sql = "SELECT * FROM cubit.employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee number.";
	}
	$emp = pg_fetch_array($empRslt);

	extract($emp, EXTR_SKIP);
	# deal with $err
	if(!isset($err)){
		$err = "";
	}
	$Tp = array("M"=>"Male","F"=>"Female");
	$sexs = extlib_cpsel("sex", $Tp,$sex);

	$salarr = array("m"=>"Per Month", "w"=>"Per Week", "f"=>"Per 2 Weeks", "d"=>"Per Day", "h"=>"Per Hour");
	$saltyp = extlib_cpsel("saltyp", $salarr, $saltyp);

	$overarr = array("1" => "x 1", "1.5" => "x 1.5", "2" => "x 2", "2.5" => "x 2.5", "3" => "x 3");
	//$noverts = extlib_cpsel("novert", $overarr, $novert);
	//$hoverts = extlib_cpsel("hovert", $overarr, $hovert);

	$Tp = array("Single"=>"Single","Married"=>"Married","Widowed"=>"Widowed","Divorced"=>"Divorced");
	$maritals = extlib_cpsel("marital", $Tp,$marital);

	$Tp = array("Yes"=>"Yes","No"=>"No");
	$residents = extlib_cpsel("resident", $Tp,$resident);

	$Tp = array("EFT"=>"EFT","Cheque"=>"Cheque","Cash"=>"Cash","Ledger Account"=>"Ledger Account");
	$paytypes = extlib_cpsel("paytype", $Tp,$paytype);

        $rslt = db_exec("SELECT accname FROM bankacctypes");
	// if no bank account types were found, add the default
	if ( pg_num_rows($rslt) < 1 ) {
		db_exec("INSERT INTO bankacctypes (accname) VALUES('Savings')");
		db_exec("INSERT INTO bankacctypes (accname) VALUES('Current or Cheque')");
		db_exec("INSERT INTO bankacctypes (accname) VALUES('Credit Card')");

		$Tp = array("Savings" => "Savings","Current or Cheque" => "Current or Cheque","Credit Card" => "Credit Card");
	} else {
		$Tp = "";

		while ( $row = pg_fetch_array($rslt) ) $Tp[$row["accname"]] = $row["accname"];
	}

	$bankacctypes = extlib_cpsel("bankacctype", $Tp,$bankacctype);

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


	db_conn('cubit');

	$Sl = "SELECT * FROM costcenters";
	$Ri = db_exec($Sl);

	$ctd = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Cost Center</th>
				<th>Percentage</th>
			</tr>";

	$i = 0;

	while($data = pg_fetch_array($Ri)) {

		$Sl = "SELECT * FROM empc WHERE emp='$empnum' AND cid='$data[ccid]'";
		$Rq = db_exec($Sl);

		$cd = pg_fetch_array($Rq);

		$ctd .= "
			<tr class='".bg_class()."'>
				<td>$data[centername]</td>
				<td><input type='text' name='ct[$data[ccid]]' size='5' value='$cd[amount]'> %</td>
			</tr>";

		$i++;
	}

	$ctd .= "</table>";

	$sal_desc = CUR . " " . ($basic_sal) . " " . $salarr[$emp["saltyp"]];

	$hdate = explode("-", $hiredate);

	$r_sel1 = "";
	$r_sel2 = "";
	$r_sel3 = "";
	$r_sel4 = "";

	if($race == "african"){
		$r_sel1 = "selected";
	}elseif ($race == "coloured"){
		$r_sel2 = "selected";
	}elseif ($race == "indian"){
		$r_sel3 = "selected";
	}elseif ($race == "white"){
		$r_sel4 = "selected";
	}
	$racedrop = "
		<select name='race'>
			<option $r_sel1 value='african'>African</option>
			<option $r_sel2 value='coloured'>Coloured</option>
			<option $r_sel3 value='indian'>Indian (Asian)</option>
			<option $r_sel4 value='white'>White</option>
		</select>";

	#get occ cats
	$get_cats = "SELECT * FROM occ_cat ORDER BY id";
	$run_cats = db_exec($get_cats) or errDie("Unable to get occupational categories.");
	if(pg_numrows($run_cats) < 1){
		//return "";
	}else {
		$occ_cat_drop = "<select name='occ_cat'>";
		while ($carr = pg_fetch_array($run_cats)){
			if($occ_cat == $carr['id']){
				$occ_cat_drop .= "<option value='$carr[id]' selected>$carr[cat]</option>";
			}else {
				$occ_cat_drop .= "<option value='$carr[id]'>$carr[cat]</option>";
			}
		}
		$occ_cat_drop .= "</select>";
	}

	#get occ category
	$getocc_level = "SELECT * FROM occ_level ORDER BY id";
	$run_level = db_exec($getocc_level) or errDie("Unable to get occupational levels.");
	if(pg_numrows($run_level) < 1){
		$occ_level_drop = "<input type='hidden' name='occ_level' value='0'>";
	}else {
		$occ_level_drop = "<select name='occ_level'>";
		while ($larr = pg_fetch_array($run_level)){
			if($occ_level == $larr['id']){
				$occ_level_drop .= "<option value='$larr[id]' selected>$larr[level]</option>";
			}else {
				$occ_level_drop .= "<option value='$larr[id]'>$larr[level]</option>";
			}
		}
		$occ_level_drop .= "</select>";
	}

	$get_dep = "SELECT * FROM departments ORDER BY id";
	$run_dep = db_exec($get_dep) or errDie("Unable to get departments information.");
	if(pg_numrows($run_dep) < 1){
		$dep_drop = "<input type='hidden' name='department' value='0'>";
	}else {
		$dep_drop = "<select name='department'>";
		while ($darr = pg_fetch_array($run_dep)){
			if ($department == "$darr[id]"){
				$dep_drop .= "<option value='$darr[id]' selected>$darr[department]</option>";
			}else {
				$dep_drop .= "<option value='$darr[id]'>$darr[department]</option>";
			}
		}
		$dep_drop .= "</select>";
	}

	$get_pos = "SELECT * FROM pos_filled ORDER BY id";
	$run_pos = db_exec($get_pos) or errDie("Unable to get position filled information.");
	if(pg_numrows($run_pos) < 1){
		$pos_filled_drop = "<input type='hidden' name='pos_filled' value='0'>";
	}else {
		$pos_filled_drop = "<select name='pos_filled'>";
		while($parr = pg_fetch_array($run_pos)){
			if($pos_filled == $parr['id']){
				$pos_filled_drop .= "<option value='$parr[id]' selected>$parr[method]</option>";
			}else {
				$pos_filled_drop .= "<option value='$parr[id]'>$parr[method]</option>";
			}
		}
		$pos_filled_drop .= "</select>";
	}

	$get_union = "SELECT * FROM unions ORDER BY id";
	$run_union = db_exec($get_union) or errDie("Unable to get unions information.");
	if(pg_numrows($run_pos) < 1){
		$union_drop = "<input type='hidden' name='union_name' value='0'>";
	}else {
		$union_drop = "<select name='union_name'>";
		while ($uarr = pg_fetch_array($run_union)){
			if($union_name == $uarr['id']){
				$union_drop .= "<option value='$uarr[id]' selected>$uarr[union_name]</option>";
			}else {
				$union_drop .= "<option value='$uarr[id]'>$uarr[union_name]</option>";
			}
		}
		$union_drop .= "</select>";
	}

	#get emp groups
	$get_egroups = "SELECT * FROM emp_groups ORDER BY emp_group";
	$run_egroups = db_exec($get_egroups) or errDie ("Unable to get employee group information.");
	if (pg_numrows($run_egroups) < 1){
		$emp_group_drop = "<input type='hidden' name='emp_group' value='0'>";
	}else {
		$emp_group_drop = "<select name='emp_group'>";
//		$emp_group_drop .= "<option value='0'>Select Group</option>";
		while ($earr = pg_fetch_array ($run_egroups)){
			if (isset($emp_group) AND $emp_group == $earr['id']){
				$emp_group_drop .= "<option value='$earr[id]' selected>$earr[emp_group]</option>";
			}else {
				$emp_group_drop .= "<option value='$earr[id]'>$earr[emp_group]</option>";
			}
		}
		$emp_group_drop .= "</select>";
	}

	$natures = array (
		"A" => "Individual with an identity- or password number",
		"B" => "Individual without an identity- or passport number",
		"C" => "Director of a private company / member of a close corporation",
		"D" => "Trust",
		"E" => "Company / cc",
		"F" => "Partnership",
		"G" => "Corporation",
		"H" => "Employment company / personal service company or cc",
		"K" => "Employment trust / personal service trust",
		"M" => "Foreign service income (may only be used with foreign income codes)",
	);

	$natures_drop = "<select name='person_nature'>";
	foreach ($natures AS $code => $nature){
		if ($person_nature == $code){
			$natures_drop .= "<option value='$code' selected'>$code - $nature</option>";
		}else {
			$natures_drop .= "<option value='$code'>$code - $nature</option>";
		}
	}
	$natures_drop .= "</select>";



	$editEmp = "
        <h3>Edit Employee Details</h3>
		<script>
			function calcsalary() {
				frm = document.emplfrm;
				passon = '?key=salary'
				passon += '&saltyp=' + document.getElementById('emplfrm').saltyp.value;
				passon += '&all=".implode("|", $allowances_ids)."';
				passon += '&ded=".implode("|", $deductions_ids)."';
				passon += '&frin=".implode("|", $fringebens_ids)."';
				passon += '&subs=".implode("|", $subsistence_ids)."';
				popupSized('".SELF."' + passon, 'salpopup', 400, 550, '');
			}

			function updateBirthDate(id) {
				bdate = document.getElementById('birthdate');
				if ( id.value.length < 6 ) {
					return invalidBirthDate(bdate);
				} else {
					bd_year = 1900 + parseFloat(id.value.substr(0,2));
					bd_month = parseFloat(id.value.substr(2,2));
					bd_day = parseFloat(id.value.substr(4,2));
					// check if month and day is valid
					if ( bd_day == 0 ) return invalidBirthDate(bdate);
					switch ( bd_month ) {
					case 2:
						if ( (bd_year % 4 && bd_day > 28) || (bd_year % 4 == 0 && bd_day > 29) )
							return invalidBirthDate(bdate);
					case 4: case 6: case 9: case 11:
						if ( bd_day > 30 )
							return invalidBirthDate(bdate);
					case 1: case 3: case 5: case 7: case 8: case 10: case 12:
						if ( bd_day > 31 )
							return invalidBirthDate(bdate);
						break;
					default:
						return invalidBirthDate(bdate);
					}
					bd_desc = bd_year + ' / ' + bd_month + ' / ' + bd_day;
					bdate.innerHTML = bd_desc;
					bdate.style.color = '#000';
					bdate.style.fontWeight = 'bold';
				}
			}

			function updateHourSal(hours) {
				efrm = document.getElementById('emplfrm');
				if ( efrm.saltyp.value == 'h' ) {
					salperiod = 'per Hour';
					saldivisor = 52 * hours;
					salamount = parseFloat(efrm.basic_sal_annum.value) / saldivisor;
					salamount = salamount.toFixed(2);
					salvalue = '".CUR."' + salamount + ' ' + salperiod;
					document.getElementById('div_basic_sal').innerHTML = salvalue;
				}
			}

			function invalidBirthDate(bdate) {
				bdate.innerHTML = 'Invalid ID Number';
				bdate.style.color = '#f00';
				bdate.style.fontWeight = 'bold';
				return 1;
			}
		</script>

		<table ".TMPL_tblDflts.">
			$err
		<form id='emplfrm' action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='confirm' />
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
			<input type='hidden' name='saltyp' value='$emp[saltyp]' />
			<input type='hidden' name='fringe_car1' value='$fringe_car1' />
			<input type='hidden' name='fringe_car1_contrib' value='$fringe_car1_contrib' />
			<input type='hidden' name='fringe_car1_fuel' value='$fringe_car1_fuel' />
			<input type='hidden' name='fringe_car1_service' value='$fringe_car1_service' />
			<input type='hidden' name='fringe_car2' value='$fringe_car2' />
			<input type='hidden' name='fringe_car2_contrib' value='$fringe_car2_contrib' />
			<input type='hidden' name='fringe_car2_fuel' value='$fringe_car2_fuel' />
			<input type='hidden' name='fringe_car2_service' value='$fringe_car2_service' />
			<input type='hidden' name='emp_usescales' value='$emp_usescales' />
			<input type='hidden' name='year' value='$hdate[0]' />
			<input type='hidden' name='month' value='$hdate[1]' />
			<input type='hidden' name='day' value='$hdate[2]' />
			<input type='hidden' name='payprd' value='$payprd' />
			<input type='hidden' name='payprd_day' value='$payprd_day' />
			<input type='hidden' name='paytype' value='$paytype' />
			<tr>
				<td>&nbsp;</td>
				<td align='right'><input type='submit' name='confirmed' value='Confirm &raquo;'></td>
			</tr>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Employee Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Emp Num</td>
							<td><input type='text' size='20' name='enum' value='$enum'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Surname</td>
							<td valign='center'><input type='text' name='sname' value='$sname'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."First Names</td>
							<td valign='center'><input type='text' name='fnames' value='$fnames'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."ID Num</td>
							<td><input type='text' size='20' name='idnum' value='$idnum' onChange='updateBirthDate(this);'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center' colspan='2'><b>OR</b></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Passport Num</td>
							<td><input type='text' size='20' name='passportnum' value='$passportnum'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Birthdate</td>
							<td><div id='birthdate'></div></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Sex</td>
							<td valign='center'>$sexs</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Race</td>
							<td>$racedrop</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Disabled Status</td>
							<td><input type='text' name='disabled_stat' value='$disabled_stat'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Marital Status</td>
							<td valign='center'>$maritals</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Resident</td>
							<td valign='center'>$residents</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Employee Group</td>
							<td>$emp_group_drop</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Telephone No</td>
							<td valign='center'><input type='text' name='telno' value='$telno'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Email</td>
							<td valign='center'><input type='text' name='email' value='$email'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Name</td>
							<td valign='center'><input type='text' name='bankname' value='$bankname'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch Code</td>
							<td valign='center'><input type='text' name='bankcode' value='$bankcode'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Account Type</td>
							<td valign='center'>$bankacctypes</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Account No</td>
							<td valign='center'><input type='text' name='bankaccno' value='$bankaccno'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Residential Address</td>
							<td valign='center'><input type='text' name='res1' value='$res1'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'><input type='text' name='res2' value='$res2'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'><input type='text' name='res3' value='$res3'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'><input type='text' name='res4' value='$res4'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Postal Address</td>
							<td valign='center'><input type='text' name='pos1' value='$pos1'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'><input type='text' name='pos2' value='$pos2'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Postal Code</td>
							<td valign='center'><input type='text' name='pcode' value='$pcode'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Upload Image</td>
							<td>Yes<input type='radio' name='changelogo' value='yes'> - No<input type='radio' name='changelogo' value='no' checked='yes'>
						</tr>
						<tr>
							<th colspan='2'>Friend Not Living With Employee</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Surname</td>
							<td valign='center'><input type='text' name='contsname' value='$contsname'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>First Names</td>
							<td valign='center'><input type='text' name='contfnames' value='$contfnames'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Residential Address</td>
							<td valign='center'><input type='text' name='contres1' value='$contres1'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'><input type='text' name='contres2' value='$contres2'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'><input type='text' name='contres3' value='$contres3'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Telephone No</td>
							<td valign='center'><input type='text' name='conttelno' value='$conttelno'></td>
						</tr>
					</table>
				</td>";

	$empfin_start = "March ".getYearOfEmpMon(3). " - February " .getYearOfEmpMon(2);

	$get_medical_aids = "SELECT * FROM medical_aid ORDER BY medical_aid_name";
	$run_medical_aids = db_exec ($get_medical_aids) or errDie ("Unable to get medical aid options.");
	if (pg_numrows ($run_medical_aids) < 1){
		$medical_aid_drop = "<input type='hidden' name='medical_aid' value='0'>None Found. <a target='_blank' href='medical_aid_add.php'>Add Medical Aid Option</a>";
	}else {
		$medical_aid_drop = "<select name='medical_aid'>";
		while ($marr = pg_fetch_array ($run_medical_aids)){
			if(isset ($medical_aid) AND $medical_aid == $marr['id']){
				$medical_aid_drop .= "<option value='$marr[id]' selected>$marr[medical_aid_name]</option>";
			}else {
				$medical_aid_drop .= "<option value='$marr[id]'>$marr[medical_aid_name]</option>";
			}
		}
		$medical_aid_drop .= "</select>";
	}

	$editEmp .= "
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Tax from Previous Employer for Current
								Employee Financial Year ($empfin_start)</th>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td>".REQ." Total Remuneration from Previous Employer (or your estimate)</td>
							<td><input type='text' size='20' name='prevemp_remun' value='$prevemp_remun' /></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td>".REQ." Total Tax from Previous Employes (or your estimate)</td>
							<td><input type='text' size='20' name='prevemp_tax' value='$prevemp_tax' /></td>
						</tr>
						<tr>
							<th colspan=2>Employement Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td rowspan='2'>Remuneration</td>
							<td><div id='div_basic_sal'>$sal_desc</div></td>
						</tr>
						<tr class='".bg_class()."'>
							<!-- ROWSPAN -->
							<td><input type='button' onClick='javascript: calcsalary();' value='Calculate Salary'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td>Medical Aid</td>
							<td>$medical_aid_drop</td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td>Medical Aid Number</td>
							<td><input type='text' size='20' name='medical_aid_number' value='$medical_aid_number'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Hours Per Week</td>
							<td valign='top'><input type='text' size='3' name='hpweek' value='$hpweek' onChange='updateHourSal(this.value)'>&nbsp;&nbsp;Hours</td>
						</tr>
						<tr bgcolor='".bgcolorc($i)."'>
							<td rowspan='2'>Overtime rate</td>
							<td valign='top'>Normal: <input type='text' name='novert' value='$novert' size='3' /></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<!-- rowspan-->
							<td valign='top'>Public holidays: <input type='text' name='hovert' value='$hovert' size='3' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Income Tax Ref No.</td>
							<td><input type='text' size='20' name='taxref' value='$taxref'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Hire Date</td>
							<td valign='center'><input type='hidden' name='hiredate' value='$hiredate'>$hiredate</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Temporary (Employee or Contract)</td>
							<td><input type='radio' name='temporary' value='yes'> Yes <input type='radio' name='temporary' value='no' checked='yes'> No</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>If Temporary: Termination Date</td>
							<td>".mkDateSelect("t")."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Nature of Person</td>
							<td>$natures_drop</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Designation (Job Title)</td>
							<td><input type='text' name='designation' value='$designation'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Department</td>
							<td>$dep_drop</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Occupational Category</td>
							<td>$occ_cat_drop</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Occupational Level</td>
							<td>$occ_level_drop</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>This Position Filled</td>
							<td>$pos_filled_drop</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Recruitment From</td>
							<td><input type='text' size='20' name='recruitment_from' value='$recruitment_from'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Reason for Employment</td>
							<td><input type='text' size='20' name='employment_reason' value='$employment_reason'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Union Name</td>
							<td>$union_drop <a href='#' onClick=\"window.open('union-add.php','unionadd','width=600, height=400');\">Add Union</a></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Union Membership Number</td>
							<td><input type='text' size='20' name='union_mem_num' value='$union_mem_num'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Union Position</td>
							<td><input type='text' size='20' name='union_pos' value='$union_pos'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Vacation Leave</td>
							<td valign='top'><input type='text' size='3' name='vaclea' value='$vaclea'> days</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Sick Leave</td>
							<td valign='top'><input type='text' size='3' name='siclea' value='$siclea'> days</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Study Leave</td>
							<td valign='top'><input type='text' size='3' name='stdlea' value='$stdlea'> days</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td align='right'><input type=submit name='confirmed' value='Confirm &raquo;'></td>
			</tr>
			$fringebens
			$allowances
			$deductions
			$subsistence
			<!--      -->
		</table>
		$ctd
		</form>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee")
		);
	return $editEmp;

}



# Confirm entered info
function confirmEmp ($_POST)
{
	
	$_POST = var_makesafe($_POST);
	extract ($_POST);

//	$hiredate = $month."-".$day."-".$year;
	$hiredate = $year."-".$month."-".$day;
	$termination_date = $t_month."-".$t_day."-".$t_year;

	# validate input
	require_lib("validate");

	$v = new validate ();
	# Limit field lengths as per database settings
	$v->isOk ($empnum,"string", 0, 20, "Invalid empnum.");
	$v->isOk ($enum,"string", 0, 20, "Invalid empnum.");
	$v->isOk ($sname, "string", 1, 50, "Invalid surname.");
	$v->isOk ($fnames, "string", 1, 50, "Invalid first names.");
	$v->isOk ($sex, "string", 1, 1, "Invalid sex.");
	$v->isOk ($changelogo, "string", 1, 3, "Invalid image selection.");
	$v->isOk ($marital, "string", 0, 10, "Invalid marital status.");
	$v->isOk ($resident, "string", 1, 5, "Invalid residential status.");
	$v->isOk ($hiredate, "date", 1, 10, "$hiredate Invalid hire date.");
	$v->isOk ($telno, "string", 0, 30, "Invalid telephone no.");
	$v->isOk ($email, "email", 0, 50, "Invalid email address.");
	$v->isOk ($designation, "string", 0, 100, "Invalid designation.");
	$v->isOk ($hpweek, "float", 1, 5, "Invalid hours per week.");
	$v->isOk ($saltyp, "string", 1, 2, "Invalid salary type.");
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
	$v->isOk ($pcode, "string", 0, 16, "Invalid postal code.");
	$v->isOk ($contsname, "string", 0, 50, "Invalid contact surname.");
	$v->isOk ($contfnames, "string", 0, 50, "Invalid first names.");
	$v->isOk ($contres1, "string", 0, 50, "Invalid contact address. (line 1)");
	$v->isOk ($contres2, "string", 0, 50, "Invalid contact address. (line 2)");
	$v->isOk ($contres3, "string", 0, 50, "Invalid contact address. (line 3)");
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

	$v->isOk ($emp_group, "num", 1, 10, "Invalid Employee Group.");

	$v->isOK ($person_nature, "string", 1, 1, "Invalid Nature Of Person Selection.");

	$v->isOK ($medical_aid, "num", 1, 4, "Invalid Medical Aid Selected.");
	$v->isOK ($medical_aid_number, "string", 0, 25, "Invalid Medical Aid Number.");

	if ( strlen($idnum) >= 6 ) {
		$bd_year = substr($idnum, 0, 2);
		$bd_month = substr($idnum, 2, 2);
		$bd_day = substr($idnum, 4, 2);

		if ( ! (is_numeric($bd_year) && is_numeric($bd_month) && is_numeric($bd_day) && checkdate($bd_month, $bd_day, $bd_year)) ) {
			$v->addError("", "Invalid id num (BD).");
		}
	}

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

	# Check date(feb, day, month, year) ( work of a genius, a master piece :-) )
	$hdate = explode("-", $hiredate);
	if(count($hdate) < 3){
		$v->isOk ($hiredate, "date", 1, 1, "Invalid hire date.");
	}else{
		if($hdate[1] > 29 && $hdate[0] == 2){
			$v->isOk ($hiredate, "date", 1, 1, "Invalid hire date.");
		}elseif($hdate[1] > 31 || $hdate[0] > 12){
			$v->isOk ($hiredate, "date", 1, 1, "Invalid hire date.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>$e[msg]</li>";
		}
		return editEmp($confirmCust);
	}



	$fringes = "";
	$fringe_disp = "";
	$fringes_amount = 0;
	if ( isset($fringebens) ) {
		foreach ( $fringebens as $key => $value ) {
			$fringes .= "
				<input type='hidden' name='fringebens[]' value='$fringebens[$key]'>
				<input type='hidden' name='fringename[]' value='$fringename[$key]'>
				<input type='hidden' name='fringeid[]' value='$fringeid[$key]'>
				<input type='hidden' name='fringetype[]' value='$fringetype[$key]'>
				<input type='hidden' name='fringeexpacc[]' value='$fringeexpacc[$key]'>";

			if ( $fringetype[$key] == "Amount" ) {
				$symbol_cur = CUR;
				$symbol_perc = "";
			} else {
				$symbol_cur = "";
				$symbol_perc = "%";
			}

			if ( $fringebens[$key] > 0 ) {
				$fringe_disp .="
					<tr class='".bg_class()."'>
						<td>$fringename[$key]</td>
						<td>$symbol_cur $fringebens[$key] $symbol_perc</td>
					</tr>";

				$fringes_amount += $fringebens[$key];
			}
		}
	}

	$allow="";
	$all_before="";
	$all_after="";
	$all_beforeamount = 0;
	$all_afteramount = 0;
	if( isset($allowtax) ) {
		foreach ($allowtax as $key => $perc) {
			$allow .="
				<input type='hidden' name='allowname[]' value='$allowname[$key]'>
				<input type='hidden' name='allowid[]' value='$allowid[$key]'>
				<input type='hidden' name='allowances[]' value='$allowances[$key]'>
				<input type='hidden' name='allowtax[]' value='$allowtax[$key]'>
				<input type='hidden' name='allowaccid[]' value='$allowaccid[$key]'>
				<input type='hidden' name='allowtype[]' value='$allowtype[$key]'>";

			if ( $allowtype[$key] == "Amount" ) {
				$symbol_cur = CUR;
				$symbol_perc ="";
			} else {
				$symbol_cur = "";
				$symbol_perc = "%";
			}

			if( $perc == "Yes" && $allowances[$key] > 0 ) {
				$all_before .="
					<tr class='".bg_class()."'>
						<td>$allowname[$key]</td>
						<td>$symbol_cur $allowances[$key] $symbol_perc</td>
					</tr>";

				$all_beforeamount = ($all_beforeamount  + $allowances[$key]);
			} elseif ( $allowances[$key] > 0 ) {
				$all_after .="
					<tr class='".bg_class()."'>
						<td>$allowname[$key]</td>
						<td>$symbol_cur $allowances[$key] $symbol_perc</td>
					</tr>";

				$all_afteramount = ($all_afteramount  + $allowances[$key]);
			}
		}
	}

	$subsistence = "";
	$i = 0;
	if (isset($subsname)) {
		foreach ($subsname as $sid => $sn) {
			$subsistence .= "
				<input type='hidden' name='subsname[$sid]' value='$subsname[$sid]'>
				<input type='hidden' name='subsamt[$sid]' value='$subsamt[$sid]'>
				<input type='hidden' name='subsacc[$sid]' value='$subsacc[$sid]'>
				<input type='hidden' name='subsdays[$sid]' value='$subsdays[$sid]'>
				<tr bgcolor='".bgcolor($i)."'>
					<td>$subsname[$sid]</td>
					<td>".CUR." $subsamt[$sid]</td>
					<td>$subsdays[$sid]</td>
				</tr>";
		}

		if (!empty($subsistence)) {
			$subsistence = "
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='3'>Subsistence Allowances</th>
					</tr>
					<tr>
						<th>Name</th>
						<th>Amount</th>
						<th>Days</th>
					</tr>
					$subsistence
				</table>";
		}
	}

	$deduct = "";
	$de_before = "
		<table width='100%' ".TMPL_tblDflts.">
			<tr>
				<th>Name</th>
				<th>Employee Contribution</th>
				<th>Company Contribution</th>
			</tr>";
	$de_after = "
		<table width='100%' ".TMPL_tblDflts.">
			<tr>
				<th>Name</th>
				<th>Employee Contribution</th>
				<th>Company Contribution</th>
			</tr>";
	$de_beforeamount = 0;
	$de_afteramount = 0;
	if( isset($deducttax) ) {
		foreach ( $deducttax as $key => $perc ) {
			$deduct .="
				<input type='hidden' name='deductname[]' value='$deductname[$key]'>
				<input type='hidden' name='deductid[]' value='$deductid[$key]'>
				<input type='hidden' name='deductions[]' value='$deductions[$key]'>
				<input type='hidden' name='comp_deductions[]' value='$comp_deductions[$key]'>
				<input type='hidden' name='deducttax[]' value='$deducttax[$key]'>
				<input type='hidden' name='deducttype[]' value='$deducttype[$key]'>
				<input type='hidden' name='deductaccid[]' value='$deductaccid[$key]'>";

			if ( $deducttype[$key] == "Amount" ) {
				$symbol_cur = CUR;
				$symbol_perc ="";
			} else {
				$symbol_cur = "";
				$symbol_perc = "%";
			}

			vsprint($deductions[$key]);
			vsprint($comp_deductions[$key]);

            if( $perc == "Yes" && $deductions[$key]>0 ) {
				$de_before .= "
					<tr class='".bg_class()."'>
						<td>$deductname[$key]</td>
						<td>$symbol_cur $deductions[$key] $symbol_perc</td>
						<td>$symbol_cur $comp_deductions[$key] $symbol_perc</td>
					</tr>";

				$de_beforeamount = ($de_beforeamount  + $deductions[$key]);
            } else if ( $deductions[$key] > 0 ) {
				$de_after .= "
					<tr class='".bg_class()."'>
						<td>$deductname[$key]</td>
						<td>$symbol_cur $deductions[$key] $symbol_perc</td>
						<td>$symbol_cur $comp_deductions[$key] $symbol_perc</td>
					</tr>";

				$de_afteramount = ($de_afteramount  + $deductions[$key]);
			}
		}
	}
	$de_before .= "</table>";
	$de_after .= "</table>";

	if ( $fringes_amount > 0 ) $fringe_disp = "<tr><th colspan='2'>Fringe Benefits</th></tr>$fringe_disp";
	if ( $all_beforeamount > 0 ) $all_before ="<tr><th colspan='2'>Allowances</th></tr>$all_before";
	if ( $all_afteramount > 0 ) $all_after ="<tr><th colspan='2'>Allowances</th></tr>$all_after";
	if ( $de_beforeamount > 0 ) {
		$de_before = "
			<tr>
				<th colspan='2'>Deductions Before PAYE</th>
			</tr>
			<tr>
				<td colspan='2'>$de_before</td>
			</tr>";
	} else {
		$de_before = "";
	}
	if ( $de_afteramount > 0 ) {
		$de_after = "
			<tr>
				<th colspan='2'>Deductions After PAYE</th>
			</tr>
			<tr>
				<td colspan='2'>$de_after</td>
			</tr>";
	} else {
		$de_after = "";
	}

	if( $sex == "M" ) {
		$sexx = "Male";
	} else {
		$sexx = "Female";
	}

	$salarr = array("m"=>"Per Month", "w"=>"Per Week", "f"=>"Per 2 Weeks", "h"=>"Per Hour");
	$saltype = $salarr[$saltyp];

	if ( $changelogo == "yes" ) {
		$img = "<tr class='".bg_class()."'><td>Image</td><td><input type='file' size='20' name='logo'></td></tr>";
	} else {
		$img = "";
	}

	db_conn('cubit');

	$sql = "SELECT * FROM costcenters";
	$rslt = db_exec($sql);

	$ctd = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Cost Center</th>
				<th>Percentage</th>
			</tr>";

	$i = 0;

	while( $data = pg_fetch_array($rslt) ) {

		$ctd .= "
			<tr class='".bg_class()."'>
				<td>$data[centername]</td>
				<td><input type='hidden' name='ct[$data[ccid]]' value='".$ct[$data['ccid']]."'>".$ct[$data['ccid']]." %</td>
			</tr>";

		$i++;
	}

	$ctd .= "</table>";

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

	#get occ cats
	$get_cats = "SELECT * FROM occ_cat WHERE id = '$occ_cat' LIMIT 1";
	$run_cats = db_exec($get_cats) or errDie("Unable to get occupational categories.");
	if(pg_numrows($run_cats) < 1){
		$showocc_cat = "Unknown";
	}else {
		$carr = pg_fetch_array($run_cats);
		$showocc_cat = $carr['cat'];
	}

	#get occ category
	$getocc_level = "SELECT * FROM occ_level WHERE id = '$occ_level' LIMIT 1";
	$run_level = db_exec($getocc_level) or errDie("Unable to get occupational levels.");
	if(pg_numrows($run_level) < 1){
		$showocc_level = "Unknown";
	}else {
		$larr = pg_fetch_array($run_level);
		$showocc_level = $larr['level'];
	}

	$get_dep = "SELECT * FROM departments WHERE id = '$department' LIMIT 1";
	$run_dep = db_exec($get_dep) or errDie("Unable to get departments information.");
	if(pg_numrows($run_dep) < 1){
		$showdepartment = "Unknown";
	}else {
		$darr = pg_fetch_array($run_dep);
		$showdepartment = $darr['department'];
	}

	$get_pos = "SELECT * FROM pos_filled WHERE id = '$pos_filled' LIMIT 1";
	$run_pos = db_exec($get_pos) or errDie("Unable to get position filled information.");
	if(pg_numrows($run_pos) < 1){
		$showpos_filled = "Unknown";
	}else {
		$parr = pg_fetch_array($run_pos);
		$showpos_filled = $parr['method'];
	}

	$get_union = "SELECT * FROM unions WHERE id = '$union_name' LIMIT 1";
	$run_union = db_exec($get_union) or errDie("Unable to get unions information.");
	if(pg_numrows($run_pos) < 1){
		$showunion = "Unknown";
	}else {
		$uarr = pg_fetch_array($run_union);
		$showunion = $uarr['union_name'];
	}

	$get_egroup = "SELECT * FROM emp_groups WHERE id = '$emp_group' LIMIT 1";
	$run_egroup = db_exec ($get_egroup) or errDie ("Unable to get employee group information.");
	if (pg_numrows($run_egroup) < 1){
		$show_emp_group = "";
	}else {
		$earr = pg_fetch_array ($run_egroup);
		$show_emp_group = "";
	}

	$natures = array (
		"A" => "Individual with an identity- or password number",
		"B" => "Individual without an identity- or passport number",
		"C" => "Director of a private company / member of a close corporation",
		"D" => "Trust",
		"E" => "Company / cc",
		"F" => "Partnership",
		"G" => "Corporation",
		"H" => "Employment company / personal service company or cc",
		"K" => "Employment trust / personal service trust",
		"M" => "Foreign service income (may only be used with foreign income codes)",
	);

	$medical_aid += 0;
	$get_med = "SELECT medical_aid_name FROM medical_aid WHERE id = '$medical_aid' LIMIT 1";
	$run_med = db_exec ($get_med) or errDie ("Unable to get medical aid information.");
	if (pg_numrows ($run_med) > 0){
		$show_medical_aid = pg_fetch_result ($run_med,0,0);
	}else {
		$show_medical_aid = "None";
	}

	$confirmEmp = "
		<h3>Edit Employee Information</h3>
		<table ".TMPL_tblDflts.">
		<form ENCTYPE='multipart/form-data' action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='write'>
			$fringes
			$allow
			$deduct
			<input type='hidden' name='month' value='$month' />
			<input type='hidden' name='day' value='$day' />
			<input type='hidden' name='year' value='$year' />
			<input type='hidden' name='empnum' value='$empnum' />
			<input type='hidden' name='sname' value='$sname' />
			<input type='hidden' name='designation' value='$designation' />
			<input type='hidden' name='fnames' value='$fnames' />
			<input type='hidden' name='sex' value='$sex' />
			<input type='hidden' name='marital' value='$marital' />
			<input type='hidden' name='resident' value='$resident' />
			<input type='hidden' name='hiredate' value='$hiredate' />
			<input type='hidden' name='telno' value='$telno' />
			<input type='hidden' name='email' value='$email' />
			<input type='hidden' name='hpweek' value='$hpweek' />
			<input type='hidden' name='novert' value='$novert' />
			<input type='hidden' name='hovert' value='$hovert' />
			<input type='hidden' name='bankname' value='$bankname' />
			<input type='hidden' name='bankcode' value='$bankcode' />
			<input type='hidden' name='bankacctype' value='$bankacctype' />
			<input type='hidden' name='bankaccno' value='$bankaccno' />
			<input type='hidden' name='vaclea' value='$vaclea' />
			<input type='hidden' name='siclea' value='$siclea' />
			<input type='hidden' name='stdlea' value='$stdlea' />
			<input type='hidden' name='res1' value='$res1' />
			<input type='hidden' name='res2' value='$res2' />
			<input type='hidden' name='res3' value='$res3' />
			<input type='hidden' name='res4' value='$res4' />
			<input type='hidden' name='pos1' value='$pos1' />
			<input type='hidden' name='pos2' value='$pos2' />
			<input type='hidden' name='pcode' value='$pcode' />
			<input type='hidden' name='contsname' value='$contsname' />
			<input type='hidden' name='contfnames' value='$contfnames' />
			<input type='hidden' name='contres1' value='$contres1' />
			<input type='hidden' name='contres2' value='$contres2' />
			<input type='hidden' name='contres3' value='$contres3' />
			<input type='hidden' name='contres4' value='' />
			<input type='hidden' name='conttelno' value='$conttelno' />
			<input type='hidden' name='idnum' value='$idnum' />
			<input type='hidden' name='passportnum' value='$passportnum' />
			<input type='hidden' name='changelogo' value='$changelogo' />
			<input type='hidden' name='taxref' value='$taxref' />
			<input type='hidden' name='basic_sal' value='$basic_sal' />
			<input type='hidden' name='saltyp' value='$saltyp' />
			<input type='hidden' name='basic_sal_annum' value='$basic_sal_annum' />
			<input type='hidden' name='sal_bonus' value='$sal_bonus' />
			<input type='hidden' name='all_travel' value='$all_travel' />
			<input type='hidden' name='comp_uif' value='$comp_uif' />
			<input type='hidden' name='comp_other' value='$comp_other' />
			<input type='hidden' name='comp_provident' value='$comp_provident' />
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
			<input type='hidden' name='comp_sdl' value='$comp_sdl' />
			<input type='hidden' name='sal_bonus_month' value='$sal_bonus_month' />
			<input type='hidden' name='fringe_car1' value='$fringe_car1' />
			<input type='hidden' name='fringe_car1_contrib' value='$fringe_car1_contrib'>
			<input type='hidden' name='fringe_car1_fuel' value='$fringe_car1_fuel'>
			<input type='hidden' name='fringe_car1_service' value='$fringe_car1_service' />
			<input type='hidden' name='fringe_car2' value='$fringe_car2' />
			<input type='hidden' name='fringe_car2_contrib' value='$fringe_car2_contrib' />
			<input type='hidden' name='fringe_car2_fuel' value='$fringe_car2_fuel' />
			<input type='hidden' name='fringe_car2_service' value='$fringe_car2_service' />
			<input type='hidden' name='emp_usescales' value='$emp_usescales' />
			<input type='hidden' name='enum' value='$enum' />
			<input type='hidden' name='department' value='$department' />
			<input type='hidden' name='occ_cat' value='$occ_cat' />
			<input type='hidden' name='occ_level' value='$occ_level' />
			<input type='hidden' name='pos_filled' value='$pos_filled' />
			<input type='hidden' name='temporary' value='$temporary' />
			<input type='hidden' name='termination_date' value='$termination_date' />
			<input type='hidden' name='recruitment_from' value='$recruitment_from' />
			<input type='hidden' name='employment_reason' value='$employment_reason' />
			<input type='hidden' name='union_name' value='$union_name' />
			<input type='hidden' name='union_mem_num' value='$union_mem_num' />
			<input type='hidden' name='union_pos' value='$union_pos' />
			<input type='hidden' name='race' value='$race' />
			<input type='hidden' name='disabled_stat' value='$disabled_stat' />
			<input type='hidden' name='prevemp_remun' value='$prevemp_remun' />
			<input type='hidden' name='prevemp_tax' value='$prevemp_tax' />
			<input type='hidden' name='payprd' value='$payprd' />
			<input type='hidden' name='payprd_day' value='$payprd_day' />
			<input type='hidden' name='paytype' value='$paytype' />
			<input type='hidden' name='emp_group' value='$emp_group' />
			<input type='hidden' name='person_nature' value='$person_nature' />
			<input type='hidden' name='medical_aid' value='$medical_aid' />
			<input type='hidden' name='medical_aid_number' value='$medical_aid_number' />
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Employee Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Emp Num</td>
							<td>$enum</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Surname</td>
							<td valign='center'>$sname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>First Names</td>
							<td valign='center'>$fnames</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Sex</td>
							<td valign='center'>$sexx</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Race</td>
							<td>$race</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Disabled Status</td>
							<td>$disabled_stat</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Marital Status</td>
							<td valign='center'>$marital</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Resident</td>
							<td valign='center'>$resident</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Employee Group</td>
							<td>$show_emp_group</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Hire Date</td>
							<td valign='center'>$hiredate</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Telephone No</td>
							<td valign='center'>$telno</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Email</td>
							<td valign='center'>$email</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Remuneration</td>
							<td valign='center'>R $basic_sal $saltype</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Hours Per Week</td>
							<td valign='top'>$hpweek&nbsp;&nbsp;Hours</td>
						</tr>
						<tr class='".bg_class()."'>
							<td rowspan='2'>Overtime rate</td>
							<td valign='top'>x $novert&nbsp;&nbsp;Normal</td>
						</tr>
						<tr class='".bg_class()."'>
							<!--         rowspan         -->
							<td valign='top'>x $hovert&nbsp;&nbsp;Public holidays</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Pay Period</td>
							<td valign='top'>$payprd_day</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Pay Type</td>
							<td valign='center'>$paytype</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Name</td>
							<td valign='center'>$bankname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch Code</td>
							<td valign='center'>$bankcode</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Account Type</td>
							<td valign='center'>$bankacctype</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank Account No</td>
							<td valign='center'>$bankaccno</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>UIF: Employee Contribution</td>
							<td valign='center'>$emp_uif %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>UIF: Company Contribution</td>
							<td valign='center'>$comp_uif %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Retirement Annuity: Employee Contribution</td>
							<td valign='center'>".CUR." ".money($emp_ret)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Retirement Annuity: Company Contribution</td>
							<td valign='center'>".CUR." ".money($comp_ret)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Pension: Employee Contribution</td>
							<td valign='center'>".money($emp_pension)." %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Pension: Company Contribution</td>
							<td valign='center'>".money($comp_pension)." %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Medical Contribution: Employee</td>
							<td valign='center'>".CUR." ".money($emp_medical)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Medical Contribution: Employee Beneficiaries</td>
							<td valign='center'>".CUR." ".money($emp_meddeps)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Medical Contribution: Company</td>
							<td valign='center'>".CUR." ".money($comp_medical)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Provident Fund: Employee Contribution</td>
							<td valign='center'>".money($emp_provident)." %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Provident Fund: Company Contribution</td>
							<td valign='center'>".money($comp_provident)." %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Other: Employee Contribution</td>
							<td valign='center'>".money($emp_other)." %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Other: Company Contribution</td>
							<td valign='center'>".money($comp_other)." %</td>
						</tr>
					</table>
				</td>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Employee Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Medical Aid</td>
							<td>$show_medical_aid</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Medical Aid Number</td>
							<td>$medical_aid_number</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Nature of Person</td>
							<td>$natures[$person_nature]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Designation (Job Title)</td>
							<td>$designation</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Department</td>
							<td>$showdepartment</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Occupational Category</td>
							<td>$showocc_cat</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Occupational Level</td>
							<td>$showocc_level</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>This Position Filled</td>
							<td>$showpos_filled</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Temporary (Employee or Contract)</td>
							<td>$temporary</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>If Temporary: Termination Date</td>
							<td>$termination_date</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Recruitment From</td>
							<td>$recruitment_from</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Reason for Employment</td>
							<td>$employment_reason</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Union Name</td>
							<td>$showunion</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Union Membership Number</td>
							<td>$union_mem_num</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Union Position</td>
							<td>$union_pos</td>
						</tr>
						$img
						<tr class='".bg_class()."'>
							<td>ID Num</td>
							<td>$idnum</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Passport Num</td>
							<td>$passportnum</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Income Tax Ref No.</td>
							<td>$taxref</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Vacation Leave</td>
							<td valign='top'>$vaclea days</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Sick Leave</td>
							<td valign='top'>$siclea days</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Study Leave</td>
							<td valign='top'>$stdlea days</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Residential Address</td>
							<td valign='center'>$res1</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$res2</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$res3</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$res4</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Postal Address</td>
							<td valign='center'>$pos1</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$pos2</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Postal Code</td>
							<td valign='center'>$pcode</td>
						</tr>
						<tr><th colspan=2>Friend Not Living With Employee</th></tr>
						<tr class='".bg_class()."'>
							<td>Surname</td>
							<td valign='center'>$contsname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>First Names</td>
							<td valign='center'>$contfnames</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Residential Address</td>
							<td valign='center'>$contres1</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$contres2</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><br></td>
							<td valign='center'>$contres3</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Telephone No</td>
							<td valign='center'>$conttelno</td>
						</tr>
						<tr>
							<td><input type='submit' name='back' value='&laquo; Correction'></td>
							<td align='right'><input type='submit' value='Write &raquo;'></td>
						</tr>
					</table>
				</td>
			</tr>
			$fringe_disp
			$all_before
			$all_after
			$subsistence
			$de_before
			$de_after
	        </table>
		$ctd
		</form>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee")
		);
	return $confirmEmp;

}



# write to database
function writeEmp ($_POST)
{

	$_POST = var_makesafe($_POST);
	global $_FILES;
	extract ($_POST);

	if ( isset($back) ) return editEmp();
//------------------------------------ Jean -----------------------------------
	$comp_uif += 0;
	$comp_sdl += 0;
	$comp_provident += 0;
	$emp_provident += 0;
	$emp_uif += 0;
//-----------------------------------------------------------------------------
	$comp_pension += 0;
	$emp_pension += 0;
	$comp_ret += 0;
	$emp_ret += 0;
	$comp_medical += 0;
	$emp_medical += 0;

	# validate input
	require_lib("validate");

	$v = new validate ();
	# Limit field lengths as per database settings
    $v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($enum, "string",1,20,"Invalid emp num");
    $v->isOk ($sname, "string", 1, 50, "Invalid surname.");
	$v->isOk ($fnames, "string", 1, 50, "Invalid first names.");
	$v->isOk ($sex, "string", 1, 1, "Invalid sex.");
	$v->isOk ($marital, "string", 0, 10, "Invalid marital status.");
	$v->isOk ($designation, "string", 0, 100, "Invalid designation.");
	$v->isOk ($resident, "string", 1, 5, "Invalid residential status.");
	$v->isOk ($hiredate, "date", 1, 10, "Invalid hire date.");
	$v->isOk ($telno, "string", 0, 30, "Invalid telephone no.");
	$v->isOk ($email, "email", 0, 50, "Invalid email address.");
	$v->isOk ($hpweek, "float", 1, 5, "Invalid hours per week.");
	$v->isOk ($novert, "float", 1, 9, "Invalid normal overtime.");
	$v->isOk ($hovert, "float", 1, 9, "Invalid holiday overtime.");
	$v->isOk ($paytype, "string", 1, 15, "Invalid pay type.");
	$v->isOk ($bankname, "string", 0, 50, "Invalid bank name.");
	$v->isOk ($bankcode, "string", 0, 8, "Invalid bank code.");
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
	$v->isOk ($pcode, "string", 0, 16, "Invalid postal code.");
	$v->isOk ($contsname, "string", 0, 50, "Invalid contact surname.");
	$v->isOk ($contfnames, "string", 0, 50, "Invalid first names.");
	$v->isOk ($contres1, "string", 0, 50, "Invalid contact address. (line 1)");
	$v->isOk ($contres2, "string", 0, 50, "Invalid contact address. (line 2)");
	$v->isOk ($contres3, "string", 0, 50, "Invalid contact address. (line 3)");
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

	$v->isOk ($emp_group, "num", 1, 10, "Invalid Employee Group.");

	$v->isOK ($person_nature, "string", 1, 1, "Invalid Nature Of Person Selection.");

	$v->isOK ($medical_aid, "num", 1, 4, "Invalid Medical Aid Selected.");
	$v->isOK ($medical_aid_number, "string", 0, 25, "Invalid Medical Aid Number.");

	if ( strlen($idnum) >= 6 ) {
		$bd_year = substr($idnum, 0, 2);
		$bd_month = substr($idnum, 2, 2);
		$bd_day = substr($idnum, 4, 2);

		if ( ! (is_numeric($bd_year) && is_numeric($bd_month) && is_numeric($bd_day) && checkdate($bd_month, $bd_day, $bd_year)) ) {
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
            $v->isOk ($comp_deductions[$key], "float", 0, 20, "Invalid deduction employer contribution amount".($key+1).".");
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
		return editEmp($confirmCust);
	}



	db_connect ();

	# deal with logo image
        if ($changelogo == "yes") {
		if (empty ($_FILES["logo"])) {
			return "<li class='err'> Please select an image to upload from your hard drive.</li>";
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

				$Sl = "DELETE FROM eimgs WHERE emp='$empnum'";
				$Ry = db_exec($Sl) or errDie("Error removing prev imgs.");

				$Sl = "INSERT INTO eimgs (emp,image, imagetype) VALUES ('$empnum','$img','$type')";
				$Ry = db_exec($Sl) or errDie("Unable to upload company logo Image to DB.",SELF);

				# to show IMG
				//$logoimg = "<br><img src='compinfo/getimg.php' width=230 height=47><br><br>";
				//$logo = "compinfo/getimg.php";
			}else {
				return "<li class='err'>Please note that we only accept images of the types PNG,GIF and JPEG.</li>";
			}
		} else {
			return "<li class='err'>Unable to upload file, Please check file permissions.</li>";
		}
	}

	# if data is ok, write to db
	db_connect ();
//------------------------------------ Jean -----------------------------------
    $comp_sdl += 0;
    $comp_uif += 0;
	$comp_provident += 0;
	$emp_provident += 0;
	$emp_uif += 0;
//-----------------------------------------------------------------------------
	$comp_pension += 0;
	$emp_pension += 0;
	$comp_ret += 0;
	$emp_ret += 0;
	$comp_medical += 0;
	$emp_medical += 0;

	$sal_bonus += 0;
	$all_travel += 0;

	/* FOR AUDITING PURPOSES THESE VALUES HAVE BEEN HARDCODED */
	$comp_sdl = 1;
	$comp_uif = 1;
	$emp_uif = 1;
	/* DONE */

	$basic_sal = sprint($basic_sal);

	if ($resident == "Yes") {$resident = "TRUE";} else {$resident = "FALSE";}

	$sql = "
		UPDATE employees 
		SET idnum='$idnum', passportnum='$passportnum', sex='$sex', sname='$sname', fnames='$fnames', marital='$marital', 
			resident='$resident', hiredate='$hiredate', telno='$telno', email='$email', basic_sal='$basic_sal', 
			hpweek='$hpweek', novert='$novert', hovert='$hovert', paytype='$paytype', taxref='$taxref', enum='$enum', 
			payprd_day='$payprd_day', bankname='$bankname', bankcode='$bankcode', bankacctype='$bankacctype', 
			bankaccno='$bankaccno', vaclea='$vaclea', siclea='$siclea', stdlea='$stdlea', res1='$res1', res2='$res2', 
			res3='$res3', res4='$res4', pos1='$pos1', pos2='$pos2', pcode='$pcode', contsname='$contsname', 
			contfnames='$contfnames', contres1='$contres1', contres2='$contres2', contres3='$contres3', 
			conttelno='$conttelno', designation='$designation', basic_sal_annum='$basic_sal_annum', sal_bonus='$sal_bonus', 
			sal_bonus_month='$sal_bonus_month', all_travel='$all_travel', comp_uif='$comp_uif', comp_sdl='$comp_sdl', 
			emp_uif='$emp_uif', comp_pension='$comp_pension', emp_pension='$emp_pension', comp_ret='$comp_ret', 
			emp_ret='$emp_ret', comp_medical='$comp_medical', emp_medical='$emp_medical', emp_meddeps='$emp_meddeps', 
			comp_provident='$comp_provident', emp_provident='$emp_provident', comp_other='$comp_other', 
			emp_other='$emp_other', payprd='$payprd', saltyp='$saltyp', department = '$department', occ_cat = '$occ_cat', 
			occ_level = '$occ_level', pos_filled = '$pos_filled', temporary = '$temporary', 
			termination_date = '$termination_date', recruitment_from = '$recruitment_from', 
			employment_reason = '$employment_reason', union_name = '$union_name', union_mem_num = '$union_mem_num', 
			union_pos = '$union_pos', race = '$race', disabled_stat = '$disabled_stat', fringe_car1='$fringe_car1', 
			fringe_car1_contrib='$fringe_car1_contrib', fringe_car1_fuel='$fringe_car1_fuel', 
			fringe_car1_service='$fringe_car1_service', fringe_car2='$fringe_car2', 
			fringe_car2_contrib='$fringe_car2_contrib', fringe_car2_fuel='$fringe_car2_fuel', 
			fringe_car2_service='$fringe_car2_service', flag=NULL,prevemp_remun='$prevemp_remun', 
			prevemp_tax='$prevemp_tax', emp_group='$emp_group', person_nature = '$person_nature', 
			medical_aid = '$medical_aid', medical_aid_number = '$medical_aid_number', emp_usescales = '$emp_usescales' 
		WHERE empnum = '$empnum' AND div = '".USER_DIV."'";
    $nwEmpRslt = db_exec ($sql) or errDie ("Unable to update employee information.");
//-----------------------------------------------------------------------------
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
			$subsamt[$sid] += 0;
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
					'$id', '$empnum', '$deductions[$i]', '$comp_deductions[$i]', '".USER_DIV."', '$deducttype[$i]', 
					'$deducttype[$i]', '$ltsal', '$deductaccid[$i]'
				)";
			$deductRslt = db_exec ($sql) or errDie ("Unable to process Employee deductions in database.");
		}
	}

	if ( isset($fringeid) ) {
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

	db_conn('cubit');

	$Sl = "SELECT * FROM costcenters";
	$Ri = db_exec($Sl);

	$i = 0;

	$Sl = "DELETE FROM empc WHERE emp='$empnum'";
	$Rl = db_exec($Sl);

	while($data = pg_fetch_array($Ri)) {

		if($ct[$data['ccid']] > 0) {
			$Sl = "INSERT INTO empc(cid,emp,amount) VALUES ('$data[ccid]','$empnum','".$ct[$data['ccid']]."')";
			$Rl = db_exec($Sl);
		}

		$i++;
	}

	# Provide some info on status
	$writeEmp = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Employee details edited</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Employee details for employee number, $enum, has been successfully edited.</td>
			</tr>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee")
		);
	return $writeEmp;

}



?>