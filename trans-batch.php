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
##
# trans-new.php :: Multiple debit-credit Transactions
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
                        $OUTPUT = details($HTTP_POST_VARS);
			break;

                case "details2":
                        $OUTPUT = details2($HTTP_POST_VARS);
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

		$refnum = getrefnum();
/*refnum*/

        // Accounts (debit)
        $view = "<center>
        <h3>Add Journal transactions to batch </h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
        <tr><th>Date</th><th>Ref num</th><th>Debit</th><th>Credit</th><th>Amount</th><th>Description</th></tr>";

        for($i=0; $i != 5; $i++){
                $view .= "<tr><td><input type=text size=10 name=date[] value=".date("d-m-Y")."></td>
                               <td><input type=text size=10 name=refnum[] value='$refnum'></td>
                               <td valign=center>
                                <select name='dtaccid[]'>";
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
                                <td valign=center>
                                <select name=ctaccid[]>";
                                $sql = "SELECT * FROM accounts ORDER BY topacc, accnum ASC";
                                $accRslt = db_exec($sql);
                                if(pg_numrows($accRslt) < 1){
                                        return "<li>There are No accounts in Cubit.";
                                }
                                while($acc = pg_fetch_array($accRslt)){
                                        $view .= "<option value='$acc[accid]'>$acc[accname]</option>";
                                }
                                $view .="</select></td>
                                <td><input type=text size=10 name=amount[]></td>
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
        foreach($amount as $key => $value){
                if($amount[$key] > 0){
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
        foreach($amount as $key => $value){
                if($amount[$key] > 0){

                        # get account to be debited
                        $dtaccRs = get("core","*","accounts","accid",$dtaccid[$key]);
                        if(pg_numrows($dtaccRs) < 1){
                                return "<li> Accounts to be debited does not exist";
                        }
                        $dtacc[$key]  = pg_fetch_array($dtaccRs);

                        # get account to be credited
                        $ctaccRs = get("core","*","accounts","accid",$ctaccid[$key]);
                        if(pg_numrows($ctaccRs) < 1){
                                return "<li> Accounts to be credited does not exist";
                        }
                        $ctacc[$key]  = pg_fetch_array($ctaccRs);
                }
        }

        $confirm =
        "<center>
        <h3>Add Multiple Journal transactions to batch</h3>
        <h4>Confirm entry</h4>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
        <tr><th>Date</th><th>Ref num</th><th>Debit</th><th>Credit</th><th>Amount</th><th>Description</th></tr>";

         foreach($amount as $key => $value){
                if($amount[$key] > 0){
                        $confirm .= "<tr bgcolor=".TMPL_tblDataColor1."><td><input type=hidden size=10 name=date[] value='$date[$key]'>$date[$key]</td>
                              <td><input type=hidden size=10 name=refnum[] value='$refnum[$key]'>$refnum[$key]</td>
                              <td valign=center><input type=hidden name='dtaccid[]' value='".$dtacc[$key]['accid']."'>".$dtacc[$key]['accname']."</td>
                              <td valign=center><input type=hidden name='ctaccid[]' value='".$ctacc[$key]['accid']."'>".$ctacc[$key]['accname']."</td>
                              <td><input type=hidden name=amount[] value='$amount[$key]'>".CUR." $amount[$key]</td>
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

                // Insert the records into the transaction table
                core_connect();

                $sql = "INSERT INTO batch(date, debit, credit, refnum, amount, author, details) VALUES('$date[$key]', '$dtaccid[$key]', '$ctaccid[$key]', '$refnum[$key]', '$amount[$key]', '".USER_NAME."', '$descript[$key]')";
                $transRslt = db_exec($sql) or errDie("Unable to insert Transaction  details to database",SELF);

                # commit sql transaction
                pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);
        }

        // Start layout
        $write ="
        <center>
        <h3>Journal transactions have been recorded to a batch file</h3>
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
        <tr class=datacell><td align=center><a href='trans-batch.php'>Add Journal Transactionst to batch</td></tr>
        <tr class=datacell><td align=center><a href='batch-view.php'>View batch file</td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $write;
}
?>
