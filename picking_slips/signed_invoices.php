<?php

require ("../settings.php");

require ("picking_slip.lib.php");
require_lib("manufact");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "scan":
			$OUTPUT = scan();
			break;
		case "enter":
			$OUTPUT = enter();
			break;
		case "write":
			$OUTPUT = write();
			break;
		case "select":
			$OUTPUT = select_view();
			break;
		case "view":
			$OUTPUT = view();
			break;
		case "status":
			$OUTPUT = update_status();
			break;
	}
} else {
	$OUTPUT = scan();
}

require ("../template.php");




function scan()
{

	$invoice = array("invoice"=>"Scan Invoice");
	$barcode = flashRed($invoice);
	$barcode = $barcode["invoice"];

	$sorder_num = decrypt_barcode($barcode);

	if (empty($sorder_num) || !is_numeric($sorder_num)) {
		$sorder_num = 0;
	}

	$prd_union = array();
	for ($i = 1; $i <= 14; $i++) {
		$prd_union[] = "SELECT pslip_sordid FROM \"$i\".pinvoices WHERE pslip_sordid='$sorder_num'";
	}
	$pinvoices_sql = implode(" UNION ", $prd_union);

	$sql = "SELECT pslip_sordid FROM cubit.invoices WHERE pslip_sordid='$sorder_num' UNION $pinvoices_sql";
	$invoice_rslt = db_exec($sql) or errDie("Unable to check sales order id.");

	if (!pg_num_rows($invoice_rslt) || $sorder_num == 0) {
		return scan_error("Scanned invoice does not exist.");
	}

	$sql = "SELECT sordid FROM cubit.pslip_signed_index WHERE sordid='$sorder_num'";
	$psi_rslt = db_exec($sql) or errDie("Unable to retrieve index.");

	if (pg_num_rows($psi_rslt) > 0) {
		scan_error("Signed invoice already uploaded");
	}

	return enter($sorder_num);

}




function scan_error($msg="")
{

	define("TIMEOUT", 3);

	$OUTPUT = "
		<script>
			setTimeout('Redirect()', ".(TIMEOUT * 1000).");
			function Redirect()
			{
				location.href = '".SELF."';
			}
		</script>
		<center>
		<table ".TMPL_tblDflts.">
			<tr>
				<td><h2><li class='err'>$msg</li></h2></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Next scan in 3 seconds</td>
			</tr>
		</table>
		</center>";
	return $OUTPUT;

}




function enter($sorder_num, $errors="")
{

	extract ($_REQUEST);

	$OUTPUT = "
		<h3>Customer Signed Invoices</h3>
		<form method='POST' action='".SELF."' enctype='multipart/form-data'>
			<input type='hidden' name='key' value='write' />
			<input type='hidden' name='sordid' value='$sorder_num' />
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'>$errors&nbsp;</td>
			<tr>
				<th colspan='2'>Select signed invoice</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='file' name='file' /></td>
				<td>
					<input type='submit' value='Upload' style='font-weight; bold' />
				</td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}




function write()
{

	extract ($_REQUEST);

	if (!preg_match("/(png|jpg|gif)$/", $_FILES["file"]["name"])) {
		$msg = "<li class='err'>We only accept images of type png, jpg or gif</li>";
		return enter($sorder_num, $msg);
	}

	$fp = fopen($_FILES["file"]["tmp_name"], "rb");

	$buf = "";
	while (!feof($fp)) {
		$buf .= fread($fp, 1024);
	}

	$file = base64_encode($buf);

	$sql = "INSERT INTO cubit.pslip_signed_files (file) VALUES ('$file')";
	db_exec($sql) or errDie("Unable to add signed invoice.");

	$id = pglib_lastid("cubit.pslip_signed_files", "id");

	$sql = "
		INSERT INTO cubit.pslip_signed_index (
			id, file_name, file_type, sordid
		) VALUES (
			'$id', '".$_FILES["file"]["name"]."', '".$_FILES["file"]["type"]."', '$sordid'
		)";
	db_exec($sql) or errDie("Unable to add signed invoice index.");

	$OUTPUT = "
		<h3>Signed Invoice</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Write</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><li>Signed Invoice Successfully Added.</li></td>
			</tr>
		</table>";
	return $OUTPUT;

}




function select_view()
{

	extract ($_REQUEST);
	
	$fields = array();
	$fields["from_year"] = date("Y");
	$fields["from_month"] = date("m");
	$fields["from_day"] = "01";
	$fields["to_year"] = date("Y");
	$fields["to_month"] = date("m");
	$fields["to_day"] = date("d");
	$fields["search"] = "";
	
	extract ($fields, EXTR_SKIP);

	$from_date = "$from_year-$from_month-$from_day";
	$to_date = "$to_year-$to_month-$to_day";

	$sql = "
		SELECT invid, invnum, surname, odate, pslip_sordid FROM cubit.invoices
		WHERE signed='0' AND pslip_sordid!='0' AND done='y' AND printed='y'
			AND odate BETWEEN '$from_date' AND '$to_date' AND
			cusname ILIKE '$search%' AND invnum ILIKE '$search%'
		ORDER BY odate DESC";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoices.");

	$inv_out = "";
	while ($inv_data = pg_fetch_array($inv_rslt)) {
		$inv_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$inv_data[odate]</td>
				<td>$inv_data[invnum]</td>
				<td>$inv_data[surname]</td>
				<td>
					<a href='".SELF."?key=view&sordid=$inv_data[pslip_sordid]'>
						Select
					</a>
				</td>
			</tr>";
	}

	if (empty($inv_out)) {
		$inv_out = "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='4'><li>No results found.</li></td>
			</tr>";
	}

	$OUTPUT = "
		<center>
		<h3>Signed Invoices</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='select' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='3'>Date Range</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".mkDateSelect("from", $from_year, $from_month, $from_day)."</td>
				<td>&nbsp; <b>To</b> &nbsp;</td>
				<td>".mkDateSelect("to", $to_year, $to_month, $to_day)."</td>
			</tr>
			<tr>
				<th colspan='3'>Search</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='3'><input type='text' name='search' value='$search' style='width: 100%' /></td>
			</tr>
			<tr>
				<td colspan='3' align='center'><input type='submit' value='Search' /></td>
			</tr>
		</table>
		</form>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Invoice Date</th>
				<th>Invoice No</th>
				<th>Customer</th>
				<th>Select</th>
			</tr>
			$inv_out
		</table>
		</center>";
	return $OUTPUT;

}




function view()
{

	extract ($_REQUEST);

	$sql = "SELECT * FROM cubit.invoices WHERE pslip_sordid='$sordid'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
	$inv_data = pg_fetch_array($inv_rslt);

	$OUTPUT = "
		<h3>Check Customer Signed Invoices</h3>
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<th width='50%'>Signed Invoice</th>
				<th width='50%'>Invoice Details</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
					<img src='get_signed_image.php?sordid=$sordid' width='100%' />
				</td>
				<td valign='top'>
					<table ".TMPL_tblDflts." width='100%'>
						<tr bgcolor='".bgcolorg()."'>
							<td width='25%'><b>Invoice No</b>: </td>
							<td width='25%'>$inv_data[invnum]</td>
							<td width='25%'><b>Date</b>: </td>
							<td width='25%'>$inv_data[odate]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td width='25%'><b>Customer</b>: </td>
							<td width='25%'>$inv_data[surname]</td>
							<td width='25%'><b>Account No</b>: </td>
							<td width='25%'>$inv_data[cusacc]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td width='25%'><b>Proforma Inv No</b>: </td>
							<td width='25%'></td>
							<td width='25%'><b>Sales Order No</b>: </td>
							<td width='25%'>$inv_data[sordid]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td width='25%'><b>Customer VAT No</b>: </td>
							<td width='25%'>$inv_data[cusvatno]</td>
							<td width='25%'><b>Customer Order No</b>: </td>
							<td width='25%'>$inv_data[cordno]</td>
						</tr>
					</table>";

	$sql = "
	SELECT stkcod, stkdes, description, qty, unitcost, disc, amt
	FROM cubit.inv_items
		LEFT JOIN cubit.stock ON inv_items.stkid=stock.stkid
	WHERE invid='$inv_data[invid]'";
	$item_rslt = db_exec($sql) or errDie("Unable to retrieve invoice items.");

	$item_out = "";
	while ($item_data = pg_fetch_array($item_rslt)) {
		if (!empty($item_data["description"])) {
			$description = $item_data["description"];
		} else {
			$description = $item_data["stkdes"];
		}

		$item_out .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$item_data[stkcod]</td>
				<td>$description</td>
				<td>$item_data[qty]</td>
				<td>$item_data[unitcost]</td>
				<td>$item_data[disc]</td>
				<td>$item_data[amt]</td>
			</tr>";
	}
	
	$OUTPUT .= "
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<th>Code</th>
						<th>Description</th>
						<th>Qty</th>
						<th>Unit Price</th>
						<th>Unit Discount</th>
						<th>Amount</th>
					</tr>
					$item_out
				</table>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2' align='center'>
			<a href='".SELF."?key=status&sordid=$sordid'>
				I certify and confirm that the Invoice and Document information
				matches and that the image file has been signed by the customer.
			</a>
			</td>
		</tr>
	</table>";
	return $OUTPUT;

}




function update_status()
{

	extract ($_REQUEST);

	$sql = "UPDATE cubit.invoices SET dispatched='1', signed='1' WHERE pslip_sordid='$sordid'";
	db_exec($sql) or errDie("Unable to update status.");

	$OUTPUT = "
		<h3>Signed Invoice</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Write</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><li>Invoice successfully updated</li></td>
			</tr>
		</table>";
	return $OUTPUT;

}



?>