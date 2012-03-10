<?php

require ("../settings.php");
require ("gantt.inc.php");

if (isset($_REQUEST["project_id"]) && is_numeric($_REQUEST["project_id"])) {
	if (isset($_REQUEST["key"])) {
		switch ($_REQUEST["key"]) {
			case "slct":
				$OUTPUT = slct();
				break;
			case "display":
				$OUTPUT = display();
				break;
		}
	} else {
		$OUTPUT = display();
	}
} else {
	$OUTPUT = slct();
}

$OUTPUT .= mkQuickLinks(
	ql("project_save.php", "Add Project"),
	ql("project_view.php", "View Projects"),
	ql("project_charter.php", "Project Charter")
);

require ("../template.php");

function slct()
{
	$sql = "SELECT * FROM project.projects";
	$project_rslt = db_exec($sql) or errDie("Unable to retrieve projects.");

	$project_sel = "<select name='project_id'>";
	while ($project_data = pg_fetch_array($project_rslt)) {
		$project_sel .= "<option value='$project_data[id]'>
			$project_data[name]
		</option>";
	}
	$project_sel .= "</select>";

	$OUTPUT = "<h3>Gantt Chart</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='display' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Select Project</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>$project_sel</td>
			<td><input type='submit' value='Display &raquo' /></td>
		</tr>
	</table>";

	return $OUTPUT;
}

function display()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["period"] = "monthly";
	$fields["start_day"] = "01";
	$fields["start_month"] = date("m");
	$fields["start_year"] = date("Y");
	$fields["end_day"] = date("d");
	$fields["end_month"] = date("m");
	$fields["end_year"] = date("Y");

	extract ($fields, EXTR_SKIP);

	$gantt = new Gantt($project_id);

	$period_fields = array(
		"daily"=>"Daily",
		"weekly"=>"Weekly",
		"monthly"=>"Monthly"
	);

	$period_sel = "<select name='period' onchange='javascript:document.form.submit()'>";
	foreach ($period_fields as $key=>$value) {
		if ($period == $key) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$period_sel .= "<option value='$key' $sel>$value</option>";
	}
	$period_sel .= "</select>";

	$start_epoch = mktime(0, 0, 0, $start_month, $start_day, $start_year);
	$end_epoch = mktime(0, 0, 0, $end_month, $end_day, $end_year);


	if ($period == "monthly") {
		$gantt_out = $gantt->generate_monthly($start_epoch, $end_epoch);
	} elseif ($period == "daily") {
		$gantt_out = $gantt->generate_daily($start_epoch, $end_epoch);
	} elseif ($period == "weekly") {
		$gantt_out = $gantt->generate_weekly($start_epoch, $end_epoch);
	}

	$OUTPUT = "<center>
	<h3>Gantt Chart</h3>
	<form method='post' action='".SELF."' name='form'>
	<input type='hidden' name='key' value='display' />
	<input type='hidden' name='project_id' value='$project_id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='4'>Date Range</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>".mkDateSelect("start", $start_year, $start_month, $start_day)."</td>
			<td><b>To</b></td>
			<td>".mkDateSelect("end", $end_year, $end_month, $end_day)."</td>
			<td><input type='submit' value='Select &raquo' /></td>
		<tr>
			<th colspan='4'>Display</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td colspan='4' align='center'>$period_sel</td>
		</tr>
	</table>
	<p></p>
	<table cellpadding='3' cellspacing='0'>
	$gantt_out
	</table>
	</center>";

	return $OUTPUT;

	return $OUTPUT;
}