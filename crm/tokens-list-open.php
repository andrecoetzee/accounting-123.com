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
	<tr bgcolor='".TMPL_tblDataColor2."'><td align=center><a href='index.php'>My Business</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td align=center><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

require("template.php");

function list_tokens(){

        global $HTTP_POST_VARS;
	extract($HTTP_POST_VARS);

	db_conn('crm');
        $Sl="SELECT * FROM crms WHERE userid='".USER_ID."'";
	$Ri=db_exec($Sl) or errDie("Unable to go on holiday.");

	if(pg_num_rows($Ri)<1) {
		return "You have not been set up to use query management.<br>
		Please allocate yourself to a team.
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='crms-allocate.php'>Allocate users to Teams</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";
	}

	$crmdata=pg_fetch_array($Ri);

	if($crmdata['teamid']==0)  {
                return "You have not been set up to use query management.<br>
		Please allocate yourself to a team.
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='crms-allocate.php'>Allocate users to Teams</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
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


	$Sl="SELECT id,name,username,sub,lastdate,nextdate,opendate,teamid FROM tokens WHERE teamid='$crmdata[teamid]' ORDER BY id";
	$Ry=db_exec($Sl) or errDie("Unable to get data from system.");
	
	if(pg_numrows($Ry)<1) {
		return "<form action='".SELF."' method=post name=form>
		There are no open queries for $teamsel.
		</form>";
	}
	

	$i=0;

	$out="<form action='".SELF."' method=post name=form>
	<h3>All Open Queries for $teamsel</h3>
	</form>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>No.</th><th>Subject</th><th>User</th><th>Date Opened</th><th>Last Date</th><th>Next Date</th>
	<th>Options</th></tr>";

	while($data=pg_fetch_array($Ry)) {

		//if(in_array($data['teamid'],$teams)) {

			$i++;

			$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;

			$out.="<tr bgcolor='$bgcolor'><td>$data[id]</td><td>$data[name] - $data[sub]</td><td>$data[username]</td>
			<td>$data[opendate]</td><td>$data[lastdate]</td><td>$data[nextdate]</td>
			<td><a href='tokens-manage.php?id=$data[id]'>Open</a></td></tr>";

		//}

	}

	$out.="</table>";

	return $out;
}





?>
