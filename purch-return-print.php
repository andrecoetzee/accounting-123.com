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
if (isset($_GET["purid"])) {
	$OUTPUT = details($_GET);
} else {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
}

# get templete
require("template.php");



# details
function details($_GET)
{

	$showvat = TRUE;

	# get vars
	extract ($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");

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

	# Get purchase info
	db_connect();

	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";


	$sql = "
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, 'cubit' as table FROM cubit.purchases WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, '1' as table FROM \"1\".purchases WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, '2' as table FROM \"2\".purchases WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, '3' as table FROM \"3\".purchases WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, '4' as table FROM \"4\".purchases WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, '5' as table FROM \"5\".purchases WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, '6' as table FROM \"6\".purchases WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, '7' as table FROM \"7\".purchases WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, '8' as table FROM \"8\".purchases WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, '9' as table FROM \"9\".purchases WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, '10' as table FROM \"10\".purchases WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, '11' as table FROM \"11\".purchases WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT purid,deptid,supid,supaddr,terms,pdate,ddate,remarks,received,done,refno,vatinc,prd,ordernum,div,purnum,supname,supno,shipchrg,subtot,total,balance,vat,supinv,apprv,appname,appdate,rvat,rshipchrg,rsubtot,jobid,jobnum,toggle,cash,shipping,invcd,rshipping,noted,returned,delvat, '12' as table FROM \"12\".purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";


	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<i class='err'>Order Not Found</i>";
	}
	$pur = pg_fetch_array($purRslt);

	$get_codes = "SELECT * FROM suppstock WHERE suppid = '$pur[supid]' ORDER BY stkid";
	$run_codes = db_exec ($get_codes) or errDie ("Unable to get supplier stock code information");
	if (pg_numrows ($run_codes) > 0){
		while ($codarr = pg_fetch_array ($run_codes)){
			if (strlen ($codarr['stkcod']) > 0) 
				$stockcodes[$codarr['stkid']]['stkcod'] = $codarr['stkcod'];
			if (strlen ($codarr['stkdes']) > 0) 
				$stockcodes[$codarr['stkid']]['stkdes'] = $codarr['stkdes'];
		}
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "
		<table cellpadding='5' cellspacing='0' border='1' width='100%' bordercolor='#000000'>
			<tr>
				<td>STORE</td>
				<td>ITEM NUMBER</td>
				<td>DESCRIPTION</td>
				<td>QTY OUTSTANDING</td>
				<td>UNIT PRICE</td>
				<td>DISCOUNT</td>
				<td>DELIVERY DATE</th>
				<td>AMOUNT</td>
			<tr>";

	# get selected stock in this purchase
	db_connect();

	#get returned purch info
	$get_ret = "SELECT rpurid, subtot FROM \"$pur[table]\".purch_ret WHERE purid = '$pur[purid]' LIMIT 1";
	$run_ret = db_exec ($get_ret) or errDie ("Unable to get returned purchase information.");
	if (pg_numrows ($run_ret) > 0){
		$rpurid = pg_fetch_result ($run_ret,0,0);
		$rpur_subtot = pg_fetch_result ($run_ret,0,1);
	}else {
		$rpurid = 0;
		$rpur_subtot = 0;
	}

	$sql = "
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM cubit.pur_items WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM \"1\".pur_items WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM \"2\".pur_items WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM \"3\".pur_items WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM \"4\".pur_items WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM \"5\".pur_items WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM \"6\".pur_items WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM \"7\".pur_items WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM \"8\".pur_items WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM \"9\".pur_items WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM \"10\".pur_items WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM \"11\".pur_items WHERE purid = '$purid' AND div = '".USER_DIV."' UNION 
		SELECT id,purid,whid,stkid,qty,ddate,div,svat,rqty,tqty,unitcost,amt,vatcode,description,account,udiscount FROM \"12\".pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

	while($stkd = pg_fetch_array($stkdRslt)){

		$puritemid = $stkd['id'];

		# check if this line item was returned at all
		$get_ritem = "SELECT * FROM \"$pur[table]\".retpur_items WHERE itemid = '$stkd[id]' LIMIT 1";
		$run_ritem = db_exec ($get_ritem) or errDie ("Unable to get returned purchase information.");
		if (pg_numrows ($run_ritem) < 1){
			#item was never returned at all .... skip
			continue;
		}

		$rpur_item = pg_fetch_array ($run_ritem);

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

		if (isset ($stockcodes[$stk['stkid']]['stkcod']))
			$stk['stkcod'] = $stockcodes[$stk['stkid']]['stkcod'];
		if (isset ($stockcodes[$stk['stkid']]['stkdes']))
			$stk['stkdes'] = $stockcodes[$stk['stkid']]['stkdes'];

		# format date
		list($dyear, $dmon, $dday) = explode("-", $stkd['ddate']);

		db_conn('cubit');

		$Sl = "SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
		$Ri = db_exec($Sl);

		$vd = pg_fetch_array($Ri);
		$VATP = $vd['vat_amount'];

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		if($stkd['whid'] == "0"){
			$desc = $stkd['description'];
		}else {
			$desc = $stk['stkdes'];
		}

		if (strlen ($stkd['description2']) > 0){
			$desc .= "<br>$stkd[description2]";
		}

//		<td>".sprint3($stkd['qty'])."</td>
//		<td nowrap>".CUR." $stkd[amt]</td>

		# If vat is not included
		if($pur['vatinc'] == "no"){
			$vat[$puritemid] = sprintf("%01.2f", (($VATP/100) * ($rpur_item['qty']*$rpur_item['unitcost'])));
		}elseif($pur['vatinc'] == "yes"){
			$vat[$puritemid] = sprintf("%01.2f", ((($rpur_item['qty']*$rpur_item['unitcost'])/(100 + $VATP)) * $VATP));
		}else{
			$vat[$puritemid] = 0;
		}

		# put in product
		$products .= "
			<tr>
				<td>$wh[whname]</td>
				<td>$stk[stkcod]</td>
				<td>$desc</td>
				<td>".sprint3($rpur_item['qty'])."</td>
				<td nowrap>".CUR." ".sprint($stkd['unitcost'])."</td>
				<td>$stkd[udiscount]</td>
				<td>$dday-$dmon-$dyear</td>
				<td nowrap>".CUR." ".sprint ($rpur_item['qty']*$rpur_item['unitcost'])."</td>
			</tr>";
	}
	$products .= "</table>";

	if($pur['vatinc'] == "no"){
		$NEWTOTAL = $rpur_subtot;
	}elseif($pur['vatinc'] == "yes"){
		$NEWTOTAL = sprint ($pur['subtot'] + array_sum($vat));
	}

	/*
	# Get supplier
	db_connect();
	$sql = "SELECT supname,supno FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec($sql);
	if(pg_numrows($supRslt) < 1){
		$sup['supname'] = "<li class=err>Supplier not found";
		$sup['supno'] = "";
	}else{
		$sup = pg_fetch_array($supRslt);
	}
	*/

	# Get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class='err'>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}


	/* --- Start Some calculations --- */


	# Get subtotal
	$SUBTOT = sprint($pur['subtot']);

	# Get Total
	$TOTAL = sprint($pur['total']);

	# Get vat
	$VAT = sprint($pur['vat']);

	/* --- End Some calculations --- */

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}


/*
						<tr>
							<td>SUBTOTAL</td>
							<td align='right' nowrap>".CUR." $pur[subtot]</td>
						</tr>
						<tr>
							<td>Delivery Charges</td>
							<td align='right' nowrap>".CUR." $pur[shipchrg]</td>
						</tr>
						<tr>
							<td>VAT $vat14</td>
							<td align='right' nowrap>".CUR." $pur[vat]</td>
						</tr>
						<tr>
							<td>GRAND TOTAL</td>
							<td align='right' nowrap>".CUR." $pur[total]</td>
						</tr>
*/

	/* -- Final Layout -- */
	$details = "
		<center>
		<h3>Stock Return</h3>
		<table cellpadding='0' cellspacing='4' border=0 width=750>
			<tr>
				<td valign='top'>
					<table cellpadding='2' cellspacing='0' border=1>
						<tr>
							<td colspan='2'> Supplier Details </td>
						</tr>
						<tr>
							<td>Department</td>
							<td valign='center'>$dept[deptname]</td>
						</tr>
						<tr>
							<td>Supplier</td>
							<td valign='center'>$pur[supname]</td>
						</tr>
						<tr>
							<td>Account number</td>
							<td valign='center'>$pur[supno]</td>
						</tr>
						<tr>
							<td valign='top'>Supplier Address</td>
							<td valign='center'>".nl2br($pur['supaddr'])."</td>
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
				<td valign='top' align='right'>
					<table cellpadding='2' cellspacing='0' border=1>
						<tr>
							<td colspan='2'> Order Details </td>
						</tr>
						<tr>
							<td>Purchase No.</td>
							<td valign='center'>$pur[purnum]</td>
						</tr>
						<tr>
							<td>Order No.</td>
							<td valign='center'>$pur[ordernum]&nbsp;</td>
						</tr>
						<tr>
							<td>Terms</td>
							<td valign='center'>$pur[terms] Days</td>
						</tr>
						<tr>
							<td>Date</td>
							<td valign='center'>$pday-$pmon-$pyear</td>
						</tr>
						<tr>
							<td>Delivery Charges</td>
							<td valign='center' nowrap>".CUR." $pur[shipchrg]</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='3'>$products</td>
			</tr>
				</td>
				<td colspan='2'>
					<table cellpadding='2' cellspacing='0' border='1'>
						<tr>
							<td>$pur[remarks]</td>
						</tr>
					</table>
				</td>
				<td align='right'>
					<table cellpadding='2' cellspacing='0' border=1 width=60%>
						<tr>
							<td>SUBTOTAL</td>
							<td align='right' nowrap>".CUR." $rpur_subtot</td>
						</tr>
						<tr>
							<td>Delivery Charges</td>
							<td align='right' nowrap>".CUR." $pur[shipchrg]</td>
						</tr>
						<tr>
							<td>VAT $vat14</td>
							<td align='right' nowrap>".CUR." ".sprint(array_sum($vat))."</td>
						</tr>
						<tr>
							<td>GRAND TOTAL</td>
							<td align='right' nowrap>".CUR." $NEWTOTAL</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>
		</center>";
	$OUTPUT = $details;

	require("tmpl-print.php");

}


?>