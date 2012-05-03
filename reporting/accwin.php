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
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

$OUTPUT = win($_GET);
# get templete
require("../template.php");

# details
function win($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

/* --------------------------- bal ------------------------------*/
	$report = "";
	core_connect();
	$sql = "SELECT * FROM trialgrpaccids WHERE gkey = '$gkey'";
	$gaccRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");
	while($gacc = pg_fetch_array($gaccRslt)){
		$sql = "SELECT * FROM trial_bal WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
		$accRslt = db_exec ($sql) or errDie ("Unable to view account.");
		$acc = pg_fetch_array($accRslt);
		$report .="<tr class='bg-even'><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";
		if(true){
			if($acc['debit'] > $acc['credit']){
				$acc['debit'] = sprint($acc['debit']-$acc['credit']);
				$acc['credit'] = 0;
			}

			if($acc['credit'] > $acc['debit']){
				$acc['credit'] = sprint($acc['credit']-$acc['debit']);
				$acc['debit'] = 0;
			}

			if($acc['credit'] == $acc['debit']){
				$acc['credit'] = 0;
				$acc['debit'] = 0;
			}
		}

		if(floatval($acc['debit']) == 0){
			$report .= "<td align=right> - </td>";
		}else{
			$report .= "<td align=right>$acc[debit]</td>";
		}

		if(floatval($acc['credit']) == 0){
			$report .= "<td align=right> - </td>";
		}else{
			$report .= "<td align=right>$acc[credit]</td>";
		}
		$report .= "</tr>";
	}
/* -- Final Layout -- */
	$details = "<center><h3>Trial Balance Note 000/00$gkey</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=360>
	<tr><th>Accounts</th><th>Debit</th><th>Credit</th></tr>
	$report
	<tr><td><br></td></tr>
	<tr><td colspan=3 align=center><input type=button value='[X]Close' onClick='javascript:window.close();'></td></tr>
	</table>";

	return $details;
}
?>
