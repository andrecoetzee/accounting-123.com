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
		case "pay":
                        $OUTPUT = pay($_POST);
			break;

                default:
			$OUTPUT = confirm($_GET["purchid"]);
	}
} else {
        # Display default output
        if(isset($_GET["purchid"])){
                $OUTPUT = confirm($_GET["purchid"]);
        }else{
                $OUTPUT = confirm('none');
        }
}

# get template
require("template.php");

function confirm($purchid)
{
        # validate input
        require_lib("validate");
        $v = new  validate ();
        $v->isOk ($purchid, "num", 1, 20, "Invalid Purchase ID.");

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

        // Connect to database
        core_Connect ();

        # get purchase info
        $sql = "SELECT * FROM purchases WHERE purchid = '$purchid'";
        $purchRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve purchase details from database.", SELF);
	$numrows = pg_numrows ($purchRslt);

        if ($numrows < 1) {
		$OUTPUT = "<li clss=err>Invalid purchase ID.";
		require ("template.php");
	}
        $purch = pg_fetch_array($purchRslt);

        # get credit purchase info
        $sql = "SELECT * FROM credit_purch WHERE purchid = '$purchid'";
        $ctRslt = db_exec($sql);
        $ct = pg_fetch_array($ctRslt);

        $confirm =
        "<h3>Pay Credit</h3>
        <h4>Confirm entry</h4>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=40%>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=pay>
        <input type=hidden name=purchid value='$purch[purchid]'>";

        # get account name for account paid
        $accRslt = get("core", "accname", "accounts", "accid",$purch['paidacc']);
        $acc= pg_fetch_array($accRslt);
        $paidacc =  $acc['accname'];

        # get account name for account used
        $accRslt = get("core", "accname", "accounts", "accid",$purch['usedacc']);
        $acc= pg_fetch_array($accRslt);
        $usedacc =  $acc['accname'];

        $confirm .="<tr><th>Field</th><th>Value</th></tr>
        <tr class='bg-even'><td>Retailer</td><td>$purch[retailer]</td></tr>
        <tr class='bg-odd'><td>Item Name</td><td>$purch[itemname]</td></tr>
        <tr class='bg-even'><td>Description</td><td>$purch[descript]</td></tr>
        <tr class='bg-odd'><td>Quantity</td><td>$purch[quantity]</td></tr>
        <tr class='bg-even'><td>Outstanding Amount</td><td>".CUR." $ct[amount]</td></tr>
        <tr class='bg-odd'><td>Amount To Be Paid</td><td>".CUR." <input type=text name=paidamt size=7></td></tr>
        <tr class='bg-even'><td>Account used</td><td>$usedacc</td></tr>
        <tr class='bg-odd'><td>Account paid</td><td>$paidacc</td></tr>
        <tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Pay Credit &raquo'></td></tr>
        </form>
        </table>
        ";
                 return $confirm;
}


# write
function pay($_POST)
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
        $v->isOk ($purchid, "num", 1, 20, "Invalid Purchase ID.");
        $v->isOk ($paidamt, "float", 1, 20, "Invalid amount to be paid.");

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

        // Connect to database
        core_Connect ();

        # get purchase info
        $sql = "SELECT * FROM purchases WHERE purchid = '$purchid'";
        $purchRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve purchase details from database.", SELF);
	$numrows = pg_numrows ($purchRslt);

        if ($numrows < 1) {
		$OUTPUT = "<li clss=err>Invalid purchase ID.";
		require ("template.php");
	}
        $purch = pg_fetch_array($purchRslt);

        # reduce the money that has been paid
        $sql = "UPDATE credit_purch SET amount = (amount - cast(float8 '$paidamt' as numeric)) WHERE purchid = '$purchid'";
        $payRslt = db_exec($sql) or errDie("Unable to update credit purchases table.",SELF);

        # get creditors account
        $creditacc = gethook("accnum", "pchsacc", "name", "Creditors");

		$refnum = getrefnum(date('d-m-Y'));

        writetrans($creditacc, $purch['usedacc'], date('d-m-Y'), $refnum, $paidamt, 'Pay Purchase Credit.');

        # status report
	$pay ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
        <tr><th>Credit Paid</th></tr>
        <tr class=datacell><td>Credit for,<b> $purch[itemname]</b> bought From <b>$purch[retailer]</b>, was successfully paid.</td></tr>
        </table>";

        # main table (layout with menu)
        $OUTPUT = "<center>
        <table width = 90%>
        <tr valign=top><td width=60%>$pay</td>
        <td align=center>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=80%>
        <tr><th>Quick Navigation</th></tr>
        <tr class=datacell><td align=center><a href='purchase-view.php'>View Other Purchases</td></tr>
        <tr class=datacell><td align=center><a href='purchase-new.php'>Add New Purchase</td></tr>
        </table>
        </td></tr></table>";

        return $OUTPUT;
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
?>
