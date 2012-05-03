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
			$OUTPUT = "Invalid";
	}
} elseif(isset($_GET["id"])) {
	$OUTPUT = enter($_GET);
} else {
	$OUTPUT = "Invalid";
}

require("template.php");

function enter($_GET) {
	extract($_GET);

	$id+=0;
	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query data from db.");

	if(pg_numrows($Ry)<1) {
		return "Invalid Query.";
	}

	$tokendata=pg_fetch_array($Ry);

	$out="<h3>Enter other Action</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='confirm'>
	<input type=hidden name=id  value='$id'>
	<tr><th colspan=2>Action details</th></tr>
	<tr class='bg-odd'><td>Action</td><td><input type=text name=action value='' size=20></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>";

	return $out;
}

function error($_POST,$errors="") {
	extract($_POST);

	$id+=0;
	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query data from db.");

	if(pg_numrows($Ry)<1) {
		return "Invalid query.";
	}

	$tokendata=pg_fetch_array($Ry);

	$out="<h3>Enter other Action</h3>
	$errors
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='confirm'>
	<input type=hidden name=id  value='$id'>
	<tr><th colspan=2>Action details</th></tr>
	<tr class='bg-odd'><td>Action</td><td><input type=text name=action value='$action' size=20></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>";

	return $out;
}

function confirm($_POST) {
	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 9, "Invalid Query ID.");
	$v->isOk ($action, "string", 1, 150, "Invalid action.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return error($_POST, $confirm."</li>");
	}

	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query data from db.");

	if(pg_numrows($Ry)<1) {
		return "Invalid query.";
	}

	$out="<h3>Confirm Action</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='write'>
	<input type=hidden name=id  value='$id'>
	<input type=hidden name=action value='$action'>
	<tr><th colspan=2>Action details</th></tr>
	<tr class='bg-odd'><td>Action</td><td>$action</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>";
	
	return $out;
}

function write($_POST) {
	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 9, "Invalid Query ID.");
	$v->isOk ($action, "string", 1, 150, "Invalid action.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return error($_POST, $confirm."</li>");
	}

	db_conn('crm');
	$Sl="SELECT * FROM tokens WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get query data from db.");

	if(pg_numrows($Ry)<1) {
		return "Invalid query.";
	}
	$time=date("H:i:s");
	$date=date("Y-m-d");

	$Sl="INSERT INTO token_actions(token,action,donedate,donetime,doneby,donebyid)
	VALUES ('$id','$action','$date','$time','".USER_NAME."','".USER_ID."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert record.");

	$out="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Action Recorded</th></tr>
	<tr class='bg-odd'><td>$action recorded</td></tr>
	</table>";

	return $out;
}

?>


















