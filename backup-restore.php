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
		case "backup":
			$OUTPUT = backup ($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = comfirm ();
	}
} else {
	$OUTPUT = confirm ();
}

require("template.php");

# confirms
function confirm ()
{

	$confirm =
"<h3>This will delete all your data in cubit.</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=backup>

<tr><td colspan=2 align=right><input type=submit value='Restore &raquo;'></td></tr>
</form>
</table> <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";
return $confirm;
}

# Select Month
function backup ()
{
	db_conn ("template1");
	
	$sql = "DROP DATABASE \"1\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE \"2\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE \"3\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE \"4\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE \"5\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE \"6\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE \"7\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE \"8\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE \"9\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE \"10\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE \"11\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE \"12\"";
	$allow = db_exec($sql);
	
	$sql = "DROP DATABASE \"13\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE \"14\"";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE audit";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE cubit";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE exten";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE prd_temp";
	$allow = db_exec($sql);
	
	$sql = "DROP DATABASE yr1";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE yr2";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE yr3";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE yr4";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE yr5";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE yr6";
	$allow = db_exec($sql);
	
	$sql = "DROP DATABASE yr7";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE yr8";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE yr9";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE yr10";
	$allow = db_exec($sql);

	$sql = "DROP DATABASE yr_temp";
	$allow = db_exec($sql);
	
	$sql = "DROP DATABASE core";
	$allow = db_exec($sql);

	$Ex = system("backup/run.pl 2&>1");
	//$Ex =`backup/db.sql`;

	$back =
"<h3>Backup Restored.</h3>
<br>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";
return $back;
}

?>
