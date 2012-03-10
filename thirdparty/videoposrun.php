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

	// Retrieve users
	$users_out = "";
	if (user_is_admin(USER_NAME)) {
		$sql = "
		SELECT DISTINCT user_id, username FROM cubit.hire_trans
			LEFT JOIN cubit.users ON hire_trans.user_id=users.userid
		WHERE done='t' AND processed='0'
		ORDER BY username ASC";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve users.");

		$user_sel = "<select name='user_id'>";
		while ($user_data = pg_fetch_array($user_rslt)) {
			$sel = ($user_id == $user_data["user_id"]) ? "selected" : "";

			$user_sel .= "
			<option value='$user_data[user_id]' $sel>
				$user_data[username]
			</option>";
		}
		$user_sel .= "</select>";

		$users_out = "
		<form method='post' action='".SELF."'>
		<input type='hidden' name='key' value='enter' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>User</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$user_sel</td>
				<td><input type='submit' value='Select' /></td>
			</tr>
		</table>
		</form>";
	}

	// Get outstanding rentals count
	$sql = "
	SELECT count(id) FROM cubit.hire_trans
	WHERE user_id='$user_id' AND done='t' AND processed='0'";
	$trans_rslt = db_exec($sql) or errDie("Unable to retrieve transactions.");
	$trans_count = pg_fetch_result($trans_rslt, 0);

	$sql = "
	SELECT count(id) FROM cubit.hire_trans_returned
	WHERE processed='f'";
	$rtrans_rslt = db_exec($sql) or errDie("Unable to retrieve returns.");
	$trans_count += pg_fetch_result($rtrans_rslt, 0);

	$OUTPUT = "
	<center>
	<h3>Video POS Cashup</h3>
	$users_out
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Type</th>
			<th>Total Outstanding</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Video Rentals</td>
			<td>$trans_count</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2' align='center'>
				<a href='".SELF."?key=run&user_id=$user_id'
				style='font-size: 1.6em'>Run</a>
			</td>
		</tr>
	</table>";

	return $OUTPUT;
}

function run()
{
	extract ($_REQUEST);

	// Update returns
	$sql = "
	SELECT id, item_id, timestamp, asset_id, qty
	FROM cubit.hire_trans_returned WHERE processed='f'";
	$return_rslt = db_exec($sql) or errDie("Unable to retrieve returned items");

	while ($return_data = pg_fetch_array($return_rslt)) {
		$sql = "
		SELECT max(id) FROM hire.assets_hired
		WHERE asset_id='$return_data[asset_id]' AND return_time IS NULL";
		$ahid_rslt = db_exec($sql) or errDie("Unable to retrieve last rental.");
		$ahid = pg_fetch_result($ahid_rslt, 0);

		if (!empty($ahid)) {
			$sql = "
			UPDATE hire.assets_hired SET return_time='$return_data[timestamp]',
				returned_qty='$return_data[qty]'
			WHERE id='$ahid'";
			db_exec($sql) or errDie("Unable to update returns.");
		}

		$sql = "DELETE FROM hire.hire_invitems WHERE asset_id='$return_data[asset_id]'";
		db_exec($sql) or errDie("Unable to remove item.");

		$sql = "
		UPDATE cubit.hire_trans_returned SET processed='t'
		WHERE id='$return_data[id]'";
		db_exec($sql) or errDie("Unable to update processed");
	}



	$sql = "
	SELECT id, customers.cusnum, customers.surname, customers.paddr1,
		customers.vatnum, customers.bankid, customers.accno, customers.addr1,
		customers.tel, order_num, timestamp, discount, discount_perc, user_id,
		subtotal, vat,	total, deposit, delivery, departments.deptname,
		departments.debtacc, users.username
	FROM cubit.hire_trans
		LEFT JOIN cubit.customers ON hire_trans.cusnum=customers.cusnum
		LEFT JOIN exten.departments ON customers.deptid=departments.deptid
		LEFT JOIN cubit.users ON hire_trans.user_id=users.userid
	WHERE done='t' AND user_id='$user_id' AND customers.cusnum > 0 AND processed='0'";
	$trans_rslt = db_exec($sql) or errDie("Unable to retrieve transactions.");

	$hire_nums = array();
	while ($trans_data = pg_fetch_array($trans_rslt)) {
		$deptid = 2;
		$time = strtotime($trans_data["timestamp"]);

		$sql = "SELECT deptname FROM exten.departments WHERE deptid='$deptid'";
		$deptname_rslt = db_exec($sql) or errDie("Unable to retrieve department");
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
		VALUES ('$deptid', '$trans_data[cusnum]', '', '', 'inc', '0', '2',
			'".date("Y-m-d", $time)."', 'y', '', 'y', '$trans_data[username]',
			'$deptname', '$trans_data[accno]', '', '$trans_data[surname]',
			'$trans_data[addr1]', '$trans_data[order_num]',
			'$trans_data[vatnum]', '".PRD_DB."', '$trans_data[id]',
			'".USER_DIV."', '0', '$trans_data[discount]',
			'$trans_data[discount_perc]', '$trans_data[delivery]',
			'$trans_data[subtotal]', '$trans_data[discount]', '0.00',
			'$trans_data[vat]', '$trans_data[total]',
			'$trans_data[discount]', '$trans_data[delivery]', '0.00', '0.00',
			'', '100', '100', '100', '100', '100', '0', '0.00',
			'$trans_data[vatnum]', '$trans_data[tel]',
			'$trans_data[timestamp]', 'CSH', '$trans_data[deposit]', '',
			'Client Collect', '0', current_timestamp, '0', '0')";
		db_exec($sql) or errDie("Unable to create hire note.");
		$hire_invid = pglib_lastid("hire.hire_invoices", "invid");
		$hire_nums[$trans_data["id"]] = $hire_invid;

		$sql = "
		SELECT count(id) FROM cubit.hire_trans_items
		WHERE hire_id='$trans_data[id]'";
		$count_rslt = db_exec($sql) or errDie("Unable to retrieve rental count.");
		$count_rental = pg_fetch_result($count_rslt, 0);

		$sql = "
		SELECT count(id) FROM cubit.video_stock_items
		WHERE hire_id='$trans_data[id]'";
		$count_rslt = db_exec($sql) or errDie("Unable to retrieve stock count.");
		$count_stock = pg_fetch_result($count_rslt, 0);

		$sql = "
		SELECT count(id) FROM cubit.hire_trans_contracts
		WHERE hire_id='$trans_data[id]'";
		$count_rslt = db_exec($sql) or errDie("Unable to retrieve contract count.");
		$count_contract = pg_fetch_result($count_rslt, 0);

		$count = $count_rental + $count_stock + $count_contract;

		if (empty($count)) {
			$sql = "DELETE FROM cubit.hire_trans WHERE id='$trans_data[id]'";
			db_exec($sql) or errDie("Unable to remove empty transaction.");
			continue;
		}

		pglib_transaction("BEGIN");

		// Create invoice
		$invnum = divlastid("inv", USER_DIV);
		$accid = qryAccountsNum(1050, 000);
		$accid = $accid["accid"];

		$sql = "
		INSERT INTO cubit.nons_invoices (cusname, cusaddr, cusvatno, chrgvat,
			sdate, done, username, prd, invnum, div, cusid, typ, subtot,
			balance, vat, total, ctyp, accid, tval, cordno, odate, salespn,
			systime, bankid, ncdate, cusnum, discount, cash)
		VALUES ('$trans_data[surname]', '$trans_data[paddr1]',
			'$trans_data[vatnum]', 'yes', '$trans_data[timestamp]', 'n',
			'$user_id', '".PRD_DB."', '$invnum', '".USER_DIV."',
			'$trans_data[cusnum]', 'inv', '$trans_data[subtotal]',
			'$trans_data[total]', '$trans_data[vat]', '$trans_data[total]',
			's', '$accid', '2', '$trans_data[order_num]',
			'$trans_data[timestamp]', 'General', '$trans_data[timestamp]',
			'$trans_data[bankid]', '$trans_data[timestamp]',
			'$trans_data[cusnum]', '$trans_data[discount]' ,
			'$trans_data[total]')";
		db_exec($sql) or errDie("Unable to create new non stock invoice.");

		// Retrieve invoice id
		$invid = lastinvid();

		// Retrieve individual items
		$sql = "
		SELECT hire_trans_items.id, asset_id, basis, from_date, to_date, qty, total_days, total,
			discount_perc, assets.des AS asset_name, returned, grpid
		FROM cubit.hire_trans_items
			LEFT JOIN cubit.assets ON hire_trans_items.asset_id=assets.id
		WHERE hire_id='$trans_data[id]'";
		$items_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

		$stkaccs = array();
		while ($items_data = pg_fetch_array($items_rslt)) {
			$description = "$items_data[asset_name] Rented Out";
			$unitcost = $items_data["total"] / $items_data["qty"];

			// Add to hire note
			$sql = "
			INSERT INTO hire.hire_invitems (invid, qty, amt, unitcost,
				from_date, to_date, asset_id, basis, hours, weeks, days,
				months, half_day, total_days)
			VALUES ('$hire_invid', '$items_data[qty]', '$items_data[total]',
				'$unitcost', '$items_data[from_date]', '$items_data[to_date]',
				'$items_data[asset_id]', '$items_data[basis]', '0',
				'0', '$items_data[total_days]', '0', '0', '$items_data[total_days]')";
			db_exec($sql) or errDie("Unable to create rental items.");
			$item_id = pglib_lastid("hire.hire_invitems", "id");

			// Add to assets hired
			$sql = "
			INSERT INTO hire.assets_hired (invid, asset_id, qty, hired_time,
				cust_id, item_id, invnum, value, basis, discount, weekends)
			VALUES ('$hire_invid', '$items_data[asset_id]', '$items_data[qty]',
				'$trans_data[timestamp]', '$trans_data[cusnum]', '$item_id',
				'$trans_data[id]', '$items_data[total]', '$items_data[basis]',
				'0.00', '0')";
			db_exec($sql) or errDie("Unable to add to assets hired.");		

			// Add to non stock invoice
			$sql = "
			INSERT INTO cubit.nons_inv_items (invid, qty, description, div,
				amt, unitcost, accid, vatex)
			VALUES ('$invid', '$items_data[qty]', '$description',
				'".USER_DIV."', '$items_data[total]', '$unitcost', '$accid',
				'2')";
			db_exec($sql) or errDie("Unable to create non stock item.");
			$id = pglib_lastid("cubit.nons_inv_items", "id");
			$stkaccs[$id] = $accid;

			$sql = "
			SELECT $items_data[basis] FROM hire.basis_prices
			WHERE assetid='$items_data[asset_id]'";
			$rate_rslt = db_exec($sql) or errDie("Unable to retrieve rate.");
			$rate = pg_fetch_result($rate_rslt, 0);
			$rate = (empty($rate)) ? 0.00 : $rate;

			$sql = "
			SELECT serial, des FROM cubit.assets
			WHERE id='$items_data[asset_id]'";
			$asset_rslt = db_exec($sql) or errDie("Unable to retrieve assets.");
			$asset_data = pg_fetch_array($asset_rslt);

			$sql = "
			INSERT INTO hire.hire_nons_inv_items (invid, qty, description, div,
				amt, unitcost, accid, vatex, cunitcost, asset_id, item_id,
				hired_days, rate)
			VALUES ('$hire_invid', '$items_data[qty]', '($asset_data[serial]) ".
				"$asset_data[des] hired from $items_data[from_date] to ".
				"$items_data[to_date].', '".USER_DIV."', '$items_data[total]',
				'$unitcost', '$accid', '2', '$unitcost',
				'$items_data[asset_id]', '$item_id', '$items_data[total_days]',
				'$rate')";
			db_exec($sql) or errDie("Unable to create invoice item.");

			// Add up revenue
			$sql = "
			INSERT INTO hire.revenue (group_id, asset_id, total, discount,
				hire_invnum, inv_invnum, cusname)
			VALUES ('$items_data[grpid]', '$items_data[asset_id]',
				'$items_data[total]', '0', '0',
				'0', '$trans_data[surname]')";
			db_exec($sql) or errDie("Unable to update revenue");

			// Flag as processed
#			$sql = "
#			UPDATE cubit.hire_trans_returned SET processed=true
#			WHERE item_id='$items_data[id]'";
#			db_exec($sql) or errDie("Unable to process returned item.");
		}


		$sql = "
		SELECT id, stock_id, stkcod, stkdes, qty, unitprice, total, cost_price
		FROM cubit.video_stock_items
			LEFT JOIN cubit.stock ON video_stock_items.stock_id=stock.stkid
		WHERE hire_id='$trans_data[id]'";
		$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

		$cost_prices = array();
		while ($stock_data = pg_fetch_array($stock_rslt)) {
			$inventory_acc = qryAccountsNum(6350, 000);
			$inventory_acc = $inventory_acc["accid"];

			$description = "($stock_data[stkcod]) $stock_data[stkdes] Sold";
			$sql = "
			INSERT INTO cubit.nons_inv_items (invid, qty, description, div,
				amt, unitcost, accid, vatex)
			VALUES ('$invid', '$stock_data[qty]', '$description',
				'".USER_DIV."', '$stock_data[total]', '$stock_data[unitprice]',
				'$inventory_acc', '2')";
			db_exec($sql) or errDie("Unable to create non stock item.");
			$nonsinv_id = pglib_lastid("cubit.nons_inv_items", "id");
			$cost_prices[$nonsinv_id] = $stock_data["cost_price"];

			// Update stock balance
			$sql = "
			UPDATE cubit.stock SET units=(units-'$stock_data[qty]')
			WHERE stkid='$stock_data[stock_id]'";
			db_exec($sql) or errDie("Unable to update stock balance.");
		}

		$sql = "
		SELECT id, stock_id, hire_id, qty, unitprice, total, cost_price, hire_trans_contracts.units
		FROM cubit.hire_trans_contracts
			LEFT JOIN cubit.stock ON hire_trans_contracts.stock_id=stock.stkid
		WHERE hire_id='$trans_data[id]'";
		$contract_rslt = db_exec($sql) or errDie("Unable to retrieve contracts.");

		while ($contract_data = pg_fetch_array($contract_rslt)) {
			$hs_acc = qryAccountsNum(1050, 000);
			$hs_acc = $hs_acc["accid"];

			$description = "Contract $stock_data[stkdes] Sold";
			$sql = "
			INSERT INTO cubit.nons_inv_items (invid, qty, description, div, amt,
				unitcost, accid, vatex)
			VALUES ('$invid', '$contract_data[qty]', '$description', '".USER_DIV."',
				'$contract_data[total]', '$contract_data[unitprice]', '$hs_acc', '2')";
			db_exec($sql) or errDie("Unable to create non stock item.");

			// Update stock balance
			$sql = "
			UPDATE cubit.stock SET units=(units-'$contract_data[qty]')
			WHERE stkid='$contract_data[stock_id]'";
			db_exec($sql) or errDie("Unable to update stock balance.");
		}
		
		print cwrite(array(
			"invid"=>$invid,
			"ctyp"=>"s",
			"cusnum"=>$trans_data["cusnum"],
			"stkaccs"=>$stkaccs,
			"cost_prices"=>$cost_prices
		));

		$sql = "UPDATE cubit.hire_trans SET processed='1' WHERE id='$trans_data[id]'";
		db_exec($sql) or errDie("Unable to update transaction item.");
		#$sql = "DELETE FROM cubit.hire_trans WHERE id='$trans_data[id]'";
		#db_exec($sql) or errDie("Unable to remove transaction item.");
		
	}
	pglib_transaction("COMMIT");
	return enter();
}
# Customer write
function cwrite($HTTP_GET_VARS)
{
	$showvat = TRUE;

	extract($HTTP_GET_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($invid, "num", 1, 20, "Invalid invoice number.");
	if(isset($ctyp) && $ctyp == 's'){
		$v->isOk ($cusnum, "num", 1, 20, "Invalid customer number.");
	}elseif(isset($ctyp) && $ctyp == 'c'){
		$v->isOk ($deptid, "num", 1, 20, "Invalid Department.");
	}

	if(isset($stkaccs)){
		foreach($stkaccs as $key => $accid){
			$v->isOk ($accid, "num", 1, 20, "Invalid Item Account number.");
		}
	}else{
		$v->isOk ($invid, "num", 0, 0, "Invalid Item Account number.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$err = $v->genErrors();
		$err .= "<input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $err;
	}
	
	db_connect();

	# Get invoice info
	$sql = "SELECT * FROM nons_invoices WHERE invid = '$invid' AND div = '".USER_DIV."' and done='n'";
	$invRslt = db_exec ($sql) or errDie ("Unable to get invoice information");
	if (pg_numrows ($invRslt) < 1) {
		return "<i class='err'>Not Found</i>";
	}
	$inv = pg_fetch_array($invRslt);

	$td = $inv['odate'];

	db_connect();
	
	# cust % bank
	if($ctyp == 's'){
		$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
		$custRslt = db_exec ($sql) or errDie ("Unable to view customer");
		$cus = pg_fetch_array($custRslt);

		$details = "
		<tr><td>$cus[surname]</td></tr>
		<tr><td>".nl2br($cus['addr1'])."</td></tr>
		<tr><td>VAT No. $cus[vatnum]</td></tr>
		<tr><td>Customer Order Number: $inv[cordno]</td></tr>";

		$na = $cus['surname'];
	}elseif($ctyp == 'c'){
		$cus['surname'] = $inv['cusname'];
		$cus['addr1'] = $inv['cusaddr'];
		$cus["del_addr1"] = "";
		$cus["paddr1"] = "";

		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$deptid'";
		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		$dept = pg_fetch_array($deptRslt);

		$details = "
		<tr><td>$inv[cusname]</td></tr>
		<tr><td>".nl2br($inv['cusaddr'])."</td></tr>
		<tr><td>VAT No. $inv[cusvatno]</td></tr>
		<tr><td>Customer Order Number: $inv[cordno]</td></tr>";

		$na = $inv['cusname'];
	} else {
		$cus["del_addr1"] = "";
		$cus["paddr1"] = "";

		$cus['surname'] = $inv['cusname'];
		$cus['addr1'] = $inv['cusaddr'];

		$details = "
		<tr><td>$inv[cusname]</td></tr>
		<tr><td>".nl2br($inv['cusaddr'])."</td></tr>
		<tr><td>VAT No. $inv[cusvatno]</td></tr>
		<tr><td>Customer Order Number: $inv[cordno]</td></tr>";

		$na = $inv['cusname'];
	}
# Begin updates
	$refnum = getrefnum();

	/* - Start Hooks - */

	$vatacc = gethook("accnum", "salesacc", "name", "VAT","NO VAT");
	$varacc = gethook("accnum", "salesacc", "name", "sales_variance");

	/* - End Hooks - */
	//lock(2);

	$real_invid = divlastid('inv', USER_DIV);

	//unlock(2);

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	/* --- Start Products Display --- */

	# Products layout
	$products = "";
	$disc = 0;

	# get selected stock in this invoice
	db_connect();
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	$stkdRslt = db_exec($sql);

    # Put in product
	$i = 0;
	$page = 0;
	while($stk = pg_fetch_array($stkdRslt)){
		if ($i >= 25) {
			$page++;
			$i = 0;
		}

		$stkacc = $stk["accid"];

		$Sl = "SELECT * FROM vatcodes WHERE id='$stk[vatex]'";
		$Ri = db_exec($Sl) or errDie("Unable to get data.");

		$vd = pg_fetch_array($Ri);

		if($vd['zero'] == "Yes") {
			$stk['vatex'] = "y";
		}

		//print $inv['chrgvat'];exit;

		if((TAX_VAT != $vd['vat_amount']) AND ($vd['vat_amount'] != "0.00")){
			$showvat = FALSE;
		}

		$t = $inv['chrgvat'];

		$VATP = TAX_VAT;

		$hs_acc = qryAccountsNum(1050, 000);
		$hs_acc = $hs_acc["accid"];

		$inv_acc = qryAccountsNum(6350, 000);
		$inv_acc = $inv_acc["accid"];
		# keep records for transactions
		if ($stkacc == $inv_acc) {
			if (!isset($totstkamt[$stkacc])) $totstkamt[$stkacc] = 0;
			$totstkamt[$stkacc] += $cost_prices[$stk["id"]];
			$va=sprint($stk['amt']-vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']));
			if($inv['chrgvat'] == "no") {
				$va = sprint($stk['amt'] * $vd['vat_amount']/100);
			}

			$totstkamt[$hs_acc] += vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']);
		} else if(isset($totstkamt[$stkacc])){
			# Is it stock sold?
			if($stk['vatex'] == "y") {
				$totstkamt[$stkacc] += vats($stk['amt'], 'novat', $vd['vat_amount']);
				$va = 0;
				$inv['chrgvat'] = "";
			} else {
				$totstkamt[$stkacc] += vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']);
				$va=sprint($stk['amt']-vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']));
				if($inv['chrgvat'] == "no") {
					$va = sprint($stk['amt'] * $vd['vat_amount']/100);
				}
			}
		}else{
			if($stk['vatex'] == "y") {
				$totstkamt[$stkacc] = $stk['amt'];
				$inv['chrgvat'] = "";
				$va = 0;
			} else {
				$totstkamt[$stkacc] = vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']);
				$va = sprint($stk['amt']-vats($stk['amt'], $inv['chrgvat'], $vd['vat_amount']));
				if($inv['chrgvat'] == "no") {
					$va = sprint($stk['amt'] * $vd['vat_amount'] / 100);
				}
			}
		}

		vatr($vd['id'], $td, "OUTPUT", $vd['code'], $refnum, "Non-Stock Sales, invoice No.$real_invid", (vats($stk['amt'],$inv['chrgvat'], $vd['vat_amount'])+$va), $va);

		$inv['chrgvat']=$t;

// 		if(isset($totstkamt[$stkacc])){
// 			$totstkamt[$stkacc] += vats($stk['amt'], $inv['chrgvat']);
// 		}else{
// 			$totstkamt[$stkacc] = vats($stk['amt'], $inv['chrgvat']);
// 		}
		$sql = "UPDATE nons_inv_items SET accid = '$stkacc' WHERE id = '$stk[id]'";
		$sRslt = db_exec($sql);

		if($stk['vatex'] == 'y'){
			$ex = "#";
		}else{
//			$ex = "&nbsp;&nbsp;";
			$ex = "";
		}

		$i++;
	}

	/* --- Start Some calculations --- */

	# Subtotal
	$SUBTOT = sprint($inv['subtot']);
	$VAT = sprint($inv['vat']);
	$TOTAL = sprint($inv['total']);

	/* --- End Some calculations --- */

	/* - Start Hooks - */
	$vatacc = gethook("accnum", "salesacc", "name", "VAT","novat");
	/* - End Hooks - */

	# todays date
	$date = date("d-m-Y");
	$sdate = date("Y-m-d");


	if(isset($bankid)) {
		$bankid += 0;
		db_conn("cubit");
		$sql = "SELECT * FROM bankacct WHERE bankid = '$inv[accid]'";

		$deptRslt = db_exec ($sql) or errDie ("Unable to view customers");
		if (pg_numrows ($deptRslt) < 1) {
			$error = "<li class='err'> Bank not Found.";
			$confirm .= "$error<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $confirm;
		}else{
			$deptd = pg_fetch_array($deptRslt);
		}

		db_conn('core');

		$Sl = "SELECT * FROM bankacc WHERE accid='$bankid'";
		$rd = db_exec($Sl) or errDie("Unable to get data.");
		$data = pg_fetch_array($rd);

		$BA = $data['accnum'];
	}

	$tot_post = 0;
	# bank  % cust
	if($ctyp == 's'){
		# Get department
		db_conn("exten");
		$sql = "SELECT * FROM departments WHERE deptid = '$cus[deptid]' AND div = '".USER_DIV."'";
		$deptRslt = db_exec($sql);
		if(pg_numrows($deptRslt) < 1){
			$dept['deptname'] = "<li class=err>Department not Found.";
		}else{
			$dept = pg_fetch_array($deptRslt);
		}
		$tpp = 0;

		$hs_acc = qryAccountsNum(1050, 000);
		$hs_acc = $hs_acc["accid"];

		$inv_acc = qryAccountsNum(6350, 000);
		$inv_acc = $inv_acc["accid"];

		$coh_acc = qryAccountsNum(7200, 000);
		$coh_acc = $coh_acc["accid"];

		$cos_acc = qryAccountsNum(2150, 000);
		$cos_acc = $cos_acc["accid"];

		# record transaction from data
		foreach($totstkamt as $stkacc => $wamt){
			$use_acc = $coh_acc;
			if ($stkacc == $hs_acc) {
				$use_acc = $coh_acc;
			} else if ($stkacc == $inv_acc) {
				$use_acc = $cos_acc;
			}

			$tot_post += $wamt;
			writetrans($use_acc, $stkacc, $td, $refnum, $wamt, "Non-Stock Sales on invoice No.$real_invid customer $cus[surname].");
		}

		# Debit bank and credit the account involved
		if($VAT <> 0){
			$tot_post += $VAT;
			writetrans($coh_acc, $vatacc, $td, $refnum, $VAT, "Non-Stock Sales VAT received on invoice No.$real_invid customer $cus[surname].");
		}

		$sdate = date("Y-m-d");
	}else{

		if(!isset($accountc)) {
			$accountc = 0;
		}

		if(!isset($dept['pca'])) {
			$accountc += 0;
			$dept['pca'] = $accountc;
			$dept['debtacc'] = $accountc;
		}

		if(isset($bankid)) {
			$dept['pca'] = $BA;

		}

		$tpp = 0;
		# record transaction  from data
		foreach($totstkamt as $stkacc => $wamt){
			if(!(isset($cust['surname']))) {
				$cust['surname'] = $inv['cusname'];
				$cust['addr1'] = $inv['cusaddr'];
			}
			# Debit Customer and Credit stock
			$tot_post += $wamt;
			writetrans($dept['pca'], $stkacc, $td, $refnum, $wamt, "Non-Stock Sales on invoice No.$real_invid customer $cust[surname].");
		}

		if(isset($bankid)) {
			db_connect();
			$bankid += 0;
			$sql = "
			INSERT INTO cashbook (bankid, trantype, date, name, descript, cheqnum, 
				amount, vat, chrgvat, banked, accinv, div)
			VALUES (
					'$bankid', 'deposit', '$td', '$inv[cusname]', 
					'Non-Stock Sales on invoice No.$real_invid customer $inv[cusname]', '0', 
					'$TOTAL', '$VAT', '$inv[chrgvat]', 'no', '$stkacc', '".USER_DIV."')";
			$Rslt = db_exec ($sql) or errDie ("Unable to add bank payment to database.",SELF);

			$sql = "UPDATE nons_invoices SET jobid='$bankid' WHERE invid = '$invid' AND div = '".USER_DIV."'";
			$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");
		}

		# Debit bank and credit the account involved
		if($VAT <> 0){
			$tot_post += $VAT;
			writetrans($dept['pca'], $vatacc, $td, $refnum, $VAT, "Non-Stock Sales VAT received on invoice No.$real_invid customer $cust[surname].");
		}

		$sdate = date("Y-m-d");
	}

	$tot_post = sprint($tot_post);

	db_connect();
	if($ctyp == 's'){
		$sql = "
		UPDATE nons_invoices SET balance=total, cusid='$cusnum', ctyp='$ctyp',
			cusname='$cus[surname]', cusaddr='$cus[addr1]', cusvatno='$cus[vatnum]',
			done='y', invnum='$real_invid'
		WHERE invid='$invid' AND div='".USER_DIV."'";
		$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

		/*
		# Record the payment on the statement
		$sql = "
			INSERT INTO stmnt (
				cusnum, invid, docref, amount, date, 
				type, div, allocation_date
			) VALUES (
				'$cusnum', '$real_invid', '$inv[docref]', '$TOTAL', '$inv[odate]', 
				'Non-Stock Invoice', '".USER_DIV."', '$inv[odate]'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Record the payment on the statement
		$sql = "
			INSERT INTO open_stmnt (
				cusnum, invid, docref, amount, balance, 
				date, type, div
			) VALUES (
				'$cusnum', '$real_invid', '$inv[docref]', '$TOTAL', '$TOTAL', 
				'$inv[sdate]', 'Non-Stock Invoice', '".USER_DIV."'
			)";
		$stmntRslt = db_exec($sql) or errDie("Unable to insert statement record in Cubit.",SELF);

		# Update the customer (make balance more)
		# $sql = "UPDATE customers SET balance = (balance + '$TOTAL'::numeric(13,2)) WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
		# $rslt = db_exec($sql) or errDie("Unable to update invoice in Cubit.",SELF);

		# Make ledge record
		custledger($cusnum,$stkacc , $td, $real_invid, "Non Stock Invoice No. $real_invid", $TOTAL, "d");
		custDT($TOTAL, $cusnum, $td, $invid, "nons");
		*/
		$tot_dif = sprint($tot_post-$TOTAL);

#		if($tot_dif > 0) {
#			writetrans($varacc,$dept['debtacc'], $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
#		} elseif($tot_dif < 0) {
#			$tot_dif = $tot_dif * -1;
#			writetrans($dept['debtacc'],$varacc, $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
#		}
	} else {
		$date = date("Y-m-d");

		$sql = "
		UPDATE nons_invoices SET balance=total, cusname = '$cust[surname]',
			accid = '$dept[pca]', ctyp = '$ctyp', cusaddr = '$cust[addr1]',
			done = 'y', invnum = '$real_invid'
		WHERE invid = '$invid' AND div = '".USER_DIV."'";
		$upRslt = db_exec($sql) or errDie ("Unable to update invoice information");

#		$tot_dif = sprint($tot_post - $TOTAL);

#		if($tot_dif > 0) {
#			writetrans($varacc,$dept['pca'], $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
#		} elseif($tot_dif < 0) {
#			$tot_dif = $tot_dif * -1;
#			writetrans($dept['pca'],$varacc, $td, $refnum, $tot_dif, "Sales Variance on invoice $real_invid");
#		}
	}

	db_connect();
	$sql = "
	INSERT INTO salesrec (edate, invid, invnum, debtacc, vat, total, typ, div)
	VALUES ('$inv[odate]', '$invid', '$real_invid', '$dept[debtacc]', '$VAT', 
		'$TOTAL', 'non', '".USER_DIV."')";
	$recRslt = db_exec($sql);

	com_invoice($inv['salespn'],($TOTAL-$VAT),0,$real_invid,$inv["odate"]);

	db_conn('cubit');

	if(!isset($cusnum))
		$cusnum = 0;

	$Sl = "
	INSERT INTO sj (cid, name, des, date, exl, vat, inc, div)
	VALUES ('$cusnum', '$na', 'Non stock Invoice $real_invid', '$inv[sdate]',
		'".sprint($TOTAL-$VAT)."','$VAT','".sprint($TOTAL)."','".USER_DIV."')";
	$Ri = db_exec($Sl);

	# Commit updates
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Get selected stock in this invoice
	$sql = "SELECT * FROM nons_inv_items  WHERE invid = '$invid' AND div = '".USER_DIV."'";
	# $stkdRslt = db_exec($sql);

	$cc = "<script> CostCenter('dt', 'Sales', '$inv[odate]', 'Non Stock Invoice No.$real_invid', '".($TOTAL-$VAT)."', ''); </script>";


	 db_conn('cubit');

	$Sl = "SELECT * FROM settings WHERE constant='SALES'";
	$Ri = db_exec($Sl) or errDie("Unable to get settings.");

	$data = pg_fetch_array($Ri);

	if($data['value'] == "Yes") {
		$sp = "<tr><td><b>Sales Person:</b> $inv[salespn]</td></tr>";
	} else {
		$sp = "";
	}
	if($inv['chrgvat'] == "yes") {
		$inv['chrgvat'] = "Inclusive";
	} elseif($inv['chrgvat'] == "no") {
		$inv['chrgvat'] = "Exclusive";
	} else {
		$inv['chrgvat'] = "No vat";
	}

	if ($inv["remarks"] == "") {
		db_conn("cubit");
		$sql = "SELECT value FROM settings WHERE constant='DEFAULT_COMMENTS'";
		$commRslt = db_exec($sql) or errDie("Unable to retrieve the default comments from Cubit.");
		$inv["remarks"] = pg_fetch_result($commRslt, 0);
	}

	if (!isset($showvat))
		$showvat = TRUE;

	if($showvat == TRUE){
		$vat14 = AT14;
	}else {
		$vat14 = "";
	}

	// Retrieve the company information
	db_conn("cubit");
	$sql = "SELECT * FROM compinfo";
	$comp_rslt = db_exec($sql) or errDie("Unable to retrieve company information from Cubit.");
	$comp_data = pg_fetch_array($comp_rslt);

	#make sure we have a valid bank id for customer
	if (!isset($inv['bankid']) OR strlen ($inv['bankid']) < 1){
		$inv['bankid'] = '2';
	}

	// Retrieve the banking information
	db_conn("cubit");
	$sql = "SELECT * FROM bankacct WHERE bankid='$inv[bankid]' AND div='".USER_DIV."'";
	$bank_rslt = db_exec($sql) or errDie("Unable to retrieve bank information from Cubit.");
	$bank_data = pg_fetch_array($bank_rslt);

}

function vats($amt, $inc, $VATP)
{

	# If vat is not included
	//$VATP = TAX_VAT;
	if($inc == "no"){
		$ret = ($amt);
	}elseif($inc == "yes"){
		$VAT = sprint(($amt/($VATP + 100)) * $VATP);
		$ret = ($amt - $VAT);
	}else{
		$ret = ($amt);
	}
	return $ret;

}

function isHiredd($asset_id, $date=false)
{
	if (!$date) $date = date("Y-m-d");

	$sql = "SELECT hire_invitems.id, hours, weeks, serial, serial2,
				printed, done, extract('epoch' FROM from_date) AS e_from,
				extract('epoch' FROM to_date) AS e_to, return_time
			FROM hire.hire_invitems
				LEFT JOIN hire.hire_invoices
					ON hire_invitems.invid = hire_invoices.invid
				LEFT JOIN cubit.assets
					ON hire_invitems.asset_id = assets.id
				LEFT JOIN hire.assets_hired
					ON hire_invitems.id = assets_hired.item_id
			WHERE hire_invitems.asset_id='$asset_id'";
	$item_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

	// Check if item in workshop
	if (inWorkshop($asset_id, $date)) {
		return true;
	}

	while ($item_data = pg_fetch_array($item_rslt)) {
		if (!isSerialized($asset_id) && $item_data["serial2"] > 0) {
			return false;
		}

		if ($item_data["printed"] == "n" || $item_data["done"] == "n") {
			continue;
		}

		if (!empty($item_data["hours"])) {
			$to_date = hiredDate($item_data["id"], "U")+(HOURS*$item_data["hours"]);
		} elseif (!empty($item_data["weeks"])) {
			$to_date = hiredDate($item_data["id"], "U")+(WEEKS*$item_data["weeks"]);
		} else {
			$to_date = $item_data["e_to"];
		}

		$date = getDTEpoch("$date 0:00:00");

		if ($date >= $item_data["e_from"] && !$item_data["return_time"] && $date <= time()) {
			return true;
		}

		if ($date >= $item_data["e_from"] && $date <= $to_date) {
			return true;
		}
	}
	return false;
}

