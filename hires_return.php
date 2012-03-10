<?
require ("../settings.php");

if (!isset($_REQUEST["invid"])) {
	$OUTPUT = "<li class='err'>Invalid use of module.</li>";
	require ("../template.php");
}

$OUTPUT = display();

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	$sql = "SELECT * FROM hire.hire_return WHERE invid='$invid'";
	$inv_rslt = db_exec($sql) or errDie("Unable to retrieve returns.");

	while ($inv_data = pg_fetch_array($inv_rslt)) {
		$sql = "SELECT * FROM cubit.assets WHERE id='$inv_data[asset_id]'";
		$asset_rslt = db_exec($sql) or errDie("Unable to retrieve asset.");
		$asset_data = pg_fetch_array($asset_rslt);

		$items_out .= "<tr bgcolor='".bgcolorg()."'>
			<td>".getSerial($asset_data["id"], 1)." $asset_data[des]</td>
			<td align='center'>
				<input type='checkbox' name='workshop[$asset_data[id]]'
				value='$item_data[id]' />
			</td>
			<td><input type='text' name='description[$asset_data[id]]' /></td>
		</tr>";
	}

	$OUTPUT = "<h3>Return</h3>
	<form method='post' action='".SELF."'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Plant</th>
			<th>Return to<br />Workshop</th>
		</tr>
		$items_out
		<tr>
			<td colspan='2' align='center'>
				<input type='submit' value='Return' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function workshop()
{
	if (isset($workshop)) {
		foreach ($workshop as $key=>$value) {
			assetToWorkshop($asset_id, $description[$key]);
		}
	}

	header("Location: hire-nons-invoice-print.php?invid=$nons_invid&key=cconfirm&ctyp=s&cusnum=$hi_data[cusnum]&post=true");
}