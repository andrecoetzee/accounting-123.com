<?

	require ("../settings.php");
	
	$OUTPUT = printStmnt ($HTTP_GET_VARS);

	require ("../template.php");





function printStmnt ($HTTP_GET_VARS)
{

	# get vars
	extract ($HTTP_GET_VARS);

	$fields = array();
	$fields["creditor_balance"] = 0;

	extract ($fields, EXTR_SKIP);

	if (!is_numeric($creditor_balance))
		$creditor_balance = 0;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($payid, "num", 1, 20, "Invalid Supplier number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}




	db_connect ();

	#get payment info
	$get_pay = "SELECT * FROM supp_payment_print WHERE id = '$payid' LIMIT 1";
	$run_pay = db_exec($get_pay) or errDie ("Unable to get payment information.");
	if(pg_numrows($run_pay) < 1){
		return "<li class='err'>Unable to get payment information.</li>";
	}
	$parr = pg_fetch_array ($run_pay);


	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$parr[supid]' AND div = '".USER_DIV."'";
	$suppRslt = db_exec ($sql) or errDie ("Unable to view Supplier");
	if (pg_numrows ($suppRslt) < 1) {
		return "<li class='err'>Invalid Supplier Number.</li>";
	}
	$supp = pg_fetch_array($suppRslt);

	# connect to database
	db_connect ();
	$fdate = date("Y")."-".date("m")."-"."01";
	$stmnt = "";
	$totout = 0;

	# Query server
	$get_info = "SELECT * FROM supp_payment_print_items WHERE payment_id = '$payid' ORDER BY id";
	$run_info = db_exec($get_info) or errDie ("Unable to get paid item information.");
	if(pg_numrows($run_info) < 1){
		$stmnt = "
					<tr>
						<td>No Items Found.</td>
					</tr>
				";
	}else {
		$stmnt = "";
		while ($piarr = pg_fetch_array ($run_info)){
			$stmnt .= "
						<tr>
							<td align='right'>$piarr[purchase]</td>
							<td align='right'>$piarr[tdate]</td>
							<td align='right'>".CUR." $piarr[paid_amt]</td>
							<td align='right'>".CUR." $piarr[sett_amt]</td>
						</tr>
					";
		}
	}

	if($supp['location'] == 'int')
		$supp['balance'] = $supp['fbalance'];


	$balbf = ($supp['balance'] - $totout);
	$balbf = sprint($balbf);
	$totout = sprint($totout);
	$supp['balance'] = sprint($supp['balance']);


//	if(!isset($print)) {
//		$bottonz = "
//					<input type='button' value='[X] Close' onClick='javascript:parent.window.close();'> | 
//					<input type='button' value='View PDF' onClick=\"javascript:document.location.href='pdf/supp-pdf-stmnt.php?supid=$parr[supid]'\"> | 
//					<input type='button' value='View By Date Range' onClick=\"javascript:document.location.href='supp-stmnt-date.php?supid=$parr[supid]'\"> | 
//					<input type='button' value='Print' onClick=\"javascript:document.location.href='supp-stmnt.php?supid=$parr[supid]&print=yes'\">";
//	} else {
		$bottonz = "";
//	} 



	#DO RECON HERE
	$sql = "SELECT balance FROM cubit.recon_creditor_balances
			WHERE supid='$parr[supid]'";
	$cbalance_rslt = db_exec($sql) or errDie("Unable to retrieve creditor balance.");
	$creditor_balance = pg_fetch_result($cbalance_rslt, 0);

	$total_balance = sprint($supp["balance"] + $creditor_balance);
	$diff_balance = sprint($supp["balance"] - $creditor_balance);

	$sql = "SELECT date, reason, amount FROM cubit.recon_balance_ct
				LEFT JOIN cubit.recon_reasons
					ON recon_balance_ct.reason_id=recon_reasons.id
			WHERE supid='$parr[supid]' AND date>='$fdate'";
	$balance_rslt = db_exec($sql) or errDie("Unable to retrieve balances.");

	$balance_out = "";
	while (list($date, $reason, $amount) = pg_fetch_array($balance_rslt)) {
		$balance_out .= "
		<tr>
			<td>$date</td>
			<td>$reason</td>
			<td align='right'>$amount</td>
		</tr>";
	}
	
	$sql = "
	SELECT date, comment FROM cubit.recon_comments_ct
	WHERE supid='$supp[supid]' ORDER BY id DESC";
	$comments_rslt = db_exec($sql) or errDie("Unable to retrieve comments.");

	$comments_out = "";
	while ($comments_data = pg_fetch_array($comments_rslt)) {
		$comments_out .= "
		<tr>
			<td>$comments_data[date]</td>
			<td>".base64_decode(nl2br($comments_data["comment"]))."</td>
		</tr>";
	}
	#DONE RECON



	// Layout
	$printStmnt = "
	<center>
		<h2>Supplier Payment Reconciliation And Remittance Statement<h2>
	</center>
	<!--<table cellpadding='3' cellspacing='0' border='0' width='750' bordercolor='#000000'>
		<tr>
			<td valign='top' width='70%'>
				<font size='5'><b>".COMP_NAME."</b></font><br>
				".COMP_ADDRESS."<br>
				".COMP_PADDR."
			</td>
			<td>
				COMPANY REG. ".COMP_REGNO."<br>
				TEL : ".COMP_TEL."<br>
				FAX : ".COMP_FAX."<br>
				VAT REG.".COMP_VATNO."<br>
			</td>
		</tr>
	</table>-->
	<p>
	<table cellpadding='3' cellspacing='0' class='border' width='400' bordercolor='#000000'>
		<tr>
			<th width='60%'><b>Supplier No:</b> $supp[supno]</th>
			<td width='40%'><b>Branch:</b> $supp[branch]</th>
		</tr>
		<tr>
			<td colspan='2'>
				<font size='4'><b>$supp[supname]</b></font><br>
				".nl2br($supp['supaddr'])."<br>
			</td>
		</tr>
	</table>
	<p>
	<table cellpadding='3' cellspacing='0' class='border' width='650' bordercolor='#000000'>
		<tr>
			<td width='30%'>Tel: $supp[tel]</td>
			<td width='30%'>Fax:  $supp[fax]</td>
			<td width='40%'>Email:  $supp[email]</td>
		</tr>
	</table>
	<p>
	<table cellpadding='3' cellspacing='0' border=0 width=750 bordercolor='#000000'>
		<tr>
			<th class='thkborder'>Purchase</th>
			<th class='thkborder'>Payment Date</th>
			<th class='thkborder'>Paid Amount</th>
			<th class='thkborder'>Settlement Amount</th>
		</tr>
		$stmnt
		<tr><td>&nbsp;</td></tr>
		<tr><td>&nbsp;</td></tr>
	</table>
	<p></p>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<td valign='top'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='3' align='center'>
							<a href='recon_balance_ct.php?key=reason&supid=$supp[supid]'>
								Add/Remove/View Reasons
							</a>
						</th>
					</tr>
					<tr>
						<th>Date</th>
						<th>Reason</th>
						<th>Amount</th>
					</tr>
					$balance_out
				</table>
			</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td valign='top'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='3' align='center'>
							<a href='recon_balance_ct.php?key=comments&supid=$supp[supid]'>
								Add/Remove/View Comments
							</a>
						</th>
					</tr>
					<tr>
						<th>Date</th>
						<th>Comment</th>
					</tr>
					$comments_out
				</table>
			</td>
		</tr>
	</table>
	<p></p>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<td>Balance According to Cubit</td>
			<td align='right'>$supp[balance]</td>
		</tr>
		<tr>
			<td>Balance According to Creditor</td>
			<td align='right'>$creditor_balance</td>
		</tr>
		<tr>
			<td>Difference in amount</td>
			<td align='right'>$diff_balance</td>
		</tr>
	</table>
	</form>
	<p></p>
	$bottonz";

	// Retrieve template settings from Cubit
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='statements'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve the template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	$OUTPUT = $printStmnt;
	require("../tmpl-print.php");

}



?>