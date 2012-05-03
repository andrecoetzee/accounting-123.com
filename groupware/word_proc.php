<?php

require ("../settings.php");

if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "enter":
			$OUTPUT = enter();
			break;
		case "display":
			$OUTPUT = display();
			break;
	}
} else {
	$OUTPUT = enter();
}

require ("gw-tmpl.php");

function enter()
{
	extract ($_REQUEST);

	$fields["title"] = "";
	$fields["team_id"] = 0;
	$fields["showdoc_html"] = "''";
	$fields["id"] = 0;

	extract ($fields, EXTR_SKIP);

	if ($id) {
		$sql = "SELECT * FROM cubit.documents WHERE docid='$id'";
		$doc_rslt = db_exec($sql) or errDie("Unable to retrieve documents.");
		$doc_data = pg_fetch_array($doc_rslt);

		$sql = "SELECT * FROM cubit.document_files WHERE doc_id='$id'";
		$df_rslt = db_exec($sql) or errDie("Unable to retrieve document file.");
		$df_data = pg_fetch_array($df_rslt);

		$showdoc_html = "'".base64_decode($df_data["file"])."'";

		$title = $doc_data["title"];
		$team_id = $doc_data["team_id"];
	}


	// Teams dropdown
	$sql = "SELECT * FROM crm.teams";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");

	$team_sel = "<select name='team_id' style='width: 100%'>";
	$team_sel.= "<option value='0'>[None]</option>";
	while ($team_data = pg_fetch_array($team_rslt)) {
		if ($team_id == $team_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$team_sel.= "<option value='$team_data[id]' $sel>
					    $team_data[name]
					 </option>";
	}
	$team_sel.= "</select>";

	// the body
	$OUTPUT = "<center>
	<h3>Word Processor</h3>
	<form method='post' action='".SELF."' name='editForm' enctype='multipart/form-data'>
	<input type='hidden' name='key' value='display' />
	<input type='hidden' name='id' value='$id' />
	<table cellpadding='5' cellspacing='0' class='shtable'>
		<tr>
			<th>Document Title</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>
				<input type='text' name='title' value='$title' style='width: 100%' />
			</td>
		</tr>
		<tr>
			<th>Team Permissions</th>
		</tr>
		<tr class='".bg_class()."'>
			<td>$team_sel</td>
		</tr>
	</table>
	<table ".TMPL_tblDflts.">
		<tr>
			<td width=100% colspan=2>
				<script language='JavaScript'>

				function update() {
					document.editForm.bodydata.value = editArea.document.body.innerHTML;
					document.editForm.submit();
				}

				function Init() {
					editArea.document.designMode = 'On';
					editArea.document.body.innerHTML = $showdoc_html;
				}

				function controlSelOn(ctrl) {
					ctrl.style.borderColor = '#000000';
					ctrl.style.backgroundColor = '#B5BED6';
					ctrl.style.cursor = 'hand';
				}

				function controlSelOff(ctrl) {
					ctrl.style.borderColor = '#D6D3CE';
					ctrl.style.backgroundColor = '#D6D3CE';
				}

				function controlSelDown(ctrl) {
					ctrl.style.backgroundColor = '#8492B5';
				}

				function controlSelUp(ctrl) {
				ctrl.style.backgroundColor = '#B5BED6';
				}

				function doBold() {
					editArea.document.execCommand('bold', false, null);
				}

				function doItalic() {
					editArea.document.execCommand('italic', false, null);
				}

				function doUnderline() {
					editArea.document.execCommand('underline', false, null);
				}

				function doLeft() {
					editArea.document.execCommand('justifyleft', false, null);
				}

				function doCenter() {
					editArea.document.execCommand('justifycenter', false, null);
				}

				function doRight() {
					editArea.document.execCommand('justifyright', false, null);
				}

				function doOrdList() {
					editArea.document.execCommand('insertorderedlist', false, null);
				}

				function doBulList() {
					editArea.document.execCommand('insertunorderedlist', false, null);
				}

				function doRule() {
					editArea.document.execCommand('inserthorizontalrule', false, null);
				}

				function doSize(fSize) {
					if(fSize != '')
						editArea.document.execCommand('fontsize', false, fSize);
				}

				window.onload = Init;

				</script>

				<table id='tblCtrls' width='700px' height='30px' border='0' cellspacing='0' cellpadding='0' bgcolor='#D6D3CE'>
				<tr>
				<td class='tdClass'>
					<img alt='Bold' class='buttonClass' src='../images/bold.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doBold()'>

					<img alt='Italic' class='buttonClass' src='../images/italic.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doItalic()'>
					<img alt='Underline' class='buttonClass' src='../images/underline.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doUnderline()'>

					<img alt='Left' class='buttonClass' src='../images/left.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doLeft()'>
					<img alt='Center' class='buttonClass' src='../images/center.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doCenter()'>
					<img alt='Right' class='buttonClass' src='../images/right.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doRight()'>

					<img alt='Ordered List' class='buttonClass' src='../images/ordlist.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doOrdList()'>
					<img alt='Bulleted List' class='buttonClass' src='../images/bullist.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doBulList()'>

					<img alt='Horizontal Rule' class='buttonClass' src='../images/rule.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doRule()'>
				</td>
				<td class='tdClass' align=right>
					<select name='selSize' onChange='doSize(this.options[this.selectedIndex].value)'>
					<option value=''>-- Font Size --</option>
					<option value='1'>Very Small</option>
					<option value='2'>Small</option>
					<option value='3'>Medium</option>
					<option value='4'>Large</option>
					<option value='5'>Larger</option>
					<option value='6'>Very Large</option>
					</select>
				</td>
				</tr>
				</table>

				<iframe name='editArea' id='editArea' style='width: 700px; height:405px; background: #FFFFFF;'></iframe>
				<input type=hidden name=bodydata value=''>
			</td>
		</tr>
		<tr>
			<td width=100% colspan=2 align='center'>
				<input type=button onClick='update();' value='Save'>
			</td>
		</tr>
		</center>";

	return $OUTPUT;
}

function display()
{
	extract ($_REQUEST);

	$doc_output = "
	<div style='width: 95%; background: #fff; border: 1px solid #000'>
		$bodydata
	</div>";

	$size = sizeof($bodydata);
	$document = base64_encode($bodydata);

	if (!$id) {
		$sql = "INSERT INTO cubit.documents (title, filename, status, team_id, wordproc)
		VALUES ('$title', '$title.html', 'active', '$team_id', 1)";
		db_exec($sql) or errDie("Unable to save document.");

		db_conn("cubit");
		$docid = pglib_lastid("documents", "docid");
	} else {
		$sql = "UPDATE cubit.documents
				SET title='$title', filename='$title.html', team_id='$team_id'
				WHERE docid='$id'";
		$doc_rslt = db_exec($sql) or errDie("Unable to save document.");
		$docid = $id;
	}

	$sql = "INSERT INTO cubit.document_files (doc_id, filename, file, size)
	VALUES ('$docid', '$title.html', '$document', '$size')";
	db_exec($sql) or errDie("Unable to save document.");

	$OUTPUT = "
	Document Successfully Saved<br />
	$doc_output";

	return $OUTPUT;
}