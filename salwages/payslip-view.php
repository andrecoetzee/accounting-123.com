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

if (isset($_GET["empnum"]) && isset($_GET["id"])){
	$OUTPUT = view();
} else {
	invalid_use();
}

require ("../template.php");




function view()
{

	extract($_GET);

	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($id, "num", 1, 20, "Invalid payslip number.");

	if ($v->isError()) {
		$confirmCust = $v->genErrors()
			."<br><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	if (($emp = qryEmployee($empnum, "*")) === false) {
		$emp = qryLEmployee($empnum, "*");
	}

    if (isset($rev)) {
    	$tbl = "salr";
    } else {
    	$tbl = "salpaid";
    }

	$sql = "SELECT * FROM cubit.$tbl WHERE empnum='$empnum' AND id = '$id' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to read employee salary details from Cubit.");
	if (pg_numrows ($rslt) < 1) {
		return "<li>Employee payment not found.</li>";
	}
	$pay = pg_fetch_array($rslt);

	# Calculate gross salary from nettpay
	$gross = $pay['salary']
			- $pay['totallow']
			- $pay['comm']
			+ $pay['totded']
			+ $pay['uif']
			+ $pay['paye']
			+ $pay['loanins'];
	vsprint($gross);

	# Layout
	$slip = "
	<table ".TMPL_tblDflts.">
	<tr>
		<td align='right'><font size='3' color='white'><b>Employee: </b></font></td>
		<td align='left'><b>$emp[empnum]</b></td>
		<td align='right'><font size='3' color='white'><b>Name: </b></font></td>
		<td align='left'><b>$emp[fnames]</b></td>
		<td align='right'><font size='3' color='white'><b>Surname: </b></font></td>
		<td align='left'><b>$emp[sname]</b></td>
	</tr>
	".TBL_BR."
	</table>
	<table ".TMPL_tblDflts.">
	<tr>
		<th>Description</th>
		<th>Amount</th>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Gross Basic salary</td>
		<td align='center'>".CUR." $gross</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Commission</td>
		<td align='center'>".CUR." $pay[comm]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Low or interest-free loan</td>
		<td align='center'>".CUR." $pay[loanins]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Interest rate charged by company</td>
		<td align='center'>$emp[loanint] %</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Deductions</td>
		<td align='center'>".CUR." $pay[totded]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>UIF</td>
		<td align='center'>".CUR." $pay[uif]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>PAYE</td>
		<td align='center'>".CUR." $pay[paye]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Allowances</td>
		<td align='center'>".CUR." $pay[totallow]</td>
	</tr>
	".TBL_BR."
	<tr bgcolor='".bgcolorg()."'>
		<td><h3>Nett Income</h3></td>
		<td align='center'><b>".CUR." $pay[salary]</b></td>
	</tr>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee")
	);

	return $slip;
}
?>
