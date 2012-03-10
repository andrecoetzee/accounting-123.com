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

require("settings.php");

if(isset($HTTP_POST_VARS["key"])) {
	switch($HTTP_POST_VARS["key"]) {
		case "remove":
			$OUTPUT = remove($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = "Invalid use of script";
	}
} elseif(isset($HTTP_GET_VARS["id"])) {
	$OUTPUT=confirm($HTTP_GET_VARS);
} else {
	$OUTPUT = "Invalid use of script.";
}

$OUTPUT.="<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='action-add.php'>Add Action</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='action-list.php'>View Actions</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

require("template.php");

function confirm($HTTP_GET_VARS) {

	extract($HTTP_GET_VARS);
	$id+=0;

	db_conn('crm');
	$Sl="SELECT * FROM actions WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get action info.");
	
	if(pg_numrows($Ry)<1) {
		return "Invalid action.";
	}
	
	$tcatdata=pg_fetch_array($Ry);
	
	$out="<h3>Remove Action</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='remove'>
	<input type=hidden name=id value='$id'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th colspan=2>Query Category Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Action</td><td>$tcatdata[action]</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
	</form>
	</table>";

	return $out;
}

function remove($HTTP_POST_VARS) {

	extract($HTTP_POST_VARS);
	
	$id+=0;

	db_conn('crm');
	$Sl="DELETE FROM actions WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to delete from db.");
	
	$out="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Action Deleted</th></tr>
	<tr class=datacell><td>Action has been successfully deleted in the system.</td></tr>
	</table>";
	
	return $out;
}

?>
