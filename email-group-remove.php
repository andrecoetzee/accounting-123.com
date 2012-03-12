<?php

require ("settings.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		default:
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
	}
} else {
	$OUTPUT = confirm($_GET);
}

	$OUTPUT .= "<p>".
				mkQuickLinks(
					ql("email-groups.php", "Send Email To Group"),
					ql("email-group-new.php", "Add Email Group"),
					ql("email-group-view.php", "View Email Groups")
				);

require ("template.php");


function confirm($_POST,$err="")
{

	extract ($_POST);

	if(!isset($id))
		return "Invalid Use Of Module.";

	require_lib("validate");
	$v = new validate;
	$v->isOk($id, "string", 1, 255, "Invalid group name.");

	if ($v->isError()) {
		return enter($_POST,$v->genErrors());
	}

	db_connect ();
	$get_info = "SELECT * from egroups WHERE id = '$id' LIMIT 1";
	$run_info = db_exec($get_info) or errDie("Unable to get group information.");
	if(pg_numrows($run_info) < 1){
		return "Invalid Use Of Module.";
	}
	
	$data = pg_fetch_array($run_info);
	
	extract ($data);

	$OUTPUT = "
				<h3>Confirm Group Removal</h3>
				<form method='POST' action='".SELF."'>
					<input type='hidden' name='key' value='write' />
					<input type='hidden' name='id' value='$id' />
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Confirm</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Group Title</td>
						<td>$grouptitle</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Group Name</td>
						<td>$groupname</td>
					</tr>
					".TBL_BR."
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
	$v->isOk($id, "num", 1, 255, "Invalid group id.");

	if ($v->isError()) {
		return enter($_POST,$v->genErrors());
	}

	$get_grp = "SELECT grouptitle FROM egroups WHERE id = '$id' LIMIT 1";
	$run_grp = db_exec($get_grp) or errDie ("Unable to get email group information (0)");
	if (pg_numrows($run_grp) < 1){
		#no group found ???
		return confirm ($_POST,"<li class='err'>Email group not found.</li>");
	}

	$gtitle = pg_fetch_result ($run_grp,0,0);

	$write_sql = "DELETE FROM egroups WHERE id = '$id'";
	$run_write = db_exec($write_sql) or errDie("Unable to remove group information.");

	$write_sql2 = "DELETE FROM email_groups WHERE email_group = '$gtitle'";
	$run_write2 = db_exec($write_sql2) or errDie ("Unable to remove email group email addresses.");

	$OUTPUT = "<h3>Write Group</h3>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td><li>Successfully removed the group.</li></td>
		</tr>
	</table>";
	return $OUTPUT;

}

?>