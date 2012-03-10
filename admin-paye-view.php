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
# admin-paye-view.php :: View PAYE brackets
##

# get settings
require ("settings.php");

$OUTPUT = showPaye ();

# display output
require ("template.php");

# print PAYE brackets in db
function showPaye ()
{
	# connect to db
	db_connect ();

	# start table, etc
	$showPaye =
"
<h3>View PAYE brackets</h3>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<tr><th>Minimum gross</th><th>Maximum gross</th><th>Percentage</th></tr>
";

	# select jobs
	$i = 0;
	$sql = "SELECT * FROM paye ORDER BY min, max";
	$payeRslt = db_exec ($sql) or errDie ("Unable to select PAYE brackets from database.", SELF);
	if (pg_numrows ($payeRslt) > 0) {
		while ($myPaye = pg_fetch_array ($payeRslt)) {
			# alternate bgcolor
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$showPaye .= "<tr bgcolor='$bgColor'><td align=right>".CUR." $myPaye[min]</td><td align=right>".CUR." $myPaye[max]</td><td align=right>$myPaye[percentage]%</td><td><a href='admin-paye-edit.php?id=$myPaye[id]'>Edit</a> | <a href='admin-paye-rem.php?id=$myPaye[id]'>Delete</a></td></tr>\n";
			$i++;
		}
	} else {
		return "No PAYE brackets found in database.";
	}
	$showPaye .="</table>\n";
	return $showPaye;
}

?>
