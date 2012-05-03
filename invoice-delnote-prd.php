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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;

		case "write":
			$OUTPUT = write($_POST);
			break;

		default:
			$OUTPUT = details($_POST);
		}
} else {
	$OUTPUT = details($_GET);
}

# Get templete
require("template.php");

# Details
function details($_GET)
{

	# Get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}
	$prd+=0;
	# Get invoice info
	db_conn($prd);
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# Keep the charge vat option stable
	if($inv['chrgvat'] == "inc"){
		$inv['chrgvat'] = "Yes";
	}elseif($inv['chrgvat'] == "exc"){
		$inv['chrgvat'] = "No";
	}else{
		$inv['chrgvat'] = "Non VAT";
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY DELIVERED</th></tr>";
		# get selected stock in this invoice
		db_conn($prd);
		$sql = "SELECT *, (qty - del) as qty FROM inv_items  WHERE invid = '$invid' AND qty > 0 AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		if(pg_numrows($stkdRslt) < 1){
			return "<li> There are no undelivered items for this invoice.";
		}

		$taxex = 0;
		while($stkd = pg_fetch_array($stkdRslt)){
			if($stkd['qty'] == 0){
				continue;
			}

			# Get warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# Get selected stock in this warehouse
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# Check Tax Excempt
			if($stk['exvat'] == 'yes'){
				$taxex += ($stkd['amt']);
			}

			if($stkd['account']>0) {
				$stk['stkid']=0;
				$stk['stkcod']="";
				$stk['stkdes']=$stkd['description'];
			}

			# Put in product
			$products .="<tr class='bg-odd'><td>$wh[whname]</td><td><input type=hidden name=ids[] value='$stkd[id]'><input type=hidden name=stkids[] value='$stk[stkid]'>$stk[stkcod]</td><td>$stk[stkdes]</td><td><input type=hidden size=4 name=qts[] value='$stkd[qty]'><input type=text size=4 name=qtys[] value='$stkd[qty]'></td></tr>";
		}
	$products .= "</table>";

	# Days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	$termssel = extlib_cpsel("terms", $days, $inv['terms']);

	/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = $inv['subtot'];

	# Calculate tradediscm
	if($inv['traddisc'] > 0){
		$traddiscm = round((($inv['traddisc']/100) * $SUBTOT), 2);
	}else{
		$traddiscm = 0;
	}

	# Calculate subtotal
	$VATP = TAX_VAT;
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);
	$traddiscm=sprint($traddiscm);

	$dct  = sprint($inv['delchrg'] - $inv['rdelchrg']);

	/* --- End Some calculations --- */

	/* -- Final Layout -- */
	$details = "<center><h3>Delivery Note</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=invid value='$invid'>
	<input type=hidden name=prd value='$prd'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr class='bg-odd'><td>Department</td><td valign=center>$inv[deptname]</td></tr>
			<tr class='bg-even'><td>Customer</td><td valign=center>$inv[cusname] $inv[surname]</td></tr>
			<tr class='bg-odd'><td valign=top>Customer Address</td><td valign=center>".nl2br($inv['cusaddr'])."</td></tr>
			<tr class='bg-even'><td>Customer Order number</td><td valign=center>$inv[cordno]</td></tr>
			<tr class='bg-odd'><td>Customer VAT Number</td><td>$inv[cusvatno]</td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Invoice Details </th></tr>
			<tr class='bg-odd'><td>Order No.</td><td valign=center>$inv[ordno]</td></tr>
			<tr class='bg-even'><td>Sales Person</td><td valign=center>$inv[salespn]</td></tr>
			<tr class='bg-odd'><td>Delivery Note Date</td><td valign=center><input type=text size=2 name=oday maxlength=2 value='".date("d")."'>-<input type=text size=2 name=omon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=oyear maxlength=4 value='".date("Y")."'> DD-MM-YYYY</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>
	$products
	</td></tr>
	<tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<p>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='cust-credit-stockinv.php'>New Invoice</a></td></tr>
			<tr class='bg-odd'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td></tr>
	<tr><td></td></tr>
	<tr><td></td><td><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# Error
function error($_GET, $err = "")
{

	# Get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");

	# Display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}
	$prd+=0;
	# Get invoice info
	db_conn($prd);
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	/* --- Start Products Display --- */

	# Products layout
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><TH>SERIAL NO.</TH><th>QTY DELIVERED</th></tr>";
		# Get selected stock in this invoice
		db_conn($prd);
		$sql = "SELECT *,(qty - del) as qty FROM inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);

		$tcosamt = 0;
		$taxex = 0;
		while($stkd = pg_fetch_array($stkdRslt)){

			# Get warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# Get selected stock in this warehouse
			db_connect();
			$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]' AND div = '".USER_DIV."'";
			$stkRslt = db_exec($sql);
			$stk = pg_fetch_array($stkRslt);

			# cost amount
			$cosamt = round(($stkd['qty'] * $stk['csprice']), 2);
			$tcosamt += $cosamt;

			# Check Tax Excempt
			if($stk['exvat'] == 'yes'){
				$taxex += $stkd['amt'];
			}

			if($stkd['account']>0) {
				$stk['stkid']=0;
				$stk['stkcod']="";
				$stk['stkdes']=$stkd['description'];
			}

			# Put in product
			$products .="<tr class='bg-odd'><td>$wh[whname]</td><td><input type=hidden name=ids[] value='$stkd[id]'><input type=hidden name=stkids[] value='$stk[stkid]'>$stk[stkcod]</td><td>$stk[stkdes]</td><td><input type=hidden name=sers[$stkd[stkid]][] value='$stkd[serno]'>$stkd[serno]</td><td><input type=hidden size=4 name=qts[] value='$stkd[qty]'><input type=text size=4 name=qtys[] value='$stkd[qty]'></td></tr>";
		}
	$products .= "</table>";

	# Get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class=err>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Days drop downs
	$days = array("30"=>"30","60"=>"60","90"=>"90","120"=>"120");
	//$termssel = extlib_cpsel("terms", $days, $terms);

	/* --- Start Some calculations --- */

	# Calculate subtotal
	$SUBTOT = $inv['subtot'];

	# Calculate tradediscm
	if($inv['traddisc'] > 0){
		$traddiscm = round((($inv['traddisc']/100) * $SUBTOT), 2);
	}else{
		$traddiscm = 0;
	}

	$VATP = TAX_VAT;

	# Calculate subtotal
	$SUBTOT = sprint($inv['subtot']);
 	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);
	$inv['delchrg'] = sprint($inv['delchrg']);

	$dct  = sprint($inv['delchrg'] - $inv['rdelchrg']);

	/* --- End Some calculations --- */

	$traddiscm=sprint($traddiscm);

	/* -- Final Layout -- */
	$details = "
	<center><h3>Delivery Note</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=invid value='$invid'>
	<input type=hidden name=prd value='$prd'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td>$err</td></tr>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr class='bg-odd'><td>Department</td><td valign=center>$inv[deptname]</td></tr>
			<tr class='bg-even'><td>Customer</td><td valign=center>$inv[cusname] $inv[surname]</td></tr>
			<tr class='bg-odd'><td valign=top>Customer Address</td><td valign=center>".nl2br($inv['cusaddr'])."</td></tr>
			<tr class='bg-even'><td>Customer Order number</td><td valign=center>$inv[cordno]</td></tr>
			<tr class='bg-odd'><td>Customer VAT Number</td><td>$inv[cusvatno]</td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Invoice Details </th></tr>
			<tr class='bg-odd'><td>Order No.</td><td valign=center>$inv[ordno]</td></tr>
			<tr class='bg-even'><td>Sales Person</td><td valign=center>$inv[salespn]</td></tr>
			<tr class='bg-odd'><td>Delivery Date</td><td valign=center><input type=text size=2 name=oday maxlength=2 value='$oday'>-<input type=text size=2 name=omon maxlength=2 value='$omon'>-<input type=text size=4 name=oyear maxlength=4 value='$oyear'> DD-MM-YYYY</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>
	$products
	</td></tr>
	<tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<p>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='cust-credit-stockinv.php'>New Invoice</a></td></tr>
			<tr class='bg-odd'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td></tr>
	<tr><td></td></tr>
	<tr><td align=right></td><td><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# details
function confirm($_POST)
{

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	$prd+=0;
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	//$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	//$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($oday, "num", 1, 2, "Invalid Invoice Date day.");
	$v->isOk ($omon, "num", 1, 2, "Invalid Invoice Date month.");
	$v->isOk ($oyear, "num", 1, 5, "Invalid Invoice Date year.");
	$odate = $oday."-".$omon."-".$oyear;
	if(!checkdate($omon, $oday, $oyear)){
		$v->isOk ($odate, "num", 1, 1, "Invalid Invoice Date.");
	}
	//$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	//$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	//if($delchrg > $dct){
//		$v->isOk ($delchrg, "float", 0, 0, "Error : Delivery Charge amount must not be more than the amount in the Invoice.");
	//}

	# Used to generate errors
	$error = "asa@";

	# Check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			if($qtys[$keys] > $qts[$keys]){
				$v->isOk ($qty, "num", 0, 0, "The Returned Quantity cannot be more than the quantity sold.");
			}
			$v->isOk ($qty, "num", 1, 10, "Invalid Returned Quantity.");
		//	$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount.");
			//$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Returned Quantity.");
	}

	# check stkids
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Stock number, please enter all details.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		# $confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return error($_POST, $err);
	}

	# Get invoice info
	db_conn($prd);
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	# Keep the charge vat option stable
	if($inv['chrgvat'] == "inc"){
		$vchrgvat = "Yes";
	}elseif($inv['chrgvat'] == "exc"){
		$vchrgvat = "No";
	}else{
		$vchrgvat = "Non VAT";
	}

	/* --- Start Products Display --- */

	# Products layout
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><TH>SERIAL NO.</TH><th>QTY DELIVERED</th><tr>";

		$c = 0;
		$taxex = 0;
		foreach($qtys as $keys => $value){
			if($qtys[$keys] > 0){
				db_connect();
				# get selamt from selected stock
				$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
				$stkRslt = db_exec($sql);
				$stk = pg_fetch_array($stkRslt);

				db_conn($prd);
				# get selected stock in this invoice
				$sql = "SELECT * FROM inv_items  WHERE stkid = '$stkids[$keys]' AND invid ='$invid'  AND id ='$ids[$keys]' AND div = '".USER_DIV."'";
				$stkdRslt = db_exec($sql);
				$stkd = pg_fetch_array($stkdRslt);

				if($stkd['account']==0) {

					# get warehouse name
					db_conn("exten");
					$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
					$whRslt = db_exec($sql);
					$wh = pg_fetch_array($whRslt);



					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * ($stkd['unitcost']));

					db_connect();
					$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
					$Ri=db_exec($Sl);

					if(pg_num_rows($Ri)<1) {
						return "Please select the vatcode for all your stock.";
					}

					$vd=pg_fetch_array($Ri);


					# Check Tax Excempt
					if($stk['exvat'] == 'yes'||$vd['zero']=="Yes"){
						$taxex += $amt[$keys];
					}

					if(!(isset($sers[$stk['stkid']][$keys]))) { $sers[$stk['stkid']][$keys]="";}

					$serial = $sers[$stk['stkid']][$keys];

					if(!(isset($sers[$stk['stkid']][$keys]))) { print "error";}
					# Put in product
					$products .="<tr class='bg-odd'><td>$wh[whname]</td><td><input type=hidden name=ids[] value='$ids[$keys]'><input type=hidden name=stkids[] value='$stk[stkid]'>$stk[stkcod]</td><td>$stk[stkdes]</td><td><input type=hidden name=sers[$stkd[stkid]][] value='$serial'>$serial</td><td><input type=hidden size=5 name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td></tr>";
					$c++;
				} else {
					# get warehouse name
					db_conn("core");
					$sql = "SELECT accname FROM accounts WHERE accid = '$stkd[account]'";
					$whRslt = db_exec($sql);
					$wh = pg_fetch_array($whRslt);

					$disc[$keys]=0;

					# Calculate amount
					$amt[$keys] = ($qtys[$keys] * ($stkd['unitcost'] - $disc[$keys]));

					db_connect();
					$Sl="SELECT * FROM vatcodes WHERE id='$stkd[vatcode]'";
					$Ri=db_exec($Sl);

					if(pg_num_rows($Ri)<1) {
						return "Please select the vatcode for all your stock.";
					}

					$vd=pg_fetch_array($Ri);

					if($stkd['account']>0) {
						$wh['whname']="";
						$stk['stkid']=0;
						$stk['stkcod']=$wh['accname'];
						$stk['stkdes']=$stkd['description'];
					}


					# Check Tax Excempt
					if($vd['zero']=="Yes"){
						$taxex += $amt[$keys];
					}

					if(!(isset($sers[$stk['stkid']][$keys]))) { $sers[$stk['stkid']][$keys]="";}

					$serial = $sers[$stk['stkid']][$keys];

					if(!(isset($sers[$stk['stkid']][$keys]))) { print "error";}
					# Put in product
					$products .="<tr class='bg-odd'><td>$wh[whname]</td><td><input type=hidden name=ids[] value='$ids[$keys]'><input type=hidden name=stkids[] value='$stk[stkid]'>$stk[stkcod]</td><td>$stk[stkdes]</td><td><input type=hidden name=sers[$stkd[stkid]][] value='$serial'>$serial</td><td><input type=hidden size=5 name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td></tr>";

					$c++;
				}
			}
		}
	$products .= "</table>";

	if($c < 1){
		$err = "<li class=err>Please enter quantity.</li>";
		return error($_POST, $err);
	}

		/* --- ----------- Clac --------------------- */
		##----------------------NEW----------------------

		$sub = 0.00;
		if(isset($amt)) {
			$sub = sprint(array_sum($amt));
		}

		$VATP = TAX_VAT;



		/* --- ----------- Clac --------------------- */
		##----------------------END----------------------

/* --- ----------- Clac ---------------------
		# calculate subtot
		$SUBTOT = 0.00;
		if(isset($amt))
			$SUBTOT = array_sum($amt);

		$SUBTOT -= $taxex;

		# duplicate
		$SUBTOTAL = $SUBTOT;

		$VATP = TAX_VAT;
		if($inv['chrgvat'] == "exc"){
			$SUBTOTAL = $SUBTOTAL;
			$delexvat= ($delchrg);
		}elseif($inv['chrgvat'] == "inc"){
			$SUBTOTAL = sprint(($SUBTOTAL * 100)/(100 + $VATP));
			$delexvat = sprint(($delchrg * 100)/($VATP + 100));
		}else{
			$SUBTOTAL = ($SUBTOTAL);
			$delexvat = ($delchrg);
		}

		$SUBTOT = $SUBTOTAL;
		$EXVATTOT = $SUBTOT;
		$EXVATTOT += $delexvat;

		# Minus trade discount from taxex
		if($traddisc > 0){
			$traddiscmtt = (($traddisc/100) * $taxex);
		}else{
			$traddiscmtt = 0;
		}
		$taxext = ($taxex - $traddiscmtt);

		if($traddisc > 0) {
			$traddiscmt = ($EXVATTOT * ($traddisc/100));
		}else{
			$traddiscmt = 0;
		}
		$EXVATTOT -= $traddiscmt;
		// $EXVATTOT -= $taxex;

		$traddiscmt = sprint($traddiscmt  + $traddiscmtt);

		if($inv['chrgvat'] != "nov"){
			$VAT = sprint($EXVATTOT * ($VATP/100));
		}else{
			$VAT = 0;
		}

		$TOTAL = sprint($EXVATTOT + $VAT + $taxext);
		$SUBTOT += $taxex;

/* --- ----------- Clac --------------------- */

//	$traddiscmt = sprint($traddiscmt);

	/* -- Final Layout -- */
	$details = "<center><h3>Delivery Note</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=invid value='$invid'>
	<input type=hidden name=oday value='$oday'>
	<input type=hidden name=omon value='$omon'>
	<input type=hidden name=oyear value='$oyear'>
	<input type=hidden name=prd value='$prd'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Customer Details </th></tr>
			<tr class='bg-odd'><td>Department</td><td valign=center>$inv[deptname]</td></tr>
			<tr class='bg-even'><td>Customer</td><td valign=center>$inv[cusname] $inv[surname]</td></tr>
			<tr class='bg-odd'><td valign=top>Customer Address</td><td valign=center>".nl2br($inv['cusaddr'])."</td></tr>
			<tr class='bg-even'><td>Customer Order number</td><td valign=center>$inv[cordno]</td></tr>
			<tr class='bg-odd'><td>Customer VAT Number</td><td>$inv[cusvatno]</td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Invoice Details </th></tr>
			<tr class='bg-odd'><td>Order No.</td><td valign=center>$inv[ordno]</td></tr>
			<tr class='bg-even'><td>Sales Person</td><td valign=center>$inv[salespn]</td></tr>
			<tr class='bg-odd'><td>Delivery Date</td><td valign=center><input type=hidden name=odate value='$odate'>$odate</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>
	$products
	</td></tr>
	<tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<p>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='cust-credit-stockinv.php'>New Invoice</a></td></tr>
			<tr class='bg-odd'><td><a href='invoice-view.php'>View Invoices</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td></tr>
	<tr><td></td></tr>
	<tr><td><input type=submit name=back value='&laquo; Correction'></td><td><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	</center>";

	return $details;
}

# details
function write($_POST)
{

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	if(isset($back)) {
		return details($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	//$v->isOk ($comm, "string", 0, 255, "Invalid Comments.");
	//$v->isOk ($terms, "num", 1, 20, "Invalid terms.");
	$v->isOk ($odate, "date", 1, 14, "Invalid Invoice note date.");
	//$v->isOk ($traddisc, "float", 0, 20, "Invalid Trade Discount.");
	//$v->isOk ($delchrg, "float", 0, 20, "Invalid Delivery Charge.");
	//$v->isOk ($SUBTOT, "float", 0, 20, "Invalid Delivery Charge.");

	# used to generate errors
	$error = "asa@";
	$prd+=0;
	# check quantities
	if(isset($qtys)){
		foreach($qtys as $keys => $qty){
			$v->isOk ($qty, "num", 1, 10, "Invalid Returned Quantity.");
			//$v->isOk ($disc[$keys], "float", 0, 20, "Invalid Discount.");
			//$v->isOk ($discp[$keys], "float", 0, 20, "Invalid Discount Percentage.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Returned Quantity.");
	}

	# check stkids[]
	if(isset($stkids)){
		foreach($stkids as $keys => $stkid){
			$v->isOk ($stkid, "num", 1, 10, "Invalid Stock number, please enter all details.");
		}
	}else{
		$v->isOk ($error, "num", 0, 1, "Invalid Stock number, please enter all details.");
	}

	# check amt[]
	if(isset($amt)){
		foreach($amt as $keys => $amount){
			$v->isOk ($amount, "float", 1, 20, "Invalid Amount, please enter all details.");
		}
	}else{
		//$v->isOk ($error, "num", 0, 1, "Invalid Amount, please enter all details.");
	}

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return error($_POST, $err);
	}

/* -------------------------------- */
	# Get invoice info
	db_conn($prd);
	$sql = "SELECT * FROM invoices WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class=err>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	//$notenum = divlastid('note', USER_DIV);
/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$taxex = 0;
	foreach($qtys as $keys => $value){
		db_connect();
		# get selamt from selected stock
		$sql = "SELECT * FROM stock WHERE stkid = '$stkids[$keys]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);
		db_conn($prd);
		# get selected stock in this invoice
		$sql = "SELECT * FROM inv_items  WHERE stkid = '$stkids[$keys]' AND invid ='$invid'  AND id ='$ids[$keys]' AND div = '".USER_DIV."'";
		$stkdRslt = db_exec($sql);
		$stkd = pg_fetch_array($stkdRslt);

		if($stkd['account']==0) {


			# get warehouse name
			db_conn("exten");
			$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]' AND div = '".USER_DIV."'";
			$whRslt = db_exec($sql);
			$wh = pg_fetch_array($whRslt);

			# Calculate the Discount discount


			# put in product
			$products .="<tr><td><input type=hidden name=stkids[] value='$stk[stkid]'>$stk[stkcod]</td><td>$stk[stkdes]</td><td><input type=hidden size=5 name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td></tr>";

			db_conn($prd);

			$Sl="UPDATE inv_items SET del=del+'$qtys[$keys]' WHERE id='$stkd[id]'";
			$Ri=db_exec($Sl);
		} else {


			$wh['whname']="";
			$stk['stkid']=0;
			$stk['stkcod']="";
			$stk['stkdes']=$stkd['description'];

			$Sl="UPDATE inv_items SET del=del+'$qtys[$keys]' WHERE id='$stkd[id]'";
			$Ri=db_exec($Sl);

			# put in product
			$products .="<tr><td><input type=hidden name=stkids[] value='$stk[stkid]'>$stk[stkcod]</td><td>$stk[stkdes]</td><td><input type=hidden size=5 name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td></tr>";

			//$products .="<tr><td><input type=hidden name=stkids[] value='$stk[stkid]'>$stk[stkcod]</td><td>$stk[stkdes]</td><td><input type=hidden size=5 name='qtys[]' value='$qtys[$keys]'>$qtys[$keys]</td><td>$stkd[unitcost]</td><td><input type=hidden name='amt[]' value='$amt[$keys]'>".CUR." $amt[$keys]</td></tr>";
		}
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$inv[deptid]' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<i class=err>Not Found</i>";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

		/* --- ----------- Clac --------------------- */
		##----------------------NEW----------------------

		$sub = 0.00;
		if(isset($amt)) {
			$sub = sprint(array_sum($amt));
		}

		$VATP = TAX_VAT;

	# Insert invoice to period DB
	db_conn($inv['prd']);

	# Format date
	$odate = explode("-", $odate);
	$rodate = $odate[2]."-".$odate[1]."-".$odate[0];
	$td=$rodate;

	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	/* - End Transactoins - */

	/* -- Final Layout -- */
	$details = "<center><h2>Delivery Note</h2>
	<table cellpadding='0' cellspacing='4' border=0 width=750>
	<tr><td valign=top width=30%>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><td>$inv[surname]</td></tr>
			<tr><td>".nl2br($inv['cusaddr'])."</td></tr>
			<tr><td>(VAT No. $inv[cusvatno])</td></tr>
		</table>
	</td><td valign=top width=25%>
		".COMP_NAME."<br>
		".COMP_ADDRESS."<br>
		".COMP_TEL."<br>
		".COMP_FAX."<br>
	</td><td width=20%>
		<img src='compinfo/getimg.php' width=230 height=47>
	</td><td valign=bottom align=right width=25%>
		<table cellpadding='2' cellspacing='0' border=1 bordercolor='#000000'>
			<tr><td><b>Invoice No.</b></td><td valign=center>$inv[invnum]</td></tr>
			<tr><td><b>Order No.</b></td><td valign=center>$inv[ordno]</td></tr>
			<tr><td><b>Delivery note Date</b></td><td valign=center>$rodate</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=4>
	<table cellpadding='5' cellspacing='0' border=1 width=100% bordercolor='#000000'>
		<tr><td><b>ITEM NUMBER</b></td><td width=45%><b>DESCRIPTION</b></td><td><b>QTY DELIVERED</b></td></tr>
		$products
	</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=1>
        	<tr><td>VAT No.</td><td align=center>".COMP_VATNO."</td></tr>
        </table>
	</td><td><br></td></tr>
	</table></center>";
	//$OUTPUT = "<script>printer('invoice-note-reprint.php?noteid=$noteid&prd=$inv[prd]&cccc=yes');move('index-sales.php');</script>";
	$OUTPUT =$details;
	require ("tmpl-print.php");

}
?>
