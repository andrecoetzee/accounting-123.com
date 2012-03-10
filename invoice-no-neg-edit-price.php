<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	case "view":
		$OUTPUT = view();
		break;
	case "edit":
		$OUTPUT = edit();
		break;
	case "write":
		$OUTPUT = write();
		break;
	}
} else {
	$OUTPUT = view();
}

require ("template.php");

function view()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["frm_year"] = date("Y");
	$fields["frm_month"] = date("m");
	$fields["frm_day"] = date("d");
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["search"] = "";

	extract ($fields, EXTR_SKIP);

	$sql = "
	SELECT invid, invnum, odate, surname, cordno, ordno, salespn
	FROM cubit.invoices
	WHERE done='n'
	ORDER BY invnum DESC";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

	$inv_out = "";
	while ($inv_data = pg_fetch_array($inv_rslt)) {
		$sql = "
		SELECT count(id) FROM cubit.inv_items
		WHERE invid='$inv_data[invid]'";
		$count_rslt = db_exec($sql) or errDie("Unable to retrieve items.");
		$count = pg_fetch_result($count_rslt, 0);

		if ($count == 0) continue;

		$inv_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$inv_data[odate]</td>
			<td>$inv_data[surname]</td>
			<td>$inv_data[cordno]</td>
			<td>$inv_data[ordno]</td>
			<td>$inv_data[salespn]</td>
			<td>
				<a href='".SELF."?key=edit&invid=$inv_data[invid]'>
					Select
				</a>
			</td>
		</tr>";
	}

	$OUTPUT = "
	<center>
	<h3>Edit Invoice Prices</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='view' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Date Range</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("frm", $frm_year, $frm_month, $frm_day)."</td>
			<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
		</tr>
		<tr>
			<th colspan='2'>Search</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2'>
				<input type='text' name='search' value='$search'
				style='width: 100%' />
			</td>
		</tr>
		<tr>
			<td colspan='2' align='center'>
				<input type='submit' value='Search' />
			</td>
		</tr>
	</table>
	</form>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Date</th>
			<th>Customer</th>
			<th>Customer Order No.</th>
			<th>Order No</th>
			<th>Sales Person</th>
			<th>&nbsp;</th>
		</tr>
		$inv_out
	</table>
	</center>";

	return $OUTPUT;
}

function edit($errors="")
{
	extract ($_REQUEST);

	$sql = "
	SELECT id, whname, stkcod, stkdes, description, qty, unitcost
	FROM cubit.inv_items
		LEFT JOIN cubit.stock ON inv_items.stkid=stock.stkid
		LEFT JOIN exten.warehouses ON stock.whid=warehouses.whid
	WHERE invid='$invid'";
	$items_rslt = db_exec($sql) or errDie("Unable to retrieve items.");

	$items_out = "";
	while ($items_data = pg_fetch_array($items_rslt)) {
		if (!empty($items_data["description"])) {
			$description = "$items_data[description]";
		} else {
			$description = "($items_data[stkcod]) $items_data[stkdes]";
		}

		$items_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>$items_data[whname]</td>
			<td>$description</td>
			<td>$items_data[qty]</td>
			<td>
				<input type='text' name='unitprices[$items_data[id]]'
				value='$items_data[unitcost]' style='text-align: right' />
			</td>
		</tr>";
	}

	$OUTPUT = "
	<h3>Edit Invoice Prices</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='invid' value='$invid' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='3'>$errors</td>
		</tr>
		<tr>
			<th>Store</th>
			<th>Stock</th>
			<th>Qty</th>
			<th>Cost Per Unit</th>
		</tr>
		$items_out
		<tr>
			<td colspan='4' align='center'>
				<input type='submit' value='Update' />
			</td>
		</tr>
	</table>
	</form>";

	$OUTPUT .= mkQuickLinks (
		ql("cust-credit-stockinv-no-neg.php?invid=$invid&cont=true", "Continue Invoice"),
		ql(SELF, "Edit Invoice Prices")
	);


	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);
	
	require_lib("validate");
	$v = new validate;
	$v->isOk($invid, "num", 1, 20, "Invalid invoice selection.");

	if ($v->isError()) {
		return edit($v->genErrors());
	}

	pglib_transaction("BEGIN");

	$subtotal = 0;

	if (isset($unitprices) && is_array($unitprices)) {
		foreach ($unitprices as $id=>$unitprice) {
			$sql = "SELECT qty FROM cubit.inv_items WHERE id='$id'";
			$qty_rslt = db_exec($sql) or errDie("Unable to retrieve qty.");
			$qty = pg_fetch_result($qty_rslt, 0);

			$sql = "
			UPDATE cubit.inv_items SET unitcost='$unitprice'
			WHERE id='$id'";
			db_exec($sql) or errDie("Unable to update unit cost.");

			$subtotal += ($unitprice * $qty);
		}
	}

	$vat = $subtotal / 100 * 14;
	$total = $subtotal + $vat;

	$sql = "
	UPDATE cubit.invoices SET total='$total', subtot='$subtotal', vat='$vat'
	WHERE invid='$invid'";
	db_exec($sql) or errDie("Unable to update invoice totals.");

	pglib_transaction("COMMIT");

	return edit();
}
?>
