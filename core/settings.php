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



/*# If this script is called by itself, abort
if (basename (getenv ("SCRIPT_NAME")) == "settings.php") {
	exit;
}

# get pglib
require ("libs/pgsql.lib.php");

// Set error reporting to E_ALL while debugging, E_NONE when live
error_reporting (E_ALL);

// seed random number generator & get md5 of random number
srand ((double) microtime() * 1000000);
define ("RAND_NO", rand());
define ("RAND_MD5", md5 (RAND_NO));

// Taxes, etc
define ("TAX_VAT", 14);

# purchases exceeding this must be authorized first
define ("MAX_PCHS", 2500);

// Account type settings
define ("MIN_INC", "1");
define ("MAX_INC", "199");
define ("MIN_EXP", "200");
define ("MAX_EXP", "499");
define ("MIN_BAL", "500");
define ("MAX_BAL", "999");

// Database settings
define ("DB_USER", "postgres");
define ("DB_PASS", "i56kfm");
define ("DB_DB", "cubit");

// Company details
db_connect();
$sql ="SELECT * FROM compinfo";
$Rslt = db_exec($sql);
$com = pg_fetch_array($Rslt);

define ("COMP_NAME", $com['compname']);
define ("COMP_SLOGAN", $com['slogan']);
define ("COMP_LOGO", $com['logoimg']);
define ("COMP_ADDRESS", $com['addr1']."<br>".$com['addr2']."<br>".$com['addr3']."<br>".$com['addr4']);
define ("COMP_TEL", $com['tel']);
define ("COMP_FAX", $com['fax']);
define ("COMP_VATNO", "4820116814");

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
define ("TMPL_tblCellPadding", 2);
define ("TMPL_tblDataColor1", "#88BBFF");  // bgcolor for data cells
define ("TMPL_tblDataColor2", "#77AAEE");  // alternate bgcolor for data cells
define ("TMPL_tblHdngBg", "#114488");   // bgcolor for cell-headings
define ("TMPL_tblHdngColor", "#FFFFFF");   // bgcolor for cell-headings

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

# login logic
if (isset ($HTTP_POST_VARS["login_user"]) && isset ($HTTP_POST_VARS["login_pass"]) && isset ($HTTP_POST_VARS["login"])) {
	checkLogin ($HTTP_POST_VARS["login_user"], md5 ($HTTP_POST_VARS["login_pass"]));
} elseif (empty ($HTTP_SESSION_VARS["USER_NAME"]) || empty ($HTTP_SESSION_VARS["USER_ID"])) {
	login ();
} else {
	# define constants (for inside scripts)
	define ("USER_NAME", $HTTP_SESSION_VARS["USER_NAME"]);
	define ("USER_ID", $HTTP_SESSION_VARS["USER_ID"]);
	define ("USER_DIV", $HTTP_SESSION_VARS["USER_DIV"]);
}

# connect to db
db_connect ();

if(!($HTTP_SESSION_VARS["USER_NAME"] == 'Root' || $HTTP_SESSION_VARS["USER_NAME"] == 'Admin' || basename (getenv ("SCRIPT_NAME")) == "acc-new.php" || basename (getenv ("SCRIPT_NAME")) == "main.php" || basename (getenv ("SCRIPT_NAME")) == "index.php" ||basename (getenv ("SCRIPT_NAME")) == "bottom_menu.php")){
        # check permission
        $chk = "SELECT * FROM userscripts WHERE username = '$HTTP_SESSION_VARS[USER_NAME]' AND script ='".rtrim(basename (getenv ("SCRIPT_NAME")))."'";
        $chkRslt = db_exec($chk) or errDie("Unable to check user access permissions",SELF);
        if(pg_numrows($chkRslt) < 1){
                $OUTPUT = "<li class=err>You <b>don't have sufficient permissions</b> to use this command.$HTTP_SESSION_VARS[USER_NAME] => ".getenv ("SCRIPT_NAME");
                require("template.php");
        }
}

# login func
function login ()
{
	# connect to db
	db_connect ();

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

	$OUTPUT =
"
<h3>Login screen</h3>
<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
<form action='".SELF."' method=post>
<input type=hidden name=login value=1>
<tr><th colspan=2>Please login</th></tr>
<tr bgcolor='".TMPL_tblDataColor1."'><td>User name</td><td>$users</td></tr>
<tr bgcolor='".TMPL_tblDataColor2."'><td>Password</td><td><input type=password size=20 name=login_pass></td></tr>
<tr><td colspan=2 align=right><input type=submit value='Log in &raquo;'></td></tr>
</form>
</table>
";
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
	$sql = "SELECT userid FROM users WHERE username='$username' AND password='$password'";
	$usrRslt = db_exec ($sql) or die ("Unable to verify login information.");
	if (pg_numrows ($usrRslt) < 1) {
		$OUTPUT = "Invalid username / password combination.";
		require ("template.php");
	}
	$myUsr = pg_fetch_array ($usrRslt);

	# register session vars
	session_name ("CUBIT_SESSION");
	# session_start ();
	global $HTTP_SESSION_VARS;
	$HTTP_SESSION_VARS["USER_NAME"] = $username;
	$HTTP_SESSION_VARS["USER_PASS"] = $password;
	$HTTP_SESSION_VARS["USER_ID"] = $myUsr["userid"];
	$HTTP_SESSION_VARS["USER_DIV"] = 2;
}*/

require ("../settings.php");
?>
