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
}else{
	if (isset($_POST["key"])) {
		switch ($_POST["key"]) {
            case "update":
				$OUTPUT = write($_POST);
				break;

            default:
				$OUTPUT = "<li class=err> Ivalid use of module.";
			}
	} else {
		$OUTPUT = "<li class=err> Ivalid use of module.";
	}
}

# get templete
require("template.php");

# details
function details($_POST, $error="")
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
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
	$sql = "SELECT * FROM purchases WHERE purid = '$purid'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class=err>purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been printed
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : purchase number <b>$purid</b> has already been received.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# get department
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE deptid = '$pur[deptid]'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$dept['deptname'] = "<li class=err>Department not Found.";
	}else{
		$dept = pg_fetch_array($deptRslt);
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]'";
	$supRslt = db_exec ($sql) or errDie ("Unable to view customer");
	if (pg_numrows ($supRslt) < 1) {
		$sup['supname'] = "<li class=err> Supplier not Found.";
		$sup['supaddr'] = "<br><br><br>";
	}else{
		$sup = pg_fetch_array($supRslt);
		$supaddr = $sup['supaddr'];
	}

/* --- Start Drop Downs --- */

	# Select warehouse
	db_conn("exten");
	$whs = "<select name='whidss[]' onChange='javascript:document.form.submit();'>";
	$sql = "SELECT * FROM warehouses ORDER BY whname ASC";
	$whRslt = db_exec($sql);
	if(pg_numrows($whRslt) < 1){
			return "<li class=err> There are no Warehouses found in Cubit.";
	}else{
			$whs .= "<option value='-S' disabled selected>Select Warehouse</option>";
			while($wh = pg_fetch_array($whRslt)){
					$whs .= "<option value='$wh[whid]'>($wh[whno]) $wh[whname]</option>";
			}
	}
	$whs .="</select>";

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
	<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY RECEIVED</th><th>UNIT PRICE</th><th>DELIVERY DATE</th><th>AMOUNT</th><tr>";

	# get selected stock in this purchase
	db_connect();
	$sql = "SELECT * FROM pur_items  WHERE purid = '$purid'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

		# get warehouse name
		db_conn("exten");
		$sql = "SELECT whname FROM warehouses WHERE whid = '$stkd[whid]'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		# get selected stock in this warehouse
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkd[stkid]'";
		$stkRslt = db_exec($sql);
		$stk = pg_fetch_array($stkRslt);

		list($syear, $smon, $sday) = explode("-", $stkd['ddate']);

		# put in product
		$products .="<tr class='bg-odd'><td>$wh[whname]</td><td><input type=hidden name=stkids[] value='$stkd[stkid]'><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td><td>$stk[stkdes]</td><td><input type=text size=5 name=qtys[] value='$stkd[qty]'></td><td>$stkd[unitcost]</td><td>$sday-$smon-$syear</td><td>".CUR." $stkd[amt]</td></tr>";
		$key++;
	}
	# look above(if i = 0 then there are no products)
	if($i == 0){
		$done = "";
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* --- Start Some calculations --- */

	# Calculate subtotal
	if(isset($amts)){
		$SUBTOT = array_sum($amts);
	}else{
		$SUBTOT = 0.00;
	}

	# Total
	$TOTAL = ($SUBTOT + $pur['shipchrg']);

/* --- End Some calculations --- */

/* -- Final Layout -- */
	$details = "<center><h3>Purchase received</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=update>
	<input type=hidden name=purid value='$purid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
 	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Supplier Details </th></tr>
			<tr class='bg-odd'><td>Department</td><td valign=center>$dept[deptname]</td></tr>
   			<tr class='bg-even'><td>Supplier</td><td valign=center>$sup[supname]</td></tr>
			<tr class='bg-odd'><td valign=top>Supplier Address</td><td valign=center>".nl2br($supaddr)."</td></tr>
			<tr><th colspan=2 valign=top>Remarks</th></tr>
			<tr class='bg-even'><td colspan=2 align=center>".nl2br($pur['remarks'])."</td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Purchase Details </th></tr>
			<tr class='bg-odd'><td>Purchase No.</td><td valign=center>$purid</td></tr>
			<tr class='bg-even'><td>Delivery Ref No.</td><td valign=center>$pur[refno]</td></tr>
			<tr class='bg-odd'><td>Terms</td><td valign=center>$pur[terms] Days</td></tr>
			<tr class='bg-even'><td>Date</td><td valign=center>$pday-$pmon-$pyear DD-MM-YYYY</td></tr>
			<tr class='bg-odd'><td>Delivery Charges</td><td valign=center>".CUR." $pur[shipchrg]</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>$products</td></tr>
	<tr><td>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='cust-credit-stockinv.php'>New purchase</a></td></tr>
			<tr class='bg-odd'><td><a href='purchase-view.php'>View purchases</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr class='bg-odd'><td>SUBTOTAL</td><td align=right>".CUR." $SUBTOT</td></tr>
			<tr class='bg-even'><td>Delivery Charges</td><td align=right>".CUR." $pur[shipchrg]</td></tr>
			<tr class='bg-odd'><th>GRAND TOTAL</th><td align=right>".CUR." $TOTAL</td></tr>
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

	#get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($purid, "num", 1, 20, "Invalid purchase number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return details($_POST, $err);
	}

	# Get purchase info
	db_connect();
	$sql = "SELECT * FROM purchases WHERE purid = '$purid'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get purchase information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- purchase Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if purchase has been received
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : purchase number <b>$purid</b> has already been received.";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# Get selected supplier info
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$pur[supid]'";
	$supRslt = db_exec ($sql) or errDie ("Unable to get customer information");
	if (pg_numrows ($supRslt) < 1) {
		// code here
	}else{
		$sup = pg_fetch_array($supRslt);
	}

	# Insert purchase to DB
	db_connect();

	# begin updating
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# Get selected stock
		$sql = "SELECT * FROM pur_items  WHERE purid = '$purid'";
		$stktRslt = db_exec($sql);

		while($stkt = pg_fetch_array($stktRslt)){
			# update stock(ordered + qty)
			$sql = "UPDATE stock SET ordered = (ordered - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}

		# remove items
		$sql = "DELETE FROM pur_items WHERE purid='$purid'";
		$rslt = db_exec($sql) or errDie("Unable to update purchase items in Cubit.",SELF);

		# remove purchase
		$sql = "DELETE FROM purchases WHERE purid='$purid'";
		$rslt = db_exec($sql) or errDie("Unable to remove purchase items in Cubit.",SELF);


		# update purchase on the DB
		// $sql = "UPDATE purchases SET refno = '$refno', remarks = '$remarks' WHERE purid = '$purid'";
		// $rslt = db_exec($sql) or errDie("Unable to update purchase in Cubit.",SELF);

	# commit updating
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	// Final Layout
	$write = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Purchase removed</th></tr>
		<tr class='bg-even'><td>Purchase from Supplier <b>$sup[supname]</b> has been removed.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='purchase-view.php'>View purchases</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>
