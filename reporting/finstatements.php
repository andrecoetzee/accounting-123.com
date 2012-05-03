<?
/**
 * Financial statement related functions/classes
 *
 * @package Cubit
 * @subpackage Financial_Statements
 */

/**
 * Contains functions to generate financial statements
 *
 */
class financialStatements {
	/**
	 * Generates a trial balance
	 *
	 * @param array $VARS GET/POST vars
	 * @param bool $pure true if quicklinks/forms/stuff should be excluded
	 * @return string
	 */
	static function trialbal($VARS = false, $pure = false) {
		if ($VARS === false) $VARS = array();
		extract($VARS);

		if (isset($key) && $key == "display" && !isset($budget)) {
			$budget = false;
		}

		// Create some default values
		$fields["naccount"] = "";
		$fields["budget"] = "";
		$fields["viewyear"] = "core";
		$fields["month_from"] = (int)date("m");
		$fields["month_to"] = (int)date("m");
		$fields["heading_1"] = COMP_NAME;
		$fields["heading_2"] = date("d/m/Y");
		$fields["heading_3"] = "Trial Balance";
		$fields["heading_4"] = "Prepared by: ".USER_NAME;
		$fields["zero_balance"] = "";
		$fields["debit_credit"] = "";

		foreach ($fields as $var_name=>$value) {
			if (!isset($$var_name)) {
				$$var_name = $value;
			}
		}

		$cols["last_year"] = true;

		foreach ($cols as $fname => $v) {
			if (!isset($$fname) && isset($customized)) {
				$$fname = false;
			} else if (isset($customized)) {
				$$fname = true;
			} else if (!isset($$fname)) {
				$$fname = $v;
			}
		}

		if ($viewyear == "core") {
			$last_year_schema = "yr" . (substr(YR_DB, 2) - 1);
		} else {
			$last_year_schema = "yr" . (substr($viewyear, 2) - 1);
		}

		if ($last_year && $last_year_schema == "yr0") {
			$last_year = false;
		}

		// Display zero balances
		if (empty($zero_balance)) {
			$zb_sql = "(debit!=0 OR credit!=0)";
		} else {
			$zb_sql = "(true)";
		}
		$zb_sql = "(true)";

		/* hard code so only one month is used */
		$month_from = $month_to;

		$month_from_out = getMonthName($month_from);
		$month_to_out = getMonthName($month_to);

		/* create the month range sql */
		if ($month_from > $month_to) {
			$month_range = "(month >= '$month_from' OR month <= '$month_to')";
			$budget_month_range = "(prd >= '$month_from' OR prd <= '$month_to')";
		} else {
			$month_range = "(month >= '$month_from' AND month <= '$month_to')";
			$budget_month_range = "(prd >= '$month_from' AND prd <= '$month_to')";
		}

		if (!isset($key)) $key = "";

		if ($pure === false) {
			switch ($key) {
				case ct("Print"):
				case ct("Save"):
					$pure = true;
					break;
				case ct("Export to Spreadsheet"):
					define("MONEY_NUMERIC", true);
					$pure = true;
					break;
				default:
					$pure = false;
					break;
			}
		}

		if ($pure) {
			// Retrieve the notes
			db_conn("cubit");
			$sql = "SELECT * FROM saved_tb_accounts WHERE note!=''";
			$rslt = db_exec($sql) or errDie("Unable to retrieve notes from Cubit.");

			$i = 0;
			$notes = array();
			while ($note_data = pg_fetch_array($rslt)) {
				$i++;

				$notes["$note_data[accid]"] = $i;
			}
		}

		// Values for the net income
		$cr_net_income = 0;
		$dr_net_income = 0;

		/* net profit budget total */
		$np_bud_total = 0;

		$tb_out = "";
		for ($i = 0; $i <= 1; $i++) {
			$last_year_total = 0.00;
			$bud_total = 0.00;
			$dr_total = 0.00;
			$cr_total = 0.00;

			// Decide which account numbers to show
			if (!$i) {
				$range_min = 5000;
				$range_max = 9999;
			} else {
				$range_min = 0;
				$range_max = 4999;
			}

			// Retrieve the trial balance information from Cubit
			//db_conn("cubit");
			//$sql = "SELECT * FROM saved_tb_accounts WHERE topacc>='$range_min' AND topacc<='$range_max' ORDER BY topacc ASC";
			//$stb_rslt = db_exec($sql) or errDie("Unable to retrieve trial balance data from Cubit.");

			//if (!pg_num_rows($stb_rslt) || !isset($acc_view)) {
			db_conn($viewyear);
			$sql = "SELECT DISTINCT accid, topacc, accnum, accname FROM trial_bal WHERE topacc>='$range_min' AND topacc<='$range_max' AND $month_range AND $zb_sql ORDER BY topacc, accnum ASC";
			$stb_rslt = db_exec($sql) or errDie("Unable to retrieve trial balance data from Cubit.");
			//}

			// Which headings should be displayed
			if (pg_num_rows($stb_rslt) != 0) {
				$tb_out .= "<tr>";
				if ($last_year) {
					$tb_out .= "<th class='thkborder'>Last Year</th>";
				}
				if (!empty($budget)) {
					$tb_out .= "<th class='thkborder'>Annual Budget</th>";
				}
				$tb_out .= "
					<th colspan='2' class='thkborder'>Account</th>
					<th class='thkborder'>DR</th>
					<th class='thkborder'>CR</th>
					<th class='thkborder thkborder_right'>Note</th>
				</tr>";
			}

			if ($i) {
				$tb_out.= "<tr class='bg-odd'>";
				$tb_out .= "<tr class='bg-odd'>";
				if (!empty($budget)) $tb_out .= "<td align='right'><!--%%NETPROFIT_BUDGET%%--></td>";
				if ($last_year) $tb_out .= "<td>&nbsp;</td>";
				$tb_out .= "
						<td>&nbsp;</td>
						<td>Net Profit / Loss</td>
						<td align='right'>".fsmoney($cr_net_income)."</td>
						<td align='right'>".fsmoney($dr_net_income)."</td>
						<td></td>
					</tr>";

				$dr_total += $cr_net_income;
				$cr_total += $dr_net_income;
			}

			while ($stb_data = pg_fetch_array($stb_rslt)) {

				$accid = $stb_data["accid"];
				// Retrieve the info from the trial balance
				db_conn($viewyear);

				$sql = "SELECT debit, credit FROM trial_bal WHERE month='$month_to' AND accid='$stb_data[accid]' AND $zb_sql";
				$tb_rslt = db_exec($sql) or errDie("Unable to retrieve trial balance information from Cubit.");
				$tb_data = pg_fetch_array($tb_rslt);

				$disp_acc = 0;
				$tmp_out = "";

				if (pg_num_rows($tb_rslt)) {
					$tmp_out .= "<tr class='bg-even'>";

					// Previous year
					if ($last_year) {
						// Retrieve previous year values
						db_conn($last_year_schema);
						$sql = "SELECT debit, credit FROM trial_bal WHERE month='$month_to' AND accid='$stb_data[accid]' AND $zb_sql";
						$prev_yr_rslt = db_exec($sql) or errDie("Unable to retrieve previous year balance from Cubit.");
						$prev_yr_data = pg_fetch_array($prev_yr_rslt);

						$tmp_out .= "<td align='right' style='width: 10%'>".fsmoney($prev_yr_data["credit"] - $prev_yr_data["debit"], 2)."</td>";

						$last_year_total += $prev_yr_data["credit"] - $prev_yr_data["debit"];
					}

					// Budget
					if (!empty($budget)) {
						// Retrieve budget values
						db_conn("cubit");
						$sql = "SELECT sum(amt) as amt FROM buditems WHERE id='$stb_data[accid]'";
						$bud_rslt = db_exec($sql) or errDie("Unable to retrieve budget items from Cubit.");
						$bud_data = pg_fetch_array($bud_rslt);
						$tmp_out .= "<td style='width: 10%' align='right'>$bud_data[amt]</td>";

						if ($i) {
							if ($stb_data["topacc"] >= MIN_EXP && $stb_data["topacc"] <= MAX_EXP) {
								$np_bud_total -= $bud_data["amt"];
							} else {
								$np_bud_total += $bud_data["amt"];
							}
						}

						$bud_total += $bud_data["amt"];

						$disp_acc += $bud_data["amt"];
					}

					// Is this a main account, then display note
					if ($pure && isset($notes[$accid])) {
						$note_out = $notes[$accid];
					} else if (!$pure && $stb_data["accnum"] == "000") {
						$note_out = "<a href='#' onclick='popupSized(\"".SELF."?key=note_view&accid=$stb_data[accid]\", \"note$stb_data[accid]\", 480, 600, \"\");'>Note</a>";
					} else {
						$note_out = "&nbsp";
					}

					if (empty($debit_credit)) {
						// Calculate the values
						if ($tb_data["debit"] > $tb_data["credit"]) {
							$debit = $tb_data["debit"] - $tb_data["credit"];
							$credit = "";
						} elseif ($tb_data["credit"] > $tb_data["debit"]) {
							$credit = $tb_data["credit"] - $tb_data["debit"];
							$debit = "";
						} else {
							$debit = "0.00";
							$credit = "";
						}
					} else {
						$debit = $tb_data["debit"];
						$credit = $tb_data["credit"];
					}

					$disp_acc += $debit - $credit;

					if (empty($zero_balance) && $disp_acc == 0) {
						continue;
					}

					$tb_out .= "
						$tmp_out
						<td align='center' style='width: 10%'>$stb_data[topacc]/$stb_data[accnum]</td>
							<td><a href='#' onClick=\"window.open('drill-view-trans.php?accid=$stb_data[accid]&month_to=$month_to','window$carr[accid]','width=900, height=380, scrollbars=yes');\">$stb_data[accname]</a></td>
							<td align='right' style='width: 10%'><a href='#' onClick=\"window.open('../core/drill-trans-new.php?dtaccid=$stb_data[accid]&ctaccid=$stb_data[accid]','window$stb_data[accid]','height=420, width=900, scrollbars=yes');\">".fsmoney($debit)."</a></td>
							<td align='right' style='width: 10%'><a href='#' onClick=\"window.open('../core/drill-trans-new.php?dtaccid=$stb_data[accid]&ctaccid=$stb_data[accid]','window$stb_data[accid]','height=420, width=900, scrollbars=yes');\">".fsmoney($credit)."</a></td>
							<td witdh='10%' align='center'>$note_out</td>
						</tr>";

					// Add to the total
					if (!empty($debit)) $dr_total += sprint($debit);
					if (!empty($credit)) $cr_total += sprint($credit);
				}
			}

			// Calculate the net income
			if (!$i) {
				if ($dr_total > $cr_total) {
					$cr_net_income = $dr_total - $cr_total;
					$cr_total += $cr_net_income;
				} elseif ($cr_total > $dr_total) {
					$dr_net_income = $cr_total - $dr_total;
					$dr_total += $dr_net_income;
				}

				$tb_out .= "<tr class='bg-odd'>";
				if (!empty($budget)) $tb_out .= "<td align='right'><!--%%NETPROFIT_BUDGET%%--></td>";
				if ($last_year) $tb_out .= "<td>&nbsp;</td>";
				$tb_out .= "
						<td>&nbsp;</td>
						<td>Net Income</td>
						<td align='right'>".fsmoney($dr_net_income)."</td>
						<td align='right'>".fsmoney($cr_net_income)."</td>
						<td></td>
					</tr>";

			}
			$tb_out .= "<tr class='bg-odd'>";
			if ($last_year) {
				$tb_out .= "<td align='right'>".fsmoney($last_year_total)."</td>";
			}
			if (!empty($budget)) {
				$tb_out .= "<td align='right'>".fsmoney($bud_total)."</td>";
			}

			/* net profit budget */
			if ($i) {
				$np_bud_total = $np_bud_total;
				$tb_out = str_replace("<!--%%NETPROFIT_BUDGET%%-->", fsmoney($np_bud_total), $tb_out);
			}

			#sanity check ...
			if ((sprint ($dr_total) != sprint ($cr_total)) AND ((sprint ($dr_total) != 0.00) AND (sprint ($cr_total) != 0.00))){
				#TRIAL BALANCE DOESNT BALANCE ?????
				# :/

				$display_dr = "<p class='err'>ERROR: Database Corruption Detected. Please Contact Your Dealer</p>";
				$display_cr = "<p class='err'>ERROR: Database Corruption Detected. Please Contact Your Dealer</p>";

				#recalculate!
//				recalculate_tb ();

				#reload!
//				return financialStatements::trialbal($_POST);

				#profit!
			}else {
				$display_dr = fsmoney($dr_total, 2);
				$display_cr = fsmoney($cr_total, 2);
			}

			$tb_out .= "
					<td></td>
					<td></td>
					<td align='right'>$display_dr</td>
					<td align='right'>$display_cr</td>
					<td>&nbsp</td>
				</tr>";
		}

		if (isset($acc_view)) {
			$acc_view_hidden = "<input type='hidden' name='acc_view' value='$acc_view'>";
		} else {
			$acc_view_hidden = "";
		}

		if (empty($debit_credit)) {
			$debit_credit = "";
		} else {
			$debit_credit = "checked";
		}

		// Retrieve the current year from Cubit
		global $PRDMON, $MONPRD;

		if ($viewyear == "core") {
			$sql = "SELECT yrname FROM core.active";
		} else {
			$sql = "SELECT yrname FROM core.year WHERE yrdb='$viewyear'";
		}
		$rslt = db_exec($sql) or errDie("Unable to retrieve current year from Cubit.");
		$year_out = substr(pg_fetch_result($rslt, 0), 1) - (int)($PRDMON[1] > 1);

		if ($month_to < $PRDMON[1]) {
			++$year_out;
		}

		if ($month_from == $month_to) {
			$date_range = "$month_from_out $year_out";
		} else {
			$date_range = "$month_from_out TO $month_to_out $year_out";
		}

		// Layout
		$OUTPUT = "";
		if (!$pure) {
			$OUTPUT .= "
				<form method='POST' action='".SELF."'>
					<input type='hidden' name='key' value='customize'>
					<input type='hidden' name='month_from' value='$month_from'>
					<input type='hidden' name='month_to' value='$month_to'>
					<input type='hidden' name='heading_1' value='$heading_1'>
					<input type='hidden' name='heading_2' value='$heading_2'>
					<input type='hidden' name='heading_3' value='$heading_3'>
					<input type='hidden' name='heading_4' value='$heading_4'>
					<input type='hidden' name='last_year' value='$last_year' />
					<input type='hidden' name='viewyear' value='$viewyear' />
					<input type='hidden' name='zero_balance' value='$zero_balance'>
					<input type='hidden' name='debit_credit' value='$debit_credit'>";
		}

		$totcols = 5;
		if (!empty($budget)) ++$totcols;
		if ($last_year) ++$totcols;
				
		$half_left = (int)($totcols/2);
		$half_right = $totcols - $half_left;
		
		$OUTPUT .= "
			$acc_view_hidden
			<table ".TMPL_tblDflts." width='100%'>
				<tr>
					<td colspan='$half_left' align='left'><h3>$heading_1</h3></td>
					<td colspan='$half_right' align='right'><h3>$heading_2</h3></td>
				</tr>
				<tr>
					<td colspan='$half_left' align='left'><h3>$heading_3</h3></td>
					<td colspan='$half_right' align='right'><h3>$heading_4</h3></td>
				</tr>
				<tr>
					<td colspan='10' align='center'><h3>$date_range<h3></td>
				</tr>
				$tb_out";

		if ($pure) {
			$OUTPUT .= "<tr><td>&nbsp;</td></tr>";
			foreach ($notes as $accid=>$num) {
				db_conn("cubit");
				$sql = "SELECT * FROM saved_tb_accounts WHERE accid='$accid'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve note from Cubit.");
				$note_data = pg_fetch_array($rslt);

				$OUTPUT .= "
					<tr><td>&nbsp;</td></tr>
					<tr>
						<td colspan='10'><u>$num) $note_data[accname]</u></td>
					</tr>
					<tr>
						<td colspan='10'>".nl2br(base64_decode($note_data["note"]))."</u></td>
					</tr>";
			}
		} else {
			$OUTPUT .= "
				<tr>
					<td colspan='6' align='center'>
						<input type='submit' value='Customise'>
						<input type='submit' name='key' value='Print'>
						<input type='submit' name='key' value='Save'>
						<input type='submit' name='key' value='Export to Spreadsheet'>
					</td>
				</tr>
				</table>
				<p>
				<center>
				<table ".TMPL_tblDflts." width='25%'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td align='center'><a href='index-reports.php'>Financials</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td align='center'><a href='index-reports-stmnt.php'>Current Year Financial Statements</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td align='center'><a href='../main.php'>Main Menu</td>
					</tr>
				</table>
				</center>";
		}
		$OUTPUT .= "</form>";
		return $OUTPUT;

	}

	/**
	 * Generates an income statement
	 *
	 * @param array $VARS GET/POST vars
	 * @param bool $pure true if quicklinks/forms/stuff should be excluded
	 * @return string
	 */
	static function incomestmnt($VARS = false, $pure = false) {
		if ($VARS === false) $VARS = array();
		extract($VARS);

		$fields["heading_1"] = COMP_NAME;
		$fields["heading_2"] = date("d/m/Y");
		$fields["heading_3"] = "Income Statement";
		$fields["heading_4"] = "Prepared by: ".USER_NAME;
		$fields["viewyear"] = "core";
		$fields["month_from"] = (int)date("m");
		$fields["month_to"] = (int)date("m");
		$fields["zero_balance"] = "";

		foreach ($fields as $var_name=>$value) {
			if (!isset($$var_name)) {
				$$var_name = $value;
			}
		}

		$cols["this_year_year_to_date"] = true;
		$cols["budget"] = true;
		$cols["this_year_budget"] = true;
		$cols["last_year_same_month"] = true;
		$cols["last_year_year_to_date"] = true;

		foreach ($cols as $fname => $v) {
			if (!isset($$fname) && isset($customized)) {
				$$fname = false;
			} else if (isset($customized)) {
				$$fname = true;
			} else if (!isset($$fname)) {
				$$fname = $v;
			}
		}

		/* hard code the months to display only the "to" month */
		$month_from = $month_to;

		/* create the month range sql */
		if ($month_from > $month_to) {
			$month_range = "(month >= '$month_from' OR month <= '$month_to')";
			$budget_month_range = "(prd >= '$month_from' OR prd <= '$month_to')";
		} else {
			$month_range = "(month >= '$month_from' AND month <= '$month_to')";
			$budget_month_range = "(prd >= '$month_from' AND prd <= '$month_to')";
		}

		// Retrieve the current year from Cubit
		global $PRDMON, $MONPRD;

		if ($viewyear == "core") {
			$sql = "SELECT yrname FROM core.active";
		} else {
			$sql = "SELECT yrname FROM core.year WHERE yrdb='$viewyear'";
		}
		//print "$viewyear: $sql";
		$rslt = db_exec($sql) or errDie("Unable to retrieve current year from Cubit.");
		$year_out = substr(pg_fetch_result($rslt, 0), 1) - (int)($PRDMON[1] > 1);

		if ($month_to < $PRDMON[1]) {
			++$year_out;
		}

		$month_from_out = getMonthName($month_from);
		$month_to_out = getMonthName($month_to);

		if ($viewyear == "core") {
			$last_year_schema = "yr" . (substr(YR_DB, 2) - 1);
		} else {
			$last_year_schema = "yr" . (substr($viewyear, 2) - 1);
		}

		if ($last_year_schema == "yr0") {
			$last_year_same_month = false;
			$last_year_year_to_date = false;
		}

		$month_to_name = strtolower($month_to_out);

		if (empty($zero_balance)) {
			$zb_sql = "((debit-credit)!=0)";
		} else {
			$zb_sql = "(true)";
		}

		$zb_sql = "(true)";

		// Retrieve ALL the values from the trial balance
		//db_conn("cubit");
		//$sql = "SELECT * FROM saved_is_accounts";
		//$tb_rslt = db_exec($sql)
		//	or errDie("Unable to retrieve saved income statement accounts from Cubit.");

		//if (!pg_num_rows($tb_rslt)) {
		db_conn($viewyear);
		$sql = "SELECT DISTINCT accid, topacc, accnum, accname FROM trial_bal WHERE $month_range AND $zb_sql";
		$tb_rslt = db_exec($sql) or errDie("Unable to retrieve trial balance values from Cubit.");
		//}

		/* acc headings */
		$incomes_vals = array (
			"other_income" => "Other Income",
			"sales" => "Sales"
		);

		$expenses_vals = array (
			"expenses" => "Expenses",
			"cost_of_sales" => "Cost of Sales",
			"tax" => "Tax"
		);

		/* acc output */
		$incomes_out = array(
			"itotals" => "",
			"grossprofit" => "",
			"netprof" => ""
		);

		$expenses_out = array(
			"etotals" => "",
			"netproftax" => ""
		);

		foreach ($incomes_vals as $k => $v) {
			$incomes_out[$k] = "";
		}

		foreach ($expenses_vals as $k => $v) {
			$expenses_out[$k] = "";
		}

		/* toptype totals */
		$incomes_tot = array();
		$expenses_tot = array();

		/* acc display order */
		$disp_order = array(
			"sales" => "incomes",
			"cost_of_sales" => "expenses",
			"grossprofit" => "incomes",
			"other_income" => "incomes",
			"itotals" => "incomes",
			"expenses" => "expenses",
			"netprof" => "incomes",
			"tax" => "expenses",
			"netproftax" => "expenses"
		);

		// These arrays are used to categorize the data
		$incomes = array ();
		$expenses = array ();
		while ($tb_data = pg_fetch_array($tb_rslt)) {
			// Now check if this account should be displayed on the income statement
			db_conn("core");
			$sql = "SELECT * FROM accounts WHERE accid='$tb_data[accid]'";
			$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account values from Cubit.");
			$acc_data = pg_fetch_array($acc_rslt);

			if ($acc_data["acctype"] == "I" && !empty($acc_data["toptype"])) {
				$incomes[$acc_data["toptype"]][] = $acc_data["accid"];
			} elseif ($acc_data["acctype"] == "I" && empty($acc_data["toptype"])) {
				$incomes["other_income"][] = $acc_data["accid"];
			}

			if ($acc_data["acctype"] == "E" && !empty($acc_data["toptype"])) {
				$expenses[$acc_data["toptype"]][] = $acc_data["accid"];
			} elseif ($acc_data["acctype"] == "E" && empty($acc_data["toptype"])) {
				$expenses["expenses"][] = $acc_data["accid"];
			}
		}

		if (!isset($key)) $key = "";

		if ($pure === false) {
			switch ($key) {
				case ct("Print"):
				case ct("Save"):
					$pure = true;
					break;
				case ct("Export to Spreadsheet"):
					define("MONEY_NUMERIC", true);
					$pure = true;
					break;
				default:
					$pure = false;
					break;
			}
		}

		// Retrieve the notes
		if ($pure) {
			db_conn("cubit");
			$sql = "SELECT * FROM saved_is_accounts WHERE note!=''";
			$rslt = db_exec($sql) or errDie("Unable to retrieve notes from Cubit.");

			$i = 0;
			$notes = array();
			while ($note_data = pg_fetch_array($rslt)) {
				$i++;

				$notes["$note_data[accid]"] = $i;
			}
		}

		// Table headings
		$report_out = "
		<tr>
			<th align='left' class='thkborder'>Account</th>
			<th align='right' class='thkborder'>Movement During<br />$month_to_out $year_out</th>";

		if ($this_year_year_to_date) $report_out .= "<th align='right' class='thkborder'>This Year<br \>At $month_to_out $year_out</th>";
		if ($budget) $report_out .= "<th align='right' class='thkborder'>Budget for<br />$month_to_out $year_out</th>";
		if ($this_year_budget) $report_out .= "<th align='right' class='thkborder'>This Year Budget <br \> To $month_to_out $year_out</th>";
		if ($last_year_same_month) $report_out .= "<th align='right' class='thkborder'>Last Year<br \>Same Month</th>";
		if ($last_year_year_to_date) $report_out .= "<th align='right' class='thkborder'>Last Year<br \>Year End</th>";

		$report_out .= "<th align='center' class='thkborder thkborder_right'>Notes</th>
		</tr>";

		$grand_totals = array();
		$grand_totals["incomes"]["curr"] = 0;
		$grand_totals["incomes"]["tyytd"] = 0;
		$grand_totals["incomes"]["budget"] = 0;
		$grand_totals["incomes"]["tybudget"] = 0;
		$grand_totals["incomes"]["lytm"] = 0;
		$grand_totals["incomes"]["lyytd"] = 0;

		if ($month_to == $PRDMON[1]) {
			$pfx = "";
		} else {
			$pfx = "_actual";
		}

		// Start creating the output
		if (isset($incomes)) {
			foreach ($incomes as $toptype=>$arlv2) {
				$totals = array();
				$totals["curr"] = 0;
				$totals["tyytd"] = 0;
				$totals["budget"] = 0;
				$totals["tybudget"] = 0;
				$totals["lytm"] = 0;
				$totals["lyytd"] = 0;

				$acc_shown = 0;
				$tmp_out = "";

				foreach ($arlv2 as $accid) {
					/* used to determine if account has zero balance in all columns */
					$disp_acc = false;

					// Retrieve the current trial balance data
					db_conn($viewyear);
					$sql = "SELECT accnum, accname, SUM(debit) AS debit, SUM(credit) AS credit
							FROM trial_bal${pfx}
							WHERE accid='$accid' AND $month_range AND $zb_sql
							GROUP BY accname, accnum";
					$tb_rslt = db_exec($sql) or errDie("Unable to retrieve trial balance information from Cubit.");
					$tb_data = pg_fetch_array($tb_rslt);

					// Retrieve this year, year to date
					if ($this_year_year_to_date) {
						db_conn($viewyear);
						$sql = "SELECT debit,credit FROM trial_bal
								WHERE accid='$accid' AND month='$month_to' AND $zb_sql LIMIT 1";
						$tyytd_rslt = db_exec($sql) or errDie("Unable to retrieve this year, year to date information from Cubit.");

						$tyytd = array();
						$tyytd["debit"] = 0;
						$tyytd["credit"] = 0;

						while ($tyytd_data = pg_fetch_array($tyytd_rslt)) {
							$tyytd["debit"] += $tyytd_data["debit"];
							$tyytd["credit"] += $tyytd_data["credit"];
						}

						$tyytd_out = "
							<td align='right' width='10%'>
								".fsmoney($tyytd["credit"] - $tyytd["debit"])."
							</td>";

						if ($tyytd["credit"] - $tyytd["debit"] != 0) {
							$disp_acc = true;
						}

						$totals["tyytd"] += $tyytd["credit"] - $tyytd["debit"];
					} else {
						$tyytd_out = "";
					}

					if ($budget) {
						// Retrieve the budget amounts from Cubit
						db_conn("cubit");
						$sql = "SELECT sum(amt) as amt FROM buditems WHERE id='$accid' AND prd='$month_to'";
						$rslt = db_exec($sql) or errDie("Unable to retrieve budget items from Cubit.");
						$bud_amt = pg_fetch_result($rslt, 0);

						$budget_out = "
							<td align='right' width='10%'>
								".fsmoney($bud_amt)."
							</td>";

						if ($bud_amt != 0) {
							$disp_acc = true;
						}

						$totals["budget"] += $bud_amt;
					} else {
						$budget_out = "";
					}

					if ($this_year_budget) {
						global $PRDMON, $MONPRD;

						if ($PRDMON[1] == 1) {
							$prdwhere = "prd<='$month_to'";
						} else if ($month_to < $PRDMON[1]) {
							$prdwhere = "(prd>='$PRDMON[1]' OR prd<='$month_to')";
						} else if ($month_to >= $PRDMON[1]) {
							$prdwhere = "(prd>='$PRDMON[1]' AND prd<='$month_to')";
						}

						// Retrieve current year budget from Cubit
						db_conn("cubit");
						$sql = "SELECT SUM(bi.amt)
	     						FROM cubit.budgets b LEFT JOIN cubit.buditems bi
	     						ON b.budid=bi.budid
	     						WHERE bi.id='$accid' AND $prdwhere";
						$rslt = db_exec($sql) or errDie("Unable to retrieve this year budget items from Cubit.");
						$bud_amt = pg_fetch_result($rslt, 0, 0);

						$tybudget_out = "
							<td align='right' width='10%'>
								".fsmoney($bud_amt)."
							</td>";

						if ($bud_amt != 0) {
							$disp_acc = true;
						}

						$totals["tybudget"] += $bud_amt;
					} else {
						$tybudget_out = "";
					}
					
					// Retrieve last year this month trial balance data
					if ($last_year_same_month) {
						db_conn($last_year_schema);
						$sql = "SELECT * FROM $month_to_name WHERE accid='$accid' AND $zb_sql";
						$lytm_rslt = db_exec($sql) or errDie("Unable to retrieve last year this month information from Cubit.");
						$lytm_data = pg_fetch_array($lytm_rslt);

						$lytm_out = "
							<td align='right' width='10%'>
								".fsmoney($lytm_data["credit"] - $lytm_data["debit"])."
							</td>";

						if ($lytm_data["credit"] - $lytm_data["debit"] != 0) {
							$disp_acc = true;
						}

						$totals["lytm"] += $lytm_data["credit"] - $lytm_data["debit"];
					} else {
						$lytm_out = "";
					}
					
					if ($last_year_year_to_date) {
						// Retrieve last year, year to date trial balance data
						db_conn($last_year_schema);
						$sql = "SELECT * FROM year_balance WHERE accid='$accid' AND $zb_sql";
						$lyytd_rslt = db_exec($sql)
						or errDie("Unable to retrieve last year, year to date information from Cubit.");
						$lyytd_data = pg_fetch_array($lyytd_rslt);

						$lyytd_out = "
							<td align='right' width='10%'>
								".fsmoney($lyytd_data["credit"] - $lyytd_data["debit"])."
							</td>";

						if ($lyytd_data["credit"] - $lyytd_data["debit"] != 0) {
							$disp_acc = true;
						}

						$totals["lyytd"] += $lyytd_data["credit"] - $lyytd_data["debit"];
					} else {
						$lyytd_out = "";
					}

					$totals["curr"] += $tb_data["credit"] - $tb_data["debit"];

					if ($tb_data["credit"] - $tb_data["debit"] != 0) {
						$disp_acc = true;
					}

					// account should not be shown, all balances are zero
					if (empty($zero_balance) && !$disp_acc) {
						continue;
					}

					if ($pure && isset($notes[$accid])) {
						$note_out = $notes[$accid];
					} else if (!$pure && $tb_data["accnum"] == "000") {
						$note_out = "<a href='#' onclick='openwindow(\"".SELF."?key=note_view&accid=$accid\")'>Note</a>";
					} else {
						$note_out = "&nbsp";
					}

					++$acc_shown;

					// Table layout
					$tmp_out .= "
						<tr class='bg-odd'>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;<a onClick=\"window.open('drill-view-trans.php?accid=$accid&month_to=$month_to','window$accid','width=900, height=380, scrollbars=yes');\" href='#'>$tb_data[accname]</a></td>
							<td align='right' width='10%'>
								<a href='#' onClick=\"window.open('../core/drill-trans-new.php?dtaccid=$accid&ctaccid=$accid','window$accid','height=420, width=900, scrollbars=yes');\">".fsmoney($tb_data["credit"] - $tb_data["debit"])."</a>
							</td>
							$tyytd_out
							$budget_out
							$tybudget_out
							$lytm_out
							$lyytd_out
							<td witdh='10%' align='center'>$note_out</td>
						</tr>";
				}

				if ($acc_shown >= 0) {
					$tmp_out = "
						<tr>
							<th colspan='10' style='text-align: left;'>".strtoupper($incomes_vals[$toptype])."</th>
						</tr>
						$tmp_out
						<tr class='bg-even'>
							<td><b>Totals</b></td>
							<td align='right'>".fsmoney($totals["curr"])."</td>";

					if ($this_year_year_to_date) $tmp_out .= "<td align='right'>".fsmoney($totals["tyytd"])."</td>";
					if ($budget) $tmp_out .= "<td align='right'>".fsmoney($totals["budget"])."</td>";
					if ($this_year_budget) $tmp_out .= "<td align='right'>".fsmoney($totals["tybudget"])."</td>";
					if ($last_year_same_month) $tmp_out .= "<td align='right'>".fsmoney($totals["lytm"])."</td>";
					if ($last_year_year_to_date) $tmp_out .= "<td align='right'>".fsmoney($totals["lyytd"])."</td>";

					$tmp_out .= "<td>&nbsp;</td></tr>";

					$grand_totals["incomes"]["curr"] += $totals["curr"];
					$grand_totals["incomes"]["tyytd"] += $totals["tyytd"];
					$grand_totals["incomes"]["budget"] += $totals["budget"];
					$grand_totals["incomes"]["tybudget"] += $totals["tybudget"];
					$grand_totals["incomes"]["lytm"] += $totals["lytm"];
					$grand_totals["incomes"]["lyytd"] += $totals["lyytd"];

					$incomes_out[$toptype] .= $tmp_out;
					$incomes_tot[$toptype] = $totals;
				}
			}
		}

		$grand_totals["expenses"]["curr"] = 0;
		$grand_totals["expenses"]["tyytd"] = 0;
		$grand_totals["expenses"]["budget"] = 0;
		$grand_totals["expenses"]["tybudget"] = 0;
		$grand_totals["expenses"]["lytm"] = 0;
		$grand_totals["expenses"]["lyytd"] = 0;

		// Start creating the output
		if (isset($expenses)) {
			foreach ($expenses as $toptype=>$arlv2) {
				/* used to determine if account has zero balance in all columns */
				$disp_acc = false;

				$totals = array();
				$totals["curr"] = 0;
				$totals["tyytd"] = 0;
				$totals["budget"] = 0;
				$totals["tybudget"] = 0;
				$totals["lytm"] = 0;
				$totals["lyytd"] = 0;

				$acc_shown = 0;
				$tmp_out = "";

				foreach ($arlv2 as $accid) {
					$disp_acc = false;

					// Retrieve the current trial balance data
					db_conn($viewyear);
					$sql = "SELECT accnum, accname, SUM(debit) AS debit, SUM(credit) AS credit
							FROM trial_bal${pfx}
							WHERE accid='$accid' AND $month_range AND $zb_sql
							GROUP BY accname, accnum";
					$tb_rslt = db_exec($sql) or errDie("Unable to retrieve trial balance information from Cubit.");
					$tb_data = pg_fetch_array($tb_rslt);

					// Retrieve this year, year to date
					if ($this_year_year_to_date) {
						db_conn($viewyear);
						$sql = "SELECT debit,credit FROM trial_bal
								WHERE accid='$accid' AND month='$month_to' AND $zb_sql LIMIT 1";
						$tyytd_rslt = db_exec($sql) or errDie("Unable to retrieve this year, year to date information from Cubit.");

						$tyytd = array();
						$tyytd["debit"] = 0;
						$tyytd["credit"] = 0;

						while ($tyytd_data = pg_fetch_array($tyytd_rslt)) {
							$tyytd["debit"] += $tyytd_data["debit"];
							$tyytd["credit"] += $tyytd_data["credit"];
						}

						$tyytd_out = "
							<td align='right' width='10%'>
								".fsmoney($tyytd["debit"] - $tyytd["credit"])."
							</td>";

						if ($tyytd["debit"] - $tyytd["credit"] != 0) {
							$disp_acc = true;
						}

						$totals["tyytd"] += $tyytd["debit"] - $tyytd["credit"];
					} else {
						$tyytd_out = "";
					}

					if ($budget) {
						// Retrieve the budget amounts from Cubit
						db_conn("cubit");
						$sql = "SELECT sum(amt) as amt FROM buditems WHERE id='$accid' AND $budget_month_range";
						$rslt = db_exec($sql) or errDie("Unable to retrieve budget items from Cubit.");
						$bud_amt = pg_fetch_result($rslt, 0);

						$budget_out = "
							<td align='right' width='10%'>
								".fsmoney($bud_amt)."
							</td>";

						if ($bud_amt != 0) {
							$disp_acc = true;
						}

						$totals["budget"] += $bud_amt;
					} else {
						$budget_out = "";
					}

					if ($this_year_budget) {
						if ($PRDMON[1] == 1) {
							$prdwhere = "prd<='$month_to'";
						} else if ($month_to < $PRDMON[1]) {
							$prdwhere = "(prd>='$PRDMON[1]' OR prd<='$month_to')";
						} else if ($month_to >= $PRDMON[1]) {
							$prdwhere = "(prd>='$PRDMON[1]' AND prd<='$month_to')";
						}

						// Retrieve current year budget from Cubit
						db_conn("cubit");
						$sql = "SELECT SUM(bi.amt)
	     						FROM cubit.budgets b LEFT JOIN cubit.buditems bi
	     						ON b.budid=bi.budid
	     						WHERE bi.id='$accid' AND $prdwhere";
						$rslt = db_exec($sql) or errDie("Unable to retrieve this year budget items from Cubit.");
						$bud_amt = pg_fetch_result($rslt, 0, 0);

						$tybudget_out = "
							<td align='right' width='10%'>
								".fsmoney($bud_amt)."
							</td>";

						if ($bud_amt != 0) {
							$disp_acc = true;
						}

						$totals["tybudget"] += $bud_amt;
					} else {
						$tybudget_out = "";
					}
					
					// Retrieve last year this month trial balance data
					if ($last_year_same_month) {
						db_conn($last_year_schema);
						$sql = "SELECT * FROM $month_to_name WHERE accid='$accid' AND $zb_sql";
						$lytm_rslt = db_exec($sql)
						or errDie("Unable to retrieve last year this month information from Cubit.");
						$lytm_data = pg_fetch_array($lytm_rslt);

						$lytm_out = "
							<td align='right' width='10%'>
								".fsmoney($lytm_data["debit"] - $lytm_data["credit"])."
							</td>";

						if ($lytm_data["debit"] - $lytm_data["credit"] != 0) {
							$disp_acc = true;
						}

						$totals["lytm"] += $lytm_data["debit"] - $lytm_data["credit"];
					} else {
						$lytm_out = "";
					}

					if ($last_year_year_to_date) {
						// Retrieve last year, year to date trial balance data
						db_conn($last_year_schema);
						$sql = "SELECT * FROM year_balance WHERE accid='$accid' AND $zb_sql";
						$lyytd_rslt = db_exec($sql)
						or errDie("Unable to retrieve last year, year to date information from Cubit.");
						$lyytd_data = pg_fetch_array($lyytd_rslt);

						$lyytd_out = "
							<td align='right' width='10%'>
								".fsmoney($lyytd_data["debit"] - $lyytd_data["credit"])."
							</td>";

						if ($lyytd_data["debit"] - $lyytd_data["credit"] != 0) {
							$disp_acc = true;
						}

						$totals["lyytd"] += $lyytd_data["debit"] - $lyytd_data["credit"];
					} else {
						$lyytd_out = "";
					}

					$totals["curr"] += $tb_data["debit"] - $tb_data["credit"];

					if ($tb_data["debit"] - $tb_data["credit"] != 0) {
						$disp_acc = true;
					}

					if ($pure && isset($notes[$accid])) {
						$note_out = $notes[$accid];
					} else if (!$pure && $tb_data["accnum"] == "000") {
						$note_out = "<a href='#' onclick='openwindow(\"".SELF."?key=note_view&accid=$accid\")'>Note</a>";
					} else {
						$note_out = "&nbsp";
					}

					// account should not be shown, all balances are zero
					if (empty($zero_balance) && !$disp_acc) {
						continue;
					}

					// Table layout
					$tmp_out .= "
						<tr class='bg-odd'>
							<td>&nbsp;&nbsp;&nbsp;&nbsp;<a onClick=\"window.open('drill-view-trans.php?accid=$accid&month_to=$month_to','window$accid','width=900, height=380, scrollbars=yes');\" href='#'>$tb_data[accname]</a></td>
							<td align='right' width='10%'>
								<a href='#' onClick=\"window.open('../core/drill-trans-new.php?dtaccid=$accid&ctaccid=$accid','window$accid','height=420, width=900, scrollbars=yes');\">".fsmoney($tb_data["debit"] - $tb_data["credit"])."</a>
							</td>
							$tyytd_out
							$budget_out
							$tybudget_out
							$lytm_out
							$lyytd_out
							<td witdh='10%' align='center'>$note_out</td>
						</tr>";
				}

				if ($acc_shown >= 0) {
					$tmp_out = "
						<tr>
							<th colspan='10' style='text-align: left;'>".strtoupper($expenses_vals[$toptype])."</th>
						</tr>
						$tmp_out
						<tr class='bg-even'>
							<td><b>Totals</b></td>
							<td align='right'>".fsmoney($totals["curr"])."</td>";

					if ($this_year_year_to_date) $tmp_out .= "<td align='right'>".fsmoney($totals["tyytd"])."</td>";
					if ($budget) $tmp_out .= "<td align='right'>".fsmoney($totals["budget"])."</td>";
					if ($this_year_budget) $tmp_out .= "<td align='right'>".fsmoney($totals["tybudget"])."</td>";
					if ($last_year_same_month) $tmp_out .= "<td align='right'>".fsmoney($totals["lytm"])."</td>";
					if ($last_year_year_to_date) $tmp_out .= "<td align='right'>".fsmoney($totals["lyytd"])."</td>";

					$tmp_out .= "<td>&nbsp</td>
					</tr>";

					$grand_totals["expenses"]["curr"] += $totals["curr"];
					$grand_totals["expenses"]["tyytd"] += $totals["tyytd"];
					$grand_totals["expenses"]["budget"] += $totals["budget"];
					$grand_totals["expenses"]["tybudget"] += $totals["tybudget"];
					$grand_totals["expenses"]["lytm"] += $totals["lytm"];
					$grand_totals["expenses"]["lyytd"] += $totals["lyytd"];

					$expenses_out[$toptype] .= $tmp_out;
					$expenses_tot[$toptype] = $totals;
				}
			}
		}
		$expenses_out["etotals"] .= "
			<tr class='bg-even'>
				<td><b>Grand Totals</b></td>
				<td align='right'>".fsmoney($grand_totals["expenses"]["curr"])."</td>";

		if ($this_year_year_to_date) $expenses_out["etotals"] .= "<td align='right'>".fsmoney($grand_totals["expenses"]["tyytd"])."</td>";
		if ($budget) $expenses_out["etotals"] .= "<td align='right'>".fsmoney($grand_totals["expenses"]["budget"])."</td>";
		if ($this_year_budget) $expenses_out["etotals"] .= "<td align='right'>".fsmoney($grand_totals["expenses"]["tybudget"])."</td>";
		if ($last_year_same_month) $expenses_out["etotals"] .= "<td align='right'>".fsmoney($grand_totals["expenses"]["lytm"])."</td>";
		if ($last_year_year_to_date) $expenses_out["etotals"] .= "<td align='right'>".fsmoney($grand_totals["expenses"]["lyytd"])."</td>";

		$expenses_out["etotals"] .= "
			<td>&nbsp;</td>
		</tr>";

		// Net Profit / Loss
		$net_prof["curr"] = $grand_totals["incomes"]["curr"] - $grand_totals["expenses"]["curr"];
		$net_prof["tyytd"] = $grand_totals["incomes"]["tyytd"] - $grand_totals["expenses"]["tyytd"];
		$net_prof["budget"] = $grand_totals["incomes"]["budget"] - $grand_totals["expenses"]["budget"];
		$net_prof["tybudget"] = $grand_totals["incomes"]["tybudget"] - $grand_totals["expenses"]["tybudget"];
		$net_prof["lytm"] = $grand_totals["incomes"]["lytm"] - $grand_totals["expenses"]["lytm"];
		$net_prof["lyytd"] = $grand_totals["incomes"]["lyytd"] - $grand_totals["expenses"]["lyytd"];

		$expenses_out["etotals"] .= "
			<tr class='bg-even'>
				<td>Net Profit / Loss</td>
				<td align='right'>".fsmoney($net_prof["curr"])."</td>";

		if ($this_year_year_to_date) $expenses_out["etotals"] .= "<td align='right'>".fsmoney($net_prof["tyytd"])."</td>";
		if ($budget) $expenses_out["etotals"] .= "<td align='right'>".fsmoney($net_prof["budget"])."</td>";
		if ($this_year_budget) $expenses_out["etotals"] .= "<td align='right'>".fsmoney($net_prof["tybudget"])."</td>";
		if ($last_year_same_month) $expenses_out["etotals"] .= "<td align='right'>".fsmoney($net_prof["lytm"])."</td>";
		if ($last_year_year_to_date) $expenses_out["etotals"] .= "<td align='right'>".fsmoney($net_prof["lyytd"])."</td>";

		$expenses_out["etotals"] .= "
			<td>&nbsp;</td>
		</tr>";

		if ($month_from == $month_to) {
			$date_range = "$month_from_out $year_out";
		} else {
			$date_range = "$month_from_out TO $month_to_out $year_out";
		}

		/* create the gross profit total */
		$gptotals = array();
		foreach ($incomes_tot["sales"] as $col => $tot) {
			$gptotals[$col] = sprint($tot - $expenses_tot["cost_of_sales"][$col]);
		}

		$tmp_out = "
			<tr class='bg-even'>
				<td><b>Gross Profit</b></td>
				<td align='right'>".fsmoney($gptotals["curr"])."</td>";

		if ($this_year_year_to_date) $tmp_out .= "<td align='right'>".fsmoney($gptotals["tyytd"])."</td>";
		if ($budget) $tmp_out .= "<td align='right'>".fsmoney($gptotals["budget"])."</td>";
		if ($this_year_budget) $tmp_out .= "<td align='right'>".fsmoney($gptotals["tybudget"])."</td>";
		if ($last_year_same_month) $tmp_out .= "<td align='right'>".fsmoney($gptotals["lytm"])."</td>";
		if ($last_year_year_to_date) $tmp_out .= "<td align='right'>".fsmoney($gptotals["lyytd"])."</td>";

		$tmp_out .= "<td>&nbsp;</td></tr>";

		$incomes_out["grossprofit"] .= $tmp_out;

		/* create the gross profit + other inc totals */
		$totals = array();
		foreach ($expenses_tot["cost_of_sales"] as $col => $tot) {
			$totals[$col] = $grand_totals["incomes"][$col] - $tot;
		}

		/* grand totals output */
		$incomes_out["itotals"] .= "
			<tr class='bg-even'>
				<td><b>Grand Totals</b></td>
				<td align='right'>".fsmoney($totals["curr"])."</td>";

		if ($this_year_year_to_date) $incomes_out["itotals"] .= "<td align='right'>".fsmoney($totals["tyytd"])."</td>";
		if ($budget) $incomes_out["itotals"] .= "<td align='right'>".fsmoney($totals["budget"])."</td>";
		if ($this_year_budget) $incomes_out["itotals"] .= "<td align='right'>".fsmoney($totals["tybudget"])."</td>";
		if ($last_year_same_month) $incomes_out["itotals"] .= "<td align='right'>".fsmoney($totals["lytm"])."</td>";
		if ($last_year_year_to_date) $incomes_out["itotals"] .= "<td align='right'>".fsmoney($totals["lyytd"])."</td>";

		$incomes_out["itotals"] .= "
			<td>&nbsp;</td>
		</tr>";

		/* net/profit b4 tax output */
		$netprof = array();
		foreach ($grand_totals["incomes"] as $col => $tot) {
			$netprof[$col] = sprint($tot - $grand_totals["expenses"][$col] + $expenses_tot["tax"][$col]);
		}

		$incomes_out["netprof"] = "
			<tr class='bg-even'>
				<td><b>Net Profit</b></td>
				<td align='right'>".fsmoney($netprof["curr"])."</td>";

		if ($this_year_year_to_date) $incomes_out["netprof"] .= "<td align='right'>".fsmoney($netprof["tyytd"])."</td>";
		if ($budget) $incomes_out["netprof"] .= "<td align='right'>".fsmoney($netprof["budget"])."</td>";
		if ($this_year_budget) $incomes_out["netprof"] .= "<td align='right'>".fsmoney($netprof["tybudget"])."</td>";
		if ($last_year_same_month) $incomes_out["netprof"] .= "<td align='right'>".fsmoney($netprof["lytm"])."</td>";
		if ($last_year_year_to_date) $incomes_out["netprof"] .= "<td align='right'>".fsmoney($netprof["lyytd"])."</td>";

		$incomes_out["netprof"] .= "
			<td>&nbsp;</td>
		</tr>";

		/* net/profit after tax output */
		$netprof = array();
		foreach ($grand_totals["incomes"] as $col => $tot) {
			$netprof[$col] = sprint($tot - $grand_totals["expenses"][$col]);
		}

		$expenses_out["netproftax"] = "
			<tr class='bg-even'>
				<td><b>Net Profit After Tax</b></td>
				<td align='right'>".fsmoney($netprof["curr"])."</td>";

		if ($this_year_year_to_date) $expenses_out["netproftax"] .= "<td align='right'>".fsmoney($netprof["tyytd"])."</td>";
		if ($budget) $expenses_out["netproftax"] .= "<td align='right'>".fsmoney($netprof["budget"])."</td>";
		if ($this_year_budget) $expenses_out["netproftax"] .= "<td align='right'>".fsmoney($netprof["tybudget"])."</td>";
		if ($last_year_same_month) $expenses_out["netproftax"] .= "<td align='right'>".fsmoney($netprof["lytm"])."</td>";
		if ($last_year_year_to_date) $expenses_out["netproftax"] .= "<td align='right'>".fsmoney($netprof["lyytd"])."</td>";

		$expenses_out["netproftax"] .= "
			<td>&nbsp;</td>
		</tr>";

		foreach ($disp_order as $ind => $tp) {
			$var = &${"${tp}_out"};

			if (empty($var[$ind])) continue;

			$report_out .= $var[$ind];//."<tr><td>&nbsp;</td></tr>";
		}
		
		$totcols = 3;
		if ($this_year_year_to_date) ++$totcols;
		if ($budget) ++$totcols;
		if ($this_year_budget) ++$totcols;
		if ($last_year_same_month) ++$totcols;
		if ($last_year_year_to_date) ++$totcols;
		
		$half_left = (int)($totcols/2);
		$half_right = $totcols - $half_left;		

		// Layout
		$OUTPUT = "
			<form method='post' action='".SELF."'>
				<input type='hidden' name='key' value='customize'>
				<input type='hidden' name='heading_1' value='$heading_1'>
				<input type='hidden' name='heading_2' value='$heading_2'>
				<input type='hidden' name='heading_3' value='$heading_3'>
				<input type='hidden' name='heading_4' value='$heading_4'>
				<input type='hidden' name='viewyear' value='$viewyear' />
				<input type='hidden' name='month_from' value='$month_from'>
				<input type='hidden' name='month_to' value='$month_to'>
				<input type='hidden' name='last_year_same_month' value='$last_year_same_month'>
				<input type='hidden' name='this_year_year_to_date' value='$this_year_year_to_date'>
				<input type='hidden' name='last_year_year_to_date' value='$last_year_year_to_date'>
				<input type='hidden' name='budget' value='$budget'>
				<input type='hidden' name='this_year_budget' value='$this_year_budget'>
				<input type='hidden' name='zero_balance' value='$zero_balance'>
			<table ".TMPL_tblDflts." width='100%'>
				<tr>
					<td colspan='$half_left' align='left'><h3>$heading_1</h3></td>
					<td colspan='$half_right' align='right'><h3>$heading_2</h3></td>
				</tr>
				<tr>
					<td colspan='$half_left' align='left'><h3>$heading_3</h3></td>
					<td colspan='$half_right' align='right'><h3>$heading_4</h3></td>
				</tr>
				<tr>
					<td colspan='10' align='center'><h3>$date_range</h3></td>
				</tr>
				$report_out";

		if ($pure) {
			$OUTPUT .= "<tr><td>&nbsp;</td></tr>";
			foreach ($notes as $accid=>$num) {
				db_conn("cubit");
				$sql = "SELECT * FROM saved_is_accounts WHERE accid='$accid'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve note from Cubit.");
				$note_data = pg_fetch_array($rslt);

				$OUTPUT .= "
					<tr><td>&nbsp;</td></tr>
					<tr>
						<td colspan='10'><u>$num) $note_data[accname]</u></td>
					</tr>
					<tr>
						<td colspan='10'>".nl2br(base64_decode($note_data["note"]))."</u></td>
					</tr>";
			}
		} else {
			$OUTPUT .= "
				<tr>
					<td align='center' colspan='10'>
						<input type='submit' value='Customise'>
						<input type='submit' name='key' value='Print'>
						<input type='submit' name='key' value='Save'>
						<input type='submit' name='key' value='Export to Spreadsheet'>
					</td>
				</tr>
			</table>
			</form>
			<p>
			<center>
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
			</table>
			</center>";
		}
		return $OUTPUT;

	}

	/**
	 * Generates a balance sheet
	 *
	 * @param array $VARS GET/POST vars
	 * @param bool $pure true if quicklinks/forms/stuff should be excluded
	 * @return string
	 */
	static function balsheet($VARS = false, $pure = false) {
		if ($VARS === false) $VARS = array();
		extract($VARS);

		// Default values
		$fields = array();
		$fields["heading_1"] = COMP_NAME;
		$fields["heading_2"] = date("d/m/Y");
		$fields["heading_3"] = "Balance Sheet";
		$fields["heading_4"] = "Prepared by: ".USER_NAME;
		$fields["viewyear"] = "core";
		$fields["capital_employed_out"] = "Capital Employed";
		$fields["employment_of_capital_out"] = "Employment of Capital";
		$fields["month_from"] = (int)date("m");
		$fields["month_to"] = (int)date("m");
		$fields["zero_balance"] = "";

		foreach ($fields as $var_name=>$value) {
			if (!isset($$var_name)) {
				$$var_name = $value;
			}
		}

		$cols["this_year_movement_to_date"] = true;
		$cols["this_year_year_to_date"] = true;
		$cols["budget"] = true;
		$cols["this_year_budget"] = true;
		$cols["last_year_same_month"] = false;
		$cols["last_year_year_to_date"] = true;

		foreach ($cols as $fname => $v) {
			if (!isset($$fname) && isset($customized)) {
				$$fname = false;
			} else if (isset($customized)) {
				$$fname = true;
			} else if (!isset($$fname)) {
				$$fname = $v;
			}
		}

		// Current Profit / Loss -------------------------------------------------
		// Initialize output variables
		$fixed_asset = "";
		$investments = "";
		$other_fixed_asset = "";
		$current_asset = "";
		$share_capital = "";
		$retained_income = "";
		$shareholders_loan = "";
		$non_current_liability = "";
		$long_term_borrowing = "";
		$other_long_term_liability = "";
		$current_liability = "";
		$tax = "";

		// Initialize the totals
		$total = array (
			"fixed_asset" => 0.00,
			"investments" => 0.00,
			"other_fixed_asset" => 0.00,
			"current_asset" => 0.00,
			"share_capital" => 0.00,
			"retained_income" => 0.00,
			"shareholders_loan" => 0.00,
			"non_current_liability" => 0.00,
			"long_term_borrowing" => 0.00,
			"other_long_term_liability" => 0.00,
			"current_liability" => 0.00,
			"tax" => 0.00,
			"TOTAL" => array("assets", "equity")
		);

		// For the current profit/loss at retained income ------------------------
		if (empty($zero_balance)) {
			$zb_sql = "(debit!=0 OR credit!=0)";
		} else {
			$zb_sql = "(true)";
		}
		$zb_sql = "(true)";

		/* hard code so only one month is used */
		$month_from = $month_to;

		/* hard code so column isn't displayed */
		$this_year_budget = false;
		//$this_year_year_to_date = false;

		// Retrieve the current year from Cubit
		global $PRDMON, $MONPRD;

		if ($viewyear == "core") {
			$sql = "SELECT yrname FROM core.active";
		} else {
			$sql = "SELECT yrname FROM core.year WHERE yrdb='$viewyear'";
		}
		$rslt = db_exec($sql) or errDie("Unable to retrieve current year from Cubit.");
		$year_out = substr(pg_fetch_result($rslt, 0), 1) - (int)($PRDMON[1] > 1);

		if ($month_to < $PRDMON[1]) {
			++$year_out;
		}

		$month_from_out = getMonthName($month_from);
		$month_to_out = getMonthName($month_to);

		if ($viewyear == "core") {
			$last_year_schema = "yr" . (substr(YR_DB, 2) - 1);
		} else {
			$last_year_schema = "yr" . (substr($viewyear, 2) - 1);
		}

		$month_to_name = strtolower($month_to_out);

		/* create the month range sql */
		if ($month_from > $month_to) {
			$month_range = "(month >= '$month_from' OR month <= '$month_to')";
			$budget_month_range = "(prd >= '$month_from' OR prd <= '$month_to')";
		} else {
			$month_range = "(month >= '$month_from' AND month <= '$month_to')";
			$budget_month_range = "(prd >= '$month_from' AND prd <= '$month_to')";
		}

		if (!isset($key)) $key = "";

		if ($pure === false) {
			switch ($key) {
				case ct("Print"):
				case ct("Save"):
					$pure = true;
					break;
				case ct("Export to Spreadsheet"):
					define("MONEY_NUMERIC", true);
					$pure = true;
					break;
				default:
					$pure = false;
					break;
			}
		}

		if ($pure) {
			// Retrieve the notes
			db_conn("cubit");
			$sql = "SELECT * FROM saved_bs_accounts WHERE note!=''";
			$rslt = db_exec($sql) or errDie("Unable to retrieve notes from Cubit.");

			$i = 0;
			$notes = array();
			while ($note_data = pg_fetch_array($rslt)) {
				$i++;

				$notes["$note_data[accid]"] = $i;
			}
		}

		// -----------------------------------------------------------------------

		$ar_cats = array (
			"fixed_asset" => "Fixed Assets",
			"investments" => "Investments",
			"other_fixed_asset" => "Other Fixed Assets",
			"current_asset" => "Current Assets",
			"share_capital" => "Share Capital",
			"retained_income" => "Retained Income",
			"shareholders_loan" => "Shareholders Loan",
			"non_current_liability" => "Non-current Liabilities",
			"long_term_borrowing" => "Long Term Borrowings",
			"other_long_term_liability" => "Other Long Term Liabilities",
			"current_liability" => "Current Liabilities"
		);

		$ar_assets = array(
			"fixed_asset",
			"investments",
			"other_fixed_asset",
			"current_asset"
		);

		$ar_equity = array(
			"share_capital",
			"retained_income",
			"shareholders_loan",
			"non_current_liability",
			"long_term_borrowing",
			"other_long_term_liability",
			"current_liability"
		);

		foreach ($ar_assets as $var_name) {
			$$var_name = "";
		}

		foreach ($ar_equity as $var_name) {
			$$var_name = "";
		}

		$curr_month = date("m");

		/* calculate retained income */
		if ($last_year_schema == "yr0") {
			$last_year_same_month = false;
			$last_year_year_to_date = false;

			$prevyear_profit_loss_total = 0;
			$prevyear_profit_loss_total_ytd = 0;
		} else {
			$prevyear_profit_loss_total = financialStatements::balsheet_GetProfitLoss(false, $month_to, $last_year_schema);
			$prevyear_profit_loss_total_ytd = financialStatements::balsheet_GetProfitLoss(true, $PRDMON[12], $last_year_schema);
		}

		$current_profit_loss_total = -financialStatements::balsheet_GetProfitLoss(false, $month_to, $viewyear);
		$current_profit_loss_total_ytd = financialStatements::balsheet_GetProfitLoss(true, $month_to, $viewyear);

		/* calculate movement */
		$mon0_profit_loss_total = financialStatements::balsheet_GetProfitLoss(true, 0, $viewyear);
		$current_profit_loss_total_mtd = 0 - ($current_profit_loss_total_ytd - $mon0_profit_loss_total);

		//$totals["equity"] += $current_profit_loss_total;
		//$totals["tymtd"] += $current_profit_loss_total_mtd;
		//$totals["tyytd"] += $current_profit_loss_total_ytd;

		//if ($last_year_same_month) $totals["lysm"] += $prevyear_profit_loss_total;
		//if ($last_year_year_to_date) $totals["lyytd"] += $prevyear_profit_loss_total_ytd;

		/* calculate retained income budget */
		$ri_calc = new dbQuery(DB_SQL,
		"SELECT SUM(CASE WHEN budtype='inc' THEN (amt) ELSE (amt*-1) END) AS amt
				FROM cubit.budgets b JOIN cubit.buditems bi ON(bi.budid=b.budid)
				WHERE b.budfor='acc' AND (b.budtype='inc' OR b.budtype='exp') AND bi.prd='$month_to'"
		);
		$ri_calc->run();

		$retained_income_budget = $ri_calc->fetch_result(0, 0);
		//$totals["budget"] += $retained_income_budget;

		// Retrieve saved balance sheet information
		//db_conn("cubit");
		//$sql = "SELECT * FROM saved_bs_accounts";
		//$tb_rslt = db_exec($sql) or errDie("Unable to retrieve accounts list from Cubit.");

		//if (pg_num_rows($tb_rslt) == 0 || !isset($acc_view)) {
		db_conn($viewyear);
		$sql = "SELECT DISTINCT accid, topacc, accnum, accname FROM trial_bal WHERE div='".USER_DIV."' AND $month_range AND $zb_sql";
		$tb_rslt = db_exec($sql) or errDie("Unable to retrieve trial balance information from Cubit.");
		//}

		$assets_out = "";
		$equity_out = "";
		$report_out = "";

		$tymtd_out = "";
		$tyytd_out = "";
		$budget_out = "";
		$tybudget_out = "";
		$lysm_out = "";
		$lyytd_out = "";

		while ($tb_data = pg_fetch_array($tb_rslt)) {
			db_conn("core");
			$sql = "SELECT * FROM accounts WHERE accid='$tb_data[accid]'";
			$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
			$acc_data = pg_fetch_array($acc_rslt);

			if (isset($tb_data["toptype"])) {
				$acc_data["toptype"] = $tb_data["toptype"];
			}

			if ($acc_data["acctype"] == "B") {
				if (financialStatements::balsheet_type($acc_data["toptype"]) == "assets") {
					$assets[$acc_data["toptype"]][] = $acc_data["accid"];
				} elseif (financialStatements::balsheet_type($acc_data["toptype"]) == "equity") {
					$equity[$acc_data["toptype"]][] = $acc_data["accid"];
				}
			}
		}

		$totals["assets"] = 0.00;
		$totals["lysm"] = 0.00;
		$totals["tymtd"] = 0.00;
		$totals["tyytd"] = 0.00;
		$totals["lyytd"] = 0.00;
		$totals["budget"] = 0.00;
		$totals["tybudget"] = 0.00;

		/* ASSETS - CAPITAL EMPLOYED */
		if (isset($assets)) {
			foreach ($assets as $toptype=>$arlv2) {
				foreach ($assets[$toptype] as $accid) {
					/* determines whether a figure in any of the accounts */
					$disp_acc = false;

					db_conn($viewyear);
					$sql = "SELECT * FROM trial_bal_actual
							WHERE accid='$accid' AND month='$month_to' AND $zb_sql";

					$tb_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
					$tb_data = pg_fetch_array($tb_rslt);

					// Retrieve this year, movement to date
					if ($this_year_movement_to_date) {
						$tymtd = array();
						$tymtd["debit"] = 0;
						$tymtd["credit"] = 0;

						db_conn($viewyear);
						/* current year, year to date */
						$sql = "SELECT debit,credit FROM trial_bal
								WHERE accid='$accid' AND month='$month_to' AND $zb_sql LIMIT 1";
						$tymtd_rslt = db_exec($sql) or errDie("Unable to retrieve this year, year to date information from Cubit.");

						while ($tymtd_data = pg_fetch_array($tymtd_rslt)) {
							$tymtd["debit"] += $tymtd_data["debit"];
							$tymtd["credit"] += $tymtd_data["credit"];
						}

						/* deduct previous year end of year amounts */
						$sql = "SELECT debit,credit FROM trial_bal
								WHERE accid='$accid' AND month='0' AND $zb_sql LIMIT 1";
						$tymtd_rslt = db_exec($sql) or errDie("Unable to retrieve this year, year to date information from Cubit.");

						while ($tymtd_data = pg_fetch_array($tymtd_rslt)) {
							$tymtd["debit"] -= $tymtd_data["debit"];
							$tymtd["credit"] -= $tymtd_data["credit"];
						}

						$amt = financialStatements::balsheet_calculate($toptype, $tymtd["debit"], $tymtd["credit"]);

						$tymtd_out = "
							<td align='right' width='10%'>
								".fsmoney($amt)."
							</td>";

						if ($amt != 0) {
							$disp_acc = true;
						}

						$totals["tymtd"] += $amt;
					} else {
						$tymtd_out = "";
					}

					// Retrieve this year, year to date
					if ($this_year_year_to_date) {
						db_conn($viewyear);
						$sql = "SELECT debit,credit FROM trial_bal
								WHERE accid='$accid' AND month='$month_to' AND $zb_sql LIMIT 1";

						$tyytd_rslt = db_exec($sql) or errDie("Unable to retrieve this year, year to date information from Cubit.");

						$tyytd = array();
						$tyytd["debit"] = 0;
						$tyytd["credit"] = 0;

						while ($tyytd_data = pg_fetch_array($tyytd_rslt)) {
							$tyytd["debit"] += $tyytd_data["debit"];
							$tyytd["credit"] += $tyytd_data["credit"];
						}

						$amt = financialStatements::balsheet_calculate($toptype, $tyytd["debit"], $tyytd["credit"]);
						$tyytd_out = "
							<td align='right' width='10%'>
								".fsmoney($amt)."
							</td>";

						if ($amt != 0) {
							$disp_acc = true;
						}

						$totals["tyytd"] += $amt;
					} else {
						$tyytd_out = "";
					}

					// Budget values
					if ($budget) {
						db_conn("cubit");
						$sql = "SELECT SUM(amt) AS amt FROM buditems WHERE prd='$month_to' AND id='$accid'";
						$bud_rslt = db_exec($sql) or errDie("Unable to retrieve budget values from Cubit.");
						$bud_amt = pg_fetch_result($bud_rslt, 0, 0);

						$budget_out = "<td align='right' width='10%'>".fsmoney($bud_amt)."</td>";

						if ($bud_amt != 0) {
							$disp_acc = true;
						}

						$totals["budget"] += $bud_amt;
					} else {
						$budget_out = "";
					}

					if ($this_year_budget) {
						if ($PRDMON[1] == 1) {
							$prdwhere = "prd<='$month_to'";
						} else if ($month_to < $PRDMON[1]) {
							$prdwhere = "(prd>='$PRDMON[1]' OR prd<='$month_to')";
						} else if ($month_to >= $PRDMON[1]) {
							$prdwhere = "(prd>='$PRDMON[1]' AND prd<='$month_to')";
						}

						// Retrieve current year budget from Cubit
						db_conn("cubit");
						$sql = "SELECT SUM(bi.amt)
	     						FROM cubit.budgets b LEFT JOIN cubit.buditems bi
	     						ON b.budid=bi.budid
	     						WHERE bi.id='$accid' AND $prdwhere";
						$rslt = db_exec($sql) or errDie("Unable to retrieve this year budget items from Cubit.");
						$bud_amt = pg_fetch_result($rslt, 0, 0);

						$tybudget_out = "
							<td align='right' width='10%'>
								".fsmoney($bud_amt)."
							</td>";

						if ($bud_amt != 0) {
							$disp_acc = true;
						}

						$totals["tybudget"] += $bud_amt;
					} else {
						$tybudget_out = "";
					}

					// Retrieve last year this month trial balance data
					if ($last_year_same_month) {
						db_conn($last_year_schema);
						$sql = "SELECT * FROM $month_to_name WHERE accid='$accid' AND $zb_sql";
						$lytm_rslt = db_exec($sql) or errDie("Unable to retrieve last year this month information from Cubit.");
						$lytm_data = pg_fetch_array($lytm_rslt);

						$amt = financialStatements::balsheet_calculate($toptype, $lytm_data["debit"], $lytm_data["credit"]);

						$lysm_out = "
							<td align='right' width='10%'>
								".fsmoney($amt)."
							</td>";

						if ($amt != 0) {
							$disp_acc = true;
						}

						$totals["lysm"] += $amt;
					} else {
						$lysm_out = "";
					}

					// Last year's values
					if ($last_year_year_to_date) {
						db_conn($last_year_schema);
						$sql = "SELECT * FROM year_balance WHERE accid='$accid' AND $zb_sql";
						$py_rslt = db_exec($sql) or errDie("Unable to retrieve previous year trial balance from Cubit.");
						$py_data = pg_fetch_array($py_rslt);

						$amt = financialStatements::balsheet_calculate($toptype, $py_data["debit"], $py_data["credit"]);

						$lyytd_out = "
							<td align='right' width='10%'>
								".fsmoney($amt)."
							</td>";

						if ($amt != 0) {
							$disp_acc = true;
						}

						$totals["lyytd"] += $amt;
					} else {
						$lyytd_out = "";
					}

					if (empty($zero_balance) && !$disp_acc && ($tb_data["credit"] == $tb_data["debit"])) {
						continue;
					}

					if ($pure && isset($notes[$accid])) {
						$note_out = $notes[$accid];
					} else if (!$pure) {
						$note_out = "<a href='#' onclick='openwindow(\"".SELF."?key=note_view&accid=$accid\")'>Note</a>";
					} else {
						$note_out = "&nbsp;";
					}

					$amt = financialStatements::balsheet_calculate($toptype, $tb_data["debit"], $tb_data["credit"]);

					$$toptype .= "
						<tr class='bg-odd'>
							<td><a onClick=\"window.open('drill-view-trans.php?accid=$accid&month_to=$month_to','window$accid','width=900, height=380, scrollbars=yes');\" href='#'>$tb_data[accname]</a></td>
							<td align='right' width='10%'><a href='#' onClick=\"window.open('../core/drill-trans-new.php?dtaccid=$accid&ctaccid=$accid','window$accid','height=420, width=900, scrollbars=yes');\">".fsmoney($amt)."</a></td>
							$tymtd_out
							$tyytd_out
							$budget_out
							$tybudget_out
							$lysm_out
							$lyytd_out
							<td align='right'>$note_out</td>
						</tr>";

					// add up the totals
					$totals["assets"] += $amt;
				}
			}

			// Decide which categories to display
			/*			if (!empty($fixed_asset))
			$assets_out .= "<tr><th colspan='10' class='balsheet_cats'>- Fixed Assets</th></tr>$fixed_asset";
			if (!empty($investments))
			$assets_out .= "<tr><th colspan='10' class='balsheet_cats'>- Investments</th></tr>$investments";
			if (!empty($other_fixed_asset))
			$assets_out .= "<tr><th colspan='10' class='balsheet_cats'>- Other Fixed Assets</th></tr>$other_fixed_asset";
			if (!empty($current_asset))
			$assets_out .= "<tr><th colspan='10' class='balsheet_cats'>- Current Assets</th></tr>$current_asset";*/
			foreach ($ar_assets as $ctoptype) {
				if (!empty($$ctoptype)) {
					$assets_out .= "
						<tr>
							<th colspan='10' class='balsheet_cats'>- $ar_cats[$ctoptype]</th>
						</tr>
						${$ctoptype}";
				}
			}
		}
		// Assets total output

		if ($this_year_movement_to_date) $tymtd_out = "<td align='right'>".fsmoney($totals["tymtd"])."</td>";
		if ($this_year_year_to_date) $tyytd_out = "<td align='right'>".fsmoney($totals["tyytd"])."</td>";
		if ($budget) $budget_out = "<td align='right'>".fsmoney($totals["budget"])."</td>";
		if ($this_year_budget) $tybudget_out = "<td align='right'>".fsmoney($totals["tybudget"])."</td>";
		if ($last_year_same_month) $lysm_out = "<td align='right'>".fsmoney($totals["lysm"])."</td>";
		if ($last_year_year_to_date) $lyytd_out = "<td align='right'>".fsmoney($totals["lyytd"])."</td>";

		//$balance2 = $totals["assets"];
		$balance2 = $totals["tyytd"];

		
// 		$assets_out .= "
// 			<tr class='bg-even'>
// 				<td>&nbsp;</td>
// 				<td align='right'>".fsmoney($totals["assets"], 2)."</td>
// 				$tymtd_out
// 				$tyytd_out
// 				$budget_out
// 				$tybudget_out
// 				$lysm_out
// 				$lyytd_out
// 				<td>&nbsp</td>
// 			</tr>";
			$assets1 .= "
				<tr class='bg-even'>
					<td>&nbsp;</td>
					<td align='right'>".fsmoney($totals["assets"], 2)."</td>
					$tymtd_out
					$tyytd_out
					$budget_out
					$tybudget_out
					$lysm_out
					$lyytd_out
					<td>&nbsp</td>
				</tr>";

			$assets2 .= "
				<tr class='bg-even'>
					<td>&nbsp;</td>
					<td align='right'>".fsmoney($totals["assets"], 2)."</td>
					$tymtd_out
					<td><p class='err'>ERROR: Database Corruption Detected. Please Contact Your Dealer</p></td>
					$budget_out
					$tybudget_out
					$lysm_out
					$lyytd_out
					<td>&nbsp</td>
				</tr>";



// 		print "assets:$totals[assets]<br>";
// 		print "lysm:$totals[lysm]<br>";
// 		print "tymtd:$totals[tymtd]<br>";
// 		print "tyytd:$totals[tyytd]<br>";
// 		print "lyytd:$totals[lyytd]<br>";
// 		print "budget:$totals[budget]<br>";
// 		print "tybudget:$totals[tybudget]<br><br>";

		$assets_total_tyytd = $totals['tyytd'];

		$totals = array();
		$totals["equity"] = 0.00;
		$totals["tymtd"] = 0.00;
		$totals["tyytd"] = 0.00;
		$totals["budget"] = 0.00;
		$totals["tybudget"] = 0.00;
		$totals["lysm"] = 0.00;
		$totals["lyytd"] = 0.00;

		/* EQUITY - LIABILITIES - EMPLOYEMENT OF CAPITAL */
		if (isset($equity)) {
			foreach ($equity as $toptype => $arlv2) {
				foreach ($equity[$toptype] as $accid) {
					$disp_acc = false;

					db_conn($viewyear);
					$sql = "SELECT * FROM trial_bal_actual
							WHERE accid='$accid' AND month='$month_to' AND $zb_sql";
					$tb_rslt = db_exec($sql) or errDie("Unable to retrieve account information from Cubit.");
					$tb_data = pg_fetch_array($tb_rslt);

					if ($tb_data["topacc"] == "5200" && $tb_data["accnum"] == "000") {
						$retinc_acc = true;
					} else {
						$retinc_acc = false;
					}

					// Retrieve this year, movement to date
					if ($this_year_movement_to_date) {
						$tymtd = array();
						$tymtd["debit"] = 0;
						$tymtd["credit"] = 0;

						db_conn($viewyear);
						/* current year, year to date */
						$sql = "SELECT debit,credit FROM trial_bal
								WHERE accid='$accid' AND month='$month_to' AND $zb_sql LIMIT 1";
						$tymtd_rslt = db_exec($sql) or errDie("Unable to retrieve this year, year to date information from Cubit.");

						while ($tymtd_data = pg_fetch_array($tymtd_rslt)) {
							$tymtd["debit"] += $tymtd_data["debit"];
							$tymtd["credit"] += $tymtd_data["credit"];
						}

						/* deduct previous year end of year amounts */
						$sql = "SELECT debit,credit FROM trial_bal
								WHERE accid='$accid' AND month='0' AND $zb_sql LIMIT 1";
						$tymtd_rslt = db_exec($sql) or errDie("Unable to retrieve this year, year to date information from Cubit.");

						while ($tymtd_data = pg_fetch_array($tymtd_rslt)) {
							$tymtd["debit"] -= $tymtd_data["debit"];
							$tymtd["credit"] -= $tymtd_data["credit"];
						}

						//$amt = financialStatements::balsheet_calculate($toptype, $tymtd["debit"], $tymtd["credit"]);
						$amt = $tymtd["debit"] - $tymtd["credit"];

						if ($retinc_acc) {
							$amt += $current_profit_loss_total_mtd;
						}

						$tymtd_out = "
							<td align='right' width='10%'>
								".fsmoney($amt)."
							</td>";

						if ($amt != 0) {
							$disp_acc = true;
						}

						$totals["tymtd"] += $amt;//financialStatements::balsheet_calculate($toptype, $tymtd["debit"], $tymtd["credit"]);
					} else {
						$tymtd_out = "";
					}

					// Retrieve this year, year to date
					if ($this_year_year_to_date) {
						db_conn($viewyear);
						$sql = "SELECT debit,credit FROM trial_bal
								WHERE accid='$accid' AND month='$month_to' AND $zb_sql LIMIT 1";
						$tyytd_rslt = db_exec($sql) or errDie("Unable to retrieve this year, year to date information from Cubit.");

						$tyytd = array();
						$tyytd["debit"] = 0;
						$tyytd["credit"] = 0;

						while ($tyytd_data = pg_fetch_array($tyytd_rslt)) {
							$tyytd["debit"] += $tyytd_data["debit"];
							$tyytd["credit"] += $tyytd_data["credit"];
						}

						$amt = financialStatements::balsheet_calculate($toptype, $tyytd["debit"], $tyytd["credit"]);

						if ($retinc_acc) {
							$amt += $current_profit_loss_total_ytd;
						}

						$tyytd_out = "
							<td align='right' width='10%'>
								".fsmoney($amt)."
							</td>";

						if ($amt != 0) {
							$disp_acc = true;
						}

						$totals["tyytd"] += $amt;
					} else {
						$tyytd_out = "";
					}

					// Budget values
					if ($budget) {
						db_conn("cubit");
						$sql = "SELECT SUM(amt) FROM buditems WHERE prd='$month_to' AND id='$accid'";
						$bud_rslt = db_exec($sql) or errDie("Unable to retrieve budget values from Cubit.");
						$bud_amt = pg_fetch_result($bud_rslt, 0, 0);

						if ($retinc_acc) {
							$bud_amt += $retained_income_budget;
						}

						$budget_out = "<td align='right' width='10%'>".fsmoney($bud_amt)."</td>";

						if ($bud_amt != 0) {
							$disp_acc = true;
						}

						$totals["budget"] += $bud_amt;
					} else {
						$budget_out = "";
					}

					if ($this_year_budget) {
						if ($PRDMON[1] == 1) {
							$prdwhere = "prd<='$month_to'";
						} else if ($month_to < $PRDMON[1]) {
							$prdwhere = "(prd>='$PRDMON[1]' OR prd<='$month_to')";
						} else if ($month_to >= $PRDMON[1]) {
							$prdwhere = "(prd>='$PRDMON[1]' AND prd<='$month_to')";
						}

						// Retrieve current year budget from Cubit
						db_conn("cubit");
						$sql = "SELECT SUM(bi.amt) 
	     						FROM cubit.budgets b LEFT JOIN cubit.buditems bi 
	     						ON b.budid=bi.budid 
	     						WHERE bi.id='$accid' AND $prdwhere";
						$rslt = db_exec($sql) or errDie("Unable to retrieve this year budget items from Cubit.");
						$bud_amt = pg_fetch_result($rslt, 0, 0);

						if ($bud_amt != 0) {
							$disp_acc = true;
						}

						$totals["tybudget"] += $bud_amt;

						$tybudget_out = "
							<td align='right' width='10%'>
								".fsmoney($bud_amt)."
							</td>";
					} else {
						$tybudget_out = "";
					}

					// Retrieve last year this month trial balance data
					if ($last_year_same_month) {
						db_conn($last_year_schema);
						$sql = "SELECT * FROM $month_to_name WHERE accid='$accid' AND $zb_sql";
						$lytm_rslt = db_exec($sql)
						or errDie("Unable to retrieve last year this month information from Cubit.");
						$lytm_data = pg_fetch_array($lytm_rslt);

						$amt = financialStatements::balsheet_calculate($toptype, $lytm_data["debit"], $lytm_data["credit"]);

						if ($retinc_acc) {
							$amt += $prevyear_profit_loss_total;
						}

						$lysm_out = "
							<td align='right' width='10%'>
								".fsmoney($amt)."
							</td>";

						if ($amt != 0) {
							$disp_acc = true;
						}

						$totals["lysm"] += $amt;
					} else {
						$lysm_out = "";
					}

					// Last year's values
					if ($last_year_year_to_date) {
						db_conn($last_year_schema);
						$sql = "SELECT * FROM year_balance WHERE accid='$accid' AND $zb_sql";
						$py_rslt = db_exec($sql) or errDie("Unable to retrieve previous year trial balance from Cubit.");
						$py_data = pg_fetch_array($py_rslt);

						$amt = financialStatements::balsheet_calculate($toptype, $py_data["debit"], $py_data["credit"]);

						if ($retinc_acc) {
							$amt += $prevyear_profit_loss_total_ytd;
						}

						$lyytd_out = "
							<td align='right' width='10%'>
								".fsmoney($amt)."
							</td>";

						if ($amt != 0) {
							$disp_acc = true;
						}

						$totals["lyytd"] += $amt;
					} else {
						$lyytd_out = "";
					}

					if (empty($zero_balance) && !$disp_acc && ($tb_data["credit"] == $tb_data["debit"])) {
						continue;
					}

					if ($pure && isset($notes[$accid])) {
						$note_out = $notes[$accid];
					} else if (!$pure) {
						$note_out = "<a href='#' onclick='openwindow(\"".SELF."?key=note_view&accid=$accid\")'>Note</a>";
					} else {
						$note_out = "&nbsp;";
					}

					//$amt = financialStatements::balsheet_calculate($toptype, $tb_data["debit"], $tb_data["credit"]);
					$amt = $tb_data["debit"] - $tb_data["credit"];

					if ($retinc_acc) {
						$amt += $current_profit_loss_total;
					}

					$$toptype .= "
						<tr class='bg-odd'>
							<td><a onClick=\"window.open('drill-view-trans.php?accid=$accid&month_to=$month_to','window$accid','width=900, height=380, scrollbars=yes');\" href='#'>$tb_data[accname]</a></td>
							<td align='right' width='10%'><a href='#' onClick=\"window.open('../core/drill-trans-new.php?dtaccid=$accid&ctaccid=$accid','window$accid','height=420, width=900, scrollbars=yes');\">".fsmoney($amt)."</a></td>
							$tymtd_out
							$tyytd_out
							$budget_out
							$tybudget_out
							$lysm_out
							$lyytd_out
							<td align='right'>$note_out</td>
						</tr>";

					$totals["equity"] += $amt;
				}
			}
			// Decide which categories to display
			/*			if (!empty($share_capital))
			$equity_out .= "<tr><th colspan='10' class='balsheet_cats'>- Share Capital</th></tr>$share_capital";
			if (!empty($retained_income))
			$equity_out .= "<tr><th colspan='10' class='balsheet_cats'>- Retained Income</th></tr>$retained_income";
			if (!empty($shareholders_loan))
			$equity_out .= "<tr><th colspan='10' class='balsheet_cats'>- Shareholders Loan</th></tr>$shareholders_loan";
			if (!empty($long_term_borrowing))
			$equity_out .= "<tr><th colspan='10' class='balsheet_cats'>- Long Term Borrowings</th></tr>$long_term_borrowing";
			if (!empty($other_long_term_liability))
			$equity_out .= "<tr><th colspan='10' class='balsheet_cats'>- Other Long Term Liabilities</th></tr>$other_long_term_liability";
			if (!empty($current_liability))
			$equity_out .= "<tr><th colspan='10' class='balsheet_cats'>- Current Liabilities</th></tr>$current_liability";*/

			foreach ($ar_equity as $ctoptype) {
				if (!empty($$ctoptype)) {
					$equity_out .= "
						<tr>
							<th colspan='10' class='balsheet_cats'>- $ar_cats[$ctoptype]</th>
						</tr>
						${$ctoptype}";
				}
			}
		}
		// equity totals output
		/*if ($last_year_same_month) $lysm_out = "<td align='right'>".fsmoney($prevyear_profit_loss_total)."</td>";
		if ($last_year_year_to_date) $lyytd_out = "<td align='right'>".fsmoney($prevyear_profit_loss_total_ytd)."</td>";
		if ($this_year_movement_to_date) $tymtd_out = "<td align='right'>".fsmoney($current_profit_loss_total_mtd)."</td>";
		if ($this_year_year_to_date) $tyytd_out = "<td align='right'>".fsmoney($current_profit_loss_total_ytd)."</td>";
		if ($budget) $budget_out = "<td align='right'>".fsmoney($retained_income_budget)."</td>";
		if ($this_year_budget) $tybudget_out = "<td>&nbsp</td>";

		$equity_out .= "
		<tr class='bg-even'>
		<td>Retained Income</td>
		<td align='right'>".fsmoney($current_profit_loss_total)."</td>
		$lysm_out
		$lyytd_out
		$tymtd_out
		$tyytd_out
		$budget_out
		$tybudget_out
		<td>&nbsp</td>
		</tr>";*/

		if ($this_year_movement_to_date) $tymtd_out = "<td align='right'>".fsmoney($totals["tymtd"])."</td>";
		if ($this_year_year_to_date) $tyytd_out = "<td align='right'>".fsmoney($totals["tyytd"])."</td>";
		if ($budget) $budget_out = "<td align='right'>".fsmoney($totals["budget"])."</td>";
		if ($this_year_budget) $tybudget_out = "<td align='right'>".fsmoney($totals["tybudget"])."</td>";
		if ($last_year_same_month) $lysm_out = "<td align='right'>".fsmoney($totals["lysm"])."</td>";
		if ($last_year_year_to_date) $lyytd_out = "<td align='right'>".fsmoney($totals["lyytd"])."</td>";

		//$balance1 = $totals["equity"];
		$balance1 = $totals["tyytd"];

// 		$equity_out .= "
// 			<tr class='bg-even'>
// 				<td>&nbsp;</td>
// 				<td align='right'>".fsmoney($totals["equity"], 2)."</td>
// 				$tymtd_out
// 				$tyytd_out
// 				$budget_out
// 				$tybudget_out
// 				$lysm_out
// 				$lyytd_out
// 				<td>&nbsp</td>
// 			</tr>";

			$equity1 .= "
				<tr class='bg-even'>
					<td>&nbsp;</td>
					<td align='right'>".fsmoney($totals["equity"], 2)."</td>
					$tymtd_out
					$tyytd_out
					$budget_out
					$tybudget_out
					$lysm_out
					$lyytd_out
					<td>&nbsp</td>
				</tr>";

			$equity2 .= "
				<tr class='bg-even'>
					<td>&nbsp;</td>
					<td align='right'>".fsmoney($totals["equity"], 2)."</td>
					$tymtd_out
					<td><p class='err'>ERROR: Database Corruption Detected. Please Contact Your Dealer</p></td>
					$budget_out
					$tybudget_out
					$lysm_out
					$lyytd_out
					<td>&nbsp</td>
				</tr>";

		if (isset($acc_view)) {
			$acc_view_hidden = "<input type='hidden' name='acc_view' value='$acc_view'>";
		} else {
			$acc_view_hidden = "";
		}

		if ($month_from == $month_to) {
			$date_range = "$month_from_out $year_out";
		} else {
			$date_range = "$month_from_out TO $month_to_out $year_out";
		}

		/* headings */
		$head = "
		<tr>
			<th align='left' class='thkborder thkborder_left'>Account</th>
			<th align='right' class='thkborder'>Movement during<br />$month_to_out $year_out</th>";

		if ($this_year_movement_to_date) $head .= "<th align='right' class='thkborder'>Movement<br />To $month_to_out $year_out</th>";
		if ($this_year_year_to_date) $head .= "<th align='right' class='thkborder'>This Year<br />At $month_to_out $year_out</th>";
		if ($budget) $head .= "<th align='right' class='thkborder'>Budget<br />for $month_to_out $year_out</th>";
		if ($this_year_budget) $head .= "<th align='right' class='thkborder'>Budget<br />To $month_to_out $year_out</th>";
		if ($last_year_same_month) $head .= "<th align='right' class='thkborder'>Last Year<br />At $month_to_out $year_out</th>";
		if ($last_year_year_to_date) $head .= "<th align='right' class='thkborder'>Last Year<br />Year End</th>";

		$head .= "
			<th align='left' class='thkborder thkborder_right'>Note</th>
		</tr>";

		/* calculate colspans to half the total column span */
		$totcols = 3;
		if ($this_year_movement_to_date) ++$totcols;
		if ($this_year_year_to_date) ++$totcols;
		if ($budget) ++$totcols;
		if ($this_year_budget) ++$totcols;
		if ($last_year_same_month) ++$totcols;
		if ($last_year_year_to_date) ++$totcols;
		
		$half_left = (int)($totcols/2);
		$half_right = $totcols - $half_left;

		$OUTPUT = "";

// 		print "equity:$totals[equity]<br>";
// 		print "tymtd:$totals[tymtd]<br>";
// 		print "tyytd:$totals[tyytd]<br>";
// 		print "budget:$totals[budget]<br>";
// 		print "tybudget:$totals[tybudget]<br>";
// 		print "lysm:$totals[lysm]<br>";
// 		print "lyytd:$totals[lyytd]<br>";

		$equity_total_tyytd = $totals['tyytd'];

		$balance1 += 0;
		$balance2 += 0;

		if ((string)$balance1 != (string)$balance2){
			$assets_out .= $assets2;
			$equity_out .= $equity2;
		}else {
			$assets_out .= $assets1;
			$equity_out .= $equity1;
		}

		// Layout
		$OUTPUT .= "
			$acc_view_hidden
			<table ".TMPL_tblDflts." width='100%'>
				<tr>
					<td colspan='$half_left' align='left'><h3>$heading_1</h3></td>
					<td colspan='$half_right' align='right'><h3>$heading_2</h3></td>
				</tr>
				<tr>
					<td colspan='$half_left' align='left'><h3>$heading_3</h3></td>
					<td colspan='$half_right' align='right'><h3>$heading_4</h3></td>
				</tr>
				<tr>
					<td colspan='10' align='center'><h3>$date_range</h3></td>
				</tr>
				<tr>
					<th colspan='10' class='balsheet_cats'><h3>$capital_employed_out</h3></th>
				</tr>
				$head
				$equity_out
				<tr>
					<th colspan='10' class='balsheet_cats'><h3>$employment_of_capital_out</h3></th>
				</tr>
				$head
				$assets_out";

		if ($pure) {
			$OUTPUT .= "<tr><td>&nbsp;</td></tr>";

			$notes_display = "";
			foreach ($notes as $accid=>$num) {
				db_conn("cubit");
				$sql = "SELECT * FROM saved_bs_accounts WHERE accid='$accid'";
				$rslt = db_exec($sql) or errDie("Unable to retrieve note from Cubit.");
				$note_data = pg_fetch_array($rslt);

				$OUTPUT .= "
					<tr><td></td></tr>
					<tr>
						<td colspan='10'><u>$num) $note_data[accname]</u></td>
					</tr>
					<tr>
						<td colspan='10'>".nl2br(base64_decode($note_data["note"]))."</u></td>
					</tr>";
			}
		} else {
			$OUTPUT .= "
					<tr>
						<td colspan='5' align='center'>
						<form method='POST' action='".SELF."'>
							<input type='hidden' name='key' value='customize' />
							<input type='hidden' name='heading_1' value='$heading_1' />
							<input type='hidden' name='heading_2' value='$heading_2' />
							<input type='hidden' name='heading_3' value='$heading_3' />
							<input type='hidden' name='heading_4' value='$heading_4' />
							<input type='hidden' name='capital_employed_out' value='$capital_employed_out' />
							<input type='hidden' name='employment_of_capital_out' value='$employment_of_capital_out' />
							<input type='hidden' name='viewyear' value='$viewyear' />
							<input type='hidden' name='month_from' value='$month_from' />
							<input type='hidden' name='month_to' value='$month_to' />
							<input type='hidden' name='last_year_same_month' value='$last_year_same_month' />
							<input type='hidden' name='last_year_year_to_date' value='$last_year_year_to_date' />
							<input type='hidden' name='this_year_movement_to_date' value='$this_year_movement_to_date' />
							<input type='hidden' name='this_year_year_to_date' value='$this_year_year_to_date' />
							<input type='hidden' name='budget' value='$budget' />
							<input type='hidden' name='this_year_budget' value='$this_year_budget' />
							<input type='hidden' name='zero_balance' value='$zero_balance' />
							<input type='submit' value='Customise' />
							<input type='submit' name='key' value='Print' />
							<input type='submit' name='key' value='Save' />
							<input type='submit' name='key' value='Export to Spreadsheet' />
						</form>
						</td>
					</tr>
				</table>
				<p>
				<center>
				<table ".TMPL_tblDflts." width='25%'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='datacell'>
						<td align='center'><a target='_blank' href='../core/acc-new2.php'>Add account (New Window)</a></td>
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
				</table>
				</center>";
		}
		return $OUTPUT;

	}

	/**
	 * Used by balance sheet to calculate profit/loss
	 *
	 * @param bool $ytdate whether we should calculate a month only or year to date
	 * @param int $month_to last month end mond
	 * @param string $prevyear yrdb for last year balance
	 * @return float
	 */
	static function balsheet_GetProfitLoss($ytdate, $month_to = false, $prevyear = false) {
		global $PRDMON;

		if ($month_to === false) {
			$month_to = $PRDMON[12];
		}

		if ($ytdate === false) {
			$TPF = "_actual";
		} else {
			$TPF = "";
		}

		if ($prevyear == false) {
			$SCHEMA = "core";
		} else {
			$SCHEMA = "$prevyear";
		}

		db_conn("core");
		$sql = "SELECT * FROM accounts
				WHERE (acctype='I' OR acctype='E') AND accnum='000' AND div='".USER_DIV."'";
		$accRslt = db_exec($sql) or errDie(ct("Unable to retrieve the sales/cost of sales accounts from Cubit."));

		$sales_total = 0;
		$cost_of_sales_total = 0;
		$other_income_total = 0;
		$expenses_total = 0;

		while ($accData = pg_fetch_array($accRslt)) {
			// Retrieve the amounts from the trial_bal
			$sql = "SELECT debit, credit FROM \"${SCHEMA}\".trial_bal${TPF}
					WHERE topacc='$accData[topacc]' AND accnum='000'
						AND div='".USER_DIV."' AND month='$month_to'";
			$tbRslt = db_exec($sql) or errDie(ct("Unable to retrieve the sales/cost of sales amounts from Cubit."));

			$tbData = pg_fetch_array($tbRslt);

			if ($accData["acctype"] == "I" && $accData["toptype"] == "" || $accData["toptype"] == "other_income") {
				$other_income_total += $tbData["credit"] - $tbData["debit"];

				$sql = "SELECT debit, credit FROM \"${SCHEMA}\".trial_bal${TPF}
						WHERE topacc='$accData[topacc]' AND accnum!='000' AND month='$month_to'";
				$stbRslt = db_exec($sql) or errDie(ct("Unable to retrieve sub account information from Cubit."));

				while ($stbData = pg_fetch_array($stbRslt)) {
					$other_income_total += $stbData["credit"] - $stbData["debit"];
				}
			} elseif ($accData["toptype"] == "cost_of_sales") {
				$cost_of_sales_total += $tbData["debit"] - $tbData["credit"];

				$sql = "SELECT debit, credit FROM \"${SCHEMA}\".trial_bal${TPF}
						WHERE topacc='$accData[topacc]' AND accnum!='000' AND month='$month_to'";
				$stbRslt = db_exec($sql) or errDie(ct("Unable to retrieve sub account information from Cubit."));

				while ($stbData = pg_fetch_array($stbRslt)) {
					$cost_of_sales_total += $stbData["debit"] - $stbData["credit"];
				}
			} else if ($accData["toptype"] == "sales") {
				$sales_total += $tbData["credit"] - $tbData["debit"];

				$sql = "SELECT debit, credit FROM \"${SCHEMA}\".trial_bal${TPF}
						WHERE topacc='$accData[topacc]' AND accnum!='000' AND month='$month_to'";
				$stbRslt = db_exec($sql) or errDie(ct("Unable to retrieve sub account information from Cubit."));

				while ($stbData = pg_fetch_array($stbRslt)) {
					$sales_total += $stbData["credit"] - $stbData["debit"];
				}
			} else if ($accData["acctype"] == "E" && $accData["toptype"] == "" || $accData["toptype"] == "expenses") {
				$expenses_total += $tbData["debit"] - $tbData["credit"];

				$sql = "SELECT debit, credit FROM \"${SCHEMA}\".trial_bal${TPF}
						WHERE topacc='$accData[topacc]' AND accnum!='000'
							AND month='$month_to'";
				$stbRslt = db_exec($sql) or errDie(ct("Unable to retrieve sub account information from Cubit."));

				while ($stbData = pg_fetch_array($stbRslt)) {
					$expenses_total += $stbData["debit"] - $stbData["credit"];
				}
			}
		}

		// Retrieve tax
		db_conn("core");
		$sql = "SELECT debit, credit FROM \"${SCHEMA}\".trial_bal${TPF}
				WHERE topacc='2800' AND accnum='000' AND month='$month_to'";
		$taxRslt = db_exec($sql) or errDie(ct("Unable to retrieve normal tax from Cubit."));
		$tax_ar = pg_fetch_array($taxRslt, 0);

		$tax = $tax_ar["debit"] - $tax_ar["credit"];

		$plt = sprint($sales_total - $cost_of_sales_total + $other_income_total - $expenses_total - $tax);

		return $plt;
	}

	/**
	 * Determines by account category whether account is asset/equity
	 *
	 * @ignore
	 * @param string $toptype account category
	 * @return string bool(false) if invalid category
	 */
	static function balsheet_type($toptype) {
		$assets = array ("fixed_asset", "investments", "other_fixed_asset", "current_asset");
		$equity = array ("share_capital", "retained_income", "shareholders_loan", "non_current_liability",
		"long_term_borrowing", "other_long_term_liability", "current_liability");

		if (in_array($toptype, $assets)) {
			return "assets";
		} elseif (in_array($toptype, $equity)) {
			return "equity";
		} else {
			return false;
		}
	}

	/**
	 * Calculates account balance using account category to determine method
	 *
	 * @param string $toptype account category
	 * @param float $debit
	 * @param float $credit
	 * @return float bool(false) if invalid account category
	 */
	static function balsheet_calculate($toptype, $debit, $credit) {
		$assets = array ("fixed_asset", "investments", "other_fixed_asset", "current_asset");
		$equity = array ("share_capital", "retained_income", "shareholders_loan", "non_current_liability",
		"long_term_borrowing", "other_long_term_liability", "current_liability");

		if (in_array($toptype, $assets)) {
			return sprint($debit - $credit);
		} elseif (in_array($toptype, $equity)) {
			return sprint($credit - $debit);
		} else {
			return false;
		}
	}
}


function recalculate_tb ()
{

// METHOD 4 -> works!

	db_conn ('core');

	#get list of accounts we work with
	$get_accs = "SELECT distinct (accid) FROM trial_bal";
	$run_accs = db_exec($get_accs) or errDie ("Unable to get trial balance information.");
	if (pg_numrows($run_accs) > 0){
		while ($aarr = pg_fetch_array ($run_accs)){

			#iterate through month in period order ... get balance for the first month (the last year bal)
			$runflag = TRUE;
			$montharr = array (3,4,5,6,7,8,9,10,11,12,1,2);
			
			#reset the balances ... switching accs ... dont want any nasty balances where they dont belong ...
			$dbalance = 0;
			$cbalance = 0;

			foreach ($montharr AS $month){

				#on first run, get the balances we are working with ...
				if ($runflag) {
					#get balances to start from ...
					$get_bal = "SELECT dbalance,cbalance FROM \"$month\".ledger WHERE acc = '$aarr[accid]' AND descript = 'Balance'";
					$run_bal = db_exec($get_bal) or errDie ("Unable to get previous year balance information.");
					$dbalance = sprint (@pg_fetch_result ($run_bal,0,0));
					$cbalance = sprint (@pg_fetch_result ($run_bal,0,1));
					$runflag = FALSE;
				}

				#get total debits/credits for this month and add to running total ...
				$get_bal = "SELECT sum(debit) as debit, sum(credit) as credit FROM \"$month\".ledger WHERE acc = '$aarr[accid]' AND descript != 'Balance'";
				$run_bal = db_exec($get_bal) or errDie ("Unable to get ledger information.");
				if (pg_numrows($run_bal) > 0){
					$debit = pg_fetch_result ($run_bal,0,0);
					$credit = pg_fetch_result ($run_bal,0,1);

					$dbalance += $debit;
					$cbalance += $credit;
				}

				#update the tb with the new running balance for this account ...
				$upd_sql = "UPDATE trial_bal SET debit = '$dbalance', credit = '$cbalance' WHERE accid = '$aarr[accid]' AND month='$month'";
				$run_upd = db_exec($upd_sql) or errDie ("Unable to update trial balance information.");

			}
		}
	}

}
