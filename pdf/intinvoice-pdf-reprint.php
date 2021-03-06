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
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

# decide what to do
if (isset($_GET["invid"])) {
	details($_GET);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
	require("../template.php");
}

# details
function details($_GET)
{

	# get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		$OUTPUT = $confirm;
		require("../template.php");
	}

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# check if invoice has been printed
	if($inv['printed'] != "y"){
		$error = "<li class=err> Error : Invoice number <b>$invid</b> has not been printed yet.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		$OUTPUT = $error;
		require("../template.php");
	}

	/* --- Start some checks --- */

	# check if stock was selected(yes = put done button)
	db_connect();
	$sql = "SELECT stkid FROM inv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class=err> Error : Invoice number <b>$invid</b> has no items.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		$OUTPUT = $error;
		require("../template.php");
	}

	/* --- End some checks --- */

	/* Start PDF Layout */
	include("../pdf-settings.php");
	$pdf =& new Cezpdf();
	$pdf ->selectFont($set_mainFont);

	# put a line top and bottom on all the pages
	$all = $pdf->openObject();
	$pdf->saveState();
	$pdf->setStrokeColor(0,0,0,1);
	$pdf->line(20,40,578,40);
	#$pdf->line(20,822,578,822);
	$pdf->addText(20,34,6,'Cubit Accounting');
	$pdf->restoreState();
	$pdf->closeObject();

	# note that object can be told to appear on just odd or even pages by changing 'all' to 'odd'
	# or 'even'.
	$pdf->addObject($all,'all');

	/* /Start PDF Layout */


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

		# put in product
		$products[] = array('stkcod' => $stk['stkcod'], 'stkdes' => "$ex $sp".pdf_lstr($stk['stkdes']), 'qty' => $stkd['qty'], 'selamt' => $inv['currency']." $stkd[unitcost]", 'disc' => $inv['currency']." $stkd[disc]", 'amt' => $inv['currency']." $stkd[amt]");
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

	if($inv['chrgvat']=="inc") {
		$inv['chrgvat']="Inclusive";
	} elseif($inv['chrgvat']=="exc") {
		$inv['chrgvat']="Exclusive";
	} else {
		$inv['chrgvat']="No vat";
	}

	# just a new line
	$pdf->ezText("<b>Tax Invoice\nReprint $inv[prints]<b>", $set_txtSize+3, array('justification'=>'centre'));

	# set y so its away from the top
	$pdf->ezSetY($set_tlY);

	# Customer details
	$pdf->addText($set_tlX, $set_tlY, $set_txtSize, "$inv[surname]");
	$nl = pdf_addnl($pdf, $set_tlX, $set_tlY, $set_txtSize, $inv['cusaddr']);
	$pdf->addText($set_tlX, $set_tlY - ($set_txtSize * $nl), $set_txtSize, "(Vat No. $inv[cusvatno])");

	# Company details
	$pdf->addText($set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_NAME);
	$nl = pdf_addnl($pdf, $set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_PADDRR);
	$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * $nl), $set_txtSize-2, COMP_TEL);
	$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+1)), $set_txtSize-2, COMP_FAX);
	$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+2)), $set_txtSize-2, "Reg No. ".COMP_REGNO);
	$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+3)), $set_txtSize-2, "VAT No. ".COMP_VATNO);

	# Invoice details data
	$invdet[] = array('tit' => 'Invoice No.', 'val' => $inv['invnum']);
	$invdet[] = array('tit' => 'Proforma Inv No.', 'val' => $inv['docref']);
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
	$amtdat[] = array('tit' => 'SUBTOTAL', 'val' => $inv['currency']." $SUBTOT");
	$amtdat[] = array('tit' => 'Trade Discount', 'val' => $inv['currency']." $inv[discount]");
	$amtdat[] = array('tit' => "Delivery Charge", 'val' => $inv['currency']." $inv[delivery]");
	$amtdat[] = array('tit' => "VAT @ $VATP%", 'val' => $inv['currency']." $VAT");
	$amtdat[] = array('tit' => "GRAND TOTAL", 'val' => $inv['currency']." $TOTAL");

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

	$pdf ->ezStream();

/* -- End Final PDF Layout -- */

}
?>
