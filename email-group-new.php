<?php

require ("settings.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		default:
		case "enter":
			$OUTPUT = enter($_POST);
			break;
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
	}
} else {
	$OUTPUT = enter($_POST);
}

	$OUTPUT .= "<p>".
				mkQuickLinks(
					ql("email-groups.php", "Send Email To Group"),
					ql("email-group-new.php", "Add Email Group"),
					ql("email-group-view.php", "View Email Groups")
				);

require ("template.php");

function enter($_POST,$errors="")
{

	extract ($_POST);

	if(!isset($group))
		$group = "";

	$OUTPUT = "
				<h3>Add Email Group</h3>
				<form method='POST' action='".SELF."'>
					<input type='hidden' name='key' value='confirm' />
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Group Details</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Group</td>
						<td><input type='text' name='group' value='$group' /></td>
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

function confirm($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($group, "string", 1, 255, "Invalid group name.");

	if ($v->isError()) {
		return enter($_POST,$v->genErrors());
	}

	$OUTPUT = "
				<h3>Confirm Group</h3>
				<form method='POST' action='".SELF."'>
					<input type='hidden' name='key' value='write' />
					<input type='hidden' name='group' value='$group' />
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Confirm</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Group</td>
						<td>$group</td>
					</tr>
					<tr>
						<td><input type='submit' name='key' value='&laquo Correction' /></td>
						<td align='right'><input type='submit' value='Write &raquo' /></td>
					</tr>
				</table>
				</form>";
	return $OUTPUT;

}



function write($_POST)
{

	extract ($_POST);

	require_lib("validate");
	$v = new validate;
	$v->isOk($group, "string", 1, 255, "Invalid group name.");

	if ($v->isError()) {
		return enter($_POST,$v->genErrors());
	}
	
	#clean and prepare vars
	$grouptitle = strtolower($group);
	$grouptitle = str_replace("'","",$grouptitle);
	$grouptitle = str_replace("\\","",$grouptitle);
	$grouptitle = str_replace("|","",$grouptitle);
	$grouptitle = str_replace("@","",$grouptitle);
	$grouptitle = str_replace("!","",$grouptitle);
	$grouptitle = str_replace("?","",$grouptitle);
	$grouptitle = str_replace("%","",$grouptitle);
	$grouptitle = str_replace(" ","",$grouptitle);
	
	$write_sql = "INSERT INTO egroups (grouptitle,groupname) VALUES ('$grouptitle','$group')";
	$run_write = db_exec($write_sql) or errDie("Unable to add group information.");

	$OUTPUT = "<h3>Write Group</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr class='".bg_class()."'>
			<td><li>Successfully saved the group.</li></td>
		</tr>
	</table>";
	return $OUTPUT;

}

?>