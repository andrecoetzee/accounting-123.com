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
if (isset($HTTP_GET_VARS["invid"])) {
	details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
	require("../template.php");
}

# details
function details($HTTP_GET_VARS)
{

	# get vars
	foreach ($HTTP_GET_VARS as $key => $value) {
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
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	/* --- Start some checks --- */

	# check if stock was selected(yes = put done button)
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class=err> Error : Invoice number <b>$inv[invnum]</b> has no items.";
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
		$products[] = array('stkdes' => "".pdf_lstr($stkd['description']), 'qty' => $stkd['qty'], 'selamt' => $inv['currency']." $stkd[unitcost]", 'amt' => $inv['currency']." $stkd[amt]");
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

	# Customer details
	$pdf->addText($set_tlX, $set_tlY, $set_txtSize, "$inv[cusname]");
	$nl = pdf_addnl($pdf, $set_tlX, $set_tlY, $set_txtSize, $inv['cusaddr']);
	$pdf->addText($set_tlX, $set_tlY - ($set_txtSize * $nl), $set_txtSize, "(Vat No. $inv[cusvatno])");

	# Company details
	$pdf->addText($set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_NAME);
	$nl = pdf_addnl($pdf, $set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_ADDRESS);
	$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * $nl), $set_txtSize-2, COMP_TEL);
	$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+1)), $set_txtSize-2, COMP_FAX);
 	$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+2)), $set_txtSize-2, "Reg No. ".COMP_REGNO);
	$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+3)), $set_txtSize-2, "VAT No. ".COMP_VATNO);

	# Invoice details data
	$invdet[] = array('tit' => 'Invoice No.', 'val' => $inv['invnum']);
	$invdet[] = array('tit' => 'Invoice Date', 'val' => $inv['sdate']);

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
	$amtdat[] = array('tit' => "VAT @ $VATP%", 'val' => $inv['currency']." $VAT");
	$amtdat[] = array('tit' => "GRAND TOTAL", 'val' => $inv['currency']." $TOTAL");

	# just a new line
	$pdf->ezText("\n", 7);

	# Amounts details table data
	$pdf ->ezTable($amtdat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));

	# just a new line
	$pdf->ezSetDy(100);
	$pdf->ezText("\n", $set_txtSize);

	$pdf->ezSetDy(-20);

	$pdf ->ezStream();

/* -- End Final PDF Layout -- */

}
?>
