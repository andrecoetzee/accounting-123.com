<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	case "enter":
		$OUTPUT = enter();
		break;
	case "display":
		$OUTPUT = display();
		break;
	case "write":
		$OUTPUT = write();
		break;
	}
} else {
	$OUTPUT = enter();
}

require ("template.php");



function enter($errors="")
{
	extract ($_REQUEST);
	
	$fields = array();
	$fields["price_one"] = "";
	$fields["price_two"] = "";
	$fields["price_a"] = "";
	$fields["price_b"] = "";
	$fields["price_c"] = "";
	$fields["price_d"] = "";
	$fields["price_e"] = "";
	$fields["factor_a"] = "";
	$fields["factor_b"] = "";
	$fields["factor_c"] = "";
	$fields["factor_d"] = "";
	$fields["factor_e"] = "";

	extract ($fields, EXTR_SKIP);

	$sql = "
	SELECT catid, cat FROM cubit.stockcat
	WHERE div='".USER_DIV."' ORDER BY cat ASC";
	$cat_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");

	$cat_start_sel = "<select name='cat_start_id'>";
	$cat_end_sel = "<select name='cat_end_id'>";

	$cat_start_sel .= "<option value='0'>[None]</option>";
	$cat_end_sel .= "<option value='0'>[None]</option>";
	while (list($id, $cat) = pg_fetch_array($cat_rslt)) {
		$cat_start_sel .= "<option value='$id'>$cat</option>";
		$cat_end_sel .= "<option value='$id'>$cat</option>";
	}
	$cat_start_sel .= "</select>";
	$cat_end_sel .= "</select>";

	if (isset($price_rad)) {
		switch ($price_rad) {
		case "price_all":
			$all_sel = "checked='checked'";
			$recent_sel = "";
			$multi_sel = "";
			break;
		case "price_recent":
			$all_sel = "";
			$recent_sel = "checked='checked'";
			$multi_sel = "";
			break;
		case "price_multi":
			$all_sel = "";
			$recent_sel = "";
			$multi_sel = "checked='checked'";
			break;
		}
	} else {
		$all_sel = "";
		$recent_sel = "";
		$multi_sel = "";
	}

	$OUTPUT = "
		<h3>Advanced Pricelist Update</h3>
		<form method='post' action='".SELF."'>
			<input type='hidden' name='key' value='display' />
		<table cellspacing='0' cellpadding='0'>
			<tr>
				<td>
					<li class='err' style='font-weight: bold; list-style: none'>
						Please make sure you provide the correct information as <br />
						this process cannot be reversed once the write button has<br />
						been pressed.
					</li>
				</td>
			</tr>
			<tr>
				<td>$errors</td>
			</tr>
			<tr>
				<td align='center'>
					<table cellspacing='0' cellpadding='0' width='100%'>
						<tr class='".bg_class()."'>
							<td>Category Start</td>
							<td>$cat_start_sel</td>
							<td>Category End</td>
							<td>$cat_end_sel</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table ".TMPL_tblDflts." width='100%'>
						<tr class='".bg_class()."'>
							<td>&nbsp;</td>
							<td colspan='4'>
								<strong>Create new selling price by selecting one of the following methods.</strong>
							</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><input type='radio' name='price_rad' value='price_all' $all_sel /></td>
							<td>Increase existing selling prices by</td>
							<td align='center'><input type='text' name='price_one' size='3' value='$price_one' /></td>
							<td colspan='2'>(factor 1.1 or 1.2 ect.)</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><input type='radio' name='price_rad' value='price_recent' $recent_sel /></td>
							<td>Calculate new selling price by multiplying<br /> the most recent (not average) cost by factor</td>
							<td align='center'><input type='text' name='price_two' size='3' value='$price_two' /></td>
							<td colspan='2'>(1.45 or 1.55 ect)</td>
						</tr>
						<tr class='".bg_class()."'>
							<td><input type='radio' name='price_rad' value='price_multi' $multi_sel /></td>
							<td colspan='4'>
								<strong>Calculate New selling price by using staggered multiple of cost method below</strong>
							</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>A</td>
							<td>For parts with a cost of between 0 and</td>
							<td align='center'><input type='text' name='price_a' size='7' value='$price_a' /></td>
							<td>multiple by factor</td>
							<td align='center'><input type='text' name='factor_a' size='3' value='$factor_a' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>B</td>
							<td>And for parts with a cost of between</td>
							<td align='center'><input type='text' name='price_b' size='7' value='$price_b' /></td>
							<td>A and B multiply by factor</td>
							<td><input type='text' name='factor_b' size='3' value='$factor_b' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>C</td>
							<td>And for parts with a cost of between</td>
							<td align='center'><input type='text' name='price_c' size='7' value='$price_c' /></td>
							<td>B and C multiply by factor</td>
							<td><input type='text' name='factor_c' size='3' value='$factor_c'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>D</td>
							<td>And for parts with a cost of between</td>
							<td align='center'><input type='text' name='price_d' size='7' value='$price_d' /></td>
							<td>C and D multiply by factor</td>
							<td><input type='text' name='factor_d' size='3' value='$factor_d' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>E</td>
							<td>And for parts with a cost of between</td>
							<td align='center'><input type='text' name='price_e' size='7' value='$price_e' /></td>
							<td>D and E multiply by factor</td>
							<td><input type='text' name='factor_e' size='3' value='$factor_e' /></td>
						</tr>
						<tr>
							<td colspan='5' align='right'><input type='submit' value='Confirm &raquo' /></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>";
	return $OUTPUT;

}




function display()
{

	extract ($_REQUEST);

	require_lib("validate");
	$v = new validate;

	// Validate Categories
	if (isset($cat_start_id) && is_numeric($cat_start_id)) {
		$sql = "SELECT catid FROM cubit.stockcat WHERE catid='$cat_start_id'";
		$cat_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");

		if (!pg_num_rows($cat_rslt)) {
			$v->addError(0, "Invalid starting category");
		}
	}

	if (isset($cat_end_id) && is_numeric($cat_end_id) && $cat_end_id > 0) {
		$sql = "SELECT catid FROM cubit.stockcat WHERE catid='$cat_end_id'";
		$cat_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");

		if (!pg_num_rows($cat_rslt)) {
			$v->addError(0, "Invalid ending category");
		}
	}

	// Validate price options
	$price_options = array("price_all", "price_recent", "price_multi");
	if (!isset($price_rad)) {
		$v->addError(0, "Please select a price option.");
	} elseif (!in_array($price_rad, $price_options)) {
		$v->addError(0, "Invalid price option selected.");
	} else {
		switch ($price_rad) {
		case "price_all":
			$v->isOk($price_one, "float", 1, 20, "Invalid price factor for existing selling prices.");
			break;
		case "price_recent":
			$v->isOk($price_two, "float", 1, 20, "Invalid price factor for most recent prices.");
			break;
		case "price_multi":
			// A
			if (!isset($price_a) || !isset($factor_a)) {
				$v->addError(0, "No cost or factor specified for price A");
			} else {
				$v->isOk($price_a, "float", 1, 20, "Invalid cost (A)");
				$v->isOk($factor_a, "float", 1, 20, "Invalid factor (A)");
			}

			// B
			if (!isset($price_b) || empty($price_b)) {
				break;
			} else {
				$v->isOk($price_b, "float", 1, 20, "Invalid cost (B)");
				$v->isOk($factor_b, "float", 1, 20, "Invalid factor (B)");
			}
			
			// C
			if (!isset($price_c) || empty($price_c)) {
				break;
			} else {
				$v->isOk($price_c, "float", 1, 20, "Invalid cost (C)");
				$v->isOk($factor_c, "float", 1, 20, "Invalid factor (C)");
			}

			// D
			if (!isset($price_d) || empty($price_d)) {
				break;
			} else {
				$v->isOk($price_d, "float", 1, 20, "Invalid cost (D)");
				$v->isOk($factor_d, "float", 1, 20, "Invalid factor (D)");
			}

			// E
			if (!isset($price_e) || empty($price_e)) {
				break;
			} else {
				$v->isOk($price_e, "float", 1, 20, "Invalid cost (E)");
				$v->isOk($factor_e, "float", 1, 20, "Invalid factor (E)");
			}
			break;
		}
	}

	if ($v->isError()) {
	   return enter($v->genErrors());
	}

	// Retrieve starting category name
	$sql = "SELECT cat FROM cubit.stockcat WHERE catid='$cat_start_id'";
	$cat_rslt = db_exec($sql) or errDie("Unable to retrieve start category.");
	$cat_start = pg_fetch_result($cat_rslt, 0);

	// Retrieve ending category name
	if ($cat_end_id) {
		$sql = "SELECT cat FROM cubit.stockcat WHERE catid='$cat_end_id'";
	} else {
		$sql = "SELECT max(cat) FROM cubit.stockcat";
	}
	$cat_rslt = db_exec($sql) or errDie("Unable to retrieve end category.");
	$cat_end = pg_fetch_result($cat_rslt, 0);

	$prices_out = "";
	switch ($price_rad) {
	case "price_all":
		// Retrieve items to update
		$sql = "
		SELECT id, stock.stkid, stkcod, stkdes, price
		FROM exten.plist_prices
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid
		WHERE cat BETWEEN '$cat_start' AND '$cat_end' AND length (stock.stkid) > 0";
		$items_rslt = db_exec($sql)
			or errDie("Unable to retrieve items to update.");
		
		while ($price_data = pg_fetch_array($items_rslt)) {
			$new_price = $price_data["price"] * $price_one;

			if ($new_price == 0) continue;

			$prices_out .= "
				<tr class='".bg_class()."'>
					<td>($price_data[stkcod]) $price_data[stkdes]</td>
					<td align='right'>".sprint($price_data["price"])."</td>
					<td align='right'><input type='text' name='new_price[$price_data[stkid]]' value='".sprint($new_price)."' size='7' style='text-align: right' /></td>
				</tr>";
		}
		break;
	case "price_recent":
		// Retrieve items to update
		$sql = "
		SELECT id, stock.stkid, lcsprice, stkcod, stkdes, price
		FROM exten.plist_prices
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid
		WHERE cat BETWEEN '$cat_start' AND '$cat_end' AND length (stock.stkid) > 0";
		$items_rslt = db_exec($sql)
			or errDie("Unable to retrieve items to update.");

		while ($price_data = pg_fetch_array($items_rslt)) {
			$new_price = $price_data["lcsprice"] * $price_two;

			if ($new_price == 0) continue;

			$prices_out .= "
				<tr class='".bg_class()."'>
					<td>($price_data[stkcod]) $price_data[stkdes]</td>
					<td align='right'>".sprint($price_data["price"])."</td>
					<td align='right'><input type='text' name='new_price[$price_data[stkid]]' value='".sprint($new_price)."' size='7' style='text-align: right' /></td>
				</tr>";
		}
		break;
	case "price_multi":
		// Query A -----------------------------------------------------------
		$sql = "
		SELECT id, stock.stkid, stkcod, stkdes, price FROM exten.plist_prices
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
		WHERE cat BETWEEN '$cat_start' AND '$cat_end' AND
			price BETWEEN 0 AND '$price_a' AND length (stock.stkid) > 0";
		$items_rslt = db_exec($sql) or errDie("Unable to retrieve prices.");

		while ($price_data = pg_fetch_array($items_rslt)) {
			$new_price = $price_data["price"] * $factor_a;

			if ($new_price == 0) continue;

			$prices_out .= "
				<tr class='".bg_class()."'>
					<td>($price_data[stkcod]) $price_data[stkdes]</td>
					<td align='right'>".sprint($price_data["price"])."</td>
					<td align='right'><input type='text' name='new_price[$price_data[stkid]]' value='".sprint($new_price)."' size='7' style='text-align: right' /></td>
				</tr>";

		}

		// Query B -----------------------------------------------------------
		if (!isset($price_b) || empty($price_b)) break;

		$sql = "
		SELECT id, stock.stkid, stkcod, stkdes, price FROM exten.plist_prices
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
		WHERE cat BETWEEN '$cat_start' AND '$cat_end' AND
			price BETWEEN '$price_a' AND '$price_b' AND length (stock.stkid) > 0";
		$items_rslt = db_exec($sql) or errDie("Unable to retrieve prices.");

		while ($price_data = pg_fetch_array($items_rslt)) {
			$new_price = $price_data["price"] * $factor_b;

			if ($new_price == 0) continue;

			$prices_out .= "
				<tr class='".bg_class()."'>
					<td>($price_data[stkcod]) $price_data[stkdes]</td>
					<td align='right'>".sprint($price_data["price"])."</td>
					<td align='right'><input type='text' name='new_price[$price_data[stkid]]' value='".sprint($new_price)."' size='7' style='text-align: right' /></td>
				</tr>";
		}
		
		// Query C -----------------------------------------------------------
		if (!isset($price_c) || empty($price_c)) break;

		$sql = "
		SELECT id, stock.stkid, stkcod, stkdes, price FROM exten.plist_prices
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
		WHERE cat BETWEEN '$cat_start' AND '$cat_end' AND
			price BETWEEN '$price_b' AND '$price_c' AND length (stock.stkid) > 0";
		$items_rslt = db_exec($sql) or errDie("Unable to retrieve prices.");

		while ($price_data = pg_fetch_array($items_rslt)) {
			$new_price = $price_data["price"] * $factor_c;

			if ($new_price == 0) continue;

			$prices_out .= "
				<tr class='".bg_class()."'>
					<td>($price_data[stkcod]) $price_data[stkdes]</td>
					<td align='right'>".sprint($price_data["price"])."</td>
					<td align='right'><input type='text' name='new_price[$price_data[stkid]]' value='".sprint($new_price)."' size='7' style='text-align: right' /></td>
				</tr>";

		}
		
		// Query D -----------------------------------------------------------
		if (!isset($price_d) || empty($price_d)) break;

		$sql = "
		SELECT id, stock.stkid, stkcod, stkdes, price FROM exten.plist_prices
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
		WHERE cat BETWEEN '$cat_start' AND '$cat_end' AND
			price BETWEEN '$price_c' AND '$price_d' AND length (stock.stkid) > 0";
		$items_rslt = db_exec($sql) or errDie("Unable to retrieve prices.");

		while ($price_data = pg_fetch_array($items_rslt)) {
			$new_price = $price_data["price"] * $factor_d;

			if ($new_price == 0) continue;

			$prices_out .= "
				<tr class='".bg_class()."'>
					<td>($price_data[stkcod]) $price_data[stkdes]</td>
					<td align='right'>".sprint($price_data["price"])."</td>
					<td align='right'><input type='text' name='new_price[$price_data[stkid]]' value='".sprint($new_price)."' size='7' style='text-align: right' /></td>
				</tr>";
		}

		// Query E -----------------------------------------------------------
		if (!isset($price_e) || empty($price_e)) break;

		$sql = "
		SELECT id, stock.stkid, stkcod, stkdes, price FROM exten.plist_prices
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
		WHERE cat BETWEEN '$cat_start' AND '$cat_end' AND
			price BETWEEN '$price_d' AND '$price_e' AND length (stock.stkid) > 0";
		$items_rslt = db_exec($sql) or errDie("Unable to retrieve prices.");

		while ($price_data = pg_fetch_array($items_rslt)) {
			$new_price = $price_data["price"] * $factor_e;

			if ($new_price == 0) continue;

			$prices_out .= "
				<tr class='".bg_class()."'>
					<td>($price_data[stkcod]) $price_data[stkdes]</td>
					<td align='right'>".sprint($price_data["price"])."</td>
					<td align='right'><input type='text' name='new_price[$price_data[stkid]]' value='".sprint($new_price)."' size='7' style='text-align: right' /></td>
				</tr>";
		}
	}

	if (empty($prices_out)) {
		$prices_out = "
			<tr class='".bg_class()."'>
				<td colspan='4'><li>No results found.</li></td>
			</tr>";
	}

	$OUTPUT = "
		<h3>Advanced Pricelist Update</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='write' />
			<input type='hidden' name='cat_start_id' value='$cat_start_id' />
			<input type='hidden' name='cat_end_id' value='$cat_end_id' />
			<input type='hidden' name='price_rad' value='$price_rad' />
			<input type='hidden' name='price_one' value='$price_one' />
			<input type='hidden' name='price_two' value='$price_two' />
			<input type='hidden' name='price_a' value='$price_a' />
			<input type='hidden' name='price_b' value='$price_b' />
			<input type='hidden' name='price_c' value='$price_c' />
			<input type='hidden' name='price_d' value='$price_d' />
			<input type='hidden' name='price_e' value='$price_e' />
			<input type='hidden' name='factor_a' value='$factor_a' />
			<input type='hidden' name='factor_b' value='$factor_b' />
			<input type='hidden' name='factor_c' value='$factor_c' />
			<input type='hidden' name='factor_d' value='$factor_d' />
			<input type='hidden' name='factor_e' value='$factor_e' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Stock</th>
				<th>Old Price</th>
				<th>New Price</th>
			</tr>
			$prices_out
			<tr>
				<td colspan='3' align='right'>
					<input type='submit' value='Write &raquo' />
				</td>
			</tr>
		</table>";
	return $OUTPUT;

}




function write()
{

	extract ($_REQUEST);
	
	require_lib("validate");
	$v = new validate;

	// Validate Categories
	if (isset($cat_start_id) && is_numeric($cat_start_id)) {
		$sql = "SELECT catid FROM cubit.stockcat WHERE catid='$cat_start_id'";
		$cat_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");

		if (!pg_num_rows($cat_rslt)) {
			$v->addError(0, "Invalid starting category");
		}
	}

	if (isset($cat_end_id) && is_numeric($cat_end_id) && $cat_end_id > 0) {
		$sql = "SELECT catid FROM cubit.stockcat WHERE catid='$cat_end_id'";
		$cat_rslt = db_exec($sql) or errDie("Unable to retrieve categories.");

		if (!pg_num_rows($cat_rslt)) {
			$v->addError(0, "Invalid ending category");
		}
	}

	// Validate price options
	$price_options = array("price_all", "price_recent", "price_multi");
	if (!isset($price_rad)) {
		$v->addError(0, "Please select a price option.");
	} elseif (!in_array($price_rad, $price_options)) {
		$v->addError(0, "Invalid price option selected.");
	} else {
		switch ($price_rad) {
		case "price_all":
			$v->isOk($price_one, "float", 1, 20, "Invalid price factor for existing selling prices.");
			break;
		case "price_recent":
			$v->isOk($price_two, "float", 1, 20, "Invalid price factor for most recent prices.");
			break;
		case "price_multi":
			// A
			if (!isset($price_a) || !isset($factor_a)) {
				$v->addError(0, "No cost or factor specified for price A");
			} else {
				$v->isOk($price_a, "float", 1, 20, "Invalid cost (A)");
				$v->isOk($factor_a, "float", 1, 20, "Invalid factor (A)");
			}

			// B
			if (!isset($price_b) || empty($price_b)) {
				break;
			} else {
				$v->isOk($price_b, "float", 1, 20, "Invalid cost (B)");
				$v->isOk($factor_b, "float", 1, 20, "Invalid factor (B)");
			}
			
			// C
			if (!isset($price_c) || empty($price_c)) {
				break;
			} else {
				$v->isOk($price_c, "float", 1, 20, "Invalid cost (C)");
				$v->isOk($factor_c, "float", 1, 20, "Invalid factor (C)");
			}

			// D
			if (!isset($price_d) || empty($price_d)) {
				break;
			} else {
				$v->isOk($price_d, "float", 1, 20, "Invalid cost (D)");
				$v->isOk($factor_d, "float", 1, 20, "Invalid factor (D)");
			}

			// E
			if (!isset($price_e) || empty($price_e)) {
				break;
			} else {
				$v->isOk($price_e, "float", 1, 20, "Invalid cost (E)");
				$v->isOk($factor_e, "float", 1, 20, "Invalid factor (E)");
			}
			break;
		}
	}

	if ($v->isError()) {
	   return enter($v->genErrors());
	}
	
	$affected_rows = 0;
	if (isset($new_price)) {
		foreach ($new_price as $stkid=>$value) {
			$sql = "
			UPDATE exten.plist_prices SET price='$value'
			WHERE stkid='$stkid'";
			db_exec($sql);
			$affected_rows++;
		}
	}

/*
	// Retrieve starting category name
	$sql = "SELECT cat FROM cubit.stockcat WHERE catid='$cat_start_id'";
	$cat_rslt = db_exec($sql) or errDie("Unable to retrieve start category.");
	$cat_start = pg_fetch_result($cat_rslt, 0);

	// Retrieve ending category name
	if ($cat_end_id) {
		$sql = "SELECT cat FROM cubit.stockcat WHERE catid='$cat_end_id'";
	} else {
		$sql = "SELECT max(cat) FROM cubit.stockcat";
	}
	$cat_rslt = db_exec($sql) or errDie("Unable to retrieve end category.");
	$cat_end = pg_fetch_result($cat_rslt, 0);

	// Keep track of the amount of pricelist items updated
	$affected_rows = 0;

	pglib_transaction("BEGIN");
	switch ($price_rad) {
	case "price_all":
		// Retrieve items to update
		$sql = "
		SELECT id
		FROM exten.plist_prices
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
		WHERE cat BETWEEN '$cat_start' AND '$cat_end'";
		$items_rslt = db_exec($sql)
			or errDie("Unable to retrieve items to update.");
		
		while (list($id) = pg_fetch_array($items_rslt)) {
			$sql = "
			UPDATE exten.plist_prices SET price=(price * '$price_one')
			WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to update pricelist.");

			$affected_rows += pg_affected_rows($rslt);
		}
		break;
	case "price_recent":
		// Retrieve items to update
		$sql = "
		SELECT id, lcsprice
		FROM exten.plist_prices 
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid
		WHERE cat BETWEEN '$cat_start' AND '$cat_end'";
		$items_rslt = db_exec($sql)
			or errDie("Unable to retrieve items to update.");

		while (list($id, $lcsprice) = pg_fetch_array($items_rslt)) {
			$sql = "
			UPDATE exten.plist_prices SET price=('$lcsprice' * '$price_two')
			WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to update pricelist.");

			$affected_rows += $pg_affected_rows($rslt);
		}
		break;
	case "price_multi":
		// Query A -----------------------------------------------------------
		$sql = "
		SELECT id FROM exten.plist_prices 
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid

		WHERE cat BETWEEN '$cat_start' AND '$cat_end' AND
			price BETWEEN 0 AND '$price_a'";
		$items_rslt = db_exec($sql) or errDie("Unable to retrieve prices.");

		while (list($id) = pg_fetch_array($items_rslt)) {
			$sql = "
			UPDATE exten.plist_prices SET price=(price * '$factor_a')
			WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to update pricelist.");

			$affected_rows += pg_affected_rows($rslt);
		}

		// Query B -----------------------------------------------------------
		if (!isset($price_b) || empty($price_b)) break;

		$sql = "
		SELECT id FROM exten.plist_prices 
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid

		WHERE cat BETWEEN '$cat_start' AND '$cat_end' AND
			price BETWEEN '$price_a' AND '$price_b'";
		$items_rslt = db_exec($sql) or errDie("Unable to retrieve prices.");

		while (list($id) = pg_fetch_array($items_rslt)) {
			$sql = "
			UPDATE exten.plist_prices SET price=(price * '$factor_b')
			WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to update pricelist.");

			$affected_rows += pg_affected_rows($rslt);
		}
		
		// Query C -----------------------------------------------------------
		if (!isset($price_c) || empty($price_c)) break;

		$sql = "
		SELECT id FROM exten.plist_prices 
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid

		WHERE cat BETWEEN '$cat_start' AND '$cat_end' AND
			price BETWEEN '$price_b' AND '$price_c'";
		$items_rslt = db_exec($sql) or errDie("Unable to retrieve prices.");

		while (list($id) = pg_fetch_array($items_rslt)) {
			$sql = "
			UPDATE exten.plist_prices SET price=(price * '$factor_c')
			WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to update pricelist.");

			$affected_rows += pg_affected_rows($rslt);
		}
		
		// Query D -----------------------------------------------------------
		if (!isset($price_d) || empty($price_d)) break;

		$sql = "
		SELECT id FROM exten.plist_prices 
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid

		WHERE cat BETWEEN '$cat_start' AND '$cat_end' AND
			price BETWEEN '$price_c' AND '$price_d'";
		$items_rslt = db_exec($sql) or errDie("Unable to retrieve prices.");

		while (list($id) = pg_fetch_array($items_rslt)) {
			$sql = "
			UPDATE exten.plist_prices SET price=(price * '$factor_d')
			WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to update pricelist.");

			$affected_rows += pg_affected_rows($rslt);
		}

		// Query E -----------------------------------------------------------
		if (!isset($price_e) || empty($price_e)) break;

		$sql = "
		SELECT id FROM exten.plist_prices 
			LEFT JOIN cubit.stockcat ON plist_prices.catid=stockcat.catid
			LEFT JOIN cubit.stock ON plist_prices.stkid=stock.stkid

		WHERE cat BETWEEN '$cat_start' AND '$cat_end' AND
			price BETWEEN '$price_d' AND '$price_e'";
		$items_rslt = db_exec($sql) or errDie("Unable to retrieve prices.");

		while (list($id) = pg_fetch_array($items_rslt)) {
			$sql = "
			UPDATE exten.plist_prices SET price=(price * '$factor_e')
			WHERE id='$id'";
			$rslt = db_exec($sql) or errDie("Unable to update pricelist.");
			
			$affected_rows += pg_affected_rows($rslt);
		}
	}
 */
	pglib_transaction("COMMIT");

	$msg = "
		<li class='yay' style='font-size: 1.2em'>
			<strong>$affected_rows</strong> prices, updated successfully!
			<a href='pricelist-xls.php?listid=2'>Export</a>
		</li>";
	return enter($msg);

}


?>
