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

	$OUTPUT = report();

$OUTPUT.="<p>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<tr><th>Quick Links</th></tr>
<script>document.write(getQuicklinkSpecial());</script>
<tr class='bg-odd'><td><a href='index.php'>My Business</a></td></tr>
</table>";

require("template.php");

function report() {
	$i=0;
	$tottot=0;
	$totout=0;
	$totfor=0;
	$totold=0;
	$date=date("Y-m-d");
	$olddate = date("Y-m-d",mktime (0,0,0,date("m")  ,date("d")-30,date("Y")));

	db_conn('crm');

	$Sl="SELECT id,name FROM teams ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to get teams from system.");

	$out="<h3>Outstanding Query Statistics</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>User</th><th>Total</th><th>Outstanding</th><th>Forwarded</th><th>Month or Older</th></tr>";

	while($tdata=pg_fetch_array($Ry)) {
		$i++;
		$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

		$out.="<tr bgcolor='$bgcolor'><td colspan=5>$tdata[name]</td></tr>";

		$Sl="SELECT name,userid FROM crms WHERE teamid='$tdata[id]' ORDER BY name";
		$Rt=db_exec($Sl) or errDie("Unabel to get users from system.");

		$teamtot=0;
		$teamout=0;
		$teamfor=0;
		$teamold=0;

		while($udata=pg_fetch_array($Rt)) {
			$i++;
			$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

			$Sl="SELECT count(id) FROM tokens WHERE userid='$udata[userid]'";
			$Rx=db_exec($Sl) or errDie("Unable to get user queries.");
			$data=pg_fetch_array($Rx);
			$usertot=$data['count'];

			$Sl="SELECT count(id) FROM tokens WHERE userid='$udata[userid]' AND nextdate<='$date'";
			$Rx=db_exec($Sl) or errDie("Unable to get user queries2.");
			$data=pg_fetch_array($Rx);
			$userout=$data['count'];

			$Sl="SELECT count(id) FROM tokens WHERE userid='$udata[userid]' AND nextdate>'$date'";
			$Rx=db_exec($Sl) or errDie("Unable to get user queries3.");
			$data=pg_fetch_array($Rx);
			$userfor=$data['count'];

			$Sl="SELECT count(id) FROM tokens WHERE userid='$udata[userid]' AND opendate<='$olddate'";
			$Rx=db_exec($Sl) or errDie("Unable to get queries from system.");
			$data=pg_fetch_array($Rx);
			$userold=$data['count'];

			$out.="<tr bgcolor='$bgcolor'><td>$udata[name]</td><td>$usertot</td><td>$userout</td>
			<td>$userfor</td><td>$userold</td></tr>";

			$teamtot+=$usertot;
			$teamout+=$userout;
			$teamfor+=$userfor;
			$teamold+=$userold;
		}

		$i++;
		$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

		$out.="<tr bgcolor='$bgcolor'><td>Team Total</td><td>$teamtot</td><td>$teamout</td><td>$teamfor</td>
		<td>$teamold</td></tr>

		<tr><td><br></td></tr>";

		$tottot+=$teamtot;
		$totout+=$teamout;
		$totfor+=$teamfor;
		$totold+=$teamold;

	}

	$i++;
	$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

	$out.="<tr bgcolor='$bgcolor'><td><b>Total</b></td><td>$tottot</td><td>$totout</td><td>$totfor</td><td>$totold</td></tr>
	</table>";

	return $out;

}
?>








