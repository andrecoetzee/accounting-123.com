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
require ("settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "write":
			$OUTPUT = write($_POST);
			break;

		default:
			if (isset($_GET['grpid'])){
				$OUTPUT = confirm ($_GET['grpid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if (isset($_GET['grpid'])){
		$OUTPUT = confirm ($_GET['grpid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module";
	}
}

# display output
require ("template.php");

# Confirm new data
function confirm ($grpid)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($grpid, "num", 1, 50, "Invalid Asset Group id.");

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

	# Select Stock
	db_connect();
	$sql = "SELECT * FROM assetgrp WHERE grpid = '$grpid' AND div = '".USER_DIV."'";
	$grpRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($grpRslt) < 1){
		return "<li> Invalid Asset Group ID.";
	}else{
		$grp = pg_fetch_array($grpRslt);
	}

	core_connect();
	$sql = "SELECT accname FROM accounts WHERE accid = '$grp[costacc]' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$acccost = pg_fetch_array($accRslt);

	# get ledger account name(accum dep)
	$sql = "SELECT accname FROM accounts WHERE accid = '$grp[accdacc]' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$acdacc = pg_fetch_array($accRslt);

	# get ledger account name(dep)
	$sql = "SELECT accname FROM accounts WHERE accid = '$grp[depacc]' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$accdep = pg_fetch_array($accRslt);

	$confirm =
	"<h3>Confirm Remove Asset Group</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=grpname value='$grp[grpname]'>
	<input type=hidden name=grpid value='$grpid'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Asset Group</td><td>$grp[grpname]</td></tr>
	<tr class='bg-even'><td>Cost Account</td><td>$acccost[accname]</td></tr>
	<tr class='bg-odd'><td>Accumulated Depreciation Account</td><td>$acdacc[accname]</td></tr>
	<tr class='bg-even'><td>Depreciation Account</td><td>$accdep[accname]</td></tr>
	<tr><td><br></td></tr>
	<tr><td align=right></td><td valign=left><input type=submit value='Delete &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='assetgrp-new.php'>Add Asset Group</a></td></tr>
	<tr class='bg-odd'><td><a href='assetgrp-view.php'>View Asset Groups</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write new data
function write ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($grpid, "num", 1, 50, "Invalid Asset Group id.");
	$v->isOk ($grpname, "string", 1, 255, "Invalid Asset Group name or Asset Group name is too long.");


	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	# connect to db
	db_connect ();

	# write to db
	$sql = "DELETE FROM assetgrp WHERE grpid = '$grpid' AND div = '".USER_DIV."'";
	$grpRslt = db_exec ($sql) or errDie ("Unable to add remove Asset Group to system.", SELF);
	if (pg_cmdtuples ($grpRslt) < 1) {
		return "<li class=err>Unable to remove Asset Group to database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Asset Group removed</th></tr>
		<tr class=datacell><td>Asset Group <b>$grpname</b>, has been removed.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='assetgrp-new.php'>Add Asset Group</a></td></tr>
		<tr class='bg-odd'><td><a href='assetgrp-view.php'>View Asset Groups</a></td></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>
