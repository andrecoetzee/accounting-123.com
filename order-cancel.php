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
			if(isset($_GET['ordnum'])){
					$OUTPUT = details($_GET['ordnum']);
			}else{
					$OUTPUT = "<li class=err> Invalid Order number.";
			}
	}
}else{
	if(isset($_GET['ordnum'])){
			$OUTPUT = details($_GET['ordnum']);
	}else{
			$OUTPUT = "<li class=err> Invalid Order number.";
	}
}

# get templete
require("template.php");

# View details
function details($ordnum)
{
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($ordnum, "num", 1, 50, "Invalid order number.");

        # display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>".$e["msg"];
			}
			$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}

		# Select Order
		db_connect();
		$sql = "SELECT * FROM orders WHERE ordnum = '$ordnum'";
        $ordRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($ordRslt) < 1){
                return "<li> Invalid Order number.";
        }else{
                $ord = pg_fetch_array($ordRslt);
		}

		# get order vars
		foreach ($ord as $key => $value) {
			$$key = $value;
		}

		# Select Stock
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkid'";
        $stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($stkRslt) < 1){
                return "<li> Invalid Stock ID.";
        }else{
                $stk = pg_fetch_array($stkRslt);
		}

		if($buom < 1 && $suom < 1){
				return "<li calss=err> Your units ordered calculate to zero. Please enter valid values.";
		}

		# Get bank account name
        db_connect();
        $sql = "SELECT * FROM bankacct WHERE bankid = '$bankacc'";
        $bankRslt = db_exec($sql);

        if(pg_numrows($bankRslt) < 1){
                return "<li class=err>ERROR : Invalid Bank Account Number.
                <p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
        }
        $bank = pg_fetch_array($bankRslt);

		// Layout
		$confirm = "<h3>Cancel Stock Order</h3>
        <h4>Details</h4>
		<form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=write>
        <input type=hidden name=ordnum value='$ordnum'>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
			<tr><th>Field</th><th>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Stock code</td><td>$stk[stkcod]</td></tr>
        	<tr bgcolor='".TMPL_tblDataColor2."'><td>Stock description</td><td>$stk[stkdes]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Supplier</td><td>$supplier</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Tel No.</td><td>$tel</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Fax No.</td><td>$fax</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>Address</td><td><pre>$addr</pre></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Order Date</td><td>$orddate</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Delivery Date</td><td>$deldate</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Buying units</td><td>$buom x $stk[buom]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Selling units</td><td>$suom x $stk[suom]</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Cost Amount</td><td>".CUR." $csamt</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Bank Account</td><td>$bank[accname]</td></tr>
			<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
		</table><br><br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
        	<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='order-new.php'>New Order</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='orders-view.php'>View Orders</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
         </form>
        </table>";

	return $confirm;
}

# write
function write($_POST)
{

		//processes
		db_connect();
		# get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($ordnum, "num", 1, 50, "Invalid Order number.");

        # display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>".$e["msg"];
			}
			$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}

		# Select Order
		db_connect();
		$sql = "SELECT * FROM orders WHERE ordnum = '$ordnum'";
        $ordRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($ordRslt) < 1){
                return "<li> Invalid Order number.";
        }else{
                $ord = pg_fetch_array($ordRslt);
		}

		# get order vars
		foreach ($ord as $key => $value) {
			$$key = $value;
		}

		# Select Stock
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$stkid'";
        $stkRslt = db_exec($sql) or errDie("Unable to access stock database.", SELF);
		if(pg_numrows($stkRslt) < 1){
                return "<li> Invalid Stock ID.";
        }else{
                $stk = pg_fetch_array($stkRslt);
        }

		# Calculate total units bought
		$units = 0;
		if($buom > 0){
			$units += ($buom * $stk['rate']);
		}
		if($suom > 0){
			$units += $suom;
		}

		// Update stock
		db_connect();
		$sql = "UPDATE stock SET ordered = (ordered - '$units') WHERE stkid = '$stkid'";
		$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

		// update order
		$sql = "UPDATE orders SET recved = 'c' WHERE stkid = '$stkid'";
		$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

		$write ="
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
			<tr><th>Stock Order Canceled</th></tr>
			<tr class=datacell><td>Stock Order for, <b>$stk[stkdes] ($stk[stkcod])</b> has been canceled.</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='order-new.php'>New Order</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='orders-view.php'>View Orders</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>`
		</table>";

		return $write;
}
?>
