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

	$OUTPUT .="<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<script>document.write(getQuicklinkSpecial());</script>
	<tr class='bg-odd'><td><a href='index.php'>My Business</a></td></tr>
	<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

require("template.php");

function enter($_GET) {
	extract($_GET);

	$id+=0;

	db_conn("crm");
	$Sl="SELECT * FROM crms WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get data.");

        if(pg_num_rows($Ry)<1) {
		return "Invalid user.";
	}

	$crmdata=pg_fetch_array($Ry);

	if($crmdata['teamid']==0) {
		return "This user is set to 'Non Active' and you cannot select mutple teams for him. Please select a team under settings 'Select default teams'";
	}

	$tar=explode("|",$crmdata['teams']);

	$i=0;
	$Sl="SELECT * FROM teams WHERE div='".USER_DIV."' ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to get data from teams.");

	$out="<h3>Select teams for this user.</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='write'>
	<input type=hidden name=id value='$id'>
	<tr><th>Teams</th><th>Select</th></tr>";

	while($data=pg_fetch_array($Ry)) {
        	$tid=$data['id'];

		if(in_array($data['id'],$tar)) {
			$ch="checked";
		} else {
			$ch="";
		}

		$out.="<tr class='bg-odd'><td>$data[name]</td><td><input type=checkbox name=team[$tid] $ch></td></tr>";

	}

	$out.="<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>";

	return $out;

}

function write($_POST) {
	extract($_POST);

	$id+=0;

	db_conn("crm");
	$Sl="SELECT * FROM crms WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get data.");

        if(pg_num_rows($Ry)<1) {
		return "Invalid user.";
	}

	$crmdata=pg_fetch_array($Ry);

	if($crmdata['teamid']==0) {
		return "This user is set to 'Non Active' and you cannot select mutple teams for him. Please select a team under settings 'Select default teams'";
	}

	$tar=explode("|",$crmdata['teams']);

	$i=0;
	$Sl="SELECT * FROM teams WHERE div='".USER_DIV."' ORDER BY name";
	$Ry=db_exec($Sl) or errDie("Unable to get data from teams.");

        $tv="";

	while($data=pg_fetch_array($Ry)) {
                $tid=$data['id'];

		$tid+=0;

		if(isset($team[$tid])) {
			$tv.="$tid|";
		}
	}

	$tvs = explode("|",$tv);

	if(!(in_array($crmdata['teamid'],$tvs))) {
		return "You cannot remove a team from a user if it is still set as his default. Change the default teams for a user under settings.";
	}

	$Sl="UPDATE crms SET teams='$tv' WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to update.");

	$out="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Done</th></tr>
	<tr class='bg-odd'><td>Your changes have been saved.</td></tr>
	</table>";

	return $out;

}

?>
