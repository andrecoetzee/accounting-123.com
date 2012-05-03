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

if ($_POST) {
	if ($_POST["key"] == "confirm") {
		# confirm entered data
		$OUTPUT = confirmPaye ($_POST);

	} elseif ($_POST["key"] == "write") {
		# write to database
		$OUTPUT = writePaye ($_POST);
	}

} else {
	# enter info to change
	$OUTPUT = editPaye ($_GET);
}

require ("../template.php");

##
# Functions
##

# enter info to change
function editPaye ($_GET)
{
	extract($_GET);

	$id+=0;

	# connect to db
	db_connect ();

	# get info
	$sql = "SELECT * FROM paye WHERE id='$id'";
	$payeRslt = db_exec ($sql) or errDie ("Unable to select paye bracket from database.", SELF);
	if (pg_numrows ($payeRslt) > 0) {
		# get result
		$myPaye = pg_fetch_array ($payeRslt);
	} else {
		return "Invalid PAYE ID.";
	}
	
	
	if(isset($min)) {
		$myPaye['min']=$min;
		$myPaye['max']=$max;
		$myPaye['percentage']=$percentage;
		$myPaye['extra']=$extra;
	}

	$editPaye =
	"<h3>Edit PAYE bracket</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=id value='$id'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Minimum gross</td><td align=center>".CUR." <input type=text size=20 name=min class=right value='$myPaye[min]'></td></tr>
	<tr class='bg-even'><td>Maximum gross</td><td align=center>".CUR." <input type=text size=20 name=max class=right value='$myPaye[max]'></td></tr>
	<tr class='bg-odd'><td>Percentage to deduct</td><td align=center><input type=text size=20 name=percentage class=right value='$myPaye[percentage]'>%</td></tr>
	<tr class='bg-even'><td>Exstra Amount</td><td align=center>".CUR." <input type=text size=20 name=extra class=right value='$myPaye[extra]'></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table><p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='#88BBFF'><td><a href='paye-add.php'>Add Paye</a></td></tr>
	<tr bgcolor='#88BBFF'><td><a href='paye-view.php'>View Paye</a></td></tr>
	<tr bgcolor='#88BBFF'><td><a href='employee-resources.php'>Employee Resources</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $editPaye;
}

# confirm new paye bracket details
function confirmPaye ($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid PAYE ID.");
	$v->isOk ($min, "float", 1, 20, "Invalid min amount.");
	$v->isOk ($max, "float", 1, 20, "Invalid max amount.");
	$v->isOk ($percentage, "float", 1, 10, "Invalid percentage.");
	$v->isOk ($extra, "float", 1, 20, "Invalid exstra amount.");
	

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		return $confirmCust."</li>".editPaye ($_POST);
	}

	$confirmPaye =
	"<h3>Confirm PAYE bracket changes</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=id value='$id'>
	<input type=hidden name=min value='$min'>
	<input type=hidden name=max value='$max'>
	<input type=hidden name=percentage value='$percentage'>
	<input type=hidden name=extra value='$extra'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Minimum gross</td><td align=right>".CUR." $min</td></tr>
	<tr class='bg-even'><td>Maximum gross</td><td align=right>".CUR." $max</td></tr>
	<tr class='bg-odd'><td>Percentage to deduct</td><td align=right>$percentage %</td></tr>
	<tr class='bg-even'><td>Exstra</td><td align=right>".CUR." $extra</td></tr>
	<tr><td><input type=submit name=back value='&laquo; Correction'></td><td align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table><p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='#88BBFF'><td><a href='paye-add.php'>Add Paye</a></td></tr>
	<tr bgcolor='#88BBFF'><td><a href='paye-view.php'>View Paye</a></td></tr>
	<tr bgcolor='#88BBFF'><td><a href='employee-resources.php'>Employee Resources</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirmPaye;
}

# write paye bracket changes to db
function writePaye ($_POST)
{

	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	
	if(isset($back)) {
		return editPaye ($_POST);
	}
	
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid PAYE ID.");
	$v->isOk ($min, "float", 1, 20, "Invalid min amount.");
	$v->isOk ($max, "float", 1, 20, "Invalid max amount.");
	$v->isOk ($percentage, "float", 1, 10, "Invalid percentage.");
	$v->isOk ($extra, "float", 1, 20, "Invalid exstra amount.");

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

	# commit PAYE changes to db
	$sql = "UPDATE paye SET min='$min', max='$max', percentage='$percentage',extra='$extra' WHERE id='$id'";
	$payeRslt = db_exec ($sql) or errDie ("Unable to commit PAYE bracket changes to database.", SELF);

	$writePaye =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>PAYE bracket successfully edited</th></tr>
	<tr class=datacell><td>PAYE bracket (R $min - ".CUR." $max) has been successfully edited.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='#88BBFF'><td><a href='paye-add.php'>Add Paye</a></td></tr>
	<tr bgcolor='#88BBFF'><td><a href='paye-view.php'>View Paye</a></td></tr>
	<tr bgcolor='#88BBFF'><td><a href='employee-resources.php'>Employee Resources</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr bgcolor='#88BBFF'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $writePaye;
}

?>
