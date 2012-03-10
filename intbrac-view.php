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
#
# Int-view.php :: View Int brackets
#
##

# get settings
require ("settings.php");

$OUTPUT = showInt ();

# display output
require ("template.php");



# print Int brackets in db
function showInt ()
{
	# connect to db
	db_connect ();

	# Start table, etc
	$showInt = "
					<h3>View Interest brackets</h3>
					<table ".TMPL_tblDflts.">
						<tr>
							<th>Minimum</th>
							<th>Maximum</th>
							<th>Percentage</th>
							<th>Options</th>
						</tr>";

	# Select bracs
	$i = 0;
	$sql = "SELECT * FROM intbracs ORDER BY min, max";
	$intRslt = db_exec ($sql) or errDie ("Unable to select Interest brackets from database.", SELF);
	if (pg_numrows ($intRslt) > 0) {
		while ($myInt = pg_fetch_array ($intRslt)) {
			$showInt .= "
							<tr bgcolor='".bgcolorg()."'>
								<td align='right'>".CUR." $myInt[min]</td>
								<td align='right'>".CUR." $myInt[max]</td>
								<td align='right'>$myInt[percentage]%</td>
								<td><a href='intbrac-edit.php?id=$myInt[id]'>Edit</a> | <a href='intbrac-rem.php?id=$myInt[id]'>Delete</a></td>
							</tr>\n";
			$i++;
		}
	} else {
		return "
					<li class='err'>No Interest brackets found in database.</li>
					<p>
					<table border=0 cellpadding='2' cellspacing='1'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='#88BBFF'>
							<td><a href='intbrac-add.php'>Add Interest Bracket</a></td>
						</tr>
						<tr bgcolor='#88BBFF'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</table>";
	}

	$showInt .= "
					</table>
					<p>
					<table border=0 cellpadding='2' cellspacing='1'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr bgcolor='#88BBFF'>
							<td><a href='intbrac-add.php'>Add Interest Bracket</a></td>
						</tr>
						<tr bgcolor='#88BBFF'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</table>";
	return $showInt;

}


?>