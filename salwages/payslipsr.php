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
require ("../settings.php");
require ("../core-settings.php");
require("emp-functions.php");

## Decide
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "slip":
			$OUTPUT = slip($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = slctDate ();
	}
}else{
	$OUTPUT = slctDate ();
}


# display output
require ("../template.php");





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
		$employees[$d["empnum"]]= "$d[sname], $d[fnames] ($d[enum])";
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

	$OUT = "
		<h3>Select date range to view</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='slip'>
			<tr>
				<th>Employee</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>".extlib_cpsel("empnum", $employees, $empnum)."</td>
			</tr>
			<tr><th colspan='2'>Date Range</th></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'  colspan='2'>
					".mkDateSelect("from",$from_year, $from_month, $from_day)."
					&nbsp;&nbsp;&nbsp;TO&nbsp;&nbsp;&nbsp;
					".mkDateSelect("to", $to_year, $to_month, $to_day)."
				</td>
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
function slip ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

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



	# get employee details
	db_connect ();
	if ($empnum != "0") {
		$empw = "empnum='$empnum' AND";
	}

	if(!isset($empw))
		$empw = "";

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

	$sql = "SELECT empnum FROM employees WHERE div = '".USER_DIV."' $egsearch ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) > 0){
		$empsarr = array ();
		while ($arr = pg_fetch_array ($empRslt)){
			$empsarr[] = "empnum='$arr[empnum]'";
		}
		$empsearch = " AND (".implode (" OR ",$empsarr).")";
	}else {
		$empsearch = " AND FALSE";
	}

	$sql = "SELECT * FROM salr WHERE $empw saldate >= '$fromdate' AND saldate <= '$todate' $empsearch AND div = '".USER_DIV."'";
	$pRslt = db_exec ($sql) or errDie ("Unable to select employee payments from database.");

	if(!isset($mon))
		$mon = 0;

	$mon += 0;
	$mon += 0;
	$mon += 0;
	$mon += 0;

	if (pg_numrows ($pRslt) < 1) {
		return "<li class='err'> - Employee payment not found for period $fromdate - $todate.</li>".slctDate ();
	}

	$mon += 0;

	if (pg_numrows ($pRslt) > 0) {
		$slip = "
			<center>
			<h3>Salaries Reversed in period $fromdate - $todate</h3>
			<table ".TMPL_tblDflts." width='85%'>
				<tr>
					<th>Employee</th>
					<th>Gross Salary</th>
					<th>Commission</th>
					<th>Low or interest free loan</th>
					<th>UIF</th>
					<th>PAYE</th>
					<th>Deductions</th>
					<th>Nett Income</th>
					<th colspan='2'>Options</th>
				</tr>";

		# totals
		$totgross = 0;
		$totcomm = 0;
		$totins = 0;
		$totuif = 0;
		$totpaye = 0;
		$totded = 0;
		$totsal = 0;
		$i = 0;
		while($pay = pg_fetch_array($pRslt)){

			# get employee details
			db_connect ();

			$sql = "SELECT fnames, sname FROM employees WHERE empnum='$pay[empnum]' AND div = '".USER_DIV."'";
			$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
			if (pg_numrows ($empRslt) < 1) {
				#check previous employees first ...
				$get_prev = "SELECT fnames, sname FROM lemployees WHERE empnum='$pay[empnum]' AND div = '".USER_DIV."'";
				$empRslt = db_exec ($get_prev) or errDie ("Unable to get employee information.");
				$showstat = "(Left Company)";
//				return "Invalid employee ID.";
			}else {
				$showstat = "";
			}
			$emp = pg_fetch_array($empRslt);

			# Calculate gross salary from nettpay
			$gross = round(($pay['salary'] - $pay['totallow'] - $pay['comm'] + $pay['totded'] + $pay['uif'] + $pay['paye'] + $pay['loanins']), 2);


			$slip .= "
                <tr bgcolor='".bgcolorg()."'>
                	<td>$emp[fnames] $emp[sname] $showstat</td>
                	<td nowrap>".CUR." ".sprint("$gross")."</td>
                	<td nowrap>".CUR." ".sprint("$pay[comm]")."</td>
                	<td nowrap>".CUR." ".sprint("$pay[loanins]")."</td>
                	<td nowrap>".CUR." ".sprint("$pay[uif]")."</td>
                	<td nowrap>".CUR." ".sprint("$pay[paye]")."</td>
                	<td nowrap>".CUR." ".sprint("$pay[totded]")."</td>
                	<td nowrap>".CUR." ".sprint("$pay[salary]")."</td>
                	<td><a target='_blank' href='payslip-print.php?rev=t&id=$pay[id]'>Print</a></td>
                </tr>";

			$totgross += $gross;
			$totcomm += $pay['comm'];
			$totins += $pay['loanins'];
			$totuif += $pay['uif'];
			$totpaye += $pay['paye'];
			$totded += $pay['totded'];
			$totsal += $pay['salary'];
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
				<tr bgcolor='".bgcolorg()."'>
					<td><b>Total</b></td>
					<td nowrap><b>".CUR." $totgross</b></td>
					<td nowrap><b>".CUR." $totcomm</b></td>
					<td nowrap><b>".CUR." $totins</b></td>
					<td nowrap><b>".CUR." $totuif</b></td>
					<td nowrap><b>".CUR." $totpaye</b></td>
					<td nowrap><b>".CUR." $totded</b></td>
					<td nowrap><b>".CUR." $totsal</b></td>
					<td colspan=2></td>
				</tr>
			</table>";
	}else{
		return "<li> - There are no salary reversed for the selected period $fromdate - $todate";
	}

	# layout
	$slip .= "<br>".
		mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee")
		);
	return $slip;

}


?>
