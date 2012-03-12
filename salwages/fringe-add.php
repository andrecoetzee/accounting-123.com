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
require ("../libs/ext.lib.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirmDeduct ($_POST);
			break;
		case "write":
			$OUTPUT = writeDeduct ($_POST);
			break;
		default:
			$OUTPUT = enterDeduct ();
	}
} else {
	$OUTPUT = enterDeduct ();
}

# display output
require ("../template.php");

# enter new data
function enterDeduct ()
{
	# connect to db
	core_connect ();
	$dedcat= "<select name='catid'>";
		$sql = "SELECT * FROM balance WHERE div = '".USER_DIV."'";
		$catRslt = db_exec($sql);
		if(pg_numrows($catRslt) < 1){
				return "<li> There are no Balance Accounts categories yet in Cubit.";
		}else{
				while($cat = pg_fetch_array($catRslt)){
						if ($cat["catname"] == "Balance") {
							$selected = "selected";
						} else {
							$selected = "";
						}

						$dedcat .= "<option value='$cat[catid]' $selected>$cat[catname]</option>";
				}
		}
	$dedcat .="</select>";

	# connect to db
	db_connect ();

	# get last inserted id for new ref no
	// a little hack to make stoopid postgres not return a 1 as last id, when there is no last id
	if ( pg_numrows(db_exec("SELECT 1 FROM salded")) < 1 )
		$lastid = 1;
	else
		$lastid = pglib_lastid ("salded", "id") + 1;

        $refno = "saldeduct". sprintf ("%02d",$lastid);

	$Tp = array("No"=>"No","Yes"=>"Yes");
	$taxables = extlib_cpsel("taxable", $Tp,"No");

	$arrtype = array("Amount"=>"Amount", "Percentage"=>"Percentage");
	$seltype = extlib_cpsel("type", $arrtype, "Percentage");

        $enterDeduct =
        "<h3>New salary deduction</h3>
        <table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='".SELF."' method=post>
        <input type=hidden name=key value=confirm>
        <input type=hidden name=refno value='$refno'>
        <tr><th>Field</th><th>Value</th></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Name of deduction</td><td align=center><input type=text size=20 name=deduction></td></tr>
        <tr bgcolor='".TMPL_tblDataColor2."'><td>Creditor name</td><td align=center><input type=text size=20 name=creditor></td></tr>
        <tr bgcolor='".TMPL_tblDataColor1."'><td>Reference no.</td><td align=center>$refno</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Category</td><td align=center>$dedcat</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Creditor details</td><td align=center><input type=text size=20 name=details></td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Deduct Before PAYE</td><td align=center>$taxables</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Deduction Type</td><td>$seltype</td></tr>

	<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
        </form></table>"
		.mkQuickLinks(
			ql("../admin-employee-add.php", "Add Employee"),
			ql("../admin-employee-view.php", "View Employees")
		);

        return $enterDeduct;
}

# confirm new data
function confirmDeduct ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deduction, "string", 1, 100, "Invalid deduction name.");
	$v->isOk ($creditor, "string", 1, 100, "Invalid creditor name.");
	$v->isOk ($refno, "string", 1, 20, "Invalid reference number.");
	$v->isOk ($catid, "string", 1, 20, "Invalid Category number.");
	$v->isOk ($details, "string", 1, 100, "Invalid creditor details.");
	$v->isOk ($taxable, "string", 1, 3, "Invalid taxablility option.");
	$v->isOk ($type, "string", 1, 15, "Invalid type.");
	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"]."</li>";
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	# connect to db
	core_connect ();
	$dedacc= "<select name='accid' style='width: 230'>";
		$sql = "SELECT * FROM accounts WHERE catid = '$catid' AND div = '".USER_DIV."'";
		$accRslt = db_exec($sql);
		$numrows = pg_numrows($accRslt);
		if(empty($numrows)){
				$paid = "There are no Balance accounts yet in Cubit.";
		}else{
				while($acc = pg_fetch_array($accRslt)){
						$dedacc .= "<option value='$acc[accid]'>$acc[accname]</option>";
				}
		}
	$dedacc .="</select>";

	// Expense account
	$expacc = "<select name='expaccid' style='width: 230'>";
	$sql = "SELECT * FROM accounts WHERE catid='E10' AND div='".USER_DIV."'";
	$expRslt = db_exec($sql);
	while($exp = pg_fetch_array($expRslt)) {
		$expacc .= "<option value='$exp[accid]'>$exp[accname]</option>";
	}

	$confirmDeduct =
	"<h3>Confirm new salary deduction</h3>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<form action='".SELF."' method=post>
	<input type=hidden name=key value=write>
	<input type=hidden name=deduction value='$deduction'>
	<input type=hidden name=creditor value='$creditor'>
	<input type=hidden name=refno value='$refno'>
	<input type=hidden name=details value='$details'>
	<input type=hidden name=taxable value='$taxable'>
	<input type=hidden name=type value='$type'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Name of deduction</td><td align=center>$deduction</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Creditor name</td><td align=center>$creditor</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Reference no.</td><td align=center>$refno</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Deduction Account</td><td align=center>$dedacc</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Expense Account</td><td align=center>$expacc</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Creditor details</td><td align=center>$details</td></tr>
	<tr bgcolor='".TMPL_tblDataColor1."'><td>Deduct Before PAYE</td><td align=center>$taxable</td></tr>
	<tr bgcolor='".TMPL_tblDataColor2."'><td>Deduction Type</td><td align=center>$type</td></tr>
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</form>
	</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);

	return $confirmDeduct;
}

# write new data
function writeDeduct ($_POST)
{
	# get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($deduction, "string", 1, 100, "Invalid deduction name.");
	$v->isOk ($creditor, "string", 1, 100, "Invalid creditor name.");
	$v->isOk ($refno, "string", 1, 20, "Invalid reference number.");
	$v->isOk ($accid, "num", 1, 20, "Invalid Account number.");
	$v->isOk ($expaccid, "num", 1, 20, "Invalid Expense Account number.");
	$v->isOk ($details, "string", 1, 100, "Invalid creditor details.");
	$v->isOk ($taxable, "string", 1, 3, "Invalid taxablility option.");
	$v->isOk ($type, "string", 1, 15, "Invalid type.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class=err>".$e["msg"];
		}
		$confirmCust .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}

	# connect to db
	db_connect ();

	# check for duplicate
	$sql = "SELECT refno FROM salded WHERE refno='$refno' AND div = '".USER_DIV."'";
	$chkRslt = db_exec ($sql) or errDie ("Unable to check for duplicate entries.");
	if (pg_numrows ($chkRslt) > 0) {
		return "Entry, with reference number '$refno', already exists in database.";
	}

	# write to db
	$sql = "INSERT INTO salded (refno, deduction, creditor, details, accid, expaccid, add, type, div)
		VALUES ('$refno', '$deduction', '$creditor', '$details', '$accid', '$expaccid', '$taxable', '$type', '".USER_DIV."')";
	$salRslt = db_exec ($sql) or errDie ("Unable to add salary deduction to database.", SELF);
	if (pg_cmdtuples ($salRslt) < 1) {
		return "Unable to add salary deduction to database.";
	}

	$writeDeduct =
"
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
<tr><th>Salary deduction added to database</th></tr>
<tr class=datacell><td>New salary deduction, $deduction, has been successfully added to Cubit.</td></tr>
</table>"
	.mkQuickLinks(
		ql("../admin-employee-add.php", "Add Employee"),
		ql("../admin-employee-view.php", "View Employees")
	);
	return $writeDeduct;
}

?>
