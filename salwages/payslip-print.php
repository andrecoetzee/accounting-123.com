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

# Get settings
require("../settings.php");
require("../core-settings.php");
require("emp-functions.php");

if (isset($_REQUEST["id"])) {
	$OUTPUT = printPayslip($_REQUEST["id"]);
}elseif (isset($_REQUEST["batch"])){
	#split up into batches and send to function in sequence
	$batch_arr = explode ("|",$_REQUEST["batch"]);
	$null_val = array_pop($batch_arr);
	foreach ($batch_arr AS $each => $own){
		$OUTPUT .= printPayslip($own);
		$OUTPUT .= "<p style='page-break-before: always'>";
	}
}else {
	invalid_use();
}

require ("../tmpl-print.php");



function printPayslip ($id)
{

	global $PRDMON, $MONPRD;
	extract($_REQUEST);

	/* reversals once passed as negative ids */
	if ($id[0] == "-") {
		$rev = true;
	}

	$id = preg_replace("/^-/", "", $id);

	require_lib("validate");
	$v = new validate();
	$v->isOk($id, "num", 1, 20, "Invalid payslip number.");

	if ($v->isError()) {
		return $v->genErrors();
	}




	db_conn('cubit');

	if (isset($rev)) {
		$sql = "SELECT * FROM salr WHERE id = '$id'";
		$rslt = db_exec($sql) or errDie("Unable to select employee payments from database.");
		if (pg_numrows($rslt) < 1) {
			return "<li> - Employee payment not found for selected month (REV)</li>";
		}
		$payr = pg_fetch_array($rslt);

		$sql = "SELECT * FROM salpaid WHERE month='$payr[month]' AND empnum='$payr[empnum]' AND true_ids<'$payr[true_ids]' ORDER BY id DESC";
		$rslt = db_exec ($sql) or errDie ("Unable to select employee payments from database.");
		$pay = pg_fetch_array($rslt);

		$pay["hovert"] -= $payr["hovert"];
		$pay["novert"] -= $payr["novert"];
		$pay["salary"] -= $payr["salary"];
		$slip_id = "-$id";
		$revmsg = "Reversal";

		$max_trueid = $payr["true_ids"];
	} else {
		$sql = "SELECT * FROM salpaid WHERE id = '$id'";
		$rslt = db_exec ($sql) or errDie ("Unable to select employee payments from database.");
		$pay = pg_fetch_array($rslt);

		$slip_id = $id;

		$revmsg = "Advice";

		$max_trueid = $pay["true_ids"];
	}

	//$pay["salary"] += $pay["comm"];

	$sql = "SELECT * FROM employees WHERE empnum='$pay[empnum]'";
	$rslt = db_exec($sql) or errDie ("Unable to select employees from database.");

	if (pg_numrows ($rslt) < 1) {
		$sql = "SELECT * FROM lemployees WHERE empnum='$pay[empnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	}

	$emp = pg_fetch_array($rslt);

	$date = $pay['saldate'];

	/* paye */
	// from = begining of finyear
	if (date("m") > $PRDMON[12]) {
		$fromdate = date("Y")."-".$PRDMON[1]."-01";
	} else {
		$fromdate = (date("Y")-1)."-".$PRDMON[1]."-01";
	}

	/* salaries */
	$sql = "SELECT sum(paye) FROM salpaid
			WHERE saldate>='$fromdate' AND saldate<='$pay[saldate]'
				AND empnum='$pay[empnum]' AND true_ids<=$max_trueid";
	$rslt = db_exec($sql) or errDie("Unable to get paye");

	$paye = pg_fetch_result($rslt, 0, 0);

	/* salary reversals */
	$sql = "SELECT sum(paye) FROM salr
			WHERE saldate>='$fromdate' AND saldate<='$pay[saldate]'
				AND empnum='$pay[empnum]' AND true_ids<=$max_trueid";
	$rslt = db_exec($sql) or errDie("Unable to get paye");

	$paye -= pg_fetch_result($rslt, 0, 0);

	$emp['basic_sal'] = sprint($emp['basic_sal']);

	$dates = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td width='50%'>Date</td>
				<td width='50%'>$date</td>
			</tr>
		</table>";

	$i = 0;

	$incomes = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td width='80%' align='center'>Description</td>
				<td align='center'>Amount</td>
			</tr>";

	db_conn('cubit');
	$sql = "SELECT * FROM emp_inc WHERE payslip='$slip_id' ORDER BY amount DESC";
	$rslt = db_exec($sql) or errDie("Unable to get data.");

	$tot_incomes = 0;
	while($data = pg_fetch_array($rslt)) {
		$data["amount"] = sprint(abs($data["amount"]));

		$incomes .= "
		<tr>
			<td>$data[description]</td>
			<td align='right' nowrap>".CUR." $data[amount]</td>
		</tr>";

		if ($data["type"] == "INCO") {
			if ($pay["novert"] > 0) {
				$incomes .= "
				<tr>
					<td colspan='2'>&nbsp;&nbsp;&nbsp; $pay[novert] Hours Normal Overtime</td>
				</tr>";
				++$i;
			}

			if ($pay["hovert"] > 0) {
				$incomes .= "
				<tr>
					<td colspan='2'>&nbsp;&nbsp;&nbsp; $pay[hovert] Hours Holiday Overtime</td>
				</tr>";
				++$i;
			}
		}

		$i++;

		if ( $data["description"] != "Fringe Benefits Total" ) {
			$tot_incomes = $tot_incomes + $data['amount'];
		}
	}

	while ($i < 7) {
		$incomes .= TBL_BR;
		$i++;
	}

	$incomes .= "
	</table>";

	$i = 0;

	$comp_parts = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td align='center'>Description</td>
				<td align='center'>Amount</td>
			</tr>";

	$sql = "SELECT * FROM emp_com WHERE payslip='$slip_id' AND description !='SDL'
			ORDER BY amount DESC";
	$rslt = db_exec($sql) or errDie("Unable to get data.");

	while ($data = pg_fetch_array($rslt)) {
		$data["amount"] = sprint(abs($data["amount"]));

		$comp_parts .= "
		<tr>
			<td width='80%'>$data[description]</td>
			<td width='20%' align='right' nowrap>".CUR." $data[amount]</td>
		</tr>";

		$i++;
	}

	while ($i < 7) {
		$comp_parts .= TBL_BR;
		$i++;
	}

	$comp_parts .= "
	</table>";

	$i = 0;

	$deductions = "
	<table ".TMPL_tblDflts." width='100%'>
	<tr>
		<td width='90%' align='center'>Description</td>
		<td align='center'>Amount</td>
	</tr>";

    $sql = "SELECT * FROM emp_ded WHERE payslip='$slip_id' ORDER BY amount DESC";
	$rslt = db_exec($sql) or errDie("Unable to get data.");

	$tot_deductions = 0;
	while ($data = pg_fetch_array($rslt)) {
		if ( $data["type"] == "PAYE" && $data["amount"] <= 375 ) {
			$data["description"] = "SITE";
		}

		$data["amount"] = sprint(abs($data["amount"]));

		$deductions .= "
		<tr>
			<td>$data[description]</td>
			<td align='right' nowrap>".CUR." $data[amount]</td>
		</tr>";

		$i++;
		$tot_deductions += $data['amount'];
	}

	while ($i < 6) {
		$deductions .= TBL_BR;
		$i++;
	}

	$deductions .= "
	</table>";

	$fringe = "
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td width='90%' align='center'>Description</td>
				<td align='center'>Amount</td>
			</tr>";

    $sql = "SELECT * FROM emp_frin WHERE payslip='$slip_id' ORDER BY amount DESC";
	$rslt = db_exec($sql) or errDie("Unable to get data.");

	$tot_fringe = 0;
	$i = 0;
	while ($data = pg_fetch_array($rslt)) {
		$data["amount"] = sprint(abs($data["amount"]));

		$fringe .= "
		<tr>
			<td>$data[description]</td>
			<td align='right' nowrap>".CUR." $data[amount]</td>
		</tr>";

		$i++;
		$tot_fringe += $data['amount'];
	}

	while ($i < 6) {
		$fringe .= TBL_BR;
		$i++;
	}

	$fringe .= "
	</table>";

	$pay["salary"] = sprint($pay["salary"]);

	if ($emp["payprd"] == "m") {
		$salprd = getMonthName($pay["month"]). " " .getYearOfEmpMon($pay["month"]);
	} else if ($emp["payprd"] == "d") {
		$salprd = $pay["week"] . getMonthName($pay["month"]). " " .getYearOfEmpMon($pay["month"]);
	} else if ($emp["payprd"] == "w") {
		$stdate = mktime(0, 0, 0, $pay["month"], 1, getYearOfEmpMon($pay["month"]));
		$endate = mktime(0, 0, 0, $pay["month"] + 1, 0, getYearOfEmpMon($pay["month"]));

		$i = 1;
		while ($stdate <= $endate) {
			if (date("w", $stdate) == 5) {
				if ($i == $pay["week"]) {
					$salprd = date("j", $stdate) . " " . getMonthName($pay["month"]). " " .getYearOfEmpMon($pay["month"]);
					break;
				}
				++$i;
			}

			/* next day */
			$stdate += 24 * 60 * 60;
		}
	} else if ($emp["payprd"] == "f") {
		$i = 1;

		/* find first friday of tax year */
		$stdate = mktime(0, 0, 0, 3, 1, getYearOfEmpMon(3));
		while (date("w", $stdate) != 5) {
			$stdate = mktime(0, 0, 0, 3, ++$i, DATE_YEAR);
		}

		// hack: go one week back so the +14 increases are easier
		$stdate -= 7 * 24 * 3600;

		/* end on the last day of the selected month */
		$endate = mktime(0, 0, 0, $pay["month"] + 1, 0, getYearOfEmpMon($pay["month"]));

		/* count weeks from start of tax year */
		$i = 1;
		while ($stdate <= $endate) {
			if (date("m", $stdate) == $pay["month"]) {
				if ($i == $pay["week"]) {
					$salprd = date("j", $stdate) . " " . getMonthName($pay["month"]). " " .getYearOfEmpMon($pay["month"]);
					break;
				}
				++$i;
			}

			/* next day */
			$stdate += 24 * 60 * 60 * 14;
		}
	}

	$exstras="
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td width='25%'>LEAVE DAYS DUE</td>
				<td width='25%'>".getLeave($pay['empnum'],"leave_vac")."</td>
				<td width='25%'><b>NETT PAY</b></td>
				<td width='25%'><b>".CUR." $pay[salary]</b></td>
			</tr>
			<tr>";
	
	if ($emp["saltyp"] == "h") {
		$exstras .= "
		<td width='25%'>Hours worked</td>
		<td width='25%'>$pay[hours] at ".CUR." ".sprint($pay["salrate"])."/Hour</td>";
	} else {
		$exstras .= "
		<td width='25%'></td>
		<td width='25%'></td>";
	}
	
	$exstras .= "
		<td width='25%'>Total Employee's Tax</td>
		<td width='25%'>".CUR." ".sprint($paye)."</td>
	</tr>
	</table>";

	$tot_incomes = sprint(abs($tot_incomes));
	$tot_deductions = sprint(abs($tot_deductions));

	$grossdata="
	<table ".TMPL_tblDflts." width='100%'>
	<tr>
		<td width='50%' align='center'><b>GROSS EARNINGS</b></td>
		<td width='50%' align='right'>".CUR." $tot_incomes</td>
	</tr>
	</table>";

	$PaySlip = "
		<center>
		<h2>".COMP_NAME." <br>Salary $revmsg</h2>
		<table ".TMPL_tblDflts." border='1' width='750'>
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

					<table ".TMPL_tblDflts." border='1' width='100%'>
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
							<td width='50%'>".CUR." $tot_deductions</td>
						</tr>
					</table>
				</td>
				<td>
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<td width='50%' nowrap='t'><b>TOTAL FRINGE BENEFITS</b></td>
							<td width='50%'>".CUR." ".sprint($tot_fringe)."</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan=2>$exstras</td>
			</tr>
		</table>";
	return $PaySlip;

}



function getLeave ($empnum, $type)
{

    switch ($type) {
        case "leave_vac":
            $ttype = "vaclea";
            break;
        case "leave_sick":
            $ttype = "siclea";
            break;
        case "leave_study":
            $ttype = "stdlea";
            break;
    }

    # Connect to db
    db_connect ();

    # Get employee info to edit
    $sql = "SELECT $ttype FROM employees WHERE empnum = '$empnum'";
    $empRslt = db_exec ($sql) or errDie ("Unable to select employee info from database.");
    if (pg_numrows ($empRslt) < 1) {
            return "Invalid employee number.";
    }
    $emp = pg_fetch_array($empRslt);
    $initial_days = $emp[$ttype];

    # Get sum of days taken
    $sql = "SELECT SUM (workingdays) AS taken FROM empleave WHERE empnum='$empnum' AND type='$type' AND approved = 'y'";
    $leaveRslt = db_exec ($sql) or errDie ("Unable to select employee leave from database.");
    if(pg_numrows($leaveRslt) > 0){
        $myLeave = pg_fetch_array ($leaveRslt);
        $taken_days = $myLeave["taken"];
    }else{
        $taken_days = 0;
    }

    $allowed = $initial_days - $taken_days;

    $arr[0] = $type;
    $arr[1] = $allowed;
    return $allowed;

}


?>