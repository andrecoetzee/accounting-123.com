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



	# Get invoice info
	db_connect();
	$sql = "SELECT * FROM pinvoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# CHECK IF THIS DATE IS IN THE BLOCKED RANGE
	$blocked_date_from = getCSetting("BLOCKED_FROM");
	$blocked_date_to = getCSetting ("BLOCKED_TO");

	if (strtotime($inv['odate']) >= strtotime($blocked_date_from) AND strtotime($inv['odate']) <= strtotime($blocked_date_to) AND !user_is_admin(USER_ID)){
		return "<li class='err'>Period Range Is Blocked. Only an administrator can process entries within this period.</li>";
	}

	if($inv['rounding'] > 0) {
		db_conn('core');
		$Sl = "SELECT * FROM salesacc WHERE name='rounding'";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			return "Please set the rounding account, under sales settings.";
		}

		$ad = pg_fetch_array($Ri);

		$rac = $ad['accnum'];

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

	$td = $inv['odate'];

	# check if invoice has been printed
	if($inv['printed'] == "y"){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has already been printed.";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	db_conn('cubit');

	$sql = "SELECT stkid FROM pinv_items WHERE invid = '$inv[invid]' AND div = '".USER_DIV."'";
	$crslt = db_exec($sql);
	if(pg_numrows($crslt) < 1){
		$error = "<li class='err'> Error : Invoice number <b>$invid</b> has no items.</li>";
		$error .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

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

		return "<li class='err'>The total of all the payments is not equal to the invoice total.<br>
		Please edit the invoice and try again(You can only overpay with cash)</li>";

	}

	db_connect();

# Begin updates
 #

		//lock(2);

		$invnum = divlastid('inv', USER_DIV);

		$Sl="INSERT INTO ncsrec (oldnum,newnum, div) VALUES ('$invid','$invnum', '".USER_DIV."')";
		$Rs= db_exec ($Sl) or errDie ("Unable to insert into db");

		//unlock(2);

		pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# get department
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql);
		if(pg_numrows($deptRslt) < 1){
			$dept['deptname'] = "<i class=err>Not Found</i>";
		}else{
			$dept = pg_fetch_array($deptRslt);
		}

		/* --- Start Products Display --- */

		# Products layout
		$products = "";
		$disc = 0;
		# get selected stock in this invoice
		db_connect();
		$sql = "SELECT * FROM pinv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$taxex = 0;

		$commision=0;
		$salesp = qrySalesPersonN($inv["salespn"]);
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
				$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
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
				$products .= "
				<tr valign='top'>
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

				$sql = "
					INSERT INTO stockrec (
						edate, stkid, stkcod, stkdes, trantype, qty, csprice, 
						csamt, details, div
					) VALUES (
						'$td', '$stkd[stkid]', '$stk[stkcod]', '$stk[stkdes]', 'invoice', '$stkd[qty]', '$stkd[amt]', 
						'$csprice', 'Stock sold - Invoice No. $invnum', '".USER_DIV."'
					)";
				$recRslt = db_exec($sql);
				
				if ($salesp["com"] > 0) {
					$itemcommission = $salesp['com'];
				} else {
					$itemcommission = $stk["com"];
				}

				$commision = $commision + coms($inv['salespn'],$amtexvat,$itemcommission);
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
				
				$VATP = TAX_VAT;
				$amtexvat = sprint($stkd['amt']);
				if($inv['chrgvat'] == "inc"){
					$amtexvat = sprint(($stkd['amt'] * 100)/(100 + $VATP));
				}
				
				if ($salesp["com"] > 0) {
					$itemcommission = $salesp['com'];
				} else {
					$itemcommission = 0;
				}

				$commision = $commision + coms($inv['salespn'],$amtexvat,$itemcommission);

				# Put in product
				$products.= "
				<tr valign='top'>
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

		com_invoice($inv['salespn'],($TOTAL-$VAT),$commision,$invnum, $td,true);

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
			$sql = "
				INSERT INTO stmnt 
					(cusnum, invid, docref, amount, date, type, div, allocation_date) 
				VALUES 
					('$inv[cusnum]', '$invnum', '0', '$nt', '$inv[odate]', 'Invoice', '".USER_DIV."', '$inv[odate]')";
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

			$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','".USER_NAME."','$invnum','$nt','Credit','".PRD_DB."','0')";
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

		if (!isset ($inv['cusname']) OR strlen ($inv['cusname']) < 1) 
			$custname = $inv['surname'];
		else 
			$custname = $inv['cusname'];

		$Sl="INSERT INTO pr(userid,username,amount,pdate,inv,cust,t) VALUES ('".USER_ID."','".USER_NAME."','$TOTAL','$td','$invnum','$custname','$inv[terms]')";
		$Ry=db_exec($Sl) or errDie("Unable to insert pos record.");

		$refnum = getrefnum();
/*refnum*/

		$fcash=$inv['pcash'];
		$fccp=$inv['pcc'];
		$fcheque=$inv['pcheque'];
		$fcredit=$inv['pcredit'];

		/* --- Updates ---- */
		db_connect();

		$Sql = "UPDATE pinvoices SET pchange='$change',printed ='y', done ='y',invnum='$invnum' WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$upRslt = db_exec($Sql) or errDie ("Unable to update invoice information");

		# save invoice discount
		$sql = "INSERT INTO inv_discs(cusnum, invid, traddisc, itemdisc, inv_date, delchrg, div,total) VALUES('0','$invnum','$inv[delivery]','$disc', '$inv[odate]', '$inv[delivery]', '".USER_DIV."',($SUBTOT+$inv[delivery]))";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# get selected stock in this invoice
		$sql = "SELECT * FROM pinv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
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
					if($stk['units']<0) {
						$stk['units']=0;
					}

					$cosamt = round(($stk['units'] * $stk['csprice']), 2);
				} else {
					$cosamt = round(($stkd['qty'] * $stk['csprice']), 2);
				}
				# cost amount
				//$cosamt = round(($stkd['qty'] * $stk['csprice']), 2);

				if($stk['csprice']>0) {
					$Sl="INSERT INTO scr(inv,stkid,amount) VALUES ('$invnum','$stkd[stkid]',' $stk[csprice]')";
					$Rg=db_exec($Sl);
				}

				# update stock(alloc - qty)
				$sql = "UPDATE stock SET csamt = (csamt - '$cosamt'),units = (units - '$stkd[qty]'),alloc = (alloc - '$stkd[qty]')  WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);

				$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
				$Ri=db_exec($Sl);

				if(pg_num_rows($Ri)<1) {
					return "Please select the vat code for all your stock.";
				}

				$VATP = TAX_VAT;
				$amtexvat = sprint($stkd['amt']);

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

				if($stk['serd'] == 'yes')
					ext_invSer($stkd['serno'], $stkd['stkid'], "POS Cash", $invnum);

				# stkid, stkcod, stkdes, trantype, edate, qty, csamt, details
				$sdate = date("Y-m-d");
				stockrec($stk['stkid'], $stk['stkcod'], $stk['stkdes'], 'ct', $td, $stkd['qty'], $cosamt, "POS Sales - Invoice No. $invnum");

				# get accounts
				db_conn("exten");

				$sql = "SELECT stkacc,cosacc FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
				$whRslt = db_exec($sql);
				$wh = pg_fetch_array($whRslt);
				$stockacc = $wh['stkacc'];
				$cosacc = $wh['cosacc'];

				# dt(cos) ct(stock)
				writetrans($cosacc, $stockacc, $td, $refnum, $cosamt, "Cost Of Sales POS Cash on POS Invoice No.$invnum.");
				$tcosamt += $cosamt;

				db_connect();
				$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
				VALUES('$inv[odate]', '$invid', '$invnum', '$dept[debtacc]', '$ivat', '$iamount', 'stk', '".USER_DIV."')";
				$recRslt = db_exec($sql);

			}else {
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
				$av-=$ivat;
				$at-=$iamount;



				vatr($vd['id'],$inv['odate'],"OUTPUT",$vd['code'],$refnum,"VAT for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]",$iamount,$ivat);

				db_connect();
				$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
				VALUES('$inv[odate]', '$invid', '$invnum', '$dept[debtacc]', '$ivat', '$iamount', 'non', '".USER_DIV."')";
				$recRslt = db_exec($sql);

				####################################################

				$amtexvat = sprint($stkd['amt']);
				db_connect();
				$sdate = date("Y-m-d");

				$nsp+=sprint($iamount-$ivat);

// 				//writetrans($cosacc, $stockacc,$inv['odate'] , $refnum, $cosamt, "Cost Of Sales for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");
// 				writetrans($dept['debtacc'], $stkd['account'],$inv['odate'], $refnum, ($iamount-$ivat), "Debtors Control for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]");
				if($inv['pcash']>0) {

					$min=$ro;

					$inv['pcash']+=$ro;
					$ro=0;

					//$amount=$inv['pcash'];

					if($inv['pcash']>=$ivat) {
						writetrans($dept['pca'], $vatacc, $td, $refnum, $ivat, "VAT Received for POS Invoice No.$invnum.");
						$inv['pcash']=sprint($inv['pcash']-$ivat);
						$ivat=0;

						if($inv['pcash']>0) {
							if($inv['pcash']>=$iamount) {
								writetrans($dept['pca'],$stkd['account'] , $td, $refnum, $iamount, "Sales for POS Invoice No.$invnum.");
								$inv['pcash']=sprint($inv['pcash']-$iamount);
								$iamount=0;
							} elseif($inv['pcash']<$iamount) {
								writetrans($dept['pca'],$stkd['account'] , $td, $refnum,$inv['pcash'] , "Sales for POS Invoice No.$invnum.");
								$iamount=sprint($iamount-$inv['pcash']);
								$inv['pcash']=0;
							}
						}

					} else {
						writetrans($dept['pca'], $vatacc, $td, $refnum, $inv['pcash'], "VAT Received for POS Invoice No.$invnum.");
						$ivat=sprint($ivat-$inv['pcash']);
						$inv['pcash']=0;
					}

// 					db_conn('cubit');
//
// 					$inv['pcash']-=$min;
//
// 					$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$sd','".USER_NAME."','$invnum','$inv[pcash]','Cash','".PRD_DB."','0')";
// 					$Ri=db_exec($Sl) or errDie("Unable to insert data.");
				}
				if($inv['pcheque']>0) {

					$min=$ro;
					$inv['pcheque']+=$ro;
					$ro=0;
					//$amount=$inv['pcash'];
					if($inv['pcheque']>=$ivat) {
						writetrans($dept['pca'], $vatacc, $td, $refnum, $ivat, "VAT Received for POS Invoice No.$invnum.");
						$inv['pcheque']=sprint($inv['pcheque']-$ivat);
						$ivat=0;
						if($inv['pcheque']>0) {
							if($inv['pcheque']>=$iamount) {
								writetrans($dept['pca'],$stkd['account'] , $td, $refnum, $iamount, "Sales for POS Invoice No.$invnum.");
								$inv['pcheque']=sprint($inv['pcheque']-$iamount);
								$iamount=0;
							} elseif($inv['pcheque']<$iamount) {
								writetrans($dept['pca'],$stkd['account'] , $td, $refnum,$inv['pcheque'] , "Sales for POS Invoice No.$invnum.");
								$iamount=sprint($iamount-$inv['pcheque']);
								$inv['pcheque']=0;
							}
						}

					} else {
						writetrans($dept['pca'], $vatacc, $td, $refnum, $inv['pcheque'], "VAT Received for POS Invoice No.$invnum.");
						$ivat=sprint($ivat-$inv['pcheque']);
						$inv['pcheque']=0;
					}

// 					db_conn('cubit');
//
// 					$inv['pcash']-=$min;
//
// 					$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$sd','".USER_NAME."','$invnum','$inv[pcash]','Cash','".PRD_DB."','0')";
// 					$Ri=db_exec($Sl) or errDie("Unable to insert data.");
				}

				if($inv['pcc']>0) {

					db_conn('core');

					$Sl="SELECT * FROM salacc WHERE name='cc'";
					$Ri=db_exec($Sl);

					if(pg_num_rows($Ri)<1) {
						return "Please set a link for the POS credit card control account";
					}

					$cd=pg_fetch_array($Ri);

					$cc=$cd['accnum'];

					$min=$ro;

					$inv['pcc']+=$ro;
					$ro=0;

					//$amount=$inv['pcash'];

					if($inv['pcc']>=$ivat) {
						writetrans($cc, $vatacc, $td, $refnum, $ivat, "VAT Received for POS Invoice No.$invnum.");
						$inv['pcc']=sprint($inv['pcc']-$ivat);
						$ivat=0;
						if($inv['pcc']>0) {
							if($inv['pcc']>=$iamount) {
								writetrans($cc,$stkd['account'] , $td, $refnum, $iamount, "Sales for POS Invoice No.$invnum.");
								$inv['pcc']=sprint($inv['pcc']-$iamount);
								$iamount=0;
							} elseif($inv['pcc']<$iamount) {
								writetrans($cc,$stkd['account'] , $td, $refnum,$inv['pcc'] , "Sales for POS Invoice No.$invnum.");
								$iamount=sprint($iamount-$inv['pcc']);
								$inv['pcc']=0;
							}
						}
					} else {
						writetrans($cc, $vatacc, $td, $refnum, $inv['pcc'], "VAT Received for POS Invoice No.$invnum.");
						$ivat=sprint($ivat-$inv['pcc']);
						$inv['pcc']=0;
					}

// 					db_conn('cubit');
//
// 					$inv['pcash']-=$min;
//
// 					$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$sd','".USER_NAME."','$invnum','$inv[pcash]','Cash','".PRD_DB."','0')";
// 					$Ri=db_exec($Sl) or errDie("Unable to insert data.");
				}


				if($inv['pcredit']>0) {
					db_conn('core');
					$min=$ro;
					$inv['pcredit']+=$ro;
					$ro=0;
					//$amount=$inv['pcash'];
					if($inv['pcredit']>=$ivat) {
						writetrans($dept['debtacc'], $vatacc, $td, $refnum, $ivat, "VAT Received for POS Invoice No.$invnum.");
						$inv['pcredit']=sprint($inv['pcredit']-$ivat);
						$ivat=0;
						if($inv['pcredit']>0) {
							if($inv['pcredit']>=$iamount) {
								writetrans($dept['debtacc'],$stkd['account'] , $td, $refnum, $iamount, "Sales for POS Invoice No.$invnum.");
								$inv['pcredit']=sprint($inv['pcredit']-$iamount);
								$iamount=0;
							} elseif($inv['pcredit']<$iamount) {
								writetrans($dept['debtacc'],$stkd['account'] , $td, $refnum,$inv['pcredit'] , "Sales for POS Invoice No.$invnum.");
								$iamount=sprint($iamount-$inv['pcredit']);
								$inv['pcredit']=0;
							}
						}
					} else {
						writetrans($dept['debtacc'], $vatacc, $td, $refnum, $inv['pcredit'], "VAT Received for POS Invoice No.$invnum.");
						$ivat=sprint($ivat-$inv['pcredit']);
						$inv['pcredit']=0;
					}

// 					db_conn('cubit');
//
// 					$inv['pcash']-=$min;
//
// 					$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$sd','".USER_NAME."','$invnum','$inv[pcash]','Cash','".PRD_DB."','0')";
// 					$Ri=db_exec($Sl) or errDie("Unable to insert data.");
				}

			}
		}

	/* - Start Transactoins - */
	###################VAT CALCS#######################

	db_conn('cubit');
	$Sl="SELECT * FROM vatcodes WHERE del='Yes'";
	$Ri=db_exec($Sl);

	if(pg_num_rows($Ri)<1) {
		$Sl="SELECT * FROM vatcodes";
		$Ri=db_exec($Sl);
	}

	$vd=pg_fetch_array($Ri);

	$excluding="";

	$vr=vatcalc($inv['delchrg'],$inv['chrgvat'],$excluding,$inv['traddisc'],$vd['vat_amount']);
	$vrs=explode("|",$vr);
	$ivat=$vrs[0];
	$iamount=$vrs[1];

	vatr($vd['id'],$inv['odate'],"OUTPUT",$vd['code'],$refnum,"VAT for Invoice No.$invnum for Customer : $inv[cusname] $inv[surname]",$iamount,$ivat);

	####################################################

	//print $inv['pcash'];exit;

	if($inv['pcash']>0) {
		$min=$ro;
		$inv['pcash']+=$ro;
		$ro=0;
		$amount=$inv['pcash'];
		if($amount>=$av) {
			writetrans($dept['pca'], $vatacc, $td, $refnum, $av, "VAT Received for POS Invoice No.$invnum.");
			// PROBLEM HERE?
			$amount=sprint($amount-$av);
			$av=0;
			if($amount>0) {
				writetrans($dept['pca'], $dept['pia'], $td, $refnum, $amount, "Sales for POS Invoice No.$invnum.");
				$at=$at-$amount;
			}
		} else {
			writetrans($dept['pca'], $vatacc, $td, $refnum, $amount, "VAT Received for POS Invoice No.$invnum.");
			$av=$av-$amount;
			$amount=0;
		}

		db_conn('cubit');
		$inv['pcash']-=$min;

		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','".USER_NAME."','$invnum','$fcash','Cash','".PRD_DB."','0')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");

		$fcash=0;
	}

	db_conn('cubit');

	if($fcash>0) {
		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','".USER_NAME."','$invnum','$fcash','Cash','".PRD_DB."','0')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");
	}

	if($inv['pcheque']>0) {
		$min=$ro;
		$inv['pcheque']+=$ro;
		$ro=0;
		$amount=$inv['pcheque'];
		if($amount>=$av) {
			writetrans($dept['pca'], $vatacc, $td, $refnum, $av, "VAT Received for POS Invoice No.$invnum.");
			$amount=sprint($amount-$av);
			$av=0;
			if($amount>0) {
				writetrans($dept['pca'], $dept['pia'], $td, $refnum, $amount, "Sales for POS Invoice No.$invnum.");
				$at=$at-$amount;
			}
		} else {
			writetrans($dept['pca'], $vatacc, $td, $refnum, $amount, "VAT Received for POS Invoice No.$invnum.");
			$av=$av-$amount;
			$amount=0;
		}

		db_conn('cubit');
		$inv['pcheque']-=$min;
		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','".USER_NAME."','$invnum','$fcheque','Cheque','".PRD_DB."','0')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");
		$fcheque=0;
	}

	db_conn('cubit');

	if($fcheque>0) {
		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','".USER_NAME."','$invnum','$fcheque','Cheque','".PRD_DB."','0')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");
	}

	if($inv['pcc']>0) {
		db_conn('core');
		$Sl="SELECT * FROM salacc WHERE name='cc'";
		$Ri=db_exec($Sl);
		if(pg_num_rows($Ri)<1) {
			return "Please set a link for the POS credit card control account";
		}

		$cd=pg_fetch_array($Ri);
		$cc=$cd['accnum'];
 		$min=$ro;
		$inv['pcc']+=$ro;
		$ro=0;
		$amount=$inv['pcc'];
		if($amount>=$av) {
			writetrans($cc, $vatacc, $td, $refnum, $av, "VAT Received for POS Invoice No.$invnum.");
			$amount=sprint($amount-$av);
			$av=0;
			if($amount>0) {
				writetrans($cc, $dept['pia'], $td, $refnum, $amount, "Sales for POS Invoice No.$invnum.");
				$at=$at-$amount;
			}
		} else {
			writetrans($cc, $vatacc, $td, $refnum, $amount, "VAT Received for POS Invoice No.$invnum.");
			$av=$av-$amount;
			$amount=0;
		}

		db_conn('cubit');

		$inv['pcc']-=$min;

		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','".USER_NAME."','$invnum','$fccp','Credit Card','".PRD_DB."','0')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");

		$fccp=0;
	}

	db_conn('cubit');

	if($fccp>0) {
		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','".USER_NAME."','$invnum','$fccp','Credit Card','".PRD_DB."','0')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");
	}

	if($inv['pcredit']>0) {

		db_conn('core');

		$min=$ro;
		$inv['pcredit']+=$ro;
		$ro=0;
		$amount=$inv['pcredit'];

		if($amount>=$av) {
			writetrans($dept['debtacc'], $vatacc, $td, $refnum, $av, "VAT Received for POS Invoice No.$invnum.");
			$amount=sprint($amount-$av);
			$av=0;
			if($amount>0) {
				writetrans($dept['debtacc'], $dept['pia'], $td, $refnum, $amount, "Sales for POS Invoice No.$invnum.");
				$at=$at-$amount;
			}
		} else {
			writetrans($dept['debtacc'], $vatacc, $td, $refnum, $amount, "VAT Received for POS Invoice No.$invnum.");
			$av=$av-$amount;
			$amount=0;
		}

		db_conn('cubit');

		$inv['pcc']-=$min;
		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','".USER_NAME."','$invnum','$fcredit','Credit','".PRD_DB."','0')";
		//$Ri=db_exec($Sl) or errDie("Unable to insert data.");

		$fcredit=0;
	}

	db_conn('cubit');

	if($fcredit>0) {
		$Sl="INSERT INTO payrec(date,by,inv,amount,method,prd,note) VALUES ('$td','".USER_NAME."','$invnum','$fcredit','Credit','".PRD_DB."','0')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");
	}

	if($inv['rounding']>0) {
		if($inv['pcash']>0) {
			writetrans($rac,$dept['pca'], $td, $refnum, $inv['rounding'], "Rounding  on Invoice No.$invnum.");
		} elseif($inv['pcheque']>0) {
			writetrans($rac,$dept['pca'], $td, $refnum, $inv['rounding'], "Rounding on Invoice No.$invnum.");
		} elseif($inv['pcc']>0) {
			writetrans($rac,$cc, $td, $refnum, $inv['rounding'], "Rounding on Invoice No.$invnum.");
		}elseif($inv['pcredit']>0) {
			writetrans($rac,$dept['debtacc'], $td, $refnum, $inv['rounding'], "Rounding on Invoice No.$invnum.");
		}
	}


// 	if($inv['terms']==1) {
// 		$dept['pca']=$cc;
// 	}
//
// 	# dt(debtors) ct(income/sales)
// 	writetrans($dept['pca'], $dept['pia'], $td, $refnum, ($TOTAL-$VAT), "Sales for POS Invoice No.$invnum.");
//
// 	# dt(debtors) ct(vat account)
// 	writetrans($dept['pca'], $vatacc, $td, $refnum, $VAT, "VAT Received for POS Invoice No.$invnum.");

//	db_connect();
//	$sql = "INSERT INTO salesrec(edate, invid, invnum, debtacc, vat, total, typ, div)
//	VALUES('$inv[odate]', '$invid', '$invnum', '$dept[debtacc]', '$VAT', '$TOTAL', 'stk', '".USER_DIV."')";
//	$recRslt = db_exec($sql);

	db_conn('cubit');

	if($inv['cusnum']>0) {
		db_conn('cubit');
		$Sl="SELECT * FROM customers WHERE cusnum='$inv[cusnum]'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		$cd=pg_fetch_array($Ri);

		$inv['cusname']=$cd['surname'];

	}

	$Sl="INSERT INTO sj(cid,name,des,date,exl,vat,inc,div) VALUES
	('$inv[cusnum]','$inv[cusname]','POS Invoice $invnum','$inv[odate]','".sprint($TOTAL-$VAT)."','$VAT','".sprint($TOTAL)."','".USER_DIV."')";
	$Ri=db_exec($Sl);


	if($change>0) {
		$Sl="INSERT INTO pc(date,by,inv,amount) VALUES ('$sd','".USER_NAME."','$invnum','$change')";
		$Ri=db_exec($Sl) or errDie("Unable to insert data.");
	}

	db_conn('cubit');

	if($inv['rounding']>0) {
		$Sl="INSERT INTO varrec(inv,date,amount) VALUES('$invnum','".date("Y-m-d")."','$inv[rounding]')";
		$Ri=db_exec($Sl);
	}


	# Commit updates
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if(strlen($inv['comm'])>0){
		$Com="<table><tr><td>".nl2br($inv['comm'])."</td></tr></table>";
	} else {$Com="";}

	$cc = "<script> sCostCenter('dt', 'Sales', '$inv[odate]', 'POS Invoice No.$invnum', '".($TOTAL-$VAT)."', 'Cost Of Sales for Invoice No.$invnum', '$tcosamt', ''); </script>";

	if($inv['chrgvat']=="inc") {
		$inv['chrgvat']="Inclusive";
	} elseif($inv['chrgvat']=="exc") {
		$inv['chrgvat']="Exclusive";
	} else {
		$inv['chrgvat']="No vat";
	}

	/* - End Transactoins - */

	/* -- Final Layout -- */
	$details = "
					<center>
					$cc
					<h2>Tax Invoice</h2>
					<table cellpadding='0' cellspacing='1' border=0 width=750>
						<tr>
							<td valign='top' width='40%'>
								<table ".TMPL_tblDflts.">
									<tr><td>$inv[surname]</td></tr>
								</table>
							</td>
							<td valign='top' width='35%'>
								".COMP_NAME."<br>
								".COMP_ADDRESS."<br>
								".COMP_TEL."<br>
								".COMP_FAX."<br>
								Reg No. ".COMP_REGNO."<br>
							</td>
							<td valign='bottom' align='right' width='25%'>
								<table cellpadding='2' cellspacing='0' border='1' bordercolor='#000000'>
									<tr>
										<td><b>Invoice No.</b></td>
										<td valign='center'>$invnum</td>
									</tr>
									<tr>
										<td><b>Order No.</b></td>
										<td valign='center'>$inv[ordno]</td>
									</tr>
									<tr>
										<td><b>Terms</b></td>
										<td valign='center'>Cash</td>
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
							<td colspan='3'>
								<table cellpadding='5' cellspacing='0' border='1' width=100% bordercolor='#000000'>
									<tr>
										<th>ITEM NUMBER</th>
										<th width='45%'>DESCRIPTION</th>
										<th>QTY</th>
										<th>UNIT PRICE</th>
										<th>AMOUNT</th>
									<tr>
									$products
								</table>
							</td>
						</tr>
						<tr>
							<td>$Com</td>
							<td align='right' colspan='2'>
								<table cellpadding='5' cellspacing='0' border='1' width='50%' bordercolor='#000000'>
									<tr>
										<td><b>SUBTOTAL</b></td>
										<td align='right'>".CUR." $SUBTOT</td>
									</tr>
									<tr>
										<td><b>Trade Discount</b></td>
										<td align='right'>".CUR." $inv[discount]</td>
									</tr>
									<tr>
										<td><b>Delivery Charge</b></td>
										<td align='right'>".CUR." $inv[delivery]</td>
									</tr>
									<tr>
										<td><b>VAT @ $VATP%</b></td>
										<td align='right'>".CUR." $VAT</td>
									</tr>
									<tr>
										<th><b>GRAND TOTAL<b></th>
										<td align='right'>".CUR." $TOTAL</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td>
								<table ".TMPL_tblDflts." border='1'>
									<tr>
										<td colspan='2'>VAT Exempt indicator = #</td>
									</tr>
									<tr>
										<th>VAT No.</th>
										<td align='center'>".COMP_VATNO."</td>
									</tr>
						        </table>
							</td>
							<td><br></td>
						</tr>
					</table>
					</center>";


	/* Start moving invoices */

	db_connect();
	# Move invoices that are fully paid
	$sql = "SELECT * FROM pinvoices WHERE printed = 'y' AND done = 'y' AND div = '".USER_DIV."'";
	$invbRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

	while($invb = pg_fetch_array($invbRslt))
	{
		db_conn($invb['prd']);

		$invb['invnum'] += 0;
		# Insert invoice to period DB
		$sql = "INSERT INTO pinvoices(invid,invnum, deptid, cusnum, deptname, cusacc, cusname, telno,
					surname, cusaddr, cusvatno, cordno, ordno, chrgvat, terms, traddisc, salespn,
					odate, delchrg, subtot, vat, total, balance, comm, printed, done, div, username,
					rounding,delvat,vatnum,pcash,pcheque,pcc,pcredit, pslip_sordid)
				VALUES('$invb[invid]','$invb[invnum]', '$invb[deptid]', '$invb[cusnum]',
					'$invb[deptname]', '$invb[cusacc]', '$invb[cusname]', '$invb[telno]', '$invb[surname]',
					'$invb[cusaddr]', '$invb[cusvatno]', '$invb[cordno]', '$invb[ordno]',
					'$invb[chrgvat]', '$invb[terms]', '$invb[traddisc]', '$invb[salespn]',
					'$invb[odate]', '$invb[delchrg]', '$invb[subtot]', '$invb[vat]' ,
					'$invb[total]', '$invb[balance]', '$invb[comm]', 'y', 'y', '".USER_DIV."',
					'".USER_NAME."','$invb[rounding]','$invb[delvat]','$invb[vatnum]',
					'$invb[pcash]','$invb[pcheque]','$invb[pcc]','$invb[pcredit]', '$invb[pslip_sordid]')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to the period database.",SELF);

		db_connect();
		$sql = "INSERT INTO movinv(invtype, invnum, prd, docref, div) VALUES('pos', '$invb[invnum]', '$invb[prd]', '', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Unable to insert invoice to the period database.",SELF);

		# get selected stock in this invoice
		db_connect();
		$sql = "SELECT * FROM pinv_items WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		while($stkd = pg_fetch_array($stkdRslt)){
			db_conn($invb['prd']);
			# insert invoice items
			$stkd['vatcode']+=0;
			$stkd['account']+=0;
			$sql = "INSERT INTO pinv_items(invid, whid, stkid, qty, unitcost, amt, disc, discp, serno, div,vatcode,account,description) VALUES('$invb[invid]', '$stkd[whid]', '$stkd[stkid]', '$stkd[qty]', '$stkd[unitcost]', '$stkd[amt]', '$stkd[disc]', '$stkd[discp]', '$stkd[serno]', '".USER_DIV."','$stkd[vatcode]','$stkd[account]','$stkd[description]')";
			$rslt = db_exec($sql) or errDie("Unable to insert invoice items to Cubit.",SELF);
		}

		db_connect();
		# Remove those invoices from running DB
		$sql = "DELETE FROM pinvoices WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);

		# Remove those invoice items from running DB
		$sql = "DELETE FROM pinv_items WHERE invid = '$invb[invid]' AND div = '".USER_DIV."'";
		$delRslt = db_exec($sql) or errDie("Unable to update Invoice information in Cubit.",SELF);
	}

	/* end moving invoices */

	/* OLD
	$OUTPUT = $details;
	require("tmpl-print.php");*/

	header("Location: pos-slip.php?invid=$inv[invid]&prd=$inv[prd]&cccc=yes");
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


?>
