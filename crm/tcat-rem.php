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

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "remove":
			$OUTPUT = remove($_POST);
			break;
		default:
			$OUTPUT = "Invalid use of script";
	}
} elseif(isset($_GET["id"])) {
	$OUTPUT=confirm($_GET);
} else {
	$OUTPUT = "Invalid use of script.";
}

$OUTPUT.="<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tcat-add.php'>Add Query Categories</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='tcat-list.php'>View Query Categories</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

require("template.php");

function confirm($_GET) {

	extract($_GET);
	$id+=0;

	db_conn('crm');
	$Sl="SELECT * FROM tcats WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get category info.");
	
	if(pg_numrows($Ry)<1) {
		return "Invalid category.";
	}
	
	$tcatdata=pg_fetch_array($Ry);
	
	$out="<h3>Remove Query Category</h3>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='remove'>
	<input type=hidden name=id value='$id'>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th colspan=2>Query Category Details</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Category Name</td><td>$tcatdata[name]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Description</td><td>$tcatdata[des]</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Remove &raquo;'></td></tr>
	</form>
	</table>";

	return $out;
}

function remove($_POST) {

	extract($_POST);
	
	$id+=0;

	db_conn('crm');
	$Sl="DELETE FROM tcats WHERE id='$id' AND div='".USER_DIV."'";
	$Ry=db_exec($Sl) or errDie("Unable to delete from db.");
	
	$out="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Query Category Deleted</th></tr>
	<tr class=datacell><td>Query Category has been successfully deleted in the system.</td></tr>
	</table>";
	
	return $out;
}

?>
