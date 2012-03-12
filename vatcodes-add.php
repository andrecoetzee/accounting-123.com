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
	"<h3>Add VAT Code</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=confirm>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Code</td><td><input type=text size=10 name=code></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td><input type=text size=20 name=description></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Percentage</td><td><input type=text size=10 name=vat_amount></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='vatcodes-view.php'>View VAT Codes</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
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
	$v->isOk ($code, "string", 1, 255, "Invalid code.");
	$v->isOk ($description, "string", 1, 255, "Invalid name.");
	$v->isOk ($vat_amount, "float", 1, 255, "Invalid VAT percentage.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# check stock code
	db_connect();
	$sql = "SELECT code FROM vatcodes WHERE lower(code) = lower('$code')";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class=err> A VAT code with code : <b>$code</b> already exists.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	if($vat_amount == "0" OR ($vat_amount == "0.00")) {
		$zero="Yes";
	} else {
		$zero="No";
	}

	$confirm =
	"<h3>Confirm VAT Code</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=code value='$code'>
	<input type=hidden name=description value='$description'>
	<input type=hidden name=vat_amount value='$vat_amount'>
	<input type=hidden name=zero value='$zero'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Code</td><td>$code</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td>$description</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>VAT Percentage</td><td>$vat_amount</td></tr>
	<tr><td align=right><input type=button value='Back' onclick='javascript:history.back();'></td><td valign=left><input type=submit value='Write &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='vatcodes-view.php'>View VAT Codes</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
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
	$v->isOk ($code, "string", 1, 255, "Invalid code.");
	$v->isOk ($description, "string", 1, 255, "Invalid description.");
	$v->isOk ($vat_amount, "float", 1, 255, "Invalid VAT percentage.");

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

	$zero=remval($zero);

	# check stock code
	db_connect();
	$sql = "SELECT code FROM vatcodes WHERE lower(code) = lower('$code')";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class=err> A VAT Code with code : <b>$code</b> already exists.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	# connect to db
	db_connect ();

	# write to db
	$sql = "INSERT INTO vatcodes(code, description,vat_amount,zero,del) VALUES ('$code', '$description','$vat_amount','$zero','No')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add class to system.", SELF);
	if (pg_cmdtuples ($catRslt) < 1) {
		return "<li class=err>Unable to add classname to database.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>VAT Code added to system</th></tr>
	<tr class=datacell><td>New VAT code <b>$code</b>, has been successfully added to the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='vatcodes-view.php'>View VAT Codes</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>
