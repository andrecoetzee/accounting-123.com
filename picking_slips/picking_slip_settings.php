<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "enter":
			$OUTPUT = enter();
			break;
		case "write":
			$OUTPUT = write();
			break;
	}
} else {
	$OUTPUT = enter();
}

$OUTPUT .= "<br>"
			.mkQuickLinks(
				ql ("../stock-view.php","View Stock"),
				ql ("unsigned_invoices_report.php","View Outstanding Invoices")
			);

require ("../template.php");




function enter()
{

	$sql = "SELECT set FROM cubit.picking_slip_setting";
	$set_rslt = db_exec($sql) or errDie("Unable to retrieve setting.");
	$set = pg_fetch_result($set_rslt, 0);
	
	if ($set == "y") {
		$set_y = "checked";
		$set_n = "";
	} else {
		$set_y = "";
		$set_n = "checked";
	}

	$OUTPUT = "
		<h3>Picking Slip Settings</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='write' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Sales Order</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Use Picking Slip</td>
				<td>
					<input type='radio' name='pslip' value='y' $set_y> Yes
					<input type='radio' name='pslip' value='n' $set_n> No
				</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Write &raquo' /></td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}




function write()
{

	extract ($_REQUEST);

	$sql = "UPDATE cubit.picking_slip_setting SET set='$pslip'";
	db_exec($sql) or errDie("Unable to update setting.");

	$OUTPUT = "
		<h3>Picking Slip Settings</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Write</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><li>Successfully saved picking slip setting.</li></td>
			</tr>
		</table>";
	return $OUTPUT;

}


?>