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
require ("../settings.php");
require ("../core-settings.php");
require ("../libs/ext.lib.php");

if(isset($HTTP_GET_VARS["id"])) {
	$OUTPUT=printPayslip($HTTP_GET_VARS);
} else {
	$OUTPUT="Invalid use.";
}

# display output
require ("../tmpl-print.php");

# confirm new data
function printPayslip ($HTTP_GET_VARS)
{
	# Get vars
	foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($id, "num", 1, 20, "Invalid payslip number.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	db_conn('cubit');

	$Sl="SELECT * FROM salr WHERE id = '$id'";
	$Ry=db_exec ($Sl) or errDie ("Unable to select employee payments from database.");
	if (pg_numrows ($Ry) < 1) {
		return "<li> - Employee payment not found for selected month.";
	}
	$pay = pg_fetch_array($Ry);

	if($pay['salary']<0) {
		$rev="Reversed";
	} else {
		$rev="";
	}

	$Sl="SELECT * FROM employees WHERE empnum='$pay[empnum]'";
	$Ry=db_exec($Sl) or errDie ("Unable to select employees from database.");
	$emp = pg_fetch_array($Ry);

	$slip=base64_decode($pay['display']);

	$pay['showex']="Yes";

	$date=$pay['saldate'];

	if($pay['showex']=="Yes") {
		if(date("m")>2) {
			$fromdate=date("Y")."-03-01";
		} else {
			$fromdate=(date("Y")-1)."-03-01";
		}

		$Sl="SELECT sum(paye) FROM salpaid WHERE saldate>='$fromdate' AND saldate<='$pay[saldate]' AND empnum='$pay[empnum]'";
		$Ry=db_exec($Sl) or errDie("Unable to get paye");

		$pdata=pg_fetch_array($Ry);

		$paid=$pdata['sum'];

		$Sl="SELECT sum(paye) FROM salr WHERE saldate>='$fromdate' AND saldate<='$pay[saldate]' AND empnum='$pay[empnum]'";
		$Ry=db_exec($Sl) or errDie("Unable to get paye");

		$pdata=pg_fetch_array($Ry);

		$upaid=$pdata['sum'];

		$paid=sprint($paid-$upaid);

		$ex="<tr><td>Available Leave:</td><td>".getLeave($pay['empnum'],"leave_vac")."</tr>
		<tr><td>PAYE to date:</td><td>".CUR." $paid</td></tr>";

	} else {
		$ex="";
	}

	$emp['basic_sal']=sprint($emp['basic_sal']);

	$dates="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
	<tr><td width='50%'>Date</td><td width='50%'>$date</td></tr>
	</table>";

	$i=0;

	$incomes="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
	<tr><td width='80%' align=center>Description</td><td align=center>Amount</td></tr>";

    db_conn('cubit');

	$Sl="SELECT * FROM emp_inc WHERE payslip='-$pay[id]' AND amount<0 ORDER BY amount DESC";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

        $tot_incomes=0;
	while($data=pg_fetch_array($Ri)) {
		$incomes.="<tr><td>$data[description]</td><td align=right>".CUR." $data[amount]</td></tr>";
		$i++;
                $tot_incomes=$tot_incomes+$data['amount'];
	}

	while($i<7) {
		$incomes.="<tr><td><br></td></tr>";
		$i++;
	}

	$incomes.="</table>";

        $i=0;

	$benefits="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>";

	while($i<4) {
		$benefits.="<tr><td><br></td></tr>";
		$i++;
	}

	$benefits.="</table>";

	$i=0;

	$comp_parts="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
	<tr><td align=center>Description</td><td align=center>Amount</td></tr>";

        $Sl="SELECT * FROM emp_com WHERE payslip='-$pay[id]' AND description !='SDL' AND amount<0 ORDER BY amount DESC";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	while($data=pg_fetch_array($Ri)) {
		$comp_parts.="<tr><td width='80%'>$data[description]</td><td width='20%' align=right>".CUR." $data[amount]</td></tr>";
		$i++;
	}

	while($i<7) {
		$comp_parts.="<tr><td><br></td></tr>";
		$i++;
	}

	$comp_parts.="</table>";

        $i=0;

	$deductions="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
	<tr><td width='90%' align=center>Description</td><td align=center>Amount</td></tr>";

        $Sl="SELECT * FROM emp_ded WHERE payslip='-$pay[id]' AND amount<0 ORDER BY amount DESC";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

        $tot_deductions=0;
	while($data=pg_fetch_array($Ri)) {
		$deductions.="<tr><td>$data[description]</td><td align=right>".CUR." $data[amount]</td></tr>";
		$i++;
		$tot_deductions=$tot_deductions+$data['amount'];
	}

	while($i<6) {
		$deductions.="<tr><td><br></td></tr>";
		$i++;
	}

	$deductions.="</table>";

	$pay["salary"] = sprint($pay["salary"]);

	$exstras="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
	<tr><td width='25%'>LEAVE DAYS DUE</td><td width='25%'>".getLeave($pay['empnum'],"leave_vac")."</td><td width='25%'><b>NETT PAY</b></td><td width='25%'><b>".CUR." -$pay[salary]</b></td></tr>
	<tr><td width='25%'>Total Employee's Tax</td><td width='25%'>".CUR." $paid</td><td width='25%'></td><td width='25%'></td></tr>
	</table>";

	$period=$pay['month'];

	$period=$period-2;

	if($period<1) {
		$period=$period+12;
	}

	$tot_incomes=sprint($tot_incomes);

	$tot_deductions=sprint($tot_deductions);

	$grossdata="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
	<tr><td width='50%' align=center><b>GROSS EARNINGS</b></td><td width='50%' align=right>".CUR." $tot_incomes</td></tr>
	</table>";

	$PaySlip="<center>
	<h2>".COMP_NAME." <br>Salary Advice Reversed</h2>
	<table border=1 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=750>
	<tr><td width='50%' align=center><b>Employee Details:</b></td><td>$dates</td></tr>
	<tr><td valign=top>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr><td>Name:</td><td>$emp[sname], $emp[fnames]</td></tr>
		<tr><td>Number:</td><td>$emp[enum]</td></tr>
		<tr><td>ID:</td><td>$emp[idnum]</td></tr>
		<tr><td>Tax No:</td><td>$emp[taxref]</td></tr>
		<tr><td>Rate:</td><td>".CUR." $emp[basic_sal]</td></tr>
		<tr><td>Designation:</td><td>$emp[designation]</td></tr>
		<tr><td>Gender:</td><td>$emp[sex]</td></tr>
		<tr><td>Marital Status:</td><td>$emp[marital]</td></tr>
		</table>
	</td><td valign=top>
            	 <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr><td width='50%'>Period</td><td width='50%'>$period</td></tr>
		</table>
		<table border=1 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr><td align=center colspan=2><b>Company Details:</b></td></tr>
		</table>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr><td>Address:</td><td>".COMP_ADDRESS."</td></tr>
		<tr><td>Tel:</td><td>".COMP_TEL."</td></tr>
		<tr><td>Fax:</td><td>".COMP_FAX."</td></tr>
		<tr><td>Reg No:</td><td>".COMP_REGNO."</td></tr>
		<tr><td>PAYE Ref:</td><td>".COMP_PAYE."</td></tr>
		</table>
	</td>
	</tr>
	<tr><td align=center><b>COMPANY CONTRIBUTIONS</b></td><td align=center><b>INCOME</b></td></tr>
        <tr><td>$comp_parts</td><td>$incomes</td></tr>
	<tr><td align=center></td><td>$grossdata</td></tr>
	<tr><td colspan=2 align=center><b>DEDUCTIONS</b></td></tr>
	 <tr><td colspan=2>$deductions</td></tr>
	 <tr><td></td><td>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr><td width='50%'><b>TOTAL DEDUCTIONS</b></td><td width='50%'>".CUR." $tot_deductions</td></tr>
		</table>
	</td></tr>
	 <tr><td colspan=2>$exstras</td></tr>
	</table>";

	return $PaySlip;

	/*Removed

	<tr><td align=center><b>BENEFITS</b></td></tr>
	<tr><td>$benefits</td></tr>
        */

	/*OLD PAYSLIP

	<center><h2>Salary Advice</h2>
	<table cellpadding='0' cellspacing='4' border=0 width=750>
	<tr><td valign=top width=30%>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
		<tr><td>$emp[sname], $emp[fnames]</td></tr>
		<tr><td>Emp No: $emp[enum]</td></tr>
		<tr><td>ID: $emp[idnum]</td></tr>
		<tr><td>Tax No: $emp[taxref]</td></tr>
		$ex
		</table>
	</td><td valign=top width=30%>
		".COMP_NAME."<br>
		".COMP_ADDRESS."<br>
		".COMP_TEL."<br>
		".COMP_FAX."<br>
		PAYE Ref: ".COMP_PAYE."<br>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><td>Date: $pay[saldate]</td></tr>
		</table>
	</td>
	</tr>
	<tr><td colspan=3>
	<table cellpadding='4' cellspacing='0'>
		<tr><td>$slip</td></tr>
	</table></td></tr>
	<tr><td><br></td></tr>
	<tr><td>Method of payment: $emp[paytype]</td></tr>
	</table>

	*/
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
