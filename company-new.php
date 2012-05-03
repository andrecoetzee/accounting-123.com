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

require ("newsettings.php");

if (isset ($_REQUEST["key"])) {
	switch ($_REQUEST["key"]) {
		case "recover":
			$OUTPUT = recover();
			break;
		case "confirm":
			$OUTPUT = confirm($_REQUEST);
			break;
		case "create":
			$OUTPUT = create($_REQUEST);
			break;
		case "creation":
			$OUTPUT = creation($_REQUEST);
			break;
		default:
			$OUTPUT = newcomp();
	}
} else {
	$OUTPUT = newcomp();
}

require("newtemplate.php");




function recover()
{

	db_con("cubit");
	$sql = "SELECT * FROM companies WHERE status='active'";
	$rslt = db_exec($sql);

	while ($r = pg_fetch_array($rslt)) {
		if (!exists_compdb($r["code"])) {
			db_con("cubit");
			$sql = "UPDATE companies SET status='no database' WHERE code='$r[code]'";
			db_exec($sql);
		}
	}

	db_con("cubit");
	$sql = "SELECT * FROM companies WHERE status='active'";
	$rslt = db_exec($sql);

	if (pg_num_rows($rslt) > 0) {
		session_destroy();
		header("Location: complogin.php");
		exit;
	}
	return newcomp();

}




# confirms
function newcomp ($err = "", $name="", $code="")
{

	global $_SESSION;

	$newcomp = "";
	if ( ! isset($_SESSION["USER_NAME"]) ) {
		$newcomp .= "
			<h3>Browser Notice</h3>
			<b>You need Firefox v1.5 or higher to use Cubit. Click
			<a class=nav target='_blank' href='http://www.mozilla.com/products/download.html?product=firefox-1.5.0.6&os=win&lang=en-US'>here</a> to download it.</b>";

		db_conn('cubit');
		$rslt = db_exec("SELECT * FROM companies WHERE status='active'");
		if(pg_numrows($rslt) > 0) {
			header("Location: complogin.php");
		}
	}

	if ( !isset($name) ) $name = "";

	$newcomp .= "
		<h3>Enter a name for Your Company</h3>
		<form action=".SELF." method='POST'>
			<input type='hidden' name='key' value='confirm'>
		<table cellpadding='1' cellspacing='1'>
			<tr>
				<td colspan='2'>$err</td>
			</tr>
			<tr>
				<th colspan='2'>New Company</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>".REQ."Company Name</td>
				<td align='center'><input type='text' size='25' name='name' value='$name'></td>
			</tr>
			<tr>
				<td><br></td>
			</tr>
			<tr>
				<td colspan='2' align='right'><input type='submit' value='Confirm &raquo;' /></td>
			</tr>
		</form>
		</table> <p>
			<table border=0 cellpadding='1' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='company-view.php'>View Companies</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $newcomp;

}




// Confirm
function confirm ($_POST)
{

	# get vars
	extract ($_POST);

	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($name,"string", 1,100, "Invalid company name.");

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>$e[msg]</li>";
		}
		return newcomp($confirmCust, $name, "");
	}

	db_conn("cubit");
	$rslt = db_exec("SELECT count(*) FROM companies");
	$num_companies = pg_fetch_result($rslt, 0, 0);

	if ( $num_companies == 0 ) {
		$code = "aaaa";
	} else {
		// generate code
		$code = "aaaa";

		// make sure it 4 chars long by padding with a's
		$code = str_replace(" ", "", $code);
		$code = str_pad($code, 4, 'a', STR_PAD_RIGHT);

		while ( 1 ) {
			// check if the code exists
            db_connect();
			$rslt = db_exec("SELECT * FROM companies WHERE code='$code'");

			// not exist! YAY!!
			if (pg_numrows($rslt) < 1 && !exists_compdb($code)) {
				break;
			}

	            // increase
			$code[3] = chr(ord($code[3]) + 1);
			for ( $i = 3; $i >= 0; $i-- ) {
				if ( ord($code[$i]) > ord('z') ) {
					$code[$i] = 'a';
					if ( $i > 0 )
						$code[$i-1] = chr( ord($code[$i-1]) + 1 );
					if ( substr($code, 0, 3) == "zzz")
						$code = "aaaa";
				}
			}
		}

		# Change code to lowercase
		$code = strtolower($code);
	}

	# Check Code and Name
	if(newlib_ex("cubit", "companies", "code", $code)){
		return newcomp("<li class=err> Company with the entered code already exists.", $name, $code);
	}
	if(newlib_ex("cubit", "companies", "name", $name)){
		return newcomp("<li class=err> Company with the entered name already exists.", $name, $code);
	}

	$confirm = "
		<h3>Confirm New Company</h3>
		<form action=".SELF." method='POST'>
			<input type='hidden' name='key' value='create'>
			<input type='hidden' name='name' value='$name'>
			<input type='hidden' name='code' value='$code'>
		<table cellpadding='1' cellspacing='1'>
			<tr>
				<th colspan='2'>New Company</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>Company Name</td>
				<td>$name</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>Company Code</td>
				<td>$code</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td><input type='submit' name='back' value='&laquo; Correction'></td>
				<td align='right'><input type='submit' value='Write &raquo;'></td>
			</tr>
		</form>
		</table>
		<p>
		<table border=0 cellpadding='1' cellspacing='1'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='company-view.php'>View Companies</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $confirm;

}




# Create the company
function create ($_POST)
{

	if(isset($_POST['back'])) {
		return newcomp("",$_POST['name'] , "");
	}

	/**
	 * so how does this progress work?
	 *
	 * just require the progress library and call displayProgress()
	 * specifying which template to use.
	 */
	require_lib("progress");
	displayProgress("newtemplate.php");

	return creation($_POST);

}




# Create the company
function creation ($_GET)
{

	# get vars
	extract ($_GET);

	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($name,"string", 2,100, "Invalid company name.");

	if (!preg_match("/[\d]/",$code)){
		$v->isOk ($code,"comp", 4,4, "Error assigning company code. Please try again.");
	}else {
		$v->isOk ($code,"comp", 10,5, "Error assigning company code. Please try again.");
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>".$e["msg"]."</li>";
		}
		return newcomp($confirmCust, $name, $code);
	}



	# Change code to lowercase
	$code = strtolower($code);

	# Check Code and Name
	if(newlib_ex("cubit", "companies", "code", $code)){
		return newcomp("<li class='err'> Error assigning company code. Please try again.", $name, $code);
	}
	if(newlib_ex("cubit", "companies", "name", $name)){
		return newcomp("<li class=err> Company with the entered name already exists.", $name, $code);
	}

	if (!exists_compdb($code)) {
		// create Cubits from the templates
		db_exec("CREATE DATABASE cubit_$code WITH template=cubit_blk1") or errDie("Unable to create company database.", SELF);
	}

	db_conn ("cubit");
	$sql  = "INSERT INTO companies (code, name, ver, status) VALUES ('$code', '$name', '".CUBIT_VERSION."', 'active')";
	$Rs = db_exec($sql) or errDie("Unable to insert company into Database.", SELF);

	global $CUBIT_MODULES;
	foreach ($CUBIT_MODULES as $modname) {
		$sql = "INSERT INTO comp_modules (code, module, version)
				VALUES('$code', '$modname', '".CUBIT_VERSION."')";
		db_exec($sql);
	}

	//$branch = branch("HO", "Head Office", "Head Office", $code);
	//$user = user($branch, $code);

	global $_SESSION;
	if ( isset($_SESSION["USER_NAME"]) ) {
		$create = "
			<h3>New Company has been created.</h3>
			<p>
			<table border=0 cellpadding='1' cellspacing='1'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='company-view.php'>View Companies</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	} else {
		$create = "
			<script>
				document.location.href='index.php';
			</script>";
	}
	return $create;

}




function user($div, $code)
{

	$username = "admin";
	$password = "123";
	$admin = 1;

	# Get md5 hash of password
	$password = md5 ($password);

	db_conn ("cubit_".$code);
	$sql = "
		INSERT INTO users (
			username, password, services_menu, admin, div
		) VALUES (
			'$username', '$password', 'L', $admin, '$div'
		)";
	$rs = db_exec($sql) or errDie("Unable to insert user into Database.", SELF);

	return 0;

}

# Returns the categories catid
function createcat($catname, $div, $type, $code)
{

	// core_connect();
	db_conn ("core_".$code);

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
	$sql = "
		INSERT INTO $tab (
			catid, catname, div
		) VALUES (
			'$type' || nextval('$seq'), '$catname', '$div'
		)";
	$catRslt = db_exec($sql) or errDie ("Unable to add Category to Database.");

	# Get last inserted id for new cat
	$catid = pglib_getlastid ("$seq");
	$catid = $type.$catid;

	return $catid;

}




# Creating an account,  returns status (0,1,2)
function createacc($topacc, $accnum, $accname, $catid, $acctype, $vat, $div, $code)
{

	# In case no upper case
	$acctype = strtoupper($acctype);

	// core_connect();
	db_conn ("core_".$code);

	# Check account number on selected branch
	$sql = "SELECT * FROM accounts WHERE topacc = '$topacc' AND accnum = '$accnum' AND div = '$div'";
	$cRslt = db_exec($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($cRslt) > 0) {
		return 1;
	}

	# Check account name on selected branch
	$sql = "SELECT * FROM accounts WHERE accname = '$accname' AND div = '$div'";
	$cRslt = db_exec($sql) or errDie ("Unable to retrieve Account details from database.");
	if (pg_numrows($cRslt) > 0) {
		return 2;
	}

	# Write to DB
	$Sql = "
		INSERT INTO accounts (
			topacc, accnum, accname, acctype, catid, vat, div
		) VALUES (
			'$topacc', '$accnum', '$accname', '$acctype', '$catid', '$vat', '$div'
		)";
	$accRslt = db_exec($Sql) or errDie ("Unable to add Account to Database.", SELF);

	# Get last inserted id for new acc
	$accid = pglib_lastid ("accounts", "accid");

	# Insert account into trial Balance
	$query = "
		INSERT INTO trial_bal (
			accid, topacc, accnum, accname, vat, div
		) VALUES (
			'$accid', '$topacc', '$accnum', '$accname', '$vat', '$div'
		)";
	$trialRslt = db_exec($query) or errDie ("Unable to add Account to Database.", SELF);

	# Return Zero on success
	return 0;

}




# Write
function branch($brancod, $branname, $brandet, $code)
{

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
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		return $confirm;
	}

	# Check stock code
	// db_connect();
	db_conn ("cubit_".$code);

	$sql = "SELECT branname FROM branches WHERE lower(branname) = lower('$branname')";
	$cRslt = db_exec($sql);
	if(pg_numrows($cRslt) > 0){
		$error = "<li class='err'>Branch name : <b>$branname</b> already exists.</li>";
		$error .= "<p><input type='button' onClick='history.back();' value='&laquo; Correct submission'>";
		return $error;
	}

	// Insert into stock
	// db_connect();
	db_conn ("cubit_".$code);
	$sql = "
		INSERT INTO branches (
			brancod, branname, brandet
		) VALUES (
			'$brancod', '$branname', '$brandet'
		)";
	$rslt = db_exec($sql) or errDie("Unable to insert branch to Cubit.",SELF);

	$div = pglib_lastid ("branches", "div", $code);

	// Insert sequences
	$sql = "INSERT INTO seq(type, last_value, div) VALUES('inv', '0', '$div')";
	$rslt = db_exec($sql) or errDie("Unable to insert branch to Cubit.",SELF);
	$sql = "INSERT INTO seq(type, last_value, div) VALUES('pur', '0', '$div')";
	$rslt = db_exec($sql) or errDie("Unable to insert branch to Cubit.",SELF);
	$sql = "INSERT INTO seq(type, last_value, div) VALUES('note', '0', '$div')";
	$rslt = db_exec($sql) or errDie("Unable to insert branch to Cubit.",SELF);

	# Create Default Accounts
	// Profit/Loss account (999/999)
	$catid = createcat("Profit/Loss", $div, "B", $code);
	if(createacc("9999", "999", "Profit/Loss account", $catid, "B", "n", $div, $code) > 0){
		return "<li class='err'>Failed to create default accounts</li>";
	}

	// Total Income account (199/999)
	$catid = createcat("Total Income", $div, "I", $code);
	if(createacc("1999", "999", "Total Income account", $catid, "I", "n", $div, $code) > 0){
		return "<li class='err'>Failed to create default accounts</li>";
	}

	// Total Expenses account (499/999)
	$catid = createcat("Total Expenses", $div, "E", $code);
	if(createacc("4999", "999", "Total Expenses account", $catid, "E", "n", $div, $code) > 0){
		return "<li class='err'>Failed to create default accounts</li>";
	}

	return $div;

}



?>