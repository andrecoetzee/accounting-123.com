<?php

require ("../settings.php");

db_conn("cubit");
$OUTPUT = display();

$OUTPUT .= mkQuickLinks(
	ql("document_save.php", "Add Document"),
	ql("document_view.php", "View Documents")
);

require ("../template.php");

function display()
{
	extract ($_REQUEST);
	$fields["period"] = "";
	$fields["date_year"] = date("Y");
	$fields["date_month"] = date("m");
	$fields["date_day"] = date("d");

	extract ($fields, EXTR_SKIP);

	$date = "$date_year-$date_month-$date_day";

	if ($period == "day") {
		$age = "0 Days";
	} elseif ($period == "month") {
		$age = "0 Months";
	} elseif ($period == "week") {
		$age = "0 Weeks";
	} else {
		$age = "0 Days";
	}

	$sql = "SELECT * FROM cubit.actions WHERE
	age('$date', date) > '$age'::interval ";
	$action_rslt = db_exec($sql) or errDie("Unable to retrieve actions.");

	$actions_out = "";
	while ($action_data = pg_fetch_array($action_rslt)) {
		$sql = "SELECT * FROM cubit.documents WHERE id='$action_data[doc_id]'";
		$doc_rslt = db_exec($sql) or errDie("Unable to retrieve documents.");
		$doc_data = pg_fetch_array($doc_rslt);

		$actions_out .= "<tr class='".bg_class()."'>
			<td>$action_data[date]</td>
			<td>$doc_data[id]</td>
			<td>$doc_data[title]</td>
			<td>$doc_data[contract]</td>
			<td>$doc_data[contractor]</td>
			<td>$action_data[title]</td>
			<td>$action_data[description]</td>
		</tr>";
	}

	if (empty($actions_out)) {
		$actions_out = "<tr class='".bg_class()."'>
			<td colspan='20'>No results found</td>
		</tr>";
	}

	$period_ar = array(
		"day"=>"Daily",
		"week"=>"Weekly",
		"month"=>"Monthly"
	);

	$period_sel = "<select name='period' onchange='javascript:document.form.submit()'>";
	foreach ($period_ar as $key=>$value) {
		if ($period == $key) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$period_sel .= "<option value='$key' $sel>$value</option>";
	}
	$period_sel .= "</select>";

	$OUTPUT = "<center>
	<h3>Action Report</h3>
	<form method='post' action='".SELF."' name='form'>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Display</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>".mkDateSelect("date", $date_year, $date_month, $date_day)."</td>
		</tr>
		<tr class='".bg_class()."'>
			<td align='center'>$period_sel</td>
		</tr>
	</table>
	</form>
	<p></p>
	<table ".TMPL_tblDflts.">
		<tr>
			<th>Action Date</th>
			<th>Record Number</th>
			<th>Document Title</th>
			<th>Contract</th>
			<th>Contractor</th>
			<th>Action Title</th>
			<th>Action Description</th>
		</tr>
		$actions_out
	</table>
	<p></p>";

	return $OUTPUT;
}
