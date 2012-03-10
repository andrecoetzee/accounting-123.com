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
$OUTPUT = settings($HTTP_POST_VARS);
require("../template.php");



function settings($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	db_conn('cubit');

	$err = "";

	$save = false;

	if(isset($account)) {

		$save = true;

		$account = remval($account);

		$Sl = "SELECT * FROM statement_settings";
		$Ri = db_exec($Sl);

		if(pg_num_rows($Ri) < 1) {
			$Sl = "INSERT INTO statement_settings (ad) VALUES ('$account')";
			$Ri = db_exec($Sl);
		} else {
			$Sl = "UPDATE statement_settings SET ad='$account'";
			$Ri = db_exec($Sl);
		}
	}

	$Sl = "SELECT * FROM statement_settings";
	$Ri = db_exec($Sl);

	$sd = pg_fetch_array($Ri);

	if(!$save) {
		$ex = "<li class='err'>Please select your statement import settings & then click 'Update'</li>";
	} else {
		$ex = "<li class='err'>Statement import settings saved</li>";
	}

	if($sd['ad'] == "num") {
		$sel1 = "";
		$sel2 = "selected";
	} else {
		$sel1 = "";
		$sel2 = "";
	}

	$accounts = "
		<select name='account'>
			<option value='name' $sel1>Account Name</option>
			<option value='num' $sel2>Account Number</option>
		</select>";

	db_conn('cubit');

	$Sl = "SELECT * FROM statement_refs ORDER BY ref";
	$Ri = db_exec($Sl) or errDie("Unable to get data.");

	$tab = "
		<h4>The following are descriptions on your statement which cubit will try to detect.</h4>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Description</th>
				<th>+-</th>
				<th>Type</th>
				<th>Action</th>
				<th>Contra Account/Action Details</th>
				<th colspan='2'>Options</th>
			</tr>";

	$i = 0;
	while($rd = pg_fetch_array($Ri)) {

		if($rd['action'] == "c" ||  $rd['action'] == "cr") {
			db_conn('core');
			$rd['account'] += 0;
			$Sl = "SELECT * FROM accounts WHERE accid='$rd[account]'";
			$Rl = db_exec($Sl) or errDie("Unable to get account.");
			$ad = pg_fetch_array($Rl);
			$details = $ad['accname'];
			$action = "Insert into cashbook";
		} elseif($rd['action'] == "cp") {
			db_conn('cubit');
			$Sl = "SELECT cusnum,surname FROM customers WHERE cusnum='$rd[account]'";
			$Rl = db_exec($Sl) or errDie("Unable to get customers.");
			$cd = pg_fetch_array($Rl);
			$details = $cd['surname'];
			$action = "Customer Payment";
		} elseif($rd['action'] == "sp") {
			db_conn('cubit');
			$Sl = "SELECT supid,supname FROM suppliers WHERE supid='$rd[account]'";
			$Rl = db_exec($Sl) or errDie("Unable to get suppliers.");
			$cd = pg_fetch_array($Rl);
			$details = $cd['supname'];
			$action = "Supplier Payment";
		} elseif($rd['action'] == "Ignore") {
			$details = "";
			$action = "Ignore";
		} elseif($rd['action'] == "Delete") {
			$details = "";
			$action = "Delete";
		}

		$tab .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$rd[ref]</td>
				<td>$rd[pn]</td>
				<td>$rd[dets]</td>
				<td>$action</td>
				<td>$details</td>
				<td><a href='statement-ref-edit.php?id=$rd[id]'>Edit</a></td>
				<td><a href='statement-ref-rem.php?id=$rd[id]'>Delete</a></td>
			</tr>";
		
		$i++;
	}

	$tab .= "
			<tr bgcolor='".bgcolorg()."'>
				<td colspan='7' align='center'><a href='statement-ref-add.php'>ADD NEW</a></td>
			</tr>
		</table>";

	$out = "
		<h3>Statement Import Settings</h3>
		$ex
		$err
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<tr>
				<th colspan='2'>Settings</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Choose Account By</td>
				<td>$accounts</td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Update &raquo;'></td>
			</tr>
		</form>
		</table>
		<br>
		$tab
		<br>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='statement-ref-add.php'>Add new description</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $out;

}


?>