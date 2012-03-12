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
require ("../settings.php");
# decide what to do

if (isset($_GET['custid'])){
	$OUTPUT = view ($_GET['custid']);
} else {
	$OUTPUT = "<li> - Invalid use of module";
}


# display output
require ("../template.php");

function view($custid)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($custid, "num", 1, 50, "Invalid customer id.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
		return $confirm;
	}

	# Select
	db_conn("toms");
	$sql = "SELECT * FROM customers WHERE custid = '$custid'";
	$custRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($custRslt) < 1){
			return "<li> Invalid Customer ID.";
	}else{
			$cust = pg_fetch_array($custRslt);
			# get vars
			foreach ($cust as $key => $value) {
				$$key = $value;
			}
	}

	# get Category
	$sql = "SELECT * FROM categories WHERE catid = '$category'";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
			$category = "<li class=err>Category not Found.";
	}else{
		$cat = pg_fetch_array($catRslt);
		$category = $cat['category'];
	}

	# get Classification
	$sql = "SELECT * FROM class WHERE clasid = '$class'";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		$class = "<li class=err>Class not Found.";
	}else{
		$clas = pg_fetch_array($clasRslt);
		$class = $clas['classname'];
	}

	# get Price List
	$sql = "SELECT * FROM pricelist WHERE listid = '$pricelist'";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		$plist = "<li class=err>Class not Found.";
	}else{
		$list = pg_fetch_array($listRslt);
		$plist = $list['listname'];
	}

	// layout
	$confirm =
	"<h3>Customer Details</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Acc No</td><td>$accno</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Surname/Company</td><td>$surname</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Title</td><td>$title</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Initials</td><td>$init</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>First Name</td><td>$firstname</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Category</td><td>$category</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Classification</td><td>$class</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Postal Address</td><td><pre>$paddr</pre></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Delivery Address</td><td><pre>$daddr</pre></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Contact Name</td><td>$contname</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Business Tel.</td><td>$bustel</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Home Tel.</td><td>$hometel</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Cell No.</td><td>$cellno</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Fax No.</td><td>$faxno</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>E-mail</td><td>$email</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Sale Term</td><td>$saleterm</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Trade Discount</td><td>$traddisc</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Settlement Discount</td><td>$setdisc</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Price List</td><td>$plist</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Charge Interest</td><td>$chrgint</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Overdue</td><td>$overdue</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Charge Vat</td><td>$chrgvat</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Vat Inclusive</td><td>$vatinc</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Open Date</td><td>$odate</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Credit Term</td><td>$credterm</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Credit Limit</td><td>$credlimit</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Block Account</td><td>$blocked</td></tr>
	<tr><td colspan=2 align=right><br></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='cust-view.php'>View Customers</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>Index</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='toms-settings.php'>Settings</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}
?>
