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
		$confirm = "<h3>Stock Order Receipt</h3>
        <h4>Details</h4>
		<form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=write>
        <input type=hidden name=ordnum value='$ordnum'>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
			<tr><th>Field</th><th>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Reference No.</td><td><input type=text name=refno size=10></td></tr>
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
		$v->isOk ($refno, "string", 0, 50, "Invalid Reference number.");

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

		$refnum = getrefnum(date("d-m-Y"););

		// Get Bank account [the traditional way re: hook of hook]
        core_connect();
        $sql = "SELECT * FROM bankacc WHERE accid = '$bankacc'";
        $rslt = db_exec($sql) or errDie("Unable to retrieve bank account link from Cubit",SELF);
        # check if link exists
        if(pg_numrows($rslt) <1){
                return "<li class=err> ERROR : The bank account that you selected doesn't appear to have an account linked to it.";
        }
        $bank = pg_fetch_array($rslt);
        $bankaccid = $bank["accnum"];

		// Update stock
		db_connect();
		$sql = "UPDATE stock SET units = (units + '$units'), ordered = (ordered - '$units'), csamt = (csamt + '$csamt') WHERE stkid = '$stkid'";
		$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

		// update order
		$sql = "UPDATE orders SET recved = 'yes', refno = '$refno' WHERE ordnum = '$ordnum'";
		$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);


		$date = date("d-m-Y");

		// insert into stock purchased
		$Sql = "INSERT INTO stock_purch(stkid, date, units, cost) VALUES('$stkid', '$orddate', '$units', '$csamt')";
		$Rslt = db_exec($Sql) or errDie("Unable to insert stock to Cubit.",SELF);

		# get accounts
		db_conn("exten");
		$sql = "SELECT stkacc FROM warehouses WHERE whid = '$stk[whid]'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);
		$stockacc = $wh['stkacc'];

		# Write Trans(debit_account_id, credit_account_id, date, refnum, amount_[11111.00], details)
		writetrans($stockacc, $bankaccid, $date, $refnum, $csamt, "bought $units x $stk[stkdes]");

		$write ="
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
			<tr><th>Stock Added</th></tr>
			<tr class=datacell><td>Stock, <b>$stk[stkdes] ($stk[stkcod])</b> has been successfully added to Cubit.</td></tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='order-new.php'>New Order</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td><a href='orders-view.php'>View Orders</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

		return $write;
}

# Write Trans(debit_account_id, credit_account_id, date, refnum, amount_[11111.00], details)
function writetrans($dtacc, $ctacc, $date, $refnum, $amount, $details)
{
        # validate input
		require_lib("validate");
		$v = new  validate ();
        $v->isOk ($ctacc, "num", 1, 50, "Invalid Account to be Credited.");
        $v->isOk ($dtacc, "num", 1, 50, "Invalid Account to be Debited.");
        $v->isOk ($date, "date", 1, 14, "Invalid date.");
        $v->isOk ($refnum, "num", 1, 50, "Invalid reference number.");
        $v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
        $v->isOk ($details, "string", 0, 255, "Invalid Details.");

		# display errors, if any
		if ($v->isError ()) {
			$write = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$write .= "<li class=err>".$e["msg"];
			}
			$write .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			$OUTPUT = $write;
			require("templete.php");
		}

        # date format
        $date = explode("-", $date);
        $date = $date[2]."-".$date[1]."-".$date[0];

        # begin sql transaction
        # pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		// Insert the records into the transect table
		db_conn(PRD_DB);
		$sql = "INSERT INTO transect(debit, credit, date, refnum, amount, author, details) VALUES('$dtacc', '$ctacc', '$date', '$refnum', '$amount', '".USER_NAME."', '$details')";
		$transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

		// Update the balances by adding appropriate values to the trial_bal Table
		core_connect();
		$ctbal = "UPDATE trial_bal SET credit = (credit + '$amount') WHERE accid = '$ctacc'";
		$dtbal = "UPDATE trial_bal SET debit = (debit + '$amount') WHERE accid  = '$dtacc'";
		$ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
		$dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);

        # commit sql transaction
        # pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);
}
?>
