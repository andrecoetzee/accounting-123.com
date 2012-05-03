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
			$OUTPUT = "Invalid use of script";
	}
} elseif(isset($_GET["id"])) {
	$OUTPUT=enter($_GET);
} else {
	$OUTPUT = "Invalid use of script.";
}

$OUTPUT .= "
				<p>
				<table border=0 cellpadding='2' cellspacing='1'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='team-add.php'>Add Cubit Team</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='team-list.php'>View Cubit Teams</a></td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
					<tr class='".bg_class()."'>
						<td><a href='index.php'>My Business</a></td>
					</tr>
				</table>";

require("template.php");




function enter($_GET)
{

	extract($_GET);
	$id+=0;

	db_conn('crm');
	$Sl="SELECT * FROM teams WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get team info.");
	
	if(pg_numrows($Ry)<1) {
		return "Invalid team.";
	}

	$teamdata=pg_fetch_array($Ry);
	
	db_conn('cubit');
	$Sl = "SELECT account_id,account_name,smtp_from
			 FROM mail_accounts WHERE ( username='".USER_NAME."' OR \"public\"='1' ) AND enable_smtp = '1'

		UNION
		SELECT mail_accounts.account_id,account_name,smtp_from
			FROM mail_accounts,mail_priv_accounts
			WHERE mail_accounts.account_id = mail_priv_accounts.account_id
				AND priv_owner = '".USER_NAME."' AND enable_smtp = '1'";

	$Ry = db_exec($Sl) or errDie("Unable to get accounts from db.");

	if ( pg_numrows($Ry) < 1 ) {
		$select_accounts="You have no accounts from which you may send email.<input type=hidden name=aid value=0>";
	} else {

		$select_accounts = "<select name='aid'>
		<option value='0'>Not using email</option>";
		while ( $row = pg_fetch_array($Ry) ) {
				if($row['account_id']==$teamdata['email']) {
					$sel="selected";
				} else {
					$sel="";
				}
			$select_accounts .= "<option value='$row[account_id]' $sel>$row[account_name] ($row[smtp_from])</option>";
		}
		$select_accounts .= "</select>";

	}
	
	$out = "
				<h3>Edit Cubit Team</h3>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='confirm'>
					<input type='hidden' name='id' value='$id'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Team Details</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Team Name</td>
						<td><input type='text' size='20' name='name' value='$teamdata[name]'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Description</td>
						<td><input type='text' size='20' name='des' value='$teamdata[des]'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Email</td>
						<td>$select_accounts</td>
					</tr>
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
					</tr>
				</form>
				</table>";
	return $out;

}



function entererr($_POST,$errors="")
{

	extract($_POST);
	
	db_conn('cubit');
	$Sl = "SELECT account_id,account_name,smtp_from
			 FROM mail_accounts WHERE ( username='".USER_NAME."' OR \"public\"='1' ) AND enable_smtp = '1'

		UNION
		SELECT mail_accounts.account_id,account_name,smtp_from
			FROM mail_accounts,mail_priv_accounts
			WHERE mail_accounts.account_id = mail_priv_accounts.account_id
				AND priv_owner = '".USER_NAME."' AND enable_smtp = '1'";

	$Ry = db_exec($Sl) or errDie("Unable to get accounts from db.");

	if ( pg_numrows($Ry) < 1 ) {
		$select_accounts="You have no accounts from which you may send email.<input type=hidden name=aid value=0>";
	} else {

		$select_accounts = "<select name='aid'>
		<option value='0'>Not using email</option>";
		while ( $row = pg_fetch_array($Ry) ) {
				if($row['account_id']==$aid) {
					$sel="selected";
				} else {
					$sel=="";
				}
			$select_accounts .= "<option value='$row[account_id]' $sel>$row[account_name] ($row[smtp_from])</option>";
		}
		$select_accounts .= "</select>";

	}

	$out = "
				<h3>Edit Cubit Team</h3>
				$errors
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='confirm'>
					<input type='hidden' name='id' value='$id'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Team Details</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Team Name</td>
						<td><input type='text' size='20' name='name' value='$name'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Description</td>
						<td><input type='text' size='20' name='des' value='$des'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Email</td>
						<td>$select_accounts</td>
					</tr>
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
					</tr>
				</form>
				</table>";
	return $out;

}




function confirm($_POST,$errors="")
{

	extract($_POST);

	$aid+=0;
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($name, "string", 1, 100, "Invalid Team name.");
	$v->isOk ($des, "string", 0, 100, "Invalid team description.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return entererr($_POST, $confirm."</li>");
	}



	$out = "
				<h3>Edit Cubit Team</h3>
				<form action='".SELF."' method='POST'>
					<input type='hidden' name='key' value='write'>
					<input type='hidden' name='id' value='$id'>
					<input type='hidden' name='aid' value='$aid'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Team Details</th>
					</tr>
					<tr class='".bg_class()."'>
						<td>Team Name</td>
						<td><input type='hidden' name='name' value='$name'>$name</td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Description</td>
						<td><input type='hidden' name='des' value='$des'>$des</td>
					</tr>
					<tr>
						<td colspan='2' align='right'><input type='submit' value='Write &raquo;'></td>
					</tr>
				</form>
				</table>";
	return $out;

}




function write($_POST)
{
	
	extract($_POST);

	$aid+=0;
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($name, "string", 1, 100, "Invalid Team name.");
	$v->isOk ($des, "string", 0, 100, "Invalid team description.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return entererr($_POST, $confirm."</li>");
	}


	$id+=0;
	db_conn('crm');
	$Sl="SELECT email FROM teams WHERE id='$id'";
	$Ry=db_exec($Sl) or errDie("Unable to get team data.");
	
	if(pg_numrows($Ry)<1) {
		return "Invalid team.";
	}
	
	$tdata=pg_fetch_array($Ry);
	$tdata['email']+=0;

	$Sl="UPDATE teams SET name='$name',des='$des',email='$aid' WHERE id='$id' AND div='".USER_DIV."'";
	$Ry=db_exec($Sl) or errDie("Unable to insert team into db.");

	db_conn('cubit');
	$Sl="UPDATE mail_accounts SET crmteam='$id' WHERE account_id='$aid'";
	$Ry=db_exec($Sl) or errDie("Unable to update mail account.");
	
	$Sl="UPDATE mail_accounts SET crmteam='0' WHERE account_id='$tdata[email]'";
	$Ry=db_exec($Sl) or errDie("Unable to update mail account.");

	$out = "
				<table ".TMPL_tblDflts." width='50%'>
					<tr>
						<th>Cubit Team Updated</th>
					</tr>
					<tr class='datacell'>
						<td>Cubit Team <b>$name</b>, has been successfully edited in the system.</td>
					</tr>
				</table>";
	return $out;

}



?>