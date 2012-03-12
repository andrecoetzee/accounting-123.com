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
	$enter =
	"<h3>Access</h3>
	<form action='".SELF."' method=post>
	<table cellpadding=0 cellspacing=0>
	<tr valign=top><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<input type=hidden name=key value=confirm>
		<tr><th colspan=2>Select Script</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Name</td><td><input type=text size=20 name=script></td></tr>
		</table>

	</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
		</table></form>
	</td></tr>
	</table>";

	return $enter;
}

# error func
function enter_err ($_POST, $err="")
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	# Departments
	db_conn("exten");
	$depts = "<select name='deptid'>";
	$sql = "SELECT * FROM departments ORDER BY deptname ASC";
	$deptRslt = db_exec($sql);
	if(pg_numrows($deptRslt) < 1){
			return "<li>There are no Price lists in Cubit.";
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
	$depts .="</select>";

	$enter =
	"<h3>Edit Supplier</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=confirm>
	<table cellpadding=0 cellspacing=0>
	<tr><td colspan=2>$err</td></tr>
	<tr valign=top><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th colspan=2>Supplier Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Department</td><td>$depts</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Supplier No</td><td><input type=text size=10 name=supno value='$supno'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Name </td><td><input type=text size=20 name=supname value='$supname'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Address</td><td><textarea name=supaddr rows=5 cols=18>$supaddr</textarea></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Contact Name</td><td><input type=text size=20 name=contname value='$contname'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Tel No.</td><td><input type=text size=20 name=tel value='$tel'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Fax No.</td><td><input type=text size=20 name=fax value='$fax'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>E-mail</td><td><input type=text size=20 name=email value='$email'></td></tr>
		</table>
	</td><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr bgcolor='".TMPL_tblDataColor2."'><th colspan=2> Bank Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Bank </td><td><input type=text size=20 name=bankname value='$bankname'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Branch</td><td><input type=text size=20 name=branname value='$branname'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Branch Code</td><td><input type=text size=20 name=brancode value='$brancode'></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Number</td><td><input type=text size=20 name=bankaccno value='$bankaccno'></td></tr>
		<tr><td><br></td></tr>
		<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
		<tr><td><br></td></tr>
		<tr><td colspan=2 align=right>
			<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='supp-view.php'>View Suppliers</a></td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
			</table>
		</td></tr>
		</table></form>
	</td></tr>
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
	/*$v->isOk ($deptid, "num", 1, 255, "Invalid Department.");
	$v->isOk ($supno, "string", 1, 255, "Invalid supplier number.");
	$v->isOk ($supname, "string", 1, 255, "Invalid supplier name.");
	$v->isOk ($supaddr, "string", 1, 255, "Invalid supplier address.");
	$v->isOk ($contname, "string", 1, 255, "Invalid contact name.");
	$v->isOk ($tel, "string", 1, 20, "Invalid tel no.");
	$v->isOk ($fax, "string", 0, 20, "Invalid fax no.");
	$v->isOk ($email, "email", 0, 255, "Invalid e-mail address.");
	$v->isOk ($bankname, "string", 1, 255, "Invalid bank name.");
	$v->isOk ($branname, "string", 0, 255, "Invalid branch name.");
	$v->isOk ($brancode, "num", 1, 255, "Invalid branch code.");
	$v->isOk ($bankaccno, "num", 1, 255, "Invalid bank account number.");*/

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

	# get department
	db_conn("cubit");
	$Sl = "SELECT * FROM deptscripts WHERE script = '$script'";
	$Rs = db_exec($Sl);
	if(pg_numrows($Rs) < 1){
		return "<li class=err>Script not Found.";
	}else{
		$dept = pg_fetch_array($Rs);
		$deptid = $dept['dept'];
	}
	if(pg_numrows($Rs) > 1){
		return "<li class=err>Script Found more than once.";
	}
	$Sl = "SELECT * FROM depts WHERE deptid = '$deptid'";
	$Rs = db_exec($Sl);
	if(pg_numrows($Rs) < 1){
		return "<li class=err>Department not Found.";
	}else{
		$dept = pg_fetch_array($Rs);
		$deptname = $dept['dept'];
	}
	if(pg_numrows($Rs) > 1){
		return "<li class=err>Department Found more than once.";
	}

	$depts=ext_dbsel('dept','depts','deptid','dept','There are no departments');

	$confirm =
	"<h3>Access</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=script value='$script'>
	<table cellpadding=0 cellspacing=0>
	<tr valign=top><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th colspan=2>Script Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Department</td><td>$deptname</td></tr>
		<tr><th colspan=2>Move To</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Department</td><td>$depts</td></tr>
		</table>
	</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
		</table></form>
	</td></tr>
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
	/*$v->isOk ($deptid, "num", 1, 255, "Invalid Department.");
	$v->isOk ($supno, "string", 1, 255, "Invalid supplier number.");
	$v->isOk ($supname, "string", 1, 255, "Invalid supplier name.");
	$v->isOk ($supaddr, "string", 1, 255, "Invalid supplier address.");
	$v->isOk ($contname, "string", 1, 255, "Invalid contact name.");
	$v->isOk ($tel, "string", 1, 20, "Invalid tel no.");
	$v->isOk ($fax, "string", 0, 20, "Invalid fax no.");
	$v->isOk ($email, "email", 0, 255, "Invalid e-mail address.");
	$v->isOk ($bankname, "string", 1, 255, "Invalid bank name.");
	$v->isOk ($branname, "string", 0, 255, "Invalid branch name.");
	$v->isOk ($brancode, "num", 1, 255, "Invalid branch code.");
	$v->isOk ($bankaccno, "num", 1, 255, "Invalid bank account number.");*/

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
	db_connect ();

	$Sl="UPDATE deptscripts SET dept='$dept' WHERE script='$script'";
	$Rs = db_exec ($Sl) or errDie ("Unable to move script to the system.", SELF);

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Script moved</th></tr>
	<tr class=datacell><td>$Sl;</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='supp-view.php'>View Suppliers</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>
