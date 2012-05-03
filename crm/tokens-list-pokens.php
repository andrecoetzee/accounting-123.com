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

	$OUTPUT = listall();
	
$OUTPUT.="<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-even'><td align=center><a href='index.php'>My Business</a></td></tr>
	<tr class='bg-odd'><td align=center><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

require("template.php");

function listall() {
	$i=0;
	db_conn('crm');
	$Sl="SELECT * FROM pokens ORDER BY id";
	$Ry=db_exec($Sl) or errDie("Unable to get unallocated queries from system.");
	
	if(pg_numrows($Ry)<1) {
		return "There are no unallocated queries.";
	}
	
	$out="<h3>Unallocated Queries</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Subject</th><th>Body</th><th>Date</th><th>Time</th><th>Options</th></tr>";

	while ($pdata=pg_fetch_array($Ry)) {
		$i++;
		
		$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;
		
		$out.="<tr bgcolor='$bgcolor'><td>$pdata[sub]</td><td><pre>$pdata[notes]</pre></td><td>$pdata[rdate]</td>
		<td>".substr($pdata['rtime'],0,5)."</td><td><a href='tokens-allocate.php?id=$pdata[id]'>Allocate</a></td></tr>";
	}
	
	$out.="</table>";

	return $out;
}

?>
