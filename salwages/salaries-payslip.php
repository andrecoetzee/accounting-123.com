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

## yadda yadda
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "process":
			$OUTPUT = process($_POST);
			break;
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = slctEmployee ();
	}
}else{
	$OUTPUT = slctEmployee ();
}

# display output
require ("../template.php");



# select employee
function slctEmployee ()
{

	db_connect ();

	# select employees
	$employees = "<select size='1' name='empnum'>\n";
	$sql = "SELECT empnum, sname, fnames FROM employees WHERE div = '".USER_DIV."' ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "No employees found in database.";
	}
	while ($myEmp = pg_fetch_array ($empRslt)) {
		$employees .= "<option value='$myEmp[empnum]'>$myEmp[sname], $myEmp[fnames] ($myEmp[empnum])</option>\n";
	}
	$employees .= "</select>\n";

	$slctEmployee = "
		<h3>Select employee to process</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='process'>
			<tr>
				<th colspan='2'>Employee</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Employee</td>
				<td align='center'>$employees</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Process &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $slctEmployee;

}



# confirm new data
function process ($_GET)
{

	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");

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

	# get employee details
	db_connect ();

	$sql = "SELECT * FROM employees WHERE empnum='$empnum'";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) < 1) {
		return "Invalid employee ID.";
	}
	$myEmp = pg_fetch_array ($empRslt);

	# get fringebens
	$fringebens = "";
	$i = 0;
	$sql = "SELECT * FROM fringebens ORDER BY fringeben";
	$fringeRslt = db_exec ($sql) or errDie ("Unable to select fringe benefits from database.");
	if (pg_numrows ($fringeRslt) < 1) {
		$fringebens = "
			<tr>
				<td class='".bg_class()."' colspan='2' align='center'>None found in database.</td>
			</tr>\n";
	} else {
		while ($myFringe = pg_fetch_array ($fringeRslt)) {

			$fringebens .= "
				<tr class='".bg_class()."'>
					<td>$myFringe[fringeben]</td>";
			# check if employee has fringe benefit
			$sql = "SELECT * FROM empfringe WHERE fringebenid='$myFringe[id]' AND empnum='$myEmp[empnum]'";
			$empFringeRslt = db_exec ($sql) or errDie ("Unable to select fringe benefit info from database.");
			if (pg_numrows ($empFringeRslt) < 1) {
				$fringebens .= "<td align='center'>No Fringe benefits for this Employee</td></tr>\n";
			} else {
				$myEmpFringe = pg_fetch_array ($empFringeRslt);
				$fringebens .= "
						<td align='center'>R 
							<input type='hidden' size='10' name='fringebenid[]' value='$myEmpFringe[fringebenid]'>
							<input type='hidden' size='30' name='fringename[]' value='$myFringe[fringeben]'>
							<input type='text' size='10' name='fringebens[]' value='$myEmpFringe[amount]' class='right'>
						</td>
					</tr>\n";
			}
			$i++;
		}
	}

	# get allowances
	$allowances = "";
	$i = 0;
	$sql = "SELECT * FROM allowances ORDER BY allowance";
	$allowRslt = db_exec ($sql) or errDie ("Unable to select allowances from database.");
	if (pg_numrows ($allowRslt) < 1) {
		$allowances = "
			<tr>
				<td class='".bg_class()."' colspan='2' align='center'>None found in database.</td>
			</tr>\n";
	} else {
		while ($myAllow = pg_fetch_array ($allowRslt)) {

			$allowances .= "
				<tr class='".bg_class()."'>
					<td>$myAllow[allowance]</td>";
			# check if employee has allowance
			$sql = "SELECT * FROM empallow WHERE allowid='$myAllow[id]' AND empnum='$myEmp[empnum]'";
			$empAllowRslt = db_exec ($sql) or errDie ("Unable to select allowance info from database.");
			if (pg_numrows ($empAllowRslt) < 1) {
				$allowances .= "<td align='center'>No Allowances For this employee</td></tr>\n";
			} else {
				$myEmpAllow = pg_fetch_array ($empAllowRslt);
				$allowances .= "
						<td align='center'>R
							<input type='hidden' size='10' name='allowid[]' value='$myEmpAllow[allowid]' class='right'>
							<input type='hidden' size='30' name='allowname[]' value='$myAllow[allowance]'>
							<input type='text' size='10' name='allowances[]' value='$myEmpAllow[amount]' class='right'>
						</td>
					</tr>\n";
			}
			$i++;
		}
	}

	# Deductions
	$deductions = "";
	$i = 0;
	$sql = "SELECT * FROM salded ORDER BY deduction";
	$deductRslt = db_exec ($sql) or errDie ("Unable to select deductions from database.");
	if (pg_numrows ($deductRslt) < 1) {
		$deductions = "
			<tr>
				<td class='".bg_class()."' colspan='2' align='center'>None found in database.</td>
			</tr>\n";
	} else {
		while ($myDeduct = pg_fetch_array ($deductRslt)) {

			$deductions .= "
				<tr class='".bg_class()."'>
					<td>$myDeduct[deduction]</td>";
			# check if employee has deduction
			$sql = "SELECT * FROM empdeduct WHERE dedid='$myDeduct[id]' AND empnum='$myEmp[empnum]'";
			$empDeductRslt = db_exec ($sql) or errDie ("Unable to select Deduction info from database.");
			if (pg_numrows ($empDeductRslt) < 1) {
				$deductions .= "<td align='center'>No Deductions for this employee</td></tr>\n";
			} else {
				$myEmpDeduct = pg_fetch_array ($empDeductRslt);
				$deductions .= "
						<td align='center'>".CUR." 
							<input type='hidden' size='10' name='deductid[]' value='$myDeduct[id]'>
							<input type='hidden' size='30' name='deductname[]' value='$myDeduct[deduction]'>
							<input type='text' size='10' name='deductions[]' value='$myEmpDeduct[amount]' class='right'>
						</td>
					</tr>\n";
			}
			$i++;
		}
	}

	$process = "
		<h3>Process Staff Salary</h3>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='empnum' value='$empnum'>
			<tr>
				<th colspan='2'>Cash section</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Gross Basic salary</td>
				<td align='center'>".CUR."<br><input type='text' size='10' name='basic_sal' value='$myEmp[basic_sal]' class='right'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Commission</td>
				<td align='center'>".CUR."<br><input type='text' size='10' name='commission' value='$myEmp[commission]' class='right'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Low or interest-free loan</td>
				<td align='center'>".CUR."<br><input type='text' size='10' name='loaninstall' value='$myEmp[loaninstall]' class='right'></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Interest rate charged by company</td>
				<td align='center'>%<br><input type='text' size='5' name='loanint' value='$myEmp[loanint]' class='right'></td>
			</tr>
			<tr><th colspan='2'>Allowances</th></tr>
			$allowances
			<tr><th colspan='2'>Fringe benefits</th></tr>
			$fringebens
			<tr><th colspan='2'>Deductions</th></tr>
			$deductions
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</form>
		</table>";
	return $process;

}

# Confirm data
function confirm ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
	$v->isOk ($basic_sal, "float", 1, 20, "Invalid basic salary.");
	$v->isOk ($commission, "float", 1, 20, "Invalid commision.");
	$v->isOk ($loaninstall, "float", 0, 20, "Invalid loan instalment.");
	$v->isOk ($loanint, "float", 0, 20, "Invalid loan interest.");
        /* commented out arrays becouse they give errors
        $v->isOk ($fringebens[], "float", 0, 10, "Invalid fringe benefits.");
	$v->isOk ($fringebenid, "num", 0, 10,  "Invalid fringe benefits Id's.");
	$v->isOk ($allowances, "float", 0, 10, "Invalid Allowances.");
        $v->isOk ($allowid, "num", 0, 10, "Invalid Allowance Id's.");
        $v->isOk ($allowtax, "float", 0, 10, "Invalid Allowance Taxes.");
        */
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


	# Get arrays and totals
	if(isset($_POST['fringebens'])){
		// sum up fringes
		$fringebens = $_POST['fringebens'];
		$tlfringe = 0;
		foreach ($fringebens as $key => $amount) {
			$tlfringe = ($tlfringe + $amount);
		}
	}else{
		$tlfringe = 0;
	}

        if(isset($_POST['deductions'])){
                // sum up deductions
                $deductions = $_POST['deductions'];
                $tldeduct = 0;
                foreach ($deductions as $key => $amount) {
		        $tldeduct = ($tldeduct + $amount);
	        }
        }else{
                $tldeduct = 0;
        }

        if(isset($_POST['allowtax'])){
                // sum up allowance
                $allowances = $_POST['allowances'];
                $tlallow = 0;
                $allowtax  = $_POST['allowtax'];
                foreach ($allowtax as $key => $perc) {
                        if($perc > 0){
                        $tlallow = ($tlallow + $allowances[$key]);
                        }
	        }
        }else{
                $tlallow = 0;
        }

        # Calculate UIF
        // Get UIF percentage
        db_connect();

        $sql = "SELECT value FROM settings WHERE constant='UIF_IND'";
        $percrslt = db_exec($sql);
        $perc = pg_fetch_array($percrslt);
        $uifperc = $perc['value'];
        $uif = ((($basic_sal + $tlallow + $tlfringe)*$uifperc)/100);

        # Calculate PAYE
        // Get PAYE percentage
        $sql = "SELECT percentage FROM paye WHERE min <= $basic_sal AND max >= $basic_sal";
        $percrslt = db_exec($sql);
        $perc = pg_fetch_array($percrslt);
        $payeperc = $perc['percentage'];
        $paye = ((($basic_sal + $commission + $tlallow + $tlfringe)*$payeperc)/100);

	# Get Fringe names and value from arrays
	if(isset($_POST['fringebens'])){
		// get fringe values
		#$fringebens = $_POST['fringebens'];
		#$fringname = $_POST['fringename'];
		#$fringbenid = $_POST['fringebenid'];
		$fringe="";
		$i = 0;
		while($i <= (count($fringebens)-1)) {

			$fringe .= "
				<tr class='".bg_class()."'>
					<td>$fringename[$i]</td>
					<input type='hidden' size='10' name='fringebenid[]' value='$fringebenid[$i]'>
					<input type='hidden' size='10' name='fringebens[]' value='$fringebens[$i]' class='right'>
					<td>".CUR." $fringebens[$i]</td>
				</tr>";
			$i++;
                }
        }else{
                $fringe .= "
			<tr>
				<td>No Fringe Benefits for this employee</td>
				<td><br></td>
			</tr>";
        }

	# Get allowances names and value from array
	if(isset($_POST['allowances'])){
		// get allowance amount and name
		#$allowances = $_POST['allowances'];
		#$allowname  = $_POST['allowname'];
		#$allowid  = $_POST['allowid'];
		$allow = "";
		$i = 0;
		while($i <= (count($allowname)-1)) {

			$allow .= "
				<tr class='".bg_class()."'>
					<td>$allowname[$i]</td>
					<input type='hidden' size='10' name='allowid[]' value='$allowid[$i]'>
					<input type='hidden' size='10' name='allowances[]' value='$allowances[$i]'>
					<td>".CUR." $allowances[$i]</td>
				</tr>";
                     $i++;
                }
        }else{
                $allow = "
			<tr>
				<td colspan='2'>No Allowances For This Employee </td>
			</tr>";
        }

	# Get Deductions names and values from arrays
	if(isset($_POST['deductions'])){
		// get fringe values
		#$deductions = $_POST['deductions'];
		#$deductname = $_POST['deductname'];
		#$deductid = $_POST['deductid'];
		$deduct="";
		$i = 0;
		while($i <= (count($deductions)-1)) {

			$deduct .= "
				<tr class='".bg_class()."'>
					<td>$deductname[$i]</td>
					<input type='hidden' size='10' name='deductid[]' value='$deductid[$i]'>
					<input type='hidden' size='10' name='deductions[]' value='$deductions[$i]'>
					<td>".CUR." $deductions[$i]</td>
				</tr>";
                      $i++;
                }
        }else{
                $deduct .= "
			<tr>
				<td>No Deductions for this employee</td>
				<td><br></td>
			</tr>";
        }

	$confirm = "
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='empnum' value='$empnum'>
			<input type='hidden' name='basic_sal' value='$basic_sal'>
			<input type='hidden' name='commission' value='$commission'>
			<input type='hidden' name='loaninstall' value='$loaninstall'>
			<input type='hidden' name='loanint' value='$loanint'>
			<input type='hidden' name='uifperc' value='$uifperc'>
			<input type='hidden' name='uif' value='$uif'>
			<input type='hidden' name='payeperc' value='$payeperc'>
			<input type='hidden' name='paye' value='$paye'>
			<tr>
				<th colspan='2'>Cash section</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Gross Basic salary</td>
				<td align='center'>".CUR." $basic_sal</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Commission</td>
				<td align='center'>".CUR." $commission</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Low or interest-free loan</td>
				<td align='center'>".CUR." $loaninstall</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Interest rate charged by company</td>
				<td align='center'>$loanint%</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>UIF Percentage</td>
				<td align='center'>".$uifperc."%</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Total UIF</td>
				<td align='center'>".CUR." $uif</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>PAYE Percentage</td>
				<td align='center'>$payeperc%</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Total PAYE</td>
				<td align='center'>".CUR." $paye</td>
			</tr>
			<tr><th colspan='2'>Fringe Benefits</th></tr>
			$fringe
			<tr><th colspan='2'>Allowances</th></tr>
			$allow
			<tr><th colspan='2'>Deductions</th></tr>
			$deduct
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
			</tr>
		</form>
		</table>";
        return $confirm;

}

# write new data
function write ($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($empnum, "num", 1, 20, "Invalid employee number.");
        $v->isOk ($basic_sal, "float", 1, 20, "Invalid basic salary.");
	$v->isOk ($commission, "float", 1, 20, "Invalid commision.");
	$v->isOk ($loaninstall, "float", 0, 20, "Invalid loan instalment.");
	$v->isOk ($loanint, "float", 0, 20, "Invalid loan interest.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$write .= "<li class='err'>".$e["msg"]."</li>";
		}
		$write .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $write;
	}

	# connect to db
	db_connect ();

	# write fringes to db
        foreach($fringebenid as $i => $id){
                $sql = "SELECT * FROM empfringe WHERE fringebenid = '$id' AND empnum = '$empnum'";
                $fringeRslt = db_exec($sql);
                $rows = pg_numrows($fringeRslt);
                if($rows > 0){
                        $sql = "UPDATE empfringe SET amount= '$fringebens[$i]' WHERE fringebenid ='$id'  AND empnum = '$empnum'";
                }else{
                        $sql = "INSERT INTO empfringe (fringebenid, empnum, amount) VALUES ('$id', '$empnum', '$fringebens[$i]')";
	        }
		$fringeRslt = db_exec ($sql) or errDie ("Unable to add report to database.");
	        if (pg_cmdtuples ($fringeRslt) < 1) {
		        return "Unable to add Employee fringes to database.";
	        }
         }
         # delete empfringes with zeros on the amount
         $sql = "DELETE FROM empfringe WHERE amount=0";
         $delRslt = db_exec($sql);

	# write Allowances to db
	foreach($allowid as $i => $id){
		$sql = "SELECT * FROM empallow WHERE allowid = '$id' AND empnum = '$empnum'";
		$allowRslt = db_exec($sql);
		$rows = pg_numrows($allowRslt);
		if($rows > 0){
			$sql = "UPDATE empallow SET amount= '$allowances[$i]' WHERE allowid ='$id' AND empnum = '$empnum'";
		}else{
			$sql = "INSERT INTO empallow (allowid, empnum, amount) VALUES ('$id', '$empnum', '$allowances[$i]')";
		}
		$allowRslt = db_exec ($sql) or errDie ("Unable to proccess Employee allowances in database.");
	        if (pg_cmdtuples ($allowRslt) < 1) {
		        return "Unable to add Employee Allowances to database.";
	        }
         }
         # delete empallow with zeros on the amount
         $sql = "DELETE FROM empallow WHERE amount=0";
         $delRslt = db_exec($sql);

	# write Deductions to db
	foreach($deductid as $i => $id){
		$sql = "SELECT * FROM empdeduct WHERE dedid = '$id' AND empnum = '$empnum'";
		$deductRslt = db_exec($sql);
		$rows = pg_numrows($deductRslt);
		if($rows > 0){
			$sql = "UPDATE empdeduct SET amount= '$deductions[$i]' WHERE dedid ='$id' AND empnum = '$empnum'";
		}else{
			$sql = "INSERT INTO empdeduct (dedid, empnum, amount) VALUES ('$id', '$empnum', '$deductions[$i]')";
		}
		$deductRslt = db_exec ($sql) or errDie ("Unable to proccess Employee deductions in database.");
	        if (pg_cmdtuples ($deductRslt) < 1) {
		        return "Unable to add Employee deductions to database.";
	        }
	}
	# delete empallow with zeros on the amount
	$sql = "DELETE FROM empdeduct WHERE amount=0";
	$delRslt = db_exec($sql);



	$write = "<center>
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th colspan='2'>Employee Salary has been successfully proccessed on system</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Gross Basic salary</td>
				<td align='center'>".CUR." $basic_sal</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Commission</td>
				<td align='center'>".CUR." $commission</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Low or interest-free loan</td>
				<td align='center'>".CUR." $loaninstall</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Interest rate charged by company</td>
				<td align='center'>$loanint%</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>UIF Percentage</td>
				<td align='center'>".$uifperc."%</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Total UIF</td>
				<td align='center'>".CUR." $uif</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>PAYE Percentage</td>
				<td align='center'>$payeperc%</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Total PAYE</td>
				<td align='center'>".CUR." $paye</td>
			</tr>
		</table>
		</center>";
	return $write;

}


?>