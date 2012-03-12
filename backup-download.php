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

header("Location: company-import.php");
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
"<h3>Download Backup.<br> Right Click on the icon and Click 'Save target as' to save it to your harddrive.</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<tr>
<td valign=top align=center width='33.33%'><a href='backup/db.sql' target=mainframe class=nav onMouseOver='imgSwop(\"accountc\", \"images/defaultaccountsh.gif\");' onMouseOut='imgSwop(\"accountc\", \"images/defaultaccount.gif\");'><img src='images/defaultaccount.gif' border=0 alt='Download Backup' title='Download Backup' name=accountc><br>Download Backup</a></td>
</tr>
</table> <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
        <script>document.write(getQuicklinkSpecial());</script>
	</table>
";
return $confirm;
}

?>
