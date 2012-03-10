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

## Decide
if (isset($HTTP_POST_VARS["key"])) {
        switch ($HTTP_POST_VARS["key"]) {
                case "slip":
	        	$OUTPUT = slip($HTTP_POST_VARS);
		        break;
		        case "export":
	        	$OUTPUT = export($HTTP_POST_VARS);
		        break;
                default:
                        $OUTPUT = slctEmployee ();
	        }
}else{
        $OUTPUT = slctEmployee ();
}


# display output
require ("../template.php");

# month list
function mlist($name){
        $month=1;
        $months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
        $list = "<select name=$name>";
        while($month <= 12){
                $list .="<option value='$month'>$months[$month]</option>";
                $month++;
        }
        $list .="</select>";
        return $list;
}

# select employee
function slctEmployee ()
{
	$employees = "<select size=1 name=empnum>\n";
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
        while ($myEmp = pg_fetch_array ($empRslt)) {
                $employees .= "<option value='$myEmp[e]'>$myEmp[sname], $myEmp[fnames] ($myEmp[empnum])</option>\n";
        }
        $employees .= "</select>";

	$slctEmployee =
        "<h3>Select month to view</h3>
        <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <form action='".SELF."' method=post>
                <input type=hidden name=key value=slip>
                <tr><th>Select Month</th></tr>
                <tr><td align=center>".mlist("mon")."</td></tr>
		<tr><th>Employee</th></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td align=center>$employees</td></tr>
                <tr><td colspan=2 align=right><input type=submit value='View &raquo;'></td></tr>
                </form>
        </table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);

        return $slctEmployee;
}

# confirm new data
function slip ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate();
        $v->isOk ($mon, "num", 1, 2, "Invalid month.");
	$empnum+=0;

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

        $months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

	if($mon<10) {
		$mon="0".$mon;
	}

	$month=$mon;
	$month+=0;

	# get employee details
	db_connect ();
        $sql = "SELECT * FROM salpaid WHERE month='$mon' OR month='$month' AND div = '".USER_DIV."' AND empnum='$empnum'";
	$pRslt = db_exec ($sql) or errDie ("Unable to select employee payments from database.");

	$mon+=0;

	if (pg_numrows ($pRslt) < 1) {
		return "<li class=err> - Employee payment not found for $months[$mon].</li>".slctEmployee ();
	}



        if (pg_numrows ($pRslt) > 0) {
                $slip = "<center><h3>Salaries Paid in $months[$mon]</h3>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='70%'>
                <tr>
                	<th>Employee</th>
                	<th>Gross Salary</th>
                	<th>Commission</th>
                	<th>Low or interest free loan</th>
                	<th>UIF</th>
                	<th>PAYE</th>
                	<th>Deductions</th>
                	<th>Nett Income</th>
                	<th colspan=2>Options</th>
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
		                return "Invalid employee ID.";
	                }
                        $emp = pg_fetch_array($empRslt);

                        # Calculate gross salary from nettpay
                        $gross = sprint(round(($pay['salary'] - $pay['totallow'] - $pay['comm'] + $pay['totded'] + $pay['uif'] + $pay['paye'] + $pay['loanins']), 2));

                        $bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                        $slip .= "<tr bgcolor='$bgColor'><td>$emp[fnames] $emp[sname]</td><td>".CUR." $gross</td><td>".CUR." $pay[comm]</td><td>".CUR." $pay[loanins]</td><td>".CUR." $pay[uif]</td><td>".CUR." $pay[paye]</td><td>".CUR." $pay[totded]</td><td>".CUR." $pay[salary]</td><td><a href='payslip-view.php?empnum=$pay[empnum]&mon=$mon&id=$pay[id]'>View</a></td><td><a target='_blank' href='payslip-print.php?id=$pay[id]'>Print</a></td></tr>";

                        $totgross += $gross;
                        $totcomm += $pay['comm'];
                        $totins += $pay['loanins'];
                        $totuif += $pay['uif'];
                        $totpaye += $pay['paye'];
                        $totded += $pay['totded'];
                        $totsal += $pay['salary'];

                }

                # Format the totals
                $totgross = sprintf("%01.2f", round($totgross, 2));
                $totcomm = sprintf("%01.2f", round($totcomm, 2));
                $totins = sprintf("%01.2f", round($totins, 2));
                $totuif = sprintf("%01.2f", round($totuif, 2));
                $totpaye = sprintf("%01.2f", round($totpaye, 2));
                $totded = sprintf("%01.2f", round($totded, 2));
                $totsal = sprintf("%01.2f", round($totsal, 2));

                $slip .= "
                <tr bgcolor='".TMPL_tblDataColor2."'><td><b>Total</b></td><td><b>".CUR." $totgross</b></td><td><b>".CUR." $totcomm</b></td><td><b>".CUR." $totins</b></td><td><b>".CUR." $totuif</b></td><td><b>".CUR." $totpaye</b></td><td><b>".CUR." $totded</b></td><td><b>".CUR." $totsal</b></td><td colspan=2></td></tr>
                ";
        }else{
                return "<li> - There are no salary payments for the selected month";
        }

        # layout
        $slip .= "
        <tr><td><br></td></tr>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value='export'>
        <input type=hidden name=mon value='$mon'>
        <input type=hidden name=empnum value='$empnum'>
        <tr><td colspan=2><input type=submit value='Export to Spreadsheet'></td></tr>


        </table><p>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);

        return $slip;
}

function export ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate();
        $v->isOk ($mon, "num", 1, 2, "Invalid month.");
	$empnum+=0;

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

        $months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

	if($mon<10) {
		$mon="0".$mon;
	}

	$month=$mon;
	$month+=0;

	# get employee details
	db_connect ();
        $sql = "SELECT * FROM salpaid WHERE month='$mon' OR month='$month' AND div = '".USER_DIV."' AND empnum='$empnum'";
	$pRslt = db_exec ($sql) or errDie ("Unable to select employee payments from database.");

	$mon+=0;

	if (pg_numrows ($pRslt) < 1) {
		return "<li class=err> - Employee payment not found for $months[$mon].</li>".slctEmployee ();
	}



        if (pg_numrows ($pRslt) > 0) {
                $slip = "<center><h3>Salaries Paid in $months[$mon]</h3>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='70%'>
                <tr><th>Employee</th><th>Gross Salary</th><th>Commission</th><th>Low or interest free loan</th><th>UIF</th><th>PAYE</th><th>Deductions</th><th>Nett Income</th></tr>";

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
		                return "Invalid employee ID.";
	                }
                        $emp = pg_fetch_array($empRslt);

                        # Calculate gross salary from nettpay
                        $gross = round(($pay['salary'] - $pay['totallow'] - $pay['comm'] + $pay['totded'] + $pay['uif'] + $pay['paye'] + $pay['loanins']), 2);

                        $bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                        $slip .= "<tr><td>$emp[fnames] $emp[sname]</td><td>".CUR." $gross</td><td>".CUR." $pay[comm]</td><td>".CUR." $pay[loanins]</td><td>".CUR." $pay[uif]</td><td>".CUR." $pay[paye]</td><td>".CUR." $pay[totded]</td><td>".CUR." $pay[salary]</td></tr>";

                        $totgross += $gross;
                        $totcomm += $pay['comm'];
                        $totins += $pay['loanins'];
                        $totuif += $pay['uif'];
                        $totpaye += $pay['paye'];
                        $totded += $pay['totded'];
                        $totsal += $pay['salary'];

                }

                # Format the totals
                $totgross = sprintf("%01.2f", round($totgross, 2));
                $totcomm = sprintf("%01.2f", round($totcomm, 2));
                $totins = sprintf("%01.2f", round($totins, 2));
                $totuif = sprintf("%01.2f", round($totuif, 2));
                $totpaye = sprintf("%01.2f", round($totpaye, 2));
                $totded = sprintf("%01.2f", round($totded, 2));
                $totsal = sprintf("%01.2f", round($totsal, 2));

                $slip .= "
                <tr><td><b>Total</b></td><td><b>".CUR." $totgross</b></td><td><b>".CUR." $totcomm</b></td><td><b>".CUR." $totins</b></td><td><b>".CUR." $totuif</b></td><td><b>".CUR." $totpaye</b></td><td><b>".CUR." $totded</b></td><td><b>".CUR." $totsal</b></td></tr>
                </table>";
        }else{
                return "<li> - There are no salary payments for the selected month";
        }
		$OUTPUT=$slip;

		include("../xls/temp.xls.php");
		Stream("Employee", $OUTPUT);


        return $slip;
}
?>
