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
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;

		case "write":
			$OUTPUT = write($_POST);
			break;

		default:
			if (isset($_GET['grpid'])){
				$OUTPUT = edit ($_GET['grpid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if (isset($_GET['grpid'])){
		$OUTPUT = edit ($_GET['grpid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module";
	}
}

# display output
require ("template.php");

function edit($grpid)
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
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
		return $confirm;
	}

	# Select Stock
	db_conn("exten");
	$sql = "SELECT * FROM assetgrp WHERE grpid = '$grpid' AND div = '".USER_DIV."'";
	$grpRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($grpRslt) < 1){
		return "<li> Invalid Asset Group ID.";
	}else{
		$grp = pg_fetch_array($grpRslt);
	}

	$enter =
	"<h3>Edit Asset Group</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=grpid value='$grp[grpid]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Asset Group</td><td><input type=text size=10 maxlength=10 name=grpname value='$grp[grpname]'></td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='assetgrp-view.php'>View Asset Groups</a></td></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# Confirm new data
function confirm ($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($grpname, "string", 1, 10, "Invalid Asset Group name or Asset Group name is too long.");

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

	$confirm =
	"<h3>Confirm Edit Asset Group</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=grpname value='$grpname'>
	<input type=hidden name=grpid value='$grpid'>
	<input type=hidden name=whno value='$grpno'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Number</td><td>$grpno</td></tr>
	<tr class='bg-even'><td>Asset Group</td><td>$grpname</td></tr>
	<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
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
	$v->isOk ($grpno, "num", 1, 10, "Invalid Asset Group number.");
	$v->isOk ($grpname, "string", 1, 10, "Invalid Asset Group name or Asset Group name is too long.");


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
	db_conn ("exten");

	# write to db
	$sql = "UPDATE assetgrp SET whno = '$grpno', grpname = '$grpname' WHERE grpid = '$grpid' AND div = '".USER_DIV."'";
	$grpRslt = db_exec ($sql) or errDie ("Unable to add edit Asset Group to system.", SELF);
	if (pg_cmdtuples ($grpRslt) < 1) {
		return "<li class=err>Unable to edit Asset Group to database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Asset Group edited</th></tr>
	<tr class=datacell><td>Asset Group <b>$grpname</b>, has been edited.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='assetgrp-view.php'>View Asset Groups</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>
