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
                case "details":
			$OUTPUT = details($_POST);
			break;

                case "confirm":
			$OUTPUT = confirm($_POST);
			break;

                case "write":
                        $OUTPUT = write($_POST);
			break;

                default:
                        if(isset($_GET['cusnum'])){
                                $OUTPUT = view($_GET['cusnum']);
                        }else{
                                $OUTPUT = "<li class=err> Invalid use of module.";
                        }
	}
} else {
        if(isset($_GET['cusnum'])){
                $OUTPUT = view($_GET['cusnum']);
        }else{
                $OUTPUT = "<li class=err> Invalid use of module.";
        }
}

# get templete
require("template.php");

# Default view
function view($cusnum)
{
    # validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1,255, "Invalid customer number.");

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

		# account paid to
		core_connect();
		$paid = "<select name='accpaid'>";
        $sql = "SELECT * FROM accounts WHERE acctype ='B'";
        $accRslt = db_exec($sql);
        $numrows = pg_numrows($accRslt);
        if(empty($numrows)){
                $paid = "There are no Balance accounts yet in Cubit.";
        }else{
                while($acc = pg_fetch_array($accRslt)){
                        $paid .= "<option value='$acc[accid]'>$acc[accname]</option>";
                }
        }
        $paid .="</select>";

        # Query server for customer info
        db_connect();
        $sql = "SELECT * FROM customers WHERE cusnum = '$cusnum'";
		$CustRslt = db_exec ($sql) or errDie ("Unable to view customers");
		$numrows = pg_numrows ($CustRslt);
		if ($numrows < 1) {
			return "<li class=err>Invalid Customer Number.";
		}
        $cust = pg_fetch_array($CustRslt);
        foreach($cust as $key => $value){
                $$key = $value;
        }

		//layout
		$view = "
		<h3>Add New Stock Invoice</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=400>
		<form action='".SELF."' method=post name=form>
		<input type=hidden name=key value=details>
		<input type=hidden name=cusnum value='$cusnum'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr class='bg-odd'><td>Customer Name</td><td valign=center>$cusname</td></tr>
		<tr class='bg-even'><td rowspan=3 valign=top>Customer Address</td><td valign=center>$addr1</td></tr>
		<tr class='bg-odd'><!-- rowspan --><td>$addr2</td></tr>
		<tr class='bg-even'><!-- rowspan --><td>$addr3</td></tr>
		<tr class='bg-odd'><td>Telephone No.</td><td valign=center>$tel</td></tr>
		<tr class='bg-even'><td>Fax No.</td><td valign=center>$fax</td></tr>
		<tr class='bg-odd'><td>E-mail Address</td><td valign=center>$email</td></tr>
		<tr class='bg-even'><td>Order Date</td><td valign=center><input type=text size=2 name=oday maxlength=2>-<input type=text size=2 name=omon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=oyear maxlength=4 value='".date("Y")."'> DD-MM-YYYY</td></tr>
		<tr class='bg-odd'><td>Invoice date</td><td valign=center><input type=text size=2 name=invday maxlength=2 value='".date("d")."'>-<input type=text size=2 name=invmon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=invyear maxlength=4 value='".date("Y")."'></td></tr>
		<tr class='bg-even'><td>Account paid</td><td valign=center>$paid</td></tr>
		<tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Add >'></td></tr>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='customers-new.php'>New Customer</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
		</tr>
		</table>

		</form>
		</table>";

        return $view;
}

function details($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($cusnum, "num", 1,255, "Invalid customer number.");
        $v->isOk ($oday, "num", 1,2, "Invalid order Date day.");
        $v->isOk ($omon, "num", 1,2, "Invalid order Date month.");
        $v->isOk ($oyear, "num", 1,4, "Invalid order Date Year.");
        $v->isOk ($invday, "num", 1,2, "Invalid invoice Date day.");
        $v->isOk ($invmon, "num", 1,2, "Invalid invoice Date month.");
        $v->isOk ($invyear, "num", 1,4, "Invalid invoice Date Year.");

		if(isset($accpaid)){
                $v->isOk ($accpaid, "num", 1, 255, "Invalid account paid to.");
        }else{
                return "<li>ERROR : There is no account paid selected";
        }


        # mix dates
        $orddate = $oday."-".$omon."-".$oyear;
        $invdate = $invday."-".$invmon."-".$invyear;

        if(!checkdate($omon, $oday, $oyear)){
                $v->isOk ($orddate, "num", 1, 1, "Invalid order date.");
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

        # Query server for customer info
        db_connect();
        $sql = "SELECT * FROM customers WHERE cusnum = '$cusnum'";
		$CustRslt = db_exec ($sql) or errDie ("Unable to view customers");
		$numrows = pg_numrows ($CustRslt);
		if ($numrows < 1) {
			return "<li class=err>Invalid Customer Number.";
		}
        $cust = pg_fetch_array($CustRslt);
        foreach($cust as $key => $value){
                $$key = $value;
        }

        // Layout
        $view = "<h3>New Stock Invoice</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <input type=hidden name=cusnum value='$cusnum'>
        <input type=hidden name=orddate value='$orddate'>
        <input type=hidden name=invdate value='$invdate'>
        <input type=hidden name=accpaid value='$accpaid'>
        <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='25%' border=0>
	        <tr><th class=h4>CUSTOMER ADDRESS</th></tr>
	        <tr class='bg-odd'><td>
                        <b>$cusname</b>
                        <p>$addr1<br>$addr2<br>$addr3
                        <p>Tel : $tel<br>Fax : $fax<br>$email<br>
                </td></tr>
	</table>
         <br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=70%>
        <tr><th>Select</th><th>Description</th><th>Quantity</th><th>Unit Cost Price</th><th>Unit Selling Price</th></tr>";

		# Query server
		db_connect();
		$i = 0;
		$sql = "SELECT * FROM stock ORDER BY stkcod ASC";
		$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
		if (pg_numrows ($stkRslt) < 1) {
			return "<li>There are no stock in Cubit.";
		}
		while ($stk = pg_fetch_array ($stkRslt)) {
				$view .= "<tr class='bg-even'><td align=center><input type=checkbox name=sel[$i] value='$stk[stkid]'></td><td>$stk[stkdes]</td>
                <td align=center><input type=text name='qty[]' size=5> x $stk[suom]</td><td align=center>".CUR." <input type=text name='cost[]' size=10></td>
                <td align=center>".CUR." <input type=text name=unitcost[] value='$stk[selamt]' size=10></td></td></tr>";
				$i++;
		}
		$view .="<tr><td align=right colspan=1><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td colspan=2><input type=submit value='Add Invoice &raquo'></td></tr>
		</table></form>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='customers-new.php'>New Customer</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
		</tr>
		</table>";

        return $view;
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
        $v->isOk ($cusnum, "num", 1,255, "Invalid customer number.");
        $v->isOk ($orddate, "date", 1,14, "Invalid order Date.");
        $v->isOk ($invdate, "date", 1,14, "Invalid invoice Date.");
        $v->isOk ($accpaid, "num", 1, 255, "Invalid account paid to.");

        if(isset($sel)){
                foreach($sel as $key => $value){
                        $v->isOk ($qty[$key], "num", 1, 20, "Invalid quantity : [$key].");
                        $v->isOk ($cost[$key], "float", 1, 20, "Invalid unit cost price : [$key].");
                        $v->isOk ($unitcost[$key], "float", 1, 20, "Invalid unit cost selling price : [$key].");
                }
        }else{
                return "<li>Please select the items. (click checkboxes on the left)";
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

        # Query server for customer info
        db_connect();
        $sql = "SELECT * FROM customers WHERE cusnum = '$cusnum'";
		$CustRslt = db_exec ($sql) or errDie ("Unable to view customers");
		$numrows = pg_numrows ($CustRslt);
		if ($numrows < 1) {
			return "<li class=err>Invalid Customer Number.";
		}
        $cust = pg_fetch_array($CustRslt);
        foreach($cust as $key => $value){
                $$key = $value;
        }

        # get paid account name
        core_connect();
        $sql = "SELECT accname FROM accounts WHERE accid = '$accpaid'";
        $paccRslt = db_exec($sql);
        $pacc = pg_fetch_array($paccRslt);

		// layout
		$confirm =
		"<h3>New Stock Invoice</h3>
		<h4>Confirm entry</h4>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=45%>
		<form action='".SELF."' method=post>
		<input type=hidden name=key value=write>
		<input type=hidden name=cusnum value='$cusnum'>
		<input type=hidden name=orddate value='$orddate'>
		<input type=hidden name=invdate value='$invdate'>
		<input type=hidden name=accpaid value='$accpaid'>
		<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
		<tr class='bg-odd'><td width=70%>Customer Name</td><td valign=center>$cusname</td></tr>
		<tr class='bg-even'><td valign=top>Customer Address</td><td valign=center>$addr1<br>$addr2<br>$addr3</td></tr>
		<tr class='bg-odd'><td>Telephone No.</td><td valign=center>$tel</td></tr>
		<tr class='bg-even'><td>Fax No.</td><td valign=center>$fax</td></tr>
		<tr class='bg-odd'><td>E-mail Address</td><td valign=center>$email</td></tr>
		<tr class='bg-even'><td>Order Date</td><td valign=center>$orddate</td></tr>
		<tr class='bg-odd'><td>invdate</td><td valign=center>$invdate</td></tr>
		<tr class='bg-even'><td>Account paid to</td><td valign=center>$pacc[accname]</td></tr>
		</table><br>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=70%>
		<tr><th width=40%>Description</th><th width=20%>Quantity</th><th width=20%>Unit Cost Price</th><th width=20%>Unit Selling Price</th></tr>";

				# input boxes
				db_connect();
				foreach($sel as $key => $value){
						$sql = "SELECT * FROM stock WHERE stkid = '$value'";
						$stkRslt = db_exec ($sql) or errDie ("Unable to retrieve stocks from database.");
						$stk = pg_fetch_array($stkRslt);

						$confirm .= "<tr class='bg-even'><td align=center><input type=hidden name=accid[] value='$stk[accid]'><input type=hidden name=sel[] value='$value'>
						<input type=hidden name=descript[] value='$stk[stkdes]'>$stk[stkdes]</td><td align=center><input type=hidden name=qty[] value='$qty[$key]'>$qty[$key] x $stk[suom]</td>
						<td align=center><input type=hidden name=cost[] value='$cost[$key]'>".CUR." ".sprintf("%01.2f",$cost[$key])."</td>
						<td align=center><input type=hidden name=unitcost[] value='$unitcost[$key]'>".CUR." ".sprintf("%01.2f",$unitcost[$key])."</td></tr>";
				}

		$confirm .="<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=left><input type=submit value='Add Invoice &raquo'></td></tr>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='customers-new.php'>New Customer</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
		</tr>
		</table>
		</form>
		</table>";

	return $confirm;
}

# write
function write($_POST)
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
        $v->isOk ($cusnum, "num", 1,255, "Invalid customer number.");
        $v->isOk ($orddate, "date", 1,14, "Invalid order Date.");
        $v->isOk ($invdate, "date", 1,14, "Invalid invoice Date.");
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

        # Query server for customer info
        db_connect();
        $sql = "SELECT * FROM customers WHERE cusnum = '$cusnum'";
		$CustRslt = db_exec ($sql) or errDie ("Unable to view customers");
		$numrows = pg_numrows ($CustRslt);
		if ($numrows < 1) {
			return "<li class=err>Invalid Customer Number.";
		}

        $cust = pg_fetch_array($CustRslt);
        foreach($cust as $key => $value){
                $$key = $value;
        }

        # caclulate sub totals
        $SUBTOT = 0; //total of subtotals
        $COSTOT = 0; // Cost of stock sold
        foreach($sel as $key => $value){
                $SUB[$key] = sprintf("%01.2f", $qty[$key] * $unitcost[$key]);
                $COST[$key] = sprintf("%01.2f", $qty[$key] * $cost[$key]);
                $COSTOT += $COST[$key];
                $SUBTOT += $SUB[$key];
        }

        #calculate vat and grand total
        $vat = sprintf("%01.2f", (TAX_VAT/100));
        $VAT = sprintf("%01.2f", ($vat * $SUBTOT));
        $GRDTOT = sprintf("%01.2f", ($SUBTOT + $VAT));

         # Join each item into a string and put them into an array
        foreach($sel as $key => $value){
                $items[$key] = "$descript[$key] [|] $qty[$key] [|] $unitcost[$key] [|] $SUB[$key]";
        }

        # Implode items into one order
        $orddes = implode("\n", $items);

        # write invoice to DB
        db_connect();
        $sql = "INSERT INTO invoices(cusname, addr1, addr2, addr3, tel, fax, email, orddate, invdate, orddes, grdtot, salesrep, accpaid) ";
        $sql .= "VALUES('$cusname', '$addr1', '$addr2', '$addr3', '$tel', '$fax', '$email', '$orddate', '$invdate', '$orddes', '$GRDTOT', '".USER_NAME."', '$accpaid')";
        $rslt = db_exec($sql) or errDie("Unable to insert invoice to Cubit.",SELF);

        # get next ordnum
        $ordnum = pglib_lastid ("invoices", "ordnum");

        # get cost of sales account
        $cosacc = gethook("accnum", "salesacc", "name", "Cost Of Sales");

        # get income account
        $incomeacc = gethook("accnum", "salesacc", "name", "Income");

		$refnum = getrefnum($invdate);

        # credit income debit acc paid
        writetrans($accpaid, $incomeacc, $invdate, $refnum, $GRDTOT, "Sales Income received.");

        # credit stock acc and cos acc
		foreach($sel as $key => $value){
			db_connect();
			$sql = "UPDATE stock SET units = (units - '$qty[$key]'), csamt = (csamt - '$COST[$key]') WHERE stkid = '$sel[$key]'";
			$rslt = db_exec($sql) or errDie("Unable to insert stock to Cubit.",SELF);

			# date format
        	$sdate = explode("-", $invdate);
        	$sdate = $sdate[2]."-".$sdate[1]."-".$sdate[0];

			$Sql = "INSERT INTO stock_sold (stkid, units, amount, selamt, sdate) VALUES('$sel[$key]', '$qty[$key]', '$COST[$key]', '$SUB[$key]', '$sdate')";
			$rslt = db_exec($Sql) or errDie("Unable to insert stock record to Cubit.",SELF);

			writetrans($cosacc, $accid[$key], $invdate, $refnum, round($COST[$key], 2), "Cost of Sales.");
		}

        // invoice design
        $printInv ="<center><table border=0 cellpadding=5 cellspacing=0 width='91%'>
        <tr><td width='35%' align=center>
	        <img src='".COMP_LOGO."' width=230 height=47 alt='".COMP_NAME."'>
        </td><td align=right>
	        ".COMP_ADDRESS."
	        <br>Tel : ".COMP_TEL."
	        <br>Fax : ".COMP_FAX."
        </td><tr>
        <tr><td width='35%' valign=top>
	        <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%' border=1>
	        <tr><th class=h4>CUSTOMER ADDRESS</th></tr>
	        <tr><td align=center>
        		<table border=0 cellpadding=10 cellspacing=0>
		        <tr><td>
			        <b>$cusname</b>
			        <p>$addr1<br>$addr2<br>$addr3
                                <p>$tel<br>$fax<br>$email<br>
		        </td></tr>
		        </table>
	        </td></tr>
	        </table>
        </td><td>
        </td></tr>
        <tr><td colspan=2>
        	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%' border=1>
        	<tr><th>INVOICE No.</th><th>SALESPERSON</th><th>ORDER DATE</th><th>INVOICE DATE</th></tr>
        	<tr><td align=center>$ordnum</td><td align=center>".USER_NAME."</td><td align=center>$orddate</td><td align=center>$invdate</td></tr>
        	</table>
        </td></tr>
        </table>
        <br>
        <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='90%' border=1>
        <tr><th>CODE</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT COST</th><th width=20%>SUBTOTAL</th></tr>";

        foreach($qty as $key => $value){
                $printInv .= "<tr><td>0000$key</td><td>".stripslashes($descript[$key])."</td><td>$qty[$key]</td><td>$unitcost[$key]</td><td align=right>".CUR." $SUB[$key]</td></tr>";
        }

        $printInv .= "<tr><td colspan=4 align=right><b>SUBtotal</b></td><td align=right>$SUBTOT</td></tr>
        <tr><td colspan=4 align=right><b>VAT @ ".TAX_VAT."%</b></td><td align=right>$VAT</td></tr>
        <tr><td colspan=4 align=right><b>GRAND total</b></td><td align=right><b>$GRDTOT</b></td></tr>
        </table></center>
        <blockquote> <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=1>
        <tr><th>VAT No.</th><td align=center>".COMP_VATNO."</td></tr>
        </table>";
        $OUTPUT = $printInv;

        #  Print the invoice and exit
        require ("tmpl-print.php");
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
