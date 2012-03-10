<?

# This program is copyright by Cubit Accounting Software CC
# Reg no 2002/099579/23
# Full e-mail support is available
# by sending an e-mail to andre@andre.co.za
#
# Rights to use, modify, change and all conditions related
# thereto can be found in the license.html file that is
# distributed along with this program.
# You may not use this program in any way or form without
# consenting to the terms and conditions contained in the
# license. If this program did not include the license.html
# file please contact us at +27834433455 or via email
# andre@andre.co.za (In South Africa: Tel. 0834433455)
#
# Our website is at http://www.cubit.co.za
# comments. suggestions and applications for free coding
# could be made via email to andre@andre.co.za
#
# Our banking details as follows:
# Banker: Nedbank
# Account Name: Cubit Accounting Software
# Account Number: 1357 082517
# Swift Code: NEDSZAJJ
# Branch Code: 135705
# Branch Name: Manager Direct
# Banker Address: 3rd Floor Nedcor Park, 6 Press Avenue, Johanesburg
#
#
# Fees due to integrators, will be paid into your account within 30 days
# of receipt of the relevant license fee.
#
# Please ensure that we have your correct banking details.

include("./_dflt_version.php");

if ( ! defined("CUBIT_VERSION") ) {
	define("CUBIT_VERSION", "-1");
}

define ("DB_USER", "postgres");
define ("DB_PASS", "i56kfm");
define ("DB_DB", "cubit");

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
		break;

	case "windows":
		define("DB_HOST","host=localhost");
		define("PG_DUMP_EXE","pg_dump.exe");
		define("PSQL_EXE","psql.exe");
		break;

	default:
		die("Please set the platform in _platform.php");
}
	
?>
