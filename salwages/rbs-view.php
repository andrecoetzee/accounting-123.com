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

$OUTPUT = viewAllow ($_POST);

$OUTPUT .= mkQuickLinks(
	ql("rbs-add.php","Add Reimbursement"),
	ql("../admin-employee-add.php", "Add Employee"),
	ql("../admin-employee-view.php", "View Employees")
);
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
				<th>Reimbursement name</th>
				<th>Account</th>
			</tr>";

	$sql = "SELECT * FROM rbs WHERE div = '".USER_DIV."' ORDER BY name";
	$allowRslt = db_exec ($sql) or errDie ("Unable to select allowances from database.");
	if (pg_numrows ($allowRslt) < 1) {
		return "<li class='err'>No reimbursements found in database.</li><br>";
	}

	while ($myAllow = pg_fetch_array ($allowRslt)) {
		# get ledger account name
		core_connect();
		$sql = "SELECT accname FROM accounts WHERE accid = '$myAllow[account]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$acc = pg_fetch_array($accRslt);

		$viewAllow .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$myAllow[name]</td>
				<td>$acc[accname]</td>
				<td><a href='rbs-edit.php?id=$myAllow[id]'>Edit</a></td>
			</tr>\n";
		$i++;
	}
	$viewAllow .= "</table>";
	return $viewAllow;

}



?>