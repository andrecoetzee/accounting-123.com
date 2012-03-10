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
# decide what to do

if (isset($HTTP_GET_VARS['cusnum'])){
	$OUTPUT = view ($HTTP_GET_VARS['cusnum']);
} else {
	$OUTPUT = "<li>Invalid use of module</li>";
}
error_reporting(E_ALL);

# display output
require ("template.php");



function view($cusnum)
{

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 50, "Invalid customer id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}

	# Select
	db_connect();

	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($custRslt) < 1){
		return "<li>Invalid Customer ID.</li>";
	}else{
		$cust = pg_fetch_array($custRslt);
		# get vars
		extract ($cust);
	}

	if (!isset($category))
		$category = "0";
	if (!isset($class))
		$class = "0";
	if (!isset($pricelist))
		$pricelist = "0";
	if (!isset($deptid))
		$deptid = "0";

	db_conn("exten");

	# get Category
	$sql = "SELECT * FROM categories WHERE catid = '$category' AND div = '".USER_DIV."'";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
			$category = "<li class='err'>Category not Found.</li>";
	}else{
		$cat = pg_fetch_array($catRslt);
		$category = $cat['category'];
	}

	# get Classification
	$sql = "SELECT * FROM class WHERE clasid = '$class' AND div = '".USER_DIV."'";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		$class = "<li class='err'>Class not Found.</li>";
	}else{
		$clas = pg_fetch_array($clasRslt);
		$class = $clas['classname'];
	}

	# get Price List
	$sql = "SELECT * FROM pricelist WHERE listid = '$pricelist' AND div = '".USER_DIV."'";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		$plist = "<li class='err'>Class not Found.</li>";
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

	db_conn('cubit');

	$Sl = "SELECT id FROM cons WHERE cust_id='$cusnum'";
	$Ry = db_exec($Sl) or errDie("Unable to get contact from db.");

	$i = 0;
	$conpers = "";

	if(pg_num_rows($Ry) > 0) {

		$cdata = pg_fetch_array($Ry);

		$Sl = "SELECT * FROM conpers WHERE con='$cdata[id]' ORDER BY name";
		$Ry = db_exec($Sl) or errDie("Unable to get contacts from db.");

		if(pg_num_rows($Ry) > 0) {

			$conpers = "
				<h3>Contact Persons</h3>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Name</th>
						<th>Position</th>
						<th>Tel</th>
						<th>Cell</th>
						<th>Fax</th>
						<th>Email</th>
						<th>Notes</th>
						<th colspan='2'>Options</th>
					</tr>";

			while($cp = pg_fetch_array($Ry)) {

				$conpers .= "
					<tr bgcolor='".bgcolorg()."'>
						<td>$cp[name]</td>
						<td>$cp[pos]</td>
						<td>$cp[tell]</td>
						<td>$cp[cell]</td>
						<td>$cp[fax]</td>
						<td>$cp[email]</td>
						<td>$cp[notes]</td>
						<td><a href='conper-edit.php?id=$cp[id]&type=edit'>Edit</a></td>
						<td><a href='conper-rem.php?id=$cp[id]'>Delete</a></td>
					</tr>";
			}

			$conpers .= "</table>";

		}

	}

	// Sales rep
	if ($sales_rep) {
		db_conn("exten");
		$sql = "SELECT salesp FROM salespeople WHERE salespid = '$sales_rep'";
		$sr_rslt = db_exec($sql) or errDie("Unable to retrieve sales rep from Cubit.");
		$sr_username = pg_fetch_result($sr_rslt, 0);
	} else {
		$sr_username = "[None]";
	}

	// Retrieve team name
	$sql = "SELECT name FROM crm.teams WHERE id='$team_id'";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");
	$team_name = pg_fetch_result($team_rslt, 0);

	db_connect ();

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
								</tr>";
				while ($arr = pg_fetch_array ($run_piclist)){
					$display_piclist .= "
						<tr bgcolor='".bgcolorg()."'>
							<td>$arr[image_name]</td>
							<td><a target='iframe1' href='view_image.php?picid=$arr[id]'>View</a></td>
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

	// layout
	$view = "
		<table cellpadding=0 cellspacing=0>
			<tr>
				<th colspan='2'>Customer Details</th>
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
							<td>Surname/Company</td>
							<td>$surname</td>
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
							<td>Category</td>
							<td>$category</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Classification</td>
							<td>$class</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Link to Sales rep</td>
							<td>$sr_username</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign=top>Postal Address</td>
							<td valign=center>".nl2br($paddr1)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign=top>Delivery Address</td>
							<td valign=center>".nl2br($addr1)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign=top>Alternative Delivery Address(1)</td>
							<td valign=center>".nl2br($add1)."</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td valign=top>Alternative Delivery Address(2)</td>
							<td valign=center>".nl2br($add2)."</td>
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
							<td>$team_name</td>
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
							<td>Price List</td>
							<td>$plist</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Charge Interest</td>
							<td>$chrgint</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Overdue</td>
							<td>$overdue</td>
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
							<td>Comments</td>
							<td>".nl2br($comments)."</td>
						</tr>
					</table>
				</td>
				<td>
					<table ".TMPL_tblDflts.">
						$display_iframe
					</table>
					<table ".TMPL_tblDflts.">
						$display_piclist
					</table>
				</td>
			</tr>";

	db_conn("crm");

	$docs_out = "";

	$sql = "SELECT * FROM customer_docs WHERE cusnum = '$cusnum'";
	$run_sql = db_exec($sql) or errDie("Unable to get customer information.");
	if(pg_numrows($run_sql) > 0){
		while ($cdoc_data = pg_fetch_array($run_sql)){

			if (strlen($cdoc_data['filename']) > 0){
				$showdoc = "$cdoc_data[filename]";
			}elseif (strlen($cdoc_data['real_filename']) > 0){
				$showdoc = "$cdoc_data[real_filename]";
			}else {
				$showdoc = "File".$cdoc_data["id"];
			}

			$docs_out .= "
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='cust_doc_get.php?id=$cdoc_data[id]'>$showdoc</a></td>
					<td>".getFileSize($cdoc_data["size"])."</td>
				</tr>";
		}
	}

	$view .= "
		<tr>
			<td>
				<table ".TMPL_tblDflts." width='100%'>
					<tr>
						<th colspan='2'>Documents</th>
					</tr>
					<tr>
						<th>Filename</th>
						<th>Size</th>
					</tr>
					$docs_out
				</table>
			</td>
		</tr>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='customers-view.php'>View Customers</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $view;

}


?>