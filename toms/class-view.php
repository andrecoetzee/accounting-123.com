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
$OUTPUT = printClass ();

require ("../template.php");



# show stock
function printClass ()
{

	# Set up table to display in
	$printClass = "
					<h3>Classificlassions</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Classification</th>
							<th colspan='2'>Options</th>
						</tr>";

	# connect to database
	db_conn ("exten");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM class WHERE div = '".USER_DIV."' ORDER BY classname ASC";
    $classRslt = db_exec ($sql) or errDie ("Unable to retrieve Classificlassions from database.");
	if (pg_numrows ($classRslt) < 1) {
		return "<li>There are no Classificlassions in Cubit.</li>";
	}

	while ($class = pg_fetch_array ($classRslt)) {
		$printClass .= "
							<tr bgcolor='".bgcolorg()."'>
								<td>$class[classname]</td>
								<td><a href='class-edit.php?clasid=$class[clasid]'>Edit</a></td>
								<td><a href='class-rem.php?clasid=$class[clasid]'>Remove</a></td>
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
								<td><a href='class-add.php'>Add Classification</a></td>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td><a href='../main.php'>Main Menu</a></td>
							</tr>
						</table>";
	return $printClass;

}


?>