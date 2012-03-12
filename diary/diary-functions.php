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

// returns the weekday number (1-monday, 7-sunday)
function getWeekDayNum($mday,$month,$year) {
	$tmp=date("w", mktime(0,0,0,$month,$mday,$year) );

	if ( $tmp == 0 ) $tmp=7;

	return $tmp;
}

// returns the weekday
function getWeekday($mday,$month,$year) {
	return date("l", mktime(0,0,0,$month,$mday,$year) );
}

// returns the 3 letter version of the weekday
function getWeekdayS($mday,$month,$year) {
	return date("D", mktime(0,0,0,$month,$mday,$year) );
}

// returns the full text of the specified month
function getMonthText($month) {
	return date("F", mktime(0,0,0,$month,1,2000) );
}

// returns the abvreviated month name
function getMonthTextS($month) {
	return date("M", mktime(0,0,0,$month,1,2000) );
}

// returns the week number the specified date is in
function getWeekNumber($mday,$month,$year) {
	$sql = "SELECT EXTRACT(week FROM DATE '$year-$month-$mday')";
	$rslt = db_exec($sql);

	return pg_fetch_result($rslt, 0, 0);

	//return round(date("z", mktime(0,0,0,$month,$mday,$year) ) / 7);
}

// returns today's week number
function getTodayWeekNumber() {
	return getWeekNumber(date("d"), date("m"), date("Y"));
}

// returns the number of date in the specified month and year
function getDaysInMonth($month,$year) {
	switch ($month) {
		case 1:
		case 3:
		case 5:
		case 7:
		case 8:
		case 10:
		case 12:
			return 31;
		case 4:
		case 6:
		case 9:
		case 11:
			return 30;
		case 2:
			if ( $year % 4 == 0 ) // is a leap year
				return 29;
			else
				return 28;
		default:
			return 0;
	}
}
?>
