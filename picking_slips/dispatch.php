<?php

require ("../settings.php");
require ("picking_slip.lib.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "scan":
			$OUTPUT = scan();
			break;
		case "reason":
			$OUTPUT = reason();
			break;
		case "reason_write":
			$OUTPUT = reason_write();
			break;
		case "dispatch":
			$OUTPUT = dispatch();
			break;
		case "dispatch_write":
			$OUTPUT = dispatch_write();
			break;
	}
} else {
	$OUTPUT = scan();
}

require ("../template.php");




function scan()
{

	// Check setting
	$sql = "SELECT set FROM cubit.picking_slip_setting";
	$setting_rslt = db_exec($sql) or errDie("Unable to retrieve setting.");
	$setting = pg_fetch_result($setting_rslt, 0);

	if ($setting == "n") {
		header("Location: picking_slip_settings.php");
	}

	$invoice = array("invoice"=>"Scan Invoice");
	list($barcode) = array_values(flashRed($invoice));
	
	$sordid = decrypt_barcode($barcode);

	if (empty($sordid) || !is_numeric($sordid)) {
		$sordid = 0;
	}

	// Has this invoice been scanned before
	$sql = "SELECT id FROM cubit.pslip_scans WHERE sordid='$sordid'";
	$scan_rslt = db_exec($sql) or errDie("Unable to retrieve scans.");

	$sql = "SELECT sordid FROM cubit.sorders WHERE sordid='$sordid'";
	$sorder_rslt = db_exec($sql) or errDie("Unable to retrieve sales order.");

	if (pg_num_rows($sorder_rslt)) {
		$reason = "";

		if (pg_num_rows($scan_rslt)) {
			$reason = "DUPLICATE (No reason)";
		}
	
		$sql = "
			INSERT INTO cubit.pslip_scans (
				sordid, timestamp, userid, reason
			) VALUES (
				'$sordid', current_timestamp, '".USER_ID."', '$reason'
			)";
		db_exec($sql) or errDie("Unable to record scan.");
	} else {
		return scan_error();
	}

	if (pg_num_rows($scan_rslt)) {
		$scan_id = pglib_lastid("cubit.pslip_scans", "id");
		return reason($scan_id);
	}
	return dispatch($sordid);

}




function scan_error()
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
				<td>
					<h2><li class='err'>Scanned invoice does not exist.</li></h2>
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Next scan in 3 seconds</td>
			</tr>
		</table>
		</center>";
	return $OUTPUT;

}




function reason($scan_id)
{
	$OUTPUT = "
		<center>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='reason_write' />
			<input type='hidden' name='scan_id' value='$scan_id' />
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='3'>
					<h2>
						<div class='err' style='text-decoration: blink'>
							NOTICE: This Invoice was used before - Do NOT allow<br />
							goods to leave premises. This request has been logged<br />
							with your manager.
						</div>
					</h2>
				</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Reason for this double request</td>
				<td><input type='text' name='double_reason' style='width: 100%' /></td>
				<td><input type='submit' value='Submit &raquo' /></td>
			</tr>
		</table>
		</form>
		</center>";
	return $OUTPUT;

}

function reason_write()
{

	extract ($_REQUEST);

	$sql = "UPDATE cubit.pslip_scans SET reason='$double_reason' WHERE id='$scan_id'";
	db_exec($sql) or errDie("Unable to record scan reason.");
	return scan();

}




function dispatch($sordid)
{

	extract ($_REQUEST);

	$sql = "SELECT id, reason FROM cubit.pslip_reasons";
	$reasons_rslt = db_exec($sql) or errDie("Unable to retrieve reasons.");

	$reasons_sel = "<select name='dispatch_reason'>";
	while (list($id, $reason) = pg_fetch_array($reasons_rslt)) {
		if ($reason == "Security Checkpoint / Customer Collect") {
			$sel = "selected='t'";
		} else {
			$sel = "";
		}
		$reasons_sel .= "<option value='$id'>$reason</option>";
	}
	$reasons_sel .= "</select>";

	$OUTPUT = "
		<center>
		<h3>Dispatch</h3>
		<form method='post' action='".SELF."'>
			<input type='hidden' name='key' value='dispatch_write' />
			<input type='hidden' name='sordid' value='$sordid' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Dispatch Reason</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>$reasons_sel</td>
				<td><input type='submit' value='Submit' /></td>
			</tr>
		</table>
		</form>
		</center>";
	return $OUTPUT;

}




function dispatch_write()
{

	extract ($_REQUEST);

	// Retrieve the invoice
	$sql = "SELECT invid FROM cubit.invoices WHERE pslip_sordid='$sordid'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve invoice.");
	$invid = pg_fetch_result($inv_rslt, 0);

	$sql = "INSERT INTO cubit.pslip_dispatched (invid, sordid, reason_id) VALUES ('$invid', '$sordid', '$dispatch_reason')";
	db_exec($sql) or errDie("Unable to add to dispatched.");

	$sql = "UPDATE cubit.invoices SET dispatched='1' WHERE invid='$invid'";
	db_exec($sql) or errDie("Unable to update invoice status.");

	$OUTPUT = "
		<h3>Dispatch</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Write</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><li>Successfully dispatched</li></td>
			</tr>
		</table>";
	return $OUTPUT;

}


?>