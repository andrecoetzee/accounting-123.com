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

// shows the day calendar
function showCalendar_day() {
	global $_GET;

	// get the post_vars
	extract($_GET);

	// create the day view and month view data
	if ( ( ! isset($view_diary) ) || $view_diary == USER_NAME ) {
		$view_diary = USER_NAME;
	} else {
		// make sure user has read privileges on this diary
		$view_diary = remval($view_diary);
		db_conn("cubit");
		$sql = "SELECT * FROM diary_privileges
			WHERE priv_owner='".USER_NAME."' AND diary_owner='$view_diary' AND privilege='R'";
		$rslt = db_exec($sql) or errDie("Error reading diary diary privileges.");

		if ( pg_num_rows($rslt) < 1 ) {
			return "<li class=err>You do not have sufficient permissions to read this diary.</li>";
		}
	}

	$DayView=createDayView($mday,$month,$year,$view_diary);
	$MonthViews=createMonthViews($month,$year,$view_diary);
	$Notices=createNotices($mday,$month,$year,$view_diary);

	$OUT="
	<center><table width='750'>
		<tr>
			<td width=550 valign=top>
				$DayView
			</td>
			<td width=200 valign=top>
				$MonthViews
				<hr>
				$Notices
			</td>
		</tr>
	</table></center>";

	return $OUT;
}

// creates the day view of the current day
function createDayView($mday,$month,$year,$view_diary) {
	// generate the previous day link data (for _GET)
	if ( $mday == 1 ) {
		if ( $month == 1 ) {
			$tmpmonth=12;
			$tmpyear=$year-1;
		} else {
			$tmpmonth=$month-1;
			$tmpyear=$year;
		}

		$tmpday=getDaysInMonth($tmpmonth,$tmpyear);
	} else {
		$tmpday=$mday-1;
		$tmpmonth=$month;
		$tmpyear=$year;
	}

	$prevlink="mday=$tmpday&month=$tmpmonth&year=$tmpyear";

	// generate the next day link data (for _GET)
	if ( $mday == getDaysInMonth($month,$year) ) {
		if ( $month == 12 ) {
			$tmpmonth=1;
			$tmpyear++;
		} else {
			$tmpmonth=$month+1;
			$tmpyear=$year;
		}

		$tmpday=1;
	} else {
		$tmpday=$mday+1;
		$tmpmonth=$month;
		$tmpyear=$year;
	}

	$nextlink="mday=$tmpday&month=$tmpmonth&year=$tmpyear";

	// create the output of the header (with date, and previous and next buttons
	$OUT="
		<table width='550' cellspacing=0 cellpadding=0>
			<tr><td align=center valign=top ".TMPL_calTimeStyle2.">
				<table width='100%' cellspacing=0 cellpadding=0>
					<tr>
						<td valign=middle align=center width='30%'>
							<a href='" . SELF . "?$prevlink'><img border=0 src='left_day.gif' align=right></a>
						</td>
						<td nowrap valign=middle align=center  ".TMPL_calTimeStyleHeader." width='40%'>
							" . GetWeekday($mday,$month,$year) . ", " . getMonthName($month) . " $mday
						</td>
						<td valign=middle align=center width='30%'>
							<a href='" . SELF . "?$nextlink'><img border=0 src='right_day.gif' align=left></a>
						</td>
					</tr>
				</table>
				<table width='100%' cellspacing=0 cellpadding=0 border=0>";

	// create the scedule layout
	$ds = & new clsDaySchedule();
	$ds->addDiary($view_diary);
	$ds->generateScheduleMatrix();
	$OUT.=$ds->generateScheduleData();

	$OUT.="
			</table>
		</td></tr>
	</table>
	";

	return $OUT;
}

// create the month view of the current, previous and next month
function createMonthViews($month,$year,$view_diary) {
	global $_GET;

	// compute the previous month and it's year
	if ( $month == 1 ) {
		$prevmonth=12;
		$prevyear=$year-1;
	} else {
		$prevmonth=$month-1;
		$prevyear=$year;
	}

	// compute then next month and it's year
	if ( $month == 12 ) {
		$nextmonth=1;
		$nextyear=$year+1;
	} else {
		$nextmonth=$month+1;
		$nextyear=$year;
	}

	// generate the different months
	$PreviousMonth=generateMonthView_small($prevmonth,$prevyear,$view_diary);
	$CurrentMonth=generateMonthView_small($month,$year,$view_diary);
	$NextMonth=generateMonthView_small($nextmonth,$nextyear,$view_diary);

	// generate the month and year selections
	$select_month = "<select name='month'>";
	for ( $i=1 ; $i<=12 ; $i++ ) {
		if ( isset($_GET["month"]) && $_GET["month"] == $i )
			$selected="selected";
		else
			$selected="";

		$select_month.="<option value=$i $selected>".date("M",mktime(0,0,0,$i,1,2000))."</option>";
	}
	$select_month .= "</select>";

	$select_year = "<select name='year'>";
	for ( $i = 1990 ; $i <= 2050 ; $i++ ) {
		if ( isset($_GET["year"]) && $_GET["year"] == $i )
			$selected="selected";
		else
			$selected="";

		$select_year.="<option value=$i $selected>$i</option>";
	}
	$select_year .= "</select>";

	$OUT="
		<table width=190>
			<tr>
				<td>
					<table width=190>
						<tr>
							<td height=20 align=center colspan=7 ".TMPL_calTodayLinkStyle." onClick='location.href=\"".SELF."\";' >
								<b>View Today</b>
							</td>
					</tr>
					</table><br>

					$PreviousMonth <br>
					$CurrentMonth <br>
					$NextMonth <br>

					<table width=190>
						<tr>
							<td>
								<form>
									<input type=hidden name='mday' value='1'>
									$select_month
									$select_year
									<input type=submit value=Go>
								</form>
							</tr>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	";

	return $OUT;
}

// function that notifies you of things to remember, ex: appointments within the week etc....
function createNotices() {
	// read all appointment entries that should appear within the next 3 days, todos and birthdays
	$rslt = db_exec("SELECT title, category_name, -EXTRACT(day from age(time_start)) AS days,EXTRACT(day from time_start) as mday,
				EXTRACT(month from time_start) as month, EXTRACT(year from time_start) as year
			FROM diary_entries,diary_categories
			WHERE -EXTRACT(day from age(time_start)) > 0
				AND -EXTRACT(day from age(time_start)) <= notify
				AND -EXTRACT(month from age(time_start)) = 0
				AND diary_categories.category_id = diary_entries.category_id
			");

	// this creates the arrays of the different kinds of notices
	while ( $sqlrow = pg_fetch_array($rslt) ) {
		$catname = $sqlrow["category_name"];
		$days = $sqlrow["days"];

		$ap_notices [$catname] [$days] [] = "<a id='calNotices' href='diary-index.php?mday=$sqlrow[mday]
			&month=$sqlrow[month]&year=$sqlrow[year]'>".$sqlrow["title"]."</a>";
	}

	// if none was found, return
	if ( ! isset($ap_notices) )
		return "";

	// create the different outputs
	$OUT="";
	foreach ( $ap_notices as $notice => $value ) {
		$OUT.="<table width=190>
				<tr><td colspan=2 align=center ".TMPL_calNoticesStyle.">$notice</td></tr>";

		// loop from 1 day before to 14 days before
		for ( $arr = 1 ; $arr <= 14 ; $arr++ ) {
			// if there is such an entry
			if ( isset( $ap_notices[$notice][$arr] ) ) {
				// create the heading
				if ( $arr == 1 ) // 1 day heading (red)!!!
					$heading = "<font color=red>1 Day:</font>";
				else
					$heading = "$arr Days:";

				// create the entries
				foreach ( $ap_notices[$notice][$arr] as $arr2 => $arrval2 ) {
					$OUT .= "<tr>
						<td ".TMPL_calNoticesStyle." valign=top align=left>$heading</td>
						<td ".TMPL_calNoticesStyle." valign=top align=right>$arrval2<br></td>
					</tr>";
					$heading = "&nbsp;"; // it is cleared, so it only gets showed the first time :>
				}
			}
		}

		$OUT .= "</table><br>";
	}

	return $OUT;
}

// creates a little month calendar
function generateMonthView_small($month,$year,$view_diary) {
    global $_GET;

	$OUT="
		<table width=190 cellspacing=0>
			<tr>
				<td height=20 align=center ".TMPL_calSmallMonthTitleStyle."
						onClick='document.location.href=\"".SELF."?key=month&month=$month&year=$year\"'>
					<b>" . getMonthName($month) . " $year</b>
				</td>
			</tr>
			<tr>
				<td align=center ".TMPL_calSmallMonthBodyStyle.">";

	// generate the titles of the weekdays
	$OUT.="
			<table width='184' cellspacing=0>
				<tr>
					<td width=23>&nbsp;</td>
					<td width=23 align=center><b>M</b></td>
					<td width=23 align=center><b>T</b></td>
					<td width=23 align=center><b>W</b></td>
					<td width=23 align=center><b>T</b></td>
					<td width=23 align=center><b>F</b></td>
					<td width=23 align=center bgcolor=".TMPL_calFillSaturday."><b>S</b></td>
					<td width=23 align=center bgcolor=".TMPL_calFillSunday."><b>S</b></td>
				</tr>";

	// get the weekday number of the first of this month
	$first_wd=getWeekdayNum(1,$month,$year);

	// the following code will generate the first entries on the calendar, which is for the previous month (if any)
	// month and year of previous month
	if ( $month == 1 ) {
		$tmp_month=12;
		$tmp_year=$year-1;
	} else {
		$tmp_month=$month-1;
		$tmp_year=$year;
	}

    // date of last monday in previous month (where the entries will start)
    if ( $first_wd > 1 ) {
        $tmp_day=getDaysInMonth($tmp_month, $tmp_year) - ($first_wd - 2);
    } else {
        $tmp_day=1;
        $tmp_month = $month;
        $tmp_year = $year;
    }

	// create a view variables
	$selected_month=$_GET["month"];

    // create the previous month's entries
    $c_weeknum=getWeekNumber($tmp_day,$tmp_month,$tmp_year);
	$selected_weeknum=getWeekNumber($_GET["mday"],$_GET["month"],$_GET["year"]);

	// if today's week number = the current generated week's number, hightlight the row, as so with the selected week,
	if ( ( $c_weeknum == getTodayWeekNumber() && $tmp_year == date("Y") && $month == date("m"))
		|| ( getTodayWeekNumber() == 0 && $month == date("m") && $year == date("Y") ) ) { // today's week
		$OUT.="<tr bgcolor='".TMPL_calSmallMonthCurrentWeek."'>";
		$ROW_COLORED=1;
	} else if ( ($month == $_GET["month"] && $c_weeknum == $selected_weeknum && $month == $_GET["month"])
			|| ( $c_weeknum==52 && $selected_weeknum==0 )) { // selected week, the last check is for the first week in jan
		$OUT.="<tr bgcolor='".TMPL_calSmallMonthSelectedWeek."'>";
		$ROW_COLORED=1;
	} else {  // other dates
		$OUT.="<tr>";
		$ROW_COLORED=0;
	}

	if ( $first_wd!=1 ) // only if there is a day in this week of previous month, print the week number
		$OUT.="<td width=23 ".TMPL_calSmallMonthWeekNumberStyle." align=center>$c_weeknum</td>";

	for ( $c_wd=1 ; $c_wd < $first_wd ; $c_wd++,$tmp_day++ ) {
		// fill differently for saturday and sunday (only when row wasn't already highlighted)
		if ( $c_wd == 6 && ! $ROW_COLORED)
			$dayfill="bgcolor=".TMPL_calFillSaturday;
		else if ( $c_wd == 7 )
			$dayfill="bgcolor=".TMPL_calFillSunday;
		else
			$dayfill="";

		$OUT.="<td $dayfill width=23 align=center>
							<a id='calSmallMonthOMLink' href='".SELF."?mday=$tmp_day&month=$tmp_month&year=$tmp_year'>$tmp_day</a>
						</td>";
	}

	// start creating this month's entries
	$cm_days=getDaysInMonth($month,$year);
	for ( $c_day=1 ; $c_day <= $cm_days ; $c_day++ ) {
		$c_weeknum = getWeekNumber($c_day,$month,$year);

		if ( $c_wd == 1 ) { // start a new row (it's MONDAY!!!!!)
			// if today's week number = the current generated week's number, hightlight the row, as so with the selected week
			if ( $c_weeknum == getTodayWeekNumber() && $year == date("Y") && $month == date("m")) { // today's week
				$OUT.="<tr bgcolor='".TMPL_calSmallMonthCurrentWeek."'>";
				$ROW_COLORED=1;
			} else if ( ($month == $_GET["month"] && $c_weeknum == $selected_weeknum) ) { // selected week
				$OUT.="<tr bgcolor='".TMPL_calSmallMonthSelectedWeek."'>";
				$ROW_COLORED=1;
			} else {  // other dates
				$OUT.="<tr>";
				$ROW_COLORED=0;
			}

			// attach the week number
			$OUT.="<td align=center width=23 ".TMPL_calSmallMonthWeekNumberStyle.">$c_weeknum</td>";
		}

		// change the fill color if it it 2day's date we are printing, or the selected date
		if ( $c_day == $_GET["mday"]
				&& $month == $_GET["month"]
				&& $year == $_GET["year"]) { // selected date
			$dayfill="bgcolor=".TMPL_calSmallMonthSelectedDay;
			$a_id="calSmallMonthCMLinkSelected";
		} else if ( date("d") == $c_day && date("m") == $month && date("Y") == $year) { // 2day's date
			$dayfill="bgcolor=".TMPL_calSmallMonthCurrentDay;
			$a_id="calSmallMonthCMLinkToday";
		} else { // other dates
			// fill differently for saturday and sunday (only when the row has not already been colored)
			if ( $c_wd == 6 && ! $ROW_COLORED)
				$dayfill="bgcolor=".TMPL_calFillSaturday;
			else if ( $c_wd == 7 && ! $ROW_COLORED)
				$dayfill="bgcolor=".TMPL_calFillSunday;
			else
				$dayfill="";

			$a_id="calSmallMonthCMLink";
		}

		$OUT.="<td width=23 $dayfill align=center>
							<a id='$a_id' href='".SELF."?mday=$c_day&month=$month&year=$year&view_diary=$view_diary'>$c_day</a>
						</td>";

		if ( $c_wd == 7 ) // end the output
			$OUT.="</tr>";

		($c_wd==7) ? $c_wd=1 : $c_wd++;
	}

	// next month variables
	if ( $month == 12 ) {
		$tmp_month=1;
		$tmp_year=$year+1;
	} else {
		$tmp_month=$month+1;
		$tmp_year=$year;
	}

	// finish with the next months entries
	for ( $c_day=1 ; $c_wd<=7 && $c_wd>1 ; $c_wd++,$c_day++ ) {
		// fill differently for saturday and sunday
		if ( $c_wd == 6 && ! $ROW_COLORED)
			$dayfill="bgcolor=".TMPL_calFillSaturday;
		else if ( $c_wd == 7 && ! $ROW_COLORED)
			$dayfill="bgcolor=".TMPL_calFillSunday;
		else
			$dayfill="";

		$OUT.="<td $dayfill width=23 align=center>
							<a id='calSmallMonthOMLink' href='".SELF."?mday=$c_day&month=$tmp_month&year=$tmp_year'>$c_day</a>
						</td>";
	}

	// finish the tables and return
	$OUT.="
				</tr>
			</table>

				</td>
			</tr>
		</table>
	";

	return $OUT;
}

?>
