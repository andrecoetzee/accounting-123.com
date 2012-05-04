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

#
# admin-employee-view.php :: View employees in db
##

require ("settings.php");
require("salwages/emp-functions.php");

$OUTPUT = viewEmp ();

require ("template.php");




##
# Functions
##

# view employees in db
function viewEmp ()
{

	# Connect to db
	db_connect ();

	global $_GET;

	extract($_GET);

	if (!isset($err)) $err = "";
	else $err = "<li class='err'>$err</li>";

	if (!isset($month)) $month = DATE_MONTH;

	if (isset($emp_group) AND $emp_group != 0){
		$egsearch = "AND emp_group = '$emp_group'";
	}else {
		#check for which groups we have perm
		$get_check = "SELECT payroll_groups FROM users WHERE username = '$_SESSION[USER_NAME]' LIMIT 1";
		$run_check = db_exec ($get_check) or errDie ("Unable to get employees group permissions.");
		if (pg_numrows ($run_check) > 0){
			$earr = pg_fetch_array ($run_check);
			if (strlen ($earr['payroll_groups']) > 0){
			    $eperms = explode (",",$earr['payroll_groups']);
			    $egsearch = " AND (emp_group = '".implode ("' OR emp_group = '",$eperms)."')";
			}
		}else {
			$egsearch = "";
		}
	}

	# Get employees from db
	$employees = "";
	$i = 0;
	$sql = "SELECT * FROM employees WHERE div = '".USER_DIV."' $egsearch ORDER BY sname,fnames";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		$employees = "
			<tr class='".bg_class()."'>
				<td colspan='5'><li class='err'>No Employees Found.</li></td>
			</tr>";
	}else {
		while ($myEmp = pg_fetch_array ($empRslt)) {

			if(isset($all) || isset($emps[$myEmp["empnum"]])) {
				$ex = "checked=yes";
			} else {
				$ex = "";
			}

//			<a href='irp5-export.php?empnum=$myEmp[empnum]'>Export IRP 5</a> |
			$employees .= "
				<tr class='".bg_class()."'>
					<td>$myEmp[enum]</td>
					<td>$myEmp[fnames]</td>
					<td>$myEmp[sname]</td>
					<td align='right' nowrap>".CUR." $myEmp[balance]</td>
					<td>
						<a href='admin-employee-detail.php?empnum=$myEmp[empnum]'>Details</a> |
						<a target=_blank href='salwages/irp5-data.php?empnum=$myEmp[empnum]'>Year to Date</a> |
						<a href='salwages/employee-pay.php?id=$myEmp[empnum]'>Pay</a> |
						<a href='salwages/employee-tran.php?id=$myEmp[empnum]'>Transaction</a> |
						<a target='_blank' href='pdf/irp5-pdf.php?empnum=$myEmp[empnum]'>IRP 5</a> |

						<a target='_blank' href='pdf/it3-pdf.php?empnum=$myEmp[empnum]'>IT 3 (a)</a> |
						<a href='#' onClick=openwindowbg('docman/doc-view-type.php?xin=$myEmp[enum]&type=empl');>View Documents</a> |
						<a href='admin-employee-edit.php?empnum=$myEmp[empnum]'>Edit</a> |
						<a href='salwages/empacc-link.php?empnum=$myEmp[empnum]'>Exp. Accs.</a> |
						<a href='salwages/employee-leave-avail.php?empnum=$myEmp[empnum]'>View Available Leave</a> |
						<a href='admin-employee-rem.php?empnum=$myEmp[empnum]'>Leave Company</a>
					</td>
					<td><input type='checkbox' name='emps[$myEmp[empnum]]' $ex></td>
				</tr>";
		}
	}

	$get_egroups = "SELECT * FROM emp_groups ORDER BY emp_group";
	$run_egroups = db_exec($get_egroups) or errDie ("Unable to get employee group information.");
	if(pg_numrows($run_egroups) < 1){
		$emp_group_drop = "<input type='hidden' name='emp_group' value='0'>";
	}else {
		$emp_group_drop = "<select name='emp_group' onChange='document.form1.submit();'>";
		$emp_group_drop .= "<option value='0'>Select Employee Group</option>";
		while ($egarr = pg_fetch_array ($run_egroups)){
			if (isset($emp_group) AND $emp_group == $egarr['id']){
				$emp_group_drop .= "<option value='$egarr[id]' selected>$egarr[emp_group]</option>";
			}else {
				$emp_group_drop .= "<option value='$egarr[id]'>$egarr[emp_group]</option>";
			}
		}
		$emp_group_drop .= "</select>";
	}

	$get_pays = "SELECT payroll_groups FROM users WHERE username = '$_SESSION[USER_NAME]' LIMIT 1";
	$run_pays = db_exec ($get_pays) or errDie ("Unable to get user payroll group information.");
	if (pg_numrows ($run_pays) > 0){
		$arr = pg_fetch_array ($run_pays);
		#check if the current user has pems to view the current payroll group
		$perms = explode (",",$arr['payroll_groups']);
		if (isset ($emp_group) AND is_array($perms) AND $emp_group != "0"){
			if (!in_array($emp_group,$perms)){
				$employees = "
					<tr>
						<td colspan='5'><li class='err'>You Do Not Have Permission To View This Payroll Group.</td>
					</tr>";
			}
		}elseif (strlen ($arr['payroll_groups']) < 1) {
			return "<li class='err'>You Have Insufficient Permissions To Access The Cubit Payroll. You May Add The Permission <a href='admin-usredit.php?username=$_SESSION[USER_NAME]'>Here</a></li>";
		}
	}


	# Set up table & form
	$enterEmp = "
		<h3>Employees</h3>
		$err
		<form action='salwages/salaries-batch.php' method='POST' name='form1'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='6'><input type='button' onClick='move(\"salwages/irp5-data.php\");'
					value ='Year to Date/Payslips for all Employees' /></td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Filter By Employee Group</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$emp_group_drop</td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Nr.</th>
				<th>First names</th>
				<th>Last name</th>
				<th>Salary Due</th>
				<th colspan='4'>Options</th>
			</tr>
			$employees
			<tr class='".bg_class()."'>
				<td colspan='6'>Total: $i</td>
			</tr>
			".TBL_BR."
		 	<tr>
		 		<td colspan='5' align='right'><input type='submit' value='Select All' name='all'></td>
		 	</tr>
			".TBL_BR."
			<tr>
				<td colspan='5' align='right'>
					<table ".TMPL_tblDflts." width='350'>
						<tr>
							<td width='100%'>&nbsp;</td>
							<th nowrap='t'>Salary Period:</th>
							<td bgcolor='".bgcolorc(1)."'>".empMonList("month", $month)."</td>
						</tr>
						<tr>
							<td colspan='3' align='right'><input type='submit' value='Process Daily Salaries &raquo;' name=d></td>
						</tr>
						<tr>
							<td colspan='3' align='right'><input type='submit' value='Process Weekly Salaries &raquo;'name=w></td>
						</tr>
						<tr>
							<td colspan='3' align='right'><input type='submit' value='Process Fortnightly Salaries &raquo;' name=b></td>
						</tr>
						<tr>
							<td colspan='3' align='right'><input type='submit' value='Process Monthly Salaries &raquo;' name=m></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>"
		.mkQuickLinks(
			ql("admin-employee-add.php", "Add Employee")
		);
	return $enterEmp;

}



?>
