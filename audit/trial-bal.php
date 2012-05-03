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

header("Location: ../reporting/trial_bal-view.php");
exit;

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
function view(){

	// Select previous year database
	preg_match ("/yr(\d*)/", YR_DB, $id);
	$i = $id['1'];
	$i--;
	if(intval($i) == 0){
		return "<li class=err> Error : Your are on the first year of cubit operation, there are no previous closed years";
	}
	$yrdb ="yr".$i;

	// Get prev year name
	core_connect();
	$sql = "SELECT * FROM year WHERE yrdb ='$yrdb'";
	$rslt = db_exec($sql);
	$yr = pg_fetch_array($rslt);
	$yrname = $yr['yrname'];

	/*
	core_connect();
	$sql = "SELECT batchid FROM batch WHERE proc = 'no'";
	$Rs = db_exec($sql) or errdie("Batch file unreachable.");
	if(pg_numrows($Rs) > 0){
		$sum = pg_numrows($Rs);
		$out = pg_fetch_array($Rs);
		$note = "<tr class='bg-even'><td colspan=2 class=err><li>Note : There are $sum unprocessed batch entries.</td></tr><tr><td><br></td></tr>";
	}else{
		$note = "";
	}
	*/

	$view = "
	<h3>Trial Balance for previous year : $yrname</h3>
	<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=300>
	<form action='".SELF."' method=post name=form>
	<input type=hidden name=key value=print>
	<input type=hidden name=yrdb value='$yrdb'>
	<input type=hidden name=yrname value='$yrname'>
	<tr><th>Field</th><th>Value</th></tr>
	<tr class='bg-even'><td>Select Period</td><td><select name=prd>";
                db_conn($yrdb);
                $sql = "SELECT * FROM info WHERE prdname !=''";
                $prdRslt = db_exec($sql);
                $rows = pg_numrows($prdRslt);
                if(empty($rows)){
                        return "ERROR : There are no periods set for the current year";
                }
                while($prd = pg_fetch_array($prdRslt)){
                        if($prd['prddb'] == PRD_DB){
                               $sel = "selected";
                        }else{
                                $sel = "";
                        }
                        $view .="<option value='$prd[prddb]' $sel>$prd[prdname]</option>";
                }
                $view .= "
    </select></td></tr>
	<tr class='bg-odd'><td>Include Accounts with Zero balances</td><td valign=center>
	<input type=radio name=zero value=yes>Yes | <input type=radio name=zero value=no checked=yes>No</td></tr>
	<tr><td><br></td></tr>
	<tr><td><input type=button value='< Cancel' onClick='javascript:history.back();'></td><td valign=center><input type=submit value='Continue >'></td></tr>
	</table>";

	return $view;
}

function ret($OUTPUT){
	require("../template.php");
}

function printacc($_POST)
{
		# get vars
		foreach ($_POST as $key => $value) {
			$$key = $value;
		}

		# get prd name
		db_conn($yrdb);
		$sql = "SELECT * FROM info WHERE prddb ='$prd'";
		$prdRslt = db_exec($sql);
		$prds = pg_fetch_array($prdRslt);
		$prdname = $prds['prdname'];

		// Set up table to display in
		$OUTPUT = "
        <center>
        <h3>Trial Balance for $prdname in previous year : $yrname</h3>

		<table border=0 cellpadding='".TMPL_tblCellPadding."' cellspacing='".TMPL_tblCellSpacing."' width=450>
        <tr><th>Account Number</th><th>Account Name</th><th>Debit</th><th>Credit</th></tr>";

		// Connect to database
		db_conn($yrdb);
        $sql = "SELECT * FROM $prdname WHERE div = '".USER_DIV."' ORDER BY topacc, accnum ASC";
        $accRslt = @db_exec ($sql) or ret ("<li class=err>Unable to retrieve data from Cubit. Period <b>$prdname</b> was not properly close on previous year.", SELF);
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
				# alternate bgcolor
				$i++;
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				if(floatval($acc['debit']) == 0 && floatval($acc['credit']) == 0){
					$i++;
					continue;
				}
				$OUTPUT .= "<tr bgcolor='$bgColor'><td>$acc[topacc]/$acc[accnum]</td><td>$acc[accname]</td>";

				if(floatval($acc['debit']) == 0){
					$OUTPUT .="<td align=center> - </td>";
				}else{
					$OUTPUT .="<td align=center>".CUR." $acc[debit]</td>";
				}

				if(floatval($acc['credit']) == 0){
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

				if(floatval($acc['debit']) == 0){
					$OUTPUT .="<td align=center> - </td>";
				}else{
					$OUTPUT .="<td align=center>".CUR." $acc[debit]</td>";
				}

				if(floatval($acc['credit']) == 0){
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
        $sql = "SELECT * FROM trial_bal WHERE div = '".USER_DIV."' ORDER BY topacc, accnum ASC";
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
				# alternate bgcolor
				$i++;
				$bgColor = ($i % 2) ? TMPL_tblDataColor2 : TMPL_tblDataColor1;

				if(floatval($acc['debit']) == 0 && floatval($acc['credit']) == 0){
					continue;
				}
				$OUTPUT .= "<tr bgcolor='$bgColor'><td>$acc[topacc]/$acc[accnum]</td><td>$acc[accname]</td>";

				if(floatval($acc['debit']) == 0){
					$OUTPUT .="<td align=center> - </td>";
				}else{
					$OUTPUT .="<td align=center>".CUR." $acc[debit]</td>";
				}

				if(floatval($acc['credit']) == 0){
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

				if(floatval($acc['debit']) == 0){
					$OUTPUT .="<td align=center> - </td>";
				}else{
					$OUTPUT .="<td align=center>".CUR." $acc[debit]</td>";
				}

				if(floatval($acc['credit']) == 0){
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
