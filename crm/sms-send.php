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
require("https_urlsettings.php");

if(isset($_POST["key"])) {
	switch($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "send":
			$OUTPUT = send($_POST);
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
		return "Invalid query.";
	}

	$tokendata=pg_fetch_array($Ry);

	$out="<h3>Enter SMS data</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='confirm'>
	<input type=hidden name=id  value='$id'>
	<tr><th colspan=2>SMS details</th></tr>
	<tr class='bg-odd'><td>To</td><td><input type=text name=to value='$tokendata[cell]'></td></tr>
	<tr><th colspan=2>Text</th></tr>
	<tr class='bg-even'><td colspan=2><textarea name=text cols=20 rows=4></textarea></td></tr>
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

	$out="<h3>Enter SMS data</h3>
	$errors
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='confirm'>
	<input type=hidden name=id  value='$id'>
	<tr><th colspan=2>SMS details</th></tr>
	<tr class='bg-odd'><td>To</td><td><input type=text name=to value='$to'></td></tr>
	<tr><th colspan=2>Text</th></tr>
	<tr class='bg-even'><td colspan=2><textarea name=text cols=20 rows=4>$text</textarea></td></tr>
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
	$v->isOk ($to, "num", 1, 20, "Invalid cell no.");
	$v->isOk ($text, "string", 1, 150, "Invalid sms text.");

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

	$out="<h3>Confirm SMS data</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value='send'>
	<input type=hidden name=id  value='$id'>
	<input type=hidden name=text value='$text'>
	<tr><th colspan=2>SMS details</th></tr>
	<tr class='bg-odd'><td>To</td><td><input type=hidden name=to value='$to'>$to</td></tr>
	<tr><th colspan=2>Text</th></tr>
	<tr class='bg-even'><td colspan=2><pre>$text</pre></td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Send &raquo;'></td></tr>
	</form>
	</table>";
	
	return $out;
}

function send($_POST) {
	extract($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($id, "num", 1, 9, "Invalid Query ID.");
	$v->isOk ($to, "num", 1, 20, "Invalid cell no.");
	$v->isOk ($text, "string", 1, 150, "Invalid sms text.");

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

	$text_enc = base64_encode($text);
	$request = @file( urler(GENERALMSG_URL."?cellnum=$to&message=$text_enc&".sendhash()));

	if ( $request == false ) {
		return "<li class=err>Connection failed. Check your internet connection and try again.</li>";
	}
	
	db_conn('crm');
	
	$Sl="INSERT INTO token_actions(token,action,donedate,donetime,doneby,donebyid)
	VALUES ('$id','Sent SMS','$date','$time','".USER_NAME."','".USER_ID."')";
	$Ry=db_exec($Sl) or errDie("Unable to insert record.");

	$OUTPUT .= "<script> window.opener.parent.mainframe.location.reload(); window.close(); </script>";
	return $OUTPUT;

	$out="<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>SMS Sent</th></tr>
	<tr class='bg-odd'><td>".implode("", $request)."</td></tr>
	</table>";

	return $out;
}

?>


















