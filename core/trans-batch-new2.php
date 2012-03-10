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

        # get last ref number
		$refnum = getrefnum();
/*refnum*/
        // Accounts (debit)
        $view = "<center>
        <h3>Add Journal transaction to batch</h3>
        <br><br>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=details>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
        <tr><th>Field</th><th>Value</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td><input type=text size=2 name=day maxlength=2  value='".date("d")."'>-<input type=text size=2 name=mon maxlength=2  value='".date("m")."'>-<input type=text size=4 name=year maxlength=4 value='".date("Y")."'></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Reference Number</td><td><input type=text size=10 name=refnum value='".($refnum++)."'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Select Account</td>
                <td valign=center>
                <select name='maccid'>";
                core_connect();
                $sql = "SELECT * FROM accounts ORDER BY accname ASC";
                $accRslt = db_exec($sql);
                if(pg_numrows($accRslt) < 1){
                        return "<li>There are No accounts in Cubit.";
                }
                while($acc = pg_fetch_array($accRslt)){
                        $view .= "<option value='$acc[accid]'>$acc[accname]</option>";
                }
        		$view .="</select></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Transaction</td><td><input type=radio name=tran value=dt checked=yes> Debit <input type=radio name=tran value=dt>Credit</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Number Of Contrast Accounts</td><td><input type=text size=5 name=noacc value='1'></td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='< Go Back' onClick='javascript:history.back();'><td><input type=submit value='Enter Details >'></td></tr></table>
        </table>
        <br>
        </form>
        <table border=0 cellpadding='2' cellspacing='1' width=15%>
			<tr><th>Quick Links</th></tr>
			<tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr class=datacell><td align=center><a href='../main.php'>Main Menu</td></tr>
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
        $v->isOk ($maccid, "num", 1, 50, "Invalid Account to be Debited.");
		$v->isOk ($noacc, "num", 1, 10, "Invalid Number of Costrast accounts.");

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

        // account numbers
        $maccRs = get("core","*","accounts","accid",$dtaccid);
        $dtacc  = pg_fetch_array($dtaccRs);
        $ctaccRs = get("core","*","accounts","accid",$ctaccid);
        $ctacc  = pg_fetch_array($ctaccRs);

        // Deatils
        $details ="
        <h3>Add Journal transaction to batch</h3>
        <h4>Enter Details</h4>
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
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Transaction Details</td><td valign=center><textarea cols=20 rows=5 name=details></textarea></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Person Authorising</td><td valign=center><input type=hidden size=20 name=author value=".USER_NAME.">".USER_NAME."</td></tr>
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
        <tr class=datacell><td align=center><a href='../main.php'>Main Menu</td></tr>
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

        // Details
        $details ="
        <h3>Add Journal transaction to batch</h3>
        <h4>Enter Details</h4>
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
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Transaction Details</td><td valign=center><textarea cols=20 rows=5 name=details></textarea></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Person Authorising</td><td valign=center><input type=hidden size=20 name=author value=".USER_NAME.">".USER_NAME."</td></tr>
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
        <tr class=datacell><td align=center><a href='../main.php'>Main Menu</td></tr>
        </table>

        ";

        return $details;
}

# Confirm
function confirm($HTTP_POST_VARS)
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

        //processes
        db_conn(PRD_DB);
        $confirm =
        "<h3>Record Journal transaction to batch</h3>
        <h4>Confirm entry</h4>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>";

        $dtaccRs = get("core","*","accounts","accid",$dtaccid);
        $dtacc  = pg_fetch_array($dtaccRs);
        $ctaccRs = get("core","*","accounts","accid",$ctaccid);
        $ctacc  = pg_fetch_array($ctaccRs);

        $confirm .="
        <input type=hidden name='dtaccid' value='$dtaccid'>
        <input type=hidden name='ctaccid' value='$ctaccid'>
        <input type=hidden name=dtaccname value='$dtacc[accname]'>
        <input type=hidden name=ctaccname value='$ctacc[accname]'>
        <input type=hidden name=date value='$date'>
        <input type=hidden name=refnum value='$refnum'>
        <input type=hidden name=amount value='$amount'>
        <input type=hidden name=details value='$details'>
        <input type=hidden name=author value='$author'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
        <tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
        <tr><td><br></td></tr>
        <tr><td><br></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Date</td><td>$date</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Reference number</td><td>$refnum</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Amount</td><td>".CUR." $amount</td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Details</td><td>$details</td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Authorising Person</td><td>$author</td></tr>
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
// Sanity Checking and get vars(Respectively)
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($ctaccid, "num", 1, 50, "Invalid Account to be Credited.");
        $v->isOk ($dtaccid, "num", 1, 50, "Invalid Account to be Debited.");
        $v->isOk ($ctaccname, "string", 1, 255, "Invalid Account name to be Credited.");
        $v->isOk ($dtaccname, "string", 1, 255, "Invalid Account name to be Debited.");
        $v->isOk ($refnum, "num", 1, 10, "Invalid Reference number.");
        $v->isOk ($amount, "float", 1, 20, "Invalid Amount.");
        $v->isOk ($details, "string", 0, 255, "Invalid Details.");
        $v->isOk ($author, "string", 1, 30, "Invalid Authorising person name.");

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

        # Format date
        $date = explode("-", $date);
        $date = $date[2]."-".$date[1]."-".$date[0];

        // Insert the records into the batch table
        core_connect();

        $sql = "INSERT INTO batch(date, debit, credit, refnum, amount, author, details) VALUES('$date', '$dtaccid', '$ctaccid', '$refnum', '$amount', '$author', '$details')";
        $transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

        // Start layout
        $write ="
        <center>
        <h3>Journal transaction have been recorded to a batch file</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
        <tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
        <tr><td><br></td></tr>
        <tr colspan=2><td><h4>Amount</h4></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><b>".CUR." $amount</b></td></tr>
        </table>
        <br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
        <tr><th>Quick Links</th></tr>
        <tr class=datacell><td align=center><a href='trans-batch-new.php'>Add Journal Transaction to batch</td></tr>
        <tr class=datacell><td align=center><a href='batch-view.php'>View batch file</td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        <script>document.write(getQuicklinkSpecial());</script>
        <script>document.write(getQuicklinkSpecial());</script>
        <tr class=datacell><td align=center><a href='../main.php'>Main Menu</td></tr>
        </table>";

        return $write;
}
?>
