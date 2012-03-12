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

# cubit settings
require ("set-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "write":
                        $OUTPUT = write($_POST);
			break;

                default:
			$OUTPUT = remacc($_GET['accid']);
	}
} else {
        # Display default output
        if(!empty($_GET['accid'])){
        $OUTPUT = remacc($_GET['accid']);
        }else{
        $OUTPUT = "<li> Invalid use of module";
        }
}

# get templete
require("template.php");

function remacc($accid)
{
        // Limit field lengths as per database settings ( Regex method doesn't work :-/ )
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accid, "num", 1, 20, "Invalid Account Number .");

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

        // Connect to database
	core_connect();
        $sql = "SELECT * FROM accounts WHERE accid = '$accid' AND div = '".USER_DIV."'";
        $accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to Retrive Account details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

        if ($numrows < 1) {
		$OUTPUT = "<li> Invalid account number.";
		return $OUTPUT;
	}
        $acc = pg_fetch_array($accntRslt);

	core_connect();
	$sql = "SELECT * FROM trial_bal WHERE accid = '$accid' AND credit = 0 AND debit= 0 AND div = '".USER_DIV."'";
	$check = db_exec($sql) or errDie("Could not retrieve accounts details from database",SELF);
	$rows = pg_numrows($check);
	if($rows < 1){
		return "Account has transactions";
	}


$rem =
"<h3>Delete Account</h3>
<h4>Confirm entry</h4>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=write>
<input type=hidden name=accid value='$acc[accid]'>
<input type=hidden name=accname value='$acc[accname]'>
<input type=hidden name=acctype value='$acc[acctype]'>
<tr><th>Field</th><th>Value</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Number</td><td>$acc[topacc]/$acc[accnum]</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Name</td><td>$acc[accname]</td></tr>
<tr><td align=right><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Delete Account&raquo'></td></tr>
</form>
</table>
";
	return $rem;
}

function write($_POST)
{

//processes
core_connect();
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
        require_lib("validate");
	$v = new  validate ();
        $v->isOk ($accid, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($accname, "string", 1, 255, "Invalid Account Name.");
        $v->isOk ($acctype, "string", 1, 3, "Invalid Account Type.");

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

	core_connect();
	$sql = "SELECT * FROM trial_bal WHERE accid = '$accid' AND credit = 0 AND debit= 0 AND div = '".USER_DIV."'";
	$check = db_exec($sql) or errDie("Could not retrieve accounts details from database",SELF);
	$rows = pg_numrows($check);
	if($rows < 1){
		return "Account has transactions";
	}

        # begin sql transaction
        pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

                # delete account records
                $sql = "DELETE FROM accounts WHERE accid='$accid' AND div = '".USER_DIV."'";
                $remRslt = db_exec ($sql) or errDie ("Unable to Delete account from database.");

                # delete account records and balances from trial balance table
                $sql = "DELETE FROM trial_bal WHERE accid='$accid' AND div = '".USER_DIV."'";
                $remRslt = db_exec ($sql) or errDie ("Unable to Delete account from database.");

        # commit sql transaction
        pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	block();

	# status report
	$write =
        "
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
        <tr><th>Account Deleted from database</th></tr>
        <tr class=datacell><td>Account, <b>$accname</b>, was successfully Deleted.</td></tr>
        </table>
        <p>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
                <tr><td><br></td></tr>
                <tr><th>Quick Links</th></tr>
                <tr class=datacell><td align=center><a href='acc-view.php'>View Accounts</td></tr>
                <tr class=datacell><td align=center><a href='".ACCNEW_LNK."'>New Account</td></tr>
                <script>document.write(getQuicklinkSpecial());</script>
        </table>";

	return $write;
}
?>
