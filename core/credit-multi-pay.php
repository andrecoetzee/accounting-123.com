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

# Get settings
require("settings.php");
require("core-settings.php");

# Decide what to do
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
			if(isset($HTTP_POST_VARS["proc"])){
                                # Process
                                $OUTPUT = det($HTTP_POST_VARS);
                        }elseif(isset($HTTP_POST_VARS["bat"])){
                                # Remove
                                $OUTPUT = detbat($HTTP_POST_VARS);
                        }else{
                               $OUTPUT = "<li class=err> Invalid use of module.";
                        }
	}
} else {
        if(isset($HTTP_POST_VARS["proc"])){
                # Process
                $OUTPUT = det($HTTP_POST_VARS);
        }elseif(isset($HTTP_POST_VARS["bat"])){
                # Remove
                $OUTPUT = detbat($HTTP_POST_VARS);
        }else{
                $OUTPUT = "<li class=err> Invalid use of module.";
        }
}

# Get templete
require("template.php");

# Details
function det($HTTP_POST_VARS)
{
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # Validate input
	require_lib("validate");
	$v = new  validate ();
        if(isset($pay)){
                foreach($pay as $key => $value){
                        $v->isOk ($pay[$key], "num", 1, 50, "Invalid purchase No.");
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
                                <td>$purch[retailer]</td>
                                <td>$purch[itemname]</td>
                                <td>$purch[descript]</td>
                                <td>$purch[quantity]</td>
                                <td>".CUR." $ctp[amount]</td>
                                <td>".CUR." <input type=text name=paidamt[] size=7></td>
                                <td>$usedacc</td>
                                <td>$paidacc</td>
                        </tr>";
        }

        $det =
        "<center>
        <h3>Process Multiple Credits Payments</h3>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=confirm>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><th>Retailer</th><th>Item Name</th><th>Description</th><th>Quantity</th><th>Outstanding amount</th><th>Amount Paid</th><th>Account used</th><th>Account paid</th></tr>
                $pays
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
function confirm($HTTP_POST_VARS)
{
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        foreach($pay as $key => $value){
                $v->isOk ($pay[$key], "num", 1, 50, "Invalid purchase No.");
                $v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid. [$key]");
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
        <h3>Process Multiple Credits Payments</h3>
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
function write($HTTP_POST_VARS)
{
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        foreach($pay as $key => $value){
                $v->isOk ($pay[$key], "num", 1, 50, "Invalid purchase No.");
                $v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid. [$key]");
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
                                <input type=hidden name=pay[] value='$pay[$key]'>
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

                $tot += $paidamt[$key];
        }

        $write = "<center>
        <h3>Multiple Credits Payments Processed</h3>
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
function detbat($HTTP_POST_VARS)
{
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        if(isset($pay)){
                foreach($pay as $key => $value){
                        $v->isOk ($pay[$key], "num", 1, 50, "Invalid purchase No.");
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
                                <td>$purch[retailer]</td>
                                <td>$purch[itemname]</td>
                                <td>$purch[descript]</td>
                                <td>$purch[quantity]</td>
                                <td>".CUR." $ctp[amount]</td>
                                <td>".CUR." <input type=text name=paidamt[] size=7></td>
                                <td>$usedacc</td>
                                <td>$paidacc</td>
                        </tr>";
        }

        $det =
        "<center>
        <h3>Add Multiple Credits Payments to batch</h3>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=confirmbat>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><th>Retailer</th><th>Item Name</th><th>Description</th><th>Quantity</th><th>Outstanding amount</th><th>Amount Paid</th><th>Account used</th><th>Account paid</th></tr>
                $pays
                <tr><td><br></td></tr>
                <tr><td align=right colspan=6><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right colspan=1><input type=submit value='Add Transactions &raquo'></td></tr>
        </form></table>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
                <tr><th>Quick Links</th></tr>
                <tr bgcolor='#88BBFF'><td><a href='creditors-view.php'>View Creditors</a></td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
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
        foreach($pay as $key => $value){
                $v->isOk ($pay[$key], "num", 1, 50, "Invalid purchase No.");
                $v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid. [$key]");
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
        <h3>Add Multiple Credits Payments to Batch</h3>
        <h4>Confirm Entry</h4>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=writebat>
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
function writebat($HTTP_POST_VARS)
{
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        foreach($pay as $key => $value){
                $v->isOk ($pay[$key], "num", 1, 50, "Invalid purchase No.");
                $v->isOk ($paidamt[$key], "float", 1, 20, "Invalid amount to be paid. [$key]");
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
                                <td>$purch[retailer]</td>
                                <td>$purch[itemname]</td>
                                <td>$purch[descript]</td>
                                <td>$purch[quantity]</td>
                                <td>".CUR." $ctp[amount]</td>
                                <td>".CUR." $paidamt[$key]</td>
                                <td>$usedacc</td>
                                <td>$paidacc</td>
                        </tr>";

                # Insert the batch entry
                $sql = "INSERT INTO credit_batch(purchid, amount) VALUES('$pay[$key]', '$paidamt[$key]')";
                $payRslt = db_exec($sql) or errDie("Unable to insert batch entries to database.",SELF);

                $tot += $paidamt[$key];
        }

        $write = "<center>
        <h3>Multiple Credits Payments have been added to Batch</h3>
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
