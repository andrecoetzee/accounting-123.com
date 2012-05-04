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
                case "writerem":
                        $OUTPUT = writerem($_POST);
        		break;
                default:
			if(isset($_POST['proc'])){
                                # Process
                                $OUTPUT = det($_POST);
                        }elseif(isset($_POST['rem'])){
                                # Remove
                                $OUTPUT = rembat($_POST);
                        }else{
                               $OUTPUT = "<li class=err> Invalid use of module.";
                        }
	}
} else {
        if(isset($_POST['proc'])){
                # Process
                $OUTPUT = det($_POST);
        }elseif(isset($_POST['rem'])){
                # Remove
                $OUTPUT = rembat($_POST);
        }else{
                $OUTPUT = "<li class=err> Invalid use of module.";
        }
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
                        $v->isOk ($ord[$key], "num", 1, 50, "Invalid batch No.");
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

        $pay = "";
        # Debtors
        foreach($ord as $key => $value){
                db_connect();
                $sql = "SELECT * FROM debtors_batch WHERE batchid = '$ord[$key]'";
                $batRslt = db_exec($sql);
                $bat = pg_fetch_array($batRslt);

                # Get all the details
                $sql = "SELECT cusname, orddate, invdate FROM credit_invoices WHERE ordnum = '$bat[ordnum]'";
                $invRslt = db_exec($sql) or errDie("Unable to access database.");
                if (pg_numrows ($invRslt) < 1) {
	        	return "<li class=err> - Invalid Invoice Number. 1";
        	}
                $inv = pg_fetch_array($invRslt);

                # Get debt invoice info
                $sql = "SELECT amount,terms FROM debtors WHERE ordnum ='$bat[ordnum]'";
                $dtRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	        if (pg_numrows ($dtRslt) < 1) {
		        return "<li class=err> - Invalid Invoice Number. 2";
	        }
                $dt = pg_fetch_array($dtRslt);

                foreach($inv as $keys => $values){
                        $$keys = $values;
                }

                # get paid account name
                core_connect();
                $sql = "SELECT accname FROM accounts WHERE accid = '$bat[accpaid]'";
                $accRslt = db_exec($sql);
                $acc = pg_fetch_array($accRslt);

                foreach($inv as $keys => $values){
                        $$keys = $values;
                }

                $pay .= "<tr class='bg-odd'>
                                <td><input type=hidden size=20 name=ord[] value='$bat[ordnum]'>
                                <input type=hidden size=20 name=bat[] value='$bat[batchid]'>$cusname</td>
                                <td align=center>$orddate</td>
                                <td align=center>$invdate</td>
                                <td align=center>".CUR." $dt[amount]</td>
                                <td align=center>$dt[terms] days</td>
                                <td align=center>".CUR." <input type=text name='paidamt[]' size=7 value='$bat[paidamt]'></td>
                                <td align=center><input type=hidden name='accpaid[]' value='$bat[accpaid]'>$acc[accname]</td>
                        </tr>";
        }

        $det =
        "<center>
        <h3>Process Debtors Batch Entries</h3>
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
                <tr bgcolor='#88BBFF'><td><a href='debtors-batch-view.php'>View Debtors batch</a></td></tr>
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
                $v->isOk ($bat[$key], "num", 1, 50, "Invalid batch No. [$key]");
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
                                <td><input type=hidden size=20 name=ord[] value='$ord[$key]'>
                                <input type=hidden size=20 name=bat[] value='$bat[$key]'>$cusname</td>
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
        <h3>Process Debtors Batch Entries</h3>
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
                <tr bgcolor='#88BBFF'><td><a href='debtors-batch-view.php'>View Debtors batch</a></td></tr>
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
                $v->isOk ($bat[$key], "num", 1, 50, "Invalid batch No. [$key]");
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

                $sql = "UPDATE debtors_batch SET proc='yes' WHERE batchid = '$bat[$key]'";
                $Rs = db_exec($sql);

                # get debtors account
                $debtorsacc = gethook("accnum", "salesacc", "name", "Debtors");

				$refnum = getrefnum();
/*refnum*/

                # credit acc used debit acc paid
                writetrans($accpaid[$key], $debtorsacc, date("d-m-Y"), $refnum, $paidamt[$key],  "Payment received from debtor $cusname.");

                $pay .= "<tr class='bg-odd'>
                                <td>$cusname</td>
                                <td align=center>$orddate</td>
                                <td align=center>$invdate</td>
                                <td align=center>".CUR." $dt[amount]</td>
                                <td align=center>$dt[terms] days</td>
                                <td align=center>".CUR." $paidamt[$key]</td>
                                <td align=center>$acc[accname]</td>
                        </tr>";
        }

        $write =
        "<center>
        <h3>Debtors Batch Entries Processed</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><th>Customer Name</th><th>Order Date</th><th>Invoice Date</th><th>Outstanding Amount</th><th>Terms</th><th>Amount Paid</th><th>Account Paid</th></tr>
                $pay
        </table>
        <br><br><br>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='debtors-batch-view.php'>View Debtors batch</a></td></tr>
                <tr bgcolor='#88BBFF'><td><a href='debtors-view.php'>View Debtors</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
                <tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
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
function rembat($_POST)
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
                        $v->isOk ($ord[$key], "num", 1, 50, "Invalid Batch No.");
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

        # connect to core
        core_Connect();

        $pay = "";
        # Debtors
        foreach($ord as $key => $value){
                db_connect();
                $sql = "SELECT * FROM debtors_batch WHERE batchid = '$ord[$key]'";
                $batRslt = db_exec($sql);
                $bat = pg_fetch_array($batRslt);

                # Get all the details
                $sql = "SELECT cusname, orddate, invdate FROM credit_invoices WHERE ordnum = '$bat[ordnum]'";
                $invRslt = db_exec($sql) or errDie("Unable to access database.");
                if (pg_numrows ($invRslt) < 1) {
	        	return "<li class=err> - Invalid Invoice Number. 1";
        	}
                $inv = pg_fetch_array($invRslt);

                # Get debt invoice info
                $sql = "SELECT amount,terms FROM debtors WHERE ordnum ='$bat[ordnum]'";
                $dtRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	        if (pg_numrows ($dtRslt) < 1) {
		        return "<li class=err> - Invalid Invoice Number. 2";
	        }
                $dt = pg_fetch_array($dtRslt);

                foreach($inv as $keys => $values){
                        $$keys = $values;
                }

                # get paid account name
                core_connect();
                $sql = "SELECT accname FROM accounts WHERE accid = '$bat[accpaid]'";
                $accRslt = db_exec($sql);
                $acc = pg_fetch_array($accRslt);

                foreach($inv as $keys => $values){
                        $$keys = $values;
                }

                $pay .= "<tr class='bg-odd'>
                                <td><input type=hidden name=bats[] value='$bat[batchid]'>$cusname</td>
                                <td align=center>$orddate</td>
                                <td align=center>$invdate</td>
                                <td align=center>".CUR." $dt[amount]</td>
                                <td align=center>$dt[terms] days</td>
                                <td align=center>".CUR." $bat[paidamt]</td>
                                <td align=center>$acc[accname]</td>
                            </tr>";
        }

        $det =
        "<center>
        <h3>Remove Debtors Batch Entries</h3>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=writerem>
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

# Write
function writerem($_POST)
{
        # Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        foreach($bats as $key => $value){
                $v->isOk ($bats[$key], "num", 1, 50, "Invalid batch No.");
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
        # Debtors
        foreach($bats as $key => $value){
                db_connect();
                $sql = "SELECT * FROM debtors_batch WHERE batchid = '$bats[$key]'";
                $batRslt = db_exec($sql);
                $bat = pg_fetch_array($batRslt);

                # Get all the details
                $sql = "SELECT cusname, orddate, invdate FROM credit_invoices WHERE ordnum = '$bat[ordnum]'";
                $invRslt = db_exec($sql) or errDie("Unable to access database.");
                if (pg_numrows ($invRslt) < 1) {
	        	return "<li class=err> - Invalid Invoice Number. 1";
        	}
                $inv = pg_fetch_array($invRslt);

                # Get debt invoice info
                $sql = "SELECT amount,terms FROM debtors WHERE ordnum ='$bat[ordnum]'";
                $dtRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	        if (pg_numrows ($dtRslt) < 1) {
		        return "<li class=err> - Invalid Invoice Number. 2";
	        }
                $dt = pg_fetch_array($dtRslt);

                foreach($inv as $keys => $values){
                        $$keys = $values;
                }

                # get paid account name
                core_connect();
                $sql = "SELECT accname FROM accounts WHERE accid = '$bat[accpaid]'";
                $accRslt = db_exec($sql);
                $acc = pg_fetch_array($accRslt);

                foreach($inv as $keys => $values){
                        $$keys = $values;
                }

                db_connect();
                $sql = "DELETE FROM debtors_batch WHERE batchid = '$bat[batchid]'";
                $Rs = db_exec($sql);

                $pay .= "<tr class='bg-odd'>
                                <td>$cusname</td>
                                <td align=center>$orddate</td>
                                <td align=center>$invdate</td>
                                <td align=center>".CUR." $dt[amount]</td>
                                <td align=center>$dt[terms] days</td>
                                <td align=center>".CUR." $bat[paidamt]</td>
                                <td align=center>$acc[accname]</td>
                            </tr>";
        }

        $det = "<center><h3>Debtors Batch Entries Removed</h3>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                        <tr><th>Customer Name</th><th>Order Date</th><th>Invoice Date</th><th>Outstanding Amount</th><th>Terms</th><th>Amount Paid</th><th>Account Paid</th></tr>
                        $pay
                </table>
                <br><br><br>
                <table border=0 cellpadding='2' cellspacing='1' width=15%>
                        <tr><th>Quick Links</th></tr>
                        <tr bgcolor='#88BBFF'><td><a href='debtors-batch-view.php'>View Debtors batch</a></td></tr>
                        <tr bgcolor='#88BBFF'><td><a href='debtors-view.php'>View Debtors</a></td></tr>
                        <script>document.write(getQuicklinkSpecial());</script>
                        <tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
                </table>";

	return $det;
}
?>
