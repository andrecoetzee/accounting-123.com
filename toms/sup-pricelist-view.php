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
$OUTPUT = printList ();

require ("../template.php");

# show stock
function printList ()
{
	# Set up table to display in
	$printList = "
    <h3>Supplier Price Lists</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Price List</th><th colspan=4>Options</th></tr>";

	# connect to database
	db_conn ("exten");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM spricelist WHERE div = '".USER_DIV."' ORDER BY listname ASC";
    $listRslt = db_exec ($sql) or errDie ("Unable to retrieve Price Lists from database.");
	if (pg_numrows ($listRslt) < 1) {
		return "<li>There are no Price Lists in Cubit.";
	}
	while ($list = pg_fetch_array ($listRslt)) {
		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printList .= "<tr bgcolor='$bgColor'><td>$list[listname]</td><td><a href='sup-pricelist-det.php?listid=$list[listid]'>Details</a></td>";
		$printList .= "<td><a href='sup-pricelist-edit.php?listid=$list[listid]'>Edit</a></td><td><a href='sup-pricelist-copy.php?listid=$list[listid]'>Copy</a></td><td><a href='sup-pricelist-rem.php?listid=$list[listid]'>Remove</a></td></tr>";
		$i++;
	}

	$printList .= "</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='sup-pricelist-add.php'>Add Supplier Price List</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $printList;
}
?>
