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
##
# trans-new.php :: Multiple debit-credit Transactions
##

# get settings
require("settings.php");
require("core-settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {

                case "write":
                        $OUTPUT = write($HTTP_POST_VARS);
        		break;
                case "confirm":
                        $OUTPUT = confirm($HTTP_POST_VARS);
        		break;
                case "writebat":
                        $OUTPUT = writebat($HTTP_POST_VARS);
        		break;
                case "confirmbat":
                        $OUTPUT = confirmbat($HTTP_POST_VARS);
        		break;
                default:
			if(isset($proc)){
                                # Process
                                $OUTPUT = det($HTTP_POST_VARS);
                        }elseif(isset($bat)){
                                # Remove
                                $OUTPUT = detbat($HTTP_POST_VARS);
                        }else{
                               $OUTPUT = "<li class=err> Invalid use of module.";
                        }
	}
} else {
        if(isset($HTTP_POST_VARS['proc'])){
                # Process
                $OUTPUT = det($HTTP_POST_VARS);
        }elseif(isset($HTTP_POST_VARS['bat'])){
                # Remove
                $OUTPUT = detbat($HTTP_POST_VARS);
        }else{
                $OUTPUT = "<li class=err> Invalid use of module. $HTTP_POST_VARS['bat'] - $HTTP_POST_VARS['proc']";
        }
}

# get templete
require("template.php");

# Confirm
function det($HTTP_POST_VARS)
{
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
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

                $pay .= "<tr bgcolor=".TMPL_tblDataColor1.">
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
        <h3>Process Multiple Debtors Payments</h3>
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
function confirm($HTTP_POST_VARS)
{
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
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

                $pay .= "<tr bgcolor=".TMPL_tblDataColor1.">
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
        <h3>Process Multiple Debtors Payments</h3>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Customer Name</th><th>Order Date</th><th>Invoice Date</th><th>Outstanding Amount</th><th>Terms</th><th>Amount Paid</th><th>Account Paid</th></tr>
        $pay
        <tr><td><br></td></tr>
        <tr bgcolor=".TMPL_tblDataColor2."><td colspan=5><b>Total Amount Received</b></td><td colspan=2><b>".CUR." ".sprintf("%01.2f", round($tot, 2))."</b></td></tr>
        <tr><td><br></td></tr>
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
function write($HTTP_POST_VARS)
{
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
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
                # reduce the money that has been paid
                $sql = "UPDATE debtors SET amount = (amount - cast(float8 '$paidamt[$key]' as numeric)) WHERE ordnum = '$ord[$key]'";
                $payRslt = db_exec($sql) or errDie("Unable to update debtors information in Cubit.",SELF);

                # get debtors account
                $debtorsacc = gethook("accnum", "salesacc", "name", "Debtors");

				$refnum = getrefnum();
/*refnum*/

                # credit acc used debit acc paid
                writetrans($accpaid[$key], $debtorsacc, date("d-m-Y"), $refnum, $paidamt[$key],  "Payment received from debtor $cusname.");

                $pay .= "<tr bgcolor=".TMPL_tblDataColor1.">
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
        <h3>Debtors Payments Processed</h3>
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
		return $write;
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

# Confirm
function detbat($HTTP_POST_VARS)
{
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
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

        # db connect
        db_conn(PRD_DB);

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

                $pay .= "<tr bgcolor=".TMPL_tblDataColor1.">
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
        <input type=hidden name=key value=confirmbat>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Customer Name</th><th>Order Date</th><th>Invoice Date</th><th>Outstanding Amount</th><th>Terms</th><th>Amount Paid</th><th>Account Paid</th></tr>
        $pay
        <tr><td><br></td></tr>
        <tr><td align=right colspan=6><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right colspan=1><input type=submit value='Confirm Transactions &raquo'></td></tr>
        </form></table>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='debtors-batch-view.php'>View Debtors batch</a></td></tr>
                <tr bgcolor='#88BBFF'><td><a href='debtors-view.php'>View Debtors</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
                <tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
        </table>";

	return $det;
}

# Confirm
function confirmbat($HTTP_POST_VARS)
{
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
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

                $pay .= "<tr bgcolor=".TMPL_tblDataColor1.">
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
        <input type=hidden name=key value=writebat>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Customer Name</th><th>Order Date</th><th>Invoice Date</th><th>Outstanding Amount</th><th>Terms</th><th>Amount Paid</th><th>Account Paid</th></tr>
        $pay
        <tr><td><br></td></tr>
        <tr bgcolor=".TMPL_tblDataColor2."><td colspan=5>Total Amount Received</td><td colspan=2>".CUR." ".round($tot, 2)."</td></tr>
        <tr><td align=right colspan=6><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right colspan=1><input type=submit value='Confirm &raquo'></td></tr>
        </form></table>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='debtors-batch-view.php'>View Debtors batch</a></td></tr>
                <tr bgcolor='#88BBFF'><td><a href='debtors-view.php'>View Debtors</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
                <tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
        </table>";

	return $confirm;
}

# Write
function writebat($HTTP_POST_VARS)
{
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
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
                $sql = "INSERT INTO debtors_batch(ordnum, paidamt, accpaid) VALUES('$ord[$key]', '$paidamt[$key]', '$accpaid[$key]')";
                $batRslt = db_exec($sql) or errDie("Unable to update debtors information in Cubit.",SELF);

                # reduce the money that has been paid
                # $sql = "UPDATE debtors SET amount = (amount - cast(float8 '$paidamt[$key]' as numeric)) WHERE ordnum = '$ord[$key]'";
                # $payRslt = db_exec($sql) or errDie("Unable to update debtors information in Cubit.",SELF);

                $pay .= "<tr bgcolor=".TMPL_tblDataColor1.">
                                <td><input type=hidden size=20 name=ord[] value='$ord[$key]'>$cusname</td>
                                <td align=center>$orddate</td>
                                <td align=center>$invdate</td>
                                <td align=center>".CUR." $dt[amount]</td>
                                <td align=center>$dt[terms] days</td>
                                <td align=center>".CUR." $paidamt[$key]</td>
                                <td align=center>$acc[accname]</td>
                            </tr>";
        }

        $confirm ="<center><h3>Debtors Payments have been added to batch file</h3>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                        <tr><th>Customer Name</th><th>Order Date</th><th>Invoice Date</th><th>Outstanding Amount</th><th>Terms</th><th>Amount Paid</th><th>Account Paid</th></tr>
                        $pay
                </table>
                <br><br><br>
                <table border=0 cellpadding='2' cellspacing='1' width=15%>
                        <tr><th>Quick Links</th></tr>
                        <tr bgcolor='#88BBFF'><td><a href='debtors-view.php'>View Debtors</a></td></tr>
                        <tr bgcolor='#88BBFF'><td><a href='debtors-batch-view.php'>View Debtors batch</a></td></tr>
                        <script>document.write(getQuicklinkSpecial());</script>
                        <tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
                </table>";

	return $confirm;
}
?>
