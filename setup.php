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

define("SETUP_PHP", true);
global $tmp_prdmap, $PRDMON;
$tmp_prdmap = array();
$PRDMON = &$tmp_prdmap;

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "write":
			$OUTPUT = write_sets ($_POST);
			break;
		default:
			$OUTPUT = get_sets ();
	}
} else {
	$OUTPUT = get_sets ();
}

# display output
require ("template.php");




function get_sets ()
{

	db_conn('cubit');

	# Check if setting exists
	$sql = "SELECT value FROM set WHERE label = 'ACCNEW_LNK'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		$set = pg_fetch_array($Rslt);
		if($set['value'] == 'acc-new.php'){
			return printSet();
		}
	}

	core_connect();

	$sql = "SELECT accname FROM accounts WHERE accnum != '999' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing accounts.");
	if (pg_numrows ($Rslt) > 0) {
		$acc = "
		<center>
		<table ".TMPL_tblDflts.">
			<tr>
				<td><li class='err'>ERROR : There are accounts in Cubit</li></td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Note : </th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Quick Setup can only be run on a new cubit installation.</td>
			</tr>
			".TBL_BR."
		</table>
    	<p>
		<table ".TMPL_tblDflts." width='15%'>
        	".TBL_BR."
        	<tr>
        		<th>Quick Links</th>
        	</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
		return $acc;

	}

	$month = 1;
	$months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
	$smonth = "<select name='smonth'>";
	while($month <= 12){
		if($month == 3) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$smonth .= "<option $sel value='$month'>$months[$month]</option>";
		$month++;
	}
	$smonth .= "</select>";

	$mdat = date("m");
	$mdat += 0;
	if($mdat > 2) {
		$plus = 1;
	} else {
		$plus = 0;
	}

	$amonth = 1;
	$amonths = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
	$asmonth = "<select name='activemonth'>";
	while($amonth <= 12){
		if($amonth == $mdat) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$asmonth .= "<option $sel value='$amonth'>$amonths[$amonth]</option>";
		$amonth++;
	}
	$asmonth .= "</select>";

	$asmonth = "<input type='hidden' name='activemonth' value='1'>";

	$selyear = "<select name='selyear'>";
	for ( $i = 1971; $i <= 2027; $i++ ) {
		if ( $i == (date("Y")+$plus) )
			$sel = "selected";
		else
			$sel = "";

		$selyear .= "<option $sel value='$i'>$i</option>";
	}
	$selyear .= "</select>";

	# Check if year has been opened
	core_connect();
	$sql = "SELECT * FROM active";
	$cRs = db_exec($sql) or errDie("Database Access Failed - check year open.", SELF);
	if(pg_numrows($cRs) < 1){
		$monset = "
					<tr bgcolor='".bgcolorg()."'>
						<td>Financial Years Start in</td>
						<td valign='center'>$smonth</td>
					</tr>
				";
	}else{
		$act = pg_fetch_array($cRs);
		$monset = "
					<tr bgcolor='".bgcolorg()."'>
						<td>Financial Years Start in</td>
						<td valign='center'><input type='hidden' name='smonth' value='$act[prddb]'>".$months[$act['prddb']]."</td>
					</tr>
				";
	}

	$sets = "
			<h3>Setup</h3>
			<br>
			<table ".TMPL_tblDflts.">
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write' />
				<input type='hidden' name='yr1' value='y2003' />
				<input type='hidden' name='yr2' value='y2004' />
				<input type='hidden' name='yr3' value='y2005' />
				<input type='hidden' name='yr4' value='y2006' />
				<input type='hidden' name='yr5' value='y2007' />
				<input type='hidden' name='yr6' value='y2008' />
				<input type='hidden' name='yr7' value='y2009' />
				<input type='hidden' name='yr8' value='y2010' />
				<input type='hidden' name='yr9' value='y2011' />
				<input type='hidden' name='yr10' value='y2012' />
				<tr>
					<th colspan='2'>Please Select the Following</th>
				</tr>
				$monset
				$asmonth
				<tr bgcolor='".bgcolorg()."'>
					<td>Select Financial Year to Start At:</td>
					<td>$selyear</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Select Installation Type:</td>
					<td>HQ (Default) <input type='radio' name='inst_mode' value='hq' checked='yes'> Branch <input type='radio' name='inst_mode' value='branch'></td>
				</tr>
				<tr>
					<td colspan='2' align='right'><input type='submit' value='Auto Setup &raquo;'></td>
				</tr>
			</form>
			</table>
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	return $sets;

}




function makemap($smonth)
{

	global $tmp_prdmap;

	$sql = "INSERT INTO core.prdmap (month, period) VALUES('0', '0')";
	$rslt = db_exec($sql) or errDie("Error inserting to period-month map (0:0)");

	$tmp_prdmap[0] = 0;

	$prd = 1;

	while ($prd <= 12) {
		if ($smonth > 12) $smonth = 1;

		$sql = "INSERT INTO core.prdmap (month, period) VALUES('$smonth', '$prd')";
		$rslt = db_exec($sql) or errDie("Error inserting to period-month map ($smonth:$prd)");

		$tmp_prdmap[$prd] = $smonth;

		++$prd;
		++$smonth;
	}
}



# write settings
function write_sets ($_POST)
{

	global $catids, $CUBIT_MODULES;

	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();

	$v->isOk ($smonth, "num", 1, 2, "Invalid Financial year starting month.");
	/*$v->isOk ($monthend,"num",1 ,2, "Invalid month end date");
	$v->isOk ($int1,"float",1 ,5, "Invalid interest 1.");
	$v->isOk ($int2,"float",1 ,5, "Invalid interest 2.");
	$v->isOk ($int3,"float",1 ,5, "Invalid interest 3.");
	$v->isOk ($brack1,"float",1 ,10, "Invalid bracket 1.");
	$v->isOk ($brack2,"float",1 ,10, "Invalid bracket 2.");*/

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirmCust;
	}

	pglib_transaction("BEGIN");

	makemap($smonth);

	core_connect();
	$sql = "SELECT accname FROM accounts WHERE accnum != '999' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing accounts.");
	if (pg_numrows ($Rslt) > 0) {
		$acc = "
					<center>
					<table ".TMPL_tblDflts.">
						<tr>
							<td><li class='err'>ERROR : There are already accounts in Cubit</td>
						</tr>
						".TBL_BR."
						<tr>
							<th>Note : </th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Quick Setup can only be run on a new cubit installation.</td>
						</tr>
						".TBL_BR."
					</table>
					<p>
					<table ".TMPL_tblDflts." width='15%'>
						".TBL_BR."
						<tr>
							<th>Quick Links</th>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
		return $acc;

	}

	$sql = "
		INSERT INTO cubit.compinfo (
			compname, slogan, logoimg, addr1, addr2, addr3, 
			addr4, paddr1, paddr2, paddr3, tel, fax, 
			vatnum, regnum, imgtype, img, div, paye, 
			terms, postcode, img2, imgtype2, logoimg2, diplomatic_indemnity
		) VALUES (
			'$_SESSION[comp]', '', '', '', '', '', 
			'', '', '', '', '', '',
			'', '', '', '', '".USER_DIV."', '', 
			'', '', '', '', '', 'N'
		);";
	db_exec($sql) or errDie("Unable to update company information.");

	db_conn('cubit');
	$sql = "SELECT label FROM set WHERE label = 'ACCNEW_LNK' AND div = '".USER_DIV."'";
	$rslt = db_exec ($sql) or errDie ("Unable to check database for existing account creation settings.");
	if (pg_num_rows($rslt) > 0) {
		$sql = "
			UPDATE set 
			SET value = 'acc-new2.php', type = 'Account Creation' 
			WHERE label = 'ACCNEW_LNK'";
	} else {
		$sql = "
			INSERT INTO set (
				type, label, value, 
				descript, div
			) VALUES (
				'Account Creation', 'ACCNEW_LNK', 'acc-new2.php', 
				'Use user selected account numbers', '".USER_DIV."'
			)";
	}
	db_exec($sql) or errDie("Unable to insert account creation settings to Cubit.");

    /* account categories */
	$catids = array(
		"I" => 0,
		"E" => 0,
		"B" => 0
	);

	$sql = "
		INSERT INTO core.income (
			catid, catname, div
		) VALUES (
			'I' || nextval('core.income_seq'), 'Income', '".USER_DIV."'
		)";
	$catRslt = db_exec($sql) or errDie("Unable to add income Category to Database.");

	$catids["I"] = "I".pglib_getlastid("core.income_seq");

	$sql = "
		INSERT INTO core.expenditure (
			catid, catname, div
		) VALUES (
			'E' || nextval('core.expenditure_seq'),'Expenditure', '".USER_DIV."'
		)";
	$catRslt = db_exec($sql) or errDie("Unable to add expense Category to Database.");

	$catids["E"] = "E".pglib_getlastid("core.expenditure_seq");

	$sql = "
		INSERT INTO core.balance (
			catid, catname, div
		) VALUES (
			'B' || nextval('core.balance_seq'),'Balance', '".USER_DIV."'
		)";
	$catRslt = db_exec($sql) or errDie("Unable to add balance Category to Database.");

	$catids["B"] = "B".pglib_getlastid("core.balance_seq");

	/* START SETUP */
	$catid = $catids["I"];

	$sales_account = newacc("1000", "000", "Sales", "I", "f", "sales");
    $pos_sales_account = newacc("1100", "000", "Point of Sale - Sales", "I", "f", "sales");
    newacc("1150", "000", "Interest Received", "I", "f");
	newacc("1200", "000", "Sundry Income", "I", "f");
	newacc("1250", "000", "Exchange Rate Profit/Loss", "I", "f", "other_income");
	newacc("1300", "000", "Sale of Assets", "I", "f", "sales");
	linkacc("1300", "000", "salesacc", "saleofassets");
    linkacc("1150", "000", "salacc", "interestreceived");

	newacc("1660", "000", "Creditors Settlement Discount", "I", "f");

    newacc("1995", "000", "Previous Year Adjustment Income 1", "I", "f");
    newacc("1996", "000", "Previous Year Adjustment Income 2", "I", "f");
    newacc("1997", "000", "Previous Year Adjustment Income 3", "I", "f");
    newacc("1998", "000", "Previous Year Adjustment Income 4", "I", "f");
    newacc("1999", "000", "Previous Year Adjustment Income 5", "I", "f");

	$catid = $catids["E"];

	$cost_account = newacc("2150", "000", "Cost of Sales", "E", "f", "cost_of_sales");
	$pension_account = newacc("2510", "000", "Pension", "E", "f");
	newacc("2520", "000", "Retirement Annuity Fund", "E", "f");
    linkacc("2520", "000", "salacc", "retireexpense");
	newacc("2530", "000", "Provident Fund", "E", "f");
    linkacc("2530", "000", "salacc", "providentexpense");
	newacc("2540", "000", "Medical Aid", "E", "f");
    linkacc("2540", "000", "salacc", "medicalexpense");
	newacc("2160", "000", "Cost Variance", "E", "f", "cost_of_sales");
    linkacc("2160", "000", "pchsacc", "Cost Variance");
	newacc("2170", "000", "Variance", "E", "f", "cost_of_sales");
    linkacc("2170", "000", "salesacc", "sales_variance");
    
	newacc("2500", "000", "Salaries and Wages", "E", "f");
	linkacc("2500", "000", "salacc", "salaries");
	newacc("2550", "000", "Salaries - Commission", "E", "f");
	linkacc("2550", "000", "salacc", "Commission");
	newacc("2555", "000", "Salaries - Bonus", "E", "f");
	linkacc("2555", "000", "salacc", "Bonus");
	newacc("2560", "000", "UIF", "E", "f");
    linkacc("2560", "000", "salacc", "uifexp");
	newacc("2570", "000", "SDL", "E", "f");
    linkacc("2570", "000", "salacc", "sdlexp");
	newacc("2000", "000", "Accounting Fees", "E", "f");
	newacc("2050", "000", "Advertising and Promotions", "E", "f");
	newacc("2100", "000", "Bank Charges", "E", "f");
	newacc("2200", "000", "Depreciation", "E", "f");
	newacc("2250", "000", "Electricity and Water", "E", "f");
	newacc("2300", "000", "General Expenses", "E", "f");
	newacc("2350", "000", "Insurance", "E", "f");
	newacc("2400", "000", "Interest Paid", "E", "f");
	newacc("2450", "000", "Printing and Stationery", "E", "f");
	newacc("2650", "000", "Rent Paid", "E", "f");

	newacc("2600", "000", "Telephone and Fax", "E", "f");
	newacc("2700", "000", "POS Rounding", "E", "f", "cost_of_sales");
	linkacc("2700", "000", "salesacc", "rounding");
	newacc("2800", "000", "Normal Tax", "E", "f", "tax");
	linkacc("2510", "000", "salacc", "pensionexpense");
	newacc("2660", "000", "Creditors Settlement Discount", "E", "f");

	newacc("3660", "000", "Debtors Settlement Discount", "E", "f");

	newacc("4995", "000", "Previous Year Adjustment Expense 1", "E", "f");
	newacc("4996", "000", "Previous Year Adjustment Expense 2", "E", "f");
	newacc("4997", "000", "Previous Year Adjustment Expense 3", "E", "f");
	newacc("4998", "000", "Previous Year Adjustment Expense 4", "E", "f");
	newacc("4999", "000", "Previous Year Adjustment Expense 5", "E", "f");


	$catid = $catids["B"];

	newacc("5200", "000", "Retained Income / Accumulated Loss", "B", "f", "retained_income");
	newacc("5250", "000", "Share Capital / Members Contribution", "B", "f", "share_capital");
	newacc("5300", "000", "Shareholder / Director / Members Loan Account", "B", "f", "shareholders_loan");
	newacc("6000", "000", "Land & Buildings - Net Value", "B", "f", "fixed_asset");
	newacc("6000", "010", "Land & Buildings - Cost", "B", "f", "fixed_asset");
	newacc("6000", "020", "Land & Buildings - Accum Depreciation", "B", "f", "fixed_asset");
	newacc("6100", "020", "Motor Vehicle - Accum Depreciation", "B", "f", "fixed_asset");
	newacc("6100", "000", "Motor Vehicle - Net Value", "B", "f", "fixed_asset");
	newacc("6100", "010", "Motor Vehicle - Cost", "B", "f", "fixed_asset");
	newacc("6150", "000", "Computer Equipment - Net Value", "B", "f", "fixed_asset");
	newacc("6150", "010", "Computer Equipment - Cost", "B", "f", "fixed_asset");
	newacc("6150", "020", "Computer Equipment - Accum Depreciation", "B", "f", "fixed_asset");
	newacc("6160", "000", "Office Equipment - Net Value", "B", "f", "fixed_asset");
	newacc("6160", "010", "Office Equipment - Cost", "B", "f", "fixed_asset");
	newacc("6160", "020", "Office Equipment - Accum Depreciation", "B", "f", "fixed_asset");
	newacc("6170", "000", "Furniture & Fittings - Net Value", "B", "f", "fixed_asset");
	newacc("6170", "010", "Furniture & Fittings - Cost", "B", "f", "fixed_asset");
	newacc("6170", "020", "Furniture & Fittings - Accum Depreciation", "B", "f", "fixed_asset");
	$stock_control = newacc("6300", "000", "Inventory Suspense Account", "B", "f", "current_asset");
	$stock_account = newacc("6350", "000", "Inventory", "B", "f", "current_asset");
	$deptors_account = newacc("6400", "000", "Customer Control Account", "B", "f", "current_asset");
	$creditors_account = newacc("6500", "000", "Supplier Control Account", "B", "f", "current_liability");
	newacc("6600", "000", "Employees Control Account", "B", "f", "current_liability");
	newacc("2151", "000", "Stock Take Suspense Account", "E", "f", "cost_of_sales");
    linkacc("6600", "000", "salacc", "salaries control");
    linkacc("6600", "000", "salacc", "salaries control original");
	newacc("6700", "000", "Employee Loan Account", "B", "f", "current_asset");
    linkacc("6700", "000", "salacc", "loanacc");
	$bank_account = newacc("7000", "000", "Bank", "B", "f", "current_asset");
	newacc("7100", "000", "Petty Cash", "B", "f", "current_asset");
    linkacc("7100", "000", "bankacc", "Petty Cash");
	$pos_cash_account = newacc("7200", "000", "Cash on Hand", "B", "f", "current_asset");
    linkacc("7200", "000", "salacc", "cash");
	newacc("7300", "000", "POS Credit Card Control", "B", "f", "current_asset");
    linkacc("7300", "000", "salacc", "cc");
	newacc("8000", "000", "VAT Control Account", "B", "f", "current_liability");
    linkacc("8000", "000", "salesacc", "VAT");
	newacc("8010", "000", "VAT Input Account", "B", "f", "current_liability");
    linkacc("8010", "000", "salesacc", "VATIN");
	newacc("8020", "000", "VAT Output Account", "B", "f", "current_liability");
    linkacc("8020", "000", "salesacc", "VATOUT");
	newacc("8100", "000", "PAYE Payable", "B", "f", "current_liability");
    linkacc("8100", "000", "salacc", "PAYE");
	newacc("8200", "000", "UIF Payable", "B", "f", "current_liability");
    linkacc("8200", "000", "salacc", "UIF");
    linkacc("8200", "000", "salacc", "uifbal");
	newacc("8300", "000", "SDL Payable", "B", "f", "current_liability");
    linkacc("8300", "000", "salacc", "sdlbal");
	newacc("8400", "000", "Pension Payable", "B", "f", "current_liability");
    linkacc("8400", "000", "salacc", "pension");
	newacc("8500", "000", "Medical Aid Payable", "B", "f", "current_liability");
    linkacc("8500", "000", "salacc", "medical");
	newacc("8600", "000", "Retirement Annuity Fund Payable", "B", "f", "current_liability");
    linkacc("8600", "000", "salacc", "retire");
	newacc("8700", "000", "Provident Fund Payable", "B", "f", "current_liability");
    linkacc("8700", "000", "salacc", "provident");
	newacc("9000", "000", "Opening Balances / Suspense Account", "B", "f", "current_liability");

	newacc("9995", "000", "Previous Year Adjustment Balance 1", "B", "f", "fixed_asset");
	newacc("9996", "000", "Previous Year Adjustment Balance 2", "B", "f", "fixed_asset");
	newacc("9997", "000", "Previous Year Adjustment Balance 3", "B", "f", "fixed_asset");
	newacc("9998", "000", "Previous Year Adjustment Balance 4", "B", "f", "fixed_asset");
	newacc("9999", "000", "Previous Year Adjustment Balance 5", "B", "f", "fixed_asset");

	# Check if year has been opened
	$sql = "DELETE FROM core.year";
	$rslt = db_exec($sql);
	for($i = 1; $i <= 10; $i++) {
		$sql = "INSERT INTO core.year VALUES('y".($selyear + $i - 1)."', 'yr$i', 'n', '".USER_DIV."')";
		$rslt = db_exec($sql) or errDie("Could not set year name in Cubit",SELF);
	}

	$yrname = "y$selyear";

	$endmon = ($smonth - 1);
	if (intval($endmon) == 0) $endmon = 12;

	$Sql = "TRUNCATE core.range";
	$Rs = db_exec($Sql) or errDie("Unable to empty year range", SELF);

	$firstmonth = $smonth;
	$activeyear = $yrname;

	$sql = "
		INSERT INTO core.range (
			\"start\", \"end\", div
		) VALUES (
			'$smonth', '$endmon', '".USER_DIV."'
		)";
	$Rslt = db_exec($sql) or errDie("Unable to insert year range", SELF);

	$sql = "SELECT * FROM core.year WHERE yrname='$yrname'";
	$yrs = db_exec($sql);
	$yr = pg_fetch_array($yrs);
	if($yr['closed'] == 'y'){
		return "<center><li class='err'>ERROR : The Selected Financial year : <b>$yrname</b> has been closed.
		<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
	}

	$yrdb =$yr['yrdb'];

	$sql = "SELECT * FROM core.range";
	$Rslt = db_exec($sql);
	if(pg_numrows($Rslt) < 1){
		$OUTPUT = "<center><li class='err'>ERROR : The Financial year Period range was not found on Database, Please make sure that everything is set during instalation.</li>";
		require("template.php");
	}
	$range = Pg_fetch_array($Rslt);

	// Months array
	$months = array("dummy",
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
    	"December"
    );

	$sql = "INSERT INTO core.active (yrdb, yrname, prddb, prdname, div) VALUES ('$yrdb', '$yrname', '$range[start]', '".$months[$range['start']]."', '".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Could not Set Next Year Database and Name",SELF);

	db_conn ("exten");
	$sql = "INSERT INTO departments (deptno, deptname, incacc, debtacc, credacc, pia, pca, div) VALUES ('1', 'Ledger 1', '$sales_account', '$deptors_account', '$creditors_account', '$pos_sales_account', '$pos_cash_account', '".USER_DIV."')";
	$deptRslt = db_exec ($sql) or errDie ("Unable to add deparment to system.", SELF);

	$sql = "INSERT INTO salespeople (salespno, salesp, div) VALUES ('1', 'General', '".USER_DIV."')";
	$salespRslt = db_exec ($sql) or errDie ("Unable to add warehouse to system.", SELF);

	$sql = "INSERT INTO  categories (category, div) VALUES ('General', '".USER_DIV."')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add category to system.", SELF);

	$sql = "INSERT INTO  class (classname, div) VALUES ('General', '".USER_DIV."')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add fringe benefit to system.", SELF);

	$sql = "INSERT INTO warehouses (whno, whname, stkacc, cosacc, conacc, div) VALUES ('1', 'Store 1', '$stock_account', '$cost_account', '$stock_control', '".USER_DIV."')";
	$whouseRslt = db_exec ($sql) or errDie ("Unable to add warehouse to system.", SELF);
	$whid = pglib_lastid ("warehouses", "whid");

	$sql = "INSERT INTO  pricelist (listname, div) VALUES ('Standard', '".USER_DIV."')";
	db_exec ($sql) or errDie ("Unable to price list to system.", SELF);

	$sql = "INSERT INTO cubit.stockcat (catcod, cat, descript, div) VALUES('1', 'General', 'General Stock Category', '".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert stock category to Cubit.",SELF);

	$sql = "INSERT INTO cubit.stockclass (classcode, classname, div) VALUES ('1', 'General', '".USER_DIV."')";
	db_exec($sql) or errDie("Unable to add class to system.", SELF);

	$sql = "SELECT label FROM cubit.set WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_num_rows($rslt) > 0) {
		$sql = "UPDATE cubit.set SET value = '$whid', type = 'Default Warehouse' WHERE label = 'DEF_WH' AND div = '".USER_DIV."'";
	} else {
		$sql = "INSERT INTO cubit.set (type, label, value, descript, div) VALUES('Default Warehouse', 'DEF_WH', '$whid', '1 &nbsp;&nbsp;&nbsp; Store1', '".USER_DIV."')";
	}
	db_exec($sql) or errDie("Unable to insert settings to Cubit.");

	$sql = "SELECT label FROM cubit.set WHERE label = 'SELAMT_VAT' AND div = '".USER_DIV."'";
	$rslt = db_exec($sql) or errDie("Unable to check database for existing settings.");
	if (pg_num_rows($rslt) > 0) {
		$sql = "UPDATE cubit.set SET value = 'inc', descript = 'Vat Inclusive' WHERE label = 'SELAMT_VAT' AND div = '".USER_DIV."'";
	}else{
		$sql = "INSERT INTO cubit.set (type, label, value, descript, div) VALUES('Vat type on stock selling price', 'SELAMT_VAT', 'inc', 'Vat Inclusive', '".USER_DIV."')";
	}
	db_exec($sql) or errDie("Unable to insert settings to Cubit.");

	$sql = "INSERT INTO cubit.currency (symbol,curcode,descrip,rate,def) VALUES ('R','ZAR', 'Rand',0.00,'')";
	db_exec($sql) or errDie("Unable to insert currency.");

	$sql = "
		INSERT INTO cubit.bankacct (
			acctype, bankname, branchname, branchcode, accname, 
			accnum, details, div, btype, 
			fcid, currency
		) VALUES (
			'Cheque', 'Bank', 'Branch', '000000', 'Account Name', 
			'000000000000', 'Default bank Account', '".USER_DIV."', 'loc', 
			(SELECT fcid FROM cubit.currency WHERE curcode='ZAR' LIMIT 1), 'Rand'
		)";
	db_exec($sql) or errDie("Unable to add bank account to database.");

	$accid = pglib_lastid("cubit.bankacct", "bankid");

	$sql = "INSERT INTO cubit.set (type, label, value, descript, div)
			VALUES('Banking Details Account', 'BANK_DET', '3', 'Bank Account: Account Name - Bank', '".USER_DIV."')";
	db_exec($sql) or errDie("Unable to set default bank account.");

	$hook = "INSERT INTO core.bankacc (accid, accnum, div) VALUES('$accid', '$bank_account', '".USER_DIV."')";
	$Rlst = db_exec($hook) or errDie("Unable to add link for for new bank account", SELF);

	$sql = "INSERT INTO crm.links (name,script) VALUES ('Add Client','../customers-new.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('View Client','../customers-view.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('New Invoice','../cust-credit-stockinv.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('Find Invoice','../invoice-search.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('View Stock','../stock-view.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('Add Supplier','../supp-new.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('View Suppliers','../supp-view.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('New Purchase','../purchase-new.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('View Purchases','../purchase-view.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('Add Quote','../quote-new.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('View Invoices','../invoice-view.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('View Quotes','../quote-view.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('Debtors Age Analysis','../reporting/debt-age-analysis.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('Creditors Age Analysis','../reporting/cred-age-analysis.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.links (name,script) VALUES ('Bank Reconciliation','../reporting/bank-recon.php')";
	db_exec($sql) or errDie("Unable to insert link.");

	$sql = "INSERT INTO crm.teams (name,div) VALUES ('Sales','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.teams (name,div) VALUES ('Support','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.teams (name,div) VALUES ('Accounts','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.teams (name,div) VALUES ('Company Relations','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.teams (name,div) VALUES ('Purchasing - Supplier Relations','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.tcats (name,div) VALUES ('Product Enquiries','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.tcats (name,div) VALUES ('Place an Order','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.tcats (name,div) VALUES ('Complain','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.tcats (name,div) VALUES ('Account querries','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.tcats (name,div) VALUES ('Delivery or Installation Tracking','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.tcats (name,div) VALUES ('Comment on good service or Remarks','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.tcats (name,div) VALUES ('Ask about employment','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.tcats (name,div) VALUES ('General','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.tcats (name,div) VALUES ('Potential Supplier','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.tcats (name,div) VALUES ('Product Support','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into teams");

	$sql = "INSERT INTO crm.actions (action) VALUES ('Called - Need to call again.')";
	db_exec($sql) or errDie("Unable to insert action.");

	$sql = "INSERT INTO crm.actions (action) VALUES ('Called - Could not get in touch')";
	db_exec($sql) or errDie("Unable to insert action.");

	$sql = "INSERT INTO crm.actions (action) VALUES ('Requested more information')";
	db_exec($sql) or errDie("Unable to insert action.");

	$sql = "INSERT INTO crm.actions (action) VALUES ('Sent Fax')";
	db_exec($sql) or errDie("Unable to insert action.");

	$pactivemonth = $activemonth;

	$pactivemonth--;

	if($pactivemonth == 0) {
		$pactivemonth = 12;
	}

	$i = 0;
	$current = $firstmonth;
	$current--;

	if($current == 0) {
		$current = 12;
	}

	/* disabled, it wurks differently now */
	while($current != $pactivemonth && 0) {
		$i++;

		if($i > 20) {
			break;
		}

		$current++;

		if($current == 13) {
			$current = 1;
		}

		close_month('yr1',$current);
	}

	for ($i = 1; $i <= 12; ++$i) {
		close_month('yr1',$i);
	}

	$sql = "SELECT accid FROM core.accounts WHERE accname='Bank Charges'";
	$rslt = db_exec($sql);

	$ad = pg_fetch_array($rslt);
	$bc = $ad['accid'];

	$sql = "SELECT accid FROM core.accounts WHERE accname='Interest Paid'";
	$rslt = db_exec($sql);

	$ad = pg_fetch_array($rslt);
	$i = $ad['accid'];

	$sql = "SELECT accid FROM core.accounts WHERE accname='Interest Received'";
	$rslt = db_exec($sql);

	$ad = pg_fetch_array($rslt);
	$ii = $ad['accid'];

	$sql = "INSERT INTO exten.spricelist (listname,div) VALUES ('Standard','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert into supplier price list.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('CASH DEPOSIT FEE','i','-','c','$bc','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('FEE CHEQUE CASHED','i','-','c','$bc','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('FEE-SPECIAL PRESENTATION','i','-','c','$bc','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('SERVICE FEE','i','-','c','$bc','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('OVERDRAFT LEDGER FEE','i','-','c','$bc','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('INTEREST','i','-','c','$i','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('INTEREST','i','+','c','$ii','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('TRANSACTION CHARGE ','i','-','c','$bc','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('ADMIN CHARGE','i','-','c','$bc','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('GARAGE CRD CHARGES','i','-','c','$bc','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('STAMP DUTY','i','-','c','$bc','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('BANKING CHARGES','i','-','c','$bc','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.statement_refs (ref,dets,pn,action,account,by) VALUES ('01 CASH DEP','i','-','c','$bc','Default');";
	db_exec($sql) or errDie("Unable to insert data.");

 	$sql = "CREATE INDEX stkid_stock_key ON cubit.stock USING btree(stkid);";
 	db_exec($sql) or errDie("Unable to index.");

 	$sql = "CREATE INDEX accid_accounts_key ON core.accounts USING btree(accid);";
 	db_exec($sql) or errDie("Unable to index.");

	$sql = "CREATE INDEX accid_trial_bal_key ON core.trial_bal USING btree(accid);";
	db_exec($sql) or errDie("Unable to index.");

	$sql = "INSERT INTO cubit.vatcodes (code,description,del,zero,vat_amount) VALUES ('01','Normal','Yes','No','14');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.vatcodes (code,description,del,zero,vat_amount) VALUES ('02','Capital Goods','No','No','14');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.vatcodes (code,description,del,zero,vat_amount) VALUES ('03','Capital Goods','No','Yes','0');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.vatcodes (code,description,del,zero,vat_amount) VALUES ('04','Zero VAT','No','Yes','0');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.vatcodes (code,description,del,zero,vat_amount) VALUES ('05','VAT Exempt','No','Yes','0');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.report_types (type,div) VALUES ('Disciplinary Verbal Warning','".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.report_types (type,div) VALUES ('Disciplinary Written Warning','".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.report_types (type,div) VALUES ('Dismissal','".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.report_types (type,div) VALUES ('Corrective Counselling','".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.report_types (type,div) VALUES ('Performance Counselling','".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.report_types (type,div) VALUES ('Grievance','".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.report_types (type,div) VALUES ('Disputes Mediation','".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.report_types (type,div) VALUES ('Disputes Conciliation','".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.report_types (type,div) VALUES ('Disputes Arbitration','".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.login_retries (tries, minutes) VALUES ('0', '0');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.supp_groups (id, groupname) VALUES ('0', '[None]');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.template_settings (template, filename, div) VALUES ('statements', 'pdf/pdf-statement.php', '".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.template_settings (template, filename, div) VALUES ('invoices', 'invoice-print.php', '".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.template_settings (template, filename, div) VALUES ('reprints', 'new', '".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.workshop_settings (setting, value, div) VALUES ('workshop_conditions', 'As per display notice.', '".USER_DIV."');";
	db_exec($sql) or errDie("Unable to insert data.");

	$sql = "INSERT INTO cubit.set(type, label, value, descript, div) VALUES('Block main accounts', 'BLOCK', 'use', 'Block main accounts', '".USER_DIV."')";
	db_exec($sql) or errDie("Error setting up default setting.");

	$sql = "INSERT INTO exten.ct (days,div) VALUES ('0','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.ct (days,div) VALUES ('7','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.ct (days,div) VALUES ('14','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.ct (days,div) VALUES ('30','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.ct (days,div) VALUES ('60','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.ct (days,div) VALUES ('90','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.ct (days,div) VALUES ('120','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.od (days,div) VALUES ('0','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.od (days,div) VALUES ('7','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.od (days,div) VALUES ('14','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.od (days,div) VALUES ('30','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.od (days,div) VALUES ('60','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.od (days,div) VALUES ('90','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	$sql = "INSERT INTO exten.od (days,div) VALUES ('120','".USER_DIV."')";
	db_exec($sql) or errDie("Unable to insert default terms");

	if (is_readable("setup-ratios.php")) {
		include("setup-ratios.php");
	}

	if (!isset($inst_mode) OR strlen($inst_mode) < 1){
		$inst_mode = "hq";
	}

	#record the install type ...
	$sql = "
		INSERT INTO cubit.settings (
			constant, label, value, type, datatype, 
			minlen, maxlen, div, readonly
		) VALUES (
			'INST_MODE', 'Cubit Install Mode', '$inst_mode', 'company', 'allstring', 
			'1', '250', '0', 'f'
		);";
	db_exec($sql) or errDie ("Unable to insert install mode.");

    /* run the addon setups */
    foreach ($CUBIT_MODULES as $modulename) {
		if (is_readable("$modulename/setup-addon.php")) {
			include("$modulename/setup-addon.php");
		}
    }

	db_conn('core');

	block();

	pglib_transaction("COMMIT");

	$sets = "
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Setup Complete</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Cubit is ready to be used.</td>
					</tr>
				</table>
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $sets;

}



function printSet ()
{

	// Connect to database
	Db_Connect ();

	// Query server
	$sql = "SELECT * FROM set WHERE label = 'ACCNEW_LNK'";
	$rslt = db_exec ($sql) or errDie ("ERROR: Unable to view settings", SELF);          // Die with custom error if failed

	if (pg_numrows ($rslt) < 1) {
		$OUTPUT = "<li class='err'> No Setting currently in database.</li>";
	} else {
		$set = pg_fetch_array ($rslt);
		// Set up table to display in
		$OUTPUT = "
					<h3><li class='err'>Error</li></h3>
					<table ".TMPL_tblDflts." width='300'>
						<tr>
							<th>Setting Type</th>
							<th>Current Setting</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td colspan='2'>Cubit Account creation is already set to $set[descript], the quick setup cannot be used for this setting</td>
						</tr>
					</table>";
	}

	$OUTPUT .= "
				<p>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Quick Links</th>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
				</table>";
	return $OUTPUT;

}




function close_month($year,$month)
{

	if($month == 12){
		$nxprd = 1;
	}else{
		$nxprd = ($month + 1);
	}

	$month_names = array(0,
		"January",
		"February",
		"March",
		"April",
		"May",
		"June",
		"July",
		"August",
		"September",
		"October",
		"November",
		"December"
	);

	$periodname = getMonthName($month);

    // copy the trial balance to the new table
    core_connect();
	$sql = "INSERT INTO $year.$periodname (accid, topacc, accnum, accname, debit, credit, div)
			SELECT accid, topacc, accnum, accname, debit, credit, div FROM core.trial_bal WHERE month='$month'";
	db_exec($sql) or die($sql);

	$sql = "INSERT INTO \"$nxprd\".openbal (accid, accname, debit, credit, div)
			SELECT accid, accname, debit, credit, div FROM core.trial_bal WHERE month='$month'";
	db_exec($sql) or die($sql);

	$sql = "INSERT INTO \"$month\".ledger (acc, contra, edate, sdate, eref, descript, credit, debit, div,
				caccname, ctopacc, caccnum, cbalance, dbalance)
			SELECT accid, accid, CURRENT_DATE, CURRENT_DATE, '0', 'Balance', '0', '0', div, accname, topacc,
				accnum, credit, debit
			FROM core.trial_bal WHERE month='$month'";
	db_exec($sql) or die($sql);
	return true;

}



function linkacc($topacc, $accnum, $table, $name)
{

	$sql = "SELECT accid FROM core.accounts WHERE topacc='$topacc' AND accnum='$accnum' LIMIT 1";
	$rslt = db_exec($sql) or errDie("Error reading $table link: $name.");

	if (pg_num_rows($rslt) < 1) {
		errDie("Account link for $table ($name) not found.");
	}

	$accid = pg_fetch_result($rslt, 0);

	$sql = "INSERT INTO core.$table (name,accnum,div) VALUES ('$name', '$accid', '".USER_DIV."')";
	$rslt = db_exec($sql) or errDie("Unable to create $table link: $name.");

}


?>