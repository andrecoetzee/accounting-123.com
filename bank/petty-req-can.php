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
require("../core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "cancel":
			$OUTPUT = write($_POST);
			break;

		default:
			# Display default output
			if(isset($_GET['cashid'])){
					$OUTPUT = confirm($_GET['cashid']);
			}else{
					$OUTPUT = "<li class=err> Invalid use of mudule";
			}
	}
} else {
	# Display default output
	if(isset($_GET['cashid'])){
			$OUTPUT = confirm($_GET['cashid']);
	}else{
			$OUTPUT = "<li class=err> Invalid use of mudule";
	}
}


# get template
require("../template.php");

function confirm($cashid)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashid, "num", 1, 20, "Invalid Reference number.");

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

	# Connect to database
	db_Connect ();
	$sql = "SELECT * FROM pettycashbook WHERE cashid = '$cashid' AND div = '".USER_DIV."'";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve cashbook entry details from database.", SELF);
	if (pg_numrows ($cashRslt) < 1) {
		$OUTPUT = "<li clss=err>Requisistion not found in Cubit.";
		return $OUTPUT;
	}
	$cash = pg_fetch_array($cashRslt);


	# Get account name for the account involved
	$accRslt = get("core","accname,accnum,topacc","accounts", "accid", $cash['accid']);
	$acc = pg_fetch_array($accRslt);

	// Layout
	$confirm ="<h3>Cancel Requisistion</h3>
	<h4>Confirm entry</h4>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=cancel>
	<input type=hidden name=cashid value='$cash[cashid]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Date</td><td>$cash[date]</td></tr>
	<tr class='bg-even'><td>Paid to</td><td>$cash[name]</td></tr>
	<tr class='bg-odd'><td>Details</td><td><pre>$cash[det]</pre></td></tr>
	<tr class='bg-even'><td>Amount</td><td>".CUR." $cash[amount]</td></tr>
	<tr class='bg-odd'><td>Account</td><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td></tr>
	<tr><td><br></td></tr>
	<tr><td	><input type=button value='&laquo Back' onClick='javascript:history.back()'></td><td align=right><input type=submit value='Cancel &raquo'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='pettycashbook-view.php'>View Petty Cash Requisitions</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}


# write
function write($_POST)
{
	//processes
	db_connect();

	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cashid, "num", 1, 4, "Invalid Reference number.");

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

	// Delete cashbook ID
	db_connect();
	$sql = "DELETE FROM pettycashbook WHERE cashid='$cashid' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("Unable to cancel cheque.", SELF);

	# status report
	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='30%'>
			<tr><th>Petty Cash Requisition Cancelled</th></tr>
			<tr class=datacell><td>Petty Cash Requisition has been successfully canceled .</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='petty-req-add.php'>Add Petty Cash Requisition</a></td></tr>
		<tr class='bg-odd'><td><a href='pettycashbook-view.php'>View Petty Cash Requisitions</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>
