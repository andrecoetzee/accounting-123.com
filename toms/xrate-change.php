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
require ("../core-settings.php");
require ("../libs/ext.lib.php");

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
			$OUTPUT = enter ($_POST);
	}
} else {
	$OUTPUT = enter ($_POST);
}

# display output
require ("../template.php");

# enter new data
function enter ($_POST)
{

	extract($_POST);

	$enter = "
		<h3>Update Exchange rate</h3>
		<form action='".SELF."' method='POST'>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<input type='hidden' name='key' value='confirm'>
			<tr><th>Currency</th><th>Exchange Rate</th></tr>";

	# Query server
	db_connect();

	$i = 0;
	$sql = "SELECT * FROM currency ORDER BY descrip";
	$curRslt = db_exec ($sql) or errDie ("Unable to retrieve currency from database.");
	if (pg_numrows ($curRslt) < 1) {
		return "<li class='err'> There are is currency in Cubit.";
	}
	$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";
	
	$i = 0;
	while ($cur = pg_fetch_array ($curRslt)) {
		# alternate bgcolor
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		if (isset($rates[$i])) {
			$cur["rate"] = $rates[$i];
		}
		$enter .= "<tr bgcolor='$bgColor'>
			<td>
				<input type='hidden' name=fcids[$i] value='$cur[fcid]'>
				$cur[symbol] - $cur[descrip] $sp4
			</td>
			<td align='right'>
				$sp4 ".CUR." / $cur[symbol] 
				<input type='text' name=rates[$i] size='8' value='$cur[rate]'>
			</td>
		</tr>";
		$i++;
	}

	$enter .= "
		<tr><td><br></td></tr>
		<tr>
			<td></td>
			<td valign='left' align='right'><input type='submit' value='Confirm &raquo;'></td>
		</tr>
	</table></form>
	<p>
	<table border='0' cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
	</table>";

	return $enter;
}

# confirm new data
function confirm ($_POST)
{

	# Get vars
	extract ($_POST);
	
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($fcids)){
		foreach($fcids as $key => $value){
			$v->isOk ($fcids[$key], "num", 1, 20, "Invalid currency.");
			$v->isOk ($rates[$key], "float", 1, 20, "Invalid rate.");
		}
	}

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"];
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	$confirm = "
		<h3>Confirm Update Exchange rate</h3>
		<form action='".SELF."' method='POST'>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<input type='hidden' name='key' value='write'>
			<tr><th colspan='2'><li class='err'> Please note : This script may take a while to finish.</th></tr>
			<tr><th>Currency</th><th>Exchange Rate</th></tr>";

	$sp4 = "&nbsp;&nbsp;&nbsp;&nbsp;";

	foreach($fcids as $key => $fcid){
		if(floatval($rates[$key]) == floatval(0))
			continue;

		$cur = getSymbol($fcid);

		$rates[$key] = sprint($rates[$key]);

		# alternate bgcolor
		$bgColor = ($key % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
		$confirm .= "<tr bgcolor='$bgColor'><td><input type='hidden' name=fcids[] value='$fcid'>$cur[symbol] - $cur[descrip] $sp4</td><td align='right'>$sp4 ".CUR." / $cur[symbol] <input type='hidden' name=rates[] value='$rates[$key]'> $rates[$key]</td></tr>";
	}

	$confirm .= "
			<tr><td><br></td></tr>
			<tr><td><input type='submit' name='key' value='&laquo; Correction'></td><td valign='left' align='right'><input type='submit' value='Confirm &raquo;'></td></tr>
		</table></form>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
		</table>";

	return $confirm;
}

# write new data
function write ($_POST)
{
	# Get vars
	extract ($_POST);
	
	if(isset($back)) {
		enter($_POST);
	}
	
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	if(isset($fcids)){
		foreach($fcids as $key => $value){
			$v->isOk ($fcids[$key], "num", 1, 20, "Invalid currency.");
			$v->isOk ($rates[$key], "float", 1, 20, "Invalid rate.");
		}
	}

	# Display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	foreach($fcids as $key => $fcid){
		$cur = getSymbol($fcid);
		$rates[$key] = sprint($rates[$key]);

		xrate_change($fcid, $rates[$key]);

		// Main updates
		sup_xrate_update($fcid, $rates[$key]);
		xrate_update($fcid, $rates[$key], "suppurch", "id");
		cus_xrate_update($fcid, $rates[$key]);
		xrate_update($fcid, $rates[$key], "invoices", "invid");
		xrate_update($fcid, $rates[$key], "custran", "id");
		bank_xrate_update($fcid, $rates[$key]);
	}

	// Layout
	$write = "
		<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
			<tr><th>Exchange rates have been updated</th></tr>
			<tr class='datacell'><td>Exchange rates have been successfully updated on the system.</td></tr>
		</table>
		<p>
		<table border='0' cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";

	return $write;
}
?>
