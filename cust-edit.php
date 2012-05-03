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
require ("libs/ext.lib.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
	case "confirm":
		$OUTPUT = confirm($_POST);
		break;
	case "write":
		$OUTPUT = write($_POST);
		break;
	default:
		if (isset($_GET['cusnum'])){
			$OUTPUT = edit($_GET);
		} else {
			$OUTPUT = "<li>Invalid use of module</li>";
		}
	}
} else {
	if (isset($_GET['cusnum'])){
		$OUTPUT = edit($_GET);
	} else {
		$OUTPUT = "<li>Invalid use of module</li>";
	}
}

# display output
require ("template.php");




function edit($_GET, $err="")
{

	extract($_GET);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 50, "Invalid customer id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		while (list($b, $e) = each($errors)) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}



	# Select Stock
	db_connect();

	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($custRslt) < 1){
			return "<li>Invalid Customer ID.</li>";
	}else{
		$cust = pg_fetch_array($custRslt);

		# get vars
		foreach ($cust as $key => $value) {
			if (!isset($$key)) {
				$$key = $value;
			}
		}
	}

	# Nasty dot Zeros
	$credlimit += 0;

	// Select the stock category
	db_conn("exten");

	$cats= "<select name='catid' style='width: 167'>";
	$sql = "SELECT * FROM categories WHERE div = '".USER_DIV."' ORDER BY category ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
		return "<li>There are no categories in Cubit.</li>";
	}else{
		while($cat = pg_fetch_array($catRslt)){
			if($cat['catid'] == $category){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$cats .= "<option value='$cat[catid]' $sel>$cat[category]</option>";
		}
	}
	$cats .= "</select>";

	$classes = "<select name='clasid' style='width: 167'>";
	$sql = "SELECT * FROM class WHERE div = '".USER_DIV."' ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
			return "<li>There are no Classifications in Cubit.</li>";
	}else{
		while($clas = pg_fetch_array($clasRslt)){
			if($clas['clasid'] == $class){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$classes .= "<option value='$clas[clasid]' $sel>$clas[classname]</option>";
		}
	}
	$classes .= "</select>";

	$pricelists = "<select name='listid'>";
	$sql = "SELECT * FROM pricelist WHERE div = '".USER_DIV."' ORDER BY listname ASC";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		return "<li>There are no Price lists in Cubit.</li>";
	}else{
		while($list = pg_fetch_array($listRslt)){
			if($list['listid'] == $pricelist){
				$sel = "selected";
			}else{
				$sel = "";
			}
			$pricelists .= "<option value='$list[listid]' $sel>$list[listname]</option>";
		}
	}
	$pricelists .= "</select>";

	# Departments
	$depts = "<select name='deptid' style='width: 167'>";
	$sql = "SELECT * FROM departments WHERE div = '".USER_DIV."' ORDER BY deptname ASC";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		return "<li>There are no departments in Cubit.</li>";
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

	db_connect ();

	# titles
	$get_titles = "SELECT title FROM titles ORDER BY title";
	$run_titles = db_exec($get_titles) or errDie ("Unable to get title information.");
	if (pg_numrows($run_titles) < 1){
		$titles = array(
			"Mr" => "Mr",
			"Mrs" => "Mrs",
			"Miss" => "Miss"
		);
	}else {
		$titles = array ();
		while ($tarr = pg_fetch_array ($run_titles)){
			$titles[$tarr['title']] = $tarr['title'];
		}
	}
	$titles = extlib_cpsel("title", $titles, $title);

	# days drop downs
	//$days = array("0"=>"0","7"=>"7","14"=>"14","30"=>"30","60"=>"60","90"=>"90","120"=>"120");

	db_conn('exten');

	$Sl = "SELECT * FROM ct WHERE div='".USER_DIV."'";
	$Ri = db_exec($Sl);

	while($cd = pg_fetch_array($Ri)) {
		$days[$cd['days']] = $cd['days'];

	}

	$credterms = extlib_cpsel("credterm", $days, $credterm);

	unset($days);

	db_conn('exten');

	$Sl = "SELECT * FROM od WHERE div='".USER_DIV."'";
	$Ri = db_exec($Sl);

	while($cd = pg_fetch_array($Ri)) {
		$days[$cd['days']] = $cd['days'];

	}

	$overdues = extlib_cpsel("overdue", $days, $overdue);

	# keep the charge interest
	if($chrgint == "yes"){
		$chinty = "checked=yes";
		$chintn = "";
	}else{
		$chinty = "";
		$chintn = "checked=yes";
	}

	db_connect();
	# Locations drop down
	$locs = array("loc"=>"Local", "int"=>"International");
	$locsel = extlib_cpsel("loc", $locs, $location);

	$currsel = ext_unddbsel("fcid", "currency", "fcid", "descrip", "There are is no currency found in Cubit, please add currency first.", $fcid);

	$date = explode("-", $odate);

	$select_source = extlib_cpsel("lead_source", crm_get_leadsrc(-1), $lead_source);


	// Sales rep
	db_conn("exten");
	$sql = "SELECT * FROM salespeople";
	$sr_rslt = db_exec($sql) or errDie("Unable to retrieve users from Cubit.");

	$sr_sel = "<select name='sales_rep'>";
	$sr_sel .= "<option value='0'>[None]</option>";
	while ($sr_data = pg_fetch_array($sr_rslt)) {
		if ($sales_rep == $sr_data["salesp"]) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$sr_sel .= "<option value='$sr_data[salesp]' $selected>$sr_data[salesp]</option>";
	}
	$sr_sel .= "</select>";

	if (isset($onthespot)) {
		$ots_hidden = "<input type='hidden' name='onthespot' value='$onthespot'>";
	} else {
		$ots_hidden = "";
	}

	$enter = "
		$err
		<form action='".SELF."' method='POST'>
			$ots_hidden
		<table cellpadding='0' cellspacing='0' width='100%'>
			<tr>
				<th colspan='2'>Edit Customer : Customer Details</th>
			</tr>
			<tr valign='top'>
				<td>
					<table ".TMPL_tblDflts." width='100%'>
						<input type='hidden' name='key' value='confirm'>
						<input type='hidden' name='cusnum' value='$cusnum'>
						<tr class='".bg_class()."'>
							<td>".REQ."Department</td>
							<td>$depts</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Acc No</td>
							<td><input type='text' size='20' name='accno' value='$accno'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Company/Name</td>
							<td><input type='text' size='20' name='surname' value='$surname'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Title $titles</td>
							<td>".REQ."Initials <input type='text' size='15' name='init' value='$init'></td>
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
							<td>".REQ."Category</td>
							<td>$cats</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Classification</td>
							<td>$classes</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Link to Sales rep</td>
							<td>$sr_sel</td>
						</tr>
						<tr class='".bg_class()."'>
							<td valign='top'>".REQ."Postal Address</td>
							<td valign='center'><textarea rows='4' cols='19' name='paddr'>$paddr1</textarea></td>
						</tr>
						<tr class='".bg_class()."'>
							<td valign='top'>".REQ."Physical Address</td>
							<td valign='center'><textarea rows='4' cols='19' name='addr'>$addr1</textarea></td>
						</tr>
						<tr class='".bg_class()."'>
							<td valign='top'>Delivery Address</td>
							<td valign='center'><textarea rows='4' cols='19' name='del_addr'>$del_addr1</textarea></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Branches</td>
							<td><input type='button' onClick=\"window.open('cust-branch-add.php?cusnum=$cusnum','','width=380,height=250,status=1')\" value='Add Branch'><input type=button onClick=\"window.open('cust-branch-view.php?cusnum=$cusnum','','width=500,height=400,status=1')\" value='View Branch'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td valign='top'>Comments</td>
							<td valign='center'><textarea rows='4' cols='19' name='comments'>$comments</textarea></td>
						</tr>
					</table>
				</td>
				<td>
					<table ".TMPL_tblDflts." width='100%'>
						<tr class='".bg_class()."'>
							<td>VAT Number</td>
							<td><input type='text' size='21' name='vatnum' value='$vatnum'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Contact Name</td>
							<td><input type='text' size='21' name='contname' value='$contname'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Business Tel.</td>
							<td><input type='text' size='21' name='bustel' value='$bustel'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Home Tel.</td>
							<td><input type='text' size='21' name='tel' value='$tel'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Cell No.</td>
							<td><input type='text' size='21' name='cellno' value='$cellno'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Fax No.</td>
							<td><input type='text' size='21' name='fax' value='$fax'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>E-mail</td>
							<td><input type='text' size='21' name='email' value='$email'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Web Address</td>
							<td>http://<input type='text' size='30' name='url' value='$url'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Price List</td>
							<td>$pricelists</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Trade Discount &nbsp;<input type='text' size='6' name='traddisc' value='$traddisc'>%</td>
							<td>Settlement Discount <input type='text' size='7' name='setdisc' value='$setdisc'>%</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Charge Interest : No <input type='radio' name='chrgint' value='no' $chintn> Yes<input type='radio' name='chrgint' value='yes' $chinty></td>
							<td>Overdue &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$overdues</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>".REQ."Interest Rate </td>
							<td><input type='text' size='7' name='intrate' value='$intrate'>%</td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Account Open Date</td>
							<td><input type='text' size='2' name='oday' maxlength='2' value='$date[2]'>-<input type='text' size='2' name='omon' maxlength='2' value='$date[1]'>-<input type='text' size='4' name='oyear' maxlength='4'  value='$date[0]'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Credit Term $credterms</td>
							<td>Credit Limit <input type='text' size='7' name='credlimit' value='".sprint($credlimit)."'></td>
						</tr>
						<tr class='".bg_class()."'>
							<td>Lead Source</td>
							<td>$select_source</td>
						</tr>
						<tr>
							<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>";

	if (!isset($onthespot)) {
		$enter .= "
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='customers-view.php'>View Customers</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}
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
	$v->isOk ($cusnum, "num", 1, 50, "Invalid customer id.");
	$v->isOk ($deptid, "num", 1, 255, "Invalid Department.");
	$v->isOk ($accno, "string", 1, 20, "Invalid Account number.");
	$v->isOk ($surname, "string", 1, 255, "Invalid surname/company.");
	$v->isOk ($title, "string", 1, 10, "Invalid title.");
	$v->isOk ($init, "string", 0, 10, "Invalid initials.");
	$v->isOk ($loc, "string", 1, 3, "Invalid Type.");
	$v->isOk ($fcid, "num", 1, 30, "Invalid Currency.");
	$v->isOk ($catid, "num", 1, 255, "Invalid Category.");
	$v->isOk ($clasid, "num", 1, 255, "Invalid Classification.");

	if(strtolower($addr) == "ab"){
		$addr = $paddr;
	}
	$v->isOk ($paddr, "string", 1, 255, "Invalid customer postal address.");
	$v->isOk ($addr, "string", 1, 255, "Invalid customer physical address.");
	$v->isOk ($del_addr, "string", 0, 255, "Invalid customer delivery address.");
	$v->isOk ($comments, "string", 0, 5550, "Invalid characters in comment.");
	$v->isOk ($vatnum, "string", 0, 255, "Invalid customer vat number.");
	$v->isOk ($contname, "string", 0, 255, "Invalid contact name.");
	$v->isOk ($bustel, "string", 1, 20, "Invalid Bussines telephone.");
	$v->isOk ($tel, "string", 0, 20, "Invalid Home telephone.");
	$v->isOk ($cellno, "string", 0, 20, "Invalid Cell number.");
	$v->isOk ($fax, "string", 0, 20, "Invalid Fax number.");
	$v->isOk ($email, "email", 0, 255, "Invalid email name.");
	$v->isOk ($url, "url", 0, 255, "Invalid web address.");
	$v->isOk ($traddisc, "float", 0, 20, "Invalid trade discount.");
	$v->isOk ($setdisc, "float", 0, 20, "Invalid settlement discount.");
	$v->isOk ($listid, "num", 1, 20, "Invalid price list.");
	$v->isOk ($chrgint, "string", 1, 4, "Invalid Charge interest option.");
	$v->isOk ($overdue, "float", 0, 20, "Invalid overdue.");
	$v->isOk ($intrate, "float", 1, 20, "Invalid interest rate.");
	$v->isOk ($credterm, "num", 0, 8, "Invalid Credit term.");
	# mix date
	$odate = $oday."-".$omon."-".$oyear;
	$v->isOk ($lead_source, "num", 0, 9, "Invalid lead source selected.");

	if(!checkdate($omon, $oday, $oyear)){
			$v->isOk ($odate, "num", 1, 1, "Invalid account open date.");
	}
	$v->isOk ($credlimit, "float", 0, 40, "Invalid credit limit.");

	// Validate the sales rep
//	if (is_numeric($sales_rep)) {
	if(is_string($sales_rep) AND (strlen($sales_rep) > 0)){
			db_conn("exten");
			$sql = "SELECT salesp FROM salespeople WHERE salesp = '$sales_rep'";
			$sr_rslt = db_exec($sql) or errDie("Unable to retrieve users from Cubit.");

			if (!pg_num_rows($sr_rslt) && $sales_rep) {
					$v->addError(0, "Selected sales rep does not exist in Cubit.");
			} elseif (!$sales_rep) {
				$sr_username = "[None]";
			} else {
				$sr_username = pg_fetch_result($sr_rslt, 0);
			}
	} else {
		$v->addError(0, "Invalid sales rep selection.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return edit($_POST, $confirm);
		exit;
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	db_conn('cubit');
	$Sl = "SELECT * FROM customers WHERE accno='$accno' AND cusnum!='$cusnum' AND div = '".USER_DIV."'";
	$Ri = db_exec($Sl) or errDie("Unablet to get account numbers.");

	if(pg_num_rows($Ri)>0) {
		return edit($_POST, "<li class=err>a Client with this account number already exists</li>");
	}

	// get drop down info
	db_conn("exten");

	# get Category
	$sql = "SELECT * FROM categories WHERE catid = '$catid' AND div = '".USER_DIV."'";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
			$category = "<li class='err'>Category not Found.</li>";
	}else{
		$cat = pg_fetch_array($catRslt);
		$category = $cat['category'];
	}

	# get Classification
	$sql = "SELECT * FROM class WHERE clasid = '$clasid' AND div = '".USER_DIV."'";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		$class = "<li class='err'>Class not Found.</li>";
	}else{
		$clas = pg_fetch_array($clasRslt);
		$class = $clas['classname'];
	}

	# get Price List
	$sql = "SELECT * FROM pricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		$pricelist = "<li class='err'>Class not Found.</li>";
	}else{
		$list = pg_fetch_array($listRslt);
		$plist = $list['listname'];
	}

	# get department
	$sql = "SELECT * FROM departments WHERE deptid = '$deptid' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$deptname = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
		$deptname = $dept['deptname'];
	}

	# Locations drop down
	$locs = array("loc"=>"Local", "int"=>"International");
	$curr = getSymbol($fcid);

	if (isset($onthespot)) {
		$ots_hidden = "<input type='hidden' name='onthespot' value='$onthespot'>";
	} else {
		$ots_hidden = "";
	}

	$confirm = "
		<form action='".SELF."' method=post>
			$ots_hidden
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='cusnum' value='$cusnum'>
			<input type='hidden' name='deptid' value='$deptid'>
			<input type='hidden' name='accno' value='$accno'>
			<input type='hidden' name='surname' value='$surname'>
			<input type='hidden' name='title' value='$title'>
			<input type='hidden' name='init' value='$init'>
			<input type='hidden' name='loc' value='$loc'>
			<input type='hidden' name='location' value='$loc'>
			<input type='hidden' name='fcid' value='$fcid'>
			<input type='hidden' name='catid' value='$catid'>
			<input type='hidden' name='clasid' value='$clasid'>
			<input type='hidden' name='addr' value='$addr'>
			<input type='hidden' name='paddr' value='$paddr'>
			<input type='hidden' name='del_addr' value='$del_addr'>
			<input type='hidden' name='comments' value='$comments'>
			<input type='hidden' name='vatnum' value='$vatnum'>
			<input type='hidden' name='contname' value='$contname'>
			<input type='hidden' name='bustel' value='$bustel'>
			<input type='hidden' name='tel' value='$tel'>
			<input type='hidden' name='cellno' value='$cellno'>
			<input type='hidden' name='fax' value='$fax'>
			<input type='hidden' name='email' value='$email'>
			<input type='hidden' name='url' value='$url'>
			<input type='hidden' name='traddisc' value='$traddisc'>
			<input type='hidden' name='setdisc' value='$setdisc'>
			<input type='hidden' name='listid' value='$listid'>
			<input type='hidden' name='chrgint' value='$chrgint'>
			<input type='hidden' name='overdue' value='$overdue'>
			<input type='hidden' name='credterm' value='$credterm'>
			<input type='hidden' name='intrate' value='$intrate'>
			<input type='hidden' name='odate' value='$odate'>
			<input type='hidden' name='credlimit' value='$credlimit'>
			<input type='hidden' name='oday' value='$oday'>
			<input type='hidden' name='omon' value='$omon'>
			<input type='hidden' name='oyear' value='$oyear'>
			<input type='hidden' name='lead_source' value='$lead_source'>
			<input type='hidden' name='sales_rep' value='$sales_rep'>
		<table cellpadding='0' cellspacing='0'>
			<tr>
				<th colspan='2'>Confirm Edit Customer : Customer Details</th>
			</tr>
			<tr valign='top'><td>
				<table ".TMPL_tblDflts." width='100%'>
				<tr class='".bg_class()."'><td>Department</td><td>$deptname</td></tr>
				<tr class='".bg_class()."'><td>Acc No</td><td>$accno</td></tr>
				<tr class='".bg_class()."'><td>Company/Name</td><td>$surname</td></tr>
				<tr class='".bg_class()."'><td>Title</td><td>$title</td></tr>
				<tr class='".bg_class()."'><td>Initials</td><td>$init</td></tr>
				<tr class='".bg_class()."'><td>Type</td><td>$locs[$loc]</td></tr>
				<tr class='".bg_class()."'><td>Currency</td><td>$curr[symbol] - $curr[name]</td></tr>
				<tr class='".bg_class()."'><td>Category</td><td>$category</td></tr>
				<tr class='".bg_class()."'><td>Classification</td><td>$class</td></tr>
				<tr class='".bg_class()."'><td>Link to Sales rep</td><td>$sr_username</td></tr>
				<tr class='".bg_class()."'><td valign='top'>Postal Address</td><td valign='center'>".nl2br($paddr)."</td></tr>
				<tr class='".bg_class()."'><td valign='top'>Physical Address</td><td valign='center'>".nl2br($addr)."</td></tr>
				<tr class='".bg_class()."'><td valign='top'>Delivery Address</td><td valign='center'>".nl2br($del_addr)."</td></tr>
				<tr class='".bg_class()."'><td valign='top'>Comments</td><td valign='center'>".nl2br($comments)."</td></tr>
				<tr class='".bg_class()."'><td>VAT Number</td><td>$vatnum</td></tr>
				<tr class='".bg_class()."'><td>Contact Name</td><td>$contname</td></tr>
				<tr class='".bg_class()."'><td>Business Tel.</td><td>$bustel</td></tr>
				<tr><td><input type='submit' name='back' value='&laquo; Correction'></td></tr>
				</table>
			</td>
			<td>
				<table ".TMPL_tblDflts." width='100%'>
					<tr class='".bg_class()."'>
						<td>Home Tel.</td>
						<td>$tel</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Cell No.</td>
						<td>$cellno</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Fax No.</td>
						<td>$fax</td>
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
						<td>Trade Discount</td>
						<td>$traddisc%</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Settlement Discount</td>
						<td>$setdisc%</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Price List</td>
						<td>$plist</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Charge Interest</td>
						<td>$chrgint</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Overdue</td>
						<td>$overdue</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Interest Rate</td>
						<td>$intrate%</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Account Open Date</td>
						<td>$odate</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Credit Term</td>
						<td>$credterm</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Credit Limit</td>
						<td>".sprint($credlimit)."</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Lead Source</td>
						<td>".crm_get_leadsrc($lead_source)."</td>
					</tr>
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Write &raquo;'></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	</form>";

	if (!isset($onthespot)) {
		$confirm .= "
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='customers-view.php'>View Customers</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}
	return $confirm;

}



# write new data
function write ($_POST)
{

	if(isset($_POST["back"])) {
		return edit($_POST);
	}

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 50, "Invalid customer id.");
	$v->isOk ($deptid, "num", 1, 255, "Invalid Department.");
	$v->isOk ($accno, "string", 1, 20, "Invalid Account number.");
	$v->isOk ($surname, "string", 0, 255, "Invalid surname/company.");
	$v->isOk ($title, "string", 0, 10, "Invalid title.");
	$v->isOk ($init, "string", 0, 10, "Invalid initials.");
	$v->isOk ($loc, "string", 1, 3, "Invalid Type.");
	$v->isOk ($fcid, "num", 1, 30, "Invalid Currency.");
	$v->isOk ($catid, "num", 1, 255, "Invalid Category.");
	$v->isOk ($clasid, "num", 1, 255, "Invalid Classification.");
	$v->isOk ($paddr, "string", 1, 255, "Invalid customer postal address.");
	$v->isOk ($del_addr, "string", 0, 255, "Invalid customer delivery address.");
	$v->isOk ($addr, "string", 1, 255, "Invalid customer physical address.");
	$v->isOk ($comments, "string", 0, 5550, "Invalid characters in comment.");
	$v->isOk ($vatnum, "string", 0, 255, "Invalid customer vat number.");
	$v->isOk ($contname, "string", 0, 255, "Invalid contact name.");
	$v->isOk ($bustel, "string", 1, 20, "Invalid Bussines telephone.");
	$v->isOk ($tel, "string", 0, 20, "Invalid Home telephone.");
	$v->isOk ($cellno, "string", 0, 20, "Invalid Cell number.");
	$v->isOk ($fax, "string", 0, 20, "Invalid Fax number.");
	$v->isOk ($email, "email", 0, 255, "Invalid email name.");
	$v->isOk ($url, "url", 0, 255, "Invalid web address.");
	$v->isOk ($traddisc, "float", 0, 20, "Invalid trade discount.");
	$v->isOk ($setdisc, "float", 0, 20, "Invalid settlement discount.");
	$v->isOk ($listid, "num", 1, 20, "Invalid price list.");
	$v->isOk ($chrgint, "string", 1, 4, "Invalid Charge interest option.");
	$v->isOk ($overdue, "float", 0, 20, "Invalid overdue.");
	$v->isOk ($intrate, "float", 1, 20, "Invalid interest rate.");
	$v->isOk ($credterm, "num", 0, 20, "Invalid Credit term.");
	$v->isOk ($odate, "date", 1, 14, "Invalid account open date.");
	$v->isOk ($credlimit, "float", 0, 40, "Invalid credit limit.");
	$v->isOk ($lead_source, "num", 0, 9, "Invalid lead source selected.");

	// Validate the sales rep
//	if (is_numeric($sales_rep)) {
	if(is_string($sales_rep) AND (strlen($sales_rep) > 0)){
		db_conn("exten");
		$sql = "SELECT * FROM salespeople WHERE salesp = '$sales_rep'";
		$sr_rslt = db_exec($sql) or errDie("Unable to retrieve users from Cubit.");

		if (!pg_num_rows($sr_rslt) && $sales_rep) {
			$v->addError(0, "The selected sales rep does not exist in Cubit.");
		}
	} else {
		$v->addError(0, "Invalid sales rep selection.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>$e[msg]</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	db_conn('cubit');

	$Sl = "SELECT * FROM customers WHERE accno='$accno' AND cusnum!='$cusnum' AND div = '".USER_DIV."'";
	$Ri = db_exec($Sl) or errDie("Unablet to get account numbers.");

	if(pg_num_rows($Ri)>0) {
		return edit($_POST, "<li class='err'>a Client with this account number already exists</li>");
	}

	$odate = explode("-", $odate);
	$odate = $odate[2]."-".$odate[1]."-".$odate[0];

	// Get drop down info
	db_conn("exten");

	# get Category
	$sql = "SELECT * FROM categories WHERE catid = '$catid' AND div = '".USER_DIV."'";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
			$category = "<li class='err'>Category not Found.</li>";
	}else{
		$cat = pg_fetch_array($catRslt);
		$category = $cat['category'];
	}

	# Get Classification
	$sql = "SELECT * FROM class WHERE clasid = '$clasid' AND div = '".USER_DIV."'";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		$class = "<li class='err'>Class not Found.</li>";
	}else{
		$clas = pg_fetch_array($clasRslt);
		$class = $clas['classname'];
	}

	# Get Price List
	$sql = "SELECT * FROM pricelist WHERE listid = '$listid' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		$pricelist = "<li class='err'>Class not Found.</li>";
	}else{
		$list = pg_fetch_array($listRslt);
		$plist = $list['listname'];
	}

	# get department
	$sql = "SELECT * FROM departments WHERE deptid = '$deptid' AND div = '".USER_DIV."'";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
		$deptname = "<li class='err'>Department not Found.</li>";
	}else{
		$dept = pg_fetch_array($deptRslt);
		$deptname = $dept['deptname'];
	}

	# connect to db
	db_connect();
	$curr = getSymbol($fcid);

	# fix numeric
	$traddisc += 0;
	$setdisc += 0;
	$overdue += 0;
	$credterm += 0;
	$credlimit += 0;

	# write to db
	$sql = "
		UPDATE customers 
		SET deptid = '$deptid', deptname = '$deptname', accno = '$accno', surname = '$surname', 
			title = '$title', init = '$init', category = '$catid', catname = '$category', 
			class = '$clasid', classname = '$class', paddr1 = '$paddr', addr1 = '$addr', 
			del_addr1 = '$del_addr', vatnum = '$vatnum', contname = '$contname', bustel = '$bustel', 
			tel = '$tel', cellno = '$cellno', fax = '$fax', email = '$email', url = '$url', 
			traddisc = '$traddisc', setdisc = '$setdisc', pricelist = '$listid', chrgint = '$chrgint', 
			overdue = '$overdue', intrate = '$intrate', chrgvat = 'yes', credterm = '$credterm', 
			odate = '$odate', credlimit = '$credlimit', location = '$loc', fcid = '$fcid', 
			currency = '$curr[symbol]',lead_source='$lead_source', comments='$comments', 
			sales_rep='$sales_rep' 
		WHERE cusnum  = '$cusnum'";

	$custRslt = db_exec ($sql) or errDie ("Unable to edit customer on the system.", SELF);
	if (pg_cmdtuples ($custRslt) < 1) {
		return "<li class='err'>Unable to Edit customer in database.</li>";
	}

	// update contact in contacts list
	$sql = "UPDATE cons SET surname='$surname', tell='$bustel', cell='$cellno', fax='$fax', email='$email',hadd='$addr', padd='$paddr', del_addr = '$del_addr' WHERE cust_id='$cusnum'";
	$rslt = db_exec($sql) or errDie("Unable to edit customer in contact list", SELF);

	if (isset($onthespot)) {
		onthespot_declare($onthespot);
	}

	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Customer edited</th>
			</tr>
			<tr class='datacell'>
				<td>Customer <b>$surname</b>, has been edited.</td>
			</tr>
		</table>";

	if (!isset($onthespot)) {
		$write .= "
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='customers-view.php'>View Customers</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	}
	return $write;

}



?>