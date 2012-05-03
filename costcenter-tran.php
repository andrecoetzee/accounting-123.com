<?
#This program is copyright by Andre Coetzee email: ac@main.me
#and is licensed under the GPL v3
#
#
#
#
#Please add yourself to: http://www.accounting-123.com
#Developers, Software Vendors, Support, Accountants, Users
#
#
#The full software license can be found here:
#http://www.accounting-123.com/a.php?a=153/GPLv3
#
#
#
#
#
#
#
#
#
#
#

# get settings
require ("settings.php");
require ("core-settings.php");
require ("libs/ext.lib.php");

# decide what to do
if (isset ($_POST["key"])) {
	switch ($_POST["key"]) {
		case "confirm":
			$OUTPUT = confirm ($_POST);
			break;
		case "write":
			$OUTPUT = write ($_POST);
			break;
		default:
			$OUTPUT = enterdet ();
	}
} else {
	$OUTPUT = enterdet ();
}

# display output
require ("template.php");




# enter new data
function enterdet ()
{

//	$edate = ext_dateEntry("e");

	db_connect();
	# Query server
	$sql = "SELECT * FROM costcenters WHERE div = '".USER_DIV."' ORDER BY centername ASC";
	$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
	$ccenters = "";
	for($i = 0; $cc = pg_fetch_array($ccRslt); $i++){
		$ccenters .= "
				<tr class='".bg_class()."'>
					<td><input type='hidden' name='ccids[]' value='$cc[ccid]'>$cc[centername] ($cc[centercode]) </td>
					<td align='right'>".CUR." <input type='text' name='ccamts[]' size='8' value='0'></td>
				</tr>";
	}

	$typearr = array("dt" => "Income", "ct" => "Expense");
	$typesel = extlib_mksel("trantype", $typearr);

	$enter = "
			<h3>Allocate amount to Cost Centers</h3>
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='confirm'>
			<table ".TMPL_tblDflts." width='300'>
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>".REQ."Entry Type</td>
					<td>$typesel</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Date</td>
					<td nowrap>".mkDateSelect("date")."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Description</td>
					<td><textarea cols='18' rows='3' name='descrip'></textarea></td>
				</tr>
				".TBL_BR."
				<tr>
					<th>Cost Center</th>
					<th>Total Amount</th>
				</tr>
				$ccenters
				".TBL_BR."
				<tr>
					<td colspan='2' align='center'><input type='submit' value='Confirm &raquo;'></td>
				</tr>
			</table>
			</form>
			<p>
			<table ".TMPL_tblDflts." width='15%'>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='costcenter-add.php'>Add Cost Center</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='costcenter-view.php'>View Cost Center</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	return $enter;

}




# confirm new data
function confirm ($_POST)
{

	# get vars
	extract ($_POST);

	$edate = $date_year."-".$date_month."-".$date_day;

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($trantype, "string", 1, 255, "Invalid Transaction type switch.");
//	$edate = $v->chkDate($eday, $emon, $eyear, "Invalid date.");
	$v->isOk ($descrip, "string", 0, 255, "Invalid description.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	if(!checkdate($date_month, $date_day, $date_year)){
			$v->isOk ($edate, "num", 1, 1, "Invalid date.");
	}

	if(isset($ccids)){
		foreach($ccids as $key => $value){
			$v->isOk ($ccamts[$key], "float", 1, 20, "Invalid Cost center amount.");
		}
	}else{
		return "<li class='err'>There are no Cost centers found.</li>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>$e[msg]</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}



	db_connect();
	# Query server
	$ccenters = "";
	foreach($ccids as $key => $value){
		if($ccamts[$key] < 1)
			continue;

		$sql = "SELECT * FROM costcenters WHERE ccid = '$ccids[$key]'";
		$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
		$cc = pg_fetch_array ($ccRslt);

		$ccamts[$key] = sprint($ccamts[$key]);

		$ccenters .= "
					<tr class='".bg_class()."'>
						<td><input type='hidden' name='ccids[]' value='$cc[ccid]'>$cc[centername] ($cc[centercode]) </td>
						<td align='right'><input type='hidden' name='ccamts[]' value='$ccamts[$key]'>".CUR." $ccamts[$key]</td>
					</tr>";
	}

	$amount = sprint(array_sum($ccamts));

	$typearr = array("dt" => "Income", "ct" => "Expense");
	$typename = $typearr[$trantype];

	$confirm = "
			<h3>Allocate amount to Cost Centers</h3>
			<form action='".SELF."' method='POST'>
				<input type='hidden' name='key' value='write'>
				<input type='hidden' name='trantype' value='$trantype'>
				<input type='hidden' name='edate' value='$edate'>
				<input type='hidden' name='date_day' value='$date_day'>
				<input type='hidden' name='date_month' value='$date_month'>
				<input type='hidden' name='date_year' value='$date_year'>
				<input type='hidden' name='descrip' value='$descrip'>
			<table ".TMPL_tblDflts." width='300'>
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Entry Type</td>
					<td>$typename</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Date</td>
					<td>$edate</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Total Amount</td>
					<td>$amount</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>Description</td>
					<td>$descrip</td>
				</tr>
				".TBL_BR."
				<tr>
					<td colspan='2'>
						<table ".TMPL_tblDflts." width='100%'>
							<tr>
								<th>Cost Center</th>
								<th>Amount</th>
							</tr>
							$ccenters
						</table>
					</td>
				</tr>
				".TBL_BR."
				<tr>
					<td colspan='3' align='center'><input type='submit' value='Confirm &raquo;'></td>
				</tr>
			</table>
			</form>
			<p>
			<table ".TMPL_tblDflts." width=15%>
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='costcenter-add.php'>Add Cost Center</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='costcenter-view.php'>View Cost Center</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='main.php'>Main Menu</a></td>
				</tr>
			</table>";
	return $confirm;

}




# write new data
function write ($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($trantype, "string", 1, 255, "Invalid Transaction type switch.");
//	$edate = $v->chkrDate($edate, "Invalid date.");
	$v->isOk ($descrip, "string", 0, 255, "Invalid description.");
	$v->isOk ($date_day, "num", 1,2, "Invalid Date day.");
	$v->isOk ($date_month, "num", 1,2, "Invalid Date month.");
	$v->isOk ($date_year, "num", 1,4, "Invalid Date Year.");
	if(!checkdate($date_month, $date_day, $date_year)){
			$v->isOk ($edate, "num", 1, 1, "Invalid date.");
	}
	if(isset($ccids)){
		foreach($ccids as $key => $value){
			$v->isOk ($ccamts[$key], "float", 1, 20, "Invalid Cost Center amount.");
		}
	}else{
		return "<li class='err'>There are no Cost centers found.</li>";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirmCust = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirmCust .= "<li class='err'>$e[msg]</li>";
		}
		$confirmCust .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirmCust;
	}



	# vars
	$amount = array_sum($ccamts);

	$typearr = array("dt" => "Income", "ct" => "Expense");
	$typename = "Manual Entry";

//	$edate = ext_rdate($edate);

	$ccenters = "";
	foreach($ccids as $key => $value){
		db_connect();
		$sql = "SELECT * FROM costcenters WHERE ccid = '$ccids[$key]'";
		$ccRslt = db_exec ($sql) or errDie ("Unable to retrieve Cost centers from database.");
		$cc = pg_fetch_array ($ccRslt);

		db_conn(PRD_DB);
		$sql = "INSERT INTO cctran(ccid, trantype, typename, edate, description, amount, username, div) VALUES('$ccids[$key]', '$trantype', '$typename', '$edate', '$descrip', '$ccamts[$key]', '".USER_NAME."', '".USER_DIV."')";
		$insRslt = db_exec ($sql) or errDie ("Unable to retrieve insert Cost center amounts into database.");
	}

	// Layout
	$write = "
				<table ".TMPL_tblDflts." width='300'>
					<tr>
						<th><h3>Allocate amount to Cost Centers</h3></th>
					</tr>
					<tr class='".bg_class()."'>
						<td align='center'><b>( i )</b> Amount has been allocated to Cost Centers. <b>( i )</b></td>
					</tr>
				</table>
				<p>
				<table ".TMPL_tblDflts." width='15%'>
			       ".TBL_BR."
			        <tr>
			        	<th>Quick Links</th>
			        </tr>
					<tr class='".bg_class()."'>
						<td><a href='costcenter-rep.php'>View Cost Ledger</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='costcenter-add.php'>Add Cost Center</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='costcenter-view.php'>View Cost Center</a></td>
					</tr>
					<tr class='".bg_class()."'>
						<td><a href='main.php'>Main Menu</a></td>
					</tr>
				</table>";
	return $write;

}



?>