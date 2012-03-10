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
require("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($HTTP_GET_VARS["key"])) {
	switch ($HTTP_GET_VARS["key"]) {
        case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
            $OUTPUT = write();
			break;
		default:
		case "slctacc":
			$OUTPUT = slctAcc();
			break;
    }
} else {
	$OUTPUT = slctAcc();
}

# get template
require("../template.php");




# Select Account
function slctAcc($err="")
{

	global $HTTP_GET_VARS;
	extract($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 9, "Invalid employee selected.");

    # display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	db_conn("cubit");
	$sql = "SELECT * FROM employees WHERE empnum='$empnum'";
	$rslt = db_exec($sql) or errDie("Error reading employee information.");

	if ( pg_num_rows($rslt) < 1 ) {
		errDie("No such employee $empnum.");
	}

	$emp = pg_fetch_array($rslt);

	$fields = Array(
			"pension" 	=> $emp["expacc_pension"],
			"provident" => $emp["expacc_provident"],
			"uif" 		=> $emp["expacc_uif"],
			"medical" 	=> $emp["expacc_medical"],
			"ret" 		=> $emp["expacc_ret"],
			"salwages" 	=> $emp["expacc_salwages"],
			"sdl" 		=> $emp["expacc_sdl"]
	);

	foreach ( $fields as $fn => $fv ) {
		if ( ! isset(${"expacc_$fn"}) ) {
			${"expacc_$fn"} = $fv;
		}
	}

	core_connect();
	$sql = "SELECT * FROM accounts
			WHERE catid='E10' AND acctype ='E' AND div = '".USER_DIV."'
			ORDER BY topacc, accnum";
	$accRslt = db_exec($sql);
	$numrows = pg_numrows($accRslt);
	if(empty($numrows)){
			return "<li>ERROR : There are no accounts in the category selected.";
	}
	$accs = Array();

	$prevtop = "";
	while($acc = pg_fetch_array($accRslt)){
			if ( $acc["topacc"] == $prevtop && $acc["accnum"] != "000" ) {
				$x = "&nbsp;&nbsp;-&nbsp;&nbsp;$acc[topacc]/$acc[accnum]";
			} else {
				$x = "$acc[topacc]/$acc[accnum]";
				$prevtop = $acc["topacc"];
			}
			if(isb($acc["accid"])) {
				continue;
			}
			$accs[$acc["accid"]] = "$x $acc[accname]";
	}

	// Get employee deductions
	db_conn("cubit");
	$sql = "SELECT * FROM empdeduct WHERE empnum='$empnum' AND div='".USER_DIV."'";
	$dedRslt = db_exec($sql);
	$emp_ded = "";
	while ( $ded = pg_fetch_array($dedRslt) ) {
		db_conn("cubit");
		$sql = "SELECT * FROM salded WHERE id='$ded[dedid]'";
		$rslt = db_exec($sql);
		$dedinfo = pg_fetch_array($rslt);

		if ( $dedinfo["creditor"] == "In House" ) {
			$emp_ded .= "
				<tr bgcolor=".bgcolorg().">
					<td>$dedinfo[deduction]</td>
					<td>".extlib_cpsel("dedaccs[$ded[id]]", $accs, $ded["accid"])."</td>
				</tr>";
		} else {
			$emp_ded .= "<input type='hidden' name='dedaccs[$ded[id]]' value='$ded[accid]'>";
		}
	}

	// Get employee allowances
	db_conn("cubit");
	$sql = "SELECT * FROM empallow WHERE empnum='$empnum' AND div='".USER_DIV."'";
	$allowRslt = db_exec($sql);
	$emp_allow = "";
	while ( $allow = pg_fetch_array($allowRslt) ) {
		db_conn("cubit");
		$sql = "SELECT * FROM allowances WHERE id='$allow[allowid]'";
		$rslt = db_exec($sql);
		$allowinfo = pg_fetch_array($rslt);

		$emp_allow .= "
			<tr bgcolor=".bgcolorg().">
				<td>$allowinfo[allowance]</td>
				<td>".extlib_cpsel("allowaccs[$allow[id]]", $accs, $allow["accid"])."</td>
			</tr>";
	}

	$slctAcc = "
		<h3>Company Contributions to Employee Deductions Expense Accounts</h3>
		$err
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='GET'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='empnum' value='$empnum'>
			<tr>
				<th>Employee Name</th>
				<td bgcolor='".bgcolorg()."'>$emp[fnames] $emp[sname]</td>
			</tr>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Salaries and Wages</td>
				<td>".extlib_cpsel("expacc_salwages", $accs, $expacc_salwages)."</td>
			</tr>
			<tr>
				<th colspan='2'>Deductions</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Pension Fund</td>
				<td>".extlib_cpsel("expacc_pension", $accs, $expacc_pension)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Provident Fund</td>
				<td>".extlib_cpsel("expacc_provident", $accs, $expacc_provident)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Retirement Annuity Fund</td>
				<td>".extlib_cpsel("expacc_ret", $accs, $expacc_ret)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Medical Aid</td>
				<td>".extlib_cpsel("expacc_medical", $accs, $expacc_medical)."</td>
			</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>UIF</td>
				<td>".extlib_cpsel("expacc_uif", $accs, $expacc_uif)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Skills Development Levy</td>
				<td>".extlib_cpsel("expacc_sdl", $accs, $expacc_sdl)."</td>
			</tr>
			$emp_ded
			<tr>
				<th colspan='2'>Allowances</th>
			</tr>
			$emp_allow
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Add Link &raquo'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $slctAcc;

}




# confirm
function confirm()
{

	global $HTTP_GET_VARS;
	extract($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk($empnum, "num", 1, 9, "Invalid employee selected.");
	$v->isOk ($expacc_pension, "string", 1, 3, "Invalid pension account.");
	$v->isOk ($expacc_provident, "string", 1, 3, "Invalid provident account.");
	$v->isOk ($expacc_medical, "string", 1, 3, "Invalid medical account.");
	$v->isOk ($expacc_ret, "string", 1, 3, "Invalid retirement annuity account.");
	$v->isOk ($expacc_uif, "string", 1, 3, "Invalid uif account.");
	$v->isOk ($expacc_salwages, "string", 1, 3, "Invalid salaries and wages account.");
	$v->isOk ($expacc_sdl, "string", 1, 3, "Invalid sdl account.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return slctAcc($confirm);
	}

	$fields = Array(
		"Pension"			 => "pension",
		"Provident"			 => "provident",
		"UIF"				 => "uif",
		"Medical Aid"		 => "medical",
		"Retirement Annuity" => "ret",
		"Salaries & Wages"	 => "salwages",
		"SDL"				 => "sdl"
	);

	foreach ( $fields as $fdesc => $fn ) {
		if ( isb(${"expacc_$fn"}) ) $v->addError("", "Account is blocked for: $fdesc");

		$rslt = get("core", "accname", "accounts", "accid", ${"expacc_$fn"});
		${"name_$fn"} = pg_fetch_result($rslt, 0, 0);
	}

	$emp_ded = "";
	if ( isset($dedaccs) && is_array($dedaccs) ) {
		foreach ($dedaccs as $key => $value) {
			if ( ! $v->isOk($key.$value, "string", 2, 20, "") ) continue;

			// salded
			db_conn("cubit");
			$sql = "SELECT salded.deduction FROM salded, empdeduct
					WHERE salded.id=empdeduct.dedid AND empdeduct.id='$key'";
			$rslt = db_exec($sql);
			$salded = pg_fetch_array($rslt);

			if ( isb($value) ) $v->addError("", "Account is blocked for: $salded[deduction]");

			// accounts
			$name_ded = pg_fetch_result(get("core", "accname", "accounts", "accid", $value), 0, 0);

			$emp_ded .= "
			<input type='hidden' name='dedaccs[$key]' value='$value'>
			<tr bgcolor=".bgcolorg().">
				<td>$salded[deduction]</td>
				<td>$name_ded</td>
			</tr>";
		}
	}

	$emp_allow = "";
	if ( isset($allowaccs) && is_array($allowaccs) ) {
		foreach ($allowaccs as $key => $value) {
			if ( ! $v->isOk($key.$value, "string", 2, 20, "") ) continue;

			// salded
			db_conn("cubit");
			$sql = "SELECT allowances.allowance FROM allowances, empallow
					WHERE allowances.id=empallow.allowid AND empallow.id='$key'";
			$rslt = db_exec($sql);
			$allowinfo = pg_fetch_array($rslt);

			if ( isb($value) ) $v->addError("", "Account is blocked for: $allowinfo[allowance]");

			// accounts
			$name_allow = pg_fetch_result(get("core", "accname", "accounts", "accid", $value), 0, 0);

			$emp_allow .= "
			<input type='hidden' name='allowaccs[$key]' value='$value'>
			<tr bgcolor=".bgcolorg().">
				<td>$allowinfo[allowance]</td>
				<td>$name_allow</td>
			</tr>";
		}
	}

	// display account block errors if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return slctAcc($confirm);
	}

	$confirm = "
		<h3>Company Contributions to Employee Deductions Expense Accounts</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='GET'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='empnum' value='$empnum'>";

	foreach ( $fields as $fn ) {
		$confirm .= "<input type='hidden' name='expacc_$fn' value='".${"expacc_$fn"}."'>";
	}

	$confirm .= "
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Salaries and Wages</td>
				<td>$name_salwages</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Pension Fund</td>
				<td>$name_pension</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Provident Fund</td>
				<td>$name_provident</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Retirement Annuity Fund</td>
				<td>$name_ret</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Medical Aid</td>
				<td>$name_medical</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>UIF</td>
				<td>$name_uif</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Skills Development Levy</td>
				<td>$name_sdl</td>
			</tr>
			$emp_ded
			<tr><th colspan='2'>Allowances</th></tr>
			$emp_allow
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='Add Link &raquo'></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $confirm;

}




# write
function write()
{

	global $HTTP_GET_VARS;
	extract($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk($empnum, "num", 1, 9, "Invalid employee selected.");
	$v->isOk ($expacc_pension, "string", 1, 3, "Invalid pension account.");
	$v->isOk ($expacc_provident, "string", 1, 3, "Invalid provident account.");
	$v->isOk ($expacc_medical, "string", 1, 3, "Invalid medical account.");
	$v->isOk ($expacc_ret, "string", 1, 3, "Invalid retirement annuity account.");
	$v->isOk ($expacc_uif, "string", 1, 3, "Invalid uif account.");
	$v->isOk ($expacc_salwages, "string", 1, 3, "Invalid salaries and wages account.");
	$v->isOk ($expacc_sdl, "string", 1, 3, "Invalid sdl account.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}

		return slctAcc($confirm);
	}

	$fields = Array(
		"pension",
		"provident",
		"uif",
		"medical",
		"ret",
		"salwages",
		"sdl"
	);

	if ( isset($dedaccs) && is_array($dedaccs) ) {
		foreach ($dedaccs as $key => $value) {
			if ( ! $v->isOk($key.$value, "string", 2, 20, "") ) continue;

			db_conn("cubit");
			$sql = "UPDATE empdeduct SET accid='$value' WHERE id='$key' AND empnum='$empnum'";
			$rslt = db_exec($sql);
		}
	}

	if ( isset($allowaccs) && is_array($allowaccs) ) {
		foreach ($allowaccs as $key => $value) {
			if ( ! $v->isOk($key.$value, "string", 2, 20, "") ) continue;

			db_conn("cubit");
			$sql = "UPDATE empallow SET accid='$value' WHERE id='$key' AND empnum='$empnum'";
			$rslt = db_exec($sql);
		}
	}

	$fields_sql = Array();
	foreach ( $fields as $fn ) {
		$fields_sql[] = "expacc_$fn='".${"expacc_$fn"}."'";
	}

	db_conn("cubit");
	$sql = "UPDATE employees
			SET ".implode(",", $fields_sql)."
			WHERE empnum='$empnum'";
	$rslt = db_exec($sql) or errDie("Error updating employee expense accounts.");

	# status report
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Employee Expense account links Successfully Updated</th>
			</tr>
		<table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $write;

}



?>