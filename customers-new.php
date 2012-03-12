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

require ("settings.php");
require_lib ("ext");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
		case "doc_save":
			$OUTPUT = doc_save();
			break;
		default:
			$OUTPUT = enter();
	}
} else {
	$OUTPUT = enter();
}

require ("template.php");



function enter($err = "")
{

	extract($_REQUEST);

	$fields = grp(
		m("deptid", 0),
		m("accno", false),
		m("surname", ""),
		m("title", ""),
		m("location", ""),
		m("fcid", ""),
		m("category", 0),
		m("class", 0),
		m("init", ""),
		m("sales_rep", 0),
		m("paddr1", ""),
		m("addr1", ""),
		m("del_addr1", ""),
		m("comments", ""),
		m("vatnum", ""),
		m("contname", ""),
		m("bustel", ""),
		m("tel", ""),
		m("cellno", ""),
		m("fax", ""),
		m("email", ""),
		m("url", ""),
		m("pricelist", 0),
		m("traddisc", 0),
		m("setdisc", 0),
		m("setdays", 1),
		m("chrgint", 0),
		m("overdue", 0),
		m("intrate", 0),
		m("o_year", date("Y")),
		m("o_month", date("m")),
		m("o_day", date("d")),
		m("credterm", 0),
		m("credlimit", ""),
		m("lead_source", 0),
		m("bankname", ""),
		m("branname", ""),
		m("brancode", ""),
		m("bankaccname", ""),
		m("bankaccno", ""),
		m("bankacctype",""),
		m("team_id", 0),
		m("registration",""),
		m("bankacct",""),
		m("bankid", 0),
		m("blocked", "no")
	);

	if (isset($cusnum)) {

		if($cusnum == "-S")
			return "<li class='err'>Invalid Customer</li><br><input type='button' value='[X] Close' onClick=\"window.close();\">";
		$qry = new dbSelect("customers", "cubit", grp(
			m("where", "cusnum='$cusnum'")
		));
		$qry->run();

		if ($qry->num_rows() <= 0) {
			$OUT = "<li class='err'>Customer not found.</li>";
			return $OUT;
		}

		$c = $qry->fetch_array();
		$qry->free();

		/* split the date into the fields */
		list($c["o_year"], $c["o_month"], $c["o_day"]) = explode("-", $c["odate"]);

		foreach ($fields as $k => $v) {
			if (isset($c[$k])) {
				$fields[$k] = $c[$k];
			}
		}

		$cusid = "<input type='hidden' name='cusnum' value='$cusnum' />";
	} else {
		$cusid = "";
	}

	extract($fields, EXTR_SKIP);

	/* get next available account number */
	if ($accno === false) {
		$lastid = pglib_lastid("cubit.customers", "cusnum");

		$sql = "SELECT accno FROM cubit.customers WHERE cusnum = '$lastid' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		if (pg_numrows($accRslt) < 1) {
			do{
				$lastid--;
				# get last account number
				$sql = "SELECT accno FROM cubit.customers WHERE cusnum = '$lastid' AND div = '".USER_DIV."'";
				$accRslt = db_exec($sql);
				if(pg_numrows($accRslt) < 1){
					$accno = "";
					$naccno= "";
				}else{
					$acc = pg_fetch_array($accRslt);
					$accno = $acc['accno'];
				}
			} while(strlen($accno) < 1 && $lastid > 1);
		}else{
			$acc = pg_fetch_array($accRslt);
			$accno = $acc['accno'];
		}

		if(strlen($accno) > 0){
			$num = preg_replace ("/[^\d]+/", "", $accno);
			$num++;
			$chars = preg_replace("/[\d]/", "", $accno);
			$naccno = $chars.$num;
		} else {
			$naccno = 1;
		}

		$accno = $naccno;
	}

	/* customer categories */
	$qry = qryCategory();
	$cats = db_mksel($qry, "category", $category, "#catid", "#category");

	/* customer class */
	$qry = qryClass();
	$classlist = db_mksel($qry, "class", $class, "#clasid", "#classname");

	/* pricelists */
	$qry = qryPricelist();
	$pricelists = db_mksel($qry, "pricelist", $pricelist, "#listid", "#listname");

	/* customer departments */
	$qry = qryDepartment();
	$depts = db_mksel($qry, "deptid", $deptid, "#deptid", "#deptname");

	/* customer title */
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

	/* credit terms */
	$qry = new dbSelect("ct", "exten", grp(
		m("where", "div='".USER_DIV."'")
	));
	$qry->run();

	while ($cd = $qry->fetch_array()) {
		$days[$cd['days']] = $cd['days'];
	}

	$cred_days = array ("0"=>"0","30"=>"30","60"=>"60");
	$credterms = extlib_cpsel("credterm", $cred_days, $credterm);

	// unset so we can use same array
	unset($days);

	/* overdue periods */
//	$qry = new dbSelect("od", "exten", grp(
//		m("where", "div='".USER_DIV."'")
//	));
//	$qry->run();
//	while ($cd = $qry->fetch_array()) {
//		$days[$cd['days']] = $cd['days'];
//	}
//	$overdues = extlib_cpsel("overdue", $days, $overdue);

	/* customer is local/international */
	$locs = grp(
		m("loc", "Local"),
		m("int", "International")
	);
	$locsel = extlib_cpsel("location", $locs, $location);

	/* currency */
	$qry = qryCurrency();
	$currsel = db_mksel($qry, "fcid", $fcid, "#fcid", "#descrip");

	/* lead sources */
	$select_source = extlib_cpsel("lead_source", crm_get_leadsrc(-1), $lead_source);

	/* something from crm */
	if (isset($_GET["crm"])) {
		$ex = "<input type='hidden' name='crm' value='' />";
	} else {
		$ex = "";
	}

	/* sales rep selection */
	$qry = qrySalesPerson();
	$sales_reps = db_mksel($qry, "sales_rep", $sales_rep, "#salespid", "#salesp", "0:None");

	if (!isset($re)) {
		$re = "not";
	} else {
		$re = remval($re);
	}

	if (isset($cusnum)) {
		$bran = "
			<tr bgcolor='".bgcolorg()."'>
				<td>Branches</td>
				<td><input type='button' onClick=\"window.open('cust-branch-add.php?cusnum=$cusnum','','width=380,height=300,status=1')\" value='Add Branch'><input type=button onClick=\"window.open('cust-branch-view.php?cusnum=$cusnum','','width=500,height=400,status=1')\" value='View Branch'></td>
			</tr>";
	} else {
		$bran = "";
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

	$get_accts = "SELECT * FROM bankacct ORDER BY accname";
	$run_accts = db_exec($get_accts) or errDie ("Unable to get bank account information.");
	if(pg_numrows($run_accts) < 1){
		$bankacct_drop = "";
	}else {
		$bankacct_drop = "<select name='bankid'>";
		while ($barr = pg_fetch_array($run_accts)){
			if($bankid == $barr['bankid']){
				$bankacct_drop .= "<option value='$barr[bankid]' selected>($barr[acctype]) $barr[accname]</option>";
			}else {
				$bankacct_drop .= "<option value='$barr[bankid]'>($barr[acctype]) $barr[accname]</option>";
			}
		}
		$bankacct_drop .= "</select>";
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

//			<option $setdayssel1 value='0'>Last Day Of The Month</option>
	$setdays_drop = "
		<select name='setdays'>
			<option $setdayssel2 value='1'>1st Day Of The Month</option>
			<option $setdayssel3 value='7'>7th Of The Month</option>
			<option $setdayssel4 value='15'>15th Of The Month</option>
			<option $setdayssel5 value='25'>25th Of The Month</option>
		</select>";


//Overdue &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$overdues

	$display_piclist = "";
	$display_iframe = "";
	#check if this cust has any pics ...
	if (isset($cusnum) AND strlen($cusnum) > 0){
		#editing customer ... show frame if we have pics
		$get_pics = "SELECT * FROM display_images WHERE type = 'customer' AND ident_id = '$cusnum' LIMIT 1";
		$run_pics = db_exec($get_pics) or errDie ("Unable to get customer images information.");
		if (pg_numrows($run_pics) < 1){
			#no pics for this customer
			$display_iframe = "";
		}else {

			#compile listing for customer
			$get_piclist = "SELECT * FROM display_images WHERE type = 'customer' AND ident_id = '$cusnum'";
			$run_piclist = db_exec($get_piclist) or errDie ("Unable to get customer images information.");
			if (pg_numrows($run_piclist) < 1){
				#wth .. pic went missing somewhere ...
				#so nothing
			}else {
				$display_piclist = "
					<tr>
						<td colspan='2'>
							<table ".TMPL_tblDflts.">
								<tr>
									<th>Picture Name</th>
									<th>View</th>
									<th>Remove</th>
								</tr>";
				while ($arr = pg_fetch_array ($run_piclist)){
					$display_piclist .= "
								<tr bgcolor='".bgcolorg()."'>
									<td>$arr[image_name]</td>
									<td><a target='iframe1' href='view_image.php?picid=$arr[id]'>View</a></td>
									<td><input type='checkbox' name='rempicid[$arr[id]]' value='yes'></td>
								</tr>";
					#at least 1 picture for this customer
					$display_iframe = "<tr><td colspan='2'><iframe name='iframe1' width='200' height='260' scrolling='false' marginwidth='0' marginheight='0' frameborder='0' src='view_image.php?picid=$arr[id]'></iframe></td></tr>";
				}
				$display_piclist .= "
							</table>
						</td>
					</tr>";
			}
		}
	}

	$show_back_button = "";
	$show_next_button = "";
	$showbuttons = "";
	if (isset($cusnum) AND strlen($cusnum) > 0 AND $cusnum != 0){

		db_connect ();

		#get 1 lower cusnum
		$get_prev = "SELECT cusnum FROM customers WHERE cusnum < '$cusnum' ORDER BY cusnum DESC LIMIT 1";
		$run_prev = db_exec($get_prev) or errDie ("Unable to get previous customer information.");
		if (pg_numrows($run_prev) > 0){
			$back_cusnum = pg_fetch_result ($run_prev,0,0);
			$show_back_button = "<input type='button' onClick=\"document.location='customers-new.php?cusnum=$back_cusnum';\" value='View Previous Customer'>";
		}

		$get_next = "SELECT cusnum FROM customers WHERE cusnum > '$cusnum' ORDER BY cusnum ASC LIMIT 1";
		$run_next = db_exec($get_next) or errDie ("Unable to get next customer information.");
		if (pg_numrows($run_next) > 0){
			$next_cusnum = pg_fetch_result ($run_next,0,0);
			$show_next_button = "<input type='button' onClick=\"document.location='customers-new.php?cusnum=$next_cusnum';\" value='View Next Customer'>";
		}

		$showbuttons = "$show_back_button $show_next_button <br><br>";
		$showcontact = "<input type='button' onClick=\"popupSized('conper-add.php?type=cust&id=$cusnum','Add Additional Contact','880','580')\" value='Add Additional Contact'>";
	}

//http://127.0.0.1/cubit3.33/conper-add.php?type=cust&id=2

	// Layout
	$OUT = "
		$showbuttons
		<form action='".SELF."' method='POST'>
			$err
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='re' value='$re' />
			<input type='hidden' name='blocked' value='$blocked' />
			$ex
			$cusid
			".onthespot_passon()."
		<table cellpadding='0' cellspacing='0'>
			<tr>
				<th colspan='2'>Add Customer : Customer Details</th>
			</tr>
			<tr valign='top'>
				<td>
				<table ".TMPL_tblDflts." width='100%'>
					<tr bgcolor='".bgcolorg()."'>
						<td>".REQ."Department</td>
						<td>$depts</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>".REQ."Acc No</td>
						<td><input type='text' size='20' name='accno' value='$accno' /></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>".REQ."Company/Name</td>
						<td><input type='text' size='20' name='surname' value='$surname' /></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>".REQ."Registration/ID</td>
						<td><input type='text' size='20' name='registration' value='$registration'></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>".REQ."Title $titles</td>
						<td>Initials <input type='text' size='15' name='init' value='$init' /></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>".REQ."Type</td>
						<td>$locsel</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>".REQ."Currency</td>
						<td>$currsel</td>
					</tr>
					<tr bgcolor='".bgcolorg()."' ".ass("Categories are used to group customers. For example: PTA,JHB,CT").">
						<td>".REQ."Category</td>
						<td>$cats</td>
					</tr>
					<tr bgcolor='".bgcolorg()."' ".ass("Classifications are used to group customers. For example: Wholesale,Retail").">
						<td>".REQ."Classification</td>
						<td>$classlist</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Link to Sales rep</td>
						<td>$sales_reps</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td valign='top'>".REQ." Postal Address</td>
						<td valign='center'><textarea rows='4' cols='19' name='paddr1'>$paddr1</textarea></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td valign='top'>
							".REQ." Physical Address<br>
							<font size='-2'>
								<input style='width: 11px; height: 11px;' type='checkbox' name='addr_same' ".(isset($addr_same)?"checked='t'":"")." />
								Same As Postal Address
							</font>
						</td>
						<td valign='center'><textarea rows='4' cols='19' name='addr1'>$addr1</textarea></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td valign='top'>Delivery Address</td>
						<td valign='center'><textarea rows='4' cols='19' name='del_addr1'>$del_addr1</textarea></td>
					</tr>
					$bran
					<tr bgcolor='".bgcolorg()."'>
						<td>Team Permissions</td>
						<td>$team_sel</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td valign='top'>Comments</td>
						<td valign='center'><textarea rows='4' cols='19' name='comments'>$comments</textarea></td>
					</tr>
					<tr><td><br></td></tr>
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
						<td>Account Number</td>
						<td><input type='text' size='20' name='bankaccno' value='$bankaccno'></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Account Type</td>
						<td><input type='text' size='20' name='bankacctype' value='$bankacctype'></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Bank Account To Use</td>
						<td>$bankacct_drop</td>
					</tr>
					<tr><td><br></td></tr>
				</table>
				</td>
				<td>
				<table ".TMPL_tblDflts." width='100%'>
					<tr bgcolor='".bgcolorg()."'>
						<td>".REQ."VAT Number</td>
						<td><input type='text' size='21' name='vatnum' value='$vatnum' /></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>".REQ."Business Tel.</td>
						<td><input type='text' size='21' name='bustel' value='$bustel' /></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Contact Name</td>
						<td>
							<input type='text' size='21' name='contname' value='$contname' />
							$showcontact
						</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Home Tel.</td>
						<td><input type='text' size='21' name='tel' value='$tel' /></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Cell No.</td>
						<td><input type='text' size='21' name='cellno' value='$cellno' /></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Fax No.</td>
						<td><input type='text' size='21' name='fax' value='$fax' /></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>E-mail</td>
						<td><input type='text' size='21' name='email' value='$email' /></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Web Address</td>
						<td>http://<input type='text' size='30' name='url' value='$url' /></td>
					</tr>
					<tr bgcolor='".bgcolorg()."' ".ass("When invoicing prices comes from the pricelist. Add more at stock settings.").">
						<td>".REQ."Price List</td>
						<td>$pricelists</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td ".ass("This is the default discount on invoices, but can be changed per invoice").">Trade Discount &nbsp;<input type='text' size='6' name='traddisc' value='$traddisc' />%</td>
						<td>Settlement Discount <input type='text' size='7' name='setdisc' value='$setdisc' />%</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>".REQ."Charge Interest : Yes <input type='radio' name='chrgint' value='yes' ".($chrgint=="yes"?"checked='t'":"")." /> No<input type='radio' name='chrgint' value='no' ".($chrgint!="yes"?"checked='t'":"")." /></td>
						<td>Statement Day $setdays_drop</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td ".ass("Depending on interest settings, invoices older than this will get interest.").">Interest Rate <input type='text' size='7' name='intrate' value='$intrate' />%</td>
						<td></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Account Open Date</td>
						<td>".mkDateSelect("o", $o_year, $o_month, $o_day)."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Default Credit Term &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$credterms</td>
						<td>Credit Limit <input type='text' size='7' name='credlimit' value='$credlimit'/></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Lead Source</td>
						<td>$select_source</td>
					</tr>
					<tr><td><br></td></tr>
					$display_iframe
					$display_piclist
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;' /></td>
					</tr>
				</table>
				</form>
				</td>
			</tr>
			<tr>
				<td align='center'>
					</table>"
		.mkQuickLinks(
			ql("customers-view.php", "View Customers")
		);
	return $OUT;

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
				INSERT INTO crm.ctmp_docs (
					filename, type, size, file, session, real_filename
				) VALUES (
					'$doc_filename', '$file_type', '$file_size', '$file', '$session', '$file_name'
				)";
			db_exec($sql) or errDie("Unable to update customer documents.");
		}
	}

	if (isset($rem)) {
		foreach ($rem as $id => $value) {
			$sql = "DELETE FROM crm.ctmp_docs WHERE id='$id'";
			db_exec($sql) or errDie("Unable to remove entry.");
		}
	}

	if (isset($oldrem)) {
		foreach ($oldrem as $id => $value) {
			$sql2 = "DELETE FROM crm.customer_docs WHERE id = '$id'";
			$run_sql = db_exec($sql2) or errDie ("Unable to remove customer information");
		}
	}

	return confirm();

}



function confirm()
{

	extract($_POST);

	if ($err = validate($_POST)) {
		return enter($err);
	}

	if (isset($addr_same)) {
		$addr1 = $paddr1;
	}

	if (!isset($cusnum) && $key != "doc_save") {
		$qry = new dbSelect("customers", "cubit", grp(
			m("where", "accno='$accno' AND div='".USER_DIV."'")
		));
		$qry->run();

		if ($qry->num_rows() > 0) {
			return enter("<li class='err'>A Customer/Client with this account number already exists.</li>");
		}
	}

	$data = qryCategory($category);
	$catname = $data["category"];

	$data = qryClass($class);
	$classname = $data["classname"];

	$data = qryPricelist($pricelist);
	$plist = $data["listname"];

	$data = qryDepartment($deptid);
	$deptname = $data["deptname"];

	if ($sales_rep == "0") {
		$salesperson = "None";
	} else {
		$data = qrySalesPerson($sales_rep);
		$salesperson = $data["salesp"];
	}

	/* customer is local/international */
	$locs = grp(
		m("loc", "Local"),
		m("int", "International")
	);

	$curr = getSymbol($fcid);

	if (isset($crm)) {
		$ex = "<input type='hidden' name='crm' value='' />";
	} else {
		$ex = "";
	}

	if (isset($cusnum)) {
		$cusid = "<input type='hidden' name='cusnum' value='$cusnum' />";
	} else {
		$cusid = "";
	}

	$odate = mkdate($o_year, $o_month, $o_day);

	// Retrieve teams
	$sql = "SELECT * FROM crm.teams WHERE id='$team_id'";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve team.");
	$team_data = pg_fetch_array($team_rslt);

	$get_acct = "SELECT * FROM bankacct WHERE bankid = '$bankid' LIMIT 1";
	$run_acct = db_exec($get_acct) or errDie ("Unable to get bank account information.");
	if(pg_numrows($run_acct) < 1){
		$showbankacct = "";
	}else {
		$bankacctarr = pg_fetch_array($run_acct);
		$showbankacct = "($bankacctarr[acctype]) $bankacctarr[accname]";
	}

//			<input type='hidden' name='overdue' value='$overdue' />

	if (!isset($rempicid) OR !is_array ($rempicid)){
		$send_rempic = "<input type='hidden' name='rempicid[0]' value=''>";
	}else {
		$send_rempic = "";
		foreach ($rempicid AS $each => $own){
			$send_rempic .= "<input type='hidden' name='rempicid[$each]' value='$own'>";
		}
	}

	$hidden = 
			onthespot_passon()."
			<input type='hidden' name='deptid' value='$deptid' />
			<input type='hidden' name='accno' value='$accno' />
			<input type='hidden' name='surname' value='$surname' />
			<input type='hidden' name='title' value='$title' />
			<input type='hidden' name='init' value='$init' />
			<input type='hidden' name='location' value='$location' />
			<input type='hidden' name='fcid' value='$fcid' />
			<input type='hidden' name='category' value='$category' />
			<input type='hidden' name='class' value='$class' />
			<input type='hidden' name='addr1' value='$addr1' />
			<input type='hidden' name='paddr1' value='$paddr1' />
			<input type='hidden' name='del_addr1' value='$del_addr1' />
			<input type='hidden' name='vatnum' value='$vatnum' />
			<input type='hidden' name='contname' value='$contname' />
			<input type='hidden' name='bustel' value='$bustel' />
			<input type='hidden' name='tel' value='$tel' />
			<input type='hidden' name='cellno' value='$cellno' />
			<input type='hidden' name='fax' value='$fax' />
			<input type='hidden' name='email' value='$email' />
			<input type='hidden' name='url' value='$url' />
			<input type='hidden' name='traddisc' value='$traddisc' />
			<input type='hidden' name='setdisc' value='$setdisc' />
			<input type='hidden' name='setdays' value='$setdays' />
			<input type='hidden' name='pricelist' value='$pricelist' />
			<input type='hidden' name='chrgint' value='$chrgint' />

			<input type='hidden' name='intrate' value='$intrate' />
			<input type='hidden' name='credterm' value='$credterm' />
			<input type='hidden' name='odate' value='$odate' />
			<input type='hidden' name='credlimit' value='$credlimit' />
			<input type='hidden' name='deptname' value='$deptname' />
			<input type='hidden' name='o_day' value='$o_day' />
			<input type='hidden' name='o_month' value='$o_month' />
			<input type='hidden' name='o_year' value='$o_year' />
			<input type='hidden' name='lead_source' value='$lead_source' />
			<input type='hidden' name='comments' value='$comments' />
			<input type='hidden' name='sales_rep' value='$sales_rep' />
			<input type='hidden' name='re' value='$re' />
			<input type='hidden' name='bankname' value='$bankname' />
			<input type='hidden' name='branname' value='$branname' />
			<input type='hidden' name='brancode' value='$brancode' />
			<input type='hidden' name='bankaccname' value='$bankaccname' />
			<input type='hidden' name='bankaccno' value='$bankaccno' />
			<input type='hidden' name='bankacctype' value='$bankacctype' />
			<input type='hidden' name='team_id' value='$team_id' />
			<input type='hidden' name='registration' value='$registration' />
			<input type='hidden' name='bankid' value='$bankid' />
			
			<input type='hidden' name='blocked' value='$blocked' />

			$send_rempic";


//					<tr bgcolor='".bgcolorg()."'>
//						<td>Overdue</td>
//						<td>$overdue</td>
//					</tr>


	$OUT = "
		<form action='".SELF."' method='POST' enctype='multipart/form-data'>
		<input type='hidden' name='key' value='write' />
			$hidden
			$ex
			$cusid
		<table cellpadding='0' cellspacing='0'>
		<tr>
			<th colspan='2'>Confirm Customer : Customer Details</th>
		</tr>
		<tr valign='top'>
			<td>
				<table ".TMPL_tblDflts." width='100%'>
					<tr bgcolor='".bgcolorg()."'>
						<td>Department</td>
						<td>$deptname</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Acc No</td>
						<td>$accno</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Company/Name</td>
						<td>$surname</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Registration/ID</td>
						<td>$registration</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Title</td>
						<td>$title</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Initials</td>
						<td>$init</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Type</td>
						<td>$locs[$location]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Currency</td>
						<td>$curr[symbol] - $curr[name]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Category</td>
						<td>$catname</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Classification</td>
						<td>$classname</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Link to Sales rep</td>
						<td>$salesperson</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td valign='top'>Postal Address</td>
						<td valign='center'>".nl2br($paddr1)."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td valign='top'>Physical Address</td>
						<td valign='center'>".nl2br($addr1)."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td valign='top'>Delivery Address</td>
						<td valign='center'>".nl2br($del_addr1)."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>VAT Number</td>
						<td>$vatnum</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Contact Name</td>
						<td>$contname</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Business Tel.</td>
						<td>$bustel</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Team Permissions</td>
						<td>$team_data[name]</td>
					</tr>
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
						<td>Account Number</td>
						<td>$bankaccno</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Account Type</td>
						<td>$bankacctype</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Bank Account To Use</td>
						<td>$showbankacct</td>
					</tr>
					<tr>
						<td><input type='submit' name='back' value='&laquo; Correction' /></td>
					</tr>
				</table>
			</td>
			<td>
				<table ".TMPL_tblDflts." width='100%'>
					<tr bgcolor='".bgcolorg()."'>
						<td>Home Tel.</td>
						<td>$tel</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Cell No.</td>
						<td>$cellno</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Fax No.</td>
						<td>$fax</td>
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
						<td>Trade Discount</td>
						<td>$traddisc%</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Settlement Discount</td>
						<td>$setdisc%</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Settlement Days</td>
						<td>$setdays</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Price List</td>
						<td>$plist</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Charge Interest</td>
						<td>$chrgint</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Interest Rate</td>
						<td>$intrate%</td>
					</tr>

					<tr bgcolor='".bgcolorg()."'>
						<td>Account Open Date</td>
						<td>$odate</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Credit Term</td>
						<td>$credterm</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Credit Limit</td>
						<td>$credlimit</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Lead Source</td>
						<td>".crm_get_leadsrc($lead_source)."</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Comments</td>
						<td>".nl2br($comments)."</td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<th colspan='2'>Add A New Picture</th>
					</tr>
					<tr>
						<th>Picture Name</th>
						<th>File Location</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><input type='text' name='picupload_name'></td>
						<td><input type='file' name='picupload_image'></td>
						<td></td>
					</tr>
					<tr><td><br></td></tr>
					<tr>
						<td>&nbsp;</td>
						<td align='right'><input type='submit' value='Write &raquo;' /></td>
					</tr>
				</table>
				</form>";
					// Retrieve documents added already
					if (isset($cusnum) && !empty($cusnum)) {
						$sql = "
							SELECT id,file,type,filename,size,real_filename,'customer_docs' AS table 
								FROM crm.customer_docs
								WHERE cusnum='$cusnum' 
							UNION 
							SELECT id,file,type,filename,size,real_filename,'ctmp_docs' AS table 
								FROM crm.ctmp_docs
								WHERE session='$_REQUEST[CUBIT_SESSION]'";
					} else {
						$sql = "
							SELECT * FROM crm.ctmp_docs
								WHERE session='$_REQUEST[CUBIT_SESSION]' 
								ORDER BY id DESC";
					}
					$cdoc_rslt = db_exec($sql) or errDie("Unable to retrieve docs.");

					$cdoc_out = "";
					while ($cdoc_data = pg_fetch_array($cdoc_rslt)) {

						if (strlen($cdoc_data['filename']) > 0){
							$showdoc = "$cdoc_data[filename]";
						}elseif (strlen($cdoc_data['real_filename']) > 0){
							$showdoc = "$cdoc_data[real_filename]";
						}else {
							$showdoc = "File".$cdoc_data["id"];
						}

						if ($cdoc_data['table'] == "customer_docs"){
							$cdoc_out .= "
								<tr bgcolor='".bgcolorg()."'>
									<td><a href='cust_doc_get.php?id=$cdoc_data[id]&tmp=1&table=$cdoc_data[table]'>$showdoc</a></td>
									<td>".getFileSize($cdoc_data["size"])."</td>
									<td><input type='checkbox' name='oldrem[$cdoc_data[id]]' value='$cdoc_data[id]' /></td>
								</tr>";
						}else {
							$cdoc_out .= "
								<tr bgcolor='".bgcolorg()."'>
									<td><a href='cust_doc_get.php?id=$cdoc_data[id]&tmp=1&table=$cdoc_data[table]'>$showdoc</a></td>
									<td>".getFileSize($cdoc_data["size"])."</td>
									<td><input type='checkbox' name='rem[$cdoc_data[id]]' value='$cdoc_data[id]' /></td>
								</tr>";
						}
					}

					if (empty($cdoc_out)) {
						$cdoc_out .= "
							<tr bgcolor='".bgcolorg()."'>
								<td colspan='3'><li>No documents added</li></td>
							</tr>";
					}

					$OUT .= "
						<form method='post' action='".SELF."' enctype='multipart/form-data'>
							<input type='hidden' name='key' value='doc_save' />
							$hidden
							$cusid
						<table ".TMPL_tblDflts.">
							<tr>
								<th colspan='3'>Documents</th>
							</tr>
							<tr>
								<th>Description</th>
								<th>Upload</th>
								<th>&nbsp;</th>
							</tr>
							<tr bgcolor='".bgcolorg()."'>
								<td><input type='text' name='doc_filename'></td>
								<td><input type='file' name='doc_file'></td>
								<td colspan='2' align='center'><input type='submit' value='Update'></td>
							</tr>
							<tr>
								<th>Description</th>
								<th>Size</th>
								<th>Remove</th>
							</tr>
							$cdoc_out
						</table>
					</td>
				</tr>
			</table>";
		mkQuickLinks(
			ql("customers-view.php", "View Customers")
		);
	return $OUT;

}



function write()
{

	global $_FILES;

	extract($_POST);

	if (isset($back)) {
		return enter();
	}

	if ($err = validate($_POST)) {
		return enter($err);
	}

	/* check account number */
	if (!isset($cusnum)) {
		$qry = new dbSelect("customers", "cubit", grp(
			m("where", "accno='$accno' AND div='".USER_DIV."'")
		));
		$qry->run();

		if ($qry->num_rows() > 0) {
			return enter("<li class='err'>A Customer/Client with this account number already exists.</li>");
		}
	}

	$data = qryCategory($category);
	$catname = $data["category"];

	$data = qryClass($class);
	$classname = $data["classname"];

	$data = qryPricelist($pricelist);
	$plist = $data["listname"];

	$curr = getSymbol($fcid);
	$currency = $curr["symbol"];

	/* fix numerics */
	$traddisc += 0;
	$setdisc += 0;
	$pricelist += 0;
//	$overdue += 0;
	$credterm += 0;
	$credlimit += 0;

	pglib_transaction("BEGIN");

	/* insert into database / update */
	$cols = grp(
		m("deptid", $deptid),
		m("accno", $accno),
		m("surname", $surname),
		m("title", $title),
		m("init", $init),
		m("location", $location),
		m("fcid", $fcid),
		m("currency", $currency),
		m("category", $category),
		m("class", $class),
		m("addr1", $addr1),
		m("paddr1", $paddr1),
		m("del_addr1", $del_addr1),
		m("vatnum", $vatnum),
		m("contname", $contname),
		m("bustel", $bustel),
		m("tel", $tel),
		m("cellno", $cellno),
		m("fax", $fax),
		m("email", $email),
		m("url", $url),
		m("traddisc", $traddisc),
		m("setdisc", $setdisc),
		m("setdays", $setdays),
		m("pricelist", $pricelist),
		m("chrgint", $chrgint),
//		m("overdue", $overdue),
		m("overdue", 0),
		m("intrate", $intrate),
		m("chrgvat", "yes"),
		m("credterm", $credterm),
		m("odate", $odate),
		m("credlimit", $credlimit),
		m("blocked", $blocked),
		m("deptname", $deptname),
		m("classname", $classname),
		m("catname", $catname),
		m("lead_source", $lead_source),
		m("comments", $comments),
		m("sales_rep", $sales_rep),
		m("div", USER_DIV),
		m("bankid", $bankid),
		m("bankname", $bankname),
		m("branname", $branname),
		m("brancode", $brancode),
		m("bankaccname", $bankaccname),
		m("bankaccno", $bankaccno),
		m("bankacctype", $bankacctype),
		m("team_id", $team_id),
		m("registration", $registration)

	);

	$where = wgrp(
		m("cusnum", isset($cusnum) ? $cusnum : 0)
	);

	$qryi = new dbUpdate("customers", "cubit", $cols, $where);
	$qryi->run(DB_REPLACE);

	/* get id */
	if (!isset($cusnum)) {
		$existing = FALSE;
		$cusnum = pglib_lastid("customers", "cusnum");
		$newcust = true; // used later to check if we should create the ledgers
	}else {
		$existing = TRUE;
	}

	/* add to/update contact list */
	$cols = grp(
		m("surname", $surname),
		m("title", $title),
		m("ref", "Customer"),
		m("tell", $tel),
		m("tell_office", $bustel),
		m("cell", $cellno),
		m("fax", $fax),
		m("email", $email),
		m("hadd", $addr1),
		m("padd", $paddr1),
		m("del_addr", $del_addr1),
		m("date", $odate),
		m("cust_id", $cusnum),
		m("con", "No"),
		m("lead_source", $lead_source),
		m("description", $comments),
		m("account_type", "Customer"),
		m("accountname", $surname),
		m("account_id", $cusnum),
		m("by", USER_NAME),
		m("div", USER_DIV)
	);

	$where = wgrp(
		m("cust_id", $cusnum)
	);

	$qryi->setTable("cons", "cubit");
	$qryi->setOpt($cols, $where);

	$qryi->run(DB_REPLACE);
	
	if (PRD_STATE == "py") {
		$audit_db = YR_NAME . "_audit";
		$actyear = PYR_NAME;
	} else {
		$audit_db = "audit";
		$actyear = YR_NAME;
	}

	if (isset($newcust)) {
		/* create customer ledgers */
		for ($i = 1; $i <= 12; ++$i) {
			/* period customer ledger */
			$cols = grp(
				m("cusnum", $cusnum),
				m("contra", 0),
				m("edate", $odate),
				m("sdate", raw("CURRENT_DATE")),
				m("eref", 0),
				m("descript", "Balance"),
				m("credit", 0),
				m("debit", 0),
				m("cbalance", 0),
				m("dbalance", 0),
				m("div", USER_DIV)
			);

			$qryi->setTable("custledger", "$i");
			$qryi->setOpt($cols);
			$qryi->run(DB_INSERT);
			
			/* audit customer ledger */
			$cols = grp(
				m("cusnum", $cusnum),
				m("contra", 0),
				m("edate", $odate),
				m("sdate", raw("CURRENT_DATE")),
				m("eref", 0),
				m("descript", "Balance"),
				m("credit", 0),
				m("debit", 0),
				m("cbalance", 0),
				m("dbalance", 0),
				m("div", USER_DIV),
				m("actyear", $actyear)
			);

			$qryi->setTable(getMonthName($i)."_custledger", $audit_db);
			$qryi->setOpt($cols);
			$qryi->run(DB_INSERT);
		}

		if (isset($crm)) {
			header("Location: crm/tokens-new.php?value=$surname");
			exit;
		}

		if ($re != "not") {
			$qryi->setTable("invoices", "cubit");

			$cols = grp(
				m("cusnum", $cusnum)
			);

			$where = wgrp(
				m("invid", $re)
			);

			$qryi->setOpt($cols, $where);
			$qryi->run(DB_UPDATE);

			header("Location: cust-credit-stockinv.php?invid=$re&cont=1&letters=");
			exit;
		}
	}

	#check if we are uploading a new picture
	if (is_uploaded_file ($_FILES["picupload_image"]["tmp_name"])) {
		# Check file ext
		if (preg_match ("/(image\/jpeg|image\/png|image\/gif)/", $_FILES["picupload_image"]["type"], $extension)) {
			$type = $_FILES["picupload_image"]["type"];
			$fname = $_FILES["picupload_image"]["name"];

			// open file in "read, binary" mode
			$img = "";
			$file = fopen ($_FILES['picupload_image']['tmp_name'], "rb");
			while (!feof ($file)) {
				// fread is binary safe
				$img .= fread ($file, 1024);
			}
			fclose ($file);
			# base 64 encoding
			$img = base64_encode($img);

			db_connect();

			$sql = "
				INSERT INTO display_images (
					type, image_name, image_data, image_type, image_filename, ident_id
				) VALUES (
					'customer','$picupload_name','$img','$type', '$fname', '$cusnum'
				)";
			$run_sql = db_exec($sql);

		}
	}


	if (!isset($rempicid) OR !is_array ($rempicid))
		$rempicid = array ();

	foreach ($rempicid AS $each => $own){
		$rem_sql = "DELETE FROM display_images WHERE id = '$each'";
		$run_rem = db_exec($rem_sql) or errDie ("Unable to remove customer image information.");
	}



	pglib_transaction("COMMIT") or errDie("Unable to add customer to database. (TC)");

	// Update documents
	$sql = "SELECT * FROM crm.ctmp_docs WHERE session='$_REQUEST[CUBIT_SESSION]'";
	$ctdoc_rslt = db_exec($sql) or errDie("Unable to retrieve docs.");

	while ($ctdoc_data = pg_fetch_array($ctdoc_rslt)) {
		$sql = "
			INSERT INTO crm.customer_docs (
				cusnum, file, type, filename, size, real_filename
			) VALUES (
				'$cusnum', '$ctdoc_data[file]', '$ctdoc_data[type]', '$ctdoc_data[filename]', '$ctdoc_data[size]', '$ctdoc_data[real_filename]'
			)";
		db_exec($sql) or errDie("Unable to save files to customer.");

		$sql = "DELETE FROM crm.ctmp_docs WHERE id='$ctdoc_data[id]'";
		db_exec($sql) or errDie("Unable to remove tmp file.");
	}

	$sql = "SELECT * FROM cubit.cons WHERE cust_id='$cusnum'";
	$con_rslt = db_exec($sql) or errDie("Unable to retrieve contact.");
	$con_data = pg_fetch_array($con_rslt);

	if (pg_num_rows($con_rslt)) {
		$con_out = "<a href='javascript:popupOpen(\"groupware/conper-add.php?type=conn&id=$con_data[id]\")'>Add Sub Contact</a>";
	} else {
		$con_out = "";
	}
	
	onthespot_declare();

	$OUT = "
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Customer add/update successful.</th>
			</tr>
			<tr class='datacell'>
				<td>
					New Customer <b>$surname</b>,
					has been successfully added to the system.
					$con_out</td>
			</tr>
		</table>";

	$OUT .= onthespot_out(
		mkQuickLinks(
			ql("customers-new.php", "Add Customers"),
			ql("customers-view.php", "View Customers")
		)
	);
//	return $OUT;

	if ($existing){
		header ("Location: customers-view.php");
		die;
	}else {
		$_REQUEST = array ();
		return enter ("<li class='yay'>Customer add/update successful. $con_out</li><br>");
	}




}




function validate($AR)
{

	extract($AR);

	require_lib("validate");
	$v = new validate ();
	$odate = mkdate($o_year, $o_month, $o_day);
	$v->isOk($deptid, "num", 1, 255, "Invalid Department.");
	$v->isOk($accno, "string", 1, 20, "Invalid Account number.");
	$v->isOk($surname, "string", 0, 255, "Invalid surname/company.");
	$v->isOk($title, "string", 0, 10, "Invalid title.");
	$v->isOk($init, "string", 0, 10, "Invalid initials.");
	$v->isOk($location, "string", 1, 3, "Invalid Type.");
	$v->isOk($fcid, "num", 1, 30, "Invalid Currency.");
	$v->isOk($category, "num", 1, 255, "Invalid Category.");
	$v->isOk($class, "num", 1, 255, "Invalid Classification.");
	$v->isOk($paddr1, "string", 1, 255, "Invalid customer postal address.");

	if (!isset($addr_same)) {
		$v->isOk($addr1, "string", 1, 255, "Invalid customer physical address.");
	}

	$v->isOk($del_addr1, "string", 0, 255, "Invalid customer delivery address.");
	$v->isOk($comments, "string", 0, 255, "Invalid characters in comment.");
	$v->isOk($vatnum, "string", 1, 255, "Invalid customer vat number.");
	$v->isOk($registration, "string", 1, 255, "Invalid registration/id number.");
	$v->isOk($contname, "string", 0, 255, "Invalid contact name.");
	$v->isOk($bustel, "string", 1, 20, "Invalid Bussines telephone.");
	$v->isOk($tel, "string", 0, 20, "Invalid Home telephone.");
	$v->isOk($cellno, "string", 0, 20, "Invalid Cell number.");
	$v->isOk($fax, "string", 0, 20, "Invalid Fax number.");
	$v->isOk($email, "email", 0, 255, "Invalid email address.");
	$v->isOk($url, "url", 0, 255, "Invalid web address.");
	$v->isOk($traddisc, "float", 0, 20, "Invalid trade discount.");
	$v->isOk($setdisc, "float", 0, 20, "Invalid settlement discount.");
	$v->isOk($setdays, "numeric", 0, 10, "Invalid settlement days.");
	$v->isOk($pricelist, "num", 1, 20, "Invalid price list.");
	$v->isOk($chrgint, "string", 1, 4, "Invalid Charge interest option.");
//	$v->isOk($overdue, "float", 0, 20, "Invalid overdue.");
	$v->isOk($intrate, "float", 1, 20, "Invalid interest rate.");
	$v->isOk($credterm, "num", 0, 20, "Invalid Credit term.");
	$v->isOk($odate, "date", 1, 14, "Invalid account open date.");
	$v->isOk($credlimit, "float", 0, 11, "Invalid credit limit.");
	/* CRM CODE */
	$v->isOk($lead_source, "num", 0, 9, "Invalid lead source selected.");
	$v->isOk($bankname, "string", 0, 20, "Invalid Bank Name.");
	$v->isOk($branname, "string", 0, 20, "Invalid Branch Name.");
	$v->isOk($brancode, "string", 0, 20, "Invalid Branch Code.");
	$v->isOk($bankaccname, "string", 0, 50, "Invalid Bank Account Name.");
	$v->isOk($bankaccno, "string", 0, 50, "Invalid Bank Account Number.");
	$v->isOk($bankacctype, "string", 0, 50, "Invalid Bank Account Type.");

	if (is_string($sales_rep) AND (strlen($sales_rep) > 0)) {
		$qry = qrySalesPerson($sales_rep);

		if ($qry === false) {
			$v->addError(0, "The selected sales rep does not exist in Cubit.");
		}
	} else {
		$v->addError(0, "Invalid sales rep selection.");
	}

	if ($v->isError()) {
		return $v->genErrors();
	} else {
		return false;
	}
}


?>
