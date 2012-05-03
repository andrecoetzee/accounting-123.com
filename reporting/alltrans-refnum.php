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
		case "xls":
			$OUTPUT = xls($_POST);
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

	//layout
	$view = "
		<center>
		<h3>Detailed General ledger Report</h3>
		<table cellpadding='5' width='80%'>
		<tr>
			<td width='60%' align='center'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='viewtrans'>
					<input type='hidden' name='search' value='date'>
				<table ".TMPL_tblDflts." width='470'>
					<tr>
						<td class='err' colspan='2'>All transactions from selected period posted between the specified dates.</td>
					</tr>
					<tr>
						<th colspan='2'>By Date Range</th>
					</tr>
					<tr class='".bg_class()."'>
						<td width='95%' align='center'>
							<table>
								<tr>
									<td nowrap='t'>".mkDateSelect("from",date("Y"),date("m"),"01")."</td>
									<td>&nbsp;&nbsp;TO&nbsp;&nbsp;</td>
									<td nowrap='t'>".mkDateSelect("to")."</td>
								</tr>
							</table>
						</td>
						<td rowspan='2' valign='bottom'><input type='submit' value='Search &raquo;'></td>
					</tr>
					<!--<tr class='".bg_class()."'>
						<td>Select Period: ".finMonList("prd", PRD_DB)."</td>
					</tr>-->
				</table>
				</form>
			</td>
		</tr>
		<tr>
			<td align='center'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='viewtrans'>
					<input type='hidden' name='search' value='refnum'>
				<table ".TMPL_tblDflts." width='370'>
					<tr>
						<td class='err' colspan='2'>All transactions from selected period with reference numbers within the specified range.</td>
					</tr>
					<tr>
						<th colspan='2'>By Journal number</th>
					</tr>
					<tr class='".bg_class()."'>
						<td width='80%' align='center'>
							From <input type='text' size='5' name='fromnum'>
							to <input type='text' size='5' name='tonum'>
						</td>
						<td rowspan='2' valign='bottom'><input type='submit' value='Search &raquo;'></td>
					</tr>
					<tr class='".bg_class()."'>
						<td>Select Period: ".finMonList("prd", PRD_DB)."</td>
					</tr>
				</table>
				</form>
			</td>
		</tr>
		<tr>
			<td align='center'>
				<form action='".SELF."' method='POST' name='form'>
					<input type='hidden' name='key' value='viewtrans'>
					<input type='hidden' name='search' value='all'>
				<table ".TMPL_tblDflts." width='370'>
					<tr>
						<td class='err' colspan='2'>All transactions from selected period.</td>
					</tr>
					<tr>
						<th colspan='2'>View All</th>
					</tr>
					<tr class='bg-even'>
						<td>Select Period: ".finMonList("prd", PRD_DB)."</td>
						<td rowspan='2' valign='bottom'><input type='submit' value='View All &raquo;'></td>
					</tr>
				</table>
				</form>
			</td>
		</tr>
	</table>"
	.mkQuickLinks(
		ql("index-reports.php", "Financials"),
		ql("index-reports-journal.php", "Current Year Details General Ledger Reports"),
		ql("../core/acc-new2.php", "Add New Account")
	);
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

		# Create the Search SQL
		$search = "SELECT DISTINCT refnum FROM transect WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."' ORDER BY refnum ASC";

		$prd = 1;
		if ($to_month < $from_month) {
			for ($i = $from_month; $i <= 12; ++$i) {
				$i += 0;
				$prds[] = "SELECT DISTINCT '$i' AS prd, refnum FROM \"$i\".transect WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."'";
			}
			for ($i = 1; $i <= $to_month; ++$i) {
				$i += 0;
				$prds[] = "SELECT DISTINCT '$i' AS prd, refnum FROM \"$i\".transect WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."'";
			}
		} else {
			for ($i = $from_month; $i <= $to_month; ++$i) {
				$i += 0;
				$prds[] = "SELECT DISTINCT '$i' AS prd, refnum FROM \"$i\".transect WHERE date >= '$fromdate' AND date <= '$todate' AND div = '".USER_DIV."'";
			}
		}

		$search = implode(" UNION ", $prds) . "  ORDER BY refnum ASC";
	}

	# Search by refnum
	if($search == "refnum"){
		$v->isOk ($fromnum, "num", 1, 20, "Invalid 'from' ref  number.");
		$v->isOk ($tonum, "num", 1, 20, "Invalid 'to' ref  number.");

		# Create the Search SQL
		$search = "SELECT DISTINCT '$prd' AS prd, refnum FROM transect WHERE refnum >= $fromnum AND refnum <= $tonum AND div = '".USER_DIV."' ORDER BY refnum ASC";
	}

	# View all
	if($search == "all"){
		# Create the Search SQL
		$search = "SELECT DISTINCT '$prd' AS prd, refnum FROM transect WHERE div = '".USER_DIV."' ORDER BY refnum ASC";
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



	# $search = "SELECT DISTINCT refnum FROM transect ORDER by refnum ASC";

	// Layout
	$OUTPUT = "
		<center>
		<h3>Detailed General ledger Report</h3>
		<table ".TMPL_tblDflts." width='100%'>
			<tr>
				<td width='50%' align='left' colspan='4'><h3>".COMP_NAME."</h3></td>
				<td width='50%' align='right' colspan='7'><h3>".date("Y-m-d")."</h3></td>
			</tr>
			<tr>
				<th colspan='5'>Debit</th>
				<th>&nbsp;</th>
				<th colspan='5'>Credit</th>
			</tr>
			<tr>
				<th>Date</th>
				<th>Account</th>
				<th>Ref No</th>
				<th>Amount</th>
				<th>Total</th>
				<th>&nbsp;</th>
				<th>Date</th>
				<th>Account</th>
				<th>Ref No</th>
				<th>Amount</th>
				<th>Total</th>
			</tr>";

	db_conn($prd);

    $refRslt = db_exec ($search) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
    if (pg_numrows ($refRslt) < 1) {
		return "<li> There are no Transactions in the selected Period.<br><br>"
			.mkQuickLinks(
				ql("index-reports.php", "Financials"),
				ql("index-reports-journal.php", "Current Year Details General Ledger Reports"),
				ql("../core/acc-new2.php", "Add New Account")
			);
	}

	while ($ref = pg_fetch_array ($refRslt)){

		# Get linked transactions
		$search = "SELECT * FROM \"$ref[prd]\".transect WHERE refnum = '$ref[refnum]'";
		$tranRslt = db_exec ($search) or errDie ("ERROR: Unable to retrieve Transaction details from database.", SELF);
    		if (pg_numrows ($tranRslt) < 1) {
			continue;
		}

		$tot = 0;

		# Display all transaction
		while ($tran = pg_fetch_array ($tranRslt)){
			# Get vars from tran as the are in db
			foreach ($tran as $key => $value) {
				$$key = $value;
			}

			# Format date
			$date = explode("-", $date);
			$date = $date[2]."-".$date[1]."-".$date[0];

			$amount = sprint($amount);
			$tot += $amount;
			$OUTPUT .= "
				<tr class='".bg_class()."'>
					<td>$date</td>
					<td>$dtopacc/$daccnum&nbsp;&nbsp;&nbsp;$daccname</td>
					<td align='right'>$custom_refnum</td>
					<td align='right' nowrap>".CUR." $amount</td>
					<td>&nbsp;</td>
					<td class='".bg_class()."'>&nbsp;</td>
					<td>$date</td>
					<td>$ctopacc/$caccnum&nbsp;&nbsp;&nbsp;$caccname</td>
					<td align='right'>$refnum</td>
					<td align='right' nowrap>".CUR." $amount</td>
					<td>&nbsp;</td>
				</tr>";
		}

		$tot = sprint($tot);
		$OUTPUT .= "
				<tr class='".bg_class()."'>
					<td align='right'>&nbsp;</td>
					<td align='right'>&nbsp;</td>
					<td align='right'>$ref[refnum]</td>
					<td>&nbsp;</td>
					<td align='right' nowrap>".CUR." $tot</td>
					<td>&nbsp;</td>
					<td align='right'>&nbsp;</td>
					<td align='right'>&nbsp;</td>
					<td align='right'>$ref[refnum]</td>
					<td>&nbsp;</td>
					<td align='right' nowrap>".CUR." $tot</td>
				</tr>
				<tr class='".bg_class()."'>
					<td align='center' colspan='11'><br></td>
				</tr>";

	}

	$xlformat = str_replace("bgcolor", "", $OUTPUT);
	$xlformat = str_replace("table border=0", "table border=1", $xlformat);
	$xlformat = base64_encode($xlformat);

	$OUTPUT .= "
			<tr>
				<td align='center' colspan='10'><br></td>
			</tr>
			<tr>
				<td align='center' colspan='10'>
					<form action='".SELF."' method='POST' name='form'>
						<input type='hidden' name='key' value='xls'>
						<input type='hidden' name='xlformat' value='$xlformat'>
						<input type='submit' name='xls' value='Export to spreadsheet'>
					</form>
				</td>
			</tr>
		</table>"
		.mkQuickLinks(
			ql("index-reports.php", "Financials"),
			ql("index-reports-journal.php", "Current Year Details General Ledger Reports"),
			ql("../core/acc-new2.php", "Add New Account")
		);
	return $OUTPUT;

}



# Default view
function xls($_POST)
{

	# Get vars
	extract ($_POST);

	$OUTPUT = base64_decode($xlformat);

	# Send the stream
	include("../xls/temp.xls.php");
	Stream("DetailedGeneralLedger", $OUTPUT);

}


?>