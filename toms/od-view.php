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

# show current stock
$OUTPUT = printCat ();

require ("../template.php");

# show stock
function printCat ()
{
	# Set up table to display in
	$printCat = "
    <h3>Overdue Terms</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Days</th><th colspan=2>Options</th></tr>";

	# connect to database
	db_conn ("exten");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM od WHERE div = '".USER_DIV."' ORDER BY days ASC";
    $catRslt = db_exec ($sql) or errDie ("Unable to retrieve Overdue terms from database.");
	if (pg_numrows ($catRslt) < 1) {
		return "<li>There are no Overdue terms in Cubit.<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='ct-add.php'>Add Overdue Term</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";
	}
	while ($cat = pg_fetch_array ($catRslt)) {
		$printCat .= "<tr class='".bg_class()."'><td>$cat[days]</td><td><a href='od-edit.php?id=$cat[id]'>Edit</a></td>";
		$printCat .= "<td><a href='od-rem.php?id=$cat[id]'>Remove</a></td></tr>";
		$i++;
	}

	$printCat .= "</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='od-add.php'>Add Overdue Term</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $printCat;
}
?>
