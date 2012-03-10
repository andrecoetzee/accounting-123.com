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

$OUTPUT = viewFringe();

# display output
require ("../template.php");




# view entries
function viewFringe()
{

	# connect to db
	db_connect ();

	# select entries from db
	$i = 0;
	$OUTPUT = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Name</th>
				<th>Account</th>
				<th>Type</th>
			</tr>";

	$sql = "SELECT * FROM fringebens WHERE div = '".USER_DIV."' ORDER BY fringeben";
	$rslt = db_exec ($sql) or errDie ("Unable to select fringe benefits from database.");
	if (pg_numrows ($rslt) < 1) {
		return "
			<li class='err'>No fringe benefits found.</li><br>"
			.mkQuickLinks(
				ql("fringeben-add.php", "Add Fringe Benefit"),
				ql("../admin-employee-add.php", "Add Employee"),
				ql("../admin-employee-view.php", "View Employees")
			);
	}
	while ($myFringe = pg_fetch_array ($rslt)) {
		# get ledger account name
		core_connect();
		$sql = "SELECT accname FROM accounts WHERE accid = '$myFringe[accid]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$acc = pg_fetch_array($accRslt);

		$OUTPUT .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$myFringe[fringeben]</td>
				<td>$acc[accname]</td>
				<td>$myFringe[type]</td>
			</tr>\n";
		$i++;
	}
	$OUTPUT .= "
		</table>"
		.mkQuickLinks(
			ql("fringeben-add.php", "Add Fringe Benefit"),
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);
	return $OUTPUT;

}



?>