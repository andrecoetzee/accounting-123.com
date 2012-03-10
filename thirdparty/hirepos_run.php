<?php
require ("../settings.php");
require ("../core-settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	default:
	case "enter":
		$OUTPUT = enter();
		break;
	case "run":
		$OUTPUT = run();
		break;
	case "view_rental":
		$OUTPUT = view_rental();
		break;
	case "view_invoice":
		$OUTPUT = view_invoice();
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
			$sql = "
			SELECT username FROM cubit.users
			WHERE userid='$user_data[user_id]'";
			$username_rslt = db_exec($sql) or errDie("Unable to retrieve user.");
			$username = pg_fetch_result($username_rslt, 0);

			$sel = ($user_data["user_id"] == $user_id) ? "selected='t'" : "";
			$users_sel .= "
			<option value='$user_data[user_id]' $sel>
				$username
			</option";
		}
		$users_sel .= "</select>";

		$users_out = "
		<form method='post' action='".SELF."'>
		<input type='hidden' name='key' value='enter' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>User</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$users_sel</td>
				<td><input type='submit' value='Select' /></td>
			</tr>
		</table>
		</form>";
	}

	// Get outstanding rentals count
	$sql = "
	SELECT count(id) FROM cubit.hire_trans
	WHERE user_id='$user_id' AND done='y'";
	$rental_rslt = db_exec($sql)
		or errDie("Unable to retrieve outstanding rentals");
	$rental_count = pg_fetch_result($rental_rslt, 0);

	// Get outstanding invoices count
	$sql = "
	SELECT count(id) FROM cubit.hire_invoice_trans
	WHERE user_id='".$user_id."' AND done='t' AND hire_id > 0";
	$invoice_rslt = db_exec($sql)
		or errDie("Unable to retrieve outstanding invoices.");
	$invoice_count = pg_fetch_result($invoice_rslt, 0);

	$OUTPUT = "
	<center>
	<h3>Rental POS Cash Up</h3>
	$users_out
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Type</th>
			<th>Total Outstanding</th>
<!--
			<th colspan='2'>Options</th>
-->
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Rentals</td>
			<td>$rental_count</td>
<!--
			<td>
				<a href='".SELF."?key=run&type=rental&user_id=$user_id'>
					Run
				</a>
			</td>
			<td>
				<a href='".SELF."?key=view_rental&type=rental&user_id=$user_id'>
					View
				</a>
			</td>
-->
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Invoices</td>
			<td>$invoice_count</td>
<!--			
			<td>
				<a href='".SELF."?key=run&type=invoice&user_id=$user_id'>
					Run
				</a>
			</td>
			<td>
				<a href='".SELF."?key=view&type=invoice&user_id=$user_id'>
					View
				</a>
			</td>
-->
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4' align='center'>
				<a href='".SELF."?key=run&user_id=$user_id'
				style='font-size: 1.6em'>Run</a>
			</td>
		</tr>
	</table>
	</center>";

	return $OUTPUT;
}

function view_rental()
{
	extract ($_REQUEST);

	$sql = "SELECT username FROM cubit.users WHERE userid='$user_id'";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve users.");
	$username = pg_fetch_result($user_rslt, 0);

	// Retrieve rentals
	$sql = "
	SELECT id, surname, order_num, extact('epoch' FROM timestamp) AS e_time,
		subtotal, total, discount, delivery
	FROM cubit.hire_trans
		LEFT JOIN cubit.customers ON hire_trans.cusnum=customers.cusnum
	WHERE done='y' AND user_id='$user_id'";
	$rentals_rslt = db_exec($sql) or errDie("Unable to retrieve rentals.");

	$rentals_out = "";
	while ($rentals_data = pg_fetch_array($rentals_rslt)) {
		$rentals_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'>$rentals_data[id]</td>
			<td>$rentals_data[surname]</td>
			<td>$rentals_data[order_num]</td>
			<td>".date("Y-m-d G:i:s", $rentals_data["e_time"])."</td>
			<td>$rentals_data[discount]</td>
			<td>$rentals_data[delivery]</td>
			<td>$rentals_data[subtotal]</td>
			<td>$rentals_data[total]</td>
		</tr>";
	}

	$OUTPUT = "
	<h3>Rental POS Cash Up - View Rentals</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>User</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>$username</td>
		</tr>
	</table>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Rental No</th>
			<th>Customer</th>
			<th>Date/Time</th>
			<th>Discount</th>
			<th>Delivery</th>
			<th>Subtotal</th>
			<th>Total</th>
		</tr>
		$rentals_out
	</table>";

	return $OUTPUT;
}

function view_invoice()
{
	extract ($_REQEUST);

	$sql = "SELECT username FROM cubit.users WHERE userid='$user_id'";
	$user_rslt = db_exec($sql) or errDie("Unable to retrieve users.");
	$username = pg_fetch_array($user_rslt, 0);

	// Retrieve invoices
	$sql = "
	SELECT id, hire_id, surname, order_num, discount, delivery, subtotal, vat,
		total, extract('epoch' FROM timestamp) AS e_time
	FROM cubit.hire_invoice_trans
		LEFT JOIN cubit.customers ON hire_invoice_trans.cusnum=customers.cusnum
	WHERE user_id='$user_id'";

	$invoice_out = "";
	while ($invoice_data = pg_fetch_array($invoice_rslt)) {
		$invoice_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td align='center'>$invoice_data[id]</td>
			<td align='center'>$invoice_data[hire_id]</td>
			<td>$invoice_data[surname]</td>
			<td>$invoice_data[order_num]</td>
			<td>".date("Y-m-d", $invoice_data["e_time"])."</td>
			<td align='right'>$invoice_data[discount]</td>
			<td align='right'>$invoice_data[delivery]</td>
			<td align='right'>$invoice_data[subtotal]</td>
			<td align='right'>$invoice_data[vat]</td>
			<td align='right'>$invoice_data[total]</td>
		</tr>";
	}

	$OUTPUT = "
	<h3>Hire POS Cash Up - View Invoices</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>User</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>$username</td>
		</tr>
	</table>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Invoice No</th>
			<th>Hire No</th>
			<th>Surname</th>
			<th>Order No</th>
			<th>Date/Time</th>
			<th>Discount</th>
			<th>Delivery</th>
			<th>Subtotal</th>
			<th>VAT</th>
			<th>Total</th>
		</tr>
		$invoice_out
	</table>";

	return $OUTPUT;
}

function run()
{
	extract($_REQUEST);

	pglib_transaction("BEGIN");

	// Retrieve outstanding rentals
	$sql = "
	SELECT id, user_id, username, order_num, subtotal, vat, total, discount,
		delivery, customers.cusnum, surname, addr1, addr2, addr3, accno,
		vatnum, tel, discount_perc, timestamp, deposit
	FROM cubit.hire_trans
		LEFT JOIN cubit.customers ON hire_trans.cusnum=customers.cusnum
		LEFT JOIN cubit.users ON hire_trans.user_id=users.userid
	WHERE user_id='$user_id' AND done='y'";
	$rental_rslt = db_exec($sql) or errDie("Unable to retrieve rentals.");

	$hire_nums = array();
	while ($rental_data = pg_fetch_array($rental_rslt)) {
		$deptid = 2;
		$time = strtotime($rental_data["timestamp"]);

		$sql = "SELECT deptname FROM exten.departments WHERE deptid='$deptid'";
		$deptname_rslt = db_exec($sql)
			or errDie("Unable to retrieve department.");
		$deptname = pg_fetch_result($deptname_rslt, 0);

		// Create hire note
		$sql = "
		INSERT INTO hire.hire_invoices (deptid, cusnum, cordno, ordno,
			chrgvat, terms, salespn, odate, printed, comm, done, username,
			deptname, cusacc, cusname, surname, cusaddr, cusordno, cusvatno,
			prd, invnum, div, prints, disc, discp, delchrg, subtot, traddisc,
			balance, vat, total, discount, delivery, nbal, rdelchrg, serd,
			pcash, pcheque, pcc, rounding, pchange, delvat, pcredit, vatnum,
			telno, systime, deposit_type, deposit_amt, custom_txt, collection,
			branch_addr, timestamp, hire_invid, revision)
		VALUES ('$deptid', '$rental_data[cusnum]', '', '', 'inc', '0', '2',
			'".date("Y-m-d", $time)."', 'y', '', 'y', '$rental_data[username]',
			'$deptname', '$rental_data[accno]', '', '$rental_data[surname]',
			'$rental_data[addr1]', '$rental_data[order_num]',
			'$rental_data[vatnum]', '".PRD_DB."', '$rental_data[id]',
			'".USER_DIV."', '0', '$rental_data[discount]',
			'$rental_data[discount_perc]', '$rental_data[delivery]',
			'$rental_data[subtotal]', '$rental_data[discount]', '0.00',
			'$rental_data[vat]', '$rental_data[total]',
			'$rental_data[discount]', '$rental_data[delivery]', '0.00', '0.00',
			'', '100', '100', '100', '100', '100', '0', '0.00',
			'$rental_data[vatnum]', '$rental_data[tel]',
			'$rental_data[timestamp]', 'CSH', '$rental_data[deposit]', '',
			'Client Collect', '0', current_timestamp, '0', '0')";
		db_exec($sql) or errDie("Unable to create hire note.");
		$invid = pglib_lastid("hire.hire_invoices", "invid");
		$hire_nums[$rental_data["id"]] = $invid;
		// Do deposit transaction if required
		if ($rental_data["deposit"] > 0) {
			$cash_on_hand = qryAccountsNum("7200", "000");
			$cash_on_hand = $cash_on_hand["accid"];
			$cust_control = qryAccountsNum("6400", "000");
			$cust_control = $cust_control["accid"];

			$refnum = getRefnum();
			writetrans($cash_on_hand, $cust_control, date("Y-m-d", $time),
				$refnum, $rental_data["deposit"],
				"Cash Receipt for ".CUR."$rental_data[deposit] from ".
				"$rental_data[surname] for Deposit on Hire Note $rental_data[id]");

			$sql = "
			INSERT INTO hire.cash (invid, cash)
			VALUES ('$invid', '$rental_data[deposit]')";
			db_exec($sql) or errDie("Unable to add cash to hire.");

			// Make ledger record
			custledger($rental_data["cusnum"], $cust_control,
				date("Y-m-d", $time), $invid,
				"Cash Receipt for ".CUR."$rental_data[deposit] from ".
				"$rental_data[surname] for Deposit on Hire Note $rental_data[id]",
				$rental_data["deposit"], "c");

			custCT($rental_data["deposit"], $rental_data["cusnum"],
				date("Y-m-d", $time));

			// Turn the amount around to a negative
			$stmnt_amt = $rental_data["deposit"] - ($rental_data["deposit"] * 2);

			// Record the payment on the statement
			$sql = "
			INSERT INTO cubit.stmnt(cusnum, invid, docref, amount, date, type,
				div)
			VALUES('$rental_data[cusnum]', '$invid', '$rental_data[id]',
				'$stmnt_amt', '".date("Y-m-d", $time)."',
				'Cash Receipt for ".CUR."$rental_data[deposit] from ".
				"$rental_data[surname] for Deposit on Hire Note $rental_data[id]',
				'".USER_DIV."')";
			$stmntRslt = db_exec($sql)
				or errDie("Unable to add deposit to statement");

			// Update customer balance
			$sql = "
			UPDATE cubit.customers SET balance=balance-'$rental_data[deposit]'
			WHERE cusnum='$rental_data[cusnum]'";
			db_exec($sql) or errDie("Unable to update customer balance.");

			$sql = "
			UPDATE hire.hire_invoices SET deposit_amt='0'
			WHERE invid='$invid'";
			db_exec($sql) or errDie("Unable to retrieve hire invoices.");
		}

		// Retrieve items on this invoice
		$sql = "
		SELECT asset_id, basis, from_date, to_date, half_day, qty,
			weekends, total_days, total
		FROM cubit.hire_trans_items
		WHERE hire_id='$rental_data[id]'";
		$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

		while ($item_data = pg_fetch_array($item_rslt)) {
			$unitcost = $item_data["total"] / $item_data["qty"];

			// Decide which basis to use
			$hours  = 0;
			$weeks  = 0;
			$days   = 0;
			$months = 0;
			$total_days = 0;
			switch ($item_data["basis"]) {
			case "per_hour":
				$hours = $item_data["total_days"];
				break;
			case "per_day":
				$days = $item_data["total_days"];
				$total_days = $item_data["total_days"];
				break;
			case "per_week":
				$week = $item_data["total_days"];
				break;
			case "per_month":
				$months = $item_data["total_days"];
				break;
			}

			// Convert booleans into something we can use
			$half_day = ($item_data["half_day"] == "t") ? 1 : 0;
			$weekends = ($item_data["weekends"] == "t") ? 1 : 0;

			$sql = "
			INSERT INTO hire.hire_invitems (invid, qty, amt, unitcost,
				from_date, to_date, asset_id, basis, hours, weeks, days,
				months, half_day, weekends, total_days)
			VALUES ('$invid', '$item_data[qty]', '$item_data[total]',
				'$unitcost', '$item_data[from_date]', '$item_data[to_date]',
				'$item_data[asset_id]', '$item_data[basis]', '$hours',
				'$weeks', '$days', '$months', '$half_day', '$weekends',
				'$total_days')";
			db_exec($sql) or errDie("Unable to create rental items.");
			$item_id = pglib_lastid("hire.hire_invitems", "id");
			
			$sql = "
			INSERT INTO hire.assets_hired (invid, asset_id, qty, hired_time,
				cust_id, item_id, invnum, value, basis, discount, weekends)
			VALUES ('$invid', '$item_data[asset_id]', '$item_data[qty]',
				'$rental_data[timestamp]', '$rental_data[cusnum]', '$item_id',
				'$rental_data[id]', '$item_data[total]', '$item_data[basis]',
				'0.00', '$weekends')";
			db_exec($sql) or errDie("Unable to add to assets hired.");
		}

	}

	// Run invoices ----------------------------------------------------------
	$sql = "
	SELECT id, hire_id, customers.cusnum, order_num, discount_perc, discount,
		subtotal, total, vat, timestamp, user_id, surname, addr1, vatnum,
		username, delivery
	FROM cubit.hire_invoice_trans
		LEFT JOIN cubit.customers ON hire_invoice_trans.cusnum=customers.cusnum
		LEFT JOIN cubit.users ON hire_invoice_trans.user_id=users.userid
	WHERE done='y' AND user_id='$user_id' AND hire_id > 0";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

	while ($inv_data = pg_fetch_array($inv_rslt)) {
		$hire_sales = qryAccountsNum("1050", "000");
		$cust_control = qryAccountsNum("6400", "000");
		$cash_on_hand = qryAccountsNum("7200", "000");
		$hire_sales = $hire_sales["accid"];
		$cust_control = $cust_control["accid"];
		$cash_on_hand = $cash_on_hand["accid"];

		$time = strtotime($inv_data["timestamp"]);

		$sql = "
		INSERT INTO cubit.nons_invoices (cusname, cusaddr, cusvatno,
			chrgvat, sdate, done, username, prd, invnum, div, remarks, cusid,
			age, typ, subtot, balance, vat, total, descrip, ctyp, accid,
			fbalance, fsubtot, cordno, terms, odate, systime, bankid,
			cusordno, ncdate, cusnum, discount, delivery, hire_invid,
			cash, cheque, credit)
		VALUES ('$inv_data[surname]', '$inv_data[addr1]', '$inv_data[vatnum]',
			'yes', '".date("Y-m-d", $time)."', 'y', '$inv_data[username]',
			'".PRD_DB."', '$inv_data[id]', '".USER_DIV."', '',
			'$inv_data[cusnum]', '0', 'inv', '$inv_data[subtotal]',
			'$inv_data[total]', '$inv_data[vat]', '$inv_data[total]', '', 's',
			'$hire_sales', '0.00', '0.00', '$inv_data[order_num]', '0',
			'".date("Y-m-d", $time)."', current_timestamp,
			'".cust_bank_id($inv_data["cusnum"])."', '$inv_data[order_num]',
			'".date("Y-m-d", $time)."', '$inv_data[cusnum]',
			'$inv_data[discount]', '$inv_data[delivery]',
			'".$hire_nums[$inv_data["hire_id"]]."', '$inv_data[total]', '0', '0')";
		db_exec($sql) or errDie("Unable to create non stock invoice.");
		$invid = lastinvid();

		$sql = "
		SELECT hire_invoice_items_trans.id, asset_id, basis, from_date,
			to_date, half_day, qty, weekends, total_days, total,
			serial, des, grpid
		FROM cubit.hire_invoice_items_trans
			LEFT JOIN cubit.assets
				ON hire_invoice_items_trans.asset_id=assets.id
		WHERE trans_id='$inv_data[id]'";
		$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

		while ($item_data = pg_fetch_array($item_rslt)) {
			$unitcost = $item_data["total"] / $item_data["qty"];
			$item_id = 0;

			$sql = "
			SELECT $item_data[basis] FROM hire.basis_prices
			WHERE assetid='$item_data[asset_id]'";
			$rate_rslt = db_exec($sql) or errDie("Unable to retrieve rate.");
			$rate = pg_fetch_result($rate_rslt, 0);
			$rate = (empty($rate)) ? 0.00 : $rate;

			$sql = "
			SELECT serial, des FROM cubit.assets
			WHERE id='$item_data[asset_id]'";
			$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");
			$asset_data = pg_fetch_array($asset_rslt);

			$sql = "
			INSERT INTO hire.hire_nons_inv_items (invid, qty, description, div,
				amt, unitcost, accid, vatex, cunitcost, asset_id, item_id,
				hired_days, rate)
			VALUES ('$invid', '$item_data[qty]', '($asset_data[serial]) ".
				"$asset_data[des] hired from $item_data[from_date] to ".
				"$item_data[to_date].', '".USER_DIV."', '$item_data[total]',
				'$unitcost', '$hire_sales', '2', '$unitcost',
				'$item_data[asset_id]', '$item_id', '$item_data[total_days]',
				'$rate')";
			db_exec($sql) or errDie("Unable to create invoice item.");

			// Add up revenue
			$sql = "
			INSERT INTO hire.revenue (group_id, asset_id, total, discount,
				hire_invnum, inv_invnum, cusname)
			VALUES ('$item_data[grpid]', '$item_data[asset_id]',
				'$item_data[total]', '0', '0',
				'0', '$inv_data[surname]')";
			db_exec($sql) or errDie("Unable to update revenue");

			$sql = "
			UPDATE hire.assets_hired SET return_time=CURRENT_TIMESTAMP
				WHERE item_id='$item_data[id]'";
			db_exec($sql) or errDie("Unable to update hired assets.");

			$sql = "
			SELECT serial2 FROM cubit.assets
			WHERE id='$item_data[asset_id]'";
			$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset");
			$asset_data = pg_fetch_array($asset_rslt);

			if (!isSerialized($item_data["asset_id"])) {
				$new_qty = $asset_data["serial2"] + $item_data["qty"];

				$sql = "
				UPDATE cubit.assets SET serial2=(serial2::numeric + '$item_data[qty]')
				WHERE id='$item_data[asset_id]'";
				db_exec($sql) or errDie("Unable to update asset qty.");
			}
		}

		$refnum = getRefnum();
		writetrans($cust_control, $hire_sales, date("Y-m-d", $time), $refnum,
			$inv_data["total"], "Non Stock Sales on invoice No. $inv_data[id] ".
			"customer $inv_data[surname]");

		// Sales record
		$sql = "
		INSERT INTO cubit.salesrec(edate, invid, invnum, debtacc, vat, total,
			typ, div)
		VALUES('".date("Y-m-d", $time)."', '$invid', '$inv_data[id]',
			'$cust_control', '$inv_data[vat]', '$inv_data[total]', 'non',
			'".USER_DIV."')";
		db_exec($sql) or errDie("Unable to create sales record.");

		// Vat record
		vatr(2, date("Y-m-d", $time), "OUTPUT", '01', $refnum,
			"Non-Stock Sales, invoice No.$inv_data[id]", $inv_data["total"],
			$inv_data["vat"]);

		// Add to statement
		$sql = "
		INSERT INTO cubit.stmnt (cusnum, invid, docref, amount, date, type, div)
		VALUES ('$inv_data[cusnum]', '$invid', '$inv_data[order_num]',
			'$inv_data[total]', '".date("Y-m-d", $time)."',
			'Hire Invoice $inv_data[id]', '".USER_DIV."')";
		db_exec($sql) or errDie("Unable to add to statement.");

		// Update customer balance
		$sql = "
		UPDATE customers SET balance = (balance + '$inv_data[total]')
		WHERE cusnum='$inv_data[cusnum]' AND div='".USER_DIV."'";
		db_exec($sql) or errDie("Unable to update customer balance.");

		custledger($inv_data["cusnum"], $hire_sales, date("Y-m-d", $time),
			$invid,	"Hire Invoice No. $inv_data[id]", $inv_data["total"], "d");
		custDT($inv_data["total"], $inv_data["cusnum"], date("Y-m-d", $time));

	}

	// Clear outstanding tables
	$sql = "DELETE FROM cubit.hire_trans";
	db_exec($sql) or errDie("Unable to remove outstanding (1)");
	$sql = "DELETE FROM cubit.hire_trans_items";
	db_exec($sql) or errDie("Unable to remove outstanding (2)");
	$sql = "DELETE FROM cubit.hire_invoice_trans";
	db_exec($sql) or errDie("Unable to remove outstanding (3)");
	$sql = "DELETE FROM cubit.hire_invoice_items_trans";
	db_exec($sql) or errDie("Unable to remove outstanding (4)");

	pglib_transaction("COMMIT");

	return enter();
}
