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
require ("../libs/ext.lib.php");
require ("../pdf-settings.php");

if (!isset($_REQUEST["cusnum"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "cust_statement":
			$OUTPUT = cust_statement();
		case "cusdetailsall":
			$OUTPUT = cusDetailsAll();
			break;
		case "getcusdetailsall":
			$OUTPUT = getcusDetailsAll();
			break;
	}
} else {
	$OUTPUT = statement_settings();
}

require ("../template.php");





function statement_settings()
{

	extract ($_REQUEST);
	
	$fields = array();
	$fields["key"] = "cust_statement";
	$fields["stmnt_type"] = "open";
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");

	extract ($fields, EXTR_SKIP);

	if ($stmnt_type == "open") {
		$open_sel = "checked='checked'";
		$detailed_sel = "";
	} else {
		$open_sel = "";
		$detailed_sel = "checked='checked'";
	}

	$OUTPUT = "
		<center>
		<h3>PDF Debtor Statement Settings</h3>
		<form method='post' action='".SELF."'>
		<input type='hidden' name='key' value='$key' />
		<input type='hidden' name='cusnum' value='$cusnum' />
		<table ".TMPL_tblDflts.">
			<tr class='".bg_class()."'>
				<td colspan='3' align='center'>
					<input type='radio' name='stmnt_type' value='detailed' $detailed_sel>
					Detailed
					<input type='radio' name='stmnt_type' value='open' $open_sel>
					Open Item
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
				<td>&nbsp; <b>To</b> &nbsp;</td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='3' align='center'>
					<input type='submit' value='Display PDF' style='font-weight: bold' />
				</td>
			</tr>
		</table>
		</form>
		</center>";
	return $OUTPUT;

}	


# THIS FUNCTION IS GENERATES FROM SINGLE STATEMENT VIEW PDF.
function cust_statement()
{

	extract ($_REQUEST);
	global $set_mainFont;

	// validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer number.");

	// display errors, if any
	if ($v->isError ()) {
		return $v->genErrors();
	}



	// Did the user request a date range?
	if (isset($from_day) && isset($from_month) && isset($from_year)) {
		$from_date = "$from_year-$from_month-$from_day";
	}
	if (isset($to_day) && isset($to_month) && isset($to_year)) {
		$to_date = "$to_year-$to_month-$to_day";
	}

	$fdate = date("Y")."-".date("m")."-"."01";
	$totout = 0;

	$pdf = &new Cezpdf;
	$pdf->selectFont($set_mainFont);

	// Heading --------------------------------------------------------------
	$heading = array (
		array ('')
	);

	// Customer info ---------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT * FROM customers WHERE cusnum='$cusnum' AND div='".USER_DIV."'";
	$custmnt_rslt = db_exec ($sql) or errDie ("Unable to retrieve customer information from Cubit.");
	if (pg_numrows ($custmnt_rslt) < 1) {
		return "<li class='err'>Invalid Customer Number.</li>";
	}
	$cust_data = pg_fetch_array($custmnt_rslt);

	// Company info ----------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT * FROM compinfo WHERE div='".USER_DIV."'";
	$ciRlst = db_exec($sql) or errDie("Unable to retrieve the company information from Cubit.");
	$compinf = pg_fetch_array($ciRlst);

	$compinfo = array (
		array (COMP_NAME),
		array ("$compinf[addr1]"),
		array ("$compinf[addr2]"),
		array ("$compinf[addr3]"),
		array ("$compinf[addr4]"),
		array (""),
		array ("<b>Tel:</b> $compinf[tel]"),
		array ("<b>Fax:</b> $compinf[fax]"),
		array ("<b>VAT REG:</b> $compinf[vatnum]"),
		array ("<b>COMPANY REG:</b> $compinf[regnum]")
	);

	$info = array();

	/* base for balance brought forward */
	$info[0] = array(
		"Date" => "",
		"Ref no" => "",
		"Details" => "<b>Balance Brought Forward: </b>",
		"Amount" => "",
		"Balance" => ""
	);

	#check for sort ...
	if(isset($sort) AND ($sort == "branch")){
		$sortinga = "ORDER BY branch, date";
		$sorting = "branch,";
	}else {
		$sortinga = "ORDER BY date";
		$sorting = "";
	}

	// Should payments or credit notes be displayed
	$payment_sql = "";
	if ($stmnt_type == "open") {
		$payment_sql = "
		AND type NOT LIKE 'Payment for%'
		AND type NOT LIKE '%Credit Note%for invoice%'";
	}

	// Retrieve statement information
	$sql = "
		SELECT date, invid, type, amount, docref, branch 
			FROM cubit.stmnt
			WHERE cusnum='$cusnum' $payment_sql AND date BETWEEN '$from_date' AND '$to_date'
			ORDER BY date, id ASC";
	$stmnt_rslt = db_exec($sql) or errrDie("Unable to retrieve statement.");

	// Retrieve balance before the 'from date'
	$sql = "
		SELECT sum(amount) 
			FROM cubit.stmnt
			WHERE cusnum='$cusnum' AND date<'$from_date'";
	$balance_rslt = db_exec($sql) or errDie("Unable to retrieve balance.");
	$balance = pg_fetch_result($balance_rslt, 0);
	$pre_balance = sprint ($balance);

//	$oldest_date = mktime(0, 0, 0, date("m"), 1, date("Y"));

	$oldest_date = mktime (0,0,0,$from_month,$from_day-1,$from_year);

	if (pg_numrows ($stmnt_rslt) < 1) {
		$info[] = array("Date"=>"", "Ref no"=>"", "Details"=>"No invoices for this month", "Amount"=>"");
	// Fill the info array
	}else{
		while ($stmnt_data = pg_fetch_array ($stmnt_rslt)) {
			// Deduct payments and credit notes from balances only
			// if this is an open item statement
			if ($stmnt_type == "open" &&
			   ($stmnt_data["type"] == "Invoice" || $stmnt_data["type"] == "Non-Stock Invoice")) {
				$sql = "
					SELECT sum(amount) 
						FROM cubit.stmnt
						WHERE type LIKE 'Payment for % $stmnt_data[invid]' OR type LIKE '%Credit Note%for invoice%$stmnt_data[invid]'";
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
			if (preg_match("/Invoice/", $stmnt_data["type"])) {
				$refnum = "INV";
			} elseif (preg_match("/Credit Note/", $stmnt_data["type"])) {
				$refnum = "CR";
			}else {
				$refnum = "";
			}
			$refnum .= " " . $stmnt_data["invid"];

			$info[] = array(
				"Date" => makewidth(&$pdf, 60, 12, $stmnt_data['date']),
				"Ref no" => makewidth(&$pdf, 70, 12, $refnum),
				//"Proforma Inv no"=>makewidth(&$pdf, 80, 12, $stmnt_data['docref']),
				"Details" => makewidth(&$pdf, 200, 12, "$stmnt_data[type] $stmnt_data[branch]"),
				"Amount" => makewidth(&$pdf, 75, 12, "$cust_data[currency]".sprint($stmnt_data['amount']).""),
				"Balance" => makewidth(&$pdf, 75, 12, "$cust_data[currency]".sprint($balance)."")
			);
		}
	}
	if (isset($from_date) && isset($to_date)) {
		# get overlapping amount
		$sql = "
		SELECT sum(amount) as amount FROM cubit.stmnt
		WHERE cusnum='$cusnum' AND date>'$to_date'";
		$balRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
		$bal = pg_fetch_array ($balRslt);
		$cust_data['balance'] = ($cust_data['balance'] - $bal['amount']);
	}

	/* alter the balance brought forward entry's (info[0]) amount */
	if($cust_data['location'] == 'int')
		$cust_data['balance'] = $cust_data['fbalance'];

	$balbf = ($cust_data['balance'] - $totout);
	$balbf = sprint($balbf);
	$balbf = $pre_balance;

	$info[0]["Date"] = date("d-m-Y", $oldest_date);// - 24*60*60);
	$info[0]["Amount"] = "$cust_data[currency] $balbf";

	$custinfo = array (
		array ("$cust_data[surname]")
	);

	// Add the address to the array
	$custaddr_ar = explode("\n", $cust_data["paddr1"]);
	foreach ($custaddr_ar as $addr) {
		$custinfo[] = array(pdf_lstr("$addr",70));
	}

	$custinfo[] = array("");
	$custinfo[] = array("<b>Account Number:</b> $cust_data[accno]");
	//$custinfo[] = array("<b>Balance Brought Forward: </b>$cust_data[currency]$balbf");


	// Comments --------------------------------------------------------------
	if (isset($comment)) {
		db_conn("cubit");
		$sql = "SELECT comment FROM saved_statement_comments WHERE id='$comment'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve comments from Cubit.");

		$default_comment = base64_decode(pg_fetch_result($rslt, 0));
	} elseif (isset($b64_comments)) {
		$default_comment = base64_decode($b64_comments);
	} else {
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_STMNT_COMMENTS'";
		$cmntRslt = db_exec($sql) or errDie("Unable to retrieve comments from Cubit.");
		$default_comment = base64_decode(pg_fetch_result($cmntRslt, 0));
	}

	$comments = array ();

	$default_comment = wordwrap ($default_comment, 55, "\n");
	$default_comment_ar = explode ("\n", $default_comment);
	$i = 1;
	foreach ($default_comment_ar as $val) {
		if ($i == 4) {
			$comments[] = array (pdf_lstr($val, 55));
			break;
		} else {
			$comments[] = array ($val);
		}
		$i++;
	}

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

 	$banking = array (
 		array ("$bank_data[bankname]"),
 		array ("<b>Branch: </b>$bank_data[branchname]"),
  		array ("<b>Branch Code: </b>$bank_data[branchcode]"),
 		array ("<b>Account Number: </b>$bank_data[accnum]"),
 	);

 	#get customer total from stmnt
 	$get_stmnt = "SELECT sum(amount) FROM stmnt WHERE cusnum = '$cust_data[cusnum]'";
 	$run_stmnt = db_exec($get_stmnt) or errDie ("Unable to get customer balance information.");
 	if (pg_numrows($run_stmnt) < 1){
 		$totbalance = 0;
 	}else {
 		$totbalance = pg_fetch_result ($run_stmnt,0,0);
 	}
 
	// Totals ----------------------------------------------------------------
	$totals = array (
		array (""),
		array ("<b>Total Outstanding Balance</b> : $cust_data[currency] ".sprint($totbalance)),
	);

	// Age analysis ----------------------------------------------------------
	if($cust_data['location'] == 'int')
		$cust_data['balance'] = $cust_data['fbalance'];

	$balbf = ($cust_data['balance'] - $totout);
	$balbf = sprint($balbf);
	$cust_data['balance'] = sprint($cust_data['balance']);

	if (!isset($to_day))
		$to_day = date ("d");
	if (!isset($to_month))
		$to_month = date("m");
	if (!isset($to_year))
		$to_year = date("Y");

	$month = $to_month;
	$nowdate = "$to_year-$to_month-$to_day";
//	$from_date = date ("Y-m-d",mktime (0,0,0,date("m"),"01",date("Y")));
	$from_date = "$from_year-$from_month-$from_day";

	# Check type of age analisys
	if(div_isset("DEBT_AGE", "mon")){
		$curr = ageage($cust_data['cusnum'], 0, $cust_data['fcid'], $cust_data['location']);
		$age30 = ageage($cust_data['cusnum'], 1, $cust_data['fcid'], $cust_data['location']);
		$age60 = ageage($cust_data['cusnum'], 2, $cust_data['fcid'], $cust_data['location']);
		$age90 = ageage($cust_data['cusnum'], 3, $cust_data['fcid'], $cust_data['location']);
		$age120 = ageage($cust_data['cusnum'], 4, $cust_data['fcid'], $cust_data['location']);
	}else{
		$curr = cust_age($cust_data['cusnum'], 29, $cust_data['fcid'], $cust_data['location'], $month, $nowdate, $from_date);
		$age30 = cust_age($cust_data['cusnum'], 59, $cust_data['fcid'], $cust_data['location'], $month, $nowdate, $from_date);
		$age60 = cust_age($cust_data['cusnum'], 89, $cust_data['fcid'], $cust_data['location'], $month, $nowdate, $from_date);
		$age90 = cust_age($cust_data['cusnum'], 119, $cust_data['fcid'], $cust_data['location'], $month, $nowdate, $from_date);
		$age120 = cust_age($cust_data['cusnum'], 149, $cust_data['fcid'], $cust_data['location'], $month, $nowdate, $from_date);
	}

	$custtot = ($curr + $age30 + $age60 + $age90 + $age120);

//	if(sprint($custtot) != sprint($cust_data['balance'])) {
//		$curr = sprint($curr + $cust_data['balance'] - $custtot);
//		$custtot = sprint($cust_data['balance']);
//	}

	$age = array (
		array (
			"Current" => "<b>Current</b>",
			"30 Days" => "<b>30 Days:</b>",
			"60 Days" => "<b>60 Days</b>",
			"90 Days" => "<b>90 Days</b>",
			"120 Days" => "<b>120 Days</b>"
		),
		array (
			"Current" => $curr,
			"30 Days" => $age30,
			"60 Days" => $age60,
			"90 Days" => $age90,
			"120 Days" => $age120
		)
	);

	// Table layout ----------------------------------------------------------
	$ic = 0;
	while ( ++$ic * 25 < count($info) );

	// Draw the pages, determine by the amount of items how many pages
	// if items > 25 start a new page
	$info_print = Array ();
	for ($i = 0; $i < $ic; $i++) {
		if ( $i ) $pdf->ezNewPage();

		if (isset($from_date) && isset($to_date)) {
			$date = "$from_date to $to_date";
			$dalign_x = 130;
		} else {
			$date = date("d-m-Y");
			$dalign_x = 105;
		}

		// Heading
		$heading_pos = drawTable(&$pdf, $heading, 0, 0, 520, 5);
		drawText(&$pdf, "<b>Page ".($i + 1)."</b>", 8, ($heading_pos['x']/2)-8, 10);
		drawText(&$pdf, "<b>$compinf[compname]</b>", 18, 8, ($heading_pos['y']/2)+6);
		drawText(&$pdf, "<b>Statement</b>", 18, $heading_pos['x']-120, ($heading_pos['y']/2));
		drawText(&$pdf, $date, 10, $heading_pos['x']-$dalign_x, ($heading_pos['y']/2)+18);

		$custinfo_pos = drawTable(&$pdf, $custinfo, 0, ($heading_pos['y'] + 5), 300, 10);
		$compinfo_pos = drawTable(&$pdf, $compinfo, $custinfo_pos['x'], ($heading_pos['y'] + 5), 220, 10);

		$info_start = ($i * 25);

		if ($i) $info_start++;

		if ($info_start >= count($info) - 25) {
			$info_end = count($info) - 1;
		} else {
			$info_end = ($i + 1) * 25;
		}
		$info_print = Array();

		for ($j = $info_start; $j <= $info_end; $j++) {
			$info_print[$j] = $info[$j];
		}

		// Adjust the column widths
		$cols = array (
			"Date" => array ("width" => 60),
			"Proforma Inv no" => array ("width" => 70),
			"Ref no" => array ("width" => 80),
			"Amount" => array ("width" => 75, "justification"=>"right"),
			"Balance" => array ("width" => 75, "justification"=>"right")
		);

		$info_pos = drawTable(&$pdf, $info_print, 0, ($custinfo_pos['y'] + 5), 520, 25, $cols, 1);
		$comments_pos = drawTable(&$pdf, $comments, 0, ($info_pos['y'] + 5), 260, 4);
		$banking_pos = drawTable(&$pdf, $banking, $comments_pos['x'], ($info_pos['y'] + 5), 260, 4);
		$totals_pos = drawTable(&$pdf, $totals, 0, ($comments_pos['y'] + 5), 520, 3);
		$age_pos = drawTable(&$pdf, $age, 0, $totals_pos['y'], 520, 2);
		drawText(&$pdf, "<b>Cubit Accounting</b>", 6, 0, $age_pos['y'] + 20);
	}

	$pdf->ezStream();
}


function getcusDetailsAll ()
{

	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["filt_class"] = "";
	$fields["filt_cat"] = "";
	extract($fields, EXTR_SKIP);

	db_conn ("exten");

	
	$get_class = "SELECT * FROM class ORDER BY classname";
	$run_class = db_exec($get_class) or errDie ("Unable to get class information.");

	$class_drop = "<select name='filt_class'>";
	$class_drop .= "<option value='0'>All Classifications</option>";
	if (pg_numrows($run_class) > 0){
		while ($clarr = pg_fetch_array ($run_class)){
			if ($filt_class == $clarr['clasid']){
				$class_drop .= "<option value='$clarr[clasid]' selected>$clarr[classname]</option>";
			}else {
				$class_drop .= "<option value='$clarr[clasid]'>$clarr[classname]</option>";
			}
		}
	}
	$class_drop .= "</select>";


	$get_cat = "SELECT * FROM categories ORDER BY category";
	$run_cat = db_exec($get_cat) or errDie ("Unable to get category information.");

	$cat_drop = "<select name='filt_cat'>";
	$cat_drop .= "<option value='0'>All Categories</option>";
	if (pg_numrows ($run_cat) > 0){
		while ($carr = pg_fetch_array ($run_cat)){
			if ($filt_cat == $carr['catid']){
				$cat_drop .= "<option value='$carr[catid]' selected>$carr[category]</option>";
			}else {
				$cat_drop .= "<option value='$carr[catid]'>$carr[category]</option>";
			}
		}
	}
	$cat_drop .= "</select>";

	$display = "
		<h2>Select PDF Report Type</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='GET'>
			<input type='hidden' name='key' value='cusdetailsall'>
			<tr><th colspan='2'>Date Range</th></td>
			<tr class='".bg_class()."'>
				<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			</tr>
			<tr>
				<th colspan='2'>Statement Type</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' name='stmnt_type' value='detailed' checked />Detailed</td>
				<td><input type='radio' name='stmnt_type' value='open' />Open Item</td>
			</tr>
			<tr>
				<th colspan='2'>Select</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><input type='radio' name='report_type' value='all' checked='yes'> All Customers</td>
				<td><input type='radio' name='report_type' value='bal'> Only Customers With Balances</td>
			</tr>
			".TBL_BR."
			<tr>
				<th colspan='2'>Select Filter</th>
			</tr>
			<tr>
				<th>Select Class</th>
				<th>Select Category</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$class_drop</td>
				<td>$cat_drop</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' value='Next'>
			</tr>
		</form>
		</table>";
	return $display;

}


# THIS FUNCTION IS GENERATED FROM DEBTORS STATEMENT PDF MENU ... GENERATES PDF LISTING
function cusDetailsAll()
{

	extract ($_REQUEST);
	global $set_mainFont;

	$fields = array();
	$fields["stmnt_type"] = "detailed";

	extract ($fields, EXTR_SKIP);

	if(!isset($report_type))
		$report_type = "all";

	switch ($report_type){
		case "all":
			$search = "";
			break;
		case "bal":
			$search = "balance != '0.00' AND ";
			break;
		default:
			$search = "true";
	}

	$search2 = "";
	if (isset($filt_class) AND (strlen($filt_class) > 0) AND $filt_class != "0"){
		$search2 .= " class = '$filt_class' AND ";
	}
	if (isset($filt_cat) AND (strlen($filt_cat) > 0) AND $filt_cat != "0"){
		$search2 .= " category = '$filt_cat' AND ";
	}

	$from_date = dateFmt($from_year, $from_month, $from_day);
	$to_date = dateFmt($to_year, $to_month, $to_day);
	$fdate = $from_date;

	$pdf = &new Cezpdf;
	$pdf->selectFont($set_mainFont);

	// Heading --------------------------------------------------------------
	$heading = array (
		array ('')
	);

	#check for sort ...
	if(isset($sort) AND $sort == "branch"){
		$sortinga = "ORDER BY branch";
		$sorting = "branch,";
	}else {
		$sortinga = "";
		$sorting = "";
	}


	// Customer info ---------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT * FROM customers WHERE $search $search2 div='".USER_DIV."' ORDER BY surname";
	$custmnt_rslt = db_exec ($sql) or errDie ("Unable to retrieve customer information from Cubit.");
	if (pg_numrows ($custmnt_rslt) < 1) {
		return "<li class='err'>No Customers Found Matching Criteria.</li>";
	}

	while ($cust_data = pg_fetch_array($custmnt_rslt)) {
		$totout = 0;
		$cusnum = $cust_data["cusnum"];

		// Company info ----------------------------------------------------------
		db_conn("cubit");
		$sql = "SELECT * FROM compinfo WHERE div='".USER_DIV."'";
		$ciRlst = db_exec($sql) or errDie("Unable to retrieve the company information from Cubit.");
		$compinf = pg_fetch_array($ciRlst);

		$compinfo = array (
			array (COMP_NAME),
			array ("$compinf[addr1]"),
			array ("$compinf[addr2]"),
			array ("$compinf[addr3]"),
			array ("$compinf[addr4]"),
			array (""),
			array ("<b>Tel:</b> $compinf[tel]"),
			array ("<b>Fax:</b> $compinf[fax]"),
			array ("<b>VAT REG:</b> $compinf[vatnum]"),
			array ("<b>COMPANY REG:</b> $compinf[regnum]")
		);

		$info = array();

		/* base for balance brought forward */
		$info[0] = array(
			"Date" => "",
			"Ref no" => "",
			"Details" => "<b>Balance Brought Forward: </b>",
			"Amount" => "",
			"Balance" => ""
		);

		#check for sort ...
		if(isset($sort) AND ($sort == "branch")){
			$sortinga = "ORDER BY branch, date";
			$sorting = "branch,";
		}else {
			$sortinga = "ORDER BY date";
			$sorting = "";
		}

		// Should payments or credit notes be displayed
		$payment_sql = "";
		if ($stmnt_type == "open") {
			$payment_sql = "
			AND type NOT LIKE 'Payment for%'
			AND type NOT LIKE '%Credit Note%for invoice%'";
		}

		// Retrieve statement information
		$sql = "
		SELECT date, invid, type, amount, docref, branch FROM cubit.stmnt
		WHERE cusnum='$cusnum' $payment_sql AND
			date BETWEEN '$from_date' AND '$to_date'
		ORDER BY date, id ASC";
		$stmnt_rslt = db_exec($sql) or errrDie("Unable to retrieve statement.");

		// Retrieve balance before the 'from date'
		$sql = "
		SELECT sum(amount) FROM cubit.stmnt
		WHERE cusnum='$cusnum' AND date < '$from_date'";
		$balance_rslt = db_exec($sql) or errDie("Unable to retrieve balance.");
		$balance = pg_fetch_result($balance_rslt, 0);

//		$oldest_date = mktime(0, 0, 0, date("m"), 1, date("Y"));
		$oldest_date = mktime (0,0,0,$from_month,$from_day-1,$from_year);

		if (pg_numrows ($stmnt_rslt) < 1) {
			$info[] = array("Date"=>"", "Ref no"=>"", "Details"=>"No invoices for this month", "Amount"=>"");
		// Fill the info array
		}else{
			while ($stmnt_data = pg_fetch_array ($stmnt_rslt)) {
				// Deduct payments and credit notes from balances only
				// if this is an open item statement
				if ($stmnt_type == "open" &&
				   ($stmnt_data["type"] == "Invoice" || $stmnt_data["type"] == "Non-Stock Invoice")) {
					$sql = "
					SELECT sum(amount) FROM cubit.stmnt
					WHERE type LIKE 'Payment for % $stmnt_data[invid]'
						OR type LIKE '%Credit Note%for invoice%$stmnt_data[invid]'";
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
				if (preg_match("/Invoice/", $stmnt_data["type"])) {
					$refnum = "INV";
				} elseif (preg_match("/Credit Note/", $stmnt_data["type"])) {
					$refnum = "CR";
				}else {
					$refnum = "";
				}
				$refnum .= " " . $stmnt_data["invid"];

				$info[] = array(
					"Date" => makewidth(&$pdf, 60, 12, $stmnt_data['date']),
					"Ref no" => makewidth(&$pdf, 70, 12, $refnum),
					//"Proforma Inv no" => makewidth(&$pdf, 80, 12, $stmnt_data['docref']),
					"Details" => makewidth(&$pdf, 200, 12, "$stmnt_data[type] $stmnt_data[branch]"),
					"Amount" => makewidth(&$pdf, 75, 12, "$cust_data[currency]$stmnt_data[amount]"),
					"Balance" => makewidth(&$pdf, 75, 12, "$cust_data[currency]".sprint($balance)."")
				);
			}
		}
		if (isset($from_date) && isset($to_date)) {
			# get overlapping amount
//			$sql = "
//			SELECT sum(amount) as amount FROM cubit.stmnt
//			WHERE cusnum='$cusnum' AND date>'$to_date'";
//			$balRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
//			$bal = pg_fetch_array ($balRslt);

			$get_bal = "SELECT sum(amount) FROM stmnt WHERE cusnum = '$cust_data[cusnum]'";
			$run_bal = db_exec($get_bal) or errDie ("Unable to get customer balance.");
			if (pg_numrows($run_bal) < 1){
				$cust_data['balance'] = sprint (0);
			}else {
				$cust_data['balance'] = sprint(pg_fetch_result ($run_bal,0,0));
			}

			$get_bal = "SELECT sum(amount) FROM stmnt WHERE cusnum = '$cust_data[cusnum]' AND date>'$from_date'";
			$run_bal = db_exec($get_bal) or errDie ("Unable to get customer balance.");
			if (pg_numrows($run_bal) < 1){
				$bal['amount'] = sprint (0);
			}else {
				$bal['amount'] = sprint(pg_fetch_result ($run_bal,0,0));
			}

			$cust_data['balance'] = ($cust_data['balance'] - $bal['amount']);
		}

		/* alter the balance brought forward entry's (info[0]) amount */
		if($cust_data['location'] == 'int')
			$cust_data['balance'] = $cust_data['fbalance'];

		$balbf = ($cust_data['balance'] - $totout);
		$balbf = sprint($balbf);

//		$balbf = "test";
		$info[0]["Date"] = date("d-m-Y", $oldest_date);// - 24*60*60);
		$info[0]["Amount"] = "$cust_data[currency]$balbf";

		$custinfo = array (
			array ("$cust_data[surname]")
		);

		// Add the address to the array
		$custaddr_ar = explode("\n", $cust_data["paddr1"]);
		foreach ($custaddr_ar as $addr) {
			$custinfo[] = array(pdf_lstr("$addr",70));
		}

		$custinfo[] = array("");
		$custinfo[] = array("<b>Account Number:</b> $cust_data[accno]");
		//$custinfo[] = array("<b>Balance Brought Forward: </b>$cust_data[currency]$balbf");


		// Comments --------------------------------------------------------------
		if (isset($comment)) {
			db_conn("cubit");
			$sql = "SELECT comment FROM saved_statement_comments WHERE id='$comment'";
			$rslt = db_exec($sql) or errDie("Unable to retrieve comments from Cubit.");

			$default_comment = base64_decode(pg_fetch_result($rslt, 0));
		} elseif (isset($b64_comments)) {
			$default_comment = base64_decode($b64_comments);
		} else {
			db_conn("cubit");
			$sql = "SELECT value FROM settings WHERE constant='DEFAULT_STMNT_COMMENTS'";
			$cmntRslt = db_exec($sql) or errDie("Unable to retrieve comments from Cubit.");
			$default_comment = base64_decode(pg_fetch_result($cmntRslt, 0));
		}

		$comments = array ();

		$default_comment = wordwrap ($default_comment, 55, "\n");
		$default_comment_ar = explode ("\n", $default_comment);
		$i = 1;
		foreach ($default_comment_ar as $val) {
			if ($i == 4) {
				$comments[] = array (pdf_lstr($val, 55));
				break;
			} else {
				$comments[] = array ($val);
			}
			$i++;
		}

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

		$banking = array (
			array ("$bank_data[bankname]"),
			array ("<b>Branch: </b>$bank_data[branchname]"),
			array ("<b>Branch Code: </b>$bank_data[branchcode]"),
			array ("<b>Account Number: </b>$bank_data[accnum]"),
		);

		$get_bal = "SELECT sum(amount) FROM stmnt WHERE cusnum = '$cust_data[cusnum]'";
		$run_bal = db_exec($get_bal) or errDie ("Unable to get customer balance.");
		if (pg_numrows($run_bal) < 1){
			$cust_data['balance'] = sprint (0);
		}else {
			$cust_data['balance'] = sprint(pg_fetch_result ($run_bal,0,0));
		}

		// Totals ----------------------------------------------------------------
		$totals = array (
			array (""),
			array ("<b>Total Outstanding Balance</b> : $cust_data[currency] ".sprint($cust_data["balance"])),
		);

		// Age analysis ----------------------------------------------------------
		if($cust_data['location'] == 'int')
			$cust_data['balance'] = $cust_data['fbalance'];

		$balbf = ($cust_data['balance'] - $totout);
		$balbf = sprint($balbf);

		$cust_data['balance'] = sprint($cust_data['balance']);
//		$from_date = date ("Y-m-d",mktime (0,0,0,date("m"),"01",date("Y")));
		$from_date = "$from_year-$from_month-$from_day";

		# Check type of age analisys
		if(div_isset("DEBT_AGE", "mon")){
			$curr = ageage($cust_data['cusnum'], 0, $cust_data['fcid'], $cust_data['location']);
			$age30 = ageage($cust_data['cusnum'], 1, $cust_data['fcid'], $cust_data['location']);
			$age60 = ageage($cust_data['cusnum'], 2, $cust_data['fcid'], $cust_data['location']);
			$age90 = ageage($cust_data['cusnum'], 3, $cust_data['fcid'], $cust_data['location']);
			$age120 = ageage($cust_data['cusnum'], 4, $cust_data['fcid'], $cust_data['location']);
		}else{
			$curr = cust_age($cust_data['cusnum'], 29, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
			$age30 = cust_age($cust_data['cusnum'], 59, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
			$age60 = cust_age($cust_data['cusnum'], 89, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
			$age90 = cust_age($cust_data['cusnum'], 119, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
			$age120 = cust_age($cust_data['cusnum'], 149, $cust_data['fcid'], $cust_data['location'], $to_month, $to_date, $from_date);
		}

		$custtot = ($curr + $age30 + $age60 + $age90 + $age120);

//		if(sprint($custtot) != sprint($cust_data['balance'])) {
//			$curr = sprint($curr + $cust_data['balance'] - $custtot);
//			$custtot = sprint($cust_data['balance']);
//		}

		$age = array (
			array (
				"Current" => "<b>Current</b>",
				"30 Days" => "<b>30 Days:</b>",
				"60 Days" => "<b>60 Days</b>",
				"90 Days" => "<b>90 Days</b>",
				"120 Days" => "<b>120 Days</b>"
			),
			array (
				"Current" => $curr,
				"30 Days" => $age30,
				"60 Days" => $age60,
				"90 Days" => $age90,
				"120 Days" => $age120
			)
		);

		// Table layout ----------------------------------------------------------
		$ic = 0;
		while ( ++$ic * 25 < count($info) );

		// Draw the pages, determine by the amount of items how many pages
		// if items > 25 start a new page
		$info_print = Array ();
		for ($i = 0; $i < $ic; $i++) {
			if ( $i ) $pdf->ezNewPage();

			if (isset($from_date) && isset($to_date)) {
				$date = "$from_date to $to_date";
				$dalign_x = 130;
			} else {
				$date = date("d-m-Y");
				$dalign_x = 105;
			}

			// Heading
			$heading_pos = drawTable(&$pdf, $heading, 0, 0, 520, 5);
			drawText(&$pdf, "<b>Page ".($i + 1)."</b>", 8, ($heading_pos['x']/2)-8, 10);
			drawText(&$pdf, "<b>$compinf[compname]</b>", 18, 8, ($heading_pos['y']/2)+6);
			drawText(&$pdf, "<b>Statement</b>", 18, $heading_pos['x']-120, ($heading_pos['y']/2));
			drawText(&$pdf, $date, 10, $heading_pos['x']-$dalign_x, ($heading_pos['y']/2)+18);

			$custinfo_pos = drawTable(&$pdf, $custinfo, 0, ($heading_pos['y'] + 5), 300, 10);
			$compinfo_pos = drawTable(&$pdf, $compinfo, $custinfo_pos['x'], ($heading_pos['y'] + 5), 220, 10);

			$info_start = ($i * 25);

			if ($i) $info_start++;

			if ($info_start >= count($info) - 25) {
				$info_end = count($info) - 1;
			} else {
				$info_end = ($i + 1) * 25;
			}
			$info_print = Array();

			for ($j = $info_start; $j <= $info_end; $j++) {
				$info_print[$j] = $info[$j];
			}

			// Adjust the column widths
			$cols = array (
				"Date" => array ("width" => 60),
				"Proforma Inv no" => array ("width" => 70),
				"Ref no" => array ("width" => 80),
				"Amount" => array ("width" => 75, "justification"=>"right"),
				"Balance" => array ("width" => 75, "justification"=>"right")
			);

			$info_pos = drawTable(&$pdf, $info_print, 0, ($custinfo_pos['y'] + 5), 520, 25, $cols, 1);
			$comments_pos = drawTable(&$pdf, $comments, 0, ($info_pos['y'] + 5), 260, 4);
			$banking_pos = drawTable(&$pdf, $banking, $comments_pos['x'], ($info_pos['y'] + 5), 260, 4);
			$totals_pos = drawTable(&$pdf, $totals, 0, ($comments_pos['y'] + 5), 520, 3);
			$age_pos = drawTable(&$pdf, $age, 0, $totals_pos['y'], 520, 2);
			drawText(&$pdf, "<b>Cubit Accounting</b>", 6, 0, $age_pos['y'] + 20);
		}

		$pdf->ezNewPage();
	}

	$pdf->ezStream();
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
