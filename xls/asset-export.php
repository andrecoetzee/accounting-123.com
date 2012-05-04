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

# show asset ledger
$OUTPUT = AssetLedg ();

require ("../template.php");

# show stock
function AssetLedg ()
{
	# Set up table to display in
	$Assets = "
	<h3>Asset Ledger</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Group</th><th>Serial</th><th>Location</th><th>Description</th><th>Date Bought</th><th>Date Added</th><th>Cost Amount</th><th>Net Value</th></tr>";

	db_connect();

	$i = 0;
	$tot=0;
	$totnet=0;
	$Sl = "SELECT * FROM assets WHERE div = '".USER_DIV."' ORDER BY serial";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve Asset Ledger from database.");
	if (pg_numrows ($Rs) < 1) {
		return "<li>There are no Assets recorded on Cubit.";
	}
	while ($Led = pg_fetch_array ($Rs))
	{
		$netval = sprint($Led['amount'] - $Led['accdep']);
		$Led['amount'] = sprint($Led['amount']);

		# Get group
		db_connect();
		$sql = "SELECT * FROM assetgrp WHERE grpid = '$Led[grpid]' AND div = '".USER_DIV."'";
		$grpRslt = db_exec($sql);
		$grp = pg_fetch_array($grpRslt);

		$tot = $tot + $Led['amount'];
		$totnet = $totnet + $netval;
		
		$Assets .= "<tr><td>$grp[grpname]</td><td>$Led[serial]</td><td>$Led[locat]</td><td>$Led[des]</td><td>$Led[bdate]</td><td>$Led[date]</td><td align=right>".CUR." $Led[amount]</td><td align=right>".CUR." $netval</td></tr>";
		$i++;
	}
	$tot = sprint($tot);
	$totnet = sprint($totnet);
	$Assets .= "<tr><td colspan=6>Total Assets: $i </td><td align=right>".CUR." $tot</td><td align=right>".CUR." $totnet</td></tr>";

	$Assets .= "</table>";
	
	include("temp.xls.php");
	Stream("Assets", $Assets);

	return $Assets;
}
?>
