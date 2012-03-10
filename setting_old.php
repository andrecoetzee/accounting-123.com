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

# If this script is called by itself, abort
if (basename (getenv ("SCRIPT_NAME")) == "settings.php") {
	exit;
}

# Get pglib
require ("libs/pgsql.lib.php");

// Set error reporting to E_ALL while debugging, E_NONE when live
error_reporting (E_ALL);

function remval ($value)
{
	if(!isset($value)) {return "Invalid use of function";}
	$value = str_replace("!","",$value);
	$value = str_replace("=","",$value);
	$value = str_replace("#","",$value);
	$value = str_replace("%","",$value);
	$value = str_replace("$","",$value);
	$value = str_replace("*","",$value);
	$value = str_replace("^","",$value);
	$value = str_replace("?","",$value);
	$value = str_replace("[","",$value);
	$value = str_replace("]","",$value);
	$value = str_replace("{","",$value);
	$value = str_replace("}","",$value);
	$value = str_replace("|","",$value);
	$value = str_replace(":","",$value);
	$value = str_replace("+","",$value);
	$value = str_replace("'","",$value);
	$value = str_replace("`","",$value);
	$value = str_replace("~","",$value);
	$value = str_replace("\\","",$value);
	$value = str_replace("\"","",$value);
	$value = str_replace("\"","",$value);
	$value = str_replace(";","",$value);
	$value = str_replace("<","",$value);
	$value = str_replace(">","",$value);
	$value = str_replace("$","",$value);
	return $value;
}

function sprint ($value)
{
	$value += 0;
	$value = round($value,2);
	$value =sprintf("%01.2f", ($value));
	return $value;
}

// seed random number generator & get md5 of random number
srand ((double) microtime() * 1000000);
define ("RAND_NO", rand());
define ("RAND_MD5", md5 (RAND_NO));

// Account type settings
define ("MIN_INC", "1");
define ("MAX_INC", "1999");
define ("MIN_EXP", "2000");
define ("MAX_EXP", "4999");
define ("MIN_BAL", "5000");
define ("MAX_BAL", "9999");

# Purchases exceeding this must be authorized first
define ("MAX_PCHS", 2500);

// Database settings
define ("DB_USER", "postgres");
define ("DB_PASS", "i56kfm");
define ("DB_DB", "cubit");

// Some Settings
# Taxes, etc
# define ("TAX_VAT", 14);
db_connect();
$sql ="SELECT * FROM settings WHERE constant = 'TAX_VAT'";
$setRslt = db_exec($sql);
$set = pg_fetch_array($setRslt);
if(pg_numrows($setRslt) < 1){
	define ("TAX_VAT", 14);
}else{
	define ("TAX_VAT", $set['value']);
}

// Company details
db_connect();
$sql ="SELECT * FROM compinfo";
$Rslt = db_exec($sql);
$com = pg_fetch_array($Rslt);

define ("COMP_NAME", $com['compname']);
define ("COMP_SLOGAN", $com['slogan']);
define ("COMP_LOGO", $com['logoimg']);
define ("COMP_ADDRESS", $com['addr1']."<br>".$com['addr2']."<br>".$com['addr3']."<br>".$com['addr4']);
define ("COMP_PADDR", "$com[paddr1], $com[paddr2], $com[paddr3], $com[pcode]");
define ("COMP_TEL", $com['tel']);
define ("COMP_REGNO", $com['regnum']);
define ("COMP_FAX", $com['fax']);
define ("COMP_VATNO", $com['vatnum']);

// HTML & layout settings
define ("TMPL_bgColor", "#4477BB");
define ("TMPL_title", "Cubit Accounting");
define ("TMPL_fntColor", "#FFFFFF");
define ("TMPL_fntColor2", "#000000");
define ("TMPL_lnkColor", "#0000DD");
define ("TMPL_lnkHvrColor", "#FF0000");
define ("TMPL_navLnkColor", "#CCCCCC");
define ("TMPL_navLnkHvrColor", "#FFFFFF");
define ("TMPL_fntSize", 10);
define ("TMPL_fntFamily", "ariel");
define ("TMPL_h3FntSize", 12);
define ("TMPL_h3Color", "#FFFFFF");
define ("TMPL_h4FntSize", 10);
define ("TMPL_h4Color", "#FFFFFF");

// Table settings
define ("TMPL_tblBrdrColor", "#FFFFFF");  // table border color
define ("TMPL_tblCellSpacing", 1);
define ("TMPL_tblCellPadding", 1);
define ("TMPL_tblDataColor1", "#88BBFF");  // bgcolor for data cells
define ("TMPL_tblDataColor2", "#77AAEE");  // alternate bgcolor for data cells
define ("TMPL_tblDataColorOver", "#FFFFFF");  // mouse over bgcolor for data cells
define ("TMPL_tblHdngBg", "#114488");   // bgcolor for cell-headings
define ("TMPL_tblHdngColor", "#FFFFFF");   // bgcolor for cell-headings

// Calendar settings
define ("TMPL_calTimeStyle1","style='border-right: 2px solid #A1A5A9; border-top: 1px dashed #A1A5A9; background-image: url(time_bg.gif);' ");
define ("TMPL_calTimeStyle2","style='border: 1px solid #A1A5A9; border-bottom: none; background-image: url(time_bg.gif);' ");
define ("TMPL_calTimeStyleHeader","style='font-size: 16px;' ");

define ("TMPL_calEntryStyle1","style='border-top: 1px solid #A1A5A9; background: white; cursor: pointer; cursor: hand;' ");
define ("TMPL_calEntryStyle2","style='border-top: 1px dashed #CCCCCC; background: white; cursor: pointer; cursor: hand;' ");
define ("TMPL_calEntryStyleBody","style='cursor: pointer; cursor: hand;' ");
define ("TMPL_calEntryStyleTitle","style='border: 1pt solid black; cursor: pointer; cursor: hand;' ");
define ("TMPL_calEntryStyleEntire","style='border: 1pt solid black; cursor: pointer; cursor: hand;' ");

define ("TMPL_calNoticesStyle","style='font-size: 15px; color: #FFFFFF; font-weight: bold;' ");
define ("TMPL_calNoticesLink_a","#e8e8e8");
define ("TMPL_calNoticesLink_h","#FFFFFF");

define ("TMPL_calTodayLinkStyle","style='font-size: 13px; border: 1px solid #A1A5A9; border-bottom: none; background-image: url(side_bg.gif); cursor: pointer; cursor: hand;' ");
define ("TMPL_calSmallMonthTitleStyle","style='font-size: 12px; border: 1px solid #A1A5A9; border-bottom: none; background-image: url(side_bg.gif);' ");
define ("TMPL_calSmallMonthBodyStyle","style='font-size: 12px; border: 1px solid #A1A5A9; border-top: none; background: #FFFFFF;' ");
define ("TMPL_calSmallMonthWeekNumberStyle","style='font-size: 9px; color: #666666;'");

define ("TMPL_calSmallMonthOMLink_a","#A1A5A9");
define ("TMPL_calSmallMonthOMLink_h","#A1A5A9");
define ("TMPL_calSmallMonthCMLink_a","#0066FF");
define ("TMPL_calSmallMonthCMLink_h","#000099");
define ("TMPL_calSmallMonthCMLinkToday_a","#FFFFFF");
define ("TMPL_calSmallMonthCMLinkToday_h","#000099");
define ("TMPL_calSmallMonthCMLinkSelected_a","red");
define ("TMPL_calSmallMonthCMLinkSelected_h","#000099");

define ("TMPL_calSmallMonthCurrentDay","#8F8FFF");
define ("TMPL_calSmallMonthCurrentWeek","#C0C0FF");
define ("TMPL_calSmallMonthSelectedDay","#81e8b6");
define ("TMPL_calSmallMonthSelectedWeek","#81e8b6");

define ("TMPL_calFillSaturday","#f7f7f7");
define ("TMPL_calFillSunday","#eaeaea");

define ("TMPL_calRefreshTime",60);

define ("TMPL_calAppointmentStyle","style='font-size: 14px; color: #FFFFFF;'");

// Get the environment variables in securely
define ("REFERER", getenv ("HTTP_REFERER"));
define ("SELF", basename (getenv ("SCRIPT_NAME")));

// Email addies
define ("EMAIL_ADMIN", "root");

// Date
define ("DATE_LOGGING", date ("Y.m.d H.i.s"));  // for logging eg: 2002.07.17 10.33.55
define ("DATE_STD", date ("Y-m-d"));  // standard date eg: 2002-08-12

// session timeout (minutes)
define ("SESSION_TIMEOUT", "20");

# danger level for stock (percentage)
define ("DANGER_LVL", 5);

##
# Accounting settings
##

##
# Functions
##

// Function to connect to db server
function Db_Connect ()
{
	$link = pg_connect ("user=".DB_USER." password=".DB_PASS." dbname=".DB_DB) or errDie ("Unable to connect to database server.", SELF);
	return $link;
}

// Function to connect to core db server
function core_Connect ()
{
	$link = pg_connect ("user=".DB_USER." password=".DB_PASS." dbname=core") or errDie ("Unable to connect to database server.", SELF);
	return $link;
}

// Connect to a specific Database
function db_Conn ($db)
{
	$link = pg_connect ("user=".DB_USER." password=".DB_PASS." dbname=".$db) or errDie ("Unable to connect to database server.", SELF);
	return $link;
}

// Function to report errors using the template
function errDie ($usrString)
{
	$ERROR = DATE_LOGGING." - ".SELF." - ".USER_NAME." - ".$usrString;    // Create error from scriptname & user string

	if (pg_ErrorMessage ()) {
		$ERROR .= " - ". pg_ErrorMessage ();
	}

	// log error to file
	$ERROR_LOG = fopen ("error_log", 'a') or die ("ERROR while opening error_log. Please notify the administrator.");
	flock ($ERROR_LOG, LOCK_EX) or die ("ERROR obtaining file lock on error_log. Please notify the administrator.");
	fwrite ($ERROR_LOG, $ERROR . "\n") or die ("ERROR writing to error_log. Please notify the administrator.");
	flock ($ERROR_LOG, LOCK_UN);
	fclose ($ERROR_LOG);
	$OUTPUT = $usrString." Error has been logged, please notify the administrator.";
	require ("template.php");
}

// Function to report errors using the template
function errDie ($usrString)
{
	$ERROR = DATE_LOGGING." - ".SELF." - ".USER_NAME." - ".$usrString;    // Create error from scriptname & user string

	if (pg_ErrorMessage ()) {
		$ERROR .= " - ". pg_ErrorMessage ();
	}

	// log error to file
	$ERROR_LOG = fopen ("error_log", 'a') or die ("ERROR while opening error_log. Please notify the administrator.");
	flock ($ERROR_LOG, LOCK_EX) or die ("ERROR obtaining file lock on error_log. Please notify the administrator.");
	fwrite ($ERROR_LOG, $ERROR . "\n") or die ("ERROR writing to error_log. Please notify the administrator.");
	flock ($ERROR_LOG, LOCK_UN);
	fclose ($ERROR_LOG);
	$OUTPUT = $usrString." Error has been logged, please notify the administrator.";
	require ("template.php");
}

# database, get field from table where fieldname = filter
function get($database,$field,$table,$filtername, $filter)
{
	$sql = "SELECT ".$field." FROM ".$table." WHERE ".$filtername."='".$filter."' AND div = '".USER_DIV."'";
	db_conn($database) or errDie ("Unable to connect to database server.", SELF);
	$rslt = db_exec($sql);
	return $rslt;
}

# Sequence impl
function divlastid($type, $div)
{
	db_connect();

	$sql = "SELECT last_value FROM seq WHERE lower(type) = lower('".$type."') AND div = '$div'";
	$rslt = db_exec($sql) or errDie ("Unable to get sequence impl from Cubit.");
	if(pg_numrows($rslt) < 1){
		errDie("Sequnce type: '$type' not found in Cubit");
	}
	$seq = pg_fetch_array($rslt);
	$nextval = ($seq['last_value'] + 1);

	$sql = "UPDATE seq SET last_value = '$nextval' WHERE lower(type) = lower('".$type."') AND div = '$div'";
	$rslt = db_exec($sql) or errDie ("Unable to update sequence impl in Cubit.");

	return $nextval;
}


# Get a standard hook acc name
function gethook($field,$table,$filtername, $filter)
{
$sql = "SELECT ".$field." FROM ".$table." WHERE ".$filtername."='".$filter."' AND div = '".USER_DIV."'";
$conn = pg_connect ("user=".DB_USER." password=".DB_PASS." dbname='core'") or errDie ("Unable to connect to database server.", SELF);
$rslt = db_exec($conn, $sql) or errDie("Unable to get account link.",SELF);
if(pg_numrows($rslt) < 1){
        $OUTPUT = "<li>ERROR : The Account link for <b>$filter</b> was not found, Please Set all account links on the Current module";
        require ("template.php");
        exit;
}
$hook = pg_fetch_array($rslt);
        return $hook['accnum'];
}

########################
session_name ("CUBIT_SESSION");
session_start ();

$login = true;

if($login){
	# login logic
	if (isset ($HTTP_POST_VARS["login_user"]) && isset ($HTTP_POST_VARS["login_pass"]) && isset ($HTTP_POST_VARS["login"])) {
		checkLogin ($HTTP_POST_VARS["login_user"], md5 ($HTTP_POST_VARS["login_pass"]));
		define ("USER_NAME", $HTTP_SESSION_VARS["USER_NAME"]);
		define ("USER_ID", $HTTP_SESSION_VARS["USER_ID"]);
		define ("USER_DIV", $HTTP_SESSION_VARS["USER_DIV"]);
		define ("BRAN_NAME", $HTTP_SESSION_VARS["BRAN_NAME"]);
		define ("ABO",$HTTP_SESSION_VARS["ABO"]);
	} elseif (empty ($HTTP_SESSION_VARS["USER_NAME"]) || empty ($HTTP_SESSION_VARS["USER_ID"])) {
		login ();
	} else {
		# define constants (for inside scripts)
		define ("USER_NAME", $HTTP_SESSION_VARS["USER_NAME"]);
		define ("USER_ID", $HTTP_SESSION_VARS["USER_ID"]);
		define ("USER_DIV", $HTTP_SESSION_VARS["USER_DIV"]);
		define ("BRAN_NAME", $HTTP_SESSION_VARS["BRAN_NAME"]);
		define ("ABO",$HTTP_SESSION_VARS["ABO"]);
	}

	# connect to db
	db_connect ();

	checkPreferences();

	if(!($user_admin
	|| basename (getenv ("SCRIPT_NAME")) == "main.php"
	|| basename (getenv ("SCRIPT_NAME")) == "index.php"
	|| basename (getenv ("SCRIPT_NAME")) == "pos-rem.php"
	||basename (getenv ("SCRIPT_NAME")) == "top_menu.php")){
			# check permission
			$chk = "SELECT * FROM userscripts WHERE username = '$HTTP_SESSION_VARS[USER_NAME]' AND script ='".basename (getenv ("SCRIPT_NAME"))."'";
			$chkRslt = db_exec($chk) or errDie("Unable to check user access permissions",SELF);
			if(pg_numrows($chkRslt) < 1){
					$OUTPUT = "<li class=err>You <b>don't have sufficient permissions</b> to use this command.".getenv ("SCRIPT_NAME");
					require("template.php");
			}
	}
}

# Login func
function login ()
{
	# connect to db
	db_connect ();


	# Select the stock category
	db_connect();
	
	# get existing users
	$users = "<select size=1 name=login_user>\n";
	$sql = "SELECT username FROM users ORDER BY username";
	$usrRslt = db_exec ($sql) or die ("Unable to get usernames from database.");
	if (pg_numrows ($usrRslt) < 1) {
		$OUTPUT = "No users found in database.";
		require ("template.php");
	}
	while ($myUsers = pg_fetch_array ($usrRslt)) {
		$users .= "<option value='$myUsers[username]'>$myUsers[username]</option>\n";
	}

	$users .= "</select>\n";

	$sqlsplash = "SELECT * FROM splash";
	$allowsplash = db_exec($sqlsplash) or die ("Unable To get Splash Screen");
	if (pg_numrows($allowsplash) < 1){
		$splashmess = "";
	}else {
		$splashmess = "";
		while($splashdata = pg_fetch_array($allowsplash)){
			$splashmess .= "$splashdata[message]";
		}
	}


$OUTPUT =
"<h3>Login screen</h3>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post name=log>
<input type=hidden name=login value=1>
<tr>
	<td valign='top'>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr><th colspan=2>Please login</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>User name</td><td>$users</td></tr>
		<tr bgcolor='".TMPL_tblDataColor2."'><td>Password</td><td><input type=password size=20 name=login_pass></td></tr>
		<tr><td colspan=2 align=right><input type=submit value='Log in &raquo;'></td></tr>
		</table>
	</td>
	<td width='30'>
		<br>
	</td>
	<td>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<tr>
			<th>Splash Message</th>
		</tr>
		<tr bgcolor='".TMPL_tblDataColor2."'>
			<td><pre>$splashmess</pre></td>
		</tr>
		</table>
	</td>
</tr>
</form>
</table>";

	require ("template.php");
}

# always with md5 password
function checkLogin ($username, $password)
{
	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($username, "string", 1, 20, "Invalid user name.");
	$v->isOk ($password, "string", 32, 32, "Invalid password.");

	# display errors, if any
	if ($v->isError ()) {
		$theseErrors = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$theseErrors .= "<li class=err>".$e["msg"];
		}
		$theseErrors .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		$OUTPUT = $theseErrors;
		require ("template.php");
	}

	# connect to db
	db_connect ();

	# select user info from db
	$sql = "SELECT userid, div,services_menu,abo FROM users WHERE username='$username' AND password='$password'";
	$usrRslt = db_exec ($sql) or die ("Unable to verify login information.");
	if (pg_numrows ($usrRslt) < 1) {
		$OUTPUT = "Invalid username / password combination.";
                require ("template.php");
	}
	$myUsr = pg_fetch_array ($usrRslt);

	# register session vars
	# session_name ("CUBIT_SESSION");
	# session_start ();
	global $HTTP_SESSION_VARS;
	$HTTP_SESSION_VARS["USER_NAME"] = $username;
	$HTTP_SESSION_VARS["USER_PASS"] = $password;
	$HTTP_SESSION_VARS["USER_ID"] = $myUsr["userid"];
	$HTTP_SESSION_VARS["USER_DIV"] = $myUsr["div"];
	$HTTP_SESSION_VARS["ABO"]= $myUsr["abo"];

	// set the type of menu
	$HTTP_SESSION_VARS["SERVICES_MENU"] = $myUsr["services_menu"];

	# Select Stock
	db_connect();
	$sql = "SELECT branname FROM branches WHERE div = '$myUsr[div]'";
	$branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($branRslt) < 1){
		$bran['branname'] = "";
	}else{
		$bran = pg_fetch_array($branRslt);
	}
	$HTTP_SESSION_VARS["BRAN_NAME"] = $bran['branname'];
}

// checks the preferences/permissions of the users
function checkPreferences() {
	global $HTTP_SESSION_VARS;
	global $user_admin;
	global $dept_count;
	global $user_dept;
	global $services_menu_left, $mail_sender;

	$user_name=$HTTP_SESSION_VARS['USER_NAME'];

	// where should the menu be showed
	if ( $HTTP_SESSION_VARS["SERVICES_MENU"] == 'L' )
		$services_menu_left = true;
	else
		$services_menu_left = false;

	// check which mail client the use will use to send mail
	$mail_sender = "mailto:";

	// check the permissions
	$rslt=db_exec("SELECT deptid,dept FROM depts WHERE deptid<=14");
	$dept_count=0;
	while ($dep=pg_fetch_array($rslt)) {
		$dep["dept"]=strtolower($dep["dept"]);

		$rslt2=db_exec("SELECT username FROM deptscripts,userscripts
								WHERE deptscripts.script=userscripts.script
									AND deptscripts.dept=$dep[deptid]
									AND username='".$user_name."' ");

		// if any results where found, it means user has privileges in this table
		if (pg_num_rows($rslt2) > 0) {
			$dept_count++;
			$user_dept[ $dep["dept"] ]=1;
		} else {
			$user_dept[ $dep["dept"] ]=0;
		}
	}

	// marks if user is administrator
	$rslt=db_exec("SELECT admin FROM users WHERE username='$user_name' ");
	$user_admin=pg_result($rslt,0,0); // read the admin field and store it
}
?>
