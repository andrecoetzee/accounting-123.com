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
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
            case "confirm":
				$OUTPUT = confirm($HTTP_POST_VARS);
				break;

			case "write":
            	$OUTPUT = write($HTTP_POST_VARS);
				break;

			default:
				if (isset($HTTP_GET_VARS['supid'])){
					$OUTPUT = rem ($HTTP_GET_VARS['supid']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($HTTP_GET_VARS['supid'])){
			$OUTPUT = rem ($HTTP_GET_VARS['supid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

# display output
require ("template.php");

function rem($supid)
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
			$confirm .= "<li class=err>-".$e["msg"]."<br>";
		}
		return $confirm;
	}

	# Select
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND balance=0 AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
			return "<li> Invalid supplier ID.";
	}else{
			$supp = pg_fetch_array($suppRslt);
			# get vars
			foreach ($supp as $key => $value) {
				$$key = $value;
			}
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

	$confirm =
	"<h3>Confirm Remove Supplier</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=supid value='$supid'>
	<table cellpadding=0 cellspacing=0>
	<tr valign=top><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th colspan=2>Supplier Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Department</td><td>$deptname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Supplier No</td><td>$supno</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Name </td><td>$supname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Address</td><td><pre>$supaddr</pre></td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Contact Name</td><td>$contname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Tel No.</td><td>$tel</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Fax No.</td><td>$fax</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>E-mail</td><td>$email</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Web Address</td><td>http://$url</td></tr>
		</table>
	</td><td>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr bgcolor='".TMPL_tblDataColor2."'><th colspan=2> Bank Details</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Bank </td><td>$bankname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Branch</td><td>$branname</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Branch Code</td><td>$brancode</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Number</td><td>$bankaccno</td></tr>
		<tr><td><br></td></tr>
		<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
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

	return $confirm;
}

# write new data
function write ($HTTP_POST_VARS)
{
	# get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($supid, "num", 1, 50, "Invalid supplier id.");

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

	# Select
	db_connect();
	$sql = "SELECT * FROM suppliers WHERE supid = '$supid' AND div = '".USER_DIV."'";
	$suppRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($suppRslt) < 1){
			return "<li> Invalid supplier ID.";
	}else{
			$supp = pg_fetch_array($suppRslt);
			# get vars
			foreach ($supp as $key => $value) {
				$$key = $value;
			}
	}


	# write to db
	$sql = "DELETE FROM suppliers WHERE supid  = '$supid' AND div = '".USER_DIV."'";

	$supRslt = db_exec ($sql) or errDie ("Unable to remove supplier from the system.", SELF);
	if (pg_cmdtuples ($supRslt) < 1) {
		return "<li class=err>Unable to remove supplier from database.";
	}

	$Sl="UPDATE cons SET supp_id=0 WHERE supp_id='$supid'";
	$Ry=db_exec($Sl) or errDie("Unable to update contacts.");

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Supplier removed</th></tr>
	<tr class=datacell><td>Supplier <b>$supname</b>, has been removed from the system.</td></tr>
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
