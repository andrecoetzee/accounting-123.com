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
					<h3>Classifications</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Classification Code</th>
							<th>Classification</th>
							<th colspan='2'>Options</th>
						</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM stockclass WHERE div = '".USER_DIV."' ORDER BY classname ASC";
    $classRslt = db_exec ($sql) or errDie ("Unable to retrieve Classifications from cubit.");
	if (pg_numrows ($classRslt) < 1) {
		return "<li class='err'>There are no Classifications in Cubit.</li>";
	}
	while ($class = pg_fetch_array ($classRslt)) {
		$printClass .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$class[classcode]</td>
								<td>$class[classname]</td>
								<td><a href='stockclass-edit.php?clasid=$class[clasid]'>Edit</a></td>
								<td><a href='stockclass-rem.php?clasid=$class[clasid]'>Remove</a></td>
							</tr>";
		$i++;
	}

	$printClass .= "
						</table>
						<p>
						<table ".TMPL_tblDflts." width='15%'>
							<tr><td><br></td></tr>
							<tr>
								<th>Quick Links</th>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td><a href='stockclass-add.php'>Add Classification</a></td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td><a href='main.php'>Main Menu</a></td>
							</tr>
						</table>";

	return $printClass;
}
?>
