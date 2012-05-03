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
		case "viewcash":
			$OUTPUT = viewcash($_POST);
			break;
		case "viewallcash":
			$OUTPUT = viewallcash($_POST);
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
		<h3>View Petty Cash Requisistions</h3>
		<table ".TMPL_tblDflts." width='350'>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='viewcash'>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
			<tr class='".bg_class()."'>
				<td>From :</td>
				<td valign='center'>".mkDateSelect("from",date("Y"),date("m"),"01")."</td>
			</tr>
			<tr class='".bg_class()."'>
				<td>To :</td>
				<td valign='center'>".mkDateSelect("to")."</td>
			</tr>
			<tr><td><br></td></tr>
			<tr>
				<td align='right'><input type='button' value='&laquo Back' onClick='javascript:history.back()'></td>
				<td align='right'><input type='submit' value='View &raquo'></td>
			</tr>
		</form>
		<form action='".SELF."' method='POST' name='form'>
			<input type='hidden' name='key' value='viewallcash'>
			<input type='hidden' name='order' value=''>
			<tr><td><br></td></tr>
			<tr>
				<td colspan='2'><input type='submit' value='View All Entries'></td>
			</tr>
		</form>
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
function viewcash($_POST)
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
		<h3>Petty Cash Book <br><br>
			From : $from To : $to</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Paid to</th>
				<th>Details</th>
				<th>Amount</th>
				<th>VAT Amount</th>
				<th>Receipt/Ref No.</th>
				<th>Account Paid to</th>
				<th colspan='3'>Options</th>
			</tr>";

	$rtotal = 0;
	$totout = 0;

	$sql = "SELECT * FROM pettycashbook WHERE  date >= '$sfrom' AND date <= '$sto' AND div = '".USER_DIV."' AND (reced = 'no' OR (length(reced) < 1) OR reced IS NULL) ORDER BY date DESC";
	$cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve petty cash entrie from database.", SELF);

	if (pg_numrows ($cashRslt) < 1) {
		$OUTPUT .= "<tr><td colspan='7' align='center'><li class='err'>There are no entries found on the selected date range.</td></tr>";
	}else{
		# display all bank Deposits
		for ($i=0; $cash = pg_fetch_array ($cashRslt); $i++) {

			# get account name for account involved
			$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $cash['accid']);
			$acc = pg_fetch_array($accRslt);

			$cash['date'] = explode("-", $cash['date']);
			$cash['date'] = $cash['date'][2]."-".$cash['date'][1]."-".$cash['date'][0];

			if ($cash['chrgvat'] == "exc")
				$cash['amount'] = $cash['amount'] + $cash['vat_paid'];

			$rtotal += $cash['amount']; // add to rtotal

			$cash['amount'] = sprint ($cash['amount']);

			$get_vcod = "SELECT vat_amount FROM vatcodes WHERE id = '$cash[vatcode]' LIMIT 1";
			$run_vcod = db_exec($get_vcod) or errDie ("Unable to get vat code information.");
			if (pg_numrows($run_vcod) < 1){
				$varr['vat_amount'] = 0;
			}else {
				$varr = pg_fetch_array ($run_vcod);
			}

			if ($cash['chrgvat'] == "inc"){
				#calculate vat amt inc ...
				$vatamt = $cash['amount'] - ($cash['amount'] / (1 + ($varr['vat_amount']/100)));
			}else {
				#calculate vat amt excl ...
				$vatamt = ($cash['amount'] / 100) * $varr['vat_amount'];
			}
			$vatamt = sprint ($vatamt);

			$OUTPUT .= "
				<tr class='".bg_class()."'>
					<td>$cash[date]</td>
					<td>$cash[name]</td>
					<td>$cash[det]</td>
					<td>".CUR." $cash[amount]</td>
					<td>".CUR." $vatamt</td>
					<td>$cash[refno]</td>
					<td>$acc[topacc]/$acc[accnum]  $acc[accname]</td>";

			if($cash['approved'] == "n"){
				$totout += $cash['amount'];
				$OUTPUT .= "
						<td><a href='petty-req-edit.php?cashid=$cash[cashid]'>Edit</td>
						<td><a href='petty-req-can.php?cashid=$cash[cashid]'>Cancel</td>
						<td><a href='petty-req-app.php?cashid=$cash[cashid]'>Approve</td>
					</tr>";
			}else{
				$OUTPUT .= "
						<td colspan=3><a href='petty-req-recpt.php?cashid=$cash[cashid]'>Record Receipt</td>
					</tr>";
			}
		}

		# print the total
		$OUTPUT .= "
			<tr><td><br></td></tr>
			<tr class='".bg_class()."''>
				<td colspan='3'><b>Total Requisitions</b></td>
				<td><b>".CUR." ".sprint($rtotal)."</b></td>
			</tr>
			<tr class='".bg_class()."''>
				<td colspan='3'><b>Total Outstanding Requisitions</b></td>
				<td><b>".CUR." ".sprint($totout)."</b></td>
			</tr>";
	}

    $OUTPUT .= "
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



# view outstanding cash book
function viewallcash($_POST)
{

	# get vars
	extract ($_POST);

	db_Connect ();

	// Layout
	$OUTPUT = "
		<center>
		<h3>Petty Cash Book</h3>
		<table ".TMPL_tblDflts.">
			<tr>
				<th>Date</th>
				<th>Paid to</th>
				<th>Details</th>
				<th>Amount</th>
				<th>VAT Amount</th>
				<th>Receipt/Ref No.</th>
				<th>Account Paid to</th>
				<th colspan='3'>Options</th>
			</tr>";

    $rtotal = 0;
	$totout = 0;

    $sql = "SELECT * FROM pettycashbook WHERE div = '".USER_DIV."' ORDER BY date DESC";
    $cashRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve petty cash entrie from database.", SELF);

    if (pg_numrows ($cashRslt) < 1) {
    	$OUTPUT .= "
			<tr>
				<td colspan='7' align='center'><li class='err'>There are no enties found on the selected date range.</td>
			</tr>";
	}else{
		# display all bank Deposits
		for ($i=0; $cash = pg_fetch_array ($cashRslt); $i++) {

			# get account name for account involved
			$accRslt = get("core", "accname,topacc,accnum", "accounts", "accid", $cash['accid']);
			$acc = pg_fetch_array($accRslt);

			$cash['date'] = explode("-", $cash['date']);
			$cash['date'] = $cash['date'][2]."-".$cash['date'][1]."-".$cash['date'][0];

			$rtotal += $cash['amount']; // add to rtotal

			if ($cash['chrgvat'] == "exc")
				$cash['amount'] = $cash['amount'] + $cash['vat_paid'];

			$cash['amount'] = sprint ($cash['amount']);

			$get_vcod = "SELECT vat_amount FROM vatcodes WHERE id = '$cash[vatcode]' LIMIT 1";
			$run_vcod = db_exec($get_vcod) or errDie ("Unable to get vat code information.");
			if (pg_numrows($run_vcod) < 1){
				$varr['vat_amount'] = 0;
			}else {
				$varr = pg_fetch_array ($run_vcod);
			}

			if ($cash['chrgvat'] == "inc"){
				#calculate vat amt inc ...
				$vatamt = $cash['amount'] - ($cash['amount'] / (1 + ($varr['vat_amount']/100)));
			}else {
				#calculate vat amt excl ...
				$vatamt = ($cash['amount'] / 100) * $varr['vat_amount'];
			}
			$vatamt = sprint ($vatamt);

			$OUTPUT .= "
				<tr class='".bg_class()."'>
					<td>$cash[date]</td>
					<td>$cash[name]</td>
					<td>$cash[det]</td>
					<td>".CUR." $cash[amount]</td>
					<td>".CUR." $vatamt</td>
					<td>$cash[refno]</td>
					<td>$acc[topacc]/$acc[accnum]  $acc[accname]</td>";

			if($cash['approved'] == "n"){
				$totout += $cash['amount'];
				$OUTPUT .= "
						<td><a href='petty-req-edit.php?cashid=$cash[cashid]'>Edit</td>
						<td><a href='petty-req-can.php?cashid=$cash[cashid]'>Cancel</td>
						<td><a href='petty-req-app.php?cashid=$cash[cashid]'>Approve</td>
					</tr>";
			}else{
				$OUTPUT .= "<td colspan='3'><a href='petty-req-recpt.php?cashid=$cash[cashid]'>Record Receipt</td></tr>";
			}
		}

		# print the total
		$OUTPUT .= "
			<tr><td><br></td></tr>
			<tr class='".bg_class()."''>
				<td colspan='3'><b>Total Requisitions</b></td>
				<td><b>".CUR." ".sprint($rtotal)."</b></td>
			</tr>
			<tr class='".bg_class()."''>
				<td colspan='3'><b>Total Outstanding Requisitions</b></td>
				<td><b>".CUR." ".sprint($totout)."</b></td>
			</tr>";
	}

    $OUTPUT .= "
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