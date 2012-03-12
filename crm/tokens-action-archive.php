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

	$Sl="SELECT * FROM token_actions WHERE token='$id'";
	$Ri=db_exec($Sl) or errDie("Unable to get actions from db.");

	while($data=pg_fetch_array($Ri)) {
		$Sl="INSERT INTO archived_actions (token,action,donedate,donetime,doneby,donebyid)
		VALUES ('$id','$data[action]','$data[donedate]','$data[donetime]','$data[doneby]','$data[donebyid]')";
                $Ro=db_exec($Sl) or errDie("Unable to archive action.");
	}

	$Sl="DELETE FROM token_actions WHERE token='$id'";
	$Rl=db_exec($Sl) or errDie("Unable to delete actions.");

	$OUTPUT = "<script> window.opener.parent.mainframe.location.reload(); window.close(); </script>";
	return $OUTPUT;



}

?>
