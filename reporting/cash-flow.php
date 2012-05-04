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
require ("../core-settings.php");

//$OUTPUT = "
//<h3>Cash Flow Statement</h3>
//<li class='err'>For a cash flow use the <a href='bal-sheet.php'>Balance Sheet</a>.
//Remember to adjust for non-cash items in your Income Statement by adding these to
//\"Retained Income\" / \"Profit\" and deducting it from the relevant asset/liability
//account.</li>";

$OUTPUT = "
<h3>Cash Flow Statement</h3>
<li class='err'>Cash Flow is a report of cash movements that have taken place. It
differs from a cash budget in that a cash budget forcasts events to come as opposed
to what has occured. To report on your cash flow use the movements column on <a href='index-reports.php'>Cubit Financial Statements.</a><br>
Remember to adjust for non-cash items in your Income Statement by adding these to
\"Retained Income\" / \"Profit\" and deducting it from the relevant asset/liability
account.</li>";

require("../template.php");

if (isset($_POST["key"])) {
	// Convert the key to lowercase to maintain consistency
	$_POST["key"] = strtolower($_POST["key"]);
	switch ($_POST["key"]) {
		case "add":
		case "select account":
		case "remove selected":
		case "update":
			$OUTPUT = update($_POST);
			break;
		case "save":
		case "print":
		case "export to spreadsheet":
		case "display":
			$OUTPUT = display($_POST);
			break;
		default:
		case "slct":
			$OUTPUT = slct();
			break;
	}
} else {
	$OUTPUT = slct();
}

require ("../template.php");

function slct($errors = "") {
	global $_POST;
	extract($_POST);

	$fields = array ();
	$fields["category"] = 0;
	$fields["accid"] = 0;
	$fields["month_from"] = (int)date("m");
	$fields["month_to"] = (int)date("m");

	foreach ($fields as $var_name=>$value) {
		if (!isset($$var_name)) {
			$$var_name = $value;
		}
	}

	// Accounts dropdown
	db_conn("core");
	$sql = "SELECT * FROM accounts WHERE div='".USER_DIV."' ORDER BY accname ASC";
	$acc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts from Cubit.");

	$acc_sel = "<select name='accid' style='width: 270px'>";
	while ($acc_data = pg_fetch_array($acc_rslt)) {
		if ($acc_data["accid"] == $accid) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$acc_sel .= "<option value='$acc_data[accid]' $selected>$acc_data[accname]</option>";
	}
	$acc_sel .= "</select>";

	// Categories dropdown
	$cat_list = array (
		"nciis" => "Non cash items in Income Statement", // was cffoa
		"ciaal" => "Change in Assets and Liabilities",
		"cffuif" => "Cash Flows From / Used in Financing",
		"cffuii" => "Cash Flows From / Used in Investing"
	);

	$cat_sel = "<select name='category' style='width: 270px'>";
	foreach ($cat_list as $key=>$value) {
		if ($key == $category) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$cat_sel .= "<option value='$key' $selected>$value</option>";
	}
	$cat_sel .= "</select>";

	// Retrieve the accounts list from Cubit
	db_conn("cubit");
	$sql = "SELECT * FROM saved_cf_accounts";
	$cfacc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts list from Cubit.");

	if (pg_num_rows($cfacc_rslt)) {
		$i = 0;
		while ($cfacc_data = pg_fetch_array($cfacc_rslt)) {
			// Multidimensional array, variable variable
			${$cfacc_data["category"]}[] = $cfacc_data["accid"];
		}
	} else {
		// Create default accounts
		$_POST["key"] = "default";
		return update($_POST);
	}

	// Output the headings and accounts
	$acc_out = "";

	$i = 0;
	foreach ($cat_list as $key=>$value) {
		if (isset($$key)) {
			$acc_out .= "<tr><th colspan='5' class='cashflow_cats'>$value</th></tr>";

			foreach ($$key as $accid) {
				$i++;

				// Retrieve the account info from the trial balance
				db_conn("core");
				$sql = "SELECT * FROM trial_bal WHERE accid='$accid'";
				$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
				$acc_data = pg_fetch_array($acc_rslt);

				$acc_out .= "
				<input type='hidden' name='acc_${key}[]' value='$accid'>
				<tr class='".bg_class()."'>
					<td>$acc_data[accname]</td>
					<td align='center'><input type='checkbox' name='rem[$acc_data[accid]]' value='$acc_data[accid]'></td>
				</tr>";
			}
		}
	}

	// Period dropdown
	$month_frm_sel = finMonList("month_from", $month_from);
	$month_to_sel = finMonList("month_to", $month_to);

	// Layout
	$OUTPUT = "
	<h3>Statement of Cash Flow</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='display'>
	<table ".TMPL_tblDflts." style='width: 600px; margin: 0px'>
		<tr>
			<td>$errors</td>
		</tr>
		<tr>
			<th colspan='3'>Note</th>
		</tr>
		<tr class='bg-odd'>
			<td colspan='3'>
				Default accounts will be added if no accounts have
				been selected, or if ALL accounts have been removed.
			</td>
		</tr>
		<tr>
			<th colspan='3'>Range</th>
		</tr>
		<tr class='bg-odd'>
			<td colspan='3' align='center'>$month_frm_sel <b>TO</b> $month_to_sel</td>
		</tr>
		<tr>
			<th colspan='3'>Select Account to be Added to the List of Accounts
				Displayed on Cash Flow Statement</th>
		</tr>
		<tr>
			<th>Account Name</th>
			<th colspan='2'>Cash Flow Statement Section to Display Under</th>
		</tr>
		<tr class='bg-odd'>
			<td align='center'>$acc_sel</td>
			<td align='center'>$cat_sel</td>
			<td align='center'><input type='submit' name='key' value='Select Account'></td>
		</tr>
	</table>
	<table ".TMPL_tblDflts." style='width: 600px; margin: 0px'>
		<tr>
			<th colspan='4'>Accounts List</th>
		</tr>
			$acc_out
		<tr>
			<td colspan='4' align='center'>
				<table border='0' cellpadding='0' cellspacing='0'>
					<tr>
						<td>
							<input type='submit' name='key' value='Remove Selected'></td>
						</td>
						<td>
							<input type='submit' value='Display &raquo' style='font-weight: bold'>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	</form>"
	.mkQuickLinks(
		ql("../core/acc-new2.php", "Add New Journal Account"),
		ql("index-reports.php", "Financials"),
		ql("index-reports-stmnt.php", "Current Year Financial Statements")
	);

	return $OUTPUT;
}

function update($_POST)
{
	extract($_POST);

	// Validate
	require_lib("validate");
	$v = new validate;
	if (isset($accid) && isset($category)) {
		$v->isOk($accid, "num", 1, 9, "Invalid account selection.");
		$v->isOk($category, "string", 1, 6, "Invalid category selection.");
	}

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return slct($confirm);
	}

	$key = strtolower($key);

	if ($key == "add" || $key == "select account") {
		// Make sure the account has not been added already
		db_conn("cubit");
		$sql = "SELECT * FROM saved_cf_accounts WHERE accid='$accid'";
		$cfacc_rslt = db_exec($sql) or errDie("Unable to retrieve account from the account list.");

		if (!pg_num_rows($cfacc_rslt)) {
			db_conn("cubit");
			$sql = "INSERT INTO saved_cf_accounts (accid, category) VALUES ('$accid', '$category')";
			$cfacc_rslt = db_exec($sql) or errDie("Unable to save account to the accounts list.");
		}
	}

	if ($key == "remove selected") {
		if(!isset($rem))
			$rem = array();

		foreach ($rem as $id) {
			db_conn("cubit");
			$sql = "DELETE FROM saved_cf_accounts WHERE accid='$id'";
			$cfacc_rslt = db_exec($sql) or errDie("Unable to remove selected account from the accounts list.");
		}
	}

	if ($key == "default") {
		list($accid) = qryAccountsName("Depreciation", "accid");

		db_conn("cubit");
		$cols = grp(
			m("accid", $accid),
			m("category", "nciis")
		);
		$qry = new dbUpdate("saved_cf_accounts", "cubit", $cols);
		$qry->run(DB_INSERT);

		// add balance sheet items to list
		$qry = new dbQuery(DB_SQL,
			"INSERT INTO cubit.saved_cf_accounts (accid, category)
			SELECT accid, 'ciaal' FROM core.accounts WHERE catid='B10'");
		$qry->run();

		/*
		// Inventory
		db_conn("core");
		$sql = "SELECT accid FROM accounts WHERE accname='Inventory'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve the inventory account.");
		$accid = pg_fetch_result($rslt, 0);

		db_conn("cubit");
		$sql = "INSERT INTO saved_cf_accounts (accid, category) VALUES ('$accid', 'ciaal')";
		$rslt = db_exec($sql) or errDie("Unable to add the inventory account.");

		// Accounts Receivable
		db_conn("core");
		$sql = "SELECT accid FROM accounts WHERE accname='Customer Control Account'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve the accounts receivable account.");
		$accid = pg_fetch_result($rslt, 0);

		db_conn("cubit");
		$sql = "INSERT INTO saved_cf_accounts (accid, category) VALUES ('$accid', 'ciaal')";
		$rslt = db_exec($sql) or errDie("Unable to add the accounts receivable account.");
		*/
	}

	return slct();
}

function display($_POST)
{
	extract($_POST);

	$prev_yr_schema = substr(YR_DB, 0, 2) . (substr(YR_DB, 2)-1);
	if ($prev_yr_schema < 0) {
		return "<li class='err'>No prior years found.</li>";
	}

	/* "prior" should be from beginning of month (iow the end of the previous)
		or if it is the first period the end of period 0 (end of last year)  */
	global $MONPRD, $PRDMON;
	$month_from = $PRDMON[$MONPRD[$month_from] - 1];

	switch (strtolower($key)) {
		case "print":
		case "save":
		case "export to spreadsheet":
			$print_func = true;
			break;
		default:
			$print_func = false;
			break;
	}

	// Retrieve the accounts list from Cubit
	db_conn("cubit");
	$sql = "SELECT * FROM saved_cf_accounts";
	$cfacc_rslt = db_exec($sql) or errDie("Unable to retrieve accounts list from Cubit.");

	$i = 0;
	while ($cfacc_data = pg_fetch_array($cfacc_rslt)) {
		// Multidimensional array, variable variable
		${$cfacc_data["category"]}[] = $cfacc_data["accid"];
	}

	$cat_list = array (
		"nciis"=>"Non Cash Item in Income Statement",
		"ciaal"=>"Change in Assets and Liabilities",
		"cffuif"=>"Cash Flows From / Used in Financing",
		"cffuii"=>"Cash Flows From / Used in Investing"
	);

	$cat_total = array (
		"ciaal"=>"Net cash provided by operating activities",
		"cffuif"=>"Net cash provided by financing activities",
		"cffuii"=>"Net cash used in investing activities"
	);

	// Net cash and cash equivalents, beginning of period
	$cash_equiv_bop = array();
	$cash_equiv_bop["curr_total"] = 0;
	$cash_equiv_bop["prev_total"] = 0;
	$cash_equiv_bop["var_total"] = 0;
	$cash_equiv_bop["percvar_total"] = 0;
	foreach ($cat_list as $key=>$value) {
		if (($key == "cffuif" || $key == "cffuii") && isset(${"acc_$key"})) {
			foreach (${"acc_$key"} as $accid) {
				db_conn("core");
				$sql = "SELECT * FROM trial_bal_actual WHERE accid='$accid' AND month='$month_from'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve trial balance data from Cubit.");
				$py_data = pg_fetch_array($rslt);

				$sql = "SELECT * FROM trial_bal_actual WHERE accid='$accid' AND month='$month_to'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve trial balance data from Cubit.");
				$tb_data = pg_fetch_array($rslt);

				/*if ($prev_yr_schema) {
					db_conn($prev_yr_schema);
					$sql = "SELECT * FROM year_balance WHERE accid='$accid'";
					$rslt = db_exec($sql) or errDie("Unable to retrieve previous year data from Cubit.");
					$py_data = pg_fetch_array($rslt);
				} else {
					$py_data = array(
						"debit" => 0,
						"credit" => 0
					);
				}*/

				db_conn("core");
				$sql = "SELECT toptype FROM accounts WHERE accid='$accid'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve accounts from Cubit.");
				$toptype = pg_fetch_result($rslt, 0);

				$cash_equiv_bop["curr_total"] += calculate($toptype, $tb_data["debit"], $tb_data["credit"]);
				$cash_equiv_bop["prev_total"] += calculate($toptype, $py_data["debit"], $py_data["credit"]);
			}
		}
	}
	$cash_equiv_bop["var_total"] = $cash_equiv_bop["curr_total"] - $cash_equiv_bop["prev_total"];
	if ($cash_equiv_bop["curr_total"] && $cash_equiv_bop["prev_total"]) {
		$cash_equiv_bop["percvar_total"] = ($cash_equiv_bop["curr_total"] / $cash_equiv_bop["prev_total"]) * 100;
	} else {
		$cash_equiv_bop["percvar_total"] = 0;
	}

	// Net cash and cash equivalents, end of period
	$cash_equiv_eop = array();
	$cash_equiv_eop["curr_total"] = 0;
	$cash_equiv_eop["prev_total"] = 0;
	$cash_equiv_eop["var_total"] = 0;
	$cash_equiv_eop["percvar_total"] = 0;
	foreach ($cat_list as $key=>$value) {
		if (($key == "cffuif" || $key == "cffuii") && isset(${"acc_$key"})) {
			foreach (${"acc_$key"} as $accid) {
				db_conn("core");
				$sql = "SELECT * FROM trial_bal_actual WHERE accid='$accid' AND month='$month_from'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve trial balance data from Cubit.");
				$py_data = pg_fetch_array($rslt);

				$sql = "SELECT * FROM trial_bal_actual WHERE accid='$accid' AND month='$month_to'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve trial balance data from Cubit.");
				$tb_data = pg_fetch_array($rslt);

				/*$py_month = strtolower(getMonthName($month_to));
				db_conn($prev_yr_schema);
				$sql = "SELECT * FROM $py_month WHERE accid='$accid'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve previous year data from Cubit.");
				$py_data = pg_fetch_array($rslt);*/

				db_conn("core");
				$sql = "SELECT toptype FROM accounts WHERE accid='$accid'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve accounts from Cubit.");
				$toptype = pg_fetch_result($rslt, 0);

				$cash_equiv_eop["curr_total"] += calculate($toptype, $tb_data["debit"], $tb_data["credit"]);
				$cash_equiv_eop["prev_total"] += calculate($toptype, $py_data["debit"], $py_data["credit"]);
			}
		}
	}
	$cash_equiv_eop["var_total"] = $cash_equiv_eop["curr_total"] - $cash_equiv_eop["prev_total"];
	if ($cash_equiv_eop["curr_total"] && $cash_equiv_eop["prev_total"]) {
		$cash_equiv_eop["percvar_total"] = ($cash_equiv_eop["curr_total"] / $cash_equiv_eop["prev_total"]) * 100;
	} else {
		$cash_equiv_eop["percvar_total"] = 0;
	}

	// Output the headings and accounts
	$acc_out = "";

	if (!pg_num_rows($cfacc_rslt)) {
		$acc_out .= "<tr class='bg-odd'><td colspan='5'>No accounts selected.</td></tr>";
	}

	$i = 0;
	foreach ($cat_list as $key=>$value) {
		if (isset($$key)) {
			// Category heading
			$acc_out .= "<tr><th colspan='5' class='cashflow_cats'>$value</th></tr>";

			foreach (${"acc_$key"} as $accid) {
				// Retrieve the account info from the trial balance
				db_conn("core");
				$sql = "SELECT debit, credit FROM trial_bal WHERE accid='$accid' AND month='$month_from'";
				$tb_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
				$ptb_data = pg_fetch_array($tb_rslt);

				$sql = "SELECT debit, credit FROM trial_bal WHERE accid='$accid' AND month='$month_to'";
				$tb_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
				$tb_data = pg_fetch_array($tb_rslt);

				// Account information
				db_conn("core");
				$sql = "SELECT * FROM accounts WHERE accid='$accid'";
				$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
				$acc_data = pg_fetch_array($acc_rslt);

				// Retrieve previous year trial balance
				/*db_conn($prev_yr_schema);
				$sql = "SELECT * FROM year_balance WHERE accid='$accid'";
				$ptb_rslt = db_exec($sql) or errDie("Unable to retrieve previous year account information from Cubit.");
				$ptb_data = pg_fetch_array($ptb_rslt);

				// Previous year account information
				db_conn("core");
				$sql = "SELECT * FROM accounts WHERE accid='$accid'";
				$pacc_rslt = db_exec($sql) or errDie("Unable to retrieve previous year account information from Cubit.");
				$pacc_data = pg_fetch_array($pacc_rslt);*/

				// Make sure we've got a toptype
				if (empty($acc_data["toptype"])) {
					if ($acc_data["acctype"] == "I") $acc_data["toptype"] = "other_income";
					if ($acc_data["acctype"] == "E") $acc_data["toptype"] = "expenses";
				}
				/*if (empty($pacc_data["toptype"])) {
					if ($pacc_data["acctype"] == "I") $pacc_data["toptype"] = "other_income";
					if ($pacc_data["acctype"] == "E") $pacc_data["toptype"] = "expenses";
				}*/

				// Do the calculations
				$current = calculate($acc_data["toptype"], $tb_data["debit"], $tb_data["credit"]);
				$prior = calculate($acc_data["toptype"], $ptb_data["debit"], $ptb_data["credit"]);

				$variance = $current - $prior;
				// We don't want a division by zero...
				if ($current && $prior) {
					$percvar = ($current / $prior) * 100;
				} else {
					$percvar = "0.00";
				}

				// How should the current category be displayed
				switch ($key) {
					case "ciaal":
						if ($current <= $prior) {
							$decrease = "(decrease)";
							$increase = "Increase";
						} else {
							$decrease = "decrease";
							$increase = "(Increase)";
						}

						$acc_out .= "<tr class='bg-even'>
							<td>$increase $decrease in $acc_data[accname]</td>
							<td align='right'><b>".sprint($current)."</b></td>
							<td align='right'>".sprint($prior)."</td>
							<td align='right'>".sprint($variance)."</td>
							<td align='right'><b>".sprint($percvar)."</td>
						</tr>";
						break;
					case "nciis":
					case "cffuif":
					case "cffuii":
						$acc_out .= "<tr class='bg-even'>
							<td>$acc_data[accname]</td>
							<td align='right'><b>".sprint($current)."</b></td>
							<td align='right'>".sprint($prior)."</td>
							<td align='right'>".sprint($variance)."</td>
							<td align='right'><b>".sprint($percvar)."</td>
						</tr>";
				}

				// Create the total variables, unless they already exist
				if (!isset(${$key}["curr_total"])) ${$key}["curr_total"] = 0.00;
				if (!isset(${$key}["prev_total"])) ${$key}["prev_total"] = 0.00;
				if (!isset(${$key}["var_total"])) ${$key}["var_total"] = 0.00;
				if (!isset(${$key}["percvar_total"])) ${$key}["percvar_total"] = 0.00;

				// Assign values to the totals
				if (isset($$key)) {
					${$key}["curr_total"] += $current;
					${$key}["prev_total"] += $prior;
					${$key}["var_total"] += $variance;
					${$key}["percvar_total"] += $percvar;
				}

			}

			if ($key == "ciaal") {
				$acc_out .= "<tr class='bg-odd'>
					<td>Total adjustments</td>
					<td align='right'><b>".sprint(${$key}["curr_total"])."</b></td>
					<td align='right'>".sprint(${$key}["prev_total"])."</td>
					<td align='right'>".sprint(${$key}["var_total"])."</td>
					<td align='right'><b>".sprint(${$key}["percvar_total"])."</b></td>
				</tr>";
			}

			// nciis and ciaal are both in the same category, dont assign a total to both
			// instead just use nciis
			if ($key != "nciis") {
				if (isset($nciis) && isset($ciaal)) {
					$ciaal["curr_total"] += $nciis["curr_total"];
					$ciaal["prev_total"] += $nciis["prev_total"];
					$ciaal["var_total"] += $nciis["var_total"];
					$ciaal["percvar_total"] += $nciis["percvar_total"];
				}

				// Totals output
				$acc_out .= "<tr class='bg-odd'>
					<td>$cat_total[$key]</td>
					<td align='right'><b>".sprint(${$key}["curr_total"])."</b></td>
					<td align='right'>".sprint(${$key}["prev_total"])."</td>
					<td align='right'>".sprint(${$key}["var_total"])."</td>
					<td align='right'><b>".sprint(${$key}["percvar_total"])."</b></td>
				</tr>";
			} else {
				// Totals output
				$acc_out .= "<tr class='bg-odd'>
					<td>&nbsp</td>
					<td align='right'><b>".sprint(${$key}["curr_total"])."</b></td>
					<td align='right'>".sprint(${$key}["prev_total"])."</td>
					<td align='right'>".sprint(${$key}["var_total"])."</td>
					<td align='right'><b>".sprint(${$key}["percvar_total"])."</b></td>
				</tr>";
			}
		}
	}

	// Net cash and cash equivalents
	if (!isset($cffuif)) {
		$cffuif = array();
		$cffuif["curr_total"] = 0.00;
		$cffuif["prev_total"] = 0.00;
		$cffuif["var_total"] = 0.00;
		$cffuif["percvar_total"] = 0.00;
	}

	if (!isset($cffuii)) {
		$cffuii = array();
		$cffuii["curr_total"] = 0.00;
		$cffuii["prev_total"] = 0.00;
		$cffuii["var_total"] = 0.00;
		$cffuii["percvar_total"] = 0.00;
	}

	$cash_equiv = array();
	$cash_equiv["curr_total"] = $cffuif["curr_total"] + $cffuii["curr_total"] + $nciis["curr_total"];
	$cash_equiv["prev_total"] = $cffuif["prev_total"] + $cffuii["prev_total"] + $nciis["prev_total"];
	$cash_equiv["var_total"] = $cffuif["var_total"] + $cffuii["var_total"] + $nciis["var_total"];
	$cash_equiv["percvar_total"] = $cffuif["percvar_total"] + $cffuii["percvar_total"] + $nciis["percvar_total"];

	if ($cash_equiv["curr_total"] < $cash_equiv["prev_total"]) {
		$cash_equiv["inc_dec"] = "(increase) decrease";
	} else {
		$cash_equiv["inc_dec"] = "increase (decrease)";
	}


	// Date range
	// Retrieve the current year from Cubit
	db_conn("core");
	$sql = "SELECT yrname FROM active";
	$rslt = db_exec($sql) or errDie("Unable to retrieve current year from Cubit.");

	$year_out = substr(pg_fetch_result($rslt, 0), 1);
	$month_from_out = getMonthName($month_from);
	$month_to_out = getMonthName($month_to);

	if ($month_from == $month_to) {
		$date_range = "$month_from_out $year_out";
	} else {
		$date_range = "$month_from_out TO $month_to_out $year_out";
	}

	$OUTPUT = "";

	if (!$print_func) {
		$OUTPUT .= "
		<form method='post' action='".SELF."'>
		<input type='hidden' name='key' value='slct'>
		<input type='hidden' name='month_from' value='$month_from'>
		<input type='hidden' name='month_to' value='$month_to'>";
	}

	$OUTPUT .= "
	<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
		<tr>
			<td><h3>Statement of Cash Flow</h3></td>
			<td align='right'><h3>$date_range</h3></td>
		</tr>
		<tr>
			<td colspan='2' class='err'>
			The non-cash items have been adjusted to operating profit. The other
			leg must still be debited or credited to an asset/liability account.
			</td>
		</tr>
	</table>
	<table border='0' cellpadding='0' cellspacing='0' width='100%'>
		<tr>
			<th>&nbsp;</td>
			<th width='10%' class='thkborder'>Current</th>
			<th width='10%' class='thkborder'>Prior</th>
			<th width='10%' class='thkborder'>Variance</th>
			<th width='10%' class='thkborder thkborder_right'>%Var</th>
		</tr>
		$acc_out
		<tr class='bg-odd'>
			<td>Net cash $cash_equiv[inc_dec] in cash and cash equivalents</td>
			<td align='right'><b>".sprint($cash_equiv["curr_total"])."</b></td>
			<td align='right'>".sprint($cash_equiv["prev_total"])."</td>
			<td align='right'>".sprint($cash_equiv["var_total"])."</td>
			<td align='right'><b>".sprint($cash_equiv["percvar_total"])."</b></td>
		</tr>
		<tr class='bg-odd'>
			<td>Net cash equivalents beginning of period</td>
			<td align='right'><b>".sprint($cash_equiv_bop["curr_total"])."</b></td>
			<td align='right'>".sprint($cash_equiv_bop["prev_total"])."</td>
			<td align='right'>".sprint($cash_equiv_bop["var_total"])."</td>
			<td align='right'>".sprint($cash_equiv_bop["percvar_total"])."</td>
		</tr>
		<tr class='bg-odd'>
			<td>Net cash equivalents end of period</td>
			<td align='right'><b>".sprint($cash_equiv_eop["curr_total"])."</b></td>
			<td align='right'>".sprint($cash_equiv_eop["prev_total"])."</td>
			<td align='right'>".sprint($cash_equiv_eop["var_total"])."</td>
			<td align='right'>".sprint($cash_equiv_eop["percvar_total"])."</td>
		</tr>";

	if (!$print_func) {
		$pf = "";
		foreach ($cat_list as $key=>$value) {
			if (!isset(${"acc_$key"})) continue;
			foreach (${"acc_$key"} as $k => $v) {
				$pf .= "<input type='hidden' name='acc_$key"."[$k]' value='$v' />";
			}
		}

		$OUTPUT .= "
		$pf
		<tr>
			<td colspan='5' align='center'>
				<input type='submit' value='Accounts'>
				<input type='submit' name='key' value='Save'>
				<input type='submit' name='key' value='Print'>
				<input type='submit' name='key' value='Export to Spreadsheet'>
			</td>
		</tr>
		</table>
		</form>";
	} else {
		$OUTPUT .= "
		</table>";
	}

	if ($print_func) {
		$OUTPUT = clean_html($OUTPUT);
		switch (strtolower($_POST["key"])) {
			case "print":
				require ("../tmpl-print.php");
				break;
			case "save":
				db_conn("core");
				$sql = "INSERT INTO save_cashflow (output, div) VALUES ('".base64_encode($OUTPUT)."', '".USER_DIV."')";
				$svincRslt = db_exec($sql) or errDie("Unable to save the balance sheet to Cubit.");

				return "<li>Cash Flow Statement has been successfully saved to Cubit.</li>";
				break;
			case "export to spreadsheet":
				require ("../xls/temp.xls.php");
				Stream ("cashflow" , $OUTPUT);
				break;
		}
	} else {
		$OUTPUT .= "
		<p>
		<center>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
			<tr><th>Quick Links</th></tr>
			<tr class='datacell'><td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
			<tr class='datacell'><td align='center'><a href='index-reports.php'>Financials</a></td></tr>
			<tr class='datacell'><td align='center'><a href='index-reports-stmnt.php'>Current Year Financial Statements</a></td></tr>
			<tr class='datacell'><td align='center'><a href='../main.php'>Main Menu</td></tr>
		</table>
		</center>";
	}

	return $OUTPUT;
}

function calculate($toptype, $debit, $credit)
{
	switch ($toptype) {
		case "expenses":
		case "fixed_asset":
		case "investments":
		case "other_fixed_asset":
		case "current_asset":
			return $debit - $credit;
			break;
		case "other_income":
		case "cost_of_sales":
		case "sales":
		case "share_capital":
		case "retained_income":
		case "shareholders_loan":
		case "non_current_liability":
		case "long_term_borrowing":
		case "other_long_term_liability":
		case "current_liability":
			return $credit - $debit;
			break;
	}
}

function accType($accid) {
	$sql = "SELECT acctype FROM core.accounts WHERE accid='$accid'";
	$rslt = db_exec($sql) or errDie("Error reading account type. ACC: $accid (ATR)");

	return pg_fetch_result($rslt, 0, 0);
}
?>
