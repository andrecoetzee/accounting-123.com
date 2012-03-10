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
                        $OUTPUT = details($HTTP_POST_VARS);
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
        db_conn(PRD_DB);
        # get last ref number
		$refnum = getrefnum();
/*refnum*/
        // Accounts (debit)
        $view = "<center>
        <h3>Double Entry System Transactions</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
        <tr><th>Account</th><th>DAte</th><th>Ref num</th><th>Debit</th><th>Credit</th><th>Description</th></tr>";

        for($i=0; $i != 5; $i++){
                $view .= "<tr bgcolor=".TMPL_tblDataColor2."><td valign=center>
                                <select name='accid[]'>";
                                core_connect();
                                $sql = "SELECT * FROM accounts ORDER BY topacc, accnum ASC";
                                $accRslt = db_exec($sql);
                                if(pg_numrows($accRslt) < 1){
                                        return "<li>There are No accounts in Cubit.";
                                }
                                while($acc = pg_fetch_array($accRslt)){
                                        $view .= "<option value='$acc[accid]'>$acc[accname]</option>";
                                }
                                $view .="</select></td>
                                <td valign=center><input type=text size=2 name=day[] maxlength=2  value='".date("d")."'>-<input type=text size=2 name=mon[] maxlength=2  value='".date("m")."'>-<input type=text size=4 name=year[] maxlength=4 value='".date("Y")."'></td>
                                <td><input type=text size=10 name=refnum[] value='$refnum'></td>
                                <td>".CUR." <input type=text size=10 name=debit[]></td>
                                <td>".CUR." <input type=text size=10 name=credit[]></td>
                                <td><input type=text size=30 name=descript[]></td>
                         </tr>";
        }
        $view .= "
        <tr><td><input type=button value=Back OnClick='javascript:history.back()'></td><td valign=center colspan=2><input type=submit value='Record Transaction'></td></tr>
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

        return $view;
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
        foreach($accid as $key => $value){
                if($debit[$key] > 0 || $credit[$key] > 0){
                        $v->isOk ($accid[$key], "num", 1, 50, "Invalid Account ID.[$key]");
                        $v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
                        $v->isOk ($debit[$key], "float", 0, 20, "Invalid debit amount.[$key]");
                        $v->isOk ($credit[$key], "float", 0, 20, "Invalid credit amount.[$key]");
                        $v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
                        $date[$key] = $day[$key]."-".$mon[$key]."-".$year[$key];
                        if(!checkdate($mon[$key], $day[$key], $year[$key])){
                                $v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
                        }
                }
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

        # accnums
        foreach($accid as $key => $value){
                if($debit[$key] > 0 || $credit[$key] > 0){
                        # get account to be credited
                        $accRs = get("core","*","accounts","accid",$accid[$key]);
                        if(pg_numrows($accRs) < 1){
                                return "<li> Accounts to be credited does not exist";
                        }
                        $acc[$key]  = pg_fetch_array($accRs);
                }
        }

        $confirm =
        "<center>
        <h3>Record Double Entry System Transactions</h3>
        <h4>Confirm entry</h4>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
        <tr><th>Account</th><th>Date</th><th>Ref num</th><th>Debit</th><th>Credit</th><th>Description</th></tr>";

         foreach($accid as $key => $value){
                if($debit[$key] > 0 || $credit[$key] > 0){
                        $confirm .= "<tr bgcolor=".TMPL_tblDataColor1.">
                                <td valign=center><input type=hidden name='accid[]' value='".$acc[$key]['accid']."'>".$acc[$key]['accname']."</td>
                                <td><input type=hidden size=10 name=date[] value='$date[$key]'>$date[$key]</td>
                                <td><input type=hidden size=10 name=refnum[] value='$refnum[$key]'>$refnum[$key]</td>
                                <td><input type=hidden name=debit[] value='$debit[$key]'>$debit[$key]</td>
                                <td><input type=hidden name=credit[] value='$credit[$key]'>$credit[$key]</td>
                                <td><input type=hidden name=descript[] value ='$descript[$key]'>$descript[$key]</td>
                         </tr>";
                }
        }

        $confirm .= "
        <tr><td><br></td></tr>
        <tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right colspan=2><input type=submit value='Confirm Transaction &raquo'></td></tr>
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

# Confirm
function write($HTTP_POST_VARS)
{
// Sanity Checking
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        foreach($accid as $key => $value){
                $v->isOk ($accid[$key], "num", 1, 50, "Invalid Account ID.[$key]");
                $v->isOk ($date[$key], "date", 1, 14, "Invalid date.[$key]");
                $v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
                $v->isOk ($debit[$key], "float", 0, 20, "Invalid debit amount.[$key]");
                $v->isOk ($credit[$key], "float", 0, 20, "Invalid credit amount.[$key]");
                $v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
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

        # accnums
        foreach($accid as $key => $value){
                # get account to be credited
                $accRs = get("core","*","accounts","accid",$accid[$key]);
                if(pg_numrows($accRs) < 1){
                        return "<li> Accounts to be credited does not exist";
                }
                $acc[$key]  = pg_fetch_array($accRs);

                # format date
                $date[$key] = explode("-", $date[$key]);
                $date[$key] = $date[$key][2]."-".$date[$key][1]."-".$date[$key][0];

                db_conn("des");
                $sql = "INSERT INTO double_sys(accid, date, refnum, credit, debit, descript) VALUES('$accid[$key]', '$date[$key]', '$refnum[$key]', '$credit[$key]'+0, '$debit[$key]'+0, '$descript[$key]')";
                $Rs = db_exec($sql);
        }

        $confirm =
        "<center>
        <h3>Transactions Recorded</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
                <tr><th>Account</th><th>Date</th><th>Ref num</th><th>Debit</th><th>Credit</th><th>Description</th></tr>";

         foreach($accid as $key => $value){
                $confirm .= "<tr bgcolor=".TMPL_tblDataColor1.">
                                <td>".$acc[$key]['accname']."</td>
                                <td>$date[$key]</td>
                                <td>$refnum[$key]</td>
                                <td>$debit[$key]</td>
                                <td>$credit[$key]</td>
                                <td>$descript[$key]</td>
                         </tr>";
        }

        $confirm .= "
                <tr><td><br></td></tr>
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

	return $confirm;
}

# Write
function writes($HTTP_POST_VARS)
{
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        foreach($amount as $key => $value){
                $v->isOk ($ctaccid[$key], "num", 1, 50, "Invalid Account to be Credited.[$key]");
                $v->isOk ($dtaccid[$key], "num", 1, 50, "Invalid Account to be Debited.[$key]");
                $v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
                $v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[$key]");
                $v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
                $datea = explode("-", $date[$key]);
                if(count($datea) == 3){
                        if(!checkdate($datea[1], $datea[0], $datea[2])){
                                $v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
                        }
                }else{
                        $v->isOk ($date[$key], "num", 1, 1, "Invalid date.");
                }
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

        foreach($amount as $key => $value){
                // Accounts details
                $dtaccRs = get("core","accname, topacc, accnum","accounts","accid",$dtaccid[$key]);
                $dtacc[$key]  = pg_fetch_array($dtaccRs);
                $ctaccRs = get("core","accname, topacc, accnum","accounts","accid",$ctaccid[$key]);
                $ctacc[$key]  = pg_fetch_array($ctaccRs);

                # begin sql transaction
                pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

                # date format
                $date[$key] = explode("-", $date[$key]);
                $date[$key] = $date[$key][2]."-".$date[$key][1]."-".$date[$key][0];

                // Insert the records into the transaction table
                db_conn(PRD_DB);
                $sql = "INSERT INTO transect(date, debit, credit, refnum, amount, author, details) VALUES('$date[$key]', '$dtaccid[$key]', '$ctaccid[$key]', '$refnum[$key]', '$amount[$key]', '".USER_NAME."', '$descript[$key]')";
                $transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

                // Update the balances by adding appropriate values to the trial_bal Table
                core_connect();
                $ctbal = "UPDATE trial_bal SET credit = (credit + '$amount[$key]') WHERE accid = '$ctaccid[$key]'";
                $dtbal = "UPDATE trial_bal SET debit = (debit + '$amount[$key]') WHERE accid = '$dtaccid[$key]'";
                $ctbalRslt = db_exec($ctbal) or errDie("Unable to update credit balance for credited account.",SELF);
                $dtbalRslt = db_exec($dtbal) or errDie("Unable to update debit balance for debited account.",SELF);

                # commit sql transaction
                pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);
        }

        // Start layout
        $write ="
        <center>
        <h3>Journal transactions have been recorded</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Date</th><th>Ref num</th><th>Debit</th><th>Credit</th><th>Amount</th><th>Description</th></tr>";

                foreach($amount as $key => $value){
                        $write .= "<tr bgcolor=".TMPL_tblDataColor1."><td>$date[$key]</td><td>$refnum[$key]</td>
                        <td valign=center>".$dtacc[$key]['topacc']."/".$dtacc[$key]['accnum']." ".$dtacc[$key]['accname']."</td>
                        <td valign=center>".$ctacc[$key]['topacc']."/".$ctacc[$key]['accnum']." ".$ctacc[$key]['accname']."</td>
                        <td>".CUR." $amount[$key]</td><td>$descript[$key]</td></tr>";
                }

        $write .= "</table>
        <br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
        <tr><th>Quick Links</th></tr>
        <tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        <script>document.write(getQuicklinkSpecial());</script>
        <script>document.write(getQuicklinkSpecial());</script>
        <tr class=datacell><td align=center><a href='../main.php'>Main Menu</td></tr>
        </table>";

        return $write;
}
?>
