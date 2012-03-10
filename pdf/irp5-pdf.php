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
require ("../pdf-settings.php");
require("../salwages/emp-functions.php");
require_lib("time");

//require ("../salwages/emp-functions.php");

//$OUTPUT = "<li class='err'>IRP 5 certificate functionality and IT3 is disabled in this version of Cubit. <br> &nbsp;&nbsp;Please update to version 2.90 in October 2007.</li>";
//
//require ("../template.php");
//die;

// Merge get vars and post vars
foreach ($HTTP_GET_VARS as $key=>$value) {
	$HTTP_POST_VARS[$key] = $value;
}

// Decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		default:
		case "slct":
			$OUTPUT = slct();
			break;
		case "update":
			$OUTPUT = update($HTTP_POST_VARS);
			break;
	}
} else if (isset($_GET["empnum"])) {
	$OUTPUT = slct();
} else {
	$OUTPUT = slctemp();
}

require ("../template.php");




function slctemp()
{

	extract($_GET);

	db_connect ();

	$sql = "SELECT enum as empnum,empnum as e, sname, fnames FROM employees WHERE div = '".USER_DIV."' ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "No employees found in database.<p>"
			.mkQuickLinks(
				ql("../admin-employee-add.php", "Add Employee"),
				ql("../admin-employee-view.php", "View Employees")
			);
	}
	$employees = "<select size='1' name='empnum'>\n";
	while ($myEmp = pg_fetch_array ($empRslt)) {
		$employees .= "<option value='$myEmp[e]'>$myEmp[sname], $myEmp[fnames] ($myEmp[empnum])</option>\n";
	}
	$employees .= "</select>";

    //layout
	$slct = "
		<h3>Select Employee for IRP5<h3>
		<form action='".SELF."' method='post' name='form'>
			<input type='hidden' name='key' value='slct' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='5'>Select Employee</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$employees
				<td><input type='submit' value='Configure IRP5 for Printing' /></td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $slct;

}



function slct($errors="")
{

	global $HTTP_POST_VARS;
	extract ($HTTP_POST_VARS);

	$fields["fdate_year"] = date("Y")-1;
	$fields["fdate_month"] = "03";
	$fields["fdate_day"] = "01";
	$fields["tdate_year"] = date("Y");
	$fields["tdate_month"] = "02";
	$fields["tdate_day"] = getDaysInMonth($fields["tdate_month"], $fields["tdate_year"]);

	$fields["irp5_number"] = "";
	$fields["directive_number"] = "";
	$fields["over_deduction"] = "";
	$fields["pay_periods"] = "";
	$fields["pay_periods_worked"] = "";

	foreach ($fields as $var_name=>$value) {
		if (!isset($$var_name)) {
			$$var_name = $value;
		}
	}

	require ("../irp5-codes.php");
	// Income sources dropdown
//	$nincome_codes_sel = "
//			<select name='nincome_code' style='width: 180px'>
//				<option value='0'>Please select</option>";
//	foreach ($income_codes as $category=>$value) {
//		foreach ($value as $code=>$description) {
//			$nincome_codes_sel .= "<option value='$code'>$code - $description</option>";
//		}
//	}
//	$nincome_codes_sel .= "</select>";
//
//	$income_sources_out = "
//	<tr bgcolor='".bgcolorg()."'>
//		<td>$nincome_codes_sel</td>
//		<td align='center'><input type='text' name='nincome_description' size='100%'></td>
//		<td align='center'>
//			<select name='nincome_rfind'>
//				<option value='N'>No</option>
//				<option value='Y'>Yes</option>
//			</select>
//		</td>
//		<td align='center'><input type='text' name='nincome_amount' size='10%'></td>
//		<td>&nbsp</td>
//	</tr>";

	// Retrieve the saved income sources from Cubit
//	db_conn("cubit");
//	$sql = "SELECT * FROM emp_income_sources WHERE empnum='$empnum' ORDER BY id DESC";
//	$rslt = db_exec($sql) or errDie("Unable to retrieve income sources from Cubit.");
//
//	// Should we add the update button
//	if (pg_num_rows($rslt) < 20) {
//		$update_out = "<input type='submit' value='Update'>";
//	} else {
//		$update_out = "";
//	}

//	$i = 0;
//	while ($income_data = pg_fetch_array($rslt)) {
//		$bgcolor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
//
//		// Income sources dropdown
//		$income_codes_sel = "
//				<select name='income_code[$income_data[id]]' style='width: 180px'>
//					<option value='0'>Please select</option>";
//		foreach ($income_codes as $category => $value) {
//			foreach ($value as $code=>$description) {
//				if ($code == $income_data["code"]) {
//					$selected = "selected";
//				} else {
//					$selected = "";
//				}
//				$income_codes_sel .= "<option value='$code' $selected>$code - $description</option>";
//			}
//		}
// 		$income_codes_sel .= "</select>";
//
//		// RF IND dropdown
//		$rf_ind_vals = array ("N"=>"No", "Y"=>"Yes");
//
//		$rf_ind_sel = "<select name='income_rfind[$income_data[id]]'";
//		foreach ($rf_ind_vals as $key=>$value) {
//			if ($key == $income_data["rf_ind"]) {
//				$selected = "selected";
//			} else {
//				$selected = "";
//			}
//			$rf_ind_sel .= "<option value='$key' $selected>$value</option>";
//		}
//		$rf_ind_sel .= "</select>";
//
//		$income_sources_out .= "
//		<tr bgcolor='$bgcolor'>
//			<td width='20%'>$income_codes_sel</td>
//			<td align='center'><input type='text' name='income_description[$income_data[id]]' size='100%' value='$income_data[description]'></td>
//			<td align='center'>$rf_ind_sel</td>
//			<td align='center'><input type='text' name='income_amount[$income_data[id]]' size='10%' value='$income_data[amount]'></td>
//			<td><input type='checkbox' name='income_rem[$income_data[id]]' size='10%'></td>
//		</tr>";
//	}

	// Deductions
//	$ndeductions_sel = "
//			<select name='ndeduction_code' style='width: 180px'>
//				<option value='0'>Please select</option>";
//	foreach ($deduction_codes as $category=>$value) {
//		foreach ($value as $code=>$description) {
//			$ndeductions_sel .= "<option value='$code'>$code - $description</option>";
//		}
//	}
//	$ndeductions_sel .= "</select>";
//
//	$deductions_out = "
//			<tr bgcolor='".bgcolorg()."'>
//				<td>$ndeductions_sel</td>
//				<td align='center'><input type='text' name='ndeduction_description' size='100%'></td>
//				<td align='center'><input type='text' name='ndeduction_clearance_no' size='10%'></td>
//				<td align='center'><input type='text' name='ndeduction_amount' size='10%'></td>
//				<td>&nbsp</td>
//			</tr>";

//	db_conn("cubit");
//	$sql = "SELECT * FROM emp_deductions WHERE empnum='$empnum'";
//	$rslt = db_exec($sql) or errDie("Unable to retrieve deductions from Cubit.");
//
//	$i = 0;
//	while ($ded_data = pg_fetch_array($rslt)) {
//		$i++;
//		$bgcolor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
//
//		$deductions_sel = "
//				<select name='deduction_code[$ded_data[id]]' style='width: 180px'>
//					<options value='0'>Please select</option";
//		foreach ($deduction_codes as $category=>$value) {
//			foreach ($value as $code=>$description) {
//				if ($ded_data["code"] == $code) {
//					$selected = "selected";
//				} else {
//					$selected = "";
//				}
//				$deductions_sel .= "<option value='$code' $selected>$code - $description</option>";
//			}
//		}
//		$deductions_sel .= "</select>";
//
//		$deductions_out .= "
//		<tr bgcolor='$bgcolor'>
//			<td>$deductions_sel</td>
//			<td align='center'><input type='text' name='deduction_description[$ded_data[id]]' value='$ded_data[description]' size='100%'></td>
//			<td align='center'><input type='text' name='deduction_clearance_no[$ded_data[id]]' value='$ded_data[clearance_no]' size='10%'></td>
//			<td align='center'><input type='text' name='deduction_amount[$ded_data[id]]' value='$ded_data[amount]' size='10%'></td>
//			<td><input type='checkbox' name='deduction_rem[$ded_data[id]]'></td>
//		</tr>";
//	}

	// Retrieve employee details
	db_conn("cubit");

	$sql = "SELECT * FROM employees WHERE empnum='$empnum'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee information from Cubit.");
	$empinfo = pg_fetch_array($rslt);

/*
	<tr bgcolor='".bgcolorg()."'>
		<td><b>From</b></td>
		<td>".mkDateSelect("fdate",$fdate_year,$fdate_month,$fdate_day)."</td>
		<td><b>To</b></td>
		<td>".mkDateSelect("tdate",$tdate_year,$tdate_month,$tdate_day)."</td>
	</tr>
*/

	db_conn ("core");

	$year_drop = "<select name='year_to_process'>";

	#get last closed year ....
	$get_lyear = "SELECT yrdb FROM year WHERE closed = 'y' ORDER BY yrdb DESC LIMIT 1";
	$run_lyear = db_exec($get_lyear) or errDie ("Unable to get closed year information.");
	if (pg_numrows($run_lyear) < 1){
		#no closed years ... fine ...
	}else {
//		$year_drop .= "<option value='".pg_fetch_result($run_lyear,0,0)."'>Previous Closed Year</option>";
		$year_drop .= "<option value='previous'>Previous Closed Year</option>";
	}

	#get current year
	$get_cyear = "SELECT yrdb FROM year WHERE closed = 'n' ORDER BY yrdb ASC LIMIT 1";
	$run_cyear = db_exec($get_cyear) or errDie ("Unable to get current year information.");
	if (pg_numrows($run_cyear) < 1){
		#hmm ... somethings wrong ... no open years found ??
	}else {
//		$year_drop .= "<option value='".pg_fetch_result($run_cyear,0,0)."'>Current Year</option>";
		$year_drop .= "<option value='active'>Current Year</option>";
	}
	
	$year_drop .= "</select>";


/*

				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<td colspan='5'>$errors</td>
					</tr>
					<tr>
						<th colspan='5'>Income Sources</th>
					</tr>
					<tr>
						<th align='left' width='10%'>Code</th>
						<th align='left' width='70%'>Description</th>
						<th align='left' width='10%'>RF IND</th>
						<th align='left' width='10%'>Amount</th>
						<th>Rem</th>
					</tr>
					$income_sources_out
					<tr>
						<th colspan='5'>Deductions</th>
					</tr>
					<tr>
						<th align='left' width='10%'>Code</th>
						<th align='left' width='70%'>Description</th>
						<th align='left' width='10%'>Clearance No</th>
						<th align='left' width='10%'>Amount</th>
						<th>Rem</th>
					</tr>
					$deductions_out
				</table>

*/


	$OUTPUT = "
		<center>
		<h3>IRP 5 for $empinfo[fnames] $empinfo[sname]</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='update'>
			<input type='hidden' name='empnum' value='$empnum'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='5'>Select Period</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$year_drop</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Values</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>IRP 5 Number</td>
				<td><input type='text' name='irp5_number' value='$irp5_number'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Directive Number</td>
				<td><input type='text' name='directive_number' value='$directive_number'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Voluntary over-deduction</td>
				<td><input type='text' name='over_deduction' value='$over_deduction'></td>
			</tr>
			<tr>
				<th colspan='2'>Note</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='2'>
					Cubit will automatically add all income sources	and<br>
					deductions processed by the system. For any additional<br>
					entries, please complete the forms below.
				</td>
			</tr>
		</table>
		<p>
		<p>
		<input type='submit' name='display' value='Done &raquo'>
		</form>
		</center>";
	return $OUTPUT;
// 			$update_out

}




function update($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	$nincome_code = "";
	$nincome_description = "";
	$nincome_rfind = "";
	$nincome_amount = "";

	require_lib("validate");
	$v = new validate;

	$v->isOk($nincome_code, "string", 0, 5, "Invalid income code specified.");
	$v->isOk($nincome_description, "string", 0, 255, "Invalid income description.");
	$v->isOk($nincome_rfind, "string", 0, 60, "Invalid RF IND.");
	$v->isOk($nincome_amount, "string", 0, 9, "Invalid income amount.");
	$v->isOk($directive_number, "num", 0, 9, "Invalid directive number.");
	$v->isOk($over_deduction, "float", 0, 20, "Invalid over deduction.");

	if (isset($income_code)) {
		foreach ($income_code as $id=>$value) {
			$v->isOk($income_code[$id], "string", 0, 5, "Invalid income code specified.");
			$v->isOk($income_description[$id], "string", 0, 255, "Invalid income description.");
			$v->isOk($income_rfind[$id], "string", 0, 60, "Invalid RF IND.");
			$v->isOk($income_amount[$id], "string", 0, 9, "Invalid income amount.");
		}
	}

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return slct($confirm);
	}

	// New income sources
	if ($nincome_code != 0 || !empty($nincome_description) || $nincome_rfind != "N" || !empty($nincome_amount)) {
		db_conn("cubit");
		$sql = "
			INSERT INTO emp_income_sources (
				empnum, code, description, rf_ind, 
				amount
			) VALUES (
				'$empnum', '$nincome_code', '$nincome_description', '$nincome_rfind', 
				'$nincome_amount'
			)";
		$rslt = db_exec($sql) or errDie("Unable to save income sources to Cubit.");
	}

	// Update old income sources
	if (isset($income_code)) {
		foreach ($income_code as $id => $value) {
			db_conn("cubit");
			$sql = "UPDATE emp_income_sources SET code='$income_code[$id]', description='$income_description[$id]', rf_ind='$income_rfind[$id]', amount='$income_amount[$id]' WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to update income sources to Cubit.");
		}
	}

	// Anything to remove
	if (isset($income_rem)) {
		foreach ($income_rem as $id => $value) {
			db_conn("cubit");
			$sql = "DELETE FROM emp_income_sources WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to remove selected items from Cubit.");
		}
	}

	// New deductions
	if (!empty($ndeduction_description) || !empty($ndeduction_clearance_no) || !empty($ndeduction_amount)) {
		db_conn("cubit");
		$sql = "
			INSERT INTO emp_deductions (
				empnum, code, description, clearance_no, amount
			) VALUES (
				'$empnum', '$ndeduction_code', '$ndeduction_description', '$ndeduction_clearance_no', '$ndeduction_amount'
			)";
		$rslt = db_exec($sql) or errDie("Unable to save deductions to Cubit.");
	}

	// Update old deductions
	if (isset($deduction_code)) {
		foreach ($deduction_code as $id => $value) {
			db_conn("cubit");
			$sql = "UPDATE emp_deductions SET code='$deduction_code[$id]', description='$deduction_description[$id]', clearance_no='$deduction_clearance_no[$id]', amount='$deduction_amount[$id]' WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to update income sources to Cubit.");
		}
	}

	// Anything to remove
	if (isset($deduction_rem)) {
		foreach ($deduction_rem as $id => $value) {
			db_conn("cubit");
			$sql = "DELETE FROM emp_deductions WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to remove selected items from Cubit.");
		}
	}

 	// Where to go from here?
	if (isset($display)) {
		return display($HTTP_POST_VARS);
	} else {
		return slct();
	}

}



function display($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	global $PRDMON;


	#determine the date range based on period selection
	if (isset($year_to_process))
		switch ($year_to_process){
			case "active":
				$fdate_year = getYearOfFinPrd(1);
				$fdate_month = $PRDMON[1];
				$fdate_day = "1";
				$tdate_year = getYearOfFinPrd(12);
				$tdate_month = $PRDMON[12];
				$tdate_day = date ("d",mktime(0,0,0,$PRDMON[12]+1,0,$tdate_year));
				break;
			case "previous":
				$fdate_year = getYearOfFinPrd(1)-1;
				$fdate_month = $PRDMON[1];
				$fdate_day = "1";
				$tdate_year = getYearOfFinPrd(12)-1;
				$tdate_month = $PRDMON[12];
				$tdate_day = date ("d",mktime(0,0,0,$PRDMON[12]+1,0,$tdate_year));
				break;
			default:
				$fdate_year = getYearOfFinPrd(1);
				$fdate_month = "03";
				$fdate_day = "01";
				$tdate_year = getYearOfFinPrd(12);
				$tdate_month = "02";
				$tdate_day = date ("d",mktime(0,0,0,3,0,$tdate_year));
		}

	// -----------------------------------------------------------------------
	// Sanity checks
	// -----------------------------------------------------------------------
	require_lib("validate");
	$v = new validate;

	// Does this employee number actually exist
	db_conn("cubit");

	$sql = "SELECT * FROM employees WHERE empnum='".(int)$empnum."' AND div='".USER_DIV."'";
	$empinf_rslt = db_exec($sql) or errDie("Unable to retrieve employee number from Cubit.");

	if (pg_num_rows($empinf_rslt) == 0) {
		$v->addError(0, "Employee number not found in Cubit.");
	}

	$v->isOk($fdate_month, "num", 1, 2, "Invalid from date (month)");
	$v->isOk($fdate_year, "num", 4, 4, "Invalid from date (year)");
	$v->isOk($tdate_month, "num", 1, 2, "Invalid to date (month)");
	$v->isOk($tdate_year, "num", 4, 4, "Invalid to date (year)");

	if ($fdate_month > 12) $v->addError(0, "Invalid from date (month)");
	if ($fdate_year < 1970 || $fdate_year > 2050) $v->addError(0, "Invalid from date (year)");
	if ($tdate_month > 12) $v->addError(0, "Invalid to date (month)");
	if ($tdate_year < 1970 || $tdate_year > 2050) $v->addError(0, "Invalid to date (year)");

	if ($fdate_day > getDaysInMonth((int)$fdate_month, $fdate_year)) {
		$v->addError(0, "Invalid from date (day)");
	}
	if ($tdate_day > getDaysInMonth((int)$tdate_month, $tdate_year)) {
		$v->addError(0, "Invalid to date (day)");
	}

	$from_time = mktime(0, 0, 0, $fdate_day, $fdate_month, $fdate_year);
	$to_time = mktime(0, 0, 0, $tdate_day, $tdate_month, $tdate_year);
	if ($from_time > $to_time) {
		$v->addError(0, "Invalid date range specified.");
	}

	// Return the errors, if any
	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return slct($confirm);
	}



	$from_date = "$fdate_year-$fdate_month-$fdate_day";
	$to_date = "$tdate_year-$tdate_month-$tdate_day";

	$gross_taxable_annual_payments = 0.00;
	$gross_non_taxable_income = 0.00;
	$gross_retirement_funding_income = 0.00;
	$gross_non_retirement_funding_income = 0.00;
	$gross_remuneration = 0.00;

	db_conn("cubit");

	$sql = "SELECT * FROM compinfo";
	$compinfo_rslt = db_exec($sql) or errDie("Unable to retrieve company information from Cubit.");
	$compinfo = pg_fetch_array($compinfo_rslt);

	db_conn("cubit");

	$sql = "SELECT * FROM employees WHERE empnum='$empnum'";
	$empinfo_rslt = db_exec($sql) or errDie("Unable to retrieve employee information from Cubit.");
	$empinfo = pg_fetch_array($empinfo_rslt);

	$header_out = "<b>Employees tax certificate</b>";

	$employer_trading_name_out = array (
		array ("<b>Trading or other name:</b> $compinfo[compname]")
	);
	$employer_irp5_number_out = array (
		array ("<b>IRP 5 number:</b> $irp5_number")
	);
	$employer_reference_number_out = array (
		array ("<b>Reference number:</b> $empinfo[taxref]")
	);
	$employer_tax_year_out = array (
		array ("<b>Tax year:</b> ".($fdate_year+1))
	);
	$employer_diplomatic_indemnity_out = array (
		array ("<b>Diplomatic indemnity:</b> $compinfo[diplomatic_indemnity]")
	);
	$employer_business_address_out = array (
		array ("<b>Employer business address:</b>"),
		array ("$compinfo[addr1]"),
		array ("$compinfo[addr2]"),
		array ("$compinfo[addr3]")
	);

	$employer_postal_code_out = array (
		array ("col1" => "<b>Postal Code:</b>", "col2"=>"$compinfo[addr4]")
	);
	$employer_postal_code_cols = array (
		"col1" => array("width" => 200, "justification" => "right"),
		"col2" => array("width" => 40, "justification" => "right")
	);

	// Extract the employee's birth date from her id number
	$bd_year = 1900 + substr($empinfo["idnum"], 0, 2);
	$bd_month = substr($empinfo["idnum"], 2, 2);
	$bd_day = substr($empinfo["idnum"], 4, 2);

	// Period employed from
	// strip out the dashes from the date
	$prd_employed_frm = explode("-", $empinfo["hiredate"]);
	$prd_employed_frm = implode($prd_employed_frm);

	// Period employed to
	if (empty($empinfo["firedate"])) {
		$prd_employed_to = getFinYear()/*date("Y")*/ . "0228";
	} else {
		$prd_employed_to = explode("-", $firedate);
		$prd_employed_to = implode("", $prd_employed_to);
	}

	$employee_nature_out = array (
		array ("<b>Nature of Person:</b> $empinfo[nature]")
	);
	$employee_surname_out = array (
		array ("<b>Employee surname or trading name:</b> $empinfo[sname]")
	);
	$employee_first_names_out = array (
		array ("<b>First two names:</b> $empinfo[fnames]")
	);

	$fnames = explode(" ", $empinfo["fnames"]);
	$initials = "";
	foreach ($fnames as $name) {
		$initials .= strtoupper($name{0});
	}
	$employee_initials_out = array (
		array ("<b>Initials:</b> $initials")
	);
	$employee_identity_number_out = array (
		array ("<b>Identity number:</b> $empinfo[idnum]")
	);
	$employee_passport_number_out = array (
		array ("<b>Passport number:</b> $empinfo[passport_number]")
	);
	$employee_date_of_birth_out = array (
		array ("<b>Date of birth:</b> $bd_year-$bd_month-$bd_day")
	);
	$employee_cc_number_out = array (
		array ("<b>Company/CC/Trust number:</b> $empinfo[cc_number]")
	);
	$employee_tax_number_out = array (
		array ("<b>Income Tax number:</b> $empinfo[tax_number]")
	);
	$employee_residential_out = array (
		array ("<b>Employees residential address:</b>"),
		array ("$empinfo[res1]"),
		array ("$empinfo[res2]"),
		array ("$empinfo[res3]"),
	);

	$employee_postal_code_out = array (
		array("col1" => "<b>Postal Code:</b>", "col2" => "$empinfo[res4]")
	);
	$employee_postal_code_cols = array (
		"col1" => array("width" => 200, "justification" => "right"),
		"col2" => array("width" => 40, "justification" => "right")
	);

	$employee_number_out = array (
		array("<b>Employee Number:</b> $empinfo[empnum]")
	);

	$tax_prd_employed_frm_out = array (
		array ("<b>Period employed from:</b> $prd_employed_frm")
	);
	$tax_prd_employed_to_out = array (
		array ("<b>Period employed to:</b> $prd_employed_to")
	);
	$tax_over_deduction_out = array (
		array ("<b>Voluntary over-deduction:</b> $over_deduction")
	);

	switch ($empinfo["payprd"]) {
		case "m":
			$pay_periods = 12;
			break;
		case "f":
			$pay_periods = 24;
			break;
		case "w":
			$pay_periods = 48;
			break;
	}
	$tax_prd_in_year_out = array (
		array ("<b>Pay periods in year:</b> $pay_periods")
	);

	if ($year_to_process == "active"){
		$cyear = EMP_YEAR;
	}else {
		$cyear = EMP_YEAR - 1;
	}
	db_conn("cubit");

	$sql = "SELECT * FROM emp_inc WHERE emp='$empinfo[empnum]' AND description='Basic Salary' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve the number of periods worked from Cubit.");

	$pay_periods_worked = pg_num_rows($rslt);
	$tax_prd_worked_out = array (
		array ("<b>Number of periods worked:</b> $pay_periods_worked")
	);
	$tax_fixed_rate_income_out = array (
		array ("<b>Fixed rate income:</b> $empinfo[fixed_rate]")
	);
	$tax_directive_number_out = array (
		array ("<b>Directive number:</b> $directive_number")
	);

	// Income sources --------------------------------------------------------
	$income_sources_out = array();

	$income_taxable_total = 0;
	$income_commission_total = 0;
	$income_bonus_total = 0;
	$income_reimburse_total = 0;
	$income_travelallowance_total = 0;
	$income_subsis_total = 0;
	$income_otherallowance_total = 0;
	$deduction_motorcar_total = 0;
	$deduction_medical_total = 0;
//	$deduction_medicalaid_total = 0;


	db_conn("cubit");

	$sql = "SELECT amount FROM emp_inc WHERE emp='$empnum' AND description='Basic Salary' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee income sources from Cubit.");
	while ($empinc_data = pg_fetch_array($rslt)) {
		 $income_taxable_total += $empinc_data["amount"];
	}
	$income_sources_out[] = array (
		"<b>CODE</b>" => "3601",
		"<b>DESCRIPTION</b>" => "Income Taxable",
		"<b>RF IND</b>" => "N",
		"<b>AMOUNT</b>" => (int)$income_taxable_total
	);

	$sql = "SELECT amount FROM emp_inc WHERE emp='$empnum' AND description='Bonus' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee income sources from Cubit.");
	while ($empinc_data = pg_fetch_array($rslt)) {
		 $income_bonus_total += $empinc_data["amount"];
	}
	$income_sources_out[] = array (
		"<b>CODE</b>" => "3605",
		"<b>DESCRIPTION</b>" => "Bonus",
		"<b>RF IND</b>" => "N",
		"<b>AMOUNT</b>" => (int)$income_bonus_total
	);

	$sql = "SELECT amount FROM emp_inc WHERE emp='$empnum' AND description='Commission' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee income sources from Cubit.");
	while ($empinc_data = pg_fetch_array($rslt)) {
		 $income_commission_total += $empinc_data["amount"];
	}
	$income_sources_out[] = array (
		"<b>CODE</b>" => "3606",
		"<b>DESCRIPTION</b>" => "Commission",
		"<b>RF IND</b>" => "N",
		"<b>AMOUNT</b>" => (int)$income_commission_total
	);

	#handle travel allowances ....
	$sql = "SELECT amount FROM emp_inc WHERE emp='$empnum' AND description = 'Travel Allowance' AND type = 'INCT' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee income sources from Cubit.");
	while ($empinc_data = pg_fetch_array($rslt)) {
		$income_travelallowance_total += $empinc_data['amount'];
	}
	$income_sources_out[] = array (
		"<b>CODE</b>" => "3701",
		"<b>DESCRIPTION</b>" => "Travel Allowance",
		"<b>RF IND</b>" => "",
		"<b>AMOUNT</b>" => (int)$income_travelallowance_total
	);

	#handle reimbursements ....
	$sql = "SELECT amount FROM emp_inc WHERE emp='$empnum' AND description!='Basic Salary' AND ex = 'RBS' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee income sources from Cubit.");
	while ($empinc_data = pg_fetch_array($rslt)) {
		$income_reimburse_total += $empinc_data['amount'];
	}
	$income_sources_out[] = array (
		"<b>CODE</b>" => "3702",
		"<b>DESCRIPTION</b>" => "Reimbursements",
		"<b>RF IND</b>" => "",
		"<b>AMOUNT</b>" => (int)$income_reimburse_total
	);

	#handle subsistance allowances ....
	$sql = "SELECT amount FROM emp_inc WHERE emp='$empnum' AND description != 'Basic Salary' AND ex = 'SUBS' AND type = '2' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee income sources from Cubit.");
	while ($empinc_data = pg_fetch_array($rslt)) {
		$income_subsis_total += $empinc_data['amount'];
	}
	$income_sources_out[] = array (
		"<b>CODE</b>" => "3704",
		"<b>DESCRIPTION</b>" => "Subsistance Allowance",
		"<b>RF IND</b>" => "",
		"<b>AMOUNT</b>" => (int)$income_subsis_total
	);

	#handle other allowances ....
	$sql = "SELECT amount FROM emp_inc WHERE emp='$empnum' AND description != 'Basic Salary' AND description != 'Travel Allowance' AND ex != 'SUBS' AND ex != 'RBS' AND type = '2' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee income sources from Cubit.");
	while ($empinc_data = pg_fetch_array($rslt)) {
		$income_otherallowance_total += $empinc_data['amount'];
	}
	$income_sources_out[] = array (
		"<b>CODE</b>" => "3713",
		"<b>DESCRIPTION</b>" => "Other Allowances",
		"<b>RF IND</b>" => "",
		"<b>AMOUNT</b>" => (int)$income_otherallowance_total
	);

	#handle motorcar DEDUCTIONS in income table ...
	$sql = "SELECT amount FROM emp_ded WHERE emp='$empnum' AND (description = 'Motorcar 1 Contribution for Use' OR description = 'Motorcar 2 Contribution for Use') AND (type = 'DEDA' OR type = 'DEDB') AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee income sources from Cubit.");
	while ($empinc_data = pg_fetch_array($rslt)) {
		$deduction_motorcar_total += $empinc_data['amount'];
	}
	$income_sources_out[] = array (
		"<b>CODE</b>" => "3802",
		"<b>DESCRIPTION</b>" => "Use Of Motor Vehicle",
		"<b>RF IND</b>" => "",
		"<b>AMOUNT</b>" => (int)$deduction_motorcar_total
	);

	#handle medical DEDUCTIONS in income table ...
	//$sql = "SELECT amount FROM emp_com WHERE emp='$empnum' AND description = 'Medical Contribution' AND type = 'DEDM'";
	$sql = "SELECT amount FROM emp_com WHERE emp='$empnum' AND description = 'Medical Contribution' AND type = 'COMM' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee income sources from Cubit.");
	while ($empinc_data = pg_fetch_array($rslt)) {
		$deduction_medical_total += $empinc_data['amount'];
	}
	//"<b>CODE</b>" => "3816",
	$income_sources_out[] = array (
		"<b>CODE</b>" => "3810",
		"<b>DESCRIPTION</b>" => "Medical Contribution",
		"<b>RF IND</b>" => "",
		"<b>AMOUNT</b>" => (int)$deduction_medical_total
	);

	#handle medical aid DEDUCTIONS in income table ...
//	$sql = "SELECT amount FROM emp_ded WHERE emp='$empnum' AND description = 'Medical Contribution' AND type = 'DEDM'";
//	$rslt = db_exec($sql) or errDie("Unable to retrieve employee income sources from Cubit.");
//	while ($empinc_data = pg_fetch_array($rslt)) {
//		$deduction_medicalaid_total += $empinc_data['amount'];
//	}
//	$income_sources_out[] = array (
//		"<b>CODE</b>" => "3802",
//		"<b>DESCRIPTION</b>" => "Medical aid contributions",
//		"<b>RF IND</b>" => "",
//		"<b>AMOUNT</b>" => (int)$deduction_medicalaid_total
//	);


// old
//	$sql = "SELECT * FROM emp_inc WHERE emp='$empnum' AND description!='Basic Salary'";
//	$rslt = db_exec($sql) or errDie("Unable to retrieve employee income sources from Cubit.");
//	while ($empinc_data = pg_fetch_array($rslt)) {
//		$income_sources_out[] = array (
//			"<b>CODE</b>"=>"$empinc_data[code]",
//			"<b>DESCRIPTION</b>"=>"$empinc_data[description]",
//			"<b>RF IND</b>"=>"",
//			"<b>AMOUNT</b>"=>(int)$empinc_data["amount"]
//		);
//	}

	if (!empty($nincome_description) || !empty($nincome_amount) && $nincome_code != 0) {
		$income_sources_out[] = array (
			"<b>CODE</b>" => "$nincome_code",
			"<b>DESCRIPTION</b>" => "$nincome_description",
			"<b>RF IND</b>" => "$nincome_rfind",
			"<b>AMOUNT</b>" => (int)$nincome_amount
		);
	}

	if (isset($income_code)) {
		foreach ($income_code as $id=>$value) {
			if ($income_code[$id] != 0) {
				$income_sources_out[] = array (
					"<b>CODE</b>" => "$income_code[$id]",
					"<b>DESCRIPTION</b>" => "$income_description[$id]",
					"<b>RF IND</b>" => "$income_rfind[$id]",
					"<b>AMOUNT</b>" => (int)$income_amount[$id]
				);
			} else {
				$income_sources_out[] = array (
					"<b>CODE</b>" => "",
					"<b>DESCRIPTION</b>" => "",
					"<b>RF IND</b>" => "",
					"<b>AMOUNT</b>" => ""
				);
			}
		}
	}

	$income_sources_cols = array(
		"<b>CODE</b>" => array("width"=>40),
		"<b>DESCRIPTION</b>" => array("width"=>340),
		"<b>RF IND</b>" => array("width"=>70),
		"<b>AMOUNT</b>" => array("width"=>70)
	);


	// Gross renumeration ----------------------------------------------------

	// Taxable annual payments
	db_conn("cubit");
	$sql = "SELECT * FROM emp_inc WHERE (code='3695' OR code='3601' OR description='Bonus') AND emp = '$empnum' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve taxable annual payments from Cubit.");

	while ($emp_inc_data = pg_fetch_array($rslt)) {
		$gross_taxable_annual_payments += $emp_inc_data["amount"];
	}

	db_conn("cubit");
	$sql = "SELECT * FROM emp_income_sources WHERE (code='3695' OR code='3601') AND empnum = '$empnum'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve taxable annual payments from Cubit.");

	while ($emp_income_sources_data = pg_fetch_array($rslt)) {
		$gross_taxable_annual_payments += $emp_income_sources_data["amount"];
	}

	// Non taxable annual payments
	db_conn("cubit");
	$sql = "SELECT * FROM emp_inc WHERE (code='3602' OR code='3604' OR code='3612' OR code='3703' OR code='3705' OR code='3709' OR code='3714') AND emp = '$empnum' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve non taxable payments from Cubit.");

	while ($emp_inc_data = pg_fetch_array($rslt)) {
		$gross_non_taxable_income += $emp_inc_data["amount"];
	}

	db_conn("cubit");
	$sql = "SELECT * FROM emp_income_sources WHERE (code='3602' OR code='3604' OR code='3612' OR code='3703' OR code='3705' OR code='3709' OR code='3714') AND empnum = '$empnum'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve non taxable payments from Cubit.");

	while ($emp_income_sources_data = pg_fetch_array($rslt)) {
		if ($emp_income_sources_data["rf_ind"] == "Y") {
			$gross_non_taxable_income += $emp_income_sources_data["amount"];
		}
	}

	// Gross retirement funding income
	db_conn("cubit");
	$sql = "SELECT emp_pension, emp_ret FROM employees WHERE empnum='$empnum'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve gross retrirement funding from Cubit.");
	$emp_data = pg_fetch_array($rslt);

	// RF IND
	$rfind_y = 0;
	if (isset($income_rfind)) {
		foreach ($income_rfind as $id=>$value) {
			if ($income_rfind[$id] == "Y") {
				$rfind_y += $income_amount[$id];
			}
		}
	}

//wth emp_pension is a PERCENTAGE, not some amount to add
//	$gross_retirement_funding_income += $emp_data["emp_pension"] + $emp_data["emp_ret"] + $rfind_y;
	$gross_retirement_funding_income += (($gross_taxable_annual_payments / 100) * $emp_data["emp_pension"]) + $emp_data["emp_ret"] + $rfind_y;

	// Gross non retirement funding income
	db_conn("cubit");
	$sql = "SELECT * FROM emp_inc WHERE ((code != '3601' AND code != '3603' AND code != '3604' AND code != '3610' AND code != '3615') OR description = 'Basic Salary') AND emp = '$empnum' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve gross non retirement funding from Cubit.");

	// RF IND
	$rfind_n = 0;
	if (isset($income_rfind)) {
		foreach ($income_rfind as $id=>$value) {
			if ($income_rfind[$id] == "N") {
				$rfind_n += $income_amount[$id];
			}
		}
	}

	while ($emp_inc_data = pg_fetch_array($rslt)) {
		$gross_non_retirement_funding_income += $emp_inc_data["amount"] + $rfind_n;
	}

	db_conn("cubit");
	$sql = "SELECT * FROM emp_income_sources WHERE (code != '3601' AND code != '3603' AND code != '3604' AND code != '3610' AND code!='3615') AND empnum = '$empnum'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve gross non retirement funding from Cubit.");

	while ($emp_income_sources_data = pg_fetch_array($rslt)) {
		if ($emp_income_sources_data["rf_ind"] == "N") {
			$gross_non_retirement_funding_income += $emp_income_sources_data["amount"];
		}
	}

	// Gross Remuneration
	$gross_remuneration = /*$gross_taxable_annual_payments + */$gross_retirement_funding_income + $gross_non_retirement_funding_income;

	$gross_remuneration_out = array (
		array (
			"<b>CODE</b>" => "3695",
			"<b>DESCRIPTION</b>" => "GROSS TAXABLE ANNUAL PAYMENTS",
			"<b>AMOUNT</b>" => (int)$gross_taxable_annual_payments
		),
		array (
			"<b>CODE</b>"=>"3696",
			"<b>DESCRIPTION</b>" => "GROSS NON-TAXABLE INCOME",
			"<b>AMOUNT</b>" => (int)$gross_non_taxable_income
		),
		array (
			"<b>CODE</b>" => "3697",
			"<b>DESCRIPTION</b>" => "GROSS RETIREMENT FUNDING INCOME",
			"<b>AMOUNT</b>" => (int)$gross_retirement_funding_income
		),
		array (
			"<b>CODE</b>" => "3698",
			"<b>DESCRIPTION</b>" => "GROSS NON-RETIREMENT FUNDING INCOME",
			"<b>AMOUNT</b>" => (int)$gross_non_retirement_funding_income
		),
		array (
			"<b>CODE</b>" => "3699",
			"<b>DESCRIPTION</b>" => "GROSS REMUNERATION",
			"<b>AMOUNT</b>" => (int)$gross_remuneration
		)
	);
	$gross_remuneration_cols = array(
		"<b>CODE</b>" => array("width"=>40),
		"<b>DESCRIPTION</b>" => array("width"=>410),
		"<b>AMOUNT</b>" => array("width"=>70)
	);

	// Deductions ------------------------------------------------------------
	$deductions_out = array();
	$deduction_pension_total = 0;
	$deduction_provident_total = 0;
	$deduction_medicalaid_total = 0;
	$deduction_medical_total2 = 0;
	$deduction_retirementann_total = 0;
	$deduction_premiumpol_total = 0;


	db_conn("cubit");

	$sql = "SELECT * FROM emp_deductions WHERE empnum='$empnum'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee deductions from Cubit.");
	while ($ded_data = pg_fetch_array($rslt)) {
		$deductions_out[] = array (
			"<b>CODE</b>" => "$ded_data[code]",
			"<b>DESCRIPTION</b>" => "$ded_data[description]",
			"<b>CLEARANCE NO</b>" => "$ded_data[clearance_no]",
			"<b>AMOUNT</b>" => (int)$ded_data["amount"]
		);
	}

	#handle pension deduction ...
	$sql = "SELECT amount FROM emp_ded WHERE emp='$empnum' AND description = 'Pension' AND type = 'DEDP' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee deductions from Cubit.");
	while ($empded_data = pg_fetch_array($rslt)) {
		$deduction_pension_total += $empded_data['amount'];
	}
	$deductions_out[] = array (
		"<b>CODE</b>" => "4001",
		"<b>DESCRIPTION</b>" => "Current pension fund contributions",
		"<b>CLEARANCE NO</b>" => "",
		"<b>AMOUNT</b>" => (int)$deduction_pension_total
	);

	#handle provident deduction ...
	$sql = "SELECT amount FROM emp_ded WHERE emp='$empnum' AND description = 'Provident' AND type = 'DEDV' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee deductions from Cubit.");
	while ($empded_data = pg_fetch_array($rslt)) {
		$deduction_provident_total += $empded_data['amount'];
	}
	$deductions_out[] = array (
		"<b>CODE</b>" => "4003",
		"<b>DESCRIPTION</b>" => "Current provident fund contributions",
		"<b>CLEARANCE NO</b>" => "",
		"<b>AMOUNT</b>" => (int)$deduction_provident_total
	);

	#handle medical DEDUCTIONS in income table ...
	$sql = "SELECT amount FROM emp_ded WHERE emp='$empnum' AND description = 'Medical Contribution' AND type = 'DEDM' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee income sources from Cubit.");
	while ($empinc_data = pg_fetch_array($rslt)) {
		$deduction_medical_total2 += $empinc_data['amount'];
	}

	$deductions_out[] = array (
		"<b>CODE</b>" => "4005",
		"<b>DESCRIPTION</b>" => "Medical Contribution",
		"<b>RF IND</b>" => "",
		"<b>AMOUNT</b>" => (int)$deduction_medical_total2
	);

	#handle retirement annuity deduction ...
	$sql = "SELECT amount FROM emp_ded WHERE emp='$empnum' AND description = 'Retirement Annuity Fund' AND type = 'DEDR' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee deductions from Cubit.");
	while ($empded_data = pg_fetch_array($rslt)) {
		$deduction_retirementann_total += $empded_data['amount'];
	}
	$deductions_out[] = array (
		"<b>CODE</b>" => "4006",
		"<b>DESCRIPTION</b>" => "Current retirement annuity fund contributions",
		"<b>CLEARANCE NO</b>" => "",
		"<b>AMOUNT</b>" => (int)$deduction_retirementann_total
	);


	$sql = "SELECT * FROM emp_ded WHERE emp='$empnum' AND description!='UIF' AND description!='SDL' AND description!='PAYE' AND description!='Motorcar 1 Contribution for Use' AND description!='Motorcar 2 Contribution for Use' AND description!='Medical Contribution' AND description!='Pension' AND description!='Provident' AND description!='Retirement Annuity Fund' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee deductions from Cubit.");
	while ($empded_data = pg_fetch_array($rslt)) {
		$deduction_premiumpol_total += $empded_data['amount'];
	}
	$deductions_out[] = array (
		"<b>CODE</b>" => "4018",
		"<b>DESCRIPTION</b>" => "Premiums paid on loss of income policies",
		"<b>CLEARANCE NO</b>" => "",
		"<b>AMOUNT</b>" => (int)$deduction_premiumpol_total
	);


//old all encapsulating search ... only first to description checks were orginal ...
//	$sql = "SELECT * FROM emp_ded WHERE emp='$empnum' AND description!='UIF' AND description!='SDL' AND description!='Motorcar 1 Contribution for Use' AND description!='Motorcar 2 Contribution for Use' AND description!='Medical Contribution' AND description!='Pension' AND description!='Provident' AND description!='Retirement Annuity Fund'";
//	$rslt = db_exec($sql) or errDie("Unable to retrieve employee deductions from Cubit.");
//	while ($empded_data = pg_fetch_array($rslt)) {
//		if ($empded_data["type"] != "PAYE") {
//			$deductions_out[] = array (
//				"<b>CODE</b>"=>"$empded_data[code]",
//				"<b>DESCRIPTION</b>"=>"$empded_data[description]",
//				"<b>CLEARANCE NO</b>"=>"",
//				"<b>AMOUNT</b>"=>(int)$empded_data["amount"]
//			);
//		}
//	}

// cant just remove whatever deductions is on system if they havent actually been applied to salary (ie. salary processed)
// this value will come from same place as the rest of the income/deductions ... wth
//	db_conn("cubit");
//	$sql = "SELECT * FROM empdeduct WHERE empnum='$empnum'";
//	$empded_rslt = db_exec($sql) or errDie("Unable to retrieve employee deductions from Cubit.'");
//
//	while ($empded_data = pg_fetch_array($empded_rslt)) {
//		db_conn("cubit");
//		$sql = "SELECT deduction, code FROM salded WHERE id='$empded_data[dedid]' AND deduction!='UIF'";
//		$rslt = db_exec($sql) or errDie("Unable to retrieve deduction information from Cubit.");
//		$ded_data = pg_fetch_array($rslt);
//
//		$deductions_out[] = array (
//			"<b>CODE</b>"=>"$ded_data[code]",
//			"<b>DESCRIPTION</b>"=>"$ded_data[deduction]",
//			"<b>CLEARANCE NO</b>"=>"$empded_data[clearance_no]",
//			"<b>AMOUNT</b>"=>(int)$empded_data["amount"], 2
//		);
//	}

	if (!isset($deductions_out[0])) {
		$deductions_out = array (
			array (
				"<b>CODE</b>" => "",
				"<b>DESCRIPTION</b>" => "",
				"<b>CLEARANCE NO</b>" => "",
				"<b>AMOUNT</b>" => ""
			)
		);
	}

	$deductions_cols = array(
		"<b>CODE</b>" => array("width" => 40),
		"<b>DESCRIPTION</b>" => array("width" => 340),
		"<b>CLEARANCE NO</b>" => array("width" => 70),
		"<b>AMOUNT</b>" => array("width" => 70)
	);

	// Employees Tax deductions-----------------------------------------------

	$tax_amt = 0;

	// Retrieve PAYE amount from Cubit
	db_conn("cubit");
	$sql = "SELECT * FROM emp_ded WHERE type='PAYE' AND emp = '$empnum' AND year = '$cyear'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve PAYE deductions from Cubit");

	$paye_amount = 0;
	while ($empded_data = pg_fetch_array($rslt)) {
		// add 375 to site if salary, -375 if reverse salary
		$tax_amt += 375 * ($empded_data["amount"] > 0 ? 1 : -1);

		$paye_amount += $empded_data["amount"];
	}

	if ($tax_amt > $paye_amount) {
		$tax_amt = $paye_amount;
		$paye_amount = 0;
	} else {
		$paye_amount -= $tax_amt;
	}

	$tax_deductions_amount = $paye_amount + $tax_amt;

	$employees_tax_deductions_out = array (
		array (
  			"<b>CODE</b>" => "4101",
  			"<b>DESCRIPTION</b>" => "STANDARD INCOME TAX ON EMPLOYEES-SITE",
  			"<b>AMOUNT</b>" => sprint($tax_amt, 2)
		),
		array (
			"<b>CODE</b>" => "4102",
			"<b>DESCRIPTION</b>" => "PAY AS YOU EARN - PAYE",
			"<b>AMOUNT</b>" => sprint($paye_amount, 2)
		),
		array (
			"<b>CODE</b>" => "4103",
			"<b>DESCRIPTION</b>" => "TOTAL EMPLOYEES TAX",
			"<b>AMOUNT</b>" => sprint($tax_deductions_amount, 2)
		)
	);
	$employees_tax_deductions_cols = array(
		"<b>CODE</b>" => array("width"=>40),
		"<b>DESCRIPTION</b>" => array("width"=>410),
		"<b>AMOUNT</b>" => array("width"=>70)
	);

	// -----------------------------------------------------------------------
	// Do the actual rendering of the pdf
	// -----------------------------------------------------------------------
	$pdf = &new Cezpdf;

	global $set_mainFont;
	$pdf->selectFont($set_mainFont);

	$pdf->addInfo("Title", "IRP 5 for $empinfo[fnames] $empinfo[sname]");
	$pdf->addInfo("Author", USER_NAME);


	$irp5_pos =
		drawText(&$pdf, "<b>IRP 5</b>", 14, 520-($pdf->getTextWidth(14, "<b>IRP 5</b>")), 0);
	$header_pos =
		drawText(&$pdf, $header_out, 12, 520-($pdf->getTextWidth(12, $header_out)), $irp5_pos['y']+14);

	// Employer information --------------------------------------------------
	$employer_information_head =
		drawText(&$pdf, "<b>EMPLOYER INFORMATION</b>", 8, 0, $irp5_pos['y']+14);
	$employer_trading_name_pos =
		drawTable2(&$pdf, $employer_trading_name_out, 0, $employer_information_head['y']+2, 520, 1);
	$employer_irp5_number_pos =
		drawTable2(&$pdf, $employer_irp5_number_out, 0, $employer_trading_name_pos['y'], 260, 1);
	$employer_reference_number_pos =
		drawTable2(&$pdf, $employer_reference_number_out, 0, $employer_irp5_number_pos['y'], 260, 1);
	$employer_tax_year_pos =
		drawTable2 (&$pdf, $employer_tax_year_out, 0, $employer_reference_number_pos['y'], 260, 1);
	$employer_diplomatic_indemnity_pos =
		drawTable2 (&$pdf, $employer_diplomatic_indemnity_out, 0, $employer_tax_year_pos['y'], 260, 1);
	$employer_business_address_pos =
		drawTable2(&$pdf, $employer_business_address_out, $employer_irp5_number_pos['x']+20, $employer_trading_name_pos['y'], 240, 4);
	$employer_postal_code_pos =
		drawTable2 (&$pdf, $employer_postal_code_out, $employer_irp5_number_pos['x']+20, $employer_business_address_pos['y'], 240, 1, $employer_postal_code_cols);

	// Employee information --------------------------------------------------
	$employee_information_head =
		drawText(&$pdf, "<b>EMPLOYEE INFORMATION</b>", 8, 0, $employer_postal_code_pos['y']+15);
	$employee_nature_pos =
		drawTable2(&$pdf, $employee_nature_out, 0, $employee_information_head['y'], 100, 1);
	$employee_surname_pos =
		drawTable2(&$pdf, $employee_surname_out, $employee_nature_pos['x']+20, $employee_information_head['y'], 400, 1);
	$employee_first_names_pos =
		drawTable2(&$pdf, $employee_first_names_out, 0, $employee_nature_pos['y'], 400, 1);
	$employee_initials_pos =
		drawTable2(&$pdf, $employee_initials_out, $employee_first_names_pos['x']+20, $employee_nature_pos['y'], 100, 1);
	$employee_identity_number_pos =
		drawTable2(&$pdf, $employee_identity_number_out, 0, $employee_first_names_pos['y'], 260, 1);
	$employee_residential_pos =
		drawTable2(&$pdf, $employee_residential_out, $employee_identity_number_pos['x']+20, $employee_first_names_pos['y'], 240, 4);
	$employee_postal_code_pos =
		drawTable2(&$pdf, $employee_postal_code_out, $employee_identity_number_pos['x']+20, $employee_residential_pos['y'], 240, 1, $employee_postal_code_cols);
	$employee_number_pos =
		drawTable2(&$pdf, $employee_number_out, $employee_identity_number_pos['x']+20,
		$employee_postal_code_pos['y'], 240, 1);
	$employee_passport_number_pos =
		drawTable2(&$pdf, $employee_passport_number_out, 0, $employee_identity_number_pos['y'], 260, 1);
	$employee_date_of_birth_pos =
		drawTable2(&$pdf, $employee_date_of_birth_out, 0, $employee_passport_number_pos['y'], 260, 1);
	$employee_cc_number_pos =
		drawTable2(&$pdf, $employee_cc_number_out, 0, $employee_date_of_birth_pos['y'], 260, 1);
	$employee_tax_number_pos =
		drawTable2(&$pdf, $employee_tax_number_out, 0, $employee_cc_number_pos['y'], 260, 1);

	// Tax calculation information -------------------------------------------
	$tax_calculation_head =
		drawText(&$pdf, "<b>TAX CALCULATION INFORMATION</b>", 8, 0, $employee_number_pos['y']+15);
	$tax_prd_employed_frm_pos =
		drawTable2(&$pdf, $tax_prd_employed_frm_out, 0, $tax_calculation_head['y'], 160, 1);
	$tax_prd_employed_to_pos =
		drawTable2(&$pdf, $tax_prd_employed_to_out, $tax_prd_employed_frm_pos['x']+20, $tax_calculation_head['y'], 160, 1);
	$tax_over_deduction_pos =
		drawTable2(&$pdf, $tax_over_deduction_out, $tax_prd_employed_to_pos['x']+20, $tax_calculation_head['y'], 160, 1);
	$tax_prd_in_year_pos =
		drawTable2(&$pdf, $tax_prd_in_year_out, 0, $tax_prd_employed_frm_pos['y'], 115, 1);
	$tax_prd_worked_pos =
		drawTable2(&$pdf, $tax_prd_worked_out, $tax_prd_in_year_pos['x']+20, $tax_prd_employed_frm_pos['y'], 115, 1);
	$tax_fixed_rate_income_pos =
		drawTable2(&$pdf, $tax_fixed_rate_income_out, $tax_prd_worked_pos['x']+20, $tax_prd_employed_frm_pos['y'], 115, 1);
	$tax_directive_number_pos =
		drawTable2(&$pdf, $tax_directive_number_out, $tax_fixed_rate_income_pos['x']+20, $tax_prd_employed_frm_pos['y'], 115, 1);

	// Income sources --------------------------------------------------------
	$income_sources_head =
		drawText(&$pdf, "<b>INCOME SOURCE</b>", 8, 0, $tax_prd_in_year_pos['y']+15);
	$income_sources_pos =
		drawTable2(&$pdf, $income_sources_out, 0, $income_sources_head['y']+2, 520, 20, $income_sources_cols, 1);

	$gross_remuneration_head =
		drawText(&$pdf, "<b>GROSS REMUNERATION</b>", 8, 0, $income_sources_pos['y']+15);
	$gross_remuneration_pos =
		drawTable2(&$pdf, $gross_remuneration_out, 0, $gross_remuneration_head['y']+2, 520, 5, $gross_remuneration_cols, 1);

	$deductions_head =
		drawText(&$pdf, "<b>DEDUCTIONS</b>", 8, 0, $gross_remuneration_pos['y']+15);
	$deductions_pos =
		drawTable2(&$pdf, $deductions_out, 0, $deductions_head['y']+2, 520, 15, $deductions_cols, 1);

	$employees_tax_deductions_head =
		drawText(&$pdf, "<b>EMPLOYEES TAX DEDUCTIONS</b>", 8, 0, $deductions_pos['y']+15);
	$employees_tax_deductions_pos =
		drawTable2(&$pdf, $employees_tax_deductions_out, 0, $employees_tax_deductions_head['y']+2, 520, 3, $employees_tax_deductions_cols, 1);

	// Footer note -----------------------------------------------------------
	$certificate_attatch =
		drawText(&$pdf,
		"Attach this certificate to your income tax return or retain it. / ",
		6, 0, $employees_tax_deductions_pos['y']+10);

	$pdf->ezStream();

}



?>