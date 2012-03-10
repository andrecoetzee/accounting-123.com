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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;

                case "write":
                        $OUTPUT = write($HTTP_POST_VARS);
			break;

                default:
                        if(isset($HTTP_GET_VARS['purchid'])){
                                $OUTPUT = details($HTTP_GET_VARS['purchid']);
                        }else{
                                # Display default output
                                $OUTPUT = view();
                        }
        }
}else{
        if(isset($HTTP_GET_VARS['purchid'])){
                $OUTPUT = details($HTTP_GET_VARS['purchid']);
        }else{
                # Display default output
                $OUTPUT = view();
        }
}

# get template
require("template.php");

# Default view
function view(){
$view = "<center><table width=90%>
        <tr><td width=80%><h3>Purchase Returns</h3></td>
        <td bgcolor='".TMPL_tblDataColor2."'><a href='purchase-new.php'>Add New Purchase</a><br>
        <a href='purchase-ret-view.php'>View Returned Purchases</a>
        </td></tr>
        </table><br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=90%>
        <tr><th>Item Name</th><th>Description</th><th>Quantity</th><th>Total Cost</th><th>Payment Method</th><th>Item Account</th><th>Account Used</th></tr>";
        core_connect();
        $sql = "SELECT * FROM purchases ORDER BY purchid DESC";
        $purchRslt = db_exec($sql);
        if(pg_numrows($purchRslt) < 1){
                return "<li class=err> There are no purchased items in Cubit yet";
        }else{
                $i = 0; // for bgcolor
                while($purch = pg_fetch_array($purchRslt)){
                        foreach($purch as $key => $value){
                                $$key = $value;
                        }
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

                        # if credit => check if it is paid if not give option to pay
                        if($paytype == "Credit"){
                                $sql = "SELECT * FROM credit_purch WHERE purchid = '$purchid' AND amount > 0";
                                $ctRslt = db_exec($sql);
                                if(pg_numrows($ctRslt) > 0){
                                        continue;
                                }else{
                                        # alternate bgcolor and write list
	        	                $bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                                        $view .= "<tr bgcolor='$bgColor'><td>$itemname</td><td>$descript</td><td>$quantity</td><td>".CUR." $tlcost</td><td>$paytype</td><td>$paidaccname</td><td>$usedaccname</td><td><a href='purchase-ret.php?purchid=$purchid'>Return</a></td></tr>";
                                }
                        }else{
                                # alternate bgcolor and write list
		                $bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
                                $view .= "<tr bgcolor='$bgColor'><td>$itemname</td><td>$descript</td><td>$quantity</td><td>".CUR." $tlcost</td><td>$paytype</td><td>$paidaccname</td><td>$usedaccname</td><td><a href='purchase-ret.php?purchid=$purchid'>Return</a></td></tr>";
                        }
                        $i++;
                }
        }
	$view .= "</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		</table>
	";
        return $view;
}

# Enter return details
function details($purchid)
{

        $details = "<h3>Purchase return</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=40%>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=confirm>
        <input type=hidden name=purchid value='$purchid'>";

        core_connect();
        $sql = "SELECT * FROM purchases WHERE purchid = '$purchid'";
        $purchRslt = db_exec($sql);
        if(pg_numrows($purchRslt) < 1){
                return "<li class=err>Invalid Purchase number.";
        }

        $purch = pg_fetch_array($purchRslt);

        foreach($purch as $key => $value){
                $$key = $value;
        }

        $sql = "SELECT terms,period FROM credit_purch WHERE purchid = '$purchid'";
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

        $details .="<tr><th>Field</th><th>Value</th></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Retailer</td><td valign=center>$retailer</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Item Name</td><td valign=center>$itemname</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td valign=center>$descript</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Quantity Returned</td><td valign=center>&nbsp;&nbsp;&nbsp;&nbsp;<input type=text name=qty size=10 value='$quantity'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Total Cost Returned</td><td valign=center>".CUR." <input type=text name=cost size=10 value='$tlcost'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Payment Method</td><td valign=center>$paytype</td></tr>";

                 # view cheq number if it is there
                if($paytype == "Cheque"){
                        $details .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>Cheque Number</td><td valign=center>$cheqnum</td></tr>";
                }elseif($paytype == "Credit"){
                        $details .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>Terms Of Payment</td><td valign=center>$ct[terms] $ct[period]</td></tr>";
                }

        $details .= "
        <tr bgcolor='".TMPL_tblDataColor2."'><td valign=center>Account Used</td><td valign=center>$usedaccname</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td valign=center>Account For Item</td><td valign=center>$paidaccname</td></tr>
        <tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
        </form></table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>



";

        return $details;
}

# confirm
function confirm($HTTP_POST_VARS)
{
        # get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($purchid, "num", 1, 255, "Invalid purchase number.");
        $v->isOk ($qty, "num", 1, 255, "Invalid quantity returned.");
        $v->isOk ($cost, "float", 1, 255, "Invalid total cost returned.");

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

        // Layout
        $details = "<h3>Purchase return</h3>
        <h4>Confirm Entry</h4>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=40%>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <input type=hidden name=purchid value='$purchid'>
        <input type=hidden name=qty value='$qty'>
        <input type=hidden name=cost value='$cost'>";

        core_connect();
        $sql = "SELECT * FROM purchases WHERE purchid = '$purchid'";
        $purchRslt = db_exec($sql);
        if(pg_numrows($purchRslt) < 1){
                return "<li class=err>Invalid Purchase number.";
        }

        $purch = pg_fetch_array($purchRslt);

        foreach($purch as $key => $value){
                $$key = $value;
        }

        $sql = "SELECT terms,period FROM credit_purch WHERE purchid = '$purchid'";
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

        $details .="<tr><th>Field</th><th>Value</th></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Retailer</td><td valign=center>$retailer</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Item Name</td><td valign=center>$itemname</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td valign=center>$descript</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Quantity Returned</td><td valign=center>$qty</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Total Cost Returned</td><td valign=center>".CUR." $cost</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Payment Method</td><td valign=center>$paytype</td></tr>";

                 # view cheq number if it is there
                if($paytype == "Cheque"){
                        $details .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>Cheque Number</td><td valign=center>$cheqnum</td></tr>";
                }elseif($paytype == "Credit"){
                        $details .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>Terms Of Payment</td><td valign=center>$ct[terms] $ct[period]</td></tr>";
                }

        $details .= "
        <tr bgcolor='".TMPL_tblDataColor2."'><td valign=center>Account Used</td><td valign=center>$usedaccname</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td valign=center>Account For Item</td><td valign=center>$paidaccname</td></tr>
        <tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Confirm &raquo'></td></tr>
        </form></table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>
";

        return $details;
}

# Write to Db
function write($HTTP_POST_VARS)
{
        # get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($purchid, "num", 1, 255, "Invalid purchase number.");
        $v->isOk ($qty, "num", 1, 255, "Invalid quantity returned.");
        $v->isOk ($cost, "float", 1, 255, "Invalid total cost returned.");

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

        //proc
        # get all vars
        core_connect();
        $sql = "SELECT * FROM purchases WHERE purchid = '$purchid'";
        $purchRslt = db_exec($sql);
        if(pg_numrows($purchRslt) < 1){
                return "<li class=err>Invalid Purchase number.";
        }

        $purch = pg_fetch_array($purchRslt);

        # get all vars
        foreach($purch as $key => $value){
                $$key = $value;
        }

		$refnum = getrefnum(date("d-m-Y"));

        # insert into purch_ret
        if($paytype == "Transfer" || $paytype == "Cash"){
                core_connect();

                # record the purchase
                $sql = "INSERT INTO purch_ret(purchid, retailer, itemname, descript, quantity, tlcost, usedacc, paidacc, paytype) VALUES ('$purchid', '$retailer', '$itemname', '$descript', '$qty', '$cost', '$usedacc', '$paidacc', '$paytype')";
        	$Rslt = db_exec ($sql) or errDie ("Unable record purchase transaction to database.",SELF);

                # update purchases
                $sql = "UPDATE purchases SET tlcost = (tlcost - cast(float '$cost' as numeric)), quantity = (quantity - '$qty')  WHERE purchid = '$purchid'";
                $rslt = db_exec($sql) or errDie("Unable to update purchases in Cubit.",SELF);

                # credit acc paid - debit acc used
                writetrans($usedacc, $paidacc, date("d-m-Y"), $refnum, $cost, "Purchase of $itemname returned");

                # credit acc paid debit acc used
                # writetrans( $usedacc, $paidacc, $cost, "Purchase of $itemname returned");

        }elseif($paytype == "Cheque"){
                core_connect();
                # record the purchase
                $sql = "INSERT INTO purch_ret(purchid, retailer, itemname, descript, quantity, tlcost, usedacc, paidacc, paytype, cheqnum) VALUES ('$purchid', '$retailer', '$itemname', '$descript', '$qty', '$cost', '$usedacc', '$paidacc', '$paytype', '$cheqnum')";
        	$Rslt = db_exec ($sql) or errDie ("Unable record purchase transaction to database.",SELF);

                # update purchases
                $sql = "UPDATE purchases SET tlcost = (tlcost - cast(float '$cost' as numeric)), quantity = (quantity - '$qty')  WHERE purchid = '$purchid'";
                $rslt = db_exec($sql) or errDie("Unable to update purchases on Cubit.",SELF);

                # credit acc paid - debit acc used
                writetrans($usedacc, $paidacc, date("d-m-Y"), $refnum, $cost, "Purchase of $itemname returned.");

                # credit acc paid debit acc used
                # writetrans(  $usedacc, $paidacc, $cost, "Purchase of $itemname returned");

        }elseif($paytype == "Credit"){

                core_connect();
                #record the purchase
                $sql = "INSERT INTO purch_ret(purchid, retailer, itemname, descript, quantity, tlcost, usedacc, paidacc, paytype) VALUES ('$purchid', '$retailer', '$itemname', '$descript', '$qty', '$cost', '$usedacc', '$paidacc', '$paytype')";
        	$Rslt = db_exec ($sql) or errDie ("Unable record purchase transaction to database.",SELF);

                # update purchases
                $sql = "UPDATE purchases SET tlcost = (tlcost - cast(float '$cost' as numeric)), quantity = (quantity - '$qty') WHERE purchid = '$purchid'";
                $rslt = db_exec($sql) or errDie("Unable to update purchases in Cubit.",SELF);

                # decrease credit owed
                $sql = "UPDATE credit_purch SET amount = (amount - cast(float '$cost' as numeric)) WHERE purchid='$purchid'";
        	$ctRslt = db_exec ($sql) or errDie ("Unable record credit purchase transaction to database.",SELF);

                # get creditors account
                $creditacc = gethook("accnum", "pchsacc", "name", "Creditors");

                # credit acc paid - debit acc used
                writetrans($creditacc, $paidacc, date("d-m-Y"), $refnum, $cost, "Purchase of $itemname returned.");

                # credit paidacc and creditors paid acc
                # writetrans( $creditacc, $paidacc, $cost, "Purchase of $itemname returned");
        }

        # Remove returned purchases
        $sql = "DELETE FROM purchases WHERE quantity = 0";
        $Rslt = db_exec ($sql) or errDie ("Unable to delete purchase from database.",SELF);

        # status report
	$write ="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
                        <tr><th>Purchase Return</th></tr>
                        <tr class=datacell><td>Purchase of, <b>$itemname</b> was successfully returned.</td></tr>
                </table>";

        # main table (layout with menu)
        $OUTPUT = "<center>
        <table width = 90%>
        <tr valign=top><td width=50%>$write</td>
        <td align=center>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=80%>
                <tr><th>Quick Navigation</th></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='".SELF."'>Return other Purchase</a></td></tr>
                <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='purchase-view.php'>View Purchases</a></td></tr>
        </table>
        </td></tr></table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
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
