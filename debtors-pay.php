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

                case "confirm":
                        $OUTPUT = confirm($_POST);
			break;

                default:
			$OUTPUT = details($_GET['ordnum']);
	}
} else {
        # Display default output
        if(isset($_GET['ordnum'])){
                $OUTPUT = details($_GET['ordnum']);
        }else{
                $OUTPUT = details('none');
        }
}


# get template
require("template.php");

function details($ordnum)
{
        # validate input
        require_lib("validate");
        $v = new  validate ();
        $v->isOk ($ordnum, "num", 1, 255, "Invalid Invoice number.");

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
        db_connect ();

        # Get invoice info
        db_connect();
        $sql = "SELECT * FROM credit_invoices WHERE ordnum ='$ordnum'";
        $invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class=err>Invalid Invoice Number.";
	}
        $inv = pg_fetch_array($invRslt);

        # Get debt invoice info
        db_connect();
        $sql = "SELECT * FROM debtors WHERE ordnum ='$ordnum'";
        $dtRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	if (pg_numrows ($dtRslt) < 1) {
		return "<li class=err>Invalid Invoice Number.";
	}
        $dt = pg_fetch_array($dtRslt);

        # account to be paid
        core_connect();
        $paid = "<select name='accpaid'>";
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

        // Layout
        $details = "<h3>Receive Debts</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=confirm>
        <input type=hidden name=ordnum value='$ordnum'>";

        foreach($inv as $key => $value){
                $$key = $value;
        }

         // Layout
        $details .=
        "<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
        <tr class='bg-odd'><td width=70%>Customer Name</td><td valign=center>$cusname</td></tr>
        <tr class='bg-even'><td>Telephone No.</td><td valign=center>$tel</td></tr>
        <tr class='bg-odd'><td>Fax No.</td><td valign=center>$fax</td></tr>
        <tr class='bg-even'><td>E-mail Address</td><td valign=center>$email</td></tr>
        <tr class='bg-odd'><td>Order Date</td><td valign=center>$orddate</td></tr>
        <tr class='bg-even'><td>Invoice Date</td><td valign=center>$invdate</td></tr>
        <tr class='bg-odd'><td>Outstanding Amount</td><td valign=center>$dt[amount]</td></tr>
        <tr class='bg-even'><td>Terms</td><td valign=center>$dt[terms]</td></tr>
        <tr><td><br></td></tr>
        <tr class='bg-odd'><td>Amount Paid</td><td>".CUR." <input type=text name='paidamt' size=7></td></tr>
        <tr class='bg-even'><td>Account Paid to</td><td>$paid</td></tr>
        <tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
        <tr><th>Quick Links</th></tr>
        <tr bgcolor='#88BBFF'><td><a href='debtors-view.php'>View Debtors</a></td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        <tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
        </tr>
        </table>
        </form></table>";

        return $details;
}

# write
function confirm($_POST)
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
        $v->isOk ($ordnum, "num", 1, 255, "Invalid invoice number.");
        $v->isOk ($paidamt, "float", 1, 20, "Invalid amount to be paid.");
        $v->isOk ($accpaid, "num", 1, 255, "Invalid account paid to.");

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
        db_connect ();

        # Get invoice info
        db_connect();
        $sql = "SELECT * FROM credit_invoices WHERE ordnum ='$ordnum'";
        $invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class=err>Invalid Invoice Number.";
	}
        $inv = pg_fetch_array($invRslt);

        # Get debt invoice info
        db_connect();
        $sql = "SELECT * FROM debtors WHERE ordnum ='$ordnum'";
        $dtRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	if (pg_numrows ($dtRslt) < 1) {
		return "<li class=err>Invalid Invoice Number.";
	}
        $dt = pg_fetch_array($dtRslt);

        // Layout
        $confirm = "<h3>Receive Debts</h3>
        <h4>Confirm Entry</h4>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=pay>
        <input type=hidden name=ordnum value='$ordnum'>
        <input type=hidden name=paidamt value='$paidamt'>
        <input type=hidden name=accpaid value='$accpaid'>";

        # get paid account name
        core_connect();
        $sql = "SELECT accname FROM accounts WHERE accid = '$accpaid'";
        $accRslt = db_exec($sql);
        $acc = pg_fetch_array($accRslt);

        foreach($inv as $key => $value){
                $$key = $value;
        }

         // Layout
        $confirm .=
        "<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
        <tr class='bg-odd'><td width=70%>Customer Name</td><td valign=center>$cusname</td></tr>
        <tr class='bg-even'><td>Telephone No.</td><td valign=center>$tel</td></tr>
        <tr class='bg-odd'><td>Fax No.</td><td valign=center>$fax</td></tr>
        <tr class='bg-even'><td>E-mail Address</td><td valign=center>$email</td></tr>
        <tr class='bg-odd'><td>Order Date</td><td valign=center>$orddate</td></tr>
        <tr class='bg-even'><td>Invoice Date</td><td valign=center>$invdate</td></tr>
        <tr class='bg-odd'><td>Outstanding Amount</td><td valign=center>$dt[amount]</td></tr>
        <tr class='bg-even'><td>Terms</td><td valign=center>$dt[terms]</td></tr>
        <tr><td><br></td></tr>
        <tr class='bg-odd'><td>Amount Paid</td><td>".CUR." $paidamt</td></tr>
        <tr class='bg-even'><td>Account Paid to</td><td>$acc[accname]</td></tr>
        <tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
<tr><th>Quick Links</th></tr>
<tr bgcolor='#88BBFF'><td><a href='debtors-view.php'>View Debtors</a></td></tr>
<script>document.write(getQuicklinkSpecial());</script>
<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
</tr>
</table>


        </form></table>";

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
        $v->isOk ($ordnum, "num", 1, 255, "Invalid invoice number.");
        $v->isOk ($paidamt, "float", 1, 20, "Invalid amount to be paid.");
        $v->isOk ($accpaid, "num", 1, 255, "Invalid account paid to.");

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
        db_connect ();

        # Get invoice info
        db_connect();
        $sql = "SELECT * FROM credit_invoices WHERE ordnum ='$ordnum'";
        $invRslt = db_exec ($sql) or errDie ("Unable to retrieve invoice details from database.");
	if (pg_numrows ($invRslt) < 1) {
		return "<li class=err>Invalid Invoice Number.";
	}
        $inv = pg_fetch_array($invRslt);

        # reduce the money that has been paid
        $sql = "UPDATE debtors SET amount = (amount - cast(float8 '$paidamt' as numeric)) WHERE ordnum = '$ordnum'";
        $payRslt = db_exec($sql) or errDie("Unable to update debtors information in Cubit.",SELF);

        # get debtors account
        $debtorsacc = gethook("accnum", "salesacc", "name", "Debtors");

		$refnum = getrefnum(date("d-m-Y"));

        # credit acc used debit acc paid
        writetrans($accpaid, $debtorsacc, date("d-m-Y"), $refnum, $paidamt,  "Payment received from debtor.");

        # credit acc used debit acc paid
        # writetrans( $accpaid, $debtorsacc, $paidamt, "Payment received from debtor.");

        # status report
		$pay ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
        <tr><th>Debt Received</th></tr>
        <tr class=datacell><td>Payment received from,<b> $inv[cusname]</b> was successfully recorded to database.</td></tr>
        </table>";

        # main table (layout with menu)
        $OUTPUT = "<center>
        <table width = 90%>
        <tr valign=top><td width=60%>$pay</td>
        <td align=center>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=80%>
        <tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='debtors-view.php'>View Debtors</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
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
