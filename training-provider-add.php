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
if (isset ($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = confirm ($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write ($HTTP_POST_VARS);
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
function enter ()
{

	# Select previous year database
	db_connect();
	$lastid = pglib_lastid("suppliers","supid");

	# get last account number
	$sql = "SELECT supno FROM suppliers WHERE supid = '$lastid' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		do{
			$lastid--;
			# get last account number
			$sql = "SELECT supno FROM suppliers WHERE supid = '$lastid' AND div = '".USER_DIV."'";
			$accRslt = db_exec($sql);
			if(pg_numrows($accRslt) < 1){
				$supno = "";
				$nsupno= "";
			}else{
				$acc = pg_fetch_array($accRslt);
				$supno = $acc['supno'];
			}
		}while(strlen($supno) < 1 && $lastid > 1);
	}else{
		$acc = pg_fetch_array($accRslt);
		$supno = $acc['supno'];
	}

	# Check if we got $supno(if not skip this)
	if(strlen($supno) > 0){
		# Get the next account number
		$num = preg_replace ("/[^\d]+/", "", $supno);
		$num++;
		$chars = preg_replace("/[\d]/", "", $supno);
		$nsupno = $chars.$num;
	} else {
		$nsupno=1;
	}

	# Departments
	db_conn("exten");
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' LIMIT 1";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
			return "<li>There are no Price lists in Cubit.</li>";
	}else{
			$dept = pg_fetch_array($deptRslt);
	}

	# Get pricelist
	$sql = "SELECT * FROM spricelist WHERE div = '".USER_DIV."' LIMIT 1";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		return "<li>There are no Price lists in Cubit.</li>";
	}else{
		$list = pg_fetch_array($listRslt);
	}

	db_connect();

	# Currency drop down
//	$currsel = ext_unddbsel("fcid", "currency", "fcid", "descrip", "There are is no currency found in Cubit, please add currency first.", "");

	global $HTTP_GET_VARS;
	extract($HTTP_GET_VARS);
	if(isset($crm)) {
		$ex = "<input type='hidden' name='crm' value=''>";
	} else {
		$ex = "";
	}

	if(!isset($re)) {
		$re = "no";
	}

	if(isset($bee_training) AND ($bee_training == "no")){
		$sel1 = "";
		$sel2 = "checked=yes";	
	}else {
		$sel1 = "checked=yes";
		$sel2 = "";
	}

	// Layout
	$enter = "
				<h3>Add Training Provider</h3>
				<form action='".SELF."' method='POST'>
				<table cellpadding='0' cellspacing='0'>
					<tr valign='top'>
						<td>
							<table ".TMPL_tblDflts.">
								<input type='hidden' name='key' value='confirm'>
								<input type='hidden' name='re' value='$re'>
								<input type='hidden' name='deptid' value='$dept[deptid]'>
								<input type='hidden' name='supno' value='$nsupno'>
								<input type='hidden' name='loc' value='loc'>
								<input type='hidden' name='fcid' value='2'>
								<input type='hidden' name='listid' value='$list[listid]'>
								$ex
								<tr>
									<th colspan='2'>Training Provider Details</th>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>".REQ."Name</td>
									<td><input type='text' size='20' name='supname'></td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>".REQ."Address</td>
									<td><textarea name='supaddr' rows='5' cols='18'></textarea></td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>".REQ."Contact Name</td>
									<td><input type='text' size='20' name='contname'></td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>".REQ."Tel No.</td>
									<td><input type='text' size='20' name='tel'></td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Fax No.</td>
									<td><input type='text' size='20' name='fax'></td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Training BEE</td>
									<td>Yes <input type='radio' name='bee_training' value='yes' $sel1> No <input type='radio' name='bee_training' value='no' $sel2></td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td valign='center'>Notes</td>
									<td><textarea cols='25' rows='4' name='comments'></textarea></td>
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
						</td>
					</tr>
				</form>
				</table>";
	return $enter;

}



# error func
function enter_err ($HTTP_POST_VARS, $err="")
{

	# get vars
	extract ($HTTP_POST_VARS);

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
			return "<li>There are no Price lists in Cubit.";
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
	$locsel = extlib_cpsel("loc", $locs, $loc);

	# Currency drop down
	$currsel = ext_unddbsel("fcid", "currency", "fcid", "descrip", "There are is no currency found in Cubit, please add currency first.", $fcid);

        if(isset($crm)) {
		$ex = "<input type='hidden' name='crm' value=''>";
	} else {
		$ex = "";
	}

	if(isset($bee_training) AND ($bee_training == "no")){
		$sel1 = "";
		$sel2 = "checked=yes";	
	}else {
		$sel1 = "checked=yes";
		$sel2 = "";
	}

	$enter = "
				<h3>Add Training Provider</h3>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='confirm'>
					<input type='hidden' name='re' value='$re'>
					$ex
				<table cellpadding='0' cellspacing='0'>
					<tr>
						<td colspan='2'>$err</td>
					</tr>
					<tr valign='top'>
						<td>
							<table ".TMPL_tblDflts.">
								<tr><th colspan='2'>Training Provider Details</th></tr>
								<tr bgcolor='".bgcolor()."'>
									<td>".REQ."Department</td>
									<td>$depts</td>
								</tr>
								<tr bgcolor='".bgcolor()."'>
									<td>".REQ."Supplier No</td>
									<td><input type='text' size='10' name='supno' value='$supno'></td>
								</tr>
								<tr bgcolor='".bgcolor()."'>
									<td>".REQ."Name</td>
									<td><input type='text' size='20' name='supname' value='$supname'></td>
								</tr>
								<tr bgcolor='".bgcolor()."'>
									<td>".REQ."Type</td>
									<td>$locsel</td>
								</tr>
								<tr bgcolor='".bgcolor()."'>
									<td>".REQ."Currency</td>
									<td>$currsel</td>
								</tr>
								<tr bgcolor='".bgcolor()."'>
									<td>".REQ."Address</td>
									<td><textarea name='supaddr' rows='5' cols='18'>$supaddr</textarea></td>
								</tr>
								<tr bgcolor='".bgcolor()."'>
									<td>".REQ."Contact Name</td>
									<td><input type='text' size='20' name='contname' value='$contname'></td>
								</tr>
								<tr bgcolor='".bgcolor()."'>
									<td>".REQ."Tel No.</td>
									<td><input type='text' size='20' name='tel' value='$tel'></td>
								</tr>
								<tr bgcolor='".bgcolor()."'>
									<td>Fax No.</td>
									<td><input type='text' size='20' name='fax' value='$fax'></td>
								</tr>
								<tr bgcolor='".bgcolor()."'>
									<td>".REQ."Price List</td>
									<td>$pricelists</td>
								</tr>
								<tr bgcolor='".bgcolor()."'>
									<td>Training BEE</td>
									<td>Yes <input type='radio' name='bee_training' value='yes' $sel1> No <input type='radio' name='bee_training' value='no' $sel2></td>
								</tr>
								<tr bgcolor='".bgcolor()."'>
									<td>Notes</td>
									<td><textarea cols='25' rows='4' name='comments'>$comments</textarea></td>
								</tr>
								".TBL_BR."
								<tr>
									<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
								</tr>
								".TBL_BR."
								<tr>
									<td colspan='2' align='right'>
										<table border=0 cellpadding='2' cellspacing='1'>
											<tr>
												<th>Quick Links</th>
											</tr>
											<tr bgcolor='".bgcolorg()."'>
												>td><a href='supp-view.php'>View Suppliers</a></td>
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
				</form>
				</table>";
	return $enter;

}



# confirm new data
function confirm ($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 1, 255, "Invalid Department.");
	$v->isOk ($supno, "string", 1, 255, "Invalid supplier number.");
	$v->isOk ($supname, "string", 1, 255, "Invalid supplier name.");
	$v->isOk ($loc, "string", 1, 3, "Invalid Type.");
	$v->isOk ($fcid, "num", 1, 30, "Invalid Currency.");
	$v->isOk ($supaddr, "string", 1, 255, "Invalid supplier address.");
	$v->isOk ($contname, "string", 1, 255, "Invalid contact name.");
	$v->isOk ($tel, "string", 1, 20, "Invalid tel no.");
	$v->isOk ($fax, "string", 0, 20, "Invalid fax no.");
	$v->isOk ($listid, "num", 1, 20, "Invalid price list.");
	$v->isOk ($bee_training, "string", 0, 255, "Invalid BEE Status");
	$v->isOk ($comments, "string", 0, 255, "Invalid Notes");
	
	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return enter_err($HTTP_POST_VARS, $confirm);
		exit;
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	db_conn('cubit');
	$Sl = "SELECT * FROM suppliers WHERE supno='$supno'";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");
	
	if(pg_num_rows($Ri)>0) {
		return enter_err($HTTP_POST_VARS, "<li class=err>There is already a supplier with that number.</lI>");
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
		$deptname = "<li class=err>Department not Found.";
	}else{
		$dept = pg_fetch_array($deptRslt);
		$deptname = $dept['deptname'];
	}

	# Get Price List
	$sql = "SELECT * FROM spricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		$plist = "<li class=err>Class not Found.";
	}else{
		$list = pg_fetch_array($listRslt);
		$plist = $list['listname'];
	}

	# Locations drop down
	$locs = array("loc"=>"Local", "int"=>"International");
	$curr = getSymbol($fcid);

	  if(isset($crm)) {
		$ex = "<input type='hidden' name='crm' value=''>";
	} else {
		$ex = "";
	}

	$confirm = "
				<h3>Confirm Training Provider</h3>
				<form action='".SELF."' method='POST'>
				<table ".TMPL_tblDflts.">
					<input type='hidden' name='key' value='write'>
					<input type='hidden' name='deptid' value='$deptid'>
					<input type='hidden' name='supno' value='$supno'>
					<input type='hidden' name='supname' value='$supname'>
					<input type='hidden' name='loc' value='$loc'>
					<input type='hidden' name='fcid' value='$fcid'>
					<input type='hidden' name='supaddr' value='$supaddr'>
					<input type='hidden' name='contname' value='$contname'>
					<input type='hidden' name='tel' value='$tel'>
					<input type='hidden' name='fax' value='$fax'>
					<input type='hidden' name='listid' value='$listid'>
					<input type='hidden' name='re' value='$re'>
					<input type='hidden' name='bee_training' value='$bee_training'>
					<input type='hidden' name='comments' value='$comments'>
					$ex
					<tr valign='top'>
						<td>
							<table ".TMPL_tblDflts.">
								<tr>
									<th colspan='2'>Supplier Details</th>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Name </td>
									<td>$supname</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Address</td>
									<td><pre>$supaddr</pre></td>
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
									<td>Training BEE</td>
									<td>$bee_training</td>
								</tr>
								<tr bgcolor='".bgcolorg()."'>
									<td>Notes</td>
									<td>".nl2br($comments)."</td>
								</tr>
								".TBL_BR."
								<tr>
									<td align='left'><input type='submit' name='back' value='&laquo; Correction'></td>
									<td align='right'><input type='submit' value='Write &raquo;'></td>
								</tr>
								".TBL_BR."
								<tr>
									<td colspan='2'>
										<table border=0 cellpadding='2' cellspacing='1'>
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
				</form>
				</table>";
	return $confirm;

}




# write new data
function write ($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);
	
	if(isset($back)) {
		return enter_err($HTTP_POST_VARS);
	}
	
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deptid, "num", 1, 255, "Invalid Department.");
	$v->isOk ($supno, "string", 1, 255, "Invalid supplier number.");
	$v->isOk ($supname, "string", 1, 255, "Invalid supplier name.");
	$v->isOk ($loc, "string", 1, 3, "Invalid Type.");
	$v->isOk ($fcid, "num", 1, 30, "Invalid Currency.");
	$v->isOk ($supaddr, "string", 1, 255, "Invalid supplier address.");
	$v->isOk ($contname, "string", 1, 255, "Invalid contact name.");
	$v->isOk ($tel, "string", 1, 20, "Invalid tel no.");
	$v->isOk ($fax, "string", 0, 20, "Invalid fax no.");
	$v->isOk ($listid, "num", 1, 20, "Invalid price list.");
	$v->isOk ($bee_training, "string", 0, 255, "Invalid BEE Status");
	$v->isOk ($comments, "string", 0, 255, "Invalid Notes");


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
	$Sl="SELECT * FROM suppliers WHERE supno='$supno'";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");
	
	if(pg_num_rows($Ri)>0) {
		return enter_err($HTTP_POST_VARS, "<li class='err'>There is already a supplier with that number.</li>");
	}

	# Connect to db
	db_connect ();
	$curr = getSymbol($fcid);

	if ( ! pglib_transaction("BEGIN") ) {
		return "<li class='err'>Unable to add supplier to database. (TB)</li>";
	}

	# Write to db
	$sql = "INSERT INTO  suppliers(deptid, supno, supname, location, fcid, currency, supaddr, contname, tel, fax, listid, balance, fbalance, div, bee_training, comments)
	VALUES ('$deptid', '$supno', '$supname', '$loc', '$fcid', '$curr[symbol]', '$supaddr', '$contname', '$tel', '$fax', '$listid', 0, 0, '".USER_DIV."', '$bee_training', '$comments')";
	$supRslt = db_exec ($sql) or errDie ("Unable to add supplier to the system.", SELF);
	if (pg_cmdtuples ($supRslt) < 1) {
		return "<li class='err'>Unable to add supplier to database.</li>";
	}

	if ( ($supp_id = pglib_lastid("suppliers", "supid")) == 0 ) {
		return "<li class='err'>Unable to add supplier to contact list.</li>";
	}

	# Check if should be added to contact list
	db_connect();
	$sql = "INSERT INTO cons (name,surname,comp,ref,tell,cell,fax,hadd,padd,date,supp_id,con,by,div)
	VALUES ('$contname','$supname','','Supplier','$tel','','$fax','$supaddr','',CURRENT_DATE, '$supp_id', 'No', '".USER_NAME."','".USER_DIV."')";
	$rslt = db_exec($sql) or errDie ("Unable to add supplier to contact list.", SELF);

	if ( ! pglib_transaction("COMMIT") ) {
		return "<li class='err'>Unable to add supplier to database. (TC)</li>";
	}
	
	$Date=date("Y-m-d");
	
	db_conn('audit');
	$Sl = "SELECT * FROM closedprd ORDER BY id";
	$Ri = db_exec($Sl);
	
	while($pd = pg_fetch_array($Ri)) {
	
		db_conn($pd['prdnum']);
		
		$Sl = "INSERT INTO suppledger(supid,contra,edate,sdate,eref,descript,credit,debit,div,dbalance,cbalance) VALUES 
		('$supp_id','0','$Date','$Date','0','Balance','0','0','".USER_DIV."','0','0')";
		$Rj = db_exec($Sl) or errDie("Unable to insert cust balances");
	
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


	$write = "
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Supplier added to the system</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>New Supplier <b>$supname</b>, has been successfully added to the system.</td>
					</tr>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='supp-new.php'>Add Supplier</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='supp-view.php'>View Suppliers</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $write;

}


?>