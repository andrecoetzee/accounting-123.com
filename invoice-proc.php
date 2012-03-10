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
require("pdf-settings.php");

if(isset($_REQUEST["email"]) || isset($_REQUEST["print"]) || isset($_REQUEST["btndelete"])) {
	$ids = "";
	if(!(isset($_REQUEST["evs"]) && is_array($_REQUEST["evs"]))) {
		$OUTPUT= "<li class='err'>Please select at least one invoice</li>";
		require("template.php");
	}
	foreach($_REQUEST["evs"] as $id => $value) {
		$ids .= "$id,";
	}
	$ids = substr($ids,0,strlen($ids) - 1);

	if (isset($_REQUEST["btndelete"])) {
		header("Location: nons-invoice-view.php?key=bdel&ids=$ids");
		exit;
	}

	if(isset($_REQUEST["print"])) {
		header("Location: nons-invoices-print.php?ids=$ids");
		exit;
	}

	if(!isset($_REQUEST["t"])) {
		header("Location: invoices-email.php?evs=$ids");
	} else {
		header("Location: invoices-email.php?evs=$ids&t=t");
	}
	exit;
}

require("core-settings.php");
require("libs/ext.lib.php");

# decide what to do
if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "process":
			$OUTPUT = process($_REQUEST);
			break;
		case "write":
			$OUTPUT = write($_REQUEST);
			break;
		default:
			# decide what to do
			if (isset($_REQUEST["invids"])) {
				$OUTPUT = details($_REQUEST);
			} else {
				$OUTPUT = "<li class='err'>Please select at least one unprinted invoice.</li>";
			}
		}
} else {
	# decide what to do
	if (isset($_REQUEST["invids"])) {
		$OUTPUT = details($_REQUEST);
	} else {
		$OUTPUT = "<li class='err'>Please select at least one unprinted invoice.</li>";
	}
}

# get templete
require("template.php");




function details($HTTP_GET_VARS)
{

	extract($HTTP_GET_VARS);

	require_lib("validate");
	$v = new  validate ();
	foreach($invids as $key => $invid){
		$v->isOk ($invid, "num", 1, 20, "Invalid recuring invoice number.");
	}

	if ($v->isError ()) {
		$err = $v->genErrors();
		$err .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $err;
	}

	$printInv = "
		<h3>Confirm Invoice Printing Process</h3>
		<form action='invoice-proc.php' method='POST'>
			<input type='hidden' name='key' value='process'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='6' class='err'>Please Note : This process might take long depending on the number of invoices. It is best to run it overnight.</td>
			</tr>
			<tr>
				<th>Department</th>
				<th>Sales Person</th>
				<th>Invoice No.</th>
				<th>Invoice Date</th>
				<th>Customer Name</th>
				<th>Order No</th>
				<th>Grand Total</th>
			</tr>";

	$i = 0;
	foreach($invids as $key => $invid){
		# Get recuring invoice info
		db_connect();
		$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$invRslt = db_exec ($sql) or errDie ("Unable to get recuring invoice information");
		if (pg_numrows ($invRslt) < 1) {
			return "<i class='err'>Not Found</i>";
		}
		$inv = pg_fetch_array($invRslt);

		$inv['total'] = sprint($inv['total']);
		$inv['balance'] = sprint($inv['balance']);

		# Format date
		list($oyear, $omon, $oday) = explode("-", $inv['odate']);

		$printInv .= "
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='hidden' name='invids[]' value='$inv[invid]'>$inv[deptname]</td>
				<td>$inv[salespn]</td>
				<td>T $inv[invid]</td>
				<td valign='center'>$oday-$omon-$oyear</td>
				<td>$inv[cusname] $inv[surname]</td>
				<td align='right'>$inv[ordno]</td>
				<td align='right'>".CUR." $inv[total]</td>
			</tr>";
		$i++;
	}

	$printInv .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='6' align='right'>Totals Invoices : $i</td>
				<td><br></td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='6' align='right'><input type='submit' value='Process >>'></td>
			</tr>
		</form>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='rec-invoice-new.php'>New Recurring Invoice</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='rec-invoice-view.php'>View Recurring Invoices</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $printInv;

}




# Create the company
function process ($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

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
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}



	$postvars = "";
	foreach($invids as $key => $invid){
		$postvars .= "<input type='hidden' name='invids[]' value='$invid'>";
	}

	$OUTPUT = "
		<form action='invoice-proc.php' method='POST' name='postvars'>
			<input type='hidden' name='key' value='write'>
			$postvars
		</form>

		<table width='100%' height='100%'>
			<tr>
				<td align='center' valign='middle'>
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
function write($HTTP_POST_VARS)
{

	# Set max execution time to 12 hours
	ini_set("max_execution_time", 43200);

	# Get vars
	extract ($HTTP_POST_VARS);

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
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $err;
	}



	$i = 0;
	foreach($invids as $key => $invid){
		# Get recuring invoice info
		db_connect();
		$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$invRslt = db_exec ($sql) or errDie ("Unable to get recuring invoice information");
		if (pg_numrows ($invRslt) < 1) {
			return "<i class='err'>Not Found</i>";
		}
		$inv = pg_fetch_array($invRslt);

		# check if invoice has been printed
		if($inv['printed'] == "y"){
			$error = "<li class='err'> Error : Invoice number <b>$inv[invnum]</b> has already been printed.";
			$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $error;
		}
		# check if invoice has been serialised
		if($inv['serd'] == "n"){
			$error = "<li class='err'> Error : You must select serial numbers for some Items on Invoice No. <b>T $invid</b> before you can print it.";
			$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $error;
		}

	# Begin Updates
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		$invnum = divlastid('inv', USER_DIV);

		$Sl = "INSERT INTO ncsrec (oldnum, newnum, div) VALUES ('$invid', '$invnum', '".USER_DIV."')";
		$Rs = db_exec ($Sl) or errDie ("Unable to insert into db");

		# Get department
		db_conn("exten");

		$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql);
		if(pg_numrows($deptRslt) < 1){
			$dept['deptname'] = "<i class='err'>Not Found</i>";
		}else{
			$dept = pg_fetch_array($deptRslt);
		}

		/* --- Start Products Display --- */

		# Products layout
		$commision=0;
		$products = "";
		$disc = 0;

		# get selected stock in this invoice
		db_connect();

		$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		if (pg_numrows($stkdRslt) < 1) {
			$error = "<li class='err'> Error : Invoice number <b>$invid</b> has no items.</li>";
			if (($r2sid = r2sListCheck("invoice_stk_view")) !== false) {
				$error .= "<p><input type='button' onClick='document.location.href=\"r2srestore.php?r2sid=$r2sid\";' value='List Invoices'>";
			} else {
				$error .= "<p><input type='button' onClick='document.location.href=\"invoice-view.php\";' value='List Invoices'>";
			}
			$OUTPUT = $error;
			pglib_transaction("ROLLBACK");
			require("template.php");
		}

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

			$sp = "&nbsp;&nbsp;&nbsp;&nbsp;";
			# Check Tax Excempt
			if($stk['exvat'] == 'yes'){
				$taxex += ($stkd['amt']);
			}

			# Keep track of discounts
			$disc += ($stkd['disc'] * $stkd['qty']);

			# Insert stock record
			$sdate = date("Y-m-d");
			$csprice = sprint($stk['csprice'] * $stkd['qty']);

			# Get amount exluding vat if including and not exempted
//			$VATP = TAX_VAT;

			#get the actual vat perc of this item
			$get_vat = "SELECT vat_amount FROM vatcodes WHERE id = '$stkd[vatcode]' LIMIT 1";
			$run_vat = db_exec($get_vat) or errDie("Unable to get vat percentage information");
			if(pg_numrows($run_vat) < 1){
				$VATP = 0;
			}else {
				$varr = pg_fetch_array($run_vat);
				$VATP = $varr['vat_amount'];
			}

			$amtexvat = sprint($stkd['amt']);
			if($inv['chrgvat'] == "inc" && $stk['exvat'] != 'yes'){
				$amtexvat = sprint(($stkd['amt'] * 100)/(100 + $VATP));
			}

			if($stkd['account'] == 0) {

				db_connect();

				$sql = "
					INSERT INTO stockrec (
						edate, stkid, stkcod, stkdes, trantype, qty, 
						csprice, csamt, details, div
					) VALUES (
						'$sdate', '$stkd[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'invoice', '$stkd[qty]', 
						'$amtexvat', '$csprice', 'Stock sold - Invoice No. $invnum', '".USER_DIV."'
					)";
				$recRslt = db_exec($sql);

				# Sales rep commission
//				$commision = $commision + coms($inv['salespn'], $stkd['amt'], $stk['com']);
				$commision = $commision + coms($inv['salespn'], $amtexvat, $stk['com']);
			}
		}

		/* --- Start Some calculations --- */

		# Subtotal
		$SUBTOT = sprint($inv['subtot']);

		# Calculate tradediscm
		if(strlen($inv['traddisc']) > 0){
			$traddiscm = sprint((($inv['traddisc']/100) * $SUBTOT));
		}else{
			$traddiscm = "0.00";
		}

		# Calculate subtotal
		$VATP = TAX_VAT;
		$SUBTOTAL = sprint($inv['subtot']);
		$VAT = sprint($inv['vat']);
		$TOTAL = sprint($inv['total']);
		$inv['delchrg'] = sprint($inv['delchrg']);

		com_invoice($inv['salespn'],$TOTAL-$VAT,$commision,$invnum,$inv["odate"]);

		/* --- End Some calculations --- */

		/* - Start Hooks - */
		$vatacc = gethook("accnum", "salesacc", "name", "VAT","out");
		/* - End Hooks - */

		# Todays date
		$date = date("d-m-Y");
		$sdate = date("Y-m-d");

		$refnum = getrefnum();

		/* --- Updates ---- */
		db_connect();

			$Sql = "UPDATE invoices SET printed ='y', done ='y', invnum='$invnum' WHERE invid = '$invid' AND div = '".USER_DIV."'";
			$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");

			# Record the payment on the statement
			$sql = "
				INSERT INTO stmnt 
					(cusnum, invid, docref, amount, date, type, div, allocation_date) 
				VALUES 
					('$inv[cusnum]', '$invnum', '$inv[docref]', '$inv[total]', '$inv[odate]', 'Invoice', '".USER_DIV."', '$inv[odate]')";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

			# Record the payment on the statement
			$sql = "INSERT INTO open_stmnt(cusnum, invid, docref, amount, balance, date, type, div) VALUES('$inv[cusnum]', '$invnum', '$inv[docref]', '$inv[total]','$inv[total]', '$inv[odate]', 'Invoice', '".USER_DIV."')";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

			# Save invoice discount
			$sql = "INSERT INTO inv_discs(cusnum, invid, traddisc, itemdisc, inv_date, delchrg, div,total) VALUES('$inv[cusnum]', '$invnum', '$traddiscm', '$disc', '$inv[odate]', '$inv[delchrg]', '".USER_DIV."', ($SUBTOT+$inv[delchrg]))";
			$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

			# Update the customer (make balance more)
			$sql = "UPDATE customers SET balance = (balance + '$inv[total]') WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

			# Make ledge record
			custledger($inv['cusnum'], $dept['incacc'], $inv["odate"], $invnum, "Invoice No. $invnum", $inv['total'], "d");
			$nsp=0;
			db_connect();
			# get selected stock in this invoice
			$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
			$stkdRslt = db_exec($sql);
			$tcosamt = 0;
			while($stkd = pg_fetch_array($stkdRslt)){

				$stkd['account']+=0;

				if($stkd['account']==0) {

					db_connect();
					# get selamt from selected stock
					$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
					$stkRslt = db_exec($sql);
					$stk = pg_fetch_array($stkRslt);

					if(($stk['units']-$stkd['qty'])<0) {
						if($stk['units']<=0) {
							$cosamt=0;
						} else {
							$cosamt = round(($stk['units'] * $stk['csprice']), 2);
						}
					} else {
						$cosamt = round(($stkd['qty'] * $stk['csprice']), 2);
					}

					$uc=sprint($cosamt/$stkd['qty']);

					if($stk['csprice']>0) {
						$Sl="INSERT INTO scr(inv,stkid,amount) VALUES ('$invnum','$stkd[stkid]','$uc')";
						$Rg=db_exec($Sl);
					}

					# update stock(alloc - qty)
					$sql = "UPDATE stock SET csamt = (csamt - '$cosamt'),units = (units - '$stkd[qty]'),alloc = (alloc - '$stkd[qty]')  WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
					$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

					if($stk['serd'] == 'yes')
						ext_invSer($stkd['serno'], $stkd['stkid'], "$inv[cusname] $inv[surname]", $invnum);

					# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
					$sdate = date("Y-m-d");

					if($stkd['account']==0) {

						stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $sdate, $stkd['qty'], $cosamt, "Sold to Customer : $inv[surname] - Invoice No. $invnum");
					}
				}

				###################VAT CALCS#######################
				db_connect();
				$stkd['vatcode']+=0;
				$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
				$Ri=db_exec($Sl);

				if(pg_num_rows($Ri)<1) {
					return "Please select the vatcode for all your stock.";
				}

				$vd=pg_fetch_array($Ri);

				if($stk['exvat'] == 'yes'||$vd['zero']=="Yes") {
					$excluding="y";
				} else {
					$excluding="";
				}

				$vr=vatcalc($stkd['amt'],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
				$vrs=explode("|",$vr);
				$ivat=$vrs[0];
				$iamount=$vrs[1];

				vatr($vd['id'],$inv['odate'],"OUTPUT",$vd['code'],$refnum,"VAT for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]",$iamount,$ivat);

				####################################################


				if($stkd['account']==0) {

					# get accounts
					db_conn("exten");
					$sql = "SELECT stkacc,cosacc FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
					$whRslt = db_exec($sql);
					$wh = pg_fetch_array($whRslt);
					$stockacc = $wh['stkacc'];
					$cosacc = $wh['cosacc'];
					if($cosamt>0) {
						# dt(cos) ct(stock)
//						writetrans($cosacc, $stockacc, $date, $refnum, $cosamt, "Cost Of Sales for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");
						writetrans($cosacc, $stockacc, $inv['odate'], $refnum, $cosamt, "Cost Of Sales for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");
					}
					$tcosamt += $cosamt;

					db_connect();
					$date = date("Y-m-d");
					$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
					VALUES('$inv[odate]', '$invid', '$invnum', '$dept[debtacc]', '$ivat', '$iamount', 'stk', '".USER_DIV."')";
					$recRslt = db_exec($sql);

				} else {
					$amtexvat = sprint($stkd['amt']);
					db_connect();
					$sdate = date("Y-m-d");
					$nsp+=sprint($iamount-$ivat);
					//writetrans($cosacc, $stockacc,$inv['odate'] , $refnum, $cosamt, "Cost Of Sales for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");
					writetrans($dept['debtacc'], $stkd['account'],$inv['odate'], $refnum, ($iamount-$ivat), "Debtors Control for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");

					db_connect();
					$date = date("Y-m-d");
					$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
					VALUES('$inv[odate]', '$invid', '$invnum', '$dept[debtacc]', '$ivat', '$iamount', 'non', '".USER_DIV."')";
					$recRslt = db_exec($sql);

				}
			}

		/* - Start Transactoins - */

			# dt(debtors) ct(income/sales)
//			writetrans($dept['debtacc'], $dept['incacc'], $date, $refnum, sprint($TOTAL-$VAT-$nsp), "Debtors Control for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");
			writetrans($dept['debtacc'], $dept['incacc'], $inv['odate'], $refnum, sprint($TOTAL-$VAT-$nsp), "Debtors Control for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");

			# dt(debtors) ct(vat account)
			writetrans($dept['debtacc'], $vatacc, $inv['odate'], $refnum, $VAT, "VAT Received on Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");

			db_conn('cubit');

			$Sl="INSERT INTO sj(cid,name,des,date,exl,vat,inc,div) VALUES
			('$inv[cusnum]','$inv[surname]','Invoice $invnum','$inv[odate]','".sprint($TOTAL-$VAT)."','$VAT','".sprint($TOTAL)."','".USER_DIV."')";
			$Ri=db_exec($Sl);


			$ecost=sprint($TOTAL-$VAT);
			db_conn('cubit');
			$inv['jobid']+=0;

			$Sl="SELECT * FROM invc WHERE inv='$inv[jobid]'";
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
						VALUES('$cc[ccid]', 'dt', 'Invoice', '$inv[odate]', 'Invoice No.$invnum', '$amount', '".USER_NAME."', '".USER_DIV."')";
						$insRslt = db_exec ($sql) or errDie ("Unable to retrieve insert Cost center amounts into database.");

					}
				}
			}

		####/*###*/############VAT CALCS#######################
		$inv['delvat']+=0;
		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$inv[delvat]'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			$Sl="SELECT * FROM vatcodes";
			$Ri=db_exec($Sl);
		}

		$vd=pg_fetch_array($Ri);

		if($vd['zero']=="Yes") {
			$excluding="y";
		} else {
			$excluding="";
		}

		$vr=vatcalc($inv['delchrg'],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
		$vrs=explode("|",$vr);
		$ivat=$vrs[0];
		$iamount=$vrs[1];

		vatr($vd['id'],$inv['odate'],"OUTPUT",$vd['code'],$refnum,"VAT for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]",$iamount,$ivat);

		####################################################


	# Commit updates
		pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);
		$i++;
	}

	// Retrieve template settings
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='invoices'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	if ($template == "invoice-print.php") {
		pdf($HTTP_POST_VARS);
	} else {
		templatePdf($HTTP_POST_VARS);
	}

	// Final Laytout
	$write = "
	<table ".TMPL_tblDflts.">
		<tr>
			<th>$i Invoices Proccesed</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Invoices has been successfully printed.</td>
		</tr>
	</table>
	<p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Quick Links</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><a href='invoice-view.php'>View Invoices</a></td>
		</tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";
	return $write;

}




# long pdf file
function pdf($HTTP_GET_VARS)
{

	$showvat = TRUE;

	global $set_mainFont, $set_txtSize, $set_tlY, $set_tlX;
	global $set_pgXCenter, $set_maxTblOpt, $set_pgWidth;

	# Get vars
	extract ($HTTP_GET_VARS);

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

			$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri=db_exec($Sl);

			if(pg_num_rows($Ri)<1) {
				return "Please select the vatcode for all your stock.";
			}

			$vd=pg_fetch_array($Ri);

			if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
				$showvat = FALSE;
			}

			$sp = "";
			# Check Tax Excempt
			if($stk['exvat'] == 'yes'||$vd['zero']=="Yes"){
				$ex = "#";
			}else{
				$ex = "";
			}

			# keep track of discounts
			$disc += $stkd['disc'];

			if($stkd['account']!=0) {
				$wh['whname']="";
				$stk['stkcod']="";
				$stk['stkdes']=$stkd['description'];
			}

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

		// Retrieve company info
		db_conn("cubit");
		$sql = "SELECT * FROM compinfo";
		$compRslt = db_exec($sql) or errDie("Unable to retrieve company information from Cubit.");
		$compData = pg_fetch_array($compRslt);

		$comp_addr = "$compData[addr1]\n$compData[addr2]\n$compData[addr3]\n$compData[addr4]";

		if (!isset($showvat))
			$showvat = TRUE;

		if($showvat == TRUE){
			$vat14 = AT14;
		}else {
			$vat14 = "";
		}

	/* -- Final PDF output layout -- */

		# set y so its away from the top
		$pdf->ezSetY($set_tlY);

		# Customer details
		$pdf->addText($set_tlX, $set_tlY, $set_txtSize-2, "$inv[surname]");
		$nl = pdf_addnl($pdf, $set_tlX, $set_tlY, $set_txtSize-2, $inv['cusaddr']);
		$pdf->addText($set_tlX, $set_tlY - ($set_txtSize * $nl), $set_txtSize-2, "(VAT No. $inv[cusvatno])");

		# Company details
		$pdf->addText($set_pgXCenter, $set_tlY, $set_txtSize-2, COMP_NAME);
		$nl = pdf_addnl($pdf, $set_pgXCenter, $set_tlY, $set_txtSize-2, $comp_addr);
		$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * $nl), $set_txtSize-2, COMP_TEL);
		$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+1)), $set_txtSize-2, COMP_FAX);
		$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+2)), $set_txtSize-2, "Reg No. ".COMP_REGNO);
		$pdf->addText($set_pgXCenter, $set_tlY - (($set_txtSize-2) * ($nl+3)), $set_txtSize-2, "VAT No. ".COMP_VATNO);

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
		$amtdat[] = array('tit' => "VAT $vat14", 'val' => CUR." $VAT");
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




function templatePdf($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);
	global $set_mainFont;

	$pdf = &new Cezpdf;

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
			$err .= "<li class='err'>$e[msg]</li>";
		}
		$OUTPUT = $confirm;
		require("template.php");
	}



	$i = 0;
	foreach ($invids as $invid) {
		$pdf->selectFont($set_mainFont);

		// Invoice info
		db_conn("cubit");
		$sql = "SELECT * FROM invoices WHERE invid='$invid' AND DIV='".USER_DIV."'";
		$invRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
		if (pg_num_rows($invRslt) < 1) {
			return "<li class='err'>Not found</li>";
		}
		$inv = pg_fetch_array($invRslt);

		db_conn("cubit");
		$sql = "SELECT symbol FROM currency WHERE fcid='$inv[fcid]'";
		$curRslt = db_exec($sql) or errDie("Unable to retrieve currency from Cubit.");
		$curr = pg_fetch_result($curRslt, 0);
		if (!$curr) $curr = CUR;

		// Check if stock was selected
		db_conn("cubit");
		$sql = "SELECT stkid FROM inv_items WHERE invid='$invid' AND DIV='".USER_DIV."'";
		$cRslt = db_exec($sql) or errDie("Unable to retrieve invoice info.");
		if (pg_num_rows($cRslt) < 1) {
			$error = "<li class='err'>Invoice number <b>$invid</b> has no items</li>";
			$OUTPUT = $error;
		}

		// Only needs to be blank, we're manually adding text
		$heading = array ( array("") );

		// Company info ----------------------------------------------------------
		db_conn("cubit");
		$sql = "SELECT * FROM compinfo WHERE div='".USER_DIV."'";
		$ciRslt = db_exec($sql) or errDie("Unable to retrieve company info from Cubit.");
		$comp = pg_fetch_array($ciRslt);

		// Banking information ---------------------------------------------------
	//	$bnkData = qryBankAcct(getdSetting("BANK_DET"));
		$bnkData = qryBankAcct($inv['bankid']);

		$compinfo = array();
		$compinfo[] = array ($comp["addr1"], $comp["paddr1"]);
		$compinfo[] = array (pdf_lstr($comp["addr2"], 35), pdf_lstr($comp["paddr2"], 35));
		$compinfo[] = array (pdf_lstr($comp["addr3"], 35), pdf_lstr($comp["paddr3"], 35));
		$compinfo[] = array (pdf_lstr($comp["addr4"], 35), "$comp[postcode]");
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
			array ("<b>Sales Order No:</b> $inv[ordno]"),
			array ("$sp")
		);
		if (isset($salespn)) {
			$docinfo[] = array("<b>Sales Person:</b> $salespn");
		}

		// Retrieve the customer information -------------------------------------
		db_conn("cubit");
		$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
		$cusRslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
		$cusData = pg_fetch_array($cusRslt);

		// Customer info ---------------------------------------------------------
		$invoice_to = array(
			array ("")
		);

		$cusinfo = array (
			array ("<b>$inv[surname]</b>")
		);

		$cusaddr = explode("\n", $cusData['addr1']);
		foreach ( $cusaddr as $v ) {
			$cusinfo[] = array(pdf_lstr($v, 40));
		}

		$cusinfo[] = array("<b>Account no: </b>$cusData[accno]");

		$cuspaddr = array (
			array("<b>Postal Address</b>"),
		);

		$paddr = explode("\n", $cusData["paddr1"]);
		foreach ($paddr as $addr) {
			$cuspaddr[] = array($addr);
		}

		$cusdaddr = array (
			array ("<b>Delivery Address:</b>"),
		);

		if($inv['branch'] == 0){
				$branchname = "Head Office";
				$cusaddr = explode("\n", $cusData['addr1']);
		}else {
				$get_addr = "SELECT * FROM customer_branches WHERE id = '$inv[branch]' LIMIT 1";
				$run_addr = db_exec($get_addr);
				if (pg_numrows($run_addr) < 1) {
						$cusaddr = Array ();
						$branchname = "Head Office";
				} else {
						$barr = pg_fetch_array($run_addr);
						$cusaddr = explode("\n", $barr['branch_descrip']);
						$branchname = $barr['branch_name'];
				}
		}

		$cusdaddr[] = array(pdf_lstr("Branch : $branchname", 30));
		$del_addr = explode("\n", $inv["del_addr"]);
		foreach ($del_addr as $addr ) {
			$cusdaddr[] = array(pdf_lstr($addr, 30));
		}

		// Registration numbers --------------------------------------------------
		$regnos = array (
			array (
				"<b>VAT No:</b>",
				"<b>Order No:</b>",
				"<b>Delivery Date:</b>"
			),
			array (
				"$inv[cusvatno]",
				"$inv[cordno]",
				"$inv[deldate]"

			)
		);

		// Items display ---------------------------------------------------------
		$items = array ();

		db_conn("cubit");
		$sql = "SELECT * FROM inv_items WHERE invid='$invid' AND DIV='".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while ($stkd = pg_fetch_array($stkdRslt)) {
			// Get warehouse
			db_conn("exten");
			$sql = "SELECT * FROM warehouses WHERE whid='$stkd[whid]' AND DIV='".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			// Get stock in this warehouse
			db_conn("cubit");
			$sql = "SELECT * FROM stock WHERE stkid='$stkd[stkid]' AND DIV='".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			$sp = "";

			// Check Tax Excempt
			db_conn("cubit");
			$sql = "SELECT zero FROM vatcodes WHERE id='$stkd[vatcode]'";
			$zRslt = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
			$vatex = pg_fetch_result($zRslt, 0);

			if($vatex == "Yes"){
				$ex = "#";
			} else {
				$ex = "";
			}

			$sql = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$runsql = db_exec($sql) or errDie("Unable to retrieve vat code from Cubit.");
			if(pg_numrows($runsql) < 1){
				return "Invalid VAT code entered";
			}

			$vd = pg_fetch_array($runsql);

			if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
				$showvat = FALSE;
			}

			// keep track of discounts
			//$disc += $stkd['disc'];
			if ($stkd["account"] > 0) {
				$description = $stkd["description"];
			} else {
				$description = $stk["stkdes"];
			}

			// Remove any new lines from the description
			$ar_desc = explode("\n", $description);
			$description = implode(" ", $ar_desc);

			$items[] = array(
				"Code"=>makewidth($pdf, 75, 12, $stk['stkcod']),
				"Description"=>makewidth($pdf, 175, 12, $ex.$description),
				"Qty"=>$stkd['qty'],
				"Unit Price"=>$curr.$stkd['unitcost'],
				"Unit Discount"=>$curr.$stkd['disc'],
				"Amount"=>$curr.$stkd['amt']
			);
		}

		$inv["comm"] = fixparag(&$pdf, 3, 520, 11, $inv["comm"]);
		/*$inv["comm"] = preg_replace("/[\n]/", " ", $inv["comm"]);

		$lines = array();
		$txtleft = $inv["comm"];
		$done = false;
		while (count($lines) < 3 && !$done) {
			$mc = maxwidth(&$pdf, 520, 11, $txtleft);

			// run until end of a word.
			while ($txtleft[$mc - 1] != ' ' && $mc < strlen($txtleft)) ++$mc;

			if ($mc == strlen($txtleft)) {
				$done = true;
			}

			$lines[] = substr($txtleft, 0, $mc);
			$txtleft = substr($txtleft, $mc);
		}

		if (strlen($txtleft) > 0) {
			$lines[2] .= "...";
		}

		$inv["comm"] = preg_replace("/  /", " ", implode("\n", $lines));*/

		// Comment ---------------------------------------------------------------
		$comment = array (
			array ("<i>VAT Exempt Indicator : #</i>"),
			array ($inv["comm"])
		);

		// Box for signature -----------------------------------------------------
		$sign = array (
			array ("<b>Terms:</b> $inv[terms] days"),
			array (''),
			array ("<b>Received in good order by:</b> ____________________"),
			array (''),
			// We aren't using a monospace font, so just a lot of spaces until it is aligned nicely.
			array ("                                      <b>Date:</b> ____________________")
		);

		// Totals ----------------------------------------------------------------

		if (!isset($showvat))
			$showvat = TRUE;

		if($showvat == TRUE){
			$vat14 = AT14;
		}else {
			$vat14 = "";
		}

		$totals = array (
			array ("1"=>"<b>Subtotal:</b> ", "2"=>$curr."$inv[subtot]"),
			array ("1"=>"<b>Trade Discount:</b> ", "2"=>$curr."$inv[discount]"),
			array ("1"=>"<b>Delivery Charge:</b> ", "2"=>$curr."$inv[delivery]"),
			array ("1"=>"<b>VAT $vat14:</b> ", "2"=>$curr."$inv[vat]"),
			array ("1"=>"<b>Total Incl VAT:</b> ", "2"=>$curr."$inv[total]")
		);
		$totCols = array (
			"1"=>array("width"=>90),
			"2"=>array("justification"=>"right")
		);

		$ic = 0;
		while ( ++$ic * 22 < count($items) );

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
			drawText(&$pdf, "<b>Tax Invoice</b>", 20, $heading_pos['x']-120, ($heading_pos['y']/2)+9);

			// Should we display reprint on the invoice
			if ($type == "invreprint") {
				drawText(&$pdf, "<b>Reprint</b>", 12, $heading_pos['x']-70, ($heading_pos['y']/2)+22);
			}

			$compinfo_pos = drawTable(&$pdf, $compinfo, 0, $heading_pos['y'], 320, 8);
			$date_pos = drawTable(&$pdf, $date, $compinfo_pos['x'], $heading_pos['y'] , 100, 3);
			$pagenr_pos = drawTable(&$pdf, $pagenr, $date_pos['x'], $heading_pos['y'], 100, 3);
			$docinfo_pos = drawTable(&$pdf, $docinfo, $compinfo_pos['x'], $date_pos['y'], 200, 5);
			$invoice_to_pos = drawTable(&$pdf, $invoice_to, 0, $compinfo_pos['y'], 520, 2);
			drawText(&$pdf, "<b>Tax Invoice to:</b>", 12, (520/2)-45, $invoice_to_pos['y']-7);

			$cusinfo_pos = drawTable(&$pdf, $cusinfo, 0, $invoice_to_pos['y'], 173, 8);
			$cuspaddr_pos = drawTable(&$pdf, $cuspaddr, $cusinfo_pos['x'], $invoice_to_pos['y'], 173, 8);
			$cusdaddr_pos = drawTable(&$pdf, $cusdaddr, $cuspaddr_pos['x'], $invoice_to_pos['y'], 174, 8);
			$regnos_pos = drawTable(&$pdf, $regnos, 0, $cusinfo_pos['y'], 520, 2);

			$items_start = ($i * 22);

			if ($i) $items_start++;

			if ($items_start >= (count($items) - 22)) {
				$items_end = count($items) - 1;
			} else {
				$items_end = ($i + 1) * 22;
			}
			$items_print = array();

			for ($j = $items_start; $j <= $items_end; $j++) {
				$items_print[$j] = $items[$j];
			}

			$cols = array(
				"Code"=>array("width"=>80),
				"Description"=>array("width"=>180),
				"Qty"=>array("width"=>33),
				"Unit Price"=>array("width"=>80, "justification"=>"right"),
				"Unit Discount"=>array("width"=>67, "justification"=>"right"),
				"Amount"=>array("width"=>80, "justification"=>"right")
			);

			$items_pos = drawTable(&$pdf, $items_print, 0, $regnos_pos['y']+2, 520, 22, $cols, 1);
			$comment_pos = drawTable(&$pdf, $comment, 0, $items_pos['y'], 520, 2);
			$sign_pos = drawTable(&$pdf, $sign, 0, $comment_pos['y'], 320, 5);
			$totals_pos = drawTable(&$pdf, $totals, $sign_pos['x'], $comment_pos['y'], 200, 5, $totCols);

		}
		$pdf->ezNewPage();
	}
	$pdf->ezStream();

}


?>