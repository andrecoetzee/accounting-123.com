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
require_lib("docman");

# Decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "write":
			$OUTPUT = write ($_POST);
			break;
		default:
			if(isset($_GET['docid'])){
				$OUTPUT = confirm ($_GET);
			}else{
				$OUTPUT = "<li> - Invalid use of module";
			}
	}
} else {
	if(isset($_GET['docid'])){
		$OUTPUT = confirm ($_GET);
	}else{
		$OUTPUT = "<li> - Invalid use of module";
	}
}

# Display output
require ("../template.php");

# Enter new data
function confirm ($VARS)
{
	# Get vars
	global $DOCLIB_DOCTYPES;
	foreach ($VARS as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($docid, "string", 1, 20, "Invalid document number.");

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

	$docRs = get("yr2", "*", "documents", "docid", $docid);
	$doc = pg_feTch_array($docRs);

	# Extra in
	$xin = xinc($doc['typeid'], $doc['xin']);

	$confirm =
	"<h3>Confirm Remove Document</h3>
	<form name=form1 action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<input type=hidden name=key value=write>
	<input type=hidden name=docid value='$docid'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Type</td><td>$doc[typename]</td></tr>
	$xin
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Ref</td><td>$doc[docref]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Document Name</td><td>$doc[docname]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Date</td><td>$doc[docdate]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Decription</td><td>".nl2br($doc['descrip'])."</td></tr>
	<tr><td><br></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doc-add.php'>Add Document</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doc-view.php'>View Documents</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $confirm;
}

# Write new data
function write ($_POST)
{
	# Get vars
	global $DOCLIB_DOCTYPES;
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($docid, "string", 1, 20, "Invalid document number.");

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

	# Connect to db
	db_conn ("yr2");

	$docRs = get("yr2", "*", "documents", "docid", $docid);
	$doc = pg_feTch_array($docRs);

	# Write to db
	$sql = "DELETE FROM documents WHERE docid = '$docid' AND div = '".USER_DIV."'";
	$docRslt = db_exec ($sql) or errDie ("Unable to remove $doc[docname] from system.", SELF);
	if (pg_cmdtuples ($docRslt) < 1) {
		return "<li class=err>Unable to remove $doc[docname] from Cubit.";
	}

	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Document removed</th></tr>
		<tr class=datacell><td>Document <b>$doc[docname]</b>, has been successfully removed from the system.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doc-add.php'>Add Document</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='doc-view.php'>View Documents</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>
