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
require("emp-functions.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "view":
			$OUTPUT = printLea ($_POST);
			break;
		default:
			$OUTPUT = slct();
			break;
	}
} else if(isset($_GET["empnum"])) {
	$OUTPUT = slct($_GET);
} else {
	$OUTPUT = slct();
}

require ("../template.php");



function slct()
{

	extract($_GET);

	if (!isset($empnum)) {
		$msg = ", for all Employees";
		$fld = "";
	} else {
		$msg = "";
		$empnum += 0;
		$fld = "<input type='hidden' name='empnum' value='$empnum'>";
	}

    //layout
	$slct = "
		<h3>Print Year to Date (Payslip)$msg<h3>
		<form action='".SELF."' method='POST' name='form'>
			$fld
			<input type='hidden' name='key' value='view' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='5'>Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>From</td>
				<td>".mkDateSelect("f", DATE_YEAR, DATE_MONTH, 1)."</td>
				<td>to</td>
				<td>".mkDateSelect("to", DATE_YEAR, DATE_MONTH, DATE_DAYS)."</td>
				<td><input type='submit' value='View' /></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $slct;

}



function printLea ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new validate ();
	$v->isOk ($f_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($f_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($f_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
	# mix dates
	$fromdate = $f_year."-".$f_month."-".$f_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	$v->isOk ($fromdate, "date", 1, 1, "Invalid from date.");
	$v->isOk ($todate, "date", 1, 1, "Invalid to date.");

	if ($v->isError()) {
		$err = $v->genErrors();
        return $err;
	}


	if (isset($empnum)) {
		$OUTPUT = genslip($empnum, $fromdate, $todate);
	} else {
		$OUTPUT = "";
		$qry = new dbSelect("employees", "cubit", grp(
			m("cols", "empnum"),
			m("where", "div='".USER_DIV."'")
		));
		$qry->run();

		while ($row = $qry->fetch_array()) {
			$OUTPUT .= paged(genslip($row["empnum"], $fromdate, $todate));
		}
	}
	require("../tmpl-print.php");

}



function genslip($empnum, $fromdate, $todate)
{

	$Sl = "SELECT * FROM employees WHERE empnum='$empnum'";
	$Ry = db_exec($Sl) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($Ry) < 1){
		$Sl = "SELECT * FROM lemployees WHERE empnum='$empnum'";
		$Ry = db_exec($Sl) or errDie ("Unable to select employees from database.");
	}
	$emp = pg_fetch_array($Ry);

	$pay['showex'] = "Yes";

	$date = $todate;

	//$pw = "saldate>='$fromdate' AND saldate<='$todate'";

	$from_month = extractMonth($fromdate);
	$to_month = extractMonth($todate);

	if ($to_month < $from_month) {
		$pw = "month::int>='$from_month' OR month::int <= '$to_month'";
	} else {
		$pw = "month::int>='$from_month' AND month::int <= '$to_month'";
	}

	$pw = "($pw) AND (saldate>='$fromdate' AND saldate<='$todate')";

	/* paye balance */
	$sql = "SELECT sum(paye) AS sum FROM salpaid WHERE ($pw) AND empnum='$empnum' AND cyear='".EMP_YEAR."'";
	$Ry = db_exec($sql) or errDie("Unable to get paye");

	$pdata = pg_fetch_array($Ry);

	$paid = $pdata['sum'];

	$sql = "SELECT sum(paye) AS sum FROM salr WHERE ($pw) AND empnum='$empnum' AND cyear='".EMP_YEAR."'";
	$Ry = db_exec($sql) or errDie("Unable to get paye");

	$pdata = pg_fetch_array($Ry);

	$upaid = $pdata['sum'];

	$tottax = sprint($paid-$upaid);
	
	/* salary balance */
	$sql = "SELECT sum(salary) FROM salpaid WHERE ($pw) AND empnum='$empnum' AND cyear='".EMP_YEAR."'";
	$Ry = db_exec($sql) or errDie("Unable to get paye");

	$pdata = pg_fetch_array($Ry);

	$sql = "SELECT sum(salary) FROM salr WHERE ($pw) AND empnum='$empnum' AND cyear='".EMP_YEAR."'";
	$Ry = db_exec($sql) or errDie("Unable to get paye");

	$prdata = pg_fetch_array($Ry);

	$pay['salary'] = $pdata['sum'] - $prdata["sum"];

	$emp['basic_sal'] = sprint($emp['basic_sal']);

	/* pay slip ids */
	$psids = array();
	$sql = "
		SELECT id, novert, hovert FROM cubit.salpaid WHERE ($pw) AND empnum='$empnum' AND cyear='".EMP_YEAR."' 
		UNION 
		SELECT -id, -novert, -hovert FROM cubit.salr WHERE ($pw) AND empnum='$empnum' AND cyear='".EMP_YEAR."'";
	$rslt = db_exec($sql) or errDie("Error reading payslip ids");

	$novert = 0;
	$hovert = 0;
	while ($row = pg_fetch_assoc($rslt)) {
		$psids[] = "payslip='$row[id]'";
		$novert += $row["novert"];
		$hovert += $row["hovert"];
	}

	if (count($psids) <= 0) {
		$psids[] = "true";
	}

	$pwc = "(".implode(" OR ", $psids).")";

	$dates = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td width='50%'>Date</td>
				<td width='50%'>$date</td>
			</tr>
		</table>";

	$i = 0;
	$epw = "date >= '$fromdate' AND date <= '$todate'";
	$fepw = "fdate >= '$fromdate' AND fdate <= '$todate'";
	$incomes = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td width='80%' align='center'>Description</td>
				<td align='center'>Amount</td>
			</tr>";

	db_conn('cubit');

	$sql = "SELECT DISTINCT description, type FROM emp_inc WHERE ($pwc) AND emp='$empnum' AND ($epw) ORDER BY description";
	$Ri = db_exec($sql) or errDie("Unable to get data.");

    $tot_incomes = 0;
	while($data = pg_fetch_array($Ri)) {

		$sql = "SELECT sum(amount) FROM emp_inc WHERE description='$data[description]' AND ($pwc) AND emp='$empnum' AND ($epw)";
		$Rl = db_exec($sql) or errDie("Unable to get data.");

		$sdata = pg_fetch_array($Rl);
		$incomes .= "
			<tr>
				<td>$data[description]</td>
				<td align='right'>".CUR." $sdata[sum]</td>
			</tr>";
		$i++;
		if ( $data["description"] != "Fringe Benefits Total" )
			$tot_incomes = $tot_incomes + $sdata['sum'];

		if ($data["type"] == "INCO") {
			if ($novert > 0) {
				$incomes .= "
					<tr>
						<td colspan='2'>&nbsp;&nbsp;&nbsp; $novert Hours Normal Overtime</td>
					</tr>";
				++$i;
			}

			if ($hovert > 0) {
				$incomes .= "
					<tr>
						<td colspan='2'>&nbsp;&nbsp;&nbsp; $hovert Hours Holiday Overtime</td>
					</tr>";
				++$i;
			}
		}
	}

	while($i < 7) {
		$incomes .= "<tr><td><br></td></tr>";
		$i++;
	}

	$incomes .= "</table>";

	$i = 0;

	$benefits = "<table ".TMPL_tblDflts." width='100%'>";

	while($i < 4) {
		$benefits .= "<tr><td><br></td></tr>";
		$i++;
	}

	$benefits .= "</table>";

	$i = 0;

	$comp_parts = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td align='center'>Description</td>
				<td align='center'>Amount</td>
			</tr>";

	$Sl = "SELECT DISTINCT(description) FROM emp_com WHERE ($pwc) AND emp='$empnum'  AND description !='SDL' AND ($epw) ORDER BY description";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	while($data = pg_fetch_array($Ri)) {

		$Sl = "SELECT SUM(amount) FROM emp_com WHERE description='$data[description]' AND ($pwc) AND emp='$empnum' AND description !='SDL' AND ($epw)";
		$Rl = db_exec($Sl) or errDie("Unable to get data.");

		$sdata = pg_fetch_array($Rl);
		$comp_parts .= "
			<tr>
				<td width='80%'>$data[description]</td>
				<td width='20%' align='right'>".CUR." $sdata[sum]</td>
			</tr>";
		$i++;
	}

	while($i < 7) {
		$comp_parts .= "<tr><td><br></td></tr>";
		$i++;
	}

	$comp_parts .= "</table>";

	$i = 0;

	$deductions = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td width='90%' align='center'>Description</td>
				<td align='center'>Amount</td>
			</tr>";

	$Sl = "SELECT DISTINCT(description),type FROM emp_ded WHERE ($pwc) AND emp='$empnum' AND ($epw) ORDER BY description";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$tot_deductions = 0;
	while($data = pg_fetch_array($Ri)) {

		$Sl = "SELECT SUM(amount) FROM emp_ded WHERE description='$data[description]' AND ($pwc) AND emp='$empnum' AND ($epw)";
		$Rl = db_exec($Sl) or errDie("Unable to get data.");

		$sdata = pg_fetch_array($Rl);

		if ( $data["type"] == "PAYE" && $emp["basic_sal_annum"] <= 65000 ) {
			$data["description"] = "SITE";
		}

		$deductions .= "
			<tr>
				<td>$data[description]</td>
				<td align='right' nowrap='t'>".CUR." $sdata[sum]</td>
			</tr>";

		$i++;
		$tot_deductions = $tot_deductions+$sdata['sum'];
	}

	while ($i < 6) {
		$deductions .= TBL_BR;
		$i++;
	}

	$deductions .= "</table>";

	$fringe = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td width='90%' align=center>Description</td>
				<td align='center'>Amount</td>
			</tr>";

    $sql = "SELECT description,SUM(amount) AS amount FROM emp_frin WHERE ($pwc) AND emp='$empnum' AND ($fepw) GROUP BY description ORDER BY description";
	$rslt = db_exec($sql) or errDie("Unable to get data.");

	$i = 0;
	$tot_fringe = 0;
	while ($data = pg_fetch_array($rslt)) {
		$data["amount"] = sprint(abs($data["amount"]));

		$fringe .= "
			<tr>
				<td>$data[description]</td>
				<td align='right' nowrap='t'>".CUR." $data[amount]</td>
			</tr>";

		$i++;
		$tot_fringe += $data['amount'];
	}

	while ($i < 6) {
		$fringe .= TBL_BR;
		$i++;
	}

	$fringe .= "</table>";

	$exstras = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td width='25%'>LEAVE DAYS DUE</td>
				<td width='25%'></td>
				<td width='25%'><b>NETT PAY</b></td>
				<td width='25%'><b>".CUR." ".sprint($pay["salary"])."</b></td>
			</tr>
			<tr>
				<td width='25%'>Total Employee's Tax</td>
				<td width='25%'>$tottax</td>
				<td width='25%'></td>
				<td width='25%'></td>
			</tr>
		</table>";

	$pay["salary"] = sprint($pay["salary"]);

	db_conn('cubit');

	$period = "";

	$tot_incomes = sprint($tot_incomes);
	$tot_deductions = sprint($tot_deductions);
	vsprint($tot_fringe);

	$grossdata = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td width='50%' align='center'><b>GROSS EARNINGS</b></td>
				<td width='50%' align='right'>".CUR." $tot_incomes</td>
			</tr>
		</table>";

	explodeDate($fromdate, $from_year, $from_month, $from_day);
	explodeDate($todate, $to_year, $to_month, $to_day);

	if ($from_year == $to_year
			&& $from_month == $to_month) {
		$title = "Salary Advice";

		if ($emp["payprd"] == "m") {
			$salprd = getMonthName($to_month). " " .getYearOfEmpMon($to_month);
		} else if ($emp["payprd"] == "d" && $fromdate == $todate) {
			$salprd = $pay["week"] . getMonthName($to_month). " " .getYearOfEmpMon($to_month);
		} else if ($emp["payprd"] == "w") {
			$stdate = mktime(0, 0, 0, $to_month, 1, getYearOfEmpMon($to_month));
			$endate = mktime(0, 0, 0, $to_month + 1, -1, getYearOfEmpMon($to_month));
			$paydate = mktimefd($todate);

			$i = 1;
			while ($stdate <= $endate) {
				if (date("w", $stdate) == 5) {
					if (date("W", $stdate) == date("W", $paydate_f)
							&& date("W", $stdate) == date("W", $paydate_t)) {
						$salprd = date("j", $stdate) . " " . getMonthName($to_month). " " .getYearOfEmpMon($to_month);
						break;
					}
					++$i;
				}

				/* next day */
				$stdate += 24 * 60 * 60;
			}
		} else if ($emp["payprd"] == "f") {
			$stdate = mktime(0, 0, 0, $to_month, 1, getYearOfEmpMon($to_month));
			$endate = mktime(0, 0, 0, $to_month + 1, -1, getYearOfEmpMon($to_month));
			$paydate_f = mktimefd($fromdate);
			$paydate_t = mktimefd($todate);

			$c = 0;
			$fnd_week_f = 0;
			$fnd_week_t = 0;
			while ($stdate <= $endate) {
				//date("W", $stdate) == date("W", $paydate)
				if (date("w", $stdate) == 5) {
					if (date("W", $stdate) == date("W", $paydate_f)) {
						$fnd_week_f = 1;
					}

					if (date("W", $stdate) == date("W", $paydate_t)) {
						$fnd_week_t = 1;
					}

					if ((++$c % 2 == 0 || $c == 5) && ($fnd_week_f || $fnd_week_t)) {
						if ($fnd_week_f == 1 && $paydate_f <= $stdate + (48*3600)) {
							$fnd_week_f = 2;
							$salprd_f = date("j", $stdate) . " " . getMonthName($from_month). " " .getYearOfEmpMon($from_month);
						}

						if ($fnd_week_t == 1 && $paydate_t <= $stdate + (48*3600)) {
							$fnd_week_t = 2;
							$salprd_t = date("j", $stdate) . " " . getMonthName($to_month). " " .getYearOfEmpMon($to_month);
						}

						/* now check that they are the same, and if so set the display */
						if ($fnd_week_f + $fnd_week_t = 4) {
							if ($salprd_f == $salprd_t) {
								$salprd = $salprd_f;
							}

							break;
						}
					}
				}

				/* next day */
				$stdate += 24 * 60 * 60;
			}
		}
	}

	if (!isset($salprd)) {
		$salprd = $title = "$fromdate TO $todate";
	}

	$OUT = "
		<center>
		<h2>".COMP_NAME."<br>$title</h2>
		<table border=1 ".TMPL_tblDflts." width='750'>
			<tr>
				<td width='50%' align='center'><b>Employee Details:</b></td>
				<td>$dates</td>
			</tr>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<td>Name:</td>
							<td>$emp[sname], $emp[fnames]</td>
						</tr>
						<tr>
							<td>Number:</td>
							<td>$emp[enum]</td>
						</tr>
						<tr>
							<td>ID:</td>
							<td>$emp[idnum]</td>
						</tr>
						<tr>
							<td>Tax No:</td>
							<td>$emp[taxref]</td>
						</tr>
						<tr>
							<td>Rate:</td>
							<td>".CUR." $emp[basic_sal]</td>
						</tr>
						<tr>
							<td>Designation:</td>
							<td>$emp[designation]</td>
						</tr>
						<tr>
							<td>Gender:</td>
							<td>$emp[sex]</td>
						</tr>
						<tr>
							<td>Marital Status:</td>
							<td>$emp[marital]</td>
						</tr>
					</table>
				</td>
				<td valign='top'>
            		<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<td width='50%'>Period</td>
							<td width='50%'>$salprd</td>
						</tr>
					</table>
					<table border=1 ".TMPL_tblDflts." width='100%'>
						<tr>
							<td align='center' colspan='2'><b>Company Details:</b></td>
						</tr>
					</table>
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<td>Address:</td>
							<td>".COMP_ADDRESS."</td>
						</tr>
						<tr>
							<td>Tel:</td>
							<td>".COMP_TEL."</td>
						</tr>
						<tr>
							<td>Fax:</td>
							<td>".COMP_FAX."</td>
						</tr>
						<tr>
							<td>Reg No:</td>
							<td>".COMP_REGNO."</td>
						</tr>
						<tr>
							<td>PAYE Ref:</td>
							<td>".COMP_PAYE."</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align='center'><b>COMPANY CONTRIBUTIONS</b></td>
				<td align='center'><b>INCOME</b></td>
			</tr>
			<tr>
				<td>$comp_parts</td>
				<td>$incomes</td>
			</tr>
			<tr>
				<td align='center'></td>
				<td>$grossdata</td>
			</tr>
			<tr>
				<td align='center'><b>DEDUCTIONS</b></td>
				<td align='center'><b>FRINGE BENEFITS</b></td>
			</tr>
			<tr>
				<td>$deductions</td>
				<td>$fringe</td>
			</tr>
			<tr>
				<td>
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<td width='50%'><b>TOTAL DEDUCTIONS</b></td>
							<td width='50%' align='right'>".CUR." $tot_deductions</td>
						</tr>
					</table>
				</td>
				<td>
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<td width='50%' nowrap='t'><b>TOTAL FRINGE BENEFITS</b></td>
							<td width='50%' align='right'>".CUR." $tot_fringe</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan='2'>$exstras</td>
			</tr>
		</table>";
	return $OUT;

}


?>