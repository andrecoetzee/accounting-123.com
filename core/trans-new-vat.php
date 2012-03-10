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


# trans-new.php :: debit-credit Transaction
#
##

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

                case "details":
                        if(isset($HTTP_POST_VARS['details'])){
                                $OUTPUT = details($HTTP_POST_VARS);
                        }else{
                                $OUTPUT = details2($HTTP_POST_VARS);
                        }
			break;

                default:
			$OUTPUT = slctacc();
	}
} else {
        # Display default output
        $OUTPUT = slctacc();
}

# get templete
require("template.php");

# Select Accounts
function slctacc()
{
        // connect
        db_conn(PRD_DB);

		$refnum = getrefnum();
/*refnum*/
        // Accounts (debit)
        $view = "<center>
        <h3> Journal transaction </h3>
        <br><br>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=details>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
        <tr><th>Field</th><th>Value</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td><input type=text size=2 name=day maxlength=2  value='".date("d")."'>-<input type=text size=2 name=mon maxlength=2  value='".date("m")."'>-<input type=text size=4 name=year maxlength=4 value='".date("Y")."'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Reference Number</td><td><input type=text size=10 name=refnum value='".($refnum++)."'></td></tr>
        <tr><td><br></td></tr>
        <tr><td align=center>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><td><h4>Debit</h4></td></tr>
                <tr><th>Select Account</th></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'>
                <td valign=center>
                <select name='dtaccid'>";
                core_connect();
                $sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
                $accRslt = db_exec($sql);
                if(pg_numrows($accRslt) < 1){
                        return "<li>There are No accounts in Cubit.";
                }
                while($acc = pg_fetch_array($accRslt)){
                        $view .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
                }
        $view .="</select></td></tr>
                </table>
        </td>
        <td align=center>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                <tr><td><h4>Credit</h4></td></tr>
                <tr><th>Select Account</th></tr>
                <tr bgcolor='".TMPL_tblDataColor2."'>
                <td valign=center>
                <select name=ctaccid>";
                $sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
                $accRslt = db_exec($sql);
                if(pg_numrows($accRslt) < 1){
                        return "<li>There are No accounts in Cubit.";
                }
                while($acc = pg_fetch_array($accRslt)){
                        $view .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
                }
        $view .="</select>
                </td><td><input name=details type=submit value='Enter Details >'></td></tr>
                </table>
        </td></tr>
        </table><br><br><br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
        <td align=center>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                        <tr><td><h4>Debit</h4></td></tr>
                        <tr><th>Account number</th></tr>
                        <tr bgcolor='".TMPL_tblDataColor2."'><td valign=center><input type=text name=dtaccnum size=20></td></tr>
                </table>
        </td>
        <td align=center>
                <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
                        <tr><td><h4>Credit</h4></td></tr>
                        <tr><th>Account number</th></tr>
                        <tr bgcolor='".TMPL_tblDataColor2."'>
                <td valign=center><input type=text name=ctaccnum size=20></td><td><input type=submit value='Enter Details >'></td></tr></table>
        </td></tr>
        </table>
        <br>
        <input type=button value='< Go Back' onClick='javascript:history.back();'>
        </form>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
	<tr><td><br></td></tr>
	<tr><th>Quick Links</th></tr>
	<tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table></center>";

return $view;
}

# Enter Details of Transaction
function details($HTTP_POST_VARS)
{
// Sanity Checking
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
        $v->isOk ($day, "num", 1,2, "Invalid to Date day.");
        $v->isOk ($mon, "num", 1,2, "Invalid to Date month.");
        $v->isOk ($year, "num", 1,4, "Invalid to Date Year.");
        $date = $day."-".$mon."-".$year;
        if(!checkdate($mon, $day, $year)){
                $v->isOk ($date, "num", 1, 1, "Invalid date.");
        }
        $v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
        $v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");

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

        // Account numbers
        $dtaccRs = get("core","*","accounts","accid",$dtaccid);
        $dtacc  = pg_fetch_array($dtaccRs);
        $ctaccRs = get("core","*","accounts","accid",$ctaccid);
        $ctacc  = pg_fetch_array($ctaccRs);

		# Check for Creditors and Debtors Control
		if(Control($dtaccid, $ctaccid, $refnum, $day, $mon, $year))
			return Control($dtaccid, $ctaccid, $refnum, $day, $mon, $year);

        // Deatils
        $details = "
        <h3> Journal transaction details</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <input type=hidden name='date' value='$date'>
        <input type=hidden name='ctaccid' value='$ctaccid'>
        <input type=hidden name='dtaccid' value='$dtaccid'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
        <tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
        <tr><td><br></td></tr>
        <tr><td><br></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td valign=center>$date</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Reference No.</td><td valign=center><input type=text size=20 name=refnum value='$refnum'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td valign=center>".CUR."<input type=text size=20 name=amount></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Charge VAT </td><td><input type=radio name=chrgvat value=yes>Yes &nbsp;&nbsp; <input type=radio name=chrgvat value=no checked=yes>No</td>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Transaction Details</td><td valign=center><textarea cols=20 rows=5 name=details></textarea></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Person Authorising</td><td valign=center><input type=hidden size=20 name=author value=".USER_NAME.">".USER_NAME."</td></tr>
        <tr><td><input type=button value=Back OnClick='javascript:history.back()'></td><td valign=center><input type=submit value='Record Transaction'></td></tr>
        </form>
        </table>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
        <tr><td>
        <br>
        </td></tr>
        <tr><th>Quick Links</th></tr>
        <tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $details;
}

# Enter Details of Transaction
function details2($HTTP_POST_VARS)
{
        // Sanity Checking
        # Get vars
		foreach ($HTTP_POST_VARS as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();
        $v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
        $v->isOk ($day, "num", 1,2, "Invalid to Date day.");
        $v->isOk ($mon, "num", 1,2, "Invalid to Date month.");
        $v->isOk ($year, "num", 1,4, "Invalid to Date Year.");
        $date = $day."-".$mon."-".$year;
        if(!checkdate($mon, $day, $year)){
                $v->isOk ($date, "num", 1, 1, "Invalid date.");
        }
        $v->isOk ($dtaccnum, "string", 1, 50, "Invalid Account number  to be Debited.");
        $v->isOk ($ctaccnum, "string", 1, 50, "Invalid Account number to be Credited.");

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

        $dtaccnum = explode("/", rtrim($dtaccnum));
        $ctaccnum = explode("/", rtrim($ctaccnum));

        if(count($dtaccnum) < 2){
                // account numbers
                $dtaccRs = get("core","*","accounts","topacc",$dtaccnum[0]."' AND accnum = '000");
                if(pg_numrows($dtaccRs) < 1){
                        return "<li> Accounts number : $dtaccnum[0] does not exist";
                }
                $dtacc  = pg_fetch_array($dtaccRs);
        }else{
                // account numbers
                $dtaccRs = get("core","*","accounts","topacc","$dtaccnum[0]' AND accnum = '$dtaccnum[1]");
                if(pg_numrows($dtaccRs) < 1){
                        return "<li> Accounts number : $dtaccnum[0]/$dtaccnum[1] does not exist";
                }
                $dtacc  = pg_fetch_array($dtaccRs);
        }

        if(count($ctaccnum) < 2){
                # get top level account
                $ctaccRs = get("core","*","accounts","topacc",$ctaccnum[0]."' AND accnum = '000");
                if(pg_numrows($ctaccRs) < 1){
                        return "<li> Accounts number : $ctaccnum[0] does not exist";
                }
                $ctacc  = pg_fetch_array($ctaccRs);
        }else{
                # get low level account
                $ctaccRs = get("core","*","accounts","topacc","$ctaccnum[0]' AND accnum = '$ctaccnum[1]");
                if(pg_numrows($ctaccRs) < 1){
                        return "<li> Accounts number : $ctaccnum[0]/$ctaccnum[1] does not exist";
                }
				$ctacc  = pg_fetch_array($ctaccRs);
        }

		# Check for Creditors and Debtors Control
		if(Control($dtacc['accid'], $ctacc['accid']))
			return Control($dtacc['accid'], $ctacc['accid']);


        // Details
        $details ="
        <h3> Journal transaction details</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <input type=hidden name='date' value='$date'>
        <input type=hidden name='ctaccid' value='$ctacc[accid]'>
        <input type=hidden name='dtaccid' value='$dtacc[accid]'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
        <tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
        <tr><td><br></td></tr>
        <tr><td><br></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td valign=center>$date</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Reference No.</td><td valign=center><input type=text size=20 name=refnum value='$refnum'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td valign=center>".CUR."<input type=text size=20 name=amount></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Charge VAT </td><td><input type=radio name=chrgvat value=yes>Yes &nbsp;&nbsp; <input type=radio name=chrgvat value=no checked=yes>No</td>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Transaction Details</td><td valign=center><textarea cols=20 rows=5 name=details></textarea></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Person Authorising</td><td valign=center><input type=hidden size=20 name=author value=".USER_NAME.">".USER_NAME."</td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value=Back OnClick='javascript:history.back()'></td><td valign=center><input type=submit value='Record Transaction'></td></tr>
        </form>
        </table>
		<table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr><td><br></td></tr>
			<tr><th>Quick Links</th></tr>
			<tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr class=datacell><td align=center><a href='../main.php'>Main Menu</td></tr>
        </table>";

        return $details;
}

# Select vat accounts
function slctVatacc($HTTP_POST_VARS)
{
	// Sanity Checking
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
	$v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
	$v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
	$v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
	$v->isOk ($details, "string", 0, 255, "Invalid Details.");
	$v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");

	$datea = explode("-", $date);

	if(count($datea) == 3){
		if(!checkdate($datea[1], $datea[0], $datea[2])){
			$v->isOk ($date, "num", 1, 1, "Invalid date.");
		}
	}else{
		$v->isOk ($date, "num", 1, 1, "Invalid date.");
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


	# Account numbers
	$dtaccRs = get("core","*","accounts","accid",$dtaccid);
	$dtacc  = pg_fetch_array($dtaccRs);
	$ctaccRs = get("core","*","accounts","accid",$ctaccid);
	$ctacc  = pg_fetch_array($ctaccRs);

	$vatacc = "<select name='vataccid'>";
	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li>There are No accounts in Cubit.";
	}
	while($acc = pg_fetch_array($accRslt)){
			# Check Disable
			if(isDisabled($acc['accid']))
				continue;
			$vatacc .= "<option value='$acc[accid]'>$acc[topacc]/$acc[accnum] - $acc[accname]</option>";
	}
	$vatacc .= "</select>";

	// Details
	$slctacc ="<center><h3>Journal Transaction VAT Details</h3>
	<h4>Select VAT Accounts</h4>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=confirm>
	<input type=hidden name='dtaccid' value='$dtaccid'>
	<input type=hidden name='ctaccid' value='$ctaccid'>
	<input type=hidden name=dtaccname value='$dtacc[accname]'>
	<input type=hidden name=ctaccname value='$ctacc[accname]'>
	<input type=hidden name=date value='$date'>
	<input type=hidden name=refnum value='$refnum'>
	<input type=hidden name=amount value='$amount'>
	<input type=hidden name=details value='$details'>
	<input type=hidden name=author value='$author'>
	<input type=hidden name=chrgvat value='$chrgvat'>
 	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center width=500>
	<tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
    <tr bgcolor='".TMPL_tblDataColor1."'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
	<tr><td><br></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td valign=top>VAT Deductable Account</td><td><input type=radio name=vatdedacc value='$dtaccid' checked=yes>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]<br><input type=radio name=vatdedacc value='$ctaccid'>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Account</td><td>$vatacc</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>VAT Inclusive </td><td><input type=radio size=20 name=vatinc value=yes checked=yes>Yes(Amount Includes VAT) &nbsp;&nbsp;<input type=radio size=20 name=vatinc value=no>No(Add VAT to Amount)</td></tr>
	<tr><td><br></td></tr>
	<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Continue &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='ledger-view.php'>View High Speed Input Ledgers</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $slctacc;
}

# Confirm
function confirm($HTTP_POST_VARS)
{
		// Sanity Checking
		# Get vars
		foreach ($HTTP_POST_VARS as $key => $value) {
			$$key = $value;
		}

		# Redirect if must chrgvat
		if($chrgvat == 'yes' && !isset($vataccid)){
			return slctVatacc($HTTP_POST_VARS);
		}

		# validate input
		require_lib("validate");
		$v = new  validate ();
        $v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
        $v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
        $v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
        $v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
        $v->isOk ($details, "string", 0, 255, "Invalid Details.");
        $v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");
		$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
		if($chrgvat == 'yes'){
			$v->isOk ($vataccid, "num", 1, 50, "Invalid VAT Account number.");
			$v->isOk ($vatdedacc, "num", 1, 50, "Invalid VAT Deductable Account number.");
			$v->isOk ($vatinc, "string", 1, 3, "Invalid vat inclusive selection.");
		}

        $datea = explode("-", $date);

        if(count($datea) == 3){
                if(!checkdate($datea[1], $datea[0], $datea[2])){
                        $v->isOk ($date, "num", 1, 1, "Invalid date.");
                }
        }else{
                $v->isOk ($date, "num", 1, 1, "Invalid date.");
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

		$amount = sprint($amount);

		if($chrgvat == 'yes'){
			$vataccRs = get("core", "*", "accounts", "accid", $vataccid);
			$vatacc  = pg_fetch_array($vataccRs);
			$vatin = ucwords($vatinc);
			$VATP = TAX_VAT;

			# if vat must be charged
			if($vatinc == "no"){
				$vatamt = sprint((($VATP/100) * $amount));
				$totamt = sprint($amount + $vatamt);
			}else{
				$vatamt = sprint((($amount/($VATP + 100)) * $VATP));
				$totamt = sprint($amount);
			}
			$vataccnum = "<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Amount</td><td><input type=hidden name=vatinc value='$vatinc'><input type=hidden name=vatamt value='$vatamt'>$vatamt</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Total Transaction Amount</td><td>$totamt</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Account</td><td><input type=hidden name=vataccid value='$vataccid'><input type=hidden name=vatdedacc value='$vatdedacc'>$vatacc[topacc]/$vatacc[accnum] - $vatacc[accname]</td></tr>";
		}else{
			$vataccnum = "";
		}

		$dtaccRs = get("core","*","accounts","accid",$dtaccid);
        $dtacc  = pg_fetch_array($dtaccRs);
        $ctaccRs = get("core","*","accounts","accid",$ctaccid);
        $ctacc  = pg_fetch_array($ctaccRs);

        $confirm =
        "<h3>Record Journal transaction</h3>
        <h4>Confirm entry</h4>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <input type=hidden name='dtaccid' value='$dtaccid'>
        <input type=hidden name='ctaccid' value='$ctaccid'>
        <input type=hidden name=dtaccname value='$dtacc[accname]'>
        <input type=hidden name=ctaccname value='$ctacc[accname]'>
        <input type=hidden name=date value='$date'>
        <input type=hidden name=refnum value='$refnum'>
        <input type=hidden name=amount value='$amount'>
		<input type=hidden name=chrgvat value='$chrgvat'>
        <input type=hidden name=details value='$details'>
        <input type=hidden name=author value='$author'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
        <tr><td><br></td></tr>
        <tr><td><br></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td>$date</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Reference number</td><td>$refnum</td></tr>
		<tr><td><br></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td>$amount</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Charge VAT </td><td>$chrgvat</td></tr>
		$vataccnum
		<tr><td><br></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Details</td><td>$details</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Authorising Person</td><td>$author</td></tr>
		<tr><td><br></td></tr>
        <tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Confirm Transaction &raquo'></td></tr>
        </form></table>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
        <tr><td>
        <br>
        </td></tr>
        <tr><th>Quick Links</th></tr>
        <tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        <tr class=datacell><td align=center><a href='../main.php'>Main Menu</td></tr>
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
        $v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
        $v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
        $v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
        $v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
        $v->isOk ($details, "string", 0, 255, "Invalid Details.");
        $v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");
		$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat option.");
		if($chrgvat == 'yes'){
			$v->isOk ($vataccid, "num", 1, 50, "Invalid VAT Account number.");
			$v->isOk ($vatdedacc, "num", 1, 50, "Invalid VAT Deductable Account number.");
			$v->isOk ($vatamt, "float", 1, 11, "Invalid VAT Amount.");
			$v->isOk ($vatinc, "string", 1, 3, "Invalid VAT inclusive selection.");
		}

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

		// Accounts details
        $dtaccRs = get("core","*","accounts","accid",$dtaccid);
        $dtacc  = pg_fetch_array($dtaccRs);
        $ctaccRs = get("core","*","accounts","accid",$ctaccid);
        $ctacc  = pg_fetch_array($ctaccRs);

		if($chrgvat == 'yes'){
			if($vatinc == 'yes'){
				# Calculate amount
				$amt = sprint($amount - $vatamt);
				$totamt = sprint($amount);
			}else{
				# Calculate amount
				$amt = sprint($amount);
				$totamt = sprint($amount + $vatamt);
			}
			# Check VAt Deductable account
			if($vatdedacc == $dtaccid){
				writetrans($vataccid, $ctaccid, $date, $refnum, $vatamt, $details."  VAT");
				writetrans($dtaccid, $ctaccid, $date, $refnum, $amt, $details);
			}elseif($led['vatdedacc'] == $led['ctaccid']){
				writetrans($dtaccid, $vataccid, $date, $refnum, $vatamt, $details."  VAT");
				writetrans($dtaccid, $ctaccid, $date, $refnum, $amt, $details);
			}
		}else{
			$totamt = sprint($amount);
			# Write normal transaction
			writetrans($dtaccid,$ctaccid, $date, $refnum, $totamt, $details);
		}

		if($chrgvat == 'yes'){
			$vataccRs = get("core", "*", "accounts", "accid", $vataccid);
			$vatacc  = pg_fetch_array($vataccRs);

			$vataccnum = "<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Amount</td><td>$vatamt</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Total Transaction Amount</td><td><b>$totamt</b></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Account</td><td>$vatacc[topacc]/$vatacc[accnum] - $vatacc[accname]</td></tr>";
		}else{
			$vataccnum = "";
		}

		// Start layout
        $write = "<center>
        <h3>Journal transaction has been recorded</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
			<tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
			<tr><td><br></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Amount</td><td><b>$amount</b></td></tr>
			$vataccnum
		</table>
        <br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
			<tr><th>Quick Links</th></tr>
			<tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $write;
}
?>
