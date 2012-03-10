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

require("../settings.php");
require("../core-settings.php");
require("finstatements.php");

// Merge get vars and post vars
foreach ($HTTP_GET_VARS as $key=>$value) {
	$HTTP_POST_VARS[$key] = $value;
}

if (isset($HTTP_POST_VARS["key"])) {
	// Decide what to do
	switch ($HTTP_POST_VARS["key"]) {
		default:
		case "customize":
			$OUTPUT = customize($HTTP_POST_VARS);
			break;
		case "add":
		case "remove selected":
		case "update":
			$OUTPUT = update($HTTP_POST_VARS);
			break;
		case "display":
			$OUTPUT = financialStatements::trialbal($_POST);
			$ql = true;
			break;
		case ct("Print"):
		case ct("Save"):
		case ct("Export to Spreadsheet"):
			$OUTPUT = print_report();
			break;
		case "note_view":
			$OUTPUT = note_view($HTTP_POST_VARS);
			break;
		case "note_save":
			$OUTPUT = note_save($HTTP_POST_VARS);
			break;
	}
} else {
	$OUTPUT = customize($HTTP_POST_VARS);
	//$OUTPUT = financialStatements::trialbal($_GET);
	$ql = true;
}

require ("../template.php");



function customize($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	$fields["naccount"] = "";
	$fields["last_year"] = "checked";
	$fields["budget"] = "checked";
	$fields["month_from"] = (int)date("m");
	$fields["month_to"] = (int)date("m");
	$fields["heading_1"] = COMP_NAME;
	$fields["heading_2"] = date("d/m/Y");
	$fields["heading_3"] = "Trial Balance";
	$fields["heading_4"] = "Prepared by: ".USER_NAME;
	$fields["viewyear"] = "core";
	$fields["zero_balance"] = "";
	$fields["debit_credit"] = "";

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

	$year_sel .= "</select>";

	// Should we display the last year field
	if ((substr(YR_DB, 2) - 1) > 0) {
		if (isset($last_year) && $last_year) {
			$ch = "checked='t'";
		} else {
			$ch = "";
		}

		$last_year_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Last Year</td>
				<td align='center'><input type='checkbox' name='last_year' value='checked' $ch></td>
			</tr>";
	} else {
		$last_year_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Last Year</td>
				<td align='center'>No prior years found.</td>
			</tr>";
	}

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

	/*// Retrieve list of accounts for the dropdowns
	db_conn("core");
	$sql = "SELECT * FROM trial_bal WHERE div='".USER_DIV."' ORDER BY topacc, accnum ASC";
	$tb_rslt = db_exec($sql) or errDie("Unable to retrieve trial balance accounts from Cubit.");

	$accounts = array();
	while ($tb_data = pg_fetch_array($tb_rslt)) {
		$accounts[$tb_data["accid"]] = "$tb_data[topacc]/$tb_data[accnum] $tb_data[accname]";
	}

	$naccount_out = "
	<tr bgcolor='".TMPL_tblDataColor1."'>
		<td align=center><select name=naccount style='width: 240px'>
		<option value='0'>Please select</option>";

	foreach ($accounts as $accid=>$value) {
		if ($naccount == $accid) {
			$selected = "selected";
		} else {
			$selected = "";
		}

		$naccount_out .= "<option value='$accid' $selected>$value</option>";
	}

	$naccount_out .= "</select></td>
		<td><input type=submit name='key' value='Add'></td>
	</tr>";*/

	// Retrieved the saved trial balance layout from Cubit
	db_conn("cubit");

	$sql = "SELECT * FROM saved_tb_accounts";
	$stbacc_rslt = db_exec($sql) or errDie("Unable to retrieve saved trial balance accounts from Cubit.");

	if (pg_num_rows($stbacc_rslt) == 0) {
		$accounts_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td>No accounts have been selected, ALL accounts will be displayed.</td>
			</tr>";
	} else {
		$accounts_out = "";
	}

	$i = 0;
	while ($stbacc_data = pg_fetch_array($stbacc_rslt)) {
		$i++;

		// Was anything in the remove list selected
		if (isset($rem[$stbacc_data["id"]])) {
			$checked = "checked";
		} else {
			$checked = "";
		}

		$accounts_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$stbacc_data[topacc]/$stbacc_data[accnum]</td>
				<td>$stbacc_data[accname]</td>
				<td><input type='checkbox' name='rem[$stbacc_data[id]]' value='$stbacc_data[id]' $checked></td>
				<td><a href='#' onclick='popupSized(\"".SELF."?key=note_view&accid=$stbacc_data[accid]\", 'note$stbacc_data[accid]', 480, 800, '');'>Note</a></td>
			</tr>";
	}

	// Layout
	$OUTPUT = "
		<h3>Trial Balance</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='display'>
			<input type='hidden' name='acc_view' value='sel'>
			<input type='hidden' name='customized' value='true'>
		<table border='0' cellpadding='0' cellspacing='0'>
			<tr>
				<th colspan='2'><h3>Customise</h3></th>
			</tr>
			<tr><td valign=top>
			<table ".TMPL_tblDflts." style='width: 300px; margin: 0px;'>
				<tr>
					<th colspan='2'>Display</th>
				</tr>
				$last_year_out
				<tr bgcolor='".bgcolorg()."'>
					<td>Annual Budget</td>
					<td align=center><input type=checkbox name=budget value='checked' $budget></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Zero Balances</td>
					<td align='center'><input type='checkbox' name='zero_balance' value='checked' $zero_balance></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>List Debit & Credit</td>
					<td align='center'><input type='checkbox' name='debit_credit' $debit_credit></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Year</td>
					<td align='center'>$year_sel</td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Month</td>
					<td align=center nowrap>$months_to</td>
				</tr>
			</table>
			<!--<table ".TMPL_tblDflts." style='width: 300px; margin: 0px;'>
				<tr>
					<th colspan='2'>Add Account</th>
				</tr>
				\$naccount_out
			</table>//-->
			<table ".TMPL_tblDflts." style='width: 300px; margin: 0px;'>
				<tr>
					<th colspan='2'>Headings</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Heading 1</td>
					<td><input type='text' name='heading_1' value='$heading_1' style='width: 100%'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Heading 2</td>
					<td><input type='text' name='heading_2' value='$heading_2' style='width: 100%'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td>Heading 3</td>
					<td><input type='text' name='heading_3' value='$heading_3' style='width: 100%'></td>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td width='0%'>Heading 4</td>
					<td><input type='text' name='heading_4' value='$heading_4' style='width: 100%'></td>
				</tr>
				<tr>
					<td colspan='3'><input type='submit' value='Display &raquo' style='width:100%; font-weight: bold;'></td>
				</tr>
			</table>
			</td><td valign=top>
			<!--<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='4'>Accounts</th>
				</tr>
				$accounts_out
				<tr>
					<td colspan='4' align='center'><input type='submit' name='key' value='Remove Selected'></td>
				</tr>
			</table>//-->
		</table>
		</form>";
	return $OUTPUT;

}




function update($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	if ($key == "add" && isset($naccount) && $naccount != 0) {
		// Has this account been added already?
		db_conn("cubit");
		$sql = "SELECT * FROM saved_tb_accounts WHERE accid='$naccount'";
		$stb_rslt = db_exec($sql) or errDie("Unable to retrieve saved trial balance accounts from Cubit.");
		if (pg_num_rows($stb_rslt) > 0) {
			return customize($HTTP_POST_VARS);
		}

		// Retrieve the account info with the accid
		db_conn("core");
		$sql = "SELECT * FROM accounts WHERE accid='$naccount' AND div='".USER_DIV."'";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
		$acc_data = pg_fetch_array($acc_rslt);

		// Insert into the db
		db_conn("cubit");
		$sql = "
			INSERT INTO saved_tb_accounts (
				accid, topacc, accnum, accname
			) VALUES (
				'$acc_data[accid]', '$acc_data[topacc]', '$acc_data[accnum]', '$acc_data[accname]'
			)";
		$stbacc_rslt = db_exec($sql) or errDie("Unable to update your customization settings to Cubit.");
	}
	// Remove selected items
	if ($key == "remove selected" && isset($rem)) {
		foreach ($rem as $id) {
			db_conn("cubit");
			$sql = "DELETE FROM saved_tb_accounts WHERE id='$id'";
			$stbacc_rslt = db_exec($sql) or errDie("Unable to remove account from the accounts list.");
		}
	}
	return customize($HTTP_POST_VARS);

}



function print_report()
{

	$OUTPUT = clean_html(financialStatements::trialbal($_POST));

	switch ($_POST["key"]) {
		case ct("Print"):
			require ("../tmpl-print.php");
			break;
		case ct("Save"):
			db_conn("core");
			$sql = "INSERT INTO save_trial_bal (output, gendate, div) VALUES ('".base64_encode($OUTPUT)."', current_date, '".USER_DIV."')";
			$svincRslt = db_exec($sql) or errDie("Unable to save the trial balance to Cubit.");

			return "
				<li>Trial Balance has been successfully saved to Cubit.</li>
				<table ".TMPL_tblDflts." width='25%'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='datacell'>
						<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
					</tr>
					<tr class='datacell'>
						<td align='center'><a href='index-reports.php'>Financials</a></td>
					</tr>
					<tr class='datacell'>
						<td align='center'><a href='index-reports-stmnt.php'>Current Year Financial Statements</a></td>
					</tr>
					<tr class='datacell'>
						<td align='center'><a href='../main.php'>Main Menu</td>
					</tr>
				</table>";
			break;
		case ct("Export to Spreadsheet"):
			require_lib("xls");
			StreamXLS("trial_balance" , $OUTPUT);
			break;
	}

}




function note_view($HTTP_POST_VARS, $msg="")
{

	extract($HTTP_POST_VARS);

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

	// Retrieve the account name and note from Cubit.
	db_conn("cubit");

	$sql = "SELECT accname, note FROM saved_tb_accounts WHERE accid='$accid'";
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
		<center>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='note_save'>
			<input type='hidden' name='accid' value='$accid'>
		<table ".TMPL_tblDflts.">
			<tr>
				<td>$msg</td>
			</tr>
			<tr>
				<th>$note_data[accname]</th>
			</tr>
			<tr bgcolor='".TMPL_tblDataColor1."'>
				<td><textarea name='note' rows='30' cols='50'>".base64_decode($note_data["note"])."</textarea></td>
			</tr>
			<tr>
				<td align='center'><input type='submit' value='Save &raquo'></td>
			</tr>
		</table>
		</center>";
	return $OUTPUT;

}




function note_save($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

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

	$sql = "SELECT * FROM saved_tb_accounts WHERE accid='$accid'";
	$sbsacc_rslt = db_exec($sql) or errDie("Unable to retrieve saved balance sheet accounts from Cubit.");
	if (pg_num_rows($sbsacc_rslt)) {
		db_conn("cubit");
		$sql = "UPDATE saved_tb_accounts SET note='".base64_encode($note)."' WHERE accid='$accid'";
		$note_rslt = db_exec($sql) or errDie("Unable to update note.");
		$note_data = pg_fetch_array($note_rslt);
	} else {
		db_conn("core");
		$sql = "SELECT * FROM accounts WHERE accid='$accid'";
		$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
		$acc_data = pg_fetch_array($acc_rslt);

		db_conn("cubit");
		$sql = "
			INSERT INTO saved_tb_accounts (
				accid, topacc, accnum, accname, note
			) VALUES (
				'$acc_data[accid]', '$acc_data[topacc]', '$acc_data[accnum]', '$acc_data[accname]', '".base64_encode($note)."'
			)";
		$sbsacc_rslt = db_exec($sql) or errDie("Unable to insert account information into the accounts list.");
	}
	return note_view($HTTP_POST_VARS, "<tr bgcolor='".bgcolorg()."'><td><li>Note has been updated.</li></td></tr>");

}


?>