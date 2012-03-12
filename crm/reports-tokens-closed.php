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
		case "seltoken":
			$OUTPUT = seltoken();
			break;
		case "report":
			$OUTPUT = report($_POST);
			break;
		case "find":
			$OUTPUT = find($_POST);
			break;
		default:
			$OUTPUT = "Invalid";
	}
} else {
	$OUTPUT = seltoken();
}

require("template.php");



function seltoken()
{

	global $_POST;
	extract($_POST);

	if(!isset($name)) {
		$name = "";
	}
	if(!isset($subject)) {
		$subject = "";
	}
	if(!isset($notes)) {
		$notes = "";
	}

	$name = remval($name);
	$subject = remval($subject);
	$notes = remval($notes);

	$whe = "";
	$csc = 0;

	if(!isset($team)) {
		$team = 0;
		$user = 0;
		$cat = 0;
		$csc = 0;
	} else {
		$team += 0;
		$user += 0;
		$cat += 0;
		$csc += 0;
	}

	$date = date("Y-m-d");

	db_conn('crm');

	$Sl = "SELECT * FROM crms WHERE userid='".USER_ID."'";
	$Ry = db_exec($Sl) or errDie("Unable to get info from db.");

	if(pg_numrows($Ry)<1) {
		return "
		You have not been set up to use query management.<br>
		Please allocate yourself to a team.
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='crms-allocate.php'>Allocate users to Teams</a></td>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	}

	$crmdata = pg_fetch_array($Ry);

	$Sl = "SELECT * FROM teams WHERE id='$crmdata[teamid]'";
	$Ry = db_exec($Sl) or errDie("Unable to get team data.");

	$teamdata = pg_fetch_array($Ry);

	$username = USER_NAME;
	$disdate = date("d-m-Y, l, G:i");

	$i = 0;


	$Sl = "SELECT id,name FROM teams ORDER BY name";
	$Ry = db_exec($Sl) or errDie("Unable to get teams from system.");

	$teams = "<select name=team>";
	$teams .= "<option value='0'>All</option>";

	while($tdata = pg_fetch_array($Ry)) {
		if($team == $tdata['id']) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$teams .= "<option value='$tdata[id]' $sel>$tdata[name]</option>";
	}

	$teams .= "</select>";

	$Sl = "SELECT userid,name FROM crms WHERE div='".USER_DIV."'";
	$Ry = db_exec($Sl) or errDie("Unable to get users from db.");

	$users = "
		<select name='user'>
			<option value='0'>All</option>";
	while($udata = pg_fetch_array($Ry)) {
		if($user == $udata['userid']) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$users .= "<option value='$udata[userid]' $sel>$udata[name]</option>";
	}
	$users .= "</select>";

	$Sl = "SELECT * FROM tcats WHERE div='".USER_DIV."' ORDER BY name";
	$Ry = db_exec($Sl) or errDie("Unable to get categories from system.");

	$cats = "
		<select name='cat'>
			<option value='0'>All</option>";
	while($cdata = pg_fetch_array($Ry)) {
		if($cat == $cdata['id']) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$cats .= "<option value='$cdata[id]' $sel>$cdata[name]</option>";
	}
	$cats .= "</select>";

    $out = "
    	<table ".TMPL_tblDflts." width='500'>
			<tr>
				<td colspan='3'>
					<table ".TMPL_tblDflts." width='100%'>
						<tr>
							<td align='center'><h3>List closed queries</h3></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan='2'>
					<table border='0' cellpadding='2' cellspacing='1' width='100%'>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='report'>
						<tr>
							<th colspan='2'>Query Criteria</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Teams</td>
							<td>$teams</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Users</td>
							<td>$users</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Categories</td>
							<td>$cats</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Enquery By(name)</td>
							<td><input type='text' size='20' name='name' value='$name'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Subject</td>
							<td><input type='text' size='20' name='subject' value='$subject'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Notes</td>
							<td><input type='text' size='20' name='notes' value='$notes'></td>
						</tr>
						<tr>
							<td colspan='2' align='right'><input type='submit' value='Search &raquo;'></td>
						</tr>
					</form>
					</table>
				</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td width='22%'>
					<table ".TMPL_tblDflts.">
					<form action='".SELF."' method='POST'>
						<input type='hidden' name='key' value='find'>
						<tr>
							<th colspan='2'>Search</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Input No</td>
							<td><input name='id' type='text' size='7'></td>
						</tr>
						<tr>
							<td colspan='2' align='right'><input type='submit' name='search' value='Search &raquo;'></td>
						</tr>
					</form>
					</table>
				</td>
			</tr>
		</table>
		<p>
        <table border=0 cellpadding='2' cellspacing='1'>
	        <tr>
	        	<th>Quick Links</th>
	        </tr>
	        <script>document.write(getQuicklinkSpecial());</script>
	        <tr bgcolor='".bgcolorg()."'>
	        	<td><a href='index.php'>My Business</a></td>
	        </tr>
        </table>";
	return $out;

}



function find($_POST)
{

	extract($_POST);

	$id += 0;

	db_conn('crm');

	$Sl = "SELECT * FROM closedtokens WHERE tid='$id'";
	$Ry = db_exec($Sl) or errDie("Unable to get query from system.");

	if(pg_numrows($Ry) < 1) {
		return seltoken();
	}

	$data = pg_fetch_array($Ry);

	$id = $data['id'];

	header("Location: tokens-closed-details.php?id=$id");
	exit;

}


function report($_POST)
{

	extract($_POST);

	if(!isset($name)) {
		$name = "";
	}
	if(!isset($subject)) {
		$subject = "";
	}
	if(!isset($notes)) {
		$notes = "";
	}

	$name = remval($name);
	$subject = remval($subject);
	$notes = remval($notes);

	$whe = "";
	$csc = 0;

	if(!isset($team)) {
		$team = 0;
		$user = 0;
		$cat = 0;
		$csc = 0;
	} else {
		$team += 0;
		$user += 0;
		$cat += 0;
		$csc += 0;
	}

	if($team != 0) {
		$whe .= " AND teamid='$team' ";
	}
	if($user != 0) {
		$whe .= " AND userid='$user' ";
	}
	if($cat != 0) {
		$whe .= " AND catid='$cat' ";
	}
	if($csc != 0) {
		if($csc == 1) {
			$whe .= " AND csct='Contact' ";
		} elseif($csc == 2) {
			$whe .= " AND csct='Customer' ";
		} elseif($csc == 3) {
			$whe .= " AND csct='Supplier' ";
		}
	}

	if(strlen($name) > 0) {
		$whe .= " AND lower(name) LIKE lower('%$name%') ";
	}

	if(strlen($subject) > 0) {
		$whe .= " AND lower(sub) LIKE lower('%$subject%') ";
	}

	if(strlen($notes) > 0) {
		$whe .= " AND lower(notes) LIKE lower('%$notes%') ";
	}

	$date = date("Y-m-d");

	db_conn('crm');

	$Sl = "SELECT * FROM crms WHERE userid='".USER_ID."'";
	$Ry = db_exec($Sl) or errDie("Unable to get info from db.");

	if(pg_numrows($Ry) < 1) {
		return "
			You have not been set up to use query management.<br>
			Please allocate yourself to a team.
			<p>
			<table border=0 cellpadding='2' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='crms-allocate.php'>Allocate users to Teams</a></td>
				</tr>
				<script>document.write(getQuicklinkSpecial());</script>
				<tr bgcolor='".bgcolorg()."'>
					<td><a href='index.php'>My Business</a></td>
				</tr>
			</table>";
	}

	$crmdata = pg_fetch_array($Ry);

	$Sl = "SELECT * FROM teams WHERE id='$crmdata[teamid]'";
	$Ry = db_exec($Sl) or errDie("Unable to get team data.");

	$teamdata = pg_fetch_array($Ry);

	$username = USER_NAME;
	$disdate = date("d-m-Y, l, G:i");

	$i = 0;
	$out = "";

	$Sl = "SELECT id,tid,name,username,sub,closedate,opendate FROM closedtokens WHERE 1=1 $whe ORDER BY id";
	$Ry = db_exec($Sl) or errDie("Unable to get data from system.");

	if(pg_numrows($Ry) > 0) {

		$i = 0;

		$out = "
			<h3>Closed Queries</h3>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>No.</th>
					<th>Subject</th>
					<th>User</th>
					<th>Date Opened</th>
					<th>Date Closed</th>
					<th>Options</th>
				</tr>";

		while($data = pg_fetch_array($Ry)) {
			$i++;

			$out .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$data[tid]</td>
					<td>$data[name] - $data[sub]</td>
					<td>$data[username]</td>
					<td>$data[opendate]</td>
					<td>$data[closedate]</td>
					<td><a href='tokens-closed-details.php?id=$data[id]'>View Details</a></td>
				</tr>";

		}

		$out .= "</table>";

	} else {
		$out = "There are no closed queries for the selected criteria.";
	}

	$out .= "
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<script>document.write(getQuicklinkSpecial());</script>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='index.php'>My Business</a></td>
			</tr>
		</table>";
	return $out;

}


?>