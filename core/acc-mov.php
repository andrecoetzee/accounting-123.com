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

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
            case "confirm":
				$OUTPUT = confirm($HTTP_POST_VARS);
				break;

			case "write":
            	$OUTPUT = write($HTTP_POST_VARS);
				break;

			default:
				if (isset($HTTP_GET_VARS['accid'])){
					$OUTPUT = edit ($HTTP_GET_VARS['accid']);
				} else {
					$OUTPUT = "<li> - Invalid use of module";
				}
	}
} else {
		if (isset($HTTP_GET_VARS['accid'])){
			$OUTPUT = edit ($HTTP_GET_VARS['accid']);
		} else {
			$OUTPUT = "<li> - Invalid use of module";
		}
}

require ("template.php");

function edit($accid, $err = "") {
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accid, "num", 1, 50, "Invalid Account number.");

	if ($v->isError ()) {
		return $v->genErrors();
	}

	$acc = qryAccounts($accid);

	$acctypes = array(
		"I" => "Income",
		"E" => "Expense",
		"B" => "Balance"
	);

	$size = strlen($acc['accname']);
	if($size < 20){
		$size = 20;
	}

	$OUT = "
	<h3>Edit Account</h3>
	$err
	<form action='".SELF."' method=post>
	<table ".TMPL_tblDflts.">
		<input type='hidden' name='key' value='confirm' />
		<input type='hidden' name='accid' value='$acc[accid]' />
		<input type='hidden' name='fcatid' value='$acc[catid]' />
		<input type='hidden' name='acctype' value='$acc[acctype]' />
		<tr>
			<th>Field</th>
			<th>Value</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Account Name</td>
			<td>$acc[accname]</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Account Type</td>
			<td>".$acctypes[$acc["acctype"]]."</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>To Category</td>
			<td>
				<select name=catid>";

		$sql = "SELECT 'I' AS acctype, * FROM core.income WHERE div = '".USER_DIV."'
				UNION
				SELECT 'E' AS acctype, * FROM core.expenditure WHERE div='".USER_DIV."'
				UNION
				SELECT 'B' AS acctype, * FROM core.balance WHERE div='".USER_DIV."'";
		$cats = new dbSql($sql);
		$cats->run();

		if ($cats->num_rows() < 1) {
			return "There are no Account Categories in Cubit.";
		}

		$pgroup = false;
		while($cat = $cats->fetch_array()){
				if ($pgroup != $cat["acctype"]) {
					if ($pgroup) {
						$OUT .= "</optgroup>";
					}

					$OUT .= "<optgroup label='".$acctypes[$cat["acctype"]]."'>";
				}

				if ($cat["catid"] == $acc["catid"]) {
					$sel = "selected";
				} else {
					$sel = "";
				}

				$OUT .= "<option $sel value='$cat[catid]'>$cat[catname]</option>";
		}

		$OUT .= "
				</optgroup>
			</select>
		</td></tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Account Number</td>
			<td valign='center'><input type='text' name='topacc' size='4' maxlength='4' value='$acc[topacc]' /> / <input type='text' name='accnum' size='3' maxlength='3' value='$acc[accnum]' /></td>
		</tr>
		".TBL_BR."
		<tr>
			<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;' /></td>
		</tr>
	</table>
	</form>"
	.mkQuickLinks(
		ql("acc-view.php", "View Accounts")
	);

	return $OUT;
}

# confirm new data
function confirm ($HTTP_POST_VARS) {
	extract($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accid, "num", 1, 50, "Invalid account number.");
	$v->isOk ($catid, "string", 1, 10, "Invalid category number.");
	$v->isOk ($topacc, "num", 1, 4, "Invalid account number prefix.");
	$v->isOk ($accnum, "num", 1, 3, "Invalid account number suffix.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		return edit($HTTP_POST_VARS, $confirm);
	}

	# Select account
	core_connect();
	$sql = "SELECT * FROM accounts WHERE accid = '$accid' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($accRslt) < 1){
			return "<li class=err> Invalid Account number.";
	}else{
			$acc = pg_fetch_array($accRslt);
	}

	$acctypes = array(
		"I" => "Income",
		"E" => "Expenditure",
		"B" => "Balance"
	);

	$ftab = $acctypes[$fcatid[0]];
	$tab = $acctypes[$catid[0]];

	// from category info
	core_connect();
	$sql = "SELECT * FROM $ftab WHERE catid = '$fcatid' AND div = '".USER_DIV."'";
	$catRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($catRslt) < 1){
			return "<li class=err> Invalid Account Category number.";
	}else{
			$fcat = pg_fetch_array($catRslt);
	}

	// to category info
	core_connect();
	$sql = "SELECT * FROM $tab WHERE catid = '$catid' AND div = '".USER_DIV."'";
	$catRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($catRslt) < 1){
			return "<li class=err> Invalid Account Category number.";
	}else{
			$cat = pg_fetch_array($catRslt);
	}

	# Check account numbers
	core_connect();
	$sql = "SELECT * FROM accounts WHERE topacc = '$topacc' AND accnum = '$accnum' AND accname != '$acc[accname]' AND div = '".USER_DIV."'";
	$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($cRslt) > 0) {
		$confirm = "<li class=err>Selected Account number already in use.";
		return edit($HTTP_POST_VARS, $confirm);
	}

	/* sub categories */
	switch($catid[0]){
		case "I":
			$toptype_a = array (
				"other_income"=>"Other Income",
				"sales"=>"Sales"
			);
			break;
		case "E":
			$toptype_a = array (
				"expenses"=>"Expenses",
				"cost_of_sales"=>"Cost of Sales"
			);
			break;
		case "B":
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

	$toptypedrop = "<select name='toptype'>";
	$optgrouped = false;
	foreach($toptype_a as $dbval=>$humanval) {
		$sel_toptype = $dbval;
		if ($acc["toptype"] == $dbval) {
			$selected = "selected";
		} else {
			$selected = "";
		}

		if (substr($humanval, 0, 3) == "-- ") {
			if ($optgrouped) $toptype_out .= "</optgroup>";
			$toptypedrop .= "<optgroup label='".substr($humanval, 3)."'>";
			continue;
		}
		$toptypedrop .= "<option value='$dbval' $selected>$humanval</option>";
	}
	if ($optgrouped) $toptypedrop .= "</optgroup>";
	$toptypedrop .= "</select>";

	$confirm = "<h3>Confirm Move Account</h3>
	<form action='".SELF."' method='post'>
	<table ".TMPL_tblDflts.">
	<input type='hidden' name='key' value='write'>
	<input type='hidden' name='accid' value='$acc[accid]'>
	<input type='hidden' name='catid' value='$catid'>
	<input type='hidden' name='accnum' value='$accnum'>
	<input type='hidden' name='topacc' value='$topacc'>
	<tr>
		<th>Field</th>
		<th>Value</th>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Account Name</td>
		<td>$acc[accname]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Account Type</td>
		<td>$tab</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>From Category</td>
		<td>$fcat[catname]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>To Category</td>
		<td>$cat[catname]</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Account Number</td>
		<td>$topacc/$accnum</td>
	</tr>
	<tr bgcolor='".bgcolorg()."'>
		<td>Financial Statement Category</td>
		<td>$toptypedrop</td>
	</tr>
	".TBL_BR."
	<tr><td colspan=2 align=right><input type=submit value='Write &raquo;'></td></tr>
	</table></form>"
	.mkQuickLinks(
		ql("acc-view.php", "View Accounts")
	);

	return $confirm;
}

# write new data
function write ($HTTP_POST_VARS) {
	extract($HTTP_POST_VARS);
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($accid, "num", 1, 50, "Invalid account number.");
	$v->isOk ($catid, "string", 1, 10, "Invalid category number.");
	$v->isOk ($toptype, "string", 1, 30, "Invalid financial statement category.");
	$v->isOk ($topacc, "num", 1, 4, "Invalid account number prefix.");
	$v->isOk ($accnum, "num", 1, 3, "Invalid account number suffix.");

	if ($v->isError()) {
		$err = $v->genErrors();
		return edit($HTTP_POST_VARS, $err);
	}

	core_connect();
	$sql = "SELECT * FROM accounts WHERE accid = '$accid' AND div = '".USER_DIV."'";
	$accRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($accRslt) < 1){
		return "<li class=err> Invalid Account number.";
	}else{
		$acc = pg_fetch_array($accRslt);
	}

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to db
		$sql = "UPDATE accounts SET catid = '$catid', accnum = '$accnum', topacc = '$topacc', toptype='$toptype' WHERE accid = '$accid' AND div = '".USER_DIV."'";
		$accRslt = db_exec ($sql) or errDie ("Unable to add edit account no system.", SELF);

		$sql = "UPDATE trial_bal SET accnum = '$accnum', topacc = '$topacc' WHERE accid = '$accid' AND div = '".USER_DIV."'";
		$accRslt = db_exec ($sql) or errDie ("Unable to add edit account no system.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to start a database transaction.",SELF);


	$write =
	"<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
		<tr><th>Account edited</th></tr>
		<tr class=datacell><td>Account <b>$acc[topacc]/$acc[accnum] - $acc[accname]</b>, has been moved to category <b></b>.</td></tr>
	</table>"
	.mkQuickLinks(
		ql("acc-view.php", "View Accounts")
	);

	return $write;
}
?>
