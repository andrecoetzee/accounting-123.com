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

# decide what to do
if (isset($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "check":
			$OUTPUT = checknum();
			break;
		case "slctCat":
			$OUTPUT = slctCat($_REQUEST);
			break;
		case "confirm":
			$OUTPUT = confirm($_REQUEST);
			break;
		case "subacc":
			$OUTPUT = subacc($_REQUEST);
			break;
		case "confirmsub":
			$OUTPUT = confirmsub($_REQUEST);
			break;
		case "writesub":
			$OUTPUT = writesub($_REQUEST);
			break;
		case "write":
			$OUTPUT = write($_REQUEST);
			break;
		default:
//			$OUTPUT = view();
			$_REQUEST["type"] = "I";
			$OUTPUT = slctCat($_REQUEST);
	}
} else {
	# Display default output
//	$OUTPUT = view();
	$_REQUEST["type"] = "I";
	$OUTPUT = slctCat($_REQUEST);
}

# get template
require("template.php");





function checknum()
{

	extract($_REQUEST);

	require_lib("validate");

	$v = new Validate();
	$v->isOk($topacc, "num", 4, 4, "Invalid Main Part.");
	$v->isOk($accnum, "num", 3, 3, "Invalid Sub Part.");

	/* is account number valid */
	if ($v->isError()) {
		$e = $v->getErrors();

		if (count($e) == 2) {
			$err = "Invalid account number.";
		} else {
			$err = $e[0]["msg"];
		}
	}
	/* check number against database */
	else {
		/* does account number exist */
		$qry = new dbSelect("accounts", "core", grp(
			m("cols", "accname"),
			m("where", "topacc='$topacc' AND accnum='$accnum'"),
			m("limit", "1")
		));
		$qry->run();

		if(!isset($rslt))
			$rslt = array ();

		if ($qry->num_rows($rslt) > 0) {
			$accname = $qry->fetch_result();
			$err = "Account number in use: $accname.";
		}
		/* sub account, check if main account exists */
		else if ($accnum != "000") {
			$qry->setOpt(grp(
				m("where", "topacc='$topacc'")
			));
			$qry->run();

			if ($qry->num_rows() <= 0) {
				$err = "Main Account doesn't exist.";
			}
		}
	}

	if (!isset($err)) {
		$err = "<strong>Account number valid.</strong>";
	} else {
		$err = "<li class='err'>$err</li>";
	}
	return $err;

}



# Default View
//function view()
//{
//
//	global $HTTP_POST_VARS;
//	extract($HTTP_POST_VARS);
//
//	$t1 = "";
//	$t2 = "";
//	$t3 = "";
//
//	if(!isset($type)) {
//		$type = "";
//	}
//
//	if($type == "I") {
//		$t1 = "selected";
//	} elseif($type == "B") {
//		$t2 = "selected";
//	} elseif($type == "E") {
//		$t3 = "selected";
//	}
//
//	$view = "
//		<h3>Add New Account</h3>
//		<table ".TMPL_tblDflts." width='350'>
//		<form action='".SELF."' method='POST' name='form'>
//			<input type='hidden' name='key' value='slctCat'>
//			<tr>
//				<th>Field</th>
//				<th>Value</th>
//			</tr>
//			<tr bgcolor='".bgcolorg()."'>
//				<td>".REQ."Select Account type</td>
//				<td valign='center'>
//					<select name='type'>
//						<option value='I' $t1>Income</option>
//						<option value='B' $t2>Balance</option>
//						<option value='E' $t3>Expenditure</option>
//					</select>
//				</td>
//			</tr>
//			<tr>
//				<td></td>
//				<td valign='center' align='right'><input type='submit' value='Enter Details &raquo;'></td>
//			</tr>
//		</form>
//		</table>"
//		.mkQuickLinks(
//			ql("../reporting/allcat.php", "List All Accounts (New Window)", true),
//			ql("acc-view.php", "View Accounts")
//		);
//	return $view;
//
//}



function slctCat($HTTP_POST_VARS, $err="")
{

	# get vars
	extract ($_REQUEST);

	if (isset($update_parent) AND $update_parent == "yes"){
		$update_parent = "yes";
	}else {
		$update_parent = "no";
	}

	if (isset($set_key) AND strlen($set_key) > 0)
		$set_key = trim ($set_key);
	else 
		$set_key = "";

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($type, "string", 1, 2, "Invalid account type.");

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}


	$tsel1 = "";
	$tsel2 = "";
	$tsel3 = "";

	# Check Category name on selected type
	core_connect();

	switch($type){
		case "I":
			$tab = "Income";
			$range_min = MIN_INC;
			$range_max = MAX_INC;
			$toptype_a = array ("other_income"=>"Other Income", "sales"=>"Sales");
			$tsel1 = "selected";
			break;
		case "E":
			$tab = "Expenditure";
			$range_min = MIN_EXP;
			$range_max = MAX_EXP;
			$toptype_a = array ("expenses"=>"Expenses", "cost_of_sales"=>"Cost of Sales");
			$tsel2 = "selected";
			break;
		case "B":
			$tab = "Balance";
			$range_min = MIN_BAL;
			$range_max = MAX_BAL;
			$toptype_a = array (
				"-- ASSETS",
				"fixed_asset" => "Fixed Assets",
				"investments" => "Investments",
				"other_fixed_asset" => "Other Fixed Assets",
				"current_asset" => "Current Assets",
				"-- EQUITY AND LIABILITIES",
				"share_capital" => "Share Capital",
				"retained_income" => "Retained Income",
				"shareholders_loan" => "Shareholders Loan",
				"non_current_liability" => "Non-current Liabilities",
				"long_term_borrowing" => "Long Term Borrowings",
				"other_long_term_liability" => "Other Long Term Liabilities",
				"current_liability" => "Current Liabilities"
			);
			$tsel3 = "selected";
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

	$range_min = str_pad($range_min, 4, "0", STR_PAD_LEFT);
	$range_max = str_pad($range_max, 3, "0", STR_PAD_LEFT);

	$range = "$range_min/000 - $range_max/999";

	if (!isset($toptype)) $toptype = -1;

	$toptype_out = "<select name='toptype'>";
	$optgrouped = false;
	foreach($toptype_a as $dbval=>$humanval) {
		$sel_toptype = $dbval;

		/* defaults when toptype not set */
		$dflt_income = $toptype == -1 && $type == "I" && $dbval == "sales";
		$dflt_expense = $toptype == -1 && $type == "E" && $dbval == "expenses";

		if ("$dbval:$humanval" == "$toptype" || $dflt_income || $dflt_expense) {
			$selected = "selected";
		} else {
			$selected = "";
		}

		if (substr($humanval, 0, 3) == "-- ") {
			if ($optgrouped) $toptype_out .= "</optgroup>";
			$toptype_out .= "<optgroup label='".substr($humanval, 3)."'>";
			continue;
		}
		$toptype_out .= "<option value='$dbval:$humanval' $selected>$humanval</option>";
	}
	if ($optgrouped) $toptype_out .= "</optgroup>";
	$toptype_out .= "</select>";


	$tab_drop = "
		<select name='type' onChange='javascript:document.form1.submit();'>
			<option value='I' $tsel1>Income</option>
			<option value='E' $tsel2>Expense</option>
			<option value='B' $tsel3>Balance</option>
		</select>";
	

	$slctCat = "
		<h3>Add New Account</h3>
		$err
		".TBL_BR."
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='old_type' value='$type' />
			<input type='hidden' name='key' value='confirm' />
			<input type='hidden' name='oldtype' value='$type' />
			<input type='hidden' name='tab' value='$tab' />
			<input type='hidden' name='update_parent' value='$update_parent' />
			<input type='hidden' name='set_key' value='$set_key' />
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Type</td>
				<td>$tab_drop</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Category Name</td>
				<td>
					<select name='catid'>";

	$sql = "SELECT * FROM core.$tab WHERE div = '".USER_DIV."' ORDER BY catid";
	$catRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);

	if (pg_numrows($catRslt) < 1){
		return "There are no Account Categories under $tab";
	}

	while ($cat = pg_fetch_array($catRslt)) {
		if ($cat['catid'] == $catid) {
			$sel = "selected";
		} else {
			$sel = "";
		}
		$slctCat .= "<option value='$cat[catid]' $sel>$cat[catname]</option>";
	}
	$slctCat .= "</select>";

	$slctCat .= "
				$toptype_out</td>
				<td>(Financial Statements heading)</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Account Number</td>
				<td valign='center' nowrap='t'>
					<input onChange='chAccnum(\"top\", this);' type='text' name='topacc' size='4' maxlength='4' value='$topacc' /> /
					<input onChange='chAccnum(\"num\", this);' type='text' name='accnum' size='3' maxlength='3' value='$accnum' />
					(Recommeded: $range)
				</td>
				<td><span id='accnum_check'></span></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>".REQ."Account Name</td>
				<td valign='center'><input type='text' name='accname' maxlength='40' value='$accname' /></td>
			</tr>
			".TBL_BR."
			<tr><td colspan='2' align='right'><input type='submit' value='Confirm &raquo' /></td></tr>
		</form>
		</table>
		<script>
			var accnum = [];
			function chAccnum(w, obj) {
				accnum[w] = obj.value;

				if (accnum['top'] && accnum['num']) {
					ajaxRequest('".SELF."', 'accnum_check', AJAX_SET,
						'key=check&topacc=' + accnum['top'] + '&' + 'accnum=' + accnum['num']);
				}
			}
		</script>"
		.mkQuickLinks(
			ql("../reporting/allcat.php", "List All Accounts (New Window)", true),
			ql("acc-view.php", "View Accounts"),
			ql("acc-new2.php", "Add Account")
		);
	return $slctCat;

}



# confirm
function confirm($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	if ($type != $old_type)
		return slctCat($HTTP_POST_VARS);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($type, "string", 1, 3, "Invalid category type.");
	$v->isOk ($tab, "string", 1, 14, "Invalid category type.");
	$v->isOk ($accname, "string", 1, 50, "Invalid account name.");
	$v->isOk ($topacc, "num", 4, 4, "Invalid account number prefix.");
	$v->isOk ($catid, "string", 1, 50, "Invalid category ID.");
	$v->isOk ($accnum, "num", 0, 3, "Invalid account number suffix.");
	$v->isOk ($toptype, "string", 1, 255, "Invalid category type.");

	#check the account numbers for range ...
	switch ($type){
		case "I":
			$min = 0001;
			$max = 1999;
			if (($topacc < $min) OR ($topacc > $max))
				$v->addError($topacc,"Please Ensure Account Number is Within Its Type Bracket ($min - $max)");
			break;
		case "E":
			$min = 2000;
			$max = 4999;
			if (($topacc < $min) OR ($topacc > $max))
				$v->addError($topacc,"Please Ensure Account Number is Within Its Type Bracket ($min - $max)");
			break;
		case "B":
			$min = 5000;
			$max = 9999;
			if (($topacc < $min) OR ($topacc > $max))
				$v->addError($topacc,"Please Ensure Account Number is Within Its Type Bracket ($min - $max)");
			break;
		default:
	}

//	if ((strlen($accname) < 1) OR (strlen($topacc) < 1) OR (strlen($accnum) < 1))
//		if ($oldtype != $type)
//			$v->addError(null, "Please check account number for this category.");
	if (preg_match("/-- [A-Z]* --/", $toptype)) {
		$v->addError(null, "Please select a category type.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "<tr><td colspan='2' class='err'>";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		$confirm .= "</li>";

		# Return error function
		return slctCat($HTTP_POST_VARS, $confirm);
	}



	// If we don't have an accnum default to 000
	if (empty($accnum)) $accnum = "000";

	# Check Account name on selected type and category
	core_connect();

	//we need a better check than this ....
//	$sql = "SELECT * FROM accounts WHERE accname = '$accname' AND div = '".USER_DIV."'";
//	$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
//	if (pg_numrows($checkRslt) > 0) {
//		$confirm = "
//						<tr>
//							<td colspan='2' class='err'>Account name already exist.</td>
//						</tr>";
//		# return error function
//		return slctCat($HTTP_POST_VARS, $confirm);
//	}

	$get_accs = "SELECT accname FROM accounts WHERE div = '".USER_DIV."'";
	$run_accs = db_exec($get_accs) or errDie("Unable to get accounts information.");
	if(pg_numrows($run_accs) > 0){
		while ($aarr = pg_fetch_array($run_accs)){
			$checkval = str_replace(" ","",strtolower($aarr['accname']));
			if($checkval == str_replace(" ","",strtolower($accname))){
				$confirm = "
					<tr>
						<td colspan='2'><li class='err'>Account name already exist.</li></td>
					</tr>";
				return slctCat($HTTP_POST_VARS, $confirm);
			}
		}
	}

	# Check Account name on selected type and category
	core_connect();

	$sql = "SELECT * FROM accounts WHERE topacc = '$topacc' AND accnum = '$accnum' AND div = '".USER_DIV."'";
	$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($cRslt) > 0) {
		$confirm = "
			<tr>
				<td colspan='2'><li class='err'>The Account number is already in use.</li></td>
			</tr>";
		# return error function
		return slctCat($HTTP_POST_VARS, $confirm);
	}

	if ($accnum != "000"){
		#check if main account exists ...
		$sql = "SELECT * FROM accounts WHERE topacc = '$topacc' OFFSET 0 LIMIT 1";
		$run_sql = db_exec($sql) or errDie("Unable to get account information.");
		if(pg_numrows($run_sql) < 1){
			$confirm = "
				<tr>
					<td colspan='2'><li class='err'>Main Account doesn't exist.</li></td>
				</tr>";
			# return error function
			return slctCat($HTTP_POST_VARS, $confirm);
		}
	}

	$sql = "SELECT SUM(debit)-SUM(credit) AS balance,accname FROM core.trial_bal 
			WHERE topacc = '$topacc' AND accnum='000' AND div = '".USER_DIV."' 
			GROUP BY accname";
	$rslt = db_exec($sql);

	if (pg_num_rows($rslt) > 0) {
		$ad = pg_fetch_array($rslt);

		if ($ad['accname'] == "Customer Control Account") {
			return slctCat($HTTP_POST_VARS,"<li class='err'>You cannot add a sub account for the Customer Control Account.</li>");
		} else if ($ad['accname'] == "Supplier Control Account") {
			return slctCat($HTTP_POST_VARS,"<li class='err'>You cannot add a sub account for the Supplier Control Account.</li>");
		} else if ($ad['accname'] == "Inventory") {
			return slctCat($HTTP_POST_VARS,"<li class='err'>You cannot add a sub account for the Inventory account.</li>");
		} else if ($ad['accname'] == "Employees Control Account") {
			return slctCat($HTTP_POST_VARS,"<li class='err'>You cannot add a sub account for the Employees Control Account.</li>");
		} else if ($ad["balance"] != 0) {
			return slctCat($HTTP_POST_VARS,"<li class='err'>You cannot create a sub account for a main account that already has transactions.</li>");
		}
	}

	// Get the human value of toptype
	$toptype_h = explode(":", $toptype);
	$toptype_h = $toptype_h[1];

	$confirm = "
		<h3>Add New Account</h3>
		<h4>Confirm entry</h4>
		<table ".TMPL_tblDflts.">
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='key' value='write' />
			<input type='hidden' name='type' value='$type' />
			<input type='hidden' name='catid' value='$catid' />
			<input type='hidden' name='tab' value='$tab' />
			<input type='hidden' name='accname' value='$accname' />
			<input type='hidden' name='topacc' value='$topacc' />
			<input type='hidden' name='accnum' value='$accnum' />
			<input type='hidden' name='toptype' value='$toptype' />
			<input type='hidden' name='update_parent' value='$update_parent' />
			<input type='hidden' name='set_key' value='$set_key' />
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Type</td>
				<td>$tab</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category ID</td>
				<td>$catid</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category Type</td>
				<td>$toptype_h</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Number</td>
				<td>$topacc/$accnum</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account Name</td>
				<td>$accname</td>
			</tr>
			".TBL_BR."
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction' /></td>
				<td align='right'><input type='submit' value='Write &raquo' /></td>
			</tr>
		</form>
		</table>"
		.mkQuickLinks(
			ql("../reporting/allcat.php", "List All Accounts (New Window)", true),
			ql("acc-view.php", "View Accounts"),
			ql("acc-new2.php", "Add Account")
		);
	return $confirm;

}



function write($HTTP_POST_VARS)
{

	extract($HTTP_POST_VARS);

	if(isset($back)) {
		unset($HTTP_POST_VARS["back"]);
		return slctCat($HTTP_POST_VARS);
	}

	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($type, "string", 1, 2, "Invalid category type.");
	$v->isOk ($tab, "string", 1, 14, "Invalid category type.");
	$v->isOk ($accname, "string", 1, 50, "Invalid account name.");
	$v->isOk ($catid, "string", 1, 50, "Invalid category Id/name.");
	$v->isOk ($topacc, "num", 4, 4, "Invalid account number.");
	$v->isOk ($accnum, "num", 1, 3, "Invalid account number.");
	$v->isOk ($toptype, "string", 1, 255, "Invalid category type.");

	if ($v->isError ()) {
		$err = $v->genErrors();
		return $err;
	}



	core_connect();

	$sql = "SELECT * FROM accounts WHERE accname = '$accname' AND div = '".USER_DIV."'";
	$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($checkRslt) > 0) {
		$confirm = "
			<tr>
				<td colspan='2' class='err'>Account name already exist.</td>
			</tr>";
		return slctCaterr($type, $tab, $accname, $catid, $topacc, $accnum, $confirm);
		exit;
	}

	# Check Account Number
	core_connect();
	$sql = "SELECT * FROM accounts WHERE topacc = '$topacc' AND accnum = '$accnum' AND div = '".USER_DIV."'";
	$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
	$check = pg_numrows ($checkRslt);
	if (pg_numrows($checkRslt) > 0) {
		$confirm = "
			<tr>
				<td colspan='2' class='err'>The Account number is already in use.</td>
			</tr>";
		return slctCaterr($type, $tab, $accname, $catid, $topacc, $accnum, $confirm);
		exit;
	}

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

	// Get the db value of toptype
	$toptype_db = explode(":", $toptype);
	$toptype_db = $toptype_db[0];

	# write to DB
	$sql = "
		INSERT INTO accounts (
			topacc, accnum, accname, acctype, catid, div, toptype
		) VALUES (
			'$topacc', '$accnum', '$accname','$type', '$catid', '".USER_DIV."', '$toptype_db'
		)";
	$catRslt = db_exec ($sql) or errDie ("Unable to add Account to Database.", SELF);

	# get last inserted id for new acc
	$accid = pglib_lastid ("accounts", "accid");

	global $MONPRD;

	$month_names = array(0,
		"January",
		"February",
		"March",
		"April",
		"May",
		"June",
		"July",
		"August",
		"September",
		"October",
		"November",
		"December"
	);

	# insert account into trial Balance

	insert_trialbal($accid, $topacc, $accnum, $accname, $type, 'f', USER_DIV);

	for ($i = 1; $i <= 12; $i++) {
		$periodname = getMonthName($i);

		$sql = "
			INSERT INTO ".YR_DB.".$periodname (
				accid, topacc, accnum, accname, debit, credit, div
			) SELECT accid, topacc, accnum, accname, debit, credit, div FROM core.trial_bal WHERE month='$i' AND accid='$accid'";
		db_exec($sql) or die($sql);

		$sql = "
			INSERT INTO \"$i\".openbal (
				accid, accname, debit, credit, div
			) SELECT accid, accname, debit, credit, div FROM core.trial_bal WHERE month='$i' AND accid='$accid'";
		db_exec($sql) or die($sql);

		$sql = "
			INSERT INTO \"$i\".ledger (
				acc, contra, edate, eref, descript, credit, debit, div, caccname, ctopacc, caccnum, cbalance, dbalance
			) SELECT accid, accid, CURRENT_DATE, '0', 'Balance', '0', '0', div, accname, topacc, accnum, credit, debit 
			FROM core.trial_bal 
			WHERE month='$i' AND accid='$accid'";
		db_exec($sql) or die($sql);
	}

	pglib_transaction ("COMMIT") or errDie("Unable to start a database transaction.",SELF);

	block();

	
//			print "
//			<script>
//				window.opener.location.reload();
//				window.close();
//			</script>";
	
	if (isset($update_parent) AND $update_parent == "yes"){
		#do something to reload the parent window ...
		print "
			<script>
				window.opener.document.form.key.value='$set_key';
				window.opener.document.form.submit ();
				window.close();
			</script>";
	}else {
		#do normal return

		# status report
		$write = "
			<table ".TMPL_tblDflts." width='50%'>
				<tr>
					<th>New Account</th>
				</tr>
				<tr class='datacell'>
					<td>New Account, <b>($topacc/$accnum) - $accname</b> was successfully added to Cubit.</td>
				</tr>
			</table><br>"
			.mkQuickLinks(
				ql("../reporting/allcat.php", "List All Accounts (New Window)", true),
				ql("acc-view.php", "View Accounts"),
				ql("acc-new2.php", "Add Account")
			);
		return $write;
	}

}


?>