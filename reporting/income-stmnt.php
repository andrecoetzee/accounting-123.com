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

// Merge get vars and post vars
foreach ($_GET as $key=>$value) {
	$_POST[$key] = $value;
}

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		default:
		case "display":
		case "cust_display":
			$OUTPUT = financialStatements::incomestmnt($_POST);
			break;
		case "customize":
			$OUTPUT = customize($_POST);
			break;
		case "add":
		case "remove selected":
		case "update":
			$OUTPUT = update($_POST);
			break;
		case ct("Print"):
		case ct("Save"):
		case ct("Export to Spreadsheet"):
		case "print_report":
			$OUTPUT = print_report($_POST);
			break;
		case "note_view":
			$OUTPUT = note_view($_POST);
			break;
		case "note_save":
			$OUTPUT = note_save($_POST);
			break;
	}
} else {
	//$OUTPUT = financialStatements::incomestmnt($_POST);
	$OUTPUT = customize($_POST);
}

require ("../template.php");




function print_report() {
	$OUTPUT = clean_html(financialStatements::incomestmnt($_POST));

	switch ($_POST["key"]) {
		case ct("Print"):
			require ("../tmpl-print.php");
			break;
		case ct("Save"):
			db_conn("core");
			$sql = "INSERT INTO save_income_stmnt (output, gendate, div) VALUES ('".base64_encode($OUTPUT)."', current_date, '".USER_DIV."')";
			$svincRslt = db_exec($sql) or errDie("Unable to save the balance sheet to Cubit.");

			return "<li class='err'>Income statement has been successfully saved to Cubit.</li>
			<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
				<tr><th>Quick Links</th></tr>
				<tr class=datacell><td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td></tr>
				<tr class=datacell><td align=center><a href='index-reports.php'>Financials</a></td></tr>
				<tr class=datacell><td align=center><a href='index-reports-stmnt.php'>Current Year Financial Statements</a></td></tr>
				<tr class=datacell><td align=center><a href='../main.php'>Main Menu</td></tr>
			</table>";
			break;
		case ct("Export to Spreadsheet"):
			require_lib("xls");
			StreamXLS("income_statement" , $OUTPUT);
			break;
	}
}

function customize($_POST)
{
	extract($_POST);

	$fields = array ();
	$fields["heading_1"] = COMP_NAME;
	$fields["heading_2"] = date("d/m/Y");
	$fields["heading_3"] = "Income Statement";
	$fields["heading_4"] = "Prepared by: ".USER_NAME;
	$fields["viewview"] = "core";
	$fields["month_from"] = (int)date("m");
	$fields["month_to"] = (int)date("m");
	$fields["this_year_this_month"] = true;
	$fields["last_year_same_month"] = true;
	$fields["this_year_year_to_date"] = true;
	$fields["last_year_year_to_date"] = true;
	$fields["budget"] = true;
	$fields["this_year_budget"] = true;
	$fields["zero_balance"] = "";
	$fields["naccount"] = "";

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

	/*$months_from = "<select name='month_from'>";
	// Retrive month names
	for ($i = 1; $i <= 12; $i++) {
		if ($month_from == $i) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$months_from .= "<option value='$i' $selected>".getMonthName($i)."</option>";
	}
	$months_from .= "</select>";*/

	$months_to = finMonList("month_to", $month_to, true);

	// Retrieve list of accounts for the account dropdown
	db_conn("core");
	$sql = "SELECT * FROM accounts WHERE acctype='I' OR acctype='E' ORDER BY accname,topacc ASC";
	$acc_rslt = db_exec($sql)
		or errDie("Unable to retrieve accounts information from Cubit.");

	/*
	// Accounts dropdown output
	$acc_sel = "<select name='naccount' style='width: 200px'>
		<option value='0'>Please select</option>";
	while ($acc_data = pg_fetch_array($acc_rslt)) {
		if ($naccount == $acc_data["accid"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}

		$acc_sel .= "<option value='$acc_data[accid]' $selected>$acc_data[accname]</option>";
	}
	$acc_sel .= "</select>";

	// Retrieve saved accounts from Cubit
	db_conn("cubit");
	$sql = "SELECT * FROM saved_is_accounts";
	$sisacc_rslt = db_exec($sql) or errDie("Unable to retrieve saved income statement accounts from Cubit");

	if (!pg_num_rows($sisacc_rslt)) {
		$accounts_out = "
		<tr class='bg-odd'>
			<td>No accounts have been selected, ALL accounts will be displayed</td>
		</tr>";
	} else {
		$accounts_out = "";
	}

	$i = 0;
	while ($sisacc_data = pg_fetch_array($sisacc_rslt)) {
		// Alternate the background colour
		$i++;

		if (isset($rem[$sisacc_data["id"]])) {
			$checked = "checked";
		} else {
			$checked = "";
		}

		$accounts_out .= "
		<tr class='".bg_class()."'>
			<td>$sisacc_data[accname]</td>
			<td><input type='checkbox' name='rem[$sisacc_data[id]]' value='$sisacc_data[id]' $checked></td>
		</tr>";
	}*/
	$accounts_out = "";

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
	$OUTPUT = "<h3>Income Statement</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='display' />
	<input type='hidden' name='acc_view' value='sel'>
	<input type='hidden' name='customized' value='true'>
	<table border='0' cellpadding='0' cellspacing='0'>
		<tr>
			<th colspan=2><h3>Customise<h3></th>
		</tr>

		<tr><td valign='top'>
		<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' style='margin: 0px; width: 300px'>
			<tr>
				<th colspan=2>Headings</th>
			</tr>
			<tr class='bg-odd'>
				<td>Heading 1</td>
				<td><input type=text name='heading_1' value='$heading_1' style='width: 100%'></td>
			</tr>
			<tr class='bg-even'>
				<td>Heading 2</td>
				<td><input type=text name='heading_2' value='$heading_2' style='width: 100%'></td>
			</tr>
			<tr class='bg-odd'>
				<td>Heading 3</td>
				<td><input type=text name='heading_3' value='$heading_3' style='width: 100%'></td>
			</tr>
			<tr class='bg-even'>
				<td width='0%'>Heading 4</td>
				<td><input type=text name='heading_4' value='$heading_4' style='width: 100%'></td>
			</tr>
			<tr>
				<th colspan=2>Display</th>
			</tr>
			<tr class='bg-odd'>
				<td>Last Year - Same Month</td>
				<td><input type='checkbox' name='last_year_same_month' value='true' $lysm \></td>
			</tr>
			<tr class='bg-even'>
				<td>This Year - To Month</td>
				<td><input type='checkbox' name='this_year_year_to_date' value='true' $tyytd \></td>
			</tr>
			<tr class='bg-odd'>
				<td>Last Year - Year To Date</td>
				<td><input type='checkbox' name='last_year_year_to_date' value='true' $lyytd \></td>
			</tr>
			<tr class='bg-even'>
				<td>Month Budget</td>
				<td><input type='checkbox' name='budget' value='true' $budget \></td>
			</tr>
			<tr class='bg-odd'>
				<td>This Year Budget To Month</td>
				<td><input type='checkbox' name='this_year_budget' value='true' $tybudget \></td>
			</tr>
			<tr class='bg-even'>
				<td>Zero Balances</td>
				<td><input type='checkbox' name='zero_balance' value='checked' $zero_balance \></td>
			</tr>
			<tr class='bg-odd'>
				<td colspan='2'>
					<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
					<tr class='bg-odd'>
						<td>Year</td>
						<td align='center'>$year_sel</td>
					</tr>
					<tr>
						<td>Month</td>
						<td nowrap>$months_to</td>
					</tr>
					</table>
				</td>
			</tr>
		</table>
		<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' style='margin: 0px; width: 300px'>
			<!--<tr>
				<th colspan='2'>Add Account</th>
			</tr>
			<tr class='bg-odd'>
				<td align='center'>\$acc_sel</td>
				<td align='center'><input type='submit' name='key' value='Add'></td>
			</tr>//-->
			<tr>
				<td colspan='2'><input type='submit' value='Display' style='width: 100%; font-weight: bold;'></td>
			</tr>
		</table>
		</td>

		<!--<td valign='top'>
		<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='250px'>
			<tr>
				<th colspan='2'>Accounts</th>
			</tr>
			<tr>
				$accounts_out
			</tr>
			<tr>
				<td align='center'><input type='submit' name='key' value='Remove Selected'></td>
			</tr>
		</table>
		</td>//-->
		</tr>
	</table>
	</form>";

	return $OUTPUT;
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
	$sql = "SELECT accname, note FROM saved_is_accounts WHERE accid='$accid'";
	$note_rslt = db_exec($sql) or errDie("Unable to retrieve notes from Cubit.");
	$note_data = pg_fetch_array($note_rslt);

	if (!pg_num_rows($note_rslt)) {
		db_conn("core");
		$sql = "SELECT accname FROM accounts WHERE accid='$accid'";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
		$note_data["accname"] = pg_fetch_result($acc_rslt, 0);
		$note_data["note"] = "";
	}

	$OUTPUT = "<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='note_save'>
	<input type='hidden' name='accid' value='$accid'>
	<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
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
	</table>";

	return $OUTPUT;
}

function update($_POST)
{
	extract ($_POST);

	if ($key == "add" && isset($naccount) && $naccount != 0) {
		// Has this account been added already?
		db_conn("cubit");
		$sql = "SELECT * FROM saved_is_accounts WHERE accid='$naccount'";
		$stb_rslt = db_exec($sql) or errDie("Unable to retrieve saved trial balance accounts from Cubit.");
		if (pg_num_rows($stb_rslt) > 0) {
			return customize($_POST);
		}

		// Retrieve the account info with the accid
		db_conn("core");
		$sql = "SELECT * FROM accounts WHERE accid='$naccount' AND div='".USER_DIV."'";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
		$acc_data = pg_fetch_array($acc_rslt);

		// Insert into the db
		db_conn("cubit");
		$sql = "INSERT INTO saved_is_accounts (accid, topacc, accnum, accname) VALUES ('$acc_data[accid]', '$acc_data[topacc]', '$acc_data[accnum]', '$acc_data[accname]')";
		$stbacc_rslt = db_exec($sql) or errDie("Unable to update your customization settings to Cubit.");
	}
	// Remove selected items
	if ($key == "remove selected" && isset($rem)) {
		foreach ($rem as $id) {
			db_conn("cubit");
			$sql = "DELETE FROM saved_is_accounts WHERE id='$id'";
			$stbacc_rslt = db_exec($sql) or errDie("Unable to remove account from the accounts list.");
		}
	}

	return customize($_POST);
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
	$sql = "SELECT * FROM saved_is_accounts WHERE accid='$accid'";
	$sbsacc_rslt = db_exec($sql) or errDie("Unable to retrieve saved balance sheet accounts from Cubit.");

	if (pg_num_rows($sbsacc_rslt)) {
		db_conn("cubit");
		$sql = "UPDATE saved_is_accounts SET note='".base64_encode($note)."' WHERE accid='$accid'";
		$note_rslt = db_exec($sql) or errDie("Unable to update note.");
		$note_data = pg_fetch_array($note_rslt);
	} else {
		db_conn("core");
		$sql = "SELECT * FROM accounts WHERE accid='$accid'";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
		$acc_data = pg_fetch_array($acc_rslt);

		db_conn("cubit");
		$sql = "INSERT INTO saved_is_accounts (accid, topacc, accnum, accname, note) VALUES ('$acc_data[accid]', '$acc_data[topacc]', '$acc_data[accnum]', '$acc_data[accname]', '".base64_encode($note)."')";
		$sbsacc_rslt = db_exec($sql) or errDie("Unable to insert account information into the accounts list.");
	}

	return note_view($_POST, "<tr class='bg-odd'><td><li>Note has been updated.</li></td></tr>");
}

?>
