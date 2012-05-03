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
		case "viewrep":
			$OUTPUT = viewrep($_POST);
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

	// main layout
	$view = "
		<h3>View Petty Cash Book Report</h3>
		<table ".TMPL_tblDflts." width='350'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='viewrep'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>From :</td>
				<td valign='center'>".mkDateSelect("from")."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>To :</td>
				<td valign='center'>".mkDateSelect("to")."</td>
			</tr>
			".TBL_BR."
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='View &raquo'></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='petty-req-add.php'>Add Petty Cash Requisition</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='pettycashbook-view.php'>View Petty Cash Requisitions</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $view;

}


# view cash book
function viewrep($_POST)
{

	# get vars
	extract ($_POST);

	# validate input
	require_lib("validate");
	$v = new  validate ();
	$v->isOk ($from_day, "num", 1, 2, "Invalid Day for the 'From' date.");
	$v->isOk ($from_month, "num", 1, 2, "Invalid month for the 'From' date..");
	$v->isOk ($from_year, "num", 1, 4, "Invalid year for the 'From' date..");
	$v->isOk ($to_day, "num", 1, 2, "Invalid Day for the 'To' date.");
	$v->isOk ($to_month, "num", 1, 2, "Invalid month for the 'To' date..");
	$v->isOk ($to_year, "num", 1, 4, "Invalid year for the 'To' date..");

	# lets mix the date
	$from = $from_day."-".$from_month."-".$from_year;
	$to = $to_day."-".$to_month."-".$to_year;

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


	db_Connect ();

	# date format
	$sfrom = explode("-", $from);
	$sfrom = $sfrom[2]."-".$sfrom[1]."-".$sfrom[0];
	$sto = explode("-", $to);
	$sto = $sto[2]."-".$sto[1]."-".$sto[0];

	// Layout
	$OUTPUT = "
		<center>
		<h3>Petty Cash Book Report<br><br>From : $from To : $to</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Type</th>
				<th>Details</th>
				<th>Amount</th>
			</tr>";

	$rtotal = 0;
	$qtotal = 0;
	$ttotal = 0;

	$sql = "SELECT * FROM pettyrec WHERE date >= '$sfrom' AND date <= '$sto' AND div = '".USER_DIV."' ORDER BY date DESC";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve petty cash entrie from database.", SELF);

	if (pg_numrows ($cashRslt) < 1) {
		$OUTPUT .= "
			<tr>
				<td colspan='4' align='center'><li class='err'>There are no enties found on the selected date range.</td>
			</tr>";
	}else{
		# display all bank Deposits
		for ($i=0; $cash = pg_fetch_array ($cashRslt); $i++) {

			$cash['date'] = explode("-", $cash['date']);
			$cash['date'] = $cash['date'][2]."-".$cash['date'][1]."-".$cash['date'][0];

			$OUTPUT .= "
				<tr class='".bg_class()."'>
					<td>$cash[date]</td>
					<td>$cash[name]</td>
					<td>$cash[det]</td>
					<td>".CUR." ".sprint ($cash['amount'])."</td>
				</tr>";

			if($cash['type'] == "Change"){
				$rtotal += $cash['amount'];
			}elseif($cash['type'] == "Req"){
				$qtotal += $cash['amount'];
			}elseif($cash['type'] == "Transfer"){
				$ttotal += $cash['amount'];
			}
		}

		# requisition total must be positive
		$qtotal = ($qtotal * (-1));

		# Get available funds
		$cashacc = gethook("accnum", "bankacc", "name", "Petty Cash");

		core_connect();

		$sql = "SELECT (debit - credit) as bal FROM trial_bal WHERE accid = '$cashacc' AND div = '".USER_DIV."' AND month = '".PRD_DB."'";
		$accbRslt = db_exec($sql);
		if(pg_numrows($accbRslt) < 1){
			return "<li class='err'> Petty Cash Account not found.</li>";
		}
		$accb = pg_fetch_array($accbRslt);
		$balance = sprint($accb['bal']);

		# print the total
		$OUTPUT .= "
			".TBL_BR."
			<tr class='".bg_class()."''>
				<td colspan='3'><b>Total Transfer</b></td>
				<td><b>".CUR." ".sprint($ttotal)."</b></td>
			</tr>
			<tr class='".bg_class()."''>
				<td colspan='3'><b>Total Requisitions</b></td>
				<td><b>".CUR." ".sprint($qtotal)."</b></td>
			</tr>
			<tr class='".bg_class()."''>
				<td colspan='3'><b>Total Returned</b></td>
				<td><b>".CUR." ".sprint($rtotal)."</b></td>
			</tr>
			".TBL_BR."
			<tr class='".bg_class()."'>
				<td colspan='3'><b>Balance</b></td>
				<td><b>".CUR." ".sprint($balance)."</b></td>
			</tr>";
	}

	$OUTPUT .= "
			".TBL_BR."
			<tr>
				<td colspan='4' align='center'><input type=button value='Select Date Range' onClick=\"javascript:document.location.href='pettycash-rep.php'\"></td>
			</tr>
		</table>
		<p>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Quick Links</th>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='petty-req-add.php'>Add Petty Cash Requisition</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='pettycashbook-view.php'>View Petty Cash Requisitions</a></td>
			</tr>
			<tr class='".bg_class()."'>
				<td><a href='../main.php'>Main Menu</a></td>
			</tr>
		</table>";
	return $OUTPUT;

}


?>
