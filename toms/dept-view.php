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
$OUTPUT = printDept ();

require ("../template.php");

# show stock
function printDept ()
{
	# Set up table to display in
	$printDept = "
    <h3>Departments</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Dept No</th><th>Department</th><th>Income Account</th><th>Debtors Control Account</th><th>Creditors Control Account</th></tr>";

	# connect to database
	db_conn ("exten");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
    $deptRslt = db_exec ($sql) or errDie ("Unable to retrieve Departments from database.");
	if (pg_numrows ($deptRslt) < 1) {
		return "<li>There are no Departments in Cubit.";
	}
	while ($dept = pg_fetch_array ($deptRslt)) {
		# get ledger account name
		core_connect();
		$sql = "SELECT accname FROM accounts WHERE accid = '$dept[incacc]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$accinc = pg_fetch_array($accRslt);

		# get debtors account name
		$sql = "SELECT accname FROM accounts WHERE accid = '$dept[debtacc]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$accdebt = pg_fetch_array($accRslt);

		# get creditors account name
		$sql = "SELECT accname FROM accounts WHERE accid = '$dept[credacc]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$acccred = pg_fetch_array($accRslt);

		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printDept .= "<tr bgcolor='$bgColor'><td>$dept[deptno]</td><td>$dept[deptname]</td><td>$accinc[accname]</td><td>$accdebt[accname]</td><td>$acccred[accname]</td><td><a href='dept-edit.php?deptid=$dept[deptid]'>Edit</a></td>";
		$printDept .= "<td><a href='dept-rem.php?deptid=$dept[deptid]'>Remove</a></td></tr>";
		$i++;
	}

	$printDept .= "</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='dept-add.php'>Add Department</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $printDept;
}
?>
