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

	$mdat=date("m");
	$mdat+=0;
	if($mdat>2) {
		$plus=1;
	} else {
		$plus=0;
	}

	$amonth=1;
	$amonths = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
	$asmonth = "<select name=activemonth>";
	while($amonth <= 12){
		if($amonth==$mdat) {
			$sel="selected";
		} else {
			$sel="";
		}
		$asmonth .="<option $sel value='$amonth'>$amonths[$amonth]</option>";
		$amonth++;
	}
	$asmonth .="</select>";

	$ayear=date("Y")+$plus;

	//layout
        $view = "<h3>Add New Branch</h3>
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <form action='".SELF."' method=post name=form>
        <input type=hidden name=key value=confirm>
		<tr><th>Field</th><th>Value</th></tr>
		<tr class='bg-odd'><td>Branch code</td><td><input type=text size=20 name='brancod' value='$brancod'></td></tr>
		<tr class='bg-even'><td>".REQ."Branch name</td></td><td><input type=text size=20 name='branname' value='$branname'></td></tr>
		<tr class='bg-odd'><td valign=top>Details</td><td><textarea cols=18 rows=5 name='brandet'>$brandet</textarea></textarea></td></tr>
		<tr><th colspan=2>Setup</th></tr>
		<tr class='bg-even'><td>Active Period</td><td valign=center>$asmonth</td></tr>
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

	$mdat=date("m");
	$mdat+=0;
	if($mdat>2) {
		$plus=1;
	} else {
		$plus=0;
	}

	$ayear=date("Y")+$plus;

	db_conn('core');

	$sql = "SELECT * FROM active";
	$cRs = db_exec($sql) or errDie("Database Access Failed - check year open.", SELF);

	$act = pg_fetch_array($cRs);
	$monset = "<input type=hidden name=smonth value='$act[prddb]'>";


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
	<input type=hidden name=yr1 value=y2003>
	<input type=hidden name=yr2 value=y2004>
	<input type=hidden name=yr3 value=y2005>
	<input type=hidden name=yr4 value=y2006>
	<input type=hidden name=yr5 value=y2007>
	<input type=hidden name=yr6 value=y2008>
	<input type=hidden name=yr7 value=y2009>
	<input type=hidden name=yr8 value=y2010>
	<input type=hidden name=yr9 value=y2011>
	<input type=hidden name=yr10 value=y2012>
	$monset
	<input type=hidden name=activemonth value='$activemonth'>
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



	db_conn('cubit');                                                                                                            // ,bc='$bc',vat='$vat',cs='$cs'

	//Select user selected account numbering
	$sql = "SELECT label FROM set WHERE label = 'ACCNEW_LNK' AND div = '$div'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing account creation settings.");
	if (pg_numrows ($Rslt) > 0) {
		$Sql = "UPDATE set SET value = 'acc-new2.php', type = 'Account Creation' WHERE label = 'ACCNEW_LNK'";
	}else{
		$Sql = "INSERT INTO set(type, label, value, descript, div) VALUES('Account Creation', 'ACCNEW_LNK', 'acc-new2.php', 'Use user selected account numbers', '$div')";
	}
	$setRslt = db_exec ($Sql) or errDie ("Unable to insert account creation settings to Cubit.");

	db_conn('core');

	//Insert Income Account Category
	$sql = "INSERT INTO income (catid, catname, div) VALUES ('I' || nextval('income_seq'), 'Income', '$div')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add income Category to Database.");

	# Get last inserted id for new cat
	$catid = pglib_getlastid ("income_seq");
	$catid = "I".$catid;

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('1000', '000', 'Sales','I', '$catid', 'f', 'sales', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$sales_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '1000', '000', 'Sales', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('1100', '000', 'Point of Sale - Sales','I', '$catid', 'f', 'sales', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$pos_sales_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '1100', '000', 'Point of Sale - Sales', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('1150', '000', 'Interest Received','I', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$interest_received_acc = $accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '1150', '000', 'Interest Received', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('1200', '000', 'Sundry Income','I', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '1200', '000', 'Sundry Income', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('1250', '000', 'Exchange Rate Profit/Loss','I', '$catid', 'f', 'other_income', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '1250', '000', 'Exchange Rate Profit/Loss', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);



	//Insert Expense Account Category
	$sql = "INSERT INTO expenditure (catid, catname, div) VALUES ('E' || nextval('expenditure_seq'),'Expenditure', '$div')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add expense Category to Database.");

	/*
	$sql = "SELECT * FROM expenditure";
	$catRslt = db_exec($sql) or errDie("Could not retrieve expense Categories Information from the Database.");
	$rows = pg_numrows($catRslt);
	if($rows < 1){
			return "There are no Account Categories under expen";
	}
	$cat = pg_fetch_array($catRslt);
	$catid=$cat['catid'];
	*/

	# Get last inserted id for new cat
	$catid = pglib_getlastid ("expenditure_seq");
	$catid = "E".$catid;


	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('2150', '000', 'Cost of Sales','E', '$catid', 'f', 'cost_of_sales', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$cost_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2150', '000', 'Cost of Sales', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2510', '000', 'Pension','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$pension_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2510', '000', 'Pension', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2520', '000', 'Retirement Annuity Fund','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$retiree_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2520', '000', 'Retirement Annuity Fund', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2530', '000', 'Provident Fund','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$providente_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2530', '000', 'Provident Fund', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2540', '000', 'Medical Aid','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$medical_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2540', '000', 'Medical Aid', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('2160', '000', 'Cost Variance','E', '$catid', 'f', 'cost_of_sales', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$costvar_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2160', '000', 'Cost Variance', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('2170', '000', 'Variance','E', '$catid', 'f', 'cost_of_sales', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$salesvar_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2170', '000', 'Variance', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2500', '000', 'Salaries and Wages','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$salaries_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2500', '000', 'Salaries and Wages', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2550', '000', 'Salaries - Commission','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$commision_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2550', '000', 'Salaries - Commission', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2560', '000', 'UIF','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$uifexp=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2560', '000', 'UIF', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2570', '000', 'SDL','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$sdlexp=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2570', '000', 'SDL', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2000', '000', 'Accounting Fees','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2000', '000', 'Accounting Fees', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2050', '000', 'Advertising and Promotions','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2050', '000', 'Advertising and Promotions', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2100', '000', 'Bank Charges','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2100', '000', 'Bank Charges', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2200', '000', 'Depreciation','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2200', '000', 'Depreciation', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2250', '000', 'Electricity and Water','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2250', '000', 'Electricity and Water', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2300', '000', 'General Expenses','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2300', '000', 'General Expenses', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2350', '000', 'Insurance','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2350', '000', 'Insurance', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2400', '000', 'Interest Paid','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2400', '000', 'Interest Paid', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2450', '000', 'Printing and Stationery','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2450', '000', 'Printing and Stationery', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2650', '000', 'Rent Paid','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2650', '000', 'Rent Paid', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, div) VALUES ('2600', '000', 'Telephone and Fax','E', '$catid', 'f', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2600', '000', 'Telephone and Fax', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);


	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('2700', '000', 'POS Rounding','E', '$catid', 'f', 'cost_of_sales', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$racc=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2700', '000', 'POS Rounding', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);


	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	//Insert Balance Account Category
	$sql = "INSERT INTO balance (catid, catname, div) VALUES ('B' || nextval('balance_seq'),'Balance', '$div')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add balance Category to Database.");

	/*
	$sql = "SELECT * FROM balance";
	$catRslt = db_exec($sql) or errDie("Could not retrieve income Categories Information from Cubit.");
	$rows = pg_numrows($catRslt);
	if($rows < 1){
			return "There are no Account Categories under income";
	}
	$cat = pg_fetch_array($catRslt);
	$catid=$cat['catid'];
	*/

	# Get last inserted id for new cat
	$catid = pglib_getlastid ("balance_seq");
	$catid = "B".$catid;

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6400', '000', 'Customer Control Account','B', '$catid', 'f', 'current_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$deptors_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6400', '000', 'Customer Control Account', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6500', '000', 'Supplier Control Account','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$creditors_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6500', '000', 'Supplier Control Account', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('7200', '000', 'Cash on Hand','B', '$catid', 'f', 'current_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$pos_cash_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '7200', '000', 'Cash on Hand', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('7300', '000', 'POS Credit Card Control','B', '$catid', 'f', 'current_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$cc=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '7300', '000', 'POS Credit Card Control', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('8000', '000', 'VAT Control Account','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$vat_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '8000', '000', 'VAT Control Account', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('8010', '000', 'VAT Input Account','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$vat_in=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '8010', '000', 'VAT Input Account', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('8020', '000', 'VAT Output Account','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$vat_out=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '8020', '000', 'VAT Output Account', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6350', '000', 'Inventory','B', '$catid', 'f', 'current_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$stock_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6350', '000', 'Inventory', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6300', '000', 'Inventory Suspense Account','B', '$catid', 'f', 'current_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sales Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$stock_control=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6300', '000', 'Inventory Suspense Account', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add sales Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6600', '000', 'Employees Control Account','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sasalaries control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$salary_control_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div ) VALUES('$accid', '6600', '000', 'Employees Control Account', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add salaries control Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('8400', '000', 'Pension Payable','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sasalaries control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$pensionc_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div ) VALUES('$accid', '8400', '000', 'Pension Payable', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add salaries control Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('8500', '000', 'Medical Aid Payable','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sasalaries control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$medicalc_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div ) VALUES('$accid', '8500', '000', 'Medical Aid Payable', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add salaries control Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('8600', '000', 'Retirement Annuity Fund Payable','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sasalaries control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$retire_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div ) VALUES('$accid', '8600', '000', 'Retirement Annuity Fund Payable', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add salaries control Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('8700', '000', 'Provident Fund Payable','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sasalaries control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$provident_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div ) VALUES('$accid', '8700', '000', 'Provident Fund Payable', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add salaries control Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('8100', '000', 'PAYE Payable','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sasalaries control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$paye_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '8100', '000', 'PAYE Payable', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add salaries control Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('8200', '000', 'UIF Payable','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sasalaries control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$uif_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '8200', '000', 'UIF Payable', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add uif control Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('8300', '000', 'SDL Payable','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sasalaries control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$sdlbal=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '8300', '000', 'SDL Payable', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add uif control Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6700', '000', 'Employee Loan Account','B', '$catid', 'f', 'current_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add sasalaries control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$loan_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6700', '000', 'Employee Loan Account', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add loan Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('7000', '000', 'Bank','B', '$catid', 'f', 'current_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$bank_account=$accid;

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '7000', '000', 'Bank', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('5200', '000', 'Retained Income / Accumulated Loss','B', '$catid', 'f', 'retained_income', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '5200', '000', 'Retained Income / Accumulated Loss', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('5250', '000', 'Share Capital / Members Contribution','B', '$catid', 'f', 'share_capital', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '5250', '000', 'Share Capital / Members Contribution', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('5300', '000', 'Shareholder / Director / Members Loan Account','B', '$catid', 'f', 'shareholders_loan', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '5300', '000', 'Shareholder / Director / Members Loan Account', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6000', '000', 'Land & Buildings - Net Value','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6000', '000', 'Land & Buildings - Net Value', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6000', '010', 'Land & Buildings - Cost','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6000', '010', 'Land & Buildings - Cost', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6000', '020', 'Land & Buildings - Accum Depreciation','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6000', '020', 'Land & Buildings - Accum Depreciation', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6100', '020', 'Motor Vehicle - Accum Depreciation','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6100', '020', 'Motor Vehicle - Accum Depreciation', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6100', '000', 'Motor Vehicle - Net Value','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6100', '000', 'Motor Vehicle - Net Value', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6100', '010', 'Motor Vehicle - Cost','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6100', '010', 'Motor Vehicle - Cost', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6150', '000', 'Computer Equipment - Net Value','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6150', '000', 'Computer Equipment - Net Value', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6150', '010', 'Computer Equipment - Cost','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6150', '010', 'Computer Equipment - Cost', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6150', '020', 'Computer Equipment - Accum Depreciation','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6150', '020', 'Computer Equipment - Accum Depreciation', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6160', '000', 'Office Equipment - Net Value','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6160', '000', 'Office Equipment - Net Value', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6160', '010', 'Office Equipment - Cost','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6160', '010', 'Office Equipment - Cost', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6160', '020', 'Office Equipment - Accum Depreciation','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6160', '020', 'Office Equipment - Accum Depreciation', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);


	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6170', '000', 'Furniture & Fittings - Net Value','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6170', '000', 'Furniture & Fittings - Net Value', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);



	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6170', '010', 'Furniture & Fittings - Cost','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6170', '010', 'Furniture & Fittings - Cost', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('6170', '020', 'Furniture & Fittings - Accum Depreciation','B', '$catid', 'f', 'fixed_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '6170', '020', 'Furniture & Fittings - Accum Depreciation', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('7100', '000', 'Petty Cash','B', '$catid', 'f', 'current_asset', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");
		$pettya=$accid;

		# Insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '7100', '000', 'Petty Cash', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# Write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('9000', '000', 'Opening Balances / Suspense Account','B', '$catid', 'f', 'current_liability', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add bank control Account to Database.", SELF);

		# Get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# Insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '9000', '000', 'Opening Balances / Suspense Account', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add bank Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	# Begin sql transaction
	pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		# Write to DB
		$sql = "INSERT INTO accounts (topacc, accnum, accname, acctype, catid, vat, toptype, div) VALUES ('2800', '000', 'Normal Tax','E', 'E10', 'f', 'tax', '$div')";
		$catRslt = db_exec ($sql) or errDie ("Unable to add Normal Tax Account to Database.", SELF);

		# Get last inserted id for new acc
		$accid = pglib_lastid ("accounts", "accid");

		# Insert account into trial Balance
		$query = "INSERT INTO trial_bal(accid, topacc, accnum, accname, vat, div) VALUES('$accid', '2800', '000', 'Normal Tax', 'f', '$div')";
		$trialRslt = db_exec($query) or errDie ("Unable to add Normal Tax Account to Database.", SELF);

	# Commit sql transaction
	pglib_transaction ("COMMIT") or errDie("Unable to commit a database transaction.",SELF);

	$sql = "SELECT * FROM bankacc WHERE name = 'Petty Cash' AND div = '$div'";
	$checkRslt = db_exec ($sql) or errDie ("Unable to retrieve Petty Cash Account details from database.");
	if (pg_numrows ($checkRslt) > 0){
		$link = "UPDATE bankacc SET accnum='$pettya' WHERE name = 'Petty Cash', '$div'";
	}else{
		$link = "INSERT INTO bankacc(name, accnum, div) VALUES('Petty Cash', '$pettya', '$div')";
	}
	$linkRslt = db_exec ($link) or errDie ("Unable to add Petty Cash Account link to Database.", SELF);


	# Check if year has been opened
	core_connect();
	$sql = "SELECT * FROM active";
	$cRs = db_exec($sql) or errDie("Database Access Failed - check year open.", SELF);
	if(pg_numrows($cRs) < 1){

		/* Skip Period Management */

		# Empty the year name table
		$sql = "DELETE FROM year";
		$rslt = db_exec($sql);
		for($i = 1; $i <= 10; $i++)
		{
			$sql = "INSERT INTO year VALUES('y".($selyear + $i - 1)."', 'yr$i', 'n', '$div')";
			$rslt = db_exec($sql) or errDie("Could not set year name in Cubit",SELF);
		}

		$yrname="y$selyear";

		$endmon = ($smonth - 1);
		if(intval($endmon == 0)) $endmon = 12;

		$Sql = "TRUNCATE range";
		$Rs = db_exec($Sql) or errDie("Unable to empty year range", SELF);

		$firstmonth = $smonth;
		$activeyear = $yrname;

		$sql = "INSERT INTO range(\"start\", \"end\", div) VALUES('$smonth', '$endmon', '$div')";
			$Rslt = db_exec($sql) or errDie("Unable to insert year range", SELF);

		$sql = "SELECT * FROM year WHERE yrname='$yrname'";
		$yrs = db_exec($sql);
		$yr = pg_fetch_array($yrs);
		if($yr['closed'] == 'y'){
			return "<center><li class=err>ERROR : The Selected Financial year : <b>$yrname</b> has been closed.
			<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		}


		$yrdb =$yr['yrdb'];

		$sql = "SELECT * FROM range";
		$Rslt = db_exec($sql);
		if(pg_numrows($Rslt) < 1){
			$OUTPUT = "<center><li class=err>ERROR : The Financial year Period range was not found on Database, Please make sure that everything is set during instalation.";
			require("template.php");
		}
		$range = Pg_fetch_array($Rslt);

		// Months array
		$months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

		$sql = "UPDATE active SET yrdb = '$yrdb', yrname = '$yrname',  prddb = '$range[start]', prdname='".$months[$range['start']]."' WHERE div = '$div'";
		$rslt = db_exec($sql) or errDie("Could not Set Next Year Database and Name",SELF);

		if(pg_cmdtuples($rslt) < 1){
			$sql = "INSERT INTO active (yrdb, yrname, prddb, prdname, div) VALUES ('$yrdb', '$yrname', '$range[start]', '".$months[$range['start']]."', '$div')";
			$rslt = db_exec($sql) or errDie("Could not Set Next Year Database and Name",SELF);
		}

		/* Skiped the period management stuff */
	} else {

		$firstmonth = $smonth;
	}
	$link = "INSERT INTO pchsacc(name, accnum, div) VALUES('Cost Variance', '$costvar_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "DELETE FROM salesacc WHERE name='VAT' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salesacc(name, accnum, div) VALUES('VAT', '$vat_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add vat Account link to Database.", SELF);

	$link = "DELETE FROM salesacc WHERE name='rounding' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salesacc(name, accnum, div) VALUES('rounding', '$racc', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add vat Account link to Database.", SELF);

	$link = "DELETE FROM salesacc WHERE name='VATIN' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salesacc(name, accnum, div) VALUES('VATIN', '$vat_in', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add vat Account link to Database.", SELF);

	$link = "DELETE FROM salesacc WHERE name='VATOUT' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salesacc(name, accnum, div) VALUES('VATOUT', '$vat_out', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add vat Account link to Database.", SELF);

	$link = "DELETE FROM salesacc WHERE name='sales_variance' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salesacc(name, accnum, div) VALUES('sales_variance', '$salesvar_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add vat Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='salaries' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('salaries', '$salaries_account', '$div')";
    	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='cash' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('cash', '$pos_cash_account', '$div')";
        $linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='cc' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('cc', '$cc', '$div')";
        $linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='salaries control' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('salaries control', '$salary_control_account', '$div')";
        $linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('salaries control original', '$salary_control_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='Commision' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('Commission', '$commision_account', '$div')";
        $linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='PAYE' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('PAYE', '$paye_account', '$div')";
        $linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='UIF' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('UIF', '$uif_account', '$div')";
        $linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='loanacc' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('loanacc', '$loan_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='uifexp' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('uifexp', '$uifexp', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='uifbal' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('uifbal', '$uif_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='sdlexp' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('sdlexp', '$sdlexp', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='sdlbal' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('sdlbal', '$sdlbal', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='pensionexpense' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('pensionexpense', '$pension_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='interestreceived' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete interest received Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('interestreceived', '$interest_received_acc', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add interest received link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='medicalexpense' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('medicalexpense', '$medical_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

        $link = "INSERT INTO salacc(name, accnum, div) VALUES('retire', '$retire_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

   	$link = "INSERT INTO salacc(name, accnum, div) VALUES('retireexpense', '$retiree_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('provident', '$provident_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Provident Fund Payable link to Database.", SELF);

   	$link = "INSERT INTO salacc(name, accnum, div) VALUES('providentexpense', '$providente_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Provident Fund Account link to Database.", SELF);

    $link = "DELETE FROM salacc WHERE name='pension' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('pension', '$pensionc_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	$link = "DELETE FROM salacc WHERE name='medical' AND div = '$div'";
	$linkRslt = db_exec ($link) or errDie ("Unable to delete vat Account link from Database.", SELF);

	$link = "INSERT INTO salacc(name, accnum, div) VALUES('medical', '$medicalc_account', '$div')";
	$linkRslt = db_exec ($link) or errDie ("Unable to add Salaries Account link to Database.", SELF);

	db_conn ("exten");

	$sql = "INSERT INTO departments(deptno, deptname, incacc, debtacc, credacc, pia, pca, div) VALUES ('1', 'Ledger 1', '$sales_account', '$deptors_account', '$creditors_account', '$pos_sales_account', '$pos_cash_account', '$div')";
	$deptRslt = db_exec ($sql) or errDie ("Unable to add deparment to system.", SELF);

	$sql = "INSERT INTO salespeople(salespno, salesp, div) VALUES ('1', 'General', '$div')";
	$salespRslt = db_exec ($sql) or errDie ("Unable to add warehouse to system.", SELF);

	$sql = "INSERT INTO  categories(category, div) VALUES ('General', '$div')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add category to system.", SELF);

	$sql = "INSERT INTO  class(classname, div) VALUES ('General', '$div')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add fringe benefit to system.", SELF);

	$sql = "INSERT INTO warehouses(whno, whname, stkacc, cosacc, conacc, div) VALUES ('1', 'Store 1', '$stock_account', '$cost_account', '$stock_control', '$div')";
	$whouseRslt = db_exec ($sql) or errDie ("Unable to add warehouse to system.", SELF);
	$whid = pglib_lastid ("warehouses", "whid");

	$sql = "INSERT INTO  pricelist(listname, div) VALUES ('Standard', '$div')";
	$catRslt = db_exec ($sql) or errDie ("Unable to price list to system.", SELF);

	db_conn ("cubit");

	$sql = "INSERT INTO stockcat(catcod, cat, descript, div) VALUES('1', 'General', 'General Stock Category', '$div')";
	$rslt = db_exec($sql) or errDie("Unable to insert stock category to Cubit.",SELF);

	$sql = "INSERT INTO stockclass(classcode, classname, div) VALUES ('1', 'General', '$div')";
	$catRslt = db_exec ($sql) or errDie ("Unable to add class to system.", SELF);

	# check if setting exists(default warehouse)
	$sql = "SELECT label FROM set WHERE label = 'DEF_WH' AND div = '$div'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		$Sql = "UPDATE set SET value = '$whid', type = 'Default Warehouse' WHERE label = 'DEF_WH' AND div = '$div'";
	}else{
		$Sql = "INSERT INTO set(type, label, value, descript, div) VALUES('Default Warehouse', 'DEF_WH', '$whid', '1 &nbsp;&nbsp;&nbsp; Store1', '$div')";
	}
	$setRslt = db_exec ($Sql) or errDie ("Unable to insert settings to Cubit.");

	# Check if setting exists (vat type)
	$sql = "SELECT label FROM set WHERE label = 'SELAMT_VAT' AND div = '$div'";
	$Rslt = db_exec ($sql) or errDie ("Unable to check database for existing settings.");
	if (pg_numrows ($Rslt) > 0) {
		$Sql = "UPDATE set SET value = 'inc', descript = 'Vat Inclusive' WHERE label = 'SELAMT_VAT' AND div = '$div'";
	}else{
		$Sql = "INSERT INTO set(type, label, value, descript, div) VALUES('Vat type on stock selling price', 'SELAMT_VAT', 'inc', 'Vat Inclusive', '$div')";
	}
	$setRslt = db_exec ($Sql) or errDie ("Unable to insert settings to Cubit.");

	# begin sql transaction
	# pglib_transaction ("BEGIN") or errDie("Unable to start a database transaction.",SELF);

		$sql = "INSERT INTO bankacct (acctype, bankname, branchname, branchcode, accname, accnum, details, div,btype) VALUES ('Cheque', 'Bank', 'Branch', '000000', 'Account Name', '000000000000', 'Default bank Account', '$div','loc')";
		$bankAccRslt = db_exec ($sql) or errDie ("Unable to add bank account to database.");

		$accid=pglib_lastid ("bankacct", "bankid");

	# Commit sql transaction
    # pglib_transaction ("COMMIT") or errDie("Unable to finish a database transaction.",SELF);

	db_conn ("core");

	$hook = "INSERT INTO bankacc(accid, accnum, div) VALUES('$accid', '$bank_account', '$div')";
	$Rlst = db_exec($hook) or errDie("Unable to add link for for new bank account", SELF);

	db_conn("crm");
	$Sl="INSERT INTO links (name,script) VALUES ('Add Client','../customers-new.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('View Client','../customers-view.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('New Invoice','../cust-credit-stockinv.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('Find Invoice','../invoice-search.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('View Stock','../stock-view.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('Add Supplier','../supp-new.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('View Suppliers','../supp-view.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('New Purchase','../purchase-new.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('View Purchases','../purchase-view.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('Add Quote','../quote-new.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('View Invoices','../invoice-view.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('View Quotes','../quote-view.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('Debtors Age Analysis','../reporting/debt-age-analysis.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('Creditors Age Analysis','../reporting/cred-age-analysis.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");
	$Sl="INSERT INTO links (name,script) VALUES ('Bank Reconciliation','../reporting/bank-recon.php')";
	$Ry=db_exec($Sl) or errDie("Unable to insert link.");

	$Sl="INSERT INTO teams (name,div) VALUES ('Sales','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO teams (name,div) VALUES ('Support','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO teams (name,div) VALUES ('Accounts','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO teams (name,div) VALUES ('Company Relations','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO teams (name,div) VALUES ('Purchasing - Supplier Relations','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Product Enquiries','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Place an Order','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Complain','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Account querries','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Delivery or Installation Tracking','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Comment on good service or Remarks','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Ask about employment','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('General','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Potential Supplier','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO tcats (name,div) VALUES ('Product Support','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into teams");

	$Sl="INSERT INTO actions (action) VALUES ('Called - Need to call again.')";
	$Ry=db_exec($Sl) or errDie("Unable to insert action.");

	$Sl="INSERT INTO actions (action) VALUES ('Called - Could not get in touch')";
	$Ry=db_exec($Sl) or errDie("Unable to insert action.");

	$Sl="INSERT INTO actions (action) VALUES ('Requested more information')";
	$Ry=db_exec($Sl) or errDie("Unable to insert action.");

	$Sl="INSERT INTO actions (action) VALUES ('Sent Fax')";
	$Ry=db_exec($Sl) or errDie("Unable to insert action.");

	$pactivemonth=$activemonth;

	$pactivemonth--;

	if($pactivemonth==0) {
		$pactivemonth=12;
	}

	$i=0;
	$current=$firstmonth;
	$current--;

	if($current==0) {
		$current=12;
	}

	while($current!=$pactivemonth) {
		$i++;

		if($i>20) {
			break;
		}

		$current++;

		if($current==13) {
			$current=1;
		}

		//close_month('yr1',$current);

	}

// 	//if(is/*set($firstmonth)) {
// 		$start=$firstmonth;
//
// 		for($i=1; $i<=12; $i++) {
// 			if(($i<$activemonth) and ($i>=$start)) {
// 				close_month('yr1',$i);
// 			}
// 		}
// //	}

	db_conn('core');
	$Sl="SELECT accid FROM accounts WHERE accname='Bank Charges'";
	$Ri=db_exec($Sl);

	$ad=pg_fetch_array($Ri);

	$bc=$ad['accid'];

	$Sl="SELECT accid FROM accounts WHERE accname='Interest Paid'";
	$Ri=db_exec($Sl);

	$ad=pg_fetch_array($Ri);

	$i=$ad['accid'];

	$Sl="SELECT accid FROM accounts WHERE accname='Interest Received'";
	$Ri=db_exec($Sl);

	$ad=pg_fetch_array($Ri);

	$ii=$ad['accid'];

	db_conn('exten');
	$Sl="INSERT INTO spricelist (listname,div) VALUES ('Standard','$div')";
	$Ry=db_exec($Sl) or errDie("Unable to insert into supplier price list.");

	db_conn('cubit');
	$Sl="INSERT INTO currency (symbol,descrip,rate,def) VALUES ('R','Rand',0.00,'')";
	$Ry=db_exec($Sl) or errDie("Unable to insert currency.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('CASH DEPOSIT FEE','i','-','c','$bc','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('FEE CHEQUE CASHED','i','-','c','$bc','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('FEE-SPECIAL PRESENTATION','i','-','c','$bc','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('SERVICE FEE','i','-','c','$bc','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('OVERDRAFT LEDGER FEE','i','-','c','$bc','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('INTEREST','i','-','c','$i','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('INTEREST','i','+','c','$ii','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('TRANSACTION CHARGE ','i','-','c','$bc','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('ADMIN CHARGE','i','-','c','$bc','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('GARAGE CRD CHARGES','i','-','c','$bc','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('STAMP DUTY','i','-','c','$bc','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('BANKING CHARGES','i','-','c','$bc','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO statement_refs (ref,dets,pn,action,account,by) VALUES ('01 CASH DEP','i','-','c','$bc','Default');";
	$Ry=db_exec($Sl) or errDie("Unable to insert data.");

//  	db_conn('cubit');
//
//  	$Sl="CREATE INDEX stkid_stock_key ON stock USING btree(stkid);";
//  	$Ri=db_exec($Sl) or errDie("Unable to index.");
//
//  	db_conn('core');
//
//  	$Sl="CREATE INDEX accid_accounts_key ON accounts USING btree(accid);";
//  	$Ri=db_exec($Sl) or errDie("Unable to index.");
//
// 	$Sl="CREATE INDEX accid_trial_bal_key ON trial_bal USING btree(accid);";
// 	$Ri=db_exec($Sl) or errDie("Unable to index.");
//
// 	/*for($p = 1; $p <=12; $p++){
// 		db_conn($p);
//
// 		$Sl="CREATE INDEX accid_accounts_key ON accounts USING btree(accid);";
//  		$Ri=db_exec($Sl) or errDie("Unable to index.");
//
//  		$Sl="CREATE INDEX accid_trial_bal_key ON trial_bal USING btree(accid);";
//  		$Ri=db_exec($Sl) or errDie("Unable to index.");
//
// 	}*/

	db_conn('cubit');

	$Sl="INSERT INTO vatcodes (code,description,del,zero,vat_amount) VALUES ('01','Normal','Yes','No','14');";
	$Ri=db_exec($Sl) or errDie("Unabel to insert data.");

	$Sl="INSERT INTO vatcodes (code,description,del,zero,vat_amount) VALUES ('02','Capital Goods','No','No','0');";
	$Ri=db_exec($Sl) or errDie("Unabel to insert data.");

	$Sl="INSERT INTO vatcodes (code,description,del,zero,vat_amount) VALUES ('03','Capital Goods','No','Yes','0');";
	$Ri=db_exec($Sl) or errDie("Unabel to insert data.");

	$Sl="INSERT INTO vatcodes (code,description,del,zero,vat_amount) VALUES ('04','Zero VAT','No','Yes','0');";
	$Ri=db_exec($Sl) or errDie("Unabel to insert data.");

	$Sl="INSERT INTO vatcodes (code,description,del,zero,vat_amount) VALUES ('05','VAT Exempt','No','Yes','0');";
	$Ri=db_exec($Sl) or errDie("Unabel to insert data.");

	$Sl="INSERT INTO login_retries (tries, minutes) VALUES ('0', '0');";
	$Ri=db_exec($Sl) or errDie("Unabel to insert data.");

	$Sl="INSERT INTO supp_groups (id, groupname) VALUES ('0', '[None]');";
	$Ri=db_exec($Sl) or errDie("Unabel to insert data.");

	$Sl="INSERT INTO template_settings (template, filename, div) VALUES ('statements', 'pdf/cust-pdf-stmnt.php', '$div');";
	$Ri=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO template_settings (template, filename, div) VALUES ('invoices', 'invoice-print.php', '$div');";
	$Ri=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO template_settings (template, filename, div) VALUES ('reprints', 'default', '$div');";
	$Ri=db_exec($Sl) or errDie("Unable to insert data.");

	$Sl="INSERT INTO workshop_settings (setting, value, div) VALUES ('workshop_conditions', 'As per display notice.', '$div');";
	$Ri=db_exec($Sl) or errDie("Unable to insert data.");

	db_conn('cubit');
	$Sql = "INSERT INTO set(type, label, value, descript, div) VALUES('Block main accounts', 'BLOCK', 'use', 'Block main accounts', '$div')";
	$Ri=db_exec($Sql);

	db_conn('exten');
	$Sl="INSERT INTO ct (days,div) VALUES ('0','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO ct (days,div) VALUES ('7','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO ct (days,div) VALUES ('14','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO ct (days,div) VALUES ('30','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO ct (days,div) VALUES ('60','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO ct (days,div) VALUES ('90','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO ct (days,div) VALUES ('120','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO od (days,div) VALUES ('0','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO od (days,div) VALUES ('7','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO od (days,div) VALUES ('14','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO od (days,div) VALUES ('30','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO od (days,div) VALUES ('60','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO od (days,div) VALUES ('90','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	$Sl="INSERT INTO od (days,div) VALUES ('120','$div')";
	$Ri=db_exec($Sl) or errDie("Unable to insert default terms");

	db_conn('core');

	block();






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

function printSet ()
{
	// Connect to database
	Db_Connect ();

	// Query server
	$sql = "SELECT * FROM set WHERE label = 'ACCNEW_LNK'";
	$rslt = db_exec ($sql) or errDie ("ERROR: Unable to view settings", SELF);          // Die with custom error if failed

	if (pg_numrows ($rslt) < 1) {
		$OUTPUT = "<li class=err> No Setting currently in Cubit.";
	} else {
		// Set up table to display in
		$OUTPUT = "
		<h3><li class=err>Error</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
		<tr><th>Setting Type</th><th>Current Setting</th></tr>";

		// display all settings
		$set = pg_fetch_array ($rslt);
		$bgColor = TMPL_tblDataColor1;

		$OUTPUT .= "<tr bgcolor='$bgColor'><td colspan=2>Cubit Account creation is already set to $set[descript], the quick setup cannot be used for this setting</td></tr>";
		$OUTPUT .= "</table>";
	}

	$OUTPUT .= "
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $OUTPUT;
}

function close_month($year,$month)
{

	$period=$month;

	if($month == 12){
		$nxprd = 1;
	}else{
		$nxprd = ($month + 1);
	}

	$months = array("1","January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
	$periodname = $months[$period];

	require("core/sel-next-prd.php");

        db_conn("audit");

        // CREATE new table with period name on audit
        $sql = "CREATE TABLE ".$periodname."(
        \"date\" date,
        \"debit\" varchar(255),
        \"credit\" varchar(255),
        \"amount\" numeric(30,2),
	\"refnum\" numeric(30),
	\"details\" varchar(255),
        \"author\" varchar(255),
	\"div\" numeric(20))";

        $rslt = db_exec($sql) or errDie("Unable to copy period transactions table to 'Audit' database.",SELF);

        // copy the current period transactions over to the new table
        $write ="";

	db_conn("audit");
	$sql = "INSERT INTO closedprd(prdnum, prdname) VALUES('$period', '$periodname')";
	$rs = db_exec($sql);

	$sql = "CREATE TABLE ".$periodname."_ledger (
	\"id\" numeric(50),
	\"acc\" numeric(50),
	\"contra\" numeric(50),
	\"edate\" date,
	\"sdate\" date,
	\"eref\" varchar(255),
	\"descript\" varchar(255),
	\"credit\" numeric(11,2),
	\"debit\" numeric(11,2),
	\"div\" numeric(50),
	\"caccname\" varchar(255),
	\"ctopacc\" varchar(255),
	\"caccnum\" varchar(255),
	\"cbalance\" numeric(11,2),
	\"dbalance\" numeric(11,2))";

	$rslt = db_exec($sql) or errDie("Unable to copy period ledger transactions table to 'Audit' database.",SELF);

	$sql = "CREATE TABLE ".$periodname."_empledger (
		id serial,
		empid numeric,
		contra numeric,
		edate date,
		ref character varying,
		descript character varying,
		debit numeric(13,2),
		credit numeric(13,2),
		dbalance numeric(13,2),
		cbalance numeric(13,2),
		sdate date,
		div numeric)";
	$rslt = db_exec($sql) or errDie("Unable to copy emp ledger transactions table to 'Audit' database.",SELF);

	$sql = "CREATE TABLE ".$periodname."_custledger(
	\"id\" serial,
	\"cusnum\" numeric(50),
	\"contra\" numeric(50),
	\"edate\" date,
	\"sdate\" date,
	\"eref\" varchar(255),
	\"descript\" varchar(255),
	\"credit\" numeric(11,2),
	\"debit\" numeric(11,2),
	\"dbalance\" numeric(11,2),
	\"cbalance\" numeric(11,2),
	\"div\" numeric(50));";

	$rslt = db_exec($sql) or errDie("Unable to copy Debtors ledger transactions table to 'Audit' database.",SELF);

	$sql = "CREATE TABLE ".$periodname."_suppledger(
	\"id\" serial,
	\"supid\" numeric(50),
	\"contra\" numeric(50),
	\"edate\" date,
	\"sdate\" date,
	\"eref\" varchar(255),
	\"descript\" varchar(255),
	\"credit\" numeric(11,2),
	\"debit\" numeric(11,2),
	\"dbalance\" numeric(11,2),
	\"cbalance\" numeric(11,2),
	\"div\" numeric(50));";

	$rslt = db_exec($sql) or errDie("Unable to copy Creditors ledger transactions table to 'Audit' database.",SELF);

	# copy trial balance table
        core_connect();
        $sql = "SELECT * FROM trial_bal";
        $trialBal = db_exec($sql) or errDie("Could not copy Balances to year DB",SELF);

        # write Trial Balance to year DB
        db_conn($year);

        // CREATE new table with period name
        $sql = "CREATE TABLE ".$periodname."(
		\"accid\" numeric(50),
		\"topacc\" varchar(255),
		\"accnum\" varchar(255),
        \"accname\" varchar(255),
        \"debit\" numeric(20,2),
        \"credit\" numeric(20,2),
		\"div\" numeric(20));";

        $Rslt = db_exec($sql) or errDie("Unable to create period table transaction to year database.",SELF);

        // copy the trial balance to the new table
        while($bal = pg_fetch_array($trialBal)){
			db_conn($year);
			$sql = "INSERT INTO ".$periodname." (accid, topacc, accnum, accname, debit, credit, div) VALUES('$bal[accid]', '$bal[topacc]', '$bal[accnum]', '$bal[accname]', '$bal[debit]', '$bal[credit]', '$bal[div]')";
			$inRslt = db_exec($sql) or print($sql);

			db_conn($nxprd);
			$sql = "INSERT INTO openbal (accid, accname, debit, credit, div) VALUES('$bal[accid]', '$bal[accname]', '$bal[debit]', '$bal[credit]', '$bal[div]')";
			$inRslt = db_exec($sql) or print($sql);

			db_conn($period);
			$tdate = date("Y-m-d");
			$sql = "INSERT INTO ledger(acc, contra, edate, eref, descript, credit, debit, div, caccname, ctopacc, caccnum, cbalance, dbalance)
			VALUES ('$bal[accid]','$bal[accid]', '$tdate', '0', 'Balance', '0', '0', '$bal[div]', '$bal[accname]', '$bal[topacc]', '$bal[accnum]', '$bal[credit]', '$bal[debit]')";
			$rslt2 = db_exec($sql) or errDie("Could not copy Ledger Transaction table to Audit database.", SELF);
        }

        $write .= "<center><h3> Current Period has been closed </h3>
        $ERROR
        <table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
        <tr><th>Quick Links</th></tr>
        <tr bgcolor='#88BBFF'><td><a href='yr-close.php'>Close Financial Year</a></td></tr>
        <tr bgcolor='#88BBFF'><td><a href='yr-open.php'>Open a New Financial Year</a></td></tr>
        <script>document.write(getQuicklinkSpecial());</script>
        </table></center>";

        return $write;
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










?>
