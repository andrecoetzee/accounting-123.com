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
	extract ($_GET);
	
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

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

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	// are we working with an international invoice?
	if ($inv["currency"] != "") {
		$currency = $inv["currency"];
	} else {
		$currency = CUR;
	}

	/* --- Start some checks --- */

	# check if stock was selected(yes = put done button)
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class='err'> Error : Invoice number <b>$inv[invnum]</b> has no items.";
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
		
		$products[] = array('stkdes' => "".pdf_lstr($stkd['description']), 'qty' => $stkd['qty'], 'selamt' => "$currency $stkd[unitcost]", 'amt' => "$currency $stkd[amt]");
	}

	/* --- Start Some calculations --- */

	# Subtotal
	$VATP = TAX_VAT;
	$SUBTOT = sprint($inv['subtot']);
	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

/* -- Final PDF output layout -- */

	# just a new line
	$pdf->ezText("<b>Tax Invoice\nReprint<b>", $set_txtSize+3, array('justification'=>'centre'));

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

	$set_txtSize += 2;
	# Invoice details data
	$invdet[] = array('tit' => 'Invoice No.', 'val' => $inv['invnum']);
	$invdet[] = array('tit' => 'Invoice Date', 'val' => $inv['sdate']);

	# invoice details
	$pdf->ezTable($invdet,'',"",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));

	# just a new line
	$pdf->ezText("\n", $set_txtSize);

	# set y so its away from the customer details
	$pdf->ezSetY($set_tlY - ($set_txtSize * ($nl+3+$geninc)));

	# products table
	$pdf->ezTable($products, $prodhead,'', $set_maxTblOpt);

	# Total amounts
	$amtdat[] = array('tit' => 'SUBTOTAL', 'val' => "$currency $SUBTOT");
	$amtdat[] = array('tit' => "VAT @ $VATP%", 'val' => "$currency $VAT");
	$amtdat[] = array('tit' => "GRAND TOTAL", 'val' => "$currency $TOTAL");

	# just a new line
	$pdf->ezText("\n", 7);

	# Amounts details table data
	$pdf ->ezTable($amtdat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));

	
	
	# just a new line
	$pdf->ezSetDy(80);
	$pdf->ezText("\n", $set_txtSize);

	$bank=str_replace("<br>","\n",BNK_BANKDET);
	
	
	$comments[] = array('tit' => "Comments", 'val' => wordwrap($inv['remarks'], 16));
	
	$banks[] = array('tit' => "Bank Details");
	$banks[] = array('tit' => "$bank");
	

	# VAT Number Table
	$pdf ->ezTable($comments, '', "",array('showLines'=> 5, 'showHeadings' => 0, 'xPos' => 89));
	
	$pdf ->ezTable($banks, '', "",array('showLines'=> 3, 'showHeadings' => 0, 'xPos' => 220));
	
	$pdf->ezSetDy(-20);
	
	$vatdat[] = array('tit' => "VAT Exempt indicator", 'val' => "#");
	// $vatdat[] = array('tit' => "VAT No.", 'val' => COMP_VATNO);

	# VAT Number Table
	$pdf ->ezTable($vatdat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => 79));

	$pdf ->ezStream();

/* -- End Final PDF Layout -- */

}


?>