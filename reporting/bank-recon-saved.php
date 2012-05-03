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
require("../settings.php");
require("../core-settings.php");
require("../libs/ext.lib.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
                case "viewsaved":
			$OUTPUT = viewsaved($_POST);
			break;
                default:
			$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

# get template
require("../template.php");


# Default view
function view()
{

	// Layout
	$view = "
			<h3>View Saved Bank Reconciliations</h3>
			<table ".TMPL_tblDflts." width='350'>
			<form action='".SELF."' method='POST' name='form'>
				<input type='hidden' name='key' value='viewsaved'>
				<tr>
					<th>Field</th>
					<th>Value</th>
				</tr>
				<tr class='".bg_class()."'>
					<td>Bank Account</td>
					<td valign='center'>
						<select name='bankid'>";

	db_connect();
	$sql = "SELECT * FROM bankacct WHERE div = '".USER_DIV."'";
	$banks = db_exec($sql);
	$numrows = pg_numrows($banks);

	if(empty($numrows)){
			return "<li class='err'> There are no accounts held at the selected Bank.
			<p><input type=button onClick='JavaScript:history.back();' value='&laquo; Correct Selection'>";
	}

	while($acc = pg_fetch_array($banks)){
			$view .= "<option value=$acc[bankid]>$acc[accname] - $acc[bankname] ($acc[acctype])</option>";
	}

	$view .= "
						</select>
					</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>From :</td>
					<td valign='center'>".mkDateSelect("from")."</td>
				</tr>
				<tr class='".bg_class()."'>
					<td>To :</td>
					<td valign='center'>".mkDateSelect("to")."</td>
				</tr>
				<tr><td><br></td></tr>
				<tr>
					<td></td>
					<td align='right'><input type='submit' value='View &raquo'></td>
				</tr>
				</table>
				<p>
				<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
					<tr><th>Quick Links</th></tr>
					<tr class='bg-odd'><td><a href='index-reports.php'>Financials</a></td></tr>
					<tr class='bg-odd'><td><a href='index-reports-banking.php'>Banking Reports</a></td></tr>
					<tr class='bg-odd'><td><a href='../main.php'>Main Menu</a></td></tr>
				</table>";

	return $view;
}

# View cash book
function viewsaved($_POST)
{
	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");

	$v = new  validate ();
	$v->isOk ($bankid, "num", 1, 30, "Invalid Bank Account.");
	$v->isOk ($from_day, "num", 1, 2, "Invalid Day for the 'From' date.");
	$v->isOk ($from_month, "num", 1, 2, "Invalid month for the 'From' date..");
	$v->isOk ($from_year, "num", 1, 4, "Invalid year for the 'From' date..");
	$v->isOk ($to_day, "num", 1, 2, "Invalid Day for the 'To' date.");
	$v->isOk ($to_month, "num", 1, 2, "Invalid month for the 'To' date..");
	$v->isOk ($to_year, "num", 1, 4, "Invalid year for the 'To' date..");

	# Lets mix the date
	$from = sprintf("%02.2d",$from_year)."-".sprintf("%02.2d",$from_month)."-".$from_day;
	$to = sprintf("%02.2d",$to_year)."-".sprintf("%02.2d",$to_month)."-".$to_day;

	# display errors, if any
	if ($v->isError ()) {
		$confirm = "";
		$errors = $v->getErrors();
		foreach ($errors as $e) {
			$confirm .= "<li class='err'>".$e["msg"]."</li>";
		}
		$confirm .= "<p><input type='button' onClick='JavaScript:history.back();' value='&laquo; Correct submission'>";
		return $confirm;
	}

	# get bank details
	$bankRslt = get("cubit", "accname,bankname", "bankacct", "bankid", $bankid);
	$bank = pg_fetch_array($bankRslt);

	// Query server
	core_connect();
	$sql = "SELECT * FROM save_bank_recon WHERE bankid = '$bankid' AND gendate >= '$from' AND gendate <= '$to' AND div = '".USER_DIV."'";
	$Rslt = db_exec ($sql) or errDie ("ERROR: Unable to view saved Trial balances", SELF);          // Die with custom error if failed

	if (pg_numrows ($Rslt) < 1) {
		$OUTPUT = "<li> There are no saved Bank Reconciliations.";
	} else {
		// Layout
		$OUTPUT = "
				<h3>View Saved Bank Reconciliations</h3>
				<table ".TMPL_tblDflts." width='300'>
					<tr>
						<th>Bank Recon No.</th>
						<th>Date</th>
					</tr>";

		// Display all statements
		for ($i=0; $recon = pg_fetch_array ($Rslt); $i++) {
			# Date format
			$date = explode("-", $recon['gendate']);
			$date = $date[2]."-".$date[1]."-".$date[0];

			$OUTPUT .= "
					<tr class='".bg_class()."'>
						<td>$recon[id]</td>
						<td>$date</td>
						<td><a target='_blank' href='bank-recon-print.php?id=$recon[id]'>Print</a></td>
					</tr>";
		}
		$OUTPUT .= "	</table>";
	}


// 			.mkQuickLinks(
// 				ql("public_holiday_add.php", "Add Public Holiday"),
// 				ql("public_holiday_list.php", "View Public Holidays")
// 			);

	$OUTPUT .= "
			<p>
			<table ".TMPL_tblDflts.">
				<tr>
					<th>Quick Links</th>
				</tr>
				<tr class='".bg_class()."'>
					<td align=center><a target=_blank href='../core/acc-new2.php'>Add account (New Window)</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='index-reports.php'>Financials</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='index-reports-banking.php'>Banking Reports</a></td>
				</tr>
				<tr class='".bg_class()."'>
					<td><a href='../main.php'>Main Menu</a></td>
				</tr>
			</table>";

	// Call template to display the info and die
	return $OUTPUT;
}
?>
