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

header("Location: company-export.php");
exit;

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "backup":
			$OUTPUT = backup ($_POST);
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
"<h3>Making a new Backup will delete the previous backup.</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=backup>

<tr><td colspan=2 align=right><input type=submit value='Backup &raquo;'></td></tr>
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

	$Ex =`/usr/local/pgsql/bin/pg_dumpall -U postgres -c > backup/db.sql`;

	$back =
"<h3>Backup Done</h3>
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
