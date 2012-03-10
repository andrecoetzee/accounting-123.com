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

$OUTPUT = sheet();

include("temp.xls.php");
Stream("TrialBalance", $OUTPUT);

exit;

# details
function sheet()
{
	$rep = new grp_report();
	$report = $rep->getReport();
	$selected = $rep->selected;
	$gdebit = $rep->totdebit;
	$gcredit = $rep->totcredit;
	$totdebit = 0;
	$totcredit = 0;

	$sql = "SELECT * FROM trial_bal WHERE div = '".USER_DIV."' order by topacc,accnum ASC";
	$accRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");
	while($acc = pg_fetch_array($accRslt)){
		if(in_array($acc['accid'], $selected)) continue;
		$report .= "<tr><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";
		if(true){
			if($acc['debit'] > $acc['credit']){
				$acc['debit'] = sprint($acc['debit']-$acc['credit']);
				$acc['credit'] = "0.00";
			}

			if($acc['credit'] > $acc['debit']){
				$acc['credit'] = sprint($acc['credit']-$acc['debit']);
				$acc['debit'] = "0.00";
			}

			if($acc['credit'] == $acc['debit']){
				$acc['credit'] = "0.00";
				$acc['debit'] = "0.00";
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
		$totdebit += $acc['debit'];
		$totcredit += $acc['credit'];
	}
	$totdebit = sprint($totdebit + $gdebit);
	$totcredit = sprint($totcredit + $gcredit);

/* -- Final Layout -- */
	$details = "Trial Balance
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=600>
	<tr><th width=40%></th><th width=30%>Debit</th><th width=30%>Credit</th></tr>
	$report
	<tr><td><b>Total</b></td><td align=right><b>$totdebit</b></td><td align=right><b>$totcredit</b></td></tr>
	<tr><td><br></td></tr>
	</table>";

	return $details;
}

class grp_report{
	var $selected = array();
	var $totdebit = 0;
	var $totcredit = 0;

	function grp_report(){
	}

	function getReport(){
		$balance = sprint(0);
		$products = "";
		# all connects to core
		db_conn("core");
		$sql = "SELECT * FROM trialgrps";
		$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
		while($grp = pg_fetch_array($grpRslt)){
			$grpbal['credit'] = 0;
			$grpbal['debit'] = 0;
			$products .="<tr><td>000/00$grp[gkey] - $grp[grpname]</td>";

			$sql = "SELECT * FROM trialgrpaccids WHERE gkey = '$grp[gkey]'";
			$gaccRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");
			while ($gacc = pg_fetch_array($gaccRslt)) {
				$sql = "SELECT * FROM trial_bal WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
				$balRslt = db_exec ($sql) or errDie ("Unable to view account.");
				$bal = pg_fetch_array($balRslt);
				$grpbal['credit'] += $bal['credit'];
				$grpbal['debit'] += $bal['debit'];
				$this->selected[] = $gacc['accid'];
			}
			if (true) {
				if($grpbal['debit'] > $grpbal['credit']){
					$grpbal['debit'] = sprint($grpbal['debit']-$grpbal['credit']);
					$grpbal['credit'] = "0.00";
				}

				if($grpbal['credit'] > $grpbal['debit']){
					$grpbal['credit'] = sprint($grpbal['credit']-$grpbal['debit']);
					$grpbal['debit'] = "0.00";
				}

				if($grpbal['credit'] == $grpbal['debit']){
					$grpbal['credit'] = "0.00";
					$grpbal['debit'] = "0.00";
				}
			}

			if (floatval($grpbal['debit']) == 0) {
				$products .="<td align=right> - </td>";
			} else {
				$products .="<td align=right>$grpbal[debit]</td>";
			}

			if (floatval($grpbal['credit']) == 0) {
				$products .="<td align=right> - </td>";
			} else {
				$products .="<td align=right>$grpbal[credit]</td>";
			}
			$products .="</tr>";
			$this->totdebit += $grpbal['debit'];
			$this->totcredit += $grpbal['credit'];
		}
		return $products;
	}
}
?>
