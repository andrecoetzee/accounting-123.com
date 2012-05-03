<?php

require ("../settings.php");
require ("../core-settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	case "enter":
		$OUTPUT = enter();
		break;
	case "run":
		$OUTPUT = run();
		break;
	}
} else {
	$OUTPUT = enter();
}

require ("../template.php");



function enter()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["user_id"] = USER_ID;

	extract ($fields, EXTR_SKIP);

	$users_out = "";
	if (user_is_admin(USER_NAME)) {
		$sql = "SELECT DISTINCT user_id FROM cubit.hire_trans";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve user ids.");

		$users_sel = "<select name='user_id'>";
		while ($user_data = pg_fetch_array($user_rslt)) {
			$sql = "SELECT username FROM cubit.users WHERE userid='$user_data[user_id]'";
			$username_rslt = db_exec($sql) or errDie("Unable to retrieve user.");
			$username = pg_fetch_result($username_rslt, 0);

			$sel = ($user_data["user_id"] == $user_id) ? "selected='t'" : "";
			$users_sel .= "<option value='$user_data[user_id]' $sel>$username</option>";
		}
		$users_sel .= "</select>";

		$users_out = "
			<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='enter' />
			<table ".TMPL_tblDflts.">
				<tr>
					<th colspan='2'>User</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>$users_sel</td>
					<td><input type='submit' value='Select' /></td>
				</tr>
			</table>
			</form>";
	}

	// Get outstanding rentals count
	$sql = "SELECT count(id) FROM cubit.hire_trans WHERE user_id='$user_id' AND done='y'";
	$count_rslt = db_exec($sql) or errDie("Unable to retrieve outstanding rentals");
	$count = pg_fetch_result($count_rslt, 0);

	$OUTPUT = "
		<center>
		<h3>POS Cash Up</h3>
		$users_out
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Type</th>
				<th>Total Outstanding</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>POS Transactions</td>
				<td>$count</td>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='4' align='center'><a href='".SELF."?key=run&user_id=$user_id' style='font-size: 1.6em'>Run</a></td>
			</tr>
		</table>
		</center>";
	return $OUTPUT;

}



function run()
{

	extract ($_REQUEST);

	pglib_transaction("BEGIN");

	$sql = "
		SELECT id, customers.cusnum, timestamp, user_id, discount_perc, discount,
			subtotal, vat, total, accno, surname, addr1, vatnum, tel, paid,
			pay_type, username
		FROM cubit.pos_trans
			LEFT JOIN cubit.customers ON pos_trans.cusnum=customers.cusnum
			LEFT JOIN cubit.users ON pos_trans.user_id=users.userid
		WHERE user_id='$user_id'";
	$pos_rslt = db_exec($sql) or errDie("Unable to retrieve outstanding.");

	while ($pos_data = pg_fetch_array($pos_rslt)) {
		$time = strtotime($pos_data["timestamp"]);
		$date = date("Y-m-d", $time);

		$refnum = getRefnum();

		$invnum = $pos_data["id"];
		$deptid = 2;
		$chrgvat = "inc";
		$salespn = "General";
		$delvat = 2;
		$vatnum = "02";
		$bankid = cust_bank_id($pos_data["cusnum"]);
		$fcid = 0;

		$total_ex_vat = $pos_data["total"] - $pos_data["vat"];

		$sql = "SELECT deptname, pca FROM exten.departments WHERE deptid='2'";
		$dept_rslt = db_exec($sql) or errDie("Unable to retrieve depts.");
		$dept_data = pg_fetch_result($dept_rslt, 0);

		$pos_data['cusnum'] += 0;

		$sql = "
			INSERT INTO cubit.pinvoices (
				deptid, cusnum, chrgvat, terms, salespn, odate, printed, comm, done, 
				username, deptname, cusacc, surname, cusaddr, 
				cusordno, cusvatno, prd, invnum, div, prints, disc, 
				discp, delchrg, subtot, traddisc, balance, 
				vat, total, discount, delivery, nbal, rdelchrg, serd, pcash, 
				pcheque, pcc, rounding, pchange, delvat, pcredit, vatnum, telno, systime, 
				bankid, fcid
			) VALUES (
				'$deptid', '$pos_data[cusnum]', '$chrgvat', '0', '$salespn', '".date("Y-m-d", $time)."', 'y', '', 'y', 
				'$pos_data[username]', '$dept_data[deptname]', '$pos_data[accno]', '$pos_data[surname]', '$pos_data[addr1]', 
				'', '$pos_data[vatnum]', '".PRD_DB."', '$pos_data[id]', '".USER_DIV."', '0', '$pos_data[discount]', 
				'$pos_data[discount_perc]', '0.00', '$pos_data[subtotal]', '$pos_data[discount]', '$pos_data[total]', 
				'$pos_data[vat]', '$pos_data[total]', '$pos_data[discount]', '0.00', '0.00', '0.00', '', '$pos_data[paid]', 
				'0.00', '0.00', '0.00', '0.00', '$delvat', '0.00', '$vatnum', '$pos_data[tel]', '$pos_data[timestamp]', 
				'$bankid', $fcid
			)";
		db_exec($sql) or errDie("Unable to update point of sale invoices.");
		$invid = lastinvid();

		$sql = "
			SELECT trans_id, stock.stkid, qty, unitcost, amt, whid, stkcod, stkdes, csprice, vatcode, sale_type
			FROM cubit.pos_trans_items
				LEFT JOIN cubit.stock ON pos_trans_items.stkid=stock.stkid
			WHERE trans_id='$pos_data[id]'";
		$item_rslt = db_exec($sql) or errDie("Unable to retrieve pos items.");

		while ($items_data = pg_fetch_array($item_rslt)) {
			$discp = 0;
			$disc = 0;

			$sql = "
				INSERT INTO cubit.inv_items (
					invid, whid, stkid, qty, div, 
					unitcost, discp, disc, vatcode
				) VALUES (
					'$invid', '$items_data[whid]', '$items_data[stkid]', '$items_data[qty]', '".USER_DIV."', 
					'$items_data[unitcost]', '$discp', '$disc', '$items_data[vatcode]'
				)";
			db_exec($sql) or errDie("Unable to add to invoice items.");

			stockrec($items_data["stkid"], $items_data["stkcod"],
				$items_data["stkdes"], "ct", $date, $items_data["qty"],
				$items_data["csprice"], "POS Sales Invoice No. $invnum");

			// Update stock quantities
			$sql = "UPDATE cubit.stock SET units=(units - '$items_data[qty]') WHERE stkid='$items_data[stkid]'";
			db_exec($sql) or errDie("Unable to update invoice.");
		}

		// Retrieve sales account
		$sql = "SELECT incacc FROM exten.departments WHERE deptid='$deptid'";
		$sales_rslt = db_exec($sql) or errDie("Unable to retrieve account.");

		// Retrieve vat account
		$sql = "SELECT accnum FROM core.salesacc WHERE name='VATOUT'";
		$vatacc_rslt = db_exec($sql) or errDie("Unable to retrieve vat account.");

		$sales = pg_fetch_result($sales_rslt, 0);
		$vat_output = pg_fetch_result($vatacc_rslt, 0);
		$point_of_sale = qryAccountsNum("1100", "000");
		$point_of_sale = $point_of_sale["accid"];
		$cash_on_hand = qryAccountsNum("7200", "000");
		$cash_on_hand = $cash_on_hand["accid"];
		$cust_control = qryAccountsNum("6400", "000");
		$cust_control = $cust_control["accid"];

		// Decide which sales transaction to perform
		if ($pos_data["cusnum"] == 0) {
			$debit_acc = $cash_on_hand;
		} else {	
			$debit_acc = $cust_control;

			// Make an entry on the customer's statement
			$sql = "
				INSERT INTO cubit.stmnt (
					invid, docref, amount, date, type, div, allocation_date
				) VALUES (
					'$invid', '0', '$pos_data[total]', '$date', 'Invoice', '".USER_DIV."', '$date'
				)";
			db_exec($sql) or errDie("Unable to update customer statement.");

			// Update customer balance
			$sql = "UPDATE cubit.customers SET balance=(balance + '$pos_data[total]') WHERE cusnum='$pos_data[cusnum]'";
			db_exec($sql) or errDie("Unable to update customers.");

			// Update customer ledger
			custledger($pos_data['cusnum'], $sales, $date, $invnum, "Invoice No. $invnum", $pos_data["total"], "d");
			recordDT($pos_data["total"], $pos_data['cusnum'], $date);

			$sql = "
				INSERT INTO payrec (
					date, by, inv, amount, method, prd, note
				) VALUES (
					'$date', '$pos_data[username]', '$invnum', '$pos_data[total]', 'Credit', '".PRD_DB."', '0'
				)";
			db_exec($sql) or errDie("Unable to record payment.");
			break;
		}

		// Record sale
		$sql = "
			INSERT INTO salesrec (
				edate, invid, invnum, debtacc, vat, total, typ, div
			) VALUES (
				'$date', '$invid', '$invnum', '$cust_control', '$pos_data[vat]', '$pos_data[total]', 'stk', '".USER_DIV."'
			)";

		// Point of sale transaction
		writetrans($debit_acc, $point_of_sale, $date, $refnum, $total_ex_vat, "Sales for POS Invoice No.$invnum");

		// Vat transaction
		writetrans($debit_acc, $vat_output, $date, $refnum, $pos_data["vat"], "VAT Received for POS Invoice No. $invnum.");
	}

	$sql = "SELECT id FROM cubit.pos_trans WHERE user_id='$user_id'";
	$trans_rslt = db_exec($sql) or errDie("Unable to retrieve transactions.");
	while (list($trans_id) = pg_fetch_array($trans_rslt)) {
		$sql = "DELETE FROM cubit.pos_trans_items WHERE trans_id='$trans_id'";
		db_exec($sql) or errDie("Unable to remove outstanding transactions.");
	}
	$sql = "DELETE FROM cubit.pos_trans WHERE user_id='$user_id'";
	db_exec($sql) or errDie("Unable to remove outstanding.");

	if ($cashup == 1) {
		header("Location: pos_cashup_report.php?user_id=$user_id");
	}

	pglib_transaction("COMMIT");

}		


?>
