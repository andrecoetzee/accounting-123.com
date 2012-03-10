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

require ("settings.php");

if ($HTTP_POST_VARS) {
	if ($HTTP_POST_VARS["key"] == "confirm") {
		# confirm entered data
		$OUTPUT = confirmPaye ($HTTP_POST_VARS);

	} elseif ($HTTP_POST_VARS["key"] == "write") {
		# write to database
		$OUTPUT = writePaye ($HTTP_POST_VARS);
	}

} else {
	# enter info to change
	$OUTPUT = editPaye ($HTTP_GET_VARS);
}

require ("template.php");

##
# Functions
##

# enter info to change
function editPaye ($HTTP_GET_VARS)
{
	$id = preg_replace ("/[^\d]/", "", substr ($HTTP_GET_VARS["id"], 0, 9));

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

	$editPaye =
	"<h3>Edit PAYE bracket</h3>

	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<input type=hidden name=id value='$id'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Minimum gross</td><td align=center>".CUR." <input type=text size=20 name=min class=right value='$myPaye[min]'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Maximum gross</td><td align=center>".CUR." <input type=text size=20 name=max class=right value='$myPaye[max]'></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Percentage to deduct</td><td align=center><input type=text size=20 name=percentage class=right value='$myPaye[percentage]'>%</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>";

	return $editPaye;
}

# confirm new paye bracket details
function confirmPaye ($HTTP_POST_VARS)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid PAYE ID.");
	$v->isOk ($min, "float", 1, 20, "Invalid min amount.");
    $v->isOk ($max, "float", 1, 20, "Invalid max amount.");
	$v->isOk ($percentage, "float", 1, 10, "Invalid percentage.");


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

	/*
	# clean non-array vars
	$min = preg_replace ("/[^\d]/", "", substr ($HTTP_POST_VARS["min"], 0, 9));
	$max = preg_replace ("/[^\d]/", "", substr ($HTTP_POST_VARS["max"], 0, 9));
	$percentage = preg_replace ("/[^\d\.]/", "", substr ($HTTP_POST_VARS["percentage"], 0, 6));
	$id = preg_replace ("/[^\d]/", "", substr ($HTTP_POST_VARS["id"], 0, 20));
	*/

	$confirmPaye =
	"<h3>Confirm PAYE bracket changes</h3>

	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=id value='$id'>
	<input type=hidden name=min value='$min'>
	<input type=hidden name=max value='$max'>
	<input type=hidden name=percentage value='$percentage'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Minimum gross</td><td align=right>".CUR." $min</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Maximum gross</td><td align=right>".CUR." $max</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Percentage to deduct</td><td align=right>$percentage %</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write changes &raquo;'></td></tr>
	</form>
	</table>";

	return $confirmPaye;
}

# write paye bracket changes to db
function writePaye ($HTTP_POST_VARS)
{

	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 20, "Invalid PAYE ID.");
	$v->isOk ($min, "float", 1, 20, "Invalid min amount.");
    $v->isOk ($max, "float", 1, 20, "Invalid max amount.");
	$v->isOk ($percentage, "float", 1, 10, "Invalid percentage.");


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

	/*
	# clean non-array vars
	$min = preg_replace ("/[^\d]/", "", substr ($HTTP_POST_VARS["min"], 0, 9));
	$max = preg_replace ("/[^\d]/", "", substr ($HTTP_POST_VARS["max"], 0, 9));
	$percentage = preg_replace ("/[^\d\.]/", "", substr ($HTTP_POST_VARS["percentage"], 0, 6));
	$id = preg_replace ("/[^\d]/", "", substr ($HTTP_POST_VARS["id"], 0, 20));
	*/

	# connect to db
	db_connect ();

	# commit PAYE changes to db
	$sql = "UPDATE paye SET min='$min', max='$max', percentage='$percentage' WHERE id='$id'";
	$payeRslt = db_exec ($sql) or errDie ("Unable to commit PAYE bracket changes to database.", SELF);

	$writePaye =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>PAYE bracket successfully edited</th></tr>
	<tr class=datacell><td>PAYE bracket (R $min - ".CUR." $max) has been successfully edited.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='#88BBFF'><td><a href='admin-paye-view.php'>View Paye</a></td></tr>
		<tr bgcolor='#88BBFF'><td><a href='admin-paye-add.php'>Add Paye</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='#88BBFF'><td><a href='main.php'>Main Menu</a></td></tr>
	</tr>";

	return $writePaye;
}

?>
