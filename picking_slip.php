<?php

require ("settings.php");
require_lib("encrypt");

if (isset($_REQUEST["key"])) {
	$key = strtolower($_REQUEST["key"]);
	switch ($key) {
		case "stock_out":
			$OUTPUT = stock_out();
			break;
		case "stock_update":
			$OUTPUT = stock_update();
			break;
		case "print":
			$OUTPUT = print_slip();
			break;
		case "dispatch_out":
			$OUTPUT = dispatch_out();
			break;
		case "invoice":
			$OUTPUT = invoice();
			break;
		case "quote":
			$OUTPUT = fromQuote();
			break;
	}
} else {
	$OUTPUT = stock_out();
}

$OUTPUT .= mkQuickLinks (
	ql(SELF."?key=stock_out", "Create Picking Slip"),
	ql(SELF."?key=dispatch_out", "Picking Slip Dispatch"),
	ql("stock-add.php", "Add Stock")
);

require ("template.php");

function stock_out($error="")
{
	extract ($_REQUEST);
	
	if (!isset($slip_id)) {
		$sql = "INSERT INTO cubit.pick_slips (user_id) VALUES ('".USER_ID."')";
		$slip_rslt = db_exec($sql) or errDie("Unable to create picking slip.");
		$slip_id = pglib_lastid("cubit.pick_slips", "id");
	}

	$sql = "SELECT id, stkcod, stkdes, units, qty
			FROM cubit.pickslip_stk
				LEFT JOIN cubit.stock ON pickslip_stk.stock_id=stock.stkid
			WHERE pickslip_id='$slip_id'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve pickslip stock.");
	
	$items_out = "";
	while (list($id ,$stkcod, $stkdes, $units, $qty) = pg_fetch_array($stock_rslt)) {
		$items_out .= "
		<tr bgcolor='".bgcolorg()."'>
			<td>($stkcod) $stkdes</td>
			<td>$units</td>
			<td>$qty</td>
			<td><input type='checkbox' name='remove[$id]' value='$id' /></td>
		</tr>";
	}

	// Stock dropdown
	$sql = "SELECT stkid, stkcod, stkdes FROM cubit.stock ORDER BY stkcod ASC";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	$stock_sel = "
	<select name='stock_id'>
		<option value='0'>[None]</option>";
	while (list($stkid, $stkcod, $stkdes) = pg_fetch_array($stock_rslt)) {
		$stock_sel .= "<option value='$stkid'>$stkcod - $stkdes</option>";
	}
	$stock_sel .= "</select>";

	$qty = 1;

	$OUTPUT = "
	<h3>Picking Slip - Add Stock</h3>
	<form method='post' action='".SELF."' name='form'>
	<input type='hidden' name='key' value='stock_update' />
	<input type='hidden' name='slip_id' value='$slip_id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='4'>$error</td>
		</tr>
		<tr>
			<th>Stock</th>
			<th>Units on Hand</th>
			<th>Quantity</th>
			<th>Remove</th>
		</tr>
		$items_out
		<tr bgcolor='".bgcolorg()."'>
			<td>$stock_sel</td>
			<td>&nbsp;</td>
			<td><input type='text' name='qty' value='$qty' size='3' /></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan='4' align='center'>
				<input type='submit' value='Update' />
				<input type='submit' name='key' value='Print' />
			</td>
		</tr>
	</table>
	</form>";
	
	return $OUTPUT;
}

function stock_update()
{
	extract ($_REQUEST);
	
	if (is_numeric($stock_id) && $stock_id && $qty > 0) {	
		$sql = "SELECT qty FROM cubit.pickslip_stk
				WHERE pickslip_id='$slip_id' AND stock_id='$stock_id'";
		$stock_rslt = db_exec($sql) or errDie("Unable to retrieve existing stock.");
		
		if (pg_num_rows($stock_rslt)) {
			$sql = "UPDATE cubit.pickslip_stk SET qty=(qty+'$qty')
					WHERE pickslip_id='$slip_id' AND stock_id='$stock_id'";
		} else {		
			$sql = "INSERT INTO cubit.pickslip_stk (pickslip_id, stock_id, qty)
					VALUES ('$slip_id', '$stock_id', '$qty')";
		}
		db_exec($sql) or errDie("Unable to add stock item.");
		
	}
	
	if (isset($remove)) {
		foreach ($remove as $id) {
			$sql = "DELETE FROM cubit.pickslip_stk WHERE id='$id'";
			db_exec($sql) or errDie("Unable to remove item from picking slip.");
		}
	}
	
	return stock_out();
}

function fromQuote()
{
	extract ($_REQUEST);
	
	$sql = "INSERT INTO cubit.pick_slips (user_id) VALUES ('".USER_ID."')";
	$ps_rslt = db_exec($sql) or errDie("Unable to retrieve slips.");
	$ps_id = pglib_lastid("cubit.pick_slips", "id");
	
	$sql = "SELECT * FROM cubit.quote_items WHERE quoid='$quoid'";
	$quote_rslt = db_exec($sql) or errDie("Unable to retrieve quote items.");
	
	while ($quote_data = pg_fetch_array($quote_rslt)) {
		$sql = "INSERT INTO cubit.pickslip_stk (pickslip_id, stock_id, qty)
				VALUES ('$ps_id', '$quote_data[stkid]', '$quote_data[qty]')";
		db_exec($sql) or errDie("Unable to add pickslip.");
	}
	
	header("Location: ".SELF."?key=print&slip_id=$ps_id");
}

function print_slip()
{
	extract ($_REQUEST);

	$sql = "SELECT stkid, stkcod, stkdes, qty
			FROM cubit.pickslip_stk
				LEFT JOIN cubit.stock ON pickslip_stk.stock_id=stock.stkid
			WHERE pickslip_id='$slip_id'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve picking slip.");
	
	$items_out = "";
	while (list($stkid, $stkcod, $stkdes, $qty) = pg_fetch_array($stock_rslt)) {
		
		for ($i = 0; $i < $qty; $i++) {
			$items_out .= "
			<tr>
				<td>($stkcod) $stkdes</td>
				<td align='center'>___________________________</td>
				<td align='center'>___________________________</td>
			</tr>";
		}
	}

	$OUTPUT = "
	<table ".TMPL_tblDflts." width='100%' style='border: 1px solid #000'>
		<tr><td>
		<table ".TMPL_tblDflts." width='100%' style='border: 1px solid #000'>
			<tr>
				<td><h1>Picking Slip</h1></td>
				<td align='right'><img src='".pick_slip_barcode($slip_id)."' /></td>
			</tr>
		</table>
		</td></tr>
		<tr><td>
		<table ".TMPL_tblDflts." width='100%' style='border: 1px solid #000'>
			<tr>
				<td><b>Stock</b></td>
				<td align='center'><b>Serial 1</b></td>
				<td align='center'><b>Serial 2</b></td>
			</tr>
			$items_out
		</table>
	</table>";
	
	require ("tmpl-print.php");
}

function dispatch_out()
{
	extract ($_REQUEST);
	
	$fields = array();
	$fields["barcode"] = "";
	$fields["slip_id"] = 0;
	
	extract ($fields, EXTR_SKIP);
	
	$items_out = "";
	if (!empty($barcode)) {
		$slip_id = decrypt_barcode($barcode);
		if (!is_numeric($slip_id)) $slip_id = 0;
		 
		$sql = "SELECT id, stkid, stkcod, stkdes, qty
				FROM cubit.pickslip_stk 
					LEFT JOIN cubit.stock ON pickslip_stk.stock_id=stock.stkid
				WHERE pickslip_id='$slip_id'";
		$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
		
		$items_out .= "
		<tr>
			<th>Stock</th>
			<th>Quantity</th>
			<th>Serial 1</th>
			<th>Serial 2</th>
		</tr>";
		
		while (list($id, $stkid, $stkcod, $stkdes, $qty) = 
			   pg_fetch_array($stock_rslt)) {
			if (!isset($serial1[$id])) $serial1[$id] = "";
			if (!isset($serial2[$id])) $serial2[$id] = "";
		
			$items_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>($stkcod) $stkdes</td>
				<td>$qty</td>
				<td>
					<input type='text' name='serial1[$id]' value='$serial1[$id]' />
				</td>
				<td>
					<input type='text' name='serial2[$id]' value='$serial2[$id]' />
				</td>
			</tr>";
		}
	}
	
	if (!empty($items_out)) {
		$items_out .= "
		<tr>
			<td colspan='4' align='center'>
				<input type='submit' name='key' value='invoice' />
			</td>
		</tr>";
	}
	
	$OUTPUT = "
	<center>
	<h3>Picking Slip - Dispatch</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='dispatch_out' />
	<input type='hidden' name='barcode' value='$barcode' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Scan Picking Slip</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='text' name='barcode' value='$barcode' /></td>
			<td><input type='submit' value='Scan &raquo' />
		</tr>
	</table>
	</form>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='slip_id' value='$slip_id' />
	<input type='hidden' name='barcode' value='$barcode' />
	<table ".TMPL_tblDflts.">
		$items_out
	</table>
	</form>
	</center>";
	
	return $OUTPUT;
}

function invoice()
{
	extract ($_REQUEST);
	
	$invnum = divlastid("inv");

	$sql = "INSERT INTO cubit.invoices(deptid, chrgvat, odate, printed, done, 
				username, prd, invnum, div, systime, barcode, pickslip_id)
			VALUES ('".USER_DIV."', 'inc', current_date, 'n', 'n',
				'".USER_NAME."', '".PRD_DB."', '$invnum', '".USER_DIV."',
				current_date, '$barcode', '$slip_id')";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
	$invid = lastinvid();
	
	$sql = "SELECT stkid, whid, qty, vatcode FROM cubit.pickslip_stk
				LEFT JOIN cubit.stock ON pickslip_stk.stock_id=stock.stkid
				LEFT JOIN cubit.vatcodes WHERE stock.vatcode=vatcodes.code
			WHERE pickslip_id='$slip_id'";
	$stock_rslt = db_exec($sql) or errDie("Unable to retrieve stock.");
	
	while (list($stkid, $whid, $qty, $vatcode) = pg_fetch_array($stock_rslt)) {
	
		$sql = "INSERT INTO cubit.inv_items (invid, whid, stkid, qty, div,
					vatcode)
				VALUES ('$invid', '$whid', '$stkid', '$qty', '".USER_DIV."',
					'$vatcode')";
		db_exec($sql) or errDie("Unable to add inventory items.");
	}

	$OUTPUT = "
	<script>
		popupOpen(\"cust-credit-stockinv.php?invid=$invid&cont=true\");
		move(\"".SELF."\");
	</script>";
	
	return $OUTPUT;
}

function pick_slip_barcode($invid)
{
	$invid = str_pad($invid, 10, "0", STR_PAD_LEFT);

	$enc = new Encryption;
	$barcode = $enc->encrypt("MiDMaCoR", $invid);
	$barcode = base64_encode($barcode);
	
	$barcode = preg_replace("/=/", "", $barcode);
	
	return getBarcode($barcode, "code128");
}

function decrypt_barcode($barcode)
{
	$decrypt = base64_decode($barcode."==");
	
	$dec = new Encryption;
	$decrypt = $dec->decrypt("MiDMaCoR", $decrypt);

	return $decrypt;
}