<?

#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#

# get settings
require ("settings.php");
require("libs/ext.lib.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		case "write":
			$OUTPUT = write ($_POST);
			break;
		case "doc_save":
			$OUTPUT = doc_save();
			break;
		default:
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# display output
require ("template.php");




# enter new data
function enter($errors="")
{

	global $_POST;
	extract ($_POST);

	$fields = array();
	$fields["re"] = "no";
	$fields["deptid"] = "";
	$fields["supno"] = "";
	$fields["supname"] = "";
	$fields["supbranch"] = "";
	$fields["loc"] = "";
	$fields["fcid"] = "";
	$fields["vatnum"] = "";
	$fields["supaddr"] = "";
	$fields["suppostaddr"] = "";
	$fields["contname"] = "";
	$fields["tel"] = "";
	$fields["fax"] = "";
	$fields["cell"] = "";
	$fields["email"] = "";
	$fields["url"] = "";
	$fields["listid"] = "";
	$fields["bee_status"] = "";
	$fields["comments"] = "";
	$fields["bankname"] = "";
	$fields["branname"] = "";
	$fields["brancode"] = "";
	$fields["bankaccno"] = "";
	$fields["bankacctype"] = "";
	$fields["bankaccname"] = "";
	$fields["reference"] = "";
	$fields["lead_source"] = "";
	$fields["team_id"] = 0;
	$fields["registration"] = "";
	$fields["supp_grp"] = "";
	$fields["setdisc"] = "0";
	$fields["setdays"] = "1";

	extract ($fields, EXTR_SKIP);

	# Select previous year database
	db_connect();
	$lastid = pglib_lastid("suppliers","supid");
	
	# get last account number
	$sql = "SELECT supno FROM suppliers WHERE supid = '$lastid' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	if (pg_numrows($accRslt) < 1) {
		do {
			$lastid--;
			# get last account number
			$sql = "SELECT supno FROM suppliers WHERE supid = '$lastid' AND div = '".USER_DIV."'";
			$accRslt = db_exec($sql);
			if(pg_numrows($accRslt) < 1){
				$supno = "";
				$nsupno = "";
			}else{
				$acc = pg_fetch_array($accRslt);
				$supno = $acc['supno'];
			}
		} while(strlen($supno) < 1 && $lastid > 1);
	}else{
		$acc = pg_fetch_array($accRslt);
		$supno = $acc['supno'];
	}

	# Check if we got $supno(if not skip this)
	if (strlen($supno) > 0) {
		# Get the next account number
		$num = preg_replace ("/[^\d]+/", "", $supno);
		$num++;
		$chars = preg_replace("/[\d]/", "", $supno);
		$nsupno = $chars.$num;
	} else {
		$nsupno = 1;
	}
	
	# Departments
	db_conn("exten");

	$depts = "<select name='deptid'>";
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return "<li>There are no Price lists in Cubit.</li>";
	}else{
		while($dept = pg_fetch_array($deptRslt)){
			if ($dept["deptid"] == $deptid) {
				$sel = "selected";
			} else {
				$sel = "";
			}
			$depts .= "<option value='$dept[deptid]' $sel>$dept[deptname]</option>";
		}
	}
	$depts .= "</select>";

	# Get pricelist
	$pricelists = "<select name='listid' style='width: 120'>";
	$sql = "SELECT * FROM spricelist WHERE div = '".USER_DIV."' ORDER BY listname ASC";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		return "<li>There are no Price lists in Cubit.</li>";
	}else{
		while($list = pg_fetch_array($listRslt)){
			if ($list["listid"] == $listid) {
				$sel = "selected";
			} else {
				$sel = "";
			}

			$pricelists .= "<option value='$list[listid]' $sel>$list[listname]</option>";
		}
	}
	$pricelists .= "</select>";

	db_connect();

	# Locations drop down
	$locs = array("loc" => "Local", "int" => "International");
	$locsel = extlib_cpsel("loc", $locs, $loc);

	# Currency drop down
	$currsel = ext_unddbsel("fcid", "currency", "fcid", "descrip", "There are is no currency found in Cubit, please add currency first.", $fcid);

	global $_GET;
	extract($_GET);
	if(isset($crm)) {
		$ex = "<input type='hidden' name='crm' value=''>";
	} else {
		$ex = "";
	}

	if(!isset($re)) {
		$re = "no";
	}

	$select_source = extlib_cpsel("lead_source", crm_get_leadsrc(-1), $lead_source);

	if(isset($bee_status) AND ($bee_status == "no")){
		$sel1 = "";
		$sel2 = "checked=yes";
	}else {
		$sel1 = "checked=yes";
		$sel2 = "";
	}


	// Retrieve teams
	$sql = "SELECT * FROM crm.teams ORDER BY name ASC";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");

	$team_sel = "<select name='team_id'>";
	$team_sel.= "<option value='0'>[None]</option>";
	while ($team_data = pg_fetch_array($team_rslt)) {
		if ($team_id == $team_data["id"]) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$team_sel .= "<option value='$team_data[id]' $sel>$team_data[name]</option>";
	}
	$team_sel .= "</select>";

	//Get supplier groups
	$get_grps = "SELECT * FROM supp_groups ORDER BY groupname";
	$run_grps = db_exec($get_grps) or errDie("Unable to get supplier group information.");
	if(pg_numrows($run_grps) < 1){
		return "Unable to get supplier group information. Please add a supplier group.";
	}else {
		$supp_grpdrop = "<select name='supp_grp'>";
		while ($garr = pg_fetch_array($run_grps)){
			if($garr['id'] == $supp_grp){
				$supp_grpdrop .= "<option value='$garr[id]' selected>$garr[groupname]</option>";
			}else {
				$supp_grpdrop .= "<option value='$garr[id]'>$garr[groupname]</option>";
			}
		}
		$supp_grpdrop .= "</select>";
	}

	$setdayssel1 = "";
	$setdayssel2 = "";
	$setdayssel3 = "";
	$setdayssel4 = "";
	$setdayssel5 = "";
	if(isset($setdays) AND $setdays == "0")
		$setdayssel1 = "selected";
	if(isset($setdays) AND $setdays == "1")
		$setdayssel2 = "selected";
	if(isset($setdays) AND $setdays == "7")
		$setdayssel3 = "selected";
	if(isset($setdays) AND $setdays == "15")
		$setdayssel4 = "selected";
	if(isset($setdays) AND $setdays == "25")
		$setdayssel5 = "selected";
	if(isset($setdays) AND $setdays == "60")
		$setdayssel6 = "selected";

	$setdays_drop = "
		<select name='setdays'>
			<option $setdayssel1 value='0'>Last Day Of The Month</option>
			<option $setdayssel2 value='1'>1st Day Of The Month</option>
			<option $setdayssel3 value='7'>7th Of The Month</option>
			<option $setdayssel4 value='15'>15th Of The Month</option>
			<option $setdayssel5 value='25'>25th Of The Month</option>
			<option $setdayssel6 value='60'>End Of Next Month</option>
		</select>";

	// Layout
	$enter = "
		<h3>Add Supplier</h3>
		<form action='".SELF."' method='POST'>
		<table cellpadding='0' cellspacing='0'>
			<tr>
				<td colspan='2'>$errors</td>
			</tr>
			<tr valign='top'>
				<td>
					<table ".TMPL_tblDflts.">
						<input type='hidden' name='key' value='confirm'>
						<input type='hidden' name='re' value='$re'>
						$ex
						<tr>
							<th colspan='2'>Supplier Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Department</td>
							<td>$depts</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Supplier No</td>
							<td><input type='text' size='10' name='supno' value='$nsupno' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Supplier/Name</td>
							<td><input type='text' size='20' name='supname' value='$supname' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Registration/ID</td>
							<td><input type='text' size='20' name='registration' value='$registration'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch</td>
							<td><input type='text' size='20' name='supbranch' value='$supbranch' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Type</td>
							<td>$locsel</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Currency</td>
							<td>$currsel</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."VAT Number</td>
							<td><input type='text' size='21' name='vatnum' value='$vatnum' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Address</td>
							<td><textarea name='supaddr' rows='4' cols='25'>$supaddr</textarea></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Postal Address</td>
							<td><textarea name='suppostaddr' rows='4' cols='25'>$suppostaddr</textarea></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Contact Name</td>
							<td><input type='text' size='20' name='contname' value='$contname' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Tel No.</td>
							<td><input type='text' size='20' name='tel' value='$tel' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Fax No.</td>
							<td><input type='text' size='20' name='fax' value='$fax' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Cell No.</td>
							<td><input type='text' size='20' name='cell' value='$cell' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>E-mail</td>
							<td><input type='text' size='20' name='email' value='$email' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Web Address</td>
							<td>http://<input type='text' size='30' name='url' value='$url' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Price List</td>
							<td>$pricelists</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier Group</td>
							<td>$supp_grpdrop</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Status BEE</td>
							<td>
								Yes <input type='radio' name='bee_status' value='yes' $sel1>
								No <input type='radio' name='bee_status' value='no' $sel2>
							</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Team Permissions</td>
							<td>$team_sel</td>
						</tr>
					</table>
				</td>
				<td>
					<table ".TMPL_tblDflts.">
						<tr class='".bg_class()."'>
							<th colspan='2'>Bank Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank</td>
							<td><input type='text' size='20' name='bankname' value='$bankname' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch</td>
							<td><input type='text' size='20' name='branname' value='$branname' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch Code</td>
							<td><input type='text' size='20' name='brancode' value='$brancode' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account Name</td>
							<td><input type='text' size='20' name='bankaccname' value='$bankaccname' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account Type</td>
							<td><input type='text' size='20' name='bankacctype' value='$bankacctype' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account Number</td>
							<td><input type='text' size='20' name='bankaccno' value='$bankaccno' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Reference</td>
							<td><input type='text' size='20' name='reference' value='$reference' /></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Lead Source</td>
							<td>$select_source</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ." Settlement Discount %</td>
							<td><input type='text' name='setdisc' value='$setdisc'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ." Statement Day</td>
							<td>$setdays_drop</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Comments</td>
							<td><textarea name='comments' rows='5' cols='18'>$comments</textarea></td>
						</tr>
						".TBL_BR."
						<tr>
							<td colspan='2' align='right'>
								<input type='submit' value='Confirm &raquo;'>
							</td>
						</tr>
						".TBL_BR."
					</form>
						<tr><td colspan='2'>
						<tr><td colspan='2' align='right'>
					<table border=0 cellpadding='2' cellspacing='1'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='supp-view.php'>View Suppliers</a></td>
						</tr>
						<tr class='".bg_class()."'>
							<td><a href='main.php'>Main Menu</a></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</td></tr>
		</table>";
	return $enter;

}




function doc_save()
{

	extract ($_REQUEST);

	$session = $_REQUEST["CUBIT_SESSION"];

	if ($_FILES["doc_file"]["tmp_name"]) {
		$tmp_name = $_FILES["doc_file"]["tmp_name"];
		$file_name = $_FILES["doc_file"]["name"];
		$file_type = $_FILES["doc_file"]["type"];

		$tmp_file = fopen($tmp_name, "rb");
		if (is_resource($tmp_file)) {
			$file = "";
			while (!feof($tmp_file)) {
				$file .= fread($tmp_file, 1024);
			}
			fclose($tmp_file);
			$file_size = strlen($file);
			$file = base64_encode($file);

			$sql = "
				INSERT INTO crm.stmp_docs (
					filename, type, size, file, session, real_filename
				) VALUES (
					'$doc_filename', '$file_type', '$file_size', '$file', '$session', '$file_name'
				)";
			db_exec($sql) or errDie("Unable to update customer documents.");
		}
	}

	if (isset($rem)) {
		foreach ($rem as $id=>$value) {
			$sql = "DELETE FROM crm.stmp_docs WHERE id='$id'";
			db_exec($sql) or errDie("Unable to remove entry.");
		}
	}
	return confirm();

}



# confirm new data
function confirm ()
{

	global $_POST;
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 0, 255, "Invalid Department.");
	$v->isOk ($supno, "string", 1, 255, "Invalid supplier number.");
	$v->isOk ($supname, "string", 1, 255, "Invalid supplier name.");
	$v->isOk ($loc, "string", 1, 3, "Invalid Type.");
	$v->isOk ($fcid, "num", 1, 30, "Invalid Currency.");
	$v->isOk ($vatnum, "string", 1, 255, "Invalid supplier vat number.");
	$v->isOk ($registration, "string", 1, 255, "Invalid registration number.");
	$v->isOk ($supaddr, "string", 1, 255, "Invalid supplier address.");
	$v->isOk ($suppostaddr, "string", 0, 255, "Invalid supplier postal address.");
	$v->isOk ($contname, "string", 1, 255, "Invalid contact name.");
	$v->isOk ($tel, "string", 1, 20, "Invalid tel no.");
	$v->isOk ($fax, "string", 0, 20, "Invalid fax no.");
	$v->isOk ($cell, "string", 0, 20, "Invalid cell no.");
	$v->isOk ($email, "email", 0, 255, "Invalid e-mail address.");
	$v->isOk ($url, "url", 0, 255, "Invalid web address.");
	$v->isOk ($listid, "num", 1, 20, "Invalid price list.");
	$v->isOk ($bankname, "string", 0, 255, "Invalid bank name.");
	$v->isOk ($branname, "string", 0, 255, "Invalid branch name.");
	$v->isOk ($brancode, "string", 0, 255, "Invalid branch code.");
	$v->isOk ($bankaccname, "string", 0, 255, "Invalid bank account name.");
	$v->isOk ($bankacctype, "string", 0, 255, "Invalid bank account type.");
	$v->isOk ($bankaccno, "num", 0, 255, "Invalid bank account number.");
	$v->isOk ($lead_source, "num", 0, 9, "Invalid lead source selected.");
	$v->isOk ($comments, "string", 0, 255, "Invalid characters in comment.");
	$v->isOk ($supbranch, "string", 0, 255, "Invalid supplier branch.");
	$v->isOk ($reference, "string", 0, 255, "Invalid reference.");
	$v->isOk ($bee_status, "string", 0, 255, "Invalid BEE Status");
	$v->isOk ($team_id, "num", 1, 9, "Invalid team selection.");
	$v->isOk ($supp_grp, "num", 1, 9, "Invalid supplier group selected.");
	$v->isOk ($setdisc, "float", 1, 40, "Invalid Settlement Discount Amount.");
	$v->isOk ($setdays, "num", 1, 40, "Invalid Settlement Discount Days");


	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return enter($confirm);
	}





	db_conn('cubit');
	$Sl = "SELECT * FROM suppliers WHERE supno='$supno'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		return enter("<li class='err'>Supplier number already exists</li>");
	}

	# Check if add contact was checked
	if ( isset($addcontact) && $addcontact == "on" ) {
		$addcontact_checked = "Yes";
	} else {
		$addcontact = "";
		$addcontact_checked = "No";
	}

	# get department
	db_conn("exten");

	$sql = "SELECT * FROM departments WHERE deptid = '$deptid' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$deptname = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
		$deptname = $dept['deptname'];
	}

	# Get Price List
	$sql = "SELECT * FROM spricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		$plist = "<li class='err'>Class not Found.</li>";
	}else{
		$list = pg_fetch_array($listRslt);
		$plist = $list['listname'];
	}

	# Locations drop down
	$locs = array("loc" => "Local", "int" => "International");
	$curr = getSymbol($fcid);

	if(isset($crm)) {
		$ex = "<input type='hidden' name='crm' value=''>";
	} else {
		$ex = "";
	}

	$get_suppgrp = "SELECT groupname FROM supp_groups WHERE id = '$supp_grp' LIMIT 1";
	$run_suppgrp = db_exec($get_suppgrp) or errDie("Unable to get supplier group information");
	if(pg_numrows($run_suppgrp) < 1){
		$showsupp_grp = "Unknown Supplier Group";
	}else{
		$garr = pg_fetch_array ($run_suppgrp);
		$showsupp_grp = $garr['groupname'];
	}
	
	// Retrieve team name
	$sql = "SELECT * FROM crm.teams WHERE id='$team_id'";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");
	$team_data = pg_fetch_array($team_rslt);

	$hidden = "
		<input type='hidden' name='deptid' value='$deptid'>
		<input type='hidden' name='supno' value='$supno'>
		<input type='hidden' name='supname' value='$supname'>
		<input type='hidden' name='loc' value='$loc'>
		<input type='hidden' name='fcid' value='$fcid'>
		<input type='hidden' name='vatnum' value='$vatnum'>
		<input type='hidden' name='registration' value='$registration'>
		<input type='hidden' name='supaddr' value='$supaddr'>
		<input type='hidden' name='suppostaddr' value='$suppostaddr'>
		<input type='hidden' name='contname' value='$contname'>
		<input type='hidden' name='tel' value='$tel'>
		<input type='hidden' name='fax' value='$fax'>
		<input type='hidden' name='cell' value='$cell'>
		<input type='hidden' name='email' value='$email'>
		<input type='hidden' name='url' value='$url'>
		<input type='hidden' name='listid' value='$listid'>
		<input type='hidden' name='bankname' value='$bankname'>
		<input type='hidden' name='branname' value='$branname'>
		<input type='hidden' name='brancode' value='$brancode'>
		<input type='hidden' name='bankaccno' value='$bankaccno'>
		<input type='hidden' name='bankaccname' value='$bankaccname'>
		<input type='hidden' name='bankacctype' value='$bankacctype'>
		<input type='hidden' name='lead_source' value='$lead_source'>
		<input type='hidden' name='comments' value='$comments'>
		<input type='hidden' name='supbranch' value='$supbranch'>
		<input type='hidden' name='reference' value='$reference'>
		<input type='hidden' name='re' value='$re'>
		<input type='hidden' name='bee_status' value='$bee_status'>
		<input type='hidden' name='team_id' value='$team_id' />
		<input type='hidden' name='supp_grp' value='$supp_grp' />
		<input type='hidden' name='setdisc' value='$setdisc' />
		<input type='hidden' name='setdays' value='$setdays' />";



	$confirm = "
		<h3>Confirm Supplier</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='write'>
			$hidden
			$ex
			<tr valign='top'>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Supplier Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Department</td>
							<td>$deptname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier No</td>
							<td>$supno</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier/Name </td>
							<td>$supname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Registration/ID </td>
							<td>$registration</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch</td>
							<td>$supbranch</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Type</td>
							<td>$locs[$loc]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Currency</td>
							<td>$curr[symbol] - $curr[name]</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>VAT Number</td>
							<td>$vatnum</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Address</td>
							<td><pre>$supaddr</pre></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Postal Address</td>
							<td><pre>$suppostaddr</pre></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Contact Name</td>
							<td>$contname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Tel No.</td>
							<td>$tel</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Fax No.</td>
							<td>$fax</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Cell No.</td>
							<td>$cell</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>E-mail</td>
							<td>$email</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Web Address</td>
							<td>http://$url</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Price List</td>
							<td>$plist</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Supplier Group</td>
							<td>$showsupp_grp</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Status BEE</td>
							<td>$bee_status</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Team Permissions</td>
							<td>$team_data[name]</td>
						</tr>
						<tr>
							<td><input type='submit' name='back' value='&laquo; Correction'></td>
						</tr>
					</table>
				</td>
				<td>
					<table ".TMPL_tblDflts.">
						<tr class='".bg_class()."'>
							<th colspan='2'> Bank Details</th>
						</tr>
						<tr class='".bg_class()."'>
							<td>Bank </td>
							<td>$bankname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch</td>
							<td>$branname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branch Code</td>
							<td>$brancode</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account Name</td>
							<td>$bankaccname</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account Type</td>
							<td>$bankacctype</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account Number</td>
							<td>$bankaccno</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Reference</td>
							<td>$reference</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Lead Source</td>
							<td>".crm_get_leadsrc($lead_source)."</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Settlement Discount %</td>
							<td>$setdisc %</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Settlement Discount Days</td>
							<td>$setdays</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Comments</td>
							<td>".nl2br($comments)."</td>
						</tr>
						".TBL_BR."
						<tr>
							<td colspan='2' align='right'><input type='submit' value='Write &raquo;'></td>
						</tr>
						".TBL_BR."
						<tr>
							<td colspan='2'>";

		// Retrieve documents added already
		$sql = "SELECT * FROM crm.stmp_docs WHERE session='$_REQUEST[CUBIT_SESSION]' ORDER BY id DESC";
		$sdoc_rslt = db_exec($sql) or errDie("Unable to retrieve docs.");

		$sdoc_out = "";
		while ($sdoc_data = pg_fetch_array($sdoc_rslt)) {

			if (strlen($sdoc_data['filename']) > 0){
				$showdoc = "$sdoc_data[filename]";
			}elseif (strlen($sdoc_data['real_filename']) > 0){
				$showdoc = "$sdoc_data[real_filename]";
			}else {
				$showdoc = "File".$sdoc_data["id"];
			}

			$sdoc_out .= "
				<tr class='".bg_class()."'>
					<td><a href='supp_doc_get.php?id=$sdoc_data[id]&tmp=1'>$showdoc</a></td>
					<td>".getFileSize($sdoc_data["size"])."</td>
					<td><input type='checkbox' name='rem[$sdoc_data[id]]' value='$sdoc_data[id]' /></td>
				</tr>";
		}

		if (empty($sdoc_out)) {
			$sdoc_out .= "
				<tr class='".bg_class()."'>
					<td colspan='3'><li>No documents added</li></td>
				</tr>";
		}

		$confirm .= "
					</form>
					<form method='post' action='".SELF."' enctype='multipart/form-data'>
						<input type='hidden' name='key' value='doc_save' />
						$hidden
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='4'>Documents</th>
						</tr>
						<tr>
							<th>Filename</th>
							<th>Upload</th>
							<th>&nbsp;</th>
						</tr>
						<tr class='".bg_class()."'>
							<td><input type='text' name='doc_filename'></td>
							<td><input type='file' name='doc_file'></td>
							<td colspan='2' align='center'>
								<input type='submit' value='Update'>
							</td>
						</tr>
						<tr>
							<th>Filename</th>
							<th>Size</th>
							<th>Remove</th>
						</tr>
						$sdoc_out
					</table>
				</td>
			</tr>";

		$confirm .= "
							<tr>
								<td colspan='2' align='right'>
									<table border='0' cellpadding='2' cellspacing='1'>
										<tr>
											<th>Quick Links</th>
										</tr>
										<tr class='".bg_class()."'>
											<td><a href='supp-view.php'>View Suppliers</a></td>
										</tr>
										<tr class='".bg_class()."'>
											<td><a href='main.php'>Main Menu</a></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>";
	return $confirm;

}



# write new data
function write ($_POST)
{

	# Get vars
	global $_POST;
	extract($_POST);

	if(isset($back)) {
		return enter();
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 1, 255, "Invalid Department.");
	$v->isOk ($supno, "string", 1, 255, "Invalid supplier number.");
	$v->isOk ($supname, "string", 1, 255, "Invalid supplier name.");
	$v->isOk ($loc, "string", 1, 3, "Invalid Type.");
	$v->isOk ($fcid, "num", 1, 30, "Invalid Currency.");
	$v->isOk ($vatnum, "string", 1, 255, "Invalid supplier vat number.");
	$v->isOk ($registration, "string", 1, 255, "Invalid registration/id number.");
	$v->isOk ($supaddr, "string", 1, 255, "Invalid supplier address.");
	$v->isOk ($suppostaddr, "string", 0, 255, "Invalid supplier postal address.");
	$v->isOk ($contname, "string", 1, 255, "Invalid contact name.");
	$v->isOk ($tel, "string", 1, 20, "Invalid tel no.");
	$v->isOk ($fax, "string", 0, 20, "Invalid fax no.");
	$v->isOk ($cell, "string", 0, 20, "Invalid cell no.");
	$v->isOk ($email, "email", 0, 255, "Invalid e-mail address.");
	$v->isOk ($url, "url", 0, 255, "Invalid web address.");
	$v->isOk ($listid, "num", 1, 20, "Invalid price list.");
	$v->isOk ($bankname, "string", 0, 255, "Invalid bank name.");
	$v->isOk ($branname, "string", 0, 255, "Invalid branch name.");
	$v->isOk ($brancode, "string", 0, 255, "Invalid branch code.");
	$v->isOk ($bankaccname, "string", 0, 255, "Invalid bank account name.");
	$v->isOk ($bankacctype, "string", 0, 255, "Invalid bank account type.");
	$v->isOk ($bankaccno, "num", 0, 255, "Invalid bank account number.");
	$v->isOk ($lead_source, "num", 0, 9, "Invalid lead source selected.");
	$v->isOk ($comments, "string", 0, 255, "Invalid characters in comment.");
	$v->isOk ($supbranch, "string", 0, 255, "Invalid supplier branch.");
	$v->isOk ($reference, "string", 0, 255, "Invalid references.");
	$v->isOk ($bee_status, "string", 0, 255, "Invalid BEE Status");
	$v->isOk ($team_id, "num", 1, 9, "Invalid team selection.");
	$v->isOk ($supp_grp, "num", 1, 9, "Invalid supplier group selected.");
	$v->isOk ($setdisc, "float", 1, 40, "Invalid Settlement Discount Amount.");
	$v->isOk ($setdays, "num", 1, 40, "Invalid Settlement Discount Days");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}



	db_conn('cubit');
	$Sl = "SELECT * FROM suppliers WHERE supno='$supno'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri) > 0) {
		return enter("<li class='err'>Supplier number exists.</li>");
	}

	# Connect to db
	db_connect ();
	$curr = getSymbol($fcid);

	if ( ! pglib_transaction("BEGIN") ) {
		return "<li class='err'>Unable to add supplier to database. (TB)</li>";
	}

	# Write to db
	$sql = "
		INSERT INTO suppliers (
			deptid, supno, supname, location, fcid, 
			currency, vatnum, supaddr, suppostaddr, contname, tel, 
			fax, cell, email, url, listid, bankname, 
			branname, brancode, bankaccno, balance, fbalance, 
			div, lead_source, comments, branch, bee_status, 
			reference, team_id, registration, bankaccname, bankacctype, 
			setdisc, setdays
		) VALUES (
			'$deptid', '$supno', '$supname', '$loc', '$fcid', 
			'$curr[symbol]', '$vatnum', '$supaddr', '$suppostaddr', '$contname', '$tel', 
			'$fax', '$cell', '$email', '$url', '$listid', '$bankname', 
			'$branname', '$brancode', '$bankaccno', 0, 0, 
			'".USER_DIV."', '$lead_source', '$comments', '$supbranch', '$bee_status', 
			'$reference', '$team_id', '$registration', '$bankaccname', '$bankacctype', 
			'$setdisc', '$setdays'
		)";
	$supRslt = db_exec ($sql) or errDie ("Unable to add supplier to the system.", SELF);
	if (pg_cmdtuples ($supRslt) < 1) {
		return "<li class='err'>Unable to add supplier to database.</li>";
	}

	if ( ($supp_id = pglib_lastid("suppliers", "supid")) == 0 ) {
		return "<li class='err'>Unable to add supplier to contact list.</li>";
	}
	
	#handle supplier groups
	if($supp_grp != 0){
		$insert_sql = "INSERT INTO supp_grpowners (grpid,supid) VALUES ('$supp_grp','$supp_id')";
		$run_insert = db_exec($insert_sql) or errDie("Unable to add supplier group information.");
	}

	# Check if should be added to contact list
	db_connect();
	$sql = "
		INSERT INTO cons (
			name, surname, comp, ref, tell, cell, 
			fax, email, hadd, padd, date, supp_id, 
			con, by, div
		) VALUES (
			'$contname', '$supname', '', 'Supplier', '$tel', '$cell', 
			'$fax', '$email', '$supaddr', '$suppostaddr', CURRENT_DATE, '$supp_id', 
			'No', '".USER_NAME."','".USER_DIV."'
		)";
	$rslt = db_exec($sql) or errDie ("Unable to add supplier to contact list.", SELF);

	if ( ! pglib_transaction("COMMIT") ) {
		return "<li class='err'>Unable to add supplier to database. (TC)</li>";
	}

	$Date = date("Y-m-d");

	$qryi = new dbUpdate();
	for ($i = 1; $i <= 12; ++$i) {
		$qryi->setTable("suppledger", "$i");

		$cols = grp(
			m("supid", $supp_id),
			m("contra", "0"),
			m("edate", $Date),
			m("sdate", $Date),
			m("eref", "0"),
			m("descript", "Balance"),
			m("credit", "0"),
			m("debit", "0"),
			m("div", USER_DIV),
			m("dbalance", "0"),
			m("cbalance", "0")
		);
		$qryi->setCols($cols);
		$qryi->run(DB_INSERT);
	}

	 if(isset($crm)) {
		header("Location: crm/tokens-new.php?value=$supname");
		exit;
	}

	if($re != "no") {
		db_conn('cubit');
		$re += 0;
		$Sl = "UPDATE purchases SET supid='$supp_id' WHERE purid='$re'";
		$Ri = db_exec($Sl);
		//print $Sl;exit;
		header("Location: purchase-new.php?purid=$re&cont=1&letters=");
		exit;
	}

	// Update documents
	$sql = "SELECT * FROM crm.stmp_docs WHERE session='$_REQUEST[CUBIT_SESSION]'";
	$stdoc_rslt = db_exec($sql) or errDie("Unable to retrieve docs.");

	while ($stdoc_data = pg_fetch_array($stdoc_rslt)) {
		$sql = "
			INSERT INTO crm.supplier_docs (
				supid, file, type, filename, size, 
				real_filename
			) VALUES (
				'$supp_id', '$stdoc_data[file]', '$stdoc_data[type]', '$stdoc_data[filename]', '$stdoc_data[size]', 
				'$stdoc_data[real_filename]'
			)";
		db_exec($sql) or errDie("Unable to save files to customer.");

		$sql = "DELETE FROM crm.stmp_docs WHERE id='$stdoc_data[id]'";
		db_exec($sql) or errDie("Unable to remove tmp file.");
	}

	$sql = "SELECT * FROM cubit.cons WHERE supp_id='$supp_id'";
	$con_rslt = db_exec($sql) or errDie("Unable to retrieve contact.");
	$con_data = pg_fetch_array($con_rslt);

	if (pg_num_rows($con_rslt)) {
		$con_out = "<a href='javascript:popupOpen(\"groupware/conper-add.php?type=conn&id=$con_data[id]\")'>
			Add Sub Contact
		</a>";
	} else {
		$con_out = "";
	}

	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Supplier added to the system</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>New Supplier <b>$supname</b>, has been successfully added to the system. $con_out</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='supp-new.php'>Add Supplier</td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='supp-view.php'>View Suppliers</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
//	return $write;

	$_POST = array ();
	return enter ("<li class='yay'>Supplier added to the system. $con_out</li><br>");

}


?>
