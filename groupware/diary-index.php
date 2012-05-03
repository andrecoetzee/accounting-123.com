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

# get settings
require("../settings.php");

// remove all '
if ( isset($_POST) ) {
	foreach ( $_POST as $key => $value ) {
		$_POST[$key] = str_replace("'", "", $value);
	}
}
if ( isset($_GET) ) {
	foreach ( $_GET as $key => $value ) {
		$_GET[$key] = str_replace("'", "", $value);
	}
}

// the following two jobs are done to make life easier and more stable
// overwrite the post vars with the get vars
if ( isset($_POST) ) {
	foreach($_POST as $gvar => $value) {
		$_GET[$gvar]=$value;
	}
}

// set the date to read to current one if not specified
if ( ! isset($_GET["year"]) )
	$_GET["year"] = date("Y");

if ( ! isset($_GET["month"]) )
	$_GET["month"] = date("m");

if ( ! isset($_GET["mday"]) )
	$_GET["mday"] = date("d");

// specified date in a processed form
$spec_date = "$_GET[year]-$_GET[month]-$_GET[mday]";

// check that the date is valid : if not, try to fix it
if ( ! checkdate( $_GET["month"] , $_GET["mday"] , $_GET["year"] ) ) {
	$valid_date = mktime(0, 0, 0, $_GET["month"], $_GET["mday"], $_GET["year"]);
	list($_GET["year"], $_GET["month"], $_GET["mday"])
		= explode("-", date("Y-m-d", $valid_date));

	if ( ! checkdate( $_GET["month"] , $_GET["mday"] , $_GET["year"] ) )
		die("Invalid date specified: $spec_date<br>Please contact Cubit.");
}

// includes the calendar scripts like day, month, year and global
include("diary-day.php");
include("diary-month.php");
include("diary-year.php");

// include the calendar objects
include("object-dayentry.php");
include("object-dayschedule.php");

// create the $OUTPUT variable
$OUTPUT="";

// decide what to do
if (isset($_GET["key"])) {
	switch ($_GET["key"]) {
		case 'month':
			$OUTPUT = showCalendar_month();
			break;
		case 'year':
			//$OUTPUT.=showCalendar_year();
			break;
		case "viewother":
			$OUTPUT = selectOther();
			break;
		case 'day':
		default:
			$OUTPUT = showCalendar_day();
			break;
	}
} else {
	$OUTPUT = showCalendar_day();
}

// $OUTPUT = "
// <div class='sub_container'>
// 	$OUTPUT
// </div>";

function selectOther() {
	db_conn("cubit");
	$sql = "SELECT diary_owner FROM diary_privileges WHERE priv_owner='".USER_NAME."' AND privilege='R'";
	$rslt = db_exec($sql) or errDie("Error reading privileges.");

	$users = Array();
	$users[USER_NAME] = USER_NAME;
	while ( $row = pg_fetch_array($rslt) ) {
		$users[ $row["diary_owner"] ] = $row["diary_owner"];
	}

	$select_user = extlib_cpsel("view_diary", $users, "");

	$OUTPUT = "
	<div id='diary_container'>
	<h3>View Other Diary</h3>
	<form method=get action='".SELF."'>
	<table cellpadding='2' cellspacing='0' class='shtable'>
	<tr>
		<th colspan=2>Details</th>
	</tr>
	<tr class='bg-odd'>
		<td align=center>$select_user</td>
		<td align=center><input type=submit value='View'></td>
	</tr>
	</table>
	</form>
	</div>";

	return $OUTPUT;
}

// set the reload of the document
$OUTPUT.="
<script>
		//setTimeout(function() { document.location.reload(); },". TMPL_calRefreshTime * 1000 .");
</script>";

require ("gw-tmpl.php");
// get templete
// require("../template.php");


?>
