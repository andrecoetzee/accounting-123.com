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
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

require("../template.php");

# show invoices
function printStmnt ($HTTP_GET_VARS)
{

	# get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 20, "Invalid Customer number.");

	if (isset($from_day)) {
		$BYDATE = true;

		$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
		$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
		$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
		$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
		$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
		$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
		# mix dates
		$fromdate = $from_year."-".$from_month."-".$from_day;
		$todate = $to_year."-".$to_month."-".$to_day;

		if(!checkdate($from_month, $from_day, $from_year)){
				$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
		}
		if(!checkdate($to_month, $to_day, $to_year)){
				$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
		}
	} else {
		$BYDATE = false;
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}

	if ($BYDATE) {
		$bdfilter = "date >= '$fromdate' AND date <= '$todate'";
		$heading = "Period Range Statement : $fromdate - $todate";
	} else {
		$fdate = date("Y")."-".date("m")."-"."01";
		$bdfilter = "date >= '$fdate'";
		$heading = "Monthly Statement";
	}


	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		return "<li class='err'>Invalid Customer Number.</li>";
	}
	$cust = pg_fetch_array($custRslt);

	# connect to database
	db_connect ();
	$fdate = date("Y")."-".date("m")."-"."01";
	$stmnt = array();
	$totout = 0;

	#check for sort ...
	if(isset($sort) AND $sort == "branch"){
		$sortinga = "ORDER BY branch";
		$sorting = "branch,";
	}else {
		$sortinga = "";
		$sorting = "";
	}

	if(!(open())) {
		# Query server
		$sql = "SELECT * FROM stmnt WHERE cusnum = '$cusnum' AND $bdfilter AND div = '".USER_DIV."' ORDER BY $sorting date ASC";
		$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
		if (pg_numrows ($stRslt) < 1) {
			//$stmnt .= "<tr><td colspan=4>No invoices for this month.</td></tr>";
		}else{
			while ($st = pg_fetch_array ($stRslt)) {
				# keep track of da totals
				$totout += $st['amount'];
			}
		}
	} else {
		# Query server
		$sql = "SELECT * FROM open_stmnt WHERE cusnum = '$cusnum' AND $bdfilter AND balance != '0' AND div = '".USER_DIV."' ORDER BY $sorting date ASC";
		$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
		if (pg_numrows ($stRslt) < 1) {
			//$stmnt .= "<tr><td colspan=4>No invoices for this month.</td></tr>";
		}else{
			while ($st = pg_fetch_array ($stRslt)) {
				# keep track of da totals
				$totout += $st['balance'];
			}
		}
	}

	// we need a way to get this for a date range selection as well .... 
	// balance brought forward == sum of all transactions before selected start date
	//	$balbf = ($cust['balance'] - $totout);
		if ($BYDATE) {
			$bdfilter2 = "date < '$fromdate'";
		} else {
			$fdate = date("Y")."-".date("m")."-"."01";
			$bdfilter2 = "date < '$fdate'";
		}

		if(!(open())) {
			$sql = "SELECT sum(amount) FROM stmnt WHERE cusnum = '$cusnum' AND $bdfilter2 AND div = '".USER_DIV."'";
			$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
			if (pg_numrows ($stRslt) < 1) {
				$stmnt .= "<tr><td colspan='4'>No invoices for this month.</td></tr>";
			}else{
				$st = pg_fetch_array ($stRslt);
			}
		}else {
			$sql = "SELECT sum(amount) FROM open_stmnt WHERE cusnum = '$cusnum' AND $bdfilter2 AND balance != '0' AND div = '".USER_DIV."'";
			$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
			if (pg_numrows ($stRslt) < 1) {
				$stmnt .= "<tr><td colspan='4'>No invoices for this month.</td></tr>";
			}else{
				$st = pg_fetch_array ($stRslt);
			}
		}
	
//	$balbf = ($cust['balance'] - $totout);
	$balbf = $st['sum'];
	$balbf = sprint($balbf);

	$rbal=$balbf;


	# Query server
	if (!open()) {
		db_conn("cubit");
		$sql = "SELECT * FROM stmnt WHERE cusnum = '$cusnum' AND $bdfilter AND div = '".USER_DIV."' ORDER BY $sorting date ASC";
		$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	} else {
		db_conn("cubit");
		$sql = "SELECT * FROM open_stmnt WHERE cusnum = '$cusnum' AND $bdfilter AND balance != '0' AND div = '".USER_DIV."' ORDER BY $sorting date ASC";
		$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	}

	if (pg_numrows ($stRslt) > 0) {
		while ($st = pg_fetch_array ($stRslt)) {
			if (!open()) {
				$amtbal = sprint($st['amount']);
				$rbal=sprint($rbal+$st['amount']);
			} else {
				$amtbal = sprint($st['balance']);
				$rbal=sprint($rbal+$st['balance']);
			}

			# format date
			$st['date'] = explode("-", $st['date']);
			$st['date'] = $st['date'][2]."-".$st['date'][1]."-".$st['date'][0];
			$st['amount']=sprint($st['amount']);

			if (substr($st['type'],0,7)=="Invoice") {
				$ex = "INV";
			} elseif(substr($st['type'],0,21)=="Non Stock Credit Note") {
				$ex = "CR";
			} elseif(substr($st['type'],0,17)=="Non-Stock Invoice") {
				$ex = "INV";
			} elseif(substr($st['type'],0,11)=="Credit Note") {
				$ex = "CR";
			} else {
				$ex = "";
			}

			$stmnt[] = grp(
				m('date', $st['date']),
				m('invid', $ex." ".$st['invid']),
				m('type', $st['type']),
				m('amount', "$cust[currency] $amtbal"),
				m('balance', "$cust[currency] $rbal")
			);

			# keep track of da totals
			//$totout += $amtbal;
		}
	}

	if ($cust['location'] == 'int') {
		$cust['balance'] = $cust['fbalance'];
	}

	//$balbf = ($cust['balance'] - $totout);
		if ($BYDATE) {
			$bdfilter2 = "date < '$fromdate'";
		} else {
			$fdate = date("Y")."-".date("m")."-"."01";
			$bdfilter2 = "date < '$fdate'";
		}
	
		if(!(open())) {
			$sql = "SELECT sum(amount) FROM stmnt WHERE cusnum = '$cusnum' AND $bdfilter2 AND div = '".USER_DIV."'";
			$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
			if (pg_numrows ($stRslt) < 1) {
				$stmnt .= "<tr><td colspan='4'>No invoices for this month.</td></tr>";
			}else{
				$st = pg_fetch_array ($stRslt);
			}
		}else {
			$sql = "SELECT sum(amount) FROM open_stmnt WHERE cusnum = '$cusnum' AND $bdfilter2 AND balance != '0' AND div = '".USER_DIV."'";
			$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
			if (pg_numrows ($stRslt) < 1) {
				$stmnt .= "<tr><td colspan='4'>No invoices for this month.</td></tr>";
			}else{
				$st = pg_fetch_array ($stRslt);
			}
		}

	$balbf = $st['sum'];
	$balbf = sprint($balbf);

	$cust['balance'] = sprint($cust['balance']);

	# Check type of age analisys
	if(div_isset("DEBT_AGE", "mon")){
		$curr = ageage($cust['cusnum'], 0, $cust['fcid'], $cust['location']);
		$age30 = ageage($cust['cusnum'], 1, $cust['fcid'], $cust['location']);
		$age60 = ageage($cust['cusnum'], 2, $cust['fcid'], $cust['location']);
		$age90 = ageage($cust['cusnum'], 3, $cust['fcid'], $cust['location']);
		$age120 = ageage($cust['cusnum'], 4, $cust['fcid'], $cust['location']);
	}else{
		$curr = age($cust['cusnum'], 29, $cust['fcid'], $cust['location']);
		$age30 = age($cust['cusnum'], 59, $cust['fcid'], $cust['location']);
		$age60 = age($cust['cusnum'], 89, $cust['fcid'], $cust['location']);
		$age90 = age($cust['cusnum'], 119, $cust['fcid'], $cust['location']);
		$age120 = age($cust['cusnum'], 149, $cust['fcid'], $cust['location']);

		$custtot = $curr + $age30 + $age60 + $age90 + $age120;

		if(sprint($custtot)!=sprint($cust['balance'])) {
			$curr=sprint($curr+$cust['balance']-$custtot);
			$custtot=sprint($cust['balance']);
		}
	}


	$stmnthead = array('date' => "Date", 'invid' => "Ref No.", 'type' => "Details", 'amount' => "Amount", 'balance' => "Balance");

	$agehead = array('cur' => "Current", '30' => "30", '60' => "60", '90' => "90", '120' => "120");
	$age=array();
	$age[] = array('cur' => "$curr", '30' => "$age30", '60' => "$age60", '90' => "$age90", '120' => "$age120");

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
	$pdf->ezText("<b>Customer $heading</b>", $set_txtSize+2, array('justification'=>'centre'));

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

	$address_ar = explode ("\n", $cust["addr1"]);
	$address_out = "";
	foreach ($address_ar as $addr) {
		$address_out .= makewidth($pdf, 175, 12, $addr);
	}

	# customer details data
	$cusdet[] = array('tit' => "Account number : $cust[accno]");
	$cusdet[] = array('tit' => "$cust[surname]\n $address_out");
	$cusdet[] = array('tit' => "Balance Brought Forward : $cust[currency] $balbf");

	# customer details table
	$pdf->ezTable($cusdet, '', "", array('shaded' => 0, 'showLines' => 2, 'showHeadings' => 0, 'xPos' => 100));

	$bnkData = qryBankAcct(getdSetting("BANK_DET"));

 	$banking = array (
 		array ("$bnkData[bankname]"),
 		array ("<b>Branch: </b>$bnkData[branchname]"),
  		array ("<b>Branch Code: </b>$bnkData[branchcode]"),
 		array ("<b>Account Number: </b>$bnkData[accnum]"),
 	);

 	global $set_pgHeight;
 	$pdf->ezSetY($set_tlY - ($set_txtSize * ($nl+1)));
	$pdf->ezTable($banking, '', "", array('shaded' => 0, 'showLines' => 2, 'showHeadings' => 0, 'xPos' => 300));

	# just a new line
	$pdf->ezText("\n", $set_txtSize);

	# Statement table
	if (count($stmnt) < 1) {
		$stmnt = array($stmnthead);
		//$pdf->ezTable($stmnthead, "", "", array_merge($set_maxTblOptNl, grp(m("showHeadings", 0))));
		$pdf->ezTable($stmnt, "",'', array_merge($set_maxTblOptNl, array("showHeadings" => 0)));

		$stmnt = array(grp(m("err", "No previous invoices/transactions for this month.")));
		$pdf->ezTable($stmnt, "",'', array_merge($set_maxTblOptNl, array("showHeadings" => 0)));
	} else {
		$pdf->ezTable($stmnt, $stmnthead,'', $set_maxTblOptNl);
	}


	# just a new line
	$pdf->ezText("\n", $set_txtSize);

	# balance table data
	//$cust[balance]
	$baldat[] = array('tit' => "Total Outstanding Balance : $cust[currency] $rbal");

	//$pdf->ezSetY($set_tlY -200);
	# balance table
	$pdf ->ezTable($baldat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-63)));

	$pdf->ezText("\n", $set_txtSize);

	$pdf->ezTable($age, $agehead,'', $set_maxTblOptNl);

	# Send stream
	$pdf ->ezStream();
	exit();
}


function age($cusnum, $days, $fcid, $loc){


	$rate = getRate($fcid);
	$bal = "balance";
	if($loc == 'int'){
		$bal = "fbalance";
		$rate = 1;
	}
	if($rate == 0) $rate = 1;

	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	# Get the current oustanding
	$sql = "SELECT sum($bal) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND odate >='".extlib_ago($ldays)."' AND odate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum($bal) FROM custran WHERE cusnum = '$cusnum' AND odate >='".extlib_ago($ldays)."' AND odate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint(($sum['sum'] + $sumb ['sum'] ) + 0);
}


function ageage($cusnum, $age, $fcid, $loc){

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