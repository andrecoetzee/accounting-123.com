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
$OUTPUT = printCust ();

require ("../template.php");

# show stock
function printCust ()
{
	# Set up table to display in
	$printCust = "
    <h3>Current Customers</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Acc no.</th><th>Title</th><th>Surname/Company</th><th>Initials</th><th>Business Tel</th><th>Home Tel</th><th>Category</th><th>Classificustion</th><th>Overdue</th></tr>";

	# connect to database
	db_conn ("toms");

	# Query server
	$i = 0;
    $sql = "SELECT * FROM customers ORDER BY accno ASC";
    $custRslt = db_exec ($sql) or errDie ("Unable to retrieve Customers from database.");
	if (pg_numrows ($custRslt) < 1) {
		return "<li>There are no Customers in Cubit.";
	}
	while ($cust = pg_fetch_array ($custRslt)) {
		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printCust .= "<tr bgcolor='$bgColor'><td>$cust[accno]</td><td align=center>$cust[title]</td><td>$cust[surname]</td><td>$cust[init]</td><td>$cust[bustel]</td><td>$cust[hometel]</td><td>$cust[category]</td><td>$cust[class]</td><td>".CUR." $cust[overdue]</td><td><a href='cust-det.php?custid=$cust[custid]'>Details</a></td><td><a href='cust-edit.php?custid=$cust[custid]'>Edit</a></td>";
		$printCust .= "<td><a href='cust-rem.php?custid=$cust[custid]'>Remove</a></td></tr>";
		$i++;
	}

	$printCust .= "</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='cust-add.php'>Add Customer</a></td></tr>
		<tr class='bg-odd'><td><a href='toms-settings.php'>Settings</a></td></tr>
		<tr class='bg-odd'><td><a href='index.php'>Index</a></td></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $printCust;
}
?>
