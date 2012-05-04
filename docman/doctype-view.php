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
require ("../core-settings.php");

# show current stock
$OUTPUT = printCat ();

require ("../template.php");

# show stock
function printCat ()
{
	# Set up table to display in
	$printTyp = "
    <h3>Document types</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Ref</th><th>Document type</th></tr>";

	# connect to database
	db_conn ("yr2");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM doctypes WHERE div = '".USER_DIV."' ORDER BY typename ASC";
    $typRslt = db_exec ($sql) or errDie ("Unable to retrieve Document types from database.");
	if (pg_numrows ($typRslt) < 1) {
		return "<li>There are no Document types in Cubit.";
	}
	while($typ = pg_fetch_array ($typRslt)) {
		$printTyp .= "<tr class='".bg_class()."'><td>$typ[typeref]</td><td>$typ[typename]</td><!--<td><a href='doctype-edit.php?typeid=$typ[typeid]'>Edit</a></td>-->";
		$printTyp .= "<td><a href='doctype-rem.php?typeid=$typ[typeid]'>Remove</a></td></tr>";
		$i++;
	}

	$printTyp .= "</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='doctype-add.php'>Add Document type</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $printTyp;
}
?>
