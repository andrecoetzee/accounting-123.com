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

require ("newsettings.php");

# show current stock
$OUTPUT = printComp ();

require ("newtemplate.php");

# show stock
function printComp ()
{
	# Set up table to display in
	$printComp = "
	<h3>Versions Information</h3>
	<h3>Cubit Version: ".CUBIT_VERSION."</h3>
	<h3>Cubit Build: ".CUBIT_BUILD."</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <p>
    <h3>Companies</h3>
    <tr><th>Company Code</th><th>Company Name</th><th>Version</th></tr>";

	# connect to database
	db_conn ("cubit");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM companies ORDER BY name ASC";
    $compRslt = db_exec($sql) or errDie ("Unable to retrieve companies from database.");
	if (pg_numrows ($compRslt) < 1) {
		return "<li>There are no companies in Cubit.";
	}
	while ($comp = pg_fetch_array ($compRslt)) {
		$printComp .= "<tr class='".bg_class()."'><td>$comp[code]</td><td>$comp[name]</td><td>$comp[ver]</td></tr>";
		$i++;
	}
// 	<!--<td><a href='company-rem.php?compid=$comp[compid]'>Remove</a></td>-->
	$printComp .= "</table>";

	$printComp .= "<p>
	<h3>History</h3>

    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Company Code</th><th>Company Name</th><th>Description</th><th>From</th><th>To</th><th>Date</th></tr>";

	# connect to database
	db_conn ("cubit");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM ch ORDER BY code ASC,id ASC";
    $compRslt = db_exec($sql) or errDie ("Unable to retrieve companies from database.");
	if (pg_numrows ($compRslt) < 1) {
		$printComp .=  "</table><li>There is no update data in Cubit.";
	}
	while ($d = pg_fetch_array ($compRslt)) {
		$printComp .= "<tr class='".bg_class()."'><td>$d[code]</td><td>$d[comp]</td><td>$d[des]</td><td>$d[f]</td><td>$d[t]</td><td>$d[date]</td></tr>";
		$i++;
	}
// 	<!--<td><a href='company-rem.php?compid=$comp[compid]'>Remove</a></td>-->
	$printComp .= "</table>";

	return $printComp;
}
?>
