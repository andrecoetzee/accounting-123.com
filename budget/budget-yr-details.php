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
	$vfromyr = $YEARS[$bud['fromprd']];
	$vtoyr = $YEARS[$bud['toprd']];
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
			
			$list .= "<tr class='bg-odd'><td>$cc[centercode] - $cc[centername]</td>";
			
			db_connect();
			$lstRs = db_exec("SELECT * FROM buditems WHERE id = '$bit[id]' AND budid = '$budid'");
			while($lst = pg_fetch_array($lstRs)){
				$list .= "<td align=right>".CUR." $lst[amt]</td>";
			}
			$list .= "</tr>";
		}
	}elseif($bud['budfor'] == 'acc'){
		$head = "<tr><th>Accounts</th>";
		
		$sql = "SELECT DISTINCT id FROM buditems WHERE budid = '$budid'";
    	$bitRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");

		while($bit = pg_fetch_array($bitRslt)){
			$accRs = get("core", "*", "accounts", "accid", $bit['id']);
			$acc  = pg_fetch_array($accRs);
			$list .= "<tr class='bg-odd'><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";
			
			db_connect();
			$lstRs = db_exec("SELECT * FROM buditems WHERE id = '$bit[id]' AND budid = '$budid'");
			while($lst = pg_fetch_array($lstRs)){
				$list .= "<td align=right>".CUR." $lst[amt]</td>";
			}
			$list .= "</tr>";
		}
	}

	# Budget headings
	if($bud['fromprd'] < $bud['toprd']){
		for($i = $bud['fromprd']; $i <= $bud['toprd']; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}	
	}elseif($bud['fromprd'] > $bud['toprd']){
		for($i = $bud['fromprd']; $i < 10; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
		for($i = 0; $i <= $bud['toprd']; $i++){
			$head .= "<th>$YEARS[$i]</th>";
		}
	}else{
		$i = $bud['toprd'];
		$head .= "<th>$YEARS[$i]</th>";
	}
	$head .= "</tr>";
	
	// $totamt = sprint($totamt);
	// $list .= "<tr class='bg-even'><td><b>Total Budget Amount</b></td><td align=right><b>".CUR." $totamt</b></td></tr>";
	
	/* End Toggle Options */

	$details = "<center><h3> Yearly Budget Details </h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr><th colspan=2>Details</th></tr>
	<tr class='bg-odd'><td>Budget Name</td><td>$bud[budname]</td></tr>
	<tr><td><br></td></tr>
	<tr><th colspan=2>Options</th></tr>
	<tr class='bg-odd'><td>Budget For</td><td>$vbudfor</td>
	<tr class='bg-even'><td>Budget Type</td><td>$vbudtype</td>
	<tr class='bg-odd'><td>Budget Year</td><td>$vfromyr to $vtoyr</td>
	<tr><td><br></td></tr>
	</table>

	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	$head
	$list
	</table>

	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center>
	<tr><td><br></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr class='bg-odd'><td align=center><a href='budget-yr-new.php'>New Yearly Budget</td></tr>
		<tr class='bg-odd'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $details;
}
?>
