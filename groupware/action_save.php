<?php

require ("../settings.php");

db_conn("cubit");
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
	ql("action_save.php?id=$_REQUEST[id]", "Add Another Action"),
	ql("action_report.php", "Action Report"),
	ql("document_save.php", "Add Document"),
	ql("document_view.php", "View Documents")
);

require ("gw-tmpl.php");

function enter()
{
	extract ($_REQUEST);

	$fields = array();
	$fields["title"] = "";
	$fields["ad_year"] = date("Y");
	$fields["ad_month"] = date("m");
	$fields["ad_day"] = date("d");
	$fields["desc"] = "";

	extract ($fields, EXTR_SKIP);

	$sql = "SELECT * FROM cubit.documents WHERE id='$id'";
	$doc_rslt = db_exec($sql) or errDie("Unable to retrieve document.");
	$doc_data = pg_fetch_array($doc_rslt);

	$OUTPUT = "<h3>Add Action</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='confirm' />
	<input type='hidden' name='id' value='$id' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Details</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Document Title</td>
			<td>$doc_data[title]</td>
		<tr class='".bg_class()."'>
			<td>Action Title</td>
			<td><input type='text' name='title' value='$title' /></td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Action Date</td>
			<td>".mkDateSelect("ad", $ad_year, $ad_month, $ad_day)."</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Action Description</td>
			<td><textarea name='desc' rows='5' cols='20'>$desc</textarea></td>
		</tr>
		<tr>
			<td colspan='2' align='right'>
				<input type='submit' value='Confirm &raquo'>
			</td>
		</tr>
	</table>
	</form>";

	return $OUTPUT;
}

function confirm()
{
	extract ($_REQUEST);

	$sql = "SELECT * FROM cubit.documents WHERE id='$id'";
	$doc_rslt = db_exec($sql) or errDie("Unable to retrieve document.");
	$doc_data = pg_fetch_array($doc_rslt);

	$ad_date = "$ad_day-$ad_month-$ad_year";

	$OUTPUT = "<h3>Add Action</h3>
	<form method='post' action='".SELF."'>
	<input type='hidden' name='key' value='write' />
	<input type='hidden' name='id' value='$id' />
	<input type='hidden' name='title' value='$title' />
	<input type='hidden' name='ad_day' value='$ad_day' />
	<input type='hidden' name='ad_month' value='$ad_month' />
	<input type='hidden' name='ad_year' value='$ad_year' />
	<input type='hidden' name='desc' value='$desc' />
	<table ".TMPL_tblDflts.">
		<tr>
			<th colspan='2'>Confirm</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Document Title</td>
			<td>$doc_data[title]</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Action Title</td>
			<td>$title</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Action Date</td>
			<td>$ad_date</td>
		</tr>
		<tr class='".bg_class()."'>
			<td>Action Description</td>
			<td>$desc</td>
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

	$ad_date = "$ad_year-$ad_month-$ad_day";

	$sql = "INSERT INTO cubit.actions (doc_id, title, description, date)
	VALUES ('$id', '$title', '$desc', '$ad_date')";
	$act_rslt = db_exec($sql) or errDie("Unable to retrieve documents.");

	$OUTPUT = "<table ".TMPL_tblDflts.">
		<tr>
			<th>Write</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>Successfully saved the action</td>
		</tr>
	</table>";

	return $OUTPUT;
}
