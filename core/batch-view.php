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

require("settings.php");
require("core-settings.php");

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
require("template.php");



# Default view
function view()
{

	//layout
	$view = "
				<center>
				<h3>View Batch Entries</h3>
				<table cellpadding='5' width='80%'>
					<tr>
						<td align='center'>
							<table ".TMPL_tblDflts.">
							<form action='".SELF."' method='POST' name='form'>
								<input type='hidden' name='key' value='viewtrans'>
								<input type='hidden' name='search' value='date'>
								<tr>
									<th colspan='2'>By Date Range</th>
								</tr>
								<tr class='".bg_class()."'>
									<td align='center'>
										<table>
											<tr>
												<td>".mkDateSelect("from",date("Y"),date("m"),"01")."</td>
												<td>&nbsp;&nbsp;TO&nbsp;&nbsp;</td>
												<td>".mkDateSelect("to")."</td>
											</tr>
										</table>
									</td>
									</td>
									<td rowspan='2' valign='bottom'><input type='submit' value='Search'></td>
								</tr>
							</form>
							</table>
						</td>
					</tr>
					<tr>
						<td align='center'>
							<table ".TMPL_tblDflts.">
							<form action='".SELF."' method='POST' name='form'>
								<input type='hidden' name='key' value='viewtrans'>
								<input type='hidden' name='search' value='refnum'>
								<tr>
									<th colspan='2'>By Journal number</th>
								</tr>
								<tr class='".bg_class()."'>
									<td width='80%' align='center'>From <input type='text' size='5' name='fromnum'> to <input type='text' size='5' name='tonum'></td>
									<td rowspan='2' valign='bottom'><input type='submit' value='Search'></td>
								</tr>
							</form>
							</table>
						</td>
					</tr>
					<tr>
						<td align='center'>
							<table ".TMPL_tblDflts." width='100'>
							<form action='".SELF."' method='POST' name='form'>
								<input type='hidden' name='key' value='viewtrans'>
								<input type='hidden' name='search' value='all'>
								<tr>
									<th>View All</th>
								</tr>
								<tr class='".bg_class()."'>
									<td align='center'><input type='submit' value='View All'></td>
								</tr>
							</form>
							</table>
						</td>
					</tr>
				</table>
				<p>
				<table ".TMPL_tblDflts." width='25%'>
					<tr>
						<th>Quick Links</th>
					</tr>
					<tr class='datacell'>
						<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
					</tr>
					<tr class=datacell>
						<td align='center'><a href='trans-batch.php'>Add Journal Transactions to batch</td>
					</tr>
					<script>document.write(getQuicklinkSpecial());</script>
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
	$v = new  validate ();
		if($search == "date"){
			$v->isOk ($from_day, "num", 1,2, "Invalid from Date day.");
			$v->isOk ($from_month, "num", 1,2, "Invalid from Date month.");
			$v->isOk ($from_year, "num", 1,4, "Invalid from Date Year.");
			$v->isOk ($to_day, "num", 1,2, "Invalid to Date day.");
			$v->isOk ($to_month, "num", 1,2, "Invalid to Date month.");
			$v->isOk ($to_year, "num", 1,4, "Invalid to Date Year.");

			# Mix dates
			$fromdate = $from_year."-".$from_month."-".$from_day;
			$todate = $to_year."-".$to_month."-".$to_day;

			if(!checkdate($from_month, $from_day, $from_year)){
					$v->isOk ($fromdate, "num", 1, 1, "Invalid from date.");
			}
			if(!checkdate($to_month, $to_day, $to_year)){
					$v->isOk ($todate, "num", 1, 1, "Invalid to date.");
			}

			# create the Search SQL
			$search = "SELECT * FROM batch WHERE date >= '$fromdate' AND date <= '$todate' AND proc!='yes' AND div = '".USER_DIV."' ORDER BY refnum ASC";
		}

	# serach by refnum
	if($search == "refnum"){
		$v->isOk ($fromnum, "num", 1, 20, "Invalid 'from' ref  number.");
		$v->isOk ($tonum, "num", 1, 20, "Invalid 'to' ref  number.");

		# create the Search SQL
		$search = "SELECT * FROM batch WHERE refnum >= $fromnum AND refnum <= $tonum AND proc!='yes' AND div = '".USER_DIV."' ORDER BY refnum ASC";
	}

	# View All
	if($search == "all"){
		# create the Search SQL
		$search = "SELECT * FROM batch WHERE proc!='yes' AND div = '".USER_DIV."' ORDER BY refnum ASC";
	}


	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class=err>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	db_conn("core");

	// Set up table to display in
	$OUTPUT = "
				<center>
				<h3>Outstanding Batch Entries</h3>
				<form action='batch-procs.php' method='POST'>
				<table ".TMPL_tblDflts." width='90%'>
					<tr>
						<th>Date</th>
						<th>Debit</th>
						<th>Credit</th>
						<th>Reference No</th>
						<th>Amount</th>
						<th>Details</th>
						<th>Charge Vat</th>
						<th>Authorised By</th>
						<th colspan='3'>Options</th>
					</tr>";

	$tranRslt = db_exec ($search) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
	if (pg_numrows ($tranRslt) < 1) {
		return "
					<li> There are no Transactions in the selected Period.</li>
					<p>
					<table ".TMPL_tblDflts." width='25%'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='datacell'>
							<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
						</tr>
						<tr class=datacell>
							<td align='center'><a href='trans-batch.php'>Add Journal Transactions to batch</td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
	}

	# display all transaction
	while ($tran = pg_fetch_array ($tranRslt)){
		# Get vars from tran as the are in db
		extract($tran);

		# Format date
		$date = explode("-", $date);
		$date = $date[2]."-".$date[1]."-".$date[0];

		$chrgvat = ucwords($chrgvat);

		// Get account names
		$deb = get("core","accname, topacc, accnum","accounts","accid",$debit);
		$debacc = pg_fetch_array($deb);
		$ct = get("core","accname, topacc,accnum","accounts","accid",$credit);
		$ctacc = pg_fetch_array($ct);

		$OUTPUT .= "
						<tr class='".bg_class()."'>
							<td>$date</td>
							<td>$debacc[topacc]/$debacc[accnum]&nbsp;&nbsp;&nbsp;$debacc[accname]</td>
							<td>$ctacc[topacc]/$ctacc[accnum]&nbsp;&nbsp;&nbsp;$ctacc[accname]</td>
							<td>$refnum</td>
							<td>".CUR." $amount</td>
							<td>$details</td>
							<td>$chrgvat</td>
							<td>$author</td>
							<td><input type=checkbox name='bank[]' value='$batchid'></td>
							<td><a href='batch-edit.php?batchid=$batchid'>Edit</a></td>
							<td><a href='batch-rem.php?batchid=$batchid'>Remove</a></td>
						</tr>";
	}

	$OUTPUT .= "
						<tr>
							<td colspan='6'><br></td>
							<td colspan='2' align='right'><input type='submit' value='Process Selected' name='proc'></td>
							<td colspan='2' align='right'><input type='submit' value='Remove Selected' name='rem'></td>
						</tr>
					</form>
					</table>";
	$OUTPUT .= "
					</table>
					<p>
					<table ".TMPL_tblDflts." width='25%'>
						<tr>
							<th>Quick Links</th>
						</tr>
						<tr class='datacell'>
							<td align='center'><a href='../reporting/index-reports.php'>Financials</a></td>
						</tr>
						<tr class='datacell'>
							<td align='center'><a href='trans-batch.php'>Add Journal Transactions to batch</td>
						</tr>
						<script>document.write(getQuicklinkSpecial());</script>
					</table>";
	return $OUTPUT;

}


?>