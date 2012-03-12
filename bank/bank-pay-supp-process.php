<?

require ("../settings.php");
require ("../core-settings.php");
require ("bank-pay-supp-write.php");

if(isset($_POST["key"])){
	switch ($_POST["key"]){
		case "search":
			$OUTPUT = show_entries ($_POST);
			break;
		case "confirm":
			$OUTPUT = process_entries ($_POST);
			break;
		default:
			$OUTPUT = get_filter ();
	}
}elseif (isset($_GET["supid"])) {
	$OUTPUT = print_entry ($_GET);
}else {
	$OUTPUT = get_filter ();
}

require ("../template.php");




function get_filter ()
{


	$display = "
		<h2>Select Date Range For Listing</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='search'>
			<tr>
				<th colspan='2'>Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
					".mkDateSelect("from",date("Y"),date("m"),"01")."
					To
					".mkDateSelect("to")."
				</td>
				<td><input type='submit' value='View'></td>
			</tr>
		</form>
		</table>";
	return $display;

}



function show_entries ($_POST,$err="")
{

	extract ($_POST);

	$fromdate = "$from_year-$from_month-$from_day";
	$todate = "$to_year-$to_month-$to_day";

	db_connect ();

	#get list of entries ... sort by supplier ...
	$get_list = "SELECT * FROM supp_payment_cheques WHERE date >= '$fromdate' AND date <= '$todate' AND done = 'no' ORDER BY supname";
	$run_list = db_exec($get_list) or errDie ("Unable to get list of processed payments.");
	if(pg_numrows($run_list) < 1){
		$listing = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='6'>No Entries Found.</td>
			</tr>";
	}else {
		$listing = "";
		while ($carr = pg_fetch_array ($run_list)){

			$pay_type = getCSetting("SUPP_PAY_TYPE");

			if (!isset($pay_type) OR strlen ($pay_type) < 1){
				$showprintbutton = "";
				$showprintcheck = "";
			}else {
				if (($pay_type == "cheq_man") OR ($pay_type == "cheq_aut")){
					$showprintcheck = "<input type='checkbox' name='print_entry[]' value='$carr[id]'>";
					$showprintbutton = "<a target='_blank' href='bank-pay-supp-process.php?supid=$carr[supid]&do=$carr[id]'>Print/Save</a>";
				}else {
					$showprintcheck = "";
					$showprintbutton = "";
				}
			}

			$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$carr[supname]</td>
					<td>$carr[date]</td>
					<td>".CUR." ".sprint ($carr['amt'])."</td>
					<td>$showprintbutton</td>
					<td>$showprintcheck</td>
					<td><input type='checkbox' name='process_entry[]' value='$carr[id]'></td>
				</tr>";
		}
		$listing .= "
			".TBL_BR."
			<tr>
				<td colspan='4'></td>
				<td><input type='submit' name='print_submit' value='Print Selected'></td>
				<td><input type='submit' name='process_submit' value='Process Selected'></td>
			</tr>";
	}


	$display = "
		<h2>Listing</h2>
		<table ".TMPL_tblDflts.">
		$err
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='from_year' value='$from_year'>
			<input type='hidden' name='from_month' value='$from_month'>
			<input type='hidden' name='from_day' value='$from_day'>
			<input type='hidden' name='to_year' value='$to_year'>
			<input type='hidden' name='to_month' value='$to_month'>
			<input type='hidden' name='to_day' value='$to_day'>
			<tr>
				<th>Supplier Name</th>
				<th>Date</th>
				<th>Amount</th>
				<th>Reprint</th>
				<th>Print</th>
				<th>Process (Handed Over)</th>
			</tr>
			$listing
		</form>
		</table>";
	return $display;

}


function print_entry ($_POST)
{

	extract ($_POST);

	db_connect ();

	$pay_type = getCSetting("SUPP_PAY_TYPE");
	if (!isset($pay_type) OR strlen ($pay_type) < 1)
		$pay_type = "cheq_man";

	#get payment information
	$get_pay = "SELECT supname,cheqnum,amt,date FROM supp_payment_cheques WHERE id = '$do' LIMIT 1";
	$run_pay = db_exec($get_pay) or errDie ("Unable to get payment information.");
	if (pg_numrows($run_pay) < 1){
		$cheqnum = "Unknown Cheque Number";
		$supname = "Unknown Supplier Number";
		$date = date ("Y-m-d");
		$amt = sprint (0);
	}else {
		$parr = pg_fetch_array ($run_pay);
		$supname = $parr['supname'];
		$cheqnum = $parr['cheqnum'];
		$amt = $parr['amt'];
		$date = $parr['date'];
	}

	if ($pay_type == "cheq_auto"){
	
		#print the cheque ...
		$OUTPUT = "
			<table ".TMPL_tblDflts." width='60%'>
				<tr>
					<td width='40%'></td>
					<td>$cheqnum</td>
				</tr>
				<tr>
					<td width='40%'>$supname</td>
					<td>$date</td>
				</tr>
				<tr>
					<td></td>
					<td>".CUR." ".sprint ($amt)."</td>
				</tr
			</table>";
		require ("../tmpl-print.php");

	}else {

		#show the details for the cheque ...
		$OUTPUT = "
			<table ".TMPL_tblDflts." width='60%'>
				<tr>
					<td width='40%'>$supname</td>
					<td>$date</td>
				</tr>
				<tr>
					<td>$amt</td>
				</tr>
			</table>";
		require ("../tmpl-print.php");
	}
//elseif ($do_expo){
//
//		$filedata = "test";
//
//		#output the file ...
//		header ('Content-Type: application/octet-stream');
//		header ('Content-Length: ' . strlen($filedata));
//		header ("Content-Disposition: attachment; filename=\"pay_".date("Y-m-d").".txt\"");
//		print $filedata;
//	}
	return $display;

}



function process_entries ($_POST)
{

	extract ($_POST);

	if (isset($print_submit)){
		#we want to print ... go there ..
		return print_entries ($_POST);
	}

	#if nothing is set, go back
	if(!isset($process_entry) OR !is_array ($process_entry)){
		return show_entries ($_POST,"<li class='err'>Please select at least 1 entry to process.</li><br>");
	}

	$pay_type = getCSetting("SUPP_PAY_TYPE");
	if (!isset($pay_type) OR strlen ($pay_type) < 1)
		$pay_type = "cheq_man";

	#we gonna process the entries ...
	if ($pay_type != "export")
		foreach ($process_entry AS $each => $own){
			#get + process this entry ...

			db_connect ();

			$get_entry = "SELECT * FROM supp_payment_cheques WHERE id = '$own' LIMIT 1";
			$run_entry = db_exec($get_entry) or errDie ("Unable to get payment information.");
			if(pg_numrows($run_entry) < 1){
				return show_entries ($_POST,"<li class='err'>Payment information could not be found.</li>");
			}

			$parr = pg_fetch_array ($run_entry);

			pglib_transaction ("BEGIN") or errDie ("Unable to begin transaction.");

				#handle the vars
				$invids = explode ("|",$parr['invids']);
				$null_invids = array_pop ($invids);

				$paidamt = explode ("|",$parr['paidamt']);
				$null_paidamt = array_pop ($paidamt);

				$stock_setamt = explode ("|",$parr['stock_setamt']);
				$null_stock_setamt = array_pop ($stock_setamt);

				$parr['invids'] = $invids;
				$parr['paidamt'] = $paidamt;
				$parr['stock_setamt'] = $stock_setamt;

				#process the payment ...
				write ($parr);

				#update the payment information
				$upd_sql = "UPDATE supp_payment_cheques SET done = 'yes' WHERE id = '$own'";
				$run_upd = db_exec($upd_sql) or errDie ("Unable to update payment information.");

			pglib_transaction("COMMIT") or errDie ("Unable to complate transaction.");

		}

if ($pay_type == "export"){
	if (!isset($confirmed)){
		$listing = "";
		$counter = 0;
		if (!isset($first))
			$first = "";
		if (!isset($second))
			$second = "";
		if (!isset($third))
			$third = "";

		$send_process = "";
		foreach ($process_entry AS $each){

			$get_det = "SELECT supid,amt FROM supp_payment_cheques WHERE id = '$each' LIMIT 1";
			$run_det = db_exec($get_det) or errDie ("Unable to get payment information.");
			if (pg_numrows($run_det) < 1){
				return "Invalid Use Of Module.";
			}
			$supid = pg_fetch_result($run_det,0,0);
			$supamt = pg_fetch_result($run_det,0,1);
			
			#get supplier info
			$get_sup = "SELECT supno,supname,brancode,bankaccno,bankacctype FROM suppliers WHERE supid = '$supid' LIMIT 1";
			$run_sup = db_exec($get_sup) or errDie ("Unable to get supplier information.");
			if (pg_numrows($run_sup) < 1){
				continue;
			}
			$suparr = pg_fetch_array ($run_sup);
			
			
			#get last payment for this employee
			$salpaidamt = $supamt;
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
				$branch_val[$counter] = str_pad($suparr['brancode'],6,"0","PAD_RIGHT");
			}
			if (!isset($empno_val[$counter])){
				$empno_val[$counter] = str_pad($suparr['supno'],7,"0","PAD_RIGHT");
			}
			if (!isset($bankacc_val[$counter])){
				$bankacc_val[$counter] = str_pad($suparr['bankaccno'],19,"0","PAD_RIGHT");
			}
			if (!isset($second_val[$counter])){
				if ($suparr['bankacctype'] == "Current or Cheque")
					$second_val[$counter] = "1";
				else 
					$second_val[$counter] = "2";
			}
			if (!isset($paidamt_val[$counter])){
				$paidamt_val[$counter] = $salpaidamt;
			}
			if (!isset($name_val[$counter])){
				$name_val[$counter] = strtoupper ($suparr['supname']);
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
			$send_process .= "<input type='hidden' name='process_entry[]' value='$each'>\n";
		}
	

		if (!isset($header))
			$header = getCSetting("EMP_PAYMENT_HEADER");
		if (!isset($footer))
			$footer = getCSetting("EMP_PAYMENT_FOOTER");

		if (!isset($header) OR strlen($header) < 1)
			$header = "";
		if (!isset($footer) OR strlen($footer) < 1)
			$footer = "";


		$display = "
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='from_year' value='$from_year'>
				<input type='hidden' name='from_month' value='$from_month'>
				<input type='hidden' name='from_day' value='$from_day'>
				<input type='hidden' name='to_year' value='$to_year'>
				<input type='hidden' name='to_month' value='$to_month'>
				<input type='hidden' name='to_day' value='$to_day'>
				$send_process
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
				<tr bgcolor='".bgcolorg()."'>
					<td>Header</td>
					<td><input type='text' size='60' name='header' value='$header'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Footer</td>
					<td><input type='text' size='60' name='footer' value='$footer'></td>
				</tr>
				<tr>
					<td></td>
					<td><input type='submit' value='Update'></td>
				</tr>
				".TBL_BR."
			</table>
			<table ".TMPL_tblDflts." width='90%'>
				$listing
				<tr>
					<td><input type='submit' name='confirmed' value='Confirm'></td>
				</tr>
			</table>
			</form>";
		return $display;
	}elseif (!isset($written)) {
		$counter = 0;
		$listing = $header."\n";
		$send_process = "";
		foreach ($process_entry AS $each){

			$get_payment = "SELECT * FROM supp_payment_cheques WHERE id = '$each' LIMIT 1";
			$run_payment = db_exec($get_payment) or errDie ("Unable to get payment information.");
			if (pg_numrows($run_payment) < 1){
				#payment not found ?????
			}else {
				$parr = pg_fetch_array ($run_payment);
			}

			#handle the vars
			$invids = explode ("|",$parr['invids']);
			$null_invids = array_pop ($invids);

			$paidamt = explode ("|",$parr['paidamt']);
			$null_paidamt = array_pop ($paidamt);

			$stock_setamt = explode ("|",$parr['stock_setamt']);
			$null_stock_setamt = array_pop ($stock_setamt);

			$parr['invids'] = $invids;
			$parr['paidamt'] = $paidamt;
			$parr['stock_setamt'] = $stock_setamt;

			write ($parr);

			#update the payment information
			$upd_sql = "UPDATE supp_payment_cheques SET done = 'yes' WHERE id = '$each'";
			$run_upd = db_exec($upd_sql) or errDie ("Unable to update payment information.");

			#also record file info
			$ins_sql = "
				INSERT INTO supp_payment_files (
					payment, sdate, first_val, 
					branch_val, empno_val, bankacc_val, 
					second_val, paidamt_val, name_val, 
					third_val
				) VALUES (
					'$each', 'now', '$first_val[$counter]',
					'$branch_val[$counter]', '$empno_val[$counter]', '$bankacc_val[$counter]', 
					'$second_val[$counter]', '$paidamt_val[$counter]', '$name_val[$counter]', 
					'$third_val[$counter]'
				)";
			$run_ins = db_exec($ins_sql) or errDie ("Unable to record payment.");

			#add this entry to file listing ...
			$listing .= "$first_val[$counter] $branch_val[$counter] $empno_val[$counter] $bankacc_val[$counter] $second_val[$counter] $paidamt_val[$counter] $name_val[$counter] $third_val[$counter]\n";

			$send_process .= "<input type='hidden' name='process_entry[]' value='$each'>\n";
			$counter++;
		}
		$listing .= $footer."\n";

		return "
			<form name='form1' action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<input type='hidden' name='from_year' value='$from_year'>
				<input type='hidden' name='from_month' value='$from_month'>
				<input type='hidden' name='from_day' value='$from_day'>
				<input type='hidden' name='to_year' value='$to_year'>
				<input type='hidden' name='to_month' value='$to_month'>
				<input type='hidden' name='to_day' value='$to_day'>
				<input type='hidden' name='confirmed' value='yes'>
				<input type='hidden' name='written' value='yes'>
				<input type='hidden' name='listing' value='$listing'>
				$send_process
				<script>
					document.form1.submit();
				</script>
			</form>";

	}else {
		#output the file
		header("Content-Type: application/octet-stream");
		header("Content-Length: ".strlen($listing));
		header("Content-Transfer-Encoding: binary");
		header("Content-Disposition: attachment; filename=\"bank_file.txt\"");
		print $listing;
	}
}

	return show_entries ($_POST, "<li class='err'>Payment Completed.</li>");

}



function print_entries ($_POST)
{

	extract ($_POST);

	if(!isset($print_entry) OR !is_array ($print_entry)){
		return show_entries ($_POST,"<li class='err'>Please select at least 1 entry to print.</li><br>");
	}

	db_connect ();

	$listing = "";
	foreach ($print_entry AS $each => $own){
		#get this entry
		$get_entry = "SELECT * FROM supp_payment_cheques WHERE id = '$own' LIMIT 1";
		$run_entry = db_exec($get_entry) or errDie ("Unable to get payment information.");
		if(pg_numrows($run_entry) < 1){
			$listing .= "
				<tr>
					<td>Unable to get payment information. ($own)</td>
				</tr>";
		}else {
			$parr = pg_fetch_array ($run_entry);

			#now figure out what to output ...
			$pay_type = getCSetting("SUPP_PAY_TYPE");
			if (!isset($pay_type) OR strlen($pay_type) < 1)
				$pay_type = "cheq_man";

			$do_auto = FALSE;
			$do_manu = FALSE;

			if ($pay_type == "cheq_man"){
				$do_manu = TRUE;
			}else {
				$do_auto = TRUE;
			}

			$supname = $parr['supname'];
			$cheqnum = $parr['cheqnum'];
			$amt = $parr['amt'];
			$date = $parr['date'];

			if ($do_auto){

				$proc_amt = str_pad($amt,8,"0","PAD_LEFT");

				$val1 = substr($proc_amt,0,2);
				$val2 = substr($proc_amt,2,1);
				$val3 = substr($proc_amt,3,1);
				$val4 = substr($proc_amt,4,1);
				$val5 = substr($proc_amt,5,1);
				$val6 = substr($proc_amt,6,1);
				$val7 = substr($proc_amt,7,1);
				$val8 = substr($proc_amt,9,2);

				#lets calculate the stars to show on the cheque
				$star_mils = calc_stars ($val1);
				$star_hundred_thou = calc_stars ($val2);
				$star_ten_thou = calc_stars ($val3);
				$star_thou = calc_stars ($val4);
				$star_hundreds = calc_stars ($val5);
				$star_tens = calc_stars ($val6);
				$star_units = calc_stars ($val7);
				$star_cents = calc_stars ($val8,TRUE);

				#print the cheque ...
				$listing .= "
					<table ".TMPL_tblDflts." width='60%'>
						<tr>
							<td width='40%' colspan='7'>$supname</td>
							<td>$date</td>
						</tr>
						<tr>
							<td width='40'>$star_mils</td>
							<td width='40'>$star_hundred_thou</td>
							<td width='40'>$star_ten_thou</td>
							<td width='40'>$star_thou</td>
							<td width='40'>$star_hundreds</td>
							<td width='40'>$star_tens</td>
							<td width='40'>$star_units</td>
							<td width='40'>$star_cents</td>
						</tr>
						<tr>
							<td colspan='5'></td>
							<td colspan='3' nowrap>".CUR." ".sprint ($amt)."</td>
						</tr>
						".TBL_BR."
						".TBL_BR."
						".TBL_BR."
						".TBL_BR."
						".TBL_BR."
						".TBL_BR."
					</table>";
			}elseif ($do_manu){
				#show the details for the cheque ...
				$listing .= "
					<table ".TMPL_tblDflts." width='60%'>
						<tr>
							<td>$cheqnum</td>
							<td>$supname</td>
							<td>$date</td>
							<td>".CUR." $amt</td>
						</tr>
					</table>";
			}

		}
	}

	$OUTPUT = "
		<table ".TMPL_tblDflts.">
			$listing
		</table>";
	require ("../tmpl-print.php");
//	return $display;

}



function calc_stars ($amount,$cents = FALSE)
{

	if(!isset($amount) OR strlen($amount) < 1 OR ($amount > 100)){
		return "";
	}

	$amount = $amount + 0;

	if ($cents)
		return "$amount";

	if ($amount > 15)
		return $amount;

	switch ($amount){
		case "0":
			$stars = "*****";
			break;
		case "1":
			$stars = "ONE";
			break;
		case "2":
			$stars = "TWO";
			break;
		case "3":
			$stars = "THREE";
			break;
		case "4":
			$stars = "FOUR";
			break;
		case "5":
			$stars = "FIVE";
			break;
		case "6":
			$stars = "SIX";
			break;
		case "7":
			$stars = "SEVEN";
			break;
		case "8":
			$stars = "EIGHT";
			break;
		case "9":
			$stars = "NINE";
			break;
		case "10":
			$stars = "TEN";
			break;
		case "11":
			$stars = "ELEVEN";
			break;
		case "12":
			$stars = "TWELVE";
			break;
		case "13":
			$stars = "THIRTEEN";
			break;
		case "14":
			$stars = "FOURTEEN";
			break;
		case "15":
			$stars = "FIFTEEN";
			break;
		default:
			$stars = "*****";
	}

	return $stars;

}



?>