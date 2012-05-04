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
require("finstatements.php");

foreach ($_GET as $key=>$value) {
	$_POST[$key] = $value;
}

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		default:
		case "display":
			$OUTPUT = financialStatements::balsheet($_POST);
			break;
		case ct("Print"):
		case ct("Save"):
		case ct("Export to Spreadsheet"):
			$OUTPUT = print_sheet($_POST);
			break;
		case "customize":
			$OUTPUT = customize($_POST);
			break;
		case "add":
		case "remove selected":
		case "update":
			$OUTPUT = update($_POST);
			break;
		case "note_view":
			$OUTPUT = note_view($_POST);
			break;
		case "note_save":
			$OUTPUT = note_save($_POST);
			break;
	}
} else {
	$OUTPUT = customize($_POST);
	//$OUTPUT = financialStatements::balsheet($_POST);
}

require ("../template.php");




function print_sheet() {
	$OUTPUT = clean_html(financialStatements::balsheet($_POST));

	switch ($_POST["key"]) {
		case ct("Print"):
			require ("../tmpl-print.php");
			break;
		case ct("Save"):
			db_conn("core");
			$sql = "INSERT INTO save_bal_sheet (output, gendate, div) VALUES ('".base64_encode($OUTPUT)."', current_date, '".USER_DIV."')";
			$svincRslt = db_exec($sql) or errDie("Unable to save the balance sheet to Cubit.");

			return "
				<li>Balance Sheet has been successfully saved to Cubit.</li>
				<table ".TMPL_tblDflts." width='25%'>
					<tr><th>Quick Links</th></tr>
					<tr class='datacell'><td align='center'><a target='_blank' href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
					<tr class='datacell'><td align='center'><a href='index-reports.php'>Financials</a></td></tr>
					<tr class='datacell'><td align='center'><a href='index-reports-stmnt.php'>Current Year Financial Statements</a></td></tr>
					<tr class='datacell'><td align='center'><a href='../main.php'>Main Menu</td></tr>
				</table>";
			break;
		case ct("Export to Spreadsheet"):
			require_lib("xls");
			StreamXLS("balance_sheet" , $OUTPUT);
			break;
	}

}




function customize($_POST)
{

	extract ($_POST);

	$fields = array();
	$fields["account"] = 0;
	$fields["heading_1"] = COMP_NAME;
	$fields["heading_2"] = date("d/m/Y");
	$fields["heading_3"] = "Balance Sheet";
	$fields["heading_4"] = "Prepared by: ".USER_NAME;
	$fields["viewyear"] = "core";
	$fields["month_from"] = date("m");
	$fields["month_to"] = date("m");
	$fields["capital_employed_out"] = "Capital Employed";
	$fields["employment_of_capital_out"] = "Employment of Capital";
	$fields["period"] = substr(PRD_DB, 2);
	$fields["month"] = date("m");
	$fields["zero_balance"] = "";

	foreach ($fields as $var_name=>$value) {
		if (!isset($$var_name)) {
			$$var_name = $value;
		}
	}

	$qry = new dbSelect("year", "core", grp(
		m("where", "closed='y'"),
		m("order", "yrname ASC")
	));
	$qry->run();

	if (PRD_STATE == "py") {
		$curyear = PYR_NAME;
	} else {
		$curyear = YR_NAME;
	}

	$year_sel = "
	<select name='viewyear'>
		<option value='core'>".$curyear." (Current)</option>";

	while ($row = $qry->fetch_array()) {
		$sel = fsel($viewyear == $row["yrdb"]);

		$year_sel .= "<option $sel value='$row[yrdb]'>$row[yrname]</option>";
	}

	$year_sel .= "
	</select>";

	// Retrieve list of accounts for the account dropdown
	db_conn("core");
	$sql = "SELECT * FROM accounts WHERE acctype='B' ORDER BY accname ASC";
	$acc_rslt = db_exec($sql)
		or errDie("Unable to retrieve accounts information from Cubit.");

	// Accounts dropdown output
	$acc_sel = "<select name='account' style='width: 200px'>
		<option value='0'>Please select</option>";
	while ($acc_data = pg_fetch_array($acc_rslt)) {
		if ($account == $acc_data["accid"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$acc_sel .= "<option value='$acc_data[accid]' $selected>$acc_data[accname]</option>";
	}
	$acc_sel .= "</select>";

	// Accounts list
	db_conn("cubit");
	$sql = "SELECT * FROM saved_bs_accounts";
	$sbsacc_rslt = db_exec($sql)
		or errDie("Unable to retrieve accounts list from Cubit.");

	/*// the accounts list
	$acc_list = "<tr>
		<th colspan='4'>Accounts</th>
	</tr>";

	// has any accounts been added?
	if (!pg_num_rows($sbsacc_rslt)) {
		$acc_list .= "<tr class='bg-odd'>
			<td>No accounts have been added, ALL balance accounts will be displayed.</td>
		</tr>";
	}

	$i = 0;
	while ($sbsacc_data = pg_fetch_array($sbsacc_rslt)) {
		$i++;

		if (empty($sbsacc_data["toptype"])) {
			db_conn("core");
			$sql = "SELECT toptype FROM accounts WHERE accid='$sbsacc_data[accid]'";
			$rslt = db_exec($sql) or errDie("Unable to retrieve trial balance information from Cubit.");
			$acc_toptype = pg_fetch_result($rslt, 0);
		} else {
			$acc_toptype = $sbsacc_data["toptype"];
		}

		$categories_sel = "<select name='toptype[$sbsacc_data[accid]]'>";
		foreach ($ar_cats as $toptype=>$description) {
			if ($acc_toptype == $toptype) {
				$selected = "selected";
			} else {
				$selected = "";
			}
			$categories_sel .= "<option value='$toptype' $selected>$description</option>";
		}
		$categories_sel .= "</select>";

		// has any of the checkboxes been checked?
		if (isset($rem[$sbsacc_data["accid"]])) {
			$checked = "checked";
		} else {
			$checked = "";
		}

		$acc_list .= "<tr class='".bg_class()."'>
			<td>$sbsacc_data[accname]</td>
			<td>$categories_sel</td>
			<td><a href='#' onclick='openwindow(\"".SELF."?key=note_view&accid=$sbsacc_data[accid]\")'>Note</a></td>
			<td><input type='checkbox' name='rem[$sbsacc_data[accid]]' value='$sbsacc_data[accid]' $checked></td>
		</tr>";
	}
	$acc_list .= "<tr>
		<td colspan='3' align='center'>
			<input type='submit' name='key' value='Remove Selected'>
			<input type='submit' name='key' value='Update'>
		</td>
	</tr>";*/
	$acc_list = "";

	// Period dropdown
	/*$month_frm_sel = "<select name='month_from'>";
	for ($i = 1; $i <= 12; $i++) {
		if ($month_from == $i) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$month_frm_sel .= "<option value='$i' $selected>".getMonthName($i)."</option>";
	}
	$month_frm_sel .= "</select>";*/

	// Period dropdown
	$month_to_sel = finMonList("month_to", $month_to, true);

	//------------------------------------------------------------------------
	if (isset($last_year_same_month) && $last_year_same_month) {
		$lysm = "checked";
	} else {
		$lysm = "";
	}
	//------------------------------------------------------------------------
	if (isset($this_year_year_to_date) && $this_year_year_to_date) {
		$tyytd = "checked";
	} else {
		$tyytd = "";
	}
	//------------------------------------------------------------------------
	if (isset($this_year_movement_to_date) && $this_year_movement_to_date) {
		$tymtd = "checked";
	} else {
		$tymtd = "";
	}
	//------------------------------------------------------------------------
	if (isset($last_year_year_to_date) && $last_year_year_to_date) {
		$lyytd = "checked";
	} else {
		$lyytd = "";
	}
	//------------------------------------------------------------------------
	if (isset($budget) && $budget) {
		$budget = "checked";
	} else {
		$budget = "";
	}
	//------------------------------------------------------------------------
	if (isset($this_year_budget) && $this_year_budget) {
		$tybudget = "checked";
	} else {
		$tybudget = "";
	}

	// Layout
	$OUTPUT = "
		<h3>Balance Sheet</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='display' />
			<input type='hidden' name='customized' value='true' />
		<table border='0' cellpadding='0' cellspacing='0'>
			<tr>
				<th colspan='2'><h3>Customise</h3></th>
			</tr>
			<tr>
				<td valign='top'>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Headings</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Heading 1</td>
							<td><input type='text' name='heading_1' value='$heading_1'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Heading 2</td>
							<td><input type='text' name='heading_2' value='$heading_2'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Heading 3</td>
							<td><input type='text' name='heading_3' value='$heading_3'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Heading 4</td>
							<td><input type='text' name='heading_4' value='$heading_4'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Capital Employed</td>
							<td><input type='text' name='capital_employed_out' value='$capital_employed_out'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Employment of Capital</td>
							<td><input type='text' name='employment_of_capital_out' value='$employment_of_capital_out'></td>
						</tr>
					</table>
					<table ".TMPL_tblDflts." style='margin: 0px; width: 300px'>
						<tr>
							<th colspan='2'>Display</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Last Year - Selected Month</td>
							<td><input type='checkbox' name='last_year_same_month' value='true' $lysm \></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>This Year - Movement To Selected Month</td>
							<td><input type='checkbox' name='this_year_movement_to_date' value='true' $tymtd \></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>This Year - To Selected Month</td>
							<td><input type='checkbox' name='this_year_year_to_date' value='true' $tyytd \></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Last Year - Year End</td>
							<td><input type='checkbox' name='last_year_year_to_date' value='true' $lyytd \></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Selected Month Budget</td>
							<td><input type='checkbox' name='budget' value='true' $budget \></td>
						</tr>
						<!--<tr class='".bg_class()."'>
							<td>This Year Budget To Month</td>
							<td><input type='checkbox' name='this_year_budget' value='true' $tybudget \></td>
						</tr>//-->
						<tr class='".bg_class()."'>
							<td>Zero Balances</td>
							<td><input type='checkbox' name='zero_balance' value='checked' $zero_balance></td>
						</tr>
					</table>
					<table ".TMPL_tblDflts." style='margin: 0px; width: 300px'>
						<tr>
							<th>Year</th>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center'>$year_sel</td>
						</tr>
						<tr>
							<th>Month</th>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center'>$month_to_sel</td>
						</tr>
					</table>
					<table ".TMPL_tblDflts." style='margin: 0px; width: 300px'>
						<!--<tr>
							<th>Add Account</th>
						</tr>
						<tr class='".bg_class()."'>
							<td align='center'>\$acc_sel<input type='submit' name='key' value='Add'></td>
						</tr>//-->
						<tr>
							<td><input type='submit' value='Display &raquo' style='width: 100%; font-weight: bold'></td>
					</table>
				</td>
				<!--<td valign='top'>
					<table ".TMPL_tblDflts.">
						$acc_list
					</table>
				</td>//-->
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}




function update($_POST)
{

	extract ($_POST);

	if ($key == "add" && $account != 0) {
		// has this account already been added
		db_conn("cubit");

		$sql = "SELECT * FROM saved_bs_accounts WHERE accid='$account'";
		$sbsacc_rslt = db_exec($sql) or errDie("Unable to retieve accounts list from Cubit.");

		if (pg_num_rows($sbsacc_rslt) == 0) {
			db_conn("core");
			$sql = "SELECT * FROM accounts WHERE accid='$account'";
			$acc_rslt = db_exec($sql)
				or errDie("Unable to retrieve accounts from Cubit.");
			$acc_data = pg_fetch_array($acc_rslt);

			db_conn("cubit");
			$sql = "
				INSERT INTO saved_bs_accounts (
					accid, topacc, accnum, accname, toptype
				) VALUES (
					'$acc_data[accid]', '$acc_data[topacc]', '$acc_data[accnum]', '$acc_data[accname]', '$acc_data[toptype]'
				)";
			$sbsacc_rslt = db_exec($sql) or errDie("Unable to save the account to the accounts list.");
		}
	}

	if ($key == "update") {
		foreach ($toptype as $accid=>$value) {
			db_conn("cubit");
			$sql = "UPDATE saved_bs_accounts SET toptype='$value' WHERE accid='$accid'";
			$rslt = db_exec($sql);
		}
	}

	if ($key == "remove selected" && isset($rem)) {
		foreach ($rem as $accid=>$value) {
			db_conn("cubit");
			$sql = "DELETE FROM saved_bs_accounts WHERE accid='$accid'";
			$sbsacc_rslt = db_exec($sql) or errDie("Unable to delete selected entries from the accounts list.");
		}
	}

	return customize($_POST);

}




function note_view($_POST, $msg="")
{

	extract($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($accid, "num", 1, 9, "Invalid account id.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}



	// Retrieve the account name and the note (if any) from Cubit
	db_conn("cubit");
	$sql = "SELECT accname, note FROM saved_bs_accounts WHERE accid='$accid'";
	$note_rslt = db_exec($sql) or errDie("Unable to retrieve notes from Cubit.");
	$note_data = pg_fetch_array($note_rslt);

	if (!pg_num_rows($note_rslt)) {
		db_conn("core");
		$sql = "SELECT accname FROM accounts WHERE accid='$accid'";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
		$note_data["accname"] = pg_fetch_result($acc_rslt, 0);
		$note_data["note"] = "";
	}

	$OUTPUT = "
		<form method='post' action='".SELF."'>
			<input type='hidden' name='key' value='note_save'>
			<input type='hidden' name='accid' value='$accid'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td>$msg</td>
			</tr>
			<tr>
				<th>$note_data[accname]</th>
			</tr>
			<tr class='bg-odd'>
				<td><textarea name='note' rows='30' cols='50'>".base64_decode($note_data["note"])."</textarea></td>
			</tr>
			<tr>
				<td align='center'><input type='submit' value='Save &raquo'></td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}




function note_save($_POST)
{

	extract($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($accid, "num", 1, 9, "Invalid account id.");

	if ($v->isError()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}



	// Is the account already in the saved balance sheet table?
	db_conn("cubit");
	$sql = "SELECT * FROM saved_bs_accounts WHERE accid='$accid'";
	$sbsacc_rslt = db_exec($sql) or errDie("Unable to retrieve saved balance sheet accounts from Cubit.");

	if (pg_num_rows($sbsacc_rslt)) {
		db_conn("cubit");
		$sql = "UPDATE saved_bs_accounts SET note='".base64_encode($note)."' WHERE accid='$accid'";
		$note_rslt = db_exec($sql) or errDie("Unable to update note.");
		$note_data = pg_fetch_array($note_rslt);
	} else {
		db_conn("core");
		$sql = "SELECT * FROM accounts WHERE accid='$accid'";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
		$acc_data = pg_fetch_array($acc_rslt);

		db_conn("cubit");
		$sql = "INSERT INTO saved_bs_accounts (accid, topacc, accnum, accname, note) VALUES ('$acc_data[accid]', '$acc_data[topacc]', '$acc_data[accnum]', '$acc_data[accname]', '".base64_encode($note)."')";
		$sbsacc_rslt = db_exec($sql) or errDie("Unable to insert account information into the accounts list.");
	}

	return note_view($_POST, "<tr class='".bg_class()."'><td><li>Note has been updated.</li></td></tr>");

}


?>
