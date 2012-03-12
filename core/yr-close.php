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

# yr-close.php :: close current Year
##

# get settings
require("settings.php");
require("core-settings.php");
require("../reporting/finstatements.php");

define("PRD_STATE_NOWARN", true);

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = main();
	}
} else {
    # Display default output
    $OUTPUT = main();
}

# get templete
require("template.php");




function main()
{

	if (PRD_STATE == 'py') {
		return "<center><li class='err'>Please update your transaction year before closing financial year first: Click <a href='../set-period-use.php'>here</a> to do so.</li></center>";
	}

	db_conn("core");

	# check if not already closed
	$sql = "SELECT * FROM year WHERE yrdb = '".YR_DB."' AND closed = 'y'";
	$rslt = db_exec($sql) or errDie("Could not select Year Database and Name",SELF);
	if(pg_numrows($rslt) > 0){
		return "<center><li class='err'>ERROR : The Current Financial year has already been closed.</li>";
	}

	$sql = "SELECT * FROM active";
	$Rslt = db_exec ($sql) or errDie ("ERROR: Uable to get active period details from database.", SELF);
	$numrows = pg_numrows ($Rslt);
	if ($numrows < 1) {
		$OUTPUT = "<li>There are no Active periods/years defined in Cubit.</li>";
		require ("template.php");
	}
	$act = pg_fetch_array ($Rslt);

	db_conn('audit');

	$Sl = "SELECT * FROM closedprd";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)<12) {
		return "<li class='err'>You need to close all the periods(12) before you can close the year.</li>"
		.mkQuickLinks();
	}

	global $PRDMON, $MONPRD;
	$pmon = 0;
	$fyear = getFinYear() - (int)($PRDMON[1] > 1);

	$prddesc = array();
	for ($i = 1; $i <= 12; $i++) {
		$mon = $PRDMON[$i];

		if ($mon < $pmon) {
			++$fyear;
		}
		$pmon = $mon;

		if ($i == 1 || $i == 12) {
			$prddesc[] = getMonthName($mon)." $fyear";
		}
	}

	$prddesc = implode(" to ", $prddesc);

	$act["yrname"] = substr($act["yrname"], 1);

	$main = "
		<br><br>
		<center>
		<h3>Close Current Year</h3>
		<table ".TMPL_tblDflts.">
			<tr bgcolor='".bgcolorg()."'>
				<th>Current Financial Year:</th>
				<td>$act[yrname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<th>Next Financial Year:</th>
				<td>".($act["yrname"] + 1)."</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<th>Period Range:</th>
				<td>$prddesc</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<th>Today's Date:</th>
				<td><b>".date("D d M Y")."</b></td>
			</tr>
		</table><br />
		<form action='".SELF."' method='POST' name='form'>
			<li class='err'>
				Are you sure you want to close current Year on this date?<br />
				<br />
				<input type='button' value='No, Go Back' onClick='document.location.href=\"../main.php\"'>
				<input type='submit' value='Yes'><br />
				&nbsp;
			</li>
			<input type='hidden' name='key' value='write'>
		</form>"
		.mkQuickLinks();
	return $main;

}



function write()
{

	if (PRD_STATE == 'py') {
		return "<center><li class='err'>Please update your transaction year before closing financial year: Click <a href='../set-period-use.php'>here</a> to do so.</li></center>";
	}
	
	// Check if last database has not been reached
	if(YR_DB == "yr10"){
			$OUTPUT = "<center>Warning : The System has reached its Last year of operation, Please contact the support team to reset.";
			require("template.php");
	}

	$i = substr(YR_DB, 2) + 1;
	$nextyr = "yr".$i;

	$sdate = DATE_STD;

	pglib_transaction("BEGIN");

	global $PRDMON;

	$stmnt = financialStatements::trialbal(array(
			"heading_3" => "Trial Balance: Year End",
			"month_to" => $PRDMON[12]
		), true);
	save_statement($stmnt, "trial_bal");

	$stmnt = financialStatements::incomestmnt(array(
			"customized" => true,
			"this_year_year_to_date" => true,
			"heading_3" => "Income Statement: Year End",
			"budget" => true,
			"this_year_budget" => true,
			"month_to" => $PRDMON[12]
		), true);
	save_statement($stmnt, "income_stmnt");

	$stmnt = financialStatements::balsheet(array(
			"customized" => true,
			"this_year_year_to_date" => true,
			"heading_3" => "Balance Sheet: Year End",
			"month_to" => $PRDMON[12]
		), true);
	save_statement($stmnt, "bal_sheet");

	# Copy balance sheet table
	core_connect();

	$sql = "SELECT * FROM bal_sheet";
	$balSheet = db_exec($sql) or errDie("Could not copy Balances to year DB",SELF);

	while ($bal = pg_fetch_array($balSheet)) {
		db_conn(YR_DB);
		$sql = "INSERT INTO bal_sheet (type, ref, value, div) VALUES ('$bal[type]', '$bal[ref]', '$bal[value]', '$bal[div]')";
		$inRslt = db_exec($sql) or print($sql);
	}

	core_connect();

	$sql = "SELECT * FROM core.trial_bal WHERE period='12'";
	$trialBal = db_exec($sql) or errDie("Could not copy Balances to year DB",SELF);

	while($bal = pg_fetch_array($trialBal)){
		$sql = "
			INSERT INTO ".YR_DB.".year_balance (
				accid, topacc, accnum, accname, debit, credit, div
			) VALUES (
				'$bal[accid]', '$bal[topacc]', '$bal[accnum]', '$bal[accname]', '$bal[debit]', '$bal[credit]', '$bal[div]'
			)";
		$inRslt = db_exec($sql) or errDie("Failed to store year balance.");
	}

	//$sql = "CREATE TABLE \"".YR_DB."\".stkledger AS SELECT * FROM core.stkledger";
	//$rslt = db_exec_safe($sql);

	// make a copy from the trial balance
	$sql = "DROP TABLE \"".YR_DB."\".trial_bal";
	//$rslt = db_exec_safe($sql);

	$sql = "CREATE TABLE \"".YR_DB."\".trial_bal AS SELECT * FROM core.trial_bal";
	$rslt = db_exec_safe($sql);

	// recreate the trial balance actual view
	$sql = "
		CREATE OR REPLACE VIEW \"".YR_DB."\".trial_bal_actual AS
		SELECT tb.accid, tb.topacc, tb.accnum, tb.accname, tb.vat, tb.div, tb.acctype, 
			CASE
				WHEN tb.period = 1 THEN tb.debit
				ELSE tb.debit - atb.debit
			END AS debit,
			CASE
				WHEN tb.period = 1 THEN tb.credit
				ELSE tb.credit - atb.credit
			END AS credit, tb.month, tb.period
		FROM \"".YR_DB."\".trial_bal tb LEFT JOIN \"".YR_DB."\".trial_bal atb
			ON tb.period = (atb.period::int + 1) AND tb.accid = atb.accid;";

	db_exec_safe($sql);
	
	/* fetch customer balances */
	$custs = qryCustomer(false, "cusnum");
	$custdebit = array();
	$custcredit = array();
	while ($cd = $custs->fetch_array()) {
		$qry = new dbSelect("custledger", $PRDMON['12'], grp(
			m("cols", "cbalance, dbalance"),
			m("where", "cusnum='$cd[cusnum]'"),
			m("order", "id DESC"),
			m("limit", 1)
		));
		$qry->run();
		
		if ($qry->num_rows() <= 0) {
			$custcredit[$cd["cusnum"]] = 0;
			$custdebit[$cd["cusnum"]] = 0;
		} else {
			$qry->fetch_array();
			$custcredit[$cd["cusnum"]] = $qry->d["cbalance"];
			$custdebit[$cd["cusnum"]] = $qry->d["dbalance"];
		}
	}
	
	/* fetch supplier balances */
	$supps = qrySupplier(false, "supid");
	$suppdebit = array();
	$suppcredit = array();
	while ($sd = $supps->fetch_array()) {
		$qry = new dbSelect("suppledger", $PRDMON['12'], grp(
			m("cols", "cbalance, dbalance"),
			m("where", "supid='$sd[supid]'"),
			m("order", "id DESC"),
			m("limit", 1)
		));
		$qry->run();
		
		if ($qry->num_rows() <= 0) {
			$suppcredit[$sd["supid"]] = 0;
			$suppdebit[$sd["supid"]] = 0;
		} else {
			$qry->fetch_array();
			$suppcredit[$sd["supid"]] = $qry->d["cbalance"];
			$suppdebit[$sd["supid"]] = $qry->d["dbalance"];
		}
	}
	
	/* copy the inventory ledger */
	for ($i = 1; $i <= 12; ++$i) {
		$mname = strtolower(getMonthName($i));
		
		$sql = "CREATE TABLE audit.${mname}_stkledger 
				AS 
				SELECT * FROM \"$i\".stkledger";
		db_exec($sql) or errDie("Error copying inventory ledger (P$i).");
	}

	// Empty All Period Databases
	for ($i = 1; $i <= 14; $i++) {
		db_conn($i);
		$sql = "TRUNCATE TABLE transect;";
		$sql .= "TRUNCATE TABLE ledger;";
		$sql .= "TRUNCATE TABLE custledger;";
		$sql .= "TRUNCATE TABLE suppledger;";
		$sql .= "TRUNCATE TABLE empledger;";
		db_exec($sql) or errDie("Unable to empty Period databases",SELF);
	}

	$qryi = new dbUpdate();

	recreateAudit();
	
	/* FP AUDIT FIX 
		for ($p = 1; $p <= 12; ++$p) {
			$monnum = $PRDMON[$i];
			$monname = strtolower(getMonthName($i));
		}
	*/

	/* create the customer ledger balance entries */
	$custs = qryCustomer(false, "cusnum");
	while ($cd = $custs->fetch_array()) {
		for ($i = 1; $i <= 12; ++$i) {
			$cols = grp(
				m("cusnum", $cd["cusnum"]),
				m("contra", 0),
				m("edate", $sdate),
				m("sdate", raw("CURRENT_DATE")),
				m("eref", 0),
				m("descript", "Balance"),
				m("credit", 0),
				m("debit", 0),
				m("cbalance", $custcredit[$cd["cusnum"]]),
				m("dbalance", $custdebit[$cd["cusnum"]]),
				m("div", USER_DIV)
			);

			$qryi->setTable("custledger", "$i");
			$qryi->setOpt($cols);
			$qryi->run(DB_INSERT);

			/* audit customer ledger */
			$cols = grp(
				m("cusnum", $cd["cusnum"]),
				m("contra", 0),
				m("edate", $sdate),
				m("sdate", raw("CURRENT_DATE")),
				m("eref", 0),
				m("descript", "Balance"),
				m("credit", 0),
				m("debit", 0),
				m("cbalance", $custcredit[$cd["cusnum"]]),
				m("dbalance", $custdebit[$cd["cusnum"]]),
				m("div", USER_DIV),
				m("actyear", YR_NAME)
			);

			$qryi->setTable(getMonthName($i)."_custledger", "audit");
			$qryi->setOpt($cols);
			$qryi->run(DB_INSERT);
		}
	}

	/* create the supplier ledger balance entries */
	$supps = qrySupplier(false, "supid");
	while ($sd = $supps->fetch_array()) {
		for ($i = 1; $i <= 12; ++$i) {
			$qryi->setTable("suppledger", "$i");

			$cols = grp(
				m("supid", $sd["supid"]),
				m("contra", "0"),
				m("edate", $sdate),
				m("sdate", raw("CURRENT_DATE")),
				m("eref", "0"),
				m("descript", "Balance"),
				m("credit", "0"),
				m("debit", "0"),
				m("div", USER_DIV),
				m("cbalance", $suppcredit[$sd["supid"]]),
				m("dbalance", $suppdebit[$sd["supid"]])
			);
			$qryi->setCols($cols);
			$qryi->run(DB_INSERT);
		}
	}
	
	/* create the stock ledger entries */
	$stock = qryStock(false, "stkid, stkcod, stkdes, units, csamt");
	while ($stk = $stock->fetch_array()) {
		for ($i = 1; $i <= 12; ++$i) {
			$qryi->setTable("stkledger", "$i");
			
			$cols = grp(
				m("stkid", $stk["stkid"]),
				m("stkcod", $stk["stkcod"]),
				m("stkdes", $stk["stkdes"]),
				m("trantype", "bal"),
				m("edate", $sdate),
				m("qty", $stk["units"]),
				m("csamt", $stk["csamt"]),
				m("balance", $stk["csamt"]),
				m("bqty", $stk["units"]),
				m("details", "Balance"),
				m("div", USER_DIV),
				m("yrdb", $nextyr)
			);
			$qryi->setCols($cols);
			$qryi->run(DB_INSERT);
		}
	}

	/* do the retained income entries for all branches */
	$sql = "SELECT div FROM cubit.branches";
	$branRs = db_exec($sql) or errDie("Could not access branches table.");
	while ($bran = pg_fetch_array($branRs)) {
		fintran($bran['div']);
	}

	/* create the ledger account balance entries */
	for ($i = 1; $i <= 12; ++$i) {
		$periodname = getMonthName($i);
		
		$sql = "
			INSERT INTO ".YR_DB.".$periodname (
				accid, topacc, accnum, accname, debit, credit, div
			) SELECT accid, topacc, accnum, accname, debit, credit, div
				FROM core.trial_bal WHERE month='1'";
		db_exec($sql) or errDie("Error creating ledger balances (1)");
	
		$sql = "
			INSERT INTO \"$i\".openbal (
				accid, accname, debit, credit, div
			) SELECT accid, accname, debit, credit, div
				FROM core.trial_bal WHERE month='1'";
		db_exec($sql) or errDie("Error creating ledger balances (2)");
		
		$sql = "
			INSERT INTO \"$i\".ledger (
				acc, contra, edate, eref, descript, credit, debit, div, caccname, ctopacc, caccnum, cbalance, dbalance
			) SELECT accid, accid, CURRENT_DATE, '0', 'Balance', '0', '0', div, accname, topacc, accnum, credit, debit
				FROM core.trial_bal WHERE month='1'";
		db_exec($sql) or errDie("Error creating ledger balances (3)");
	}

	/* close and select new year */
	selectNextYear($nextyr);

	/* mark year as closed */
	$sql = "UPDATE core.year SET closed = 'y' WHERE yrdb = '".YR_DB."'";
	$rslt = db_exec($sql) or errDie("Could not Set Next Year Database and Name",SELF);

	pglib_transaction("COMMIT");

	// Display
	$write = "<center><h3> Current Year has been closed </h3>
	<b>( i ) The next Year has been activated ( i )</b></center>"
	.mkQuickLinks();

	return $write;
}


function save_statement($stmnt, $tbl_pfx) {
	$stmnt = clean_html($stmnt);
	$cols = grp(
		m("gendate", raw("CURRENT_DATE")),
		m("output", base64_encode($stmnt)),
		m("div", USER_DIV),
		m("des", "Saved by ".USER_NAME." at Year End: ".getFinYear())
	);
	$stmnt = new dbUpdate("save_$tbl_pfx", "core", $cols);
	$stmnt->run(DB_INSERT);
}


function fintran($div) {
	# Transfer from to Profit/Loss account (9999/999)
	$plRs = unget("core","*","accounts","topacc","5200' AND accnum = '000' AND div = '$div");
	if(pg_numrows($plRs) < 1){
		$OUTPUT = "<li> Retained Income / Accumulated Loss Account number : 5200/000 does not exist";
		require("../template.php");
	}
	$pl = pg_fetch_array($plRs);

// 	# Total Income (1999/999)
// 	$tiRs = unget("core","*","accounts","topacc","1999' AND accnum = '999' AND div = '$div");
// 	if(pg_numrows($tiRs) < 1){
// 		$OUTPUT = "<li> Total Income Account number : 1999/999 does not exist";
// 		require("../template.php");
// 	}
// 	$ti = pg_fetch_array($tiRs);
//
// 	# total Expenses (4999/999)
// 	$teRs = unget("core","*","accounts","topacc","4999' AND accnum = '999' AND div = '$div");
// 	if(pg_numrows($teRs) < 1){
// 		$OUTPUT = "<li> Total Expenses Account number : 4999/999 does not exist";
// 		require("../template.php");
// 	}
// 	$te = pg_fetch_array($teRs);

	$netProfit = sprint(getNetProfit($div));

	if (floatval($netProfit) > 0) {
		writetransdivy(0, $pl['accid'] , date("d-m-Y"), 0, $netProfit, "Year End, Net profit.", $div);
	} else if (floatval($netProfit) < 0) {
		$netProfit = ($netProfit * (-1));
		writetransdivy($pl['accid'], 0, date("d-m-Y"), 0, $netProfit, "Year End, Net loss.", $div);
	}

	$sql = "
		UPDATE core.trial_bal 
		SET debit=(SELECT debit FROM core.trial_bal AS tbdt WHERE period='12' AND accid=trial_bal.accid),
			credit=(SELECT credit FROM core.trial_bal AS tbct WHERE period='12' AND accid=trial_bal.accid)";
	db_exec($sql) or errDie("Error updating balances for new financial year.");

	$sql = "
		UPDATE core.trial_bal 
		SET credit=0, debit=0 
		WHERE (acctype='I' OR acctype='E') AND period>0 AND month>0 AND div='$div'";
	db_exec($sql) or errDie("Could not reset Income Statement Balances on year DB",SELF);

	$sql = "
		UPDATE core.trial_bal 
		SET debit=(SELECT debit FROM ".YR_DB.".trial_bal AS tbdt WHERE period='12' AND accid=trial_bal.accid),
			credit=(SELECT credit FROM ".YR_DB.".trial_bal AS tbct WHERE period='12' AND accid=trial_bal.accid) 
		WHERE period=0 AND month=0";
	db_exec($sql) or errDie("Error updating balances for new financial year.");
}

function getNetProfitDumbOldTechnique($div) {
	core_connect();
	$sql = "SELECT accid FROM accounts WHERE acctype='I' AND div = '$div'";
	$incRslt = db_exec($sql) or errDie("Unable to retrieve income statement settings from the Database",SELF);
	if(pg_numrows($incRslt) < 1){
		$OUTPUT = "<center>There Are no Income in Cubit.";
		require("../template.php");
	}

	# Get income accounts Balances
	$tlinc = 0; // total income credit

	while($inc = pg_fetch_array($incRslt)){
		# get the balances (debit nad credit) from trial Balance
		$sql = "SELECT * FROM trial_bal WHERE accid = '$inc[accid]' AND period='12'";
		$balRslt = db_exec($sql) or errDie("Unable to retrieve Account Balance information from the Database.",SELF);
		$bal = pg_fetch_array($balRslt);

		$total = ($bal['credit'] - $bal['debit']);
		$tlinc += $total;
	}

	# Get the income statement settings
	$sql = "SELECT accid FROM accounts WHERE acctype='E' AND div = '$div'";
	$expRslt = db_exec($sql) or errDie("Unable to retrieve income statement settings from the Database",SELF);
	if(pg_numrows($expRslt) < 1){
		$OUTPUT = "<center>There Are no Expenditure accounts in Cubit.";
		require("../template.php");
	}

	# Get account Balances for Expenditure
	$tlexp = 0; // total expenditures

	while($exp = pg_fetch_array($expRslt)){
		# Get vars from inc (accnum, type)
		foreach($exp as $key => $value){
				$$key = $value;
		}

		# Get the balances (debit nad credit) from trial Balance
		$sql = "SELECT * FROM trial_bal WHERE accid = '$exp[accid]' AND period='12'";
		$balRslt = db_exec($sql) or errDie("Unable to retrieve Account Balance information from the Database.",SELF);
		$bal = pg_fetch_array($balRslt);

		# alternate bgcolor
		$total = ($bal['debit'] - $bal['credit']);
		$tlexp += $total;        // And increment the balance for expenditure
	}
	return sprintf("%01.2f", ($tlinc - $tlexp));
	core_connect();
}

function selectNextYear($nextyr) {
	// get next year name
	$sql = "SELECT * FROM year WHERE yrdb ='".$nextyr."'";
	$rslt = db_exec($sql);
	$yr = pg_fetch_array($rslt);

	// get first period name
	// Get range
	core_connect();
	$sql = "SELECT * FROM range";
	$Rslt = db_exec($sql);
	if(pg_numrows($Rslt) < 1){
		$OUTPUT = "<center><li class=err>ERROR : The Financial year Period range was not found on Database, Please make sure that everything is set during instalation.";
		require("template.php");
	}
	$range = Pg_fetch_array($Rslt);

	// Months array
	$months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

	db_connect();
	$sql = "delete from set where label = 'YRCLOSE'";
	$rslt = db_exec($sql) or errDie("Could not Set Next Year Database and Name",SELF);

	// Update Active Year Db and name
	db_conn("core");
	$sql = "UPDATE active SET yrdb = '$nextyr', yrname = '$yr[yrname]', prddb = '$range[start]', prdname='".$months[$range['start']]."'";
	$rslt = db_exec($sql) or errDie("Could not Set Next Year Database and Name",SELF);

	$sql = "UPDATE year SET closed = 'y' WHERE yrdb = '".YR_DB."'";
	$rslt = db_exec($sql) or errDie("Could not Set Last Year Database as Closed.",SELF);
}

function recreateAudit() {
	// Copy the audit DB
	db_conn("cubit");
	$sql  = "ALTER SCHEMA audit RENAME TO \"".YR_NAME."_audit\"";
	$Rslt = @db_exec_safe($sql);

	// Create a new audit
	$sql  = "CREATE SCHEMA audit";
	$rslt = @db_exec_safe($sql);

	db_conn("audit");
	// create the new audit database
	if ( ! $fd = fopen("../dumping/sql/audit.sql", "r") )
		errDie("Audit database template file not found. Please
			contact Cubit to obtain this a file (TMPL_YR_CLOSE).");

	$lcount = 0;
	while ( ! feof($fd) ) {
		$line = "";
		$pc = "";
		// read all the characters into line until end of query
		while ( ($c = fgetc($fd)) !== false ) {
			if ( $c == "\n" ) {
				// line is finished, blank or comment, break
				if ( strlen(trim($line)) < 1 || preg_match("/^--/", trim($line)) || $pc == ";" )
					break;
			}
			$line .= $c;
			if ( $c != " " && $c != "\n" ) $pc = $c;
		}
		$line = trim($line);
		++$lcount;

		if ( empty($line) || preg_match("/^--/", $line) ) continue;
		@db_exec_safe($line);
	}

	fclose($fd);

}


?>
