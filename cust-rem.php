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
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "write":
        	$OUTPUT = write($_POST);
			break;
		default:
			if (isset($_GET['cusnum'])){
				$OUTPUT = rem ($_GET['cusnum']);
			} else {
				$OUTPUT = "<li> - Invalid use of module.</li>";
			}
	}
} else {
	if (isset($_GET['cusnum'])){
		$OUTPUT = rem ($_GET['cusnum']);
	} else {
		$OUTPUT = "<li> - Invalid use of module.</li>";
	}
}

# display output
require ("template.php");




function rem($cusnum)
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
			$confirm .= "<li class='err'>-".$e["msg"]."<li>";
		}
		return $confirm;
	}

	# Select
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND balance=0 AND div = '".USER_DIV."'";
	$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($custRslt) < 1){
			return "<li> Invalid Customer ID.</li>";
	}else{
			$cust = pg_fetch_array($custRslt);
			# get vars
			extract ($cust);
	}

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

	$rem = "
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='write'>
					<input type='hidden' name='cusnum' value='$cusnum'>
				<table cellpadding=0 cellspacing=0>
					<tr>
						<th colspan='2'>Confirm Remove Customer : Customer Details</th>
					</tr>
					<tr valign='top'>
						<td>
							<table ".TMPL_tblDflts." width='100%'>
								<tr class='".bg_class()."'>
									<td>Department</td>
									<td>$deptname</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Acc No</td>
									<td>$accno</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Company/Name</td>
									<td>$surname</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Title</td>
									<td>$title</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Initials</td>
									<td>$init</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Category</td>
									<td>$category</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Classification</td>
									<td>$class</td>
								</tr>
								<tr class='".bg_class()."'>
									<td valign='top'>Postal Address</td>
									<td valign='center'>".nl2br($paddr1)."</td>
								</tr>
								<tr class='".bg_class()."'>
									<td valign='top'>Delivery Address</td>
									<td valign='center'>".nl2br($addr1)."</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>VAT Number</td>
									<td>$vatnum</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Contact Name</td>
									<td>$contname</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Business Tel.</td>
									<td>$bustel</td>
								</tr>
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
									<td>Sale Term</td>
									<td>$saleterm</td>
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
									<td>$chrgint%</td>
								</tr>
								<tr class='".bg_class()."'>
									<td>Overdue</td>
									<td>$overdue</td>
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
									<td>$credlimit</td>
								</tr>
								<tr>
									<td colspan='2' align='right'><input type='submit' value='Remove &raquo;'></td>
								</tr>
							</table>
							</form>
						</td>
					</tr>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='customers-view.php'>View Customers</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $rem;

}



# write new data
function write ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($cusnum, "num", 1, 50, "Invalid customer id.");

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

	# get info
	db_connect();
	$sql = "SELECT * FROM customers WHERE cusnum = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($custRslt) < 1){
			return "<li> Invalid Customer ID.</li>";
	}else{
			$cust = pg_fetch_array($custRslt);
			# get vars
			foreach ($cust as $key => $value) {
				$$key = $value;
			}
	}


	# write to db
	$sql = "DELETE FROM customers WHERE cusnum  = '$cusnum' AND div = '".USER_DIV."'";
	$custRslt = db_exec ($sql) or errDie ("Unable to remove customer on the system.", SELF);
	if (pg_cmdtuples ($custRslt) < 1) {
		return "<li class='err'>Unable to Remove Customer from database.</li>";
	}

	$Sl="UPDATE cons SET cust_id=0 WHERE cust_id='$cusnum'";
	$Ry=db_exec($Sl) or errDie("Unable to update contacts.");

	$write = "
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Customer removed</th>
					</tr>
					<tr class='datacell'>
						<td>Customer <b>$cusname $surname</b>, has been removed from Cubit.</td>
					</tr>
				</table>
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='customers-view.php'>View Customers</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $write;

}


?>