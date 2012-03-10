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
$OUTPUT = printGrp ();

require ("template.php");

# show stock
function printGrp ()
{
	# Set up table to display in
	$printGrp = "
    <h3>Asset Groups</h3>
    <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
    <tr><th>Group</th><th>Cost Account</th><th>Accumulated Depreciation Account</th><th>Depreciation Account</th><th colspan=2>Options</th></tr>";

	# connect to database
	db_connect ();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM assetgrp WHERE div = '".USER_DIV."' ORDER BY grpname ASC";
    $GrpRslt = db_exec ($sql) or errDie ("Unable to retrieve Asset Groups from database.");
	if (pg_numrows ($GrpRslt) < 1) {
		return "
				<li> There are no Assets Groups in Cubit.</li><br>"
				.mkQuickLinks(
					ql("assetgrp-new.php", "Add Asset Group"),
					ql("assetgrp-view.php", "View Asset Groups")
				);
	}
	while ($Grp = pg_fetch_array ($GrpRslt)) {
		# get ledger account name(cost)
		core_connect();
		$sql = "SELECT accname FROM accounts WHERE accid = '$Grp[costacc]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$acccost = pg_fetch_array($accRslt);

		# get ledger account name(accum dep)
		$sql = "SELECT accname FROM accounts WHERE accid = '$Grp[accdacc]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$acdacc = pg_fetch_array($accRslt);

		# get ledger account name(dep)
		$sql = "SELECT accname FROM accounts WHERE accid = '$Grp[depacc]' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$accdep = pg_fetch_array($accRslt);

		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$printGrp .= "<tr bgcolor='$bgColor'><td>$Grp[grpname]</td><td>$acccost[accname]</td><td>$acdacc[accname]</td><td>$accdep[accname]</td><td><a href='assetgrp-edit.php?grpid=$Grp[grpid]'>Edit</a></td>";
		$printGrp .= "<td><a href='assetgrp-rem.php?grpid=$Grp[grpid]'>Remove</a></td></tr>";
		$i++;
	}

	$printGrp .= "</table>
    <p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=15%>
        <tr><td><br></td></tr>
        <tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='assetgrp-new.php'>Add Asset Group</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $printGrp;
}
?>
