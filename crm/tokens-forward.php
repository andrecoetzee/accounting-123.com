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
	$date=date("Y-m-d");

	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get token from system.");

	if(pg_numrows($Ry)<1) {
		return "Invalid token.";
	}

	$tokendata=pg_fetch_array($Ry);

	if($tokendata['nextdate']<=$date) {
		$day=date("d");
		$mon=date("m");
		$year=date("Y");
	} else {
		$datearr=explode("-",$tokendata['nextdate']);
		$day=$datearr[2];
		$mon=$datearr[1];
		$year=$datearr[0];
	}

	$out="<h3>Forward query</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='confirm'>
	<input type=hidden name=id value='$id'>
	<tr><th colspan=2>Select date</th></tr>
	<tr class='bg-odd'><td>Date</td>
	<td>
		<table border=0 cellpadding=0 cellspacing=0>
		<tr><td><input type=text size=2 name=day value='$day'>-</td>
		<td><input type=text size=2 name=month value='$mon'>-</td>
		<td><input type=text size=4 name=year value='$year'></td>
		</tr>
		</table>
	</td>
	</tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>";

	return $out;
}

function confirm($_POST) {
	extract($_POST);

	$date = $day."-".$month."-".$year;

	if(!checkdate($month, $day, $year)){
		return "<li class=err>Invalid date</li>".enter($_POST);
	}
	
	$id+=0;
	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get token data from db.");

	if(pg_numrows($Ry)<1) {
		return "Invalid query.";
	}

	$out="<h3>Forward query</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='write'>
	<input type=hidden name=id value='$id'>
	<input type=hidden name=day value='$day'>
	<input type=hidden name=month value='$month'>
	<input type=hidden name=year value='$year'>
	<tr><th colspan=2>Confirm date</th></tr>
	<tr class='bg-odd'><td>Date</td><td>$date</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>";

	return $out;
}


function write($_POST) {

	extract($_POST);

	$nextdate = $year."-".$month."-".$day;

	if(!checkdate($month, $day, $year)){
		return "<li class=err>Invalid date</li>".enter($_POST);
	}

	$id+=0;
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

	$Sl="UPDATE tokens SET nextdate='$nextdate' WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to update query.");

	$Sl="INSERT INTO token_actions (token,action,donedate,donetime,doneby,donebyid)
	VALUES ('$id','Query forwarded to $nextdate','$date','$time','".USER_NAME."','".USER_ID."')";
	$Ry=db_exec($Sl) or errDie("Uable to insert action.");

	$OUTPUT = "<script> window.opener.parent.mainframe.location.reload(); window.close(); </script>";
	return $OUTPUT;

	$out="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Query forwarded</th></tr>
	<tr class='bg-odd'><td>Query forwarded to $nextdate by $user</td></tr>
	</table>";

	return $out;
}


?>
