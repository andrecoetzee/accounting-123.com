<?php

require ("settings.php");

if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		default:
		case "enter":
			$OUTPUT = enter($HTTP_POST_VARS);
			break;
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
	}
} else {
	$OUTPUT = enter($HTTP_POST_VARS);
}

	$OUTPUT .= "<p>".
				mkQuickLinks(
					ql("email-groups.php", "Send Email To Group"),
					ql("email-group-new.php", "Add Email Group"),
					ql("email-group-view.php", "View Email Groups")
				);

require ("template.php");

function enter($HTTP_POST_VARS,$errors="")
{

	extract ($HTTP_POST_VARS);

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
					<tr bgcolor='".bgcolorg()."'>
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

function confirm($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	require_lib("validate");
	$v = new validate;
	$v->isOk($group, "string", 1, 255, "Invalid group name.");

	if ($v->isError()) {
		return enter($HTTP_POST_VARS,$v->genErrors());
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
					<tr bgcolor='".bgcolorg()."'>
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



function write($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	require_lib("validate");
	$v = new validate;
	$v->isOk($group, "string", 1, 255, "Invalid group name.");

	if ($v->isError()) {
		return enter($HTTP_POST_VARS,$v->genErrors());
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
		<tr bgcolor='".bgcolorg()."'>
			<td><li>Successfully saved the group.</li></td>
		</tr>
	</table>";
	return $OUTPUT;

}

?>