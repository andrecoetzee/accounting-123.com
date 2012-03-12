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
	$vfromprd = $PERIODS[$bud['fromprd']];
	$vtoprd = $PERIODS[$bud['toprd']];
	$bud['edate'] = ext_rdate($bud['edate']);
		
	/* Toggle Options */
	$list = "";
	$totamt = 0;
	
	db_connect();
	# budget for
	if($bud['budfor'] == 'cost'){
		$sql = "SELECT DISTINCT prd FROM buditems WHERE budid = '$budid'";
    	$bitRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");
		
		while($bit = pg_fetch_array($bitRslt)){
			$prd = $bit['prd'];
			$list .= "
			<tr><td colspan=5><h3>$PERIODS[$prd]</h3></td></tr>
			<tr><td><b>Cost Centers</b></td><td><b>Budget Amount ".CUR."</b></td><td><b>Actual Amount ".CUR."</b></td><td><b>Difference ".CUR."</b></td><td><b>Difference %</b></td></tr>";
			
			db_connect();
			$cclRs = db_exec("SELECT * FROM buditems WHERE prd = '$bit[prd]' AND budid  = '$budid'");
			while($ccl = pg_fetch_array($cclRs)){
				$ccRs = get("cubit", "*", "costcenters", "ccid", $ccl['id']);
				$cc  = pg_fetch_array($ccRs);
			
				$list .= "<tr><td>$cc[centercode] - $cc[centername]</td>";

				db_connect();
				$lstRs = db_exec("SELECT * FROM buditems WHERE prd = '$bit[prd]' AND id = '$ccl[id]' AND budid  = '$budid'");
				while($lst = pg_fetch_array($lstRs)){
					db_conn($bit['prd']);
					$dbalRs = db_exec("SELECT sum(amount) FROM cctran WHERE ccid = '$ccl[id]' AND trantype = 'dt'");
					$dbal = pg_fetch_array($dbalRs);
					$cbalRs = db_exec("SELECT sum(amount) FROM cctran WHERE ccid = '$ccl[id]' AND trantype = 'ct'");
					$cbal = pg_fetch_array($cbalRs);

// 					if($bud['budtype'] == "inc"){
// 						$bal = sprint($dbal['sum'] - $cbal['sum']);
// 						$diff = sprint($bal - $lst['amt']);
// 					}else{
// 						$bal = sprint($dbal['sum'] - $cbal['sum']);
// 						$diff = sprint($lst['amt'] - $bal);
// 					}
					
					if($bud['budtype'] == "inc"){
						$bal = sprint($dbal['sum']);
						$diff = sprint($bal - $lst['amt']);
					}else{
						$bal = sprint($cbal['sum']);
						$diff = sprint($lst['amt'] - $bal);
					}


					if($lst['amt'] <> 0){
						$perc = sprint(($diff/$lst['amt']) * 100);
					}else{
						$perc = sprint(0);
					}

					$list .= "<td align=right>$lst[amt]</td><td align=right>$bal</td><td align=right>$diff</td><td align=right>$perc</td>";
				}
				$list .= "</tr>";
			}
			$list .= "<tr><td  colspan=5><br></td></tr>";
		}
	}elseif($bud['budfor'] == 'acc'){
		$sql = "SELECT DISTINCT prd FROM buditems WHERE budid = '$budid'";
    	$bitRslt = db_exec ($sql) or errDie ("Unable to retrieve Budgets from database.");

		while($bit = pg_fetch_array($bitRslt)){
			$prd = $bit['prd'];
			$list .= "
			<tr><td colspan=5><h3>$PERIODS[$prd]</h3></td></tr>
			<tr><td><b>Cost Centers</b></td><td><b>Budget Amount ".CUR."</b></td><td><b>Actual Amount ".CUR."</b></td><td><b>Difference ".CUR."</b></td><td><b>Difference %</b></td></tr>";

			db_connect();
			$cclRs = db_exec("SELECT * FROM buditems WHERE prd = '$bit[prd]' AND budid  = '$budid'");
			while($ccl = pg_fetch_array($cclRs)){
				$ccRs = get("core", "*", "accounts", "accid", $ccl['id']);
				$cc  = pg_fetch_array($ccRs);

				$list .= "<tr><td>$cc[topacc]/$cc[accnum] - $cc[accname]</td>";

				db_connect();
				$lstRs = db_exec("SELECT * FROM buditems WHERE prd = '$bit[prd]' AND id = '$ccl[id]' AND budid  = '$budid'");
				while($lst = pg_fetch_array($lstRs)){
					db_conn($bit['prd']);
					$dbalRs = db_exec("SELECT sum(amount) FROM transect WHERE debit = '$ccl[id]'");
					$dbal = pg_fetch_array($dbalRs);
					$cbalRs = db_exec("SELECT sum(amount) FROM transect WHERE credit = '$ccl[id]'");
					$cbal = pg_fetch_array($cbalRs);

					if($bud['budtype'] == "inc"){
						$bal = sprint($cbal['sum'] - $dbal['sum']);
						$diff = sprint($bal - $lst['amt']);
					}else{
						$bal = sprint($dbal['sum'] - $cbal['sum']);
						$diff = sprint($lst['amt'] - $bal);
					}
					
					if($lst['amt'] <> 0){
						$perc = sprint(($diff/$lst['amt']) * 100);
					}else{
						$perc = sprint(0);
					}

					$list .= "<td align=right>$lst[amt]</td><td align=right>$bal</td><td align=right>$diff</td><td align=right>$perc</td>";
				}
				$list .= "</tr>";
			}
			$list .= "<tr><td  colspan=5><br></td></tr>";
		}
	}

	$details = "<center><h3> Budget Report </h3></center>
	<table cellpadding='5' cellspacing='0' border=1 width=340 bordercolor='#000000'>
	<tr><td colspan=2 align='center'><b>Details</b></th></tr>
	<tr><td>Budget Name</td><td>$bud[budname]</td></tr>
	<tr><td>Budget Type</td><td>$vbudtype</td>
	<tr><td>Budget Period</td><td>$vfromprd to $vtoprd</td>
	</table>
	<p>
	<table cellpadding='5' cellspacing='0' border=1 width=680 bordercolor='#000000'>
	$list
	</table>";

	$OUTPUT = $details;
	require("../tmpl-print.php");
}
?>
