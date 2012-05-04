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

# get settings
require ("settings.php");
require ("core-settings.php");
require ("salwages/emp-functions.php");
require_lib("time");

## Decide
if (isset($_REQUEST["key"])) {
	switch (strtolower($_REQUEST["key"])) {
		case "emp":
			$OUTPUT = slctEmployee();
			break;
		case "slip":
			$OUTPUT = slip($_REQUEST);
			break;
		case "export to spreadsheet":
			$OUTPUT = export($_REQUEST);
			break;
		default:
			$OUTPUT = slctDate();
	}
}else{
	$OUTPUT = slctDate();
}

# display output
require ("template.php");




function slctEmployee()
{

	db_connect ();

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

	$sql = "SELECT enum,empnum, sname, fnames FROM employees WHERE div = '".USER_DIV."' $egsearch ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		$employees = array ();
	//	return "No employees found in database.<p>"
	//		.mkQuickLinks();
	}else {
		$employees = array();
		while ($d = pg_fetch_array ($empRslt)) {
			$employees[$d["empnum"]]= "$d[sname], $d[fnames] ($d[enum])";
		}
	}
	$fields = array(
		"empnum" => 0,
		"mon" => date("m")
	);

	foreach ($fields as $fname => $dflt) {
		if (!isset($$fname)) $$fname = $dflt;
	}

	$get_egroups = "SELECT * FROM emp_groups ORDER BY emp_group";
	$run_egroups = db_exec($get_egroups) or errDie ("Unable to get employee group information.");
	if (pg_numrows($run_egroups) < 1){
		$emp_group_drop = "<input type='hidden' name='emp_group[]' value='0'>No Employee Groups Found.";
	}else {
		$emp_group_drop = "<select name='emp_group[]' multiple size='5'>";
		$emp_group_drop .= "<option value='0'>All</option>";
		while ($garr = pg_fetch_array ($run_egroups)){
			$emp_group_drop .= "<option value='$garr[id]'>$garr[emp_group]</option>";
		}
		$emp_group_drop .= "</select>";
	}

	$slctEmployee = "
		<h3>Select month to view</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='slip'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Select Month</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>".empMonList("mon", $mon)."</td>
			</tr>
			<tr>
				<th>Employee Group</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>$emp_group_drop</td>
			</tr>
			<tr>
				<th>Employee</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>".extlib_cpsel("empnum", $employees, $empnum)."</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='View &raquo;'></td>
			</tr>
		</table>
		</form>"
	.mkQuickLinks();
	return $slctEmployee;

}



# select employee
function slctDate()
{

	db_connect ();

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

	$sql = "SELECT enum,empnum, sname, fnames FROM employees WHERE div = '".USER_DIV."' $egsearch ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
	//	return "No employees found in database.<p>"
	//		.mkQuickLinks();
	}

	$employees = array(
		"0" => "All"
	);
	while ($d = pg_fetch_array ($empRslt)) {
		$employees[$d["empnum"]] = "$d[sname], $d[fnames] ($d[enum])";
	}

	$fields = array(
		"empnum" => 0,
		"from_year" => DATE_YEAR,
		"from_month" => DATE_MONTH,
		"from_day" => 1,
		"to_year" => DATE_YEAR,
		"to_month" => DATE_MONTH,
		"to_day" => getDaysInMonth(DATE_MONTH, DATE_YEAR)
	);

	foreach ($fields as $fname => $dflt) {
		if (!isset($$fname)) $$fname = $dflt;
	}

	$get_egroups = "SELECT * FROM emp_groups ORDER BY emp_group";
	$run_egroups = db_exec($get_egroups) or errDie ("Unable to get employee group information.");
	if (pg_numrows($run_egroups) < 1){
		$emp_group_drop = "<input type='hidden' name='emp_group[]' value='0'>No Employee Groups Found.";
	}else {
		$emp_group_drop = "<select name='emp_group[]' multiple size='5'>";
		$emp_group_drop .= "<option value='0'>All</option>";
		while ($garr = pg_fetch_array ($run_egroups)){
			$emp_group_drop .= "<option value='$garr[id]'>$garr[emp_group]</option>";
		}
		$emp_group_drop .= "</select>";
	}

	db_connect ();
	
	$get_years = "SELECT distinct (cyear) FROM salpaid ORDER BY cyear";
	$run_years = db_exec ($get_years) or errDie ("Unable to get salary processed years.");
	if (pg_numrows ($run_years) < 1){
		$sal_year_drop = "<input type='hidden' name='salyear' value='".EMP_YEAR."'>No Previously Processed Salaries Found.";
	}else {
		$sal_year_drop = "<select name='salyear'>";
		while ($sarr = pg_fetch_array ($run_years)){
			if ($sarr['cyear'] == EMP_YEAR){
				$sal_year_drop .= "<option value='$sarr[cyear]' selected>$sarr[cyear]</option>";
			}else {
				$sal_year_drop .= "<option value='$sarr[cyear]'>$sarr[cyear]</option>";
			}
		}
		$sal_year_drop .= "</select>";
	}

	$OUT = "
		<h3>Select date range to view</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='slip'>
			<tr>
				<th>Employee</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>".extlib_cpsel("empnum", $employees, $empnum)."</td>
			</tr>
			<tr>
				<th>Employee Group</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>$emp_group_drop</td>
			</tr>
			<tr>
				<th colspan='2'>Date Range</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'  colspan='2'>
					".mkDateSelect("from",$from_year, $from_month, $from_day)."
					&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
					".mkDateSelect("to", $to_year, $to_month, $to_day)."
				</td>
			</tr>
			<tr>
				<th>Select Salary Financial Year</th>
			</tr>
			<tr class='".bg_class()."'>
				<td align='center'>$sal_year_drop</td>
			</tr>
		    <tr>
		    	<td colspan='2' align='right'><input type='submit' value='View &raquo;'></td>
		    </tr>
	    </table>
	    </form>"
	.mkQuickLinks();
	return $OUT;

}



# confirm new data
function slip ($_POST, $pure = false)
{

	# get vars
	extract ($_POST);

	$empnum += 0;

	# validate input
	require_lib("validate");
	$v = new  validate();

	if (isset($from_day)) {
		$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
		$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
		$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
		$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
		$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
		$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
		# mix dates
		$fromdate = $from_year."-".$from_month."-".$from_day;
		$todate = $to_year."-".$to_month."-".$to_day;

		if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
		}
		if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
		}
	} else if (isset($mon)) {
		$v->isOk ($mon, "num", 1,2, "Invalid month selected.");
	}
	$v->isOk ($empnum, "num", 1,14, "Invalid employee selected.");

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


	#check what we have permission to
	$get_perm = "SELECT payroll_groups FROM users WHERE username = '$_SESSION[USER_NAME]' LIMIT 1";
	$run_perm = db_exec ($get_perm) or errDie ("Unable to get payroll groups permission information.");
	if (pg_numrows ($run_perm) > 0){
		$parr = pg_fetch_array ($run_perm);
		if (strlen ($parr['payroll_groups']) > 0){
			$pay_grps = explode (",",$parr['payroll_groups']);
		}else {
			$pay_grps = array ();
		}
	}else {
		$pay_grps = array ();
	}


	if (isset($emp_group) AND is_array ($emp_group)){
		$emp_groups = array ();
		$emps = array ();
		foreach ($emp_group AS $each){

			if (!in_array ($each,$pay_grps)) 
				continue;

			$emp_groups[] = $each;

			$get_emp = "SELECT empnum FROM employees WHERE emp_group = '$each'";
			$run_emp = db_exec($get_emp) or errDie ("Unable to get employees information.");
			if (pg_numrows($run_emp) > 0){
				while ($earr = pg_fetch_array ($run_emp))
					$emps[] = $earr['empnum'];
			}
		}
	}else {

		#check for which groups we have perm
		$get_check = "SELECT payroll_groups FROM users WHERE username = '$_SESSION[USER_NAME]' LIMIT 1";
		$run_check = db_exec ($get_check) or errDie ("Unable to get employees group permissions.");
		if (pg_numrows ($run_check) > 0){
			$earr = pg_fetch_array ($run_check);
			if (strlen ($earr['payroll_groups']) > 0){
			    $eperms = explode (",",$earr['payroll_groups']);
			    $egsearch = " AND (emp_group = '".implode ("' OR emp_group = '",$eperms)."')";
			}else {
				$egsearch = "AND FALSE";
			}
		}

		$emp_groups[] = array (0 => '0');
		$get_emp = "SELECT empnum FROM employees WHERE true $egsearch";
		$run_emp = db_exec($get_emp) or errDie ("Unable to get employees information.");
		while ($earr = pg_fetch_array ($run_emp))
			$emps[] = $earr['empnum'];
	}

	if (!isset ($emps))
		$emps = array (0);

	if (in_array ('0',$emp_groups))
		$show_all = TRUE;
	else 
		$show_all = FALSE;

	$totgross = 0;
	$totcomm = 0;
	$totins = 0;
	$totuif = 0;
	$totpaye = 0;
	$totded = 0;
	$totsal = 0;

	if (!isset ($salyear) OR strlen ($salyear) < 1){
		$salyear = EMP_YEAR;
	}

	/* get employee details */
	db_connect ();
	if (isset($from_day)) {
		$retfunc = "slctDate";
		if ($empnum != "0") {
			#if not all then use selected employee
			$empw = "empnum='$empnum' AND ";
		}else {
			#else use all payslips ... but only with emps in selected group
			if (!$show_all){
				$empw = "";
				foreach ($emps AS $each){
					$empw .= "empnum='$each' OR ";
				}
				$empw .= "empnum='$each'";
			}
		}

		if (substr($empw,-4) == "AND "){
			$empw = substr($empw,0,-4);
		}
		if(!isset($empw))
			$empw = "true";

		$sql = "SELECT 'salp' AS paytype, * FROM salpaid
				WHERE ($empw) AND saldate >= '$fromdate' AND saldate <= '$todate' AND div = '".USER_DIV."' AND cyear='$salyear'
				UNION
				SELECT 'salr' AS paytype, * FROM salr
				WHERE ($empw) AND saldate >= '$fromdate' AND saldate <= '$todate' AND div = '".USER_DIV."' AND cyear='$salyear'
				ORDER BY true_ids ASC";
	} else if (isset($empnum)) {
		$retfunc = "slctEmployee";
		$sql = "SELECT 'salp' AS paytype, * FROM salpaid
				WHERE month='$mon' AND empnum='$empnum' AND div = '".USER_DIV."' AND cyear='$salyear'
				UNION
				SELECT 'salr' AS paytype, * FROM salr
				WHERE month='$mon' AND empnum='$empnum' AND div = '".USER_DIV."' AND cyear='$salyear'
				ORDER BY true_ids ASC";
	} else {
		invalid_use();
	}
	$pRslt = db_exec ($sql) or errDie ("Unable to select employee payments from database.");

	if (pg_numrows ($pRslt) < 1) {
		return "<li class='err'> - Employee salaries matching the search criteria not found.</li>".$retfunc();
	}

	$slip = "";
	if (pg_numrows ($pRslt) > 0) {
		$empdata = array();
		$empcounter = array();

		while($pay = pg_fetch_array($pRslt)){
			$en = $pay["empnum"];
			$mwid = "$pay[month]:$pay[week]";

			if (!isset($empdata[$en])) {
				$empdata[$en] = array();
			}

			if (!isset($empdata[$en][$mwid])) {
				$empdata[$en][$mwid] = array(
					"gross" => 0,
					"comm" => 0,
					"loanins" => 0,
					"uif" => 0,
					"paye" => 0,
// 					"totded" => array(),
					"salary" => 0,
					"saldate" => "",
					"payslip" => 0
				);
			}

			$ed = &$empdata[$en][$mwid];

			$gross = $pay['salary'] - $pay['totallow'] - $pay['comm'] + $pay['totded']
					+ $pay['uif'] + $pay['paye'] + $pay['loanins'];

			$ed["saldate"] = $pay["saldate"];

			if ($pay["paytype"] == "salp") {
				$ed["gross"] += $gross;

				$ed["comm"] += $pay["comm"];
				$ed["loanins"] += $pay["loanins"];
				$ed["uif"] += $pay["uif"];
				$ed["paye"] += $pay["paye"];
// 				$ed["totded"] += $pay["totded"];
				$ed["salary"] += $pay["salary"];

				$ed["payslip"] = $pay["id"];

				$totgross += $gross;
				$totcomm += $pay['comm'];
				$totins += $pay['loanins'];
				$totuif += $pay['uif'];
				$totpaye += $pay['paye'];
				$totded += $pay['totded'];
				$totsal += $pay['salary'];
			} else {
				$ed["gross"] -= $gross;

				$ed["comm"] -= $pay["comm"];
				$ed["loanins"] -= $pay["loanins"];
				$ed["uif"] -= $pay["uif"];
				$ed["paye"] -= $pay["paye"];
// 				$ed["totded"] -= $pay["totded"];
				$ed["salary"] -= $pay["salary"];

				$ed["payslip"] = "$pay[id]&rev=true";

				$totgross -= $gross;
				$totcomm -= $pay['comm'];
				$totins -= $pay['loanins'];
				$totuif -= $pay['uif'];
				$totpaye -= $pay['paye'];
				$totded -= $pay['totded'];
				$totsal -= $pay['salary'];
			}

			$get_deds = "SELECT distinct(type) FROM emp_ded WHERE payslip = '$pay[id]'";
			$run_deds = db_exec ($get_deds) or errDie ("Unable to get salary deduction information.");
			if (pg_numrows ($run_deds) > 0){
// 				$deductions = "";
// 				$ed["totded"] = array()
				$colspan=0;
				while ($darr = pg_fetch_array ($run_deds)){
					$darr['type'] += 0;
					if ($darr['type'] > 0){
print "adding a deduction<br>";

						$get_amt = "SELECT amount, description FROM emp_ded WHERE payslip = '$pay[id]' AND type = '$darr[type]' LIMIT 1";
						$run_amt = db_exec ($get_amt) or errDie ("Unable to get employee deduction amount.");
						$deduction_heading .= "<th>".pg_fetch_result ($run_amt,0,1)."</th>";
						$ed["totded"][] = "<td nowrap>".CUR." ".sprint(pg_fetch_result ($run_amt,0,0))."</td>";
// 						$deductions .= "<td nowrap>".CUR." ".sprint($darr['amount'])."</td>";
						$colspan++;
					}
				}
			}else {
// 				$deductions = "";
				$colspan=1;
			}

		}

print "----------<br>";
print "<pre>";
var_dump ($ed);
print "</pre>";
print "<br>>>>>>>>>>>>>>>>>>>>><br>";
// print "<pre>";
// var_dump ($


		foreach ($empdata as $empnum => $months) {
			foreach ($months as $monthweek => $sal) {
				list($month, $week) = explode(":", $monthweek);

				if (($emp = qryEmployee($empnum, "fnames, sname, basic_sal, payprd")) === false) {
					$emp = qryLEmployee($empnum, "fnames, sname, basic_sal, payprd");
				}

				// not a date range but a single employee, store the name
				if (!isset($from_day)) {
					$empname = "$emp[fnames] $emp[sname]";
				}

				/* create month week description */
				$mw_desc = getMonthName($month);

				// weekly
				if ($emp["payprd"] == "w") {
					$mw_desc .= ", Week $week";
				// fortnightly
				} else if ($emp["payprd"] == "f") {
					if ($week == 1) {
						$week = "1-2";
					} else if ($week == 2) {
						$week = "3-4";
					} else {
						$week = "5";
					}
					$mw_desc .= ", Week $week";
				}
print "<pre>";
var_dump ($sal["totded"]);
print "</pre>";
				$bgColor = bgcolorg();
				$slip .= "
					<tr class='".bg_class()."'>
						<td>$emp[fnames] $emp[sname]</td>
						<td nowrap>".CUR." ".sprint($sal["gross"])."</td>
						<td nowrap>".CUR." ".sprint($sal["comm"])."</td>
						<td nowrap>".CUR." ".sprint($sal["loanins"])."</td>
						<td nowrap>".CUR." ".sprint($sal["uif"])."</td>
						<td nowrap>".CUR." ".sprint($sal["paye"])."</td>
						".implode("",$sal["totded"])."
						<td nowrap>".CUR." ".sprint($sal["salary"])."</td>
						<td nowrap>$mw_desc</td>
						<td nowrap>$sal[saldate]</td>";

				if (!$pure) {
					$slip .= "
						<td><a href='payslip-view.php?empnum=$empnum&id=$sal[payslip]'>View</a></td>
						<td><a target='_blank' href='payslip-print.php?id=$sal[payslip]'>Print</a></td>";
				}

				$slip .= "</tr>";
			}
		}

		# Format the totals
		$totgross = sprint($totgross);
		$totcomm = sprint($totcomm);
		$totins = sprint($totins);
		$totuif = sprint($totuif);
		$totpaye = sprint($totpaye);
		$totded = sprint($totded);
		$totsal = sprint($totsal);

		$slip .= "
			<tr class='bg-even'>
				<td><b>Total</b></td>
				<td nowrap><b>".CUR." $totgross</b></td>
				<td nowrap><b>".CUR." $totcomm</b></td>
				<td nowrap><b>".CUR." $totins</b></td>
				<td nowrap><b>".CUR." $totuif</b></td>
				<td nowrap><b>".CUR." $totpaye</b></td>
				<td nowrap><b>".CUR." $totded</b></td>
				<td nowrap><b>".CUR." $totsal</b></td>
				<td colspan='4'></td>
			</tr>";
	}else{
		return "<li> - There are no salary payments for the selected month</li>";
	}


	if (isset($from_day)) {
		$title = "<h3>Salaries Paid $fromdate TO $todate</h3>";
	} else {
		$title = "<h3>Salaries for $empname</h3>";
	}

	$slip = "
		<center>
		$title
		<table ".TMPL_tblDflts." width='70%'>
			<tr>
				<th>Employee</th>
				<th>Gross Salary</th>
				<th>Commission</th>
				<th>Low or interest free loan</th>
				<th>UIF</th>
				<th>PAYE</th>
				<th colspan='$colspan'>Deductions</th>
				<th>Nett Income</th>
				<th>Month/Week</th>
				<th>Payment Date</th>
				".(!$pure?"<th colspan='2'>Options</th>":"")."
			</tr>
			<tr>
				<th colspan='6'></th>
				$deduction_heading
				<th colspan='5'></th>
			</tr>
			$slip
			".TBL_BR;

	if (!$pure) {
		$slip .= "
				<form action='".SELF."' method='POST'>
				".array2form($_REQUEST)."
				<tr>
					<td colspan='2'><input name=key type=submit value='Export to Spreadsheet'></td>
				</tr>
			</form>"
			.mkQuickLinks(
				ql("../admin-employee-add.php", "Add Employee")
			)."
			</td></tR>";
	}

	$slip .= "
		</table>
		</center>";
	return $slip;

}



function export ($_POST)
{

	$slip = clean_html(slip($_POST, true));
	include("../xls/temp.xls.php");
	Stream("Report", $slip);

}



?>