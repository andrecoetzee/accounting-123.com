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
# get templete
require("../template.php");

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

	$sql = "SELECT * FROM trial_bal WHERE period='".PRD_DB."' AND div = '".USER_DIV."' order by topacc,accnum ASC";
	$accRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");
	while($acc = pg_fetch_array($accRslt)){
		if(in_array($acc['accid'], $selected)) continue;
		$report .= "<tr bgcolor='".TMPL_tblDataColor1."'><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td>";
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
		$totdebit += $acc['debit'];
		$totcredit += $acc['credit'];
	}
	$totdebit = sprint($totdebit + $gdebit);
	$totcredit = sprint($totcredit + $gcredit);

/* -- Final Layout -- */
	$details = "<center>
	<h3>
	  ".COMP_NAME." Trial Balance as at<br>
	  ".date("Y-m-d")."
	</h3>
	<b>Author:</b> ".USER_NAME."
	<form action='".SELF."' method=post name=form1>
	<input type=hidden name=key value=update>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=600>
	<tr><th width=40%></th><th width=30%>Debit</th><th width=30%>Credit</th></tr>
	$report
	<tr bgcolor='".TMPL_tblDataColor2."'><td><b>Total</b></td><td align=right><b>$totdebit</b></td><td align=right><b>$totcredit</b></td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2></form><form action='../xls/gen-trial-balance-xls.php' method=post name=form><input type=hidden name=key value=print><input type=submit name=xls value='Export to spreadsheet'></form></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table></center>";

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
			$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td><a href=# onclick=\"javascript:openAccWin('accwin.php?gkey=$grp[gkey]')\">000/00$grp[gkey]</a> - $grp[grpname]</td>";

			$sql = "SELECT * FROM trialgrpaccids WHERE gkey = '$grp[gkey]'";
			$gaccRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");
			while($gacc = pg_fetch_array($gaccRslt)){
				$sql = "SELECT * FROM trial_bal WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
				$balRslt = db_exec ($sql) or errDie ("Unable to view account.");
				$bal = pg_fetch_array($balRslt);
				$grpbal['credit'] += $bal['credit'];
				$grpbal['debit'] += $bal['debit'];
				$this->selected[] = $gacc['accid'];
			}
			if(true){
				if($grpbal['debit'] > $grpbal['credit']){
					$grpbal['debit'] = sprint($grpbal['debit']-$grpbal['credit']);
					$grpbal['credit'] = 0;
				}

				if($grpbal['credit'] > $grpbal['debit']){
					$grpbal['credit'] = sprint($grpbal['credit']-$grpbal['debit']);
					$grpbal['debit'] = 0;
				}

				if($grpbal['credit'] == $grpbal['debit']){
					$grpbal['credit'] = 0;
					$grpbal['debit'] = 0;
				}
			}

			if(floatval($grpbal['debit']) == 0){
				$products .="<td align=right> - </td>";
			}else{
				$products .="<td align=right>$grpbal[debit]</td>";
			}

			if(floatval($grpbal['credit']) == 0){
				$products .="<td align=right> - </td>";
			}else{
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
