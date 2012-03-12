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
require ("../libs/ext.lib.php");

## Decide
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
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

require ("../template.php");




function mlist($name, $curr="")
{

	$month=1;
	$months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
	$list = "<select name=$name>";
	while($month <= 12){
		if($month == $curr){
			$s="selected";
		} else {
			$s="";
		}
		$list .="<option $s value='$month'>$months[$month]</option>";
		$month++;
	}
	$list .= "</select>";

	return $list;

}




# Select employee
function slctEmployee ()
{

	db_conn('cubit');

	$Sl = "SELECT empnum,enum, sname, fnames FROM employees WHERE div = '".USER_DIV."' ORDER BY sname,fnames";
	$Ry = db_exec($Sl) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($Ry) < 1){
		return "
			No employees found in database.<p>
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
				<tr bgcolor='#88BBFF'>
					<td><a href='../main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}

	$employees = "<select size=1 name=empnum>";

	while ($myEmp = pg_fetch_array ($Ry)) {
		$employees .= "<option value='$myEmp[empnum]'>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</option>";
	}

	$employees .= "</select>";

	$slctEmployee = "
		<h3>Select employee to process</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='process'>
			<tr>
				<th colspan='2'>Employee</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Employee</td>
				<td align='center'>$employees</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select month</td>
				<td align='center'>".mlist("MON", date("m"))."</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Process &raquo;'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='#88BBFF'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $slctEmployee;

}




function process ($_POST)
{

	extract($_POST);

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



	# Get employee details
	db_conn('cubit');

	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}
	$myEmp = pg_fetch_array ($empRslt);
	
	if ( ! empty($idnum) ) {
		$bd_year = substr($myEmp["idnum"], 0, 2);
		$bd_month = substr($myEmp["idnum"], 2, 2);
		$bd_day = substr($myEmp["idnum"], 4, 2);
		
		if ( ! checkdate($bd_month, $bd_day, $bd_year) ) {
			$OUTPUT = "
				<h3>Process Employee Salary</h3>
				<li class=err>
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

	if($myEmp['payprd'] == "m") {
		$Sl = "SELECT * FROM salpaid WHERE empnum='$empnum' AND month='$mm' AND substr(saldate,1,4)='$yy'";
		$Ri = db_exec($Sl);

		$paid = pg_num_rows($Ri);

		$Sl = "SELECT * FROM salr WHERE empnum='$empnum' AND month='$mm' AND substr(saldate,1,4)='$yy'";
		$Ri = db_exec($Sl);

		$upaid = pg_num_rows($Ri);

		$upaid += 0;

		$paid -= $upaid;

	}

	if(isset($paid) && ($paid > 0)) {
		return "<li class='err'>You have already processed a salary for that period</li>".slctEmployee();
	}
	
	switch ( $myEmp["saltyp"] ) {
	case "h":
	case "m":
		$divisor = 1;
		break;
	case "w":
		$divisor = 52 / 12;
		break;
	case "f":
		$divisor = 26 / 12;
	}

	# fringe benefits
	$fringes = "";
	$i = 0;
	$sql = "SELECT * FROM fringebens WHERE div = '".USER_DIV."' ORDER BY fringeben";
	$rslt = db_exec ($sql) or errDie ("Unable to select allowances from database.");
	if ( pg_num_rows ($rslt) < 1 ) {
		$fringes = "<tr><td bgcolor='".bgcolorg()."' colspan='2' align='center'>None found in database.</td></tr>\n";
	} else {
		while ($myFringe = pg_fetch_array ($rslt)) {
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

			# check if employee has allowance
			$sql = "SELECT * FROM empfringe WHERE fringeid='$myFringe[id]' AND empnum='$myEmp[empnum]' AND div = '".USER_DIV."'";
			$empRslt = db_exec ($sql) or errDie ("Unable to retrieve fringe benefit info from database.");
			if (pg_numrows ($empRslt) > 0) {
				$empFringe = pg_fetch_array ($empRslt);
				
				$fringes .= "
					<tr bgcolor='$bgColor'>
						<td>$myFringe[fringeben]</td>";

				if ( substr($empFringe["type"], 0, 4) == "Perc" ) {
					$empFringe["amount"] = sprint($myEmp["basic_sal"] * ($empFringe["amount"]/100) / $divisor);
				} else {
					$empFringe['amount'] = sprint($empFringe['amount'] / $divisor);
				}
				
				$fringes .= "
						<td align='center'>
							".CUR."<input type='hidden' name='fringeid[]' value='$empFringe[fringeid]'>
		                    <input type='hidden' name='fringename[]' value='$myFringe[fringeben]'>
							<input type='text' size='10' name='fringebens[]' value='$empFringe[amount]'>
							<input type='hidden' name='fringeaccs[]' value='$empFringe[accid]'>
						</td>
					</tr>";

				$grossal += $empFringe["amount"];
			}

			$i++;
		}
	}

	# get allowances
	$allowances = "";
	$i = 0;
	$sql = "SELECT * FROM allowances WHERE div = '".USER_DIV."' ORDER BY allowance";
	$allowRslt = db_exec ($sql) or errDie ("Unable to select allowances from database.");
	if (pg_numrows ($allowRslt) < 1) {
		$allowances = "<tr><td bgcolor='".TMPL_tblDataColor1."' colspan=2 align=center>None found in database.</td></tr>\n";
	} else {
		while ($myAllow = pg_fetch_array ($allowRslt)) {
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

			# check if employee has allowance
			$sql = "SELECT * FROM empallow WHERE allowid='$myAllow[id]' AND empnum='$myEmp[empnum]' AND div = '".USER_DIV."'";
			$empAllowRslt = db_exec ($sql) or errDie ("Unable to select allowance info from database.");
			if (pg_numrows ($empAllowRslt) > 0) {
				$allowances .= "<tr bgcolor='$bgColor'><td>$myAllow[allowance]</td>";
				$myEmpAllow = pg_fetch_array ($empAllowRslt);

				if ( substr($myEmpAllow["type"], 0, 4) == "Perc" ) {
					$myEmpAllow["amount"] = sprint($myEmp["basic_sal"] * ($myEmpAllow["amount"]/100) / $divisor);
				} else {
					$myEmpAllow['amount'] = sprint($myEmpAllow['amount'] / $divisor);
				}
				$allowances .= "
						<td align='center'>
							".CUR."<input type='hidden' size='10' name='allowid[]' value='$myEmpAllow[allowid]'>
		                    <input type='hidden' size='30' name='allowname[]' value='$myAllow[allowance]'>
							<input type='hidden' size='10' name='allowtax[]' value='$myAllow[add]'>
							<input type='text' size='10' name='allowances[]' value='$myEmpAllow[amount]'>
							<input type='hidden' name='allowaccs[]' value='$myEmpAllow[accid]'>
						</td>
					</tr>";

				$grossal += $myEmpAllow["amount"];
			}

			$i++;
		}
	}

	# Deductions
	$deductions = "
		<tr>
			<td colspan='2'>
				<table width='100%' ".TMPL_tblDflts.">
					<tr>
						<th>Details</th>
						<th>Employee Contribution</th>
						<!--<th>Employer Contribution</th>//-->
					</tr>";
	$i = 0;
	$sql = "SELECT * FROM salded WHERE div = '".USER_DIV."' ORDER BY deduction";
	$deductRslt = db_exec ($sql) or errDie ("Unable to select deductions from database.");
	if (pg_numrows ($deductRslt) < 1) {
		$deductions = "<tr><td bgcolor='".TMPL_tblDataColor1."' colspan=2 align=center>None found in database.</td></tr>\n";
	} else {
		while ($myDeduct = pg_fetch_array ($deductRslt)) {
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			# check if employee has deduction
			$sql = "SELECT * FROM empdeduct WHERE dedid='$myDeduct[id]' AND empnum='$myEmp[empnum]' AND div = '".USER_DIV."'";
			$empDeductRslt = db_exec ($sql) or errDie ("Unable to select Deduction info from database.");
			
			if (pg_numrows ($empDeductRslt) > 0) {
				$myEmpDeduct = pg_fetch_array ($empDeductRslt);
				if ( $myEmpDeduct["grosdeduct"] == "y" ) {
					$deductions .= "<tr bgcolor='$bgColor'><td>$myDeduct[deduction] (Deducted from Gross Salary)</td>";
					$sal_calcfrom = $grossal;
				} else {
					$deductions .= "<tr bgcolor='$bgColor'><td>$myDeduct[deduction]</td>";
					$sal_calcfrom = $myEmp['basic_sal'];
				}
				if($myEmpDeduct['type']=="Amount") {
					$myEmpDeduct['amount']=sprint($myEmpDeduct['amount'] / $divisor);
				} else {
					$myEmpDeduct['amount']=sprint($sal_calcfrom*$myEmpDeduct['amount']/100 / $divisor);
				}

				// calculate employer contribution to deduction
				if ( $myEmpDeduct["employer_type"] == "Amount" ) {
					$myEmpDeduct["employer_amount"] = sprint($myEmpDeduct["employer_amount"] / $divisor);
				} else {
					$myEmpDeduct["employer_amount"] = sprint($myEmpDeduct["amount"] * $myEmpDeduct["employer_amount"] / 100 / $divisor);
				}

				$deductions .= "
					<td align='center'>
						".CUR."<input type='hidden' size='10' name='deductid[]' value='$myDeduct[id]'>
						<input type='hidden' size='30' name='deductname[]' value='$myDeduct[deduction]'>
						<input type='text' size='10' name='deductions[]' value='$myEmpDeduct[amount]'>
						<input type='hidden' name='dedaccs[]' value='$myEmpDeduct[accid]'>
					</td>
						<input type='hidden' size='10' name='employer_deductions[]' value='$myEmpDeduct[employer_amount]'>
						<input type='hidden' size='10' name='deducttax[]' value='$myDeduct[add]'>
						<input type='hidden' name='grosdeduct[]' value='$myEmpDeduct[grosdeduct]'>
						<input type='hidden' name='bal_dedaccs[]' value='$myDeduct[accid]'>
				</tr>";
			}
			$i++;
		}
	}
	$deductions .= "</table></td></tr>";

	$salarr = array("m"=>"Per Month", "w"=>"Per Week", "f"=>"Fortnightly", "h"=>"Per Hour");
	$salnarr = array("d"=>"Day(s)", "h"=>"Hour(s)");
	$saltype = $salarr[$myEmp['saltyp']];
	$multi = "";
	if($myEmp['saltyp'] == 'd' || $myEmp['saltyp'] == 'h'){
		$salntype = $salnarr[$myEmp['saltyp']];
		$multi = "x <input type=text size=3 name=multi value='1'> $salntype";
	} else {
		$saltype="";
	}

	db_conn('cubit');
	$Sl="SELECT * FROM bankacct WHERE btype != 'int' AND div = '".USER_DIV."' ORDER BY accname ASC";
	$Ry=db_exec($Sl) or errDie("Unable to get bank account.");

	if(pg_numrows($Ry) < 1){
		return "<li class='err'> There are no bank accounts found in Cubit.
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	$banks="<select name='accid'>";

	while($acc = pg_fetch_array($Ry)){
		$banks .= "<option value='$acc[bankid]'>$acc[accname] ($acc[acctype])</option>";
	}

	$banks.="</select>";

	$myEmp['loaninstall'] += 0;

	if($myEmp['paytype'] == "Cash") {
		$paydetails = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>Employee paid cash</td>
			</tr>
			<input type='hidden' name='accid' value='0'>";
	} elseif($myEmp['paytype'] == "Ledger Account") {
		db_conn('core');

		$Sl = "SELECT accid,accname FROM accounts ORDER BY accname";
		$Ri = db_exec($Sl);

		$accounts = "
			<select name='account'>
				<option value='#'>Select Account</option>";

		while($ad = pg_fetch_array($Ri)) {
			if(isset($account) && $account == $ad['accid']) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$accounts .= "<option value='$ad[accid]'>$ad[accname]</option>";
		}

		$accounts .= "</select>";

		$paydetails = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Ledger Account for payment</td>
				<td>$accounts</td>
			</tr>
			<input type='hidden' name='accid' value='0'>";
	} else {
		$paydetails = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank Account</td>
				<td valign='center'>$banks</td>
			</tr>";
	}

	$h1 = "";
	$h2 = "";

	$db = Array(
		"comp_pension" 		=> $myEmp["comp_pension"],
		"emp_pension"		=> $myEmp["emp_pension"],
		"comp_provident"	=> $myEmp["comp_provident"],
		"emp_provident"		=> $myEmp["emp_provident"],
		"comp_uif" 			=> $myEmp["comp_uif"],
		"emp_uif"			=> $myEmp["emp_uif"],
		"comp_other" 		=> $myEmp["comp_other"],
		"emp_other"			=> $myEmp["emp_other"]
	);

	if(isset($basic_sal)) {
		$myEmp['basic_sal'] = $basic_sal;
		$myEmp['all_travel'] = $all_travel;
		$myEmp['bonus'] = $bonus;
		$myEmp['commission'] = $commission;
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
		$h1 = $novert;
		$h2 = $hovert;
	} else {
		$day = date("d");
		$mon = date("m");
		$year = date("Y");

		if ( $myEmp["payprd"] == "w" || $myEmp["payprd"] == "f" ) {
			$tmpmon = date("j");
			$daycount = date("t");
			$dayweek = date("D");
	
			if ( strtolower($dayweek) == $myEmp["payprd_day"] && $day+7 > $daycount ) {
				$process_comp_deductions = true;
			} else {
				$process_comp_deductions = false;
			}
		} else {
			$process_comp_deductions = true;
		} 

		$myEmp["emp_pension"] = sprint($myEmp["basic_sal"] * ($myEmp["emp_pension"]/100));
		$myEmp["comp_pension"] = sprint($myEmp["basic_sal"] * ($myEmp["comp_pension"]/100));
		$myEmp["emp_provident"] = sprint($myEmp["basic_sal"] * ($myEmp["emp_provident"]/100));
		$myEmp["comp_provident"] = sprint($myEmp["basic_sal"] * ($myEmp["comp_provident"]/100));
		$myEmp["emp_medical"] = sprint($myEmp["emp_medical"] / $divisor);
		$myEmp["comp_medical"] = sprint($myEmp["comp_medical"] / $divisor);
		$myEmp["emp_ret"] = sprint($myEmp["emp_ret"] / $divisor);
		$myEmp["comp_ret"] = sprint($myEmp["comp_ret"] / $divisor);
		$myEmp["loaninstall"] = sprint(($myEmp["loaninstall"] / $divisor));
		$myEmp["all_travel"] = sprint($myEmp["all_travel"] / $divisor);
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

	//$rt="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	$rt="<tr><th colspan=2>Reimbursements</th></tr>";

	$Sl="SELECT * FROM rbs ORDER BY name";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	$i=0;

	if(pg_num_rows($Ri)>0) {

		while($td = pg_fetch_array($Ri)) {
			$bgcolor = ($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

			if(!isset($rbsa[$td['id']])) {
				$rbsa[$td['id']]="";
			}

			$rt .= "
				<tr bgcolor='$bgcolor'>
					<td><input type='hidden' name='rbs[$td[id]]' value='$td[id]'>$td[name]</td>
					<td>".CUR." <input type='text' size='10' name='rbsa[$td[id]]' value='".$rbsa[$td['id']]."' class='right'></td>
				</tr>";

			$i++;
		}
	} else {
		$rt .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2' align='center'>There are no reimbursements</td>
			</tr>";
	}

	if($myEmp['payprd'] == "w") {
		$weeks = "
			<select name='week'>
				<option value='1'>Week 1</option>
				<option value='2'>Week 2</option>
				<option value='3'>Week 3</option>
				<option value='4'>Week 4</option>
				<option value='5'>Week 5</option>
			</select>";
		$row = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Period</td>
				<td>$weeks</td>
			</tr>";
	} elseif($myEmp['payprd'] == "f") {
		$weeks = "
			<select name='week'>
				<option value='1'>Week 1-2</option>
				<option value='2'>Week 3-4</option>
				<option value='3'>Week 5</option>
			</select>";
		$row = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Period</td>
				<td>$weeks</td>
			</tr>";
	} else {
		$weeks = "";
		$row = "<input type='hidden' name='week' value='0'>";
	}

	if ( ! isset($annual) && $myEmp["sal_bonus_month"] == $MON ) {
		$annual = $myEmp["sal_bonus"];
	} else if ( ! isset($annual) ) {
		$annual = 0;
	}

	if ( $myEmp["saltyp"] == "m" || $myEmp["saltyp"] == "h" ) {
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

	if ( $myEmp["saltyp"] == "w" ) {
		if ( ! isset($wh_total) ) $wh_total = $myEmp["hpweek"];
		if ( ! isset($wh_actual) ) $wh_actual = $wh_total;
	}

	if ( $myEmp["saltyp"] == "f" ) {
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
		//f_cuif		= sf.elements['comp_uif'];
		//f_euif		= sf.elements['emp_uif'];
		f_cother	= sf.elements['comp_other'];
		f_eother	= sf.elements['emp_other'];

		db_cpension	= ".$db["comp_pension"].";
		db_epension	= ".$db["emp_pension"].";
		db_cprov		= ".$db["comp_provident"].";
		db_eprov	= ".$db["emp_provident"].";
		//db_cuif		= ".$db["comp_uif"].";
		//db_euif		= ".$db["emp_uif"].";
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
				val_cpension 	= x * db_cpension / 100;
				val_epension 	= x * db_epension / 100;
				val_cprov		= x * db_cprov / 100;
				val_eprov		= x * db_eprov / 100;
				//val_cuif		= x * db_cuif / 100;
				//val_euif		= x * db_euif / 100;
				val_cother		= x * db_cother / 100;
				val_eother		= x * db_eother / 100;

				val_cpension 	= val_cpension.toFixed(2);
				val_epension 	= val_epension.toFixed(2);
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
		<h3>Process Salary for $myEmp[sname], $myEmp[fnames]</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' id='salfrm'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='empnum' value='$empnum'>
			<input type='hidden' name='MON' value='$MON'>
			<input type='hidden' name='saltyp' value='$myEmp[saltyp]'>
			<input type='hidden' name='loanint' value='$myEmp[loanint]'>
			<input type='hidden' name='process_comp_deductions' value='$process_comp_deductions'>
			<input type='hidden' name='divisor' value='$divisor'>
			<tr><th colspan='2'>Salary Details for the Pay Period</th></tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Basic salary</td>
				<td>".CUR." <input type='text' size='10' name='basic_sal' value='$myEmp[basic_sal]' class='right' onChange='changedfield();'> $saltype $multi</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Total Work Hours:</td>
				<td><input type='text' size='10' name='wh_total' value='$wh_total' class='right' onChange='workhours();'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Actual Hours Worked:</td>
				<td><input type='text' size='10' name='wh_actual' value='$wh_actual' class='right' onChange='workhours();'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Normal Overtime</td>
				<td nowrap><input type='text' size='5' name='novert' value='$h1' class='right'> Hrs</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Public Holiday Overtime</td>
				<td nowrap><input type='text' size='5' name='hovert' value='$h2' class='right'> Hrs</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Special Bonus/Additional Salary</td>
				<td>".CUR." <input type='text' size='10' name='bonus' value='$myEmp[bonus]' class='right'></td>
				<td rowspan='2'>
					<li class='err'>An amount entered here will be treated as a recurring bonus/payment per pay period for PAYE purposes, the amount will not be treated as an annual payment. If the amount paid as a bonus is a once off/annual payment please use the Bonus(Annual Payments) option or override the PAYE ro reflect an annual payment.</li>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bonus(Annual/Once Off Payments)</td>
				<td nowrap>".CUR." <input type='text' size='10' name='annual' value='$annual' class='right'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Commission</td>
				<td nowrap>".CUR." <input type='text' size='10' name='commission' value='$myEmp[commission]' class='right'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Low or interest-free loan</td>
				<td nowrap>".CUR." <input type='text' size='10' name='loaninstall' value='$myEmp[loaninstall]' class='right'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Travel Allowance</td>
				<td nowrap>".CUR." <input type='text' size='10' name='all_travel' value='$myEmp[all_travel]' class='right'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap>Pension: Company Contribution</td>
				<td nowrap>".CUR." <input type='text' size='10' name='comp_pension' value='$myEmp[comp_pension]' class='right'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap>Pension: Employee Deduction</td>
				<td nowrap>".CUR." <input type='text' size='10' name='emp_pension' value='$myEmp[emp_pension]' class='right'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap>Provident: Company Contribution</td>
				<td nowrap>".CUR." <input type='text' size='10' name='comp_provident' value='$myEmp[comp_provident]' class='right'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap>Provident: Employee Deduction</td>
				<td nowrap>".CUR." <input type='text' size='10' name='emp_provident' value='$myEmp[emp_provident]' class='right'></td>
			</tr>
			<!--
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap>UIF: Company Contribution</td>
				<td nowrap>".CUR." <input type='text' size='10' name='comp_uif' value='$myEmp[comp_uif]' class='right'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap>UIF: Employee Deduction</td>
				<td nowrap>".CUR." <input type='text' size='10' name='emp_uif' value='$myEmp[emp_uif]' class='right'></td>
			</tr>
			//-->
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap>Retirement Annuity: Company Contribution</td>
				<td nowrap>".CUR." <input type='text' size='10' name='comp_ret' value='$myEmp[comp_ret]' class='right'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap>Retirement Annuity: Employee Deduction</td>
				<td nowrap>".CUR." <input type='text' size='10' name='emp_ret' value='$myEmp[emp_ret]' class='right'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap>Medical Aid: Company Contribution</td>
				<td nowrap>".CUR." <input type='text' size='10' name='comp_medical' value='$myEmp[comp_medical]' class='right'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td nowrap>Medical Aid: Employee Deduction</td>
				<td nowrap>".CUR." <input type='text' size='10' name='emp_medical' value='$myEmp[emp_medical]' class='right'></td>
			</tr>
			<input type='hidden' name='comp_other' value='0'>
			<input type='hidden' name='emp_other' value='0'>
			<!--
			<tr bgcolor='".bgcolorg()."'>
				<td>Other: Company Contribution</td>
				<td>".CUR." <input type='text' size='10' name='comp_other' value='$myEmp[comp_other]' class='right'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Other: Employee Deduction</td>
				<td>".CUR." <input type='text' size='10' name='emp_other' value='$myEmp[emp_other]' class='right'></td>
			</tr>
			//-->
			$paydetails
			<tr bgcolor='".bgcolorg()."'>
				<td>Date</td>
				<td nowrap><input type='text' size='2' name='day' maxlength='2' value='$day'>-<input type='text' size='2' name='mon' maxlength='2' value='$mon'>-<input type='text' size='4' name='year' maxlength='4' value='$year'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Override PAYE <input type='checkbox' name='mpaye'></td>
				<td>".CUR." <input type='text' size='10' name='mpaye_amount'></td>
			</tr>
			$row
			<tr><th colspan='2'>Fringe Benefits</th></tr>
			$fringes
			<tr><th colspan='2'>Allowances</th></tr>
			$allowances
			<tr><th colspan='2'>Deductions</th></tr>
			$deductions
			$rt
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		$js_workhours
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='#88BBFF'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $process;

}



# Confirm data
function confirm ($_POST)
{

	# get vars
	$_POST = var_makesafe($_POST);
	extract ($_POST);

	if(isset($back)) {
		return slctEmployee();
	}

	$annual += 0;

	$bonus += 0;
	$mpaye_amount += 0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($account)) {
		$v->isOk ($account, "num", 1, 9, "Invalid ledger account.");
	}
	
	$v->isOk ($empnum, "num", 1, 9, "Invalid employee number.");
	$v->isOk ($accid, "num", 1, 9, "Invalid bank number.");
	$v->isOk ($MON, "num", 1, 2, "Invalid month.");
	$v->isOk ($bonus, "float", 1, 11, "Invalid bonus.");
	$v->isOk ($mpaye_amount, "float", 1, 11, "Invalid manual PAYE amount.");
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
		$v->addError("", "Invalid pay period (DIVIS).");
	}

	if($saltyp == 'd' || $saltyp == 'h'){
		$salnarr = array("d"=>"Days", "h"=>"Hours");
		$salntype = $salnarr[$saltyp];
		$v->isOk ($multi, "float", 1, 5, "Invalid number of $salntype.");
		if($multi < 1)
			$v->isOk ("##", "num", 1, 1, "Error : Employee cannot be paid for $multi $salntype.");
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

	$date = $day."-".$mon."-".$year;
	$ydate= $year."-".$mon."-".$day;

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

	db_conn('cubit');

	$sql = "SELECT * FROM employees WHERE empnum='$empnum' AND div = '".USER_DIV."'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}

	$myEmp = pg_fetch_array ($empRslt);

	if($myEmp['payprd'] == "d") {
		$Sl = "SELECT * FROM salpaid WHERE empnum='$empnum' AND saldate='$ydate'";
		$Ri = db_exec($Sl);

		$paid = pg_num_rows($Ri);

		$Sl = "SELECT * FROM salr WHERE empnum='$empnum' AND saldate='$ydate'";
		$Ri = db_exec($Sl);

		$upaid = pg_num_rows($Ri);

		$upaid += 0;

		$paid -= $upaid;

	}elseif($myEmp['payprd'] == "w") {
		$yy = date("Y");
		$mm = $MON;
		$mm += 0;

		$Sl = "SELECT * FROM salpaid WHERE empnum='$empnum' AND month='$mm' AND substr(saldate,1,4)='$yy' AND week='$week'";
		$Ri = db_exec($Sl);

		$paid = pg_num_rows($Ri);

		$Sl = "SELECT * FROM salr WHERE empnum='$empnum' AND month='$mm' AND substr(saldate,1,4)='$yy' AND week='$week'";
		$Ri = db_exec($Sl);

		$upaid = pg_num_rows($Ri);

		$upaid += 0;

		$paid -= $upaid;

	}elseif($myEmp['payprd'] == "f") {
		$yy = date("Y");
		$mm = $MON;
		$mm += 0;

		$Sl = "SELECT * FROM salpaid WHERE empnum='$empnum' AND month='$mm' AND substr(saldate,1,4)='$yy' AND week='$week'";
		$Ri = db_exec($Sl);

		$paid = pg_num_rows($Ri);

		$Sl = "SELECT * FROM salr WHERE empnum='$empnum' AND month='$mm' AND substr(saldate,1,4)='$yy' AND week='$week'";
		$Ri = db_exec($Sl);

		$upaid = pg_num_rows($Ri);

		$upaid += 0;

		$paid -= $upaid;

	}

	if(isset($paid) && ($paid > 0)) {
		return "<li class='err'>You have already processed a salary for that period</li>".process($_POST);
	}
	
	$salconacc = gethook("accnum", "salacc", "name", "salaries control");
	$salconacc_orig = gethook("accnum", "salacc", "name", "salaries control original");
	
	if ( $salconacc != $salconacc_orig ) {
		block_check($salconacc);
	}
	
	block_check($uifbal = gethook("accnum", "salacc", "name", "uifbal"));
	block_check($intrec = gethook("accnum", "salacc", "name", "interestreceived"));
	block_check($sdlbal = gethook("accnum", "salacc", "name", "sdlbal"));
	block_check($pa = gethook("accnum", "salacc", "name", "pension"));
	block_check($ma = gethook("accnum", "salacc", "name", "medical"));
	block_check($cash_account= gethook("accnum", "salacc", "name", "cash"));
	block_check($retire = gethook("accnum", "salacc", "name", "retire"));
	block_check($provident = gethook("accnum", "salacc", "name", "provident"));
	block_check($commacc = gethook("accnum", "salacc", "name", "Commission"));
	block_check($payeacc = gethook("accnum", "salacc", "name", "PAYE"));
	block_check($uifacc = gethook("accnum", "salacc", "name", "UIF"));

	block_check($providente = $myEmp["expacc_provident"]);
	block_check($retiree = $myEmp["expacc_ret"]);
	block_check($pax = $myEmp["expacc_pension"]);
	block_check($uifexp = $myEmp["expacc_uif"]);
	block_check($max = $myEmp["expacc_medical"]);
	block_check($dedgenerale = $myEmp["expacc_other"]);
	block_check($sdlexp = $myEmp["expacc_sdl"]);
	block_check($salacc = $myEmp["expacc_salwages"]);
	
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
	if ( isset($bal_dedaccs) ) {
		foreach ( $bal_dedaccs as $checkacc ) {
			if ( $checkacc != 0 ) block_check($checkacc);
		}
	}
	
	finish_block_check();

	# The Paye
	$tyear = 12;
	switch($saltyp){
		case 'm':
			$tyear = 12;
			$perhr = sprint($basic_sal / (($myEmp['hpweek'] * 52)/12));
			break;
		case 'w':
			$tyear = 52;
			$perhr = sprint($basic_sal / $myEmp['hpweek']);
			break;
		case 'f':
			$tyear = 26;
			$perhr = sprint($basic_sal / ($myEmp['hpweek'] * 2));
			break;
		case 'd':
			$tyear = (5 * 52);
			$perhr = sprint($basic_sal / ($myEmp['hpweek'] / 5));
			break;
		case 'h':
			$tyear = (45 * 52);
			$perhr = $basic_sal;
			break;
	}

	$overamt = $novert * ($perhr * $myEmp['novert']);
	$overamt += $hovert * ($perhr * $myEmp['hovert']);
	
	$overamt = sprint($overamt);

	# Multiply basic_sal add overtime
	if(isset($multi)){
		$basic_sal = sprint($basic_sal * $multi);
		$tyear = ($tyear/$multi);
	}

	# Zero if not specified
	$commission = $commission + 0;
	$loaninstall = $loaninstall + 0;

	//$basic_sal=$basic_sal+$commission;

	$all_before = "";
	$all_after = "";
	$all_beforeamount = 0;
	$all_afteramount = 0;
	if(isset($allowtax)) {
		foreach ($allowtax as $key => $perc) {
            if($perc == "Yes" and $allowances[$key] > 0) {
				$all_before .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$allowname[$key]</td>
						<td>".CUR." $allowances[$key]</td>
					</tr>";
				$all_beforeamount = ($all_beforeamount  + $allowances[$key]);
            }elseif ($allowances[$key] > 0) {
				$all_after .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$allowname[$key]</td>
						<td>".CUR." $allowances[$key]</td>
					</tr>";
				$all_afteramount = ($all_afteramount  + $allowances[$key]);
			}
        }
    }

	$de_before = "
		<tr>
			<td colspan='2'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Details</th>
						<th>Employee Contribution</th>
						<!--<th>Employer Contribution</th>//-->
					</tr>";
	$de_after = "
		<tr>
			<td colspan='2'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Details</th>
						<th>Employee Contribution</th>
						<!--<th>Employer Contribution</th>//-->
					</tr>";
	$de_beforeamount = 0;
	$de_afteramount = 0;
	$de_beforeamount_emp = 0;
	$de_afteramount_emp = 0;

	if(isset($deducttax)) {
		foreach ($deducttax as $key => $perc) {
            if($perc == "Yes" and $deductions[$key]>0) {
				$de_before .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$deductname[$key]</td>
						<td>".CUR." $deductions[$key]</td>
						<!--<td>".CUR." $employer_deductions[$key]</td>//-->
					</tr>";
				$de_beforeamount = ($de_beforeamount  + $deductions[$key] + $employer_deductions[$key]);
				$de_beforeamount_emp += $employer_deductions[$key];
            }elseif ($deductions[$key]>0) {
				$de_after .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$deductname[$key]</td>
						<td>".CUR." $deductions[$key]</td>
						<!--<td>".CUR." $employer_deductions[$key]</td>//-->
					</tr>";
				$de_afteramount = ($de_afteramount  + $deductions[$key] + $employer_deductions[$key]);
				$de_afteramount_emp += $employer_deductions[$key];
			}
        }
    }

    $de_before .= "</table></td></tr>";
    $de_after .= "</table></td></tr>";

	if ($all_beforeamount > 0) {$all_before ="<tr><th colspan='2'>Allowances</th></tr>".$all_before;}
	if ($all_afteramount > 0) {$all_after ="<tr><th colspan='2'>Allowances</th></tr>".$all_after;}
	if ($de_beforeamount > 0) {$de_before = "<tr><th colspan='2'>Deductions</th></tr>".$de_before;} else {$de_before = "";}
	if ($de_afteramount>0) {$de_after ="<tr><th colspan='2'>Deductions</th></tr>".$de_after;} else {$de_after = "";}
	
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
	
	/* calculate uif */
	$tmp_uif = $basic_sal 
				+ $all_travel 
				+ $overamt 
				+ $comp_provident 
				+ $comp_pension 
				+ $comp_ret
				+ $all_beforeamount
				+ $all_afteramount
				+ $comp_medical
				+ $bonus;
	
	$comp_uif = sprint($tmp_uif * ($myEmp["comp_uif"] / 100));
	$emp_uif = sprint($tmp_uif * ($myEmp["emp_uif"] / 100));
	
	db_conn("cubit");

	$sql = "SELECT value FROM settings WHERE constant='UIF_MAX'";
	$percrslt = db_exec($sql);
	$perc = pg_fetch_array($percrslt);
	$uifmax = $perc['value'];

	if ( $emp_uif > $uifmax ) {
		$emp_uif = sprint($uifmax);
	}

	if ( $comp_uif > $uifmax ) {
		$comp_uif = sprint($uifmax);
	}
	
	/* calculate sdl */
	$tmp_sdl = $basic_sal 
				+ $overamt
				+ $commission
				+ $comp_provident
				+ $all_travel
				+ $all_beforeamount
				+ $all_afteramount
				+ $comp_medical
				+ $bonus;
				
	if ( $age > 65 ) {
		$tmp_sdl -= $comp_medical;
	}
	
	$comp_sdl = $tmp_sdl * ($myEmp["comp_sdl"] / 100);

	// calculate loan fringe benefit amount for this month
	if (!empty($myEmp["loanamt"]) && $myEmp["gotloan"] == "t" && $myEmp["loanamt"] > 0) {
		$loanpart = $loaninstall / $myEmp["loanamt"];
		$fringe_loan = sprint($myEmp["loanfringe"] * $loanpart);
	} else {
		$fringe_loan = "0.00";
	}
	
	$car_count = ($myEmp["fringe_car1"] > 0?1:0) + ($myEmp["fringe_car2"] > 0?1:0);
	
	// if car count is one and employee gets a travel allowance, that car's fringe benefit is calculated
	// as if the second car, and ALSO: contribitions/fuel/service amounts are not deducted from benefit
	$car1_travelall = $car_count == 1 && $all_travel > 0;
	
	if ( $car1_travelall ) {
		$PERC1 = 0.04;
	} else {
		$PERC1 = 0.018;
	}

	// calculate motor car fringe benefit
	if ( $myEmp["fringe_car1"] > 0 ) {
		$fringe_car1 = $myEmp["fringe_car1"] * ($myEmp["fringe_car1"]>=$myEmp["fringe_car2"]?$PERC1:0.04);
		$fringe_car1 /= $divisor;

		if ( $myEmp["fringe_car1_contrib"] > 0 && ! $car1_travelall ) {
			$fringe_car1 -= ($myEmp["fringe_car1_contrib"] / $divisor);
		}

		if ( $myEmp["fringe_car1_fuel"] == 1 && ! $car1_travelall ) {
			$fringe_car1 -= (120 / $divisor);
		}

		if ( $myEmp["fringe_car1_service"] == 1 && ! $car1_travelall ) {
			$fringe_car1 -= (85 / $divisor);
		}

		if ( $fringe_car1 < 0 ) $fringe_car1 = 0;
	} else {
		$fringe_car1 = 0;
	}

	if ( $myEmp["fringe_car2"] > 0 ) {
		$fringe_car2 = $myEmp["fringe_car2"] * ($myEmp["fringe_car2"]>$myEmp["fringe_car1"]?$PERC1:0.04);
		$fringe_car2 /= $divisor;

		if ( $myEmp["fringe_car2_contrib"] > 0 && ! $car1_travelall ) {
			$fringe_car2 -= ($myEmp["fringe_car2_contrib"] / $divisor);
		}

		if ( $myEmp["fringe_car2_fuel"] == 1 && ! $car1_travelall ) {
			$fringe_car2 -= (120 / $divisor);
		}

		if ( $myEmp["fringe_car2_service"] == 1 && ! $car1_travelall ) {
			$fringe_car2 -= (85 / $divisor);
		}

		if ( $fringe_car2 < 0 ) $fringe_car2 = 0;
	} else {
		$fringe_car2 = 0;
	}

	$fringe_car1 = sprint($fringe_car1);
	$fringe_car2 = sprint($fringe_car2);

	// calc medical 1/3rd fringe benefits
	$tot_medical = sprint( $emp_medical + $comp_medical );

	if( $comp_medical > ($tot_medical/3*2) ) {
		$fringe_medical = sprint($comp_medical - ($tot_medical/3*2));
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
		$emp_pension = $basic_sal * 7.5/100;
	} 

	$max_ret = ($myEmp["basic_sal_annum"] * 7.5/100 > 1750)?$myEmp["basic_sal_annum"] * 7.5/100:1750;

	// calculate total gross salary
	$grossal = $basic_sal
					+ $commission
					+ $overamt // overtime
					+ $bonus // monthly bonus
					+ $annual // annual bonus paid this month
					+ $all_beforeamount // allowances added before paye
					+ ( $all_travel*0.5 ) // 50% of travel allowance
					- $de_beforeamount; // deductions deducted before paye (non taxible)
	$grossal_2 = $grossal;

	$grossal_nodedall = $basic_sal
						+ $overamt
						+ $bonus
						+ $annual
						+ $all_travel;

	// pension/provident/ra: calculate deduction amounts, limiting them to maximum amount and only deducting
	// ONE of them for taxable income
	if ( $comp_pension + $emp_pension > 0 ) {
		$tmp = ($grossal_2 + $tot_fringe) * $tyear;
		$maxallowed = ($tmp * 0.075>1750)?$tmp * 0.075:1750;
		if ( $emp_pension > $maxallowed ) {
			$tmp_ded = $maxallowed;
		} else {
			$tmp_ded = $emp_pension;
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
		$maxallowed = ($maxallowed > 3500 - $emp_pension * 12) ? $maxallowed : 3500 - $emp_pension * 12;

		if ( $emp_ret + $comp_ret > $maxallowed / 12 ) {
			$tmp_ded = $maxallowed / 12;
		} else {
			$tmp_ded = $emp_ret + $comp_ret;
		}
		
		$grossal -= $tmp_ded;
	}

	// calculate total paye salary
	// just remove annual this month, and add annual divided by 12
	// because paye is calculate for full twelve months and therefore
	// paye salary is average received each month
	$paye_salary = $grossal
				- $annual // annual bonus fixed for monthly/weerkly/hourly multiply
				// special bonus not calculated annually - $bonus + ($bonus/$tyear) // same with special bonus
				+ $tot_fringe; // total fringe benefits;
				
	// a little hack, apparently the grossal is displayed wrong, in a strictly antisocial.co.za opinion, 
	// i think the person who thinks that must suck
	$grossal += $comp_ret;
				
	if( isset($mpaye) ) {
		$paye = $mpaye_amount;
	} else {
		// calculate paye (take age of 65+ threshold into account)
		if ( ($age >= 65 && ($paye_salary * $tyear) < 60000) || ($paye_salary * $tyear) < 35000 ) {
			$paye = "0.00";
		} else {
			$paye = calculate_paye($paye_salary, $tyear, $age);
			
			if ( $annual > 0 ) {
				$tmp_bonpaye = calculate_paye($paye_salary + $annual/12, $tyear, $age);
				
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
				$bgColor = (++$i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				$fringes_desc .= "
				<tr bgcolor='$bgColor'>
					<td>$fringename[$key]</td>
					<td>".CUR." $fringebens[$key]</td>
				</tr>";

				$fringes .= "
					<input type=hidden name='fringebens[]' value='$fringebens[$key]'>
					<input type=hidden name='fringeid[]' value='$fringeid[$key]'>
					<input type=hidden name='fringename[]' value='$fringename[$key]'>
					<input type=hidden name='fringeaccs[]' value='$fringeaccs[$key]'>";
			}
		}
	}

	if ( ! empty($fringes_desc) ) {
		$fringes_desc = "<tr><th colspan=2>Fringe Benefits</th></tr>$fringes_desc";
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
				$bgColor = (++$i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				$deduct .="
					<input type='hidden' size='10' name='deductname[]' value='$deductname[$key]'>
					<input type='hidden' size='10' name='deductid[]' value='$deductid[$key]'>
					<input type='hidden' size='10' name='deductions[]' value='$deductions[$key]'>
					<input type='hidden' size='10' name='employer_deductions[]' value='$employer_deductions[$key]'>
					<input type='hidden' size='10' name='deducttax[]' value='$deducttax[$key]'>
					<input type='hidden' name='dedaccs[]' value='$dedaccs[$key]'>
					<input type='hidden' name='bal_dedaccs[]' value='$bal_dedaccs[$key]'>";
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
				+ $bonus;

	$nettpay = sprint($nettpay);

	if(isset($rbsa)) {
		$nettpay+=array_sum($rbsa);
		$nettpay=sprint($nettpay);
	}

	db_conn("cubit");
	# Get bank account name
	$sql = "SELECT * FROM bankacct WHERE bankid = '$accid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);

	$bank = pg_fetch_array($bankRslt);

	$basic_sal = sprint($basic_sal);
	$commission = sprint($commission);
	$overamt = sprint($overamt);
	$paye = sprint($paye);
	$nettpay = sprint($nettpay);

	if($myEmp['paytype'] == "Cash") {
		$paydetails=" <tr bgcolor='".bgcolorg()."'><td colspan=2>Pay Salary Cash</td></tr>";
	} else {
		$paydetails=" <tr bgcolor='".bgcolorg()."'><td>Bank Account</td><td>$bank[accname]</td></tr>";
	}

	$bonus = sprint($bonus);
	$annual = sprint($annual);
	$comp_pension = sprint($comp_pension);
	$emp_medical = sprint($emp_medical);
	$comp_ret = sprint($comp_ret);
	$emp_ret = sprint($emp_ret);
	$loaninstall = sprint($loaninstall);
	$emp_pension = sprint($emp_pension);

	if(!isset($account)) {
		$account = 0;
	} else {

		db_conn('core');

		$Sl = "SELECT * FROM accounts WHERE accid='$account'";
		$Ri = db_exec($Sl);

		$ad = pg_fetch_array($Ri);

		$paydetails =" <tr bgcolor='".bgcolorg()."'><td>Ledger Account</td><td>$ad[accname]</td></tr>";
	}

	db_conn('cubit');

	//$rt="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>

	$Sl = "SELECT * FROM rbs ORDER BY name";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$i = 0;

	$rt = "";

	if (pg_num_rows($Ri) > 0) {
		while($td = pg_fetch_array($Ri)) {
			$bgcolor = ($i%2)?TMPL_tblDataColor1:TMPL_tblDataColor2;

			if (!isset($rbsa[$td['id']]) || $rbsa[$td['id']] < 1) {
				continue;
			}

			$rbsa[$td['id']] = sprint($rbsa[$td['id']]);

			if ($i == 0) {
				$rt = "<tr><th colspan='2'>Reimbursements</th></tr>";
			}

			$rt .= "
				<tr bgcolor='$bgcolor'>
					<td><input type='hidden' name='rbs[$td[id]]' value='$td[id]'>$td[name]</td>
					<td>".CUR." <input type='hidden' name='rbsa[$td[id]]' value='".$rbsa[$td['id']]."'>".$rbsa[$td['id']]."</td>
				</tr>";

			$i++;
		}
	} else {
		//$rt.="<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2>There are no reimbursements</td></tr>";
	}

	if ($myEmp['payprd'] == "w") {
		$row = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Period</td>
				<td>$week</td>
			</tr>
			<input type='hidden' name='week' value='$week'>";
	} else if ($myEmp['payprd'] == "f") {
		$row = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Period</td>
				<td>$week</td>
			</tr>
			<input type='hidden' name='week' value='$week'>";
	} else {
		$row="<input type='hidden' name='week' value='0'>";
	}

	$grossal = sprint($grossal);

	$confirm = "
        <table ".TMPL_tblDflts." width='300'>
        <form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='pack'>
			<input type='hidden' name='grossal' value='$grossal'>
			<input type='hidden' name='grossal_nodedall' value='$grossal_nodedall'>
			<input type='hidden' name='empnum' value='$empnum'>
			<input type='hidden' name='account' value='$account'>
			<input type='hidden' name='MON' value=$MON>
			<input type='hidden' name='basic_sal' value='$basic_sal'>
			<input type='hidden' name='commission' value='$commission'>
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
			<input type='hidden' name='process_comp_deductions' value='$process_comp_deductions'>
			$fringes
			$allow
			$deduct
			<tr><th colspan='2'>Salary Details</th></tr>
			<tr bgcolor='".bgcolorg()."'><td>Basic salary</td><td>".CUR." $basic_sal</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Special Bonus/Additional Salary</td><td>".CUR." $bonus</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Bonus(Annual Payments)</td><td>".CUR." $annual</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Commission</td><td>".CUR." $commission</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Travel Allowance</td><td>".CUR." $all_travel</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Pension: Company Contribution</td><td>".CUR." $comp_pension</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Pension: Employee Deduction</td><td>".CUR." $emp_pension</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Provident Fund: Company Contribution</td><td>".CUR." $comp_provident</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Provident Fund: Employee Deduction</td><td>".CUR." $emp_provident</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>UIF: Company Contribution</td><td>".CUR." $comp_uif</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>UIF: Employee Deduction</td><td>".CUR." $emp_uif</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Retirement Annuity: Company Contribution</td><td>".CUR." $comp_ret</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Retirement Annuity: Employee Deduction</td><td>".CUR." $emp_ret</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Medical Aid: Company Contribution</td><td>".CUR." $comp_medical</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Medical Aid: Employee Deduction</td><td>".CUR." $emp_medical</td></tr>
			<!--
			<tr bgcolor='".bgcolorg()."'><td>Other: Company Contribution</td><td>".CUR." $comp_other</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Other: Employee Deduction</td><td>".CUR." $emp_other</td></tr>
			//-->
			<tr bgcolor='".bgcolorg()."'><td>Overtime</td><td>".CUR." $overamt</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Medical Fringe Benefit</td><td>".CUR." $fringe_medical</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Motorcar 1 Fringe Benefit</td><td>".CUR." $fringe_car1</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Motorcar 1 Contribution for Use</td><td>".CUR." $myEmp[fringe_car1_contrib]</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Motorcar 2 Fringe Benefit</td><td>".CUR." $fringe_car2</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Motorcar 2 Contribution for Use</td><td>".CUR." $myEmp[fringe_car2_contrib]</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Loan Interest Fringe Benefit</td><td>".CUR." $fringe_loan</td></tr>
			$fringes_desc
			$all_before
			$de_before
			<tr><th colspan='2'>Gross Salary</th></tr>
			<tr bgcolor='".bgcolorg()."'><td>Gross Salary</td><td>".CUR." $grossal</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>SITE/PAYE</td><td>".CUR." $paye</td></tr>
			<tr><th colspan='2'>Loans</th></tr>
			<tr bgcolor='".bgcolorg()."'><td>Loan Instalment</td><td>".CUR." $loaninstall</td></tr>
			$all_after
			$de_after
			<tr><th colspan='2'>Nett Pay</th></tr>
			<tr bgcolor='".bgcolorg()."'><td>Nett Pay + Reimbursements</td><td>".CUR." $nettpay</td></tr>
			<tr bgcolor='".bgcolorg()."'><td>Amount Paid now</td><td><input type='text' size='10' name='paidamount' value='0'></td></tr>
			$paydetails
			<tr bgcolor='".bgcolorg()."'><td>Date</td><td>$date</td></tr>
			$row
			$rt
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='#88BBFF'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
        </table>";
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

	$ydate = $year."-".$mon."-".$day;
	$ddate = $day."-".$mon."-".$year;

	if(!checkdate($mon, $day, $year)){
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
	}

	$mon=$MON;

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

	db_conn('cubit');

	$nettpay=$income;

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
		$fringes_desc = "<tr><th colspan=2>Fringe Benefits</th></tr>$fringes_desc";
	}

	$all_before="";
	$all_after="";
	$all_beforeamount = 0;
	$all_afteramount = 0;
	if(isset($allowtax)) {
		foreach ($allowtax as $key => $perc) {
			if($perc == "Yes" and $allowances[$key]>0) {
				$all_before .="<tr><td>$allowname[$key]</td><td align=right>".CUR." $allowances[$key]</td></tr>";
				$all_beforeamount = ($all_beforeamount  + $allowances[$key]);
			} elseif ( $allowances[$key] > 0 ) {
				$all_after .="<tr><td>$allowname[$key]</td><td align=right>".CUR." $allowances[$key]</td></tr>";
				$all_afteramount = ($all_afteramount  + $allowances[$key]);
			}
		}
	}

	$de_before = "
		<tr>
			<td colspan='2'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Details</th>
						<th>Employee Contribution</th>
						<!--<th>Employer Contribution</th>//-->
					</tr>";
	$de_after = "
		<tr>
			<td colspan='2'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Details</th>
						<th>Employee Contribution</th>
						<!--<th>Employer Contribution</th>//-->
					</tr>";
	$de_beforeamount = 0;
	$de_afteramount = 0;
	$de_beforeamount_emp = 0;
	$de_afteramount_emp = 0;
	if(isset($deducttax)) {
		foreach ($deducttax as $key => $perc) {
            if($perc == "Yes" and $deductions[$key] > 0) {
				$de_before .="
					<tr>
						<td>$deductname[$key]</td>
						<td align='right'>".CUR." $deductions[$key]</td>
<!--					<td align='right'>".CUR." $employer_deductions[$key]</td> //-->
					</tr>";
				$de_beforeamount = ($de_beforeamount  + $deductions[$key] + $employer_deductions[$key]);
				$de_beforeamount_emp += $employer_deductions[$key];
            }elseif ($deductions[$key]>0) {
				$de_after .="
					<tr>
						<td>$deductname[$key]</td>
						<td align='right'>".CUR." $deductions[$key]</td>
<!--					<td align='right'>".CUR." $employer_deductions[$key]</td> //-->
					</tr>";
				$de_afteramount = ($de_afteramount  + $deductions[$key] + $employer_deductions[$key]);
				$de_afteramount_emp += $employer_deductions[$key];
			}
        }
    }

    $de_before .= "</table></td></tr>";
    $de_after .= "</table></td></tr>";

	if ($all_beforeamount > 0) {$all_before ="<tr><td colspan='2'>Allowances</td></tr>".$all_before;}
	if ($all_afteramount > 0) {$all_after ="<tr><td colspan='2'>Allowances</td></tr>".$all_after;}
	if ($de_beforeamount > 0) {$de_before ="<tr><td colspan='2'>Deductions</td></tr>".$de_before;}
	if ($de_afteramount > 0) {$de_after ="<tr><td colspan='2'>Deductions</td></tr>".$de_after;}

	$gros_sal = sprint($grossal);

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	core_connect();

	$sql = "SELECT * FROM bankacc WHERE accid = '$accid' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
    # check if link exists
    if(pg_numrows($rslt) <1){
        return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
    }
    $bank = pg_fetch_array($rslt);
    $bankacc = $bank["accnum"];

	$basic_sal = sprint($basic_sal);
	$commission = sprint($commission);
	$overamt = sprint($overamt);
	$paye = sprint($paye);
	$nettpay = sprint($nettpay);

	$date = date("d-m-Y");
	$sdl = sprint($comp_sdl);
	$amount = sprint($gros_sal + $comp_pension + $comp_provident + $comp_medical + $comp_other + $comp_uif + $comp_ret + $sdl);
	$loaninstall=sprint($loaninstall);

	//Original CC
	//$cc = "<script> CostCenter('ct', 'Salaries', '$date', 'Salary Payment for employee,  $myEmp[fnames] $myEmp[sname]', '$amount', '../'); </script>";

	//New CC
	$cc = "CostCenter('ct', 'Salaries', '$date', 'Salary Payment for employee,  $myEmp[fnames] $myEmp[sname]', '$amount', '../'); ";

	$ecost = $amount;

	if($commission > 0) {
		$comDis = "<tr><td>Commission</td><td align='right'>".CUR." $commission</td></tr>";
	} else {
		$comDis = "";
	}


	if($overamt > 0) {
		$oveDis = "<tr><td>Overtime</td><td align='right'>".CUR." $overamt</td></tr>";
	} else {
		$oveDis = "";
	}

	if($loaninstall > 0) {
		$loaDis = "<tr><td>Loan Instalment</td><td align='right'>".CUR." $loaninstall</td></tr>";
	} else {
		$loaDis = "";
	}

	if($basic_sal != $gros_sal) {
		$groDis = "<tr><td>Gross Salary</td><td align='right'>".CUR." $gros_sal</td></tr>";
	} else {
		$groDis = "";
	}

	if($all_travel > 0) {
		$talDis = "<tr><td>Travel Allowance</td><td align='right'>".CUR." $all_travel</td></tr>";
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
	if(pg_numrows($Rslt) <1){
		return "<li class='err'> ERROR : The bank account that you selected doesn't appear to have an account linked to it.</li>";
	}

	$bank = pg_fetch_array($Rslt);

	# date(todays date)
	$date = $ddate;

	$refnum = getrefnum($date);

	# Debit uif acc and credit uif control acc
	if ( $comp_uif > 0 ) {
		writetrans($uifexp, $uifbal , $date, $refnum, $comp_uif, "Company UIF Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if ( $emp_uif > 0 ) {
		db_conn("cubit");
		$Sl = "UPDATE employees SET balance=balance-($emp_uif) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $uifacc, $ydate, $refnum, "UIF" ,  $emp_uif, "d");

		writetrans($salconacc, $uifbal, $date, $refnum, $emp_uif, "Employee UIF Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	# Debit uif sdl and credit sdl control acc
	writetrans($sdlexp, $sdlbal , $date, $refnum, $sdl, "SDL,  $myEmp[fnames] $myEmp[sname].");

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	db_conn('cubit');

	$Sl = "UPDATE employees SET balance=balance+($grossal_nodedall) WHERE empnum = '$empnum'";
	$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

	empledger($empnum, $salacc, $ydate, $refnum,"Gross Salary" , $grossal_nodedall , "c");

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	# Debit salaries acc and credit salaries control acc
    writetrans($salacc, $salconacc, $date, $refnum, $grossal_nodedall, "Gross Salary proccessing for employee,  $myEmp[fnames] $myEmp[sname].");

	if($commission>0) {
		if($con) {
			$commacc = $salacc;
		}

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance+($commission) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $commacc, $ydate, $refnum,"Commission" ,  $commission, "c");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		# Debit commission acc and credit salaries control acc
		writetrans($commacc, $salconacc, $date, $refnum, $commission, "Commission for employee,  $myEmp[fnames] $myEmp[sname].");
	}

	if($paye>0) {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance-($paye) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $payeacc, $ydate, $refnum,"PAYE" , $paye , "d");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		# Debit salaries control acc and credit PAYE control acc
		writetrans($salconacc, $payeacc, $date, $refnum, $paye, "PAYE for employee,  $myEmp[fnames] $myEmp[sname].");
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	db_conn('cubit');

	// fringe benefits
	if ( isset($fringeid) ) {
		foreach ( $fringeid as $i => $id ) {
//			empledger($empnum, $fringeaccs[$i], $ydate, $refnum,"Fringe Benefit, $fringename[$i]" , $fringebens[$i], "d");
//			writetrans($salconacc, $fringeaccs[$i], $date, $refnum, $fringebens[$i], "Fringe Benefit for employee, $myEmp[fnames] $myEmp[sname].");
		}
	}

	if ( $fringe_medical > 0 ) {
//		empledger($empnum, $fringe_medexp, $ydate, $refnum,"Medical Fringe Benefit" , $fringe_medical, "d");
//		writetrans($salconacc, $fringe_medexp, $date, $refnum, $fringe_medical, "Fringe Benefit for employee, $myEmp[fnames] $myEmp[sname].");
	}

	if ( $fringe_car1 > 0 ) {
//		empledger($empnum, $fringe_carexp, $ydate, $refnum,"Motor Vehicle 1 Fringe Benefit" , $fringe_car1, "d");
//		writetrans($salconacc, $fringe_carexp, $date, $refnum, $fringe_car1, "Car Fringe Benefit for employee, $myEmp[fnames] $myEmp[sname].");
	}

	if ( $fringe_car2 > 0 ) {
//		empledger($empnum, $fringe_carexp, $ydate, $refnum,"Motor Vehicle 2 Fringe Benefit" , $fringe_car2, "d");
//		writetrans($salconacc, $fringe_carexp, $date, $refnum, $fringe_car2, "Car Fringe Benefit for employee, $myEmp[fnames] $myEmp[sname].");
	}

	if ( $fringe_loan > 0 ) {
//		empledger($empnum, $fringe_loanexp, $ydate, $refnum,"Loan Interest Fringe Benefit" , $fringe_loan, "d");
//		writetrans($salconacc, $fringe_loanexp, $date, $refnum, $fringe_loan, "Loan Interest Benefit for employee, $myEmp[fnames] $myEmp[sname].");
	}

	# Pay allowances accounts
	if(isset($allowid)){
		foreach($allowid as $i => $id){
		# Debit allowances acc and credit salaries control acc
			if($con) {
				$allowaccs[$i]=$salacc;
			}

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance+($allowances[$i]) WHERE empnum = '$empnum'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($empnum, $allowaccs[$i], $ydate, $refnum,"Allowance" , $allowances[$i] , "c");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			writetrans($allowaccs[$i], $salconacc, $date, $refnum, $allowances[$i], "Allowances for employee, $myEmp[fnames] $myEmp[sname].");
		}
	}

	# Pay Deductions accounts
	if(isset($deductid)){
		foreach($deductid as $i => $id){
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance-($deductions[$i]) WHERE empnum = '$empnum'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($empnum, $dedaccs[$i], $ydate, $refnum,"Deduction" , $deductions[$i], "d");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			# Debit salaries control acc and credit  acc
			// salcon acc - ded balance acc
			writetrans($salconacc, $dedaccs[$i], $date, $refnum, $deductions[$i], "Deductions for employee, $myEmp[fnames] $myEmp[sname].");
			
			db_conn("cubit");
			$sql = "SELECT * FROM salded WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Error reading deduction information.");
			
			$dedinfo = pg_fetch_array($rslt);
/*
			if ( $employer_deductions[$i] > 0 && $dedinfo["creditor"] != "In House" ) {
				// ded exp acc - ded balance acc
				writetrans($dedaccs[$i], $bal_dedaccs[$i], $date, $refnum, $employer_deductions[$i], "Company Contribution to Deductions for employee, $myEmp[fnames] $myEmp[sname].");
			}*/
		}
	}

	if($comp_pension > 0) {
		writetrans($pax,$pa , $date, $refnum, $comp_pension, "Company Pension Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if($emp_pension > 0) {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance-($emp_pension) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $pa, $ydate, $refnum,"Pension Contribution" , $emp_pension, "d");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		writetrans($salconacc,$pa , $date, $refnum, $emp_pension, "Pension Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if($comp_medical > 0 ) {
		writetrans($max,$ma , $date, $refnum, $comp_medical, "Company Medical Aid Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if($emp_medical > 0) {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance-($emp_medical) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $ma, $ydate, $refnum,"Medical Aid Contribution" , $emp_medical, "d");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		writetrans($salconacc,$ma , $date, $refnum, $emp_medical, "Employee Medical Aid Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if($comp_provident > 0) {
		writetrans($providente, $provident, $date, $refnum, $comp_provident, "Company Provident Fund Contribution, $myEmp[fnames] $myEmp[sname].");
	}

	if($emp_provident > 0) {

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance-($emp_provident) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $provident, $ydate, $refnum,"Provident Fund Contribution" , $emp_provident, "d");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		writetrans($salconacc,$provident,$date,$refnum,$emp_provident, "Provident Fund Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if(false && $comp_other > 0) {
		writetrans($dedgenerale, $dedgeneral, $date, $refnum, $comp_other, "Company Contribution to Other Deductions, $myEmp[fnames] $myEmp[sname].");
	}

	if(false && $emp_other > 0) {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance-($emp_other) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $dedgeneral, $ydate, $refnum,"Other Deductions Contribution" , $emp_other, "d");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		writetrans($salconacc,$dedgeneral, $date, $refnum, $emp_other, "Other Deductions Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if($emp_ret > 0) {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance-($emp_ret) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $retire, $ydate, $refnum,"Retirement Annuity Contribution" , $emp_ret, "d");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		writetrans($salconacc, $retire, $date, $refnum, $emp_ret, "Employee Retirement Annuity Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	if($comp_ret > 0) {
		writetrans($retiree, $retire, $date, $refnum, $comp_ret, "Company Retirement Annuity Contribution,  $myEmp[fnames] $myEmp[sname].");
	}

	db_conn('cubit');
	$mons ="$mon;";

	$due = sprint($nettpay-$paidamount);//, balance=balance+'$due

	$sql = "UPDATE employees SET lastpay = '$mons',
				loanamt = (loanamt - cast(float '$loaninstall' as numeric)),
				loanfringe = (loanfringe - cast(float '$fringe_loan' as numeric))
			WHERE empnum = '$empnum' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to get employee details.");
	
	// check if loan is 0, then unmark loan as active, and store in archive
	$sql = "SELECT loanid FROM employees WHERE loanamt=0 AND empnum='$empnum' AND gotloan='t'::bool";
	$rslt = db_exec($sql) or errDie("Error reading employee details for loan.");
	
	if ( pg_num_rows($rslt) > 0 ) {
		$loanid = pg_fetch_result($rslt, 0, 0);
		
		$sql = "UPDATE employees SET gotloan='f'::bool, loaninstall='0'
				WHERE empnum='$empnum'";
		$rslt = db_exec($sql) or errDie("Unable to update employee loan status.");
		
		$sql = "UPDATE emp_loanarchive SET donedata=CURRENT_DATE WHERE id='$loanid'";
		$rslt = db_exec($sql) or errDie("Unable to archive loan.");
		
		$sql = "SELECT loanint_unpaid FROM employees WHERE empnum='$empnum'";
		$rslt = db_exec($sql) or errDie("Error reading loan interest for installment.");
		
		$loanint = sprint(pg_fetch_result($rslt, 0, 0));
	} else if ( $loaninstall > 0 ) {
		$sql = "SELECT loanamt_tot, loanint_amt FROM employees WHERE empnum='$empnum'";
		$rslt = db_exec($sql) or errDie("Error reading loan interest for installment.");
		
		$loan_tot = pg_fetch_result($rslt, 0, 0);
		$loan_totint = pg_fetch_result($rslt, 0, 1);
		
		$loanint = sprint(($loaninstall / $loan_tot) * $loan_totint);
	} else {
		$loanint = 0;
	}
	
	$sql = "UPDATE employees SET loanint_unpaid = (loanint_unpaid - cast(float '$loanint' as numeric))
			WHERE empnum = '$empnum' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update employee interest.");	
	
	if($loaninstall > 0 && !empty($loanexp))
	{
		$loaninstall += 0;

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance-($loaninstall) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $loanexp, $ydate, $refnum,"Loan Instalment" , $loaninstall, "d");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		# Debit salaries control acc and credit loan control acc
		writetrans($salconacc, $loanexp, $date, $refnum, $loaninstall - $loanint, "Loan Installment for employee,  $myEmp[fnames] $myEmp[sname].");
		writetrans($salconacc, $intrec, $date, $refnum, $loanint, "Loan Interest for employee,  $myEmp[fnames] $myEmp[sname].");
	}

	$loaninstall = $loaninstall + 0;
	$totded = ($de_beforeamount + $de_afteramount+$emp_pension+$emp_medical+$emp_provident+$emp_ret+$emp_other);
	$totded_employer = ($de_beforeamount_emp + $de_afteramount_emp+$comp_pension+$comp_medical+$comp_provident+$comp_ret+$comp_other);
	$totall = ($all_beforeamount + $all_afteramount);

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
			<tr><td>UIF</td><td align='right'>".CUR." $emp_uif</td></tr>
			<tr><td>PAYE</td><td align='right'>".CUR." $paye</td></tr>
			$loaDis
			$all_after
			$de_after
			<tr><td><b>Nett Pay</b></td><td align='right'><b>".CUR." $nettpay</b></td></tr>
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
			<tr><td>UIF</td><td align='right'>".CUR." $emp_uif</td></tr>
			<tr><td>PAYE</td><td align='right'>".CUR." $paye</td></tr>
			$loaDis
			$all_after
			$de_after
			<tr><td><b>Nett Pay</b></td><td align='right'><b>".CUR." $nettpay</b></td></tr>
		</form>
		</table>
		</center>";
        
	$OUTPUT = $parkage;

	$save = base64_encode($parkagesave);

	$Date =$ydate;

	$np=$nettpay;

	if(isset($rbsa)) {
		$np=sprint($np- array_sum($rbsa));
	}

		db_conn("cubit");
       	$Sl = "
       		INSERT INTO salpaid (
       			empnum, month, bankid, salary, comm, uifperc, uif, payeperc, paye, totded, 
       			totded_employer, totallow, loanins, div, display, saldate, week
       		) VALUES (
       			'$empnum', '$mon', '$accid', '$np', '$commission', '0', '$emp_uif', '0', '$paye', '$totded', 
       			'$totded_employer', '$totall', '$loaninstall', '".USER_DIV."','$save','$Date','$week'
       		)";
	$Ry = db_exec($Sl) or errDie("Unable to insert record.");

	$id = pglib_lastid ("salpaid", "id");

    $year = $year;

	$payslip_id=$id;

	db_conn('cubit');

	$Sl = "SELECT * FROM rbs ORDER BY name";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$i=0;

	if(pg_num_rows($Ri) > 0) {
		while($td = pg_fetch_array($Ri)) {
			if(!isset($rbsa[$td['id']]) || $rbsa[$td['id']] < 1) {
				continue;
			}

			db_conn('cubit');

			$rbsa[$td['id']] = sprint($rbsa[$td['id']]);

			//$rt.="<tr bgcolor='$bgcolor'>
			//<td><input type=hidden name='rbs[$td[id]]' value='$td[id]'>$td[name]</td>
			//<td>".CUR." <input type=hidden name='rbsa[$td[id]]' value='".$rbsa[$td['id']]."'>".$rbsa[$td['id']]."</td></tr>";

			$rb = $rbsa[$td['id']];

			$i++;

			$Sl = "
				INSERT INTO emp_inc (
					emp, year, period, date, payslip, type, code, description, qty, rate, amount, ex
				) VALUES (
					'$empnum','$year','$mon', '$Date', '$payslip_id', '$td[id]', '', '$td[name]', '1', '0', '$rb', 'RBS'
				)";
			$Ri = db_exec($Sl) or errDie("unable to insert data.");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			db_conn('cubit');

			$Sl = "UPDATE employees SET balance=balance+($rb) WHERE empnum = '$empnum'";
			$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

			empledger($empnum, $td['account'], $ydate, $refnum,"Reimbursement" , $rb, "c");

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			writetrans($td['account'], $salconacc, $date, $refnum, $rb, "Reimbursement for employee, $myEmp[fnames] $myEmp[sname].");
		}
	}

	if($myEmp['paytype'] == "Cash") {
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance-($paidamount) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $cash_account, $ydate, $refnum,"Payment(Cash)" ,  $paidamount, "d");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		# Debit salaries control acc and credit Bank acc
		writetrans($salconacc, $cash_account, $date, $refnum, $paidamount, "Salary Payment(Cash) for employee,  $myEmp[fnames] $myEmp[sname].");

	} elseif($myEmp['paytype'] == "Ledger Account") {

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance-($paidamount) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $account, $ydate, $refnum,"Payment(Ledger Account)" ,  $paidamount, "d");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		# Debit salaries control acc and credit Bank acc
		writetrans($salconacc, $account, $date, $refnum, $paidamount, "Salary Payment(Ledger Account) for employee,  $myEmp[fnames] $myEmp[sname].");

	} else {

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		db_conn('cubit');

		$Sl = "UPDATE employees SET balance=balance-($paidamount) WHERE empnum = '$empnum'";
		$Rp = db_exec($Sl) or errDie("Unable to get employee details.");

		empledger($empnum, $bankacc, $ydate, $refnum,"Payment(Bank)" ,  $paidamount, "d");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		# Debit salaries control acc and credit Bank acc
		writetrans($salconacc, $bankacc, $date, $refnum, $paidamount, "Salary Payment for employee(Bank),  $myEmp[fnames] $myEmp[sname].");

		# issue bank record
		banktrans($accid, "withdrawal", $date, "$myEmp[fnames] $myEmp[sname]", "Salary Payment for employee,  $myEmp[fnames] $myEmp[sname]", 0, $paidamount, $salconacc,$myEmp['empnum']);
	}

	db_conn('cubit');

	/*
	writetrans($uifexp,$uifbal , $date, $refnum, $uif, "Company UIF Contribution,  $myEmp[fnames] $myEmp[sname].");
*/
	# Debit uif sdl and credit sdl control acc
//	writetrans($sdlexp,$sdlbal , $date, $refnum, $sdl, "SDL,  $myEmp[fnames] $myEmp[sname].");

	db_conn("cubit");
	if( $comp_uif > 0 ) {
		$Sl = "INSERT INTO emp_com(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$empnum','$year','$mon','$Date','$id','UIFC','','UIF','1','0','$comp_uif')";
		$Ri=db_exec($Sl) or errDie("unable to insert data1.");
	}

	if ( $emp_uif > 0 ) {
		$Sl="INSERT INTO emp_ded(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','UIFE','','UIF','1','0','$emp_uif')";
		$Ri=db_exec($Sl) or errDie("unable to insert data3.");
	}

	if($sdl>0) {
		$Sl="INSERT INTO emp_com(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','SDL','','SDL','1','0','$sdl')";
		$Ri=db_exec($Sl) or errDie("unable to insert data2.");
	}

	if($paye>0) {
		$Sl="INSERT INTO emp_ded(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','PAYE','','PAYE','1','0','$paye')";
		$Ri=db_exec($Sl) or errDie("unable to insert data3.");
	}

	if($basic_sal>0) {
		$Sl="INSERT INTO emp_inc(emp,year,period,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
		('$empnum','$year','$mon','$Date','$id','INC','','Basic Salary','','1','0','$basic_sal','')";
		$Ri=db_exec($Sl) or errDie("unable to insert data4.");
	}

	if ( $fringe_tot > 0 ) {
		$Sl="INSERT INTO emp_inc(emp,year,period,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
		('$empnum','$year','$mon','$Date','$id','INC','','Fringe Benefits Total','','1','0','$fringe_tot','')";
		$Ri=db_exec($Sl) or errDie("unable to insert data4.");
	}
	
	if ( $myEmp["loanpayslip"] > 0 ) {
		$Sl="INSERT INTO emp_inc(emp,year,period,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
		('$empnum','$year','$mon','$Date','$id','LOAN','','Employee Loan','','1','0','$myEmp[loanpayslip]','')";
		$Ri=db_exec($Sl) or errDie("unable to insert loan data for employee income on payslip.");
		
		$sql = "UPDATE employees SET loanpayslip='0' WHERE empnum='$empnum'";
		$rslt = db_exec($sql) or errDie("Error updating loan information for payslip.");
	}

        if($bonus>0 && $myEmp["payprd"] != "f" && $myEmp["payprd"] != "w") {
		$Sl="INSERT INTO emp_inc(emp,year,period,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
		('$empnum','$year','$mon','$Date','$id','INCB','','Bonus','','1','0','$bonus','')";
		$Ri=db_exec($Sl) or errDie("unable to insert data5.");
	} else if ( $bonus > 0 ) {
		$Sl="INSERT INTO emp_inc(emp,year,period,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
		('$empnum','$year','$mon','$Date','$id','INCB','','Special Bonus/Additional Salary','','1','0','$bonus','')";
		$Ri=db_exec($Sl) or errDie("unable to insert data5.");
	}

	if($annual>0) {
		$Sl="INSERT INTO emp_inc(emp,year,period,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
		('$empnum','$year','$mon','$Date','$id','INCAB','','Annual Bonus','','1','0','$annual','')";
		$Ri=db_exec($Sl) or errDie("unable to insert data5.");
	}

	if($commission>0) {
		$Sl="INSERT INTO emp_inc(emp,year,period,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
		('$empnum','$year','$mon','$Date','$id','INCC','','Commission','','1','0','$commission','')";
		$Ri=db_exec($Sl) or errDie("unable to insert data6.");
	}

	if($all_travel>0) {
		$Sl="INSERT INTO emp_inc(emp,year,period,date,payslip,type,code,description,pension,qty,rate,amount,ex) VALUES
		('$empnum','$year','$mon','$Date','$id','INCT','','Travel Allowance','','1','0','$all_travel','')";
		$Ri=db_exec($Sl) or errDie("unable to insert data7.");
	}

        if($loaninstall>0) {
		$Sl="INSERT INTO emp_ded(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','DEDL','','Loan Repayment','1','0','$loaninstall')";
		$Ri=db_exec($Sl) or errDie("unable to insert data8.");
	}

	if ( $comp_pension > 0 ) {
		$Sl="INSERT INTO emp_com(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$empnum','$year','$mon','$Date','$id','COMP','','Pension','1','0','$comp_pension')";
		$Ri=db_exec($Sl) or errDie("unable to insert data9.");
	}

	if ( $emp_pension > 0 ) {
		$Sl="INSERT INTO emp_ded(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
			('$empnum','$year','$mon','$Date','$id','DEDP','','Pension','1','0','$emp_pension')";
		$Ri=db_exec($Sl) or errDie("unable to insert data10.");
	}

	if($comp_ret>0) {
		$Sl="INSERT INTO emp_com(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','COMR','','Retirement Annuity Fund','1','0','$comp_ret')";
		$Ri=db_exec($Sl) or errDie("unable to insert data9.");
	}

	if($emp_ret>0) {
        $Sl="INSERT INTO emp_ded(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','DEDR','','Retirement Annuity Fund','1','0','$emp_ret')";
		$Ri=db_exec($Sl) or errDie("unable to insert data10.");
	}

	if ( $myEmp["fringe_car1_contrib"] > 0 ) {
        $Sl="INSERT INTO emp_ded(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','DEDR','','Motorcar 1 Contribution for Use','1','0','$myEmp[fringe_car1_contrib]')";
		$Ri=db_exec($Sl) or errDie("unable to insert data10.");
	}

	if ( $myEmp["fringe_car2_contrib"] > 0 ) {
        $Sl="INSERT INTO emp_ded(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','DEDR','','Motorcar 2 Contribution for Use','1','0','$myEmp[fringe_car2_contrib]')";
		$Ri=db_exec($Sl) or errDie("unable to insert data10.");
	}

	if ($comp_medical>0) {
		$Sl="INSERT INTO emp_com(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','COMM','','Medical Aid','1','0','$comp_medical')";
		$Ri=db_exec($Sl) or errDie("unable to insert data.11");
	}

	if($emp_medical>0) {
		$Sl="INSERT INTO emp_ded(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','DEDM','','Medical Aid','1','0','$emp_medical')";
		$Ri=db_exec($Sl) or errDie("unable to insert data.12");
	}

	if($comp_provident>0) {
		$Sl="INSERT INTO emp_com(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','COMV','','Provident','1','0','$comp_provident')";
		$Ri=db_exec($Sl) or errDie("unable to insert data9.");
	}

	if($emp_provident>0) {
		$Sl="INSERT INTO emp_ded(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','DEDV','','Provident','1','0','$emp_provident')";
		$Ri=db_exec($Sl) or errDie("unable to insert data10.");
	}

	if($comp_other>0) {
		$Sl="INSERT INTO emp_com(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','COMO','','Other Deductions','1','0','$comp_other')";
		$Ri=db_exec($Sl) or errDie("unable to insert data9.");
	}

	if($emp_other>0) {
		$Sl="INSERT INTO emp_ded(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
		('$empnum','$year','$mon','$Date','$id','DEDO','','Other Deductions','1','0','$emp_other')";
		$Ri=db_exec($Sl) or errDie("unable to insert data10.");
	}

	if($overamt>0) {
		$Sl="INSERT INTO emp_inc(emp,year,period,date,payslip,type,code,description,qty,rate,amount,ex) VALUES
		('$empnum','$year','$mon','$Date','$id','INCO','','Over Time','1','0','$overamt','')";
		$Ri=db_exec($Sl) or errDie("unable to insert data.13");
	}

	$payslip_id = $id;

	if(isset($allowid)){
       	$Sl = "SELECT id,allowance FROM allowances";
		$Ri = db_exec($Sl) or errDie("Unable to get allowances.");

		while( $data = pg_fetch_array($Ri) ) {
			$allname[$data['id']] = $data['allowance'];
		}

		foreach( $allowid as $i => $id ) {
			$aname=$allname[$allowid[$i]];
			
			if ( ($allowances[$i]=sprint($allowances[$i])) <= 0 ) continue;

			$Sl="INSERT INTO emp_inc(emp,year,period,date,payslip,type,code,description,qty,rate,amount,ex)
				VALUES ('$empnum','$year','$mon','$Date','$payslip_id','$allowid[$i]','','$aname','1','0','$allowances[$i]','')";
			$Ri=db_exec($Sl) or errDie("unable to insert data.");
		}
	}

	# Pay Deductions accounts
	if ( isset($deductid) ) {
		$Sl="SELECT id,deduction FROM salded";
		$Ri=db_exec($Sl) or errDie("Unabel to get get dat.");

		while($data=pg_fetch_array($Ri)) {
			$dnames[$data['id']]=$data['deduction'];
		}

		foreach($deductid as $i => $id){
			$dname=$dnames[$deductid[$i]];

			# Debit salaries control acc and credit  acc
			if ( ($deductions[$i]=sprint($deductions[$i])) > 0 ) {
				$Sl="INSERT INTO emp_ded(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
					('$empnum','$year','$mon','$Date','$payslip_id','$deductid[$i]','','$dname','1','0','$deductions[$i]')";
				$Ri=db_exec($Sl) or errDie("unable to insert data.");
			}

			if ( ($employer_deductions[$i]=sprint($employer_deductions[$i])) > 0 ) {
				$Sl="INSERT INTO emp_com(emp,year,period,date,payslip,type,code,description,qty,rate,amount) VALUES
					('$empnum','$year','$mon','$Date','$payslip_id','$deductid[$i]','','$dname','1','0','$employer_deductions[$i]')";
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

			$amount = sprint($ecost*$data['amount']/100);

			db_conn(PRD_DB);
			$sql = "INSERT INTO cctran(ccid, trantype, typename, edate, description, amount, username, div)
			VALUES('$cc[ccid]', 'ct', 'Salary', '$Date', 'Salary for employee,  $myEmp[fnames] $myEmp[sname]', '$amount', '".USER_NAME."', '".USER_DIV."')";
			$insRslt = db_exec ($sql) or errDie ("Unable to retrieve insert Cost center amounts into database.");
		}
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	$OUTPUT = "<script>printer('payslip-print.php?id=$id');move('../main.php');</script>";
	require("../template.php");

}




function block_check($acc, $debug = false)
{

	global $block_check_errs, $block_check_accs;

	if ( (! isset($block_check_accs[$acc])) && isb($acc) ) {		
		$block_check_accs[$acc] = 1;
		$sql = "SELECT accname FROM core.accounts WHERE accid='$acc'";
		$rslt = db_exec($sql) or errDie("Error reading account name for blocked account.");
		
		$accname = pg_fetch_result($rslt, 0, 0);
		
		$block_check_errs .= "<li class='err'>$accname is a blocked account. Please use the appropriate feature to
					change the usage of this account before you continue with processing salaries.</li>";
		return false;
	}
	return true;

}



function finish_block_check()
{

	global $block_check_errs;
	
	if ( empty($block_check_errs) ) return;
	
	$OUTPUT = "<h3>Process Employee Salary</h3>$block_check_errs";
	require("../template.php");

}

function banktrans($bankacc, $trantype, $date, $name, $details, $cheqnum, $amount, $accinv,$id)
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

	# date format
	$date = explode("-", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];

	# record the payment record
	db_connect();

	$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, banked, accinv, div,fcid) VALUES ('$bankacc', '$trantype', '$date', '$name', '$details', '$cheqnum', '$amount', 'no', '$accinv', '".USER_DIV."','$id')";
	$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

}




?>