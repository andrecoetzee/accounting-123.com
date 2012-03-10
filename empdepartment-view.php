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
$OUTPUT = printDep ();

require ("template.php");

# show stock
function printDep ()
{

	# Set up table to display in
	$printDep = "
		<h3>View Employee Departments</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Department</th>
				<th colspan='2'>Options</th>
			</tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;

	$sql = "SELECT * FROM departments ORDER BY department ASC";
	$depRslt = db_exec ($sql) or errDie ("Unable to retrieve employee departments from database.");
	if (pg_numrows ($depRslt) < 1) {
		return "<li>There are no employee departments in Cubit.</li>";
	}

	while ($dep = pg_fetch_array ($depRslt)) {

		$printDep .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$dep[department]</td>
				<td><a href='empdepartment-edit.php?id=$dep[id]'>Edit</a></td>";

		$sql = "SELECT * FROM employees WHERE department='$dep[id]'";
		$depRslt = db_exec ($sql) or ereDie ("Unable to retrieve employee departments from database.");
		if (pg_numrows ($depRslt) < 1) {
			$printDep .= "<td><a href='empdepartment-rem.php?id=$dep[id]'>Remove</a></td></tr>";
		}else{
			$printDep .= "</tr>";
		}
		$i++;
	}

	$printDep .= "
		</table>
		<p>
		<table ".TMPL_tblDflts." width='15%'>
			<tr><td><br></td></tr>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='empdepartment-add.php'>Add Employee Department</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $printDep;

}



?>