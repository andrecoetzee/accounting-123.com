<?

require ("settings.php");

if (isset($_POST["confirm"])){
	$OUTPUT = write_data_output ($_POST);
}else {
	$OUTPUT = get_data_output ($_POST);
}

require ("template.php");




function get_data_output ($_POST)
{

	extract ($_POST);

	db_connect ();

	$listing = "";
	$counter = 0;
	if (!isset($first))
		$first = "";
	if (!isset($second))
		$second = "";
	if (!isset($third))
		$third = "";

	#check what we have permission to
	$get_perm = "SELECT payroll_groups FROM users WHERE username = '$_SESSION[USER_NAME]' LIMIT 1";
	$run_perm = db_exec ($get_perm) or errDie ("Unable to get payroll groups permission information.");
	if (pg_numrows ($run_perm) > 0){
		$parr = pg_fetch_array ($run_perm);
		if (strlen ($parr['payroll_groups']) > 0){
			$pay_grps = explode (",",$parr['payroll_groups']);
			if (is_array ($pay_grps)){
				$egsearch = " AND (emp_group = '".implode ("' OR emp_group = '",$pay_grps)."')";
			}
		}else {
			$egsearch = "AND false";
		}
	}

	$sql = "SELECT empnum FROM employees WHERE div = '".USER_DIV."' $egsearch ORDER BY sname";
	$empRslt = db_exec ($sql) or errDie ("Unable to select employees from database.");
	if (pg_numrows ($empRslt) > 0){
		$empsarr = array ();
		while ($arr = pg_fetch_array ($empRslt)){
			$empsarr[] = "$arr[empnum]";
		}
	}else {
		$empsarr = array ();
	}

	$get_listing = "SELECT distinct(empnum) FROM salpaid";
	$run_listing = db_exec($get_listing) or errDie ("Unable to get employee info.");
	if (pg_numrows($run_listing)  < 1){
		return "<li class='err'> No Employees With Paid Salaries Found.</li>";
	}else {
		while ($arr = pg_fetch_array ($run_listing)){

			if (!in_array ($arr['empnum'],$empsarr)) 
				continue;

			#get employee info
			$get_emp = "SELECT enum,sname,fnames,bankcode,bankaccno,bankacctype FROM employees WHERE empnum = '$arr[empnum]' LIMIT 1";
			$run_emp = db_exec($get_emp) or errDie ("Unable to get employee information.");
			if (pg_numrows($run_emp) < 1){
				continue;
		//		return "<li class='err'>Unable to get employee information.</li>";
			}
			$emparr = pg_fetch_array ($run_emp);
			
			
			#get last payment for this employee
			$get_emppay = "SELECT salary FROM salpaid WHERE empnum = '$arr[empnum]' ORDER BY id DESC LIMIT 1";
			$run_emppay = db_exec($get_emppay) or errDie ("Unable to get employees payment information.");
			if (pg_numrows($run_emppay) < 1){
				return "<li class='err'>No employee salary payment information.</li>";
			}
			$salpaidamt = pg_fetch_result ($run_emppay,0,0);
			$salpaidamt = str_pad(str_replace (".","",sprint ($salpaidamt)),11,"0",'PAD_RIGHT');

			#if any override vals are set ... override ...
			if (isset($first) AND (strlen($first) > 0) AND (strlen ($first) < 6))
				$first_val[$counter] = $first;
			if (isset($second) AND (strlen($second) > 0) AND (strlen ($second) < 2))
				$second_val[$counter] = $second;
			if (isset($third) AND (strlen($third) > 0) AND (strlen ($third) < 7))
				$third_val[$counter] = $third;

			if (!isset($first_val[$counter])){
				$first_val[$counter] = "";
			}
			if (!isset($branch_val[$counter])){
				$branch_val[$counter] = str_pad($emparr['bankcode'],6,"0","PAD_RIGHT");
			}
			if (!isset($empno_val[$counter])){
				$empno_val[$counter] = str_pad($emparr['enum'],7,"0","PAD_RIGHT");
			}
			if (!isset($bankacc_val[$counter])){
				$bankacc_val[$counter] = str_pad($emparr['bankaccno'],19,"0","PAD_RIGHT");
			}
			if (!isset($second_val[$counter])){
				if ($emparr['bankacctype'] == "Current or Cheque")
					$second_val[$counter] = "1";
				else 
					$second_val[$counter] = "2";
			}
			if (!isset($paidamt_val[$counter])){
				$paidamt_val[$counter] = $salpaidamt;
			}
			if (!isset($name_val[$counter])){
				$name_val[$counter] = strtoupper ($emparr['sname'])." ".strtoupper(substr($emparr['fnames'],0,1));
			}
			if (!isset($third_val[$counter])){
				$third_val[$counter] = "";
			}


			$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><input type='text' size='5' maxlength='5' name='first_val[$counter]' value='$first_val[$counter]'></td>
					<td><input type='text' size='6' maxlength='6' name='branch_val[$counter]' value='$branch_val[$counter]'></td>
					<td><input type='text' size='7' maxlength='7' name='empno_val[$counter]' value='$empno_val[$counter]'></td>
					<td><input type='text' size='20' maxlength='19' name='bankacc_val[$counter]' value='$bankacc_val[$counter]'></td>
					<td width='5%'></td>
					<td><input type='text' size='1' maxlength='1' name='second_val[$counter]' value='$second_val[$counter]'></td>
					<td><input type='text' size='11' maxlength='11' name='paidamt_val[$counter]' value='$paidamt_val[$counter]'></td>
					<td><input type='text' size='50' maxlength='50' name='name_val[$counter]' value='$name_val[$counter]'></td>
					<td width='5%'></td>
					<td><input type='text' size='7' maxlength='6' name='third_val[$counter]' value='$third_val[$counter]'></td>
				</tr>";
			$counter++;
		}
	}

	$order1 = "
		<select name='ord1'>
			<option value='counter' selected>Counter</option>
			<option value='bankbranch'>Bank Branch</option>
			<option value='empnum'>Employee Number</option>
			<option value='bankacc'>Bank Account</option>
			<option value='secondchar'>Bank Account Character</option>
			<option value='paidamount'>Paid Amount</option>
			<option value='name'>Employee Name</option>
			<option value='lastchar'>Last Character</option>
		</select>";
	$order2 = "
		<select name='ord2'>
			<option value='counter'>Counter</option>
			<option value='bankbranch' selected>Bank Branch</option>
			<option value='empnum'>Employee Number</option>
			<option value='bankacc'>Bank Account</option>
			<option value='secondchar'>Bank Account Character</option>
			<option value='paidamount'>Paid Amount</option>
			<option value='name'>Employee Name</option>
			<option value='lastchar'>Last Character</option>
		</select>";
	$order3 = "
		<select name='ord3'>
			<option value='counter'>Counter</option>
			<option value='bankbranch'>Bank Branch</option>
			<option value='empnum' selected>Employee Number</option>
			<option value='bankacc'>Bank Account</option>
			<option value='secondchar'>Bank Account Character</option>
			<option value='paidamount'>Paid Amount</option>
			<option value='name'>Employee Name</option>
			<option value='lastchar'>Last Character</option>
		</select>";
	$order4 = "
		<select name='ord4'>
			<option value='counter'>Counter</option>
			<option value='bankbranch'>Bank Branch</option>
			<option value='empnum'>Employee Number</option>
			<option value='bankacc' selected>Bank Account</option>
			<option value='secondchar'>Bank Account Character</option>
			<option value='paidamount'>Paid Amount</option>
			<option value='name'>Employee Name</option>
			<option value='lastchar'>Last Character</option>
		</select>";
	$order5 = "
		<select name='ord5'>
			<option value='counter'>Counter</option>
			<option value='bankbranch'>Bank Branch</option>
			<option value='empnum'>Employee Number</option>
			<option value='bankacc'>Bank Account</option>
			<option value='secondchar' selected>Bank Account Character</option>
			<option value='paidamount'>Paid Amount</option>
			<option value='name'>Employee Name</option>
			<option value='lastchar'>Last Character</option>
		</select>";
	$order6 = "
		<select name='ord6'>
			<option value='counter'>Counter</option>
			<option value='bankbranch'>Bank Branch</option>
			<option value='empnum'>Employee Number</option>
			<option value='bankacc'>Bank Account</option>
			<option value='secondchar'>Bank Account Character</option>
			<option value='paidamount' selected>Paid Amount</option>
			<option value='name'>Employee Name</option>
			<option value='lastchar'>Last Character</option>
		</select>";
	$order7 = "
		<select name='ord7'>
			<option value='counter'>Counter</option>
			<option value='bankbranch'>Bank Branch</option>
			<option value='empnum'>Employee Number</option>
			<option value='bankacc'>Bank Account</option>
			<option value='secondchar'>Bank Account Character</option>
			<option value='paidamount'>Paid Amount</option>
			<option value='name' selected>Employee Name</option>
			<option value='lastchar'>Last Character</option>
		</select>";
	$order8 = "
		<select name='ord8'>
			<option value='counter'>Counter</option>
			<option value='bankbranch'>Bank Branch</option>
			<option value='empnum'>Employee Number</option>
			<option value='bankacc'>Bank Account</option>
			<option value='secondchar'>Bank Account Character</option>
			<option value='paidamount'>Paid Amount</option>
			<option value='name'>Employee Name</option>
			<option value='lastchar' selected>Last Character</option>
		</select>";

	$display = "
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Universal Setting</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>First 5 Chars</td>
				<td><input type='text' name='first' value='$first' size='5' maxlength='5'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Bank Account Character</td>
				<td><input type='text' name='second' value='$second' size='1' maxlength='1'></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Last Characters</td>
				<td><input type='text' name='third' value='$third' size='7' maxlength='6'</td>
			</tr>
			<tr>
				<td></td>
				<td><input type='submit' value='Update'></td>
			</tr>
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Define Output Order (Left To Right For CSV File)</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$order1</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$order2</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$order3</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$order4</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$order5</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$order6</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$order7</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$order8</td>
			</tr>
		</table>
		<br>
		<table ".TMPL_tblDflts." width='90%'>
			<tr>
				<th>Counter</th>
				<th>Bank Branch</th>
				<th>Employee Number</th>
				<th>Bank Account</th>
				<th></th>
				<th>Bank Account Character</th>
				<th>Paid Amount</th>
				<th>Employee Name</th>
				<th></th>
				<th>Last Character</th>
			</tr>
			$listing
			<tr>
				<td><input type='submit' name='confirm' value='Confirm'></td>
			</tr>
		</table>
		</form>";
	return $display;

}





function write_data_output ($_POST)
{

	extract ($_POST);
	
	$data_val = "";
	foreach ($first_val AS $counter => $own){


		switch ($ord1){
			default:
			case "counter":
				$display1 = "$own";
				break;
			case "bankbranch":
				$display1 = "$branch_val[$counter]";
				break;
			case "empnum":
				$display1 = "$empno_val[$counter]";
				break;
			case "bankacc":
				$display1 = "$bankacc_val[$counter]";
				break;
			case "secondchar":
				$display1 = "$second_val[$counter]";
				break;
			case "paidamount":
				$display1 = "$paidamt_val[$counter]";
				break;
			case "name":
				$display1 = str_pad(trim($name_val[$counter]),50," ");
				break;
			case "lastchar":
				$display1 = "$third_val[$counter]";
				break;
		}

		switch ($ord2){
			case "counter":
				$display2 = "$own";
				break;
			default:
			case "bankbranch":
				$display2 = "$branch_val[$counter]";
				break;
			case "empnum":
				$display2 = "$empno_val[$counter]";
				break;
			case "bankacc":
				$display2 = "$bankacc_val[$counter]";
				break;
			case "secondchar":
				$display2 = "$second_val[$counter]";
				break;
			case "paidamount":
				$display2 = "$paidamt_val[$counter]";
				break;
			case "name":
				$display2 = str_pad(trim($name_val[$counter]),50," ");
				break;
			case "lastchar":
				$display2 = "$third_val[$counter]";
				break;
		}

		switch ($ord3){
			case "counter":
				$display3 = "$own";
				break;
			case "bankbranch":
				$display3 = "$branch_val[$counter]";
				break;
			default:
			case "empnum":
				$display3 = "$empno_val[$counter]";
				break;
			case "bankacc":
				$display3 = "$bankacc_val[$counter]";
				break;
			case "secondchar":
				$display3 = "$second_val[$counter]";
				break;
			case "paidamount":
				$display3 = "$paidamt_val[$counter]";
				break;
			case "name":
				$display3 = str_pad(trim($name_val[$counter]),50," ");
				break;
			case "lastchar":
				$display3 = "$third_val[$counter]";
				break;
		}

		switch ($ord4){
			case "counter":
				$display4 = "$own";
				break;
			case "bankbranch":
				$display4 = "$branch_val[$counter]";
				break;
			case "empnum":
				$display4 = "$empno_val[$counter]";
				break;
			default:
			case "bankacc":
				$display4 = "$bankacc_val[$counter]";
				break;
			case "secondchar":
				$display4 = "$second_val[$counter]";
				break;
			case "paidamount":
				$display4 = "$paidamt_val[$counter]";
				break;
			case "name":
				$display4 = str_pad(trim($name_val[$counter]),50," ");
				break;
			case "lastchar":
				$display4 = "$third_val[$counter]";
				break;
		}

		switch ($ord5){
			case "counter":
				$display5 = "$own";
				break;
			case "bankbranch":
				$display5 = "$branch_val[$counter]";
				break;
			case "empnum":
				$display5 = "$empno_val[$counter]";
				break;
			case "bankacc":
				$display5 = "$bankacc_val[$counter]";
				break;
			default:
			case "secondchar":
				$display5 = "$second_val[$counter]";
				break;
			case "paidamount":
				$display5 = "$paidamt_val[$counter]";
				break;
			case "name":
				$display5 = str_pad(trim($name_val[$counter]),50," ");
				break;
			case "lastchar":
				$display5 = "$third_val[$counter]";
				break;
		}

		switch ($ord6){
			case "counter":
				$display6 = "$own";
				break;
			case "bankbranch":
				$display6 = "$branch_val[$counter]";
				break;
			case "empnum":
				$display6 = "$empno_val[$counter]";
				break;
			case "bankacc":
				$display6 = "$bankacc_val[$counter]";
				break;
			case "secondchar":
				$display6 = "$second_val[$counter]";
				break;
			default:
			case "paidamount":
				$display6 = "$paidamt_val[$counter]";
				break;
			case "name":
				$display6 = str_pad(trim($name_val[$counter]),50," ");
				break;
			case "lastchar":
				$display6 = "$third_val[$counter]";
				break;
		}

		switch ($ord7){
			case "counter":
				$display7 = "$own";
				break;
			case "bankbranch":
				$display7 = "$branch_val[$counter]";
				break;
			case "empnum":
				$display7 = "$empno_val[$counter]";
				break;
			case "bankacc":
				$display7 = "$bankacc_val[$counter]";
				break;
			case "secondchar":
				$display7 = "$second_val[$counter]";
				break;
			case "paidamount":
				$display7 = "$paidamt_val[$counter]";
				break;
			default:
			case "name":
				$display7 = str_pad(trim($name_val[$counter]),50," ");
				break;
			case "lastchar":
				$display7 = "$third_val[$counter]";
				break;
		}

		switch ($ord8){

			case "counter":
				$display8 = "$own";
				break;
			case "bankbranch":
				$display8 = "$branch_val[$counter]";
				break;
			case "empnum":
				$display8 = "$empno_val[$counter]";
				break;
			case "bankacc":
				$display8 = "$bankacc_val[$counter]";
				break;
			case "secondchar":
				$display8 = "$second_val[$counter]";
				break;
			case "paidamount":
				$display8 = "$paidamt_val[$counter]";
				break;
			case "name":
				$display8 = str_pad(trim($name_val[$counter]),50," ");
				break;
			default:
			case "lastchar":
				$display8 = "$third_val[$counter]";
				break;
		}

		$data_val .= "$display1$display2$display3$display4 $display5$display6$display7$display8\n";

//		$data_val .= "$own$branch_val[$counter]$empno_val[$counter]$bankacc_val[$counter] $second_val[$counter]$paidamt_val[$counter]".str_pad(trim($name_val[$counter]),50," ")."$third_val[$counter]\n";
	}

	header('Content-type: text/plain');
	header('Content-Disposition: attachment; filename="Delivery.etc"');
	print $data_val;
	exit;

}

?>