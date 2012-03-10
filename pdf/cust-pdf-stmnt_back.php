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
if (isset($HTTP_GET_VARS["cusnum"])) {
	$OUTPUT = printStmnt($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
}

require("../template.php");

# show invoices
function printStmnt ($HTTP_GET_VARS)
{
	# get vars
	foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return $err;
	}

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		return "<li class=err>Invalid Customer Number.";
	}
	$cust = pg_fetch_array($custRslt);

	# connect to database
	db_connect ();
	$fdate = date("Y")."-".date("m")."-"."01";
	$stmnt = array();
	$totout = 0;

	# Query server
	$sql = "SELECT * FROM stmnt WHERE cusnum = '$cusnum' AND date >= '$fdate' AND div = '".USER_DIV."' ";
	$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	if (pg_numrows ($stRslt) < 1) {
		$stmnt[] = array('date' => "No previous invoices/transactions for this month.", 'invid' => " ", 'type' => " ", 'amount' => " ");
	}else{
		while ($st = pg_fetch_array ($stRslt)) {
			# format date
			$st['date'] = explode("-", $st['date']);
			$st['date'] = $st['date'][2]."-".$st['date'][1]."-".$st['date'][0];
			$st['amount']=sprint($st['amount']);

			$stmnt[] = array('date' => $st['date'], 'invid' => $st['invid'], 'type' => $st['type'], 'amount' => CUR." $st[amount]");

			# keep track of da totals
			$totout += $st['amount'];
		}
	}

	$balbf = ($cust['balance'] - $totout);
	$balbf = sprint($balbf);
	$cust['balance']=sprint($cust['balance']);


	$stmnthead = array('date' => "Date", 'invid' => "Invoice No.", 'type' => "Details", 'amount' => "Amount");

	/* Start PDF Layout */

	include("../pdf-settings.php");
	$pdf =& new Cezpdf();
	$pdf ->selectFont($set_mainFont);

	# put a line top and bottom on all the pages
	$all = $pdf->openObject();
	$pdf->saveState();
	$pdf->setStrokeColor(0,0,0,1);
	$pdf->line(20,40,578,40);
	# $pdf->line(20,822,578,822);
	$pdf->addText(20,34,6,'Cubit Accounting');
	$pdf->restoreState();
	$pdf->closeObject();

	# note that object can be told to appear on just odd or even pages by changing 'all' to 'odd'
	# or 'even'.
	$pdf->addObject($all,'all');

	/* End PDF Layout */

	/* start PDF Layout */

	# Heading
	$pdf->ezText("<b>Customer Monthly Statement</b>", $set_txtSize+2, array('justification'=>'centre'));

	# Set y so its away from the top
	$pdf->ezSetY($set_tlY);

	# Company details
	$smTxtSz = ($set_txtSize-3);
	$pdf->addText($set_tlX, $set_tlY, $smTxtSz, COMP_NAME);
	$nl = pdf_addnl($pdf, $set_tlX, $set_tlY, $smTxtSz, COMP_ADDRESS);
	$pdf->addText($set_tlX, $set_tlY - ($smTxtSz * $nl), $smTxtSz, COMP_PADDR);

	# Company details cont
	$lrite = $set_pgXCenter + 60;
	$pdf->addText($lrite, $set_tlY - ($smTxtSz), $smTxtSz, "COMPANY REG. ".COMP_REGNO);
	$pdf->addText($lrite, $set_tlY - ($smTxtSz * 2), $smTxtSz, "TEL : ".COMP_TEL);
	$pdf->addText($lrite, $set_tlY - ($smTxtSz * 3), $smTxtSz, "FAX : ".COMP_FAX);
	$pdf->addText($lrite, $set_tlY - ($smTxtSz * 4), $smTxtSz, "VAT REG. ".COMP_VATNO);

	# Set y so its away from the company details
	$pdf->ezSetY($set_tlY - ($set_txtSize * ($nl+1)));

	# customer details data
	$cusdet[] = array('tit' => "Account number : $cust[accno]");
	$cusdet[] = array('tit' => "$cust[cusname]  $cust[surname]\n\n$cust[addr1]");
	$cusdet[] = array('tit' => "Balance Brought Forward : ".CUR." $balbf");

	# customer details table
	$pdf->ezTable($cusdet, '', "", array('shaded' => 0, 'showLines' => 2, 'showHeadings' => 0, 'xPos' => 94));

	# just a new line
	$pdf->ezText("\n", $set_txtSize);

	# Statement table
	$pdf->ezTable($stmnt, $stmnthead,'', $set_maxTblOptNl);

	# just a new line
	$pdf->ezText("\n", $set_txtSize);

	# balance table data
	$baldat[] = array('tit' => "Total Outstanding Balance : ".CUR." $cust[balance]");

	# balance table
	$pdf ->ezTable($baldat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-63)));

	# Send stream
	$pdf ->ezStream();
	exit();
}
?>
