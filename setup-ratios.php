<?php

// Setup Turnover Ratio -------------------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
	VALUES ('turnover', 'Turnover')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$accid = qryAccountsName("Sales");
$accid = $accid["accid"];
$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
	VALUES ('$type_id', '$accid')";
db_exec($sql) or errDie("Unable to create ratio account.");

$accid = qryAccountsName("Point of Sale - Sales");
$accid = $accid["accid"];
$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
	VALUES ('$type_id', '$accid')";
db_exec($sql) or errDie("Unable to create ratio account.");

$accid = qryAccountsName("Sale of Assets");
$accid = $accid["accid"];
$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
	VALUES ('$type_id', '$accid')";
db_exec($sql) or errDie("Unable to create ratio account.");

// Setup Current Liabilities Ratio --------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
	VALUES ('current_liability', 'Current Liabilities')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$sql = "SELECT accid FROM core.accounts WHERE toptype='current_liability'";
$acc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

while (list($accid) = pg_fetch_array($acc_rslt)) {
	$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
			VALUES ('$type_id', '$accid')";
	db_exec($sql) or errDie("Unable to add ratio account");
}

// Setup Current Assets Ratio -------------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
        VALUES ('current_asset', 'Current Assets')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$sql = "SELECT accid FROM core.accounts WHERE toptype='current_asset'";
$acc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

while (list($accid) = pg_fetch_array($acc_rslt)) {
        $sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
        	VALUES ('$type_id', '$accid')";
        db_exec($sql) or errDie("Unable to add ratio account");
}

// Setup Fixed Assets Ratio ---------------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
        VALUES ('fixed_asset', 'Fixed Assets')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$sql = "SELECT accid FROM core.accounts WHERE toptype='fixed_asset'";
$acc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

while (list($accid) = pg_fetch_array($acc_rslt)) {
        $sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
        	VALUES ('$type_id', '$accid')";
        db_exec($sql) or errDie("Unable to add ratio account");
}

// Setup Cost of Sales Ratio --------------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
        VALUES ('cost_of_sales', 'Cost of Sales')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$sql = "SELECT accid FROM core.accounts WHERE toptype='cost_of_sales'";
$acc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

while (list($accid) = pg_fetch_array($acc_rslt)) {
        $sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
        	VALUES ('$type_id', '$accid')";
        db_exec($sql) or errDie("Unable to add ratio account");
}

// Setup Inventory Ratio ------------------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
        VALUES ('inventory', 'Inventory')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$accid = qryAccountsName("Inventory");
$accid = $accid["accid"];
$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
	VALUES ('$type_id', '$accid')";
db_exec($sql) or errDie("Unable to add ratio account");

// Setup Gross Depreciable Property Ratio -------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
        VALUES ('gross_depreciable_property', 'Gross Depreciable Property')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$accid = qryAccountsName("Land & Buildings - Net Value");
$accid = $accid["accid"];
$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
        VALUES ('$type_id', '$accid')";
db_exec($sql) or errDie("Unable to add ratio account");

// Setup Depreciation Expense -------------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
        VALUES ('depreciation_expense', 'Depreciation Expense')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$sql = "SELECT accid FROM core.accounts
		WHERE accname ILIKE '%Accum Depreciation%'";
$acc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

while (list($accid) = pg_fetch_array($acc_rslt)) {
	$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
			VALUES ('$type_id', '$accid')";
	db_exec($sql) or errDie("Unable to add ratio account.");
}

// Setup Credit Ratio ---------------------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
		VALUES ('credit', 'Credit')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$accid = qryAccountsName("POS Credit Card Control");
$accid = $accid["accid"];

$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
		VALUES ('$type_id', '$accid')";
db_exec($sql) or errDie("Unable to add ratio account.");

// Setup Repairs and Maintenance ----------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
		VALUES ('repairs_and_maintenance', 'Repairs and Maintenance')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$sql = "SELECT accid FROM core.accounts
		WHERE accname ILIKE '%- Cost%'";
$acc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts.");

while (list($accid) = pg_fetch_array($acc_rslt)) {
	$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
			VALUES ('$type_id', '$accid')";
	db_exec($sql) or errDie("Unable to add ratio account.");
}
// Setup Working Capital ------------------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
		VALUES ('working_capital', 'Working Capital')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$accid = qryAccountsName("Share Capital / Members Contribution");
$accid = $accid["accid"];

$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
		VALUES ('$type_id', '$accid')";
db_exec($sql) or errDie("Unable to add ratio account.");

// Setup Long Term Liabilities ------------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
		VALUES ('long_term_liabilities', 'Long Term Liabilities')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$accid = qryAccountsName("Shareholder / Director / Members Loan Account");
$accid = $accid["accid"];

$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
		VALUES ('$type_id', '$accid')";
db_exec($sql) or errDie("Unable to add ratio account.");

// Setup Owners Equity --------------------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
		VALUES ('owners_equity', 'Owners Equity')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$accid = qryAccountsName("Share Capital / Members Contribution");
$accid = $accid["accid"];

$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
		VALUES ('$type_id', '$accid')";
db_exec($sql) or errDie("Unable to add ratio account.");

// Setup Interest Expense -----------------------------------------------------
$sql = "INSERT INTO cubit.ratio_account_types (rtype, rname)
		VALUES ('interest_expense', 'Interest Expense')";
db_exec($sql) or errDie("Unable to create ratio type");
$type_id = pglib_lastid("cubit.ratio_account_types", "id");

$accid = qryAccountsName("Interest Paid");
$accid = $accid["accid"];

$sql = "INSERT INTO cubit.ratio_account_owners (type_id, accid)
		VALUES ('$type_id', '$accid')";
db_exec($sql) or errDie("Unable to add ratio account.");

?>