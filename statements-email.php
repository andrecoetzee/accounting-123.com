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

require("settings.php");
require_lib("mail.smtp");
require ("pdf-settings.php");

if(isset($_POST["key"])&&$_POST["key"]=="confirm") {
	$OUTPUT = confirm($_POST);
} elseif(isset($_POST["key"])&&$_POST["key"]=="send") {
	$OUTPUT = send($_POST);
} elseif(isset($_GET["cids"])) {
	$OUTPUT = enter($_GET);
} else {
	$OUTPUT ="Please select at least one customer.";
}

	$OUTPUT.= "
			<p>
			<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
			</table>";

require("template.php");


function enter($_GET)
{

	$es = qryEmailSettings();

	extract($_GET);

	$types = "
			<select name='type'>
				<option value='Date range'>Date Range</option>
				<option value='Monthly'>Monthly Statement</option>
			</select>";

	$out = "
			<h3>Statements to be e-mailed</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
				<tr>
					<th colspan='3'>Date Range</th>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center' colspan='3'>
						".mkDateSelect("from",date("Y"),date("m"),"01")."
						&nbsp;&nbsp;&nbsp; TO &nbsp;&nbsp;&nbsp;
						".mkDateSelect("to")."
					</td>
				</tr>
				".TBL_BR."
				<tr>
					<td class='err' colspan='3'>IMPORTANT: Please Select Correct Type</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Type</td>
					<td colspan='2'>$types</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<th>Acc Num</th>
					<th>Customer</th>
					<th>Email Address</th>
				</tr>";

	$i=0;

	db_conn('cubit');


	foreach($cids as $id)
	{

		$id+=0;

		$Sl="SELECT accno,surname,email FROM customers WHERE cusnum='$id'";
		$Ri=db_exec($Sl);

		$cd=pg_fetch_array($Ri);

		$out .= "
				<tr class='".bg_class()."'>
					<td>$cd[accno]</td>
					<td>$cd[surname]</td>
					<td><input type='text' name='email[$id]' value='$cd[email]' size='30'></td>
				</tr>
				<input type='hidden' name='cids[]' value='$id'>";
		$i++;
	}

	$out .= "
			".TBL_BR."
			<tr>
				<th colspan='3'>Message That Will Display In The Email</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='3'><textarea name='body' cols='40' rows='5'>Statement attached in pdf format.</textarea></td>
			</tr>
			<tr>
				<td colspan='3' align='right'><input type=submit value='Confirm Emails &raquo;'></td>
			</tr>
		</form>
		</table>";

	return $out;
}


function confirm($_GET)
{

	extract($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
	$v->isOk ($body, "string", 1,800, "Invalid to Email Message.");

	# mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
	}

	$type=remval($type);

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();

		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}


	$out = "
			<h3>Statements to be e-mailed: $fromdate TO $todate, $type</h3>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='send'>
				<input type='hidden' name='from_day' value='$from_day'>
				<input type='hidden' name='from_month' value='$from_month'>
				<input type='hidden' name='from_year' value='$from_year'>
				<input type='hidden' name='to_day' value='$to_day'>
				<input type='hidden' name='to_month' value='$to_month'>
				<input type='hidden' name='to_year' value='$to_year'>
				<input type='hidden' name='type' value='$type'>
				<input type='hidden' name='body' value='$body'>
				<tr>
					<th>Acc Num</th>
					<th>Customer</th>
					<th>Email Address</th>
				</tr>";

	$i=0;

	db_conn('cubit');

	foreach($cids as $id) {
		$id+=0;

		$Sl="SELECT accno,surname,email FROM customers WHERE cusnum='$id'";
		$Ri=db_exec($Sl);

		$cd=pg_fetch_array($Ri);

		$out .= "
				<tr class='".bg_class()."'>
					<td>$cd[accno]</td>
					<td>$cd[surname]</td>
					<td>${email[$id]}</td>
				</tr>
				<input type='hidden' name='cids[]' value='$id'>
				<input type='hidden' name='email[$id]' value='${email[$id]}'>";
		$i++;
	}

	$out .= "
			".TBL_BR."
			<tr>
				<th colspan='3'>Message That Will Display In The Email</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='3'>".nl2br($body)."</td>
			</tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo Correction'></td>
				<td colspan='2' align='right'><input type='submit' value='Send Emails &raquo;'></td>
			</tr>
		</form>
		</table>";

	return $out;
}


function send($_POST)
{

	extract($_POST);

	if(isset($back))
		return enter ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
	$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
	$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
	$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
	$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
	$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");
	$v->isOk ($body, "string", 1,800, "Invalid to Email Message.");

	# mix dates
	$fromdate = $from_year."-".$from_month."-".$from_day;
	$todate = $to_year."-".$to_month."-".$to_day;

	if(!checkdate($from_month, $from_day, $from_year)){
			$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
	}
	if(!checkdate($to_month, $to_day, $to_year)){
			$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
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



	$out = "
			<h3>Results</h3>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Acc Num</th>
					<th>Customer</th>
					<th>Email Address</th>
					<th>Result</th>
				</tr>";

	$i=0;

	db_conn('cubit');

	foreach($cids as $id) {
		$id+=0;

		$Sl="SELECT accno,surname,email FROM customers WHERE cusnum='$id'";
		$Ri=db_exec($Sl);

		$cd=pg_fetch_array($Ri);

		$out .= "
				<tr class='".bg_class()."'>
					<td>$cd[accno]</td>
					<td>$cd[surname]</td>
					<td>${email[$id]}</td>
					<td>".sendstate($id,$fromdate,$todate,$type,$email[$id],$body)."</td>
				</tr>";

		$i++;
	}

	$out.="</table>";

	return $out;
}


function sendstate($id,$fromdate,$todate,$type, $email,$body="Statement attached in pdf format.")
{

	db_conn('cubit');

	$es = qryEmailSettings();

	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid id.");

	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	$Sl="SELECT * FROM customers WHERE cusnum='$id'";
	$Ri=db_exec($Sl);

	$cd=pg_fetch_array($Ri);

	if(strlen($email) <1) {
		return "This customer does not have an email address";
	}

//	$body = "Statement attached in pdf format.";

	$send_cc = "";
	$send_bcc = "";

	$smtp_data['signature']=$es['sig'];
	$smtp_data['smtp_from']=$es['fromname'];
	$smtp_data['smtp_reply']=$es['reply'];
	$smtp_data['smtp_host']=$es['smtp_host'];
	$smtp_data['smtp_auth']=$es['smtp_auth'];
	$smtp_data['smtp_user']=$es['smtp_user'];
	$smtp_data['smtp_pass']=$es['smtp_pass'];

	// build msg body
	$body = "$body\n\n$smtp_data[signature]";

	// determine whether or not here is an attachment
	//$has_attachment = is_uploaded_file($attachment["tmp_name"]);
	$has_attachment = true;
	// modify message and create content_type header depending on whether or not an attachment was posted
	if ( $has_attachment == false ) {
		$content_type = "text/html;charset=US-ASCII";
		$transfer_encoding = "8bit";
	} else { // has attachment
		$content_type = "multipart/mixed";

		// create the main body
		$body_text = "Content-Type: text/plain; charset=US-ASCII\n";
		$body_text .= "Content-Transfer-Encoding: base64\n";
		$body_text .= "\n" . chunk_split(base64_encode($body));

		// get the attachment data
		$attachment = Array();
		$attachment["data"] = state($id,$fromdate,$todate,$type);
		$attachment["name"] = "statement.pdf";

		// delete the temporary file

		$attachment["data"] = chunk_split(base64_encode($attachment["data"]));

		$attachment["headers"] = "Content-Type: application/x-pdf; name=\"$attachment[name]\"\n";
		$attachment["headers"] .= "Content-Transfer-Encoding: base64\n";
		$attachment["headers"] .= "Content-Disposition: attachment; filename=\"$attachment[name]\"\n";

		$attachment["data"] = "$attachment[headers]\n$attachment[data]";

		// generate a unique boundary ( md5 of filename + ":=" + filesize )
		$boundary = md5($attachment["name"]) . "=:" . strlen($attachment["data"]);
		$content_type .= "; boundary=\"$boundary\"";

		// put together the body
		$body = "\n--$boundary\n$body_text\n\n--$boundary\n$attachment[data]\n\n--$boundary--\n";
	}

	// build headers
	$headers[] = "From: $smtp_data[smtp_from]";
	$headers[] = "To: $email";
	$headers[] = "Reply-To: $smtp_data[smtp_reply]";
	$headers[] = "X-Mailer: Cubit Mail";
	$headers[] = "Return-Path: $smtp_data[smtp_reply]";
	$headers[] = "Content-Type: $content_type";
	$headers[] = "cc: $send_cc";
	$headers[] = "bcc: $send_bcc";

	// create the mime header if should
	if ( $has_attachment == TRUE ) {
		$headers[] = "MIME-Version: 1.0";
	}

	// create the header variable (it is done this way, to make management of headers easier, since there
	// may be no tabs and unnecesary whitespace in mail headers)
	//$headers[] = "\n"; // add another new line to finish the headers
	$headers = implode("\n", $headers);

	//return "done";
        // send the message
	$sendmail = & new clsSMTPMail;
	$OUTPUT = $sendmail->sendMessages($smtp_data["smtp_host"], 25, $smtp_data["smtp_auth"], $smtp_data["smtp_user"],
		$smtp_data["smtp_pass"],$email, $smtp_data["smtp_from"], "Statement", $body, $headers);

	/*if ( mail($send_to, $subject, $body, $headers) == TRUE )
		$OUTPUT = "Successfully sent mail to $send_to.<br>";
	else
		$OUTPUT = "Error sending mail.<br>";*/

	return $OUTPUT;

}


function state ($id,$fromdate,$todate,$type )
{

	$fdate=$fromdate;

	global $set_mainFont, $set_codeFont, $set_pgWidth, $set_pgHeight, $set_pgXCenter, $set_pgYCenter, $set_tlX, $set_tlY, $set_txtSize, $set_ttlY, $set_maxTblOpt, $set_maxTblOptNl, $set_repTblOpt, $set_repTblOptSm, $set_tubTblOpt, $set_tubTblOpt2, $set_tubTblOpt3;

	# Get selected customer info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$id' AND div = '".USER_DIV."'";
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

	if($type=="Monthly") {
		$fdate = date("Y")."-".date("m")."-"."01";
		$whe="";
	} else {
		$whe="AND date<='$todate'";
	}


	if(!(open())) {
		# Query server
		$sql = "SELECT * FROM stmnt WHERE cusnum = '$id' AND date >= '$fdate' AND div = '".USER_DIV."' $whe ORDER BY date";
		$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	} else {
		# Query server
		$sql = "SELECT * FROM open_stmnt WHERE cusnum = '$id' AND balance != '0' AND div = '".USER_DIV."' $whe ORDER BY date";
		$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	}
	if (pg_numrows ($stRslt) < 1) {
		$stmnt[] = array('date' => "No invoices/transactions for this month.", 'invid' => " ", 'type' => " ", 'amount' => " ");
	}else{
		while ($st = pg_fetch_array ($stRslt)) {
			# format date
			$st['date'] = explode("-", $st['date']);
			$st['date'] = $st['date'][2]."-".$st['date'][1]."-".$st['date'][0];
			$st['amount']=sprint($st['amount']);

			if(substr($st['type'],0,7)=="Invoice") {
				$ex="INV";
			} elseif(substr($st['type'],0,21)=="Non Stock Credit Note") {
				$ex="CR";
			} elseif(substr($st['type'],0,17)=="Non-Stock Invoice") {
				$ex="INV";
			} elseif(substr($st['type'],0,11)=="Credit Note") {
				$ex="CR";
			} else {
				$ex="";
			}

			$stmnt[] = array('date' => $st['date'], 'invid' => $ex." ".$st['invid'], 'type' => $st['type'], 'amount' => "$cust[currency] $st[amount]");

			# keep track of da totals
			$totout += $st['amount'];
		}
	}

	if($cust['location'] == 'int')
		$cust['balance'] = $cust['fbalance'];

	$balbf = ($cust['balance'] - $totout);
	$balbf = sprint($balbf);
	$cust['balance']=sprint($cust['balance']);

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
	}


	$stmnthead = array('date' => "Date", 'invid' => "Ref No.", 'type' => "Details", 'amount' => "Amount");

	$agehead = array('cur' => "Current", '30' => "30 days", '60' => "60 days", '90' => "90 days", '120' => "120 days +");
	$age=array();
	$age[] = array('cur' => "$curr", '30' => "$age30", '60' => "$age60", '90' => "$age90", '120' => "$age120");

	/* Start PDF Layout */

	//include("pdf-settings.php");
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

	if ($type == "Monthly"){
		$pdf->ezText(date("Y-m-") . "01 - " . date("Y-m-d",mktime(0,0,0,date("m")+1,-0,date("Y"))), $set_txtSize, array('justification'=>'centre'));
	}else {
		$pdf->ezText("$fromdate - $todate", $set_txtSize, array('justification'=>'centre'));
	}

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
	$cusdet[] = array('tit' => "$cust[surname]\n$cust[addr1]");
	$cusdet[] = array('tit' => "Balance Brought Forward : $cust[currency] $balbf");

	# customer details table
	$pdf->ezTable($cusdet, '', "", array('shaded' => 0, 'showLines' => 2, 'showHeadings' => 0, 'xPos' => 94));

	# just a new line
	$pdf->ezText("\n", $set_txtSize);

	# Statement table
	$pdf->ezTable($stmnt, $stmnthead,'', $set_maxTblOptNl);

	# just a new line
	$pdf->ezText("\n", $set_txtSize);

	# balance table data
	$baldat[] = array('tit' => "Total Outstanding Balance : $cust[currency] $cust[balance]");

	//$pdf->ezSetY($set_tlY -200);

	# balance table
	$pdf ->ezTable($baldat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-63)));

	$pdf->ezText("\n", $set_txtSize);

	if($type=="Monthly") {
		$pdf->ezTable($age, $agehead,'', $set_maxTblOptNl);
	}

	return $pdf->output();
	exit;

	# Send stream
	$pdf ->ezStream();
	exit();

}

/*
function ssm($cid,$fd,$td)
{
	$cid+=0;
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($fd, "string", 8, 10, "Invalid from date");
	$v->isOk ($td, "string", 8, 10, "Invalid to date");

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
	$sql = "SELECT * FROM customers WHERE cusnum = '$cid' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($custRslt) < 1) {
		return "<li class=err>Invalid Customer Number.";
	}
	$cust = pg_fetch_array($custRslt);

	if($cust['location'] == 'int')
		$cust['balance'] = $cust['fbalance'];

	# connect to database
	db_connect ();
	$stmnt = "";
	$totout = 0;

	# Query server
	$sql = "SELECT * FROM stmnt WHERE cusnum = '$cid' AND date >= '$fd' AND date <= '$td' AND div = '".USER_DIV."' ORDER BY date ASC";
	$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	if (pg_numrows ($stRslt) < 1) {
		$stmnt .= "<tr><td colspan=4>No previous invoices for this month.</td></tr>";
	}else{
		while ($st = pg_fetch_array ($stRslt)) {
			# format date
			$st['date'] = explode("-", $st['date']);
			$st['date'] = $st['date'][2]."-".$st['date'][1]."-".$st['date'][0];
			$st['amount']=sprint($st['amount']);

			if(substr($st['type'],0,7)=="Invoice") {
				$ex="INV";
			} elseif(substr($st['type'],0,17)=="Non-Stock Invoice") {
				$ex="INV";
			} elseif(substr($st['type'],0,21)=="Non Stock Credit Note") {
				$ex="CR";
			} elseif(substr($st['type'],0,11)=="Credit Note") {
				$ex="CR";
			} else {
				$ex="";
			}

			$stmnt .= "<tr><td align=center>$st[date]</td><td align=center>$ex $st[invid]</td><td align=center>$st[docref]</td><td>$st[type]</td><td align=right>$cust[currency] $st[amount]</td></tr>";

			# keep track of da totals
			$totout += $st['amount'];
		}
	}

	db_connect ();
	# get overlapping amount
	$sql = "SELECT sum(amount) as amount FROM stmnt WHERE cusnum = '$cid' AND date > '$td' AND div = '".USER_DIV."' ";
	$balRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
	$bal = pg_fetch_array ($balRslt);
	$cust['balance'] = ($cust['balance'] - $bal['amount']);

	$balbf = ($cust['balance'] - $totout);
	$balbf = sprint($balbf);
	$cust['balance'] = sprint($cust['balance']);

	# Check type of age analisys
	if(div_isset("DEBT_AGE", "mon")){
		$curr = ageage($cust['cusnum'], 0);
		$age30 = ageage($cust['cusnum'], 1);
		$age60 = ageage($cust['cusnum'], 2);
		$age90 = ageage($cust['cusnum'], 3);
		$age120 = ageage($cust['cusnum'], 4);
	}else{
		$curr = age($cust['cusnum'], 29);
		$age30 = age($cust['cusnum'], 59);
		$age60 = age($cust['cusnum'], 89);
		$age90 = age($cust['cusnum'], 119);
		$age120 = age($cust['cusnum'], 149);
	}

	$age = "<table cellpadding='3' cellspacing='1' border=0 width=100% bordercolor='#000000'>
		<tr><th>Current</th><th>30 days</th><th>60 days</th><th>90 days</th><th>120 days +</th></tr>
		<tr><td align=right>$cust[currency] $curr</td><td align=right>$cust[currency] $age30</td><td align=right>$cust[currency] $age60</td><td align=right>$cust[currency] $age90</td><td align=right>$cust[currency] $age120</td></tr>
		</table>";

	// Layout
	$printStmnt = "<center><h2>Customer Statement</h2>
	<h3>$fd -- $td</h3></center>
	<table cellpadding='3' cellspacing='0' border=0 width=750 bordercolor='#000000'>
		<tr></td><td valign=top width=70%>
			<font size=5><b>".COMP_NAME."</b></font><br>
			".COMP_ADDRESS."<br>
			".COMP_PADDR."
		</td><td>
			COMPANY REG. ".COMP_REGNO."<br>
			TEL : ".COMP_TEL."<br>
			FAX : ".COMP_FAX."<br>
			VAT REG.".COMP_VATNO."<br>
		</td></tr>
	</table>
	<p>
	<table cellpadding='3' cellspacing='0' border=1 width=400 bordercolor='#000000'>
		<tr><th width=60%><b>Account No.</b></th><td width=40%>$cust[accno]</th></tr>
		<tr><td colspan=2>
			<font size=4><b>$cust[cusname] $cust[surname]</b></font><br>
			".nl2br($cust['addr1'])."<br>
		</td></tr>
		<tr><td><b>Balance Brought Forward</b></td><td>$cust[currency] $balbf</td>
	</table>
		<p>
	<table cellpadding='3' cellspacing='0' border=0 width=750 bordercolor='#000000'>
		<tr><th>Date</th><th>Ref No.</th><th>Proforma Inv No.</th><th>Details</th><th>Amount</th></tr>
		$stmnt
		<tr><td><br></td></tr>
		<tr><td colspan=4 align=right>
			<table cellpadding='3' cellspacing='0' border=1 width=300 bordercolor='#000000'>
				<tr><th><b>Total Outstanding</b></th><td colspan=2>$cust[currency] $cust[balance]</td></tr>
			</table>
		</td></tr>
		<tr><td><br></td></tr>
		<tr><td><br></td></tr>
		<tr><td colspan=4><!--age--></td></tr>
		<tr><td><br></td></tr>
	</table>
	<p>";

	# return $printStmnt;
	$body = $printStmnt;

	db_conn('cubit');

	$es = qryEmailSettings();

	$Sl="SELECT * FROM customers WHERE cusnum='$cid'";
	$Ri=db_exec($Sl);

	$cd=pg_fetch_array($Ri);

	if(strlen($cd['email']) <1) {
		return "This customer does not have an email address";
	}


	//$send_cc="mg@mailbox.co.za";
	//$send_bcc="mg@mailbox.co.za";
	$send_cc="";
	$send_bcc="";

	$smtp_data['signature']=$es['sig'];
	$smtp_data['smtp_from']=$es['fromname'];
	$smtp_data['smtp_reply']=$es['reply'];
	$smtp_data['smtp_host']=$es['smtp_host'];
	$smtp_data['smtp_auth']=$es['smtp_auth'];
	$smtp_data['smtp_user']=$es['smtp_user'];
	$smtp_data['smtp_pass']=$es['smtp_pass'];


	//db_conn('cubit');

// 	$rslt = db_exec("SELECT smtp_from, smtp_reply, signature, smtp_host, smtp_auth, smtp_user, smtp_pass
// 		FROM mail_accounts");
// 	$smtp_data = pg_fetch_array($rslt);


	// build msg body
	$body = "$body\n\n$smtp_data[signature]";

	// determine whether or not here is an attachment
	//$has_attachment = is_uploaded_file($attachment["tmp_name"]);
	$has_attachment =FALSE;
	// modify message and create content_type header depending on whether or not an attachment was posted
	if ( $has_attachment == FALSE ) {
		$content_type = "text/html;charset=US-ASCII";
		$transfer_encoding = "8bit";
	} else { // has attachment
		$content_type = "multipart/mixed";

		// create the main body
		$body_text = "Content-Type: text/plain; charset=US-ASCII\n";
		$body_text .= "Content-Transfer-Encoding: base64\n";
		$body_text .= "\n" . base64_encode($body);

		// get the attachment data
		if ( ($fd = fopen($attachment["tmp_name"], "r")) == TRUE ) {
			$attachment_data = "";
			while ( ! feof($fd) ) {
				$attachment_data .= fgets( $fd, 4096 );
			}
			fclose($fd);

			// delete the temporary file
			unlink($attachment["tmp_name"]);

			$attachment_data = base64_encode($attachment_data);

			$attachment_headers = "Content-Type: $attachment[type]; name=\"$attachment[name]\"\n";
			$attachment_headers .= "Content-Transfer-Encoding: base64\n";
			$attachment_headers .= "Content-Disposition: attachment; filename=\"$attachment[name]\"\n";

			$attachment_data = "$attachment_headers\n$attachment_data";
		} else { // error opening the attachment file
			$attachment_data = "";
		}

		// generate a unique boundary ( md5 of filename + ":=" + filesize )
		$boundary = md5($attachment["name"]) . "=:" . $attachment["size"];
		$content_type .= "; boundary=\"$boundary\"";

		// put together the body
		$body = "\n--$boundary\n$body_text\n\n--$boundary\n$attachment_data\n\n--$boundary--\n
		";
	}

	// build headers
	$headers[] = "From: $smtp_data[smtp_from]";
	$headers[] = "Reply-To: $smtp_data[smtp_reply]";
	$headers[] = "X-Mailer: Cubit Mail";
	$headers[] = "Return-Path: $smtp_data[smtp_reply]";
	$headers[] = "Content-Type: $content_type";
	$headers[] = "cc: $send_cc";
	$headers[] = "bcc: $send_bcc";

	// create the mime header if should
	if ( $has_attachment == TRUE ) {
		$headers[] = "MIME-Version: 1.0";
	}

	// create the header variable (it is done this way, to make management of headers easier, since there
	// may be no tabs and unnecesary whitespace in mail headers)
	//$headers[] = "\n"; // add another new line to finish the headers
	$headers = implode("\n", $headers);

	//return "done";
        // send the message
	$sendmail = & new clsSMTPMail;
	$OUTPUT = $sendmail->sendMessages($smtp_data["smtp_host"], 25, $smtp_data["smtp_auth"], $smtp_data["smtp_user"],
		$smtp_data["smtp_pass"],$cd['email'], $smtp_data["smtp_from"], "Statement: $fd TO $td", $body, $headers);

	//if ( mail($send_to, $subject, $body, $headers) == TRUE )
	//	$OUTPUT = "Successfully sent mail to $send_to.<br>";
	//else
	//	$OUTPUT = "Error sending mail.<br>";

	return $OUTPUT;


}*/


function age($cusnum, $days)
{

	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	# Get the current oustanding
	$sql = "SELECT sum(balance) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND odate >='".extlib_ago($ldays)."' AND odate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM custran WHERE cusnum = '$cusnum' AND odate >='".extlib_ago($ldays)."' AND odate <'".extlib_ago($days-30)."' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint(($sum['sum'] + $sumb ['sum'] ) + 0);
}


function ageage($cusnum, $age){
	# Get the current oustanding
	$sql = "SELECT sum(balance) FROM invoices WHERE cusnum = '$cusnum' AND printed = 'y' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	# Get the current oustanding on transactions
	$sql = "SELECT sum(balance) FROM custran WHERE cusnum = '$cusnum' AND age = '$age' AND div = '".USER_DIV."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sumb = pg_fetch_array($rs);

	# Take care of nasty zero
	return sprint(($sum['sum'] + $sumb ['sum']) + 0);
}


?>