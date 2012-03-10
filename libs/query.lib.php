<?
/**
 * Quick db queries. Using without or with "false" id values will return dbSelect
 * object for all rows in db
 *
 * @package Cubit
 * @subpackage Queries
 */
if (!defined("QUERY_LIB")) {
	define("QUERY_LIB", true);

/**
 * returns the last invid value from core.invoicesids_seq;
 */
function lastinvid() {
	$qry = new dbQuery(DB_SQL, "SELECT last_value FROM core.invoicesids_seq");
	$qry->run();

	if ($qry->num_rows() < 1) {
		return 0;
	}

	return $qry->fetch_result();
}

/**
 * returns the last purid value from core.purchasesids_seq;
 */
function lastpurid() {
	$qry = new dbQuery(DB_SQL, "SELECT last_value FROM core.purchasesids_seq");
	$qry->run();

	if ($qry->num_rows() < 1) {
		return 0;
	}

	return $qry->fetch_result();
}

/**
 * returns general email settings, and if done does an r2sListSet and goes to email settings page
 * 
 * @return array
 */
function qryEmailSettings() {
	$qry = new dbSelect("esettings", "cubit");
	$qry->run();

	$invalid = false;
	if ($qry->num_rows() <= 0) {
		$invalid = true;
	} else {
		$d = $qry->fetch_array();
		$qry->free();
		
		if ($d["smtp_host"] == "" || $d["fromname"] == "") {
			$invalid = true;
		}
	}
	
	if ($invalid) {
		r2sListSet("emailsettings");
		header("Location: email-settings.php");
		exit;
	}
	
	return $d;
}

/**
 * wrapper function to easily create functions to execute the below queries
 *
 * (1) if $hvdiv is true, it means the table has a div column, so the where condition
 * will automatically match the div column to the constant USER_DIV.
 *
 * (2) to order the query specify columns in the $order parameter. if this parameter
 * is false or left out, the query will be ordered with the $idseq column.
 *
 * @param string $table
 * @param string $schema
 * @param string $cols
 * @param string/array $idseq sequence column name
 * @param int/array $idval sequence column value
 * @param string $order the order expression columns seperated by commas
 * @param bool $hvdiv the table has a div column (dflt true)
 * @return array/object
 */
function qryWrapper($table, $schema, $cols, $idseq, $idval, $order = false, $hvdiv = true) {
	/* if idseq and idval isn't arrays, make them so we can build match later */
	if (!is_array($idseq)) {
		$idseq = array(0 => $idseq);
		$idval = array(0 => $idval);
	}

	/* didn't specify an order, use the first idseq value */
	if ($order === false) {
		$order = $idseq[0];
	}

	/* build where expression */
	$all_idval = false;
	$idval_matches = false;
	foreach ($idseq as $k => $idseq_v) {
		$idval_matches = wgrp(
			$idval_matches,
			$idval[$k] == false ? false : m($idseq_v, $idval[$k])
		);

		$all_idval |= $idval[$k] != false;
	}

	/* do the select */
	$qry = new dbSelect($table, $schema, grp(
		m("cols", $cols),
		m("where", wgrp(
			// where expression
			$idval_matches,
			// div='".USER_DIV."'
			$hvdiv === false ? false : m("div", USER_DIV)
		)),
		m("order", $order)
	));
	$qry->run();

	/* requested all rows, return dbSelect object */
	if ($all_idval == false) {
		return $qry;
	/* requested single row, return it as array */
	} else {
		return $qry->fetch_array();
	}
}

/**
 * returns account row out of db (by accid)
 *
 * @param int $accid account id
 * @param string cols columns to return
 * @return array/object
 */
function qryAccounts($accid = false, $cols = "*") {
	return qryWrapper("accounts", "core", $cols, "accid", $accid, "accname");
}

/**
 * returns account row out of db (by name)
 *
 * @param int $accname account name
 * @param string cols columns to return
 * @return array/object
 */
function qryAccountsName($accname = false, $cols = "*") {
	return qryWrapper("accounts", "core", $cols, "accname", $accname, "accname");
}

/**
 * returns account row out of db (by topacc and accnum)
 *
 * @param int $topacc account number (part 1, 4 digit)
 * @param int $accnum account number (part 2, 3 digit)
 * @param string cols columns to return
 * @return array/object
 */
function qryAccountsNum($topacc = false, $accnum = false, $cols = "*") {
	return qryWrapper("accounts", "core", $cols, array("topacc", "accnum"),
		array($topacc, $accnum), "accname");
}

/**
 * returns vatcode row out of db
 *
 * @param int $id vatcode id
 * @param string cols columns to return
 * @return array/object
 */
function qryVatcode($id = false, $cols = "*") {
	return qryWrapper("vatcodes", "cubit", $cols, "id", $id, "code", false);
}

/**
 * returns vatcode row out of db (by code)
 *
 * @param int $code vat code
 * @param string cols columns to return
 * @return array/object
 */
function qryVatcodeC($code  = false, $cols = "*") {
	return qryWrapper("vatcodes", "cubit", $cols, "code", $code, "code", false);
}

/**
 * returns warehouse row out of db
 *
 * @param int $whid warehouse id
 * @param string $cols columns to return
 * @return array/object
 */
function qryWarehouse($whid = false, $cols = "*") {
	return qryWrapper("warehouses", "exten", $cols, "whid", $whid, "whname");
}

/**
 * returns stock row out of db
 *
 * @param int $stkid stock item id
 * @param string $cols columns to return
 * @return array/object
 */
function qryStock($stkid = false, $cols = "*") {
	return qryWrapper("stock", "cubit", $cols, "stkid", $stkid, "stkcod");
}

/**
 * returns stock row out of db by stkcod
 *
 * @param int $stkcod stock item code
 * @param string $cols columns to return
 * @return array/object
 */
function qryStockC($stkcod = false, $cols = "*") {
	return qryWrapper("stock", "cubit", $cols, "stkcod", $stkcod, "stkcod");
}

/**
 * returns stock category row out of db
 *
 * @param int $stkid stock item id
 * @param string $cols columns to return
 * @return array/object
 */
function qryStockCat($catid = false, $cols = "*") {
	return qryWrapper("stockcat", "cubit", $cols, "catid", $catid, "cat");
}

/**
 * returns stock class row out of db
 *
 * @param int $stkid stock item id
 * @param string $cols columns to return
 * @return array/object
 */
function qryStockClass($clasid = false, $cols = "*") {
	return qryWrapper("stockclass", "cubit", $cols, "clasid", $clasid, "classname");
}

/**
 * returns supplier row out of db
 *
 * @param int $supid supplier id
 * @param string $cols columns to return
 * @return array/object
 */
function qrySupplier($supid = false, $cols = "*") {
	return qryWrapper("suppliers", "cubit", $cols, "supid", $supid, "supname");
}

/**
 * returns customer row out of db
 *
 * @param int $cusnum customer id
 * @param string $cols columns to return
 * @return array/object
 */
function qryCustomer($cusnum = false, $cols = "*") {
	return qryWrapper("customers", "cubit", $cols, "cusnum", $cusnum, "surname");
}

/**
 * returns category row out of db
 *
 * @param int $catid category id
 * @param string $cols columns to return
 * @return array/object
 */
function qryCategory($catid = false, $cols = "*") {
	return qryWrapper("categories", "exten", $cols, "catid", $catid, "category");
}

/**
 * returns class row out of db
 *
 * @param int $clasid class id
 * @param string $cols columns to return
 * @return array/object
 */
function qryClass($clasid = false, $cols = "*") {
	return qryWrapper("class", "exten", $cols, "clasid", $clasid, "classname");
}

/**
 * returns pricelist row out of db
 *
 * @param int $listid pricelist id
 * @param string $cols columns to return
 * @return array/object
 */
function qryPricelist($listid = false, $cols = "*") {
	return qryWrapper("pricelist", "exten", $cols, "listid", $listid, "listname");
}

/**
 * returns departments row out of db
 *
 * @param int $deptid department id
 * @param string $cols columns to return
 * @return array/object
 */
function qryDepartment($deptid = false, $cols = "*") {
	return qryWrapper("departments", "exten", $cols, "deptid", $deptid, "deptname");
}

/**
 * returns bank account row out of db
 *
 * @param int $bankid bank account id
 * @param string $cols columns to return
 * @return array/object
 */
function qryBankAcct($bankid = false, $cols = "*") {
	return qryWrapper("bankacct", "cubit", $cols, "bankid", $bankid, "bankname, branchname, accname");
}

/**
 * returns bank account number row out of db
 *
 * @param int $bankid bank account id
 * @param string $cols columns to return
 * @return array/object
 */
function qryBankAccnum($bankid = false, $cols = "*") {
	return qryWrapper("bankacc", "core", $cols, "bankid", $bankid, "name");
}

/**
 * returns currency row out of db
 *
 * @param int $fcid currency id
 * @param string $cols columns to return
 * @return array/object
 */
function qryCurrency($fcid = false, $cols = "*") {
	return qryWrapper("currency", "cubit", $cols, "fcid", $fcid, "curcode, symbol",
		false);
}

/**
 * returns sales person row out of db
 *
 * @param int $salespid sales person id
 * @param string $cols columns to return
 * @return array/object
 */
function qrySalesPerson($salespid = false, $cols = "*") {
		return qryWrapper("salespeople", "exten", $cols, "salespid", $salespid, "salesp");
}

/**
 * returns sales person row out of db by name
 *
 * @param int $salesp sales person name
 * @param string $cols columns to return
 * @return array/object
 */
function qrySalesPersonN($salesp = false, $cols = "*") {
	return qryWrapper("salespeople", "exten", $cols, "salesp", $salesp, "salesp");
}

/**
 * returns employee row out of db
 *
 * @param int $empnum employee id
 * @param string $cols columns to return
 * @return array/object
 */
function qryEmployee($empnum = false, $cols = "*") {
	return qryWrapper("employees", "cubit", $cols, "empnum", $empnum, "sname, fnames");
}

/**
 * returns previous employee row out of db
 *
 * @param int $empnum employee id
 * @param string $cols columns to return
 * @return array/object
 */
function qryLEmployee($empnum = false, $cols = "*") {
	return qryWrapper("lemployees", "cubit", $cols, "empnum", $empnum, "sname, fnames");
}

/**
 * returns users row out of db
 *
 * @param int $userid user id
 * @param string $cols columns to return
 * @return array/object
 */
function qryUsers($userid = false, $cols = "*") {
	return qryWrapper("users", "cubit", $cols, "userid", $userid, "username");
}

/**
 * returns branches row out of db
 *
 * @param int $div branche code
 * @param string $cols columns to return
 * @return array/object
 */
function qryBranch($div = false, $cols = "*") {
	return qryWrapper("branches", "cubit", $cols, "div", $div, "branname", false);
}

/**
 * returns timezone row out of db
 *
 * @param int $tz timezone name
 * @param string $cols columns to return
 * @return array/object
 */
function qryTimezone($timezone = false, $cols = "*") {
	return qryWrapper("timezones", "exten", $cols, "timezone", $timezone, "timezone", false);
}

/**
 * returns company details row out of db (excludes images for memory)
 *
 * @return array
 */
function qryCompInfo($cols = "*") {
	// columns we dont want
	$ncols = array(
		"logoimg",
		"imgtype",
		"img",
		"img2",
		"imgtype2",
		"logoimg2"
	);

	/* fetch company information */
	$q = qryWrapper("compinfo", "cubit", $cols, false, false, false, false);
	$a = $q->fetch_array();
	$q->free();

	/* no company info entered yet, return */
	if ($a === false) {
		return $a;
	}

	/* remove unwanted columns */
	foreach ($ncols as $n) {
		if (isset($a[$n])) {
			unset($a[$n]);
		}
	}

	return $a;
}

/**
 * quick way to fetch a column from array returned by above functions
 *
 * @param array $a array
 * @param string $col key
 * @return string
 */
function qryCol($a, $col) {
	return $a[$col];
}

} /* LIB END */
?>
