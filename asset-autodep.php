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
require ("settings.php");
require ("core-settings.php");

cFramework::run("select");
cFramework::parse();

function select(&$frm) {
	$frm->setkey("confirm");
	$frm->settitle("Automatic Depreciation");
	$frm->add_heading("Options");
	$frm->add_date("Date to Depreciate To", "date", DATE_YEAR, DATE_MONTH, DATE_DAY);
	
	return $frm->getfrm_input();
}

function confirm(&$frm) {
	if ($frm->validate("confirm")) {
		return select($frm);
	}
	
	$frm->setkey("write");
	return $frm->getfrm_input();
}

function write($frm) {
	extract($_REQUEST);
	
	if ($frm->validate("write")) {
		return confirm($frm);
	}
	
	pglib_transaction("BEGIN");
	
	db_conn('cubit');
	$user = USER_NAME;
	$Sql = "SELECT * FROM assets WHERE (dep_month='yes' AND remaction IS NULL)";
	$Rslt = db_exec($Sql) or errDie ("Unable to access database.");
	
	$cc = "";
	
	$todate = mkdate($date_year, $date_month, $date_day);
	$ttime = mktimefd($todate);

	$refnum = getrefnum($todate);

	while ($led = pg_fetch_array($Rslt)) {
		if (empty($led["autodepr_date"])) {
			$led["autodepr_date"] = $led["date"];
		}
		
		explodeDate($led["autodepr_date"], $date_year, $date_month, $date_day);
		
		$ftime = mktime(0, 0, 0, $date_month, $date_day, $date_year);
		
		$depmonths = 0;			
		while ($ftime < $ttime) {
			++$depmonths;
			$ftime = mktime(0, 0, 0, $date_month + $depmonths, $date_day, $date_year);	
		}
		
		if ($depmonths == 0) {
			continue;
		}
		
		$depperc = $led["dep_perc"];		
		$ml_perc = $depperc * (($depmonths % 12) / 12);
		$years = ($depmonths - ($depmonths % 12)) / 12;
		
		$baseamt = $led["amount"] - $led["accdep"];
		$depamt = 0;
		
		/* yearly depreciations */
		for ($i = 1; $i <= $years; ++$i) {
			$depamt += ($baseamt - $depamt) * ($depperc / 100);
		}
		
		/* monthly depreciation */
		$depamt += ($baseamt - $depamt) * ($ml_perc / 100);
		
		$sql = "SELECT * FROM assetgrp WHERE grpid = '$led[grpid]' AND div = '".USER_DIV."'";
		$grpRslt = db_exec($sql);
		$grp = pg_fetch_array($grpRslt);
		
		writetrans($grp['depacc'], $grp['accdacc'], $todate, $refnum, $depamt, "$led[des] Depreciation");
	
		db_connect();
		$sql = "UPDATE assets SET accdep = (accdep + '$depamt'), autodepr_date='$todate'
				WHERE (id='$led[id]' AND div = '".USER_DIV."')";
		db_exec($sql) or errdie("Could not update assets table.");
	
		$snetval = ($baseamt - $depamt);
		$sdate = date("Y-m-d");
		$sql = "INSERT INTO assetledger(assetid, asset, date, depamt, netval, div) 
				VALUES ('$led[id]', '$led[des]', '$todate', '$depamt', '$snetval', '".USER_DIV."')";
		db_exec($sql) or errdie("Could not write to asset ledger.");
		
		$cc .= "CostCenter('ct', 'Asset Depreciation', '$todate', '$led[des] Depreciation', '$depamt', '');";
	}
	
	pglib_transaction("COMMIT");

	$write = "
	<script> 
	$cc
	</script>
	<table ".TMPL_tblDflts." width='50%'>
		<tr>
			<th>Auto Asset Depreciation</th>
		</tr>
		<tr class='datacell'>
			<td>Asset Depreciation has calculated and recorded.</td>
		</tr>
	</table>";
	
	return $write;
}
?>
