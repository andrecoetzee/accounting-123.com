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
require("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($HTTP_POST_VARS["key"])) {
	switch ($HTTP_POST_VARS["key"]) {
                case "slctcat":
			$OUTPUT = slctCat($HTTP_POST_VARS);
			break;

                case "viewtrans":
			$OUTPUT = viewtrans($HTTP_POST_VARS);
			break;

                default:
			$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# get templete
require("../template.php");

# Default view
function view()
{
//layout
$view = "
		<h3>Select Category type</h3>
		<table ".TMPL_tblDflts." width='300'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='slctcat'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Account type</td>
				<td valign=center>
					<select name='type'>
						<option value='inc'>Income</option>
						<option value='exp'>Expenditure</option>
						<option value='bal'>Balance</option>
					</select>
				</td>
			</tr>
			<tr>
				<td></td>
				<td valign='center' align='right'><input type='submit' value='Continue &raquo;'></td>
			</tr>
		</form>
		</table>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../main.php'>Main Menu</a></td>
			</tr>
			</tr>
		</table>
";
        return $view;
}

# Select Category
function slctCat($HTTP_POST_VARS)
{

	# get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
        $v->isOk ($type, "string", 1, 3, "Invalid category type.");

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

	# Check Category name on selected type
	core_connect();
	switch($type){
		case "inc":
			$tab = "Income";
			break;
		case "exp":
			$tab = "Expenditure";
			break;
		case "bal":
			$tab = "Balance";
			break;
		default:
			return "<li>Invalid Category type";
	}

	$slctCat = "
	<h3>Select Category</h3>
	<table ".TMPL_tblDflts.">
	<form action='".SELF."' method='POST'>
		<input type='hidden' name='key' value='viewtrans'>
		<input type='hidden' name='type' value='$type'>
		<input type='hidden' name='tab' value='$tab'>
		<tr>
			<th>Field</th>
			<th>Value</th>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Accounts Type</td>
			<td>$tab</td>
		</tr>
		<tr bgcolor='".bgcolorg()."'>
			<td>Category Name</td>
			<td>
				<select name='catid'>";
	core_connect();
	$sql = "SELECT * FROM $tab WHERE div = '".USER_DIV."' ORDER BY catid";
	$catRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);
	$rows = pg_numrows($catRslt);

	if($rows < 1){
		return "There are no Account Categories under $tab";
	}

	while($cat = pg_fetch_array($catRslt)){
		$slctCat .= "<option value='$cat[catid]'>$cat[catname]</option>";
	}

	$slctCat .= "
					</select>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>Select Period</td>
				<td valign='center'>".finMonList("prd", PRD_DB)."</td>
			</tr>
			<tr>
				<td></td>
				<td align='right'><input type='submit' value='View Transactions &raquo'></td>
			</tr>
		</form>
		</table>
		<table ".TMPL_tblDflts." width='100'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='datacell'>
				<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports.php'>Financials</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td>
			</tr>
			<tr class='datacell'>
				<td align='center'><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";

	return $slctCat;
}

# View per account number and cat
function viewtran($HTTP_POST_VARS,$accid)
{
	# Get vars
	foreach ($HTTP_POST_VARS as $key => $value) {
		$$key = $value;
	}

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd, "string", 1, 14, "Invalid Period name.");
	$v->isOk ($type, "string", 1, 3, "Invalid Account type.");
	$v->isOk ($tab, "string", 1, 50, "Invalid Account type.");
	$v->isOk ($catid, "string", 1, 20, "Invalid Category.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"];
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	db_conn($prd);
	$OUTPUT = "";

	# Get Transactions
	$sql = "SELECT * FROM transect WHERE debit = '$accid' AND div = '".USER_DIV."' OR credit = '$accid' AND div = '".USER_DIV."'";
	$tranRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
	$numrows = pg_numrows ($tranRslt);

	if ($numrows < 1) {
		return "";
	}

	# display all transactions
	while ($tran = pg_fetch_array ($tranRslt)){

		# Get vars from tran as the are in db
		foreach ($tran as $key => $value) {
			$$key = $value;
		}

		/*
		// get account names
		$deb = get("core","accname","accounts","accid",$debit);
		$debacc = pg_fetch_array($deb);
		$ct = get("core","accname","accounts","accid",$credit);
		$ctacc = pg_fetch_array($ct);
		*/

		$amount = sprint($amount);

		$OUTPUT .= "
				<tr bgcolor='".bgcolorg()."'>
					<td>$date</td>
					<td>$daccname</td>
					<td>$caccname</td>
					<td align='right'>".CUR." $amount</td>
					<td>$author</td>
				</tr>";
	}

	return $OUTPUT;
}

function viewtrans($HTTP_POST_VARS)
{

	# Get vars
	extract ($HTTP_POST_VARS);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($prd, "string", 1, 14, "Invalid Period name.");
	$v->isOk ($type, "string", 1, 3, "Invalid Account type.");
	$v->isOk ($tab, "string", 1, 50, "Invalid Account type.");
	$v->isOk ($catid, "string", 1, 20, "Invalid Category.");

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

	# Get Category Name
	$cats = get("core","catname",$tab,"catid",$catid);
	$cat = pg_fetch_array($cats);
	// Set up table to display in
	$OUTPUT = "
			<center>
			<h3>Journal Entries for Category : $cat[catname]</h3>
			<table ".TMPL_tblDflts." width='80%'>
			<tr>
				<th>Date</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Amount</th>
				<th>Person Who Authorized</th>
			</tr>";

	core_connect();
	# Get accounts
	$type = strtoupper($type);
	$sql = "SELECT * FROM accounts WHERE catid='$catid' AND div = '".USER_DIV."'";
	$accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$numrows = pg_numrows ($accRslt);
	while($acc = pg_fetch_array($accRslt)){
			$OUTPUT .= viewtran($HTTP_POST_VARS,$acc['accid']);
	}
	$OUTPUT .= "
				<tr><td><br></td></tr>
				<tr>
					<td align='center' colspan='10'>
						<form action='../xls/cat-trans-xls.php' method='POST' name='form'>
							<input type='hidden' name='key' value='viewtrans'>
							<input type='hidden' name='type' value='$type'>
							<input type='hidden' name='tab' value='$tab'>
							<input type='hidden' name='prd' value='$prd'>
							<input type='hidden' name='catid' value='$catid'>
							<input type='submit' name='xls' value='Export to spreadsheet'>
						</form>
					</td>
				</tr>
			</table>\n
			<table ".TMPL_tblDflts." width='25%'>
				<tr><td><br></td></tr>
				<tr><th>Quick Links</th></tr>
				<tr class='datacell'>
					<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='index-reports.php'>Financials</a></td>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td>
				</tr>
				<tr class='datacell'>
					<td align='center'><a href='../main.php'>Main Menu</td>
				</tr>
			</table>";

	return $OUTPUT;
}
?>
