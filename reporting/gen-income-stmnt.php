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

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "print":
			$OUTPUT = sheet($_POST);
			break;

		default:
			$OUTPUT = slct();
	}
} else {
	$OUTPUT = slct();
}

# get templete
require("../template.php");

function slct(){

	$view = "<center><h3>Income Statement</h3>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=print>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=200>
		<tr><th colspan=2>Report Type</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><input type=radio name=summary value=yes>Summarized</td>
		<td><input type=radio name=summary value=no checked=yes>Detailed</td></tr>
		<tr><td><br></td></tr>
		<tr><td><input type=button value='Cancel' onClick='javascript:history.back();'></td><td align=right><input type=submit value='Print'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $view;
}

# details
function sheet($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
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
	$details = "<center>
	<h3>
	  ".COMP_NAME." Income Statement as at<br>
	  ".date("Y-m-d")."
	</h3>
	<b>Author:</b> ".USER_NAME."
	<form action='".SELF."' method=post name=form1>
	<input type=hidden name=key value=update>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=600>
	<tr><th width=30%></th><th width=50%></th><th width=20%></th></tr>
	".$increp->getReport()."
	<tr><td colspan=3><br></td></tr>
	".$cosrep->getReport()."
	<tr><td colspan=3><br></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td align=right class=tot><br></td><td class=tot><b><u>Gross Profit</u></b></td><td align=right class=tot><b>$grosamt</b></td></tr>
	<tr><td colspan=3><br></td></tr>
	".$exprep->getReport()."
	<tr><td><br></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td align=right class=tot><br></td><td class=tot><b><u>Nett Profit</u></b></td><td align=right class=tot><b>$nettamt</b></td></tr>
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

		$products = "<tr bgcolor='".TMPL_tblDataColor1."'><th colspan=3>$name</th></tr>";

		$sql = "SELECT * FROM stmntgrps WHERE typ = '$typ'";
		$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
		while($grp = pg_fetch_array($grpRslt)){
			$grpbal = sprint(0);
			$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2><b>$grp[grpname]<b></td>";

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
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=3><br></td></tr>";
		$products .="<tr bgcolor='".TMPL_tblDataColor2."'><td align=right><br></td><td class=tot><b>Total $name</b></td><td align=right class=tot><b>$balance</b></td></tr>";

		$this->balance = $balance;

		return $products."<tr><td colspan=2></form><form action='../xls/gen-income-stmnt-xls.php' method=post name=form><input type=hidden name=key value=print><input type=hidden name=summary value='yes'><input type=submit name=xls value='Export to spreadsheet'></form></td></tr>";

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

		$products = "<tr bgcolor='".TMPL_tblDataColor1."'><th colspan=3>$name</th></tr>";

		$sql = "SELECT * FROM stmntgrps WHERE typ = '$typ'";
		$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
		while($grp = pg_fetch_array($grpRslt)){
			$grpbal = 0;
			$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2><b><u>$grp[grpname]<u><b></td><td> <br> </td><tr>";

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
				$products .="<tr bgcolor='".TMPL_tblDataColor1."'>
				<td colspan=2><blockquote>$acc[topacc]/$acc[accnum] - $acc[accname]</blockquote></td><td align=right>$bal[bal]</td></tr>";
			}
			$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2></td><td align=right class=tot>$grpbal</td></tr>";
			$balance = sprint($balance + $grpbal);
		}
		$products .="<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=3><br></td></tr>";
		$products .="<tr bgcolor='".TMPL_tblDataColor2."'><td><br></td><td class=tot><b>Total $name</b></td><td align=right class=tot><b>$balance</b></td></tr>";

		$this->balance = $balance;

		return $products."<tr><td colspan=2></form><form action='../xls/gen-income-stmnt-xls.php' method=post name=form><input type=hidden name=key value=print><input type=hidden name=summary value='no'><input type=submit name=xls value='Export to spreadsheet'></form></td></tr>";
	}
}
?>
