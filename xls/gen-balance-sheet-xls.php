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
			$OUTPUT = "error";
	}
} else {
	$OUTPUT = "error";
}

include("temp.xls.php");
Stream("BalanceSheet", $OUTPUT);

exit;

# get templete
require("../template.php");

# details
function sheet($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	if($summary == 'yes'){
		$oerep = new grp_report("oe", "Equity & Liabilities", "credit - debit");
		$asrep = new grp_report("as", "Assets", "debit - credit");
	}else{
		$oerep = new acc_report("oe", "Equity & Liabilities", "credit - debit");
		$asrep = new acc_report("as", "Assets", "debit - credit");
	}

/* -- Final Layout -- */
	$details = "Balance sheet
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' border=0 width=600>
	<tr><th width=15%></th><th width=25%></th><th width=40%></th><th width=20%></th></tr>
	".$oerep->getREport()."
	<tr><td colspan=4><br></td></tr>
	".$asrep->getREport()."
	<tr><td><br></td></tr>
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
		getReport();
		return $this->balance;
	}
	function getReport(){
		$typ = $this->typ;
		$name = $this->name;
		$tot = $this->tot;
		$balance = sprint(0);
		$nettincome = getNetIncome();

		# all connects to core
		db_conn("core");

		$products = "<tr><th colspan=5>$name</th></tr>";

		$sql = "SELECT * FROM balsubs WHERE typ = '$typ' ORDER BY skey ASC";
		$subRslt = db_exec ($sql) or errDie ("Unable to get sub-headings information.");
		while ($sub = pg_fetch_array($subRslt)) {
			$subbal = sprint(0);
			$products .="<tr><td colspan=2><b><u>$sub[subname]</u></b></td><td> <br> </td><td> <br> </td><tr>";

			$sql = "SELECT * FROM balgrps WHERE skey = '$sub[skey]' AND typ = '$typ'";
			$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
			while($grp = pg_fetch_array($grpRslt)){
				$grpbal = sprint(0);
				$products .="<tr><td> <br> </td><td colspan=2><b>$grp[grpname]<b></td>";

				$sql = "SELECT * FROM balgrpaccids WHERE skey = '$sub[skey]' AND gkey = '$grp[gkey]' AND typ = '$typ'";
				$gaccRslt = db_exec ($sql) or errDie ("Unable to get group accounts information.");
				while($gacc = pg_fetch_array($gaccRslt)){
					$sql = "SELECT ($tot) as bal FROM trial_bal WHERE accid = '$gacc[accid]' AND div = '".USER_DIV."'";
					$balRslt = db_exec ($sql) or errDie ("Unable to view account.");
					$bal = pg_fetch_array($balRslt);
					$bal['bal'] = sprint($bal['bal']);
					$grpbal = sprint($grpbal + $bal['bal']);
				}
				$products .="<td align=right>$grpbal</td></tr>";
				$subbal = sprint($subbal + $grpbal);
			}
			$balance = sprint($balance + $subbal);
			$products .="</tr>";
			$products .="<tr><td colspan=3><br></td><td align=right class=tot><b>$subbal</b></td></tr>";
		}
		if($typ == 'oe') $products .="<tr><td colspan=2><b><u>Nett Income</u></b></td><td></td><td align=right>$nettincome</td><tr>";
		
		$balance = sprint($balance + $nettincome);
		
		$products .="<tr><td colspan=4><br></td></tr>";
		$products .="<tr><td colspan=2 align=right class=tot><br></td><td class=tot><b><u>Total</u></b></td><td align=right class=tot><b>$balance</b></td></tr>";

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
		getReport();
		return $this->balance;
	}
	function getReport(){
		$typ = $this->typ;
		$name = $this->name;
		$tot = $this->tot;
		$balance = sprint(0);
		$nettincome = getNetIncome();

		# all connects to core
		db_conn("core");

		$products = "<tr><th colspan=5>$name</th></tr>";

		$sql = "SELECT * FROM balsubs WHERE typ = '$typ' ORDER BY skey ASC";
		$subRslt = db_exec ($sql) or errDie ("Unable to get sub-headings information.");

		while ($sub = pg_fetch_array($subRslt)) {
			$products .="<tr><td colspan=2><b><u>$sub[subname]</u></b></td><td> <br> </td><td> <br> </td><tr>";

			$sql = "SELECT * FROM balgrps WHERE skey = '$sub[skey]' AND typ = '$typ'";
			$grpRslt = db_exec ($sql) or errDie ("Unable to get groups information.");
			while($grp = pg_fetch_array($grpRslt)){
				$grpbal = 0;
				$products .="<tr><td colspan=2><b>$grp[grpname] :<b></td><td> <br> </td><td> <br> </td><tr>";

				$sql = "SELECT * FROM balgrpaccids WHERE skey = '$sub[skey]' AND gkey = '$grp[gkey]' AND typ = '$typ'";
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
					$products .="<tr><td> <br> </td>
					<td colspan=2>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td align=right>$bal[bal]</td></tr>";
				}
				$products .="<tr><td colspan=3></td><td align=right class=tot>$grpbal</td></tr>";
				$balance = sprint($balance + $grpbal);
			}
			$products .="</tr>";
		}
		if($typ == 'oe'){
			$products .="<tr><td colspan=4><br></td></tr>";
			$products .="<tr><td colspan=3><b><u>Nett Income</u></b></td><td align=right class=tot>$nettincome</td><tr>";
			$balance = sprint($balance + $nettincome);
		}
		$products .="<tr><td colspan=4><br></td></tr>";
		$products .="<tr><td colspan=2 align=right class=tot><br></td><td class=tot><b><u>Total</u></b></td><td align=right class=tot><b>$balance</b></td></tr>";

		$this->balance = $balance;

		return $products;
	}
}

// get total income
function getNetIncome()
{
	# get the income statement settings
	core_connect();
	$sql = "SELECT accid FROM accounts WHERE acctype='I' AND div = '".USER_DIV."'";
	$incRslt = db_exec($sql) or errDie("Unable to retrieve income statement settings from the Database",SELF);
	if(pg_numrows($incRslt) < 1){
			return "<center>There Are no Income in Cubit.";
	}

	# get income accounts Balances
	$tlinc = 0; // total income credit

	while($inc = pg_fetch_array($incRslt)){
		# get the balances (debit nad credit) from trial Balance
		$sql = "SELECT * FROM trial_bal WHERE accid = '$inc[accid]' AND div = '".USER_DIV."'";
		$balRslt = db_exec($sql) or errDie("Unable to retrieve Account Balance information from the Database.",SELF);
		$bal = pg_fetch_array($balRslt);

		$total = ($bal['credit'] - $bal['debit']);
		$tlinc += $total;
	}

	# get the income statement settings
	$sql = "SELECT accid FROM accounts WHERE acctype='E' AND div = '".USER_DIV."'";
	$expRslt = db_exec($sql) or errDie("Unable to retrieve income statement settings from the Database",SELF);
	if(pg_numrows($expRslt) < 1){
			return "<center>There Are no Expenditure accounts in Cubit.";
	}

	# get account Balances for Expenditure
	$tlexp = 0; // total expenditures

	while($exp = pg_fetch_array($expRslt)){
		#get vars from inc (accnum, type)
		foreach($exp as $key => $value){
				$$key = $value;
		}

		# get the balances (debit nad credit) from trial Balance
		$sql = "SELECT * FROM trial_bal WHERE accid = '$exp[accid]' AND div = '".USER_DIV."'";
		$balRslt = db_exec($sql) or errDie("Unable to retrieve Account Balance information from the Database.",SELF);
		$bal = pg_fetch_array($balRslt);

		# alternate bgcolor
		$total = ($bal['debit'] - $bal['credit']);
		$tlexp += $total;        // And increment the balance for expenditure
	}
	return sprint($tlinc - $tlexp);
}
?>
