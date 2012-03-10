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

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "print":
			$OUTPUT = sheet($HTTP_POST_VARS);
			break;

		default:
			$OUTPUT = "err";
	}
} else {
	$OUTPUT = "err";
}

include("temp.xls.php");
Stream("IncomeStatement", $OUTPUT);

exit;

# details
function sheet($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	if($summary == 'yes'){
		$increp = new grp_report("inc", "Income", "credit - debit");
		$cosrep = new grp_report("cos", "Cost Of Sales", "debit - credit");
		$exprep = new grp_report("exp", "Expenditure", "debit - credit");
	}else{
		$increp = new acc_report("inc", "Income", "credit - debit");
		$cosrep = new acc_report("cos", "Cost Of Sales", "debit - credit");
		$exprep = new acc_report("exp", "Expenditure", "debit - credit");
	}
	$incbal = sprint($increp->getBalance());
	$cosbal = sprint($cosrep->getBalance());
	$expbal = sprint($exprep->getBalance());

	$grosamt = sprint($incbal - $cosbal);
	$nettamt = sprint($grosamt - $expbal);

/* -- Final Layout -- */
	$details = "Income Statement
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=600>
	<tr><th width=30%></th><th width=50%></th><th width=20%></th></tr>
	".$increp->getReport()."
	<tr><td colspan=3><br></td></tr>
	".$cosrep->getReport()."
	<tr><td colspan=3><br></td></tr>
	<tr><td align=right class=tot><br></td><td class=tot><b><u>Gross Profit</u></b></td><td align=right class=tot><b>$grosamt</b></td></tr>
	<tr><td colspan=3><br></td></tr>
	".$exprep->getReport()."
	<tr><td><br></td></tr>
	<tr><td align=right class=tot><br></td><td class=tot><b><u>Nett Profit</u></b></td><td align=right class=tot><b>$nettamt</b></td></tr>
	</table>";

	return $details;
}

class grp_report{
	var $typ;
	var $name;
	var $tot;
	var $balance;
	function grp_report($a_typ, $a_name, $a_tot){
		$this->typ = $a_typ;
		$this->name = $a_name;
		$this->tot = $a_tot;
	}
	function getBalance(){
		$this->getReport();
		return $this->balance;
	}
	function getReport(){
		$typ = $this->typ;
		$name = $this->name;
		$tot = $this->tot;
		$balance = sprint(0);

		# all connects to core
		db_conn("core");

		$products = "<tr><th colspan=3>$name</th></tr>";

		$sql = "SELECT * FROM stmntgrps WHERE typ = '$typ'";
		$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
		while($grp = pg_fetch_array($grpRslt)){
			$grpbal = sprint(0);
			$products .="<tr><td colspan=2><b>$grp[grpname]<b></td>";

			$sql = "SELECT * FROM stmntgrpaccids WHERE gkey = '$grp[gkey]' AND typ = '$typ'";
			$gaccRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");
			while($gacc = pg_fetch_array($gaccRslt)){
				$sql = "SELECT ($tot) as bal FROM trial_bal WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
				$balRslt = db_exec ($sql) or errDie ("Unable to view account.");
				$bal = pg_fetch_array($balRslt);
				$bal['bal'] = sprint($bal['bal']);
				$grpbal = sprint($grpbal + $bal['bal']);
			}
			$products .="<td align=right>$grpbal</td></tr>";
			$balance = sprint($balance + $grpbal);
		}
		$products .="<tr><td colspan=3><br></td></tr>";
		$products .="<tr><td align=right><br></td><td class=tot><b>Total $name</b></td><td align=right class=tot><b>$balance</b></td></tr>";

		$this->balance = $balance;

		return $products;
	
	}
}

class acc_report{
	var $typ;
	var $name;
	var $tot;
	var $balance;
	function acc_report($a_typ, $a_name, $a_tot){
		$this->typ = $a_typ;
		$this->name = $a_name;
		$this->tot = $a_tot;
	}
	function getBalance(){
		$this->getReport();
		return $this->balance;
	}
	function getReport(){
		$typ = $this->typ;
		$name = $this->name;
		$tot = $this->tot;
		$balance = sprint(0);

		# all connects to core
		db_conn("core");

		$products = "<tr><th colspan=3>$name</th></tr>";

		$sql = "SELECT * FROM stmntgrps WHERE typ = '$typ'";
		$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
		while($grp = pg_fetch_array($grpRslt)){
			$grpbal = 0;
			$products .="<tr><td colspan=2><b><u>$grp[grpname]<u><b></td><td> <br> </td><tr>";

			$sql = "SELECT * FROM stmntgrpaccids WHERE gkey = '$grp[gkey]' AND typ = '$typ'";
			$gaccRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");
			while($gacc = pg_fetch_array($gaccRslt)){
				$sql = "SELECT * FROM accounts WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
				$accRslt = db_exec ($sql) or errDie ("Unable to view account.");
				$acc = pg_fetch_array($accRslt);

				$sql = "SELECT ($tot) as bal FROM trial_bal WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
				$balRslt = db_exec ($sql) or errDie ("Unable to view account.");
				$bal = pg_fetch_array($balRslt);
				$bal['bal'] = sprint($bal['bal']);
				$grpbal = sprint($grpbal + $bal['bal']);
				$products .="<tr>
				<td colspan=2><blockquote>$acc[topacc]/$acc[accnum] - $acc[accname]</blockquote></td><td align=right>$bal[bal]</td></tr>";
			}
			$products .="<tr><td colspan=2></td><td align=right class=tot>$grpbal</td></tr>";
			$balance = sprint($balance + $grpbal);
		}
		$products .="<tr><td colspan=3><br></td></tr>";
		$products .="<tr><td><br></td><td class=tot><b>Total $name</b></td><td align=right class=tot><b>$balance</b></td></tr>";

		$this->balance = $balance;

		return $products;
	}
}
?>
