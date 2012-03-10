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

                case "write":
                        $OUTPUT = write($HTTP_POST_VARS);
        		break;
                default:
			$OUTPUT = confirm($HTTP_POST_VARS);
	}
} else {
        # Display default output
        $OUTPUT = confirm($HTTP_POST_VARS);
}

# get templete
require("template.php");

# Confirm
function confirms($HTTP_POST_VARS)
{
// Sanity Checking
        # Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

        # validate input
	require_lib("validate");
	$v = new  validate ();
        if(isset($bank)){
                foreach($bank as $key => $value){
                        $v->isOk ($bank[$key], "num", 1, 50, "Invalid Batch ID.");
                }
        }else{
                return "<li> - No Batch Entries Seleted. Please select at least one batch entry.";
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

        $trans = "";
        # batches
        foreach($bank as $key => $value){
                # Get all the details
                $sql = "SELECT * FROM batch WHERE batchid = '$value'";
                $rslt = db_exec($sql) or errDie("Unable to access database.");
                $tran = pg_fetch_array($rslt);

                # get account to be debited
                $dtaccRs = get("core","accname","accounts","accid",$tran['debit']);
                if(pg_numrows($dtaccRs) < 1){
                        return "<li> Accounts to be debited does not exist";
                }
                $dtacc = pg_fetch_array($dtaccRs);

                # get account to be debited
                $ctaccRs = get("core","accname","accounts","accid",$tran['credit']);
                if(pg_numrows($ctaccRs) < 1){
                        return "<li> Accounts to be debited does not exist";
                }
                $ctacc = pg_fetch_array($ctaccRs);

                $trans .= "<tr bgcolor=".TMPL_tblDataColor1."><td><input type=hidden size=20 name=bank[] value='$value'>$tran[date]</td>
                                <td>$tran[refnum]</td>
                                <td valign=center>$dtacc[accname]</td>
                                <td valign=center>$ctacc[accname]</td>
                                <td>".CUR." $tran[amount]</td>
                                <td>$tran[details]</td>
                            </tr>";
        }

        $confirm =
        "<center>
        <h3>Multiple Journal transactions</h3>
        <h4>Confirm entry</h4>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=write>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=590>
        <tr><th>Date</th><th>Ref num</th><th>Debit</th><th>Credit</th><th>Amount</th><th>Description</th></tr>
        $trans
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
        if(isset($bank)){
                foreach($bank as $key => $value){
                        $v->isOk ($bank[$key], "num", 1, 50, "Invalid Batch ID.");
                }
        }else{
                return "<li> - No Batch Entries Seleted. Please select at least one batch entry.";
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

        foreach($bank as $key => $value){
                // Accounts details
                $dtaccRs = get("core","accname, topacc, accnum","accounts","accid",$dtaccid[$key]);
                $dtacc[$key]  = pg_fetch_array($dtaccRs);
                $ctaccRs = get("core","accname, topacc, accnum","accounts","accid",$ctaccid[$key]);
                $ctacc[$key]  = pg_fetch_array($ctaccRs);

                # Get all the details
                $sql = "SELECT * FROM batch WHERE batchid = '$value'";
                $rslt = db_exec($sql) or errDie("Unable to access database.");
                $tran = pg_fetch_array($rslt);

                $date[$key] = $tran['date'];
                $refnum[$key] = $tran['refnum'];
                $amount[$key] = $tran['amount'];
                $descript[$key] = $tran['descript'];

                # Remove the entries one by one
                core_Connect();
                $query = "DELETE FROM batch WHERE batchid = '$bank[$key]'";
                $Ex = db_exec($query) or errDie("Unable to delete batch file entries.",SELF);
        }

        // Start layout
        $write ="
        <center>
        <h3>Batch Journal transactions entries have been removed</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Date</th><th>Ref num</th><th>Debit</th><th>Credit</th><th>Amount</th><th>Description</th></tr>";

                foreach($bank as $key => $value){
                        $write .= "<tr bgcolor=".TMPL_tblDataColor1."><td>$date[$key]</td><td>$refnum[$key]</td>
                        <td valign=center>".$dtacc[$key]['topacc']."/".$dtacc[$key]['accnum']." ".$dtacc[$key]['accname']."</td>
                        <td valign=center>".$ctacc[$key]['topacc']."/".$ctacc[$key]['accnum']." ".$ctacc[$key]['accname']."</td>
                        <td>".CUR." $amount[$key]</td><td>$descript[$key]</td></tr>";
                }

        $write .= "</table>
        <br>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
        <tr><th>Quick Links</th></tr>
        <tr class=datacell><td align=center><a href='trans-batch.php'>Add Journal Transactions to batch</td></tr>
        <tr class=datacell><td align=center><a href='batch-view.php'>View batch Entries</td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        </table>";

        return $write;
}
?>
