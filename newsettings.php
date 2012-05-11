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
if (basename (getenv ("SCRIPT_NAME")) == "newsettings.php") {
	exit;
}

if (defined("CONSOLE")) {
	$argv = $_SERVER["argv"];
	//ob_start();
}

define("RAINING_OUTSIDE", true);

# Get the environment on which this script is run (linux/windows)
require("_platform.php");

/* session and initial database */
session_name ("CUBIT_SESSION");
session_start ();

$ALINK = pg_connect("user=".DB_USER." password=".DB_PASS." ".DB_HOST." dbname=cubit");
if(!defined("DB_CUBIT_MAIN")) define("DB_CUBIT_MAIN", $ALINK);

/* document root */
// first wait for cubit to be loaded at least once for the docroot to be set
if (($dr = locateRoot()) === false) {
	errDie("Error locating document root.");
}
define("DOCROOT", $dr);

# Get pglib
require_lib("r2s");
require_lib("ext");
require_lib("time");
require_lib("pgsql");
require_lib("error");
require_lib("db");
require_lib("file");
require_lib("query");

if (count($CUBIT_MODULES)) {
	foreach ($CUBIT_MODULES as $modname) {
		require_mlib($modname, true);
	}
}

// Set error reporting to E_ALL while debugging, E_NONE when live
error_reporting (E_ALL);

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
//define ("DB_USER", "postgres");
//define ("DB_PASS", "i56kfm");
//define ("DB_DB", "cubit");

// HTML & layout settings
define("TBL_BR", "<tr><td>&nbsp;</td></tr>");
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
define ("TMPL_tblDflts", "cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'");

define ("REQ", "<font class='required'>*</font>");

// Get the environment variables in securely
define ("REFERER", getenv ("HTTP_REFERER"));
define ("SELF", basename (getenv ("SCRIPT_NAME")));

// Email addies
define ("EMAIL_ADMIN", "root");

// Date
// session timeout (minutes)
define ("SESSION_TIMEOUT", "20");

# danger level for stock (percentage)
define ("DANGER_LVL", 5);

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

/**
 * checks if a companie's db exists
 *
 * @param string $code company code
 */
function exists_compdb($code) {
	global $ALINK;
	$c = @pg_connect("user=".DB_USER." password=".DB_PASS." ".DB_HOST." dbname=cubit_$code");

	if ($c) {
		pg_close($c);
		return true;
	} else {
		return false;
	}
}

/**
 * connects to custom db
 *
 * @param string $db
 * @return int
 */
function db_con ($db)
{
	return db_conn($db);
}

function closefirst() {
	global $ALINK;
	if ($ALINK && $ALINK != DB_CUBIT_MAIN) {
		pg_close($ALINK);
	}
}

// executes db command
function db_exec($sql) {
	global $ALINK;
	return pg_exec($ALINK, $sql);
}

// Function to connect to db server
function db_connect ()
{
	closefirst();
	global $ALINK;
	$ALINK = DB_CUBIT_MAIN;
	return $ALINK;
}

// Connect to a specific Database
function db_conn($db) {
	global $ALINK;

	closefirst();

	if ($db == "cubit") {
		$ALINK = DB_CUBIT_MAIN;
	} else {
		$ALINK = pg_connect("user=".DB_USER." password=".DB_PASS." ".DB_HOST." dbname=".$db) or errDie ("Unable to connect to database server.", SELF);
	}

	return $ALINK;
}

// Check if you can Connect to a specific Database
function db_check ($db) {
	return @pg_connect ("user=".DB_USER." password=".DB_PASS." ".DB_HOST." dbname=".$db);
}

function newlib_ex($db, $tab, $field, $filter) {
	db_con($db);
	$sql = "SELECT * FROM $tab WHERE lower($field) = lower('$filter')";
	$cRslt = db_exec($sql);
	if (pg_num_rows($cRslt) > 0) {
		return true;
	} else {
		return false;
	}
}

/**
 * reports ands logs error
 *
 * skip the log of there are filesystem problems.
 *
 * @param string $err
 * @param bool $skiplog
 */
function errDie($errstring, $skiplog = false) {
	$err = DATE_LOGGING." - ".SELF." - $errstring";

	if (pg_ErrorMessage()) {
		$err .= " - ". pg_ErrorMessage();
	}

	// log error to file
	die($errstring);
	if ($skiplog === false && $fd = cfs::fopen("error_log", 'a')) {
		if (cfs::fwrite($fd, "$err\n")) {
			$errlog_msg = "Error has been logged. Please notify the administrator.";
		} else {
			$errlog_msg = "Error writing to error log. Please notify the administrator.";
		}

		cfs::fclose($fd);
	} else {
		$errlog_msg = "Error opening error log. Please notify the administrator.";
	}

	$OUTPUT = "$errstring $errlog_msg";
	require ("newtemplate.php");
}

function ct($string)
{
	return $string;
}

/**
 * checks the db for the stored root docroot to cubit and returns the path
 *
 */
function locateRoot() {
	if (isset($_SESSION["code"])) {
		$ex = "_$_SESSION[code]";
	} else if (defined("CONSOLE")) {
		$ex = "_".CONSOLE;
	} else {
		$ex = "";
	}

	db_con("cubit");
	$sql = "SELECT value FROM globalset WHERE name='docroot$ex'";
	$rslt = db_exec($sql) or die("Error reading document root.");

	if (pg_num_rows($rslt) > 0) {
		/**
		 * document root of cubit (full path)
		 */
		db_conn("cubit");
		return pg_fetch_result($rslt, 0);
	} else if (defined("CONSOLE")) {
		/* loop until root is found */
		$CWD = CUBIT_WD;
		$CP = preg_replace("/[\\\\]/", "/", $CWD);

		while (!is_file("${CP}/_defineroot.php") && $CP != "/") {
			$CP = dirname($CP);
		}

		/* make sure the file really exists */
		if (!is_file("${CP}/_defineroot.php")) {
			return false;
		}

		/* save it */
		$sql = "INSERT INTO globalset (name, value) VALUES('docroot$ex', '$CP')";
		@db_exec($sql);

		return $CP;
	} else {
		$me = basename(getenv("SCRIPT_NAME"));

		if (is_file("_defineroot.php")) header("Location: _defineroot.php?p=$me");
		else if (is_file("../_defineroot.php")) header("Location: ../_defineroot.php?p=$me");
		else if (is_file("../../_defineroot.php")) header("Location: ../../_defineroot.php?p=$me");
		else if (is_file("../../../_defineroot.php")) header("Location: ../../../_defineroot.php?p=$me");
		exit;
	}

	die("Unable to locate document root.");
}

/**
 * includes a module's library automatically
 *
 * @param string $lib library to include
 * @param bool $safe dont panic if libary not found
 */
function require_mlib($lib, $safe = false) {
	$f = DOCROOT."/$lib/$lib.lib.php";

	if (is_file($f)) {
		include_once($f);
		return true;
	} else if (require_lib($lib, true)) {
		return true;
	} else if ($safe !== true) {
		errDie("Module \"$lib\" library not found.");
	}

	return false;
}

/**
 * includes a library automatically from within any directory
 *
 * @param string $lib library to include
 * @param bool $safe dont panic if libary not found
 */
function require_lib($lib, $safe = false) {
	$f = DOCROOT."/libs/$lib.lib.php";

	if (is_file($f)) {
		include_once($f);
		return true;
	} else if ($safe !== true) {
		errDie("Libary \"$lib\" not found.");
	}

	return false;
}

function bg_class($reset = false) {
          global $BGCOLOR_COUNTER;
          if ($reset) $BGCOLOR_COUNTER = 0;
          if ($BGCOLOR_COUNTER++ % 2) {
              return "bg-even";
            } else {
              return "bg-odd";
            }
}

?>
