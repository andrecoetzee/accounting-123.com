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

require ("../settings.php");

$OUTPUT = "<h3>Edit PAYE Brackets</h3>
<li class=err>This feature has been disabled for auditing purposes.</li>";
require ("../template.php");

if ($HTTP_POST_VARS) {
	if ($HTTP_POST_VARS["key"] == "write") {
		# remove paye
		$OUTPUT = remPaye ($HTTP_POST_VARS);
	}
} else {
	# confirm removal
	$OUTPUT = confirmPaye ($HTTP_GET_VARS);
}

	$OUTPUT.="<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='#88BBFF'><td><a href='paye-add.php'>Add Paye</a></td></tr>
	<tr bgcolor='#88BBFF'><td><a href='paye-view.php'>View Paye</a></td></tr>
	<tr bgcolor='#88BBFF'><td><a href='employee-resources.php'>Employee Resources</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

require ("../template.php");

# confirm removal
function confirmPaye ($HTTP_GET_VARS)
{
	$id = preg_replace ("/[^\d]/", "", substr ($HTTP_GET_VARS["id"], 0, 9));

	# connect to db
	db_connect ();

	# select paye bracket
	$sql = "SELECT * FROM paye WHERE id='$id'";
	$payeRslt = db_exec ($sql) or errDie ("Unable to select PAYE bracket from database.", SELF);
	if (pg_numrows ($payeRslt) < 1) {
		return "No PAYE brackets found in database.";
	}
	# get result
	$myPaye = pg_fetch_array ($payeRslt);

	$confirmPaye =
	"<h3>Confirm removal of PAYE bracket</h3>

	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=id value='$myPaye[id]'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Minimum gross</td><td align=right>".CUR." $myPaye[min]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Maximum gross</td><td align=right>".CUR." $myPaye[max]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Percentage</td><td align=right>$myPaye[percentage]%</td></tr>
	<tr><td><br></td><td align=right><input type=submit value='Remove PAYE bracket &raquo;'></td></tr>
	</form>
	</table>";

	return $confirmPaye;
}

# remove entry
function remPaye ($HTTP_POST_VARS)
{
	# clean vars
	$id = preg_replace ("/[^\d]/", "", substr ($HTTP_POST_VARS["id"], 0, 9));

	# connect to db
	db_connect ();

	# remove job
	$sql = "DELETE FROM paye WHERE id='$id'";
	$payeRslt = db_exec ($sql) or errDie ("Unable to remove PAYE bracket.", SELF);
	if (pg_cmdtuples ($payeRslt) < 1) {
		return "Failed to delete PAYE bracket.";
	}

	$remPaye =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>PAYE bracket removed</th></tr>
	<tr class=datacell><td>PAYE bracket has been successfully removed.</td></tr>
	</table>";

	return $remPaye;
}
?>
