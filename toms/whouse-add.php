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

# get settings
require ("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
	case "confirm":
		$OUTPUT = confirm ($_POST);
		break;
	case "write":
		$OUTPUT = write ($_POST);
		break;
	default:
		$OUTPUT = enter ();
	}
} else {
	$OUTPUT = enter ();
}

# display output
require ("../template.php");



# enter new data
function enter ()
{

	extract ($_REQUEST);

	db_connect ();

	#branch mode install may not add stores .. disable this script
	$get_check = getCSetting ("INST_MODE");

	if (!isset($get_check) OR (strlen($get_check) < 1)){
		#setting not found ??? do nothing
	}elseif ($get_check == "branch"){
		return "<li class='err'>Branch Companies May Not Add New Stores.</li>";
	}

	$fields = array();
	$fields["team_id"] = 0;

	extract ($fields, EXTR_SKIP);

	# connect to db
	core_connect ();

	$stkacc = "<select name='stkacc'>";
	$sql = "SELECT * FROM accounts WHERE acctype = 'B' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li class='err'>There are no Balance accounts yet in Cubit.</li>";
	}else{
		while($acc = pg_fetch_array($accRslt)){
			if(isb($acc['accid'])) {
				continue;
			}
			$stkacc .= "<option value='$acc[accid]'>$acc[accname]</option>";
		}
	}
	$stkacc .= "</select>";

	$cosacc = "<select name='cosacc'>";
	$sql = "SELECT * FROM accounts WHERE acctype = 'E' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li class='err'>There are no Expenditure accounts yet in Cubit.</li>";
	}else{
		while($acc = pg_fetch_array($accRslt)){
			if(isb($acc['accid'])) {
				continue;
			}
			$cosacc .= "<option value='$acc[accid]'>$acc[accname]</option>";
		}
	}
	$cosacc .= "</select>";

	$conacc = "<select name='conacc'>";
	$sql = "SELECT * FROM accounts WHERE acctype = 'B' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	if(pg_numrows($accRslt) < 1){
		return "<li class='err'>There are no Income accounts yet in Cubit.</li>";
	}else{
		while($acc = pg_fetch_array($accRslt)){
			if(isb($acc['accid'])) {
				continue;
			}
			$conacc .= "<option value='$acc[accid]'>$acc[accname]</option>";
		}
	}
	$conacc .= "</select>";

	// Team permissions
	$sql = "SELECT id, name, des FROM crm.teams ORDER BY name ASC";
	$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");

	$team_sel = "
		<select name='team_id' style='width: 100%'>
			<option value='0'>[All]</option>";
	while (list($id, $team_name, $team_desc) = pg_fetch_array($team_rslt)) {
		if ($team_id == $id) {
			$sel = "selected='selected'";
		} else {
			$sel = "";
		}
		$team_sel .= "<option value='$id' $sel>$team_name - $team_desc</option>";
	}


	$enter = "
		<h3>Add Store</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='confirm'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Number</td>
				<td><input type='text' size='10' name='whno' /></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Store</td>
				<td><input type='text' size='10' maxlength='10' name='whname' /></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock Account</td>
				<td>$stkacc</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cost Of Sales Account</td>
				<td>$cosacc</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock Control Account</td>
				<td>$conacc</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Team Permissions</td>
				<td>$team_sel</td>
			</tr>
			<tr>
				<td colspan='2' align='right'>
					<input type='submit' value='Confirm &raquo;' />
				</td>
			</tr>
		</table>
		</form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='whouse-view.php'>View Stores</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../core/acc-new2.php'>Add Account</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $enter;

}




# confirm new data
function confirm ($_POST)
{

	# get vars
	extract ($_POST);

	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whno, "num", 1, 10, "Invalid Store number.");
	$v->isOk ($whname, "string", 1, 10, "Invalid Store name or Store name is too long.");
	$v->isOk ($stkacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($cosacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($conacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($team_id, "num", 1, 9, "Invalid team selection.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	# Get ledger account name
	core_connect();

	$sql = "SELECT accname FROM accounts WHERE accid = '$stkacc' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$accstk = pg_fetch_array($accRslt);

	# Get ledger account name
	core_connect();

	$sql = "SELECT accname FROM accounts WHERE accid = '$cosacc' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$acccos = pg_fetch_array($accRslt);

	# Get ledger account name
	core_connect();

	$sql = "SELECT accname FROM accounts WHERE accid = '$conacc' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql);
	$acccon = pg_fetch_array($accRslt);

	// Retrieve team
	if ($team_id) {
		$sql = "SELECT name, des FROM crm.teams WHERE id='$team_id'";
		$team_rslt = db_exec($sql) or errDie("Unable to retrieve teams.");
		list($team_name, $team_desc) = pg_fetch_array($team_rslt, 0);
	} else {
		$team_name = "[All]";
		$team_desc = "";
	}

	$confirm = "
		<h3>Confirm Store</h3>
		<form action='".SELF."' method='POST'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='key' value='write'>
			<input type='hidden' name='whno' value='$whno'>
			<input type='hidden' name='whname' value='$whname'>
			<input type='hidden' name='stkacc' value='$stkacc'>
			<input type='hidden' name='cosacc' value='$cosacc'>
			<input type='hidden' name='conacc' value='$conacc'>
			<input type='hidden' name='team_id' value='$team_id' />
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Number</td>
				<td>$whno</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Store</td>
				<td>$whname</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock Account</td>
				<td>$accstk[accname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Cost Of Sales Account</td>
				<td>$acccos[accname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Stock Control Account</td>
				<td>$acccon[accname]</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Team Permissions</td>
				<td>$team_name - $team_desc</td>
			</tr>
			<tr>
				<td align='right'></td>
				<td valign='left'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</table>
		</form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='whouse-view.php'>View Store</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../core/acc-new2.php'>Add Account</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $confirm;

}



# write new data
function write ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($whno, "num", 1, 10, "Invalid Store number.");
	$v->isOk ($whname, "string", 1, 10, "Invalid Store name or Store name is too long.");
	$v->isOk ($stkacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($cosacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($conacc, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($team_id, "num", 1, 9, "Invalid team.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	# connect to db
	db_conn ("exten");

	# write to db
	$sql = "
		INSERT INTO warehouses (
			whno, whname, stkacc, cosacc, conacc, div, team_id
		) VALUES (
			'$whno', '$whname', '$stkacc', '$cosacc', '$conacc', '".USER_DIV."', '$team_id'
		)";
	$whouseRslt = db_exec ($sql) or errDie ("Unable to add warehouse to system.", SELF);
	if (pg_cmdtuples ($whouseRslt) < 1) {
		return "<li class='err'>Unable to add warehouse to database.</li>";
	}

	$write = "
		<table ".TMPL_tblDflts." width='50%'>
			<tr>
				<th>Store added to system</th>
			</tr>
			<tr class='datacell'>
				<td>New Store <b>$whname</b>, has been successfully added to the system.</td>
			</tr>
		</table>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='whouse-view.php'>View Stores</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../core/acc-new2.php'>Add Account</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $write;

}



?>