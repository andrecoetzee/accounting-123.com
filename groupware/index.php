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
require_once("../settings.php");

require ("tree.php");

$get = "";
$i = 0;
foreach ($_GET as $key=>$value) {
	if ($key == "script") {
		continue;
	}

	if ($i > 0) {
		$get .= "&";
	}
	$i++;

	$get .= $value;
}

if (!isset($_GET["script"])) {
	$script = "<iframe class='diary_frameset' src='messages.php?key=frameset&fid=0'>";
} else {
	$script = "<iframe class='diary_frameset' src='$_GET[script]?$get'></iframe>";
}

$OUTPUT = "
<table id='toolbar'>
<tr>
	<td align='right'>";

$OUTPUT .= "
	<script src='cubitmenu.js'></script>
	<table cellpadding='2' cellpadding='2'>
	<tr>
		<td align=left valign=center>
			<div id=\"cubit_menu\" style=\"height=28;\"></div>
		</td>
	</tr>
	</table>
	
	<script>
		var cubitMenu = [ 
			[null, 'Today', 'script=today.php', 'ajax', null],
			[null, 'Email', null, null, null,
				[null, 'Check Mail', 'script=getmessages.php', 'ajax', null],
				[null, 'Compose Mail', 'script=newmessage.php', 'ajax', null],
				[null, 'Inbox', 'script=messages.php&key=frameset&fid=2', 'ajax', null],
				[null, 'New Account', 'script=newaccount.php', 'ajax', null],
				[null, 'View Accounts', 'script=accounts.php', 'ajax', null],
			],
			[null, 'Contacts', null, null, null,
				[null, 'Add New Contact', 'script=new_con.php', 'ajax', null],
				[null, 'View Contacts', 'script=list_cons.php', 'ajax', null],
			],
			[null, 'Diary', null, null, null,
				[null, 'Day View', 'script=diary-index.php', 'ajax', null],
				[null, 'Monthly View', 'script=diary-index.php&key=month', 'ajax', null],
				[null, 'Diary Privileges', 'script=diary-privileges.php', 'ajax', null],
				[null, 'View Other Diary', 'script=diary-index.php&key=viewother', 'ajax', null],
			],
			[null, 'Messages', null, null, null,
				[null, 'New Message', 'script=req_gen.php', 'ajax', null],
				[null, 'View Messages', 'script=view_req.php', 'ajax', null],
			],
			[null, 'Documents', null, null, null,
				[null, 'Add New Document', 'script=document_save.php', 'ajax', null],
				[null, 'View Documents', 'script=document_view.php', 'ajax', null],
				cubitmenuSplit,
				[null, 'Add Document Type', 'script=doc_type_save.php', 'ajax', null],
				[null, 'View Document Types', 'script=doc_type_view.php', 'ajax', null],
				cubitmenuSplit,
				[null, 'Document Movement Report', 'script=document_movement.php', 'ajax', null],
			],
			[null, 'Todo', 'script=todo_sub_save.php', 'ajax', null],
		];

		cubitmenuDraw (cubitMenu, 'cubit_menu', 'hv', cubitmenuObject, 'top');
	</script>";
	
$OUTPUT .= "
	</td>
</tr>
</table>";


$OUTPUT .= "
<table id='left_container'>
<tr>
	<td style='height: 40px'></td>
</tr>
<tr>
	<td id='tree'>
		<table height='100%'>
		<tr>
			<td height='100%' valign='top'>$tree</td>
		</tr>
		<tr>
			<td align='bottom'><div id='diary_small'>".showMonthView()."</div></td>
		</tr>
		</table>
	</td>
</tr>
</table>

<table ".TMPL_tblDflts." id='main_body'>
<tr>
	<td colspan='2' style='height: 32px'></td>
</tr>
<tr>
	<td style='width: 193px'></td>
	<td>
		<div id='content'>
			$script
		</div>
	</td>
</tr>
</table>";

require("gw-tmpl.php");

function showMonthView() {
	global $_GET;

	$fields = array(
		"sday"		=> date("d"),
		"smonth"	=> date("m"),
		"syear"		=> date("Y"),
		"month"		=> date("m"),
		"year"		=> date("Y")
	);

	foreach ($fields as $fname => $dflt) {
		if (!isset($_GET[$fname])) {
			$_GET[$fname] = $dflt;
		}
	}

	return generateMonthView_small_mail(USER_NAME);
}

// creates a little month calendar
function generateMonthView_small_mail($view_diary) {
	global $_GET;
	extract($_GET);

	$pyear = extractYear(mkdatet($year - 1, $month, 1));
	$pmonth = extractMonth(mkdatet($year, $month - 1, 1));
	$nyear = extractYear(mkdatet($year + 1, $month, 1));
	$nmonth = extractMonth(mkdatet($year, $month + 1, 1));

	$OUTPUT="
		<table width=190 cellspacing=0>
			<tr>
				<td height=20 align=center ".TMPL_calSmallMonthTitleStyle.">
					<a href='".SELF."?month=$month&year=$pyear'><img border='0' src='left_year.gif'></a>
					<a href='".SELF."?month=$pmonth&year=$year'><img border='0' src='left_month.gif'></a>
					<b><a class='month_text' href='javascript:ajaxLink(\"iframe.php\", \"script=diary-index.php&key=month&month=$month&year=$year\");'>" . getMonthName($month) . " $year</a></b>
					<a href='".SELF."?month=$nmonth&year=$year'><img border='0' src='right_month.gif'></a>
					<a href='".SELF."?month=$month&year=$nyear'><img border='0' src='right_year.gif'></a>
				</td>
			</tr>
			<tr>
				<td align=center ".TMPL_calSmallMonthBodyStyle." colspan='5'>";

	// generate the titles of the weekdays
	$OUTPUT.="
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
    $c_weeknum = getWeekNumber($tmp_day,$tmp_month,$tmp_year);
	$selected_weeknum = getWeekNumber($sday,$smonth,$syear);

	// if today's week number = the current generated week's number, hightlight the row, as so with the selected week,
	if ( ( $c_weeknum == getTodayWeekNumber() && $tmp_year == date("Y") && $month == date("m"))
		|| ( getTodayWeekNumber() == 0 && $month == date("m") && $year == date("Y") ) ) { // today's week
		$OUTPUT.="<tr bgcolor='".TMPL_calSmallMonthCurrentWeek."'>";
		$ROW_COLORED=1;
	} else if ( ($month == $smonth && $c_weeknum == $selected_weeknum)
			|| ( $c_weeknum==52 && $selected_weeknum==0 )) { // selected week, the last check is for the first week in jan
		$OUTPUT.="<tr bgcolor='".TMPL_calSmallMonthSelectedWeek."'>";
		$ROW_COLORED=1;
	} else {  // other dates
		$OUTPUT.="<tr>";
		$ROW_COLORED=0;
	}

	if ( $first_wd!=1 ) // only if there is a day in this week of previous month, print the week number
		$OUTPUT.="<td width=23 ".TMPL_calSmallMonthWeekNumberStyle." align=center>$c_weeknum</td>";

	for ( $c_wd=1 ; $c_wd < $first_wd ; $c_wd++,$tmp_day++ ) {
		// fill differently for saturday and sunday (only when row wasn't already highlighted)
		if ( $c_wd == 6 && ! $ROW_COLORED)
			$dayfill="bgcolor=".TMPL_calFillSaturday;
		else if ( $c_wd == 7 )
			$dayfill="bgcolor=".TMPL_calFillSunday;
		else
			$dayfill="";

//		** 2006-05-08 **
// 		$OUTPUT.="<td $dayfill width=23 align=center>
// 							<a id='calSmallMonthOMLink' href='#' onClick='parent.rightframe.document.location.href=\"diary-index.php?mday=$tmp_day&month=$tmp_month&year=$tmp_year\"'>$tmp_day</a>
// 						</td>";

		$OUTPUT .= "<td $dayfill width='23' align='center'>
			<a id='calSmallMonthOMLink' href='javascript:ajaxLink(\"iframe.php\", \"script=diary-index.php&mday=$tmp_day&month=$tmp_month&year=$tmp_year\");'>$tmp_day</a></td>";
	}

	// start creating this month's entries
	$cm_days=getDaysInMonth($month,$year);
	for ( $c_day=1 ; $c_day <= $cm_days ; $c_day++ ) {
		$c_weeknum = getWeekNumber($c_day,$month,$year);

		if ( $c_wd == 1 ) { // start a new row (it's MONDAY!!!!!)
			// if today's week number = the current generated week's number, hightlight the row, as so with the selected week
			if ( $c_weeknum == getTodayWeekNumber() && $year == date("Y") && $month == date("m")) { // today's week
				$OUTPUT.="<tr bgcolor='".TMPL_calSmallMonthCurrentWeek."'>";
				$ROW_COLORED=1;
			} else if ( ($month == $smonth && $c_weeknum == $selected_weeknum) ) { // selected week
				$OUTPUT.="<tr bgcolor='".TMPL_calSmallMonthSelectedWeek."'>";
				$ROW_COLORED=1;
			} else {  // other dates
				$OUTPUT.="<tr>";
				$ROW_COLORED=0;
			}

			// attach the week number
			$OUTPUT.="<td align=center width=23 ".TMPL_calSmallMonthWeekNumberStyle.">$c_weeknum</td>";
		}

		// change the fill color if it it 2day's date we are printing, or the selected date
		if ( $c_day == $sday
				&& $month == $smonth
				&& $year == $syear) { // selected date
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

//		** 2006-05-08 **
// 		$OUTPUT.="<td width=23 $dayfill align=center>
// 							<a id='$a_id' href='#' onClick='parent.rightframe.document.location.href=\"diary-index.php?mday=$c_day&month=$month&year=$year&view_diary=$view_diary\"'>$c_day</a>
// 						</td>";

		$OUTPUT .= "<td width='23' $dayfill align='center'>
			<a id='$a_id' href='javascript:ajaxLink(\"iframe.php\", \"script=diary-index.php&mday=$c_day&month=$month&year=$year\");'>$c_day</a></td>";

		if ( $c_wd == 7 ) // end the output
			$OUTPUT.="</tr>";

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

//		** 2006-05-08 **
// 		$OUTPUT.="<td $dayfill width=23 align=center>
// 							<a id='calSmallMonthOMLink' href='#' inClick='parent.rightframe.document.location.href=\"diary-index.php?mday=$c_day&month=$tmp_month&year=$tmp_year\"'>$c_day</a>
// 						</td>";

		$OUTPUT .= "<td $dayfill width='23' align='center'>
			<a id='calSmallMonthOMLink' href='javascript:ajaxLink(\"iframe.php?script=diary-index.php\", \"mday=$c_day&month=$tmp_month&year=$tmp_year\");'>$c_day</a>";
	}


	// finish the tables and return
	$OUTPUT.="
				</tr>
			</table>

				</td>
			</tr>
		</table>";

	return $OUTPUT;
}



?>
