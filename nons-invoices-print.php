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
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");
require("pdf-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "process":
			$OUTPUT = process($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			# decide what to do
			if (isset($_GET["invids"])) {
				$OUTPUT = details($_GET);
			} else {
				$OUTPUT = "<li class=err>Please select at least one unprinted recurring invoice.</li>";
			}
		}
} else {
	# decide what to do
	if (isset($_GET["ids"])) {
		$OUTPUT = details($_GET);
	} else {
		$OUTPUT = "<li class=err>Please select at least one unprinted recurring invoice.</li>";
	}
}

require("template.php");




function details($_GET)
{

	extract($_GET);

	$ids = explode(",",$ids);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($ids as $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
	}

	# display errors, if any
	if ($v->isError()) {
		$err = $v->genErrors();
		$confirm = "$err<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	/* --- Start Display --- */
	$printInv = "
	<h3>Confirm Invoice Printing Process</h3>
	<form action='".SELF."' method='POST'>
		<input type='hidden' name='key' value='process'>
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='6' class='err'>Please Note : This process might take long depending on the number of invoices. It is best to run it overnight.</td>
		</tr>
		<tr>
			<th>Invoice No.</th>
			<th>Invoice Date</th>
			<th>Customer Name</th>
			<th>Grand Total</th>
		</tr>";

	$i = 0;
	foreach($ids as $key => $invid){
		# Get recuring invoice info
		db_connect();
		$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."' AND done!='y'";
		$invRslt = db_exec ($sql) or errDie ("Unable to get recuring invoice information");
		if (pg_numrows ($invRslt) < 1) {
			return "<i class='err'>Invoice Not Found, Please make sure you have selected a unprinted invoice.</i>";
		}
		$inv = pg_fetch_array($invRslt);

		$bgColor = bgcolor($i);

		$inv['total']=sprint($inv['total']);
		$inv['balance']=sprint($inv['balance']);

		$printInv .= "
		<input type=hidden name='invids[]' value='$inv[invid]'>
		<tr class='".bg_class()."'>
			<td>T $inv[invid]</td>
			<td valign='center'>$inv[odate]</td>
			<td>$inv[cusname]</td>
			<td align=right>".CUR." $inv[total]</td>
		</tr>";
	}

	$bgColor = bgcolor($i);

	$printInv .= "<tr class='".bg_class()."'><td colspan=6 align=right>Totals Invoices : $i</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=6 align=right><input type=submit value='Process >>'></td></tr>
	</form></table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='rec-nons-invoice-new.php'>New Recurring Non-stock Invoice</a></td></tr>
	<tr class='bg-odd'><td><a href='rec-nons-invoice-view.php'>View Recurring Non-stock Invoices</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $printInv;
}

# Create the company
function process ($_POST) {
	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($invids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return $err;
	}


	$postvars = "";
	foreach($invids as $key => $invid){
		$postvars .= "<input type=hidden name='invids[]' value='$invid'>";
	}

	$OUTPUT = "
	<form action='".SELF."' method=post name=postvars>
	<input type=hidden name=key value=write>
	$postvars
	</form>
	<table width=100% height=100%>
		<tr>
			<td align=center valign=middle>
				<font size='2' color='white'>
				Please wait while the invoices are being processed. This may take several minutes.</font><br>
				<div id='wait_bar_parent' style='border: 1px solid black; width:100px'>
					<div id='wait_bar' style='font-size: 15pt'>...</div>
				</div>
			</td>
		</tr>
	</table>

	<script>
		wait_bar = getObjectById('wait_bar')
		function moveWaitBar() {
			if ( wait_bar.innerHTML == '...................')
				wait_bar.innerHTML = '.';
			else
				wait_bar.innerHTML = wait_bar.innerHTML + '.';

			setTimeout('moveWaitBar()', 50);
		}

		setTimeout('moveWaitBar()', 100);

		document.postvars.submit();
	</script>";

	return $OUTPUT;
}

# Details
function write($_POST)
{
	# Set max execution time to 12 hours
	ini_set("max_execution_time", 43200);

	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($invids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
	}
	$VATP = TAX_VAT;
	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return $err;
	}
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);


	$i = 0;
	foreach($invids as $key => $invid){

		db_connect();

		$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."' and done='n'";
		$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
		if (pg_numrows ($invRslt) < 1) {
			return "<i class=err>Not Found</i>";
		}
		$inv = pg_fetch_array($invRslt);

		$ctyp=$inv['ctyp'];

		//$td=$inv['sdate'];
		$td = $inv['odate'];
		//$cus['surname']=$inv['cusname'];

		if($ctyp == 's'){
			$cusnum=$inv['tval'];

			$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
			$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
			$cus = pg_fetch_array($custRslt);

			$na=$cus['surname'];

		}elseif($ctyp == 'c'){
			$deptid=$inv['tval'];
			db_conn("exten");
			$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
			$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
			$dept = pg_fetch_array($deptRslt);

			$na=$inv['cusname'];

		}

		db_connect();
		$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql) or errDie("Unable to get data.");

		unset($totstkamt);

		$refnum = getrefnum();
/*refnum*/

		/* - Start Hooks - */

		$vatacc = gethook("accnum", "salesacc", "name", "VAT","NO VAT");
		$varacc = gethook("accnum", "salesacc", "name", "sales_variance");

		/* - End Hooks - */
		db_conn("cubit");
		$real_invid = divlastid('inv', USER_DIV);
		db_conn("cubit");
		# Put in product
		$totstkamt = array();
		while($stk = pg_fetch_array($stkdRslt)){

			$Sl="SELECT * FROM vatcodes WHERE id='$stk[vatex]'";
			$Ri=db_exec($Sl) or errDie("Unable to get data.");

			$vd=pg_fetch_array($Ri);

			if($vd['zero']=="Yes") {
				$stk['vatex']="y";
			}

			$t=$inv['chrgvat'];

			$stkacc=$stk['accid'];

			if(isset($totstkamt[$stkacc])){
				if($stk['vatex']=="y") {
					$totstkamt[$stkacc] += vats($stk['amt'], 'novat', $vd['vat_amount']);
					$va=0;
					$inv['chrgvat']="";
				} else {
					$totstkamt[$stkacc] += vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']);
					$va=sprint($stk['amt']-vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']));
					if($inv['chrgvat']=="no") {
						$va=sprint($stk['amt']*$vd['vat_amount']/100);
					}
				}
			}else{
				if($stk['vatex']=="y") {
					$totstkamt[$stkacc] = vats($stk['amt'], 'novat', $vd['vat_amount']);
					$inv['chrgvat']="";
					$va=0;
				} else {
					$totstkamt[$stkacc] = vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']);
					$va=sprint($stk['amt']-vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']));
					if($inv['chrgvat']=="no") {
						$va=sprint($stk['amt']*$vd['vat_amount']/100);
					}
				}
			}

			vatr($vd['id'],$td,"OUTPUT",$vd['code'],$refnum,"Non-Stock Sales, invoice No.$real_invid",( vats($stk['amt'],$inv['chrgvat'], $vd['vat_amount'])+$va),$va);
			//print vats($stk['amt'],$inv['chrgvat'], $vd['vat_amount']);
			$inv['chrgvat']=$t;

			//$sql = "UPDATE nons_inv_items SET accid = '$stk[account]' WHERE id = '$stk[id]'";
			//$sRslt = db_exec($sql);
		}

		/* --- Start Some calculations --- */

		# Subtotal
		$SUBTOT = sprint($inv['subtot']);
		$VAT = sprint($inv['vat']);
		$TOTAL = sprint($inv['total']);

		/* --- End Some calculations --- */

		/* - Start Hooks - */
		//$vatacc = gethook("accnum", "salesacc", "name", "VAT");
		/* - End Hooks - */

		# todays date
		$date = date("d-m-Y");
		$sdate = date("Y-m-d");

		db_conn("cubit");

		if(isset($bankid)) {
			$bankid+=0;
			db_conn("cubit");
			$sql = "SELECT * FROM bankacct WHERE bankid = '$inv[accid]'";

			$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
			if (pg_numrows ($deptRslt) < 1) {
				$error = "<li class=err> Bank not Found.";
				$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
				return $confirm;
			}else{
				$deptd = pg_fetch_array($deptRslt);
			}

			db_conn('core');


			$Sl="SELECT * FROM bankacc WHERE accid='$bankid'";
			$rd=db_exec($Sl) or errDie("Unable to get data.");
			$data=pg_fetch_array($rd);

			$BA = $data['accnum'];
		}

		$tot_post=0;
		# bank  % cust
		if($ctyp == 's'){
			# Get department
			db_conn("exten");
			$sql = "SELECT * FROM departments WHERE deptid = '$cus[deptid]' AND div = '".USER_DIV."'";
			$deptRslt = db_exec($sql);
			if(pg_numrows($deptRslt) < 1){
				$dept['deptname'] = "<li class=err>Department not Found.";
			}else{
				$dept = pg_fetch_array($deptRslt);
			}
			$tpp=0;

			//$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
			//$stkdRslt = db_exec($sql);

// 			# Put in product
// 			while($stk = pg_fetch_array($stkdRslt)){
// 				$wamt=$stk['amt'];
//
// 				$tot_post+=$wamt;
// 				writetrans($dept['debtacc'], $stk['account'], $td, $refnum, $wamt, "Non-Stock Sales on invoice No.$real_invid customer $cus[surname].");
// 			}


			# record transaction  from data
			foreach($totstkamt as $stkacc => $wamt){
				# Debit Customer and Credit stock
				$tot_post+=$wamt;
				writetrans($dept['debtacc'], $stkacc, $td, $refnum, $wamt, "Non-Stock Sales on invoice No.$real_invid customer $inv[cusname].");
			}

			# Debit bank and credit the account involved
			if($VAT <> 0){
				$tot_post+=$VAT;
				writetrans($dept['debtacc'], $vatacc, $td, $refnum, $VAT, "Non-Stock Sales VAT received on invoice No.$real_invid customer $inv[cusname].");
			}

			$sdate = date("Y-m-d");
		}else{

			if(!isset($accountc)) {
				$accountc=0;
			}

			if(!isset($dept['pca'])) {
				$accountc+=0;
				$dept['pca']=$accountc;
				$dept['debtacc']=$accountc;
			}

			if(isset($bankid)) {
				$dept['pca']=$BA;

			}

			if($ctyp=="ac") {
				$dept['pca']=$inv['tval'];
			}

			$tpp=0;
			# record transaction  from data
			foreach($totstkamt as $stkacc => $wamt){
				if(!(isset($cust['surname']))) {
					$cust['surname']=$inv['cusname'];
					$cust['addr1']=$inv['cusaddr'];
				}
				# Debit Customer and Credit stock
				$tot_post+=$wamt;
				writetrans($dept['pca'], $stkacc, $td, $refnum, $wamt, "Non-Stock Sales on invoice No.$real_invid customer $inv[cusname].");
			}

			if(isset($bankid)) {
				db_connect();
				$bankid+=0;
				$sql = "INSERT INTO cashbook(bankid, trantype, date, name, descript, cheqnum, amount, vat, chrgvat, banked, accinv, div) VALUES ('$bankid', 'deposit', '$td', '$inv[cusname]', 'Non-Stock Sales on invoice No.$real_invid customer $inv[cusname]', '0', '$TOTAL', '$VAT', '$inv[chrgvat]', 'no', '$stkacc', '".USER_DIV."')";
				$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

				$sql = "UPDATE nons_invoices SET jobid='$bankid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
				$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");
			}

			# Debit bank and credit the account involved
			if($VAT <> 0){
				$tot_post+=$VAT;
				writetrans($dept['pca'], $vatacc, $td, $refnum, $VAT, "Non-Stock Sales VAT received on invoice No.$real_invid customer $inv[cusname].");
			}

			$sdate = date("Y-m-d");
		}

		$tot_post=sprint($tot_post);

		db_connect();
		if($ctyp == 's'){
			$sql = "UPDATE nons_invoices SET balance = total, cusid = '$cusnum', ctyp = '$ctyp', cusaddr = '$cus[addr1]', cusvatno = '$cus[vatnum]', done = 'y', invnum = '$real_invid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
			$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

			# Record the payment on the statement
			$sql = "
				INSERT INTO stmnt 
					(cusnum, invid, docref, amount, date, type, div, allocation_date) 
				VALUES 
					('$cusnum', '$real_invid', '$inv[docref]', '$TOTAL','$inv[odate]', 'Non-Stock Invoice', '".USER_DIV."', '$inv[odate]')";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

			# Record the payment on the statement
			$sql = "INSERT INTO open_stmnt(cusnum, invid, docref, amount, balance, date, type, div) VALUES('$cusnum', '$real_invid', '$inv[docref]', '$TOTAL', '$TOTAL','$inv[sdate]', 'Non-Stock Invoice', '".USER_DIV."')";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

			# Update the customer (make balance more)
			$sql = "UPDATE customers SET balance = (balance + '$TOTAL'::numeric(13,2)) WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			# Make ledge record
			custledger($cusnum,$stkacc , $td, $real_invid, "Non Stock Invoice No. $real_invid", $TOTAL, "d");
			custDT($TOTAL, $cusnum, $td, $invid, "nons");
	
			//print $tot_post;exit;

			$tot_dif=sprint($tot_post-$TOTAL);

			if($tot_dif>0) {
				writetrans($varacc,$dept['debtacc'], $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
			} elseif($tot_dif<0) {
				$tot_dif=$tot_dif*-1;
				writetrans($dept['debtacc'],$varacc, $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
			}
		} else {
			$date = date("Y-m-d");

			$sql = "UPDATE nons_invoices SET balance=total, accid = '$dept[pca]', ctyp = '$ctyp', done = 'y', invnum = '$real_invid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
			$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

			$tot_dif=sprint($tot_post-$TOTAL);

			if($tot_dif>0) {
				writetrans($varacc,$dept['pca'], $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
			} elseif($tot_dif<0) {
				$tot_dif=$tot_dif*-1;
				writetrans($dept['pca'],$varacc, $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
			}

			if ($ctyp == "c"){
				$cusnum = "0";
			}elseif ($ctyp == "ac"){
				$cusnum = "0";
				$na = "";
			}
		}

		db_connect();
		$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
		VALUES('$inv[sdate]', '$invid', '$real_invid', '$dept[debtacc]', '$VAT', '$TOTAL', 'non', '".USER_DIV."')";
		$recRslt = db_exec($sql);

		db_conn('cubit');

		$Sl="INSERT INTO sj(cid,name,des,date,exl,vat,inc,div) VALUES
		('$cusnum','$na','Non-stock Invoice $real_invid','$inv[sdate]','".sprint($TOTAL-$VAT)."','$VAT','".sprint($TOTAL)."','".USER_DIV."')";
		$Ri=db_exec($Sl);


		$ecost=sprint($TOTAL-$VAT);
		db_conn('cubit');
		$inv['jobid']+=0;

		$Sl="SELECT * FROM ninvc WHERE inv='$inv[jobid]'";
		$Ri=db_exec($Sl);

		if(CC_USE=="use") {

			if(pg_num_rows($Ri)>0) {
				while($data=pg_fetch_array($Ri)) {
					db_conn('cubit');
					$sql = "SELECT * FROM costcenters WHERE ccid = '$data[cid]'";
					$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
					$cc = pg_fetch_array ($ccRslt);

					$amount=sprint($ecost*$data['amount']/100);

					db_conn(PRD_DB);
					$sql = "INSERT INTO cctran(ccid, trantype, typename, edate, description, amount, username, div)
					VALUES('$cc[ccid]', 'dt', 'Invoice', '$inv[sdate]', 'Invoice No.$real_invid', '$amount', '".USER_NAME."', '".USER_DIV."')";
					$insRslt = db_exec ($sql) or errDie ("Unable to retrieve insert Cost center amounts into database.");
				}
			}
		}

		$i++;
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Retrieve template settings
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='invoices' AND div='".USER_DIV."'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	if ($template == "invoice-print.php") {
		pdf($_POST);
	} else {
		templatePdf($_POST);
	}

	// Final Laytout
	$write = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>$i Invoices Proccesed</th></tr>
		<tr class='bg-even'><td>Invoices have been successfully printed.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}

function pdf($_POST) {
	$showvat = TRUE;

	global $set_mainFont, $set_txtSize, $set_tlY, $set_tlX;
	global $set_pgXCenter, $set_maxTblOpt, $set_pgWidth;

	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($invids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
	}

	/* Start PDF Layout */
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

	$i = 0;
	foreach($invids as $key => $invid){
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
			//$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			//$OUTPUT = $error;
			//require("../template.php");
		}

		/* --- End some checks --- */

		/* Start PDF Layout */
// 		include("pdf-settings.php");
// 		$pdf =& new Cezpdf();
// 		$pdf ->selectFont($set_mainFont);
//
// 		# put a line top and bottom on all the pages
// 		$all = $pdf->openObject();
// 		$pdf->saveState();
// 		$pdf->setStrokeColor(0,0,0,1);
// 		$pdf->line(20,40,578,40);
// 		#$pdf->line(20,822,578,822);
// 		$pdf->addText(20,34,6,'Cubit Accounting');
// 		$pdf->restoreState();
// 		$pdf->closeObject();
//
// 		# note that object can be told to appear on just odd or even pages by changing 'all' to 'odd'
// 		# or 'even'.
// 		$pdf->addObject($all,'all');

		/* /Start PDF Layout */

		if($i > 0)
			$pdf ->newPage();


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

			$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatex]'";
			$Ri=db_exec($Sl);

			if(pg_num_rows($Ri)<1) {
				return "Please select the vatcode for all your stock.";
			}

			$vd=pg_fetch_array($Ri);

			if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
				$showvat = FALSE;
			}

			if($stkd['vatex'] == 'y'||$vd['zero']=="Yes"){
				$stkd['description']="# ".$stkd['description'];
				$ex = "#";
			}else{
				$ex = "&nbsp;&nbsp;";
			}

			# put in product
			$products[] = array('stkdes' => "".pdf_lstr($stkd['description']), 'qty' => $stkd['qty'], 'selamt' => CUR." $stkd[unitcost]", 'amt' => CUR." $stkd[amt]");
		}

		/* --- Start Some calculations --- */

		# Subtotal
		$VATP = TAX_VAT;
		$SUBTOT = sprint($inv['subtot']);
		$VAT = sprint($inv['vat']);
		$TOTAL = sprint($inv['total']);


		if (!isset($showvat))
			$showvat = TRUE;

		if($showvat == TRUE){
			$vat14 = AT14;
		}else {
			$vat14 = "";
		}

	/* -- Final PDF output layout -- */

		# just a new line
		//$pdf->ezText("<b>Tax Invoice\nReprint<b>", $set_txtSize+3, array('justification'=>'centre'));

		# set y so its away from the top
		$pdf->ezSetY($set_tlY);

		# Customer details
		$pdf->addText($set_tlX, $set_tlY, $set_txtSize-2, "$inv[cusname]");
		$nl = pdf_addnl($pdf, $set_tlX, $set_tlY, $set_txtSize-2, $inv['cusaddr']);
		$pdf->addText($set_tlX, $set_tlY - ($set_txtSize * $nl), $set_txtSize-2, "(VAT No. $inv[cusvatno])");
		$pdf->addText($set_tlX, $set_tlY - ($set_txtSize * ($nl+1)), $set_txtSize-2, "Customer Order Number: $inv[cordno]");

		# Company details
		$pdf->addText($set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_NAME);
		$nl = pdf_addnl($pdf, $set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_ADDRESS);
		$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * $nl), $set_txtSize-2, COMP_TEL);
		$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+1)), $set_txtSize-2, COMP_FAX);
		$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+2)), $set_txtSize-2, "Reg No. ".COMP_REGNO);
		$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+3)), $set_txtSize-2, "VAT No. ".COMP_VATNO);

		unset($invdet);

		# Invoice details data
		$invdet[] = array('tit' => 'Invoice No.', 'val' => $inv['invnum']);
		$invdet[] = array('tit' => 'Invoice Date', 'val' => $inv['odate']);

		# invoice details
		$pdf->ezTable($invdet,'',"",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));

		# just a new line
		$pdf->ezText("\n", $set_txtSize);

		# set y so its away from the customer details
		$pdf->ezSetY($set_tlY - ($set_txtSize * ($nl+3)));


		# products table
		$pdf->ezTable($products, $prodhead,'', $set_maxTblOpt);

		unset($amtdat);

		# Total amounts
		$amtdat[] = array('tit' => 'SUBTOTAL', 'val' => CUR." $SUBTOT");
		$amtdat[] = array('tit' => "VAT $vat14", 'val' => CUR." $VAT");
		$amtdat[] = array('tit' => "GRAND TOTAL", 'val' => CUR." $TOTAL");

		# just a new line
		$pdf->ezText("\n", 7);

		# Amounts details table data
		$pdf ->ezTable($amtdat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => ($set_pgWidth-42)));



		# just a new line
		$pdf->ezSetDy(80);
		$pdf->ezText("\n", $set_txtSize);

		$bank=str_replace("<br>","\n",BNK_BANKDET);

		unset($comments);

		$comments[] = array('tit' => "Comments", 'val' => wordwrap($inv['remarks'], 16));

		unset($banks);

		$banks[] = array('tit' => "Bank Details");
		$banks[] = array('tit' => "$bank");


		# VAT Number Table
		$pdf ->ezTable($comments, '', "",array('showLines'=> 5, 'showHeadings' => 0, 'xPos' => 89));

		$pdf ->ezTable($banks, '', "",array('showLines'=> 3, 'showHeadings' => 0, 'xPos' => 220));

		$pdf->ezSetDy(-20);

		unset($vatdat);

		$vatdat[] = array('tit' => "VAT Exempt indicator", 'val' => "#");
		// $vatdat[] = array('tit' => "VAT No.", 'val' => COMP_VATNO);

		# VAT Number Table
		$pdf ->ezTable($vatdat, '', "",array('showLines'=> 2, 'showHeadings' => 0, 'xPos' => 79));

		$i++;
	}
	$pdf ->ezStream();

/* -- End Final PDF Layout -- */

}

# long pdf file
function pdfinv($_GET)
{
	# Get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	foreach($invids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
	}

	/* Start PDF Layout */

		include("pdf-settings.php");
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

	$i = 0;
	foreach($invids as $key => $invid){
		# Get recuring invoice info
		db_connect();
		$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$invRslt = db_exec ($sql) or errDie ("Unable to get recuring invoice information");
		if (pg_numrows ($invRslt) < 1) {
			return "<i class=err>Not Found</i>";
		}
		$inv = pg_fetch_array($invRslt);

		$products = array();
		$invdet = array();
		$amtdat = array();
		$comments = array();
		$vatdat = array();

		# Create a new page for invoice
		if($i > 0)
			$pdf ->newPage();

		/* --- Start some checks --- */

		# check if stock was selected(yes = put done button)
		db_connect();
		$sql = "SELECT stkid FROM inv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
		$crslt = db_exec($sql);
		if(pg_numrows($crslt) < 1){
			$error = "<li class=err> Error : Invoice number <b>$invid</b> has no items.";
			$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			$OUTPUT = $error;
			require("template.php");
		}

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

			$sp = "    ";
			# Check Tax Excempt
			if($stkd['exvat'] == 'y'){
				$ex = "#";
			}else{
				$ex = "  ";
			}

			# keep track of discounts
			$disc += $stkd['disc'];

			# put in product
			$products[] = array('stkcod' => $stk['stkcod'], 'stkdes' => "$ex $sp ".pdf_lstr($stk['stkdes']), 'qty' => $stkd['qty'], 'selamt' => CUR." $stkd[unitcost]", 'disc' => CUR." $stkd[disc]", 'amt' => CUR." $stkd[amt]");
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

		/*/
		# Update number of prints
		$inv['prints']++;
		db_connect();
		$Sql = "UPDATE invoices SET prints = '$inv[prints]' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");
		/*/

	/* -- Final PDF output layout -- */

		# set y so its away from the top
		$pdf->ezSetY($set_tlY);

		# Customer details
		$pdf->addText($set_tlX, $set_tlY, $set_txtSize, "$inv[surname]");
		$nl = pdf_addnl($pdf, $set_tlX, $set_tlY, $set_txtSize, $inv['cusaddr']);
		$pdf->addText($set_tlX, $set_tlY - ($set_txtSize * $nl), $set_txtSize, "(VAT No. $inv[cusvatno])");

		# Company details
		$pdf->addText($set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_NAME);
		$nl = pdf_addnl($pdf, $set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_PADDRR);
		$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * $nl), $set_txtSize-2, COMP_TEL);
		$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+1)), $set_txtSize-2, COMP_FAX);
		$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+2)), $set_txtSize-2, "Reg No. ".COMP_REGNO);
		$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+3)), $set_txtSize-2, "VAT No. ".COMP_VATNO);

		$invdet = array();
		# Invoice details data
		$invdet[] = array('tit' => 'Invoice No.', 'val' => $inv['invnum']);
		$invdet[] = array('tit' => 'Order No.', 'val' => $inv['ordno']);
		$invdet[] = array('tit' => 'Terms', 'val' => "$inv[terms] Days");
		$invdet[] = array('tit' => 'Invoice Date', 'val' => $inv['odate']);

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
		$amtdat[] = array('tit' => 'Trade Discount', 'val' => CUR." $inv[discount]");
		$amtdat[] = array('tit' => "Delivery Charge", 'val' => CUR." $inv[delivery]");
		$amtdat[] = array('tit' => "VAT @ $VATP%", 'val' => CUR." $VAT");
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
	$pdf ->ezStream();

/* -- End Final PDF Layout -- */

}

function templatePdf($_POST)
{
	extract ($_POST);
	global $set_mainFont;

	$pdf = &new Cezpdf;
	$pdf->selectFont($set_mainFont);

	// Validate
	require_lib("validate");
	$v = new Validate();
	foreach ($invids as $invid) {
		$v->isOk($invid, "num", 1, 20, "Invalid invoice number.");
	}

	// Any errors?
	if ($v->isError()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=error>$e[msg]</li>";
		}
		$OUTPUT = $confirm;
		require("template.php");
	}

	$ai = 0;
	foreach ($invids as $invid) {
		if ($ai) $pdf->ezNewPage();
		++$ai;

		// Invoice info
		db_conn("cubit");
		$sql = "SELECT * FROM nons_invoices WHERE invid='$invid' AND DIV='".USER_DIV."'";
		$invRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");

		if (pg_num_rows($invRslt) == 0) {
			return "<li class=err>Not found</li>";
		}
		$inv = pg_fetch_array($invRslt);

		// Only needs to be blank, we're manually adding text
		$heading = array ( array("") );

		// Company info ----------------------------------------------------------
		db_conn("cubit");
		$sql = "SELECT * FROM compinfo WHERE div='".USER_DIV."'";
		$ciRslt = db_exec($sql) or errDie("Unable to retrieve company info from Cubit.");
		$comp = pg_fetch_array($ciRslt);

		$bnkData = qryBankAcct(cust_bank_id($inv["cusid"]));

		$compinfo = array();
		$compinfo[] = array ($comp["addr1"], "$comp[paddr1]");
		$compinfo[] = array ($comp["addr2"], "$comp[paddr2]");
		$compinfo[] = array ($comp["addr3"], "$comp[paddr3]");
		$compinfo[] = array ($comp["addr4"], "$comp[postcode]");
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

		if($data['value']=="Yes") {
			$sp="<b>Sales Person: </b>$inv[salespn]";
		} else {
			$sp="";
		}

		$docinfo = array (
			array ("<b>Invoice No:</b> $inv[invnum]"),
			array ("<b>Proforma Inv No:</b> $inv[docref]"),
			array ("$sp")
		);
		// Customer info ---------------------------------------------------------
		if ($inv["cusid"] != 0) {
			db_conn("cubit");
			$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusid]'";
			$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
			$cusData = pg_fetch_array($cusRslt);
		} else {
			$cusData["surname"] = $inv["cusname"];
			$cusData["addr1"] = $inv["cusaddr"];
			$cusData["paddr1"] = "";
			$cusData["accno"] = "";
		}

		$cusinfo = array (
			array ("<b>$cusData[surname]</b>")
		);

		$cusaddr = explode("\n", $cusData['paddr1']);
		foreach ( $cusaddr as $v ) {
			$cusinfo[] = array(pdf_lstr($v, 40));
		}

		$cusinfo[] = array("<b>Account no: </b>$cusData[accno]");

		$cusdaddr = array (
			array ("<b>Physical Address:</b>"),
		);

		$cusaddr = explode("\n", $cusData['addr1']);
		foreach ( $cusaddr as $v ) {
			$cusdaddr[] = array(pdf_lstr($v, 40));
		}
		// Registration numbers --------------------------------------------------
		$regnos = array (
			array (
				"<b>VAT No:</b>",
				"<b>Order No:</b>",
			),
			array (
				"$inv[cusvatno]",
				"$inv[cordno]"
			)
		);

		// Items display ---------------------------------------------------------
		$items = array ();

		db_conn("cubit");
		$sql = "SELECT * FROM nons_inv_items WHERE invid='$invid' AND DIV='".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while ($stkd = pg_fetch_array($stkdRslt)) {
			// Check Tax Excempt
			db_conn("cubit");
			$sql = "SELECT zero FROM vatcodes WHERE id='$stkd[vatex]'";
			$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
			$vatex = pg_fetch_result($zRslt, 0);

			if($vatex == "Yes"){
				$ex = "#";
			} else {
				$ex = "";
			}

			$items[] = array(
				"Description"=>pdf_lstr($ex.$stkd['description'], 65),
				"Qty"=>$stkd['qty'],
				"Unit Price"=>CUR.$stkd['unitcost'],
				"Amount"=>CUR.$stkd['amt']
			);
		}

		// Comment ---------------------------------------------------------------
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$commentRslt = db_exec($sql) or errDie("Unable to retrieve the default comment from Cubit.");
		$default_comment = pg_fetch_result($commentRslt, 0);

		$comment = array (
			array("<i>VAT Exempt Indicator: #</i>"),
			array(base64_decode($default_comment))
		);

		// Box to sign in --------------------------------------------------------
		$sign = array (
			array ("<i>Thank you for your support</i>"),
			array (''),
			array ("<b>Received in good order by:</b> ____________________"),
			array (''),
			// We aren't using a monospace font, so just a lot of spaces until it is aligned nicely.
			array ("                                      <b>Date:</b> ____________________")
		);
		// Totals ----------------------------------------------------------------
		$totals = array (
			array ("1"=>"<b>Subtotal:</b> ", "2"=>CUR."$inv[subtot]"),
			array ("1"=>"<b>VAT @ ".TAX_VAT."%:</b> ", "2"=>CUR."$inv[vat]"),
			array ("1"=>"<b>Total Incl VAT:</b> ", "2"=>CUR."$inv[total]")
		);
		$totCols = array (
			"1"=>array("width"=>90),
			"2"=>array("justification"=>"right")
		);

		$ic = 0;
		while ( ++$ic * 20 < count($items) );

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
			drawText(&$pdf, "<b>Tax Invoice</b>", 18, $heading_pos['x']-120, ($heading_pos['y']/2)+9);

			$compinfo_pos = drawTable(&$pdf, $compinfo, 0, $heading_pos['y'], 320, 8);
			$date_pos = drawTable(&$pdf, $date, $compinfo_pos['x'], $heading_pos['y'] , 100, 4);
			$pagenr_pos = drawTable(&$pdf, $pagenr, $date_pos['x'], $heading_pos['y'], 100, 4);
			$docinfo_pos = drawTable(&$pdf, $docinfo, $compinfo_pos['x'], $date_pos['y'], 200, 4);
			$cusinfo_pos = drawTable(&$pdf, $cusinfo, 0, $compinfo_pos['y'], 320, 10);
			$cusdaddr_pos = drawTable(&$pdf, $cusdaddr, $cusinfo_pos['x'], $compinfo_pos['y'], 200, 10);
			$regnos_pos = drawTable(&$pdf, $regnos, 0, $cusinfo_pos['y'], 520, 2);

			$items_start = ($i * 20);

			if ($items_start >= count($items) - 20) {
				$items_end = count($items) - 1;
			} else {
				$items_end = ($i + 1) * 20;
			}
			$items_print = array();

			for ($j = $items_start; $j <= $items_end; $j++) {
				$items_print[$j] = $items[$j];
			}

			// Adjust the column widths
			$cols = array (
				"Description"=>array("width"=>310),
				"Qty"=>array ("width"=>50),
				"Unit Price"=>array ("width"=>80, "justification"=>"right"),
				"Amount"=>array ("width"=>80, "justification"=>"right")
			);

			$items_pos = drawTable(&$pdf, $items_print, 0, $regnos_pos['y']+2, 520, 20, $cols, 1);
			$comment_pos = drawTable(&$pdf, $comment, 0, $items_pos['y'], 520, 2);
			$sign_pos = drawTable(&$pdf, $sign, 0, $comment_pos['y'], 320, 5);
			$totals_pos = drawTable(&$pdf, $totals, $sign_pos['x'], $comment_pos['y'], 200, 5, $totCols);

		}
	}
	$pdf->ezStream();

}

# vats
function vats($amt, $inc, $VATP){
	# If vat is not included
	//$VATP = TAX_VAT;
	if($inc == "no"){
		$ret = ($amt);
	}elseif($inc == "yes"){
		$VAT = sprint(($amt/($VATP + 100)) * $VATP);
		$ret = ($amt - $VAT);
	}else{
		$ret = ($amt);
	}

	return $ret;
}

?>
