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

require ("settings.php");

$OUTPUT = "<li class='err'>IRP 5 certificate functionality and IT3 is disabled in this version of Cubit. <br> &nbsp;&nbsp;Please update to version 2.90 in October 2007.</li>";

require ("template.php");
die;

// Merge get vars and post vars
foreach ($_GET as $key=>$value) {
	$_POST[$key] = $value;
}

if (!isset($_POST["empnum"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	require ("template.php");
}

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "slct":
			$OUTPUT = slct();
			break;
		case "update":
			$OUTPUT = update($_POST);
			break;
		case "export":
			export($_POST);
			break;
	}
} else {
	$OUTPUT = slct();
}

if (!isset($_POST["key"]) || $_POST["key"] != "export") {
	require ("template.php");
}

function slct($errors="")
{
	global $_POST;
	extract ($_POST);

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

	require ("irp5-codes.php");
	// Income sources dropdown
	$nincome_codes_sel = "<select name='nincome_code' style='width: 180px'>
		<option value='0'>Please select</option>";
	foreach ($income_codes as $category=>$value) {
		foreach ($value as $code=>$description) {
			$nincome_codes_sel .= "<option value='$code'>$code - $description</option>";
		}
	}
	$nincome_codes_sel .= "</select>";

	$income_sources_out = "<tr bgcolor='".TMPL_tblDataColor1."'>
		<td>$nincome_codes_sel</td>
		<td align=center><input type=text name=nincome_description size=100%></td>
		<td align=center>
			<select name=nincome_rfind>
				<option value='N'>No</option>
				<option value='Y'>Yes</option>
			</select>
		</td>
		<td align=center><input type=text name=nincome_amount size=10%></td>
		<td>&nbsp</td>
	</tr>";
	 
	// Retrieve the saved income sources from Cubit
	db_conn("cubit");
	$sql = "SELECT * FROM emp_income_sources WHERE empnum='$empnum' ORDER BY id DESC";
	$rslt = db_exec($sql) or errDie("Unable to retrieve income sources from Cubit.");

	// Should we add the update button
	if (pg_num_rows($rslt) < 20) {
		$update_out = "<input type=submit value='Update'>";
	} else {
		$update_out = "";
	}

	$i = 0;
	while ($income_data = pg_fetch_array($rslt)) {
		$bgcolor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

		// Income sources dropdown
		$income_codes_sel = "<select name='income_code[$income_data[id]]' style='width: 180px'>
			<option value='0'>Please select</option>";
		foreach ($income_codes as $category=>$value) {
			foreach ($value as $code=>$description) {
				if ($code == $income_data["code"]) {
					$selected = "selected";
				} else {
					$selected = "";
				}
				$income_codes_sel .= "<option value='$code' $selected>$code - $description</option>";
			}
		}
 		$income_codes_sel .= "</select>";

		// RF IND dropdown
		$rf_ind_vals = array ("N"=>"No", "Y"=>"Yes");
		
		$rf_ind_sel = "<select name='income_rfind[$income_data[id]]'";
		foreach ($rf_ind_vals as $key=>$value) {
			if ($key == $income_data["rf_ind"]) {
				$selected = "selected";
			} else {
				$selected = "";
			}
			$rf_ind_sel .= "<option value='$key' $selected>$value</option>";
		}
		$rf_ind_sel .= "</select>";
	
		$income_sources_out .= "<tr bgcolor='$bgcolor'>
			<td width=20%>$income_codes_sel</td>
			<td align=center><input type=text name='income_description[$income_data[id]]' size=100% value='$income_data[description]'></td>
			<td align=center>$rf_ind_sel</td>
			<td align=center><input type=text name='income_amount[$income_data[id]]' size=10% value='$income_data[amount]'></td>
			<td><input type=checkbox name=income_rem[$income_data[id]] size=10%></td>
		</tr>";
	}

	// Retrieve employee details
	db_conn("cubit");
	$sql = "SELECT * FROM employees WHERE empnum='$empnum'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee information from Cubit.");
	$empinfo = pg_fetch_array($rslt);
	
	$OUTPUT = "<center>
	<h3>IRP 5 Output for $empinfo[fnames] $empinfo[sname]</h3>
	<form method=post action='".SELF."'>
	<input type=hidden name=key value='update'>
	<input type=hidden name=empnum value='$empnum'>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr>
			<th colspan=5>Date Range</th>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor1."'>
			<td><b>From</b></td>
			<td>".mkDateSelect("fdate",$fdate_year,$fdate_month,$fdate_day)."</td>
			<td><b>To</b></td>
			<td>".mkDateSelect("tdate",$tdate_year,$tdate_month,$tdate_day)."</td>
		</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr>
			<th colspan=2>Values</th>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor1."'>
			<td>IRP 5 Number</td>
			<td><input type=text name='irp5_number' value='$irp5_number'></td>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor2."'>
			<td>Directive Number</td>
			<td><input type=text name='directive_number' value='$directive_number'></td>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor1."'>
			<td>Voluntary over-deduction</td>
			<td><input type=text name='over_deduction' value='$over_deduction'></td>
		</tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100%>
		<tr>
			<td colspan=5>$errors</td>
		</tr>
		<tr>
			<th colspan=5>Income Sources</th>
		</tr>
		<tr>
			<th align=left width=10%>Code</th>
			<th align=left width=70%>Description</th>
			<th align=left width=10%>RF IND</th>
			<th align=left width=10%>Amount</th>
			<th>&nbsp</th>
		</tr>
		$income_sources_out
	</table>
	<p>
	$update_out
	<input type=submit name=display value='Done &raquo'>
	</form>
	</center>";

	return $OUTPUT;
}


function update($_POST)
{
	extract ($_POST);

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
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return slct($confirm);
	}

	// New income sources
	if ($nincome_code !=0 || !empty($nincome_description) || $nincome_rfind != "N" || !empty($nincome_amount)) {
		db_conn("cubit");
		$sql = "INSERT INTO emp_income_sources (empnum, code, description, rf_ind, amount) VALUES
			('$empnum', '$nincome_code', '$nincome_description', '$nincome_rfind', '$nincome_amount')";
		$rslt = db_exec($sql) or errDie("Unable to save income sources to Cubit.");
	}

	// Update old income sources
	if (isset($income_code)) {
		foreach ($income_code as $id=>$value) {
			db_conn("cubit");
			$sql = "UPDATE emp_income_sources SET code='$income_code[$id]', description='$income_description[$id]', rf_ind='$income_rfind[$id]', amount='$income_amount[$id]' WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to update income sources to Cubit.");
		}
	}
	
	// Anything to remove
	if (isset($income_rem)) {
		foreach ($income_rem as $id=>$value) {
			db_conn("cubit");
			$sql = "DELETE FROM emp_income_sources WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to remove selected items from Cubit.");
		}
	}

 	// Where to go from here?
	if (isset($display)) {
		export($_POST);
	} else {
		return slct();
	}
}

function export($_POST)
{
	extract ($_POST);
	
	require_lib("validate");
	$v = new validate;

	// Retrieve company information from Cubit
	db_conn("cubit");
	$sql = "SELECT * FROM compinfo";
	$rslt = db_exec($sql) or errDie("Unable to retrieve company information from Cubit.");
	$comp_data = pg_fetch_array($rslt);

	// Retrieve employee information from Cubit
	db_conn("cubit");
	$sql = "SELECT * FROM employees WHERE empnum='$empnum'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee information from Cubit.");
	$emp_data = pg_fetch_array($rslt);

	// Insert dummy data just so we can get the generation number
	db_conn("cubit");
	$sql = "INSERT INTO irp5_exports (dummy) VALUES ('dummy')";
	$rslt = db_exec($sql) or errDie("Unable to create a dummy entry into Cubit.");

	$generation = pglib_lastid("irp5_exports", "c1120");

	// Employee Initials
	$names = explode(" ", $emp_data["fnames"]);
	$initials = "";
	foreach ($names as $name) {
		$initials = $name{0};
	}

	// Employee birth date
	$birth_date = 1900 + substr($emp_data["idnum"], 0, 2);
	$birth_date .= substr($emp_data["idnum"], 2, 2);
	$birth_date .= substr($emp_data["idnum"], 4, 2);

	switch ($emp_data["payprd"]) {
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

	db_conn("cubit");
	$sql = "SELECT * FROM emp_inc WHERE emp='$emp_data[empnum]' AND description='Basic Salary'";
	$rslt = db_exec($sql);
	$pay_periods_worked = pg_num_rows($rslt);
	
	$codes = array (
		// File header record
		"1010"=>array ("f"=>"A70",  "r"=>"M", "v"=>"$comp_data[compname]"),
		"1020"=>array ("f"=>"N10f", "r"=>"M", "v"=>"0123456789"),
		"1030"=>array ("f"=>"A30",  "r"=>"M", "v"=>USER_NAME),
		"1040"=>array ("f"=>"A16",  "r"=>"M", "v"=>$comp_data["tel"]),
		"1060"=>array ("f"=>"A35",  "r"=>"M", "v"=>$comp_data["paddr1"]),
		"1070"=>array ("f"=>"A35",  "r"=>"O", "v"=>$comp_data["paddr2"]),
		"1080"=>array ("f"=>"A35",  "r"=>"O", "v"=>$comp_data["paddr3"]),
		"1100"=>array ("f"=>"N4f",  "r"=>"O", "v"=>$comp_data["pcode"]),
		"1110"=>array ("f"=>"N8f",  "r"=>"M", "v"=>date("Ymd")),

		// Employer header record
		"2010"=>array ("f"=>"A70",  "r"=>"M", "v"=>$comp_data["compname"]),
		"2020"=>array ("f"=>"N10",  "r"=>"M", "v"=>$emp_data["taxref"]),
		"2030"=>array ("f"=>"N4",   "r"=>"M", "v"=>date("Y")-1),
		"2040"=>array ("f"=>"A35",  "r"=>"M", "v"=>$comp_data["paddr1"]),
		"2050"=>array ("f"=>"A35",  "r"=>"O", "v"=>$comp_data["paddr2"]),
		"2060"=>array ("f"=>"A35",  "r"=>"O", "v"=>$comp_data["paddr3"]),
		"2080"=>array ("f"=>"N4f",  "r"=>"M", "v"=>$comp_data["pcode"]),
		"2090"=>array ("f"=>"A1f",  "r"=>"M", "v"=>$comp_data["diplomatic_indemnity"]),

		// Employee IRP 5 detailed record
		"3010"=>array ("f"=>"A8f",  "r"=>"M", "v"=>"12345678"),
		"3020"=>array ("f"=>"A1",   "r"=>"M", "v"=>$emp_data["nature"]),
		"3030"=>array ("f"=>"A120", "r"=>"M", "v"=>$emp_data["sname"]),
		"3040"=>array ("f"=>"A90",  "r"=>"M", "v"=>$emp_data["fnames"]),
		"3050"=>array ("f"=>"A90",  "r"=>"M", "v"=>$initials),
		"3060"=>array ("f"=>"N13f", "r"=>"M", "v"=>$emp_data["idnum"]),
		"3070"=>array ("f"=>"A13",  "r"=>"O", "v"=>"$emp_data[passport_number]"),
		"3080"=>array ("f"=>"N8",   "r"=>"M", "v"=>$birth_date),
		"3090"=>array ("f"=>"A16",  "r"=>"M", "v"=>$emp_data["cc_number"]),
		"3100"=>array ("f"=>"A10",  "r"=>"M", "v"=>$emp_data["taxref"]),
		"3110"=>array ("f"=>"A35",  "r"=>"M", "v"=>$emp_data["res1"]),
		"3120"=>array ("f"=>"A35",  "r"=>"O", "v"=>$emp_data["res2"]),
		"3130"=>array ("f"=>"A35",  "r"=>"O", "v"=>$emp_data["res3"]),
		"3150"=>array ("f"=>"A25",  "r"=>"M", "v"=>$emp_data["res4"]),
		"3160"=>array ("f"=>"A25",  "r"=>"M", "v"=>$emp_data["empnum"]),
/*N8f*/	"3170"=>array ("f"=>"A18",  "r"=>"M", "v"=>"$emp_data[hiredate]"),
/*N8f*/	"3180"=>array ("f"=>"N8",   "r"=>"O", "v"=>"$emp_data[firedate]"),
 		"3190"=>array ("f"=>"A1f",  "r"=>"M", "v"=>"$over_deduction"),
		"3200"=>array ("f"=>"N3.4f","r"=>"M", "v"=>$pay_periods),
		"3210"=>array ("f"=>"N3.4f","r"=>"M", "v"=>$pay_periods_worked),
		"3220"=>array ("f"=>"A1f",  "r"=>"M", "v"=>"$emp_data[fixed_rate]"),
		"3230"=>array ("f"=>"A13",  "r"=>"O", "v"=>$directive_number),
	);
	
	db_conn("cubit");
	$sql = "SELECT * FROM emp_income_sources WHERE empnum='$empnum'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve income sources from Cubit.");
	
	
	// Income sources --------------------------------------------------------
	$annual_payments = 0;
	$non_taxable_income = 0;
	$retirement_funding_income = 0;
	$non_retirement_funding_income = 0;
	
	while ($income_sources = pg_fetch_array($rslt)) {
		$codes[$income_sources["code"]] = array ("f"=>"N15", "r"=>"M", "v"=>"\"$income_sources[rf_ind]\", $income_sources[amount]");	
		
		if ($income_sources["code"] = 3601) {
			$annual_payments += $income_sources["amount"];
		}
					
		if ($income_sources["rf_ind"] == "Y") {
			$retirement_funding_income += $income_sources["amount"];
		} else {
			$non_retirement_funding_income += $income_sources["amount"];
		}

		
		$codes[3695] = array ("f"=>"N15", "r"=>"M", "v"=>"$annual_payments");
		$codes[3696] = array ("f"=>"N15", "r"=>"M", "v"=>"$non_taxable_income");
		$codes[3697] = array ("f"=>"N15", "r"=>"M", "v"=>"$retirement_funding_income");
		$codes[3698] = array ("f"=>"N15", "r"=>"M", "v"=>"$non_retirement_funding_income");
	}
	// Gross remuneration
	$gross_remuneration = $annual_payments + $non_taxable_income + $retirement_funding_income + $non_retirement_funding_income;
	$codes[3699] = array ("f"=>"N15", "r"=>"M", "v"=>"$gross_remuneration");

	// Deductions --------------------------------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT * FROM emp_ded WHERE emp='$empnum' AND description!='UIF' AND description!='SDL'";
	$rslt = db_exec($sql) or errDie("Unable to retrieve employee deductions from Cubit.");
	
	$site = 0;
	$paye = 0;
	$total_tax = 0;
	while ($deductions = pg_fetch_array($rslt)) {
		if (!empty($deductions["code"])) {
			$codes[$deductions["code"]] = array ("f"=>"N15", "r"=>"M", "v"=>"$deductions[amount]");
			
			if ($deductions["type"] == "PAYE") {
				$paye += $deductions["amount"];
			} elseif ($deductions["type"] = "SITE") {
				$site += $deductions["amount"];
			}
		}
	}
	$total_tax += $paye + $site;
	$codes["4103"] = array ("f"=>"N15", "r"=>"M", "v"=>"$total_tax");
	
	$emp_codes_records = 0;
	$emp_codes_total = 0;
	$emp_amt_total = 0;
	foreach ($codes as $code=>$value) {
		if (($code >= 2010 && $code <= 2090) || ($code >= 3010 && $code <= 4150)) {
			$emp_codes_total += $code;
			$emp_codes_records++;
		}
	}
	$codes_total = 9999 * 6;
	$codes["6010"] = array ("f"=>"N15", "r"=>"M", "v"=>"$emp_codes_records");
	$codes["6020"] = array ("f"=>"N15", "r"=>"M", "v"=>"$emp_codes_total");
	$codes["6030"] = array ("f"=>"N15", "r"=>"M", "v"=>"$emp_amt_total");
	
	$total_records = count($codes) + 2;
	$codes["7000"] = array ("f"=>"N15", "r"=>"M", "v"=>"$total_records");
	
	ksort($codes);
	$last_section = "1";
	$output = "";
	foreach ($codes as $code=>$fields) {
		
		// End of record
		if (substr($code, 0, 1) > $last_section) {
			$output .= " 9999, ";
		}
		$last_section = substr($code, 0, 1);
		
		preg_match("/([AN])([0-9]+)(f?)/", $fields["f"], $matches);

		
		if ($matches[1] == "A") {
			$output .= "$code, \"$fields[v]\" ,";
		} else {
			$output .= "$code, $fields[v] ,";
		}
	}
	
	// Close the record
	$output .= " 9999";
	
	$empnum = $emp_data["empnum"];
	
	header ( "Expires: Mon, 28 Aug 1984 05:00:00 GMT" );
	header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
	header ( "Pragma: no-cache" );
	header ( "Content-type: text" );
	header ( "Content-Disposition: attachment; filename=irp5-$empnum.txt" );
	header ( "Content-Description: IRP 5 Export" );

	print $output;
	// FIXME
	die();
}

// returns the number of date in the specified month and year
/*function getDaysInMonth($month,$year) {
    switch ($month) {
        case 1:
        case 3:
        case 5:
        case 7:
        case 8:
        case 10:
        case 12:
            return 31;
        case 4:
        case 6:
        case 9:
        case 11:
            return 30;
        case 2:
            if ( $year % 4 == 0 ) // is a leap year
                return 29;
            else
                return 28;
        default:
            return false;
    }
}*/
