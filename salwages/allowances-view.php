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

# get settings
require ("../settings.php");

$OUTPUT = viewAllow ($HTTP_POST_VARS);

# display output
require ("../template.php");



# view entries
function viewAllow ()
{

	# connect to db
	db_connect ();

	# select entries from db
	$i = 0;
	$viewAllow = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Allowance name</th>
				<th>Taxable</th>
				<th>Account</th>
				<th>Type</th>
			</tr>";

	$sql = "SELECT * FROM allowances WHERE div = '".USER_DIV."' ORDER BY allowance";
	$allowRslt = db_exec ($sql) or errDie ("Unable to select allowances from database.");
	if (pg_numrows ($allowRslt) < 1) {
		return "<li class='err'>No allowances found in database.</li><br>"
		.mkQuickLinks(
			ql("allowance-add.php", "Add Allowance"),
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	}
	while ($myAllow = pg_fetch_array ($allowRslt)) {
		# get ledger account name
		core_connect();
		$sql = "SELECT accname FROM accounts WHERE accid = '$myAllow[accid]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$acc = pg_fetch_array($accRslt);

		$viewAllow .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$myAllow[allowance]</td>
				<td>$myAllow[taxable]</td><td>$acc[accname]</td>
				<td>$myAllow[type]</td>
				<td><a href='allowance-edit.php?id=$myAllow[id]'>Edit</a></td>
			</tr>\n";
		$i++;
	}
	$viewAllow .= "
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='#88BBFF'>
				<td><a href='employee-resources.php'>Employee Resources</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $viewAllow;

}



?>