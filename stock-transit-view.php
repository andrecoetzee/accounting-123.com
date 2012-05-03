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

# Display default output
$OUTPUT = printStk();

require ("template.php");

# show stock in transit
function printStk ()
{
	# connect to database
	db_connect ();

	# Query server
	$searchs = "SELECT * FROM transit WHERE div = '".USER_DIV."' ORDER BY trandate DESC";
	$tranRslt = db_exec ($searchs) or errDie ("Unable to retrieve stock in transit from database.");
	if (pg_numrows ($tranRslt) < 1) {
		return "<li class=err> There are no stock items in transit.</li>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
			<tr><td><br></td></tr>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='stock-transfer-bran.php'>Transfer Stock</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	}

	# Set up table to display in
	$printStk = "
    <h3>View Stock in transit</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>From Branch</th><th>From Warehouse</th><th>Stock Code</th><th>Description</th><th>Number of units</th><th>Cost Amount</th><th>To Branch</th><th>To Warehouse</th><th colspan=6>Options</th></tr>";

	$i = 0;
	while ($tran = pg_fetch_array ($tranRslt)) {

		# Select Stock
		db_connect();
		$sql = "SELECT * FROM stock WHERE stkid = '$tran[stkid]' AND div = '".USER_DIV."'";
		$stkRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($stkRslt) < 1){
			return "<li> Invalid Stock ID.";
		}else{
			$stk = pg_fetch_array($stkRslt);
		}

		# Original Branch
		$sql = "SELECT * FROM branches WHERE div = '$stk[div]'";
		$branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($branRslt) < 1){
			return "<li> Invalid Branch ID.";
		}else{
			$bran = pg_fetch_array($branRslt);
		}

		# Selected Branch
		$sql = "SELECT * FROM branches WHERE div = '$tran[sdiv]'";
		$sbranRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
		if(pg_numrows($sbranRslt) < 1){
			return "<li> Invalid Branch ID.";
		}else{
			$sbran = pg_fetch_array($sbranRslt);
		}

		db_conn("exten");
		# get warehouse
		$sql = "SELECT * FROM warehouses WHERE whid = '$stk[whid]' AND div = '".USER_DIV."'";
		$whRslt = db_exec($sql);
		$wh = pg_fetch_array($whRslt);

		# get warehouse
		$sql = "SELECT * FROM warehouses WHERE whid = '$tran[swhid]' AND div = '$tran[sdiv]'";
		$swhRslt = db_exec($sql);
		$swh = pg_fetch_array($swhRslt);


		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printStk .= "<tr bgcolor='$bgColor'><td>$bran[branname]</td><td>$wh[whname]</td><td>$stk[stkcod]</td><td>$stk[stkdes]</td><td align=right>$tran[tunits]</td><td align=right>".CUR." $tran[cstamt]</td><td>$sbran[branname]</td><td>$swh[whname]</td>";
		$printStk .= "<td><a href='stock-transit-can.php?id=$tran[id]'>Cancel</a></td><td><a href='stock-transit-del.php?id=$tran[id]'>Delivered</a></td></tr>";
		$i++;
	}

	$printStk .= "</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='stock-transfer-bran.php'>Transfer Stock</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $printStk;
}
?>
