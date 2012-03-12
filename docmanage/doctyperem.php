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
require ("../core-settings.php");

# Decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "write":
			$OUTPUT = write ($_POST);
			break;
		default:
			if(isset($_GET['typeid'])){
				$OUTPUT = confirm ($_GET);
			}else{
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if(isset($_GET['typeid'])){
		$OUTPUT = confirm ($_GET);
	}else{
		$OUTPUT = "<li> - Invalid use of module";
	}
}

# Display output
require ("../template.php");

# enter new data
function confirm($VARS)
{
	foreach ($VARS as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($typeid, "string", 1, 20, "Invalid document type number.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	$doctRs = get("cubit", "*", "doctypes", "typeid", $typeid);
	$doct = pg_feTch_array($doctRs);

	$enter =
	"<h3>Remove Document type</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=typeid value=$typeid>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Ref</td><td>$doct[typeref]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Document type</td><td>$doct[typename]</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doctypeview.php'>View Document types</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# write new data
function write ($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($typeid, "string", 1, 20, "Invalid document type number.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	$doctRs = get("cubit", "*", "doctypes", "typeid", $typeid);
	$doct = pg_feTch_array($doctRs);

	# Write to db
	db_conn("cubit");
	$sql = "DELETE FROM doctypes WHERE typeid = '$typeid'";
	$dRslt = db_exec ($sql) or errDie ("Unable to remove $doct[typename] to system.", SELF);

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Document type removed</th></tr>
	<tr class=datacell><td>Document type <b>$doct[typename]</b>, has been successfully removed from the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doctypeview.php'>View Document types</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>
