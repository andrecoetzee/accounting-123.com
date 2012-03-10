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
	<h3>Cash flow budget entries</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Date</th><th>Description</th><th>Amount</th><th colspan=3>Options</th></tr>";

	db_connect();

	$i = 0;
	$tot=0;
	$totnet=0;
	$Sl = "SELECT * FROM cf WHERE div = '".USER_DIV."' ORDER BY date DESC";
	$Rs = db_exec ($Sl) or errDie ("Unable to retrieve Asset Ledger from database.");
	if (pg_numrows ($Rs) < 1) {
		return "<li>There are no cash flow entries in Cubit.<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
		<tr><td><br></td></tr>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	}
	while ($Led = pg_fetch_array ($Rs))
	{
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$Assets .= "<tr bgcolor='$bgColor'><td>$Led[date]</td><td>$Led[description]</td><td align=right>".CUR." $Led[amount]</td>
		<td><a href='cfe-edit.php?id=$Led[id]'>Edit</a></td><td><a href='cfe-rem.php?id=$Led[id]'>Remove</a></td></tr>";
		$i++;
	}

	$Assets .= "</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $Assets;
}
?>
