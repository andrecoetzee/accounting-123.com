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

# decide what to do
if (isset($HTTP_GET_VARS["invid"])) {
	$OUTPUT = details($HTTP_GET_VARS);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

# get templete
require("template.php");




# details
function details($HTTP_GET_VARS)
{

	$showvat = TRUE;

	# get vars
	extract ($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
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
	
	$td = $inv['odate'];

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($td) >= strtotime($blocked_date_from) AND strtotime($td) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

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
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}
	# check if invoice has been serialised
	if($inv['serd'] == "n"){
		$error = "<li class='err'> Error : You must select serial numbers for some Items on Invoice No. <b>T $invid</b> before you can print it.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	cus_xrate_update($inv['fcid'], $inv['xrate']);

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
	$commision = 0;
	$products = "";
	$disc = 0;

	# get selected stock in this invoice
	db_connect();

	$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

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

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl);
		
		if(pg_num_rows($Ri) < 1) {
			return "Please select the vatcode for all your stock.";
		}

		$vd = pg_fetch_array($Ri);

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

		# Get amount exluding vat if including and not exempted
		$VATP = TAX_VAT;
		$amtexvat = sprint($stkd['famt']);
		if($inv['chrgvat'] == "inc" && $stk['exvat'] != 'yes'){
			$amtexvat = sprint(($stkd['famt'] * 100)/(100 + $VATP));
		}

		db_connect();

		$sql = "
			INSERT INTO stockrec (
				edate, stkid, stkcod, stkdes, trantype, qty, csprice, csamt, 
				details, div
			) VALUES (
				'$td', '$stkd[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'invoice', '$stkd[qty]', '$amtexvat', '$csprice', 
				'Stock sold - Invoice No. $invnum', '".USER_DIV."'
			)";
		$recRslt = db_exec($sql);

		# Sales rep commission
		$commision = $commision + coms($inv['salespn'], $stkd['amt'], $stk['com']);

		# Put in product
		$products .= "
			<tr valign='top'>
				<td>$stk[stkcod]</td>
				<td>$ex $sp $stk[stkdes]</td>
				<td>".sprint3($stkd['qty'])."</td>
				<td>$inv[currency] ".sprint($stkd['unitcost'])."</td>
				<td>$inv[currency] $stkd[disc]</td>
				<td>$inv[currency] $stkd[amt]</td>
			</tr>";
	}

	/* --- Start Some calculations --- */

	# Subtotal
	$SUBTOT = sprint($inv['subtot']);


	# Calculate subtotal
	$VATP = TAX_VAT;
	$SUBTOTAL = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);

	$FSUBTOT = sprint($inv['subtot'] * $inv['xrate']);
	$FVAT = sprint($inv['vat'] * $inv['xrate']);
	$FTOTAL = sprint($inv['total'] * $inv['xrate']);
	$fdelchrg = sprint($inv['delchrg'] * $inv['xrate']);
	$ftraddiscm = sprint($inv['discount'] * $inv['xrate']);

	com_invoice($inv['salespn'], $FTOTAL, ($commision * $inv['xrate']), $invnum, $td);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT","int");
	/* - End Hooks - */

	# Todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");

	$refnum = getrefnum();
/*$refnum*/

	/* --- Updates ---- */
	db_connect();

	$Sql = "UPDATE invoices SET printed ='y', done ='y', invnum='$invnum' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");

	# Record the payment on the statement
	$sql = "
		INSERT INTO stmnt (
			cusnum, invid, amount, date, type, div, allocation_date
		) VALUES (
			'$inv[cusnum]','$invnum', '$TOTAL', '$inv[odate]', 'Invoice', '".USER_DIV."', '$inv[odate]'
		)";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	# Record the payment on the statement
	$sql = "
		INSERT INTO open_stmnt (
			cusnum, invid, amount, balance, date, type, div
		) VALUES (
			'$inv[cusnum]', '$invnum', '$TOTAL', '$TOTAL', '$inv[odate]', 'Invoice', '".USER_DIV."'
		)";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	# Save invoice discount
	$sql = "
		INSERT INTO inv_discs (
			cusnum, invid, traddisc, itemdisc, inv_date, delchrg, div, 
			total
		) VALUES (
			'$inv[cusnum]', '$invnum', '$ftraddiscm', '$disc', '$inv[odate]', '$fdelchrg', '".USER_DIV."', 
			($FSUBTOT+$fdelchrg)
		)";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	# Update the customer (make balance more)
	$sql = "UPDATE customers SET balance = (balance + '$FTOTAL'), fbalance = (fbalance + '$TOTAL') WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

	# Make ledge record
	custledger($inv['cusnum'], $dept['incacc'], $td, $invnum, "Invoice No. $invnum", $FTOTAL, "d");

	db_connect();
	# get selected stock in this invoice
	$sql = "SELECT * FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$tcosamt = 0;
	while($stkd = pg_fetch_array($stkdRslt)){
		db_connect();
		# get selamt from selected stock
		$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		# cost amount
		$cosamt = round(($stkd['qty'] * $stk['csprice']), 2);

		# update stock(alloc - qty)
		$sql = "UPDATE stock SET csamt = (csamt - '$cosamt'),units = (units - '$stkd[qty]'),alloc = (alloc - '$stkd[qty]')  WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

		###################VAT CALCS#######################
			
		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl);
		
		if(pg_num_rows($Ri) < 1) {
			return "Please select the vatcode for all your stock.";
		}

		$vd = pg_fetch_array($Ri);

		if($stk['exvat'] == 'yes'||$vd['zero']=="Yes") {
			$excluding = "y";
		} else {
			$excluding = "";
		}
	
		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$vr = vatcalc($stkd['amt'],$inv['chrgvat'],$excluding,$inv['traddisc'], $vd['vat_amount']);
		$vrs = explode("|",$vr);
		$ivat = $vrs[0];
		$iamount = $vrs[1];

		$iamount = $iamount* $inv['xrate'];
		$ivat = $ivat* $inv['xrate'];

		vatr($vd['id'],$inv['odate'],"OUTPUT",$vd['code'],$refnum,"VAT for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]",$iamount,$ivat);

		####################################################

		if($stk['serd'] == 'yes')
			ext_invSer($stkd['serno'], $stkd['stkid'], "$inv[cusname] $inv[surname]", $invnum);

		# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
		$sdate = date("Y-m-d");
		stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $td, $stkd['qty'], $cosamt, "Sold to Customer : $inv[surname] - Invoice No. $invnum");

		# get accounts
		db_conn("exten");

		$sql = "SELECT stkacc,cosacc FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);
		$stockacc = $wh['stkacc'];
		$cosacc = $wh['cosacc'];

		# dt(cos) ct(stock)
		writetrans($cosacc, $stockacc, $td, $refnum, $cosamt, "Cost Of Sales for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");
		$tcosamt += $cosamt;
	}


	###################VAT CALCS#######################
	$inv['delvat'] += 0;

	db_conn('cubit');

	$Sl = "SELECT * FROM vatcodes WHERE id='$inv[delvat]'";
	$Ri = db_exec($Sl);
	
	if(pg_num_rows($Ri) < 1) {
		$Sl = "SELECT * FROM vatcodes";
		$Ri = db_exec($Sl);
	}

	$vd = pg_fetch_array($Ri);
	
	if($vd['zero'] == "Yes") {
		$excluding = "y";
	} else {
		$excluding = "";
	}

	$vr = vatcalc($inv['delchrg'],$inv['chrgvat'],$excluding,$inv['traddisc'], $vd['vat_amount']);
	$vrs = explode("|",$vr);
	$ivat = $vrs[0];
	$iamount = $vrs[1];

	$iamount = $iamount* $inv['xrate'];
	$ivat = $ivat * $inv['xrate'];
	
	vatr($vd['id'],$inv['odate'],"OUTPUT",$vd['code'],$refnum,"VAT for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]",$iamount,$ivat);

	####################################################

	/* - Start Transactoins - */

	# dt(debtors) ct(income/sales)
	writetrans($dept['debtacc'], $dept['incacc'], $td, $refnum, ($FTOTAL-$FVAT), "Debtors Control for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");

	# dt(debtors) ct(vat account)
	writetrans($dept['debtacc'], $vatacc, $td, $refnum, $FVAT, "VAT Received on Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");

	db_connect();

	$sql = "
		INSERT INTO salesrec (
			edate, invid, invnum, debtacc, vat, total, typ, div
		) VALUES (
			'$inv[odate]', '$invid', '$invnum', '$dept[debtacc]', '$FVAT', '$FTOTAL', 'stk', '".USER_DIV."'
		)";
	$recRslt = db_exec($sql);

	db_conn('cubit');

	$Sl = "
		INSERT INTO sj (
			cid, name, des, date, exl, vat, inc, div
		) VALUES (
			'$inv[cusnum]', '$inv[surname]', 'International Invoice $invnum', '$inv[odate]', '".sprint($FTOTAL-$FVAT)."', 
			'$FVAT', '".sprint($FTOTAL)."', '".USER_DIV."'
		)";
	$Ri = db_exec($Sl);


# Commit updates
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* - End Transactoins - */

	# vat explanation
	if($inv['chrgvat'] == 'nov'){
		$expl = "VAT Exempt indicator";
	}else{
		$expl = "0% VAT indicator";
		$expl = "VAT Exempt indicator";
	}

	# Avoid little box, <table border=1> <-- ehhhemm !!
	if(strlen($inv['comm']) > 0){
		$inv['comm'] = "
			<table border='1' cellspacing='0' bordercolor='#000000'>
				<tr>
					<td>".nl2br($inv['comm'])."</td>
				</tr>
			</table>";
	}
	
	if($inv['chrgvat'] == "inc") {
		$inv['chrgvat'] = "Inclusive";
	} elseif($inv['chrgvat'] == "exc") {
		$inv['chrgvat'] = "Exclusive";
	} else {
		$inv['chrgvat'] = "No vat";
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	$cc = "<script> sCostCenter('dt', 'Sales', '$inv[odate]', 'Invoice No.$invnum for Customer $inv[cusname] $inv[surname]', '".($FTOTAL-$FVAT)."', 'Cost Of Sales for Invoice No.$invnum', '$tcosamt', ''); </script>";

	/* -- Final Layout -- */
	$details = "
		<center>
		$cc
		<h2>Tax Invoice</h2>
		<table cellpadding='0' cellspacing='4' border='0' width='750'>
			<tr>
				<td valign='top' width='30%'>
					<table ".TMPL_tblDflts.">
						<tr>
							<td>$inv[surname]</td>
						</tr>
						<tr>
							<td>".nl2br($inv['cusaddr'])."</td>
						</tr>
						<tr>
							<td>(VAT No. $inv[cusvatno])</td>
						</tr>
					</table>
				</td>
				<td valign='top' width='30%'>
					".COMP_NAME."<br>
					".COMP_ADDRESS."<br>
					".COMP_PADDR."<br>
					".COMP_TEL."<br>
					".COMP_FAX."<br>
					Reg No. ".COMP_REGNO."<br>
					VAT No. ".COMP_VATNO."<br>
				</td>
				<td width='20%'>
					<img src='compinfo/getimg.php' width='230' height='47'>
				</td>
				<td valign='bottom' align='right' width='20%'>
					<table cellpadding='2' cellspacing='0' border='1' bordercolor='#000000'>
						<tr>
							<td><b>Invoice No.</b></td>
							<td valign='center'>$invnum</td>
						</tr>
						<tr>
							<td><b>Proforma Inv No.</b></td>
							<td>$inv[docref]</td>
						</tr>
						<tr>
							<td><b>Order No.</b></td>
							<td valign='center'>$inv[ordno]</td>
						</tr>
						<tr>
							<td><b>Terms</b></td>
							<td valign='center'>$inv[terms] Days</td>
						</tr>
						<tr>
							<td><b>Invoice Date</b></td>
							<td valign='center'>$inv[odate]</td>
						</tr>
						<tr>
							<td><b>VAT</b></td>
							<td valign='center'>$inv[chrgvat]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='4'>
					<table cellpadding='5' cellspacing='0' border='1' width='100%' bordercolor='#000000'>
						<tr>
							<td><b>ITEM NUMBER</b></td>
							<td width='45%'><b>DESCRIPTION</b></td>
							<td><b>QTY</b></td>
							<td><b>UNIT PRICE</b></td>
							<td><b>DISCOUNT</b></td>
							<td><b>AMOUNT</b></td>
						<tr>
						$products
					</table>
				</td>
			</tr>
			<tr>
				<td>$inv[comm]</td>
				<td>".BNK_BANKDET."</td>
				<td align='right' colspan='2'>
					<table cellpadding='5' cellspacing='0' border='1' width='50%' bordercolor='#000000'>
						<tr>
							<td><b>SUBTOTAL</b></td>
							<td align='right'>$inv[currency] $SUBTOT</td>
						</tr>
						<tr>
							<td><b>Trade Discount</b></td>
							<td align='right'>$inv[currency] $inv[discount]</td>
						</tr>
						<tr>
							<td><b>Delivery Charge</b></td>
							<td align='right'>$inv[currency] $inv[delivery]</td>
						</tr>
						<tr>
							<td><b>VAT $vat14</b></td>
							<td align='right'>$inv[currency] $VAT</td>
						</tr>
						<tr>
							<td><b>GRAND TOTAL<b></td>
							<td align='right'>$inv[currency] $TOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td>
					<table cellpadding='2' cellspacing='0' border='1'>
						<tr>
							<td colspan='2'>$expl = #</td>
						</tr>
			        </table>
				</td>
				<td><br></td>
			</tr>
		</table>
		</center>";
	$OUTPUT = $details;
	require("tmpl-print.php");

}


?>
