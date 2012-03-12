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

if(isset($_GET["id"])) {
	$OUTPUT = archive($_GET);
} else {
	$OUTPUT = "Invalid.";
}

require("template.php");

function archive($_GET) {
	extract($_GET);

	$id+=0;

	db_conn('crm');
	$Sl="SELECT * FROM crms WHERE userid='".USER_ID."'";
	$Ri=db_exec($Sl) or errDie("Unable to get data from system.");

	if(pg_num_rows($Ri)<1) {
		return "Invalid.";
	}

	$crmdata=pg_fetch_array($Ri);

        $teams=explode("|",$crmdata['teams']);

	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ri=db_exec($Sl) or errDie("Unable to get query.");

	if(pg_num_rows($Ri)<1) {
		return "Invalid query.";
	}

	$tokendata=pg_fetch_array($Ri);

	if(!(in_array($tokendata['teamid'],$teams))) {
		return "Declined.";
	}

	$i=0;
	$pactions="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='100%'>
	<tr><td colspan=3 align=center><h4>Archived Actions</h4></td></tr>
	<tr><th>Date</th><th>Action</th><th>Done By</th></tr>";

	$Sl="SELECT donedate,donetime,action,doneby FROM archived_actions WHERE token='$id' ORDER BY id DESC";
	$Ry=db_exec($Sl) or errDie("Unable to get query actions from system.");

	while($pdata=pg_fetch_array($Ry)) {
		$i++;

		$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

		$pactions.="<tr bgcolor='$bgcolor'><td>$pdata[donedate], ".substr($pdata['donetime'],0,5)."</td><td>$pdata[action]</td><td>$pdata[doneby]</td></tr>";

	}

	$pactions.="</table>";

	return $pactions;

}

?>