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
require("libs/crm.lib.php");

	$OUTPUT=list_cats();

	$OUTPUT.="<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='tcat-add.php'>Add Query Category</a></td></tr>
	<tr class='bg-odd'><td><a href='tcat-list.php'>View Query Categories</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr class='bg-odd'><td><a href='index.php'>My Business</a></td></tr>
	</table>";

require("template.php");

function list_cats() {

	db_conn('crm');
	$Sl="SELECT * FROM tcats WHERE div='".USER_DIV."' ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to list teams.");

	if(pg_numrows($Ry)<1) {
		dc();
		$Sl="SELECT * FROM tcats WHERE div='".USER_DIV."' ORDER BY name";
		$Ry=db_exec($Sl) or errDie("Unable to list teams.");
	}

	$out="<h3>Query Categories</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Name</th><th>Description</th><th colspan=2>Options</th></tr>";

	$i=0;

	while($tcatdata=pg_fetch_array($Ry)) {
		$i++;

		$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

		$out.="<tr bgcolor='$bgcolor'><td>$tcatdata[name]</td><td>$tcatdata[des]</td><td><a href='tcat-edit.php?id=$tcatdata[id]'>Edit</a></td><td><a href='tcat-rem.php?id=$tcatdata[id]'>Remove</a></td></tr>";

	}

	$out.="</table>";

	return $out;
}

?>
