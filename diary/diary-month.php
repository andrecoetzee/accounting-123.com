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

if ( isset($_POST) && is_array($_POST) ) {
	foreach ( $_POST as $key => $value ) {
		$_GET[$key] = $value;
	}
}

// shows the month calendar
function showCalendar_month() {
	global $_GET;
	extract($_GET);

	// check diary view permissions
	if ( ! isset($view_diary) || $view_diary == USER_NAME) {
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

	if ( ! isset($month) ) $month = date("m");
	if ( ! isset($year) ) $year = date("Y");

	$monthview = generateMonthView_large($month, $year, $view_diary);

	$OUTPUT="
	<table width='750'>
	<tr>
		<td align=center valign=top>$monthview</td>
	</tr>
	</table>";

	return $OUTPUT;
}

// creates a little month calendar
function generateMonthView_large( $month, $year, $view_diary ) {
	global $_GET;
	
	$valid_date = mktime(0, 0, 0, $month, 1, $year);
	$month = date("m", $valid_date);
	$year = date("Y", $valid_date);

	$select_month = "<select name=month>";
	for ( $i = 1; $i <= 12; $i++ ) {
		if ( $month == $i )
			$sel = "selected";
		else
			$sel = "";

		$select_month .= "<option $sel value='$i'>".getMonthText($i)."</option>";
	}
	$select_month .= "</select>";

	$select_year = "<select name=year>";
	for ( $i = 1971; $i <= 2028; $i++ ) {
		if ( $year == $i )
			$sel = "selected";
		else
			$sel = "";

		$select_year .= "<option $sel value=$i>$i</option>";
	}
	$select_year .= "</select>";

	$OUTPUT = "
	<table width=600 cellspacing=0>
	<tr>
		<td align=center style='font-size: 14px; color: #ffffff; font-weight: bold;' colspan=3>
			<form method=post action='".SELF."'>
			<input type=hidden name=key value='month'>
			Goto: $select_month $select_year <input type=submit value='Go'>
			</form>
		</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
        <tr>
        	<td colspan=3 ".TMPL_calTimeStyle2.">
        	<table width=100%><tr>
        	<td width=30% align=center valign=middle>
        		<a href='".SELF."?key=month&month=".($month-1)."&year=$year&view_diary=$view_diary'>
        			<img src='left_day.gif' border=0>
			</a>
		</td>
		<td width=40% valign=middle align=center ".TMPL_calTimeStyleHeader.">
			<b>" . getMonthText($month) . " $year</b>
		</td>
		<td width=30% align=center valign=middle>
			<a href='".SELF."?key=month&month=".($month+1)."&year=$year&view_diary=$view_diary'>
				<img src='right_day.gif' border=0>
			</a>
		</td>
		</tr></table>
		</td>
	</tr>
	<tr>
		<td align=center ".TMPL_calLargeMonthBodyStyle." colspan=3>
		<table width='600' cellspacing=0>
		<tr>
			<td width=75 height=60>&nbsp;</td>
			<td width=75 height=60 align=center><h1>M</h1></td>
			<td width=75 height=60 align=center><h1>T</h1></td>
			<td width=75 height=60 align=center><h1>W</h1></td>
			<td width=75 height=60 align=center><h1>T</h1></td>
			<td width=75 height=60 align=center><h1>F</h1></td>
			<td width=75 height=60 align=center bgcolor=".TMPL_calFillSaturday."><h1>S</h1></td>
			<td width=75 height=60 align=center bgcolor=".TMPL_calFillSunday."><h1>S</h1></td>
		</tr>";

	// get the weekday number of the first of this month
	$first_wd = getWeekdayNum( 1, $month, $year );

	// the following code will generate the first entries on the calendar, which is for the previous month (if any)
	// and year of previous month
	if ( $month == 1 ) {
		$tmp_month = 12;
		$tmp_year = $year - 1;
	} else {
		$tmp_month = $month - 1;
		$tmp_year = $year;
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
	$selected_month = $_GET["month"];

	// create the previous month's entries
	$c_weeknum = getWeekNumber( $tmp_day, $tmp_month, $tmp_year );
	$selected_weeknum = getWeekNumber( $_GET["mday"], $_GET["month"], $_GET["year"] );

	$OUTPUT .= "<tr>";

	if ( $first_wd != 1 ) // only if there is a day in this week of previous month, print the week number
		$OUTPUT .= "<td width=75 height=60 ".TMPL_calLargeMonthWeekNumberStyle." align=center>$c_weeknum</td>";

	for ( $c_wd=1 ; $c_wd < $first_wd ; $c_wd++,$tmp_day++ ) {
		// fill differently for saturday and sunday (only when row wasn't already highlighted)
		if ( $c_wd == 6 )
			$dayfill = "bgcolor=".TMPL_calFillSaturday;
		else if ( $c_wd == 7 )
			$dayfill = "bgcolor=".TMPL_calFillSunday;
		else
			$dayfill = "";

		$OUTPUT .= "<td $dayfill width=75 style='border-bottom: 1px solid #000000;' height=60 align=center>
				<a id='calSmallMonthOMLink' href='".SELF."?mday=$tmp_day&month=$tmp_month&year=$tmp_year'>$tmp_day</a>
			</td>";
	}

	// start creating this month's entries
	$cm_days = getDaysInMonth( $month, $year );
	for ( $c_day=1 ; $c_day <= $cm_days ; $c_day++ ) {
		$c_weeknum = getWeekNumber( $c_day, $month, $year );

		if ( $c_wd == 1 ) { // start a new row (it's MONDAY!!!!!)
			$OUTPUT .= "<tr>";

			// attach the week number
			$OUTPUT .= "<td align=center width=75 height=60 ".TMPL_calLargeMonthWeekNumberStyle.">$c_weeknum</td>";
		}

		// change the fill color if it is 2day's date we are printing, or the selected date
  		if ( date("d") == $c_day && date("m") == $month && date("Y") == $year) { // 2day's date
			$dayfill = "bgcolor=".TMPL_calLargeMonthSelectedDay;
			$a_id = "calSmallMonthCMLinkToday";
		} else { // other dates
			// fill differently for saturday and sunday (only when the row has not already been colored)
			if ( $c_weeknum == getTodayWeekNumber() && $year == date("Y") && $month == date("m") )
				$dayfill = "bgcolor='".TMPL_calLargeMonthCurrentWeek."'";
			else if ( $c_wd == 6 )
				$dayfill = "bgcolor=".TMPL_calFillSaturday;
			else if ( $c_wd == 7 )
				$dayfill = "bgcolor=".TMPL_calFillSunday;
			else
				$dayfill = "";

			$a_id = "calSmallMonthCMLink";
		}

		$weeknum_first = getWeekNumber( 1, $month, $year );
		$weeknum_last = getWeekNumber( getDaysInMonth($month, $year), $month, $year );

		// create the borders for the days
		$b_top = "border-top: 1px solid #000000;";
		$b_bottom = "border-bottom: 1px solid #000000;";
		$b_right = "border-right: 1px solid #000000;";
		$b_left = "border-left: 1px solid #000000;";

		$day_style = "cursor: pointer; cursor: hand; $b_left $b_bottom";

		// first_week
                if ( $c_weeknum == $weeknum_first ) {
			$day_style .= "$b_top $b_left";
		}

		// sundays
		if ( $c_wd == 7 || getDaysInMonth($month, $year) == $c_day ) {
			$day_style .= "$b_right";
		}

		// check appointment, reminders and create the day information
		$checkdate_start = date("Y-m-d", mktime(0, 0, 0, $month, $c_day, $year));
		$checkdate_end = date("Y-m-d", mktime(0, 0, 0, $month, $c_day + 1, $year));

		db_conn("cubit");
		$sql = "SELECT * FROM diary_entries
			WHERE (DATE '$checkdate_start', DATE '$checkdate_end') OVERLAPS (time_start, time_end)";
		$rslt = db_exec($sql) or errDie("Error checking for appointments");

		$entrycount = pg_num_rows($rslt);
		if ( $entrycount > 0 ) {
			$count_entireday = 0;
			
			$day_information = "<table width=100%>";

			while ( $row = pg_fetch_array($rslt) ) {
				$appointment_url="diary-appointment.php?entry_id=$row[entry_id]&key=view";
				$onClick="popupOpen(\"$appointment_url\",\"appointment_popup\",\"scrollbars=yes,width=500,height=590\")";

				$day_information .= "
					<tr><td onClick='$onClick' nowrap>
						$row[title]
					</td></tr>";
			}
			
			$day_information .= "</table>";
		} else {
			$day_information = "&nbsp;";
		}

		$OUTPUT .= "
			<td width=75 height=60 $dayfill valign=top align=center style='$day_style'>
				<table width=100% height=100%>
				<tr><td valign=top align=right height=0% onClick='document.location.href=\"".SELF."?mday=$c_day&month=$month&year=$year&view_diary=$view_diary\";'>
					<font size=2 color='".TMPL_calLargeMonthOMLink_a."'><b>$c_day</b></font>
				</td></tr>
				<tr><td valign=top align=center height=100%>
					$day_information
				</td></tr>
				</table>
			</td>";

		if ( $c_wd == 7 ) // end the output
			$OUTPUT .= "</tr>";

		($c_wd == 7) ? $c_wd = 1 : $c_wd++;
	}

	// next month variables
	if ( $month == 12 ) {
		$tmp_month = 1;
		$tmp_year = $year + 1;
	} else {
		$tmp_month = $month + 1;
		$tmp_year = $year;
	}

	// finish with the next months entries
	for ( $c_day = 1 ; $c_wd <= 7 && $c_wd > 1 ; $c_wd++, $c_day++ ) {
		// fill differently for saturday and sunday
		if ( $c_wd == 6 )
            $dayfill = "bgcolor=".TMPL_calFillSaturday;
		else if ( $c_wd == 7 )
			$dayfill = "bgcolor=".TMPL_calFillSunday;
		else
			$dayfill = "";

		$OUTPUT .= "<td $dayfill width=75 height=60 align=center>
				<a id='calSmallMonthOMLink' href='".SELF."?mday=$c_day&month=$tmp_month&year=$tmp_year'>$c_day</a>
			</td>";
	}

	// finish the tables and return
	$OUTPUT .= "
			</tr>
		</table>

			</td>
		</tr>
	</table>";

	return $OUTPUT;
}

?>
