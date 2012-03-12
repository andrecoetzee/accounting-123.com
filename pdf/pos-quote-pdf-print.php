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
if (isset($_GET["quoid"])) {
	details($_GET);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
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
	$v->isOk ($quoid, "num", 1, 20, "Invalid quote number.");

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



	# Get quote info
	db_connect();
	$sql = "SELECT * FROM pos_quotes WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
	$quoRslt = db_exec ($sql) or errDie ("Unable to get quote information");
	if (pg_numrows ($quoRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$quo = pg_fetch_array($quoRslt);

	# format date
	$quo['odate'] = explode("-", $quo['odate']);
	$quo['odate'] = $quo['odate'][2]."-".$quo['odate'][1]."-".$quo['odate'][0];


	/* --- Start some checks --- */

	# check if stock was selected(yes = put done button)
	db_connect();
	$sql = "SELECT stkid FROM pos_quote_items WHERE quoid = '$quo[quoid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class='err'> Error : Quote number <b>$quoid</b> has no items.";
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
	# get selected stock in this quote
	db_connect();
	$sql = "SELECT * FROM pos_quote_items  WHERE quoid = '$quoid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	# they product display arrays
	$products = array();
	$prodhead = array('stkcod' => 'ITEM NUMBER', 'stkdes' => 'DESCRIPTION', 'qty' => 'QTY', 'selamt' => 'UNIT PRICE', 'amt' => 'AMOUNT');
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

		$sp = "    ";
		# Check Tax Excempt
		if($stk['exvat'] == 'yes'){
			$ex = "#";
		}else{
			$ex = "  ";
		}

		# keep track of discounts
		$disc += $stkd['disc'];

		// Stock or non stock description?
		if ($stkd["account"] > 0) {
			$description = $stkd["description"];
		} else {
			$description = $stk["stkdes"];
		}

		# put in product
		$products[] = array('stkcod' => $stk['stkcod'], 'stkdes' => "$ex $sp ".pdf_lstr($description), 'qty' => $stkd['qty'], 'selamt' => CUR." $stkd[unitcost]", 'amt' => CUR." $stkd[amt]");
	}

		/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = sprint($quo['subtot']);

	# Calculate tradediscm
	if(strlen($quo['traddisc']) > 0){
		$traddiscm = sprint((($quo['traddisc']/100) * $SUBTOT));
	}else{
		$traddiscm = "0.00";
	}

	# Calculate subtotal
	$VATP = TAX_VAT;
	$SUBTOT = sprint($quo['subtot']);
 	$VAT = sprint($quo['vat']);
	$TOTAL = sprint($quo['total']);
	$quo['delchrg'] = sprint($quo['delchrg']);

	/* --- End Some calculations --- */

/* -- Final PDF output layout -- */

	# just a new line
	$pdf->ezText("<b>Quote<b>", $set_txtSize+3, array('justification'=>'centre'));

	# set y so its away from the top
	$pdf->ezSetY($set_tlY);

	# Customer details
	$pdf->addText($set_tlX, $set_tlY, $set_txtSize, "$quo[cusname]");
	$nl = pdf_addnl($pdf, $set_tlX, $set_tlY, $set_txtSize, $quo['cusaddr']);
	$pdf->addText($set_tlX, $set_tlY - ($set_txtSize * $nl), $set_txtSize, "");

	# Company details
	$pdf->addText($set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_NAME);
	$nl = pdf_addnl($pdf, $set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_ADDRESS);
	$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * $nl), $set_txtSize-2, COMP_TEL);
	$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+1)), $set_txtSize-2, COMP_FAX);
	$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+2)), $set_txtSize-2, "VAT No. ".COMP_VATNO);

	# Quote details data
	$quodet[] = array('tit' => 'Quote No.', 'val' => $quo['quoid']);
	$quodet[] = array('tit' => 'Order No.', 'val' => $quo['ordno']);
	$quodet[] = array('tit' => 'Terms', 'val' => "$quo[terms] Days");
	if($quo['salespn'] != "General")
		$quodet[] = array('tit' => 'Sales Person', 'val' => $quo['salespn']);
	$quodet[] = array('tit' => 'Quote Date', 'val' => $quo['odate']);

	# quote details
	$pdf->ezTable($quodet,'',"",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));

	# just a new line
	$pdf->ezText("\n", $set_txtSize);

	# set y so its away from the customer details
	$pdf->ezSetY($set_tlY - ($set_txtSize * ($nl+3)));


	# products table
	$pdf->ezTable($products, $prodhead,'', $set_maxTblOpt);

	# Total amounts
	$amtdat[] = array('tit' => 'SUBTOTAL', 'val' => CUR." $SUBTOT");
	$amtdat[] = array('tit' => 'Trade Discount', 'val' => CUR." $quo[discount]");
	$amtdat[] = array('tit' => "Delivery Charge", 'val' => CUR." $quo[delivery]");
	$amtdat[] = array('tit' => "VAT @ $VATP%", 'val' => CUR." $VAT");
	$amtdat[] = array('tit' => "GRAND TOTAL", 'val' => CUR." $TOTAL");

	# just a new line
	$pdf->ezText("\n", 7);

	# amounts details table data
	$pdf ->ezTable($amtdat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));

	# just a new line
	$pdf->ezSetDy(100);
	$pdf->ezText("\n", $set_txtSize);

	$comments[] = array('tit' => "Comments", 'val' => "$quo[comm]");

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