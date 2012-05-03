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

	$OUTPUT = list_tokens();

$OUTPUT.="<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr class='bg-odd'><td><a href='crms-allocate.php'>Allocate users to Teams</a></td></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr class='bg-even'><td align=center><a href='index.php'>My Business</a></td></tr>
	</table>";

require("template.php");

function list_tokens(){
	global $_POST;
	extract($_POST);

        $i=0;

	db_conn('crm');
        $Sl="SELECT * FROM crms WHERE userid='".USER_ID."'";
	$Ri=db_exec($Sl) or errDie("Unable to go on holiday.");

	if(pg_num_rows($Ri)<1) {
		return "You have not been set up to use query management.<br>
		Please allocate yourself to a team.
		";
	}

	$crmdata=pg_fetch_array($Ri);

	if($crmdata['teamid']==0)  {
                return "You have not been set up to use query management.<br>
		Please allocate yourself to a team.
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='crms-allocate.php'>Allocate users to Teams</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr class='bg-odd'><td><a href='index.php'>My Business</a></td></tr>
		</table>";
	}

        $teams=explode("|",$crmdata['teams']);

	if(isset($cteam)) {
		$cteam+=0;
                $crmdata['teamid']=$cteam;
	}

        $teamsel="<select name=cteam onChange='javascript:document.form.submit();'>";

	$Sl="SELECT id,name FROM teams WHERE div='".USER_DIV."' ORDER BY name";
	$Ri=db_exec($Sl) or errDie("Unable to get data from teams.");

	if(pg_num_rows($Ri)<1) {
		return "There are no teams in the system.";
	}

	while($td=pg_fetch_array($Ri)) {
        	if(in_array($td['id'],$teams)) {
				if($td['id']==$crmdata['teamid']) {
					$sel="selected";
				} else {
					$sel="";
				}
			$teamsel.="<option value='$td[id]' $sel>$td[name]</option>";
		}
	}

	$teamsel.="</select>";

	$Sl="SELECT id,name,username,sub,lastdate,nextdate,opendate,teamid FROM tokens WHERE teamid=0 AND accnum='$crmdata[teamid]' ORDER BY id";
	$Ry=db_exec($Sl) or errDie("Unable to get data from system.");

	if(pg_numrows($Ry)<1) {
  $out= "<form action='".SELF."' method=post name=form>
	<h3>There are no unallocted queries for $teamsel</h3>
	</form>";
	} else {


		$i=0;

		$out="<form action='".SELF."' method=post name=form>
		<h3>Unallocated Queries for $teamsel</h3>
		</form>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>No.</th><th>Subject</th><th>User</th><th>Date Opened</th><th>Options</th></tr>";

		while($data=pg_fetch_array($Ry)) {

				$i++;

				$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

				$out.="<tr bgcolor='$bgcolor'><td>$data[id]</td><td>$data[name] - $data[sub]</td><td>$data[username]</td>
				<td>$data[opendate]</td><td><a href='tokens-allocate.php?id=$data[id]'>Allocate</a></td></tr>";

		}

		$out.="</table>";

	}

	$Sl="SELECT * FROM pokens WHERE teamid='$crmdata[teamid]' ORDER BY id";
	$Ry=db_exec($Sl) or errDie("Unable to get unallocated queries from system.");

	if(pg_numrows($Ry)>0) {

		$out.="<p><h3>Unallocated Email Queries</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th>Subject</th><th>Body</th><th>Date</th><th>Time</th><th>Options</th></tr>";

		while ($pdata=pg_fetch_array($Ry)) {
			$i++;

			$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

			$out.="<tr bgcolor='$bgcolor'><td>$pdata[sub]</td><td><pre>$pdata[notes]</pre></td><td>$pdata[rdate]</td>
			<td>".substr($pdata['rtime'],0,5)."</td><td><a href='tokens-new.php?poken=$pdata[id]'>Create Query</a></td></tr>";
		}

		$out.="</table>";
	}

	return $out;
}





?>
