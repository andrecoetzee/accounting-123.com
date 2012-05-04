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

                case "write":
                        $OUTPUT = write($_POST);
        		break;
                case "confirm":
                        $OUTPUT = confirm($_POST);
        		break;
                default:
                        $OUTPUT = det($_POST);
	}
} else {
        $OUTPUT = det($_POST);
}

# get templete
require("template.php");

# Confirm
function det($_POST)
{
        # Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        if(isset($ord)){
                foreach($ord as $key => $value){
                        $v->isOk ($ord[$key], "num", 1, 50, "Invalid order No.");
                }
        }else{
                return "<li> - No Credit Purchases Seleted. Please select at least one entry.";
        }

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

		$refnum = getrefnum();
/*refnum*/

        # connect to core
        core_Connect();

        # account to be paid
        $paid = "<select name='accpaid[]'>";
        $sql = "SELECT * FROM accounts WHERE acctype ='B'";
        $accRslt = db_exec($sql);
        $numrows = pg_numrows($accRslt);
        if(empty($numrows)){
                return "<li>There are no Income accounts yet in Cubit.";
        }else{
                while($acc = pg_fetch_array($accRslt)){
                        $paid .= "<option value='$acc[accid]'>$acc[accname]</option>";
                }
        }
        $paid .="</select>";

        $pay = "";
        # Debtors
        foreach($ord as $key => $value){
                db_connect();
                # Get all the details
                $sql = "SELECT * FROM credit_invoices WHERE ordnum = '$ord[$key]'";
                $invRslt = db_exec($sql) or errDie("Unable to access database.");
                if (pg_numrows ($invRslt) < 1) {
	        	return "<li class=err> - Invalid Invoice Number.";
        	}
                $inv = pg_fetch_array($invRslt);

                # Get debt invoice info
                $sql = "SELECT amount,terms FROM debtors WHERE ordnum ='$ord[$key]'";
                $dtRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	        if (pg_numrows ($dtRslt) < 1) {
		        return "<li class=err>Invalid Invoice Number.";
	        }
                $dt = pg_fetch_array($dtRslt);

                foreach($inv as $keys => $values){
                        $$keys = $values;
                }

                $pay .= "<tr class='bg-odd'>
                                <td><input type=hidden size=20 name=ord[] value='$ord[$key]'>$cusname</td>
                                <td align=center>$orddate</td>
                                <td align=center>$invdate</td>
                                <td align=center>".CUR." $dt[amount]</td>
                                <td align=center>$dt[terms] days</td>
                                <td align=center>".CUR." <input type=text name='paidamt[]' size=7></td>
                                <td align=center>$paid</td>
                        </tr>";
        }

        $det =
        "<center>
        <h3>Add Multiple Debtors Payments to batch file</h3>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=confirm>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Customer Name</th><th>Order Date</th><th>Invoice Date</th><th>Outstanding Amount</th><th>Terms</th><th>Amount Paid</th><th>Account Paid</th></tr>
        $pay
        <tr><td><br></td></tr>
        <tr><td align=right colspan=6><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right colspan=1><input type=submit value='Confirm Transactions &raquo'></td></tr>
        </form></table>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='debtors-view.php'>View Debtors</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
                <tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
        </table>";

	return $det;
}

# Confirm
function confirm($_POST)
{
        # Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        foreach($ord as $key => $value){
                $v->isOk ($ord[$key], "num", 1, 50, "Invalid order No. [$key]");
                $v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid. [$key]");
                $v->isOk ($accpaid[$key], "num", 1, 255, "Invalid account paid to. [$key]");
        }

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

        # connect to core
        core_Connect();

        $pay = "";
        $tot = 0;
        # Debtors
        foreach($ord as $key => $value){
                db_connect();
                # Get all the details
                $sql = "SELECT * FROM credit_invoices WHERE ordnum = '$ord[$key]'";
                $invRslt = db_exec($sql) or errDie("Unable to access database.");
                if (pg_numrows ($invRslt) < 1) {
	        	return "<li class=err> - Invalid ord number $ord[$key].";
        	}
                $inv = pg_fetch_array($invRslt);

                # Get debt invoice info
                $sql = "SELECT * FROM debtors WHERE ordnum ='$ord[$key]'";
                $dtRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	        if (pg_numrows ($dtRslt) < 1) {
		        return "<li class=err>Invalid Invoice Number.";
	        }
                $dt = pg_fetch_array($dtRslt);

                foreach($inv as $keys => $values){
                        $$keys = $values;
                }

                # get paid account name
                core_connect();
                $sql = "SELECT accname FROM accounts WHERE accid = '$accpaid[$key]'";
                $accRslt = db_exec($sql);
                $acc = pg_fetch_array($accRslt);

                foreach($inv as $keys => $values){
                        $$keys = $values;
                }

                $pay .= "<tr class='bg-odd'>
                                <td><input type=hidden size=20 name=ord[] value='$ord[$key]'>$cusname</td>
                                <td align=center>$orddate</td>
                                <td align=center>$invdate</td>
                                <td align=center>".CUR." $dt[amount]</td>
                                <td align=center>$dt[terms] days</td>
                                <td align=center>".CUR." <input type=hidden name='paidamt[]' value='$paidamt[$key]'>$paidamt[$key]</td>
                                <td align=center><input type=hidden name='accpaid[]' value='$accpaid[$key]'>$acc[accname]</td>
                            </tr>";

                $tot += $paidamt[$key];
        }

        $confirm =
        "<center>
        <h3>Add Multiple Debtors Payments to batch file</h3>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Customer Name</th><th>Order Date</th><th>Invoice Date</th><th>Outstanding Amount</th><th>Terms</th><th>Amount Paid</th><th>Account Paid</th></tr>
        $pay
        <tr><td><br></td></tr>
        <tr class='bg-even'><td colspan=5>Total Amount Received</td><td colspan=2>".CUR." ".round($tot, 2)."</td></tr>
        <tr><td align=right colspan=6><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right colspan=1><input type=submit value='Confirm &raquo'></td></tr>
        </form></table>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='debtors-view.php'>View Debtors</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
                <tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
        </table>";

	return $confirm;
}

# Write
function write($_POST)
{
        # Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        foreach($ord as $key => $value){
                $v->isOk ($ord[$key], "num", 1, 50, "Invalid order No. [$key]");
                $v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid.[$key]");
                $v->isOk ($accpaid[$key], "num", 1, 255, "Invalid account paid to.[$key]");
        }

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

        $pay = "";
        # Debtors
        foreach($ord as $key => $value){
                db_connect();
                # Get all the details
                $sql = "SELECT * FROM credit_invoices WHERE ordnum = '$ord[$key]'";
                $invRslt = db_exec($sql) or errDie("Unable to access database.");
                if (pg_numrows ($invRslt) < 1) {
	        	return "<li class=err> - Invalid ord number $ord[$key].";
        	}
                $inv = pg_fetch_array($invRslt);

                # Get debt invoice info
                $sql = "SELECT * FROM debtors WHERE ordnum ='$ord[$key]'";
                $dtRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	        if (pg_numrows ($dtRslt) < 1) {
		        return "<li class=err>Invalid Invoice Number.";
	        }
                $dt = pg_fetch_array($dtRslt);

                foreach($inv as $keys => $values){
                        $$keys = $values;
                }

                # get paid account name
                core_connect();
                $sql = "SELECT accname FROM accounts WHERE accid = '$accpaid[$key]'";
                $accRslt = db_exec($sql);
                $acc = pg_fetch_array($accRslt);

                foreach($inv as $keys => $values){
                        $$keys = $values;
                }

                db_connect();
                $sql = "INSERT INTO debtors_batch(paidamt, accpaid) VALUES('$paidamt[$key]', '$accpaid[$key]')";
                $batRslt = db_exec($sql) or errDie("Unable to update debtors information in Cubit.",SELF);

                # reduce the money that has been paid
                # $sql = "UPDATE debtors SET amount = (amount - cast(float8 '$paidamt[$key]' as numeric)) WHERE ordnum = '$ord[$key]'";
                # $payRslt = db_exec($sql) or errDie("Unable to update debtors information in Cubit.",SELF);

                $pay .= "<tr class='bg-odd'>
                                <td><input type=hidden size=20 name=ord[] value='$ord[$key]'>$cusname</td>
                                <td align=center>$orddate</td>
                                <td align=center>$invdate</td>
                                <td align=center>".CUR." $dt[amount]</td>
                                <td align=center>$dt[terms] days</td>
                                <td align=center>".CUR." $paidamt[$key]</td>
                                <td align=center>$acc[accname]</td>
                            </tr>";
        }

        $confirm =
        "<center>
        <h3>Debtors Payments have been added to batch file</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><th>Customer Name</th><th>Order Date</th><th>Invoice Date</th><th>Outstanding Amount</th><th>Terms</th><th>Amount Paid</th><th>Account Paid</th></tr>
                $pay
        </table>
        <br><br><br>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='debtors-view.php'>View Debtors</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
                <tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
        </table>";

	return $confirm;
}
?>
