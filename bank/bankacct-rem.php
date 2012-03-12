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
require("../settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "rem":
            $OUTPUT = rem($_POST);
			break;
        default:
			$OUTPUT = confirm($_GET['bankid']);
	}
} else {
	# Display default output
	if(!empty($_GET['bankid'])){
		$OUTPUT = confirm($_GET['bankid']);
	}else{
		$OUTPUT = confirm('none');
	}
}

# get templete
require("../template.php");



function confirm($bankid)
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 4, "Invalid Bank Account ID.");

    # display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	// Connect to database
	Db_Connect ();

	$sql = "SELECT * FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$accntRslt = db_exec ($sql) or errDie ("ERROR: Unable to select Bank account details from database.", SELF);
	$numrows = pg_numrows ($accntRslt);

    if ($numrows > 1) {
		$OUTPUT = "There are more than one accounts with the same account ID.";
		require ("../template.php");
	}

   if ($numrows < 1) {
		$OUTPUT = "<center>Invalid Bank Account ID.";
		require ("../template.php");
	}

    $accnt = pg_fetch_array($accntRslt);

    $confirm = "
    	<h3>Remove Bank  Account from database</h3>
        <h4>Confirm entry</h4>
        <table ".TMPL_tblDflts.">
        <form action='".SELF."' method='POST'>
	        <input type='hidden' name='key' value=rem>
	        <input type='hidden' name='bankid' value='$bankid'>
	        <input type='hidden' name='acctype' value='$accnt[acctype]'>
	        <input type='hidden' name='bankname' value='$accnt[bankname]'>
	        <input type='hidden' name='branchname' value='$accnt[branchname]'>
	        <input type='hidden' name='branchcode' value='$accnt[branchcode]'>
	        <input type='hidden' name='accname' value='$accnt[accname]'>
	        <input type='hidden' name='accnum' value='$accnt[accnum]'>
	        <input type='hidden' name='details' value='$accnt[details]'>
	        <tr>
	        	<th>Field</th>
	        	<th>Value</th>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Account Type</td>
	        	<td>$accnt[acctype]</td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Bank Name</td>
	        	<td>$accnt[bankname]</td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Branch Name</td>
	        	<td>$accnt[branchname]</td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Branch Code</td>
	        	<td>$accnt[branchcode]</td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Account Name</td>
	        	<td>$accnt[accname]</td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Account Number</td>
	        	<td>$accnt[accnum]</td>
	        </tr>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td>Details</td>
	        	<td>$accnt[details]</td>
	        </tr>
	        <tr>
	        	<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
	        	<td align='right'><input type='submit' value='Remove Bank Account &raquo'></td>
	        </tr>
        </form>
        </table>
        <p>
        <table ".TMPL_tblDflts.">
            <tr>
            	<th>Quick Links</th>
            </tr>
            <tr bgcolor='".bgcolorg()."'>
            	<td><a href='bankacct-view.php'>View Bank Accounts</a></td>
            </tr>
            <script>document.write(getQuicklinkSpecial());</script>
        </table>";
	return $confirm;

}




function rem($_POST)
{

	// Processes
	db_connect();

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 4, "Invalid Bank Account ID.");
	$v->isOk ($acctype, "string", 1, 30, "Invalid Account Type.");
	$v->isOk ($bankname, "string", 1, 50, "Invalid Bank name.");
	$v->isOk ($branchname, "string", 1, 50, "Invalid Branch Name.");
	$v->isOk ($branchcode, "string", 1, 15, "Invalid Branch Code.");
	$v->isOk ($accname, "string", 1, 50, "Invalid Account Name.");
	$v->isOk ($accnum, "num", 1, 20, "Invalid Account Number.");
	$v->isOk ($details, "string", 1, 255, "Invalid Details.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	$sql = "DELETE FROM bankacct WHERE bankid='$bankid' AND div = '".USER_DIV."'";
	$editRslt = db_exec ($sql) or errDie ("Unable to delete bank account.");

	# Remove the account link
	core_connect();
	$sql = "DELETE FROM bankacc WHERE accid='$bankid' AND div = '".USER_DIV."'";
	$editRslt = db_exec ($sql) or errDie ("Unable to delele bank account.");

	# status report
	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Bank Account Removed from database</th>
			</tr>
			<tr class='datacell'>
				<td>The Bank account ,<b> $accname </b> was successfully deleted.</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='bankacct-view.php'>View Bank Accounts</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $write;

}



?>