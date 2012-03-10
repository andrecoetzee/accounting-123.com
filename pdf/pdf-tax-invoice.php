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
require ("../pdf-settings.php");

//ini_set ("allow_call_time_pass_reference","true");

if (isset($HTTP_GET_VARS['invid']) || isset($HTTP_GET_VARS['noteid']) || isset($HTTP_GET_VARS['invids']) || isset($HTTP_GET_VARS['cusnum'])) {
	switch ($HTTP_GET_VARS['type']) {
		default:
		case "inv":
		case "invreprint":
			$OUTPUT = invDetails($HTTP_GET_VARS);
			break;
		case "cusprintinvoices":
			$OUTPUT = cusPrintInvoices($HTTP_GET_VARS);
			break;
		case "invpaid":
		case "invpaidreprint":
			$OUTPUT = invPaidDetails($HTTP_GET_VARS);
			break;
		case "nons":
		case "nonsreprint":
			$OUTPUT = nonsDetails($HTTP_GET_VARS);
			break;
		case "invnotereprint":
		case "invnote":
			$OUTPUT = invNoteDetails($HTTP_GET_VARS);
			break;
		case "nonsnote":
			$OUTPUT = nonsNoteDetails($HTTP_GET_VARS);
			break;
	}
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

require ("../template.php");



#normal reprint invoice in pdf function
function invDetails($HTTP_GET_VARS)
{

	extract ($HTTP_GET_VARS);
	global $set_mainFont;

	$showvat = TRUE;

	$pdf = &new Cezpdf;
	$pdf->selectFont($set_mainFont);

	// Validate
	require_lib("validate");
	$v = new Validate();
	$v->isOk($invid, "num", 1, 20, "Invalid invoice number.");

	// Any errors?
	if ($v->isError()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		$OUTPUT = $confirm;
		require("../template.php");
	}

	// Invoice info
	db_conn("cubit");

	$sql = "SELECT * FROM invoices WHERE invid='$invid' AND DIV='".USER_DIV."'";
	$invRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
	if (pg_num_rows($invRslt) < 1) {
		return "<li class='err'>Not found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	db_conn("cubit");

	$sql = "SELECT symbol FROM currency WHERE fcid='$inv[fcid]'";
	$curRslt = db_exec($sql) or errDie("Unable to retrieve currency from Cubit.");
	$curr = pg_fetch_result($curRslt, 0);
	if (!$curr) $curr = CUR;

	// Check if stock was selected
	db_conn("cubit");

	$sql = "SELECT stkid FROM inv_items WHERE invid='$invid' AND DIV='".USER_DIV."'";
	$cRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
	if (pg_num_rows($cRslt) < 1) {
		$error = "<li class='err'>Invoice number <b>$invid</b> has no items</li>";
		$OUTPUT = $error;
	}

	// Only needs to be blank, we're manually adding text
	$heading = array ( array("") );

	// Company info ----------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT * FROM compinfo WHERE div='".USER_DIV."'";
	$ciRslt = db_exec($sql) or errDie("Unable to retrieve company info from Cubit.");
	$comp = pg_fetch_array($ciRslt);

	// Banking information ---------------------------------------------------
//	$bnkData = qryBankAcct(getdSetting("BANK_DET"));
	$bnkData = qryBankAcct($inv['bankid']);

	$compinfo = array();
	$compinfo[] = array ($comp["addr1"], $comp["paddr1"]);
	$compinfo[] = array (pdf_lstr($comp["addr2"], 35), pdf_lstr($comp["paddr2"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr3"], 35), pdf_lstr($comp["paddr3"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr4"], 35), "$comp[postcode]");
	$compinfo[] = array ("<b>REG: </b>$comp[regnum]", "<b>$bnkData[bankname]</b>");
	$compinfo[] = array ("<b>VAT REG: </b>$comp[vatnum]", "<b>Branch: </b>$bnkData[branchname]");
	$compinfo[] = array ("<b>Tel:</b> $comp[tel]", "<b>Branch Code: </b>$bnkData[branchcode]");
	$compinfo[] = array ("<b>Fax:</b> $comp[fax]", "<b>Acc Num: </b>$bnkData[accnum]");

	// Date ------------------------------------------------------------------
	$date = array (
		array ("<b>Date</b>"),
		array ($inv['odate'])
	);

	// Document info ---------------------------------------------------------
	db_conn('cubit');
	$Sl = "SELECT * FROM settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	db_conn('cubit');

	$Sl = "SELECT * FROM settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sp = "<b>Sales Person: </b>$inv[salespn]";
	} else {
		$sp = "";
	}

	// Retrieve the customer information -------------------------------------
	db_conn("cubit");
	$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
	$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
	$cusData = pg_fetch_array($cusRslt);

	$docinfo = array (
		array ("<b>Invoice No:</b> $inv[invnum]"),
		array ("<b>Proforma Inv No:</b> $inv[docref]"),
		array ("<b>Sales Order No:</b> $inv[ordno]"),
		array ("<b>Account No: </b>$cusData[accno]"),
		array ("$sp")
	);
	if (isset($salespn)) {
		$docinfo[] = array("<b>Sales Person:</b> $salespn");
	}


	// Customer info ---------------------------------------------------------
	$invoice_to = array(
		array ("")
	);

	$cusinfo = array (
		array ("<b>$inv[surname]</b>")
	);

	$cusaddr = explode("\n", $cusData['addr1']);
	foreach ( $cusaddr as $v ) {
		$cusinfo[] = array(pdf_lstr($v, 40));
	}

//	$cusinfo[] = array("<b>Account no: </b>$cusData[accno]");

	$cuspaddr = array (
		array("<b>Postal Address</b>"),
	);

	$paddr = explode("\n", $cusData["paddr1"]);
	foreach ($paddr as $addr) {
		$cuspaddr[] = array($addr);
	}

	$cusdaddr = array (
		array ("<b>Delivery Address:</b>"),
	);

	if($inv['branch'] == 0){
		$branchname = "Head Office";
		$cusaddr = explode("\n", $cusData['addr1']);
	}else {
		$get_addr = "SELECT * FROM customer_branches WHERE id = '$inv[branch]' LIMIT 1";
		$run_addr = db_exec($get_addr);
		if (pg_numrows($run_addr) < 1) {
			$cusaddr = Array ();
			$branchname = "Head Office";
		} else {
			$barr = pg_fetch_array($run_addr);
			$cusaddr = explode("\n", $barr['branch_descrip']);
			$branchname = $barr['branch_name'];
		}
	}

	$cusdaddr[] = array(pdf_lstr("Branch : $branchname", 30));
	$del_addr = explode("\n", $inv["del_addr"]);
	foreach ($del_addr as $addr ) {
		$cusdaddr[] = array(pdf_lstr($addr, 30));
	}

	// Registration numbers --------------------------------------------------
	$regnos = array (
		array (
			"<b>VAT No:</b>",
			"<b>Order No:</b>",
			"<b>Delivery Date:</b>"
		),
		array (
			"$inv[cusvatno]",
			"$inv[cordno]",
			"$inv[deldate]"

		)
	);

	// Items display ---------------------------------------------------------
	$items = array ();

	db_conn("cubit");
	$sql = "SELECT * FROM inv_items WHERE invid='$invid' AND DIV='".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	$serialcount = 0;
	while ($stkd = pg_fetch_array($stkdRslt)) {
		// Get warehouse
		db_conn("exten");
		$sql = "SELECT * FROM warehouses WHERE whid='$stkd[whid]' AND DIV='".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		// Get stock in this warehouse
		db_conn("cubit");
		$sql = "SELECT * FROM stock WHERE stkid='$stkd[stkid]' AND DIV='".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		$sp = "";

		// Check Tax Excempt
		db_conn("cubit");
		$sql = "SELECT zero FROM vatcodes WHERE id='$stkd[vatcode]'";
		$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		$vatex = pg_fetch_result($zRslt, 0);

		if($vatex == "Yes"){
			$ex = "#";
		} else {
			$ex = "";
		}

		$sql = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$runsql = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		if(pg_numrows($runsql) < 1){
			return "Invalid VAT code entered";
		}

		$vd = pg_fetch_array($runsql);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		// keep track of discounts
		//$disc += $stkd['disc'];
		if ($stkd["account"] > 0) {
			$description = $stkd["description"];
		} else {
			$description = $stk["stkdes"];
		}

		// Remove any new lines from the description
		$ar_desc = explode("\n", $description);
		$description = implode(" ", $ar_desc);

		if (strlen($stkd['serno']) > 0){
			$showser = "\n".$stkd['serno'];
			$serialcount++;
		}else {
			$showser = "";
		}

		$items[] = array(
			"Code" => makewidth($pdf, 75, 12, $stk['stkcod']),
			"Description" => makewidth($pdf, 175, 12, $ex.$description.$showser),
			"Qty" => $stkd['qty'],
			"Unit Price" => $curr.$stkd['unitcost'],
			"Unit Discount" => $curr.$stkd['disc'],
			"Amount" => $curr.$stkd['amt']
		);
	}

	$inv["comm"] = fixparag(&$pdf, 3, 520, 11, $inv["comm"]);
	/*$inv["comm"] = preg_replace("/[\n]/", " ", $inv["comm"]);

	$lines = array();
	$txtleft = $inv["comm"];
	$done = false;
	while (count($lines) < 3 && !$done) {
		$mc = maxwidth(&$pdf, 520, 11, $txtleft);

		// run until end of a word.
		while ($txtleft[$mc - 1] != ' ' && $mc < strlen($txtleft)) ++$mc;

		if ($mc == strlen($txtleft)) {
			$done = true;
		}

		$lines[] = substr($txtleft, 0, $mc);
		$txtleft = substr($txtleft, $mc);
	}

	if (strlen($txtleft) > 0) {
		$lines[2] .= "...";
	}

	$inv["comm"] = preg_replace("/  /", " ", implode("\n", $lines));*/

	// Comment ---------------------------------------------------------------
	$comment = array (
		array ("<i>VAT Exempt Indicator : #</i>"),
		array ($inv["comm"])
	);

	// Box for signature -----------------------------------------------------
	$sign = array (
		array ("<b>Terms:</b> $inv[terms] days"),
		array (''),
		array ("<b>Received in good order by:</b> ____________________"),
		array (''),
		// We aren't using a monospace font, so just a lot of spaces until it is aligned nicely.
		array ("                                      <b>Date:</b> ____________________")
	);

	// Totals ----------------------------------------------------------------

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$totals = array (
		array ("1" => "<b>Subtotal:</b> ", "2" => $curr."$inv[subtot]"),
		array ("1" => "<b>Trade Discount:</b> ", "2" => $curr."$inv[discount]"),
		array ("1" => "<b>Delivery Charge:</b> ", "2" => $curr."$inv[delivery]"),
		array ("1" => "<b>VAT $vat14:</b> ", "2" => $curr."$inv[vat]"),
		array ("1" => "<b>Total Incl VAT:</b> ", "2" => $curr."$inv[total]")
	);
	$totCols = array (
		"1" => array("width" => 90),
		"2" => array("justification" => "right")
	);

	$ic = 0;
	while ( ++$ic * 22 < count($items));

	// Draw the pages, determine by the amount of items how many pages
	// if items > 20 start a new page
	$items_print = Array ();
	for ($i = 0; $i < $ic; $i++) {
		if ( $i ) $pdf->ezNewPage();

		// Page number -------------------------------------------------------
		$pagenr = array (
			array ("<b>Page number</b>"),
			array ($i + 1)
		);

		// Heading
		$heading_pos = drawTable(&$pdf, $heading, 0, 0, 520, 5);
		drawText(&$pdf, "<b>$comp[compname]</b>", 18, 18, ($heading_pos['y']/2)+6);
		drawText(&$pdf, "<b>Tax Invoice</b>", 20, $heading_pos['x']-120, ($heading_pos['y']/2)+9);

		// Should we display reprint on the invoice
		if ($type == "invreprint") {
			drawText(&$pdf, "<b>Reprint</b>", 12, $heading_pos['x']-70, ($heading_pos['y']/2)+22);
		}

		$compinfo_pos = drawTable(&$pdf, $compinfo, 0, $heading_pos['y'], 320, 8);
		$date_pos = drawTable(&$pdf, $date, $compinfo_pos['x'], $heading_pos['y'] , 100, 3);
		$pagenr_pos = drawTable(&$pdf, $pagenr, $date_pos['x'], $heading_pos['y'], 100, 3);
		$docinfo_pos = drawTable(&$pdf, $docinfo, $compinfo_pos['x'], $date_pos['y'], 200, 5);
		$invoice_to_pos = drawTable(&$pdf, $invoice_to, 0, $compinfo_pos['y'], 520, 2);
		drawText(&$pdf, "<b>Tax Invoice to:</b>", 12, (520/2)-45, $invoice_to_pos['y']-7);

		$cusinfo_pos = drawTable(&$pdf, $cusinfo, 0, $invoice_to_pos['y'], 173, 8);
		$cuspaddr_pos = drawTable(&$pdf, $cuspaddr, $cusinfo_pos['x'], $invoice_to_pos['y'], 173, 8);
		$cusdaddr_pos = drawTable(&$pdf, $cusdaddr, $cuspaddr_pos['x'], $invoice_to_pos['y'], 174, 8);
		$regnos_pos = drawTable(&$pdf, $regnos, 0, $cusinfo_pos['y'], 520, 2);

		$items_start = ($i * 22);

		if ($i) $items_start++;

		if ($items_start >= (count($items) - 22)) {
			$items_end = count($items) - 1;
		} else {
			$items_end = ($i + 1) * 22;
		}
		$items_print = array();

		for ($j = $items_start; $j <= $items_end; $j++) {
			$items_print[$j] = $items[$j];
		}

		$cols = array(
			"Code" => array("width" => 80),
			"Description" => array("width" => 172),
			"Qty" => array("width" => 50),
			"Unit Price" => array("width" => 71, "justification" => "right"),
			"Unit Discount" => array("width" => 67, "justification" => "right"),
			"Amount" => array("width" => 80, "justification" => "right")
		);

		$items_pos = drawTable(&$pdf, $items_print, 0, $regnos_pos['y']+2, 520, (22-$serialcount), $cols, 1);
		$comment_pos = drawTable(&$pdf, $comment, 0, $items_pos['y'], 520, 2);
		$sign_pos = drawTable(&$pdf, $sign, 0, $comment_pos['y'], 320, 5);
		$totals_pos = drawTable(&$pdf, $totals, $sign_pos['x'], $comment_pos['y'], 200, 5, $totCols);

		$pdf->addText(20,34,6,'Cubit Accounting');

	}
	$pdf->ezStream();

}



function cusPrintInvoices($HTTP_GET_VARS)
{

	extract ($HTTP_GET_VARS);
	global $set_mainFont;

	$showvat = TRUE;

	$pdf = &new Cezpdf;
	$pdf->selectFont($set_mainFont);

	// 0 == normal 1 == pos
	for ($invtyp = 0; $invtyp < 3; $invtyp++) {
		// Invoice info
//		if (!$invtyp) {
//			db_conn("cubit");
//			$sql = "SELECT * FROM invoices WHERE cusnum='$cusnum' AND div='".USER_DIV."'";
//		} else {
//			db_conn(PRD_DB);
//			$sql = "SELECT * FROM pinvoices WHERE cusnum='$cusnum' AND div='".USER_DIV."'";
//		}


		if ($invtyp == "0") {
			db_conn("cubit");
			$sql = "SELECT * FROM invoices WHERE cusnum='$cusnum' AND div='".USER_DIV."' AND done = 'y' AND printed = 'y'";
		} elseif ($invtyp == "1") {
			db_conn(PRD_DB);
			$sql = "SELECT * FROM pinvoices WHERE cusnum='$cusnum' AND div='".USER_DIV."' AND done = 'y' AND printed = 'y'";
		}else {
			db_conn("cubit");
			$get_cust = "SELECT surname FROM customers WHERE cusnum = '$cusnum' LIMIT 1";
			$run_cust = db_exec($get_cust) or errDie("Unable to get customer information.");
			if(pg_numrows($run_cust) < 1){
				continue;
			}
			$carr = pg_fetch_array($run_cust);

			$sql = "SELECT * FROM nons_invoices WHERE cusnum='$cusnum' OR cusid='$cusnum' AND div='".USER_DIV."' AND done = 'y'";
		}



		$invRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
		if (pg_num_rows($invRslt) < 1) {
			continue;
		}

		while ($inv = pg_fetch_array($invRslt)) {
			if ($invtyp != 1 || $invtyp != 2) {
				$sql = "
				SELECT count(invid) FROM cubit.nons_inv_items WHERE invid='$inv[invid]'";
				$count_rslt = db_exec($sql) or errDie("Unable to retrieve count.");
				$count = pg_fetch_result($count_rslt, 0);
				if ($count == 0) {
					continue;
				}
			}

			db_conn("cubit");
			$sql = "SELECT symbol FROM currency WHERE fcid='$inv[fcid]'";
			$curRslt = db_exec($sql) or errDie("Unable to retrieve currency from Cubit.");
			$curr = pg_fetch_result($curRslt, 0);
			if (!$curr) $curr = CUR;

			$invid = $inv["invid"];

			// Check if stock was selected
			if (!$invtyp) {
				db_conn("cubit");
				$sql = "SELECT stkid FROM inv_items WHERE invid='$invid' AND DIV='".USER_DIV."'";
			} else {
				db_conn(PRD_DB);
				$sql = "SELECT stkid FROM pinv_items WHERE invid='$invid'";
			}
			$cRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
			if (pg_num_rows($cRslt) < 1) {
				$error = "<li class='err'>Invoice number <b>$invid</b> has no items</li>";
				$OUTPUT = $error;
			}

			// Only needs to be blank, we're manually adding text
			$heading = array ( array("") );

			// Company info ----------------------------------------------------------
			db_conn("cubit");
			$sql = "SELECT * FROM compinfo WHERE div='".USER_DIV."'";
			$ciRslt = db_exec($sql) or errDie("Unable to retrieve company info from Cubit.");
			$comp = pg_fetch_array($ciRslt);

			// Banking information ---------------------------------------------------
//			$bnkData = qryBankAcct(getdSetting("BANK_DET"));
			if ($inv['bankid'] == "0"){
				$bnkData = qryBankAcct(getdSetting("BANK_DET"));
			}else {
				$bnkData = qryBankAcct($inv['bankid']);
			}
//print "$invtyp --$inv[bankid]--<br>";

			$compinfo = array();
			$compinfo[] = array ($comp["addr1"], $comp["paddr1"]);
			$compinfo[] = array (pdf_lstr($comp["addr2"], 35), pdf_lstr($comp["paddr2"], 35));
			$compinfo[] = array (pdf_lstr($comp["addr3"], 35), pdf_lstr($comp["paddr3"], 35));
			$compinfo[] = array (pdf_lstr($comp["addr4"], 35), "$comp[postcode]");
			$compinfo[] = array ("<b>REG: </b>$comp[regnum]", "<b>$bnkData[bankname]</b>");
			$compinfo[] = array ("<b>VAT REG: </b>$comp[vatnum]", "<b>Branch: </b>$bnkData[branchname]");
			$compinfo[] = array ("<b>Tel:</b> $comp[tel]", "<b>Branch Code: </b>$bnkData[branchcode]");
			$compinfo[] = array ("<b>Fax:</b> $comp[fax]", "<b>Acc Num: </b>$bnkData[accnum]");

			// Date ------------------------------------------------------------------
			$date = array (
				array ("<b>Date</b>"),
				array ($inv['odate'])
			);
			// Document info ---------------------------------------------------------
			db_conn('cubit');
			$Sl = "SELECT * FROM settings WHERE constant='SALES'";
			$Ri = db_exec($Sl) or errDie("Unable to get settings.");

			$data = pg_fetch_array($Ri);

			db_conn('cubit');

			$Sl = "SELECT * FROM settings WHERE constant='SALES'";
			$Ri = db_exec($Sl) or errDie("Unable to get settings.");

			$data = pg_fetch_array($Ri);

			if($data['value'] == "Yes") {
				$sp = "<b>Sales Person: </b>$inv[salespn]";
			} else {
				$sp = "";
			}


			// Retrieve the customer information -------------------------------------
			db_conn("cubit");
			if($invtyp == "2"){
				$sql = "SELECT * FROM customers WHERE cusname='$inv[cusname]'";
			}else {
				$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
			}
			$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
			$cusData = pg_fetch_array($cusRslt);

			if($invtyp == 1){
				$inv['docref'] = "";
				$inv['branch'] = "";
				$inv['del_addr'] = "";
				$inv['deldate'] = "";
				$inv['ordno'] = "";
				$stkd['account'] = "";
			}

			if($invtyp == 2){
				$inv['ordno'] = "";
				$inv['branch'] = "";
				$inv['del_addr'] = "";
				$stkd['account'] = "";
				$inv['comm'] = "";
				$stkd['account'] = "";
			}

			$docinfo = array (
				array ("<b>Invoice No:</b> $inv[invnum]"),
				array ("<b>Proforma Inv No:</b> $inv[docref]"),
				array ("<b>Sales Order No:</b> $inv[ordno]"),
				array ("<b>Account No: </b>$cusData[accno]"),
				array ("$sp")
			);



			if (isset($salespn)) {
				$docinfo[] = array("<b>Sales Person:</b> $salespn");
			}

			// Customer info ---------------------------------------------------------
			$invoice_to = array(
				array ("")
			);

			if($invtyp == "2"){
				$cusinfo = array (
					array ("<b>$inv[cusname]</b>")
				);
			}elseif ($invtyp == "1"){
				if($inv['cusnum'] > 0){
					$cusinfo = array (
						array ("<b>$cusData[surname]</b>")
					);
				}else {
					$cusinfo = array (
						array ("<b>$inv[cusname]</b>")
					);
				}
				
			}else {
				$cusinfo = array (
					array ("<b>$inv[surname]</b>")
				);
			}

			$cusaddr = explode("\n", $cusData['addr1']);
			foreach ( $cusaddr as $v ) {
				$cusinfo[] = array(pdf_lstr($v, 40));
			}

//			$cusinfo[] = array("<b>Account no: </b>$cusData[accno]");

			$cuspaddr = array (
				array("<b>Postal Address</b>"),
			);

			$paddr = explode("\n", $cusData["paddr1"]);
			foreach ($paddr as $addr) {
				$cuspaddr[] = array($addr);
			}

			$cusdaddr = array (
				array ("<b>Delivery Address:</b>"),
			);

			if($inv['branch'] == 0){
				$branchname = "Head Office";
				$cusaddr = explode("\n", $cusData['addr1']);
			}else {
				$get_addr = "SELECT * FROM customer_branches WHERE id = '$inv[branch]' LIMIT 1";
				$run_addr = db_exec($get_addr);
				if (pg_numrows($run_addr) < 1) {
					$cusaddr = Array ();
					$branchname = "Head Office";
				} else {
					$barr = pg_fetch_array($run_addr);
					$cusaddr = explode("\n", $barr['branch_descrip']);
					$branchname = $barr['branch_name'];
				}
			}

			$cusdaddr[] = array(pdf_lstr("Branch : $branchname", 30));
			$del_addr = explode("\n", $inv["del_addr"]);
			foreach ($del_addr as $addr ) {
				$cusdaddr[] = array(pdf_lstr($addr, 30));
			}

			// Registration numbers --------------------------------------------------
			$regnos = array (
				array (
					"<b>VAT No:</b>",
					"<b>Order No:</b>",
					"<b>Delivery Date:</b>"
				),
				array (
					"$inv[cusvatno]",
					"$inv[cordno]",
					"$inv[deldate]"
				)
			);

			// Items display ---------------------------------------------------------
			$items = array ();

//			if (!$invtyp) {
//				$sql = "SELECT * FROM inv_items WHERE invid='$invid' AND div='".USER_DIV."'";
//			} else {
//				$sql = "SELECT * FROM pinv_items WHERE invid='$invid' AND div='".USER_DIV."'";
//			}

			if ($invtyp == "0") {
				db_conn("cubit");
				$sql = "SELECT * FROM inv_items WHERE invid='$invid' AND div='".USER_DIV."'";
			} elseif ($invtyp == "1") {
				db_conn(PRD_DB);
				$sql = "SELECT * FROM pinv_items WHERE invid='$invid' AND div='".USER_DIV."'";
			}else {
				db_conn("cubit");
				$sql = "SELECT * FROM nons_inv_items WHERE invid='$invid' AND div='".USER_DIV."'";
				$inv['discount'] = sprint (0);
				$inv['delivery'] = sprint (0);
			}


			$stkdRslt = db_exec($sql);

			$fds = 0;
			while ($stkd = pg_fetch_array($stkdRslt)) {

				if(!isset ($stkd['account']))
					$stkd['account'] = "";

				// Get warehouse IF not NON STOCK
				if($invtyp == "2"){
					#do nothing
				}else {
					db_conn("exten");
					$sql = "SELECT * FROM warehouses WHERE whid='$stkd[whid]' AND DIV='".USER_DIV."'";
					$whRslt = db_exec($sql);
					$wh = pg_fetch_array($whRslt);
				}

				// Get stock in this warehouse IF not NON STOCK
				if($invtyp == "2"){
					$stk['stkcod'] = "";
					$stkd['disc'] = sprint (0);
				}else {
					db_conn("cubit");
					$sql = "SELECT * FROM stock WHERE stkid='$stkd[stkid]' AND DIV='".USER_DIV."'";
					$stkRslt = db_exec($sql);
					$stk = pg_fetch_array($stkRslt);
				}

				$sp = "";

				// Check Tax Excempt
				db_conn("cubit");
				if($invtyp == "2"){
					$sql = "SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
				}else {
					$sql = "SELECT zero FROM vatcodes WHERE id='$stkd[vatcode]'";
				}
				$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
				$vatex = pg_fetch_result($zRslt, 0);

				if($vatex == "Yes"){
					$ex = "#";
				} else {
					$ex = "";
				}

				if($invtyp == "2"){
					$sql = "SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
				}else {
					$sql = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
				}
				$runsql = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
				if(pg_numrows($runsql) < 1){
					return "Invalid VAT code entered";
				}

				$vd = pg_fetch_array($runsql);

				if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
					$showvat = FALSE;
				}

				// keep track of discounts
				//$disc += $stkd['disc'];
				if (($stkd["account"] > 0) OR ($invtyp == "2")) {
					$description = $stkd["description"];
				} else {
					$description = $stk["stkdes"];
				}

				// Remove any new lines from the description
				$ar_desc = explode("\n", $description);
				$description = implode(" ", $ar_desc);

				$items[] = array(
					"Code" => makewidth($pdf, 75, 12, $stk['stkcod'])." ",
					"Description" => makewidth($pdf, 175, 12, $ex.$description)." ",
					"Qty" => $stkd['qty']." ",
					"Unit Price" => $curr.$stkd['unitcost']." ",
					"Unit Discount" => $curr.$stkd['disc']." ",
					"Amount" => $curr.$stkd['amt']." "
				);
			}

			// Comment ---------------------------------------------------------------
			$comment = array (
				array ("<i>VAT Exempt Indicator : #</i>"),
				array ($inv["comm"])
			);

			// Box for signature -----------------------------------------------------
			$sign = array (
				array ("<b>Terms:</b> $inv[terms] days"),
				array (''),
				array ("<b>Received in good order by:</b> ____________________"),
				array (''),
				// We aren't using a monospace font, so just a lot of spaces until it is aligned nicely.
				array ("                                      <b>Date:</b> ____________________")
			);

			// Totals ----------------------------------------------------------------

			if (!isset($showvat))
				$showvat = TRUE;

			if($showvat == TRUE){
				$vat14 = AT14;
			}else {
				$vat14 = "";
			}

			$totals = array (
				array ("1"=>"<b>Subtotal:</b> ", "2" => $curr."$inv[subtot]"),
				array ("1"=>"<b>Trade Discount:</b> ", "2" => $curr."$inv[discount]"),
				array ("1"=>"<b>Delivery Charge:</b> ", "2" => $curr."$inv[delivery]"),
				array ("1"=>"<b>VAT $vat14:</b> ", "2" => $curr."$inv[vat]"),
				array ("1"=>"<b>Total Incl VAT:</b> ", "2" => $curr."$inv[total]")
			);
			$totCols = array (
				"1"=>array("width" => 90),
				"2"=>array("justification" => "right")
			);

			$ic = 0;
			while ( ++$ic * 22 < count($items) );

			// Draw the pages, determine by the amount of items how many pages
			// if items > 20 start a new page
			$items_print = Array ();
			for ($i = 0; $i < $ic; $i++) {
				if ( $i ) $pdf->ezNewPage();

				// Page number -------------------------------------------------------
				$pagenr = array (
					array ("<b>Page number</b>"),
					array ($i + 1)
				);

				// Heading
				$heading_pos = drawTable(&$pdf, $heading, 0, 0, 520, 5);
				drawText(&$pdf, "<b>$comp[compname]</b>", 18, 18, ($heading_pos['y']/2)+6);
				drawText(&$pdf, "<b>Tax Invoice</b>", 20, $heading_pos['x']-120, ($heading_pos['y']/2)+9);

				// Should we display reprint on the invoice
				if ($type == "invreprint") {
					drawText(&$pdf, "<b>Reprint</b>", 12, $heading_pos['x']-70, ($heading_pos['y']/2)+22);
				}

				$compinfo_pos = drawTable(&$pdf, $compinfo, 0, $heading_pos['y'], 320, 8);
				$date_pos = drawTable(&$pdf, $date, $compinfo_pos['x'], $heading_pos['y'] , 100, 3);
				$pagenr_pos = drawTable(&$pdf, $pagenr, $date_pos['x'], $heading_pos['y'], 100, 3);
				$docinfo_pos = drawTable(&$pdf, $docinfo, $compinfo_pos['x'], $date_pos['y'], 200, 5);
				$invoice_to_pos = drawTable(&$pdf, $invoice_to, 0, $compinfo_pos['y'], 520, 2);
				drawText(&$pdf, "<b>Tax Invoice to:</b>", 12, (520/2)-45, $invoice_to_pos['y']-7);

				$cusinfo_pos = drawTable(&$pdf, $cusinfo, 0, $invoice_to_pos['y'], 173, 8);
				$cuspaddr_pos = drawTable(&$pdf, $cuspaddr, $cusinfo_pos['x'], $invoice_to_pos['y'], 173, 8);
				$cusdaddr_pos = drawTable(&$pdf, $cusdaddr, $cuspaddr_pos['x'], $invoice_to_pos['y'], 174, 8);
				$regnos_pos = drawTable(&$pdf, $regnos, 0, $cusinfo_pos['y'], 520, 2);

				$items_start = ($i * 22);

				if ($i) $items_start++;

				if ($items_start >= (count($items) - 22)) {
					$items_end = count($items) - 1;
				} else {
					$items_end = ($i + 1) * 22;
				}
				$items_print = array();

				for ($j = $items_start; $j <= $items_end; $j++) {
					$items_print[$j] = $items[$j];
				}

				$cols = array(
					"Code" => array("width"=>80),
					"Description" => array("width"=>180),
					"Qty"=>array("width" => 33),
					"Unit Price"=>array("width" => 80, "justification" => "right"),
					"Unit Discount"=>array("width" => 67, "justification" => "right"),
					"Amount"=>array("width" => 80, "justification" => "right")
				);

				$items_pos = drawTable(&$pdf, $items_print, 0, $regnos_pos['y']+2, 520, 22, $cols, 1);
				$comment_pos = drawTable(&$pdf, $comment, 0, $items_pos['y'], 520, 2);
				$sign_pos = drawTable(&$pdf, $sign, 0, $comment_pos['y'], 320, 5);
				$totals_pos = drawTable(&$pdf, $totals, $sign_pos['x'], $comment_pos['y'], 200, 5, $totCols);
				$pdf->addText(20,34,6,'Cubit Accounting');
			}
			$pdf->ezNewPage();
		}
	}
	$pdf->ezStream();

}

function invPaidDetails($HTTP_GET_VARS)
{

	extract ($HTTP_GET_VARS);
	global $set_mainFont;

	$showvat = TRUE;

	$pdf = &new Cezpdf;
	$pdf->selectFont($set_mainFont);

	// Validate
	require_lib("validate");
	$v = new Validate();
	$v->isOk($invid, "num", 1, 20, "Invalid invoice number.");

	// Any errors?
	if ($v->isError()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		$OUTPUT = $confirm;
		require("../template.php");
	}

	// Invoice info
	db_conn($prd);
	$sql = "SELECT * FROM invoices WHERE invid='$invid' AND DIV='".USER_DIV."'";
	$invRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
	if (pg_num_rows($invRslt) < 1) {
		return "<li class='err'>Not found</li>";
	}
	$inv = pg_fetch_array($invRslt);
	db_conn("cubit");

	$sql = "SELECT symbol FROM currency WHERE fcid='$inv[fcid]'";
	$curRslt = db_exec($sql) or errDie("Unable to retrieve currency from Cubit.");
	$curr = pg_fetch_result($curRslt, 0);
	if (!$curr) $curr = CUR;

	// Check if stock was selected
	db_conn($prd);

	$sql = "SELECT stkid FROM inv_items WHERE invid='$invid' AND DIV='".USER_DIV."'";
	$cRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
	if (pg_num_rows($cRslt) < 1) {
		$error = "<li class='err'>Invoice number <b>$invid</b> has no items</li>";
		$OUTPUT = $error;
	}

	// Only needs to be blank, we're manually adding text
	$heading = array ( array("") );

	// Company info ----------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT * FROM compinfo WHERE div='".USER_DIV."'";
	$ciRslt = db_exec($sql) or errDie("Unable to retrieve company info from Cubit.");
	$comp = pg_fetch_array($ciRslt);

	// Banking information ---------------------------------------------------
	$bnkData = qryBankAcct(getdSetting("BANK_DET"));
//	$bnkData = qryBankAcct($inv['bankid']);

	$compinfo = array();
	$compinfo[] = array (pdf_lstr($comp["addr1"], 35), pdf_lstr($comp["paddr1"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr2"], 35), pdf_lstr($comp["paddr2"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr3"], 35), pdf_lstr($comp["paddr3"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr4"], 35), "$comp[postcode]");
	$compinfo[] = array ("<b>REG: </b>$comp[regnum]", "<b>$bnkData[bankname]</b>");
	$compinfo[] = array ("<b>VAT REG: </b>$comp[vatnum]", "<b>Branch: </b>$bnkData[branchname]");
	$compinfo[] = array ("<b>Tel:</b> $comp[tel]", "<b>Branch Code: </b>$bnkData[branchcode]");
	$compinfo[] = array ("<b>Fax:</b> $comp[fax]", "<b>Acc Num: </b>$bnkData[accnum]");

	// Date ------------------------------------------------------------------
	$date = array (
		array ("<b>Date</b>"),
		array ($inv['odate'])
	);

	// Document info ---------------------------------------------------------
	db_conn('cubit');
	$Sl = "SELECT * FROM settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sp = "<b>Sales Person: </b>$inv[salespn]";
	} else {
		$sp = "";
	}

	// Retrieve the customer information -------------------------------------
	db_conn("cubit");
	$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
	$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
	$cusData = pg_fetch_array($cusRslt);

	$docinfo = array (
		array ("<b>Invoice No:</b> $inv[invnum]"),
		array ("<b>Proforma Inv No:</b> $inv[docref]"),
		array ("<b>Sales Order No:</b> $inv[ordno]"),
		array ("<b>Account No: </b>$cusData[accno]"),
		array ("$sp"),
	);
	if (isset($salespn)) {
		$docinfo[] = array("<b>Sales Person:</b> $salespn");
	}

	// Customer info ---------------------------------------------------------
	$invoice_to = array(
		array ("")
	);

	$cusinfo = array (
		array ("<b>$inv[surname]</b>")
	);

	$cusaddr = explode("\n", $cusData['addr1']);
	foreach ( $cusaddr as $v ) {
		$cusinfo[] = array(pdf_lstr($v, 40));
	}

//	$cusinfo[] = array("<b>Account no: </b>$cusData[accno]");

	$cuspaddr = array (
		array("<b>Postal Address</b>"),
	);

	$paddr = explode("\n", $cusData["paddr1"]);
	foreach ($paddr as $addr) {
		$cuspaddr[] = array($addr);
	}

	$cusdaddr = array (
		array ("<b>Delivery Address:</b>"),
	);

	// Temp
	$inv["branch"] = 0;
	if($inv['branch'] == 0){
		$branchname = "Head Office";
		$cusaddr = explode("\n", $cusData['addr1']);
	}else {
		$get_addr = "SELECT * FROM customer_branches WHERE id = '$inv[branch]' LIMIT 1";
		$run_addr = db_exec($get_addr);
		if (pg_numrows($run_addr) < 1) {
			$cusaddr = Array ();
			$branchname = "Head Office";
		} else {
			$barr = pg_fetch_array($run_addr);
			$cusaddr = explode("\n", $barr['branch_descrip']);
			$branchname = $barr['branch_name'];
		}
	}

	$cusdaddr[] = array(pdf_lstr("Branch : $branchname", 30));
	$del_addr = explode("\n", $cusData["del_addr1"]);
	foreach ($del_addr as $addr ) {
		$cusdaddr[] = array(pdf_lstr($addr, 30));
	}

	// Registration numbers --------------------------------------------------
	$regnos = array (
		array (
			"<b>VAT No:</b>",
			"<b>Order No:</b>"
		),
		array (
			"$inv[cusvatno]",
			"$inv[cordno]"
		)
	);

	// Items display ---------------------------------------------------------
	$items = array ();

	db_conn($prd);
	$sql = "SELECT * FROM inv_items WHERE invid='$invid' AND DIV='".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	while ($stkd = pg_fetch_array($stkdRslt)) {
		// Get warehouse
		db_conn("exten");
		$sql = "SELECT * FROM warehouses WHERE whid='$stkd[whid]' AND DIV='".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		// Get stock in this warehouse
		db_conn("cubit");
		$sql = "SELECT * FROM stock WHERE stkid='$stkd[stkid]' AND DIV='".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		$sp = "";

		// Check Tax Excempt
		db_conn("cubit");
		$sql = "SELECT zero FROM vatcodes WHERE id='$stkd[vatcode]'";
		$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		$vatex = pg_fetch_result($zRslt, 0);

		if($vatex == "Yes"){
			$ex = "#";
		} else {
			$ex = "";
		}

		$sql = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$tRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		if(pg_numrows($tRslt) < 1){
			return "Invalid VAT code entered.";
		}

		$vd = pg_fetch_array($tRslt);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		// keep track of discounts
		//$disc += $stkd['disc'];
		if ($stkd["account"] > 0) {
			$description = $stkd["description"];
		} else {
			$description = $stk["stkdes"];
		}
		$description = explode("\n", $description);
		$description = implode(" ", $description);

		if (strlen($stkd['serno']) > 0){
			$showser = "\n".trim($stkd['serno']);
		}else {
			$showser = "";
		}

		$items[] = array(
			"Code" => makewidth($pdf, 80, 12, $stk['stkcod']),
   			"Description" => makewidth($pdf, 172, 12, $ex.$description.$showser),
			"Qty" => $stkd['qty'],
			"Unit Price" => $curr.$stkd['unitcost'],
			"Unit Discount" => $curr.$stkd['disc'],
			"Amount" => $curr.$stkd['amt']
		);
	}

	// Comment ---------------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
	$commentRslt = db_exec($sql) or errDie("Unable to retrieve the default comment from Cubit.");
	$default_comment = pg_fetch_result($commentRslt, 0);

	$comment = array (
		array ("<i>VAT Exempt Indicator : #</i>"),
		array (base64_decode($default_comment))
	);

	// Box for signature -----------------------------------------------------
	$sign = array (
		array ("<i>Thank you for your support</i>"),
		array (''),
		array ("<b>Received in good order by:</b> ____________________"),
		array (''),
		// We aren't using a monospace font, so just a lot of spaces until it is aligned nicely.
		array ("                                      <b>Date:</b> ____________________")
	);

	// Totals ----------------------------------------------------------------

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$totals = array (
		array ("1" => "<b>Subtotal:</b> ", "2" => $curr."$inv[subtot]"),
		array ("1" => "<b>Trade Discount:</b> ", "2" => $curr."$inv[discount]"),
		array ("1" => "<b>Delivery Charge:</b> ", "2" => $curr."$inv[delivery]"),
		array ("1" => "<b>VAT $vat14:</b> ", "2" => $curr."$inv[vat]"),
		array ("1" => "<b>Total Incl VAT:</b> ", "2" => $curr."$inv[total]")
	);
	$totCols = array (
		"1" => array("width" => 90),
		"2" => array("justification" => "right")
	);

	$ic = 0;
	while ( ++$ic * 20 < count($items) );

	// Draw the pages, determine by the amount of items how many pages
	// if items > 20 start a new page
	$items_print = Array ();
	for ($i = 0; $i < $ic; $i++) {
		if ( $i ) $pdf->ezNewPage();

		// Page number -------------------------------------------------------
		$pagenr = array (
			array ("<b>Page number</b>"),
			array ($i + 1)
		);

		// Heading
		$heading_pos = drawTable(&$pdf, $heading, 0, 0, 520, 5);
		drawText(&$pdf, "<b>$comp[compname]</b>", 18, 18, ($heading_pos['y']/2)+6);
		drawText(&$pdf, "<b>Tax Invoice</b>", 18, $heading_pos['x']-120, ($heading_pos['y']/2)+9);

		// Should we display reprint on the invoice
		if ($type == "invpaidreprint") {
			drawText(&$pdf, "<b>Reprint</b>", 12, $heading_pos['x']-70, ($heading_pos['y']/2)+22);
		}

		$compinfo_pos = drawTable(&$pdf, $compinfo, 0, $heading_pos['y'], 320, 8);
		$date_pos = drawTable(&$pdf, $date, $compinfo_pos['x'], $heading_pos['y'] , 100, 3);
		$pagenr_pos = drawTable(&$pdf, $pagenr, $date_pos['x'], $heading_pos['y'], 100, 3);
		$docinfo_pos = drawTable(&$pdf, $docinfo, $compinfo_pos['x'], $date_pos['y'], 200, 5);
		$invoice_to_pos = drawTable(&$pdf, $invoice_to, 0, $compinfo_pos['y'], 520, 2);
		drawText(&$pdf, "<b>Tax Invoice to:</b>", 12, (520/2)-45, $invoice_to_pos['y']-7);

		$cusinfo_pos = drawTable(&$pdf, $cusinfo, 0, $invoice_to_pos['y'], 173, 8);
		$cuspaddr_pos = drawTable(&$pdf, $cuspaddr, $cusinfo_pos['x'], $invoice_to_pos['y'], 173, 8);
		$cusdaddr_pos = drawTable(&$pdf, $cusdaddr, $cuspaddr_pos['x'], $invoice_to_pos['y'], 174, 8);
		$regnos_pos = drawTable(&$pdf, $regnos, 0, $cusinfo_pos['y'], 520, 2);

		$items_start = ($i * 22);
		if ($i) $items_start++;

		if ($items_start >= count($items) - 22) {
			$items_end = count($items) - 1;
		} else {
			$items_end = ($i + 1) * 22;
		}
		$items_print = array();

		for ($j = $items_start; $j <= $items_end; $j++) {
			$items_print[$j] = $items[$j];
		}

		$cols = array(
			"Code" => array("width" => 80),
			"Description" => array("width" => 170),
			"Qty" => array("width" => 50),
			"Unit Price" => array("width" => 71, "justification" => "right"),
			"Unit Discount" => array("width" => 67, "justification" => "right"),
			"Amount" => array("width" => 82, "justification" => "right")
		);

		$items_pos = drawTable(&$pdf, $items_print, 0, $regnos_pos['y']+2, 520, 23, $cols, 1);
		$comment_pos = drawTable(&$pdf, $comment, 0, $items_pos['y'], 520, 2);
		$sign_pos = drawTable(&$pdf, $sign, 0, $comment_pos['y'], 320, 5);
		$totals_pos = drawTable(&$pdf, $totals, $sign_pos['x'], $comment_pos['y'], 200, 5, $totCols);

		$pdf->addText(20,34,6,'Cubit Accounting');
	}
	$pdf->ezStream();

}

function invMultiDetails($HTTP_GET_VARS)
{
	extract ($HTTP_GET_VARS);
	global $set_mainFont;

	$showvat = TRUE;

	$pdf = &new Cezpdf;
	$pdf->selectFont($set_mainFont);

	// Validate
	require_lib("validate");
	$v = new Validate();
	foreach ($invids as $invid) {
		$v->isOk($invid, "num", 1, 20, "Invalid invoice number.");
	}

	// Any errors?
	if ($v->isError()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		$OUTPUT = $confirm;
		require("../template.php");
	}
	$ai = 0;
	foreach ($invids as $invid) {
		if ( $ai ) $pdf->ezNewPage();
		++$ai;

		// Invoice info
		db_conn("cubit");
		$sql = "SELECT * FROM invoices WHERE invid='$invid' AND DIV='".USER_DIV."'";
		$invRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
		if (pg_num_rows($invRslt) < 1) {
			return "<li class='err'>Not found</li>";
		}
		$inv = pg_fetch_array($invRslt);

		db_conn("cubit");
		$sql = "SELECT symbol FROM currency WHERE fcid='$inv[fcid]'";
		$curRslt = db_exec($sql) or errDie("Unable to retrieve currency from Cubit.");
		$curr = pg_fetch_result($curRslt, 0);
		if (!$curr) $curr = CUR;

		// Check if stock was selected
		db_conn("cubit");
		$sql = "SELECT stkid FROM inv_items WHERE invid='$invid' AND DIV='".USER_DIV."'";
		$cRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
		if (pg_num_rows($cRslt) < 1) {
			$error = "<li class='err'>Invoice number <b>$invid</b> has no items</li>";
			$OUTPUT = $error;
		}

		// Only needs to be blank, we're manually adding text
		$heading = array ( array("") );

		// Company info ----------------------------------------------------------
		db_conn("cubit");
		$sql = "SELECT * FROM compinfo WHERE div='".USER_DIV."'";
		$ciRslt = db_exec($sql) or errDie("Unable to retrieve company info from Cubit.");
		$comp = pg_fetch_array($ciRslt);

//		$bnkData = qryBankAcct(getdSetting("BANK_DET"));
		$bnkData = qryBankAcct($inv['bankid']);

		$compinfo = array();
		$compinfo[] = array (pdf_lstr($comp["addr1"], 35), pdf_lstr($comp["paddr1"], 35));
		$compinfo[] = array (pdf_lstr($comp["addr2"], 35), pdf_lstr($comp["paddr2"], 35));
		$compinfo[] = array (pdf_lstr($comp["addr3"], 35), pdf_lstr($comp["paddr3"], 35));
		$compinfo[] = array (pdf_lstr($comp["addr4"], 35), "$comp[postcode]");
		$compinfo[] = array ("<b>REG: </b>$comp[regnum]", "<b>$bnkData[bankname]</b>");
		$compinfo[] = array ("<b>VAT REG: </b>$comp[vatnum]", "<b>Branch: </b>$bnkData[branchname]");
		$compinfo[] = array ("<b>Tel:</b> $comp[tel]", "<b>Branch Code: </b>$bnkData[branchcode]");
		$compinfo[] = array ("<b>Fax:</b> $comp[fax]", "<b>Acc Num: </b>$bnkData[accnum]");

		// Date ------------------------------------------------------------------
		$date = array (
			array ("<b>Date</b>"),
			array ($inv['odate'])
		);
		// Document info ---------------------------------------------------------
		db_conn('cubit');
		$Sl = "SELECT * FROM settings WHERE constant='SALES'";
		$Ri = db_exec($Sl) or errDie("Unable to get settings.");

		$data = pg_fetch_array($Ri);

		if($data['value'] == "Yes") {
			$sp = "<b>Sales Person: </b>$inv[salespn]";
		} else {
			$sp = "";
		}

		// Retrieve the customer information -------------------------------------
		db_conn("cubit");
		$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
		$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
		$cusData = pg_fetch_array($cusRslt);

		$docinfo = array (
			array ("<b>Invoice No:</b> $inv[invnum]"),
			array ("<b>Proforma Inv No:</b> $inv[docref]"),
			array ("<b>Sales Order No:</b> $inv[ordno]"),
			array ("<b>Account No: </b>$cusData[accno]"),
			array ("$sp"),
		);
		if (isset($salespn)) {
			$docinfo[] = array("<b>Sales Person:</b> $salespn");
		}

		// Customer info ---------------------------------------------------------
		$invoice_to = array(
			array ("")
		);

		$cusinfo = array (
			array ("<b>$inv[surname]</b>")
		);

		$cusaddr = explode("\n", $cusData['paddr1']);
		foreach ( $cusaddr as $v ) {
			$cusinfo[] = array(pdf_lstr($v, 40));
		}

//		$cusinfo[] = array("<b>Account no: </b>$cusData[accno]");

		$cusdaddr = array (
			array ("<b>Physical Address:</b>"),
		);

		$cusaddr = explode("\n", $cusData['addr1']);
		foreach ( $cusaddr as $v ) {
			$cusdaddr[] = array(pdf_lstr($v, 40));
		}

		// Registration numbers --------------------------------------------------
		$regnos = array (
			array (
				"<b>VAT No:</b>",
				"<b>Order No:</b>"
			),
			array (
				"$inv[cusvatno]",
				"$inv[cordno]"
			)
		);

		// Items display ---------------------------------------------------------
		$items = array ();

		db_conn("cubit");
		$sql = "SELECT * FROM inv_items WHERE invid='$invid' AND DIV='".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while ($stkd = pg_fetch_array($stkdRslt)) {
			// Get warehouse
			db_conn("exten");
			$sql = "SELECT * FROM warehouses WHERE whid='$stkd[whid]' AND DIV='".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			// Get stock in this warehouse
			db_conn("cubit");
			$sql = "SELECT * FROM stock WHERE stkid='$stkd[stkid]' AND DIV='".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			$sp = "";

			// Check Tax Excempt
			db_conn("cubit");
			$sql = "SELECT zero FROM vatcodes WHERE id='$stkd[vatcode]'";
			$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
			$vatex = pg_fetch_result($zRslt, 0);

			if($vatex == "Yes"){
				$ex = "#";
			} else {
				$ex = "";
			}

			$sql = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$runsql = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
			if(pg_numrows($runsql) < 1){
				return "Invalid VAT code entered";
			}

			$vd = pg_fetch_array($runsql);

			if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
				$showvat = FALSE;
			}

			// keep track of discounts
			//$disc += $stkd['disc'];
			if ($stkd["account"] > 0) {
				$description = $stkd["description"];
			} else {
				$description = $stk["stkdes"];
			}

			$description = explode("\n", $description);
			$description = implode(" ", $description);

			$items[] = array(
				"Code" => makewidth($pdf, 75, 12, $stk['stkcod']),
   				"Description" => makewidth($pdf, 175, 12, $ex.$description),
				"Qty" => $stkd['qty'],
				"Unit Price" => $curr.$stkd['unitcost'],
				"Unit Discount" => $curr.$stkd['disc'],
				"Amount" => $curr.$stkd['amt']
			);
		}

		// Comment ---------------------------------------------------------------
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$commentRslt = db_exec($sql) or errDie("Unable to retrieve the default comment from Cubit.");
		$default_comment = pg_fetch_result($commentRslt, 0);

		$comment = array (
			array ("<i>VAT Exempt Indicator : #</i>"),
			array (base64_decode($default_comment))
		);

		// Box for signature -----------------------------------------------------
		$sign = array (
			array ("<i>Thank you for your support</i>"),
			array (''),
			array ("<b>Received in good order by:</b> ____________________"),
			array (''),
			// We aren't using a monospace font, so just a lot of spaces until it is aligned nicely.
			array ("                                      <b>Date:</b> ____________________")
		);

		// Totals ----------------------------------------------------------------

		if (!isset($showvat))
			$showvat = TRUE;

		if($showvat == TRUE){
			$vat14 = AT14;
		}else {
			$vat14 = "";
		}

		$totals = array (
			array ("1" => "<b>Subtotal:</b> ", "2" => $curr."$inv[subtot]"),
			array ("1" => "<b>Trade Discount:</b> ", "2" => $curr."$inv[discount]"),
			array ("1" => "<b>Delivery Charge:</b> ", "2" => $curr."$inv[delivery]"),
			array ("1" => "<b>VAT $vat14:</b> ", "2" => $curr."$inv[vat]"),
			array ("1" => "<b>Total Incl VAT:</b> ", "2" => $curr."$inv[total]")
		);
		$totCols = array (
			"1" => array("width" => 90),
			"2" => array("justification" => "right")
		);

		$ic = 0;
		while ( ++$ic * 20 < count($items) );

		// Draw the pages, determine by the amount of items how many pages
		// if items > 20 start a new page
		$items_print = Array ();
		for ($i = 0; $i < $ic; $i++) {
			if ( $i ) $pdf->ezNewPage();

			// Page number -------------------------------------------------------
			$pagenr = array (
				array ("<b>Page number</b>"),
				array ($i + 1)
			);

			// Heading
			$heading_pos = drawTable(&$pdf, $heading, 0, 0, 520, 5);
			drawText(&$pdf, "<b>$comp[compname]</b>", 18, 18, ($heading_pos['y']/2)+6);
			drawText(&$pdf, "<b>Tax Invoice</b>", 18, $heading_pos['x']-120, ($heading_pos['y']/2)+9);

			$compinfo_pos = drawTable(&$pdf, $compinfo, 0, $heading_pos['y'], 320, 8);
			$date_pos = drawTable(&$pdf, $date, $compinfo_pos['x'], $heading_pos['y'] , 100, 4);
			$pagenr_pos = drawTable(&$pdf, $pagenr, $date_pos['x'], $heading_pos['y'], 100, 4);
			$docinfo_pos = drawTable(&$pdf, $docinfo, $compinfo_pos['x'], $date_pos['y'], 200, 4);
			$invoice_to_pos = drawTable(&$pdf, $invoice_to, 0, $compinfo_pos['y'], 520, 2);
			drawText(&$pdf, "<b>Tax Invoice to:</b>", 12, (520/2)-45, $invoice_to_pos['y']-7);

			$cusinfo_pos = drawTable(&$pdf, $cusinfo, 0, $invoice_to_pos['y'], 173, 8);
			$cuspaddr_pos = drawTable(&$pdf, $cuspaddr, $cusinfo_pos['x'], $invoice_to_pos['y'], 173, 8);
			$cusdaddr_pos = drawTable(&$pdf, $cusdaddr, $cuspaddr_pos['x'], $invoice_to_pos['y'], 174, 8);
			$regnos_pos = drawTable(&$pdf, $regnos, 0, $cusinfo_pos['y'], 520, 2);

			$items_start = ($i * 22);
			if ($i) $items_start++;

			if ($items_start >= count($items) - 22) {
				$items_end = count($items) - 1;
			} else {
				$items_end = ($i + 1) * 22;
			}
			$items_print = array();

			for ($j = $items_start; $j <= $items_end; $j++) {
				$items_print[$j] = $items[$j];
			}

			$cols = array(
				"Code" => array("width" => 80),
				"Description" => array("width" => 180),
				"Qty" => array("width" => 33),
				"Unit Price" => array("width" => 80, "justification" => "right"),
				"Unit Discount" => array("width" => 67, "justification" => "right"),
				"Amount" => array("width" => 80, "justification" => "right")
			);

			$items_pos = drawTable(&$pdf, $items_print, 0, $regnos_pos['y']+2, 520, 22, $cols, 1);
			$comment_pos = drawTable(&$pdf, $comment, 0, $items_pos['y'], 520, 2);
			$sign_pos = drawTable(&$pdf, $sign, 0, $comment_pos['y'], 320, 5);
			$totals_pos = drawTable(&$pdf, $totals, $sign_pos['x'], $comment_pos['y'], 200, 5, $totCols);

			$pdf->addText(20,34,6,'Cubit Accounting');
		}
	}
	$pdf->ezStream();

}

function nonsDetails($HTTP_GET_VARS)
{

	extract ($HTTP_GET_VARS);
	global $set_mainFont;

	$showvat = TRUE;

	$pdf = &new Cezpdf;
	$pdf->selectFont($set_mainFont);

	// Validate
	require_lib("validate");
	$v = new Validate();
	$v->isOk($invid, "num", 1, 20, "Invalid invoice number.");

	// Any errors?
	if ($v->isError()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		$OUTPUT = $confirm;
		require("../template.php");
	}

	// Invoice info
	db_conn("cubit");
	$sql = "SELECT * FROM nons_invoices WHERE invid='$invid' AND DIV='".USER_DIV."'";
	$invRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
	//die ($sql);
	if (pg_num_rows($invRslt) == 0) {
		return "<li class='err'>Not found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	db_conn("cubit");
	$sql = "SELECT symbol FROM currency WHERE fcid='$inv[fcid]'";
	$curRslt = db_exec($sql) or errDie("Unable to retrieve currency from Cubit.");
	$curr = pg_fetch_result($curRslt, 0);
	if (!$curr) $curr = CUR;

	// Only needs to be blank, we're manually adding text
	$heading = array ( array("") );

	// Company info ----------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT * FROM compinfo WHERE div='".USER_DIV."'";
	$ciRslt = db_exec($sql) or errDie("Unable to retrieve company info from Cubit.");
	$comp = pg_fetch_array($ciRslt);

//	$bnkData = qryBankAcct(getdSetting("BANK_DET"));
	$bnkData = qryBankAcct($inv['bankid']);
	if (!is_array($bnkData))
		$bnkData = $bnkData->fetch_array();

	$compinfo = array();
	$compinfo[] = array (pdf_lstr($comp["addr1"], 35), pdf_lstr($comp["paddr1"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr2"], 35), pdf_lstr($comp["paddr2"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr3"], 35), pdf_lstr($comp["paddr3"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr4"], 35), "$comp[postcode]");
	$compinfo[] = array ("<b>REG: </b>$comp[regnum]", "<b>$bnkData[bankname]</b>");
	$compinfo[] = array ("<b>VAT REG: </b>$comp[vatnum]", "<b>Branch: </b>$bnkData[branchname]");
	$compinfo[] = array ("<b>Tel:</b> $comp[tel]", "<b>Branch Code: </b>$bnkData[branchcode]");
	$compinfo[] = array ("<b>Fax:</b> $comp[fax]", "<b>Acc Num: </b>$bnkData[accnum]");

	// Date ------------------------------------------------------------------
	$date = array (
		array ("<b>Date</b>"),
		array ($inv['odate'])
	);
	// Document info ---------------------------------------------------------
	db_conn('cubit');

	$Sl = "SELECT * FROM settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sp = "<b>Sales Person: </b>$inv[salespn]";
	} else {
		$sp = "";
	}

	$docinfo = array (
		array ("<b>Invoice No:</b> $inv[invnum]"),
		array ("<b>Proforma Inv No:</b> $inv[docref]"),
		array ("$sp")
	);
	// Customer info ---------------------------------------------------------
	if ($inv["cusid"] != 0) {
		db_conn("cubit");
		$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusid]'";
		$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
		$cusData = pg_fetch_array($cusRslt);
	} else {
		$cusData["surname"] = $inv["cusname"];
		$cusData["addr1"] = $inv["cusaddr"];
		$cusData["paddr1"] = $inv["cusaddr"];
		$cusData["del_addr1"] = "";
		$cusData["accno"] = "";
	}

	$invoice_to = array(
		array ("")
	);

	$cusinfo = array (
		array ("<b>$cusData[surname]</b>")
	);

	$cusaddr = explode("\n", $cusData['addr1']);
	foreach ( $cusaddr as $v ) {
		$cusinfo[] = array(pdf_lstr($v, 40));
	}

	$cusinfo[] = array("<b>Account no: </b>$cusData[accno]");

	$cuspaddr = array (
		array ("<b>Postal Address</b>")
	);

	$paddr = explode("\n", $cusData["paddr1"]);
	foreach ($paddr as $addr) {
		$cuspaddr[] = array("$addr");
	}

	$cusdaddr = array (
		array ("<b>Delivery Address:</b>"),
	);

	$cusaddr = explode("\n", $cusData['del_addr1']);
	foreach ( $cusaddr as $v ) {
		$cusdaddr[] = array(pdf_lstr($v, 40));
	}
	// Registration numbers --------------------------------------------------
	$regnos = array (
		array (
			"<b>VAT No:</b>",
			"<b>Order No:</b>"
		),
		array (
			"$inv[cusvatno]",
			"$inv[cordno]"
		)
	);

	// Items display ---------------------------------------------------------
	$items = array ();

	db_conn("cubit");
	$sql = "SELECT * FROM nons_inv_items WHERE invid='$invid' AND DIV='".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	while ($stkd = pg_fetch_array($stkdRslt)) {
		// Check Tax Excempt
		db_conn("cubit");
		$sql = "SELECT zero FROM vatcodes WHERE id='$stkd[vatex]'";
		$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		$vatex = pg_fetch_result($zRslt, 0);

		if($vatex == "Yes"){
			$ex = "#";
		} else {
			$ex = "";
		}

		$sql = "SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
		$runsql = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		if(pg_numrows($runsql) < 1){
			return "Invalid VAT code entered";
		}

		$vd = pg_fetch_array($runsql);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$items[] = array(
			"Description" => makewidth($pdf, 305, 12, $ex.strip_tags($stkd["description"])),
			"Qty" => $stkd['qty'],
			"Unit Price" => $curr.$stkd['unitcost'],
			"Amount" => $curr.$stkd['amt']
		);
	}

	// Comment ---------------------------------------------------------------
	$comment = array (
		array("<i>VAT Exempt Indicator: #</i>"),
		array($inv["remarks"])
	);

	// Box to sign in --------------------------------------------------------
	$sign = array (
		array ("<b>Terms:</b> $inv[terms]"),
		array (''),
		array ("<b>Received in good order by:</b> ____________________"),
		array (''),
		// We aren't using a monospace font, so just a lot of spaces until it is aligned nicely.
		array ("                                      <b>Date:</b> ____________________")
	);

	// Totals ----------------------------------------------------------------

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$totals = array (
		array ("1" => "<b>Subtotal:</b> ", "2" => $curr."$inv[subtot]"),
		array ("1" => "<b>VAT $vat14:</b> ", "2" => $curr."$inv[vat]"),
		array ("1" => "<b>Total Incl VAT:</b> ", "2" => $curr."$inv[total]")
	);
	$totCols = array (
		"1"=>array("width" => 90),
		"2"=>array("justification" => "right")
	);

	$ic = 0;
	while ( ++$ic * 20 < count($items) );

	// Draw the pages, determine by the amount of items how many pages
	// if items > 20 start a new page
	$items_print = Array ();
	for ($i = 0; $i < $ic; $i++) {
		if ( $i ) $pdf->ezNewPage();

		// Page number -------------------------------------------------------
		$pagenr = array (
			array ("<b>Page number</b>"),
			array ($i + 1)
		);

		// Heading
		$heading_pos = drawTable(&$pdf, $heading, 0, 0, 520, 5);
		drawText(&$pdf, "<b>$comp[compname]</b>", 18, 18, ($heading_pos['y']/2)+6);
		drawText(&$pdf, "<b>Tax Invoice</b>", 18, $heading_pos['x']-120, ($heading_pos['y']/2)+9);

		// Should we display reprint on the invoice
		if ($type == "nonsreprint") {
			drawText(&$pdf, "<b>Reprint</b>", 12, $heading_pos['x']-70, ($heading_pos['y']/2)+22);
		}

		$compinfo_pos = drawTable(&$pdf, $compinfo, 0, $heading_pos['y'], 320, 8);
		$date_pos = drawTable(&$pdf, $date, $compinfo_pos['x'], $heading_pos['y'] , 100, 4);
		$pagenr_pos = drawTable(&$pdf, $pagenr, $date_pos['x'], $heading_pos['y'], 100, 4);
		$docinfo_pos = drawTable(&$pdf, $docinfo, $compinfo_pos['x'], $date_pos['y'], 200, 4);
				$invoice_to_pos = drawTable(&$pdf, $invoice_to, 0, $compinfo_pos['y'], 520, 2);
		drawText(&$pdf, "<b>Tax Invoice to:</b>", 12, (520/2)-45, $invoice_to_pos['y']-7);

		$cusinfo_pos = drawTable(&$pdf, $cusinfo, 0, $invoice_to_pos['y'], 173, 8);
		$cuspaddr_pos = drawTable(&$pdf, $cuspaddr, $cusinfo_pos['x'], $invoice_to_pos['y'], 173, 8);
		$cusdaddr_pos = drawTable(&$pdf, $cusdaddr, $cuspaddr_pos['x'], $invoice_to_pos['y'], 174, 8);
		$regnos_pos = drawTable(&$pdf, $regnos, 0, $cusinfo_pos['y'], 520, 2);

		$items_start = ($i * 20);
		if ($i) $items_start++;

		if ($items_start >= count($items) - 20) {
			$items_end = count($items) - 1;
		} else {
			$items_end = ($i + 1) * 20;
		}
		$items_print = array();

		for ($j = $items_start; $j <= $items_end; $j++) {
			$items_print[$j] = $items[$j];
		}

		// Adjust the column widths
		$cols = array (
			"Description" => array("width" => 285),
			"Qty" => array ("width" => 55),
			"Unit Price" => array ("width" => 90, "justification"=>"right"),
			"Amount" => array ("width" => 90, "justification"=>"right")
		);

		$items_pos = drawTable(&$pdf, $items_print, 0, $regnos_pos['y']+2, 520, 22, $cols, 1);
		$comment_pos = drawTable(&$pdf, $comment, 0, $items_pos['y'], 520, 2);
		$sign_pos = drawTable(&$pdf, $sign, 0, $comment_pos['y'], 320, 5);
		$totals_pos = drawTable(&$pdf, $totals, $sign_pos['x'], $comment_pos['y'], 200, 5, $totCols);

		$pdf->addText(20,34,6,'Cubit Accounting');
	}

	$pdf->ezStream();

}




function nonsNoteDetails($HTTP_GET_VARS)
{

	extract ($HTTP_GET_VARS);
	global $set_mainFont;

	$showvat = TRUE;

	$pdf = &new Cezpdf;
	$pdf->selectFont($set_mainFont);

	// Validate
	require_lib("validate");
	$v = new Validate();
	$v->isOk($noteid, "num", 1, 20, "Invalid credit note id.");

	// Any errors?
	if ($v->isError()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		$OUTPUT = $confirm;
		require("../template.php");
	}

	// Invoice info
	db_conn("cubit");
	$sql = "SELECT * FROM nons_inv_notes WHERE noteid='$noteid'";
	$invRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
	if (pg_num_rows($invRslt) < 1) {;
		return "<li class='err'>Not found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	if(strlen($inv['fcid']) < 1)
		$inv['fcid'] = "0";

	db_conn("cubit");
	$sql = "SELECT symbol FROM currency WHERE fcid='$inv[fcid]'";
	$curRslt = db_exec($sql) or errDie("Unable to retrieve currency from Cubit.");
	$curr = pg_fetch_result($curRslt, 0);
	if (!$curr) $curr = CUR;

	db_conn("cubit");
	$sql = "SELECT * FROM nons_invoices WHERE invid='$inv[invid]'";
	$nonsRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
	$nons = pg_fetch_array($nonsRslt);

	// Only needs to be blank, we're manually adding text
	$heading = array ( array("") );

	// Company info ----------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT * FROM compinfo WHERE div='".USER_DIV."'";
	$ciRslt = db_exec($sql) or errDie("Unable to retrieve company info from Cubit.");
	$comp = pg_fetch_array($ciRslt);

	// Banking information ---------------------------------------------------
//	$bnkData = qryBankAcct(getdSetting("BANK_DET"));
	$bnkData = qryBankAcct($inv['bankid']);

	$compinfo = array();
	$compinfo[] = array (pdf_lstr($comp["addr1"], 35), pdf_lstr($comp["paddr1"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr2"], 35), pdf_lstr($comp["paddr2"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr3"], 35), pdf_lstr($comp["paddr3"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr4"], 35), "$comp[postcode]");
	$compinfo[] = array ("<b>REG: </b>$comp[regnum]", "<b>$bnkData[bankname]</b>");
	$compinfo[] = array ("<b>VAT REG: </b>$comp[vatnum]", "<b>Branch: </b>$bnkData[branchname]");
	$compinfo[] = array ("<b>Tel:</b> $comp[tel]", "<b>Branch Code: </b>$bnkData[branchcode]");
	$compinfo[] = array ("<b>Fax:</b> $comp[fax]", "<b>Acc Num: </b>$bnkData[accnum]");

	// Date ------------------------------------------------------------------
	$date = array (
		array ("<b>Date</b>"),
		array ($inv['date'])
	);
	// Document info ---------------------------------------------------------
	$docinfo = array (
		array ("<b>Credit Note No: </b> $inv[notenum]"),
		array ("<b>Invoice No:</b> $inv[invnum]")
	);

	// Customer info ---------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT cusid FROM nons_invoices WHERE invid='$inv[invid]'";
	$ciRslt = db_exec($sql) or errDie("Unable to retrieve customer id from Cubit.");
	$cusid = pg_fetch_result($ciRslt, 0);

	if ($inv["ctyp"] == "s") {
		db_conn("cubit");
		$sql = "SELECT * FROM customers WHERE cusnum='$cusid'";
		$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
		$cusData = pg_fetch_array($cusRslt);
	} else {
		$cusData["surname"] = $inv["cusname"];
		$cusData["addr1"] = $inv["cusaddr"];
		$cusData["paddr1"] = "";
		$cusData["accno"] = "";
	}

	$invoice_to = array(
		array ("")
	);

	$cusinfo = array (
		array ("<b>$cusData[surname]</b>")
	);

	$addr = explode("\n", $cusData["addr1"]);
	foreach ($addr as $each) {
		$cusinfo[] = array($each);
	}

	$cuspaddr = array (
		array("<b>Postal Address</b>"),
	);

	$paddr = explode("\n", $cusData["paddr1"]);
	foreach ($paddr as $addr) {
		$cuspaddr[] = array($addr);
	}

	$cusdaddr = array (
		array ("<b>Delivery Address:</b>"),
	);

	// Temp
	$inv["branch"] = 0;
	if($inv['branch'] == 0){
		$branchname = "Head Office";
		$cusaddr = explode("\n", $cusData['addr1']);
	}else {
		$get_addr = "SELECT * FROM customer_branches WHERE id = '$inv[branch]' LIMIT 1";
		$run_addr = db_exec($get_addr);
		if (pg_numrows($run_addr) < 1) {
			$cusaddr = Array ();
			$branchname = "Head Office";
		} else {
			$barr = pg_fetch_array($run_addr);
			$cusaddr = explode("\n", $barr['branch_descrip']);
			$branchname = $barr['branch_name'];
		}
	}

	$cusdaddr[] = array(pdf_lstr("Branch : $branchname", 30));
	$del_addr = explode("\n", $cusData["addr1"]);
	foreach ($del_addr as $addr ) {
		$cusdaddr[] = array(pdf_lstr($addr, 30));
	}

	// Registration numbers --------------------------------------------------
	$regnos = array (
		array (
			"<b>VAT No:</b>",
			"<b>Order No:</b>"
		),
		array (
			"$inv[cusvatno]",
			"$nons[cordno]"
		)
	);

	// Items display ---------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT * FROM nons_note_items WHERE noteid='$inv[noteid]'";
	$stkdRslt = db_exec($sql);

	$items = array ( );
	while ($stkd = pg_fetch_array($stkdRslt)) {
		// Check Tax Excempt
		db_conn("cubit");
		$sql = "SELECT vatex FROM nons_inv_items WHERE invid='$inv[invid]'";
		$vtxRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		$vatid = pg_fetch_result($vtxRslt, 0);

		db_conn("cubit");
		$sql = "SELECT zero FROM vatcodes WHERE id='$vatid'";
		$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		$vatex = pg_fetch_result($zRslt, 0);

		if($vatex == "Yes"){
			$ex = "#";
		} else {
			$ex = "";
		}

		$sql = "SELECT * FROM vatcodes WHERE id='$vatid'";
		$runsql = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		if(pg_numrows($runsql) < 1){
			return "Invalid VAT code entered.";
		}

		$vd = pg_fetch_array($runsql);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$items[] = array(
			"Description" => makewidth($pdf, 305, 12, $ex.$stkd["description"]),
			"Qty" => $stkd['qty'],
			"Unit Price" => $curr.$stkd['unitcost'],
			"Amount" => $curr.$stkd['amt']
		);
	}

	// Comment ---------------------------------------------------------------
	#check if cred note has comment, else use the default comment
	if(strlen($inv['remarks']) > 0){
		$inv["remarks"] = fixparag(&$pdf, 3, 520, 11, $inv["remarks"]);
		$comment = array (
			array("<i>VAT Exempt Indicator : #</i>"),
			array($inv['remarks'])
		);
	}else {
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$commentRslt = db_exec($sql) or errDie("Unable to retrieve the default comment from Cubit.");
		$default_comment = pg_fetch_result($commentRslt, 0);

		$$default_comment = fixparag(&$pdf, 3, 520, 11, $default_comment);
		$comment = array (
			array("<i>VAT Exempt Indicator : #</i>"),
			array(base64_decode($default_comment))
		);
	}

	// Box to sign in --------------------------------------------------------
	$sign = array (
		array ("<i>Thank you for your support</i>"),
		array (''),
		array ("<b>Received in good order by:</b> ____________________"),
		array (''),
		// We aren't using a monospace font, so just a lot of spaces until it is aligned nicely.
		array ("                                      <b>Date:</b> ____________________")
	);

	// Totals ----------------------------------------------------------------

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$totals = array (
		array ("1" => "<b>Subtotal:</b> ", "2" => $curr."$inv[subtot]"),
		array ("1" => "<b>VAT $vat14:</b> ", "2" => $curr."$inv[vat]"),
		array ("1" => "<b>Total Incl VAT:</b> ", "2" => $curr."$inv[total]")
	);
	$totCols = array (
		"1"=>array("width" => 90),
		"2"=>array("justification" => "right")
	);

	$ic = 0;
	while ( ++$ic * 20 < count($items) );

	// Draw the pages, determine by the amount of items how many pages
	// if items > 20 start a new page
	$items_print = Array ();
	for ($i = 0; $i < $ic; $i++) {
		if ( $i ) $pdf->ezNewPage();

		// Page number -------------------------------------------------------
		$pagenr = array (
			array ("<b>Page number</b>"),
			array ($i + 1)
		);

		// Heading
		$heading_pos = drawTable(&$pdf, $heading, 0, 0, 520, 5);
		drawText(&$pdf, "<b>$comp[compname]</b>", 18, 18, ($heading_pos['y']/2)+6);
		drawText(&$pdf, "<b>Tax Credit Note</b>", 18, $heading_pos['x']-140, ($heading_pos['y']/2)+9);

		// Should we display reprint on the invoice
		if (isset($reprint)) {
			drawText(&$pdf, "<b>Reprint</b>", 12, $heading_pos['x']-70, ($heading_pos['y']/2)+22);
		}

		$compinfo_pos = drawTable(&$pdf, $compinfo, 0, $heading_pos['y'], 320, 8);
		$date_pos = drawTable(&$pdf, $date, $compinfo_pos['x'], $heading_pos['y'] , 100, 4);
		$pagenr_pos = drawTable(&$pdf, $pagenr, $date_pos['x'], $heading_pos['y'], 100, 4);
		$docinfo_pos = drawTable(&$pdf, $docinfo, $compinfo_pos['x'], $date_pos['y'], 200, 4);
		$invoice_to_pos = drawTable(&$pdf, $invoice_to, 0, $compinfo_pos['y'], 520, 2);
		drawText(&$pdf, "<b>Credit Note to:</b>", 12, (520/2)-45, $invoice_to_pos['y']-7);

		$cusinfo_pos = drawTable(&$pdf, $cusinfo, 0, $invoice_to_pos['y'], 173, 8);
		$cuspaddr_pos = drawTable(&$pdf, $cuspaddr, $cusinfo_pos['x'], $invoice_to_pos['y'], 173, 8);
		$cusdaddr_pos = drawTable(&$pdf, $cusdaddr, $cuspaddr_pos['x'], $invoice_to_pos['y'], 174, 8);
		$regnos_pos = drawTable(&$pdf, $regnos, 0, $cusinfo_pos['y'], 520, 2);

		$items_start = ($i * 22);
		if ($i) $items_start++;

		if ($items_start >= count($items) - 22) {
			$items_end = count($items) - 1;
		} else {
			$items_end = ($i + 1) * 22;
		}
		$items_print = array();

		for ($j = $items_start; $j <= $items_end; $j++) {
			$items_print[$j] = $items[$j];
		}

		// Adjust the column widths
		$cols = array (
			"Description" => array("width" => 310),
			"Qty" => array ("width" => 50),
			"Unit Price" => array ("width" => 80, "justification" => "right"),
			"Amount" => array ("width" => 80, "justification" => "right")
		);

		$items_pos = drawTable(&$pdf, $items_print, 0, $regnos_pos['y']+2, 520, 23, $cols, 1);
		$comment_pos = drawTable(&$pdf, $comment, 0, $items_pos['y'], 520, 2);
		$sign_pos = drawTable(&$pdf, $sign, 0, $comment_pos['y'], 320, 5);
		$totals_pos = drawTable(&$pdf, $totals, $sign_pos['x'], $comment_pos['y'], 200, 5, $totCols);

		$pdf->addText(20,34,6,'Cubit Accounting');
	}
	$pdf->ezStream();

}

function invNoteDetails($HTTP_GET_VARS)
{
	extract ($HTTP_GET_VARS);
	global $set_mainFont;

	$showvat = TRUE;

	$pdf = &new Cezpdf;
	$pdf->selectFont($set_mainFont);

	// Validate
	require_lib("validate");
	$v = new Validate();
	$v->isOk($invid, "num", 1, 20, "Invalid invoice number.");
	$v->isOk($prd, "num", 1, 9, "Invalid period.");

	// Any errors?
	if ($v->isError()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		$confirm = "<p><input type='button' onClick='javascript.history.back();' value='&laquo; Correct Submission'></p>";
		$OUTPUT = $confirm;
		require("../template.php");
	}

	// Invoice info
	db_conn($prd);
	$sql = "SELECT * FROM inv_notes WHERE noteid='$invid' AND DIV='".USER_DIV."'";
	$invRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
	if (pg_num_rows($invRslt) < 1) {
		return "<li class='err'>Not found</li>";
	}
	$inv = pg_fetch_array($invRslt);

	db_conn("cubit");

	$sql = "SELECT symbol FROM currency WHERE fcid='$inv[fcid]'";
	$curRslt = db_exec($sql) or errDie("Unable to retrieve currency from Cubit.");
	$curr = pg_fetch_result($curRslt, 0);
	if (!$curr) $curr = CUR;

	// Check if stock was selected
	db_conn("cubit");

	$sql = "SELECT stkid FROM inv_items WHERE invid='$invid' AND DIV='".USER_DIV."'";
	$cRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
	if (pg_num_rows($cRslt) < 1) {
		$error = "<li class='err'>Invoice number <b>$invid</b> has no items</li>";
		$OUTPUT = $error;
	}

	// Only needs to be blank, we're manually adding text
	$heading = array ( array("") );

	// Company info ----------------------------------------------------------
	db_conn("cubit");
	$sql = "SELECT * FROM compinfo WHERE div='".USER_DIV."'";
	$ciRslt = db_exec($sql) or errDie("Unable to retrieve company info from Cubit.");
	$comp = pg_fetch_array($ciRslt);

	// Banking information ---------------------------------------------------
//	$bnkData = qryBankAcct(getdSetting("BANK_DET"));
	$bnkData = qryBankAcct($inv['bankid']);

	$compinfo = array();
	$compinfo[] = array (pdf_lstr($comp["addr1"], 35), pdf_lstr($comp["paddr1"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr2"], 35), pdf_lstr($comp["paddr2"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr3"], 35), pdf_lstr($comp["paddr3"], 35));
	$compinfo[] = array (pdf_lstr($comp["addr4"], 35), "$comp[postcode]");
	$compinfo[] = array ("<b>REG: </b>$comp[regnum]", "<b>$bnkData[bankname]</b>");
	$compinfo[] = array ("<b>VAT REG: </b>$comp[vatnum]", "<b>Branch: </b>$bnkData[branchname]");
	$compinfo[] = array ("<b>Tel:</b> $comp[tel]", "<b>Branch Code: </b>$bnkData[branchcode]");
	$compinfo[] = array ("<b>Fax:</b> $comp[fax]", "<b>Acc Num: </b>$bnkData[accnum]");

	// Date ------------------------------------------------------------------
	$date = array (
		array ("<b>Date</b>"),
		array ($inv['odate'])
	);

	// Document info ---------------------------------------------------------
	db_conn('cubit');

	$Sl = "SELECT * FROM cubit.settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sp = "<b>Sales Person: </b>$inv[salespn]";
	} else {
		$sp = "";
	}

	// Retrieve the customer information -------------------------------------
	db_conn("cubit");

	$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
	$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
	$cusData = pg_fetch_array($cusRslt);

	$docinfo = array (
		array ("<b>Credit Note No:</b> $inv[notenum]"),
		array ("<b>Invoice No:</b> $inv[invnum]"),
		array ("<b>Sales Order No:</b> $inv[ordno]"),
		array ("$sp")
	);

	// Customer info ---------------------------------------------------------
	$invoice_to = array(
		array ("")
	);

	$cusinfo = array (
		array ("<b>$inv[surname]</b>")
	);

	$addr1 = explode("\n", $cusData["addr1"]);
	foreach ($addr1 as $addr) {
		$cusinfo[] = array($addr);
	}

	$cuspaddr = array (
		array("<b>Postal Address</b>"),
	);

	$paddr = explode("\n", $cusData["paddr1"]);
	foreach ($paddr as $addr) {
		$cuspaddr[] = array($addr);
	}

	$cusdaddr = array (
		array ("<b>Delivery Address:</b>"),
	);

	// Temp
//	$inv["branch"] = 0;
	if($inv['branch'] == 0){
		$branchname = "Head Office";
		$cusaddr = explode("\n", $cusData['addr1']);
	}else {
		$get_addr = "SELECT * FROM customer_branches WHERE id = '$inv[branch]' LIMIT 1";
		$run_addr = db_exec($get_addr);
		if (pg_numrows($run_addr) < 1) {
			$cusaddr = Array ();
			$branchname = "Head Office";
		} else {
			$barr = pg_fetch_array($run_addr);
			$cusaddr = explode("\n", $barr['branch_descrip']);
			$branchname = $barr['branch_name'];
			$cusData["del_addr1"] = $barr['branch_descrip'];
		}
	}

	$cusdaddr[] = array(pdf_lstr("Branch : $branchname", 30));
	$del_addr = explode("\n", $cusData["del_addr1"]);
	foreach ($del_addr as $addr ) {
		$cusdaddr[] = array(pdf_lstr($addr, 30));
	}

	// Registration numbers --------------------------------------------------
	$regnos = array (
		array (
			"<b>VAT No:</b>",
			"<b>Order No:</b>"
		),
		array (
			"$inv[cusvatno]",
			"$inv[cordno]"
		)
	);

	// Items display ---------------------------------------------------------
	$items = array ();

	db_conn($prd);
	$sql = "SELECT * FROM inv_note_items WHERE noteid='$invid' AND DIV='".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	$nsub = 0;
	while ($stkd = pg_fetch_array($stkdRslt)) {
		// Get warehouse
		db_conn("exten");
		$sql = "SELECT * FROM warehouses WHERE whid='$stkd[whid]' AND DIV='".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		// Get stock in this warehouse
		db_conn("cubit");
		$sql = "SELECT * FROM stock WHERE stkid='$stkd[stkid]' AND DIV='".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		$sp = "";
		// Check Tax Excempt
		db_conn("cubit");
		$sql = "SELECT zero FROM vatcodes WHERE id='$stkd[vatcode]'";
		$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		$vatex = pg_fetch_result($zRslt, 0);

		if($vatex == "Yes"){
			$ex = "#";
		} else {
			$ex = "";
		}

		$sql = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
	//	print $sql;
		$runsql = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
		if(pg_numrows($runsql) < 1){
			//return "Invalid VAT code entered.";
		}

		$vd = pg_fetch_array($runsql);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$selamt = sprint($stkd['amt']/$stkd['qty']);

		$nsub+= sprint($stkd["amt"]);

		// keep track of discounts
		//$disc += $stkd['disc'];

		// Stock or non stock description?
 		if (!empty($stkd["description"])) {
 			$description = $stkd["description"];
 		} else {
 			$description = $stk["stkdes"];
 		}

 		$description = explode("\n", $description);
 		$description = implode(" ", $description);

		$items[] = array(
		    "Stock Code" => makewidth($pdf, 80, 12, $stk["stkcod"]),
		    "Description" => makewidth($pdf, 280, 12, $ex.$description),
			"Qty Returned" => $stkd['qty'],
			"Amount" => $stkd['amt']
		);

	}

	// Comment ---------------------------------------------------------------
	$comment = array (
  		array ("<i>VAT Exempt Indicator : #</i>"),
		array($inv["comm"])
	);

	// Box to sign in --------------------------------------------------------
	$sign = array (
		array ("<i>Thank you for your support</i>"),
		array (''),
		array ("<b>Received in good order by:</b> ____________________"),
		array (''),
		// We aren't using a monospace font, so just a lot of spaces until it is aligned nicely.
		array ("                                      <b>Date:</b> ____________________")
	);

	// Totals ----------------------------------------------------------------

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$totals = array (
		array ("1" => "<b>Subtotal:</b> ", "2" => $curr.sprint($nsub, 2)),
		array ("1" => "<b>Trade Discount:</b> ", "2" => $curr."$inv[traddisc]"),
		array ("1" => "<b>Delivery Charge:</b> ", "2" => $curr."$inv[delchrg]"),
		array ("1" => "<b>VAT $vat14:</b> ", "2" => $curr."$inv[vat]"),
		array ("1" => "<b>Total Incl VAT:</b> ", "2" => $curr."$inv[total]")
	);
	$totCols = array (
		"1" => array("width" => 90),
		"2" => array("justification" => "right")
	);

	$ic = 0;
	while ((++$ic * 20) < count($items));

	// Draw the pages, determine by the amount of items how many pages
	// if items > 20 start a new page
	$items_print = Array ();
	for ($i = 0; $i < $ic; $i++) {
		if ( $i ) $pdf->ezNewPage();

		// Page number -------------------------------------------------------
		$pagenr = array (
			array ("<b>Page number</b>"),
			array ($i + 1)
		);

		// Heading
		$heading_pos = drawTable(&$pdf, $heading, 0, 0, 520, 5);
		drawText(&$pdf, "<b>$comp[compname]</b>", 18, 18, ($heading_pos['y']/2)+6);
		drawText(&$pdf, "<b>Tax Credit Note</b>", 18, $heading_pos['x']-140, ($heading_pos['y']/2)+9);
		// Should we display reprint on the invoice
		if ($type == "invnotereprint") {
			drawText(&$pdf, "<b>Reprint</b>", 12, $heading_pos['x']-70, ($heading_pos['y']/2)+22);
		}

		$compinfo_pos = drawTable(&$pdf, $compinfo, 0, $heading_pos['y'], 320, 8);
		$date_pos = drawTable(&$pdf, $date, $compinfo_pos['x'], $heading_pos['y'] , 100, 4);
		$pagenr_pos = drawTable(&$pdf, $pagenr, $date_pos['x'], $heading_pos['y'], 100, 4);
		$docinfo_pos = drawTable(&$pdf, $docinfo, $compinfo_pos['x'], $date_pos['y'], 200, 4);
		$invoice_to_pos = drawTable(&$pdf, $invoice_to, 0, $compinfo_pos['y'], 520, 2);
		drawText(&$pdf, "<b>Credit Note to:</b>", 12, (520/2)-45, $invoice_to_pos['y']-7);

		$cusinfo_pos = drawTable(&$pdf, $cusinfo, 0, $invoice_to_pos['y'], 173, 8);
		$cuspaddr_pos = drawTable(&$pdf, $cuspaddr, $cusinfo_pos['x'], $invoice_to_pos['y'], 173, 8);
		$cusdaddr_pos = drawTable(&$pdf, $cusdaddr, $cuspaddr_pos['x'], $invoice_to_pos['y'], 174, 8);
		$regnos_pos = drawTable(&$pdf, $regnos, 0, $cusinfo_pos['y'], 520, 2);

		$items_start = ($i * 22);
		if ($i) $items_start++;

		if ($items_start >= count($items) - 22) {
			$items_end = count($items) - 1;
		} else {
			$items_end = ($i + 1) * 22;
		}
		$items_print = array();

		for ($j = $items_start; $j <= $items_end; $j++) {
			$items_print[$j] = $items[$j];
		}
		$cols = array (
			"Stock Code" => array("width" => 80),
			"Description" => array("width" => 280),
			"Qty Returned" => array("width" => 80),
			"Amount"=>array("width" => 80, "justification" => "right")
		);
		$items_pos = drawTable(&$pdf, $items_print, 0, $regnos_pos['y']+2, 520, 23, $cols, 1);
		$comment_pos = drawTable(&$pdf, $comment, 0, $items_pos['y'], 520, 2);
		$sign_pos = drawTable(&$pdf, $sign, 0, $comment_pos['y'], 320, 5);
		$totals_pos = drawTable(&$pdf, $totals, $sign_pos['x'], $comment_pos['y'], 200, 5, $totCols);

		$pdf->addText(20,34,6,'Cubit Accounting');
	}
	$pdf->ezStream();

}



?>
