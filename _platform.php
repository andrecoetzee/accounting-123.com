<?
# This program is copyright by Cubit 
# Full e-mail support is available
# by sending an e-mail to sales@cubit.co.za
#
# Rights to use, modify, change and all conditions related
# thereto can be found in the license.html file that is
# distributed along with this program.
# You may not use this program in any way or form without
# consenting to the terms and conditions contained in the
# license. If this program did not include the license.html
# file please contact us at +27834433455 or via email
# andre@andre.co.za (In South Africa: Tel. 0766006613)
#

/* debug levels: 1=ALL except NOTICE, 2=ALL */
define("DEBUG", 0);
define("ERRORNET", 0);

if (defined("CUBIT_WD")) {
	$DPFX = CUBIT_WD;
} else {
	$DPFX = dirname($_SERVER["SCRIPT_FILENAME"]);
}

$DPFX = preg_replace("/\\\\/", "/", $DPFX);

if (empty($DPFX)) {
	$DPFX = ".";
}

if ( is_file("${DPFX}/_version.php") ) require("${DPFX}/_version.php");
else if ( is_file("${DPFX}/../_version.php") ) require("${DPFX}/../_version.php");
else if ( is_file("${DPFX}/../../_version.php") ) require("${DPFX}/../../_version.php");
else if ( is_file("${DPFX}/../../../_version.php") ) require("${DPFX}/../../../_version.php");

if ( ! defined("CUBIT_VERSION") ) {
	define("CUBIT_VERSION", "-1");
}

define ("DB_USER", "postgres");
define ("DB_PASS", "i56kfm");
define ("DB_DB", "cubit");

define ("DB_MUSER", "postgres");
define ("DB_MPASS", "i56kfm");

// DO NOT CHANGE ANY BELOW THIS

// determine the platform we are running this one "linux" or "windows" (LOWERCASE!!!)
switch ( PHP_OS ) {
	case "Linux":
	case "SunOS":
	case "Darwin":
	case "AIX":
		define("PLATFORM", "linux");
		break;
	case "WIN32":
	case "WINNT":
	default:
		define("PLATFORM", "windows");
		break;
}

// this will set all the setting differences between the platforms
switch ( PLATFORM ) {
	case "linux":
		define("DB_HOST","");
		define("PG_DUMP_EXE","pg_dump");
		define("PSQL_EXE","psql");
		define("PATH_DEL", "/");
		break;

	case "windows":
		define("DB_HOST","host=localhost");
		define("PG_DUMP_EXE","pg_dump.exe");
		define("PSQL_EXE","psql.exe");
		define("PATH_DEL", "\\");
		break;

	default:
		die("Unsupported platform. Contact Cubit Pty. Ltd for a version for your
		platform or operating system.");
}

?>
