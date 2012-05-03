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
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		case "write":
			$OUTPUT = write ($_POST);
			break;
		default:
			$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# display output
require ("../template.php");

# enter new data
function enter ()
{
	// Select the stock category
	db_conn("toms");
	$cats= "<select name='catid'>";
	$sql = "SELECT * FROM categories ORDER BY category ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
			return "<li>There are no categories in Cubit.";
	}else{
			while($cat = pg_fetch_array($catRslt)){
					$cats .= "<option value='$cat[catid]'>$cat[category]</option>";
			}
	}
	$cats .="</select>";

	$class = "<select name='clasid'>";
	$sql = "SELECT * FROM class ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
			return "<li>There are no Classifications in Cubit.";
	}else{
			while($clas = pg_fetch_array($clasRslt)){
					$class .= "<option value='$clas[clasid]'>$clas[classname]</option>";
			}
	}
	$class .="</select>";

	$pricelists = "<select name='listid'>";
	$sql = "SELECT * FROM pricelist ORDER BY listname ASC";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
			return "<li>There are no Price lists in Cubit.";
	}else{
			while($list = pg_fetch_array($listRslt)){
					$pricelists .= "<option value='$list[listid]'>$list[listname]</option>";
			}
	}
	$pricelists .="</select>";

	$enter =
	"<h3>Add Customer</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Acc No</td><td><input type=text size=10 name=accno></td></tr>
	<tr class='bg-odd'><td>Surname/Company</td><td><input type=text size=20 name=surname></td></tr>
	<tr class='bg-odd'><td>Title</td><td><input type=text size=5 name=title></td></tr>
	<tr class='bg-odd'><td>Initials</td><td><input type=text size=10 name=init></td></tr>
	<tr class='bg-odd'><td>First Name</td><td><input type=text size=20 name=firstname></td></tr>
	<tr class='bg-odd'><td>Category</td><td>$cats</td></tr>
	<tr class='bg-odd'><td>Classification</td><td>$class</td></tr>
	<tr class='bg-odd'><td>Postal Address</td><td><textarea rows=5 cols=18 name=paddr></textarea></td></tr>
	<tr class='bg-odd'><td>Delivery Address</td><td><textarea rows=5 cols=18 name=daddr></textarea></td></tr>
	<tr class='bg-odd'><td>Contact Name</td><td><input type=text size=20 name=contname></td></tr>
	<tr class='bg-odd'><td>Business Tel.</td><td><input type=text size=20 name=bustel></td></tr>
	<tr class='bg-odd'><td>Home Tel.</td><td><input type=text size=20 name=hometel></td></tr>
	<tr class='bg-odd'><td>Cell No.</td><td><input type=text size=20 name=cellno></td></tr>
	<tr class='bg-odd'><td>Fax No.</td><td><input type=text size=20 name=faxno></td></tr>
	<tr class='bg-odd'><td>E-mail</td><td><input type=text size=20 name=email></td></tr>
	<tr class='bg-odd'><td>Sale Term</td><td><select name=saleterm>
	<option value='30'>30</option><option value='60'>60</option><option value='90'>90</option
	</select></td></tr>
	<tr class='bg-odd'><td>Trade Discount</td><td><input type=text size=20 name=traddisc></td></tr>
	<tr class='bg-odd'><td>Settlement Discount</td><td><input type=text size=20 name=setdisc></td></tr>
	<tr class='bg-odd'><td>Price List</td><td>$pricelists</td></tr>
	<tr class='bg-odd'><td>Charge Interest</td><td><input type=text size=20 name=chrgint></td></tr>
	<tr class='bg-odd'><td>Overdue</td><td><input type=text size=20 name=overdue></td></tr>
	<tr class='bg-odd'><td>Charge Vat</td><td>No<input type=radio size=20 name=chrgvat value=no checked=yes> Yes<input type=radio size=20 name=chrgvat value=yes checked=yes></td></tr>
	<tr class='bg-odd'><td colspan=2 align=center>Vat : Inclusive<input type=radio size=20 name=vatinc value=yes checked=yes> Exclusive<input type=radio size=20 name=vatinc value=no></td></tr>
	<tr class='bg-odd'><td>Account Open Date</td><td><input type=text size=2 name=oday maxlength=2 value='".date("d")."'>-<input type=text size=2 name=omon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=oyear maxlength=4 value='".date("Y")."'></td></tr>
	<tr class='bg-odd'><td>Credit Term</td><td><input type=text size=20 name=credterm></td></tr>
	<tr class='bg-odd'><td>Credit Limit</td><td><input type=text size=20 name=credlimit></td></tr>
	<tr class='bg-odd'><td>Block Account</td><td>No<input type=radio size=20 name=block value=no checked=yes> Yes<input type=radio size=20 name=block value=yes></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='cust-view.php'>View Customers</a></td></tr>
	<tr class='bg-odd'><td><a href='index.php'>Index</a></td></tr>
	<tr class='bg-odd'><td><a href='toms-settings.php'>Settings</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# enter new data
function enter_err ($_POST, $err="")
{

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Select Stock
	db_conn("toms");

	// Select the stock category
	db_conn("toms");
	$cats= "<select name='catid'>";
	$sql = "SELECT * FROM categories ORDER BY category ASC";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
			return "<li>There are no categories in Cubit.";
	}else{
			while($cat = pg_fetch_array($catRslt)){
					if($cat['catid'] == $catid){
						$sel = "selected";
					}else{
						$sel = "";
					}
					$cats .= "<option value='$cat[catid]' $sel>$cat[category]</option>";
			}
	}
	$cats .="</select>";

	$classes = "<select name='clasid'>";
	$sql = "SELECT * FROM class ORDER BY classname ASC";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
			return "<li>There are no Classifications in Cubit.";
	}else{
			while($clas = pg_fetch_array($clasRslt)){
			if($clas['clasid'] == $clasid){
						$sel = "selected";
					}else{
						$sel = "";
					}
					$classes .= "<option value='$clas[clasid]' $sel>$clas[classname]</option>";
			}
	}
	$classes .="</select>";

	$pricelists = "<select name='listid'>";
	$sql = "SELECT * FROM pricelist ORDER BY listname ASC";
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
	$pricelists .="</select>";

	$enter =
	"<h3>Add Customer</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><td colspan=2>$err</td></tr>
	<input type=hidden name=key value=confirm>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Acc No</td><td><input type=text size=10 name=accno value='$accno'></td></tr>
	<tr class='bg-odd'><td>Surname/Company</td><td><input type=text size=20 name=surname value='$surname'></td></tr>
	<tr class='bg-odd'><td>Title</td><td><input type=text size=5 name=title value='$title'></td></tr>
	<tr class='bg-odd'><td>Initials</td><td><input type=text size=10 name=init value='$init'></td></tr>
	<tr class='bg-odd'><td>First Name</td><td><input type=text size=20 name=firstname value='$firstname'></td></tr>
	<tr class='bg-odd'><td>Category</td><td>$cats</td></tr>
	<tr class='bg-odd'><td>Classification</td><td>$classes</td></tr>
	<tr class='bg-odd'><td>Postal Address</td><td><textarea rows=5 cols=18 name=paddr>$paddr</textarea></td></tr>
	<tr class='bg-odd'><td>Delivery Address</td><td><textarea rows=5 cols=18 name=daddr>$daddr</textarea></td></tr>
	<tr class='bg-odd'><td>Contact Name</td><td><input type=text size=20 name=contname value='$contname'></td></tr>
	<tr class='bg-odd'><td>Business Tel.</td><td><input type=text size=20 name=bustel value='$bustel'></td></tr>
	<tr class='bg-odd'><td>Home Tel.</td><td><input type=text size=20 name=hometel value='$hometel'></td></tr>
	<tr class='bg-odd'><td>Cell No.</td><td><input type=text size=20 name=cellno value='$cellno'></td></tr>
	<tr class='bg-odd'><td>Fax No.</td><td><input type=text size=20 name=faxno value='$faxno'></td></tr>
	<tr class='bg-odd'><td>E-mail</td><td><input type=text size=20 name=email value='$email'></td></tr>
	<tr class='bg-odd'><td>Sale Term</td><td><select name=saleterm>
	<option value='30'>30</option><option value='60'>60</option><option value='90'>90</option
	</select></td></tr>
	<tr class='bg-odd'><td>Trade Discount</td><td><input type=text size=20 name=traddisc value='$traddisc'></td></tr>
	<tr class='bg-odd'><td>Settlement Discount</td><td><input type=text size=20 name=setdisc value='$setdisc'></td></tr>
	<tr class='bg-odd'><td>Price List</td><td>$pricelists</td></tr>
	<tr class='bg-odd'><td>Charge Interest</td><td><input type=text size=20 name=chrgint value='$chrgint'></td></tr>
	<tr class='bg-odd'><td>Overdue</td><td><input type=text size=20 name=overdue value='$overdue'></td></tr>
	<tr class='bg-odd'><td>Charge Vat</td><td><input type=text size=20 name=chrgvat value='$chrgvat'></td></tr>
	<tr class='bg-odd'><td colspan=2 align=center>Vat : Inclusive<input type=radio size=20 name=vatinc value=yes checked=yes> Exclusive<input type=radio size=20 name=vatinc value=no></td></tr>
	<tr class='bg-odd'><td>Account Open Date</td><td><input type=text size=2 name=oday maxlength=2 value='".date("d")."'>-<input type=text size=2 name=omon maxlength=2 value='".date("m")."'>-<input type=text size=4 name=oyear maxlength=4 value='".date("Y")."'></td></tr>
	<tr class='bg-odd'><td>Credit Term</td><td><input type=text size=20 name=credterm value='$credterm'></td></tr>
	<tr class='bg-odd'><td>Credit Limit</td><td><input type=text size=20 name=credlimit value='$credlimit'></td></tr>
	<tr class='bg-odd'><td>Block Account</td><td>No<input type=radio size=20 name=block value=no checked=yes> Yes<input type=radio size=20 name=block value=yes></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='cust-view.php'>View Customers</a></td></tr>
	<tr class='bg-odd'><td><a href='index.php'>Index</a></td></tr>
	<tr class='bg-odd'><td><a href='toms-settings.php'>Settings</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# confirm new data
function confirm ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accno, "string", 1, 20, "Invalid Account number.");
	$v->isOk ($surname, "string", 1, 255, "Invalid surname/company.");
	$v->isOk ($title, "string", 1, 10, "Invalid title.");
	$v->isOk ($init, "string", 1, 10, "Invalid initials.");
	$v->isOk ($firstname, "string", 1, 255, "Invalid Customer name.");
	$v->isOk ($catid, "num", 1, 255, "Invalid Category.");
	$v->isOk ($clasid, "num", 1, 255, "Invalid Classification.");
	$v->isOk ($paddr, "string", 1, 255, "Invalid Postal Address.");
	$v->isOk ($daddr, "string", 1, 255, "Invalid Delivery Address.");
	$v->isOk ($contname, "string", 1, 255, "Invalid contact name.");
	$v->isOk ($bustel, "string", 1, 20, "Invalid Bussines telephone.");
	$v->isOk ($hometel, "string", 1, 20, "Invalid Home telephone.");
	$v->isOk ($cellno, "string", 0, 20, "Invalid Cell number.");
	$v->isOk ($faxno, "string", 0, 20, "Invalid Fax number.");
	$v->isOk ($email, "email", 0, 255, "Invalid email name.");
	$v->isOk ($saleterm, "num", 1, 20, "Invalid Sale Term.");
	$v->isOk ($traddisc, "float", 0, 20, "Invalid trade discount.");
	$v->isOk ($setdisc, "float", 0, 20, "Invalid settlement discount.");
	$v->isOk ($listid, "num", 1, 20, "Invalid price list.");
	$v->isOk ($chrgint, "float", 0, 20, "Invalid Charge interest.");
	$v->isOk ($overdue, "float", 0, 20, "Invalid overdue.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat.");
	$v->isOk ($vatinc, "string", 1, 3, "Invalid vat inclusive selection.");
	$v->isOk ($credterm, "num", 0, 20, "Invalid Credit term.");
	# mix date
	$odate = $oday."-".$omon."-".$oyear;

	if(!checkdate($omon, $oday, $oyear)){
			$v->isOk ($odate, "num", 1, 1, "Invalid account open date.");
	}
	$v->isOk ($credlimit, "float", 0, 20, "Invalid credit limit.");
	$v->isOk ($block, "string", 1, 3, "Invalid Block acc selection.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return enter_err($_POST, $confirm);
		exit;
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	// get drop down info
	db_conn("toms");
	# get Category
	$sql = "SELECT * FROM categories WHERE catid = '$catid'";
	$catRslt = db_exec($sql);
	if(pg_numrows($catRslt) < 1){
			$category = "<li class=err>Category not Found.";
	}else{
		$cat = pg_fetch_array($catRslt);
		$category = $cat['category'];
	}

	# get Classification
	$sql = "SELECT * FROM class WHERE clasid = '$clasid'";
	$clasRslt = db_exec($sql);
	if(pg_numrows($clasRslt) < 1){
		$class = "<li class=err>Class not Found.";
	}else{
		$clas = pg_fetch_array($clasRslt);
		$class = $clas['classname'];
	}

	# get Price List
	$sql = "SELECT * FROM pricelist WHERE listid = '$listid'";
	$listRslt = db_exec($sql);
	if(pg_numrows($listRslt) < 1){
		$pricelist = "<li class=err>Class not Found.";
	}else{
		$list = pg_fetch_array($listRslt);
		$plist = $list['listname'];
	}

	$confirm =
	"<h3>Confirm Customer</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=accno value='$accno'>
	<input type=hidden name=surname value='$surname'>
	<input type=hidden name=title value='$title'>
	<input type=hidden name=init value='$init'>
	<input type=hidden name=firstname value='$firstname'>
	<input type=hidden name=catid value='$catid'>
	<input type=hidden name=clasid value='$clasid'>
	<input type=hidden name=paddr value='$paddr'>
	<input type=hidden name=daddr value='$daddr'>
	<input type=hidden name=contname value='$contname'>
	<input type=hidden name=bustel value='$bustel'>
	<input type=hidden name=hometel value='$hometel'>
	<input type=hidden name=cellno value='$cellno'>
	<input type=hidden name=faxno value='$faxno'>
	<input type=hidden name=email value='$email'>
	<input type=hidden name=saleterm value='$saleterm'>
	<input type=hidden name=traddisc value='$traddisc'>
	<input type=hidden name=setdisc value='$setdisc'>
	<input type=hidden name=listid value='$listid'>
	<input type=hidden name=chrgint value='$chrgint'>
	<input type=hidden name=overdue value='$overdue'>
	<input type=hidden name=chrgvat value='$chrgvat'>
	<input type=hidden name=vatinc value='$vatinc'>
	<input type=hidden name=credterm value='$credterm'>
	<input type=hidden name=odate value='$odate'>
	<input type=hidden name=credlimit value='$credlimit'>
	<input type=hidden name=block value='$block'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Acc No</td><td>$accno</td></tr>
	<tr class='bg-odd'><td>Surname/Company</td><td>$surname</td></tr>
	<tr class='bg-odd'><td>Title</td><td>$title</td></tr>
	<tr class='bg-odd'><td>Initials</td><td>$init</td></tr>
	<tr class='bg-odd'><td>First Name</td><td>$firstname</td></tr>
	<tr class='bg-odd'><td>Category</td><td>$category</td></tr>
	<tr class='bg-odd'><td>Classification</td><td>$class</td></tr>
	<tr class='bg-odd'><td>Postal Address</td><td><pre>$paddr</pre></td></tr>
	<tr class='bg-odd'><td>Delivery Address</td><td><pre>$daddr</pre></td></tr>
	<tr class='bg-odd'><td>Contact Name</td><td>$contname</td></tr>
	<tr class='bg-odd'><td>Business Tel.</td><td>$bustel</td></tr>
	<tr class='bg-odd'><td>Home Tel.</td><td>$hometel</td></tr>
	<tr class='bg-odd'><td>Cell No.</td><td>$cellno</td></tr>
	<tr class='bg-odd'><td>Fax No.</td><td>$faxno</td></tr>
	<tr class='bg-odd'><td>E-mail</td><td>$email</td></tr>
	<tr class='bg-odd'><td>Sale Term</td><td>$saleterm</td></tr>
	<tr class='bg-odd'><td>Trade Discount</td><td>$traddisc</td></tr>
	<tr class='bg-odd'><td>Settlement Discount</td><td>$setdisc</td></tr>
	<tr class='bg-odd'><td>Price List</td><td>$plist</td></tr>
	<tr class='bg-odd'><td>Charge Interest</td><td>$chrgint</td></tr>
	<tr class='bg-odd'><td>Overdue</td><td>$overdue</td></tr>
	<tr class='bg-odd'><td>Charge Vat</td><td>$chrgvat</td></tr>
	<tr class='bg-odd'><td>Vat Inclusive</td><td>$vatinc</td></tr>
	<tr class='bg-odd'><td>Account Open Date</td><td>$odate</td></tr>
	<tr class='bg-odd'><td>Credit Term</td><td>$credterm</td></tr>
	<tr class='bg-odd'><td>Credit Limit</td><td>$credlimit</td></tr>
	<tr class='bg-odd'><td>Block Account</td><td>$block</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='cust-view.php'>View Customers</a></td></tr>
	<tr class='bg-odd'><td><a href='index.php'>Index</a></td></tr>
	<tr class='bg-odd'><td><a href='toms-settings.php'>Settings</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# write new data
function write ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accno, "string", 1, 20, "Invalid Account number.");
	$v->isOk ($surname, "string", 0, 255, "Invalid surname/company.");
	$v->isOk ($title, "string", 0, 10, "Invalid title.");
	$v->isOk ($init, "string", 0, 10, "Invalid initials.");
	$v->isOk ($firstname, "string", 0, 255, "Invalid Customer name.");
	$v->isOk ($catid, "num", 1, 255, "Invalid Category.");
	$v->isOk ($clasid, "num", 1, 255, "Invalid Classification.");
	$v->isOk ($paddr, "string", 0, 255, "Invalid Postal Address.");
	$v->isOk ($daddr, "string", 0, 255, "Invalid Delivery Address.");
	$v->isOk ($contname, "string", 0, 255, "Invalid contact name.");
	$v->isOk ($bustel, "string", 1, 20, "Invalid Bussines telephone.");
	$v->isOk ($hometel, "string", 1, 20, "Invalid Home telephone.");
	$v->isOk ($cellno, "string", 0, 20, "Invalid Cell number.");
	$v->isOk ($faxno, "string", 0, 20, "Invalid Fax number.");
	$v->isOk ($email, "email", 0, 255, "Invalid email name.");
	$v->isOk ($saleterm, "num", 1, 20, "Invalid Sale Term.");
	$v->isOk ($traddisc, "float", 0, 20, "Invalid trade discount.");
	$v->isOk ($setdisc, "float", 0, 20, "Invalid settlement discount.");
	$v->isOk ($listid, "num", 1, 20, "Invalid price list.");
	$v->isOk ($chrgint, "float", 0, 20, "Invalid Charge interest.");
	$v->isOk ($overdue, "float", 0, 20, "Invalid overdue.");
	$v->isOk ($chrgvat, "string", 1, 4, "Invalid charge vat.");
	$v->isOk ($vatinc, "string", 1, 3, "Invalid vat inclusive selection.");
	$v->isOk ($credterm, "num", 0, 20, "Invalid Credit term.");
	$v->isOk ($odate, "date", 1, 14, "Invalid account open date.");
	$v->isOk ($credlimit, "float", 0, 20, "Invalid credit limit.");
	$v->isOk ($block, "string", 1, 3, "Invalid Block acc selection.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	# connect to db
	db_conn ("toms");

	# fix numeric
	$saleterm += 0;
	$traddisc += 0;
	$setdisc += 0;
	$listid += 0;
	$chrgint += 0;
	$overdue += 0;
	$credterm += 0;
	$credlimit += 0;



	# write to db
	$sql = "INSERT INTO  customers(accno, surname, title, init, firstname, category, class, paddr, daddr, contname, bustel, hometel, cellno, faxno, email, saleterm, traddisc, setdisc, pricelist, chrgint, overdue, chrgvat, vatinc, credterm, odate, credlimit, blocked)
	VALUES ('$accno', '$surname', '$title', '$init', '$firstname', '$catid', '$clasid', '$paddr', '$daddr', '$contname', '$bustel', '$hometel', '$cellno', '$faxno', '$email', '$saleterm', '$traddisc', '$setdisc', '$listid', '$chrgint', '$overdue', '$chrgvat', '$vatinc', '$credterm', '$odate', '$credlimit', '$block')";
	$custRslt = db_exec ($sql) or errDie ("Unable to add fringe benefit to system.", SELF);
	if (pg_cmdtuples ($custRslt) < 1) {
		return "<li class=err>Unable to add customer to database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Customer added to system</th></tr>
	<tr class=datacell><td>New Customer <b>$firstname $surname</b>, has been successfully added to the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='cust-view.php'>View Customers</a></td></tr>
	<tr class='bg-odd'><td><a href='index.php'>Index</a></td></tr>
	<tr class='bg-odd'><td><a href='toms-settings.php'>Settings</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>
