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
require("../core-settings.php");
require("../libs/ext.lib.php");

# decide what to do
if (isset($HTTP_POST_VARS["supid"])) {
	$OUTPUT = printStmnt($HTTP_POST_VARS);
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
}

require("../template.php");

# show invoices
function printStmnt ($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 20, "Invalid Supplier number.");
	$v->isOk ($fday, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($fmon, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($fyear, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($today, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($tomon, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($toyear, "num", 1,4, "Invalid to Date Year.");
	# mix dates
	$fromdate = $fyear."-".$fmon."-".$fday;
	$todate = $toyear."-".$tomon."-".$today;

	if(!checkdate($fmon, $fday, $fyear)){
		$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($tomon, $today, $toyear)){
		$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	# isplay errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return $err;
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec ($sql) or errDie ("Unable to view Supplier");
	if (pg_numrows ($suppRslt) < 1) {
		return "<li class=err>Invalid Supplier Number.";
	}
	$supp = pg_fetch_array($suppRslt);

	# Connect to database
	db_connect ();
	$fdate = date("Y")."-".date("m")."-"."01";
	$stmnt = array();
	$totout = 0;

	# Query server
	$sql = "SELECT * FROM sup_stmnt WHERE supid = '$supid' AND edate >= '$fromdate' AND edate <= '$todate' AND div = '".USER_DIV."' ORDER BY edate ASC";
	$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	if (pg_numrows ($stRslt) < 1) {
		$stmnt[] = array('date' => "No previous invoices/transactions for this month.", 'ref' => "  ", 'cacc' => "  ", 'descript' => "  ", 'amount' => "  ");
	}else{
		while ($st = pg_fetch_array ($stRslt)) {
			# format date
			$st['edate'] = explode("-", $st['edate']);
			$st['edate'] = $st['edate'][2]."-".$st['edate'][1]."-".$st['edate'][0];
			$st['amount']=sprint($st['amount']);

			# Accounts details
	    	if($st['cacc']>0){
	    		$accRs = get("core","*","accounts","accid",$st['cacc']);
    	    	$acc  = pg_fetch_array($accRs);
				$Dis ="$acc[topacc]/$acc[accnum] - $acc[accname]";
			}else{
				$Dis="Purchase Num: $st[ex]";
			}


			$stmnt[] = array('date' => $st['edate'], 'ref' => $st['ref'], 'cacc' => $Dis, 'descript' => "$st[descript]", 'amount' => CUR." $st[amount]");

			# keep track of da totals
			$totout += $st['amount'];
		}
	}

	# get overlapping amount
	db_connect ();
	$sql = "SELECT sum(amount) as amount FROM sup_stmnt WHERE supid = '$supid' AND edate > '$todate' AND div = '".USER_DIV."' ";
	$balRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	$bal = pg_fetch_array ($balRslt);
	$supp['balance'] = ($supp['balance'] - $bal['amount']);

	$balbf = ($supp['balance'] - $totout);
	$balbf = sprint($balbf);
	$totout = sprint($totout);
	$supp['balance'] = sprint($supp['balance']);

	$stmnthead = array('date' => "Date", 'ref' => "Ref", 'cacc' => "Contra Acc.", 'descript' => "Description", 'amount' => "Amount");

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
	$pdf->ezText("<b>Supplier Monthly Statement</b>", $set_txtSize+2, array('justification'=>'centre'));

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
	$cusdet[] = array('tit' => "Supplier number : $supp[supno]");
	$cusdet[] = array('tit' => "$supp[supname]\n\n$supp[supaddr]");
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
	$baldat[] = array('tit' => "Total Outstanding Balance : ".CUR." $supp[balance]");

	# balance table
	$pdf ->ezTable($baldat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-63)));

	# Send stream
	$pdf ->ezStream();
	exit();
}
?>
