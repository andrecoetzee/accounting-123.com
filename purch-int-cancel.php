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
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");

	# display errors, if any
	if ($v->isError ()) {
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$error .= "<li class=err>".$e["msg"];
		}
		$confirm .= "$error<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# Get Order info
	db_connect();
	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li class=err>Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been printed
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : International Order number <b>$purid</b> has already been received.";
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

	# format date
	list($pyear, $pmon, $pday) = explode("-", $pur['pdate']);
	list($dyear, $dmon, $dday) = explode("-", $pur['ddate']);

/* --- End Drop Downs --- */

/* --- Start Products Display --- */

	# Select all products
	$products = "
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=100%>
	<tr><th>WAREHOUSE</th><th>ITEM NUMBER</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>DUTY</th><th>AMT</th><tr>";

	# Get selected stock in this Order
	db_connect();
	$sql = "SELECT * FROM purint_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);
	$i = 0;
	$key = 0;
	while($stkd = pg_fetch_array($stkdRslt)){

		# keep track of selected stock amounts
		$amts[$i] = $stkd['amt'];
		$i++;

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

		# put in product
		$products .="<tr class='bg-odd'><td>$wh[whname]</td><td><a href='#' onclick='openwindow(\"stock-amt-det.php?stkid=$stk[stkid]\")'>$stk[stkcod]</a></td><td>".extlib_rstr($stk['stkdes'], 30)."</td><td>$stkd[qty]</td><td>$pur[curr] $stkd[cunitcost] &nbsp;&nbsp;or &nbsp;&nbsp;R $stkd[unitcost]</td><td>".CUR." $stkd[duty] &nbsp;&nbsp; or &nbsp;&nbsp;$stkd[dutyp]%</td><td>".CUR." $stkd[amt]</td></tr>";
	}
	$products .= "</table>";

/* --- End Products Display --- */

/* -- Final Layout -- */
	$details = "<center><h3>International Order Cancel</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=update>
	<input type=hidden name=purid value='$purid'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=95%>
	<tr><td valign=top>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Supplier Details </th></tr>
			<tr class='bg-odd'><td>Department</td><td valign=center>$dept[deptname]</td></tr>
			<tr class='bg-even'><td>Supplier</td><td valign=center>$sup[supname]</td></tr>
			<tr class='bg-odd'><td valign=top>Supplier Address</td><td valign=center>".nl2br($pur['supaddr'])."</td></tr>
		</table>
	</td><td valign=top align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0>
			<tr><th colspan=2> Order Details </th></tr>
			<tr class='bg-even'><td>Order No.</td><td valign=center>$pur[purnum]</td></tr>
			<tr class='bg-odd'><td>Terms</td><td valign=center>$pur[terms] Days</td></tr>
			<tr class='bg-even'><td>Date</td><td valign=center>$pday-$pmon-$pyear</td></tr>
			<tr class='bg-odd'><td>Foreign Currency</td><td valign=center>$pur[curr]</td></tr>
			<tr class='bg-even'><td>Exchange rate</td><td>".CUR." $pur[xrate]</td></tr>
			<tr class='bg-odd'><td>Tax</td><td valign=center>".CUR." $pur[tax]</td></tr>
			<tr class='bg-even'><td>Shipping Charges</td><td valign=center>$pur[curr] $pur[fshipchrg]</td></tr>
			<tr class='bg-odd'><td>Delivery Date</td><td valign=center>$dday-$dmon-$dyear</td></tr>
		</table>
	</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2>
	$products
	</td></tr>
	<tr><td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<tr><th width=40%>Quick Links</th><th width=45%>Remarks</th><td rowspan=5 valign=top width=15%><br></td></tr>
			<tr class='bg-odd'><td><a href='purch-int-new.php'>New International Order</a></td><td class='bg-odd' rowspan=4 align=center valign=top>".nl2br($pur['remarks'])."</td></tr>
			<tr class='bg-odd'><td><a href='purch-int-view.php'>View International Orders</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>
	</td><td align=right>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=80%>
			<tr class='bg-odd'><td>SUBTOTAL</td><td align=right>".CUR." $pur[subtot]</td></tr>
			<tr class='bg-even'><td>Shipping Charges</td><td align=right>".CUR." $pur[shipchrg]</td></tr>
			<tr class='bg-odd'><td>Tax </td><td align=right>".CUR." $pur[tax]</td></tr>
			<tr class='bg-even'><th>GRAND TOTAL</th><td align=right>".CUR." $pur[total]</td></tr>
		</table>
	</td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'> | <input type=submit name='upBtn' value='Cancel &raquo'></td></tr>
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
	$v->isOk ($purid, "num", 1, 20, "Invalid Order number.");

	# display errors, if any
	$err = "";
	if ($v->isError ()) {
		$errors = $v->getErrors();
			foreach ($errors as $e) {
			$err .= "<li class=err>".$e["msg"];
		}
		return details($_POST, $err);
	}

	# Get Order info
	db_connect();
	$sql = "SELECT * FROM purch_int WHERE purid = '$purid' AND div = '".USER_DIV."'";
	$purRslt = db_exec ($sql) or errDie ("Unable to get Order information");
	if (pg_numrows ($purRslt) < 1) {
		return "<li>- Order Not Found</li>";
	}
	$pur = pg_fetch_array($purRslt);

	# check if Order has been received
	if($pur['received'] == "y"){
		$error = "<li class=err> Error : Order number <b>$purid</b> has already been received.";
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

	# Insert Order to DB
	db_connect();

# begin updating
pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# Get selected stock
		$sql = "SELECT * FROM purint_items  WHERE purid = '$purid' AND div = '".USER_DIV."'";
		$stktRslt = db_exec($sql);

		while($stkt = pg_fetch_array($stktRslt)){
			# update stock(ordered + qty)
			$sql = "UPDATE stock SET ordered = (ordered - '$stkt[qty]')  WHERE stkid = '$stkt[stkid]' AND div = '".USER_DIV."'";
			$rslt = db_exec($sql) or errDie("Unable to update stock to Cubit.",SELF);
		}

		# Remove items
		$sql = "DELETE FROM purint_items WHERE purid='$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to update Order items in Cubit.",SELF);

		# Remove Order
		$sql = "DELETE FROM purch_int WHERE purid='$purid' AND div = '".USER_DIV."'";
		$rslt = db_exec($sql) or errDie("Unable to remove Order items in Cubit.",SELF);

		# Insert record
		$sql = "INSERT INTO cancelled_purch(purnum, pdate, username, div) VALUES('$pur[purnum]', '$pur[pdate]', '".USER_NAME."', '$pur[div]')";
		$rslt = db_exec($sql) or errDie("Unable to remove Order items in Cubit.",SELF);

# commit updating
pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	if(!(isset($sup['supname']))) {
		$sup['supname']='';
	}

	// Final Layout
	$write = "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>International Order Cancel</th></tr>
		<tr class='bg-even'><td>International Order from Supplier <b>$sup[supname]</b> has been cancelled.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='purch-int-view.php'>View International Orders</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>
