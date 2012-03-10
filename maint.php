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

# decide what to do
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = con_sets ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write_sets ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = get_sets ();
	}
} else {
	$OUTPUT = get_sets ();
}

# display output
require ("template.php");

function get_sets ()
{

	db_conn('cubit');
	# go throug the dbs
	$Sl = "SELECT datname FROM pg_stat_database WHERE datname!='template0' and datname!='template1' ORDER BY datname ASC LIMIT 1";
	$Rs = db_exec($Sl) or errDie("Unabled to retrive sales reps from Cubit.",SELF);
	if(pg_numrows($Rs) > 0)
	{
		while($data = pg_fetch_array($Rs))
		{
			db_conn_maint($data['datname']);
			$Sql = "VACUUM FULL";
			$Ex = db_exec($Sql) or errDie ("Unable to access database.");
		}
	}else{
		return "<li class=err> - There are no valid Databases in the system.";
	}

	$sets =
	"<h3>Maintenance Complete</h3>
	<br>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	</table>";
        return $sets;
}

# confirm new settings
function con_sets ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($monthend,"num",1 ,2, "Invalid month end date.");
	$v->isOk ($int1,"float",1 ,5, "Invalid interest 1.");
	$v->isOk ($int2,"float",1 ,5, "Invalid interest 2.");
	$v->isOk ($int3,"float",1 ,5, "Invalid interest 3.");
	$v->isOk ($brack1,"float",1 ,10, "Invalid bracket 1.");
	$v->isOk ($brack2,"float",1 ,10, "Invalid bracket 2.");

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

	$brack11=$brack1+0.01;
	$brack22=$brack2+0.01;
	$brack1=sprint($brack1);
	$brack2=sprint($brack2);
	$brack11=sprint($brack11);
	$brack22=sprint($brack22);

	$sets =
	"<h3>Confirm Settings</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key      value=write>
	<input type=hidden name=monthend value='$monthend'>
	<input type=hidden name=int1     value='$int1'>
	<input type=hidden name=int2     value='$int2'>
	<input type=hidden name=int3     value='$int3'>
	<input type=hidden name=brack1   value='$brack1'>
	<input type=hidden name=brack2   value='$brack2'>
	<tr><th colspan=2>Settings</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Month End</td><td>$monthend</td></tr>
	<tr><th colspan=2>Interest Settings</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Interest(R0.00 - R$brack1)</td><td>$int1%</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Interest(R$brack11 - R$brack2)</td><td>$int2%</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Interest(More than R$brack22)</td><td>$int3%</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";
        return $sets;
}
# write settings
function write_sets ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($monthend,"num",1 ,2, "Invalid month end date");
	$v->isOk ($int1,"float",1 ,5, "Invalid interest 1.");
	$v->isOk ($int2,"float",1 ,5, "Invalid interest 2.");
	$v->isOk ($int3,"float",1 ,5, "Invalid interest 3.");
	$v->isOk ($brack1,"float",1 ,10, "Invalid bracket 1.");
	$v->isOk ($brack2,"float",1 ,10, "Invalid bracket 2.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		        return $confirmCust;
	}

	db_conn('hp');                                                                                                            // ,bc='$bc',vat='$vat',cs='$cs'

	$Sql = "UPDATE gensets SET monthend='$monthend',int1='$int1',int2='$int2',int3='$int3',brack1='$brack1',brack2='$brack2'";
	$Ex = db_exec($Sql) or errDie ("Unable to access database.");
	$Data = pg_fetch_array($Ex);

	$sets =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Settings updated</th></tr>
	<tr class=datacell><td>The settings has been updated.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $sets;
}
?>
