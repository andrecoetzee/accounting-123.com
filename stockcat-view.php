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
$OUTPUT = printCat ();

require ("template.php");

# show stock
function printCat ()
{
	# Set up table to display in
	$printCat = "
					<h3>View Stock Category</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Category Code</th>
							<th>Category Name</th>
							<th>Stock Description</th>
							<th colspan='3'>Options</th>
						</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM stockcat WHERE div = '".USER_DIV."' ORDER BY cat ASC";
    $catRslt = db_exec ($sql) or errDie ("Unable to retrieve stock categories from database.");
	if (pg_numrows ($catRslt) < 1) {
		return "<li>There are no stock categories in Cubit.";
	}

	while ($cat = pg_fetch_array ($catRslt)) {

		$printCat .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$cat[catcod]</td>
								<td align='center'>$cat[cat]</td>
								<td>$cat[descript]</td>
								<td><a href='stockcat-det.php?catid=$cat[catid]'>Details</a></td>
								<td><a href='stockcat-edit.php?catid=$cat[catid]'>Edit</a></td>";

		$sql = "SELECT * FROM stock WHERE catid='$cat[catid]'";
    	$catsRslt = db_exec ($sql) or ereDie ("Unable to retrieve stock categories from database.");
		if (pg_numrows ($catsRslt) < 1) {
			$printCat .= "
								<td><a href='stockcat-rem.php?catid=$cat[catid]'>Remove</a></td>
							</tr>";
		}else{
			$printCat .= "
							</tr>";
		}
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
							<td><a href='stockcat-add.php'>Add Stock Category</a></td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
	return $printCat;

}


?>