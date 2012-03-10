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

$OUTPUT = details($HTTP_GET_VARS);

# Get template
require("../template.php");

# Default view
function details($HTTP_GET_VARS)
{
	# Get vars
	foreach ($HTTP_GET_VARS as $key => $value) {
		$$key = $value;
	}

	$invtabs = array("invoices", "nons_invoices");
	$purtabs = array("purchases", "purch_int", "nons_purch_int", "nons_purchases");

	db_connect();
	$invdays = getinvmain($invtabs, "cubit", "invnum", "odate");
	$purdays = getpurmain($purtabs, PRD_DB, "purnum", "suppurch", "cubit", "purid", "pdate");
	$actual = getactual();

	$actot = sprint($actual['debit'] - $actual['credit']);
	$tot1 = sprint($actot + $invdays['1']['totamt'] - $purdays['1']['totamt']);
	$tot7 = sprint($tot1 + $invdays['7']['totamt'] - $purdays['7']['totamt']);
	$tot14 = sprint($tot7 + $invdays['14']['totamt'] - $purdays['14']['totamt']);
	$tot30 = sprint($tot14 + $invdays['30']['totamt'] - $purdays['30']['totamt']);
	$tot60 = sprint($tot30 + $invdays['60']['totamt'] - $purdays['60']['totamt']);
	$tot90 = sprint($tot60 + $invdays['90']['totamt'] - $purdays['90']['totamt']);
	$tot120 = sprint($tot90 + $invdays['120']['totamt'] - $purdays['120']['totamt']);

	$view = "<tr bgcolor='".TMPL_tblDataColor1."'><th>Incoming</th><td>$actual[debit]</td><td>".$invdays['1']['totamt']."</td><td>".$invdays['7']['totamt']."</td><td>".$invdays['14']['totamt']."</td><td>".$invdays['30']['totamt']."</td><td>".$invdays['60']['totamt']."</td><td>".$invdays['90']['totamt']."</td><td>".$invdays['120']['totamt']."</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><th>Outgoing</th><td>$actual[credit]</td><td>".$purdays['1']['totamt']."</td><td>".$purdays['7']['totamt']."</td><td>".$purdays['14']['totamt']."</td><td>".$purdays['30']['totamt']."</td><td>".$purdays['60']['totamt']."</td><td>".$purdays['90']['totamt']."</td><td>".$purdays['120']['totamt']."</td></tr>
	<tr><td><br></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><th>Running Total</th><td>$actot</td><td>$tot1</td><td>$tot7</td><td>$tot14</td><td>$tot30</td><td>$tot60</td><td>$tot90</td><td>$tot120</td></tr>";

	$details = "<center>
	<h3> Cash Flow Budget Report </h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' align=center width=590>
	<tr><td></td><th>Actual</th><th>Due</th><th>7 Days</th><th>14 days</th><th>30 days</th><th>60 days</th><th>90 days</th><th>120 days</th></tr>
	$view
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1' width=15%>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='budget-view.php'>View Budgets</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../main.php'>Main Menu</td></tr>
	</table>";

	return $details;
}

function getactual(){
	# get petty cash account
	$pcacc = gethook("accnum", "bankacc", "name", "Petty Cash");

	core_connect ();
	$pcbalRs = db_exec("SELECT sum(credit) as credit, sum(debit) as debit FROM trial_bal WHERE period='".PRD_DB."' AND accid = '$pcacc'");
	$pcbal = pg_fetch_array($pcbalRs);

	$bankct = $pcbal['credit'];
	$bankdt = $pcbal['debit'];

	# get bank balances
	db_connect ();
	$sql = "SELECT bankid FROM bankacct WHERE div = '".USER_DIV."'";
	$bankRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve bank account details from database.", SELF);
	while($bnk = pg_fetch_array($bankRslt)){
		$bacc = gethook("accnum", "bankacc", "accid", $bnk['bankid']);
		core_connect ();
		$bbalRs = db_exec("SELECT sum(credit) as credit, sum(debit) as debit FROM trial_bal WHERE period='".PRD_DB."' AND accid = '$bacc'");
		$bbal = pg_fetch_array($bbalRs);
		$bankct += $bbal['credit'];
		$bankdt += $bbal['debit'];;
	}

	# get cash on hand
	db_conn ("exten");
	$sql = "SELECT pca FROM departments  WHERE div = '".USER_DIV."'";
	$deptRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve departments from database.", SELF);
	while($dept = pg_fetch_array($deptRslt)){
		core_connect ();
		$deptbalRs = db_exec("SELECT sum(credit) as credit, sum(debit) as debit FROM trial_bal WHERE period='".PRD_DB."' AND accid = '$dept[pca]'");
		$deptbal = pg_fetch_array($deptbalRs);
		$bankct += $deptbal['credit'];
		$bankdt += $deptbal['debit'];;
	}

	$ret['debit'] = sprint($bankdt);
	$ret['credit'] = sprint($bankct);
	return $ret;
}

function gettot($array){
	$tot = 0;
	foreach($array as $key => $item){
		$tot += $item['amt'];
	}
	return sprint($tot);
}

function getinvdays($tabs, $db, $id, $date, $days){
	$ret = array();

	/*if($days > 29){
		$fdays = ($days - 30);
	}elseif($days < 2){
		$fdays = ($days - 365);
	}elseif($days == 14){
		$fdays = 7;
	}else{
		$fdays = 0;
	}*/
	if($days==1) {
		$fdays=-1;
	} elseif($days==7) {
		$fdays=0;
	} elseif($days==14) {
		$fdays=7;
	} elseif($days==30) {
		$fdays=14;
	} elseif($days==60) {
		$fdays=30;
	} elseif($days==90) {
		$fdays=60;
	} else {
		$fdays=90;
	}

	$tdate = date("Y-m-d");
	foreach($tabs as $key => $tab){
		db_conn($db);
		if($fdays==-1) {
			$sql = "SELECT balance,$id FROM $tab WHERE (terms - (date '$tdate' - $date)) < 1";
		} else {
			$sql = "SELECT balance,$id FROM $tab WHERE (terms - (date '$tdate' - $date)) > $fdays AND (terms - (date '$tdate' - $date)) <= $days";
		}
		$rs = db_exec($sql);
		if(pg_numrows($rs) > 0){
			while($inv = pg_fetch_array($rs)){
				$ret[] = array("tab" => $tab, $id => $inv[$id], "amt" => $inv['balance']);
			}
		}
	}
	# using objects
	$day["totamt"] =  gettot($ret);
	$day["items"] = $ret;
	return $day;
}

function getpurdays($tabs, $db, $id, $baltab, $baldb, $balid, $date, $days){
	$ret = array();

	if($days > 29){
		$fdays = ($days - 30);
	}elseif($days < 2){
		$fdays = ($days - 365);
	}elseif($days == 14){
		$fdays = 7;
	}else{
		$fdays = 0;
	}

	$tdate = date("Y-m-d");
	# search all 12 periods
	for($i = 1; $i <= 12; $i++){
		foreach($tabs as $key => $tab){
			db_conn($i);
			$sql = "SELECT balance,$id FROM $tab WHERE (terms - (date '$tdate' - $date)) > $fdays AND (terms - (date '$tdate' - $date)) <= $days";
			$rs = db_exec($sql);
			if(pg_numrows($rs) > 0){
				while($inv = pg_fetch_array($rs)){
					db_conn($baldb);
					$sql = "SELECT sum(balance) as balance FROM $baltab WHERE $balid = '$inv[$id]'";
					$brs = db_exec($sql);
					$bal = pg_fetch_array($brs);
					$ret[] = array("tab" => $tab, $id => $inv[$id], "amt" => $bal['balance']);
				}
			}
		}
	}

	db_conn('cubit');
	$sql = "SELECT balance,$id FROM purchases WHERE (terms - (date '$tdate' - $date)) > $fdays AND (terms - (date '$tdate' - $date)) <= $days";
	$rs = db_exec($sql);
	if(pg_numrows($rs) > 0){
		while($inv = pg_fetch_array($rs)){
			db_conn($baldb);
			$sql = "SELECT sum(balance) as balance FROM $baltab WHERE $balid = '$inv[$id]'";
			$brs = db_exec($sql);
			$bal = pg_fetch_array($brs);
			$ret[] = array("tab" => $tab, $id => $inv[$id], "amt" => $bal['balance']);
		}
	}


	# using objects
	$day["totamt"] =  gettot($ret);
	$day["items"] = $ret;
	return $day;
}


function getinvmain($tabs, $db, $id, $date){
	$ret['1'] = getinvdays($tabs, $db, $id, $date, 1);
	$ret['7'] = getinvdays($tabs, $db, $id, $date, 7);
	$ret['14'] = getinvdays($tabs, $db, $id, $date, 14);
	$ret['30'] = getinvdays($tabs, $db, $id, $date, 30);
	$ret['60'] = getinvdays($tabs, $db, $id, $date, 60);
	$ret['90'] = getinvdays($tabs, $db, $id, $date, 90);
	$ret['120'] = getinvdays($tabs, $db, $id, $date, 120);
	return $ret;
}

function getpurmain($tabs, $db, $id, $baltab, $baldb, $balid, $date){
	$ret['1'] = getpurdays($tabs, $db, $id, $baltab, $baldb, $balid, $date, 1);
	$ret['7'] = getpurdays($tabs, $db, $id, $baltab, $baldb, $balid, $date, 7);
	$ret['14'] = getpurdays($tabs, $db, $id, $baltab, $baldb, $balid, $date, 14);
	$ret['30'] = getpurdays($tabs, $db, $id, $baltab, $baldb, $balid, $date, 30);
	$ret['60'] = getpurdays($tabs, $db, $id, $baltab, $baldb, $balid, $date, 60);
	$ret['90'] = getpurdays($tabs, $db, $id, $baltab, $baldb, $balid, $date, 90);
	$ret['120'] = getpurdays($tabs, $db, $id, $baltab, $baldb, $balid, $date, 120);
	return $ret;
}
?>
