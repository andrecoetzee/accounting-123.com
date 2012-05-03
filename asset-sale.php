<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "slct":
			$OUTPUT = slct();
			break;
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "invoice":
			$OUTPUT = invoice();
			break;
	}
} else {
	if (isset($_REQUEST["asset_id"])) {
		$OUTPUT = enter();
	} else {
		$OUTPUT = slct();
	}
}

$OUTPUT .= mkQuickLinks (
	ql("asset-new.php", "Add Asset"),
	ql("asset-view.php", "View Assets")
);

require ("template.php");



function slct()
{

	db_connect ();

	$sql = "SELECT * FROM cubit.assets WHERE remaction IS NULL ORDER BY des ASC";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
	if(pg_numrows($asset_rslt) < 1){
		return "<li class='err'>No Assets Found.</li><br>";
	}

	$asset_sel = "<select name='asset_id' style='width: 100%' onchange='javascript:document.form.submit()'>";
	while ($asset_data = pg_fetch_array($asset_rslt)) {
		$asset_sel.= "<option value='$asset_data[id]'>($asset_data[serial]) $asset_data[des]</option>";
	}
	$asset_sel.= "</select>";

	$OUTPUT = "
				<center>
				<h3>Asset Sale</h3>
				<form method='POST' action='".SELF."' name='form'>
				<input type='hidden' name='key' value='enter' />
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Select Asset</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>$asset_sel</td>
					</tr>
					<tr>
						<td align='right'><input type='submit' value='Next' /></td>
					</tr>
				</table>
				</form>";
	return $OUTPUT;

}



function enter($err = "")
{

	extract ($_REQUEST);

	$fields = array();
	$fields["price"] = "0.00";
	$fields["cust_id"] = 0;
	$fields["vatcode"] = "2";
	$fields["vatinc"] = "inc";
	
	if (!isset($date_year)) {
		explodeDate(DATE_STD, $date_year, $date_month, $date_day);
	}

	extract ($fields, EXTR_SKIP);

	db_connect ();

	// Retrieve asset
	$sql = "SELECT * FROM cubit.assets WHERE id='$asset_id'";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
	$asset_data = pg_fetch_array($asset_rslt);

	// Retrieve asset group
	$sql = "SELECT * FROM cubit.assetgrp WHERE grpid='$asset_data[grpid]'";
	$asgrp_rslt = db_exec($sql) or errDie("Unable to retrieve asset group.");
	$asgrp_data = pg_fetch_array($asgrp_rslt);

	// Retrieve customer
	$sql = "SELECT * FROM cubit.customers ORDER BY surname ASC";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");

	$cust_sel = "<select name='cust_id' style='width: 100%'>";
	$cust_sel.= "<option ".fsel($cust_id == "-1")." value='-1'>Cash Sale</option>";
	//$cust_sel.= "<option ".fsel($cust_id == "-2")." value='-2'>Ledger Account Sale</option>";
	$cust_sel.= "<optgroup label='Customer Sale'>";
	while ($cust_data = pg_fetch_array($cust_rslt)) {
		$sel = fsel($cust_id == $cust_data["cusnum"]);

		$cust_sel.= "
		<option $sel value='$cust_data[cusnum]'>
			$cust_data[cusname] $cust_data[surname]
		</option>";
	}
	$cust_sel .= "</optgroup>";
	$cust_sel.= "</select>";
	
	$cds = qryVatcode();
	$sel_vatcode = db_mksel($cds, "vatcode", $vatcode, "#id", "#code");
	
	if ($asset_data["nonserial"] == "1") {
		if (!isset($qty)) {
			$qty = $asset_data["serial2"];
		}
		
		$qtyinput = "
						<tr class='".bg_class()."'>
							<td>Sell Units</td>
							<td><input type='text' size='4' name='qty' value='$qty' /></td>
						</tr>";
	} else {
		$qtyinput = "<input type='hidden' name='qty' value='1' />";
	}

	$OUTPUT = "
				<center>
				<h3>Asset Sale</h3>
				$err
				<form method='POST' action='".SELF."'>
					<input type='hidden' name='key' value='confirm' />
					<input type='hidden' name='asset_id' value='$asset_id' />
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Details</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Group</td>
						<td>$asgrp_data[grpname]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Serial Number</td>
						<td>$asset_data[serial]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>2nd Serial Number/Qty</td>
						<td>$asset_data[serial2]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Description</td>
						<td>$asset_data[des]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Customer</td>
						<td>$cust_sel</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Date</td>
						<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Vat</td>
						<td>
							<input type='radio' name='vatinc' value='inc' ".fcheck($vatinc!="exc")." /> Including
							<input type='radio' name='vatinc' value='exc' ".fcheck($vatinc=="exc")." /> Excluding
					</tr>
					<tr class='".bg_class()."'>
						<td>Vatcode</td>
						<td>$sel_vatcode</td>
					</tr>
					$qtyinput
					<tr class='".bg_class()."'>
						<td>Selling Price/Asset Unit</td>
						<td nowrap='t'>
							".CUR." <input type='text' size='10' name='price' value='$price'>
						</td>
					</tr>
					<tr>
						<td colspan='2' align='right'>
							<input type='submit' value='Confirm &raquo' />
						</td>
					</tr>
				</table>
				</form>
				</center>";
	return $OUTPUT;

}



function confirm()
{

	extract ($_REQUEST);

	// Retrieve asset
	$sql = "SELECT * FROM cubit.assets WHERE id='$asset_id'";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
	$asset_data = pg_fetch_array($asset_rslt);

	// Retrieve asset group
	$sql = "SELECT * FROM cubit.assetgrp WHERE grpid='$asset_data[grpid]'";
	$asgrp_rslt = db_exec($sql) or errDie("Unable to retrieve asset group.");
	$asgrp_data = pg_fetch_array($asgrp_rslt);

	// Retrieve customer
	if ($cust_id > 0) {
		$sql = "SELECT * FROM cubit.customers WHERE cusnum='$cust_id'";
		$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customers.");
		$cust_data = pg_fetch_array($cust_rslt);
	} else {
		if ($cust_id == "-1") {
			$cust_data["surname"] = "Cash Sale";
		} else if ($cust_id == "-2") {
			$cust_data["surname"] = "Ledger Account Sale";
		} else {
			return enter("<li class='err'>Invalid sale option selected.</li>");
		}
	}
	
	$date = mkdate($date_year, $date_month, $date_day);
	
	/* vat/vatcode display */
	$vcd = qryVatcode($vatcode);
	$vatc_disp = $vcd["code"];
	
	if ($vatinc == "exc") {
		$vat_disp = "Excluding";
	} else {
		$vat_disp = "Including";
		$vatinc = "inc"; // force it to inc if it invalid value
	}

	$OUTPUT = "
				<center>
				<h3>Asset Sale</h3>
				<form method='POST' action='".SELF."'>
					<input type='hidden' name='key' value='invoice' />
					<input type='hidden' name='asset_id' value='$asset_id' />
					<input type='hidden' name='price' value='$price' />
					<input type='hidden' name='cust_id' value='$cust_id' />
					<input type='hidden' name='date' value='$date' />
					<input type='hidden' name='date_year' value='$date_year' />
					<input type='hidden' name='date_month' value='$date_month' />
					<input type='hidden' name='date_day' value='$date_day' />
					<input type='hidden' name='vatinc' value='$vatinc' />
					<input type='hidden' name='vatcode' value='$vatcode' />
					<input type='hidden' name='qty' value='$qty' />
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Confirm</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Group</td>
						<td>$asgrp_data[grpname]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Serial Number</td>
						<td>$asset_data[serial]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>2nd Serial Number</td>
						<td>$asset_data[serial2]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Location</td>
						<td>$asset_data[locat]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Description</td>
						<td>$asset_data[des]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Date</td>
						<td>$date</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Customer</td>
						<td>$cust_data[surname]</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Vat</td>
						<td>$vat_disp</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Vatcode</td>
						<td>$vatc_disp</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Selling Price</td>
						<td>".CUR." ".sprint($price)."</td>
					</tr>
					<tr>
						<td colspan='2' align='right'>
							<input type='submit' name='btn_back' value='&laquo Correction' />
							<input type='submit' value='Invoice &raquo' />
						</td>
					</tr>
				</table>
				</form>
				</center>";
	return $OUTPUT;

}



function invoice()
{

	if (isset($_REQUEST["btn_back"])) {
		return enter();
	}

	extract ($_REQUEST);

	// Retrieve asset
	$sql = "SELECT * FROM cubit.assets WHERE id='$asset_id'";
	$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
	$asset_data = pg_fetch_array($asset_rslt);

	// Retrieve asset group
	$sql = "SELECT * FROM cubit.assetgrp WHERE grpid='$asset_data[grpid]'";
	$grp_rslt = db_exec($sql) or errDie("Unable to retrieve asset group.");
	$grp_data = pg_fetch_array($grp_rslt);

	// Retrieve customer
	$sql = "SELECT * FROM cubit.customers WHERE cusnum='$cust_id'";
	$cust_rslt = db_exec($sql) or errDie("Unable to retrieve customer.");
	$cust_data = pg_fetch_array($cust_rslt);

	if ($cust_id > 0) {
		$ctyp = "s";
		$tval = $cust_id;
	} else if ($cust_id == "-1") {
		$ctyp = "c";
		$tval = "2";
		$cust_data["surname"] = "Cash Sale";
	} else if ($cust_id == "-2") {
		$ctyp = "ac";
		$tval = "";
		$cust_data["surname"] = "Cash Sale";
	} else {
		return enter("<li class='err'>Invalid sale option selected.</li>");
	}
	
	$acc  = "0";
	
	$vatchrg = ($vatinc == "exc") ? "no" : "yes";
	$vcd = qryVatcode($vatcode);
	$va = vatcalca($price * $qty, $vatchrg, "no", 0, $vcd["vat_amount"]);
	
	pglib_transaction("BEGIN");

	if ($cust_data["surname"] == "Cash Sale") {
		$cust_data["paddr1"] = "";
		$cust_data["vatnum"] = "";
	}

	$sql = "INSERT INTO cubit.nons_invoices(cusname, cusaddr, cusvatno, chrgvat,
				sdate, odate, subtot, balance, vat, total, done, username, prd,
				invnum, typ, ctyp, tval, div, accid, salespn)
			VALUES ('$cust_data[surname]', '$cust_data[paddr1]',
				'$cust_data[vatnum]', '$vatchrg', CURRENT_DATE, '$date', '$va[subtotal]', 0,
				'$va[vat]', '$va[total]', 'n', '".USER_NAME."', '".extractMonth($date)."', 0, 
				'inv', '$ctyp', '$tval', '".USER_DIV."', '$acc', 'General')";
	db_exec($sql) or errDie("Unable to create invoice");

	$ni_id = lastinvid();
	
	$asset_saleacc = gethook("accnum", "salesacc", "name", "saleofassets");
	
	$price_all = $price * $qty;
	
	$sql = "INSERT INTO cubit.nons_inv_items (invid, qty, description, div,
				amt, unitcost, accid, rqty, vatex, cunitcost, asset_id)
			VALUES ('$ni_id', '$qty', '$asset_data[des]', '".USER_DIV."',
				'$price_all', '$price', '$asset_saleacc', '0', 
				'$vatcode', '0', '$asset_id')";
	db_exec($sql) or errDie("Unable to create invoice.");
	
	pglib_transaction("COMMIT");

	header("Location: asset-invoice-print.php?invid=$ni_id&printpage=t");
	exit;

}


?>
