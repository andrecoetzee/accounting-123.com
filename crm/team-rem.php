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

if(isset($HTTP_POST_VARS["key"])) {
	switch($HTTP_POST_VARS["key"]) {
		case "remove":
			$OUTPUT = remove($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = "Invalid use of script";
	}
} elseif(isset($HTTP_GET_VARS["id"])) {
	$OUTPUT=confirm($HTTP_GET_VARS);
} else {
	$OUTPUT = "Invalid use of script.";
}

$OUTPUT .= "
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='team-add.php'>Add Cubit Team</a></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='team-list.php'>View Cubit Teams</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
					<tr bgcolor='".bgcolorg()."'>
						<td><a href='index.php'>My Business</a></td>
					</tr>
				</table>";

require("template.php");



function confirm($HTTP_GET_VARS)
{

	extract($HTTP_GET_VARS);
	$id+=0;

	db_conn('crm');
	$Sl="SELECT * FROM teams WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get team info.");
	
	if(pg_numrows($Ry)<1) {
		return "Invalid team.";
	}

	$teamdata=pg_fetch_array($Ry);

	$Sl="SELECT * FROM crms WHERE div='".USER_DIV."'";
	$Ry=db_exec($Sl) or errDie("Unable to get data.");

	while($cdata=pg_fetch_array($Ry)) {
		$teams=explode("|",$cdata['teams']);

		if(in_array($id,$teams)) {
			return "You Cannot remove this team, $cdata[name] is still allocated to it.";
		}

	}

	$Sl="SELECT * FROM crms WHERE div='".USER_DIV."' AND teamid='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get data.");

        if(pg_num_rows($Ry)>0) {
        	$cdata=pg_fetch_array($Ry);

		return "You Cannot remove this team, $cdata[name] still has it set as its default.";
	}



	$out = "
				<h3>Remove Cubit Team</h3>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='remove'>
					<input type='hidden' name='id' value='$id'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Team Details</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Team Name</td>
						<td>$teamdata[name]</td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Description</td>
						<td>$teamdata[des]</td>
					</tr>
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Remove &raquo;'></td>
					</tr>
				</form>
				</table>";
	return $out;

}



function remove($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);
	
	$id+=0;

	db_conn('crm');

	$Sl="SELECT * FROM crms WHERE div='".USER_DIV."'";
	$Ry=db_exec($Sl) or errDie("Unable to get data.");

	while($cdata=pg_fetch_array($Ry)) {
		$teams=explode("|",$cdata['teams']);

		if(in_array($id,$teams)) {
			return "You Cannot remove this team, $cdata[name] is still allocated to it.";
		}

	}

	$Sl="DELETE FROM teams WHERE id='$id' AND div='".USER_DIV."'";
	$Ry=db_exec($Sl) or errDie("Unable to insert team into db.");
	
	db_conn('cubit');
	$Sl="UPDATE mail_accounts SET crmteam='0' WHERE crmteam='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to update mail account.");
	
	$out = "
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Cubit Team Removed</th>
					</tr>
					<tr class='datacell'>
						<td>Cubit Team has been successfully removed from the system.</td>
					</tr>
				</table>";
	return $out;

}



?>