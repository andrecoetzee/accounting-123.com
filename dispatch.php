<?php

require ("../settings.php");
require ("../picking_slips/picking_slip.lib.php");

error_reporting(E_ALL);

if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);

	switch ($button) {
	case "collect_types":
		$OUTPUT = collect_types();
		break;
	}
} else if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	case "collect_types":
		$OUTPUT = collect_types();
		break;
	case "collect_decide":
		$OUTPUT = collect_decide();
		break;
	case "collect_customer":
		$OUTPUT = collect_customer();
		break;
	case "collect_thirdparty":
		$OUTPUT = collect_thirdparty();
		break;

	case "invoice_scan":
		$OUTPUT = invoice_scan();
		break;
	}
} else {
	$OUTPUT = collect_types();
}

require ("../template.php");

function collect_types()
{
	extract($_REQUEST);

	$hardcoded_types = array(
		"hard_customer"=>"Customer Collection",
		"hard_thirdparty"=>"Third Party Collection"
	);

	$types_sel = "<select name='dispatch_type' style='width: 100%'>";
	foreach ($hardcoded_types as $type=>$name) {
		$types_sel .= "<option value='$type'>$name</option>";
	}

	$sql = "SELECT id, name FROM cubit.dispatch_how ORDER BY name ASC";
	$how_rslt = db_exec($sql) or errDie("Unable to retrieve how.");

	while ($how_data = pg_fetch_array($how_rslt)) {
		$types_sel .= "<option value='$how_data[id]'>$how_data[name]</option>";
	}
	$types_sel .= "</select>";

	$OUTPUT = "
	<center>
	<h3>Dispatch</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='collect_decide' />
	<table ".TMPL_tblDflts.">
		<tr bgcolor='".bgcolorg()."'>
			<td><strong>How are goods leaving the premisis?</strong></td>
			<td rowspan='2'>
				<input type='submit' value='Continue &raquo'
				style='height: 100%; font-weight: bold;' />
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>$types_sel</td>
		</tr>
	</table>
	</form>
	</center>";

	return $OUTPUT;
}

function collect_decide()
{
	extract ($_REQUEST);

	switch ($dispatch_type) {
	case "hard_customer":
		return collect_customer();
		break;
	case "hard_thirdparty":
		return collect_thirdparty();
		break;
	default:
		return collect_default();
		break;
	}
}

function collect_customer()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["driver_name"] = "";
	$fields["driver_idnum"] = "";
	$fields["driver_plate"] = "";
	$fields["driver_comments"] = "";
	$fields["dispatch_type"] = "hard_customer";

	extract ($fields, EXTR_SKIP);

	$OUTPUT = "
	<center>
	<h3>Dispatch</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='invoice_scan' />
	<input type='hidden' name='dispatch_type' value='$dispatch_type' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Customer Collect</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Driver Name</td>
			<td>
				<input type='text' name='driver_name' value='$driver_name' />
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>ID Number</td>
			<td>
				<input type='text' name='driver_idnum' value='$driver_idnum' />
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Vehicle Registration</td>
			<td>
				<input type='text' name='driver_plate' value='$driver_plate' />
			</td>
		</tr>
		<tr>
			<th colspan='2'>Comments</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2'>
				<textarea name='driver_comments' style='width: 100%'>"
					."$driver_comments".
				"</textarea>
			</td>
		</tr>
		<tr>
			<td>
				<input type='submit' name='button[collect_types]'
				value='&laquo; Correction' />
			</td>
			<td align='right'>
				<input type='submit' value='Continue &raquo;' />
			</td>
		</tr>
	</table>
	</form>
	</center>";

	return $OUTPUT;
}

function collect_thirdparty()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["driver_waybill"] = "";
	$fields["driver_name"] = "";
	$fields["driver_idnum"] = "";
	$fields["driver_plate"] = "";
	$fields["driver_comments"] = "";
	$fields["dispatch_type"] = "hard_customer";

	extract ($fields, EXTR_SKIP);

	$OUTPUT = "
	<center>
	<h3>Dispatch</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='invoice_scan' />
	<input type='hidden' name='dispatch_type' value='$dispatch_type' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Third Party Collect</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Waybill Number</td>
			<td>
				<input type='text' name='driver_waybill' value='$driver_waybill' />
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Driver Name</td>
			<td>
				<input type='text' name='driver_name' value='$driver_name' />
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>ID Number</td>
			<td>
				<input type='text' name='driver_idnum' value='$driver_idnum' />
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Vehicle Registration</td>
			<td>
				<input type='text' name='driver_plate' value='$driver_plate' />
			</td>
		</tr>
		<tr>
			<th colspan='2'>Comments</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='2'>
				<textarea name='driver_comments' style='width: 100%'>"
					."$driver_comments".
				"</textarea>
			</td>
		</tr>
		<tr>
			<td>
				<input type='submit' name='button[collect_types]'
				value='&laquo; Correction' />
			</td>
			<td align='right'>
				<input type='submit' value='Continue &raquo;' />
			</td>
		</tr>
	</table>
	</form>
	</center>";

	return $OUTPUT;
}

function collect_default()
{
	extract ($_REQUEST);

	$sql = "SELECT name FROM cubit.dispatch_how WHERE id='$dispatch_type'";
	$how_rslt = db_exec($sql) or errDie("Unable to retrieve how.");
	$how = pg_fetch_result($how_rslt, 0);

	$OUTPUT = "
	<h3>$how</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='invoice_scan' />
	<input type='hidden' name='dispatch_type' value='$how' />
	<table ".TMPL_tblDflts.">
		<tr>		
			<th colspan='2'>$how</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>$how</td>
			<td><input type='text' name='default' /></td>
		</tr>
		<tr>
			<td colspan='2'>
				<input type='submit' value='Continue &raquo' />
			</td>
		</tr>
	</table>";

	return $OUTPUT;
}


function invoice_scan()
{
	extract ($_REQUEST);
/*
	// Check setting
	$sql = "SELECT set FROM cubit.picking_slip_setting";
	$setting_rslt = db_exec($sql) or errDie("Unable to retrieve setting.");
	$setting = pg_fetch_result($setting_rslt, 0);

	if ($setting == "n") {
		header("Location: picking_slip_settings.php");
	}
 */
	$invoice = array("invoice"=>"Scan Invoice");
	list($barcode) = array_values(flashRed($invoice, "", $_REQUEST));
	$invid = decrypt_barcode($barcode);
	
	if (empty($invid) || !is_numeric($invid)) {
		$invid = 0;
	}

/*
	// Has this invoice been scanned before
	$sql = "SELECT id FROM cubit.pslip_scans WHERE sordid='$sordid'";
	$scan_rslt = db_exec($sql) or errDie("Unable to retrieve scans.");
 */

	$sql = "SELECT id FROM cubit.dispatch_scans WHERE invid='$invid'";
	$scan_rslt = db_exec($sql) or errDie("Unable to retrieve scans.");

	$sql = "SELECT invid FROM ".which_invoice($invid)." WHERE invid='$invid'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");

	$duplicate = (pg_num_rows($scan_rslt)) ? 1 : 0;
	
	if (pg_num_rows($inv_rslt)) {
		$sql = "
			INSERT INTO cubit.dispatch_scans (invid, timestamp, userid,
				duplicate, dispatch_type)
				VALUES ('$invid', current_timestamp, '".USER_ID."',
					'$duplicate', '$dispatch_type')";
		db_exec($sql) or errDie("Unable to record scan.");
	}

	$scan_id = pglib_lastid("cubit.pslip_scans", "id");
	if (pg_num_rows($scan_rslt)) {
		return invoice_error_block($scan_id);
	} else if (!pg_num_rows($inv_rslt)) {
		return invoice_error_notfound();
	}

	return dispatch_now($scan_id);
}

function invoice_error_notfound()
{
	define("TIMEOUT", 3);

	$get_ar = array();
	foreach ($_REQUEST as $varname=>$value) {
		$get_ar[] = "$varname=$value";
	}
	$get = implode("&", $get_ar);

	$OUTPUT = "
	<script>
		setTimeout('Redirect()', ".(TIMEOUT * 1000).");
		function Redirect()
		{
			location.href = '".SELF."?$get';
		}
	</script>
	<center>
	<table ".TMPL_tblDflts.">
		<tr>
			<td>
				<h2><li class='err'>Scanned invoice does not exist.</li></h2>
			</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Next scan in 3 seconds</td>
		</tr>
	</table>
	</center>";

	return $OUTPUT;
}

function invoice_error_block($scan_id)
{
	$OUTPUT = "
	<center>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='reason_write' />
	<input type='hidden' name='scan_id' value='$scan_id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='3'>
				<h2>
					<div class='err' style='text-decoration: blink'>
					NOTICE: Do not dispatch as these goods have already<br />
					been dispatched -- Call Supervisor
					</div>
				</h2>
			</td>
		</tr>
	</table>
	</form>
	</center>";

	return $OUTPUT;
}

function dispatch_now($scan_id)
{
	$OUTPUT = "
	<h3>Dispatch Now</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Dispatch</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><input type='submit' value='Dispatch Now' /></td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function dispatch_now_write($scan_id)
{
	extract ($_REQUEST);

	$sql = "INSERT INTO cubit.dispatched (scan_id) VALUES ('$scan_id')";
	db_exec($sql) or errDie("Unable to retrieve dispatch.");

	return collect_types();
}
