<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		default:
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

require ("gw-tmpl.php");

function enter()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["location"] = "";
	$fields["page_option"] = "Add";

	extract ($fields, EXTR_SKIP);

	$OUTPUT = "<h3>$page_option Location</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='page_option' value='$page_option' />
	<input type='hidden' name='key' value='confirm' />
	".frmupdate_passon()."
	<table cellpadding='2' cellspacing='0' class='shtable'>
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Location</td>
			<td><input type='text' name='location' value='$location' /></td>
		</tr>
	</table>
	<p>
	<input type='submit' value='Confirm &raquo'>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract ($_REQUEST);

	$OUTPUT = "<h3>$page_option Location</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='location' value='$location' />
	<input type='hidden' name='page_option' value='$page_option' />
	<input type='hidden' name='key' value='write' />
	".frmupdate_passon()."
	<table cellpadding='2' cellspacing='0' class='shtable'>
		<tr>
			<th colspan='2'>Confirm</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Location</td>
			<td>$location</td>
		</tr>
	</table>
	<p>
	<input type='submit' name='key' value='&laquo Correction' />
	<input type='submit' value='Write &raquo' />
	</form>";

	return $OUTPUT;
}

function write()
{
	extract ($_REQUEST);

	if ($page_option == "Edit") {
		$sql = "UPDATE cubit.diary_locations SET location='$location'
		WHERE id='$id'";
	} else {
		$sql = "INSERT INTO cubit.diary_locations (location) VALUES ('$location')";
	}
	db_exec($sql) or errDie("Unable to save location.");

	if (frmupdate_passon()) {
		$newlist = new dbSelect("diary_locations", "cubit");
		$newlist->run();

		$a = array();
		if ($newlist->num_rows() > 0) {
			while ($row = $newlist->fetch_array()) {
				$a[$row["id"]] = "$row[location]";
			}
		}
		$js = frmupdate_exec(array($a), true);
	} else {
		$js = "";
	}

	$OUTPUT = "$js
	<h3>$page_option Location</h3>
	<table cellpadding='2' cellspacing='0' class='shtable'>
		<tr>
			<th>Write</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Successfully saved location.</td>
		</tr>
	</table>";

	return $OUTPUT;
}