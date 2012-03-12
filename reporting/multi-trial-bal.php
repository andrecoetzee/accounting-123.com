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

			case "printsave":
				$OUTPUT = print_saveacc($_POST);
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
function view()
{

	db_connect();
	$sql = "SELECT * FROM branches";
	$branRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$brans = "";
	while($bran = pg_fetch_array ($branRslt)){
		$sp = "&nbsp;&nbsp;&nbsp;";
		$brans .= "<tr bgcolor='".TMPL_tblDataColor1."'><td colspan=2><input type=checkbox name=divs[] checked=yes value='$bran[div]'>$sp $bran[branname]</td></tr>";
	}

	# Layout
	$view = "
	<h3>Trial Balance</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=350>
		<form action='".SELF."' method=post name=form>
		<input type=hidden name=key value=print>
		<tr><th>Field</th><th>Value</th></tr>
		<tr bgcolor='".TMPL_tblDataColor1."'><td>Include Accounts with Zero balances</td><td valign=center>
		<input type=radio name=zero value=yes>Yes | <input type=radio name=zero value=no checked=yes>No</td></tr>
		<tr><td><br></td></tr>
		<tr><th colspan=2>Select Branch</th></tr>
		$brans
		<tr><td><br></td></tr>
		<tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue >'></td></tr>
	</table>";

	return $view;
}

# print accounts
function printacc($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	if(!isset($divs)){
		return "<li class=err> Please select at least one branch for the report.";
	}

	db_connect();

	$head = "";
	$head2 = "";
	# Add each selected branch to the headers
	foreach($divs as $key => $div){
		$sql = "SELECT * FROM branches WHERE div = '$div'";
		$branRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
		$bran = pg_fetch_array ($branRslt);
		$head .= "<th colspan=2>$bran[branname]</th>";
		$head2 .= "<th>Debit</th><th>Credit</th>";
		$brans[] = $bran['div'];
		$tldebit[] = 0;
		$tlcredit[] = 0;
	}
	if(count($divs) > 1){
		$head .= "<th colspan=2><i>TOTAL</i></th>";
		$head2 .= "<th>Debit</th><th>Credit</th>";
	}

	# Set up table to display in
	$OUTPUT = "
	<center>
	<h3>Trial Balance</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."'>
	<tr><th colspan=2></th>$head</tr>
	<tr><th>Account Number</th><th>Account Name</th>$head2</tr>";

	# Connect to database
	core_connect();
	$sql = "SELECT DISTINCT topacc,accnum FROM trial_bal";
	$accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$numrows = pg_numrows ($accRslt);

	if ($numrows < 1) {
		return "<li class=err>There are no Accounts yet in Cubit.";
	}

	# Display all Accounts
	$i = 0;
	$ttldeb = 0;
	$ttlcred = 0;
	while($acc = pg_fetch_array ($accRslt)){
		if($zero == "no"){
			# Check balance across the branches
			$sql = "SELECT sum(credit) as credit, sum(debit) as debit FROM trial_bal WHERE accnum = '$acc[accnum]' AND topacc = '$acc[topacc]'";
			$acccRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
			$accc = pg_fetch_array ($acccRslt);
			if(($accc['debit'] - $accc['credit']) == 0){
				continue;
			}
		}

		# Alternate bgcolor
		$i++;
		$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

		# To get the account name
		$sql = "SELECT * FROM trial_bal WHERE accnum = '$acc[accnum]' AND topacc = '$acc[topacc]' LIMIT 1";
		$acccRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
		$accc = pg_fetch_array ($acccRslt);

		$OUTPUT .= "<tr bgcolor='$bgColor'><td>$accc[topacc]/$accc[accnum]</td><td>$accc[accname]</td>";

		$tldeb = 0;
		$tlcred = 0;

		# Get balances for each branch
		foreach($brans as $key => $value){
			$sql = "SELECT * FROM trial_bal WHERE accnum = '$acc[accnum]' AND topacc = '$acc[topacc]' AND div = '$value'";
			$subRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
			if(pg_numrows($subRslt) > 0){
				$sub = pg_fetch_array ($subRslt);
				$sub['debit'] = sprint($sub['debit']);
				$sub['credit'] = sprint($sub['credit']);

				if(floatval($sub['debit']) == 0){
					$OUTPUT .="<td align=center> - </td>";
				}else{
					$OUTPUT .="<td align=right>".CUR." $sub[debit]</td>";
				}

				if(floatval($sub['credit']) == 0){
					$OUTPUT .="<td align=center> - </td>";
				}else{
					$OUTPUT .="<td align=right>".CUR." $sub[credit]</td>";
				}

				$tldebit[$key] += $sub['debit'];
				$tlcredit[$key] += $sub['credit'];
				$tldeb += $sub['debit'];
				$tlcred += $sub['credit'];
			}else{
				$OUTPUT .="<td></td><td></td>";
			}
		}

		# If more than one view total
		if(count($divs) > 1){
			if(floatval($tldeb) == 0){
				$OUTPUT .="<td align=center> - </td>";
			}else{
				$tldeb = sprint($tldeb);
				$OUTPUT .="<td align=right>".CUR." $tldeb</td>";
			}
			if(floatval($tlcred) == 0){
				$OUTPUT .="<td align=center> - </td>";
			}else{
				$tlcred = sprint($tlcred);
				$OUTPUT .="<td align=right>".CUR." $tlcred</td>";
			}
			$ttldeb += $tldeb;
			$ttlcred += $tlcred;
		}
		$OUTPUT .="</tr>";
	}

	# Totals
	$OUTPUT .= "<tr bgcolor='".TMPL_tblDataColor2."'><td colspan=2><b>Total</b></td>";
	foreach($brans as $key => $value){
		$tldebit[$key] = sprint($tldebit[$key]);
		$tlcredit[$key] = sprint($tlcredit[$key]);
		$OUTPUT .= "<td align=center><b>".CUR." $tldebit[$key]</b></td><td align=center><b>".CUR." $tlcredit[$key]</b></td>";
	}

	# If more than one view total
	if(count($divs) > 1){
		$ttldeb = sprint($ttldeb);
		$ttlcred = sprint($ttlcred);
		$OUTPUT .= "<td align=center><b>".CUR." $ttldeb</b></td><td align=center><b>".CUR." $ttlcred</b></td>";
	}

	$OUTPUT .= "</tr>";
	$OUTPUT .= "<tr><td><br></td></tr>
	</table>
	<p>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>

	</table>";

	return $OUTPUT;
}

function print_saveacc($_POST)
{
	# Get vars
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}

	// Set up table to display in
	$OUTPUT = "
	<center>
	<h3>Trial Balance as at : ".date("d M Y")."</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=450>
	<tr><th>Account Number</th><th>Account Name</th><th>Debit</th><th>Credit</th></tr>";

	# Connect to database
	core_connect();
	$sql = "SELECT * FROM trial_bal WHERE div = '".USER_DIV."' ORDER BY topacc, accnum ASC";
	$accRslt = db_exec ($sql) or errDie ("ERROR: Unable to retrieve account details from database.", SELF);
	$numrows = pg_numrows ($accRslt);

	if ($numrows < 1) {
		return "<li class=err>There are no Accounts yet in Cubit.";
	}

	# display all Accounts
	$i=0;
	$tldebit = 0;
	$tlcredit = 0;

	if($zero == "no"){
		while($acc = pg_fetch_array ($accRslt)){
			# alternate bgcolor
			$i++;
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

			if(intval($acc['debit']) == 0 && intval($acc['credit']) == 0){
				continue;
			}
			$OUTPUT .= "<tr bgcolor='$bgColor'><td>$acc[topacc]/$acc[accnum]</td><td>$acc[accname]</td>";

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
			# alternate bgcolor
			$i++;
			$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;
			$OUTPUT .= "<tr bgcolor='$bgColor'><td>$acc[topacc]/$acc[accnum]</td><td>$acc[accname]</td>";

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
	$OUTPUT .= "<tr bgcolor='$bgColor'><td colspan=2><b>Total</b></td><td align=center><b>".CUR." $tldebit</b></td><td align=center><b>".CUR." $tlcredit</b></td></tr>
	</table><br>";

	$output = base64_encode($OUTPUT);
	core_connect();
	$sql = "INSERT INTO save_trial_bal(gendate, output, div) VALUES('".date("Y-m-d")."', '$output', '".USER_DIV."')";
	$Rs = db_exec($sql) or errdie("Unable to save the Trial Balance.");

	$OUTPUT .= "
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=25%>
		<tr><th>Quick Links</th></tr>
		<script>document.write(getQuicklinkSpecial());</script>
	</table>";

	return $OUTPUT;
}
?>
