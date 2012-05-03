<?php

require ("settings.php");

$OUTPUT = display();

require ("template.php");

function display()
{
	extract($_REQUEST);

	$reports = array(
		"acid_test",
		"customers_turnover",
		"daily_sales_in_receivables",
		"inventory_turnover",
		"days_cost_of_sales_in_inventory",
		"accounts_receivable_turnover",
		"accounts_payable_turnover",
		"days_cost_of_sales_in_accounts_payable",
		"assets_turnover",
		"debt_ratio",
		"depreciation_expense_gross_depreciable_property",
		"current_ratio",
		"working_captial",
		"accounts_receivable_working_capital",
		"inventory_working_capital",
		"long_term_liabilities_working_capital",
		"sales_working_capital",
		"gross_profit_turnover",
		"net_profit_turnover",
		"return_on_assets",
		"return_on_investment",
		"repairs_and_maintenance_gross_depreciation_of_property",
		"owners_equity",
		"debt_equity",
		"financial_leverage",
		"interest_net_income_excluding_interest",
		"operating_cycle_days"
	);

	$not_percentage = array(
		"daily_sales_in_receivables",
		"days_cost_of_sales_in_inventory",
		"days_cost_of_sales_in_accounts_payable",
		"working_capital",
		"operating_cycle_days"
	);

	$i = 1;
	$reports_out = "";
	foreach ($reports as $name) {
		if ($i == 1) {
			$reports_out .= "<tr><td width='20%' valign='top'>";
		} else {
			$reports_out .= "<td width='20%' valign='top'>";
		}

		if (function_exists($name)) {
			list($description, $calculation) = call_user_func($name);
		} else {
			$description = "Not implemented.";
		}

		$calculation = sprint($calculation);

		if (!in_array($name, $not_percentage)) {
			$calculation .= "%";
		}

		$reports_out .= "
		<table ".TMPL_tblDflts." width='100%' style='border: 1px solid #fff'>
			<tr><th>".nice_name($name)."</th></tr>
			<tr class='".bg_class()."'>
				<td>
					<span style='font-size: 0.9em; letter-spacing: -0.065em'>
						$description
					</span>
				</td>
			</tr>
			<tr bgcolor='#ffffff'>
				<td align='center'>
					<span style='font-size: 1.2em; color: #f00; font-weight:bold'>
						$calculation
					</span>
				</td>
			</tr>
		</table>";

		if ($i == 5) {
			$reports_out .= "</td></tr>";
			$i = 0;
		} else {
			$reports_out .= "</td>";
		}

		$i++;
	}

	if (!isset($heart)) {
		$sql = "SELECT value FROM cubit.settings WHERE constant='HEART'";
		$heart_rslt = db_exec($sql) or errDie("Unable to retrieve heart display value.");
		$heart = pg_fetch_result($heart_rslt, 0);
	} else {
		$sql = "UPDATE cubit.settings SET value='$heart' WHERE constant='HEART'";
		db_exec($sql) or errDie("Unable to update heart status");
	}

	if ($heart) {
		$heart_yes = "checked";
		$heart_no = "";
	} else {
		$heart_yes = "";
		$heart_no = "checked";
	}

	$bgcolor = bgcolorg();

	$ql = mkQuickLinks(
		ql("ratio_settings.php", "Link Accounts to Ratios")
	);

	$OUTPUT = "
	<table cellpadding='5' cellspacing='2'>$reports_out</table>
	<center>
	<form method='post' action='".SELF."' name='form'>
	<table ".TMPL_tblDflts.">
		<tr><th colspan='2'>Display Heart on Main Menu</th></tr>
		<tr>
			<td bgcolor='$bgcolor' align='center'>
				Yes <input type='radio' name='heart' value='1'
				onchange='javascript:document.form.submit()' $heart_yes />
			</td>
			<td bgcolor='$bgcolor' align='center'>
				No <input type='radio' name='heart' value='0'
				onchange='javascript:document.form.submit()' $heart_no />
			</td>
		</tr>
		<tr><td colspan='2' align='center'>$ql</td></tr>
	</table>
	</form>
	</center>";

	return $OUTPUT;
}

function nice_name($name) {
	// Convert underscores to spaces
	$name = preg_replace("/_/", " ", $name);

	// Capitalise Words
	$name = ucwords($name);

	return $name;
}

function ratio_total($ratio_type)
{
	$sql = "SELECT accname
			FROM cubit.ratio_account_owners
				LEFT JOIN ratio_account_types
					ON ratio_account_owners.type_id=ratio_account_types.id
				LEFT JOIN core.accounts
					ON ratio_account_owners.accid=accounts.accid
			WHERE rtype='$ratio_type'";
	$acc_rslt = db_exec($sql);

	$ratio_total = 0;
	while (list($accname) = pg_fetch_array($acc_rslt)) {
		$ratio_total += acc_total($accname);
	}

	return $ratio_total;
}

function acc_total($accname)
{
	$accid = qryAccountsName($accname);
	$accid = $accid["accid"];

	list($left_op, $right_op) = acc_calc($accid);

	$sql = "SELECT (sum($left_op) - sum($right_op)) AS total
			FROM core.trial_bal
			WHERE accid='$accid' AND month='".date("m")."'";

	$total_rslt = db_exec($sql) or errDie("Unable to retrieve account total.");
	$total = (float)pg_fetch_result($total_rslt, 0);

	return $total;
}

function acc_calc($accid)
{
	$sql = "SELECT toptype, acctype FROM core.accounts WHERE accid='$accid'";
	$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account types.");
	list($toptype, $acctype) = pg_fetch_array($acc_rslt);

	if (empty($toptype)) {
		if ($acctype == "I") $toptype = "other_income";
		if ($acctype == "E") $toptype = "expenses";
	}

	$assets = array (
		"fixed_asset",
		"investments",
		"other_fixed_asset",
		"current_asset",
		"other_income",
		"sales"
	);
	$equity = array (
		"share_capital",
		"retained_income",
		"shareholders_loan",
		"non_current_liability",
		"long_term_borrowing",
		"other_long_term_liability",
		"current_liability",
		"expenses",
		"cost_of_sales",
		"tax"
	);

	if (in_array($toptype, $assets)) {
		$left_op = "debit";
		$right_op = "credit";
	} elseif (in_array($toptype, $equity)) {
		$left_op = "credit";
		$right_op = "debit";
	}

	return array($left_op, $right_op);
}

function accounts_payable()
{
	$sql = "SELECT DISTINCT supid FROM \"".PRD_DB."\".suppledger";
	$sup_rslt = db_exec($sql) or errDie("Unable to retrieve creditors.");

	$total_balance = 0;
	while (list($supid) = pg_fetch_array($sup_rslt)) {
		$sql = "SELECT max(id) FROM \"".PRD_DB."\".suppledger
				WHERE supid='$supid'";
		$led_rslt = db_exec($sql) or errDie("Unable to retrieve ledger entry.");
		$led_id = pg_fetch_result($led_rslt, 0);

		$sql = "SELECT cbalance, dbalance
				FROM \"".PRD_DB."\".suppledger WHERE id='$led_id'";
		$bal_rslt = db_exec($sql) or errDie("Unable to retrieve balances.");

		list($cbalance, $dbalance) = pg_fetch_array($bal_rslt);

		$total_balance += $cbalance - $dbalance;
	}

	return $total_balance;
}

function accounts_receivable()
{
	$sql = "SELECT DISTINCT cusnum FROM \"".PRD_DB."\".custledger";
	$cus_rslt = db_exec($sql) or errDie("Unable to retrieve debtors.");

	$total_balance = 0;
	while (list($cusnum) = pg_fetch_array($cus_rslt)) {
		$sql = "SELECT max(id) FROM \"".PRD_DB."\".custledger
				WHERE cusnum='$cusnum'";
		$led_rslt = db_exec($sql) or errDie("Unable to retrieve ledger entry.");
		$led_id = pg_fetch_result($led_rslt, 0);

		$sql = "SELECT dbalance, cbalance
				FROM \"".PRD_DB."\".custledger WHERE id='$led_id'";
		$bal_rslt = db_exec($sql) or errDie("Unable to retrieve balances.");

		list($dbalance, $cbalance) = pg_fetch_array($bal_rslt);

		$total_balance += $dbalance - $cbalance;
	}

	return $total_balance;
}

function total_liabilities()
{
	$ratios = array(
		"current_liability"
	);

	$total_liabilities = 0;
	foreach ($ratios as $ratio) {
		$total_liabilities += ratio_total($ratio);
	}

	return $total_liabilities;
}

function total_assets()
{
	$ratios = array(
		"current_asset",
		"fixed_asset"
	);

	$total_assets = 0;
	foreach ($ratios as $ratio) {
		$total_assets += ratio_total($ratio);
	}
}

function net_profit()
{
	$sql = "SELECT debit, credit, accounts.accid, accounts.acctype,
				accounts.toptype
			FROM core.trial_bal
				LEFT JOIN core.accounts ON trial_bal.accid=accounts.accid
			WHERE (accounts.acctype='I' OR accounts.acctype='E')
				AND month='".date("m")."'";
	$tb_rslt = db_exec($sql) or errDie("Unable to retrieve trial balance");

	$total_income = 0;
	$total_expense = 0;

	while ($tb_data = pg_fetch_array($tb_rslt)) {
		list($left_op, $right_op) = acc_calc($tb_data["accid"]);

		$balance = $tb_data[$left_op] - $tb_data[$right_op];

		if ($acctype = "I") {
			$total_income += $balance;
		}else {
			$total_expense += $balance;
		}
	}

	return $total_income - $total_expense;
}

// Report Blocks --------------------------------------------------------------

function customers_turnover()
{
	$description = "This ratio determines the number of times that customers ";
	$description.= "turn over during a period";

	$credit = ratio_total("credit");
	$accounts_receivable = accounts_receivable();

	if ($credit && $accounts_receivable) {
		$calculation = ($credit / $accounts_receivable) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function daily_sales_in_receivables()
{
	$description = "Measures the average length of time receivables are ";
	$description.= "outstanding. Helps determine if potential collection ";
	$description.= "problems exist.";

	$accounts_receivable = accounts_receivable();
	$turnover = ratio_total("turnover");

	if ($accounts_receivable && $turnover) {
		$calculation = 365 * ($accounts_receivable / $turnover);
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function inventory_turnover()
{
	$description = "Measures the number of times inventory turns over during ";
	$description.= "the year. High turnover can indicate better liquidity or ";
	$description.= "superior merchandising. Conversely, it can indicate a ";
	$description.= "shortage of needed inventory for the level of sales. Low ";
	$description.= "turnover can indicate poor liquidity, overstocking or ";
	$description.= "obsolescence.";

	$cost_of_sales = ratio_total("cost_of_sales");
	$inventory = ratio_total("inventory");

	if ($cost_of_sales && $inventory) {
		$calculation = ($cost_of_sales / $inventory) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function days_cost_of_sales_in_inventory()
{
	$description = "Measures the average length of time units are in ";
	$description.= "inventory. Helps determine if inadequate or excess ";
	$description.= "inventory levels exist.";

	$cost_of_sales = ratio_total("cost_of_sales");
	$inventory = ratio_total("inventory");

	if ($cost_of_sales && $inventory) {
		$calculation = 365 * ($inventory / $cost_of_sales);
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function accounts_payable_turnover()
{
	$description = "The ratio determines the number of times that accounts ";
	$description.= "payable are turned over during a period";

	$cost_of_sales = ratio_total("cost_of_sales");
	$accounts_payable = accounts_payable();

	if ($cost_of_sales && $accounts_payable) {
		$calculation = ($cost_of_sales / $accounts_payable) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function days_cost_of_sales_in_accounts_payable()
{
	$description = "The ratio determines the average period that suppliers are ";
	$description.= "outstanding. It indicates payment of creditors efficiency.";

	$accounts_payable = accounts_payable();
	$cost_of_sales = ratio_total("cost_of_sales");

	if ($accounts_payable && $cost_of_sales) {
		$calculation = 365 * ($accounts_payable / $cost_of_sales);
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function assets_turnover()
{
	$description = "The ratio determines the effectiveness with which assets ";
	$description.= "are employed to generate business. a High ratio normally ";
	$description.= "indicates efficient use of assets";

	$total_sales = ratio_total("turnover");
	$total_assets = total_assets();

	if ($total_sales && $total_assets) {
		$calculation = ($total_sales / $total_assets) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function repairs_and_maintenance_gross_depreciation_of_property()
{
	$description = "The ratio affects the relationship between maintenance ";
	$description.= "cost and gross value of property, which might consist of ";
	$description.= "fixed property, plant or equipment. a Higher ratio normally";
	$description.= "indicates a need to replace assets.";

	$repairs_and_maintenance = ratio_total("repairs_and_maintenance");
	$depreciation_of_property = ratio_total("gross_depreciation_of_property");

	if ($repairs_and_maintenance && $depreciation_of_property) {
		$calculation = $repairs_and_maintenance / $depreciation_of_property;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function depreciation_expense_gross_depreciable_property()
{
	$description = "The ratio determines the rate at which productive assets ";
	$description.= "are written off.";

	$depreciation_expense = ratio_total("depreciation_expense");
	$gross_depreciable_property = ratio_total("gross_depreciable_property");

	if ($depreciation_expense && $gross_depreciable_property) {
		$calculation = $depreciation_expense / $gross_depreciable_property;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function current_ratio()
{
	$description = "This ratio determines an entity's liquidity and the ";
	$description.= "ability to meet current liabilities. It indicates the ";
	$description.= "entity's ability pay current liabilities out of current ";
	$description.= "assets";

	$current_assets = ratio_total("current_assets");
	$current_liabilities = ratio_total("current_liabilities");

	if ($current_assets && $current_liabilities) {
		$calculation = ($current_assets / $current_liabilities) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function working_captial()
{
	$description = "This ratio indicates the general illiquidity of the entity ";
	$description.= "concerned. It measures the excess or shortfall of current ";
	$description.= "assets over current liabilities at a given point in time ";
	$description.= "during a period.";

	$current_assets = ratio_total("current_assets");
	$current_liabilities = ratio_total("current_liabilities");

	$calculation = $current_assets - $current_liabilities;

	return array($description, $calculation);
}

function acid_test()
{
	$description = "The acid test measures to what extent customers' ";
	$description.= "outstandings are sufficient to meet suppliers' liabilities.";

	$sql = "SELECT sum(balance) AS cust_bal FROM cubit.customers";
	$cbal_rslt = db_exec($sql) or errDie("Unable to retrieve customer balance");
	$cus_bal = pg_fetch_result($cbal_rslt, 0);

	$sql = "SELECT sum(balance) AS supp_bal FROM cubit.suppliers";
	$sbal_rslt = db_exec($sql) or errDie("Unable to retrieve supplier balance");
	$sup_bal = pg_fetch_result($sbal_rslt, 0);

	if ($cus_bal != 0.00 && $sup_bal != 0.00) {
		$calculation = ($cus_bal / $sup_bal) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function accounts_receivable_working_capital()
{
	$description = "Measures the dependency of working capital on the ";
	$description.= "collection of receivables.";

	$accounts_receivable = accounts_receivable();
	$working_capital = ratio_total("working_capital");

	if ($accounts_receivable && $working_capital) {
		$calculation = ($accounts_receivable / $working_capital) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function inventory_working_capital()
{
	$description = "The ratio indicates the component of inventory in working ";
	$description.= "capital.";

	$inventory = ratio_total("inventory");
	$working_capital = ratio_total("working_capital");

	if ($inventory && $working_capital) {
		$calculation = ($inventory / $working_capital) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function long_term_liabilities_working_capital()
{
	$description = "This erasure measures the adequacy of working capital. A ";
	$description.= "high pressure normally indicates that the entity requires ";
	$description.= "equity funding.";

	$long_term_liabilities = ratio_total("long_term_liabilities");
	$working_capital = ratio_total("working_capital");

	if ($long_term_liabilities && $working_capital) {
		$calculation = ($long_term_liabilities / $working_capital) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function sales_working_capital()
{
	$description = "This ratio indicates the dependency of working capital on ";
	$description.= "sales";

	$total_sales = ratio_total("turnover");
	$working_capital = ratio_total("working_capital");

	if ($total_sales && $working_capital) {
		$calculation = ($total_sales / $working_capital);
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function gross_profit_turnover()
{
	$description = "This ratio indicates the portion of sales available to ";
	$description.= "cover operating expenses";

	$gross_profit = ratio_total("turnover") - ratio_total("cost_of_sales");
	$turnover = ratio_total("turnover");

	if ($gross_profit && $turnover) {
		$calculation = ($gross_profit / $turnover) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function net_profit_turnover()
{
	$description = "The ratio indicates the portion of sales that represent ";
	$description.= "net income before tax";

	$net_profit = net_profit();
	$turnover = ratio_total("turnover");

	if ($net_profit && $turnover) {
		$calculation = $net_profit / $turnover;
	} else {
		$calculation = "";
	}

	return array($description, $calculation);
}

function return_on_assets()
{
	$description = "The ratio measures the return on the investment in ";
	$description.= "productive assets";

	$net_income = net_profit();
	$total_assets = total_assets();

	if ($net_income && $total_assets) {
		$calculation = ($net_income / $total_assets) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function return_on_investment()
{
	$description = "Measures each sales currency's benefits to owners.";

	$net_income = net_profit();
	$owners_equity = ratio_total("owners_equity");

	if ($net_income && $owners_equity) {
		$calculation = ($net_income / $owners_equity) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function debt_ratio()
{
	$description = "The debt ratio indicates the amount of assets that are ";
	$description.= "provided by the entity's creditors";

	$total_liabilities = total_liabilities();
	$total_assets = total_assets();

	if ($total_liabilities && $total_assets) {
		$calculation = ($total_liabilities / $total_assets) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function owners_equity()
{
	$description = "The ratio measures the assets financed by the entity's owner";

	$total_capital = ratio_total("owners_equity");
	$total_assets = total_assets();

	if ($total_capital && $total_assets) {
		$calculation = ($total_capital / $total_assets) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function debt_equity()
{
	$description = "Expresses the relationship between capital contributes by ";
	$description.= "owners. May indicate a company's borrowing capability and ";
	$description.= "under or over capitalization.";

	$total_liabilities = total_liabilities();
	$owners_equity = ratio_total("owners_equity");

	if ($total_liabilities && $owners_equity) {
		$calculation = ($total_liabilities / $owners_equity) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function financial_leverage()
{
	$description = "The ratio indicates the extent to which the owners has ";
	$description.= "leveraged their investment. It can be seen as an ";
	$description.= "explanation for the difference between a return on assets ";
	$description.= "ratio and a return on equity ratio.";

	$total_assets = total_assets();
	$owners_equity = ratio_total("owners_equity");

	if ($total_assets && $owners_equity) {
		$calculation = ($total_assets / $owners_equity) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function interest_net_income_excluding_interest()
{
	$description = "Measures the operating profit of a company before the ";
	$description.= "effects of financing costs and income taxes. This a ";
	$description.= "meaningful indicator which has a comparability with other ";
	$description.= "companies because it excludes the effects of capitalization ";
	$description.= "(i.e. debt and equity financing).";

	$interest_expense = ratio_total("interest_expense");
	$net_income = net_profit();

	if ($interest_expense && $net_income) {
		$calculation = ($interest_expense / ($net_income + $interest_expense)) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function accounts_receivable_turnover()
{
	$description = "Measures the number of times trade receivables turn over ";
	$description.= "during the year. The higher the turnover, the shorter ";
	$description.= "the time between sale and cash collection.";

	$credit = ratio_total("credit");
	$accounts_receivable = accounts_receivable();

	if ($credit && $accounts_receivable) {
		$calculation = ($credit / $accounts_receivable) * 100;
	} else {
		$calculation = 0;
	}

	return array($description, $calculation);
}

function operating_cycle_days()
{
	$description = "Measures the time it takes to convert products and ";
	$description.= "services into cash. An unfavorable trend maybe a leading ";
	$description.= "indicator of future cash flow problems.";

	$days_sales_in_receivables = accounts_receivable();
	$days_cost_of_sales_in_inventory = ratio_total("inventory");
	
	list($none, $days_sales_in_receivables) = daily_sales_in_receivables();
	list($none, $days_cost_of_sales_in_inventory) = days_cost_of_sales_in_inventory();

	$calculation = $days_sales_in_receivables + $days_cost_of_sales_in_inventory;

	return array($description, $calculation);
}

?>
