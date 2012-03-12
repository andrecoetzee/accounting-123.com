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
                case "confirmrem":
                        $OUTPUT = confirmrem($_POST);
        		break;
                default:
			if(isset($_POST["proc"])){
                                # Process
                                $OUTPUT = det($_POST);
                        }elseif(isset($_POST["rem"])){
                                # Remove
                                $OUTPUT = detrem($_POST);
                        }else{
                               $OUTPUT = "<li class=err> Invalid use of module.";
                        }
	}
} else {
        if(isset($_POST["proc"])){
                # Process
                $OUTPUT = det($_POST);
        }elseif(isset($_POST["rem"])){
                # Remove
                $OUTPUT = detrem($_POST);
        }else{
                $OUTPUT = "<li class=err> Invalid use of module w.";
        }
}

# get templete
require("template.php");

# Details
function det($_POST)
{
        # Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        if(isset($bat)){
                foreach($bat as $key => $value){
                        $v->isOk ($bat[$key], "num", 1, 50, "Invalid Batch ID.");
                }
        }else{
                return "<li> - No Batch entries selected. Please select at least one entry.";
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

        # Creditors
        $proc = "";
        foreach($bat as $key => $value){
                core_connect();

                # get bach info
                $sql = "SELECT * FROM credit_batch WHERE batchid = '$bat[$key]'";
                $batRslt = db_exec($sql);
                if(pg_numrows($batRslt) < 1){
                        return "<li class=err> Invalid Batch ID.";
                }
                $batch = pg_fetch_array($batRslt);

                $sql = "SELECT * FROM purchases WHERE purchid='$batch[purchid]'";
                $ctRslt = db_exec($sql);
                if(pg_numrows($ctRslt) < 1){
                        return "<li class=err> Invalid Purchase No.";
                }
                $ct = pg_fetch_array($ctRslt);

                foreach($ct as $key => $value){
                        $$key = $value;
                }

                # get credit infomation
                $sql = "SELECT * FROM credit_purch WHERE purchid = '$purchid' AND amount > 0";
                $ctRslt = db_exec($sql);
                $ct = pg_fetch_array($ctRslt);

                # get used account name
                $sql = "SELECT accname FROM accounts WHERE accid = '$usedacc'";
                $accRslt = db_exec($sql);
                $acc = pg_fetch_array($accRslt);
                $usedaccname = $acc['accname'];

                # get paid account name
                $sql = "SELECT accname FROM accounts WHERE accid = '$paidacc'";
                $accRslt = db_exec($sql);
                $acc = pg_fetch_array($accRslt);
                $paidaccname = $acc['accname'];

                $proc .= "<tr bgcolor=".TMPL_tblDataColor1.">
                                <input type=hidden name=pay[] value='$purchid'>
                                <input type=hidden name=bat[] value='$batch[batchid]'>
                                <td>$retailer</td><td>$itemname</td>
                                <td>$descript</td><td>$quantity</td>
                                <td>".CUR." $ct[amount]</td><td>".CUR." <input type=text name=paidamt[] size=7 value='$batch[amount]'>
                                </td><td>$usedaccname</td><td>$paidaccname</td>
                        </tr>";
        }

        $det =
        "<center>
        <h3>Process Multiple Creditors Batch Entries</h3>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=confirm>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><th>Retailer</th><th>Item Name</th><th>Description</th><th>Quantity</th><th>Outstanding amount</th><th>Amount Paid</th><th>Account used</th><th>Account paid</th></tr>
                $proc
                <tr><td><br></td></tr>
                <tr><td align=right colspan=6><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right colspan=1><input type=submit value='Confirm Transactions &raquo'></td></tr>
        </form></table>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='creditors-view.php'>View Creditors</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
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
        foreach($pay as $key => $value){
                $v->isOk ($pay[$key], "num", 1, 50, "Invalid purchase No.");
                $v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid. [$key]");
                $v->isOk ($bat[$key], "float", 1, 20, "Invalid batch ID. [$key]");
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

        $tot = 0;
        # Creditors
        $pays = "";
        foreach($pay as $key => $value){
                core_connect();

                # get purchase info
                $sql = "SELECT * FROM purchases WHERE purchid = '$pay[$key]'";
                $purchRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve purchase details from database.", SELF);

                if (pg_numrows($purchRslt) < 1) {
                        $OUTPUT = "<li clss=err>Invalid purchase ID.";
                        return $OUTPUT;
                }
                $purch = pg_fetch_array($purchRslt);

                # get credit purchase info
                $sql = "SELECT amount FROM credit_purch WHERE purchid = '$pay[$key]'";
                $ctpRslt = db_exec($sql);
                $ctp = pg_fetch_array($ctpRslt);

                # get account name for account paid
                $accRslt = get("core", "accname", "accounts", "accid",$purch['paidacc']);
                $acc= pg_fetch_array($accRslt);
                $paidacc =  $acc['accname'];

                # get account name for account used
                $accRslt = get("core", "accname", "accounts", "accid",$purch['usedacc']);
                $acc= pg_fetch_array($accRslt);
                $usedacc =  $acc['accname'];

                $pays .= "<tr bgcolor=".TMPL_tblDataColor1.">
                                <input type=hidden name=pay[] value='$pay[$key]'>
                                <input type=hidden name=bat[] value='$bat[$key]'>
                                <td>$purch[retailer]</td>
                                <td>$purch[itemname]</td>
                                <td>$purch[descript]</td>
                                <td>$purch[quantity]</td>
                                <td>".CUR." $ctp[amount]</td>
                                <td>".CUR." <input type=hidden name='paidamt[]' value='$paidamt[$key]'>$paidamt[$key]</td>
                                <td>$usedacc</td>
                                <td>$paidacc</td>
                        </tr>";
                $tot += $paidamt[$key];
        }

        $confirm = "<center>
        <h3>Process Multiple Creditors Batch Entries</h3>
        <h4>Confirm Entry</h4>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><th>Retailer</th><th>Item Name</th><th>Description</th><th>Quantity</th><th>Outstanding amount</th><th>Amount Paid</th><th>Account used</th><th>Account paid</th></tr>
                $pays
                <tr><td><br></td></tr>
                <tr bgcolor=".TMPL_tblDataColor2."><td colspan=5><b>Total Amount Paid</b></td><td colspan=2><b>".CUR." ".sprintf("%01.2f", round($tot, 2))."</b></td></tr>
                <tr><td align=right colspan=6><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right colspan=1><input type=submit value='Confirm &raquo'></td></tr>
        </form></table>
        <br><br><br>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='creditors-view.php'>View Creditors</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
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
        foreach($pay as $key => $value){
                $v->isOk ($pay[$key], "num", 1, 50, "Invalid purchase No.");
                $v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid. [$key]");
                $v->isOk ($bat[$key], "float", 1, 20, "Invalid batch ID. [$key]");
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

        # Get creditors account
        $creditacc = gethook("accnum", "pchsacc", "name", "Creditors");

        $tot = 0;
        # Creditors
        $pays = "";
        foreach($pay as $key => $value){
                core_connect();

                # get purchase info
                $sql = "SELECT * FROM purchases WHERE purchid = '$pay[$key]'";
                $purchRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve purchase details from database.", SELF);

                if (pg_numrows($purchRslt) < 1) {
                        $OUTPUT = "<li clss=err>Invalid purchase ID.";
                        return $OUTPUT;
                }
                $purch = pg_fetch_array($purchRslt);

                # get credit purchase info
                $sql = "SELECT amount FROM credit_purch WHERE purchid = '$pay[$key]'";
                $ctpRslt = db_exec($sql);
                $ctp = pg_fetch_array($ctpRslt);

                # get account name for account paid
                $accRslt = get("core", "accname", "accounts", "accid",$purch['paidacc']);
                $acc= pg_fetch_array($accRslt);
                $paidacc =  $acc['accname'];

                # get account name for account used
                $accRslt = get("core", "accname", "accounts", "accid",$purch['usedacc']);
                $acc= pg_fetch_array($accRslt);
                $usedacc =  $acc['accname'];

                $pays .= "<tr bgcolor=".TMPL_tblDataColor1.">
                                <td>$purch[retailer]</td>
                                <td>$purch[itemname]</td>
                                <td>$purch[descript]</td>
                                <td>$purch[quantity]</td>
                                <td>".CUR." $ctp[amount]</td>
                                <td>".CUR." $paidamt[$key]</td>
                                <td>$usedacc</td>
                                <td>$paidacc</td>
                        </tr>";

                # reduce the money that has been paid
                $sql = "UPDATE credit_purch SET amount = (amount - cast(float8 '$paidamt[$key]' as numeric)) WHERE purchid = '$pay[$key]'";
                $payRslt = db_exec($sql) or errDie("Unable to update credit purchases table.",SELF);

				$refnum = getrefnum(date('d-m-Y'));

                writetrans($creditacc, $purch['usedacc'], date('d-m-Y'), $refnum, $paidamt[$key], 'Pay Purchase Credit.');

                # update creditors batch
                core_connect();
                $sql = "UPDATE credit_batch SET proc = 'yes' WHERE batchid = '$bat[$key]'";
                $batRslt = db_exec($sql) or errDie("Unable to update creditors batch.",SELF);

                $tot += $paidamt[$key];
        }

        $write = "<center>
        <h3>Multiple Credits Batch Entries Processed</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><th>Retailer</th><th>Item Name</th><th>Description</th><th>Quantity</th><th>Outstanding amount</th><th>Amount Paid</th><th>Account used</th><th>Account paid</th></tr>
                $pays
                <tr><td><br></td></tr>
                <tr bgcolor=".TMPL_tblDataColor2."><td colspan=5><b>Total Amount Paid</b></td><td colspan=2><b>".CUR." ".sprintf("%01.2f", round($tot, 2))."</b></td></tr>
        </table>
        <br><br><br>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='creditors-view.php'>View Creditors</a></td></tr>
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

# Details
function detrem($_POST)
{
        # Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        if(isset($bat)){
                foreach($bat as $key => $value){
                        $v->isOk ($bat[$key], "num", 1, 50, "Invalid Batch ID.");
                }
        }else{
                return "<li> - No Batch entries selected. Please select at least one entry.";
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

        # Creditors
        $proc = "";
        foreach($bat as $key => $value){
                core_connect();

                # get bach info
                $sql = "SELECT * FROM credit_batch WHERE batchid = '$bat[$key]'";
                $batRslt = db_exec($sql);
                if(pg_numrows($batRslt) < 1){
                        return "<li class=err> Invalid Batch ID.";
                }
                $batch = pg_fetch_array($batRslt);

                $sql = "SELECT * FROM purchases WHERE purchid='$batch[purchid]'";
                $ctRslt = db_exec($sql);
                if(pg_numrows($ctRslt) < 1){
                        return "<li class=err> Invalid Purchase No.";
                }
                $ct = pg_fetch_array($ctRslt);

                foreach($ct as $key => $value){
                        $$key = $value;
                }

                # get credit infomation
                $sql = "SELECT * FROM credit_purch WHERE purchid = '$purchid' AND amount > 0";
                $ctRslt = db_exec($sql);
                $ct = pg_fetch_array($ctRslt);

                # get used account name
                $sql = "SELECT accname FROM accounts WHERE accid = '$usedacc'";
                $accRslt = db_exec($sql);
                $acc = pg_fetch_array($accRslt);
                $usedaccname = $acc['accname'];

                # get paid account name
                $sql = "SELECT accname FROM accounts WHERE accid = '$paidacc'";
                $accRslt = db_exec($sql);
                $acc = pg_fetch_array($accRslt);
                $paidaccname = $acc['accname'];

                $proc .= "<tr bgcolor=".TMPL_tblDataColor1.">
                                <input type=hidden name=pay[] value='$purchid'>
                                <input type=hidden name=bat[] value='$batch[batchid]'>
                                <td>$retailer</td>
                                <td>$itemname</td>
                                <td>$descript</td>
                                <td>$quantity</td>
                                <td>".CUR." $ct[amount]</td>
                                <td>".CUR." <input type=hidden name=paidamt[] value='$batch[amount]'>$batch[amount]</td>
                                <td>$usedaccname</td>
                                <td>$paidaccname</td>
                        </tr>";
        }

        $det =
        "<center>
        <h3>Cancel Multiple Creditors Batch Entries</h3>
        <form action='".SELF."' method=post>
        <h4>Confirm Entry</h4>
        <input type=hidden name=key value=writerem>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><th>Retailer</th><th>Item Name</th><th>Description</th><th>Quantity</th><th>Outstanding amount</th><th>Amount Paid</th><th>Account used</th><th>Account paid</th></tr>
                $proc
                <tr><td><br></td></tr>
                <tr><td align=right colspan=6><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right colspan=1><input type=submit value='Confirm Transactions &raquo'></td></tr>
        </form></table>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='creditors-view.php'>View Creditors</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
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
        foreach($pay as $key => $value){
                $v->isOk ($pay[$key], "num", 1, 50, "Invalid purchase No.");
                $v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid. [$key]");
                $v->isOk ($bat[$key], "float", 1, 20, "Invalid batch ID. [$key]");
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

        # Get creditors account
        $creditacc = gethook("accnum", "pchsacc", "name", "Creditors");

        $tot = 0;
        # Creditors
        $pays = "";
        foreach($pay as $key => $value){
                core_connect();

                # get purchase info
                $sql = "SELECT * FROM purchases WHERE purchid = '$pay[$key]'";
                $purchRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve purchase details from database.", SELF);

                if (pg_numrows($purchRslt) < 1) {
                        $OUTPUT = "<li clss=err>Invalid purchase ID.";
                        return $OUTPUT;
                }
                $purch = pg_fetch_array($purchRslt);

                # get credit purchase info
                $sql = "SELECT amount FROM credit_purch WHERE purchid = '$pay[$key]'";
                $ctpRslt = db_exec($sql);
                $ctp = pg_fetch_array($ctpRslt);

                # get account name for account paid
                $accRslt = get("core", "accname", "accounts", "accid",$purch['paidacc']);
                $acc= pg_fetch_array($accRslt);
                $paidacc =  $acc['accname'];

                # get account name for account used
                $accRslt = get("core", "accname", "accounts", "accid",$purch['usedacc']);
                $acc= pg_fetch_array($accRslt);
                $usedacc =  $acc['accname'];

                $pays .= "<tr bgcolor=".TMPL_tblDataColor1.">
                                <td>$purch[retailer]</td>
                                <td>$purch[itemname]</td>
                                <td>$purch[descript]</td>
                                <td>$purch[quantity]</td>
                                <td>".CUR." $ctp[amount]</td>
                                <td>".CUR." $paidamt[$key]</td>
                                <td>$usedacc</td>
                                <td>$paidacc</td>
                        </tr>";

                # remove from creditors batch
                core_connect();
                $sql = "UPDATE credit_batch SET proc = 'can' WHERE batchid = '$bat[$key]'";
                $batRslt = db_exec($sql) or errDie("Unable to update creditors batch.",SELF);

                $tot += $paidamt[$key];
        }

        $write = "<center>
        <h3>Multiple Credits Batch Entries Canceled</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><th>Retailer</th><th>Item Name</th><th>Description</th><th>Quantity</th><th>Outstanding amount</th><th>Amount Paid</th><th>Account used</th><th>Account paid</th></tr>
                $pays
                <tr><td><br></td></tr>
                <tr bgcolor=".TMPL_tblDataColor2."><td colspan=5><b>Total Amount Paid</b></td><td colspan=2><b>".CUR." ".sprintf("%01.2f", round($tot, 2))."</b></td></tr>
        </table>
        <br><br><br>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='credit-batch-view.php'>View Creditors Batch</a></td></tr>
                <tr bgcolor='#88BBFF'><td><a href='creditors-view.php'>View Creditors</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
        </table>";

	return $write;
}
?>
