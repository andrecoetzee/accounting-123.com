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
					<h3>Categories</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Category</th>
							<th colspan='2'>Options</th>
						</tr>";

	# connect to database
	db_conn ("exten");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM categories WHERE div = '".USER_DIV."' ORDER BY category ASC";
    $catRslt = db_exec ($sql) or errDie ("Unable to retrieve Categories from database.");
	if (pg_numrows ($catRslt) < 1) {
		return "<li>There are no Categories in Cubit.</li>";
	}
	while ($cat = pg_fetch_array ($catRslt)) {
		$printCat .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$cat[category]</td>
							<td><a href='cat-edit.php?catid=$cat[catid]'>Edit</a></td>
							<td><a href='cat-rem.php?catid=$cat[catid]'>Remove</a></td>
						</tr>";
		$i++;
	}

	$printCat .= "
					</table>
					<p>
					<table ".TMPL_tblDflts." width='15%'>
						<tr><td><br></td></tr>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='cat-add.php'>Add Category</a></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td><a href='../main.php'>Main Menu</a></td>
						</tr>
					</table>";
	return $printCat;

}


?>