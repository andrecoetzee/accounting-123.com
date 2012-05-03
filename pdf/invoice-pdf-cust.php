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
require("../settings.php");
require("../libs/ext.lib.php");

# Decide what to do
if (isset($_GET["cusnum"])) {
	$OUTPUT =enter($_GET);

	require("../template.php");
} elseif (isset($_POST["cusnum"])) {
	details($_POST);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
	require("../template.php");
}

function enter($_GET) {

	extract($_GET);

	$cusnum+=0;

	db_conn('cubit');

	$Sl="SELECT * FROM customers WHERE cusnum='$cusnum'";
	$Ri=db_exec($Sl);

	$cd=pg_fetch_array($Ri);

	$invs= "
			<select name='type'>
				<option value='all'>All Invoices</option>
				<option value='paid'>Paid Invoices</option>
				<option value='unpaid'>Outstanding Invoices</option>
			</select>";

	$out = "
			<h3>Print Invoices for $cd[surname]</h3>
			<form action='".SELF."' method='POST'>
			<table ".TMPL_tblDflts.">
				<input type='hidden' name='cusnum' value='$cusnum'>
				<tr>
					<th>Invoices</th>
				</tr>
				<tr class='".bg_class()."'1>
					<td>$invs</td>
				</tr>
				<tr><td><br></td></tr>
				<tr><td><br></td></tr>
				<tr>
					<td><input type='submit' value='Process'></td>
				</tr>
			</table>
			</form>";

	return $out;

}
	

# Details
function details($_GET)
{

	# Get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		$OUTPUT = $confirm;
		require("../template.php");
	}

	if($type=="paid") {
		$ex="AND balance=0";
	} elseif($type=="unpaid") {
		$ex="AND balance>0";
	} else {
		$ex="";
	}

	
	# Get invoice info

	/* Start PDF Layout */
	include("../pdf-settings.php");
	
	$get_set = "SELECT filename FROM template_settings WHERE template = 'reprints' LIMIT 1";
	$run_set = db_exec($get_set) or errDie("Unable to get template settings.");
	if(pg_numrows($run_set) < 1){
		$setting = "default";
	}else {
		$sarr = pg_fetch_array($run_set);
		$setting = $sarr['filename'];
	}
	

//	$pdf =& new Cezpdf();
//	$pdf ->selectFont($set_mainFont);
//
//	# put a line top and bottom on all the pages
//	$all = $pdf->openObject();
//	$pdf->saveState();
//	$pdf->setStrokeColor(0,0,0,1);
//
//	# just a new line
//	$pdf->ezText("<b>Tax Invoice</b>", $set_txtSize+3, array('justification'=>'centre'));
//
//	$pdf->line(20,40,578,40);
//	#$pdf->line(20,822,578,822);
//	$pdf->addText(20,34,6,'Cubit Accounting');
//	$pdf->restoreState();
//	$pdf->closeObject();
//
//	# note that object can be told to appear on just odd or even pages by changing 'all' to 'odd'
//	# or 'even'.
//	$pdf->addObject($all,'all');
//	/* /Start PDF Layout */
//


##################################################################
	$pdf =& new Cezpdf();
	$pdf ->selectFont($set_mainFont);

	# put a line top and bottom on all the pages
	$all = $pdf->openObject();
	$pdf->saveState();
	$pdf->setStrokeColor(0,0,0,1);

	# just a new line
	$pdf->ezText("<b>Tax Invoice</b>", $set_txtSize+3, array('justification'=>'centre'));

	$pdf->line(20,40,578,40);
	#$pdf->line(20,822,578,822);
	$pdf->addText(20,34,6,'Cubit Accounting');
	$pdf->restoreState();
	$pdf->closeObject();

	# note that object can be told to appear on just odd or even pages by changing 'all' to 'odd'
	# or 'even'.
	$pdf->addObject($all,'all');
	/* /Start PDF Layout */


##################################################################
	db_connect();
	$sql = "SELECT * FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");

	$sql = "SELECT * FROM nons_invoices WHERE cusid='$cusnum' AND div='".USER_DIV."' $ex";
	$nonsinvRslt = pg_exec($sql) or errDie("Error reading nons stock invoices.");

//	$none=true;
	$i = 0;

	/*******************************************************************************
	****
	****				STOCK INVOICES
	****
	********************************************************************************/

	if($type=="unpaid"||$type=="all") {

		
		while($inv = pg_fetch_array($invRslt)){
			$invid = $inv['invid'];
			
			
			if ($setting == "default"){
				

				
				$none=false;
				$products = array();
				$invdet = array();
				$amtdat = array();
				$comments = array();
				$vatdat = array();
	
				/* --- Start some checks --- */
	
				# check if stock was selected(yes = put done button)
				db_connect();
				$sql = "SELECT stkid FROM inv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
				$crslt = db_exec($sql);
				if(pg_numrows($crslt) < 1){
					continue;
				}
	
				# Create a new page for customer
				if($i > 0)
					$pdf->newPage();
	
				/* --- End some checks --- */
	
				/* --- Start Products Display --- */
	
				# Products layout
				$products = "";
				$disc = 0;
				# get selected stock in this invoice
				db_connect();
				$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
				$stkdRslt = db_exec($sql);
	
				# they product display arrays
				$products = array();
				$prodhead = array('stkcod' => 'ITEM NUMBER', 'stkdes' => 'DESCRIPTION', 'qty' => 'QTY', 'selamt' => 'UNIT PRICE', 'disc' => 'DISCOUNT', 'amt' => 'AMOUNT');
				$taxex = 0;
				while($stkd = pg_fetch_array($stkdRslt)){
					# get warehouse name
					db_conn("exten");
					$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
					$whRslt = db_exec($sql);
					$wh = pg_fetch_array($whRslt);
		
					# get selected stock in this warehouse
					db_connect();
					$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
					$stkRslt = db_exec($sql);
					$stk = pg_fetch_array($stkRslt);
	
					$sp = "";
					# Check Tax Excempt
					if($stk['exvat'] == 'yes'){
						$ex = "#";
					}else{
						$ex = "";
					}
	
					# keep track of discounts
					$disc += $stkd['disc'];
				
					if ($stkd["account"] > 0) {
						$description = $stkd["description"];
					} else {
						$description = $stk["stkdes"];
					}
	
					# put in product
					$products[] = array('stkcod' => $stk['stkcod'], 'stkdes' => "$ex $sp".pdf_lstr($description), 'qty' => $stkd['qty'], 'selamt' => CUR." $stkd[unitcost]", 'disc' => CUR." $stkd[disc]", 'amt' => CUR." $stkd[amt]");
				}
	
					/* --- Start Some calculations --- */
	
				# subtotal
				$SUBTOT = sprint($inv['subtot']);
		
				# Calculate tradediscm
				if(strlen($inv['traddisc']) > 0){
					$traddiscm = sprint((($inv['traddisc']/100) * $SUBTOT));
				}else{
					$traddiscm = "0.00";
				}
	
				# Calculate subtotal
				$VATP = TAX_VAT;
				$SUBTOT = sprint($inv['subtot']);
				$VAT = sprint($inv['vat']);
				$TOTAL = sprint($inv['total']);
				$inv['delchrg'] = sprint($inv['delchrg']);
	
	
				# Update number of prints
				$inv['prints']++;
				db_connect();
				$Sql = "UPDATE invoices SET prints = '$inv[prints]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
				$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");
	
				/*
				# minus discount
				# $SUBTOT -= $disc; --> already minused
	
				# duplicate
				$SUBTOTAL = $SUBTOT;
	
				# minus trade discount
				$SUBTOTAL -= $traddiscm;
	
				# add del charge
				$SUBTOTAL += $inv['delchrg'];
	
	
				# If vat must be charged
				if($inv['chrgvat'] == "yes"){
					$VATP = TAX_VAT;
					$VAT = sprintf("%01.2f", (($VATP/100) * ($SUBTOTAL - $taxex)));
				}else{
					$VATP = 0;
					$VAT = "0.00";
				}
	
				# total
				$TOTAL = sprint($SUBTOTAL + $VAT);
				*/
		
				/* --- End Some calculations --- */
	
				/* -- Final PDF output layout -- */
	
				# set y so its away from the top
				$pdf->ezSetY($set_tlY);
	
				# Customer details
				$pdf->addText($set_tlX, $set_tlY, $set_txtSize-2, "$inv[surname]");
				$nl = pdf_addnl($pdf, $set_tlX, $set_tlY, $set_txtSize-2, $inv['cusaddr']);
				$pdf->addText($set_tlX, $set_tlY - ($set_txtSize * $nl), $set_txtSize-2, "(Vat No. $inv[cusvatno])");
	
				# Company details
				$pdf->addText($set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_NAME);
				$nl = pdf_addnl($pdf, $set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_PADDRR);
				$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * $nl), $set_txtSize-2, COMP_TEL);
				$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+1)), $set_txtSize-2, COMP_FAX);
				$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+2)), $set_txtSize-2, "Reg No. ".COMP_REGNO);
				$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+3)), $set_txtSize-2, "VAT No. ".COMP_VATNO);
	
			
				if($inv['chrgvat']=="inc") {
					$inv['chrgvat']="Inclusive";
				} elseif($inv['chrgvat']=="exc") {
					$inv['chrgvat']="Exclusive";
				} else {
					$inv['chrgvat']="No vat";
				}
				# Invoice details data
				$invdet[] = array('tit' => 'Invoice No.', 'val' => $inv['invnum']);
				$invdet[] = array('tit' => 'Order No.', 'val' => $inv['ordno']);
				$invdet[] = array('tit' => 'Terms', 'val' => "$inv[terms] Days");
				$invdet[] = array('tit' => 'Invoice Date', 'val' => $inv['odate']);
				$invdet[] = array('tit' => 'Vat', 'val' => $inv['chrgvat']);
	
				# invoice details
				$pdf->ezTable($invdet,'',"",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));
		
				# just a new line
				$pdf->ezText("\n", $set_txtSize);
		
				# set y so its away from the customer details
				$pdf->ezSetY($set_tlY - ($set_txtSize * ($nl+3)));
		
		
				# products table
				$pdf->ezTable($products, $prodhead,'', $set_maxTblOpt);
		
				# Total amounts
				$amtdat[] = array('tit' => 'SUBTOTAL', 'val' => CUR." $SUBTOT");
				$amtdat[] = array('tit' => 'Trade Discount', 'val' => CUR." $traddiscm");
				$amtdat[] = array('tit' => "VAT @ $VATP%", 'val' => CUR." $VAT");
				$amtdat[] = array('tit' => "Delivery Charge", 'val' => CUR." $inv[delchrg]");
				$amtdat[] = array('tit' => "GRAND TOTAL", 'val' => CUR." $TOTAL");
	
				# just a new line
				$pdf->ezText("\n", 7);
		
				# amounts details table data
				$pdf ->ezTable($amtdat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));
		
				# just a new line
				$pdf->ezSetDy(100);
				$pdf->ezText("\n", $set_txtSize);
		
				$comments[] = array('tit' => "Comments", 'val' => wordwrap($inv['comm'], 16));
		
				# VAT Number Table
				$pdf ->ezTable($comments, '', "",array('showLines'=> 5, 'showHeadings' => 0, 'xPos' => 79));
		
				$pdf->ezSetDy(-20);
	
				$vatdat[] = array('tit' => "VAT Exempt indicator", 'val' => "#");
				// $vatdat[] = array('tit' => "VAT No.", 'val' => COMP_VATNO);
		
				# VAT Number Table
				$pdf ->ezTable($vatdat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => 79));
				$i++;
			}else {

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
						$Sl="SELECT * FROM settings WHERE constant='SALES'";
						$Ri=db_exec($Sl) or errDie("Unable to get settings.");
					
						$data=pg_fetch_array($Ri);
					
						db_conn('cubit');
					
						$Sl="SELECT * FROM settings WHERE constant='SALES'";
						$Ri=db_exec($Sl) or errDie("Unable to get settings.");
					
						$data=pg_fetch_array($Ri);
					
						if($data['value']=="Yes") {
							$sp="<b>Sales Person: </b>$inv[salespn]";
						} else {
							$sp="";
						}
					
						$docinfo = array (
							array ("<b>Invoice No:</b> $inv[invnum]"),
							array ("<b>Proforma Inv No:</b> $inv[docref]"),
							array ("<b>Sales Order No:</b> $inv[ordno]"),
							array ("$sp")
						);
						if (isset($salespn)) {
							$docinfo[] = array("<b>Sales Person:</b> $salespn");
						}
					
						// Retrieve the customer information -------------------------------------
						db_conn("cubit");
						$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
						$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
						$cusData = pg_fetch_array($cusRslt);
					
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
					
						$cusinfo[] = array("<b>Account no: </b>$cusData[accno]");
					
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
					
							$items[] = array(
								"Code"=>makewidth($pdf, 75, 12, $stk['stkcod']),
								"Description"=>makewidth($pdf, 175, 12, $ex.$description),
								"Qty"=>$stkd['qty'],
								"Unit Price"=>$curr.$stkd['unitcost'],
								"Unit Discount"=>$curr.$stkd['disc'],
								"Amount"=>$curr.$stkd['amt']
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
							array ("1"=>"<b>Subtotal:</b> ", "2"=>$curr."$inv[subtot]"),
							array ("1"=>"<b>Trade Discount:</b> ", "2"=>$curr."$inv[discount]"),
							array ("1"=>"<b>Delivery Charge:</b> ", "2"=>$curr."$inv[delivery]"),
							array ("1"=>"<b>VAT $vat14:</b> ", "2"=>$curr."$inv[vat]"),
							array ("1"=>"<b>Total Incl VAT:</b> ", "2"=>$curr."$inv[total]")
						);
						$totCols = array (
							"1"=>array("width"=>90),
							"2"=>array("justification"=>"right")
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
							drawText(&$pdf, "<b>$comp[compname]</b>", 18, 0, ($heading_pos['y']/2)+6);
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
								"Code"=>array("width"=>80),
								"Description"=>array("width"=>180),
								"Qty"=>array("width"=>33),
								"Unit Price"=>array("width"=>80, "justification"=>"right"),
								"Unit Discount"=>array("width"=>67, "justification"=>"right"),
								"Amount"=>array("width"=>80, "justification"=>"right")
							);
					
							$items_pos = drawTable(&$pdf, $items_print, 0, $regnos_pos['y']+2, 520, 22, $cols, 1);
							$comment_pos = drawTable(&$pdf, $comment, 0, $items_pos['y'], 520, 2);
							$sign_pos = drawTable(&$pdf, $sign, 0, $comment_pos['y'], 320, 5);
							$totals_pos = drawTable(&$pdf, $totals, $sign_pos['x'], $comment_pos['y'], 200, 5, $totCols);
					
						}

			}
		}
	}

	if($type=="paid"||$type=="all") {

		for($p=1;$p<13;$p++) {

			db_conn($p);
		
			$sql = "SELECT * FROM invoices WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
			$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
			
			while($inv = pg_fetch_array($invRslt)){
				$invid = $inv['invid'];
				$none=false;
				$products = array();
				$invdet = array();
				$amtdat = array();
				$comments = array();
				$vatdat = array();
		
				/* --- Start some checks --- */
		
				# check if stock was selected(yes = put done button)
				db_conn($p);
				$sql = "SELECT stkid FROM inv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
				$crslt = db_exec($sql);
				if(pg_numrows($crslt) < 1){
					continue;
				}
		
				# Create a new page for customer
				if($i > 0)
					$pdf->newPage();
		
				/* --- End some checks --- */
		
				/* --- Start Products Display --- */
		
				# Products layout
				$products = "";
				$disc = 0;
				# get selected stock in this invoice
				db_conn($p);
				$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
				$stkdRslt = db_exec($sql);
		
				# they product display arrays
				$products = array();
				$prodhead = array('stkcod' => 'ITEM NUMBER', 'stkdes' => 'DESCRIPTION', 'qty' => 'QTY', 'selamt' => 'UNIT PRICE', 'disc' => 'DISCOUNT', 'amt' => 'AMOUNT');
				$taxex = 0;
				while($stkd = pg_fetch_array($stkdRslt)){
					# get warehouse name
					db_conn("exten");
					$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
					$whRslt = db_exec($sql);
					$wh = pg_fetch_array($whRslt);
		
					# get selected stock in this warehouse
					db_connect();
					$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
					$stkRslt = db_exec($sql);
					$stk = pg_fetch_array($stkRslt);
		
					$sp = "";
					# Check Tax Excempt
					if($stk['exvat'] == 'yes'){
						$ex = "#";
					}else{
						$ex = "";
					}
		
					# keep track of discounts
					$disc += $stkd['disc'];
					
					if ($stkd["account"] > 0) {
						$description = $stkd["description"];
					} else {
						$description = $stk["stkdes"];
					}
		
					# put in product
					$products[] = array('stkcod' => $stk['stkcod'], 'stkdes' => "$ex $sp".pdf_lstr($description), 'qty' => $stkd['qty'], 'selamt' => CUR." $stkd[unitcost]", 'disc' => CUR." $stkd[disc]", 'amt' => CUR." $stkd[amt]");
				}
		
					/* --- Start Some calculations --- */
		
				# subtotal
				$SUBTOT = sprint($inv['subtot']);
		
				# Calculate tradediscm
				if(strlen($inv['traddisc']) > 0){
					$traddiscm = sprint((($inv['traddisc']/100) * $SUBTOT));
				}else{
					$traddiscm = "0.00";
				}
		
				# Calculate subtotal
				$VATP = TAX_VAT;
				$SUBTOT = sprint($inv['subtot']);
				$VAT = sprint($inv['vat']);
				$TOTAL = sprint($inv['total']);
				$inv['delchrg'] = sprint($inv['delchrg']);
		
		
				# Update number of prints
				$inv['prints']++;
				db_connect();
				$Sql = "UPDATE invoices SET prints = '$inv[prints]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
				$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");
		
				/* -- Final PDF output layout -- */
		
				# set y so its away from the top
				$pdf->ezSetY($set_tlY);
		
				# Customer details
				$pdf->addText($set_tlX, $set_tlY, $set_txtSize-2, "$inv[surname]");
				$nl = pdf_addnl($pdf, $set_tlX, $set_tlY, $set_txtSize-2, $inv['cusaddr']);
				$pdf->addText($set_tlX, $set_tlY - ($set_txtSize * $nl), $set_txtSize-2, "(Vat No. $inv[cusvatno])");
		
				# Company details
				$pdf->addText($set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_NAME);
				$nl = pdf_addnl($pdf, $set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_PADDRR);
				$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * $nl), $set_txtSize-2, COMP_TEL);
				$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+1)), $set_txtSize-2, COMP_FAX);
				$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+2)), $set_txtSize-2, "Reg No. ".COMP_REGNO);
				$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+3)), $set_txtSize-2, "VAT No. ".COMP_VATNO);
		
				
				if($inv['chrgvat']=="inc") {
					$inv['chrgvat']="Inclusive";
				} elseif($inv['chrgvat']=="exc") {
					$inv['chrgvat']="Exclusive";
				} else {
					$inv['chrgvat']="No vat";
				}
				# Invoice details data
				$invdet[] = array('tit' => 'Invoice No.', 'val' => $inv['invnum']);
				$invdet[] = array('tit' => 'Order No.', 'val' => $inv['ordno']);
				$invdet[] = array('tit' => 'Terms', 'val' => "$inv[terms] Days");
				$invdet[] = array('tit' => 'Invoice Date', 'val' => $inv['odate']);
				$invdet[] = array('tit' => 'Vat', 'val' => $inv['chrgvat']);
		
				# invoice details
				$pdf->ezTable($invdet,'',"",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));
		
				# just a new line
				$pdf->ezText("\n", $set_txtSize);
		
				# set y so its away from the customer details
				$pdf->ezSetY($set_tlY - ($set_txtSize * ($nl+3)));
		
		
				# products table
				$pdf->ezTable($products, $prodhead,'', $set_maxTblOpt);
		
				# Total amounts
				$amtdat[] = array('tit' => 'SUBTOTAL', 'val' => CUR." $SUBTOT");
				$amtdat[] = array('tit' => 'Trade Discount', 'val' => CUR." $traddiscm");
				$amtdat[] = array('tit' => "VAT @ $VATP%", 'val' => CUR." $VAT");
				$amtdat[] = array('tit' => "Delivery Charge", 'val' => CUR." $inv[delchrg]");
				$amtdat[] = array('tit' => "GRAND TOTAL", 'val' => CUR." $TOTAL");
		
				# just a new line
				$pdf->ezText("\n", 7);
		
				# amounts details table data
				$pdf ->ezTable($amtdat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));
		
				# just a new line
				$pdf->ezSetDy(100);
				$pdf->ezText("\n", $set_txtSize);
		
				$comments[] = array('tit' => "Comments", 'val' => wordwrap($inv['comm'], 16));
		
				# VAT Number Table
				$pdf ->ezTable($comments, '', "",array('showLines'=> 5, 'showHeadings' => 0, 'xPos' => 79));
		
				$pdf->ezSetDy(-20);
		
				$vatdat[] = array('tit' => "VAT Exempt indicator", 'val' => "#");
				// $vatdat[] = array('tit' => "VAT No.", 'val' => COMP_VATNO);
		
				# VAT Number Table
				$pdf ->ezTable($vatdat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => 79));
				$i++;
			}
		}
	}

	/*******************************************************************************
	****
	****				NON STOCK INVOICES
	****
	********************************************************************************/

	while ( $inv = pg_fetch_array($nonsinvRslt) ) {
		$none=false;
		$invid = $inv["invid"];
		$products = array();
		$invdet = array();
		$amtdat = array();
		$comments = array();
		$vatdat = array();
		
		# check if stock was selected(yes = put done button)
		db_connect();
		$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$crslt = db_exec($sql);
		if(pg_numrows($crslt) < 1){
			continue;
		}

		if($i > 0)
			$pdf->newPage();

		# Products layout
		$products = "";
		$disc = 0;
		# get selected stock in this invoice
		db_connect();
		$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
	
		# they product display arrays
		$products = array();
		$prodhead = array('stkdes' => 'DESCRIPTION', 'qty' => 'QTY', 'selamt' => 'UNIT PRICE', 'amt' => 'AMOUNT');
		while($stkd = pg_fetch_array($stkdRslt)){
			# put in product
			
			if($stkd['vatex'] == 'y'){
				$stkd['description']="# ".$stkd['description'];
				$ex = "#";
			}else{
				$ex = "&nbsp;&nbsp;";
			}
			
			$products[] = array('stkdes' => "".pdf_lstr($stkd['description']), 'qty' => $stkd['qty'], 'selamt' => CUR." $stkd[unitcost]", 'amt' => CUR." $stkd[amt]");
		}
	
		/* --- Start Some calculations --- */
	
		# Subtotal
		$VATP = TAX_VAT;
		$SUBTOT = sprint($inv['subtot']);
		$VAT = sprint($inv['vat']);
		$TOTAL = sprint($inv['total']);
	
	/* -- Final PDF output layout -- */
	
		# just a new line
//		$pdf->ezText("<b>Tax Invoice\nReprint<b>", $set_txtSize+3, array('justification'=>'centre'));
	
		# set y so its away from the top
		$pdf->ezSetY($set_tlY);

		$set_txtSize -= 2;
		# Customer details
		$pdf->addText($set_tlX, $set_tlY, $set_txtSize, "$inv[cusname]");
		$nl = pdf_addnl($pdf, $set_tlX, $set_tlY, $set_txtSize, trim($inv['cusaddr']));
		$pdf->addText($set_tlX, $set_tlY - ($set_txtSize * $nl), $set_txtSize, "(Vat No. $inv[cusvatno])");
		$pdf->addText($set_tlX, $set_tlY - ($set_txtSize * ($nl+1)), $set_txtSize, "Customer Order Number: $inv[cordno]");
	
		if ( $nl > 7 ) {
			$geninc = $nl - 7;
		} else {
			$geninc = 0;
		}
		
		# Company details
		$pdf->addText($set_pgXCenter, $set_tlY, $set_txtSize, COMP_NAME);
		$nl = pdf_addnl($pdf, $set_pgXCenter, $set_tlY, $set_txtSize, COMP_ADDRESS);
		$pdf->addText($set_pgXCenter, $set_tlY - ($set_txtSize * $nl), $set_txtSize, COMP_TEL);
		$pdf->addText($set_pgXCenter, $set_tlY - ($set_txtSize * ($nl+1)), $set_txtSize, COMP_FAX);
		$pdf->addText($set_pgXCenter, $set_tlY - ($set_txtSize * ($nl+2)), $set_txtSize, "Reg No. ".COMP_REGNO);
		$pdf->addText($set_pgXCenter, $set_tlY - ($set_txtSize * ($nl+3)), $set_txtSize, "VAT No. ".COMP_VATNO);

		if ( $nl - 7 > $geninc ) {
		//	$geninc = $nl - 6;
		}
	
		$set_txtSize += 2;
		# Invoice details data
		$invdet[] = array('tit' => 'Invoice No.', 'val' => $inv['invnum']);
		$invdet[] = array('tit' => 'Invoice Date', 'val' => $inv['sdate']);
	
		# invoice details
		$pdf->ezTable($invdet,'',"",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));
	
		# just a new line
		$pdf->ezText("\n", $set_txtSize);
	
		# set y so its away from the customer details
		$pdf->ezSetY($set_tlY - ($set_txtSize * ($nl+4+$geninc)));
	
		# products table
		$pdf->ezTable($products, $prodhead,'', $set_maxTblOpt);
	
		# Total amounts
		$amtdat[] = array('tit' => 'SUBTOTAL', 'val' => CUR." $SUBTOT");
		$amtdat[] = array('tit' => "VAT @ $VATP%", 'val' => CUR." $VAT");
		$amtdat[] = array('tit' => "GRAND TOTAL", 'val' => CUR." $TOTAL");
	
		# just a new line
		$pdf->ezText("\n", 7);
	
		# Amounts details table data
		$pdf ->ezTable($amtdat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));
	
		
		
		# just a new line
		$pdf->ezSetDy(80);
		$pdf->ezText("\n", $set_txtSize);
	
		$bank=str_replace("<br>","\n",BNK_BANKDET);
		
		
		$comments[] = array('tit' => "Comments", 'val' => wordwrap($inv['remarks'], 16));
	
		# VAT Number Table
		$pdf ->ezTable($comments, '', "",array('showLines'=> 5, 'showHeadings' => 0, 'xPos' => 89));
		
		$pdf->ezSetDy(-20);
		
		$vatdat[] = array('tit' => "VAT Exempt indicator", 'val' => "#");
		// $vatdat[] = array('tit' => "VAT No.", 'val' => COMP_VATNO);
	
		# VAT Number Table
		$pdf ->ezTable($vatdat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => 79));
	}

	if(!isset($none))
		$none = "";

	if ($none) {
		if($type=="all") {
			$type="";
		}
		$OUTPUT = "<li class=err>Selected customer doesn't have any $type invoices</li>
			<input type=button value='[X] Close' onClick='javascript:window.close();'>";
		require("../template.php");
	}


	$pdf ->ezStream();

/* -- End Final PDF Layout -- */

}


?>