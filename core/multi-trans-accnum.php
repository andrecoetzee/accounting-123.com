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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
                case "confirm":
			$OUTPUT = confirm($_POST);
			break;

                case "write":
                        $OUTPUT = write($_POST);
			break;

                case "details":
                        $OUTPUT = details($_POST);
			break;

                case "details2":
                        $OUTPUT = details2($_POST);
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
        <h3> Multiple Journal transactions </h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
        <tr><th>Date</th><th>Ref num</th><th>Debit (Acc No.)</th><th>Credit (Acc No.)</th><th>Amount</th><th>Description</th></tr>";

        for($i=0; $i != 15; $i++){
                $view .= "<tr class='bg-even'>
                              <td><input type=text size=2 name=day[] maxlength=2>-<input type=text size=2 name=mon[] maxlength=2  value='".date("m")."'>-<input type=text size=4 name=year[] maxlength=4 value='".date("Y")."'></td>
                              <td><input type=text size=10 name=refnum[] value='".($refnum++)."'></td>
                              <td valign=center><input type=text size=12 name='dtaccnum[]'></td>
                              <td valign=center><input type=text size=12 name='ctaccnum[]'></td>
                              <td><input type=text size=20 name=amount[]></td>
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
function confirm($_POST)
{
// Sanity Checking
        # Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        foreach($amount as $key => $value){
                if($amount[$key] > 0){
                        $v->isOk ($ctaccnum[$key], "string", 1, 50, "Invalid Account to be Credited.[$key]");
                        $v->isOk ($dtaccnum[$key], "string", 1, 50, "Invalid Account to be Debited.[$key]");
                        $v->isOk ($refnum[$key], "num", 1, 10, "Invalid Reference number.[$key]");
                        $v->isOk ($amount[$key], "float", 1, 20, "Invalid Amount.[$key]");
                        $v->isOk ($descript[$key], "string", 0, 255, "Invalid Details.[$key]");
                        $v->isOk ($day[$key], "num", 1,2, "Invalid to Date day.");
                        $v->isOk ($mon[$key], "num", 1,2, "Invalid to Date month.");
                        $v->isOk ($year[$key], "num", 1,4, "Invalid to Date Year.");
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
        foreach($amount as $key => $value){
                if($amount[$key] > 0){
                        $dtaccnum[$key] = explode("/", rtrim($dtaccnum[$key]));
                        $ctaccnum[$key] = explode("/", rtrim($ctaccnum[$key]));

                        if(count($dtaccnum[$key]) < 2){
                                // account numbers
                                $dtaccRs = get("core","*","accounts","topacc",$dtaccnum[$key][0]."' AND accnum = '000");
                                if(pg_numrows($dtaccRs) < 1){
                                        return "<li> Accounts number : ".$dtaccnum[$key][0]." does not exist";
                                }
                                $dtacc[$key]  = pg_fetch_array($dtaccRs);
                        }else{
                                // account numbers
                                $dtaccRs = get("core","*","accounts","topacc",$dtaccnum[$key][0]."' AND accnum = '".$dtaccnum[$key][1]);
                                if(pg_numrows($dtaccRs) < 1){
                                        return "<li> Accounts number : ".$dtaccnum[$key][0]/$dtaccnum[$key][1]." does not exist";
                                }
                                $dtacc[$key]  = pg_fetch_array($dtaccRs);
                        }

                        if(count($ctaccnum[$key]) < 2){
                                # get top level account
                                $ctaccRs = get("core","*","accounts","topacc",$ctaccnum[$key][0]."' AND accnum = '000");
                                if(pg_numrows($ctaccRs) < 1){
                                        return "<li> Accounts number : ".$ctaccnum[$key][0]." does not exist";
                                }
                                $ctacc[$key]  = pg_fetch_array($ctaccRs);
                        }else{
                                # get low level account
                                $ctaccRs = get("core","*","accounts","topacc",$ctaccnum[$key][0]."' AND accnum = '".$ctaccnum[$key][1]);
                                if(pg_numrows($ctaccRs) < 1){
                                        return "<li> Accounts number : ".$ctaccnum[$key][0]/$ctaccnum[$key][1]." does not exist";
                                }
                                $ctacc[$key]  = pg_fetch_array($ctaccRs);
                        }
                }
        }

        # print "<pre>";var_dump($ctacc);
        # print "<br><pre>";var_dump($dtacc);
        # exit;

        $confirm =
        "<center>
        <h3>Multiple Journal transactions</h3>
        <h4>Confirm entry</h4>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Date</th><th>Ref num</th><th>Debit</th><th>Credit</th><th>Amount</th><th>Description</th></tr>";

         foreach($amount as $key => $value){
                if($amount[$key] > 0){
                        $confirm .= "<tr class='bg-odd'><td><input type=hidden size=10 name=date[] value='$date[$key]'>$date[$key]</td>
                              <td><input type=hidden size=10 name=refnum[] value='$refnum[$key]'>$refnum[$key]</td>
                              <td valign=center><input type=hidden name='dtaccid[]' value='".$dtacc[$key]['accid']."'>".$dtacc[$key]['topacc']."/".$dtacc[$key]['accnum']."&nbsp;&nbsp;&nbsp;".$dtacc[$key]['accname']."</td>
                              <td valign=center><input type=hidden name='ctaccid[]' value='".$ctacc[$key]['accid']."'>".$ctacc[$key]['topacc']."/".$ctacc[$key]['accnum']."&nbsp;&nbsp;&nbsp;".$ctacc[$key]['accname']."</td>
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
function write($_POST)
{
        # Get vars
	foreach ($_POST as $key => $value) {
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
                        $write .= "<tr class='bg-odd'><td>$date[$key]</td><td>$refnum[$key]</td>
                        <td valign=center>".$dtacc[$key]['topacc']."/".$dtacc[$key]['accnum']."&nbsp;&nbsp;&nbsp;".$dtacc[$key]['accname']."</td>
                        <td valign=center>".$ctacc[$key]['topacc']."/".$ctacc[$key]['accnum']."&nbsp;&nbsp;&nbsp;".$ctacc[$key]['accname']."</td>
                        <td>".CUR." $amount[$key]</td><td>$descript[$key]</td></tr>";
                }

        $write .= "</table>
        <br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
        <tr><th>Quick Links</th></tr>
        <tr class=datacell><td align=center><a href='trans-new.php'>Journal Transactions</td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $write;
}
?>
