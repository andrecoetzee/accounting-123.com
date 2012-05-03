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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {

                case "write":
                        $OUTPUT = write($_POST);
			break;

                default:
                        if(isset($_GET['batchid'])){
                                $OUTPUT = rem($_GET['batchid']);
                        }else{
                                $OUTPUT = "<li> - Invalid use of module";
                        }
	}
} else {
        if(isset($_GET['batchid'])){
                $OUTPUT = rem($_GET['batchid']);
        }else{
                $OUTPUT = "<li> - Invalid use of module";
        }
}

# get templete
require("template.php");

# Enter Details of Transaction
function rem($batchid)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($batchid, "num", 1, 20, "Invalid batch number.");

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

        # connect
        core_connect();

        # Get all the details
        $sql = "SELECT * FROM batch WHERE batchid = '$batchid' AND div = '".USER_DIV."'";
        $rslt = db_exec($sql) or errDie("Unable to access database.");
        $tran = pg_fetch_array($rslt);

        # get account to be debited
        $dtaccRs = get("core","*","accounts","accid",$tran['debit']);
        if(pg_numrows($dtaccRs) < 1){
                return "<li> Accounts to be debited does not exist";
        }
        $dtacc = pg_fetch_array($dtaccRs);

        # get account to be debited
        $ctaccRs = get("core","*","accounts","accid",$tran['credit']);
        if(pg_numrows($ctaccRs) < 1){
                return "<li> Accounts to be debited does not exist";
        }
        $ctacc = pg_fetch_array($ctaccRs);

        // Deatils
        $rem ="
        <h3>Remove Batch Entry</h3>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=write>
        <input type=hidden name='batchid' value='$batchid'>
        <input type=hidden name=dtaccname value='$dtacc[accname]'>
        <input type=hidden name=ctaccname value='$ctacc[accname]'>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
        <tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
        <tr class='bg-odd'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
        <tr><td><br></td></tr>
        <tr><td><br></td></tr>
        <tr class='bg-odd'><td>Date</td><td>$tran[date]</td></tr>
        <tr class='bg-even'><td>Reference No.</td><td valign=center>$tran[refnum]</td></tr>
        <tr class='bg-odd'><td>Amount</td><td valign=center>".CUR." $tran[amount]</td></tr>
        <tr class='bg-even'><td>Transaction Details</td><td valign=center>$tran[details]</td></tr>
        <tr class='bg-odd'><td>Person Authorising</td><td valign=center>$tran[author]</td></tr>
        <tr><td><input type=button value=Back OnClick='javascript:history.back()'></td><td valign=center><input type=submit value='Remove Entry'></td></tr>
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

        return $rem;
}

# Write
function write($_POST)
{
// Sanity Checking and get vars(Respectively)
        # Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($batchid, "num", 1, 20, "Invalid batch number.");
        $v->isOk ($ctaccname, "string", 1, 255, "Invalid Account name to be Credited.");
        $v->isOk ($dtaccname, "string", 1, 255, "Invalid Account name to be Debited.");

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


	db_conn("core");
	# Get all the details
        $sql = "SELECT * FROM batch WHERE batchid = '$batchid' AND div = '".USER_DIV."'";
        $rslt = db_exec($sql) or errDie("Unable to access database.");
        $tran = pg_fetch_array($rslt);

        // Accounts details
        $dtaccRs = get("core","*","accounts","accid",$tran['debit']);
        $dtacc  = pg_fetch_array($dtaccRs);
        $ctaccRs = get("core","*","accounts","accid",$tran['credit']);
        $ctacc  = pg_fetch_array($ctaccRs);

        // Insert the records into the transaction table
        db_conn("core");
        $sql = "DELETE FROM batch WHERE batchid = '$batchid' AND div = '".USER_DIV."'";
	$upRslt = db_exec($sql) or errDie("Unable to update batch entry details.",SELF);

        // Start layout
        $write ="
        <center>
        <h3>Batch Entry has been removed</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>
        <tr><td width=50%><h3>Debit</h3></td><td width=50%><h3>Credit</h3></td></tr>
        <tr class='bg-even'><td>$dtacc[topacc]/$dtacc[accnum] - $dtacc[accname]</td><td>$ctacc[topacc]/$ctacc[accnum] - $ctacc[accname]</td></tr>
        <tr><td><br></td></tr>
        <tr colspan=2><td><h4>Amount</h4></td></tr>
        <tr class='bg-even'><td colspan=2><b>".CUR." $tran[amount]</b></td></tr>
        </table>
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
