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

                case "slctacc":
			$OUTPUT = slctacc($_POST);
			break;

                case "write":
                        $OUTPUT = write($_POST);
			break;

                default:
			$OUTPUT = view();
	}
}elseif(isset($_GET["err"])){
        # get vars from _GET
        foreach($_GET as $key => $value){
                $$key = $value;
        }
        $OUTPUT = view ($retailer, $itemname, $descript, $quantity, $tlcost, $err);
} else {
        # Display default output
        $OUTPUT = view();
}

# get template
require("template.php");

# Default view
function view($retailer="", $itemname="", $descript="", $quantity="", $tlcost="",$err=""){
	$view = "
	<h3>New Purchase</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=slctacc>
	$err
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Supplier</td><td valign=center><input type=text name=retailer value='$retailer'></td></tr>
	<tr class='bg-odd'><td>Item Name</td><td valign=center><input type=text name=itemname value='$itemname'></td></tr>
	<tr class='bg-even'><td>Description</td><td valign=center><textarea name=descript cols=18 rows=5>$descript</textarea></td></tr>
	<tr class='bg-odd'><td>Quantity</td><td valign=center><input type=text name=quantity value='$quantity'></td></tr>
	<tr class='bg-even'><td>Total Cost</td><td valign=center>".CUR." <input type=text name=tlcost size=10 value='$tlcost'></td></tr>
	<tr class='bg-odd'><td>Date</td><td><input type=text size=2 name=day maxlength=2>-<input type=text size=2 name=mon maxlength=2  value='".date("m")."'>-<input type=text size=4 name=year maxlength=4 value='".date("Y")."'></td></tr>
	<tr class='bg-even'><td>Payment Method</td><td valign=center><select name=paytype>
	<option value='Transfer'>Transfer</option><option value='Cash'>Cash</option>
	<option value='Cheque'>Cheque</option><option value='Credit'>Credit</option></select></td></tr>
	<tr class='bg-odd'><td>Item Account Type</td><td valign=center><select name=paidacctype>
	<option value='E'>Expenditure</option>
	<option value='B'>Balance</option>
	<option value='I'>Income</option>
	</select></td></tr>
	<tr><td><input type=button value='&laquo Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Add &raquo'></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}

# Select Accounts
function slctacc($_POST)
{
        # get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($retailer, "string", 1, 255, "Invalid Supplier name.");
        $v->isOk ($itemname, "string", 1, 255, "Invalid Item name.");
        $v->isOk ($descript, "string", 0, 255, "Invalid Description Entry.");
        $v->isOk ($quantity, "num", 1, 20, "Invalid Quantity.");
		$v->isOk ($day, "num", 1,2, "Invalid to Date day.");
        $v->isOk ($mon, "num", 1,2, "Invalid to Date month.");
        $v->isOk ($year, "num", 1,4, "Invalid to Date Year.");
        $date = $day."-".$mon."-".$year;
        if(!checkdate($mon, $day, $year)){
                $v->isOk ($date, "num", 1, 1, "Invalid date.");
        }
		$v->isOk ($tlcost, "float", 1, 20, "Invalid Total Cost.");
        $v->isOk ($paytype, "string", 1, 10, "Invalid Payment Type.");
        $v->isOk ($paidacctype, "string", 1, 3, "Invalid Item account type.");

        # display errors, if any
	if ($v->isError ()) {
		$Errors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$Errors .= "-".$e["msg"]."<br>";
		}
                $Errors = "<tr><td class=err colspan=2>$Errors</td></tr>
                <tr><td colspan=2><br></td></tr>";
                header("Location: ".SELF."?retailer=$retailer&itemname=$itemname&descript=$descript&quantity=$quantity&tlcost=$tlcost&err=$Errors");

                $Errors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $Errors;
        }

        // Accounts Drop down selections
        core_connect();
        # account to be used
        $used = "<select name='usedacc'>";
        $sql = "SELECT * FROM accounts WHERE acctype ='B'";
        $accRslt = db_exec($sql);
        $numrows = pg_numrows($accRslt);
        if(empty($numrows)){
                $used = "There are no Balance accounts yet in Cubit.";
        }else{
                while($acc = pg_fetch_array($accRslt)){
                        $used .= "<option value='$acc[accid]'>$acc[accname]</option>";
                }
        }
        $used .="</select>";

        # account to be paid
        $paid = "<select name='paidacc'>";
        $sql = "SELECT * FROM accounts WHERE acctype ='$paidacctype'";
        $accRslt = db_exec($sql);
        $numrows = pg_numrows($accRslt);
        if(empty($numrows)){
                $paid = "There are no Income accounts yet in Cubit.";
        }else{
                while($acc = pg_fetch_array($accRslt)){
                        $paid .= "<option value='$acc[accid]'>$acc[accname]</option>";
                }
        }
        $paid .="</select>";

        // layout
        $purchase = "<h3>New Purchase</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=50%>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <!-- hidden data -->
        <input type=hidden name=retailer value='$retailer'>
        <input type=hidden name=itemname value='$itemname'>
        <input type=hidden name=descript value='$descript'>
		<input type=hidden name=date value='$date'>
		<input type=hidden name=quantity value='$quantity'>
        <input type=hidden name=tlcost value='$tlcost'>
        <input type=hidden name=paytype value='$paytype'>
        <tr><th>Field</th><th>Value</th></tr>
        <tr class='bg-odd'><td>Supplier</td><td valign=center>$retailer</td></tr>
        <tr class='bg-even'><td>Item Name</td><td valign=center>$itemname</td></tr>
        <tr class='bg-odd'><td>Description</td><td valign=center>$descript</td></tr>
        <tr class='bg-even'><td>Quantity</td><td valign=center>$quantity</td></tr>
		<tr class='bg-odd'><td>Date</td><td valign=center>$date</td></tr>
        <tr class='bg-even'><td>Payment Method</td><td valign=center>$paytype</td></tr>
        <tr class='bg-odd'><td>Total Cost</td><td valign=center>".CUR." $tlcost</td></tr>";
                if($paytype == "Cheque"){
                        $purchase .= "<tr class='bg-even'><td>Cheque Number</td><td valign=center><input type=text name=cheqnum></td></tr>";
                }elseif($paytype == "Credit"){
                        $purchase .= "<tr class='bg-odd'><td>Terms Of Payment</td><td valign=center><input type=text name=terms>
                                      <select name=time><option value='days'>days</option><option value='months'>months</option></select></td></tr>";
                }
        $purchase .= "
        <tr class='bg-odd'><td valign=center>Select Account Used</td><td valign=center>$used</td></tr>
        <tr class='bg-even'><td valign=center>Select Account For Item</td><td valign=center>$paid</td></tr>
        <tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Add >'></td></tr>
        </table>


<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>


";

        return $purchase;
}

# confirm
function confirm($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($retailer, "string", 1, 255, "Invalid Supplier name.");
        $v->isOk ($itemname, "string", 1, 255, "Invalid Item name.");
        $v->isOk ($descript, "string", 0, 255, "Invalid 'Description' Entry.");
        $v->isOk ($quantity, "num", 1, 20, "Invalid 'Quantity' Entry.");
        $v->isOk ($tlcost, "float", 1, 20, "Invalid 'Total cost' Entry.");
        $v->isOk ($paytype, "string", 1, 10, "Invalid Payment Type.");

        if(isset($paidacc))
                $v->isOk ($paidacc, "num", 1, 50, "Invalid account to be paid.");
        else{
                return "<li>ERROR : Account to be paid was no selected.
                        <p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
        }

        if(isset($usedacc))
                $v->isOk ($usedacc, "num", 1, 50, "Invalid account to be used.");
        else{
                return "<li>ERROR : account to be used was no selected.
                        <p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
        }

        if($paytype == "Cheque"){
                $v->isOk ($cheqnum, "num", 1, 50, "Invalid cheque number.");
        }elseif($paytype == "Credit"){
                $v->isOk ($time, "string", 1, 10, "Invalid payment time.");
                $v->isOk ($terms, "num", 1, 50, "Invalid number of $time in terms.");
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

        $confirm ="<center><h3>New Purchase</h3>
        <h4>Confirm entry (Please check the details)</h4>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=60%>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <input type=hidden name=retailer value='$retailer'>
        <input type=hidden name=itemname value='$itemname'>
        <input type=hidden name=descript value='$descript'>
        <input type=hidden name=quantity value='$quantity'>
		<input type=hidden name=date value='$date'>
        <input type=hidden name=tlcost value='$tlcost'>
        <input type=hidden name=paytype value='$paytype'>
        <input type=hidden name=usedacc value='$usedacc'>
        <input type=hidden name=paidacc value='$paidacc'>";
        # hide cheqnumber if it is there
        if($paytype == "Cheque"){
                $confirm .= "<input type=hidden name=cheqnum value='$cheqnum'>";
        }elseif($paytype == "Credit"){
                $confirm .= "<input type=hidden name=terms value='$terms'>
                             <input type=hidden name=time value='$time'>";
        }


        # get used account name
        core_connect();
        $sql = "SELECT accname FROM accounts WHERE accid = '$usedacc'";
        $accRslt = db_exec($sql);
        $acc = pg_fetch_array($accRslt);
        $usedaccname = $acc['accname'];

        # get paid account name
        core_connect();
        $sql = "SELECT accname FROM accounts WHERE accid = '$paidacc'";
        $accRslt = db_exec($sql);
        $acc = pg_fetch_array($accRslt);
        $paidaccname = $acc['accname'];


        $confirm .="<tr><th>Field</th><th>Value</th></tr>
        <tr class='bg-even'><td>Supplier</td><td valign=center>$retailer</td></tr>
        <tr class='bg-odd'><td>Item Name</td><td valign=center>$itemname</td></tr>
        <tr class='bg-even'><td>Description</td><td valign=center>$descript</td></tr>
        <tr class='bg-odd'><td>Quantity</td><td valign=center>$quantity</td></tr>
		<tr class='bg-even'><td>Date</td><td valign=center>$date</td></tr>
		<tr class='bg-odd'><td>Total Cost</td><td valign=center>".CUR." $tlcost</td></tr>
        <tr class='bg-even'><td>Payment Method</td><td valign=center>$paytype</td></tr>";

                 # view cheq number if it is there
                if($paytype == "Cheque"){
                        $confirm .= "<tr class='bg-odd'><td>Cheque Number</td><td valign=center>$cheqnum</td></tr>";
                }elseif($paytype == "Credit"){
                        $confirm .= "<tr class='bg-odd'><td>Terms Of Payment</td><td valign=center>$terms $time</td></tr>";
                }

        $confirm .= "
        <tr class='bg-even'><td valign=center>Account Used</td><td valign=center>$usedaccname</td></tr>
        <tr class='bg-odd'><td valign=center>Account For Item</td><td valign=center>$paidaccname</td></tr>
        <tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
        </form></table>




<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>



";

        return $confirm;
}

# write
function write($_POST)
{
        //processes
        core_connect();

        # get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($retailer, "string", 1, 255, "Invalid Supplier name.");
        $v->isOk ($itemname, "string", 1, 255, "Invalid Item name.");
        $v->isOk ($descript, "string", 0, 255, "Invalid 'Description' Entry.");
        $v->isOk ($quantity, "num", 1, 20, "Invalid 'Quantity' Entry.");
        $v->isOk ($tlcost, "float", 1, 20, "Invalid 'Total cost' Entry.");
        $v->isOk ($paytype, "string", 1, 10, "Invalid Payment Type.");
        $v->isOk ($paidacc, "num", 1, 50, "Invalid account to be paid.");
        $v->isOk ($usedacc, "num", 1, 50, "Invalid account to be used.");
        if($paytype == "Cheque"){
                $v->isOk ($cheqnum, "num", 1, 50, "Invalid cheque number.");
        }elseif($paytype == "Credit"){
                $v->isOk ($time, "string", 1, 10, "Invalid payment time.");
                $v->isOk ($terms, "num", 1, 50, "Invalid number of $time in terms.");
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

		$refnum = getrefnum($date);

        // write the transaction
        if($paytype == "Transfer" || $paytype == "Cash"){
                core_connect();
                # record the purchase
                $sql = "INSERT INTO purchases(retailer, itemname, descript, quantity, tlcost, usedacc, paidacc, paytype) VALUES ('$retailer', '$itemname', '$descript', '$quantity', '$tlcost', '$usedacc', '$paidacc', '$paytype')";
        	$Rslt = db_exec ($sql) or errDie ("Unable record purchase transaction to database.",SELF);


                # credit acc used - debit acc paid
                writetrans($paidacc, $usedacc, $date, $refnum, $tlcost,  "Bought $itemname with transfer payment.");

                # credit acc used debit acc paid
                # writetrans( $paidacc, $usedacc, $tlcost, "Bought $itemname with transfer payment.");

        }elseif($paytype == "Cheque"){
                core_connect();
                # record the purchase
                $sql = "INSERT INTO purchases(retailer, itemname, descript, quantity, tlcost, usedacc, paidacc, paytype, cheqnum) VALUES ('$retailer', '$itemname', '$descript', '$quantity', '$tlcost', '$usedacc', '$paidacc', '$paytype', '$cheqnum')";
        	$Rslt = db_exec ($sql) or errDie ("Unable record purchase transaction to database.",SELF);

                # credit acc used - debit acc paid
                writetrans($paidacc, $usedacc, $date, $refnum, $tlcost,  "Bought $itemname With Cheque.");

                # credit acc used debit acc paid
                # writetrans( $paidacc, $usedacc, $tlcost, "Bought $itemname With Cheque.");

        }elseif($paytype == "Credit"){
                core_connect();
                #record the purchase
                $sql = "INSERT INTO purchases(retailer, itemname, descript, quantity, tlcost, usedacc, paidacc, paytype) VALUES ('$retailer', '$itemname', '$descript', '$quantity', '$tlcost', '$usedacc', '$paidacc', '$paytype')";
        	$Rslt = db_exec ($sql) or errDie ("Unable record purchase transaction to database.",SELF);

                # get purchase ID
                $purchid = pglib_lastid ("purchases", "purchid");

                # record credit owed
                $sql = "INSERT INTO credit_purch(purchid, retailer, terms, amount, period) VALUES ( '$purchid', '$retailer', '$terms', '$tlcost', '$time')";
        	$ctRslt = db_exec ($sql) or errDie ("Unable record credit purchase transaction to database.",SELF);

                # get creditors account
                $creditacc = gethook("accnum", "pchsacc", "name", "Creditors");

                writetrans($paidacc, $creditacc, $date, $refnum, $tlcost, "Bought $itemname on credit");

                # credit creditors and debit paid acc
                # writetrans( $paidacc, $creditacc, $tlcost, "Bought $itemname on credit");
        }


        # status report
	$write ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
        <tr><th>New Purchase</th></tr>
        <tr class=datacell><td>New purchase of, <b>$itemname</b> was successfully added to Cubit.</td></tr>
        </table>";

        # main table (layout with menu)
        $OUTPUT = "<center>
        <table width = 90%>
        <tr valign=top><td width=50%>$write</td>
        <td align=center>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=80%>
        <tr><th>Quick Navigation</th></tr>
        <tr class='bg-odd'><td><a href='".SELF."'>Add New Purchase</a></td></tr>
        <tr class='bg-odd'><td><a href='purchase-view.php'>View Purchases</a></td></tr>
	</table>
        </td></tr></table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

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
        pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

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
        pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);
}
?>
