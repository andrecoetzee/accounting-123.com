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

# cubit settings
require ("set-settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
		case "confirm":
			$OUTPUT = view($HTTP_POST_VARS);
			break;
		default:
			$OUTPUT = view($HTTP_POST_VARS);
	}
} else {
	# Display default output
	$OUTPUT = view($HTTP_POST_VARS);
}

require("template.php");




function view ($HTTP_POST_VARS)
{

	extract ($HTTP_POST_VARS);

	$check1 = "";
	$check2 = "";
	$check3 = "";
	if (!isset ($type)) 
		$type = "I";
	switch($type){
		case "I":
			$tab = "Income";
			$check1 = "selected";
			break;
		case "E":
			$tab = "Expenditure";
			$check2 = "selected";
			break;
		case "B":
			$tab = "Balance";
			$check3 = "selected";
			break;
		default:
			return "<li>Invalid Category type</li>";
	}
	if (!isset ($catid) OR !isset($view)) {
		core_connect ();
		$sql = "SELECT catid FROM $tab WHERE div = '".USER_DIV."' ORDER BY catid LIMIT 1";
		$catRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);
		$catid = pg_fetch_result ($catRslt,0,0);
	}

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($type, "string", 1, 2, "Invalid Account type.");
	$v->isOk ($tab, "string", 1, 50, "Invalid Account type.");
	$v->isOk ($catid, "string", 1, 50, "Invalid Category.");

        # display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}







	core_connect();

	$sql = "SELECT * FROM $tab WHERE div = '".USER_DIV."' ORDER BY catid";
	$catRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);
	$rows = pg_numrows($catRslt);

	if($rows < 1){
		return "There are no Account Categories under $tab";
	}

	$cat_drop = "<select name='catid'>";
	while($cat = pg_fetch_array($catRslt)){
		if (isset ($catid) AND $catid == $cat['catid']){
			$cat_drop .= "<option value='$cat[catid]' selected>$cat[catname]</option>";
		}else {
			$cat_drop .= "<option value='$cat[catid]'>$cat[catname]</option>";
		}
	}
	$cat_drop .= "</select>";



	//layout
	$display = "
		<h3>View Accounts</h3>
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST' name='form1'>
			<input type='hidden' name='key' value='confirm'>
			<input type='hidden' name='oldtype' value='oldtype'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Type</td>
				<td valign='center'>
					<select name='type' onChange='document.form1.submit();'>
						<option value='I' $check1>Income</option>
						<option value='E' $check2>Expenditure</option>
						<option value='B' $check3>Balance</option>
					</select>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Category</td>
				<td nowrap>$cat_drop <input type='submit' name='view' value='View Accounts &raquo'></td>
			</tr>
		</form>
		</table>
		<center>
		<h3>View Accounts</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Category ID</th>
				<th>Account Number</th>
				<th>Account Name</th>
				<th colspan='4'>Options</th>
			</tr>";

	// get accounts
        $type = strtoupper($type);
        $sql = "SELECT * FROM core.accounts WHERE catid='$catid' AND div = '".USER_DIV."' ORDER BY topacc,accnum";
        $accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$numrows = pg_numrows ($accRslt);

        if ($numrows < 1) {
		$OUTPUT = "There are no Accounts on the selected Category.";
		require ("../template.php");
	}

	# display all accounts
        for ($i=0; $i < $numrows; $i++) {
		$acc = pg_fetch_array ($accRslt, $i);

		extract($acc);

		# check if account is already being used
		core_connect();
		$sql = "
			SELECT * FROM trial_bal
			WHERE accnum = '$accnum' AND topacc = '$topacc' AND (credit!=0 OR debit!=0) AND div = '".USER_DIV."'";
		$check = db_exec($sql) or errDie("Failed to retrieve accounts.");
		$rows = pg_numrows($check);
		if($rows) {
			$hastrans = true;
		} else {
			$hastrans = false;
		}


		$display .= "
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'>$catid</td>
				<td>$topacc/$accnum</td>
				<td>$accname</td>
				<td><a href='acc-edit.php?accid=$accid'>Edit</a></td>
				<td><a href='../reporting/acc-trans.php?accid=$accid'>View Transactions</a></td>";

		if ($hastrans) {
			$display .= "<td colspan='2'>Account has balance</td>";
		} else {
			$display .= "<td><a href='acc-mov.php?key=chtype&accid=$accid'>Change Account Category/Number</a></td>";
			$display .= "<td><a href='acc-rem.php?accid=$accid'>Delete</a></td>";
		}
        }

	$display .= "
			</tr>
		</table><br>"
        .mkQuickLinks(
        	ql(ACCNEW_LNK, "New Account")
        );

	return $display;

}



?>