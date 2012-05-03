<?php

require ("../settings.php");

if (isset($_REQUEST["button"])) {
	list($button) = array_keys($_REQUEST["button"]);

	switch ($button) {
	case "add_type":
		$OUTPUT = type_add();
		break;
	}
} else if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
	case "display":
		$OUTPUT = display();
		break;
	}
} else {
	$OUTPUT = display();
}

require ("../template.php");

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["type_id"] = 0;

	extract ($fields, EXTR_SKIP);

	$sql = "
	SELECT id, type_name FROM cubit.dispatch_types
	ORDER BY type_name ASC";
	$types_rslt = db_exec($sql) or errDie("Unable to retrieve types.");

	$types_sel = "
	<select name='type_id' onchange='javascript:document.form.submit'
	style='width: 100%'>
		<option value='0'>Please Select</option>";
	while ($types_data = pg_fetch_array($types_rslt)) {
		$sel = ($types_data["id"] == $type_id) ? "selected='t'" : "";

		$types_sel .= "
		<option value='$types_data[id]' $sel>
			$types_data[type_name]
		</option>";
	}

	$OUTPUT = "
	<h3>Dispatch Types (How goods are leaving the premesis)</h3>
	<form method='post' action='".SELF."' name='form'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Dispatch Types</th>
		</tr>
		<tr class='".bg_class()."'>
			<td colspan='2'>$types_sel</td>
		</tr>
		<tr class='".bg_class()."'>
			<td><input type='text' name='type_name' /></td>
			<td>
				<input type='submit' name='button[add_type]' value='Add' />
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}
