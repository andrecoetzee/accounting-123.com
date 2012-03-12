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

require ("../settings.php");

// Decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		default:
		case "printSupp":
			$OUTPUT = print_supp($_POST);
			break;
	}
} else {
	$OUTPUT = print_supp();
}

# show stock
function print_supp ($errors="")
{
	global $_POST;
	extract ($_POST);

	if (!isset($fdate_day)) $fdate_day = "01";
	if (!isset($fdate_month)) $fdate_month = date("m");
	if (!isset($fdate_year)) $fdate_year = date("Y");
	if (!isset($tdate_day)) $tdate_day = date("d");
	if (!isset($tdate_month)) $tdate_month = date("m");
	if (!isset($tdate_year)) $tdate_year = date("Y");

	$fdate = "$fdate_year-$fdate_month-$fdate_day";
	$tdate = "$tdate_year-$tdate_month-$tdate_day";
	
	# Set up table to display in
	$printSupp = "<h3>Current Supplier Transactions</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=500>";

	# connect to database
	db_connect();

	# Query server
	$i = 0;
    $sql = "SELECT * FROM suppliers WHERE div = '".USER_DIV."' ORDER BY supid ASC";
    $suppRslt = db_exec ($sql) or errDie ("Unable to retrieve Suppliers from database.");
	if (pg_numrows ($suppRslt) < 1) {
		return "<li>There are no Suppliers in Cubit.<p>";
	}

	while ($supp = pg_fetch_array ($suppRslt)) {
		# get department
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$supp[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql);
		if(pg_numrows($deptRslt) < 1){
			$deptname = "<li class=err>".ct("Department not Found.")."";
		}else{
			$dept = pg_fetch_array($deptRslt);
			$deptname = $dept['deptname'];
		}

		if($supp['location'] == 'int'){
			$cur = $supp['currency'];
			$bal = "fbalance";
		}else{
			$cur = CUR;
			$bal = "balance";
		}

		$printSupp .= "<tr><td colspan=10>$supp[supno] - $supp[supname]  <b>$cur $supp[$bal]</b></td></tr>";

		# connect to database
		db_connect ();
		$stmnt = "";
		$totout = 0;

		# Query server
		$sql = "SELECT * FROM sup_stmnt WHERE supid = '$supp[supid]' AND edate >= '$fdate' AND edate <= '$tdate' AND div = '".USER_DIV."' ORDER BY edate ASC";
		$stRslt = db_exec ($sql) or errDie ("Unable to retrieve invoices statement from database.");
		if (pg_numrows ($stRslt) < 1) {
			$stmnt .= "<tr><td colspan=10>".ct("No transactions found for the current date range.")."</td></tr>";
		}else{
			while ($st = pg_fetch_array ($stRslt)) {
				# Accounts details
				if($st['cacc'] > 0){
					$accRs = get("core","*","accounts","accid",$st['cacc']);
					if(pg_numrows($accRs) < 1){
						$acc['accname'] = "No Account.";
						$acc['topacc'] = "000";
						$acc['accnum'] = "000";
					}else{
						$acc  = pg_fetch_array($accRs);
					}
				}else{
					$acc['accname'] = "No Account.";
					$acc['topacc'] = "000";
					$acc['accnum'] = "000";
				}
				# format date
				$st['edate'] = explode("-", $st['edate']);
				$st['edate'] = $st['edate'][2]."-".$st['edate'][1]."-".$st['edate'][0];

				$st['amount'] = sprint($st['amount']);
				$stmnt .= "<tr><td align=center>$st[edate]</td><td>$st[ref]</td><td>$acc[topacc]/$acc[accnum] - $acc[accname]</td><td>$st[descript]</td><td align=right>$cur $st[amount]</td></tr>";

				# keep track of da totals
				$totout += $st['amount'];
			}
		}
		$printSupp .= $stmnt."<tr><td><br><br></td></tr>";
	}

	$printSupp .= "</table>";
	
	$OUTPUT = $printSupp;

	require ("temp.xls.php");
	Stream("Suppliers Transaction Report", $OUTPUT);
}
?>
