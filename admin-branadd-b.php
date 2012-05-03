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

# Decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
            case "confirm":
				$OUTPUT = confirm($_POST);
				break;

			case "write":
            	$OUTPUT = write($_POST);
				break;

			default:
				$OUTPUT = view($_POST);
	}
} else {
        # Display default output
        $OUTPUT = view($_POST);
}

# Get template
require("template.php");

# Default view
function view($_POST)
{

	extract($_POST);
	
	if(!isset($brancod)) {
		$brancod="";
		$branname="";
		$brandet="";
	}

	//layout
        $view = "<h3>Add New Branch</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
		<tr><th>Field</th><th>Value</th></tr>
		<tr class='bg-odd'><td>Branch code</td><td><input type=text size=20 name='brancod' value='$brancod'></td></tr>
		<tr class='bg-even'><td>".REQ."Branch name</td></td><td><input type=text size=20 name='branname' value='$branname'></td></tr>
		<tr class='bg-odd'><td valign=top>Details</td><td><textarea cols=18 rows=5 name='brandet'>$brandet</textarea></textarea></td></tr>
		<tr><td><br></td></tr>
		<tr><td></td><td valign=center align=right><input type=submit value='Confirm &raquo'></td></tr>
		</form>
        </table>
		<p>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
        <tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='admin-branview.php'>View Branches</a></td></tr>
			<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
        </table>";

        return $view;
}

# Confirm
function confirm($_POST)
{
		# get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}
		# validate input
		require_lib("validate");
		$v = new  validate ();
		$v->isOk ($brancod, "string", 0, 50, "Invalid branch code.");
		$v->isOk ($branname, "string", 1, 255, "Invalid branch name.");
		$v->isOk ($brandet, "string", 0, 255, "Invalid branch details.");

		# display errors, if any
		if ($v->isError ()) {
			$confirm = "";
			$errors = $v->getErrors();
			foreach ($errors as $e) {
				$confirm .= "<li class=err>$e[msg]</li>";
			}
			return $confirm.view($_POST);
		}

		# Check stock code
		db_connect();
		$sql = "SELECT branname FROM branches WHERE lower(branname) = lower('$branname')";
		$cRslt = db_exec($sql);
		if(pg_numrows($cRslt) > 0){
			$error = "<li class=err>Branch name : <b>$branname</b> already exists.</li>";
			$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
			return $error;
		}

		// Layout
		$confirm =
		"<h3>Add Branch</h3>
		<h4>Confirm entry</h4>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method=post>
		<input type=hidden name=key value=write>
		<input type=hidden name=brancod value='$brancod'>
		<input type=hidden name=branname value='$branname'>
		<input type=hidden name=brandet value='$brandet'>
		<tr><th width=40%>Field</th><th width=60%>Value</th></tr>
			<tr class='bg-odd'><td>Branch Code</td><td>$brancod</td></tr>
			<tr class='bg-even'><td>Branch Name</td></td><td>$branname</td></tr>
			<tr class='bg-odd'><td valign=top>Details</td><td><pre>$brandet</pre></td></tr>
			<tr><td><br></td></tr>
			<tr><td><input type=submit name=back value='&laquo; Correction'></td><td align=right><input type=submit value='Write &raquo'></td></tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=100>
			<tr><th>Quick Links</th></tr>
			<tr class='bg-odd'><td><a href='admin-branview.php'>View Branches</a></td></tr>
			<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
		</table>";

		return $confirm;
}

# Returns the categories catid
function createcat($catname, $div, $type)
{
	core_connect();

	# In case no upper case
	$type = strtoupper($type);

	switch($type){
		case "I":
			$tab = "income";
			break;

		case "B":
			$tab = "balance";
			break;

		case "E":
			$tab = "expenditure";
			break;

		default:
			return "<li> Invalid Category type";
	}

	# Make seq
	$seq = $tab."_seq";

	# Insert Category
	$sql = "INSERT INTO $tab (catid, catname, div) VALUES ('$type' || nextval('$seq'), '$catname', '$div')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add Category to Database.");

	# Get last inserted id for new cat
	$catid = pglib_getlastid ("$seq");
	$catid = $type.$catid;

	return $catid;
}

# Creating an account,  returns status (0,1,2)
function createacc($topacc, $accnum, $accname, $catid, $acctype, $vat, $div)
{
	# In case no upper case
	$acctype = strtoupper($acctype);

	core_connect();

	# Check account number on selected branch
	$sql = "SELECT * FROM accounts WHERE topacc = '$topacc' AND accnum = '$accnum' AND div = '$div'";
	$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($cRslt) > 0) {
		return 1;
	}

	# Check account name on selected branch
	$sql = "SELECT * FROM accounts WHERE accname = '$accname' AND div = '$div'";
	$cRslt = db_exec ($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($cRslt) > 0) {
		return 2;
	}

	# Write to DB
	$Sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('$topacc', '$accnum', '$accname', '$acctype', '$catid', '$vat', '$div')";
	$accRslt = db_exec ($Sql) or errDie ("Unable to add Account to Database.", SELF);

	# Get last inserted id for new acc
	$accid = pglib_lastid ("accounts", "accid");

	# Insert account into trial Balance
	$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '$topacc', '$accnum', '$accname', '$vat', '$div')";
	$trialRslt = db_exec($query) or errDie ("Unable to add Account to Database.", SELF);

	# Return Zero on success
	return 0;
}

# Write
function write($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	
	if(isset($back)) {
		return view($_POST);
	}
	
	# Validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($brancod, "string", 0, 50, "Invalid branch code.");
	$v->isOk ($branname, "string", 1, 255, "Invalid branch name.");
	$v->isOk ($brandet, "string", 0, 255, "Invalid branch details.");

	# Display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>$e[msg]</li>";
		}
		$confirm .= "</li><p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}
	
	# Check stock code
	db_connect();
	$sql = "SELECT branname FROM branches WHERE lower(branname) = lower('$branname')";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class=err>Branch name : <b>$branname</b> already exists.</li>";
		$error .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	// Insert into stock
	db_connect();
	$sql = "INSERT INTO branches(brancod, branname, brandet) VALUES('$brancod', '$branname', '$brandet')";
	$rslt = db_exec($sql) or errDie("Unable to insert branch to Cubit.",SELF);

	$div = pglib_lastid ("branches", "div");

	/*
	// Insert sequences
	$sql = "INSERT INTO seq(type, last_value, div) VALUES('inv', '0', '$div')";
	$rslt = db_exec($sql) or errDie("Unable to insert branch to Cubit.",SELF);
	$sql = "INSERT INTO seq(type, last_value, div) VALUES('pur', '0', '$div')";
	$rslt = db_exec($sql) or errDie("Unable to insert branch to Cubit.",SELF);
	$sql = "INSERT INTO seq(type, last_value, div) VALUES('note', '0', '$div')";
	$rslt = db_exec($sql) or errDie("Unable to insert branch to Cubit.",SELF);
	*/

	# Create Default Accounts
	// Profit/Loss account (999/999)
	$catid = createcat("Profit/Loss", $div, "B");
	if(createacc("9999", "999", "Profit/Loss account", $catid, "B", "n", $div) > 0){
		return "<li class=err>Failed to create default accounts</li>";
	}

	// Total Income account (199/999)
	$catid = createcat("Total Income", $div, "I");
	if(createacc("1999", "999", "Total Income account", $catid, "I", "n", $div) > 0){
		return "<li class=err>Failed to create default accounts</li>";
	}

	// Total Expenses account (499/999)
	$catid = createcat("Total Expenses", $div, "E");
	if(createacc("4999", "999", "Total Expenses account", $catid, "E", "n", $div) > 0){
		return "<li class=err>Failed to create default accounts</li>";
	}

	// Layout
	$write ="
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width='50%'>
	<tr><th>New Branch added to database</th></tr>
	<tr class=datacell><td>New Branch, $branname ($brancod) has been successfully added to Cubit.</td></tr>
	</table>
	<p>
	<table border=0 cellpadding='2' cellspacing='1'>
		<tr><th>Quick Links</th></tr>
		<tr class='bg-odd'><td><a href='admin-branview.php'>View Branches</a></td></tr>
		<tr class='bg-odd'><td><a href='main.php'>Main Menu</a></td></tr>
	</table>";

	return $write;
}
?>
