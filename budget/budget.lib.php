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


# If this script is called by itself, abort
if (basename (getenv ("SCRIPT_NAME")) == "budget.lib.php") {
	exit;
}

# get array from all periods
$PERIODS = array();

global $PRDMON, $MONPRD;

$pmon = 0;
$fyear = getFinYear() - (int)($PRDMON[1] > 1);

for ($i = 1; $i <= 12; $i++) {
	$mon = $PRDMON[$i];

	if ($mon < $pmon) {
		++$fyear;
	}
	$pmon = $mon;

	$PERIODS[$mon] = getMonthName($mon)." $fyear";
}

# get array from all years
$YEARS = array();
db_conn("core");
$sql = "SELECT * FROM year";
$yrRslt = db_exec($sql);
$i = 0;
while($yr = pg_fetch_array($yrRslt)){
	if ($yr["yrdb"] == YR_DB) {
		define("BUDGET_YEARS_INDEX", $i);
	}
	$YEARS[$i++] = $yr['yrname'];
}

//removed cash" => "Cash Flow Projections"
$TYPES = array("bal"=>"Balance", "exp" => "Expense", "inc" => "Sales Projections");
$BUDFOR = array("cost" => "Cost Centers", "acc" => "Accounts");

function budgetTotalFromMonth($accid, $budfor) {
	db_conn("cubit");
	$sql = "SELECT SUM(bi.amt) FROM cubit.buditems bi, cubit.budgets bd
			WHERE bi.budid=bd.budid AND bi.id='$accid' AND bd.budfor='$budfor'
				AND prdtyp IS NULL";
	$rslt = db_exec($sql) or errDie("Error reading monthly budget total.");

	return pg_fetch_result($rslt, 0, 0);
}

function budgetTotalFromYear($accid, $budfor) {
	db_conn("cubit");
	$sql = "SELECT SUM(bi.amt) FROM cubit.buditems bi, cubit.budgets bd
			WHERE bi.budid=bd.budid AND bi.id='$accid' AND bd.budfor='$budfor'
				AND bd.prdtyp='yr' AND bi.prd='".BUDGET_YEARS_INDEX."'";
	$rslt = db_exec($sql) or errDie("Error reading monthly budget total.");

	return pg_fetch_result($rslt, 0, 0);
}

?>
