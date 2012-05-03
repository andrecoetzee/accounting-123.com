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
require ("../libs/ext.lib.php");

# show current stock
$OUTPUT = printSupp ();

require ("../template.php");

# show stock
function printSupp ()
{
	# Set up table to display in
	$printSupp = "<h3>Creditors Age Analysis</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Acc no.</th><th>Suppliers</th><th>Current</th><th>30 days</th><th>60 days</th><th>90 days</th><th>120 days</th><th>Total Outstanding</th></tr>";

	# connect to database
	db_connect();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM suppliers ORDER BY supname ASC";
    $suppRslt = db_exec ($sql) or errDie ("Unable to retrieve Suppliers from database.");
	if (pg_numrows ($suppRslt) < 1) {
		return "<li>There are no Suppliers in Cubit.";
	}

	# totals
	$totcurr = 0;
	$tot30 = 0;
	$tot60 = 0;
	$tot90 = 0;
	$tot120 = 0;
	$alltot = 0;

	while ($supp = pg_fetch_array ($suppRslt)) {
		# Get all ages
		$curr = age($supp['supid'], 29);
		$age30 = age($supp['supid'], 59);
		$age60 = age($supp['supid'], 89);
		$age90 = age($supp['supid'], 119);
		$age120 = age($supp['supid'], 149);

		# Suppliers total
		$supptot = ($curr + $age30 + $age60 + $age90 + $age120);

		# Alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printSupp .= "<tr bgcolor='$bgColor'><td>$supp[div]-$supp[supno]</td><td>$supp[supname]</td><td>".CUR." $curr</td><td>".CUR." $age30</td><td>".CUR." $age60</td><td>".CUR." $age90</td><td>".CUR." $age120</td><td>".CUR." $supptot</td></tr>";

		# hold totals
		$totcurr += $curr;
		$tot30 += $age30;
		$tot60 += $age60;
		$tot90 += $age90;
		$tot120 += $age120;
		$alltot += $supptot;
		$i++;
	}

	$printSupp .= "<tr><td><br></td></tr>
	<tr class='bg-even'><td colspan=2><b>Totals</b></td><td><b>".CUR." $totcurr</b></td><td><b>".CUR." $tot30</b></td><td><b>".CUR." $tot60</b></td><td><b>".CUR." $tot90</b></td><td><b>".CUR." $tot120</b></td><td><b>".CUR." $alltot</b></td></tr>
	<tr><td><br></td></tr>

	<!--
	<tr><td align=center colspan=10>
		<form action='../xls/cred-age-analysis-xls.php' method=post name=form>
		<input type=submit name=xls value='Export to spreadsheet'>
		</form>
	</td></tr>
	-->

	</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='../supp-new.php'>Add Supplier</a></td></tr>
		<tr class='bg-odd'><td><a href='../supp-view.php'>View Suppliers</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $printSupp;
}

# check age
function age($supid, $days)
{
	$ldays  = $days;
	if($days == 149)
		$ldays = (365 * 10);

	# Get the current outstanding
	$sql = "SELECT sum(balance) FROM suppurch WHERE supid = '$supid' AND pdate >='".extlib_ago($ldays)."' AND pdate <='".extlib_ago($days-30)."'";
	$rs = db_exec($sql) or errDie("Unable to access database");
	$sum = pg_fetch_array($rs);

	/*
	# Get the current outstanding
	$sql = "SELECT sum(balance) FROM purch_int WHERE supid = '$supid' AND balance > 0 AND received = 'y' AND pdate >='".extlib_ago($days)."' AND pdate <='".extlib_ago($days-30)."'";
	$rsint = db_exec($sql) or errDie("Unable to access database");
	$sumint = pg_fetch_array($rsint);
	*/

	# Take care of nasty zero
	return $sum['sum'] += 0;
}
?>
