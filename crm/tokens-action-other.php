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
		case "confirm":
			$OUTPUT = confirm($HTTP_POST_VARS);
			break;
		case "write":
			$OUTPUT = write($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = "Invalid";
	}
} elseif(isset($HTTP_GET_VARS["id"])) {
	$OUTPUT = enter($HTTP_GET_VARS);
} else {
	$OUTPUT = "Invalid";
}

require("template.php");

function enter($HTTP_GET_VARS) {
	extract($HTTP_GET_VARS);

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
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Action</td><td><input type=text name=action value='' size=20></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>";

	return $out;
}

function error($HTTP_POST_VARS,$errors="") {
	extract($HTTP_POST_VARS);

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
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Action</td><td><input type=text name=action value='$action' size=20></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
	</form>
	</table>";

	return $out;
}

function confirm($HTTP_POST_VARS) {
	extract($HTTP_POST_VARS);

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
		return error($HTTP_POST_VARS, $confirm."</li>");
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
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Action</td><td>$action</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>";
	
	return $out;
}

function write($HTTP_POST_VARS) {
	extract($HTTP_POST_VARS);

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
		return error($HTTP_POST_VARS, $confirm."</li>");
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
	<tr bgcolor='".TMPL_tblDataColor1."'><td>$action recorded</td></tr>
	</table>";

	return $out;
}

?>


















