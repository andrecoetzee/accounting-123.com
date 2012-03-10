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

define ("cdsMaxShowUserDiaries",5);

// define the sql statements
// the following are the sql statements to retrieve the appropriate diary_entries
define ( "sql_today" , "SELECT entry_id,time_entireday,
		EXTRACT(hour from time_start) AS start_h, EXTRACT(minute from time_start) AS start_m,
		EXTRACT(hour from time_end) AS end_h, EXTRACT(minute from time_end) AS end_m
	FROM diary_entries
	WHERE '$spec_date'=date_trunc('day',time_start)
		AND repetitions='N' " );

define ( "sql_daily" , "SELECT entry_id,time_entireday,
		EXTRACT(hour from time_start) AS start_h, EXTRACT(minute from time_start) AS start_m,
		EXTRACT(hour from time_end) AS end_h, EXTRACT(minute from time_end) AS end_m
	FROM diary_entries
	WHERE to_timestamp('$spec_date','YYYY-mm-DD') >= date_trunc('day',time_start)
		AND repetitions='D'

		AND ( rep_forever='1'
			OR to_timestamp('$spec_date','YYYY-mm-DD') <= to_timestamp(rep_date,'YYYY-mm-DD')
		)" );

define ( "sql_weekly" , "SELECT entry_id,time_entireday,
		EXTRACT(hour from time_start) AS start_h, EXTRACT(minute from time_start) AS start_m,
		EXTRACT(hour from time_end) AS end_h, EXTRACT(minute from time_end) AS end_m
	FROM diary_entries
	WHERE EXTRACT( dow from to_date('$spec_date','YYYY-mm-DD') ) = EXTRACT( dow from time_start )
		AND to_timestamp('$spec_date','YYYY-mm-DD') >= date_trunc('day',time_start)
		AND repetitions='W'

		AND ( rep_forever='1'
			OR to_timestamp('$spec_date','YYYY-mm-DD') <= to_timestamp(rep_date,'YYYY-mm-DD')
		)" );

define ( "sql_monthly" , "SELECT entry_id,time_entireday,
		EXTRACT(hour from time_start) AS start_h, EXTRACT(minute from time_start) AS start_m,
		EXTRACT(hour from time_end) AS end_h, EXTRACT(minute from time_end) AS end_m
	FROM diary_entries
	WHERE EXTRACT( day from to_date('$spec_date','YYYY-mm-DD') ) = EXTRACT( day from time_start )
		AND to_timestamp('$spec_date','YYYY-mm-DD') >= date_trunc('day',time_start)
		AND repetitions='M'

		AND ( rep_forever='1'
			OR to_timestamp('$spec_date','YYYY-mm-DD') <= to_timestamp(rep_date,'YYYY-mm-DD')
		)" );

define ( "sql_yearly" , "SELECT entry_id,
		EXTRACT(hour from time_start) AS start_h, EXTRACT(minute from time_start) AS start_m,
		EXTRACT(hour from time_end) AS end_h, EXTRACT(minute from time_end) AS end_m
	FROM diary_entries
	WHERE EXTRACT( day from to_date('$spec_date','YYYY-mm-DD') ) = EXTRACT( day from time_start )
		AND EXTRACT( month from to_date('$spec_date','YYYY-mm-DD') ) = EXTRACT( month from time_start )
		AND to_timestamp('$spec_date','YYYY-mm-DD') >= date_trunc('day',time_start)
		AND repetitions='Y'

		AND ( rep_forever='1'
			OR to_timestamp('$spec_date','YYYY-mm-DD') <= to_timestamp(rep_date,'YYYY-mm-DD')
		)" );

// the day schedule matrix object
class clsDaySchedule {
 	// what date the schedule should be computed for
	var $user_diaries;
	var $entry;

	var $matrix;
	var $entries_entireday;

	// constructor
	function clsDaySchedule() {
		global $HTTP_SESSION_VARS,$HTTP_GET_VARS;

		$this->user_diaries = Array();
		$this->entry=& new clsDayEntry;
	}

	// function that adds a person's diary entries to the list
	function addDiary($username) {
		// only add if not at max user count
        if ( count($this->user_diaries) >= cdsMaxShowUserDiaries )
            return;

		// only add if user exists
		db_conn("cubit");
		$rslt=db_exec("SELECT * FROM users WHERE username='$username'");

		if ( pg_num_rows($rslt) > 0 ) {
			$this->user_diaries[]=$username;
		}
	} // end of function addDiary

	// function that computes the whole matrix
	function generateScheduleMatrix() {
		if ( count($this->user_diaries) == 0 ) return;

		// go through each user
		foreach ($this->user_diaries as $arruser => $username) {
			// read all diary entries for this user for this day, and add the data to an day entry object, and generate it's pos on the cal
			// minute and hour is extracted from start and end time, because that is all we need
			$this->insertIntoMatrix(sql_today . " AND username='$username' ORDER BY time_start");
			$this->insertIntoMatrix(sql_daily . " AND username='$username' ORDER BY time_start");
			$this->insertIntoMatrix(sql_weekly . " AND username='$username' ORDER BY time_start");
			$this->insertIntoMatrix(sql_monthly . " AND username='$username' ORDER BY time_start");
			$this->insertIntoMatrix(sql_yearly . " AND username='$username' ORDER BY time_start");
		}

		// if no data was filled, at least add 1, so we dont get NO matrix at all, and rather a blank one
		if ( count($this->matrix) == 0 )
			$this->matrix[0][0]="0";

		// fill the blank space of the matrix with default data
		for ( $col=0 ; $col < count($this->matrix) ; $col++ ) {
			for ( $row=0 ; $row < 32 ; $row++ ) {
				if ( empty($this->matrix[$col][$row]) )
					$this->matrix[$col][$row] = "0";
			}
		}
	} // end of function generateScheduleMatrix

	// function that takes
	function insertIntoMatrix($sql) {
		if ( empty($sql) )
			return;

		$rslt=db_exec($sql);

		while ( $sqlrow = pg_fetch_array($rslt) ) {
			$this->entry->setVars("$sqlrow[start_h]:$sqlrow[start_m]","$sqlrow[end_h]:$sqlrow[end_m]",0,0,0,0);

			if (!isset($sqlrow["time_entireday"])) {
				$sqlrow["time_entireday"] = 0;
			}

			// do entire day entry, or normal entry
			if ( isset($sqlrow["time_entireday"]) && $sqlrow['time_entireday'] == '0') {
				$fr = $this->entry->getFirstRow(); // get the first row this entry will use
				$nr = $this->entry->getNumRows(); // get the number of rows this entry will use
				$lr = $nr + $fr - 1; // get the row number of the last row this entry will use

				// look for open space, if open space was found, the $col var will have the column, and the row will be $fr variable
				// go from column 0 to max
				for ( $col=0 ; $col < count($this->matrix) ; $col++ ) {
					// start at $fr and read until at $lr encountered, if a non blank was found, set $row=-1 and break
					for ( $row=$fr ; $row <= $lr && $row <= 32 ; $row++ ) {
						if ( ! empty($this->matrix[$col][$row]) ) {
							$row = -1;
							break;
						}
					}

					// if $lr is smaller than $row, that means enough space was found, and we can break
					if  ( $row >= $lr )
						break;
				}

				// ok, now that enough space was found, let's fill it up with the entry_id and type of whatever entry we are busy with
				// the first row of the entry is type title and the second type body, the last fields are the title and font color,
				// and body and font color respectively
				$this->matrix[$col][$fr] = $sqlrow[0] . "|t|" . $this->entry->colors;
				for ( $i=$fr + 1 ; $i<=$lr ; $i++ )
					$this->matrix[$col][$i] = $sqlrow[0] . "|b|" . $this->entry->colors;
			} else { // this is an entire day entry... easy
				$this->entries_entireday[] = $sqlrow[0] . "|b|" . $this->entry->colors;
			}
		}
	} // end of function insert into matrix

	// generates the table data row by row, and returns this data (without the <table></table> tags)
	function generateScheduleData() {
		global $HTTP_GET_VARS;

		if ( ! isset($this->matrix) )
			return;

		// set the requested date variables
		$mday = $HTTP_GET_VARS["mday"];
		$month = $HTTP_GET_VARS["month"];
		$year = $HTTP_GET_VARS["year"];

		$data="";

		// create the entire day entries
		if ( isset($this->entries_entireday) ) {
			foreach( $this->entries_entireday as $arr => $arrval ) {
				list( $entry_id , $entry_type , $chead , $cfhead , $cbody , $cfbody ) = explode( "|" , $arrval );

				// get the data from dbase
				$rslt=db_exec("SELECT title FROM diary_entries WHERE entry_id='$entry_id'");
				if ( ! ($sqlrow = pg_fetch_array($rslt)) ) die("Error reading entry. Please contact Cubit.");
				$entry_title = $sqlrow["title"];

				// create the url
				$appointment_url="diary-appointment.php?entry_id=$entry_id&key=view";
				$onClick="onClick='popupOpen(\"$appointment_url\",\"appointment_popup\",\"scrollbars=yes,width=600,height=590\")'";

				// print the entry
				$data.="
				<tr>
					<td $onClick ".TMPL_calEntryStyleEntire." align='center' colspan=100 bgcolor='$cbody'>
						<font color='$cfbody' size=3><b>$entry_title</b></font>
					</td>
				</tr>";
			}
		}

		// search the matrix for data. when found, check the type, and fill it up
		for ( $row = 0 ; $row < 32 ; $row++ ) {
			// start a new row
			$data.="<tr>";

			// generate the current row's time in AM/PM format, at every second row (not printing half hours, but only hours)
			$time = ($row / 2) + 6;
			if ( $row % 2 == 0 ) {
				if ( floor($time) > 12 )
					$aptime=floor($time)-12 . ":00 PM";
				else
					$aptime=floor($time) . ":00 AM";

				$data.="<td align=center width=55 height=60 rowspan=2 ".TMPL_calTimeStyle1.">$aptime</td>";

				$entry_style=TMPL_calEntryStyle1; // the style of the diary blank entry
				$click_time=floor($time).":00";
			} else {
				$entry_style=TMPL_calEntryStyle2; // the style of the diary blank entry
				$click_time=floor($time).":30";
			}

			for ( $col = 0 ; $col < count($this->matrix) ; $col++ ) {
				if ( $this->matrix[$col][$row] == "0" ) { // default data
					// create the url the field will link to
					$appointment_url="diary-appointment.php?ap_day=$mday&ap_month=$month&ap_year=$year&ap_start_time=$click_time&ap_diaryowner=".$this->user_diaries[0];
					$onClick="onClick='popupOpen(\"$appointment_url\",\"appointment_popup\",\"scrollbars=yes,width=600,height=590\")'";

					// check if this is a first or second row of hour (:00 or :30)
					$data.="<td $onClick height=30 $entry_style> &nbsp; </td>";
				} else {
					// check if entry is a title or body, and fill appropriatly
					list( $entry_id , $entry_type , $chead , $cfhead , $cbody , $cfbody ) = explode( "|" , $this->matrix[$col][$row] );

					// create the url the field will link to
					$appointment_url="diary-appointment.php?entry_id=$entry_id&key=view";
					$onClick="onClick='popupOpen(\"$appointment_url\",\"appointment_popup\",\"scrollbars=yes,width=600,height=590\")'";

					// get the data
					$rslt=db_exec("SELECT title, EXTRACT(hour from time_start) AS shour, EXTRACT(minute from time_start) AS smin,
											EXTRACT(hour from time_end) AS ehour, EXTRACT(minute from time_end) AS emin
										FROM diary_entries WHERE entry_id='$entry_id'");

					if ( ! ($sqlrow = pg_fetch_array($rslt)) ) die("Error reading entry. Please contact Cubit.");

					$entry_title = $sqlrow["title"];

					// pad the time so it doesn't show as 1:0 or 10:3 etc...
					$entry_time = str_pad($sqlrow["shour"], 2, "0", STR_PAD_LEFT)
							.":". str_pad($sqlrow["smin"], 2, "0", STR_PAD_LEFT)
							." - ". str_pad($sqlrow["ehour"], 2, 0, STR_PAD_LEFT)
							.":". str_pad($sqlrow["emin"], 2, 0, STR_PAD_LEFT);

					switch ( $entry_type ) {
						case "t": // title
							$data.="<td height=30 valign=top $onClick>
											<table height='100%' width='100%' cellpadding=0 cellspacing=0 border=0>
												<tr><td $onClick ".TMPL_calEntryStyleTitle." bgcolor='$chead'><font color='$cfhead' size=2>$entry_time</font></td></tr>
												<tr><td $onClick ".TMPL_calEntryStyleBody." bgcolor='$cbody'><font color='$cfbody' size=2>$entry_title</font></td></tr>
											</table>
										</td> ";
							break;
						case "b": // body
						default:
							$data.="<td $onClick height=30 ".TMPL_calEntryStyleBody." bgcolor='$cbody'> &nbsp; </td> ";
					}
				}
			}

			// end the row
			$data.="</tr>";
		}

		return $data;
	} // end of function generateScheduleData

// end class bracket
}

?>
