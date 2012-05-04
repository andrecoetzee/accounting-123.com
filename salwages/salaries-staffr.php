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

require ("../settings.php");
require ("../core-settings.php");
require("emp-functions.php");

## Decide
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "prd":
			$OUTPUT = slctPrd();
			break;
		case "process":
			$OUTPUT = process($_POST);
			break;
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "pack":
			$OUTPUT = package($_POST);
			break;
		default:
			$OUTPUT = slctEmployee ();
	}
}else{
	$OUTPUT = slctEmployee ();
}

$OUTPUT .= "<br>"
	.mkQuickLinks(
);

require ("../template.php");




# Select employee
function slctEmployee ($err = "")
{

	db_conn('cubit');

	#check what we have permission to
	$get_perm = "SELECT payroll_groups FROM users WHERE username = '$_SESSION[USER_NAME]' LIMIT 1";
	$run_perm = db_exec ($get_perm) or errDie ("Unable to get payroll groups permission information.");
	if (pg_numrows ($run_perm) > 0){
		$parr = pg_fetch_array ($run_perm);
		if (strlen ($parr['payroll_groups']) > 0){
			$pay_grps = explode (",",$parr['payroll_groups']);
			if (is_array ($pay_grps)){
				$egsearch = " AND (emp_group = '".implode ("' OR emp_group = '",$pay_grps)."')";
			}
		}else {
			$egsearch = "AND false";
		}
	}

	$Sl = "SELECT empnum,enum, sname, fnames FROM employees WHERE div = '".USER_DIV."' ORDER BY sname,fnames";
	$Ry = db_exec($Sl) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($Ry) < 1){
		return "<li class='err'>No Employees Found In Cubit.</li>";
	}

	$Sl = "SELECT empnum,enum, sname, fnames FROM employees WHERE div = '".USER_DIV."' $egsearch ORDER BY sname,fnames";
	$Ry = db_exec($Sl) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($Ry) < 1){
		return "<li class='err'>You Have Insufficient Permissions To Access The Cubit Payroll. You May Add The Permission <a href='../admin-usredit.php?username=$_SESSION[USER_NAME]'>Here</a></li>";
	}

	$employees = "<select size='1' name='empnum'>";
	while ($myEmp = pg_fetch_array ($Ry)) {
		$employees .= "<option value='$myEmp[empnum]'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</option>";
	}
	$employees .= "</select>";

	$slctEmployee = "
		<h3>Select employee to reverse</h3>
		$err
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='prd'>
			<tr>
				<th colspan='2'>Employee</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Employee</td>
				<td align='center'>$employees</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Select month</td>
				<td align=center>".empMonList("MON", DATE_MONTH)."</td>
				<td class='err'>This is the period for which you are processing the salary.</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Processing Date</td>
				<td nowrap>".mkDateSelect("date")."</td>
				<td class='err'>This is the date Cubit will use to enter transactions into the ledgers.</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Process &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $slctEmployee;

}




function slctPrd($err = "")
{

	extract($_REQUEST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($MON, "num", 1, 2, "Invalid month.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}


	db_conn('cubit');

	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	if($myEmp['payprd'] == "w") {
		$weeks = "<select name='week'>";

		$stdate = mktime(0, 0, 0, $MON, 1, DATE_YEAR);
		$endate = mktime(0, 0, 0, $MON, DATE_DAYS, DATE_YEAR);

		$i = 1;
		while ($stdate <= $endate) {
			if (date("w", $stdate) == 5) {
				if (isset($week) && $week == $i) {
					$sel = "selected='t'";
				} else {
					$sel = "";
				}
				$weeks .= "<option $sel value='$i'>Week $i (".date("j M", $stdate).")</option>";
				++$i;
			}

			/* next day */
			$stdate += 24 * 60 * 60;
		}

		/*<option value='1'>Week 1</option>
		<option value='2'>Week 2</option>
		<option value='3'>Week 3</option>
		<option value='4'>Week 4</option>
		<option value='5'>Week 5</option>*/;

		$weeks .= "</select>";

		$row = "
			<tr class='".bg_class()."'>
				<td>Period</td>
				<td>$weeks</td>
			</tr>";
	} elseif($myEmp['payprd']=="f") {
		$weeks = "<select name='week'>";

		$i = 1;
		/* find first friday of tax year */
		$stdate = mktime(0, 0, 0, 3, 1, getYearOfEmpMon(3));
		while (date("w", $stdate) != 5) {
			$stdate = mktime(0, 0, 0, 3, ++$i, getYearOfEmpMon(3));
		}

		// hack: go one week back so the +14 increases are easier
		$stdate -= 7 * 24 * 3600;

		/* end on the last day of the selected month */
		$endate = mktime(0, 0, 0, $MON + 1, 0, getYearOfEmpMon($MON));

		/* count weeks from start of tax year */
		$i = 1;
		$c = 0;
		while ($stdate <= $endate) {
			if (date("m", $stdate) == $MON && date("Y", $stdate) == getYearOfEmpMon($MON)) {
				$c += 2;
				$cd = ($c - 1)."-$c";

				$weekends .= "<input type='hidden' name='weekends[$i]' value='".date("j", $stdate)."' />";

				if (isset($week) && $week == $i) {
					$sel = "selected";
				} else {
					$sel = "";
				}

				$weeks .= "<option $sel value='$i'>Week $cd (".date("j M", $stdate).")</option>";
				++$i;
			}

			/* next day */
			$stdate += 24 * 60 * 60 * 14;
		}

		/*<option value='1'>Week 1</option>
		<option value='2'>Week 2</option>
		<option value='3'>Week 3</option>
		<option value='4'>Week 4</option>
		<option value='5'>Week 5</option>*/;

		$weeks .= "</select>";

		$row = "
			<tr class='".bg_class()."'>
				<td>Period</td>
				<td>$weeks</td>
			</tr>";
	} else if ($myEmp["payprd"] == "d") {
		$x = date("t", mktime(0, 0, 0, $MON, 1, getYearOfFinMon($MON)));
		$MONstr = getMonthNameS($MON);

		if (!isset($pday)) {
			$pday = $proc_day;
		}

		$days = "<select name='pday'>";
		for ($i = 1; $i <= $x; ++$i) {
			if (isset($pday) && $i == $pday) {
				$sel = "selected='t'";
			} else {
				$sel = "";
			}

			$days .= "<option $sel value='$i'>$i $MONstr</option>";
		}
		$days .= "</select>";

		$row = "
			<tr class='".bg_class()."'>
				<td>Day for Payment</td>
				<td>$days</td>
			</tr>";
	} else {
		if (isset($back) || !empty($err)) {
			return slctEmployee($err);
		} else {
			return process($_REQUEST);
		}
	}

	$OUT = "
		<h3>Reverse Employee Salary</h3>
		$err
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='process' />
			<input type='hidden' name='MON' value='$MON' />
			<input type='hidden' name='empnum' value='$empnum' />
			<input type='hidden' name='date_day' value='$date_day' />
			<input type='hidden' name='date_month' value='$date_month' />
			<input type='hidden' name='date_year' value='$date_year' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Select Period</th>
			</tr>
			$row
			<tr>
				<td colspan='2' align='right'>
					<input type='submit' name='btn_correction' value='&laquo; Correction' />
					<input type='submit' value='Next' />
				</td>
			</tr>
		</table>
		</form>";
	return $OUT;

}



function process ($_POST)
{

	extract($_POST);

	if (isset($btn_correction)) {
		return slctEmployee();
	}

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($MON, "num", 1, 2, "Invalid month.");

	global $PRDMON, $MONPRD;
	$yr = getYearOfEmpMon($MON);
	$curyr = getActiveFinYear();
	if ($yr > $curyr || ($yr == $curyr && $MON > $PRDMON[12])) {
		$v->addError("", "Cannot do transaction in future financial year. You need
			to close your year first before you can continue.");
	}

	if ($v->isError()) {
		return slctEmployee($v->genErrors());
		return $confirmCust;
	}

	# Get employee details
	global $global_empnum;
	$global_empnum = $empnum;

	db_conn('cubit');

	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	if ( $myEmp["flag"] == "2.5EMP" ) {
		$OUTPUT = "
			<h3>Process Employee Salary</h3>
			<li class='err'>
				Due to the changes from Cubit 2.5 to Cubit 2.6.1 you should first update your employee's
				salary/deduction/allowance information in the employee edit form.
				Click <a href='../admin-employee-edit.php?empnum=$empnum'>here</a> to do so.
			</li>";

		return $OUTPUT;
	}

	if ( ! empty($idnum) ) {
		$bd_year = substr($myEmp["idnum"], 0, 2);
		$bd_month = substr($myEmp["idnum"], 2, 2);
		$bd_day = substr($myEmp["idnum"], 4, 2);

		if ( ! checkdate($bd_month, $bd_day, $bd_year) ) {
			$OUTPUT = "
				<h3>Process Employee Salary</h3>
				<li class='err'>
					Selected employee does not have a valid id number and therefore his age cannot be
					calculated.<br>
					Please update this information in the employee <a href='../admin-employee-edit.php?empnum=$empnum'>edit</a> form.
				</li>";
			return $OUTPUT;
		}
	}

	if ( $myEmp["paytype"] == "EFT" && (empty($myEmp["bankname"]) || empty($myEmp["bankaccno"]))) {
		return "Employee banking information not entered.<br>
			Click <a href='../admin-employee-edit.php?empnum=$empnum'>here</a> employee banking information.";
	}

	$grossal = $myEmp["basic_sal"] + $myEmp["commission"] + $myEmp["bonus"];

	$yy = date("Y");
	$mm = $MON;
	$mm += 0;

	if ($myEmp['payprd'] == "m") {
		$Sl = "SELECT * FROM salpaid WHERE empnum='$empnum' AND month='$mm' AND cyear='".EMP_YEAR."'";
		$Ri = db_exec($Sl);

		$paid = pg_num_rows($Ri);

		$Sl = "SELECT * FROM salr WHERE empnum='$empnum' AND month='$mm' AND cyear='".EMP_YEAR."'";
		$Ri = db_exec($Sl);

		$upaid = pg_num_rows($Ri);

		$upaid += 0;

		$paid -= $upaid;
	} else {
		$yy = date("Y");
		$mm = $MON;
		$mm += 0;

		$Sl = "SELECT * FROM salpaid WHERE empnum='$empnum' AND month='$mm' AND week='$week' AND cyear='".EMP_YEAR."'";
		$Ri = db_exec($Sl);

		$paid = pg_num_rows($Ri);

		$Sl = "SELECT * FROM salr WHERE empnum='$empnum' AND month='$mm' AND week='$week' AND cyear='".EMP_YEAR."'";
		$Ri = db_exec($Sl);

		$upaid = pg_num_rows($Ri);

		$upaid += 0;

		$paid -= $upaid;
	}

	if (empty($paid)) {
		return slctPrd("<li class='err'>You have not processed a salary for that period.</li>");
	}

	/* calculate basic salary divisors and multipliers
	 * used for calculating deductions/allowances/etc. when the
	 * salary type and payment period differs in length
	 */
	switch ($myEmp["saltyp"]) {
		case "h":
			$divisor = 1;
			switch ($myEmp["payprd"]) {
				case "d":
					$multiplier = $myEmp["hpweek"] / 5;
					break;
				case "w":
					$multiplier = $myEmp["hpweek"];
					break;
				case "f":
					$multiplier = $myEmp["hpweek"] * 2;
					break;
				case "m":
					$multiplier = $myEmp["hpweek"] * 52 / 12;
					break;
			}
			break;

		case "m":
			$divisor = 1;
			switch ($myEmp["payprd"]) {
				case "d":
					$multiplier = 12 / (5 * 52);
					break;
				case "w":
					$multiplier = 12 / 52;
					break;
				case "f":
					$multiplier = 12 / 26;
					break;
				case "m":
					$multiplier = 1;
					break;
			}
			break;

		case "w":
			$divisor = 52 / 12;
			switch ($myEmp["payprd"]) {
				case "d":
					$multiplier = 1 / 5;
					break;
				case "w":
					$multiplier = 1;
					break;
				case "f":
					$multiplier = 2;
					break;
				case "m":
					$multiplier = 52 / 12;
					break;
			}
			break;

		case "f":
			$divisor = 26 / 12;
			switch ($myEmp["payprd"]) {
				case "d":
					$multiplier = 1 / 10;
					break;
				case "w":
					$multiplier = 1 / 2;
					break;
				case "f":
					$multiplier = 1;
					break;
				case "m":
					$multiplier = 26 / 12;
					break;
			}
			break;
	}

	/* BEGIN: retrieve/populate amounts to reverse */
	switch ($myEmp["payprd"]) {
		case "m":
			$spw = "true";
			break;
		case "d":
			$spw = "week='$pday'";
			break;
		case "f":
		case "w":
			$spw = "week='$week'";
			break;
	}

	/* previous salary entries in salpaid/salr */
	$vals = array(
		"paye",
		"hours",
		"salrate",
		"comm",
		"bonus",
		"novert",
		"hovert"
	);

	$prevsal = array();

	/* FP: CALCULATE PREVIOUS */
	/* previous salary entries in emp_(inc|com|ded|frin) */
	foreach ($vals as $vn) {
		/* process */
		$sql = "SELECT sum($vn) FROM salpaid WHERE empnum='$empnum' AND ($spw) AND month='$MON' and cyear='".EMP_YEAR."'";
		$rslt = db_exec($sql) or errDie("Unable to get paye");

		$prevsal[$vn] = pg_fetch_result($rslt, 0, 0);

		/* reverse */
		$sql = "SELECT sum($vn) FROM salr WHERE empnum='$empnum' AND ($spw) AND month='$MON' and cyear='".EMP_YEAR."'";
		$rslt = db_exec($sql) or errDie("Unable to get paye");

		$prevsal[$vn] -= pg_fetch_result($rslt, 0, 0);
		vsprint($prevsal[$vn]);
	}


	//do we want to include overtime in the reversal? ... yes plz ...
	$h1 = $prevsal['novert'];
	$h2 = $prevsal['hovert'];

	//FP use this to go though tables and get info to reverse (bonus etc)
	$vals = array(
		"emp_ded" => array(
			"DEDP" => "emp_pension",
			"DEDV" => "emp_provident",
			"UIFE" => "emp_uif",
			"DEDR" => "emp_ret",
			"DEDA" => "myEmp[fringe_car1_contrib]",
			"DEDB" => "myEmp[fringe_car2_contrib]",
			"DEDM" => "emp_medical",
			"DEDO" => "emp_other"
		)
//		, "emp_inc" => array(
//			"INCAB" => "bonus"
//		)
		, "emp_com" => array(
			"COMP" => "comp_pension"
			, "COMV" => "comp_provident"
			, "UIFC" => "comp_uif"
			, "COMR" => "comp_ret"
			, "COMM" => "comp_medical"
			, "COMO" => "comp_other"
			, "SDL" => "sdl"
		)
		, "emp_frin" => array(
		)
	);

	foreach ($vals as $table => $pd) {
		foreach ($pd as $code => $vn) {
			/* process */
			$sql = "
				SELECT sum(amount) 
				FROM $table 
				WHERE emp='$empnum' AND type='$code' AND ($spw) AND period='$MON' AND year='".EMP_YEAR."' LIMIT 1";
			$rslt = db_exec($sql) or errDie("Unable to get paye");
			if(strlen(pg_fetch_result($rslt, 0, 0)) > 0)
				$prevsal[$vn] = pg_fetch_result($rslt, 0, 0);
			else 
				$prevsal[$vn] = "0.00";
		}
	}

	/* END: retrieve/populate amounts to reverse */

	# fringe benefits
	$fringes = "";
	$i = 0;
	$sql = "SELECT * FROM fringebens WHERE div = '".USER_DIV."' ORDER BY fringeben";
	$rslt = db_exec ($sql) or errDie ("Unable to select allowances from database.");
	if ( pg_num_rows ($rslt) < 1 ) {
		$fringes = "
			<tr>
				<td class='".bg_class()."' colspan='2' align='center'>None found in database.</td>
			</tr>\n";
	} else {
		while ($myFringe = pg_fetch_array ($rslt)) {

			# check if employee has allowance
			$sql = "SELECT * FROM empfringe WHERE fringeid='$myFringe[id]' AND empnum='$myEmp[empnum]' AND div = '".USER_DIV."'";
			$empRslt = db_exec ($sql) or errDie ("Unable to retrieve fringe benefit info from database.");
			if (pg_numrows ($empRslt) > 0) {
				$empFringe = pg_fetch_array ($empRslt);
				
				$sql = "SELECT sum(amount) FROM cubit.emp_frin WHERE emp='$empnum' AND type='$myFringe[id]' AND ($spw) AND period='$MON' AND year='".EMP_YEAR."'";
				$amtRslt = db_exec($sql);
				
				$empFringe["amount"] = pg_fetch_result($amtRslt, 0, 0);

//				if ( substr($empFringe["type"], 0, 4) == "Perc" ) {
//					$empFringe["amount"] = sprint($myEmp["basic_sal"] * ($empFringe["amount"]/100) / $divisor);
//				} else {
//					$empFringe['amount'] = sprint($empFringe['amount'] / $divisor);
//				}

				$grossal += $empFringe["amount"];

				$tmp_fringeaccs = $empFringe["accid"];
				$tmp_fringebens = $empFringe["amount"];
			} else {
				$tmp_fringeaccs = "0";
				$tmp_fringebens = "0.00";
			}

			$fringes .= "
				<input type='hidden' name='fringeid[]' value='$myFringe[id]'>
				<input type='hidden' name='fringename[]' value='$myFringe[fringeben]'>
				<input type='hidden' name='fringeaccs[]' value='$tmp_fringeaccs'>
				<tr class='".bg_class()."'>
					<td>$myFringe[fringeben]</td>
					<td align='center'>
						".CUR." $tmp_fringebens
						<input type='hidden' size=10 name='fringebens[]' value='$tmp_fringebens'>
					</td>
				</tr>";

			$i++;
		}
	}

	# get allowances
	$allowances = "";
	$i = 0;
	$sql = "SELECT * FROM allowances WHERE div = '".USER_DIV."' ORDER BY allowance";
	$allowRslt = db_exec ($sql) or errDie ("Unable to select allowances from database.");
	if (pg_numrows($allowRslt) < 1) {
		$allowances = "<tr><td class='".bg_class()."' colspan='2' align='center'>None found in database.</td></tr>\n";
	} else {
		while ($myAllow = pg_fetch_array ($allowRslt)) {

			# check if employee has allowance
			$sql = "SELECT * FROM empallow WHERE allowid='$myAllow[id]' AND empnum='$myEmp[empnum]' AND div = '".USER_DIV."'";
			$empAllowRslt = db_exec ($sql) or errDie ("Unable to select allowance info from database.");
			if (pg_numrows ($empAllowRslt) > 0) {
				$myEmpAllow = pg_fetch_array ($empAllowRslt);
				
				$sql = "SELECT sum(amount) FROM cubit.emp_inc WHERE emp='$empnum' AND type='$myAllow[id]' AND ex != 'SUBS' AND ($spw) AND period='$MON' AND year='".EMP_YEAR."'";
				$amtRslt = db_exec($sql);
				
				$myEmpAllow["amount"] = pg_fetch_result($amtRslt, 0, 0);

//				if ( substr($myEmpAllow["type"], 0, 4) == "Perc" ) {
//					$myEmpAllow["amount"] = sprint($myEmp["basic_sal"] * ($myEmpAllow["amount"]/100) / $divisor);
//				} else {
//					$myEmpAllow['amount'] = sprint($myEmpAllow['amount'] / $divisor);
//				}

				$grossal += $myEmpAllow["amount"];

				$tmp_allowaccs = $myEmpAllow["accid"];
				$tmp_allowances = $myEmpAllow["amount"];
			} else {
				$tmp_allowaccs = $myAllow["accid"];
				$tmp_allowances = "0.00";
			}

			$allowances .= "
				<input type='hidden' size='10' name='allowid[]' value='$myAllow[id]'>
				<input type='hidden' size='30' name='allowname[]' value='$myAllow[allowance]'>
				<input type='hidden' size='10' name='allowtax[]' value='$myAllow[add]'>
				<input type='hidden' name='allowaccs[]' value='$tmp_allowaccs'>
				<tr class='".bg_class()."'>
					<td>$myAllow[allowance]</td>
					<td align='center'>".CUR." $tmp_allowances<input type='hidden' size='10' name='allowances[]' value='$tmp_allowances'></td>
				</tr>";

			$i++;
		}
	}

	$subsistence = "";
	$subslst = new dbSelect("subsistence", "cubit", array(
		"where" => "div='".USER_DIV."'",
		"order" => "name"
	));
	$subslst->run();
	$subs_int = false;
	if ($subslst->num_rows() > 0) {
		$i = 0;
		$subsistence .= "
			<tr>
				<td colspan='10'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Name</th>
							<th>Amount</th>
							<th>Days</th>
						</tr>";

		$empsubs = new dbSelect("emp_subsistence", "cubit");

		while ($subs = $subslst->fetch_array()) {
			$sid = $subs["id"];

			$empsubs->setOpt(array(
				"where" => "empnum='$empnum' AND subid='$sid'"
			));
			$empsubs->run();

			if ($empsubs->num_rows() <= 0) {
				$si["amount"] = "0.00";
				$si["days"] = "0";
				$si["accid"] = $subs["accid"];
			} else {
				$si = $empsubs->fetch_array();
			}

			if ($subs["in_republic"] != "yes") {
				$subs_int = true;
			}

			$subsistence .= "
				<input type='hidden' name='subsname[$sid]' value='$subs[name]'>
				<input type='hidden' name='subsacc[$sid]' value='$si[accid]'>
				<input type='hidden' name='subsrep[$sid]' value='$subs[in_republic]'>
				<input type='hidden' name='subsmeal[$sid]' value='$subs[meals]'>
				<tr bgcolor='".bgcolor($i)."'>
					<td>$subs[name]</td>
					<td>".CUR." $si[amount]<input type='hidden' name='subsamt[$sid]' value='$si[amount]'></td>
					<td>$si[days]<input type='hidden' name='subsdays[$sid]' value='$si[days]'></td>
				</tr>";
		}

		if ($subs_int) {
			$subsistence .= "
				<input type='hidden' name='subs_exch' value='1'>
				<tr bgcolor='".bgcolor($i)."'>
					<td colspan='3'><li class='err'>Please calculate the rand amount prior to completing the amount above.</li></td
				</tr>";

// 				<tr bgcolor='".bgcolor($i)."'>
// 					<td colspan='2'>Exchange (ZAR-USD):</td>
// 					<td>".xrate_get("USD")."<input type='hidden' name='subs_exch' value='".xrate_get("USD")."'></td>
// 				</tr>";
		}

		$subsistence .= "
			</table>
			</td></tr>";
	}

	# Deductions
	$deductions = "";
	$i = 0;
	$sql = "SELECT * FROM salded WHERE div = '".USER_DIV."' ORDER BY deduction";
	$deductRslt = db_exec ($sql) or errDie ("Unable to select deductions from database.");
	if (pg_numrows ($deductRslt) < 1) {
		$deductions = "
			<tr>
				<td class='".bg_class()."' colspan='2' align='center'>None found in database.</td>
			</tr>\n";
	} else {
		while ($myDeduct = pg_fetch_array ($deductRslt)) {

			# check if employee has deduction
			$sql = "SELECT * FROM empdeduct WHERE dedid='$myDeduct[id]' AND empnum='$myEmp[empnum]' AND div = '".USER_DIV."'";
			$empDeductRslt = db_exec ($sql) or errDie ("Unable to select Deduction info from database.");

			if (pg_numrows ($empDeductRslt) > 0) {
				$myEmpDeduct = pg_fetch_array ($empDeductRslt);
				if ( $myEmpDeduct["grosdeduct"] == "y" ) {
					$deductions_msg = "(Deducted from Gross Salary)";
					$sal_calcfrom = $grossal;
				} else {
					$deductions_msg = "";
					$sal_calcfrom = $myEmp['basic_sal'];
				}
				
				$sql = "SELECT sum(amount) FROM cubit.emp_ded WHERE emp='$empnum' AND type='$myDeduct[id]' AND ($spw) AND period='$MON' AND year='".EMP_YEAR."'";
				$amtRslt = db_exec($sql);
				
				$myEmpDeduct['amount'] = pg_fetch_result($amtRslt, 0, 0);
					
				//if ($myEmpDeduct['type'] == "Amount") {
				//	$myEmpDeduct['amount'] = sprint($myEmpDeduct['amount'] / $divisor);
				//} else {
				//	$myEmpDeduct['amount'] = sprint($sal_calcfrom*$myEmpDeduct['amount']/100 / $divisor);
				//}

				// calculate employer contribution to deduction
				$sql = "SELECT sum(amount) FROM cubit.emp_com WHERE emp='$empnum' AND type='$myDeduct[id]' AND ($spw) AND period='$MON' AND year='".EMP_YEAR."'";
				$amtRslt = db_exec($sql);
				
				$myEmpDeduct["employer_amount"] = pg_fetch_result($amtRslt, 0, 0);
				
				//if ( $myEmpDeduct["employer_type"] == "Amount" ) {
				//	$myEmpDeduct["employer_amount"] = sprint($myEmpDeduct["employer_amount"] / $divisor);
				//} else {
				//	$myEmpDeduct["employer_amount"] = sprint($myEmpDeduct["amount"] * $myEmpDeduct["employer_amount"] / 100 / $divisor);
				//}

				$tmp_deductions = $myEmpDeduct["amount"];
				$tmp_dedaccs = $myEmpDeduct["accid"];
				$tmp_emp_ded = $myEmpDeduct["employer_amount"];
				$tmp_grosdeduct = $myEmpDeduct["grosdeduct"];
			} else {
				#employee may have R0.00 entered into deduction fields ....
				#in which case no db entries will exist ... manually check these vars here ...
				$sql = "SELECT sum(amount) FROM cubit.emp_ded WHERE emp='$empnum' AND type='$myDeduct[id]' AND ($spw) AND period='$MON' AND year='".EMP_YEAR."'";
				$runsql = db_exec ($sql) or errDie ("Unable to get employee deduction information.");
				if (pg_numrows ($runsql) > 0){
					$myEmpDeduct['amount'] = pg_fetch_result($runsql, 0, 0);

					$sql2 = "SELECT * FROM cubit.salded WHERE id = '$myDeduct[id]'";
					$run_sql2 = db_exec ($sql2) or errDie ("Unable to get employee deductions information");
					if (pg_numrows($run_sql2) > 0){
						$darr = pg_fetch_array ($run_sql2);
						$tmp_dedaccs = $darr["accid"];
					}else {
						$tmp_dedaccs = $myEmpDeduct["accid"];
					}

					$sql = "SELECT sum(amount) FROM cubit.emp_com WHERE emp='$empnum' AND type='$myDeduct[id]' AND ($spw) AND period='$MON' AND year='".EMP_YEAR."'";
					$amtRslt = db_exec($sql);
				
					$myEmpDeduct["employer_amount"] = pg_fetch_result($amtRslt, 0, 0);
					$tmp_deductions = $myEmpDeduct["amount"];
					$tmp_emp_ded = $myEmpDeduct["employer_amount"];
					$tmp_grosdeduct = $myEmpDeduct["grosdeduct"];

				}else {
					$tmp_deductions = "0.00";
					$tmp_emp_ded = "0.00";
					$tmp_dedaccs = $myDeduct["accid"] != 0 ? $myDeduct["accid"] : $myDeduct["expaccid"];
					$tmp_grosdeduct = "n";
					$deductions_msg = "";
				}
			}

			$deductions .= "
				<input type='hidden' size='10' name='employer_deductions[]' value='$tmp_emp_ded'>
				<input type='hidden' size='10' name='deducttax[]' value='$myDeduct[add]'>
				<input type='hidden' name='grosdeduct[]' value='$tmp_grosdeduct'>
				<tr class='".bg_class()."'>
					<td>$myDeduct[deduction] $deductions_msg</td>
					<td align='center'>
						".CUR." $tmp_deductions
						<input type='hidden' size='10' name='deductid[]' value='$myDeduct[id]'>
						<input type='hidden' size='30' name='deductname[]' value='$myDeduct[deduction]'>
						<input type='hidden' size='10' name='deductions[]' value='$tmp_deductions'>
						<input type='hidden' name='dedaccs[]' value='$tmp_dedaccs'>
					</td>
				</tr>";
			$i++;
		}
	}
	$deductions .= "";

	/* get loan installment for applicable month */
	db_conn("cubit");

	$sql = "SELECT * FROM emp_loaninstallments WHERE empnum='$empnum' AND fmonth='$mm' AND fyear='".EMP_YEAR."' LIMIT 1";
	$rslt = db_exec($sql) or errDie("Error reading possible loan installment information.");

	if (pg_num_rows($rslt) > 0) {
		$loandata = pg_fetch_array($rslt);
		$myEmp["loaninstall"] = $loandata["installment"];
		$tm_loaninstall = $loandata["installment"];
		$fringe_loan = $loandata["fringe"];
		$loanint = $loandata["interest"];
		$loaninstall_date = $loandata["fdate"];
		$loaninstall_prd = $loandata["fperiod"];
	} else {
		$myEmp['loaninstall'] = "0.00";
		$tm_loaninstall = "0.00";
		$fringe_loan = "0.00";
		$loanint = "0.00";
		$loaninstall_date = "0000-00-00";
		$loaninstall_prd = "0";
	}

	$salarr = array(
		"m"	=> "Per Month",
		"w"	=> "Per Week",
		"f"	=> "Fortnightly",
		"h"	=> "Per Hour"
	);

	$salnarr = array(
		"d"	=> "Day(s)",
		"h"	=> "Hour(s)"
	);

	$saltype = $salarr[$myEmp['saltyp']];
	
	$multi = round($prevsal["hours"]);

	if ($myEmp['saltyp'] == 'd' || $myEmp['saltyp'] == 'h') {
		$salntype = $salnarr[$myEmp['saltyp']];
		$multi_show = "x <input type='hidden' size='3' name='multi' value='$multi'>$multi $salntype";
	} else {
		$multi_show = "<input type='hidden' name='multi' value='$multi'>";
		$saltype = "";
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname ASC";
	$Ry = db_exec($Sl) or errDie("Unable to get bank account.");

	if (pg_numrows($Ry) < 1) {
		return "<li class='err'> There are no bank accounts found in Cubit.</li>
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	$banks = "<select name='accid'>";
	while($acc = pg_fetch_array($Ry)){
		$banks .= "<option value='$acc[bankid]'>$acc[accname] ($acc[acctype])</option>";
	}
	$banks .= "</select>";

	$myEmp['loaninstall'] += 0;

	if ($myEmp['paytype'] == "Cash") {
		$paydetails = "
			<tr class='".bg_class()."'>
				<td colspan='2'>Employee paid cash</td>
			</tr>
			<input type='hidden' name='accid' value='0'>";
	} else if ($myEmp['paytype'] == "Ledger Account") {
		db_conn('core');
		$Sl = "SELECT accid,accname FROM accounts ORDER BY accname";
		$Ri = db_exec($Sl);

		$accounts = "
			<select name='account'>
				<option value='#'>Select Account</option>";
		while ($ad = pg_fetch_array($Ri)) {
			if (isset($account) && $account == $ad['accid']) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$accounts .= "<option value='$ad[accid]'>$ad[accname]</option>";
		}
		$accounts .= "</select>";

		$paydetails = "
			<tr class='".bg_class()."'>
				<td>Ledger Account for payment</td>
				<td>$accounts</td>
			</tr>
			<input type='hidden' name='accid' value='0'>";
	} else {
		$paydetails = "
			<tr class='".bg_class()."'>
				<td>Bank Account</td>
				<td valign='center'>$banks</td>
			</tr>";
	}

//	$h1 = "";
//	$h2 = "";


	$db = Array(
		"comp_pension" 		=> $myEmp["comp_pension"],
		"emp_pension"		=> $myEmp["emp_pension"],
		"comp_provident"	=> $myEmp["comp_provident"],
		"emp_provident"		=> $myEmp["emp_provident"],
		"comp_uif" 		=> $myEmp["comp_uif"],
		"emp_uif"		=> $myEmp["emp_uif"],
		"comp_other" 		=> $myEmp["comp_other"],
		"emp_other"		=> $myEmp["emp_other"]
	);

	if (isset($basic_sal)) {
		$myEmp['basic_sal'] = $basic_sal;
		$myEmp['all_travel'] = $all_travel;
		$myEmp['bonus'] = $bonus;
		$myEmp['commission'] = $commission;
		$myEmp['abonus'] = $abonus;
		$myEmp['loaninstall'] = $loaninstall;
		$myEmp['comp_pension'] = $comp_pension;
		$myEmp['emp_pension'] = $emp_pension;
		$myEmp["comp_provident"] = $comp_provident;
		$myEmp["emp_provident"] = $emp_provident;
		//$myEmp["comp_uif"] = $comp_uif;
		//$myEmp["emp_uif"] = $emp_uif;
		$myEmp["comp_other"] = $comp_other;
		$myEmp["emp_other"] = $emp_other;
		$myEmp['comp_medical'] = $comp_medical;
		$myEmp['emp_medical'] = $emp_medical;
		$myEmp['comp_ret'] = $comp_ret;
		$myEmp['emp_ret'] = $emp_ret;
//		$h1 = $novert;
//		$h2 = $hovert;
		
	} else {
		//$day = date("d");
		//$mon = date("m");
		//$year = date("Y");

		if ( $myEmp["payprd"] == "w" || $myEmp["payprd"] == "f" ) {
			$tmpmon = date("j");
			$daycount = date("t");
			$dayweek = date("D");

			if (strtolower($dayweek) == $myEmp["payprd_day"] && ($date_day + 7) > $daycount) {
				$process_comp_deductions = true;
			} else {
				$process_comp_deductions = false;
			}
		} else {
			$process_comp_deductions = true;
		}

		$effective_basicsal = ($myEmp["basic_sal"] * $multiplier);

		/* we only changing basic sal for non hourly employees,
			because for hourly employees we change the hours ($mutli)  */
		if ($myEmp["saltyp"] != "h") {
			$myEmp["basic_sal"] *= $multiplier;
		}

		$myEmp["emp_pension"] = sprint($effective_basicsal * ($myEmp["emp_pension"]/100));
		$myEmp["comp_pension"] = sprint($effective_basicsal * ($myEmp["comp_pension"]/100));
		$myEmp["emp_provident"] = sprint($effective_basicsal * ($myEmp["emp_provident"]/100));
		$myEmp["comp_provident"] = sprint($effective_basicsal * ($myEmp["comp_provident"]/100));
		$myEmp["emp_medical"] = sprint($myEmp["emp_medical"] / $divisor);
		$myEmp["comp_medical"] = sprint($myEmp["comp_medical"] / $divisor);
		$myEmp["emp_ret"] = sprint($myEmp["emp_ret"] / $divisor);
		$myEmp["comp_ret"] = sprint($myEmp["comp_ret"] / $divisor);
		$myEmp["loaninstall"] = sprint($myEmp["loaninstall"] / $divisor);
		$myEmp["all_travel"] = sprint($myEmp["all_travel"] / $divisor);

		if(!isset($salyr))
			$salyr = "";

		explodeDate($myEmp["loandate"], $loana_year, $loana_month, $loana_day);
		if ($loana_year > $salyr || ($loana_year == $salyr && $loana_month > $MON)) {
			$myEmp["loanint"] = 0;
			$myEmp["loaninstall"] = 0;
		}
	}

	/*	db_conn('cubit');
	$sql = "SELECT value FROM settings WHERE constant='UIF_MAX'";
	$percrslt = db_exec($sql);
	$perc = pg_fetch_array($percrslt);
	$uifmax = $perc['value'];

	if ( $myEmp["emp_uif"] > $uifmax ) {
	$myEmp["emp_uif"] = $uifmax;
	}
	if ( $myEmp["comp_uif"] > $uifmax ) {
	$myEmp["comp_uif"] = $uifmax;
	}
	*/

	$rt = "<tr><th colspan='2'>Reimbursements</th></tr>";

	$Sl = "SELECT * FROM cubit.rbs ORDER BY name";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$i = 0;

	if(pg_num_rows($Ri) > 0) {

		while($td = pg_fetch_array($Ri)) {

			if(!isset($rbsa[$td['id']])) {
				$rbsa[$td['id']] = "";
			}

			$rt .= "
				<tr class='".bg_class()."'>
					<td><input type='hidden' name='rbs[$td[id]]' value='$td[id]'>$td[name]</td>
					<td>".CUR." ".$rbsa[$td['id']]."<input type='hidden' size='10' name='rbsa[$td[id]]' value='".$rbsa[$td['id']]."' class=right></td>
				</tr>";

			$i++;
		}
	} else {
		$rt .= "
			<tr class='".bg_class()."'>
				<td colspan='2' align='center'>There are no reimbursements</td>
			</tr>";
	}

	if ( ! isset($annual) && $myEmp["sal_bonus_month"] == $MON ) {
		$annual = $myEmp["sal_bonus"];
	} else if ( ! isset($annual) ) {
		$annual = 0;
	}

	if ( $myEmp["payprd"] == "m" || $myEmp["payprd"] == "d" ) {
		// count the amount of weekdays in this month
		$workdays = 0;
		for ( $i = 1; $i <= date("t", mktime(0, 0, 0, $MON, 1, date("Y"))); ++$i ) {
			$wd = date("w", mktime(0, 0, 0, $MON, $i, date("Y")));

			if ( $wd != 0 && $wd != 6 ) {
				++$workdays;
			}
		}

		// hours per day calculation
		$hpd = $myEmp["hpweek"] / 5;

		if ( ! isset($wh_total) ) $wh_total = $workdays * $hpd;
		if ( ! isset($wh_actual) ) $wh_actual = $wh_total;
	}

	if ( $myEmp["payprd"] == "w" ) {
		if ( ! isset($wh_total) ) $wh_total = $myEmp["hpweek"];
		if ( ! isset($wh_actual) ) $wh_actual = $wh_total;
	}

	if ( $myEmp["payprd"] == "f" ) {
		if ( ! isset($wh_total) ) $wh_total = $myEmp["hpweek"] * 2;
		if ( ! isset($wh_actual) ) $wh_actual = $wh_total;
	}

	$js_workhours = "
		<script>
			sf = document.getElementById('salfrm');

			f_sal		= sf.elements['basic_sal'];
			f_whtot		= sf.elements['wh_total'];
			f_whact		= sf.elements['wh_actual'];
			f_cpension	= sf.elements['comp_pension'];
			f_epension 	= sf.elements['emp_pension'];
			f_cprov		= sf.elements['comp_provident'];
			f_eprov		= sf.elements['emp_provident'];
			//f_cuif	= sf.elements['comp_uif'];
			//f_euif	= sf.elements['emp_uif'];
			f_cother	= sf.elements['comp_other'];
			f_eother	= sf.elements['emp_other'];

			db_cpension	= ".$db["comp_pension"].";
			db_epension	= ".$db["emp_pension"].";
			db_cprov	= ".$db["comp_provident"].";
			db_eprov	= ".$db["emp_provident"].";
			//db_cuif	= ".$db["comp_uif"].";
			//db_euif	= ".$db["emp_uif"].";
			db_cother	= ".$db["comp_other"].";
			db_eother	= ".$db["emp_other"].";

			val_sal 		= -1;

			// changing the workhours
			function workhours() {
				if ( val_sal < 0 ) val_sal = parseFloat(f_sal.value);

				val_whtot	= parseFloat(f_whtot.value);
				val_whact	= parseFloat(f_whact.value);

				if ( val_whtot >= val_whact ) {
					p = val_whact / val_whtot;

					// calculate the new basic salary
					x = val_sal * p;
					x = x.toFixed(2);
					f_sal.value = x;

					// calculate the new values
					val_cpension 		= x * db_cpension / 100;
					val_epension 		= x * db_epension / 100;
					val_cprov		= x * db_cprov / 100;
					val_eprov		= x * db_eprov / 100;
					//val_cuif		= x * db_cuif / 100;
					//val_euif		= x * db_euif / 100;
					val_cother		= x * db_cother / 100;
					val_eother		= x * db_eother / 100;

					val_cpension 		= val_cpension.toFixed(2);
					val_epension 		= val_epension.toFixed(2);
					val_cprov 		= val_cprov.toFixed(2);
					val_eprov		= val_eprov.toFixed(2);
					//val_cuif		= val_cuif.toFixed(2);
					//val_euif		= val_euif.toFixed(2);
					val_cother		= val_cother.toFixed(2);
					val_eother		= val_eother.toFixed(2);

					f_cpension.value	= val_cpension;
					f_epension.value 	= val_epension;
					f_cprov.value 		= val_cprov;
					f_eprov.value		= val_eprov;
					//f_cuif.value		= val_cuif;
					//f_euif.value		= val_euif;
					f_cother.value		= val_cother;
					f_eother.value		= val_eother;
				}
			}

			function changedfield() {
				val_whtot	= parseFloat(f_whtot.value);
				val_whact	= parseFloat(f_whact.value);

				p = val_whtot / val_whact;

				val_sal = parseFloat(f_sal.value) * p;
				val_sal = val_sal.toFixed(2);
			}
		</script>";

	$process = "
		<h3>Reverse Salary for $myEmp[sname], $myEmp[fnames]</h3>
		<form action='".SELF."' method='POST' id='salfrm'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='empnum' value='$empnum'>
			<input type='hidden' name='MON' value='$MON'>
			<input type='hidden' name='date_day' value='$date_day' />
			<input type='hidden' name='date_month' value='$date_month' />
			<input type='hidden' name='date_year' value='$date_year' />
			<input type='hidden' name='day' value='$date_day' />
			<input type='hidden' name='mon' value='$date_month' />
			<input type='hidden' name='year' value='$date_year' />
			<input type='hidden' name='saltyp' value='$myEmp[saltyp]'>
			<input type='hidden' name='process_comp_deductions' value='$process_comp_deductions'>
			<input type='hidden' name='divisor' value='$divisor'>";

	if ($myEmp["saltyp"] == "h") {
		$process .= "<li class='err'>Please remember to enter the amount of hours you wish to reverse the salary for.</li>";
	}

	vsprint($myEmp["basic_sal"]);

	if (!isset($week)) {
		$week = "0";
	}

	if (!isset($pday)) {
		$pday = "0";
	}

	$weekpday = "
		<input type='hidden' name='week' value='$week'/>
		<input type='hidden' name='pday' value='$pday' />";

	$process .= "
		<tr>
			<th colspan='2'>Salary Details</th>
		</tr>
		<tr class='".bg_class()."'>
			<td nowrap>Basic salary</td>
			<td nowrap>".CUR." <input type='hidden' size='10' name='basic_sal' value='$prevsal[salrate]' class='right' onChange='changedfield();'> $prevsal[salrate] $saltype $multi_show</td>
		</tr>";

	if ($myEmp["payprd"] == "d") {
		$process .= "
			<input type='hidden' name='wh_total' value='1'>
			<input type='hidden' name='wh_actual' value='1'>";
	} else {
		$process .= "
			<tr class='".bg_class()."'>
				<td nowrap>Total Work Hours:</td>
				<td><input type='hidden' size='10' name='wh_total' value='$wh_total' class='right' onChange='workhours();'>$wh_total</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Actual Hours Worked:</td>
				<td><input type='hidden' size='10' name='wh_actual' value='$wh_actual' class='right' onChange='workhours();'>$wh_actual</td>
			</tr>";
	}

	$process .= "
			<tr class='".bg_class()."'>
				<td nowrap>Normal Overtime</td>
				<td><input type='hidden' size='5' name='novert' value='$h1' class='right'>$h1 Hrs</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Public Holiday Overtime</td>
				<td><input type='hidden' size='5' name='hovert' value='$h2' class='right'>$h2 Hrs</td>
			</tr>
			<tr class='".bg_class()."'>
				<!--<td>Special Bonus/Additional Salary</td>-->
				<td>Annual Bonus</td>
				<td>".CUR." <input type='hidden' name='bonus' value='0' class='right'><input type='hidden' name='abonus' value='$prevsal[bonus]' class='right'>$prevsal[bonus]</td>
				<!--<td rowspan='2' class='err'>An amount entered here (Special Bonus/Additional
					Salary) will be treated as a recurring bonus/payment per pay period for PAYE
					purposes, the amount will not be treated as an annual payment. If the
					amount paid as a bonus is a once off/annual payment please use the
					Bonus(Annual Payments) option. In other cases PAYE has to be manually
					adjusted <u>per directive</u> from SARS when processing salary.
				</td>-->
			</tr>
			<input type='hidden' name='annual' value='0' />
			<!--<tr class='".bg_class()."'>
				<td>Bonus(Annual/Once Off Payments)</td>
				<td nowrap>".CUR." <input type='text' size='10' name='annual' value='$annual' class='right'></td>
			</tr>-->
			<tr class='".bg_class()."'>
				<td nowrap>Commission</td>
				<td>".CUR." <input type='hidden' size='10' name='commission' value='$prevsal[comm]' class='right'>$prevsal[comm]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Low or interest-free loan</td>
				<td>".CUR." <input type='hidden' size=10 name=loaninstall value='$tm_loaninstall' class=right>$tm_loaninstall</td>
				<input type='hidden' name='fringe_loan' value='$fringe_loan'>
				<input type='hidden' name='loanint' value='$loanint'>
				<input type='hidden' name='loaninstall_date' value='$loaninstall_date'>
				<input type='hidden' name='loaninstall_prd' value='$loaninstall_prd'>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Travel Allowance</td>
				<td>R <input type='hidden' size='10' name='all_travel' value='$myEmp[all_travel]' class='right'>$myEmp[all_travel]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Pension: Company Contribution</td>
				<td>".CUR." <input type='hidden' size='10' name='comp_pension' value='$prevsal[comp_pension]' class='right'>$prevsal[comp_pension]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Pension: Employee Deduction</td>
				<td>".CUR." <input type='hidden' size='10' name='emp_pension' value='$prevsal[emp_pension]' class='right'>$prevsal[emp_pension]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Provident: Company Contribution</td>
				<td>".CUR." <input type='hidden' size='10' name='comp_provident' value='$prevsal[comp_provident]' class='right'>$prevsal[comp_provident]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Provident: Employee Deduction</td>
				<td>".CUR." <input type='hidden' size='10' name='emp_provident' value='$prevsal[emp_provident]' class='right'>$prevsal[emp_provident]</td>
			</tr>
			<!--
			<tr class='".bg_class()."'>
				<td nowrap>UIF: Company Contribution</td>
				<td>".CUR." <input type='hidden' size='10' name='comp_uif' value='$prevsal[comp_uif]' class='right'>$prevsal[comp_uif]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>UIF: Employee Deduction</td>
				<td>".CUR." <input type='hidden' size='10' name='emp_uif' value='$prevsal[emp_uif]' class='right'>$prevsal[comp_uif]</td>
			</tr>
			//-->
			<tr class='".bg_class()."'>
				<td nowrap>Retirement Annuity: Company Contribution</td>
				<td>".CUR." <input type='hidden' size='10' name='comp_ret' value='$prevsal[comp_ret]' class='right'>$prevsal[comp_ret]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Retirement Annuity: Employee Deduction</td>
				<td>".CUR." <input type='hidden' size='10' name='emp_ret' value='$prevsal[emp_ret]' class='right'>$prevsal[emp_ret]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Medical Contribution: Company</td>
				<td>".CUR." <input type='hidden' size='10' name='comp_medical' value='$prevsal[comp_medical]' class='right'>$prevsal[comp_medical]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Medical Contribution: Employee</td>
				<td>".CUR." <input type='hidden' size='10' name='emp_medical' value='$prevsal[emp_medical]' class='right'>$prevsal[emp_medical]</td>
			</tr>
			<input type='hidden' name='comp_other' value='0'>
			<input type='hidden' name='emp_other' value='0'>
			<!--
			<tr class='".bg_class()."'>
				<td nowrap>Other: Company Contribution</td>
				<td>".CUR." <input type='hidden' size='10' name='comp_other' value='$prevsal[comp_other]' class='right'>$prevsal[comp_other]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td nowrap>Other: Employee Deduction</td>
				<td>".CUR." <input type='hidden' size='10' name='emp_other' value='$prevsal[emp_other]' class='right'>$prevsal[emp_other]</td>
			</tr>
			//-->
			$paydetails
			<input type='hidden' name='mpaye' value='1' />
			<input type='hidden' size=10 value='".sprint($prevsal["paye"])."' name='mpaye_amount' />
			<tr><th colspan='2'>Fringe Benefits</th></tr>
			$fringes
			<tr><th colspan='2'>Allowances</th></tr>
			$allowances
			<tr><th colspan='2'>Subsistence Allowances</th></tr>
			$subsistence
			<tr><th colspan='2'>Deductions</th></tr>
			$deductions
			<tr><th colspan='2'>Reimbursements</th></tr>
			$rt
			$weekpday
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
			$js_workhours
		</table>
		</form>";
	return $process;

}



# Confirm data
function confirm ($_POST)
{

	# get vars
	$_POST = var_makesafe($_POST);
	extract ($_POST);

	if(isset($back)) {
		return slctPrd();
	}

	$annual += 0;

	$bonus += 0;

	# validate input
	require_lib("validate");

	$v = new  validate ();
	if(isset($account)) {
		$v->isOk ($account, "num", 1, 9, "Invalid ledger account.");
	}

	$v->isOk ($empnum, "num", 1, 9, "Invalid employee number.");
	$v->isOk ($accid, "num", 1, 9, "Invalid bank number.");
	$v->isOk ($MON, "num", 1, 2, "Invalid month.");
	$v->isOk ($abonus, "float", 1, 11, "Invalid bonus.");
	$v->isOk ($mpaye_amount, "float", 1, 11, "Invalid manual PAYE amount: $mpaye_amount.");
	$v->isOk ($all_travel, "float", 1, 11, "Invalid travel allowance.");
	$v->isOk ($comp_pension, "float", 1, 11, "Invalid company pension.");
	$v->isOk ($comp_medical, "float", 1, 11, "Invalid company medical.");
	$v->isOk ($emp_pension, "float", 1, 11, "Invalid employee pension.");
	$v->isOk ($emp_medical, "float", 1, 11, "Invalid employee medical.");
	$v->isOk ($comp_provident, "float", 1, 11, "Invalid company provident.");
	$v->isOk ($emp_provident, "float", 1, 11, "Invalid employee provident.");
	//$v->isOk ($comp_uif, "float", 1, 11, "Invalid company uif.");
	//$v->isOk ($emp_uif, "float", 1, 11, "Invalid employee uif.");
	$v->isOk ($comp_other, "float", 1, 11, "Invalid company other.");
	$v->isOk ($emp_other, "float", 1, 11, "Invalid employee other.");
	$v->isOk ($comp_ret, "float", 1, 11, "Invalid company ret.");
	$v->isOk ($emp_ret, "float", 1, 11, "Invalid employee ret.");
	$v->isOk ($basic_sal, "float", 1, 11, "Invalid basic salary.");
	$v->isOk ($commission, "float", 0, 11, "Invalid commision.");
	$v->isOk ($loaninstall, "float", 0, 11, "Invalid loan installment.");
	$v->isOk ($loanint, "float", 0, 11, "Invalid loan interest.");

	if ( $divisor != 1 && round($divisor,2) != round(52/12,2) && round($divisor) != round(26/12,2) ) {
		//$v->addError("", "Invalid pay period (DIVIS).");
	}

	if($saltyp == 'd' || $saltyp == 'h'){
		$salnarr = array("d"=>"Days", "h"=>"Hours");
		$salntype = $salnarr[$saltyp];
		$v->isOk ($multi, "float", 1, 5, "Invalid number of $salntype.");
		if($multi < 1)
		$v->addError("", "Error : Employee cannot be paid for $multi $salntype.");
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
			$v->isOk ($allowtax[$key], "string", 1, 3, "Invalid allowance tax option".($key+1).".");
		}
	}

	$date= $year."-".$mon."-".$day;

	if(!checkdate($mon, $day, $year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirmCust.process($_POST);
	}



	$basic_sal_save = $basic_sal;

	db_conn('cubit');

	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}

	$myEmp = pg_fetch_array ($empRslt);

	$yy = date("Y");
	$mm = $MON;
	$mm += 0;

	if($myEmp['payprd'] == "m") {
		$Sl = "SELECT * FROM salpaid WHERE empnum='$empnum' AND month='$mm' AND cyear='".EMP_YEAR."'";
		$Ri = db_exec($Sl);

		$paid = pg_num_rows($Ri);

		$Sl = "SELECT * FROM salr WHERE empnum='$empnum' AND month='$mm' AND cyear='".EMP_YEAR."'";
		$Ri = db_exec($Sl);

		$upaid = pg_num_rows($Ri);

		$upaid += 0;

		$paid -= $upaid;
	}elseif($myEmp['payprd'] == "w") {
		$yy = date("Y");
		$mm = $MON;
		$mm += 0;

		$Sl = "SELECT * FROM salpaid WHERE empnum='$empnum' AND month='$mm' AND cyear='".EMP_YEAR."'";
		$Ri = db_exec($Sl);

		$paid = pg_num_rows($Ri);

		$Sl = "SELECT * FROM salr WHERE empnum='$empnum' AND month='$mm' AND cyear='".EMP_YEAR."'";
		$Ri = db_exec($Sl);

		$upaid = pg_num_rows($Ri);

		$upaid += 0;

		$paid -= $upaid;

	}elseif($myEmp['payprd'] == "f") {
		$yy = date("Y");
		$mm = $MON;
		$mm += 0;

		$Sl = "SELECT * FROM salpaid WHERE empnum='$empnum' AND month='$mm' AND cyear='".EMP_YEAR."'";
		$Ri = db_exec($Sl);

		$paid = pg_num_rows($Ri);

		$Sl = "SELECT * FROM salr WHERE empnum='$empnum' AND month='$mm' AND cyear='".EMP_YEAR."'";
		$Ri = db_exec($Sl);

		$upaid = pg_num_rows($Ri);

		$upaid += 0;

		$paid -= $upaid;

	}

	if(empty($paid) && $myEmp["payprd"] != "d") {
		return "<li class='err'>You have not processed a salary for that period yet.</li>".process($_POST);
	}

	$salconacc = gethook("accnum", "salacc", "name", "salaries control");
	$salconacc_orig = gethook("accnum", "salacc", "name", "salaries control original");

	if ( $salconacc != $salconacc_orig ) {
		block_check($salconacc);
	}

	block_check($uifbal = gethook("accnum", "salacc", "name", "uifbal"));
	block_check($sdlbal = gethook("accnum", "salacc", "name", "sdlbal"));
	block_check($pa = gethook("accnum", "salacc", "name", "pension"));
	block_check($ma = gethook("accnum", "salacc", "name", "medical"));
	block_check($cash_account= gethook("accnum", "salacc", "name", "cash"));
	block_check($retire = gethook("accnum", "salacc", "name", "retire"));
	block_check($provident = gethook("accnum", "salacc", "name", "provident"));
	block_check($commacc = gethook("accnum", "salacc", "name", "Commission"));
	block_check($abonusacc = gethook("accnum", "salacc", "name", "Bonus"));
	block_check($payeacc = gethook("accnum", "salacc", "name", "PAYE"));
	block_check($uifacc = gethook("accnum", "salacc", "name", "UIF"));

	block_check($providente = $myEmp["expacc_provident"]);
	block_check($retiree = $myEmp["expacc_ret"]);
	block_check($pax = $myEmp["expacc_pension"]);
	block_check($uifexp = $myEmp["expacc_uif"]);
	block_check($max = $myEmp["expacc_medical"]);
	block_check($sdlexp = $myEmp["expacc_sdl"]);
	block_check($salacc = $myEmp["expacc_salwages"]);
	block_check($reimbursexp = $myEmp["expacc_reimburs"]);

	if ( ($loanexp = $myEmp["expacc_loan"]) > 0 ) {
		block_check($loanexp);
	}

	if ( isset($allowaccs) ) {
		foreach ( $allowaccs as $checkacc ) {
			block_check($checkacc);
		}
	}
	if ( isset($dedaccs) ) {
		foreach ( $dedaccs as $k => $checkacc ) {
			block_check($checkacc);
		}
	}

	finish_block_check();

	# The Paye
	$tyear = 12;
	switch($myEmp["payprd"]){
		case 'm':
			$tyear = 12;
			break;
		case 'w':
			$tyear = 52;
			break;
		case 'f':
			$tyear = 26;
			break;
		case 'd':
			$tyear = (5 * 52);
			break;
	}

	if ($myEmp["saltyp"] != "h") {
		if ($myEmp["saltyp"] == "w"){
			$perhr = sprint ($basic_sal / $wh_actual);
		}else {
			$perhr = sprint(($basic_sal * $tyear) / ($myEmp['hpweek'] * 52));
		}
	} else {
		$perhr = $basic_sal;
	}

	$overamt = ($novert * ($perhr * $myEmp['novert']));
	$overamt += sprint($hovert * ($perhr * $myEmp['hovert']));

	# Multiply basic_sal add overtime
	if(isset($multi)){
		$basic_sal = sprint($basic_sal * $multi);
		//$tyear = ($tyear/$multi);
	}

	# Zero if not specified
	$commission = $commission + 0;
	$abonus = $abonus + 0;
	$loaninstall = $loaninstall + 0;

	//$basic_sal=$basic_sal+$commission;

	$all_before = "";
	$all_after = "";
	$all_beforeamount = 0;
	$all_afteramount = 0;
	if(isset($allowtax)) {
		foreach ($allowtax as $key => $perc) {
			if($perc == "Yes" and $allowances[$key]>0) {
				$all_before .= "
					<tr class='".bg_class()."'>
						<td>$allowname[$key]</td>
						<td>".CUR." $allowances[$key]</td>
					</tr>";
				$all_beforeamount = ($all_beforeamount  + $allowances[$key]);
			}elseif ($allowances[$key] > 0) {
				$all_after .= "
					<tr class='".bg_class()."'>
						<td>$allowname[$key]</td>
						<td>".CUR." $allowances[$key]</td>
					</tr>";
				$all_afteramount = ($all_afteramount  + $allowances[$key]);
			}
		}
	}

	$subsistence = "";
	$subs_taxable = 0;
	$subs_total = 0;
	$i = 0;
	if (isset($subsname)) {
		if (isset($subs_exch) && $subs_exch == 0) {
			$subs_exch = 1;
		}
		foreach ($subsname as $sid => $sn) {
			if ($subsrep[$sid] == "yes") {
				$nontax = $subsdays[$sid] * ($subsmeal[$sid] == "yes" ? 276 : 85);
				$subs_total += $subsamt[$sid];
			} else {
				// outside republic, 196 dollars
				$nontax = $subsdays[$sid] * (215 / $subs_exch);
				$subs_total += $subsamt[$sid] * $subs_exch;
			}

			$tmp = $subsamt[$sid] - $nontax;

			if ($tmp > 0) {
				$subs_taxable += $tmp;
			}

			$subsistence .= "
				<input type='hidden' name='subsname[$sid]' value='$subsname[$sid]'>
				<input type='hidden' name='subsacc[$sid]' value='$subsacc[$sid]'>
				<input type='hidden' name='subsamt[$sid]' value='$subsamt[$sid]'>
				<input type='hidden' name='subsrep[$sid]' value='$subsrep[$sid]'>
				<input type='hidden' name='subsmeal[$sid]' value='$subsmeal[$sid]'>
				<input type='hidden' name='subsdays[$sid]' value='$subsdays[$sid]'>
				<!--<tr bgcolor='".bgcolor($i)."'>
					<td>$subsname[$sid]</td>
					<td>".CUR." $subsamt[$sid]</td>
					<td>$subsdays[$sid]</td>
				</tr>-->";
		}

		if (false && !empty($subsistence)) {
			$subsistence = "
				<tr>
					<td colspan='20'>
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
						</table>
					</td>
				</tr>";
		}
	}

	$de_before = "";
	$de_after = "";
	$de_beforeamount = 0;
	$de_afteramount = 0;
	$de_beforeamount_emp = 0;
	$de_afteramount_emp = 0;

	if(isset($deducttax)) {
		foreach ($deducttax as $key => $perc) {
			if($perc == "Yes" and $deductions[$key] > 0) {
				$de_before .= "
					<tr class='".bg_class()."'>
						<td>$deductname[$key]</td>
						<td>".CUR." $deductions[$key]</td>
						<!--<td>".CUR." $employer_deductions[$key]</td>//-->
					</tr>";
				$de_beforeamount = ($de_beforeamount  + $deductions[$key] + $employer_deductions[$key]);
				$de_beforeamount_emp += $employer_deductions[$key];
			}elseif ($deductions[$key] > 0) {
				$de_after .= "
					<tr class='".bg_class()."'>
						<td>$deductname[$key]</td>
						<td>".CUR." $deductions[$key]</td>
						<!--<td>".CUR." $employer_deductions[$key]</td>//-->
					</tr>";
				$de_afteramount = ($de_afteramount  + $deductions[$key] + $employer_deductions[$key]);
				$de_afteramount_emp += $employer_deductions[$key];
			}
		}
	}
	$de_before .= "";
	$de_after .= "";

	if ($all_beforeamount > 0) {$all_before ="<tr><th colspan='2'>Allowances</th></tr>".$all_before;}
	if ($all_afteramount > 0) {$all_after ="<tr><th colspan='2'>Allowances</th></tr>".$all_after;}
	if ($de_beforeamount > 0) {
		$de_before = "<tr><th colspan='2'>Deductions</th></tr>".$de_before;
	} else {
		$de_before = "";
	}
	if ($de_afteramount>0) {
		$de_after = "<tr><th colspan='2'>Deductions</th></tr>".$de_after;
	} else {
		$de_after = "";
	}

	// calculate age of employee (if intl., ie has passport num only), act asif under 65
	if ( ! empty($myEmp["idnum"]) ) {
		$bd_year = 1900 + substr($myEmp["idnum"], 0, 2);
		$bd_month = substr($myEmp["idnum"], 2, 2);
		$bd_day = substr($myEmp["idnum"], 4, 2);

		/* get the current financial year */
		db_conn("core");

		$sql = "SELECT yrname FROM active";
		$rslt = db_exec($sql) or errDie("Error fetching financial year.");
		if ( pg_num_rows($rslt) < 1 ) return "Please run quick setup first.";

		$fin_year = substr(pg_fetch_result($rslt, 0, 0), 1);

		$sql = "SELECT EXTRACT('year' FROM AGE('$fin_year-02-28', '$bd_year-$bd_month-$bd_day'))";
		$rslt = db_exec($sql) or errDie("Error calculating age at financial year end.");
		$age = pg_fetch_result($rslt, 0, 0);
	} else {
		$age = 1;
	}

	// calculate loan fringe benefit amount for this month
	//	if ( ! empty($myEmp["loanamt"]) ) {
	//		$loanpart = $loaninstall / $myEmp["loanamt"];
	//		$fringe_loan = sprint($myEmp["loanfringe"] * $loanpart);
	//	} else {
	//		$fringe_loan = "0.00";
	//	}

	/*
	$car_count = ($myEmp["fringe_car1"] > 0?1:0) + ($myEmp["fringe_car2"] > 0?1:0);

	// if car count is one and employee gets a travel allowance, that car's fringe benefit is calculated
	// as if the second car, and ALSO: contribitions/fuel/service amounts are not deducted from benefit
	$car1_travelall = $car_count == 1 && $all_travel > 0;

	if ( $car1_travelall ) {
	$PERC1 = 0.025;
	} else {
	$PERC1 = 0.018;
	}
	*/
	$car1_travelall = false;

	// calculate motor car fringe benefit
	if ( $myEmp["fringe_car1"] > 0 ) {
		$PD = 0;
		if ( $myEmp["fringe_car1_fuel"] == 1 && ! $car1_travelall ) {
			$PD += 0.0022;
		}

		if ( $myEmp["fringe_car1_service"] == 1 && ! $car1_travelall ) {
			$PD += 0.0018;
		}

		$fringe_car1 = $myEmp["fringe_car1"] * ($myEmp["fringe_car1"]>=$myEmp["fringe_car2"]?0.025-$PD:0.04-$PD);

		if ( $myEmp["fringe_car1_contrib"] > 0 && ! $car1_travelall ) {
			$fringe_car1 -= ($myEmp["fringe_car1_contrib"]);
		}
		
		$fringe_car1 /= $divisor;

		if ( $fringe_car1 < 0 ) $fringe_car1 = 0;
	} else {
		$fringe_car1 = 0;
	}

	if ( $myEmp["fringe_car2"] > 0 ) {
		$PD = 0;
		if ( $myEmp["fringe_car2_fuel"] == 1 && ! $car1_travelall ) {
			$PD += 0.0022;
		}

		if ( $myEmp["fringe_car2_service"] == 1 && ! $car1_travelall ) {
			$PD += 0.0018;
		}

		$fringe_car2 = $myEmp["fringe_car2"] * ($myEmp["fringe_car2"]>$myEmp["fringe_car1"]?0.025-$PD:0.04-$PD);

		if ( $myEmp["fringe_car2_contrib"] > 0 && ! $car1_travelall ) {
			$fringe_car2 -= ($myEmp["fringe_car2_contrib"]);
		}
		
		$fringe_car2 /= $divisor;

		if ( $fringe_car2 < 0 ) $fringe_car2 = 0;
	} else {
		$fringe_car2 = 0;
	}

	$fringe_car1 = sprint($fringe_car1);
	$fringe_car2 = sprint($fringe_car2);

	// calc medical fringe benefits
	if ($comp_medical > 0) {
		// calculate dependants after first one
		$tmp_deps = $myEmp["emp_meddeps"] - 2;
		if ($tmp_deps < 0) $tmp_deps = 0;

		// calculate paragraph 12A amount
		$p12A_amt = ($myEmp["emp_meddeps"] > 1 ? 1340 : 820) + ($tmp_deps * 410);

		// calculate taxable fringe benefit amount
		$fringe_medical = sprint($comp_medical - ($p12A_amt / $divisor));
		if ($fringe_medical < 0) {
			$fringe_medical = 0;
		}
	} else {
		$fringe_medical = 0;
	}

	// calculate total fringe benefits
	$tot_fringe = $fringe_medical
				+ $fringe_car1
				+ $fringe_car2
				+ $fringe_loan
				+ $comp_other
				+ $comp_ret
				+ $de_beforeamount_emp
				+ $de_afteramount_emp;

	if ( isset($fringeid) ) {
		foreach ( $fringeid as $key => $value ) {
			$fringebens[$key] = sprint($fringebens[$key]);

			$tot_fringe += $fringebens[$key];
		}
	}

	if ( $emp_pension > $basic_sal * 7.5/100 ) {
		$emp_mpension = $basic_sal * 7.5/100;
	} else {
		$emp_mpension = $emp_pension;
	}
	
	$max_ret = ($myEmp["basic_sal_annum"] * 7.5/100 > 1750)?$myEmp["basic_sal_annum"] * 7.5/100:1750;

	// calculate total gross salary
	$grossal = $basic_sal
				+ $commission
				+ $abonus
				+ $overamt // overtime
				+ $bonus // monthly bonus
				+ $annual // annual bonus paid this month
				+ $all_beforeamount // allowances added before paye
				+ ( $all_travel*0.8 ) // 80% of travel allowance
				- $de_beforeamount; // deductions deducted before paye (non taxible)
	$grossal_2 = $grossal;

	$taxed_all = $all_afteramount + ($all_travel * 0.8);

	$grossal_nodedall = $basic_sal
		+ $overamt
		+ $bonus
		+ $annual
		+ $all_travel;

	#UIF HAX
	$uif_grossal = $grossal;

	// pension/provident/ra: calculate deduction amounts, limiting them to maximum amount and only deducting
	// ONE of them for taxable income
	if ( $comp_pension + $emp_pension > 0 ) {
		$tmp = ($grossal_2 + $tot_fringe) * $tyear;
		$maxallowed = ($tmp * 0.075>1750)?$tmp * 0.075:1750;
		if ( $emp_mpension > $maxallowed ) {
			$tmp_ded = $maxallowed;
		} else {
			$tmp_ded = $emp_mpension;
		}

		$grossal -= $tmp_ded;
	}

	if ( $comp_ret + $emp_ret > 0 ) {
		$tmp = ($grossal_2 + $tot_fringe) * $tyear;

		// if their is a pension contributions the percentage is 0
		if ( $comp_pension + $emp_pension + $comp_provident + $emp_provident > 0 ) {
			$PERC = 0;
		} else {
			$PERC = 0.15;
		}

		$maxallowed = ($tmp * $PERC>1750) ? $tmp * $PERC : 1750;
		$maxallowed = ($maxallowed > 3500 - ($emp_pension * $divisor * 12)) ? $maxallowed : 3500 - $emp_pension * 12;

		if (($emp_ret + $comp_ret) * $divisor > $maxallowed / 12 ) {
			$tmp_ded = $maxallowed / 12;
		} else {
			$tmp_ded = ($emp_ret + $comp_ret) * $divisor;
		}

		$grossal -= $tmp_ded;
	}

	// calculate total paye salary
	// just remove annual this month, and add annual divided by 12
	// because paye is calculate for full twelve months and therefore
	// paye salary is average received each month
	$paye_salary = $grossal
					+ $subs_taxable
					- $annual // annual bonus fixed for monthly/weerkly/hourly multiply
					// special bonus not calculated annually - $bonus + ($bonus/$tyear) // same with special bonus
					+ $tot_fringe; // total fringe benefits;

	#UIF HAX
	$uif_paye_salary = $uif_grossal + $subs_taxable - $annual + $tot_fringe;

	/* calculate uif */
	$tmp_remun = $paye_salary + $annual - $commission - $abonus;

	#UIF HAX
	$uif_tmp_remun = $uif_paye_salary + $annual - $commission - $abonus;

//	$comp_uif = sprint($tmp_remun * ($myEmp["comp_uif"] / 100));
//	$emp_uif = sprint($tmp_remun * ($myEmp["emp_uif"] / 100));

	#UIF HAX
	$comp_uif = sprint($uif_tmp_remun * ($myEmp["comp_uif"] / 100));
	$emp_uif = sprint($uif_tmp_remun * ($myEmp["emp_uif"] / 100));

	$uifmax = getCSetting("UIF_MAX");

	if ( $emp_uif > $uifmax ) {
		$emp_uif = sprint($uifmax);
	}

	if ( $comp_uif > $uifmax ) {
		$comp_uif = sprint($uifmax);
	}


	/* calculate sdl */
	$tmp_remun = $paye_salary + $annual;
	if (getCSetting("SDLPAYABLE") == "y") {
		$tmp_sdl = $tmp_remun;
		
		if ( $age > 65 ) {
			$tmp_sdl -= $comp_medical;
		}

		$comp_sdl = $tmp_sdl * ($myEmp["comp_sdl"] / 100);

	} else {
		$comp_sdl = 0;
	}

	// a little hack, apparently the grossal is displayed wrong, in a strictly antisocial.co.za opinion,
	// i think the person who thinks that must suck
	$grossal += $comp_ret;

	// add rest of travel allowance
	$grossal += $all_travel * 0.2;

	if( isset($mpaye) ) {
		$paye = $mpaye_amount;
	} else {
		// calculate paye (take age of 65+ threshold into account)
//		if ( ($age >= 65 && ($paye_salary * $tyear) < 69000) || ($paye_salary * $tyear) < 43000 ) {
// 		if ( ($age >= 65 && ($paye_salary * $tyear) < 74000) || ($paye_salary * $tyear) < 46000 ) {
//		if ( ($age >= 65 && ($paye_salary * $tyear) < 84200) || ($paye_salary * $tyear) < 54200 ) {
		if ( ($age >= 65 && ($paye_salary * $tyear) < 88528) || ($paye_salary * $tyear) < 57000 ) {
			$paye = "0.00";
		} else {
			if ($myEmp["payprd"] == "w" || $myEmp["payprd"] == "f") {
				$paye_prd = "$MON:$week";
			} else if ($myEmp["payprd"] == "d") {
				$paye_prd = "$MON:$day";
			} else {
				$paye_prd = "$MON";
			}

			$paye = calculate_paye($myEmp, $paye_prd, $paye_salary, $tyear, $age);

			if ( $annual > 0 ) {
				$tmp_bonpaye = calculate_paye($myEmp, $paye_prd, $paye_salary + $annual/12, $tyear, $age);

				$paye += ($tmp_bonpaye * $tyear) - ($paye * $tyear);
			}
		}
	}

	// fringe benefits
	$i = 0;
	$fringes = "";
	$fringes_desc = "";
	if ( isset($fringebens) ) {
		foreach ( $fringebens as $key => $value ) {
			if ( $fringebens[$key] > 0 ) {

				$fringes_desc .= "
					<tr class='".bg_class()."'>
						<td>$fringename[$key]</td>
						<td>".CUR." $fringebens[$key]</td>
					</tr>";

				$fringes .= "
					<input type='hidden' name='fringebens[]' value='$fringebens[$key]'>
					<input type='hidden' name='fringeid[]' value='$fringeid[$key]'>
					<input type='hidden' name='fringename[]' value='$fringename[$key]'>
					<input type='hidden' name='fringeaccs[]' value='$fringeaccs[$key]'>";
			}
		}
	}

	if ( ! empty($fringes_desc) ) {
		$fringes_desc = "
			<tr>
				<th colspan='2'>Fringe Benefits</th>
			</tr>
			$fringes_desc";
	}

	$allow = "";
	# Get allowances names and value from array
	if(isset($allowances)){
		foreach($allowances as $key => $value){
			if($allowances[$key] > 0){
				$allow .="
					<input type='hidden' size='10' name='allowname[]' value='$allowname[$key]'>
					<input type='hidden' size='10' name='allowid[]' value='$allowid[$key]'>
					<input type='hidden' size='10' name='allowances[]' value='$allowances[$key]'>
					<input type='hidden' size='10' name='allowtax[]' value='$allowtax[$key]'>
					<input type='hidden' name='allowaccs[]' value='$allowaccs[$key]'>";
			}
		}
	}

	$deduct="";
	if( isset($deductions) ) {
		foreach( $deductions as $key => $value ){
			if( $deductions[$key] > 0) {
				$deduct .= "
					<input type='hidden' size='10' name='deductname[]' value='$deductname[$key]'>
					<input type='hidden' size='10' name='deductid[]' value='$deductid[$key]'>
					<input type='hidden' size='10' name='deductions[]' value='$deductions[$key]'>
					<input type='hidden' size='10' name='employer_deductions[]' value='$employer_deductions[$key]'>
					<input type='hidden' size='10' name='deducttax[]' value='$deducttax[$key]'>
					<input type='hidden' name='dedaccs[]' value='$dedaccs[$key]'>";
			}
		}
	}

	$nonretfunding = $basic_sal
					- $paye
					- $loaninstall
					- $de_afteramount
					+ $de_afteramount_emp
					+ $all_afteramount
					- $emp_pension
					- $emp_medical
					- $emp_uif
					- $emp_provident;

	/*$ret_max = (1800>($nonretfunding*0.15)) ? 1800 : ($nonretfunding*0.15);

	if ( $comp_ret + $emp_ret > $ret_max ) {
	$comp_ret = $ret_max - $emp_ret;

	if ( $comp_ret < 0 ) {
	$comp_ret = 0;
	$emp_ret = $ret_max;
	}
	}*/

	$nettpay = $basic_sal
				+ $overamt
				- $paye
				+ $commission
				+ $abonus
				- $loaninstall
				- $de_beforeamount
				- $de_afteramount
				+ $all_afteramount
				+ $all_beforeamount
				- $emp_pension
				- $emp_medical
				- $emp_ret
				- $emp_uif
				- $emp_provident
				- $emp_other
				+ $all_travel
				+ $annual
				+ $bonus
				- $myEmp["fringe_car1_contrib"]
				- $myEmp["fringe_car2_contrib"]
				+ $subs_total;

	$nettpay = sprint($nettpay);

	if(isset($rbsa)) {
		$nettpay += array_sum($rbsa);
		$nettpay = sprint($nettpay);
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM cubit.rbs ORDER BY name";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$i = 0;

	$rt = "";

	if (pg_num_rows($Ri) > 0) {
		while($td = pg_fetch_array($Ri)) {
			if (!isset($rbsa[$td['id']]) || $rbsa[$td['id']] < 1) {
				continue;
			}

			$rbsa[$td['id']] = sprint($rbsa[$td['id']]);

			if ($i == 0) {
				$rt = "<tr><th colspan=2>Reimbursements</th></tr>";
			}

			$rt .= "
                <tr class='".bg_class()."'>
                    <td><input type='hidden' name='rbs[$td[id]]' value='$td[id]'>$td[name]</td>
                    <td>".CUR." <input type='hidden' name='rbsa[$td[id]]' value='".$rbsa[$td['id']]."'>".$rbsa[$td['id']]."</td>
				</tr>";

			$i++;
		}
	} else {
		$rt .= "
			<tr class='".bg_class()."'>
				<td colspan='2'>There are no reimbursements</td>
			</tr>";
	}

	db_conn("cubit");

	# Get bank account name
	$sql = "SELECT * FROM bankacct WHERE bankid = '$accid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);

	$bank = pg_fetch_array($bankRslt);

	$basic_sal = sprint($basic_sal);
	$commission = sprint($commission);
	$abonus = sprint ($abonus);
	$overamt = sprint($overamt);
	$paye = sprint($paye);
	$nettpay = sprint($nettpay);

	if($myEmp['paytype']=="Cash") {
		$paydetails = "
			<tr class='".bg_class()."'>
				<td colspan='2'>Pay Salary Cash</td>
			</tr>";
	} else {
		$paydetails = "
			<tr class='".bg_class()."'>
				<td>Bank Account</td>
				<td>$bank[accname]</td>
			</tr>";
	}

	vsprint($bonus);
	vsprint($annual);
	vsprint($comp_pension);
	vsprint($emp_medical);
	vsprint($comp_ret);
	vsprint($emp_ret);
	vsprint($loaninstall);
	vsprint($emp_pension);
	vsprint($fringe_medical);

	if(!isset($account)) {
		$account = 0;
	} else {

		db_conn('core');

		$Sl = "SELECT * FROM accounts WHERE accid='$account'";
		$Ri = db_exec($Sl);

		$ad = pg_fetch_array($Ri);

		$paydetails = "
			<tr class='".bg_class()."'>
				<td>Ledger Account</td>
				<td>$ad[accname]</td>
			</tr>";
	}

	if($myEmp['payprd'] == "w") {
		$row = "
			<tr class='".bg_class()."'>
				<td>Period</td>
				<td>$week</td>
			</tr>
			<input type='hidden' name='week' value='$week'>";
	} elseif($myEmp['payprd']=="f") {
		$row = "
			<tr class='".bg_class()."'>
				<td>Period</td>
				<td>$week</td>
			</tr>
			<input type='hidden' name='week' value='$week'>";
	} else {
		$row = "<input type='hidden' name='week' value='0'>";
	}

	$grossal = sprint($grossal);

	$confirm = "
		<form action='".SELF."' method='POST'>
    	<table ".TMPL_tblDflts." width='300'>
		    <tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
			<input type='hidden' name='date_day' value='$day' />
			<input type='hidden' name='date_month' value='$mon' />
			<input type='hidden' name='date_year' value='$year' />
			<input type='hidden' name='key' value=pack>
			<input type='hidden' name='grossal' value='$grossal'>
			<input type='hidden' name='grossal_nodedall' value='$grossal_nodedall'>
			<input type='hidden' name='empnum' value='$empnum'>
			<input type='hidden' name='account' value='$account'>
			<input type='hidden' name='MON' value=$MON>
			<input type='hidden' name='basic_sal' value='$basic_sal_save'>
			<input type='hidden' name='multi' value='$multi'>
			<input type='hidden' name='tyear' value='$tyear'>
			<input type='hidden' name='commission' value='$commission'>
			<input type='hidden' name='abonus' value='$abonus'>
			<input type='hidden' name='overamt' value='$overamt'>
			<input type='hidden' name='loaninstall' value='$loaninstall'>
			<input type='hidden' name='paye' value='$paye'>
			<input type='hidden' name='accid' value='$accid'>
			<input type='hidden' name='income' value='$nettpay'>
			<input type='hidden' name='bonus' value='$bonus'>
			<input type='hidden' name='all_travel' value='$all_travel'>
			<input type='hidden' name='comp_pension' value='$comp_pension'>
			<input type='hidden' name='emp_pension' value='$emp_pension'>
			<input type='hidden' name='comp_medical' value='$comp_medical'>
			<input type='hidden' name='emp_medical' value='$emp_medical'>
			<input type='hidden' name='comp_ret' value='$comp_ret'>
			<input type='hidden' name='emp_ret' value='$emp_ret'>
			<input type='hidden' name='comp_uif' value='$comp_uif'>
			<input type='hidden' name='emp_uif' value='$emp_uif'>
			<input type='hidden' name='comp_sdl' value='$comp_sdl'>
			<input type='hidden' name='comp_other' value='$comp_other'>
			<input type='hidden' name='emp_other' value='$emp_other'>
			<input type='hidden' name='comp_provident' value='$comp_provident'>
			<input type='hidden' name='emp_provident' value='$emp_provident'>
			<input type='hidden' name='paye_salary' value='$paye_salary'>
			<input type='hidden' name='day' value='$day'>
			<input type='hidden' name='mon' value='$mon'>
			<input type='hidden' name='year' value='$year'>
			<input type='hidden' name='novert' value='$novert'>
			<input type='hidden' name='hovert' value='$hovert'>
			<input type='hidden' name='annual' value='$annual'>
			<input type='hidden' name='week' value='$week'>
			<input type='hidden' name='fringe_medical' value='$fringe_medical'>
			<input type='hidden' name='fringe_tot' value='$tot_fringe'>
			<input type='hidden' name='fringe_car1' value='$fringe_car1'>
			<input type='hidden' name='fringe_car2' value='$fringe_car2'>
			<input type='hidden' name='fringe_loan' value='$fringe_loan'>
			<input type='hidden' name='loanint' value='$loanint'>
			<input type='hidden' name='loaninstall_date' value='$loaninstall_date'>
			<input type='hidden' name='loaninstall_prd' value='$loaninstall_prd'>
			<input type='hidden' name='process_comp_deductions' value='$process_comp_deductions'>
			<input type='hidden' name='taxed_all' value='$taxed_all' />
			".(isset($subs_exch)?"<input type='hidden' name='subs_exch' value='$subs_exch'>":"")."
			$fringes
			$allow
			$deduct
			<tr>
				<th colspan='2'>Salary Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Basic salary</td>
				<td>".CUR." $basic_sal</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Annual Bonus</td>
				<!--<td>Special Bonus/Additional Salary</td>-->
				<td>".CUR." $abonus</td>
			</tr>
			<!--
			<tr class='".bg_class()."'>
				<td>Bonus(Annual Payments)</td>
				<td>".CUR." $annual</td>
			</tr>
			-->
			<tr class='".bg_class()."'>
				<td>Commission</td>
				<td>".CUR." $commission</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Travel Allowance</td>
				<td>".CUR." $all_travel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Pension: Company Contribution</td>
				<td>".CUR." $comp_pension</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Pension: Employee Deduction</td>
				<td>".CUR." $emp_pension</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Provident Fund: Company Contribution</td>
				<td>".CUR." $comp_provident</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Provident Fund: Employee Deduction</td>
				<td>".CUR." $emp_provident</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>UIF: Company Contribution</td>
				<td>".CUR." $comp_uif</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>UIF: Employee Deduction</td>
				<td>".CUR." $emp_uif</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Retirement Annuity: Company Contribution</td>
				<td>".CUR." $comp_ret</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Retirement Annuity: Employee Deduction</td>
				<td>".CUR." $emp_ret</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Contribution: Company</td>
				<td>".CUR." $comp_medical</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Contribution: Employee</td>
				<td>".CUR." $emp_medical</td>
			</tr>
			<!--
			<tr class='".bg_class()."'>
				<td>Other: Company Contribution</td>
				<td>".CUR." $comp_other</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Other: Employee Deduction</td>
				<td>".CUR." $emp_other</td>
			</tr>
			//-->
			<tr class='".bg_class()."'>
				<td>Overtime</td>
				<td>".CUR." $overamt</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Medical Fringe Benefit</td>
				<td>".CUR." $fringe_medical</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Motorcar 1 Fringe Benefit</td>
				<td>".CUR." $fringe_car1</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Motorcar 1 Contribution for Use</td>
				<td>".CUR." $myEmp[fringe_car1_contrib]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Motorcar 2 Fringe Benefit</td>
				<td>".CUR." $fringe_car2</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Motorcar 2 Contribution for Use</td>
				<td>".CUR." $myEmp[fringe_car2_contrib]</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Loan Interest Fringe Benefit</td>
				<td>".CUR." $fringe_loan</td>
			</tr>
			$fringes_desc
			$all_before
			$de_before
			<tr>
				<th colspan='2'>Gross Salary</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Gross Salary</td>
				<td>".CUR." $grossal</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>SITE/PAYE</td>
				<td>".CUR." $paye</td>
			</tr>
			<tr>
				<th colspan='2'>Loans</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Loan Instalment</td>
				<td>".CUR." $loaninstall</td>
			</tr>
			$all_after
			$subsistence
			$de_after
			<tr>
				<th colspan='2'>Nett Pay</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Nett Pay + Reimbursements</td>
				<td>".CUR." $nettpay</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Amount Paid now</td>
				<td><input type='text' size='10' name='paidamount' value='0'></td>
			</tr>
			$paydetails
			<tr class='".bg_class()."'>
				<td>Date</td>
				<td>$date</td>
			</tr>
			$row
			$rt
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</table>
		</form>";
	return $confirm;

}



# Write new data
function package($_POST)
{

	$_POST = var_makesafe($_POST);
	extract($_POST);

	$week += 0;

	if(isset($back)) {
		return process($_POST);
	}

	$annual += 0;

	$bonus += 0;
	$paye_salary += 0;

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($accid, "num", 1, 20, "Invalid bank number.");
	$v->isOk ($MON, "num", 1, 2, "Invalid month.");
	$v->isOk ($basic_sal, "float", 1, 20, "Invalid basic salary.");
	$v->isOk ($overamt, "float", 1, 20, "Invalid overtime amount.");
	$v->isOk ($income, "float", 1, 20, "Invalid income.");
	$v->isOk ($commission, "float", 0, 20, "Invalid commision.");
	$v->isOk ($abonus, "float", 0, 20, "Invalid Annual Bonus.");
	$v->isOk ($loaninstall, "float", 0, 20, "Invalid loan installment.");
	$v->isOk ($paidamount, "float", 1, 20, "Invalid paid amount.");

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

	$date = mkdate($year, $mon, $day);

	if(!checkdate($mon, $day, $year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	$mon = $MON;

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class='err'>".$e["msg"]."</li>";
		}
		$write .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}



	$basic_sal_save = $basic_sal;

	if(isset($multi)){
		$basic_sal = sprint($basic_sal * $multi);
		$tyear = ($tyear/$multi);
	} else {
		$basic_sal = $basic_sal;
	}

	db_conn('cubit');

	$nettpay = $income;

	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}

	$ecost = 0;

	$myEmp = pg_fetch_array ($empRslt);


	// fringe benefits
	$i = 0;
	$fringes = "";
	$fringes_desc = "";
	if ( isset($fringebens) ) {
		foreach ( $fringebens as $key => $value ) {
			if ( $fringebens[$key] > 0 ) {
				$fringes_desc .= "
					<tr>
						<td>$fringename[$key]</td>
						<td>".CUR." $fringebens[$key]</td>
					</tr>";
			}
		}
	}

	if ( ! empty($fringes_desc) ) {
		$fringes_desc = "<tr><th colspan='2'>Fringe Benefits</th></tr>$fringes_desc";
	}

	$all_before = "";
	$all_after = "";
	$all_beforeamount = 0;
	$all_afteramount = 0;
	if(isset($allowtax)) {
		foreach ($allowtax as $key => $perc) {
			if($perc == "Yes" and $allowances[$key]>0) {
				$all_before .="<tr><td>$allowname[$key]</td><td align='right'>".CUR." $allowances[$key]</td></tr>";
				$all_beforeamount = ($all_beforeamount  + $allowances[$key]);
			} elseif ( $allowances[$key] > 0 ) {
				$all_after .="<tr><td>$allowname[$key]</td><td align='right'>".CUR." $allowances[$key]</td></tr>";
				$all_afteramount = ($all_afteramount  + $allowances[$key]);
			}
		}
	}

	$de_before = "";
	$de_after = "";
	$de_beforeamount = 0;
	$de_afteramount = 0;
	$de_beforeamount_emp = 0;
	$de_afteramount_emp = 0;
	if(isset($deducttax)) {
		foreach ($deducttax as $key => $perc) {
			if($perc == "Yes" and $deductions[$key] > 0) {
				$de_before .= "
					<tr>
						<td>$deductname[$key]</td>
						<td align='right'>".CUR." $deductions[$key]</td>
						<td align='right'>".CUR." $employer_deductions[$key]</td>
					</tr>";
				$de_beforeamount = ($de_beforeamount  + $deductions[$key] + $employer_deductions[$key]);
				$de_beforeamount_emp += $employer_deductions[$key];
			}elseif ($deductions[$key] > 0) {
				$de_after .= "
					<tr>
						<td>$deductname[$key]</td>
						<td align='right'>".CUR." $deductions[$key]</td>
						<td align='right'>".CUR." $employer_deductions[$key]</td>
					</tr>";
				$de_afteramount = ($de_afteramount  + $deductions[$key] + $employer_deductions[$key]);
				$de_afteramount_emp += $employer_deductions[$key];
			}
		}
	}
	$de_before .= "";
	$de_after .= "";

	if ($all_beforeamount > 0) {$all_before = "<tr><td colspan='2'>Allowances</td></tr>".$all_before;}
	if ($all_afteramount > 0) {$all_after = "<tr><td colspan='2'>Allowances</td></tr>".$all_after;}
	if ($de_beforeamount > 0) {$de_before = "<tr><td colspan='2'>Deductions</td></tr>".$de_before;}
	if ($de_afteramount > 0) {$de_after = "<tr><td colspan='2'>Deductions</td></tr>".$de_after;}

	$gros_sal = sprint($grossal);

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	core_connect();

	$sql = "SELECT * FROM bankacc WHERE accid = '$accid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# check if link exists
	if(pg_numrows($rslt) < 1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}
	$bank = pg_fetch_array($rslt);
	$bankacc = $bank["accnum"];

	$basic_sal = sprint($basic_sal);
	$commission = sprint($commission);
	$abonus = sprint ($abonus);
	$overamt = sprint($overamt);
	$paye = sprint($paye);
	$nettpay = sprint($nettpay);

	$sdl = sprint($comp_sdl);
	$amount = sprint($gros_sal + $comp_pension + $comp_provident + $comp_medical + $comp_other + $comp_uif + $comp_ret + $sdl);
	$loaninstall = sprint($loaninstall);

	//Original CC
	//$cc = "<script> CostCenter('ct', 'Salaries', '$date', 'Salary Payment for employee,  $myEmp[fnames] $myEmp[sname]', '$amount', '../'); </script>";

	//New CC
	$cc = "CostCenter('ct', 'Salaries', '$date', 'Salary Payment for employee,  $myEmp[fnames] $myEmp[sname]', '$amount', '../'); ";

	$ecost = $amount;

	if($commission > 0) {
		$comDis = "
			<tr>
				<td>Commission</td>
				<td align='right'>".CUR." $commission</td>
			</tr>";
	} else {
		$comDis = "";
	}

	if($abonus > 0) {
		$abonusDis = "
			<tr>
				<td>Commission</td>
				<td align='right'>".CUR." $commission</td>
			</tr>";
	} else {
		$abonusDis = "";
	}


	if($overamt > 0) {
		$oveDis = "
			<tr>
				<td>Overtime</td>
				<td align='right'>".CUR." $overamt</td>
			</tr>";
	} else {
		$oveDis = "";
	}

	if($loaninstall > 0) {
		$loaDis = "
			<tr>
				<td>Loan Instalment</td>
				<td align='right'>".CUR." $loaninstall</td>
			</tr>";
	} else {
		$loaDis = "";
	}

	if($basic_sal != $gros_sal) {
		$groDis = "
			<tr>
				<td>Gross Salary</td>
				<td align='right'>".CUR." $gros_sal</td>
			</tr>";
	} else {
		$groDis = "";
	}

	if($all_travel > 0) {
		$talDis = "
			<tr>
				<td>Travel Allowance</td>
				<td align='right'>".CUR." $all_travel</td>
			</tr>";
	} else {
		$talDis = "";
	}

	db_connect ();

	$Sl = "SELECT * FROM salset";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) > 0) {
		$con = true;
	} else {
		$con = false;
	}

	$intrec = gethook("accnum", "salacc", "name", "interestreceived");
	$uifbal = gethook("accnum", "salacc", "name", "uifbal");
	$sdlbal = gethook("accnum", "salacc", "name", "sdlbal");
	$pa = gethook("accnum", "salacc", "name", "pension");
	$ma = gethook("accnum", "salacc", "name", "medical");
	$cash_account= gethook("accnum", "salacc", "name", "cash");
	$retire = gethook("accnum", "salacc", "name", "retire");
	$provident = gethook("accnum", "salacc", "name", "provident");
	$salconacc = gethook("accnum", "salacc", "name", "salaries control");
	$commacc = gethook("accnum", "salacc", "name", "Commission");
	$abonusacc = gethook ("accnum", "salacc", "name", "Bonus");
	$payeacc = gethook("accnum", "salacc", "name", "PAYE");
	$uifacc = gethook("accnum", "salacc", "name", "UIF");

	$providente = $myEmp["expacc_provident"];
	$retiree = $myEmp["expacc_ret"];
	$pax = $myEmp["expacc_pension"];
	$uifexp = $myEmp["expacc_uif"];
	$max = $myEmp["expacc_medical"];
	$dedgenerale = $myEmp["expacc_other"];
	$sdlexp = $myEmp["expacc_sdl"];
	$salacc = $myEmp["expacc_salwages"];
	$loanexp = $myEmp["expacc_loan"];
	$reimbursexp = $myEmp["expacc_reimburs"];

	if ( $con ) {
		$uifexp = $salacc;
		$sdlexp = $salacc;
		$pax = $salacc;
		$max = $salacc;
		$retiree = $salacc;
	}

	// Get Bank account [the traditional way re: hook of hook]
	core_connect();

	$sql = "SELECT * FROM bankacc WHERE accid = '$accid' AND div = '".USER_DIV."'";
	$Rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
	# check if link exists
	if(pg_numrows($Rslt) < 1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}

	$bank = pg_fetch_array($Rslt);

	$refnum = getrefnum($date);

	# Debit uif acc and credit uif control acc
	if ( $comp_uif > 0 ) {
		writetrans($uifbal, $uifexp, $date, $refnum, $comp_uif, "Company UIF Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if ( $emp_uif > 0 ) {
		db_conn("cubit");
		$Sl = "UPDATE employees SET balance=balance+($emp_uif) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $uifacc, $date, $refnum, "UIF" ,  $emp_uif, "c");

		writetrans($uifbal, $salconacc, $date, $refnum, $emp_uif, "Employee UIF Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	# Debit uif sdl and credit sdl control acc
	writetrans($sdlbal, $sdlexp, $date, $refnum, $sdl, "SDL,  $myEmp[fnames] $myEmp[sname].");

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	db_conn('cubit');

	$Sl = "UPDATE employees SET balance=balance-($grossal_nodedall) WHERE empnum = '$empnum'";
	$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

	empledger($empnum, $salacc, $date, $refnum,"Gross Salary" , $grossal_nodedall , "d");

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	# Debit salaries acc and credit salaries control acc
	writetrans($salconacc, $salacc, $date, $refnum, $grossal_nodedall, "Gross Salary proccessing for employee,  $myEmp[fnames] $myEmp[sname].");

	if($commission > 0) {
		if($con) {
			$commacc = $salacc;
		}
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance-($commission) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");
		empledger($empnum, $commacc, $date, $refnum,"Commission" ,  $commission, "d");
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		# Debit commission acc and credit salaries control acc
		writetrans($salconacc, $commacc, $date, $refnum, $commission, "Commission for employee,  $myEmp[fnames] $myEmp[sname].");
	}

	if($abonus > 0) {
		if($con) {
			$abonusacc = $salacc;
		}
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance-($abonus) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");
		empledger($empnum, $abonusacc, $date, $refnum,"Bonus" ,  $abonus, "d");
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		# Debit commission acc and credit salaries control acc
		writetrans($salconacc, $abonusacc, $date, $refnum, $abonus, "Bonus for employee,  $myEmp[fnames] $myEmp[sname].");
	}



	if($loaninstall > 0 && !empty($loanexp)) {
		$loaninstall += 0;

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance+($loaninstall) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $loanexp, $date, $refnum,"Loan Instalment" , $loaninstall , "c");

		# Debit salaries control acc and credit loan control acc
		writetrans($loanexp, $salconacc, $date, $refnum, $loaninstall - $loanint, "Loan Installment for employee,  $myEmp[fnames] $myEmp[sname].");
		writetrans($intrec, $salconacc, $date, $refnum, $loanint, "Loan Interest for employee,  $myEmp[fnames] $myEmp[sname].");

		/* wipe the loan installment info recording for this salprd */
		db_conn("cubit");
		$sql = "
			DELETE FROM emp_loaninstallments 
			WHERE empnum='$empnum' AND fmonth='$mon' AND fyear='".EMP_YEAR."' AND fdate='$loaninstall_date' AND fperiod='$loaninstall_prd'";
		$rslt = db_exec($sql) or errDie("Error record loan fringe benefit.");
	}

	if($paye > 0) {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance+($paye) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $payeacc, $date, $refnum,"PAYE" , $paye , "c");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		# Debit salaries control acc and credit PAYE control acc
		writetrans($payeacc, $salconacc, $date, $refnum, $paye, "PAYE for employee,  $myEmp[fnames] $myEmp[sname].");
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	db_conn('cubit');

	// fringe benefits
	if ( isset($fringeid) ) {
		foreach ( $fringeid as $i => $id ) {
			//		empledger($empnum, $fringeaccs[$i], $date, $refnum,"Fringe Benefit, $fringename[$i]" , $fringebens[$i], "d");
			//		writetrans($salconacc, $fringeaccs[$i], $date, $refnum, $fringebens[$i], "Fringe Benefit for employee, $myEmp[fnames] $myEmp[sname].");
		}
	}

	# Pay allowances accounts
	if(isset($allowid)){
		foreach($allowid as $i => $id){
			# Debit allowances acc and credit salaries control acc
			if($con) {
				$allowaccs[$i] = $salacc;
			}

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance-($allowances[$i]) WHERE empnum = '$empnum'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($empnum, $allowaccs[$i], $date, $refnum,"Allowance" , $allowances[$i] , "d");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			writetrans($salconacc, $allowaccs[$i], $date, $refnum, $allowances[$i], "Allowances for employee, $myEmp[fnames] $myEmp[sname].");
		}
	}

	# Pay Deductions accounts
	if(isset($deductid)){
		foreach($deductid as $i => $id){
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance+($deductions[$i]) WHERE empnum = '$empnum'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($empnum, $dedaccs[$i], $date, $refnum,"Deduction" , $deductions[$i], "c");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			# Debit salaries control acc and credit  acc
			// salcon acc - ded balance acc
			writetrans($dedaccs[$i], $salconacc, $date, $refnum, $deductions[$i], "Deductions for employee, $myEmp[fnames] $myEmp[sname].");

			db_conn("cubit");
			$sql = "SELECT * FROM salded WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Error reading deduction information.");

			$dedinfo = pg_fetch_array($rslt);
		}
	}

	if($comp_pension > 0) {
		writetrans($pa, $pax, $date, $refnum, $comp_pension, "Company Pension Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if($emp_pension > 0) {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance+($emp_pension) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $pa, $date, $refnum,"Pension Contribution" , $emp_pension, "c");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		writetrans($pa, $salconacc, $date, $refnum, $emp_pension, "Pension Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if($comp_medical > 0) {
		writetrans($ma, $max, $date, $refnum, $comp_medical, "Company Medical Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if($emp_medical > 0) {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance+($emp_medical) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $ma, $date, $refnum, "Medical Contribution" , $emp_medical, "c");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		writetrans($ma, $salconacc, $date, $refnum, $emp_medical, "Employee Medical Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if($comp_provident > 0) {
		writetrans($provident, $providente, $date, $refnum, $comp_provident, "Company Provident Fund Contribution, $myEmp[fnames] $myEmp[sname].");
	}

	if($emp_provident > 0) {

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance+($emp_provident) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $provident, $date, $refnum,"Provident Fund Contribution" , $emp_provident, "c");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		writetrans($provident, $salconacc, $date, $refnum, $emp_provident, "Provident Fund Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if(false && $comp_other > 0) {
		writetrans($dedgeneral, $dedgenerale, $date, $refnum, $comp_other, "Company Contribution to Other Deductions, $myEmp[fnames] $myEmp[sname].");
	}

	if(false && $emp_other > 0) {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance+($emp_other) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $dedgeneral, $date, $refnum,"Other Deductions Contribution" , $emp_other, "c");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		writetrans($dedgeneral, $salconacc,$date, $refnum, $emp_other, "Other Deductions Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if($emp_ret > 0) {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance+($emp_ret) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $retire, $date, $refnum,"Retirement Annuity Contribution" , $emp_ret, "c");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		writetrans($retire, $salconacc, $date, $refnum, $emp_ret, "Employee Retirement Annuity Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if($comp_ret > 0) {
		writetrans($retire, $retiree, $date, $refnum, $comp_ret, "Company Retirement Annuity Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	db_conn('cubit');
	$mons = "$mon;";

	$due = sprint($nettpay-$paidamount);//, balance=balance+'$due

	$sql = "
		UPDATE employees 
		SET lastpay = '$mons', loanamt = (loanamt + cast(float '$loaninstall' as numeric)), 
			loanfringe = (loanfringe + cast(float '$fringe_loan' as numeric)) 
		WHERE empnum = '$empnum' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to get employee details.");

	$loaninstall = $loaninstall + 0;
	$totded = sprint($de_beforeamount + $de_afteramount+$emp_pension+$emp_medical+$emp_provident+$emp_ret+$emp_other);
	$totded_employer = sprint($de_beforeamount_emp + $de_afteramount_emp+$comp_pension+$comp_medical+$comp_provident+$comp_ret+$comp_other);
	$totall = sprint($all_beforeamount + $all_afteramount + $all_travel);

	$parkage = "
		<br><br>
		<center>
		$cc
		<table border='2' cellpadding='4' cellspacing='0' width='750' bordercolor='#000000'>
			<tr>
				<td align='center'><b>Description</b></td>
				<td width='100' align='center'><b>Amount</b></td>
			</tr>
			<tr>
				<td>Basic salary</td>
				<td align='right'>".CUR." $basic_sal</td>
			</tr>
			$comDis
			$fringes_desc
			$all_before
			$de_before
			$groDis
			$talDis
			<tr>
				<td>UIF</td>
				<td align='right'>".CUR." $emp_uif</td>
			</tr>
			<tr>
				<td>PAYE</td>
				<td align='right'>".CUR." $paye</td>
			</tr>
			$loaDis
			$all_after
			$de_after
			<tr>
				<td><b>Nett Pay</b></td>
				<td align='right'><b>".CUR." $nettpay</b></td>
			</tr>
			</form>
		</table>
		</center>";

	$parkagesave = "
		<br><br>
		<center>
		<table border='2' width='750' border=2 cellpadding='4' cellspacing='0' bordercolor='#000000'>
			<tr>
				<td align='center'><b>Description</b></td>
				<td width='100' align='center'><b>Amount</b></td>
			</tr>
			<tr>
				<td>Basic salary</td>
				<td align='right'>".CUR." $basic_sal</td>
			</tr>
			$comDis
			$fringes_desc
			$all_before
			$de_before
			$groDis
			$talDis
			<tr>
				<td>UIF</td>
				<td align='right'>".CUR." $emp_uif</td>
			</tr>
			<tr>
				<td>PAYE</td>
				<td align='right'>".CUR." $paye</td>
			</tr>
			$loaDis
			$all_after
			$de_after
			<tr>
				<td><b>Nett Pay</b></td>
				<td align='right'><b>".CUR." $nettpay</b></td>
			</tr>
			</form>
		</table>
		</center>";

	$OUTPUT = $parkage;

	$save = base64_encode($parkagesave);

	$Date = $date;

	$np = $nettpay;

	if(isset($rbsa)) {
		$np = sprint($np- array_sum($rbsa));
	}

	if (empty($novert)) {
    	$novert = "0";
    }

	if (empty($hovert)) {
		$hovert = "0";
	}

	$Sl = "
		INSERT INTO cubit.salr (
			empnum, month, bankid, salary, comm, uifperc, uif, payeperc, paye, totded, totded_employer, 
			totallow, loanins, tot_fringe, div, display, saldate, week, cyear, novert, 
			hovert, taxed_sal, hours, salrate, bonus
		) VALUES (
			'$empnum', '$mon', '$accid', '$np', '$commission', '0', '$emp_uif', '0', '$paye', '$totded', '$totded_employer', 
			'$totall', '$loaninstall', '$fringe_tot', '".USER_DIV."', '$save','$Date','$week', '".EMP_YEAR."', '$novert', 
			'$hovert', '$paye_salary', '$multi','$basic_sal_save', '$abonus'
		)";
	$Ry = db_exec($Sl) or errDie("Unable to insert record.");

	$id = -pglib_lastid("salr", "id");

	$year = $year;

	$payslip_id = $id;

	db_conn("cubit");

	$Sl = "SELECT * FROM cubit.rbs ORDER BY name";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$i = 0;

	if(pg_num_rows($Ri) > 0) {
		while($td = pg_fetch_array($Ri)) {
			if(!isset($rbsa[$td['id']])||$rbsa[$td['id']]<1) {
				continue;
			}

			$rbsa[$td['id']] = sprint($rbsa[$td['id']]);

			$rb = $rbsa[$td['id']];

			$i++;

			db_conn('cubit');

			$sql = "
				INSERT INTO emp_inc (
					emp, year, period, week, date, payslip, type, code, description, qty, 
					rate, amount, ex
				) VALUES (
					'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$payslip_id', '$td[id]', '', '$td[name]', '1', 
					'0', '-$rb', 'RBS'
				)";
			db_exec($sql) or errDie("unable to insert data.");

			$sql = "UPDATE employees SET balance=balance-($rb) WHERE empnum = '$empnum'";
			db_exec($sql) or errDie("Unable to get employee details.");

			empledger($empnum, $td['account'], $date, $refnum,"Reimbursement" , $rb, "d");
			writetrans($salconacc, $td['account'], $date, $refnum, $rb, "Reimbursement for employee, $myEmp[fnames] $myEmp[sname].");
		}
	}

	if (isset($subsname)) {
		foreach ($subsname as $sid => $sn) {
			if(empty($subsamt[$sid]) || $subsamt[$sid] <= 0) {
				continue;
			}

			$samt = sprint($subsamt[$sid]);

			$i++;

			db_conn('cubit');
			$cols = grp(
				m("emp", $empnum),
				m("year", EMP_YEAR),
				m("period", $mon),
				m("week", $week),
				m("date", $Date),
				m("payslip", $payslip_id),
				m("type", $sid),
				m("code", ""),
				m("description", $subsname[$sid]),
				m("qty", 1),
				m("rate", 0),
				m("amount", -$samt),
				m("ex", "SUBS")
			);
			$subin = new dbUpdate("emp_inc", "cubit", $cols);
			$subin->run(DB_INSERT);

			$cols = grp(
				m("balance", raw("balance-($samt)"))
			);
			$subin->setTable("employees");
			$subin->setOpt($cols, wgrp(
				m("empnum", $empnum)
			));
			$subin->run(DB_UPDATE);

			empledger($empnum, $subsacc[$sid], $date, $refnum, "Subsistence Allowance: $subsname[$sid]" , $samt, "d");
			writetrans($salconacc, $subsacc[$sid], $date, $refnum, $samt, "Subsistence Allownace ($subsname[$sid]) for employee, $myEmp[fnames] $myEmp[sname].");
		}
	}

	if($myEmp['paytype'] == "Cash") {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance+($paidamount) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $cash_account, $date, $refnum,"Payment(Cash)" ,  $paidamount, "c");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		# Debit salaries control acc and credit Bank acc
		writetrans($cash_account, $salconacc, $date, $refnum, $paidamount, "Salary Payment(Cash) for employee,  $myEmp[fnames] $myEmp[sname].");

	} elseif($myEmp['paytype'] == "Ledger Account") {

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance+($paidamount) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $account, $date, $refnum,"Payment(Ledger Account)" ,  $paidamount, "c");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		# Debit salaries control acc and credit Bank acc
		writetrans($account, $salconacc, $date, $refnum, $paidamount, "Salary Payment(Ledger Account) for employee,  $myEmp[fnames] $myEmp[sname].");

	} else {

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance+($paidamount) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $bankacc, $date, $refnum, "Payment(Bank)" ,  $paidamount, "c");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		# Debit salaries control acc and credit Bank acc
		writetrans($bankacc, $salconacc, $date, $refnum, $paidamount, "Salary Payment for employee(Bank),  $myEmp[fnames] $myEmp[sname].");

		# issue bank record
		banktrans($accid, "deposit", $date, "$myEmp[fnames] $myEmp[sname]", "Salary Payment for employee,  $myEmp[fnames] $myEmp[sname]", 0, $paidamount, $salconacc,$myEmp['empnum']);
	}

	db_conn('cubit');

	/*
	writetrans($uifexp,$uifbal , $date, $refnum, $uif, "Company UIF Contribution,  $myEmp[fnames] $myEmp[sname].");
	*/
	# Debit uif sdl and credit sdl control acc
	//	writetrans($sdlexp,$sdlbal , $date, $refnum, $sdl, "SDL,  $myEmp[fnames] $myEmp[sname].");

	db_conn("cubit");

	if( $comp_uif > 0 ) {
		$Sl = "
			INSERT INTO emp_com (
				emp, year, period, week, date, payslip, type,code, description, qty, rate, amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'UIFC', '', 'UIF', '1', '0', '-$comp_uif'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data1.");
	}

	if ( $emp_uif > 0 ) {
		$Sl = "
			INSERT INTO emp_ded (
				emp, year, period, week, date, payslip, type, code, description, qty, rate, amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'UIFE', '', 'UIF', '1', '0', '-$emp_uif'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data3.");
	}

	if($sdl > 0) {
		$Sl = "
			INSERT INTO emp_com (
				emp, year, period, week, date, payslip, type, code, description, qty, rate, amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'SDL', '', 'SDL', '1', '0', '-$sdl'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data2.");
	}

	if($paye > 0) {
		$Sl = "
			INSERT INTO emp_ded (
				emp, year, period, week, date, payslip, type, code, description, qty, rate, amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'PAYE', '', 'PAYE', '1', '0', '-$paye'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data3.");
	}

	if($basic_sal > 0) {
		$Sl = "
			INSERT INTO emp_inc (
				emp, year, period, week, date, payslip, type, code, description, pension, qty, 
				rate, amount, ex
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'INC', '', 'Basic Salary', '', '1', 
				'0', '-$basic_sal', ''
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data4.");
	}

	if ( $myEmp["loanpayslip"] > 0 ) {
		$Sl = "
			INSERT INTO emp_inc (
				emp, year, period, week, date, payslip, type, code, description, pension, qty, rate, 
				amount, ex
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'LOAN', '', 'Employee Loan', '', '1', '0', 
				'-$myEmp[loanpayslip]', ''
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert loan data for employee income on payslip.");

		$sql = "UPDATE employees SET loanpayslip='0' WHERE empnum='$empnum'";
		$rslt = db_exec($sql) or errDie("Error updating loan information for payslip.");
	}

	if($bonus > 0 && $myEmp["payprd"] != "f" && $myEmp["payprd"] != "w") {
		$Sl = "
			INSERT INTO emp_inc (
				emp, year, period, week, date, payslip, type, code, description, pension, qty, rate, amount, ex
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'INCB', '', 'Bonus', '', '1', '0', '-$bonus', ''
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data5.");
	} else if ( $bonus > 0 ) {
		$Sl = "
			INSERT INTO emp_inc (
				emp, year, period, week, date, payslip, type, code, description, 
				pension, qty, rate, amount, ex
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'INCB', '', 'Special Bonus/Additional Salary', 
				'', '1', '0', '-$bonus', ''
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data5.");
	}

	if($annual > 0) {
		$Sl = "
			INSERT INTO emp_inc (
				emp, year, period, week, date, payslip, type, code, description, pension, qty, rate, 
				amount, ex
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'INCAB', '', 'Annual Bonus', '', '1', '0', 
				'-$annual', ''
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data5.");
	}

	if($commission > 0) {
		$Sl = "
			INSERT INTO emp_inc (
				emp, year, period, week, date, payslip, type, code, description, pension, qty, rate, 
				amount, ex
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'INCC', '', 'Commission', '', '1', '0', 
				'-$commission', ''
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data6.");
	}

	if($abonus > 0) {
		$Sl = "
			INSERT INTO emp_inc (
				emp, year, period, week, date, payslip, type, code, description, pension, qty, rate, 
				amount, ex
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'INCAB', '', 'Bonus', '', '1', '0', 
				'-$abonus', ''
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data6.");
	}

	if($all_travel > 0) {
		$Sl = "
			INSERT INTO emp_inc (
				emp, year, period, week, date, payslip, type, code, description, pension, qty, 
				rate, amount, ex
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'INCT', '', 'Travel Allowance', '', '1', 
				'0', '-$all_travel', ''
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data7.");
	}

	if($loaninstall > 0) {
		$Sl = "
			INSERT INTO emp_ded (
				emp, year, period, week, date, payslip, type, code, description, qty, rate, 
				amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'DEDL', '', 'Loan Repayment', '1', '0', 
				'-$loaninstall'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data8.");
	}

	if ( $comp_pension > 0 ) {
		$Sl = "
			INSERT INTO emp_com (
				emp, year, period, week, date, payslip, type, code, description, qty, rate, 
				amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'COMP', '', 'Pension', '1', '0', 
				'-$comp_pension'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data9.");
	}

	if ( $emp_pension > 0 ) {
		$Sl = "
			INSERT INTO emp_ded (
				emp, year, period, week, date, payslip, type, code, description, qty, rate, 
				amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'DEDP', '', 'Pension', '1', '0', 
				'-$emp_pension'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data10.");
	}

	if($comp_ret > 0) {
		$Sl = "
			INSERT INTO emp_com (
				emp, year, period, week, date, payslip, type, code, description, qty, 
				rate, amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'COMR', '', 'Retirement Annuity Fund', '1', 
				'0', '-$comp_ret'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data9.");
	}

	if($emp_ret > 0) {
		$Sl = "
			INSERT INTO emp_ded (
				emp, year, period, week, date, payslip, type, code, description, qty, 
				rate, amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'DEDR', '', 'Retirement Annuity Fund', '1', 
				'0', '-$emp_ret'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data10.");
	}

	if ( $myEmp["fringe_car1_contrib"] > 0 ) {
		$Sl = "
			INSERT INTO emp_ded (
				emp, year, period, week, date, payslip, type, code, description, 
				qty, rate, amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'DEDA', '', 'Motorcar 1 Contribution for Use', 
				'1', '0', '-$myEmp[fringe_car1_contrib]'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data10.");
	}

	if ( $myEmp["fringe_car2_contrib"] > 0 ) {
		$Sl = "
			INSERT INTO emp_ded (
				emp, year, period, week, date, payslip, type, code, description, 
				qty, rate, amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'DEDB', '', 'Motorcar 2 Contribution for Use', 
				'1', '0', '-$myEmp[fringe_car2_contrib]'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data10.");
	}

	if ($comp_medical > 0) {
		$Sl = "
			INSERT INTO emp_com (
				emp, year, period, week, date, payslip, type, code, description, qty, 
				rate, amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'COMM', '', 'Medical Contribution', '1', 
				'0', '-$comp_medical'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data.11");
	}

	if($emp_medical > 0) {
		$Sl = "
			INSERT INTO emp_ded (
				emp, year, period, week, date, payslip, type, code, description, qty, 
				rate, amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'DEDM', '', 'Medical Contribution', '1', 
				'0', '-$emp_medical'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data.12");
	}

	if($comp_provident > 0) {
		$Sl = "
			INSERT INTO emp_com (
				emp, year, period, week, date, payslip, type, code, description, qty, rate, 
				amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'COMV', '', 'Provident', '1', '0', 
				'-$comp_provident'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data9.");
	}

	if($emp_provident > 0) {
		$Sl = "
			INSERT INTO emp_ded (
				emp, year, period, week, date, payslip, type, code, description, qty, rate, 
				amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'DEDV', '', 'Provident', '1', '0', 
				'-$emp_provident'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data10.");
	}

	if($comp_other > 0) {
		$Sl = "
			INSERT INTO emp_com (
				emp, year, period, week, date, payslip, type, code, description, qty, 
				rate, amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'COMO', '', 'Other Deductions', '1', 
				'0', '-$comp_other'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data9.");
	}

	if($emp_other > 0) {
		$Sl = "
			INSERT INTO emp_ded (
				emp, year, period, week, date, payslip, type, code, description, qty, 
				rate, amount
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'DEDO', '', 'Other Deductions', '1', 
				'0', '-$emp_other'
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data10.");
	}

	if($overamt > 0) {
		$Sl = "
			INSERT INTO emp_inc (
				emp, year, period, week, date, payslip, type, code, description, qty, rate, 
				amount, ex
			) VALUES (
				'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$id', 'INCO', '', 'Over Time', '1', '0', 
				'-$overamt', ''
			)";
		$Ri = db_exec($Sl) or errDie("unable to insert data.13");
	}

	$payslip_id = $id;

	$frinupd = new dbUpdate("emp_frin", "cubit");
	if (isset($fringebens)) {
		foreach ($fringebens as $key => $value) {
			$cols = grp(
				m("emp", $empnum),
				m("year", EMP_YEAR),
				m("period", $mon),
				m("week", $week),
				m("fdate", $Date),
				m("payslip", $payslip_id),
				m("code", $key),
				m("description", sprint($fringename[$key])),
				m("qty", 1),
				m("amount", -$fringebens[$key])
			);

			$frinupd->setCols($cols);
			$frinupd->run(DB_INSERT);
		}
	}

	if ($fringe_loan > 0) {
		$cols = grp(
			m("emp", $empnum),
			m("year", EMP_YEAR),
			m("period", $mon),
			m("fdate", $Date),
			m("payslip", $payslip_id),
			m("code", "FRINLOAN"),
			m("description", "Loan Fringe Benefit"),
			m("qty", 1),
			m("amount", -$fringe_loan)
		);

		$frinupd->setCols($cols);
		$frinupd->run(DB_INSERT);
	}

	if ($fringe_medical > 0) {
		$cols = grp(
			m("emp", $empnum),
			m("year", EMP_YEAR),
			m("period", $mon),
			m("fdate", $Date),
			m("payslip", $payslip_id),
			m("code", "FRINMED"),
			m("description", "Medical Fringe Benefit"),
			m("qty", 1),
			m("amount", -$fringe_medical)
		);

		$frinupd->setCols($cols);
		$frinupd->run(DB_INSERT);
	}

	if ($fringe_car1 > 0) {
		$cols = grp(
			m("emp", $empnum),
			m("year", EMP_YEAR),
			m("period", $mon),
			m("fdate", $Date),
			m("payslip", $payslip_id),
			m("code", "FRINCAR1"),
			m("description", "Fringe Benefit: Vehicle 1"),
			m("qty", 1),
			m("amount", -$fringe_car1)
		);

		$frinupd->setCols($cols);
		$frinupd->run(DB_INSERT);
	}

	if ($fringe_car2 > 0) {
		$cols = grp(
			m("emp", $empnum),
			m("year", EMP_YEAR),
			m("period", $mon),
			m("fdate", $Date),
			m("payslip", $payslip_id),
			m("code", "FRINCAR2"),
			m("description", "Fringe Benefit: Vehicle 2"),
			m("qty", 1),
			m("amount", -$fringe_car2)
		);

		$frinupd->setCols($cols);
		$frinupd->run(DB_INSERT);
	}

	if(isset($allowid)){
		$Sl = "SELECT id,allowance FROM allowances";
		$Ri = db_exec($Sl) or errDie("Unable to get allowances.");

		while( $data = pg_fetch_array($Ri) ) {
			$allname[$data['id']] = $data['allowance'];
		}

		foreach( $allowid as $i => $id ) {
			$aname = $allname[$allowid[$i]];

			if ( ($allowances[$i] = sprint($allowances[$i])) <= 0 ) continue;

			$Sl = "
				INSERT INTO emp_inc (
					emp, year, period, week, date, payslip, type, code, description, 
					qty, rate, amount, ex
				) VALUES (
					'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$payslip_id', '$allowid[$i]', '', '$aname', 
					'1', '0', '-$allowances[$i]', ''
				)";
			$Ri = db_exec($Sl) or errDie("unable to insert data.");
		}
	}

	# Pay Deductions accounts
	if ( isset($deductid) ) {
		$Sl = "SELECT id,deduction FROM salded";
		$Ri = db_exec($Sl) or errDie("Unabel to get get dat.");

		while($data = pg_fetch_array($Ri)) {
			$dnames[$data['id']] = $data['deduction'];
		}

		foreach($deductid as $i => $id){
			$dname = $dnames[$deductid[$i]];

			# Debit salaries control acc and credit  acc
			if ( ($deductions[$i] = sprint($deductions[$i])) > 0 ) {
				$Sl = "
					INSERT INTO emp_ded (
						emp, year, period, week, date, payslip, type, code, description, 
						qty, rate, amount
					) VALUES (
						'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$payslip_id', '$deductid[$i]', '', '$dname', 
						'1', '0', '-$deductions[$i]'
					)";
				$Ri = db_exec($Sl) or errDie("unable to insert data.");
			}

			if ( ($employer_deductions[$i] = sprint($employer_deductions[$i])) > 0 ) {
				$Sl = "
					INSERT INTO emp_com (
						emp, year, period, week, date, payslip, type, code, description, 
						qty, rate, amount
					) VALUES (
						'$empnum', '".EMP_YEAR."', '$mon', '$week', '$Date', '$payslip_id', '$deductid[$i]', '', '$dname', 
						'1', '0', '-$employer_deductions[$i]'
					)";
				//$Ri=db_exec($Sl) or errDie("unable to insert data1.");
			}
		}
	}

	$id = $payslip_id;

	$ecost += 0;

	db_conn('cubit');

	$Sl = "SELECT * FROM empc WHERE emp='$empnum'";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) > 0) {
		while($data = pg_fetch_array($Ri)) {
			db_conn('cubit');
			$sql = "SELECT * FROM costcenters WHERE ccid = '$data[cid]'";
			$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
			$cc = pg_fetch_array ($ccRslt);

			$amount=sprint($ecost*$data['amount']/100);

			db_conn(PRD_DB);
			$sql = "
				INSERT INTO cctran (
					ccid, trantype, typename, edate, description, 
					amount, username, div
				) VALUES (
					'$cc[ccid]', 'ct', 'Salary', '$Date', 'Salary for employee,  $myEmp[fnames] $myEmp[sname]', 
					'$amount', '".USER_NAME."', '".USER_DIV."'
				)";
			$insRslt = db_exec ($sql) or errDie ("Unable to retrieve insert Cost center amounts into database.");
		}
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	//if (getCSetting("EMP_PRINTSLIP") != "n") {
		$OUTPUT = "<script>printer('salwages/payslip-print.php?id=$id&rev=true');spmove('../main.php');</script>";
	/*} else {
		$OUTPUT = "
		<h3>Process Employee Salary</h3>
		Successfully processed salary.<br /><br />"
		.mkQuickLinks(
			ql("salaries-staff.php", "Process Employee Salary"),
			ql("../admin-employee-view.php", "View Employees/Process Salaries by Batch"),
			ql("../admin-employee-add.php", "Add New Employee"),
			ql("settings-acc-edit.php", "Salary Settings")
		);
	}*/
	require("../template.php");

}



function block_check($acc, $debug = false)
{

	global $global_empnum;
	global $block_check_errs, $block_check_accs;

	if ( (! isset($block_check_accs[$acc])) && isb($acc) ) {
		$block_check_accs[$acc] = 1;
		$sql = "SELECT accname FROM core.accounts WHERE accid='$acc'";
		$rslt = db_exec($sql) or errDie("Error reading account name for blocked account.");

		$accname = pg_fetch_result($rslt, 0, 0);

		$block_check_errs .= "<li class='err'>$accname is a blocked account. Click
			<a href='empacc-link.php?empnum=$global_empnum'>here</a> to change the
			account for as the 'Salaries and Wages' account before you continue
			 with reversing salaries.</li>";

		return false;
	}
	return true;

}



function finish_block_check()
{

	global $block_check_errs;

	if ( empty($block_check_errs) ) return;

	$OUTPUT = "<h3>Reverse Employee Salary</h3>$block_check_errs";
	require("../template.php");

}



function banktrans($bankacc, $trantype, $date, $name, $details, $cheqnum, $amount, $accinv,$empnum)
{

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($bankacc, "num", 1, 50, "Invalid Bank Account number.");
	$v->isOk ($trantype, "string", 1, 50, "Invalid Transaction type.");
	$v->isOk ($date, "date", 1, 14, "Invalid Bank Transaction date.");
	$v->isOk ($name, "string", 1, 50, "Invalid Name.");
	$v->isOk ($details, "string", 0, 255, "Invalid Bank Transacton details.");
	$v->isOk ($cheqnum, "num", 0, 50, "Invalid Bank Transacton cheque number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Bank Transacton Amount.");
	$v->isOk ($accinv, "num", 1, 20, "Invalid Bank Transaction account involved.");

	# display errors, if any
	if ($v->isError ()) {
		$write = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class='err'>".$e["msg"]."</li>";
		}
		$OUTPUT = $write."<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		require("../template.php");
	}



	# record the payment record
	db_connect();

	$sql = "
		INSERT INTO cashbook (
			bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, 
			div, fcid, empnum
		) VALUES (
			'$bankacc', '$trantype', '$date', '$name', '$details', '$cheqnum', '$amount', 'no', '$accinv', 
			'".USER_DIV."', (SELECT fcid FROM cubit.currency WHERE curcode='ZAR'), '$empnum'
		)";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

}


?>
