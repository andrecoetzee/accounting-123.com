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

require("../settings.php");
require("../core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
		case "viewtrans":
			$OUTPUT = viewtrans($_POST);
			break;
		default:
			$OUTPUT = view();
	}
} else {
	# Display default output
	$OUTPUT = view();
}

# get templete
require("../template.php");




# Default view
function view()
{

	core_connect();

 	$sql = "SELECT * FROM accounts WHERE div = '".USER_DIV."' ORDER BY accname ASC";
	$accRslt = db_exec($sql) or errDie("Could not retrieve Categories Information from the Database.",SELF);

	if(pg_numrows($accRslt) < 1){
		return "<li class='err'> There are no Accounts in Cubit.</li>";
	}

	$slctAcc = "
		<select name='accid'>
			<option value='0'>All Accounts</option>";
	while($acc = pg_fetch_array($accRslt)){
		$slctAcc .= "<option value='$acc[accid]'>$acc[accname]</option>";
	}
	$slctAcc .= "</select>";

	$prds = finMonList("prd", PRD_DB);

	//layout
	$view = "
		<center>
		<h3>Journal Entries Report</h3>
		<table cellpadding='5' width='80%'>
			<tr>
				<td width='60%'>
					<table ".TMPL_tblDflts." width='510'>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='viewtrans'>
						<input type='hidden' name='search' value='date'>
						<tr>
							<th colspan='2'>By Date Range</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td width='80%' align='center'>
								<table>
									<tr>
										<td>".mkDateSelect("from")."</td>
										<td>&nbsp;&nbsp;TO&nbsp;&nbsp;</td>
										<td>".mkDateSelect("to")."</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Select Period  $prds</td>
						</tr>
						<tr>
							<th colspan='2'>Select Account</th>
						</tr>
						<tr>
							<td colspan='2'>$slctAcc</td>
							<td valign='bottom'><input type='submit' value='Search &raquo;'></td>
						</tr>
					</form>
					".TBL_BR."
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table ".TMPL_tblDflts." width='370'>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='viewtrans'>
						<input type='hidden' name='search' value='refnum'>
						<tr>
							<th colspan='2'>By Journal number</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td width='80%' align='center'>
								From <input type='text' size='5' name='fromnum'> to <input type='text' size='5' name='tonum'>
							</td>
							<td rowspan='2' valign='bottom'><input type='submit' value='View All &raquo;'></td>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Select Period $prds</td>
						</tr>
					</form>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table ".TMPL_tblDflts." width='370'>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='viewtrans'>
						<input type='hidden' name='search' value='all'>
						<tr>
							<th colspan='2'>View All</th>
						</tr>
						<tr bgcolor='".bgcolorg()."'>
							<td>Select Period $prds</td>
	
							<td rowspan='2' valign='bottom'><input type='submit' value='View All &raquo;'></td>
						</tr>
					</form>
					</table>
				</td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts." width='25%'>
			".TBL_BR."
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='index-reports.php'>Financials</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	return $view;

}



# View Categories
function viewtrans($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new validate ();

	if(!isset($accid)) {
		$accid = 0;
	} else {
		$accid += 0;

	}

	#make sure we dont get previous period entries
	$act_year = getYearOfFinMon($prd);

	if($accid > 0) {
		$exw = " AND (date >= '$act_year-01-01' AND date <= '$act_year-12-31') AND (debit = '$accid' OR credit = '$accid') ";
	} else {
		$exw = " AND (date >= '$act_year-01-01' AND date <= '$act_year-12-31')";
	}

	# Search by date
	if($search == "date"){
		$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
		$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
		$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
		$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
		$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
		$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

		# mix dates
		$fromdate = $from_year."-".$from_month."-".$from_day;
		$todate = $to_year."-".$to_month."-".$to_day;

		if(!checkdate($from_month, $from_day, $from_year)){
				$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
		}
		if(!checkdate($to_month, $to_day, $to_year)){
				$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
		}

		$hide = "
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='search' value='$search'>
			<input type='hidden' name='fday' value='$from_day'>
			<input type='hidden' name='fmon' value='$from_month'>
			<input type='hidden' name='fyear' value='$from_year'>
			<input type='hidden' name='today' value='$to_day'>
			<input type='hidden' name='tomon' value='$to_month'>
			<input type='hidden' name='toyear' value='$to_year'>";

		# Create the Search SQL
		$search = "SELECT * FROM transect WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' $exw ORDER BY refnum ASC";

	}

	# Search by refnum
	if($search == "refnum"){
		$v->isOk ($fromnum, "num", 1, 20, "Invalid 'from' ref  number.");
		$v->isOk ($tonum, "num", 1, 20, "Invalid 'to' ref  number.");
		$hide = "
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='search' value='$search'>
			<input type='hidden' name='fromnum' value='$fromnum'>
			<input type='hidden' name='tonum' value='$tonum'>";

		# Create the Search SQL
		$search = "SELECT * FROM transect WHERE refnum >= $fromnum AND refnum <= $tonum AND div = '".USER_DIV."' $exw ORDER BY refnum ASC";
	}

	# View all
	if($search == "all"){
		$hide = "
			<input type='hidden' name='prd' value='$prd'>
			<input type='hidden' name='search' value='$search'>";

		# Create the Search SQL
		$search = "SELECT * FROM transect WHERE div = '".USER_DIV."' $exw ORDER BY refnum ASC";
	}

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		return $confirm.view();
	}




	// Layout
	$OUTPUT = "
		<center>
		<h3>Journal Entries Report</h3>
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td width='50%' align='left' colspan='4'><h3>".COMP_NAME."</h3></td>
				<td width='50%' align='right' colspan='4'><h3>".date("Y-m-d")."</h3></td>
			</tr>
			<tr>
				<th>Date</th>
				<th>System Date</th>
				<th>Debit</th>
				<th>Credit</th>
				<th>Ref No</th>
				<th>Amount</th>
				<th>Details</th>
				<th>User</th>
			</tr>";
	db_conn($prd);
	$tranRslt = db_exec ($search) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
	if (pg_numrows ($tranRslt) < 1) {
		return "<li> There are no Transactions in the selected Period.</li><br>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='index-reports.php'>Financials</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	}

	$total_amount = 0;
	# display all transaction
	while ($tran = pg_fetch_array ($tranRslt)){
		# get vars from tran as the are in db
		foreach ($tran as $key => $value) {
			$$key = $value;
		}

		# format date
		$date = explode("-", $date);
		$date = $date[2]."-".$date[1]."-".$date[0];
		$sdate = explode("-", $sdate);

		if(isset($sdate[2])) {
			$sdate = $sdate[2]."-".$sdate[1]."-".$sdate[0];
		} else {
			$sdate = $date;
		}

		/*
		# Get account names
		$deb = get("core","accname, topacc, accnum","accounts","accid",$debit);
		$debacc = pg_fetch_array($deb);
		$ct = get("core","accname, topacc,accnum","accounts","accid",$credit);
		$ctacc = pg_fetch_array($ct);
		*/

		$amount = sprint($amount);

		$OUTPUT .= "
			<tr bgcolor='".bgcolorg()."'>
				<td>$date</td>
				<td>$sdate</td>
				<td>$dtopacc/$daccnum&nbsp;&nbsp;&nbsp;$daccname</td>
				<td>$ctopacc/$caccnum&nbsp;&nbsp;&nbsp;$caccname</td>
				<td align='right'>$custom_refnum</td>
				<td align='right' nowrap>".CUR." $amount</td>
				<td>$details</td>
				<td>$author</td>
			</tr>";

		$total_amount += $amount;
	}

	$OUTPUT .= "
			".TBL_BR."
			<tr>
				<td align='center' colspan='10'>
					<form action='../xls/alltrans-xls.php' method='POST' name='form'>
						<input type='hidden' name='key' value='viewtrans'>
						<input type='hidden' name='accid' value='$accid'>
						$hide
						<input type='submit' name='xls' value='Export to spreadsheet'>
					</form>
				</td>
			</tr>
		</table>
		<table ".TMPL_tblDflts." width='25%'>
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='index-reports.php'>Financials</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='index-reports-journal.php'>Current Year Details General Ledger Reports</a></td>
			</tr>
			<tr bgcolor='".bgcolorg()."'>
				<td align='center'><a href='../main.php'>Main Menu</td>
			</tr>
		</table>";
	return $OUTPUT;

}



?>