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

define ("cdeDefaultHead","#114488");
define ("cdeDefaultHeadFont","#FFFFFF");
define ("cdeDefaultBody","#88BBFF");
define ("cdeDefaultBodyFont","#000000");

// the day entry object
class clsDayEntry {
	var $start_time_h;
	var $start_time_m;
	var $end_time_h;
	var $end_time_m;

	// this variable store the colors of the entry in the form ( head | head_font | body | body_font )
	var $colors;

	// constructor
	function clsDayEntry() {
		$this->setVars( 0 , 0 , 0 , 0 , 0 , 0 );
	}

	// function that sets the variables
	function setVars($stime, $etime, $chead, $cfhead, $cbody, $cfbody) {
		// set default values
		if ( $stime == 0 ) $stime = "6:00";
		if ( $etime == 0 ) $etime = "21:30";
		if ( $chead == 0 ) $chead = cdeDefaultHead;
		if ( $cfhead == 0 ) $cfhead = cdeDefaultHeadFont;
		if ( $cbody == 0 ) $cbody = cdeDefaultBody;
		if ( $cfbody == 0 ) $cfbody = cdeDefaultBodyFont;

		// assign the time variables
		list ( $this->start_time_h, $this->start_time_m ) = explode(":", $stime);
		list ( $this->end_time_h, $this->end_time_m ) = explode(":", $etime);

		// create the colors attached with a pipe (helps with attaching it to the matrix
		$this->colors = $chead . "|" . $cfhead . "|" . $cbody . "|" . $cfbody;
	}

	// function that returns the first row, where 9:00 is 0
	function getFirstRow() {
		// hours count as 2, minutes as 1, starts with one -> 3 2 1 compute!!!!!
		$row = ($this->start_time_h - 6) * 2;
		$row += ($this->start_time_m) / 30;

		return $row;
	}

	// function that returns the number of rows of the current entry
	function getNumRows() {
		// hours count as 2, minutes as 1 -> 3 2 1 compute!!!!!
		$count = ($this->end_time_h - $this->start_time_h) * 2;
		$count += ($this->end_time_m - $this->start_time_m) / 30;

		return $count;
	}
}

?>
