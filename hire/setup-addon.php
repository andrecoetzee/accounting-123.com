<?

if (!defined("SETUP_PHP")) {
	exit;
}

newacc("1050", "000", "Hire Sales", "I", "f", "sales");
newacc("6180", "000", "Assets for Hire - Net Value", "B", "f", "fixed_asset");
newacc("6180", "010", "Assets for Hire - Cost", "B", "f", "fixed_asset");
newacc("6180", "020", "Assets for Hire - Accum Depreciation", "B", "f", "fixed_asset");
newacc("2180", "010", "Repairs and Maintenance - Equipment", "E", "f", "cost_of_sales");
newacc("2810", "000", "Royalties", "E", "f", "");

$add_to_ledger = array(
					"Hire Sales",
					"Assets for Hire - Net Value",
					"Assets for Hire - Cost",
					"Assets for Hire - Accum Depreciation",
					"Repairs and Maintenance - Equipment",
					"Royalties"
				);
				

foreach ($add_to_ledger as $value) {
	for ($i = 1; $i <= 14; $i++) {
		$sql = "SELECT accid, topacc, accnum FROM core.accounts
				WHERE accname='$value'";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account.");
		list($accid, $topacc, $accnum) = pg_fetch_array($acc_rslt);
	
		$date = date("Y-m-d");
	
		$sql = "INSERT INTO \"$i\".ledger (acc, contra, edate, eref, descript,
					credit, debit, div, caccname, ctopacc, caccnum, cbalance,
					dbalance, refnum, sdate)
				VALUES ('$accid', '$accid', '$date', '0', 'Balance', 0, 0,
					'".USER_DIV."', '$value', '$topacc', '$accnum', 0.00, 0.00,
					0, '$date')";
		db_exec($sql) or errDie("Unable to create ledger entry.");
	}
}

$sql = "INSERT INTO cubit.seq (type, last_value, div) VALUES ('hire', '0', '".USER_DIV."')";
db_exec($sql) or errDie("Unable to add hire sequence.");

// Retrieve the accounts
$sql = "SELECT accid FROM core.accounts WHERE topacc='6180' AND accnum='000'";
$net_rslt = db_exec($sql) or errDie("Unable to retrieve cost account.");
$net_acc = pg_fetch_result($net_rslt, 0);

$sql = "SELECT accid FROM core.accounts WHERE topacc='6180' AND accnum='010'";
$cos_rslt = db_exec($sql) or errDie("Unable to retrieve cost account.");
$cos_acc = pg_fetch_result($cos_rslt, 0);

$sql = "SELECT accid FROM core.accounts WHERE topacc='6180' AND accnum='020'";
$adep_rslt = db_exec($sql) or errDie("Unable to retrieve cost account.");
$adep_acc = pg_fetch_result($adep_rslt, 0);

$sql = "INSERT INTO  exten.categories(category, div) VALUES ('Contract', '".USER_DIV."')";
$catRslt = db_exec ($sql) or errDie ("Unable to add category to system.", SELF);

$sql = "INSERT INTO  exten.categories(category, div) VALUES ('Casual', '".USER_DIV."')";
$catRslt = db_exec ($sql) or errDie ("Unable to add category to system.", SELF);

$sql = "SELECT accid FROM core.accounts WHERE topacc='2200' AND accnum='000'";
$dep_rslt = db_exec($sql) or errDie("Unable to retrieve depreciation account.");
$dep_acc = pg_fetch_result($dep_rslt, 0);

$sql = "INSERT INTO cubit.assetgrp (grpname, costacc, accdacc, depacc, div)
			VALUES ('Temporary - Equipment', '$cos_acc', '$adep_acc', '$dep_acc', '".USER_DIV."')";
$grpRslt = db_exec($sql) or errDie("Unable to add asset group to system.");

$sql = "INSERT INTO cubit.assetgrp (grpname, costacc, accdacc, depacc, div)
			VALUES ('Temporary - Plant', '$cos_acc', '$adep_acc', '$dep_acc', '".USER_DIV."')";
$grpRslt = db_exec($sql) or errDie("Unable to add asset group to system.");

$sql = "INSERT INTO cubit.assetgrp (grpname, costacc, accdacc, depacc, div)
			VALUES ('Equipment', '$cos_acc', '$adep_acc', '$dep_acc', '".USER_DIV."')";
$grpRslt = db_exec($sql) or errDie("Unable to add asset group to system.");

$sql = "INSERT INTO cubit.assetgrp (grpname, costacc, accdacc, depacc, div)
			VALUES ('Plant', '$cos_acc', '$adep_acc', '$dep_acc', '".USER_DIV."')";
$grpRslt = db_exec($sql) or errDie("Unable to add asset group to system.");

?>
