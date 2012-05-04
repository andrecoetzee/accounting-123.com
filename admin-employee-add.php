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
require_lib("time");

if ( isset($_GET["key"]) ) {
	$_POST["key"] = $_GET["key"];
}

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "salary":
			$OUTPUT = salary();
			break;
		case  "confirm":
			if (!isset ($_POST["confirmed"])){
				$OUTPUT = enterEmp ();
			}else {
				$OUTPUT = confirmEmp ($_POST);
			}
			break;
		case "write":
			$OUTPUT = writeEmp ($_POST);
			break;
		default:
			$OUTPUT = enterEmp ();
	}
} else {
	# print form for data entry
	$OUTPUT = enterEmp ();
}

require ("template.php");



##
# Functions
##

# form to enter new data
function enterEmp ($err="")
{

	global $_POST;

	$fields = Array();

	// de-poo'd and quintified
	// iow
	// from: function enterEmp ($idnum="",$sname="", $fnames="", $hiredate="", $telno="", $email="", $basic_sal="", $bankname="", $bankcode="", $bankaccno="", $res1="", $res2="", $res3="", $res4="", $pos1="", $pos2="", $pcode="", $contsname="", $contfnames="", $contres1="", $contres2="", $contres3="", $conttelno="",$sex="",$marital="",$resident="",$paytype="",$bankacctype="",$empnum="",$designation="", $err="")
	// to: below + function enterEmp($err="")
	$fields["idnum"] = "";
	$fields["passportnum"] = "";
	$fields["sname"] = "";
	$fields["fnames"] = "";
	$fields["hiredate"] = "";
	$fields["telno"] = "";
	$fields["email"] = "";
	$fields["basic_sal"] = "";
	$fields["bankname"] = "";
	$fields["bankcode"] = "";
	$fields["bankaccno"] = "";
	$fields["res1"] = "";
	$fields["res2"] = "";
	$fields["res3"] = "";
	$fields["res4"] = "";
	$fields["pos1"] = "";
	$fields["pos2"] = "";
	$fields["pcode"] = "";
	$fields["contsname"] = "";
	$fields["contfnames"] = "";
	$fields["contres1"] = "";
	$fields["contres2"] = "";
	$fields["contres3"] = "";
	$fields["conttelno"] = "";
	$fields["sex"] = "";
	$fields["marital"] = "";
	$fields["resident"] = "";
	$fields["paytype"] = "Cash";
	$fields["bankacctype"] = "";
	$fields["empnum"] = "";
	$fields["designation"] = "";
	$fields["all_travel"] = "0.00";
	$fields["saltyp"] = "m";
	$fields["basic_sal_annum"] = "0.00";
	$fields["novert"] = "1.5";
	$fields["hovert"] = "2";
	$fields["sal_bonus"] = "0.00";
	$fields["sal_bonus_month"] = "12";
	$fields["comp_pension"] = "0";
	$fields["emp_pension"] = "0";
	$fields["comp_ret"] = "0.00";
	$fields["emp_ret"] = "0.00";
	$fields["comp_medical"] = "0.00";
	$fields["emp_medical"] = "0.00";
	$fields["emp_meddeps"] = "0";
	$fields["comp_provident"] = "0";
	$fields["emp_provident"] = "0";
	$fields["comp_sdl"] = "1";
	$fields["comp_uif"] = "1";
	$fields["emp_uif"] = "1";
	$fields["comp_other"] = "0";
	$fields["emp_other"] = "0";
	$fields["fringe_car1"] = "0.00";
	$fields["fringe_car1_contrib"] = "0.00";
	$fields["fringe_car1_fuel"] = "0";
	$fields["fringe_car1_service"] = "0";
	$fields["fringe_car2"] = "0.00";
	$fields["fringe_car2_contrib"] = "0.00";
	$fields["fringe_car2_fuel"] = "0";
	$fields["fringe_car2_service"] = "0";
	$fields["emp_usescales"] = "0";
	$fields["payprd"] = "m";
	$fields["payprd_day"] = "fri";
	$fields["hpweek"] = "40";
	$fields["taxref"] = "";
	$fields["department"] = "";
	$fields["occ_cat"] = "";
	$fields["occ_level"] = "";
	$fields["pos_filled"] = "External appointment";
	$fields["temporary"] = "no";
	$fields["termination_date"] = "";
	$fields["recruitment_from"] = "Advertised Position";
	$fields["employment_reason"] = "Vacant Position";
	$fields["union_name"] = "";
	$fields["union_mem_num"] = "None";
	$fields["union_pos"] = "None";
	$fields["race"] = "";
	$fields["disabled_stat"] = "No";
	$fields["prevemp_remun"] = "";
	$fields["prevemp_tax"] = "";
	$fields["hd_year"] = DATE_YEAR;
	$fields["hd_month"] = DATE_MONTH;
	$fields["hd_day"] = DATE_DAY;

	$fields["hd_month"] = "";
	$fields["hd_year"] = "";
	$fields["hd_day"] = "";
	
	$fields["emp_group"] = "";

	$fields["person_nature"] = "";

	$fields["medical_aid"] = "";
	$fields["medical_aid_number"] = "";


	db_conn("cubit");

	$sql = "SELECT value FROM settings WHERE constant='UIF_COMP'";
	$rslt = db_exec($sql) or errDie("Error reading company UIF setting.");

	if ( pg_num_rows($rslt) ) {
		$fields["comp_uif"] = pg_fetch_result($rslt, 0, 0);
	}

	foreach ( $fields as $fn => $fv ) {
		if ( ! isset($_POST[$fn]) ) {
			$_POST[$fn] = $fv;
		}
	}

	extract($_POST);

	$Tp = array("M"=>"Male","F"=>"Female");
	$sexs = extlib_cpsel("sex", $Tp,$sex);

	$overarr = array("1" => "x 1", "1.5" => "x 1.5", "2" => "x 2", "2.5" => "x 2.5", "3" => "x 3");
	//$noverts = extlib_cpsel("novert", $overarr, "1.5");
	//$hoverts = extlib_cpsel("hovert", $overarr, "2");

	$Tp = array("Single" => "Single","Married" => "Married","Widowed" => "Widowed","Divorced" => "Divorced");
	$maritals = extlib_cpsel("marital", $Tp,$marital);

	$Tp = array("Yes" => "Yes","No" => "No");
	$residents = extlib_cpsel("resident", $Tp,$resident);

	$rslt = db_exec("SELECT accname FROM bankacctypes");
	// if no bank account types were found, add the default
	if ( pg_num_rows($rslt) < 1 ) {
		db_exec("INSERT INTO bankacctypes (accname) VALUES('Savings')");
		db_exec("INSERT INTO bankacctypes (accname) VALUES('Current or Cheque')");
		db_exec("INSERT INTO bankacctypes (accname) VALUES('Credit Card')");

		$Tp = array("Savings"=>"Savings","Current or Cheque"=>"Current or Cheque","Credit Card"=>"Credit Card");
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
			$allowances .= "
				<input type='hidden' name='allowid[$aid]' value='$aid'>
				<input type='hidden' name='allowname[$aid]' value='$myAllow[allowance]'>
				<input type='hidden' name='allowtax[$aid]' value='$myAllow[add]'>
				<input type='hidden' name='allowances[$aid]' value=''>
				<input type='hidden' name='allowaccid[$aid]' value='$myAllow[accid]'>
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

			if (!isset($subsamt[$sid])) $subsamt[$sid] = "0.00";
			if (!isset($subsdays[$sid])) $subsdays[$sid] = 0;

			$subsistence .= "
				<input type='hidden' name='subsname[$sid]' value='$subs[name]'>
				<input type='hidden' name='subsamt[$sid]' value='$subsamt[$sid]'>
				<input type='hidden' name='subsacc[$sid]' value='$subs[accid]'>
				<input type='hidden' name='subsdays[$sid]' value='$subsdays[$sid]'>";
		}
	}

	$deductions = "";
	$deductions_ids = Array();
	$sql = "SELECT * FROM salded WHERE div = '".USER_DIV."' ORDER BY deduction";
	$deductRslt = db_exec ($sql) or errDie("Unable to select deductions from database.");
	if (pg_numrows ($deductRslt) > 0) {
		while ( $myDeduct = pg_fetch_array ($deductRslt) ) {
			$did = $myDeduct["id"];

			if ( $myDeduct["creditor"] == "In House" ) {
				$deduct_acc = "$myDeduct[expaccid]";
			} else {
				$deduct_acc = "$myDeduct[accid]";
			}

			$deductions .= "
				<input type='hidden' name='deductid[$did]' value='$did'>
				<input type='hidden' name='deductname[$did]' value='$myDeduct[deduction]'>
				<input type='hidden' name='deducttax[$did]' value='$myDeduct[add]'>
				<input type='hidden' name='deductions[$did]' value=''>
				<input type='hidden' name='comp_deductions[$did]' value=''>
				<input type='hidden' name='deducttype[$did]' value='$myDeduct[type]'>
				<input type='hidden' name='deductaccid[$did]' value='$deduct_acc'>";

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

			$fringebens .= "
				<input type='hidden' name='fringeid[$fid]' value='$fid'>
				<input type='hidden' name='fringename[$fid]' value='$myFringe[fringeben]'>
				<input type='hidden' name='fringebens[$fid]' value=''>
				<input type='hidden' name='fringetype[$fid]' value='$myFringe[type]'>
				<input type='hidden' name='fringeexpacc[$fid]' value='$myFringe[accid]'>";

			$fringebens_ids[] = $fid;
		}
	}

	$lvac = getLeave ("leave_vac");
	$lsick = getLeave ("leave_sick");
	$lstudy = getLeave ("leave_study");

	db_conn('cubit');

	$Sl="SELECT * FROM costcenters";
	$Ri=db_exec($Sl);

	$ctd="
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Cost Center</th>
				<th>Percentage</th>
			</tr>";

	$i = 0;

	while($data=pg_fetch_array($Ri)) {
		$bgcolor = bgcolor($i);

		$Sl = "SELECT * FROM empc WHERE emp='0' AND cid='$data[ccid]'";
		$Rq = db_exec($Sl);

		$cd = pg_fetch_array($Rq);

		$ctd .= "
			<tr class='".bg_class()."'>
				<td>$data[centername]</td>
				<td><input type='text' name='ct[$data[ccid]]' size='5' value='$cd[amount]'>%</td>
			</tr>";
	}

	if ($i > 0) {
		$ctd .= "</table>";
	} else {
		$ctd = "";
	}

	// setup the display value for the renumeration, in case we get sent back to this step because of
	// validation errors, at least the salary is still displayed
	switch ( $saltyp ) {
		case 'w':
			$salperiod = 'per Week';
			$saldivisor = 52;
			break;
		case 'h':
			$salperiod = 'per Hour';
			$saldivisor = 52 * $hpweek;
			break;
		case 'f':
			$salperiod = 'Fortnightly';
			$saldivisor = 26;
			break;
		case 'm':
		default:
			$salperiod = 'per Month';
			$saldivisor = 12;
			break;
	}

	$salval = CUR . " " . sprint($basic_sal_annum / $saldivisor) . " $salperiod";

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
	$occ_level = "SELECT * FROM occ_level ORDER BY id";
	$run_level = db_exec($occ_level) or errDie("Unable to get occupational levels.");
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

	db_connect ();

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

	$i = 0;

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

	# Set up table & form
	$enterEmp = "
		<h3>Add New Employee to Database</h3>
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
		<form id='emplfrm' action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='saltyp' value='$saltyp' />
			<input type='hidden' name='basic_sal_annum' value='$basic_sal_annum' />
			<input type='hidden' name='sal_bonus' value='$sal_bonus' />
			<input type='hidden' name='sal_bonus_month' value='$sal_bonus_month' />
			<input type='hidden' name='all_travel' value='$all_travel' />
			<input type='hidden' name='comp_sdl' value='$comp_sdl' />
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
			<input type='hidden' name='fringe_car1' value='$fringe_car1' />
			<input type='hidden' name='fringe_car1_contrib' value='$fringe_car1_contrib' />
			<input type='hidden' name='fringe_car1_fuel' value='$fringe_car1_fuel' />
			<input type='hidden' name='fringe_car1_service' value='$fringe_car1_service' />
			<input type='hidden' name='fringe_car2' value='$fringe_car2' />
			<input type='hidden' name='fringe_car2_contrib' value='$fringe_car2_contrib' />
			<input type='hidden' name='fringe_car2_fuel' value='$fringe_car2_fuel' />
			<input type='hidden' name='fringe_car2_service' value='$fringe_car2_service' />
			<input type='hidden' name='emp_usescales' value='$emp_usescales' />
			<input type='hidden' name='payprd' value='$payprd' />
			<input type='hidden' name='payprd_day' value='$payprd_day' />
			<input type='hidden' name='paytype' value='$paytype' />
			$allowances
			$deductions
			$fringebens
			$subsistence
			$err
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
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Emp Num</td>
							<td><input type='text' size='20' name='empnum' value='$empnum'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>".REQ."Surname</td>
							<td valign='center'><input type='text' size='20' name='sname' value='$sname'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>".REQ."First Names</td>
							<td valign='center'><input type='text' size='20' name='fnames' value='$fnames'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>".REQ."ID Num</td>
							<td><input type='text' size='20' name='idnum' value='$idnum' onChange='updateBirthDate(this);'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t' align='center' colspan='2'><b>OR</b></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Passport Num</td>
							<td><input type='text' size='20' name='passportnum' value='$passportnum'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Birthdate</td>
							<td><div id='birthdate'></div></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Sex</td>
							<td valign='center'>$sexs</td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td>Race</td>
							<td>$racedrop</td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Disabled Status</td>
							<td><input type='text' name='disabled_stat' value='$disabled_stat'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Marital Status</td>
							<td valign='center'>$maritals</td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td>Resident</td>
							<td valign='center'>$residents</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Employee Group</td>
							<td>$emp_group_drop</td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Telephone No</td>
							<td valign='center'><input type='text' size='20' name='telno' value='$telno'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td>Email</td>
							<td valign='center'><input type='text' size='20' name='email' value='$email'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Bank Name</td>
							<td valign='center'><input type='text' size='20' name='bankname' value='$bankname'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Branch Code</td>
							<td valign='center'><input type='text' size='20' name='bankcode' value='$bankcode'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Bank Account Type</td>
							<td valign='center'>$bankacctypes</td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Bank Account No</td>
							<td valign='center'><input type='text' size='20' name='bankaccno' value='$bankaccno'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>".REQ."Residential Address</td>
							<td valign='center'><input type='text' size='20' name='res1' value='$res1'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td><br></td>
							<td valign='center'><input type='text' size='20' name='res2' value='$res2'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td><br></td>
							<td valign='center'><input type='text' size='20' name='res3' value='$res3'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td><br></td>
							<td valign='center'><input type='text' size='20' name='res4' value='$res4'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Postal Address</td>
							<td valign='center'><input type='text' size='20' name='pos1' value='$pos1'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td><br></td>
							<td valign='center'><input type='text' size='20' name='pos2' value='$pos2'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Postal Code</td>
							<td valign='center'><input type='text' size='20' name='pcode' value='$pcode'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Upload Image</td>
							<td>Yes<input type='radio' name='changelogo' value='yes'> - No<input type='radio' name='changelogo' value='no' checked='yes'>
						</tr>
						<tr>
							<th colspan='2'>Friend Not Living With Employee</th>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td>Surname</td>
							<td valign='center'><input type='text' size='20' name='contsname' value='$contsname'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>First Names</td>
							<td valign='center'><input type='text' size='20' name='contfnames' value='$contfnames'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Residential Address</td>
							<td valign='center'><input type='text' size='20' name='contres1' value='$contres1'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td><br></td>
							<td valign='center'><input type='text' size='20' name='contres2' value='$contres2'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td><br></td>
							<td valign='center'><input type='text' size='20' name='contres3' value='$contres3'></td>
						</tr>
						<tr bgcolor='".bgcolor($i)."'>
							<td nowrap='t'>Telephone No</td>
							<td valign='center'><input type='text' size='20' name='conttelno' value='$conttelno'></td>
						</tr>
					</table>
					$ctd
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

	$enterEmp .= "
			<td valign='top'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Tax from Previous Employer for Current Employee Financial Year ($empfin_start)</th>
					</tr>
					<tr>
						<td colspan='2' class='err'>It is important to enter
							the taxable earnings of an employee for the period
							from the beginning
							of March to the date of actual employment, irrespective
							of the employer's financial year end. Cubit is
							configured to take those earnings and PAYE
							paid in respect of such earnings during the employee's tax
							year, which is March to February, into consideration in the
							determination of PAYE of the employee.
							Consequently the PAYE determined by Cubit will be
							different to most Payroll systems, but
							may be far more accurate.</li>
						</td>
					</tr>
					<tr>
						<td colspan='2' class='err'>If \"0\" is inserted below in respect of the employee's
							prior taxable earnings and PAYE, Cubit is configured to assume
							that the hire date is the first time that the employee is employed
							for the purpose of income tax.
						</td>
					</tr>
					<tr>
						<td colspan='2' class='err'>
							If you do not have the previous employee earnings data you can 
							calculate an estimated amount and an estimated amount of
							tax, but do not leave the fields on '0' as this will result in incorrect
							calculation of present taxes.
						</td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td nowrap='t'>".REQ." Total Remuneration from Previous Employer (or your estimate)</td>
						<td><input type='text' size='20' name='prevemp_remun' value='$prevemp_remun' /></td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td nowrap='t'>".REQ." Total Tax from Previous Employer (or your estimate)</td>
						<td><input type='text' size='20' name='prevemp_tax' value='$prevemp_tax' /></td>
					</tr>
					<tr>
						<th colspan='2'>Employment Details</th>
					</tr>
					<tr bgcolor='".bgcolorc($i)."'>
						<td rowspan='2'>Remuneration</td>
						<td><div id='div_basic_sal'>$salval</div></td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
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
					<tr bgcolor='".bgcolor($i)."'>
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
					<tr bgcolor='".bgcolor($i)."'>
						<td>Income Tax Ref No.</td>
						<td><input type='text' size='20' name='taxref' value='$taxref'></td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>".REQ." Hire Date<li class='err'>Please use correct date</li></td>
						<td nowrap='t'>".mkDateSelect("hd", $hd_year, $hd_month, $hd_day)."</td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Temporary (Employee or Contract)</td>
						<td><input type='radio' name='temporary' value='yes'> Yes <input type='radio' name='temporary' value='no' checked='yes'> No</td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>If Temporary: Termination Date</td>
						<td valign='bottom' nowrap>
							".mkDateSelect("t")."
						</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Nature Of Person</td>
						<td>$natures_drop</td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Designation (Job Title)</td>
						<td><input type='text' size='20' name='designation' value='$designation'></td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Department</td>
						<td>$dep_drop</td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Occupational Category</td>
						<td>$occ_cat_drop</td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Occupational Level</td>
						<td>$occ_level_drop</td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>This Position Filled</td>
						<td>$pos_filled_drop</td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Recruitment From</td>
						<td><input type='text' size='20' name='recruitment_from' value='$recruitment_from'></td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Reason for Employment</td>
						<td><input type='text' size='20' name='employment_reason' value='$employment_reason'></td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Union Name</td>
						<td>$union_drop <a href='#' onClick=\"window.open('union-add.php','unionadd','width=600, height=400');\">Add Union</a></td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Union Membership Number</td>
						<td><input type='text' size='20' name='union_mem_num' value='$union_mem_num'></td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Union Position</td>
						<td><input type='text' size='20' name='union_pos' value='$union_pos'></td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Vacation Leave</td>
						<td valign='top'><input type='text' size='3' name='vaclea' value='$lvac'> days</td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Sick Leave</td>
						<td valign='top'><input type='text' size='3' name='siclea' value='$lsick'> days</td>
					</tr>
					<tr bgcolor='".bgcolor($i)."'>
						<td>Study Leave</td>
						<td valign='top'><input type='text' size='3' name='stdlea' value='$lstudy'> days</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td align='right'><input type='submit' name='confirmed' value='Confirm &raquo;'></td>
		</tr>
	</table>
	</form>"
	.mkQuickLinks();
	return $enterEmp;

}



function confirmEmp ($_POST)
{

	$_POST = var_makesafe($_POST);
	extract ($_POST);

	$hiredate = mkdate($hd_year, $hd_month, $hd_day);
	$termination_date = mkdate($t_year, $t_month, $t_day);

	# validate input
	require_lib("validate");

	$v = new validate ();
	# Limit field lengths as per database settings
	$v->isOk ($empnum,"string", 0, 20, "Invalid empnum.");
	$v->isOk ($sname, "string", 1, 50, "Invalid surname.");
	$v->isOk ($fnames, "string", 1, 50, "Invalid first names.");
	$v->isOk ($sex, "string", 1, 1, "Invalid sex.");
	$v->isOk ($changelogo, "string", 1, 3, "Invalid image selection.");
	$v->isOk ($marital, "string", 0, 10, "Invalid marital status.");
	$v->isOk ($resident, "string", 1, 5, "Invalid residential status.");
	$v->isOk ($hiredate, "date", 1, 10, "Invalid hire date.");
	$v->isOk ($telno, "string", 0, 30, "Invalid telephone no.");
	$v->isOk ($email, "email", 0, 255, "Invalid email address.");
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

	$v->isOk ($prevemp_remun, "float", 1, 50, "Invalid Value for Previous Employer Remuneration (this field is required).");
	$v->isOk ($prevemp_tax, "float", 1, 50, "Invalid Value for Previous Employer PAYE/Tax (this field is required).");

	$v->isOk ($emp_group, "num", 1, 10, "Invalid Employee Group.");

	$v->isOK ($person_nature, "string", 1, 1, "Invalid Nature Of Person Selection.");

	$v->isOK ($medical_aid, "num", 1, 4, "Invalid Medical Aid Selected.");
	$v->isOK ($medical_aid_number, "string", 0, 25, "Invalid Medical Aid Number.");

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

	$tdate = explode("-", $termination_date);
	if(count($tdate) < 3){
		$v->isOk ($termination_date, "date", 1, 1, "Invalid termination date.");
	}else{
		if($tdate[1] > 29 && $tdate[0] == 2){
			$v->isOk ($termination_date, "date", 1, 1, "Invalid termination date.");
		}elseif($tdate[1] > 31 || $tdate[0] > 12){
			$v->isOk ($termination_date, "date", 1, 1, "Invalid termination date.");
		}
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>$e[msg]</li>";
		}

		return enterEmp($confirmCust);
	}


	db_conn("cubit");

	$sql = "SELECT * FROM employees WHERE enum='$empnum'
				OR (fnames='$fnames' AND sname='$sname')
				OR ((idnum='$idnum' AND idnum!='') OR (passportnum='$passportnum' AND passportnum!=''))";
	$rslt = db_exec($sql) or errDie("Error checking for employee duplicity.");

	if ( pg_num_rows($rslt) > 0 ) {
		return enterEmp("<li class=err>An employee with this employee number,
			or name or id/passport number already exists</li>");
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

	$allow = "";
	$all_before = "";
	$all_after = "";
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

			if( $perc == "Yes" && $allowances[$key]>0 ) {
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
	$de_before="
		<table width='100%' ".TMPL_tblDflts.">
			<tr>
				<th>Name</th>
				<th>Employee Contribution</th>
				<th>Company Contribution</th>
			</tr>";
	$de_after="
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

            if( $perc == "Yes" && $deductions[$key]>0 ) {
				$de_before .="
					<tr class='".bg_class()."'>
						<td>$deductname[$key]</td>
						<td>$symbol_cur $deductions[$key] $symbol_perc</td>
						<td>$symbol_cur $comp_deductions[$key] $symbol_perc</td>
					</tr>";

				$de_beforeamount = ($de_beforeamount  + $deductions[$key]);
            } else if ( $deductions[$key] > 0 ) {
				$de_after .="
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
			<tr><th colspan='2'>Deductions Before PAYE</th></tr>
			<tr><td colspan='2'>$de_before</td></tr>";
	} else {
		$de_before = "";
	}
	if ( $de_afteramount > 0 ) {
		$de_after = "
			<tr><th colspan='2'>Deductions After PAYE</th></tr>
			<tr><td colspan='2'>$de_after</td></tr>";
	} else {
		$de_after = "";
	}

	if( $sex == "M" ) {
		$sexx = "Male";
	} else {
		$sexx = "Female";
	}

	$salarr = array("m"=>"Per Month", "w"=>"Per Week", "f"=>"Per 2 Weeks","d"=>"Per Day", "h"=>"Per Hour");
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

	$i=0;

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

	$basic_sal_annum = sprint($basic_sal_annum);
	$sal_bonus = sprint($sal_bonus);
	$all_travel = sprint($all_travel);
	$comp_uif = sprint($comp_uif);
	$comp_other = sprint($comp_other);
	$comp_provident = sprint($comp_provident);
	$comp_medical = sprint($comp_medical);
	$comp_ret = sprint($comp_ret);
	$comp_pension = sprint($comp_pension);
	$emp_uif = sprint($emp_uif);
	$emp_other = sprint($emp_other);
	$emp_provident = sprint($emp_provident);
	$emp_medical = sprint($emp_medical);
	$emp_ret = sprint($emp_ret);
	$emp_pension = sprint($emp_pension);
	$comp_sdl = sprint($comp_sdl);
	$fringe_car1 = sprint($fringe_car1);
	$fringe_car2 = sprint($fringe_car2);

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
		<h3>Add New Employee to Database</h3>
		<table ".TMPL_tblDflts.">
		<form ENCTYPE='multipart/form-data' action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='write'>
			$fringes
			$allow
			$deduct
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
			<input type='hidden' name='payprd' value='$payprd' />
			<input type='hidden' name='payprd_day' value='$payprd_day' />
			<input type='hidden' name='paytype' value='$paytype' />
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
			<input type='hidden' name='fringe_car1_contrib' value='$fringe_car1_contrib' />
			<input type='hidden' name='fringe_car1_fuel' value='$fringe_car1_fuel' />
			<input type='hidden' name='fringe_car1_service' value='$fringe_car1_service' />
			<input type='hidden' name='fringe_car2_contrib' value='$fringe_car2_contrib' />
			<input type='hidden' name='fringe_car2_fuel' value='$fringe_car2_fuel' />
			<input type='hidden' name='fringe_car2_service' value='$fringe_car2_service' />
			<input type='hidden' name='emp_usescales' value='$emp_usescales' />
			<input type='hidden' name='fringe_car2' value='$fringe_car2' />
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
							<td>$empnum</td>
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
							<td>Hire Date<li class='err'>Ensure this date is correct as
								it will be used in<br />PAYE calculations and cannot be
								changed once saved.</li></td>
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
							<td valign='center'>".CUR." $emp_ret</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Retirement Annuity: Company Contribution</td>
							<td valign='center'>".CUR." $comp_ret</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Pension: Employee Contribution</td>
							<td valign='center'>$emp_pension %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Pension: Company Contribution</td>
							<td valign='center'>$comp_pension %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Medical Contribution: Employee</td>
							<td valign='center'>".CUR." $emp_medical</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Medical Contribution: Employee Beneficiaries</td>
							<td valign='center'>$emp_meddeps</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Medical Contribution: Company</td>
							<td valign='center'>".CUR." $comp_medical</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Provident Fund: Employee Contribution</td>
							<td valign='center'>$emp_provident %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Provident Fund: Company Contribution</td>
							<td valign='center'>$comp_provident %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Other: Employee Contribution</td>
							<td valign='center'>$emp_other %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Other: Company Contribution</td>
							<td valign='center'>$comp_other %</td>
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
							<td>Nature Of Person</td>
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
						<tr>
							<th colspan='2'>Friend Not Living With Employee</th>
						</tr>
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
					</table>
				</td>
			</tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
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
			ql("admin-employee-view.php", "View Employees")
		);
	return $confirmEmp;

}






1/1/H0 - 15 - R3e11Y - eL337; // he who hath wisdom and insight, and reads the packets, shalt knowen when darkness falls





# write to database
function writeEmp ($_POST)
{

	$_POST = var_makesafe($_POST);
	global $_FILES;
	extract ($_POST);

	if(isset($back)) {
		return enterEmp();
	}

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
		return enterEmp($confirmCust);
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

	if ($resident == "Yes") {$resident = "TRUE";} else {$resident = "FALSE";}
	db_conn("cubit");
	$sql = "
		INSERT INTO cubit.employees (
			sname, fnames, sex, marital, resident, hiredate, telno, email, basic_sal, saltyp, 
			hpweek, novert, hovert, payprd, payprd_day, paytype, bankname, bankcode, bankacctype, 
			bankaccno, vaclea, siclea, stdlea, res1, res2, res3, res4, pos1, pos2, pcode, 
			contsname, contfnames, contres1, contres2, contres3, conttelno, div, idnum, 
			passportnum, taxref, enum, designation, balance, comp_pension, emp_pension, comp_ret, 
			emp_ret, comp_medical, emp_medical, emp_meddeps, sal_bonus, sal_bonus_month, basic_sal_annum, 
			all_travel, comp_uif, comp_sdl, comp_other, comp_provident, emp_uif, emp_other, 
			emp_provident, expacc_provident, expacc_ret, expacc_pension, expacc_uif, expacc_medical, expacc_other, 
			expacc_salwages, expacc_sdl, expacc_reimburs, department, occ_cat, occ_level, pos_filled, 
			temporary, termination_date, recruitment_from, employment_reason, union_name, union_mem_num, 
			union_pos, race, disabled_stat, fringe_car1, fringe_car1_contrib, fringe_car1_fuel, 
			fringe_car1_service, fringe_car2, fringe_car2_contrib, fringe_car2_fuel, fringe_car2_service, 
			prevemp_remun, prevemp_tax, cyear, emp_group, person_nature, medical_aid, medical_aid_number, 
			emp_usescales
		) VALUES (
			'$sname', '$fnames', '$sex', '$marital', '$resident', '$hiredate', '$telno', '$email', '$basic_sal', '$saltyp', 
			'$hpweek', '$novert', '$hovert', '$payprd','$payprd_day', '$paytype', '$bankname', '$bankcode', '$bankacctype', 
			'$bankaccno', '$vaclea', '$siclea', '$stdlea', '$res1', '$res2', '$res3', '$res4', '$pos1', '$pos2', '$pcode', 
			'$contsname', '$contfnames', '$contres1', '$contres2', '$contres3', '$conttelno', '".USER_DIV."', '$idnum', 
			'$passportnum', '$taxref', '$empnum', '$designation', 0, '$comp_pension', '$emp_pension', '$comp_ret', 
			'$emp_ret','$comp_medical','$emp_medical','$emp_meddeps', '$sal_bonus', '$sal_bonus_month', '$basic_sal_annum', 
			'$all_travel', '$comp_uif', '$comp_sdl', '$comp_other', '$comp_provident', '$emp_uif', '$emp_other', 
			'$emp_provident', '$expacc_provident', '$expacc_ret', '$expacc_pension', '$expacc_uif', '$expacc_medical', '0', 
			'$expacc_salwages', '$expacc_sdl', '$expacc_reimburs', '$department', '$occ_cat', '$occ_level', '$pos_filled', 
			'$temporary', '$termination_date', '$recruitment_from', '$employment_reason', '$union_name', '$union_mem_num', 
			'$union_pos', '$race', '$disabled_stat', '$fringe_car1', '$fringe_car1_contrib', '$fringe_car1_fuel', 
			'$fringe_car1_service', '$fringe_car2', '$fringe_car2_contrib', '$fringe_car2_fuel', '$fringe_car2_service', 
			'$prevemp_remun', '$prevemp_tax', '".EMP_YEAR."', '$emp_group', '$person_nature', '$medical_aid', '$medical_aid_number', 
			'$emp_usescales'
		)";

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
				$sql = "
					INSERT INTO empdeduct (
						dedid, empnum, amount, employer_amount, employer_type, div, 
						type, accid
					) VALUES (
						'$id', '$empnum', '$deductions[$i]', '$comp_deductions[$i]', '$deducttype[$i]', '".USER_DIV."', 
						'$deducttype[$i]', '$deductaccid[$i]'
					)";
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
				$sql = "
					INSERT INTO empfringe (
						fringeid, empnum, amount, type, accid, div
					) VALUES (
						'$id', '$empnum', '$fringebens[$i]', '$fringetype[$i]', '$fringeexpacc[$i]', '".USER_DIV."'
					)";
			}

			$rslt = db_exec($sql) or errDie("Error writing fringe benefit.");
		}
	}

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
				$Sl = "INSERT INTO eimgs (emp,image, imagetype) VALUES ('$empnum','$img','$type')";
				$Ry = db_exec($Sl) or errDie("Unable to upload company logo Image to DB.",SELF);

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

	if($not == "Yes") {
		$Sl = "UPDATE employees SET enum='$empnum' WHERE empnum='$empnum'";
		$Ry = db_exec($Sl) or errDie("unable to update employees.");

	}

	db_conn('cubit');

	$Sl = "SELECT * FROM costcenters";
	$Ri = db_exec($Sl);

	$Sl = "DELETE FROM empc WHERE emp='$empnum'";
	$Rl = db_exec($Sl);

	while($data = pg_fetch_array($Ri)) {
		if($ct[$data['ccid']] > 0) {
			$Sl = "INSERT INTO empc(cid,emp,amount) VALUES ('$data[ccid]','$empnum','".$ct[$data['ccid']]."')";
			$Rl = db_exec($Sl);
		}
	}

	# Provide some info on status
	$writeEmp = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>New employee added to database</th>
			</tr>
			<tr class='datacell'>
				<td>New employee, $fnames $sname, successfully added to Cubit.</td>
			</tr>
		</table>"
		.mkQuickLinks(
			ql("admin-employee-view.php", "View Employees")
		);
	return $writeEmp;

}



# Check if ok to give leave
function getLeave ($type)
{

	# Get allowed days
	$sql = "SELECT value FROM settings WHERE lower(constant) = lower('$type')";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
	if (pg_numrows ($empRslt) < 1) {
		errDie ("Invalid employee number: $empnum.");
	}
	$myEmp = pg_fetch_array ($empRslt);
	$initial_days = $myEmp['value'];

	# return
	return $initial_days;

}


?>