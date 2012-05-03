<?php

require ("settings.php");

if (!isset($_REQUEST["cusnum"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	require ("template.php");
}	

$OUTPUT = print_stmnt();

require ("template.php");




function print_stmnt()
{

	extract ($_REQUEST);

	define ("PAGE_SPLIT", 25);

	$fields = array();
	$fields["cusnum"] = 0;
	$fields["stmnt_type"] = "detailed";
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["comments"] = "[_BLANK_]";
	
	extract ($fields, EXTR_SKIP);
	
	if (isset($b64_comments))  {
		$comments = base64_decode($b64_comments);
	}

	if (!checkdate ($from_month,$from_day,$from_year)) {
		$from_day = date ("d");
		$from_month = date ("m");
		$from_year = date ("Y");
	}

	if (!checkdate ($to_month,$to_day,$to_year)) {
		$to_day = date ("d");
		$to_month = date ("m");
		$to_year = date ("Y");
	}

	// Date Selections Concatenated
	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";



	// Border styles
	$rborder = "style='border-right: 2px solid #000'";
	$bborder = "style='border-bottom: 2px solid #000'";
	$tborder = "style='border-top: 2px solid #000'";
	$thborder = "style='border-right: 2px solid #000; border-bottom: 2px solid #000'";
	$aborder = "style='border-right: 2px solid #000; border-top: 2px solid #000'";
	$br = "<br style='line-height: 2px'>";
	$page_break = "<br style='page-break-after:always;'>";
	
	// Retrieve customer information
	$sql = "
		SELECT  cusnum, accno, surname, balance, paddr1, addr1, fcid, location, bankid 
		FROM cubit.customers
		WHERE cusnum='$cusnum'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer information.");
	$cust_data = pg_fetch_array($cust_rslt);
	
	// Retrieve company information
	$sql = "
		SELECT compname, addr1, addr2, addr3, addr4, tel, fax, vatnum, regnum
		FROM cubit.compinfo";
	$comp_rslt = db_exec($sql) or errDie("Unable to retrieve company information.");
	$comp_data = pg_fetch_array($comp_rslt);

	#handle unset bank information
	if ($cust_data['bankid'] == "0"){
		$get_bid = "SELECT * FROM bankacct LIMIT 1";
		$run_bid = db_exec($get_bid) or errDie ("Unable to get default bank information.");
		if (pg_numrows($run_bid) < 1){
			#no bank accounts in cubit ????
			$bank_data = array ();
			$bank_data['bankname'] = "";
			$bank_data['branchname'] = "";
			$bank_data['branchcode'] = "";
			$bank_data['accnum'] = "";
		}else {
			$cust_data['bankid'] = pg_fetch_result ($run_bid,0,0);
			$bank_data = qryBankAcct($cust_data['bankid']);
		}
	}else {
		$bank_data = qryBankAcct($cust_data['bankid']);
	}

	// Retrieve banking details
//	$bank_data = qryBankAcct(getdSetting("BANK_DET"));



	// Should payments or credit notes be displayed
	$payment_sql = "";
	if ($stmnt_type == "open") {
		$payment_sql = "
		AND type NOT LIKE 'Payment for%'
		AND type NOT LIKE '%Credit Note%for invoice%'
		AND (allocation = '0' OR allocation = '')";
	}

	// Retrieve statement information
	$sql = "
		SELECT id, date, invid, type, amount, docref, refnum FROM cubit.stmnt 
		WHERE cusnum='$cusnum' $payment_sql AND date BETWEEN '$from_date' AND '$to_date' 
		ORDER BY date, allocation_date, invid, allocation ASC";
	$stmnt_rslt = db_exec($sql) or errDie("Unable to retrieve statement.");

	// Retrieve balance before the 'from date'
	$sql = "
		SELECT sum(amount) 
		FROM cubit.stmnt 
		WHERE cusnum='$cusnum' AND date<'$from_date'";

	$balance_rslt = db_exec($sql) or errDie("Unable to retrieve balance.");
	$balance = pg_fetch_result($balance_rslt, 0);

	$stmnt_ar = array();
	$pages = 1;
	$item_count = 0;
	while ($stmnt_data = pg_fetch_array($stmnt_rslt)) {

		// Deduct payments and credit notes from balances only
		// if this is an open item statement
		if ($stmnt_type == "open" &&
		   ($stmnt_data["type"] == "Invoice" || $stmnt_data["type"] == "Non-Stock Invoice")) {
			$sql = "
				SELECT sum(amount) 
				FROM cubit.stmnt
				WHERE 
					type LIKE 'Payment for % $stmnt_data[invid]' OR 
					type LIKE '%Credit Note%for invoice%$stmnt_data[invid]' OR 
					allocation = '$stmnt_data[id]'";
			$payment_rslt = db_exec($sql) or errDie("Unable to retrieve payments.");
			$payment = pg_fetch_result($payment_rslt, 0);

			// If the amount has been paid/credit note'ed in full
			// then no need to display this line
			if ($stmnt_data["amount"] == ($payment * -1)) {
				continue;
			}

			$stmnt_data["amount"] += $payment;
		}

		// Increase the balance
		$balance += $stmnt_data["amount"];

		// What should we prepend the ref num with, either invoice or credit note
		if (preg_match("/Payment/", $stmnt_data["type"])) {
			$refnum = "";
		} elseif (preg_match("/Invoice$/", $stmnt_data["type"])) {
			$refnum = "INV";
		} elseif (preg_match("/Credit Note/", $stmnt_data["type"])) {
			$refnum = "CR";
		}
		if (isset($refnum)) {
			$refnum .= " " . $stmnt_data["invid"];
		} else {
			$refnum = "";
		}

		if (empty($refnum)) {
			$refnum = $stmnt_data["invid"];
		}

		if ($stmnt_type == "open"){
			$show_bal = "";
		}else {
			$show_bal = "<td align='right'>".sprint($balance)."</td>";
		}

		if ($stmnt_data['type'] == "Invoice"){
			db_connect ();
			$get_invid = "SELECT invid FROM invoices WHERE invnum = '$stmnt_data[invid]' LIMIT 1";
			$run_invid = db_exec($get_invid) or errDie ("Unable to get invoice information.");
			if (pg_numrows($run_invid) == 1){
				$stmnt_invid = pg_fetch_result ($run_invid,0,0);
				$showtype = "<font onClick=\"window.open('invoice-reprint.php?invid=$stmnt_invid&type=invreprint','window1','height=600, width=900, scrollbars=yes');\">$stmnt_data[type]</font>";
			}else {
				$showtype = $stmnt_data['type'];
			}
		}elseif ($stmnt_data['type'] == "Non-Stock Invoice"){
			db_connect ();
			$get_invid = "SELECT invid FROM nons_invoices WHERE invnum = '$stmnt_data[invid]' LIMIT 1";
			$run_invid = db_exec($get_invid) or errDie ("Unable to get non stock invoice information.");
			if (pg_numrows($run_invid) == 1){
				$stmnt_invid = pg_fetch_result ($run_invid,0,0);
				$showtype = "<font onClick=\"window.open('nons-invoice-reprint.php?invid=$stmnt_invid&type=nonsreprint','window1','height=600, width=900, scrollbars=yes');\">$stmnt_data[type]</font>";
			}else {
				$showtype = $stmnt_data['type'];
			}
		}else {
			$showtype = "$stmnt_data[type]";
		}

		// Add the line to the current page
		$stmnt_ar[$pages][] = "
			<tr>
				<td align='center' $rborder>
					".date("d-m-Y", strtotime($stmnt_data["date"]))." &nbsp;
				</td>
				<td align='center' $rborder>$refnum &nbsp;</td>
				<td align='center' $rborder>$stmnt_data[docref] &nbsp;</td>
				<td $rborder>$showtype &nbsp;</td>
				<td align='right' $rborder>".sprint($stmnt_data["amount"])." &nbsp;</td>
				$show_bal
			</tr>";

		unset($refnum);

		$item_count++;
		// Time for a new page
		if ($item_count == PAGE_SPLIT) {
			$pages++;
			$item_count = 0;
		}
	}

	if ($stmnt_type == "open"){
		$show_bal_space = "";
		$unmatch = "Unmatched";
	}else {
		$show_bal_space = "<td>&nbsp;</td>";
		$unmatch = "";
	}

	// If there's wasn't one single line returned from the database
	// at the very least make the user aware of this.
	if (count($stmnt_ar) == 0) {
		$stmnt_ar[1][] = "
			<tr>
				<td $rborder>&nbsp;</td>
				<td $rborder>&nbsp;</td>
				<td $rborder>&nbsp;</td>
				<td $rborder align='center'><b>No $unmatch Invoices for this date range.</b></td>
				<td $rborder>&nbsp;</td>
				$show_bal_space
			</tr>";
	}

	// Generate blank lines to fill the the page
	foreach ($stmnt_ar as $page=>$lv2) {
		$blank_lines = PAGE_SPLIT - count($stmnt_ar[$page]);
		for ($i = 0; $i < $blank_lines; $i++) {
			$stmnt_ar[$page][] = "
				<tr>
					<td $rborder>&nbsp;</td>
					<td $rborder>&nbsp;</td>
					<td $rborder>&nbsp;</td>
					<td $rborder>&nbsp;</td>
					<td $rborder>&nbsp;</td>
					$show_bal_space
				</tr>";
		}
	}

	// Decide which radio button should be selected
	if ($stmnt_type == "detailed") {
		$detailed_sel = "checked='checked'";
		$open_sel = "";
	} elseif ($stmnt_type == "open") {
		$detailed_sel = "";
		$open_sel = "checked='checked'";
	}

	// Comments
	if ($comments == "[_BLANK_]") {
		$sql = "
			SELECT value FROM cubit.settings 
			WHERE constant='DEFAULT_STMNT_COMMENTS'";
		$comment_rslt = db_exec($sql) or errDie("Unable to retrieve comments.");
		$comments = base64_decode(pg_fetch_result($comment_rslt, 0));
	}

	// Get age analysis
	if(div_isset("DEBT_AGE", "mon")){
		#shouldnt be used ...
		$curr = ageage($cust_data['cusnum'], 0, $cust_data['fcid'], $cust_data['location']);
		$age30 = ageage($cust_data['cusnum'], 1, $cust_data['fcid'], $cust_data['location']);
		$age60 = ageage($cust_data['cusnum'], 2, $cust_data['fcid'], $cust_data['location']);
		$age90 = ageage($cust_data['cusnum'], 3, $cust_data['fcid'], $cust_data['location']);
		$age120 = ageage($cust_data['cusnum'], 4, $cust_data['fcid'], $cust_data['location']);
	}else{
		#this is the used setting ...
		$curr = cust_age($cust_data['cusnum'], 29, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
		$age30 = cust_age($cust_data['cusnum'], 59, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
		$age60 = cust_age($cust_data['cusnum'], 89, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
		$age90 = cust_age($cust_data['cusnum'], 119, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
		$age120 = cust_age($cust_data['cusnum'], 149, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
	}
	$custtot = ($curr + $age30 + $age60 + $age90 + $age120);

	$OUTPUT = "
		<center>
		<style>
			table { border: 2px solid #000 }
			input, textarea { border: 1px solid #000 }
		</style>";

	// Statement settings, only display when not printing
	if (!isset($key) || $key != "print") {
		$OUTPUT .= "
			<form method='post' action='".SELF."' name='form'>
			<input type='hidden' name='cusnum' value='$cusnum' />
			<table ".TMPL_tblDflts." style='border: 1px solid #000'>
				<tr class='bg-even'>
					<td colspan='3' align='center'>
						<input type='radio' name='stmnt_type' value='detailed'
						onchange='javascript:document.form.submit()' $detailed_sel>
						Detailed
						<input type='radio' name='stmnt_type' value='open'
						onchange='javascript:document.form.submit()' $open_sel>
						Open Item
					</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
					<td align='center'>&nbsp; <b>To</b> &nbsp;</td>
					<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td colspan='3' align='center'><b>Comments</b></td>
				</tr>
				<tr class='".bg_class()."'>
					<td colspan='3'>
						<textarea name='comments' style='width: 100%'>$comments</textarea>
					</td>
				<tr class='".bg_class()."'>
					<td align='center'>
						<input type='button'  value='Print'
						onclick='javascript:popupOpen(\"".SELF."?"
						."key=print&cusnum=$cusnum&stmnt_type=$stmnt_type&"
						."b64_comments=".base64_encode($comments)."&from_year=$from_year"
						."&from_month=$from_month&from_day=$from_day&to_year=$to_year&"
						."to_month=$to_month&to_day=$to_day\");' />
					</td>
					<td><input type='submit' value='Apply' style='font-weight: bold' /></td>
					<td align='center'>
						<input type='button' value='View PDF'
						onclick='javascript:popupOpen(\"pdf/pdf-statement.php?"
						."key=cust_statement&cusnum=$cusnum&stmnt_type=$stmnt_type&"
						."from_year=$from_year&from_month=$from_month&from_day=$from_day&"
						."to_year=$to_year&to_month=$to_month&to_day=$to_day&"
						."b64_comments=".base64_encode($comments)."\");' />
					</td>
				</tr>
			</table>
			$page_break";
	}

	if ($stmnt_type == "open"){
		$show_bal_head = "";
	}else {
		$show_bal_head = "<th width='15%' $bborder>Balance</th>";
	}

	// Actual Statement per page
	for ($i = 1; $i <= $pages; $i++) {
		$stmnt_out = "";

		foreach ($stmnt_ar[$i] as $items_out) {
			$stmnt_out .= $items_out;
		}

		$OUTPUT .= "
		</form>
		<table cellpadding='5' cellspacing='2'' width='90%'>
			<tr>
				<td colspan='2' align='center'><b>Page $i</b></td>
			</tr>
			<tr>
				<td rowspan='2' valign='middle'>
					<h1>".COMP_NAME."</h3>
				</td>
				<td width='10%' align='center'>
					<h1>Statement</h3>
				</td>
			</tr>
			<tr>
				<td align='center' nowrap>
					<h3>
						".date("d-m-Y", strtotime($from_date))." -
						".date("d-m-Y", strtotime($to_date))."
					</h3>
				</td>
			</tr>
		</table>
		
		$br
		
		<table cellpadding='0' cellspacing='0' width='90%'>
			<tr>
				<td valign='top' $rborder>
					$cust_data[surname]<br />
					".nl2br($cust_data["paddr1"])."<br />
					<br />
					<b>Account Number:</b> $cust_data[accno]<br />
				</td>
				
				<td valign='top'>
					$comp_data[compname]<br />
					$comp_data[addr1]<br />
					$comp_data[addr2]<br />
					$comp_data[addr3]<br />
					$comp_data[addr4]<br />
					<br />
					<b>Tel:</b> $comp_data[tel]<br />
					<b>Fax:</b> $comp_data[fax]<br />
					<b>VAT Reg:</b> $comp_data[vatnum]<br />
					<b>Company Reg:</b> $comp_data[regnum]<br />
				</td>
			</tr>
		</table>
		$br
		<table cellpadding='0' cellspacing='0' width='90%'>
			<tr>
				<th width='10%' $thborder>Date</th>
				<th width='10%' $thborder>Ref No.</th>
				<th width='10%' $thborder>Customer Ref No.</th>
				<th width='40%' $thborder>Details</th>
				<th width='15%' $thborder>Amount</th>
				$show_bal_head
			</tr>
			$stmnt_out
		</table>
		$br
		<table cellpadding='0' cellspacing='0' width='90%'>
			<tr>
				<td colspan='5' align='right'>
					&nbsp;
					<br />
					<b>Total Outstanding Balance:</b> ".sprint($balance)."
					<br />
					&nbsp;
				</td>
			</tr>
			<tr>
				<td width='20%' $aborder>
					<b>120+ Days</b><br />
					$age120
				</td>
				<td width='20%' $aborder>
					<b>90 Days</b><br />
					$age90
				</td>
				<td width='20%' $aborder>
					<b>60 Days</b><br />
					$age60
				</td>
				<td width='20%' $aborder>
					<b>30 Days</b><br />
					$age30
				</td>
				<td width='20%' $tborder>
					<b>Current</b><br />
					$curr
				</td>
		</table>

		$br		

		<table cellpadding='0' cellspacing='0' width=90%'>
			<tr>
				<td rowspan='5' $rborder width='50%'>".nl2br($comments)." &nbsp;</td>
			</tr>
			<tr><td>$bank_data[bankname] &nbsp;</td></tr>
			<tr><td><b>Branch:</b> $bank_data[branchname]</td></tr>
			<tr><td><b>Branch Code:</b> $bank_data[branchcode]</td></tr>
			<tr><td><b>Account Number:</b> $bank_data[accnum]</td></tr>
		</table>";
	
		if ($i >= 1) {
			$OUTPUT .= $page_break;
		}
		
	}
	
	$OUTPUT .= "
	</center>";
	require ("tmpl-print.php");

}


function ageage($cusnum, $age, $fcid, $loc)
{

	$rate = getRate($fcid);
	$bal = "balance";
	if($loc == 'int'){
		$bal = "fbalance";
		$rate = 1;
	}
	if($rate == 0) $rate = 1;

	# Get the current oustanding
	$sql = "SELECT sum($bal) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum($bal) FROM custran WHERE cusnum = '$cusnum' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint(($sum['sum'] + $sumb ['sum']) + 0);

}



?>
