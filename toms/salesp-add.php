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

if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
		default:
			$OUTPUT = enter();
	}
} else {
	$OUTPUT = enter();
}

$OUTPUT .= mkQuickLinks(
	ql("salesp-add.php", "Add Sales Person"),
	ql("salesp-view.php", "View Sales People")
);

require ("../template.php");

function enter() {
	$fields = grp(
		m("salespno", ""),
		m("salesp", ""),
		m("com", "")
	);

	fillFields($fields, $_POST);
	extract($_POST);

	$OUT =
	"<h3>Add Sales Person</h3>
	<form action='".SELF."' method='post'>
	<table ".TMPL_tblDflts.">
	<input type='hidden' name='key' value='confirm'>
	<tr>
		<th>Field</th>
		<th>Value</th>
	</tr>
	<tr class='".bg_class()."'>
		<td>Number</td>
		<td><input type='text' size='10' name='salespno' value='$salespno'></td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Sales Person</td>
		<td><input type='text' size='20' name='salesp' values='$salesp'></td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Commission(input zero or leave blank to use stock settings commission)</td>
		<td><input type='text' size='4' name='com' value='$com'></td>
	</tr>
	<tr>
		<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;'></td>
	</tr>
	</table>
	</form>";

	return $OUT;
}

function confirm() {
	extract($_POST);

	require_lib("validate");
	$v = new validate ();
	$v->isOk($salespno, "num", 1, 10, "Invalid Sales Person number.");
	$v->isOk($salesp, "string", 1, 255, "Invalid Sales Person name.");
	$com+=0;

	if ($v->isError ()) {
		return $v->genErrors();
	}

	if (!empty($com) && $com != 0) {
		$com_disp = "$com %";
	} else {
		$com_disp = "Using Commission Set Under Stock Settings";
		$com = 0;
	}

	$OUT =
	"<h3>Confirm Sales Person</h3>
	<form action='".SELF."' method='post'>
	<table ".TMPL_tblDflts.">
	<input type=hidden name=key value=write>
	<input type=hidden name=salespno value='$salespno'>
	<input type=hidden name=salesp value='$salesp'>
	<input type=hidden name=com value='$com'>
	<tr>
		<th>Field</th>
		<th>Value</th>
	</tr>
	<tr class='bg-odd'>
		<td>Number</td>
		<td>$salespno</td>
	</tr>
	<tr class='bg-even'>
		<td>Sales Person</td>
		<td>$salesp</td>
	</tr>
	<tr class='bg-odd'>
		<td>Commission</td>
		<td>$com_disp</td>
	</tr>
	<tr>
		<td align='right'></td>
		<td valign='left'><input type='submit' value='Write &raquo;'></td>
	</tr>
	</table>
	</form>";

	return $OUT;
}

function write () {
	extract($_POST);

	require_lib("validate");
	$v = new validate ();
	$v->isOk($salespno, "num", 1, 10, "Invalid Sales Person number.");
	$v->isOk($salesp, "string", 1, 255, "Invalid Sales Person name.");

	if ($v->isError ()) {
		return $v->genErrors();
	}

	$cols = grp(
		m("salespno", $salespno),
		m("salesp", $salesp),
		m("com", $com),
		m("div", USER_DIV)
	);

	$qry = new dbUpdate("salespeople", "exten", $cols);
	$qry->run(DB_INSERT);

	if ($qry->affected() < 1) {
		return "<li class=err>Unable to add sales person to Cubit.";
	}

	$write = "
	<table ".TMPL_tblDflts.">
	<tr>
		<th>Sales Person added to system</th>
	</tr>
	<tr class='text'><td>New Sales Person <b>$salesp</b>, has been
		successfully added to the system.</td>
	</tr>
	</table>";

	return $write;
}
?>
