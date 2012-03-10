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
if (isset($HTTP_GET_VARS["invid"])) {
	if (isset($HTTP_GET_VARS["key"])) {
		switch ($HTTP_GET_VARS["key"]) {
			case "details":
				$OUTPUT = details($HTTP_GET_VARS);
				break;
			case "cash_receipt":
				$OUTPUT = cash_receipt();
				break;
			case "reprint":
				$OUTPUT = reprint();
				break;
		}
	} else {
		$OUTPUT = details($HTTP_GET_VARS);
	}
} else {
	$OUTPUT = "<li class=err>Invalid use of module.";
}

# get templete
require("../template.php");

# details
function details($HTTP_GET_VARS)
{

	extract($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM hire.hire_invoices WHERE invid = '$invid'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found[1]</i>";
	}
	$inv = pg_fetch_array($invRslt);

	// Cash deposit
	if ($inv["deposit_type"] == "CSH" && $inv["deposit_amt"] > 0) {
		$get_ar = array();
		foreach ($HTTP_GET_VARS as $key=>$value) {
			if ($key != "key") {
				$get_ar[] = "$key=$value";
			}
		}
		$get_vars = implode("&", $get_ar);

		$deposit_receipt = "<script>
								printer(\"hire/".SELF."?key=deposit$get_vars\")
							</script>";
	} else {
		$deposit_receipt = "";
	}

	if($inv['rounding']>0) {
		db_conn('core');
		$Sl="SELECT * FROM salesacc WHERE name='rounding'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			return "Please set the rounding account, under sales settings.";
		}

		$ad=pg_fetch_array($Ri);

		$rac=$ad['accnum'];

	}

	if($inv['cusnum'] != "0"){
		#then get the actual customer
		db_connect ();
		$get_cus = "SELECT * FROM customers WHERE cusnum = '$inv[cusnum]' LIMIT 1";
		$run_cus = db_exec($get_cus) or errDie("Unable to get customer information");
		if(pg_numrows($run_cus) < 1){
			#do nothing
		}else {
			$carr = pg_fetch_array($run_cus);
			$inv['cusname'] = "$carr[cusname]";
			$inv['surname'] = "$carr[surname]";
		}
	}

	$td=$inv['odate'];

	db_conn('cubit');

	$sql = "SELECT asset_id FROM hire.hire_invitems WHERE invid = '$inv[invid]'";
	$crslt = db_exec($sql);

	if($inv['terms']==1) {
		db_conn('core');

		$Sl="SELECT * FROM salacc WHERE name='cc'";
		$Ri=db_exec($Sl);

		if(pg_num_rows($Ri)<1) {
			return "Please set a link for the POS credit card control account";
		}

		$cd=pg_fetch_array($Ri);

		$cc=$cd['accnum'];
	}

	$change=sprint(sprint($inv['pcash']+$inv['pcheque']+$inv['pcc']+$inv['pcredit'])-sprint($inv['total']-$inv['rounding']));

	$inv['pcash']=sprint($inv['pcash']-$change);

	if($inv['pcash']<0) {
		$inv['pcash']=0;
	}

	if(sprint($inv['pcash']+$inv['pcheque']+$inv['pcc']+$inv['pcredit'])!=sprint($inv['total']-$inv['rounding'])) {

		return "<li class=err>The total of all the payments is not equal to the invoice total.<br>
		Please edit the invoice and try again(You can only overpay with cash)</li>";

	}

	db_connect();

	pglib_transaction("BEGIN");

	$invnum = getHirenum($invid, 1);
	
	$sql = "UPDATE hire.monthly_invitems SET invnum='$invnum' WHERE invid='$invid'";
	db_exec($sql) or errDie("Unable to assign hire number to monthly.");

	$sql = "UPDATE hire.reprint_invoices SET invnum='$invnum' WHERE invid='$invid'";
	db_exec($sql) or errDie("Unable to assign hire invoice number.");

	$Sl="INSERT INTO ncsrec (oldnum,newnum, div) VALUES ('$invid','$invnum', '".USER_DIV."')";
	$Rs= db_exec ($Sl) or errDie ("Unable to insert into db");

	//unlock(2);

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class=err>Not Found[2]</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;
	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM hire.hire_invitems  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$taxex = 0;

	$commision=0;
	while($stkd = pg_fetch_array($stkdRslt)){

		$stkd['account']+=0;

		if($stkd['account']==0) {

			# get warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# get selected stock in this warehouse
			db_connect();
			$sql = "SELECT * FROM assets WHERE id = '$stkd[asset_id]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			db_connect();
			//this was set to the stock vatcode ??? must be the pur_item code ...
			$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
			$Ri=db_exec($Sl);

			if(pg_num_rows($Ri)<1) {
				return "<li class='err'>Please select the vatcode for all your stock.</li>";
			}

			$vd=pg_fetch_array($Ri);

			$sp = "&nbsp;&nbsp;&nbsp;&nbsp;";
			# Check Tax Excempt
			if($stk['exvat'] == 'yes'||$vd['zero']=="Yes"){
				$taxex += ($stkd['amt']);
				$ex = "#";
			}else{
				$ex = "&nbsp;&nbsp;";
			}

			# Keep track of discounts
			$disc += ($stkd['disc'] * $stkd['qty']);

			# Insert stock record
			$sdate = date("Y-m-d");
			$csprice = sprint($stk['csprice'] * $stkd['qty']);

			# put in product
			$products .="<tr valign=top>
				<td>$stk[stkcod]</td>
				<td>$ex $sp $stk[stkdes]</td>
				<td>$stkd[qty]</td>
				<td>".sprint($stk["selamt"])."</td>
				<td>".CUR. sprint($stkd["amt"])."</td>
			</tr>";

			# Get amount exluding vat if including and not exempted
			$VATP = TAX_VAT;
			$amtexvat = sprint($stkd['amt']);
			if($inv['chrgvat'] == "inc" && $stk['exvat'] != 'yes'){
				$amtexvat = sprint(($stkd['amt'] * 100)/(100 + $VATP));
			}

			$commision=$commision+coms($inv['salespn'],$stkd['amt'],$stk['com']);
		}else{
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
			$products.="<tr valign=top>
				<td></td>
				<td>$ex $sp $stkd[description]</td>
				<td>$stkd[qty]</td>
				<td>".sprint($stkd["unitcost"])."</td>
				<td>$stkd[disc]</td>
				<td>".CUR. sprint($stkd["amt"])."</td>
			</tr>";
		}
	}

	/* --- Start Some calculations --- */

	# subtotal
	$SUBTOT = sprint($inv['subtot']);

	# Calculate subtotal
	$VATP = TAX_VAT;
	$SUBTOTAL = sprint($inv['subtot']);
	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

	$av=$VAT;
	$at=$TOTAL-$VAT;

	$nt=sprint($inv['pcredit']);

	$sd=date("Y-m-d");

	$ro=$inv['rounding'];
	$ro+=0;

	com_invoice($inv['salespn'],($TOTAL-$VAT),$commision,$invnum);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT","novat");
	/* - End Hooks - */

	$nsp=0;
	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");
	db_conn('cubit');

	if($inv['cusnum']>0&&$nt>0) {
		# Record the payment on the statement
		$sql = "INSERT INTO stmnt(cusnum, invid, docref, amount, date, type, div) VALUES('$inv[cusnum]', '$invnum', '0', '$nt', '$inv[odate]', 'Invoice', '".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Record the payment on the statement
		$sql = "INSERT INTO open_stmnt(cusnum, invid, docref, amount, balance, date, type, div) VALUES('$inv[cusnum]', '$invnum', '0', '$nt', '$nt', '$inv[odate]', 'Invoice', '".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Update the customer (make balance more)
		$sql = "UPDATE customers SET balance = (balance + '$nt') WHERE cusnum = '$inv[cusnum]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);


		custledger($inv['cusnum'], $dept['incacc'], $inv['odate'], $invnum, "Invoice No. $invnum", $nt, "d");

		recordDT($nt, $inv['cusnum'],$inv['odate']);


		db_conn('cubit');

		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$sd','".USER_NAME."','$invnum','$nt','Credit','".PRD_DB."','0')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");
	}

	db_conn('cubit');

	if($inv['terms']==1) {
		$Sl="INSERT INTO crec(userid,username,amount,pdate,inv) VALUES ('".USER_ID."','".USER_NAME."','$TOTAL','$td','$invnum')";
		$Ry=db_exec($Sl) or errDie("Unable to insert pos record.");
	} else {
		$Sl="INSERT INTO posrec(userid,username,amount,pdate,inv) VALUES ('".USER_ID."','".USER_NAME."','$TOTAL','$td','$invnum')";
		$Ry=db_exec($Sl) or errDie("Unable to insert pos record.");
	}

	$Sl="INSERT INTO pr(userid,username,amount,pdate,inv,cust,t) VALUES ('".USER_ID."','".USER_NAME."','$TOTAL','$td','$invnum','$inv[cusname]','$inv[terms]')";
	$Ry=db_exec($Sl) or errDie("Unable to insert pos record.");

	$refnum = getrefnum();

	$fcash=$inv['pcash'];
	$fccp=$inv['pcc'];
	$fcheque=$inv['pcheque'];
	$fcredit=$inv['pcredit'];

	/* --- Updates ---- */
	db_connect();

	$Sql = "UPDATE hire.hire_invoices SET pchange='$change',printed = 'y', done ='y',invnum='$invnum' WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");

	# save invoice discount
	$sql = "INSERT INTO inv_discs(cusnum, invid, traddisc, itemdisc, inv_date, delchrg, div,total) VALUES('0','$invnum','$inv[delivery]','$disc', '$inv[odate]', '$inv[delivery]', '".USER_DIV."',($SUBTOT+$inv[delivery]))";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

	# get selected stock in this invoice
	$sql = "SELECT * FROM hire.hire_invitems  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$tcosamt = 0;

	if(strlen($inv['comm'])>0){
		$Com="<table><tr><td>".nl2br($inv['comm'])."</td></tr></table>";
	} else {$Com="";}

	$cc = "<script> sCostCenter('dt', 'Sales', '$date', 'POS Invoice No.$invnum', '".($TOTAL-$VAT)."', 'Cost Of Sales for Invoice No.$invnum', '$tcosamt', ''); </script>";

	if($inv['chrgvat']=="inc") {
		$inv['chrgvat']="Inclusive";
	} elseif($inv['chrgvat']=="exc") {
		$inv['chrgvat']="Exclusive";
	} else {
		$inv['chrgvat']="No vat";
	}

	/* - End Transactoins - */

	/* -- Final Layout -- */
	$details = "<center>
	$deposit_receipt $cc
	<h2>Tax Invoice</h2>
	<table cellpadding='0' cellspacing='1' border=0 width=750>
	<tr><td valign=top width=40%>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><td>$inv[surname]</td></tr>
		</table>
	</td><td valign=top width=35%>
		".COMP_NAME."<br>
		".COMP_ADDRESS."<br>
		".COMP_TEL."<br>
		".COMP_FAX."<br>
		Reg No. ".COMP_REGNO."<br>
	</td><td valign=bottom align=right width=25%>
		<table cellpadding='2' cellspacing='0' border=1 bordercolor='#000000'>
			<tr><td><b>Hire No.</b></td><td valign=center>H".getHirenum($inv["invid"], 1)."</td></tr>
			<tr><td><b>Order No.</b></td><td valign=center>$inv[ordno]</td></tr>
			<tr><td><b>Terms</b></td><td valign=center>Cash</td></tr>
			<tr><td><b>Invoice Date</b></td><td valign=center>$inv[odate]</td></tr>
			<tr><td><b>VAT</b></td><td valign=center>$inv[chrgvat]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=3>
	<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
		<tr><th>ITEM NUMBER</th><th width=45%>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>AMOUNT</th><tr>
		$products
	</table>
	</td></tr>
	<tr><td>
		$inv[custom_txt]
		$Com
	</td><td align=right colspan=2>
		<table cellpadding='5' cellspacing='0' border=1 width=50% bordercolor='#000000'>
			<tr><td><b>SUBTOTAL</b></td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr><td><b>Trade Discount</b></td><td align=right>".CUR." $inv[discount]</td></tr>
			<tr><td><b>Delivery Charge</b></td><td align=right>".CUR." $inv[delivery]</td></tr>
			<tr><td><b>VAT @ $VATP%</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr><th><b>GRAND TOTAL<b></th><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=1>
			<tr><td colspan=2>VAT Exempt indicator = #</td></tr>
			<tr><th>VAT No.</th><td align=center>".COMP_VATNO."</td></tr>
        </table>
	</td><td><br></td></tr>
	</table></center>";


	/* Start moving invoices */

	db_connect();
	# Move invoices that are fully paid
	$sql = "SELECT * FROM hire.hire_invoices WHERE invid='$invid'";
	$invbRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

	$time2 = time();
	while($invb = pg_fetch_array($invbRslt))
	{
		$invb['invnum'] += 0;
		# Insert invoice to period DB
		$sql = "INSERT INTO hire.hire_invoices(invid,invnum, deptid, cusnum, deptname, cusacc, cusname, surname, cusaddr, cusvatno, cordno, ordno, chrgvat, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, balance, comm, printed, done, div, username,rounding,delvat,vatnum,pcash,pcheque,pcc,pcredit)";
		$sql .= " VALUES('$invb[invid]','$invb[invnum]', '$invb[deptid]', '$invb[cusnum]', '$invb[deptname]', '$invb[cusacc]', '$invb[cusname]', '$invb[surname]', '$invb[cusaddr]', '$invb[cusvatno]', '$invb[cordno]', '$invb[ordno]', '$invb[chrgvat]', '$invb[terms]', '$invb[traddisc]', '$invb[salespn]', '$invb[odate]', '$invb[delchrg]', '$invb[subtot]', '$invb[vat]' , '$invb[total]', '$invb[balance]', '$invb[comm]', 'y', 'y', '".USER_DIV."','".USER_NAME."','$invb[rounding]','$invb[delvat]','$invb[vatnum]','$invb[pcash]','$invb[pcheque]','$invb[pcc]','$invb[pcredit]')";
		//$rslt = db_exec($sql) or errDie("Unable to insert invoice to the period database.",SELF);

		$sql = "SELECT * FROM hire.monthly_invoices WHERE invid='$invb[invid]'";
		$hi_rslt = db_exec($sql) or errDie("Unable to retrieve hire invoice.");
		if (pg_num_rows($hi_rslt)) {
			$sql = "UPDATE hire.monthly_invoices SET invnum='$invb[invnum]',
						deptid='$invb[deptid]', cusnum='$invb[cusnum]',
						deptname='$invb[deptname]', cusacc='$invb[cusacc]',
						cusname='$invb[cusname]', surname='$invb[surname]',
						cusaddr='$invb[cusaddr]', cusvatno='$invb[cusvatno]',
						cordno='$invb[cordno]', ordno='$invb[ordno]',
						chrgvat='$invb[chrgvat]', terms='$invb[terms]',
						traddisc='$invb[traddisc]', salespn='$invb[salespn]',
						odate='$invb[odate]', delchrg='$invb[delchrg]',
						subtot='$invb[subtot]', vat='$invb[vat]',
						total='$invb[total]', balance='$invb[balance]',
						comm='$invb[comm]', printed='$invb[printed]',
						done='$invb[done]', div='$invb[div]',
						username='$invb[username]', rounding='$invb[rounding]',
						delvat='$invb[delvat]', vatnum='$invb[vatnum]',
						pcash='$invb[pcash]', pcheque='$invb[pcheque]',
						pcc='$invb[pcc]', pcredit='$invb[pcredit]'
					WHERE invid='$invb[invid]'";
				db_exec($sql) or errDie("Unable to store monthly invoice.");
				$mi_invid = $invb["invid"];
		} else {
			$sql = "INSERT INTO hire.monthly_invoices(invid, invnum, deptid, cusnum, deptname, cusacc, cusname, surname, cusaddr, cusvatno, cordno, ordno, chrgvat, terms, traddisc, salespn, odate, delchrg, subtot, vat, total, balance, comm, printed, done, div, username,rounding,delvat,vatnum,pcash,pcheque,pcc,pcredit, invoiced_month)";
			$sql .= " VALUES('$invb[invid]', '$invb[invnum]', '$invb[deptid]', '$invb[cusnum]', '$invb[deptname]', '$invb[cusacc]', '$invb[cusname]', '$invb[surname]', '$invb[cusaddr]', '$invb[cusvatno]', '$invb[cordno]', '$invb[ordno]', '$invb[chrgvat]', '$invb[terms]', '$invb[traddisc]', '$invb[salespn]', '$invb[odate]', '$invb[delchrg]', '$invb[subtot]', '$invb[vat]' , '$invb[total]', '$invb[balance]', '$invb[comm]', 'y', 'y', '".USER_DIV."','".USER_NAME."','$invb[rounding]','$invb[delvat]','$invb[vatnum]','$invb[pcash]','$invb[pcheque]','$invb[pcc]','$invb[pcredit]', '".date("m")."')";
			db_exec($sql) or errDie("Unable to store monthly invoice.");
			db_conn("hire");
			$mi_invid = pglib_lastid("monthly_invoices", "invid");
		}

		$sql = "SELECT * FROM hire.hire_invitems WHERE invid='$invb[invid]'";
		$invi_rslt = db_exec($sql) or errDie("Unable to retrieve note items.");

// 		while ($invi = pg_fetch_array($invi_rslt)) {
// 			if (isset($monthly) && $monthly) {
// 				$sql = "DELETE FROM hire.monthly_invitems WHERE invid='$mi_invid'";
// 				db_exec($sql) or errDie("Unable to remove items.");
// 
// 				$sql = "INSERT INTO hire.monthly_invitems (invid, asset_id, qty,
// 							unitcost, amt, disc, discp, serno, div, vatcode, account,
// 							description, basis, from_date, to_date, hours, weeks,
// 							collection)
// 						VALUES ('$mi_invid', '$invi[asset_id]',
// 							'$invi[qty]', '$invi[unitcost]', '$invi[amt]',
// 							'$invi[disc]', '$invi[discp]',	'$invi[serno]',
// 							'".USER_DIV."',	'$invi[vatcode]', '$invi[account]',
// 							'$invi[description]', '$invi[basis]', '$invi[from_date]',
// 							'$invi[to_date]', '$invi[hours]', '$invi[weeks]',
// 							'$invi[collection]')";
// 				db_exec($sql) or errDie("Unable to create montly item.");
// 			}
// 		}

		db_connect();
		$sql = "INSERT INTO movinv(invtype, invnum, prd, docref, div) VALUES('pos', '$invb[invnum]', '$invb[prd]', '', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to the period database.",SELF);
		# get selected stock in this invoice
		db_connect();
		$sql = "SELECT * FROM hire.hire_invitems WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			# insert invoice items
			$stkd['vatcode']+=0;
			$stkd['account']+=0;
			$sql = "INSERT INTO hire.hire_invitems(invid, whid, asset_id, qty,
						unitcost, amt, disc, discp, serno, div, vatcode, account,
						description)
					VALUES ('$invb[invid]', '$stkd[whid]',
						'$stkd[asset_id]', '$stkd[qty]', '$stkd[unitcost]',
						'$stkd[amt]', '$stkd[disc]', '$stkd[discp]',
						'$stkd[serno]', '".USER_DIV."', '$stkd[vatcode]',
						'$stkd[account]', '$stkd[description]')";

			$sql = "INSERT INTO hire.monthly_items (invid, whid, asset_id, qty,
						unitcost, amt, disc, discp, serno, div, vatcode, account,
						description)
					VALUES ('$invb[invid]', '$stkd[whid]', '$stkd[asset_id]',
						'$stkd[qty]', '$stkd[unitcost]', '$stkd[amt]',
						'$stkd[disc]', '$stkd[discp]',	'$stkd[serno]',
						'".USER_DIV."',	'$stkd[vatcode]', '$stkd[account]',
						 '$stkd[desciption]')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
		}
	}

	// Update assets
	$sql = "SELECT * FROM hire.hire_invitems WHERE invid='$inv[invid]'";
	$item_rslt = db_exec($sql) or errDie("Unable to update items.");

	while ($item_data = pg_fetch_array($item_rslt)) {
		if (!isSerialized($item_data["asset_id"])) {
			$sql = "SELECT serial2 FROM cubit.assets
						WHERE id='$item_data[asset_id]'";
			$qty_rslt = db_exec($sql) or errDie("Unable to retrieve qty.");
			$qty = pg_fetch_result($qty_rslt, 0);

			$qty = $qty - $item_data["qty"];

			$sql = "UPDATE cubit.assets SET serial2='$qty'
						WHERE id='$item_data[asset_id]'";
			db_exec($sql) or errDie("Unable to update assets.");

			$sql = "SELECT id, units FROM hire.bookings
						WHERE cust_id='$inv[cusnum]' AND
							asset_id='$item_data[asset_id]'";
			$bk_rslt = db_exec($sql) or errDie("Unable to retrieve booking.");
			$bk_data = pg_fetch_array($bk_rslt);

			// Update booking information.
			if (!empty($bk_data["id"])) {
				if (($bk_data["units"] - $item_data["qty"]) <= 0) {
					$sql = "DELETE FROM hire.bookings WHERE id='$bk_data[id]'";
				} else {
					$new_qty = $bk_data["units"] - $item_data["qty"];
					$sql = "UPDATE hire.bookings SET units=(units-'$new_qty')
								WHERE id='$bk_data[id]'";
				}
				db_exec($sql) or errDie("Unable to update bookings.");
			}

			$item_qty = $item_data["qty"];
		} else {
			$sql = "DELETE FROM hire.bookings WHERE cust_id='$inv[cusnum]'
						AND asset_id='$item_data[asset_id]'";
			db_exec($sql) or errDie("Unable to remove booking.");

			$item_qty = 1;
		}
		
		$discount = $item_data["amt"] / 100 * $inv["traddisc"];

		$sql = "INSERT INTO hire.assets_hired (invid, asset_id, hired_time, qty,
					 item_id, cust_id, invnum, basis, value, discount, weekends)
				VALUES ('$invid', '$item_data[asset_id]', CURRENT_TIMESTAMP,
					'$item_qty', '$item_data[id]', '$inv[cusnum]',
					'$inv[invnum]', '$item_data[basis]', '$item_data[amt]',
					'$discount', '$item_data[weekends]')";
		db_exec($sql) or errDie("Unable to hire out item.");
	}

	# Commit updates
	pglib_transaction("COMMIT");

	header("Location: hire-slip.php?invid=$inv[invid]&prd=$inv[prd]&cccc=yes");
	exit;
}

function recordDT($amount, $cusnum,$odate)
{
	db_connect();

	# Check for previous transactions
	$sql = "SELECT * FROM custran WHERE cusnum = '$cusnum' AND balance < 0 AND div = '11111' ORDER BY odate ASC";
	$rs  = db_exec($sql) or errDie("Unable to get analysis records from Cubit.",SELF);
	if(pg_numrows($rs) > 0){
		while($dat = pg_fetch_array($rs)){
			if(floatval($amount) > 0){
				if($dat['balance'] <= $amount){
					# Remove make amount less
					$sql = "UPDATE custran SET balance = (balance + '$amount') WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
					$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					$amount = 0;
				}else{
					# remove small ones
					if($dat['balance'] < $amount){
						$amount -= $dat['balance'];
						$sql = "DELETE FROM custran WHERE id = '$dat[id]' AND div = '".USER_DIV."'";
						$dRs  = db_exec($sql) or errDie("Unable to update analysis records from Cubit.",SELF);
					}
				}
			}
		}
		if($amount > 0){
			/* Make transaction record for age analysis */
			//$odate = date("Y-m-d");
			$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
			$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
		}
	}else{
		/* Make transaction record for age analysis */
		//$odate = date("Y-m-d");
		$sql = "INSERT INTO custran(cusnum, odate, balance, div) VALUES('$cusnum', '$odate', '$amount', '".USER_DIV."')";
		$purcRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);
	}

	# Remove all empty entries
	$sql = "DELETE FROM custran WHERE balance = 0 AND fbalance = 0 AND div = '".USER_DIV."'";
	$rs = db_exec($sql);
}

function cash_receipt()
{
	extract ($_REQUEST);

	$sql = "SELECT * FROM hire.hire_invoices WHERE invid='$invid'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve note.");
	$inv = pg_fetch_array($inv_rslt);

	// Retrieve customer account
	$sql = "SELECT accid FROM core.accounts WHERE topacc='6400' AND accnum='000'";
	$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
	$cust_acc = pg_fetch_result($acc_rslt, 0);

	// Retrieve cash on hand
	$sql = "SELECT accid FROM core.accounts WHERE topacc='7200' AND accnum='000'";
	$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
	$coh_acc = pg_fetch_result($acc_rslt, 0);

	$sql = "SELECT * FROM cubit.customers WHERE cusnum='$inv[cusnum]'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
	$cust_data = pg_fetch_array($cust_rslt);

	// Retrieve company details
	$sql = "SELECT * FROM cubit.compinfo WHERE compname='".COMP_NAME."'";
	$comp_rslt = db_exec($sql) or errDie("Unable to retrieve company details.");
	$comp_data = pg_fetch_array($comp_rslt);

	// Start transactions -----------------------------------------------------
	pglib_transaction("BEGIN");

	$sql = "INSERT INTO hire.cash (invid, cash)
			VALUES ('$invid', '$inv[deposit_amt]')";
	db_exec($sql) or errDie("Unable to add cash to hire.");

	$refnum = getrefnum();
	writetrans($coh_acc, $cust_acc, $inv["odate"], $refnum, $inv["deposit_amt"],
		"Cash Receipt for ".CUR."$inv[deposit_amt] from $cust_data[cusname] ".
		"$cust_data[surname] for Deposit on Hire Note H".getHirenum($inv["invid"], 1));

	// Make ledger record
	custledger($inv["cusnum"], $cust_acc, $inv["odate"], $inv["invid"],
		"Cash Receipt for ".CUR."$inv[deposit_amt] from $cust_data[cusname] ".
		"$cust_data[surname] for Deposit on Hire Note H".getHirenum($inv["invid"], 1),
		$inv["deposit_amt"], "c");

	custCT($inv["deposit_amt"], $inv["cusnum"], $inv["odate"]);

	// Turn the amount around to a negative
	$stmnt_amt = $inv["deposit_amt"] - ($inv["deposit_amt"] * 2);

	// Record the payment on the statement
	$sql = "INSERT INTO cubit.stmnt(cusnum, invid, docref, amount, date, type,
				div)
			VALUES('$inv[cusnum]', '$inv[invid]', '$inv[invnum]',
				'$stmnt_amt', '$inv[odate]',
				'Cash Receipt for ".CUR."$inv[deposit_amt] from $cust_data[cusname] ".
				"$cust_data[surname] for Deposit on Hire Note H".getHirenum($inv["invid"], 1)."',
				'".USER_DIV."')";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record");

	// Record the payment on the statement
	$sql = "INSERT INTO cubit.open_stmnt(cusnum, invid, docref, amount, balance,
				date, type, div)
			VALUES ('$inv[cusnum]', '$inv[invid]', '$inv[invnum]',
				'$stmnt_amt', '$stmnt_amt', '$inv[odate]',
				'Cash Receipt for ".CUR."$inv[deposit_amt] from $cust_data[cusname] ".
				"$cust_data[surname] for Deposit on Hire Note H".getHirenum($inv["invid"], 1)."',
				'".USER_DIV."')";
	$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record.");

	$sql = "UPDATE cubit.customers SET balance=balance-'$inv[deposit_amt]'
			WHERE cusnum='$inv[cusnum]'";
	db_exec($sql) or errDie("Unable to update customer balance.");

	$sql = "UPDATE hire.hire_invoices SET deposit_amt='0' WHERE invid='$inv[invid]'";
	db_exec($sql) or errDie("Unable to retrieve hire invoices.");

	pglib_transaction("COMMIT");
	// End transactions -------------------------------------------------------

	$OUTPUT = "<table ".TMPL_tblDflts." style='border: 1px solid #000'>
		<tr>
			<td align='center'>
				<b>CASH RECEIPT</b>
			</td>
		</tr>
		<tr>
			<td align='center'><b>$comp_data[compname]</b></td>
		</tr>
		<tr>
			<td align='center'>$comp_data[addr1]</td>
		</tr>
		<tr>
			<td align='center'>$comp_data[addr2]</td>
		</tr>
		<tr>
			<td align='center'>$comp_data[addr3]</td>
		</tr>
		<tr>
			<td align='center'>$comp_data[addr4]</td>
		</tr>
		<tr>
			<td align='center'>Tel: $comp_data[tel]</td>
		</tr>
		<tr>
			<td style='border-top: 1px solid #000'>Hire No: H".getHirenum($inv["invid"], 1)."</td>
		</tr>
		<tr>
			<td>Order No.$inv[ordno]</td>
		</tr>
		<tr>
			<td>Hire Date. $inv[odate]</td>
		</tr>
		<tr>
			<td style='border-top: 1px solid #000'
				>Cash Amount Received<br /> From $cust_data[cusname] $cust_data[surname]: ".CUR."$inv[deposit_amt]</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>By: $inv[username]</td>
		</tr>
		<tr>
			<td><br /><br /></td>
		</tr>
	</table>";

	require ("../tmpl-print.php");
}

function reprint()
{
	extract ($_REQUEST);

	// Retrieve the hire note
	$sql = "SELECT * FROM hire.hire_invoices WHERE invid='$invid'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve hire note.");
	$inv = pg_fetch_array($inv_rslt);

	$invnum = $inv["invnum"];

	$OUTPUT = "<center>
	<h2>Tax Invoice</h2>
	<table cellpadding='0' cellspacing='1' border=0 width=750>
	<tr><td valign=top width=40%>
		<table ".TMPL_tblDflts." border=0>
			<tr><td>$inv[surname]</td></tr>
		</table>
	</td><td valign=top width=35%>
		".COMP_NAME."<br>
		".COMP_ADDRESS."<br>
		".COMP_TEL."<br>
		".COMP_FAX."<br>
		Reg No. ".COMP_REGNO."<br>
	</td><td valign=bottom align=right width=25%>
		<table cellpadding='2' cellspacing='0' border=1 bordercolor='#000000'>
			<tr><td><b>Hire No.</b></td><td valign=center>H".getHirenum($inv["invid"], 1)."</td></tr>
			<tr><td><b>Order No.</b></td><td valign=center>$inv[ordno]</td></tr>
			<tr><td><b>Terms</b></td><td valign=center>Cash</td></tr>
			<tr><td><b>Invoice Date</b></td><td valign=center>$inv[odate]</td></tr>
			<tr><td><b>VAT</b></td><td valign=center>$inv[chrgvat]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=3>
	<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
		<tr><th>ITEM NUMBER</th><th width=45%>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>AMOUNT</th><tr>
		$products
	</table>
	</td></tr>
	<tr><td>
		$inv[custom_txt]
		$Com
	</td><td align=right colspan=2>
		<table cellpadding='5' cellspacing='0' border=1 width=50% bordercolor='#000000'>
			<tr><td><b>SUBTOTAL</b></td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr><td><b>Trade Discount</b></td><td align=right>".CUR." $inv[discount]</td></tr>
			<tr><td><b>Delivery Charge</b></td><td align=right>".CUR." $inv[delivery]</td></tr>
			<tr><td><b>VAT @ $VATP%</b></td><td align=right>".CUR." $VAT</td></tr>
			<tr><th><b>GRAND TOTAL<b></th><td align=right>".CUR." $TOTAL</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=1>
			<tr><td colspan=2>VAT Exempt indicator = #</td></tr>
			<tr><th>VAT No.</th><td align=center>".COMP_VATNO."</td></tr>
        </table>
	</td><td><br></td></tr>
	</table></center>";

	require ("../tmpl-print.php");
}
?>
