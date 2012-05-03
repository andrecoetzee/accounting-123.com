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
require("payprdmsg.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = "Invalid.";
	}
}elseif(isset($_POST["all"])) {
	if (isset($_POST['emp_group'])) 
		header("Location: ../admin-employee-view.php?all=yes&emp_group=$_POST[emp_group]");
	else 
		header("Location: ../admin-employee-view.php?all=yes");
	exit;
} elseif(isset($_POST["emps"])) {
	$OUTPUT = enter($_POST);
} elseif(isset($_POST['emp_group'])){
	header ("Location: ../admin-employee-view.php?emp_group=$_POST[emp_group]");
	exit;
} else {
	$OUTPUT = "<li class='err'>Please select at least one employee.</li>";
}

$OUTPUT.=mkQuickLinks(
	ql("salaries-staff.php", "Process Employee Salary"),
	ql("../admin-employee-view.php", "View Employees/Process Salaries by Batch"),
	ql("../admin-employee-add.php", "Add New Employee"),
	ql("settings-acc-edit.php", "Salary Settings")
);


require ("../template.php");






function enter ($_POST, $err = "")
{

	extract($_POST);

	global $PRDMON, $MONPRD;
	$salyr = getYearOfEmpMon($month);
	$curyr = getActiveFinYear();
	if ($salyr > $curyr || ($salyr == $curyr && $month > $PRDMON[12])) {
		header("Location: ../admin-employee-view.php?err=Cannot do transaction in future financial year. You need to close your year first before you can continue.&".array2get($_POST));
		exit;
	}
	
	if ((!isset($emps)) || (!is_array($emps))) {
		header("Location: ../admin-employee-view.php?err=Please select at least one employee.");
		exit;
	}
	
	// first check if all the selected employees with EFT pay types have banking information
	// and their id numbers are valid
	$emp_err = array();
	$emp_nam = array();
	foreach ($emps as $e_empnum => $e_val) {
		/* previously selected to remove this employee from process */
		if (isset($rememp[$e_empnum])) {
			unset($emps[$e_empnum]);
			continue;
		}

		$sql = "
			SELECT fnames, sname, paytype, bankname, bankaccno, idnum, flag, hiredate, payprd 
			FROM employees 
			WHERE div='".USER_DIV."' AND empnum='$e_empnum'";
		$rslt = db_exec($sql) or errDie("error checking employee payment types.");
		$e_info = pg_fetch_array($rslt);

		/* if the employee's pay period doesn't match the selected button, don't
			include employee in checklist */
		if (isset($d) && $e_info["payprd"] != "d") {
			continue;
		} else if (isset($w) && $e_info["payprd"] != "w") {
			continue;
		} else if (isset($b) && $e_info["payprd"] != "f") {
			continue;
		} else if (isset($m) && $e_info["payprd"] != "m") {
			continue;
		}

		$e_empnum += 0;
		$emp_err[$e_empnum] = 0;

		$emp_nam[$e_empnum] = "$e_info[fnames] $e_info[sname]";

		if ( $e_info["paytype"] == "EFT" && (empty($e_info["bankname"]) || empty($e_info["bankaccno"])) ) {
			$emp_err[$e_empnum] |= 0x01;
		}

		if ( ! empty($e_info["idnum"]) ) {
			$bd_year = substr($e_info["idnum"], 0, 2);
			$bd_month = substr($e_info["idnum"], 2, 2);
			$bd_day = substr($e_info["idnum"], 4, 2);

			if ( ! checkdate($bd_month, $bd_day, $bd_year) ) {
				$emp_err[$e_empnum] |= 0x02;
			}
		}

		if ( $e_info["flag"] == "2.5EMP" ) {
			$emp_err[$e_empnum] |= 0x04;
			$special_error = 0x01;
		}

		if ($e_info["flag"] == "272PREVEMP") {
			$emp_err[$e_empnum] |= 0x04;
			$special_error = 0x02;
		}

		/* check hiredate after process date */
		explodeDate($e_info["hiredate"], $hd_year, $hd_month, $hd_day);
		$MONempyear = getYearOfEmpMon($month);
		if ($hd_year > $MONempyear || ($hd_year == $MONempyear && $hd_month > $month)) {
			$emp_err[$e_empnum] |= 0x08;
		}
	}

	// list the employee information problems
	if (array_sum($emp_err) > 0) {
		$out = "
			<h3>Batch Salaries</h3>
				<form method='POST' action='".SELF."'>";

		foreach ( $_POST as $key => $value ) {
			if ( is_array($value) ) {
				foreach ( $value as $akey => $avalue ) {
					$out .= "<input type='hidden' name='$key"."[$akey]' value='$avalue'>";
				}
			} else {
				$out .= "<input type='hidden' name='$key' value='$value'>";
			}
		}

		if (isset($special_error)) {
			switch ($special_error) {
				case 0x01:
					$out .= "
					<li class='err'><strong>NOTICE:</strong> Due to changes in employee functionality from Cubit 2.5 to Cubit 2.6 <br>
						you need to edit your employees' salary/deduction/allowance information</li>
						<br />";
					break;
				case 0x02:
					$out .= "
					<li class='err'><strong>NOTICE:</strong> Due to the changes from Cubit 2.71 to Cubit 2.72 you should first update your employee's
						previous employment information in the employee edit form.</li>
						<br />";
					break;
			}
		}

		$out .= "
			<table ".TMPL_tblDflts.">
				<tr>
					<td colspan='3' class='err'>There are problems with the following employees.<br />
						Edit their information or to remove an employee from the process select
						the checkbox next to employee's name.<br /><br />
						Click the 'Done' button when ready to proceed.</td>
				</tr>";

		$out .= "
			<tr>
				<td>&nbsp;</td>
				<th>Name</th>
				<th>Message</th>
			</tr>";

		$i = 0;
		foreach ($emp_err as $e_empnum => $err_val) {
			$out .= "
				<tr bgcolor='".bgcolor($i)."'>
					<td><input type='checkbox' name='rememp[$e_empnum]' /></td>
					<th>$emp_nam[$e_empnum]</td>";

			if ($err_val & 0x04) {
				$specerr_msg = "(See above notice for this employee)";
			} else {
				$specerr_msg = "";
			}

			if ( ($err_val & 0x01) && ($err_val & 0x02) ) {
				$out .= "<td class='err'>Banking info and ID number $specerr_msg</td>";
			} else if ( $err_val & 0x01 ) {
				$out .= "<td class='err'>Banking info $specerr_msg</td>";
			} else if ( $err_val & 0x02 ) {
				$out .= "<td class='err'>ID number $specerr_msg</td>";
			} else if ($err_val & 0x08) {
				$out .= "<td class='err'>Employee was not employed in the period
					requested $specerr_msg</td>";
			} else {
				$out .= "<td>Employee Info Correct $specerr_msg</td>";
			}

			if ($err_val && !($err_val == 0x08)) {
				$out .= "<td class='err'><a target='_blank' href='../admin-employee-edit.php?empnum=$e_empnum'>Edit Employee</a></td>";
			}

			$out .= "</tr>";
		}

		$out .= "
				<tr>
					<td colspan='3' align='right'><input type='submit' value='Done' /></td>
				</tr>
			</table>
			</form>";
		return $out;

	}

	if(!isset($date_day)) {
		$date_day = date("d");
		$date_month = date("m");
		$date_year = date("Y");
	}

	if(!isset($date_month)) {
		$date_month = date("m");
	}

	/* make week/day selections */
	if (isset($w)) {
		$weekends = "";
		$weeks = "<select name='week'>";

		$stdate = mktime(0, 0, 0, $month, 1, DATE_YEAR);
		$endate = mktime(0, 0, 0, $month, DATE_DAYS, DATE_YEAR);

		$i = 1;
		while ($stdate <= $endate) {
			if (date("w", $stdate) == 5) {
				$weekends .= "<input type='hidden' name='weekends[$i]' value='".date("j", $stdate)."' />";

				if (isset($week) && $week == $i) {
					$sel = "selected";
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

		$weeks = "
			<th>Week</th>
			<td>$weekends$weeks</td>
			<td class='err'>Period salaries are processed for</td>";
	} else if (isset($b)) {
		$weekends = "";
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
		$endate = mktime(0, 0, 0, $month + 1, 0, getYearOfEmpMon($month));

		/* count weeks from start of tax year */
		$i = 1;
		$c = 0;
		while ($stdate <= $endate) {
			if (date("m", $stdate) == $month && date("Y", $stdate) == getYearOfEmpMon($month)) {
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

		$weeks .= "
		</select>";

		$weeks = "
			<th>Week</th>
			<td>$weekends$weeks</td>
			<td class='err'>Period salaries are processed for</td>";
	} else if (isset($d)) {
		$x = date("t", mktime(0, 0, 0, $month, 1, getYearOfFinMon($month)));
		$MONstr = getMonthNameS($month);

		if (!isset($proc_day)) {
			$proc_day = 0;
		}

		if (!isset($pday)) {
			$pday = $proc_day;
		}

		$days = "<select name='pday'>";
		for ($i = 1; $i <= $x; ++$i) {
			if ($i == $pday) {
				$sel = "selected='t'";
			} else {
				$sel = "";
			}

			$days .= "<option $sel value='$i'>$i $MONstr</option>";
		}
		$days .= "</select>";

		$weeks = "
			<th>Day for Payment</th>
			<td>$days</td>
			<td class='err'>Period salaries are processed for</td>";
	} else {
		$weeks = "<input type='hidden' name='week' value='0'>";
	}

	/* payprd message */
	if (isset($d)) {
		$cpayprd = "d";
	} else if (isset($w)) {
		$cpayprd = "w";
	} else if (isset($b)) {
		$cpayprd = "f";
	} else if (isset($m)) {
		$cpayprd = "m";
	} else {
		invalid_use("Invalid payment type selected.");
	}

	$dispmsg = getCSetting("EMP_SALMSG");

	if (strpos($dispmsg, $cpayprd) === false) {
		$payprd_msg_ch = "";
	} else {
		$payprd_msg_ch = "checked='t'";
	}

	$payprd_msg = get_payprdmsg($cpayprd);

	/* print payslip on/off */
	$printslip = getCSetting("EMP_PRINTSLIP");

	$out = "
		<script>
			function update_salmsg(obj) {
				ajaxRequest('payprdmsg.php', 'payprd_msg', AJAX_SET,'payprd=$cpayprd&newval=' + obj.checked);
			}
		</script>
		<form action='".SELF."' method='POST' id='salfrm'>
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='month' value='$month' />
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='9'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td><h3>Processing Batch Salaries</h3></td>
						</tr>
						<tr>
							<td>
								<li class='err'>NOTE that Cubit is configured to compute employees' tax
									during the employees' tax year that<br />
									starts in March and ends in February,
									irrespective of the employer's financial year end.</li>
							</td>
						<tr>
							<td id='payprd_msg' colspan='2'>$payprd_msg</td>
						</tr>
						<tr>
							<th align='right'>Salary Help Message: <input type='checkbox' onclick='update_salmsg(this);' name='payprd_dispmsg' $payprd_msg_ch /></th>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan='6' valign='top' rowspan='2'>
					<table ".TMPL_tblDflts.">
					<tr class='".bg_class()."'>
						$weeks
					</tr>
					<tr class='".bg_class()."'>
						<th>Processing Date:</th>
						<td nowrap>".mkDateSelect("date",$date_year,$date_month,$date_day)."</td>
						<td colspan='2' class='err'>Date used by Cubit for the ledgers.</td>
					</tr>
					<tr class='".bg_class()."'>
						<th nowrap='t'>Print Salary Advice</th
						<td>
						<select name='printslip'>
							<option value='y' ".($printslip!="n"?"selected='t'":"").">Yes</option>
							<option value='n' ".($printslip=="n"?"selected='t'":"").">No</option>
						</select>
						</td>
					</tr>
					
					</table>
				</td>
				<td colspan='3' align='right' nowrap='t'>
					<input type='submit' name='btn_back' value='&laquo; Correction'>
					<input type='submit' value='Confirm &raquo;'>
				</td>
				<td colspan='10' align='right' nowrap='t'>
					<input type='submit' name='btn_back' value='&laquo; Correction'>
					<input type='submit' value='Confirm &raquo;'>
				</td>
			</tr>
			<tr>
				<!--<td align='center' colspan='3' class='err'>An amount entered here (Special Bonus/Additional
					Salary) will be treated as a recurring bonus/payment per pay period for PAYE
					purposes, the amount will not be treated as an annual payment. If the
					amount paid as a bonus is a once off/annual payment please use the
					Bonus(Annual Payments) option. In other cases PAYE has to be manually
					adjusted <u>per directive</u> from SARS when processing salary.</td>-->
				<td colspan='3'>&nbsp;</td>
				<td colspan='3' class='err'><strong>LOAN NOTE:</strong><br />In the event that the employee repays more than the installment -
					enter that amount, plus interest that is remitted, in the \"Loan Repayment\" field below.
				</td>
			</tr>
			<tr>
				<td colspan='10'>$err</td>
			</tr>
			<tr>
				<th>Nr.</th>
				<th>Name</th>
				<th>Remuneration</th>";

	if (!isset($d)) {
		$out .= "
			<th>Total Work Hours</th>
			<th>Actual Hours Worked</th>";
	}

	$out .= "
			<th>Normal Overtime</th>
			<th>Public Holiday Overtime</th>
			<th>Annual Bonus</th>
			<!--
			<th>Special Bonus/Additional Salary</th>
			<th>Annual/Once Off Bonus</th>
			-->
			<th>Commission</th>
			<th>Travel Allowance</th>
			<th>Loan Repayment</th>
			<th>Pension: Company Contribution</th>
			<th>Pension: Employee Deduction</th>
			<th>Provident: Company Contribution</th>
			<th>Provident: Employee Deduction</th>
			<!--
			<th>UIF: Company Contribution</th>
			<th>UIF: Employee Deduction</th>
			//-->
			<th>Retirement Annuity: Company Contribution</th>
			<th>Retirement Annuity: Employee Deduction</th>
			<th>Medical Contribution: Company</th>
			<th>Medical Contribution: Employee</th>
			<!--
			<th>Other: Company Contribution</th>
			<th>Other: Employee Deduction</th>
			//-->
			<th>Method of Payment</th>
	 		<th>Override PAYE</th>
	 		<th>Fringe Ben.</th>
	 		<th>Allowances</th>
	 		<th>Subsistence</th>
	 		<th>Deductions</th>
	 		<th>Reimbursements</th>
		</tr>";

	db_conn('cubit');

	$i = 0;

	$Sl = "SELECT * FROM employees WHERE div='".USER_DIV."' ORDER BY sname,fnames";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$jsbonus_clear = Array();
	$uif_calc = Array();
	$jsbonus = Array(
		1 => Array(),
		2 => Array(),
		3 => Array(),
		4 => Array(),
		5 => Array(),
		6 => Array(),
		7 => Array(),
		8 => Array(),
		9 => Array(),
		10 => Array(),
		11 => Array(),
		12 => Array()
	);

	$js_workhours_fields = "";

	$subs_int = false; // whether there are internation subsistence allowances
	$counter = 0;
	while($data = pg_fetch_array($Ri)) {

		if ($counter == 8){
			#show headings...
			$out .= "
				<tr>
					<th>Nr.</th>
					<th>Name</th>
					<th>Remuneration</th>";

			if (!isset($d)) {
				$out .= "
					<th>Total Work Hours</th>
					<th>Actual Hours Worked</th>";
			}

			$out .= "
					<th>Normal Overtime</th>
					<th>Public Holiday Overtime</th>
					<th>Annual Bonus</th>
					<!--
					<th>Special Bonus/Additional Salary</th>
					<th>Annual/Once Off Bonus</th>
					-->
					<th>Commission</th>
					<th>Travel Allowance</th>
					<th>Loan Repayment</th>
					<th>Pension: Company Contribution</th>
					<th>Pension: Employee Deduction</th>
					<th>Provident: Company Contribution</th>
					<th>Provident: Employee Deduction</th>
					<!--
					<th>UIF: Company Contribution</th>
					<th>UIF: Employee Deduction</th>
					//-->
					<th>Retirement Annuity: Company Contribution</th>
					<th>Retirement Annuity: Employee Deduction</th>
					<th>Medical Contribution: Company</th>
					<th>Medical Contribution: Employee</th>
					<!--
					<th>Other: Company Contribution</th>
					<th>Other: Employee Deduction</th>
					//-->
					<th>Method of Payment</th>
			 		<th>Override PAYE</th>
			 		<th>Fringe Ben.</th>
			 		<th>Allowances</th>
			 		<th>Subsistence</th>
			 		<th>Deductions</th>
			 		<th>Reimbursements</th>
				</tr>";
					
			$counter = 0;
		}
		$counter++;


		if (!(isset($emps[$data['empnum']]))) {
			continue;
		}

		$bgcolor = bgcolorc($i);
		$send = "";

		$grossal = $data["basic_sal"] + $data["commission"] + ($data["all_travel"]/2) + $data["bonus"];

		if(isset($d)) {
			$send = "<input type='hidden' name='d' value=''>";
			if($data['payprd'] != "d") {
				continue;
			}
		} elseif(isset($w)) {
			$send = "<input type='hidden' name='w' value=''>";
			if($data['payprd'] != "w") {
				continue;
			}
		} elseif(isset($m)) {
			$send = "<input type='hidden' name='m' value=''>";
			if($data['payprd'] != "m") {
				continue;
			}
		} elseif(isset($b)) {
			$send = "<input type='hidden' name='b' value=''>";
			if($data['payprd'] != "f") {
				continue;
			}
		} else {
			continue;
		}

		/* set employee id */
		$id = $data['empnum'];

		/* calculate basic salary divisors and multipliers
		 * used for calculating deductions/allowances/etc. when the
		 * salary type and payment period differs in length
		 */
		switch ($data["saltyp"]) {
			case "h":
				$divisor = 1;
				switch ($data["payprd"]) {
					case "d":
						$multiplier = $data["hpweek"] / 5;
						break;
					case "w":
						$multiplier = $data["hpweek"];
						break;
					case "f":
						$multiplier = $data["hpweek"] * 2;
						break;
					case "m":
						$multiplier = $data["hpweek"] * 52 / 12;
						break;
				}
				break;

			case "m":
				$divisor = 1;
				switch ($data["payprd"]) {
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
				switch ($data["payprd"]) {
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
				switch ($data["payprd"]) {
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

		$bon_month = round($data["sal_bonus_month"]);

		$jsbonus_clear[] = 	"document.getElementById('salfrm').elements['bonus[$id]'].value='0.00';";

		$jsbonus[$bon_month][] = "document.getElementById('salfrm').elements['bonus[$id]'].value = '$data[sal_bonus]';";

/*		$uif_calc[] = "
			tmp_calc = parseFloat(document.getElementById('salfrm').elements['basic_sal[$id]'].value)
				+ parseFloat(document.getElementById('salfrm').elements['annual[$id]'].value)
				+ parseFloat(document.getElementById('salfrm').elements['all_travel[$id]'].value);
			tmp_calc_emp = tmp_calc * $data[emp_uif] / 100;
			tmp_calc_comp = tmp_calc * $data[comp_uif] / 100;
			tmp_calc_emp = tmp_calc_emp.toFixed(2);
			tmp_calc_comp = tmp_calc_comp.toFixed(2);
			document.getElementById('salfrm').elements['emp_uif[$id]'].value = tmp_calc_emp;
			document.getElementById('salfrm').elements['comp_uif[$id]'].value = tmp_calc_comp;";*/

		$db = Array(
			"comp_pension" 		=> $data["comp_pension"],
			"emp_pension"		=> $data["emp_pension"],
			"comp_provident"	=> $data["comp_provident"],
			"emp_provident"		=> $data["emp_provident"],
			"comp_uif" 			=> $data["comp_uif"],
			"emp_uif"			=> $data["emp_uif"],
			"comp_other"		=> $data["comp_other"],
			"emp_other"			=> $data["emp_other"]
		);

		if (isset($basic_sal[$id])) {
			$data['basic_sal'] = $basic_sal[$id];
			$data['bonus'] = $bonus[$id];
			$data['commission'] = $commission[$id];
			$date['abonus'] = $abonus[$id];
			$data['all_travel'] = $all_travel[$id];
			$data['loaninstall'] = $loaninstall[$id];
			$data['comp_pension'] = $comp_pension[$id];
			$data['emp_pension'] = $emp_pension[$id];
			$data['comp_provident'] = $comp_provident[$id];
			$data['emp_provident'] = $emp_provident[$id];
			//$data['comp_uif']=$comp_uif[$id];
			//$data['emp_uif']=$emp_uif[$id];
			$data['comp_ret'] = $comp_ret[$id];
			$data['emp_ret'] = $emp_ret[$id];
			$data['comp_medical'] = $comp_medical[$id];
			$data['emp_medical'] = $emp_medical[$id];
			$data['comp_other'] = $comp_other[$id];
			$data['emp_other'] = $emp_other[$id];
		} else {
			if ( $data["sal_bonus_month"] == $month ) {
				$annual[$id] = sprint($data["sal_bonus"]);
			} else {
				$annual[$id]="0.00";
			}

			$novert[$id] = "";
			$hovert[$id] = "";
			$mpaye_amount[$id] = "";

			if ( $data["payprd"] == "w" || $data["payprd"] == "f" ) {
				$tmpmon = date("j");
				$daycount = date("t");
				$dayweek = date("D");

				if ( strtolower($dayweek) == $data["payprd_day"] && $date_day+7 > $daycount ) {
					$process_comp_deductions = true;
				} else {
					$process_comp_deductions = false;
				}
			} else {
				$process_comp_deductions = true;
			}

			//$data["emp_uif"] = sprint(($data["basic_sal"] + $data["all_travel"]) * ($data["emp_uif"]/100));

			$effective_basicsal = $data["basic_sal"] * $multiplier;

			/* we only changing basic sal for non hourly employees,
				because for hourly employees we change the hours ($mutli)  */
			if ($data["saltyp"] != "h") {
				$data["basic_sal"] *= $multiplier;
			}

			if ($data["loaninstall"] > $data["loanamt"]) $data["loaninstall"] = $data["loanamt"];

			$data["comp_pension"] = sprint($effective_basicsal * ($data["comp_pension"]/100));
			$data["comp_provident"] = sprint($effective_basicsal * ($data["comp_provident"]/100));
			$data["emp_pension"] = sprint($effective_basicsal * ($data["emp_pension"]/100));
			$data["emp_provident"] = sprint($effective_basicsal * ($data["emp_provident"]/100));
			$data["emp_medical"] = sprint($data["emp_medical"] / $divisor);
			$data["comp_medical"] = sprint($data["comp_medical"] / $divisor);
			$data["emp_ret"] = sprint($data["emp_ret"] / $divisor);
			$data["comp_ret"] = sprint($data["comp_ret"] / $divisor);
			$data["loaninstall"] = sprint($data["loaninstall"] / $divisor);
			$data["all_travel"] = sprint($data["all_travel"] / $divisor);

			explodeDate($data["loandate"], $loana_year, $loana_month, $loana_day);
			if ($loana_year > $salyr || ($loana_year == $salyr && $loana_month > $month)) {
				$data["loanint"] = 0;
				$data["loaninstall"] = 0;
			}
		}

		if($data['paytype'] == "Cash") {
			$paydetails = "Cash
			<input type='hidden' name='accid[$id]' value='0'>";
		} elseif($data['paytype'] == "Ledger Account") {
			db_conn('core');

			$Sl = "SELECT accid,accname FROM accounts ORDER BY accname";
			$Rl = db_exec($Sl);

			$accounts = "<select name='account[$id]'>";

			while($ad = pg_fetch_array($Rl)) {
				if(isset($account[$id]) && ($account[$id] == $ad['accid'])) {
					$sel = "selected";
				} else {
					$sel = "";
				}
				$accounts .= "<option value='$ad[accid]' $sel>$ad[accname]</option>";
			}

			$accounts .= "</select>";

			$paydetails = "$accounts
				<input type='hidden' name='accid[$id]' value='0'>";
		} else {

			db_conn('cubit');

			$Sl = "SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname ASC";
			$Ry = db_exec($Sl) or errDie("Unable to get bank account.");

			if(pg_numrows($Ry) < 1){
				return "<li class='err'> There are no bank accounts found in Cubit.
				<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
			}

			$banks = "<select name='accid[$id]'>";

			while($acc = pg_fetch_array($Ry)){
				$banks .= "<option value='$acc[bankid]'>$acc[accname] ($acc[acctype])</option>";
			}

			$banks .= "</select>";
			$paydetails = "$banks";
		}

		# fringe benefits
		$i = 0;
		db_conn("cubit");

		$sql = "SELECT * FROM fringebens WHERE div = '".USER_DIV."' ORDER BY fringeben";
		$rslt = db_exec ($sql) or errDie ("Unable to select fringe benefits from database.");
		if ( pg_num_rows ($rslt) < 1 ) {
			$fringes = "<table ".TMPL_tblDflts.">";
			$fringes .= "<tr><td class='".bg_class()."' colspan='2' align='center'>None found in database.</td></tr>";
			$fringes .= "</table>";
		} else {
			$fringes = "<table ".TMPL_tblDflts.">";
			while ($myFringe = pg_fetch_array ($rslt)) {
				# check if employee has allowance
				$sql = "SELECT * FROM empfringe WHERE fringeid='$myFringe[id]' AND empnum='$data[empnum]' AND div = '".USER_DIV."'";
				$empRslt = db_exec ($sql) or errDie ("Unable to retrieve fringe benefit info from database.");
				if (pg_numrows ($empRslt) > 0) {
					$empFringe = pg_fetch_array ($empRslt);

					if ( substr($empFringe["type"], 0, 4) == "Perc" ) {
						$empFringe["amount"] = sprint($data["basic_sal"] * ($empFringe["amount"]/100) / $divisor);
					} else {
						$empFringe['amount'] = sprint($empFringe['amount'] / $divisor);
					}

					$grossal += $empFringe["amount"];

					$tmp_fringeaccs = $empFringe["accid"];
					$tmp_fringebens = $empFringe["amount"];
				} else {
					$tmp_fringeaccs = "0";
					$tmp_fringebens = "0.00";
				}

				$fringes .= "
					<input type='hidden' name='fringeaccs[$id][]' value='$tmp_fringeaccs'>
					<input type='hidden' name='fringeid[$id][]' value='$myFringe[id]'>
					<input type='hidden' name='fringename[$id][]' value='$myFringe[fringeben]'>
					<tr>
						<td>$myFringe[fringeben]</td>
						<td>".CUR."</td>
						<td><input type='text' size='10' name='fringebens[$id][]' value='$tmp_fringebens'></td>
					</tr>";

				$i++;
			}
			$fringes .= "</table>";
		}

		# get allowances
		$i = 0;
		db_conn('cubit');

		$sql = "SELECT * FROM allowances WHERE div = '".USER_DIV."' ORDER BY allowance";
		$allowRslt = db_exec ($sql) or errDie ("Unable to select allowances from database.");
		if (pg_numrows ($allowRslt) < 1) {
			$allowances = "<table ".TMPL_tblDflts.">";
			$allowances .= "<tr><td>None</td></tr>";
			$allowances .= "</table>";
		} else {

			$allowances = "<table ".TMPL_tblDflts.">";
			while ($myAllow = pg_fetch_array ($allowRslt)) {
				# check if employee has allowance
				$sql = "SELECT * FROM empallow WHERE allowid='$myAllow[id]' AND empnum='$data[empnum]' AND div = '".USER_DIV."'";
				$empAllowRslt = db_exec ($sql) or errDie ("Unable to select allowance info from database.");
				if (pg_numrows ($empAllowRslt) > 0) {
					$dataAllow = pg_fetch_array ($empAllowRslt);
					$dataAllow['amount'] = sprint($dataAllow['amount'] / $divisor);

					$grossal += $dataAllow["amount"];

					$tmp_allowaccs = $dataAllow["accid"];
					$tmp_allowances = $dataAllow["amount"];
				} else {
					$tmp_allowaccs = $myAllow["accid"];
					$tmp_allowances = "0.00";
				}

				$allowances .= "
					<input type='hidden' name='allowid[$id][]' value='$myAllow[id]'>
					<input type='hidden' name='allowname[$id][]' value='$myAllow[allowance]'>
					<input type='hidden' name='allowtax[$id][]' value='$myAllow[add]'>
					<input type='hidden' name='allowaccs[$id][]' value='$tmp_allowaccs'>
					<tr><td>$myAllow[allowance]</td>
						<td>".CUR."</td>
						<td><input type='text' size='10' name='allowances[$id][]' value='$tmp_allowances'></td>
					</tr>";

				$i++;
			}

			$allowances .= "</table>";
		}

		$subsistence = "";
		$subslst = new dbSelect("subsistence", "cubit", array(
			"where" => "div='".USER_DIV."'",
			"order" => "name"
		));
		$subslst->run();
		if ($subslst->num_rows() > 0) {
			$i = 0;
			$subsistence .= "
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
					"where" => "empnum='$data[empnum]' AND subid='$sid'"
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
					<input type='hidden' name='subsname[$id][$sid]' value='$subs[name]'>
					<input type='hidden' name='subsacc[$id][$sid]' value='$si[accid]'>
					<input type='hidden' name='subsrep[$id][$sid]' value='$subs[in_republic]'>
					<input type='hidden' name='subsmeal[$id][$sid]' value='$subs[meals]'>
					<tr bgcolor='".bgcolor($i)."'>
						<td>$subs[name]</td>
						<td nowrap>".CUR." <input type='text' size='5' name='subsamt[$id][$sid]' value='$si[amount]'></td>
						<td><input type='text' size='2' name='subsdays[$id][$sid]' value='$si[days]'></td>
					</tr>";
			}

			$subsistence .= "
				</table>";
		}

		# Deductions
		$i = 0;
		db_conn('cubit');

		$sql = "SELECT * FROM salded WHERE div = '".USER_DIV."' ORDER BY deduction";
		$deductRslt = db_exec ($sql) or errDie ("Unable to select deductions from database.");
		if (pg_numrows ($deductRslt) < 1) {
			$deductions = "<table ".TMPL_tblDflts.">";
			$deductions .= "<tr><td>None</td></tr>";
			$deductions .= "</table>";
		} else {

			$deductions = "<table ".TMPL_tblDflts.">";
			while ($myDeduct = pg_fetch_array ($deductRslt)) {
				# check if employee has deduction
				$sql = "SELECT * FROM empdeduct WHERE dedid='$myDeduct[id]' AND empnum='$data[empnum]' AND div = '".USER_DIV."'";
				$empDeductRslt = db_exec ($sql) or errDie ("Unable to select Deduction info from database.");
				if (pg_numrows ($empDeductRslt) > 0) {
					$dataDeduct = pg_fetch_array ($empDeductRslt);
					if ( $dataDeduct["grosdeduct"] == "y" ) {
						$deductions_msg = "(Before PAYE)";
						$sal_calcfrom = $grossal;
					} else {
						$deductions_msg = "";
						$sal_calcfrom = $data["basic_sal"];
					}

					if($dataDeduct['type'] == "Amount") {
						$dataDeduct['amount'] = sprint($dataDeduct['amount'] / $divisor);
					} else {
						$dataDeduct['amount'] = sprint($sal_calcfrom*$dataDeduct['amount']/100 / $divisor);
					}

					// calculate employer contribution to deduction
					if ( $dataDeduct["employer_type"] == "Amount" ) {
						$dataDeduct["employer_amount"] = sprint($dataDeduct["employer_amount"] / $divisor);
					} else {
						$dataDeduct["employer_amount"] = sprint($dataDeduct["amount"] * $dataDeduct["employer_amount"] / 100 / $divisor);
					}

					$tmp_deductions = $dataDeduct["amount"];
					$tmp_dedaccs = $dataDeduct["accid"];
					$tmp_emp_ded = $dataDeduct["employer_amount"];
				} else {
					$tmp_deductions = "0.00";
					$tmp_emp_ded = "0.00";
					$tmp_dedaccs = $myDeduct["accid"] != 0 ? $myDeduct["accid"] : $myDeduct["expaccid"];
					$deductions_msg = "";
				}

				# check if we should be using deductions
				if ($data['emp_usescales'] == "1" AND $myDeduct['type'] == "Percentage"){
					# check if this deduction has scales
					$get_scales = "SELECT * FROM salded_scales WHERE saldedid = '$myDeduct[id]' LIMIT 1";
					$run_scales = db_exec ($get_scales) or errDie ("Unable to get deduction scale information.");
					if (pg_numrows ($run_scales) > 0){
						# scales exist
						$get_perc = "
							SELECT * FROM salded_scales 
							WHERE scale_from <= '$data[basic_sal]' AND scale_to >= '$data[basic_sal]' AND saldedid = '$myDeduct[id]' 
							LIMIT 1";
						$run_perc = db_exec ($get_perc) or errDie ("Unable to get deduction scale information.");
						if (pg_numrows ($run_perc) > 0){
							# found a matching scale for this scaled duduction for a customer using scales ....
							$scale_arr = pg_fetch_array ($run_perc);
	
							$tmp_deductions = sprint(($data['basic_sal'] / 100) * $scale_arr['scale_amount']);
						}
					}
				}

				$deductions .= "
					<input type='hidden' size='10' name='deductid[$id][]' value='$myDeduct[id]'>
					<input type='hidden' size='30' name='deductname[$id][]' value='$myDeduct[deduction]'>
					<input type='hidden' size='10' name='deducttax[$id][]' value='$myDeduct[add]'>
					<input type='hidden' name='dedaccs[$id][]' value='$tmp_dedaccs'>
					<tr>
						<td>$myDeduct[deduction] $deductions_msg</td>
						<td>".CUR."</td>
						<td><input type='text' size='10' name='deductions[$id][]' value='$tmp_deductions'></td>
						<td>".CUR."</td>
						<td><input type='hidden' size='10' name='employer_deductions[$id][]' value='$tmp_emp_ded'></td>
					</tr>";
				$i++;
			}
			$deductions .= "</table>";
		}

		$rt = "";

		db_conn('cubit');

		$Sl = "SELECT * FROM rbs ORDER BY name";
		$Rl = db_exec($Sl) or errDie("Unable to get data.");

		$i = 0;

		if (pg_num_rows($Rl)>0) {
			$rt = "<table ".TMPL_tblDflts.">";

			while($td = pg_fetch_array($Rl)) {
				$bgcolor = ($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				if(!isset($rbsa[$id][$td['id']])) {
					$rbsa[$id][$td['id']] = "";
				}

				$rt .= "
					<tr>
						<td><input type='hidden' name='rbs[$id][$td[id]]' value='$td[id]'>$td[name]</td>
						<td>".CUR."</td>
						<td><input type='text' size='10' name='rbsa[$id][$td[id]]' value='".$rbsa[$id][$td['id']]."' class='right'></td>
					</tr>";

				$i++;
			}

			$rt .= "</table>";
		} else {
			$rt .= "None";
		}

		$salarr = array("m"=>"Per Month", "w"=>"Per Week", "f"=>"Fortnightly", "h"=>"Per Hour");
		$salnarr = array("d"=>"Day(s)", "h"=>"Hour(s)");
		$saltype = $salarr[$data['saltyp']];

		if (!isset($multi[$id])) {
			$multi[$id] = $data["saltyp"] == "h" ? $multiplier : 1;
		}
		
		$multi[$id] = round($multi[$id]);

		if($data['saltyp'] == 'd' || $data['saltyp'] == 'h'){
			$salntype = $salnarr[$data['saltyp']];
			$multi_show = "x <input type='text' size='3' name='multi[$id]' value='$multi[$id]'> $salntype";
		} else {
			$multi_show = "<input type='hidden' name='multi[$id]' value='$multi[$id]'>";
			$saltype = "";
		}

		if(isset($mpaye[$id])) {
			$ch = "checked=yes";
		} else {
			$ch = "";
		}

		if ( $data["payprd"] == "m" || $data["payprd"] == "d" ) {
			// count the amount of weekdays in this month
			$workdays = 0;
			for ( $i = 1; $i <= date("t", mktime(0, 0, 0, $month, 1, date("Y"))); ++$i ) {
				$wd = date("w", mktime(0, 0, 0, $month, $i, date("Y")));

				if ( $wd != 0 && $wd != 6 ) {
					++$workdays;
				}
			}

			// hours per day calculation
			$hpd = $data["hpweek"] / 5;

			if ( ! isset($wh_total[$id]) ) $wh_total[$id] = $workdays * $hpd;
			if ( ! isset($wh_actual[$id]) ) $wh_actual[$id] = $wh_total[$id];
		}

		if ( $data["payprd"] == "w" ) {
			if ( ! isset($wh_total[$id]) ) $wh_total[$id] = $data["hpweek"];
			if ( ! isset($wh_actual[$id]) ) $wh_actual[$id] = $wh_total[$id];
		}

		if ( $data["payprd"] == "f" ) {
			if ( ! isset($wh_total[$id]) ) $wh_total[$id] = $data["hpweek"] * 2;
			if ( ! isset($wh_actual[$id]) ) $wh_actual[$id] = $wh_total[$id];
		}

		$js_workhours_fields .= "
			<script>
				f_sal[$id]		= sf.elements['basic_sal[$id]'];
				f_salbonus[$id] = sf.elements['sal_bonus[$id]'];
				f_whtot[$id]	= sf.elements['wh_total[$id]'];
				f_whact[$id]	= sf.elements['wh_actual[$id]'];
				f_cpension[$id]	= sf.elements['comp_pension[$id]'];
				f_epension[$id] = sf.elements['emp_pension[$id]'];
				f_cprov[$id]	= sf.elements['comp_provident[$id]'];
				f_eprov[$id]	= sf.elements['emp_provident[$id]'];
				//f_cuif[$id]		= sf.elements['comp_uif[$id]'];
				//f_euif[$id]		= sf.elements['emp_uif[$id]'];
				f_cother[$id]	= sf.elements['comp_other[$id]'];
				f_eother[$id]	= sf.elements['emp_other[$id]'];

				db_cpension[$id]	= ".$db["comp_pension"].";
				db_epension[$id]	= ".$db["emp_pension"].";
				db_cprov[$id]		= ".$db["comp_provident"].";
				db_eprov[$id]		= ".$db["emp_provident"].";
				//db_cuif[$id]		= ".$db["comp_uif"].";
				//db_euif[$id]		= ".$db["emp_uif"].";
				db_cother[$id]	= ".$db["comp_other"].";
				db_eother[$id]	= ".$db["emp_other"].";

				val_sal[$id] = -1;
			</script>";

		vsprint($data["basic_sal"]);

		$out .= "
			<tr bgcolor='$bgcolor'>
				<input type='hidden' name='emps[$id]' value='$id'>
				<input type='hidden' name='saltyp[$id]' value='$data[saltyp]'>
				<input type='hidden' name='process_comp_deductions[$id]' value='$process_comp_deductions'>
				<input type='hidden' name='divisor[$id]' value='$divisor'>
				<td nowrap>$data[enum]</td>
				<td>$data[sname], $data[fnames]</td>
				<td nowrap><input type='text' size='8' name='basic_sal[$id]' value='$data[basic_sal]' class='right' onChange='changedfield($id);'>$saltype $multi_show</td>";

		if (isset($d)) {
			$out .= "
				<input type='hidden' name='wh_total' value='1'>
				<input type='hidden' name='wh_actual' value='1'>";
		} else {
			$out .= "
				<td nowrap><input type='text' size='10' name='wh_total[$id]' value='$wh_total[$id]' class='right' onChange='workhours($id);'></td>
				<td nowrap><input type='text' size='10' name='wh_actual[$id]' value='$wh_actual[$id]' class='right' onChange='workhours($id);'></td>";
		}

		if (!isset($abonus[$id]))
			$abonus = 0;

		$out .= "
				<td nowrap><input type='text' size='5' name='novert[$id]' value='$novert[$id]' class='right'> Hrs</td>
				<td nowrap><input type='text' size='5' name='hovert[$id]' value='$hovert[$id]' class='right'> Hrs</td>
				<td nowrap><input type='hidden' size='8' name='bonus[$id]' value='0' class='right'><input type='text' size='8' name='abonus[$id]' value='$abonus[$id]' class='right'></td>
				<input type='hidden' name='annual[$id]' value='0' />
				<!--<td nowrap><input type='text' size='8' name='annual[$id]' value='$annual[$id]' class='right'></td>-->
				<td nowrap><input type='text' size='8' name='commission[$id]' value='$data[commission]' class='right'></td>
				<td nowrap>R<input type='text' size='8' name='all_travel[$id]' value='$data[all_travel]' class='right'></td>
				<td nowrap>R<input type='text' size='8' name='loaninstall[$id]' value='$data[loaninstall]' class='right'></td>
				<td nowrap>R<input type='text' size='8' name='comp_pension[$id]' value='$data[comp_pension]' class='right'></td>
				<td nowrap>R<input type='text' size='8' name='emp_pension[$id]' value='$data[emp_pension]' class='right'></td>
				<td nowrap>R<input type='text' size='8' name='comp_provident[$id]' value='$data[comp_provident]' class='right'></td>
				<td nowrap>R<input type='text' size='8' name='emp_provident[$id]' value='$data[emp_provident]' class='right'></td>
				<!--
				<td nowrap>R<input type='text' size='8' name='comp_uif[$id]' value='$data[comp_uif]' class='right'></td>
				<td nowrap>R<input type='text' size='8' name='emp_uif[$id]' value='$data[emp_uif]' class='right'></td>
				//-->
				<td nowrap>R<input type='text' size='8' name='comp_ret[$id]' value='$data[comp_ret]' class='right'></td>
				<td nowrap>R<input type='text' size='8' name='emp_ret[$id]' value='$data[emp_ret]' class='right'></td>
				<td nowrap>R<input type='text' size='8' name='comp_medical[$id]' value='$data[comp_medical]' class='right'></td>
				<td nowrap>R<input type='text' size='8' name='emp_medical[$id]' value='$data[emp_medical]' class='right'></td>
				<input type=hidden name='comp_other[$id]' value='0'>
				<input type=hidden name='emp_other[$id]' value='0'>
				<!--
				<td nowrap>R<input type='text' size='8' name='comp_other[$id]' value='$data[comp_other]' class='right'></td>
				<td nowrap>R<input type='text' size='8' name='emp_other[$id]' value='$data[emp_other]' class='right'></td>
				//-->
				<td>$paydetails</td>
				<td>
					<table>
						<tr>
							<td><input type='checkbox' name='mpaye[$id]' $ch></td>
							<td><input type='text' size='8' name='mpaye_amount[$id]' value='$mpaye_amount[$id]'></td>
						</tr>
					</table>
				</td>
				<td>$fringes</td>
				<td>$allowances</td>
				<td>$subsistence<!--%%EXCHANGE%%--></td>
				<td>$deductions</td>
				<td>$rt</td>
			</tr>";
	}
	
	if (!isset($id)) {
		header("Location: ../admin-employee-view.php?err=Please select at least one employee.");
		exit;
	}

	// replace the exchange rate place holder with the exchange rate form field
	if ($subs_int) {
		$exch = "
			<input type='hidden' name='subs_exch' value='1'>
			<li class='err'>Please calculate the rand amount prior to completing the amount above.</li>";

// 			<tr class='".bg_class()."'>
// 				<th class='".bg_class()."'>Exchange (ZAR-USD):</th>
// 				<td><input type='text' name='subs_exch' value='".xrate_get("USD")."'></td>
// 			</tr>";

		$out = str_replace("<!--%%EXCHANGE%%-->", $exch, $out);
	}

	$out .= "
			<tr><td><br></td></tr>
			$send
			<tr>
				<td colspan='9' align='right' nowrap='t'>
					<input type='submit' name='btn_back' value='&laquo; Correction'>
					<input type='submit' value='Confirm &raquo;'>
				</td>
				<td colspan='10' align='right' nowrap='t'>
					<input type='submit' name='btn_back' value='&laquo; Correction'>
					<input type='submit' value='Confirm &raquo;'>
				</td>
			</tr>
		</table>
		</form>
		<script>
			function monthchange(mondd) {
				".implode("\n", $jsbonus_clear)."
				switch ( mondd.value ) {
				case '1':
					".implode("\n", $jsbonus[1])."
					break;
				case '2':
					".implode("\n", $jsbonus[2])."
					break;
				case '3':
					".implode("\n", $jsbonus[3])."
					break;
				case '4':
					".implode("\n", $jsbonus[4])."
					break;
				case '5':
					".implode("\n", $jsbonus[5])."
					break;
				case '6':
					".implode("\n", $jsbonus[6])."
					break;
				case '7':
					".implode("\n", $jsbonus[7])."
					break;
				case '8':
					".implode("\n", $jsbonus[8])."
					break;
				case '9':
					".implode("\n", $jsbonus[9])."
					break;
				case '10':
					".implode("\n", $jsbonus[10])."
					break;
				case '11':
					".implode("\n", $jsbonus[11])."
					break;
				case '12':
					".implode("\n", $jsbonus[12])."
					break;
				}";

			//".implode("\n", $uif_calc)."

	$out .= "
			}
		</script>
		<script>
			sf = document.getElementById('salfrm');
			f_sal = new Array();
			f_salbonus = new Array();
			f_whtot = new Array();
			f_whact = new Array();
			f_cpension = new Array();
			f_epension = new Array();
			f_cprov = new Array();
			f_eprov = new Array();
			//f_cuif = new Array();
			//f_euif = new Array();
			f_cother = new Array();
			f_eother = new Array();

			db_cpension = new Array();
			db_epension = new Array();
			db_cprov = new Array();
			db_eprov = new Array();
			//db_cuif = new Array();
			//db_euif = new Array();
			db_cother = new Array();
			db_eother = new Array();

			val_sal = new Array();
		</script>
 		$js_workhours_fields
		<script>
			// changing the workhours
			function workhours(id) {
				if ( val_sal[id] < 0 ) val_sal[id] = parseFloat(f_sal[id].value);

				val_whtot	= parseFloat(f_whtot[id].value);
				val_whact	= parseFloat(f_whact[id].value);

				if ( val_whtot >= val_whact ) {
					p = val_whact / val_whtot;

					// calculate the new basic salary
					x = val_sal[id] * p;
					x = x.toFixed(2);
					f_sal[id].value = x;

					// calculate the new values
					val_cpension 	= x * db_cpension[id] / 100;
					val_epension 	= x * db_epension[id] / 100;
					val_cprov		= x * db_cprov[id] / 100;
					val_eprov		= x * db_eprov[id] / 100;
					//val_cuif		= x * db_cuif[id] / 100;
					//val_euif		= x * db_euif[id] / 100;
					val_cother		= x * db_cother[id] / 100;
					val_eother		= x * db_eother[id] / 100;

					val_cpension 	= val_cpension.toFixed(2);
					val_epension 	= val_epension.toFixed(2);
					val_cprov 		= val_cprov.toFixed(2);
					val_eprov		= val_eprov.toFixed(2);
					//val_cuif		= val_cuif.toFixed(2);
					//val_euif		= val_euif.toFixed(2);
					val_cother		= val_cother.toFixed(2);
					val_eother		= val_eother.toFixed(2);

					f_cpension[id].value	= val_cpension;
					f_epension[id].value 	= val_epension;
					f_cprov[id].value 		= val_cprov;
					f_eprov[id].value		= val_eprov;
					//f_cuif[id].value		= val_cuif;
					//f_euif[id].value		= val_euif;
					f_cother[id].value		= val_cother;
					f_eother[id].value		= val_eother;
				}
			}

			function changedfield(id) {
				val_whtot	= parseFloat(f_whtot[id].value);
				val_whact	= parseFloat(f_whact[id].value);

				p = val_whtot / val_whact;

				val_sal[id] = parseFloat(f_sal[id].value) * p;
				val_sal[id] = val_sal[id].toFixed(2);
			}

			monthchange(document.getElementById('salfrm').elements['month']);
		</script>";
	return $out;

}




function confirm ($_POST)
{

	$_POST = var_makesafe($_POST);
	extract($_POST);

	if(!isset($date_day)) {
		exit;
	}

	if (isset($btn_back)) {
		header("Location: ../admin-employee-view.php?".array2get($_POST));
		exit;
	}

	$ydate = "$date_year-$date_month-$date_day";

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($ydate) >= strtotime($blocked_date_from) AND strtotime($ydate) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	$week += 0;

	/* check for blocked accounts */
	$salconacc = gethook("accnum", "salacc", "name", "salaries control");
	$salconacc_orig = gethook("accnum", "salacc", "name", "salaries control original");

	if ( $salconacc != $salconacc_orig ) {
		block_check($salconacc);
	}

	block_check(gethook("accnum", "salacc", "name", "Commission"));
	block_check(gethook("accnum", "salacc", "name", "Bonus"));
	block_check(gethook("accnum", "salacc", "name", "interestreceived"));
	block_check(gethook("accnum", "salacc", "name", "PAYE"));
	block_check(gethook("accnum", "salacc", "name", "UIF"));
	block_check(gethook("accnum", "salacc", "name", "uifbal"));
	block_check(gethook("accnum", "salacc", "name", "sdlbal"));
	block_check(gethook("accnum", "salacc", "name", "pension"));
	block_check(gethook("accnum", "salacc", "name", "medical"));
	block_check(gethook("accnum", "salacc", "name", "cash"));
	block_check(gethook("accnum", "salacc", "name", "retire"));
	block_check(gethook("accnum", "salacc", "name", "provident"));

	global $global_empnum;

	db_conn('cubit');
	$Sl = "SELECT * FROM employees WHERE div='".USER_DIV."' ORDER BY sname,fnames";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");
	while ( $data = pg_fetch_array($Ri) ) {
		if(!(isset($emps[$data['empnum']]))) {
			continue;
		}

		$global_empnum = $id = $data["empnum"];

		$empname = "$data[fnames] $data[sname] ($data[enum])";

		// check for employee exp accs blocks
		block_check($data["expacc_provident"], $empname);
		block_check($data["expacc_ret"], $empname);
		block_check($data["expacc_pension"], $empname);
		block_check($data["expacc_uif"], $empname);
		block_check($data["expacc_medical"], $empname);
		block_check($data["expacc_other"], $empname);
		block_check($data["expacc_sdl"], $empname);
		block_check($data["expacc_salwages"], $empname);
		if ( $data["expacc_loan"] > 0 ) {
			block_check($data["expacc_loan"], $empname);
		}

		if ( isset($dedaccs[$id]) ) {
			foreach ( $dedaccs[$id] as $checkacc ) {
				block_check($checkacc, $empname);
			}
		}
		if ( isset($allowaccs[$id]) ) {
			foreach ( $allowaccs[$id] as $checkacc ) {
				block_check($checkacc, $empname);
			}
		}
	}

	finish_block_check();

	/* check if hire date before pay date for any of the employees*/
	$sql = "SELECT * FROM employees WHERE div='".USER_DIV."' ORDER BY sname,fnames";
	$rslt = db_exec($sql) or errDie("Unable to get data.");

	$hderrs = array();
	while($data = pg_fetch_array($rslt)) {
		if (!isset($emps[$data['empnum']])) {
			continue;
		}

		explodeDate($data["hiredate"], $hd_year, $hd_month, $hd_day);
		$MONempyear = getYearOfEmpMon($month);

		switch ($data["payprd"]) {
			case "m":
				$extra = false;
				break;
			case "d":
				$extra = $hd_year == $MONempyear && $hd_month == $month && $pday < $hd_day;
				break;
			case "w":
			case "f":
				$extra = $hd_year == $MONempyear && $hd_month == $month && $weekends[$week] < $hd_day;
				break;
		}

		if ($hd_year > $MONempyear
				|| ($hd_year == $MONempyear && $hd_month > $month)
				|| ($extra)) {
			$hderrs[] = "&nbsp; - &nbsp; $data[sname], $data[fnames]<br />";
		}
	}

	if (count($hderrs) > 0) {
		$hderrs = "
			<li class='err'>
				The following employees were not employed in the period requested.<br />
				".implode("", $hderrs)."
			</li>";

		return enter($_POST, $hderrs);
	}

	global $eMONPRD;

	$out="
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='month' value='$month'>
			<input type='hidden' name='week' value='$week'>
			<input type='hidden' name='printslip' value='$printslip' />
			".(isset($pday)?"<input type='hidden' name='pday' value='$pday' />":"")."
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='4' valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td colspan='2'><h3>Processing Batch Salaries</h3></td>
						</tr>
						<tr class='".bg_class()."'>
							<th>Salary Period:</th>
							<td>".getMonthName($month). " " .getYearOfEmpMon($month)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<th>Processing Date:</th>
							<td nowrap>
								<input type='hidden' name='date_day' value='$date_day'> $date_day -
								<input type='hidden' name='date_month' value='$date_month'> $date_month -
								<input type='hidden' name='date_year' value='$date_year'> $date_year
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan='3'><input type='submit' value='&laquo; Correction'></td>
				<td colspan='5'><input type='button' value='Pay Selected Employees In Full After Processing' onClick='emp_payall();'></td>
				<td colspan='2' align=right><input type='submit' value='Process Salaries &raquo;' name='button'></td>
				<td colspan='10' align=right><input type='submit' value='Process Salaries &raquo;' name='button'></td>
			</tr>
			<tr>
				<th>Nr.</th>
				<th>Name</th>
				<th>Remuneration</th>
				<th>Amount Paid After Processing</th>
				<th>Annual Bonus</th>
				<!--
				<th>Special Bonus/Additional Salary</th>
				<th>Annual Bonus</th>
				-->
				<th>Commission</th>
				<th>Travel Allowance</th>
				<th>Loan Repayment</th>
				<th>Pension: Company Contribution</th>
				<th>Pension: Employee Deduction</th>
				<th>Provident: Company Contribution</th>
				<th>Provident: Employee Deduction</th>
				<th>UIF: Company Contribution</th>
				<th>UIF: Employee Deduction</th>
				<th>Retirement Annuity: Company Contribution</th>
				<th>Retirement Annuity: Employee Deduction</th>
				<th>Medical Contribution: Company</th>
				<th>Medical Contribution: Employee</th>
				<!--
				<th>Other: Company Contribution</th>
				<th>Other: Employee Deduction</th>
				//-->
				<th>Normal Overtime</th>
				<th>Public Holiday Overtime</th>
				<th>Method of Payment</th>
				<th>Medical Fringe Ben.</th>
				<th>Car 1 Fringe</th>
				<th>Car 2 Fringe</th>
				<th>Loan Fringe</th>
				<th>Fringe Ben.</th>
		 		<th>Allowances</th>
		 		<th>Subsistence</th>
		 		<th>Deductions</th>
		 		<th>Reimbursements</th>
		 		<th>Gross Salary</th>
				<th>PAYE</th>
				<th>Nett Pay + Reimbursements</th>
			</tr>";

	db_conn('cubit');

	$i = 0;

	$Sl = "SELECT * FROM employees WHERE div='".USER_DIV."' ORDER BY sname,fnames";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$errout = "";
	$payall_js = "";
	while( $data = pg_fetch_array($Ri) ) {
		if (!isset($emps[$data['empnum']])) {
			continue;
		}

		$id = $data['empnum'];

		$basic_sal_save[$id] = $basic_sal[$id];

		$send = "";

		if (isset($b)) {
			$send = "<input type='hidden' name='b' value=''>";
			if($data["payprd"] != "f") {
				continue;
			}
		} elseif(isset($w)) {
			$send = "<input type='hidden' name='w' value=''>";
			if($data['payprd'] != "w") {
				continue;
			}
		} elseif(isset($m)) {
			$send = "<input type='hidden' name='m' value=''>";
			if($data['payprd'] != "m") {
				continue;
			}
		} elseif(isset($d)) {
			$send = "<input type='hidden' name='d' value=''>";
			if($data['payprd'] != "d") {
				continue;
			}
		} else {
			continue;
		}

		$yy = date("Y");
		$mm = $month;
		$mm += 0;

		if(!isset($myEmp))
			$myEmp = array ("payprd" => "");

		db_conn("cubit");

		if ($data["payprd"] == "d") {
			$Sl = "SELECT * FROM salpaid WHERE empnum='$data[empnum]' AND month='$mm' AND week='$pday' AND cyear='".EMP_YEAR."'";
			$Rq = db_exec($Sl);

			$paid = pg_num_rows($Rq);

			$Sl = "SELECT * FROM salr WHERE empnum='$data[empnum]' AND month='$mm' AND week='$pday' AND cyear='".EMP_YEAR."'";
			$Rq = db_exec($Sl);

			$upaid = pg_num_rows($Rq);

			$upaid += 0;

			$paid -= $upaid;
		} else if($myEmp['payprd']!="m") {
			$Sl = "SELECT * FROM salpaid WHERE empnum='$data[empnum]' AND month='$mm' AND week='$week' AND cyear='".EMP_YEAR."'";
			$Rq = db_exec($Sl);

			$paid = pg_num_rows($Rq);

			$Sl = "SELECT * FROM salr WHERE empnum='$data[empnum]' AND month='$mm' AND week='$week' AND cyear='".EMP_YEAR."'";
			$Rq = db_exec($Sl);

			$upaid = pg_num_rows($Rq);

			$upaid += 0;

			$paid -= $upaid;
		}

		if(isset($paid) && ($paid > 0)) {
			$out .= "
				<tr>
					<td colspan='1000'>
						<li class='err'>You have already processed a salary for this period, for $data[sname], $data[fnames]</li>
						<input type='hidden' name='emps[$id]' value='$id'>
						<input type='hidden' name='emps_already[$id]' value='$id'>
					</td>
				</tr>";
			continue;
		}

		if ( $divisor[$id] != 1 && round($divisor[$id],2) != round(52/12,2) && round($divisor[$id],2) != round(26/12,2) ) {
			/*$out .= "<tr><td colspan=1000>
						<li class=err>Error with pay period (DIVIS), for $data[sname], $data[fnames]</li>
						<input type=hidden name='emps[$id]' value='$id'>
						<input type=hidden name='emps_already[$id]' value='$id'>
					</td></tr>";
			continue;*/
		}

		$bgcolor = ($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

		if(isset($basic_sal[$id])) {

		} else {
			$annual[$id] = "";
			$novert[$id] = "";
			$hovert[$id] = "";
		}

		if($data['paytype'] == "Cash") {
			$paydetails = "Cash
				<input type='hidden' name='accid[$id]' value='0'>";
		} elseif($data['paytype'] == "Ledger Account") {
			db_conn('core');

			$account[$id] += 0;

			$Sl = "SELECT accid,accname FROM accounts WHERE accid='$account[$id]'";
			$Rl = db_exec($Sl);

			$accounts = "<input type='hidden' name='account[$id]' value='$account[$id]'>";

			$ad = pg_fetch_array($Rl);

			$accounts .= "$ad[accname]";

			$paydetails = "$accounts
				<input type='hidden' name='accid[$id]' value='0'>";
		} else {

			$accid[$id] += 0;

			db_conn('cubit');

			$Sl = "SELECT * FROM bankacct WHERE bankid='$accid[$id]'";
			$Ry = db_exec($Sl) or errDie("Unable to get bank account.");

			if(pg_numrows($Ry) < 1){
				return "<li class='err'> There are no bank accounts found in Cubit.
				<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
			}

			$banks = "<input type='hidden' name='accid[$id]' value='$accid[$id]'>";

			$acc = pg_fetch_array($Ry);

			$banks .= "$acc[accname] ($acc[acctype])";

			$paydetails = "$banks";
		}

		// fringe benefits
		$fringe_tot[$id] = 0;
		$c = 0;
		$fringes = "<table ".TMPL_tblDflts.">";
		if ( isset($fringeid[$id]) && is_array($fringeid[$id]) ) {
			foreach ( $fringeid[$id] as $i => $fid ) {
				$bgColor = (++$c % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				$fringes .= "
					<tr bgcolor='$bgColor'>
						<td>".$fringename[$id][$i]."</td>
						<td>".CUR."</td>
						<td>".$fringebens[$id][$i]."</td>
					</tr>
					<input type='hidden' name='fringeid[$id][$i]' value='".$fringeid[$id][$i]."'>
					<input type='hidden' name='fringebens[$id][$i]' value='".$fringebens[$id][$i]."'>
					<input type='hidden' name='fringename[$id][$i]' value='".$fringename[$id][$i]."'>
					<input type='hidden' name='fringeaccs[$id][$i]' value='".$fringeaccs[$id][$i]."'>";

				$fringe_tot[$id] += $fringebens[$id][$i];
			}
		}
		$fringes .= "</table>";

		if ( $fringe_tot[$id] == 0 ) {
			$fringes = "";
		}

		$fringes .= "<input type='hidden' name='fringe_tot[$id]' value='$fringe_tot[$id]'>";

		/* allowances */
		$all_beforeamount[$id] = 0;
		$all_afteramount[$id] = 0;
		$Allowances = "";
/*		db_conn('cubit');
		$sql = "SELECT * FROM allowances WHERE div = '".USER_DIV."' ORDER BY allowance";
		$allowRslt = db_exec ($sql) or errDie ("Unable to select allowances from database.");
		if (pg_numrows ($allowRslt) < 1) {
			$Allowances = "None";
		} else {

			$Allowances = "<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>";
			while ($myAllow = pg_fetch_array ($allowRslt)) {
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				# check if employee has allowance
				$sql = "SELECT * FROM empallow WHERE allowid='$myAllow[id]' AND empnum='$data[empnum]' AND div = '".USER_DIV."'";
				$empAllowRslt = db_exec ($sql) or errDie ("Unable to select allowance info from database.");
				if (pg_numrows ($empAllowRslt) > 0) {
					$Allowances .= "<tr><td>$myAllow[allowance]</td>";
					$dataAllow = pg_fetch_array ($empAllowRslt);
					$dataAllow['amount']=sprint($dataAllow['amount']);

					$allowances[$id][$i]=sprint($allowances[$id][$i]);
					$Allowances .= "<td>R</td><td align=right>
					<input type=hidden size=10 name='allowid[$id][]' value='$dataAllow[allowid]'>
					<input type=hidden size=30 name='allowname[$id][]' value='$myAllow[allowance]'>
					<input type=hidden size=10 name='allowtax[$id][]' value='$myAllow[add]'>
					<input type=hidden name='allowaccs[$id][]' value='".$allowaccs[$id][$i]."'>
					<input type=hidden name='allowances[$id][]' value='".$allowances[$id][$i]."'>".$allowances[$id][$i]."</td></tr>\n";

					if($myAllow['add']=="Yes") {
						$all_beforeamount[$id] += $allowances[$id][$i];
					} else {
						$all_afteramount[$id] += $allowances[$id][$i];
					}
				}
				$i++;
			}

			$Allowances .= "</table>";
		}*/

		if (isset($allowid[$id]) && is_array($allowid[$id]) && count($allowid[$id]) > 0) {
			$Allowances = "<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>";
			foreach ($allowid[$id] as $k => $dummy) {
					$Allowances .= "
						<input type='hidden' size='10' name='allowid[$id][$k]' value='".$allowid[$id][$k]."'>
						<input type='hidden' size='30' name='allowname[$id][$k]' value='".$allowname[$id][$k]."'>
						<input type='hidden' size='10' name='allowtax[$id][$k]' value='".$allowtax[$id][$k]."'>
						<input type='hidden' name='allowaccs[$id][$k]' value='".$allowaccs[$id][$k]."'>
						<input type='hidden' name='allowances[$id][$k]' value='".$allowances[$id][$k]."'>
						<tr>
							<td>".$allowname[$id][$k]."</td>
							<td>".CUR."</td>
							<td align='right'>".$allowances[$id][$k]."</td>
						</tr>";

					if ($allowtax[$id][$k]=="Yes") {
						$all_beforeamount[$id] += $allowances[$id][$k];
					} else {
						$all_afteramount[$id] += $allowances[$id][$k];
					}
			}

			$Allowances .= "</table>";
		} else {
			$Allowances = "";
		}

		$Allowances .= "
			<input type='hidden' name='all_beforeamount[$id]' value='$all_beforeamount[$id]'>
			<input type='hidden' name='all_afteramount[$id]' value='$all_afteramount[$id]'>";

		$subsistence = "";
		$subs_taxable[$id] = 0;
		$subs_total[$id] = 0;
		$i = 0;
		if (isset($subsname[$id])) {
			if (isset($subs_exch) && $subs_exch == 0) {
				$subs_exch = 1;
			}
			foreach ($subsname[$id] as $sid => $sn) {
				if ($subsrep[$id][$sid] == "yes") {
					$nontax = $subsdays[$id][$sid] * ($subsmeal[$id][$sid] == "yes" ? 276 : 85);
					$subs_total[$id] += $subsamt[$id][$sid];
				} else {
					// outside republic, 196 dollars
					$nontax = $subsdays[$id][$sid] * (215 / $subs_exch);
					$subs_total[$id] += $subsamt[$id][$sid] * $subs_exch;
				}

				$tmp = $subsamt[$id][$sid] - $nontax;

				if ($tmp > 0) {
					$subs_taxable[$id] += ($tmp / $divisor[$id]);
				}

				$subsistence .= "
					<input type='hidden' name='subsname[$id][$sid]' value='".$subsname[$id][$sid]."'>
					<input type='hidden' name='subsacc[$id][$sid]' value='".$subsacc[$id][$sid]."'>
					<input type='hidden' name='subsamt[$id][$sid]' value='".$subsamt[$id][$sid]."'>
					<input type='hidden' name='subsrep[$id][$sid]' value='".$subsrep[$id][$sid]."'>
					<input type='hidden' name='subsmeal[$id][$sid]' value='".$subsmeal[$id][$sid]."'>
					<input type='hidden' name='subsdays[$id][$sid]' value='".$subsdays[$id][$sid]."'>
					<tr bgcolor='".bgcolor($i)."'>
						<td nowrap>".$subsname[$id][$sid]."</td>
						<td nowrap>".CUR." ".$subsamt[$id][$sid]."</td>
						<td>".$subsdays[$id][$sid]."</td>
					</tr>";
			}

			if (!empty($subsistence)) {
				$subsistence = "
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Name</th>
							<th>Amount</th>
							<th>Days</th>
						</tr>
						$subsistence
					</table>";
			}
		}

		# Deductions
		$Deductions = "";
		$de_beforeamount[$id] = 0;
		$de_afteramount[$id] = 0;
		$de_beforeamount_emp[$id] = 0;
		$de_afteramount_emp[$id] = 0;

/*		db_conn('cubit');
		$sql = "SELECT * FROM salded WHERE div = '".USER_DIV."' ORDER BY deduction";
		$deductRslt = db_exec ($sql) or errDie ("Unable to select deductions from database.");
		if (pg_numrows ($deductRslt) < 1) {
			$Deductions = "None";
		} else {
			$Deductions = "<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>";
			while ($myDeduct = pg_fetch_array ($deductRslt)) {
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
				# check if employee has deduction
				$sql = "SELECT * FROM empdeduct WHERE dedid='$myDeduct[id]' AND empnum='$data[empnum]' AND div = '".USER_DIV."'";
				$empDeductRslt = db_exec ($sql) or errDie ("Unable to select Deduction info from database.");
				if (pg_numrows ($empDeductRslt) > 0) {
					$Deductions .= "<tr><td>$myDeduct[deduction]</td>";
					$dataDeduct = pg_fetch_array ($empDeductRslt);

//					if($dataDeduct['type']=="Amount") {
//						$dataDeduct['amount']=sprint($dataDeduct['amount']);
//						$deductions[$id][$i]=sprint($deductions[$id][$i]);
//					} else {
//						$deductions[$id][$i]=sprint($data['basic_sal']*$deductions[$id][$i]/100);
//						//$dataDeduct['amount']=sprint($data['basic_sal']*$dataDeduct['amount']/100);
//					}
					$Deductions .= "
					<td>".CUR."</td><td align=right>
						<input type=hidden size=10 name='deductid[$id][]' value='$myDeduct[id]'>
						<input type=hidden size=30 name='deductname[$id][]' value='$myDeduct[deduction]'>
						<input type=hidden name='deductions[$id][]' value='".$deductions[$id][$i]."'>".$deductions[$id][$i]."
						<input type=hidden name='dedaccs[$id][]' value='".$dedaccs[$id][$i]."'>
					</td>
<!--					<td>".CUR."</td> //-->
						<td>
						<input type=hidden name='employer_deductions[$id][]' value='".$employer_deductions[$id][$i]."'>
						<input type=hidden size=10 name='deducttax[$id][]' value='$myDeduct[add]'></td></tr>\n";


					if($myDeduct['add']=="Yes") {
						$de_beforeamount[$id] += $deductions[$id][$i]+$employer_deductions[$id][$i];
						//$de_beforeamount_emp[$id] += $employer_deductions[$id][$i];
					} else {
						$de_afteramount[$id] += $deductions[$id][$i]+$employer_deductions[$id][$i];
						//$de_afteramount_emp[$id] += $employer_deductions[$id][$i];
					}

					$i++;
				}
			}
			$Deductions .= "</table>";
		}*/

		if (isset($deductid[$id]) && is_array($deductid[$id]) && count($deductid[$id]) > 0) {
			$Deductions = "<table ".TMPL_tblDflts.">";
			foreach ($deductid[$id] as $k => $dummy) {
				$Deductions .= "
					<tr>
						<input type='hidden' name='deductid[$id][$k]' value='".$deductid[$id][$k]."'>
						<input type='hidden' name='deductname[$id][$k]' value='".$deductname[$id][$k]."'>
						<input type='hidden' name='deductions[$id][$k]' value='".$deductions[$id][$k]."'>
						<input type='hidden' name='dedaccs[$id][$k]' value='".$dedaccs[$id][$k]."'>
						<input type='hidden' name='employer_deductions[$id][$k]' value='".$employer_deductions[$id][$k]."'>
						<input type='hidden' name='deducttax[$id][$k]' value='".$deducttax[$id][$k]."'>
						<td>".$deductname[$id][$k]."</td>
						<td>".CUR."</td>
						<td>".$deductions[$id][$k]."</td>
					</tr>";

				if($deducttax[$id][$k]=="Yes") {
					$de_beforeamount[$id] += $deductions[$id][$k]+$employer_deductions[$id][$k];
					//$de_beforeamount_emp[$id] += $employer_deductions[$id][$i];
				} else {
					$de_afteramount[$id] += $deductions[$id][$k]+$employer_deductions[$id][$k];
					//$de_afteramount_emp[$id] += $employer_deductions[$id][$i];
				}
			}
			$Deductions .= "</table>";
		} else {
			$Deductions = "None";
		}

		$Deductions .= "
			<input type='hidden' name='de_beforeamount[$id]' value='$de_beforeamount[$id]'>
			<input type='hidden' name='de_beforeamount_emp[$id]' value='$de_beforeamount_emp[$id]'>
			<input type='hidden' name='de_afteramount[$id]' value='$de_afteramount[$id]'>
			<input type='hidden' name='de_afteramount_emp[$id]' value='$de_afteramount[$id]'>";

		db_conn('cubit');

		$Sl = "SELECT * FROM rbs ORDER BY name";
		$Rl = db_exec($Sl) or errDie("Unable to get data.");

		$i = 0;

		if(pg_num_rows($Rl)>0) {

			$rt = "<table ".TMPL_tblDflts." width='100%'>";

			while($td = pg_fetch_array($Rl)) {
				$bgcolor = ($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				if(!isset($rbsa[$id][$td['id']])) {
					$rbsa[$id][$td['id']] = "";
				}

				$rbsa[$id][$td['id']] = sprint($rbsa[$id][$td['id']]);

				$rt .= "
					<tr>
						<td><input type='hidden' name='rbs[$id][$td[id]]' value='$td[id]'>$td[name]</td>
						<td>".CUR."</td>
						<td align='right'><input type='hidden' name='rbsa[$id][$td[id]]' value='".$rbsa[$id][$td['id']]."'>".$rbsa[$id][$td['id']]."</td>
					</tr>";

				$i++;
			}

			$rt .= "</table>";
		} else {
			$rt = "None";
		}

		// calculate age of employee (if intl., ie has passport num only), act asif under 65
		if ( ! empty($data["idnum"]) ) {
			// calculate age of employee
			$bd_year = 1900 + substr($data["idnum"], 0, 2);
			$bd_month = substr($data["idnum"], 2, 2);
			$bd_day = substr($data["idnum"], 4, 2);

			/* get the current financial year */
			db_conn("core");

			$sql = "SELECT yrname FROM active";
			$rslt = db_exec($sql) or errDie("Error fetching financial year.");
			if ( pg_num_rows($rslt) < 1 ) return "Please run quick setup first.";

			$fin_year = substr(pg_fetch_result($rslt, 0, 0), 1);

			$sql = "SELECT EXTRACT('year' FROM AGE('$fin_year-02-28', '$bd_year-$bd_month-$bd_day'))";
			$rslt = db_exec($sql) or errDie("Error calculating age at financial year end.");
			$age[$id] = pg_fetch_result($rslt, 0, 0);
		} else {
			$age[$id] = 1;
		}

		# The Paye
		$tyear = 12;
		switch($data["payprd"]){
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

		if ($data["saltyp"] != "h") {
			if ($myEmp["saltyp"] == "w"){
				$perhr = sprint ($basic_sal / $wh_actual);
			}else {
				$perhr = sprint(($basic_sal[$id] * $tyear) / ($data['hpweek'] * 52));
			}
		} else {
			$perhr = $basic_sal[$id];
		}

		$overamt[$id] = ($novert[$id] * ($perhr * $data['novert']));
		$overamt[$id] += sprint($hovert[$id] * ($perhr * $data['hovert']));

		# Multiply basic_sal add overtime
		if(isset($multi[$id])){
			$basic_sal[$id] = sprint($basic_sal[$id] * $multi[$id]);
			//$tyear = ($tyear/$multi[$id]);
		}

		// calculate loan fringe benefit amount for this month
		if ( ! empty($data["loanamt"])  && $data["gotloan"] == "t" && $data["loanamt"] > 0 ) {
			$loanpart = $loaninstall[$id] / $data["loanamt"];
			$fringe_loan[$id] = sprint($data["loanfringe"] * $loanpart);
		} else {
			$fringe_loan[$id] = "0.00";
		}

		$fringe_tot[$id] += $fringe_loan[$id];
		$fringe_tot[$id] += $comp_other[$id];
		$fringe_tot[$id] += $comp_ret[$id];

		/*
		$car_count = ($data["fringe_car1"] > 0?1:0) + ($data["fringe_car2"] > 0?1:0);

		// if car count is one and employee gets a travel allowance, that car's fringe benefit is calculated
		// as if the second car, and ALSO: contribitions/fuel/service amounts are not deducted from benefit
		$car1_travelall = $car_count == 1 && $all_travel[$id] > 0;

		if ( $car1_travelall ) {
			$PERC1 = 0.025;
		} else {
			$PERC1 = 0.04;
		}
		*/
		$car1_travelall = false;

		// calculate motor car fringe benefit
		if ( $data["fringe_car1"] > 0 ) {
			$PD = 0;
			if ( $data["fringe_car1_fuel"] == 1 && ! $car1_travelall ) {
				$PD += 0.0022;
			}

			if ( $data["fringe_car1_service"] == 1 && ! $car1_travelall ) {
				$PD += 0.0018;
			}

			$fringe_car1[$id] = $data["fringe_car1"] * ($data["fringe_car1"]>=$data["fringe_car2"]?0.025-$PD:0.04-$PD);

			if ( $data["fringe_car1_contrib"] > 0 && ! $car1_travelall ) {
				$fringe_car1[$id] -= ($data["fringe_car1_contrib"]);
			}
			
			$fringe_car1[$id] /= $divisor[$id];

			if ( $fringe_car1[$id] < 0 ) $fringe_car1[$id] = 0;
		} else {
			$fringe_car1[$id] = 0;
		}

		if ( $data["fringe_car2"] > 0 ) {
			$PD = 0;
			if ( $data["fringe_car2_fuel"] == 1 && ! $car1_travelall ) {
				$PD += 0.0022;
			}

			if ( $data["fringe_car2_service"] == 1 && ! $car1_travelall ) {
				$PD += 0.0018;
			}

			$fringe_car2[$id] = $data["fringe_car2"] * ($data["fringe_car2"]>$data["fringe_car1"]?0.025-$PD:0.04-$PD);

			if ( $data["fringe_car2_contrib"] > 0 && ! $car1_travelall ) {
				$fringe_car2[$id] -= ($data["fringe_car2_contrib"]);
			}

			$fringe_car2[$id] /= $divisor[$id];

			if ( $fringe_car2[$id] < 0 ) $fringe_car2[$id] = 0;
		} else {
			$fringe_car2[$id] = 0;
		}

		vsprint($fringe_car1[$id]);
		vsprint($fringe_car2[$id]);
		
		$fringe_tot[$id] += $fringe_car1[$id] + $fringe_car2[$id];
		$fringe_tot[$id] += $de_afteramount_emp[$id] + $de_beforeamount_emp[$id];
		$fringe_tot[$id] += $subs_taxable[$id];

		// calc medical fringe benefits
		if ($comp_medical[$id] > 0) {
			// calculate dependants after first one
			$tmp_deps = $data["emp_meddeps"] - 2;
			if ($tmp_deps < 0) $tmp_deps = 0;

			//2009
			// calculate paragraph 12A amount
			//first 2 dependants are 530 each (1060) rest is 320 each. 
			//$p12A_amt = ($data["emp_meddeps"] > 1 ? 1060 : 530) + ($tmp_deps * 320);

			//2010
			// calculate paragraph 12A amount
			//first 2 dependants are 625 each (1250) rest is 380 each. 
			//$p12A_amt = ($data["emp_meddeps"] > 1 ? 1250 : 625) + ($tmp_deps * 380);

			//2011
			// calculate paragraph 12A amount
			//first 2 dependants are 670 each (1340) rest is 410 each. 
			$p12A_amt = ($myEmp["emp_meddeps"] > 1 ? 1340 : 820) + ($tmp_deps * 410);

			// calculate taxable fringe benefit amount
			$fringe_medical[$id] = sprint($comp_medical[$id] - ($p12A_amt / $divisor[$id]));
			if ($fringe_medical[$id] < 0) {
				$fringe_medical[$id] = 0;
			}

			$fringe_tot[$id] += $fringe_medical[$id];
		} else {
			$fringe_medical[$id] = 0;
		}

		if ( $emp_pension[$id] > $basic_sal[$id] * 7.5/100 ) {
			$emp_mpension[$id] = $basic_sal[$id] * 7.5/100;
		} else {
			$emp_mpension[$id] = $emp_pension[$id];
		}

		// calculate total gross salary
		$grossal[$id] = $basic_sal[$id]
				+ $commission[$id]
				+ $abonus[$id]
				+ $overamt[$id] // overtime
				+ $bonus[$id] // monthly bonus
				+ $annual[$id] // annual bonus paid this month
				+ $all_beforeamount[$id] // allowances added before paye
				+ ( $all_travel[$id]*0.8 ) // 80% of travel allowance
				- $de_beforeamount[$id]; // deductions deducted before paye (non taxible)

		$grossal_2 = $grossal[$id];

		$taxed_all[$id] = $all_afteramount[$id] + ($all_travel[$id] * 0.8);

		$grossal_nodedall[$id] = $basic_sal[$id]
					+ $overamt[$id]
					+ $bonus[$id]
					+ $annual[$id]
					+ $all_travel[$id];

		#UIF HAX
		$uif_grossal[$id] = $grossal[$id];

		// pension/provident/ra: calculate deduction amounts, limiting them to maximum amount and only deducting
		// ONE of them for taxable income
		if ( $comp_pension[$id] + $emp_pension[$id] > 0 ) {
			$tmp = ($grossal_2 + $fringe_tot[$id]) * $tyear;
			$maxallowed = ($tmp * 0.075>1750)?$tmp * 0.075:1750;
			if ( $emp_mpension[$id] > $maxallowed ) {
				$tmp_ded = $maxallowed;
			} else {
				$tmp_ded = $emp_mpension[$id];
			}

			$grossal[$id] -= $tmp_ded;
		}

		if ( $comp_ret[$id] + $emp_ret[$id] > 0 ) {
			$tmp = ($grossal_2 + $fringe_tot[$id]) * $tyear;

			// if their is a pension contributions the percentage is 0
			if ( $comp_pension[$id] + $emp_pension[$id] + $comp_provident[$id] + $emp_provident[$id] > 0 ) {
				$PERC = 0;
			} else {
				$PERC = 0.15;
			}

			$maxallowed = ($tmp * $PERC > 1750) ? $tmp * $PERC : 1750;
			$maxallowed = ($maxallowed > 3500 - ($emp_pension[$id] * $divisor[$id] * 12)) ? $maxallowed : 3500 - $emp_pension[$id] * 12;

			if (($emp_ret[$id] + $comp_ret[$id]) * $divisor[$id] > $maxallowed / 12 ) {
				$tmp_ded = ($maxallowed / 12) / $divisor[$id];
			} else {
				$tmp_ded = $emp_ret[$id] + $comp_ret[$id];
			}

			$grossal[$id] -= $tmp_ded;
		}

		// calculate total paye salary
		// just remove annual this month, and add annual divided by 12
		// because paye is calculate for full twelve months and therefore
		// paye salary is average received each month
		$paye_salary[$id] = $grossal[$id]
				- $annual[$id]
				// special bonus not calculated annually - $bonus[$id] + ($bonus[$id]/12)
				+ $fringe_tot[$id]; // total fringe benefits;

		#UIF HAX
		$uif_paye_salary[$id] = $uif_grossal[$id] - $annual[$id] + $fringe_tot[$id];

		/* calculate uif */
		$tmp_remun = $paye_salary[$id] + $annual[$id] - $commission[$id] - $abonus[$id];

		#UIF HAX
		$uif_tmp_remun = $uif_paye_salary[$id] + $annual[$id] - $commission[$id] - $abonus[$id];

//		$comp_uif[$id] = sprint($tmp_remun * ($data["comp_uif"] / 100));
//		$emp_uif[$id] = sprint($tmp_remun * ($data["emp_uif"] / 100));

		#UIF HAX
		$comp_uif[$id] = sprint($uif_tmp_remun * ($data["comp_uif"] / 100));
		$emp_uif[$id] = sprint($uif_tmp_remun * ($data["emp_uif"] / 100));

		$uifmax = getCSetting("UIF_MAX");

		if ( $emp_uif[$id] > $uifmax ) {
			$emp_uif[$id] = sprint($uifmax);
		}

		if ( $comp_uif[$id] > $uifmax ) {
			$comp_uif[$id] = sprint($uifmax);
		}

		/* calculate sdl */
		$tmp_remun = $paye_salary[$id] + $annual[$id];
		if (getCSetting("SDLPAYABLE") == "y") {
			$tmp_sdl = $tmp_remun;

			if ( $age > 65 ) {
				$tmp_sdl -= $comp_medical[$id];
			}

			$comp_sdl[$id] = $tmp_sdl * ($data["comp_sdl"] / 100);
		} else {
			$comp_sdl[$id] = 0;
		}

		// a little hack, apparently the grossal is displayed wrong, in a strictly antisocial.co.za opinion,
		// i think the person who thinks that must suck
		$grossal[$id] += $comp_ret[$id];

		// add rest of travel allowance
		$grossal[$id] += $all_travel[$id] * 0.2;

		if(isset($mpaye[$id])) {
			$paye[$id] = $mpaye_amount[$id];
		} else {

			// calculate paye (take age of 65+ threshold into account)
			//2008
//			if ( ($age[$id] >= 65 && ($paye_salary[$id] * $tyear) < 69000) || ($paye_salary[$id] * $tyear) < 43000 ) {
			//2009
// 			if ( ($age[$id] >= 65 && ($paye_salary[$id] * $tyear) < 74000) || ($paye_salary[$id] * $tyear) < 46000 ) {
			//2010
//			if ( ($age[$id] >= 65 && ($paye_salary[$id] * $tyear) < 84200) || ($paye_salary[$id] * $tyear) < 54200 ) {
			//2011
			if ( ($age[$id] >= 65 && ($paye_salary[$id] * $tyear) < 88528) || ($paye_salary[$id] * $tyear) < 57000 ) {
				$paye[$id] = "0.00";
			} else {
				if ($data["payprd"] == "w" || $data["payprd"] == "f") {
					$paye_prd = "$month:$week";
				} else if ($data["payprd"] == "d") {
					$paye_prd = "$month:$pday";
				} else {
					$paye_prd = "$month";
				}

				$paye[$id] = calculate_paye($data, $paye_prd, $paye_salary[$id], $tyear, $age[$id]);

				if ( $annual[$id] > 0 ) {
					$tmp_bonpaye = calculate_paye($data, $paye_prd, $paye_salary[$id] + $annual[$id]/12, $tyear, $age[$id]);

					$paye[$id] += ($tmp_bonpaye * $tyear) - ($paye[$id] * $tyear);
				}
			}
		}

		$nonretfunding = $grossal[$id]
				- $paye[$id]
				- $loaninstall[$id]
				- $de_afteramount[$id]
				+ $de_afteramount_emp[$id]
				+ $all_afteramount[$id]
				- $emp_pension[$id]
				- $emp_medical[$id]
				- $emp_provident[$id]
				- $emp_uif[$id]
				- $emp_other[$id];

/*		$ret_max = (1800>($nonretfunding*0.15))?1800:($nonretfunding*0.15);

		if ( $comp_ret[$id] + $emp_ret[$id] > $ret_max ) {
			$comp_ret[$id] = $ret_max - $emp_ret[$id];

			if ( $comp_ret[$id] < 0 ) {
				$comp_ret[$id] = 0;
				$emp_ret[$id] = $ret_max;
			}
		}*/

		$nettpay[$id] = $basic_sal[$id]
				+ $overamt[$id]
				- $paye[$id]
				+ $commission[$id]
				+ $abonus[$id]
				- $loaninstall[$id]
				- $de_afteramount[$id]
				- $de_beforeamount[$id]
				+ $all_afteramount[$id]
				+ $all_beforeamount[$id]
				- $emp_pension[$id]
				- $emp_medical[$id]
				- $emp_ret[$id]
				- $emp_uif[$id]
				- $emp_provident[$id]
				- $emp_other[$id]
				+ $all_travel[$id]
				+ $annual[$id]
				+ $bonus[$id]
				- $data["fringe_car1_contrib"]
				- $data["fringe_car2_contrib"]
				+ $subs_total[$id];

		if (isset($rbsa[$id])) {
			$nettpay[$id] += array_sum($rbsa[$id]);
		}

		$nettpay[$id] = sprint($nettpay[$id]);
		//<td><table><tr><td><input type=checkbox name='mpaye[$id]'></td><td><input type=text size=8 name='mpaye_amount[$id]'></td></tr></table></td>

		$totded[$id] = sprint($de_beforeamount[$id] + $de_afteramount[$id]+$emp_pension[$id]+$emp_medical[$id]+$emp_provident[$id]+$emp_ret[$id]+$emp_other[$id]);
		$totded_employer[$id] = sprint($de_beforeamount_emp[$id] + $de_afteramount_emp[$id]+$comp_pension[$id]+$comp_medical[$id]+$comp_provident[$id]+$comp_ret[$id]+$comp_other[$id]);
		$totall[$id] = sprint($all_beforeamount[$id] + $all_afteramount[$id] + $all_travel[$id]);

		if(isset($mpaye[$id])) {
			$che = "<input type='hidden' name='mpaye[$id]' value=''>";
		} else {
			$che = "";
		}

		vsprint($grossal[$id]);
		vsprint($basic_sal[$id]);
		vsprint($bonus[$id]);
		vsprint($annual[$id]);
		vsprint($commission[$id]);
		vsprint($abonus[$id]);
		vsprint($all_travel[$id]);
		vsprint($loaninstall[$id]);
		vsprint($comp_pension[$id]);
		vsprint($emp_pension[$id]);
		vsprint($comp_provident[$id]);
		vsprint($emp_provident[$id]);
		vsprint($comp_ret[$id]);
		vsprint($emp_ret[$id]);
		vsprint($comp_medical[$id]);
		vsprint($emp_medical[$id]);
		vsprint($comp_other[$id]);
		vsprint($emp_other[$id]);
		vsprint($novert[$id]);
		vsprint($novert[$id]);
		vsprint($fringe_medical[$id]);
		vsprint($paye[$id]);

		$out .= "
			<input type='hidden' name='mpaye_amount[$id]' value='$mpaye_amount[$id]'>
			$che
			<tr bgcolor='$bgcolor'>
				<input type='hidden' name='overamt[$id]' value='$overamt[$id]'>
				<input type='hidden' name='comp_sdl[$id]' value='$comp_sdl[$id]'>
				<input type='hidden' name='process_comp_deductions[$id]' value='$process_comp_deductions[$id]'>
				<input type='hidden' name='grossal[$id]' value='$grossal[$id]'>
				<input type='hidden' name='grossal_nodedall[$id]' value='$grossal_nodedall[$id]'>
				<input type='hidden' name='totded[$id]' value='$totded[$id]'>
				<input type='hidden' name='totded_employer[$id]' value='$totded_employer[$id]'>
				<input type='hidden' name='totall[$id]' value='$totall[$id]'>
				<input type='hidden' name='emps[$id]' value='$id'>
				<input type='hidden' name='fringe_tot[$id]' value='$fringe_tot[$id]'>
				<input type='hidden' name='paye_salary[$id]' value='$paye_salary[$id]' />
				<input type='hidden' name='multi[$id]' value='$multi[$id]'>
				<input type='hidden' name='tyear[$id]' value='$tyear'>
				<input type='hidden' name='taxed_all[$id]' value='$taxed_all[$id]' />
				<td>$data[enum]</td>
				<td>$data[sname], $data[fnames]</td>
				<td><input type='hidden' name='basic_sal[$id]' value='$basic_sal_save[$id]' class='right'>$basic_sal[$id]</td>
				<td><input type='text' size='8' name='paidamount[$id]' id='paidamount[$id]' value='0.00'></td>
				<td><input type='hidden' name='bonus[$id]' value='$bonus[$id]' class='right'><input type='hidden' name='abonus[$id]' value='$abonus[$id]'>$abonus[$id]</td>
				<input type='hidden' name='annual[$id]' value='$annual[$id]' />
				<!--<td><input type='hidden' name='annual[$id]' value='$annual[$id]' class='right'>$annual[$id]</td>-->
				<td><input type='hidden' name='commission[$id]' value='$commission[$id]' class='right'>$commission[$id]</td>
				<td><input type='hidden' name='all_travel[$id]' value='$all_travel[$id]' class='right'>$all_travel[$id]</td>
				<td><input type='hidden' name='loaninstall[$id]' value='$loaninstall[$id]' class='right'>$loaninstall[$id]</td>
				<td><input type='hidden' name='comp_pension[$id]' value='$comp_pension[$id]' class='right'>$comp_pension[$id]</td>
				<td><input type='hidden' name='emp_pension[$id]' value='$emp_pension[$id]' class='right'>$emp_pension[$id]</td>
				<td><input type='hidden' name='comp_provident[$id]' value='$comp_provident[$id]' class='right'>$comp_provident[$id]</td>
				<td><input type='hidden' name='emp_provident[$id]' value='$emp_provident[$id]' class='right'>$emp_provident[$id]</td>
				<td><input type='hidden' name='comp_uif[$id]' value='$comp_uif[$id]' class='right'>$comp_uif[$id]</td>
				<td><input type='hidden' name='emp_uif[$id]' value='$emp_uif[$id]' class='right'>$emp_uif[$id]</td>
				<td><input type='hidden' name='comp_ret[$id]' value='$comp_ret[$id]' class='right'>$comp_ret[$id]</td>
				<td><input type='hidden' name='emp_ret[$id]' value='$emp_ret[$id]' class='right'>$emp_ret[$id]</td>
				<td><input type='hidden' name='comp_medical[$id]' value='$comp_medical[$id]' class='right'>$comp_medical[$id]</td>
				<td><input type='hidden' name='emp_medical[$id]' value='$emp_medical[$id]' class='right'>$emp_medical[$id]</td>

				<input type='hidden' name='comp_other[$id]' value='$comp_other[$id]' class='right'>
				<input type='hidden' name='emp_other[$id]' value='$emp_other[$id]' class='right'>
				<!--
				<td><input type='hidden' name='comp_other[$id]' value='$comp_other[$id]' class='right'>$comp_other[$id]</td>
				<td><input type='hidden' name='emp_other[$id]' value='$emp_other[$id]' class='right'>$emp_other[$id]</td>
				//-->

				<td><input type='hidden' name='novert[$id]' value='$novert[$id]'>$novert[$id]</td>
				<td><input type='hidden' name='hovert[$id]' value='$hovert[$id]'>$hovert[$id]</td>
				<td>$paydetails</td>
				<td nowrap><input type='hidden' name='fringe_medical[$id]' value='$fringe_medical[$id]'>".CUR." $fringe_medical[$id]</td>
				<td nowrap><input type='hidden' name='fringe_car1[$id]' value='$fringe_car1[$id]'>".CUR." $fringe_car1[$id]</td>
				<td nowrap><input type='hidden' name='fringe_car2[$id]' value='$fringe_car2[$id]'>".CUR." $fringe_car2[$id]</td>
				<td nowrap><input type='hidden' name='fringe_loan[$id]' value='$fringe_loan[$id]'>".CUR." $fringe_loan[$id]</td>
				<td>$fringes</td>
				<td>$Allowances</td>
				<td>$subsistence</td>
				<td>$Deductions</td>
				<td>$rt</td>
				<td>$grossal[$id]<input type='hidden' name='grossal[$id]' value='$grossal[$id]'></td>
				<td>$paye[$id]<input type='hidden' name='paye[$id]' value='$paye[$id]'></td>
				<td>$nettpay[$id]<input type='hidden' id='nettpay[$id]' name='nettpay[$id]' value='$nettpay[$id]'></td>
			</tr>";

		$payall_js .= "getObject('paidamount[$id]').value = getObject('nettpay[$id]').value;";
	}

	$out .= "
			<tr><td><br></td></tr>$send
			<script>
				function emp_payall() {
					$payall_js
				}
			</script>
			<tr>
				<td colspan='3'><input type='submit' value='&laquo; Correction'></td>
				<td colspan='5'><input type='button' value='Pay Selected Employees In Full After Processing' onClick='emp_payall();'></td>
				<td colspan='2' align='right'><input type='submit' value='Process Salaries &raquo;' name='button'></td>
				<td colspan='10' align='right'><input type='submit' value='Process Salaries &raquo;' name='button'></td>
			</tr>
		</table>
		</form>";
	return $out;

}



function write($_POST)
{

	$_POST = var_makesafe($_POST);
	extract($_POST);

	$week += 0;

	if(!isset($button)) {
		return enter($_POST);
	}

	if(!isset($date_day)) {
		exit;
	}

	require_lib("validate");
	$v = new  validate ();
	//$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");

	if(!checkdate($date_month, $date_day, $date_year)){
		$v->isOk ($date_day, "num", 10, 1, "Invalid date.");
	}

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

	$date = $date_year."-".$date_month."-".$date_day;

	$salconacc = gethook("accnum", "salacc", "name", "salaries control");
	$intrec = gethook("accnum", "salacc", "name", "interestreceived");
	$commacc = gethook("accnum", "salacc", "name", "Commission");
	$abonusacc = gethook("accnum", "salacc", "name", "Bonus");
	$payeacc = gethook("accnum", "salacc", "name", "PAYE");
	$uifacc = gethook("accnum", "salacc", "name", "UIF");
	$uifbal = gethook("accnum", "salacc", "name", "uifbal");
	$sdlbal = gethook("accnum", "salacc", "name", "sdlbal");
	$pa = gethook("accnum", "salacc", "name", "pension");
	$ma = gethook("accnum", "salacc", "name", "medical");
	$cash_account= gethook("accnum", "salacc", "name", "cash");
	$retire = gethook("accnum", "salacc", "name", "retire");
	$provident = gethook("accnum", "salacc", "name", "provident");

	$refnum = getrefnum($date);

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	db_conn('cubit');

	$i = 0;

	$Sl = "SELECT * FROM salset";
	$Ri = db_exec($Sl);

	if(pg_num_rows($Ri) > 0) {
		$con = true;
	} else {
		$con = false;
	}

	if($con) {
		$uifexp = $salacc;
		$sdlexp = $salacc;
		$pax = $salacc;
		$max = $salacc;
		$retiree = $salacc;
	}

	$Sl = "SELECT * FROM employees WHERE div='".USER_DIV."' ORDER BY sname,fnames";
	$Rr = db_exec($Sl) or errDie("Unable to get data.");

	$out = "<script>";
	$batch_setting = getCSetting("PRINT_PSLIPS_BATCH");
	if (isset($batch_setting) AND $batch_setting == "yes"){
		if ($printslip != "n") {
			$out .= "nhprinter('salwages/payslip-print.php?batch=";
		}
	}

	while($data = pg_fetch_array($Rr)) {
		if( ( ! isset($emps[$data['empnum']]) ) || isset($emps_already[$data["empnum"]]) ) {
			continue;
		}

		$providente = $data["expacc_provident"];
		$retiree = $data["expacc_ret"];
		$pax = $data["expacc_pension"];
		$uifexp = $data["expacc_uif"];
		$max = $data["expacc_medical"];
		$dedgenerale = $data["expacc_other"];
		$sdlexp = $data["expacc_sdl"];
		$salacc = $data["expacc_salwages"];
		$loanexp = $data["expacc_loan"];

		$id = $data['empnum'];
		
		$basic_sal_save[$id] = $basic_sal[$id];

		# Multiply basic_sal add overtime
		if (isset($multi[$id])) {
			$basic_sal[$id] = sprint($basic_sal[$id] * $multi[$id]);
		}

		$basic_sal[$id] = sprint($basic_sal[$id]);
		$bonus[$id] = sprint($bonus[$id]);
		$annual[$id] = sprint($annual[$id]);
		$abonus[$id] = sprint($abonus[$id]);
		$commission[$id] = sprint($commission[$id]);
		$all_travel[$id] = sprint($all_travel[$id]);
		$loaninstall[$id] = sprint($loaninstall[$id]);
		$comp_pension[$id] = sprint($comp_pension[$id]);
		$emp_pension[$id] = sprint($emp_pension[$id]);
		$comp_provident[$id] = sprint($comp_provident[$id]);
		$emp_provident[$id] = sprint($emp_provident[$id]);
		$comp_uif[$id] = sprint($comp_uif[$id]);
		$emp_uif[$id] = sprint($emp_uif[$id]);
		$comp_ret[$id] = sprint($comp_ret[$id]);
		$emp_ret[$id] = sprint($emp_ret[$id]);
		$comp_medical[$id] = sprint($comp_medical[$id]);
		$emp_medical[$id] = sprint($emp_medical[$id]);
		$comp_other[$id] = sprint($comp_other[$id]);
		$emp_other[$id] = sprint($emp_other[$id]);
		$novert[$id] += 0;
		$hovert[$id] += 0;
		$overamt[$id] = sprint($overamt[$id]);

		$sdl[$id] = sprint($comp_sdl[$id]);

		$ecost = $basic_sal[$id]
			+ $overamt[$id]
			+ $all_travel[$id]
			+ $bonus[$id]
			+ $annual[$id]
			+ $sdl[$id]
			+ $all_beforeamount[$id]
			+ $all_afteramount[$id]
			+ $comp_pension[$id]
			+ $comp_uif[$id]
			+ $comp_ret[$id]
			+ $comp_medical[$id]
			+ $comp_provident[$id]
			+ $comp_other[$id]
			+ $all_beforeamount[$id]
			+ $all_afteramount[$id];

		$ecost = sprint($ecost);

//		writetrans($uifexp,$uifbal , $date, $refnum, $comp_uif[$id], "Company UIF Contribution,  $data[fnames] $data[sname].");
		writetrans($sdlexp,$sdlbal , $date, $refnum, $sdl[$id], "SDL,  $data[fnames] $data[sname].");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance+($grossal_nodedall[$id]) WHERE empnum = '$data[empnum]'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($data['empnum'], $salacc, $date, $refnum,"Gross Salary" , $grossal_nodedall[$id], "c");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		writetrans($salacc, $salconacc, $date, $refnum, $grossal_nodedall[$id], "Gross Salary proccessing for employee,  $data[fnames] $data[sname].");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		if ( $emp_uif[$id] > 0 ) {
			db_conn('cubit');
			$Sl = "UPDATE employees SET balance=balance-($emp_uif[$id]) WHERE empnum = '$data[empnum]'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($data['empnum'], $uifacc, $date, $refnum, "UIF" ,  $emp_uif[$id], "d");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			writetrans($salconacc, $uifbal, $date, $refnum, $emp_uif[$id], "UIF for employee,  $data[fnames] $data[sname].");
		}

		if ( $comp_uif[$id] > 0) {
			writetrans($uifexp, $uifbal, $date, $refnum, $emp_uif[$id], "UIF for employee,  $data[fnames] $data[sname].");
		}

		if($commission[$id] > 0) {
			if($con) {
				$commacc = $salacc;
			}
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			db_conn('cubit');
			$Sl = "UPDATE employees SET balance=balance+($commission[$id]) WHERE empnum = '$data[empnum]'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");
			empledger($data['empnum'], $commacc, $date, $refnum,"Commission" ,  $commission[$id], "c");
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			writetrans($commacc, $salconacc, $date, $refnum, $commission[$id], "Commission for employee,  $data[fnames] $data[sname].");
		}

		if($abonus[$id] > 0) {
			if($con) {
				$abonusacc = $salacc;
			}
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance+($abonus[$id]) WHERE empnum = '$data[empnum]'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");
			empledger($data['empnum'], $abonusacc, $date, $refnum,"Bonus" ,  $abonus[$id], "c");
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			writetrans($abonusacc, $salconacc, $date, $refnum, $abonus[$id], "Bonus for employee,  $data[fnames] $data[sname].");
		}

		if($paye[$id] > 0){
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance-($paye[$id]) WHERE empnum = '$data[empnum]'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($data['empnum'], $payeacc, $date, $refnum,"PAYE" , $paye[$id] , "d");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			writetrans($salconacc, $payeacc, $date, $refnum, $paye[$id], "PAYE for employee,  $data[fnames] $data[sname].");
		}

		if($comp_pension[$id] > 0) {
			writetrans($pax,$pa , $date, $refnum, $comp_pension[$id], "Company Pension Contribution,  $data[fnames] $data[sname].");
		}

		if($comp_medical[$id] > 0) {
			writetrans($max,$ma , $date, $refnum, $comp_medical[$id], "Company Medical Contribution,  $data[fnames] $data[sname].");
		}

		if($emp_pension[$id] > 0) {
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance-($emp_pension[$id]) WHERE empnum = '$data[empnum]'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($data['empnum'], $pa, $date, $refnum,"Pension Contribution" , $emp_pension[$id], "d");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			writetrans($salconacc,$pa , $date, $refnum, $emp_pension[$id], "Employee Pension Contribution,  $data[fnames] $data[sname].");
		}

		if($emp_medical[$id] > 0) {

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance-($emp_medical[$id]) WHERE empnum = '$data[empnum]'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($data['empnum'], $ma, $date, $refnum,"Medical Contribution" , $emp_medical[$id], "d");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			writetrans($salconacc,$ma , $date, $refnum, $emp_medical[$id], "Employee Medical Contribution,  $data[fnames] $data[sname].");
		}

		if($comp_provident[$id] > 0) {
			writetrans($providente, $provident, $date, $refnum, $comp_provident[$id], "Company Provident Fund Contribution, $data[fnames] $data[sname].");
		}

		if($emp_provident[$id] > 0) {

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance-($emp_provident[$id]) WHERE empnum = '$data[empnum]'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($data['empnum'], $provident, $date, $refnum,"Provident Fund Contribution" , $emp_provident[$id], "d");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			writetrans($salconacc,$provident,$date,$refnum,$emp_provident[$id], "Provident Fund Contribution,  $data[fnames] $data[sname].");
		}

		if(false && $comp_other[$id] > 0) {
			writetrans($dedgenerale, $dedgeneral, $date, $refnum, $comp_other[$id], "Company Contribution to Other Deductions, $data[fnames] $data[sname].");
		}

		if(false && $emp_other[$id] > 0) {
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance-($emp_other[$id]) WHERE empnum = '$data[empnum]'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($data['empnum'], $dedgeneral, $date, $refnum,"Other Deductions Contribution" , $emp_other[$id], "d");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			writetrans($salconacc,$dedgeneral, $date, $refnum, $emp_other[$id], "Other Deductions Contribution,  $data[fnames] $data[sname].");
		}

		if($emp_ret[$id] > 0) {
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance-($emp_ret[$id]) WHERE empnum = '$data[empnum]'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($data['empnum'], $retire, $date, $refnum,"Retirement Annuity Contribution" , $emp_ret[$id], "d");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			writetrans($salconacc, $retire, $date, $refnum, $emp_ret[$id], "Employee Retirement Annuity Contribution,  $data[fnames] $data[sname].");
		}

		if($comp_ret[$id] > 0) {
			writetrans($retiree, $retire, $date, $refnum, $comp_ret[$id], "Company Retirement Annuity Contribution,  $data[fnames] $data[sname].");
		}

		$paidamount[$id] += 0;



		db_conn('cubit');
		$mons = "$month;";

		$due = sprint($nettpay[$id]-$paidamount[$id]);//, balance=balance+'$due'

		$sql = "
			UPDATE employees 
			SET lastpay = '$mons', loanamt = (loanamt - cast(float '$loaninstall[$id]' as numeric)), 
				loanfringe = (loanfringe - cast(float '$fringe_loan[$id]' as numeric)) 
			WHERE empnum = '$data[empnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to get employee details.");

		// check if loan is 0, then unmark loan as active, and store in archive
		$sql = "SELECT loanid FROM employees WHERE loanamt=0 AND empnum='$data[empnum]' AND gotloan='t'::bool";
		$rslt = db_exec($sql) or errDie("Error reading employee details for loan.");

		if ( pg_num_rows($rslt) > 0 ) {
			$loanid = pg_fetch_result($rslt, 0, 0);

			$sql = "UPDATE employees SET gotloan='f'::bool, loaninstall='0' WHERE empnum='$data[empnum]'";
			$rslt = db_exec($sql) or errDie("Unable to update employee loan status.");

			$sql = "UPDATE emp_loanarchive SET donedata=CURRENT_DATE WHERE id='$loanid'";
			$rslt = db_exec($sql) or errDie("Unable to archive loan.");

			$sql = "SELECT loanint_unpaid FROM employees WHERE empnum='$data[empnum]'";
			$rslt = db_exec($sql) or errDie("Error reading loan interest for installment.");

			$loanint[$id] = sprint(pg_fetch_result($rslt, 0, 0));
		} else if ( $loaninstall[$id] > 0 ) {
			$sql = "SELECT loanamt_tot, loanint_amt FROM employees WHERE empnum='$data[empnum]'";
			$rslt = db_exec($sql) or errDie("Error reading loan interest for installment.");

			$loan_tot = pg_fetch_result($rslt, 0, 0);
			$loan_totint = pg_fetch_result($rslt, 0, 1);

			$loanint[$id] = sprint(($loaninstall[$id] / $loan_tot) * $loan_totint);
		} else {
			$loanint[$id] = 0;
		}

		$sql = "
			UPDATE employees 
			SET loanint_unpaid = (loanint_unpaid - cast(float '$loanint[$id]' as numeric)) 
			WHERE empnum = '$data[empnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update employee interest.");

		if($loaninstall[$id] > 0 && !empty($loanexp)) {;
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance-($loaninstall[$id]) WHERE empnum = '$data[empnum]'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($data['empnum'], $loanexp, $date, $refnum,"Loan Instalment" , $loaninstall[$id] , "d");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			writetrans($salconacc, $loanexp, $date, $refnum, $loaninstall[$id] - $loanint[$id], "Loan Installment for employee,  $data[fnames] $data[sname].");
			writetrans($salconacc, $intrec, $date, $refnum, $loanint[$id], "Loan Interest for employee,  $data[fnames] $data[sname].");

			/* record this month's loan amounts (for reversal purposes) */
			// determine the salary period
			switch ($data["payprd"]) {
				case "d":
					$lprd = date_part($date, DP_DAY);
					break;
				case "m":
				case "f":
				case "w":
				default:
					$lprd = $week;
					break;
			}

			// record it!
			db_conn("cubit");
			$sql = "
				INSERT INTO cubit.emp_loaninstallments (
					empnum, fdate, fperiod, fmonth, fyear, installment, interest, 
					fringe
				) VALUES (
					'$data[empnum]', '$date', '$lprd', '$month', '".EMP_YEAR."', '$loaninstall[$id]', '$loanint[$id]', 
					'$fringe_loan[$id]'
				)";
			$rslt = db_exec($sql) or errDie("Error record loan fringe benefit.");
		}

		if(!isset($accid[$id])) {
			$accid[$id] = 0;
		}

		$totded[$id] = sprint($de_beforeamount[$id] 
						+ $de_afteramount[$id] 
						+ $emp_pension[$id] 
						+ $emp_medical[$id] 
						+ $emp_provident[$id] 
						+ $emp_ret[$id] 
						+ $emp_other[$id]);
		$totded_employer[$id] = sprint($de_beforeamount_emp[$id] 
									+ $de_afteramount_emp[$id]
									+ $comp_pension[$id] 
									+ $comp_medical[$id] 
									+ $comp_provident[$id] 
									+ $comp_ret[$id]
									+ $comp_other[$id]);

		$totall[$id] = sprint($totall[$id]);

		$np = $nettpay[$id];

		if(isset($rbsa[$id])) {
			$np = sprint($np - array_sum($rbsa[$id]));
		}

		if ($data["payprd"] == "d") {
	    	$week = $pday;
	    }

	    if (empty($novert[$id])) {
	    	$novert[$id] = "0";
	    }

	    if (empty($hovert[$id])) {
	    	$hovert[$id] = "0";
	    }

		db_conn("cubit");
		$Sl = "
			INSERT INTO cubit.salpaid (
				empnum, month, bankid, salary, comm, uifperc, uif, payeperc, 
				paye, totded, totded_employer, totallow, loanins, 
				tot_fringe, div, display, saldate, week, cyear, novert, 
				hovert, taxed_sal, hours, salrate, bonus
			) VALUES (
				'$data[empnum]', '$month', '$accid[$id]', '$np', '$commission[$id]', '0', '$emp_uif[$id]', '0', 
				'$paye[$id]', '$totded[$id]', '$totded_employer[$id]', '$totall[$id]', '$loaninstall[$id]', 
				'$fringe_tot[$id]', '".USER_DIV."', '','$date','$week', '".EMP_YEAR."', '$novert[$id]', 
				'$hovert[$id]', '$paye_salary[$id]', '$multi[$id]','$basic_sal_save[$id]', '$abonus[$id]'
			)";
		$Ry = db_exec($Sl) or errDie("Unable to insert record.");

		$pid = pglib_lastid ("salpaid", "id");

		// fringe benefits
		if ( isset($fringeid[$id]) ) {
			foreach ( $fringeid[$id] as $i => $fid ) {
//				empledger($data["empnum"], $fringeaccs[$id][$i], $date, $refnum,"Fringe Benefit, ".$fringename[$id][$i], $fringebens[$id][$i], "d");
//				writetrans($salconacc, $fringeaccs[$id][$i], $date, $refnum, $fringebens[$id][$i], "Fringe Benefit for employee, $data[fnames] $data[sname].");
			}
		}

/*		db_conn('cubit');
		// allowances
		$i = 0;
		$sql = "SELECT * FROM allowances WHERE div = '".USER_DIV."' ORDER BY allowance";
		$allowRslt = db_exec ($sql) or errDie ("Unable to select allowances from database.");
		if (pg_numrows ($allowRslt) > 0) {

			while ($myAllow = pg_fetch_array ($allowRslt)) {

				db_conn('cubit');

				# check if employee has allowance
				$sql = "SELECT * FROM empallow WHERE allowid='$myAllow[id]' AND empnum='$data[empnum]' AND div = '".USER_DIV."'";
				$empAllowRslt = db_exec ($sql) or errDie ("Unable to select allowance info from database.");
				if (pg_numrows ($empAllowRslt) > 0) {
					$dataAllow = pg_fetch_array ($empAllowRslt);

					if ( ($allowances[$id][$i]=sprint($allowances[$id][$i])) <= 0 ) continue;

					$Sl="INSERT INTO emp_inc(emp,year,period,date,payslip,type,code,description,qty,rate,amount,ex)
						VALUES ('$data[empnum]','$year','$month','$date','$pid','$myAllow[id]','','$myAllow[allowance]','1','0','".$allowances[$id][$i]."','')";
					$Ri=db_exec($Sl) or errDie("unable to insert data.1");

					if($con) {
						$myAllow['accid']=$salacc;
					}

					///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					db_conn('cubit');

					$Sl = "UPDATE employees SET balance=balance+(".$allowances[$id][$i].") WHERE empnum = '$data[empnum]'";
					$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

					empledger($data['empnum'], $allowaccs[$id][$i], $date, $refnum,"Allowance" , $allowances[$id][$i] , "c");

					///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					writetrans($allowaccs[$id][$i], $salconacc, $date, $refnum, $allowances[$id][$i], "Allowances for employee, $data[fnames] $data[sname].");
				}
				$i++;
			}
		}*/

		$frinupd = new dbUpdate("emp_frin", "cubit");
		if (isset($fringebens[$id])) {
			foreach ($fringebens[$id] as $key => $value) {
				$cols = grp(
					m("emp", $data["empnum"]),
					m("year", EMP_YEAR),
					m("period", $month),
					m("week", $week),
					m("fdate", $date),
					m("payslip", $pid),
					m("code", $key),
					m("description", sprint($fringename[$id][$key])),
					m("qty", 1),
					m("amount", $fringebens[$id][$key])
				);

				$frinupd->setCols($cols);
				$frinupd->run(DB_INSERT);
			}
		}

		if ($fringe_loan[$id] > 0) {
			$cols = grp(
				m("emp", $data["empnum"]),
				m("year", EMP_YEAR),
				m("period", $month),
				m("fdate", $date),
				m("payslip", $pid),
				m("code", "FRINLOAN"),
				m("description", "Loan Fringe Benefit"),
				m("qty", 1),
				m("amount", $fringe_loan[$id])
			);

			$frinupd->setCols($cols);
			$frinupd->run(DB_INSERT);
		}

		if ($fringe_medical[$id] > 0) {
			$cols = grp(
				m("emp", $data["empnum"]),
				m("year", EMP_YEAR),
				m("period", $month),
				m("fdate", $date),
				m("payslip", $pid),
				m("code", "FRINMED"),
				m("description", "Medical Fringe Benefit"),
				m("qty", 1),
				m("amount", $fringe_medical[$id])
			);

			$frinupd->setCols($cols);
			$frinupd->run(DB_INSERT);
		}

		if ($fringe_car1[$id] > 0) {
			$cols = grp(
				m("emp", $data["empnum"]),
				m("year", EMP_YEAR),
				m("period", $month),
				m("fdate", $date),
				m("payslip", $pid),
				m("code", "FRINCAR1"),
				m("description", "Fringe Benefit: Vehicle 1"),
				m("qty", 1),
				m("amount", $fringe_car1[$id])
			);

			$frinupd->setCols($cols);
			$frinupd->run(DB_INSERT);
		}

		if ($fringe_car2[$id] > 0) {
			$cols = grp(
				m("emp", $data["empnum"]),
				m("year", EMP_YEAR),
				m("period", $month),
				m("fdate", $date),
				m("payslip", $pid),
				m("code", "FRINCAR2"),
				m("description", "Fringe Benefit: Vehicle 2"),
				m("qty", 1),
				m("amount", $fringe_car2[$id])
			);

			$frinupd->setCols($cols);
			$frinupd->run(DB_INSERT);
		}

		// allowances
		if (isset($allowid[$id]) && is_array($allowid[$id]) && count($allowid[$id]) > 0) {
			foreach ($allowid[$id] as $k => $dummy) {
				if ( ($allowances[$id][$k]=sprint($allowances[$id][$k])) <= 0 ) continue;

				db_conn('cubit');
				$sql = "
					INSERT INTO emp_inc (
						emp, year, period, week, date, payslip, type, code, 
						description, qty, rate, amount, ex
					) VALUES (
						'$data[empnum]', '".EMP_YEAR."', '$month', '$week', '$date', '$pid', '".$allowid[$id][$k]."', '', 
						'".$allowname[$id][$k]."', '1', '0', '".$allowances[$id][$k]."', ''
					)";
				$rslt = db_exec($sql) or errDie("unable to insert data.1");

				if ($con) {
					$allowaccs[$id][$k] = $salacc;
				}

				$Sl = "UPDATE employees SET balance=balance+(".$allowances[$id][$k].") WHERE empnum = '$data[empnum]'";
				$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

				empledger($data['empnum'], $allowaccs[$id][$k], $date, $refnum,"Allowance" , $allowances[$id][$i] , "c");
				writetrans($allowaccs[$id][$k], $salconacc, $date, $refnum, $allowances[$id][$k], "Allowances for employee, $data[fnames] $data[sname].");
			}
		}

/*		db_conn('cubit');
		# Deductions
		$i = 0;
		$sql = "SELECT * FROM salded WHERE div = '".USER_DIV."' ORDER BY deduction";
		$deductRslt = db_exec ($sql) or errDie ("Unable to select deductions from database.");
		if (pg_numrows ($deductRslt) >0) {

			while ($myDeduct = pg_fetch_array ($deductRslt)) {

				db_conn('cubit');

				# check if employee has deduction
				$sql = "SELECT * FROM empdeduct WHERE dedid='$myDeduct[id]' AND empnum='$data[empnum]' AND div = '".USER_DIV."'";
				$empDeductRslt = db_exec ($sql) or errDie ("Unable to select Deduction info from database.");
				if (pg_numrows ($empDeductRslt) > 0) {
					$dataDeduct = pg_fetch_array ($empDeductRslt);
					if ( ($deductions[$id][$i]=sprint($deductions[$id][$i])) > 0 ) {
						# Debit salaries control acc and credit  acc
						$Sl="INSERT INTO emp_ded(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
							('$data[empnum]','$year','$month','$date','$pid','$myDeduct[id]','','$myDeduct[deduction]','1','0','".$deductions[$id][$i]."')";
						$Ri=db_exec($Sl) or errDie("unable to insert data.2");
					}

					if ( ($employer_deductions[$id][$i]=sprint($employer_deductions[$id][$i])) > 0 ) {
						$Sl="INSERT INTO emp_com(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
							('$data[empnum]','$year','$month','$date','$pid','$myDeduct[id]','','$myDeduct[deduction]','1','0','".$employer_deductions[$id][$i]."')";
						//$Ri=db_exec($Sl) or errDie("unable to insert data1.");
					}

					///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					db_conn('cubit');

					$Sl = "UPDATE employees SET balance=balance-(".$deductions[$id][$i].") WHERE empnum = '$data[empnum]'";
					$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

					empledger($data['empnum'], $myDeduct['accid'], $date, $refnum,"Deduction" , $deductions[$id][$i], "d");

					///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					writetrans($salconacc, $dedaccs[$id][$i], $date, $refnum, $deductions[$id][$i], "Deductions for employee, $data[fnames] $data[sname].");

     				$i++;
				}
			}
		}*/

		# Deductions
		if (isset($deductid[$id]) && is_array($deductid[$id]) && count($deductid[$id]) > 0) {
			foreach ($deductid[$id] as $k => $dummy) {
				if ( ($deductions[$id][$k] = sprint($deductions[$id][$k])) <= 0 ) continue;

				db_conn('cubit');
				# Debit salaries control acc and credit  acc
				$sql = "
					INSERT INTO emp_ded (
						emp, year, period, week, date, payslip, type, code, 
						description, qty, rate, amount
					) VALUES (
						'$data[empnum]', '".EMP_YEAR."', '$month', '$week', '$date', '$pid', '".$deductid[$id][$k]."', '', 
						'".$deductname[$id][$k]."', '1', '0', '".$deductions[$id][$k]."'
					)";
				$rslt = db_exec($sql) or errDie("unable to insert data.2");

				$sql = "UPDATE employees SET balance=balance-(".$deductions[$id][$k].") WHERE empnum = '$data[empnum]'";
				$rslt = db_exec($sql) or errDie("Unable to get employee details.");

				empledger($data['empnum'], $dedaccs[$id][$k], $date, $refnum,"Deduction" , $deductions[$id][$k], "d");
				writetrans($salconacc, $dedaccs[$id][$k], $date, $refnum, $deductions[$id][$k], "Deductions for employee, $data[fnames] $data[sname].");
			}
		}

		db_conn('cubit');

		$Sl = "SELECT * FROM rbs ORDER BY name";
		$Rl = db_exec($Sl) or errDie("Unable to get data.");

		if(pg_num_rows($Rl) > 0) {
			while($td = pg_fetch_array($Rl)) {
				$rbsa[$id][$td['id']] = sprint($rbsa[$id][$td['id']]);

				db_conn('cubit');
				$sql = "
					INSERT INTO emp_inc (
						emp, year, period, week, date, payslip, type, code, description, 
						qty, rate, amount, ex
					) VALUES (
						'$data[empnum]', '".EMP_YEAR."', '$month', '$week', '$date', '$pid', '$td[id]', '', '$td[name]', 
						'1', '0', '".$rbsa[$id][$td['id']]."', 'RBS'
					)";
				db_exec($sql) or errDie("unable to insert data.3");

				$sql = "UPDATE employees SET balance=balance+(".$rbsa[$id][$td['id']].") WHERE empnum = '$data[empnum]'";
				db_exec($sql) or errDie("Unable to get employee details.");

				empledger($data['empnum'], $td['account'], $date, $refnum,"Reimbursement" ,$rbsa[$id][$td['id']] , "c");
				writetrans($td['account'], $salconacc, $date, $refnum,$rbsa[$id][$td['id']], "Reimbursement for employee, $data[fnames] $data[sname].");

			}
		}

		if (isset($subsname)) {
			foreach ($subsname[$id] as $sid => $sn) {
				if(empty($subsamt[$id][$sid]) || $subsamt[$id][$sid] <= 0) {
					continue;
				}

				$samt = sprint($subsamt[$id][$sid]);

				$i++;

				db_conn('cubit');
				$cols = grp(
					m("emp", $data["empnum"]),
					m("year", EMP_YEAR),
					m("period", $month),
					m("week", $week),
					m("date", $date),
					m("payslip", $pid),
					m("type", $sid),
					m("code", ""),
					m("description", $subsname[$id][$sid]),
					m("qty", 1),
					m("rate", 0),
					m("amount", $samt),
					m("ex", "SUBS")
				);
				$subin = new dbUpdate("emp_inc", "cubit", $cols);
				$subin->run(DB_INSERT);

				$cols = grp(
					m("balance", raw("balance+($samt)"))
				);
				$subin->setTable("employees");
				$subin->setOpt($cols, wgrp(
					m("empnum", $data["empnum"])
				));
				$subin->run(DB_UPDATE);

				empledger($data["empnum"], $subsacc[$id][$sid], $date, $refnum, "Subsistence Allowance: ".$subsname[$id][$sid] , $samt, "c");
				writetrans($subsacc[$id][$sid], $salconacc, $date, $refnum, $samt, "Subsistence Allownace (".$subsname[$id][$sid].") for employee, $data[fnames] $data[sname].");
			}
		}

		if($paidamount[$id] > 0) {

			if($data['paytype'] == "Cash") {

				///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

				db_conn('cubit');

				$Sl = "UPDATE employees SET balance=balance-($paidamount[$id]) WHERE empnum = '$data[empnum]'";
				$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

				empledger($data['empnum'], $cash_account, $date, $refnum,"Payment(Cash)" ,  $paidamount[$id], "d");

				///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

				writetrans($salconacc, $cash_account, $date, $refnum, $paidamount[$id], "Salary Payment(Cash) for employee,  $data[fnames] $data[sname].");
			} elseif($data['paytype'] == "Ledger Account") {

				///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

				db_conn('cubit');

				$Sl = "UPDATE employees SET balance=balance-($paidamount[$id]) WHERE empnum = '$data[empnum]'";
				$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

				empledger($data['empnum'], $account[$id], $date, $refnum,"Payment(Ledger Account)" ,  $paidamount[$id], "d");

				///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

				writetrans($salconacc, $account[$id], $date, $refnum, $paidamount[$id], "Salary Payment(Ledger Account) for employee,  $data[fnames] $data[sname].");
			} else {
				$accid[$id] += 0;

				core_connect();

				$sql = "SELECT * FROM bankacc WHERE accid = '$accid[$id]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
				# check if link exists
				if(pg_numrows($rslt) <1){
					return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
				}
				$bank = pg_fetch_array($rslt);
				$bankacc = $bank["accnum"];

				///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

				db_conn('cubit');

				$Sl = "UPDATE employees SET balance=balance-($paidamount[$id]) WHERE empnum = '$data[empnum]'";
				$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

				empledger($data['empnum'], $bankacc, $date, $refnum,"Payment(Bank)" ,  $paidamount[$id], "d");

				///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

				if($paidamount[$id] > 0) {

					writetrans($salconacc, $bankacc, $date, $refnum, $paidamount[$id], "Salary Payment for employee(Bank),  $data[fnames] $data[sname].");
					banktrans($accid[$id], "withdrawal", $date, "$data[fnames] $data[sname]", "Salary Payment for employee,  $data[fnames] $data[sname]", 0, $paidamount[$id], $salconacc,$data['empnum']);
				}
			}
		}

		db_conn('cubit');

		if($comp_uif[$id] > 0) {
			$Sl = "INSERT INTO emp_com(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','UIFC','','UIF','1','0','$comp_uif[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data1.");
		}

		if ( $emp_uif[$id] > 0 ) {
			$Sl = "INSERT INTO emp_ded(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','UIFE','','UIF','1','0','$emp_uif[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data3.");
		}

		if($sdl[$id] > 0) {
			$Sl = "INSERT INTO emp_com(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','SDL','','SDL','1','0','$sdl[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data2.");
		}

		if($paye[$id] > 0) {
			$Sl = "INSERT INTO emp_ded(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','PAYE','','PAYE','1','0','$paye[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data3.");
		}

		if($basic_sal[$id] > 0) {
			$Sl = "INSERT INTO emp_inc(emp,year,period,week,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','INC','','Basic Salary','','1','0','$basic_sal[$id]','')";
			$Ri = db_exec($Sl) or errDie("unable to insert data4.");
		}

		if ( $data["loanpayslip"] > 0 ) {
			$Sl = "INSERT INTO emp_inc(emp,year,period,week,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
				('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','LOAN','','Employee Loan','','1','0','$data[loanpayslip]','')";
			$Ri = db_exec($Sl) or errDie("unable to insert loan data for employee income on payslip.");

			$sql = "UPDATE employees SET loanpayslip='0' WHERE empnum='$data[empnum]'";
			$rslt = db_exec($sql) or errDie("Error updating loan information for payslip.");
		}

		if($bonus[$id] > 0 && $data["payprd"] != "f" && $data["payprd"] != "w") {
			$Sl = "INSERT INTO emp_inc(emp,year,period,week,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','INCB','','Bonus','','1','0','$bonus[$id]','')";
			$Ri = db_exec($Sl) or errDie("unable to insert data5.");
		} else if ( $bonus[$id] > 0 ) {
			$Sl = "INSERT INTO emp_inc(emp,year,period,week,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','INCB','','Special Bonus/Additional Salary','','1','0','$bonus[$id]','')";
			$Ri = db_exec($Sl) or errDie("unable to insert data5.");
		}

		if($annual[$id] > 0) {
			$Sl = "INSERT INTO emp_inc(emp,year,period,week,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','INCAB','','Annual Bonus','','1','0','$annual[$id]','')";
			$Ri = db_exec($Sl) or errDie("unable to insert data5.");
		}

		if($commission[$id] > 0) {
			$Sl = "INSERT INTO emp_inc(emp,year,period,week,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','INCC','','Commission','','1','0','$commission[$id]','')";
			$Ri = db_exec($Sl) or errDie("unable to insert data6.");
		}

		if($abonus[$id] > 0) {
			$Sl = "INSERT INTO emp_inc(emp,year,period,week,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','INCAB','','Bonus','','1','0','$abonus[$id]','')";
			$Ri = db_exec($Sl) or errDie("unable to insert data6a.");
		}

		if($all_travel[$id] > 0) {
			$Sl = "INSERT INTO emp_inc(emp,year,period,week,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','INCT','','Travel Allowance','','1','0','$all_travel[$id]','')";
			$Ri = db_exec($Sl) or errDie("unable to insert data7.");
		}

		if($loaninstall[$id] > 0) {
			$Sl = "INSERT INTO emp_ded(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','DEDL','','Loan Repayment','1','0','$loaninstall[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data8.");
		}

		if($comp_pension[$id] > 0) {
			$Sl = "INSERT INTO emp_com(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','COMP','','Pension','1','0','$comp_pension[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data9.");
		}

		if($emp_pension[$id] > 0) {
			$Sl = "INSERT INTO emp_ded(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','DEDP','','Pension','1','0','$emp_pension[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data10.");
		}

		if($comp_provident[$id] > 0) {
			$Sl = "INSERT INTO emp_com(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','COMV','','Provident','1','0','$comp_provident[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data9.");
		}

		if($emp_provident[$id] > 0) {
			$Sl = "INSERT INTO emp_ded(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','DEDV','','Provident','1','0','$emp_provident[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data10.");
		}

		if($comp_other[$id] > 0) {
			$Sl = "INSERT INTO emp_com(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','COMO','','Other Deductions','1','0','$comp_other[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data9.");
		}

		if($emp_other[$id] > 0) {
			$Sl = "INSERT INTO emp_ded(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','DEDO','','Other Deductions','1','0','$emp_other[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data10.");
		}

		if($comp_ret[$id] > 0) {
			$Sl = "INSERT INTO emp_com(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','COMR','','Retirement Annuity Fund','1','0','$comp_ret[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data9.");
		}

		if($emp_ret[$id] > 0) {
			$Sl = "INSERT INTO emp_ded(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','DEDR','','Retirement Annuity Fund','1','0','$emp_ret[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data10.");
		}

		if ( $data["fringe_car1_contrib"] > 0 ) {
			$Sl = "INSERT INTO emp_ded(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','DEDR','','Motorcar 1 Contribution for Use','1','0','$data[fringe_car1_contrib]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data10.");
		}

		if ( $data["fringe_car2_contrib"] > 0 ) {
			$Sl = "INSERT INTO emp_ded(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','DEDR','','Motorcar 2 Contribution for Use','1','0','$data[fringe_car2_contrib]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data10.");
		}

		if($comp_medical[$id] > 0) {
			$Sl = "INSERT INTO emp_com(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','COMM','','Medical Contribution','1','0','$comp_medical[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data.11");
		}

		if($emp_medical[$id] > 0) {
			$Sl = "INSERT INTO emp_ded(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','DEDM','','Medical Contribution','1','0','$emp_medical[$id]')";
			$Ri = db_exec($Sl) or errDie("unable to insert data.12");
		}

		if ( $overamt[$id] > 0 ) {
			$Sl = "INSERT INTO emp_inc(emp,year,period,week,date,payslip,type,code,description,qty,rate,amount,ex) VALUES
			('$data[empnum]','".EMP_YEAR."','$month','$week','$date','$pid','INCO','','Over Time','1','0','$overamt[$id]','')";
			$Ri = db_exec($Sl) or errDie("unable to insert data.13");
		}

		$ecost += 0;

		db_conn('cubit');

		$Sl = "SELECT * FROM empc WHERE emp='$data[empnum]'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) > 0) {
			while($cdata = pg_fetch_array($Ri)) {
				db_conn('cubit');
				$sql = "SELECT * FROM costcenters WHERE ccid = '$cdata[cid]'";
				$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
				$cc = pg_fetch_array ($ccRslt);

				$amount = sprint($ecost*$cdata['amount']/100);

				db_conn(PRD_DB);

				$sql = "
					INSERT INTO cctran (
						ccid, trantype, typename, edate, description, 
						amount, username, div
					) VALUES (
						'$cc[ccid]', 'ct', 'Salary', '$date', 'Salary for employee,  $data[fnames] $data[sname]', 
						'$amount', '".USER_NAME."', '".USER_DIV."'
					)";
				$insRslt = db_exec ($sql) or errDie ("Unable to retrieve insert Cost center amounts into database.");
			}
		}

		$batch_setting = getCSetting("PRINT_PSLIPS_BATCH");

		if (isset($batch_setting) AND $batch_setting == "yes"){
			if ($printslip != "n") {
				$out.= "$pid|";
			}
		}else {
			if ($printslip != "n") {
				$out.= "nhprinter('salwages/payslip-print.php?id=$pid',$pid);";
			}
		}

	}

	$batch_setting = getCSetting("PRINT_PSLIPS_BATCH");
	if (isset($batch_setting) AND $batch_setting == "yes"){
		$out .= "',$pid);</script>";
	}else {
		if ($printslip != "n") {
			$out.= "</script>";//spmove('../main.php');</script>";
		}
	}

	if ($printslip == "n") {
		$out = "
		<h3>Process Employee Salaries</h3>
		Successfully processed salaries for selected employees.<br /><br />";
	}

	/* update printslip setting */
	setCSetting("EMP_PRINTSLIP", $printslip);

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	//$out="";

	return $out;
}

// function to check accounts that will be used in the write function against blocked main accounts
function block_check($acc, $empname = false, $debug = false)
{

	global $global_empnum;
	global $block_check_errs, $block_check_accs;

	if ( (! isset($block_check_accs[$acc])) && isb($acc) ) {
		$block_check_accs[$acc] = 1;

		db_conn("core");
		$sql = "SELECT accname FROM accounts WHERE accid='$acc'";
		$rslt = db_exec($sql) or errDie("Error reading account name for blocked account.");

		$accname = pg_fetch_result($rslt, 0, 0);

		if ($empname === false ) {
			$block_check_errs .= "<li class='err'>$accname is a blocked account. Please use the appropriate feature to
						change the usage of this account before you continue with processing salaries.</li>";
		} else {
			$block_check_errs .= "<li class='err'>$accname is a blocked account. Unable to process employee $empname. Click
				<a href='empacc-link.php?empnum=$global_empnum'>here</a> to change the
				account to appropriate/custom account before you continue with reversing salaries.</li>";
		}

		return false;
	}
	return true;

}



function finish_block_check() {
	global $block_check_errs;

	if ( empty($block_check_errs) ) return;
	$OUTPUT = "<h3>Process Employee Salary</h3>$block_check_errs";
	require("../template.php");
}



function banktrans($bankacc, $trantype, $date, $name, $details, $cheqnum, $amount, $accinv, $empnum)
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
			bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, div, 
			fcid, empnum
		) VALUES (
			'$bankacc', '$trantype', '$date', '$name', '$details', '$cheqnum', '$amount', 'no', '$accinv', '".USER_DIV."', 
			(SELECT fcid FROM cubit.currency WHERE curcode='ZAR'), '$empnum'
		)";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);
}

?>
