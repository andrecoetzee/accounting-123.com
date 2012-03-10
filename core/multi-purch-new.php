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

                case "slctacc":
			$OUTPUT = slctacc($HTTP_POST_VARS);
			break;

                case "write":
                        $OUTPUT = write($HTTP_POST_VARS);
			break;

                default:
			$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# get template
require("template.php");

# Default view
function view(){

        //LAYOUT
        $view = "<h3>New Purchases</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=80%>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=slctacc>
        <tr bgcolor='".TMPL_tblDataColor1."'><th>Payment Method</th>
        <td  bgcolor='".TMPL_tblDataColor1."' valign=center><select name=paytype>
                <option value='Transfer'>Transfer</option><option value='Cash'>Cash</option>
                <option value='Cheque'>Cheque</option><option value='Credit'>Credit</option>
        </select></td></tr>
        <tr><td><br></td></tr>
        <tr><th>Supplier</th><th>Item Name</th><th>Description</th><th>Quantity</th><th>Date</th><th>Total Cost</th><th>Item Account type</th></tr>";

        for($i = 1; $i <= 5; $i++){
                $view .= "<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=text name=retailer[] size=20></td>
                        <td valign=center><input type=text size=20 name=itemname[]></td>
                        <td valign=center><input type=text size=20 name=descript[]></td>
                        <td valign=center><input type=text name=quantity[] size=7></td>
						<td valign=center><input type=text size=2 name=day[] maxlength=2>-<input type=text size=2 name=mon[] maxlength=2  value='".date("m")."'>-<input type=text size=4 name=year[] maxlength=4 value='".date("Y")."'></td>
						<td valign=center>".CUR." <input type=text name=tlcost[] size=10></td>
                        <td valign=center><select name=paidacctype[]>
                                <option value='E'>Expenditure</option>
                                <option value='B'>Balance</option>
                                <option value='I'>Income</option>
                        </select></td></tr>";
        }

        $view .= "
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
function slctacc($HTTP_POST_VARS)
{
        # get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($paytype, "string", 1, 10, "Invalid Payment Type.");
        $t = 0;
        foreach($tlcost as $key => $value){
                if($tlcost[$key] > 0){
                        $v->isOk ($retailer[$key], "string", 1, 255, "Invalid Supplier name.[$key]");
                        $v->isOk ($itemname[$key], "string", 1, 255, "Invalid Item name.[$key]");
                        $v->isOk ($descript[$key], "string", 0, 255, "Invalid Description Entry.[$key]");
                        $v->isOk ($quantity[$key], "num", 1, 20, "Invalid Quantity.[$key]");
                        $v->isOk ($tlcost[$key], "float", 1, 20, "Invalid Total Cost.[$key]");
                        $v->isOk ($paidacctype[$key], "string", 1, 3, "Invalid Item account type.[$key]");
						$v->isOk ($day[$key], "num", 1,2, "Invalid to Date day.");
                        $v->isOk ($mon[$key], "num", 1,2, "Invalid to Date month.");
                        $v->isOk ($year[$key], "num", 1,4, "Invalid to Date Year.");
                        $date[$key] = $day[$key]."-".$mon[$key]."-".$year[$key];
                        if(!checkdate($mon[$key], $day[$key], $year[$key])){
                                $v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
                        }
						$t = 1;
                }
        }
        if($t == 0){
                return "<li>Please enter full purchases information.";
        }

        # display errors, if any
	if ($v->isError ()) {
		$Errors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$Errors .= "-".$e["msg"]."<br>";
		}
                $Errors = "<tr><td class=err colspan=2>$Errors</td></tr>
                <tr><td colspan=2><br></td></tr>";
                $Errors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $Errors;
        }

        // layout
        $purchase = "<h3>New Purchase</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <input type=hidden name=paytype value='$paytype'>";

        $purchases = "";
        foreach($tlcost as $key => $value){
                if($tlcost[$key] > 0){
                        // Accounts Drop down selections
                        core_connect();
                        # account to be used
                        $used = "<select name='usedacc[]'>";
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
                        $paid = "<select name='paidacc[]'>";
                        $sql = "SELECT * FROM accounts WHERE acctype ='$paidacctype[$key]'";
                        $accRslt = db_exec($sql);
                        $numrows = pg_numrows($accRslt);
                        if(empty($numrows)){
                                if($paidacctype[$key] == "E"){
                                        $paid = "There are no Expenditure accounts yet in Cubit.";
                                }elseif($paidacctype[$key] == "I"){
                                        $paid = "There are no Income accounts yet in Cubit.";
                                }else{
                                        $paid = "There are no Balance accounts yet in Cubit.";
                                }
                        }else{
                                while($acc = pg_fetch_array($accRslt)){
                                        $paid .= "<option value='$acc[accid]'>$acc[accname]</option>";
                                }
                        }
                        $paid .="</select>";

                $purchases .= "<tr bgcolor='".TMPL_tblDataColor1."'>
                        <td><input type=hidden name=retailer[] value='$retailer[$key]'>$retailer[$key]</td>
                        <td valign=center><input type=hidden name=itemname[] value='$itemname[$key]'>$itemname[$key]</td>
                        <td valign=center><input type=hidden name=descript[] value='$descript[$key]'>$descript[$key]</td>
						<td valign=center><input type=hidden name=quantity[] size=7 value='$quantity[$key]'>$quantity[$key]</td>
						<td valign=center><input type=hidden size=10 name=date[] value='$date[$key]'>$date[$key]</td>
						<td valign=center>".CUR." <input type=hidden name=tlcost[] size=10 value='$tlcost[$key]'>$tlcost[$key]</td>
                        <td valign=center>$used</td>
                        <td valign=center>$paid</td>";

					# check pay type
					if($paytype == "Cheque"){
							$head = "<th>Cheque number</th>";
							$purchases .= "<td valign=center><input type=text name=cheqnum[]></td></tr>";
					}elseif($paytype == "Credit"){
							$head = "<th>Terms</th>";
							$purchases .= "<td valign=center><input type=text name=terms[] size=7>
									<select name=time><option value='days'>days</option><option value='months'>months</option></select></td></tr>";
					}else{
							$head = "";
							$purchases .= "</tr>";
					}

                }
        }

        $purchase .= "
        <tr bgcolor='".TMPL_tblDataColor1."'><th>Payment Method</th><td valign=center><input type=hidden name=paytype value='$paytype'>$paytype</td></tr>
        <tr><td><br></td></tr>
        <tr><th>Supplier</th><th>Item Name</th><th>Description</th><th>Quantity</th><th>Date</th><th>Total Cost</th><th>Used Account</th><th>Item Account</th>$head</tr>
        $purchases
        <tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Add >'></td></tr>
        </table>
        <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

        return $purchase;
}

# Select Accounts
function confirm($HTTP_POST_VARS)
{
        # get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($paytype, "string", 1, 10, "Invalid Payment Type.");
        foreach($tlcost as $key => $value){
                        $v->isOk ($retailer[$key], "string", 1, 255, "Invalid Supplier name.[$key]");
                        $v->isOk ($itemname[$key], "string", 1, 255, "Invalid Item name.[$key]");
                        $v->isOk ($descript[$key], "string", 0, 255, "Invalid Description Entry.[$key]");
                        $v->isOk ($quantity[$key], "num", 1, 20, "Invalid Quantity.[$key]");
                        $v->isOk ($tlcost[$key], "float", 1, 20, "Invalid Total Cost.[$key]");
						$datea = explode("-", $date[$key]);
						if(count($datea) == 3){
								if(!checkdate($datea[1], $datea[0], $datea[2])){
										$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
								}
						}else{
								$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
						}

						if(isset($paidacc[$key])){
                                $v->isOk ($paidacc[$key], "num", 1, 50, "Invalid account to be paid.");
                        }else{
                                return "<li>ERROR : Account to be paid was no selected.
                                <p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
                        }

                        if(isset($usedacc[$key])){
                                $v->isOk ($usedacc[$key], "num", 1, 50, "Invalid account to be used.");
                        }else{
                                return "<li>ERROR : account to be used was no selected.
                                <p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
                        }
                        if($paytype == "Cheque"){
                                $v->isOk ($cheqnum[$key], "num", 1, 50, "Invalid cheque number.");
                        }elseif($paytype == "Credit"){
                                $v->isOk ($time[$key], "string", 1, 10, "Invalid payment time.");
                                $v->isOk ($terms[$key], "num", 1, 50, "Invalid number of $time[$key] in terms.");
                        }
        }

        # display errors, if any
	if ($v->isError ()) {
		$Errors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$Errors .= "-".$e["msg"]."<br>";
		}
                $Errors = "<tr><td class=err colspan=2>$Errors</td></tr>
                <tr><td colspan=2><br></td></tr>";
                # header("Location: ".SELF."?retailer=$retailer&itemname=$itemname&descript=$descript&quantity=$quantity&tlcost=$tlcost&err=$Errors");

                $Errors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $Errors;
        }

        // layout
        $purchase = "<h3>New Purchase</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=write>
        <input type=hidden name=paytype value='$paytype'>";

        $purchases = "";
        foreach($tlcost as $key => $value){
                        core_connect();

                        # get used account name
                        core_connect();
                        $sql = "SELECT accname FROM accounts WHERE accid = '$usedacc[$key]'";
                        $accRslt = db_exec($sql);
                        $acc = pg_fetch_array($accRslt);
                        $usedaccname = $acc['accname'];

                        # get paid account name
                        $sql = "SELECT accname FROM accounts WHERE accid = '$paidacc[$key]'";
                        $accRslt = db_exec($sql);
                        $acc = pg_fetch_array($accRslt);
                        $paidaccname = $acc['accname'];

                $purchases .= "<tr bgcolor='".TMPL_tblDataColor1."'>
                        <td><input type=hidden name=retailer[] value='$retailer[$key]'>$retailer[$key]</td>
                        <td valign=center><input type=hidden name=itemname[] value='$itemname[$key]'>$itemname[$key]</td>
                        <td valign=center><input type=hidden name=descript[] value='$descript[$key]'>$descript[$key]</td>
                        <td valign=center><input type=hidden name=quantity[] size=7 value='$quantity[$key]'>$quantity[$key]</td>
						<td valign=center><input type=hidden size=10 name=date[] value='$date[$key]'>$date[$key]</td>
						<td valign=center>".CUR." <input type=hidden name=tlcost[] size=10 value='$tlcost[$key]'>$tlcost[$key]</td>
                        <td valign=center><input type=hidden name=usedacc[] value='$usedacc[$key]'>$usedaccname</td>
                        <td valign=center><input type=hidden name=paidacc[] value='$paidacc[$key]'>$paidaccname</td>";

                        # check pay type
                        if($paytype == "Cheque"){
                                $head = "<th>Cheque number</th>";
                                $purchases .= "<td valign=center><input type=hidden name=cheqnum[] value='$cheqnum[$key]'>$cheqnum[$key]</td></tr>";
                        }elseif($paytype == "Credit"){
                                $head = "<th>Terms</th>";
                                if($time[$key] == "d"){
                                        $AT = "Days";
                                }else{
                                        $AT = "Months";
                                }
                                $purchases .= "<td valign=center><input type=hidden name=terms[] value='$terms[$key]'size=7>$terms[$key]&nbsp;<input type=hidden name=time[] value='$time[$key]'>$AT</td></tr>";
                        }else{
                                $head = "";
                                $purchases .= "</tr>";
                        }
        }

        $purchase .= "
        <tr bgcolor='".TMPL_tblDataColor1."'><th>Payment Method</th><td valign=center><input type=hidden name=paytype value='$paytype'>$paytype</td></tr>
        <tr><td><br></td></tr>
        <tr><th>Supplier</th><th>Item Name</th><th>Description</th><th>Quantity</th><th>Date</th><th>Total Cost</th><th>Used Account</th><th>Item Account</th>$head</tr>
        $purchases
        <tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Confirm >'></td></tr>
        </table>
        <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

        return $purchase;
}

# write
function write($HTTP_POST_VARS)
{
        //processes
        core_connect();

        # get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
        # validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($paytype, "string", 1, 10, "Invalid Payment Type.");

        foreach($tlcost as $key => $value){
                $v->isOk ($retailer[$key], "string", 1, 255, "Invalid Supplier name.[$key]");
                $v->isOk ($itemname[$key], "string", 1, 255, "Invalid Item name.[$key]");
                $v->isOk ($descript[$key], "string", 0, 255, "Invalid Description Entry.[$key]");
                $v->isOk ($quantity[$key], "num", 1, 20, "Invalid Quantity.[$key]");
                $v->isOk ($tlcost[$key], "float", 1, 20, "Invalid Total Cost.[$key]");
				$datea = explode("-", $date[$key]);
				if(count($datea) == 3){
						if(!checkdate($datea[1], $datea[0], $datea[2])){
							$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
						}
				}else{
						$v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
				}

				if(isset($paidacc[$key])){
                        $v->isOk ($paidacc[$key], "num", 1, 50, "Invalid account to be paid.");
                }else{
                        return "<li>ERROR : Account to be paid was no selected.
                                <p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
                }

                if(isset($usedacc[$key])){
                        $v->isOk ($usedacc[$key], "num", 1, 50, "Invalid account to be used.");
                }else{
                        return "<li>ERROR : account to be used was no selected.
                                <p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
                }
                if($paytype == "Cheque"){
                        $v->isOk ($cheqnum[$key], "num", 1, 50, "Invalid cheque number.");
                }elseif($paytype == "Credit"){
                        $v->isOk ($time[$key], "string", 1, 10, "Invalid payment time.");
                        $v->isOk ($terms[$key], "num", 1, 50, "Invalid number of $time[$key] in terms.");
                }
        }

        # display errors, if any
	if ($v->isError ()) {
		$Errors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$Errors .= "-".$e["msg"]."<br>";
		}
                $Errors = "<tr><td class=err colspan=2>$Errors</td></tr>
                <tr><td colspan=2><br></td></tr>";
                # header("Location: ".SELF."?retailer=$retailer&itemname=$itemname&descript=$descript&quantity=$quantity&tlcost=$tlcost&err=$Errors");

                $Errors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $Errors;
        }

		$refnum = getrefnum($date[$key]);

foreach($tlcost as $key => $value){
        // write the transaction
        if($paytype == "Transfer" || $paytype == "Cash"){

                core_connect();
                # record the purchase
                $sql = "INSERT INTO purchases(retailer, itemname, descript, quantity, tlcost, usedacc, paidacc, paytype) VALUES ('$retailer[$key]', '$itemname[$key]', '$descript[$key]', '$quantity[$key]', '$tlcost[$key]', '$usedacc[$key]', '$paidacc[$key]', '$paytype')";
        	$Rslt = db_exec ($sql) or errDie ("Unable record purchase transaction to database.",SELF);


                # credit acc used - debit acc paid
                writetrans($paidacc[$key], $usedacc[$key], $date[$key], $refnum, $tlcost[$key],  "Bought $itemname[$key] with transfer payment.");

         }elseif($paytype == "Cheque"){

                core_connect();
                # record the purchase
                $sql = "INSERT INTO purchases(retailer, itemname, descript, quantity, tlcost, usedacc, paidacc, paytype, cheqnum) VALUES ('$retailer[$key]', '$itemname[$key]', '$descript[$key]', '$quantity[$key]', '$tlcost[$key]', '$usedacc[$key]', '$paidacc[$key]', '$paytype', '$cheqnum[$key]')";
        	$Rslt = db_exec ($sql) or errDie ("Unable record purchase transaction to database.",SELF);

                # credit acc used - debit acc paid
                writetrans($paidacc[$key], $usedacc[$key], $date[$key], $refnum, $tlcost[$key],  "Bought $itemname[$key] With Cheque.");

        }elseif($paytype == "Credit"){
                core_connect();
                #record the purchase
                $sql = "INSERT INTO purchases(retailer, itemname, descript, quantity, tlcost, usedacc, paidacc, paytype) VALUES ('$retailer[$key]', '$itemname[$key]', '$descript[$key]', '$quantity[$key]', '$tlcost[$key]', '$usedacc[$key]', '$paidacc[$key]', '$paytype')";
        		$Rslt = db_exec ($sql) or errDie ("Unable record purchase transaction to database.",SELF);

                # get purchase ID
                $purchid[$key] = pglib_lastid ("purchases", "purchid");

                # record credit owed
                $sql = "INSERT INTO credit_purch(purchid, retailer, terms, amount, period) VALUES ( '$purchid[$key]', '$retailer[$key]', '$terms[$key]', '$tlcost[$key]', '$time[$key]')";
        		$ctRslt = db_exec ($sql) or errDie ("Unable record credit purchase transaction to database.",SELF);

                # get creditors account
                $creditacc = gethook("accnum", "pchsacc", "name", "Creditors");

                # debit paid acc credit creditors acc
                writetrans($paidacc[$key], $creditacc, $date[$key], $refnum, $tlcost[$key], "Bought $itemname[$key] on credit");
        }
}


        # status report
	$write ="
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
        <tr><th>New Purchases</th></tr>
        <tr class=datacell><td>New purchases have been successfully added to Cubit.</td></tr>
        </table>";

        # main table (layout with menu)
        $OUTPUT = "<center>
        <table width = 90%>
        <tr valign=top><td width=50%>$write</td>
        <td align=center>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=80%>
        <tr><th>Quick Navigation</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='".SELF."'>Add New Purchase</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='purchase-view.php'>View Purchases</a></td></tr>
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
