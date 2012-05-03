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

define("EMP_YEAR", getCSetting("EMP_TAXYEAR"));

$ePRDMON = array(
	1 => 3,
	2 => 4,
	3 => 5,
	4 => 6,
	5 => 7,
	6 => 8,
	7 => 9,
	8 => 10,
	9 => 11,
	10 => 12,
	11 => 1,
	12 => 2
);

$eMONPRD = array(
	3 => 1,
	4 => 2,
	5 => 3,
	6 => 4,
	7 => 5,
	8 => 6,
	9 => 7,
	10 => 8,
	11 => 9,
	12 => 10,
	1 => 11,
	2 => 12
);

/**
 * paye debug function
 *
 * @param string $str
 */
function payedbg($str, $show_always = true) {
	if (DEBUG > 0 && $show_always) {
		print $str;
	}
}

/**
 * generates a month selection list for employees
 *
 * @param string $name name of html form field
 * @param int $curr selected month number
 * @param bool $py whether or not we are generating for the previous year
 * @return string
 */
function empMonList($name, $curr="", $py = false) {
	global $ePRDMON;

	$finyear = EMP_YEAR; //DATE_YEAR - (DATE_MONTH < 3 ? 1 : 0);

	if ($py) {
		$py = 1;
	}

	$month_to_sel = "<select name='$name'>";
	for ($i = 1; $i <= 12; $i++) {
		$mon = $ePRDMON[$i];

		$fyear = $finyear - ($mon >= 3 ? 1 : 0) - $py;

		if ($curr == $mon) {
			$selected = "selected";
		} else {
			$selected = "";
		}

		$month_to_sel .= "<option value='$mon' $selected>".date("F", mktime(0,0,0,$mon,1,2000))." $fyear</option>";
	}
	$month_to_sel .= "</select>";

	return $month_to_sel;
}

/**
 * gets the real year of a month in the active employee financial year
 *
 * @param int $mon month for which you wish to find out the year
 * @return int
 */
function getYearOfEmpMon($mon) {
	return EMP_YEAR - ($mon >= 3 ? 1 : 0);
}

/**
 * gets the real year of a period in the active employee financial year
 *
 * @param int $prd period for which you wish to find out the year
 * @return int
 */
function getYearOfEmpPrd($prd) {
	global $ePRDMON;

	return EMP_YEAR - ($ePRDMON[$prd] < 3 ? 1 : 0);
}

/**
 * @ignore
 */
function tabiast($sal) {

	//2008
//	$ptables = array(
//		/* percentage, extra, min, max */
//		array(18, 0, 0, 112500.99),
//		array(25, 20250, 112501, 180000.99),
//		array(30, 37125, 180001, 250000.99),
//		array(35, 58125, 250001, 350000.99),
//		array(38, 93125, 350001, 450000.99),
//		array(40, 131125, 450001, 999999999)
//	);

	//2009
// 	$ptables = array(
// 		/* percentage, extra, min, max */
// 		array(18, 0, 0, 122000.99),
// 		array(25, 21960, 122001, 195000.99),
// 		array(30, 40210, 195001, 270000.99),
// 		array(35, 62710, 270001, 380000.99),
// 		array(38, 101210, 380001, 490000.99),
// 		array(40, 143010, 490001, 999999999)
// 	);

	//2010
// 	$ptables = array(
// 		/* percentage, extra, min, max */
// 		array(18, 0, 0, 132000.99),
// 		array(25, 23760, 132001, 210000.99),
// 		array(30, 43260, 210001, 290000.99),
// 		array(35, 67260, 290001, 410000.99),
// 		array(38, 109260, 410001, 525000.99),
// 		array(40, 152960, 525001, 999999999)
// 	);

	//2011
	$ptables = array(
		/* percentage, extra, min, max */
		array(18, 0, 0, 140000.99),
		array(25, 25200, 140001, 221000.99),
		array(30, 45450, 221001, 305000.99),
		array(35, 70650, 305001, 431000.99),
		array(38, 114750, 431001, 552000.99),
		array(40, 160730, 552001, 999999999)
	);

	foreach ($ptables as $t) {
		if ($sal >= $t[2] && $sal <= $t[3]) {
			return $t;
		}
	}

	return false;
}

/**
 * Calculate PAYE on salary
 *
 * @param array &$emp employee db row
 * @param int $prd month/week for which salary is calculated
 * @param float $paye_salary taxable salary
 * @param int $tyear periods in year (weekly emp: 52, monthly: 12, etc...)
 * @param int $age employee age
 * @return float
 */
function calculate_paye(&$emp, $prd, $paye_salary, $tyear, $age) {
	$empnum = $emp["empnum"];

	payedbg("Supplied Taxed Salary: $paye_salary<br />");

//print "<pre>";
//var_dump ($emp);
//print "</pre>";

	//FP
	//if an employee was added in a diff year than now, we reset this
	if (EMP_YEAR != $emp['cyear']){
		$emp['prevemp_remun'] = 0;
		$emp['prevemp_tax'] = 0;
	}

	/* query expression for previous payments/paye */
	payedbg("<b>For Year</b>: ".EMP_YEAR."<br />");
	if ($emp["payprd"] == "w" || $emp["payprd"] == "f" || $emp["payprd"] == "d") {
		list($month, $week) = explode(":", $prd);
		payedbg("<b>For Month</b>: $month<br />");
		if ($emp["payprd"] == "d") {
			$day = $week;
			payedbg("<b>For Day</b>: $day<br />");
		} else {
			$day = 1;
			payedbg("<b>For Week</b>: $week<br />");
		}
		$mw_b = "((month::int>='3' AND month::int<'$month')
				OR ('$month' < '3' AND (month::int<'$month' OR month::int>='3')))
				OR (month::int='$month' AND week<'$week')";
	} else {
		$month = $prd;
		payedbg("<b>For Month</b>: $month<br />");
		$day = 1;
		$mw_b = "(month::int>='3' AND month::int<'$month')
				OR ('$month' < '3' AND (month::int<'$month' OR month::int>='3'))";
	}

	/* determine previously paid amounts */
	$sql = "SELECT 1 AS m, * FROM cubit.salamt_pay
			WHERE empnum='$empnum' AND cyear='".EMP_YEAR."' AND ($mw_b)
			UNION
			SELECT -1 AS m, * FROM cubit.salamt_rev
			WHERE empnum='$empnum' AND cyear='".EMP_YEAR."' AND ($mw_b)";

	$qry = new dbSql($sql);
	$qry->run();

	$tmgross = $paye_salary;	
	$totpaye = sprint((float)$emp["prevemp_tax"]);
	$totgross = 0;

	payedbg("Total Tax: $totpaye<br />");
	payedbg("Total Taxed Salary: $totgross<br />");

	while ($row = $qry->fetch_array()) {
		$totpaye += $row["paye"] * $row["m"];
		$totgross += $row["payegross"] * $row["m"];
		
		payedbg("-> Add Tax: ".sprint($row["paye"] * $row["m"])."<br />");
		payedbg("-> Add Salary: ".sprint($row["payegross"] * $row["m"])."<br />");
	}

	/* calculate current year fraction */
	$fday = 1;
	$year_fmon = getYearOfEmpMon(3);
	$year_month = getYearOfEmpMon($month);
	$fmstart = mktime(0, 0, 0, 3, $fday, $year_fmon);
	$tmstart = mktime(0, 0, 0, $month, $day, $year_month);

	/* if weekly/fortnightly, find the first friday (effective first week)
		and last day of selected weeks  */
	if ($emp["payprd"] == "w" || $emp["payprd"] == "f") {
		/* effective first week */
		while (date("w", $fmstart) != 5) {
			$fmstart = mktime(0, 0, 0, 3, ++$fday, $year_fmon);
		}

		/* end of week, find first week first */
		while (date("w", $tmstart) != 5) {
			$tmstart = mktime(0, 0, 0, $month, ++$day, $year_month);
		}

		/* move the day by "week" number of weeks */
		$day += ($week - 1) * ($emp["payprd"] == "w" ? 1 : 2) * 7;
		$tmstart = mktime(0, 0, 0, $month, $day, $year_month);

		// hack(fortnightly): increase to end of 1st week
		if ($emp["payprd"] == "f") {
			$fmstart += 7 * 24 * 60 * 60;
			//$tmstart += 7 * 24 * 60 * 60;
		}
	}
	
	/* count the periods in the past */
	$curprd = prdage($fmstart, $tmstart, $emp["payprd"]);
	payedbg("Total Periods in Year: $tyear<br />");
	payedbg("Calculating for Period: ".mkdatet($fmstart)." - ".mkdatet($tmstart).BR);
	payedbg("Current Period in Year: $curprd<br />");

	/* determine start period from the $curprd */
	$finYearStart = getYearOfEmpMon(3);
	$styear = extractYear($emp["hiredate"]);
	$stmon = extractMonth($emp["hiredate"]);

	if ($styear < $finYearStart || ($styear == $finYearStart && $stmon < 3)) {
		$stprd = 1;
	} else {
		payedbg("First Period: ".date("Y-m-d",mktimefd(getYearOfEmpMon(3)."-03-01"))."<br />");
		payedbg("Hire Date: ".date("Y-m-d",mktimefd($emp["hiredate"]))."<br />");
		$stprd = prdage(mktimefd(getYearOfEmpMon(3)."-03-01"), mktimefd($emp["hiredate"]), $emp["payprd"]);
	}

	// num of prds working
	$working_prd = $curprd - ($stprd - 1);

	// total periods should be working this year
	$totprd = $tyear - ($stprd - 1);


	payedbg("Period Started Working: $stprd<br />");
	payedbg("Total Period to Work: $totprd<br />");
	payedbg("totgross: $totgross<br />");
	payedbg("Working Period: $working_prd<br />");

//print "<br>$emp[prevemp_remun] +(($totgross) + ($tmgross * ($tyear - $curprd + 1)<br><br>";

	$paye_salary = $emp["prevemp_remun"]
		+(($totgross)
		+($tmgross * ($tyear - $curprd + 1)));

	$annual_eq = $emp["prevemp_remun"]
		+($tmgross * ($tyear - 1 + 1));

	payedbg("Total Annual SITE: $annual_eq<br>");
	payedbg("Total Taxed Salary to Calculate On: $paye_salary<br />");

	if ($annual_eq <= 60000){
		$paye = bracket_calcpaye($age, $annual_eq);
		$totprd = $tyear;
		$working_prd = 1;
		$totpaye = 0;
	}else {
		$paye = bracket_calcpaye($age, $paye_salary);
	}

	payedbg("Calculated PAYE: $paye<br />");
	payedbg("PAYE After Deduction of Previous PAYE: ".sprint($paye - $totpaye)."<br />");
	payedbg("PAYE Divide for Period: ".sprint(($paye - $totpaye) * ($working_prd / $totprd))."<br />");

	if (($amt = sprint(($paye - $totpaye) / ($totprd - ($working_prd - 1)))) < 0) {
		$amt = 0;
	}


	payedbg("<b>Resulting PAYE: $amt</b><br />");

	return $amt;

}

function bracket_calcpaye($age, $paye_salary) {
	payedbg(":: PAYE Bracket - Age: $age<br />");
	payedbg(":: PAYE Bracket - Taxed Salary: $paye_salary<br />");
	
	/* get PAYE bracket percentages */
	if(($tables = tabiast($paye_salary)) === false){
		errDie("The PAYE bracket for ".CUR." $paye_salary does not exist ($).");
	} else {
		list($payeperc, $payex, $min, $max) = $tables;
	}

	//2008
	// Get paye rebate
//	$rebate = 7740;
//	if ( $age >= 65 ) {
//		$rebate += 4680;
//	}

	//2009
	// Get paye rebate
// 	$rebate = 8280;
// 	if ( $age >= 65 ) {
// 		$rebate += 5040;
// 	}

	//2010
	// Get paye rebate
// 	$rebate = 9756;
// 	if ( $age >= 65 ) {
// 		$rebate += 5400;
// 	}

	//2011
	// Get paye rebate
	$rebate = 10260;
	if ( $age >= 65 ) {
		$rebate += 5675;
	}

	if ( $min > 0 ) --$min;

	$paye = ($paye_salary - $min) * $payeperc / 100;
	$paye = $paye + $payex - $rebate;

	if ( $paye < 0 ) {
		$paye = 0;
	}
	
	payedbg(":: PAYE Bracket - Result: $paye<br />");
	
	return $paye;
}

/**
 * Calculate PAYE on salary
 *
 * @param array &$emp employee db row
 * @param int $prd month/week for which salary is calculated
 * @param float $paye_salary taxable salary
 * @param int $tyear periods in year (weekly emp: 52, monthly: 12, etc...)
 * @param int $age employee age
 * @return float
 */
function calculate_paye_old(&$emp, $prd, $paye_salary, $tyear, $age) {
	$empnum = $emp["empnum"];
//define("ACTUAL_EMP_YEAR",getYearOfEmpMon($prd));
	payedbg("fromsal: $paye_salary<br />");

	/* query expression for previous payments/paye */
	if ($emp["payprd"] == "w" || $emp["payprd"] == "f" || $emp["payprd"] == "d") {
		list($month, $week) = explode(":", $prd);
		if ($emp["payprd"] == "d") {
			$day = $week;
		} else {
			$day = 1;
		}
		$mw_b = "((month::int>='3' AND month::int<'$month')
				OR ('$month' < '3' AND (month::int<'$month' OR month::int>='3')))
				OR (month::int='$month' AND week<'$week')";
	} else {
		$month = $prd;
		$day = 1;
		$mw_b = "(month::int>='3' AND month::int<'$month')
				OR ('$month' < '3' AND (month::int<'$month' OR month::int>='3'))";
	}

	/* determine previously paid amounts */
	$sql = "SELECT 1 AS m, * FROM cubit.salamt_pay
			WHERE empnum='$empnum' AND cyear='".EMP_YEAR."' AND ($mw_b)
			UNION
			SELECT -1 AS m, * FROM cubit.salamt_rev
			WHERE empnum='$empnum' AND cyear='".EMP_YEAR."' AND ($mw_b)";
	$qry = new dbSql($sql);
	$qry->run();

	$prev_emp = $emp["cyear"] == EMP_YEAR
		&& ($emp["prevemp_tax"] > 0 && $emp["prevemp_remun"] > 0);

	payedbg("prevemp: ".($prev_emp?"true":"false")."<br />");

	if ($prev_emp) {
		$totpaye = $emp["prevemp_tax"];
		$totgross = $paye_salary + $emp["prevemp_remun"];
	} else {
		$totpaye = 0;
		$totgross = $paye_salary;
	}
	$totnetgross = 0;

	while ($row = $qry->fetch_array()) {
		$totpaye += $row["paye"] * $row["m"];
		$totgross += $row["payegross"] * $row["m"];
		$totnetgross += $row["netgross"] * $row["m"];
	}

	/* calculate current year fraction */
	$fday = 1;
	$year_fmon = getYearOfEmpMon(3);
	$year_month = getYearOfEmpMon($month);
	$fmstart = mktime(0, 0, 0, 3, $fday, $year_fmon);
	$tmstart = mktime(0, 0, 0, $month, $day, $year_month);

	/* if weekly/fortnightly, find the first friday (effective first week)
		and last day of selected weeks  */
	if ($emp["payprd"] == "w" || $emp["payprd"] == "f") {
		payedbg("week: $week<br />");
		/* effective first week */
		while (date("w", $fmstart) != 5) {
			$fmstart = mktime(0, 0, 0, 3, ++$fday, $year_fmon);
		}

		/* end of week, find first week first */
		while (date("w", $tmstart) != 5) {
			$tmstart = mktime(0, 0, 0, $month, ++$day, $year_month);
		}

		/* move the day by "week" number of weeks */
		$day += ($week - 1) * ($emp["payprd"] == "w" ? 1 : 2) * 7;
		$tmstart = mktime(0, 0, 0, $month, $day, $year_month);

		// hack(fortnightly): increase to end of 1st week
		if ($emp["payprd"] == "f") {
			$fmstart += 7 * 24 * 60 * 60;
			//$tmstart += 7 * 24 * 60 * 60;
		}
	}

	/* count the periods in the past */
	$curprd = prdage($fmstart, $tmstart, $emp["payprd"]);
	payedbg("curprd calc: ".mkdatet($fmstart)." - ".mkdatet($tmstart).BR);

	payedbg("curprd: $curprd<br />");

	/* determine start period from the $curprd */
	$finYearStart = getYearOfEmpMon(3);
	$styear = extractYear($emp["hiredate"]);
	$stmon = extractMonth($emp["hiredate"]);

	if ($styear < $finYearStart || ($styear == $finYearStart && $stmon < 3)) {
		$stprd = 1;
	} else {
		//$stprd = prdage(mktimefd($emp["hiredate"]), $tmstart, $emp["payprd"]);
		payedbg("stprd calc: ".date("Y-m-d",mktimefd(getYearOfEmpMon(3)."-03-01")));
		payedbg(" - ".date("Y-m-d",mktimefd($emp["hiredate"]))."<br />");
		$stprd = prdage(mktimefd(getYearOfEmpMon(3)."-03-01"), mktimefd($emp["hiredate"]), $emp["payprd"]);
	}

	if ($prev_emp) {
		$working_prd = $curprd;
		$totprd = $tyear;
		//$totprd = $tyear - ($stprd - 0);
	} else {
		// num of prds working
		$working_prd = $curprd - ($stprd - 1);

		// total periods should be working this year
		$totprd = $tyear - ($stprd - 1);
	}

	payedbg("startprd: $stprd<br />");
	payedbg("totprd: $totprd<br />");
	payedbg("totgross: $totgross<br />");
	payedbg("totnetgross: $totnetgross<br />");
	payedbg("workprd: $working_prd<br />");
	
	//$working_prd = 2;
	//$totprd = 7;
	$paye_salary = $totgross / $working_prd;
	
	payedbg("payesal1: $paye_salary<br />");

	/* scale paye to amount of periods should be working */
	$paye_salary *= $totprd / $tyear;
	//$paye_salary *= $tyear / $curprd;

	payedbg("payesal2: $paye_salary<br />");

	$paye = bracket_calcpaye_old($age, $paye_salary, $tyear);

	if (($amt = sprint(($paye * ($working_prd / $totprd)) - $totpaye)) < 0) {
		$amt = 0;
	}

	return $amt;
}

function bracket_calcpaye_old($age, $paye_salary, $tyear) {
	payedbg("bcalc - age: $age<br />");
	payedbg("bcalc - paye_salary: $paye_salary<br />");
	payedbg("bcalc - tyear: $tyear<br />");
	
	/* get PAYE bracket percentages */
	if(($tables = tabiast($paye_salary * $tyear)) === false){
		errDie("The PAYE bracket for ".CUR." $paye_salary does not exist ($).");
	} else {
		list($payeperc, $payex, $min, $max) = $tables;
	}

	//2008
	// Get paye rebate
//	$rebate = 7740;
//	if ( $age >= 65 ) {
//		$rebate += 4680;
//	}

	//2009
	// Get paye rebate
// 	$rebate = 8280;
// 	if ( $age >= 65 ) {
// 		$rebate += 5040;
// 	}

	//2010
	// Get paye rebate
// 	$rebate = 9756;
// 	if ( $age >= 65 ) {
// 		$rebate += 5400;
// 	}

	//2011
	// Get paye rebate
	$rebate = 10260;
	if ( $age >= 65 ) {
		$rebate += 5675;
	}

	if ( $min > 0 ) --$min;

	$paye = ($paye_salary * $tyear - $min) * $payeperc / 100;
	$paye = $paye + $payex - $rebate;

	if ( $paye < 0 ) {
		$paye = 0;
	}
	
	payedbg("bcalc - result: $paye<br />");
	
	return $paye;
}

/**
 * counts periods from one date to another
 *
 * @param string $from
 * @param string $to
 * @param string $type period type w/f/w/d
 * @return unknown
 */
function prdage($from, $to, $type) {
	/* if weekly salary, move $from to first friday */
	if ($type == "w" || $type == "f") {
		while (date("w", $from) != 5) {
			$from += 24 * 60 * 60;
		}
	}

	//print "to: ".date("Y-m-d", $to)."<br />";

	/* extract day/month from $from */
	$fmon = date("m", $from);
	$fday = date("d", $from);

	/* count them */
	$i = 0;
	$prd = 1;
	//print "$prd - $i - from: ".date("Y-m-d", $from)."<br />";
	while ($from < $to) {
		++$prd;

		/* increase to next period */
		if ($type == "f") {
			/* find next period
				two weeks onwards, */
			$from = mktime(0, 0, 0, $fmon, $fday + (++$i * 14), getYearOfEmpMon($fmon));
		} else if ($type == "w") {
			$from = mktime(0, 0, 0, $fmon, $fday + (++$i * 7), getYearOfEmpMon($fmon));
		} else if ($type == "d") {
			/* increase day to next skipping sundays/saturdays
				sunday: date("w") == 0
				saturday: date("w") == 6
			 */
			do {
				$from = mktime(0, 0, 0, $fmon, 1 + ++$i, getYearOfEmpMon($fmon));
			} while (date("w", $from) % 6 == 0 && $from < $to);
		} else if ($type == "m") {
			$from = mktime(0, 0, 0, $fmon + ++$i, 1, getYearOfEmpMon($fmon));
			$from = mktime(0, 0, 0, date("m", $from), date("t", $from), date("Y", $from));
		} else {
			return false;
		}

		//print "$prd - $i - from: ".date("Y-m-d", $from)."<br />";
	}

	//payedbg("calculated age: $prd<br />");

	return $prd;
}

function salary() {
	global $_GET;
	extract($_GET);

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
		<form id='emplfrm'>
			<input type='hidden' name='key' value='salary'>";

	$OUTPUT .= "
		<tr>
			<td colspan='2' align='right'>
				<input type='button' value='Save' onClick='savesalary();'>
			</td>
		</tr>
		<tr>
			<th colspan='2'>General Salary and Allowances</th>
		</tr>
		<tr class='bg-odd'>
			<td><b>Remuneration per Annum</b></td>
			<td>
				<table><tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='basic_sal_annum' value='0.00' class='right'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-even'>
			<td>Salary Calculation</td>
			<td>$saltyp</td>
		</tr>
		<tr bgcolor='".bgcolor($i)."'>
			<td>Pay Period</td>
			<td valign='top'>
				$payprd
				<div id='div_payprd_day'>$payprd_day</div>
			</td>
		</tr>
		<tr bgcolor='".bgcolor($i)."'>
			<td>Pay Type</td>
			<td valign='center'>$paytypes</td>
		</tr>
		<tr class='bg-even'>
			<td>Annual Bonus</td>
			<td>
				<table><tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='sal_bonus' value='0.00' class=right></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-odd'>
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
		<tr class='bg-odd'>
			<td>Medical Contribution</td>
			<td align='right'><div id='div_fringe_medaid'>".CUR."0.00</div></td>
		</tr>
		<tr class='bg-odd'>
			<td>Motorcar 1 Determined Value</td>
			<td>
				<table><tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='fringe_car1' value='0.00' class='right'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-even'>
			<td>- Contributions for Use</td>
			<td>
				<table><tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='fringe_car1_contrib' value='0.00' class='right'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-even'>
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
		<tr class='bg-even'>
			<td>- Pays for Servicing</td>
			<td>
				<table><tr>
					<td>&nbsp;</td>
					<td>
						<select name='fringe_car1_service'>
							<option value='0'>No</option>
							<option value='1'>Yes</option>
						</select>
					</td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr>
			<td colspan='2'><li class='err'>In case of 2 motorcars it is accepted that the second
				vehicle is not used for business purposes. In other cases PAYE has to be
				manually adjusted when processing salary.</li></td>
		</tr>
		<tr class='bg-odd'>
			<td>Motorcar 2 Determined Value</td>
			<td>
				<table><tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='fringe_car2' value='0.00' class='right'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-even'>
			<td>- Contributions for Use</td>
			<td>
				<table><tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='fringe_car2_contrib' value='0.00' class='right'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-even'>
			<td>- Pays for own Fuel</td>
			<td>
				<table><tr>
					<td>&nbsp;</td>
					<td>
						<select name='fringe_car2_fuel'>
							<option value='0'>No</option>
							<option value='1'>Yes</option>
						</select>
					</td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-even'>
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
  		$bgColor = (++$i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

  		$OUTPUT .= "
			<tr bgcolor='$bgColor'>
				<td><div id='divfrin[$fid]'></div></td>
				<td>
					<table><tr>
						<td><div id='divfrinamt[$fid]'>&nbsp;</div></td>
						<td><input type='text' size='10' name='fringebens[$fid]' value='' class='right'></td>
						<td><div id='divfrinperc[$fid]'>&nbsp;</div></td>
					</tr></table>
				</td>
			</tr>";
  	}

	// allowances
	$OUTPUT .= "
		<tr>
			<th colspan='2'>Allowances</th>
		</tr>
		<tr class='bg-odd'>
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
		$bgColor = (++$i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

		$OUTPUT .= "
		<tr bgcolor='$bgColor'>
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
		$bgcolor = bgcolor($i);

		$OUTPUT .= "
			<tr bgcolor='$bgcolor'>
				<td><div id='subsname[$sid]'></div></td>
				<td>
					<table>
						<tr>
							<td>Amount:</td>
							<td>".CUR." <input type='text' name='subsamt[$sid]' value=''></td>
						</tr>
						<tr>
							<td>Days:</td>
							<td><input type='text' name='subsdays[$sid]' value=''></td>
						</tr>
					</table>
				</td>
			</tr>";
	}

	$OUTPUT .= "
		<tr>
			<th colspan='2'>Deductions: Company Contributions</th>
		</tr>
		<tr class='bg-even'>
			<td>SDL</td>
			<td>
				<table><tr>
					<td>&nbsp;</td>
					<td><input type='hidden' size='10' name='comp_sdl' value='0' class='right'>1</td>
					<td>%</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-odd'>
			<td>UIF</td>
			<td>
				<table><tr>
					<td>&nbsp;</td>
					<td><input type='hidden' size='10' name='comp_uif' value='0' class='right'>1</td>
					<td>%</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-even'>
			<td>Pension Fund</td>
			<td>
				<table><tr>
					<td>&nbsp;</td>
					<td><input type='text' size='10' name='comp_pension' value='0' class='right'></td>
					<td>%</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-odd'>
			<td>Retirement Annuity Fund</td>
			<td>
				<table></tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='comp_ret' value='0.00' class='right'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-even'>
			<td>Medical Contribution</td>
			<td>
				<table></tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='comp_medical' value='0.00' class='right' onChange='updateMedFringe();'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-odd'>
			<td>Provident Fund</td>
			<td>
				<table></tr>
					<td>&nbsp;</td>
					<td><input type='text' size='10' name='comp_provident' value='0' class='right'></td>
					<td>%</td>
				</tr></table>
			</td>
		</tr>";

	$i = 0;
	foreach ( $ded as $did ) {
		$bgColor = (++$i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

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
	<tr class='bg-even'>
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
/*

*/
	// deductions
	$OUTPUT .= "
		<tr>
			<th colspan='2'>Deductions: Employee Contributions</th>
		</tr>
		<tr class='bg-odd'>
			<td>UIF</td>
			<td>
				<table><tr>
					<td>&nbsp;</td>
					<td><input type='hidden' size='10' name='emp_uif' value='0' class='right'>1</td>
					<td>%</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-even'>
			<td>Pension Fund</td>
			<td>
				<table><tr>
					<td>&nbsp;</td>
					<td><input type='text' size='10' name='emp_pension' value='0' class='right'></td>
					<td>%</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-odd'>
			<td>Retirement Annuity Fund</td>
			<td>
				<table><tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='emp_ret' value='0.00' class='right'></td>
					<td><li class='err'>To be paid to RA fund by employer</li></td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-even'>
			<td>Medical Contribution</td>
			<td>
				<table><tr>
					<td>".CUR."</td>
					<td><input type='text' size='10' name='emp_medical' value='0.00' class='right' onChange='updateMedFringe();'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-odd'>
			<td> - Total Benificiaries<br>(Including Member)</td>
			<td>
				<table><tr>
					<td>&nbsp;</td>
					<td><input type='text' size='2' name='emp_meddeps' value='0' class='right'></td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-even'>
			<td>Provident Fund</td>
			<td>
				<table><tr>
					<td>&nbsp;</td>
					<td><input type='text' size='10' name='emp_provident' value='0' class='right'></td>
					<td>%</td>
				</tr></table>
			</td>
		</tr>
		<tr class='bg-odd'>
			<td>Use Salary Deduction Scales (If Supplied)</td>
			<td>
				<table><tr>
					<td>&nbsp;</td>
					<td>
						<select name='emp_usescales'>
							<option value='0'>No</option>
							<option value='1'>Yes</option>
						</select>
					</td>
					<td>&nbsp;</td>
				</tr></table>
			</td>
		</tr>";

	$i = 1;
	foreach ( $ded as $did ) {
		$bgColor = (++$i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

		$OUTPUT .= "
			<tr bgcolor='$bgColor'>
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
	<tr class='bg-even'>
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

	$OUTPUT .= "
			<tr>
				<td colspan='2' align='right'>
					<input type='button' value='Save' onClick='savesalary();'>
				</td>
			</tr>
		</form>
		</table>";

	// javascript
	$OUTPUT .= "
		<script>
			function payprd_change(p) {
				if ( p.value == \"f\" || p.value == \"w\" ) {
					document.getElementById('div_payprd_day').style.visibility = 'visible';
					document.getElementById('div_payprd_day').style.height = document.getElementById('emplfrm').payprd_day.style.height;
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

			opfrm = opdoc.getElementById('emplfrm');
			efrm = document.getElementById('emplfrm');

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
				efrm.fringe_car2_service.options[opfrm.fringe_car2_service.value].selected = true;
				efrm.emp_usescales.options[opfrm.emp_usescales.value].selected = true;";

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
			efrm.elements['fringebens[$fid]'].value = opfrm.elements['fringebens[$fid]'].value;";
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
			efrm.elements['allowances[$aid]'].value = opfrm.elements['allowances[$aid]'].value;";
	}

	foreach ( $subs as $sid ) {
		$OUTPUT .= "
			// set the allowance name
			document.getElementById('subsname[$sid]').innerHTML = opfrm.elements['subsname[$sid]'].value;

			// set the allowance amount
			efrm.elements['subsamt[$sid]'].value = opfrm.elements['subsamt[$sid]'].value;

			// allowance days
			efrm.elements['subsdays[$sid]'].value = opfrm.elements['subsdays[$sid]'].value;";
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
			opfrm.fringe_car2_service.value = efrm.fringe_car2_service.value;
			opfrm.emp_usescales.value = efrm.emp_usescales.value;";

	foreach ( $frin as $fid ) {
		$OUTPUT .= "
			opfrm.elements['fringebens[$fid]'].value = efrm.elements['fringebens[$fid]'].value;";
	}

	foreach ( $all as $aid ) {
		$OUTPUT .= "
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
