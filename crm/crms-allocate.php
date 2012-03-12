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
//require_once("libs/ext.lib.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = "Invalid";
	}
} else {
	$OUTPUT = enter();
}

$OUTPUT.="<p>
	<table border=0 cellpadding='2' cellspacing='1'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index.php'>My Business</a></td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

require("template.php");

function enter() {

	db_conn('cubit');
	$Sl="SELECT userid,username FROM users WHERE div='".USER_DIV."' ORDER BY username";
	$Ry=db_exec($Sl) or errDie("Unable to get users from db.");

	$i=0;

	$out="<h3>Allocate Users to Teams</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='confirm'>
	<tr><th>Username</th><th>Team</th></tr>";

	db_conn('crm');

	while($data=pg_fetch_array($Ry)) {
		$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;
		$userid=$data['userid'];
		
		$Sl="SELECT teamid FROM crms WHERE userid='$userid'";
		$Rq=db_exec($Sl) or errDie("Unable to get data from db.");

		if(pg_numrows($Rq)<1) {
			$crmdata['teamid']=0;
		} else {
			$crmdata=pg_fetch_array($Rq);
		}

		$Sl="SELECT id,name FROM teams WHERE div='".USER_DIV."' ORDER BY name";
		$Ri=db_exec($Sl) or errDie("Unable to get data form.");

		if(pg_num_rows($Ri)<1) {
			return "There are no teams in the system. Please add them under settings";
		}

		$teams="<select name=team[$userid]>
		<option value='0'>Not Active</option>";

                while($td=pg_fetch_array($Ri)) {
                	if($td['id']==$crmdata['teamid']) {
				$sel="selected";
			} else {
				$sel="";
			}

			$teams.="<option value='$td[id]' $sel>$td[name]</option>";
		}

		$teams.="</select>";

		$out.="<tr bgcolor='$bgcolor'><td>$data[username]</td><td>$teams</td></tr>";
		
		$i++;

	}

	$out.="<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>";

	return $out;

}

function confirm($_POST) {
	extract($_POST);

	db_conn('cubit');
	$Sl="SELECT userid,username FROM users WHERE div='".USER_DIV."' ORDER BY username";
	$Ry=db_exec($Sl) or errDie("Unable to get users from db.");

	$i=0;

	$out="<h3>Allocate Users to Teams</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='write'>
	<tr><th>Username</th><th>Team</th></tr>";

	db_conn('crm');

	while($data=pg_fetch_array($Ry)) {
		$bgcolor=($i%2) ? TMPL_tblDataColor1 : TMPL_tblDataColor2;
		$userid=$data['userid'];
		$team[$userid]+=0;

                if($team[$userid]!=0) {
			$Sl="SELECT name FROM teams WHERE id='$team[$userid]'";
			$Rx=db_exec($Sl) or errDie("Unable to get teams from db.");

			$teamdata=pg_fetch_array($Rx);
		} else {
			$teamdata['name']="Not Active";
		}

		$out.="<tr bgcolor='$bgcolor'><td>$data[username]</td><td>$teamdata[name]<input type=hidden name='team[$userid]' value='$team[$userid]'></td></tr>";

		$i++;

	}

	$out.="<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>";

	return $out;

}

function write($_POST) {
	extract($_POST);

	db_conn('cubit');
	$Sl="SELECT userid,username FROM users WHERE div='".USER_DIV."' ORDER BY username";
	$Ry=db_exec($Sl) or errDie("Unable to get users from db.");

	db_conn('crm');

	while($data=pg_fetch_array($Ry)) {
		$userid=$data['userid'];
		$team[$userid]+=0;

		$Sl="SELECT id,teams FROM crms WHERE userid='$userid'";
		$Rx=db_exec($Sl) or errDie("Unable to get crm from db.");

		if(pg_num_rows($Rx)>0) {

			$crmdata=pg_fetch_array($Rx);

			$cteams=explode("|",$crmdata['teams']);

			if(!(in_array($team[$userid],$cteams))) {
				$newteams=$crmdata['teams'].$team[$userid]."|";
			} else {
				$newteams=$crmdata['teams'];
			}
		} else {
			$newteams=$team[$userid]."|";
		}

		if($team[$userid]==0) {
			$newteams="";
		}

		if(pg_num_rows($Rx)<1) {
			$Sl="INSERT INTO crms (name,userid,teamid,div,teams) VALUES ('$data[username]','$userid','$team[$userid]','".USER_DIV."','$newteams')";
			$Rx=db_exec($Sl) or errDie("Unable to insert crm.");
		} else {
			$Sl="UPDATE crms SET name='$data[username]',teamid='$team[$userid]',teams='$newteams' WHERE userid='$userid'";
			$Rx=db_exec($Sl) or errDie("Unable to update crm.");
		}
	}

	$out="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Users Allocated</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>The Users have been allocated to teams</td></tr>
	</table>";

	return $out;

}

?>
