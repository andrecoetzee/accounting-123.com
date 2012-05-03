<?php

require ("settings.php");
require ("core-settings.php");

error_reporting('E_ALL');

pglib_transaction("BEGIN");

if (!isset($_REQUEST["m_stock_id"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	require("template.php");
}

if (isset($_REQUEST["opt"])) {
	$tdate = "$_REQUEST[tdate_year]-$_REQUEST[tdate_month]-$_REQUEST[tdate_day]";
	switch ($_REQUEST["opt"]) {
		case "manufacture":
			$OUTPUT = manufacture($_REQUEST["m_stock_id"], $_REQUEST["qty"], $tdate);
			break;
	}
}

if (isset($_REQUEST["btn_dissasemble"])) {
	$tdate = "$_REQUEST[tdate_year]-$_REQUEST[tdate_month]-$_REQUEST[tdate_day]";
	if(!isset($_POST["stkval"]))
		$_POST["stkval"] = array();
	if(!isset($_POST["stkqty"]))
		$_POST["stkqty"] = array();

	$OUTPUT = unmanufacture($_REQUEST["m_stock_id"], $_REQUEST["qty"], $_POST["stkval"], $_POST["stkqty"], $tdate);
} else if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "manuout":
			$OUTPUT = manuOut();
			break;
		case "unmanuout":
			$OUTPUT = unmanuOut();
			break;
		case "unmanuupdate":
			$OUTPUT = unmanuUpdate();
			break;
	}
}

pglib_transaction("COMMIT");

require ("template.php");




function manuOut()
{

	extract ($_REQUEST);

	$fields = array();
	$fields["qty"] = 1;
	$fields["tdate_year"] = date("Y");
	$fields["tdate_month"] = date("m");
	$fields["tdate_day"] = date("d");

	extract ($fields, EXTR_SKIP);

	$requirements_met = true;

	// Retrieve main stock item
	$sql = "SELECT * FROM cubit.stock WHERE stkid='$m_stock_id'";
	$m_stock_rslt = db_exec($sql) or errDie("Unable to retrieve main stock item.");
	$m_stock_data = pg_fetch_array($m_stock_rslt);

	// Retrieve recipe
	$sql = "SELECT * FROM cubit.recipies WHERE m_stock_id='$m_stock_id' ORDER BY id DESC";
	$recipe_rslt = db_exec($sql) or errDie("Unable to retrieve recipe.");

	$s_stock_out = "";
	while ($recipe_data = pg_fetch_array($recipe_rslt)) {
		// Retrieve stock item
		$sql = "SELECT * FROM cubit.stock WHERE stkid='$recipe_data[s_stock_id]'";
		$s_stock_rslt = db_exec($sql) or errDie("Unable to retrieve recipe.");
		$s_stock_data = pg_fetch_array($s_stock_rslt);

		$qty_item = $recipe_data["qty"] * $qty;

		// Do we have enough stock items
		if ($qty_item > $s_stock_data["units"]) {
			$qty_required = "<span class='required'>".($qty_item - $s_stock_data["units"])."</span>";
			$options = "
				<table cellpadding='1' cellspacing='0'>
					<tr>
						<td>Edit Recipe</td>
						<td>Purchase</td>
					</tr>
				</table>";
			$requirements_met = false;
		} else {
			$qty_required = 0;
			$options = "";
		}

		$s_stock_out .= "
			<tr class='".bg_class()."'>
				<td>$s_stock_data[stkcod]</td>
				<td align='center'>".sprint3($qty_item)."</td>
				<td align='center'>$qty_required</td>
			</tr>";
	}

	if ($qty) {
		if ($requirements_met) {
			$datefield = "
				<tr>
					<th colspan='4'>Transaction Date</th>
				</tr>
				<tr class='".bg_class()."'>
					<td colspan='4' align='center'>".mkDateSelect("tdate", $tdate_year, $tdate_month, $tdate_day)."</td>
				</tr>";
			$manubtn = "<input type='submit' value='Manufacture &raquo' style='font-size: 14pt; font-weight: bold; width: 100%' />";
		} else {
			$datefield = "
				<tr>
					<th colspan='4'>Transaction Date</th>
				</tr>
				<tr class='".bg_class()."'>
					<td colspan='4' align='center'>
						".mkDateSelect("tdate", $tdate_year, $tdate_month, $tdate_day)."
					</td>
				</tr>";
			$manubtn = "
				<li class='err'>
					Unable to manufacture not<br />
					enough stock available.<br />
					<a href='purchase-new.php'>New Purchase</a><br />
					<a href='manu_recipe.php?m_stock_id=$m_stock_id'>Edit Recipe</a>
				</li>";
		}
	} else {
		$datefield = "";
		$manubtn = "";
	}

	$OUTPUT = "
		<center>
		<h3>Manufacture</h3>
		<form method='post' action='".SELF."'>
			<input type='hidden' name='m_stock_id' value='$m_stock_id' />
			<input type='hidden' name='key' value='$key' />
			<input type='hidden' name='tdate_year' value='$tdate_year' />
			<input type='hidden' name='tdate_month' value='$tdate_month' />
			<input type='hidden' name='tdate_day' value='$tdate_day' />
			<li class='err' style='width: 50%'>
				Please remember to click update after selecting the quantity to be
				manufactured!
			</li>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>New Stock Item</th>
				<th>Qty</th>
				<th colspan='2'>&nbsp;</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><b>$m_stock_data[stkcod]</b></td>
				<td><input type='text' name='qty' value='$qty' size='3' style='text-align: center; width: 100%;' /></td>
				<td colspan='2'><input type='submit' value='Update &raquo' style='width: 100%;' /></td>
			</tr>
			<tr>
				<th>Stock Item</th>
				<th>Qty</th>
				<th>Required</th>
			</tr>
			$s_stock_out
		</form>
		<form method='post' action='".SELF."'>
			<input type='hidden' name='m_stock_id' value='$m_stock_id' />
			<input type='hidden' name='opt' value='manufacture' />
			<input type='hidden' name='qty' value='$qty' />
			$datefield
			<tr>
				<th colspan='4' align='center'>$manubtn</th>
			</tr>
		</form>
		</table>
		</form>
		</center>";
	return $OUTPUT;

}




function unmanuOut($err = "")
{

	extract ($_REQUEST);

	$fields = array();
	$fields["qty"] = 1;
	$fields["tdate_year"] = date("Y");
	$fields["tdate_month"] = date("m");
	$fields["tdate_day"] = date("d");

	extract ($fields, EXTR_SKIP);

	$sql = "SELECT * FROM cubit.dissasemble_save WHERE m_stock_id='$m_stock_id' AND session_id='$_REQUEST[CUBIT_SESSION]'";
	$ds_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

	while ($ds_data = pg_fetch_array($ds_rslt)) {
		$_POST["stkval"][$ds_data["stkid"]] = $ds_data["stk_val"];
		$_POST["stkqty"][$ds_data["stkid"]] = $ds_data["stk_qty"];
	}

	$sql = "SELECT * FROM cubit.stock ORDER BY stkcod ASC";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");

	$stock_sel = "<select name='n_stk_id'>";
	while ($stock_data = pg_fetch_array($stock_rslt)) {
		$stock_sel .= "<option value='$stock_data[stkid]'>$stock_data[stkcod]</option>";
	}
	$stock_sel .= "</select>";

	$ns_stock_out = "
		<tr class='".bg_class()."'>
			<td>$stock_sel</td>
			<td><input type='text' name='n_stk_qty' value='0' size='3' style='text-align: center'></td>
			<td align='right'>".CUR." <input type='text' name='n_stk_val' value='0.00' size='7' style='text-align: right' /></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>";

	$sql = "SELECT * FROM cubit.dissasemble_save WHERE session_id='$_REQUEST[CUBIT_SESSION]' AND m_stock_id='$m_stock_id'";
	$ds_rslt = db_exec($sql) or errDie("Unable to retrieve saved entries.");

	$s_stock_out = "";
	while ($ds_data = pg_fetch_array($ds_rslt)) {
		$sql = "SELECT * FROM cubit.stock WHERE stkid='$ds_data[stkid]'";
		$sstock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		$sstock_data = pg_fetch_array($sstock_rslt);

		$cost = $sstock_data["csprice"] * $ds_data["stk_qty"];

		$s_stock_out .= "
			<input type='hidden' name='stkval[$sstock_data[stkid]]' value='$ds_data[stk_val]' />
			<input type='hidden' name='stkqty[$sstock_data[stkid]]' value='$ds_data[stk_qty]' />
			<tr class='".bg_class()."'>
				<td>$sstock_data[stkcod]</td>
				<td align='center'>$ds_data[stk_qty]</td>
				<td align='right'>".CUR.sprint($ds_data["stk_val"])."</td>
				<td align='right'>".CUR.sprint($sstock_data["csprice"])."</td>
				<td align='center'><input type='checkbox' name='rem[$ds_data[id]]' value='$ds_data[id]' /></td>
			</tr>";
	}

	// Retrieve main stock item
	$sql = "SELECT * FROM cubit.stock WHERE stkid='$m_stock_id'";
	$m_stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$m_stock_data = pg_fetch_array($m_stock_rslt);

	if ($m_stock_data["units"] >= $qty) {
		$datefield = "
			<tr>
				<th colspan='5'>Transaction Date</th>
			</tr>
			<tr class='".bg_class()."'>
				<td colspan='5' align='center'>
					".mkDateSelect("tdate", $tdate_year, $tdate_month, $tdate_day)."
				</td>
			</tr>";
		$unmanubtn = "<input type='submit' name='btn_dissasemble' value='Disassemble &raquo' style='width: 100%; font-size: 14pt; font-weight: bold'>";
	} else {
		$datefield = "";
		$unmanubtn = "
			<li class='err'>
				Not enough stock items<br />
				available to disassemble.
			</li>";
	}

	$OUTPUT = "
		<center>
		<h3>Disassemble</h3>
		$err
		<form method='post' action='".SELF."'>
			<input type='hidden' name='m_stock_id' value='$m_stock_id' />
			<input type='hidden' name='key' value='unmanuupdate' />
			<input type='hidden' name='origin' value='unmanuout' />
			<input type='hidden' name='tdate_year' value='$tdate_year' />
			<input type='hidden' name='tdate_month' value='$tdate_month' />
			<input type='hidden' name='tdate_day' value='$tdate_day' />
			<li class='err' style='width: 50%'>Please remember to click update after selecting the quantity to be dissassembled!</li>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Stock Item</th>
				<th>Qty</th>
				<th>Value for Stock Items</th>
				<th colspan='2'>&nbsp;</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$m_stock_data[stkcod]</td>
				<td><input type='text' name='qty' value='$qty' size='3' style='text-align: center; width: 100%;' /></td>
				<td align='right'>".CUR." ".money($m_stock_data["csprice"] * $qty)."</td>
				<td colspan='2'><input type='submit' value='Update &raquo' style='width: 100%' /></td>
			</tr>
			<tr>
				<th>New Stock Items</th>
				<th>Qty</th>
				<th>Total Value of Items</td>
				<th>Cost of Stock</th>
				<th>Remove</th>
			</tr>
			$s_stock_out
			$ns_stock_out
			$datefield
			<tr>
				<th colspan='5'>$unmanubtn</th>
			</tr>
		</form>
		</table>
		</form>
		</center>";
	return $OUTPUT;

}




function unmanuUpdate($origin="unmanuout", $m_stock_id=0, $qty=0, &$stkval=0, &$qtys=0, $tdate=0)
{

	extract ($_REQUEST);

	$sql = "SELECT * FROM cubit.dissasemble_save WHERE session_id='$_REQUEST[CUBIT_SESSION]' AND stkid='$n_stk_id' AND m_stock_id='$m_stock_id'";
	$ds_rslt = db_exec($sql);

	if (!pg_num_rows($ds_rslt) && (float)$n_stk_qty && (float)$n_stk_val) {
		$sql = "
			INSERT INTO cubit.dissasemble_save (
				stkid, stk_qty, stk_val, session_id, m_stock_id
			) VALUES (
				'$n_stk_id', '$n_stk_qty', '$n_stk_val', '$_REQUEST[CUBIT_SESSION]', '$m_stock_id'
			)";
		db_exec($sql) or errDie("Unable to add new items to be disassembled.");
		
		if (is_array($stkval) && is_array($qtys)) {
			$stkval[$n_stk_id] = $n_stk_val;
			$qtys[$n_stk_id] = $n_stk_qty;
		}
	}

	if (isset($rem)) {
		foreach ($rem as $rem_id) {
			$sql = "DELETE FROM cubit.dissasemble_save WHERE id='$rem_id'";
			db_exec($sql) or errDie("Unable to remove item.");
		}
	}

	if ($origin == "unmanuout") {
		return unmanuOut();
	} elseif ($origin == "disassemble") {
		return unmanufacture($m_stock_id, $qty, $stkval, $qtys, $tdate);
	}

}




function manufacture($m_stock_id, $qty, $tdate)
{

	$time = time();
	for ($i = 0; $i < $qty; $i++) {
		// Retrieve recipe
		$sql = "SELECT * FROM cubit.recipies WHERE m_stock_id='$m_stock_id'";
		$recipe_rslt = db_exec($sql) or errDie("Unable to retrieve recipe.");

		// Decrease sub stock
		while ($recipe_data = pg_fetch_array($recipe_rslt)) {
			$sub_qty = $recipe_data["qty"] * $qty;
			$sql = "UPDATE cubit.stock SET units=(units - '$recipe_data[qty]') WHERE stkid='$recipe_data[s_stock_id]'";
			db_exec($sql) or errDie("Unable to update stock.");

			// Stock barcodes
			$sqls = array();
			for ($k = 0; $k <= 9; $k++) {
				$sqls[] = "SELECT * FROM cubit.ss$k WHERE active='yes' AND stock='$recipe_data[s_stock_id]'";
			}

			$sql = implode(" UNION ", $sqls);
			$ss_rslt = db_exec($sql) or errDie("Unable to remove barcode.");

			$j = 0;
			while ($ss_data = pg_fetch_array($ss_rslt)) {
				$j++;

				if ($j > $recipe_data["qty"]) {
					continue 2;
				}

				$sql = "DELETE FROM cubit.ss".$ss_data["code"]{0}." WHERE code='$ss_data[code]' AND stock='$recipe_data[s_stock_id]'";
				db_exec($sql) or errDie("Unable to remove barcode.");
			}

			// Record in history for reporting history
			$sql = "SELECT csprice FROM cubit.stock WHERE stkid='$recipe_data[s_stock_id]'";
			$stock_rslt = db_exec($sql) or errDie("Unable to retrieve cost.");
			$stock_cost = pg_fetch_result($stock_rslt, 0);

			$sql = "
				INSERT INTO cubit.manu_history (
					m_stock_id, s_stock_id, qty, 
					timestamp, cost
				) VALUES (
					'$recipe_data[m_stock_id]', '$recipe_data[s_stock_id]', '$recipe_data[qty]', 
					'".date("Y-m-d G:i:s", $time)."', '$stock_cost'
				)";
			db_exec($sql) or errDie("Unable to update manufacturing history.");
		}
	} 

	// Create the new items
	$sql = "UPDATE cubit.stock SET units=(units + $qty) WHERE stkid='$m_stock_id'";
	db_exec($sql) or errDie("Unable to create manufactured stock.");

	doManuAccounting($m_stock_id, $qty, $tdate);

	$OUTPUT = "
		<h3>Manufacture Stock</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Manufactured</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Item successfully manufactured.</td>
				<td><a href='stock-view.php'>View Stock</a></td>
			</tr>
		</table>";
	return $OUTPUT;

}




function unmanufacture($m_stock_id, $qty, $stkval, $qtys, $tdate)
{

	unmanuUpdate("disassemble", $m_stock_id, $qty, $stkval, $qtys, $tdate);

	// Do we have enough stock items to unmanufacture
	$sql = "SELECT units,csprice FROM cubit.stock WHERE stkid='$m_stock_id'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock units.");
	$stock_units = pg_fetch_result($stock_rslt, 0);
	$stock_csprice = pg_fetch_result($stock_rslt, 1);

	if ($stock_units < $qty) {
		return false;
	}

	if (sprint($stock_csprice * $qty) != sprint(array_sum($stkval))) {
		return unmanuOut("<li class='err'>Total of disassembled items not equal to assembled item.</li>");
	}

// 	// Retrieve recipe
// 	$sql = "SELECT * FROM cubit.recipies WHERE m_stock_id='$m_stock_id'";
// 	$recipe_rslt = db_exec($sql) or errDie("Unable to retrieve recipe.");
//
// 	// Increase sub stock
// 	while ($recipe_data = pg_fetch_array($recipe_rslt)) {
// 		$sub_qty = $recipe_data["qty"] * $qty;
// 		$sql = "
// 		UPDATE cubit.stock SET units=(units + '$sub_qty')
// 		WHERE stkid='$recipe_data[s_stock_id]'";
// 		db_exec($sql) or errDie("Unable to update stock.");
// 	}

	foreach ($qtys as $stkid => $stk_qty) {
		$sub_qty = $stk_qty;// * $qty;
		$sql = "UPDATE cubit.stock SET units=(units + $sub_qty) WHERE stkid='$stkid'";
		db_exec($sql) or errDie("Unable to update stock quantities.");
	}

	// Remove the old items
	$sql = "UPDATE cubit.stock SET units=(units - $qty) WHERE stkid='$m_stock_id'";
	db_exec($sql) or errDie("Unable to disassemble stock.");

	$sql = "DELETE FROM cubit.dissasemble_save WHERE m_stock_id='$m_stock_id' AND session_id='$_REQUEST[CUBIT_SESSION]'";
	db_exec($sql) or errDie("Unable to remove old entries.");

	doUnmanuAccounting($m_stock_id, $qty, $stkval, $qtys, $tdate);

	$OUTPUT = "
		<h3>Disassemble Stock</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Disassembled</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Item/s successfully disassembled</td>
				<td><a href='stock-view.php'>View Stock</a></td>
			</tr>
		</table>";
	return $OUTPUT;

}




function doManuAccounting($stock_id, $qty, $tdate)
{

	for ($i = 0; $i < $qty; $i++) {
		// Retrieve stock
		$sql = "SELECT * FROM cubit.stock WHERE stkid='$stock_id'";
		$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		$stock_data = pg_fetch_array($stock_rslt);

		// Retrieve store
		$sql = "SELECT * FROM exten.warehouses WHERE whid='$stock_data[whid]'";
		$store_rslt = db_exec($sql) or errDie("Unable to retrieve store.");
		$store_data = pg_fetch_array($store_rslt);

		// Retrieve the total amount of the manufacture
		$sql = "SELECT * FROM cubit.recipies WHERE m_stock_id='$stock_id'";
		$recipe_rslt = db_exec($sql) or errDie("Unable to retrieve recipe.");

		$total_qty = 0;
		$total_amount = 0;

		$updated = false;

		while ($recipe_data = pg_fetch_array($recipe_rslt)) {
			// Retrieve the stock
			$sql = "SELECT * FROM cubit.stock WHERE stkid='$recipe_data[s_stock_id]'";
			$sstock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
			$sstock_data = pg_fetch_array($sstock_rslt);

			$amount = $sstock_data["csprice"] * $recipe_data["qty"];
			$total_amount += $amount;

			stockrec($sstock_data["stkid"], $sstock_data["stkcod"], $sstock_data["stkdes"], "ct", $tdate, $recipe_data["qty"], $amount, "$stock_data[stkcod] Manufactured");

			$refnum = getrefnum();

			// DT Inventory Suspense CT Inventory
			writetrans($store_data["conacc"], $store_data["stkacc"], $tdate,
				$refnum, $amount,
				"$sstock_data[stkcod] ($recipe_data[qty] units) used for ".
				"$stock_data[stkcod] Manufacture");
		}
	}

	$sql = "SELECT * FROM cubit.recipies WHERE m_stock_id='$stock_id'";
	$recipe_rslt = db_exec($sql) or errDie("Unable to retrieve recipe.");

	$cost_price = 0;

	// Update the stock costs
	while ($recipe_data = pg_fetch_array($recipe_rslt)) {
		// Retrieve the stock
		$sql = "SELECT csprice, units, stkid FROM cubit.stock WHERE stkid='$recipe_data[s_stock_id]'";
		$sstock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		$sstock_data = pg_fetch_array($sstock_rslt);

		$csamt = $sstock_data["csprice"] * $sstock_data["units"];

		$sql = "UPDATE cubit.stock SET csamt='$csamt' WHERE stkid='$sstock_data[stkid]'";
		$rslt = db_exec($sql) or errDie("Unable to update stock cost amount.");

		$cost_price += ($sstock_data["csprice"] * $recipe_data["qty"]);
	}

	$total_amount *= $qty;

	$refnum = getrefnum();

	// Retrieve the cost variance account
	$sql = "SELECT * FROM core.accounts WHERE topacc='2160' AND accnum='000'";
	$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account 2160.");
	$acc_data = pg_fetch_array($acc_rslt);

	// Calculate the variance
	$variance_t = $total_amount / $qty;
	$variance_r = sprint($variance_t);

	if ($variance_r > $variance_t) {
		$variance = $variance_r - $variance_t;

		// DT: Inventory Suspense CT: Cost Variance
		writetrans($store_data["conacc"], $acc_data["accid"], $tdate, $refnum, $variance, "$stock_data[stkcod] Manufacturing Variance");

	} elseif ($variance_t > $variance_r) {
		$variance = $variance_t - $variance_r;

		// DT: Cost Variance CT: Inventory Suspense
		writetrans($acc_data["accid"], $store_data["conacc"], $tdate, $refnum, $variance, "$stock_data[stkcod] Manufacturing Variance");
	}

	stockrec($stock_data["stkid"], $stock_data["stkcod"], $stock_data["stkdes"], "dt", $tdate, $qty, $total_amount, "$stock_data[stkcod] Manufactured");
	// DT Inventory CT Inventory Suspense
	writetrans($store_data["stkacc"], $store_data["conacc"], $tdate, $refnum, $total_amount, "$stock_data[stkcod] Manufactured");

	$sql = "SELECT * FROM cubit.stock WHERE stkid='$stock_data[stkid]'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$stock_data = pg_fetch_array($stock_rslt);

	$curr_on_hand = $stock_data["csamt"];

	$cost_on_hand = $cost_price * $qty;
	$cost_on_hand = $curr_on_hand + $cost_on_hand;

	#hack to make php not have to divide by 0 ...
	if (!isset($stock_data["units"]) OR $stock_data["units"] == 0)
		$stock_data["units"] = 1;

	$csprice = $cost_on_hand/$stock_data["units"];

	$sql = "
		UPDATE cubit.stock 
		SET csamt='$cost_on_hand', lcsprice='$cost_price', csprice='$csprice' 
		WHERE stkid='$stock_data[stkid]'";
	db_exec($sql) or errDie("Unable to update manufactured stock item.");

}



function doUnmanuAccounting($stock_id, $qty, $stkval, $qtys, $tdate)
{

	$cost_price = 0;
	
	$refnum = getrefnum();

	for ($i = 0; $i < $qty; $i++) {
		// Retrieve stock
		$sql = "SELECT * FROM cubit.stock WHERE stkid='$stock_id'";
		$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		$stock_data = pg_fetch_array($stock_rslt);

		// Retrieve store
		$sql = "SELECT * FROM exten.warehouses WHERE whid='$stock_data[whid]'";
		$store_rslt = db_exec($sql) or errDie("Unable to retrieve store.");
		$store_data = pg_fetch_array($store_rslt);

// 		// Retrieve the total amount of the manufacture
// 		$sql = "SELECT * FROM cubit.recipies WHERE m_stock_id='$stock_id'";
// 		$recipe_rslt = db_exec($sql) or errDie("Unable to retrieve recipe.");

		$total_qty = 0;
		$total_amount = 0;

// 		while ($recipe_data = pg_fetch_array($recipe_rslt)) {
		foreach ($qtys as $s_stock_id=>$stk_qty) {
			if (!$stk_qty) {
				continue;
			}

			// Retrieve the stock
			$sql = "SELECT * FROM cubit.stock WHERE stkid='$s_stock_id'";
			$sstock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
			$sstock_data = pg_fetch_array($sstock_rslt);

			$amount = $stkval[$sstock_data["stkid"]] / ($stk_qty / $qty);
			$iamount = $stkval[$sstock_data["stkid"]] / $qty;
			$total_amount += $amount;

			stockrec($sstock_data["stkid"], $sstock_data["stkcod"], $sstock_data["stkdes"], "dt", $tdate, $stk_qty / $qty, $iamount, "$stock_data[stkcod] Disassembled");

			// DT Inventory Suspense CT Inventory
			writetrans($store_data["stkacc"], $store_data["conacc"], $tdate,
				$refnum, $iamount, "$sstock_data[stkcod] ($stk_qty units) ".
				"acquired after $stock_data[stkcod] Disassembled");
		}
	}

	$cost_price = 0;

// 	// Retrieve the total amount of the manufacture
// 	$sql = "SELECT * FROM cubit.recipies WHERE m_stock_id='$stock_id'";
// 	$recipe_rslt = db_exec($sql) or errDie("Unable to retrieve recipe.");

	// Update the stock costs
// 	while ($recipe_data = pg_fetch_array($recipe_rslt)) {
	foreach ($qtys as $s_stock_id => $stk_qty) {
		if (!$stk_qty) {
			continue;
		}

		// Retrieve the stock
		$sql = "SELECT * FROM cubit.stock WHERE stkid='$s_stock_id'";
		$sstock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		$sstock_data = pg_fetch_array($sstock_rslt);

		// remove from the onhand the recipe units as we already increased units
		//$csamt = $sstock_data["csprice"] * ($sstock_data["units"] - ($qty * $recipe_data["qty"]));

		$cost_price += $stkval[$sstock_data["stkid"]];
		$newprice = $sstock_data["csamt"] + $stkval[$sstock_data["stkid"]];
		$newuprice = $newprice / $sstock_data["units"];

		$sql = "
			UPDATE cubit.stock 
			SET lcsprice=csprice, csprice='$newuprice', csamt='$newprice' 
			WHERE stkid='$s_stock_id'";
		db_exec($sql) or errDie("Error updating stock prices.");
	}

//	$total_amount *= $qty;

	// Retrieve the cost variance account
	$sql = "SELECT * FROM core.accounts WHERE topacc='2160' AND accnum='000'";
	$acc_rslt = db_exec($sql) or errDie("Unable to retrieve account 2160.");
	$acc_data = pg_fetch_array($acc_rslt);

	// Calculate the variance
	$variance_t = $total_amount / $qty;
	$variance_r = sprint($variance_t);

	if ($variance_r > $variance_t) {
		$variance = $variance_r - $variance_t;

		// DT: Cost Variance CT: Inventory Suspense
		writetrans($store_data["conacc"], $acc_data["accid"], $tdate, $refnum, $variance, "$stock_data[stkcod] Disassemble Variance");

	} elseif ($variance_t > $variance_r) {
		$variance = $variance_t - $variance_r;

		// DT: Inventory Suspense CT: Cost Variance
		writetrans($store_data["conacc"], $acc_data["accid"], $tdate, $refnum, $variance, "$stock_data[stkcod] Disassemble Variance");
	}

	stockrec($stock_data["stkid"], $stock_data["stkcod"], $stock_data["stkdes"], "ct", $tdate, $qty, sprint(array_sum($stkval)), "$stock_data[stkcod] Disassembled");

	// DT Inventory Suspense CT Inventory
	writetrans($store_data["conacc"], $store_data["stkacc"], $tdate, $refnum, sprint(array_sum($stkval)), "$stock_data[stkcod] Disassembled");

	$sql = "SELECT * FROM cubit.stock WHERE stkid='$stock_data[stkid]'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$stock_data = pg_fetch_array($stock_rslt);

	$curr_on_hand = $stock_data["csamt"];

	$unit_cost = $cost_price / $qty;
	$cost_on_hand = $curr_on_hand - $cost_price;

	$sql = "
		UPDATE cubit.stock 
		SET csamt='$cost_on_hand', lcsprice=csprice, csprice='$unit_cost' 
		WHERE stkid='$stock_data[stkid]'";
	db_exec($sql) or errDie("Unable to update manufactured stock item.");
}



?>
