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
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
			$OUTPUT = write($_POST);
			break;
		default:
			$OUTPUT = "Invalid.";
	}
} elseif(isset($_GET["id"])) {
	$OUTPUT = enter($_GET);
} else {
	$OUTPUT = "Invalid.";
}

require("template.php");

function enter($_GET) {
	extract($_GET);
	
	$id+=0;
	
	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query from system.");
	
	if(pg_numrows($Ry)<1) {
		return "Invalid query.";
	}
	
	$tokendata=pg_fetch_array($Ry);

	$Sl="SELECT id,name FROM crms WHERE userid!='$tokendata[userid]'";
	$Ry=db_exec($Sl) or errDie("Unable to get crms from system.");

	if(pg_numrows($Ry)<1) {
		return "There are no other users that are crm enabled.";
	}

	$crms="<select name=crm>
	<option value='0' selected>Select User</option>";

	while($cdata=pg_fetch_array($Ry)) {
		$crms.="<option value='$cdata[id]'>$cdata[name]</option>";
	}

	$crms.="</select>";

	$teams="<select name=team>
	<option value='0' selected>Select Team</option>";

	$Sl="SELECT id,name FROM teams ORDER BY name";
	$Ri=db_exec($Sl) or errDie("Unable to get data.");

	while($data=pg_fetch_array($Ri)) {
		$teams.="<option value='$data[id]'>$data[name]</option>";
	}

	$teams.="</select>";

	$out="<h3>Pass query</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='confirm'>
	<input type=hidden name=id value='$id'>
	<tr><th colspan=2>Select new user</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Users</td><td>$crms</td></tr>
        <tr><th colspan=2 align=center>OR</th></tr>
	<tr><th colspan=2>Select new team</th></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Teams</td><td>$teams</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Archive Actions</td><td><input type=checkbox name=archive></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>";

	return $out;
}

function confirm($_POST) {
	extract($_POST);

	$id+=0;
	$crm+=0;
	$team+=0;
	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query data from db.");

	if(pg_numrows($Ry)<1) {
		return "Invalid query.";
	}

        if((($crm==0)&&($team==0))||(($crm!=0)&&($team!=0))) {
		return "Please select a user OR a team.".enter($_POST);
	}

        if($crm!=0) {
		$Sl="SELECT * FROM crms WHERE id='$crm'";
		$Ri=db_exec($Sl) or errDie("Unable to get crm from system.");

		if(pg_num_rows($Ry)<1) {
			return "Invalid crm";
		}

	} else {
		$Sl="SELECT * FROM teams WHERE id='$team'";
		$Ri=db_exec($Sl) or errDie("Unable to get data.");

		if(pg_num_rows($Ry)<1) {
			return "Invalid team";
		}

	}

	$data=pg_fetch_array($Ri);

	if(isset($archive)) {
		$archive="Yes<input type=hidden name=archive value=''>";
	} else {
		$archive="No";
	}

	$out="<h3>Pass query</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='write'>
	<input type=hidden name=id value='$id'>
	<input type=hidden name=crm value='$crm'>
	<input type=hidden name=team value='$team'>
	<tr><th colspan=2>Confirm new user</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Send to</td><td>$data[name]</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Archive</td><td>$archive</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>";

	return $out;
}


function write($_POST) {

	extract($_POST);

	$id+=0;
	$crm+=0;
	$team+=0;
	$user=USER_NAME;
	$date=date("Y-m-d");
	$time=date("H:i:s");

	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query data from db.");

	if(pg_numrows($Ry)<1) {
		return "Invalid query.";
	}

	$tokendata=pg_fetch_array($Ry);

        if(isset($archive)) {
		$Sl="SELECT * FROM token_actions WHERE token='$id'";
		$Ri=db_exec($Sl) or errDie("Unable to get actions from db.");

		while($data=pg_fetch_array($Ri)) {
			$Sl="INSERT INTO archived_actions (token,action,donedate,donetime,doneby,donebyid)
			VALUES ('$id','$data[action]','$data[donedate]','$data[donetime]','$data[doneby]','$data[donebyid]')";
			$Ro=db_exec($Sl) or errDie("Unable to archive action.");
		}

		$Sl="DELETE FROM token_actions WHERE token='$id'";
		$Rl=db_exec($Sl) or errDie("Unable to delete actions.");
	}

	if($crm>0) {

		$Sl="SELECT * FROM crms WHERE id='$crm'";
		$Ry=db_exec($Sl) or errDie("Unable to get crm from system.");

		if(pg_numrows($Ry)<1) {
			return "Invalid crm";
		}

		$crmdata=pg_fetch_array($Ry);

		$Sl="UPDATE tokens SET username='$crmdata[name]',userid='$crmdata[userid]',teamid='$crmdata[teamid]' WHERE id='$id'";
		$Ry=db_exec($Sl) or errDie("Unable to update query.");

		$Sl="INSERT INTO token_actions (token,action,donedate,donetime,doneby,donebyid)
		VALUES ('$id','Query passed from $tokendata[username] to $crmdata[name]','$date','$time','".USER_NAME."','".USER_ID."')";
		$Ry=db_exec($Sl) or errDie("Uable to insert action.");

	} else {

		$Sl="SELECT * FROM teams WHERE id='$team'";
		$Ry=db_exec($Sl) or errDie("Unable to get crm from system.");

		if(pg_numrows($Ry)<1) {
			return "Invalid crm";
		}

		$teamdata=pg_fetch_array($Ry);

		$Sl="UPDATE tokens SET username='Unallocated',userid='0',teamid='0',accnum='$team' WHERE id='$id'";
		$Ry=db_exec($Sl) or errDie("Unable to update query.");

		$Sl="INSERT INTO token_actions (token,action,donedate,donetime,doneby,donebyid)
		VALUES ('$id','Query passed from $tokendata[username] to $teamdata[name]','$date','$time','".USER_NAME."','".USER_ID."')";
		$Ry=db_exec($Sl) or errDie("Uable to insert action.");
	}


	$OUTPUT = "<script> window.opener.parent.mainframe.location.reload(); window.close(); </script>";
	return $OUTPUT;

	$out="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Query passed</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Query passed from $tokendata[username] to $crmdata[name] by $user</td></tr>
	</table>";

	return $out;
}


?>
