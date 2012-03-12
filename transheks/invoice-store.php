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

// Merge get vars with post vars
foreach ($_GET as $key=>$val) {
	$_POST[$key] = $val;
}

// We need the invid
if (!isset($_POST["invid"])) {
	$OUTPUT = "<li class=err>Invalid use of module.</li>";
}

// Decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		default:
		case "details":
			$OUTPUT = details($_POST);
			break;
	}
} else {
	$OUTPUT = details($_POST);
}
require("template.php");




function details($_POST)
{

	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}

	db_connect();

	# Get invoice info
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	$sql = "SELECT stkid FROM inv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has no items.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}
	# check if invoice has been printed
	if($inv['printed'] == "y"){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has already been printed.</li>";
		return $error;
	}
	# check if invoice has been serialised
	if($inv['serd'] == "n"){
		$error = "<li class='err'> Error : You must select serial numbers for some Items on Invoice No. <b>T $invid</b> before you can print it.</li>";
		return $error;
	}

	db_conn('cubit');
	$showvat = TRUE;

	$invnum = divlastid('inv', USER_DIV);

	$Sl="INSERT INTO ncsrec (oldnum, newnum, div) VALUES ('$invid', '$invnum', '".USER_DIV."')";
	$Rs= db_exec ($Sl) or errDie ("Unable to insert into db");

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

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
	$products = array();
	$disc = 0;
	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	$taxex = 0;
	$i = 0;
	$page = 0;
	$salesp = qrySalesPersonN($inv["salespn"]);
	while($stkd = pg_fetch_array($stkdRslt)){
		if ($i >= 25) {
			$page++;
			$i = 0;
		}

		$stkd['account']+=0;
		if($stkd['account']==0) {
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
				$taxex += ($stkd['amt']);
				$ex = "#";
			}else{
				$ex = "";
			}

 			# all must be excempted
 			if($inv['chrgvat'] == 'nov'){
 				$ex = "#";
 			}

			# Keep track of discounts
			$disc += ($stkd['disc'] * $stkd['qty']);

			# Insert stock record
			$sdate = date("Y-m-d");
			$csprice = sprint($stk['csprice'] * $stkd['qty']);

			# Sales rep commission
			if ($salesp["com"] > 0) {
				$itemcommission = $salesp['com'];
			} else {
				$itemcommission = $stk["com"];
			}

			$commision = $commision + coms($inv['salespn'], $stkd['amt'], $itemcommission);

			# Put in product
			$products[$page][] = "
			<tr valign='top'>
				<td style='border-right: 2px solid #000'>$stk[stkcod]&nbsp;</td>
				<td style='border-right: 2px solid #000'>$ex $sp $stk[stkdes]&nbsp;</td>
				<td style='border-right: 2px solid #000'>$stkd[qty]&nbsp;</td>
				<td align='right' style='border-right: 2px solid #000'>$stkd[unitcost]&nbsp;</td>
				<td align='right' style='border-right: 2px solid #000'>$stkd[disc]&nbsp;</td>
				<td align='right'>".CUR." $stkd[amt]&nbsp;</td>
			</tr>";

			$i++;
		} else {
			db_conn('core');

			$Sl="SELECT * FROM accounts WHERE accid='$stkd[account]'";
			$Ri=db_exec($Sl) or errDie("Unable to get account data.");

			$ad=pg_fetch_array($Ri);

			db_conn('cubit');

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
			if($vd['zero']=="Yes"){
				$taxex += ($stkd['amt']);
				$ex = "#";
			}else{
				$ex = "";
			}

			# all must be excempted
			if($inv['chrgvat'] == 'nov'){
				$ex = "#";
			}

			//$commision=$commision+coms($inv['salespn'], $stkd['amt'], $stk['com']);

			# Put in product
			$products[$page][] = "
			<tr valign='top'>
				<td style='border-right: 2px solid #000'>&nbsp;</td>
				<td style='border-right: 2px solid #000'>$ex $sp $stkd[description]&nbsp;</td>
				<td style='border-right: 2px solid #000'>$stkd[qty]&nbsp;</td>
				<td align='right' style='border-right: 2px solid #000'>$stkd[unitcost]&nbsp;</td>
				<td align='right' style='border-right: 2px solid #000'>$stkd[disc]&nbsp;</td>
				<td align='right'>".CUR." $stkd[amt]&nbsp;</td>
			</tr>";

			$i++;
		}
	}

 	$blank_lines = 25;
 	foreach ($products as $key=>$val) {
 		$bl = $blank_lines - count($products[$key]);
 		for($i = 0; $i <= $bl; $i++) {
 			$products[$key][] = "
 			<tr>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td style='border-right: 2px solid #000'>&nbsp;</td>
 				<td>&nbsp;</td>
 			</tr>";
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

	com_invoice($inv['salespn'],($TOTAL-$VAT),$commision,$invnum,$inv["odate"],true);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT","VAT");
	/* - End Hooks - */

	# Todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	$refnum = getrefnum();
/*refnum*/

	if($inv['branch'] != 0){
		db_conn ("cubit");
		$get_addr = "SELECT * FROM customer_branches WHERE id = '$inv[branch]' LIMIT 1";
		$run_addr = db_exec($get_addr);
		if(pg_numrows($run_addr) < 1){
			$address = "";
		}else {
			$barr = pg_fetch_array($run_addr);
			$address = " - $barr[branch_name]";
		}
	}else {
		$address = "";
	}

	/* --- Updates ---- */
		db_connect();
		$Sql = "UPDATE invoices SET printed = 'y', done = 'y', invnum='$invnum' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");

		# Record the payment on the statement
		$sql = "
			INSERT INTO stmnt 
				(cusnum, invid, docref, amount, date, type, branch, div, allocation_date) 
			VALUES 
				('$inv[cusnum]', '$invnum', '$inv[docref]', '$inv[total]', '$inv[odate]', 'Invoice', '$address', '".USER_DIV."', '$inv[odate]')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Record the payment on the statement
		$sql = "INSERT INTO open_stmnt(cusnum, invid, docref, amount, balance, date, type, div) VALUES('$inv[cusnum]', '$invnum', '$inv[docref]', '$inv[total]','$inv[total]', '$inv[odate]', 'Invoice', '".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Save invoice discount
		$sql = "INSERT INTO inv_discs(cusnum, invid, traddisc, itemdisc, inv_date, delchrg, div,total) VALUES('$inv[cusnum]','$invnum','$traddiscm','$disc', '$inv[odate]', '$inv[delchrg]', '".USER_DIV."',($SUBTOT+$inv[delchrg]))";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Update the customer (make balance more)
		$sql = "UPDATE customers SET balance = (balance + '$inv[total]') WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Make ledge record
		custledger($inv['cusnum'], $dept['incacc'], $inv['odate'], $invnum, "Invoice No. $invnum", $inv['total'], "d");

		db_connect();
		# get selected stock in this invoice
		$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$tcosamt = 0;
		$sdate = date("Y-m-d");

		$nsp=0;

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
						$cosamt2=0;
					} else {
						$cosamt = round(($stk['units'] * $stk['csprice']), 2);
						$cosamt2 = round(($stk['units'] * $stk['csprice']), 4);
					}
				} else {
					$cosamt = round(($stkd['qty'] * $stk['csprice']), 2);
					$cosamt2 = round(($stkd['qty'] * $stk['csprice']), 4);
				}



				# update stock(alloc - qty)
				$sql = "UPDATE stock SET csamt = (csamt - '$cosamt'),units = (units - '$stkd[qty]'),alloc = (alloc - '$stkd[qty]')  WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

				###################VAT CALCS#######################

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

				$amtexvat = sprint($stkd['amt']);

			//	$uc=sprint($cosamt2/$stkd['qty']);
				$uc=round($cosamt2/$stkd['qty'],4);
				db_connect();
				$sql = "INSERT INTO stockrec(edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, details, div)
				VALUES('$inv[odate]', '$stkd[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'invoice', '$stkd[qty]', '$amtexvat', '$cosamt', 'Stock sold - Invoice No. $invnum', '".USER_DIV."')";
				$recRslt = db_exec($sql);

				if($stk['csprice']>0) {
					$Sl="INSERT INTO scr(inv,stkid,amount,invid) VALUES ('$invnum','$stkd[stkid]','$uc','$stkd[id]')";
					$Rg=db_exec($Sl);
				}

				if($stk['serd'] == 'yes')
					ext_invSer($stkd['serno'], $stkd['stkid'], "$inv[cusname] $inv[surname]", $invnum);

				# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
				$sdate = date("Y-m-d");
				stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $inv['odate'], $stkd['qty'], $cosamt, "Sold to Customer : $inv[surname] - Invoice No. $invnum");

				# get accounts
				db_conn("exten");
				$sql = "SELECT stkacc,cosacc FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);
				$stockacc = $wh['stkacc'];
				$cosacc = $wh['cosacc'];
				if($cosamt<0) {
					$cosamt=0;
				}
				# dt(cos) ct(stock)
				writetrans($cosacc, $stockacc,$inv['odate'] , $refnum, $cosamt, "Cost Of Sales for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");
				$tcosamt += $cosamt;
			} else {
				db_connect();

				###################VAT CALCS#######################

				$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
				$Ri=db_exec($Sl);

				if(pg_num_rows($Ri)<1) {
					return "Please select the vatcode for all your stock.";
				}

				$vd=pg_fetch_array($Ri);

				if($vd['zero']=="Yes") {
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

				$amtexvat = sprint($stkd['amt']);
				db_connect();
				$sdate = date("Y-m-d");

				$nsp+=sprint($iamount-$ivat);

				writetrans($dept['debtacc'], $stkd['account'],$inv['odate'], $refnum, ($iamount-$ivat), "Debtors Control for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");

			}
		}

		###################VAT CALCS#######################
		$inv['delvat']+=0;
		db_conn('cubit');
		$Sl="SELECT * FROM vatcodes WHERE id='$inv[delvat]'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			$Sl="SELECT * FROM vatcodes";
			$Ri=db_exec($Sl);
		}

		$vd=pg_fetch_array($Ri);

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		if($vd['zero']=="Yes") {
			$excluding="y";
		} else {
			$excluding="";
		}

		$vr=vatcalc($inv['delchrg'],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
		$vrs=explode("|",$vr);
		$ivat=$vrs[0];
		$iamount=$vrs[1];

		vatr($vd['id'],$inv['odate'],"OUTPUT",$vd['code'],$refnum,"VAT for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]",sprint($iamount+$ivat),$ivat);

		####################################################


	/* - Start Transactoins - */

		# dt(debtors) ct(income/sales)
		writetrans($dept['debtacc'], $dept['incacc'],$inv['odate'], $refnum, sprint($TOTAL-$VAT-$nsp), "Debtors Control for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");

		# dt(debtors) ct(vat account)
		writetrans($dept['debtacc'], $vatacc, $inv['odate'], $refnum, $VAT, "VAT Received on Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");

		db_conn('cubit');

		$Sl="INSERT INTO sj(cid,name,des,date,exl,vat,inc,div) VALUES
		('$inv[cusnum]','$inv[surname]','Invoice $invnum','$inv[odate]','".sprint($TOTAL-$VAT)."','$VAT','".sprint($TOTAL)."','".USER_DIV."')";
		$Ri=db_exec($Sl);

		db_connect();
		$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
		VALUES('$inv[odate]', '$invid', '$invnum', '$dept[debtacc]', '$VAT', '$TOTAL', 'stk', '".USER_DIV."')";
		$recRslt = db_exec($sql);

	//exit;



# Commit updates
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* - End Transactoins - */

	# vat explanation
	if($inv['chrgvat'] == 'nov'){
		$expl = "VAT Exempt indicator";
	}else{
		$expl = "VAT Exempt indicator";
	}

	if(strlen($inv['comm']) > 0){
		$inv['comm'] = "
		<table border='1' cellspacing='0' bordercolor='#000000'>
			<tr><td>Remarks:</td><td>".nl2br($inv['comm'])."</td></tr>
		</table>";
	}

	$cc = "<script> sCostCenter('dt', 'Sales', '$inv[odate]', 'Invoice No.$invnum for Customer $inv[cusname] $inv[surname]', '".($TOTAL-$VAT)."', 'Cost Of Sales for Invoice No.$invnum', '$tcosamt', ''); </script>";

	db_conn('cubit');

	$Sl="SELECT * FROM settings WHERE constant='SALES'";
	$Ri=db_exec($Sl) or errDie("Unable to get settings.");

	$data=pg_fetch_array($Ri);

	if($data['value']=="Yes") {
		$sp="<tr>
			<td><b>Sales Person:</b> $inv[salespn]</td>
		</tr>";
	} else {
		$sp="";
	}

	if($inv['chrgvat']=="inc") {
		$inv['chrgvat']="Inclusive";
	} elseif($inv['chrgvat']=="exc") {
		$inv['chrgvat']="Exclusive";
	} else {
		$inv['chrgvat']="No vat";
	}

	if($inv['branch'] == 0){
		$branchname = "Head Office";
	}else {
		$get_bname = "SELECT * FROM customer_branches WHERE id = '$inv[branch]' LIMIT 1";
		$run_bname = db_exec($get_bname);
		if(pg_numrows($run_bname) < 1){
			$branchname = "";
		}else {
			$arr = pg_fetch_array($run_bname);
			$branchname = $arr['branch_name'];
		}
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	if(strlen(COMP_TEL) > 0){
		$showtel = "Tel: ";
	}else {
		$showtel = "";
	}

	if(strlen(COMP_FAX) > 0){
		$showfax = "Fax: ";
	}else {
		$showfax = "";
	}

	// Retrieve the company information
	db_conn("cubit");
	$sql = "SELECT * FROM compinfo";
	$comp_rslt = db_exec($sql) or errDie("Unable to retrieve company information from Cubit.");
	$comp_data = pg_fetch_array($comp_rslt);

	// Retrieve the banking information
	$bank_data = qryBankAcct(getdSetting("BANK_DET"));

	// Retrieve customer information
	db_conn("cubit");
	$sql = "SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer information from Cubit.");
	$cust_data = pg_fetch_array($cust_rslt);

	$table_borders = "
		border-top: 2px solid #000000;
		border-left: 2px solid #000000;
		border-right: 2px solid #000000;
		border-bottom: none;
	";

	$details = "";

	for ($i = 0; $i <= $page; $i++) {
		// new page?
		if ($i > 1) {
			$details .= "<br style='page-break-after:always;'>";
		}

		$products_out = "";
		foreach ($products[$i] as $string) {
			$products_out .= $string;
		}

		$details .= "
		<center>
		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table border='0' cellpadding='2' cellspacing='2' width='100%'>
				<tr>
					<td align='left'><img src='compinfo/getimg.php' width=230 height=47></td>
					<td align='left'><font size='5'><b>".COMP_NAME."</b></font></td>
					<td align='right'><font size='5'><b>Tax Invoice</b></font></td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td valign='top'>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'>$comp_data[addr1]&nbsp;</td>
					<td style='border-right: 2px solid #000'>$comp_data[paddr1]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$comp_data[addr2]&nbsp;</td>
					<td style='border-right: 2px solid #000'>$comp_data[paddr2]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$comp_data[addr3]&nbsp;</td>
					<td style='border-right: 2px solid #000'>$comp_data[paddr3]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$comp_data[addr4]&nbsp;</td>
					<td style='border-right: 2px solid #000'>$comp_data[postcode]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>REG:</b> $comp_data[regnum]</b>&nbsp;</td>
					<td style='border-right: 2px solid #000'><b>$bank_data[bankname]</b>&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>VAT REG:</b> $comp_data[vatnum]&nbsp;</td>
					<td style='border-right: 2px solid #000'><b>Branch</b> $bank_data[branchname]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Tel:</b> $comp_data[tel]&nbsp;</td>
					<td style='border-right: 2px solid #000'><b>Branch Code:</b> $bank_data[branchcode]&nbsp;</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Fax:</b> $comp_data[fax]&nbsp;</td>
					<td style='border-right: 2px solid #000'><b>Acc Num:</b> $bank_data[accnum]&nbsp;</td>
				</tr>
			</table>
			</td><td valign='top'>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'><b>Date</b></td>
					<td><b>Page Number</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>$inv[odate]</td>
					<td>".($i + 1)."</td>
				</tr>

				<tr>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000'>&nbsp</td>
					<td style='border-bottom: 2px solid #000'>&nbsp</td>
				</tr>
				<tr><td>&nbsp</td></tr>

				<tr>
					<td colspan='2'><b>Invoice No:</b> $invnum</td>
				</tr>
				<tr>
					<td colspan='2'><b>Proforma Inv No:</b> $inv[docref]</td>
				</tr>
				<tr>
					<td colspan='2'><b>Sales Order No:</b> $inv[ordno]</td>
				</tr>
				$sp
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td align='center'><font size='4'><b>Tax Invoice To:</b></font></td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td width='33%' style='border-right: 2px solid #000'><b>$inv[surname]</b></td>
					<td width='33%' style='border-right: 2px solid #000'><b>Postal Address</b></td>
					<td width='33%'><b>Delivery Address</td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>".nl2br($cust_data["addr1"])."</td>
					<td style='border-right: 2px solid #000'>".nl2br($cust_data["paddr1"])."</td>
					<td>Branch: $branchname<br />".nl2br($inv["del_addr"])."</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td width='33%' style='border-right: 2px solid #000'><b>Customer VAT No:</b> $inv[cusvatno]</td>
					<td width='33%' style='border-right: 2px solid #000'><b>Customer Order No:</b> $inv[cordno]</td>
					<td width='33%'><b>Delivery Date:</b> $inv[deldate]</td>
				</tr>
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;'><b>Code</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;'><b>Description</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;'><b>Qty</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;' align='right'><b>Unit Price</b></td>
					<td style='border-bottom: 2px solid #000; border-right: 2px solid #000;' align='right'><b>Unit Discount</b></td>
					<td style='border-bottom: 2px solid #000' align='right'><b>Amount</b></td>
				</tr>
				$products_out
			</table>
			</td></tr>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='$table_borders'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td><i>VAT Exempt Indicator: #</i></td>
				</tr>
				<tr>
					<td>$inv[comm]</td>
				</tr>
			</table>
		</table>

		<table cellpadding='0' cellspacing='0' width='85%' style='border: 2px solid #000000'>
			<tr><td>
			<table cellpadding='2' cellspacing='0' border='0' width='100%'>
				<tr>
					<td style='border-right: 2px solid #000'><b>Terms: $inv[terms] days</b></td>
					<td><b>Subtotal:</b></td>
					<td><b>".CUR."$inv[subtot]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>Trade Discount:</b></td>
					<td><b>".CUR."$inv[discount]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Received in good order by:</b>_____________________</td>
					<td><b>Delivery Charge</b></td>
					<td><b>".CUR."$inv[delivery]</b></td>
				</tr>
				<tr>
					<td style='border-right: 2px solid #000'>&nbsp;</td>
					<td><b>VAT $vat14:</b></td>
					<td><b>".CUR."$inv[vat]</b></td>
				<tr>
				<tr>
					<td style='border-right: 2px solid #000'><b>Date:</b>_____________________</td>
					<td><b>Total Incl VAT:</b></td>
					<td><b>".CUR."$inv[total]</b></td>
				</tr>
				</tr>
			</table>
		</table>";
	}

	// Retrieve template settings from Cubit
	db_conn("cubit");
	$sql = "SELECT filename FROM template_settings WHERE template='invoices'";
	$tsRslt = db_exec($sql) or errDie("Unable to retrieve the template settings from Cubit.");
	$template = pg_fetch_result($tsRslt, 0);

	$OUTPUT = "
	<script>
		sCostCenter('dt', 'Sales', '$date', 'Invoice No.$invnum for Customer $inv[cusname] $inv[surname]', '".($TOTAL-$VAT)."', 'Cost Of Sales for Invoice No.$invnum', '$tcosamt', '');
	</script>";

	if ($template == "invoice-print.php") {
		$OUTPUT .= $details;
		require("tmpl-print.php");
	} else {
		$OUTPUT .= "
		<script>
		  move(\"$template?invid=$inv[invid]&type=inv\");
		</script>";
		require("template.php");
	}
}
?>
