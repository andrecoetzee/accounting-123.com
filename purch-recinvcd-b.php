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

# Get settings
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");

# Decide what to do
if (isset($_GET["purid"])) {
	$OUTPUT = details($_GET);
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
            case "update":
				$OUTPUT = write($_POST);
				break;

            default:
				$OUTPUT = "<li class=err> Invalid use of module.";
			}
	} else {
		$OUTPUT = "<li class=err> Invalid use of module.";
	}
}

# Get templete
require("template.php");

# Details
function details($_POST, $error="")
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Purchase number.");

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class=err>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been received
	if($pur['invcd'] == "y"){
		$error = "<li class=err> Error : purchase number <b>$pur[purnum]</b> has already been invoiced.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class=err>Department not Found.";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($supRslt) < 1) {
		$sup['supname'] = "<li class=err> Supplier not Found.";
		$sup['supaddr'] = "<br><br><br>";
	}else{
		$sup = pg_fetch_array($supRslt);
		$supaddr = $sup['supaddr'];
	}

/* --- Start Drop Downs --- */

	# days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $pur['terms']);

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# select all products
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>STORE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>DELIVERY DATE</th><th>AMOUNT</th><tr>";

	# get selected stock in this purchase
	db_connect();
	$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
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

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		$i++;

		# put in product
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td>$wh[whname]</td><td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td><td>$stk[stkdes]</td><td>$stkd[qty]</td><td>$stkd[unitcost]</td><td>$sday-$smon-$syear</td><td>".CUR." $stkd[amt]</td></tr>";
		$key++;
	}
	# Look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* -- Final Layout -- */
	$details = "<center><h3>Record Purchase Invoice</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=update>
	<input type=hidden name=purid value='$purid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Supplier Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Department</td><td valign=center>$dept[deptname]</td></tr>
   			<tr bgcolor='".TMPL_tblDataColor2."'><td>Supplier</td><td valign=center>$sup[supname]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Account number</td><td valign=center>$sup[supno]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>Supplier Address</td><td valign=center>".nl2br($supaddr)."</td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Purchase Details </th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Purchase No.</td><td valign=center>$pur[purnum]</td></tr>
 			<tr bgcolor='".TMPL_tblDataColor2."'><td>Supp Inv No.</td><td valign=center><input type=text name=supinv size=10 value='$pur[supinv]'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Delivery Ref No.</td><td valign=center><input type=text name=refno size=10 value='$pur[refno]'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Terms</td><td valign=center>$pur[terms] Days</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td valign=center>$pday-$pmon-$pyear DD-MM-YYYY</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>VAT Inclusive</td><td valign=center>$pur[vatinc]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Delivery Charges</td><td valign=center>".CUR." $pur[shipchrg]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>$products</td></tr>
	<tr><td>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=25%>Quick Links</th><th width=25%>Remarks</th><td rowspan=5 valign=top width=50%>$error</td></tr>
			<tr><td bgcolor='".TMPL_tblDataColor1."'><a href='purchase-new.php'>New purchase</a></td><td bgcolor='".TMPL_tblDataColor1."' rowspan=4 align=center valign=top><textarea name=remarks rows=4 cols=20>$pur[remarks]</textarea></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='purchase-view.php'>View purchases</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>SUBTOTAL</td><td align=right>".CUR." $pur[subtot]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Charges</td><td align=right>".CUR." $pur[shipping]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT @ ".TAX_VAT." %</td><td align=right>".CUR." $pur[vat]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><th>GRAND TOTAL</th><td align=right>".CUR." $pur[total]</td></tr>
		</table>
	</td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'> | <input type=submit name='upBtn' value='Write'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# details
function write($_POST)
{

	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");
	$v->isOk ($refno, "string", 0, 255, "Invalid Delivery Reference No.");
	$v->isOk ($remarks, "string", 0, 255, "Invalid Remarks.");
	$v->isOk ($supinv, "string", 0, 255, "Invalid supp inv.");

	# used to generate errors
	$error = "asa@";

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return details($_POST, $err);
	}

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	$td=$pur['pdate'];

	# check if purchase has been received
	if($pur['invcd'] == "y"){
		$error = "<li class=err> Error : purchase number <b>$pur[purnum]</b> has already been invoiced.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($supRslt) < 1) {
		// code here
	}else{
		$sup = pg_fetch_array($supRslt);
	}

	# Get department info
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class=err> - Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get warehouse name
	db_conn("exten");
	$sql = "SELECT * FROM warehouses WHERE div = '".USER_DIV."'";
	$whRslt = db_exec($sql);
	$wh = pg_fetch_array($whRslt);

	//pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);
		# get selected stock in this purchase
		db_connect();
		$sql = "SELECT * FROM pur_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$Ri = db_exec($sql);

		$refnum = getrefnum();

		while($id=pg_fetch_array($Ri)) {
			db_connect();
			# get selamt from selected stock
			$sql = "SELECT * FROM stock WHERE stkid = '$id[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);


			$Sl="SELECT * FROM vatcodes WHERE id='$stk[vatcode]'";
			$Ri=db_exec($Sl);

			if(pg_num_rows($Ri)<1) {
				return "Please select the vatcode for all your stock.";
			}

			$vd=pg_fetch_array($Ri);

			if($id['svat']==0) {
				$exvat="y";
			} else {
				$exvat="";
			}

			$vr=pvatcalc($id['amt'],$pur['vatinc'],$exvat);
			$vrs=explode("|",$vr);
			$ivat=$vrs[0];
			$iamount=$vrs[1];

			vatr($vd['id'],$pur['pdate'],"INPUT",$vd['code'],$refnum,"Purchase $pur[purnum] Supplier : $pur[supname].",$iamount,$ivat);
		}


		/* - Start Hooks - */

		$vatacc = gethook("accnum", "salesacc", "name", "VAT");
		$cvacc = gethook("accnum", "pchsacc", "name", "Cost Variance");

		/* - End Hooks - */

		# Record the payment on the statement
		db_connect();
		$sdate = date("Y-m-d");
		$DAte = date("Y-m-d");


		db_connect();
		# update the supplier (make balance more)
		$sql = "UPDATE suppliers SET balance = (balance + '$pur[total]') WHERE supid = '$pur[supid]' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		$sql = "INSERT INTO sup_stmnt(supid, edate, cacc, amount, descript,ref,ex,div) VALUES('$pur[supid]','$pur[pdate]', '$dept[credacc]', '$pur[total]', 'Stock Received - Purchase $pur[purnum]', '$refnum','$pur[purnum]','".USER_DIV."')";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);



		# Debit Stock Control and Credit Creditors control
		writetrans($wh['conacc'], $dept['credacc'], $td, $refnum, ($pur['total'] - $pur['vat']), "Invoice Received for Purchase No. $pur[purnum] from Supplier : $pur[supname].");

		# Transfer vat
		writetrans($vatacc, $dept['credacc'], $td, $refnum, $pur['vat'], "Vat Paid for Purchase No. $pur[purnum] from Supplier : $pur[supname].");

		# Ledger Records
		suppledger($pur['supid'], $wh['conacc'], $td, $pur['purid'], "Purchase No. $pur[purnum] received.", $pur['total'], 'c');
		db_connect();

		/* End Transactions */

		/* Make transaction record  for age analysis */
			db_connect();
			# update the supplier age analysis (make balance less)
			if(ext_ex2("suppurch", "purid", $pur['purnum'], "supid", $pur['supid'])){
				# Found? Make amount less
				$sql = "UPDATE suppurch SET balance = (balance + '$pur[total]') WHERE supid = '$pur[supid]' AND purid = '$pur[purnum]' AND div = '".USER_DIV."'";
				$rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);
			}else{
				/* Make transaction record for age analysis */
				$sql = "INSERT INTO suppurch(supid, purid, pdate, balance, div) VALUES('$pur[supid]', '$pur[purnum]', '$pur[pdate]', '$pur[total]', '".USER_DIV."')";
				$purcRslt = db_exec($sql) or errDie("Unable to update Order information in Cubit.",SELF);
			}

		/* Make transaction record  for age analysis */

	# commit updating
	//1 ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

/* Start moving if purchase */
	if($pur['received'] == "y"){

		if(strlen($pur['appdate'])<8) {
			$pur['appdate']=date("Y-m-d");
		}

		# copy purchase
		db_conn(PRD_DB);
		$sql = "INSERT INTO purchases(purid, deptid, supid, supname, supaddr, supno, terms, pdate, ddate, shipchrg, subtot, total, balance, vatinc, vat, shipping, remarks, refno, received, done, div, purnum, supinv,ordernum,appname,appdate)";
		$sql .= " VALUES('$purid', '$pur[deptid]', '$pur[supid]',  '$pur[supname]', '$pur[supaddr]', '$pur[supno]', '$pur[terms]', '$pur[pdate]', '$pur[ddate]', '$pur[shipchrg]', '$pur[subtot]', '$pur[total]', '0', '$pur[vatinc]', '$pur[vat]', '$pur[shipping]', '$pur[remarks]', '$pur[refno]', 'y', 'y', '".USER_DIV."', '$pur[purnum]','$supinv','$pur[ordernum]','$pur[appname]','$pur[appdate]')";
		$rslt = db_exec($sql) or errDie("Unable to insert Order to Cubit.",SELF);

		/*-- Cost varience -- */
		$nsubtot = sprint($pur['total'] - $pur['vat']);
		if($pur['rsubtot'] > $nsubtot){
			$diff = sprint($pur['rsubtot'] - $nsubtot);
			# Debit Stock Control and Credit Creditors control
			writetrans($wh['conacc'], $cvacc, $td, $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
		}elseif($nsubtot > $pur['rsubtot']){
			$diff = sprint($nsubtot - $pur['rsubtot']);
			# Debit Stock Control and Credit Creditors control
			writetrans($cvacc, $wh['conacc'],$td, $refnum, $diff, "Cost Variance for Stock Received on Purchase No. $pur[purnum] from Supplier : $sup[supname].");
		}
		/*-- End Cost varience -- */

		db_connect();
		# Get selected stock
		$sql = "SELECT * FROM pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stktcRslt = db_exec($sql);

		while($stktc = pg_fetch_array($stktcRslt)){
			# Insert purchase items
			db_conn(PRD_DB);
			$sql = "INSERT INTO pur_items(purid, whid, stkid, qty, rqty, unitcost, amt, svat, ddate, div) VALUES('$purid', '$stktc[whid]', '$stktc[stkid]', '$stktc[qty]', '$stktc[rqty]', '$stktc[unitcost]', '$stktc[amt]', '$stktc[svat]', '$stktc[ddate]', '".USER_DIV."')";
			$rslt = db_exec($sql) or errDie("Unable to insert Order items to Cubit.",SELF);
		}

		# begin updating


			db_connect();
			# Remove the purchase from running DB
			$sql = "DELETE FROM purchases WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

			# Record where purchase is
			$sql = "INSERT INTO movpurch(purtype, purnum, prd, div) VALUES('loc', '$pur[purnum]', '$pur[prd]', '".USER_DIV."')";
			$movRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

			# Remove those purchase items from running DB
			$sql = "DELETE FROM pur_items WHERE purid = '$purid' AND div = '".USER_DIV."'";
			$delRslt = db_exec($sql) or errDie("Unable to update int purchases information in Cubit.",SELF);

		/* End moving purchase received */

		# commit updating


	}else{
		# insert Order to DB
		$sql = "UPDATE purchases SET invcd = 'y',supinv='$supinv' WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order status in Cubit.",SELF);
	}

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Final Layout
	$write = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Purchase Invoiced</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Purchase Invoice from Supplier <b>$pur[supname]</b> has been recorded.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='purchase-view.php'>View purchases</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>
