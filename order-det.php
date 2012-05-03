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
if (isset($_GET["ordnum"])) {
	$OUTPUT = details($_GET["ordnum"]);
}else{
	$OUTPUT = "<li> Invalid Order number";
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
		$confirm = "<h3>Stock Order</h3>
        <h4>Details</h4>
		<form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=write>
        <input type=hidden name=stkid value='$stkid'>
		<input type=hidden name=supplier value='$supplier'>
		<input type=hidden name=suptel value='$tel'>
		<input type=hidden name=supfax value='$fax'>
		<input type=hidden name=addr value='$addr'>
		<input type=hidden name=odate value='$orddate'>
		<input type=hidden name=csamt value='$csamt'>
		<input type=hidden name=buom value='$buom'>
		<input type=hidden name=suom value='$suom'>
		<input type=hidden name=bankacc value='$bankacc'>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
			<tr><th>Field</th><th>Value</th></tr>
			<tr class='bg-odd'><td>Stock code</td><td>$stk[stkcod]</td></tr>
        	<tr class='bg-even'><td>Stock description</td><td>$stk[stkdes]</td></tr>
			<tr class='bg-odd'><td>Supplier</td><td>$supplier</td></tr>
			<tr class='bg-even'><td>Tel No.</td><td>$tel</td></tr>
			<tr class='bg-odd'><td>Fax No.</td><td>$fax</td></tr>
			<tr class='bg-even'><td valign=top>Address</td><td><pre>$addr</pre></td></tr>
			<tr class='bg-odd'><td>Order Date</td><td>$orddate</td></tr>
			<tr class='bg-even'><td>Delivery Date</td><td>$deldate</td></tr>
			<tr class='bg-odd'><td>Buying units</td><td>$buom x $stk[buom]</td></tr>
			<tr class='bg-even'><td>Selling units</td><td>$suom x $stk[suom]</td></tr>
			<tr class='bg-odd'><td>Cost Amount</td><td>".CUR." $csamt</td></tr>
			<tr class='bg-even'><td>Bank Account</td><td>$bank[accname]</td></tr>
		</table><br><br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
        	<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='order-new.php'>New Order</a></td></tr>
			<tr class='bg-even'><td><a href='orders-view.php'>View Orders</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
        </form>
        </table>";

	return $confirm;
}
?>
