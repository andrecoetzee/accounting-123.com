<?

	require ("../settings.php");

	$OUTPUT = show_listing ();

	require ("../template.php");


function show_listing ()
{

	db_connect ();

#######################[ LOANS APPLICATIONS ]##########################
	$get_loans = "SELECT * FROM loan_requests ORDER BY loanamt";
	$run_loans = db_exec($get_loans) or errDie("Unable to get loan applications.");
	if(pg_numrows($run_loans) < 1){
		$listing = "<tr bgcolor='".bgcolorg()."'><td colspan='8'>No Loan Applications Found.</td></tr>";
	}else {
		$listing = "";
		while ($larr = pg_fetch_array($run_loans)){

			$get_emp = "SELECT fnames,sname FROM employees WHERE empnum = '$larr[empnum]' LIMIT 1";
			$run_emp = db_exec($get_emp) or errDie("Unable to get employee information.");
			if(pg_numrows($run_emp) < 1){
				$showemp = "<li class='err'>Invalid Employee For Loan Selected</li>";
			}else {
				$earr = pg_fetch_array($run_emp);
				$showemp = "$earr[fnames] $earr[sname]";
			}

			$get_type = "SELECT * FROM loan_types WHERE id = '$larr[loan_type]' LIMIT 1";
			$run_type = db_exec($get_type) or errDie("Unable to get loan type information.");
			if(pg_numrows($run_type) < 1){
				$showloantype = "Invalid Loan Type Selected";
			}else {
				$tarr = pg_fetch_array($run_type);
				$showloantype = $tarr['loan_type'];
			}

			$listing .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$showemp</td>
						<td>$larr[loanamt]</td>
						<td>$larr[loaninstall]</td>
						<td>$larr[loanint]</td>
						<td>$larr[loanperiod]</td>
						<td>$larr[loandate]</td>
						<td>$showloantype</td>
						<td><a href='loan_apply_approve.php?id=$larr[id]&deny=t'>Deny</a></td>
						<td><a href='loan_apply_approve.php?id=$larr[id]'>Approve</a></td>
					</tr>
				";
		}
	}
########################################################################


#####################[ CURRENT LOANS ]##################################


	$employees = "";
	$i = 0;

	db_connect ();

	$sql = "SELECT * FROM employees WHERE gotloan='t'::bool AND div = '".USER_DIV."' ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees with loans from database.");
	if (pg_numrows ($empRslt) < 1) {
		$employees .= "
					<tr bgcolor='".bgcolorg()."'>
						<td colspan='7'>No Employees With Loans Found.</td>
					</tr>";
// 		return "No employee-loans found in database.<p>"
// 		.mkQuickLinks(
// 			ql("loan_apply.php", "Apply For New Loan"),
// 			ql("../admin-employee-add.php", "Add Employee"),
// 			ql("../admin-employee-view.php", "View Employees")
// 		);
	}else {
		while ($myEmp = pg_fetch_array ($empRslt)) {
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$totloan = sprint($myEmp['loaninstall']*$myEmp['loanperiod']);
			$totout= sprint($myEmp['loanamt']);

			$employees .= "
						<tr bgcolor='$bgColor'>
							<td>$myEmp[sname], $myEmp[fnames] ($myEmp[enum])</td>
							<td align='right'>".CUR." $totloan</td>
							<td align='right'>".CUR." $totout</td>
							<td align='right'>".CUR." $myEmp[loaninstall]</td>
							<td align='right'>$myEmp[loanint] %</td>
							<td align='right'>$myEmp[loanperiod] months</td>
							<td><a href='loan-edit.php?empnum=$myEmp[empnum]'>Edit</a></td>
						</tr>\n";
			$i++;
		}
	}
########################################################################

	$display = "
			<h2>Summary Of Present Loans</h2>
			<table ".TMPL_tblDflts.">
				<input type='hidden' name='key' value='input'>
				<tr>
					<th>Employee</th>
					<th>Loan amount(incl interest)</th>
					<th>Amount outstanding</th>
					<th>Monthly installment</th>
					<th>Loan interest</th>
					<th>Payback period</th>
					<th colspan='2'>Options</th>
				</tr>
				$employees
			</table>
			<br><br>
			<h2>Current Loan Applications</h2>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Employee</th>
					<th>Loan Amount</th>
					<th>Installments</th>
					<th>Loan Interest Rate</th>
					<th>Loan Period</th>
					<th>Loan Date</th>
					<th>Loan Type</th>
					<th colspan='2'>Options</th>
				</tr>
				$listing
			</table><br>"
			.mkQuickLinks(
				ql("loan_apply.php", "New Loan Application")
			);
	return $display;


}


?>
