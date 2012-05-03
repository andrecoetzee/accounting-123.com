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

	$OUTPUT = listcrms();

	$OUTPUT .="<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr class='bg-odd'><td><a href='index.php'>My Business</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

require("template.php");

function listcrms() {
        db_conn('crm');
	$Sl="SELECT * FROM crms ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to get data.");

	if(pg_num_rows($Ry)<1) {
		return "There are no crms in Cubit.";
	}

	$i=0;

	$out="<h3>Select user</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Name</th><th>Options</th>";

	while($cdata=pg_fetch_array($Ry)) {
        	$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

                $out.="<tr bgcolor='$bgcolor'><td>$cdata[name]</td><td><a href='crms-teams.php?id=$cdata[id]'>Select teams</a></td></tr>";

	}

	$out.="</table>";

	return $out;

}

?>