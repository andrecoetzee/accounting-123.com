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

// show current users
$OUTPUT = printDepts ();


require ("template.php");




/*
 * Functions
 *
 */

// Prints a form to enter new stock details into

function printDepts ()
{

	// Connect to database
	Db_Connect ();

	// Query server
	$sql = "SELECT * FROM depts ORDER BY dept";
	$Rslt = db_exec ($sql) or errDie ("ERROR: Unable to view User Departments", SELF);          // Die with custom error if failed

	if (pg_numrows ($Rslt) < 1) {
		$OUTPUT = "No User Departments currently in database.";
	} else {
		// Set up table to display in

		$OUTPUT = "
			<h3>View Current User Departments</h3>
			<table ".TMPL_tblDflts." width='300'>
				<tr>
					<th>User Department</th>
					<th colspan='2'>Options</th>
				</tr>";

		// display all stock
		for ($i = 0; $dep =pg_fetch_array ($Rslt); $i++) {
			$OUTPUT .= "
				<tr class='".bg_class()."'>
					<td>$dep[dept]</td>
					<td><a href='admin-deptedit.php?deptid=$dep[deptid]'>Edit</a></td>
					<td><a href='admin-deptrem.php?deptid=$dep[deptid]'>Remove</td>
				</tr>";
		}
		$OUTPUT .= "</table>";
	}

    $OUTPUT .= "
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='admin-deptadd.php'>Add Department</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

	// call template to display the info and die
	return $OUTPUT;

}


?>