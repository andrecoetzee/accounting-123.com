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
require ("../settings.php");          // Get global variables & functions
require("../core-settings.php");

# decide what to do
if (isset($_POST["key"])) {
	switch ($_POST["key"]) {
			case "print":
				$OUTPUT = printacc($_POST);
				break;

			default:
				$OUTPUT = view();
	}
} else {
        # Display default output
        $OUTPUT = view();
}

require ("../template.php");

# Default View
function view(){

	core_connect();
	$sql = "SELECT sum(batchid) FROM batch WHERE proc = 'no'";
	$Rs = db_exec($sql) or errdie("Batch file unreachable.");
	if(pg_numrows($Rs) > 0){
		$sum = pg_numrows($Rs);
		$out = pg_fetch_array($Rs);
		$note = "<tr class='bg-even'><td colspan=2 class=err><li>Note : There are $sum unprocessed batch entries.</td></tr><tr><td><br></td></tr>";
	}else{
		$note = "";
	}

	$view = "
	<h3>Trial Balance</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=print>
	$note
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-odd'><td>Include Accounts with Zero balances</td><td valign=center>
	<input type=radio name=zero value=yes>Yes | <input type=radio name=zero value=no checked=yes>No</td></tr>
	<tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue >'></td></tr>
	</table>";

	return $view;
}

function print_saveacc($_POST)
{
		# get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}

		// Set up table to display in
		$OUTPUT = "
        <center>
        <h3>Trial Balance as at : ".date("d M Y")."</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=450>
        <tr><th>Account Number</th><th>Account Name</th><th>Debit</th><th>Credit</th></tr>";

		// Connect to database
		core_connect();
        $sql = "SELECT * FROM trial_bal ORDER BY topacc, accnum ASC";
        $accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
		$numrows = pg_numrows ($accRslt);

        if ($numrows < 1) {
			$OUTPUT = "There are no Accounts yet in Cubit.";
			require ("../template.php");
		}

		# display all Accounts
        $i=0;
        $tldebit = 0;
        $tlcredit = 0;

		if($zero == "no"){
			while($acc = pg_fetch_array ($accRslt)){
				$i++;

				if(intval($acc['debit']) == 0 && intval($acc['credit']) == 0){
					continue;
				}
				$OUTPUT .= "<tr class='".bg_class()."'><td>$acc[topacc]/$acc[accnum]</td><td>$acc[accname]</td>";

				if(intval($acc['debit']) == 0){
					$OUTPUT .="<td align=center> - </td>";
				}else{
					$OUTPUT .="<td align=center>".CUR." $acc[debit]</td>";
				}

				if(intval($acc['credit']) == 0){
					$OUTPUT .="<td align=center> - </td>";
				}else{
					$OUTPUT .="<td align=center>".CUR." $acc[credit]</td>";
				}

				$OUTPUT .="</tr>";

				$tldebit += $acc['debit'];
				$tlcredit += $acc['credit'];
			}
		}elseif($zero == "yes"){
			while($acc = pg_fetch_array ($accRslt)){
				$i++;
				$OUTPUT .= "<tr class='".bg_class()."'><td>$acc[topacc]/$acc[accnum]</td><td>$acc[accname]</td>";

				if(intval($acc['debit']) == 0){
					$OUTPUT .="<td align=center> - </td>";
				}else{
					$OUTPUT .="<td align=center>".CUR." $acc[debit]</td>";
				}

				if(intval($acc['credit']) == 0){
					$OUTPUT .="<td align=center> - </td>";
				}else{
					$OUTPUT .="<td align=center>".CUR." $acc[credit]</td>";
				}

				$OUTPUT .="</tr>";

				$tldebit += $acc['debit'];
				$tlcredit += $acc['credit'];
			}
		}
        $OUTPUT .= "<tr class='".bg_class()."'><td colspan=2><b>Total</b></td><td align=center><b>".CUR." $tldebit</b></td><td align=center><b>".CUR." $tlcredit</b></td></tr>
		</table><br>";

		$output = base64_encode($OUTPUT);
		core_connect();
		$sql = "INSERT INTO save_trial_bal(gendate, output) VALUES('".date("Y-m-d")."', '$output')";
		$Rs = db_exec($sql) or errdie("Unable to save the Trial Balance.");

		$OUTPUT .= "
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
			<tr><th>Quick Links</th></tr>
			<script>document.write(getQuicklinkSpecial());</script>
		</table>";

		return $OUTPUT;
}

// saving a trial balance
function trial_save()
{
		// Set up table to display in
		$OUTPUT = "
        <center>
        <h3>Trial Balance as at : ".date("d M Y")."</h3>
		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=450>
        <tr><th>Account Number</th><th>Account Name</th><th>Debit</th><th>Credit</th></tr>";

		// Connect to database
		core_connect();
        $sql = "SELECT * FROM trial_bal ORDER BY topacc, accnum ASC";
        $accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
		$numrows = pg_numrows ($accRslt);

        if ($numrows < 1) {
			$OUTPUT = "There are no Accounts yet in Cubit.";
			require ("../template.php");
		}

		# display all Accounts
        $i=0;
        $tldebit = 0;
        $tlcredit = 0;

		while($acc = pg_fetch_array ($accRslt)){
			$i++;

			if(intval($acc['debit']) == 0 && intval($acc['credit']) == 0){
				continue;
			}
			$OUTPUT .= "<tr class='".bg_class()."'><td>$acc[topacc]/$acc[accnum]</td><td>$acc[accname]</td>";

			if(intval($acc['debit']) == 0){
				$OUTPUT .="<td align=center> - </td>";
			}else{
				$OUTPUT .="<td align=center>".CUR." $acc[debit]</td>";
			}

			if(intval($acc['credit']) == 0){
				$OUTPUT .="<td align=center> - </td>";
			}else{
				$OUTPUT .="<td align=center>".CUR." $acc[credit]</td>";
			}

			$OUTPUT .="</tr>";

			$tldebit += $acc['debit'];
			$tlcredit += $acc['credit'];
		}
		$OUTPUT .= "<tr class='".bg_class()."'><td colspan=2><b>Total</b></td><td align=center><b>".CUR." $tldebit</b></td><td align=center><b>".CUR." $tlcredit</b></td></tr>
		</table><br>";

		$output = base64_encode($OUTPUT);
		core_connect();
		$sql = "INSERT INTO save_trial_bal(gendate, output) VALUES('".date("Y-m-d")."', '$output')";
		$Rs = db_exec($sql) or errdie("Unable to save the Trial Balance.");

		return true;
}
?>
