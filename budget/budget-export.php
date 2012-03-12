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
require ("../core-settings.php");
require("../libs/ext.lib.php");

if (isset($_GET["budid"])){
	$OUTPUT = details($_GET);
} else {
	# Display default output
	$OUTPUT = "<li class=err> - Invalid use of module.";
}

# Get template
require("../template.php");

# Default view
function details($_GET)
{
	# Get vars
	foreach ($_GET as $key => $value) {
		$$key = $value;
	}

	# Query server
    db_connect();
	$sql = "SELECT * FROM budgets WHERE budid = '$budid'";
	$budRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");
	if (pg_numrows ($budRslt) < 1) {
		return "<li class=err> - Invalid Budget.";
	}
	$bud = pg_fetch_array ($budRslt);

	require("budget.lib.php");
	$vbudtype = $TYPES[$bud['budtype']];
	$vbudfor = $BUDFOR[$bud['budfor']];
	$vfromprd = $PERIODS[$bud['fromprd']];
	$vtoprd = $PERIODS[$bud['toprd']];
	$bud['edate'] = ext_rdate($bud['edate']);

	/* Toggle Options */
	$list = "";
	$totamt = 0;

	db_connect();
	# budget for
	if($bud['budfor'] == 'cost'){
		$head = "<tr><th>Cost Centers</th>";

		$sql = "SELECT DISTINCT id FROM buditems WHERE budid = '$budid'";
    	$bitRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");

		while($bit = pg_fetch_array($bitRslt)){
			$ccRs = get("cubit", "*", "costcenters", "ccid", $bit['id']);
			$cc  = pg_fetch_array($ccRs);

			$list .= "<tr><td>$cc[centercode] - $cc[centername]</td>";

			db_connect();
			$lstRs = db_exec("SELECT * FROM buditems WHERE id = '$bit[id]' AND budid = '$budid'");
			$tot_annual = 0;
			while($lst = pg_fetch_array($lstRs)){
				$tot_annual += $lst["amt"];
				$list .= "<td align=right>".CUR." $lst[amt]</td>";
			}
			$tot_annual = sprint($tot_annual);
			$list .= "
				<td>".CUR." $tot_annual</td>
			</tr>";
		}
	}elseif($bud['budfor'] == 'acc'){
		$head = "<tr><th>Accounts</th>";

		$sql = "SELECT DISTINCT id FROM buditems WHERE budid = '$budid'";
    	$bitRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");

		while($bit = pg_fetch_array($bitRslt)){
			$accRs = get("core", "*", "accounts", "accid", $bit['id']);
			$acc  = pg_fetch_array($accRs);
			$list .= "<tr><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";

			db_connect();
			$lstRs = db_exec("SELECT * FROM buditems WHERE id = '$bit[id]' AND budid = '$budid'");
			$tot_annual = 0;
			while($lst = pg_fetch_array($lstRs)){
				$tot_annual += $lst["amt"];
				$list .= "<td align=right>".CUR." $lst[amt]</td>";
			}
			$tot_annual = sprint($tot_annual);
			$list .= "
				<td>".CUR." $tot_annual</td>
			</tr>";
		}
	}

	# Budget headings
	if($bud['fromprd'] < $bud['toprd']){
		for($i = $bud['fromprd']; $i <= $bud['toprd']; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
	}elseif($bud['fromprd'] > $bud['toprd']){
		for($i = $bud['fromprd']; $i <= 12; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
		for($i = 1; $i <= $bud['toprd']; $i++){
			$head .= "<th>$PERIODS[$i]</th>";
		}
	}else{
		$i = $bud['toprd'];
		$head .= "<th>$PERIODS[$i]</th>";
	}
	$head .= "
		<th>Annual Total</th>
	</tr>";

	// $totamt = sprint($totamt);
	// $list .= "<tr bgcolor='".TMPL_tblDataColor2."'><td><b>Total Budget Amount</b></td><td align=right><b>".CUR." $totamt</b></td></tr>";

	/* End Toggle Options */

	$details = "<center><h3> Budget Details </h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr><th colspan=2>Details</th></tr>
	<tr><td>Budget Name</td><td>$bud[budname]</td></tr>
	<tr><td><br></td></tr>
	<tr><th colspan=2>Options</th></tr>
	<tr><td>Budget For</td><td>$vbudfor</td>
	<tr><td>Budget Type</td><td>$vbudtype</td>
	<tr><td>Budget Period</td><td>$vfromprd to $vtoprd</td>
	<tr><td><br></td></tr>
	</table>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	$head
	$list
	</table>";

	include("../xls/temp.xls.php");
	Stream("Budget.xls", $details);
}
?>
