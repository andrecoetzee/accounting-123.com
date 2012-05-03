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

# show current stock
$OUTPUT = printClass ();

require ("template.php");

# show stock
function printClass ()
{
	# Set up table to display in
	$printClass = "
    <h3>VAT Codes</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Code</th><th>Description</th><th>Zero VAT</th><th>VAT Amount</th><th colspan=2>Options</th></tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM vatcodes ORDER BY code";
    $classRslt = db_exec ($sql) or errDie ("Unable to retrieve Classificlassions from database.");
	if (pg_numrows ($classRslt) < 1) {
		return "<li>There are no vat codes in Cubit.<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='vatcodes-add.php'>Add VAT Code</a></td></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";
	}
	while ($class = pg_fetch_array ($classRslt)) {
		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printClass .= "<tr bgcolor='$bgColor'><td>$class[code]</td><td>$class[description]</td><td>$class[zero]</td>
		<td>$class[vat_amount]</td><td><a href='vatcodes-edit.php?id=$class[id]'>Edit</a></td>";
		$printClass .= "<td><a href='vatcodes-rem.php?id=$class[id]'>Remove</a></td></tr>";
		$i++;
	}

	$printClass .= "</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='vatcodes-add.php'>Add VAT Code</a></td></tr>
	<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $printClass;
}
?>
