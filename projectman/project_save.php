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

$OUTPUT .= mkQuickLinks(
	ql("project_save.php", "Add Project"),
	ql("project_view.php", "View Projects"),
	ql("project_charter.php", "Project Charter"),
	ql("gantt_display.php", "Gantt Chart")
);

require ("../template.php");



function enter($errors="")
{

	extract ($_REQUEST);

	$fields = array();
	$fields["name"] = "";
	$fields["champion_id"] = 0;
	$fields["sponsor_id"] = 0;
	$fields["leader_id"] = 0;
	$fields["edate_day"] = date("d");
	$fields["edate_month"] = date("m");
	$fields["edate_year"] = date("Y");
	$fields["priority"] = 1;
	$fields["sub_project"] = "no";

	extract ($fields, EXTR_SKIP);

	if (strtolower($page_option) == "edit") {
		$sql = "SELECT * FROM project.projects WHERE id='$id'";
		$po_rslt = db_exec($sql) or errDie("Unable to retrieve project details.");

		if (pg_num_rows($po_rslt)) {
			extract(pg_fetch_array($po_rslt));
		} else {
			$page_option = "Add";
		}
	}

	// Champion dropdown ------------------------------------------------------
	$sql = "SELECT * FROM project.people";
	$champ_rslt = db_exec($sql) or errDie("Unable to retrieve champion.");

	$champ_sel = "<select name='champion_id' style='width: 100%'>";
	while ($champ_data = pg_fetch_array($champ_rslt)) {
		$sql = "SELECT * FROM cubit.users WHERE userid='$champ_data[user_id]'";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve champion user.");
		$user_data = pg_fetch_array($user_rslt);

		if ($champion_id == $user_data["userid"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$champ_sel .= "<option value='$user_data[userid]' $sel>$user_data[username]</option>";
	}
	$champ_sel .= "</select>";

	// Sponsor dropdown -------------------------------------------------------
	$sql = "SELECT * FROM project.people";
	$sponsor_rslt = db_exec($sql) or errDie("Unable to retrieve sponsor.");

	$sponsor_sel = "<select name='sponsor_id' style='width: 100%'>";
	while ($sponsor_data = pg_fetch_array($sponsor_rslt)) {
		$sql = "SELECT * FROM cubit.users WHERE userid='$sponsor_data[user_id]'";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve sponsor user.");
		$user_data = pg_fetch_array($user_rslt);

		if ($sponsor_id == $user_data["userid"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$sponsor_sel .= "<option value='$user_data[userid]' $sel>$user_data[username]</option>";
	}
	$sponsor_sel .= "</select>";

	// Leader dropdown --------------------------------------------------------
	$sql = "SELECT * FROM project.people";
	$leader_rslt = db_exec($sql) or errDie("Unable to retrieve leader.");

	$leader_sel = "<select name='leader_id' style='width: 100%'>";
	while ($leader_data = pg_fetch_array($leader_rslt)) {
		$sql = "SELECT * FROM cubit.users WHERE userid='$leader_data[user_id]'";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve leader user.");
		$user_data = pg_fetch_array($user_rslt);

		if ($leader_id == $user_data["userid"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$leader_sel .= "<option value='$user_data[userid]' $sel>$user_data[username]</option>";
	}
	$leader_sel .= "</select>";

	// Priority dropdown ------------------------------------------------------
	$priority_sel = "<select name='priority'>";
	for ($i = 1; $i <= 10; $i++) {
		if ($i == $priority) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$priority_sel .= "<option value='$i' $sel>$i</option>";
	}
	$priority_sel .= "</select>";

	$sql = "SELECT * FROM project.projects WHERE sub='no'";
	$mproj_rslt = db_exec($sql) or errDie("Unable to retrieve main projects.");

	$mproj_sel = "<select name='main_id'>";
	while ($mproj_data = pg_fetch_array($mproj_rslt)) {
		if ($main_id == $mproj_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$mproj_sel .= "<option value='$mproj_data[id]' $sel>$mproj_data[name]</option>";
	}
	$mproj_sel .= "</select>";

	if ($sub_project == "yes") {
		$sproj_y = "checked";
		$sproj_n = "";
	} else {
		$sproj_y = "";
		$sproj_n = "checked";
	}

	$OUTPUT = "
		<h3>$page_option Project</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='id' value='$id' />
			<input type='hidden' name='page_option' value='$page_option' />
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'>$errors</td>
			</tr>
			<tr>
				<th colspan='2'>Project Details</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Name</td>
				<td><input type='text' name='name' value='$name' style='width: 100%' /></td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Champion</td>
				<td>$champ_sel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Sponsor</td>
				<td>$sponsor_sel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Leader</td>
				<td>$leader_sel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Expected Date of Completion</td>
				<td>".mkDateSelect("edate", $edate_year, $edate_month, $edate_day)."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Priority</td>
				<td>$priority_sel</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Sub Project</td>
				<td align='center'>
					Yes <input type='radio' name='sub_project' value='yes' $sproj_y />
					No <input type='radio' name='sub_project' value='no' $sproj_n />
					$mproj_sel
				</td>
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

	if (!isset($champion_id) || !isset($sponsor_id) || !isset($leader_id)) {
		return enter("<li class='err'>No people were selected</li>");
	}

	require_lib("validate");

	$v = new validate;
	$v->isOk($name, "string", 1, 255, "Invalid project name.");
	$v->isOk($champion_id, "num", 1, 20, "Invalid champion.");
	$v->isOk($sponsor_id, "num", 1, 20, "Invalid sponsor.");
	$v->isOk($leader_id, "num", 1, 20, "Invalid leader.");
	$v->isOk($edate_day, "num", 1, 2, "Invalid expected date (day).");
	$v->isOk($edate_month, "num", 1, 2, "Invalid expected date (month).");
	$v->isOk($edate_year, "num", 4, 4, "Invalid expected date (year).");
	$v->isOk($priority, "num", 1, 9, "Invalid priority.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	$people_types = array(
		"champion" => "champion_id", 
		"sponsor" => "sponsor_id", 
		"leader" => "leader_id"
	);

	foreach ($people_types as $key=>$value) {
		$sql = "SELECT username FROM cubit.users WHERE userid='".$$value."'";
		$user_rslt = db_exec($sql) or errDie("Unable to retrieve user.");
		$$key = pg_fetch_result($user_rslt, 0);
	}


	$edate = "$edate_day-$edate_month-$edate_year";

	$OUTPUT = "
		<h3>$page_option Project</h3>
		<form method='POST' action='".SELF."'>
			<input type='hidden' name='key' value='write' />
			<input type='hidden' name='id' value='$id' />
			<input type='hidden' name='page_option' value='$page_option' />
			<input type='hidden' name='name' value='$name' />
			<input type='hidden' name='champion_id' value='$champion_id' />
			<input type='hidden' name='sponsor_id' value='$sponsor_id' />
			<input type='hidden' name='leader_id' value='$leader_id' />
			<input type='hidden' name='edate_day' value='$edate_day' />
			<input type='hidden' name='edate_month' value='$edate_month' />
			<input type='hidden' name='edate_year' value='$edate_year' />
			<input type='hidden' name='priority' value='$priority' />
		<table ".TMPL_tblDflts.">
			<tr>
				<th colspan='2'>Confirm</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Name</td>
				<td>$name</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Champion</td>
				<td>$champion</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Sponsor</td>
				<td>$sponsor</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Leader</td>
				<td>$leader</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Expected Date of Project Completion</td>
				<td>$edate</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Priority</td>
				<td>$priority</td>
			</tr>
			<tr>
				<td><input type='submit' name='key' value='&laquo Correction' /></td>
				<td align='right'><input type='submit' value='Write &raquo' /></td>
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
	$v->isOk($name, "string", 1, 255, "Invalid project name.");
	$v->isOk($champion_id, "num", 1, 20, "Invalid champion.");
	$v->isOk($sponsor_id, "num", 1, 20, "Invalid sponsor.");
	$v->isOk($leader_id, "num", 1, 20, "Invalid leader.");
	$v->isOk($edate_day, "num", 1, 2, "Invalid expected date (day).");
	$v->isOk($edate_month, "num", 1, 2, "Invalid expected date (month).");
	$v->isOk($edate_year, "num", 4, 4, "Invalid expected date (year).");
	$v->isOk($priority, "num", 1, 9, "Invalid priority.");

	if ($v->isError()) {
		return enter($v->genErrors());
	}

	$edate = "$edate_year-$edate_month-$edate_day";

	if (strtolower($page_option) == "edit") {
		$sql = "
			UPDATE project.projects 
			SET name='$name', champion_id='$champion_id', sponsor_id='$sponsor_id', leader_id='$leader_id', 
				edate='$edate', priority='$priority' 
			WHERE id = '$id'";
		db_exec($sql) or errDie("Unable to update project.");
	} else {
		$sql = "
			INSERT INTO project.projects (
				name, champion_id, sponsor_id, leader_id, edate, priority
			) VALUES (
				'$name', '$champion_id', '$sponsor_id', '$leader_id', '$edate', '$priority'
			)";
		db_exec($sql) or  errDie("Unable to add project.");
	}

	$OUTPUT = "
		<h3>$page_option Project</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Write</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><li>Successfully saved the project</li></td>
			</tr>
		</table>";
	return $OUTPUT;

}


?>