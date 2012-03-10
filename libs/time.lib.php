<?
/**
 * Generally used functions/constants related to date/time
 * @package Cubit
 * @subpackage Time
 */

if (!defined("TIME_LIB")) {
	define("TIME_LIB", true);

/**
 * sets the timezone according to the setting in the db
 */
if (!defined("RAINING_OUTSIDE")) {
	date_default_timezone_set(getCSetting("LOCALE_TIMEZONE"));
}

/**
 * current date/time constants
 */
define("DATE_LOGGING", date("Y.m.d H.i.s"));  // for logging eg: 2002.07.17 10.33.55
define("DATE_STD", date("Y-m-d"));  // standard date eg: 2002-08-12
define("DATE_YEAR", date("Y"));
define("DATE_MONTH", date("m"));
define("DATE_DAY", date("d"));
define("DATE_DAYS", date("t"));

/**
 * date_part function constant: year part
 *
 * @see date_part();
 */
define("DP_YEAR", 1);

/**
 * date_part function constant: month part
 *
 * @see date_part();
 */
define("DP_MONTH", 2);

/**
 * date_part function constant: day part
 *
 * @see date_part();
 */
define("DP_DAY", 3);

/**
 * returns selected element of date
 *
 * can use in/constants
 *
 * @param string date
 * @param int parts: 1/DP_YEAR, 2/DP_MONTH, 3/DP_DAY
 *
 * @ignore
 */
function date_part($date, $part) {
	return explode_get($date, "-", $part - 1);
}

/**
 * returns the week day number of a date.
 *
 * checks on what day of the week a date is and returns that day number.
 *
 * @param int $mday
 * @param int $month
 * @param int $myear
 * @return string
*/
function getWeekDayNum($mday,$month,$year) {
	$tmp=date("w", mktime(0,0,0,$month,$mday,$year) );

	if ( $tmp == 0 ) $tmp=7;

	return $tmp;
}

/**
 * returns the week name of a date.
 *
 * checks on what day of the week a date is and returns that day's name.
 * by default long name is returned. specifying false as $long parameter
 * a short name can be returned instead
 *
 * @param int $mday
 * @param int $month
 * @param int $myear
 * @param bool $long whether long name should be returned
 * @return string
*/
function getWeekday($mday,$month,$year, $long = true) {
	if ($long) {
		$c = "l";
	} else {
		$c = "D";
	}
	return date("l", mktime(0,0,0,$month,$mday,$year) );
}

/**
 * returns the short week name of a date.
 *
 * checks on what day of the week a date is and returns that day's name.
 *
 * @ignore
 * @param int $mday
 * @param int $month
 * @param int $myear
 * @return string
*/
function getWeekdayS($mday,$month,$year) {
	return getWeekday($mday, $month, $year, false);
}

/**
 * returns the name of a month.
 *
 * by default long name is returned. specifying false as $long parameter
 * a short name can be returned instead
 *
 * @param int $month (dflt: current month)
 * @param bool $long whether long name should be returned
 * @return string
*/
function getMonthName($month = false, $long = true) {
	if ($long) {
		$c = "F";
	} else {
		$c = "M";
	}

	return date($c, mktime(0,0,0,$month,1,2000) );
}

/**
 * returns the short name of a month
 *
 * @ignore
 * @param int $month
 * @return string
*/
function getMonthNameS($month = false) {
	return getMonthName($month, false);
}

/**
 * returns the week number of the specified date
 *
 * @param int $mday
 * @param int $month
 * @param int $year
 * @return int
*/
function getWeekNumber($mday,$month,$year) {
	$sql = "SELECT EXTRACT(week FROM DATE '$year-$month-$mday')";
	$rslt = db_exec($sql);

	return pg_fetch_result($rslt, 0, 0);

	//return round(date("z", mktime(0,0,0,$month,$mday,$year) ) / 7);
}

/**
 * returns the system date's week number
 *
 * @return int
*/
function getTodayWeekNumber() {
	return getWeekNumber(date("d"), date("m"), date("Y"));
}

/**
 * returns the number of days in specified month/year
 *
 * @param int $month
 * @param int $year
 * @return int
*/
function getDaysInMonth($month, $year) {
	return (int)date("t", mktime(0, 0, 0, $month, 1, $year));
}

/**
 * returns a date part: day
 *
 * returns the day part of a numeric date, ex. 19 in 2001-02-19
 *
 * @param int $date
 * @return int
*/
function extractDay($date) {
	list($y, $m, $d) = explode("-", $date);
	return (int)$d;
}

/**
 * returns a date part: month
 *
 * returns the month part of a numeric date, ex. 02 in 2001-02-19
 *
 * @param int $date
 * @return int
*/
function extractMonth($date) {
	list($y, $m, $d) = explode("-", $date);
	return (int)$m;
}

/**
 * returns a date part: year
 *
 * returns the year part of a numeric date, ex. 2001 in 2001-02-19
 *
 * @param int $date
 * @return int
*/
function extractYear($date) {
	list($y, $m, $d) = explode("-", $date);
	return (int)$y;
}

/**
 * assigns the day/month/year to variables from date parts (exploded with -)
 *
 * @param string $date
 * @param int $year &
 * @param int $month &
 * @param int $day &
 * @return bool true on valid date
 */
function explodeDate($date = false, &$year, &$month, &$day) {
	if ($date === false) {
		$date = DATE_STD;
	}

	if(isset($date))
		list($year, $month, $day) = explode("-", $date);
	return true;
}

/**
 * makes a numeric date
 *
 * creates a date string from year, month and day in numeric form:
 * 2000-05-06. $year can also be unix epoch with month/day being false.
 *
 * @param int $year
 * @param int $month
 * @param int $day
 * @return string
*/
function mkdate($year, $month = false, $day = false) {
	return "$year-$month-$day";
}

/**
 * makes a numeric date from mktime
 *
 * creates a date string from year, month and day in numeric form:
 * 2000-05-06. $year can also be unix epoch with month/day being false.
 *
 * @param int $year/$unixtime
 * @param int $month
 * @param int $day
 * @return string
*/
function mkdatet($year, $month = false, $day = false) {
	if (is_numeric($year) && $month === false && $day === false) {
		return date("Y-m-d", $year);
	} else if ((string)$year == "" || (string)$month == "" || (string)$day == "") {
		return "$year-$month-$day";
	} else {
		return date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
	}
}

/**
 * makes a unix time using mktime from a string date
 *
 * @param string $date
 * @return int
 */
function mktimefd($date) {
	return mktimeft("$date 0:0:0");
}

/**
 * makes a unix time using mktime from a string time
 *
 * @param string $date
 * @return int
 */
function mktimeft($time) {
	$rx = "/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/";

	if (!preg_match($rx, $time, $m)) {
		return false;
	}

	return mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]);
}

/**
 * makes a numeric date with the last day of the selected month
 *
 * creates a date string from year, month and day in numeric form:
 * 2000-05-31
 *
 * @param int $year
 * @param int $month
 * @param int $day
 * @return string
*/
function mkldate($year, $month) {
	return date("Y-m-t", mktime(0, 0, 0, $month, 1, $year));
}

/**
 * makes a string date
 *
 * creates a date string from year, month and day. optionally a full month name
 * can be displayed, ex. December instead of Dec.
 *
 * @param int $year
 * @param int $month
 * @param int $day
 * @param bool $long whether a long/full month name should be displayed
 * @return string
*/
function mkstrdate($year, $month, $day, $long = false) {
	$t = ($long) ? "F" : "M";
	return date("d $t Y", mktime(0, 0, 0, $month, $day, $year));
}

/**
 * makes a date drop down: day
 *
 * creates an html drop down for day selection.
 * if a month and year is specified a day selection will be made for that month/year
 * if only a month is specified, the year 2000 will be used
 * if no month and year is specified 31 days will be listed
 *
 * @param string $name name and id of field
 * @param int $sel value of selected day
 * @param string $opt extra html options for field, ex. style='...'
 * @param int $month specific month for which you wish to generate selection
 * @param int $year specific year to go with month
 * @return string
*/
function mksel_day($name, $sel, $opt = "", $month = false, $year = false) {
	if (preg_match("/id='[^']+'/", $opt)) {
		$id = "";
	} else {
		$id = "id='$name'";
	}

	if ($month && $year) {
		$max = getDaysInMonth($month, $year);
	} else if ($month) {
		$max = getDaysInMonth($month, 2000);
	} else {
		$max = 31;
	}

	$drop = "<select name='$name' $id $opt>";
	for ($i = 1; $i < $max; ++$i) {
		if ($i == $sel) {
			$s = "selected";
		} else {
			$s = "";
		}

		$drop .= "<option $s value='$i'>$i</option>";
	}
	$drop .= "</select>";

	return $drop;
}

/**
 * makes a date drop down: month
 *
 * creates an html drop down for month selection. optionally a short month
 * name can be listed.
 *
 * @param string $name name and id of field
 * @param int $sel value of selected month
 * @param string $opt extra html options for field, ex. style='...'
 * @param bool $short whether a short month name should be displayed
 * @return string
*/
function mksel_month($name, $sel, $opt = "", $short = false) {
	if (preg_match("/id='[^']+'/", $opt)) {
		$id = "";
	} else {
		$id = "id='$name'";
	}

	$drop = "<select name='$name' $id $opt>";
	for ($i = 1; $i <= 12; ++$i) {
		if ($i == $sel) {
			$s = "selected";
		} else {
			$s = "";
		}

		$drop .= "<option $s value='$i'>".getMonthName($i, !$short)."</option>";
	}
	$drop .= "</select>";

	return $drop;
}

/**
 * makes a date drop down: year
 *
 * creates an html drop down for year selection
 *
 * @param string $name name and id of field
 * @param int $sel value of selected year
 * @param string $opt extra html options for field, ex. style='...'
 * @return string
*/
function mksel_year($name, $sel, $opt = "") {
	if (preg_match("/id='[^']+'/", $opt)) {
		$id = "";
	} else {
		$id = "id='$name'";
	}

	$drop = "<select name='$name' $id $opt>";
	for ($i = 1971; $i < 2038; ++$i) {
		if ($i == $sel) {
			$s = "selected";
		} else {
			$s = "";
		}

		$drop .= "<option $s value='$i'>$i</option>";
	}
	$drop .= "</select>";

	return $drop;
}

/**
 * returns the button to popup date selection.
 *
 * this one only returns the button. You specify which fields to update
 * by setting the selected fields id's to ${idpfx}_anything
 * and passing ${idpfx} to this function. then only the button will appear
 * and with selection it will update your form fields.
 *
 * you can also update arrays by setting the field id's to ${idpfx}[id]_anything
 * and specifying all the "id"s to update in an array as argument 2.
 *
 * @param string $idpfx form field id prefix
 * @param string $array array of object ids to update (false is none) (comma seperated)
 * @param string $btnstring string to display on button
 * @return string html
 */
function mkDateSelectB($idpfx, $array = false, $btnstring = "Select Date") {
	$GWPP = relpath("groupware", true);
	if ($array !== false) {
		return "<input type='button' value='$btnstring' onClick='dateSelPopup(\"$idpfx\", \"$GWPP\", \"$array\")'>";
	} else {
		return "<input type='button' value='$btnstring' onClick='dateSelPopup(\"$idpfx\", \"$GWPP\", null)'>";
	}
}

/**
 * returns date selection form fields
 *
 * @param string fname form field name
 * @param int year default selected year
 * @param int month default selected month
 * @param int day default selected day
 * @param string $btnstring string to display on button
 * @return string html
 */
function mkDateSelect($fname, $year = false, $month = false, $day = false, $btnstring = "Select Date") {



	if ($year === false) {
		$year = DATE_YEAR;
		$month = DATE_MONTH;
		$day = DATE_DAY;
	}

	$GWPP = relpath("groupware", true);

	$OUT = "";
	$OUT .= "<input size='2' type='text' name='${fname}_day' id='${fname}_day' value='$day'>&nbsp;";
	$OUT .= "<input size='2' type='text' name='${fname}_month' id='${fname}_month' value='$month'>&nbsp;";
	$OUT .= "<input size='4' type='text' name='${fname}_year' id='${fname}_year' value='$year'>&nbsp;";
	$OUT .= "<input type='button' onClick='dateSelPopup(\"$fname\", \"$GWPP\", null)' value='$btnstring'>";

	return $OUT;
}

/**
 * returns date selection form fields in array form
 *
 * @param string $fname form field name
 * @param mixed $key array key
 * @param int $year default selected year
 * @param int $month default selected month
 * @param int $day default selected day
 * @param string $btnstring string to display on button
 * @return string html
 */
function mkDateSelectA($fname, $key, $year = false, $month = false, $day = false, $btnstring = "Select Date") {
	if ($year === false) {
		$year = DATE_YEAR;
		$month = DATE_MONTH;
		$day = DATE_DAY;
	}

	$GWPP = relpath("groupware", true);

	if (is_array($key)) {
		$nkey = "";
		$key = $key[0];
	} else {
		$nkey = $key;
	}

	$OUT = "<input size='2' type='text' name='${fname}_day[$nkey]' id='${fname}[$key]_day' value='$day'>&nbsp;";
	$OUT .= "<input size='2' type='text' name='${fname}_month[$nkey]' id='${fname}[$key]_month' value='$month'>&nbsp;";
	$OUT .= "<input size='4' type='text' name='${fname}_year[$nkey]' id='${fname}[$key]_year' value='$year'>&nbsp;";
	$OUT .= "<input type='button' onClick='dateSelPopup(\"${fname}[$key]\", \"$GWPP\", null)' value='$btnstring'>";

	return $OUT;
}

/**
 * returns html for a date selection
 *
 * the form fields to update must have in id in the form of
 * ${idpfx}_day, ${idpfx}_month, ${idpfx}_year in the order day, month, year
 * respectively.
 *
 * @ignore
 * @param string idpfx prefix for form fields to update
 * @return string html
 */
function dateSelection($idpfx) {
	global $GWPP;

	/* all the different date parts with default values -> false */
	$date_fields = array(
		"day",
		"month",
		"year",
		"sday",
		"smonth",
		"syear"
	);

	foreach ($date_fields as $k) {
		if (!isset($_REQUEST[$k])) {
			$$k = false;
		} else {
			$$k = $_REQUEST[$k];
		}
	}

	/* why check only date === false but set all of them to date()
		values? what if they have values you gonna overwrite?
		because I dont want bugs like day isset, month not, year isset
		causing some weird month to be shown */
	if ($day === false) {
		$day = date("d");
		$month = date("m");
		$year = date("Y");
	}

	if ($sday === false) {
    	$sday = $day;
    	$smonth = $month;
    	$syear = $year;
    }

    /* forcibly fix the date */
    explodeDate(date("Y-m-d", mktime(0, 0, 0, $smonth, $sday, $syear)), $syear, $smonth, $sday);

    /* previous year */
	$pyear = extractYear(mkdatet($year - 1, $month, 1));

	/* previous month */
	$pmonth = extractMonth(mkdatet($year, $month - 1, 1));
	$pmyear = extractYear(mkdatet($year, $month - 1, 1));

	/* next month */
	$nmonth = extractMonth(mkdatet($year, $month + 1, 1));
	$nmyear = extractYear(mkdatet($year, $month + 1, 1));

	/* next year */
	$nyear = extractYear(mkdatet($year + 1, $month, 1));

	/* month/year selections */
	$dateselmove = "dateSelMoveBySelect(\"$idpfx\", \"$day\", \"$sday\", \"$smonth\", \"$syear\", \"$GWPP\");";
	$move_month = mksel_month("datesel_move_month", $month, "onchange='$dateselmove'", true);
	$move_year = mksel_year("datesel_move_year", $year, "onchange='$dateselmove'");

	$title_style = "onMouseUp='moveXLayer(false);' onMouseDown='moveXLayer(true);'";
	$OUTPUT="
	<div id='datesel_container' style='background: #fdeb89; border: 1px dashed black;' >
	<div id='datesel_loading' style='position: absolute; visibility: hidden;'>
		<p style='margin-left: 25px; margin-top: 60px;'>
			<strong>Loading. Please Wait...</strong>
		</p>
	</div>
	<div id='datesel_calender'>
	<table>
	<tr>
		<td nowrap='t' align='left'>
			$move_month $move_year
			<!--<input type='button' onclick='$dateselmove' value='Go'/>-->
		</td>
		<td align='right' nowrap='t' onMouseUp='moveXLayer(false);' onMouseDown='moveXLayer(true);'>
			<b><a id='xpopup_cls' href='javascript: XPopupHideAct()'>[X]&nbsp;&nbsp;</a></b>
		</td>
	</tr>
	<tr><td colspan='2'>

	<table width='190' cellspacing='0'>
	<tr>
		<td $title_style ".TMPL_calSmallMonthTitleStyleLeft.">
			<a href='javascript: dateSelMove(\"$idpfx\", \"$day\", \"$month\", \"$pyear\", \"$sday\", \"$smonth\", \"$syear\", \"$GWPP\");'><img border='0' src='${GWPP}/left_year.gif' /></a>
			<a href='javascript: dateSelMove(\"$idpfx\", \"$day\", \"$pmonth\", \"$pmyear\", \"$sday\", \"$smonth\", \"$syear\", \"$GWPP\");'><img border='0' src='${GWPP}/left_month.gif' /></a>
		</td>
		<td $title_style ".TMPL_calSmallMonthTitleStyleCenter.">
			".getMonthName($month) . " $year
		</td>
		<td $title_style ".TMPL_calSmallMonthTitleStyleRight.">
			<a href='javascript: dateSelMove(\"$idpfx\", \"$day\", \"$nmonth\", \"$nmyear\", \"$sday\", \"$smonth\", \"$syear\", \"$GWPP\");'><img border='0' src='${GWPP}/right_month.gif' /></a>
			<a href='javascript: dateSelMove(\"$idpfx\", \"$day\", \"$month\", \"$nyear\", \"$sday\", \"$smonth\", \"$syear\", \"$GWPP\");'><img border='0' src='${GWPP}/right_year.gif' /></a>
		</td>
	</tr>
	<tr>
		<td align='center' ".TMPL_calSmallMonthBodyStyle." colspan='5'>";

	// generate the titles of the weekdays
	$OUTPUT.="
		<table width='184' cellspacing='0'>
			<tr>
				<td width='23'>&nbsp;</td>
				<td width='23' align='center'><b>M</b></td>
				<td width='23' align='center'><b>T</b></td>
				<td width='23' align='center'><b>W</b></td>
				<td width='23' align='center'><b>T</b></td>
				<td width='23' align='center'><b>F</b></td>
				<td width='23' align='center' bgcolor=".TMPL_calFillSaturday."><b>S</b></td>
				<td width='23' align='center' bgcolor=".TMPL_calFillSunday."><b>S</b></td>
			</tr>";

	// get the weekday number of the first of this month
	$first_wd = getWeekdayNum(1, $month, $year);

	// the following code will generate the first entries on the calendar, which is for the previous month (if any)
	// month and year of previous month
	if ($month == 1) {
		$tmp_month = 12;
		$tmp_year = $year - 1;
	} else {
		$tmp_month = $month - 1;
		$tmp_year = $year;
	}

    // date of last monday in previous month (where the entries will start)
    if ($first_wd > 1) {
        $tmp_day = getDaysInMonth($tmp_month, $tmp_year) - ($first_wd - 2);
    } else {
        $tmp_day = 1;
        $tmp_month = $month;
        $tmp_year = $year;
    }

    // create a view variables
	$selected_month = $smonth;

    // create the previous month's entries
    $c_weeknum = getWeekNumber($tmp_day, $tmp_month, $tmp_year);
    $selected_weeknum = getWeekNumber($sday,$smonth,$syear);

	// if today's week number = the current generated week's number, hightlight the row, as so with the selected week,
	if (($c_weeknum == getTodayWeekNumber() && $tmp_year == date("Y") && $month == date("m"))
		|| (getTodayWeekNumber() == 0 && $month == date("m") && $year == date("Y"))) { // today's week
		$OUTPUT .= "<tr bgcolor='".TMPL_calSmallMonthCurrentWeek."'>";
		$ROW_COLORED = 1;
	} else if (($year == $syear && $month == $smonth && $c_weeknum == $selected_weeknum)
			|| ($c_weeknum == 52 && $selected_weeknum == 0)) { // selected week, the last check is for the first week in jan
		$OUTPUT.="<tr bgcolor='".TMPL_calSmallMonthSelectedWeek."'>";
		$ROW_COLORED=1;
	} else {  // other dates
		$OUTPUT .= "<tr>";
		$ROW_COLORED = 0;
	}

	if ($first_wd != 1) // only if there is a day in this week of previous month, print the week number
		$OUTPUT .= "<td width='23' ".TMPL_calSmallMonthWeekNumberStyle." align='center'>$c_weeknum</td>";

	for ($c_wd = 1; $c_wd < $first_wd; $c_wd++, $tmp_day++) {
		// fill differently for saturday and sunday (only when row wasn't already highlighted)
		if ($c_wd == 6 && ! $ROW_COLORED) {
			$dayfill = "bgcolor='" . TMPL_calFillSaturday . "'";
		} else if ( $c_wd == 7 ) {
			$dayfill = "bgcolor='" . TMPL_calFillSunday . "'";
		} else {
			$dayfill = "";
		}

		$OUTPUT .= "
			<td $dayfill width='23' align='center'>
				<a id='calSmallMonthOMLink' href='javascript: dateSelUpdate(\"$idpfx\", \"$tmp_day\", \"$tmp_month\", \"$tmp_year\");'>$tmp_day</a>
			</td>";
	}

	// start creating this month's entries
	$cm_days = getDaysInMonth($month,$year);
	for ($c_day = 1; $c_day <= $cm_days; $c_day++) {
		$c_weeknum = getWeekNumber($c_day, $month, $year);

		if ($c_wd == 1) { // start a new row (it's MONDAY!!!!!)
			// if today's week number = the current generated week's number, hightlight the row, as so with the selected week
			if ($c_weeknum == getTodayWeekNumber() && $year == date("Y") && $month == date("m")) { // today's week
				$OUTPUT .= "<tr bgcolor='".TMPL_calSmallMonthCurrentWeek."'>";
				$ROW_COLORED = 1;
			} else if (($year == $syear && $month == $smonth && $c_weeknum == $selected_weeknum)) { // selected week
				$OUTPUT.="<tr bgcolor='".TMPL_calSmallMonthSelectedWeek."'>";
				$ROW_COLORED=1;
			} else {  // other dates
				$OUTPUT .= "<tr>";
				$ROW_COLORED = 0;
			}

			// attach the week number
			$OUTPUT .= "<td align='center' width='23' ".TMPL_calSmallMonthWeekNumberStyle.">$c_weeknum</td>";
		}

		/* change the fill color if it it 2day's date we are printing */
		if ( $c_day == $sday
				&& $month == $smonth
				&& $year == $syear) { // selected date
			$dayfill="bgcolor=".TMPL_calSmallMonthSelectedDay;
			$a_id="calSmallMonthCMLinkSelected";
		} else if (date("d") == $c_day && date("m") == $month && date("Y") == $year) { // 2day's date
			$dayfill = "bgcolor='" . TMPL_calSmallMonthCurrentDay . "'";
			$a_id = "calSmallMonthCMLinkToday";
		} else { // other dates
			// fill differently for saturday and sunday (only when the row has not already been colored)
			if ($c_wd == 6 && ! $ROW_COLORED) {
				$dayfill = "bgcolor='" . TMPL_calFillSaturday . "'";
			} else if ( $c_wd == 7 && ! $ROW_COLORED) {
				$dayfill = "bgcolor='" . TMPL_calFillSunday . "'";
			} else {
				$dayfill = "";
			}

			$a_id = "calSmallMonthCMLink";
		}

		$OUTPUT .= "
			<td width='23' $dayfill align='center'>
				<a id='$a_id' href='javascript: dateSelUpdate(\"$idpfx\", \"$c_day\", \"$month\", \"$year\");'>$c_day</a>
			</td>";

		/* end of output */
		if ($c_wd == 7) {
			$OUTPUT .= "</tr>";
		}

		$c_wd = ($c_wd == 7) ? 1 : $c_wd + 1;
	}

	// next month variables
	if ($month == 12) {
		$tmp_month = 1;
		$tmp_year = $year + 1;
	} else {
		$tmp_month = $month + 1;
		$tmp_year = $year;
	}

	// finish with the next months entries
	for ($c_day = 1; $c_wd <= 7 && $c_wd > 1; $c_wd++, $c_day++) {
		// fill differently for saturday and sunday
		if ( $c_wd == 6 && ! $ROW_COLORED) {
			$dayfill  ="bgcolor='" . TMPL_calFillSaturday . "'";
		} else if ( $c_wd == 7 && ! $ROW_COLORED) {
			$dayfill = "bgcolor='" . TMPL_calFillSunday . "'";
		} else {
			$dayfill = "";
		}

		$OUTPUT .= "
		<td $dayfill width='23' align='center'>
			<a id='calSmallMonthOMLink' href='javascript: dateSelUpdate(\"$idpfx\", \"$c_day\", \"$tmp_month\", \"$tmp_year\");'>$c_day</a>
		</td>";
	}

	/* finish the tables and return */
	$OUTPUT.="
			</tr>
		</table>
		</td>
	</tr>
	</table>

	</td></tr>
	</table>
	</div>
	</div>";

	return $OUTPUT;
}

} /* LIB END */
?>
