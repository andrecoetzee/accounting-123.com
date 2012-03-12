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

$OUTPUT = viewDeducts ($_POST);

# display output
require ("../template.php");




# view entries
function viewDeducts ()
{

	# connect to db
	db_connect ();

	# select entries from db
	$i = 0;
	$viewDeducts = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Reference no</th>
				<th>Deduction name</th>
				<th>Creditor name</th>
				<th>Account</th>
				<th>Expense Account</th>
				<th>Creditor details</th>
				<th>Type</th>
			</tr>";

	$sql = "SELECT * FROM salded WHERE div = '".USER_DIV."' ORDER BY refno";
	$salRslt = db_exec ($sql) or errDie ("Unable to select salary deductions from database.");
	if (pg_numrows ($salRslt) < 1) {
		return "
			<li class='err'>No salary deductions found in database.</li><br>"
			.mkQuickLinks(
				ql("../admin-employee-add.php", "Add Employee"),
				ql("../admin-employee-view.php", "View Employees")
			);
	}

	while ($mySal = pg_fetch_array ($salRslt)) {
		# get ledger account name
		core_connect();
		$sql = "SELECT accname FROM accounts WHERE accid = '$mySal[accid]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$acc = pg_fetch_array($accRslt);

		if (isset($mySal["expaccid"])) {
			$sql = "SELECT accname FROM accounts WHERE accid='$mySal[expaccid]' AND div = '".USER_DIV."'";
			$expRslt = db_exec($sql);
			$exp = pg_fetch_array($expRslt);
		} else {
			$exp["accname"] = "";
		}

		$viewDeducts .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$mySal[refno]</td>
				<td>$mySal[deduction]</td>
				<td>$mySal[creditor]</td>
				<td>$acc[accname]</td>
				<td>$exp[accname]</td>
				<td>$mySal[details]</td>
				<td>$mySal[type]</td>
				<td><a href='salded-edit.php?refno=$mySal[refno]'>Edit</a></td>
			</tr>\n";
		$i++;
	}
	$viewDeducts .= "</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $viewDeducts;

}



?>