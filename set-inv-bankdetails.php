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
require("settings.php");
require("core-settings.php");
require("libs/ext.lib.php");

if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm();
			break;
		case "write":
			$OUTPUT = write();
			break;
		default:
			$OUTPUT = add();
	}
} else {
	$OUTPUT = add();
}

require("template.php");

function add($err = "") {
	extract($_POST);

	if (!isset($bankid)) {
		$bankid = getdSetting("BANK_DET");
	}

	$banks = qryBankAcct();
	if ($banks->num_rows() < 1) {
		return "<li class=err> There Are No Bank Accounts on Cubit.";
	}

	$bank = "<select name='bankid'>";
	while($acc = $banks->fetch_array()){
		if ($acc['bankid'] == $bankid) {
			$sel = "selected";
		} else {
			$sel = "";
		}

		$bank .= "<option value='$acc[bankid]' $sel>($acc[acctype]) $acc[accname] - $acc[bankname]</option>";
	}
	$bank .= "</select>";

	$OUT = "
	<h3>Banking Details Account</h3>
	$err
	<table ".TMPL_tblDflts.">
	<form action='".SELF."' method='post' name='form'>
	<input type='hidden' name='key' value='confirm' />
	<tr>
		<th>Field</th>
		<th>Value</th>
	</tr>
	<tr class='bg-even'>
		<td>Bank Account</td>
		<td valign='center'>$bank</td>
	</tr>
	".TBL_BR."
	<tr>
		<td></td>
		<td valign='center' align='right'><input type='submit' value='Confirm &raquo' /></td>
	</tr>
	</table>".mkQuickLinks();

	return $OUT;
}

function confirm() {
	extract($_POST);

	require_lib("validate");
	$v = new  validate ();
	$v->isOk($bankid, "num", 1, 30, "Invalid Bank Account.");

	if ($v->isError ()) {
		$err = $v->genErrors();
		return add($err);
	}

	$bank = qryBankAcct($bankid);

	$OUT = "
	<h3>Banking Details Account</h3>
	<h4>Confirm entry (Please check the details)</h4>
	<form action='".SELF."' method='post'>
	<table ".TMPL_tblDflts.">
	<input type='hidden' name='key' value='write'>
	<input type='hidden' name='bankid' value='$bankid'>
	<tr>
		<th>Field</th>
		<th>Value</th>
	</tr>
	<tr class='".bg_class()."'>
		<td>Bank</td>
		<td>$bank[bankname]</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Branch</td>
		<td>$bank[branchname] ($bank[branchcode])</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Account Name</td>
		<td>$bank[accname]</td>
	</tr>
	<tr class='".bg_class()."'>
		<td>Account Number</td>
		<td>$bank[accnum]</td>
	</tr>
	".TBL_BR."
	<tr>
		<td><input type='submit' name='back' value='&laquo; Correction' /></td>
		<td align='right'><input type='submit' value='Write &raquo' /></td>
	</tr>
	</table>
	</form>".mkQuickLinks();

	return $OUT;
}

function write() {
	extract($_POST);

	if (isset($back)) {
		return add($_POST);
	}

	require_lib("validate");
	$v = new validate ();
	$v->isOk($bankid, "num", 1, 30, "Invalid Bank Account.");

	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	$bank = qryBankAcct($bankid);

	$cols = grp(
		m("type", "Banking Details Account"),
		m("label", "BANK_DET"),
		m("value", $bankid),
		m("descript", "Bank Account: ($bank[acctype]) $bank[accname] - $bank[bankname]"),
		m("div", USER_DIV)
	);
	$qry = new dbUpdate("set", "cubit", $cols, "label = 'BANK_DET' AND div = '".USER_DIV."'");
	$qry->run(DB_REPLACE);

	$write ="
	<table ".TMPL_tblDflts.">
	<tr>
		<th>Bank Details Account</th>
	</tr>
	<tr class='text'>
		<td>Bank Details Account have been set to Bank Account: ($bank[acctype]) $bank[accname] - $bank[bankname].</td>
	</tr>
	</table>"
	.mkQuickLinks();

	return $write;
}
?>
