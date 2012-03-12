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
require ("settings.php");

$_POST = array_merge($_POST, $_GET);

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
        case "confirm":
			$OUTPUT = confirm($_POST);
			break;
		case "write":
        	$OUTPUT = write($_POST);
			break;
		default:
			if (isset($_POST['accid'])) {
				$OUTPUT = edit ();
			} else {
				$OUTPUT = "<li>Invalid use of module</li>";
			}
	}
} else {
	if (isset($_POST['accid'])) {
		$OUTPUT = edit ();
	} else {
		$OUTPUT = "<li>Invalid use of module</li>";
	}
}

# display output
require ("template.php");



function edit($errors="")
{

	global $_POST;
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accid, "num", 1, 50, "Invalid Account number.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}

	# Select Stock
	core_connect();
	$sql = "SELECT * FROM core.accounts WHERE accid = '$accid' AND div = '".USER_DIV."'";
    $accRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($accRslt) < 1){
        return "<li> Invalid Account number.</li>";
    }else{
        $acc = pg_fetch_array($accRslt);
    }

	switch($acc["acctype"]){
		case "I":
			$tab = "Income";
			$acctype = "I";
			$toptype_a = array ("other_income"=>"Other Income", "sales"=>"Sales");
			break;
		case "E":
			$tab = "Expenditure";
			$acctype = "E";
			$toptype_a = array ("expenses"=>"Expenses", "cost_of_sales"=>"Cost of Sales");
			break;
		case "B":
			$tab = "Balance";
			$acctype = "B";
			$toptype_a = array (
				"-- ASSETS",
				"fixed_asset"=>"Fixed Assets",
				"investments"=>"Investments",
				"other_fixed_asset"=>"Other Fixed Assets",
				"current_asset"=>"Current Assets",
				"-- EQUITY AND LIABILITIES",
				"share_capital"=>"Share Capital",
				"retained_income"=>"Retained Income",
				"shareholders_loan"=>"Shareholders Loan",
				"non_current_liability"=>"Non-current Liabilities",
				"long_term_borrowing"=>"Long Term Borrowings",
				"other_long_term_liability"=>"Other Long Term Liabilities",
				"current_liability"=>"Current Liabilities"
			);
			break;
		default:
			return "<li>Invalid Category type</li>";
	}

	if(!isset($topacc)) {
		$topacc = "";
		$accname = "";
		$accnum = "";
		$catid = 0;
	}

	$toptypedrop = "<select name='toptype'>";
	$optgrouped = false;
	foreach($toptype_a as $dbval=>$humanval) {
		$sel_toptype = $dbval;
//		if ($sel_toptype[0] == $dbval) {
		if($acc['toptype'] == $dbval){
			$selected = "selected";
		} else {
			$selected = "";
		}

		if (substr($humanval, 0, 3) == "-- ") {
			if ($optgrouped) $toptype_out .= "</optgroup>";
			$toptypedrop .= "<optgroup label='".substr($humanval, 3)."'>";
			continue;
		}
		$toptypedrop .= "<option value='$dbval:$humanval' $selected>$humanval</option>";
	}
	if ($optgrouped) $toptypedrop .= "</optgroup>";
	$toptypedrop .= "</select>";

	# length of input box
	$size = strlen($acc['accname']);
	if($size < 20){
		$size = 20;
	}

	// Edit Account Layout
	$edit = "
		<h3>Edit Account</h3>
		<form action='".SELF."' method=post>
		<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
			<input type=hidden name=key value=confirm>
			<input type=hidden name=accid value='$acc[accid]'>
			<input type=hidden name=tab value='$tab'>
			<input type=hidden name='acctype' value='$acctype'>
			<tr><td colspan='2'>$errors</td></tr>
			<tr><th>Field</th><th>Value</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Type</td><td>$tab</td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Category Name</td><td>$toptypedrop</td></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Number</td><td valign=center><input type=text name=topacc size=4 maxlength=4 value='$acc[topacc]'> / <input type=text name=accnum size=3 maxlength=3 value='$acc[accnum]'></td></tr>
			<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Name</td><td><input type=text size=$size name=accname value='$acc[accname]'></td></tr>
			<tr><td><br></td></tr>
			<tr><td colspan=2 align=right><input type=submit value='Confirm &raquo;'></td></tr>
		</table></form>
		<p>
		<table border=0 cellpadding='2' cellspacing='1'>
			<tr><th>Quick Links</th></tr>
			<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='acc-view.php'>View Accounts</a></td></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";
	return $edit;

}


# confirm new data
function confirm ($_POST)
{
	# get vars
	extract ($_POST);
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accid, "num", 1, 50, "Invalid account number.");
	$v->isOk ($accname, "string", 1, 255, "Invalid account name.");
	$v->isOk ($topacc, "num", 1, 4, "Invalid account number prefix.");
	$v->isOk ($accnum, "num", 1, 3, "Invalid account number suffix.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		return edit($confirm);
	}

	# Select account
	core_connect();
	$sql = "SELECT * FROM core.accounts WHERE accid = '$accid' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($accRslt) < 1){
		return "<li class=err> Invalid Account number.";
	}else{
		$acc = pg_fetch_array($accRslt);
	}

	# Check account numbers
	core_connect();
	$sql = "SELECT * FROM core.accounts WHERE topacc = '$topacc' AND accnum = '$accnum' AND accname != '$acc[accname]' AND div = '".USER_DIV."' AND accid != '$accid'";
	$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($cRslt) > 0) {
		$confirm = "<li class=err>Selected Account number already in use.";
		return edit($confirm);
	}

	# Check account name
	core_connect();
	$sql = "SELECT * FROM core.accounts WHERE lower(accname) = '".strtolower($accname)."' AND accid != '$accid' AND div = '".USER_DIV."' AND accid != '$accid'";
	$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($checkRslt) > 0) {
		$confirm = "<li class=err> Account name already exist.";
		return edit($confirm);
	}

	$category = explode(":", $toptype);

	$confirm = "<h3>Confirm Edit Account</h3>
	<form action='".SELF."' method=post>
	<table cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<input type=hidden name=key value=write>
		<input type=hidden name=accid value='$acc[accid]'>
		<input type=hidden name=accnum value='$accnum'>
		<input type=hidden name=topacc value='$topacc'>
		<input type=hidden name=accname value='$accname'>
		<input type=hidden name=toptype value='$toptype'>
		<tr><th>Field</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Type</td><td>$tab</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Category Name</td><td>$category[1]</td></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Account Number</td><td>$topacc/$accnum</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Account Name</td><td>$accname</td></tr>
		<tr><td><br></td></tr>
		<tr><td colspan=2 align=right>
			<input type='submit' name='key' value='&laquo; Correction'>
			<input type='submit' value='Write &raquo;'>
		</td></tr>
	</table></form>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='acc-view.php'>View Accounts</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $confirm;
}

# Write new data
function write ($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accid, "num", 1, 50, "Invalid account number.");
	$v->isOk ($accname, "string", 1, 255, "Invalid account name.");
	$v->isOk ($topacc, "num", 1, 4, "Invalid account number prefix.");
	$v->isOk ($accnum, "num", 1, 3, "Invalid account number suffix.");
	$v->isOk ($toptype, "string", 1, 255, "Invalid toptype.");

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

	# Connect to db
	core_connect ();

	# Select account
	core_connect();
	$sql = "SELECT accid,topacc,accnum FROM core.accounts WHERE accid = '$accid' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($accRslt) < 1){
		return "<li class=err> Invalid Account number.";
	}else{
		$acc = pg_fetch_array($accRslt);
	}

	$toptype = explode(":", $toptype);

# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# Write to db
		$sql = "UPDATE accounts SET accname = '$accname', accnum = '$accnum', topacc = '$topacc', toptype='$toptype[0]' WHERE accid = '$accid' AND div = '".USER_DIV."'";
		$accRslt = db_exec ($sql) or errDie ("Unable to add edit account no system.", SELF);

		$sql = "UPDATE trial_bal SET accname = '$accname', accnum = '$accnum', topacc = '$topacc' WHERE accid = '$accid' AND div = '".USER_DIV."'";
		$accRslt = db_exec ($sql) or errDie ("Unable to add edit account no system.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to start a database transaction.",SELF);

	block();

	# Output
	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Account edited</th></tr>
		<tr class=datacell><td>Account <b>$acc[topacc]/$acc[accnum]</b>, has been edited.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td><a href='acc-view.php'>View Accounts</a></td></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $write;
}
?>
