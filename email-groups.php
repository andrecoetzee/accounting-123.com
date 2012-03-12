<?

require ("settings.php");
require_lib("mail.smtp");

if(isset($_POST["key"])){
	if ($_POST["key"] == "group"){
		$OUTPUT = get_data ($_POST);
	}elseif($_POST["key"] == "process") {
		$OUTPUT = process_data ($_POST);
	}elseif($_POST["key"] == "modify") {
		if(isset($_POST["done"])){
			$OUTPUT = get_email ($_POST);
		}else {
			$OUTPUT = process_data ($_POST);
		}
	}elseif($_POST["key"] == "send_mail") {
		$OUTPUT = send_emails ($_POST);
	}
}else {
	$OUTPUT = select_group ();
}

$OUTPUT .= "<p>".
	mkQuickLinks(
		ql("email-queue-manage.php","Send Emails In Queue"),
		ql("email-queue-failures.php","Resend Failed Emails In Queue"),
		ql("email-groups.php", "Send Email To Group"),
		ql("email-group-new.php", "Add Email Group"),
		ql("email-group-view.php", "View Email Groups")
	);
require ("template.php");



function select_group ()
{
	
	db_connect ();
	
	$groups = "";

	#get list of groups
	$get_groups = "SELECT * from egroups ORDER BY groupname";
	$run_egroups = db_exec($get_groups) or errDie("Unable to get group information.");
	if(pg_numrows($run_egroups) > 0){
		while ($garr = pg_fetch_array($run_egroups)){
			$groups .= "<option value='$garr[grouptitle]'>$garr[groupname]</option>";
		}
	}


	$display = "
		<h2>Select Group To Email</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='group'>
			<tr>
				<th>Group</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
					<select name='group'>
						<option value='customers'>Customers</option>
						<option value='leads'>Leads</option>
						<option value='contacts'>Contacts</option>
						$groups
					</select>
				</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' value='Next'></td>
			</tr>
		</form>
		</table>
		<p>";
	return $display;

}

function get_data ($_POST)
{
	
	extract ($_POST);
	
	$es = qryEmailSettings();

	#check if we have a list ....
	$get_email_list = "SELECT * FROM email_groups WHERE email_group = '$group' LIMIT 1";
	$run_email_list = db_exec($get_email_list) or errDie("Unable to get email group information.");
	if(pg_numrows($run_email_list) < 1){
		#no group ... only create new
		$options = "";
	}else {
		$options = "<option value='old'>Use Previous List</option>";
	}


	$display = "
		<h2>Select Email Group</h2>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='process'>
			<input type='hidden' name='group' value='$group'>
			<tr>
				<th>Select Email Group</th>
			</tr>
			<tr>
				<td>
					<select name='list'>
						$options
						<option value='new'>Create New List</option>
					</select>
				</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' value='Next'></td>
			</tr>
		</form>
		</table>";
	return $display;

}


function process_data ($_POST,$err="")
{
	
	extract ($_POST);
	
	if(isset($add) AND (strlen($add) > 0)){
		$list = "old";
	}
	
	if(!isset($list) OR (strlen($list) < 1))
		return "Invalid Use Of Module.";

	db_connect ();
		
	if($list == "new"){
		#remove any old list and generate new list
		$remove_old = "DELETE FROM email_groups WHERE email_group = '$group'";
		$run_remove = db_exec($remove_old) or errDie("Unable to remove old email list.");

		switch ($group){
			case "customers":
				#get all customer email addresses
				$get_custs = "SELECT * FROM customers WHERE length(email) > 0 AND div ='".USER_DIV."' ORDER BY surname";
				$run_custs = db_exec($get_custs) or errDie ("Unable to get customer group information.");
				if(pg_numrows($run_custs) > 0){
					while ($carr = pg_fetch_array($run_custs)){
		
						#add this cusomer to the database
						$insert_sql = "
							INSERT INTO email_groups (
								email_group, emailaddress, date_added
							) VALUES (
								'customers', '$carr[email]', 'now'
							)";
						$run_insert = db_exec($insert_sql) or errDie("Unable to add customer to email list.");
					}
				}
				break;
			case "leads":
				db_conn("crm");
				$get_cons = "SELECT * FROM leads WHERE length(email) > 0";
				$run_cons = db_exec($get_cons) or errDie("Unable to get contact email addresses.");
				if(pg_numrows($run_cons) > 0){
					while ($carr = pg_fetch_array($run_cons)){
						$insert_sql = "
							INSERT INTO email_groups (
								email_group, emailaddress, date_added
							) VALUES (
								'leads', '$carr[email]', 'now'
							)";
						$run_insert = db_exec($insert_sql) or errDie("Unable to add customer to email list.");
					}
				}
				break;
			case "contacts":
				$get_cons = "SELECT * FROM cons WHERE length(email) > 0";
				$run_cons = db_exec($get_cons) or errDie("Unable to get contact email addresses.");
				if(pg_numrows($run_cons) > 0){
					while ($carr = pg_fetch_array($run_cons)){
						$insert_sql = "
							INSERT INTO email_groups (
								email_group, emailaddress, date_added
							) VALUES (
								'contacts', '$carr[email]', 'now'
							)";
						$run_insert = db_exec($insert_sql) or errDie("Unable to add customer to email list.");
					}
				}
				break;
			default:
//				return select_group ();
		}
	}else {
		#do nothing ... just use old list ...
	}

	db_connect ();

	#remove any entry if its been set
	if(isset($remove) AND is_array($remove)){
		foreach ($remove as $each){
			$remove_sql = "DELETE FROM email_groups WHERE id = '$each'";
			$run_remove = db_exec($remove_sql) or errDie("Unable to get remove selected email address.");
		}
	}
	
	if(isset($add) AND (strlen($add) > 0)){
		
		# validate input
		require_lib("validate");
		$v = new validate ();
		$v->isOk ($add, "email", 0, 50, "Invalid Email Address.");

		# Display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class='err'>$e[msg]</li>";
			}
			#remove nasty entry and send error
			unset ($_POST["add"]);
			return process_data($_POST,$confirm);
		}

		$add_sql = "
			INSERT INTO email_groups (
				email_group, emailaddress, date_added
			) VALUES (
				'$group', '$add', 'now'
			)";
		$run_add = db_exec($add_sql) or errDie("Unable to add new email address.");
	}
	
	if(isset($search)){
		$searchsql = "AND lower(emailaddress) LIKE lower('%$search%')";
	}else {
		$searchsql = "";
	}

	if(!isset($offset))
		$offset = 0;


	if(isset($gonext) AND strlen($gonext) > 0)
		$offset = $offset + 15;

	if(isset($goprev) AND strlen($goprev) > 0)
		$offset = $offset - 15;

	if ($offset < 0)
		$offset = 0;

	#get a total
	$get_tot = "SELECT count(id) FROM email_groups WHERE email_group = '$group' $searchsql";
	$run_tot = db_exec($get_tot) or errDie ("Unable to get total email information.");
	if (pg_numrows($run_tot) < 1) 
		$tot = 0;
	else 
		$tot = pg_fetch_result ($run_tot,0,0);


	#get list to use from the database ...
	$get_list = "SELECT * FROM email_groups WHERE email_group = '$group' $searchsql ORDER BY emailaddress OFFSET $offset LIMIT 25";
	$run_list = db_exec($get_list) or errDie("Unable to get customer email list.");
	if(pg_numrows($run_list) < 1){
		$listing = "";
		$count = 0;
	}else {
		$listing = "";
		$count = 0;
		while ($larr = pg_fetch_array($run_list)){
			$listing .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$larr[emailaddress]</td>
					<td><input id='$larr[id]' type='checkbox' name='remove[]' value='$larr[id]'></td>
				</tr>";
			$count++;
		}
	}

	#handle previous ...
	if($offset != 0) 
		$prev = TRUE;
	else 
		$prev = FALSE;

	if($count == 15)
		$next = TRUE;
	else 
		$next = FALSE;

	$buttons = "";
	if($prev OR $next){
		$show_prev = "";
		$show_next = "";
		if($prev)
			$show_prev = "<input type='submit' name='goprev' value='Previous'>";
		if($next)
			$show_next = "<input type='submit' name='gonext' value='Next'>";

		$buttons .= "
			<tr>
				<td colspan='2'>$show_prev $show_next</td>
			</tr>";
	}

	$display = "
		<script>
			function checkAll(field) {
				var_list = document.form1.getElementsByName ('remove[]');
				for (var a = 0; a<var_list.length;a++){
					alert (var_list[a]);
				}
				for (i = 0; i < field.length; i++){
					alert(field.length);
					field[i].checked = true ;
				}
			}

			function uncheckAll(field) {
				for (i = 0; i < field.length; i++)
					field[i].checked = false ;
			}
		</script>
		<h2>Email Listing</h2>
		$err
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='modify'>
			<input type='hidden' name='group' value='$group'>
			<input type='hidden' name='offset' value='$offset'>
			<input type='hidden' name='list' value='old'>
			<input type='hidden' name='' value=''>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Search For Email Address</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='text' size='35' name='search'> <input type='submit' value='Search'></td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Add Email Address To List</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='text' size='35' name='add'> <input type='submit' value='Add'></td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Sending Format</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
					<input type='radio' name='send_format' value='html' checked='yes'>HTML
					<input type='radio' name='send_format' value='plain'>Plain Text
				</td>
			</tr>
			".TBL_BR."
			".TBL_BR."
		</table>
		<table ".TMPL_tblDflts.">
			<tr>
				<td colspan='2'><h3>Send Email To These $tot Email Addresses</h3></td>
			</tr>
			$buttons
			<tr>
				<th>Email Address</th>
				<th>Remove</th>
			</tr>
			$listing
			<tr>
				<td><input type='button' onClick='javascript:checkAll(document.form1.remove);' value='Check All'></td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Remove Selected'></td>
			</tr>
			".TBL_BR."
			<tr>
				<td colspan='2' align='right'><input type='submit' name='done' value='Send Email'></td>
			</tr>
		</form>
		</table>";
	return $display;
	
}





function get_email ($_POST)
{
	
	extract ($_POST);
	$showdoc_html = "''";
	
	$groupname = date("Y")."-".date("m")."-".date("d")."_".date("H").":".date("i").":".date("s");

	if ($send_format == "plain"){
		$show_ide = "
			<script language='JavaScript'>
				function update() {
					document.editForm.submit();
				}
			</script>
			<textarea name='bodydata' cols='80' rows='24'></textarea>";
	}else {
		$show_ide = "
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
						<img alt='Bold' class='buttonClass' src='images/bold.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doBold()'>
		
						<img alt='Italic' class='buttonClass' src='images/italic.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doItalic()'>
						<img alt='Underline' class='buttonClass' src='images/underline.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doUnderline()'>
		
						<img alt='Left' class='buttonClass' src='images/left.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doLeft()'>
						<img alt='Center' class='buttonClass' src='images/center.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doCenter()'>
						<img alt='Right' class='buttonClass' src='images/right.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doRight()'>
		
						<img alt='Ordered List' class='buttonClass' src='images/ordlist.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doOrdList()'>
						<img alt='Bulleted List' class='buttonClass' src='images/bullist.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doBulList()'>
		
						<img alt='Horizontal Rule' class='buttonClass' src='images/rule.gif' onMouseOver='controlSelOn(this)' onMouseOut='controlSelOff(this)' onMouseDown='controlSelDown(this)' onMouseUp='controlSelUp(this)' onClick='doRule()'>
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
			<input type='hidden' name='bodydata' value=''>";
	}


	$display = "
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='editForm' enctype='multipart/form-data'>
			<input type='hidden' name='key' value='send_mail'>
			<input type='hidden' name='group' value='$group'>
			<input type='hidden' name='list' value='$list'>
			<input type='hidden' name='' value=''>
			<input type='hidden' name='groupname' value='$groupname'>
			<input type='hidden' name='send_format' value='$send_format'>
			<tr>
				<th>Email Batch Name</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>$groupname</td>
			</tr>
			".TBL_BR."
			<tr bgcolor='".bgcolorg()."'>
				<th>Subject</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='text' size='55' name='subject'></td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Attachment</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><input type='file' name='attachment'></td>
			</tr>
			".TBL_BR."
			<tr>
				<th>Email Content</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td width='100%' colspan='2'>
					$show_ide
				</td>
			</tr>
			<tr>
				<td width='100%' colspan='2'>
					<input type='button' onClick='update();' value='Save Emails To Queue'> &nbsp; &nbsp;
				</td>
			</tr>
		</form>
		</table>";
	return $display;

}


function send_emails ($_POST)
{

	extract ($_POST);

	if (isset($send_format) AND $send_format == "plain")
		$bodydata = strip_tags($bodydata);

	if (is_uploaded_file($_FILES["attachment"]["tmp_name"])){

		$fdata = "";
		$file = fopen ($_FILES['attachment']['tmp_name'], "rb");
		while (!feof ($file)) {
			$fdata .= fread ($file, 1024);
		}
		fclose ($file);
		# base 64 encoding
		$fdata = base64_encode($fdata);

		$type = $_FILES["attachment"]["type"];
		$filename = $_FILES["attachment"]["name"];

		#attachment uploaded ...
		$ins_sql = "
			INSERT INTO email_attachments (
				attach_data, attach_mime, attach_filename
			) VALUES (
				'$fdata', '$type', '$filename'
			)";
		$run_ins = db_exec($ins_sql) or errDie ("Unable to record email attachment information.");

		$att_id = pglib_lastid ("email_attachments","id");
	}else {
		$att_id = 0;
	}

	#get to email
	$get_list = "SELECT * from email_groups WHERE email_group = '$group'";
	$run_list = db_exec($get_list) or errDie("Unable to get email list.");
	if(pg_numrows($run_list) < 1){
		return "Recipient List Is Empty.";
	}else {
		while ($earr = pg_fetch_array($run_list)){
			#store this email in queue ... 
			$store_sql = "
				INSERT INTO email_queue (
					emailaddress, subject, message, status, date_added, date_sent, groupname, attachment, 
					send_format
				) VALUES (
					'$earr[emailaddress]', '$subject', '$bodydata', 'sending', 'now', 'now', '$groupname', '$att_id', 
					'$send_format'
				)";
			$run_store = db_exec($store_sql) or errDie ("Unable to store email information.");
		}
		return "<li class='err'>Email(s) Have Been Placed In Email Queue.</li>";
	}

}


?>