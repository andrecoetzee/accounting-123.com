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
$OUTPUT = list_messages ();

require("gw-tmpl.php");



function list_messages ()
{
	global $_GET;
	$Display="";
	$PDisplay="";

	if ( isset($_GET["key"]) && isset($_GET["id"]) ) {
		// if we should read, read
		if ( $_GET["key"] == "view" ) {
			$rslt = db_exec("
				SELECT sender, message, EXTRACT(month from timesent) as month, 
					EXTRACT(day from timesent) as day, EXTRACT(year from timesent) as year, 
					EXTRACT(hour from timesent) as hour, EXTRACT(minute from timesent) as minute 
				FROM req 
				WHERE id='$_GET[id]'");

			if ( pg_num_rows($rslt) > 0 ) {
				$row = pg_fetch_array($rslt);

				$time = date("j F, Y  -  H:i", mktime($row["hour"],$row["minute"],0,$row["month"],$row["day"],$row["year"]));

				$PDisplay .= "
					<h3>Output</h3>
					<table cellpadding='2' cellspacing='0' class='shtable'>
						<tr>
							<td width='50'>Sender</td>
							<td width='200'>$row[sender]</td>
						</tr>
						<tr>
							<td width='50'>Time Sent:</td>
							<td width='200'>$time</td>
						</tr>
						<tr>
							<td width='50'>Message:</td>
							<td width='200'>$row[message]</td>
						</tr>
					</table>
					<br>";

				// mark as read
				db_exec("UPDATE req SET viewed='1' WHERE id='$_GET[id]'");
			}
		}

		// if we should delete... delete
		if ( $_GET["key"] == "del" ) {
			$rslt = db_exec("DELETE FROM req WHERE id='$_GET[id]'");

			if ( pg_cmdtuples($rslt) > 0 ) {
				$PDisplay.="<h3>Output</h3>Message Successfully Deleted.<br><br>";
			}
		}
	}

	$user= USER_NAME;

	// clear the message notify que
	db_exec("DELETE FROM req_new WHERE for_user='$user' ");
	db_exec("UPDATE req SET alerted='1' WHERE recipient='$user'");

 // $dep =USER_DPT;
        db_conn('cubit');
         $n = 0;

        $Sql = "
		SELECT id,sender,message,reference, 
			EXTRACT(month from timesent) as month,
			EXTRACT(day from timesent) as day, 
			EXTRACT(year from timesent) as year,viewed
		FROM req 
		WHERE recipient='$user' ORDER BY timesent";
        $Exs = db_exec ($Sql) or errDie ("Unable to select cases from database.");
         if (pg_numrows($Exs) < 1) {
		return "
			<table cellpadding='2' cellspacing='0' class='shtable'>
				<tr>
					<th>No Outstanding Messages</th>
				</tr>
				<tr class='odd'>
					<td>You have no outstanding messages</td>
				</tr>
			</table>
			<p><p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='even'>
					<td><a href='req_gen.php'>Add Message</a></td>
				</tr>
				<tr class='odd'>
					<td><a href='../doc-index.php'>Main Menu</a></td>
				</tr>
			<table>";
	}

        while( $depts = pg_fetch_array($Exs) ) {
		$Date = date("j F, Y", mktime(0,0,0,$depts["month"],$depts["day"],$depts["year"]));

		$n = $n + 1;
		$msgid = $depts['id'];

		// created the new msg cell data
		if ( $depts["viewed"] == '0' ) {
			$newmsg = "<li>&nbsp</li>";
		} else {
			$newmsg = "&nbsp;";
		}

			$Display .= "
				<tr class='even'>
					<td align='center'>$newmsg</td>
					<td>$Date</td><td>$depts[sender]</td>
					<td>$depts[message]</td>
					<td><a href='".SELF."?key=view&id=$msgid'>view</a> / <a href='".SELF."?key=del&id=$msgid'>delete</a></td>
				</tr>";
         }

	$list_messages = "
		$PDisplay
		<h3>Messages for $user</h3>
		<br>
		<table cellpadding='2' cellspacing='0' class='shtable'>
			<tr>
				<th>New</th>
				<th>Date sent</th>
				<th>From</th>
				<th>Details</th>
				<th>Option</th>
			</tr>
			$Display
			<tr>
				<th colspan='7' align='right'>Total messages: $n</th>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='even'>
				<td><a href='req_gen.php'>Add Message</a></td>
			</tr>
		</table>";

	return $list_messages;

}

?>