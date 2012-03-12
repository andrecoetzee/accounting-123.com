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
require ("../settings.php");

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

require ("gw-tmpl.php");




function get_req ()
{

	db_conn('cubit');


	$sql = "SELECT username FROM users ORDER BY username";
	$ServRslt = db_exec ($sql) or errDie ("Unable to select users from database.");
	if (pg_numrows ($ServRslt) < 1) {
		return "No users found in database.";
	}

	$users = "<select size=5 name=to[] style='width: 95%' multiple>";
	$users .= "<option value='_ALL_'>* All Users</option>";
	while ($namesA = pg_fetch_array ($ServRslt)) {
		$users .= "<option value='$namesA[username]'>$namesA[username]</option>\n";
	}
	$users .= "</select>\n";

	$get_req = "
		<h3>Send Message</h3>
		<br>
		<table cellpadding='2' cellspacing='0' class='shtable'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write'>
			<tr>
				<th>For</th>
			</tr>
			<tr class='odd'>
				<td align='center'>
					[CTRL] + Click to select more than one user<br>
					$users
				</td>
			</tr>
			<tr>
				<th>Message</th>
			</tr>
			<tr class='even'>
				<td align='center'><textarea cols='25' rows='4' name='des'></textarea></td>
			</tr>
		</table>
			<p></p>
			<input type='submit' value='Send &raquo;'>
		</form>
		<p>
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='even'>
				<td><a href='req_gen.php'>Add Message</a></td>
			</tr>
			<tr class='odd'>
				<td><a href='view_req.php'>View Messages</a></td>
			</tr>
		</table>";
	return $get_req;

}




# write new data
function write_req ($_POST)
{

	global $_SESSION;

	# get vars
	extract ($_POST);

	$user = $_SESSION["USER_NAME"];

	# validate input
	require_lib("validate");
	$v = new validate ();

	if ( ! isset($to) )
		$v->addError("","No user specified");
	else {
		foreach ( $to as $arr => $arrval )
			$v->isOk ($arrval,"string", 1,200, "Invalid recipient: $arrval");
	}

//	$v->isOk ($des,"string", 1,200, "Invalid message.");
	$v->isOk ($des,"text", 1,200, "Invalid message.");
	$v->isOk ($user,"string", 1,200, "Invalid user.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirmCust.get_req();
	}

    db_conn('cubit');

	// if should send to all, clear the $to list, and add all users
	// it is cleared just incase sum1 selected All option together with another one
	// since this could cause the same message sent to the same users twice!!!!
	if ( in_array("_ALL_", $to) ) {
		$to = "";
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
		$Sql = "
			INSERT INTO req (
				sender, recipient, message, timesent, viewed
			) VALUES (
				'$user', '$arrval', '$des', CURRENT_TIMESTAMP, 0
			)";

		$Rslt = db_exec ($Sql) or errDie ("Unable to add to database.", SELF);

		if (pg_cmdtuples ($Rslt) < 1) {
			return "Unable to access database.";
		} else {
			// if it isn't noticed that person has new messages, notify him
			$rslt = db_exec("SELECT * from req_new WHERE for_user='$arrval' ");
			if ( pg_num_rows($rslt) == 0 ) {
				db_exec("INSERT INTO req_new VALUES('$arrval')");
			}

			$msg_results .= "<tr class='datacell'><td>Your message has been sent to $arrval</td></tr>";
		}
	}

	$write_req = "
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th>Message proccessed</th>
			</tr>
			<tr class='even'>
				<td>$msg_results</td>
			</tr>
		</table>
		<p>
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='odd'>
				<td><a href='".SELF."'>Send another message</a></td>
			</tr>
			<tr class='odd'>
				<td><a href='view_req.php'>View Messages</a></td>
			</tr>
		</table>";
	return $write_req;

}



?>