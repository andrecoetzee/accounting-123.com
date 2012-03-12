<?
/**
 * Generally used functions/constants, login logic also
 * @package Cubit
 * @subpackage Settings
 */

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

if (!defined("SETTINGS_PHP")) {
	define("SETTINGS_PHP", true);
}

/*** NOT RECOMMENDED UNLESS YOU WILLING TO SPEND ALOT OF TIME FIXING HTML ***/
/*** IN MY PERSONAL OPINION LESS THAN 10% OF CUBIT IS XML COMPATIBLE, I   ***/
/*** MIGHT BE WRONG, IT MIGHT BE EVEN LESS                                ***/
/*** DEFINE THIS ONLY WHEN YOU HAVE TIME TO GO THROUGH YOUR CODE MAKING   ***/
/*** IT CORRECTLY FORMED. UNDEFINE IT AFTERWARDS. ONE DAY I SAY, ONE DAY! ***/
//define("CUBIT_XML", true);

require_once("_platform.php");

/**
 * cubit globals (non auto)
 *
 * ex. JS_XPOPUP, JS_AJAX for html output scripts
 * dont add them here, add them after defined using addglobals().
 *
 * @see addglobals()
 * @see scGLOB
 */
$CUBIT_GLOBALS = array();

/**
 * cubit globals eval code
 *
 * eval() this code to globalise all globals
 */
$scGLOB = "";

/**
 * cubit xml namespaces
 */
$XMLNS = array(
	"errornet" => "http://www.cubit.co.za/accounting/errornet"
);

/**
 * add output to the globals
 */
$OUTPUT = "";
addglobals("OUTPUT");

/**
 * onload
 */
$BODY_ONLOAD = array();
addglobals("BODY_ONLOAD");

/**
 * js
 */
$JS_EXTRA = array();
addglobals("JS_EXTRA");

/**
 * r2s name->id list
 */
if (!isset($_SESSION["R2S_NAMED"])) {
	$_SESSION["R2S_NAMED"] = array();
}
$R2S_NAMED = &$_SESSION["R2S_NAMED"];

/**
 * cubit email addresses
 */
define("ERRORNET_EMAIL", "support@cubit.co.za");

function flashRed($ar_vals, $msg="", $return_to_sender=array())
{
	extract ($_POST);

	if (!defined("JS_ONLOAD")) {
		define("JS_ONLOAD", "focusText()");
	}

	// Give each field a default value, in this case just blank
	$fields = array();
	foreach ($ar_vals as $varname=>$value) {
		$fields[$varname] = "";
	}

	foreach ($fields as $varname=>$value) {
		if (!isset($$varname)) {
			$$varname = $value;
		}
	}

	// Create hidden fields for each field
	$hidden = "";
	foreach ($ar_vals as $varname=>$value) {
		$hidden .= "<input type='hidden' name='$varname' value='".$$varname."'>";
	}

	foreach ($return_to_sender as $varname=>$value) {
		$hidden .= "<input type='hidden' name='$varname' value='$value'>";
	}

	foreach ($ar_vals as $varname=>$display) {
		// We already got this value, restart
		if (!empty($$varname)) {
			continue;
		}

		$OUTPUT = "
		<center>
		<form method='post' action='".SELF."' name='form1'>
		$hidden
		<table ".TMPL_tblDflts.">
			<tr>
				<td>$msg</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
					<li class='err' style='font-size: 48px; font-weight: bold;
						text-align: center; text-decoration: blink;
						list-style: none'>$display</li>
				</td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td>
					<input type='text' name='$varname' style='font-size: 48px;
						width: 100%; text-align: center' id='textbox'>
				</td>
			</tr>
			<tr>
				<td align='center'><input type='submit' value='Continue &raquo'></td>
		</table>
		</form>
		</center>
		<script type='text/javascript'>
			document.form1.textbox.focus();
		</script>";

		require ("../template.php");
	}
	
	$inputs = array();
	foreach ($ar_vals as $varname=>$value) {
		$inputs[$varname] = $$varname;
	}

	return $inputs;
}

// Set error reporting to E_ALL while debugging, E_NONE when live
if (!defined("_DEFINEROOT_PHP")) {
	session_name("CUBIT_SESSION");
	session_start();
}

#if noroute is set, setup some SESSION VARS manually
if (isset($_POST["noroute"]) AND (isset($_POST["login"]))){
	$_SESSION["code"] = $_POST["code"];
	$_SESSION["comp"] = $_POST["comp"];
}

// Database settings
// Define ("COMP_DB", "wool");
if(isset ($_SESSION["code"])){
	define ("COMP_DB", $_SESSION["code"]);
	define ("COMP_NNAME", $_SESSION["comp"]);
}else{
	if (is_file("./complogin.php")) header("Location: complogin.php");
	else if (is_file("../complogin.php")) header("Location: ../complogin.php");
	else if (is_file("../../complogin.php")) header("Location: ../../complogin.php");
	exit();
}

$link = @pg_connect("user=".DB_MUSER." password=".DB_MPASS." ".DB_HOST." dbname=cubit")
	or die ("Unable to find main database. Cubit cannot start.");
if(!defined("DB_CUBIT_MAIN")) define("DB_CUBIT_MAIN", $link);

$link = @pg_connect("user=".DB_USER." password=".DB_PASS." ".DB_HOST." dbname=cubit_".COMP_DB);
if(!defined("DB_CUBIT")) define("DB_CUBIT", $link);

if (!$link) {
	session_destroy();
	header("Location: ".relpath("complogin.php"));
}

global $ALINK;
$ALINK = DB_CUBIT;
$ADB = "cubit_".COMP_DB;
$SQL_EXEC = array();
$SQL_EXEC_NUM = -1;
$DEBUG_OPT = array();

define("DOCROOT", locateRoot());

require("locale-alt.php");

error_reporting('E_NONE');

require_lib("user");
require_lib("stock");
require_lib("r2s");
require_lib("pgsql");
require_lib("ext");
require_lib("db");
require_lib("query");
require_lib("file");
require_lib("form");
require_lib("time");
require_lib("error");
require_lib("xpopup");
require_lib("ajax");

if (count($CUBIT_MODULES)) {
	foreach ($CUBIT_MODULES as $modname) {
		require_mlib($modname, true);
	}
}

# environment
require("uselog.php");

if ( ! defined("USELOG_H") ) {
	print "<li class='err'>Error loading registration modules.</li>";
	exit;
}

/* check if this is an ajax query and set constant as needed */
if (isset($_POST["AJAX"]) || isset($_GET["AJAX"])) {
	if (isset($_POST["AJAX"])) unset($_POST["AJAX"]);
	if (isset($_GET["AJAX"])) unset($_GET["AJAX"]);
	define("AJAX", true);
} else {
	define("AJAX", false);
}

/* popup messages options */
define("MSGS_CHECKTIME", 15000);
addglobals("MSGS_NOALERT");
$MSGS_NOALERT = array(
	"view_req.php"
);

// Seed random number generator & get md5 of random number
srand ((double) microtime() * 1000000);
define ("RAND_NO", rand());
define ("RAND_MD5", md5 (RAND_NO));

// work days in month
define("WORK_DAYS_MONTH", "27.25");

// items to show in lists (with offsets/limits this is the limit)
// old  // define("SHOW_LIMIT", 250);
define("SHOW_LIMIT", 100);

// Account type settings
define ("MIN_INC", "1");
define ("MAX_INC", "1999");
define ("MIN_EXP", "2000");
define ("MAX_EXP", "4999");
define ("MIN_BAL", "5000");
define ("MAX_BAL", "9999");

// Get the environment variables in securely
define ("REFERER", getenv ("HTTP_REFERER"));
define ("SELF", basename (getenv ("SCRIPT_NAME")));

// Email addies
define ("EMAIL_ADMIN", "root");

// Session timeout (minutes)
define ("SESSION_TIMEOUT", "20");

# Danger level for stock (percentage)
define ("DANGER_LVL", 5);

//define ("DB_USER", "postgres");
//define ("DB_PASS", "i56kfm");
//define ("DB_DB", "cubit");

/* -------------------------Database Connecitons ------------------------- */

if (pg_num_rows(db_exec("SELECT * FROM core.active")) < 1
		&& SELF != "setup.php" && SELF != "index.xul.php") {
	redir("setup.php");
}

/* period-month map */
$PRDMON = array();
$MONPRD = array();

db_conn("core");
$sql = "SELECT * FROM prdmap";
$rslt = db_exec($sql) or errDie("Error reading period-month map.");
while ($row = pg_fetch_array($rslt)) {
	$PRDMON[$row["period"]] = $row["month"];
	$MONPRD[$row["month"]] = $row["period"];
}

// Some Settings
# Taxes, etc
# Define ("TAX_VAT", 14);
db_connect();
$sql ="SELECT * FROM settings WHERE constant = 'TAX_VAT'";
$setRslt = db_exec($sql);
$set = pg_fetch_array($setRslt);
if(pg_numrows($setRslt) < 1){
	define ("TAX_VAT", 14);
}else{
	define ("TAX_VAT", $set['value']);
}

define ("AT14","@ ".TAX_VAT."%");

# Define ("CUR", "R");
$sql ="SELECT * FROM settings WHERE constant = 'CURRENCY'";
$setRslt = db_exec($sql);
$set = pg_fetch_array($setRslt);
if(pg_numrows($setRslt) < 1){
	define ("CUR", "R");
}else{
	define ("CUR", $set['value']);
}

if(isSetting("CC_USE")){
	$set = getSetting("CC_USE");
	define ("CC_USE", $set);
}else{
	define ("CC_USE", "");
}

define("REQ", "<font class='required'>*</font>");
define("IMP", "<font class='required'>!</font>");

// HTML & layout settings

db_connect ();
$get_sets = "SELECT * FROM template_colors";
$run_sets = db_exec($get_sets) or errDie("Unable to get template color settings.");
if(pg_numrows($run_sets) > 0){
	while ($sarr = pg_fetch_array($run_sets)){
		if (!defined("$sarr[setting]"))
			define ("$sarr[setting]","$sarr[value]");
	}
}


//define("TMPL_bgColor", "#4477BB");
define("TMPL_title", "Cubit Accounting");
//define("TMPL_fntColor", "#FFFFFF");
//define("TMPL_fntColor2", "#000000");
//define("TMPL_lnkColor", "#0000DD");
//define("TMPL_lnkHvrColor", "#FF0000");
//define("TMPL_navLnkColor", "#CCCCCC");
//define("TMPL_navLnkHvrColor", "#FFFFFF");
//define("TMPL_fntSize", 10);
//define("TMPL_fntFamily", "arial");
//define("TMPL_h2FntSize", 14);
//define("TMPL_h2Color", "#FFFFFF");
//define("TMPL_h3FntSize", 12);
//define("TMPL_h3Color", "#FFFFFF");
//define("TMPL_h4FntSize", 10);
//define("TMPL_h4Color", "#FFFFFF");

// Table settings
define("BR", "<br />");
define("TBL_BR", "<tr><td>&nbsp;</td></tr>");
//define("TMPL_tblBrdrColor", "#FFFFFF");  // table border color
//define("TMPL_tblCellSpacing", 1);
//define("TMPL_tblCellPadding", 1);
//define("TMPL_tblDataColor1", "#88BBFF");  // bgcolor for data cells
//define("TMPL_tblDataColor2", "#77AAEE");  // alternate bgcolor for data cells
//define("TMPL_tblDataColorOver", "#FFFFFF");  // mouse over bgcolor for data cells
//define("TMPL_tblHdngBg", "#114488");   // bgcolor for cell-headings
//define("TMPL_tblHdngColor", "#FFFFFF");   // bgcolor for cell-headings
define("TMPL_tblDflts", "cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'");


// Calendar settings
/* hack to make the ajax request to date selection have the correct
	path to images if cubit is in a subdirectory of webroot */
global $GWPP;
if (!isset($_REQUEST["GWPP"])) {
	$GWPP = relpath("groupware", true);
} else {
	$GWPP = $_REQUEST["GWPP"];
}
define("TMPL_calDateSelButton", "style='border: 0px;'");

define("TMPL_calTimeStyle1","style='border-right: 2px solid #A1A5A9; border-top: 1px dashed #A1A5A9; background-image: url(${GWPP}/time_bg.gif);'");
define("TMPL_calTimeStyle2","style='border: 1px solid #A1A5A9; border-bottom: none; background-image: url(${GWPP}/time_bg.gif);' ");
define("TMPL_calTimeStyleHeader","style='font-size: 16px;' ");

define("TMPL_calEntryStyle1","style='border-top: 1px solid #A1A5A9; background: white; cursor: pointer;' ");
define("TMPL_calEntryStyle2","style='border-top: 1px dashed #CCCCCC; background: white; cursor: pointer;' ");
define("TMPL_calEntryStyleBody","style='cursor: pointer;' ");
define("TMPL_calEntryStyleTitle","style='border: 1pt solid black; cursor: pointer;' ");
define("TMPL_calEntryStyleEntire","style='border: 1pt solid black; cursor: pointer;' ");

define("TMPL_calNoticesStyle","style='font-size: 15px; color: #FFFFFF; font-weight: bold;' ");
define("TMPL_calNoticesLink_a","#e8e8e8");
define("TMPL_calNoticesLink_h","#FFFFFF");

define("TMPL_calTodayLinkStyle","style='font-size: 13px; border: 1px solid #A1A5A9; border-bottom: none; background-image: url(${GWPP}/side_bg.gif); cursor: pointer;' ");
define("TMPL_calSmallMonthTitleStyle","style='font-size: 12px; border: 1px solid #A1A5A9; border-bottom: none; background-image: url(${GWPP}/side_bg.gif); cursor: pointer;' ");
define("TMPL_calSmallMonthTitleStyleLeft","style='text-align: left; font-size: 12px; border: 1px solid #A1A5A9; border-bottom: none; border-right: none; height: 20px; width: 35px; background-image: url(${GWPP}/side_bg.gif); cursor: pointer;' ");
define("TMPL_calSmallMonthTitleStyleCenter","style='text-align: center; font-size: 12px; border: none; border-top: 1px solid #A1A5A9; height: 20px; background-image: url(${GWPP}/side_bg.gif); cursor: pointer;' ");
define("TMPL_calSmallMonthTitleStyleRight","style='text-align: right; font-size: 12px; border: 1px solid #A1A5A9; border-bottom: none; border-left: none; height: 20px; width: 35px; background-image: url(${GWPP}/side_bg.gif); cursor: pointer;' ");
//define ("TMPL_calSmallMonthTitleStyle","style='font-size: 12px; border: 1px solid #A1A5A9; border-bottom: none; background-image: url(side_bg.gif);' ");

define("TMPL_calLargeMonthTitleStyle","style='font-size: 12px; border: 1px solid #A1A5A9; border-bottom: none; background-image: url(${GWPP}/side_bg.gif);' ");
define("TMPL_calLargeMonthBodyStyle","style='font-size: 12px; border: 1px solid #A1A5A9; border-top: none; background: #FFFFFF;' ");
define("TMPL_calLargeMonthWeekNumberStyle","style='font-size: 9px; color: #666666;'");

define("TMPL_calLargeMonthOMLink_a","#A1A5A9");
define("TMPL_calLargeMonthOMLink_h","#A1A5A9");
define("TMPL_calLargeMonthCMLink_a","#0066FF");
define("TMPL_calLargeMonthCMLink_h","#000099");
define("TMPL_calLargeMonthCMLinkToday_a","#FFFFFF");
define("TMPL_calLargeMonthCMLinkToday_h","#000099");
define("TMPL_calLargeMonthCMLinkSelected_a","red");
define("TMPL_calLargeMonthCMLinkSelected_h","#000099");
define("TMPL_calLargeMonthCurrentDay","#8F8FFF");
define("TMPL_calLargeMonthCurrentWeek","#C0C0FF");
define("TMPL_calLargeMonthSelectedDay","#81e8b6");
define("TMPL_calLargeMonthSelectedWeek","#81e8b6");

define("TMPL_calSmallMonthBodyStyle","style='font-size: 12px; border: 1px solid #A1A5A9; border-top: none; background: #FFFFFF;' ");
define("TMPL_calSmallMonthWeekNumberStyle","style='font-size: 9px; color: #666666;'");

define("TMPL_calSmallMonthOMLink_a","#A1A5A9");
define("TMPL_calSmallMonthOMLink_h","#A1A5A9");
define("TMPL_calSmallMonthCMLink_a","#0066FF");
define("TMPL_calSmallMonthCMLink_h","#000099");
define("TMPL_calSmallMonthCMLinkToday_a","#FFFFFF");
define("TMPL_calSmallMonthCMLinkToday_h","#000099");
define("TMPL_calSmallMonthCMLinkSelected_a","red");
define("TMPL_calSmallMonthCMLinkSelected_h","#000099");

define("TMPL_calSmallMonthCurrentDay","#8F8FFF");
define("TMPL_calSmallMonthCurrentWeek","#C0C0FF");
define("TMPL_calSmallMonthSelectedDay","#81e8b6");
define("TMPL_calSmallMonthSelectedWeek","#81e8b6");

define("TMPL_calFillSaturday","#f7f7f7");
define("TMPL_calFillSunday","#eaeaea");

define("TMPL_calRefreshTime",60);

define("TMPL_calAppointmentStyle","style='font-size: 14px; color: #FFFFFF;'");

$login = true;

if ($login) {
	# login logic
	if (isset($_POST["login_user"]) && isset($_POST["login_pass"]) && isset($_POST["login"])) {
		if(isset($_POST["noroute"])){
			checkLogin($_POST["login_user"], $_POST["login_pass"], $_POST["div"], isset($_POST["noroute"]));
		} else {
			checkLogin($_POST["login_user"], md5 ($_POST["login_pass"]), $_POST["div"], isset($_POST["noroute"]));
		}
	} else if (isset ($_POST["div"]) && isset ($_POST["logindiv"])) {
		login($_POST["div"]);
	} else if (empty ($_SESSION["USER_NAME"]) || empty ($_SESSION["USER_ID"])) {
		if (isset($_COOKIES["cubitdiv"])) {
			login ($_COOKIES["cubitdiv"]);
		} else {
			login ("2");
		}
//		} else {
//			logindiv();
//		}
	} else {
		db_conn("cubit");
		$sql = "SELECT loginseq FROM users WHERE userid='$_SESSION[USER_ID]'";
		$rslt = db_exec($sql) or errDie("Error reading login sequence for user.");

		if (pg_num_rows($rslt) < 1) {
			errDie("Invalid user in session.");
		}

		# define constants (for inside scripts)
		define("USER_NAME", $_SESSION["USER_NAME"]);
		define("USER_ID", $_SESSION["USER_ID"]);
		define("USER_DIV", $_SESSION["USER_DIV"]);
		define("BRAN_NAME", $_SESSION["BRAN_NAME"]);
		define("USER_HELP", $_SESSION["USER_HELP"]);
		define("USER_TYPE", $_SESSION["USER_TYPE"]);
		define("LOGIN_SEQ", $_SESSION["LOGIN_SEQ"]);

		/* some constants for common db where expressions */
		define("W_DIV", "div='".USER_DIV."'");

		if (DEBUG < 1 && pg_fetch_result($rslt, 0, 0) != $_SESSION["LOGIN_SEQ"]) {
			if (is_readable("./logout.php")) $LOGOUT_SCRIPT = "logout.php";
			else if (is_readable("../logout.php")) $LOGOUT_SCRIPT = "../logout.php";
			else if (is_readable("../../logout.php")) $LOGOUT_SCRIPT = "../../logout.php";
			else if (is_readable("../../../logout.php")) $LOGOUT_SCRIPT = "../../../logout.php";
			$OUTPUT = "
			<html>
			<head>
			<script>
				parent.document.location.href='$LOGOUT_SCRIPT';
			</script>";
			require("template.php");
		}

		checkVatReminder();
	}

	if (defined("USER_NAME")) {
		db_conn("cubit");
		$sql = "SELECT locale_enable FROM users WHERE username='".USER_NAME."'";
		$rslt = db_exec($sql) or errDie("Unable to retrieve user language information from Cubit.");
		$locale_enable = pg_fetch_result($rslt, 0);
	} else {
		$locale_enable = "disabled";
	}

	if ($locale_enable != "disabled") {
		if (defined("USER_NAME")) {
			$sql = "SELECT locale FROM users WHERE username='".USER_NAME."'";
			$rslt = db_exec($sql) or errDie("Unable to retrieve user language information from Cubit");
			$locale = pg_fetch_result($rslt, 0);
		}

		// Retrieve the default locale
		if (empty($locale)) {
			db_conn("cubit");
			$sql = "SELECT value FROM settings WHERE constant='LOCALE_DEFAULT'";
			$rslt = db_exec($sql) or errDie("Unable to retrieve the default locale from Cubit.");
			$locale = pg_fetch_result($rslt, 0);
		}
	} else {
		$locale = "disabled";
	}
	define ("LOCALE", $locale);

	// Company details
	db_connect();
	$sql ="SELECT * FROM compinfo WHERE div = '".$_SESSION["USER_DIV"]."'";
	$Rslt = db_exec($sql);
	$com = pg_fetch_array($Rslt);

	define("COMP_NAME", $com['compname']);
	define("COMP_SLOGAN", $com['slogan']);
	define("COMP_LOGO", $com['logoimg']);
	define("COMP_LOGO2", $com['logoimg2']);
	define("COMP_ADDRESS", $com['addr1']."<br>".$com['addr2']."<br>".$com['addr3'].", ".$com['addr4']);
	define("COMP_PADDR", "$com[paddr1], $com[paddr2], $com[paddr3], $com[postcode]");
	define("COMP_PADDRR", "$com[paddr1]<br>$com[paddr2]<br>$com[paddr3]<br>$com[postcode]");
	define("COMP_TEL", $com['tel']);
	define("COMP_REGNO", $com['regnum']);
	define("COMP_PAYE", $com['paye']);
	define("COMP_FAX", $com['fax']);
	define("COMP_VATNO", $com['vatnum']);

	# connect to db
	db_connect();

	checkPreferences();
	//recordUsage();
	defineIncludePath();

	// array of scripts ALL users may accesss(services etc...)
	$global_scripts = array(
		"dateselect.php",
		"getimg.php",
		"getimg2.php",
		"main.php",
		"purchase-new-cash.php",
		"purch-recv-cash.php",
		"pos-rem.php",
		"index.php",
		"top_menu.php",
		"getimg.php",
		"todo.php",
		"diary-index.php",
		"diary-appointment.php",
		"new_con.php",
		"list_cons.php",
		"acctab-new.php",
		"view_con.php",
		"req_gen.php",
		"view_req.php",
		"toolbar.php",
		"messages.php",
		"tree.php",
		"msglist.php",
		"viewmessage.php",
		"getattachment.php",
		"accounts.php",
		"newmessage.php",
		"getimg.php",
		"getmessages.php",
		"doc-index.php",
		"supp-pdf-stmnt.php",
		"nons-invoice-pdf-reprint.php",
		"supp-pdf-stmnt-date.php",
		"cust-pdf-stmnt-date.php",
		"pos-quote-pdf-print.php",
		"nons-quote-pdf-print.php",
		"quote-pdf-print.php",
		"help_general.php",
		"ccpopup.php",
		"scpopup.php",
		"ncpopup.php",
		"index-reports.php",
		"index-settings.php",
		"conper-add.php",
		"conper-edit.php",
		"conper-rem.php",
		"getimg.php",
		"multi-acc-popup.php",
		"doc-type-view.php",
		"set-doc-year.php",
		"lnons-purch-ret.php",
		"pos-invoice-speed.php",
		"showlogo.php",
		"index.xul.php",
		"time.lib.php",
		"getfile.php",
		"gantt.inc.php",
		"people.inc.php",
		"project.inc.php",
		"task.inc.php",
		"team.inc.php",
		"parsexml.php",
		"replayobj.php",
		"checkmsgs.php"
	);

	if (empty($uselog["registered"]["str"]) && (SELF == "company-import.php" || SELF == "company-export.php")) {
		$OUTPUT = "<li class=err>You cannot use the company import/export features without registering Cubit.
			You can register Cubit by clicking <a href='register.php'>here</a>.";
		require("template.php");
	}

	if(!($user_admin || in_array(basename(getenv("SCRIPT_NAME")),$global_scripts) )){
		# Check permission
		db_conn("cubit");
		$chk = "SELECT * FROM userscripts WHERE username = '$_SESSION[USER_NAME]' AND script ='".basename (getenv ("SCRIPT_NAME"))."'";
		$chkRslt = db_exec($chk) or errDie("Unable to check user access permissions",SELF);
		if(pg_numrows($chkRslt) < 1){
			$OUTPUT = "<li class=err>You <b>don't have sufficient permissions</b> to use this command.".getenv ("SCRIPT_NAME")."<br>
			If you have been given permission to this function please email andre@cubit.co.za with the details of this problem";
			require("template.php");

			$script = basename (getenv("SCRIPT_NAME"));

			$sql = "SELECT * FROM perm WHERE script = '$script'";
			$rs = db_exec($sql);
			if(pg_numrows($rs) < 1){
				$sql = "INSERT INTO perm(script) VALUES('$script')";
				$rs = db_exec($sql);
				$sql = "INSERT INTO userscripts (username, script) VALUES ('$_SESSION[USER_NAME]', '$script')";
				//$rs = db_exec($sql);
			}
			$OUTPUT = "<li class=err>You <b>don't have sufficient permissions</b> to use this command.".getenv ("SCRIPT_NAME")."<br>
			If you have been given permission to this function please email andre@cubit.co.za with the details of this problem";
			require("template.php");

		}
	}
}

if (isSetting("BANK_DET")) {
	$bankid = getdSetting("BANK_DET");
	# Get bank account name
	db_connect();
	$sql = "SELECT * FROM bankacct WHERE bankid = '$bankid' AND div = '".USER_DIV."'";
	$bankRslt = db_exec($sql);
	$bank = pg_fetch_array($bankRslt);
	define("BNK_BANKNAME", $bank['bankname']);
	define("BNK_BRANCHNAME", $bank['branchname']);
	define("BNK_BRANCHCODE", $bank['branchcode']);
	define("BNK_ACCNAME", $bank['accname']);
	define("BNK_ACCNUM", $bank['accnum']);
	define("BNK_BANKDET", "Bank : $bank[bankname]<br>Branch : $bank[branchname] ($bank[branchcode])<br>Acc No. : $bank[accnum]");
} else {
	define("BNK_BANKNAME", "");
	define("BNK_BRANCHNAME", "");
	define("BNK_BRANCHCODE", "");
	define("BNK_ACCNAME", "");
	define("BNK_ACCNUM", "");
	define("BNK_BANKDET", "");
}

$sql = "SELECT * FROM core.active";
$rslt = db_exec($sql);
$act = pg_fetch_array($rslt);

db_conn('cubit');
$Sl="SELECT * FROM users WHERE userid='".USER_ID."'";
$Ri=db_exec($Sl);

if (pg_num_rows($Ri) > 0) {
	$usr = pg_fetch_array($Ri);
}

define("PRD_DB", date("n"));
define("PRD_NAME", date("F"));
define("PRD_STATE", $usr["state"]);

if (PRD_STATE == "py") {
	define("YR_NAME", $usr['prdname']);
	define("YR_DB", $usr['prddb']);
	define("PYR_DB", $act["yrdb"]);
	define("PYR_NAME", $act["yrname"]);
	define("CUR_YR_DB", $usr["cur_prd_db"]);
} else {
	define("YR_NAME", $act['yrname']);
	define("YR_DB", $act['yrdb']);
	if (YR_DB == "yr1") {
		define("PYR_DB", false);
	} else {
		define("PYR_DB", "yr".((int)substr($act["yrdb"], 2) - 1));
	}
	define("CUR_YR_DB", YR_DB);
}

/******************************/
/** initialize the framework **/
/******************************/
require_lib("framework");

global $FRAMEWORK;
$FRAMEWORK = new cFramework();

/******************************/

if ( defined("LOGIN_SUCCESSFUL") && ! defined("LOGIN_SUCCESSFUL_NOROUTE") ) {
	$OUTPUT = "
	<h3>Logging in...</h3>
	<h3>If you are not logged in automatically, click <a href='index.xul.php' target='_top'>here</a>.</h3>

	<script> top.location.href = 'index.xul.php';
	</script>";

	require("template.php");
}

// functions
// EDITORS LIKE KWRITE CAN'T READ ADVANCED ASCII COMBINATION
// REVISITION OF ABOVE: s/LIKE KWRITE/LIKE OLD KWRITE's/g
// READ THE FOLLOWING IN VIM IF YOU CAN'T MAKE IT OUT
/*******************************************************************************************
   FFFFFF   U    U    NN    N     CCCC    TTTTTTT   IIIIIII    OOOO    NN    N    SSSSS
   F        U    U    N N   N    C    C      T         I      O    O   N N   N    S
   FFFF     U    U    N  N  N    C           T         I      O    O   N  N  N      S
   F        U    U    N   N N    C    C      T         I      O    O   N   N N        S
   F         UUUU     N    NN     CCCC       T      IIIIIII    OOOO    N    NN    SSSSS
********************************************************************************************/

function getDTEpoch($datetime)
{
        $fb = explode(" ", $datetime);
        if (preg_match("/[^-]/", $fb[0]) && is_numeric($fb[0])) {
                return $fb[0];
        }

        // Split up date and time
        $dt = explode(" ", $datetime);

        $date = explode("-", $dt[0]);
        $time = explode(":", $dt[1]);

        if (count($date) < 3) {
                return 0;
        }

        $epoch = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
        return $epoch;
}

function getBarcode($barcode, $encoding="")
{
	$height = 35;
        if (empty($encoding)) {
		$encoding = "I25";
	}
	
	$scale = 2;
	if ($encoding == "code128")	$scale = 1;

        if (is_dir("barcode")) {
                $dir = "barcode";
        } elseif (is_dir("manufact/barcode")) {
                $dir = "barcode";
        }

        return " $dir/barcode.php?encode=$encoding&bdata=$barcode&height=$height&scale=$scale&bgcolor=%23FFFFFF&color=%23000000&file=&type=png&Genrate=Submit&";
}

/**
 * makes the document parse as xmler
 */
function i_am_the_true_xml() {
	$GLOBALS["_CUBIT_XML"] = true;
}

/**
 * require template.php
 */
function parse($O = false) {
	global $scGLOB;
	eval($scGLOB);
	if ($O !== false) {
		$OUTPUT = $O;
	}
	include(relpath("template.php"));
}

/**
 * adds a variable to the list of globals
 *
 * used in libs and then the eval code is executed to globalise them all
 * in selected function/place.
 *
 * @param string vname variable name
 */
function addglobals($vname) {
	global $CUBIT_GLOBALS, $scGLOB;

	$CUBIT_GLOBALS[] = $vname;
	$scGLOB .= "if (!isset(\$$vname)) global \$$vname;";
}

/**
 * makes a javascript function/code execute when the page has completed loading
 *
 * @param string $js javascript function/code
 * @param bool $nounique add even if it has been added before
 */
function addonload($js, $nounique = false) {
	global $BODY_ONLOAD;

	if ($nounique !== false) {
		$BODY_ONLOAD[] = $js;
	} else {
		$BODY_ONLOAD[md5($js)] = $js;
	}
}

/**
 * adds some javascript code/functions to template.php
 *
 * used by module include (like property.lib.php)
 *
 * @param string $js
 */
function addjs($js) {
	global $JS_EXTRA;

	$JS_EXTRA[md5($js)] = $js;
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
		locateRoot(true);
		errDie("Library \"$lib\" not found.");
	}

	return false;
}

/**
 * checks the db for the stored root docroot to cubit and returns the path
 *
 */
function locateRoot($force = false) {
	if (isset($_SESSION["code"])) {
		$ex = "_$_SESSION[code]";
	} else {
		$ex = "";
	}

	db_con("cubit");
	$sql = "SELECT value FROM globalset WHERE name='docroot$ex'";
	$rslt = db_exec($sql) or die("Error reading document root.");

	if (($force === false || isset($_REQUEST["defineroot_try"])) && pg_num_rows($rslt) > 0) {
		/**
		 * document root of cubit (full path)
		 */
		db_conn("cubit");
		return pg_fetch_result($rslt, 0);
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
 * sets the include paths
 *
 */
function defineIncludePath() {
}

/**
 * prints error message (default: Invalid use of module) and exists
 *
 * @param string $err error message to display
 */
function invalid_use($err = false) {
	if ($err === false) $err = "<li class='err'>Invalid use of module.</li>";
	$OUTPUT = $err;
	pglib_transaction("ROLLBACK");
	if (is_file("./template.php")) include("./template.php");
	if (is_file("../template.php")) include("../template.php");
	if (is_file("../../template.php")) include("../../template.php");
	if (is_file("../../../template.php")) include("../../../template.php");
	if (!defined("TEMPLATE_LOADED")) print $err;
	exit;
}

/**
 * Puts a page break (for printers) after a block of html
 *
 * @param string $data html to use
 * @return block of html with page break after
 */
function paged($data) {
	return "<div style='page-break-after: always'>$data</div>";
}

/**
 * creates a frmupdate passon
 *
 * @param string $types comma seperated list of field types
 * @param string $form form name
 * @param string $fields comma seperated list of fields to update
 * @param string $notes notes to be used by caller script
 */
function frmupdate_make($types, $form, $fields, $notes = "") {
	return "frmupdate=$types|$form|$fields&frmupdate_notes=$notes";
}

/**
 * creates passon fields for frmupdate
 *
 * will check if there are any form update commands in get/post
 * and pass them on with hidden form fields. can also be used to check
 * whether or not there is a frmupdate.
 *
 * form updates: <type>|<form name>|<field name>
 * types: list
 *
 * @param bool $get whether to pass as get
 * @return string
 */
function frmupdate_passon($get = false) {
	if (isset($_REQUEST["frmupdate"])) {
		if (!$get) {
			$out = "<input type='hidden' name='frmupdate' value='$_REQUEST[frmupdate]' />";
		} else {
			$out = "frmupdate=$_REQUEST[frmupdate]";
		}
	} else {
		return false;
	}

	if (isset($_REQUEST["frmupdate_notes"])) {
		if (!$get) {
			$out .= "<input type='hidden' name='frmupdate_notes' value='$_REQUEST[frmupdate_notes]' />";
		} else {
			$out .= "&frmupdate_notes=$_REQUEST[frmupdate_notes]";
		}
	}

	return $out;
}

/**
 * returns the notes for frmupdate
 */
function frmupdate_notes() {
	if (isset($_REQUEST["frmupdate_notes"])) {
		return $_REQUEST["frmupdate_notes"];
	} else {
		return false;
	}
}

/**
 * executes the form update
 *
 * will check if there are any form update commands in get/post and return
 * javascript that will execute the update with $x
 *
 * $x should be an array with an element for each field, each element being:
 * 	  for lists an array in form val=>text.
 *    for text fields, just a text value
 * $clr determines whether the field should be clear first (only for lists).
 *
 * possible form updates: <type>|<form name>|<field name>.
 * possible types: list, text
 *
 * @param array $x array with new values for form field
 * @param bool $clr should field be cleared first
 * @param bool $keepopen should we keep window open
 *
 * @return bool success
 */
function frmupdate_exec($xdata, $clr = false, $keepopen = false) {
	/* get target */
	if (isset($_REQUEST["frmupdate"])) {
		$v = $_REQUEST["frmupdate"];
	} else {
		return false;
	}

	/* split target */
	$a = explode("|", $v);

	/* insufficient information */
	if (count($a) < 3) {
		return false;
	}

	list($ftypes, $frmname, $fldnames) = $a;

	$ftypes = explode(",", $ftypes);
	$flds = explode(",", $fldnames);

	/* switch target field type */
	$OUT = "";
	foreach ($ftypes as $k => $ftype) {
		$fn = $flds[$k];
		$x = $xdata[$k];

		switch ($ftype) {
			case "list":
				$OUT .= "
				function additem(fld, val, txt) {
					var n = document.createElement('option');
					n.text = txt;
					n.value = val;

					try {
						fld.add(n, null); // non standard ie!!!
					} catch(ex) {
						fld.add(n); // ie only
					}
				}

				// only do it if the form field exists
				if (fld = window.opener.document.$frmname.$fn) {";

				if ($clr === true) {
					$OUT .= "
						while (fld.length) fld.remove(0);";
				}

				foreach ($x as $key => $value) {
					$OUT .= "
						additem(fld, '$key', '$value');";
				}

				$OUT .= "
				}";

				break;

			case "text":
				$OUT = "window.opener.document.$frmname.$fn.value = '$x';";
				break;

			default:
				return false;
		}
	}

	if ($keepopen === false) {
		$OUT .= "window.close();";
	}

	return "<script>$OUT</script>";
}

/**
 * transforms the data to be used inside an xml tag as data
 *
 * @param string $data
 * @return string
 */
function xmldata($data) {
	return "<![CDATA[$data]]>";
}

/**
 * sets a debug option
 *
 * @param string $optname
 * @param mixed $val
 */
function _DEBUG_SET($optname, $val) {
	global $DEBUG_OPT;
	$DEBUG_OPT[$optname] = $val;
}

/**
 * Used to print a debug string
 *
 * will only print if {@link DEBUG} is defined
 * @param string $str string to print
 * @param bool $brnl prints a break afterwards
 */
function _DEBUG($str, $brnl = false) {
	if ( defined("DEBUG") && DEBUG >= 1 ) {
		print "<br /><strong>Error str:</strong> ";
		print $str;
		if ($brnl != false) {
			print "<br>";
		}

		print "<strong>Function trace:</strong><br />";
		$a = debug_backtrace();
		$j = 0;
		for ($i = count($a) - 1; $i > 0; --$i, ++$j) {
			for ($k = 0; $k < $j; ++$k) {
				print "&nbsp;-&nbsp;";
			}
			print basename($a[$i]["file"])." (".$a[$i]["line"].") -> "
				.$a[$i]["function"]."(";

			if ($a[$i]["function"] == "cubit_run") {
				print "--CODE--";
			} else {
				if (count($a[$i]["args"]) > 0) {
					print "\"".implode("\", \"", $a[$i]["args"])."\"";
				}
			}

			print ");<br />";
		}
		print "<br />";
	}
}

/**
 * Used to debug sql queries.
 *
 * Will convert it so you can just copy and paste it into psql. It will put
 * the schema names and stuff in for you.
 * will only print if {@link DEBUG} is defined
 *
 * @param string $str query to print
 */
function _DEBUG_SQL($sql) {
	if ( defined("DEBUG") && DEBUG > 1 ) {
		global $ADB;
		print preg_replace("/(^INSERT INTO|^SELECT.*FROM|^DELETE FROM|^UPDATE) ([^ ]+)/", "\\1 $ADB.\\2", $sql) . "<br>";
	}
}

/**
 * include in your output inside the form tags to make onthespot be remembered
 *
 * @return string
 */
function onthespot_passon() {
	if (isset($_REQUEST["onthespot"])) {
		return "<input type='hidden' name='onthespot' value='$_REQUEST[onthespot]'>";
	} else {
		return false;
	}
}

/**
 * used to suppress certain output if the script is in onthespot mode.
 *
 * use this around strings you DONT want to display in onthespot mode. any string you
 * pass to this function will be returned back if not in "onthespot" and false will be
 * returned if in "onthespot" mode.
 *
 * @return string/bool
 */
function onthespot_out($o) {
	if (!isset($_REQUEST["onthespot"])) {
		return $o;
	} else {
		return false;
	}
}

/**
 * builds an onthespot instruction.
 *
 * @param $script script to call via ajax
 * @param $layer layer to update via ajax
 * @param $vars get variables
 * @return string
 */
function onthespot_encode($script, $layer, $vars) {
	$o = "$script|$layer|$vars";
	return str_replace("=", "#", base64_encode($o));
}

/**
 * call this when done with script action to make onthespot execute if in onthespot session
 *
 * @param $o optionally the onthespot variable
 */
function onthespot_declare() {
	if (isset($_REQUEST["onthespot"])) {
		define("ONTHESPOT", base64_decode(str_replace("#", "=", $_REQUEST["onthespot"])));
	}
}


/**
 * returns current financial year
 *
 * @return integer
 */
function getFinYear() {
	// Retrieve the current year from Cubit
	db_conn("core");
	$sql = "SELECT yrname FROM active";
	$rslt = db_exec($sql) or errDie("Unable to retrieve current year from Cubit.");
	$year_out = substr(pg_fetch_result($rslt, 0), 1);

	return (int)$year_out;
}

/**
 * returns active financial year
 *
 * @return int
 */
function getActiveFinYear() {
	return (int)substr(YR_NAME, 1);
}

/**
 * gets the real year of a month in the active financial year
 *
 * @param int $mon month for which you wish to find out the year
 * @return int
 */
function getYearOfFinMon($mon) {
	global $PRDMON, $MONPRD;

	$fyear = getFinYear();

	if ($PRDMON[1] == 1) {
		return $fyear;
	}

	if ($mon >= $PRDMON[1]) {
		--$fyear;
	}

	return (int)$fyear;
}

/**
 * gets the real year of a period in the active financial year
 *
 * @param int $mon month for which you wish to find out the year
 * @return int
 */
function getYearOfFinPrd($prd) {
	global $PRDMON, $MONPRD;

	$mon = $PRDMON[$prd];

	$fyear = getFinYear();

	if ($PRDMON[1] == 1) {
		return $fyear;
	}

	if ($mon >= $PRDMON[1]) {
		--$fyear;
	}

	return (int)$fyear;
}

/**
 * makes quick links together with the ql() function
 *
 * @see ql()
 * @param ... unlimited return values of ql()
 * @return string html with quick links table
 */
function mkQuickLinks() {
	$OUT = "
	<table ".TMPL_tblDflts.">
    <tr>
    	<th>Quick Links</th>
    </tr>";

	foreach (func_get_args() as $arg) {
		$disp = $arg[1];

		$OUT .= "
		<tr class='quicklinks'>
			<td nowrap><a ".($disp[0]?"target='_blank'":"")." href='$arg[0]'>$disp[1]</a></td>
		</tr>";
	}

	$OUT .= "
	<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $OUT;
}

/**
 * alias for m(), used so we can run sed for global quick link changes
 *
 * @see ql()
 * @param string $url quick link url
 * @param string $disp quick link display
 * @param bool $newwin open in new window (dflt: false)
 * @return array
 */
function ql($url, $disp, $newwin = false) {
	return m($url, array($newwin, $disp));
}

/**
 * @ignore
 */
function getkey($registered=false) {
	global $uselog;

	$KEYGEN_VERSION = 2;

	// 4byte dates, with 2bytes in between
	if ( $registered === true ) { // already registered, check with previous hash
		$prefix = "reg_";
	} else {
		$prefix = "";
	}

	$r = explode("|", $uselog["$prefix"."randhash"]["str"]);

	$parts = array(
		//"rfirstday"		=> bytedate($uselog["rfirstday"]["date"], $r[4].$r[0]),
		"version"		=> bytedata(uselog_version(), $r[4]),
		"totusers"		=> bytedata(uselog_countusers(), $r[1]),
		"totcomps"		=> bytedata(uselog_countcomps(), $r[3]),
		"firstday"		=> bytedate($uselog["$prefix"."firstday"]["date"], $r[0].$r[3]),
		//"lastday"		=> bytedate($uselog["$prefix"."lastday"]["date"], $r[1].$r[2]),
		"firsttrans"		=> bytedate($uselog["$prefix"."firsttrans"]["date"], $r[2].$r[4]),
		//"expired"		=> bytedate($uselog["$prefix"."expired"]["date"], $r[4].$r[1])
	);

	// mask this one
	$mask = "`fj#[+~A-=%;bo3up&2_]df;4f{ss}!q81<!-?1)";
	$i = 0;
	$ctr = 0;
	foreach ($parts as $k => $v) {
		for ($p = 0; $p < strlen($v); ++$p) {
			$c = $v[$p];
			$m = $mask[$i++];

			$parts[$k][$p] = $c ^ $m;
		}
		++$ctr;
	}

	$tb36 = array();
	$i = 1;
	$ctr = 0;
	foreach ($parts as $k => $v) {

		$tb36[$i] = ord($v[2])
					+ (ord($v[1]) * pow(2, 8))
					+ (ord($v[0]) * pow(2, 16));

		$tb36[$i] = base_convert($tb36[$i], 10, 36);
		$v = $tb36[$i];

		++$i;

		++$ctr;
	}

	/* build the key version and hash of key */
	$md5 = md5(implode("", $tb36));
	$kp1_a = $md5[0].$md5[1];
	$kp1_b = $md5[30].$md5[31];

	$kp1_m = base_convert($KEYGEN_VERSION, 10, 36);

	/* build the key array */
	$b36 = array(
		"$kp1_a$kp1_m$kp1_b",
		$tb36[1],
		$tb36[2],
		$tb36[3],
		$tb36[4],
		$tb36[5]
	);

	// convert nums back to char strings
	/*
	print "<hr>";
	foreach ($b36 as $k => $v) {
		print "$k: $v --- ";
		$v = base_convert($v, 36, 10);
		$parts[$k] = chr(($v / pow(2, 16)) % 256)
				. chr(($v / pow(2, 8)) % 256)
				. chr(($v / 1) % 256);
		print "$parts[$k]<br>";
	}

	// unmask this one
	$i = 0;
	foreach ($parts as $k => $v) {
		for ($p = 0; $p < strlen($v); ++$p) {
			 $c = $v[$p];
			 $m = $mask[$i++];

			 $parts[$k][$p] = $c ^ $m;
		}
		print "$k: $parts[$k]<br>";
	}
	print "<hr>";
	*/

	$client_key = implode(" - ", $b36);

	return strtolower($client_key);
}

/**
 * @ignore
 */
function checkkey($key, $registered=false) {
	$client_key = getkey($registered);

	//3.5
	if ( date("Y") > 2010 ) {
		if ( date("m") > 3 ) 
			return false;
	}

	//3.4
//	if ( date("Y") > 2009 ) {
//		if ( date("m") > 10 ) 
//			return false;
//	}

	//3.3
//	if ( date("Y") > 2009 ) {
//		if ( date("m") > 3 ) 
//			return false;
//	}

	//3.2
//	if ( date("Y") > 2008 ) {
//		if ( date("m") > 10 ) 
//			return false;
//	}

	//3.1
// 	if ( date("Y") > 2008 ) {
// 		if ( date("m") > 3 ) 
// 			return false;
// 	}

	// 2.9
//	if ( date("Y") > 2007 ) {
//		if ( date("m") > 3 ) 
//			return false;
//	}

	#orig, orig ...
//	if ( date("Y") > 2006 ){
//		if ( date("m") > 04 )
//			return false;
//	}

	$mask = "^at$6;38cm|d>lo,<<q0kjd';=1-&?req%*((#sa12";

	$kparts = explode("-", preg_replace("/[ ]*/", "", $client_key));

	//unset($kparts[0]);
	//unset($kparts[2]);
	$kparts[0] = "*tkl8";
	$kparts[2] = "j##bp";
	unset($kparts[3]);

	//print implode("::", $kparts)."<br />";

	$nk = ""; // new key
	$i = 0;
	foreach ($kparts as $in => $kv) {
		$kv = base_convert($kv, 36, 20);

		// extend it to a multiple of 9
		while (strlen($kv)%8) {
			$kv .= $mask[++$i];
		}

		// hash it with predefined string
		for ($x = 0; $x < strlen($kv); ++$x) {
			$kv[$x] = ord($kv[$x]) ^ ord($mask[$x]);
		}

		// shrink
		$nk_part = 0;
		for ($x = 0; $x < strlen($kv); $x+=2) {
			$nk_part += ((ord($kv[$x]) ^ ord($kv[$x+1])) << 2) * ord($kv[$x]);
			$nk_part *= ord($kv[$x+1]) >> 4;
		}

		$nk .= base_convert($nk_part, 10, 36);
	}

	$genkey = $nk;

	//print "-$key-$genkey-";

	//print "generated: $genkey<br />";

	if ( $key == $genkey ) {
		return true;
	}

	return false;
}

/**
 * encode data into 5 bytes
 *
 * $data array: [0] = description, [1] => data
 *
 * @param array $data
 * @param char $r
 * @return string
 */
function bytedata($data, $r) {
	$p = explode(":", $data[1]);

	switch ($data[0]) {
		case "number":
			$x = str_pad(base_convert($p[0], 10, 36), 2, "0", STR_PAD_LEFT);
			//print "$data[0] - $p[0] - $x<Br />";
			$r_b1 = ord($x[0]) ^ $r;
			$r_b2 = ord($x[1]) ^ $r;
			return chr($r).chr($r_b1).chr($r_b2);
			break;
		case "version":
			$r_b1 = ord(base_convert($p[0], 10, 36)) ^ $r;
			$r_b2 = ord($p[1]) ^ $r;
			return chr($r_b1).chr($r_b2).chr($r);
			break;
		default:
			errDie("Invalid data supplied to key authenticity module.");
	}
}

/**
 * compress dates into 2/3 bytes
 *
 * generates a two byte date from a Y-m-d date with an optional character in between.
 * Year is measured from 2000 (max 2128)
 * m = 4 bits
 * d = 5 bits
 * Y = 7 bits
 *
 * @param string $date date you wish to convert
 * @param char $r character to put in between
 * @return string
*/
function bytedate($date, $r = "") {
	if ( empty($date) ) $date = "2000-00-00";
	list($year, $month, $day) = explode("-", $date);
	$year -= 2000;
	$day <<= 7;
	$month <<= 12;

	$magic_xor = (ord("!") << 8) | ord(";");

	$twobytes = $year | $month | $day;

	$twobytes ^= $magic_xor;

	$b1 = ($twobytes >> 8) & 255;
	$b2 = $twobytes & 255;

	$r = $r[0];
	$r_b1 = $b1 ^ $r;
	$r_b2 = $b2 ^ $r;

	$ret = chr($r_b1).chr($r).chr($r_b2);

	$r = ord($ret[1]);
	$r_b1 = ord($ret[0]);
	$r_b2 = ord($ret[2]);

	return $ret;
	return chr($b1).chr($r[0]).chr($r[1]).chr($b2);
}

/**
 * executes sql query in currently selected schema
 *
 * @param string $query sql query
 * @return int postgresql result
 */
function db_exec($query, $nodebug = false) {
	global $ALINK, $SQL_EXEC, $SQL_EXEC_NUM;

	$rslt = pg_exec($ALINK, $query);
	$SQL_EXEC[++$SQL_EXEC_NUM] = $query;

//print "$query<br>";

	if (DEBUG > 0) {
		global $DEBUG_OPT;
		if (isset($DEBUG_OPT["printsql"]) && $DEBUG_OPT["printsql"]) {
			print "$query<br />";
		}
	}

	if (!$nodebug && !$rslt) {
		_DEBUG($query, true);
	}

	return $rslt;
}

/**
 * executes sql query in currently selected schema in safe way;
 *
 * this doesn't raise an error or kill a transaction when it fails
 *
 * @param string $query sql query
 * @return int postgresql result
 */
function db_exec_safe($sql) {
	disableErrorNet();

	pglib_transaction("SAVEPOINT dbexec_failsafe", true);

	$rslt = db_exec($sql, true);

	if (!$rslt) {
		pglib_transaction("ROLLBACK TO dbexec_failsafe", true);
	}

	pglib_transaction("RELEASE SAVEPOINT dbexec_failsafe", true);

	enableErrorNet();

	return $rslt;
}

/**
 * @ignore
 */
function open() {
        db_conn('cubit');
        $Sl="SELECT * FROM ms WHERE set='statement type' AND val='open'";
        $Ri=db_exec($Sl) or errDie("Unable to get statement type.");

	if(pg_num_rows($Ri)>0) {
                return true;
        } else {
                return false;
        }
}

/**
 * @ignore
 */
function newacc($topacc, $accnum, $accname, $acctype, $vat, $ttype = false) {
	global $catids;

	if ($ttype === false) {
		$ttype = "NULL";
	} else {
		$ttype = "'$ttype'";
	}

	pglib_transaction("BEGIN") or errDie ("Unable to start transaction");

		$sql = "
			INSERT INTO core.accounts (
				topacc, accnum, accname, acctype, 
				catid, vat, div, toptype
			) VALUES (
				'$topacc', '$accnum', '$accname', '$acctype', 
				'$catids[$acctype]', '$vat', '".USER_DIV."', $ttype
			)";
		$rslt = db_exec($sql) or errDie("Unable to add account: $topacc/$accnum $accname.");

		$accid = pglib_lastid("core.accounts", "accid");

		insert_trialbal($accid, $topacc, $accnum, $accname, $acctype, $vat, USER_DIV);

	pglib_transaction("COMMIT") or errDie ("Unable to complete transaction.");
	return $accid;

}

/**
 * calculates vat
 *
 * @param float $sub subtotal
 * @param string $chrgvat do vat calculation
 * @param string $exvat exempt from vat
 * @param float $traddisc trade discount percentage
 * @param float $VATP vat percentage
 * @return string
 */
function vatcalc($sub,$chrgvat,$exvat,$traddisc,$VATP) {

	if($exvat=="y"||$exvat=="yes"||$exvat=="Yes") {
		$taxex=$sub;
	} else{
		$taxex=0;
	}

	$delchrg=0;
// 	$VATP = TAX_VAT;

	if(($chrgvat == "exc") OR ($chrgvat == "no")){
		$taxex = sprint($taxex - ($taxex * $traddisc/100));
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc/100);
		$subtotal = sprint($subtotal - $traddiscmt);
		$VAT = sprint(($subtotal - $taxex) * $VATP/100);
		$SUBTOT = $sub;
		$TOTAL = sprint($subtotal + $VAT);
		$delexvat = sprint($delchrg);

	}elseif(($chrgvat == "inc") OR ($chrgvat == "yes")){
		$ot = $taxex;
		$taxex = sprint($taxex - ($taxex * $traddisc / 100));
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc/100);
		$subtotal = sprint($subtotal - $traddiscmt);
		$VAT = sprint(($subtotal - $taxex) * $VATP / (100 + $VATP));
		$SUBTOT = sprint($sub);
		$TOTAL = sprint($subtotal);
		$delexvat = sprint(($delchrg));
		$traddiscmt = sprint($traddiscmt);

	} else {
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc / 100);
		$subtotal = sprint($subtotal - $traddiscmt);
		$VAT = sprint(0);
		$SUBTOT = $sub;
		$TOTAL = $subtotal;
		$delexvat = sprint($delchrg);
	}

	return $VAT."|".$TOTAL."|".$SUBTOT;
}

/**
 * calculates vat, returns array(vat, total, subtotal)
 *
 * @param float $sub subtotal
 * @param string $chrgvat do vat calculation
 * @param string $exvat exempt from vat
 * @param float $traddisc trade discount percentage
 * @param float $VATP vat percentage
 * @param array
 */
function vatcalca($sub, $chrgvat, $exvat, $traddisc, $vatperc) {
	$a = explode("|", vatcalc($sub, $chrgvat, $exvat, $traddisc, $vatperc));
	return array(
		"vat" => $a[0],
		"total" => $a[1],
		"subtotal" => $a[2]
	);
}

/**
 * @ignore
 */
function pvatcalc($sub,$chrgvat,$exvat) {
	$traddisc = 0;
	if($exvat == "y" || $exvat == "yes" || $exvat == "Yes") {
		$taxex = $sub;
	} else{
		$taxex = 0;
	}

	$delchrg = 0;
	$VATP = TAX_VAT;

	if($chrgvat == "no"){
		$taxex = sprint($taxex - ($taxex * $traddisc / 100));
		$subtotal = sprint($sub + $delchrg);
		$traddiscmt = sprint($subtotal * $traddisc/100);
		$subtotal = sprint($subtotal - $traddiscmt);
		$VAT = sprint(($subtotal - $taxex) * $VATP / 100);
		$SUBTOT = $sub;
		$TOTAL = sprint($subtotal + $VAT);
		$delexvat = sprint($delchrg);

	}elseif($chrgvat == "yes"){
		$ot = $taxex;
		$taxex = sprint($taxex-($taxex*$traddisc/100));
		$subtotal = sprint($sub+$delchrg);
		$traddiscmt = sprint($subtotal * $traddisc/100);
		$subtotal = sprint($subtotal - $traddiscmt);
		$VAT = sprint(($subtotal - $taxex) * $VATP / (100 + $VATP));
		$SUBTOT = sprint($sub);
		$TOTAL = sprint($subtotal);
		$delexvat = sprint(($delchrg));
		$traddiscmt = sprint($traddiscmt);

	} else {
		$subtotal=sprint($sub+$delchrg);
		$traddiscmt=sprint($subtotal*$traddisc/100);
		$subtotal=sprint($subtotal-$traddiscmt);
		$VAT=sprint(0);
		$SUBTOT=$sub;
		$TOTAL=$subtotal;
		$delexvat=sprint($delchrg);
	}

	return $VAT."|".$TOTAL;
}

/**
 * @ignore
 */
function lock($id) {
	$id+=0;

	db_conn('cubit');

	$Sl="SELECT * FROM locks WHERE lockid='$id'";
	$Ri=db_exec($Sl);

	while(pg_num_rows($Ri)>0) {

		sleep(1);

		$Sl="SELECT * FROM locks WHERE lockid='$id'";
		$Ri=db_exec($Sl);
	}

	//sleep(3);

	$Sl="INSERT INTO locks(lockid) VALUES ('$id')";
	$Ri=db_exec($Sl);

	sleep(1);

	$Sl="SELECT * FROM locks WHERE lockid='$id'";
	$Ri=db_exec($Sl);

	if(pg_num_rows($Ri)!=1) {
		unlock($id);
		$OUTPUT = "Transaction could note be completed. Please try again.";
		require("template.php");
		//return false;
	}

	return true;
}

/**
 * @ignore
 */
function daccounts() {

	db_conn('core');

	$Sl="UPDATE trial_bal SET vat='f'";
	$Ri=db_exec($Sl);

	$Sl="SELECT accid,topacc FROM trial_bal";
	$Ri=db_exec($Sl);

	while($tb=pg_fetch_array($Ri)) {
		$Sl="SELECT * FROM trial_bal WHERE topacc='$tb[topacc]'";
		$Rt=db_exec($Sl);

		if(pg_num_rows($Rt)>1) {
			$Sl="UPDATE trial_bal SET vat='t' WHERE topacc='$tb[topacc]' AND accnum='000'";
			$Rs=db_exec($Sl);
		}
	}
}

/**
 * @ignore
 */
function unlock($id) {
	$id+=0;

	db_conn('cubit');

	$Sl="DELETE FROM locks WHERE lockid='$id'";
	$Ri=db_exec($Sl);

	return true;
}

/**
 * @ignore
 */
function ass($words) {
	if((USER_HELP) != "Yes") {
		return "";
	} else {
		return "Title = 'Tool Tip: $words'";
	}
}

/**
 * strips out form elements and bgcolors for use in printing/xls
 *
 * @param string $str html code block to clean up
 * @return string
 */
function clean_html($str) {
    $str = preg_replace("/".TMPL_tblDataColor1."/", "#ffffff", $str);
    $str = preg_replace("/".TMPL_tblDataColor2."/", "#ffffff", $str);
    $str = preg_replace("/<\/?form[^>]*>/", "", $str);
    $str = preg_replace("/<input[^>]*>/", "", $str);

    return $str;
}

/**
 * quick fix against sql injection
 *
 * strips out range of unwanted characters and returns cleaned form. used
 * to quickly clean up string for sql-injection protection
 *
 * @param string $value
 * @return string
 */
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
	$value = str_replace("(","",$value);
	$value = str_replace(")","",$value);
	return $value;
}

/**
 * applies remval() to an array
 *
 * @param array $VAR
 * @return array
 */
function var_makesafe($VAR) {
        foreach ( $VAR as $key => $value ) {
                if ( is_array($value) ) {
                        $VAR[$key] = var_makesafe($value);
                } else {
                        $VAR[$key] = remval($value);
                }
        }

        return $VAR;
}

/**
 * rounds a number to two digits
 *
 * @param float $num
 * @return float
 */
function sprint($num){
	if ( $num < 0 ) $x = -100; else $x = 100;
	$num *= $x / 100;
	$num = explode(".", (($num * 100) + 0.5));
	return sprintf("%01.2f", $num[0] / $x);
}

/**
 * rounds a number to three digits
 *
 * @param float $num
 * @return float
 */
function sprint3($num){
	if ( $num < 0 ) $x = -1000; else $x = 1000;
	$num *= $x / 1000;
	$num = explode(".", (($num * 1000) + 0.5));
	return sprintf("%01.3f", $num[0] / $x);
}

/**
 * rounds a number to seven digits
 *
 * @param float $num
 * @return float
 */
function sprint7($num){
	if ( $num < 0 ) $x = -10000000; else $x = 10000000;
	$num *= $x / 1000;
	$num = explode(".", (($num * 1000) + 0.5));
	return sprintf("%01.7f", $num[0] / $x);
}


/**
 * performs a sprint() on a variable passed by reference
 *
 * @param float $num
 * @return float
 */
function vsprint(&$num){
	return $num = sprint($num);
}

/**
 * returns the supplied float in a money format
 *
 * @param float $num
 * @return string
 */
function money($num) {
	if (defined("MONEY_NUMERIC")) {
		return sprint($num);
	} else {
		return number_format(sprint($num), 2, ".", ",");
	}
}

/**
 * returns supplied float in money format if amount != 0.00
 *
 * used in (f)inancial (s)tatements (fs in fsmoney).
 *
 * @param float $num
 * @return string
 */
function fsmoney($num) {
	if ($num == 0) {
		return "";
	} else {
		return money($num);
	}
}

/**
 * performs a money() on a variable passed by reference
 *
 * @param float $num
 * @return float
 */
function vmoney(&$num) {
	return $num = money($num);
}

/* NOT USED
function sprint4($num){
	if ( $num < 0 ) $x = -1; else $x = 1;
	$num *= $x;
	$num = chop(($num * 100) + 0.5) / 100;
	return sprintf("%01.2f", $num * $x);
}

function sprint3($num){
	$num = chop(($num * 100) + 0.5) / 100;
	return sprintf("%01.2f", $num);
}

function sprint2($value){
	$value += 0;
	$value = round(round($value * 1000)/1000, 2);
	$value = sprintf("%01.2f", ($value));
	return $value;
}

function sprint1 ($value){
	$value += 0;
	$value = round( round($value, 13), 2);
	$value = sprintf("%01.2f", ($value));
	return $value;
}*/

/**
 * selects cubit schema
 *
 * @ignore
 * @return int dblink
 */
function db_connect ()
{
	global $ALINK, $ADB;
	$ALINK = DB_CUBIT;
	$ADB = "cubit";
	db_conn($ADB);
	return $ALINK;
}

/**
 * selects core schema
 *
 * @ignore
 * @return int dblink
 */
function core_connect ()
{
	global $ALINK;
	$ALINK = DB_CUBIT;
	db_conn("core");
	return $ALINK;
}

/**
 * selects custom schema
 *
 * @param string $db schema to select
 * @return int dblink
 */
function db_conn ($db)
{
	global $ALINK, $ADB;

	$ALINK = DB_CUBIT;
	$ADB = $db;
	if ((strlen($db) == 2) AND (substr($db,0,1) == "0")){
		$db = substr($db,1);
	}
	db_exec("SET search_path='$db'");

	return $ALINK;
}

/**
 * @ignore
 */
function db_ConnComp($db, $code)
{
	global $ALINK;
	if ((strlen($db) == 2) AND (substr($db,0,1) == "0")){
		$db = substr($db,1);
	}
	$ALINK = pg_connect("user=".DB_USER." password=".DB_PASS." ".DB_HOST." dbname=cubit_".$code) or errDie ("Unable to connect to database server.", SELF);
	return $ALINK;
	db_exec("SET search_path='$db'");
	return $ALINK;
}

/**
 * @ignore
 */
function db_conn_maint($db)
{
	db_con($db);
}

/**
 * connects to custom db
 *
 * @param string $db
 * @return int
 */
function db_con ($db)
{
	global $ALINK;
	if ( $db == "cubit" ) {
		$ADB = "-";
		$ALINK = DB_CUBIT_MAIN;
	} else {
		db_conn($db);
		$ALINK = DB_CUBIT;
	}
	return $ALINK;
}

/**
 * returns description of lead source by number
 *
 * @param int $srcnum lead source id
 * @return string
 */
function crm_get_leadsrc($srcnum) {
	$crm_lsrc = Array(
		0 => "None",
		1 => "Cold Call",
		2 => "Existing",
		3 => "Customer",
		4 => "Self Generated",
		5 => "Employee",
		6 => "Partner",
		7 => "Public Relations",
		8 => "Direct Mail",
		9 => "Conference",
		10 => "Trade Show",
		11 => "Web Site",
		12 => "Word of Mouth");

	if ( empty($crm_lsrc[$srcnum]) ) return $crm_lsrc;
	return $crm_lsrc[$srcnum];
}

/**
 * @ignore
 */
function check_sessionvars($_POST)
{

	global $_SESSION;

	if (!isset($_SESSION['USER_NAME'])){
		#we've lost the php login .... reload
		$_SESSION["code"] = $code;
		$_SESSION["comp"] = $comp;
		$_SESSION["USER_DIV"] = $USER_DIV;
		$_SESSION["tries"] = $tries;
		$_SESSION["USER_NAME"] = $USER_NAME;
		$_SESSION["USER_PASS"] = $USER_PASS;
		$_SESSION["USER_ID"] = $USER_DIV;
		$_SESSION["USER_HELP"] = $USER_HELP;
		$_SESSION["USER_TYPE"] = $USER_TYPE;
		$_SESSION["LOGIN_SEQ"] = $LOGIN_SEQ;
		$_SESSION["BRAN_NAME"] = $BRAN_NAME;
		$_SESSION["SERVICES_MENU"] = $SERVICES_MENU;
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
	pglib_transaction("ROLLBACK");

	if (!defined("USER_NAME")) {
		define("USER_NAME", "Not Logged In");
	}

	$err = DATE_LOGGING." - ".SELF." - ".USER_NAME." - $errstring";

	if (pg_ErrorMessage()) {
		$err .= " - ". pg_ErrorMessage();
	}

	// log error to file
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
	require(relpath("template.php"));
}

/**
 * @ignore
 */
function errDir($usrString){
	return errDie ($usrString);
}


/**
 * @ignore
 */
function div_isset($label, $filter){
	$label = strtoupper($label);
	$sql = "SELECT * FROM set WHERE label = '$label' AND value = '$filter' AND div = '".USER_DIV."'";
	db_connect() or errDie ("Unable to connect to database server.", SELF);
	$rslt = db_exec($sql) or errDie ("Unable to connect to database server.", SELF);
	if(pg_numrows($rslt) > 0){
		return true;
	}else{
		return false;
	}
}


/**
 * @ignore
 */
function divlastid($type, $div="")
{
	db_connect();

	//lock(2);

	$sql = "SELECT last_value FROM seq WHERE lower(type) = lower('".$type."')";
	$rslt = db_exec($sql) or errDie ("Unable to get sequence impl from Cubit.");
	if(pg_numrows($rslt) < 1){
		errDie("Sequence type: '$type' not found in Cubit");
	}
	$seq = pg_fetch_array($rslt);
	$nextval = ($seq['last_value'] + 1);

	$sql = "UPDATE seq SET last_value = '$nextval' WHERE lower(type) = lower('".$type."')";
	$rslt = db_exec($sql) or errDie ("Unable to update sequence impl in Cubit.");

	//unlock(2);

	return $nextval;
}

/**
 * @ignore
 */
function perm($script){
	db_connect();
	if((USER_NAME == "Root") OR (USER_NAME == "admin")) 
		return true;

	# Check permission
	$chk = "SELECT * FROM userscripts WHERE username = '".USER_NAME."' AND script ='$script'";
	$chkRslt = db_exec($chk) or errDie("Unable to check user access permissions",SELF);
	if(pg_numrows($chkRslt) > 0){
		return true;
	}else{
		return false;
	}
}

/**
 * used to return account hooks
 *
 * @param string $field column name to return
 * @param string $table db table
 * @param string $filtername column name to match with
 * @param string $filter value of column to match with
 * @param string $ex some extra sql
 * @return string value of $field
 */
function gethook($field, $table, $filtername, $filter,$ex="")
{
	if(($ex=="")&&($filter=="VAT")) {
		$filter="VATIN";
	} elseif(($ex!="")&&($filter=="VAT")) {
		$filter="VATOUT";
	}

	$sql = "SELECT ".$field." FROM ".$table." WHERE ".$filtername."='".$filter."' AND div = '".USER_DIV."'";
	core_connect() or errDie ("Unable to connect to database server.", SELF);
	$rslt = db_exec($sql) or errDie("Unable to get account link.",SELF);
	if(pg_numrows($rslt) < 1){
        $OUTPUT = "<li>ERROR : The Account link for <b>$filter</b> was not found, Please Set all account links on the Current module";
        require ("template.php");
        exit;
	}
	$hook = pg_fetch_array($rslt);
	return $hook['accnum'];
}

/**
 * does a customer ledger transaction
 *
 * @param int $cusnum customer id seq
 * @param int $contra contra account id
 * @param string $date date of transaction
 * @param id $ref transaction reference number
 * @param string $details transaction details
 * @param float $amount
 * @param char $type D/C (debit/credit customer account)
 */
function custledger($cusnum, $contra, $date, $ref, $details, $amount, $type) {
	if($amount == 0) return;

	$edate = date("Y-m-d");

	list($PRD_DB, $PRD_NAME) = getPRD($date);
	list($CUR_PRD_DB, $CUR_PRD_NAME) = getPRD($edate);

	db_conn($PRD_DB);
	if ($type == "d") {
		$damount = $amount;
		$camount = 0;

		$idRs = get($PRD_DB, "max(id)", "custledger", "cusnum", $cusnum);
		$id = pg_fetch_array($idRs);

		if($id['max'] <> 0){
			$balRs = get($PRD_DB, "cbalance,dbalance", "custledger", "id", $id['max']);
			$bal = pg_fetch_array($balRs);
			$bal['cbalance'] += 0;
			$bal['dbalance'] += $amount;
		} else {
			$balRs = get("cubit", "balance", "customers", "cusnum", $cusnum);
			$bal = pg_fetch_array($balRs);
			$bal['balance'] += 0;

			if($bal['balance'] > 0) {
				$bal['dbalance'] = $bal['balance'];
				$bal['cbalance'] = 0;
			} else {
				$bal['cbalance'] = ($bal['balance']*-1);
				$bal['dbalance'] = 0;
			}
		}

		# Total balance changes
		if($bal['dbalance'] >= $bal['cbalance']){
			$bal['dbalance'] = ($bal['dbalance'] - $bal['cbalance']);
			$bal['cbalance'] = 0;
		}elseif($bal['cbalance'] > $bal['dbalance']){
			$bal['cbalance'] = ($bal['cbalance'] - $bal['dbalance']);
			$bal['dbalance'] = 0;
		}

		db_conn($PRD_DB);
		$sql = "
			INSERT INTO custledger (
				cusnum, contra, edate, sdate, eref, 
				descript, debit, dbalance, cbalance, div
			) VALUES (
				'$cusnum', '$contra', '$date', '$edate', '$ref', 
				'$details', '$amount', $bal[dbalance], '$bal[cbalance]', '".USER_DIV."'
			)";

	} else {
		$damount=0;
		$camount=$amount;

		# Get balances
		$idRs = get($PRD_DB, "max(id)", "custledger", "cusnum", $cusnum);
		$id = pg_fetch_array($idRs);

		if($id['max'] <> 0){
			$balRs = get($PRD_DB, "cbalance,dbalance", "custledger", "id", $id['max']);
			$bal = pg_fetch_array($balRs);
			$bal['cbalance'] += 0;
			$bal['dbalance'] += 0;
			$bal['cbalance'] += $amount;
		}else{
			$balRs = get("cubit", "balance", "customers", "cusnum", $cusnum);
			$bal = pg_fetch_array($balRs);
			$bal['balance']+=0;

			if($bal['balance']>0) {
				$bal['dbalance'] = $bal['balance'];
				$bal['cbalance'] = 0;
			} else {
				$bal['cbalance'] = ($bal['balance']*-1);
				$bal['dbalance'] = 0;
			}
		}

		# Total balance changes
		if($bal['dbalance'] >= $bal['cbalance']){
			$bal['dbalance'] = ($bal['dbalance'] - $bal['cbalance']);
			$bal['cbalance'] = 0;
		}elseif($bal['cbalance'] > $bal['dbalance']){
			$bal['cbalance'] = ($bal['cbalance'] - $bal['dbalance']);
			$bal['dbalance'] = 0;
		}

		db_conn($PRD_DB);
		$sql = "INSERT INTO custledger(cusnum, contra, edate,sdate, eref, descript, credit, dbalance, cbalance, div)
				VALUES('$cusnum', '$contra', '$date','$edate', '$ref', '$details', '$amount', $bal[dbalance], '$bal[cbalance]', '".USER_DIV."')";
	}
	$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");

	db_conn('cubit');
	$Sl="SELECT * FROM users WHERE username='".USER_NAME."' AND div='".USER_DIV."' AND state='p'";
	$Ri=db_exec($Sl);

	$data=pg_fetch_array($Ri);

	/*db_conn('core');

	$Sl="SELECT * FROM active";
	$Ri=db_exec($Sl) or errDie("Unablet to get data.");

	$data=pg_fetch_array($Ri);*/

	global $PRDMON, $MONPRD;

	if (PRD_STATE == "py") {
		$audit_db = YR_NAME . "_audit";
		$actyear = PYR_NAME;
	} else {
		$audit_db = "audit";
		$actyear = YR_NAME;
	}

	if ($type == "d") {
		$cd_col = "debit";
		$cd_balcol = "dbalance";
	} else {
		$cd_col = "credit";
		$cd_balcol = "cbalance";
	}

	for ($iPRD = $MONPRD[$PRD_DB] + 1; $iPRD <= 12; ++$iPRD) {
		db_conn($PRDMON[$iPRD]);
		$Sl="UPDATE custledger SET dbalance=dbalance+'$damount',cbalance=cbalance+'$camount' WHERE cusnum='$cusnum'";
		$Ri=db_exec($Sl) or errDie("Unable to update custledger.");

		if (pg_affected_rows($Ri) <= 0) {
			$sql = "INSERT INTO custledger(cusnum, contra, edate,sdate, eref, descript,
						$cd_col, dbalance, cbalance, div)
					VALUES('$cusnum', '0', '$date','$edate', '$ref', 'Balance',
						'0', '$bal[dbalance]', '$bal[cbalance]', '".USER_DIV."')";
			db_exec($sql) or errDie("Unable to insert ledger entry to Cubit.");
		} else {
			$sql = "UPDATE custledger SET dbalance=dbalance-cbalance,cbalance=0
					WHERE cusnum='$cusnum' AND dbalance>=cbalance";
			db_exec($sql) or errDie("Unable to update debtors ledger (DGD).");

			$sql = "UPDATE custledger SET cbalance=cbalance-dbalance,dbalance=0
					WHERE cusnum='$cusnum' AND cbalance>dbalance";
			db_exec($sql) or errDie("Unable to update debtors ledger (CGD).");
		}





//ORIGINAL
//		db_conn($audit_db);
//		$iPRDNAME = getMonthName($PRDMON[$iPRD]);
//		$sql = "UPDATE ${iPRDNAME}_custledger SET $cd_balcol=$cd_balcol+'$amount'::numeric
//				WHERE cusnum='$cusnum'";
//		db_exec($sql) or errDie("Error updating debtors ledger (ADU).");
//
//		if (pg_affected_rows($Ri) <= 0) {
//			$sql = "INSERT INTO ${iPRDNAME}_custledger(cusnum, contra, sdate, edate, eref,
//						descript, debit, credit, dbalance, cbalance, div, actyear)
//					VALUES('$cusnum', '$contra','$edate', '$date', '$ref', '$details',
//						'0', '0', '$bal[dbalance]', '$bal[cbalance]', '".USER_DIV."', '$actyear')";
//			db_exec($sql) or errDie("Error updating debtors ledger (ADI).");
//		} else {
//			$sql = "UPDATE ${iPRDNAME}_custledger SET dbalance=dbalance-cbalance,cbalance=0
//					WHERE cusnum='$cusnum' AND dbalance>=cbalance";
//			db_exec($sql) or errDie("Unable to update debtors ledger (DGD).");
//
//			$sql = "UPDATE ${iPRDNAME}_custledger SET cbalance=cbalance-dbalance,dbalance=0
//					WHERE cusnum='$cusnum' AND cbalance>dbalance";
//			db_exec($sql) or errDie("Unable to update debtors ledger (CGD).");
//		}

	}


//FPIZED
	db_conn($audit_db);
	if ($type == "d") {
		$sql = "INSERT INTO ".$PRD_NAME."_custledger(cusnum, contra, sdate, edate, eref,
			descript, debit, dbalance, cbalance, div, actyear)
		VALUES('$cusnum', '$contra','$edate', '$date', '$ref', '$details',
			'$amount',  '$bal[dbalance]', '$bal[cbalance]', '".USER_DIV."', '$actyear')";
		$Ri=db_exec($sql) or errDie("Unable to insert aduit");
	} else {
		$sql = "INSERT INTO ".$PRD_NAME."_custledger(cusnum, contra, sdate, edate, eref,
			descript, credit, dbalance, cbalance, div, actyear)
		VALUES('$cusnum', '$contra','$edate', '$date', '$ref', '$details',
			'$amount',  '$bal[cbalance]', '$bal[dbalance]', '".USER_DIV."', '$actyear')";
		$Ri=db_exec($sql) or errDie("Unable to insert aduit");
	}



}

/**
 * does a supplier ledger transaction
 *
 * @param int $supid supplier id seq
 * @param int $contra contra account id
 * @param string $date date of transaction
 * @param id $ref transaction reference number
 * @param string $details transaction details
 * @param float $amount
 * @param char $type D/C (debit/credit supplier account)
 */
function suppledger($supid, $contra, $date, $ref, $details, $amount, $type)
{

	if(floatval($amount) == 0) return;

	$edate = date("Y-m-d");

	list($PRD_DB, $PRD_NAME) = getPRD($date);
	list($CUR_PRD_DB, $CUR_PRD_NAME) = getPRD($edate);

	db_conn($PRD_DB);
	if ($type == "d") {
		$damount = $amount;
		$camount = 0;

		# Get balances
		$idRs = get($PRD_DB, "max(id)", "suppledger", "supid", $supid);
		$id = pg_fetch_array($idRs);

		if($id['max'] <> 0){
			$balRs = get($PRD_DB, "cbalance,dbalance", "suppledger", "id", $id['max']);
			$bal = pg_fetch_array($balRs);
			$bal['cbalance'] += 0;
			$bal['dbalance'] += 0;
			$bal['dbalance'] += $amount;
		}else{
			$balRs = get("cubit", "balance", "suppliers", "supid", $supid);
			$bal = pg_fetch_array($balRs);
			$bal['balance'] += 0;

			if($bal['balance'] < 0) {
				$bal['dbalance'] = ($bal['balance']*-1);
				$bal['cbalance'] = 0;
			} else {
				$bal['cbalance'] = $bal['balance'];
				$bal['dbalance'] = 0;
			}
			//$bal['dbalance'] += $amount;
		}

		# Total balance changes
		if($bal['dbalance'] >= $bal['cbalance']){
			$bal['dbalance'] = ($bal['dbalance'] - $bal['cbalance']);
			$bal['cbalance'] = 0;
		}elseif($bal['cbalance'] > $bal['dbalance']){
			$bal['cbalance'] = ($bal['cbalance'] - $bal['dbalance']);
			$bal['dbalance'] = 0;
		}

		db_conn($PRD_DB);

		$sql = "
			INSERT INTO suppledger (
				supid, contra, edate, sdate, eref, 
				descript, debit, dbalance, cbalance, div
			) VALUES (
				'$supid', '$contra', '$date','$edate', '$ref', 
				'$details', '$amount', $bal[dbalance], '$bal[cbalance]', '".USER_DIV."'
			)";
	} else {
		$camount = $amount;
		$damount = 0;

		# Get balances
		$idRs = get($PRD_DB, "max(id)", "suppledger", "supid", $supid);
		$id = pg_fetch_array($idRs);

		if($id['max'] <> 0){
			$balRs = get($PRD_DB, "cbalance,dbalance", "suppledger", "id", $id['max']);
			$bal = pg_fetch_array($balRs);
			$bal['cbalance'] += 0;
			$bal['dbalance'] += 0;
			$bal['cbalance'] += $amount;
		}else{
			$balRs = get("cubit", "balance", "suppliers", "supid", $supid);
			$bal = pg_fetch_array($balRs);
			$bal['balance'] += 0;

			if($bal['balance'] < 0) {
				$bal['dbalance'] = ($bal['balance']*-1);
				$bal['cbalance'] = 0;
			} else {
				$bal['cbalance'] = $bal['balance'];
				$bal['dbalance'] = 0;
			}
		}

		# Total balance changes
		if($bal['dbalance'] >= $bal['cbalance']){
			$bal['dbalance'] = ($bal['dbalance'] - $bal['cbalance']);
			$bal['cbalance'] = 0;
		}elseif($bal['cbalance'] > $bal['dbalance']){
			$bal['cbalance'] = ($bal['cbalance'] - $bal['dbalance']);
			$bal['dbalance'] = 0;
		}

		db_conn($PRD_DB);

		$sql = "
			INSERT INTO suppledger (
				supid, contra, edate, sdate, eref, 
				descript, credit, dbalance, cbalance, div
			) VALUES (
				'$supid', '$contra', '$date', '$edate', '$ref', 
				'$details', '$amount', '$bal[dbalance]', '$bal[cbalance]', '".USER_DIV."'
			)";
	}
	$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");

	db_conn('cubit');
	$Sl = "SELECT * FROM users WHERE username='".USER_NAME."' AND div='".USER_DIV."' AND state='p'";
	$Ri = db_exec($Sl);

	$data = pg_fetch_array($Ri);

// 	db_conn('core');
//
// 	$Sl="SELECT * FROM active";
// 	$Ri=db_exec($Sl) or errDie("Unablet to get data.");
//
// 	$data=pg_fetch_array($Ri);

	global $PRDMON, $MONPRD;

	if (true || $MONPRD[$PRD_DB] < $MONPRD[$CUR_PRD_DB]) {
		for ($iPRD = $MONPRD[$PRD_DB] + 1; $iPRD <= 12; ++$iPRD) {
			db_conn($PRDMON[$iPRD]);
			$sql = "UPDATE suppledger
					SET dbalance=dbalance+'$damount',cbalance=cbalance+'$camount'
					WHERE supid='$supid'";
			$Ri = db_exec($sql) or errDie("Unable to update suppledeger.");

			if (pg_affected_rows($Ri) <= 0) {
				$sql = "INSERT INTO suppledger(supid, contra, edate,sdate, eref,
						descript, credit, dbalance, cbalance, div)
					VALUES('$supid', '0', '$date','$edate', '$ref',
						'$details', '0', $bal[dbalance], '$bal[cbalance]',
						'".USER_DIV."')";
				db_exec($sql) or errDie("Error updating supplier ledger (INS).");
			} else {
				$sql = "UPDATE suppledger SET dbalance=dbalance-cbalance,cbalance=0
						WHERE supid='$supid' AND dbalance>=cbalance";
				db_exec($sql) or errDie("Unable to update supp ledger.");

				$sql = "UPDATE suppledger SET cbalance=cbalance-dbalance,dbalance=0
						WHERE supid='$supid' AND cbalance>dbalance";
				db_exec($sql) or errDie("Unable to update supplier ledger.");
			}
		}
	}

	if (PRD_STATE == "py") {
		$audit_db = YR_NAME . "_audit";
		$actyear = PYR_NAME;
	} else {
		$audit_db = "audit";
		$actyear = YR_NAME;
	}

	db_conn($audit_db);
	if ($type == "d") {
		$sql = "
			INSERT INTO ".$PRD_NAME."_suppledger (
				supid, contra, edate, sdate, eref, descript,
				debit, dbalance, cbalance, div, actyear
			) VALUES (
				'$supid', '$contra', '$date', '$edate', '$ref', '$details',
				'$amount', '$bal[dbalance]', '$bal[cbalance]', '".USER_DIV."', '$actyear'
			)";
		$Ri = db_exec($sql) or errDie("Unable to insert aduit");
	} else {
		$sql = "
			INSERT INTO ".$PRD_NAME."_suppledger (
				supid, contra, edate, sdate, eref, descript,
				credit, dbalance, cbalance, div, actyear
			) VALUES (
				'$supid', '$contra', '$date', '$edate', '$ref', '$details',
				'$amount', '$bal[dbalance]', '$bal[cbalance]', '".USER_DIV."', '$actyear'
			)";
		$Ri = db_exec($sql) or errDie("Unable to insert aduit");
	}
}

/**
 * does an employee ledger transaction
 *
 * @param int $empid employee id seq
 * @param int $contra account id seq
 * @param string $date
 * @param int $ref reference number
 * @param string $details description of transaction
 * @param float $amount
 * @param char $type D/C - debit/credit employee ledger
 */
function empledger($empid, $contra, $date, $ref, $details, $amount, $type)
{
	$amount=abs($amount);
	if(floatval($amount) == 0) return;

	$edate=date("Y-m-d");

	list($PRD_DB, $PRD_NAME) = getPRD($date);
	list($CUR_PRD_DB, $CUR_PRD_NAME) = getPRD($edate);

	db_conn($PRD_DB);
	if($type == "d"){

		$damount=$amount;
		$camount=0;

		# Get balances
		$idRs = get($PRD_DB, "max(id)", "empledger", "empid", $empid);
		$id = pg_fetch_array($idRs);

		if($id['max'] <> 0){
			$balRs = get($PRD_DB, "cbalance,dbalance", "empledger", "id", $id['max']);
			$bal = pg_fetch_array($balRs);
			$bal['cbalance'] += 0;
			$bal['dbalance'] += $amount;
		}else{
			$balRs = get("cubit", "balance", "employees", "empnum", $empid);
			$bal = pg_fetch_array($balRs);
			$bal['balance']+=0;

			if($bal['balance']<0) {
				$bal['dbalance'] = ($bal['balance']*-1);
				$bal['cbalance'] = 0;
			} else {
				$bal['cbalance'] = $bal['balance'];
				$bal['dbalance'] = 0;
			}
			//$bal['dbalance'] += $amount;
		}

		# Total balance changes
		if($bal['dbalance'] >= $bal['cbalance']){
			$bal['dbalance'] = ($bal['dbalance'] - $bal['cbalance']);
			$bal['cbalance'] = 0;
		}elseif($bal['cbalance'] > $bal['dbalance']){
			$bal['cbalance'] = ($bal['cbalance'] - $bal['dbalance']);
			$bal['dbalance'] = 0;
		}

		db_conn($PRD_DB);

		$sql = "
			INSERT INTO empledger (
				empid, contra, edate,sdate, ref, des, debit, dbalance, cbalance, div
			) VALUES (
				'$empid', '$contra', '$date','$edate', '$ref', '$details', '$amount', $bal[dbalance], '$bal[cbalance]', '".USER_DIV."'
			)";

	}else{
		$camount=$amount;
		$damount=0;

		# Get balances
		$idRs = get($PRD_DB, "max(id)", "empledger", "empid", $empid);
		$id = pg_fetch_array($idRs);

		if($id['max'] <> 0){
			$balRs = get($PRD_DB, "cbalance,dbalance", "empledger", "id", $id['max']);
			$bal = pg_fetch_array($balRs);
			$bal['cbalance'] += 0;
			$bal['dbalance'] += 0;
			$bal['cbalance'] += $amount;
		}else{
			$balRs = get("cubit", "balance", "employees", "empnum", $empid);
			$bal = pg_fetch_array($balRs);
			$bal['balance']+=0;

			if($bal['balance']<0) {
				$bal['dbalance'] = ($bal['balance']*-1);
				$bal['cbalance'] = 0;
			} else {
				$bal['cbalance'] = $bal['balance'];
				$bal['dbalance'] = 0;
			}
		}

		# Total balance changes
		if($bal['dbalance'] > $bal['cbalance']){
			$bal['dbalance'] = ($bal['dbalance'] - $bal['cbalance']);
			$bal['cbalance'] = 0;
		}elseif($bal['cbalance'] > $bal['dbalance']){
			$bal['cbalance'] = ($bal['cbalance'] - $bal['dbalance']);
			$bal['dbalance'] = 0;
		}else{
			$bal['cbalance'] = 0;
			$bal['dbalance'] = 0;
		}

		db_conn($PRD_DB);
		$sql = "INSERT INTO empledger(empid, contra, edate,sdate, ref, des, credit, dbalance, cbalance, div)
		VALUES('$empid', '$contra', '$date','$edate', '$ref', '$details', '$amount', '$bal[dbalance]', '$bal[cbalance]', '".USER_DIV."')";
	}
	$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");

	db_conn('cubit');
	$Sl="SELECT * FROM users WHERE username='".USER_NAME."' AND div='".USER_DIV."' AND state='p'";
	$Ri=db_exec($Sl);

	//if(pg_num_rows($Ri)>0) {
	$data=pg_fetch_array($Ri);

// 	db_conn('core');
//
// 	$Sl="SELECT * FROM active";
// 	$Ri=db_exec($Sl) or errDie("Unablet to get data.");
//
// 	$data=pg_fetch_array($Ri);

	global $PRDMON, $MONPRD;

	if (true || $MONPRD[$PRD_DB] < $MONPRD[$CUR_PRD_DB]) {
		for ($iPRD = $MONPRD[$PRD_DB] + 1; $iPRD <= 12; ++$iPRD) {
			if ($type == "d") {
				$d_extra = $amount;
				$c_extra = 0;
			} else {
				$d_extra = 0;
				$c_extra = $amount;
			}
			$d_extra = $c_extra = 0;

			db_conn($PRDMON[$iPRD]);
			$sql = "UPDATE empledger SET dbalance=dbalance+'$damount',cbalance=cbalance+'$camount'
				WHERE empid='$empid'";
			$Ri = db_exec($sql) or errDie("Unable to update suppledeger.");

			if (pg_affected_rows($Ri) <= 0) {
				$sql = "INSERT INTO empledger(empid, contra, edate,sdate, ref, des,
						credit, dbalance, cbalance, div)
					VALUES('$empid', '0', '$date','$edate', '$ref', 'Balance',
						'0', '$bal[dbalance]', '$bal[cbalance]', '".USER_DIV."')";
				db_exec($sql) or errDie("Error updating employee ledger (PINS).");
			} else {
				//$Sl="SELECT * FROM empledger WHERE empid='$empid'";
				//$Rl=db_exec($Sl) or errDie("Unable to get ledger.");

				//while($cdata=pg_fetch_array($Rl)) {
				//	if($cdata['dbalance']>=$cdata['cbalance']) {
				$sql = "UPDATE empledger SET dbalance=dbalance-cbalance,cbalance=0
					WHERE empid='$empid' AND dbalance>=cbalance";
				db_exec($sql) or errDie("Unable to update emp ledger.");
				//	} elseif($cdata['cbalance']>$cdata['dbalance']) {
				$sql = "UPDATE empledger SET cbalance=cbalance-dbalance,dbalance=0
					WHERE empid='$empid' AND cbalance>dbalance";
				db_exec($sql) or errDie("Unable to update emp ledger.");
				//	}
				//}
			}
		}
	}

	if (PRD_STATE == "py") {
		$audit_db = YR_NAME . "_audit";
		$actyear = PYR_NAME;
	} else {
		$audit_db = "audit";
		$actyear = YR_NAME;
	}

	db_conn($audit_db);
	if ($type == "d") {
		$sql = "INSERT INTO ".$PRD_NAME."_empledger(empid, contra, edate,sdate, ref, descript, debit,
				dbalance, cbalance, div, actyear)
			VALUES('$empid', '$contra', '$date','$edate', '$ref', '$details', '$amount',
				'$bal[dbalance]', '$bal[cbalance]', '".USER_DIV."', '$actyear')";
	} else {
		$sql = "INSERT INTO ".$PRD_NAME."_empledger(empid, contra, edate,sdate, ref, descript, credit,
				dbalance, cbalance, div, actyear)
			VALUES('$empid', '$contra', '$date','$edate', '$ref', '$details', '$amount',
				'$bal[dbalance]', '$bal[cbalance]', '".USER_DIV."', '$actyear')";
	}
	db_exec($sql) or errDie("Unable to insert aduit");
}

// function suppledger($supid, $contra, $date, $ref, $details, $amount, $type)
// {
// 	if(floatval($amount) == 0) return;
//
// 	db_conn(PRD_DB);
// 	if($type == "d"){
// 		$sql = "INSERT INTO suppledger(supid, contra, edate, eref, descript, debit, div) VALUES('$supid', '$contra', '$date', '$ref', '$details', '$amount', '".USER_DIV."')";
// 	}else{
// 		$sql = "INSERT INTO suppledger(supid, contra, edate, eref, descript, credit, div) VALUES('$supid', '$contra', '$date', '$ref', '$details', '$amount', '".USER_DIV."')";
// 	}
// 	$rs = db_exec($sql) or errdie("Unable to insert ledger entry to the Database.");
// }

/**
 * @ignore
 */
function branname($div)
{
	db_connect() or errDie ("Unable to connect to database server : branname().", SELF);

	$sql = "SELECT branname FROM branches WHERE div = '$div'";
	$rslt = db_exec($sql) or errDie("Unable to get branch name.",SELF);
	if(pg_numrows($rslt) < 1){
        $OUTPUT = "<li>ERROR : Invalid Branch number.</li>";
        require ("template.php");
        exit;
	}
	$bran = pg_fetch_array($rslt);
	return $bran['branname'];
}

/**
 * @ignore
 */
function login ($div, $err="")
{
	# connect to db
	db_connect ();

/*	# Get branches
	$brans = "<select size=1 name=div>\n";
	$sql = "SELECT * FROM branches ORDER BY branname";
	$branRslt = db_exec ($sql) or die ("Unable to get branches from database.");
	if (pg_numrows ($branRslt) < 1) {
		$OUTPUT = "No branches found in database.";
		require ("template.php");
	}
	while ($bran = pg_fetch_array ($branRslt)) {
		if($bran['div'] == $div){
			$sel = "selected";
		}else{
			$sel = "";
		}
		$brans .= "<option value='$bran[div]' $sel>$bran[branname]</option>\n";
	}
	$brans .= "</select>\n";*/

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

//<tr bgcolor='".TMPL_tblDataColor2."'><td>Branch</td><td>$brans</td></tr>

	$OUTPUT = "
		<h3>Login screen</h3>
		$err
		<form action='".SELF."' method='POST' name='log'>
		<table ".TMPL_tblDflts.">
			<input type='hidden' name='logindiv' value='1'>
			<input type='hidden' name='div' value='2'>
		<tr>
			<td valign='top'>
				<table ".TMPL_tblDflts.">
					<tr>
						<th colspan='2'>Please login</th>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>User name</td>
						<td><input type='text' size='20' name='login_user'></td>
					</tr>
					<tr bgcolor='".bgcolorg()."'>
						<td>Password</td>
						<td><input type='password' size='20' name='login_pass'></td>
					</tr>
					<tr>
						<td colspan='2' align='right'>
							<input type='button' value='Select Company' onClick='document.location.href=\"".relpath("complogin.php")."\";'>
							<input type='submit' name='login' value='Log in &raquo;'>
						</td>
					</tr>
				</table>
			</td>
			<td width='30'>&nbsp;</td>
			<td>
				<table ".TMPL_tblDflts.">
					<tr>
						<th>Splash Message</th>
					</tr>
					<tr bgcolor='".TMPL_tblDataColor2."'>
						<td style='font-size: 12;'><pre>$splashmess</pre></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	</form>";
	require ("template.php");

}

/**
 * @ignore
 */
function logindiv ($err="")
{

	# connect to db
	db_connect ();

	$div = 3;

	# Get branches
	$brans = "<select size='1' name='div'>\n";
	$sql = "SELECT * FROM branches ORDER BY branname";
	$branRslt = db_exec ($sql) or die ("Unable to get branches from database.");
	if (pg_numrows ($branRslt) < 1) {
		$OUTPUT = "No branches found in database.";
		require ("template.php");
	}
	while ($bran = pg_fetch_array ($branRslt)) {
		if($bran['div'] == $div){
			$sel = "selected";
		}else{
			$sel = "";
		}
		$brans .= "<option value='$bran[div]' $sel>$bran[branname]</option>\n";
	}
	$brans .= "</select>\n";

	$OUTPUT = "
		<h3>Login screen</h3>
		<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='logindiv' value='1'>
			<tr>
				<td valign='top'>
					<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
						<tr>
							<th colspan='2'>Please Select Branch</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Branch</td>
							<td>$brans</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='2' align='right'><input type='submit' value='Continue &raquo;'></td>
						</tr>
					</table>
				</td>
			</tr>
		</form>
		</table>";
	require ("template.php");

}

/**
 * @ignore
 */
function logincomp ()
{

	# Connect to db
	db_conn("cubit");

	# Get Companies
	$comps = "<select size='1' name='code'>\n";
	$sql = "SELECT * FROM companies ORDER BY name ASC";
	$compRslt = db_exec ($sql) or die ("Unable to get companies from database.");
	if (pg_numrows ($compRslt) < 1) {
		$OUTPUT = "No Companies found in database.";
		require ("template.php");
	}
	while ($comp = pg_fetch_array ($compRslt)) {
		$comps .= "<option value='$comp[code]'>$comp[name]</option>\n";
	}
	$comps .= "</select>\n";

	$OUTPUT = "
		<h3>Login screen</h3>
		<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
		<form action='".SELF."' method='POST'>
			<input type='hidden' name='logincomp' value='1'>
			<tr>
				<td valign='top'>
					<table border='0' cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
						<tr>
							<th colspan='2'>Please Select Company</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Company Name</td>
							<td>$comps</td>
						</tr>
						<tr><td><br></td></tr>
						<tr>
							<td colspan='2' align='right'><input type='submit' value='Continue &raquo;'></td>
						</tr>
					</table>
				</td>
			</tr>
		</form>
		</table>";
	require ("template.php");

}

/**
 * @ignore
 */
function checkLogin ($username, $password, $sdiv, $noroute_set) {
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
			$theseErrors .= "<li class='err'>".$e["msg"]."</li>";;
		}
		return login($sdiv, $theseErrors);
	}

	# connect to db
	db_connect ();

	# select user info from db
	$sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
	$usrRslt = db_exec ($sql) or die ("Unable to verify login information.");

	db_conn("cubit");
	$sql = "SELECT * FROM login_retries";
	$retrRslt = db_exec($sql) or errDie("Unable to verify login retry information.");
	$retr = pg_fetch_array($retrRslt);

	global $_SESSION;

	// Block a user after a certain amount of failed logins ------------------

	// User has not been logged in yet, no branch
	$_SESSION["USER_DIV"] = NULL;

	if (!isset($_SESSION['tries'])) {
		$_SESSION['tries'] = 0;
	}

	db_conn("cubit");
	$sql = "SELECT blocktime FROM users WHERE username='$username'";
	$blockRslt = db_exec($sql) or errDie("Unable to verify login information");
	$block = pg_fetch_array($blockRslt);

	// The time in UNIX format
	$blocked_time = $block['blocktime'] + ($retr['minutes'] * 60);

 	// Check if the account is blocked
	if (!empty($block['blocktime']) && $blocked_time > time()) {
		// minute or minutes?
		$s = ($retr['minutes'] > 1) ? "s" : "";

		// Reset the tries, so they don't keep adding up
		$_SESSION["tries"] = 0;
		$err = "<li class='err'>Your account has been suspended for <b>$retr[minutes]</b> minute$s, due to too many failed login attempts.</li>";

		return login($sdiv, $err);

	} else if ($blocked_time < time()) {
		// Our user is once again free to roam... remove the ban.
		db_conn("cubit");
		$sql = "UPDATE users SET blocktime='' WHERE username='$username'";
		db_exec($sql) or errDie("Unable to verify login information");
	}

	// Block the account
	if (($_SESSION['tries']+1) == $retr['tries']) {
		db_conn('cubit');
		$sql = "UPDATE users SET blocktime='".time()."' WHERE username='$username'";
		db_exec($sql) or errDie("Unable to verify login information.");

		$_SESSION['tries'] = 0;
	}

	// Display an error
	$err = "";
	if (pg_numrows ($usrRslt) < 1 && empty($block["blocktime"])) {
		++$_SESSION["tries"];

		if (($_SESSION["tries"]+1) == $retr["tries"]) {
			$s = ($retr['minutes'] > 1) ? "s" : "";
			$err = "<li class='err'>Your account has been suspended for <b>$retr[minutes]</b> minute$s, due to too many failed login attempts.</li>";
			return login($sdiv, $err);
		} else {
			$err .= "<li class='err'>Invalid username / password combination (R).</li>";
			return login($sdiv, $err);
		}
	}
	$myUsr = pg_fetch_array ($usrRslt);

	// -----------------------------------------------------------------------

	if($myUsr["div"] != $sdiv){
		// Last minute temp fix
		return login($sdiv, "<li class='err'>Invalid username / password combination (B).</li>");
// 		return login($sdiv, "<li class='err'>User is not in selected Branch.</li>");
	}

	# register session vars
	# session_name ("CUBIT_SESSION");
	# session_start ();
	$next_loginseq = $myUsr["loginseq"] + 1;

	$_SESSION["USER_NAME"] = $username;
	$_SESSION["USER_PASS"] = $password;
	$_SESSION["USER_ID"] = $myUsr["userid"];
	$_SESSION["USER_DIV"] = $myUsr["div"];
	$_SESSION["USER_HELP"] = $myUsr["help"];
	$_SESSION["USER_TYPE"] = $myUsr["usertype"];
	$_SESSION["LOGIN_SEQ"] = $next_loginseq;
	$_SESSION["RET2STEP_SEQ"] = 1;

	# define constants (for inside scripts)
	define("USER_NAME", $_SESSION["USER_NAME"]);
	define("USER_ID", $_SESSION["USER_ID"]);
	define("USER_DIV", $_SESSION["USER_DIV"]);
	define("USER_HELP", $_SESSION["USER_HELP"]);
	define("USER_TYPE", $_SESSION["USER_TYPE"]);
	define("LOGIN_SEQ", $_SESSION["LOGIN_SEQ"]);

	setcookie("cubitdiv", $myUsr["div"], time() + 60000000);

	db_conn("cubit");
	$sql = "UPDATE users SET loginseq='$next_loginseq' WHERE userid='$myUsr[userid]'";
	$rslt = db_exec($sql) or errDie("Error updating login sequence for user.");

	# Get Branch Name
	db_connect();
	$sql = "SELECT branname FROM branches WHERE div = '$myUsr[div]'";
	$branRslt = db_exec($sql) or errDie("Unable to access databse.", SELF);
	if(pg_numrows($branRslt) < 1){
		$bran['branname'] = "";
	}else{
		$bran = pg_fetch_array($branRslt);
	}
	$_SESSION["BRAN_NAME"] = $bran['branname'];

	if ( ! defined("LOGIN_SUCCESSFUL") ) define("LOGIN_SUCCESSFUL", true);
	if ( $noroute_set ) define("LOGIN_SUCCESSFUL_NOROUTE", true);
}

/**
 * autoloaded function with every page load
 *
 * Done after every page load and only when login details are A-OK.
 * Used to check/load user specific and global settings
 *
 */
function checkPreferences() {
	global $_SESSION;
	global $user_admin;
	global $dept_count;
	global $user_dept;
	global $services_menu_left, $mail_sender;

	$user_name=$_SESSION['USER_NAME'];

        db_conn("cubit");
	$rslt = db_exec("SELECT services_menu FROM users WHERE userid = '$_SESSION[USER_ID]'");
	if ( pg_num_rows($rslt) > 0 ) {
		$_SESSION["SERVICES_MENU"] = pg_fetch_result($rslt, 0, 0);
	} else {
		$_SESSION["SERVICES_MENU"] = 'L';
	}

	// where should the menu be showed
	if ($_SESSION["SERVICES_MENU"] == 'T')
		$services_menu_left = false;
	else
		$services_menu_left = true;

	// check which mail client the use will use to send mail
	$mail_sender = "mailto:";

	// check the permissions
	$rslt = db_exec("SELECT deptid,dept FROM depts WHERE deptid<=14");
	$dept_count = 0;
	while ($dep = pg_fetch_array($rslt)) {
		$dep["dept"] = strtolower($dep["dept"]);

		$rslt2 = db_exec("SELECT username FROM deptscripts,userscripts
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
	$rslt = db_exec("SELECT admin FROM users WHERE username='$user_name' ");
	$user_admin = pg_result($rslt,0,0); // read the admin field and store it
}

/**
 * necesary to call after all account modifications
 *
 * call after all account alterations/creations. used to determine if account
 * may be used in journal transaction.
 *
 */
function block() {

	db_conn('core');

	$Sl = "UPDATE trial_bal SET vat='f'";
	$Ri = db_exec($Sl);

	$Sl = "SELECT DISTINCT accid,topacc FROM trial_bal WHERE accnum!='000'";
	$Ri = db_exec($Sl);

	while($ad = pg_fetch_array($Ri)) {
		$Sl = "UPDATE trial_bal SET vat='t' WHERE topacc='$ad[topacc]' AND accid!='$ad[accid]' AND accnum='000'";
		$Rl = db_exec($Sl);
	}

	$Sl = "UPDATE trial_bal SET vat='t' WHERE
		accname='Employees Control Account'
		OR accname='Customer Control Account'
		OR accname='Supplier Control Account'";
	$Rl = db_exec($Sl);
}

function insert_trialbal ($accid, $topacc, $accnum, $accname, $acctype, $vat, $div)
{

    global $PRDMON;
    for ($i = 0; $i <= 12; ++$i) {
		$mon = $PRDMON[$i];
		$sql = "
			INSERT INTO core.trial_bal (
				accid, topacc, accnum, accname, acctype, vat, div, month, period
			) VALUES (
				'$accid', '$topacc', '$accnum', '$accname', '$acctype', '$vat', '$div', '$mon', '$i'
			)";
		$rslt = db_exec($sql) or errDie("Error inserting '$accname' into trial balance.");
	}

}

/**
 * @ignore
 */
function checkVatReminder() {
	return;
	global $user_admin;
	global $_GET;
	extract($_GET);

	if ( isset($reminder) ) {
		$sql = "DELETE FROM vatreminder WHERE username='".USER_NAME."' AND opt<>'none'";
		$rslt = db_exec($sql) or errDie("Error deleting old reminders.");

		if ( $reminder == "dont" ) {
			db_conn("cubit");
			$sql = "INSERT INTO vatreminder (opt, username)
					VALUES('dont', '".USER_NAME."')";
			$rslt = db_exec($sql) or errDie("Unable to store Vat dont reminder option.");
		} else {
			db_conn("cubit");
			// if day, store day without time, so a "tommorow" will be triggered even in the early morning
			// if set late the previous night
			if ( $reminder == "day" ) {
				$sql = "
					INSERT INTO vatreminder (
						opt, val, username, reminded
					) VALUES (
						'$reminder', '$val', '".USER_NAME."', CURRENT_DATE::timestamp
					)";
			} else if ( $reminder == "min" || $reminder == "hour" ) {
				$sql = "
					INSERT INTO vatreminder (
						opt, val, username
					) VALUES (
						'$reminder', '$val', '".USER_NAME."'
					)";
			}
			$rslt = db_exec($sql) or errDie("Unable to store Vat tomorrow reminder option.");
		}

		return;
	}

	db_conn("cubit");
	$sql = "SELECT value, constant FROM settings
			WHERE constant='TAX_PRDCAT' OR constant='VAT_REG'";
	$rslt = db_exec($sql) or errDie("Error reading tax period category.");

	while ( $row = pg_fetch_array($rslt) ) {
		if ( $row["constant"] == "VAT_REG" && $row["value"] == "no" ) {
			return;
		} else if ( $row["constant"] == "TAX_PRDCAT" ) {
			if ( $row["value"] == "none" ) return;
			$prdcat = $row["value"];
		}
	}

	if ( ! $user_admin ) {
		db_conn("cubit");
		$sql = "SELECT * FROM userscripts WHERE username='".USER_NAME."' AND (script='trans-new.php' OR script='vat-report.php')";
		$rslt = db_exec($sql) or errDie("Error reading vat reminder settings.php");

		if ( pg_num_rows($rslt) < 1 ) {
			return;
		}
	}

	// check if there an expired re-reminder
	db_conn("cubit");
	$sql = "SELECT 1 FROM vatreminder WHERE username='".USER_NAME."' AND (
				(opt='min' AND EXTRACT('minutes' FROM AGE(CURRENT_TIMESTAMP, reminded)) >= val)
				OR  (opt='hour' AND EXTRACT('hour' FROM AGE(CURRENT_TIMESTAMP, reminded)) >= val)
				OR  (opt='day' AND EXTRACT('day' FROM AGE(CURRENT_TIMESTAMP, reminded)) >= val)
			)";
	$rslt = db_exec($sql) or errDie("Error checking vat reminder.");

	if ( pg_num_rows($rslt) < 1 ) {
		// check if the user HAS a reminder
		$sql = "SELECT * FROM vatreminder WHERE username='".USER_NAME."' AND opt<>'dont'";
		if ( pg_num_rows(db_exec($sql)) > 0 ) {
			return;
		}

		// no reminder, check if it time to create ONE.... mwahahahahahahhahahaha
		switch ( $prdcat[0] ) {
		case "a":
			if ( ! (date("m") % 2 && date("d") == date("t")) ) {
				return;
			}
			break;
		case "b":
			if ( ! (date("m") % 2 == 0 && date("d") == date("t")) ) {
				return;
			}
			break;
		case "c":
			if ( ! (date("d") == date("t")) ) {
				return;
			}
			break;
		case "d":
			if ( ! (( date("m") == 2 || date("m") == "8" ) && date("d") == date("t")) ) {
				return;
			}
			break;
		case "e":
			$prdmon = substr($prdcat, 1);
			if ( ! (date("m") == $prdmon && date("d") == date("t")) ) {
				return;
			}
			break;
		case "f":
			if ( ! (date("m") == 6 || date("m") == 10 || date("m") == 2) ) {
				return;
			}
			break;
		}
	}

	$OUTPUT = "
		<h3>Vat Transactions</h3>
		<li class='err'>Please remember to do your Vat Return<br>
			Remind me again in:
				<a href='".SELF."?reminder=min&val=30'>30 Minutes</a>,
				<a href='".SELF."?reminder=hour&val=1'>1 Hour</a>,
				<a href='".SELF."?reminder=hour&val=2'>2 Hours</a>,
				<a href='".SELF."?reminder=hour&val=3'>3 Hours</a>,
				<a href='".SELF."?reminder=hour&val=6'>6 Hours</a>,
				<a href='".SELF."?reminder=day&val=1'>Tomorrow</a>,
				<a href='".SELF."?reminder=day&val=3'>3 Days</a>,
				<a href='".SELF."?reminder=day&val=7'>1 Week</a>.
				<a href='".SELF."?reminder=dont'>Dont Remind Me</a>,
		</li>";
	require("template.php");

}


/**
 * used to return account hooks
 *
 * @param string $cusnum customer number
 * @param string $days determines age period
 * @param string $fcid fcid from customer table
 * @param string $loc location from customer table
 * @param string $month month for calculation
 * @param string $stdate statement date
 * @return string value of $field
 */
function cust_age($cusnum, $days, $fcid, $loc, $month, $stdate="now",$sfdate="now")
{

	if (!isset($month) OR strlen($month) < 1){
		$month = date("m");
	}

	#HAX TO MAKE TRANS-YEAR WORK
	if ($stdate == "now") 
		$stdate = date ("Y-m-d");
	$getfromyear = (int)substr($sfdate,0,4);
	$getfromyear += 0;
	$gettoyear = (int)substr($stdate,0,4);
	$gettoyear += 0;
//	if ($month == "01" AND $getfromyear < $gettoyear) 
//		$frommonth = "12";
//	else 
//		$frommonth = $month;

	$ldays = $days;

	if ($days == 149) {
		$ldays = (365 * 10);
	}

	#determine the date we are using for aging .... statement date or received "invoice" date
	$age_setting = getCSetting("STATEMENT_AGE");

	if (!isset($age_setting) OR strlen ($age_setting) < 1){
		#use customer statement date for default
		$get_stmnt = "SELECT setdays FROM customers WHERE cusnum = '$cusnum' LIMIT 1";
		$run_stmnt = db_exec($get_stmnt) or errDie ("Unable to get statement date information.");
		if(pg_numrows($run_stmnt) < 1){
			$stmnt_day = date("d");
		}else {
			$stmnt_day = pg_fetch_result ($run_stmnt,0,0);
		}

		if ($stmnt_day == "0")
			$stmnt_day = date("d",mktime (0, 0, 0, $month+1, 0, $gettoyear));

		$stmnt_date = "$gettoyear-$month-$stmnt_day";
	}else {
		if ($age_setting == "statement"){

			#use customer statement date
			$get_stmnt = "SELECT setdays FROM customers WHERE cusnum = '$cusnum' LIMIT 1";
			$run_stmnt = db_exec($get_stmnt) or errDie ("Unable to get statement date information.");
			if(pg_numrows($run_stmnt) < 1){
				$stmnt_day = date("d");
			}else {
				$stmnt_day = pg_fetch_result ($run_stmnt,0,0);
			}
			if ($stmnt_day == "0") 
				$stmnt_day = date("d",mktime (0,0,0,$month+1,0,$gettoyear));

			if ($stmnt_day == "60"){
				$stmnt_date = date ("Y-m-d", mktime ("0","0","0", $month+2, 0, $gettoyear));
			}else {
				$stmnt_date = "$gettoyear-$month-$stmnt_day";
			}

		}elseif ($age_setting == "invoice"){
			if ($stdate == "now"){
				$stmnt_date = date("Y-m-d");
				$stmnt_day = date ("d");
			}else {
				$stmnt_date = $stdate;
				$stmnt_day = substr($stmnt_date,8,2);
			}
		}
	}

	if ($days == 29){
		#current
		$from_date = date("Y-m-d",mktime(0,0,0,$month,$stmnt_day,$gettoyear));
		$to_date = date("Y-m-d",mktime(0,0,0,$month + 1,$stmnt_day-1,$gettoyear));
	}elseif ($days == 59){
		#30 days
		$from_date = date("Y-m-d",mktime(0,0,0,$month - 1,$stmnt_day,$gettoyear));
		$to_date = date("Y-m-d",mktime(0,0,0,$month,$stmnt_day-1,$gettoyear));
	}elseif ($days == 89){
		#60 days
		$from_date = date("Y-m-d",mktime(0,0,0,$month - 2,$stmnt_day,$gettoyear));
		$to_date = date("Y-m-d",mktime(0,0,0,$month - 1,$stmnt_day-1,$gettoyear));
	}elseif ($days == 119){
		#90 days
		$from_date = date("Y-m-d",mktime(0,0,0,$month - 3,$stmnt_day,$gettoyear));
		$to_date = date("Y-m-d",mktime(0,0,0,$month - 2,$stmnt_day-1,$gettoyear));
	}elseif ($days == 149){
		#120 days
		$from_date = date("Y-m-d",mktime(0,0,0,$month - 4,$stmnt_day,$gettoyear-5));
		$to_date = date("Y-m-d",mktime(0,0,0,$month - 3,$stmnt_day-1,$gettoyear));
	}else {
		$from_date = $stmnt_date;
		$to_date = $stmnt_date;
	}

	$oldmethod = TRUE;
	$newmethod = FALSE;

	// check allocation state
	$get_check = "SELECT id, allocation_linked FROM cubit.stmnt WHERE cusnum = '$cusnum'";
	$run_check = db_exec ($get_check) or errDie ("Unable to get allocation check information.");
	if (pg_numrows ($run_check) > 0){
		while ($tarr = pg_fetch_array ($run_check)){
			if (strlen ($tarr['allocation_linked']) > 0) {
				$old_method = FALSE;
				$newmethod = TRUE;
			}
		}
	}

	if ($newmethod) {
		$get_entries = "
			SELECT amount, allocation_balance FROM cubit.stmnt 
			WHERE cusnum = '$cusnum' AND date BETWEEN '$from_date' AND '$to_date'";
		$run_entries = db_exec ($get_entries) or errDie ("Unable to get statement information");
		if (pg_numrows ($run_entries) < 1){
			$amount = 0;
		}else {
			while ($aarr = pg_fetch_array ($run_entries)){
				if ($aarr['amount'] > 0){
					$amount += $aarr['allocation_balance'];
				}else {
					$amount -= $aarr['allocation_balance'];
				}
			}
		}
	}else {
		#handle normal statement aging ...
		$sql = "
			SELECT sum(amount) 
			FROM cubit.stmnt
			WHERE 
				cusnum='$cusnum' AND
				CASE WHEN (length(allocation_date) > 0) THEN allocation_date
				ELSE date
				END
			BETWEEN '$from_date' AND '$to_date'";
		$amount_rslt = db_exec($sql) or errDie("Unable to retrieve aging.");
		$amount = pg_fetch_result($amount_rslt, 0);

		#handle reverse aging
		#get all entries that match ...
		$get_sql = "SELECT * FROM cubit.stmnt WHERE cusnum = '$cusnum' AND allocation_date = '1500-01-01'";
		$run_sql = db_exec($get_sql) or errDie ("Unable to get customer statement information.");
		if (pg_numrows($run_sql) < 1){
			#no reverse entries ... no problem ...
		}else {
			while ($rarr = pg_fetch_array ($run_sql)){
				$date_arr = explode ("|",$rarr['reverse_allocation_dates']);
				$amts_arr = explode ("|",$rarr['reverse_allocation_amounts']);
				array_pop($date_arr);
				array_pop($amts_arr);

				foreach ($date_arr AS $key => $each){
					$sql = "
						SELECT amount 
						FROM cubit.stmnt 
						WHERE cusnum = '$cusnum' AND ('$each' >= '$from_date') AND ('$each' <= '$to_date')";
					$amount_rslt = db_exec($sql) or errDie("Unable to retrieve aging.");
					if (pg_numrows($amount_rslt) > 0){
						$amount += $amts_arr[$key];
					}
				}
			}
		}
	}
	return sprint($amount);

}



?>
