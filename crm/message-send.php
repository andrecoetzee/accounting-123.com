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
require ("settings.php");

// remove all '
if ( isset($_POST) ) {
	foreach ( $_POST as $key => $value ) {
		$_POST[$key] = str_replace("'", "", $value);
	}
}
if ( isset($_GET) ) {
	foreach ( $_GET as $key => $value ) {
		$_GET[$key] = str_replace("'", "", $value);
	}
}

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "write":
			$OUTPUT = write_req ($_POST);
			break;
		default:
			$OUTPUT = get_req ();
	}
} else {
	$OUTPUT = get_req ();
}

// print  USER_NAME;
# display output

require ("template.php");
# enter new data
function get_req ()
{
	global $_GET;
	extract($_GET);
	if(!isset($id)) {
		return "Invalid use.";
	}

	db_conn('cubit');

	$users = "<select size=5 name=to[] style='width: 95%' multiple>";
	$sql = "SELECT username FROM users ORDER BY username";
	$ServRslt = db_exec ($sql) or errDie ("Unable to select users from database.");

	if (pg_numrows ($ServRslt) < 1) {
		return "No users found in database.";
	}

	// add the "all" user
	$users .= "<option value='_ALL_'>* All Users</option>";

	while ($namesA = pg_fetch_array ($ServRslt)) {
		$users .= "<option value='$namesA[username]'>$namesA[username]</option>\n";
	}

	$users .= "</select>\n";

	$get_req =
"
<h3>Send Message</h3>
<br>
<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=key value=write>
<input type=hidden name=id value='$id'>
<tr><th>For</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td align=center>
[CTRL] + Click to select more than one user<br>
$users
</td></tr>
<tr><th>Message</th></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td align=center><textarea cols=25 rows=4 name=des></textarea></td></tr>
<tr><td colspan=2 align=right><input type=submit value='Send &raquo;'></td></tr>
</form>
</table>
<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='index_reqs.php'>Messages</a></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>
";
        return $get_req;
}

# write new data
function write_req ($_POST)
{
	global $_SESSION;

	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	$user = $_SESSION["USER_NAME"];

	# validate input
	require_lib("validate");
	$v = new  validate ();

	if ( ! isset($to) )
		$v->addError("","No user specified");
	else {
		foreach ( $to as $arr => $arrval )
			$v->isOk ($arrval,"string", 1,200, "Invalid recipient: $arrval");
	}

	$v->isOk ($des,"string", 1,200, "Invalid description.");
	$v->isOk ($user,"string", 1,200, "Invalid user.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}

		return "$confirmCust</li></li>" . get_req();
	}

	$id+=0;
        $date=date("Y-m-d");
	$time=date("H:i:s");

	db_conn('cubit');

	if ( in_array("_ALL_", $to) ) {
		$to="";

		$rslt = db_exec("SELECT username FROM users");

		// if users found
		if ( pg_num_rows($rslt) > 0 ) {
			while ( $row = pg_fetch_array($rslt) ) {
				$to[]=$row["username"];
			}
		}
	}

	# write to db
	// create the list of users the messages should get sent to
	$msg_results="";
	foreach ( $to as $arr => $arrval ) {
		db_conn('cubit');

		$Sql = "INSERT INTO req (sender, recipient, message, timesent, viewed)
			VALUES ('$user','$arrval','$des',CURRENT_TIMESTAMP, 0)";

		$Rslt = db_exec ($Sql) or errDie ("Unable to add to database.", SELF);

		if (pg_cmdtuples ($Rslt) < 1) {
			return "Unable to access database.";
		} else {
			// if it isn't noticed that person has new messages, notify him
			$rslt = db_exec("SELECT * from req_new WHERE for_user='$arrval' ");
			if ( pg_num_rows($rslt) == 0 ) {
				db_exec("INSERT INTO req_new VALUES('$arrval')");
			}

			$msg_results .= "<tr class=datacell><td>Your message has been sent to $arrval</td></tr>";
		}
		
		db_conn('crm');
		$Sl="INSERT INTO token_actions (token,action,donedate,donetime,doneby,donebyid)
		VALUES ('$id','Sent message to $arrval','$date','$time','".USER_NAME."','".USER_ID."')";
		$Ry=db_exec($Sl) or errDie("Unable to insert query action.");
	}

	$OUTPUT .= "<script> window.opener.parent.mainframe.location.reload(); window.close(); </script>";
	return $OUTPUT;

	$write_req =
	"
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>Message proccessed</th></tr>
	$msg_results
	</table>";

	return $write_req;
}
?>
