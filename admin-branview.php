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
$OUTPUT = printBran ();

require ("template.php");

# show stock
function printBran ()
{
	# Set up table to display in
	$printBran = "
    <h3>View Branches</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Branch Code</th><th>Branch Name</th><th>Details</th><th colspan=2>Options</th></tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM branches ORDER BY branname ASC";
    $branRslt = db_exec ($sql) or errDie ("Unable to retrieve branches from database.");
	if (pg_numrows ($branRslt) < 1) {
		return "<li>There are no branches in Cubit.";
	}
	while ($bran = pg_fetch_array ($branRslt)) {
		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printBran .= "<tr bgcolor='$bgColor'><td>$bran[brancod]</td><td align=center>$bran[branname]</td><td>$bran[brandet]</td><td><a href='admin-branedit.php?div=$bran[div]'>Edit</a></td>";

		core_connect();
		$sql = "SELECT accid FROM accounts WHERE div = '$bran[div]' AND accnum != '999'";
    	$cRslt = db_exec ($sql) or errDie ("Unable to retrieve branches from database.");
		if (pg_numrows ($cRslt) < 1){
			$printBran .= "<td><a href='admin-branrem.php?div=$bran[div]'>Remove</a></td>";
		}else{
			$printBran .= "<td><br></td></tr>";
		}

		$printBran .= "</tr>";
		$i++;
	}

	$printBran .= "</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='admin-branadd.php'>Add Branch</a></td></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $printBran;
}
?>
