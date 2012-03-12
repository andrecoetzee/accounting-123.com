<?

	require ("../settings.php");

	$OUTPUT = show_report ();

	require ("../tmpl-print.php");



function show_report ()
{

	global $_GET;

	if (!isset($_GET["id"]) OR (strlen($_GET["id"]) < 1)){
		return "<li class='err'>Invalid Use Of Module. Invalid Loan.</li>";
	}

	db_connect ();

	$get_loan = "SELECT * FROM emp_loanarchive WHERE id = '$_GET[id]' LIMIT 1";
	$run_loan = db_exec($get_loan) or errDie("Unable to get loan information.");
	if(pg_numrows($run_loan) < 1){
		return "<li class='err'>Could Not Get Loan Information.</li>";
	}else {
		$larr = pg_fetch_array($run_loan);
	}

	#get employee details ...
	$get_emp = "SELECT * FROM employees WHERE empnum = '$larr[empnum]' LIMIT 1";
	$run_emp = db_exec($get_emp) or errDie("Unable to get employee details.");
	if(pg_numrows($run_emp) < 1){
		$showempname = "";
	}else {
		$earr = pg_fetch_array($run_emp);
		$showempname = "$earr[fnames] $earr[sname]";
	}

	#get loan type details ...
	$get_ltype = "SELECT * FROM loan_types WHERE id = '$larr[loan_type]' LIMIT 1";
	$run_ltype = db_exec($get_ltype) or errDie("Unable to get loan type details.");
	if(pg_numrows($run_ltype) < 1){
		$showloantype = "";
	}else {
		$tarr = pg_fetch_array($run_ltype);
		$showloantype = $tarr['loan_type'];
	}

	$ld_mon = extractMonth($larr["loandate"]);
	$ld_year = extractYear($larr["loandate"]);

	$repays = array();
	//$repays[] = "<tr>" . "<td>" . date("F Y") . "</td><td>" . CUR . " $larr[loaninstall]</td></tr>";
	for ($i = 0; $i < $larr['loanperiod'] ; $i++){
		$repays[] = "
		<tr>
			<td>".date("F Y", mktime(0, 0, 0, $ld_mon + $i, 1, $ld_year))."</td>
			<td>".CUR." $larr[loaninstall]</td>
		</tr>";
	}

	$showrepays = "";
	foreach ($repays as $each){
		$showrepays .= "$each";
	}

	$display = "
			<center>
			<table ".TMPL_tblDflts.">
				<tr>
					<td align='center' colspan='2'><font size='4'><b>Loan For Employee: $showempname</b></font></td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td>Loan Issued Date</td>
					<td>$larr[archdate]</td>
				</tr>
				<tr>
					<td>Loan Type</td>
					<td>$showloantype</td>
				</tr>
				<tr>
					<td>Loan Amount</td>
					<td>$showloanamt</td>
				</tr>
				<tr>
					<td>Loan Interest Rate</td>
					<td>$larr[loanint]</td>
				</tr>
				<tr>
					<td>Loan Total</td>
					<td>".CUR." $larr[loanamt]</td>
				</tr>
				<tr>
					<td>Repayment Period</td>
					<td>$larr[loanperiod]</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<th colspan='2'>Repayment Periods</th>
				</tr>
			</table>
			<table ".TMPL_tblDflts." width='20%'>
				$showrepays
			</table>
			</center>
		";
	return $display;


}


?>