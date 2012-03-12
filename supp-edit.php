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

# Get settings
require ("settings.php");

# Decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		case "doc_save":
			$OUTPUT = doc_save();
			break;
		default:
			if (isset($_GET['supid'])){
				$OUTPUT = edit ($_GET['supid']);
			} else {
				$OUTPUT = "<li> - Invalid use of module</li>";
			}
	}
} else {
	if (isset($_GET['supid'])){
		$OUTPUT = edit ($_GET['supid']);
	} else {
		$OUTPUT = "<li> - Invalid use of module.</li>";
	}
}

# display output
require ("template.php");




function edit($supid, $err="")
{

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 50, "Invalid supplier id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>-".$e["msg"]."</li>";
		}
		return $confirm;
	}



	# Select
	db_connect();

	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
		return "<li> Invalid supplier ID.</li>";
	}else{
		$supp = pg_fetch_array($suppRslt);
		# get vars
		extract ($supp);
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
			if($dept['deptid'] == $deptid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$depts .= "<option value='$dept[deptid]' $sel>$dept[deptname]</option>";
		}
	}
	$depts .= "</select>";

	# Get Pricelists
	$pricelists = "<select name='listid' style='width: 120'>";
	$sql = "SELECT * FROM spricelist WHERE div = '".USER_DIV."' ORDER BY listname ASC";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		return "<li>There are no Price lists in Cubit.</li>";
	}else{
		while($list = pg_fetch_array($listRslt)){
			if($list['listid'] == $listid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$pricelists .= "<option value='$list[listid]' $sel>$list[listname]</option>";
		}
	}
	$pricelists .= "</select>";

	db_connect();

	# Locations drop down
	$locs = array("loc"=>"Local", "int"=>"International");
	$locsel = extlib_cpsel("loc", $locs, $location);

	# Currency drop down
	$currsel = ext_unddbsel("fcid", "currency", "fcid", "descrip", "There are is no currency found in Cubit, please add currency first.", $fcid);

	if(isset($bee_status) AND ($bee_status == "no")){
		$sel1 = "";
		$sel2 = "checked=yes";
	}else {
		$sel1 = "checked=yes";
		$sel2 = "";
	}


	$get_grp = "SELECT * FROM supp_grpowners WHERE supid = '$supid' LIMIT 1";
	$run_grp = db_exec($get_grp) or errDie("Unable to get supplier group information.");
	if(pg_numrows($run_grp) < 1){
		$supp_grp = "0";
	}else {
		$sarr = pg_fetch_array($run_grp);
		$supp_grp = $sarr['grpid'];
	}

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

	$setdays_drop = "
		<select name='setdays'>
			<option $setdayssel1 value='0'>Last Day Of The Month</option>
			<option $setdayssel2 value='1'>1st Day Of The Month</option>
			<option $setdayssel3 value='7'>7th Of The Month</option>
			<option $setdayssel4 value='15'>15th Of The Month</option>
			<option $setdayssel5 value='25'>25th Of The Month</option>
			<option $setdayssel6 value='60'>End Of Next Month</option>
		</select>";

	if ($supid != 0){

		db_connect ();

		#get 1 lower cusnum
		$get_prev = "SELECT supid FROM suppliers WHERE supid < '$supid' ORDER BY supid DESC LIMIT 1";
		$run_prev = db_exec($get_prev) or errDie ("Unable to get previous supplier information.");
		if (pg_numrows($run_prev) > 0){
			$back_supid = pg_fetch_result ($run_prev,0,0);
			$show_back_button = "<input type='button' onClick=\"document.location='supp-edit.php?supid=$back_supid';\" value='View Previous Supplier'>";
		}

		$get_next = "SELECT supid FROM suppliers WHERE supid > '$supid' ORDER BY supid ASC LIMIT 1";
		$run_next = db_exec($get_next) or errDie ("Unable to get next supplier information.");
		if (pg_numrows($run_next) > 0){
			$next_supid = pg_fetch_result ($run_next,0,0);
			$show_next_button = "<input type='button' onClick=\"document.location='supp-edit.php?supid=$next_supid';\" value='View Next Supplier'>";
		}

		$showbuttons = "$show_back_button $show_next_button <br><br>";

	}
//<input type='button' onClick=\"popupSized('contact-entry-new.php?type=supplier&sup=$supid','Add Additional Contact','880','580')\" value='Add Additional Contact'>
//http://127.0.0.1/cubit3.33/conper-add.php?type=supp&id=2

	$enter = "
		$showbuttons
		<form action='".SELF."' method='POST'>
			$err
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='supid' value='$supid'>
		<table cellpadding=0 cellspacing=0>
			<tr valign='top'>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Supplier Details</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Department</td>
							<td>$depts</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Supplier No</td>
							<td><input type='text' size='10' name='supno' value='$supno'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Supplier/Name</td>
							<td><input type='text' size='20' name='supname' value='$supname'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Registration/ID</td>
							<td><input type='text' size='20' name='registration' value='$registration'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Branch</td>
							<td><input type='text' size='20' name='branch' value='$branch'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Type</td>
							<td>$locsel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Currency</td>
							<td>$currsel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ." VAT Number</td>
							<td><input type='text' size='21' name='vatnum' value='$vatnum'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ." Address</td>
							<td><textarea name='supaddr' rows='5' cols='18'>$supaddr</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Postal Address</td>
							<td><textarea name='suppostaddr' rows='5' cols='18'>$suppostaddr</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Contact Name</td>
							<td>
								<input type='text' size='20' name='contname' value='$contname'>
								&nbsp;&nbsp;
								<input type='button' onClick=\"popupSized('conper-add.php?type=supp&id=$supid','Add Additional Contact','880','580')\" value='Add Additional Contact'>
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Tel No.</td>
							<td><input type='text' size='20' name='tel' value='$tel'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Fax No.</td>
							<td><input type='text' size='20' name='fax' value='$fax'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Cell No.</td>
							<td><input type='text' size='20' name='cell' value='$cell'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>E-mail</td>
							<td><input type='text' size='20' name='email' value='$email'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Web Address</td>
							<td>http://<input type='text' size='30' name='url' value='$url'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Price List</td>
							<td>$pricelists</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier Group</td>
							<td>$supp_grpdrop</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Status BEE</td>
							<td>Yes <input type='radio' name='bee_status' value='yes' $sel1> No <input type='radio' name='bee_status' value='no' $sel2></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Team Permissions</td>
							<td>$team_sel</td>
						</tr>
					</table>
				</td>
				<td>
					<table ".TMPL_tblDflts.">
						<tr bgcolor='".bgcolorg()."'>
							<th colspan='2'> Bank Details</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Bank </td>
							<td><input type='text' size='20' name='bankname' value='$bankname'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Branch</td>
							<td><input type='text' size='20' name='branname' value='$branname'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Branch Code</td>
							<td><input type='text' size='20' name='brancode' value='$brancode'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account Name</td>
							<td><input type='text' size='20' name='bankaccname' value='$bankaccname'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account Type</td>
							<td><input type='text' size='20' name='bankacctype' value='$bankacctype'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account Number</td>
							<td><input type='text' size='20' name='bankaccno' value='$bankaccno'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Reference</td>
							<td><input type='text' size='20' name='reference' value='$reference'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Settlement Discount %</td>
							<td><input type='text' name='setdisc' value='$setdisc'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Statement Day</td>
							<td>$setdays_drop</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Comments</td>
							<td><textarea name='comments' rows='5' cols='18'>$comments</textarea></td>
						</tr>
						<tr>
							<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='2' align='right'>
								<table border='0' cellpadding='2' cellspacing='1'>
									<tr>
										<th>Quick Links</th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='supp-view.php'>View Suppliers</a></td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='main.php'>Main Menu</a></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>";
	return $enter;

}



# error func
function edit_err ($_POST, $err="")
{

	# get vars
	extract ($_POST);

	# Departments
	db_conn("exten");

	$depts = "<select name='deptid'>";
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return "<li>There are no Price lists in Cubit.</li>";
	}else{
		while($dept = pg_fetch_array($deptRslt)){
			if($dept['deptid'] == $deptid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$depts .= "<option value='$dept[deptid]' $sel>$dept[deptname]</option>";
		}
	}
	$depts .= "</select>";

	# Get Pricelists
	$pricelists = "<select name='listid' style='width: 120'>";
	$sql = "SELECT * FROM spricelist WHERE div = '".USER_DIV."' ORDER BY listname ASC";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		return "<li>There are no Price lists in Cubit.</li>";
	}else{
		while($list = pg_fetch_array($listRslt)){
			if($list['listid'] == $listid){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$pricelists .= "<option value='$list[listid]' $sel>$list[listname]</option>";
		}
	}
	$pricelists .= "</select>";

	db_connect();

	if(!isset($location)) {
		$location = "";
	}
	# Locations drop down
	$locs = array("loc"=>"Local", "int"=>"International");
	$locsel = extlib_cpsel("loc", $locs, $location);

	# Currency drop down
	$currsel = ext_unddbsel("fcid", "currency", "fcid", "descrip", "There are is no currency found in Cubit, please add currency first.", $fcid);

	if(isset($bee_status) AND ($bee_status == "no")){
		$sel1 = "";
		$sel2 = "checked=yes";
	}else {
		$sel1 = "checked=yes";
		$sel2 = "";
	}


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

	// Retrieve teams
	$sql = "SELECT * FROM crm.teams WHERE id = '$team_id' LIMIT 1";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");
	$team_data = pg_fetch_array($team_rslt);
	$team_sel = "$team_data[name]";

	if(!isset($team_sel))
		$team_sel = "";

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

	$setdays_drop = "
		<select name='setdays'>
			<option $setdayssel1 value='0'>Last Day Of The Month</option>
			<option $setdayssel2 value='1'>1st Day Of The Month</option>
			<option $setdayssel3 value='7'>7th Of The Month</option>
			<option $setdayssel4 value='15'>15th Of The Month</option>
			<option $setdayssel5 value='25'>25th Of The Month</option>
		</select>";


	$enter = "
		<h3>Edit Supplier</h3>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='supid' value='$supid'>
			<input type='hidden' name='team_id' value='$team_id' />
		<table cellpadding='0' cellspacing='0'>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr valign='top'>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
							<th colspan='2'>Supplier Details</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Department</td>
							<td>$depts</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Supplier No</td>
							<td><input type='text' size='10' name='supno' value='$supno'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Supplier/Name</td>
							<td><input type='text' size='20' name='supname' value='$supname'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Registration/ID</td>
							<td><input type='text' size='20' name='registration' value='$registration'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Branch</td>
							<td><input type='text' size='20' name='branch' value='$branch'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Type</td>
							<td>$locsel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Currency</td>
							<td>$currsel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."VAT Number</td>
							<td><input type='text' size='21' name='vatnum' value='$vatnum'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Address</td>
							<td><textarea name='supaddr' rows='5' cols='18'>$supaddr</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Postal Address</td>
							<td><textarea name='suppostaddr' rows='5' cols='18'>$suppostaddr</textarea></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Contact Name</td>
							<td><input type='text' size='20' name='contname' value='$contname'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Tel No.</td>
							<td><input type='text' size='20' name='tel' value='$tel'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Fax No.</td>
							<td><input type='text' size='20' name='fax' value='$fax'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Cell No.</td>
							<td><input type='text' size='20' name='cell' value='$cell'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>E-mail</td>
							<td><input type='text' size='20' name='email' value='$email'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Web Address</td>
							<td>http://<input type='text' size='30' name='url' value='$url'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>".REQ."Price List</td>
							<td>$pricelists</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier Group</td>
							<td>$supp_grpdrop</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Status BEE</td>
							<td>Yes <input type='radio' name='bee_status' value='yes' $sel1> No <input type='radio' name='bee_status' value='no' $sel2></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Team Permissions</td>
							<td>$team_sel</td>
						</tr>
					</table>
				</td>
				<td>
					<table ".TMPL_tblDflts.">
						<tr bgcolor='".bgcolorg()."'>
							<th colspan='2'> Bank Details</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Bank </td>
							<td><input type='text' size='20' name='bankname' value='$bankname'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Branch</td>
							<td><input type='text' size='20' name='branname' value='$branname'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Branch Code</td>
							<td><input type='text' size='20' name='brancode' value='$brancode'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account Name</td>
							<td><input type='text' size='20' name='bankaccname' value='$bankaccname'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account Type</td>
							<td><input type='text' size='20' name='bankacctype' value='$bankacctype'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Account Number</td>
							<td><input type='text' size='20' name='bankaccno' value='$bankaccno'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Reference</td>
							<td><input type='text' size='20' name='reference' value='$reference'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Settlement Discount %</td>
							<td><input type='text' name='setdisc' value='$setdisc'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Statement Day</td>
							<td>$setdays_drop</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Comments</td>
							<td><textarea name='comments' rows='5' cols='18'>$comments</textarea></td>
						</tr>
						".TBL_BR."
						<tr>
							<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
						</tr>
						".TBL_BR."
						<tr>
							<td colspan='2' align='right'>
								<table border='0' cellpadding='2' cellspacing='1'>
									<tr>
										<th>Quick Links</th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='supp-view.php'>View Suppliers</a></td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='main.php'>Main Menu</a></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					</form>
				</td>
			</tr>
		</table>";
	return $enter;

}



# confirm new data
function confirm ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 1, 255, "Invalid Department.");
	$v->isOk ($supid, "num", 1, 50, "Invalid supplier id.");
	$v->isOk ($supno, "string", 1, 255, "Invalid suppleir number.");
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
	$v->isOk ($listid, "num", 1, 20, "Invalid price list.");
	$v->isOk ($url, "url", 0, 255, "Invalid web address.");
	$v->isOk ($bankname, "string", 0, 255, "Invalid bank name.");
	$v->isOk ($branname, "string", 0, 255, "Invalid branch name.");
	$v->isOk ($brancode, "string", 0, 255, "Invalid branch code.");
	$v->isOk ($bankaccname, "string", 0, 255, "Invalid bank account name.");
	$v->isOk ($bankacctype, "string", 0, 255, "Invalid bank account type.");
	$v->isOk ($bankaccno, "num", 0, 255, "Invalid bank account number.");
	$v->isOk ($comments, "string", 0, 255, "Invalid characters in comment.");
	$v->isOk ($branch, "string", 0, 255, "Invalid supplier branch.");
	$v->isOk ($reference, "string", 0, 255, "Invalid reference.");
	$v->isOk ($bee_status, "string", 0, 255, "Invalid BEE Status");
	$v->isOk ($supp_grp, "num", 1, 9, "Invalid supplier group selected.");
	$v->isOk ($team_id, "num", 1, 9, "Invalid team selection.");
	$v->isOk ($setdisc, "float", 1, 40, "Invalid Settlement Discount Amount.");
	$v->isOk ($setdays, "num", 1, 40, "Invalid Settlement Discount Days");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return edit_err($_POST, $confirm);
		exit;
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	db_conn('cubit');

	$Sl = "SELECT * FROM suppliers WHERE supno='$supno' AND supid!='$supid'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		return edit_err($_POST, "<li class='err'>There is already a supplier with that number.</lI>");
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
	$locs = array("loc"=>"Local", "int"=>"International");
	$curr = getSymbol($fcid);

	$get_suppgrp = "SELECT groupname FROM supp_groups WHERE id = '$supp_grp' LIMIT 1";
	$run_suppgrp = db_exec($get_suppgrp) or errDie("Unable to get supplier group information");
	if(pg_numrows($run_suppgrp) < 1){
		$showsupp_grp = "Unknown Supplier Group";
	}else{
		$garr = pg_fetch_array ($run_suppgrp);
		$showsupp_grp = $garr['groupname'];
	}

	$hidden = "
		<input type='hidden' name='deptid' value='$deptid'>
		<input type='hidden' name='supid' value='$supid'>
		<input type='hidden' name='supno' value='$supno'>
		<input type='hidden' name='supname' value='$supname'>
		<input type='hidden' name='loc' value='$loc'>
		<input type='hidden' name='location' value='$loc'>
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
		<input type='hidden' name='bankaccname' value='$bankaccname'>
		<input type='hidden' name='bankacctype' value='$bankacctype'>
		<input type='hidden' name='bankaccno' value='$bankaccno'>
		<input type='hidden' name='comments' value='$comments'>
		<input type='hidden' name='branch' value='$branch'>
		<input type='hidden' name='reference' value='$reference'>
		<input type='hidden' name='bee_status' value='$bee_status'>
		<input type='hidden' name='supp_grp' value='$supp_grp'>
		<input type='hidden' name='team_id' value='$team_id' />
		<input type='hidden' name='setdisc' value='$setdisc' />
		<input type='hidden' name='setdays' value='$setdays' />";


	// Retrieve teams
	$sql = "SELECT * FROM crm.teams WHERE id = '$team_id' LIMIT 1";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");
	$team_data = pg_fetch_array($team_rslt);
	$team_sel = "$team_data[name]";

	if(!isset($team_sel))
		$team_sel = "";


	$confirm = "
		<h3>Confirm Supplier</h3>
		<form action='".SELF."' method='POST'>
			$hidden
			<input type='hidden' name='key' value='write'>
		<table ".TMPL_tblDflts.">
		<table cellpadding='0' cellspacing='0'>
			<tr valign='top'>
				<td>
					<table ".TMPL_tblDflts.">
						<tr>
						<th colspan='2'>Supplier Details</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Department</td>
							<td>$deptname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier No</td>
							<td>$supno</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier/Name </td>
							<td>$supname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Registration/ID </td>
							<td>$registration</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Branch</td>
							<td>$branch</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Type</td>
							<td>$locs[$loc]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Currency</td>
							<td>$curr[symbol] - $curr[name]</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>VAT Number</td>
							<td>$vatnum</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Address</td>
							<td><pre>$supaddr</pre></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Postal Address</td>
							<td><pre>$suppostaddr</pre></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Contact Name</td>
							<td>$contname</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Tel No.</td>
							<td>$tel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Fax No.</td>
							<td>$fax</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Cell No.</td>
							<td>$cell</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>E-mail</td>
							<td>$email</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Web Address</td>
							<td>http://$url</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Price List</td>
							<td>$plist</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Supplier Group</td>
							<td>$showsupp_grp</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Status BEE</td>
							<td>$bee_status</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Team Permissions</td>
							<td>$team_sel</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Comments</td>
							<td>".nl2br($comments)."</td>
						</tr>
						<tr>
							<td><input type='submit' name='back' value='&laquo; Correction'></td>
						</tr>
					</table>
				</td>
			<td>
				<table ".TMPL_tblDflts.">
					<tr bgcolor='".bgcolorg()."'>
						<th colspan='2'> Bank Details</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Bank </td>
						<td>$bankname</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Branch</td>
						<td>$branname</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Branch Code</td>
						<td>$brancode</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Account Name</td>
						<td>$bankaccname</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Account Type</td>
						<td>$bankacctype</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Account Number</td>
						<td>$bankaccno</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Reference</td>
						<td>$reference</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Settlement Discount %</td>
						<td>$setdisc %</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Statement Day</td>
						<td>$setdays</td>
					</tr>
					".TBL_BR."
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Write &raquo;'></td>
					</tr>
					".TBL_BR."
					<tr>
						<td colspan='2'>";

	// Retrieve documents added already
	$sql = "
		SELECT id,file,type,filename,size,real_filename,'supplier_docs' AS table 
			FROM crm.supplier_docs
			WHERE supid='$supid' 
		UNION 
		SELECT id,file,type,filename,size,real_filename,'stmp_docs' AS table 
			FROM crm.stmp_docs 
			WHERE session='$_REQUEST[CUBIT_SESSION]' ORDER BY id DESC";
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

		if ($sdoc_data['table'] == "supplier_docs"){
			$sdoc_out .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='supp_doc_get.php?id=$sdoc_data[id]&tmp=1'>$showdoc</a></td>
					<td>".getFileSize($sdoc_data["size"])."</td>
					<td><input type='checkbox' name='oldrem[$sdoc_data[id]]' value='$sdoc_data[id]' /></td>
				</tr>";
		}else {
			$sdoc_out .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='supp_doc_get.php?id=$sdoc_data[id]&tmp=1'>$showdoc</a></td>
					<td>".getFileSize($sdoc_data["size"])."</td>
					<td><input type='checkbox' name='rem[$sdoc_data[id]]' value='$sdoc_data[id]' /></td>
				</tr>";
		}
	}

	if (empty($sdoc_out)) {
		$sdoc_out .= "<tr bgcolor='".bgcolorg()."'><td colspan='3'><li>No documents added</li></td></tr>";
	}

		$confirm .= "
								</form>
								<form method='POST' action='".SELF."' enctype='multipart/form-data'>
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
									<tr bgcolor='".bgcolorg()."'>
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
						</tr>
						<tr>
							<td colspan='2' align='right'>
								<table border='0' cellpadding='2' cellspacing='1'>
									<tr>
										<th>Quick Links</th>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='supp-view.php'>View Suppliers</a></td>
									</tr>
									<tr bgcolor='".bgcolorg()."'>
										<td><a href='main.php'>Main Menu</a></td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					</form>
				</td>
			</tr>
		</table>";
	return $confirm;

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

	if (isset($oldrem)) {
		foreach ($oldrem as $id => $value) {
			$sql2 = "DELETE FROM crm.supplier_docs WHERE id = '$id'";
			$run_sql = db_exec($sql2) or errDie ("Unable to remove customer information");
		}
	}

	return confirm($_REQUEST);

}



# write new data
function write ($_POST)
{

	# get vars
	extract ($_POST);

	if(isset($back)) {
		return edit_err($_POST);
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 1, 255, "Invalid Department.");
	$v->isOk ($supid, "num", 1, 50, "Invalid supplier id.");
	$v->isOk ($supno, "string", 1, 255, "Invalid suppleir number.");
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
	$v->isOk ($listid, "num", 1, 20, "Invalid price list.");
	$v->isOk ($url, "url", 0, 255, "Invalid web address.");
	$v->isOk ($bankname, "string", 0, 255, "Invalid bank name.");
	$v->isOk ($branname, "string", 0, 255, "Invalid branch name.");
	$v->isOk ($brancode, "string", 0, 255, "Invalid branch code.");
	$v->isOk ($bankaccname, "string", 0, 255, "Invalid bank account name.");
	$v->isOk ($bankacctype, "string", 0, 255, "Invalid bank account type.");
	$v->isOk ($bankaccno, "num", 0, 255, "Invalid bank account number.");
	$v->isOk ($comments, "string", 0, 255, "Invalid characters in comment.");
	$v->isOk ($branch, "string", 0, 255, "Invalid supplier branch.");
	$v->isOk ($reference, "string", 0, 255, "Invalid reference.");
	$v->isOk ($bee_status, "string", 0, 255, "Invalid BEE Status");
	$v->isOk ($supp_grp, "num", 1, 9, "Invalid supplier group selected.");
	$v->isOk ($team_id, "num", 1, 9, "Invalid team selection.");
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

	$Sl = "SELECT * FROM suppliers WHERE supno='$supno' AND supid!='$supid'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ri)>0) {
		return edit_err($_POST, "<li class='err'>There is already a supplier with that number.</li>");
	}

	# connect to db
	db_connect();
	$curr = getSymbol($fcid);

	# write to db
	$sql = "
		UPDATE suppliers 
		SET 
			deptid = '$deptid', supno = '$supno', supname = '$supname', vatnum = '$vatnum', supaddr = '$supaddr', 
			suppostaddr = '$suppostaddr', contname = '$contname', tel = '$tel', fax = '$fax', cell = '$cell', 
			email = '$email', url = '$url', listid = '$listid', bankname = '$bankname', branname = '$branname', 
			brancode = '$brancode', bankaccno = '$bankaccno', location = '$loc', fcid = '$fcid', currency = '$curr[symbol]', 
			comments = '$comments', branch = '$branch', reference = '$reference', bee_status = '$bee_status', 
			registration = '$registration', bankaccname = '$bankaccname', bankacctype = '$bankacctype', 
			team_id = '$team_id', setdisc = '$setdisc', setdays = '$setdays' 
		WHERE supid  = '$supid'";

	$suppRslt = db_exec ($sql) or errDie ("Unable to edit supplier on the system.", SELF);
	if (pg_cmdtuples ($suppRslt) < 1) {
		return "<li class='err'>Unable to Edit supplier in database.</li>";
	}

	// Update documents
	$sql = "SELECT * FROM crm.stmp_docs WHERE session='$_REQUEST[CUBIT_SESSION]'";
	$stdoc_rslt = db_exec($sql) or errDie("Unable to retrieve docs.");

	while ($stdoc_data = pg_fetch_array($stdoc_rslt)) {
		$sql = "
			INSERT INTO crm.supplier_docs (
				supid, file, type, filename, size, real_filename
			) VALUES (
				'$supid', '$stdoc_data[file]', '$stdoc_data[type]', '$stdoc_data[filename]', '$stdoc_data[size]', '$stdoc_data[real_filename]'
			)";
		db_exec($sql) or errDie("Unable to save files to customer.");

		$sql = "DELETE FROM crm.stmp_docs WHERE id='$stdoc_data[id]'";
		db_exec($sql) or errDie("Unable to remove tmp file.");
	}

	#handle supplier groups
	if($supp_grp != 0){

		#group set, check whether we should add the new entry or update a existing one ...
		$check_grp = "SELECT * FROM supp_grpowners WHERE supid = '$supid' LIMIT 1";
		$run_check = db_exec($check_grp) or errDie("Unable to get supplier group information.");
		if(pg_numrows($run_check) < 1){
			$insert_sql = "INSERT INTO supp_grpowners (grpid,supid) VALUES ('$supp_grp','$supid')";
			$run_insert = db_exec($insert_sql) or errDie("Unable to add supplier group information.");
		}else {
			$update_sql = "UPDATE supp_grpowners SET grpid = '$supp_grp' WHERE supid = '$supid'";
			$run_update = db_exec($update_sql) or errDie("Unable to update supplier group information");
		}

	}else {
		#remove any current entry
		$remove_sql = "DELETE FROM supp_grpowners WHERE supid = '$supid'";
		$run_remove = db_exec($remove_sql) or errDie("Unable to update supplier group informatiom");
	}

	// update the contact in the contact list if any
	$sql = "UPDATE cons SET surname='$supname', tell='$tel', fax='$fax', email='$email', padd='$supaddr' WHERE supp_id = '$supid'";
	$rslt = db_exec($sql) or errDie ("Unable to edit supplier in contact list.", SELF);

	return edit ($supid, "<li class='yay'>Supplier <b>$supname</b>, has been saved.</li><br>");

//	$write = "
//		<table ".TMPL_tblDflts." width='50%'>
//			<tr>
//				<th>Supplier edited</th>
//			</tr>
//			<tr class='datacell'>
//				<td>Supplier <b>$supname</b>, has been edited.</td>
//			</tr>
//		</table>
//		<p>
//		<table border=0 cellpadding='2' cellspacing='1'>
//			<tr>
//				<th>Quick Links</th>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td><a href='supp-view.php'>View Suppliers</a></td>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td><a href='main.php'>Main Menu</a></td>
//			</tr>
//		</table>";
//	return $write;

}


?>