<?php

require ("settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "enter":
			$OUTPUT = enter();
			break;
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
	}
} else {
	$OUTPUT = enter();
}

$OUTPUT .= mkQuickLinks(
	ql("asset-new.php", "Add Asset"),
	ql("asset-view.php", "View Assets"),
	ql("asset_type_save.php", "Add Asset Type"),
	ql("asset_type_view.php", "View Asset Types")
);

require ("template.php");



function enter($errors="")
{

	extract ($_REQUEST);

	$fields = array();
	$fields["page_option"] = "Add";
	$fields["name"] = "";
	$fields["descr"] = "";
	$fields["id"] = 0;

	extract ($fields, EXTR_SKIP);

	if ($page_option == "Edit") {
		$sql = "SELECT * FROM cubit.asset_types WHERE id='$id'";
		$at_rslt = db_exec($sql) or errDie("Unable to retrieve asset type.");
		$at_data = pg_fetch_array($at_rslt);

		$name = $at_data["name"];
		$descr = $at_data["description"];
	}

	$OUTPUT = "
		<h3>$page_option Asset Type</h3>
		<form method='post' action='".SELF."'>
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='page_option' value='$page_option' />
			<input type='hidden' name='id' value='$id' />
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'>$errors</td>
			</tr>
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>".REQ."Name</td>
				<td><input type='text' name='name' value='$name' /></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td><textarea name='descr' rows='5' cols='20'>$descr</textarea></td>
			</tr>
			<tr>
				<td colspan='2' align='right'>
					<input type='submit' value='Confirm &raquo' />
				</td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}


function confirm()
{

	extract ($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($name, "string", 1, 80, "Invalid type name.");
	$v->isOk($descr, "string", 0, 255, "Invalid description.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	$OUTPUT = "
		<h3>$page_option Asset Type</h3>
		<form method='post' action='".SELF."'>
			<input type='hidden' name='key' value='write' />
			<input type='hidden' name='page_option' value='$page_option' />
			<input type='hidden' name='id' value='$id' />
			<input type='hidden' name='name' value='$name' />
			<input type='hidden' name='descr' value='$descr' />
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'>$errors</td>
			</tr>
			<tr>
				<th colspan='2'>Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>".REQ."Name</td>
				<td>$name</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Description</td>
				<td>$descr</td>
			</tr>
			<tr>
				<td colspan='2' align='right'>
					<input type='submit' value='Write &raquo' />
				</td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}


function write()
{

	extract ($_REQUEST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($name, "string", 1, 80, "Invalid type name.");
	$v->isOk($descr, "string", 0, 255, "Invalid description.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	if ($page_option == "Edit") {
		$sql = "UPDATE cubit.asset_types SET name='$name', description='$descr' WHERE id='$id'";
	} else {
		$sql = "INSERT INTO cubit.asset_types (name, description) VALUES ('$name', '$descr')";
	}
	db_exec($sql) or errDie("Unable to save asset type.");

	$OUTPUT = "
		<h3>$page_option Asset Type</h3>
		<form method='post' action='".SELF."'>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Write</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Successfully Saved the Asset Type.</td>
			</tr>
		</table>
		</form>";
	return $OUTPUT;

}


?>