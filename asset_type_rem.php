<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = remove();
			break;
	}
} else {
	$OUTPUT = confirm();
}

$OUTPUT .= mkQuickLinks(
	ql("asset-new.php", "Add Asset"),
	ql("asset-view.php", "View Assets"),
	ql("asset_type_save.php", "Add Asset Type"),
	ql("asset_type_view.php", "View Asset Types")
);

require ("template.php");

function confirm()
{
	extract ($_REQUEST);

	$sql = "SELECT * FROM cubit.asset_types WHERE id='$id'";
	$at_rslt = db_exec($sql) or errDie("Unable to retrieve asset type.");
	$at_data = pg_fetch_array($at_rslt);

	$name = $at_data["name"];
	$descr = $at_data["description"];

	$OUTPUT = "<h3>Remove Asset Type</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='id' value='$id' />
	<input type='hidden' name='key' value='write' />
	<table ".TMPL_tblDflts.">
		<tr>
			<td colspan='2'>$errors</td>
		</tr>
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".REQ."Name</td>
			<td>$name</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Description</td>
			<td>$descr</td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Remove &raquo' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function remove()
{
	extract ($_REQUEST);

	$sql = "DELETE FROM cubit.asset_types WHERE id='$id'";
	db_exec($sql) or errDie("Unable to remove asset type.");

	$OUTPUT = "<h3>Remove Asset Type</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Remove</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Successfully Removed the Asset Type</td>
		</tr>
	</table>";

	return $OUTPUT;
}